-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2025 at 03:38 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_scheduler`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `year` varchar(255) NOT NULL,
  `students` int(11) NOT NULL,
  `curriculum_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`, `year`, `students`, `curriculum_id`, `created_at`, `updated_at`) VALUES
(2, 'BSAIS 1-11', '1st Year\r\n', 45, 5, '2025-10-14 11:33:24', '2025-10-14 11:37:26'),
(6, 'BSIT 1-11', '1st Year', 45, 8, '2025-10-14 11:40:17', '2025-10-14 11:40:17'),
(7, 'BSA 1-11', '1st Year', 45, 10, '2025-10-14 11:46:12', '2025-10-14 11:46:12'),
(11, 'BSEN 1-11', '1st Year', 1, 18, '2025-10-18 17:19:40', '2025-10-18 17:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `curriculums`
--

CREATE TABLE `curriculums` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `curriculums`
--

INSERT INTO `curriculums` (`id`, `name`, `file_path`, `created_at`, `updated_at`) VALUES
(1, '1760469900_BSIT_Curriculum', 'curriculums/1760469900_BSIT_Curriculum.xlsx', '2025-10-14 11:25:01', '2025-10-14 11:25:01'),
(2, '1760469925_BSAIS_Curriculum', 'curriculums/1760469925_BSAIS_Curriculum.xlsx', '2025-10-14 11:25:25', '2025-10-14 11:25:25'),
(3, '1760470063_BSIT_Curriculum', 'curriculums/1760470063_BSIT_Curriculum.xlsx', '2025-10-14 11:27:43', '2025-10-14 11:27:43'),
(4, '1760470075_BSIT_Curriculum', 'curriculums/1760470075_BSIT_Curriculum.xlsx', '2025-10-14 11:27:55', '2025-10-14 11:27:55'),
(5, '1760470376_BSAIS_Curriculum', 'curriculums/1760470376_BSAIS_Curriculum.xlsx', '2025-10-14 11:32:56', '2025-10-14 11:32:56'),
(6, '1760470574_BSA_Curriculum', 'curriculums/1760470574_BSA_Curriculum.xlsx', '2025-10-14 11:36:14', '2025-10-14 11:36:14'),
(7, '1760470740_BSIT_Curriculum', 'curriculums/1760470740_BSIT_Curriculum.xlsx', '2025-10-14 11:39:00', '2025-10-14 11:39:00'),
(8, '1760470804_BSIT_Curriculum', 'curriculums/1760470804_BSIT_Curriculum.xlsx', '2025-10-14 11:40:04', '2025-10-14 11:40:04'),
(9, '1760471054_BSA_Curriculum', 'curriculums/1760471054_BSA_Curriculum.xlsx', '2025-10-14 11:44:14', '2025-10-14 11:44:14'),
(10, '1760471162_BSA_Curriculum', 'curriculums/1760471162_BSA_Curriculum.xlsx', '2025-10-14 11:46:02', '2025-10-14 11:46:02'),
(14, '1760835164_BSIT_Curriculum', 'curriculums/1760835164_BSIT_Curriculum.xlsx', '2025-10-18 16:52:44', '2025-10-18 16:52:44'),
(18, '1760836696_Entrepreneurship_Curriculum', 'curriculums/1760836696_Entrepreneurship_Curriculum.xlsx', '2025-10-18 17:18:16', '2025-10-18 17:18:16');

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--

CREATE TABLE `error_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `error_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ai_suggestion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_08_14_170933_add_two_factor_columns_to_users_table', 1),
(5, '2025_09_25_203149_create_years_table', 1),
(6, '2025_09_25_203200_create_subjects_table', 1),
(7, '2025_09_25_203231_create_professors_table', 1),
(8, '2025_09_25_203249_create_rooms_table', 1),
(9, '2025_09_25_203300_create_schedules_table', 1),
(10, '2025_09_25_203310_create_error_logs_table', 1),
(11, '2025_09_25_203322_create_admins_table', 1),
(12, '2025_09_28_114955_add_timetable_columns_to_schedules_table', 1),
(13, '2025_09_28_yyyyyy_add_timetable_columns_to_schedules_table', 1),
(14, '2025_10_06_164738_create_personal_access_tokens_table', 1),
(15, '2025_10_08_162703_create_curriculums_table', 1),
(16, '2025_10_08_203137_create_courses_table', 1),
(17, '2025_10_14_190906_create_pending_schedules_table', 1),
(18, '2025_10_14_192726_create_semesters_table', 2),
(19, '2025_10_15_191349_add_batch_id_to_pending_schedules_table', 3),
(20, '2025_10_15_192436_add_batch_id_to_pending_schedules_table', 4);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_schedules`
--

