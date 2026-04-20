-- Bonus system schema (MySQL 8+)
-- Mirrors migrations:
-- 2026_02_20_100000_create_bonus_rules_table.php
-- 2026_02_20_100100_create_bonus_grants_table.php
-- 2026_02_20_100200_create_bonus_grant_events_table.php
-- 2026_02_20_100300_create_manual_bonus_batches_table.php
-- 2026_02_20_100400_adjust_wallets_unique_for_multi_wallet_per_player.php

START TRANSACTION;

CREATE TABLE `bonus_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `int_casino_id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `trigger_type` VARCHAR(40) NOT NULL,
  `condition_json` JSON NULL,
  `reward_type` VARCHAR(30) NOT NULL,
  `reward_value` DECIMAL(24,8) NOT NULL,
  `currency_id` VARCHAR(32) NULL,
  `currency_code` VARCHAR(16) NULL,
  `max_reward_amount` DECIMAL(24,8) NULL,
  `wagering_multiplier` INT UNSIGNED NOT NULL DEFAULT 0,
  `valid_from` TIMESTAMP NULL,
  `valid_until` TIMESTAMP NULL,
  `priority` INT UNSIGNED NOT NULL DEFAULT 100,
  `stacking_policy` VARCHAR(20) NOT NULL DEFAULT 'stackable',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `bonus_rules_int_casino_id_index` (`int_casino_id`),
  INDEX `bonus_rules_trigger_type_index` (`trigger_type`),
  INDEX `bonus_rules_currency_id_index` (`currency_id`),
  INDEX `bonus_rules_valid_from_index` (`valid_from`),
  INDEX `bonus_rules_valid_until_index` (`valid_until`),
  INDEX `bonus_rules_priority_index` (`priority`),
  INDEX `bonus_rules_is_active_index` (`is_active`),
  CONSTRAINT `bonus_rules_int_casino_id_foreign`
    FOREIGN KEY (`int_casino_id`) REFERENCES `casinos` (`int_casino_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `bonus_rules_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `bonus_rules_updated_by_foreign`
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bonus_grants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bonus_rule_id` BIGINT UNSIGNED NULL,
  `int_casino_id` VARCHAR(255) NOT NULL,
  `player_id` BIGINT UNSIGNED NOT NULL,
  `wallet_id_bonus` BIGINT UNSIGNED NOT NULL,
  `currency_id` VARCHAR(32) NULL,
  `currency_code` VARCHAR(16) NULL,
  `amount_granted_base` DECIMAL(65,0) NOT NULL,
  `amount_remaining_base` DECIMAL(65,0) NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'granted',
  `source_type` VARCHAR(30) NOT NULL DEFAULT 'automatic',
  `source_ref` VARCHAR(191) NULL,
  `expires_at` TIMESTAMP NULL,
  `wagering_required_base` DECIMAL(65,0) NOT NULL DEFAULT 0,
  `wagering_progress_base` DECIMAL(65,0) NOT NULL DEFAULT 0,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `bonus_grants_bonus_rule_id_index` (`bonus_rule_id`),
  INDEX `bonus_grants_int_casino_id_index` (`int_casino_id`),
  INDEX `bonus_grants_player_id_index` (`player_id`),
  INDEX `bonus_grants_wallet_id_bonus_index` (`wallet_id_bonus`),
  INDEX `bonus_grants_currency_id_index` (`currency_id`),
  INDEX `bonus_grants_amount_remaining_base_index` (`amount_remaining_base`),
  INDEX `bonus_grants_status_index` (`status`),
  INDEX `bonus_grants_source_type_index` (`source_type`),
  INDEX `bonus_grants_source_ref_index` (`source_ref`),
  INDEX `bonus_grants_expires_at_index` (`expires_at`),
  INDEX `bonus_grants_player_status_expires_idx` (`player_id`, `status`, `expires_at`),
  INDEX `bonus_grants_casino_status_created_idx` (`int_casino_id`, `status`, `created_at`),
  CONSTRAINT `bonus_grants_bonus_rule_id_foreign`
    FOREIGN KEY (`bonus_rule_id`) REFERENCES `bonus_rules` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `bonus_grants_int_casino_id_foreign`
    FOREIGN KEY (`int_casino_id`) REFERENCES `casinos` (`int_casino_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `bonus_grants_player_id_foreign`
    FOREIGN KEY (`player_id`) REFERENCES `players` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `bonus_grants_wallet_id_bonus_foreign`
    FOREIGN KEY (`wallet_id_bonus`) REFERENCES `wallets` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bonus_grant_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bonus_grant_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(40) NOT NULL,
  `amount_base` DECIMAL(65,0) NOT NULL DEFAULT 0,
  `idempotency_key` VARCHAR(191) NOT NULL,
  `reference_type` VARCHAR(32) NULL,
  `reference_id` VARCHAR(128) NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bonus_grant_events_idempotency_key_unique` (`idempotency_key`),
  INDEX `bonus_grant_events_bonus_grant_id_index` (`bonus_grant_id`),
  INDEX `bonus_grant_events_event_type_index` (`event_type`),
  INDEX `bonus_grant_events_reference_type_index` (`reference_type`),
  INDEX `bonus_grant_events_reference_id_index` (`reference_id`),
  INDEX `bonus_grant_events_grant_event_created_idx` (`bonus_grant_id`, `event_type`, `created_at`),
  CONSTRAINT `bonus_grant_events_bonus_grant_id_foreign`
    FOREIGN KEY (`bonus_grant_id`) REFERENCES `bonus_grants` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `manual_bonus_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `int_casino_id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `segment_filter_json` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `estimated_players` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `manual_bonus_batches_int_casino_id_index` (`int_casino_id`),
  INDEX `manual_bonus_batches_status_index` (`status`),
  INDEX `manual_bonus_batches_casino_status_created_idx` (`int_casino_id`, `status`, `created_at`),
  CONSTRAINT `manual_bonus_batches_int_casino_id_foreign`
    FOREIGN KEY (`int_casino_id`) REFERENCES `casinos` (`int_casino_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `manual_bonus_batches_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Allow multiple wallets per player (dual wallet support)
ALTER TABLE `wallets`
  DROP INDEX `wallets_holder_type_holder_id_unique`,
  ADD UNIQUE KEY `wallets_holder_wallet_type_unique` (`holder_type`, `holder_id`, `wallet_type_id`);

COMMIT;
