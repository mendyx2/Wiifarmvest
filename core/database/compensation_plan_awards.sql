-- ============================================================================
-- Proposed Compensation Plan: 13 Binary Recognition Awards SQL Seed
-- Pages 4 & 5 of Proposed Plan
-- Date: October 14, 2026
-- ============================================================================

INSERT INTO `awards` (`id`, `name`, `description`, `required_total_pv`, `required_left_pv`, `required_right_pv`, `prerequisite_award_id`, `payment_amount`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
(1, 'Wiifarm-Emerald', '₦80,000.00 cash gift only (Builder package requirement)', 3000.00, 1500.00, 1500.00, NULL, 80000.00, 1, 1, NOW(), NOW()),
(2, 'Wiifarm-Silver', '₦150,000.00 cash gift only (Booster package requirement)', 6000.00, 3000.00, 3000.00, NULL, 150000.00, 2, 1, NOW(), NOW()),
(3, 'Wiifarm-Jade', '₦300,000.00 cash gift only (Executive package requirement)', 12000.00, 6000.00, 6000.00, NULL, 300000.00, 3, 1, NOW(), NOW()),
(4, 'Wiifarm-Gold', '₦550,000.00 cash or Laptop (Mega + Jade L&R requirement)', 25000.00, 12500.00, 12500.00, 3, 550000.00, 4, 1, NOW(), NOW()),
(5, 'Wiifarm-Diamond', '₦1,200,000.00 cash or Smart TV (Mega + Gold L&R requirement)', 50000.00, 25000.00, 25000.00, 4, 1200000.00, 5, 1, NOW(), NOW()),
(6, 'Wiifarm-Executive Diamond', '₦1,500,000.00 or Solar Freezer & Washer (Mega + Diamond L&R)', 70000.00, 35000.00, 35000.00, 5, 1500000.00, 6, 1, NOW(), NOW()),
(7, 'Wiifarm-Platinum', '₦4,000,000.00 or Solar system (Mega + Exec Diamond L&R)', 160000.00, 80000.00, 80000.00, 6, 4000000.00, 7, 1, NOW(), NOW()),
(8, 'Wiifarm-Premium Platinum', '₦10,000,000.00 cash or Small Car (Mega + Platinum L&R)', 300000.00, 150000.00, 150000.00, 7, 10000000.00, 8, 1, NOW(), NOW()),
(9, 'Wiifarm-Ambassador', '₦16,000,000.00 cash or Trip (Mega + Premium Platinum L&R)', 600000.00, 300000.00, 300000.00, 8, 16000000.00, 9, 1, NOW(), NOW()),
(10, 'Wiifarm-Executive Ambassador', '₦25,000,000.00 cash or Big Car (Mega + Ambassador L&R)', 900000.00, 450000.00, 450000.00, 9, 25000000.00, 10, 1, NOW(), NOW()),
(11, 'Wiifarm-Sapphire', '₦40,000,000.00 cash or Land (Mega + Exec Ambassador L&R)', 1600000.00, 800000.00, 800000.00, 10, 40000000.00, 11, 1, NOW(), NOW()),
(12, 'Wiifarm-Executive Sapphire', '₦100,000,000.00 cash or Bungalow (Mega + Sapphire L&R)', 5000000.00, 2500000.00, 2500000.00, 11, 100000000.00, 12, 1, NOW(), NOW()),
(13, 'Wiifarm-Global Mega', '₦180,000,000.00 cash or Duplex (Mega + Exec Sapphire L&R)', 10000000.00, 5000000.00, 5000000.00, 12, 180000000.00, 13, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`description` = VALUES(`description`),
`required_total_pv` = VALUES(`required_total_pv`),
`required_left_pv` = VALUES(`required_left_pv`),
`required_right_pv` = VALUES(`required_right_pv`),
`prerequisite_award_id` = VALUES(`prerequisite_award_id`),
`payment_amount` = VALUES(`payment_amount`),
`sort_order` = VALUES(`sort_order`),
`status` = VALUES(`status`),
`updated_at` = NOW();
