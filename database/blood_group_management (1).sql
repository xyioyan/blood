-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 02:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `blood_group_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAvailableDonorsByBloodGroup` (IN `bloodGroup` VARCHAR(5))   BEGIN
    SELECT s.*,
           DATEDIFF(CURDATE(), COALESCE(s.last_donation_date, '2000-01-01')) as days_since_last_donation
    FROM students s
    WHERE s.blood_group = bloodGroup
    AND s.is_available = 1
    AND (s.last_donation_date IS NULL OR DATEDIFF(CURDATE(), s.last_donation_date) >= 90)
    ORDER BY s.full_name;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `role`, `is_active`, `remember_token`, `token_expiry`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$4jl5pXe/4R13/eWMgqD3Q.X2m1F8EI3N4hmLAd.Y5D4bSvX3i5o6q', 'System Administrator', 'superadmin', 1, NULL, NULL, '2025-09-09 23:35:32', '2025-08-04 15:28:33', '2025-09-09 18:05:32');

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE `blood_requests` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `contact_name` varchar(100) NOT NULL,
  `contact_phone` varchar(15) NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_needed` int(11) NOT NULL DEFAULT 1,
  `hospital_name` varchar(100) NOT NULL,
  `hospital_address` text NOT NULL,
  `urgency` enum('Critical','High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `additional_info` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `requested_by` int(11) DEFAULT NULL COMMENT 'Student ID if logged in',
  `requested_ip` varchar(45) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL COMMENT 'Admin ID who approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`id`, `patient_name`, `contact_name`, `contact_phone`, `blood_group`, `units_needed`, `hospital_name`, `hospital_address`, `urgency`, `additional_info`, `status`, `requested_by`, `requested_ip`, `admin_notes`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'Mary Smith', '9845012345', 'A+', 2, 'City General Hospital', '123 Main Street, City Center', 'Critical', 'Emergency surgery required', 'pending', NULL, NULL, '', 1, '2025-09-09 16:57:36', '2025-09-09 18:27:40'),
(2, 'Sarah Johnson', 'David Johnson', '9845567890', 'B+', 1, 'Central Medical Center', '456 Oak Avenue, Downtown', 'High', 'Cancer treatment', 'rejected', NULL, NULL, 'the detatils are not enough', 1, '2025-09-09 16:57:36', '2025-09-09 18:33:47'),
(3, 'Robert Brown', 'Emily Brown', '9845513579', 'AB+', 3, 'Westside Hospital', '789 Pine Road, West District', 'Medium', 'Incomplete information provided', 'approved', NULL, NULL, '', 1, '2025-09-09 16:57:36', '2025-09-09 18:33:57');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `donation_date` date NOT NULL,
  `recipient_details` text DEFAULT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `donation_date`, `recipient_details`, `event_name`, `event_id`, `request_id`) VALUES
(1, 3, '2024-11-12', 'City Hospital – Emergency Case', 'Blood Drive 2024', NULL, NULL),
(2, 4, '2024-10-05', 'Local Clinic – Surgery Patient', 'Engineering Blood Camp', NULL, NULL),
(3, 6, '2025-01-20', 'St. Mary’s Hospital – Accident Victim', 'Annual College Camp', NULL, NULL),
(4, 26, '2025-08-10', NULL, NULL, 6, NULL),
(5, 2, '2025-08-11', NULL, NULL, 6, NULL),
(6, 3, '2025-08-11', NULL, 'College Annual Blood Camp', 6, NULL),
(7, 20, '2025-08-17', NULL, 'Festival Blood Camp Test', 5, NULL),
(8, 9, '2025-08-17', NULL, 'Festival Blood Camp Test', 5, NULL),
(9, 2, '2025-08-17', NULL, 'Festival Blood Camp Test', 5, NULL),
(10, 1, '2025-08-24', NULL, 'Festival Blood Camp Test', 5, NULL),
(11, 1, '2025-09-05', NULL, 'Engineering Blood Donation', 3, NULL);

--
-- Triggers `donations`
--
DELIMITER $$
CREATE TRIGGER `update_last_donation_after_insert` AFTER INSERT ON `donations` FOR EACH ROW BEGIN
    UPDATE students
    SET last_donation_date = (
        SELECT MAX(donation_date) FROM donations WHERE donor_id = NEW.donor_id
    )
    WHERE id = NEW.donor_id;
    
    -- Update blood request status if this donation fulfills a request
    IF NEW.request_id IS NOT NULL THEN
        UPDATE blood_requests br
        SET status = 'completed'
        WHERE br.id = NEW.request_id
        AND (SELECT COALESCE(SUM(units_donated), 0) FROM donation_fulfillments WHERE request_id = br.id) >= br.units_needed;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_last_donation_after_update` AFTER UPDATE ON `donations` FOR EACH ROW BEGIN
    UPDATE students
    SET last_donation_date = (
        SELECT MAX(donation_date) FROM donations WHERE donor_id = NEW.donor_id
    )
    WHERE id = NEW.donor_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `donation_events`
