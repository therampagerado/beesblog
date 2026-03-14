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

namespace BeesBlogModule;

use Configuration;
use Context;
use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionClass;
use Shop;
use Tools;
use Validate;

/**
 * Class BeesBlogImageType
 *
 * @since 1.0.0
 */
class BeesBlogImageType extends ObjectModel
{
    const PRIMARY = 'id_bees_blog_image_type';
    const TABLE = 'bees_blog_image_type';
    const LANG_TABLE = 'bees_blog_image_type_lang';
    const SHOP_TABLE = 'bees_blog_image_type_shop';

    const POST_LIST_ITEM_WIDTH = 800;
    const POST_LIST_ITEM_HEIGHT = 500;
    const POST_DEFAULT_WIDTH = 800;
    const POST_DEFAULT_HEIGHT = 500;
    const CATEGORY_DEFAULT_WIDTH = 800;
    const CATEGORY_DEFAULT_HEIGHT = 500;

    /**
     * @var array Image types cache
     */
    protected static $imagesTypesCache = [];

    /**
     * @var array
     */
    protected static $imagesTypesNameCache = [];

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var int Width
     */
    public $width;

    /**
     * @var int Height
     */
    public $height;

    /**
     * @var bool $posts Apply to posts
     */
    public $posts;

    /**
     * @var bool $categories Apply to categories
     */
    public $categories;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => self::TABLE,
        'primary'   => self::PRIMARY,
        'multishop' => true,
        'fields'    => [
            'name'       => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64, 'db_type' => 'VARCHAR(64)'],
            'width'      => ['type' => self::TYPE_INT,    'validate' => 'isImageSize',     'required' => true,               'db_type' => 'INT(11) UNSIGNED'],
            'height'     => ['type' => self::TYPE_INT,    'validate' => 'isImageSize',     'required' => true,               'db_type' => 'INT(11) UNSIGNED'],
            'posts'      => ['type' => self::TYPE_BOOL,   'validate' => 'isBool',          'required' => true,               'db_type' => 'TINYINT(1)'],
            'categories' => ['type' => self::TYPE_BOOL,   'validate' => 'isBool',          'required' => true,               'db_type' => 'TINYINT(1)'],
        ],
    ];

    /**
     * @var array
     */
    protected $webserviceParameters = [];

    /**
     * Returns image type definitions
     *
     * @param string|null $type Image type
     * @param bool $orderBySize
     *
     * @return array Image type definitions
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getImagesTypes($type = null, $orderBySize = false)
    {
        static::ensureShopAssociations();
        $shopIds = array_map('intval', Shop::getContextListShopID());
        if (!$shopIds) {
            $shopIds = [(int) Context::getContext()->shop->id ?: (int) Configuration::get('PS_SHOP_DEFAULT')];
        }
        $cacheKey = implode('-', $shopIds).'|'.$type.'|'.(int) $orderBySize;
        if (!isset(static::$imagesTypesCache[$cacheKey])) {
            $where = 'WHERE 1';
            if (!empty($type)) {
                $where .= ' AND it.`'.bqSQL($type).'` = 1 ';
            }
            $where .= ' AND its.`id_shop` IN ('.implode(', ', $shopIds).')';

            if ($orderBySize) {
                $query = 'SELECT DISTINCT it.* FROM `'._DB_PREFIX_.bqSQL(static::$definition['table']).'` it
                    INNER JOIN `'._DB_PREFIX_.bqSQL(static::SHOP_TABLE).'` its ON (its.`'.static::PRIMARY.'` = it.`'.static::PRIMARY.'`)
                    '.$where.' ORDER BY it.`width` DESC, it.`height` DESC, it.`name` ASC';
            } else {
                $query = 'SELECT DISTINCT it.* FROM `'._DB_PREFIX_.bqSQL(static::$definition['table']).'` it
                    INNER JOIN `'._DB_PREFIX_.bqSQL(static::SHOP_TABLE).'` its ON (its.`'.static::PRIMARY.'` = it.`'.static::PRIMARY.'`)
                    '.$where.' ORDER BY it.`name` ASC';
            }

            static::$imagesTypesCache[$cacheKey] = Db::getInstance()->executeS($query);
        }

        return static::$imagesTypesCache[$cacheKey];
    }

    /**
     * Check if type already is already registered in database
     *
     * @param string $typeName Name
     *
     * @return int Number of results found
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function typeAlreadyExists($typeName)
    {
        if (!Validate::isImageTypeName($typeName)) {
            die(Tools::displayError());
        }

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select(static::PRIMARY)
                ->from(static::TABLE)
                ->where('`name` = \''.pSQL($typeName).'\'')
        );
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFormatedName($name)
    {
        $themeName = Context::getContext()->shop->theme_name;
        $nameWithoutThemeName = str_replace(['_'.$themeName, $themeName.'_'], '', $name);

        //check if the theme name is already in $name if yes only return $name
        if (strstr($name, $themeName) && static::getByNameNType($name)) {
            return $name;
        } elseif (static::getByNameNType($nameWithoutThemeName.'_'.$themeName)) {
            return $nameWithoutThemeName.'_'.$themeName;
        } elseif (static::getByNameNType($themeName.'_'.$nameWithoutThemeName)) {
            return $themeName.'_'.$nameWithoutThemeName;
        } else {
            return $nameWithoutThemeName.'_default';
        }
    }

    /**
     * Finds image type definition by name and type
     *
     * @param string $name
     * @param string|null $type
     * @param int $order
     *
     * @return bool|array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByNameNType($name, $type = null, $order = 0)
    {
        static::ensureShopAssociations();
        $shopIds = array_map('intval', Shop::getContextListShopID());
        if (!$shopIds) {
            $shopIds = [(int) Context::getContext()->shop->id ?: (int) Configuration::get('PS_SHOP_DEFAULT')];
        }
        $cacheKey = implode('-', $shopIds)."_{$name}_{$type}_{$order}";
        if (!array_key_exists($cacheKey, static::$imagesTypesNameCache)) {
            $sql = new DbQuery();
            $sql->select('DISTINCT it.*');
            $sql->from(static::TABLE, 'it');
            $sql->innerJoin(static::SHOP_TABLE, 'its', 'its.`'.static::PRIMARY.'` = it.`'.static::PRIMARY.'`');
            $sql->where("it.`name` = '".pSQL($name)."'");
            $sql->where('its.`id_shop` IN ('.implode(', ', $shopIds).')');
            if ($type) {
                $sql->where('it.`'.bqSQL($type).'` = '.(int) $order);
            }

            static::$imagesTypesNameCache[$cacheKey] = Db::getInstance()->getRow($sql) ?: false;
        }

        return static::$imagesTypesNameCache[$cacheKey];
    }

    /**
     * Get basic type IDs
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public static function getBasicTypeIds()
    {
        static::ensureShopAssociations();
        $shopIds = static::getContextShopIds();

        $sql = new DbQuery();
        $sql->select('DISTINCT it.'.static::PRIMARY);
        $sql->from(static::TABLE, 'it');
        $sql->innerJoin(static::SHOP_TABLE, 'its', 'its.`'.static::PRIMARY.'` = it.`'.static::PRIMARY.'`');
        $sql->where('its.`id_shop` IN ('.implode(', ', $shopIds).')');
        $sql->where('it.`name` IN (\'post_list_item\', \'post_default\', \'category_default\')');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (is_array($result)) {
            return array_column($result, static::PRIMARY);
        }

        return false;
    }

    /**
     * Install basic image types
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public static function installBasics()
    {
        static::ensureShopAssociations();
        $basicTypes = static::getBasicTypeDefinitions();
        $shops = array_map('intval', Shop::getShops(false, null, true));

        $reflection = new ReflectionClass(__CLASS__);
        $consts = $reflection->getConstants();

        foreach ($basicTypes as $basicType => $basicTypeData) {
            $canonicalId = static::getCanonicalTypeId($basicType);
            if (!$canonicalId) {
                Db::getInstance()->insert(
                    static::TABLE,
                    [
                        'name'       => $basicType,
                        'width'      => $consts[strtoupper($basicType).'_WIDTH'],
                        'height'     => $consts[strtoupper($basicType).'_HEIGHT'],
                        'posts'      => (int) $basicTypeData['posts'],
                        'categories' => (int) $basicTypeData['categories'],
                    ]
                );
                $canonicalId = (int) Db::getInstance()->Insert_ID();
            } else {
                Db::getInstance()->update(
                    static::TABLE,
                    [
                        'width'      => (int) $consts[strtoupper($basicType).'_WIDTH'],
                        'height'     => (int) $consts[strtoupper($basicType).'_HEIGHT'],
                        'posts'      => (int) $basicTypeData['posts'],
                        'categories' => (int) $basicTypeData['categories'],
                    ],
                    static::PRIMARY.' = '.(int) $canonicalId
                );
            }

            static::associateTypeToShops($canonicalId, $shops);
            static::mergeDuplicateBasicTypes($basicType, $canonicalId);
        }

        static::clearCaches();
    }

    /**
     * Ensures shop associations are registered before multistore queries run.
     *
     * @return void
     * @throws PrestaShopException
     */
    protected static function ensureShopAssociations()
    {
        \BeesBlog::registerShopAssociations();
    }

    /**
     * Returns reserved basic type definitions keyed by type name.
     *
     * @return array[]
     */
    protected static function getBasicTypeDefinitions()
    {
        return [
            'post_list_item'  => ['posts' => true,  'categories' => false],
            'post_default'    => ['posts' => true,  'categories' => false],
            'category_default'=> ['posts' => false, 'categories' => true],
        ];
    }

    /**
     * Returns the active shop IDs for the current context.
     *
     * @return int[]
     * @throws PrestaShopException
     */
    protected static function getContextShopIds()
    {
        $shopIds = array_map('intval', Shop::getContextListShopID());
        if (!$shopIds) {
            $shopId = (int) Context::getContext()->shop->id;
            if (!$shopId) {
                $shopId = (int) Configuration::get('PS_SHOP_DEFAULT');
            }
            $shopIds = [$shopId];
        }

        return $shopIds;
    }

    /**
     * Returns the canonical image type ID for a reserved basic name.
     *
     * @param string $typeName
     *
     * @return int
     */
    protected static function getCanonicalTypeId($typeName)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select(static::PRIMARY)
                ->from(static::TABLE)
                ->where('`name` = \''.pSQL($typeName).'\'')
                ->orderBy('`'.static::PRIMARY.'` ASC')
        );
    }

    /**
     * Ensures the given image type is associated with all provided shops.
     *
     * @param int $imageTypeId
     * @param int[] $shopIds
     *
     * @return void
     */
    protected static function associateTypeToShops($imageTypeId, array $shopIds)
    {
        if (!$imageTypeId || !$shopIds) {
            return;
        }

        $rows = [];
        foreach ($shopIds as $shopId) {
            $rows[] = [
                static::PRIMARY => (int) $imageTypeId,
                'id_shop'       => (int) $shopId,
            ];
        }
        Db::getInstance()->insert(static::SHOP_TABLE, $rows, false, true, Db::INSERT_IGNORE);
    }

    /**
     * Merges duplicate reserved image types into one canonical record.
     *
     * @param string $typeName
     * @param int $canonicalId
     *
     * @return void
     */
    protected static function mergeDuplicateBasicTypes($typeName, $canonicalId)
    {
        $duplicates = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select(static::PRIMARY)
                ->from(static::TABLE)
                ->where('`name` = \''.pSQL($typeName).'\'')
                ->where('`'.static::PRIMARY.'` != '.(int) $canonicalId)
                ->orderBy('`'.static::PRIMARY.'` ASC')
        );

        if (!is_array($duplicates) || !$duplicates) {
            return;
        }

        foreach ($duplicates as $duplicate) {
            $duplicateId = (int) $duplicate[static::PRIMARY];
            if (!$duplicateId) {
                continue;
            }

            $shopIds = array_map('intval', array_column(
                Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('id_shop')
                        ->from(static::SHOP_TABLE)
                        ->where('`'.static::PRIMARY.'` = '.(int) $duplicateId)
                ) ?: [],
                'id_shop'
            ));

            static::associateTypeToShops($canonicalId, $shopIds);

            Db::getInstance()->delete(static::SHOP_TABLE, '`'.static::PRIMARY.'` = '.(int) $duplicateId);
            Db::getInstance()->delete(static::TABLE, '`'.static::PRIMARY.'` = '.(int) $duplicateId);
        }
    }

    /**
     * Clears in-memory lookup caches after structural changes.
     *
     * @return void
     */
    protected static function clearCaches()
    {
        static::$imagesTypesCache = [];
        static::$imagesTypesNameCache = [];
    }
}
