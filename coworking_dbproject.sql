-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 10:38 AM
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
  `status` enum('pending checkin','active','completed','checkout late','cancelled') DEFAULT 'pending checkin',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `checkin_time` datetime DEFAULT NULL,
  `checkout_time` datetime DEFAULT NULL,
  `booking_type` enum('slot','week','month','year') NOT NULL DEFAULT 'slot',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `customer_id`, `workspace_id`, `staff_id`, `booking_date`, `booking_token`, `status`, `start_time`, `end_time`, `checkin_time`, `checkout_time`, `booking_type`, `total_price`, `notes`, `created_at`, `updated_at`) VALUES
(58, 4, 33, NULL, '2026-06-10', 'BK-20260610-03D5D', 'pending checkin', '2026-06-10 10:00:00', '2026-06-17 10:00:00', NULL, NULL, 'week', 400.00, '', '2026-06-11 03:28:18', '2026-06-11 03:28:18'),
(59, 4, 32, NULL, '2026-06-11', 'BK-20260610-7D782', 'pending checkin', '2026-06-11 10:00:00', '2026-07-11 10:00:00', NULL, NULL, 'month', 1000.00, '', '2026-06-11 03:28:47', '2026-06-11 03:28:47'),
(60, 4, 31, NULL, '2026-06-10', 'BK-20260610-6DA5B', 'active', '2026-06-10 10:00:00', '2026-07-10 10:00:00', NULL, NULL, 'month', 1000.00, '', '2026-06-11 03:29:11', '2026-06-11 03:45:37'),
(61, 4, 34, NULL, '2026-06-10', 'BK-20260610-F3EEE', 'completed', '2026-06-10 10:00:00', '2026-07-10 10:00:00', NULL, NULL, 'month', 1000.00, '', '2026-06-11 03:29:52', '2026-06-11 03:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `fullname`, `email`, `password`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(4, 'MAYI', 'mayi3@gmail.com', '$2y$10$AX6R4w2V00QgkxbzyCik.eQF5LNsgd6wiA33X8WKS2zp7uge3Yete', '0178492625', 'active', '2026-06-11 00:06:24', '2026-06-11 00:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'staff',
  `phone` varchar(15) NOT NULL DEFAULT '',
  `promoted_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `fullname`, `email`, `password`, `role`, `phone`, `promoted_by`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@cowork.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', '0123456789', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(2, 'Ma', 'mayi2@gmail.com', '$2y$10$kNQwRBcaNMEmKnDkA7hBuu9TVhwkVwCQ4MuLRVEQrENIECEB0omDa', 'staff', '0178492625', 1, '2026-06-10 14:41:08', '2026-06-10 14:41:08');

-- --------------------------------------------------------

--
-- Table structure for table `workspace`
--

CREATE TABLE `workspace` (
  `workspace_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `workspace_name` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `added_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workspace`
--

INSERT INTO `workspace` (`workspace_id`, `zone_id`, `workspace_name`, `capacity`, `status`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'S01', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(2, 1, 'S02', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:42:16'),
(3, 1, 'S03', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(4, 1, 'S04', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(5, 1, 'S05', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(6, 1, 'S06', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(7, 1, 'S07', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(8, 1, 'S08', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(9, 1, 'S09', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(10, 1, 'S10', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(11, 1, 'S11', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(12, 1, 'S12', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(13, 1, 'S13', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(14, 1, 'S14', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(15, 1, 'S15', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(16, 1, 'S16', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(17, 1, 'S17', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(18, 1, 'S18', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(19, 1, 'S19', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(20, 1, 'S20', 1, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(21, 2, 'D01', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(22, 2, 'D02', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(23, 2, 'D03', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(24, 2, 'D04', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(25, 2, 'D05', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 17:10:28'),
(26, 2, 'D06', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(27, 2, 'D07', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(28, 2, 'D08', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(29, 2, 'D09', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(30, 2, 'D10', 6, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(31, 3, 'O01', 10, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(32, 3, 'O02', 10, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(33, 3, 'O03', 10, 'available', NULL, '2026-06-10 14:35:10', '2026-06-10 14:35:10'),
(34, 3, 'O04', 10, 'available', NULL, '2026-06-10 14:35:10', '2026-06-11 03:47:53'),
(35, 3, 'O05', 10, 'available', NULL, '2026-06-10 14:35:10', '2026-06-11 03:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `zone`
--

CREATE TABLE `zone` (
  `zone_id` int(11) NOT NULL,
  `zone_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `floor` tinyint(4) NOT NULL DEFAULT 1,
  `capacity` tinyint(4) NOT NULL DEFAULT 1,
  `price_slot` decimal(10,2) DEFAULT NULL COMMENT 'price per 4-hr slot',
  `price_week` decimal(10,2) DEFAULT NULL,
  `price_month` decimal(10,2) DEFAULT NULL,
  `price_year` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `zone`
--

INSERT INTO `zone` (`zone_id`, `zone_name`, `description`, `floor`, `capacity`, `price_slot`, `price_week`, `price_month`, `price_year`) VALUES
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
  ADD KEY `idx_booking_status` (`status`),
  ADD KEY `idx_booking_times` (`start_time`,`end_time`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `promoted_by` (`promoted_by`);

--
-- Indexes for table `workspace`
--
ALTER TABLE `workspace`
  ADD PRIMARY KEY (`workspace_id`),
  ADD UNIQUE KEY `workspace_name` (`workspace_name`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_workspace_zone` (`zone_id`),
  ADD KEY `idx_workspace_status` (`status`);

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
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workspace`
--
ALTER TABLE `workspace`
  MODIFY `workspace_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

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
