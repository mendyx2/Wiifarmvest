-- ============================================================================
-- Phase 3: Blueprint Compensation Feature Delivery SQL Migration
-- Wiifarmvest Compensation Remediations
-- Date: October 13, 2026
-- ============================================================================

-- 1. Add sponsor_user_id to stockists table (Track who introduced the stockist)
ALTER TABLE `stockists` 
ADD COLUMN IF NOT EXISTS `sponsor_user_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD INDEX IF NOT EXISTS `idx_stockists_sponsor_user_id` (`sponsor_user_id`);

-- 2. Add stockist_sponsor_bonus accumulator wallet to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `stockist_sponsor_bonus` DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER `stockist_rebate`;

-- 3. Create global_pool_ledgers table for 1.25% Strategic Reserve Accumulation & Quarterly Sharing
CREATE TABLE IF NOT EXISTS `global_pool_ledgers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `period` VARCHAR(20) NOT NULL COMMENT 'Quarterly period e.g. Q4-2026',
    `accumulated_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '1.25% PV volume pool accrued in Naira',
    `distributed_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total distributed to leaders',
    `qualified_count` INT NOT NULL DEFAULT 0 COMMENT 'Number of qualified top rank leaders sharing the pool',
    `status` VARCHAR(20) NOT NULL DEFAULT 'accumulating' COMMENT 'accumulating | distributed | closed',
    `distributed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_global_pool_period` (`period`),
    INDEX `idx_global_pool_status` (`status`),
    UNIQUE KEY `uk_global_pool_period_status` (`period`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Add carton packaging specifications to products table (Standard 15 Packs Per Carton)
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `carton_size` SMALLINT UNSIGNED NOT NULL DEFAULT 15 AFTER `quantity`,
ADD COLUMN IF NOT EXISTS `price_per_carton` DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER `carton_size`;
