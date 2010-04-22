<?php

/**
 * W3 CDN Mirror Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

/**
 * Class W3_Cdn_Mirror
 */
class W3_Cdn_Mirror extends W3_Cdn_Base
{
    /**
     * Uploads files to FTP
     *
     * @param array $files
     * @param array $results
     * @param boolean $force_rewrite
     * @return boolean
     */
    function upload($files, &$results, $force_rewrite = false)
    {
        $results = $this->get_results($files, W3_CDN_RESULT_OK, 'OK');
        
        return count($files);
    }
    
    /**
     * Deletes files from FTP
     *
     * @param array $files
     * @param array $results
     * @return boolean
     */
    function delete($files, &$results)
    {
        $results = $this->get_results($files, W3_CDN_RESULT_OK, 'OK');
        
        return count($files);
    }
    
    /**
     * Tests FTP server
     *
     * @param string $error
     * @return boolean
     */
    function test(&$error)
    {
        return true;
    }
    
    /**
     * Returns CDN domain
     * @return string
     */
    function get_domain()
    {
        if (! empty($this->_config['domain'])) {
            return $this->_config['domain'];
        }
        
        return false;
    }
}
