<?php

/**
 * File cache for Page cache
 */
require_once W3TC_LIB_W3_DIR . '/Cache/File.php';

/**
 * Class W3_Cache_File_PgCache
 */
class W3_Cache_File_PgCache extends W3_Cache_File
{
    var $_expire = 0;
    
    function __construct($config = array())
    {
        parent::__construct($config);
        
        $this->_expire = (isset($config['expire']) ? (int) $config['expire'] : 0);
        
        if (! $this->_expire || $this->_expire > W3_CACHE_FILE_EXPIRE_MAX) {
            $this->_expire = W3_CACHE_FILE_EXPIRE_MAX;
        }
    }
    
    function W3_Cache_File_PgCache($config = array())
    {
        $this->__construct($config);
    }
    
    /**
     * Sets data
	 *
     * @param string $key
     * @param string $var
     * @return boolean
     */
    function set($key, $var)
    {
        $sub_path = $this->_get_path($key);
        $path = $this->_cache_dir . '/' . $sub_path;
        
        $sub_dir = dirname($sub_path);
        $dir = dirname($path);
        
        if ((is_dir($dir) || w3_mkdir($sub_dir, 0755, $this->_cache_dir))) {
            $fp = @fopen($path, 'w');
            if ($fp) {
                @fputs($fp, $var);
                @fclose($fp);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Returns data
	 *
     * @param string $key
     * @return string
     */
    function get($key)
    {
        $var = false;
        $path = $this->_cache_dir . '/' . $this->_get_path($key);
        
        if (is_readable($path)) {
            $ftime = @filemtime($path);
            
            if ($ftime && $ftime > (time() - $this->_expire)) {
                $fp = @fopen($path, 'r');
                
                if ($fp) {
                    $var = '';
                    while (! @feof($fp)) {
                        $var .= @fread($fp, 4096);
                    }
                    @fclose($fp);
                }
            }
        }
        
        return $var;
    }
    
    /**
     * Flushes all data
     *
     * @return boolean
     */
    function flush()
    {
        w3_emptydir($this->_cache_dir, array(
            $this->_cache_dir . '/.htaccess'
        ));
        
        return true;
    }
    
    /**
     * Returns cache file path by key
	 *
     * @param string $key
     * @return string
     */
    function _get_path($key)
    {
        return $key;
    }
}