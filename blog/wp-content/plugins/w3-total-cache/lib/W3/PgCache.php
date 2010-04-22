<?php

/**
 * W3 PgCache
 */

/**
 * Class W3_PgCache
 */
class W3_PgCache
{
    /**
     * Advanced cache config
     *
     * @var W3_Config
     */
    var $_config = null;
    
    /**
     * Caching flag
     *
     * @var boolean
     */
    var $_caching = false;
    
    /**
     * Time start
     *
     * @var double
     */
    var $_time_start = 0;
    
    /**
     * Lifetime
     * @var integer
     */
    var $_lifetime = 0;
    
    /**
     * Enhanced mode flag
     * 
     * @var boolean
     */
    var $_enhanced_mode = false;
    
    /**
     * Debug flag
     * 
     * @var boolean
     */
    var $_debug = false;
    
    /**
     * Cache reject reason
     *
     * @var string
     */
    var $cache_reject_reason = '';
    
    /**
     * PHP5 Constructor
     */
    function __construct()
    {
        require_once W3TC_LIB_W3_DIR . '/Config.php';
        
        $this->_config = & W3_Config::instance();
        $this->_debug = $this->_config->get_boolean('pgcache.debug');
        $this->_lifetime = $this->_config->get_integer('pgcache.lifetime');
        $this->_enhanced_mode = ($this->_config->get_string('pgcache.engine') == 'file_pgcache');
    }
    
    /**
     * PHP4 Constructor
     */
    function W3_PgCache()
    {
        $this->__construct();
    }
    
    /**
     * Do cache logic
     */
    function process()
    {
        /**
         * Skip caching for some pages
         */
        switch (true) {
            case defined('DOING_AJAX'):
            case defined('DOING_CRON'):
            case defined('APP_REQUEST'):
            case defined('XMLRPC_REQUEST'):
            case defined('WP_ADMIN'):
                return;
        }
        
        /**
         * Handle mobile redirects
         */
        $mobile_redirect = $this->_config->get_string('pgcache.mobile.redirect');
        
        if ($mobile_redirect != '' && $this->_is_mobile()) {
            w3_redirect($mobile_redirect);
            exit();
        }
        
        /**
         * Do page cache logic
         */
        if ($this->_debug) {
            $this->_time_start = w3_microtime();
        }
        
        $this->_caching = $this->_can_cache();
        
        $compression = $this->_get_compression();
        $page_key = $this->_get_page_key($_SERVER['REQUEST_URI'], $compression);
        
        if ($this->_caching) {
            /**
             * Check if page is cached
             */
            $cache = & $this->_get_cache();
            $data = $cache->get($page_key);
            
            if ($data) {
                /**
                 * Do Bad Behavior check
                 */
                $this->_bad_behavior();
                
                if ($this->_enhanced_mode) {
                    $is_404 = false;
                    $headers = array();
                    $time = $cache->mtime($page_key);
                    $content = $data;
                } else {
                    $is_404 = $data['404'];
                    $headers = $data['headers'];
                    $time = $data['time'];
                    $content = $data['content'];
                }
                
                /**
                 * Calculate content etag
                 */
                $etag = md5($content);
                
                /**
                 * Send headers
                 */
                $this->_send_headers($is_404, $time, $etag, $compression, $headers);
                
                /**
                 * Append debug info
                 */
                if ($this->_debug) {
                    $time_total = w3_microtime() - $this->_time_start;
                    $debug_info = $this->_get_debug_info($page_key, true, '', true, $time_total);
                    $this->_append_content($content, "\r\n\r\n" . $debug_info, $compression);
                }
                
                echo $content;
                exit();
            }
        }
        
        /**
         * Start output buffering
         */
        ob_start(array(
            &$this, 
            'ob_callback'
        ));
    }
    
    /**
     * Output buffering callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        if ($buffer != '') {
            if (w3_is_xml($buffer) && $this->_can_cache2()) {
                $compression = $this->_get_compression();
                $compressions = $this->_get_compressions();
                
                if ($this->_enhanced_mode) {
                    $is_404 = false;
                    $headers = array();
                } else {
                    $is_404 = (function_exists('is_404') ? is_404() : false);
                    $headers = $this->_get_cached_headers();
                }
                
                $time = time();
                $cache = & $this->_get_cache();
                
                foreach ($compressions as $_compression) {
                    $_page_key = $this->_get_page_key($_SERVER['REQUEST_URI'], $_compression);
                    
                    /**
                     * Encode content
                     */
                    switch ($_compression) {
                        case false:
                            $_content = $buffer;
                            break;
                        
                        case 'gzip':
                            $_content = gzencode($buffer);
                            break;
                        
                        case 'deflate':
                            $_content = gzdeflate($buffer);
                            break;
                    }
                    
