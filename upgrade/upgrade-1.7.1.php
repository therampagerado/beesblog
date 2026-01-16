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

    $db->execute(
        'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_post_shop` (
            `id_bees_blog_post` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_bees_blog_post`, `id_shop`),
            KEY `id_shop` (`id_shop`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $db->execute(
        'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_category_shop` (
            `id_bees_blog_category` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_bees_blog_category`, `id_shop`),
            KEY `id_shop` (`id_shop`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.'bees_blog_post_shop` (`id_bees_blog_post`, `id_shop`)
        SELECT p.`id_bees_blog_post`, s.`id_shop`
        FROM `'._DB_PREFIX_.'bees_blog_post` p
        CROSS JOIN `'._DB_PREFIX_.'shop` s'
    );

    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.'bees_blog_category_shop` (`id_bees_blog_category`, `id_shop`)
        SELECT c.`id_bees_blog_category`, s.`id_shop`
        FROM `'._DB_PREFIX_.'bees_blog_category` c
        CROSS JOIN `'._DB_PREFIX_.'shop` s'
    );

    $keys = [
        'BEESBLOG_POSTS_PER_PAGE',
        'BEESBLOG_SHOW_AUTHOR_STYLE',
        'BEESBLOG_MAIN_URL_KEY',
        'BEESBLOG_USE_HTML',
        'BEESBLOG_ENABLE_COMMENT',
        'BEESBLOG_SHOW_AUTHOR',
        'BEESBLOG_SHOW_DATE',
        'BEESBLOG_SOCIAL_SHARING',
        'BEESBLOG_SHOW_VIEWED',
        'BEESBLOG_SHOW_NO_IMAGE',
        'BEESBLOG_CUSTOM_CSS',
        'BEESBLOG_DISABLE_CATEGORY_IMAGE',
        'BEESBLOG_META_TITLE',
        'BEESBLOG_META_KEYWORDS',
        'BEESBLOG_META_DESCRIPTION',
        'BEESBLOG_DISQUS_USERNAME',
    ];

    $escapedKeys = array_map('pSQL', $keys);
    $keysList = "'".implode("','", $escapedKeys)."'";

    $db->execute(
        'INSERT INTO `'._DB_PREFIX_.'configuration` (`id_shop_group`, `id_shop`, `name`, `value`, `date_add`, `date_upd`)
        SELECT s.`id_shop_group`, s.`id_shop`, c.`name`, c.`value`, c.`date_add`, c.`date_upd`
        FROM `'._DB_PREFIX_.'configuration` c
        INNER JOIN `'._DB_PREFIX_.'shop` s ON 1=1
        LEFT JOIN `'._DB_PREFIX_.'configuration` cs
            ON cs.`name` = c.`name`
            AND cs.`id_shop` = s.`id_shop`
            AND cs.`id_shop_group` = s.`id_shop_group`
        WHERE c.`name` IN ('.$keysList.')
            AND c.`id_shop` IS NULL
            AND c.`id_shop_group` IS NULL
            AND cs.`id_configuration` IS NULL'
    );

    return true;
}
