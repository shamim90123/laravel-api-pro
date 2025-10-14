-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table crm_backend_api.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.cache: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.cache_locks: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.failed_jobs: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.jobs: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.job_batches
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.job_batches: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.leads
CREATE TABLE IF NOT EXISTS `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `item_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sams_pay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sams_manage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sams_platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sams_pay_client_management` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booked_demo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.leads: ~0 rows (approximately)
INSERT INTO `leads` (`id`, `name`, `email`, `firstname`, `lastname`, `job_title`, `phone`, `city`, `link`, `item_id`, `sams_pay`, `sams_manage`, `sams_platform`, `sams_pay_client_management`, `booked_demo`, `comments`, `created_at`, `updated_at`) VALUES
	(4, 'Buckinghamshire New University', 'test@gmail.com', 'Chris', 'Garrett', 'Head of International Recruitment and Development', NULL, NULL, 'LInkedin Article - https://I%20hope%20you%20are%20well.%20%20%20I%20wanted%20to%20share%20my%20latest%20article%20on%20Linkedin%20with%20you.%20as%20we%20start%20to%20think%20about%20agents%20reviews,%20budget%20allocations%20and%20all%20the%20other%20\'housekeeping\'%20jobs%20that%20we%20store%20up%20for%20the%20summer%20season,%20I%20thought%20it%20might%20be%20useful%20to%20look%20at%20the%20idea%20of%20digital%20transformation%20and%20the%20impact%20on%20our%20business.%20%20%20Let%20me%20know%20what%20you%20think%20about%20the%20piece%20and%20look%20out%20for%20the%20second%20part.%20%20%20Click%20on%20the%20link%20to%20read%20the%20article.%20https://www.linkedin.com/pulse/digital-transformation-lip-service-lifeline-part-1-shamim-ghani-6hdve/?trackingId=QGmbidFocrRnJzmbrA8WZQ%253D%253D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-14 00:34:23', '2025-10-14 00:34:23');

-- Dumping structure for table crm_backend_api.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.migrations: ~6 rows (approximately)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2025_10_12_071754_create_personal_access_tokens_table', 2),
	(5, '2025_10_12_085318_create_permission_tables', 3),
	(6, '2025_10_12_092906_create_leads_table', 4),
	(7, '2025_10_14_064834_create_products_table', 5),
	(8, '2025_10_14_084419_create_lead_stages_table', 6);

-- Dumping structure for table crm_backend_api.model_has_permissions
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.model_has_permissions: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.model_has_roles
CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.model_has_roles: ~0 rows (approximately)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
	(1, 'App\\Models\\User', 1),
	(1, 'App\\Models\\User', 9);

-- Dumping structure for table crm_backend_api.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.password_reset_tokens: ~0 rows (approximately)

-- Dumping structure for table crm_backend_api.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.permissions: ~4 rows (approximately)
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'posts.view', 'web', '2025-10-12 02:57:09', '2025-10-12 02:57:09'),
	(2, 'posts.create', 'web', '2025-10-12 02:57:09', '2025-10-12 02:57:09'),
	(3, 'posts.update', 'web', '2025-10-12 02:57:09', '2025-10-12 02:57:09'),
	(4, 'posts.delete', 'web', '2025-10-12 02:57:09', '2025-10-12 02:57:09'),
	(5, 'users.view', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(6, 'users.create', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(7, 'users.update', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(8, 'users.delete', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(9, 'leads.view', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(10, 'leads.create', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(11, 'leads.update', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(12, 'leads.delete', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50');

-- Dumping structure for table crm_backend_api.personal_access_tokens
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.personal_access_tokens: ~1 rows (approximately)
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
	(15, 'App\\Models\\User', 9, 'api', 'eaeeed2116891858d9d0abb89d786a9cfddfc08eb8fa02a796f4b3bde146abeb', '["*"]', NULL, NULL, '2025-10-13 05:27:50', '2025-10-13 05:27:50'),
	(27, 'App\\Models\\User', 1, 'api', 'c6bd35909503300cf7032b76414cd3c846c8b7ba51bd9b006351d30e7421c839', '["*"]', '2025-10-14 03:29:21', NULL, '2025-10-14 03:21:14', '2025-10-14 03:29:21');

-- Dumping structure for table crm_backend_api.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.products: ~4 rows (approximately)
INSERT INTO `products` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'SAMS Manage', 'active', '2025-10-14 01:03:35', '2025-10-14 01:19:31'),
	(2, 'SAMS Pay', 'active', '2025-10-14 01:03:58', '2025-10-14 01:19:41'),
	(3, 'SAMS Perform', 'active', '2025-10-14 01:05:53', '2025-10-14 01:19:57'),
	(4, 'SAMS Pay Management', 'active', '2025-10-14 01:07:04', '2025-10-14 01:20:26');

-- Dumping structure for table crm_backend_api.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.roles: ~2 rows (approximately)
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'admin', 'web', '2025-10-12 02:57:09', '2025-10-12 02:57:09'),
	(2, 'user', 'web', '2025-10-12 02:57:09', '2025-10-12 02:57:09'),
	(3, 'manager', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(4, 'staff', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50'),
	(5, 'viewer', 'web', '2025-10-13 05:01:50', '2025-10-13 05:01:50');

-- Dumping structure for table crm_backend_api.role_has_permissions
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.role_has_permissions: ~5 rows (approximately)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(4, 1),
	(5, 1),
	(6, 1),
	(7, 1),
	(8, 1),
	(9, 1),
	(10, 1),
	(11, 1),
	(12, 1),
	(1, 2),
	(5, 3),
	(9, 3),
	(10, 3),
	(11, 3),
	(12, 3),
	(9, 4),
	(10, 4),
	(9, 5);

-- Dumping structure for table crm_backend_api.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.sessions: ~6 rows (approximately)
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('C8UfiSm2EQpxrX9Kcc5Nqw2QCJFSUvvQny4Mestz', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRmRwbzdmbXRDZncweWtRUXl1SVlxZ3prMnlaSzl5U21heFlmZU9jMyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9jcm0tYmFja2VuZC1hcGkudGVzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760253369),
	('k32fRNaE2iR4gdKMb7xhaxmRVulp8LT7MIJS2VW8', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYlNNYjJXTW4weUw3WTdEUnlUdTNwSlY0WG82RnNMc3RPUjFpRllaaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly9sb2NhbGhvc3QvY3JtLWJhY2tlbmQtYXBpL3B1YmxpYyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760252604),
	('kBfvEAUEusBvygqIMSyzZsE5obOSydV9Yct66qr2', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMjNyUkNUaExsNjROUXpjMzE4dnJVeHNJRHA2THdPdzhyTWFKNDFwVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9jcm0tYmFja2VuZC1hcGkudGVzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760332098),
	('q1LKLLdZr74SQ6YFLNCOMGTibNBIPxYiAjWTOlqK', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWnE2SGFzbjlrbDZYWjVUeEZxSWhieVJDMFpvM25uMktsbnRLU1p4eSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly9sb2NhbGhvc3QvY3JtLWJhY2tlbmQtYXBpL3B1YmxpYyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760252597),
	('Vf5VpuKknslOjOkWH5rxVZAKF2vetI6PakBSo9Fp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWTNGSXFTbUJ2MkJNRFZLN1Izb1hzaVRmWHVyQW1xVWRBempuM2tQSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9jcm0tYmFja2VuZC1hcGkudGVzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760332098),
	('W3tCBFMPKRlKdp6M5c3B2yWvnCZ0mud9MmV8V8sf', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWXg1bUkwcHpnbzZRZk1TY0dDR3pJUjJQemVIZThvdzFUTG5adWJVbyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9jcm0tYmFja2VuZC1hcGkudGVzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760332098);

-- Dumping structure for table crm_backend_api.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table crm_backend_api.users: ~3 rows (approximately)
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, 'Admin User', 'admin@gmail.com', NULL, '$2y$12$Vo9tTiu.uQpj.U7g2/rpn.QgWq1WyGbDOvVBVGrs7u.XS.16Bmjdi', NULL, '2025-10-13 02:39:57', '2025-10-13 05:30:17'),
	(9, 'Shamim Reza', 'shamimreza@revinr.com', NULL, '$2y$12$QItkFokRE3uGzfD4WvVW/ufAVRbNoZAkcofgkAvAFZHAdl93njuMC', NULL, '2025-10-13 05:27:38', '2025-10-13 05:27:38');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
