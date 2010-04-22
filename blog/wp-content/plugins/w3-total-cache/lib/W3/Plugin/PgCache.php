<?php

/**
 * W3 PgCache plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_PgCache
 */
class W3_Plugin_PgCache extends W3_Plugin
{
    /**
     * Runs plugin
     */
    function run()
    {
        register_activation_hook(W3TC_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3TC_FILE, array(
            &$this, 
            'deactivate'
        ));
        
        add_filter('cron_schedules', array(
            &$this, 
            'cron_schedules'
        ));
        
        if ($this->_config->get_boolean('pgcache.enabled')) {
            if ($this->_config->get_string('pgcache.engine') == 'file' || $this->_config->get_string('pgcache.engine') == 'file_pgcache') {
                add_action('w3_pgcache_cleanup', array(
                    &$this, 
                    'cleanup'
                ));
            }
            
            add_action('publish_phone', array(
                &$this, 
                'on_post_edit'
            ), 0);
            
            add_action('publish_post', array(
                &$this, 
                'on_post_edit'
            ), 0);
            
            add_action('edit_post', array(
                &$this, 
                'on_post_change'
            ), 0);
            
            add_action('delete_post', array(
                &$this, 
                'on_post_edit'
            ), 0);
            
            add_action('comment_post', array(
                &$this, 
                'on_comment_change'
            ), 0);
            
            add_action('edit_comment', array(
                &$this, 
                'on_comment_change'
            ), 0);
            
            add_action('delete_comment', array(
                &$this, 
                'on_comment_change'
            ), 0);
            
            add_action('wp_set_comment_status', array(
                &$this, 
                'on_comment_status'
            ), 0, 2);
            
            add_action('trackback_post', array(
                &$this, 
                'on_comment_change'
            ), 0);
            
            add_action('pingback_post', array(
                &$this, 
                'on_comment_change'
            ), 0);
            
            add_action('switch_theme', array(
                &$this, 
                'on_change'
            ), 0);
            
            add_action('edit_user_profile_update', array(
                &$this, 
                'on_change'
            ), 0);
        }
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_PgCache
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
     * Activate plugin action
     */
    function activate()
    {
        if (!$this->update_wp_config()) {
            $error = sprintf('<strong>%swp-config.php</strong> could not be written, please edit config and add:<br /><strong style="color:#f00;">define(\'WP_CACHE\', true);</strong> before <strong style="color:#f00;">require_once(ABSPATH . \'wp-settings.php\');</strong><br />then re-activate plugin.', ABSPATH);
            
            w3_activate_error($error);
        }
        
        if ($this->_config->get_boolean('pgcache.enabled') && $this->_config->get_string('pgcache.engine') == 'file_pgcache') {
            /**
             * Disable enchanged mode if permalink structure is disabled
             */
            $permalink_structure = get_option('permalink_structure');
            
            if ($permalink_structure == '') {
                $this->_config->set('pgcache.engine', 'file');
                $this->_config->save();
            } else {
                if (!w3_is_wpmu() && !$this->write_rules_core()) {
                    w3_writable_error(ABSPATH . '.htaccess');
                }
                
                if (!$this->write_rules_cache()) {
                    w3_writable_error(W3TC_CACHE_FILE_PGCACHE_DIR . '/.htaccess');
                }
            }
        }
        
        if (!$this->locked()) {
            if (@copy(W3TC_INSTALL_DIR . '/advanced-cache.php', WP_CONTENT_DIR . '/advanced-cache.php')) {
                @chmod(WP_CONTENT_DIR . '/advanced-cache.php', 0644);
            } else {
                w3_writable_error(WP_CONTENT_DIR . '/advanced-cache.php');
            }
        }
        
        $this->schedule();
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        $this->unschedule();
        
        if (!$this->locked()) {
            @unlink(WP_CONTENT_DIR . '/advanced-cache.php');
        }
        
        $this->remove_rules_cache();
        
        if (!w3_is_wpmu()) {
            $this->remove_rules_core();
        }
    }
    
    /**
     * Schedules events
     */
    function schedule()
    {
        if ($this->_config->get_boolean('pgcache.enabled') && ($this->_config->get_string('pgcache.engine') == 'file' || $this->_config->get_string('pgcache.engine') == 'file_pgcache')) {
            if (!wp_next_scheduled('w3_pgcache_cleanup')) {
                wp_schedule_event(time(), 'w3_pgcache_cleanup', 'w3_pgcache_cleanup');
            }
        } else {
            $this->unschedule();
        }
    }
    
    /**
     * Unschedules events
     */
    function unschedule()
    {
        if (wp_next_scheduled('w3_pgcache_cleanup')) {
            wp_clear_scheduled_hook('w3_pgcache_cleanup');
        }
    }
    
    /**
     * Updates WP config
     *
     * @return boolean
     */
    function update_wp_config()
    {
        static $updated = false;
        
        if (defined('WP_CACHE') || $updated) {
            return true;
        }
        
        $config_path = ABSPATH . 'wp-config.php';
        $config_data = @file_get_contents($config_path);
        
        if (!$config_data) {
            return false;
        }
        
        $config_data = preg_replace('~<\?(php)?~', "\\0\r\n/** Enable W3 Total Cache **/\r\ndefine('WP_CACHE', true); // Added by W3 Total Cache\r\n", $config_data, 1);
        
        if (!@file_put_contents($config_path, $config_data)) {
            return false;
        }
        
        $updated = true;
        
        return true;
    }
    
    /**
     * Does disk cache cleanup
     *
     * @return void
     */
    function cleanup()
    {
        $engine = $this->_config->get_string('pgcache.engine');
        
        switch ($engine) {
            case 'file':
                require_once W3TC_LIB_W3_DIR . '/Cache/File/Manager.php';
                
                $w3_cache_file_manager = & new W3_Cache_File_Manager(array(
                    'cache_dir' => W3TC_CACHE_FILE_PGCACHE_DIR
                ));
                
                $w3_cache_file_manager->clean();
                break;
            
            case 'file_pgcache':
                require_once W3TC_LIB_W3_DIR . '/Cache/File/PgCache/Manager.php';
                
                $w3_cache_file_pgcache_manager = & new W3_Cache_File_PgCache_Manager(array(
                    'cache_dir' => W3TC_CACHE_FILE_PGCACHE_DIR, 
                    'expire' => $this->_config->get_integer('pgcache.lifetime')
                ));
                
                $w3_cache_file_pgcache_manager->clean();
                break;
        }
    }
    
    /**
     * Cron schedules filter
     *
     * @paran array $schedules
     * @return array
     */
    function cron_schedules($schedules)
    {
        $gc = $this->_config->get_integer('pgcache.file.gc');
        
        return array_merge($schedules, array(
            'w3_pgcache_cleanup' => array(
                'interval' => $gc, 
                'display' => sprintf('Every %d seconds', $gc)
            )
        ));
    }
    
    /**
     * Post edit action
     *
     * @param integer $post_id
     */
    function on_post_edit($post_id)
    {
        if ($this->_config->get_boolean('pgcache.cache.flush')) {
            $this->on_change();
        } else {
            $this->on_post_change($post_id);
        }
    }
    
    /**
     * Post change action
     *
     * @param integer $post_id
     */
    function on_post_change($post_id)
    {
        static $flushed_posts = array();
        
        if (!in_array($post_id, $flushed_posts)) {
            require_once W3TC_LIB_W3_DIR . '/PgCache.php';
            
            $w3_pgcache = & W3_PgCache::instance();
            $w3_pgcache->flush_post($post_id);
            
            $flushed_posts[] = $post_id;
        }
    }
    
    /**
     * Comment change action
     *
     * @param integer $comment_id
     */
    function on_comment_change($comment_id)
    {
        $post_id = 0;
        
        if ($comment_id) {
            $comment = get_comment($comment_id, ARRAY_A);
            $post_id = !empty($comment['comment_post_ID']) ? (int) $comment['comment_post_ID'] : 0;
        }
        
        $this->on_post_change($post_id);
    }
    
    /**
     * Comment status action
     *
     * @param integer $comment_id
     * @param string $status
     */
    function on_comment_status($comment_id, $status)
    {
        if ($status === 'approve' || $status === '1') {
            $this->on_comment_change($comment_id);
        }
    }
    
    /**
     * Change action
     */
    function on_change()
    {
        static $flushed = false;
        
        if (!$flushed) {
            require_once W3TC_LIB_W3_DIR . '/PgCache.php';
            
            $w3_pgcache = & W3_PgCache::instance();
            $w3_pgcache->flush();
        }
    }
    
    /**
     * Generates rules for WP dir
     *
     * @return string
     */
    function generate_rules_core()
    {
        global $w3_reserved_blognames;
        
        /**
         * Auto reject cookies
         */
        $reject_cookies = array(
            'comment_author', 
            'wp-postpass'
        );
        
        /**
         * Auto reject URIs
         */
        $reject_uris = array(
            '\/wp-admin\/', 
            '\/xmlrpc.php', 
            '\/wp-(app|cron|login|register).php'
        );
        
        /**
         * Reject cache for logged in users
         */
        if ($this->_config->get_boolean('pgcache.reject.logged')) {
            $reject_cookies = array_merge($reject_cookies, array(
                'wordpress_[a-f0-9]+', 
                'wordpress_logged_in'
            ));
        }
        
        /**
         * Reject cache for home page
         */
        if (!$this->_config->get_boolean('pgcache.cache.home')) {
            $reject_uris[] = '^(\/|\/index.php)$';
        }
        
        /**
         * Reject cache for feeds
         */
        if (!$this->_config->get_boolean('pgcache.cache.feed')) {
            $reject_uris[] = 'feed';
        }
        
        /**
         * Custom config
         */
        $reject_cookies = array_merge($reject_cookies, $this->_config->get_array('pgcache.reject.cookie'));
        $reject_uris = array_merge($reject_uris, $this->_config->get_array('pgcache.reject.uri'));
        $reject_user_agents = $this->_config->get_array('pgcache.reject.ua');
        $accept_files = $this->_config->get_array('pgcache.accept.files');
        
        /**
         * WPMU support
         */
        $is_wpmu = w3_is_wpmu();
        $is_vhost = w3_is_vhost();
        
        /**
         * Generate directives
         */
        $rules = '';
        $rules .= "# BEGIN W3 Total Cache\n";
        
        $setenvif_rules = '';
        
        if ($is_wpmu) {
            $setenvif_rules .= "    SetEnvIfNoCase Host ^(www\\.)?([a-z0-9\\-\\.]+\\.[a-z]+)\\.?(:[0-9]+)?$ DOMAIN=$2\n";
            
            if (!$is_vhost) {
                $setenvif_rules .= "    SetEnvIfNoCase Request_URI ^" . w3_get_site_path() . "([a-z0-9\\-]+)/ BLOGNAME=$1\n";
            }
        }
        
        $compression = $this->_config->get_string('pgcache.compression');
        
        if ($compression != '') {
            $compressions = array();
            
            if (stristr($compression, 'gzip') !== false) {
                $compressions[] = 'gzip';
            }
            
            if (stristr($compression, 'deflate') !== false) {
                $compressions[] = 'deflate';
            }
            
            if (count($compressions)) {
                $setenvif_rules .= "    SetEnvIfNoCase Accept-Encoding (" . implode('|', $compressions) . ") APPEND_EXT=.$1\n";
            }
        }
        
        if ($setenvif_rules != '') {
            $rules .= "<IfModule mod_setenvif.c>\n" . $setenvif_rules .= "</IfModule>\n";
        }
        
        $rules .= "<IfModule mod_rewrite.c>\n";
        
        $rules .= "    RewriteEngine On\n";
        
        $mobile_redirect = $this->_config->get_string('pgcache.mobile.redirect');
        
        if ($mobile_redirect != '') {
            $mobile_agents = $this->_config->get_array('pgcache.mobile.agents');
            
            $rules .= "    RewriteCond %{HTTP_USER_AGENT} (" . implode('|', array_map('w3_preg_quote', $mobile_agents)) . ") [NC]\n";
            $rules .= "    RewriteRule .* " . $mobile_redirect . " [R,L]\n";
        }
        
        $rules .= "    RewriteCond %{REQUEST_URI} \\/$\n";
        $rules .= "    RewriteCond %{REQUEST_URI} !(" . implode('|', $reject_uris) . ")";
        
        if (count($accept_files)) {
            $rules .= " [OR]\n    RewriteCond %{REQUEST_URI} (" . implode('|', array_map('w3_preg_quote', $accept_files)) . ") [NC]\n";
        } else {
            $rules .= "\n";
        }
        
        $rules .= "    RewriteCond %{REQUEST_METHOD} !=POST\n";
        $rules .= "    RewriteCond %{QUERY_STRING} =\"\"\n";
        $rules .= "    RewriteCond %{HTTP_COOKIE} !(" . implode('|', array_map('w3_preg_quote', $reject_cookies)) . ") [NC]\n";
        
        if (count($reject_user_agents)) {
            $rules .= "    RewriteCond %{HTTP_USER_AGENT} !(" . implode('|', array_map('w3_preg_quote', $reject_user_agents)) . ") [NC]\n";
        }
        
        if ($is_wpmu) {
            if ($is_vhost) {
                $replacement = '/w3tc-%{ENV:DOMAIN}/';
            } else {
                $rules .= "    RewriteCond %{ENV:BLOGNAME} !^(" . implode('|', $w3_reserved_blognames) . ")$\n";
                $rules .= "    RewriteCond %{ENV:BLOGNAME} !-f\n";
                $rules .= "    RewriteCond %{ENV:BLOGNAME} !-d\n";
                
                $replacement = '/w3tc-%{ENV:BLOGNAME}.%{ENV:DOMAIN}/';
            }
            
            $cache_dir = preg_replace('~/w3tc.*/~U', $replacement, W3TC_CACHE_FILE_PGCACHE_DIR, 1);
        } else {
            $cache_dir = W3TC_CACHE_FILE_PGCACHE_DIR;
        }
        
        $rules .= "    RewriteCond " . w3_path($cache_dir) . "/$1/_default_.html%{ENV:APPEND_EXT} -f\n";
        $rules .= "    RewriteRule (.*) " . str_replace(ABSPATH, '', $cache_dir) . "/$1/_default_.html%{ENV:APPEND_EXT} [L]\n";
        $rules .= "</IfModule>\n";
        
        $rules .= "# END W3 Total Cache\n\n";
        
        if (!$this->check_rules_wp()) {
            $rules .= "# BEGIN WordPress\n";
            $rules .= "<IfModule mod_rewrite.c>\n";
            $rules .= "    RewriteEngine On\n";
            $rules .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
            $rules .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
            $rules .= "    RewriteRule .* index.php [L]\n";
            $rules .= "</IfModule>\n";
            $rules .= "# END WordPress\n\n";
        }
        
        return $rules;
    }
    
    /**
     * Generates directives for file cache dir
     *
     * @return string
     */
    function generate_rules_cache()
    {
        $charset = get_option('blog_charset');
        
        $rules = '';
        $rules .= "# BEGIN W3 Total Cache\n";
        $rules .= "AddDefaultCharset " . ($charset ? $charset : 'UTF-8') . "\n";
        
        $compression = $this->_config->get_string('pgcache.compression');
        
        if ($compression != '') {
            $compressions = array();
            
            if (stristr($compression, 'gzip') !== false) {
                $compressions[] = 'gzip';
            }
            
            if (stristr($compression, 'deflate') !== false) {
                $compressions[] = 'deflate';
            }
            
            if (count($compressions)) {
                $rules .= "<IfModule mod_mime.c>\n";
                
                foreach ($compressions as $_compression) {
                    $rules .= "    AddType text/html ." . $_compression . "\n";
                    $rules .= "    AddEncoding " . $_compression . " .$_compression\n";
                }
                
                $rules .= "</IfModule>\n";
                
                $rules .= "<IfModule mod_deflate.c>\n";
                $rules .= "    SetEnvIfNoCase Request_URI \\.(" . implode('|', $compressions) . ")$ no-gzip\n";
                $rules .= "</IfModule>\n";
            }
        }
        
        $rules .= "<IfModule mod_expires.c>\n";
        $rules .= "    ExpiresActive On\n";
        $rules .= "    ExpiresByType text/html M" . $this->_config->get_integer('pgcache.lifetime') . "\n";
        $rules .= "</IfModule>\n";
        
        $rules .= "<IfModule mod_headers.c>\n";
        $rules .= "    Header set X-Pingback \"" . get_bloginfo('pingback_url') . "\"\n";
        $rules .= "    Header set X-Powered-By \"" . W3TC_POWERED_BY . "\"\n";
        
        if ($compression != '') {
            $rules .= "    Header set Vary \"Accept-Encoding, Cookie\"\n";
        } else {
            $rules .= "    Header set Vary \"Cookie\"\n";
        }
        
        $rules .= "    Header set Pragma public\n";
        $rules .= "    Header append Cache-Control \"public, must-revalidate, proxy-revalidate\"\n";
        $rules .= "</IfModule>\n";
        
        $rules .= "# END W3 Total Cache\n\n";
        
        return $rules;
    }
    
    /**
     * Writes directives to WP .htaccess
     *
     * @return boolean
     */
    function write_rules_core()
    {
        $path = ABSPATH . '.htaccess';
        
        if (file_exists($path)) {
            if (($data = @file_get_contents($path)) !== false) {
                $data = $this->erase_rules_w3tc($data);
                $data = $this->erase_rules_wpsc($data);
            } else {
                return false;
            }
        } else {
            $data = '';
        }
        
        $data = trim($this->generate_rules_core() . $data);
        
        return @file_put_contents($path, $data);
    }
    
    /**
     * Writes directives to file cache .htaccess
     *
     * @return boolean
     */
    function write_rules_cache()
    {
        $path = W3TC_CACHE_FILE_PGCACHE_DIR . '/.htaccess';
        
        if (file_exists($path)) {
            if (($data = @file_get_contents($path)) !== false) {
                $data = $this->erase_rules_w3tc($data);
            } else {
                return false;
            }
        } else {
            $data = '';
        }
        
        $data = trim($this->generate_rules_cache() . $data);
        
        return @file_put_contents($path, $data);
    }
    
    /**
     * Erases W3TC directives from config
     *
     * @param string $data
     * @return string
     */
    function erase_rules_w3tc($data)
    {
        $data = preg_replace('~# BEGIN W3 Total Cache.*# END W3 Total Cache~Us', '', $data);
        $data = trim($data);
        
        return $data;
    }
    
    /**
     * Erases WP Super Cache rules directives config
     *
     * @param string $data
     * @return string
     */
    function erase_rules_wpsc($data)
    {
        $data = preg_replace('~# BEGIN WPSuperCache.*# END WPSuperCache~Us', '', $data);
        $data = trim($data);
        
        return $data;
    }
    
    /**
     * Removes W3TC directives from WP .htaccess
     *
     * @return boolean
     */
    function remove_rules_core()
    {
        $path = ABSPATH . '.htaccess';
        
        if (file_exists($path)) {
            if (($data = @file_get_contents($path)) !== false) {
                $data = $this->erase_rules_w3tc($data);
                
                return @file_put_contents($path, $data);
            }
        } else {
            return true;
        }
        
        return false;
    }
    
    /**
     * Removes W3TC directives from file cache dir
     *
     * @return boolean
     */
    function remove_rules_cache()
    {
        $path = W3TC_CACHE_FILE_PGCACHE_DIR . '/.htaccess';
        
        return @unlink($path);
    }
    
    /**
     * Checks core directives
     *
     * @return boolean
     */
    function check_rules_core()
    {
        $path = ABSPATH . '.htaccess';
        $search = $this->generate_rules_core();
        
        return (($data = @file_get_contents($path)) && strstr(w3_clean_rules($data), w3_clean_rules($search)) !== false && $this->check_rules_wp());
    }
    
    /**
     * Checks cache directives
     *
     * @return boolean
     */
    function check_rules_cache()
    {
        $path = W3TC_CACHE_FILE_PGCACHE_DIR . '/.htaccess';
        $search = $this->generate_rules_cache();
        
        return (($data = @file_get_contents($path)) && strstr(w3_clean_rules($data), w3_clean_rules($search)) !== false);
    }
    
    /**
     * Checks WP directives
     *
     * @return boolean
     */
    function check_rules_wp()
    {
        if (function_exists('is_site_admin')) {
            return true;
        }
        
        $path = ABSPATH . '/.htaccess';
        
        return (($data = @file_get_contents($path)) && preg_match('~# BEGIN WordPress.*# END WordPress~s', w3_clean_rules($data)));
    }
}
