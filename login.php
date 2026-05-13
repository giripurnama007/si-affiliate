<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
$message = '';

if (isset($_GET['status']) && $_GET['status'] == 'registered') {
    $message = 'Registrasi berhasil! Silakan login setelah akun Anda disetujui oleh admin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Akun Anda belum aktif. Mohon tunggu persetujuan dari admin.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit();
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$csrf_token = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Akun</title>
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
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Login</h1>
            
            <?php if ($message): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded" role="alert"><p><?= $message ?></p></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert"><p><?= $error ?></p></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus-ring">
                </div>
                <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 rounded-lg hover:bg-blue-800 transition">Login</button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Belum punya akun? <a href="register.php" class="font-medium text-blue-700 hover:text-blue-600">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
