<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestData(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respond(400, ['success' => false, 'message' => 'Format data permintaan tidak valid.']);
    }
    return $data;
}

function validateStudent(array $student): array
{
    $name = trim((string)($student['name'] ?? ''));
    $nim = trim((string)($student['nim'] ?? ''));
    $studentClass = trim((string)($student['studentClass'] ?? ''));
    $classes = ['PW2-IF1', 'PW2-IF2', 'PW2-IF3', 'PW2-EXT'];

    if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
        respond(422, ['success' => false, 'message' => 'Nama harus terdiri dari 3 sampai 150 karakter.']);
    }
    if (mb_strlen($nim) < 5 || mb_strlen($nim) > 30) {
        respond(422, ['success' => false, 'message' => 'NIM harus terdiri dari 5 sampai 30 karakter.']);
    }
    if (!in_array($studentClass, $classes, true)) {
        respond(422, ['success' => false, 'message' => 'Kelas yang dipilih tidak valid.']);
    }

    return ['name' => $name, 'nim' => $nim, 'studentClass' => $studentClass];
}

require_once __DIR__ . '/question_bank.php';

function getAttempt(PDO $pdo, string $attemptId, bool $lock = false): array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $attemptId)) {
        respond(422, ['success' => false, 'message' => 'Sesi ujian tidak valid.']);
    }
    $statement = $pdo->prepare(
        'SELECT a.*, s.name, s.student_class FROM exam_attempts a
         INNER JOIN students s ON s.nim = a.student_nim
         WHERE a.attempt_id = :attempt_id' . ($lock ? ' FOR UPDATE' : '')
    );
    $statement->execute(['attempt_id' => $attemptId]);
    $attempt = $statement->fetch();
    if (!$attempt) {
        respond(404, ['success' => false, 'message' => 'Sesi ujian tidak ditemukan.']);
    }
    return $attempt;
}

function insertAnswer(PDO $pdo, string $attemptId, array $question, string $answer, bool $timedOut): void
{
    $correct = !$timedOut && validateExamAnswer($question, $answer);
    $statement = $pdo->prepare(
        'INSERT INTO exam_answers
            (attempt_id, question_id, student_answer, is_correct, is_timeout)
         VALUES (:attempt_id, :question_id, :student_answer, :is_correct, :is_timeout)'
    );
    $statement->execute([
        'attempt_id' => $attemptId,
        'question_id' => $question['id'],
        'student_answer' => $answer,
        'is_correct' => $correct ? 1 : 0,
        'is_timeout' => $timedOut ? 1 : 0
    ]);
}

