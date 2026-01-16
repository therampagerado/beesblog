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

use BeesBlog;
use Employee;
use Context;
use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use Shop;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogPost
 */
class BeesBlogPost extends ObjectModel
{
    const PRIMARY    = 'id_bees_blog_post';
    const TABLE      = 'bees_blog_post';
    const LANG_TABLE = 'bees_blog_post_lang';
    const SHOP_TABLE = 'bees_blog_post_shop';
    const IMAGE_TYPE = 'beesblog_post';

    /**
     * @var array Contains object definition
     */
    public static $definition = [
        'table'          => self::TABLE,
        'primary'        => self::PRIMARY,
        'multilang'      => true,
        'multishop'      => true,
        'fields'         => [
            'active'            => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool',        'required' => true,                                      'db_type' => 'TINYINT(1) UNSIGNED'],
            'comments_enabled'  => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool',        'required' => true,                                      'db_type' => 'TINYINT(1) UNSIGNED'],
            'date_add'          => ['type' => self::TYPE_DATE,                   'validate' => 'isDate',        'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'date_upd'          => ['type' => self::TYPE_DATE,                   'validate' => 'isDate',        'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'published'         => ['type' => self::TYPE_DATE,                   'validate' => 'isDate',        'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'id_category'       => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true,                                      'db_type' => 'INT(11) UNSIGNED'],
            'id_employee'       => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true,                                      'db_type' => 'INT(11) UNSIGNED'],
            'image'             => ['type' => self::TYPE_STRING,                 'validate' => 'isString',      'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'position'          => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true,  'default' => '1',                   'db_type' => 'INT(11) UNSIGNED'],
            'post_type'         => ['type' => self::TYPE_STRING,                 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(45)',  'size' => 45],
            'viewed'            => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true,  'default' => '0',                   'db_type' => 'INT(20) UNSIGNED'],
            'title'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(255)'],
            'content'           => ['type' => self::TYPE_HTML,   'lang' => true, 'validate' => 'isString',      'required' => false,                                     'db_type' => 'TEXT'],
            'link_rewrite'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(255)'],
            'meta_title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(128)'],
            'meta_description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'meta_keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'lang_active'       => ['type' => self::TYPE_BOOL,   'lang' => true, 'validate' => 'isBool', 'db_type' => 'TINYINT(1) UNSIGNED'],
        ],
    ];

    /**
     * @var bool $active
     */
    public $active = true;

    /**
     * @var int $id_employee
     */
    public $id_employee;

    /**
     * @var int $id_category
     */
    public $id_category;

    /**
     * @var int $position
     */
    public $position = 0;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var string $date_upd
     */
    public $date_upd;

    /**
     * @var string $published
     */
    public $published;

    /**
     * @var int $viewed
     */
    public $viewed;

    /**
     * @var bool $comments_enabled
     */
    public $comments_enabled = true;

    /**
     * @var int $post_type
     */
    public $post_type;

    /**
     * @var string|string[] $title
     */
    public $title;

    /**
     * @var string $image
     */
    public $image;

    /**
     * @var string|string[] $content
     */
    public $content;

    /**
     * @var string|string[] $link_rewrite
     */
    public $link_rewrite;

    /**
     * @var string|string[] $meta_title
     */
    public $meta_title;

    /**
     * @var string|string[] $meta_description
     */
    public $meta_description;

    /**
     * @var string|string[] $meta_keywords
     */
    public $meta_keywords;

    /**
     * @var bool|bool[] $lang_active
     */
    public $lang_active;

    /**
     * @var array $imageTypes Default image types
     */
    public static $imageTypes = ['post_default', 'post_list_item'];

    /**
     * @var BeesBlogCategory
     */
    public $category;

    /**
     * @var Employee
     */
    public $employee;

    /**
     * @var string|string[]
     */
    public $link;

    /**
     * BeesBlogPost constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);

        $this->image_dir = _PS_IMG_DIR_ . '/beesblog/' . static::IMAGE_TYPE;

        $this->resolveAssociations($idLang, $idShop);
    }

    /**
     * @param array $row
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hydrate(array $row, $idLang = null)
    {
        parent::hydrate($row, $idLang);
        $this->resolveAssociations($idLang, $this->id_shop);
    }

    /**
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function resolveAssociations($idLang, $idShop)
    {
        $this->employee = new Employee((int)$this->id_employee);
        $this->category = new BeesBlogCategory((int)$this->id_category, $idLang, $idShop);
        if ($idLang) {
            // single language context
            if (is_string($this->link_rewrite)) {
                $this->link = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $this->link_rewrite], $idShop, $idLang);
            } else {
                $this->link = '';
            }
        } else {
            // multiple language context
            $this->link = [];
            if (is_array($this->link_rewrite)) {
                foreach ($this->link_rewrite as $lang => $rewrite) {
                    $this->link[$lang] = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $rewrite], $idShop, $lang);
                }
            }
        }
    }

    /**
     * Get posts
     *
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     *
     * @return array|int
     * @throws PrestaShopException
     */
    public static function getPosts($idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        $idLang = $idLang ?: (int) Context::getContext()->language->id;
        $query = static::buildPostsQuery($idLang)
            ->orderBy('p.`published` DESC');

        if ($count) {
            $countQuery = static::buildPostsQuery($idLang, 'COUNT(DISTINCT p.`'.self::PRIMARY.'`)', false);
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($countQuery);
        }

        $rows = static::fetchPagedIds($query, $page, $limit);
        $results = static::hydratePostsFromRows($rows, $idLang);
        static::filterCollectionResults($results, $raw, $propertyFilter);

        return $results;
    }

    /**
     * Get posts by popularity
     *
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     *
     * @return array|int
     * @throws PrestaShopException
     */
    public static function getPopularPosts($idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        $idLang = $idLang ?: (int) Context::getContext()->language->id;
        $query = static::buildPostsQuery($idLang)
            ->orderBy('p.`viewed` DESC');

        if ($count) {
            $countQuery = static::buildPostsQuery($idLang, 'COUNT(DISTINCT p.`'.self::PRIMARY.'`)', false);
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($countQuery);
        }

        $rows = static::fetchPagedIds($query, $page, $limit);
        $results = static::hydratePostsFromRows($rows, $idLang);
        static::filterCollectionResults($results, $raw, $propertyFilter);

        return $results;
    }

    /**
     * Increment view count
     *
     * @param int $idBeesBlogPost BeesBlogPost ID
     *
     * @return bool Whether view count has been successfully incremented
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function viewed($idBeesBlogPost)
    {
        $sql = 'UPDATE '._DB_PREFIX_.'bees_blog_post as p SET p.viewed = (p.viewed+1) where p.id_bees_blog_post = '.(int) $idBeesBlogPost;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get blog image
     *
     * @return false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getBlogImage()
    {
        $sql = (new DbQuery())
            ->select('p.`'.self::PRIMARY.'`')
            ->from(self::TABLE, 'p')
            ->join(Shop::addSqlAssociation(self::TABLE, 'p'));

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get BeesBlogPost rewrite by ID
     *
     * @param int|null $idPost BeesBlogPost ID
     * @param int|null $idLang Language ID
     *
     * @return false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getPostRewriteById($idPost, $idLang = null)
    {
        if ($idLang == null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        $idShop = (int) Context::getContext()->shop->id;

        $sql = new DbQuery();
        $sql->select('sbp.`link_rewrite`');
        $sql->from(self::TABLE, 'sbp');
        $sql->innerJoin(self::LANG_TABLE, 'sbpl', 'sbp.`'.self::PRIMARY.'` = sbpl.`'.self::PRIMARY.'`');
        $sql->join(Shop::addSqlAssociation(self::TABLE, 'sbp'));
        $sql->where('sbpl.`id_lang` = '.(int) $idLang);
        $sql->where('sbpl.`lang_active` = 1');
        $sql->where('`'.self::TABLE.'_shop`.`id_shop` = '.(int) $idShop);
        $sql->where('sbp.`active` = 1');
        $sql->where('sbp.`'.self::PRIMARY.'` = '.(int) $idPost);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get BeesBlogPost ID by rewrite
     *
     * @param string $rewrite Rewrite
     * @param bool $active Active
     * @param int|null $idLang Language ID
     * @param int|null $idShop Shop ID
     *
     * @return bool|false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdByRewrite($rewrite, $active = true, $idLang = null, $idShop = null)
    {
        if (empty($rewrite)) {
            return false;
        }
        if (empty($idLang)) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if (empty($idShop)) {
            $idShop = (int) Context::getContext()->shop->id;
        }
        $sql = new DbQuery();
        $sql->select('sbp.`'.self::PRIMARY.'`');
        $sql->from(self::TABLE, 'sbp');
        $sql->innerJoin(self::LANG_TABLE, 'sbpl', 'sbp.`'.self::PRIMARY.'` = sbpl.`'.self::PRIMARY.'`');
        $sql->join(Shop::addSqlAssociation(self::TABLE, 'sbp'));
        $sql->where('sbpl.`id_lang` = '.(int) $idLang);
        $sql->where('`'.self::TABLE.'_shop`.`id_shop` = '.(int) $idShop);
        $sql->where('sbp.`active` = '.(int) $active);
        $sql->where('sbpl.`lang_active`');
        $sql->where('sbpl.`link_rewrite` = \''.pSQL($rewrite).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * @param int $idBeesBlogPost BeesBlogPost ID
     * @param int $idLang Language ID
     *
     * @return false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getLangActive($idBeesBlogPost, $idLang)
    {
        $sql = new DbQuery();
        $sql->select('sbpl.`lang_active`');
        $sql->from(static::LANG_TABLE, 'sbpl');
        $sql->where('sbpl.`'.static::PRIMARY.'` = '.(int) $idBeesBlogPost);
        $sql->where('sbpl.`id_lang` = '.(int) $idLang);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * @param int $idBeesBlogPost BeesBlogPost ID
     * @param array $langActive
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setLangActive($idBeesBlogPost, $langActive)
    {
        if (!is_array($langActive)) {
            return;
        }

        foreach ($langActive as $idLang => $active) {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->update(
                static::LANG_TABLE,
                [
                    'lang_active' => $active,
                ],
                static::PRIMARY.' = '.(int) $idBeesBlogPost.' AND id_lang = '.(int) $idLang
            );
        }
    }

    /**
     * Get summary of content
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getSummary()
    {
        $content = strip_tags($this->content);
        if (mb_strlen($content) < 512) {
            return $content;
        }
        return mb_substr($content, 0, 512).' [...]';
    }

    /**
     * Get local image path
     *
     * @param int    $id
     * @param string $type
     *
     * @return string
     */
    public static function getImagePath($id, $type = 'post_default')
    {
        $baseLocation = _PS_IMG_DIR_.'beesblog/posts/';

        if ($type === 'original') {
            if (file_exists("{$baseLocation}{$id}.png")) {
                return "{$baseLocation}{$id}.png";
            } else {
                return "{$baseLocation}{$id}.jpg";
            }
        }

        if (file_exists("{$baseLocation}{$id}-{$type}.png")) {
            return "{$baseLocation}{$id}-{$type}.png";
        } else {
            return "{$baseLocation}{$id}-{$type}.jpg";
        }
    }

    /**
     * Create the database tables for BeesBlogPost model
     *
     * @param string|null $className Class name
     * @return bool Indicates whether the database was successfully added
     * @throws PrestaShopException
     */
    public static function createDatabase($className = null)
    {
        return (
            parent::createDatabase($className) &&
            static::createShopTable() &&
            static::createRelatedProductsTable()
        );
    }

    /**
     * Drop the database for BeesBlogPost model
     *
     * @param string|null $className Class name
     * @return bool Indicates whether the database was successfully dropped
     * @throws PrestaShopException
     */
    public static function dropDatabase($className = null)
    {
        return (
            parent::dropDatabase($className) &&
            static::dropShopTable() &&
            static::dropRelatedProductsTable()
        );
    }

    /**
     * Creates database table to store related products
     *
     * @return boolean
     * @throws PrestaShopException
     */
    public static function createRelatedProductsTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_post_product` (
               `id_product`        INT(11) UNSIGNED NOT NULL,
               `id_bees_blog_post` INT(11) UNSIGNED NOT NULL,
               PRIMARY KEY (`id_product`, `id_bees_blog_post`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    /**
     * Drop related products database table
     *
     * @return boolean
     * @throws PrestaShopException
     */
    public static function dropRelatedProductsTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'bees_blog_post_product`');
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    protected static function createShopTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_post_shop` (
               `id_bees_blog_post` INT(11) UNSIGNED NOT NULL,
               `id_shop` INT(11) UNSIGNED NOT NULL,
               PRIMARY KEY (`id_bees_blog_post`, `id_shop`),
               KEY `id_shop` (`id_shop`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    protected static function dropShopTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'bees_blog_post_shop`');
    }