--

CREATE TABLE `donation_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `target_donors` int(11) NOT NULL,
  `target_blood_groups` varchar(100) DEFAULT NULL,
  `target_departments` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation_events`
--

INSERT INTO `donation_events` (`id`, `event_name`, `event_date`, `start_time`, `end_time`, `location`, `target_donors`, `target_blood_groups`, `target_departments`, `description`, `created_at`, `updated_at`) VALUES
(1, 'College Annual Blood Camp', '2025-08-11', '08:00:00', '17:00:00', 'Main Auditorium', 100, 'B-,AB+,AB-,O+', 'Civil,Civil Engineering,Computer Science', 'Annual camp for all students.', '2025-08-10 09:48:07', '2025-08-10 09:48:07'),
(2, 'Freshers Welcome Blood Drive', '2025-09-05', '10:00:00', '14:00:00', 'Sports Complex', 75, 'O-, A-', 'First Year Students', 'Welcome event + donation camp.', '2025-08-10 09:51:45', '2025-08-10 09:51:45'),
(3, 'Engineering Blood Donation', '2025-09-15', '08:30:00', '12:30:00', 'Engineering Block Hall', 80, 'All', 'Civil, Mechanical, Electrical', 'Organized by engineering dept.', '2025-08-10 09:51:45', '2025-08-10 09:51:45'),
(4, 'Medical Check & Blood Drive', '2025-10-02', '09:00:00', '15:00:00', 'Health Center', 120, 'B+, AB+', 'Nursing, Pharmacy', 'Includes free health check-up.', '2025-08-10 09:51:45', '2025-08-10 09:51:45'),
(5, 'Festival Blood Camp Test', '2025-11-01', '10:00:00', '16:00:00', 'Central Grounds', 150, '[]', '[]', 'Part of cultural festival.', '2025-08-10 09:51:45', '2025-08-17 13:54:42'),
(6, 'College Annual Blood Camp', '2025-08-27', '09:30:00', '12:00:00', 'Main Auditorium', 20, 'A-,B-,O+', 'Civil Engineering,cse,Mechanical', 'Annual camp for all students', '2025-08-10 12:00:52', '2025-08-10 12:00:52');

-- --------------------------------------------------------

--
-- Table structure for table `donation_fulfillments`
--

CREATE TABLE `donation_fulfillments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `units_donated` int(11) NOT NULL DEFAULT 1,
  `donation_date` datetime NOT NULL,
  `verified_by` int(11) DEFAULT NULL COMMENT 'Admin or hospital staff who verified',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation_fulfillments`
--

