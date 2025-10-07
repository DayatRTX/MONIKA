-- Hapus dan buat ulang tabel untuk memastikan kebersihan
DROP TABLE IF EXISTS `log_kegiatan`;
DROP TABLE IF EXISTS `kegiatan_master`;

-- Membuat tabel kegiatan_master
CREATE TABLE `kegiatan_master` (
  `kegiatan_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` varchar(255) NOT NULL,
  `waktu_standar` time NOT NULL,
  `urutan` int(11) DEFAULT 1,
  PRIMARY KEY (`kegiatan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mengisi data awal
INSERT INTO `kegiatan_master` (`kegiatan_id`, `nama_kegiatan`, `waktu_standar`, `urutan`) VALUES
(1, 'Briefing Pagi Tim Operasional', '08:00:00', 1),
(2, 'Pengecekan Kebersihan Area Kerja', '08:30:00', 2),
(3, 'Inspeksi Mesin Produksi A', '09:00:00', 3),
(4, 'Inspeksi Mesin Produksi B', '09:30:00', 4),
(5, 'Laporan Stok Bahan Baku Awal Hari', '10:00:00', 5),
(6, 'Pengarsipan Dokumen Harian Sesi Pagi', '11:30:00', 6),
(7, 'Pengecekan Sistem Keamanan (CCTV)', '13:00:00', 7),
(8, 'Laporan Serah Terima Shift', '16:00:00', 8),
(9, 'Pembersihan dan Perapihan Area Kerja Akhir Hari', '16:30:00', 9);

-- Membuat tabel log_kegiatan
CREATE TABLE `log_kegiatan` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `kegiatan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Belum','Selesai','Terlambat','Lewat','Libur') NOT NULL,
  `timestamp_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `unique_activity_per_day` (`kegiatan_id`,`tanggal`),
  KEY `tanggal` (`tanggal`),
  CONSTRAINT `log_kegiatan_ibfk_1` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_master` (`kegiatan_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;