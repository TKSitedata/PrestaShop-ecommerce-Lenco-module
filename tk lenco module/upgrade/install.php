<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Lenco Technologies Inc. <support@lenco.co>
 * @copyright Since 2024 Lenco Technologies Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Create database table for storing transactions
 *
 * @param Lenco $module
 *
 * @return bool
 */
function upgrade_module_1_0_0($module)
{
    $sql = [];

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lenco_transactions` (
        `id_lenco_transaction` INT(11) NOT NULL AUTO_INCREMENT,
        `id_order` INT(11) NOT NULL,
        `reference` VARCHAR(255) NOT NULL,
        `amount` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
        `currency` VARCHAR(10) NOT NULL DEFAULT \'ZMW\',
        `status` ENUM(\'pending\', \'success\', \'failed\', \'cancelled\') NOT NULL DEFAULT \'pending\',
        `lenco_response` TEXT,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id_lenco_transaction`),
        UNIQUE KEY `reference` (`reference`),
        KEY `id_order` (`id_order`),
        KEY `status` (`status`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return true;
}