function completeAttempt(PDO $pdo, array $attempt): array
{
    $statement = $pdo->prepare(
        'SELECT question_id, student_answer, is_correct, is_timeout
         FROM exam_answers WHERE attempt_id = :attempt_id ORDER BY question_id'
    );
    $statement->execute(['attempt_id' => $attempt['attempt_id']]);
    $savedAnswers = $statement->fetchAll();
    $questions = examQuestions();
    $details = [];
    $theoryCorrect = 0;
    $practiceCorrect = 0;
    $unanswered = 0;
    foreach ($savedAnswers as $saved) {
        $question = $questions[(int)$saved['question_id'] - 1];
        $correct = (bool)$saved['is_correct'];
        if ($correct && $question['type'] === 'theory') $theoryCorrect++;
        if ($correct && $question['type'] === 'practice') $practiceCorrect++;
        if ((string)$saved['student_answer'] === '') $unanswered++;
        $details[] = [
            'questionId' => (int)$question['id'],
            'questionTitle' => $question['title'],
            'type' => $question['type'],
            'studentAnswer' => (string)$saved['student_answer'],
            'correctKeyOrRef' => $question['reference'],
            'isCorrect' => $correct,
            'points' => $correct ? 5 : 0,
            'isTimeout' => (bool)$saved['is_timeout']
        ];
    }
    $totalCorrect = $theoryCorrect + $practiceCorrect;
    $completedAt = time();
    $elapsed = max(0, $completedAt - (int)$attempt['started_epoch']);
    $update = $pdo->prepare(
        "UPDATE exam_attempts SET status = 'completed', completed_epoch = :completed_epoch
         WHERE attempt_id = :attempt_id"
    );
    $update->execute(['completed_epoch' => $completedAt, 'attempt_id' => $attempt['attempt_id']]);
    $save = $pdo->prepare(
        'INSERT INTO exam_results
            (attempt_id, student_nim, score, total_correct, theory_correct, practice_correct,
             total_questions, unanswered_count, completion_time_seconds, exam_date, details)
         VALUES
            (:attempt_id, :student_nim, :score, :total_correct, :theory_correct, :practice_correct,
             20, :unanswered_count, :completion_time_seconds, :exam_date, :details)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $save->execute([
        'attempt_id' => $attempt['attempt_id'],
        'student_nim' => $attempt['student_nim'],
        'score' => $totalCorrect * 5,
        'total_correct' => $totalCorrect,
        'theory_correct' => $theoryCorrect,
        'practice_correct' => $practiceCorrect,
        'unanswered_count' => $unanswered,
        'completion_time_seconds' => $elapsed,
        'exam_date' => date('Y-m-d'),
        'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ]);
    return [
        'score' => $totalCorrect * 5,
        'totalCorrect' => $totalCorrect,
        'theoryCorrect' => $theoryCorrect,
        'practiceCorrect' => $practiceCorrect,
        'totalQuestions' => count($questions),
        'unansweredCount' => $unanswered,
        'completionTimeSeconds' => $elapsed,
        'details' => $details
    ];
}

function processExpiredQuestions(PDO $pdo, array &$attempt): ?array
{
    $questions = examQuestions();
    $now = time();
    while ($attempt['status'] === 'active'
        && (int)$attempt['current_question'] <= count($questions)
        && (int)$attempt['deadline_epoch'] <= $now) {
        $question = $questions[(int)$attempt['current_question'] - 1];
        insertAnswer($pdo, $attempt['attempt_id'], $question, '', true);
        $nextQuestion = (int)$attempt['current_question'] + 1;
        $nextDeadline = (int)$attempt['deadline_epoch'] + 30;
        if ($nextQuestion > count($questions)) {
            return completeAttempt($pdo, $attempt);
        }
        $attempt['current_question'] = $nextQuestion;
        $attempt['deadline_epoch'] = $nextDeadline;
        $update = $pdo->prepare(
            'UPDATE exam_attempts SET current_question = :question, deadline_epoch = :deadline
             WHERE attempt_id = :attempt_id'
        );
        $update->execute([
            'question' => $nextQuestion,
            'deadline' => $nextDeadline,
            'attempt_id' => $attempt['attempt_id']
        ]);
    }
    return null;
}

function attemptResponse(array $attempt): array
{
    $questions = examQuestions();
    $index = (int)$attempt['current_question'];
    return [
        'attemptId' => $attempt['attempt_id'],
        'currentQuestion' => $index,
        'questionCount' => count($questions),
        'deadlineTimestamp' => (int)$attempt['deadline_epoch'] * 1000,
        'serverNowTimestamp' => time() * 1000,
        'startedTimestamp' => (int)$attempt['started_epoch'] * 1000,
        'student' => [
            'name' => $attempt['name'],
            'nim' => $attempt['student_nim'],
            'studentClass' => $attempt['student_class']
        ],
        'question' => $index <= count($questions) ? publicQuestion($questions[$index - 1]) : null
    ];
}

function savedResult(PDO $pdo, string $attemptId): array
{
    $statement = $pdo->prepare(
        'SELECT r.score, r.total_correct AS totalCorrect,
                r.theory_correct AS theoryCorrect, r.practice_correct AS practiceCorrect,
                r.total_questions AS totalQuestions, r.unanswered_count AS unansweredCount,
                r.completion_time_seconds AS completionTimeSeconds, r.details
         FROM exam_results r WHERE r.attempt_id = :attempt_id'
    );
    $statement->execute(['attempt_id' => $attemptId]);
    $result = $statement->fetch();
    if (!$result) respond(500, ['success' => false, 'message' => 'Hasil ujian selesai tidak ditemukan.']);
    $result['details'] = json_decode((string)$result['details'], true) ?: [];
    foreach (['score', 'totalCorrect', 'theoryCorrect', 'practiceCorrect', 'totalQuestions', 'unansweredCount', 'completionTimeSeconds'] as $field) {
        $result[$field] = (int)$result[$field];
    }
    return $result;
}

try {
    $dbHost = getenv('UJIAN_DB_HOST') ?: '127.0.0.1';
    $dbPort = getenv('UJIAN_DB_PORT') ?: '3306';
    $dbName = getenv('UJIAN_DB_NAME') ?: 'ujianpemweb2';
    $dbUser = getenv('UJIAN_DB_USER') ?: 'root';
    $dbPassword = getenv('UJIAN_DB_PASSWORD') ?: '';
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS students (
            nim VARCHAR(30) NOT NULL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS exam_results (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            attempt_id VARCHAR(80) NOT NULL UNIQUE,
            student_nim VARCHAR(30) NOT NULL,
            score TINYINT UNSIGNED NOT NULL,
            total_correct TINYINT UNSIGNED NOT NULL,
            theory_correct TINYINT UNSIGNED NOT NULL,
            practice_correct TINYINT UNSIGNED NOT NULL,
            total_questions TINYINT UNSIGNED NOT NULL,
            unanswered_count TINYINT UNSIGNED NOT NULL,
            completion_time_seconds INT UNSIGNED NOT NULL,
            exam_date DATE NOT NULL,
            details JSON NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_results_ranking (score, completion_time_seconds),
            INDEX idx_results_exam_date (exam_date),
            CONSTRAINT fk_results_student FOREIGN KEY (student_nim)
                REFERENCES students (nim) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS exam_attempts (
            attempt_id CHAR(48) NOT NULL PRIMARY KEY,
            student_nim VARCHAR(30) NOT NULL,
            current_question TINYINT UNSIGNED NOT NULL DEFAULT 1,
            deadline_epoch BIGINT UNSIGNED NOT NULL,
            started_epoch BIGINT UNSIGNED NOT NULL,
            completed_epoch BIGINT UNSIGNED NULL,
            status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_attempts_status (status),
            CONSTRAINT fk_attempts_student FOREIGN KEY (student_nim)
                REFERENCES students (nim) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS exam_answers (
            attempt_id CHAR(48) NOT NULL,
            question_id TINYINT UNSIGNED NOT NULL,
            student_answer TEXT NOT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            is_timeout TINYINT(1) NOT NULL DEFAULT 0,
            answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (attempt_id, question_id),
            CONSTRAINT fk_answers_attempt FOREIGN KEY (attempt_id)
                REFERENCES exam_attempts (attempt_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $action = (string)($_GET['action'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($action === 'start' && $method === 'POST') {
        $student = validateStudent(requestData());
        $pdo->beginTransaction();
        $saveStudent = $pdo->prepare(
            "INSERT INTO students (nim, name, student_class)
             VALUES (:nim, :name, :student_class)
             ON DUPLICATE KEY UPDATE name = VALUES(name), student_class = VALUES(student_class)"
        );
        $saveStudent->execute([
            'nim' => $student['nim'],
            'name' => $student['name'],
            'student_class' => $student['studentClass']
        ]);
        $attemptId = bin2hex(random_bytes(24));
        $now = time();
        $createAttempt = $pdo->prepare(
            "INSERT INTO exam_attempts
                (attempt_id, student_nim, current_question, deadline_epoch, started_epoch, status)
             VALUES (:attempt_id, :student_nim, 1, :deadline_epoch, :started_epoch, 'active')"
        );
        $createAttempt->execute([
            'attempt_id' => $attemptId,
            'student_nim' => $student['nim'],
            'deadline_epoch' => $now + 30,
            'started_epoch' => $now
        ]);
        $pdo->commit();
        $attempt = getAttempt($pdo, $attemptId);
        respond(200, ['success' => true, 'data' => attemptResponse($attempt)]);
    }

    if ($action === 'resume' && $method === 'POST') {
        $data = requestData();
        $pdo->beginTransaction();
        $attempt = getAttempt($pdo, (string)($data['attemptId'] ?? ''), true);
        if ($attempt['status'] === 'cancelled') {
            $pdo->commit();
            respond(410, ['success' => false, 'message' => 'Sesi ujian ini telah dibatalkan.']);
        }
        $result = $attempt['status'] === 'active' ? processExpiredQuestions($pdo, $attempt) : null;
        if ($result !== null) {
            $attempt['current_question'] = 21;
            $attempt['status'] = 'completed';
        }
        $pdo->commit();
        if ($attempt['status'] === 'completed') {
            respond(200, [
                'success' => true,
                'completed' => true,
                'student' => [
                    'name' => $attempt['name'],
                    'nim' => $attempt['student_nim'],
                    'studentClass' => $attempt['student_class']
                ],
                'result' => savedResult($pdo, $attempt['attempt_id'])
            ]);
        }
        respond(200, ['success' => true, 'completed' => false, 'data' => attemptResponse($attempt)]);
    }

    if ($action === 'current' && $method === 'POST') {
        $data = requestData();
        $pdo->beginTransaction();
        $attempt = getAttempt($pdo, (string)($data['attemptId'] ?? ''), true);
        if ($attempt['status'] !== 'active') {
            $pdo->commit();
            respond(409, ['success' => false, 'message' => 'Sesi ujian sudah tidak aktif.']);
        }
        $result = processExpiredQuestions($pdo, $attempt);
        $pdo->commit();
        if ($result !== null) {
            respond(200, ['success' => true, 'completed' => true, 'result' => $result]);
        }
        respond(200, ['success' => true, 'completed' => false, 'data' => attemptResponse($attempt)]);
    }

    if ($action === 'answer' && $method === 'POST') {
        $data = requestData();
        $attemptId = (string)($data['attemptId'] ?? '');
        $questionNumber = filter_var($data['questionNumber'] ?? null, FILTER_VALIDATE_INT);
        $answer = trim((string)($data['answer'] ?? ''));
        if ($questionNumber === false || $questionNumber < 1 || $questionNumber > 20 || mb_strlen($answer) > 2000) {
            respond(422, ['success' => false, 'message' => 'Nomor soal atau jawaban tidak valid.']);
        }
        $pdo->beginTransaction();
        $attempt = getAttempt($pdo, $attemptId, true);
        if ($attempt['status'] !== 'active') {
            $pdo->commit();
            respond(409, ['success' => false, 'message' => 'Sesi ujian sudah tidak aktif.']);
        }
        $timedOutResult = processExpiredQuestions($pdo, $attempt);
        if ($timedOutResult !== null) {
            $pdo->commit();
            respond(200, ['success' => true, 'completed' => true, 'result' => $timedOutResult, 'timedOut' => true]);
        }
        if ((int)$attempt['current_question'] !== $questionNumber) {
            if ((int)$attempt['current_question'] > $questionNumber && $attempt['status'] === 'active') {
                $pdo->commit();
                $attempt = getAttempt($pdo, $attemptId);
                respond(200, ['success' => true, 'completed' => false, 'data' => attemptResponse($attempt), 'timedOut' => true]);
            }
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'Urutan soal tidak sesuai dengan sesi ujian.']);
        }
        $questions = examQuestions();
        $question = $questions[$questionNumber - 1];
        insertAnswer($pdo, $attemptId, $question, $answer, false);
        $nextQuestion = $questionNumber + 1;
        if ($nextQuestion > count($questions)) {
            $attempt['current_question'] = $nextQuestion;
            $result = completeAttempt($pdo, $attempt);
            $pdo->commit();
            respond(200, ['success' => true, 'completed' => true, 'result' => $result]);
        }
        $now = time();
        $attempt['current_question'] = $nextQuestion;
        $attempt['deadline_epoch'] = $now + 30;
        $update = $pdo->prepare(
            'UPDATE exam_attempts SET current_question = :question, deadline_epoch = :deadline
             WHERE attempt_id = :attempt_id'
        );
        $update->execute([
            'question' => $nextQuestion,
            'deadline' => $now + 30,
            'attempt_id' => $attemptId
        ]);
        $pdo->commit();
        $attempt = getAttempt($pdo, $attemptId);
        respond(200, ['success' => true, 'completed' => false, 'data' => attemptResponse($attempt)]);
    }

    if ($action === 'cancel' && $method === 'POST') {
        $data = requestData();
        $pdo->beginTransaction();
        $attempt = getAttempt($pdo, (string)($data['attemptId'] ?? ''), true);
        if ($attempt['status'] === 'active') {
            $deleteAnswers = $pdo->prepare('DELETE FROM exam_answers WHERE attempt_id = :attempt_id');
            $deleteAnswers->execute(['attempt_id' => $attempt['attempt_id']]);
            $statement = $pdo->prepare(
                "UPDATE exam_attempts SET status = 'cancelled'
                 WHERE attempt_id = :attempt_id AND status = 'active'"
            );
            $statement->execute(['attempt_id' => $attempt['attempt_id']]);
        }
        $pdo->commit();
        respond(200, ['success' => true]);
    }

    if ($action === 'leaderboard' && $method === 'GET') {
        $rows = $pdo->query(
            "SELECT r.id, s.name, s.nim, s.student_class AS studentClass,
                    r.score, r.total_correct AS totalCorrect,
                    r.theory_correct AS theoryCorrect,
                    r.practice_correct AS practiceCorrect,
                    r.total_questions AS totalQuestions,
                    r.unanswered_count AS unansweredCount,
                    r.exam_date AS date,
                    r.completion_time_seconds AS completionTimeSeconds
             FROM exam_results r
             INNER JOIN students s ON s.nim = r.student_nim
             ORDER BY r.score DESC, r.completion_time_seconds ASC, r.created_at ASC"
        )->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (string)$row['id'];
            foreach (['score', 'totalCorrect', 'theoryCorrect', 'practiceCorrect', 'totalQuestions', 'unansweredCount', 'completionTimeSeconds'] as $field) {
                $row[$field] = (int)$row[$field];
            }
        }
        unset($row);
        respond(200, ['success' => true, 'data' => $rows]);
    }

    if ($action === 'participant' && $method === 'POST') {
        $student = validateStudent(requestData());
        $statement = $pdo->prepare(
            "INSERT INTO students (nim, name, student_class)
             VALUES (:nim, :name, :student_class)
             ON DUPLICATE KEY UPDATE name = VALUES(name), student_class = VALUES(student_class)"
        );
        $statement->execute([
            'nim' => $student['nim'],
            'name' => $student['name'],
            'student_class' => $student['studentClass']
        ]);
        respond(200, ['success' => true]);
    }

    if ($action === 'result' && $method === 'POST') {
        respond(403, ['success' => false, 'message' => 'Hasil ujian hanya dapat disimpan melalui sesi ujian aktif.']);
    }

    if ($action === 'clear_leaderboard' && $method === 'POST') {
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM exam_results');
        $pdo->exec("DELETE FROM exam_attempts WHERE status IN ('completed', 'cancelled')");
        $pdo->commit();
        respond(200, ['success' => true]);
    }

    respond(404, ['success' => false, 'message' => 'Endpoint tidak ditemukan.']);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ujian Pemweb II API: ' . $error->getMessage());
    respond(500, [
        'success' => false,
        'message' => 'Server tidak dapat memproses permintaan. Periksa koneksi MySQL dan konfigurasi database.'
    ]);
}
