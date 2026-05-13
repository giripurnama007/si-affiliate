<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = 'Jawaban CAPTCHA salah. Silakan coba lagi.';
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $phone = sanitize($_POST['phone']);
        $bank_name = sanitize($_POST['bank_name']);
        $account_number = sanitize($_POST['account_number']);
        
        $student_status = sanitize($_POST['student_status'] ?? 'non_mahasiswa');
        $nim = ($student_status === 'mahasiswa') ? sanitize($_POST['nim'] ?? '') : null;
        $prodi = ($student_status === 'mahasiswa') ? sanitize($_POST['prodi'] ?? '') : null;

        // Validasi sederhana
        if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($bank_name) || empty($account_number)) {
            $error = 'Semua field wajib diisi.';
        } elseif ($student_status === 'mahasiswa' && (empty($nim) || empty($prodi))) {
            $error = 'NIM dan Asal Prodi wajib diisi jika Anda berstatus Mahasiswa.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Cek apakah email sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $unique_code = uniqid('ref_');
 
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, unique_code, bank_name, account_number, student_status, nim, prodi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $hashed_password, $phone, $unique_code, $bank_name, $account_number, $student_status, $nim, $prodi]);
                    
                    // Redirect ke login dengan pesan sukses
                    header("Location: login.php?status=registered");
                    exit();
                } catch (PDOException $e) {
                    $error = "Registrasi gagal: " . $e->getMessage();
                }
            }
        }
    }
}

$captcha_question = generateCaptcha();
$csrf_token = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Afiliasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .focus-ring:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Buat Akun Afiliasi</h1>
            
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-5 mb-6 text-sm text-gray-700">
                <p class="font-bold text-blue-800 text-base mb-2">Raih Penghasilan Tambahan Hanya dengan Berbagi Link!</p>
                <p class="mb-3">Yuk, gabung di Program Afiliasi kami dan nikmati berbagai keuntungannya:</p>
                <ul class="list-none space-y-2">
                    <li class="flex items-start"><span class="mr-2">💸</span> <span><strong>Komisi Tak Terbatas:</strong> Dapatkan penghasilan berkelanjutan hingga jutaan rupiah setiap bulannya.</span></li>
                    <li class="flex items-start"><span class="mr-2">✨</span> <span><strong>100% Gratis:</strong> Pendaftaran tanpa dipungut biaya awal (Biaya Join Gratis).</span></li>
                    <li class="flex items-start"><span class="mr-2">📱</span> <span><strong>Sangat Mudah:</strong> Cukup bagikan link unik Anda ke berbagai akun media sosial.</span></li>
                    <li class="flex items-start"><span class="mr-2">🌍</span> <span><strong>Fleksibel:</strong> Bisa dikerjakan dari mana saja dan kapan saja tanpa terikat waktu.</span></li>
                </ul>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert"><p><?= $error ?></p></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                    <input type="text" id="name" name="name" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 text-sm font-medium mb-2">No. HP / WA</label>
                    <input type="tel" id="phone" name="phone" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-4">
                    <label for="student_status" class="block text-gray-700 text-sm font-medium mb-2">Status Pendaftar</label>
                    <select id="student_status" name="student_status" required class="w-full px-4 py-2 border rounded-lg focus-ring bg-white" onchange="toggleMahasiswaFields()">
                        <option value="non_mahasiswa">Non Mahasiswa</option>
                        <option value="mahasiswa">Mahasiswa</option>
                    </select>
                </div>
                <div id="mahasiswa_fields" class="hidden mb-4 p-4 bg-blue-50 border border-blue-100 rounded-lg space-y-4">
                    <div>
                        <label for="nim" class="block text-gray-700 text-sm font-medium mb-2">NIM (Nomor Induk Mahasiswa)</label>
                        <input type="text" id="nim" name="nim" class="w-full px-4 py-2 border rounded-lg focus-ring">
                    </div>
                    <div>
                        <label for="prodi" class="block text-gray-700 text-sm font-medium mb-2">Asal Program Studi</label>
                        <select id="prodi" name="prodi" class="w-full px-4 py-2 border rounded-lg focus-ring bg-white">
                            <option value="">-- Pilih --</option>
                            <option value="Akuntansi">Akuntansi</option>
                            <option value="Manajemen">Manajemen</option>
                            <option value="Administrasi Bisnis">Administrasi Bisnis</option>
                            <option value="Desain Grafis">Desain Grafis</option>
                            <option value="Ilmu Komunikasi">Ilmu Komunikasi</option>
                            <option value="Teknik Mesin">Teknik Mesin</option>
                            <option value="Teknik Elektro">Teknik Elektro</option>
                            <option value="Teknik Industri">Teknik Industri</option>
                            <option value="Sistem Informasi">Sistem Informasi</option>
                            <option value="Teknik Informatika">Teknik Informatika</option>
                            <option value="Psikologi">Psikologi</option>
                            <option value="Ilmu Hukum">Ilmu Hukum</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="bank_name" class="block text-gray-700 text-sm font-medium mb-2">Nama Bank</label>
                    <input type="text" id="bank_name" name="bank_name" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-4">
                    <label for="account_number" class="block text-gray-700 text-sm font-medium mb-2">Nomor Rekening</label>
                    <input type="text" id="account_number" name="account_number" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-6">
                    <label for="captcha" class="block text-gray-700 text-sm font-medium mb-2">Keamanan: <?= $captcha_question ?></label>
                    <input type="number" id="captcha" name="captcha" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 rounded-lg hover:bg-blue-800 transition">Daftar</button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Sudah punya akun? <a href="login.php" class="font-medium text-blue-700 hover:text-blue-600">Login di sini</a>
            </p>
        </div>
    </div>

    <script>
        function toggleMahasiswaFields() {
            const status = document.getElementById('student_status').value;
            const fields = document.getElementById('mahasiswa_fields');
            const nimInput = document.getElementById('nim');
            const prodiInput = document.getElementById('prodi');
            
            if (status === 'mahasiswa') {
                fields.classList.remove('hidden');
                nimInput.required = true;
                prodiInput.required = true;
            } else {
                fields.classList.add('hidden');
                nimInput.required = false;
                prodiInput.required = false;
                nimInput.value = '';
                prodiInput.value = '';
            }
        }
    </script>
</body>
</html>
