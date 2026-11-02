-- ============================================================================
-- Proposed Compensation Plan: 7 Registration Packages SQL Seed
-- 1 PV = ₦600 | Direct Referral = 25% of PV | Step Matching = 50 PV
-- Date: October 14, 2026
-- ============================================================================

INSERT INTO `projects` (`title`, `slug`, `amount`, `pv`, `direct_commission`, `pairing_per_day`, `max_pairing_slots`, `cash_back`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
('Wiifarm-Pre Starter', 'wiifarm-pre-starter', 15000.00, 15.00, 2250.00, 900.00, 5, 0.00, 1, 1, NOW(), NOW()),
('Wiifarm-Starter', 'wiifarm-starter', 30000.00, 30.00, 4500.00, 1500.00, 7, 0.00, 2, 1, NOW(), NOW()),
('Wiifarm-Builder', 'wiifarm-builder', 60000.00, 60.00, 9000.00, 2100.00, 10, 0.00, 3, 1, NOW(), NOW()),
('Wiifarm-Booster', 'wiifarm-booster', 105000.00, 105.00, 15750.00, 2400.00, 30, 0.00, 4, 1, NOW(), NOW()),
('Wiifarm-Executive', 'wiifarm-executive', 150000.00, 150.00, 22500.00, 2700.00, 60, 0.00, 5, 1, NOW(), NOW()),
('Wiifarm-Advance', 'wiifarm-advance', 255000.00, 255.00, 38250.00, 3000.00, 90, 0.00, 6, 1, NOW(), NOW()),
('Wiifarm-Mega', 'wiifarm-mega', 450000.00, 450.00, 67500.00, 3600.00, 120, 0.00, 7, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
`title` = VALUES(`title`),
`amount` = VALUES(`amount`),
`pv` = VALUES(`pv`),
`direct_commission` = VALUES(`direct_commission`),
`pairing_per_day` = VALUES(`pairing_per_day`),
`max_pairing_slots` = VALUES(`max_pairing_slots`),
`sort_order` = VALUES(`sort_order`),
`status` = VALUES(`status`),
`updated_at` = NOW();
