-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2025 at 06:32 AM
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
-- Database: `shls_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 6, 'clear_logs', 'All activity logs cleared by admin', '::1', '2025-04-06 04:31:00'),
(2, 22, 'login', 'Doctor logged in successfully', NULL, '2025-04-06 04:31:22');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `test_type` varchar(100) NOT NULL,
  `lab_room` varchar(50) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `test_type`, `lab_room`, `status`, `created_at`) VALUES
(1, 1, 4, '2025-04-19', '14:00:00', 'Kidney Function', 'Lab Room 102', 'scheduled', '2025-04-05 13:15:21'),
(2, 1, 22, '2025-05-15', '15:30:00', 'Lipid Profile & Glucose Test', 'Lab Room 105', 'scheduled', '2025-04-05 13:15:21'),
(3, 1, 7, '2025-05-31', '14:30:00', 'Liver Function', NULL, 'scheduled', '2025-04-05 14:41:21'),
(4, 1, 1, '2025-04-19', '15:30:00', 'Kidney Function', NULL, 'scheduled', '2025-04-05 14:52:13'),
(5, 3, 1, '2025-04-24', '15:00:00', 'Complete Blood Count', NULL, 'scheduled', '2025-04-05 15:06:19'),
(6, 1, 6, '2025-04-17', '14:30:00', 'Lipid Profile', NULL, 'scheduled', '2025-04-05 22:29:28'),
(7, 1, 6, '2025-04-24', '15:00:00', 'Kidney Function', NULL, 'scheduled', '2025-04-06 01:40:04'),
(8, 1, 9, '2025-04-29', '16:00:00', 'Lipid Profile', NULL, 'scheduled', '2025-04-06 03:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_valid` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `first_name`, `last_name`, `email`, `phone`, `specialization`, `department`, `created_at`, `updated_at`) VALUES
(1, 'Sarah', 'Johnson', 'sarah.johnson@hospital.com', '(555) 123-4567', 'Internal Medicine', 'General Medicine', '2025-04-05 13:15:21', '2025-04-05 13:15:21'),
(2, 'Michael', 'Chen', 'michael.chen@hospital.com', '(555) 234-5678', 'Cardiology', 'Cardiology', '2025-04-05 13:15:21', '2025-04-05 13:15:21'),
(3, 'James', 'Wilson', 'james.wilson@hospital.com', '(555) 345-6789', 'Radiology', 'Imaging', '2025-04-05 13:15:21', '2025-04-05 13:15:21'),
(4, 'Emily', 'Brown', 'emily.brown@hospital.com', '(555) 456-7890', 'Pediatrics', 'Pediatrics', '2025-04-05 13:15:21', '2025-04-05 13:15:21'),
(5, 'David', 'Lee', 'david.lee@hospital.com', '(555) 567-8901', 'Neurology', 'Neurology', '2025-04-05 13:15:21', '2025-04-05 13:15:21'),
(6, 'Ranuka', 'Jayesh', 'dr.ranukajayesh@gmail.com', '0759307059', 'mk', 'mk', '2025-04-05 22:11:03', '2025-04-05 22:11:03'),
(8, 'alo', 'doc', 'a@gmail.com', 'alo', 'alo', 'alo', '2025-04-06 03:08:49', '2025-04-06 03:13:34'),
(9, 'me', 'doctor', 'md@gmail.com', '123', '123', '123', '2025-04-06 03:16:39', '2025-04-06 03:16:39');

-- --------------------------------------------------------

--
-- Table structure for table `health_metrics`
--

CREATE TABLE `health_metrics` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `metric_type` varchar(50) NOT NULL,
  `result_value` decimal(10,2) NOT NULL,
  `test_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_metrics`
--

INSERT INTO `health_metrics` (`id`, `patient_id`, `metric_type`, `result_value`, `test_date`, `created_at`) VALUES
(1, 1, 'blood_glucose', 95.00, '2025-04-04', '2025-04-05 13:15:21'),
(2, 1, 'blood_glucose', 105.00, '2025-03-31', '2025-04-05 13:15:21'),
(3, 1, 'blood_glucose', 92.00, '2025-03-26', '2025-04-05 13:15:21'),
(4, 1, 'blood_glucose', 98.00, '2025-03-21', '2025-04-05 13:15:21'),
(5, 1, 'blood_glucose', 90.00, '2025-03-16', '2025-04-05 13:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','test_result','doctor_message','medication','health_tip') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `patient_id`, `doctor_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 22, 'New Appointment', 'You have a new appointment scheduled for 2025-04-19 at 14:00', 'appointment', 0, '2025-04-05 13:15:21'),
(2, 1, 22, 'Test Result', 'Your test results are ready. Please visit the lab to collect them.', 'test_result', 0, '2025-04-05 13:15:21'),
(3, 1, 22, 'Doctor Message', 'Dr. Johnson has sent you a message. Please check your messages.', 'doctor_message', 0, '2025-04-05 13:15:21'),
(4, 1, 22, 'Medication Reminder', 'It\'s time to take your medication. Please remember to do so.', 'medication', 0, '2025-04-05 13:15:21'),
(5, 1, 22, 'Health Tip', 'Did you know? Regular exercise can improve your overall health.', 'health_tip', 0, '2025-04-05 13:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `notification_channels`
--

CREATE TABLE `notification_channels` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `channel_type` enum('email','sms','push') NOT NULL,
  `channel_value` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_channels`
