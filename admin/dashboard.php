<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_commission') {
    $new_commission = (int) $_POST['commission'];
    $stmt = $pdo->prepare("INSERT INTO commission_history (commission_amount) VALUES (?)");
    $stmt->execute([$new_commission]);
    $message = "Nilai komisi berhasil diperbarui dan disimpan ke riwayat!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_share_message') {
    $share_message = sanitize($_POST['share_message']);
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'share_message_template'");
    $stmt->execute([$share_message]);
    $message = "Nilai komisi berhasil diperbarui!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_commission') {
    $referral_id = (int)$_POST['referral_id'];
    $stmt = $pdo->prepare("UPDATE referrals SET payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$referral_id]);
    $message = "Komisi untuk pendaftar berhasil dibayarkan!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $new_status = '';

    if ($action === 'approve') {
        $new_status = 'active';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    }

    if ($new_status) {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
    }
    header("Location: dashboard.php");
    exit();
}

// Helper fungsi untuk membuat URL paging tanpa merusak parameter paging tabel lainnya
function buildPageUrl($param, $value) {
    $query = $_GET;
    $query[$param] = $value;
    return '?' . http_build_query($query);
}

$limit = 20;

// 1. Pending Users (Validasi)
$page_pending = isset($_GET['page_pending']) ? max(1, (int)$_GET['page_pending']) : 1;
$offset_pending = ($page_pending - 1) * $limit;
$total_pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$total_pages_pending = ceil($total_pending / $limit);

$stmt = $pdo->prepare("SELECT id, name, email, phone, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC LIMIT $limit OFFSET $offset_pending");
$stmt->execute();
$pending_users = $stmt->fetchAll();

// 1.5 Active Users (Afiliator Tervalidasi)
$page_active = isset($_GET['page_active']) ? max(1, (int)$_GET['page_active']) : 1;
$offset_active = ($page_active - 1) * $limit;
$total_active = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND role = 'user'")->fetchColumn();
$total_pages_active = ceil($total_active / $limit);

$stmt_active = $pdo->prepare("SELECT id, name, email, phone, unique_code, bank_name, account_number, created_at FROM users WHERE status = 'active' AND role = 'user' ORDER BY created_at DESC LIMIT $limit OFFSET $offset_active");
$stmt_active->execute();
$active_users = $stmt_active->fetchAll();

// Ambil nilai komisi saat ini
$stmt_setting = $pdo->query("SELECT commission_amount FROM commission_history ORDER BY created_at DESC LIMIT 1");
$current_commission = $stmt_setting->fetchColumn() ?: 50000;

// 2. Ambil riwayat komisi
$page_history = isset($_GET['page_history']) ? max(1, (int)$_GET['page_history']) : 1;
$offset_history = ($page_history - 1) * $limit;

$stmt_history = $pdo->prepare("SELECT commission_amount, created_at FROM commission_history ORDER BY created_at DESC LIMIT $limit OFFSET $offset_history");
$stmt_history->execute();
$commission_history_list = $stmt_history->fetchAll();

// Ambil template pesan share saat ini
$stmt_share_msg = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'share_message_template'");
$current_share_message = $stmt_share_msg->fetchColumn() ?: 'Daftar di sini: {{link}}';

// 3. Ambil semua data pendaftar (referral)
$page_referrals = isset($_GET['page_referrals']) ? max(1, (int)$_GET['page_referrals']) : 1;
$offset_referrals = ($page_referrals - 1) * $limit;
$total_referrals = $pdo->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
$total_pages_referrals = ceil($total_referrals / $limit);

