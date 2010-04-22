<?php

/**
 * W3 Total Cache Minify module
 */
define('W3TC_IN_MINIFY', true);

if (! defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

if (! defined('W3TC_DIR')) {
    define('W3TC_DIR', realpath(dirname(__FILE__) . '/../plugins/w3-total-cache'));
}

if (! is_dir(W3TC_DIR) || ! file_exists(W3TC_DIR . '/inc/define.php')) {
    @header('X-Robots-Tag: noarchive, noodp, nosnippet');
    die(sprintf('<strong>W3 Total Cache Error:</strong> some files appear to be missing or out of place. Please re-install plugin or remove <strong>%s</strong>.', dirname(__FILE__)));
}

require_once W3TC_DIR . '/inc/define.php';
require_once W3TC_DIR . '/lib/W3/Minify.php';

$w3_minify = & W3_Minify::instance();
$w3_minify->process();
