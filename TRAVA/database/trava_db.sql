-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2026 at 02:20 AM
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
-- Database: `trava_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'invite',
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `message` varchar(255) DEFAULT '',
  `link_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `from_user_id`, `trip_id`, `type`, `is_read`, `created_at`, `message`, `link_url`) VALUES
(3, 8, 8, NULL, 'wishlist', 1, '2026-05-22 22:19:50', 'Kamu menambahkan Keraton kanoman ke wishlist.', NULL),
(4, 8, 8, NULL, 'akun', 1, '2026-05-23 01:25:02', 'Profil berhasil diperbarui. Nama, Bio, dan Foto Profil telah diubah.', NULL),
(5, 8, 8, NULL, 'akun', 1, '2026-05-23 01:26:14', 'Profil berhasil diperbarui. Nama, Bio, dan Foto Profil telah diubah.', NULL),
(31, 1, 1, 3, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"crb escape\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=3'),
(32, 1, 1, 6, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"y\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=6'),
(33, 1, 1, 7, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"ya\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=7'),
(34, 1, 1, 8, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"hhhhhhhh\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=8'),
(35, 1, 1, 9, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"ya\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=9'),
(36, 7, 7, 14, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"crb hiling\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=14'),
(37, 8, 8, 17, 'wacana', 0, '2026-05-23 11:47:59', 'Trip \"crb hiling\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=17'),
(46, 2, 2, NULL, 'akun', 0, '2026-05-23 11:50:32', 'Nama dan email berhasil diperbarui.', 'profil.php'),
(47, 2, 2, NULL, 'wishlist', 0, '2026-05-23 11:50:55', 'Kamu menambahkan Keraton kanoman ke wishlist.', NULL),
(48, 2, 2, NULL, 'review', 0, '2026-05-23 11:51:05', 'Review-mu di Keraton kanoman berhasil dikirim. Rating: 5 bintang.', NULL),
(49, 2, 2, 24, 'trip', 0, '2026-05-23 11:52:16', 'Trip \"HHH\" berhasil dibuat. Tanggal berangkat: 08 Aug 8888.', 'trip_group.php?id=24'),
(50, 2, 2, 24, 'wacana', 0, '2026-05-23 11:52:30', 'Trip \"HHH\" dibatalkan dan masuk ke daftar wacana kamu 😅', 'trip_detail.php?id=24'),
(51, 8, 8, 25, 'trip', 0, '2026-05-23 11:54:32', 'Trip \"yasssssssss\" berhasil dibuat. Tanggal berangkat: 24 Jun 2026.', 'trip_group.php?id=25'),
(52, 2, 8, 25, 'invite', 0, '2026-05-23 11:54:49', 'jefrii mengundangmu ikut trip: yasssssssss', 'trip_group.php?id=25'),
(53, 7, 8, 25, 'invite', 0, '2026-05-23 11:55:07', 'jefrii mengundangmu ikut trip: yasssssssss', 'trip_group.php?id=25'),
(54, 2, 8, 25, 'chat_personal', 0, '2026-05-23 11:55:17', 'jefrii: hai bruhhh', 'trip_group.php?id=25&tab=personal'),
(55, 2, 8, 25, 'chat_group', 0, '2026-05-23 11:55:28', 'jefrii di grup yasssssssss: jadi gaaa', 'trip_group.php?id=25&tab=chat'),
(56, 7, 8, 25, 'chat_group', 0, '2026-05-23 11:55:28', 'jefrii di grup yasssssssss: jadi gaaa', 'trip_group.php?id=25&tab=chat'),
(57, 2, 8, NULL, 'review', 1, '2026-05-23 11:56:00', 'Review-mu di Keraton kanoman mendapat 1 like. Disukai oleh: jefrii.', 'detail.php?id=30'),
(58, 2, 8, NULL, 'review', 0, '2026-05-23 11:56:05', 'jefrii membalas review-mu di Keraton kanoman: APASIH', 'detail.php?id=30'),
(59, 7, 2, 25, 'chat_group', 0, '2026-05-23 12:10:35', 'faija di grup yasssssssss: bebas si', 'trip_group.php?id=25&tab=chat'),
(60, 8, 2, 25, 'chat_group', 0, '2026-05-23 12:10:35', 'faija di grup yasssssssss: bebas si', 'trip_group.php?id=25&tab=chat'),
(61, 7, 2, 25, 'chat_group', 0, '2026-05-24 02:17:18', 'faija di grup yasssssssss: y', 'trip_group.php?id=25&tab=chat'),
(62, 8, 2, 25, 'chat_group', 0, '2026-05-24 02:17:18', 'faija di grup yasssssssss: y', 'trip_group.php?id=25&tab=chat'),
(63, 2, 8, NULL, 'review', 0, '2026-05-28 00:50:12', 'jefrii membalas review-mu di Keraton kanoman: HAH', 'detail.php?id=30'),
(64, 2, 8, NULL, 'review', 0, '2026-05-28 00:50:41', 'Review-mu di Keraton kanoman mendapat 1 like. Disukai oleh: jefrii.', 'detail.php?id=30'),
(65, 8, 8, 26, 'trip', 0, '2026-05-28 00:51:25', 'Trip \"EEEEEEEEEEEEEEEEE\" berhasil dibuat. Tanggal berangkat: 01 Jan 2009.', 'trip_group.php?id=26'),
(66, 2, 8, 25, 'chat_group', 0, '2026-05-28 00:52:34', 'jefrii di grup yasssssssss: OIIII', 'trip_group.php?id=25&tab=chat'),
(67, 7, 8, 25, 'chat_group', 0, '2026-05-28 00:52:34', 'jefrii di grup yasssssssss: OIIII', 'trip_group.php?id=25&tab=chat'),
(68, 7, 8, 25, 'chat_personal', 0, '2026-05-28 00:52:49', 'jefrii: HIII', 'trip_group.php?id=25&tab=personal'),
(69, 7, 8, 25, 'chat_personal', 0, '2026-05-28 00:52:55', 'jefrii: HIII', 'trip_group.php?id=25&tab=personal'),
(70, 2, 8, 25, 'chat_personal', 0, '2026-05-28 00:53:00', 'jefrii: HIII', 'trip_group.php?id=25&tab=personal'),
(71, 2, 8, 25, 'chat_personal', 0, '2026-05-28 00:53:05', 'jefrii: HIIII', 'trip_group.php?id=25&tab=personal'),
(72, 2, 8, 25, 'invite', 0, '2026-05-28 00:55:08', 'jefrii mengundangmu ikut trip: yasssssssss', 'trip_group.php?id=25'),
(73, 2, 8, NULL, 'review', 0, '2026-05-28 00:55:28', 'Review-mu di Pantai Kejawanan mendapat 1 like. Disukai oleh: jefrii.', 'detail.php?id=2'),
(74, 2, 8, NULL, 'review', 0, '2026-05-28 00:55:29', 'jefrii membalas review-mu di Pantai Kejawanan: YAW', 'detail.php?id=2'),
(75, 8, 8, NULL, 'review', 0, '2026-05-28 02:27:33', 'Review-mu di Keraton kanoman berhasil dikirim. Rating: 5 bintang.', NULL),
(76, 8, 0, NULL, 'wishlist', 0, '2026-05-28 02:27:33', '🔥 Destinasi wishlist-mu sedang populer! Keraton kanoman dikunjungi 10 kali minggu ini. Jangan sampai kehabisan tempat!', 'detail.php?id=30'),
(77, 2, 0, NULL, 'wishlist', 0, '2026-05-28 02:27:33', '🔥 Destinasi wishlist-mu sedang populer! Keraton kanoman dikunjungi 10 kali minggu ini. Jangan sampai kehabisan tempat!', 'detail.php?id=30'),
(78, 10, 10, NULL, 'review', 1, '2026-05-28 11:36:53', 'Review-mu di Keraton Kasepuhan berhasil dikirim. Rating: 5 bintang.', NULL),
(79, 10, 10, NULL, 'wishlist', 1, '2026-05-28 11:36:58', 'Kamu menambahkan Keraton Kasepuhan ke wishlist.', NULL),
(80, 10, 0, NULL, 'wishlist', 1, '2026-05-28 11:37:01', '🔥 Destinasi wishlist-mu sedang populer! Keraton Kasepuhan dikunjungi 18 kali minggu ini. Jangan sampai kehabisan tempat!', 'detail.php?id=29'),
(81, 2, 10, NULL, 'review', 0, '2026-05-28 11:42:36', 'Review-mu di Keraton Kasepuhan mendapat 2 like. Disukai oleh: cipuy, jefrii.', 'detail.php?id=29'),
(82, 2, 10, NULL, 'review', 0, '2026-05-28 11:42:41', 'cipuy membalas review-mu di Keraton Kasepuhan: yu', 'detail.php?id=29'),
(83, 10, 10, 27, 'trip', 0, '2026-05-28 11:43:57', 'Trip \"nn\" berhasil dibuat. Tanggal berangkat: 08 Aug 8888.', 'trip_group.php?id=27'),
(84, 10, 10, NULL, 'akun', 1, '2026-05-28 11:45:49', 'Nama dan foto profil berhasil diperbarui.', 'profil.php'),
(85, 2, 2, NULL, 'review', 0, '2026-05-28 22:26:55', 'Review-mu di Telaga Biru berhasil dikirim. Rating: 5 bintang.', NULL),
(86, 2, 2, NULL, 'review', 0, '2026-05-28 22:27:55', 'Review-mu di Telaga Biru berhasil dikirim. Rating: 5 bintang.', NULL),
(87, 2, 2, NULL, 'akun', 0, '2026-05-28 22:36:07', 'Selamat! Level Traveller kamu naik dari Explorer ke Traveler. Terus jelajahi lebih banyak destinasi!', 'profil.php'),
(88, 12, 12, NULL, 'review', 0, '2026-05-28 23:03:05', 'Review-mu di Talaga Langit berhasil dikirim. Rating: 5 bintang.', NULL),
(89, 12, 12, NULL, 'review', 0, '2026-05-28 23:03:39', 'Review-mu di Bukit Gronggong berhasil dikirim. Rating: 5 bintang.', NULL),
(90, 2, 0, NULL, 'wishlist', 0, '2026-05-28 23:04:25', '🔥 Destinasi wishlist-mu sedang populer! Keraton kanoman dikunjungi 11 kali minggu ini. Jangan sampai kehabisan tempat!', 'detail.php?id=30'),
(91, 13, 13, NULL, 'review', 0, '2026-05-28 23:04:43', 'Review-mu di Keraton kanoman berhasil dikirim. Rating: 4 bintang.', NULL),
(92, 13, 13, NULL, 'review', 0, '2026-05-28 23:05:34', 'Review-mu di Goa Sunyaragi  berhasil dikirim. Rating: 5 bintang.', NULL),
(93, 14, 14, NULL, 'review', 0, '2026-05-28 23:06:56', 'Review-mu di Batu Lawang berhasil dikirim. Rating: 5 bintang.', NULL),
(94, 15, 15, NULL, 'review', 0, '2026-05-28 23:08:21', 'Review-mu di Plangon berhasil dikirim. Rating: 4 bintang.', NULL),
(95, 16, 16, NULL, 'review', 0, '2026-05-28 23:09:37', 'Review-mu di Keraton Kasepuhan berhasil dikirim. Rating: 5 bintang.', NULL),
(96, 17, 17, NULL, 'review', 0, '2026-05-28 23:11:33', 'Review-mu di Keraton Kasepuhan berhasil dikirim. Rating: 5 bintang.', NULL),
(97, 1, 0, NULL, 'wishlist', 0, '2026-05-28 23:12:56', '🔥 Destinasi wishlist-mu sedang populer! Pantai Kejawanan dikunjungi 17 kali minggu ini. Jangan sampai kehabisan tempat!', 'detail.php?id=2'),
(98, 18, 18, NULL, 'review', 0, '2026-05-28 23:13:28', 'Review-mu di Pantai Kejawanan berhasil dikirim. Rating: 4 bintang.', NULL),
(99, 1, 0, NULL, 'wishlist', 0, '2026-05-28 23:22:42', '🔥 Destinasi wishlist-mu sedang populer! Keraton Kasepuhan dikunjungi 10 kali minggu ini. Jangan sampai kehabisan tempat!', 'detail.php?id=1');

-- --------------------------------------------------------

--
-- Table structure for table `personal_chats`
--

CREATE TABLE `personal_chats` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_chats`
--

INSERT INTO `personal_chats` (`id`, `trip_id`, `sender_id`, `receiver_id`, `created_at`) VALUES
(1, 17, 8, 7, '2026-05-18 08:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `personal_messages`
--

CREATE TABLE `personal_messages` (
  `id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_messages`
--

INSERT INTO `personal_messages` (`id`, `chat_id`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 8, 'hai', 0, '2026-05-18 08:47:38'),
(2, 1, 8, 'Jam berapa berangkat?', 0, '2026-05-18 08:47:48'),
(3, 1, 8, 'Tunggu ya sebentar', 0, '2026-05-18 08:58:52');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `wisata_id` int(11) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`id`, `user_id`, `wisata_id`, `komentar`, `rating`, `created_at`) VALUES
(14, 2, 31, 'suasananya nyaman dan juga sejuk banget! suka deh sama tenpatnya', 5, '2026-05-28 22:26:54'),
(15, 2, 31, 'suasananya nyaman dan sejuk banget! suka deh sama tempatnya', 5, '2026-05-28 22:27:55'),
(16, 12, 32, 'cantik viewnyaa cocok buat foto foto!', 5, '2026-05-28 23:03:05'),
(17, 12, 26, 'gak nyesel sering kesini karna emang sebagus itu ', 5, '2026-05-28 23:03:39'),
(19, 13, 25, 'wow kita jdi bisa wisata sambil belajar sejarah disini!', 5, '2026-05-28 23:05:34'),
(20, 14, 27, 'sumpah cakep banget pemandangannya memanjakan mata', 5, '2026-05-28 23:06:56'),
(23, 17, 1, 'gilaaa udah cakep banget aja sekarang nih keraton', 5, '2026-05-28 23:11:33'),
(24, 18, 2, 'jadi kangen temen smp tiap kali main kesini, sunsetnya bagus banget', 4, '2026-05-28 23:13:28');

-- --------------------------------------------------------

--
-- Table structure for table `review_likes`
--

CREATE TABLE `review_likes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_likes`
--

INSERT INTO `review_likes` (`id`, `review_id`, `user_id`, `created_at`) VALUES
(2, 8, 8, '2026-05-23 05:27:55'),
(3, 7, 2, '2026-05-23 05:34:56'),
(4, 10, 2, '2026-05-23 09:31:45'),
(7, 11, 8, '2026-05-28 00:50:41'),
(8, 4, 8, '2026-05-28 00:55:28'),
(9, 8, 10, '2026-05-28 11:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_replies`
--

INSERT INTO `review_replies` (`id`, `review_id`, `user_id`, `komentar`, `created_at`) VALUES
(1, 8, 8, 'BENER', '2026-05-23 05:28:02'),
(2, 8, 8, 'BENER', '2026-05-23 05:28:06'),
(3, 7, 2, 'waw iya', '2026-05-23 05:34:53'),
(4, 8, 8, 'BAGUS', '2026-05-23 05:35:57'),
(5, 10, 2, 'ok', '2026-05-23 09:31:49'),
(6, 11, 2, 'Y', '2026-05-23 11:51:29'),
(7, 11, 8, 'APASIH', '2026-05-23 11:56:05'),
(8, 11, 8, 'HAH', '2026-05-28 00:50:12'),
(9, 4, 8, 'YAW', '2026-05-28 00:55:29'),
(10, 8, 10, 'yu', '2026-05-28 11:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `trip`
--

CREATE TABLE `trip` (
  `id` int(11) NOT NULL,
  `nama_trip` varchar(100) NOT NULL,
  `destinasi` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `max_orang` int(11) DEFAULT 5,
  `tanggal` date DEFAULT NULL,
  `transportasi` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('planning','selesai','batal') DEFAULT 'planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip`
--

INSERT INTO `trip` (`id`, `nama_trip`, `destinasi`, `deskripsi`, `creator_id`, `max_orang`, `tanggal`, `transportasi`, `catatan`, `status`, `created_at`) VALUES
(1, 'healing', NULL, 'jalanjalan', 2, 5, '2026-12-02', NULL, NULL, 'selesai', '2026-05-10 13:32:58'),
(2, 'healing cirebon', NULL, '\r\n    Transportasi : Motor\r\n\r\n    Catatan :\r\n    ya\r\n    ', 1, 5, '2026-03-12', NULL, NULL, '', '2026-05-11 00:51:54'),
(3, 'crb escape', NULL, '\r\n    Transportasi : Motor\r\n\r\n    Catatan :\r\n    t\r\n    ', 1, 5, '2026-12-04', NULL, NULL, 'batal', '2026-05-11 00:53:24'),
(4, 'healing', NULL, '\r\n    Transportasi : Pesawat\r\n\r\n    Catatan :\r\n    jangan luoa makan yg banyak biar sehat dan fit sellau badannya yaaa kita harus aware dengan diri kita sendiri okey \r\n    ', 1, 5, '0000-00-00', NULL, NULL, 'selesai', '2026-05-11 00:59:45'),
(5, 'h', NULL, '\r\n    Transportasi : Motor\r\n\r\n    Catatan :\r\n    y\r\n    ', 1, 5, '7777-07-07', NULL, NULL, '', '2026-05-11 01:23:39'),
(6, 'y', NULL, '\r\nTransportasi : Pesawat\r\n\r\nCatatan :\r\nm\r\n', 1, 5, '0888-08-08', NULL, NULL, 'batal', '2026-05-11 01:34:39'),
(7, 'ya', NULL, '\r\nTransportasi : Mobil\r\n\r\nBudget : Rp 500.000\r\n\r\nCatatan :\r\nya\r\n', 1, 5, '8888-08-08', NULL, NULL, 'batal', '2026-05-11 05:17:29'),
(8, 'hhhhhhhh', NULL, '\r\nTransportasi : Bus\r\n\r\nBudget : Rp 5.000.000.000\r\n\r\nCatatan :\r\nhmmmmmm\r\n', 1, 5, '0000-00-00', NULL, NULL, 'batal', '2026-05-11 05:18:02'),
(9, 'ya', NULL, '\r\nTransportasi : Mobil\r\n\r\nBudget : Rp 80.000.000\r\n\r\nCatatan :\r\ny\r\n', 1, 5, '0000-00-00', NULL, NULL, 'batal', '2026-05-11 05:30:11'),
(10, 'kkkkkkkkk', NULL, '\r\nTransportasi : Kereta\r\n\r\nBudget : Rp 1.999.993\r\n\r\nCatatan :\r\n88888888888\r\n', 1, 5, '8888-08-08', NULL, NULL, 'selesai', '2026-05-11 09:27:37'),
(12, 'crb healing', NULL, '\r\nTransportasi : Kereta\r\n\r\nBudget : Rp 500.000\r\n\r\nCatatan :\r\njangan lupa bawa pb\r\n', 2, 5, '2026-06-12', NULL, NULL, 'selesai', '2026-05-11 14:35:52'),
(18, 'esacpe', NULL, '\nTransportasi : Pesawat\n\nBudget : Rp 5.000.000.000\n\nCatatan :\nheyyyyyyyyyyyyyyyyyyyyyyyy\n', 1, 5, '0000-00-00', NULL, NULL, 'planning', '2026-05-14 01:39:50'),
(20, 'y', NULL, '\nTransportasi : Bus\n\nBudget : Rp 200.000\n\nCatatan :\nnnnnnnnnnnnnnnn\n', 2, 5, '0000-00-00', NULL, NULL, 'selesai', '2026-05-23 09:39:45'),
(21, 'xyz', NULL, '\nTransportasi : Bus\n\nBudget : Rp 4.999.999\n\nCatatan :\nzzzzzzzzzzzz\n', 2, 5, '2026-05-24', NULL, NULL, 'selesai', '2026-05-23 09:41:13'),
(22, 'hiling', NULL, '\nTransportasi : Kereta\n\nBudget : Rp 70.000.000\n\nCatatan :\nkkkkkkk\n', 2, 5, '0000-00-00', NULL, NULL, 'selesai', '2026-05-23 10:28:52'),
(23, 'scp', NULL, '\nTransportasi : Kereta\n\nBudget : Rp 5.000\n\nCatatan :\nmmmmmmmm\n', 2, 5, '2026-05-24', NULL, NULL, 'selesai', '2026-05-23 10:29:54'),
(24, 'HHH', NULL, '\nTransportasi : Motor\n\nBudget : Rp 6.999\n\nCatatan :\nY\n', 2, 5, '8888-08-08', NULL, NULL, 'selesai', '2026-05-23 11:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `trip_budget_items`
--

CREATE TABLE `trip_budget_items` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_item` varchar(255) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kategori` varchar(100) DEFAULT 'Lainnya',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_budget_items`
--

INSERT INTO `trip_budget_items` (`id`, `trip_id`, `user_id`, `nama_item`, `jumlah`, `kategori`, `created_at`) VALUES
(1, 12, 2, 'ben', 5000.00, 'Transportasi', '2026-05-23 09:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `trip_chat`
--

CREATE TABLE `trip_chat` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('group','personal') DEFAULT 'group',
  `to_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_chat`
--

INSERT INTO `trip_chat` (`id`, `trip_id`, `user_id`, `message`, `type`, `to_user_id`, `created_at`) VALUES
(1, 17, 8, 'hai', 'personal', 7, '2026-05-18 11:04:58'),
(2, 17, 8, 'HAI', 'personal', 7, '2026-05-19 08:02:16'),
(3, 18, 1, 'HAI', 'personal', 8, '2026-05-20 06:18:50'),
(4, 17, 8, 'HAI', 'personal', 2, '2026-05-22 11:44:35'),
(5, 17, 2, 'iyaa', 'personal', 8, '2026-05-22 13:39:52'),
(6, 17, 8, 'gimana jadi gaaa?', 'personal', 2, '2026-05-22 13:41:32'),
(7, 17, 8, 'kangen', 'personal', 2, '2026-05-23 01:28:01'),
(8, 25, 8, 'hai bruhhh', 'personal', 2, '2026-05-23 11:55:17'),
(9, 25, 8, 'jadi gaaa', 'group', NULL, '2026-05-23 11:55:28'),
(10, 25, 2, 'bebas si', 'group', NULL, '2026-05-23 12:10:35'),
(11, 25, 2, 'y', 'group', NULL, '2026-05-24 02:17:18'),
(12, 25, 8, 'OIIII', 'group', NULL, '2026-05-28 00:52:34'),
(13, 25, 8, 'HIII', 'personal', 7, '2026-05-28 00:52:49'),
(14, 25, 8, 'HIII', 'personal', 7, '2026-05-28 00:52:55'),
(15, 25, 8, 'HIII', 'personal', 2, '2026-05-28 00:53:00'),
(16, 25, 8, 'HIIII', 'personal', 2, '2026-05-28 00:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `trip_collaborators`
--

CREATE TABLE `trip_collaborators` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('host','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_collaborators`
--

INSERT INTO `trip_collaborators` (`id`, `trip_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 2, 1, 'host', '2026-05-18 08:46:16'),
(2, 3, 1, 'host', '2026-05-18 08:46:16'),
(3, 4, 1, 'host', '2026-05-18 08:46:16'),
(4, 5, 1, 'host', '2026-05-18 08:46:16'),
(5, 6, 1, 'host', '2026-05-18 08:46:16'),
(6, 7, 1, 'host', '2026-05-18 08:46:16'),
(7, 8, 1, 'host', '2026-05-18 08:46:16'),
(8, 9, 1, 'host', '2026-05-18 08:46:16'),
(9, 10, 1, 'host', '2026-05-18 08:46:16'),
(10, 18, 1, 'host', '2026-05-18 08:46:16'),
(11, 1, 2, 'host', '2026-05-18 08:46:16'),
(12, 12, 2, 'host', '2026-05-18 08:46:16'),
(13, 13, 6, 'host', '2026-05-18 08:46:16'),
(14, 14, 7, 'host', '2026-05-18 08:46:16'),
(15, 15, 7, 'host', '2026-05-18 08:46:16'),
(16, 16, 7, 'host', '2026-05-18 08:46:16'),
(17, 17, 8, 'host', '2026-05-18 08:46:16'),
(18, 19, 9, 'host', '2026-05-18 08:46:16'),
(39, 17, 7, 'member', '2026-05-18 08:47:28'),
(139, 9, 7, 'member', '2026-05-18 09:03:36'),
(142, 9, 6, 'member', '2026-05-18 09:03:42'),
(145, 9, 8, 'member', '2026-05-18 09:03:59');

-- --------------------------------------------------------

--
-- Table structure for table `trip_detail`
--

CREATE TABLE `trip_detail` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `wisata_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_detail`
--

INSERT INTO `trip_detail` (`id`, `trip_id`, `wisata_id`) VALUES
(2, 3, 1),
(3, 4, 2),
(4, 5, 1),
(22, 6, 2),
(23, 7, 2),
(21, 7, 25),
(7, 8, 1),
(8, 9, 1),
(24, 9, 2),
(20, 9, 25),
(9, 10, 1),
(28, 10, 25),
(11, 12, 1),
(26, 18, 1),
(27, 18, 2),
(25, 18, 25),
(35, 21, 1),
(45, 21, 2),
(44, 21, 28),
(36, 22, 2),
(38, 24, 1);

-- --------------------------------------------------------

--
-- Table structure for table `trip_itinerary`
--

CREATE TABLE `trip_itinerary` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hari` int(11) NOT NULL DEFAULT 1,
  `waktu` varchar(10) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_itinerary`
--

INSERT INTO `trip_itinerary` (`id`, `trip_id`, `user_id`, `hari`, `waktu`, `aktivitas`, `lokasi`, `catatan`, `created_at`) VALUES
(1, 17, 8, 1, '09:09', 'mkn', 'pntai', 'y', '2026-05-18 11:05:18'),
(2, 12, 2, 12, '09:09', 'mkn', 'pntai', 'y', '2026-05-23 04:28:27');

-- --------------------------------------------------------

--
-- Table structure for table `trip_member`
--

CREATE TABLE `trip_member` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_members`
--

CREATE TABLE `trip_members` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('creator','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_members`
--

INSERT INTO `trip_members` (`id`, `trip_id`, `user_id`, `role`, `joined_at`) VALUES
(3, 18, 8, 'member', '2026-05-20 06:18:33'),
(5, 12, 8, 'member', '2026-05-22 12:03:00'),
(7, 17, 2, 'member', '2026-05-23 10:32:01'),
(9, 25, 7, 'member', '2026-05-23 11:55:07'),
(10, 25, 2, 'member', '2026-05-28 00:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `trip_votes`
--

CREATE TABLE `trip_votes` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_votes`
--

INSERT INTO `trip_votes` (`id`, `trip_id`, `creator_id`, `judul`, `deskripsi`, `created_at`) VALUES
(1, 12, 2, 'mau apa', '', '2026-05-23 09:35:55'),
(2, 18, 8, ',,,,', 'BB', '2026-05-28 00:48:12'),
(3, 25, 8, 'BBBBBB', 'B', '2026-05-28 00:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `trip_vote_options`
--

CREATE TABLE `trip_vote_options` (
  `id` int(11) NOT NULL,
  `vote_id` int(11) NOT NULL,
  `opsi` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_vote_options`
--

INSERT INTO `trip_vote_options` (`id`, `vote_id`, `opsi`) VALUES
(1, 1, 'aa'),
(2, 1, 'bb'),
(3, 1, 'cc'),
(4, 2, 'NN'),
(5, 2, 'NN'),
(6, 3, 'B'),
(7, 3, 'B'),
(8, 3, 'B');

-- --------------------------------------------------------

--
-- Table structure for table `trip_vote_responses`
--

CREATE TABLE `trip_vote_responses` (
  `id` int(11) NOT NULL,
  `vote_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_vote_responses`
--

INSERT INTO `trip_vote_responses` (`id`, `vote_id`, `option_id`, `user_id`) VALUES
(1, 1, 2, 2),
(2, 2, 5, 8),
(3, 3, 7, 8);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `level` enum('Newbie','Explorer','Traveler','Expert Traveler','Cirebon Master') DEFAULT 'Newbie',
  `wacana_count` int(11) DEFAULT 0,
  `total_review` int(11) DEFAULT 0,
  `total_wishlist` int(11) DEFAULT 0,
  `total_trip` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `level`, `wacana_count`, `total_review`, `total_wishlist`, `total_trip`, `created_at`, `foto`) VALUES
(1, 'Admin TRAVA', 'admin@trava.com', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 'Newbie', 0, 0, 0, 0, '2026-05-02 19:40:16', '1778485733_1778485567_kimi.jpg'),
(2, 'faija', 'faija@gmail.com', '35d7f869465ceed4a67ded6b8240c786', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-10 05:45:05', '1778500262_1778485567_kimi.jpg'),
(12, 'mila', 'mila@gmail.com', '08c12f6fd8c9da88a79f0abbddbb6fd4', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-28 23:02:26', NULL),
(13, 'farel', 'farel@gmail.com', 'd32b1937fafb4c4c6bdae009d4e93151', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-28 23:04:10', NULL),
(14, 'dilan', 'dilan@gmail.com', '06850213d32b28cc188f0d6a16373b69', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-28 23:06:03', NULL),
(17, 'andrea', 'andrea@gmail.com', '19984dcaea13176bbb694f62ba6b5b35', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-28 23:11:01', NULL),
(18, 'pian', 'pian@gmail.com', '16d5d24f5b09a1991bd4e5f57bf11237', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-28 23:12:48', NULL),
(19, 'gema', 'gema@gmail.com', 'bf012e2220ad9dbb07740f2519057746', 'user', 'Newbie', 0, 0, 0, 0, '2026-05-28 23:19:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wisata`
--

CREATE TABLE `wisata` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `lokasi` varchar(150) DEFAULT 'Cirebon',
  `alamat_detail` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `harga` int(11) DEFAULT 0,
  `fasilitas` text DEFAULT NULL,
  `aktivitas` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `maps` text DEFAULT NULL,
  `rating_avg` float DEFAULT 0,
  `rating_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `harga_sebelumnya` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wisata`
--

INSERT INTO `wisata` (`id`, `nama`, `deskripsi`, `lokasi`, `alamat_detail`, `kategori`, `harga`, `fasilitas`, `aktivitas`, `gambar`, `maps`, `rating_avg`, `rating_count`, `created_at`, `harga_sebelumnya`) VALUES
(1, 'Keraton Kasepuhan', 'Keraton Kasepuhan adalah pusat pemerintahan Kesultanan Cirebon pada masa lalu. Arsitekturnya memadukan gaya Jawa, Islam, dan Tiongkok. Di dalamnya terdapat bangunan utama keraton, museum pusaka, kereta kencana Singa Barong, serta koleksi lukisan dan keramik kuno. Tradisi budaya seperti Grebeg Syawal dan Maulid Nabi masih rutin digelar di sini.', 'Cirebon', 'Jl. Kasepuhan No.43, Kesepuhan, Kec. Lemahwungkuk, Kota Cirebon, Jawa Barat 45114', 'Sejarah', 17000, 'Kompleks keraton dengan alun-alun, Masjid Agung Sang Cipta Rasa, museum pusaka, kereta Singa Barong, bangsal pertemuan, taman, area parkir, pemandu wisata, serta spot foto bersejarah.', 'Menjelajahi bangunan bersejarah, melihat koleksi pusaka dan kereta Singa Barong, berfoto di gapura klasik dan bangsal keraton, menyaksikan tradisi budaya seperti Grebeg Syawal, berziarah di Masjid Sang Cipta Rasa, serta mengikuti tur edukasi sejarah Cirebon.', '1780011310_kasepuhan.jpg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.358620887743!2d108.56829427403257!3d-6.726023165761237!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6ee263eaaaaaab%3A0x20ea18cbfb1df195!2sKeraton%20Kasepuhan!5e0!3m2!1sid!2sid!4v1778304487119!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 5, 1, '2026-05-05 02:16:41', 17000),
(2, 'Pantai Kejawanan', 'Pantai Kejawanan memiliki garis pantai yang cukup panjang dengan pasir kecokelatan dan ombak tenang. Suasananya ramai saat sore hari karena banyak pengunjung datang untuk menikmati sunset. Selain itu, pantai ini juga menjadi pusat aktivitas nelayan, sehingga pengunjung bisa melihat langsung perahu tradisional dan hasil tangkapan laut.', 'Cirebon', '7H8P+V6W, Jl raya kali jaga kejawanan, Jl. Pelabuhan Perikanan, Pegambiran, Kec. Lemahwungkuk, Kota Cirebon, Jawa Barat 41165', 'Pantai', 10000, 'Area parkir, warung makan seafood, gazebo sederhana, toilet, mushola, dermaga kecil untuk perahu nelayan, serta spot foto di tepi pantai.', 'Menikmati sunset di tepi pantai, berfoto dengan latar perahu nelayan, kulineran seafood segar di warung sekitar, bermain pasir bersama keluarga, naik perahu tradisional untuk berkeliling pantai, serta bersantai menikmati angin laut.', '1780011295_kejawanan.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.2967282627774!2d108.58321607403269!3d-6.7336076658370505!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1d644fadef95%3A0x8a0e2fb918b655bd!2sPantai%20Kejawanan%20Kota%20Cirebon!5e0!3m2!1sid!2sid!4v1778305057252!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 4, 1, '2026-05-05 02:16:41', 10000),
(25, 'Goa Sunyaragi ', 'Goa Sunyaragi berada di Kelurahan Sunyaragi, Kecamatan Kesambi, Kota Cirebon. Nama “Sunyaragi” berasal dari kata sunya (sepi) dan ragi (raga), yang berarti tempat menyepi untuk menenangkan diri. Kompleks ini memiliki arsitektur unik berupa gua-gua batu karang yang dibangun menyerupai benteng. Selain sebagai tempat meditasi para sultan dan prajurit, Goa Sunyaragi juga berfungsi sebagai benteng pertahanan dari serangan musuh.', 'Cirebon', 'Jl. Brigjen Dharsono No.107, Sunyaragi, Kec. Kesambi, Kota Cirebon, Jawa Barat 45132', 'Alam', 16000, 'Kompleks gua batu karang, taman terbuka, panggung kesenian, area parkir, toilet, mushola, pemandu wisata, serta spot foto bersejarah.', 'Menjelajahi gua-gua bersejarah (Goa Peteng, Goa Padang Ati, Goa Lawa), berfoto di arsitektur batu karang unik, menyaksikan pertunjukan seni tradisional di panggung terbuka, belajar sejarah Kesultanan Cirebon, serta berziarah di area sakral.', 'goa sunyaragi.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.2694896865287!2d108.54070157356242!3d-6.736942865869405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1df257d4d4fb%3A0x8d4ea2ef1ff1103d!2sTaman%20Wisata%20Goa%20Sunyaragi%20Cirebon!5e0!3m2!1sid!2sid!4v1778720120113!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 5, 1, '2026-05-14 00:57:25', 16000),
(26, 'Bukit Gronggong', 'Bukit Gronggong berada di jalur utama Cirebon–Kuningan. Dari puncak bukit, pengunjung bisa melihat gemerlap lampu kota Cirebon, bahkan hingga laut utara Jawa. Suasananya sejuk, romantis, dan sering dijadikan tempat nongkrong anak muda maupun keluarga.', 'Cirebon', NULL, 'Alam', 5005, 'Restoran dan kafe dengan view city light, gazebo untuk bersantai, area parkir luas, warung makan sederhana, spot foto romantis, serta area terbuka untuk piknik.', 'Menikmati city light Cirebon di malam hari, kulineran di restoran atau kafe, berfoto dengan latar gemerlap lampu kota, bersantai bersama keluarga atau pasangan, serta menikmati udara sejuk pegunungan.', 'gronggong.jpg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.906741011909!2d108.5218086!3d-6.7812035999999996!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1e9d4d3629d1%3A0x7ef0a6b8a94a3398!2sBukit%20Gronggong!5e0!3m2!1sid!2sid!4v1778745946500!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 5, 1, '2026-05-14 08:06:35', 999),
(27, 'Batu Lawang', 'Batu Lawang diresmikan tahun 2016, dulunya bekas tambang semen. Nama “Lawang” berasal dari bahasa Sunda yang berarti pintu, karena terdapat batu besar menjulang menyerupai pintu masuk alami. Tebing-tebing batu berwarna krem hingga abu-abu tua berpadu dengan pepohonan rindang, menciptakan suasana sejuk dan asri. Dari puncak bukit, pengunjung bisa melihat panorama Cirebon dan pabrik semen di kejauhan.', 'Cirebon', NULL, 'Alam', 10000, 'Gazebo untuk bersantai, toilet, mushola, area parkir, jalur trekking dengan lebih dari 80 anak tangga, area permainan anak, warung makan, serta spot foto Instagramable dengan pagar pembatas untuk keamanan.', 'Trekking atau hiking menaiki tebing, berfoto di spot batu unik (bentuk sarang burung, menumpuk, melingkar), bersantai di gazebo, piknik keluarga, berkemah di area terbuka, menikmati panorama alam dari puncak bukit, serta hunting foto dengan latar bebatuan eksotis.', '1780011283_batu lawang.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.345400329613!2d108.37813537356224!3d-6.727643965776472!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6edf7700000001%3A0x6184c1b9c1112bde!2sBatu%20Lawang%20Cirebon!5e0!3m2!1sid!2sid!4v1778746526651!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 5, 1, '2026-05-14 08:16:14', 10000),
(28, 'Plangon', 'Plangon adalah kawasan hutan rindang di dataran tinggi Sumber, Cirebon. Suasananya sejuk dengan pepohonan besar, cocok untuk rekreasi keluarga. Daya tarik utamanya adalah ratusan kera liar yang hidup bebas dan jinak, sehingga pengunjung bisa memberi makan langsung. Selain itu, terdapat makam keramat Pangeran Panjunan dan Pangeran Kejaksan, tokoh penting dalam sejarah Cirebon. Konon jumlah kera di Plangon selalu 99 ekor, terkait mitos kesetiaan monyet peliharaan Pangeran Kejaksan.', 'Cirebon', NULL, 'Alam', 3000, 'Tangga menanjak (224 anak tangga), Area parkir, warung makan dan minuman, gazebo sederhana, jalur pejalan kaki di hutan, makam keramat, serta area terbuka untuk berinteraksi dengan kera.', 'Memberi makan kera liar, berziarah ke makam keramat, berjalan santai di hutan rindang, berfoto dengan suasana alami, menikmati udara sejuk pegunungan, serta belajar sejarah dan mitos lokal.', '1780011272_plangon.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31695.682903917826!2d108.46495586429089!3d-6.774679049741891!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1ef9c7471a0d%3A0x3ee1bf4561fce4d0!2sBukit%20Plangon!5e0!3m2!1sid!2sid!4v1778746771493!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 4, 1, '2026-05-14 08:20:01', 3000),
(30, 'Keraton kanoman', 'Keraton Kanoman berdiri di atas lahan ±6 hektare, merupakan pecahan dari Kesultanan Cirebon bersama Keraton Kasepuhan dan Keraton Kacirebonan. Di dalamnya terdapat bangunan bersejarah, pusaka sakral, kereta kerajaan (Paksi Naga Liman dan Jempana), serta ornamen keramik Tiongkok peninggalan Putri Ong Tien. Tradisi seperti Sekaten, Maulid Nabi, dan upacara adat masih dilestarikan di sini.', 'Cirebon', NULL, 'Sejarah', 10000, 'Alun-alun dengan Beringin Kurung, Masjid Agung Kanoman, Lawang Dalem Agung (pintu utama), Bangsal Panca Niti & Panca Ratna, Lumpang Alu peninggalan Pangeran Cakrabuana, Komplek Ksiti Hinggil (Made Manguntur, Bangsal Sekaten), Lawang Si Blawong dari kayu jati, Balai Paseban, Taman Sari & Balong Asem dengan patung binatang, museum pusaka, area parkir, dan pemandu wisata lokal. ', 'Menjelajahi bangunan bersejarah, melihat pusaka keraton, menyaksikan tradisi budaya seperti Sekaten, berfoto di gapura dan bangsal klasik, berziarah ke sumur-sumur sakral di Kebon Jimat, mengikuti tur edukasi sejarah Cirebon, serta menikmati suasana religius di Masjid Agung Kanoman. ', '1780011262_kanoman.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.3882626239624!2d108.56524907356219!3d-6.722387765724023!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6ee25deaaaaaab%3A0xb5cb14c3d5f80987!2sKeraton%20Kanoman!5e0!3m2!1sid!2sid!4v1778748422692!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 0, 0, '2026-05-14 08:47:21', 10000),
(31, 'Telaga Biru', 'Telaga Biru Cicerem berada di Desa Kaduela, Kecamatan Pasawahan, Kabupaten Kuningan (±20 km dari Cirebon, ±35 menit perjalanan). Air telaga berwarna biru kehijauan dengan kejernihan tinggi sehingga ikan terlihat jelas dari permukaan. Suasana rindang pepohonan membuat udara sejuk dan cocok untuk relaksasi. Telaga ini juga menyimpan legenda Nyi Bomas Inten, yang divisualisasikan lewat patung dan air mancur di area wisata.', 'Cirebon', NULL, 'Alam', 15000, 'Area parkir luas, toilet bersih, mushola, gazebo, warung makan, toko oleh-oleh, jembatan kayu, spot foto berbayar dan gratis, sewa perahu, kolam terapi ikan.', 'Bersantai di tepi telaga, berenang atau menyelam di air jernih, mengelilingi telaga dengan perahu, memberi makan ikan, berfoto di ayunan viral atau spot sarang burung, menikmati kuliner di warung sekitar, terapi ikan untuk relaksasi.', '1780011252_telaga biru.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.774413621778!2d108.4233507!3d-6.7972781!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f21e13b414561%3A0x9f9df80a24eac71f!2sTelaga%20Biru%20Cicerem!5e0!3m2!1sid!2sid!4v1779993280969!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 5, 2, '2026-05-28 18:21:33', 15000),
(32, 'Talaga Langit', 'Talaga Langit dibangun di atas lahan ±5 hektare oleh Ujang Bustomi (youtuber asal Cirebon). Suasananya sejuk dengan pemandangan hijau perbukitan, siluet Gunung Ciremai, dan panorama Setu Patok. Konsepnya memadukan rekreasi keluarga, edukasi budaya mistis, dan spot foto estetik.', 'Cirebon', NULL, 'Alam', 15000, 'Kolam renang (bertingkat, untuk dewasa & anak, ada perosotan), Goa alami (Goa Cinta, Goa Adipati, dan goa dengan sumber mata air jernih), Spot selfie (taman bunga, jembatan kaca, tangga berbentuk tangan), Museum Santet (edukasi budaya mistis), Kafe Bukit Cinta Anti Galau (menu nasi timbel, sop iga, dimsum, dengan view city light malam hari), Kolam terapi ikan untuk relaksasi.', 'Berenang dengan panorama (sensasi “berenang di atas awan”), Eksplorasi goa (merasakan air jernih dan suasana alami), Berfoto di spot estetik (cocok untuk konten media sosial), Wisata mistis edukatif (Kampung Dukun, Taman Pocong, Museum Santet), Kuliner di kafe (menikmati makanan sambil melihat city light Cirebon).', '1780011242_talaga langit.jpeg', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.7647451241264!2d108.5701848!3d-6.798451099999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1dfe2d3d57cd%3A0x32fd5afa0060fedf!2sTalaga%20langit!5e0!3m2!1sid!2sid!4v1779993150747!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', 5, 1, '2026-05-28 18:22:43', 15000);

-- --------------------------------------------------------

--
-- Table structure for table `wisata_visits`
--

CREATE TABLE `wisata_visits` (
  `id` int(11) NOT NULL,
  `wisata_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wisata_visits`
--

INSERT INTO `wisata_visits` (`id`, `wisata_id`, `user_id`, `visited_at`) VALUES
(1, 25, 2, '2026-05-23 05:26:07'),
(2, 25, 2, '2026-05-23 05:26:22'),
(3, 26, 2, '2026-05-23 05:27:20'),
(4, 29, 8, '2026-05-23 05:27:51'),
(5, 26, 2, '2026-05-23 05:34:41'),
(6, 29, 2, '2026-05-23 05:35:00'),
(7, 28, 8, '2026-05-23 05:35:38'),
(8, 29, 8, '2026-05-23 05:35:48'),
(9, 29, 2, '2026-05-23 05:36:41'),
(10, 29, 2, '2026-05-23 05:45:16'),
(11, 1, 2, '2026-05-23 05:50:21'),
(12, 2, 2, '2026-05-23 05:51:42'),
(13, 2, 2, '2026-05-23 05:51:47'),
(14, 26, 2, '2026-05-23 05:52:47'),
(15, 26, 2, '2026-05-23 05:52:51'),
(16, 29, 2, '2026-05-23 09:31:28'),
(17, 29, 2, '2026-05-23 09:31:33'),
(18, 29, 2, '2026-05-23 09:31:41'),
(19, 29, 2, '2026-05-23 09:31:58'),
(20, 29, 2, '2026-05-23 09:31:59'),
(21, 29, 2, '2026-05-23 09:32:04'),
(22, 2, 2, '2026-05-23 09:32:26'),
(23, 30, 2, '2026-05-23 11:50:53'),
(24, 30, 2, '2026-05-23 11:50:57'),
(25, 30, 2, '2026-05-23 11:51:05'),
(26, 30, 8, '2026-05-23 11:55:56'),
(27, 26, 2, '2026-05-24 01:00:13'),
(28, 29, 2, '2026-05-24 01:05:46'),
(29, 26, 2, '2026-05-24 01:54:29'),
(30, 26, 2, '2026-05-24 01:59:33'),
(31, 26, 2, '2026-05-24 01:59:47'),
(32, 30, 8, '2026-05-24 02:47:42'),
(33, 26, 8, '2026-05-26 12:05:33'),
(34, 29, 8, '2026-05-27 13:52:47'),
(35, 2, 8, '2026-05-27 13:53:01'),
(36, 25, NULL, '2026-05-28 00:04:02'),
(37, 25, NULL, '2026-05-28 00:04:13'),
(38, 25, NULL, '2026-05-28 00:04:57'),
(39, 25, NULL, '2026-05-28 00:05:08'),
(40, 30, NULL, '2026-05-28 00:05:41'),
(41, 25, NULL, '2026-05-28 00:16:15'),
(42, 30, 8, '2026-05-28 00:49:48'),
(43, 30, 8, '2026-05-28 00:50:46'),
(44, 29, 8, '2026-05-28 00:52:06'),
(45, 2, 8, '2026-05-28 00:55:17'),
(46, 29, 8, '2026-05-28 02:25:26'),
(47, 30, 8, '2026-05-28 02:27:27'),
(48, 30, 8, '2026-05-28 02:27:33'),
(49, 25, NULL, '2026-05-28 02:56:32'),
(50, 25, NULL, '2026-05-28 02:56:48'),
(51, 2, NULL, '2026-05-28 05:30:31'),
(52, 2, NULL, '2026-05-28 05:31:03'),
(53, 25, NULL, '2026-05-28 05:31:11'),
(54, 28, NULL, '2026-05-28 11:34:36'),
(55, 2, NULL, '2026-05-28 11:34:59'),
(56, 2, NULL, '2026-05-28 11:35:02'),
(57, 28, NULL, '2026-05-28 11:35:21'),
(58, 29, 10, '2026-05-28 11:36:43'),
(59, 29, 10, '2026-05-28 11:36:53'),
(60, 29, 10, '2026-05-28 11:37:01'),
(61, 29, 10, '2026-05-28 11:42:30'),
(62, 29, 10, '2026-05-28 11:42:51'),
(63, 29, 10, '2026-05-28 11:43:20'),
(64, 1, 10, '2026-05-28 11:44:22'),
(65, 28, NULL, '2026-05-28 11:48:56'),
(66, 28, NULL, '2026-05-28 13:37:43'),
(67, 2, NULL, '2026-05-28 13:37:49'),
(68, 2, NULL, '2026-05-28 13:56:26'),
(69, 27, NULL, '2026-05-28 14:01:59'),
(70, 27, NULL, '2026-05-28 14:02:10'),
(71, 27, NULL, '2026-05-28 14:02:30'),
(72, 2, NULL, '2026-05-28 15:38:16'),
(73, 28, NULL, '2026-05-28 15:38:42'),
(74, 25, NULL, '2026-05-28 15:43:47'),
(75, 25, NULL, '2026-05-28 15:45:05'),
(76, 29, 8, '2026-05-28 15:45:57'),
(77, 31, NULL, '2026-05-28 21:34:49'),
(78, 2, NULL, '2026-05-28 21:35:07'),
(79, 2, NULL, '2026-05-28 21:38:08'),
(80, 2, NULL, '2026-05-28 21:38:10'),
(81, 32, NULL, '2026-05-28 22:01:30'),
(82, 2, NULL, '2026-05-28 22:01:57'),
(83, 31, 2, '2026-05-28 22:03:36'),
(84, 25, NULL, '2026-05-28 22:04:58'),
(85, 32, 2, '2026-05-28 22:26:19'),
(86, 31, 2, '2026-05-28 22:26:32'),
(87, 31, 2, '2026-05-28 22:26:55'),
(88, 31, 2, '2026-05-28 22:27:01'),
(89, 31, 2, '2026-05-28 22:27:26'),
(90, 31, 2, '2026-05-28 22:27:55'),
(91, 31, 2, '2026-05-28 22:28:04'),
(92, 32, 2, '2026-05-28 22:42:38'),
(93, 32, 12, '2026-05-28 23:02:45'),
(94, 32, 12, '2026-05-28 23:03:05'),
(95, 32, 12, '2026-05-28 23:03:07'),
(96, 26, 12, '2026-05-28 23:03:15'),
(97, 26, 12, '2026-05-28 23:03:39'),
(98, 1, 12, '2026-05-28 23:03:49'),
(99, 30, 13, '2026-05-28 23:04:25'),
(100, 30, 13, '2026-05-28 23:04:43'),
(101, 25, 13, '2026-05-28 23:05:06'),
(102, 25, 13, '2026-05-28 23:05:34'),
(103, 27, 14, '2026-05-28 23:06:11'),
(104, 27, 14, '2026-05-28 23:06:56'),
(105, 28, 15, '2026-05-28 23:07:44'),
(106, 28, 15, '2026-05-28 23:08:21'),
(107, 28, 15, '2026-05-28 23:08:23'),
(108, 1, 16, '2026-05-28 23:09:09'),
(109, 1, 16, '2026-05-28 23:09:37'),
(110, 1, 17, '2026-05-28 23:11:11'),
(111, 1, 17, '2026-05-28 23:11:33'),
(112, 1, NULL, '2026-05-28 23:12:30'),
(113, 1, NULL, '2026-05-28 23:12:31'),
(114, 2, 18, '2026-05-28 23:12:56'),
(115, 2, 18, '2026-05-28 23:13:29'),
(116, 1, 20, '2026-05-28 23:22:42'),
(117, 25, NULL, '2026-05-28 23:30:12');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `wisata_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `wisata_id`, `created_at`) VALUES
(10, 1, 2, '2026-05-11 01:48:34'),
(12, 1, 1, '2026-05-11 01:49:41'),
(34, 2, 30, '2026-05-23 11:50:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_chats`
--
ALTER TABLE `personal_chats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_chat` (`trip_id`,`sender_id`,`receiver_id`);

--
-- Indexes for table `personal_messages`
--
ALTER TABLE `personal_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_review_user` (`user_id`),
  ADD KEY `idx_review_wisata` (`wisata_id`);

--
-- Indexes for table `review_likes`
--
ALTER TABLE `review_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`review_id`,`user_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip`
--
ALTER TABLE `trip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trip_creator` (`creator_id`);

--
-- Indexes for table `trip_budget_items`
--
ALTER TABLE `trip_budget_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_chat`
--
ALTER TABLE `trip_chat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_collaborators`
--
ALTER TABLE `trip_collaborators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_collab` (`trip_id`,`user_id`);

--
-- Indexes for table `trip_detail`
--
ALTER TABLE `trip_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trip_id` (`trip_id`,`wisata_id`),
  ADD KEY `wisata_id` (`wisata_id`);

--
-- Indexes for table `trip_itinerary`
--
ALTER TABLE `trip_itinerary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_member`
--
ALTER TABLE `trip_member`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trip_id` (`trip_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trip_members`
--
ALTER TABLE `trip_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`trip_id`,`user_id`);

--
-- Indexes for table `trip_votes`
--
ALTER TABLE `trip_votes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_vote_options`
--
ALTER TABLE `trip_vote_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_vote_responses`
--
ALTER TABLE `trip_vote_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`vote_id`,`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wisata`
--
ALTER TABLE `wisata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wisata_kategori` (`kategori`),
  ADD KEY `idx_wisata_lokasi` (`lokasi`);

--
-- Indexes for table `wisata_visits`
--
ALTER TABLE `wisata_visits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`wisata_id`),
  ADD KEY `wisata_id` (`wisata_id`),
  ADD KEY `idx_wishlist_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `personal_chats`
--
ALTER TABLE `personal_chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `personal_messages`
--
ALTER TABLE `personal_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `review_likes`
--
ALTER TABLE `review_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `trip`
--
ALTER TABLE `trip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `trip_budget_items`
--
ALTER TABLE `trip_budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `trip_chat`
--
ALTER TABLE `trip_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `trip_collaborators`
--
ALTER TABLE `trip_collaborators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `trip_detail`
--
ALTER TABLE `trip_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `trip_itinerary`
--
ALTER TABLE `trip_itinerary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trip_member`
--
ALTER TABLE `trip_member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_members`
--
ALTER TABLE `trip_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `trip_votes`
--
ALTER TABLE `trip_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trip_vote_options`
--
ALTER TABLE `trip_vote_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `trip_vote_responses`
--
ALTER TABLE `trip_vote_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `wisata`
--
ALTER TABLE `wisata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `wisata_visits`
--
ALTER TABLE `wisata_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip`
--
ALTER TABLE `trip`
  ADD CONSTRAINT `trip_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_detail`
--
ALTER TABLE `trip_detail`
  ADD CONSTRAINT `trip_detail_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trip` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_detail_ibfk_2` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_member`
--
ALTER TABLE `trip_member`
  ADD CONSTRAINT `trip_member_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trip` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_member_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
