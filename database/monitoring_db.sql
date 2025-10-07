-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 07:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `monitoring_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `alat_id` int(11) NOT NULL,
  `nama_alat` varchar(255) NOT NULL,
  `status_alat` enum('Baik','Perbaikan','Rusak Total') NOT NULL DEFAULT 'Baik',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`alat_id`, `nama_alat`, `status_alat`, `keterangan`) VALUES
(1, 'Mesin Produksi A', 'Baik', NULL),
(2, 'Mesin Produksi B', 'Baik', NULL),
(3, 'Sistem CCTV', 'Baik', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_master`
--

CREATE TABLE `kegiatan_master` (
  `kegiatan_id` int(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `waktu_standar` time NOT NULL,
  `urutan` int(11) DEFAULT 1,
  `alat_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan_master`
--

INSERT INTO `kegiatan_master` (`kegiatan_id`, `nama_kegiatan`, `waktu_standar`, `urutan`, `alat_id`) VALUES
(1, 'Briefing Pagi Tim Operasional', '08:00:00', 1, NULL),
(2, 'Pengecekan Kebersihan Area Kerja', '08:30:00', 2, NULL),
(3, 'Inspeksi Mesin Produksi A', '09:00:00', 3, 1),
(4, 'Inspeksi Mesin Produksi B', '09:30:00', 4, 2),
(5, 'Laporan Stok Bahan Baku Awal Hari', '10:00:00', 5, NULL),
(6, 'Pengarsipan Dokumen Harian Sesi Pagi', '11:30:00', 6, NULL),
(7, 'Pengecekan Sistem Keamanan (CCTV)', '13:00:00', 7, 3),
(8, 'Laporan Serah Terima Shift', '16:00:00', 8, NULL),
(9, 'Pembersihan dan Perapihan Area Kerja Akhir Hari', '16:30:00', 9, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `log_kegiatan`
--

CREATE TABLE `log_kegiatan` (
  `log_id` int(11) NOT NULL,
  `kegiatan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Belum','Selesai','Terlambat','Lewat','Libur','Rusak','Lainnya') NOT NULL,
  `timestamp_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `komentar` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_alat_rusak`
--

CREATE TABLE `notifikasi_alat_rusak` (
  `notifikasi_id` int(11) NOT NULL,
  `alat_id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `status_notifikasi` enum('Aktif','Selesai') NOT NULL DEFAULT 'Aktif',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi_alat_rusak`
--

INSERT INTO `notifikasi_alat_rusak` (`notifikasi_id`, `alat_id`, `pesan`, `status_notifikasi`, `tanggal_dibuat`) VALUES
(1, 1, 'Peringatan: Alat \'Mesin Produksi A\' telah dilaporkan rusak sebanyak 3 kali. Mohon segera diperiksa.', 'Selesai', '2025-10-07 16:47:44'),
(2, 1, 'Peringatan: Alat \'Mesin Produksi A\' telah dilaporkan rusak sebanyak 3 kali. Mohon segera diperiksa dan konfirmasi statusnya.', 'Selesai', '2025-10-07 17:47:04'),
(3, 1, 'Peringatan: Alat \'Mesin Produksi A\' telah dilaporkan rusak sebanyak 3 kali. Mohon segera diperiksa dan konfirmasi statusnya.', 'Selesai', '2025-10-07 17:47:34'),
(4, 1, 'Peringatan: Alat \'Mesin Produksi A\' telah dilaporkan rusak sebanyak 3 kali. Mohon segera diperiksa dan konfirmasi statusnya.', 'Selesai', '2025-10-07 17:52:23'),
(5, 1, 'Peringatan: Alat \'Mesin Produksi A\' telah dilaporkan rusak sebanyak 3 kali. Mohon segera diperiksa dan konfirmasi statusnya.', 'Selesai', '2025-10-07 17:52:34'),
(6, 1, 'Peringatan: Alat \'Mesin Produksi A\' telah dilaporkan rusak sebanyak 3 kali. Mohon segera diperiksa dan konfirmasi statusnya.', 'Selesai', '2025-10-07 17:52:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`alat_id`);

--
-- Indexes for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  ADD PRIMARY KEY (`kegiatan_id`),
  ADD KEY `alat_id` (`alat_id`);

--
-- Indexes for table `log_kegiatan`
--
ALTER TABLE `log_kegiatan`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `unique_activity_per_day` (`kegiatan_id`,`tanggal`),
  ADD KEY `tanggal` (`tanggal`);

--
-- Indexes for table `notifikasi_alat_rusak`
--
ALTER TABLE `notifikasi_alat_rusak`
  ADD PRIMARY KEY (`notifikasi_id`),
  ADD KEY `alat_id` (`alat_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `alat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  MODIFY `kegiatan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `log_kegiatan`
--
ALTER TABLE `log_kegiatan`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifikasi_alat_rusak`
--
ALTER TABLE `notifikasi_alat_rusak`
  MODIFY `notifikasi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  ADD CONSTRAINT `kegiatan_master_ibfk_1` FOREIGN KEY (`alat_id`) REFERENCES `alat` (`alat_id`) ON DELETE SET NULL;

--
-- Constraints for table `log_kegiatan`
--
ALTER TABLE `log_kegiatan`
  ADD CONSTRAINT `log_kegiatan_ibfk_1` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_master` (`kegiatan_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi_alat_rusak`
--
ALTER TABLE `notifikasi_alat_rusak`
  ADD CONSTRAINT `notifikasi_alat_rusak_ibfk_1` FOREIGN KEY (`alat_id`) REFERENCES `alat` (`alat_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