INSERT INTO `donation_fulfillments` (`id`, `request_id`, `donor_id`, `units_donated`, `donation_date`, `verified_by`, `notes`, `created_at`) VALUES
(1, 1, 3, 1, '2025-09-07 10:30:00', 1, NULL, '2025-09-09 16:57:36'),
(2, 1, 8, 1, '2025-09-07 11:15:00', 1, NULL, '2025-09-09 16:57:36'),
(3, 2, 4, 1, '2025-09-07 14:20:00', 1, NULL, '2025-09-09 16:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance`
--

CREATE TABLE `event_attendance` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `checked_in_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_attendance`
--

INSERT INTO `event_attendance` (`id`, `event_id`, `student_id`, `checked_in_at`) VALUES
(1, 6, 26, '2025-08-10 22:11:52'),
(2, 6, 2, '2025-08-11 22:12:45'),
(3, 6, 3, '2025-08-11 22:33:11'),
(4, 5, 20, '2025-08-17 22:28:56'),
(5, 5, 9, '2025-08-17 22:41:26'),
(6, 5, 2, '2025-08-17 22:41:38'),
(7, 5, 1, '2025-08-24 16:18:55'),
(8, 3, 1, '2025-09-06 19:26:11');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `registered_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `student_id`, `registered_at`) VALUES
(7, 6, 26, '2025-08-10 21:00:08'),
(8, 1, 26, '2025-08-10 21:05:12'),
(10, 5, 2, '2025-08-17 17:57:06'),
(11, 5, 20, '2025-08-17 22:28:55'),
(12, 5, 9, '2025-08-17 22:41:26'),
(13, 5, 1, '2025-08-24 16:18:54'),
(14, 3, 1, '2025-08-28 13:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `successful` tinyint(1) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `successful`, `attempt_time`) VALUES
(1, 'admin', '::1', 0, '2025-08-04 15:29:31'),
(2, '21cs056', '::1', 0, '2025-08-04 15:30:32'),
(3, 'admin', '::1', 0, '2025-08-04 15:30:49'),
(4, 'admin', '::1', 0, '2025-08-04 15:31:55'),
(5, 'admin', '::1', 1, '2025-08-04 15:33:01'),
(6, 'admin', '::1', 1, '2025-08-10 03:27:40'),
(7, 'admin', '::1', 1, '2025-08-10 04:04:00'),
(8, 'admin', '::1', 1, '2025-08-10 04:05:18'),
(9, 'admin', '::1', 1, '2025-08-10 04:08:34'),
(10, 'admin', '::1', 1, '2025-08-10 04:09:11'),
(11, 'admin', '::1', 1, '2025-08-10 04:28:48'),
(12, 'admin', '::1', 1, '2025-08-10 06:21:41'),
(13, 'admin', '::1', 1, '2025-08-10 16:26:41'),
(14, 'admin', '::1', 1, '2025-08-11 15:48:14'),
(15, 'admin', '::1', 1, '2025-08-17 12:43:29'),
(16, 'admin', '::1', 1, '2025-08-18 00:58:56'),
(17, 'admin', '::1', 1, '2025-08-24 10:45:53'),
(18, 'admin', '::1', 0, '2025-08-24 11:11:24'),
(19, 'admin', '::1', 1, '2025-08-24 11:11:37'),
(20, 'admin', '::1', 1, '2025-08-24 13:55:00'),
(21, 'admin', '::1', 1, '2025-09-06 13:42:12'),
(22, '21cs056', '::1', 0, '2025-09-06 13:47:12'),
(23, 'admin', '::1', 1, '2025-09-06 13:47:20'),
(24, 'admin', '::1', 1, '2025-09-06 16:21:41'),
(25, 'admin', '::1', 1, '2025-09-07 14:56:10'),
(26, 'admin', '::1', 1, '2025-09-07 16:40:19'),
(27, 'admin', '::1', 1, '2025-09-07 17:07:49'),
(28, 'admin', '::1', 1, '2025-09-09 18:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`, `expires_at`) VALUES
('aruna@gmail.com', '8b6718ca27678cdf84bff0144aa28e605a99f35b1ab0940994d63be23250c71b', NULL, '2025-07-23 03:14:32'),
('raj@gmail.com', 'd9b64fcf17e0089cf03a47783c1f277324d7427d4f0c54f2e906ee5a670a9420', NULL, '2025-07-23 03:17:12'),
('alice.johnson@example.com', 'token123', '2025-08-04 15:25:03', '2025-08-04 16:25:03'),
('bob.smith@example.com', 'token456', '2025-08-04 15:25:03', '2025-08-04 16:25:03');

