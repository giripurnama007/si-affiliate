<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_share_message') {
    $custom_msg = sanitize($_POST['custom_message'] ?? '');
    $stmt_update = $pdo->prepare("UPDATE users SET custom_share_message = ? WHERE id = ?");
    $stmt_update->execute([$custom_msg, $user_id]);
    $success_msg = "Template pesan berhasil disimpan!";
}

// Ambil data user
$stmt = $pdo->prepare("SELECT name, unique_code, custom_share_message FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil statistik (menggunakan variabel $stmt yang berbeda untuk kejelasan)
$stmt_clicks = $pdo->prepare("SELECT COUNT(*) as total FROM clicks WHERE affiliate_id = ?");
$stmt_clicks->execute([$user_id]);
$clicks = $stmt_clicks->fetchColumn();

$stmt_referrals = $pdo->prepare("SELECT COUNT(*) as total FROM referrals WHERE affiliate_id = ?");
$stmt_referrals->execute([$user_id]);
$referrals = $stmt_referrals->fetchColumn();

// Ambil nilai komisi dari pengaturan
$stmt_setting = $pdo->query("SELECT commission_amount FROM commission_history ORDER BY created_at DESC LIMIT 1");
$commission_rate = (int)($stmt_setting->fetchColumn() ?: 50000);

// Ambil jumlah referral yang belum dibayar
$stmt_unpaid = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE affiliate_id = ? AND payment_status = 'unpaid'");
$stmt_unpaid->execute([$user_id]);
$unpaid_referrals = $stmt_unpaid->fetchColumn();

// Hitung total komisi berjalan berdasarkan referral yang belum dibayar saja
$commission = $unpaid_referrals * $commission_rate;

// Ambil daftar referral milik user ini
$stmt_user_referrals = $pdo->prepare("SELECT student_name, student_email, student_phone, nik, asal_sekolah, program_studi, payment_status, DATE_FORMAT(registered_at, '%d %b %Y, %H:%i') as formatted_date FROM referrals WHERE affiliate_id = ? ORDER BY registered_at DESC");
$stmt_user_referrals->execute([$user_id]);
$user_referrals = $stmt_user_referrals->fetchAll();

// Ambil template pesan share
$stmt_share_msg = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'share_message_template'");
$share_message_template = $stmt_share_msg->fetchColumn() ?: 'Daftar di sini: {{link}}';

// Tentukan template yang akan digunakan (kustom milik user atau default admin)
$final_share_template = !empty($user['custom_share_message']) ? $user['custom_share_message'] : $share_message_template;

// Siapkan data untuk Vue.js
$vue_data = [
    'name' => $user['name'],
    'unique_code' => $user['unique_code'],
    'stats' => [
        'clicks' => (int)$clicks,
        'referrals' => (int)$referrals,
        'commission' => (int)$commission,
    ],
    'referral_list' => $user_referrals,
    'share_template' => $final_share_template
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
    <div id="app" class="min-h-screen">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Afiliasi</h1>
                <div>
                    <span class="text-gray-700 mr-4">Halo, {{ name }}</span>
                    <a href="profile.php" class="text-sm font-medium text-blue-600 hover:text-blue-500 mr-4">Edit Profil</a>
                    <a href="../logout.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">Logout</a>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <?php if ($success_msg): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
                    <p><?= htmlspecialchars($success_msg) ?></p>
                </div>
            <?php endif; ?>

            <!-- Statistik -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-sm font-medium text-gray-500">Total Klik</h3>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.clicks }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-sm font-medium text-gray-500">Total Referral</h3>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.referrals }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-sm font-medium text-gray-500">Estimasi Komisi</h3>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">Rp {{ formatCurrency(stats.commission) }}</p>
                </div>
            </div>

            <!-- Link Afiliasi -->
            <div class="bg-white p-8 rounded-lg shadow">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Link Afiliasi Unik Anda</h2>
                <div class="flex flex-col sm:flex-row items-stretch gap-2">
                    <input type="text" :value="affiliateLink" readonly class="flex-grow bg-gray-100 border border-gray-300 rounded-lg px-4 py-2 text-gray-700 focus:outline-none">
                    <button @click="copyLink" class="bg-blue-700 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-800 transition duration-300">
                        {{ copyButtonText }}
                    </button>
                </div>
                <form action="dashboard.php" method="POST" class="mt-6">
                    <input type="hidden" name="action" value="save_share_message">
                    <label for="share-message" class="block text-sm font-medium text-gray-700 mb-2">Narasi untuk dibagikan:</label>
                    <textarea id="share-message" name="custom_message" v-model="shareMessage" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <button type="submit" class="mt-2 bg-gray-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-300 text-sm">Simpan Template Anda</button>
                </form>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <h3 class="text-md font-medium text-gray-600">Bagikan ke:</h3>
                    <button @click="shareToWhatsApp" class="bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition duration-300">WhatsApp</button>
                    <button @click="shareToFacebook" class="bg-blue-800 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-900 transition duration-300">Facebook</button>
                    <button @click="shareToX" class="bg-black text-white font-bold py-2 px-4 rounded-lg hover:bg-gray-800 transition duration-300">X</button>
                    <button @click="shareToTelegram" class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-300">Telegram</button>
                    <button @click="shareToInstagram" class="bg-gradient-to-r from-pink-500 to-yellow-500 text-white font-bold py-2 px-4 rounded-lg hover:opacity-90 transition duration-300">Instagram</button>
                    <button @click="shareToLinkedIn" class="bg-blue-700 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-800 transition duration-300">LinkedIn</button>
                </div>
            </div>

            <!-- Riwayat Referral -->
            <div class="mt-8 bg-white p-8 rounded-lg shadow">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Riwayat Referral Anda</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendaftar</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendidikan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Komisi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="referral_list.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Anda belum memiliki referral.</td>
                            </tr>
                            <tr v-for="referral in referral_list" :key="referral.student_email">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ referral.student_name }}</div>
                                    <div class="text-xs text-gray-500">NIK: {{ referral.nik }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ referral.program_studi }}</div>
                                    <div class="text-xs text-gray-500">{{ referral.asal_sekolah }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ referral.student_email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ referral.student_phone }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ referral.formatted_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="referral.payment_status === 'paid'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Terbayar</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const serverData = <?= json_encode($vue_data) ?>;

        const { createApp, ref, computed } = Vue

        createApp({
            setup() {
                const name = ref(serverData.name);
                const unique_code = ref(serverData.unique_code);
                const stats = ref(serverData.stats);
                const copyButtonText = ref('Copy Link');
                const referral_list = ref(serverData.referral_list);
                const shareMessage = ref('');

                // Variabel untuk Paging
                const currentPage = ref(1);
                const itemsPerPage = 20;

                const totalPages = computed(() => {
                    return Math.ceil(referral_list.value.length / itemsPerPage) || 1;
                });

                const paginatedReferrals = computed(() => {
                    const start = (currentPage.value - 1) * itemsPerPage;
                    return referral_list.value.slice(start, start + itemsPerPage);
                });

                const nextPage = () => {
                    if (currentPage.value < totalPages.value) currentPage.value++;
                };

                const prevPage = () => {
                    if (currentPage.value > 1) currentPage.value--;
                };

                // Menghasilkan URL root yang benar, misal: http://localhost/affiliate-pmb/
                const rootUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname.substring(0, window.location.pathname.indexOf('/user/'))}/`;
                
                const affiliateLink = computed(() => {
                    return `${rootUrl}index.php?ref=${unique_code.value}`;
                });

                // Inisialisasi pesan share
                shareMessage.value = serverData.share_template.replace('{{link}}', affiliateLink.value);

                const formatCurrency = (value) => {
                    return new Intl.NumberFormat('id-ID').format(value);
                };

                const copyLink = () => {
                    navigator.clipboard.writeText(affiliateLink.value).then(() => {
                        copyButtonText.value = 'Copied!';
                        setTimeout(() => {
                            copyButtonText.value = 'Copy Link';
                        }, 2000);
                    });
                };

                const shareToWhatsApp = () => {
                    const text = shareMessage.value;
                    const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
                    window.open(url, '_blank');
                };

                const shareToFacebook = () => {
                    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(affiliateLink.value)}`;
                    window.open(url, '_blank');
                };

                const shareToX = () => {
                    const text = shareMessage.value;
                    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`;
                    window.open(url, '_blank');
                };

                const shareToTelegram = () => {
                    const text = shareMessage.value;
                    const url = `https://t.me/share/url?url=${encodeURIComponent(affiliateLink.value)}&text=${encodeURIComponent(text)}`;
                    window.open(url, '_blank');
                };

                const shareToLinkedIn = () => {
                    const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(affiliateLink.value)}`;
                    window.open(url, '_blank');
                };

                const shareToInstagram = () => {
                    navigator.clipboard.writeText(shareMessage.value).then(() => {
                        alert('Narasi telah disalin! Silakan paste di Instagram (Bio, Story, atau Post).');
                        window.open('https://www.instagram.com/', '_blank');
                    });
                };

                return {
                    name,
                    stats,
                    affiliateLink,
                    copyButtonText,
                    referral_list,
                    shareMessage,
                    currentPage,
                    totalPages,
                    paginatedReferrals,
                    nextPage,
                    prevPage,
                    formatCurrency,
                    copyLink,
                    shareToWhatsApp,
                    shareToFacebook,
                    shareToX,
                    shareToTelegram,
                    shareToLinkedIn,
                    shareToInstagram
                }
            }
        }).mount('#app')
    </script>
</body>
</html>
