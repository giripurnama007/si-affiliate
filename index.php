<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Lacak klik afiliasi saat halaman diakses
trackAffiliateClick($pdo);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = 'Jawaban CAPTCHA salah. Silakan coba lagi.';
    } else {
        $student_name = sanitize($_POST['name'] ?? '');
        $nama_panggilan = sanitize($_POST['nama_panggilan'] ?? '');
        $tempat_lahir = sanitize($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir = sanitize($_POST['tanggal_lahir'] ?? '');
        $student_email = sanitize($_POST['email'] ?? '');
        $kewarganegaraan = sanitize($_POST['kewarganegaraan'] ?? '');
        $jenis_kelamin = sanitize($_POST['jenis_kelamin'] ?? '');
        $agama = sanitize($_POST['agama'] ?? '');
        $student_nik = sanitize($_POST['nik'] ?? '');
        $status_perkawinan = sanitize($_POST['status_perkawinan'] ?? '');
        $student_phone = sanitize($_POST['phone'] ?? '');
        $telepon = sanitize($_POST['telepon'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');
        
        $program_studi = sanitize($_POST['program_studi'] ?? '');
        $lulusan = sanitize($_POST['lulusan'] ?? '');
        $waktu_kuliah = sanitize($_POST['waktu_kuliah'] ?? '');
        
        $asal_sekolah = sanitize($_POST['asal_sekolah'] ?? '');
        $alamat_sekolah = sanitize($_POST['alamat_sekolah'] ?? '');
        $nisn = sanitize($_POST['nisn'] ?? '');
        $nilai_rata_rata = sanitize($_POST['nilai_rata_rata'] ?? '');
        $jurusan_sekolah = sanitize($_POST['jurusan_sekolah'] ?? '');
        $tahun_lulus = sanitize($_POST['tahun_lulus'] ?? '');
        
        $instansi_pekerjaan = sanitize($_POST['instansi_pekerjaan'] ?? '');
        $alamat_perusahaan = sanitize($_POST['alamat_perusahaan'] ?? '');
        $bagian_pekerjaan = sanitize($_POST['bagian_pekerjaan'] ?? '');
        $pekerjaan = sanitize($_POST['pekerjaan'] ?? '');
        
        $bahasa_asing = sanitize($_POST['bahasa_asing'] ?? '');
        $kemampuan_membaca = sanitize($_POST['kemampuan_membaca'] ?? '');
        $kemampuan_menulis = sanitize($_POST['kemampuan_menulis'] ?? '');
        $kemampuan_berbicara = sanitize($_POST['kemampuan_berbicara'] ?? '');
        
        $sumber_biaya = sanitize($_POST['sumber_biaya'] ?? '');
        $nama_orang_tua = sanitize($_POST['nama_orang_tua'] ?? '');
        $pekerjaan_orang_tua = sanitize($_POST['pekerjaan_orang_tua'] ?? '');
        $alamat_orang_tua = sanitize($_POST['alamat_orang_tua'] ?? '');
        $info_dari = sanitize($_POST['info_dari'] ?? '');
        
        $tanggal_wawancara = sanitize($_POST['tanggal_wawancara'] ?? '');
        $tanggal_bayar = sanitize($_POST['tanggal_bayar'] ?? '');

        $affiliate_id = null;
        if (isset($_COOKIE['affiliate_ref'])) {
            $ref_code = sanitize($_COOKIE['affiliate_ref']);
            $stmt = $pdo->prepare("SELECT id FROM users WHERE unique_code = ?");
            $stmt->execute([$ref_code]);
            $affiliate = $stmt->fetch();
            if ($affiliate) {
                $affiliate_id = $affiliate['id'];
            }
        }
 
        try {
            $stmt = $pdo->prepare("INSERT INTO referrals (
                affiliate_id, student_name, nama_panggilan, student_email, kewarganegaraan, student_phone, 
                telepon, nik, status_perkawinan, asal_sekolah, alamat_sekolah, nisn, nilai_rata_rata, 
                jurusan_sekolah, program_studi, lulusan, waktu_kuliah, tempat_lahir, tanggal_lahir, 
                jenis_kelamin, agama, alamat, tahun_lulus, pekerjaan, bagian_pekerjaan, instansi_pekerjaan, 
                alamat_perusahaan, bahasa_asing, kemampuan_membaca, kemampuan_menulis, kemampuan_berbicara, 
                sumber_biaya, nama_orang_tua, pekerjaan_orang_tua, alamat_orang_tua, info_dari, 
                tanggal_wawancara, tanggal_bayar
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $affiliate_id, $student_name, $nama_panggilan, $student_email, $kewarganegaraan, $student_phone, 
                $telepon, $student_nik, $status_perkawinan, $asal_sekolah, $alamat_sekolah, $nisn, $nilai_rata_rata, 
                $jurusan_sekolah, $program_studi, $lulusan, $waktu_kuliah, $tempat_lahir, $tanggal_lahir, 
                $jenis_kelamin, $agama, $alamat, $tahun_lulus, $pekerjaan, $bagian_pekerjaan, $instansi_pekerjaan, 
                $alamat_perusahaan, $bahasa_asing, $kemampuan_membaca, $kemampuan_menulis, $kemampuan_berbicara, 
                $sumber_biaya, $nama_orang_tua, $pekerjaan_orang_tua, $alamat_orang_tua, $info_dari, 
                $tanggal_wawancara, $tanggal_bayar
            ]);
            $message = 'Pendaftaran berhasil! Terima kasih telah mendaftar.';
        } catch (PDOException $e) {
            $error = 'Gagal mendaftar. Silakan coba lagi. ' . $e->getMessage();
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
    <title>Pendaftaran Mahasiswa Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-image: url('https://pmb.sains.ac.id/images/about/998WhatsApp-Image-2025-01-11-at-11.12.28.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .focus-ring:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 py-10 bg-black bg-opacity-50 backdrop-blur-md">
        <div class="w-full max-w-3xl">
            <div class="bg-white shadow-lg rounded-xl p-8">
                <div class="flex justify-center mb-6">
                    <img src="https://data.kelaskaryawan.com/sains/uploads/mt4oTqbplSqA7p9cPAe1JnMxlm13iJOy6sIVZjQM.jpg" alt="Logo" class="h-24 w-auto object-contain">
                </div>
                <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Formulir Pendaftaran</h1>
                <p class="text-center text-gray-500 mb-6">Pendaftaran Online Kelas Karyawan</p>
                
                <!-- Informasi Program Kelas Karyawan USI -->
                <!-- <div class="mb-8 p-6 bg-blue-50 rounded-lg border border-blue-200">
                    <h2 class="text-2xl font-bold text-blue-800 mb-4 text-center">
                        Anda Sibuk Bekerja, Ingin Kuliah S1 di Bekasi?
                    </h2>
                    <p class="text-gray-700 mb-4 leading-relaxed">
                        Silahkan bergabung dengan Program Kelas Karyawan Universitas Sains Indonesia (USI) Bekasi, Kelas Karyawan Terbaik Dan Termurah Di Bekasi.
                    </p>
                    <p class="text-gray-700 mb-4 leading-relaxed">
                        Program Kelas Karyawan Universitas Sains Indonesia (USI) Bekasi bertujuan untuk masa depan lebih baik dengan Biaya Terjangkau bagi anda para karyawan, pekerja atau buruh yang sibuk bekerja tapi ingin melanjutkan pendidikan ke Jenjang S1 dengan Biaya Kuliah Hanya Rp. 600.000 per bulan.
                    </p>
                    <p class="text-gray-700 mb-4 leading-relaxed">
                        Kualitas dan proses pendidikan pada Program Kelas Karyawan USI sama dirancang sama dengan Kualitas dan proses pendidikan pada hari biasa. Setiap perkuliahaan diatur secara terstruktur dan terjadwal dengan pemilihan tenaga pengajar terbaik dan berpengalaman di bidangnya. Proses belajar didukung oleh fasilitas terbaik seperti Ruang Kuliah ber-AC, Laboratorium, Studio, Perpustakaan, Sarana Olahraga dan lain-lain.
                    </p>
                    <p class="text-gray-700 mb-6 leading-relaxed">
                        Untuk kenyaman dan keamanan mahasiswa maka disediakan Mess (Tempat Menginap) secara gratis bagi mahasiswa yang berdomisili jauh dari kampus (Luar Kota).
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center mb-6">
                        <a href="https://www.youtube.com/watch?v=VW72WTt6lYQ" target="_blank" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center">
                            Profil USI di Youtube <span class="ml-1 px-2 py-1 bg-blue-100 rounded-md text-sm">KLIK DISINI</span>
                        </a>
                        <a href="https://chat.whatsapp.com/IaSVYbJg3JOExoxKlAfqA8" target="_blank" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center">
                            Bergabung di Group WA <span class="ml-1 px-2 py-1 bg-blue-100 rounded-md text-sm">KLIK DISINI</span>
                        </a>
                    </div>

                    <h3 class="text-xl font-bold text-blue-800 mb-3">KEUNGGULAN-KEUNGGULAN</h3>
                    <p class="text-gray-700 mb-3">Berikut ini adalah kelebihan-kelebihan Kelas Karyawan USI Bekasi yaitu:</p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-1">
                        <li>Diselenggarakan oleh Universitas Swasta Terbaik di Bekasi</li>
                        <li>Program Studi Terakreditasi BAN-PT</li>
                        <li>Saat ini ada 1600 orang lebih Mahasiswa yang sedang menempuh pendidikan di Kelas Karyawan USI</li>
                        <li>Lokasi kampus strategi, mudah dicapai dari manapun menggunakan KRL ke stasiun Cibitung.</li>
                        <li>Disediakan Mess (Tempat Menginap) secara gratis bagi Mahasiswa dari Luar Kota</li>
                        <li>Kurikulum dan Proses Belajar diatur secara sistematis agar mahasiswa lulus tepat waktu</li>
                        <li>Perkuliahaan dilaksanakan dengan Metode Hybrid menggunakan Teknologi Modern</li>
                        <li>Jadwal kuliah bisa dipilih mahasiswa</li>
                        <li>Biaya studi terjangkau dan dapat diangsur sesuai kemampuan</li>
                        <li>Disediakan fasilitas Elearning agar mahasiswa dapat belajar dimanapun tanpa batas waktu</li>
                        <li>Kualitas dan proses pendidikan dirancang sama dengan Program Reguler.</li>
                        <li>Disediakan Career Center untuk meningkat karir mahasiswa</li>
                        <li>Tidak ada batasan umur dan tahun kelulusan pendidikan terakhir mahasiswa</li>
                        <li>Fasilitas pendidikan lengkap</li>
                        <li>Diasuh oleh Tenaga Pengajar berpengalaman yang profesional dibidangnya</li>
                    </ul>

                    <h3 class="text-xl font-bold text-blue-800 mb-3">PILIHAN JADWAL KULIAH</h3>
                    <p class="text-gray-700 mb-3">Mahasiswa Kelas Karyawan dapat memilih jadwal kuliah, yaitu:</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
                        <li>Kelas Sabtu Pagi: Jam 07.00 – 14.30 WIB + Elearning</li>
                        <li>Kelas Sabtu Siang: Jam 14.30 – 22.00 WIB + Elearning</li>
                        <li>Kelas Malam: Senin – Jumat: Jam 19.00 – 21.30 WIB + Elearning</li>
                        <li>Kelas Shift: Jadwal Kuliah Fleksibel</li>
                    </ul>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Elearning adalah proses belajar mengajar melalui Multi Acces Learning (MAL) dimana Bahan Ajar, Diskusi, Tugas dan Quiz diakses di internet 24 jam sehari, ditambah kuliah tatap muka 3 kali dan ujian 2 kali di kelas dalam satu semester.
                    </p>
                </div> --> 
                
                <?php if ($message): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <p><?= $message ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <p><?= $error ?></p>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <!-- DATA PRIBADI -->
                    <div class="mt-2 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">DATA PRIBADI</h3>
                        <p class="text-sm text-gray-500">Silahkan isi dengan huruf KAPITAL data pribadi anda di bawah ini</p>
                    </div>
                    
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="nama_panggilan" class="block text-gray-700 text-sm font-medium mb-2">Nama Panggilan <span class="text-red-500">*</span></label>
                            <input type="text" id="nama_panggilan" name="nama_panggilan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="tempat_lahir" class="block text-gray-700 text-sm font-medium mb-2">Tempat Lahir <span class="text-red-500">*</span></label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Lahir <span class="text-red-500">*</span></label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="kewarganegaraan" class="block text-gray-700 text-sm font-medium mb-2">Kewarganegaraan <span class="text-red-500">*</span></label>
                            <select id="kewarganegaraan" name="kewarganegaraan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                                <option value="">-- Pilih --</option>
                                <option value="WNI">WNI</option>
                                <option value="WNA">WNA</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="jenis_kelamin" class="block text-gray-700 text-sm font-medium mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                            <select id="jenis_kelamin" name="jenis_kelamin" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                                <option value="">-- Pilih --</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="agama" class="block text-gray-700 text-sm font-medium mb-2">Agama <span class="text-red-500">*</span></label>
                            <input type="text" id="agama" name="agama" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nik" class="block text-gray-700 text-sm font-medium mb-2">NIK <span class="text-red-500">*</span></label>
                            <input type="text" id="nik" name="nik" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="status_perkawinan" class="block text-gray-700 text-sm font-medium mb-2">Status Perkawinan <span class="text-red-500">*</span></label>
                            <select id="status_perkawinan" name="status_perkawinan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                                <option value="">-- Pilih --</option>
                                <option value="Sudah Menikah">Sudah Menikah</option>
                                <option value="Belum Menikah">Belum Menikah</option>
                                <option value="Duda/Janda">Duda/Janda</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-gray-700 text-sm font-medium mb-2">Nomor WhatsApp (WA) <span class="text-red-500">*</span></label>
                            <input type="tel" id="phone" name="phone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="telepon" class="block text-gray-700 text-sm font-medium mb-2">No HP/Telepon</label>
                            <input type="tel" id="telepon" name="telepon" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="alamat" class="block text-gray-700 text-sm font-medium mb-2">Alamat Tempat Tinggal <span class="text-red-500">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200"></textarea>
                    </div>

                    <!-- PROGRAM STUDI DAN WAKTU KULIAH -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">PROGRAM STUDI DAN WAKTU KULIAH</h3>
                        <p class="text-sm text-gray-500">Pilih Program Studi, Tempat dan Waktu kuliah</p>
                    </div>
                    
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="program_studi" class="block text-gray-700 text-sm font-medium mb-2">Program yang dipilih <span class="text-red-500">*</span></label>
                            <select id="program_studi" name="program_studi" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
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
                        <div>
                            <label for="lulusan" class="block text-gray-700 text-sm font-medium mb-2">Anda Lulusan <span class="text-red-500">*</span></label>
                            <select id="lulusan" name="lulusan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                                <option value="">-- Pilih --</option>
                                <option value="SMU/K/PAKET C">SMU/K/PAKET C</option>
                                <option value="D3/AKADEMI SEDERAJAT">D3/AKADEMI SEDERAJAT</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="waktu_kuliah" class="block text-gray-700 text-sm font-medium mb-2">Waktu Kuliah yang dipilih <span class="text-red-500">*</span></label>
                        <select id="waktu_kuliah" name="waktu_kuliah" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                            <option value="">-- Pilih --</option>
                            <option value="Kelas Sabtu Pagi: Jam 07.00 – 14.30 WIB">Kelas Sabtu Pagi: Jam 07.00 – 14.30 WIB</option>
                            <option value="Kelas Sabtu Siang: Jam 14.30 – 22.00 WIB">Kelas Sabtu Siang: Jam 14.30 – 22.00 WIB</option>
                            <option value="Kelas Malam: Senin – Jumat: Jam 19.00 – 21.30 WIB">Kelas Malam: Senin – Jumat: Jam 19.00 – 21.30 WIB</option>
                            <option value="Kelas Shift: Jadwal Kuliah Fleksibel">Kelas Shift: Jadwal Kuliah Fleksibel</option>
                        </select>
                    </div>

                    <!-- LATAR BELAKANG PENDIDIKAN -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">LATAR BELAKANG PENDIDIKAN</h3>
                        <p class="text-sm text-gray-500">Silahkan isi pendidikan terakhir anda</p>
                    </div>
                    
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="asal_sekolah" class="block text-gray-700 text-sm font-medium mb-2">Nama Sekolah <span class="text-red-500">*</span></label>
                            <input type="text" id="asal_sekolah" name="asal_sekolah" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="alamat_sekolah" class="block text-gray-700 text-sm font-medium mb-2">Alamat Sekolah <span class="text-red-500">*</span></label>
                            <input type="text" id="alamat_sekolah" name="alamat_sekolah" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nisn" class="block text-gray-700 text-sm font-medium mb-2">NISN & NIM (Untuk Lulusan D3)</label>
                            <input type="text" id="nisn" name="nisn" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="nilai_rata_rata" class="block text-gray-700 text-sm font-medium mb-2">IPK/Nilai Rata-Rata <span class="text-red-500">*</span></label>
                            <input type="text" id="nilai_rata_rata" name="nilai_rata_rata" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="jurusan_sekolah" class="block text-gray-700 text-sm font-medium mb-2">Jurusan <span class="text-red-500">*</span></label>
                            <input type="text" id="jurusan_sekolah" name="jurusan_sekolah" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="tahun_lulus" class="block text-gray-700 text-sm font-medium mb-2">Tahun Lulus <span class="text-red-500">*</span></label>
                            <input type="number" id="tahun_lulus" name="tahun_lulus" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>

                    <!-- LATAR BELAKANG PEKERJAAN -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">LATAR BELAKANG PEKERJAAN</h3>
                        <p class="text-sm text-gray-500">Silahkan isi data pekerjaan anda, kosongkan jika belum bekerja</p>
                    </div>
                    
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="instansi_pekerjaan" class="block text-gray-700 text-sm font-medium mb-2">Nama Perusahaan</label>
                            <input type="text" id="instansi_pekerjaan" name="instansi_pekerjaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="alamat_perusahaan" class="block text-gray-700 text-sm font-medium mb-2">Alamat Perusahaan</label>
                            <input type="text" id="alamat_perusahaan" name="alamat_perusahaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bagian_pekerjaan" class="block text-gray-700 text-sm font-medium mb-2">Bagian</label>
                            <input type="text" id="bagian_pekerjaan" name="bagian_pekerjaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="pekerjaan" class="block text-gray-700 text-sm font-medium mb-2">Jabatan</label>
                            <input type="text" id="pekerjaan" name="pekerjaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>

                    <!-- PENGUASAAN BAHASA ASING -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">PENGUASAAN BAHASA ASING</h3>
                        <p class="text-sm text-gray-500">Kemampuan anda dalam berbahasa asing</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="bahasa_asing" class="block text-gray-700 text-sm font-medium mb-2">Bahasa</label>
                        <input type="text" id="bahasa_asing" name="bahasa_asing" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                    </div>
                    
                    <div class="mb-4 overflow-x-auto">
                        <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 font-medium text-gray-700"></th>
                                    <th class="px-4 py-2 font-medium text-gray-700">Baik</th>
                                    <th class="px-4 py-2 font-medium text-gray-700">Sedang</th>
                                    <th class="px-4 py-2 font-medium text-gray-700">Buruk</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-700 bg-gray-50">Membaca</td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_membaca" value="Baik" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_membaca" value="Sedang" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_membaca" value="Buruk" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-700 bg-gray-50">Menulis</td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_menulis" value="Baik" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_menulis" value="Sedang" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_menulis" value="Buruk" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-700 bg-gray-50">Berbicara</td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_berbicara" value="Baik" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_berbicara" value="Sedang" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                    <td class="px-4 py-3"><input type="radio" name="kemampuan_berbicara" value="Buruk" class="w-4 h-4 text-blue-600 focus:ring-blue-500"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- BIAYA STUDI DAN LAIN-LAIN -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">BIAYA STUDI DAN LAIN-LAIN</h3>
                        <p class="text-sm text-gray-500">Sumber Biaya Studi dan Rencana Pembayaran</p>
                    </div>
                    
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="sumber_biaya" class="block text-gray-700 text-sm font-medium mb-2">Biaya Studi dari</label>
                            <select id="sumber_biaya" name="sumber_biaya" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                                <option value="">-- Pilih --</option>
                                <option value="Orang Tua / Wali">Orang Tua / Wali</option>
                                <option value="Sendiri">Sendiri</option>
                                <option value="Tugas Belajar / Beasiswa / Perusahaan">Tugas Belajar / Beasiswa / Perusahaan</option>
                            </select>
                        </div>
                        <div>
                            <label for="nama_orang_tua" class="block text-gray-700 text-sm font-medium mb-2">Nama Orang Tua/Wali</label>
                            <input type="text" id="nama_orang_tua" name="nama_orang_tua" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="pekerjaan_orang_tua" class="block text-gray-700 text-sm font-medium mb-2">Pekerjaan Orang Tua/Wali</label>
                            <input type="text" id="pekerjaan_orang_tua" name="pekerjaan_orang_tua" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                        <div>
                            <label for="alamat_orang_tua" class="block text-gray-700 text-sm font-medium mb-2">Alamat Orang Tua/Wali</label>
                            <input type="text" id="alamat_orang_tua" name="alamat_orang_tua" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="info_dari" class="block text-gray-700 text-sm font-medium mb-2">Informasi USI di Peroleh dari <span class="text-red-500">*</span></label>
                        <select id="info_dari" name="info_dari" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200 bg-white">
                            <option value="">-- Pilih --</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Website">Website</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Baliho">Baliho</option>
                            <option value="SMS / WA">SMS / WA</option>
                            <option value="Keluarga">Keluarga</option>
                            <option value="Rekan Kerja">Rekan Kerja</option>
                            <option value="Teman Sekolah">Teman Sekolah</option>
                            <option value="Perusahaan / Instansi">Perusahaan / Instansi</option>
                            <option value="Rekomendasi">Rekomendasi</option>
                            <option value="Email">Email</option>
                            <option value="Tiktok">Tiktok</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- JADWAL WAWANCARA -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">JADWAL WAWANCARA</h3>
                        <p class="text-sm text-gray-500">Kapan anda akan datang ke kampus untuk wawancara.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tanggal_wawancara" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Wawancara <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal_wawancara" name="tanggal_wawancara" required class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                    </div>

                    <!-- TANGGAL PELUNASAN BIAYA AWAL KULIAH -->
                    <div class="mt-8 mb-6 border-b border-gray-200 pb-2">
                        <h3 class="text-xl font-bold text-gray-800">TANGGAL PELUNASAN BIAYA AWAL KULIAH</h3>
                        <p class="text-sm text-gray-500">Silahkan tentukan jadwal anda untuk melakukan Pembayaran Biaya Awal Kuliah sebesar Rp. 600.000,-</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tanggal_bayar" class="block text-gray-700 text-sm font-medium mb-2">Akan Membayar Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal_bayar" name="tanggal_bayar" required class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                    </div>

                    <!-- CAPTCHA & SUBMIT -->
                    <div class="mt-8 mb-6">
                        <label for="captcha" class="block text-gray-700 text-sm font-medium mb-2">Keamanan: <?= $captcha_question ?> <span class="text-red-500">*</span></label>
                        <input type="number" id="captcha" name="captcha" required class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus-ring focus:border-blue-500 transition duration-200">
                    </div>

                    <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300">
                        Submit
                    </button>
                </form>
                <p class="text-center text-sm text-gray-500 mt-6">
                    Ingin jadi afiliator? <a href="register.php" class="font-medium text-blue-700 hover:text-blue-600">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
