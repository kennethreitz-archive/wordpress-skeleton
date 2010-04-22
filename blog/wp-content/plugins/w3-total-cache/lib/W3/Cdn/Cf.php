<?php

require_once W3TC_LIB_W3_DIR . '/Cdn/S3.php';

class W3_Cdn_Cf extends W3_Cdn_S3
{
    /**
     * Returns CDN domain
	 *
     * @return string
     */
    function get_domain()
    {
        if (! empty($this->_config['cname'])) {
            return $this->_config['cname'];
        } elseif (! empty($this->_config['id'])) {
            $domain = sprintf('%s.cloudfront.net', $this->_config['id']);
            
            return $domain;
        }
        
        return false;
    }
    /**
     * Tests CF
     *
     * @param string $error
     * @return boolean
     */
    function test(&$error)
    {
        /**
         * Test S3 first
         */
        if (! parent::test($error)) {
            return false;
        }
        
        /**
         * Search active CF distribution
         */
        $dists = @$this->_s3->listDistributions();
        
        if (! $dists) {
            $error = 'Unable to list distributions.';
            
            return false;
        }
        
        $search = sprintf('%s.s3.amazonaws.com', $this->_config['bucket']);
        $dist = false;
        
        if ($dists) {
            foreach ((array) $dists as $_dist) {
                if (isset($_dist['origin']) && $_dist['origin'] == $search) {
                    $dist = $_dist;
                    break;
                }
            }
        }
        
        if (! $dist) {
            $error = sprintf('Distribution for bucket "%s" not found.', $this->_config['bucket']);
            
            return false;
        }
        
        if (! $dist['enabled']) {
            $error = sprintf('Distribution for bucket "%s" is disabled.', $this->_config['bucket']);
            
            return false;
        }
        
        if ($this->_config['cname'] != '') {
            $cnames = (isset($dist['cnames']) ? (array) $dist['cnames'] : array());
            
            if (! in_array($this->_config['cname'], $cnames)) {
                $error = sprintf('Domain name %s is not in distribution CNAME list.', $this->_config['cname']);
                
                return false;
            }
        
        } elseif ($this->_config['id'] != '') {
            $domain = $this->get_domain();
            
            if ($domain != $dist['domain']) {
                $error = sprintf('Distribution domain name mismatch (%s != %s).', $domain, $dist['domain']);
                
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Returns via string
	 *
     * @return string
     */
    function get_via()
    {
        return sprintf('Amazon Web Services: CloudFront: %s', $this->get_domain());
    }
}
