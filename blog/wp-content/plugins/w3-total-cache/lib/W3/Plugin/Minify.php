<?php

/**
 * W3 Minify plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_Minify
 */
class W3_Plugin_Minify extends W3_Plugin
{
    /**
     * Minify reject reason
     * 
     * @var string
     */
    var $minify_reject_reason = '';
    
    /**
     * Array of printed styles
     * @var array
     */
    var $printed_styles = array();
    
    /**
     * Array of printed scripts
     * @var array
     */
    var $printed_scripts = array();
    
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
        
        if ($this->_config->get_boolean('minify.enabled') && $this->_config->get_string('minify.engine') == 'file') {
            add_action('w3_minify_cleanup', array(
                &$this, 
                'cleanup'
            ));
        }
        
        if ($this->can_minify()) {
            ob_start(array(
                &$this, 
                'ob_callback'
            ));
        }
    }
    
    /**
     * Returns instance
     *
     * @return W3_Plugin_Minify
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
        if (!is_dir(W3TC_CONTENT_MINIFY_DIR)) {
            if (@mkdir(W3TC_CONTENT_MINIFY_DIR, 0755)) {
                @chmod(W3TC_CONTENT_MINIFY_DIR, 0755);
            } else {
                w3_writable_error(W3TC_CONTENT_MINIFY_DIR);
            }
        }
        
        $file_index = W3TC_CONTENT_MINIFY_DIR . '/index.php';
        
        if (@copy(W3TC_INSTALL_MINIFY_DIR . '/index.php', $file_index)) {
            @chmod($file_index, 0644);
        } else {
            w3_writable_error($file_index);
        }
        
        if ($this->_config->get_boolean('minify.rewrite') && !$this->write_rules()) {
            w3_writable_error(W3TC_CONTENT_MINIFY_DIR . '/.htaccess');
        }
        
        $this->schedule();
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        $this->unschedule();
        
        @unlink(W3TC_CONTENT_MINIFY_DIR . '/index.php');
        
        $this->remove_rules();
    }
    
    /**
     * Schedules events
     */
    function schedule()
    {
        if ($this->_config->get_boolean('minify.enabled') && $this->_config->get_string('minify.engine') == 'file') {
            if (!wp_next_scheduled('w3_minify_cleanup')) {
                wp_schedule_event(time(), 'w3_minify_cleanup', 'w3_minify_cleanup');
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
        if (wp_next_scheduled('w3_minify_cleanup')) {
            wp_clear_scheduled_hook('w3_minify_cleanup');
        }
    }
    
    /**
     * Does disk cache cleanup
     *
     * @return void
     */
    function cleanup()
    {
        require_once W3TC_LIB_W3_DIR . '/Cache/File/Minify/Manager.php';
        
        $w3_cache_file_minify_manager = & new W3_Cache_File_Minify_Manager(array(
            'cache_dir' => W3TC_CACHE_FILE_MINIFY_DIR, 
            'expire' => $this->_config->get_integer('minify.lifetime')
        ));
        
        $w3_cache_file_minify_manager->clean();
    }
    
    /**
     * Cron schedules filter
     *
     * @paran array $schedules
     * @return array
     */
    function cron_schedules($schedules)
    {
        $gc = $this->_config->get_integer('minify.file.gc');
        
        return array_merge($schedules, array(
            'w3_minify_cleanup' => array(
                'interval' => $gc, 
                'display' => sprintf('Every %d seconds', $gc)
            )
        ));
    }
    
    /**
     * OB callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        if ($buffer != '' && w3_is_xml($buffer) && $this->can_minify2()) {
            $head_prepend = '';
            $body_append = '';
            
            if ($this->_config->get_boolean('minify.css.enable') && !in_array('include', $this->printed_styles)) {
                $head_prepend .= $this->get_styles('include');
            }
            
            if ($this->_config->get_boolean('minify.js.enable')) {
                if (!in_array('include', $this->printed_scripts)) {
                    $head_prepend .= $this->get_scripts('include');
                }
                
                if (!in_array('include-nb', $this->printed_scripts)) {
                    $head_prepend .= $this->get_scripts('include-nb');
                }
                
                if (!in_array('include-footer', $this->printed_scripts)) {
                    $body_append .= $this->get_scripts('include-footer');
                }
                
                if (!in_array('include-footer-nb', $this->printed_scripts)) {
                    $body_append .= $this->get_scripts('include-footer-nb');
                }
            }
            
            if ($head_prepend != '') {
                $buffer = preg_replace('~<head(\s+[^<>]+)*>~Ui', '\\0' . $head_prepend, $buffer, 1);
            }
            
            if ($body_append != '') {
                $buffer = preg_replace('~<\\/body>~', $body_append . '\\0', $buffer, 1);
            }
            
            $buffer = $this->clean($buffer);
            
            if ($this->_config->get_boolean('minify.debug')) {
                $buffer .= "\r\n\r\n" . $this->get_debug_info();
            }
        }
        
        return $buffer;
    }
    
    /**
     * Cleans content
     *
     * @param string $content
     * @return string
     */
    function clean($content)
    {
        if (!is_feed()) {
            if ($this->_config->get_boolean('minify.css.enable')) {
                $content = $this->clean_styles($content);
                $content = preg_replace('~<style[^<>]*>\s*</style>~', '', $content);
            }
            
            if ($this->_config->get_boolean('minify.js.enable')) {
                $content = $this->clean_scripts($content);
            }
        }
        
        if ($this->_config->get_boolean('minify.html.enable') && !($this->_config->get_boolean('minify.html.reject.admin') && current_user_can('manage_options'))) {
            $content = $this->minify_html($content);
        }
        
        return $content;
    }
    
    /**
     * Cleans styles
     *
     * @param string $content
     * @return string
     */
    function clean_styles($content)
    {
        $regexps = array();
        
        $groups = $this->_config->get_array('minify.css.groups');
        $domain_url_regexp = w3_get_domain_url_regexp();
        
        foreach ($groups as $group => $locations) {
            foreach ((array) $locations as $location => $config) {
                if (!empty($config['files'])) {
                    foreach ((array) $config['files'] as $file) {
                        if (w3_is_url($file) && !preg_match('~' . $domain_url_regexp . '~i', $file)) {
                            // external CSS files
                            $regexps[] = w3_preg_quote($file);
                        } else {
                            // local CSS files
                            $file = ltrim(preg_replace('~' . $domain_url_regexp . '~i', '', $file), '/\\');
                            $regexps[] = '(' . $domain_url_regexp . ')?/?' . w3_preg_quote($file);
                        }
                    }
                }
            }
        }
        
        foreach ($regexps as $regexp) {
            $content = preg_replace('~<link\s+[^<>]*href=["\']?' . $regexp . '["\']?[^<>]*/?>~is', '', $content);
            $content = preg_replace('~@import\s+(url\s*)?\(?["\']?\s*' . $regexp . '\s*["\']?\)?[^;]*;?~is', '', $content);
        }
        
        return $content;
    }
    
    /**
     * Cleans scripts
     *
     * @param string $content
     * @return string
     */
    function clean_scripts($content)
    {
        $regexps = array();
        
        $groups = $this->_config->get_array('minify.js.groups');
        $domain_url_regexp = w3_get_domain_url_regexp();
        
        foreach ($groups as $group => $locations) {
            foreach ((array) $locations as $location => $config) {
                if (!empty($config['files'])) {
                    foreach ((array) $config['files'] as $file) {
                        if (w3_is_url($file) && !preg_match('~' . $domain_url_regexp . '~i', $file)) {
                            // external JS files
                            $regexps[] = w3_preg_quote($file);
                        } else {
                            // local JS files
                            $file = ltrim(preg_replace('~' . $domain_url_regexp . '~i', '', $file), '/\\');
                            $regexps[] = '(' . $domain_url_regexp . ')?/?' . w3_preg_quote($file);
                        }
                    }
                }
            }
        }
        
        foreach ($regexps as $regexp) {
            $content = preg_replace('~<script\s+[^<>]*src=["\']?' . $regexp . '["\']?[^<>]*>\s*</script>~is', '', $content);
        }
        
        return $content;
    }
    
    /**
     * Minifies HTML
     *
     * @param string $content
     * @return string
     */
    function minify_html($content)
    {
        require_once W3TC_LIB_MINIFY_DIR . '/Minify/HTML.php';
        require_once W3TC_LIB_MINIFY_DIR . '/Minify/CSS.php';
        require_once W3TC_LIB_MINIFY_DIR . '/JSMin.php';
        
        $options = array(
            'xhtml' => true, 
            'stripCrlf' => $this->_config->get_boolean('minify.html.strip.crlf'), 
            'cssStripCrlf' => $this->_config->get_boolean('minify.css.strip.crlf'), 
            'cssStripComments' => $this->_config->get_boolean('minify.css.strip.comments'), 
            'jsStripCrlf' => $this->_config->get_boolean('minify.js.strip.crlf'), 
            'jsStripComments' => $this->_config->get_boolean('minify.js.strip.comments')
        );
        
        if ($this->_config->get_boolean('minify.html.inline.css')) {
            $options['cssMinifier'] = array(
                'Minify_CSS', 
                'minify'
            );
        }
        
        if ($this->_config->get_boolean('minify.html.inline.js')) {
            $options['jsMinifier'] = array(
                'JSMin', 
                'minify'
            );
        }
        
        try {
            $content = Minify_HTML::minify($content, $options);
        } catch (Exception $exception) {
            return sprintf('<strong>W3 Total Cache Error:</strong> Minify error: %s', $exception->getMessage());
        }
        
        return $content;
    }
    
    /**
     * Returns current group
     *
     * @return string
     */
    function get_group()
    {
        static $group = null;
        
        if ($group === null) {
            switch (true) {
                case (is_404() && ($template = get_404_template())):
                case (is_search() && ($template = get_search_template())):
                case (is_tax() && ($template = get_taxonomy_template())):
                case (is_home() && ($template = get_home_template())):
                case (is_attachment() && ($template = get_attachment_template())):
                case (is_single() && ($template = get_single_template())):
                case (is_page() && ($template = get_page_template())):
                case (is_category() && ($template = get_category_template())):
                case (is_tag() && ($template = get_tag_template())):
                case (is_author() && ($template = get_author_template())):
                case (is_date() && ($template = get_date_template())):
                case (is_archive() && ($template = get_archive_template())):
                case (is_comments_popup() && ($template = get_comments_popup_template())):
                case (is_paged() && ($template = get_paged_template())):
                    $group = basename($template, '.php');
                    break;
                
                default:
                    $group = 'default';
                    break;
            }
        }
        
        return $group;
    }
    
    /**
     * Returns style link
     *
     * @param string $url
     * @param string $import
     */
    function get_style($url, $import = false)
    {
        if ($import) {
            return "<style type=\"text/css\" media=\"all\">@import url(\"" . $url . "\");</style>\r\n";
        } else {
            return "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . str_replace('&', '&amp;', $url) . "\" media=\"all\" />\r\n";
        }
    }
    
    /**
     * Prints script link
     *
     * @param string $url
     * @param boolean $non_blocking
     */
    function get_script($url, $blocking = true)
    {
        static $non_blocking_function = false;
        
        if ($blocking) {
            return '<script type="text/javascript" src="' . str_replace('&', '&amp;', $url) . '"></script>';
        } else {
            $script = '';
            
            if (!$non_blocking_function) {
                $non_blocking_function = true;
                $script = "<script type=\"text/javascript\">function w3tc_load_js(u){var d=document,p=d.getElementsByTagName('HEAD')[0],c=d.createElement('script');c.type='text/javascript';c.src=u;p.appendChild(c);}</script>";
            }
            
            $script .= "<script type=\"text/javascript\">w3tc_load_js('" . $url . "');</script>";
            
            return $script;
        }
    }
    
    /**
     * Returns style link for styles group
     *
     * @param string $location
     * @param string $group
     */
    function get_styles($location, $group = null)
    {
        $styles = '';
        $groups = $this->_config->get_array('minify.css.groups');
        
        if (empty($group)) {
            $group = $this->get_group();
        }
        
        if ($group != 'default' && empty($groups[$group][$location]['files'])) {
            $group = 'default';
        }
        
        if (!empty($groups[$group][$location]['files'])) {
            $styles .= $this->get_style($this->format_url($group, $location, 'css'), isset($groups[$group][$location]['import']) ? (boolean) $groups[$group][$location]['import'] : false);
        }
        
        return $styles;
    }
    
    /**
     * Returns script linkg for scripts group
     *
     * @param string $location
     * @param string $group
     */
    function get_scripts($location, $group = null)
    {
        $scripts = '';
        $groups = $this->_config->get_array('minify.js.groups');
        
        if (empty($group)) {
            $group = $this->get_group();
        }
        
        if ($group != 'default' && empty($groups[$group][$location]['files'])) {
            $group = 'default';
        }
        
        if (!empty($groups[$group][$location]['files'])) {
            $scripts .= $this->get_script($this->format_url($group, $location, 'js'), isset($groups[$group][$location]['blocking']) ? (boolean) $groups[$group][$location]['blocking'] : true);
        }
        
        return $scripts;
    }
    
    /**
     * Returns link for custom script files
     *
     * @param string|array $files
     * @param boolean $blocking
     */
    function get_custom_script($files, $blocking = true)
    {
        return $this->get_script($this->format_custom_url($files), $blocking);
    }
    
    /**
     * Returns link for custom style files
     *
     * @param string|array $files
     * @param boolean $import
     */
    function get_custom_style($files, $import = false)
    {
        return $this->get_style($this->format_custom_url($files), $import);
    }
    
    /**
     * Formats URL
     *
     * @param string $group
     * @param string $location
     * @param string $type
     * @return string
     */
    function format_url($group, $location, $type)
    {
        $site_url_ssl = w3_get_site_url_ssl();
        
        if ($this->_config->get_boolean('minify.rewrite')) {
            return sprintf('%s/%s/%s.%s.%s', $site_url_ssl, W3TC_CONTENT_MINIFY_DIR_NAME, $group, $location, $type);
        }
        
        return sprintf('%s/%s/index.php?gg=%s&g=%s&t=%s', $site_url_ssl, W3TC_CONTENT_MINIFY_DIR_NAME, $group, $location, $type);
    }
    
    /**
     * Formats custom URL
     *
     * @param string|array $files
     * @return string
     */
    function format_custom_url($files)
    {
        if (!is_array($files)) {
            $files = array(
                (string) $files
            );
        }
        
        $base = false;
        foreach ($files as &$file) {
            $current_base = dirname($file);
            if ($base && $base != $current_base) {
                $base = false;
                break;
            } else {
                $file = basename($file);
                $base = $current_base;
            }
        }
        
        $site_url_ssl = w3_get_site_url_ssl();
        $url = sprintf('%s/%s/minify.php?f=%s', $site_url_ssl, W3TC_CONTENT_DIR_NAME, implode(',', $files));
        
        if ($base) {
            $url .= sprintf('&b=%s', $base);
        }
        
        return $url;
    }
    
    /**
     * Returns array of minify URLs
     *
     * @return array
     */
    function get_urls()
    {
        $files = array();
        
        $js_groups = $this->_config->get_array('minify.js.groups');
        $css_groups = $this->_config->get_array('minify.css.groups');
        
        foreach ($js_groups as $js_group => $js_locations) {
            foreach ((array) $js_locations as $js_location => $js_config) {
                if (!empty($js_config['files'])) {
                    $files[] = $this->format_url($js_group, $js_location, 'js');
                }
            }
        }
        
        foreach ($css_groups as $css_group => $css_locations) {
            foreach ((array) $css_locations as $css_location => $css_config) {
                if (!empty($css_config['files'])) {
                    $files[] = $this->format_url($css_group, $css_location, 'css');
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Returns debug info
     */
    function get_debug_info()
    {
        $group = $this->get_group();
        
        $debug_info = "<!-- W3 Total Cache: Minify debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), w3_get_engine_name($this->_config->get_string('minify.engine')));
        $debug_info .= sprintf("%s%s\r\n", str_pad('Group: ', 20), $group);
        
        require_once W3TC_LIB_W3_DIR . '/Minify.php';
        $w3_minify = & W3_Minify::instance();
        
        $css_groups = $w3_minify->get_groups($group, 'css');
        
        if (count($css_groups)) {
            $debug_info .= "Stylesheet info:\r\n";
            $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad('Location', 15, ' ', STR_PAD_BOTH), str_pad('Last modified', 19, ' ', STR_PAD_BOTH), str_pad('Size', 12, ' ', STR_PAD_LEFT), 'Path');
            
            foreach ($css_groups as $css_group => $css_files) {
                foreach ($css_files as $css_file => $css_file_path) {
                    if (is_a($css_file_path, 'Minify_Source')) {
                        $css_file_path = $css_file_path->filepath;
                        $css_file_info = sprintf('%s (%s)', $css_file, $css_file_path);
                    } else {
                        $css_file_info = $css_file;
                        $css_file_path = ABSPATH . ltrim($css_file_path, '/\\');
                    }
                    
                    $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad($css_group, 15, ' ', STR_PAD_BOTH), str_pad(date('Y-m-d H:i:s', filemtime($css_file_path)), 19, ' ', STR_PAD_BOTH), str_pad(filesize($css_file_path), 12, ' ', STR_PAD_LEFT), $css_file_info);
                }
            }
        }
        
        $js_groups = $w3_minify->get_groups($group, 'js');
        
        if (count($js_groups)) {
            $debug_info .= "JavaScript info:\r\n";
            $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad('Location', 15, ' ', STR_PAD_BOTH), str_pad('Last modified', 19, ' ', STR_PAD_BOTH), str_pad('Size', 12, ' ', STR_PAD_LEFT), 'Path');
            
            foreach ($js_groups as $js_group => $js_files) {
                foreach ($js_files as $js_file => $js_file_path) {
                    if (is_a($js_file_path, 'Minify_Source')) {
                        $js_file_path = $js_file_path->filepath;
                        $js_file_info = sprintf('%s (%s)', $js_file, $js_file_path);
                    } else {
                        $js_file_path = $js_file_info = ABSPATH . ltrim($js_file, '/\\');
                    }
                    
                    $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad($js_group, 15, ' ', STR_PAD_BOTH), str_pad(date('Y-m-d H:i:s', filemtime($js_file_path)), 19, ' ', STR_PAD_BOTH), str_pad(filesize($js_file_path), 12, ' ', STR_PAD_LEFT), $js_file_info);
                }
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
    
    /**
     * Check if we can do minify logic
     *
     * @return boolean
     */
    function can_minify()
    {
        /**
         * Skip if Minify is disabled
         */
        if (!$this->_config->get_boolean('minify.enabled')) {
            $this->minify_reject_reason = 'minify is disabled';
            
            return false;
        }
        
        /**
         * Skip if Admin
         */
        if (defined('WP_ADMIN')) {
            $this->minify_reject_reason = 'wp-admin';
            
            return false;
        }
        
        /**
         * Skip if doint AJAX
         */
        if (defined('DOING_AJAX')) {
            $this->minify_reject_reason = 'doing AJAX';
            
            return false;
        }
        
        /**
         * Skip if doing cron
         */
        if (defined('DOING_CRON')) {
            $this->minify_reject_reason = 'doing cron';
            
            return false;
        }
        
        /**
         * Skip if APP request
         */
        if (defined('APP_REQUEST')) {
            $this->minify_reject_reason = 'application request';
            
            return false;
        }
        
        /**
         * Skip if XMLRPC request
         */
        if (defined('XMLRPC_REQUEST')) {
            $this->minify_reject_reason = 'XMLRPC request';
            
            return false;
        }
        
        /**
         * Check User agent
         */
        if (!$this->check_ua()) {
            $this->minify_reject_reason = 'user agent is rejected';
            
            return false;
        }
        
        /**
         * Check request URI
         */
        if (!$this->check_request_uri()) {
            $this->minify_reject_reason = 'request URI is rejected';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if we can do minify logic
     *
     * @return boolean
     */
    function can_minify2()
    {
        if ($this->_config->get_boolean('minify.html.reject.feed') && function_exists('is_feed') && is_feed()) {
            $this->minify_reject_reason = 'feed is rejected';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks User Agent
     *
     * @return boolean
     */
    function check_ua()
    {
        foreach ($this->_config->get_array('minify.reject.ua') as $ua) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $ua) !== false) {
                return false;
            }
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
        $auto_reject_uri = array(
            'wp-login', 
            'wp-register'
        );
        
        foreach ($auto_reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        foreach ($this->_config->get_array('minify.reject.uri') as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $_SERVER['REQUEST_URI'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generates rules
     *
     * @return string
     */
    function generate_rules()
    {
        $compressions = array();
        $engine = $this->_config->get_string('minify.engine');
        $lifetime = $this->_config->get_integer('minify.lifetime');
        
        $rules = '';
        $rules .= "# BEGIN W3TC Minify\n";
        
        if ($engine == 'file') {
            $compression = $this->_config->get_string('minify.compression');
            
            if ($compression != '') {
                if (stristr($compression, 'gzip') !== false) {
                    $compressions[] = 'gzip';
                }
                
                if (stristr($compression, 'deflate') !== false) {
                    $compressions[] = 'deflate';
                }
            }
            
            if (count($compressions)) {
                $rules .= "<IfModule mod_mime.c>\n";
                
                foreach ($compressions as $_compression) {
                    $rules .= "    AddEncoding " . $_compression . " ." . $_compression . "\n";
                    $rules .= "    <Files *.css." . $_compression . ">\n";
                    $rules .= "        ForceType text/css\n";
                    $rules .= "    </Files>\n";
                    $rules .= "    <Files *.js." . $_compression . ">\n";
                    $rules .= "        ForceType application/x-javascript\n";
                    $rules .= "    </Files>\n";
                }
                
                $rules .= "</IfModule>\n";
                
                $rules .= "<IfModule mod_setenvif.c>\n";
                $rules .= "    SetEnvIfNoCase Accept-Encoding (" . implode('|', $compressions) . ") APPEND_EXT=.$1\n";
                $rules .= "    <IfModule mod_deflate.c>\n";
                $rules .= "        SetEnvIfNoCase Request_URI \\.(" . implode('|', $compressions) . ")$ no-gzip\n";
                $rules .= "    </IfModule>\n";
                $rules .= "</IfModule>\n";
            }
            
            $rules .= "<IfModule mod_expires.c>\n";
            $rules .= "    ExpiresActive On\n";
            $rules .= "    ExpiresByType text/css M" . $lifetime . "\n";
            $rules .= "    ExpiresByType application/x-javascript M" . $lifetime . "\n";
            $rules .= "</IfModule>\n";
            
            $rules .= "<IfModule mod_headers.c>\n";
            $rules .= "    Header set Pragma public\n";
            $rules .= "    Header set X-Powered-By \"" . W3TC_POWERED_BY . "\"\n";
            $rules .= "    Header set Vary \"Accept-Encoding\"\n";
            $rules .= "    Header append Cache-Control \"public, must-revalidate, proxy-revalidate\"\n";
            $rules .= "</IfModule>\n";
        }
        
        $rules .= "<IfModule mod_rewrite.c>\n";
        $rules .= "    RewriteEngine On\n";
        
        if ($engine == 'file') {
            $rules .= "    RewriteCond %{REQUEST_FILENAME}%{ENV:APPEND_EXT} -f\n";
            $rules .= "    RewriteRule (.*) $1%{ENV:APPEND_EXT} [L]\n";
        }
        
        $rules .= "    RewriteRule ^([a-z0-9\\-_]+)\\.(include(-footer)?(-nb)?)\\.(css|js)$ index.php?gg=$1&g=$2&t=$5 [L]\n";
        $rules .= "</IfModule>\n";
        
        $rules .= "# END W3TC Minify\n\n";
        
        return $rules;
    }
    
    /**
     * Writes rules to file cache .htaccess
     *
     * @return boolean
     */
    function write_rules()
    {
        $path = W3TC_CONTENT_MINIFY_DIR . '/.htaccess';
        
        if (file_exists($path)) {
            if (($data = @file_get_contents($path)) !== false) {
                $data = $this->erase_rules($data);
            } else {
                return false;
            }
        } else {
            $data = '';
        }
        
        $data = trim($this->generate_rules() . $data);
        
        return @file_put_contents($path, $data);
    }
    
    /**
     * Erases W3TC rules from config
     *
     * @param string $data
     * @return string
     */
    function erase_rules($data)
    {
        $data = preg_replace('~# BEGIN W3TC Minify.*# END W3TC Minify~Us', '', $data);
        $data = trim($data);
        
        return $data;
    }
    
    /**
     * Removes W3TC rules from file cache dir
     *
     * @return boolean
     */
    function remove_rules()
    {
        $path = W3TC_CONTENT_MINIFY_DIR . '/.htaccess';
        
        return @unlink($path);
    }
    
    /**
     * Checks rules
     *
     * @return boolean
     */
    function check_rules()
    {
        $path = W3TC_CACHE_FILE_MINIFY_DIR . '/.htaccess';
        $search = $this->generate_rules();
        
        return (($data = @file_get_contents($path)) && strstr(w3_clean_rules($data), w3_clean_rules($search)) !== false);
    }
}

/**
 * Prints script link for scripts group
 *
 * @param string $location
 * @param string $group
 */
function w3tc_scripts($location, $group = null)
{
    $w3_plugin_minify = & W3_Plugin_Minify::instance();
    $w3_plugin_minify->printed_scripts[] = $location;
    
    echo $w3_plugin_minify->get_scripts($location, $group);
}

/**
 * Prints style link for styles group
 *
 * @param string $location
 * @param string $group
 */
function w3tc_styles($location, $group = null)
{
    $w3_plugin_minify = & W3_Plugin_Minify::instance();
    $w3_plugin_minify->printed_styles[] = $location;
    
    echo $w3_plugin_minify->get_styles($location, $group);
}

/**
 * Prints link for custom scripts
 *
 * @param string|array $files
 * @param boolean $blocking
 */
function w3tc_custom_script($files, $blocking = true)
{
    $w3_plugin_minify = & W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_custom_script($files, $blocking);
}

/**
 * Prints link for custom styles
 *
 * @param string|array $files
 * @param boolean $import
 */
function w3tc_custom_style($files, $import = false)
{
    $w3_plugin_minify = & W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_custom_style($files, $import);
}
