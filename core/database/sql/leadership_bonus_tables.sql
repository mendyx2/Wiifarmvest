-- =====================================================================
-- WIIFARMVEST PLATFORM: LEADERSHIP BONUS TABLES & COLUMNS
-- Blueprint Compliance: 4.00% Leadership Bonus Engine
-- =====================================================================

-- 1. Leadership Settings Table
CREATE TABLE IF NOT EXISTS `leadership_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `required_pv` decimal(12,2) NOT NULL DEFAULT '1000.00',
  `percentage` decimal(5,2) NOT NULL DEFAULT '4.00',
  `max_levels` int(11) NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `enabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default setting (4.00% matching leadership, 1000 PV threshold, 5 levels)
INSERT INTO `leadership_settings` (`id`, `required_pv`, `percentage`, `max_levels`, `is_active`, `enabled_at`, `created_at`, `updated_at`)
SELECT 1, 1000.00, 4.00, 5, 1, NOW(), NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leadership_settings` WHERE `id` = 1);

-- 2. Leadership Leaders Table
CREATE TABLE IF NOT EXISTS `leadership_leaders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `pv_left` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pv_right` decimal(12,2) NOT NULL DEFAULT '0.00',
  `required_pv` decimal(12,2) NOT NULL DEFAULT '1000.00',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `qualified_at` timestamp NULL DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leadership_leaders_user_id_unique` (`user_id`),
  KEY `leadership_leaders_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Leadership Bonus Logs Table
CREATE TABLE IF NOT EXISTS `leadership_bonus_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leader_id` bigint(20) unsigned NOT NULL,
  `source_user_id` bigint(20) unsigned NOT NULL,
  `matching_bonus_log_id` bigint(20) unsigned DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT '1',
  `leg` varchar(10) DEFAULT NULL,
  `matching_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `percentage` decimal(5,2) NOT NULL DEFAULT '4.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `trx` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leadership_bonus_logs_leader_id_index` (`leader_id`),
  KEY `leadership_bonus_logs_source_user_id_index` (`source_user_id`),
  KEY `leadership_bonus_logs_matching_bonus_log_id_index` (`matching_bonus_log_id`),
  KEY `leadership_bonus_logs_trx_index` (`trx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Matching Bonus Logs: add leadership_processed_at column
ALTER TABLE `matching_bonus_logs`
ADD COLUMN IF NOT EXISTS `leadership_processed_at` timestamp NULL DEFAULT NULL AFTER `processed_at`;
