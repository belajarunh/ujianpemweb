<?php
declare(strict_types=1);

function examQuestions(): array
{
    return [
        ['id' => 1, 'type' => 'theory', 'category' => 'Teori VCS', 'title' => 'Soal 1 — Version Control System',
            'question' => 'Apa yang dimaksud dengan Version Control System (VCS)?',
            'options' => [
                ['key' => 'A', 'text' => 'Sistem untuk mempercepat koneksi internet.'],
                ['key' => 'B', 'text' => 'Sistem untuk mencatat, mengelola, dan melacak perubahan berkas dari waktu ke waktu.'],
                ['key' => 'C', 'text' => 'Aplikasi untuk mendesain antarmuka web.'],
                ['key' => 'D', 'text' => 'Sistem untuk menghapus semua versi berkas lama.']
            ], 'answer' => 'B', 'reference' => 'B'],
        ['id' => 2, 'type' => 'theory', 'category' => 'Teori Git & GitHub', 'title' => 'Soal 2 — Git dan GitHub',
            'question' => 'Apa perbedaan utama antara Git dan GitHub?',
            'options' => [
                ['key' => 'A', 'text' => 'Git adalah bahasa pemrograman, sedangkan GitHub adalah compiler.'],
                ['key' => 'B', 'text' => 'Git adalah sistem version control, sedangkan GitHub adalah platform untuk meng-host repository Git dan berkolaborasi.'],
                ['key' => 'C', 'text' => 'Git dan GitHub adalah dua nama untuk aplikasi yang sama.'],
                ['key' => 'D', 'text' => 'Git hanya digunakan untuk frontend, sedangkan GitHub hanya untuk backend.']
            ], 'answer' => 'B', 'reference' => 'B'],
        ['id' => 3, 'type' => 'theory', 'category' => 'Teori Perintah Git', 'title' => 'Soal 3 — git init',
            'question' => 'Apa fungsi perintah git init?',
            'options' => [
                ['key' => 'A', 'text' => 'Mengirim kode ke GitHub.'],
                ['key' => 'B', 'text' => 'Menghapus seluruh riwayat repository.'],
                ['key' => 'C', 'text' => 'Menginisialisasi repository Git pada direktori yang digunakan.'],
                ['key' => 'D', 'text' => 'Mengunduh repository dari GitHub.']
            ], 'answer' => 'C', 'reference' => 'C'],
        ['id' => 4, 'type' => 'theory', 'category' => 'Teori Staging Area', 'title' => 'Soal 4 — git add',
            'question' => 'Apa fungsi perintah git add .?',
            'options' => [
                ['key' => 'A', 'text' => 'Menambahkan semua berkas dan perubahan yang relevan di direktori saat ini dan subdirektorinya ke staging area, sesuai aturan Git.'],
                ['key' => 'B', 'text' => 'Mengirim seluruh kode ke GitHub.'],
                ['key' => 'C', 'text' => 'Menghapus semua berkas yang tidak dipakai.'],
                ['key' => 'D', 'text' => 'Membuat branch baru bernama add.']
            ], 'answer' => 'A', 'reference' => 'A'],
        ['id' => 5, 'type' => 'theory', 'category' => 'Teori Snapshot', 'title' => 'Soal 5 — Commit',
            'question' => 'Apa fungsi git commit -m "Pesan perubahan"?',
            'options' => [
                ['key' => 'A', 'text' => 'Membuat repository GitHub baru.'],
                ['key' => 'B', 'text' => 'Menyimpan snapshot perubahan yang sudah berada di staging area ke riwayat repository lokal, dengan pesan commit.'],
                ['key' => 'C', 'text' => 'Mengunduh seluruh branch dari remote.'],
                ['key' => 'D', 'text' => 'Menggabungkan dua branch secara otomatis.']
            ], 'answer' => 'B', 'reference' => 'B'],
        ['id' => 6, 'type' => 'theory', 'category' => 'Teori Branching', 'title' => 'Soal 6 — Branch',
            'question' => 'Mengapa branch digunakan dalam pengembangan software?',
            'options' => [
                ['key' => 'A', 'text' => 'Untuk menggantikan fungsi compiler.'],
                ['key' => 'B', 'text' => 'Untuk menghapus kebutuhan commit.'],
                ['key' => 'C', 'text' => 'Untuk memisahkan pekerjaan atau fitur sehingga dapat dikembangkan tanpa langsung mengubah branch utama.'],
                ['key' => 'D', 'text' => 'Untuk mengubah repository privat menjadi publik.']
            ], 'answer' => 'C', 'reference' => 'C'],
        ['id' => 7, 'type' => 'theory', 'category' => 'Teori Sinkronisasi Remote', 'title' => 'Soal 7 — Push dan Pull',
            'question' => 'Apa perbedaan git push dan git pull?',
            'options' => [
                ['key' => 'A', 'text' => 'Push mengambil perubahan, sedangkan pull menghapus perubahan.'],
                ['key' => 'B', 'text' => 'Push mengirim commit lokal ke remote, sedangkan pull mengambil perubahan dari remote dan mengintegrasikannya ke branch lokal.'],
                ['key' => 'C', 'text' => 'Push membuat folder, sedangkan pull membuat berkas.'],
                ['key' => 'D', 'text' => 'Keduanya selalu mempunyai fungsi yang sama.']
            ], 'answer' => 'B', 'reference' => 'B'],
        ['id' => 8, 'type' => 'theory', 'category' => 'Teori Clone Repository', 'title' => 'Soal 8 — Clone',
            'question' => 'Apa fungsi git clone?',
            'options' => [
                ['key' => 'A', 'text' => 'Membuat commit kosong.'],
                ['key' => 'B', 'text' => 'Menghapus repository remote.'],
                ['key' => 'C', 'text' => 'Mengubah nama branch.'],
                ['key' => 'D', 'text' => 'Menyalin repository remote beserta riwayatnya ke lingkungan lokal.']
            ], 'answer' => 'D', 'reference' => 'D'],
        ['id' => 9, 'type' => 'theory', 'category' => 'Teori Kolaborasi Git', 'title' => 'Soal 9 — Merge Conflict',
            'question' => 'Apa yang dimaksud dengan merge conflict?',
            'options' => [
                ['key' => 'A', 'text' => 'Kondisi ketika Git tidak dapat menggabungkan perubahan tertentu secara otomatis, misalnya karena perubahan yang bertentangan pada bagian berkas yang sama.'],
                ['key' => 'B', 'text' => 'Kondisi ketika koneksi internet terlalu cepat.'],
                ['key' => 'C', 'text' => 'Kondisi ketika semua commit terhapus.'],
                ['key' => 'D', 'text' => 'Kondisi ketika GitHub tidak memiliki repository.']
            ], 'answer' => 'A', 'reference' => 'A'],
        ['id' => 10, 'type' => 'theory', 'category' => 'Teori Sintaks & Opsi Git', 'title' => 'Soal 10 — Opsi Perintah Git',
            'question' => 'Manakah pernyataan yang benar tentang opsi -m, -u, -c, dan -M pada perintah Git?',
            'options' => [
                ['key' => 'A', 'text' => 'Semua opsi tersebut memiliki fungsi yang sama.'],
                ['key' => 'B', 'text' => '-m selalu berarti membuat branch, sedangkan -M selalu berarti commit.'],
                ['key' => 'C', 'text' => '-m dapat digunakan untuk pesan commit; -u untuk menetapkan upstream pada konteks tertentu seperti push; -c dapat membuat branch baru melalui git switch -c; dan -M dapat memaksa penggantian nama branch melalui git branch -M.'],
                ['key' => 'D', 'text' => '-m dan -M selalu dapat saling menggantikan.']
            ], 'answer' => 'C', 'reference' => 'C'],
        ['id' => 11, 'type' => 'practice', 'category' => 'Praktik Terminal Git', 'title' => 'Soal 11 — Inisialisasi Repository',
            'question' => 'Tuliskan perintah untuk menginisialisasi repository Git pada direktori aktif.',
            'guidance' => 'Gunakan perintah dasar inisialisasi git.', 'answer' => 'git init', 'reference' => 'git init'],
        ['id' => 12, 'type' => 'practice', 'category' => 'Praktik Terminal Git', 'title' => 'Soal 12 — Memeriksa Status Repository',
            'question' => 'Tuliskan perintah untuk melihat status berkas pada repository Git lokal.',
            'guidance' => 'Periksa status staging dan working tree.', 'answer' => 'git status', 'reference' => 'git status'],
        ['id' => 13, 'type' => 'practice', 'category' => 'Praktik Terminal Git', 'title' => 'Soal 13 — Menambahkan Perubahan ke Staging Area',
            'question' => 'Tuliskan perintah untuk menambahkan semua perubahan yang relevan di direktori saat ini dan subdirektorinya ke staging area.',
            'guidance' => 'Gunakan tanda titik sebagai argumen direktori aktif.', 'answer' => 'git add .', 'reference' => 'git add .'],
        ['id' => 14, 'type' => 'practice', 'category' => 'Praktik Terminal Git', 'title' => 'Soal 14 — Membuat Commit',
            'question' => 'Tuliskan perintah untuk membuat commit dengan pesan Menambahkan halaman utama.',
            'guidance' => 'Gunakan opsi -m (huruf kecil) dan kutip pesan secara tepat. Jangan gunakan -M.',
            'answer' => 'git commit -m "Menambahkan halaman utama"', 'reference' => 'git commit -m "Menambahkan halaman utama"'],
        ['id' => 15, 'type' => 'practice', 'category' => 'Praktik Terminal Git', 'title' => 'Soal 15 — Memeriksa Branch dan Membuat Branch Baru',
            'question' => "Tuliskan dua perintah secara berurutan:\n1. Melihat daftar branch lokal.\n2. Membuat sekaligus berpindah ke branch baru bernama fitur-login.",
            'guidance' => 'Tuliskan perintah pada 2 baris atau dipisahkan enter. Perintah pertama melihat branch, perintah kedua switch -c.',
            'answer' => "git branch\ngit switch -c fitur-login", 'reference' => "git branch\ngit switch -c fitur-login"],
        ['id' => 16, 'type' => 'practice', 'category' => 'Praktik Git Remote', 'title' => 'Soal 16 — Menambahkan Remote Repository',
            'question' => 'Tuliskan perintah untuk menambahkan remote bernama origin dengan URL https://github.com/mahasiswa/pemrograman-web-ii.git.',
            'guidance' => 'Format: git remote add <name> <url>',
            'answer' => 'git remote add origin https://github.com/mahasiswa/pemrograman-web-ii.git',
            'reference' => 'git remote add origin https://github.com/mahasiswa/pemrograman-web-ii.git'],
        ['id' => 17, 'type' => 'practice', 'category' => 'Praktik Git Push', 'title' => 'Soal 17 — Push dan Upstream',
            'question' => 'Tuliskan perintah untuk mengirim branch main ke remote origin sekaligus menetapkan upstream.',
            'guidance' => 'Gunakan opsi -u (upstream), bukan -m. Target: origin main.',
            'answer' => 'git push -u origin main', 'reference' => 'git push -u origin main'],
        ['id' => 18, 'type' => 'practice', 'category' => 'Praktik Git Pull', 'title' => 'Soal 18 — Pull dari Remote',
            'question' => 'Tuliskan perintah untuk mengambil dan mengintegrasikan perubahan branch main dari remote origin.',
            'guidance' => 'Sintaks: git pull <remote> <branch>',
            'answer' => 'git pull origin main', 'reference' => 'git pull origin main'],
        ['id' => 19, 'type' => 'practice', 'category' => 'Praktik Git Merge', 'title' => 'Soal 19 — Merge Branch',
            'question' => 'Jika sedang berada di branch main, tuliskan perintah untuk menggabungkan perubahan dari branch fitur-login ke branch yang sedang aktif.',
            'guidance' => 'Gabungkan branch sumber fitur-login ke branch aktif saat ini.',
            'answer' => 'git merge fitur-login', 'reference' => 'git merge fitur-login'],
        ['id' => 20, 'type' => 'practice', 'category' => 'Praktik GitHub CLI', 'title' => 'Soal 20 — Membuat Pull Request',
            'question' => 'Tuliskan perintah GitHub CLI untuk membuat Pull Request dengan judul Implementasi fitur login dan base branch main, dari branch yang sedang aktif.',
            'guidance' => 'Gunakan perintah gh pr create, dengan flag --base main dan --title "Implementasi fitur login".',
            'answer' => 'gh pr create --base main --title "Implementasi fitur login"',
            'reference' => 'gh pr create --base main --title "Implementasi fitur login"']
    ];
}

