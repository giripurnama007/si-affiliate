-- Tabel untuk user (afiliator dan admin)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `unique_code` varchar(50) NOT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `student_status` enum('mahasiswa','non_mahasiswa') NOT NULL DEFAULT 'non_mahasiswa',
  `nim` varchar(50) DEFAULT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `custom_share_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_code` (`unique_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menambahkan user admin default
INSERT INTO `users` (`name`, `email`, `password`, `unique_code`, `status`, `role`) VALUES
('Admin', 'admin@example.com', '$2yIXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN', 'active', 'admin');
-- Password untuk admin@example.com adalah 'password'

-- Tabel untuk melacak klik link afiliasi
CREATE TABLE `clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `affiliate_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `affiliate_id` (`affiliate_id`),
  CONSTRAINT `clicks_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk pendaftar yang berhasil dikonversi
CREATE TABLE `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `affiliate_id` int(11) DEFAULT NULL,
  `student_name` varchar(255) NOT NULL,
  `nama_panggilan` varchar(100) DEFAULT NULL,
  `student_email` varchar(255) NOT NULL,
  `kewarganegaraan` varchar(50) DEFAULT NULL,
  `student_phone` varchar(20) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `nik` varchar(20) NOT NULL,
  `status_perkawinan` varchar(50) DEFAULT NULL,
  `asal_sekolah` varchar(100) NOT NULL,
  `alamat_sekolah` text DEFAULT NULL,
  `nisn` varchar(50) DEFAULT NULL,
  `nilai_rata_rata` varchar(10) DEFAULT NULL,
  `jurusan_sekolah` varchar(100) DEFAULT NULL,
  `program_studi` varchar(100) NOT NULL,
  `lulusan` varchar(100) DEFAULT NULL,
  `waktu_kuliah` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tahun_lulus` varchar(4) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `bagian_pekerjaan` varchar(100) DEFAULT NULL,
  `instansi_pekerjaan` varchar(100) DEFAULT NULL,
  `alamat_perusahaan` text DEFAULT NULL,
  `bahasa_asing` varchar(100) DEFAULT NULL,
  `kemampuan_membaca` enum('Baik','Sedang','Buruk') DEFAULT NULL,
  `kemampuan_menulis` enum('Baik','Sedang','Buruk') DEFAULT NULL,
  `kemampuan_berbicara` enum('Baik','Sedang','Buruk') DEFAULT NULL,
  `sumber_biaya` varchar(100) DEFAULT NULL,
  `nama_orang_tua` varchar(255) DEFAULT NULL,
  `pekerjaan_orang_tua` varchar(100) DEFAULT NULL,
  `alamat_orang_tua` text DEFAULT NULL,
  `info_dari` varchar(100) DEFAULT NULL,
  `tanggal_wawancara` date DEFAULT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `payment_status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `affiliate_id` (`affiliate_id`),
  CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk pengaturan aplikasi
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('referral_commission', '50000');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('share_message_template', 'Hai! Yuk daftar jadi mahasiswa baru melalui link saya dan dapatkan keuntungannya. Klik di sini: {{link}}');

-- Tabel untuk riwayat komisi
CREATE TABLE `commission_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commission_amount` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `commission_history` (`commission_amount`) VALUES (50000);
