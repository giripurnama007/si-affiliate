<?php
// includes/functions.php

// Mulai session di awal
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Membersihkan input untuk mencegah XSS.
 * @param string $data Input string.
 * @return string Sanitized string.
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Membuat dan menyimpan CSRF token di session.
 * @return string CSRF token.
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memverifikasi CSRF token yang disubmit.
 * @param string $token Token dari form.
 * @return bool True jika valid, false jika tidak.
 */
function verifyCSRF($token) {
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Hapus token setelah digunakan untuk mencegah replay attacks
        unset($_SESSION['csrf_token']);
        return true;
    }
    return false;
}

/**
 * Melacak klik link afiliasi, menyimpan cookie, dan mencatat klik.
 * @param PDO $pdo PDO database connection object.
 */
function trackAffiliateClick($pdo) {
    if (isset($_GET['ref'])) {
        $ref_code = sanitize($_GET['ref']);

        // Cek apakah ref_code valid
        $stmt = $pdo->prepare("SELECT id FROM users WHERE unique_code = ? AND status = 'active'");
        $stmt->execute([$ref_code]);
        $user = $stmt->fetch();

        if ($user) {
            // Set cookie selama 30 hari
            setcookie('affiliate_ref', $ref_code, time() + (86400 * 30), "/"); // 86400 = 1 hari

            // Catat klik ke database
            try {
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $pdo->prepare("INSERT INTO clicks (affiliate_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $ip_address, $user_agent]);
            } catch (PDOException $e) {
                // Abaikan jika ada error (misal: duplikat klik dari IP yang sama dalam waktu singkat jika ada constraint)
                // Untuk aplikasi production, ini bisa di-log ke file error.
            }
        }
    }
}

/**
 * Membuat soal CAPTCHA matematika sederhana.
 * @return string Pertanyaan CAPTCHA.
 */
function generateCaptcha() {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_answer'] = $num1 + $num2;
    return "Berapa hasil dari $num1 + $num2 ?";
}

/**
 * Memverifikasi jawaban CAPTCHA.
 * @param string|int $answer Jawaban dari form.
 * @return bool True jika valid, false jika tidak.
 */
function verifyCaptcha($answer) {
    if (isset($_SESSION['captcha_answer']) && (int)$answer === $_SESSION['captcha_answer']) {
        unset($_SESSION['captcha_answer']); // Hapus setelah digunakan
        return true;
    }
    return false;
}
?>