function publicQuestion(array $question): array
{
    unset($question['answer'], $question['reference']);
    return $question;
}

function validateExamAnswer(array $question, string $answer): bool
{
    if ($answer === '') return false;
    if ($question['type'] === 'theory') {
        return strtoupper(trim($answer)) === $question['answer'];
    }

    $trimmed = trim($answer);
    $tokens = preg_split('/\s+/', $trimmed);
    switch ($question['id']) {
        case 11:
            return $tokens === ['git', 'init'];
        case 12:
            return $tokens === ['git', 'status'];
        case 13:
            return $tokens === ['git', 'add', '.'];
        case 14:
            return preg_match('/^git\s+commit\s+-m\s+["\']Menambahkan halaman utama["\']$/', $trimmed) === 1;
        case 15:
            $lines = array_values(array_filter(array_map('trim', preg_split('/[\r\n;]+/', $trimmed)), static fn($line) => $line !== ''));
            if (count($lines) !== 2 || preg_split('/\s+/', $lines[0]) !== ['git', 'branch']) return false;
            $command = preg_split('/\s+/', $lines[1]);
            return $command === ['git', 'switch', '-c', 'fitur-login']
                || $command === ['git', 'checkout', '-b', 'fitur-login'];
        case 16:
            return $tokens === ['git', 'remote', 'add', 'origin', 'https://github.com/mahasiswa/pemrograman-web-ii.git'];
        case 17:
            return $tokens === ['git', 'push', '-u', 'origin', 'main']
                || $tokens === ['git', 'push', '--set-upstream', 'origin', 'main']
                || $tokens === ['git', 'push', 'origin', 'main', '-u']
                || $tokens === ['git', 'push', 'origin', 'main', '--set-upstream'];
        case 18:
            return $tokens === ['git', 'pull', 'origin', 'main'];
        case 19:
            return $tokens === ['git', 'merge', 'fitur-login'];
        case 20:
            return preg_match('/^gh\s+pr\s+create\s+(--base|-B)\s+main\s+(--title|-t)\s+["\']Implementasi fitur login["\']$/', $trimmed) === 1
                || preg_match('/^gh\s+pr\s+create\s+(--title|-t)\s+["\']Implementasi fitur login["\']\s+(--base|-B)\s+main$/', $trimmed) === 1;
        default:
            return false;
    }
}
