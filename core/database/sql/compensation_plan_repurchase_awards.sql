-- ============================================================================
-- WIIFARMVEST COMPENSATION PLAN: REPURCHASE (UNILEVEL) AWARDS (PAGE 7)
-- Cumulative personal/team repurchase PV thresholds & award payouts
-- ============================================================================

INSERT INTO `repurchase_awards` (`title`, `required_pv`, `amount`, `status`, `created_at`, `updated_at`)
VALUES
  ('Phone Award', 750.00, 50000.00, 1, NOW(), NOW()),
  ('Laptop Award', 1500.00, 100000.00, 1, NOW(), NOW()),
  ('Generator / Fridge Award', 3000.00, 200000.00, 1, NOW(), NOW()),
  ('International Trip Award', 6000.00, 400000.00, 1, NOW(), NOW()),
  ('Mini Car Award', 10000.00, 700000.00, 1, NOW(), NOW()),
  ('Car Award', 15000.00, 1200000.00, 1, NOW(), NOW()),
  ('SUV Award', 20000.00, 1800000.00, 1, NOW(), NOW()),
  ('Prime SUV Award', 25000.00, 2500000.00, 1, NOW(), NOW()),
  ('House Fund Award', 35000.00, 3500000.00, 1, NOW(), NOW()),
  ('Estate Fund Award', 45000.00, 5000000.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `amount` = VALUES(`amount`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();
