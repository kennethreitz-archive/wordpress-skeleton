<?php

/**
 * W3 CDN FTP Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

if (! defined('W3_CDN_FTP_CONNECT_TIMEOUT')) {
    define('W3_CDN_FTP_CONNECT_TIMEOUT', 30);
}

/**
 * Class W3_Cdn_Ftp
 */
class W3_Cdn_Ftp extends W3_Cdn_Base
{
    /**
     * FTP resource
     *
     * @var resource
     */
    var $_ftp = null;
    
    /**
     * Connects to FTP server
     *
     * @param string $error
     * @return boolean
     */
    function _connect(&$error)
    {
        if (empty($this->_config['host'])) {
            $error = 'Empty host.';
            return false;
        }
        
        if (! isset($this->_config['port'])) {
            $this->_config['port'] = 21;
        }
        
        $this->_ftp = @ftp_connect($this->_config['host'], $this->_config['port'], W3_CDN_FTP_CONNECT_TIMEOUT);
        
        if (! $this->_ftp) {
            $error = sprintf('Unable to connect to %s:%d.', $this->_config['host'], $this->_config['port']);
            return false;
        }
        
        if (! @ftp_login($this->_ftp, $this->_config['user'], $this->_config['pass'])) {
            $this->_disconnect();
            $error = 'Incorrect login or password.';
            
            return false;
        }
        
        if (isset($this->_config['pasv']) && ! @ftp_pasv($this->_ftp, $this->_config['pasv'])) {
            $this->_disconnect();
            $error = 'Unable to change mode to passive.';
            
            return false;
        }
        
        if (! empty($this->_config['path']) && ! @ftp_chdir($this->_ftp, $this->_config['path'])) {
            $this->_disconnect();
            $error = sprintf('Unable to change directory to: %s.', $this->_config['path']);
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Disconnects from FTP server
     */
    function _disconnect()
    {
        @ftp_close($this->_ftp);
    }
    
    /**
     * Sends MDTM command
     * @param string $remote_file
     * @return boolean
     */
    function _mdtm($remote_file, $mtime)
    {
        $command = sprintf('MDTM %s %s', date('YmdHis', $mtime), $remote_file);
        
        return @ftp_raw($this->_ftp, $command);
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
        
        if (! $this->_connect($error)) {
            $results = $this->get_results($files, W3_CDN_RESULT_HALT, $error);
            return false;
        }
        
        $home = @ftp_pwd($this->_ftp);
        
        foreach ($files as $local_path => $remote_path) {
            if (! file_exists($local_path)) {
                $results[] = $this->get_result($local_path, $remote_path, W3_CDN_RESULT_ERROR, 'Source file not found');
                continue;
            }
            
            @ftp_chdir($this->_ftp, $home);
            
            $remote_dir = dirname($remote_path);
            $remote_dirs = preg_split('~\\/+~', $remote_dir);
            
            foreach ($remote_dirs as $dir) {
                if (! @ftp_chdir($this->_ftp, $dir)) {
                    if (! @ftp_mkdir($this->_ftp, $dir)) {
                        @ftp_close($this->_ftp);
                        $results[] = $this->get_result($local_path, $remote_path, W3_CDN_RESULT_ERROR, 'Unable to create directory');
                        continue;
                    }
                    
                    @ftp_chmod($this->_ftp, 0755, $dir);
                    
                    if (! @ftp_chdir($this->_ftp, $dir)) {
                        @ftp_close($this->_ftp);
                        $results[] = $this->get_result($local_path, $remote_path, W3_CDN_RESULT_ERROR, 'Unable to change directory');
                        continue;
                    }
                }
            }
            
            $remote_file = basename($remote_path);
            $mtime = @filemtime($local_path);
            
            if (! $force_rewrite) {
                $size = @filesize($local_path);
                $ftp_size = @ftp_size($this->_ftp, $remote_file);
                $ftp_mtime = @ftp_mdtm($this->_ftp, $remote_file);
                
                if ($size === $ftp_size && $mtime === $ftp_mtime) {
                    $results[] = $this->get_result($local_path, $remote_path, W3_CDN_RESULT_ERROR, 'File already exists');
                    continue;
                }
            }
            
            $result = @ftp_put($this->_ftp, $remote_file, $local_path, FTP_BINARY);
            $this->_mdtm($remote_file, $mtime);
            
            $results[] = $this->get_result($local_path, $remote_path, ($result ? W3_CDN_RESULT_OK : W3_CDN_RESULT_ERROR), ($result ? 'OK' : 'Unable to upload file'));
            
            if ($result) {
                $count++;
                @ftp_chmod($this->_ftp, 0644, $remote_file);
            }
        }
        
        $this->_disconnect();
        
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
        
        if (! $this->_connect($error)) {
            $results = $this->get_results($files, W3_CDN_RESULT_HALT, $error);
            return false;
        }
        
        foreach ($files as $local_path => $remote_path) {
            $result = @ftp_delete($this->_ftp, $remote_path);
            $results[] = $this->get_result($local_path, $remote_path, ($result ? W3_CDN_RESULT_OK : W3_CDN_RESULT_ERROR), ($result ? 'OK' : 'Unable to delete file'));
            
            if ($result) {
                $count++;
            }
            
            while (true) {
                $remote_path = dirname($remote_path);
                if ($remote_path == '.' || ! @ftp_rmdir($this->_ftp, $remote_path)) {
                    break;
                }
            }
        }
        
        $this->_disconnect();
        
        return $count;
    }
    
    /**
     * Tests FTP server
     *
     * @param string $error
     * @return boolean
     */
    function test(&$error)
    {
        $rand = md5(time());
        $upload_info = w3_upload_info();
        $tmp_dir = 'test_dir_' . $rand;
        $tmp_file = 'test_file_' . $rand;
        $tmp_path = $upload_info['path'] . '/' . $tmp_file;
        
        if (! @file_put_contents($tmp_path, $rand)) {
            $error = sprintf('Unable to create file: %s.', $tmp_path);
            return false;
        }
        
        if (! $this->_connect($error)) {
            return false;
        }
        
        if (! @ftp_mkdir($this->_ftp, $tmp_dir)) {
            $this->_disconnect();
            $error = sprintf('Unable to make directory: %s.', $tmp_dir);
            return false;
        } else {
            @ftp_chmod($this->_ftp, 0755, $tmp_dir);
        }
        
        if (! @ftp_chdir($this->_ftp, $tmp_dir)) {
            $this->_disconnect();
            $error = sprintf('Unable to change directory to: %s.', $tmp_dir);
            return false;
        }
        
        if (! @ftp_put($this->_ftp, $tmp_file, $tmp_path, FTP_BINARY)) {
            @ftp_cdup($this->_ftp);
            @ftp_rmdir($this->_ftp, $tmp_dir);
            @unlink($tmp_path);
            $this->_disconnect();
            $error = sprintf('Unable to upload file: %s.', $tmp_path);
            return false;
        }
        
        @ftp_delete($this->_ftp, $tmp_file);
        @ftp_cdup($this->_ftp);
        @ftp_rmdir($this->_ftp, $tmp_dir);
        @unlink($tmp_path);
        $this->_disconnect();
        
        return true;
    }
    
    /**
     * Returns CDN domain
     *
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
