-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 25, 2026 at 07:09 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u815114538_GREENBRIDGE`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`id`, `admin_id`, `action_type`, `target_type`, `target_id`, `details`, `created_at`, `ip_address`, `user_agent`) VALUES
(1, 3, 'approve_student', 'student', 1, NULL, '2026-05-19 14:24:57', NULL, NULL),
(2, 3, 'approve_company', 'company', 1, NULL, '2026-05-19 14:25:02', NULL, NULL),
(3, 3, 'approve_student', 'student', 3, NULL, '2026-05-19 14:28:20', NULL, NULL),
(4, 3, 'approve_student', 'student', 2, NULL, '2026-05-19 14:28:22', NULL, NULL),
(5, 3, 'approve_student', 'student', 6, NULL, '2026-05-19 16:29:29', NULL, NULL),
(6, 3, 'approve_post', 'post', 11, NULL, '2026-05-19 16:46:13', NULL, NULL),
(7, 3, 'approve_post', 'post', 12, NULL, '2026-05-19 16:46:47', NULL, NULL),
(8, 3, 'approve_post', 'post', 16, NULL, '2026-05-19 16:47:27', NULL, NULL),
(9, 3, 'approve_post', 'post', 16, NULL, '2026-05-19 16:47:28', NULL, NULL),
(10, 3, 'approve_post', 'post', 15, NULL, '2026-05-19 16:47:31', NULL, NULL),
(11, 3, 'remove_post', 'post', 15, NULL, '2026-05-19 16:47:54', NULL, NULL),
(12, 3, 'approve_student', 'student', 8, NULL, '2026-05-19 22:42:22', NULL, NULL),
(13, 3, 'approve_student', 'student', 10, NULL, '2026-05-20 01:41:37', NULL, NULL),
(14, 3, 'approve_company', 'company', 2, NULL, '2026-05-20 01:41:51', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_used` varchar(500) DEFAULT NULL,
  `company_notes` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `student_id`, `internship_id`, `cover_letter`, `resume_used`, `company_notes`, `status`, `applied_at`, `reviewed_at`) VALUES
