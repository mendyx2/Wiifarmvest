-- ============================================================================
-- Proposed Compensation Plan: 15 Unilevel Generations SQL Seed
-- Page 6 of Proposed Plan
-- Date: October 14, 2026
-- ============================================================================

INSERT INTO `unilevel_generations` (`number`, `title`, `percentage`, `description`, `status`, `created_at`, `updated_at`)
VALUES
(1, '1st Generation', 10.0000, '10% on 1st Generation repurchase volume', 1, NOW(), NOW()),
(2, '2nd Generation', 7.0000, '7% on 2nd Generation repurchase volume', 1, NOW(), NOW()),
(3, '3rd Generation', 5.0000, '5% on 3rd Generation repurchase volume', 1, NOW(), NOW()),
(4, '4th Generation', 4.0000, '4% on 4th Generation repurchase volume', 1, NOW(), NOW()),
(5, '5th Generation', 4.0000, '4% on 5th Generation repurchase volume', 1, NOW(), NOW()),
(6, '6th Generation', 3.0000, '3% on 6th Generation repurchase volume', 1, NOW(), NOW()),
(7, '7th Generation', 3.0000, '3% on 7th Generation repurchase volume', 1, NOW(), NOW()),
(8, '8th Generation', 3.0000, '3% on 8th Generation repurchase volume', 1, NOW(), NOW()),
(9, '9th Generation', 2.0000, '2% on 9th Generation repurchase volume', 1, NOW(), NOW()),
(10, '10th Generation', 2.0000, '2% on 10th Generation repurchase volume', 1, NOW(), NOW()),
(11, '11th Generation', 2.0000, '2% on 11th Generation repurchase volume', 1, NOW(), NOW()),
(12, '12th Generation', 1.0000, '1% on 12th Generation repurchase volume', 1, NOW(), NOW()),
(13, '13th Generation', 1.0000, '1% on 13th Generation repurchase volume', 1, NOW(), NOW()),
(14, '14th Generation', 1.0000, '1% on 14th Generation repurchase volume', 1, NOW(), NOW()),
(15, '15th Generation', 1.0000, '1% on 15th Generation repurchase volume', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
`title` = VALUES(`title`),
`percentage` = VALUES(`percentage`),
`description` = VALUES(`description`),
`status` = VALUES(`status`),
`updated_at` = NOW();
