-- ---------------------------------------------------------------------------
-- Production SQL for the customer activity audit log feature.
--
-- Run this against the PRODUCTION database to create the customer_activity_logs
-- table used by the customer activity feed (created / updated / invited /
-- logged_in / logged_out / password_reset events).
--
-- This mirrors the Laravel migration:
--   database/migrations/2026_09_08_000001_create_customer_activity_logs_table.php
--
-- Safe to run once. Uses IF NOT EXISTS so re-running is a no-op.
-- Requires the `customers` and `users` tables to already exist.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `customer_activity_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `old_values` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_activity_logs_user_id_foreign` (`user_id`),
  KEY `customer_activity_logs_customer_id_created_at_index` (`customer_id`, `created_at`),
  CONSTRAINT `customer_activity_logs_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_activity_logs_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional but recommended: record the migration as run so `php artisan migrate`
-- in production does not attempt to create the table again. Set the batch number
-- to one higher than the current max, e.g.:
--   INSERT INTO `migrations` (`migration`, `batch`)
--   VALUES ('2026_09_08_000001_create_customer_activity_logs_table',
--           (SELECT COALESCE(MAX(b.batch), 0) + 1 FROM (SELECT batch FROM migrations) AS b));
