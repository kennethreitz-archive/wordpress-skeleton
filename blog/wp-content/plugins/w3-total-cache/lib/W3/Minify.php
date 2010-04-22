<?php

/**
 * W3 Minify object
 */

/**
 * Class W3_Minify
 */
class W3_Minify
{
    /**
     * Config
     *
     * @var W3_Config
     */
    var $_config = null;
    
    /**
     * Memcached object
     *
     * @var W3_Cache_Memcached
     */
    var $_memcached = null;
    
    /**
     * PHP5 constructor
     */
    function __construct()
    {
        require_once W3TC_LIB_W3_DIR . '/Config.php';
        
        $this->_config = & W3_Config::instance();
    }
    
    /**
     * PHP4 constructor
     * @return W3_Minify
     */
    function W3_Minify()
    {
        $this->__construct();
    }
    
    /**
     * Runs minify
     */
    function process()
    {
        require_once W3TC_LIB_MINIFY_DIR . '/Minify.php';
        require_once W3TC_LIB_MINIFY_DIR . '/HTTP/Encoder.php';
        
        HTTP_Encoder::$encodeToIe6 = true;
        
        Minify::$uploaderHoursBehind = $this->_config->get_integer('minify.fixtime');
        Minify::setCache($this->_get_cache());
        
        $serve_options = $this->_config->get_array('minify.options');
        $serve_options['maxAge'] = $this->_config->get_integer('minify.maxage');
        $serve_options['encodeOutput'] = $this->_config->get_string('minify.compression');
        
        if (stripos(PHP_OS, 'win') === 0) {
            Minify::setDocRoot();
        }
        
        foreach ($this->_config->get_array('minify.symlinks') as $link => $target) {
            $link = str_replace('//', realpath($_SERVER['DOCUMENT_ROOT']), $link);
            $link = strtr($link, '/', DIRECTORY_SEPARATOR);
            $serve_options['minifierOptions']['text/css']['symlinks'][$link] = realpath($target);
        }
        
        if ($this->_config->get_boolean('minify.debug')) {
            $serve_options['debug'] = true;
        }
        
        if ($this->_config->get('minify.debug')) {
            require_once W3TC_LIB_MINIFY_DIR . '/Minify/Logger.php';
            Minify_Logger::setLogger($this);
        }
        
        if (isset($_GET['f']) || (isset($_GET['gg']) && isset($_GET['g']) && isset($_GET['t']))) {
            if (isset($_GET['gg']) && isset($_GET['g']) && isset($_GET['t'])) {
                $serve_options['minApp']['groups'] = $this->get_groups($_GET['gg'], $_GET['t']);
                
                /**
                 * Handle combine option
                 */
                switch (true) {
                    case ($_GET['t'] == 'js' && $_GET['g'] == 'include') && $this->_config->get_boolean('minify.js.combine.header'):
                    case ($_GET['t'] == 'js' && $_GET['g'] == 'include-nb') && $this->_config->get_boolean('minify.js.combine.header'):
                    case ($_GET['t'] == 'js' && $_GET['g'] == 'include-footer') && $this->_config->get_boolean('minify.js.combine.footer'):
                    case ($_GET['t'] == 'js' && $_GET['g'] == 'include-footer-nb') && $this->_config->get_boolean('minify.js.combine.footer'):
                        $serve_options['minifiers']['application/x-javascript'] = array(
                            $this, 
                            'minify_stub'
                        );
                        break;
                    
                    case ($_GET['t'] == 'css' && $_GET['g'] == 'include') && $this->_config->get_boolean('minify.css.combine'):
                        $serve_options['minifiers']['text/css'] = array(
                            $this, 
                            'minify_stub'
                        );
                        break;
                    
                    default:
                        $serve_options['postprocessor'] = array(
                            &$this, 
                            'postprocessor'
                        );
                }
                
                /**
                 * Setup user-friendly cache ID for disk cache
                 */
                if ($this->_config->get_string('minify.engine') == 'file') {
                    $cacheId = sprintf('%s.%s.%s', $_GET['gg'], $_GET['g'], $_GET['t']);
                    
                    Minify::setCacheId($cacheId);
                }
            }
            
            @header('Pragma: public');
            
            try {
                Minify::serve('MinApp', $serve_options);
            } catch (Exception $exception) {
                printf('<strong>W3 Total Cache Error:</strong> Minify error: %s', $exception->getMessage());
            }
        } else {
            die('This file cannot be accessed directly');
        }
    }
    
    /**
     * Minify postprocessor
     *
     * @param string $content
     * @param string $type
     * @return string
     */
    function postprocessor($content, $type)
    {
        switch ($type) {
            case 'text/css':
                if ($this->_config->get_boolean('minify.css.strip.comments')) {
                    $content = preg_replace('~/\*.*\*/~Us', '', $content);
                }
                
                if ($this->_config->get_boolean('minify.css.strip.crlf')) {
                    $content = preg_replace("~[\r\n]+~", ' ', $content);
                } else {
                    $content = preg_replace("~[\r\n]+~", "\n", $content);
                }
                break;
            
            case 'application/x-javascript':
                if ($this->_config->get_boolean('minify.js.strip.comments')) {
                    $content = preg_replace('~^//.*$~m', '', $content);
                    $content = preg_replace('~/\*.*\*/~Us', '', $content);
                }
                
                if ($this->_config->get_boolean('minify.js.strip.crlf')) {
                    $content = preg_replace("~[\r\n]+~", '', $content);
                } else {
                    $content = preg_replace("~[\r\n]+~", "\n", $content);
                }
                break;
        }
        
        return trim($content);
    }
    
