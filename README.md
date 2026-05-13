# E-Afiliate PMB

E-Afiliate PMB adalah aplikasi afiliasi sederhana berbasis PHP untuk membantu proses promosi dan pendaftaran mahasiswa baru. Afiliator mendapatkan link unik untuk dibagikan, sistem mencatat klik dan referral, lalu admin dapat mengelola validasi akun, data pendaftar, komisi, dan status pembayaran komisi.

## Fitur

- Registrasi akun afiliator dengan status awal `pending`.
- Login berbasis role untuk `admin` dan `user`.
- Validasi akun afiliator oleh admin.
- Link afiliasi unik dengan parameter `?ref=kode_unik`.
- Tracking klik link afiliasi menggunakan cookie referral selama 30 hari.
- Formulir pendaftaran mahasiswa baru.
- Dashboard afiliator berisi total klik, total referral, estimasi komisi, link afiliasi, dan riwayat referral.
- Fitur berbagi link ke WhatsApp, Facebook, X, Telegram, Instagram, dan LinkedIn.
- Pengaturan template pesan share oleh admin dan afiliator.
- Pengaturan nominal komisi dan riwayat perubahan komisi.
- Status pembayaran komisi per referral.
- Proteksi dasar berupa sanitasi input, CSRF token, password hashing, dan CAPTCHA matematika sederhana.

## Kebutuhan Sistem

- PHP 7.4 atau lebih baru.
- MySQL atau MariaDB.
- Web server lokal seperti Apache dari XAMPP, Laragon, WAMP, atau server PHP bawaan.
- Browser modern.
- Koneksi internet untuk memuat Tailwind CSS, Vue.js, dan Google Fonts dari CDN.

## Instalasi

1. Salin folder proyek ke direktori web server.

   Contoh untuk XAMPP:

   ```text
   C:\xampp\htdocs\e-afiliate
   ```

   Contoh untuk Laragon:

   ```text
   C:\laragon\www\e-afiliate
   ```

2. Buat database baru di MySQL/MariaDB dengan nama:

   ```sql
   affiliate_pmb
   ```

3. Import file database:

   ```text
   databases.sql
   ```

   Import dapat dilakukan melalui phpMyAdmin atau terminal MySQL.

4. Sesuaikan konfigurasi database di `config/db.php`.

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'affiliate_pmb');
   ```

5. Jalankan aplikasi melalui browser:

   ```text
   http://localhost/e-afiliate/
   ```

## Akun Admin Default

File `databases.sql` menyediakan akun admin default:

```text
Email    : admin@example.com
Password : password
```

Setelah berhasil login, sebaiknya ubah kredensial admin atau ganti data admin langsung dari database sebelum aplikasi digunakan secara publik.

## Alur Penggunaan

1. Afiliator mendaftar melalui:

   ```text
   register.php
   ```

2. Admin login dan membuka:

   ```text
   admin/dashboard.php
   ```

3. Admin menyetujui akun afiliator yang masih `pending`.

4. Afiliator login dan membuka:

   ```text
   user/dashboard.php
   ```

5. Afiliator menyalin atau membagikan link unik, misalnya:

   ```text
   http://localhost/e-afiliate/index.php?ref=ref_xxxxx
   ```

6. Calon mahasiswa mengisi formulir pendaftaran di halaman utama.

7. Sistem mengaitkan pendaftar dengan afiliator berdasarkan cookie referral.

8. Admin dapat melihat data referral dan menandai komisi sebagai sudah dibayar.

## Struktur Folder

```text
e-afiliate/
+-- admin/
|   +-- dashboard.php
+-- config/
|   +-- db.php
+-- includes/
|   +-- functions.php
+-- user/
|   +-- dashboard.php
|   +-- profile.php
+-- databases.sql
+-- index.php
+-- login.php
+-- logout.php
+-- register.php
+-- README.md
```

## Ringkasan File Penting

- `index.php`: halaman formulir pendaftaran mahasiswa baru dan tracking referral.
- `register.php`: halaman pendaftaran akun afiliator.
- `login.php`: autentikasi user dan admin.
- `logout.php`: keluar dari sesi login.
- `admin/dashboard.php`: panel admin untuk validasi afiliator, komisi, template share, referral, dan status pembayaran.
- `user/dashboard.php`: panel afiliator untuk melihat statistik, link unik, template share, dan riwayat referral.
- `user/profile.php`: halaman edit profil afiliator.
- `config/db.php`: konfigurasi koneksi database.
- `includes/functions.php`: helper sanitasi input, CSRF, CAPTCHA, dan tracking klik.
- `databases.sql`: skema tabel dan data awal aplikasi.

## Database

Tabel utama yang digunakan:

- `users`: data admin dan afiliator.
- `clicks`: riwayat klik link afiliasi.
- `referrals`: data pendaftar mahasiswa baru dari link afiliasi.
- `settings`: pengaturan aplikasi, termasuk template pesan share.
- `commission_history`: riwayat perubahan nilai komisi.

## Catatan Keamanan

- Ganti kredensial admin default sebelum aplikasi dipakai di production.
- Pastikan konfigurasi database tidak menggunakan user root tanpa password di server publik.
- Aktifkan HTTPS jika aplikasi dipasang online.
- Batasi akses ke file sensitif seperti `databases.sql` dan `config/db.php`.
- Validasi dan audit ulang input form sebelum digunakan untuk lingkungan production.

## Teknologi

- PHP
- MySQL/MariaDB
- PDO
- Tailwind CSS CDN
- Vue.js CDN
