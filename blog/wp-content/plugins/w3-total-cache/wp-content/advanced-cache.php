<?php

/**
 * W3 Total Cache advanced cache module
 */
if (! defined('W3TC_IN_MINIFY')) {
    if (! defined('W3TC_DIR')) {
        define('W3TC_DIR', WP_CONTENT_DIR . '/plugins/w3-total-cache');
    }
    
    if (! is_dir(W3TC_DIR) || ! file_exists(W3TC_DIR . '/inc/define.php')) {
        @header('X-Robots-Tag: noarchive, noodp, nosnippet');
        die(sprintf('<strong>W3 Total Cache Error:</strong> some files appear to be missing or out of place. Please re-install plugin or remove <strong>%s</strong>.', __FILE__));
    }
    
    require_once W3TC_DIR . '/inc/define.php';
    require_once W3TC_DIR . '/lib/W3/PgCache.php';
    
    $w3_pgcache = & W3_PgCache::instance();
    $w3_pgcache->process();
}