(1, 1, 1, '', 'uploads/resumes/6a0c7b2881394_1779202856.pdf', '', 'accepted', '2026-05-19 15:01:15', '2026-05-19 15:18:26'),
(2, 1, 2, 'Dear Hiring Manager,\r\n\r\nI am writing to express my interest in the Frontend Developer position. I am currently a Computer Engineering student with a strong interest in software development and system design, particularly in building scalable backend systems.\r\n\r\nI am highly motivated, willing to learn, and capable of working collaboratively in a team environment. I would appreciate the opportunity to contribute to your organization and grow as a frontend developer.\r\n\r\nThank you for your time and consideration.\r\n\r\nSincerely,\r\nJuan Dela Cruz', 'uploads/resumes/6a0c7b2881394_1779202856.pdf', '', 'accepted', '2026-05-19 15:03:34', '2026-05-19 15:08:55'),
(3, 2, 1, 'hello', 'uploads/resumes/6a0cfebc2a91d_1779236540.pdf', '', 'accepted', '2026-05-20 00:22:29', '2026-05-20 00:22:35'),
(4, 2, 4, 'hello', 'uploads/resumes/6a0d12b7bc2b1_1779241655.pdf', '', 'accepted', '2026-05-20 01:47:38', '2026-05-20 01:48:21');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_name` varchar(255) DEFAULT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `credential_id` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `certificate_name`, `issuer`, `issue_date`, `credential_id`, `file_path`, `uploaded_at`) VALUES
(1, 1, 'Certidicate', 'DLSUD', '0000-00-00', '', 'uploads/certificates/6a0c93efc6993_1779209199.jpg', '2026-05-19 16:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('approved','flagged','removed') DEFAULT 'approved',
  `is_flagged` tinyint(1) DEFAULT 0,
  `flag_reason` varchar(255) DEFAULT NULL,
  `flagged_by` int(11) DEFAULT NULL,
  `flagged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_position` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_description` text DEFAULT NULL,
  `company_logo` varchar(500) DEFAULT NULL,
  `verification_document` varchar(500) DEFAULT NULL,
  `status` enum('pending','active','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `user_id`, `company_name`, `industry`, `website`, `contact_person`, `contact_position`, `contact_email`, `contact_phone`, `company_address`, `company_description`, `company_logo`, `verification_document`, `status`, `created_at`) VALUES
(1, 2, 'The Company', 'Information Technology', '', '', '', '', '', '', '', NULL, 'uploads/company/6a0c718b71853_1779200395.pdf', 'active', '2026-05-19 14:19:55'),
(2, 13, 'Seinen ', 'Marketing & Advertising', 'https://seinen.com', 'Shu Yamino', 'HR Director', 'shu@gmail.com', '09123456789', 'Manila', NULL, NULL, 'uploads/company/6a0d11158c56a_1779241237.pdf', 'active', '2026-05-20 01:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `participant1_id` int(11) NOT NULL,
  `participant2_id` int(11) NOT NULL,
  `last_message` text DEFAULT NULL,
  `last_message_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `participant1_id`, `participant2_id`, `last_message`, `last_message_time`, `created_at`) VALUES
(2, 1, 6, 'Damn', '2026-05-19 15:01:52', '2026-05-19 15:01:50'),
(3, 3, 6, 'Hello', '2026-05-19 16:36:11', '2026-05-19 16:36:11'),
(4, 3, 7, 'weak', '2026-05-19 16:36:26', '2026-05-19 16:36:26'),
(5, 10, 11, 'nyelo', '2026-05-20 00:19:47', '2026-05-20 00:19:47'),
(6, 1, 2, 'What is your name?', '2026-05-20 00:35:01', '2026-05-20 00:35:01'),
(7, 1, 4, 'hello world', '2026-05-20 01:47:11', '2026-05-20 01:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `doc_type` enum('resume','other') DEFAULT 'other',
  `doc_name` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `student_id`, `doc_type`, `doc_name`, `file_name`, `file_path`, `file_size`, `uploaded_at`) VALUES
(1, 1, 'resume', '6a0c7717f14f3_1779201815.pdf', '6a0c7b2881394_1779202856.pdf', 'uploads/resumes/6a0c7b2881394_1779202856.pdf', 109911, '2026-05-19 15:00:56'),
(2, 2, 'resume', '6a0cfebc2a91d_1779236540.pdf', '6a0d12b7bc2b1_1779241655.pdf', 'uploads/resumes/6a0d12b7bc2b1_1779241655.pdf', 53712, '2026-05-20 01:47:35');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_logs`
--

CREATE TABLE `dtr_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `hours` decimal(10,2) DEFAULT NULL,
  `status` enum('active','completed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dtr_logs`
--

INSERT INTO `dtr_logs` (`id`, `student_id`, `internship_id`, `clock_in`, `clock_out`, `hours`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-05-19 15:19:45', '2026-05-19 15:21:00', 0.02, 'completed', '2026-05-19 15:19:45'),
(2, 1, 1, '2026-05-20 00:20:22', '2026-05-20 00:21:35', 0.02, 'completed', '2026-05-20 00:20:22'),
(3, 2, 4, '2026-05-20 01:48:38', NULL, NULL, 'active', '2026-05-20 01:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `technical_skills` decimal(3,1) DEFAULT NULL,
  `work_attitude` decimal(3,1) DEFAULT NULL,
  `communication` decimal(3,1) DEFAULT NULL,
  `initiative` decimal(3,1) DEFAULT NULL,
  `teamwork` decimal(3,1) DEFAULT NULL,
  `overall_score` decimal(3,1) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `evaluated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flagged_comments`
--

CREATE TABLE `flagged_comments` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','reviewed','dismissed','deleted') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flagged_items`
--

CREATE TABLE `flagged_items` (
  `id` int(11) NOT NULL,
  `item_type` enum('post','comment') NOT NULL,
  `item_id` int(11) NOT NULL,
  `flagged_by` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','reviewed','dismissed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flagged_posts`
--

CREATE TABLE `flagged_posts` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','reviewed','dismissed','deleted') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`id`, `student_id`, `friend_id`, `status`, `created_at`) VALUES
(5, 4, 1, 'accepted', '2026-05-19 15:01:39'),
(6, 1, 2, 'accepted', '2026-05-19 16:26:14'),
(7, 7, 5, 'pending', '2026-05-19 17:24:33'),
(8, 7, 6, 'pending', '2026-05-19 17:24:43'),
(9, 7, 2, 'pending', '2026-05-19 17:24:45'),
(10, 7, 1, 'pending', '2026-05-19 17:24:51'),
(11, 9, 8, 'accepted', '2026-05-20 00:19:41'),
(12, 8, 9, 'accepted', '2026-05-20 00:27:15'),
(14, 1, 7, 'accepted', '2026-05-20 00:38:52'),
(15, 2, 1, 'accepted', '2026-05-20 01:47:02');

-- --------------------------------------------------------

--
-- Table structure for table `internships`
--

CREATE TABLE `internships` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `type` enum('OJT','Internship') DEFAULT 'Internship',
  `location` varchar(255) DEFAULT NULL,
  `setup` enum('Onsite','Remote','Hybrid') DEFAULT 'Onsite',
  `department` varchar(100) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT 400,
  `slots` int(11) DEFAULT 1,
  `stipend` varchar(100) DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internships`
--

INSERT INTO `internships` (`id`, `company_id`, `title`, `description`, `requirements`, `type`, `location`, `setup`, `department`, `duration_hours`, `slots`, `stipend`, `application_deadline`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Backend Developer', 'We are looking for a Backend Developer to design, develop, and maintain server-side applications and APIs that support our web systems. The role involves working with databases, optimizing system performance, and ensuring data security.', '-Proficiency in backend programming languages: Python, Java, or PHP\r\n-Experience with RESTful API development and integration\r\n-Knowledge of database management systems ', 'OJT', 'Manila', 'Remote', 'CCS', 200, 2, '5000', '2026-05-30', 'open', '2026-05-19 14:38:19', '2026-05-19 14:38:19'),
(2, 1, 'Frontend Developer', 'We are looking for a Frontend Developer to design and implement user-facing features for web applications.', '-Knowledge of responsive and mobile-first design principles\r\n-Familiarity with RESTful API integration\r\n-Understanding of UI/UX best practices', 'Internship', 'Manila', 'Remote', 'CEAT', 100, 1, '1000', '2026-05-30', 'closed', '2026-05-19 14:40:05', '2026-05-24 02:47:04'),
(3, 1, 'Marketing Assistant', 'Retail worker', 'a pretty smile', 'OJT', 'Manila', 'Onsite', 'COB', 100, 2, '5000', '2026-05-31', 'open', '2026-05-20 00:25:07', '2026-05-20 00:25:07'),
(4, 2, 'Retail Worker', 'Retail Worker ', 'a pretty smile', 'OJT', 'Manila', 'Onsite', 'COB', 400, 10, '5000', '2026-05-30', 'open', '2026-05-20 01:44:17', '2026-05-20 01:44:17');

-- --------------------------------------------------------

--
-- Table structure for table `logbook_entries`
--

CREATE TABLE `logbook_entries` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `narrative` text DEFAULT NULL,
  `company_feedback` text DEFAULT NULL,
  `status` enum('pending','approved','commented') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `logbook_entries`
--

INSERT INTO `logbook_entries` (`id`, `student_id`, `internship_id`, `week_number`, `title`, `narrative`, `company_feedback`, `status`, `created_at`) VALUES
(1, 1, 1, 21, 'Logbook', 'Test logbook', NULL, 'approved', '2026-05-19 15:55:49'),
(2, 1, 1, 1, 'Logbook report', 'logbook report test', 'nice', 'commented', '2026-05-19 16:53:32'),
(3, 2, 1, 1, 'logbook update', 'i cleared my homework', NULL, 'pending', '2026-05-20 01:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `sender_type` enum('student','company','admin') NOT NULL,
  `receiver_type` enum('student','company','admin') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `sender_type`, `receiver_type`, `subject`, `message`, `is_read`, `created_at`) VALUES
(3, 2, 1, 'company', 'student', 'Internship Opportunity', '', 1, '2026-05-19 14:40:18'),
(4, 2, 1, 'company', 'student', 'Internship Opportunity', '', 1, '2026-05-19 14:40:22'),
(5, 6, 1, 'student', 'student', NULL, 'Test', 1, '2026-05-19 15:01:50'),
(6, 6, 1, 'student', 'student', NULL, 'Damn', 1, '2026-05-19 15:01:52'),
(7, 3, 6, 'admin', 'student', NULL, 'Hello', 1, '2026-05-19 16:36:11'),
(8, 3, 7, 'admin', 'student', NULL, 'weak', 0, '2026-05-19 16:36:26'),
(9, 11, 10, 'student', 'student', NULL, 'nyelo', 0, '2026-05-20 00:19:47'),
(10, 1, 2, 'student', 'company', NULL, 'What is your name?', 0, '2026-05-20 00:35:01'),
(11, 4, 1, 'student', 'student', NULL, 'hello world', 0, '2026-05-20 01:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `type` enum('post','announcement') DEFAULT 'post',
  `status` enum('pending','approved','flagged','removed') DEFAULT 'pending',
  `flag` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_flagged` tinyint(1) DEFAULT 0,
  `flag_reason` varchar(255) DEFAULT NULL,
  `flagged_by` int(11) DEFAULT NULL,
  `flagged_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `type`, `status`, `flag`, `created_at`, `updated_at`, `is_flagged`, `flag_reason`, `flagged_by`, `flagged_at`) VALUES
(1, 1, 'Hello World', 'post', '', NULL, '2026-05-19 14:26:17', '2026-05-19 14:26:17', 0, NULL, NULL, NULL),
(2, 2, '📢 NEW OPPORTUNITY: We\'re hiring for a Backend Developer position! Apply now through GreenBridge.', 'announcement', 'approved', NULL, '2026-05-19 14:38:19', '2026-05-19 14:38:19', 0, NULL, NULL, NULL),
(3, 2, '📢 NEW OPPORTUNITY: We\'re hiring for a Frontend Developer position! Apply now through GreenBridge.', 'announcement', 'approved', NULL, '2026-05-19 14:40:05', '2026-05-19 14:40:05', 0, NULL, NULL, NULL),
(4, 6, 'Damn\r\n', 'post', '', NULL, '2026-05-19 14:59:11', '2026-05-19 14:59:11', 0, NULL, NULL, NULL),
(5, 6, 'everything is for review\r\n', 'post', '', NULL, '2026-05-19 14:59:22', '2026-05-19 14:59:22', 0, NULL, NULL, NULL),
(6, 6, 'hoy faith testing testing', 'post', '', NULL, '2026-05-19 14:59:33', '2026-05-19 14:59:33', 0, NULL, NULL, NULL),
(7, 6, 'hoy be usefull naman', 'post', '', NULL, '2026-05-19 15:01:23', '2026-05-19 15:01:23', 0, NULL, NULL, NULL),
(8, 6, 'let me post stupid baka admin', 'post', '', NULL, '2026-05-19 15:01:30', '2026-05-19 15:01:30', 0, NULL, NULL, NULL),
(9, 7, 'LF> Community for Database Skills', 'post', '', NULL, '2026-05-19 16:27:53', '2026-05-19 16:27:53', 0, NULL, NULL, NULL),
(10, 6, 'Test', 'post', '', NULL, '2026-05-19 16:33:35', '2026-05-19 16:33:35', 0, NULL, NULL, NULL),
(11, 1, 'hello world', 'post', 'approved', NULL, '2026-05-19 16:45:30', '2026-05-19 16:46:13', 0, NULL, NULL, NULL),
(12, 6, 'test', 'post', 'approved', NULL, '2026-05-19 16:46:42', '2026-05-19 16:46:47', 0, NULL, NULL, NULL),
(13, 6, 'Hello world\r\n', 'post', 'pending', NULL, '2026-05-19 16:46:55', '2026-05-19 16:46:55', 0, NULL, NULL, NULL),
(14, 6, 'Spam\r\n', 'post', 'pending', NULL, '2026-05-19 16:46:58', '2026-05-19 16:46:58', 0, NULL, NULL, NULL),
(15, 6, 'you suck bob\'s', 'post', 'removed', NULL, '2026-05-19 16:47:10', '2026-05-19 16:47:54', 0, NULL, NULL, NULL),
(16, 1, 'hi world', 'post', 'approved', NULL, '2026-05-19 16:47:13', '2026-05-19 16:47:28', 0, NULL, NULL, NULL),
(17, 6, 'ok naba?', 'post', 'pending', NULL, '2026-05-19 16:48:04', '2026-05-19 16:48:04', 0, NULL, NULL, NULL),
(18, 9, 'Nǐ hǎo shìjiè', 'post', 'pending', NULL, '2026-05-19 17:24:23', '2026-05-19 17:24:23', 0, NULL, NULL, NULL),
(19, 2, '📢 NEW OPPORTUNITY: We\'re hiring for a Marketing Assistant position! Apply now through GreenBridge.', 'announcement', 'approved', NULL, '2026-05-20 00:25:07', '2026-05-20 00:25:07', 0, NULL, NULL, NULL),
(20, 13, '📢 NEW OPPORTUNITY: We\'re hiring for a Retail Worker position! Apply now through GreenBridge.', 'announcement', 'approved', NULL, '2026-05-20 01:44:17', '2026-05-20 01:44:17', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','investigating','resolved','dismissed') DEFAULT 'open',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_name` varchar(100) DEFAULT NULL,
  `level` enum('learning','good','excellent') DEFAULT 'learning',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `university` varchar(255) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `total_hours` decimal(10,2) DEFAULT 0.00,
  `required_hours` decimal(10,2) DEFAULT 400.00,
  `status` enum('pending','active','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `fullname`, `university`, `college`, `course`, `year_level`, `contact`, `bio`, `total_hours`, `required_hours`, `status`, `created_at`) VALUES
(1, 1, '2020111111', 'Juan Dela Cruz', 'De La Salle University - Dasmarinas', 'CEAT', 'BS Computer Engineering', '3rd Year', '09123456789', 'A Computer Engineering student with a strong interest in software development, system design, and embedded systems. I seek to build my skills in programming and hardware integration through practical application.', 0.00, 400.00, 'active', '2026-05-19 13:59:41'),
(2, 4, '2020111112', 'Sharon Rainsworth', 'De La Salle University - Dasmarinas', 'CAS', 'BS Psychology', '3rd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-19 14:27:28'),
(4, 6, '123456789', 'Marcus Pearson', 'De La Salle University', 'CCS', 'CPE', '3rd Year', '', '', 0.00, 400.00, 'active', '2026-05-19 14:32:07'),
(5, 7, '202131199', 'Maeryl Faith Bobadilla', 'De La Salle University', 'CEAT', 'Computer Engineering', '3rd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-19 16:27:25'),
(6, 8, '2020131198', 'Vesper Os', 'De La Salle University - Dasmarinas', 'CEAT', 'BS Information Technology', '2nd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-19 16:29:12'),
(7, 9, '202180818', 'Mirai Asano', 'De La Salle University Dasmarinas', 'CEAT', 'BS Computer Engineering', '3rd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-19 17:24:01'),
(8, 10, '20208200', 'Vesper Ros', 'De La Salle University Dasmarinas', 'CEAT', 'BS Computer Science', '3rd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-19 22:41:47'),
(9, 11, '20', 'MAERYL FAITH BOBADILLA', 'De La Salle University', 'CEAT', 'Computer Engineering', '3rd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-20 00:19:19'),
(10, 12, '2021001', 'Chapel Roan', 'De La Salle University - Dasmarinas', 'CCS', 'BS Accountancy', '2nd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-20 01:39:18'),
(11, 14, '10', 'Angeline Serrato', 'De La Salle University', 'CEAT', 'CPE32', '3rd Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-20 01:53:12'),
(12, 15, '73833383', 'Serrato, Janelle Annika D.', 'De La Salle University', 'CCS', 'Computer ', '4th Year', NULL, NULL, 0.00, 400.00, 'active', '2026-05-20 08:06:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_type` enum('student','company','admin') NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','active','rejected','suspended') DEFAULT 'pending',
  `rejected_reason` text DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `google_id` varchar(255) DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL,
  `microsoft_id` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `twofa_secret` varchar(255) DEFAULT NULL,
  `twofa_enabled` tinyint(1) DEFAULT 0,
  `twofa_backup_codes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_type`, `email`, `password`, `status`, `rejected_reason`, `profile_picture`, `verified_at`, `last_login`, `created_at`, `google_id`, `facebook_id`, `microsoft_id`, `reset_token`, `reset_token_expires`, `twofa_secret`, `twofa_enabled`, `twofa_backup_codes`) VALUES
(1, 'student', 'student@dlsud.edu.ph', '$2y$10$S9SQFJoy8X6JKMV5QFttaec.W00bnfWpMCmjJJ29LJ454mZVyO4FS', 'active', NULL, NULL, '2026-05-19 14:24:57', '2026-05-24 03:51:29', '2026-05-19 13:59:41', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(2, 'company', 'organization@gmail.com', '$2y$10$QT8QebyVBJOnCKBzg6jbS.WSIzOnV/rn6BIzFhPH0QeC9VzGj78uK', 'active', NULL, NULL, '2026-05-19 14:25:02', '2026-05-24 03:40:46', '2026-05-19 14:19:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(3, 'admin', 'admin@greenbridge.com', '$2y$12$XC3z7ox/rUoR3FUnu7lWlu.HKJwen3R4qaWAWDYnNSfcRTlA6XQ4i', 'active', NULL, NULL, '2026-05-19 14:23:37', '2026-05-24 03:54:26', '2026-05-19 14:23:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(4, 'student', 'sr@dlsud.edu.ph', '$2y$10$ZXJ2R/uIPnPIVIcRbDKM0uVRjvsL4M/04lzG3BaZRGNjqTfc1v/la', 'active', NULL, NULL, '2026-05-19 14:28:22', '2026-05-20 01:46:14', '2026-05-19 14:27:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(6, 'student', 'pearsonmarcus93@gmail.com', '', 'active', NULL, NULL, NULL, NULL, '2026-05-19 14:32:07', '102618464562925196922', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(7, 'student', 'maefai.bobadilla@gmail.com', '', 'active', NULL, NULL, NULL, NULL, '2026-05-19 16:27:25', '100844275157808966901', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(8, 'student', 'sidereusfool@gmail.com', '$2y$10$x3HoErjS0YJB7vTCpmWkqeIv4Qvj5j6deja7hVVSNLaLWxvx7o29y', 'active', NULL, NULL, '2026-05-19 16:29:29', NULL, '2026-05-19 16:29:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(9, 'student', 'miraif.asano@gmail.com', '', 'active', NULL, NULL, NULL, NULL, '2026-05-19 17:24:01', '101325758652961803251', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(10, 'student', 'v3sperdraws@gmail.com', '$2y$10$VfzQS2wqCjGnLy43lJPFTuJexBA.h4rndjNhkWtbXL3/SQR2YUTiO', 'active', NULL, NULL, '2026-05-19 22:42:22', '2026-05-20 01:51:47', '2026-05-19 22:41:47', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(11, 'student', 'bmp1200@dlsud.edu.ph', '', 'active', NULL, NULL, NULL, '2026-05-20 00:19:31', '2026-05-20 00:19:19', NULL, NULL, '92f63c57-f5a7-49e6-8741-8e82f2f5174f', NULL, NULL, NULL, 0, NULL),
(12, 'student', 'cp@dlsud.edu.ph', '$2y$10$18ET7Vg2xpL/XnnheoAWveWbFkAaHOg7cVX3SUxVsxjBVjures9lC', 'active', NULL, NULL, '2026-05-20 01:41:37', NULL, '2026-05-20 01:39:18', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(13, 'company', 'seinen@gmail.com', '$2y$10$gzLgHvokIh1vw1cIam1wYuP7UkaX.goJYH7LbaHhA2C6fFAHxR72i', 'active', NULL, NULL, '2026-05-20 01:41:51', '2026-05-20 01:47:58', '2026-05-20 01:40:37', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(14, 'student', 'angelineserrato10@gmail.com', '', 'active', NULL, NULL, NULL, NULL, '2026-05-20 01:53:12', '104945913798145775929', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(15, 'student', 'serratojanelle017@gmail.com', '', 'active', NULL, NULL, NULL, NULL, '2026-05-20 08:06:17', '113390056605626423743', NULL, NULL, NULL, NULL, NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_comments_is_flagged` (`is_flagged`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_participant1` (`participant1_id`),
  ADD KEY `idx_participant2` (`participant2_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `dtr_logs`
--
ALTER TABLE `dtr_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `flagged_comments`
--
ALTER TABLE `flagged_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comment_id` (`comment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `flagged_items`
--
ALTER TABLE `flagged_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item` (`item_type`,`item_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `flagged_posts`
--
ALTER TABLE `flagged_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `friend_id` (`friend_id`);

--
-- Indexes for table `internships`
--
ALTER TABLE `internships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `logbook_entries`
--
ALTER TABLE `logbook_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_ibfk_1` (`sender_id`),
  ADD KEY `messages_ibfk_2` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_posts_is_flagged` (`is_flagged`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_id` (`reported_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_facebook_id` (`facebook_id`),
  ADD KEY `idx_microsoft_id` (`microsoft_id`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dtr_logs`
--
ALTER TABLE `dtr_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flagged_comments`
--
ALTER TABLE `flagged_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flagged_items`
--
ALTER TABLE `flagged_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flagged_posts`
--
ALTER TABLE `flagged_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `internships`
--
ALTER TABLE `internships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `logbook_entries`
--
ALTER TABLE `logbook_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`participant1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`participant2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dtr_logs`
--
ALTER TABLE `dtr_logs`
  ADD CONSTRAINT `dtr_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dtr_logs_ibfk_2` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `flagged_comments`
--
ALTER TABLE `flagged_comments`
  ADD CONSTRAINT `flagged_comments_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `flagged_comments_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `flagged_posts`
--
ALTER TABLE `flagged_posts`
  ADD CONSTRAINT `flagged_posts_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `flagged_posts_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internships`
--
ALTER TABLE `internships`
  ADD CONSTRAINT `internships_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logbook_entries`
--
ALTER TABLE `logbook_entries`
  ADD CONSTRAINT `logbook_entries_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logbook_entries_ibfk_2` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `skills`
--
ALTER TABLE `skills`
  ADD CONSTRAINT `skills_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