-- --------------------------------------------------------

--
-- Stand-in structure for view `pending_blood_requests`
-- (See below for the actual view)
--
CREATE TABLE `pending_blood_requests` (
`id` int(11)
,`patient_name` varchar(100)
,`contact_name` varchar(100)
,`contact_phone` varchar(15)
,`blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-')
,`units_needed` int(11)
,`hospital_name` varchar(100)
,`hospital_address` text
,`urgency` enum('Critical','High','Medium','Low')
,`additional_info` text
,`status` enum('pending','approved','rejected','completed')
,`requested_by` int(11)
,`requested_ip` varchar(45)
,`admin_notes` text
,`approved_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`approved_by_name` varchar(100)
,`requested_by_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `department` varchar(50) NOT NULL,
  `year_of_study` int(11) NOT NULL,
  `last_donation_date` date DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `full_name`, `email`, `phone`, `blood_group`, `department`, `year_of_study`, `last_donation_date`, `is_available`, `password`, `created_at`) VALUES
(1, '21cs056', 'aruna', 'aruna@gmail.com', '9087564378', 'O+', 'cse', 2, '2025-09-05', 0, '$2y$10$bED4mHyMqAfFwX5OQZg06uIbMNGkn4zOak683VWK/SSGAEuSVzQUq', '2025-07-22 16:43:52'),
(2, '21cs057', 'raj', 'raj@gmail.com', '6378965445', 'A+', 'mech', 3, NULL, 1, '$2y$10$2CAvk9vJOi2bnxM2uQRPSOSjJTYw91npgQwlgK1PDciFrOYElGbd6', '2025-07-23 00:55:24'),
(3, 'S1001', 'Alice Johnson', 'alice.johnson@example.com', '9876543210', 'A+', 'Computer Science', 2, '2024-11-12', 1, '$2y$10$2CAvk9vJOi2bnxM2uQRPSOSjJTYw91npgQwlgK1PDciFrOYElGbd6', '2025-08-04 15:25:03'),
(4, 'S1002', 'Bob Smith', 'bob.smith@example.com', '9876543211', 'B+', 'Mechanical', 3, '2024-10-05', 1, '$2y$10$2CAvk9vJOi2bnxM2uQRPSOSjJTYw91npgQwlgK1PDciFrOYElGbd6', '2025-08-04 15:25:03'),
(5, 'S1003', 'Clara Lee', 'clara.lee@example.com', '9876543212', 'O-', 'Electrical', 1, NULL, 1, '$2y$10$2CAvk9vJOi2bnxM2uQRPSOSjJTYw91npgQwlgK1PDciFrOYElGbd6', '2025-08-04 15:25:03'),
(6, 'S1004', 'David Kim', 'david.kim@example.com', '9876543213', 'AB+', 'Civil', 4, '2025-01-20', 0, '$2y$10$2CAvk9vJOi2bnxM2uQRPSOSjJTYw91npgQwlgK1PDciFrOYElGbd6', '2025-08-04 15:25:03'),
(7, '21cs041', 'Dhanya', 'dhanya@gmail.com', '9654234543', 'A+', 'Computer Science', 4, NULL, 1, '$2y$10$UPbFeYrfPYB76MwsPqpD9.uutlviW2hUh3Kn6E2UoyFfot7m7.m0m', '2025-08-10 04:45:34'),
(8, 'STU001', 'John Smith', 'john.smith@example.com', '9876500001', 'A+', 'Computer Science', 1, NULL, 1, '', '2025-08-10 05:37:59'),
(9, 'STU002', 'Sarah Johnson', 'sarah.johnson@example.com', '9876500002', 'B-', 'Mechanical', 2, NULL, 0, '', '2025-08-10 05:37:59'),
(10, 'STU003', 'Michael Williams', 'michael.williams@example.com', '9876500003', 'O+', 'Electrical', 3, NULL, 1, '', '2025-08-10 05:37:59'),
(11, 'STU004', 'Emily Brown', 'emily.brown@example.com', '9876500004', 'AB+', 'Civil', 4, NULL, 1, '', '2025-08-10 05:37:59'),
(12, 'STU005', 'David Jones', 'david.jones@example.com', '9876500005', 'A-', 'Computer Science', 2, NULL, 0, '', '2025-08-10 05:37:59'),
(13, 'STU006', 'Olivia Garcia', 'olivia.garcia@example.com', '9876500006', 'B+', 'Information Technology', 1, NULL, 1, '', '2025-08-10 05:38:00'),
(14, 'STU007', 'James Miller', 'james.miller@example.com', '9876500007', 'O-', 'Electronics', 3, NULL, 1, '', '2025-08-10 05:38:00'),
(15, 'STU008', 'Emma Davis', 'emma.davis@example.com', '9876500008', 'A+', 'Mechanical', 2, NULL, 1, '', '2025-08-10 05:38:00'),
(16, 'STU009', 'William Rodriguez', 'william.rodriguez@example.com', '9876500009', 'B-', 'Civil', 1, NULL, 0, '', '2025-08-10 05:38:00'),
(17, 'STU010', 'Ava Martinez', 'ava.martinez@example.com', '9876500010', 'O+', 'Computer Science', 4, NULL, 1, '', '2025-08-10 05:38:00'),
(18, 'STU011', 'Alexander Hernandez', 'alex.hernandez@example.com', '9876500011', 'AB-', 'Electrical', 3, NULL, 0, '', '2025-08-10 05:38:00'),
(19, 'STU012', 'Sophia Lopez', 'sophia.lopez@example.com', '9876500012', 'A-', 'Information Technology', 2, NULL, 1, '', '2025-08-10 05:38:00'),
(20, 'STU013', 'Daniel Gonzalez', 'daniel.gonzalez@example.com', '9876500013', 'B+', 'Electronics', 1, NULL, 1, '', '2025-08-10 05:38:00'),
(21, 'STU014', 'Mia Wilson', 'mia.wilson@example.com', '9876500014', 'O-', 'Mechanical', 4, NULL, 0, '', '2025-08-10 05:38:00'),
(22, 'STU015', 'Matthew Anderson', 'matthew.anderson@example.com', '9876500015', 'A+', 'Civil', 2, NULL, 1, '', '2025-08-10 05:38:00'),
(23, 'STU016', 'Isabella Thomas', 'isabella.thomas@example.com', '9876500016', 'B-', 'Computer Science', 3, NULL, 1, '', '2025-08-10 05:38:00'),
(24, 'STU017', 'Ethan Taylor', 'ethan.taylor@example.com', '9876500017', 'O+', 'Electrical', 1, NULL, 0, '', '2025-08-10 05:38:00'),
(25, 'STU018', 'Amelia Moore', 'amelia.moore@example.com', '9876500018', 'AB+', 'Electronics', 4, NULL, 1, '', '2025-08-10 05:38:00'),
(26, 'STU019', 'Christopher Jackson', 'chris.jackson@example.com', '9876500019', 'A-', 'Mechanical', 2, NULL, 1, '$2y$10$N/IgL3F3OM7YURaSfrj46uuoOjtBO4Ci/PGU1fhvmwXMLH./dkFOy', '2025-08-10 05:38:00'),
(27, 'STU020', 'Harper Martin', 'harper.martin@example.com', '9876500020', 'B+', 'Civil', 3, NULL, 0, '', '2025-08-10 05:38:00'),
(28, 'STU021', 'Joseph Lee', 'joseph.lee@example.com', '9876500021', 'O-', 'Computer Science', 1, NULL, 1, '', '2025-08-10 05:38:00'),
(29, 'STU022', 'Evelyn Perez', 'evelyn.perez@example.com', '9876500022', 'A+', 'Electrical', 2, NULL, 0, '', '2025-08-10 05:38:00'),
(30, 'STU023', 'Samuel White', 'samuel.white@example.com', '9876500023', 'B-', 'Information Technology', 4, NULL, 1, '', '2025-08-10 05:38:00'),
(31, 'STU024', 'Abigail Harris', 'abigail.harris@example.com', '9876500024', 'O+', 'Electronics', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(32, 'STU025', 'Henry Clark', 'henry.clark@example.com', '9876500025', 'AB-', 'Mechanical', 1, NULL, 0, '', '2025-08-10 05:38:01'),
(33, 'STU026', 'Elizabeth Lewis', 'elizabeth.lewis@example.com', '9876500026', 'A-', 'Civil', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(34, 'STU027', 'Jack Young', 'jack.young@example.com', '9876500027', 'B+', 'Computer Science', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(35, 'STU028', 'Sofia Hall', 'sofia.hall@example.com', '9876500028', 'O-', 'Electrical', 4, NULL, 0, '', '2025-08-10 05:38:01'),
(36, 'STU029', 'Logan Allen', 'logan.allen@example.com', '9876500029', 'A+', 'Information Technology', 1, NULL, 1, '', '2025-08-10 05:38:01'),
(37, 'STU030', 'Charlotte King', 'charlotte.king@example.com', '9876500030', 'B-', 'Electronics', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(38, 'STU031', 'Jacob Wright', 'jacob.wright@example.com', '9876500031', 'O+', 'Mechanical', 3, NULL, 0, '', '2025-08-10 05:38:01'),
(39, 'STU032', 'Scarlett Scott', 'scarlett.scott@example.com', '9876500032', 'AB+', 'Civil', 4, NULL, 1, '', '2025-08-10 05:38:01'),
(40, 'STU033', 'Levi Green', 'levi.green@example.com', '9876500033', 'A-', 'Computer Science', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(41, 'STU034', 'Victoria Adams', 'victoria.adams@example.com', '9876500034', 'B+', 'Electrical', 1, NULL, 0, '', '2025-08-10 05:38:01'),
(42, 'STU035', 'David Baker', 'david.baker@example.com', '9876500035', 'O-', 'Electronics', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(43, 'STU036', 'Luna Nelson', 'luna.nelson@example.com', '9876500036', 'A+', 'Mechanical', 4, NULL, 1, '', '2025-08-10 05:38:01'),
(44, 'STU037', 'Andrew Carter', 'andrew.carter@example.com', '9876500037', 'B-', 'Civil', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(45, 'STU038', 'Grace Mitchell', 'grace.mitchell@example.com', '9876500038', 'O+', 'Computer Science', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(46, 'STU039', 'Joshua Perez', 'joshua.perez@example.com', '9876500039', 'AB-', 'Electrical', 1, NULL, 1, '', '2025-08-10 05:38:01'),
(47, 'STU040', 'Chloe Roberts', 'chloe.roberts@example.com', '9876500040', 'A-', 'Information Technology', 4, NULL, 0, '', '2025-08-10 05:38:01'),
(48, 'STU041', 'Isaac Turner', 'isaac.turner@example.com', '9876500041', 'B+', 'Electronics', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(49, 'STU042', 'Penelope Phillips', 'penelope.phillips@example.com', '9876500042', 'O-', 'Mechanical', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(50, 'STU043', 'Anthony Campbell', 'anthony.campbell@example.com', '9876500043', 'A+', 'Civil', 1, NULL, 0, '', '2025-08-10 05:38:01'),
(51, 'STU044', 'Nora Parker', 'nora.parker@example.com', '9876500044', 'B-', 'Computer Science', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(52, 'STU045', 'Dylan Evans', 'dylan.evans@example.com', '9876500045', 'O+', 'Electrical', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(53, 'STU046', 'Layla Edwards', 'layla.edwards@example.com', '9876500046', 'AB+', 'Electronics', 4, NULL, 0, '', '2025-08-10 05:38:01'),
(54, 'STU047', 'Charles Collins', 'charles.collins@example.com', '9876500047', 'A-', 'Mechanical', 2, NULL, 1, '', '2025-08-10 05:38:01'),
(55, 'STU048', 'Hazel Stewart', 'hazel.stewart@example.com', '9876500048', 'B+', 'Civil', 3, NULL, 1, '', '2025-08-10 05:38:01'),
(56, 'STU049', 'Thomas Sanchez', 'thomas.sanchez@example.com', '9876500049', 'O-', 'Computer Science', 1, NULL, 0, '', '2025-08-10 05:38:01'),
(57, 'STU050', 'Zoe Morris', 'zoe.morris@example.com', '9876500050', 'A+', 'Electrical', 2, NULL, 1, '', '2025-08-10 05:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `site_name` varchar(100) NOT NULL DEFAULT 'Blood Group Management',
  `admin_email` varchar(100) NOT NULL,
  `items_per_page` int(11) NOT NULL DEFAULT 20,
  `enable_registration` tinyint(1) NOT NULL DEFAULT 1,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `blood_group_colors` text DEFAULT NULL,
  `whatsapp_group_link` varchar(255) DEFAULT NULL,
  `whatsapp_group_name` varchar(100) DEFAULT NULL,
  `enable_whatsapp_broadcast` tinyint(1) NOT NULL DEFAULT 1,
  `enable_blood_requests` tinyint(1) NOT NULL DEFAULT 1,
  `auto_approve_requests` tinyint(1) NOT NULL DEFAULT 0,
  `request_notification_email` varchar(100) DEFAULT NULL,
  `broadcast_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `site_name`, `admin_email`, `items_per_page`, `enable_registration`, `maintenance_mode`, `blood_group_colors`, `whatsapp_group_link`, `whatsapp_group_name`, `enable_whatsapp_broadcast`, `enable_blood_requests`, `auto_approve_requests`, `request_notification_email`, `broadcast_message`) VALUES
