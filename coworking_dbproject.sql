-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 10:31 AM
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
-- Database: `coworking_dbproject`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `workspace_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_token` varchar(50) NOT NULL,
  `booking_status` enum('pending checkin','active','completed','checkout late','cancelled') DEFAULT 'pending checkin',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `checkin_time` datetime DEFAULT NULL,
  `checkout_time` datetime DEFAULT NULL,
  `booking_type` enum('slot','week','month','year') NOT NULL DEFAULT 'slot',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `booking_notes` text DEFAULT NULL,
  `booking_created_at` datetime DEFAULT current_timestamp(),
  `booking_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `customer_id`, `workspace_id`, `staff_id`, `booking_date`, `booking_token`, `booking_status`, `start_time`, `end_time`, `checkin_time`, `checkout_time`, `booking_type`, `total_price`, `booking_notes`, `booking_created_at`, `booking_updated_at`) VALUES
(58, 4, 33, NULL, '2026-06-10', 'BK-20260610-03D5D', 'active', '2026-06-10 10:00:00', '2026-06-17 10:00:00', NULL, NULL, 'week', 400.00, '', '2026-06-11 03:28:18', '2026-06-12 02:02:53'),
(59, 4, 32, NULL, '2026-06-11', 'BK-20260610-7D782', 'active', '2026-06-11 10:00:00', '2026-07-11 10:00:00', NULL, NULL, 'month', 1000.00, '', '2026-06-11 03:28:47', '2026-06-12 02:07:59'),
(60, 4, 31, NULL, '2026-06-10', 'BK-20260610-6DA5B', 'active', '2026-06-10 10:00:00', '2026-07-10 10:00:00', '2026-06-12 02:14:12', NULL, 'month', 1000.00, '', '2026-06-11 03:29:11', '2026-06-12 02:14:12'),
(61, 4, 34, NULL, '2026-06-10', 'BK-20260610-F3EEE', 'completed', '2026-06-10 10:00:00', '2026-07-10 10:00:00', NULL, NULL, 'month', 1000.00, '', '2026-06-11 03:29:52', '2026-06-11 03:33:46'),
(63, 4, 1, NULL, '2026-06-11', 'BK-20260611-34303', 'completed', '2026-06-11 13:30:00', '2026-06-11 17:30:00', NULL, NULL, 'slot', 10.00, '', '2026-06-11 17:15:18', '2026-06-11 17:17:52'),
(64, 4, 1, NULL, '2026-06-11', 'BK-20260611-B0A80', 'completed', '2026-06-11 13:30:00', '2026-06-11 17:30:00', NULL, NULL, 'slot', 10.00, '', '2026-06-11 17:18:31', '2026-06-11 17:53:05'),
(65, 4, 1, NULL, '2026-06-11', 'BK-20260611-832D8', 'completed', '2026-06-11 14:00:00', '2026-06-11 18:00:00', NULL, NULL, 'slot', 10.00, '\n[System: Automatically completed - No check-in recorded by slot end time.]', '2026-06-11 17:59:38', '2026-06-11 18:00:18'),
(66, 4, 1, NULL, '2026-06-11', 'BK-20260611-5A76A', 'checkout late', '2026-06-11 14:30:00', '2026-06-11 18:30:00', NULL, NULL, 'slot', 13.00, '[System: Late check-out by 38 mins. Charged extra RM 3.00]', '2026-06-11 18:00:46', '2026-06-11 19:07:51'),
(67, 10, 21, NULL, '2026-06-12', 'BK-20260612-26C5E', 'active', '2026-06-12 10:00:00', '2026-06-12 14:00:00', '2026-06-12 09:05:21', NULL, 'slot', 30.00, '', '2026-06-12 09:00:08', '2026-06-12 09:05:21');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `customer_fullname` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_password` varchar(255) NOT NULL,
  `customer_phone` varchar(15) NOT NULL,
  `customer_status` enum('active','suspended') DEFAULT 'active',
  `customer_created_at` datetime DEFAULT current_timestamp(),
  `customer_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `customer_fullname`, `customer_email`, `customer_password`, `customer_phone`, `customer_status`, `customer_created_at`, `customer_updated_at`) VALUES
(4, 'MAYI', 'mayi3@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2zp7uge3Yete', '0178492625', 'active', '2026-06-11 00:06:24', '2026-06-11 00:35:22'),
(6, 'Hi', 'hi@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2z', '0172223344', 'active', '2026-06-11 17:44:36', '2026-06-11 17:44:36'),
(7, 'Kaimi', 'kaimi@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2z', '0173334455', 'active', '2026-06-11 17:44:36', '2026-06-11 17:44:36'),
(8, 'Jingen', 'jingen@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2z', '0174445566', 'active', '2026-06-11 17:44:36', '2026-06-11 17:44:36'),
(9, 'CELESTINA', 'celestinaong@gmail.com', '$2y$10$E32q9fnwMBqOKhnKYkm7.Oqc5szwsQOr5tciyt/7o13vXFuqoGLJy', '01110653827', 'active', '2026-06-12 08:40:09', '2026-06-12 09:07:00'),
(10, 'mayi', 'mayi@gmail.com', '$2y$10$fweKtu1/37sngwQ7Qd/pbeG.ybmXA/nsNc5XSgfIemKILzocyzeza', '01110653827', 'active', '2026-06-12 08:58:18', '2026-06-12 08:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `staff_fullname` varchar(100) NOT NULL,
  `staff_email` varchar(100) NOT NULL,
  `staff_password` varchar(255) NOT NULL,
  `staff_role` varchar(50) NOT NULL DEFAULT 'staff',
  `staff_phone` varchar(15) NOT NULL DEFAULT '',
  `promoted_by` int(11) DEFAULT NULL,
  `staff_created_at` datetime DEFAULT current_timestamp(),
  `staff_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `staff_fullname`, `staff_email`, `staff_password`, `staff_role`, `staff_phone`, `promoted_by`, `staff_created_at`, `staff_updated_at`) VALUES
