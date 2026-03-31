-- --------------------------------------------------------
-- Lenco Payment Gateway - Database Schema
-- For PrestaShop 1.7+
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `{PREFIX}lenco_transactions` (
  `id_lenco_transaction` INT(11) NOT NULL AUTO_INCREMENT,
  `id_order` INT(11) NOT NULL,
  `reference` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'ZMW',
  `status` ENUM('pending', 'success', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `lenco_response` TEXT,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_lenco_transaction`),
  UNIQUE KEY `reference` (`reference`),
  KEY `id_order` (`id_order`),
  KEY `status` (`status`)
) ENGINE={ENGINE} DEFAULT CHARSET=utf8;
