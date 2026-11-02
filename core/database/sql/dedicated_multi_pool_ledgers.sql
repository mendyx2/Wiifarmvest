-- ============================================================================
-- Dedicated Multi-Ledger Financial Governance Architecture
-- Wiifarmvest Platform
-- Date: October 14, 2026
-- ============================================================================

-- 1. Company Reserve Dedicated Ledger (30.00% Statutory Return)
CREATE TABLE IF NOT EXISTS `company_reserve_ledgers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trx` VARCHAR(40) NOT NULL COMMENT 'Transaction reference',
    `source_type` VARCHAR(50) NOT NULL COMMENT 'deposit | registration | upgrade | order',
    `credit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '30% inflow credited in Naira',
    `debit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Outflow/disbursement in Naira',
    `balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance of Company Reserve',
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_crl_trx` (`trx`),
    INDEX `idx_crl_source` (`source_type`),
    INDEX `idx_crl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Executive Leadership Pool Dedicated Ledger (10.00%)
CREATE TABLE IF NOT EXISTS `executive_pool_ledgers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trx` VARCHAR(40) NOT NULL COMMENT 'Transaction reference',
    `source_type` VARCHAR(50) NOT NULL,
    `credit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '10% inflow credited in Naira',
    `debit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    `balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance of Executive Pool',
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_epl_trx` (`trx`),
    INDEX `idx_epl_source` (`source_type`),
    INDEX `idx_epl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Operational Expenses Pool Dedicated Ledger (5.00%)
CREATE TABLE IF NOT EXISTS `operational_pool_ledgers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trx` VARCHAR(40) NOT NULL COMMENT 'Transaction reference',
    `source_type` VARCHAR(50) NOT NULL,
    `credit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '5% inflow credited in Naira',
    `debit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    `balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance of Operational Budget',
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_opl_trx` (`trx`),
    INDEX `idx_opl_source` (`source_type`),
    INDEX `idx_opl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Member Compensation Reserve Dedicated Ledger (55.00%)
CREATE TABLE IF NOT EXISTS `compensation_reserve_ledgers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trx` VARCHAR(40) NOT NULL COMMENT 'Transaction reference',
    `source_type` VARCHAR(50) NOT NULL COMMENT 'allocation | compensation_payout',
    `sub_pool` VARCHAR(20) NOT NULL DEFAULT 'CRP' COMMENT 'CRP (22%) | CP (24.75%) | SR (8.25%) | ALLOCATION',
    `bonus_type` VARCHAR(50) NULL COMMENT 'direct_bonus | indirect_bonus | binary_matching | upgrade_bonus | etc.',
    `user_id` BIGINT UNSIGNED NULL COMMENT 'Recipient member user id',
    `credit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '55% reserve inflow credited in Naira',
    `debit` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Member commission disbursed in Naira',
    `balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance of Compensation Reserve',
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cresl_trx` (`trx`),
    INDEX `idx_cresl_source` (`source_type`),
    INDEX `idx_cresl_sub_pool` (`sub_pool`),
    INDEX `idx_cresl_bonus` (`bonus_type`),
    INDEX `idx_cresl_user` (`user_id`),
    INDEX `idx_cresl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
