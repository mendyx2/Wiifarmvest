-- ============================================================================
-- Phase 4: Financial Governance & Pool Separation SQL Migration
-- Wiifarmvest Compensation Remediations
-- Date: October 13, 2026
-- ============================================================================

-- Create company_pool_ledgers table for real-time 4-pool revenue allocation
CREATE TABLE IF NOT EXISTS `company_pool_ledgers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trx` VARCHAR(40) NOT NULL COMMENT 'Transaction reference',
    `source_type` VARCHAR(50) NOT NULL COMMENT 'deposit | registration | upgrade | order | compensation_payout',
    `description` VARCHAR(255) NULL COMMENT 'Human-readable narration',
    `gross_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Gross amount transacted',
    
    -- Split allocations for entry
    `company_reserve_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '30.00% Company Reserve allocation',
    `executive_pool_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '10.00% Executive Leadership Pool allocation',
    `operational_pool_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '5.00% Operational Expenses Pool allocation',
    `compensation_reserve_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT '55.00% Member Compensation Reserve allocation',
    
    -- Running balances post-transaction
    `company_reserve_balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance: 30% Company Reserve',
    `executive_pool_balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance: 10% Executive Pool',
    `operational_pool_balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance: 5% Operational Pool',
    `compensation_reserve_balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance: 55% Compensation Reserve',
    
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_cpl_trx` (`trx`),
    INDEX `idx_cpl_source` (`source_type`),
    INDEX `idx_cpl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
