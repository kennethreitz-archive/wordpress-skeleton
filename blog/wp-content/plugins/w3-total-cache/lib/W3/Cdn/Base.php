<?php

/**
 * W3 CDN Base class
 */

if (! defined('W3_CDN_RESULT_HALT')) {
    define('W3_CDN_RESULT_HALT', - 1);
}

if (! defined('W3_CDN_RESULT_ERROR')) {
    define('W3_CDN_RESULT_ERROR', 0);
}

if (! defined('W3_CDN_RESULT_OK')) {
    define('W3_CDN_RESULT_OK', 1);
}

/**
 * Class W3_Cdn_Base
 */
class W3_Cdn_Base
{
    /**
     * CDN Configuration
     *
     * @var array
     */
    var $_config = array();
    
    /**
     * PHP5 Constructor
     *
     * @param array $config
     */
    function __construct($config)
    {
        $this->_config = $config;
    }
    
    /**
     * PHP4 Constructor
     *
     * @param array $config
     */
    function W3_Cdn_Base($config)
    {
        $this->__construct($config);
    }
    
    /**
     * Upload files to CDN
     *
     * @param array $files
     * @param array $results
     * @param boolean $force_rewrite
     * @return boolean
     */
    function upload($files, &$results, $force_rewrite = false)
    {
        $results = $this->get_results($files, W3_CDN_RESULT_HALT, 'Not implemented.');
        return false;
    }
    
    /**
     * Delete files from CDN
     *
     * @param array $files
     * @param array $results
     * @return boolean
     */
    function delete($files, &$results)
    {
        $results = $this->get_results($files, W3_CDN_RESULT_HALT, 'Not implemented.');
        return false;
    }
    
    /**
     * Test CDN server
     *
     * @param string $error
     * @return boolean
     */
    function test(&$error)
    {
        $error = 'Not implemented.';
        return false;
    }
    
    /**
     * Returns CDN domain
     *
     * @return string
     */
    function get_domain()
    {
        return false;
    }
    
    /**
     * Returns via string
     *
     * @return string
     */
    function get_via()
    {
        return $this->get_domain();
    }
    
    /**
     * Formats object URL
     *
     * @param string $path
     * @return string
     */
    function format_url($path)
    {
        $domain = $this->get_domain();
        
        if ($domain) {
            $url = sprintf('%s://%s%s', (w3_is_https() ? 'https' : 'http'), $domain, $path);
            
            return $url;
        }
        
        return false;
    }
    
    /**
     * Returns results
     *
     * @param array $files
     * @param integer $result
     * @param string $error
     * @return array
     */
    function get_results($files, $result = W3_CDN_RESULT_OK, $error = 'OK')
    {
        $results = array();
        
        foreach ($files as $local_path => $remote_path) {
            $results[] = $this->get_result($local_path, $remote_path, $result, $error);
        }
        
        return $results;
    }
    
    /**
     * Returns file process result
     *
     * @param string $local_path
     * @param string $remote_path
     * @param integer $result
     * @param string $error
     * @return array
     */
    function get_result($local_path, $remote_path, $result = W3_CDN_RESULT_OK, $error = 'OK')
    {
        return array(
            'local_path' => $local_path, 
            'remote_path' => $remote_path, 
            'result' => $result, 
            'error' => $error
        );
    }
}
