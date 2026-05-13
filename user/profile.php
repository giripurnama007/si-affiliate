<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Silakan coba lagi.';
    } else {
        // Sanitize inputs
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $bank_name = sanitize($_POST['bank_name']);
        $account_number = sanitize($_POST['account_number']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        // Basic validation
        if (empty($name) || empty($email) || empty($phone) || empty($bank_name) || empty($account_number)) {
            $error = 'Nama, Email, No. HP, Nama Bank, dan Nomor Rekening tidak boleh kosong.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Check if email is taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email ini sudah digunakan oleh akun lain.';
            } else {
                // Prepare the update query
                $query_parts = [
                    "name = ?",
                    "email = ?",
                    "phone = ?",
                    "bank_name = ?",
                    "account_number = ?"
                ];
                $params = [$name, $email, $phone, $bank_name, $account_number];

                // Handle password update
                if (!empty($password)) {
                    if ($password !== $password_confirm) {
                        $error = 'Konfirmasi password tidak cocok.';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $query_parts[] = "password = ?";
                        $params[] = $hashed_password;
                    }
                }

                if (empty($error)) {
                    $params[] = $user_id;
                    $sql = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
                    
                    try {
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $message = 'Profil berhasil diperbarui!';

                        // Update session data
                        $_SESSION['user_name'] = $name;

                    } catch (PDOException $e) {
                        $error = 'Gagal memperbarui profil: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch current user data for form pre-filling
$stmt = $pdo->prepare("SELECT name, email, phone, bank_name, account_number FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // This should not happen if user is logged in, but as a safeguard
    header("Location: ../logout.php");
    exit();
}

$csrf_token = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .focus-ring:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Edit Profil</h1>
                <div>
                    <a href="dashboard.php" class="text-sm font-medium text-blue-600 hover:text-blue-500 mr-4">Kembali ke Dashboard</a>
                    <a href="../logout.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">Logout</a>
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-xl p-8">
                <?php if ($message): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <p><?= htmlspecialchars($message) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>
                        <div>
                            <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>
                        <div>
                            <label for="phone" class="block text-gray-700 text-sm font-medium mb-2">No. HP / WA</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>
                        <div>
                            <label for="bank_name" class="block text-gray-700 text-sm font-medium mb-2">Nama Bank</label>
                            <input type="text" id="bank_name" name="bank_name" value="<?= htmlspecialchars($user['bank_name'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>
                        <div>
                            <label for="account_number" class="block text-gray-700 text-sm font-medium mb-2">Nomor Rekening</label>
                            <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars($user['account_number'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>

                        <hr class="my-6">

                        <p class="text-gray-600 text-sm">Isi bagian di bawah ini hanya jika Anda ingin mengubah password.</p>
                        
                        <div>
                            <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password Baru</label>
                            <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>
                        <div>
                            <label for="password_confirm" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password Baru</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring">
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>