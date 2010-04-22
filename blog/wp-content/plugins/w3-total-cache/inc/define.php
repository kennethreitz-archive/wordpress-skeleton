<?php

define('W3TC_VERSION', '0.8.5.2');
define('W3TC_POWERED_BY', 'W3 Total Cache/' . W3TC_VERSION);
define('W3TC_EMAIL', 'w3tc@w3-edge.com');
define('W3TC_LINK_URL', 'http://www.w3-edge.com/wordpress-plugins/');
define('W3TC_LINK_NAME', 'WordPress Plugins');
define('W3TC_FEED_URL', 'http://feeds.feedburner.com/W3TOTALCACHE');
define('W3TC_README_URL', 'http://plugins.trac.wordpress.org/browser/w3-total-cache/trunk/readme.txt?format=txt');
define('W3TC_TWITTER_STATUS', 'I just optimized my #wordpress blog\'s #performance using the W3 Total Cache #plugin by @w3edge. Check it out! http://j.mp/A69xX');
define('W3TC_SUPPORT_US_TIMEOUT', 2592000);

define('W3TC_PHP5', PHP_VERSION >= 5);
define('W3TC_WIN', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'));

defined('W3TC_DIR') || define('W3TC_DIR', realpath(dirname(__FILE__) . '/..'));
define('W3TC_FILE', 'w3-total-cache/w3-total-cache.php');
define('W3TC_LIB_DIR', W3TC_DIR . '/lib');
define('W3TC_LIB_W3_DIR', W3TC_LIB_DIR . '/W3');
define('W3TC_LIB_MINIFY_DIR', W3TC_LIB_DIR . '/Minify');
define('W3TC_PLUGINS_DIR', W3TC_DIR . '/plugins');
define('W3TC_INSTALL_DIR', W3TC_DIR . '/wp-content');
define('W3TC_INSTALL_MINIFY_DIR', W3TC_INSTALL_DIR . '/w3tc/min');

$GLOBALS['w3_reserved_blognames'] = array(
    'page', 
    'comments', 
    'blog', 
    'wp-admin', 
    'wp-includes', 
    'wp-content', 
    'files', 
    'feed'
);

define('W3TC_BLOGNAME', w3_get_blogname());
define('W3TC_PREFIX', (W3TC_BLOGNAME != '' ? '-' . W3TC_BLOGNAME : ''));

defined('WP_CONTENT_DIR') || define('WP_CONTENT_DIR', realpath(W3TC_DIR . '/../..'));
define('WP_CONTENT_DIR_NAME', basename(WP_CONTENT_DIR));
define('W3TC_CONTENT_DIR_NAME', WP_CONTENT_DIR_NAME . '/w3tc' . W3TC_PREFIX);
define('W3TC_CONTENT_DIR', ABSPATH . W3TC_CONTENT_DIR_NAME);
define('W3TC_CONTENT_MINIFY_DIR_NAME', W3TC_CONTENT_DIR_NAME . '/min');
define('W3TC_CONTENT_MINIFY_DIR', ABSPATH . W3TC_CONTENT_DIR_NAME . '/min');
define('W3TC_CACHE_FILE_DBCACHE_DIR', W3TC_CONTENT_DIR . '/dbcache');
define('W3TC_CACHE_FILE_PGCACHE_DIR', W3TC_CONTENT_DIR . '/pgcache');
define('W3TC_CACHE_FILE_MINIFY_DIR', W3TC_CONTENT_DIR . '/min');
define('W3TC_LOG_DIR', W3TC_CONTENT_DIR . '/log');
define('W3TC_TMP_DIR', W3TC_CONTENT_DIR . '/tmp');
define('W3TC_CONFIG_PATH', WP_CONTENT_DIR . '/w3-total-cache-config' . W3TC_PREFIX . '.php');
define('W3TC_CONFIG_MASTER_PATH', WP_CONTENT_DIR . '/w3-total-cache-config.php');
define('W3TC_MINIFY_LOG_FILE', W3TC_LOG_DIR . '/minify.log');
define('W3TC_CDN_COMMAND_UPLOAD', 1);
define('W3TC_CDN_COMMAND_DELETE', 2);
define('W3TC_CDN_TABLE_QUEUE', 'w3tc_cdn_queue');

ini_set('pcre.backtrack_limit', 4194304);
ini_set('pcre.recursion_limit', 4194304);

/**
 * W3 activate error
 * 
 * @param string $error
 * @return void
 */

function w3_activate_error($error)
{
    $active_plugins = (array) get_option('active_plugins');
    
    $key = array_search(W3TC_FILE, $active_plugins);
    
    if ($key !== false) {
        do_action('deactivate_plugin', W3TC_FILE);
        
        array_splice($active_plugins, $key, 1);
        
        do_action('deactivate_' . W3TC_FILE);
        do_action('deactivated_plugin', W3TC_FILE);
        
        update_option('active_plugins', $active_plugins);
    } else {
        do_action('deactivate_' . W3TC_FILE);
    }
    
    include W3TC_DIR . '/inc/error.phtml';
    exit();
}

/**
 * W3 writable error
 *
 * @param string $path
 * @return string
 */
function w3_writable_error($path)
{
    $activate_url = wp_nonce_url('plugins.php?action=activate&plugin=' . W3TC_FILE, 'activate-plugin_' . W3TC_FILE);
    $reactivate_button = sprintf('<input type="button" value="re-activate plugin" onclick="top.location.href = \'%s\'" />', addslashes($activate_url));
    
    if (w3_check_open_basedir($path)) {
        if (file_exists($path)) {
            $error = sprintf('<strong>%s</strong> is not write-able, please run following command:<br /><strong style="color: #f00;">chmod 777 %s</strong><br />then %s.', $path, $path, $reactivate_button);
        } else {
            $error = sprintf('<strong>%s</strong> could not be created, please run following command:<br /><strong style="color: #f00;">chmod 777 %s</strong><br />then %s.', $path, dirname($path), $reactivate_button);
        }
    } else {
        $error = sprintf('<strong>%s</strong> could not be created, <strong>open_basedir</strong> restriction in effect, please check your php.ini settings:<br /><strong style="color: #f00;">open_basedir = "%s"</strong><br />then %s.', $path, ini_get('open_basedir'), $reactivate_button);
    }
    
    w3_activate_error($error);
}

/**
 * Returns current microtime
 *
 * @return float
 */
function w3_microtime()
{
    list ($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

/**
 * Check if URL is valid
 *
 * @param string $url
 * @return boolean
 */
function w3_is_url($url)
{
    return preg_match('~^https?://~', $url);
}

/**
 * Decodes gzip-encoded string
 *
 * @param string $data
 * @return string
 */
function w3_gzdecode($data)
{
    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;
    
    if ($flags & 4) {
        $extralen = unpack('v', substr($data, 10, 2));
        $extralen = $extralen[1];
        $headerlen += 2 + $extralen;
    }
    
    if ($flags & 8) {
        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    }
    
    if ($flags & 16) {
        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    }
    
    if ($flags & 2) {
        $headerlen += 2;
    }
    
    $unpacked = gzinflate(substr($data, $headerlen));
    
    if ($unpacked === FALSE) {
        $unpacked = $data;
    }
    
    return $unpacked;
}

/**
 * Recursive creates directory
 *
 * @param string $path
 * @param integer $mask
 * @param string
 * @return boolean
 */
function w3_mkdir($path, $mask = 0755, $curr_path = '')
{
    $path = w3_realpath($path);
    $path = trim($path, '/');
    $dirs = explode('/', $path);
    
    foreach ($dirs as $dir) {
        if (empty($dir)) {
            return false;
        }
        
        $curr_path .= ($curr_path == '' ? '' : '/') . $dir;
        
        if (! is_dir($curr_path)) {
            if (@mkdir($curr_path, $mask)) {
                @chmod($curr_path, $mask);
            } else {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Recursive remove dir
 * 
 * @param string $path
 * @param array $exclude
 * @return void
 */
function w3_rmdir($path, $exclude = array(), $remove = true)
{
    $dir = @opendir($path);
    
    if ($dir) {
        while (($entry = @readdir($dir))) {
            $full_path = $path . '/' . $entry;
            
            if ($entry != '.' && $entry != '..' && ! in_array($full_path, $exclude)) {
                if (is_dir($full_path)) {
                    w3_rmdir($full_path, $exclude);
                } else {
                    @unlink($full_path);
                }
            }
        }
        
        @closedir($dir);
        
        if ($remove) {
            @rmdir($path);
        }
    }
}

/**
 * Recursive empty dir
 * 
 * @param string $path
 * @param array $exclude
 * @return void
 */
function w3_emptydir($path, $exclude = array())
{
    w3_rmdir($path, $exclude, false);
}

/**
 * Check if content is HTML or XML
 *
 * @param string $content
 * @return boolean
 */
function w3_is_xml($content)
{
    return (stristr($content, '<?xml') !== false || stristr($content, '<html') !== false);
}

/**
 * Returns true if it's WPMU
 * @return boolean
 */
function w3_is_wpmu()
{
    static $wpmu = null;
    
    if ($wpmu === null) {
        $wpmu = (w3_is_vhost() || file_exists(ABSPATH . 'wpmu-settings.php'));
    }
    
    return $wpmu;
}

/**
 * Returns true if WPMU uses vhosts
 * @return boolean
 */
function w3_is_vhost()
{
    return (defined('VHOST') && VHOST == 'yes');
}

/**
 * Detect WPMU blogname
 *
 * @return string
 */
function w3_get_blogname()
{
    static $blogname = null;
    
    if ($blogname === null) {
        if (w3_is_wpmu()) {
            $domain = w3_get_domain($_SERVER['HTTP_HOST']);
            
            if (w3_is_vhost()) {
                $blogname = $domain;
            } else {
                $uri = $_SERVER['REQUEST_URI'];
                $site_path = w3_get_site_path();
                
                if ($site_path != '' && strpos($uri, $site_path) === 0) {
                    $uri = substr_replace($uri, '/', 0, strlen($site_path));
                }
                
                $blogname = w3_get_blogname_from_uri($uri);
                
                if ($blogname != '') {
                    $blogname = $blogname . '.' . $domain;
                } else {
                    $blogname = $domain;
                }
            }
        } else {
            $blogname = '';
        }
    }
    
    return $blogname;
}

/**
 * Returns blogname from URI
 * 
 * @param string $uri
 * @param string
 */
function w3_get_blogname_from_uri($uri)
{
    global $w3_reserved_blognames;
    
    $blogname = '';
    $matches = null;
    $uri = strtolower($uri);
    
    if (preg_match('~^/([a-z0-9-]+)/~', $uri, $matches)) {
        $blogname = $matches[1];
        
        if (in_array($blogname, $w3_reserved_blognames) || file_exists($blogname)) {
            $blogname = '';
        }
    }
    
    return $blogname;
}

/**
 * Returns site url
 *
 * @return string
 */
function w3_get_site_url()
{
    static $site_url = null;
    
    if ($site_url === null) {
        if (function_exists('get_option')) {
            $site_url = get_option('siteurl');
        } else {
            $site_url = sprintf('http://%s%s', $_SERVER['HTTP_HOST'], w3_get_site_path());
        }
        
        $site_url = rtrim($site_url, '/');
    }
    
    return $site_url;
}

/**
 * Returns SSL site url
 *
 * @return string
 */
function w3_get_site_url_ssl()
{
    $site_url = w3_get_site_url();
    
    if (w3_is_https()) {
        $site_url = str_replace('http:', 'https:', $site_url);
    }
    
    return $site_url;
}

/**
 * Returns site url regexp
 *
 * @return string
 */
function w3_get_site_url_regexp()
{
    $site_url = w3_get_site_url();
    $domain = preg_replace('~https?://~i', '', $site_url);
    $regexp = 'https?://' . w3_preg_quote($domain);
    return $regexp;
}

/**
 * Returns blog path
 *
 * @return string
 */
function w3_get_site_path()
{
    $document_root = w3_get_document_root();
    $path = str_replace($document_root, '', w3_path(ABSPATH));
    $path = '/' . ltrim($path, '/');
    
    if (substr($path, - 1) != '/') {
        $path .= '/';
    }
    
    return $path;
}

/**
 * Returns blog path
 * 
 * @return string
 */
function w3_get_blog_path()
{
    $domain_url = w3_get_domain_url();
    $site_url = w3_get_site_url();
    
    $path = str_replace($domain_url, '', $site_url);
    $path = '/' . ltrim($path, '/');
    
    if (substr($path, - 1) != '/') {
        $path .= '/';
    }
    
    return $path;
}

/**
 * Returns document root
 * @return string
 */
function w3_get_document_root()
{
    static $document_root = null;
    
    if ($document_root === null) {
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $document_root = $_SERVER['DOCUMENT_ROOT'];
        } elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
            $document_root = substr($_SERVER['SCRIPT_FILENAME'], 0, - strlen($_SERVER['PHP_SELF']));
        } elseif (isset($_SERVER['PATH_TRANSLATED'])) {
            $document_root = substr($_SERVER['PATH_TRANSLATED'], 0, - strlen($_SERVER['PHP_SELF']));
        } else {
            $document_root = ABSPATH;
        }
        
        $document_root = w3_path($document_root);
    }
    
    return $document_root;
}

/**
 * Returns domain from host
 *
 * @param string $host
 * @return string
 */
function w3_get_domain($host)
{
    $host = strtolower($host);
    
    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }
    
    if (($pos = strpos($host, ':')) !== false) {
        $host = substr($host, 0, $pos);
    }
    
    $host = rtrim($host, '.');
    
    return $host;
}

/**
 * Get domain URL
 *
 * @return string
 */
function w3_get_domain_url()
{
    $site_url = w3_get_site_url();
    $parse_url = @parse_url($site_url);
    
    if ($parse_url && isset($parse_url['scheme']) && isset($parse_url['host'])) {
        $scheme = $parse_url['scheme'];
        $host = $parse_url['host'];
        $port = (isset($parse_url['port']) && $parse_url['port'] != 80 ? ':' . (int) $parse_url['port'] : '');
        $domain_url = sprintf('%s://%s%s', $scheme, $host, $port);
        
        return $domain_url;
    }
    
    return false;
}

/**
 * Returns domain url regexp
 *
 * @return string
 */
function w3_get_domain_url_regexp()
{
    $domain_url = w3_get_domain_url();
    $domain = preg_replace('~https?://~i', '', $domain_url);
    $regexp = 'https?://' . w3_preg_quote($domain);
    return $regexp;
}

/**
 * Returns upload info
 *
 * @return array
 */
function w3_upload_info()
{
    static $upload_info = null;
    
    if ($upload_info === null) {
        $upload_info = @wp_upload_dir();
        
        if (empty($upload_info['error'])) {
            $site_url = w3_get_site_url();
            $upload_info['upload_url'] = trim(str_replace($site_url, '', $upload_info['baseurl']), '/');
            $upload_info['upload_dir'] = trim(str_replace(ABSPATH, '', $upload_info['basedir']), '/');
        } else {
            $upload_info = false;
        }
    }
    
    return $upload_info;
}

/**
 * Redirects to URL
 * 
 * @param string $url
 * @param string $params
 * @return string
 */
function w3_redirect($url = '', $params = array())
{
    $fragment = '';
    
    if ($url != '' && ($parse_url = @parse_url($url))) {
        $url = '';
        
        if (! empty($parse_url['scheme'])) {
            $url .= $parse_url['scheme'] . '://';
        }
        
        if (! empty($parse_url['user'])) {
            $url .= $parse_url['user'];
            
            if (! empty($parse_url['pass'])) {
                $url .= ':' . $parse_url['pass'];
            }
        }
        
        if (! empty($parse_url['host'])) {
            $url .= $parse_url['host'];
        }
        
        if (! empty($parse_url['port']) && $parse_url['port'] != 80) {
            $url .= ':' . (int) $parse_url['port'];
        }
        
        $url .= (! empty($parse_url['path']) ? $parse_url['path'] : '/');
        
        if (! empty($parse_url['query'])) {
            $old_params = array();
            parse_str($parse_url['query'], $old_params);
            
            $params = array_merge($old_params, $params);
        }
        
        if (! empty($parse_url['fragment'])) {
            $fragment = '#' . $parse_url['fragment'];
        }
    } else {
        $parse_url = array();
    }
    
    if (($count = count($params))) {
        $query = '';
        
        foreach ($params as $param => $value) {
            $count--;
            $query .= urlencode($param) . (! empty($value) ? '=' . urlencode($value) : '') . ($count ? '&' : '');
        }
        
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }
    
    if ($fragment != '') {
        $url .= $fragment;
    }
    
    @header('Location: ' . $url);
    exit();
}

/**
 * Returns caching engine name
 *
 * @param $engine
 * @return string
 */
function w3_get_engine_name($engine)
{
    switch ($engine) {
        case 'memcached':
            $engine_name = 'memcached';
            break;
        
        case 'apc':
            $engine_name = 'apc';
            break;
        
        case 'eaccelerator':
            $engine_name = 'eaccelerator';
            break;
        
        case 'xcache':
            $engine_name = 'xcache';
            break;
        
        case 'file':
            $engine_name = 'disk';
            break;
        
        case 'file_pgcache':
            $engine_name = 'disk (enhanced)';
            break;
        
        default:
            $engine_name = 'N/A';
            break;
    }
    
    return $engine_name;
}

/**
 * Converts value to boolean
 *
 * @param mixed $value
 * @return boolean
 */
function w3_to_boolean($value)
{
    if (is_string($value)) {
        switch (strtolower($value)) {
            case '+':
            case '1':
            case 'y':
            case 'on':
            case 'yes':
            case 'true':
            case 'enabled':
                return true;
            
            case '-':
            case '0':
            case 'n':
            case 'no':
            case 'off':
            case 'false':
            case 'disabled':
                return false;
        }
    }
    
    return (boolean) $value;
}

/**
 * Request URL
 *
 * @param string $method
 * @param string $url
 * @param string $data
 * @param string $auth
 * @return string
 */
function w3_url_request($method, $url, $data = '', $auth = '')
{
    $status = 0;
    $method = strtoupper($method);
    
    if (! function_exists('curl_init')) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, W3TC_POWERED_BY);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        if (! empty($auth)) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
        }
        
        $contents = curl_exec($ch);
        
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
    } else {
        $parse_url = @parse_url($url);
        
        if ($parse_url && isset($parse_url['host'])) {
            $host = $parse_url['host'];
            $port = (isset($parse_url['port']) ? (int) $parse_url['port'] : 80);
            $path = (! empty($parse_url['path']) ? $parse_url['path'] : '/');
            $query = (isset($parse_url['query']) ? $parse_url['query'] : '');
            $request_uri = $path . ($query != '' ? '?' . $query : '');
            
            $request_headers_array = array(
                sprintf('%s %s HTTP/1.1', $method, $request_uri), 
                sprintf('Host: %s', $host), 
                sprintf('User-Agent: %s', W3TC_POWERED_BY), 
                'Connection: close'
            );
            
            if (! empty($data)) {
                $request_headers_array[] = sprintf('Content-Length: %d', strlen($data));
            }
            
            if (! empty($auth)) {
                $request_headers_array[] = sprintf('Authorization: Basic %s', base64_encode($auth));
            }
            
            $request_headers = implode("\r\n", $request_headers_array);
            $request = $request_headers . "\r\n\r\n" . $data;
            
            $fp = @fsockopen($host, $port);
            
            if (! $fp) {
                return false;
            }
            
            $response = '';
            @fputs($fp, $request);
            
            while (! @feof($fp)) {
                $response .= @fgets($fp, 4096);
            }
            
            @fclose($fp);
            
            list ($response_headers, $contents) = explode("\r\n\r\n", $response, 2);
            
            $matches = null;
            
            if (preg_match('~^HTTP/1.[01] (\d+)~', $response_headers, $matches)) {
                $status = (int) $matches[1];
            }
        }
    }
    
    if ($status == 200) {
        return $contents;
    }
    
    return false;
}

/**
 * Download url via GET
 *
 * @param $url
 * @return string
 */
function w3_url_get($url)
{
    return w3_url_request('GET', $url);
}

/**
 * Send POST request to URL
 *
 * @param string $url
 * @param string $data
 * @param string $auth
 * @return string
 */
function w3_url_post($url, $data = '', $auth = '')
{
    return w3_url_request('POST', $url, $data, $auth);
}

/**
 * Downloads data to a file
 * 
 * @param string $url
 * @param string $file
 * @return boolean
 */
function w3_download($url, $file)
{
    $data = w3_url_get($url);
    
    if ($data !== false) {
        return @file_put_contents($file, $data);
    }
    
    return false;
}

/**
 * Loads plugins
 *
 * @return void
 */
function w3_load_plugins()
{
    $dir = @opendir(W3TC_PLUGINS_DIR);
    
    if ($dir) {
        while (($entry = @readdir($dir))) {
            if (strrchr($entry, '.') === '.php') {
                require_once W3TC_PLUGINS_DIR . '/' . $entry;
            }
        }
        @closedir($dir);
    }
}

/**
 * Returns true if current connection is secure
 *
 * @return boolean
 */
function w3_is_https()
{
    switch (true) {
        case (isset($_SERVER['HTTPS']) && w3_to_boolean($_SERVER['HTTPS'])):
        case (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] == 443):
            return true;
    }
    
    return false;
}