(1, 'Super Admin', 'admin@cowork.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', '0123456789', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(2, 'Ma', 'mayi2@gmail.com', '$2y$10$kNQwRBcaNMEmKnDkA7hBuu9TVhwkVwCQ4MuLRVEQrENIECEB0omDa', 'staff', '0178492625', 1, '2026-06-10 14:41:08', '2026-06-10 14:41:08'),
(3, 'Tina', 'tina@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2z', 'staff', '0154321321', 1, '2026-06-11 17:48:25', '2026-06-11 17:48:25'),
(4, 'Je', 'je@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2z', 'staff', '01765432189', 1, '2026-06-11 17:48:25', '2026-06-11 17:48:25'),
(5, 'Km', 'km@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2z', 'staff', '0177894561', 1, '2026-06-11 17:48:25', '2026-06-11 17:48:25');

-- --------------------------------------------------------

--
-- Table structure for table `workspace`
--

CREATE TABLE `workspace` (
  `workspace_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `workspace_name` varchar(10) NOT NULL,
  `workspace_status` varchar(20) NOT NULL DEFAULT 'available',
  `added_by` int(11) DEFAULT NULL,
  `workspace_created_at` datetime DEFAULT current_timestamp(),
  `workspace_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workspace`
--

INSERT INTO `workspace` (`workspace_id`, `zone_id`, `workspace_name`, `workspace_status`, `added_by`, `workspace_created_at`, `workspace_updated_at`) VALUES
(1, 1, 'S22', 'available', NULL, '2026-06-10 14:35:10', '2026-06-12 09:03:27'),
(2, 1, 'S02', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:42:16'),
(3, 1, 'S03', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(4, 1, 'S04', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(5, 1, 'S05', 'available', NULL, '2026-06-10 14:35:10', '2026-06-11 17:00:57'),
(6, 1, 'S06', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(7, 1, 'S07', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(8, 1, 'S08', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(9, 1, 'S09', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(10, 1, 'S10', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(11, 1, 'S11', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(12, 1, 'S12', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(13, 1, 'S13', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(14, 1, 'S14', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(15, 1, 'S15', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(16, 1, 'S16', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(17, 1, 'S17', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(18, 1, 'S18', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(19, 1, 'S19', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(20, 1, 'S20', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(21, 2, 'D01', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(22, 2, 'D02', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(23, 2, 'D03', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(24, 2, 'D04', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(25, 2, 'D05', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 17:10:28'),
(26, 2, 'D06', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(27, 2, 'D07', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(28, 2, 'D08', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(29, 2, 'D09', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(30, 2, 'D10', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(31, 3, 'O01', 'available', NULL, '2026-06-10 14:35:10', '2026-06-12 08:09:20'),
(32, 3, 'O02', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(33, 3, 'O03', 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(34, 3, 'O04', 'available', NULL, '2026-06-10 14:35:10', '2026-06-11 03:47:53'),
(35, 3, 'O05', 'available', NULL, '2026-06-10 14:35:10', '2026-06-11 03:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `zone`
--

CREATE TABLE `zone` (
  `zone_id` int(11) NOT NULL,
  `zone_name` varchar(50) NOT NULL,
  `zone_description` text DEFAULT NULL,
  `zone_floor` tinyint(4) NOT NULL DEFAULT 1,
  `zone_capacity` tinyint(4) NOT NULL DEFAULT 1,
  `price_slot` decimal(10,2) DEFAULT NULL COMMENT 'price per 4-hr slot',
  `price_week` decimal(10,2) DEFAULT NULL,
  `price_month` decimal(10,2) DEFAULT NULL,
  `price_year` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `zone`
--

INSERT INTO `zone` (`zone_id`, `zone_name`, `zone_description`, `zone_floor`, `zone_capacity`, `price_slot`, `price_week`, `price_month`, `price_year`) VALUES
(1, 'Single Room', 'A private quiet desk for solo focused work. Ergonomic chair, dedicated power outlet, natural lighting.', 1, 1, 10.00, NULL, NULL, NULL),
(2, 'Discussion Room', 'Sound-managed meeting room for small teams. Whiteboard, projector-ready wall, seats up to 6.', 2, 6, 30.00, NULL, NULL, NULL),
(3, 'Private Office', 'Fully enclosed private office with lockable door, dedicated internet line, and storage cabinet.', 3, 10, NULL, 400.00, 1000.00, 11000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_token` (`booking_token`),
  ADD KEY `idx_booking_customer` (`customer_id`),
  ADD KEY `idx_booking_workspace` (`workspace_id`),
  ADD KEY `idx_booking_staff` (`staff_id`),
  ADD KEY `idx_booking_status` (`booking_status`),
  ADD KEY `idx_booking_times` (`start_time`,`end_time`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_email` (`customer_email`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `staff_email` (`staff_email`),
  ADD KEY `promoted_by` (`promoted_by`);

--
-- Indexes for table `workspace`
--
ALTER TABLE `workspace`
  ADD PRIMARY KEY (`workspace_id`),
  ADD UNIQUE KEY `workspace_name` (`workspace_name`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_workspace_zone` (`zone_id`),
  ADD KEY `idx_workspace_status` (`workspace_status`);

--
-- Indexes for table `zone`
--
ALTER TABLE `zone`
  ADD PRIMARY KEY (`zone_id`),
  ADD UNIQUE KEY `zone_name` (`zone_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `workspace`
--
ALTER TABLE `workspace`
  MODIFY `workspace_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `zone`
--
ALTER TABLE `zone`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`workspace_id`) REFERENCES `workspace` (`workspace_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`promoted_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `workspace`
--
ALTER TABLE `workspace`
  ADD CONSTRAINT `workspace_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`),
  ADD CONSTRAINT `workspace_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;