(1, 'Blood Group Management System', 'admin@example.com', 20, 1, 0, '{\"A+\":\"#ff5252\",\"A-\":\"#ff8a80\",\"B+\":\"#448aff\",\"B-\":\"#82b1ff\",\"AB+\":\"#7c4dff\",\"AB-\":\"#b388ff\",\"O+\":\"#ffc107\",\"O-\":\"#ffd740\"}', 'https://chat.whatsapp.com/XXXXXX', 'Blood Donors Community', 1, 1, 0, 'admin@example.com', 'Urgent blood needed! Please check the blood donation system for details.');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_broadcasts`
--

CREATE TABLE `whatsapp_broadcasts` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_by` int(11) NOT NULL COMMENT 'Admin ID',
  `recipient_count` int(11) DEFAULT 0 COMMENT 'Number of donors notified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `whatsapp_broadcasts`
--

INSERT INTO `whatsapp_broadcasts` (`id`, `request_id`, `message`, `sent_by`, `recipient_count`, `created_at`) VALUES
(1, 1, '🆘 URGENT: A+ blood needed at City General Hospital for John Smith. Contact Mary Smith at 9845012345. Emergency surgery required. Please share with potential donors! 🩸', 1, 15, '2025-09-09 16:57:36'),
(2, 2, '🆘 URGENT: B+ blood needed at Central Medical Center for Sarah Johnson. Contact David Johnson at 9845567890. Cancer treatment. Please share with potential donors! 🩸', 1, 12, '2025-09-09 16:57:36'),
(3, 2, '🟠 URGENT BLOOD REQUEST 🟠\n\nPatient: Sarah Johnson\nBlood Group: B+\nUnits Needed: 1\nHospital: Central Medical Center\nLocation: 456 Oak Avenue, Downtown\nContact: David Johnson - 9845567890\nUrgency: High\n\nAdditional Info: Cancer treatment\n\nPlease help if you can! Share with potential donors. 🩸', 1, 6, '2025-09-09 18:33:08');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_group_joins`
--

CREATE TABLE `whatsapp_group_joins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `whatsapp_group_joins`
--

