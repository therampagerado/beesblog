<?php
/** Render the module and packaged-theme picture partials through Smarty. */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_responsive_template_smoke.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';

function assertResponsiveTemplate($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

try {
    $data = [
        'modern_type' => 'image/avif',
        'srcset' => '/320.avif 320w, /768.avif 768w',
        'sizes' => '100vw',
        'fallback_url' => '/fallback-768.jpg',
        'width' => 768,
        'height' => 480,
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ];
    Context::getContext()->smarty->assign([
        'responsiveImage' => $data,
        'responsiveAlt' => 'Responsive test',
    ]);
    $moduleRoot = $root.'/modules/beesblog/';
    $partials = [
        'module' => $moduleRoot.'views/templates/front/responsive_image.tpl',
        'Niara package' => $moduleRoot.'themes/niara/modules/beesblog/views/templates/front/responsive_image.tpl',
        'Community package' => $moduleRoot.'themes/community-theme-default/modules/beesblog/views/templates/front/responsive_image.tpl',
        'Warehouse package' => $moduleRoot.'themes/warehouse/modules/beesblog/views/templates/front/responsive_image.tpl',
    ];
    foreach ($partials as $label => $partial) {
        $html = Context::getContext()->smarty->fetch($partial);
        assertResponsiveTemplate(strpos($html, '<picture') !== false, $label.' renders a picture element');
        assertResponsiveTemplate(strpos($html, 'srcset="/320.avif 320w, /768.avif 768w"') !== false, $label.' renders width descriptors');
        assertResponsiveTemplate(strpos($html, 'src="/fallback-768.jpg"') !== false, $label.' renders the legacy fallback');
        assertResponsiveTemplate(strpos($html, 'width="768"') !== false && strpos($html, 'height="480"') !== false, $label.' renders intrinsic dimensions');
        assertResponsiveTemplate(strpos($html, 'fetchpriority="high"') !== false, $label.' renders priority metadata');
    }
    echo "RESULT: responsive template smoke checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
}
