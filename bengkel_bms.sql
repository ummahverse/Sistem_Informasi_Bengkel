-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Apr 2025 pada 10.50
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bengkel_bms`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg',
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama`, `no_wa`, `foto`, `alamat`, `created_at`, `updated_at`, `role`) VALUES
(3, 'admin', 'admin1', 'Admin Utama', NULL, '../uploads/1745159925_pexels-iriser-1366957 (1).jpg', NULL, '2025-04-20 08:08:02', '2025-04-20 20:48:34', 'admin');

-- --------------------------------------------------------

--
-- Struktur dari tabel `karyawan`
--

CREATE TABLE `karyawan` (
  `id_karyawan` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg',
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'karyawan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `karyawan`
--

INSERT INTO `karyawan` (`id_karyawan`, `username`, `password`, `nama`, `no_wa`, `foto`, `alamat`, `created_at`, `updated_at`, `role`) VALUES
(5, 'cikal', 'cikal', 'Haical ravinda rassya', NULL, '../uploads/1745172686_cikal.jpg', 'Bekasi', '2025-04-20 18:10:34', '2025-04-20 20:49:23', 'karyawan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`) VALUES
(12, 'Alat Kerja'),
(4, 'Ban'),
(3, 'Jasa'),
(8, 'Knalpot'),
(7, 'Mesin'),
(6, 'Oli'),
(9, 'Rem'),
(10, 'Spion');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laba_bersih`
--

CREATE TABLE `laba_bersih` (
  `id` int(11) NOT NULL,
  `bulan` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `laba_kotor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pengeluaran` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total dari semua pengeluaran',
  `laba_bersih` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laba_bersih`
--

INSERT INTO `laba_bersih` (`id`, `bulan`, `laba_kotor`, `pengeluaran`, `laba_bersih`, `created_at`, `updated_at`) VALUES
(4, '2025-04', 5473644.00, 2200000.00, 2973644.00, '2025-04-18 10:20:53', '2025-04-20 19:41:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `manajer`
--

CREATE TABLE `manajer` (
  `id_manajer` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg',
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'manajer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `manajer`
--

INSERT INTO `manajer` (`id_manajer`, `username`, `password`, `nama`, `no_wa`, `foto`, `alamat`, `created_at`, `updated_at`, `role`) VALUES
(1, 'andra aja', 'andra02102003', 'andraaaa', NULL, 'default.jpg', NULL, '2025-04-19 16:36:46', '2025-04-19 16:36:46', 'manajer'),
(2, 'manajer', 'manajer', 'manajer bengkel BMS', NULL, '../uploads/1745144959_pexels-iriser-1366957 (1).jpg', NULL, '2025-04-20 08:12:30', '2025-04-20 20:18:40', 'manajer');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL,
  `kategori` enum('Sewa Lahan','Token Listrik','Kasbon Karyawan','Uang Makan','Gaji Karyawan','Lainnya') NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal` date NOT NULL,
  `bulan` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID User yang input'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengeluaran`
--

INSERT INTO `pengeluaran` (`id`, `kategori`, `jumlah`, `keterangan`, `tanggal`, `bulan`, `created_at`, `created_by`) VALUES
(2, 'Gaji Karyawan', 500000.00, 'Untuk gaji karyawan', '2025-04-21', '2025-04', '2025-04-20 19:36:10', 0),
(3, 'Sewa Lahan', 300000.00, 'bayar sewa ruko\r\n', '2025-04-21', '2025-04', '2025-04-20 19:41:09', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran_detail`
--

CREATE TABLE `pengeluaran_detail` (
  `id` int(11) NOT NULL,
  `laba_bersih_id` int(11) NOT NULL,
  `pengeluaran_id` int(11) DEFAULT NULL,
  `nama_pengeluaran` varchar(255) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengeluaran_detail`
--

INSERT INTO `pengeluaran_detail` (`id`, `laba_bersih_id`, `pengeluaran_id`, `nama_pengeluaran`, `jumlah`, `created_at`) VALUES
(19, 4, NULL, 'Sewa tempat', 200000.00, '2025-04-20 08:04:34'),
(20, 4, NULL, 'Gaji Karyawan', 1200000.00, '2025-04-20 08:04:34'),
(22, 4, 2, 'Gaji Karyawan - Untuk gaji karyawan', 500000.00, '2025-04-20 19:36:10'),
(23, 4, 3, 'Sewa Lahan - bayar sewa ruko\r\n', 300000.00, '2025-04-20 19:41:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `piutang_cair`
--

CREATE TABLE `piutang_cair` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID User yang input'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `piutang_cair`
--

INSERT INTO `piutang_cair` (`id`, `transaksi_id`, `jumlah_bayar`, `tanggal_bayar`, `keterangan`, `created_at`, `created_by`) VALUES
(1, 74, 555000.00, '2025-04-21', 'Pelunasan manual', '2025-04-20 20:16:17', 0),
(6, -1, 100000.00, '2025-04-21', 'Pembayaran hutang produk: Ban Mahal - sasas', '2025-04-20 20:35:12', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `harga_beli` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `hutang_sparepart` enum('Hutang','Cash') DEFAULT 'Cash',
  `nominal_hutang` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama`, `harga_beli`, `harga_jual`, `stok`, `kategori_id`, `hutang_sparepart`, `nominal_hutang`) VALUES
(4, 'Oli Garden', 85000.00, 150000.00, 27, 6, 'Cash', 0.00),
(5, 'Oli lamborghini', 55000.00, 100000.00, 11, 6, 'Cash', 0.00),
(6, 'Oli Ferrari', 150000.00, 200000.00, 9, 6, 'Cash', 0.00),
(7, 'Ban Racing 80', 300000.00, 400000.00, 3, 4, 'Cash', 0.00),
(8, 'Ban Cacing', 90000.00, 250000.00, 38, 4, 'Cash', 0.00),
(9, 'Knalpot mberr', 200000.00, 350000.00, 3, 8, 'Cash', 0.00),
(10, 'Knalpot Best 3', 300000.00, 400000.00, 77, 8, 'Cash', 0.00),
(11, 'Mesin Mio', 500000.00, 900000.00, 9, 7, 'Cash', 0.00),
(12, 'Mesin Beat', 300000.00, 400000.00, 16, 7, 'Cash', 0.00),
(13, 'Rem Beat', 120000.00, 300000.00, 7, 9, 'Cash', 0.00),
(14, 'Rem Supra ', 95000.00, 120000.00, 17, 9, 'Cash', 0.00),
(15, 'Spion Aerox', 35000.00, 55000.00, 3, 10, 'Cash', 0.00),
(16, 'Spion Vespa Matic', 95000.00, 160000.00, 1, 10, 'Cash', 0.00),
(17, 'Service Motor', 0.00, 50000.00, 97, 3, 'Cash', 0.00),
(18, 'Oli Minyak', 98000.00, 150000.00, 20, 6, 'Hutang', 98000.00),
(19, 'Ban Mahal', 150000.00, 200000.00, 11, 4, 'Cash', 0.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `jumlah_bayar` decimal(10,2) DEFAULT 0.00,
  `hutang` decimal(10,2) DEFAULT 0.00,
  `status_hutang` tinyint(1) DEFAULT 0,
  `kembalian` decimal(10,2) DEFAULT 0.00,
  `kasir` varchar(25) NOT NULL,
  `nama_customer` varchar(100) NOT NULL,
  `no_whatsapp` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `plat_nomor_motor` varchar(20) NOT NULL,
  `metode_pembayaran` enum('Cash','Transfer') NOT NULL,
  `pendapatan` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `tanggal`, `total`, `jumlah_bayar`, `hutang`, `status_hutang`, `kembalian`, `kasir`, `nama_customer`, `no_whatsapp`, `alamat`, `plat_nomor_motor`, `metode_pembayaran`, `pendapatan`) VALUES
(-1, '2025-04-21', 0.00, 0.00, 0.00, 0, 0.00, 'system', 'Hutang Produk', '0', 'Sistem', 'Sistem', 'Cash', 0.00),
(8, '2025-04-17', 100000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Inas Hamidah', '0856-9410-1820', 'Jalan Jatikramat indah no 57 ', 'B 12 88 90', 'Cash', 0.00),
(9, '2025-04-17', 55000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Vidya', '08978290299', 'Halim Pondok Gede', 'B 12 99 88', 'Cash', 0.00),
(16, '2025-04-18', 750000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Andra', '082298702018', 'tes', 'B 1477 KRS', 'Cash', 0.00),
(17, '2025-04-18', 320000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Zico Marchelino', '028292992', 'Narogong', 'B12 33', 'Transfer', 0.00),
(18, '2025-04-18', 750000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Faris Naufal', '02829299292', 'Rawalumbu', 'B12 99', 'Transfer', 0.00),
(19, '2025-04-18', 160000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Meishel ', '028292903003', 'Bekasi', 'B12 KAL', 'Transfer', 0.00),
(20, '2025-04-18', 660000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Bona', '089292992', 'Jawa', 'B12 88', 'Transfer', 0.00),
(21, '2025-04-18', 400000.00, 0.00, 0.00, 0, 0.00, 'cikall', 'Buyung', '0812818729200', 'Bandung', 'B12 99 77 45', 'Transfer', 0.00),
(22, '2025-04-18', 1200000.00, 0.00, 0.00, 0, 0.00, 'andra aja', 'Andra terus', '08928282822', 'tes 1213', 'B 1477 KRS121', 'Transfer', 0.00),
(23, '2025-04-19', 750000.00, 0.00, 0.00, 0, 0.00, 'andra aja', 'Andra hari sabtu', '+6282298702018', 'Jln raya hankam Blok B 10', 'B 3679 KKU', 'Cash', 0.00),
(24, '2025-04-19', 400000.00, 0.00, 0.00, 0, 0.00, 'karyawan', 'Andra', '+628928282822', 'tes', 'B 1477 KRS', 'Cash', 0.00),
(25, '2025-04-19', 400000.00, 0.00, 0.00, 0, 0.00, 'karyawan', 'Andra', '+628928282822', 'tes', 'B 1477 KRS', 'Cash', 0.00),
(26, '2025-04-19', 350000.00, 0.00, 0.00, 0, 0.00, 'karyawan', 'Andra', '+628928282822', 'tes', 'B 1477 KRS', 'Cash', 0.00),
(27, '2025-04-19', 233333.00, 0.00, 0.00, 0, 0.00, 'karyawan', 'Andra lagi', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(28, '2025-04-20', 300000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 3679 KKU', 'Cash', 0.00),
(29, '2025-04-20', 250000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 3679 KKU', 'Cash', 0.00),
(30, '2025-04-20', 105000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam B10', 'B 1477 KRS', 'Cash', 0.00),
(31, '2025-04-20', 55000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam B10', 'B 1477 KRS', 'Cash', 0.00),
(32, '2025-04-20', 400000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 6283 RSX', 'Cash', 0.00),
(33, '2025-04-20', 400000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu Terakhir', '+62829702018', 'Jln raya hankam B10', 'B 1477 KRS', 'Cash', 0.00),
(34, '2025-04-20', 400000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu Terakhir', '+62829702018', 'Jln raya hankam B10', 'B 1477 KRS', 'Cash', 0.00),
(35, '2025-04-20', 400000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Hari Minggu Terakhir Banget', '+62829702018', 'Jln raya hankam B10', 'B 6283 RSX', 'Cash', 0.00),
(36, '2025-04-20', 60000.00, 250000.00, -190000.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 3679 KKU', 'Cash', 0.00),
(37, '2025-04-20', 500000.00, 300000.00, 200000.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 3679 KKU', 'Cash', 0.00),
(38, '2025-04-20', 200000.00, 200000.00, 0.00, 0, 0.00, 'andra', 'Pak Stevi', '+6282298702018', 'Jatiasih Cikunir', 'B 1792 KRS', 'Cash', 0.00),
(39, '2025-04-20', 120000.00, 200000.00, -80000.00, 0, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam B10', 'B 6283 RSX', 'Cash', 0.00),
(40, '2025-04-20', 150000.00, 150000.00, 0.00, 0, 0.00, 'andra', 'Pak Stevi', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(41, '2025-04-20', 30000.00, 50000.00, -20000.00, 0, 0.00, 'andra', 'Pak Stevi Aja', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(42, '2025-04-20', 200000.00, 28000.00, 172000.00, 1, 0.00, 'andra', 'Pak Stevi', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(43, '2025-04-20', 250000.00, 200000.00, 50000.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'tes', 'B 1477 KRS', 'Cash', 0.00),
(44, '2025-04-20', 200000.00, 388888.00, -188888.00, 0, 0.00, 'andra', 'Pak Stevi', '+62829702018', 'tes', 'B 1477 KRS', 'Cash', 0.00),
(45, '2025-04-20', 200000.00, 200000.00, 0.00, 0, 0.00, 'andra', 'Andra', '+62829702018', 'Jln raya hankam', 'B 6283 RSX', 'Cash', 0.00),
(46, '2025-04-20', 200000.00, 200000.00, 0.00, 0, 0.00, 'andra', 'Andra', '+62829702018', 'Jln raya hankam', 'B 6283 RSX', 'Cash', 0.00),
(47, '2025-04-20', 200000.00, 200000.00, 0.00, 0, 0.00, 'andra', 'Andra', '+62829702018', 'Jln raya hankam', 'B 6283 RSX', 'Cash', 0.00),
(48, '2025-04-20', 200000.00, 200000.00, 0.00, 0, 0.00, 'andra', 'Andra', '+62829702018', 'Jln raya hankam', 'B 6283 RSX', 'Cash', 0.00),
(49, '2025-04-20', 200000.00, 232323.00, -32323.00, 0, 0.00, 'andra', 'Andra', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(50, '2025-04-20', 898989.00, 676767.00, 222222.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(51, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(52, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(53, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(54, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(55, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(56, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(57, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(58, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(59, '2025-04-20', 232323.00, 22222.00, 210101.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(60, '2025-04-20', 482332.00, 455555.00, 26777.00, 1, 0.00, 'andra', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(61, '2025-04-20', 275000.00, 200000.00, 75000.00, 1, 0.00, 'andra', 'Bu Ana Kurniawati', '+6282298702018', 'Jln Raya Jatiasih , Pondok Gede', 'B 1477 KRX', 'Cash', 0.00),
(63, '2025-04-20', 223333.00, 200000.00, 23333.00, 1, 0.00, 'haical', 'Andra', '+62829702018', 'tes', 'B 6283 RSX', 'Transfer', 0.00),
(64, '2025-04-20', 40000.00, 35000.00, 5000.00, 1, 0.00, 'haical', 'Andra', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(65, '2025-04-20', 760000.00, 800000.00, -40000.00, 0, 0.00, 'haical', 'Andra', '+628229702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(66, '2025-04-20', 370000.00, 400000.00, 0.00, 0, 30000.00, 'haical', 'Andra', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(67, '2025-04-20', 311106.00, 311106.00, 0.00, 0, 0.00, 'haical', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(68, '2025-04-20', 350000.00, 400000.00, 0.00, 0, 50000.00, 'haical', 'Hari Minggu', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(69, '2025-04-20', 200000.00, 150000.00, 50000.00, 1, 0.00, 'haical', 'Hari Minggu', '+628229702018', 'tes', 'B 1477 KRS', 'Transfer', 0.00),
(70, '2025-04-20', 150000.00, 200000.00, 0.00, 0, 50000.00, 'haical', 'Andra', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 0.00),
(71, '2025-04-20', 400000.00, 350000.00, 50000.00, 1, 0.00, 'haical', 'Andra', '+628229702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(72, '2025-04-20', 250000.00, 150000.00, 100000.00, 1, 0.00, 'haical', 'Andra', '+628229702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(73, '2025-04-20', 50000.00, 200000.00, 0.00, 0, 150000.00, 'haical', 'Andra', '+62829702018', 'tes', 'B 1477 KRS', 'Transfer', 0.00),
(74, '2025-04-20', 555000.00, 0.00, 0.00, 0, 0.00, 'andra', 'Andra', '+628229702018', 'Jln raya hankam', 'B 1477 KRS', 'Transfer', 0.00),
(75, '2025-04-20', 275000.00, 300000.00, 0.00, 0, 25000.00, 'andra', 'Andra', '+62829702018', 'Jln raya hankam', 'B 1477 KRS', 'Cash', 275000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `nama_produk_manual` varchar(255) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi_detail`
--

INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `produk_id`, `nama_produk_manual`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(5, 8, 5, '', 1, 100000.00, 100000.00),
(6, 9, 15, '', 1, 55000.00, 55000.00),
(15, 16, 10, '', 1, 400000.00, 400000.00),
(16, 16, 9, '', 1, 350000.00, 350000.00),
(17, 17, 16, '', 2, 160000.00, 320000.00),
(18, 18, 8, '', 3, 250000.00, 750000.00),
(19, 19, 16, '', 1, 160000.00, 160000.00),
(20, 20, 16, '', 1, 160000.00, 160000.00),
(21, 20, 5, '', 1, 100000.00, 100000.00),
(22, 20, 7, '', 1, 400000.00, 400000.00),
(23, 21, 12, '', 1, 400000.00, 400000.00),
(25, 22, 11, '', 1, 900000.00, 900000.00),
(26, 22, 6, '', 1, 200000.00, 200000.00),
(27, 23, 8, '', 2, 250000.00, 500000.00),
(29, 23, 4, '', 1, 150000.00, 150000.00),
(30, 24, 7, '', 1, 400000.00, 400000.00),
(31, 25, 7, '', 1, 400000.00, 400000.00),
(32, 26, 9, '', 1, 350000.00, 350000.00),
(33, 28, 8, '', 1, 250000.00, 250000.00),
(34, 28, 17, '', 1, 50000.00, 50000.00),
(35, 29, 8, '', 1, 250000.00, 250000.00),
(36, 30, 15, '', 1, 55000.00, 55000.00),
(37, 31, 15, '', 1, 55000.00, 55000.00),
(38, 32, 7, '', 1, 400000.00, 400000.00),
(39, 33, 7, '', 1, 400000.00, 400000.00),
(40, 34, 7, '', 1, 400000.00, 400000.00),
(41, 35, 7, '', 1, 400000.00, 400000.00),
(42, 36, 13, '', 1, 60000.00, 60000.00),
(43, 37, 5, '', 1, 100000.00, 100000.00),
(44, 37, 12, '', 1, 400000.00, 400000.00),
(45, 38, 6, '', 1, 200000.00, 200000.00),
(46, 39, 5, '', 1, 100000.00, 100000.00),
(47, 39, 6, '', 1, 20000.00, 20000.00),
(48, 40, 4, '', 1, 150000.00, 150000.00),
(49, 41, 15, '', 1, 30000.00, 30000.00),
(50, 42, 6, '', 1, 200000.00, 200000.00),
(51, 43, 8, '', 1, 250000.00, 250000.00),
(52, 44, 6, '', 1, 200000.00, 200000.00),
(53, 48, 6, '', 1, 200000.00, 200000.00),
(54, 49, 6, '', 1, 200000.00, 200000.00),
(55, 50, 17, NULL, 1, 898989.00, 898989.00),
(61, 59, NULL, 'asadadad', 1, 232323.00, 232323.00),
(62, 60, 8, NULL, 1, 250000.00, 250000.00),
(63, 60, NULL, 'Servis', 1, 232332.00, 232332.00),
(64, 61, 8, NULL, 1, 250000.00, 250000.00),
(65, 61, NULL, 'Servis Motor', 1, 25000.00, 25000.00),
(67, 63, 6, NULL, 1, 200000.00, 200000.00),
(68, 63, NULL, 'Servis motor', 1, 23333.00, 23333.00),
(69, 64, NULL, 'Service', 2, 20000.00, 40000.00),
(70, 65, NULL, 'Servis', 1, 60000.00, 60000.00),
(71, 65, 9, NULL, 2, 350000.00, 700000.00),
(72, 66, 9, NULL, 1, 350000.00, 350000.00),
(73, 66, NULL, 'Servis', 1, 20000.00, 20000.00),
(74, 67, NULL, 'Service', 1, 311106.00, 311106.00),
(75, 68, 9, NULL, 1, 350000.00, 350000.00),
(76, 69, 6, NULL, 1, 200000.00, 200000.00),
(77, 70, 4, NULL, 1, 150000.00, 150000.00),
(78, 71, 10, NULL, 1, 400000.00, 400000.00),
(79, 72, 8, NULL, 1, 250000.00, 250000.00),
(80, 73, 17, NULL, 1, 50000.00, 50000.00),
(81, 74, 6, NULL, 1, 200000.00, 200000.00),
(82, 74, 13, NULL, 1, 300000.00, 300000.00),
(83, 74, 15, NULL, 1, 55000.00, 55000.00),
(84, 75, NULL, 'Servis', 1, 25000.00, 25000.00),
(85, 75, 8, NULL, 1, 250000.00, 250000.00);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id_karyawan`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indeks untuk tabel `laba_bersih`
--
ALTER TABLE `laba_bersih`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bulan_unique` (`bulan`);

--
-- Indeks untuk tabel `manajer`
--
ALTER TABLE `manajer`
  ADD PRIMARY KEY (`id_manajer`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bulan` (`bulan`),
  ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indeks untuk tabel `pengeluaran_detail`
--
ALTER TABLE `pengeluaran_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengeluaran_laba_bersih` (`laba_bersih_id`),
  ADD KEY `fk_pengeluaran` (`pengeluaran_id`);

--
-- Indeks untuk tabel `piutang_cair`
--
ALTER TABLE `piutang_cair`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transaksi` (`transaksi_id`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_hutang` (`status_hutang`);

--
-- Indeks untuk tabel `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id_karyawan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `laba_bersih`
--
ALTER TABLE `laba_bersih`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `manajer`
--
ALTER TABLE `manajer`
  MODIFY `id_manajer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran_detail`
--
ALTER TABLE `pengeluaran_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `piutang_cair`
--
ALTER TABLE `piutang_cair`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT untuk tabel `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pengeluaran_detail`
--
ALTER TABLE `pengeluaran_detail`
  ADD CONSTRAINT `fk_pengeluaran` FOREIGN KEY (`pengeluaran_id`) REFERENCES `pengeluaran` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengeluaran_laba_bersih` FOREIGN KEY (`laba_bersih_id`) REFERENCES `laba_bersih` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `piutang_cair`
--
ALTER TABLE `piutang_cair`
  ADD CONSTRAINT `fk_transaksi` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `transaksi_detail_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_detail_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
