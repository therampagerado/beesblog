<?php
/** Back Office responsive settings and validation smoke checks. */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_responsive_admin_smoke.php <thirty-bees-root>\n");
    exit(1);
}

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', $root.'/admin-dev');
}
require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';
require_once $root.'/modules/beesblog/controllers/admin/AdminBeesBlogImagesController.php';

use BeesBlogModule\BeesBlogResponsiveImage;

function assertResponsiveAdmin($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

try {
    $employeeId = (int) Db::getInstance()->getValue(
        'SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` ORDER BY `id_employee` ASC'
    );
    Context::getContext()->employee = new Employee($employeeId);
    $controller = new AdminBeesBlogImagesController();
    assertResponsiveAdmin(
        isset($controller->fields_options['responsive']['fields'][BeesBlogResponsiveImage::CONFIG_WIDTHS]),
        'Images page exposes merchant-configurable responsive widths'
    );
    assertResponsiveAdmin(
        isset($controller->fields_options['regenerate']['submit']),
        'Images page keeps a regeneration action for applying changed widths'
    );

    $_POST = [BeesBlogResponsiveImage::CONFIG_WIDTHS => '768, 320, 480, 768'];
    $controller->beforeUpdateOptions();
    assertResponsiveAdmin(
        $_POST[BeesBlogResponsiveImage::CONFIG_WIDTHS] === '320,480,768',
        'Back Office validation stores a sorted, deduplicated list'
    );

    $invalidController = new AdminBeesBlogImagesController();
    $_POST = [BeesBlogResponsiveImage::CONFIG_WIDTHS => '320,invalid'];
    $invalidController->beforeUpdateOptions();
    assertResponsiveAdmin(
        !empty($invalidController->errors),
        'Back Office validation rejects malformed widths before Configuration is updated'
    );

    echo "RESULT: responsive Back Office smoke checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
} finally {
    $_POST = [];
}
