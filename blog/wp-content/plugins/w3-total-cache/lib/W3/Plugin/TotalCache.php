<?php

/**
 * W3 Total Cache plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_TotalCache
 */
class W3_Plugin_TotalCache extends W3_Plugin
{
    /**
     * Page tab
     * @var string
     */
    var $_tab = '';
    
    /**
     * Notes
     * @var array
     */
    var $_notes = array();
    
    /**
     * Errors
     * @var array
     */
    var $_errors = array();
    
    /**
     * Show support reminder flag
     * @var boolean
     */
    var $_support_reminder = false;
    
    /**
     * Used in PHPMailer init function
     * @var string
     */
    var $_phpmailer_sender = '';
    
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
        
        add_action('admin_menu', array(
            &$this, 
            'admin_menu'
        ));
        
        add_filter('plugin_action_links_' . W3TC_FILE, array(
            &$this, 
            'plugin_action_links'
        ));
        
        add_filter('favorite_actions', array(
            &$this, 
            'favorite_actions'
        ));
        
        add_action('init', array(
            &$this, 
            'init'
        ));
        
        add_action('in_plugin_update_message-' . W3TC_FILE, array(
            &$this, 
            'in_plugin_update_message'
        ));
        
        if ($this->_config->get_boolean('widget.latest.enabled')) {
            add_action('wp_dashboard_setup', array(
                &$this, 
                'wp_dashboard_setup'
            ));
        }
        
        if ($this->_config->get_boolean('cdn.enabled')) {
            add_action('switch_theme', array(
                &$this, 
                'switch_theme'
            ));
            
            add_filter('update_feedback', array(
                &$this, 
                'update_feedback'
            ));
        }
        
        if ($this->_config->get_boolean('pgcache.enabled') || $this->_config->get_boolean('minify.enabled')) {
            add_filter('pre_update_option_active_plugins', array(
                &$this, 
                'pre_update_option_active_plugins'
            ));
        }
        
        if ($this->_config->get_string('common.support') == 'footer') {
            add_action('wp_footer', array(
                &$this, 
                'footer'
            ));
        }
        
        if ($this->can_modify_contents()) {
            ob_start(array(
                &$this, 
                'ob_callback'
            ));
        }
        
        /**
         * Run DbCache plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/DbCache.php';
        $w3_plugin_dbcache = & W3_Plugin_DbCache::instance();
        $w3_plugin_dbcache->run();
        
        /**
         * Run PgCache plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/PgCache.php';
        $w3_plugin_pgcache = & W3_Plugin_PgCache::instance();
        $w3_plugin_pgcache->run();
        
        /**
         * Run CDN plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/Cdn.php';
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $w3_plugin_cdn->run();
        
        /**
         * Run Minify plugin
         */
        if (W3TC_PHP5) {
            require_once W3TC_DIR . '/lib/W3/Plugin/Minify.php';
            $w3_plugin_minify = & W3_Plugin_Minify::instance();
            $w3_plugin_minify->run();
        }
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_TotalCache
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
     * Check for buggy site-wide activation
     */
    function wpmu_check()
    {
        $sitewide_plugins = (array) @unserialize(get_site_option('active_sitewide_plugins'));
        $plugins = (array) @unserialize(get_option('active_plugins'));
    }
    
    /**
     * Activate plugin action
     */
    function activate()
    {
        if (!is_dir(W3TC_CONTENT_DIR)) {
            if (@mkdir(W3TC_CONTENT_DIR, 0755)) {
                @chmod(W3TC_CONTENT_DIR, 0755);
            } else {
                w3_writable_error(W3TC_CONTENT_DIR);
            }
        }
        
        if (!is_dir(W3TC_CACHE_FILE_DBCACHE_DIR)) {
            if (@mkdir(W3TC_CACHE_FILE_DBCACHE_DIR, 0755)) {
                @chmod(W3TC_CACHE_FILE_DBCACHE_DIR, 0755);
            } else {
                w3_writable_error(W3TC_CACHE_FILE_DBCACHE_DIR);
            }
        }
        
        if (!is_dir(W3TC_CACHE_FILE_PGCACHE_DIR)) {
            if (@mkdir(W3TC_CACHE_FILE_PGCACHE_DIR, 0755)) {
                @chmod(W3TC_CACHE_FILE_PGCACHE_DIR, 0755);
            } else {
                w3_writable_error(W3TC_CACHE_FILE_PGCACHE_DIR);
            }
        }
        
        if (!is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
            if (@mkdir(W3TC_CACHE_FILE_MINIFY_DIR, 0755)) {
                @chmod(W3TC_CACHE_FILE_MINIFY_DIR, 0755);
            } else {
                w3_writable_error(W3TC_CACHE_FILE_MINIFY_DIR);
            }
        }
        
        if (!is_dir(W3TC_LOG_DIR)) {
            if (@mkdir(W3TC_LOG_DIR, 0755)) {
                @chmod(W3TC_LOG_DIR, 0755);
            } else {
                w3_writable_error(W3TC_LOG_DIR);
            }
        }
        
        if (!is_dir(W3TC_TMP_DIR)) {
            if (@mkdir(W3TC_TMP_DIR, 0755)) {
                @chmod(W3TC_TMP_DIR, 0755);
            } else {
                w3_writable_error(W3TC_TMP_DIR);
            }
        }
        
        if (!$this->_config->get_integer('common.install')) {
            $this->_config->set('common.install', time());
        }
        
        if (w3_is_wpmu()) {
            $this->_config->load_master();
        }
        
        if (!$this->_config->save()) {
            w3_writable_error(W3TC_CONFIG_PATH);
        }
        
        delete_option('w3tc_request_data');
        add_option('w3tc_request_data', '', null, 'no');
        
        $this->link_update();
    }
    
    /**
     * Deactivate plugin action
     * @todo Complete plugin uninstall on site wide activation
     */
    function deactivate()
    {
        $this->link_delete();
        
        delete_option('w3tc_request_data');
        
        w3_rmdir(W3TC_TMP_DIR);
        w3_rmdir(W3TC_LOG_DIR);
        w3_rmdir(W3TC_CACHE_FILE_MINIFY_DIR);
        w3_rmdir(W3TC_CACHE_FILE_PGCACHE_DIR);
        w3_rmdir(W3TC_CACHE_FILE_DBCACHE_DIR);
        w3_rmdir(W3TC_CONTENT_DIR);
    }
    
    /**
     * Init action
     */
    function init()
    {
        $this->check_request();
    }
    
    /**
     * Load action
     */
    function load()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $this->_tab = W3_Request::get_string('tab');
        
        switch (true) {
            case ($this->_tab == 'general'):
            case ($this->_tab == 'pgcache'):
            case ($this->_tab == 'dbcache'):
            case ($this->_tab == 'minify' && W3TC_PHP5):
            case ($this->_tab == 'cdn'):
            case ($this->_tab == 'install'):
            case ($this->_tab == 'faq'):
            case ($this->_tab == 'about'):
            case ($this->_tab == 'support'):
                break;
            
            default:
                $this->_tab = 'general';
        }
        
        /**
         * Flush all caches
         */
        if (isset($_REQUEST['flush_all'])) {
            $this->flush_memcached();
            $this->flush_opcode();
            $this->flush_file();
            
            $this->redirect(array(
                'note' => 'flush_all'
            ), true);
        }
        
        /**
         * Flush memcached cache
         */
        if (isset($_REQUEST['flush_memcached'])) {
            $this->flush_memcached();
            
            $this->redirect(array(
                'note' => 'flush_memcached'
            ), true);
        }
        
        /**
         * Flush APC cache
         */
        if (isset($_REQUEST['flush_opcode'])) {
            $this->flush_opcode();
            
            $this->redirect(array(
                'note' => 'flush_opcode'
            ), true);
        }
        
        /**
         * Flush disk cache
         */
        if (isset($_REQUEST['flush_file'])) {
            $this->flush_file();
            
            $this->redirect(array(
                'note' => 'flush_file'
            ), true);
        }
        
        /**
         * Flush page cache
         */
        if (isset($_REQUEST['flush_pgcache'])) {
            $this->flush_pgcache();
            
            $this->_config->set('notes.need_empty_pgcache', false);
            $this->_config->set('notes.plugins_updated', false);
            
            if (!$this->_config->save()) {
                $this->redirect(array(
                    'error' => 'config_save'
                ), true);
            }
            
            $this->redirect(array(
                'note' => 'flush_pgcache'
            ), true);
        }
        
        /**
         * Flush db cache
         */
        if (isset($_REQUEST['flush_dbcache'])) {
            $this->flush_dbcache();
            
            $this->redirect(array(
                'note' => 'flush_dbcache'
            ), true);
        }
        
        /**
         * Flush minify cache
         */
        if (isset($_REQUEST['flush_minify'])) {
            $this->flush_minify();
            
            $this->_config->set('notes.need_empty_minify', false);
            
            if (!$this->_config->save()) {
                $this->redirect(array(
                    'error' => 'config_save'
                ), true);
            }
            
            $this->redirect(array(
                'note' => 'flush_minify'
            ), true);
        }
        
        /**
         * Hide notes
         */
        if (isset($_REQUEST['hide_note'])) {
            $setting = sprintf('notes.%s', W3_Request::get_string('hide_note'));
            
            $this->_config->set($setting, false);
            
            if (!$this->_config->save()) {
                $this->redirect(array(
                    'error' => 'config_save'
                ), true);
            }
            
            $this->redirect(array(), true);
        }
        
        /**
         * Save config
         */
        if (isset($_REQUEST['save_config'])) {
            if ($this->_config->save()) {
                $this->redirect(array(
                    'note' => 'config_save'
                ), true);
            } else {
                $this->redirect(array(
                    'error' => 'config_save'
                ), true);
            }
        }
        
        /**
         * Write page cache rules
         */
        if (isset($_REQUEST['pgcache_write_rules_core'])) {
            require_once W3TC_LIB_W3_DIR . '/Plugin/PgCache.php';
            $w3_plugin_pgcache = & W3_Plugin_PgCache::instance();
            
            if ($w3_plugin_pgcache->write_rules_core()) {
                $this->redirect(array(
                    'note' => 'pgcache_write_rules_core'
                ));
            } else {
                $this->redirect(array(
                    'error' => 'pgcache_write_rules_core'
                ));
            }
        }
        
