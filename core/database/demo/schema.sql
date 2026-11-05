-- Wiifarmvest demo database: the tables (MySQL 8 / MariaDB 10.6+).
-- Loaded by `php artisan demo:install` (App\Console\Commands\InstallDemo); do not run on a live database.
-- The tables the migrations create (leadership, pools, USDT) are left to `migrate`.
-- Generated from the test schema the pages were checked against (notes Step 129).

CREATE TABLE IF NOT EXISTS `acb_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `added_by` BIGINT NULL,
  `notes` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ad_shares` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` BIGINT NULL,
  `user_id` BIGINT NULL,
  `channel` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_action_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` BIGINT NULL,
  `admin_name` VARCHAR(255) NULL,
  `admin_username` VARCHAR(255) NULL,
  `admin_role` VARCHAR(255) NULL,
  `method` VARCHAR(255) NULL,
  `route_name` VARCHAR(255) NULL,
  `uri` MEDIUMTEXT NULL,
  `action` VARCHAR(255) NULL,
  `subject_type` VARCHAR(255) NULL,
  `subject_id` VARCHAR(255) NULL,
  `description` MEDIUMTEXT NULL,
  `meta` MEDIUMTEXT NULL,
  `ip_address` VARCHAR(255) NULL,
  `user_agent` MEDIUMTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_autoship` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `month` VARCHAR(255) NULL,
  `swept_at` DATETIME NULL,
  `trx` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `title` MEDIUMTEXT NULL,
  `click_url` MEDIUMTEXT NULL,
  `is_read` BIGINT NULL DEFAULT 0,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_password_resets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NULL,
  `token` VARCHAR(255) NULL,
  `status` BIGINT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `username` MEDIUMTEXT NULL,
  `email` VARCHAR(255) NULL,
  `password` VARCHAR(255) NULL,
  `created_at` VARCHAR(255) NULL,
  `updated_at` VARCHAR(255) NULL,
  `image` VARCHAR(255) NULL,
  `remember_token` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NULL,
  `caption` MEDIUMTEXT NULL,
  `media_type` VARCHAR(255) NULL DEFAULT 'image',
  `media` VARCHAR(255) NULL,
  `poster` VARCHAR(255) NULL,
  `product_id` BIGINT NULL,
  `is_active` TINYINT(1) NULL DEFAULT 1,
  `sort_order` BIGINT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_clicks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `affiliate_user_id` BIGINT NULL,
  `ad_id` BIGINT NULL,
  `session_token` VARCHAR(255) NULL,
  `ip_address` VARCHAR(255) NULL,
  `user_agent` MEDIUMTEXT NULL,
  `referer` MEDIUMTEXT NULL,
  `landing_url` MEDIUMTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `affiliate_order_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `product_name` MEDIUMTEXT NULL,
  `quantity` BIGINT NULL,
  `unit_price` DECIMAL(28,8) NULL,
  `line_total` DECIMAL(28,8) NULL,
  `bonus_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` MEDIUMTEXT NULL,
  `affiliate_user_id` BIGINT NULL,
  `affiliate_click_id` BIGINT NULL,
  `buyer_name` MEDIUMTEXT NULL,
  `buyer_email` MEDIUMTEXT NULL,
  `buyer_phone` MEDIUMTEXT NULL,
  `state_id` BIGINT NULL,
  `payment_method` MEDIUMTEXT NULL,
  `status` MEDIUMTEXT NULL,
  `subtotal` DECIMAL(28,8) NULL,
  `total_amount` DECIMAL(28,8) NULL,
  `paystack_reference` MEDIUMTEXT NULL,
  `bonus_credited` BIGINT NULL DEFAULT 0,
  `redeemed_by_stockist_id` BIGINT NULL,
  `redeemed_at` MEDIUMTEXT NULL,
  `ip_address` MEDIUMTEXT NULL,
  `user_agent` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cash_on_pickup_enabled` BIGINT NULL DEFAULT 1,
  `cookie_days` BIGINT NULL DEFAULT 30,
  `pending_order_expiry_days` BIGINT NULL DEFAULT 7,
  `stockist_pickup_fee_type` VARCHAR(255) NULL,
  `stockist_pickup_fee_value` DECIMAL(28,8) NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `paystack_enabled` TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `awards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `description` MEDIUMTEXT NULL,
  `required_total_pv` DECIMAL(28,8) NULL,
  `required_left_pv` DECIMAL(28,8) NULL,
  `required_right_pv` DECIMAL(28,8) NULL,
  `payment_amount` DECIMAL(28,8) NULL,
  `prerequisite_award_id` BIGINT NULL,
  `image` MEDIUMTEXT NULL,
  `sort_order` BIGINT NULL,
  `status` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_detail_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `bname` MEDIUMTEXT NULL,
  `aname` MEDIUMTEXT NULL,
  `ano` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bv_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL DEFAULT 0,
  `position` BIGINT NULL DEFAULT 1,
  `trx_type` VARCHAR(255) NULL DEFAULT '+',
  `details` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `product_state_id` BIGINT NULL,
  `quantity` BIGINT NULL,
  `price` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `carts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `total_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `status` BIGINT NULL DEFAULT 1,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cron_job_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cron_job_id` BIGINT NULL,
  `start_at` DATETIME NULL,
  `end_at` DATETIME NULL,
  `duration` BIGINT NULL DEFAULT 0,
  `error` MEDIUMTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cron_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `alias` VARCHAR(255) NULL,
  `action` MEDIUMTEXT NULL,
  `url` VARCHAR(255) NULL,
  `cron_schedule_id` BIGINT NULL,
  `next_run` DATETIME NULL,
  `last_run` DATETIME NULL,
  `is_running` BIGINT NULL DEFAULT 1,
  `is_default` BIGINT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cron_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `interval` BIGINT NULL,
  `status` BIGINT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deposits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `method_code` BIGINT NULL,
  `method_currency` MEDIUMTEXT NULL,
  `amount` DECIMAL(28,8) NULL,
  `charge` DECIMAL(28,8) NULL,
  `rate` DECIMAL(28,8) NULL,
  `final_amount` DECIMAL(28,8) NULL,
  `trx` MEDIUMTEXT NULL,
  `status` BIGINT NULL DEFAULT 0,
  `detail` MEDIUMTEXT NULL,
  `admin_feedback` MEDIUMTEXT NULL,
  `btc_amount` DECIMAL(28,8) NULL,
  `btc_wallet` MEDIUMTEXT NULL,
  `success_url` MEDIUMTEXT NULL,
  `failed_url` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `is_app` BIGINT NULL,
  `token` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dividend_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by_id` BIGINT NULL,
  `amount_per_unit` DECIMAL(28,8) NULL,
  `scope` VARCHAR(255) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `plan_id` BIGINT NULL,
  `status` VARCHAR(255) NULL,
  `total_users` BIGINT NULL DEFAULT 0,
  `total_units` DECIMAL(28,8) NULL DEFAULT 0,
  `total_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `processed_count` BIGINT NULL DEFAULT 0,
  `failed_count` BIGINT NULL DEFAULT 0,
  `cancelled_at` DATETIME NULL,
  `cancelled_by_id` BIGINT NULL,
  `reversed_at` DATETIME NULL,
  `reversed_by_id` BIGINT NULL,
  `notes` MEDIUMTEXT NULL,
  `metadata` MEDIUMTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `extensions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `act` VARCHAR(255) NULL,
  `script` MEDIUMTEXT NULL,
  `shortcode` MEDIUMTEXT NULL,
  `status` BIGINT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `name` VARCHAR(255) NULL,
  `image` VARCHAR(255) NULL,
  `description` MEDIUMTEXT NULL,
  `support` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `farm_cycles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `min_contribution` DECIMAL(28,8) NULL DEFAULT 0,
  `max_contribution` DECIMAL(28,8) NULL,
  `min_yield` DECIMAL(28,8) NULL DEFAULT 0,
  `max_yield` DECIMAL(28,8) NULL DEFAULT 0,
  `actual_yield` DECIMAL(28,8) NULL,
  `subscription_opens_at` DATE NULL,
  `subscription_closes_at` DATE NULL,
  `maturity_date` DATE NULL,
  `status` VARCHAR(255) NULL DEFAULT 'open',
  `payout_processed` TINYINT(1) NULL DEFAULT 0,
  `matured_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flashcard_views` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `flashcard_id` BIGINT NULL,
  `viewed_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flashcards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NULL,
  `description` MEDIUMTEXT NULL,
  `type` VARCHAR(255) NULL,
  `file` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `act` MEDIUMTEXT NULL,
  `form_data` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `frontends` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `data_keys` VARCHAR(255) NULL,
  `data_values` MEDIUMTEXT NULL,
  `seo_content` MEDIUMTEXT NULL,
  `tempname` VARCHAR(255) NULL,
  `slug` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gateway_currencies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `currency` MEDIUMTEXT NULL,
  `symbol` MEDIUMTEXT NULL,
  `method_code` BIGINT NULL,
  `gateway_alias` MEDIUMTEXT NULL,
  `min_amount` DECIMAL(28,8) NULL,
  `max_amount` DECIMAL(28,8) NULL,
  `percent_charge` DECIMAL(28,8) NULL,
  `fixed_charge` DECIMAL(28,8) NULL,
  `rate` DECIMAL(28,8) NULL,
  `gateway_parameter` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gateways` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` BIGINT NULL,
  `code` BIGINT NULL,
  `name` MEDIUMTEXT NULL,
  `alias` MEDIUMTEXT NULL,
  `image` MEDIUMTEXT NULL,
  `status` BIGINT NULL,
  `crypto` BIGINT NULL DEFAULT 0,
  `description` MEDIUMTEXT NULL,
  `gateway_parameters` MEDIUMTEXT NULL,
  `extra` MEDIUMTEXT NULL,
  `input_form` MEDIUMTEXT NULL,
  `supported_currencies` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_name` MEDIUMTEXT NULL,
  `active_template` MEDIUMTEXT NULL,
  `secure_password` BIGINT NULL,
  `base_color` MEDIUMTEXT NULL,
  `secondary_color` MEDIUMTEXT NULL,
  `paginate_number` BIGINT NULL,
  `cur_sym` MEDIUMTEXT NULL,
  `cur_text` MEDIUMTEXT NULL,
  `currency_format` BIGINT NULL,
  `en` BIGINT NULL DEFAULT 0,
  `sn` BIGINT NULL DEFAULT 0,
  `pn` BIGINT NULL DEFAULT 0,
  `mail_config` MEDIUMTEXT NULL,
  `sms_config` MEDIUMTEXT NULL,
  `email_template` MEDIUMTEXT NULL,
  `sms_template` MEDIUMTEXT NULL,
  `email_from` VARCHAR(255) NULL,
  `email_from_name` VARCHAR(255) NULL,
  `sms_from` VARCHAR(255) NULL,
  `push_template` MEDIUMTEXT NULL,
  `push_title` VARCHAR(255) NULL,
  `firebase_config` MEDIUMTEXT NULL,
  `bal_trans_fixed_charge` DECIMAL(28,8) NULL DEFAULT 0,
  `bal_trans_per_charge` DECIMAL(28,8) NULL DEFAULT 0,
  `notice` MEDIUMTEXT NULL,
  `free_user_notice` MEDIUMTEXT NULL,
  `bv_price` DECIMAL(28,8) NULL DEFAULT 0,
  `total_bv` BIGINT NULL DEFAULT 0,
  `max_bv` BIGINT NULL DEFAULT 0,
  `cary_flash` BIGINT NULL DEFAULT 0,
  `matching_bonus_time` VARCHAR(255) NULL,
  `matching_when` VARCHAR(255) NULL,
  `maintenance_mode` BIGINT NULL DEFAULT 0,
  `multi_language` BIGINT NULL DEFAULT 0,
  `agree` BIGINT NULL DEFAULT 0,
  `force_ssl` BIGINT NULL DEFAULT 0,
  `registration` BIGINT NULL DEFAULT 0,
  `last_paid` VARCHAR(255) NULL,
  `kv` BIGINT NULL DEFAULT 0,
  `ev` BIGINT NULL DEFAULT 0,
  `sv` BIGINT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `global_shortcodes` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `guarantor_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `guarantor_id` BIGINT NULL,
  `status` MEDIUMTEXT NULL,
  `responded_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_redemptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` BIGINT NULL,
  `stockist_id` BIGINT NULL,
  `user_id` BIGINT NULL,
  `redeemed_items` MEDIUMTEXT NULL,
  `total_amount` DECIMAL(28,8) NULL,
  `notes` MEDIUMTEXT NULL,
  `redeemed_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT NULL,
  `invoice_code` MEDIUMTEXT NULL,
  `state_id` BIGINT NULL,
  `items` MEDIUMTEXT NULL,
  `total_amount` DECIMAL(28,8) NULL,
  `redeemed_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `is_fully_redeemed` BIGINT NULL DEFAULT 0,
  `fully_redeemed_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `languages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `code` VARCHAR(255) NULL,
  `image` VARCHAR(255) NULL,
  `is_default` BIGINT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `description` VARCHAR(255) NULL,
  `interest_rate` DECIMAL(28,8) NULL,
  `min_loan_amount` DECIMAL(28,8) NULL,
  `max_loan_amount` DECIMAL(28,8) NULL,
  `savings_multiple` DECIMAL(28,8) NULL,
  `min_savings_threshold` DECIMAL(28,8) NULL,
  `min_membership_days` BIGINT NULL,
  `tenure_options` MEDIUMTEXT NULL,
  `auto_approve` TINYINT(1) NULL DEFAULT 0,
  `is_active` TINYINT(1) NULL DEFAULT 1,
  `created_by` BIGINT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_repayments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` BIGINT NULL,
  `user_id` BIGINT NULL,
  `reference` VARCHAR(255) NULL,
  `amount` DECIMAL(28,8) NULL,
  `source` VARCHAR(255) NULL DEFAULT 'auto',
  `outstanding_after` DECIMAL(28,8) NULL DEFAULT 0,
  `status` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` BIGINT NULL,
  `installment_number` BIGINT NULL,
  `due_date` DATE NULL,
  `principal` DECIMAL(28,8) NULL,
  `interest` DECIMAL(28,8) NULL,
  `total` DECIMAL(28,8) NULL,
  `amount_paid` DECIMAL(28,8) NULL DEFAULT 0,
  `status` VARCHAR(255) NULL DEFAULT 'pending',
  `paid_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `loan_product_id` BIGINT NULL,
  `reference` VARCHAR(255) NULL,
  `status` VARCHAR(255) NULL,
  `purpose` VARCHAR(255) NULL,
  `original_amount` DECIMAL(28,8) NULL,
  `interest_rate` DECIMAL(28,8) NULL,
  `total_interest` DECIMAL(28,8) NULL DEFAULT 0,
  `total_repayable` DECIMAL(28,8) NULL DEFAULT 0,
  `outstanding` DECIMAL(28,8) NULL DEFAULT 0,
  `total_repaid` DECIMAL(28,8) NULL DEFAULT 0,
  `tenure_months` BIGINT NULL,
  `tenure_unit` VARCHAR(255) NULL DEFAULT 'month',
  `monthly_installment` DECIMAL(28,8) NULL DEFAULT 0,
  `installments_paid` BIGINT NULL DEFAULT 0,
  `next_due_date` DATE NULL,
  `savings_balance_at_application` DECIMAL(28,8) NULL DEFAULT 0,
  `approved_at` DATETIME NULL,
  `disbursed_at` DATETIME NULL,
  `cleared_at` DATETIME NULL,
  `defaulted_at` DATETIME NULL,
  `approved_by` BIGINT NULL,
  `rejection_reason` MEDIUMTEXT NULL,
  `metadata` MEDIUMTEXT NULL,
  `repayment_priority` TINYINT(1) NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `matching_bonus_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `matrix_id` BIGINT NULL,
  `project_id` BIGINT NULL,
  `matches` BIGINT NULL,
  `pv_per_match` DECIMAL(28,8) NULL DEFAULT 0,
  `bonus_per_match` DECIMAL(28,8) NULL DEFAULT 0,
  `total_bonus` DECIMAL(28,8) NULL DEFAULT 0,
  `autoship_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_left_before` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_right_before` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_left_after` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_right_after` DECIMAL(28,8) NULL DEFAULT 0,
  `autoship_fulfilled` TINYINT(1) NULL DEFAULT 0,
  `trx` VARCHAR(255) NULL,
  `processed_at` DATETIME NULL,
  `leadership_processed_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `matrices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `stage_id` BIGINT NULL,
  `parent_id` BIGINT NULL,
  `left` BIGINT NULL DEFAULT 0,
  `right` BIGINT NULL DEFAULT 0,
  `is_active` BIGINT NULL DEFAULT 1,
  `pv_left` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_right` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_left_pairing` DECIMAL(28,8) NULL DEFAULT 0,
  `pv_right_pairing` DECIMAL(28,8) NULL DEFAULT 0,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `position` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `matrix_stages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `level` BIGINT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` BIGINT NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  KEY `model_has_permissions_model_id_model_type` (`model_id`, `model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` BIGINT NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  KEY `model_has_roles_model_id_model_type` (`model_id`, `model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `sent_by_admin_id` BIGINT NULL,
  `subject` MEDIUMTEXT NULL,
  `message` MEDIUMTEXT NULL,
  `notification_type` MEDIUMTEXT NULL,
  `user_read` BIGINT NULL,
  `is_admin_notice` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `sender` MEDIUMTEXT NULL,
  `sent_from` MEDIUMTEXT NULL,
  `sent_to` MEDIUMTEXT NULL,
  `image` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `act` MEDIUMTEXT NULL,
  `name` MEDIUMTEXT NULL,
  `subject` MEDIUMTEXT NULL,
  `email_body` MEDIUMTEXT NULL,
  `sms_body` MEDIUMTEXT NULL,
  `push_title` MEDIUMTEXT NULL,
  `push_body` MEDIUMTEXT NULL,
  `shortcodes` MEDIUMTEXT NULL,
  `email_status` BIGINT NULL,
  `sms_status` BIGINT NULL,
  `push_status` BIGINT NULL,
  `email_sent_from_name` MEDIUMTEXT NULL,
  `email_sent_from_address` MEDIUMTEXT NULL,
  `sms_sent_from` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `quantity` BIGINT NULL,
  `price` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `state_id` BIGINT NULL,
  `quantity` BIGINT NULL,
  `price` DECIMAL(28,8) NULL,
  `total_price` DECIMAL(28,8) NULL,
  `trx` MEDIUMTEXT NULL,
  `order_code` MEDIUMTEXT NULL,
  `invoice_code` MEDIUMTEXT NULL,
  `total_amount` DECIMAL(28,8) NULL,
  `stockist_user_id` BIGINT NULL,
  `status` BIGINT NULL DEFAULT 0,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `slug` VARCHAR(255) NULL,
  `tempname` VARCHAR(255) NULL,
  `secs` MEDIUMTEXT NULL,
  `seo_content` MEDIUMTEXT NULL,
  `is_default` BIGINT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NULL,
  `token` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `guard_name` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `group` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL DEFAULT 0,
  `generate_user_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL DEFAULT 0,
  `pin` VARCHAR(255) NULL,
  `details` VARCHAR(255) NULL,
  `status` BIGINT NULL DEFAULT 0,
  `stats` BIGINT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `price` DECIMAL(28,8) NULL,
  `quantity` BIGINT NULL,
  `status` BIGINT NULL DEFAULT 1,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `ref_com` DECIMAL(28,8) NULL DEFAULT 0,
  `lev2` DECIMAL(28,8) NULL DEFAULT 0,
  `lev3` DECIMAL(28,8) NULL DEFAULT 0,
  `lev4` DECIMAL(28,8) NULL DEFAULT 0,
  `bv` DECIMAL(28,8) NULL DEFAULT 0,
  `tree_com` DECIMAL(28,8) NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT NULL,
  `name` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_state_prices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT NULL,
  `state_id` BIGINT NULL,
  `price` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `mega_stockist_bonus` DECIMAL(28,8) NULL DEFAULT 0,
  `micro_stockist_bonus` DECIMAL(28,8) NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT NULL,
  `name` MEDIUMTEXT NULL,
  `slug` MEDIUMTEXT NULL,
  `price` DECIMAL(28,8) NULL,
  `quantity` BIGINT NULL,
  `thumbnail` MEDIUMTEXT NULL,
  `description` MEDIUMTEXT NULL,
  `specification` MEDIUMTEXT NULL,
  `meta_title` MEDIUMTEXT NULL,
  `meta_keyword` MEDIUMTEXT NULL,
  `meta_description` MEDIUMTEXT NULL,
  `status` BIGINT NULL DEFAULT 1,
  `pv` DECIMAL(28,8) NULL DEFAULT 0,
  `bv` DECIMAL(28,8) NULL DEFAULT 0,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `sku` MEDIUMTEXT NULL,
  `specifications` MEDIUMTEXT NULL,
  `min_order_quantity` BIGINT NULL DEFAULT 1,
  `max_order_quantity` BIGINT NULL DEFAULT 0,
  `is_featured` BIGINT NULL DEFAULT 0,
  `selling_price` DECIMAL(28,8) NULL,
  `affiliate_bonus_type` VARCHAR(255) NULL,
  `affiliate_bonus_value` DECIMAL(28,8) NULL,
  `prb` DECIMAL(28,8) NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_unilevel_generation` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT NULL,
  `generation_id` BIGINT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` MEDIUMTEXT NULL,
  `slug` MEDIUMTEXT NULL,
  `description` MEDIUMTEXT NULL,
  `icon` MEDIUMTEXT NULL,
  `color` MEDIUMTEXT NULL,
  `amount` DECIMAL(28,8) NULL,
  `direct_commission` DECIMAL(28,8) NULL,
  `indirect_commission` DECIMAL(28,8) NULL,
  `pv` DECIMAL(28,8) NULL,
  `pairing_per_day` DECIMAL(28,8) NULL,
  `cash_back` DECIMAL(28,8) NULL,
  `monthly_maintenance` DECIMAL(28,8) NULL,
  `upgrade_bonus` DECIMAL(28,8) NULL,
  `max_pairing_slots` BIGINT NULL,
  `upgrade_allowed` BIGINT NULL,
  `is_default` BIGINT NULL,
  `sort_order` BIGINT NULL,
  `status` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `unilevel_bonus` DECIMAL(28,8) NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pv_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `position` BIGINT NULL,
  `trx_type` MEDIUMTEXT NULL,
  `details` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `stage_id` BIGINT NULL,
  `original_trx` VARCHAR(255) NULL,
  `refund_trx` VARCHAR(255) NULL,
  `amount` DECIMAL(28,8) NULL DEFAULT 0,
  `balance_before` DECIMAL(28,8) NULL DEFAULT 0,
  `balance_after` DECIMAL(28,8) NULL DEFAULT 0,
  `admin_id` BIGINT NULL,
  `note` MEDIUMTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `repurchase_award_credits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `repurchase_award_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `paid_by` BIGINT NULL,
  `paid_at` DATETIME NULL,
  `acb_processed` TINYINT(1) NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `repurchase_awards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` MEDIUMTEXT NULL,
  `required_pv` DECIMAL(28,8) NULL,
  `amount` DECIMAL(28,8) NULL,
  `status` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `repurchase_pvs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `total_pv` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rinvestments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `plan_id` BIGINT NULL,
  `five` DECIMAL(28,8) NULL,
  `unit_cost` DECIMAL(28,8) NULL,
  `units` BIGINT NULL,
  `trx` MEDIUMTEXT NULL,
  `status` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rmatrices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `parent_id` BIGINT NULL DEFAULT 0,
  `position` VARCHAR(255) NULL,
  `stage_id` BIGINT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` BIGINT NOT NULL,
  `role_id` BIGINT NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `guard_name` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `savings_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `type` VARCHAR(255) NULL,
  `reference` VARCHAR(255) NULL,
  `name` VARCHAR(255) NULL,
  `principal` DECIMAL(28,8) NULL DEFAULT 0,
  `balance` DECIMAL(28,8) NULL DEFAULT 0,
  `interest_rate` DECIMAL(28,8) NULL DEFAULT 0,
  `interest_earned` DECIMAL(28,8) NULL DEFAULT 0,
  `target_amount` DECIMAL(28,8) NULL,
  `frequency` VARCHAR(255) NULL,
  `duration_cycles` BIGINT NULL,
  `contribution_per_cycle` DECIMAL(28,8) NULL,
  `start_date` DATE NULL,
  `maturity_date` DATE NULL,
  `next_due_date` DATE NULL,
  `farm_cycle_id` BIGINT NULL,
  `status` VARCHAR(255) NULL DEFAULT 'active',
  `matured_at` DATETIME NULL,
  `closed_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `savings_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(255) NULL,
  `duration_months` BIGINT NULL,
  `label` VARCHAR(255) NULL,
  `description` VARCHAR(255) NULL,
  `interest_rate` DECIMAL(28,8) NULL,
  `min_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `max_amount` DECIMAL(28,8) NULL,
  `is_active` TINYINT(1) NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shtransactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `rinvestment_id` BIGINT NULL,
  `dividend_batch_id` BIGINT NULL,
  `type` MEDIUMTEXT NULL,
  `status` MEDIUMTEXT NULL,
  `amount` DECIMAL(28,8) NULL,
  `units_held` BIGINT NULL,
  `amount_per_unit` DECIMAL(28,8) NULL,
  `reference` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `description` MEDIUMTEXT NULL,
  `paid_at` DATETIME NULL,
  `metadata` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sktransactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `amount` VARCHAR(255) NULL,
  `stockist_id` BIGINT NULL,
  `type` VARCHAR(255) NULL,
  `balance_before` DECIMAL(28,8) NULL DEFAULT 0,
  `balance_after` DECIMAL(28,8) NULL DEFAULT 0,
  `description` VARCHAR(255) NULL,
  `notes` MEDIUMTEXT NULL,
  `reference` VARCHAR(255) NULL,
  `metadata` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sponsor_change_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `previous_sponsor_id` BIGINT NULL,
  `new_sponsor_id` BIGINT NULL,
  `admin_id` BIGINT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `states` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` MEDIUMTEXT NULL,
  `invoice_code` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `code` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `product_name` MEDIUMTEXT NULL,
  `quantity` BIGINT NULL,
  `unit_price` DECIMAL(28,8) NULL,
  `line_total` DECIMAL(28,8) NULL,
  `seller_stock_before` BIGINT NULL,
  `seller_stock_after` BIGINT NULL,
  `buyer_stock_before` BIGINT NULL,
  `buyer_stock_after` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `rebate_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `rebate_percent` DECIMAL(28,8) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` MEDIUMTEXT NULL,
  `trx` MEDIUMTEXT NULL,
  `settle_trx` MEDIUMTEXT NULL,
  `buyer_stockist_id` BIGINT NULL,
  `seller_stockist_id` BIGINT NULL,
  `state_id` BIGINT NULL,
  `status` MEDIUMTEXT NULL,
  `total_amount` DECIMAL(28,8) NULL,
  `total_quantity` BIGINT NULL,
  `rebate_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `rebate_percent` DECIMAL(28,8) NULL,
  `buyer_balance_after` DECIMAL(28,8) NULL,
  `seller_balance_after` DECIMAL(28,8) NULL,
  `buyer_note` MEDIUMTEXT NULL,
  `decision_note` MEDIUMTEXT NULL,
  `closed_by` MEDIUMTEXT NULL,
  `admin_id` BIGINT NULL,
  `completed_at` MEDIUMTEXT NULL,
  `declined_at` MEDIUMTEXT NULL,
  `cancelled_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stockist_id` BIGINT NULL,
  `address_line_1` MEDIUMTEXT NULL,
  `address_line_2` MEDIUMTEXT NULL,
  `city` MEDIUMTEXT NULL,
  `state` MEDIUMTEXT NULL,
  `country` MEDIUMTEXT NULL,
  `postal_code` MEDIUMTEXT NULL,
  `latitude` DECIMAL(28,8) NULL,
  `longitude` DECIMAL(28,8) NULL,
  `phone` MEDIUMTEXT NULL,
  `email` MEDIUMTEXT NULL,
  `opening_hours` MEDIUMTEXT NULL,
  `is_primary` BIGINT NULL DEFAULT 0,
  `is_active` BIGINT NULL DEFAULT 1,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stockist_order_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `quantity` BIGINT NULL,
  `unit_price` DECIMAL(28,8) NULL,
  `total_price` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` MEDIUMTEXT NULL,
  `stockist_id` BIGINT NULL,
  `total_amount` DECIMAL(28,8) NULL,
  `shipping_cost` DECIMAL(28,8) NULL DEFAULT 0,
  `tax_amount` DECIMAL(28,8) NULL DEFAULT 0,
  `grand_total` DECIMAL(28,8) NULL,
  `status` MEDIUMTEXT NULL,
  `notes` MEDIUMTEXT NULL,
  `approved_at` MEDIUMTEXT NULL,
  `shipped_at` MEDIUMTEXT NULL,
  `delivered_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_redemptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stockist_id` BIGINT NULL,
  `type` MEDIUMTEXT NULL,
  `trx` MEDIUMTEXT NULL,
  `reference_code` MEDIUMTEXT NULL,
  `invoice_id` BIGINT NULL,
  `welcome_package_id` BIGINT NULL,
  `affiliate_order_id` BIGINT NULL,
  `customer_user_id` BIGINT NULL,
  `buyer_name` MEDIUMTEXT NULL,
  `items` MEDIUMTEXT NULL,
  `quantity` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `notes` MEDIUMTEXT NULL,
  `redeemed_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stockist_id` BIGINT NULL,
  `service_name` MEDIUMTEXT NULL,
  `description` MEDIUMTEXT NULL,
  `is_active` BIGINT NULL DEFAULT 1,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_store_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `stockist_store_id` BIGINT NULL,
  `sorder_id` BIGINT NULL,
  `quantity` BIGINT NULL,
  `quantity_prev` BIGINT NULL,
  `admin_id` BIGINT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_store_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `invoice_id` BIGINT NULL,
  `price` DECIMAL(28,8) NULL,
  `quantity` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_stores` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `product_id` BIGINT NULL,
  `quantity` BIGINT NULL DEFAULT 0,
  `is_active` BIGINT NULL DEFAULT 1,
  `status` BIGINT NULL DEFAULT 1,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `stockist_id` BIGINT NULL,
  `last_restocked_at` MEDIUMTEXT NULL,
  `min_stock_level` BIGINT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `sponsor_user_id` BIGINT NULL,
  `business_name` MEDIUMTEXT NULL,
  `state_id` BIGINT NULL,
  `business_email` MEDIUMTEXT NULL,
  `business_phone` MEDIUMTEXT NULL,
  `business_registration_number` MEDIUMTEXT NULL,
  `business_description` MEDIUMTEXT NULL,
  `website` MEDIUMTEXT NULL,
  `business_hours` MEDIUMTEXT NULL,
  `is_verified` BIGINT NULL DEFAULT 0,
  `is_active` BIGINT NULL DEFAULT 1,
  `verified_at` MEDIUMTEXT NULL,
  `wallet` DECIMAL(28,8) NULL DEFAULT 0,
  `store_type` BIGINT NULL DEFAULT 2,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `status` BIGINT NULL DEFAULT 1,
  `enrollment_bonus_paid_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stransactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `charge` DECIMAL(28,8) NULL DEFAULT 0,
  `post_balance` DECIMAL(28,8) NULL,
  `trx_type` MEDIUMTEXT NULL,
  `trx` MEDIUMTEXT NULL,
  `details` MEDIUMTEXT NULL,
  `remark` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `support_message_id` BIGINT NULL,
  `attachment` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `support_ticket_id` BIGINT NULL,
  `admin_id` BIGINT NULL DEFAULT 0,
  `message` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `name` MEDIUMTEXT NULL,
  `email` MEDIUMTEXT NULL,
  `ticket` MEDIUMTEXT NULL,
  `subject` MEDIUMTEXT NULL,
  `status` BIGINT NULL,
  `priority` BIGINT NULL,
  `last_reply` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `trx` MEDIUMTEXT NULL,
  `trx_type` MEDIUMTEXT NULL,
  `details` MEDIUMTEXT NULL,
  `remark` MEDIUMTEXT NULL,
  `amount` DECIMAL(28,8) NULL,
  `charge` DECIMAL(28,8) NULL,
  `post_balance` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `bonus_type` BIGINT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transfers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `receiver_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL DEFAULT 0,
  `charge` DECIMAL(28,8) NULL DEFAULT 0,
  `wallet` VARCHAR(255) NULL,
  `trx` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unilevel_generations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` BIGINT NULL,
  `percentage` DECIMAL(28,8) NULL,
  `status` BIGINT NULL DEFAULT 1,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `title` VARCHAR(255) NULL,
  `description` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `update_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(255) NULL,
  `update_log` MEDIUMTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_awards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `award_id` BIGINT NULL,
  `status` BIGINT NULL,
  `earned_at` MEDIUMTEXT NULL,
  `paid_at` MEDIUMTEXT NULL,
  `paid_amount` DECIMAL(28,8) NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `paid_by` BIGINT NULL,
  `note` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_extras` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `bv_left` DECIMAL(28,8) NULL DEFAULT 0,
  `bv_right` DECIMAL(28,8) NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_logins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `user_ip` VARCHAR(255) NULL,
  `city` VARCHAR(255) NULL,
  `country` VARCHAR(255) NULL,
  `country_code` VARCHAR(255) NULL,
  `longitude` VARCHAR(255) NULL,
  `latitude` VARCHAR(255) NULL,
  `browser` VARCHAR(255) NULL,
  `os` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_stage_progress` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `stage_id` BIGINT NULL,
  `is_completed` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `useridcards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` MEDIUMTEXT NULL,
  `firstname` MEDIUMTEXT NULL,
  `lastname` MEDIUMTEXT NULL,
  `email` MEDIUMTEXT NULL,
  `mobile` MEDIUMTEXT NULL,
  `bname` MEDIUMTEXT NULL,
  `aname` MEDIUMTEXT NULL,
  `ano` MEDIUMTEXT NULL,
  `address` MEDIUMTEXT NULL,
  `state` MEDIUMTEXT NULL,
  `city` MEDIUMTEXT NULL,
  `zip` MEDIUMTEXT NULL,
  `country_name` MEDIUMTEXT NULL,
  `image` MEDIUMTEXT NULL,
  `ts` BIGINT NULL,
  `tsc` MEDIUMTEXT NULL,
  `password` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  `ref_by` BIGINT NULL,
  `withdrawal_blocked` BIGINT NULL DEFAULT 0,
  `withdrawal_block_reason` MEDIUMTEXT NULL,
  `balance` DECIMAL(28,8) NULL DEFAULT 0,
  `status` BIGINT NULL DEFAULT 1,
  `tv` BIGINT NULL DEFAULT 0,
  `kv` BIGINT NULL DEFAULT 0,
  `nin` MEDIUMTEXT NULL,
  `id_card_type` MEDIUMTEXT NULL,
  `id_card_image` MEDIUMTEXT NULL,
  `kyc_data` MEDIUMTEXT NULL,
  `kyc_rejection_reason` MEDIUMTEXT NULL,
  `repurchase_award` DECIMAL(28,8) NULL DEFAULT 0,
  `acb` DECIMAL(28,8) NULL DEFAULT 0,
  `visa` DECIMAL(28,8) NULL DEFAULT 0,
  `project_id` BIGINT NULL,
  `product_wallet` DECIMAL(28,8) NULL DEFAULT 0,
  `stockist_rebate` DECIMAL(28,8) NULL DEFAULT 0,
  `section` BIGINT NULL DEFAULT 1,
  `affiliate_code` VARCHAR(255) NULL,
  `autoship` DECIMAL(28,8) NULL DEFAULT 0,
  `ver_code` VARCHAR(255) NULL,
  `ver_code_send_at` DATETIME NULL,
  `ban_reason` MEDIUMTEXT NULL,
  `ev` BIGINT NULL,
  `sv` BIGINT NULL,
  `dial_code` VARCHAR(255) NULL,
  `profile_complete` BIGINT NULL,
  `parent` VARCHAR(255) NULL,
  `position` VARCHAR(255) NULL,
  `pos_id` BIGINT NULL,
  `trx` VARCHAR(255) NULL,
  `country_code` VARCHAR(255) NULL,
  `savings_wallet` VARCHAR(255) NULL,
  `stockist_sponsor_bonus` VARCHAR(255) NULL,
  `affiliate_bonus` VARCHAR(255) NULL,
  `key_in_bonus` VARCHAR(255) NULL,
  `matching_bonus` VARCHAR(255) NULL,
  `awards` VARCHAR(255) NULL,
  `unilevel_bonus` VARCHAR(255) NULL,
  `upgrade_bonus` VARCHAR(255) NULL,
  `indirect_bonus` VARCHAR(255) NULL,
  `direct_bonus` VARCHAR(255) NULL,
  `ambassador` BIGINT NULL DEFAULT 0,
  `total_invest` DECIMAL(28,8) NULL DEFAULT 0,
  `total_binary_com` DECIMAL(28,8) NULL DEFAULT 0,
  `shares` DECIMAL(28,8) NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `welcome_packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `code` MEDIUMTEXT NULL,
  `source` MEDIUMTEXT NULL,
  `status` MEDIUMTEXT NULL,
  `trx` MEDIUMTEXT NULL,
  `redeemed_by_stockist_id` BIGINT NULL,
  `redeemed_at` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `withdraw_methods` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` BIGINT NULL,
  `name` MEDIUMTEXT NULL,
  `image` MEDIUMTEXT NULL,
  `min_limit` DECIMAL(28,8) NULL,
  `max_limit` DECIMAL(28,8) NULL,
  `fixed_charge` DECIMAL(28,8) NULL,
  `percent_charge` DECIMAL(28,8) NULL,
  `rate` DECIMAL(28,8) NULL,
  `currency` MEDIUMTEXT NULL,
  `description` MEDIUMTEXT NULL,
  `status` BIGINT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NULL,
  `method_id` BIGINT NULL,
  `amount` DECIMAL(28,8) NULL,
  `charge` DECIMAL(28,8) NULL,
  `rate` DECIMAL(28,8) NULL,
  `final_amount` DECIMAL(28,8) NULL,
  `after_charge` DECIMAL(28,8) NULL,
  `currency` MEDIUMTEXT NULL,
  `trx` MEDIUMTEXT NULL,
  `status` BIGINT NULL DEFAULT 0,
  `withdraw_information` MEDIUMTEXT NULL,
  `admin_feedback` MEDIUMTEXT NULL,
  `created_at` MEDIUMTEXT NULL,
  `updated_at` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `queue` VARCHAR(255) NOT NULL, `payload` LONGTEXT NOT NULL, `attempts` TINYINT UNSIGNED NOT NULL, `reserved_at` INT UNSIGNED NULL, `available_at` INT UNSIGNED NOT NULL, `created_at` INT UNSIGNED NOT NULL, PRIMARY KEY (`id`), KEY `jobs_queue_index` (`queue`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (`id` VARCHAR(255) NOT NULL, `name` VARCHAR(255) NOT NULL, `total_jobs` INT NOT NULL, `pending_jobs` INT NOT NULL, `failed_jobs` INT NOT NULL, `failed_job_ids` LONGTEXT NOT NULL, `options` MEDIUMTEXT NULL, `cancelled_at` INT NULL, `created_at` INT NOT NULL, `finished_at` INT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` VARCHAR(255) NOT NULL, `connection` TEXT NOT NULL, `queue` TEXT NOT NULL, `payload` LONGTEXT NOT NULL, `exception` LONGTEXT NOT NULL, `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (`key` VARCHAR(255) NOT NULL, `value` MEDIUMTEXT NOT NULL, `expiration` INT NOT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (`key` VARCHAR(255) NOT NULL, `owner` VARCHAR(255) NOT NULL, `expiration` INT NOT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (`id` VARCHAR(255) NOT NULL, `user_id` BIGINT UNSIGNED NULL, `ip_address` VARCHAR(45) NULL, `user_agent` TEXT NULL, `payload` LONGTEXT NOT NULL, `last_activity` INT NOT NULL, PRIMARY KEY (`id`), KEY `sessions_user_id_index` (`user_id`), KEY `sessions_last_activity_index` (`last_activity`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commissions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, from_user_id BIGINT NULL, amount DECIMAL(28,8) NULL DEFAULT 0, type VARCHAR(255) NULL, details MEDIUMTEXT NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referrals` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, ref_by BIGINT NULL, details MEDIUMTEXT NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `savings_transactions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, wallet_type VARCHAR(255) NULL, savings_product_id BIGINT NULL, amount DECIMAL(28,8) NULL DEFAULT 0, post_balance DECIMAL(28,8) NULL DEFAULT 0, trx_type VARCHAR(10) NULL, trx VARCHAR(255) NULL, remark VARCHAR(255) NULL, details MEDIUMTEXT NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sorders` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, stockist_id BIGINT NULL, product_id BIGINT NULL, quantity BIGINT NULL DEFAULT 0, amount DECIMAL(28,8) NULL DEFAULT 0, status TINYINT NULL DEFAULT 0, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stage_outs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, stage_id BIGINT NULL, details MEDIUMTEXT NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stateleaders` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, state_id BIGINT NULL, status TINYINT NULL DEFAULT 1, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_inventories` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, stockist_id BIGINT NULL, product_id BIGINT NULL, quantity BIGINT NULL DEFAULT 0, min_stock_level BIGINT NULL, max_stock_level BIGINT NULL, unit_cost DECIMAL(28,8) NULL, total_value DECIMAL(28,8) NULL, last_restocked_at DATETIME NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stockist_products` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(255) NULL, description MEDIUMTEXT NULL, price DECIMAL(28,8) NULL, stock_quantity BIGINT NULL, min_order_quantity BIGINT NULL, max_order_quantity BIGINT NULL, is_active TINYINT(1) NULL DEFAULT 1, image VARCHAR(255) NULL, specifications MEDIUMTEXT NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `supplies` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, stockist_id BIGINT NULL, product_id BIGINT NULL, quantity BIGINT NULL DEFAULT 0, amount DECIMAL(28,8) NULL DEFAULT 0, status TINYINT NULL DEFAULT 0, details MEDIUMTEXT NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
