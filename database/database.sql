-- Active: 1758631367884@@127.0.0.1@3306@monitoring_db
-- Active: 1758631367884@@127.0.0.1@3306
-- Active: 1758630990965@@127.0.0.1@3306@mysql
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 02:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

--
-- Database: `monitoring_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_master`
--

CREATE TABLE `kegiatan_master` (
  `kegiatan_id` int(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `waktu_standar` time NOT NULL,
  `urutan` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan_master`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `log_kegiatan`
--

CREATE TABLE `log_kegiatan` (
  `log_id` int(11) NOT NULL,
  `kegiatan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Belum','Selesai','Terlambat','Tidak Dilaksanakan') NOT NULL DEFAULT 'Belum',
  `catatan` text DEFAULT NULL,
  `timestamp_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  ADD PRIMARY KEY (`kegiatan_id`);

--
-- Indexes for table `log_kegiatan`
--
ALTER TABLE `log_kegiatan`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `unique_activity_per_day` (`kegiatan_id`,`tanggal`),
  ADD KEY `tanggal` (`tanggal`);

--
-- AUTO_INCREMENT for dumped tables
--

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
-- Constraints for dumped tables
--

--
-- Constraints for table `log_kegiatan`
--
ALTER TABLE `log_kegiatan`
  ADD CONSTRAINT `log_kegiatan_ibfk_1` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_master` (`kegiatan_id`) ON DELETE CASCADE;
COMMIT;

ALTER TABLE `log_kegiatan`
MODIFY `status` enum('Belum','Selesai','Terlambat','Terlewat','Libur') NOT NULL DEFAULT 'Belum',
DROP COLUMN `catatan`;

ALTER TABLE `log_kegiatan`
MODIFY `status` enum('Belum','Selesai','Terlambat','Lewat','Libur') NOT NULL DEFAULT 'Belum';