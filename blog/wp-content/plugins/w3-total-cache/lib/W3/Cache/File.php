<?php

if (! defined('W3_CACHE_FILE_EXPIRE_MAX')) {
    define('W3_CACHE_FILE_EXPIRE_MAX', 2592000);
}

/**
 * File class
 */
require_once W3TC_LIB_W3_DIR . '/Cache/Base.php';

/**
 * Class W3_Cache_File
 */
class W3_Cache_File extends W3_Cache_Base
{
    /**
     * Path to cache dir
     *
     * @var string
     */
    var $_cache_dir = '';
    
    /**
     * PHP5 constructor
     *
     * @param array $config
     */
    function __construct($config = array())
    {
        $this->_cache_dir = isset($config['cache_dir']) ? trim($config['cache_dir']) : 'cache';
    }
    
    /**
     * PHP4 constructor
     *
     * @paran array $config
     * @return W3_Cache_File
     */
    function W3_Cache_File($config = array())
    {
        $this->__construct($config);
    }
    
    /**
     * Adds data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @return boolean
     */
    function add($key, $var, $expire = 0)
    {
        if ($this->get($key) === false) {
            return $this->set($key, $var, $expire);
        }
        
        return false;
    }
    
    /**
     * Sets data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @return boolean
     */
    function set($key, $var, $expire = 0)
    {
        $sub_path = $this->_get_path($key);
        $path = $this->_cache_dir . '/' . $sub_path;
        
        $sub_dir = dirname($sub_path);
        $dir = dirname($path);
        
        if ((is_dir($dir) || w3_mkdir($sub_dir, 0755, $this->_cache_dir))) {
            $fp = @fopen($path, 'wb');
            if ($fp) {
                @fputs($fp, pack('L', $expire));
                @fputs($fp, @serialize($var));
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
     * @return mixed
     */
    function get($key)
    {
        $var = false;
        $path = $this->_cache_dir . '/' . $this->_get_path($key);
        
        if (is_readable($path)) {
            $ftime = @filemtime($path);
            if ($ftime) {
                $fp = @fopen($path, 'rb');
                if ($fp) {
                    $expires = @fread($fp, 4);
                    if ($expires !== false) {
                        list (, $expire) = @unpack('L', $expires);
                        $expire = ($expire && $expire <= W3_CACHE_FILE_EXPIRE_MAX ? $expire : W3_CACHE_FILE_EXPIRE_MAX);
                        if ($ftime > time() - $expire) {
                            $data = '';
                            while (! @feof($fp)) {
                                $data .= @fread($fp, 4096);
                            }
                            $var = @unserialize($data);
                        }
                    }
                    @fclose($fp);
                }
            }
        }
        
        return $var;
    }
    
    /**
     * Replaces data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @return boolean
     */
    function replace($key, $var, $expire = 0)
    {
        if ($this->get($key) !== false) {
            return $this->set($key, $var, $expire);
        }
        
        return false;
    }
    
    /**
     * Deletes data
     *
     * @param string $key
     * @return boolean
     */
    function delete($key)
    {
        $path = $this->_cache_dir . '/' . $this->_get_path($key);
        
        if (file_exists($path)) {
            return @unlink($path);
        }
        
        return false;
    }
    
    /**
     * Flushes all data
     *
     * @return boolean
     */
    function flush()
    {
        w3_emptydir($this->_cache_dir);
        
        return true;
    }
    
    /**
     * Returns modification time of cache file
	 *
     * @param integer $key
     */
    function mtime($key)
    {
        $path = $this->_cache_dir . '/' . $this->_get_path($key);
        
        if (file_exists($path)) {
            return @filemtime($path);
        }
        
        return false;
    }
    
    /**
     * Returns file path for key
     *
     * @param string $key
     * @return string
     */
    function _get_path($key)
    {
        $hash = md5($key);
        $path = sprintf('%s/%s/%s', substr($hash, 0, 2), substr($hash, 2, 2), $hash);
        
        return $path;
    }
}
