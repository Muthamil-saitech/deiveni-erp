-- Adminer 5.4.2 MariaDB 10.11.19-MariaDB-cll-lve dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(40) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(120) NOT NULL,
  `user_role` varchar(60) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `target_type` varchar(120) DEFAULT NULL,
  `target_id` varchar(80) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `title` text NOT NULL,
  `title_ta` text DEFAULT NULL,
  `site_name` varchar(150) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `integrity_hash` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_logs_event_id_unique` (`event_id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_module_index` (`module`),
  KEY `audit_logs_target_id_index` (`target_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_severity_index` (`severity`),
  KEY `audit_logs_site_name_index` (`site_name`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`, `event_id`, `user_id`, `user_name`, `user_role`, `ip_address`, `user_agent`, `module`, `target_type`, `target_id`, `action`, `severity`, `title`, `title_ta`, `site_name`, `amount`, `old_values`, `new_values`, `details`, `integrity_hash`, `created_at`, `updated_at`) VALUES
(1,	'AUD-20261120-PC6I',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGOUT',	'info',	'User Murugan SM (Site Manager) logged out from session',	'பயனாளர் Murugan SM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\"}',	'sha256:d6c6e5d51533e8d5da041b1b30321b17701542904d73064c9f71c2e35160c3ed',	'2026-11-20 16:31:37',	'2026-11-20 16:31:37'),
(2,	'AUD-20261120-QIQA',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:e0afc6b8058ce7aabfaba403f76a0f05d1e7c386e88ad53687bd97dadd793aa6',	'2026-11-20 16:31:41',	'2026-11-20 16:31:41'),
(3,	'AUD-20261120-7AJX',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGIN',	'info',	'User Murugan SM (Site Manager) logged in to the system',	'பயனாளர் Murugan SM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:01ca1da47e24dc32548cd011f5247bdf444ccb8f5d00b6ce6b896bbd455567b4',	'2026-11-20 16:34:23',	'2026-11-20 16:34:23'),
(4,	'AUD-20261120-I1UD',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGOUT',	'info',	'User Murugan SM (Site Manager) logged out from session',	'பயனாளர் Murugan SM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\"}',	'sha256:04347f6e3a0ed46a04ff69193e4b4eaabcce7f69a360756a0f62d324613e5291',	'2026-11-20 16:34:41',	'2026-11-20 16:34:41'),
(5,	'AUD-20261120-UVJI',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:3b0f71ea13e183c6d39e148bc91d2f9f7e94c14dea598d4213450fe80b7070a5',	'2026-11-20 16:34:43',	'2026-11-20 16:34:43'),
(6,	'AUD-20261120-WDZJ',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:e762b3b2f465e95401c10e577124a3dc49e1ba80777fe692f173ba356d3421a1',	'2026-11-20 16:35:10',	'2026-11-20 16:35:10'),
(7,	'AUD-20261120-K8P9',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:e32bc7e9a820f45cd34fc7b4b3bddf1003625cf245f6b23177f8fdaa204d8d1f',	'2026-11-20 16:35:12',	'2026-11-20 16:35:12'),
(8,	'AUD-20261120-QOKG',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-009',	'CREATE',	'info',	'Created new system user account: Sivasakthi S',	'புதிய பயனர் கணக்கு உருவாக்கப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"name\":\"Sivasakthi S\",\"email\":\"sivasakthi.saitech@gmail.com\",\"phone\":\"9874563210\",\"status\":\"Active\",\"role_id\":3,\"role\":\"account-manager\",\"id\":9}',	'{\"model\":\"User\",\"id\":9}',	'sha256:007caa5f85667e43c663571b7b0edaf0faed4e5425897c654ab231eec1fd4cd2',	'2026-11-20 16:36:02',	'2026-11-20 16:36:02'),
(9,	'AUD-20261120-UOPF',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-002',	'UPDATE',	'warning',	'Updated User (#USER-002) [fields: password]',	'User (#USER-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:a465b335e26cf1ce3366c8315ab9c37d5b0e25873eddc1a33e71750472246f59',	'2026-11-20 16:36:12',	'2026-11-20 16:36:12'),
(10,	'AUD-20261120-RQRX',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:b4ba0d1bbfaf6aaaeaca122cac584fa0f5944fc47d6142715a43f604cbd42186',	'2026-11-20 16:36:18',	'2026-11-20 16:36:18'),
(11,	'AUD-20261120-ZLUB',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-008',	'UPDATE',	'warning',	'Updated User (#USER-008) [fields: password]',	'User (#USER-008) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:5d6542091a2d9919f24beaf07d5057b8f7dcb540c09bb2f474c61e7ceed7c837',	'2026-11-20 16:36:21',	'2026-11-20 16:36:21'),
(12,	'AUD-20261120-Q5PD',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGIN',	'info',	'User Sivasakthi S (Account Manager) logged in to the system',	'பயனாளர் Sivasakthi S கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:f461b62c7a7c340072e9c9a1279a2f128cd0f4817f5d783c61e76d2bac61574d',	'2026-11-20 16:36:25',	'2026-11-20 16:36:25'),
(13,	'AUD-20261120-42YL',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-008',	'UPDATE',	'warning',	'Updated User (#USER-008) [fields: email]',	'User (#USER-008) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: email]',	'Head Office',	NULL,	'{\"email\":\"syed@gmail.com\"}',	'{\"email\":\"syed@deiveni.com\"}',	'{\"model\":\"User\",\"changed_fields\":[\"email\"]}',	'sha256:9a4207a16eeeb637603fcec12c50ecb8e5b6ee8e5112a26d98e60225c2065181',	'2026-11-20 16:36:37',	'2026-11-20 16:36:37'),
(14,	'AUD-20261120-ECS8',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:e825538107c6a8b1358a7fbf520090cdd41d56a8849805db93719a8f9989b1fc',	'2026-11-20 16:36:59',	'2026-11-20 16:36:59'),
(15,	'AUD-20261120-YB3X',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:69192ca2c931e074f8cabcb737d6f0fbf1c2f6039d0e94b1c3d4f2c49bec4568',	'2026-11-20 16:37:02',	'2026-11-20 16:37:02'),
(16,	'AUD-20261120-P80A',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGOUT',	'info',	'User Sivasakthi S (Account Manager) logged out from session',	'பயனாளர் Sivasakthi S அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\"}',	'sha256:01e3a8152145ff63f86666d67563d9e51d90d2b5ed7b1777e0e50d05eba77997',	'2026-11-20 16:39:03',	'2026-11-20 16:39:03'),
(17,	'AUD-20261120-LWJE',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:6ba0379f89b029b7ac2321d893d93a6aaaec17eb794ad20854c75f4430c7b7fd',	'2026-11-20 16:39:10',	'2026-11-20 16:39:10'),
(18,	'AUD-20261120-TKGS',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	NULL,	'#ID-1',	'CREATE',	'info',	'Created / Submitted Roles (#ID-1)',	'Roles படிவ செயல்முறை (#ID-1) பதிவு செய்யப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"name\":\"Owner\",\"description\":\"System Owner with full administrative access across all modules.\",\"menus\":[\"dashboard\",\"sites\",\"reports_profit_loss\",\"reports_labour_allocations\",\"reports_cash_handling\",\"reports_purchases_inward\",\"reports_warehouse_inventory\",\"reports_ledger\",\"users\",\"audit_logs\",\"profile\"]}',	'{\"endpoint\":\"roles\\/1\\/update\",\"method\":\"POST\",\"ip\":\"103.16.202.228\"}',	'sha256:cc690eaeef22954a39a8b22332fcb9b5e231ebaf4deaccaa30fc476d2879529b',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(19,	'AUD-20261120-EX9N',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:36000779a1163d77dcc5933d12f16b4d668436e2d2a41bacaf56e7b7fc00e102',	'2026-11-20 16:41:03',	'2026-11-20 16:41:03'),
(20,	'AUD-20261120-Q2C1',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:d6f3cf4c88a06e4fd1acc233da9663970a63cda9323986e6cec15b58e29b3640',	'2026-11-20 16:41:16',	'2026-11-20 16:41:16'),
(21,	'AUD-20261120-QHQH',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-002',	'UPDATE',	'warning',	'Updated User (#USER-002) [fields: password]',	'User (#USER-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:06aac0957748533773377cb0975953aa2b95bd7a1875101d8e19958d1b720000',	'2026-11-20 16:41:20',	'2026-11-20 16:41:20'),
(22,	'AUD-20261120-WUPV',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:36541b875fb14112beb7773f7bfbdc21bc76bdeae0741af2323d5d2e39846713',	'2026-11-20 16:41:28',	'2026-11-20 16:41:28'),
(23,	'AUD-20261120-Y9YM',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:5a915eb4a54a691f7a1b5158f522bf6079a7491a7d2b701b24ba10b62b109120',	'2026-11-20 16:41:54',	'2026-11-20 16:41:54'),
(24,	'AUD-20261120-GVUJ',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:b585787629458a183dffaafde2bceb79146c3cb5b536e6c0c64671e061c55827',	'2026-11-20 16:42:03',	'2026-11-20 16:42:03'),
(25,	'AUD-20261120-0WWP',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGIN',	'info',	'User Murugan SM (Site Manager) logged in to the system',	'பயனாளர் Murugan SM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:5a7d4ea6a125b8f9aa2a1ae62a24df97cc9b951d5c54a2dc760f08aa4943f8ea',	'2026-11-20 16:42:09',	'2026-11-20 16:42:09'),
(26,	'AUD-20261120-XBBW',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'sites',	'App\\Models\\Client',	'#CLIE-005',	'CREATE',	'info',	'Created new Client record #CLIE-005',	'புதிய Client பதிவு #CLIE-005 உருவாக்கப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"name\":\"P. Saravanan\",\"phone\":\"9952123456\",\"address\":\"45, Lake View Road, Othakadai, Madurai \\u2013 625107\",\"created_by\":2,\"id\":5}',	'{\"model\":\"Client\",\"id\":5}',	'sha256:d28baa1590eac328b2cb4d466e8ceb60888c4f3062f42c5c9e59008e862a789e',	'2026-11-20 16:43:13',	'2026-11-20 16:43:13'),
(27,	'AUD-20261120-3ZWH',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'sites',	'App\\Models\\ProjectSite',	'#PROJ-001',	'CREATE',	'info',	'Created new project site: Lake View Luxury Villa',	'புதிய கட்டுமான தளம் சேர்க்கப்பட்டது',	'Lake View Luxury Villa',	NULL,	NULL,	'{\"client_id\":5,\"name\":\"Lake View Luxury Villa\",\"location\":\"Othakadai, Madurai\",\"work_type\":\"New Build\",\"start_date\":\"2026-12-10\",\"end_date\":\"2027-09-10\",\"estimated_budget\":\"6500000\",\"spent\":0,\"status\":\"In Progress\",\"created_by\":2,\"id\":1}',	'{\"model\":\"ProjectSite\",\"id\":1}',	'sha256:e2b053f8823e256e54d93f5aa0ee9cca138b35ec5907b9f0773b7380ca922c2d',	'2026-11-20 16:43:13',	'2026-11-20 16:43:13'),
(28,	'AUD-20261120-GHID',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGOUT',	'info',	'User Murugan SM (Site Manager) logged out from session',	'பயனாளர் Murugan SM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\"}',	'sha256:65061893271917e8e1e970c1b2d5c9cb67151e7e80714ac0320d0028face1b0e',	'2026-11-20 16:43:33',	'2026-11-20 16:43:33'),
(29,	'AUD-20261120-TQWA',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:070f2dedc06076545159c0b3c6081ecc27e3d81637319ca84c0205d7e89df05c',	'2026-11-20 16:43:37',	'2026-11-20 16:43:37'),
(30,	'AUD-20261120-VIAT',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:3a8829198ee1d2b07e4f9d1c76bcc3acc50da89ab141fc97d02528f7707caeea',	'2026-11-20 16:43:50',	'2026-11-20 16:43:50'),
(31,	'AUD-20261120-BF8Q',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGIN',	'info',	'User Sivasakthi S (Account Manager) logged in to the system',	'பயனாளர் Sivasakthi S கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:975d53d5a272647a5c70d2c420f050668003bc814aee0672599bb0561317340f',	'2026-11-20 16:43:58',	'2026-11-20 16:43:58'),
(32,	'AUD-20261120-FQLW',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'masters',	'App\\Models\\TransactionType',	'#TRAN-001',	'CREATE',	'info',	'Created new TransactionType record #TRAN-001',	'புதிய TransactionType பதிவு #TRAN-001 உருவாக்கப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"name_en\":\"Cash Advance\",\"category\":\"Construction\",\"status\":\"Active\",\"id\":1}',	'{\"model\":\"TransactionType\",\"id\":1}',	'sha256:949f76d23e6d0abd17ab2885c8ca9dbcc2d45102ca15a1bcd20bfd763594290c',	'2026-11-20 16:44:40',	'2026-11-20 16:44:40'),
(33,	'AUD-20261120-VPVO',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'sites',	'App\\Models\\Client',	'#CLIE-006',	'CREATE',	'info',	'Created new Client record #CLIE-006',	'புதிய Client பதிவு #CLIE-006 உருவாக்கப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"name\":\"Parthasarathi\",\"phone\":\"9988776655\",\"address\":null,\"created_by\":8,\"id\":6}',	'{\"model\":\"Client\",\"id\":6}',	'sha256:005eb750bae6aacb3426bc26d6aaac830b6e0974e730348413c476f777d1a106',	'2026-11-20 16:44:52',	'2026-11-20 16:44:52'),
(34,	'AUD-20261120-OORO',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'sites',	'App\\Models\\ProjectSite',	'#PROJ-002',	'CREATE',	'info',	'Created new project site: Parthasarathi Sir Site',	'புதிய கட்டுமான தளம் சேர்க்கப்பட்டது',	'Parthasarathi Sir Site',	NULL,	NULL,	'{\"client_id\":6,\"name\":\"Parthasarathi Sir Site\",\"location\":\"Madurai\",\"work_type\":\"New Build\",\"start_date\":\"2026-11-20\",\"end_date\":\"2027-04-20\",\"estimated_budget\":\"1000000\",\"spent\":0,\"status\":\"In Progress\",\"created_by\":8,\"id\":2}',	'{\"model\":\"ProjectSite\",\"id\":2}',	'sha256:a4893c5d6e2aee1455864950712192f1b2efdfd3e38b848e18ecae6c824b903a',	'2026-11-20 16:44:52',	'2026-11-20 16:44:52'),
(35,	'AUD-20261120-WRRO',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:c14cf1b35c510a7583728597e91a595b1cd111dd4021f8568bb33960009114b3',	'2026-11-20 16:45:23',	'2026-11-20 16:45:23'),
(36,	'AUD-20261120-7CAC',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:20195f5d0ed5b42d30ff4e8f067947287e02a9c3137eed36f5321e52106a49de',	'2026-11-20 16:45:26',	'2026-11-20 16:45:26'),
(37,	'AUD-20261120-TBDN',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:e5ad98446f78b0057d691c947b3a5f2ee4a1ad57257330da179f843741c3ed1c',	'2026-11-20 16:56:44',	'2026-11-20 16:56:44'),
(38,	'AUD-20261120-7I9C',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:89f71539c24713eb1d0d0198cbe01205f6827066b3dc6a533cace38ce5f67dc3',	'2026-11-20 16:56:56',	'2026-11-20 16:56:56'),
(39,	'AUD-20261120-WPVO',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:9f67694715a12f6f289050d2e709559f21d12f3ce0214429a010048fd937edfd',	'2026-11-20 16:57:09',	'2026-11-20 16:57:09'),
(40,	'AUD-20261120-HF3E',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:81d8e6f75096fb93ecec1ca677ebed925927fa2d5a855f8aefaa87067c59a2a1',	'2026-11-20 16:57:12',	'2026-11-20 16:57:12'),
(41,	'AUD-20261120-CS2T',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'ledger',	'App\\Models\\Ledger',	'#LEDG-001',	'CREATE',	'info',	'Posted ledger journal entry #LEDG-001 (₹20,000.00)',	'பேரேட்டில் புதிய கணக்கு பதிவு #LEDG-001 செய்யப்பட்டது (₹20,000.00)',	'Parthasarathi Sir Site',	20000.00,	NULL,	'{\"entry_date\":\"2026-11-20\",\"flow\":\"income\",\"source\":\"Construction\",\"amount\":20000,\"project_site_id\":2,\"site_name\":\"Parthasarathi Sir Site\",\"client_name\":\"Parthasarathi\",\"cash_received_from\":\"client\",\"supplier_id\":null,\"supplier_name\":null,\"bank_id\":null,\"bank_name\":null,\"branch_id\":null,\"branch_name\":null,\"bank_customer_ref\":null,\"property_id\":null,\"property_name\":null,\"property_type_id\":null,\"unit_name\":null,\"office_expense_details\":null,\"transaction_type_id\":1,\"transaction_type_name\":\"Cash Advance\",\"transaction_type_value\":\"\",\"transaction_type_data\":\"[{\\\"id\\\":1,\\\"name\\\":\\\"Cash Advance\\\",\\\"category\\\":\\\"Construction\\\",\\\"value\\\":\\\"\\\"}]\",\"description\":\"Construction: Parthasarathi Sir Site (Parthasarathi)\",\"receipt_image\":null,\"daily_allocation_id\":null,\"wage_item_ids\":null,\"extra_workers\":null,\"wage_cash_source\":null,\"created_by\":3,\"id\":1}',	'{\"model\":\"Ledger\",\"id\":1}',	'sha256:e0b7b1f550a996af6dad7917dbe4843636c25fba9f9bd090921e26c89addfc5f',	'2026-11-20 16:57:29',	'2026-11-20 16:57:29'),
(42,	'AUD-20261120-0COS',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:5696f721849398228fd8eb460e7f6d199fb1aaabf555e509a9e7e8e3f58113ca',	'2026-11-20 16:58:02',	'2026-11-20 16:58:02'),
(43,	'AUD-20261120-PJ3Q',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-002',	'UPDATE',	'warning',	'Updated User (#USER-002) [fields: password]',	'User (#USER-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:ad9b9556f7559ab10939ef1f2c86362d15ae9c9de9667ce7633555b8b384d069',	'2026-11-20 16:58:28',	'2026-11-20 16:58:28'),
(44,	'AUD-20261120-EXII',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:e1d8f8f2131b15564564072e91dac244e6a5287405b84170b90c4450e214a612',	'2026-11-20 16:58:35',	'2026-11-20 16:58:35'),
(45,	'AUD-20261120-7PYO',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGIN',	'info',	'User Murugan SM (Site Manager) logged in to the system',	'பயனாளர் Murugan SM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:0a7d46a630d6cb5535e81a3010e95d69773804e64ae36b975e786a5eea6825fa',	'2026-11-20 16:58:43',	'2026-11-20 16:58:43'),
(46,	'AUD-20261120-WL5O',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'ledger',	'App\\Models\\Ledger',	'#LEDG-002',	'CREATE',	'info',	'Posted ledger journal entry #LEDG-002 (₹200,000.00)',	'பேரேட்டில் புதிய கணக்கு பதிவு #LEDG-002 செய்யப்பட்டது (₹200,000.00)',	'Parthasarathi Sir Site',	200000.00,	NULL,	'{\"entry_date\":\"2026-11-20\",\"flow\":\"income\",\"source\":\"Construction\",\"amount\":200000,\"project_site_id\":2,\"site_name\":\"Parthasarathi Sir Site\",\"client_name\":\"Parthasarathi\",\"cash_received_from\":\"owner\",\"supplier_id\":null,\"supplier_name\":null,\"bank_id\":null,\"bank_name\":null,\"branch_id\":null,\"branch_name\":null,\"bank_customer_ref\":null,\"property_id\":null,\"property_name\":null,\"property_type_id\":null,\"unit_name\":null,\"office_expense_details\":null,\"transaction_type_id\":1,\"transaction_type_name\":\"Cash Advance\",\"transaction_type_value\":\"\",\"transaction_type_data\":\"[{\\\"id\\\":1,\\\"name\\\":\\\"Cash Advance\\\",\\\"category\\\":\\\"Construction\\\",\\\"value\\\":\\\"\\\"}]\",\"description\":\"Construction: Parthasarathi Sir Site (Parthasarathi)\",\"receipt_image\":null,\"daily_allocation_id\":null,\"wage_item_ids\":null,\"extra_workers\":null,\"wage_cash_source\":null,\"created_by\":9,\"id\":2}',	'{\"model\":\"Ledger\",\"id\":2}',	'sha256:8bb19099c95023b7b65a84ef5884a8c4cca8c6a836437d901d0e57d802a7be5f',	'2026-11-20 16:59:23',	'2026-11-20 16:59:23'),
(47,	'AUD-20261120-0Z2R',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGOUT',	'info',	'User Murugan SM (Site Manager) logged out from session',	'பயனாளர் Murugan SM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\"}',	'sha256:4dedfff5e5a6005d14ae0194f041d279f19b9b1ce6b1c295f015105689a430f2',	'2026-11-20 16:59:31',	'2026-11-20 16:59:31'),
(48,	'AUD-20261120-B4T9',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:d62b515504b46096b3e4d99433628b88cb1438ffbb96800f43b8d40c14effe65',	'2026-11-20 16:59:35',	'2026-11-20 16:59:35'),
(49,	'AUD-20261120-VGXN',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:4b15148ba767d9720e9e0256728aa08d9136f2a017acf13f80145f7116fba301',	'2026-11-20 17:00:14',	'2026-11-20 17:00:14'),
(50,	'AUD-20261120-E8LE',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:c17847e431d43ee864072f29428591fb49a869f80041e71119e76d24b9598f9f',	'2026-11-20 17:00:23',	'2026-11-20 17:00:23'),
(51,	'AUD-20261120-4FKY',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:7f0a91ee62855b02a40f02e01e97c698c50a2d37c10696dbed4dec25a41414c7',	'2026-11-20 17:00:36',	'2026-11-20 17:00:36'),
(52,	'AUD-20261120-TBC9',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-003',	'UPDATE',	'warning',	'Updated User (#USER-003) [fields: password]',	'User (#USER-003) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:4064c57dc01b7e74348828cb65e8b0c80e48f5a426cfb0e5a5fd089aa413d9d1',	'2026-11-20 17:01:46',	'2026-11-20 17:01:46'),
(53,	'AUD-20261120-DZUL',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:ba5379e54460a386e186ab38a81ab1989ac3beefb5fe7711d79291ab50a4fe1c',	'2026-11-20 17:01:57',	'2026-11-20 17:01:57'),
(54,	'AUD-20261120-Z7YL',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'ledger',	'App\\Models\\Ledger',	'#LEDG-003',	'CREATE',	'info',	'Posted ledger journal entry #LEDG-003 (₹12,000.00)',	'பேரேட்டில் புதிய கணக்கு பதிவு #LEDG-003 செய்யப்பட்டது (₹12,000.00)',	'Lake View Luxury Villa',	12000.00,	NULL,	'{\"entry_date\":\"2026-11-20\",\"flow\":\"expense\",\"source\":\"Construction\",\"amount\":12000,\"project_site_id\":1,\"cash_transfer_id\":1,\"site_name\":\"Lake View Luxury Villa\",\"client_name\":\"P. Saravanan\",\"transaction_type_name\":\"Project Cash Transfer to Site\",\"description\":\"Project Cash transferred to Site Manager (Lake View Luxury Villa) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Acceptance Pending]\",\"wage_cash_source\":\"office_cash\",\"created_by\":3,\"id\":3}',	'{\"model\":\"Ledger\",\"id\":3}',	'sha256:7e745b7b47ea7c6229239923899eb65d9e9130d7898c63ebf998939879360e91',	'2026-11-20 17:02:29',	'2026-11-20 17:02:29'),
(55,	'AUD-20261120-ZNPE',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGOUT',	'info',	'User Sivasakthi S (Account Manager) logged out from session',	'பயனாளர் Sivasakthi S அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\"}',	'sha256:6f08f9e6150ae2642d5530845b28865602b535994672b1b804a63f9b6549094f',	'2026-11-20 17:02:36',	'2026-11-20 17:02:36'),
(56,	'AUD-20261120-YKF8',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:43ff257270dc80fc36ea9b2b4939e436494be1f840a1ab23bc385898af0d1a78',	'2026-11-20 17:02:51',	'2026-11-20 17:02:51'),
(57,	'AUD-20261120-NH1I',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-002',	'UPDATE',	'warning',	'Updated User (#USER-002) [fields: password]',	'User (#USER-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:d501b07e08285d18924eb2fa43016f8dc1e3df558e225a009ea76cf696c48c12',	'2026-11-20 17:03:07',	'2026-11-20 17:03:07'),
(58,	'AUD-20261120-GFSI',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:975292859a3cfee2c288f2304908cfa7da4729f6594eff84c20075d4916c5e42',	'2026-11-20 17:03:10',	'2026-11-20 17:03:10'),
(59,	'AUD-20261120-SP66',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:56fda950793cefa18930e8a4089ef184f3c00491a301fce80361ee8c455a27ea',	'2026-11-20 17:03:12',	'2026-11-20 17:03:12'),
(60,	'AUD-20261120-TT7V',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:9043c73498b1e24e39a6336a702b13b3c99d7c4295afcc5f92d73c03e0f5c8db',	'2026-11-20 17:03:13',	'2026-11-20 17:03:13'),
(61,	'AUD-20261120-J9TC',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGIN',	'info',	'User Murugan SM (Site Manager) logged in to the system',	'பயனாளர் Murugan SM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:53402edcf1f435815dc03bfd85ec4eb040d63253370f168ed1a977c2687d0cad',	'2026-11-20 17:03:17',	'2026-11-20 17:03:17'),
(62,	'AUD-20261120-HNUL',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'ledger',	'App\\Models\\Ledger',	'#LEDG-003',	'UPDATE',	'warning',	'Updated Ledger (#LEDG-003) [fields: description]',	'Ledger (#LEDG-003) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: description]',	'Lake View Luxury Villa',	12000.00,	'{\"description\":\"Project Cash transferred to Site Manager (Lake View Luxury Villa) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Acceptance Pending]\"}',	'{\"description\":\"Project Cash transferred to Site Manager (Lake View Luxury Villa) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Accepted]\"}',	'{\"model\":\"Ledger\",\"changed_fields\":[\"description\"]}',	'sha256:4543dd9f67ad013bbb5fb2c3fead171b085f8a348b2a2c0ac3ba681f427c71b2',	'2026-11-20 17:07:33',	'2026-11-20 17:07:33'),
(63,	'AUD-20261120-FTGB',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'site_cash',	'App\\Models\\SiteCash',	'#BILL-XFER-1',	'CASH_TXN',	'info',	'Recorded site cash transaction #BILL-XFER-1 (₹12,000.00)',	'தள ரொக்க பரிவர்த்தனை #BILL-XFER-1 பதிவு செய்யப்பட்டது (₹12,000.00)',	'Lake View Luxury Villa',	12000.00,	NULL,	'{\"project_site_id\":1,\"cash_transfer_id\":1,\"transaction_type\":\"Project Cash Received\",\"flow_type\":\"inflow\",\"amount\":12000,\"payment_mode\":\"Cash\",\"payment_reference\":null,\"bank_name\":null,\"paid_to\":\"Site Manager\",\"paid_by\":\"Accountant (Raja AM)\",\"receiver_name\":\"Site Manager\",\"is_expense\":false,\"in_amount\":12000,\"out_amount\":0,\"bill_no\":\"XFER-1\",\"payment_date\":\"2026-11-20\",\"payment_time\":\"10:02 AM\",\"description\":\"Project Cash received from Account Manager - Given by: Accountant (Raja AM). Purpose: Inter-entity cash transfer\",\"wage_cash_source\":\"project_cash\",\"created_by\":2,\"id\":1}',	'{\"model\":\"SiteCash\",\"id\":1}',	'sha256:c719e7bcc7c50e87be9c6a5db102b80a9b223349d6d8f3bedffb6b982890a15c',	'2026-11-20 17:07:33',	'2026-11-20 17:07:33'),
(64,	'AUD-20261120-ZUCN',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGOUT',	'info',	'User Murugan SM (Site Manager) logged out from session',	'பயனாளர் Murugan SM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\"}',	'sha256:544b34153922c5e630a23406a510dfb3118d7864a853305766cabc9e8fcc3612',	'2026-11-20 17:07:40',	'2026-11-20 17:07:40'),
(65,	'AUD-20261120-PZVZ',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGIN',	'info',	'User Sivasakthi S (Account Manager) logged in to the system',	'பயனாளர் Sivasakthi S கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:6609618291eec03f76a30ef4747eada01e0aa35e7b0843a2e830c8aa8d24863b',	'2026-11-20 17:07:49',	'2026-11-20 17:07:49'),
(66,	'AUD-20261120-BFFS',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGOUT',	'info',	'User Sivasakthi S (Account Manager) logged out from session',	'பயனாளர் Sivasakthi S அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\"}',	'sha256:c4b21176b04faf8879802341d3144ac18318dc6ad7228b3622841167ff4d1bff',	'2026-11-20 17:08:12',	'2026-11-20 17:08:12'),
(67,	'AUD-20261120-UYBC',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:aadf097793c14dee415809121ae8fe22696204f64394ad7b8a96cf4ee3a88daa',	'2026-11-20 17:08:14',	'2026-11-20 17:08:14'),
(68,	'AUD-20261120-4VJ6',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	NULL,	'#ID-3',	'CREATE',	'info',	'Created / Submitted Roles (#ID-3)',	'Roles படிவ செயல்முறை (#ID-3) பதிவு செய்யப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"name\":\"Account Manager\",\"description\":\"Financial operations, daily ledgers, banks, property rental masters, and supplier ledgers.\",\"menus\":[\"dashboard\",\"suppliers\",\"banks\",\"branches\",\"properties\",\"property_types\",\"transaction_types\",\"material_usage\",\"stock_transfer\",\"ledger\",\"cash_transfer\",\"cash_transfer_settle\",\"reports_stock_transfer\",\"reports_ledger\",\"profile\"]}',	'{\"endpoint\":\"roles\\/3\\/update\",\"method\":\"POST\",\"ip\":\"103.16.202.228\"}',	'sha256:175faf2c3ac6cc7fe926761d4618a9210d46320ca57314bdcd71691a261baa51',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(69,	'AUD-20261120-WXH0',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:200f7b06016e7ca182d7d7f00ad4a4f7cfb6cf0a58d10f1d59f52cf967180821',	'2026-11-20 17:15:14',	'2026-11-20 17:15:14'),
(70,	'AUD-20261120-HIRO',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGIN',	'info',	'User Sivasakthi S (Account Manager) logged in to the system',	'பயனாளர் Sivasakthi S கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:6d7ccee9186f22267de6a5429364abd74295c010a3647ef346367e3ab71e4df9',	'2026-11-20 17:15:19',	'2026-11-20 17:15:19'),
(71,	'AUD-20261120-BYKQ',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'ledger',	'App\\Models\\Ledger',	'#LEDG-004',	'CREATE',	'info',	'Posted ledger journal entry #LEDG-004 (₹250,000.00)',	'பேரேட்டில் புதிய கணக்கு பதிவு #LEDG-004 செய்யப்பட்டது (₹250,000.00)',	'Parthasarathi Sir Site',	250000.00,	NULL,	'{\"entry_date\":\"2026-11-20\",\"flow\":\"expense\",\"source\":\"Construction\",\"amount\":250000,\"project_site_id\":2,\"cash_transfer_id\":2,\"site_name\":\"Parthasarathi Sir Site\",\"client_name\":\"Parthasarathi\",\"transaction_type_name\":\"Project Cash Transfer to Site\",\"description\":\"Project Cash transferred to Site Manager (Parthasarathi Sir Site) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Acceptance Pending]\",\"wage_cash_source\":\"office_cash\",\"created_by\":9,\"id\":4}',	'{\"model\":\"Ledger\",\"id\":4}',	'sha256:e66ee3d75d0777ab1b09662464ae9f9f75aea0a84356dc19f1f9352b7d39eb21',	'2026-11-20 17:15:51',	'2026-11-20 17:15:51'),
(72,	'AUD-20261120-TGUW',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:c1b9ad46a910ebc8847a6469e3766dbbff42ea5d961a3ac619fba6cdc971cd41',	'2026-11-20 17:16:07',	'2026-11-20 17:16:07'),
(73,	'AUD-20261120-KJAI',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-003',	'UPDATE',	'warning',	'Updated User (#USER-003) [fields: password]',	'User (#USER-003) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:102b0b243bc6e47024800d3118f6cc3e27c9a615e24257f6c0392fc82d018d5b',	'2026-11-20 17:16:35',	'2026-11-20 17:16:35'),
(74,	'AUD-20261120-AHMT',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:745478e0904ebb60cd5886b6eaaa58ae8f133f81f6e6ed19023c3b1cd943d604',	'2026-11-20 17:17:16',	'2026-11-20 17:17:16'),
(75,	'AUD-20261120-I7PK',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'ledger',	'App\\Models\\Ledger',	'#LEDG-004',	'UPDATE',	'warning',	'Updated Ledger (#LEDG-004) [fields: description]',	'Ledger (#LEDG-004) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: description]',	'Parthasarathi Sir Site',	250000.00,	'{\"description\":\"Project Cash transferred to Site Manager (Parthasarathi Sir Site) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Acceptance Pending]\"}',	'{\"description\":\"Project Cash transferred to Site Manager (Parthasarathi Sir Site) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Accepted]\"}',	'{\"model\":\"Ledger\",\"changed_fields\":[\"description\"]}',	'sha256:e459ebbad6f695e4fe0d2ba3a2fa95f8ab8e46bee7971f9f8bfe529f5007adce',	'2026-11-20 17:17:53',	'2026-11-20 17:17:53'),
(76,	'AUD-20261120-7Z9Y',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'site_cash',	'App\\Models\\SiteCash',	'#BILL-XFER-2',	'CASH_TXN',	'info',	'Recorded site cash transaction #BILL-XFER-2 (₹250,000.00)',	'தள ரொக்க பரிவர்த்தனை #BILL-XFER-2 பதிவு செய்யப்பட்டது (₹250,000.00)',	'Parthasarathi Sir Site',	250000.00,	NULL,	'{\"project_site_id\":2,\"cash_transfer_id\":2,\"transaction_type\":\"Project Cash Received\",\"flow_type\":\"inflow\",\"amount\":250000,\"payment_mode\":\"Cash\",\"payment_reference\":null,\"bank_name\":null,\"paid_to\":\"Site Manager\",\"paid_by\":\"Accountant (Raja AM)\",\"receiver_name\":\"Site Manager\",\"is_expense\":false,\"in_amount\":250000,\"out_amount\":0,\"bill_no\":\"XFER-2\",\"payment_date\":\"2026-11-20\",\"payment_time\":\"10:15 AM\",\"description\":\"Project Cash received from Account Manager - Given by: Accountant (Raja AM). Purpose: Inter-entity cash transfer\",\"wage_cash_source\":\"project_cash\",\"created_by\":8,\"id\":2}',	'{\"model\":\"SiteCash\",\"id\":2}',	'sha256:e54ba1d033edac61a43dedbe51342cb08ee88b7b4fa2b37e24183ab8009cdac2',	'2026-11-20 17:17:53',	'2026-11-20 17:17:53'),
(77,	'AUD-20261120-AJFI',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:f2491cfee5367d199d3fff8ccd6ad602b6b927585053433bfad85641c8d5f884',	'2026-11-20 17:26:26',	'2026-11-20 17:26:26'),
(78,	'AUD-20261120-DM5O',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:d17a59b9085a4080617f979759f4558b88893552684b0f601ef3f86c89c3ba75',	'2026-11-20 17:26:38',	'2026-11-20 17:26:38'),
(79,	'AUD-20261120-SHFE',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-002',	'UPDATE',	'warning',	'Updated User (#USER-002) [fields: password]',	'User (#USER-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:8155a8d2f8dc6d5fb548264e13487b5f2dca6d918b7a4e8a6005d7e82664c661',	'2026-11-20 17:43:47',	'2026-11-20 17:43:47'),
(80,	'AUD-20261120-Z8PW',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-003',	'UPDATE',	'warning',	'Updated User (#USER-003) [fields: password]',	'User (#USER-003) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:34fd33f23af634349fff4c1014e14198f2b2f5a001646db3c4a5e089e05dfc72',	'2026-11-20 17:43:54',	'2026-11-20 17:43:54'),
(81,	'AUD-20261120-O6GD',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-008',	'UPDATE',	'warning',	'Updated User (#USER-008) [fields: password]',	'User (#USER-008) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:e468a10723ab2bb4976085de7ea9a695b07c64e08f64e56b62fce9f9c1b1c9b0',	'2026-11-20 17:44:03',	'2026-11-20 17:44:03'),
(82,	'AUD-20261120-HZBY',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-009',	'UPDATE',	'warning',	'Updated User (#USER-009) [fields: password]',	'User (#USER-009) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:61d62987857c35b854fece1c57765f4483995a40cfa47ecad2ffa33ebf884c68',	'2026-11-20 17:44:10',	'2026-11-20 17:44:10'),
(83,	'AUD-20261120-XIOA',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:b6a1c57ad05b4e369b08ba70ff25348a2db13e7b70ed5983d9f3b1dde5915362',	'2026-11-20 17:44:14',	'2026-11-20 17:44:14'),
(84,	'AUD-20261120-4YVJ',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:fa130349995ae90a873f7ca542ebc90ca7dd4e19076ff9528511501b2bae6bdb',	'2026-11-20 17:44:27',	'2026-11-20 17:44:27'),
(85,	'AUD-20261120-XDSS',	9,	'Sivasakthi S',	'Account Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-009',	'LOGOUT',	'info',	'User Sivasakthi S (Account Manager) logged out from session',	'பயனாளர் Sivasakthi S அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sivasakthi.saitech@gmail.com\",\"role\":\"Account Manager\"}',	'sha256:833e56d84544924997d3885db7b8c2aeb5381ef5605c06d02310af4be122ea17',	'2026-11-20 17:50:52',	'2026-11-20 17:50:52'),
(86,	'AUD-20261120-DQNR',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGIN',	'info',	'User K. Deiveni (Owner) logged in to the system',	'பயனாளர் K. Deiveni கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:f14b6ecbea6eb5f8cefc7dd829ee87bc1e112925a2522092626574da4e76c68a',	'2026-11-20 17:51:11',	'2026-11-20 17:51:11'),
(87,	'AUD-20261120-NE0H',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'users',	'App\\Models\\User',	'#USER-002',	'UPDATE',	'warning',	'Updated User (#USER-002) [fields: password]',	'User (#USER-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: password]',	'Head Office',	NULL,	'{\"password\":\"[HIDDEN]\"}',	'{\"password\":\"[UPDATED]\"}',	'{\"model\":\"User\",\"changed_fields\":[\"password\"]}',	'sha256:12dd4fe810e3b99bd03266ba3ee51504a7e755453e43a39343e95b29a6de1801',	'2026-11-20 17:51:34',	'2026-11-20 17:51:34'),
(88,	'AUD-20261120-FAQG',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:cf99c4f2e7b2dbbbbf41113f77419117b343ac5abeff5be2ae17bc6062395c4b',	'2026-11-20 17:51:43',	'2026-11-20 17:51:43'),
(89,	'AUD-20261120-ZWDN',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGIN',	'info',	'User Murugan SM (Site Manager) logged in to the system',	'பயனாளர் Murugan SM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:62e17e5ddf2bf85ce0303b5ff20777c9f7ff0f8ebabcad971a5e9b299966a37d',	'2026-11-20 17:51:49',	'2026-11-20 17:51:49'),
(90,	'AUD-20261120-AUDZ',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-001',	'CREATE',	'info',	'Generated weekly wage settlement #WEEK-001',	'வாராந்திர கூலி வழங்கல் பட்டியல் #WEEK-001 உருவாக்கப்பட்டது',	'Lake View Luxury Villa',	NULL,	NULL,	'{\"project_site_id\":1,\"week_start_date\":\"2026-11-16\",\"week_end_date\":\"2026-11-22\",\"total_weekly_expenses\":0,\"created_by\":2,\"id\":1}',	'{\"model\":\"WeeklyWage\",\"id\":1}',	'sha256:3ea21c41fa936c9c90ae904cd1b522d33a076cc83419cca0307640b4188be63c',	'2026-11-20 17:52:07',	'2026-11-20 17:52:07'),
(91,	'AUD-20261120-JOOF',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-001',	'UPDATE',	'warning',	'Updated WeeklyWage (#WEEK-001) [fields: total_labour_man_days, total_labour_wages, total_daily_expenses, net_payout]',	'WeeklyWage (#WEEK-001) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: total_labour_man_days, total_labour_wages, total_daily_expenses, net_payout]',	'Lake View Luxury Villa',	NULL,	'{\"total_labour_man_days\":null,\"total_labour_wages\":null,\"total_daily_expenses\":null,\"net_payout\":null}',	'{\"total_labour_man_days\":0,\"total_labour_wages\":0,\"total_daily_expenses\":0,\"net_payout\":0}',	'{\"model\":\"WeeklyWage\",\"changed_fields\":[\"total_labour_man_days\",\"total_labour_wages\",\"total_daily_expenses\",\"net_payout\"]}',	'sha256:12245808d89b0b4298e3ef924011cd83079266100c81440d8103fc56ddad9640',	'2026-11-20 17:52:07',	'2026-11-20 17:52:07'),
(92,	'AUD-20261120-IMNL',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:2acb0068d7db8f78d6340f3e84b6aca2652bc027b983f85f541c7f78112e81be',	'2026-11-20 17:54:09',	'2026-11-20 17:54:09'),
(93,	'AUD-20261120-H3YF',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:a0dc9a34977ac3273ca81af226e084c089625a65d4179f43cb0f6f799f85d1ca',	'2026-11-20 17:54:26',	'2026-11-20 17:54:26'),
(94,	'AUD-20261120-AKIQ',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'purchases',	'App\\Models\\PurchaseOrder',	'#PO-PO-2026-001',	'CREATE',	'info',	'Created purchase order #PO-PO-2026-001 (₹41,000.00)',	'புதிய கொள்முதல் ஆணை #PO-PO-2026-001 உருவாக்கப்பட்டது (₹41,000.00)',	'Lake View Luxury Villa',	41000.00,	NULL,	'{\"po_number\":\"PO-2026-001\",\"order_date\":\"2026-11-20\",\"project_site_id\":1,\"supplier_id\":2,\"supplier_name\":\"Chettinad Cement Agency\",\"source_type\":\"Third-Party Supplier\",\"status\":\"Ordered\",\"status_reason\":null,\"delivery_status\":\"Inward Pending\",\"delivery_date\":null,\"subtotal\":41000,\"tax_amount\":0,\"total_amount\":41000,\"notes\":null,\"receipt_image\":null,\"created_by\":2,\"id\":1}',	'{\"model\":\"PurchaseOrder\",\"id\":1}',	'sha256:c33a78932d7c099f70420346399f95bf435b61b251ef7d1396cb596240949c80',	'2026-11-20 17:55:00',	'2026-11-20 17:55:00'),
(95,	'AUD-20261120-CFYS',	1,	'K. Deiveni',	'owner',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-001',	'LOGOUT',	'info',	'User K. Deiveni (Owner) logged out from session',	'பயனாளர் K. Deiveni அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"owner@deiveni.com\",\"role\":\"owner\"}',	'sha256:7db6d4256f9aa70dd79a81f3f93647763b97223a9d52b18cdecf80eb3bf0b318',	'2026-11-20 17:55:37',	'2026-11-20 17:55:37'),
(96,	'AUD-20261120-YBQR',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:c8c8b2f048e2e67deb7cbbc8eaae2421f30f9d7c177bef6deb60fd0b6b9e7af6',	'2026-11-20 17:55:45',	'2026-11-20 17:55:45'),
(97,	'AUD-20261120-OHWS',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'purchases',	'App\\Models\\PurchaseOrder',	'#PO-PO-2026-002',	'CREATE',	'info',	'Created purchase order #PO-PO-2026-002 (₹15,000.00)',	'புதிய கொள்முதல் ஆணை #PO-PO-2026-002 உருவாக்கப்பட்டது (₹15,000.00)',	'Lake View Luxury Villa',	15000.00,	NULL,	'{\"po_number\":\"PO-2026-002\",\"order_date\":\"2026-11-20\",\"project_site_id\":1,\"supplier_id\":3,\"supplier_name\":\"Rani Paints\",\"source_type\":\"Third-Party Supplier\",\"status\":\"Ordered\",\"status_reason\":null,\"delivery_status\":\"Inward Pending\",\"delivery_date\":null,\"subtotal\":15000,\"tax_amount\":0,\"total_amount\":15000,\"notes\":null,\"receipt_image\":null,\"created_by\":2,\"id\":2}',	'{\"model\":\"PurchaseOrder\",\"id\":2}',	'sha256:14841559c393729a098df8e9fd13f13ee3c5ac55c6d92aee887be3074d749213',	'2026-11-20 17:55:54',	'2026-11-20 17:55:54'),
(98,	'AUD-20261120-QA3L',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'purchases',	'App\\Models\\PurchaseOrder',	'#PO-PO-2026-003',	'CREATE',	'info',	'Created purchase order #PO-PO-2026-003 (₹127,500.00)',	'புதிய கொள்முதல் ஆணை #PO-PO-2026-003 உருவாக்கப்பட்டது (₹127,500.00)',	'Parthasarathi Sir Site',	127500.00,	NULL,	'{\"po_number\":\"PO-2026-003\",\"order_date\":\"2026-11-20\",\"project_site_id\":2,\"supplier_id\":1,\"supplier_name\":\"Sri Balaji Blue Metals & M-Sand\",\"source_type\":\"Third-Party Supplier\",\"status\":\"Ordered\",\"status_reason\":null,\"delivery_status\":\"Inward Pending\",\"delivery_date\":null,\"subtotal\":127500,\"tax_amount\":0,\"total_amount\":127500,\"notes\":null,\"receipt_image\":null,\"created_by\":8,\"id\":3}',	'{\"model\":\"PurchaseOrder\",\"id\":3}',	'sha256:aa68adc7a0ebf3fa1e74a2d796f47fbcd61e3e104b47f5dc85acd08921339536',	'2026-11-20 17:57:26',	'2026-11-20 17:57:26'),
(99,	'AUD-20261120-WZ4N',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'general',	NULL,	'#MATERIAL-USAGES',	'CREATE',	'info',	'Created / Submitted Material Usages (#MATERIAL-USAGES)',	'Material Usages படிவ செயல்முறை (#MATERIAL-USAGES) பதிவு செய்யப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"usage_date\":\"2026-11-20\",\"project_site_id\":\"2\",\"material_source\":\"third_party\",\"items\":[{\"material_id\":\"2\",\"material_name\":\"M-Sand (Manufactured Sand)\",\"purchase_order_id\":\"3\",\"purchase_order_item_id\":\"3\",\"po_number\":\"PO-2026-003\",\"source_reference\":\"PO-2026-003\",\"source_type\":\"Third-Party Supplier\",\"inward_quantity\":\"15\",\"unit\":\"Load (Truck Load)\",\"unit_id\":\"4\",\"unit_price\":\"8500\",\"used_quantity\":\"5\"}],\"remarks\":null,\"purchase_order_ids\":[\"3\"],\"purchase_order_id\":\"3\"}',	'{\"endpoint\":\"material-usages\",\"method\":\"POST\",\"ip\":\"103.16.202.228\"}',	'sha256:66f4329d003006922cf05dfa22173a0cfd8e38595d4a20b2b2bde2dce8bb90de',	'2026-11-20 17:58:04',	'2026-11-20 17:58:04'),
(100,	'AUD-20261120-IE1J',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:078e71568756846fa345d3abf94875f2cf68f796e7da1c9f523c599e5cd9b5ac',	'2026-11-20 17:59:07',	'2026-11-20 17:59:07'),
(101,	'AUD-20261120-K6GF',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGIN',	'info',	'User Murugan SM (Site Manager) logged in to the system',	'பயனாளர் Murugan SM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:6582f3badedc90e5828ffe8beda5a40dafea6ec32cc6dcd96c5bb6a2c8e7789d',	'2026-11-20 17:59:29',	'2026-11-20 17:59:29'),
(102,	'AUD-20261120-6AYS',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-002',	'LOGOUT',	'info',	'User Murugan SM (Site Manager) logged out from session',	'பயனாளர் Murugan SM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"sm@deiveni.com\",\"role\":\"site_manager\"}',	'sha256:9de33c9663d960fe170958aa42dbd3a96b3c7715bef29ca5224df8131411519c',	'2026-11-20 18:01:02',	'2026-11-20 18:01:02'),
(103,	'AUD-20261120-HO2N',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:78ec8a29a7f58f6f7a9990850ae1f72a341e7647cbeb685e5d500d6add914870',	'2026-11-20 18:01:12',	'2026-11-20 18:01:12'),
(104,	'AUD-20261120-R93C',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'general',	NULL,	'#MATERIAL-USAGES',	'CREATE',	'info',	'Created / Submitted Material Usages (#MATERIAL-USAGES)',	'Material Usages படிவ செயல்முறை (#MATERIAL-USAGES) பதிவு செய்யப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"usage_date\":\"2026-11-20\",\"project_site_id\":\"1\",\"material_source\":\"third_party\",\"items\":[{\"material_id\":\"17\",\"material_name\":\"Paint\",\"purchase_order_id\":\"2\",\"purchase_order_item_id\":\"2\",\"po_number\":\"PO-2026-002\",\"source_reference\":\"PO-2026-002\",\"source_type\":\"Third-Party Supplier\",\"inward_quantity\":\"15\",\"unit\":\"Litres\",\"unit_id\":\"3\",\"unit_price\":\"1000\",\"used_quantity\":\"5\"},{\"material_id\":\"1\",\"material_name\":\"Cement\",\"purchase_order_id\":\"1\",\"purchase_order_item_id\":\"1\",\"po_number\":\"PO-2026-001\",\"source_reference\":\"PO-2026-001\",\"source_type\":\"Third-Party Supplier\",\"inward_quantity\":\"100\",\"unit\":\"Bags\",\"unit_id\":\"1\",\"unit_price\":\"410\",\"used_quantity\":\"62\"}],\"remarks\":null,\"purchase_order_ids\":[\"2\",\"1\"],\"purchase_order_id\":\"2\"}',	'{\"endpoint\":\"material-usages\",\"method\":\"POST\",\"ip\":\"103.16.202.228\"}',	'sha256:72954fb0e3fb77bbd18acdb38312a8cd4692e5588e6e81289cf133c80e0da9d0',	'2026-11-20 18:01:24',	'2026-11-20 18:01:24'),
(105,	'AUD-20261120-JMCN',	2,	'Murugan SM',	'site_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'general',	NULL,	'#STOCK-TRANSFERS',	'CREATE',	'info',	'Created / Submitted Stock Transfers (#STOCK-TRANSFERS)',	'Stock Transfers படிவ செயல்முறை (#STOCK-TRANSFERS) பதிவு செய்யப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"transfer_no\":\"ST-2026-0001\",\"transfer_date\":\"2026-11-20\",\"from_project_site_id\":\"1\",\"to_project_site_id\":\"2\",\"return_expected\":\"1\",\"vehicle_no\":null,\"driver_name\":null,\"remarks\":null,\"items\":[{\"material_id\":\"17\",\"material_name\":\"Paint\",\"source_type\":\"PO\",\"source_reference\":\"PO-2026-002\",\"supplier_name\":\"Rani Paints\",\"quantity\":\"2\",\"unit\":\"Litres\",\"unit_id\":\"3\",\"unit_price\":\"1000\",\"total_price\":\"2000\"},{\"material_id\":\"1\",\"material_name\":\"Cement\",\"source_type\":\"PO\",\"source_reference\":\"PO-2026-001\",\"supplier_name\":\"Chettinad Cement Agency\",\"quantity\":\"30\",\"unit\":\"Bags\",\"unit_id\":\"1\",\"unit_price\":\"410\",\"total_price\":\"12300\"}]}',	'{\"endpoint\":\"stock-transfers\",\"method\":\"POST\",\"ip\":\"103.16.202.228\"}',	'sha256:3d3959f7ad48e458af9412cde8c689851df837a1439a4cd7152a0154b907ed5f',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(106,	'AUD-20261120-YQNQ',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'general',	NULL,	'#STOCK-TRANSFERS',	'CREATE',	'info',	'Created / Submitted Stock Transfers (#STOCK-TRANSFERS)',	'Stock Transfers படிவ செயல்முறை (#STOCK-TRANSFERS) பதிவு செய்யப்பட்டது',	'Head Office',	NULL,	NULL,	'{\"transfer_no\":\"ST-2026-0001\",\"transfer_date\":\"2026-11-20\",\"from_project_site_id\":\"2\",\"to_project_site_id\":\"1\",\"return_expected\":\"1\",\"vehicle_no\":null,\"driver_name\":\"Murugan\",\"remarks\":null,\"items\":[{\"material_id\":\"2\",\"material_name\":\"M-Sand (Manufactured Sand)\",\"source_type\":\"PO\",\"source_reference\":\"PO-2026-003\",\"supplier_name\":\"Sri Balaji Blue Metals & M-Sand\",\"quantity\":\"5\",\"unit\":\"Load (Truck Load)\",\"unit_id\":\"4\",\"unit_price\":\"8500\",\"total_price\":\"42500\"}]}',	'{\"endpoint\":\"stock-transfers\",\"method\":\"POST\",\"ip\":\"103.16.202.228\"}',	'sha256:faf24c465ddde5ba0cb9fcbb3925ce3b83f742ccc1b7e299b9b425adbbc68a61',	'2026-11-20 18:01:55',	'2026-11-20 18:01:55'),
(107,	'AUD-20261120-RTIT',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:8fb8f7149c75d1664f73e589ed4c99d7052d2b62a8bb185ab93042a255eb3477',	'2026-11-20 18:04:34',	'2026-11-20 18:04:34'),
(108,	'AUD-20261120-KMZM',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGIN',	'info',	'User Raja AM (Account Manager) logged in to the system',	'பயனாளர் Raja AM கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:ff0b9a002937f58aef4705e8b09d55c204fc9b3e11b0b1025795ae04cc1e9bfe',	'2026-11-20 18:16:53',	'2026-11-20 18:16:53'),
(109,	'AUD-20261120-WGVV',	3,	'Raja AM',	'account_manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-003',	'LOGOUT',	'info',	'User Raja AM (Account Manager) logged out from session',	'பயனாளர் Raja AM அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"am@deiveni.com\",\"role\":\"account_manager\"}',	'sha256:96ad20665374d417b7c751c5b07595f5dc9a05ccc0847f7ed929855bcec4a623',	'2026-11-20 18:19:44',	'2026-11-20 18:19:44'),
(110,	'AUD-20261120-P6HA',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:e57f55123d0b4665d8c5d81e1b590bba49abafb736ea28f3ed354488a97e6831',	'2026-11-20 18:19:58',	'2026-11-20 18:19:58'),
(111,	'AUD-20261120-XIRY',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:39a0be1546a70648a45e2f5afbd7368df1a141831abf6cd027593a48c43b06ee',	'2026-11-20 18:21:34',	'2026-11-20 18:21:34'),
(112,	'AUD-20261120-JIZC',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGIN',	'info',	'User syed (Site Manager) logged in to the system',	'பயனாளர் syed கணினியில் வெற்றிகரமாக உள்நுழைந்தார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\",\"ip\":\"103.16.202.228\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/157.0.0.0 Safari\\/537.36\"}',	'sha256:8a9dc545b5f9b83b1b618f4dc4c45675381c196c64f53591e3a0f89a7df045bb',	'2026-11-20 18:21:51',	'2026-11-20 18:21:51'),
(113,	'AUD-20261120-HZFU',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\DailyAllocation',	'#DAIL-001',	'CREATE',	'info',	'Created daily worker allocation sheet #DAIL-001',	'தினசரி தொழிலாளர் வேலை ஒதுக்கீடு தாள் #DAIL-001 உருவாக்கப்பட்டது',	'Parthasarathi Sir Site',	NULL,	NULL,	'{\"project_site_id\":\"2\",\"date\":\"2026-11-16\",\"total_labour_count\":7,\"total_labour_wage\":6800,\"total_expenses\":500,\"grand_total\":7300,\"trade_summary\":\"2 \\u0baa\\u0bc6\\u0ba3\\u0bcd \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd + 3 \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd (\\u0b86\\u0ba3\\u0bcd) + 2 \\u0b95\\u0bca\\u0ba4\\u0bcd\\u0ba4\\u0ba9\\u0bbe\\u0bb0\\u0bcd\",\"created_by\":8,\"id\":1}',	'{\"model\":\"DailyAllocation\",\"id\":1}',	'sha256:8d47009a6d3d3f5d627a94940e00bab4e6f5187c9d35c09d298c5be9e627576d',	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(114,	'AUD-20261120-3GWS',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-002',	'CREATE',	'info',	'Generated weekly wage settlement #WEEK-002',	'வாராந்திர கூலி வழங்கல் பட்டியல் #WEEK-002 உருவாக்கப்பட்டது',	'Parthasarathi Sir Site',	NULL,	NULL,	'{\"project_site_id\":2,\"week_start_date\":\"2026-11-16\",\"week_end_date\":\"2026-11-22\",\"total_weekly_expenses\":0,\"created_by\":8,\"id\":2}',	'{\"model\":\"WeeklyWage\",\"id\":2}',	'sha256:c8bc03f4eb8467933d10b450a9093b314b4ce7c3f6d646a787aca08f1d78ff05',	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(115,	'AUD-20261120-ZT3N',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-002',	'UPDATE',	'warning',	'Updated WeeklyWage (#WEEK-002) [fields: total_labour_man_days, total_labour_wages, total_daily_expenses, net_payout]',	'WeeklyWage (#WEEK-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: total_labour_man_days, total_labour_wages, total_daily_expenses, net_payout]',	'Parthasarathi Sir Site',	NULL,	'{\"total_labour_man_days\":null,\"total_labour_wages\":null,\"total_daily_expenses\":null,\"net_payout\":null}',	'{\"total_labour_man_days\":7,\"total_labour_wages\":6800,\"total_daily_expenses\":500,\"net_payout\":7300}',	'{\"model\":\"WeeklyWage\",\"changed_fields\":[\"total_labour_man_days\",\"total_labour_wages\",\"total_daily_expenses\",\"net_payout\"]}',	'sha256:507b04445c30f8a61d667f512d56ff6896c6351b856e2e55b710eb419665b5b3',	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(116,	'AUD-20261120-OAJO',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\DailyAllocation',	'#DAIL-002',	'CREATE',	'info',	'Created daily worker allocation sheet #DAIL-002',	'தினசரி தொழிலாளர் வேலை ஒதுக்கீடு தாள் #DAIL-002 உருவாக்கப்பட்டது',	'Parthasarathi Sir Site',	NULL,	NULL,	'{\"project_site_id\":\"2\",\"date\":\"2026-11-17\",\"total_labour_count\":5,\"total_labour_wage\":4400,\"total_expenses\":0,\"grand_total\":4400,\"trade_summary\":\"2 \\u0baa\\u0bc6\\u0ba3\\u0bcd \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd + 3 \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd (\\u0b86\\u0ba3\\u0bcd)\",\"created_by\":8,\"id\":2}',	'{\"model\":\"DailyAllocation\",\"id\":2}',	'sha256:d4a4b2787bbf6988137ef68219dbcaea41bc21dfec34d4f01396774cd5478953',	'2026-11-20 18:24:52',	'2026-11-20 18:24:52'),
(117,	'AUD-20261120-IRSX',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-002',	'UPDATE',	'warning',	'Updated WeeklyWage (#WEEK-002) [fields: total_labour_man_days, total_labour_wages, net_payout]',	'WeeklyWage (#WEEK-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: total_labour_man_days, total_labour_wages, net_payout]',	'Parthasarathi Sir Site',	NULL,	'{\"total_labour_man_days\":7,\"total_labour_wages\":\"6800.00\",\"net_payout\":\"7300.00\"}',	'{\"total_labour_man_days\":12,\"total_labour_wages\":11200,\"net_payout\":11700}',	'{\"model\":\"WeeklyWage\",\"changed_fields\":[\"total_labour_man_days\",\"total_labour_wages\",\"net_payout\"]}',	'sha256:1fe8474b5e3fa9262ec7bf610a2471d4a1df4e595a00029e0f774215a96cf8cc',	'2026-11-20 18:24:52',	'2026-11-20 18:24:52'),
(118,	'AUD-20261120-NQFQ',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\DailyAllocation',	'#DAIL-003',	'CREATE',	'info',	'Created daily worker allocation sheet #DAIL-003',	'தினசரி தொழிலாளர் வேலை ஒதுக்கீடு தாள் #DAIL-003 உருவாக்கப்பட்டது',	'Parthasarathi Sir Site',	NULL,	NULL,	'{\"project_site_id\":\"2\",\"date\":\"2026-11-18\",\"total_labour_count\":5,\"total_labour_wage\":4400,\"total_expenses\":0,\"grand_total\":4400,\"trade_summary\":\"2 \\u0baa\\u0bc6\\u0ba3\\u0bcd \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd + 3 \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd (\\u0b86\\u0ba3\\u0bcd)\",\"created_by\":8,\"id\":3}',	'{\"model\":\"DailyAllocation\",\"id\":3}',	'sha256:172abfe5ecb2dacdd80e587d5fc6062dd9ef2d96cf36ec138ae168a9dc367d5f',	'2026-11-20 18:25:08',	'2026-11-20 18:25:08'),
(119,	'AUD-20261120-5RI6',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-002',	'UPDATE',	'warning',	'Updated WeeklyWage (#WEEK-002) [fields: total_labour_man_days, total_labour_wages, net_payout]',	'WeeklyWage (#WEEK-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: total_labour_man_days, total_labour_wages, net_payout]',	'Parthasarathi Sir Site',	NULL,	'{\"total_labour_man_days\":12,\"total_labour_wages\":\"11200.00\",\"net_payout\":\"11700.00\"}',	'{\"total_labour_man_days\":17,\"total_labour_wages\":15600,\"net_payout\":16100}',	'{\"model\":\"WeeklyWage\",\"changed_fields\":[\"total_labour_man_days\",\"total_labour_wages\",\"net_payout\"]}',	'sha256:b934f2218928edb8874a273cd9f78010801fdd42c966829500c3c3a2c33cec82',	'2026-11-20 18:25:08',	'2026-11-20 18:25:08'),
(120,	'AUD-20261120-RHCG',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\DailyAllocation',	'#DAIL-004',	'CREATE',	'info',	'Created daily worker allocation sheet #DAIL-004',	'தினசரி தொழிலாளர் வேலை ஒதுக்கீடு தாள் #DAIL-004 உருவாக்கப்பட்டது',	'Parthasarathi Sir Site',	NULL,	NULL,	'{\"project_site_id\":\"2\",\"date\":\"2026-11-19\",\"total_labour_count\":6,\"total_labour_wage\":5600,\"total_expenses\":0,\"grand_total\":5600,\"trade_summary\":\"2 \\u0baa\\u0bc6\\u0ba3\\u0bcd \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd + 3 \\u0b9a\\u0bbf\\u0ba4\\u0bcd\\u0ba4\\u0bbe\\u0bb3\\u0bcd (\\u0b86\\u0ba3\\u0bcd) + 1 \\u0b95\\u0bca\\u0ba4\\u0bcd\\u0ba4\\u0ba9\\u0bbe\\u0bb0\\u0bcd\",\"created_by\":8,\"id\":4}',	'{\"model\":\"DailyAllocation\",\"id\":4}',	'sha256:50722e5e726433f1e8e4f87dd87beea7f76f606ba056179b84aa2317613df106',	'2026-11-20 18:25:23',	'2026-11-20 18:25:23'),
(121,	'AUD-20261120-YXW2',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'daily_wages',	'App\\Models\\WeeklyWage',	'#WEEK-002',	'UPDATE',	'warning',	'Updated WeeklyWage (#WEEK-002) [fields: total_labour_man_days, total_labour_wages, net_payout]',	'WeeklyWage (#WEEK-002) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: total_labour_man_days, total_labour_wages, net_payout]',	'Parthasarathi Sir Site',	NULL,	'{\"total_labour_man_days\":17,\"total_labour_wages\":\"15600.00\",\"net_payout\":\"16100.00\"}',	'{\"total_labour_man_days\":23,\"total_labour_wages\":21200,\"net_payout\":21700}',	'{\"model\":\"WeeklyWage\",\"changed_fields\":[\"total_labour_man_days\",\"total_labour_wages\",\"net_payout\"]}',	'sha256:488e41583d551564e1ed15b0d1f83233a689bf6dc599136fed467162a4eaa151',	'2026-11-20 18:25:23',	'2026-11-20 18:25:23'),
(122,	'AUD-20261120-XNAS',	8,	'syed',	'Site Manager',	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'auth',	'App\\Models\\User',	'#USER-008',	'LOGOUT',	'info',	'User syed (Site Manager) logged out from session',	'பயனாளர் syed அமர்விலிருந்து வெளியேறினார்',	'Head Office',	NULL,	NULL,	NULL,	'{\"email\":\"syed@deiveni.com\",\"role\":\"Site Manager\"}',	'sha256:5b83da16af7fce16c2b8256ac6b54047927fd662141a10691b426abccc7a0054',	'2026-11-20 18:27:27',	'2026-11-20 18:27:27');

DROP TABLE IF EXISTS `banks`;
CREATE TABLE `banks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(255) NOT NULL,
  `bank_name_ta` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banks_bank_name_unique` (`bank_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `banks` (`id`, `bank_name`, `bank_name_ta`, `status`, `created_at`, `updated_at`) VALUES
(1,	'State Bank of India (SBI)',	'பாரத ஸ்டேட் வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-11-06 14:05:20'),
(2,	'HDFC Bank',	'ஹெச்டிஎஃப்சி வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-10-24 00:47:03'),
(3,	'Indian Bank',	'இந்தியன் வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-10-24 00:47:03'),
(4,	'Canara Bank',	'கனரா வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-10-24 00:47:03'),
(5,	'ICICI Bank',	'ஐசிஐசிஐ வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-10-24 00:47:03'),
(6,	'Axis Bank',	'ஆக்சிஸ் வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-10-24 00:47:03'),
(7,	'Punjab National Bank',	'பஞ்சாப் நேஷனல் வங்கி',	'Active',	'2026-10-24 00:47:03',	'2026-10-24 00:47:03'),
(8,	'Bank of Baroda',	'பாங்க் ஆஃப் பரோடா',	'Active',	'2026-10-24 00:47:03',	'2026-10-27 03:28:05');

DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_id` bigint(20) unsigned DEFAULT NULL,
  `branch_name` varchar(255) NOT NULL,
  `branch_name_ta` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(11) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `valuation_fee` decimal(10,2) DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banks_ifsc_code_unique` (`ifsc_code`),
  UNIQUE KEY `branches_branch_name_unique` (`branch_name`),
  UNIQUE KEY `branches_branch_name_ta_unique` (`branch_name_ta`),
  KEY `branches_bank_id_foreign` (`bank_id`),
  CONSTRAINT `branches_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `branches` (`id`, `bank_id`, `branch_name`, `branch_name_ta`, `ifsc_code`, `contact_person`, `phone`, `valuation_fee`, `status`, `created_at`, `updated_at`) VALUES
(1,	1,	'Kumara',	'குமாரா',	'SBIN0001234',	'Mr. S. Ramanathan (Chief Manager)',	'+91 98401 23456',	7500.00,	'Active',	'2026-10-23 04:09:43',	'2026-10-24 05:37:50'),
(2,	1,	'thanichiyam',	'தனிச்சியம்',	'SBIN0001892',	'Mr. K. Venkatesh (Loan Officer)',	'+91 98403 45678',	0.00,	'Active',	'2026-10-23 04:09:43',	'2026-10-24 05:36:45'),
(3,	1,	'SME',	'எஸ்.எம்.இ',	'SBIN000M128',	'Mr. G. Sundar (Branch Manager)',	'+91 98404 56789',	5000.00,	'Active',	'2026-10-23 04:09:43',	'2026-10-27 23:56:34'),
(4,	1,	'Kuruvithurai',	'குருவித்துறை',	'SBIN0002041',	'Mr. R. Anand (Valuation Head)',	'+91 98405 67890',	3000.00,	'Active',	'2026-10-23 04:09:43',	'2026-10-24 01:29:24'),
(5,	1,	'Alanganallur',	'அலங்காநல்லூர்',	'SBIN0000184',	'Ms. Deepa (Credit Manager)',	'+91 98406 78901',	0.00,	'Active',	'2026-10-23 04:09:43',	'2026-10-24 01:26:15');

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `cash_transfers`;
CREATE TABLE `cash_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_direction` varchar(30) NOT NULL,
  `project_site_id` bigint(20) unsigned DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `transfer_time` varchar(20) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_mode` varchar(30) NOT NULL DEFAULT 'Cash',
  `payment_reference` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `handed_over_by` varchar(255) NOT NULL,
  `received_by` varchar(255) NOT NULL,
  `purpose` text DEFAULT NULL,
  `is_temporary` tinyint(1) NOT NULL DEFAULT 1,
  `expected_return_date` date DEFAULT NULL,
  `settlement_status` varchar(20) NOT NULL DEFAULT 'pending',
  `settled_at` datetime DEFAULT NULL,
  `settled_by` bigint(20) unsigned DEFAULT NULL,
  `settlement_notes` text DEFAULT NULL,
  `acceptance_status` varchar(20) NOT NULL DEFAULT 'accepted',
  `accepted_at` datetime DEFAULT NULL,
  `accepted_by` bigint(20) unsigned DEFAULT NULL,
  `acceptance_notes` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `ledger_id` bigint(20) unsigned DEFAULT NULL,
  `site_cash_id` bigint(20) unsigned DEFAULT NULL,
  `return_site_cash_id` bigint(20) unsigned DEFAULT NULL,
  `return_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_transfers_settled_by_foreign` (`settled_by`),
  KEY `cash_transfers_transfer_direction_index` (`transfer_direction`),
  KEY `cash_transfers_project_site_id_index` (`project_site_id`),
  KEY `cash_transfers_transfer_date_index` (`transfer_date`),
  KEY `cash_transfers_settlement_status_index` (`settlement_status`),
  KEY `cash_transfers_ledger_id_index` (`ledger_id`),
  KEY `cash_transfers_site_cash_id_index` (`site_cash_id`),
  KEY `cash_transfers_created_by_index` (`created_by`),
  KEY `cash_transfers_return_site_cash_id_foreign` (`return_site_cash_id`),
  KEY `cash_transfers_return_ledger_id_foreign` (`return_ledger_id`),
  KEY `cash_transfers_acceptance_status_index` (`acceptance_status`),
  KEY `cash_transfers_accepted_by_foreign` (`accepted_by`),
  CONSTRAINT `cash_transfers_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cash_transfers` (`id`, `transfer_direction`, `project_site_id`, `transfer_date`, `transfer_time`, `amount`, `payment_mode`, `payment_reference`, `bank_name`, `handed_over_by`, `received_by`, `purpose`, `is_temporary`, `expected_return_date`, `settlement_status`, `settled_at`, `settled_by`, `settlement_notes`, `acceptance_status`, `accepted_at`, `accepted_by`, `acceptance_notes`, `receipt_image`, `ledger_id`, `site_cash_id`, `return_site_cash_id`, `return_ledger_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'project_cash',	1,	'2026-11-20',	'10:02 AM',	12000.00,	'Cash',	NULL,	NULL,	'Accountant (Raja AM)',	'Site Manager',	NULL,	0,	'2026-11-23',	'settled',	NULL,	NULL,	NULL,	'accepted',	'2026-11-20 10:07:33',	2,	NULL,	NULL,	3,	1,	NULL,	NULL,	3,	'2026-11-20 17:02:29',	'2026-11-20 17:07:33'),
(2,	'project_cash',	2,	'2026-11-20',	'10:15 AM',	250000.00,	'Cash',	NULL,	NULL,	'Accountant (Raja AM)',	'Site Manager',	NULL,	0,	'2026-11-23',	'settled',	NULL,	NULL,	NULL,	'accepted',	'2026-11-20 10:17:53',	8,	'Okay',	NULL,	4,	2,	NULL,	NULL,	9,	'2026-11-20 17:15:51',	'2026-11-20 17:17:53');

DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clients_created_by_index` (`created_by`),
  CONSTRAINT `clients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clients` (`id`, `name`, `phone`, `address`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'Mr. Parthasarathi',	'9569854585',	'Northmasi Street, Madurai, Tamil Nadu',	2,	'2026-11-17 02:18:43',	'2026-11-17 02:18:43'),
(2,	'Mr. Jayakumar',	'9865874525',	'Paraipati, Madurai, Tamil Nadu',	2,	'2026-11-17 02:20:14',	'2026-11-17 02:20:14'),
(5,	'P. Saravanan',	'9952123456',	'45, Lake View Road, Othakadai, Madurai – 625107',	2,	'2026-11-20 16:43:13',	'2026-11-20 16:43:13'),
(6,	'Parthasarathi',	'9988776655',	NULL,	8,	'2026-11-20 16:44:52',	'2026-11-20 16:44:52');

DROP TABLE IF EXISTS `daily_allocations`;
CREATE TABLE `daily_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_site_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `total_labour_count` int(11) NOT NULL DEFAULT 0,
  `total_labour_wage` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `trade_summary` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_allocations_project_site_id_date_unique` (`project_site_id`,`date`),
  KEY `daily_allocations_created_by_foreign` (`created_by`),
  KEY `daily_allocations_date_index` (`date`),
  CONSTRAINT `daily_allocations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `daily_allocations_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `daily_allocations` (`id`, `project_site_id`, `date`, `total_labour_count`, `total_labour_wage`, `total_expenses`, `grand_total`, `trade_summary`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	2,	'2026-11-16',	7,	6800.00,	500.00,	7300.00,	'2 பெண் சித்தாள் + 3 சித்தாள் (ஆண்) + 2 கொத்தனார்',	8,	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(2,	2,	'2026-11-17',	5,	4400.00,	0.00,	4400.00,	'2 பெண் சித்தாள் + 3 சித்தாள் (ஆண்)',	8,	'2026-11-20 18:24:52',	'2026-11-20 18:24:52'),
(3,	2,	'2026-11-18',	5,	4400.00,	0.00,	4400.00,	'2 பெண் சித்தாள் + 3 சித்தாள் (ஆண்)',	8,	'2026-11-20 18:25:08',	'2026-11-20 18:25:08'),
(4,	2,	'2026-11-19',	6,	5600.00,	0.00,	5600.00,	'2 பெண் சித்தாள் + 3 சித்தாள் (ஆண்) + 1 கொத்தனார்',	8,	'2026-11-20 18:25:23',	'2026-11-20 18:25:23');

DROP TABLE IF EXISTS `daily_allocation_expenses`;
CREATE TABLE `daily_allocation_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `daily_allocation_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_allocation_expenses_daily_allocation_id_index` (`daily_allocation_id`),
  CONSTRAINT `daily_allocation_expenses_daily_allocation_id_foreign` FOREIGN KEY (`daily_allocation_id`) REFERENCES `daily_allocations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `daily_allocation_expenses` (`id`, `daily_allocation_id`, `item_name`, `quantity`, `unit_price`, `amount`, `created_at`, `updated_at`) VALUES
(1,	1,	'Scaffolding Trestle - கோக்காலி',	2.00,	250.00,	500.00,	'2026-11-20 18:24:35',	'2026-11-20 18:24:35');

DROP TABLE IF EXISTS `daily_allocation_items`;
CREATE TABLE `daily_allocation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `daily_allocation_id` bigint(20) unsigned NOT NULL,
  `worker_category_id` bigint(20) unsigned DEFAULT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `name_ta` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `daily_wage` decimal(15,2) NOT NULL DEFAULT 0.00,
  `master_wage` decimal(15,2) NOT NULL DEFAULT 0.00,
  `worker_count` int(11) NOT NULL DEFAULT 0,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_by` varchar(50) DEFAULT NULL,
  `ledger_id` bigint(20) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `wage_cash_source` varchar(30) DEFAULT 'office_cash',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_allocation_items_worker_category_id_foreign` (`worker_category_id`),
  KEY `daily_allocation_items_daily_allocation_id_index` (`daily_allocation_id`),
  CONSTRAINT `daily_allocation_items_daily_allocation_id_foreign` FOREIGN KEY (`daily_allocation_id`) REFERENCES `daily_allocations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `daily_allocation_items_worker_category_id_foreign` FOREIGN KEY (`worker_category_id`) REFERENCES `worker_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `daily_allocation_items` (`id`, `daily_allocation_id`, `worker_category_id`, `category_code`, `name_ta`, `name_en`, `daily_wage`, `master_wage`, `worker_count`, `subtotal`, `paid_by`, `ledger_id`, `paid_at`, `wage_cash_source`, `created_at`, `updated_at`) VALUES
(1,	1,	4,	'CAT-004',	'பெண் சித்தாள்',	'Female Labour Helper',	850.00,	850.00,	2,	1700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(2,	1,	3,	'CAT-003',	'சித்தாள் (ஆண்)',	'Male Labour Helper',	900.00,	900.00,	3,	2700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(3,	1,	2,	'CAT-002',	'கொத்தனார்',	'Brick Mason / Plasterer',	1200.00,	1200.00,	2,	2400.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:24:35',	'2026-11-20 18:24:35'),
(4,	2,	4,	'CAT-004',	'பெண் சித்தாள்',	'Female Labour Helper',	850.00,	850.00,	2,	1700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:24:52',	'2026-11-20 18:24:52'),
(5,	2,	3,	'CAT-003',	'சித்தாள் (ஆண்)',	'Male Labour Helper',	900.00,	900.00,	3,	2700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:24:52',	'2026-11-20 18:24:52'),
(6,	2,	2,	'CAT-002',	'கொத்தனார்',	'Brick Mason / Plasterer',	1200.00,	1200.00,	0,	0.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:24:52',	'2026-11-20 18:24:52'),
(7,	3,	4,	'CAT-004',	'பெண் சித்தாள்',	'Female Labour Helper',	850.00,	850.00,	2,	1700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:25:08',	'2026-11-20 18:25:08'),
(8,	3,	3,	'CAT-003',	'சித்தாள் (ஆண்)',	'Male Labour Helper',	900.00,	900.00,	3,	2700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:25:08',	'2026-11-20 18:25:08'),
(9,	3,	2,	'CAT-002',	'கொத்தனார்',	'Brick Mason / Plasterer',	1200.00,	1200.00,	0,	0.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:25:08',	'2026-11-20 18:25:08'),
(10,	4,	4,	'CAT-004',	'பெண் சித்தாள்',	'Female Labour Helper',	850.00,	850.00,	2,	1700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:25:23',	'2026-11-20 18:25:23'),
(11,	4,	3,	'CAT-003',	'சித்தாள் (ஆண்)',	'Male Labour Helper',	900.00,	900.00,	3,	2700.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:25:23',	'2026-11-20 18:25:23'),
(12,	4,	2,	'CAT-002',	'கொத்தனார்',	'Brick Mason / Plasterer',	1200.00,	1200.00,	1,	1200.00,	NULL,	NULL,	NULL,	NULL,	'2026-11-20 18:25:23',	'2026-11-20 18:25:23');

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `job_batches`;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `ledgers`;
CREATE TABLE `ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `flow` enum('income','expense') NOT NULL DEFAULT 'income',
  `source` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `project_site_id` bigint(20) unsigned DEFAULT NULL,
  `cash_transfer_id` bigint(20) unsigned DEFAULT NULL,
  `daily_allocation_id` bigint(20) unsigned DEFAULT NULL,
  `wage_item_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`wage_item_ids`)),
  `extra_workers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_workers`)),
  `wage_cash_source` varchar(30) DEFAULT 'office_cash',
  `site_name` varchar(255) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `cash_received_from` enum('client','owner') DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `bank_id` bigint(20) unsigned DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `bank_customer_ref` varchar(255) DEFAULT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `property_name` varchar(255) DEFAULT NULL,
  `property_type_id` bigint(20) unsigned DEFAULT NULL,
  `unit_name` varchar(255) DEFAULT NULL,
  `office_expense_details` varchar(255) DEFAULT NULL,
  `transaction_type_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_type_name` varchar(255) DEFAULT NULL,
  `transaction_type_value` text DEFAULT NULL,
  `transaction_type_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transaction_type_data`)),
  `description` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ledgers_project_site_id_foreign` (`project_site_id`),
  KEY `ledgers_supplier_id_foreign` (`supplier_id`),
  KEY `ledgers_bank_id_foreign` (`bank_id`),
  KEY `ledgers_branch_id_foreign` (`branch_id`),
  KEY `ledgers_property_id_foreign` (`property_id`),
  KEY `ledgers_property_type_id_foreign` (`property_type_id`),
  KEY `ledgers_transaction_type_id_foreign` (`transaction_type_id`),
  KEY `ledgers_created_by_foreign` (`created_by`),
  KEY `ledgers_cash_transfer_id_foreign` (`cash_transfer_id`),
  CONSTRAINT `ledgers_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_cash_transfer_id_foreign` FOREIGN KEY (`cash_transfer_id`) REFERENCES `cash_transfers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_property_type_id_foreign` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledgers_transaction_type_id_foreign` FOREIGN KEY (`transaction_type_id`) REFERENCES `transaction_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ledgers` (`id`, `entry_date`, `flow`, `source`, `amount`, `project_site_id`, `cash_transfer_id`, `daily_allocation_id`, `wage_item_ids`, `extra_workers`, `wage_cash_source`, `site_name`, `client_name`, `cash_received_from`, `supplier_id`, `supplier_name`, `bank_id`, `bank_name`, `branch_id`, `branch_name`, `bank_customer_ref`, `property_id`, `property_name`, `property_type_id`, `unit_name`, `office_expense_details`, `transaction_type_id`, `transaction_type_name`, `transaction_type_value`, `transaction_type_data`, `description`, `receipt_image`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'2026-11-20',	'income',	'Construction',	20000.00,	2,	NULL,	NULL,	NULL,	NULL,	NULL,	'Parthasarathi Sir Site',	'Parthasarathi',	'client',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	1,	'Cash Advance',	'',	'[{\"id\":1,\"name\":\"Cash Advance\",\"category\":\"Construction\",\"value\":\"\"}]',	'Construction: Parthasarathi Sir Site (Parthasarathi)',	NULL,	3,	'2026-11-20 16:57:29',	'2026-11-20 16:57:29'),
(2,	'2026-11-20',	'income',	'Construction',	200000.00,	2,	NULL,	NULL,	NULL,	NULL,	NULL,	'Parthasarathi Sir Site',	'Parthasarathi',	'owner',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	1,	'Cash Advance',	'',	'[{\"id\":1,\"name\":\"Cash Advance\",\"category\":\"Construction\",\"value\":\"\"}]',	'Construction: Parthasarathi Sir Site (Parthasarathi)',	NULL,	9,	'2026-11-20 16:59:23',	'2026-11-20 16:59:23'),
(3,	'2026-11-20',	'expense',	'Construction',	12000.00,	1,	1,	NULL,	NULL,	NULL,	'office_cash',	'Lake View Luxury Villa',	'P. Saravanan',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'Project Cash Transfer to Site',	NULL,	NULL,	'Project Cash transferred to Site Manager (Lake View Luxury Villa) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Accepted]',	NULL,	3,	'2026-11-20 17:02:29',	'2026-11-20 17:07:33'),
(4,	'2026-11-20',	'expense',	'Construction',	250000.00,	2,	2,	NULL,	NULL,	NULL,	'office_cash',	'Parthasarathi Sir Site',	'Parthasarathi',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'Project Cash Transfer to Site',	NULL,	NULL,	'Project Cash transferred to Site Manager (Parthasarathi Sir Site) - Given by: Accountant (Raja AM), Received by: Site Manager. Purpose: Inter-entity cash transfer [Accepted]',	NULL,	9,	'2026-11-20 17:15:51',	'2026-11-20 17:17:53');

DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(255) NOT NULL,
  `name_ta` varchar(255) NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materials_name_en_unique` (`name_en`),
  UNIQUE KEY `materials_name_ta_unique` (`name_ta`),
  KEY `materials_unit_id_foreign` (`unit_id`),
  CONSTRAINT `materials_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `materials` (`id`, `name_en`, `name_ta`, `unit_id`, `status`, `created_at`, `updated_at`) VALUES
(1,	'Cement',	'சிமெண்ட்',	1,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 08:03:56'),
(2,	'M-Sand (Manufactured Sand)',	'எம்-சாண்ட் (கட்டுமான மணல்)',	4,	'Active',	'2026-10-13 05:02:02',	'2026-10-13 05:26:35'),
(3,	'Hoe / Spade',	'மண்வெட்டி',	6,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:56:25'),
(4,	'TMT 550D Steel Rods (12mm)',	'TMT கம்பிகள்',	2,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 08:04:17'),
(5,	'Red Bricks',	'செங்கல் (சாம்பர் செங்கல்)',	6,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:54:24'),
(6,	'Tarpaulin Sheet',	'தார்பாய்',	6,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:54:00'),
(7,	'Construction String',	'சரக்கயிறு',	9,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:53:03'),
(8,	'Scaffolding Trestle',	'கோக்காலி',	6,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:51:56'),
(9,	'Concrete Mixer Machine',	'கான்கிரீட் மிக்சர் இயந்திரம்',	10,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:51:04'),
(10,	'Demolition Breaker',	'மிஷன் உடைப்பு',	10,	'Active',	'2026-10-13 05:02:02',	'2026-10-15 07:46:08'),
(13,	'Mortar Pan / Ghamela',	'தட்டு',	6,	'Active',	'2026-10-15 07:57:10',	'2026-10-15 07:57:10'),
(14,	'Wooden Plank',	'மர பலகை',	6,	'Active',	'2026-10-15 07:57:58',	'2026-10-15 07:57:58'),
(15,	'Shuttering Board',	'கம்பி கட்டு பலகை அடைப்பு',	6,	'Active',	'2026-10-15 07:58:35',	'2026-10-15 07:58:35'),
(16,	'Welding Machine',	'வேல்ட் மிஷன்',	10,	'Active',	'2026-10-15 07:59:09',	'2026-10-15 07:59:09'),
(17,	'Paint',	'வண்ணம் பூசு',	3,	'Active',	'2026-10-16 04:03:55',	'2026-10-16 04:03:55');

DROP TABLE IF EXISTS `material_usages`;
CREATE TABLE `material_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usage_no` varchar(30) NOT NULL,
  `usage_date` date NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `project_site_id` bigint(20) unsigned NOT NULL,
  `work_stage` varchar(100) DEFAULT NULL,
  `supervisor_name` varchar(100) DEFAULT NULL,
  `total_items_count` int(11) NOT NULL DEFAULT 1,
  `total_used_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Recorded',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_usages_usage_no_unique` (`usage_no`),
  KEY `material_usages_created_by_foreign` (`created_by`),
  KEY `material_usages_project_site_id_usage_date_index` (`project_site_id`,`usage_date`),
  KEY `material_usages_purchase_order_id_index` (`purchase_order_id`),
  CONSTRAINT `material_usages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_usages_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_usages_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `material_usages` (`id`, `usage_no`, `usage_date`, `purchase_order_id`, `project_site_id`, `work_stage`, `supervisor_name`, `total_items_count`, `total_used_quantity`, `total_cost`, `remarks`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'MU-2026-0001',	'2026-11-20',	3,	2,	NULL,	NULL,	1,	5.00,	42500.00,	NULL,	'Recorded',	8,	'2026-11-20 17:58:04',	'2026-11-20 17:58:04'),
(2,	'MU-2026-0002',	'2026-11-20',	2,	1,	NULL,	NULL,	2,	67.00,	30420.00,	NULL,	'Recorded',	2,	'2026-11-20 18:01:24',	'2026-11-20 18:01:24');

DROP TABLE IF EXISTS `material_usage_items`;
CREATE TABLE `material_usage_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_usage_id` bigint(20) unsigned NOT NULL,
  `purchase_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'PO',
  `source_reference` varchar(100) DEFAULT NULL,
  `inward_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `used_quantity` decimal(12,2) NOT NULL,
  `remaining_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_usage_items_unit_id_foreign` (`unit_id`),
  KEY `material_usage_items_material_usage_id_index` (`material_usage_id`),
  KEY `material_usage_items_material_id_index` (`material_id`),
  KEY `material_usage_items_purchase_order_item_id_index` (`purchase_order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `material_usage_items` (`id`, `material_usage_id`, `purchase_order_item_id`, `material_id`, `material_name`, `source_type`, `source_reference`, `inward_quantity`, `used_quantity`, `remaining_quantity`, `unit`, `unit_id`, `unit_price`, `total_price`, `created_at`, `updated_at`) VALUES
(1,	1,	3,	2,	'M-Sand (Manufactured Sand)',	'Third-Party Supplier',	'PO-2026-003',	15.00,	5.00,	10.00,	'Load (Truck Load)',	NULL,	8500.00,	42500.00,	'2026-11-20 17:58:04',	'2026-11-20 17:58:04'),
(2,	2,	2,	17,	'Paint',	'Third-Party Supplier',	'PO-2026-002',	15.00,	5.00,	10.00,	'Litres',	NULL,	1000.00,	5000.00,	'2026-11-20 18:01:24',	'2026-11-20 18:01:24'),
(3,	2,	1,	1,	'Cement',	'Third-Party Supplier',	'PO-2026-001',	100.00,	62.00,	38.00,	'Bags',	NULL,	410.00,	25420.00,	'2026-11-20 18:01:24',	'2026-11-20 18:01:24');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1,	'0001_01_01_000000_create_users_table',	1),
(2,	'0001_01_01_000001_create_cache_table',	1),
(3,	'0001_01_01_000002_create_jobs_table',	1),
(4,	'2026_10_13_055937_add_role_and_details_to_users_table',	2),
(6,	'2026_10_13_084806_create_worker_categories_table',	3),
(7,	'2026_10_13_100721_create_units_table',	4),
(8,	'2026_10_13_103013_create_materials_table',	5),
(9,	'2026_10_13_110239_create_suppliers_table',	6),
(12,	'2026_10_15_090118_remove_rate_from_materials_table',	8),
(13,	'2026_10_15_094809_create_supplier_materials_table',	9),
(14,	'2026_10_15_101004_add_grade_to_supplier_materials_table',	10),
(15,	'2026_10_21_094654_add_unit_id_to_materials_table',	11),
(17,	'2026_10_23_065302_create_banks_table',	12),
(20,	'2026_10_23_095646_create_rentals_table',	13),
(21,	'2026_10_24_060000_rename_banks_to_branches_table',	14),
(22,	'2026_10_24_060100_create_banks_table',	14),
(23,	'2026_10_24_063000_rename_bank_name_ta_to_branch_name_ta_in_branches_table',	15),
(24,	'2026_10_24_070000_create_properties_table',	16),
(25,	'2026_10_24_071000_rename_rentals_to_property_types_table',	16),
(26,	'2026_10_24_072000_add_property_id_to_property_types_table',	17),
(27,	'2026_10_24_073000_add_bank_id_to_branches_table',	18),
(28,	'2026_10_24_100648_add_property_name_ta_to_properties_table',	19),
(29,	'2026_10_28_170000_create_roles_and_role_menus_tables',	20),
(30,	'2026_11_03_100000_create_clients_table',	21),
(31,	'2026_11_03_100001_create_project_sites_table',	21),
(34,	'2026_11_04_060258_create_transaction_types_table',	22),
(35,	'2026_11_04_105434_create_site_cashes_table',	23),
(38,	'2026_11_04_180000_create_daily_allocations_tables',	24),
(40,	'2026_11_04_180100_create_weekly_wages_tables',	25),
(41,	'2026_11_05_092805_create_warehouse_tables',	26),
(42,	'2026_11_05_092816_create_purchase_orders_tables',	26),
(43,	'2026_11_05_131600_update_purchase_order_items_change_warehouse_item_id_to_material_id',	27),
(44,	'2026_11_06_165000_create_ledgers_table',	28),
(45,	'2026_11_06_184612_create_audit_logs_table',	29),
(46,	'2026_11_08_170207_add_unique_constraints_to_worker_categories_table',	30),
(47,	'2026_11_08_172554_add_unique_constraints_to_units_table',	31),
(48,	'2026_11_08_174857_add_unique_constraints_to_materials_table',	32),
(49,	'2026_11_08_180842_add_unique_constraints_to_suppliers_table',	33),
(50,	'2026_11_08_182510_add_unique_constraints_to_branches_table',	34),
(51,	'2026_11_08_184136_add_unique_constraint_to_property_types_table',	35),
(52,	'2026_11_09_194000_add_cash_amount_to_warehouse_tables',	36),
(53,	'2026_11_12_101555_add_created_by_to_project_sites_clients_and_weekly_wages_tables',	37),
(54,	'2026_11_14_052644_change_unit_to_unit_id_in_warehouse_items_table',	38),
(55,	'2026_11_18_102242_add_payment_mode_and_recipient_to_site_cashes_table',	39),
(56,	'2026_11_18_113500_add_extended_payment_details_to_site_cashes_table',	40),
(57,	'2026_11_18_124500_add_account_manager_wages_tracking',	41),
(58,	'2026_11_18_134500_add_wage_cash_source_to_tables',	42),
(59,	'2026_11_18_135000_add_extra_workers_to_ledgers',	43),
(60,	'2026_11_18_151057_add_return_tracking_to_site_cashes_table',	44),
(61,	'2026_11_18_164312_create_cash_transfers_table',	45),
(62,	'2026_11_18_171636_add_cash_transfer_id_to_site_cashes_and_ledgers_table',	46),
(63,	'2026_11_18_175650_add_return_ids_to_cash_transfers_table',	47),
(64,	'2026_11_19_094606_add_cash_transfer_settle_permission_to_role_menus',	48),
(65,	'2026_11_18_140433_create_site_material_stocks_table',	49),
(66,	'2026_11_18_140444_create_stock_transfers_table',	49),
(67,	'2026_11_18_154228_create_stock_transfer_items_table',	49),
(68,	'2026_11_18_164553_create_material_usages_and_items_tables',	49),
(69,	'2026_11_18_174317_make_purchase_order_id_nullable_in_material_usages_table',	49),
(70,	'2026_11_19_121613_sync_all_menus_to_owner_role',	50),
(71,	'2026_11_19_160738_add_cash_received_from_to_ledgers_table',	51),
(72,	'2026_11_19_121613_sync_all_menus_to_owner_role',	100),
(73,	'2026_11_19_160738_add_cash_received_from_to_ledgers_table',	100),
(74,	'2026_11_19_182504_add_acceptance_status_to_cash_transfers_table',	100);

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `project_sites`;
CREATE TABLE `project_sites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `work_type` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `estimated_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'In Progress',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_sites_client_id_foreign` (`client_id`),
  KEY `project_sites_created_by_index` (`created_by`),
  CONSTRAINT `project_sites_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_sites_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `project_sites` (`id`, `client_id`, `name`, `location`, `work_type`, `start_date`, `end_date`, `estimated_budget`, `spent`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	5,	'Lake View Luxury Villa',	'Othakadai, Madurai',	'New Build',	'2026-12-10',	'2027-09-10',	6500000.00,	0.00,	'In Progress',	2,	'2026-11-20 16:43:13',	'2026-11-20 16:43:13'),
(2,	6,	'Parthasarathi Sir Site',	'Madurai',	'New Build',	'2026-11-20',	'2027-04-20',	1000000.00,	0.00,	'In Progress',	8,	'2026-11-20 16:44:52',	'2026-11-20 16:44:52');

DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_name` varchar(255) NOT NULL,
  `property_name_ta` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `properties_property_name_unique` (`property_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `properties` (`id`, `property_name`, `property_name_ta`, `status`, `created_at`, `updated_at`) VALUES
(8,	'Sri Deiveni Amman Complex - Utchaparamedu',	'ஸ்ரீ தெய்வேனி அம்மன் காம்ப்ளக்ஸ் - உச்சபரமேடு',	'Active',	'2026-10-24 03:18:02',	'2026-10-24 05:06:14'),
(9,	'Sri Deiveni Complex - Iyer Bungalow',	'ஸ்ரீ தெய்வேனி காம்ப்ளக்ஸ் - ஐயர் பங்களோ',	'Active',	'2026-10-24 03:18:02',	'2026-10-24 05:04:33');

DROP TABLE IF EXISTS `property_types`;
CREATE TABLE `property_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `shop_home_office_name` varchar(255) DEFAULT NULL,
  `property_type` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `tenant_name` varchar(255) DEFAULT NULL,
  `tenant_phone` varchar(255) DEFAULT NULL,
  `monthly_rent` decimal(12,2) NOT NULL DEFAULT 0.00,
  `security_deposit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rent_due_day` varchar(255) NOT NULL DEFAULT '1st of Month',
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `floor_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_types_shop_home_office_name_unique` (`shop_home_office_name`),
  KEY `property_types_property_id_foreign` (`property_id`),
  CONSTRAINT `property_types_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `property_types` (`id`, `property_id`, `shop_home_office_name`, `property_type`, `location`, `tenant_name`, `tenant_phone`, `monthly_rent`, `security_deposit`, `rent_due_day`, `status`, `created_at`, `updated_at`, `floor_type`) VALUES
(1,	9,	'Vanya Sweet',	'Commercial Shop',	'Iyer Bungalow',	'Ram',	'+91 98411 22334',	39600.00,	0.00,	'5th of Month',	'Active',	'2026-10-24 05:11:09',	'2026-10-24 05:11:09',	'GF'),
(2,	9,	'Lucky Store',	'Commercial Shop',	'Iyer Bungalow',	'Selva',	'+91 98411 22399',	10000.00,	0.00,	'10th of Month',	'Active',	'2026-10-24 05:13:22',	'2026-10-24 05:13:22',	'GF'),
(3,	9,	'Lawyer',	'Office Space',	'Iyer Bungalow',	'Ram Sir',	'+91 98411 22354',	4500.00,	0.00,	'5th of Month',	'Active',	'2026-10-24 05:18:49',	'2026-10-24 05:18:49',	'FF'),
(4,	9,	'Mutual Fund',	'Commercial Shop',	'Iyer Bungalow',	'Sheik',	'+91 98411 22323',	7000.00,	0.00,	'1st of Month',	'Active',	'2026-10-24 05:21:13',	'2026-10-24 05:21:13',	'FF'),
(5,	9,	'Dock Muthu',	'Commercial Shop',	'Iyer Bungalow',	'Muthu',	'+91 98411 22347',	7000.00,	0.00,	'1st of Month',	'Active',	'2026-10-24 05:22:21',	'2026-10-24 05:22:21',	'FF'),
(6,	8,	'Saloon',	'Commercial Shop',	'Corner Plot, GST Road',	'Selva',	'+91 98411 22364',	5500.00,	0.00,	'15th of Month',	'Active',	'2026-10-24 05:24:09',	'2026-10-24 05:24:09',	NULL),
(7,	8,	'Studio',	'Commercial Shop',	'Medavakkam Main Road',	'Ram',	'+91 98411 22111',	5500.00,	0.00,	'1st of Month',	'Active',	'2026-10-24 05:26:20',	'2026-10-24 05:26:20',	NULL),
(8,	8,	'People Laundry',	'Commercial Shop',	'Corner Plot',	'Bala',	'+91 98411 22888',	15000.00,	0.00,	'5th of Month',	'Active',	'2026-10-24 05:28:58',	'2026-10-24 05:28:58',	NULL),
(9,	8,	'J Square',	'Commercial Shop',	'Medavakkam Main Road',	'Mohamed',	'+91 98411 22888',	6000.00,	0.00,	'15th of Month',	'Active',	'2026-10-24 05:30:37',	'2026-10-24 05:30:37',	'FF'),
(10,	8,	'structural',	'Commercial Shop',	'GST Road',	'Selva',	'+91 98411 22451',	6000.00,	0.00,	'10th of Month',	'Active',	'2026-10-24 05:34:05',	'2026-10-24 05:34:25',	'FF');

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(100) NOT NULL,
  `order_date` date NOT NULL,
  `project_site_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'Third-Party Supplier',
  `status` varchar(50) NOT NULL DEFAULT 'Ordered',
  `status_reason` varchar(255) DEFAULT NULL,
  `delivery_status` varchar(50) NOT NULL DEFAULT 'Inward Pending',
  `delivery_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  KEY `purchase_orders_project_site_id_status_index` (`project_site_id`,`status`),
  KEY `purchase_orders_order_date_index` (`order_date`),
  KEY `purchase_orders_delivery_status_index` (`delivery_status`),
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_orders` (`id`, `po_number`, `order_date`, `project_site_id`, `supplier_id`, `supplier_name`, `source_type`, `status`, `status_reason`, `delivery_status`, `delivery_date`, `subtotal`, `tax_amount`, `total_amount`, `notes`, `receipt_image`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'PO-2026-001',	'2026-11-20',	1,	2,	'Chettinad Cement Agency',	'Third-Party Supplier',	'Ordered',	NULL,	'Inward Pending',	NULL,	41000.00,	0.00,	41000.00,	NULL,	NULL,	2,	'2026-11-20 17:55:00',	'2026-11-20 17:55:00'),
(2,	'PO-2026-002',	'2026-11-20',	1,	3,	'Rani Paints',	'Third-Party Supplier',	'Ordered',	NULL,	'Inward Pending',	NULL,	15000.00,	0.00,	15000.00,	NULL,	NULL,	2,	'2026-11-20 17:55:54',	'2026-11-20 17:55:54'),
(3,	'PO-2026-003',	'2026-11-20',	2,	1,	'Sri Balaji Blue Metals & M-Sand',	'Third-Party Supplier',	'Ordered',	NULL,	'Inward Pending',	NULL,	127500.00,	0.00,	127500.00,	NULL,	NULL,	8,	'2026-11-20 17:57:26',	'2026-11-20 17:57:26');

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `material_name` varchar(255) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) NOT NULL DEFAULT 'Nos',
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_index` (`purchase_order_id`),
  KEY `purchase_order_items_material_id_foreign` (`material_id`),
  CONSTRAINT `purchase_order_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `material_id`, `material_name`, `quantity`, `unit`, `unit_price`, `total_price`, `created_at`, `updated_at`) VALUES
(1,	1,	1,	'Cement',	100.00,	'Bags',	410.00,	41000.00,	'2026-11-20 17:55:00',	'2026-11-20 17:55:00'),
(2,	2,	17,	'Paint',	15.00,	'Litres',	1000.00,	15000.00,	'2026-11-20 17:55:54',	'2026-11-20 17:55:54'),
(3,	3,	2,	'M-Sand (Manufactured Sand)',	15.00,	'Load (Truck Load)',	8500.00,	127500.00,	'2026-11-20 17:57:26',	'2026-11-20 17:57:26');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1,	'Owner',	'owner',	'System Owner with full administrative access across all modules.',	1,	'2026-10-28 06:35:13',	'2026-10-28 06:35:13'),
(2,	'Site Manager',	'site-manager',	'Field operations, material requests, daily labour allocations, wages, and store inventory.',	1,	'2026-10-28 06:35:14',	'2026-10-28 07:25:10'),
(3,	'Account Manager',	'account-manager',	'Financial operations, daily ledgers, banks, property rental masters, and supplier ledgers.',	1,	'2026-10-28 06:35:14',	'2026-10-28 07:06:41');

DROP TABLE IF EXISTS `role_menus`;
CREATE TABLE `role_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `menu_key` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_menus_role_id_menu_key_unique` (`role_id`,`menu_key`),
  CONSTRAINT `role_menus_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_menus` (`id`, `role_id`, `menu_key`, `created_at`, `updated_at`) VALUES
(429,	2,	'dashboard',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(430,	2,	'worker_categories',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(431,	2,	'units',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(432,	2,	'materials',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(433,	2,	'suppliers',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(434,	2,	'sites',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(435,	2,	'daily_allocation',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(436,	2,	'weekly_wages',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(437,	2,	'site_cash',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(438,	2,	'purchases',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(439,	2,	'material_usage',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(440,	2,	'stock_transfer',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(441,	2,	'cash_transfer',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(442,	2,	'warehouse',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(443,	2,	'reports_labour_allocations',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(444,	2,	'reports_cash_handling',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(445,	2,	'reports_purchases_inward',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(446,	2,	'reports_warehouse_inventory',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(447,	2,	'reports_stock_transfer',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(448,	2,	'profile',	'2026-11-19 06:33:19',	'2026-11-19 06:33:19'),
(480,	2,	'transaction_types',	'2026-11-19 06:46:31',	'2026-11-19 06:46:31'),
(498,	1,	'dashboard',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(499,	1,	'sites',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(500,	1,	'reports_profit_loss',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(501,	1,	'reports_labour_allocations',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(502,	1,	'reports_cash_handling',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(503,	1,	'reports_purchases_inward',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(504,	1,	'reports_warehouse_inventory',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(505,	1,	'reports_ledger',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(506,	1,	'users',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(507,	1,	'audit_logs',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(508,	1,	'profile',	'2026-11-20 16:40:54',	'2026-11-20 16:40:54'),
(509,	3,	'dashboard',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(510,	3,	'suppliers',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(511,	3,	'banks',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(512,	3,	'branches',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(513,	3,	'properties',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(514,	3,	'property_types',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(515,	3,	'transaction_types',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(516,	3,	'material_usage',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(517,	3,	'stock_transfer',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(518,	3,	'ledger',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(519,	3,	'cash_transfer',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(520,	3,	'cash_transfer_settle',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(521,	3,	'reports_stock_transfer',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(522,	3,	'reports_ledger',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11'),
(523,	3,	'profile',	'2026-11-20 17:15:11',	'2026-11-20 17:15:11');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('08pmPSB4z9QXt3wUzdOBO8n3Tphl1SVMQPsJjOUe',	2,	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUHNkaHBxekcxS3ZYSXZzU01pblhMYWhSSnl5N3IwQ2JzQkczWXVZRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTM6Imh0dHBzOi8vZ2Vld2luLnRlY2hidWNrZXQuaW4vZGVpdmVuaS9wdWJsaWMvcHVyY2hhc2VzIjtzOjU6InJvdXRlIjtzOjE1OiJwdXJjaGFzZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiIyOGVhMWMyMTNiNGJjZjRlODI1NGJhNjFiNDkyZWJkODE3YTA3ZTNmMzMxYzgzNzRmZjhlNzliM2RiMTg3Mjk3Ijt9',	1795153999),
('lDf5KrdKCRvbSTyDwKwbbpJ00ZE3AeI7RfuUc0p8',	8,	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaHlkcUtTdEFma2lLOHdHNXV3VWx3a3JIdVlXQ09ZcnBNRUVVQnZYbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTM6Imh0dHBzOi8vZ2Vld2luLnRlY2hidWNrZXQuaW4vZGVpdmVuaS9wdWJsaWMvcHVyY2hhc2VzIjtzOjU6InJvdXRlIjtzOjE1OiJwdXJjaGFzZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo4O3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiIxMWFhNzgxZGZiYjI5MjVkYThiMzU5MWEzOGJjNjhjNjMzZmQyZGZhN2RiMDRlYzRhNjFhMGJlZTlmOWY0YTVjIjt9',	1795155927),
('XAhcQ9NKrzofFSQKaylegBCiMevgpbIwBf4ZDmb9',	NULL,	'103.16.202.228',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',	'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWllQY2luclJzNnJWZUVCYk5NWVpDUkJzMkFQcExnU2Q2Q1lUQnc2eCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDM6Imh0dHBzOi8vZ2Vld2luLnRlY2hidWNrZXQuaW4vZGVpdmVuaS9wdWJsaWMiO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',	1795154247);

DROP TABLE IF EXISTS `site_cashes`;
CREATE TABLE `site_cashes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_site_id` bigint(20) unsigned NOT NULL,
  `transaction_type` varchar(255) NOT NULL,
  `flow_type` varchar(20) NOT NULL DEFAULT 'outflow',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(30) NOT NULL DEFAULT 'Cash',
  `payment_reference` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `paid_to` varchar(50) DEFAULT 'Site Manager',
  `paid_by` varchar(50) DEFAULT NULL,
  `ledger_id` bigint(20) unsigned DEFAULT NULL,
  `cash_transfer_id` bigint(20) unsigned DEFAULT NULL,
  `wage_cash_source` varchar(30) DEFAULT 'office_cash',
  `return_site_cash_id` bigint(20) unsigned DEFAULT NULL,
  `returned_wage_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`returned_wage_ids`)),
  `extra_workers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_workers`)),
  `receiver_name` varchar(100) DEFAULT NULL,
  `in_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `out_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_expense` tinyint(1) NOT NULL DEFAULT 0,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `purchase_order_no` varchar(255) DEFAULT NULL,
  `bill_no` varchar(255) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_time` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_cashes_supplier_id_foreign` (`supplier_id`),
  KEY `site_cashes_created_by_foreign` (`created_by`),
  KEY `site_cashes_project_site_id_is_expense_index` (`project_site_id`,`is_expense`),
  KEY `site_cashes_payment_date_index` (`payment_date`),
  KEY `site_cashes_return_site_cash_id_index` (`return_site_cash_id`),
  KEY `site_cashes_cash_transfer_id_foreign` (`cash_transfer_id`),
  CONSTRAINT `site_cashes_cash_transfer_id_foreign` FOREIGN KEY (`cash_transfer_id`) REFERENCES `cash_transfers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_cashes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_cashes_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_cashes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_cashes` (`id`, `project_site_id`, `transaction_type`, `flow_type`, `amount`, `payment_mode`, `payment_reference`, `bank_name`, `upi_id`, `account_holder_name`, `paid_to`, `paid_by`, `ledger_id`, `cash_transfer_id`, `wage_cash_source`, `return_site_cash_id`, `returned_wage_ids`, `extra_workers`, `receiver_name`, `in_amount`, `out_amount`, `is_expense`, `supplier_id`, `supplier_name`, `purchase_order_no`, `bill_no`, `payment_date`, `payment_time`, `description`, `receipt_image`, `items`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	1,	'Project Cash Received',	'inflow',	12000.00,	'Cash',	NULL,	NULL,	NULL,	NULL,	'Site Manager',	'Accountant (Raja AM)',	NULL,	1,	'project_cash',	NULL,	NULL,	NULL,	'Site Manager',	12000.00,	0.00,	0,	NULL,	NULL,	NULL,	'XFER-1',	'2026-11-20',	'10:02 AM',	'Project Cash received from Account Manager - Given by: Accountant (Raja AM). Purpose: Inter-entity cash transfer',	NULL,	NULL,	2,	'2026-11-20 17:07:33',	'2026-11-20 17:07:33'),
(2,	2,	'Project Cash Received',	'inflow',	250000.00,	'Cash',	NULL,	NULL,	NULL,	NULL,	'Site Manager',	'Accountant (Raja AM)',	NULL,	2,	'project_cash',	NULL,	NULL,	NULL,	'Site Manager',	250000.00,	0.00,	0,	NULL,	NULL,	NULL,	'XFER-2',	'2026-11-20',	'10:15 AM',	'Project Cash received from Account Manager - Given by: Accountant (Raja AM). Purpose: Inter-entity cash transfer',	NULL,	NULL,	8,	'2026-11-20 17:17:53',	'2026-11-20 17:17:53');

DROP TABLE IF EXISTS `site_material_stocks`;
CREATE TABLE `site_material_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_site_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `last_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_material_stock_unique` (`project_site_id`,`material_id`),
  KEY `site_material_stocks_material_id_foreign` (`material_id`),
  KEY `site_material_stocks_unit_id_foreign` (`unit_id`),
  CONSTRAINT `site_material_stocks_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_material_stocks_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_material_stocks_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_material_stocks` (`id`, `project_site_id`, `material_id`, `unit_id`, `unit`, `current_stock`, `last_updated_at`, `created_at`, `updated_at`) VALUES
(1,	1,	17,	3,	'Litres',	-2.00,	'2026-11-20 18:01:49',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(2,	2,	17,	3,	'Litres',	2.00,	'2026-11-20 18:01:49',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(3,	1,	1,	1,	'Bags',	-30.00,	'2026-11-20 18:01:49',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(4,	2,	1,	1,	'Bags',	30.00,	'2026-11-20 18:01:49',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(5,	2,	2,	4,	'Load (Truck Load)',	-5.00,	'2026-11-20 18:01:55',	'2026-11-20 18:01:55',	'2026-11-20 18:01:55'),
(6,	1,	2,	4,	'Load (Truck Load)',	5.00,	'2026-11-20 18:01:55',	'2026-11-20 18:01:55',	'2026-11-20 18:01:55');

DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE `stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(30) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_project_site_id` bigint(20) unsigned NOT NULL,
  `to_project_site_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` decimal(12,2) DEFAULT NULL,
  `transfer_type` varchar(20) NOT NULL DEFAULT 'TRANSFER',
  `parent_transfer_id` bigint(20) unsigned DEFAULT NULL,
  `return_expected` tinyint(1) NOT NULL DEFAULT 1,
  `return_status` varchar(30) NOT NULL DEFAULT 'Pending Return',
  `returned_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_items_count` int(11) NOT NULL DEFAULT 1,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `challan_no` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Completed',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transfers_transfer_no_unique` (`transfer_no`),
  KEY `stock_transfers_unit_id_foreign` (`unit_id`),
  KEY `stock_transfers_parent_transfer_id_foreign` (`parent_transfer_id`),
  KEY `stock_transfers_created_by_foreign` (`created_by`),
  KEY `stock_transfers_from_project_site_id_transfer_date_index` (`from_project_site_id`,`transfer_date`),
  KEY `stock_transfers_to_project_site_id_transfer_date_index` (`to_project_site_id`,`transfer_date`),
  KEY `stock_transfers_material_id_index` (`material_id`),
  KEY `stock_transfers_return_status_index` (`return_status`),
  CONSTRAINT `stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfers_from_project_site_id_foreign` FOREIGN KEY (`from_project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfers_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfers_parent_transfer_id_foreign` FOREIGN KEY (`parent_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_transfers_to_project_site_id_foreign` FOREIGN KEY (`to_project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfers_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_transfers` (`id`, `transfer_no`, `transfer_date`, `from_project_site_id`, `to_project_site_id`, `material_id`, `unit_id`, `unit`, `quantity`, `transfer_type`, `parent_transfer_id`, `return_expected`, `return_status`, `returned_quantity`, `total_amount`, `total_items_count`, `vehicle_no`, `driver_name`, `challan_no`, `remarks`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'ST-2026-0001',	'2026-11-20',	1,	2,	17,	3,	'Litres',	32.00,	'TRANSFER',	NULL,	1,	'Pending Return',	0.00,	14300.00,	2,	NULL,	NULL,	NULL,	NULL,	'Completed',	2,	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(2,	'ST-2026-0002',	'2026-11-20',	2,	1,	2,	4,	'Load (Truck Load)',	5.00,	'TRANSFER',	NULL,	1,	'Pending Return',	0.00,	42500.00,	1,	NULL,	'Murugan',	NULL,	NULL,	'Completed',	8,	'2026-11-20 18:01:55',	'2026-11-20 18:01:55');

DROP TABLE IF EXISTS `stock_transfer_items`;
CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'PO',
  `source_reference` varchar(100) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `returned_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `return_status` varchar(30) NOT NULL DEFAULT 'Pending Return',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_items_unit_id_foreign` (`unit_id`),
  KEY `stock_transfer_items_stock_transfer_id_index` (`stock_transfer_id`),
  KEY `stock_transfer_items_material_id_index` (`material_id`),
  KEY `stock_transfer_items_source_type_index` (`source_type`),
  CONSTRAINT `stock_transfer_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_transfer_items` (`id`, `stock_transfer_id`, `material_id`, `material_name`, `source_type`, `source_reference`, `supplier_name`, `quantity`, `unit`, `unit_id`, `unit_price`, `total_price`, `returned_quantity`, `return_status`, `created_at`, `updated_at`) VALUES
(1,	1,	17,	'Paint',	'PO',	'PO-2026-002',	'Rani Paints',	2.00,	'Litres',	3,	1000.00,	2000.00,	0.00,	'Pending Return',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(2,	1,	1,	'Cement',	'PO',	'PO-2026-001',	'Chettinad Cement Agency',	30.00,	'Bags',	1,	410.00,	12300.00,	0.00,	'Pending Return',	'2026-11-20 18:01:49',	'2026-11-20 18:01:49'),
(3,	2,	2,	'M-Sand (Manufactured Sand)',	'PO',	'PO-2026-003',	'Sri Balaji Blue Metals & M-Sand',	5.00,	'Load (Truck Load)',	4,	8500.00,	42500.00,	0.00,	'Pending Return',	'2026-11-20 18:01:55',	'2026-11-20 18:01:55');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_name_unique` (`name`),
  UNIQUE KEY `suppliers_gst_number_unique` (`gst_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `address`, `gst_number`, `status`, `created_at`, `updated_at`) VALUES
(1,	'Sri Balaji Blue Metals & M-Sand',	'Bala Murugan',	'94441 11222',	'Kundrathur Main Road, Chennai - 69',	'33AAAAA0000A1Z5',	'Active',	'2026-10-13 05:34:02',	'2026-10-13 05:34:02'),
(2,	'Chettinad Cement Agency',	'R. Sundaram',	'94442 22333',	'GST Road, Tambaram Sanatorium',	'33BBBBB1111B2Z6',	'Active',	'2026-10-13 05:34:02',	'2026-10-13 05:34:02'),
(3,	'Rani Paints',	'K. Praveen',	'94443 33444',	'Industrial Estate, Guindy, Chennai',	'33CCCCC2222C3Z7',	'Active',	'2026-10-13 05:34:02',	'2026-10-16 04:04:43'),
(4,	'Sunder Hardwares',	'M. Kumaran',	'94444 44555',	'Station Road, Chromepet',	'33DDDDD3333D4Z8',	'Active',	'2026-10-13 05:34:02',	'2026-10-16 01:30:03'),
(5,	'Balas',	'V. Krishna',	'94445 55666',	'Velachery Main Road, Medavakkam',	'33EEEEE4444E5Z9',	'Active',	'2026-10-13 05:34:02',	'2026-10-16 01:33:57'),
(6,	'SSS Electric Stores',	'P. Kandasamy',	'94447 77888',	'Poonamallee High Road, Maduravoyal',	'33FFFFF5555F6Z1',	'Active',	'2026-10-13 05:34:02',	'2026-10-16 01:28:38'),
(13,	'Muruganantham',	'',	'9876543210',	'',	NULL,	'Active',	'2026-11-17 19:55:57',	'2026-11-17 19:55:57');

DROP TABLE IF EXISTS `supplier_materials`;
CREATE TABLE `supplier_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `grade` varchar(50) NOT NULL DEFAULT '1st Quality',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_materials_supplier_id_material_id_unique` (`supplier_id`,`material_id`),
  KEY `supplier_materials_material_id_foreign` (`material_id`),
  CONSTRAINT `supplier_materials_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `supplier_materials_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supplier_materials` (`id`, `supplier_id`, `material_id`, `grade`, `price`, `created_at`, `updated_at`) VALUES
(1,	1,	2,	'1st Quality',	8500.00,	'2026-10-15 04:19:10',	'2026-10-21 07:06:44'),
(2,	1,	3,	'1st Quality',	6200.00,	'2026-10-15 04:19:10',	'2026-10-21 07:06:44'),
(3,	2,	1,	'1st Quality',	410.00,	'2026-10-15 04:19:10',	'2026-10-15 04:19:10'),
(6,	5,	6,	'1st Quality',	145.00,	'2026-10-15 04:19:10',	'2026-10-16 01:33:57'),
(8,	4,	10,	'Premium',	500.00,	'2026-10-15 04:31:45',	'2026-10-16 01:30:03'),
(9,	6,	10,	'1st Quality',	1000.00,	'2026-10-16 01:28:38',	'2026-11-06 13:55:47'),
(10,	3,	17,	'1st Quality',	1000.00,	'2026-10-16 04:04:43',	'2026-10-16 04:04:43');

DROP TABLE IF EXISTS `transaction_types`;
CREATE TABLE `transaction_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaction_types` (`id`, `name_en`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1,	'Cash Advance',	'Construction',	'Active',	'2026-11-20 16:44:40',	'2026-11-20 16:44:40');

DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(255) NOT NULL,
  `name_ta` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `usage` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_code_unique` (`code`),
  UNIQUE KEY `units_name_en_unique` (`name_en`),
  UNIQUE KEY `units_name_ta_unique` (`name_ta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `units` (`id`, `name_en`, `name_ta`, `code`, `usage`, `status`, `created_at`, `updated_at`) VALUES
(1,	'Bags',	'மூட்டை',	'BAG',	'Cement (50 Kg), Tile Adhesive',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(2,	'Kilograms (Kg)',	'கிலோகிராம்',	'KG',	'TMT 550D Steel, Binding Wire, Hardware Nails',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(3,	'Litres',	'லிட்டர்',	'LTR',	'Asian Paints, Exterior Primer, Chemical Cans',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(4,	'Load (Truck Load)',	'லோடு (2 யூனிட்)',	'LOAD',	'M-Sand, Plastering Sand, Blue Metal Aggregate',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(5,	'Brass (100 Cu.Ft)',	'பிராஸ்',	'BRASS',	'Basement Filling Soil, Quarry Dust',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(6,	'Nos / Pieces',	'எண்கள் / பீஸ்',	'NOS',	'Red Bricks (1000 Nos), PVC Pipes 10ft, Switches',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(7,	'Metric Ton (Ton)',	'டன்',	'TON',	'Bulk TMT Steel bundles',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 04:40:52'),
(8,	'Square Feet (Sq.Ft)',	'சதுர அடி',	'SQFT',	'Vitrified Floor Tiles, Granite Slabs',	'Active',	'2026-10-13 04:40:52',	'2026-11-02 07:24:33'),
(9,	'Running Feet (RFT)',	'நீள அடி',	'RFT',	'SS Balcony Handrails, PVC Drainage Line',	'Active',	'2026-10-13 04:40:52',	'2026-10-13 05:23:41'),
(10,	'Hours',	'மணிநேரங்கள்',	'HOUR',	'Rental Mixer Machine, Needle Vibrator, Scaffoldings',	'Active',	'2026-10-13 04:40:52',	'2026-10-22 04:31:13');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'site_manager',
  `phone` varchar(30) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `role_id`, `role`, `phone`, `status`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1,	'K. Deiveni',	'owner@deiveni.com',	1,	'owner',	'98400 11111',	'Active',	NULL,	'$2y$12$/JgKKUHvVWXYviSByvMtn.ZZvdAbdzAlJOz0NS4ggAGDlDpRSs04O',	'eCJGbOJjLiS13P6beQFCJoWkubkVz0pT98lRxvA3ihN1uKwIpezehO6zb8QA',	'2026-10-13 00:31:58',	'2026-10-28 07:39:45'),
(2,	'Murugan SM',	'sm@deiveni.com',	2,	'site_manager',	'98400 22222',	'Active',	NULL,	'$2y$12$arGzUwZGCUFRS3GWH3se6O/ksImZD.1fF6n0oQ3csIgBO2TOrpbWq',	'MKo8RfF4pke5Rm7eLhREu1IK3YZFidUJuIBXf5ysmryiC5aFX3iJa4c9WDoA',	'2026-10-13 00:31:58',	'2026-11-20 17:51:34'),
(3,	'Raja AM',	'am@deiveni.com',	3,	'account_manager',	'98400 33333',	'Active',	NULL,	'$2y$12$e8ek8GLd3ibliNiEbR8Jl.cdw.nyl/fvauJVVoTAYFJz9fYgNXzta',	'VoQ36SJ8Xyn9LF8cxpA0vpZ09IXk3qwv7mmklOzycSm8P8mbImEBpqPhpYP1',	'2026-10-13 00:31:58',	'2026-11-20 17:43:54'),
(8,	'syed',	'syed@deiveni.com',	2,	'site-manager',	'9658458754',	'Active',	NULL,	'$2y$12$IpqOCl.Ocxo.gWtJJILt3e4pi2M4efe3hO73n/0GgRHXLQmcbGhtC',	NULL,	'2026-11-12 03:16:36',	'2026-11-20 17:44:03'),
(9,	'Sivasakthi S',	'sivasakthi.saitech@gmail.com',	3,	'account-manager',	'9874563210',	'Active',	NULL,	'$2y$12$CVfaXD8jjaKxmb9sayMXKOSC6pmEGW9TWduX2/AkmKskrUMPXSaV.',	NULL,	'2026-11-20 16:36:02',	'2026-11-20 17:44:10');

DROP TABLE IF EXISTS `warehouse_items`;
CREATE TABLE `warehouse_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ta` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Raw Material',
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_threshold` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_items_sku_unique` (`sku`),
  KEY `warehouse_items_status_category_index` (`status`,`category`),
  KEY `warehouse_items_sku_index` (`sku`),
  KEY `warehouse_items_unit_id_foreign` (`unit_id`),
  CONSTRAINT `warehouse_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `warehouse_movements`;
CREATE TABLE `warehouse_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_item_id` bigint(20) unsigned NOT NULL,
  `movement_type` varchar(50) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `project_site_id` bigint(20) unsigned DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `movement_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_movements_project_site_id_foreign` (`project_site_id`),
  KEY `warehouse_movements_created_by_foreign` (`created_by`),
  KEY `warehouse_movements_warehouse_item_id_movement_type_index` (`warehouse_item_id`,`movement_type`),
  KEY `warehouse_movements_movement_date_index` (`movement_date`),
  CONSTRAINT `warehouse_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouse_movements_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouse_movements_warehouse_item_id_foreign` FOREIGN KEY (`warehouse_item_id`) REFERENCES `warehouse_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `weekly_wages`;
CREATE TABLE `weekly_wages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_site_id` bigint(20) unsigned NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `total_labour_man_days` int(11) NOT NULL DEFAULT 0,
  `total_labour_wages` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_daily_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_weekly_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_payout` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ww_site_week_unique` (`project_site_id`,`week_start_date`,`week_end_date`),
  KEY `weekly_wages_week_start_date_week_end_date_index` (`week_start_date`,`week_end_date`),
  KEY `weekly_wages_created_by_index` (`created_by`),
  CONSTRAINT `weekly_wages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `weekly_wages_project_site_id_foreign` FOREIGN KEY (`project_site_id`) REFERENCES `project_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `weekly_wages` (`id`, `project_site_id`, `week_start_date`, `week_end_date`, `total_labour_man_days`, `total_labour_wages`, `total_daily_expenses`, `total_weekly_expenses`, `net_payout`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	1,	'2026-11-16',	'2026-11-22',	0,	0.00,	0.00,	0.00,	0.00,	2,	'2026-11-20 17:52:07',	'2026-11-20 17:52:07'),
(2,	2,	'2026-11-16',	'2026-11-22',	23,	21200.00,	500.00,	0.00,	21700.00,	8,	'2026-11-20 18:24:35',	'2026-11-20 18:25:23');

DROP TABLE IF EXISTS `weekly_wage_expenses`;
CREATE TABLE `weekly_wage_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `weekly_wage_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weekly_wage_expenses_weekly_wage_id_index` (`weekly_wage_id`),
  CONSTRAINT `weekly_wage_expenses_weekly_wage_id_foreign` FOREIGN KEY (`weekly_wage_id`) REFERENCES `weekly_wages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `worker_categories`;
CREATE TABLE `worker_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ta` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `daily_wage` decimal(10,2) NOT NULL,
  `shift` varchar(255) NOT NULL DEFAULT '8 Hours (8:30 AM - 5:30 PM)',
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_categories_code_unique` (`code`),
  UNIQUE KEY `worker_categories_name_ta_unique` (`name_ta`),
  UNIQUE KEY `worker_categories_name_en_unique` (`name_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `worker_categories` (`id`, `name_ta`, `name_en`, `code`, `daily_wage`, `shift`, `status`, `created_at`, `updated_at`) VALUES
(1,	'மேஸ்திரி',	'Head Mason / Lead Mestri',	'CAT-001',	1200.00,	'8 Hours',	'Active',	'2026-10-13 03:45:01',	'2026-10-22 04:15:30'),
(2,	'கொத்தனார்',	'Brick Mason / Plasterer',	'CAT-002',	1200.00,	'8 Hours (8:30 AM - 5:30 PM)',	'Active',	'2026-10-13 03:45:01',	'2026-11-05 06:34:50'),
(3,	'சித்தாள் (ஆண்)',	'Male Labour Helper',	'CAT-003',	900.00,	'8 Hours (8:30 AM - 5:30 PM)',	'Active',	'2026-10-13 03:45:01',	'2026-10-13 03:45:01'),
(4,	'பெண் சித்தாள்',	'Female Labour Helper',	'CAT-004',	850.00,	'8 Hours (8:30 AM - 5:30 PM)',	'Active',	'2026-10-13 03:45:01',	'2026-10-13 03:45:01'),
(5,	'கம்பிகட்டுனர்',	'Bar Bender / Steel Worker',	'CAT-005',	1250.00,	'8 Hours (8:30 AM - 5:30 PM)',	'Active',	'2026-10-13 03:45:01',	'2026-10-13 03:45:01'),
(6,	'தச்சர் (சென்டரிங்)',	'Carpenter & Centering',	'CAT-006',	1350.00,	'8 Hours (9:00 AM - 3:00 PM)',	'Active',	'2026-10-13 03:45:01',	'2026-10-22 04:15:08'),
(7,	'பெயிண்டர்',	'Painter / Wall Finisher',	'CAT-007',	1100.00,	'6 Hours',	'Active',	'2026-10-13 03:45:01',	'2026-10-22 04:14:28'),
(8,	'எலக்ட்ரீஷியன் / பிளம்பர்',	'Electrician & Plumbing Tech',	'CAT-008',	1300.00,	'8 Hours',	'Active',	'2026-10-13 03:45:01',	'2026-10-22 04:10:19'),
(9,	'மின் கம்பிப் பணியாளர்',	'Electrical Wireman',	'CAT-009',	500.00,	'8 Hours',	'Active',	'2026-10-13 03:51:53',	'2026-10-22 02:50:54'),
(25,	'குளி தோண்டியத்து',	'foundation',	'CAT-010',	1100.00,	'8 Hours (8:30 AM - 5:30 PM)',	'Active',	'2026-11-19 09:04:41',	'2026-11-19 09:04:41');

-- 2026-11-20 06:26:50 UTC