                    /**
                     * Store cache data
                     */
                    if ($this->_enhanced_mode) {
                        $cache->set($_page_key, $_content);
                    } else {
                        $_data = array(
                            '404' => $is_404, 
                            'headers' => $headers, 
                            'time' => $time, 
                            'content' => $_content
                        );
                        
                        $cache->set($_page_key, $_data, $this->_lifetime);
                    }
                    
                    if ($compression == $_compression) {
                        $page_key = $_page_key;
                        $buffer = $_content;
                    }
                }
                
                /**
                 * Calculate content etag
                 */
                $etag = md5($buffer);
                
                /**
                 * Send headers
                 */
                $this->_send_headers($is_404, $time, $etag, $compression, $headers);
                
                /**
                 * Append debug info
                 */
                if ($this->_debug) {
                    $time_total = w3_microtime() - $this->_time_start;
                    $debug_info = $this->_get_debug_info($page_key, true, '', false, $time_total);
                    $this->_append_content($buffer, "\r\n\r\n" . $debug_info, $compression);
                }
            } elseif ($this->_debug) {
                /**
                 * Append debug info
                 */
                $page_key = $this->_get_page_key($_SERVER['REQUEST_URI'], false);
                $time_total = w3_microtime() - $this->_time_start;
                $debug_info = $this->_get_debug_info($page_key, false, $this->cache_reject_reason, false, $time_total);
                $this->_append_content($buffer, "\r\n\r\n" . $debug_info, false);
            }
        }
        
        return $buffer;
    }
    
    /**
     * Flushes all caches
     *
     * @return boolean
     */
    function flush()
    {
        $cache = & $this->_get_cache();
        
        return $cache->flush();
    }
    
    /**
     * Flushes post cache
     *
     * @param integer $post_id
     * @return boolean
     */
    function flush_post($post_id)
    {
        if (!$post_id) {
            $post_id = $this->_detect_post_id();
        }
        
        if ($post_id) {
            $home = get_option('home');
            
            $page_keys = array(
                $this->_get_page_key(str_replace($home, '', post_permalink($post_id)), false), 
                $this->_get_page_key(str_replace($home, '', post_permalink($post_id)), 'gzip'), 
                $this->_get_page_key(str_replace($home, '', post_permalink($post_id)), 'deflate'), 
                $this->_get_page_key('/', false), 
                $this->_get_page_key('/', 'gzip'), 
                $this->_get_page_key('/', 'deflate')
            );
            
            $cache = & $this->_get_cache();
            
            foreach ($page_keys as $page_key) {
                $cache->delete($page_key);
            }
        }
        
        return false;
    }
    
    /**
     * Returns object instance
     *
     * @return W3_PgCache
     */
    function &instance()
    {
        static $instances = array();
        
        if (!isset($instances[0])) {
            $class = __CLASS__;
            $instances[0] = & new $class();
        }
        
        return $instances[0];
    }
    
    /**
     * Checks if can we do cache logic
     *
     * @return boolean
     */
    function _can_cache()
    {
        /**
         * Skip if disabled
         */
        if (!$this->_config->get_boolean('pgcache.enabled')) {
            $this->cache_reject_reason = 'page caching is disabled';
            return false;
        }
        
        /**
         * Skip if posting
         */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->cache_reject_reason = 'request method is POST';
            return false;
        }
        
        /**
         * Skip if session defined
         */
        if (defined('SID') && SID != '') {
            $this->cache_reject_reason = 'session is started';
            return false;
        }
        
        /**
         * Skip if there is query in the request uri
         */
        if (!$this->_config->get_boolean('pgcache.cache.query') && strstr($_SERVER['REQUEST_URI'], '?') !== false) {
            $this->cache_reject_reason = 'request URI contains query';
            return false;
        }
        
        /**
         * Check request URI
         */
        if (!in_array($_SERVER['PHP_SELF'], $this->_config->get_array('pgcache.accept.files')) && !$this->_check_request_uri()) {
            $this->cache_reject_reason = 'request URI is rejected';
            return false;
        }
        
        /**
         * Check User Agent
         */
        if (!$this->_check_ua()) {
            $this->cache_reject_reason = 'user agent is rejected';
            return false;
        }
        
        /**
         * Check WordPress cookies
         */
        if (!$this->_check_cookies()) {
            $this->cache_reject_reason = 'cookie is rejected';
            return false;
        }
        
        /**
         * Skip if user is logged in
         */
        if ($this->_config->get_boolean('pgcache.reject.logged') && !$this->_check_logged_in()) {
            $this->cache_reject_reason = 'user is logged in';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks if can we do cache logic
     *
     * @param string $buffer
     * @return boolean
     */
    function _can_cache2()
    {
        /**
         * Skip if caching is disabled
         */
        if (!$this->_caching) {
            return false;
        }
        
        /**
         * Don't cache 404 pages
         */
        if (!$this->_config->get_boolean('pgcache.cache.404') && function_exists('is_404') && is_404()) {
            $this->cache_reject_reason = 'page is 404';
            
            return false;
        }
        
        /**
         * Don't cache homepage
         */
        if (!$this->_config->get_boolean('pgcache.cache.home') && function_exists('is_home') && is_home()) {
            $this->cache_reject_reason = 'page is home';
            
            return false;
        }
        
        /**
         * Don't cache feed
         */
        if (!$this->_config->get_boolean('pgcache.cache.feed') && function_exists('is_feed') && is_feed()) {
            $this->cache_reject_reason = 'page is feed';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns cache object
     *
     * @return W3_Cache_Base
     */
    function &_get_cache()
    {
        static $cache = array();
        
        if (!isset($cache[0])) {
            $engine = $this->_config->get_string('pgcache.engine');
            
            switch ($engine) {
                case 'memcached':
                    $engineConfig = array(
                        'servers' => $this->_config->get_array('pgcache.memcached.servers'), 
                        'persistant' => $this->_config->get_boolean('pgcache.memcached.persistant')
                    );
                    break;
                
                case 'file':
                    $engineConfig = array(
                        'cache_dir' => W3TC_CACHE_FILE_PGCACHE_DIR
                    );
                    break;
                
                case 'file_pgcache':
                    $engineConfig = array(
                        'cache_dir' => W3TC_CACHE_FILE_PGCACHE_DIR, 
                        'expire' => $this->_lifetime
                    );
                    break;
                
                default:
                    $engineConfig = array();
            }
            
            require_once W3TC_LIB_W3_DIR . '/Cache.php';
            $cache[0] = & W3_Cache::instance($engine, $engineConfig);
        }
        
        return $cache[0];
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function _check_request_uri()
    {
        $auto_reject_uri = array(
            'wp-login', 
            'wp-register'
        );
        
        foreach ($auto_reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        foreach ($this->_config->get_array('pgcache.reject.uri') as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $_SERVER['REQUEST_URI'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks User Agent
     *
     * @return boolean
     */
    function _check_ua()
    {
        foreach ($this->_config->get_array('pgcache.reject.ua') as $ua) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $ua) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks WordPress cookies
     *
     * @return boolean
     */
    function _check_cookies()
    {
        foreach (array_keys($_COOKIE) as $cookie_name) {
            if ($cookie_name == 'wordpress_test_cookie') {
                continue;
            }
            if (preg_match('/^(wp-postpass|comment_author)/', $cookie_name)) {
                return false;
            }
        }
        
        foreach ($this->_config->get_array('pgcache.reject.cookie') as $reject_cookie) {
            foreach (array_keys($_COOKIE) as $cookie_name) {
                if (strstr($cookie_name, $reject_cookie) !== false) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if user is logged in
     *
     * @return boolean
     */
    function _check_logged_in()
    {
        foreach (array_keys($_COOKIE) as $cookie_name) {
            if ($cookie_name == 'wordpress_test_cookie') {
                continue;
            }
            if (strpos($cookie_name, 'wordpress') === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks gzip availability
     *
     * @return boolean
     */
    function _get_compression()
    {
        if (!w3_zlib_output_compression() && !headers_sent() && !$this->_is_buggy_ie()) {
            $compressions = $this->_get_compressions();
            
            foreach ($compressions as $compression) {
                if (stristr($_SERVER['HTTP_ACCEPT_ENCODING'], $compression) !== false) {
                    return $compression;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Returns array of supported compressions
     * 
     * @return array
     */
    function _get_compressions()
    {
        $compression = $this->_config->get_string('pgcache.compression');
        $compressions = array(
            false
        );
        
        if ($compression != '') {
            if (stristr($compression, 'gzip') !== false && function_exists('gzencode')) {
                $compressions[] = 'gzip';
            }
            
            if (stristr($compression, 'deflate') !== false && function_exists('gzdeflate')) {
                $compressions[] = 'deflate';
            }
        }
        
        return $compressions;
    }
    
    /**
     * Returns array of response headers
     *
     * @return array
     */
    function _get_response_headers()
    {
        $headers = array();
        
        if (function_exists('headers_list')) {
            $headers_list = headers_list();
            if ($headers_list) {
                foreach ($headers_list as $header) {
                    list($header_name, $header_value) = explode(': ', $header, 2);
                    $headers[$header_name] = $header_value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Checks for buggy IE6 that doesn't support compression
     * 
     * @return boolean
     */
    function _is_buggy_ie()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        
        if (strpos($ua, 'Mozilla/4.0 (compatible; MSIE ') === 0 && strpos($ua, 'Opera') === false) {
            $version = (float) substr($ua, 30);
            
            return ($version < 6 || ($version == 6 && strpos($ua, 'SV1') === false));
        }
        
        return false;
    }
    
    /**
     * Returns array of data headers
     *
     * @return array
     */
    function _get_cached_headers()
    {
        $data_headers = array();
        $cache_headers = $this->_config->get_array('pgcache.cache.headers');
        $response_headers = $this->_get_response_headers();
        
        foreach ($response_headers as $header_name => $header_value) {
            foreach ($cache_headers as $cache_header_name) {
                if (strcasecmp($header_name, $cache_header_name) == 0) {
                    $data_headers[$header_name] = $header_value;
                }
            }
        }
        
        return $data_headers;
    }
    
    /**
     * Returns page key
     *
     * @param string $request_uri
     * @param string $compression
     * @return string
     */
    function _get_page_key($request_uri, $compression)
    {
        if ($this->_config->get_string('pgcache.engine') == 'file_pgcache') {
            $key = preg_replace('~[/\\\]+~', '/', $key);
            $key = preg_replace('~\?.*$~', '', $request_uri);
            $key = str_replace(w3_get_site_path(), '/', $key);
            $key = str_replace('/index.php', '/', $key);
            
            if ($key == '') {
                $key = '/';
            }
            
            if (substr($key, -1) == '/') {
                $key .= '_default_.html';
            }
            
            $key = ltrim($key, '/');
            
            if (!empty($compression)) {
                $key .= '.' . $compression;
            }
        } else {
            $blogname = w3_get_blogname();
            
            if ($blogname == '') {
                $blogname = $_SERVER['HTTP_HOST'];
            }
            
            $key = sprintf('w3tc_%s_page_%s', md5($blogname), md5($request_uri));
            
            if (!empty($compression)) {
                $key .= '_' . $compression;
            }
        }
        
        return $key;
    }
    
    /**
     * Detects post ID
     *
     * @return integer
     */
    function _detect_post_id()
    {
        global $posts, $comment_post_ID, $post_ID;
        
        if ($post_ID) {
            return $post_ID;
        } elseif ($comment_post_ID) {
            return $comment_post_ID;
        } elseif (is_single() || is_page() && count($posts)) {
            return $posts[0]->ID;
        } elseif (isset($_REQUEST['p'])) {
            return (integer) $_REQUEST['p'];
        }
        
        return 0;
    }
    
    /**
     * Returns debug info
     *
     * @param string $page_key
     * @param boolean $cache
     * @param string $reason
     * @param boolean $status
     * @param double $time
     * @return string
     */
    function _get_debug_info($page_key, $cache, $reason, $status, $time)
    {
        $debug_info = "<!-- W3 Total Cache: Page cache debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), w3_get_engine_name($this->_config->get_string('pgcache.engine')));
        $debug_info .= sprintf("%s%s\r\n", str_pad('Key: ', 20), $page_key);
        $debug_info .= sprintf("%s%s\r\n", str_pad('Caching: ', 20), ($cache ? 'enabled' : 'disabled'));
        if (!$cache) {
            $debug_info .= sprintf("%s%s\r\n", str_pad('Reject reason: ', 20), $reason);
        }
        $debug_info .= sprintf("%s%s\r\n", str_pad('Status: ', 20), ($status ? 'cached' : 'not cached'));
        $debug_info .= sprintf("%s%.3fs\r\n", str_pad('Creation Time: ', 20), $time);
        
        $headers = $this->_get_response_headers();
        
        if (count($headers)) {
            $debug_info .= "Header info:\r\n";
            
            foreach ($headers as $header_name => $header_value) {
                $debug_info .= sprintf("%s%s\r\n", str_pad($header_name . ': ', 20), $header_value);
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
    
    /**
     * Appends content to data content
     *
     * @param array $data
     * @param string $content
     * @param string $compression
     * @return string
     */
    function _append_content(&$data, $content, $compression)
    {
        switch ($compression) {
            case false:
                $data .= $content;
                break;
            
            case 'gzip':
                $data = (function_exists('gzdecode') ? gzdecode($data) : w3_gzdecode($data));
                $data .= $content;
                $data = gzencode($data);
                break;
            
            case 'deflate':
                $data = gzinflate($data);
                $data .= $content;
                $data = gzdeflate($data);
                break;
        }
    }
    
    /**
     * Sends headers
     * 
     * @param array $headers
     * @return boolean
     */
    function _headers($headers)
    {
        if (!headers_sent()) {
            foreach ($headers as $name => $value) {
                $header = ($name == 'Status' ? $value : $name . ': ' . $value);
                @header($header);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Sends headers
     * @param boolean $is_404
     * @param string $etag
     * @param integer $time
     * @param string $compression
     * @param array $custom_headers
     * @return boolean
     */
    function _send_headers($is_404, $time, $etag, $compression, $custom_headers = array())
    {
        $exit = false;
        $headers = array();
        $curr_time = time();
        $expires = $time + $this->_lifetime;
        $max_age = ($expires > $curr_time ? $expires - $curr_time : 0);
        
        if ($is_404) {
            /**
             * Add 404 header
             */
            $headers = array_merge($headers, array(
                'Status' => 'HTTP/1.1 404 Not Found'
            ));
        } elseif ($this->_check_modified_since($time) || $this->_check_match($etag)) {
            /**
             * Add 304 header
             */
            $headers = array_merge($headers, array(
                'Status' => 'HTTP/1.1 304 Not Modified'
            ));
            
            /**
             * Don't send content if it isn't modified
             */
            $exit = true;
        }
        
        /**
         * Add default headers
         */
        $headers = array_merge($headers, array(
            'Pragma' => 'public', 
            'Expires' => $this->_gmdate($expires), 
            'Last-Modified' => $this->_gmdate($time), 
            'Cache-Control' => sprintf('max-age=%d, public, must-revalidate, proxy-revalidate', $max_age), 
            'Vary' => 'Cookie', 
            'Etag' => $etag
        ));
        
        if ($compression) {
            /**
             * Add Content-Encoding header
             */
            $headers = array_merge($headers, array(
                'Vary' => 'Accept-Encoding, Cookie', 
                'Content-Encoding' => $compression
            ));
        }
        
        /**
         * Add custom headers
         */
        $headers = array_merge($headers, $custom_headers);
        
        /**
         * Send headers to client
         */
        $result = $this->_headers($headers);
        
        if ($exit) {
            exit();
        }
        
        return $result;
    }
    
    /**
     * Checks if User Agent is mobile
     * 
     * @return boolean
     */
    function _is_mobile()
    {
        $mobile_agents = $this->_config->get_array('pgcache.mobile.agents');
        
        foreach ($mobile_agents as $mobile_agent) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $mobile_agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if content was modified by time
     * @param integer $time
     * @return boolean
     */
    function _check_modified_since($time)
    {
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            
            // IE has tacked on extra data to this header, strip it
            if (($semicolon = strrpos($if_modified_since, ';')) !== false) {
                $if_modified_since = substr($if_modified_since, 0, $semicolon);
            }
            
            return ($time == strtotime($if_modified_since));
        }
        
        return false;
    }
    
    /**
     * Check if content was modified by etag
     * @param string $etag
     * @return boolean
     */
    function _check_match($etag)
    {
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $if_none_match = (get_magic_quotes_gpc() ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : $_SERVER['HTTP_IF_NONE_MATCH']);
            $client_etags = explode(',', $if_none_match);
            
            foreach ($client_etags as $client_etag) {
                $client_etag = trim($client_etag);
                
                if ($etag == $client_etag) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Returns GMT date
     * @param integer $time
     * @return string
     */
    function _gmdate($time)
    {
        return gmdate('D, d M Y H:i:s \G\M\T', $time);
    }
    
    /**
     * Bad Behavior support
     * @return void
     */
    function _bad_behavior()
    {
        if (file_exists(WP_CONTENT_DIR . '/plugins/bad-behavior/bad-behavior-generic.php')) {
            $bb_file = WP_CONTENT_DIR . '/plugins/bad-behavior/bad-behavior-generic.php';
        } elseif (file_exists(WP_CONTENT_DIR . '/plugins/Bad-Behavior/bad-behavior-generic.php')) {
            $bb_file = WP_CONTENT_DIR . '/plugins/Bad-Behavior/bad-behavior-generic.php';
        } else {
            $bb_file = false;
        }
        
        if ($bb_file) {
            require_once $bb_file;
        }
    }
}
