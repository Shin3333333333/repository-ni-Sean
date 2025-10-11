-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 11:39 PM
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

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel_cache_8d3f92cec846e4aeebbbb05de0392c22', 'i:1;', 1760127631),
('laravel_cache_8d3f92cec846e4aeebbbb05de0392c22:timer', 'i:1760127631;', 1760127631);

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
(12, 'BSIT 1-11', '1st', 1, 29, '2025-10-11 12:27:40', '2025-10-11 12:27:40');

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
(29, '1760210878_BSIT_Curriculum', 'curriculums/1760210878_BSIT_Curriculum.xlsx', '2025-10-11 11:27:58', '2025-10-11 11:27:58'),
(34, '1760215938_BSAIS_Curriculum', 'curriculums/1760215938_BSAIS_Curriculum.xlsx', '2025-10-11 12:52:18', '2025-10-11 12:52:18');

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
(16, '2025_10_08_203137_create_courses_table', 1);

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
(1, 'App\\Models\\User', 1, 'timetableToken', '46b50ca523300a6f8c0fd81f520f8bf2466f375f5093e5e41d8bc445cb9b3a10', '[\"*\"]', NULL, NULL, '2025-10-10 12:30:31', '2025-10-10 12:30:31'),
(2, 'App\\Models\\User', 1, 'timetableToken', '4df601e9f05a04547a166c6d14db3d6cf8f6cf41d2c0df0ec0953223b28c5726', '[\"*\"]', NULL, NULL, '2025-10-10 12:45:29', '2025-10-10 12:45:29'),
(3, 'App\\Models\\User', 1, 'timetableToken', 'b9afb7bcd373347d9c93ca7e1d67ac5f1c3a38222c2f38ecec480512c0f78fdf', '[\"*\"]', NULL, NULL, '2025-10-10 12:54:34', '2025-10-10 12:54:34'),
(4, 'App\\Models\\User', 1, 'timetableToken', '0109bffd030a9e7f80baacbb88acba7bcd2c9dc7907faed2dc4ef5aeb5d92996', '[\"*\"]', NULL, NULL, '2025-10-10 14:15:39', '2025-10-10 14:15:39'),
(5, 'App\\Models\\User', 1, 'timetableToken', '346824e2db6d25ac2e6d9ea7a105665a7d75b58de5c206ea339509dfa2099763', '[\"*\"]', NULL, NULL, '2025-10-11 09:27:07', '2025-10-11 09:27:07');

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
(5, 'Sean', 'Full-time', 'BSIT', 100, 'Th 10:00-10:00', 'Active', '2025-10-08 12:12:41', '2025-10-08 12:12:41'),
(7, 'adads', 'Full-time', 'adad', 100, 'Sat 10:00-10:00', 'Active', '2025-10-08 12:16:34', '2025-10-08 12:16:34'),
(8, 'asasd', 'Full-time', 'asdasd', 100, 'Sat 10:00-10:00', 'Active', '2025-10-08 12:18:08', '2025-10-08 12:18:08'),
(9, 'asdada', 'Full-time', 'adsda', 100, 'M 10:00-10:00', 'Active', '2025-10-10 12:43:34', '2025-10-10 12:43:34');

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
(3, '102', 100, 'Lecture', 'Available', '2025-10-08 12:07:41', '2025-10-08 12:07:41'),
(5, '104', 45, 'Ko', 'Unavailable', '2025-10-10 12:43:54', '2025-10-10 12:43:54');

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
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `name`) VALUES
(1, '1st Semester'),
(2, '2nd Semester');

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
('dNH1cxmoc0t6nvmS5WJO98v00PhmUf5WAZvLCEc3', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQnVIT3ZLcDZ5RmNKOGJjUHEyQ2lKM294dHdqR1k4R2NQQ3VvenZJaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1760126052),
('e4NA4gglTZYZT5uPSIaLSbQQMYHsI7b8P9gRUprE', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOWd5Y3dtTmJPZzdnN1VOME1MSmt6b1Q0V01vN2pkNWlWQXdkOWhDUSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1760134507),
('xLgHc0WaAbt0B0F1qFCCpcwXk6MseiJJaJbQ3sQu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiemZkWFMyNmRoUnE2YnJzRFF3TFM3VVhvMzRBVDRmN2VnYUtMVjh4ZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC8ud2VsbC1rbm93bi9hcHBzcGVjaWZpYy9jb20uY2hyb21lLmRldnRvb2xzLmpzb24iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1760215400);

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
(111, '1st Year', 1, 'IT101', 'Introduction to Computing', 3, 3, 'None', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(112, '1st Year', 1, 'GE101', 'Purposive Communication', 3, 3, 'None', 'General Ed', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(113, '1st Year', 1, 'IT102', 'Computer Programming 1', 3, 4, 'IT101', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(114, '1st Year', 1, 'GE102', 'Understanding the Self', 3, 3, 'None', 'General Ed', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(115, '2nd Year', 1, 'IT201', 'Data Structures & Algorithms', 3, 4, 'IT102', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(116, '2nd Year', 1, 'IT202', 'Database Management Systems', 3, 4, 'IT201', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(117, '3rd Year', 1, 'IT301', 'Web Development', 3, 4, 'IT202', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(118, '3rd Year', 1, 'IT302', 'Mobile App Development', 3, 4, 'IT301', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(119, '4th Year', 1, 'IT401', 'Capstone Project 1', 3, 3, 'IT302', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
(120, '4th Year', 1, 'IT402', 'Capstone Project 2', 3, 3, 'IT401', 'Major', '2025-10-11 12:27:40', '2025-10-11 12:27:40', 29, 12),
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
(144, '4th Year', 1, 'ACC402', 'Capstone Project in Accounting Systems', 3, 3, NULL, 'Major', '2025-10-11 12:52:18', '2025-10-11 12:52:18', 34, NULL);

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
(1, 'Admin', 'admin@example.com', NULL, '$2y$12$KG6d6UMm8LU6ta9cm4DG9eVnjQomcF6KnyXgaYUqALR3ejxbpQZB2', NULL, NULL, NULL, NULL, '2025-10-08 08:38:41', '2025-10-08 08:38:41');

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
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `curriculums`
--
ALTER TABLE `curriculums`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

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

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