INSERT INTO `whatsapp_group_joins` (`id`, `name`, `phone`, `blood_group`, `ip_address`, `user_agent`, `joined_at`) VALUES
(1, 'sathi', '9087564378', 'AB-', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-09-06 16:11:07'),
(2, 'sathi', '9087564378', 'AB-', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', '2025-09-06 16:21:29');

-- --------------------------------------------------------

--
-- Structure for view `pending_blood_requests`
--
DROP TABLE IF EXISTS `pending_blood_requests`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_blood_requests`  AS SELECT `br`.`id` AS `id`, `br`.`patient_name` AS `patient_name`, `br`.`contact_name` AS `contact_name`, `br`.`contact_phone` AS `contact_phone`, `br`.`blood_group` AS `blood_group`, `br`.`units_needed` AS `units_needed`, `br`.`hospital_name` AS `hospital_name`, `br`.`hospital_address` AS `hospital_address`, `br`.`urgency` AS `urgency`, `br`.`additional_info` AS `additional_info`, `br`.`status` AS `status`, `br`.`requested_by` AS `requested_by`, `br`.`requested_ip` AS `requested_ip`, `br`.`admin_notes` AS `admin_notes`, `br`.`approved_by` AS `approved_by`, `br`.`created_at` AS `created_at`, `br`.`updated_at` AS `updated_at`, `a`.`full_name` AS `approved_by_name`, `s`.`full_name` AS `requested_by_name` FROM ((`blood_requests` `br` left join `admins` `a` on(`br`.`approved_by` = `a`.`id`)) left join `students` `s` on(`br`.`requested_by` = `s`.`id`)) WHERE `br`.`status` = 'pending' ORDER BY `br`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `blood_group` (`blood_group`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `fk_event` (`event_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `donation_events`
--
ALTER TABLE `donation_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donation_fulfillments`
--
ALTER TABLE `donation_fulfillments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_broadcasts`
--
ALTER TABLE `whatsapp_broadcasts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `whatsapp_group_joins`
--
ALTER TABLE `whatsapp_group_joins`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blood_requests`
--
ALTER TABLE `blood_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `donation_events`
--
ALTER TABLE `donation_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `donation_fulfillments`
--
ALTER TABLE `donation_fulfillments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `event_attendance`
--
ALTER TABLE `event_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `whatsapp_broadcasts`
--
ALTER TABLE `whatsapp_broadcasts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `whatsapp_group_joins`
--
ALTER TABLE `whatsapp_group_joins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD CONSTRAINT `blood_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blood_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `donations_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_event` FOREIGN KEY (`event_id`) REFERENCES `donation_events` (`id`);

--
-- Constraints for table `donation_fulfillments`
--
ALTER TABLE `donation_fulfillments`
  ADD CONSTRAINT `donation_fulfillments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donation_fulfillments_ibfk_2` FOREIGN KEY (`donor_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donation_fulfillments_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD CONSTRAINT `event_attendance_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `donation_events` (`id`),
  ADD CONSTRAINT `event_attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `donation_events` (`id`),
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `whatsapp_broadcasts`
--
ALTER TABLE `whatsapp_broadcasts`
  ADD CONSTRAINT `whatsapp_broadcasts_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `whatsapp_broadcasts_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `update_year_of_study` ON SCHEDULE EVERY 1 MONTH STARTS '2025-06-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE students
    SET year_of_study = LEAST(4, year_of_study + 
        FLOOR(
            TIMESTAMPDIFF(MONTH, created_at, CURDATE()) / 12 +
            IF(MONTH(CURDATE()) >= 6, 1, 0)
        )
    )
    WHERE year_of_study < 4$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