/**
 * Converts win path to unix
 * @param string $path
 * @return string
 */
function w3_path($path)
{
    $path = preg_replace('~[/\\\]+~', '/', $path);
    $path = rtrim($path, '/');
    
    return $path;
}

/**
 * Returns realpath of given path
 *
 * @param string $path
 */
function w3_realpath($path)
{
    $path = w3_path($path);
    $parts = explode('/', $path);
    $absolutes = array();
    
    foreach ($parts as $part) {
        if ('.' == $part) {
            continue;
        }
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    
    return implode('/', $absolutes);
}

/**
 * Returns open basedirs
 *
 * @return array
 */
function w3_get_open_basedirs()
{
    $open_basedir_ini = ini_get('open_basedir');
    $open_basedirs = (W3TC_WIN ? preg_split('~[;,]~', $open_basedir_ini) : explode(':', $open_basedir_ini));
    $result = array();
    
    foreach ($open_basedirs as $open_basedir) {
        $open_basedir = trim($open_basedir);
        if ($open_basedir != '') {
            $result[] = w3_realpath($open_basedir);
        }
    }
    
    return $result;
}

/**
 * Checks if path is restricted by open_basedir
 *
 * @param string $path
 * @return boolean
 */
function w3_check_open_basedir($path)
{
    $path = w3_realpath($path);
    $open_basedirs = w3_get_open_basedirs();
    
    if (! count($open_basedirs)) {
        return true;
    }
    
    foreach ($open_basedirs as $open_basedir) {
        if (strstr($path, $open_basedir) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Returns file mime type
 *
 * @param string $file
 * @return string
 */
function w3_get_mime_type($file)
{
    $mime_types = include W3TC_DIR . '/inc/mime.php';
    
    $file_ext = strrchr($file, '.');
    
    if ($file_ext) {
        $file_ext = ltrim($file_ext, '.');
        foreach ($mime_types as $extension => $mime_type) {
            $exts = explode('|', $extension);
            foreach ($exts as $ext) {
                if ($file_ext == $ext) {
                    return $mime_type;
                }
            }
        }
    }
    
    return false;
}

/**
 * Send twitter update status request
 *
 * @param string $username
 * @param string $password
 * @param string $status
 * @param string $error
 * @return string
 */
function w3_twitter_status_update($username, $password, $status, &$error)
{
    $data = sprintf('status=%s', urlencode($status));
    $auth = sprintf('%s:%s', $username, $password);
    
    $xml = w3_url_post('http://twitter.com/statuses/update.xml', $data, $auth);
    
    if ($xml) {
        $matches = null;
        
        if (preg_match('~<id>(\d+)</id>~', $xml, $matches)) {
            return $matches[1];
        } elseif (preg_match('~<error>([^<]+)</error>~', $xml, $matches)) {
            $error = $matches[1];
        } else {
            $error = 'Unknown error.';
        }
    } else {
        $error = 'Unable to send request.';
    }
    
    return false;
}

/**
 * Quotes regular expression string
 * 
 * @param string $regexp
 * @return string
 */
function w3_preg_quote($string, $delimiter = null)
{
    $string = preg_quote($string, $delimiter);
    $string = strtr($string, array(
        ' ' => '\ '
    ));
    
    return $string;
}

/**
 * Converts file path to relative 
 * 
 * @param string $file
 * @return string
 */
function w3_normalize_file($file)
{
    if (w3_is_url($file)) {
        if (strstr($file, '?') === false) {
            $domain_url_regexp = '~' . w3_get_domain_url_regexp() . '~i';
            $file = preg_replace($domain_url_regexp, '', $file);
        }
    } else {
        $abspath = w3_path(ABSPATH);
        $file = w3_path($file);
        $file = str_replace($abspath, '', $file);
    }
    
    $file = ltrim($file, '/');
    
    return $file;
}

/**
 * Translates URL to local path
 * @param string $url
 * @return string
 */
function w3_translate_file($url)
{
    if (! w3_is_url($url)) {
        $url = w3_get_domain_url() . '/' . ltrim($url, '/\\');
    }
    
    $site_url_regexp = '~' . w3_get_site_url_regexp() . '~i';
    
    if (preg_match($site_url_regexp, $url) && strstr($url, '?') === false) {
        $url = preg_replace($site_url_regexp, '', $url);
        $url = w3_get_site_path() . ltrim($url, '/\\');
    }
    
    $url = ltrim($url, '/');
    
    return $url;
}

/**
 * Returns true if zlib output compression is enabled otherwise false
 * 
 * @return boolean
 */
function w3_zlib_output_compression()
{
    return w3_to_boolean(ini_get('zlib.output_compression'));
}

/**
 * Recursive strips slahes from the var
 * 
 * @param mixed $var
 * @return mixed
 */
function w3_stripslashes($var)
{
    if (is_string($var)) {
        return stripslashes($var);
    } elseif (is_array($var)) {
        $var = array_map('w3_stripslashes', $var);
    }
    
    return $var;
}

if (! function_exists('file_put_contents')) {
    if (! defined('FILE_APPEND')) {
        define('FILE_APPEND', 8);
    }
    
    /**
     * Puts contents to the file
     * 
     * @param string $filename
     * @param string $data
     * @param integer $flags
     * @return boolean
     */
    function file_put_contents($filename, $data, $flags = 0)
    {
        $fp = fopen($filename, ($flags & FILE_APPEND ? 'a' : 'w'));
        
        if ($fp) {
            fputs($fp, $data);
            fclose($fp);
            
            return true;
        }
        
        return false;
    }
}

/**
 * Cleanup .htaccess rules
 * @param string $rules
 * @return string
 */
function w3_clean_rules($rules)
{
    $rules = preg_replace('~[\r\n]+~', "\n", $rules);
    $rules = preg_replace('~^\s+~m', '', $rules);
    $rules = trim($rules);
    
    return $rules;
}

/**
 * Send powered by header
 */
function w3_send_x_powered_by()
{
    switch (true) {
        case defined('DOING_AJAX'):
        case defined('DOING_CRON'):
        case defined('APP_REQUEST'):
        case defined('XMLRPC_REQUEST'):
        case defined('WP_ADMIN'):
            return;
    }
    
    @header('X-Powered-By: ' . W3TC_POWERED_BY);
}

if (w3_is_wpmu()) {
    unset($_GET['sitewide']);
}

w3_send_x_powered_by();
