<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * @return bool
 * @throws PrestaShopDatabaseException
 */
function upgrade_module_1_7_1()
{
    $db = Db::getInstance();
    $result = true;

    $result &= $db->execute(
        'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_post_shop` (
            `id_bees_blog_post` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_bees_blog_post`, `id_shop`),
            KEY `id_shop` (`id_shop`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $result &= $db->execute(
        'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_category_shop` (
            `id_bees_blog_category` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_bees_blog_category`, `id_shop`),
            KEY `id_shop` (`id_shop`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $result &= $db->execute(
        'INSERT INTO `'._DB_PREFIX_.'bees_blog_post_shop` (`id_bees_blog_post`, `id_shop`)
         SELECT p.`id_bees_blog_post`, s.`id_shop`
         FROM `'._DB_PREFIX_.'bees_blog_post` p
         CROSS JOIN `'._DB_PREFIX_.'shop` s
         LEFT JOIN `'._DB_PREFIX_.'bees_blog_post_shop` ps
           ON (ps.`id_bees_blog_post` = p.`id_bees_blog_post` AND ps.`id_shop` = s.`id_shop`)
         WHERE ps.`id_bees_blog_post` IS NULL'
    );

    $result &= $db->execute(
        'INSERT INTO `'._DB_PREFIX_.'bees_blog_category_shop` (`id_bees_blog_category`, `id_shop`)
         SELECT c.`id_bees_blog_category`, s.`id_shop`
         FROM `'._DB_PREFIX_.'bees_blog_category` c
         CROSS JOIN `'._DB_PREFIX_.'shop` s
         LEFT JOIN `'._DB_PREFIX_.'bees_blog_category_shop` cs
           ON (cs.`id_bees_blog_category` = c.`id_bees_blog_category` AND cs.`id_shop` = s.`id_shop`)
         WHERE cs.`id_bees_blog_category` IS NULL'
    );

    $configRows = $db->executeS(
        'SELECT `name`, `value`
         FROM `'._DB_PREFIX_.'configuration`
         WHERE `name` LIKE "BEESBLOG\\_%" AND `id_shop` IS NULL AND `id_shop_group` IS NULL'
    );

    if ($configRows) {
        $now = date('Y-m-d H:i:s');
        foreach (Shop::getShops(true, null, true) as $shopId) {
            foreach ($configRows as $config) {
                $name = (string) $config['name'];
                $value = (string) $config['value'];
                $exists = $db->getValue(
                    'SELECT `id_configuration`
                     FROM `'._DB_PREFIX_.'configuration`
                     WHERE `name` = "'.pSQL($name).'"
                       AND `id_shop` = '.(int) $shopId.'
                       AND `id_shop_group` IS NULL'
                );
                if (!$exists) {
                    $result &= $db->insert('configuration', [
                        'name' => pSQL($name),
                        'value' => pSQL($value, true),
                        'date_add' => $now,
                        'date_upd' => $now,
                        'id_shop_group' => null,
                        'id_shop' => (int) $shopId,
                    ]);
                }
            }
        }
    }

    return (bool) $result;
}