    /**
     * @param DbQuery $query
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    protected static function fetchPagedIds(DbQuery $query, $page, $limit)
    {
        if ($limit > 0) {
            $page = (int) $page;
            $offset = max($page - 1, 0) * (int) $limit;
            $query->limit((int) $limit, $offset);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * @param int $idLang
     * @param string|null $select
     * @param bool $groupBy
     *
     * @return DbQuery
     */
    protected static function buildPostsQuery($idLang, $select = null, $groupBy = true)
    {
        $query = new DbQuery();
        if ($select) {
            $query->select($select);
        } else {
            $query->select('DISTINCT p.`'.self::PRIMARY.'`');
        }
        $query->from(self::TABLE, 'p');
        $query->innerJoin(self::LANG_TABLE, 'pl', 'p.`'.self::PRIMARY.'` = pl.`'.self::PRIMARY.'`');
        $query->join(Shop::addSqlAssociation(self::TABLE, 'p'));
        $query->where('pl.`id_lang` = '.(int) $idLang);
        $query->where('pl.`lang_active` = 1');
        $query->where('p.`published` <= \''.pSQL(date('Y-m-d H:i:s')).'\'');
        $query->where('p.`active` = 1');
        if ($groupBy) {
            $query->groupBy('p.`'.self::PRIMARY.'`');
        }

        return $query;
    }

    /**
     * @param array $rows
     * @param int $idLang
     *
     * @return BeesBlogPost[]
     * @throws PrestaShopException
     */
    protected static function hydratePostsFromRows(array $rows, $idLang)
    {
        $results = [];
        $idShop = (int) Context::getContext()->shop->id;
        foreach ($rows as $row) {
            $id = (int) $row[self::PRIMARY];
            if ($id) {
                $results[] = new BeesBlogPost($id, $idLang, $idShop);
            }
        }

        return $results;
    }

    /**
     * @param array $results
     * @param bool $raw
     * @param array $propertyFilter
     */
    protected static function filterCollectionResults(&$results, $raw, $propertyFilter)
    {
        if ($raw) {
            $newResults = [];
            foreach ($results as $result) {
                if (!empty($propertyFilter)) {
                    $newPost = [];
                    foreach ($propertyFilter as $filter) {
                        $newPost[$filter] = $result->{$filter};
                    }
                    $newResults[] = $newPost;
                } else {
                    $newResults[] = (array) $result;
                }
            }
            $results = $newResults;
        }
    }
}
