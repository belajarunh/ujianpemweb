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

    $action = (string)($_GET['action'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($action === 'leaderboard' && $method === 'GET') {
        $rows = $pdo->query(
            "SELECT r.id, s.name, s.nim, s.student_class AS studentClass,
                    r.score, r.total_correct AS totalCorrect,
                    r.theory_correct AS theoryCorrect,
                    r.practice_correct AS practiceCorrect,
                    r.total_questions AS totalQuestions,
                    r.unanswered_count AS unansweredCount,
                    r.exam_date AS date,
                    r.completion_time_seconds AS completionTimeSeconds,
                    r.details
             FROM exam_results r
             INNER JOIN students s ON s.nim = r.student_nim
             ORDER BY r.score DESC, r.completion_time_seconds ASC, r.created_at ASC"
        )->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (string)$row['id'];
            $row['details'] = json_decode((string)$row['details'], true) ?: [];
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
        $data = requestData();
        $student = validateStudent(is_array($data['student'] ?? null) ? $data['student'] : []);
        $attemptId = trim((string)($data['attemptId'] ?? ''));
        $answers = $data['details'] ?? null;

        if ($attemptId === '' || mb_strlen($attemptId) > 80 || !is_array($answers) || count($answers) !== 20) {
            respond(422, ['success' => false, 'message' => 'Data hasil ujian tidak lengkap atau tidak valid.']);
        }

        $details = [];
        $theoryCorrect = 0;
        $practiceCorrect = 0;
        $unansweredCount = 0;
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                respond(422, ['success' => false, 'message' => 'Rincian jawaban tidak valid.']);
            }
            $type = (string)($answer['type'] ?? '');
            $studentAnswer = trim((string)($answer['studentAnswer'] ?? ''));
            if (!in_array($type, ['theory', 'practice'], true) || mb_strlen($studentAnswer) > 2000) {
                respond(422, ['success' => false, 'message' => 'Isi jawaban tidak valid.']);
            }

            $isCorrect = ($answer['isCorrect'] ?? false) === true;
            if ($isCorrect && $type === 'theory') $theoryCorrect++;
            if ($isCorrect && $type === 'practice') $practiceCorrect++;
            if ($studentAnswer === '') $unansweredCount++;

            $details[] = [
                'questionId' => (int)($answer['questionId'] ?? 0),
                'questionTitle' => mb_substr((string)($answer['questionTitle'] ?? ''), 0, 200),
                'type' => $type,
                'studentAnswer' => $studentAnswer,
                'correctKeyOrRef' => mb_substr((string)($answer['correctKeyOrRef'] ?? ''), 0, 500),
                'isCorrect' => $isCorrect,
                'points' => $isCorrect ? 5 : 0,
                'isTimeout' => ($answer['isTimeout'] ?? false) === true
            ];
        }

        $totalCorrect = $theoryCorrect + $practiceCorrect;
        $completionTimeSeconds = filter_var($data['completionTimeSeconds'] ?? null, FILTER_VALIDATE_INT);
        if ($completionTimeSeconds === false || $completionTimeSeconds < 0) {
            respond(422, ['success' => false, 'message' => 'Durasi ujian tidak valid.']);
        }

        $pdo->beginTransaction();
        $studentStatement = $pdo->prepare(
            "INSERT INTO students (nim, name, student_class)
             VALUES (:nim, :name, :student_class)
             ON DUPLICATE KEY UPDATE name = VALUES(name), student_class = VALUES(student_class)"
        );
        $studentStatement->execute([
            'nim' => $student['nim'],
            'name' => $student['name'],
            'student_class' => $student['studentClass']
        ]);

        $resultStatement = $pdo->prepare(
            "INSERT INTO exam_results
                (attempt_id, student_nim, score, total_correct, theory_correct,
                 practice_correct, total_questions, unanswered_count,
                 completion_time_seconds, exam_date, details)
             VALUES
                (:attempt_id, :student_nim, :score, :total_correct, :theory_correct,
                 :practice_correct, 20, :unanswered_count, :completion_time_seconds,
                 :exam_date, :details)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
        );
        $resultStatement->execute([
            'attempt_id' => $attemptId,
            'student_nim' => $student['nim'],
            'score' => $totalCorrect * 5,
            'total_correct' => $totalCorrect,
            'theory_correct' => $theoryCorrect,
            'practice_correct' => $practiceCorrect,
            'unanswered_count' => $unansweredCount,
            'completion_time_seconds' => $completionTimeSeconds,
            'exam_date' => date('Y-m-d'),
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);
        $resultId = $pdo->lastInsertId();
        $pdo->commit();
        respond(200, ['success' => true, 'id' => $resultId]);
    }

    if ($action === 'clear_leaderboard' && $method === 'POST') {
        $pdo->exec('DELETE FROM exam_results');
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
