-- Offerwall schema: MySQL
-- Run: mysql -u root -p offerwall < migrations/schema.sql

CREATE DATABASE IF NOT EXISTS `offerwall` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `offerwall`;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `upi` VARCHAR(100) DEFAULT NULL,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` CHAR(64) NOT NULL UNIQUE,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `device` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `settings` (
  `k` VARCHAR(100) NOT NULL PRIMARY KEY,
  `v` TEXT
) ENGINE=InnoDB;

INSERT INTO `settings` (`k`,`v`) VALUES
('min_withdraw','100'),
('postback_secret', 'CHANGE_ME'),
('maintenance_mode','0'),
('support_email','support@example.com');

CREATE TABLE `offers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `steps` JSON DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `reward` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `offer_clicks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `offer_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `click_id` CHAR(36) NOT NULL UNIQUE,
  `status` ENUM('started','pending','approved','rejected') NOT NULL DEFAULT 'started',
  `meta` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`offer_id`) REFERENCES `offers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `postbacks_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payload` JSON,
  `processed` TINYINT(1) NOT NULL DEFAULT 0,
  `error` TEXT DEFAULT NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('credit','debit') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `balance_before` DECIMAL(12,2) NOT NULL,
  `balance_after` DECIMAL(12,2) NOT NULL,
  `reference` VARCHAR(255) DEFAULT NULL,
  `meta` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `withdrawals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `upi` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('requested','processing','paid','rejected') NOT NULL DEFAULT 'requested',
  `admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `blocked_entities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('ip','device','upi','user') NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Indexes
CREATE INDEX idx_offer_clicks_user ON offer_clicks(user_id);
CREATE INDEX idx_wallet_user ON wallet_transactions(user_id);
CREATE INDEX idx_withdraw_user ON withdrawals(user_id);