--

INSERT INTO `notification_channels` (`id`, `patient_id`, `channel_type`, `channel_value`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 1, 'email', 'dr.ranukajayesh@gmail.com', 1, '2025-04-06 04:27:24', '2025-04-06 04:27:24'),
(6, 1, 'sms', '7596546546', 1, '2025-04-06 04:27:38', '2025-04-06 04:27:38');

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 0,
  `appointment_reminders` tinyint(1) DEFAULT 1,
  `test_results_notifications` tinyint(1) DEFAULT 1,
  `doctor_messages_notifications` tinyint(1) DEFAULT 1,
  `medication_reminders` tinyint(1) DEFAULT 0,
  `health_tips_notifications` tinyint(1) DEFAULT 0,
  `email_address` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `patient_id`, `email_notifications`, `sms_notifications`, `push_notifications`, `appointment_reminders`, `test_results_notifications`, `doctor_messages_notifications`, `medication_reminders`, `health_tips_notifications`, `email_address`, `phone_number`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 0, 1, 1, 1, 0, 0, NULL, NULL, '2025-04-05 18:00:23', '2025-04-05 18:00:23'),
(2, 1, 1, 1, 1, 1, 1, 1, 0, 0, NULL, NULL, '2025-04-05 18:01:49', '2025-04-05 18:01:49');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `national_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `security_question` varchar(100) NOT NULL,
  `security_answer` varchar(100) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `language_preference` varchar(2) DEFAULT 'en',
  `timezone` varchar(2) DEFAULT 'et',
  `appointment_reminders` tinyint(1) DEFAULT 1,
  `test_result_notifications` tinyint(1) DEFAULT 1,
  `doctor_messages` tinyint(1) DEFAULT 1,
  `medication_reminders` tinyint(1) DEFAULT 1,
  `health_tips` tinyint(1) DEFAULT 1,
  `data_usage_research` tinyint(1) DEFAULT 0,
  `profile_visibility` tinyint(1) DEFAULT 1,
  `two_factor_auth` tinyint(1) DEFAULT 0,
  `share_medical_records` tinyint(1) DEFAULT 1,
  `allow_email_communications` tinyint(1) DEFAULT 1,
  `allow_sms_communications` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `national_id`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `country`, `blood_type`, `allergies`, `medications`, `medical_conditions`, `emergency_contact`, `emergency_phone`, `username`, `password`, `security_question`, `security_answer`, `remember_token`, `token_expires`, `created_at`, `language_preference`, `timezone`, `appointment_reminders`, `test_result_notifications`, `doctor_messages`, `medication_reminders`, `health_tips`, `data_usage_research`, `profile_visibility`, `two_factor_auth`, `share_medical_records`, `allow_email_communications`, `allow_sms_communications`) VALUES
(1, 'Ranuka', 'Jayesh', '2003-06-16', 'Male', '20202000156', 'dr.ranukajayesh@gmail.com', '0759307059', 'Colombo', 'Homagama', '20', '300', 'ca', 'O+', 'Food', 'Non', 'Non', 'Ranuka Jayesh', '0759307050', 'RJGanegama', '$2y$10$6qFlo3jVEW3N0h7BViB3ieOhBFDDZyEB1jmnDoBFS7YFt6nXCrrv6', 'first-pet', 'Me', '43d0697b32bfa772496e9b72e5090a45b741e5a56c4b7bb93c8453fe515ff5b1', '2025-05-06 06:28:57', '2025-04-05 12:50:35', 'en', 'et', 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1),
(3, 'Passan', 'Arjuna', '2000-01-01', 'male', '20023215465', 'pasan@gmail.com', '0761231231', 'Colombo', 'Homagama', '123', '150', 'uk', 'ab-negativ', 'Non', 'Non', 'Non', 'Kaviru', '07612345678', 'Pasan', '$2y$10$sDGgES533M.bgZ8uVSxZJegtvxG9SmxAZyK8jZuVKGTNHYzxvfRsi', 'birth-city', 'Me', 'febcea46beb66110d75e506d4f730d9f0571ba1de4aa561b3d60afbd334b6d39', '2025-05-05 17:05:50', '2025-04-05 15:05:34', 'en', 'et', 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1),
(4, 'mk', 'mk', '2032-04-05', 'male', '546546', 'm@gmail.com', '123456789', 'Samitha Hardware ,Dampe Road , Pitipana', 'Homagama', '456', '465', 'Sri Lanka', 'B+', '456', '65465', '4654', '546', '54656', 'm@gmail.com', '$2y$10$3ZETZAbhsnUupt7KXBav7OvrgwWIoU9u9B9r.u/VhQaCWHYLNVo0.', '', '', NULL, NULL, '2025-04-05 22:15:55', 'en', 'et', 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1),
(5, 'Visal', 'Denuwan', '2025-04-16', 'female', '2500', 'visal@gmail.com', '0759307059', 'Samitha Hardware ,Dampe Road , Pitipana', 'Homagama', '465', '10200', 'Sri Lanka', 'A-', 'Non', 'non', 'non', 'non', '456', 'visal@gmail.com', '$2y$10$naus3eNHkXJ3rwempLSSeOKrgG.NhJC2RthKnOQR3eVSOQjjTkIF6', '', '', 'ac052e789f4b0fc2ff15d0cccda1fb624dd29f60775319153d4ad619cc0342ae', '2025-05-06 00:32:02', '2025-04-05 22:18:41', 'en', 'et', 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1),
(6, 'kaviru', 'de silva', '2025-05-02', 'female', '5000', 'k@gmail.com', '0759307059', 'Samitha Hardware ,Dampe Road , Pitipana', 'Homagama', '5', '10200', 'Sri Lanka', 'B+', 'No', 'no', 'no', 'no', '456', 'k@gmail.com', '$2y$10$7Au2IpMCplA5tyoeLA0PhO7t/zOUt97uJLQ4LG3p/uRkAzU7KUglS', '', '', 'f746968abb494dd4c7b04da69cc0048b0f7b1288eb6142513ed20ba14b99b66b', '2025-05-06 04:22:02', '2025-04-06 02:21:11', 'en', 'et', 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1),
(7, 'new', 'user', '2025-05-01', 'male', '200000', 'new@gmail.com', '07569845632', 'Colombo', 'Homagama', 'B45', '45300', 'Srilanka', 'AB-', 'No', 'No', 'No', 'Dad', '123', 'new@gmail.com', '$2y$10$cCqH7Cqfc9dBEqAV24cbYOH4u7CtWd76n8h9IGiSFofYQlSOipNbG', '', '', '1b7c8a43816864e0a48ca4a35e98531e2b978e3276b51c66648f4281fc90e6fc', '2025-05-06 06:25:03', '2025-04-06 04:24:22', 'en', 'et', 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_group` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Smart Hospital Laboratory System', 'general', 'The name of the system', '2025-04-05 21:10:33', '2025-04-05 21:10:33'),
(2, 'site_email', 'admin@shls.com', 'general', 'System administrator email', '2025-04-05 21:10:33', '2025-04-05 21:10:33'),
(3, 'site_phone', '+1234567890', 'general', 'System contact phone number', '2025-04-05 21:10:33', '2025-04-05 21:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `turnaround_time` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `category_id`, `name`, `description`, `price`, `turnaround_time`, `created_at`, `updated_at`) VALUES
(1, 1, 'Complete Blood Count', 'Basic blood cell analysis', 50.00, '24 hours', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(2, 1, 'Blood Sugar Test', 'Glucose level measurement', 30.00, '2 hours', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(3, 2, 'Urinalysis', 'Basic urine analysis', 25.00, '24 hours', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(4, 3, 'Chest X-ray', 'Chest imaging', 100.00, '48 hours', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(5, 4, 'Culture and Sensitivity', 'Bacterial culture test', 75.00, '72 hours', '2025-04-05 20:35:08', '2025-04-05 20:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `test_categories`
--

CREATE TABLE `test_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_categories`
--

INSERT INTO `test_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Blood Tests', 'Various blood analysis tests', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(2, 'Urine Tests', 'Urine analysis and related tests', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(3, 'Imaging Tests', 'X-rays, MRIs, and other imaging tests', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(4, 'Microbiology', 'Bacterial and viral tests', '2025-04-05 20:35:08', '2025-04-05 20:35:08'),
(5, 'Biochemistry', 'Chemical analysis of body fluids', '2025-04-05 20:35:08', '2025-04-05 20:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `test_results`
--

CREATE TABLE `test_results` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `lab_staff_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `test_name` varchar(100) DEFAULT NULL,
  `order_date` timestamp NULL DEFAULT NULL,
  `test_date` date DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `results` text DEFAULT NULL,
  `result_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_results`
--

INSERT INTO `test_results` (`id`, `patient_id`, `doctor_id`, `lab_staff_id`, `test_id`, `test_name`, `order_date`, `test_date`, `status`, `results`, `result_file`, `created_at`, `updated_at`) VALUES
(1, 1, 22, 18, 5, 'Non', '2025-04-06 01:20:51', '2025-04-06', 'completed', '{\"wbc\":\"1\",\"rbc\":\"1\",\"hgb\":\"1\",\"hct\":\"1\",\"mcv\":\"1\",\"platelets\":\"1\",\"comments\":\"Nice\",\"tested_by\":\"G.VISHWA DEEPTHI\",\"verified_by\":\"\",\"test_date\":\"2025-04-06T07:24\"}', 'Non', '2025-04-06 01:21:48', '2025-04-06 03:44:33'),
(2, 5, 22, 18, 4, 'Non', '2025-04-06 01:31:57', '2025-04-06', 'pending', '{\"wbc\":\"1\",\"rbc\":\"1\",\"hgb\":\"1\",\"hct\":\"1\",\"mcv\":\"1\",\"platelets\":\"1\",\"comments\":\"mk\",\"tested_by\":\"G.VISHWA DEEPTHI\",\"verified_by\":\"\",\"test_date\":\"2025-04-06T07:40\"}', 'Non', '2025-04-06 01:32:49', '2025-04-06 03:40:39'),
(3, 1, 22, 18, 1, 'Non', '2025-04-06 01:31:57', '2025-04-06', 'completed', '{\"wbc\":\"1\",\"rbc\":\"1\",\"hgb\":\"1\",\"hct\":\"1\",\"mcv\":\"1\",\"platelets\":\"1\",\"comments\":\"New\",\"tested_by\":\"G.VISHWA DEEPTHI\",\"verified_by\":\"\",\"test_date\":\"2025-04-06T09:59\"}', 'Non', '2025-04-06 01:33:55', '2025-04-06 04:30:07'),
(5, 1, 22, 18, 4, 'non', '2025-04-06 03:36:31', '2025-04-06', 'completed', 'non', 'non', '2025-04-06 03:37:27', '2025-04-06 03:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','doctor','lab_staff','patient') NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `email`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(6, 'admin@gmail.com', '$2y$10$22NwuX2eowmTgVx.waB0uO5UHxlqWxTuAv5qcFPMFiVcNIMDEslA2', 'admin admin', 'admin', 'admin@gmail.com', 'active', '2025-04-06 10:00:52', '2025-04-05 21:52:12', '2025-04-06 04:30:52'),
(10, 'dr.ranukajayesh@gmail.com', '$2y$10$hWmI0MdBLVR.Mdi8a2zfOuH0Hd13jVtaU/FkjsrMHy/nNaJZ43Zne', 'Ranuka Jayesh', 'doctor', 'dr.ranukajayesh@gmail.com', 'active', NULL, '2025-04-05 22:11:03', '2025-04-05 22:11:03'),
(17, 'visal@gmail.com', '$2y$10$naus3eNHkXJ3rwempLSSeOKrgG.NhJC2RthKnOQR3eVSOQjjTkIF6', 'Visal Denuwan', 'patient', 'visal@gmail.com', 'active', NULL, '2025-04-05 22:18:41', '2025-04-05 22:18:41'),
(18, 'm@gmail.com', '$2y$10$/PKnaGwTEETC.8ppWCaDvOpUxio0.2.JW1kAnTIsuZ76hFWfqEQEG', 'G.VISHWA DEEPTHI', 'lab_staff', 'm@gmail.com', 'active', '2025-04-06 09:58:23', '2025-04-05 22:47:26', '2025-04-06 04:28:23'),
(22, 'md@gmail.com', '$2y$10$beMG9eizzYK5M0ENZglBLOwVE2NdBqZLXx4WnX/mJhBYRawqqc93u', 'me doctor', 'doctor', 'md@gmail.com', 'active', NULL, '2025-04-06 03:16:39', '2025-04-06 03:19:27'),
(23, 'new@gmail.com', '$2y$10$cCqH7Cqfc9dBEqAV24cbYOH4u7CtWd76n8h9IGiSFofYQlSOipNbG', 'new user', 'patient', 'new@gmail.com', 'active', NULL, '2025-04-06 04:24:22', '2025-04-06 04:24:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `health_metrics`
--
ALTER TABLE `health_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `notification_channels`
--
ALTER TABLE `notification_channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `test_categories`
--
ALTER TABLE `test_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `test_results`
--
ALTER TABLE `test_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `lab_staff_id` (`lab_staff_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `health_metrics`
--
ALTER TABLE `health_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_channels`
--
ALTER TABLE `notification_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `test_categories`
--
ALTER TABLE `test_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `test_results`
--
ALTER TABLE `test_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `health_metrics`
--
ALTER TABLE `health_metrics`
  ADD CONSTRAINT `health_metrics_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_channels`