        if (isset($_REQUEST['pgcache_write_rules_cache'])) {
            require_once W3TC_LIB_W3_DIR . '/Plugin/PgCache.php';
            $w3_plugin_pgcache = & W3_Plugin_PgCache::instance();
            
            if ($w3_plugin_pgcache->write_rules_cache()) {
                $this->redirect(array(
                    'note' => 'pgcache_write_rules_cache'
                ));
            } else {
                $this->redirect(array(
                    'error' => 'pgcache_write_rules_cache'
                ));
            }
        }
        
        /**
         * Write minify rules
         */
        if (W3TC_PHP5 && isset($_REQUEST['minify_write_rules'])) {
            require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
            $w3_plugin_minify = & W3_Plugin_Minify::instance();
            
            if ($w3_plugin_minify->write_rules()) {
                $this->redirect(array(
                    'note' => 'minify_write_rules'
                ));
            } else {
                $this->redirect(array(
                    'error' => 'minify_write_rules'
                ));
            }
        }
        
        /**
         * Save support us options
         */
        if (isset($_REQUEST['save_support_us'])) {
            $support = W3_Request::get_string('support');
            
            $this->_config->set('common.support', $support);
            
            if (!$this->_config->save()) {
                $this->redirect(array(
                    'error' => 'config_save'
                ));
            }
            
            $this->link_update();
            
            $this->redirect(array(
                'note' => 'config_save'
            ));
        }
        
        /**
         * Run plugin action
         */
        if (isset($_REQUEST['w3tc_action'])) {
            $action = trim($_REQUEST['w3tc_action']);
            
            if (method_exists($this, $action)) {
                call_user_func(array(
                    &$this, 
                    $action
                ));
                exit();
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->_tab == 'support') {
                $this->support_request();
            } else {
                $this->options_save();
            }
            exit();
        }
        
        $this->_support_reminder = ($this->_config->get_boolean('notes.support_us') && $this->_config->get_integer('common.install') < (time() - W3TC_SUPPORT_US_TIMEOUT) && !$this->is_supported());
        
