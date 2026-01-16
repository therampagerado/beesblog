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
 * @throws PrestaShopException
 */
function upgrade_module_1_7_1()
{
    $db = Db::getInstance();
    $prefix = _DB_PREFIX_;

    $success = true;

    $success = $success && $db->execute(
        'CREATE TABLE IF NOT EXISTS `'.$prefix.'bees_blog_post_shop` (
            `id_bees_blog_post` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_bees_blog_post`, `id_shop`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $success = $success && $db->execute(
        'CREATE TABLE IF NOT EXISTS `'.$prefix.'bees_blog_category_shop` (
            `id_bees_blog_category` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_bees_blog_category`, `id_shop`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $success = $success && $db->execute(
        'INSERT IGNORE INTO `'.$prefix.'bees_blog_post_shop` (`id_bees_blog_post`, `id_shop`)
         SELECT p.`id_bees_blog_post`, s.`id_shop`
         FROM `'.$prefix.'bees_blog_post` p
         CROSS JOIN `'.$prefix.'shop` s'
    );

    $success = $success && $db->execute(
        'INSERT IGNORE INTO `'.$prefix.'bees_blog_category_shop` (`id_bees_blog_category`, `id_shop`)
         SELECT c.`id_bees_blog_category`, s.`id_shop`
         FROM `'.$prefix.'bees_blog_category` c
         CROSS JOIN `'.$prefix.'shop` s'
    );

    $configKeys = [
        BeesBlog::POSTS_PER_PAGE,
        BeesBlog::AUTHOR_STYLE,
        BeesBlog::MAIN_URL_KEY,
        BeesBlog::USE_HTML,
        BeesBlog::ENABLE_COMMENT,
        BeesBlog::SHOW_AUTHOR,
        BeesBlog::SHOW_DATE,
        BeesBlog::SOCIAL_SHARING,
        BeesBlog::SHOW_POST_COUNT,
        BeesBlog::SHOW_NO_IMAGE,
        BeesBlog::CUSTOM_CSS,
        BeesBlog::SHOW_CATEGORY_IMAGE,
        BeesBlog::HOME_TITLE,
        BeesBlog::HOME_KEYWORDS,
        BeesBlog::HOME_DESCRIPTION,
        BeesBlog::DISQUS_USERNAME,
    ];

    $shops = Shop::getShops(false, null, true);
    foreach ($configKeys as $key) {
        $globalValue = Configuration::getGlobalValue($key);
        if ($globalValue === false) {
            continue;
        }
        foreach ($shops as $shopId) {
            $exists = $db->getValue(
                'SELECT 1 FROM `'.$prefix.'configuration`
                 WHERE `name` = \''.pSQL($key).'\' AND `id_shop` = '.(int) $shopId
            );
            if (!$exists) {
                Configuration::updateValue($key, $globalValue, false, null, (int) $shopId);
            }
        }
    }

    return (bool) $success;
}
