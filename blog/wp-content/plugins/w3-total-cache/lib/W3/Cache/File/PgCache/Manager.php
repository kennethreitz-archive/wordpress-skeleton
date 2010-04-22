<?php

require_once W3TC_LIB_W3_DIR . '/Cache/File/Manager.php';

class W3_Cache_File_PgCache_Manager extends W3_Cache_File_Manager
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
    
    function W3_Cache_File_PgCache_Manager($config = array())
    {
        $this->__construct($config);
    }
    
    function is_valid($file)
    {
        if ($file == $this->_cache_dir . '/.htaccess') {
            return true;
        }
        
        if (file_exists($file)) {
            $ftime = @filemtime($file);
            
            if ($ftime && $ftime > (time() - $this->_expire)) {
                return true;
            }
        }
        
        return false;
    }
}