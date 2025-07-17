-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2025 at 03:44 PM
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
-- Database: `cemeterydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `accepted_request`
--

CREATE TABLE `accepted_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('Interment','Transfer','Exhumation') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `dob` varchar(50) NOT NULL,
  `dod` varchar(50) NOT NULL,
  `residency` varchar(150) NOT NULL,
  `informant_name` varchar(150) NOT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accepted_request`
--

INSERT INTO `accepted_request` (`id`, `user_id`, `type`, `first_name`, `last_name`, `middle_name`, `age`, `dob`, `dod`, `residency`, `informant_name`, `file_upload`, `created_at`) VALUES
(1, 51, 'Exhumation', 'jang', 'maki', 'spol', 25, '2025-07-01', '2025-07-15', 'jakokas', 'manika', '1752581487_IMG_20250714_121530_129.jpg', '2025-07-15 12:11:27'),
(3, 47, 'Exhumation', 'hays', 'buhay', 'haha', 12, '2025-07-01', '2025-07-12', 'haha', 'meow', '1752589861_IMG_20250621_084651_941.jpg', '2025-07-15 14:31:01'),
(4, 47, 'Transfer', 'Jano', 'Gibs', 'Min', 58, '2025-07-01', '2025-07-14', 'manila', 'jojo', '1752591309_IMG_20250701_142351_749.jpg', '2025-07-15 14:55:09'),
(5, 47, 'Interment', 'donie', 'nietez', 'ahas', 58, '2025-07-01', '2025-07-15', 'manila', 'manny', '1752592264_denver COR.pdf', '2025-07-15 15:11:04'),
(6, 47, 'Interment', 'try', 'try', 'try', 15, '2025-07-01', '2025-07-15', 'try', 'try', '1752594303_IMG_20250701_202530_340.jpg', '2025-07-15 15:45:03'),
(7, 47, 'Transfer', 'kaj', 'bdbd', 'djf', 15, '2025-07-02', '2025-07-16', 'bsbs', 'nsbs', '1752639655_IMG_20250701_142351_749.jpg', '2025-07-16 04:20:55'),
(8, 52, 'Interment', 'John', 'Regala', 'John', 15, '2025-07-01', '2025-07-15', 'Manila Zoo', 'John Regala', '1752755119_IMG_20250629_114822_313.jpg', '2025-07-17 12:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `admin_accounts`
--

CREATE TABLE `admin_accounts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_accounts`
--

INSERT INTO `admin_accounts` (`id`, `email`, `password`) VALUES
(1, 'admin@restease.com', '$2y$10$5BAjYuDyUy5w/eT8qS6lRujqmSK8K3oFIewzCPylLQeTu6hv35qJ6');

-- --------------------------------------------------------

--
-- Table structure for table `archive_clients`
--

CREATE TABLE `archive_clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_no` varchar(30) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive_clients`
--

INSERT INTO `archive_clients` (`id`, `first_name`, `last_name`, `email`, `contact_no`, `archived_at`) VALUES
(8, 'DENVER', 'SALAZAR', 'denver@gmail.com', '09859822196', '2025-06-10 11:57:29'),
(9, 'DENVER', 'SALAZAR', 'denver11@gmail.com', '09859822196', '2025-06-10 12:01:41'),
(10, 'DENVER', 'KALA', 'denver12@gmail.com', '09859822196', '2025-06-10 12:07:02'),
(11, 'JOHN', 'Mike', 'john@gmail.com', '09859822196', '2025-06-10 12:12:32'),
(12, 'JOHN', 'Mike', 'john@gmail.com', '09859822196', '2025-06-10 12:15:21'),
(13, 'Denver', 'Salazar', 'denversalazar24@gmail.com', '12345678910', '2025-06-10 12:18:05'),
(14, 'JOHN', 'Mike', 'john@gmail.com', '09859822196', '2025-06-10 12:18:33'),
(15, 'JOHN', 'DOE', 'john@gmail.com', '09859822196', '2025-06-10 12:52:57'),
(16, 'Alleon John', 'Perez', 'alleon@gmail.com', '09859822196', '2025-06-10 13:01:35'),
(17, 'Lourenz Angel', 'Francisco', 'lourenz@gmail.com', '09859822196', '2025-06-11 06:02:58'),
(18, 'DENVER', 'SALAZAR', 'denver@gmail.com', '09859822196', '2025-06-11 06:03:41'),
(19, 'DENVER', 'SALAZAR', 'denver@gmail.com', '09859822196', '2025-06-11 07:15:48'),
(20, 'totoy', 'brown', 'totoy@gmail.com', '09859822196', '2025-07-04 13:47:06'),
(21, 'Denver', 'Salazar', 'denversalazar20@gmail.com', '09859822196', '2025-07-04 13:58:23'),
(22, 'Jung', 'Kook', 'jung@gmail.com', '09859822196', '2025-07-07 15:31:47');