        wp_enqueue_style('w3tc-options', WP_PLUGIN_URL . '/w3-total-cache/inc/css/options.css');
        wp_enqueue_style('w3tc-lightbox', WP_PLUGIN_URL . '/w3-total-cache/inc/css/lightbox.css');
    }
    
    /**
     * Dashboard setup action
     */
    function wp_dashboard_setup()
    {
        wp_add_dashboard_widget('w3tc_latest', 'The Latest from W3 EDGE', array(
            &$this, 
            'widget_latest'
        ), array(
            &$this, 
            'widget_latest_control'
        ));
    }
    
    /**
     * Prints latest widget contents
     */
    function widget_latest()
    {
        global $wp_version;
        
        $items = array();
        $items_count = $this->_config->get_integer('widget.latest.items');
        
        if ($wp_version >= 2.8) {
            include_once (ABSPATH . WPINC . '/feed.php');
            $feed = fetch_feed(W3TC_FEED_URL);
            
            if (!is_wp_error($feed)) {
                $feed_items = $feed->get_items(0, $items_count);
                
                foreach ($feed_items as $feed_item) {
                    $items[] = array(
                        'link' => $feed_item->get_link(), 
                        'title' => $feed_item->get_title(), 
                        'description' => $feed_item->get_description()
                    );
                }
            }
        } else {
            include_once (ABSPATH . WPINC . '/rss.php');
            $rss = fetch_rss(W3TC_FEED_URL);
            
            if (is_object($rss)) {
                $items = array_slice($rss->items, 0, $items_count);
            }
        }
        
        include W3TC_DIR . '/inc/widget/latest.phtml';
    }
    
    /**
     * Latest widget control
     */
    function widget_latest_control($widget_id, $form_inputs = array())
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once W3TC_LIB_W3_DIR . '/Request.php';
            
            $this->_config->set('widget.latest.items', W3_Request::get_integer('w3tc_latest_items', 3));
            $this->_config->save();
        } else {
            include W3TC_DIR . '/inc/widget/latest_control.phtml';
        }
    }
    
    /**
     * Admin menu
     */
    function admin_menu()
    {
        $page = add_options_page('W3 Total Cache', 'W3 Total Cache', 'manage_options', W3TC_FILE, array(
            &$this, 
            'options'
        ));
        
        if (current_user_can('manage_options')) {
            /**
             * Only admin can modify W3TC settings
             */
            add_action('load-' . $page, array(
                &$this, 
                'load'
            ));
            
            /**
             * Only admin can see W3TC notices and errors
             */
            add_action('admin_notices', array(
                &$this, 
                'admin_notices'
            ));
        }
    }
    
    /**
     * Plugin action links filter
     *
     * @return array
     */
    function plugin_action_links($links)
    {
        array_unshift($links, '<a class="edit" href="options-general.php?page=' . W3TC_FILE . '">Settings</a>');
        
        return $links;
    }
    
    /**
     * favorite_actions filter
     */
    function favorite_actions($actions)
    {
        $actions['options-general.php?page=' . W3TC_FILE . '&amp;flush_all'] = array(
            'Empty Caches', 
            'manage_options'
        );
        
        return $actions;
    }
    
    /**
     * Check request and handle w3tc_request_data requests
     */
    function check_request()
    {
        $pos = strpos($_SERVER['REQUEST_URI'], '/w3tc_request_data/');
        
        if ($pos !== false) {
            $hash = substr($_SERVER['REQUEST_URI'], $pos + 19, 32);
            
            if (strlen($hash) == 32) {
                $request_data = (array) get_option('w3tc_request_data');
                
                if (isset($request_data[$hash])) {
                    echo '<pre>';
                    foreach ($request_data[$hash] as $key => $value) {
                        printf("%s: %s\n", $key, $value);
                    }
                    echo '</pre>';
                    
                    unset($request_data[$hash]);
                    update_option('w3tc_request_data', $request_data);
                } else {
                    echo 'Hash is expired or invalid';
                }
                
                exit();
            }
        }
    }
    
    /**
     * Admin notices action
     */
    function admin_notices()
    {
        $error_messages = array(
            'config_save' => sprintf('The settings could not be saved because the config file is not write-able. Please run <strong>chmod 777 %s</strong> to resolve this issue.', file_exists(W3TC_CONFIG_PATH) ? W3TC_CONFIG_PATH : WP_CONTENT_DIR), 
            'fancy_permalinks_disabled' => sprintf('Fancy permalinks are disabled. Please %s it first, then re-attempt to enabling the enhanced disk mode.', $this->button_link('enable', 'options-permalink.php')), 
            'pgcache_write_rules_core' => sprintf('Either your .htaccess file does not exist or cannot be modified (%s.htaccess). Please run <strong>chmod 777 %s.htaccess</strong> to resolve this issue.', ABSPATH, ABSPATH), 
            'pgcache_write_rules_cache' => sprintf('The page cache rules (%s/.htaccess) could not be modified. Please run <strong>chmod 777 %s/.htaccess</strong> to resolve this issue.', W3TC_CACHE_FILE_PGCACHE_DIR, W3TC_CACHE_FILE_PGCACHE_DIR), 
            'minify_write_rules' => sprintf('The minify cache rules (%s/.htaccess) could not be modified. Please run <strong>chmod 777 %s/.htaccess</strong> to resolve this issue.', W3TC_CACHE_FILE_MINIFY_DIR, W3TC_CACHE_FILE_MINIFY_DIR), 
            'support_request_url' => 'Please enter the address of your blog in the Blog <acronym title="Uniform Resource Locator">URL</acronym> field.', 
            'support_request_name' => 'Please enter your name in the Name field', 
            'support_request_email' => 'Please enter valid email address in the E-Mail field.', 
            'support_request_type' => 'Please select request type.', 
            'support_request_description' => 'Please describe the issue in the issue description field.', 
            'support_request_wp_login' => 'Please enter an administrator login. Remember you can create a temporary one just for this support case.', 
            'support_request_wp_password' => 'Please enter WP Admin password, be sure it\'s spelled correctly.', 
            'support_request_ftp_host' => 'Please enter <acronym title="Secure Shell">SSH</acronym> or <acronym title="File Transfer Protocol">FTP</acronym> host for your site.', 
            'support_request_ftp_login' => 'Please enter <acronym title="Secure Shell">SSH</acronym> or <acronym title="File Transfer Protocol">FTP</acronym> login for your server. Remember you can create a temporary one just for this support case.', 
            'support_request_ftp_password' => 'Please enter <acronym title="Secure Shell">SSH</acronym> or <acronym title="File Transfer Protocol">FTP</acronym> password for your <acronym title="File Transfer Protocol">FTP</acronym> account.', 
            'support_request' => 'Unable to send your support request.'
        );
        
        $note_messages = array(
            'config_save' => 'Plugin configuration successfully updated.', 
            'flush_all' => 'All caches successfully emptied.', 
            'flush_memcached' => 'Memcached cache(s) successfully emptied.', 
            'flush_opcode' => 'Opcode cache(s) successfully emptied.', 
            'flush_file' => 'Disk cache successfully emptied.', 
            'flush_pgcache' => 'Page cache successfully emptied.', 
            'flush_dbcache' => 'Database cache successfully emptied.', 
            'flush_minify' => 'Minify cache successfully emptied.', 
            'pgcache_write_rules_core' => 'Page cache rewrite rules have been successfully written.', 
            'pgcache_write_rules_cache' => 'Page cache rewrite rules have been successfully written.', 
            'minify_write_rules' => 'Minify rewrite rules have been successfully written.', 
            'support_request' => 'Your support request has been successfully sent.'
        );
        
        $errors = array();
        $notes = array();
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $error = W3_Request::get_string('error');
        $note = W3_Request::get_string('note');
        
        /**
         * Handle messages from reqeust
         */
        if (isset($error_messages[$error])) {
            $errors[] = $error_messages[$error];
        }
        
        if (isset($note_messages[$note])) {
            $notes[] = $note_messages[$note];
        }
        
        /**
         * Check config file
         */
        if (!file_exists(W3TC_CONFIG_PATH)) {
            $errors[] = sprintf('<strong>W3 Total Cache Error:</strong> Default settings are in use. The configuration file could not be read or doesn\'t exist. Please %s to create the file.', $this->button_link('save your settings', sprintf('options-general.php?page=%s&tab=%s&save_config', W3TC_FILE, $this->_tab)));
        }
        
        /**
         * CDN notifications
         */
        if ($this->_config->get_boolean('cdn.enabled') && $this->_config->get_string('cdn.engine') != 'mirror') {
            /**
             * Show notification after theme change
             */
            if ($this->_config->get_boolean('notes.theme_changed')) {
                $notes[] = sprintf('Your active theme has changed, please %s now to ensure proper operation. %s', $this->button_popup('upload active theme files', 'cdn_export', 'cdn_export_type=theme'), $this->button_hide_note('Hide this message', 'theme_changed'));
            }
            
            /**
             * Show notification after WP upgrade
             */
            if ($this->_config->get_boolean('notes.wp_upgraded')) {
                $notes[] = sprintf('Have you upgraded WordPress? Please %s files now to ensure proper operation. %s', $this->button_popup('upload wp-includes', 'cdn_export', 'cdn_export_type=includes'), $this->button_hide_note('Hide this message', 'wp_upgraded'));
            }
            
            /**
             * Show notification after CDN enable
             */
            if ($this->_config->get_boolean('notes.cdn_upload')) {
                $cdn_upload_buttons = array();
                
                if ($this->_config->get_boolean('cdn.includes.enable')) {
                    $cdn_upload_buttons[] = $this->button_popup('wp-includes', 'cdn_export', 'cdn_export_type=includes');
                }
                
                if ($this->_config->get_boolean('cdn.theme.enable')) {
                    $cdn_upload_buttons[] = $this->button_popup('theme files', 'cdn_export', 'cdn_export_type=theme');
                }
                
                if ($this->_config->get_boolean('minify.enabled') && $this->_config->get_boolean('cdn.minify.enable')) {
                    $cdn_upload_buttons[] = $this->button_popup('minify files', 'cdn_export', 'cdn_export_type=minify');
                }
                
                if ($this->_config->get_boolean('cdn.custom.enable')) {
                    $cdn_upload_buttons[] = $this->button_popup('custom files', 'cdn_export', 'cdn_export_type=custom');
                }
                
                $notes[] = sprintf('Make sure to %s and upload your %s, files to the CDN to ensure proper operation. %s', $this->button_popup('export your media library', 'cdn_export_library'), implode(', ', $cdn_upload_buttons), $this->button_hide_note('Hide this message', 'cdn_upload'));
            }
        }
        
        /**
         * Show notification after plugin activate/deactivate
         */
        if ($this->_config->get_boolean('notes.plugins_updated')) {
            $texts = array();
            
            if ($this->_config->get_boolean('pgcache.enabled')) {
                $texts[] = $this->button_link('empty the page cache', sprintf('options-general.php?page=%s&tab=%s&flush_pgcache', W3TC_FILE, $this->_tab));
            }
            
            if ($this->_config->get_boolean('minify.enabled')) {
                $texts[] = sprintf('check your %s to maintain the desired user experience', $this->button_hide_note('minify settings', 'plugins_updated', sprintf('options-general.php?page=%s&tab=minify', W3TC_FILE)));
            }
            
            if (count($texts)) {
                $notes[] = sprintf('One or more plugins have been activated or deactivated, please %s. %s', implode(' and ', $texts), $this->button_hide_note('Hide this message', 'plugins_updated'));
            }
        }
        
        /**
         * Show notification when page cache needs to be emptied
         */
        if ($this->_config->get_boolean('pgcache.enabled') && $this->_config->get('notes.need_empty_pgcache')) {
            $notes[] = sprintf('The setting change(s) made either invalidate your cached data or modify the behavior of your site. %s now to provide a consistent user experience.', $this->button_link('Empty the page cache', sprintf('options-general.php?page=%s&tab=%s&flush_pgcache', W3TC_FILE, $this->_tab)));
        }
        
        /**
         * Show notification when minify needs to be emptied
         */
        if ($this->_config->get_boolean('minify.enabled') && $this->_config->get('notes.need_empty_minify')) {
            $notes[] = sprintf('The setting change(s) made either invalidate your cached data or modify the behavior of your site. %s now to provide a consistent user experience.', $this->button_link('Empty the minify cache', sprintf('options-general.php?page=%s&tab=%s&flush_minify', W3TC_FILE, $this->_tab)));
        }
        
        /**
         * Show messages
         */
        foreach ($errors as $error) {
            echo sprintf('<div class="error"><p>%s</p></div>', $error);
        }
        
        foreach ($notes as $note) {
            echo sprintf('<div class="updated fade"><p>%s</p></div>', $note);
        }
    }
    
    /**
     * Switch theme action
     */
    function switch_theme()
    {
        $this->_config->set('notes.theme_changed', true);
        $this->_config->save();
    }
    
    /**
     * WP Upgrade action hack
     *
     * @param string $message
     */
    function update_feedback($message)
    {
        if ($message == __('Upgrading database')) {
            $this->_config->set('notes.wp_upgraded', true);
            $this->_config->save();
        }
    }
    
    /**
     * Active plugins pre update option filter
     */
    function pre_update_option_active_plugins($new_value)
    {
        $old_value = (array) get_option('active_plugins');
        
        if ($new_value !== $old_value && in_array(W3TC_FILE, (array) $new_value) && in_array(W3TC_FILE, (array) $old_value)) {
            $this->_config->set('notes.plugins_updated', true);
            $this->_config->save();
        }
        
        return $new_value;
    }
    
    /**
     * Show plugin changes
     */
    function in_plugin_update_message()
    {
        $data = w3_url_get(W3TC_README_URL);
        
        if ($data) {
            $matches = null;
            if (preg_match('~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*=(.*)(=\s*[0-9.]+\s*=|$)~Uis', $data, $matches)) {
                $changelog = (array) preg_split('~[\r\n]+~', trim($matches[1]));
                
                echo '<div style="color: #f00;">Take a minute to update, here\'s why:</div><div style="font-weight: normal;">';
                $ul = false;
                
                foreach ($changelog as $index => $line) {
                    if (preg_match('~^\s*\*\s*~', $line)) {
                        if (!$ul) {
                            echo '<ul style="list-style: disc; margin-left: 20px;">';
                            $ul = true;
                        }
                        $line = preg_replace('~^\s*\*\s*~', '', htmlspecialchars($line));
                        echo '<li style="width: 50%; margin: 0; float: left; ' . ($index % 2 == 0 ? 'clear: left;' : '') . '">' . $line . '</li>';
                    } else {
                        if ($ul) {
                            echo '</ul><div style="clear: left;"></div>';
                            $ul = false;
                        }
                        echo '<p style="margin: 5px 0;">' . htmlspecialchars($line) . '</p>';
                    }
                }
                
                if ($ul) {
                    echo '</ul><div style="clear: left;"></div>';
                }
                
                echo '</div>';
            }
        }
    }
    
    /**
     * Footer plugin action
     */
    function footer()
    {
        echo '<div style="text-align: center;">Performance Optimization <a href="http://www.w3-edge.com/wordpress-plugins/" rel="external">WordPress Plugins</a> by W3 EDGE</div>';
    }
    
    /**
     * Options page
     */
    function options()
    {
        /**
         * Check for page cache availability
         */
        if ($this->_config->get_boolean('pgcache.enabled')) {
            if (!$this->check_advanced_cache()) {
                $this->_errors[] = sprintf('Page caching is not available: advanced-cache.php is not installed. Either the <strong>%s</strong> directory is not write-able or you have another caching plugin installed. This error message will automatically disappear once the change is successfully made.', WP_CONTENT_DIR);
            } elseif (!defined('WP_CACHE')) {
                $this->_errors[] = sprintf('Page caching is not available: please add: <strong>define(\'WP_CACHE\', true);</strong> to <strong>%swp-config.php</strong>. This error message will automatically disappear once the change is successfully made.', ABSPATH);
            } else {
                switch ($this->_config->get_string('pgcache.engine')) {
                    case 'file_pgcache':
                        require_once W3TC_LIB_W3_DIR . '/Plugin/PgCache.php';
                        $w3_plugin_pgcache = & W3_Plugin_PgCache::instance();
                        
                        if ($this->_config->get_boolean('notes.pgcache_rules_core') && !$w3_plugin_pgcache->check_rules_core()) {
                            if (w3_is_wpmu()) {
                                $this->_errors[] = sprintf('Enhanced mode page cache is not operational. Your .htaccess rules could not be modified. Please verify <strong>%s.htaccess</strong> has the following rules: <pre>%s</pre> %s', ABSPATH, htmlspecialchars($w3_plugin_pgcache->generate_rules_core()), $this->button_hide_note('Hide this message', 'pgcache_rules_core'));
                            } else {
                                $this->_errors[] = sprintf('You\'ve selected disk caching with enhanced mode however the .htaccess file is not properly configured. Please run <strong>chmod 777 %s.htaccess</strong>, then %s. To manually modify your server configuration for enhanced mode append the following code: <pre>%s</pre> and %s.', ABSPATH, $this->button_link('try again', sprintf('options-general.php?page=%s&tab=%s&pgcache_write_rules_core', W3TC_FILE, $this->_tab)), htmlspecialchars($w3_plugin_pgcache->generate_rules_core()), $this->button_hide_note('hide this message', 'pgcache_rules_core'));
                            }
                        }
                        
                        if ($this->_config->get_boolean('notes.pgcache_rules_cache') && !$w3_plugin_pgcache->check_rules_cache()) {
                            $this->_errors[] = sprintf('You\'ve selected disk caching with enhanced mode however the .htaccess file is not properly configured. Please run <strong>chmod 777 %s/.htaccess</strong>, then %s. To manually modify your server configuration for enhanced mode append the following code: <pre>%s</pre> and %s.', W3TC_CACHE_FILE_PGCACHE_DIR, $this->button_link('try again', sprintf('options-general.php?page=%s&tab=%s&pgcache_write_rules_cache', W3TC_FILE, $this->_tab)), htmlspecialchars($w3_plugin_pgcache->generate_rules_cache()), $this->button_hide_note('hide this message', 'pgcache_rules_cache'));
                        }
                        break;
                    
                    case 'memcached':
                        $pgcache_memcached_servers = $this->_config->get_array('pgcache.memcached.servers');
                        
                        if (!$this->is_memcache_available($pgcache_memcached_servers)) {
                            $this->_errors[] = sprintf('Page caching is not working properly. Memcached server(s): <strong>%s</strong> may not running or not responding. This error message will automatically disappear once the issue is resolved.', implode(', ', $pgcache_memcached_servers));
                        }
                        break;
                }
            }
        }
        
        /**
         * Check for minify availability
         */
        if ($this->_config->get_boolean('minify.enabled')) {
            switch ($this->_config->get_string('minify.engine')) {
                case 'file':
                    if (W3TC_PHP5) {
                        require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
                        $w3_plugin_minify = & W3_Plugin_Minify::instance();
                        
                        if ($this->_config->get_boolean('notes.minify_rules') && !$w3_plugin_minify->check_rules()) {
                            $this->_errors[] = sprintf('The "Rewrite URL Structure" feature, requires rewrite rules be present. Please run <strong>chmod 777 %s/.htaccess</strong>, then %s. To manually modify your server configuration for minify append the following code: <pre>%s</pre> and %s.', W3TC_CACHE_FILE_MINIFY_DIR, $this->button_link('try again', sprintf('options-general.php?page=%s&tab=%s&minify_write_rules', W3TC_FILE, $this->_tab)), htmlspecialchars($w3_plugin_minify->generate_rules()), $this->button_hide_note('hide this message', 'minify_rules'));
                        }
                    }
                    break;
                
                case 'memcached':
                    $minify_memcached_servers = $this->_config->get_array('minify.memcached.servers');
                    
                    if (!$this->is_memcache_available($minify_memcached_servers)) {
                        $this->_errors[] = sprintf('Minify is not working properly. Memcached server(s): <strong>%s</strong> may not running or not responding. This error message will automatically disappear once the issue is resolved.', implode(', ', $minify_memcached_servers));
                    }
                    break;
            }
        }
        
        /**
         * Check for database cache availability
         */
        if ($this->_config->get_boolean('dbcache.enabled')) {
            if (!$this->check_db()) {
                $this->_errors[] = sprintf('Database caching is not available: db.php is not installed. Either the <strong>%s</strong> directory is not write-able or you have another caching plugin installed. This error message will automatically disappear once the change is successfully made.', WP_CONTENT_DIR);
            } elseif ($this->_config->get_string('pgcache.engine') == 'memcached') {
                $dbcache_memcached_servers = $this->_config->get_array('dbcache.memcached.servers');
                
                if (!$this->is_memcache_available($dbcache_memcached_servers)) {
                    $this->_errors[] = sprintf('Database caching is not working properly. Memcached server(s): <strong>%s</strong> may not running or not responding. This error message will automatically disappear once the issue is successfully resolved.', implode(', ', $dbcache_memcached_servers));
                }
            }
        }
        
        /**
         * Check PHP version
         */
        if (!W3TC_PHP5 && $this->_config->get_boolean('notes.php_is_old')) {
            $this->_notes[] = sprintf('Unfortunately, <strong>PHP5</strong> is required for full functionality of this plugin; incompatible features are automatically disabled. Please upgrade if possible. %s', $this->button_hide_note('Hide this message', 'php_is_old'));
        }
        
        /**
         * Check CURL extension
         */
        if ($this->_config->get_boolean('notes.no_curl') && $this->_config->get_boolean('cdn.enabled') && !function_exists('curl_init')) {
            $this->_notes[] = sprintf('The <strong>CURL PHP</strong> extension is not available. Please install it to enable S3 or CloudFront functionality. %s', $this->button_hide_note('Hide this message', 'no_curl'));
        }
        
        /**
         * Check Zlib extension
         */
        if ($this->_config->get_boolean('notes.no_zlib') && (!function_exists('gzencode') || !function_exists('gzdeflate'))) {
            $this->_notes[] = sprintf('Unfortunately the PHP installation is incomplete, the <strong>zlib module is missing</strong>. This is a core PHP module. Please notify your server administrator and ask for it to be installed. %s', $this->button_hide_note('Hide this message', 'no_zlib'));
        }
        
        /**
         * Check if Zlib output compression is enabled
         */
        if ($this->_config->get_boolean('notes.zlib_output_compression') && w3_zlib_output_compression()) {
            $this->_notes[] = sprintf('Either the PHP configuration, Web Server configuration or a script somewhere in your WordPress installation is has set <strong>zlib.output_compression</strong> to enabled.<br />Please locate and disable this setting to ensure proper HTTP compression management. %s', $this->button_hide_note('Hide this message', 'zlib_output_compression'));
        }
        
        /**
         * Show message when defaults are set
         */
        if ($this->_config->get_boolean('notes.defaults')) {
            $this->_notes[] = sprintf('The plugin is in quick setup mode, most recommended defaults are set. Satisfy any warnings customizing any settings. %s', $this->button_hide_note('Hide this message', 'defaults'));
        }
        
        /**
         * Check wp-content permissions
         */
        if (!W3TC_WIN && $this->_config->get_boolean('notes.wp_content_perms')) {
            $wp_content_stat = stat(WP_CONTENT_DIR);
            $wp_content_mode = ($wp_content_stat['mode'] & 0777);
            
            if ($wp_content_mode != 0755) {
                $this->_notes[] = sprintf('<strong>%s</strong> is write-able. If you\'ve finished installing the plugin, change the permissions back to the default: <strong>chmod 755 %s</strong>. %s', WP_CONTENT_DIR, WP_CONTENT_DIR, $this->button_hide_note('Hide this message', 'wp_content_perms'));
            }
        }
        
        /**
         * Check CDN settings
         */
        if ($this->_config->get_boolean('cdn.enabled')) {
            $cdn_engine = $this->_config->get_string('cdn.engine');
            
            switch (true) {
                case ($cdn_engine == 'mirror' && $this->_config->get_string('cdn.mirror.domain') == ''):
                    $this->_errors[] = 'The <strong>"Replace default hostname with"</strong> field must be populated.';
                    break;
                
                case ($cdn_engine == 'ftp' && $this->_config->get_string('cdn.ftp.domain') == ''):
                    $this->_errors[] = 'The <strong>"Replace default hostname with"</strong> field must be populated. Enter the hostname of your <acronym title="Content Delivery Network">CDN</acronym> provider. <em>This is the hostname you would enter into your address bar in order to view objects in your browser.</em>';
                    break;
                
                case ($cdn_engine == 's3' && ($this->_config->get_string('cdn.s3.key') == '' || $this->_config->get_string('cdn.s3.bucket') == '' || $this->_config->get_string('cdn.s3.bucket') == '')):
                    $this->_errors[] = 'The <strong>"Access key", "Secret key" and "Bucket"</strong> fields must be populated.';
                    break;
                
                case ($cdn_engine == 'cf' && ($this->_config->get_string('cdn.cf.key') == '' || $this->_config->get_string('cdn.cf.secret') == '' || $this->_config->get_string('cdn.cf.bucket') == '' || ($this->_config->get_string('cdn.cf.id') == '' && $this->_config->get_string('cdn.cf.cname') == ''))):
                    $this->_errors[] = 'The <strong>"Access key", "Secret key", "Bucket" and "Replace default hostname with"</strong> fields must be populated.';
                    break;
            }
        }
        
        /**
         * Show tab
         */
        switch ($this->_tab) {
            case 'general':
                $this->options_general();
                break;
            
            case 'pgcache':
                $this->options_pgcache();
                break;
            
            case 'dbcache':
                $this->options_dbcache();
                break;
            
            case 'minify':
                $this->options_minify();
                break;
            
            case 'cdn':
                $this->options_cdn();
                break;
            
            case 'faq':
                $this->options_faq();
                break;
            
            case 'support':
                $this->options_support();
                break;
            
            case 'install':
                $this->options_install();
                break;
            
            case 'about':
                $this->options_about();
                break;
        }
    }
    
    /**
     * General tab
     */
    function options_general()
    {
        $pgcache_enabled = $this->_config->get_boolean('pgcache.enabled');
        $dbcache_enabled = $this->_config->get_boolean('dbcache.enabled');
        $minify_enabled = $this->_config->get_boolean('minify.enabled');
        $cdn_enabled = $this->_config->get_boolean('cdn.enabled');
        
        $enabled = ($pgcache_enabled || $dbcache_enabled || $minify_enabled || $cdn_enabled);
        
        $check_apc = function_exists('apc_store');
        $check_eaccelerator = function_exists('eaccelerator_put');
        $check_xcache = function_exists('xcache_set');
        $check_curl = function_exists('curl_init');
        $check_memcached = class_exists('Memcache');
        
        $pgcache_engine = $this->_config->get_string('pgcache.engine');
        $dbcache_engine = $this->_config->get_string('dbcache.engine');
        $minify_engine = $this->_config->get_string('minify.engine');
        
        $can_empty_memcache = ($pgcache_engine == 'memcached' || $dbcache_engine == 'memcached' || $minify_engine == 'memcached');
        
        $can_empty_opcode = ($pgcache_engine == 'apc' || $pgcache_engine == 'eaccelerator' || $pgcache_engine == 'xcache');
        $can_empty_opcode = $can_empty_opcode || ($dbcache_engine == 'apc' || $dbcache_engine == 'eaccelerator' || $dbcache_engine == 'xcache');
        $can_empty_opcode = $can_empty_opcode || ($minify_engine == 'apc' || $minify_engine == 'eaccelerator' || $minify_engine == 'xcache');
        
        $can_empty_file = ($pgcache_engine == 'file' || $pgcache_engine == 'file_pgcache' || $dbcache_engine == 'file' || $minify_engine == 'file');
        
        $debug = ($this->_config->get_boolean('dbcache.debug') || $this->_config->get_boolean('pgcache.debug') || $this->_config->get_boolean('minify.debug') || $this->_config->get_boolean('cdn.debug'));
        
        $support = $this->_config->get_string('common.support');
        $supports = $this->get_supports();
        
        include W3TC_DIR . '/inc/options/general.phtml';
    }
    
    /**
     * Page cache tab
     */
    function options_pgcache()
    {
        $pgcache_enabled = $this->_config->get_boolean('pgcache.enabled');
        $pgcache_gzip = function_exists('gzencode');
        $pgcache_deflate = function_exists('gzdeflate');
        
        include W3TC_DIR . '/inc/options/pgcache.phtml';
    }
    
    /**
     * Minify tab
     */
    function options_minify()
    {
        $minify_enabled = $this->_config->get_boolean('minify.enabled');
        $minify_gzip = function_exists('gzencode');
        $minify_deflate = function_exists('gzdeflate');
        
        $groups = $this->minify_get_groups();
        
        $js_group = W3_Request::get_string('js_group', 'default');
        $js_groups = $this->_config->get_array('minify.js.groups');
        
        $css_group = W3_Request::get_string('css_group', 'default');
        $css_groups = $this->_config->get_array('minify.css.groups');
        
        include W3TC_DIR . '/inc/options/minify.phtml';
    }
    
    /**
     * Database cache tab
     */
    function options_dbcache()
    {
        $dbcache_enabled = $this->_config->get_boolean('dbcache.enabled');
        
        include W3TC_DIR . '/inc/options/dbcache.phtml';
    }
    
    /**
     * CDN tab
     */
    function options_cdn()
    {
        $cdn_enabled = $this->_config->get_boolean('cdn.enabled');
        $cdn_engine = $this->_config->get_string('cdn.engine');
        $cdn_mirror = ($cdn_engine == 'mirror');
        
        $minify_enabled = $this->_config->get_boolean('minify.enabled');
        
        include W3TC_DIR . '/inc/options/cdn.phtml';
    }
    
    /**
     * FAQ tab
     */
    function options_faq()
    {
        include W3TC_DIR . '/inc/options/faq.phtml';
    }
    
    /**
     * Support tab
     */
    function options_support()
    {
        $theme = get_theme(get_current_theme());
        $template_files = (isset($theme['Template Files']) ? (array) $theme['Template Files'] : array());
        
        $request_types = array(
            'Bug Submission', 
            'Plugin (add-on) Request'
        );
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $user = get_user_by('login', 'admin');
        
        $url = W3_Request::get_string('url', w3_get_domain_url());
        $name = W3_Request::get_string('name', ($user ? $user->display_name : ''));
        $email = W3_Request::get_string('email', ($user ? $user->user_email : ''));
        $request_type = W3_Request::get_string('request_type');
        $description = W3_Request::get_string('description');
        $templates = W3_Request::get_array('templates');
        $wp_login = W3_Request::get_string('wp_login');
        $wp_password = W3_Request::get_string('wp_password');
        $ftp_host = W3_Request::get_string('ftp_host');
        $ftp_login = W3_Request::get_string('ftp_login');
        $ftp_password = W3_Request::get_string('ftp_password');
        
        include W3TC_DIR . '/inc/options/support.phtml';
    }
    
    /**
     * Install tab
     */
    function options_install()
    {
        include W3TC_DIR . '/inc/options/install.phtml';
    }
    
    /**
     * About tab
     */
    function options_about()
    {
        include W3TC_DIR . '/inc/options/about.phtml';
    }
    
    /**
     * Options save action
     */
    function options_save()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        /**
         * Redirect params
         */
        $params = array();
        
        /**
         * Read config
         */
        $config = & new W3_Config();
        $config->read_request();
        
        /**
         * General tab
         */
        if ($this->_tab == 'general') {
            $debug = W3_Request::get_array('debug');
            
            $config->set('dbcache.debug', in_array('dbcache', $debug));
            $config->set('pgcache.debug', in_array('pgcache', $debug));
            $config->set('minify.debug', in_array('minify', $debug));
            $config->set('cdn.debug', in_array('cdn', $debug));
            
            /**
             * Page cache tab
             */
            if ($config->get_boolean('pgcache.enabled') && $config->get_string('pgcache.engine') == 'file_pgcache' && get_option('permalink_structure') == '') {
                $this->redirect(array(
                    'error' => 'fancy_permalinks_disabled'
                ));
            }
            
            /**
             * Show notification when CDN enabled
             */
            if ($this->_config->get_boolean('cdn.enabled') == false && $config->get_boolean('cdn.enabled') == true && $config->get_string('cdn.engine') != 'mirror') {
                $config->set('notes.cdn_upload', true);
            }
        }
        
        /**
         * Minify tab
         */
        if ($this->_tab == 'minify') {
            $groups = $this->minify_get_groups();
            
            $js_group = W3_Request::get_string('js_group');
            $js_files = W3_Request::get_array('js_files');
            
            $css_group = W3_Request::get_string('css_group');
            $css_files = W3_Request::get_array('css_files');
            
            $js_groups = array();
            $css_groups = array();
            
            foreach ($js_files as $group => $locations) {
                if (!array_key_exists($group, $groups)) {
                    continue;
                }
                
                foreach ((array) $locations as $location => $files) {
                    switch ($location) {
                        case 'include':
                            $js_groups[$group][$location]['blocking'] = true;
                            break;
                        case 'include-nb':
                            $js_groups[$group][$location]['blocking'] = false;
                            break;
                        case 'include-footer':
                            $js_groups[$group][$location]['blocking'] = true;
                            break;
                        case 'include-footer-nb':
                            $js_groups[$group][$location]['blocking'] = false;
                            break;
                    }
                    foreach ((array) $files as $file) {
                        if (!empty($file)) {
                            $js_groups[$group][$location]['files'][] = w3_normalize_file($file);
                        }
                    }
                }
            }
            
            foreach ($css_files as $group => $locations) {
                if (!array_key_exists($group, $groups)) {
                    continue;
                }
                
                foreach ((array) $locations as $location => $files) {
                    foreach ((array) $files as $file) {
                        if (!empty($file)) {
                            $css_groups[$group][$location]['files'][] = w3_normalize_file($file);
                        }
                    }
                }
            }
            
            $config->set('minify.js.groups', $js_groups);
            $config->set('minify.css.groups', $css_groups);
            
            $params = array_merge($params, array(
                'js_group' => $js_group, 
                'css_group' => $css_group
            ));
        }
        
        /**
         * Handle settings change that require pgcache and minify empty
         */
        $pgcache_dependencies = array(
            'pgcache.debug', 
            'dbcache.enabled', 
            'minify.enabled', 
            'cdn.enabled'
        );
        
        if ($config->get_boolean('dbcache.enabled')) {
            $pgcache_dependencies = array_merge($pgcache_dependencies, array(
                'dbcache.debug'
            ));
        }
        
        if ($config->get_boolean('minify.enabled')) {
            $pgcache_dependencies = array_merge($pgcache_dependencies, array(
                'minify.debug', 
                'minify.rewrite', 
                'minify.options', 
                'minify.html.enable', 
                'minify.html.reject.admin', 
                'minify.html.inline.css', 
                'minify.html.inline.js', 
                'minify.html.strip.crlf', 
                'minify.css.enable', 
                'minify.css.groups', 
                'minify.js.enable', 
                'minify.js.groups'
            ));
        }
        
        if ($config->get_boolean('cdn.enabled')) {
            $pgcache_dependencies = array_merge($pgcache_dependencies, array(
                'cdn.debug', 
                'cdn.engine', 
                'cdn.includes.enable', 
                'cdn.includes.files', 
                'cdn.theme.enable', 
                'cdn.theme.files', 
                'cdn.minify.enable', 
                'cdn.custom.enable', 
                'cdn.custom.files', 
                'cdn.ftp.domain', 
                'cdn.s3.bucket', 
                'cdn.cf.id', 
                'cdn.cf.cname'
            ));
        }
        
        $minify_dependencies = array(
            'minify.debug', 
            'minify.css.combine', 
            'minify.css.strip.comments', 
            'minify.css.strip.crlf', 
            'minify.css.groups', 
            'minify.js.combine.header', 
            'minify.js.combine.footer', 
            'minify.js.strip.comments', 
            'minify.js.strip.crlf', 
            'minify.js.groups'
        );
        
        $old_pgcache_dependencies_values = array();
        $new_pgcache_dependencies_values = array();
        
        $old_minify_dependencies_values = array();
        $new_minify_dependencies_values = array();
        
        foreach ($pgcache_dependencies as $pgcache_dependency) {
            $old_pgcache_dependencies_values[] = $this->_config->get($pgcache_dependency);
            $new_pgcache_dependencies_values[] = $config->get($pgcache_dependency);
        }
        
        foreach ($minify_dependencies as $minify_dependency) {
            $old_minify_dependencies_values[] = $this->_config->get($minify_dependency);
            $new_minify_dependencies_values[] = $config->get($minify_dependency);
        }
        
        if ($this->_config->get_boolean('pgcache.enabled') && serialize($old_pgcache_dependencies_values) != serialize($new_pgcache_dependencies_values)) {
            $config->set('notes.need_empty_pgcache', true);
        }
        
        if ($this->_config->get_boolean('minify.enabled') && serialize($old_minify_dependencies_values) != serialize($new_minify_dependencies_values)) {
            $config->set('notes.need_empty_minify', true);
        }
        
        /**
         * Save config
         */
        if ($config->save()) {
            require_once W3TC_LIB_W3_DIR . '/Plugin/PgCache.php';
            require_once W3TC_LIB_W3_DIR . '/Plugin/DbCache.php';
            require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
            
            $w3_plugin_pgcache = & W3_Plugin_PgCache::instance();
            $w3_plugin_dbcache = & W3_Plugin_DbCache::instance();
            $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
            
            if (W3TC_PHP5) {
                require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
                $w3_plugin_minify = & W3_Plugin_Minify::instance();
            }
            
            /**
             * Empty caches on engine change or cache enable/disable
             */
            if ($this->_config->get_string('pgcache.engine') != $config->get_string('pgcache.engine') || $this->_config->get_string('pgcache.enabled') != $config->get_string('pgcache.enabled')) {
                $this->flush_pgcache();
            }
            
            if ($this->_config->get_string('dbcache.engine') != $config->get_string('dbcache.engine') || $this->_config->get_string('dbcache.enabled') != $config->get_string('dbcache.enabled')) {
                $this->flush_dbcache();
            }
            
            if ($this->_config->get_string('minify.engine') != $config->get_string('minify.engine') || $this->_config->get_string('minify.enabled') != $config->get_string('minify.enabled')) {
                $this->flush_minify();
            }
            
            /**
             * Unschedule events if changed file gc interval
             */
            if ($this->_config->get_boolean('pgcache.file.gc') != $config->get_boolean('pgcache.file.gc')) {
                $w3_plugin_pgcache->unschedule();
            }
            
            if ($this->_config->get_boolean('dbcache.file.gc') != $config->get_boolean('dbcache.file.gc')) {
                $w3_plugin_dbcache->unschedule();
            }
            
            if (W3TC_PHP5 && $this->_config->get_boolean('minify.file.gc') != $config->get_boolean('minify.file.gc')) {
                $w3_plugin_minify->unschedule();
            }
            
            $this->_config->load();
            
            /**
             * Schedule events
             */
            $w3_plugin_pgcache->schedule();
            $w3_plugin_dbcache->schedule();
            $w3_plugin_cdn->schedule();
            
            if (W3TC_PHP5) {
                $w3_plugin_minify->schedule();
            }
            
            /**
             * Update support us option
             */
            $this->link_update();
            
            /**
             * Auto upload minify files to CDN
             */
            if ($this->_tab == 'minify' && $this->_config->get_boolean('minify.upload') && $this->_config->get_boolean('cdn.enabled') && $this->_config->get_string('cdn.engine') != 'mirror') {
                $this->cdn_upload_minify();
            }
            
            /**
             * Write page cache rewrite rules
             */
            if ($this->_tab == 'general' || $this->_tab == 'pgcache') {
                $is_wpmu = w3_is_wpmu();
                
                if ($this->_config->get_boolean('pgcache.enabled') && $this->_config->get_string('pgcache.engine') == 'file_pgcache') {
                    if (!$is_wpmu) {
                        $w3_plugin_pgcache->write_rules_core();
                    }
                    
                    $w3_plugin_pgcache->write_rules_cache();
                } else {
                    if (!$is_wpmu) {
                        $w3_plugin_pgcache->remove_rules_core();
                    }
                    
                    $w3_plugin_pgcache->remove_rules_cache();
                }
            }
            
            /**
             * Write minify rewrite rules
             */
            if (W3TC_PHP5 && ($this->_tab == 'general' || $this->_tab == 'minify')) {
                if ($this->_config->get_boolean('minify.enabled') && $this->_config->get_boolean('minify.rewrite')) {
                    $w3_plugin_minify->write_rules();
                } else {
                    require_once W3TC_DIR . '/lib/W3/Plugin/Minify.php';
                    $w3_plugin_minify->remove_rules();
                }
            }
            
            $this->redirect(array_merge($params, array(
                'note' => 'config_save'
            )));
        } else {
            $this->redirect(array_merge($params, array(
                'error' => 'config_save'
            )));
        }
    }
    
    /**
     * Send support request
     */
    function support_request()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $url = W3_Request::get_string('url');
        $name = W3_Request::get_string('name');
        $email = W3_Request::get_string('email');
        $request_type = W3_Request::get_string('request_type');
        $description = W3_Request::get_string('description');
        $templates = W3_Request::get_array('templates');
        $wp_login = W3_Request::get_string('wp_login');
        $wp_password = W3_Request::get_string('wp_password');
        $ftp_host = W3_Request::get_string('ftp_host');
        $ftp_login = W3_Request::get_string('ftp_login');
        $ftp_password = W3_Request::get_string('ftp_password');
        
        $params = array(
            'url' => $url, 
            'name' => $name, 
            'email' => $email, 
            'request_type' => $request_type, 
            'description' => $description, 
            'wp_login' => $wp_login, 
            'wp_password' => $wp_password, 
            'ftp_host' => $ftp_host, 
            'ftp_login' => $ftp_login, 
            'ftp_password' => $ftp_password
        );
        
        foreach ($templates as $template_index => $template) {
            $template_key = sprintf('templates[%d]', $template_index);
            $params[$template_key] = $template;
        }
        
        if ($url == '') {
            $this->redirect(array_merge($params, array(
                'error' => 'support_request_url'
            )));
        }
        
        if ($name == '') {
            $this->redirect(array_merge($params, array(
                'error' => 'support_request_name'
            )));
        }
        
        if (!preg_match('~^[a-z0-9_\-\.]+@[a-z0-9-\.]+\.[a-z]{2,5}$~', $email)) {
            $this->redirect(array_merge($params, array(
                'error' => 'support_request_email'
            )));
        }
        
        if ($request_type == '') {
            $this->redirect(array_merge($params, array(
                'error' => 'support_request_type'
            )));
        }
        
        if ($description == '') {
            $this->redirect(array_merge($params, array(
                'error' => 'support_request_description'
            )));
        }
        
        if ($wp_login != '' || $wp_password != '') {
            if ($wp_login == '') {
                $this->redirect(array_merge($params, array(
                    'error' => 'support_request_wp_login'
                )));
            }
            
            if ($wp_password == '') {
                $this->redirect(array_merge($params, array(
                    'error' => 'support_request_wp_password'
                )));
            }
        }
        
        if ($ftp_host != '' || $ftp_login != '' || $ftp_password != '') {
            if ($ftp_host == '') {
                $this->redirect(array_merge($params, array(
                    'error' => 'support_request_ftp_host'
                )));
            }
            
            if ($ftp_login == '') {
                $this->redirect(array_merge($params, array(
                    'error' => 'support_request_ftp_login'
                )));
            }
            
            if ($ftp_password == '') {
                $this->redirect(array_merge($params, array(
                    'error' => 'support_request_ftp_password'
                )));
            }
        }
        
        /**
         * Add attachments
         */
        $attachments = array(
            W3TC_CONFIG_PATH
        );
        
        /**
         * Attach server info
         */
        $server_info = print_r($this->get_server_info(), true);
        $server_info = str_replace("\n", "\r\n", $server_info);
        
        $server_info_path = W3TC_TMP_DIR . '/server_info.txt';
        
        if (@file_put_contents($server_info_path, $server_info)) {
            $attachments[] = $server_info_path;
        }
        
        /**
         * Attach phpinfo
         */
        ob_start();
        phpinfo();
        $php_info = ob_get_contents();
        ob_end_clean();
        
        $php_info_path = W3TC_TMP_DIR . '/php_info.html';
        
        if (@file_put_contents($php_info_path, $php_info)) {
            $attachments[] = $php_info_path;
        }
        
        /**
         * Attach minify log
         */
        if (file_exists(W3TC_MINIFY_LOG_FILE)) {
            $attachments[] = W3TC_MINIFY_LOG_FILE;
        }
        
        /**
         * Attach templates
         */
        foreach ($templates as $template) {
            if (!empty($template)) {
                $attachments[] = $template;
            }
        }
        
        /**
         * Attach other files
         */
        if (!empty($_FILES['files'])) {
            $files = (array) $_FILES['files'];
            for ($i = 0, $l = count($files); $i < $l; $i++) {
                if (isset($files['tmp_name'][$i]) && isset($files['name'][$i]) && isset($files['error'][$i]) && $files['error'][$i] == UPLOAD_ERR_OK) {
                    $path = W3TC_TMP_DIR . '/' . $files['name'][$i];
                    if (@move_uploaded_file($files['tmp_name'][$i], $path)) {
                        $attachments[] = $path;
                    }
                }
            }
        }
        
        $data = array();
        
        if (!empty($wp_login) && !empty($wp_password)) {
            $data['WP Admin login'] = $wp_login;
            $data['WP Admin password'] = $wp_password;
        }
        
        if (!empty($ftp_host) && !empty($ftp_login) && !empty($ftp_password)) {
            $data['SSH / FTP host'] = $ftp_host;
            $data['SSH / FTP login'] = $ftp_login;
            $data['SSH / FTP password'] = $ftp_password;
        }
        
        /**
         * Store request data for future access
         */
        if (count($data)) {
            $hash = md5(microtime());
            $request_data = get_option('w3tc_request_data', array());
            $request_data[$hash] = $data;
            
            update_option('w3tc_request_data', $request_data);
            
            $request_data_url = sprintf('%sw3tc_request_data/%s', w3_get_site_url(), $hash);
        } else {
            $request_data_url = null;
        }
        
        /**
         * Get body contents
         */
        ob_start();
        include W3TC_DIR . '/inc/options/support_email.phtml';
        $body = ob_get_contents();
        ob_end_clean();
        
        /**
         * Send email
         */
        $subject = sprintf('W3TC Support Request: %s', $request_type);
        
        $headers = array(
            sprintf('From: "%s" <%s>', addslashes($name), $email), 
            sprintf('Reply-To: "%s" <%s>', addslashes($name), $email), 
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $this->_phpmailer_sender = $email;
        
        add_action('phpmailer_init', array(
            &$this, 
            'phpmailer_init'
        ));
        
        set_time_limit(600);
        
        $result = @wp_mail(W3TC_EMAIL, $subject, $body, implode("\n", $headers), $attachments);
        
        /**
         * Remove temporary files
         */
        foreach ($attachments as $attachment) {
            if (strstr($attachment, W3TC_TMP_DIR) !== false) {
                @unlink($attachment);
            }
        }
        
        if ($result) {
            $this->redirect(array(
                'note' => 'support_request'
            ));
        } else {
            $this->redirect(array_merge($params, array(
                'error' => 'support_request'
            )));
        }
    }
    
    /**
     * PHPMailer init function
     * 
     * @param PHPMailer $phpmailer
     * @return void
     */
    function phpmailer_init(&$phpmailer)
    {
        $phpmailer->Sender = $this->_phpmailer_sender;
    }
    
    /**
     * Returns button html
     *
     * @param string $text
     * @param string $onclick
     * @return string
     */
    function button($text, $onclick = '')
    {
        return sprintf('<input type="button" class="button" value="%s" onclick="%s" />', htmlspecialchars($text), htmlspecialchars($onclick));
    }
    
    /**
     * Returns button link html
     *
     * @param string $text
     * @param string $url
     * @return string
     */
    function button_link($text, $url)
    {
        $onclick = sprintf('document.location.href = \'%s\';', addslashes($url));
        
        return $this->button($text, $onclick);
    }
    
    /**
     * Returns hide note button html
     *
     * @param string $text
     * @param string $note
     * @param string $redirect
     * @return string
     */
    function button_hide_note($text, $note, $redirect = '')
    {
        $url = sprintf('options-general.php?page=%s&tab=%s&hide_note=%s', W3TC_FILE, $this->_tab, $note);
        
        if ($redirect != '') {
            $url .= '&redirect=' . urlencode($redirect);
        }
        
        return $this->button_link($text, $url);
    }
    
    /**
     * Returns popup button html
     *
     * @param string $text
     * @param string $w3tc_action
     * @param string $params
     * @param integer $width
     * @param integer $height
     * @return string
     */
    function button_popup($text, $w3tc_action, $params = '', $width = 800, $height = 600)
    {
        $onclick = sprintf('window.open(\'options-general.php?page=%s&w3tc_action=%s%s\', \'%s\', \'width=%d,height=%d,status=no,toolbar=no,menubar=no,scrollbars=yes\');', W3TC_FILE, $w3tc_action, ($params != '' ? '&' . $params : ''), $w3tc_action, $width, $height);
        
        return $this->button($text, $onclick);
    }
    
    /**
     * CDN queue action
     */
    function cdn_queue()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $cdn_queue_action = W3_Request::get_string('cdn_queue_action');
        $cdn_queue_tab = W3_Request::get_string('cdn_queue_tab');
        
        $notes = array();
        
        switch ($cdn_queue_tab) {
            case 'upload':
            case 'delete':
                break;
            
            default:
                $cdn_queue_tab = 'upload';
        }
        
        switch ($cdn_queue_action) {
            case 'delete':
                $cdn_queue_id = W3_Request::get_integer('cdn_queue_id');
                if (!empty($cdn_queue_id)) {
                    $w3_plugin_cdn->queue_delete($cdn_queue_id);
                    $notes[] = 'File successfully deleted from the queue.';
                }
                break;
            
            case 'empty':
                $cdn_queue_type = W3_Request::get_integer('cdn_queue_type');
                if (!empty($cdn_queue_type)) {
                    $w3_plugin_cdn->queue_empty($cdn_queue_type);
                    $notes[] = 'Queue successfully emptied.';
                }
                break;
        }
        
        $queue = $w3_plugin_cdn->queue_get();
        $title = 'Unsuccessful file transfer queue.';
        
        include W3TC_DIR . '/inc/popup/cdn_queue.phtml';
    }
    
    /**
     * CDN export library action
     */
    function cdn_export_library()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $total = $w3_plugin_cdn->get_attachments_count();
        $title = 'Media Library export';
        
        include W3TC_DIR . '/inc/popup/cdn_export_library.phtml';
    }
    
    /**
     * CDN export library process
     */
    function cdn_export_library_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        
        $count = null;
        $total = null;
        $results = array();
        
        @$w3_plugin_cdn->export_library($limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{local_path: '%s', remote_path: '%s', result: %d, error: '%s'}", addslashes($result['local_path']), addslashes($result['remote_path']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN import library action
     */
    function cdn_import_library()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $cdn = & $w3_plugin_cdn->get_cdn();
        
        $total = $w3_plugin_cdn->get_import_posts_count();
        $cdn_host = $cdn->get_domain();
        
        $title = 'Media Library import';
        
        include W3TC_DIR . '/inc/popup/cdn_import_library.phtml';
    }
    
    /**
     * CDN import library process
     */
    function cdn_import_library_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        
        $count = null;
        $total = null;
        $results = array();
        
        @$w3_plugin_cdn->import_library($limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{src: '%s', dst: '%s', result: %d, error: '%s'}", addslashes($result['src']), addslashes($result['dst']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN rename domain action
     */
    function cdn_rename_domain()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $total = $w3_plugin_cdn->get_rename_posts_count();
        
        $title = 'Modify attachment URLs';
        
        include W3TC_DIR . '/inc/popup/cdn_rename_domain.phtml';
    }
    
    /**
     * CDN rename domain process
     */
    function cdn_rename_domain_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        $names = W3_Request::get_array('names');
        
        $count = null;
        $total = null;
        $results = array();
        
        @$w3_plugin_cdn->rename_domain($names, $limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{old: '%s', new: '%s', result: %d, error: '%s'}", addslashes($result['old']), addslashes($result['new']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN export action
     */
    function cdn_export()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $cdn_export_type = W3_Request::get_string('cdn_export_type', 'custom');
        
        switch ($cdn_export_type) {
            case 'includes':
                $title = 'Includes files export';
                $files = $w3_plugin_cdn->get_files_includes();
                break;
            
            case 'theme':
                $title = 'Theme files export';
                $files = $w3_plugin_cdn->get_files_theme();
                break;
            
            case 'minify':
                $title = 'Minify files export';
                $files = $w3_plugin_cdn->get_files_minify();
                break;
            
            default:
            case 'custom':
                $title = 'Custom files export';
                $files = $w3_plugin_cdn->get_files_custom();
                break;
        }
        
        include W3TC_DIR . '/inc/popup/cdn_export_file.phtml';
    }
    
    /**
     * CDN export process
     */
    function cdn_export_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $files = W3_Request::get_array('files');
        $upload = array();
        $results = array();
        
        foreach ($files as $file) {
            $upload[$file] = $file;
        }
        
        $w3_plugin_cdn->upload($upload, false, $results);
        
        echo "{results: [\r\n";
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{local_path: '%s', remote_path: '%s', result: %d, error: '%s'}", addslashes($result['local_path']), addslashes($result['remote_path']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * Uploads minify files to CDN
     */
    function cdn_upload_minify()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $files = $w3_plugin_cdn->get_files_minify();
        $upload = array();
        $results = array();
        
        foreach ($files as $file) {
            $upload[$file] = $file;
        }
        
        return $w3_plugin_cdn->upload($upload, false, $results);
    }
    
    /**
     * CDN Test FTP
     */
    function cdn_test_ftp()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Cdn.php';
        
        $host = W3_Request::get_string('host');
        $user = W3_Request::get_string('user');
        $pass = W3_Request::get_string('pass');
        $path = W3_Request::get_string('path');
        $pasv = W3_Request::get_boolean('pasv');
        
        $w3_cdn_ftp = & W3_Cdn::instance('ftp', array(
            'host' => $host, 
            'user' => $user, 
            'pass' => $pass, 
            'path' => $path, 
            'pasv' => $pasv
        ));
        
        $error = null;
        
        if ($w3_cdn_ftp->test($error)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = sprintf('Test failed. Error: %s', $error);
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * CDN Test S3
     */
    function cdn_test_s3()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Cdn.php';
        
        $key = W3_Request::get_string('key');
        $secret = W3_Request::get_string('secret');
        $bucket = W3_Request::get_string('bucket');
        
        $w3_cdn_s3 = & W3_Cdn::instance('s3', array(
            'key' => $key, 
            'secret' => $secret, 
            'bucket' => $bucket
        ));
        
        $error = null;
        
        if ($w3_cdn_s3->test($error)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = sprintf('Test failed. Error: %s', $error);
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * CDN Test CloudFront
     */
    function cdn_test_cf()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Cdn.php';
        
        $key = W3_Request::get_string('key');
        $secret = W3_Request::get_string('secret');
        $bucket = W3_Request::get_string('bucket');
        $id = W3_Request::get_string('id');
        $cname = W3_Request::get_string('cname');
        
        $w3_cdn_s3 = & W3_Cdn::instance('cf', array(
            'key' => $key, 
            'secret' => $secret, 
            'bucket' => $bucket, 
            'id' => $id, 
            'cname' => $cname
        ));
        
        $error = null;
        
        if ($w3_cdn_s3->test($error)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = sprintf('Test failed. Error: %s', $error);
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * Create bucket action
     */
    function cdn_create_bucket()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Cdn.php';
        
        $type = W3_Request::get_string('type');
        $key = W3_Request::get_string('key');
        $secret = W3_Request::get_string('secret');
        $bucket = W3_Request::get_string('bucket');
        
        switch ($type) {
            case 's3':
            case 'cf':
                $result = true;
                break;
            
            default:
                $result = false;
                $error = 'Incorrect type.';
                break;
        }
        
        if ($result) {
            $w3_cdn_s3 = & W3_Cdn::instance($type, array(
                'key' => $key, 
                'secret' => $secret, 
                'bucket' => $bucket
            ));
            
            $error = null;
            
            if ($w3_cdn_s3->create_bucket($error)) {
                $result = true;
                $error = 'Bucket has been successfully created.';
            } else {
                $result = false;
                $error = sprintf('Error: %s', $error);
            }
            
            echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
        }
    }
    
    /**
     * Check if memcache is available
     *
     * @param array $servers
     * @return boolean
     */
    function is_memcache_available($servers)
    {
        static $results = array();
        
        $key = md5(serialize($servers));
        
        if (!isset($results[$key])) {
            require_once W3TC_LIB_W3_DIR . '/Cache/Memcached.php';
            
            $memcached = & new W3_Cache_Memcached(array(
                'servers' => $servers, 
                'persistant' => false
            ));
            
            $test_string = sprintf('test_' . md5(time()));
            $memcached->set($test_string, $test_string, 60);
            
            $results[$key] = ($memcached->get($test_string) == $test_string);
        }
        
        return $results[$key];
    }
    
    /**
     * Test memcached
     */
    function test_memcached()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $servers = W3_Request::get_array('servers');
        
        if ($this->is_memcache_available($servers)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = 'Test failed';
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * Insert plugin link into Blogroll
     */
    function link_insert()
    {
        $support = $this->_config->get_string('common.support');
        $matches = null;
        
        if ($support != '' && preg_match('~^link_category_(\d+)$~', $support, $matches)) {
            require_once ABSPATH . 'wp-admin/includes/bookmark.php';
            
            wp_insert_link(array(
                'link_url' => W3TC_LINK_URL, 
                'link_name' => W3TC_LINK_NAME, 
                'link_category' => array(
                    (int) $matches[1]
                )
            ));
        }
    }
    
    /**
     * Deletes plugin link from Blogroll
     */
    function link_delete()
    {
        $bookmarks = get_bookmarks();
        $link_id = 0;
        
        foreach ($bookmarks as $bookmark) {
            if ($bookmark->link_url == W3TC_LINK_URL) {
                $link_id = $bookmark->link_id;
                break;
            }
        }
        
        if ($link_id) {
            require_once ABSPATH . 'wp-admin/includes/bookmark.php';
            wp_delete_link($link_id);
        }
    }
    
    /**
     * Updates link
     */
    function link_update()
    {
        $this->link_delete();
        $this->link_insert();
    }
    
    /**
     * Flush specified cache
     *
     * @param string $type
     */
    function flush($type)
    {
        if ($this->_config->get_string('pgcache.engine') == $type && $this->_config->get_boolean('pgcache.enabled')) {
            $this->_config->set('notes.need_empty_pgcache', false);
            $this->_config->set('notes.plugins_updated', false);
            
            if (!$this->_config->save()) {
                $this->redirect(array(
                    'error' => 'config_save'
                ));
            }
            
            $this->flush_pgcache();
        }
        
        if ($this->_config->get_string('dbcache.engine') == $type && $this->_config->get_boolean('dbcache.enabled')) {
            $this->flush_dbcache();
        }
        
        if ($this->_config->get_string('minify.engine') == $type && $this->_config->get_boolean('minify.enabled')) {
            $this->_config->set('notes.need_empty_minify', false);
            
            if (!$this->_config->save()) {
                $this->redirect(array(
                    'error' => 'config_save'
                ));
            }
            
            $this->flush_minify();
        }
    }
    
    /**
     * Flush memcached cache
     *
     * @return void
     */
    function flush_memcached()
    {
        $this->flush('memcached');
    }
    
    /**
     * Flush APC cache
     * @return void
     */
    function flush_opcode()
    {
        $this->flush('apc');
        $this->flush('eaccelerator');
        $this->flush('xcache');
    }
    
    /**
     * Flush file cache
     *
     * @return void
     */
    function flush_file()
    {
        $this->flush('file');
        $this->flush('file_pgcache');
    }
    
    /**
     * Flush page cache
     */
    function flush_pgcache()
    {
        require_once W3TC_DIR . '/lib/W3/PgCache.php';
        $w3_pgcache = & W3_PgCache::instance();
        $w3_pgcache->flush();
    }
    
    /**
     * Flush page cache
     */
    function flush_dbcache()
    {
        require_once W3TC_DIR . '/lib/W3/Db.php';
        $w3_db = & W3_Db::instance();
        $w3_db->flush_cache();
    }
    
    /**
     * Flush minify cache
     */
    function flush_minify()
    {
        if (W3TC_PHP5) {
            require_once W3TC_DIR . '/lib/W3/Minify.php';
            $w3_minify = & W3_Minify::instance();
            $w3_minify->flush();
        }
    }
    
    /**
     * Checks if advanced-cache.php exists
     *
     * @return boolean
     */
    function check_advanced_cache()
    {
        return (file_exists(WP_CONTENT_DIR . '/advanced-cache.php') && ($script_data = @file_get_contents(WP_CONTENT_DIR . '/advanced-cache.php')) && strstr($script_data, 'W3_PgCache') !== false);
    }
    
    /**
     * Checks if db.php exists
     *
     * @return boolean
     */
    function check_db()
    {
        return (file_exists(WP_CONTENT_DIR . '/db.php') && ($script_data = @file_get_contents(WP_CONTENT_DIR . '/db.php')) && strstr($script_data, 'W3_Db') !== false);
    }
    
    /**
     * Output buffering callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        global $wpdb;
        
        if ($buffer != '' && w3_is_xml($buffer)) {
            $date = date('Y-m-d H:i:s');
            $host = (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
            
            if ($this->is_supported()) {
                $buffer .= sprintf("\r\n<!-- Served from: %s @ %s by W3 Total Cache -->", $host, $date);
            } else {
                $buffer .= "\r\n<!-- Performance optimized by W3 Total Cache. Learn more: http://www.w3-edge.com/wordpress-plugins/\r\n\r\n";
                
                if ($this->_config->get_boolean('minify.enabled')) {
                    require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
                    $w3_plugin_minify = & W3_Plugin_Minify::instance();
                    
                    $buffer .= sprintf("Minified using %s%s\r\n", w3_get_engine_name($this->_config->get_string('minify.engine')), ($w3_plugin_minify->minify_reject_reason != '' ? sprintf(' (%s)', $w3_plugin_minify->minify_reject_reason) : ''));
                }
                
                if ($this->_config->get_boolean('pgcache.enabled')) {
                    require_once W3TC_LIB_W3_DIR . '/PgCache.php';
                    $w3_pgcache = & W3_PgCache::instance();
                    
                    $buffer .= sprintf("Page Caching using %s%s\r\n", w3_get_engine_name($this->_config->get_string('pgcache.engine')), ($w3_pgcache->cache_reject_reason != '' ? sprintf(' (%s)', $w3_pgcache->cache_reject_reason) : ''));
                }
                
                if ($this->_config->get_boolean('dbcache.enabled') && is_a($wpdb, 'W3_Db')) {
                    $append = (is_user_logged_in() ? ' (user is logged in)' : '');
                    
                    if ($wpdb->query_hits) {
                        $buffer .= sprintf("Database Caching %d/%d queries in %.3f seconds using %s%s\r\n", $wpdb->query_hits, $wpdb->query_total, $wpdb->time_total, w3_get_engine_name($this->_config->get_string('dbcache.engine')), $append);
                    } else {
                        $buffer .= sprintf("Database Caching using %s%s\r\n", w3_get_engine_name($this->_config->get_string('dbcache.engine')), $append);
                    }
                }
                
                if ($this->_config->get_boolean('cdn.enabled')) {
                    require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
                    
                    $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
                    $cdn = & $w3_plugin_cdn->get_cdn();
                    $via = $cdn->get_via();
                    
                    $buffer .= sprintf("Content Delivery Network via %s%s\r\n", ($via ? $via : 'N/A'), ($w3_plugin_cdn->cdn_reject_reason != '' ? sprintf(' (%s)', $w3_plugin_cdn->cdn_reject_reason) : ''));
                }
                
                $buffer .= sprintf("\r\nServed from: %s @ %s -->", $host, $date);
            }
        }
        
        return $buffer;
    }
    
    /**
     * Check if we can do modify contents
     * @return boolean
     */
    function can_modify_contents()
    {
        /**
         * Skip if admin
         */
        if (defined('WP_ADMIN')) {
            return false;
        }
        
        /**
         * Skip if doint AJAX
         */
        if (defined('DOING_AJAX')) {
            return false;
        }
        
        /**
         * Skip if doing cron
         */
        if (defined('DOING_CRON')) {
            return false;
        }
        
        /**
         * Skip if APP request
         */
        if (defined('APP_REQUEST')) {
            return false;
        }
        
        /**
         * Skip if XMLRPC request
         */
        if (defined('XMLRPC_REQUEST')) {
            return false;
        }
        
        /**
         * Check request URI
         */
        if (!$this->check_request_uri()) {
            return false;
        }
        
        /**
         * Skip if debug mode is enabled
         */
        if ($this->_config->get_boolean('pgcache.debug') || $this->_config->get_boolean('dbcache.debug') || $this->_config->get_boolean('minify.debug') || $this->_config->get_boolean('cdn.debug')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function check_request_uri()
    {
        $reject_uri = array(
            'wp-login', 
            'wp-register'
        );
        
        foreach ($reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Returns server info
     */
    function get_server_info()
    {
        global $wp_version, $wp_db_version, $wpdb;
        
        $wordpress_plugins = get_plugins();
        $wordpress_plugins_active = array();
        
        foreach ($wordpress_plugins as $wordpress_plugin_file => $wordpress_plugin) {
            if (is_plugin_active($wordpress_plugin_file)) {
                $wordpress_plugins_active[$wordpress_plugin_file] = $wordpress_plugin;
            }
        }
        
        $w3tc_config = (array) @include W3TC_CONFIG_PATH;
        $mysql_version = (array) $wpdb->get_var('SELECT VERSION()');
        $mysql_variables_result = (array) $wpdb->get_results('SHOW VARIABLES', ARRAY_N);
        $mysql_variables = array();
        
        foreach ($mysql_variables_result as $mysql_variables_row) {
            $mysql_variables[$mysql_variables_row[0]] = $mysql_variables_row[1];
        }
        
        return array(
            'wp' => array(
                'version' => $wp_version, 
                'db_version' => $wp_db_version, 
                'w3tc_version' => W3TC_VERSION, 
                'url' => w3_get_domain_url(), 
                'path' => ABSPATH, 
                'email' => get_option('admin_email'), 
                'upload_info' => (array) w3_upload_info(), 
                'theme' => get_theme(get_current_theme()), 
                'plugins' => $wordpress_plugins_active, 
                'wp_cache' => (defined('WP_CACHE') ? 'true' : 'false')
            ), 
            'mysql' => array(
                'version' => $mysql_version, 
                'variables' => $mysql_variables
            )
        );
    }
    
    /**
     * Support Us action
     */
    function support_us()
    {
        $supports = $this->get_supports();
        
        include W3TC_DIR . '/inc/lightbox/support_us.phtml';
    }
    
    /**
     * Tweet action
     */
    function tweet()
    {
        include W3TC_DIR . '/inc/lightbox/tweet.phtml';
    }
    
    /**
     * Update twitter status
     */
    function twitter_status_update()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $username = W3_Request::get_string('username');
        $password = W3_Request::get_string('password');
        
        $error = 'OK';
        
        if (w3_twitter_status_update($username, $password, W3TC_TWITTER_STATUS, $error)) {
            $this->_config->set('common.tweeted', time());
            
            if ($this->_config->save()) {
                $result = true;
            } else {
                $error = 'Unable to save config.';
                $result = false;
            }
        } else {
            $result = false;
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * Returns list of support types
     * @return array
     */
    function get_supports()
    {
        $supports = array(
            'footer' => 'page footer'
        );
        
        $link_categories = get_terms('link_category', array(
            'hide_empty' => 0
        ));
        
        foreach ($link_categories as $link_category) {
            $supports['link_category_' . $link_category->term_id] = strtolower($link_category->name);
        }
        
        return $supports;
    }
    
    /**
     * Returns true if is supported
     * @return boolean
     */
    function is_supported()
    {
        return ($this->_config->get_string('common.support') != '' || $this->_config->get_string('common.tweeted'));
    }
    
    /**
     * Returns minify groups
     * @return array
     */
    function minify_get_groups()
    {
        $groups = array(
            'default' => 'Default'
        );
        
        $current_theme = get_current_theme();
        
        if ($current_theme) {
            $theme = get_theme($current_theme);
            if ($theme && isset($theme['Template Files'])) {
                foreach ((array) $theme['Template Files'] as $template_file) {
                    $group = basename($template_file, '.php');
                    $groups[$group] = ucfirst($group);
                }
            }
        }
        
        return $groups;
    }
    
    /**
     * Redirect function
     * 
     * @param boolean $check_referer
     */
    function redirect($params = array(), $check_referer = false)
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $url = W3_Request::get_string('redirect');
        
        if ($url == '') {
            if ($check_referer && !empty($_SERVER['HTTP_REFERER'])) {
                $url = $_SERVER['HTTP_REFERER'];
            } else {
                $url = 'options-general.php';
                $params = array_merge(array(
                    'page' => W3TC_FILE, 
                    'tab' => $this->_tab
                ), $params);
            }
        }
        
        w3_redirect($url, $params);
    }
}