$stmt_all_referrals = $pdo->prepare("SELECT r.*, u.name as affiliate_name 
                                   FROM referrals r 
                                   LEFT JOIN users u ON r.affiliate_id = u.id ORDER BY r.registered_at DESC LIMIT $limit OFFSET $offset_referrals");
$stmt_all_referrals->execute();
$all_referrals = $stmt_all_referrals->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                <a href="../logout.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">Logout</a>
            </div>
        </header>
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <?php if ($message): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <p><?= htmlspecialchars($message) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Form Pengaturan Komisi -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8 p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pengaturan Komisi</h2>
                    <form action="dashboard.php" method="POST" class="flex flex-col sm:flex-row items-end gap-4">
                        <input type="hidden" name="action" value="update_commission">
                        <div class="w-full sm:w-auto">
                            <label for="commission" class="block text-sm font-medium text-gray-700 mb-1">Nilai Komisi per Referral (Rp)</label>
                            <input type="number" id="commission" name="commission" value="<?= htmlspecialchars($current_commission) ?>" required class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <button type="submit" class="w-full sm:w-auto bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition">Simpan</button>
                    </form>

                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Riwayat Nilai Komisi</h3>
                        <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Input</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nilai Komisi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($commission_history_list as $history): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i:s', strtotime($history['created_at'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= number_format($history['commission_amount'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Pengaturan Pesan Share -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8 p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pengaturan Narasi Tautan Share</h2>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="update_share_message">
                        <label for="share_message" class="block text-sm font-medium text-gray-700 mb-1">Template Pesan (gunakan {{link}} untuk placeholder tautan)</label>
                        <textarea id="share_message" name="share_message" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($current_share_message) ?></textarea>
                        <button type="submit" class="mt-4 w-full sm:w-auto bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition">Simpan Narasi</button>
                    </form>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mb-4">Validasi User Afiliasi</h2>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($pending_users)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada user yang menunggu validasi.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pending_users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= sanitize($user['name']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?= sanitize($user['email']) ?></div>
                                                    <div class="text-xs text-gray-500">WA: <?= sanitize($user['phone'] ?? '-') ?></div>
                                                </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                                <form action="dashboard.php" method="POST" class="inline-block">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="action" value="approve" class="text-white bg-green-600 hover:bg-green-700 px-3 py-1 rounded-md text-xs">Approve</button>
                                                </form>
                                                <form action="dashboard.php" method="POST" class="inline-block">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="action" value="reject" class="text-white bg-red-600 hover:bg-red-700 px-3 py-1 rounded-md text-xs">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ($total_pages_pending > 1): ?>
                        <div class="px-6 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50">
                            <div class="flex-1 flex justify-between items-center">
                                <a href="<?= buildPageUrl('page_pending', $page_pending - 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $page_pending <= 1 ? 'pointer-events-none opacity-50' : '' ?>">Previous</a>
                                <span class="text-sm text-gray-700">Halaman <?= $page_pending ?> dari <?= $total_pages_pending ?></span>
                                <a href="<?= buildPageUrl('page_pending', $page_pending + 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $page_pending >= $total_pages_pending ? 'pointer-events-none opacity-50' : '' ?>">Next</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Afiliasi Tervalidasi -->
                <div class="mt-10">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">User Afiliasi Tervalidasi (Aktif)</h2>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Unik</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rekening Bank</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($active_users)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada afiliator yang aktif.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($active_users as $user): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?= sanitize($user['name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= sanitize($user['email']) ?></div>
                                                    <div class="text-xs text-gray-500">WA: <?= sanitize($user['phone'] ?? '-') ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                                    <?= sanitize($user['unique_code']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?= sanitize($user['bank_name'] ?: '-') ?></div>
                                                    <div class="text-sm text-gray-500"><?= sanitize($user['account_number'] ?: '-') ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <form action="dashboard.php" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menonaktifkan afiliator ini?');">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" name="action" value="reject" class="text-white bg-red-600 hover:bg-red-700 px-3 py-1 rounded-md text-xs transition">Nonaktifkan</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ($total_pages_active > 1): ?>
                            <div class="px-6 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50">
                                <div class="flex-1 flex justify-between items-center">
                                    <a href="<?= buildPageUrl('page_active', $page_active - 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $page_active <= 1 ? 'pointer-events-none opacity-50' : '' ?>">Previous</a>
                                    <span class="text-sm text-gray-700">Halaman <?= $page_active ?> dari <?= $total_pages_active ?></span>
                                    <a href="<?= buildPageUrl('page_active', $page_active + 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $page_active >= $total_pages_active ? 'pointer-events-none opacity-50' : '' ?>">Next</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Daftar Semua Pendaftar -->
                <div class="mt-10">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Daftar Semua Pendaftar (Referral)</h2>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendaftar</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendidikan</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliator</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($all_referrals)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada pendaftar.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_referrals as $referral): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?= sanitize($referral['student_name']) ?></div>
                                                    <div class="text-xs text-gray-500">NIK: <?= sanitize($referral['nik']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?= sanitize($referral['program_studi']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= sanitize($referral['asal_sekolah']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <div class="text-sm text-gray-900"><?= sanitize($referral['student_email']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= sanitize($referral['student_phone']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($referral['registered_at'])) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?= sanitize($referral['affiliate_name'] ?? 'Pendaftaran Langsung') ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php if ($referral['payment_status'] === 'paid'): ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Terbayar</span>
                                                    <?php else: ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Belum Bayar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                                    <div class="flex flex-col gap-2 items-center">
                                                        <button type="button" onclick="showDetail(this)" data-referral='<?= htmlspecialchars(json_encode($referral), ENT_QUOTES, 'UTF-8') ?>' class="text-white bg-teal-600 hover:bg-teal-700 px-3 py-1 rounded-md text-xs transition w-full">Detail</button>
                                                        <?php if ($referral['payment_status'] === 'unpaid' && !empty($referral['affiliate_name'])): ?>
                                                            <form action="dashboard.php" method="POST" class="w-full" onsubmit="return confirm('Tandai komisi ini sebagai sudah dibayar?');">
                                                                <input type="hidden" name="action" value="pay_commission">
                                                                <input type="hidden" name="referral_id" value="<?= $referral['id'] ?>">
                                                                <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded-md text-xs transition w-full">Bayar Komisi</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php if ($total_pages_referrals > 1): ?>
                        <div class="px-6 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50">
                            <div class="flex-1 flex justify-between items-center">
                                <a href="<?= buildPageUrl('page_referrals', $page_referrals - 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $page_referrals <= 1 ? 'pointer-events-none opacity-50' : '' ?>">Previous</a>
                                <span class="text-sm text-gray-700">Halaman <?= $page_referrals ?> dari <?= $total_pages_referrals ?></span>
                                <a href="<?= buildPageUrl('page_referrals', $page_referrals + 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $page_referrals >= $total_pages_referrals ? 'pointer-events-none opacity-50' : '' ?>">Next</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Detail Pendaftar -->
    <div id="detailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeDetail()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start w-full">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 border-b pb-2 mb-4" id="modal-title">Detail Pendaftar</h3>
                            <div id="modal-content" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-700 max-h-[70vh] overflow-y-auto pr-2">
                                <!-- Content injected via JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeDetail()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm transition">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showDetail(element) {
        const data = JSON.parse(element.getAttribute('data-referral'));
        const content = document.getElementById('modal-content');
        
        const fields = [
            {label: 'Nama Lengkap', value: data.student_name},
            {label: 'Nama Panggilan', value: data.nama_panggilan},
            {label: 'NIK', value: data.nik},
            {label: 'Email', value: data.student_email},
            {label: 'No. HP (WA)', value: data.student_phone},
            {label: 'Telepon', value: data.telepon},
            {label: 'Tempat, Tgl Lahir', value: (data.tempat_lahir || '-') + ', ' + (data.tanggal_lahir || '-')},
            {label: 'Kewarganegaraan', value: data.kewarganegaraan},
            {label: 'Jenis Kelamin', value: data.jenis_kelamin},
            {label: 'Agama', value: data.agama},
            {label: 'Status Perkawinan', value: data.status_perkawinan},
            {label: 'Alamat', value: data.alamat},
            {label: 'Program Studi', value: data.program_studi},
            {label: 'Waktu Kuliah', value: data.waktu_kuliah},
            {label: 'Lulusan', value: data.lulusan},
            {label: 'Asal Sekolah', value: data.asal_sekolah},
            {label: 'Jurusan Sekolah', value: data.jurusan_sekolah},
            {label: 'Pekerjaan', value: data.pekerjaan},
            {label: 'Sumber Biaya', value: data.sumber_biaya},
            {label: 'Info Dari', value: data.info_dari},
            {label: 'Tgl Wawancara', value: data.tanggal_wawancara},
            {label: 'Afiliator', value: data.affiliate_name || 'Pendaftaran Langsung'}
        ];
        
        content.innerHTML = fields.map(f => `
            <div class="border-b border-gray-100 pb-2">
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">${f.label}</span>
                <span class="block mt-1 text-gray-900 break-words font-medium">${f.value || '-'}</span>
            </div>
        `).join('');
        
        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetail() {
        document.getElementById('detailModal').classList.add('hidden');
    }
    </script>
</body>
</html>