-- --------------------------------------------------------

--
-- Table structure for table `archive_deceased`
--

CREATE TABLE `archive_deceased` (
  `id` int(11) NOT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `born` date DEFAULT NULL,
  `residency` varchar(255) DEFAULT NULL,
  `dateDied` date DEFAULT NULL,
  `dateInternment` date DEFAULT NULL,
  `nicheID` varchar(50) DEFAULT NULL,
  `informantName` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive_deceased`
--

INSERT INTO `archive_deceased` (`id`, `firstName`, `lastName`, `age`, `born`, `residency`, `dateDied`, `dateInternment`, `nicheID`, `informantName`) VALUES
(1, 'NAO', 'NANA', 10, '2025-06-04', 'HAHAAH', '2025-06-17', '2025-06-02', '1F-02FB', 'JAJA'),
(2, 'JAO', 'MOTO', 56, '2025-06-01', 'NATU', '2025-06-02', '2025-06-07', '1F-02FB', 'MIC'),
(3, 'boboy', 'kato', 50, '2025-06-12', 'jaa', '2025-06-20', '2025-07-12', '1F-09FB', 'haha'),
(4, '', '', 0, '0000-00-00', '', '0000-00-00', '0000-00-00', '1F-05FB', ''),
(5, '', '', 0, '0000-00-00', '', '0000-00-00', '0000-00-00', '1F-09FB', ''),
(6, 'hahah', 'haha', 4, '2025-06-01', 'haha', '2025-06-02', '2025-06-19', '1F-03FB', 'jaja'),
(7, 'watosi', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-03FB', 'wala'),
(33, 'wa', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-03FB', 'wala'),
(34, 'wa', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-06FB', 'wala'),
(35, 'wa', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-06FB', 'wala'),
(36, 'wa', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-06FB', 'wala'),
(37, 'wa', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-06FB', 'wala'),
(38, 'JAM', 'NOW', 5050, '2025-06-04', 'JAM', '2025-06-11', '2025-07-12', '1F-69AA', 'MKIE'),
(39, 'JONG', 'BONG', 50, '2025-06-04', 'NUT', '2025-06-09', '2025-06-11', '1F-01FD', 'JAJA'),
(40, 'Lebron', 'James', 56, '1969-02-11', 'Kalumpit, Bulacan', '2023-11-11', '2023-11-11', 'null', 'Jason Titum'),
(41, 'Lebron', 'James', 56, '1969-02-11', 'Kalumpit, Bulacan', '2023-11-11', '2023-11-11', 'null', 'Jason Titum'),
(42, 'Jovit', 'Baldevino', 43, '2000-04-12', 'Bawi, Padre Garcia, Batangas', '2024-08-29', '2024-08-29', 'null', 'Joseph Manalo'),
(44, 'hahahhaah', 'SSS', 35, '2025-06-09', 'FF', '2025-06-09', '2025-06-09', '1F-09FB', 'FF'),
(45, 'John', 'Manugo', 50, '1990-06-20', 'Quilib, Padre Garcia, Batangas', '2025-06-02', '2025-06-14', '1F-01FB', 'Maning Buchot'),
(46, 'noy', 'vot', 56, '2025-06-11', 'gheh', '2025-06-17', '2025-07-11', '1F-02FB', ''),
(47, 'jang', 'bot', 50, '2025-06-01', 'namunga', '2025-06-09', '2025-06-14', '1F-72FW', 'mamang'),
(48, 'Jovit', 'Baldevino', 43, '2000-04-12', 'Bawi, Padre Garcia, Batangas', '2024-08-29', '2024-08-29', 'null', 'Joseph Manalo'),
(49, 'JONG', 'MAMNU', 60, '1990-01-17', 'MABUNGA', '2025-06-04', '2025-06-21', '1F-72FB', 'NANA BELS'),
(50, 'haha', 'hh', 3, '2000-04-20', 'ahaha', '2025-06-04', '2025-07-05', '1F-01CB', 'sueue'),
(51, 'nana', 'ling', 23, '2025-06-01', 'mlbb', '2025-06-04', '2025-06-07', '1F-01AA', 'mino'),
(52, 'watosi', 'boy', 56, '2025-06-08', 'ewan', '2025-05-31', '2025-05-31', '1F-05FB', 'wala'),
(53, 'Lebron', 'James', 56, '2025-06-02', 'Kalumpit, Bulacan', '2023-11-11', '2023-11-11', '1F-12FB', 'Jason Titum'),
(54, 'watosi', 'boy', 56, '0000-00-00', 'ewan', '2025-05-31', '2025-05-31', '1F-16FB', 'wala'),
(55, 'J', 'REGALA', 59, '1898-04-20', 'Poblacion dos, Quezon, City', '2025-06-01', '2025-06-07', '1F-01FE', 'JOCO DIAZ'),
(56, 'NAMO', 'KA', 60, '2025-06-04', 'DWD', '2025-06-11', '2025-07-12', '1F-01FB', 'HAHAHA'),
(57, 'JANG', 'MONG', 56, '2025-06-12', 'HHAHA', '2025-06-11', '2025-06-21', '1F-09FB', 'NANAG'),
(58, 'HAHAAH', 'HAHAHA', 81, '2025-06-12', 'HAHAHA', '2025-07-12', '2025-07-03', '1F-13FB', 'HAHAHA'),
(59, 'Jovit', 'Baldevino', 43, '2000-04-12', 'Bawi, Padre Garcia, Batangas', '2024-08-29', '2024-08-29', 'null', 'Joseph Manalo'),
(60, 'Jovit', 'Baldevino', 43, '2000-04-12', 'Bawi, Padre Garcia, Batangas', '2024-08-29', '2024-08-29', '1F-17FB', 'Joseph Manalo'),
(66, 'J', 'REGALA', 59, '1898-04-20', 'Poblacion dos, Quezon, City', '2025-06-01', '2025-06-07', '1F-01FE', 'JOCO DIAZ'),
(67, 'f', 'f', 4, '2025-05-31', 'ha', '2025-05-31', '2025-05-15', '1F-36FB', 'haha'),
(68, 'jaja', 'jaja', 50, '2025-05-03', 'ena', '2025-05-15', '2025-05-31', '1F-02FA', 'haha'),
(69, 'hf', 'kd', 12, '2025-05-24', 'shsh', '2025-05-07', '2025-05-29', '1F-20FA', 'hss'),
(70, 'djdj', 'nccn', 10, '2025-05-29', 'haah', '2025-05-31', '2025-05-31', '1F-15FB', 'jssj'),
(71, 'jssj', 'snsn', 10, '2025-05-24', 'ahah', '2025-05-21', '2025-05-30', '1F-35FB', 'haah'),
(72, 'jsjs', 'kaka', 30, '2025-05-02', 'jfjf', '2025-05-22', '2025-06-04', '1F-39FB', 'sjsj'),
(73, 'hshs', 'bdb', 40, '2025-05-21', 'dmd', '2025-05-21', '2025-06-06', '1F-39FA', 'haha'),
(74, 'jang', 'seb', 54, '2025-06-03', 'manuka', '2025-06-03', '2025-06-04', '1F-08FB', 'nana'),
(75, 'Mico', 'The First', 100, '1990-06-24', 'Namunga, Rosario, Batangas', '2025-06-02', '2025-06-07', '1F-1AA', 'Balukot'),
(81, 'wa', 'boy', 56, '2004-02-20', 'ewan', '2025-05-31', '2025-05-31', '1F-29FB', 'wala'),
(82, 'ham', 'bol', 69, '2025-05-31', 'wala', '2025-05-31', '2025-05-31', '1F-04FB', 'kahit sino'),
(83, 'CHOOX', 'TV', 101, '1998-05-20', 'BIRINGAN CITY', '2025-05-31', '2025-06-01', '1F-04FA', 'AKOSIDOGIE'),
(84, 'JAMO', 'NAM', 60, '2025-06-01', 'NAMUNGA', '2025-06-12', '2025-07-05', '1F-01FB', 'JANG NY'),
(85, 'H', 'DA', 23, '2025-06-07', 'EWED', '2025-07-01', '2025-07-02', '1F-08FB', 'DFWD'),
(86, 'HANA', 'BANG', 13, '2025-06-05', 'ABABY', '2025-06-12', '2025-07-04', '1F-05FB', 'NGUB'),
(87, 'AHAHA', 'HAHAHA', 133, '2025-06-19', 'DDD', '2025-06-20', '2025-07-04', '1F-03FB', 'DD'),
(88, 'HANooo', 'BANG', 15, '2025-06-01', 'BAYAN', '2025-06-11', '2025-06-28', '1F-65FB', 'RYAN BANG'),
(89, 'JANA', 'BANA', 45, '2025-06-03', 'HAHA', '2025-06-11', '2025-07-05', '1F-02FB', 'HHAA'),
(90, 'JANA', 'BANA', 45, '2025-06-03', 'HAHA', '2025-06-11', '2025-07-05', '1F-04FB', 'HHAA'),
(91, 'Jovit', 'Baldevino', 43, '2000-04-12', 'Bawi, Padre Garcia, Batangas', '2024-08-29', '2024-08-29', 'null', 'Joseph Manalo'),
(92, 'Lebron', 'James', 56, '1969-02-11', 'Kalumpit, Bulacan', '2023-11-11', '2023-11-11', 'null', 'Jason Titum'),
(93, 'Jovit', 'Baldevino', 43, '2000-04-12', 'Bawi, Padre Garcia, Batangas', '2024-08-29', '2024-08-29', 'null', 'Joseph Manalo'),
(94, 'Lebron', 'James', 56, '1969-02-11', 'Kalumpit, Bulacan', '2023-11-11', '2023-11-11', 'null', 'Jason Titum'),
(95, 'HAHAw', 'HAHA', 50, '2025-07-03', 'HAHA', '2025-07-04', '2025-07-05', '1F-01FB', 'HAHA');

-- --------------------------------------------------------

--
-- Table structure for table `client_requests`
--

CREATE TABLE `client_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('Interment','Transfer','Exhumation') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `dob` varchar(50) NOT NULL,
  `dod` varchar(50) NOT NULL,
  `residency` varchar(150) NOT NULL,
  `informant_name` varchar(150) NOT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_requests`
--

INSERT INTO `client_requests` (`id`, `user_id`, `type`, `first_name`, `last_name`, `middle_name`, `age`, `dob`, `dod`, `residency`, `informant_name`, `file_upload`, `created_at`) VALUES
(26, 47, 'Transfer', 'HAHA', 'ahha', 'hahah', 19, '2025-07-01', '2025-07-17', 'haha', 'haha', '1752756010_IMG_20250714_121530_129.jpg', '2025-07-17 12:40:10');

-- --------------------------------------------------------

--
-- Table structure for table `deceased`
--

CREATE TABLE `deceased` (
  `id` int(11) NOT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `born` date DEFAULT NULL,
  `residency` varchar(255) DEFAULT NULL,
  `dateDied` date DEFAULT NULL,
  `dateInternment` date DEFAULT NULL,
  `nicheID` varchar(50) DEFAULT NULL,
  `informantName` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deceased`
--

INSERT INTO `deceased` (`id`, `firstName`, `lastName`, `age`, `born`, `residency`, `dateDied`, `dateInternment`, `nicheID`, `informantName`) VALUES
(74, 'HAHAHA', 'HAHAA', 35, '2025-07-12', 'HAHAHA', '2025-07-12', '2025-07-19', '1F-10FB', 'HAHAHA'),
(75, 'yami', 'maho', 50, '2025-07-01', 'nambu', '2025-07-15', '2025-07-18', '1F-69FA', 'asta');

-- --------------------------------------------------------

--
-- Table structure for table `denied_request`
--

CREATE TABLE `denied_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('Interment','Transfer','Exhumation') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `dob` varchar(50) NOT NULL,
  `dod` varchar(50) NOT NULL,
  `residency` varchar(150) NOT NULL,
  `informant_name` varchar(150) NOT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `denied_request`
--

INSERT INTO `denied_request` (`id`, `user_id`, `type`, `first_name`, `last_name`, `middle_name`, `age`, `dob`, `dod`, `residency`, `informant_name`, `file_upload`, `created_at`) VALUES
(2, 51, 'Transfer', 'Jaja', 'sayo', 'moni', 25, '2025-07-01', '2025-07-15', 'bahay', 'monic', '1752579508_IMG_20250713_170057_932.jpg', '2025-07-15 11:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(11) NOT NULL,
  `ApartmentNo` varchar(50) NOT NULL,
  `DatePaid` date NOT NULL,
  `Payee` varchar(100) NOT NULL,
  `Amount` decimal(12,2) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `ORNumber` varchar(50) DEFAULT NULL,
  `Validity` date DEFAULT NULL,
  `MCNo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ledger`
--

INSERT INTO `ledger` (`id`, `ApartmentNo`, `DatePaid`, `Payee`, `Amount`, `Description`, `ORNumber`, `Validity`, `MCNo`) VALUES
(3272, '1F-01AA', '2025-06-11', 'BONG BONG MARCOS', 1000000.00, 'PAID', '12WER', '2025-07-11', 'QWER12'),
(3273, '1', '2025-06-11', 'W', 11111.00, 'PAID', '1WWQ', '2025-06-11', 'EEEE'),
(3274, '1', '2025-06-11', 'W', 11111.00, 'PAID', '1WWQ', '2025-06-11', 'EEEE'),
(3275, '1', '2025-06-11', 'W', 11111.00, 'PAID', '1WWQ', '2025-06-11', 'EEEE'),
(3276, '122', '2025-06-19', 'FF', 333.00, 'FFF', 'FFF', '2025-07-11', 'FFF'),
(3277, 'QWE12', '2025-07-05', 'DW', 2313131.00, 'WDFDF', '2EWEW', '2025-07-10', 'DWEW'),
(3278, 'ADA', '2025-07-12', 'FEFE', 12234.00, 'WF', '23E231', '2025-06-24', 'WWW'),
(3279, '1223', '2025-06-11', 'EWW', 123.00, 'WFEWWE', 'WRFD', '0000-00-00', '133DS'),
(3280, 'QWWEW', '2025-06-11', 'QEQEQ', 12121.00, 'QEWW', '1W1W1', '2025-06-23', '1W1WW'),
(3281, '123242', '2025-06-11', 'QDQDQ', 1312.00, 'QDQDQ', 'QDQDQ', '2025-07-10', 'QQDQD'),
(3282, 'Q1EW', '2025-06-12', 'WDWDW', 11.00, 'WDDW', '1WQQS', '2025-07-03', 'qQDDW'),
(3283, 'SFW', '2025-06-12', '1211', 21313.00, 'WR3R', 'WWW', '2025-06-25', 'FWW'),
(3284, 'HAHA', '2025-06-12', 'HAHA', 11.00, 'WDWDW', '11W', '2025-06-26', 'DWDWD');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_no` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `contact_no`, `password`, `created_at`, `reset_code`, `reset_expires`) VALUES
(47, 'Denver', 'Salazar', 'denversalazar24@gmail.com', '09859822196', '$2y$10$pKYBYj2DvTvr1OzAgmTf6ObC6EhQGDTbDKiqycr.h3XkWYUytiGm6', '2025-07-07 06:33:54', NULL, NULL),
(48, 'Alleon', 'Perez', 'alleonperez@gmail.com', '09859822196', '$2y$10$WkGZ3Y56NNvd9wyuYXNGGePSNvBJub6nhXcODLEMAWopMh00LYl0O', '2025-07-07 07:03:16', NULL, NULL),
(50, 'Jung', 'Kook', 'jung@gmail.com', '09859822196', '$2y$10$aAJ5JQ8iSoc3kJ2/7Cd35O9TDyEU0tZUntHMyHQI4lLF1BE0Hcs9G', '2025-07-07 07:44:24', NULL, NULL),
(51, 'Jam', 'poul', 'jam@gmail.com', '09859822196', '$2y$10$4ouK44mxlCsSCme1ESaqG.KgFipb9OXmD0N94po5TIUwtGTRxuTV2', '2025-07-15 09:25:57', NULL, NULL),
(52, 'John', 'Regala', 'john@gmail.com', '09859822196', '$2y$10$SVB0hv7K9zFGiA/zGN3rK.DcmjwoiF2BfG3aj9k4DebCsnlM22p/S', '2025-07-17 12:24:15', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accepted_request`
--
ALTER TABLE `accepted_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `archive_clients`
--
ALTER TABLE `archive_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archive_deceased`
--
ALTER TABLE `archive_deceased`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_requests`
--
ALTER TABLE `client_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `deceased`
--
ALTER TABLE `deceased`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `denied_request`
--
ALTER TABLE `denied_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accepted_request`
--
ALTER TABLE `accepted_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `archive_clients`
--
ALTER TABLE `archive_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `archive_deceased`
--
ALTER TABLE `archive_deceased`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `client_requests`
--
ALTER TABLE `client_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `deceased`
--
ALTER TABLE `deceased`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `denied_request`
--
ALTER TABLE `denied_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3285;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client_requests`
--
ALTER TABLE `client_requests`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