CREATE TABLE `pending_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_id` varchar(255) DEFAULT NULL,
  `faculty` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `time` varchar(255) DEFAULT NULL,
  `classroom` varchar(255) DEFAULT NULL,
  `course_code` varchar(255) DEFAULT NULL,
  `course_section` varchar(50) DEFAULT NULL,
  `units` int(11) NOT NULL DEFAULT 0,
  `academicYear` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `status` enum('pending','finalized') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_schedules`
--

INSERT INTO `pending_schedules` (`id`, `user_id`, `batch_id`, `faculty`, `subject`, `time`, `classroom`, `course_code`, `course_section`, `units`, `academicYear`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(641, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Business Mathematics', 'Tue 13:00-16:00', '103', 'ACC101', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(642, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Financial Accounting and Reporting', 'Tue 18:00-21:00', '103', 'ACC102', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(643, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Fundamentals of Accounting', 'Wed 18:00-21:00', '104', 'ACC201', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(644, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Cost Accounting and Control', 'Wed 06:00-09:00', '103', 'ACC202', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(645, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Auditing Theory', 'Fri 13:00-16:00', '103', 'ACC301', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(646, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Management Advisory Services', 'Mon 13:00-16:00', '101', 'ACC302', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(647, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Business Law and Taxation', 'Sat 06:00-09:00', '101', 'ACC401', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(648, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Osabel', 'Capstone Project in Accounting Systems', 'Tue 06:00-09:00', '104', 'ACC402', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(649, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Calip', 'Purposive Communication', 'Tue 06:00-09:00', '103', 'GE101', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(650, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Calip', 'Understanding the Self', 'Tue 13:00-16:00', '101', 'GE102', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(651, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Prof Essor', 'Ethics', 'Tue 06:00-09:00', '101', 'GE201', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(652, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Prof Essor', 'Purposive Communication', 'Tue 18:00-21:00', '101', 'GE101', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(653, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Prof Essor', 'Understanding the Self', 'Fri 18:00-21:00', '102', 'GE102', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(654, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Prof Essor', 'Purposive Communication', 'Sat 13:00-16:00', '102', 'GE101', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(655, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Sean', 'Accounting Information Systems', 'Sat 06:00-09:00', '103', 'IT201', 'BSAIS 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(656, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Sean', 'Introduction to Computing', 'Sat 18:00-21:00', '101', 'IT101', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(657, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Sean', 'Computer Programming 1', 'Thu 06:00-09:00', '102', 'IT102', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(658, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Sean', 'Data Structures & Algorithms', 'Fri 06:00-09:00', '104', 'IT201', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(659, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'JE', 'Database Management Systems', 'Wed 06:00-09:00', '101', 'IT202', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(660, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'JE', 'Web Development', 'Tue 13:00-16:00', '102', 'IT301', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(661, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'JE', 'Mobile App Development', 'Tue 18:00-21:00', '102', 'IT302', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(662, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'JE', 'Capstone Project 1', 'Sat 18:00-21:00', '102', 'IT401', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(663, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'JE', 'Capstone Project 2', 'Thu 13:00-16:00', '104', 'IT402', 'BSIT 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(664, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'JE', 'Introduction to Computing', 'Fri 13:00-16:00', '104', 'IT101', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(665, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Financial Accounting 1', 'Mon 18:00-21:00', '103', 'ACC101', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(666, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Financial Accounting 2', 'Tue 13:00-16:00', '104', 'ACC102', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(667, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Cost Accounting 1', 'Tue 18:00-21:00', '104', 'ACC201', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(668, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Cost Accounting 2', 'Thu 06:00-09:00', '104', 'ACC202', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(669, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Auditing 1', 'Fri 18:00-21:00', '104', 'ACC301', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(670, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Auditing 2', 'Sat 06:00-09:00', '104', 'ACC302', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(671, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Advanced Financial Accounting', 'Sat 13:00-16:00', '104', 'ACC401', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08'),
(672, NULL, 'ea703f1f-704c-4860-a603-05e67e827d95', 'Renjard', 'Taxation', 'Sat 18:00-21:00', '104', 'ACC402', 'BSA 1-11', 3, '2025-2026', '1st Semester', 'pending', '2025-10-18 15:29:19', '2025-10-18 15:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'timetableToken', 'e411381c61cc1c519cdbedcf05d5d279fe2f80d27d5d55659b73e31196e32efb', '[\"*\"]', NULL, NULL, '2025-10-15 09:34:23', '2025-10-15 09:34:23'),
(2, 'App\\Models\\User', 1, 'timetableToken', '26cad069eb98769dd996f31eea373158eb61e8b63517b8c74a6a0dcc9c42adbc', '[\"*\"]', NULL, NULL, '2025-10-15 09:34:24', '2025-10-15 09:34:24'),
(3, 'App\\Models\\User', 1, 'timetableToken', '95d8a03f2517e911f22fd3c066fe513b006c453b21dc204c46ae394fde5a27a8', '[\"*\"]', NULL, NULL, '2025-10-17 21:43:09', '2025-10-17 21:43:09'),
(4, 'App\\Models\\User', 1, 'timetableToken', 'bad5edcce5b2629c7fe90ef4506401d6520a94413d7c585b35c03fab1316cd5a', '[\"*\"]', NULL, NULL, '2025-10-17 21:43:10', '2025-10-17 21:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `professors`
--

CREATE TABLE `professors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `max_load` int(11) NOT NULL,
  `time_unavailable` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professors`
--

INSERT INTO `professors` (`id`, `name`, `type`, `department`, `max_load`, `time_unavailable`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Sean', 'Full-time', 'IT', 20, 'Mon 08:00–20:00, Tue 09:00–21:00', 'Active', '2025-10-14 11:22:32', '2025-10-14 11:22:32'),
(4, 'Calip', 'Full-time', 'GE', 20, 'Sat 08:00–20:00', 'Active', '2025-10-14 11:23:44', '2025-10-14 11:23:44'),
(5, 'Renjard', 'Full-time', 'ACC', 20, 'Wed 08:00–21:00', 'Active', '2025-10-14 11:24:25', '2025-10-14 12:40:49'),
(7, 'Prof Essor', 'Full-time', 'GE', 20, 'Thu 08:00–20:00', 'Active', '2025-10-14 11:41:30', '2025-10-14 11:41:30'),
(9, 'JE', 'Full-time', 'IT', 45, 'Mon 04:52–20:52', 'Active', '2025-10-14 11:48:11', '2025-10-14 11:52:28'),
(11, 'Osabel', 'Full-time', 'ACC', 20, 'Thu 03:54–21:52', 'Active', '2025-10-14 11:50:15', '2025-10-14 12:40:56'),
(15, 'uyhgjh', 'Full-time', 'uhvfjfh', 1, NULL, 'Active', '2025-10-15 09:34:43', '2025-10-15 09:34:43'),
(16, 'uyhgjh', 'Full-time', 'uhvfjfh', 1, NULL, 'Active', '2025-10-15 09:34:44', '2025-10-15 09:34:44');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `status`, `created_at`, `updated_at`) VALUES
(5, '103', 45, 'Lecture', 'Available', '2025-10-14 11:40:34', '2025-10-14 11:40:34'),
(9, '101', 45, 'Lecture', 'Available', '2025-10-14 11:55:04', '2025-10-14 11:55:04'),
(10, '102', 45, 'Lecture', 'Available', '2025-10-14 11:55:21', '2025-10-14 11:55:21'),
(11, '104', 45, 'Lecture', 'Available', '2025-10-14 12:37:51', '2025-10-14 12:37:51'),
(12, '106', 1, 'COMLAB', 'Available', '2025-10-15 09:35:08', '2025-10-15 09:35:08'),
(13, '106', 1, 'COMLAB', 'Available', '2025-10-15 09:35:08', '2025-10-15 09:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `day` varchar(255) NOT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `professor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, '1st Semester', '2025-10-14 11:27:37', '2025-10-14 11:27:37'),
(2, '2nd Semester', '2025-10-14 11:27:37', '2025-10-14 11:27:37'),
(3, '1st Year – 1st Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(4, '1st Year – 2nd Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(5, '2nd Year – 1st Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(6, '2nd Year – 2nd Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(7, '3rd Year – 1st Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(8, '3rd Year – 2nd Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(9, '4th Year – 1st Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43'),
(10, '4th Year – 2nd Semester', '2025-10-18 16:38:43', '2025-10-18 16:38:43');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('vh3ShLO3censvfGJ42LHWmX083o5zhpGa7Z7zRHG', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaEE5OHVHVlZ4bllkdGJxRUlwRzh0R2ptMWEyOFJIRkZvNXZjR0lrTSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC8ud2VsbC1rbm93bi9hcHBzcGVjaWZpYy9jb20uY2hyb21lLmRldnRvb2xzLmpzb24iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1760837843);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_level` varchar(255) NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_title` varchar(255) NOT NULL,
  `units` int(11) NOT NULL,
  `hours` int(11) NOT NULL,
  `pre_requisite` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `curriculum_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `year_level`, `semester_id`, `subject_code`, `subject_title`, `units`, `hours`, `pre_requisite`, `type`, `created_at`, `updated_at`, `curriculum_id`, `course_id`) VALUES
(71, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, 'None', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(72, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(73, '1st Year', 1, 'IT102', 'Computer Programming 1', 3, 4, 'IT101', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(74, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, 'None', 'General Ed', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(75, '2nd Year', 1, 'IT201', 'Data Structures & Algorithms', 3, 4, 'IT102', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(76, '2nd Year', 1, 'IT202', 'Database Management Systems', 3, 4, 'IT201', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(77, '3rd Year', 1, 'IT301', 'Web Development', 3, 4, 'IT202', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(78, '3rd Year', 1, 'IT302', 'Mobile App Development', 3, 4, 'IT301', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(79, '4th Year', 1, 'IT401', 'Capstone Project 1', 3, 3, 'IT302', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(80, '4th Year', 1, 'IT402', 'Capstone Project 2', 3, 3, 'IT401', 'Major', '2025-10-11 11:27:58', '2025-10-11 11:27:58', 29, NULL),
(133, '1st Year', 1, 'ACC101', 'Fundamentals of Accounting', 3, 3, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(134, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(135, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, NULL, 'General Ed', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(136, '1st Year', 1, 'ACC102', 'Business Mathematics', 3, 3, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(137, '2nd Year', 1, 'ACC201', 'Financial Accounting and Reporting', 3, 4, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(138, '2nd Year', 1, 'ACC202', 'Cost Accounting and Control', 3, 4, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(139, '2nd Year', 1, 'GE201', 'Ethics', 3, 3, NULL, 'General Ed', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(140, '3rd Year', 1, 'ACC301', 'Auditing Theory', 3, 4, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(141, '3rd Year', 1, 'ACC302', 'Management Advisory Services', 3, 4, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(142, '3rd Year', 1, 'IT201', 'Accounting Information Systems', 3, 3, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(143, '4th Year', 1, 'ACC401', 'Business Law and Taxation', 3, 3, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(144, '4th Year', 1, 'ACC402', 'Capstone Project in Accounting Systems', 3, 3, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL),
(157, '1st Year', 1, 'ACC101', 'Fundamentals of Accounting', 3, 3, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(158, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(159, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, NULL, 'General Ed', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(160, '1st Year', 1, 'ACC102', 'Business Mathematics', 3, 3, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(161, '2nd Year', 1, 'ACC201', 'Financial Accounting and Reporting', 3, 4, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(162, '2nd Year', 1, 'ACC202', 'Cost Accounting and Control', 3, 4, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(163, '2nd Year', 1, 'GE201', 'Ethics', 3, 3, NULL, 'General Ed', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(164, '3rd Year', 1, 'ACC301', 'Auditing Theory', 3, 4, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(165, '3rd Year', 1, 'ACC302', 'Management Advisory Services', 3, 4, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(166, '3rd Year', 1, 'IT201', 'Accounting Information Systems', 3, 3, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(167, '4th Year', 1, 'ACC401', 'Business Law and Taxation', 3, 3, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(168, '4th Year', 1, 'ACC402', 'Capstone Project in Accounting Systems', 3, 3, NULL, 'Major', '2025-10-14 11:32:56', '2025-10-14 11:32:56', 5, NULL),
(169, '1st Year', 1, 'ACC101', 'Fundamentals of Accounting', 3, 3, 'None', 'Major', '2025-10-14 11:33:24', '2025-10-14 11:37:26', 5, 2),
(170, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-14 11:33:24', '2025-10-14 11:37:26', 5, 2),
(171, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, 'None', 'General Ed', '2025-10-14 11:33:24', '2025-10-14 11:37:26', 5, 2),
(172, '1st Year', 1, 'ACC102', 'Business Mathematics', 3, 3, 'None', 'Major', '2025-10-14 11:33:24', '2025-10-14 11:37:26', 5, 2),
(173, '2nd Year', 1, 'ACC201', 'Financial Accounting and Reporting', 3, 4, 'None', 'Major', '2025-10-14 11:33:24', '2025-10-14 11:37:26', 5, 2),
(174, '2nd Year', 1, 'ACC202', 'Cost Accounting and Control', 3, 4, 'None', 'Major', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(175, '2nd Year', 1, 'GE201', 'Ethics', 3, 3, 'None', 'General Ed', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(176, '3rd Year', 1, 'ACC301', 'Auditing Theory', 3, 4, 'None', 'Major', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(177, '3rd Year', 1, 'ACC302', 'Management Advisory Services', 3, 4, 'None', 'Major', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(178, '3rd Year', 1, 'IT201', 'Accounting Information Systems', 3, 3, 'None', 'Major', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(179, '4th Year', 1, 'ACC401', 'Business Law and Taxation', 3, 3, 'None', 'Major', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(180, '4th Year', 1, 'ACC402', 'Capstone Project in Accounting Systems', 3, 3, 'None', 'Major', '2025-10-14 11:33:25', '2025-10-14 11:37:26', 5, 2),
(181, 'Year Level', NULL, 'Semester', 'Subject Code', 0, 0, NULL, 'Prerequisite', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(182, '1st Year', NULL, '1st Sem', 'ACC101', 0, 3, NULL, 'None', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(183, '1st Year', NULL, '1st Sem', 'GE101', 0, 3, NULL, 'None', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(184, '1st Year', NULL, '2nd Sem', 'ACC102', 0, 3, NULL, 'ACC101', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(185, '1st Year', NULL, '2nd Sem', 'IT101', 0, 3, NULL, 'None', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(186, '2nd Year', NULL, '1st Sem', 'ACC201', 0, 3, NULL, 'ACC102', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(187, '2nd Year', NULL, '2nd Sem', 'ACC202', 0, 3, NULL, 'ACC201', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(188, '3rd Year', NULL, '1st Sem', 'ACC301', 0, 3, NULL, 'ACC202', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(189, '3rd Year', NULL, '2nd Sem', 'ACC302', 0, 3, NULL, 'ACC301', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(190, '4th Year', NULL, '1st Sem', 'ACC401', 0, 3, NULL, 'ACC302', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(191, '4th Year', NULL, '2nd Sem', 'ACC402', 0, 3, NULL, 'ACC401', '2025-10-14 11:36:14', '2025-10-14 11:36:14', 6, NULL),
(203, 'Year Level', NULL, 'Semester', 'Subject Code', 0, 0, NULL, 'Prerequisite', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(204, '1st Year', NULL, '1st Sem', 'IT101', 0, 3, NULL, 'None', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(205, '1st Year', NULL, '1st Sem', 'GE101', 0, 3, NULL, 'None', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(206, '1st Year', NULL, '2nd Sem', 'IT102', 0, 3, NULL, 'IT101', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(207, '1st Year', NULL, '2nd Sem', 'GE102', 0, 3, NULL, 'None', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(208, '2nd Year', NULL, '1st Sem', 'IT201', 0, 3, NULL, 'IT102', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(209, '2nd Year', NULL, '2nd Sem', 'IT202', 0, 3, NULL, 'IT201', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(210, '3rd Year', NULL, '1st Sem', 'IT301', 0, 3, NULL, 'IT202', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(211, '3rd Year', NULL, '2nd Sem', 'IT302', 0, 3, NULL, 'IT301', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(212, '4th Year', NULL, '1st Sem', 'IT401', 0, 3, NULL, 'IT302', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(213, '4th Year', NULL, '2nd Sem', 'IT402', 0, 3, NULL, 'IT401', '2025-10-14 11:39:00', '2025-10-14 11:39:00', 7, NULL),
(225, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(226, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(227, '1st Year', 1, 'IT102', 'Computer Programming 1', 3, 4, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(228, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, NULL, 'General Ed', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(229, '2nd Year', 1, 'IT201', 'Data Structures & Algorithms', 3, 4, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(230, '2nd Year', 1, 'IT202', 'Database Management Systems', 3, 4, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(231, '3rd Year', 1, 'IT301', 'Web Development', 3, 4, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(232, '3rd Year', 1, 'IT302', 'Mobile App Development', 3, 4, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(233, '4th Year', 1, 'IT401', 'Capstone Project 1', 3, 3, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(234, '4th Year', 1, 'IT402', 'Capstone Project 2', 3, 3, NULL, 'Major', '2025-10-14 11:40:05', '2025-10-14 11:40:05', 8, NULL),
(235, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(236, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(237, '1st Year', 1, 'IT102', 'Computer Programming 1', 3, 4, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(238, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, 'None', 'General Ed', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(239, '2nd Year', 1, 'IT201', 'Data Structures & Algorithms', 3, 4, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(240, '2nd Year', 1, 'IT202', 'Database Management Systems', 3, 4, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(241, '3rd Year', 1, 'IT301', 'Web Development', 3, 4, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(242, '3rd Year', 1, 'IT302', 'Mobile App Development', 3, 4, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(243, '4th Year', 1, 'IT401', 'Capstone Project 1', 3, 3, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(244, '4th Year', 1, 'IT402', 'Capstone Project 2', 3, 3, 'None', 'Major', '2025-10-14 11:40:17', '2025-10-14 11:40:17', 8, 6),
(245, '1st Year', 1, 'ACC101', 'Financial Accounting 1', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(246, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(247, '1st Year', 1, 'ACC102', 'Financial Accounting 2', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(248, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(249, '2nd Year', 1, 'ACC201', 'Cost Accounting 1', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(250, '2nd Year', 1, 'ACC202', 'Cost Accounting 2', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(251, '3rd Year', 1, 'ACC301', 'Auditing 1', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(252, '3rd Year', 1, 'ACC302', 'Auditing 2', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(253, '4th Year', 1, 'ACC401', 'Advanced Financial Accounting', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(254, '4th Year', 1, 'ACC402', 'Taxation', 3, 3, NULL, 'Major', '2025-10-14 11:44:14', '2025-10-14 11:44:14', 9, NULL),
(255, '1st Year', 1, 'ACC101', 'Financial Accounting 1', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(256, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(257, '1st Year', 1, 'ACC102', 'Financial Accounting 2', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(258, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(259, '2nd Year', 1, 'ACC201', 'Cost Accounting 1', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(260, '2nd Year', 1, 'ACC202', 'Cost Accounting 2', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(261, '3rd Year', 1, 'ACC301', 'Auditing 1', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(262, '3rd Year', 1, 'ACC302', 'Auditing 2', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(263, '4th Year', 1, 'ACC401', 'Advanced Financial Accounting', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(264, '4th Year', 1, 'ACC402', 'Taxation', 3, 3, NULL, 'Major', '2025-10-14 11:46:02', '2025-10-14 11:46:02', 10, NULL),
(265, '1st Year', 1, 'ACC101', 'Financial Accounting 1', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(266, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(267, '1st Year', 1, 'ACC102', 'Financial Accounting 2', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(268, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(269, '2nd Year', 1, 'ACC201', 'Cost Accounting 1', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(270, '2nd Year', 1, 'ACC202', 'Cost Accounting 2', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(271, '3rd Year', 1, 'ACC301', 'Auditing 1', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(272, '3rd Year', 1, 'ACC302', 'Auditing 2', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(273, '4th Year', 1, 'ACC401', 'Advanced Financial Accounting', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(274, '4th Year', 1, 'ACC402', 'Taxation', 3, 3, 'None', 'Major', '2025-10-14 11:46:12', '2025-10-14 11:46:12', 10, 7),
(275, '1st Year – 1st Semester', 3, 'ENT101', 'Introduction to Entrepreneurship', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(276, '1st Year – 1st Semester', 3, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(277, '1st Year – 1st Semester', 3, 'ENT102', 'Business Planning', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(278, '1st Year – 1st Semester', 3, 'IT101', 'Introduction to Computing', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(279, '1st Year – 2nd Semester', 4, 'ENT103', 'Marketing Fundamentals', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(280, '1st Year – 2nd Semester', 4, 'ENT104', 'Financial Management', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(281, '1st Year – 2nd Semester', 4, 'GE102', 'Critical Thinking', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(282, '1st Year – 2nd Semester', 4, 'IT102', 'Computer Applications', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(283, '2nd Year – 1st Semester', 5, 'ENT201', 'Entrepreneurial Law', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(284, '2nd Year – 1st Semester', 5, 'ENT202', 'Operations Management', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(285, '2nd Year – 1st Semester', 5, 'GE201', 'Ethics', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(286, '2nd Year – 1st Semester', 5, 'IT201', 'Database Fundamentals', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(287, '2nd Year – 2nd Semester', 6, 'ENT203', 'Business Simulation', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(288, '2nd Year – 2nd Semester', 6, 'ENT204', 'Small Business Management', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(289, '2nd Year – 2nd Semester', 6, 'GE202', 'Entrepreneurial Mindset', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(290, '2nd Year – 2nd Semester', 6, 'IT202', 'Web Development Basics', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(291, '3rd Year – 1st Semester', 7, 'ENT301', 'Innovation Management', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(292, '3rd Year – 1st Semester', 7, 'ENT302', 'Supply Chain Management', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(293, '3rd Year – 1st Semester', 7, 'GE301', 'Sociology', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(294, '3rd Year – 1st Semester', 7, 'IT301', 'Data Analytics', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(295, '3rd Year – 2nd Semester', 8, 'ENT303', 'Entrepreneurial Finance', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(296, '3rd Year – 2nd Semester', 8, 'ENT304', 'Strategic Management', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(297, '3rd Year – 2nd Semester', 8, 'GE302', 'Philosophy', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(298, '3rd Year – 2nd Semester', 8, 'IT302', 'Project Management Tools', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(299, '4th Year – 1st Semester', 9, 'ENT401', 'Capstone Project 1', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(300, '4th Year – 1st Semester', 9, 'ENT402', 'Business Ethics', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(301, '4th Year – 1st Semester', 9, 'GE401', 'Leadership', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(302, '4th Year – 2nd Semester', 10, 'ENT403', 'Capstone Project 2', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(303, '4th Year – 2nd Semester', 10, 'ENT404', 'Global Entrepreneurship', 3, 3, NULL, 'Major', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(304, '4th Year – 2nd Semester', 10, 'GE402', 'Innovation & Society', 3, 3, NULL, 'General Ed', '2025-10-18 16:38:43', '2025-10-18 16:38:43', 12, NULL),
(305, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(306, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, NULL, 'General Ed', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(307, '1st Year', 1, 'IT102', 'Computer Programming 1', 3, 4, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(308, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, NULL, 'General Ed', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(309, '2nd Year', 1, 'IT201', 'Data Structures & Algorithms', 3, 4, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(310, '2nd Year', 1, 'IT202', 'Database Management Systems', 3, 4, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(311, '3rd Year', 1, 'IT301', 'Web Development', 3, 4, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(312, '3rd Year', 1, 'IT302', 'Mobile App Development', 3, 4, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(313, '4th Year', 1, 'IT401', 'Capstone Project 1', 3, 3, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(314, '4th Year', 1, 'IT402', 'Capstone Project 2', 3, 3, NULL, 'Major', '2025-10-18 16:52:44', '2025-10-18 16:52:44', 14, NULL),
(489, '1st Year', 1, 'ENT101', 'Introduction to Entrepreneurship', 3, 3, 'None', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(490, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(491, '1st Year', 1, 'ENT102', 'Business Planning', 3, 3, 'ENT101', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(492, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, 'None', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(493, '1st Year', 2, 'ENT103', 'Marketing Fundamentals', 3, 3, 'ENT102', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(494, '1st Year', 2, 'ENT104', 'Financial Management', 3, 3, 'ENT102', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(495, '1st Year', 2, 'GE102', 'Critical Thinking', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(496, '1st Year', 2, 'IT102', 'Computer Applications', 3, 3, 'IT101', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(497, '2nd Year', 1, 'ENT201', 'Entrepreneurial Law', 3, 3, 'ENT104', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(498, '2nd Year', 1, 'ENT202', 'Operations Management', 3, 3, 'ENT104', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(499, '2nd Year', 1, 'GE201', 'Ethics', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(500, '2nd Year', 1, 'IT201', 'Database Fundamentals', 3, 3, 'IT102', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(501, '2nd Year', 2, 'ENT203', 'Business Simulation', 3, 3, 'ENT202', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(502, '2nd Year', 2, 'ENT204', 'Small Business Management', 3, 3, 'ENT202', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(503, '2nd Year', 2, 'GE202', 'Entrepreneurial Mindset', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(504, '2nd Year', 2, 'IT202', 'Web Development Basics', 3, 3, 'IT201', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(505, '3rd Year', 1, 'ENT301', 'Innovation Management', 3, 3, 'ENT203', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(506, '3rd Year', 1, 'ENT302', 'Supply Chain Management', 3, 3, 'ENT203', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(507, '3rd Year', 1, 'GE301', 'Sociology', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(508, '3rd Year', 1, 'IT301', 'Data Analytics', 3, 3, 'IT202', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(509, '3rd Year', 2, 'ENT303', 'Entrepreneurial Finance', 3, 3, 'ENT302', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(510, '3rd Year', 2, 'ENT304', 'Strategic Management', 3, 3, 'ENT302', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(511, '3rd Year', 2, 'GE302', 'Philosophy', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(512, '3rd Year', 2, 'IT302', 'Project Management Tools', 3, 3, 'IT301', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(513, '4th Year', 1, 'ENT401', 'Capstone Project 1', 3, 3, 'ENT304', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(514, '4th Year', 1, 'ENT402', 'Business Ethics', 3, 3, 'ENT304', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(515, '4th Year', 1, 'GE401', 'Leadership', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(516, '4th Year', 2, 'ENT403', 'Capstone Project 2', 3, 3, 'ENT401', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(517, '4th Year', 2, 'ENT404', 'Global Entrepreneurship', 3, 3, 'ENT402', 'Major', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(518, '4th Year', 2, 'GE402', 'Innovation & Society', 3, 3, 'None', 'General Ed', '2025-10-18 17:18:16', '2025-10-18 17:18:16', 18, NULL),
(519, '1st Year', 1, 'ENT101', 'Introduction to Entrepreneurship', 3, 3, 'None', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(520, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(521, '1st Year', 1, 'ENT102', 'Business Planning', 3, 3, 'ENT101', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(522, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, 'None', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(523, '1st Year', 2, 'ENT103', 'Marketing Fundamentals', 3, 3, 'ENT102', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(524, '1st Year', 2, 'ENT104', 'Financial Management', 3, 3, 'ENT102', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(525, '1st Year', 2, 'GE102', 'Critical Thinking', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(526, '1st Year', 2, 'IT102', 'Computer Applications', 3, 3, 'IT101', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(527, '2nd Year', 1, 'ENT201', 'Entrepreneurial Law', 3, 3, 'ENT104', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(528, '2nd Year', 1, 'ENT202', 'Operations Management', 3, 3, 'ENT104', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(529, '2nd Year', 1, 'GE201', 'Ethics', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(530, '2nd Year', 1, 'IT201', 'Database Fundamentals', 3, 3, 'IT102', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(531, '2nd Year', 2, 'ENT203', 'Business Simulation', 3, 3, 'ENT202', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(532, '2nd Year', 2, 'ENT204', 'Small Business Management', 3, 3, 'ENT202', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(533, '2nd Year', 2, 'GE202', 'Entrepreneurial Mindset', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(534, '2nd Year', 2, 'IT202', 'Web Development Basics', 3, 3, 'IT201', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(535, '3rd Year', 1, 'ENT301', 'Innovation Management', 3, 3, 'ENT203', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(536, '3rd Year', 1, 'ENT302', 'Supply Chain Management', 3, 3, 'ENT203', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(537, '3rd Year', 1, 'GE301', 'Sociology', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(538, '3rd Year', 1, 'IT301', 'Data Analytics', 3, 3, 'IT202', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(539, '3rd Year', 2, 'ENT303', 'Entrepreneurial Finance', 3, 3, 'ENT302', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(540, '3rd Year', 2, 'ENT304', 'Strategic Management', 3, 3, 'ENT302', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(541, '3rd Year', 2, 'GE302', 'Philosophy', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(542, '3rd Year', 2, 'IT302', 'Project Management Tools', 3, 3, 'IT301', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(543, '4th Year', 1, 'ENT401', 'Capstone Project 1', 3, 3, 'ENT304', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(544, '4th Year', 1, 'ENT402', 'Business Ethics', 3, 3, 'ENT304', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(545, '4th Year', 1, 'GE401', 'Leadership', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(546, '4th Year', 2, 'ENT403', 'Capstone Project 2', 3, 3, 'ENT401', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(547, '4th Year', 2, 'ENT404', 'Global Entrepreneurship', 3, 3, 'ENT402', 'Major', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11),
(548, '4th Year', 2, 'GE402', 'Innovation & Society', 3, 3, 'None', 'General Ed', '2025-10-18 17:19:40', '2025-10-18 17:19:40', 18, 11);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@example.com', NULL, '$2y$12$q.Uu.TB2hleotTqtNCqOM.hrEDiQlnU5QPJc85yWEdgf6b3vHkr7S', NULL, NULL, NULL, NULL, '2025-10-14 11:28:48', '2025-10-14 11:28:48');

-- --------------------------------------------------------

--
-- Table structure for table `years`
--

CREATE TABLE `years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `courses_curriculum_id_foreign` (`curriculum_id`);

--
-- Indexes for table `curriculums`
--
ALTER TABLE `curriculums`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `error_logs_schedule_id_foreign` (`schedule_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `pending_schedules`
--
ALTER TABLE `pending_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedules_room_id_foreign` (`room_id`),
  ADD KEY `schedules_professor_id_foreign` (`professor_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `semesters_name_unique` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `years`
--
ALTER TABLE `years`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `curriculums`
--
ALTER TABLE `curriculums`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pending_schedules`
--
ALTER TABLE `pending_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=673;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=549;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `years`
--
ALTER TABLE `years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_curriculum_id_foreign` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `error_logs_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