    /**
     * Flushes cache
     */
    function flush()
    {
        static $cache_path = null;
        
        $cache = & $this->_get_cache();
        
        if (is_a($cache, 'W3_Cache_Memcached') && class_exists('Memcache')) {
            return $this->_memcached->flush();
        } elseif (is_a($cache, 'Minify_Cache_APC') && function_exists('apc_clear_cache')) {
            return apc_clear_cache('user');
        } elseif (is_a($cache, 'Minify_Cache_File')) {
            if (!is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
                $this->log(sprintf('Cache directory %s does not exists', W3TC_CACHE_FILE_MINIFY_DIR));
            }
            
            return w3_emptydir(W3TC_CACHE_FILE_MINIFY_DIR, array(
                W3TC_CACHE_FILE_MINIFY_DIR . '/index.php', 
                W3TC_CACHE_FILE_MINIFY_DIR . '/.htaccess'
            ));
        }
        
        return false;
    }
    
    /**
     * Returns onject instance
     *
     * @return W3_Minify
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
     * Minify stub function
     *
     * @param string $source
     */
    function minify_stub($source)
    {
        return $source;
    }
    
    /**
     * Log
     *
     * @param mixed $object
     * @param string $label
     */
    function log($object, $label = null)
    {
        $data = sprintf("[%s] [%s] %s\n", date('r'), $_SERVER['REQUEST_URI'], $object);
        
        return @file_put_contents(W3TC_MINIFY_LOG_FILE, $data, FILE_APPEND);
    }
    
    /**
     * Returns minify groups
     * @param string $group
     * @param string $type
     * @return array
     */
    function get_groups($group, $type)
    {
        $result = array();
        
        switch ($type) {
            case 'css':
                $groups = $this->_config->get_array('minify.css.groups');
                break;
            
            case 'js':
                $groups = $this->_config->get_array('minify.js.groups');
                break;
            
            default:
                return $result;
        }
        
        if (isset($groups['default'])) {
            $locations = (array) $groups['default'];
        } else {
            $locations = array();
        }
        
        if ($group != 'default' && isset($groups[$group])) {
            $locations = array_merge_recursive($locations, (array) $groups[$group]);
        }
        
        $site_url = w3_get_site_url();
        
        foreach ($locations as $location => $config) {
            if (!empty($config['files'])) {
                foreach ((array) $config['files'] as $file) {
                    $file = $this->_normalize_file($file);
                    
                    if (w3_is_url($file)) {
                        $precached_file = $this->_precache_file($file, $type);
                        
                        if ($precached_file) {
                            $result[$location][$file] = $precached_file;
                        }
                    } else {
                        $result[$location][$file] = '//' . $file;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Precaches external file
     *
     * @param string $url
     * @param string $type
     * @return string
     */
    function _precache_file($url, $type)
    {
        $lifetime = $this->_config->get_integer('minify.lifetime');
        $file_path = sprintf('%s/minify_%s.%s', W3TC_CACHE_FILE_MINIFY_DIR, md5($url), $type);
        $file_exists = file_exists($file_path);
        
        if ($file_exists && @filemtime($file_path) >= (time() - $lifetime)) {
            return $this->_get_minify_source($file_path, $url);
        }
        
        if (is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
            if (w3_download($url, $file_path) !== false) {
                return $this->_get_minify_source($file_path, $url);
            } else {
                $this->log(sprintf('Unable to download URL: %s', $url));
            }
        } else {
            $this->log(sprintf('The cache directory %s does not exist', W3TC_CACHE_FILE_MINIFY_DIR));
        }
        
        return ($file_exists ? $this->_get_minify_source($file_path, $url) : false);
    }
    
    /**
     * Normalizes URL
     * @param string $url
     * @return string
     */
    function _normalize_file($url)
    {
        $url = preg_replace('~\?ver=[0-9\.]+$~', '', $url);
        $url = w3_normalize_file($url);
        $url = w3_translate_file($url);
        
        return $url;
    }
    
    /**
     * Returns minify source
     * @param $file_path
     * @param $url
     * @return Minify_Source
     */
    function _get_minify_source($file_path, $url)
    {
        require_once W3TC_LIB_MINIFY_DIR . '/Minify/Source.php';
        
        return new Minify_Source(array(
            'filepath' => $file_path, 
            'minifyOptions' => array(
                'prependRelativePath' => $url
            )
        ));
    }
    
    /**
     * Returns minify cache object
     *
     * @return object
     */
    function &_get_cache()
    {
        static $cache = array();
        
        if (!isset($cache[0])) {
            switch ($this->_config->get_string('minify.engine')) {
                case 'memcached':
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached.php';
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/Memcache.php';
                    $this->_memcached = & new W3_Cache_Memcached(array(
                        'servers' => $this->_config->get_array('minify.memcached.servers'), 
                        'persistant' => $this->_config->get_boolean('minify.memcached.persistant')
                    ));
                    $cache[0] = & new Minify_Cache_Memcache($this->_memcached);
                    break;
                
                case 'apc':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/APC.php';
                    $cache[0] = & new Minify_Cache_APC();
                    break;
                
                case 'eaccelerator':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/Eaccelerator.php';
                    $cache[0] = & new Minify_Cache_Eaccelerator();
                    break;
                
                case 'xcache':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/XCache.php';
                    $cache[0] = & new Minify_Cache_XCache();
                    break;
                
                case 'file':
                default:
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/File.php';
                    if (!is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
                        $this->log(sprintf('Cache directory %s does not exist', W3TC_CACHE_FILE_MINIFY_DIR));
                    }
                    $cache[0] = & new Minify_Cache_File(W3TC_CACHE_FILE_MINIFY_DIR, $this->_config->get_boolean('minify.file.locking'));
                    break;
            }
        }
        
        return $cache[0];
    }
}
