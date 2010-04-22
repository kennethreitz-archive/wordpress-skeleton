<?php

require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';
require_once W3TC_LIB_DIR . '/S3.php';

class W3_Cdn_S3 extends W3_Cdn_Base
{
    /**
     * S3 object
     *
     * @var S3
     */
    var $_s3 = null;
    
    /**
     * Inits S3 object
     *
     * @param string $error
     * @return boolean
     */
    function _init(&$error)
    {
        if (empty($this->_config['key'])) {
            $error = 'Empty access key';
            
            return false;
        }
        
        if (empty($this->_config['secret'])) {
            $error = 'Empty secret key';
            
            return false;
        }
        
        if (empty($this->_config['bucket'])) {
            $error = 'Empty bucket';
            
            return false;
        }
        
        $this->_s3 = & new S3($this->_config['key'], $this->_config['secret'], false);
        
        return true;
    }
    
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
        $count = 0;
        $error = null;
        
        if (! $this->_init($error)) {
            $results = $this->get_results($files, W3_CDN_RESULT_HALT, $error);
            return false;
        }
        
        foreach ($files as $local_path => $remote_path) {
            if (! file_exists($local_path)) {
                $results[] = $this->get_result($local_path, $remote_path, W3_CDN_RESULT_ERROR, 'Source file not found');
                continue;
            }
            
            if (! $force_rewrite) {
                $info = @$this->_s3->getObjectInfo($this->_config['bucket'], $remote_path);
                
                if ($info) {
                    $hash = @md5_file($local_path);
                    $s3_hash = (isset($info['hash']) ? $info['hash'] : '');
                    
                    if ($hash === $s3_hash) {
                        $results[] = $this->get_result($local_path, $remote_path, W3_CDN_RESULT_ERROR, 'Object already exists');
                        continue;
                    }
                }
            }
            
            $result = @$this->_s3->putObjectFile($local_path, $this->_config['bucket'], $remote_path, S3::ACL_PUBLIC_READ);
            $results[] = $this->get_result($local_path, $remote_path, ($result ? W3_CDN_RESULT_OK : W3_CDN_RESULT_ERROR), ($result ? 'OK' : 'Unable to put object'));
            
            if ($result) {
                $count++;
            }
        }
        
        return $count;
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
        $error = null;
        $count = 0;
        
        if (! $this->_init($error)) {
            $results = $this->get_results($files, W3_CDN_RESULT_HALT, $error);
            return false;
        }
        
        foreach ($files as $local_path => $remote_path) {
            $result = @$this->_s3->deleteObject($this->_config['bucket'], $remote_path);
            $results[] = $this->get_result($local_path, $remote_path, ($result ? W3_CDN_RESULT_OK : W3_CDN_RESULT_ERROR), ($result ? 'OK' : 'Unable to delete object'));
            
            if ($result) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Tests S3
     *
     * @param string $error
     * @return boolean
     */
    function test(&$error)
    {
        $string = 'test_s3_' . md5(time());
        
        if (! $this->_init($error)) {
            return false;
        }
        
        $domain = $this->get_domain();
        
        if (! $domain) {
            $error = 'Empty domain.';
            
            return false;
        }
        
        if (gethostbyname($domain) == $domain) {
            $error = sprintf('Unable to resolve domain: %s.', $domain);
            
            return false;
        }
        
        $buckets = @$this->_s3->listBuckets();
        
        if (! $buckets) {
            $error = 'Unable to list buckets (check your credentials).';
            
            return false;
        }
        
        if (! in_array($this->_config['bucket'], (array) $buckets)) {
            $error = sprintf('Bucket doesn\'t exist: %s', $this->_config['bucket']);
            
            return false;
        }
        
        if (! @$this->_s3->putObjectString($string, $this->_config['bucket'], $string, S3::ACL_PUBLIC_READ)) {
            $error = 'Unable to put object.';
            
            return false;
        }
        
        if (! ($object = @$this->_s3->getObject($this->_config['bucket'], $string))) {
            $error = 'Unable to get object.';
            
            return false;
        }
        
        if ($object->body != $string) {
            @$this->_s3->deleteObject($this->_config['bucket'], $string);
            $error = 'Objects are not equal.';
            
            return false;
        }
        
        if (! @$this->_s3->deleteObject($this->_config['bucket'], $string)) {
            $error = 'Unable to delete object.';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns CDN domain
     *
     * @return string
     */
    function get_domain()
    {
        if (! empty($this->_config['bucket'])) {
            $domain = sprintf('%s.s3.amazonaws.com', $this->_config['bucket']);
            
            return $domain;
        }
        
        return false;
    }
    
    /**
     * Returns via string
     *
     * @return string
     */
    function get_via()
    {
        $domain = $this->get_domain();
        
        return sprintf('Amazon Web Services: S3: %s', ($domain ? $domain : 'N/A'));
    }
    
    /**
     * Creates bucket
     *
     * @param string $error
     * @return boolean
     */
    function create_bucket(&$error)
    {
        if (! $this->_init($error)) {
            return false;
        }
        
        $buckets = @$this->_s3->listBuckets();
        
        if (! $buckets) {
            $error = 'Unable to list buckets (check your credentials).';
            
            return false;
        }
        
        if (in_array($this->_config['bucket'], (array) $buckets)) {
            $error = sprintf('Bucket already exists: %s.', $this->_config['bucket']);
            
            return false;
        }
        
        if (! @$this->_s3->putBucket($this->_config['bucket'], S3::ACL_PUBLIC_READ)) {
            $error = sprintf('Unable to create bucket: %s.', $this->_config['bucket']);
            
            return false;
        }
        
        return true;
    }
}
