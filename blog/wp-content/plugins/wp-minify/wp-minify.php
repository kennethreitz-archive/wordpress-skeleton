<?php
/*
Plugin Name: WP Minify
Plugin URI: http://omninoggin.com/wordpress-plugins/wp-minify-wordpress-plugin/
Description: This plugin uses the Minify engine to combine and compress JS and CSS files to improve page load time.
Version: 0.7.4
Author: Thaya Kareeson
Author URI: http://omninoggin.com
*/

/*
Copyright 2009-2010 Thaya Kareeson (email : thaya.kareeson@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!class_exists('WPMinify')) {

  class WPMinify {

    var $author_homepage = 'http://omninoggin.com/';
    var $homepage = 'http://omninoggin.com/wordpress-plugins/wp-minify-wordpress-plugin/';
    var $name = 'wp_minify'; 
    var $name_dashed = 'wp-minify'; 
    var $name_proper = 'WP Minify'; 
    var $required_wp_version = '2.7';
    var $version = '0.7.4';

    var $c = null;
    var $debug = true;
    var $cache_location = 'wp-content/plugins/wp-minify/cache/';
    var $url_len_limit = 2000;
    var $minify_limit = 50;
    var $buffer_started = false;
    var $default_exclude = array('https://');

    function WPMinify() {
      // initialize common functions
      $this->c = new WPMinifyCommon($this);

      // load translation
      $this->c->load_text_domain();

      // register admin scripts
      add_action('admin_init', array($this->c, 'a_register_scripts'));
      add_action('admin_init', array($this->c, 'a_register_styles'));

      // check wp version
      add_action('admin_head', array($this->c, 'a_check_version'));

      // load admin menu
      add_action('admin_menu', array($this, 'a_menu'));

      // register ajax handler
      add_action('wp_ajax_wpm', array($this, 'a_ajax_handler'));

      if (!is_admin()) {
        // No need to minify admin stuff
        add_action('init', array($this, 'pre_content'), 99999);
        add_action('wp_footer', array($this, 'post_content'));
        // advertise hook
        add_action('wp_footer', array($this, 'advertise'));
      }
    }

    // admin functions
  
    function a_default_options() {
      return array(
        'cache_external' => false,
        'cache_interval' => 900,
        'css_exclude' => array(),
        'css_include' => array(),
        'debug' => false,
        'enable_css' => true,
        'enable_js' => true,
        'extra_minify_options' => '',
        'js_exclude' => array(),
        'js_include' => array(),
        'js_in_footer' => false,
        'show_link' => true,
        'show_advanced' => false,
        'version' => $this->version,
        'deprecated' => array(
          'wp_path'
        )
      );
    }

    function a_update_options() {
      // new options
      $wpm_new_options = stripslashes_deep($_POST['wpm_options_update']);
  
      // current options
      $wpm_current_options = get_option($this->name);
  
      // convert "on" to true and "off" to false for checkbox fields
      // and set defaults for fields that are left blank
      if ( isset($wpm_new_options['show_link']) && $wpm_new_options['show_link'] == "on")
        $wpm_new_options['show_link'] = true;
      else
        $wpm_new_options['show_link'] = false;
  
      if ( isset($wpm_new_options['enable_js']) )
        $wpm_new_options['enable_js'] = true;
      else
        $wpm_new_options['enable_js'] = false;
  
      if ( isset($wpm_new_options['enable_css']) )
        $wpm_new_options['enable_css'] = true;
      else
        $wpm_new_options['enable_css'] = false;
  
      if ( isset($wpm_new_options['cache_external']) )
        $wpm_new_options['cache_external'] = true;
      else
        $wpm_new_options['cache_external'] = false;
  
      if ( isset($wpm_new_options['js_in_footer']) )
        $wpm_new_options['js_in_footer'] = true;
      else
        $wpm_new_options['js_in_footer'] = false;
  
      if ( isset($wpm_new_options['debug']) )
        $wpm_new_options['debug'] = true;
      else
        $wpm_new_options['debug'] = false;
  
      if ( strlen(trim($wpm_new_options['js_include'])) > 0 )
        $wpm_new_options['js_include'] = $this->array_trim(split(chr(10), str_replace(chr(13), '', $wpm_new_options['js_include'])));
      else
        $wpm_new_options['js_include'] = array();
  
      if ( strlen(trim($wpm_new_options['js_exclude'])) > 0 )
        $wpm_new_options['js_exclude'] = $this->array_trim(split(chr(10), str_replace(chr(13), '', $wpm_new_options['js_exclude'])));
      else
        $wpm_new_options['js_exclude'] = array();
  
      if ( strlen(trim($wpm_new_options['css_include'])) > 0 )
        $wpm_new_options['css_include'] = $this->array_trim(split(chr(10), str_replace(chr(13), '', $wpm_new_options['css_include'])));
      else
        $wpm_new_options['css_include'] = array();
  
      if ( strlen(trim($wpm_new_options['css_exclude'])) > 0 )
        $wpm_new_options['css_exclude'] = $this->array_trim(split(chr(10), str_replace(chr(13), '', $wpm_new_options['css_exclude'])));
      else
        $wpm_new_options['css_exclude'] = array();
  
      // Update options
      foreach($wpm_new_options as $key => $value) {
        $wpm_current_options[$key] = $value;
      }
  
      update_option($this->name, $wpm_current_options);
    }

    function a_set_advanced_options($val) {
      $wpm_options = get_option($this->name);
      $wpm_options['show_advanced'] = $val;
      update_option($this->name, $wpm_options);
    }

    function a_ajax_handler() {
      check_ajax_referer($this->name);
      if(isset($_POST['wpm_action'])){
        if ( strtolower($_POST['wpm_action']) == 'show_advanced' ) {
          $this->a_set_advanced_options(true);
        }
        elseif ( strtolower($_POST['wpm_action']) == 'hide_advanced' ) {
          $this->a_set_advanced_options(false);
        }
        else {
          echo 'Invalid wpm_action.';
        }
      }
      exit();
    }

    function a_request_handler() {
      if (isset($_POST['wpm_options_update_submit'])) {
        check_admin_referer($this->name);
        $this->a_update_options();
        add_action('admin_notices', array($this->c, 'a_notify_updated'));
      }
      elseif (isset($_POST['wpm_options_clear_cache_submit'])) {
        // if user wants to regenerate nonce
        check_admin_referer($this->name);
        $this->c->a_clear_cache();
        add_action('admin_notices', array($this->c, 'a_notify_cache_cleared'));
      }
      elseif (isset($_POST['wpm_options_upgrade_submit'])) {
        // if user wants to upgrade options (for new options on version upgrades)
        check_admin_referer($this->name);
        $this->c->a_upgrade_options();
        add_action('admin_notices', array($this->c, 'a_notify_upgraded'));
      }
      elseif (isset($_POST['wpm_options_reset_submit'])) {
        // if user wants to reset all options
        check_admin_referer($this->name);
        $this->c->a_reset_options();
        add_action('admin_notices', array($this->c, 'a_notify_reset'));
      }

      // only check these on plugin settings page
      $this->c->a_check_dir_writable($this->c->get_plugin_dir().'cache/', array($this, 'a_notify_cache_not_writable'));
      $this->c->a_check_orphan_options(array($this, 'a_notify_orphan_options'));
      if ($this->c->a_check_dir_writable($this->c->get_plugin_dir().'min/config.php', array($this, 'a_notify_config_not_writable'))) {
        $this->a_check_minify_config();
      }
    }

    function a_check_minify_config() {
      $fname = $this->c->get_plugin_dir().'min/config.php';
      $fhandle = fopen($fname,'r');
      $content = fread($fhandle,filesize($fname));
      
      preg_match('/\/\/###WPM-CACHE-PATH-BEFORE###(.*)\/\/###WPM-CACHE-PATH-AFTER###/s', $content, $matches);
      $cache_path_code = $matches[1];
      if (!preg_match('/\$min_cachePath.*?/', $cache_path_code)) {
        $content = preg_replace(
          '/\/\/###WPM-CACHE-PATH-BEFORE###(.*)\/\/###WPM-CACHE-PATH-AFTER###/s',
          "//###WPM-CACHE-PATH-BEFORE###\n".'$min_cachePath = \''.$this->c->get_plugin_dir()."cache/';\n//###WPM-CACHE-PATH-AFTER###",
          $content);
        $this->a_notify_modified_minify_config();
      }
      
      $fhandle = fopen($fname,"w");
      fwrite($fhandle,$content);
      fclose($fhandle);
    }

    function a_notify_cache_not_writable() {
      $this->c->a_notify(
        sprintf('%s: %s',
          __('Cache directory is not writable. Please grant your server write permissions to the directory', $this->name),
          $this->c->get_plugin_dir().'cache/'),
        true);
    }

    function a_notify_config_not_writable() {
      $this->c->a_notify(
        sprintf('%s: %s',
          __('Minify Engine config.php is not writable. Please grant your server write permissions to file', $this->name),
          $this->c->get_plugin_dir().'min/config.php'));
    }

    function a_notify_orphan_options() {
      $this->c->a_notify(
        sprintf('%s',
          __('Some option settings are missing (possibly from plugin upgrade).  Please reactivate.', $this->name)));
    }

    function a_notify_modified_minify_config() {
      $this->c->a_notify( __('Minify Engine config.php was configured automatically.', $this->name));
    }

    function a_menu() {
      $options_page = add_options_page($this->name_proper, $this->name_proper, 'manage_options', 'wp-minify', array($this, 'a_page'));
      add_action('admin_head-'.$options_page, array($this, 'a_request_handler'));
      add_action('admin_print_scripts-'.$options_page, array($this->c, 'a_enqueue_scripts'));
      add_action('admin_print_styles-'.$options_page, array($this->c, 'a_enqueue_styles'));
    }
  
    function a_page() {
      $wpm_options = get_option($this->name);
      printf('
        <div class="wrap">
          <h2>%s</h2>
          <div>
            <a href="'.preg_replace('/&wpm-page=[^&]*/', '', $_SERVER['REQUEST_URI']).'">%s</a>&nbsp;|&nbsp;
            <a href="'.$this->homepage.'">%s</a>
          </div>',
        __('WP Minify Options', $this->name),
        __('General Configuration', $this->name),
        __('Documentation', $this->name)
      );
      printf('<div class="omni_admin_main">');
      if ( isset($_GET['wpm-page']) ) {
        if ( $_GET['wpm-page'] || !$_GET['wpm-page'] ) {
          require_once('options-generic.php');
        }
      }
      else {
        require_once('options-generic.php');
      }
      printf('</div>'); // omni_admin_main
      require_once('options-sidebar.php');
      printf('</div>'); // wrap

    } // admin_page()

    // other functions
  
    function fetch_and_cache($url, $cache_file) {
      $ch = curl_init();
      $timeout = 5; // set to zero for no timeout
      curl_setopt ($ch, CURLOPT_URL, $url);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
      $content = curl_exec($ch);
      curl_close($ch);
      if ( $content ) {
        if ( is_array($content) ) {
          $content = implode($content);
        }

        // save cache file
        $fh = fopen($cache_file, 'w');
        if ( $fh ) {
          fwrite($fh, $content);
          fclose($fh);
        }
        else {
          // cannot open for write.  no error b/c something else is probably writing to the file.
        }

        return $content;
      }
      else {
        printf(
          '%s: '.$url.'. %s<br/>',
          __('Error: Could not fetch and cache URL'),
          __('You might need to exclude this file in WP Minify options.')
        );
        return '';
      }
    }
  
    function refetch_cache_if_expired($url, $cache_file) {
      $wpm_options = get_option($this->name);
      $cache_file_mtime = filemtime($cache_file);
      if ( (time() - $cache_file_mtime) > $wpm_options['cache_interval'] ) {
        $this->fetch_and_cache($url, $cache_file);
      }
    }
  
    function tiny_filename($str) {
      $f = __FILE__;
      // no fancy shortening for Windows
      return ('/' === $f[0]) ? strtr(base64_encode(md5($str, true)), '+/=', '-_(') : md5($str);
    }
  
    function array_trim($arr, $charlist=null){
      foreach($arr as $key => $value){
        if (is_array($value)) $result[$key] = array_trim($value, $charlist);
        else $result[$key] = trim($value, $charlist);
      }
      return $result;
    }
  
    function check_and_split_url($url) {
      $wpm_options = get_option($this->name);
  
      // append &debug if we need to
      if ( $wpm_options['debug'] ) {
        $debug_url = '&debug=true';
      }
      else {
        $debug_url = '';
      }
  
      $url_chunk = explode('?f=', $url);
      $base_url = array_shift($url_chunk);
      $files = explode(',', array_shift($url_chunk));
      $num_files = sizeof($files);
      if ( $url > $this->url_len_limit or $num_files > $this->minify_limit ) {
        $first_half = $this->check_and_split_url($base_url . '?f=' . implode(',', array_slice($files, 0, $num_files/2)));
        $second_half = $this->check_and_split_url($base_url . '?f=' . implode(',', array_slice($files, $num_files/2)));
        return $first_half + $second_half;
      }
      else {
        return array($base_url . '?f=' . implode(',', $files) . $debug_url . '&' . $wpm_options['extra_minify_options']);
      }
    }
  
    function fetch_content($url, $type) {
      $wpm_options = get_option($this->name);
      $cache_file = $this->c->get_plugin_dir().'cache/'.md5($url).$type;
      $content = '';
      if ( file_exists($cache_file) ) {
        // check cache expiration
        $this->refetch_cache_if_expired($url, $cache_file);
  
        $fh = fopen($cache_file, 'r');
        if ( $fh && filesize($cache_file) > 0 ) {
          $content = fread($fh, filesize($cache_file));
          fclose($fh);
        }
        else {
          // cannot open cache file so fetch it
          $content = $this->fetch_and_cache($url, $cache_file);
        }
      }
      else {
        // no cache file.  fetch from internet and save to local cache
        $content = $this->fetch_and_cache($url, $cache_file);
      }
      return $content;
    }

    function get_script_src_from_handle($handle) {
      global $wp_scripts;
      $ver = $wp_scripts->registered[$handle]->ver ? $wp_scripts->registered[$handle]->ver : $wp_scripts->default_version;
      if ( isset($wp_scripts->args[$handle]) )
        $ver .= '&amp;' . $wp_scripts->args[$handle];
  
      $src = $wp_scripts->registered[$handle]->src;
      if ( !preg_match('|^https?://|', $src) && !preg_match('|^' . preg_quote(WP_CONTENT_URL) . '|', $src) ) {
        $src = $wp_scripts->base_url . $src;
      }
  
      $src = add_query_arg('ver', $ver, $src);
      $src = clean_url(apply_filters( 'script_loader_src', $src, $handle ));
  
      $wp_scripts->print_scripts_l10n( $handle );
  
      return $src;
    }

    function local_version($url) {
      $site_url = trailingslashit(get_option('siteurl'));
      $num_matches = preg_match('/^https?:\/\/.*?\//', $site_url, $matches);
      $domain = $num_matches>0? $matches[0] : $site_url; // domain if found; the "slashed" site url otherwise
      $url = str_replace($domain, '', $url); // relative paths only for local urls
      $url = preg_replace('/^\//', '', $url); // strip front / if any
      $url = preg_replace('/\?.*/i', '', $url); // throws away parameters, if any
      return $url;
    }

    function is_external($url, $localize=true) {
      if ($localize) {
        $url = $this->local_version($url);
      }

      if (substr($url, 0, 4) != 'http'
        && (substr($url, -3, 3) == '.js' || substr($url, -4, 4) == '.css')) {
        return false;
      } else {
        return true;
      }
    }
  
    function get_js_location($src) {
      if ( $this->debug )
        echo 'Script URL:'.$src."<br/>\n";
  
      $script_path = $this->local_version($src);
      if ($this->is_external($script_path, false)) {
        // fetch scripts if necessary
        $this->fetch_content($src, '.js');
        $location = $this->cache_location . md5($src) . '.js';
        if ( $this->debug )
          echo 'External script detected, cached as:'. md5($src) . "<br/>\n";
      } else {
        // if script is local to server
        $location = $script_path;
        if ( $this->debug )
          echo 'Local script detected:'.$script_path."<br/>\n";
      }
  
      return $location;
    }
  
    function get_css_location($src) {
      if ( $this->debug )
        echo 'Style URL:'.$src."<br/>\n";
  
      $css_path = $this->local_version($src);
      if ($this->is_external($css_path, false)) {
        // fetch scripts if necessary
        $this->fetch_content($src, '.css');
        $location = $this->cache_location . md5($src) . '.css';
        if ( $this->debug )
          echo 'External css detected, cached as:'. md5($src) . "<br/>\n";
      } else {
        $location = $css_path;
        // if css is local to server
        if ( $this->debug )
          echo 'Local css detected:'.$css_path."<br/>\n";
      }
  
      return $location;
    }
  
    function build_minify_urls($locations) {
      $minify_url = $this->c->get_plugin_url().'min/?f=';
      $minify_url .= implode(',', $locations);
      return $this->check_and_split_url($minify_url);
    }

    function get_base_from_minify_args() {
      $wpm_options = get_option($this->name);
      if (!empty($wpm_options['extra_minify_options'])) {
        if (preg_match('/\bb=([^&]*?)(&|$)/', trim($wpm_options['extra_minify_options']), $matches)) {
          return trim($matches[1]);
        }
      }
      return '';
    }
  
    function extract_css($content) {
      $wpm_options = get_option($this->name);
      $css_locations = array();

      preg_match_all('/<link([^>]*?)>/i', $content, $link_tags_match);

      foreach ($link_tags_match[0] as $link_tag) {
        if ( strpos(strtolower($link_tag), 'stylesheet') ) {
          // check CSS media type
          if ( !strpos(strtolower($link_tag), 'media=' )
            || preg_match('/media=["\'](?:["\']|[^"\']*?(all|screen)[^"\']*?["\'])/', $link_tag )
          ) {
            preg_match('/href=[\'"]([^\'"]+)/', $link_tag, $href_match);
            if ( $href_match[1] ) {
              // support external files?
              if (!$wpm_options['cache_external'] && $this->is_external($href_match[1])) {
                continue; // skip if we don't cache externals and this file is external
              }

              // do not include anything in excluded list
              $skip = false;
              $exclusions = array_merge($this->default_exclude, $wpm_options['css_exclude']);
              foreach ($exclusions as $exclude_pat) {
                $exclude_pat = trim($exclude_pat);
                if ( strlen($exclude_pat) > 0 && strpos($href_match[1], $exclude_pat) !== false ) {
                  $skip = true;
                  break;
                }
              }
              if ( $skip ) continue;

              $content = str_replace($link_tag . '</link>', '', $content);
              $content = str_replace($link_tag, '', $content);
              $css_locations[] = $this->get_css_location($href_match[1]);
            }
          }
        }
      }

      foreach ($wpm_options['css_include'] as $src) {
        $css_locations[] = $this->get_css_location($src);
      }

      return array($content, $css_locations);
    }

    function inject_css($content, $css_locations) {
      if ( count($css_locations) > 0 ) {
        // build minify URLS
        $css_tags = '';
        $minify_urls = $this->build_minify_urls($css_locations);

        $latest_modified = 0;
        $base_path = trailingslashit($_SERVER['DOCUMENT_ROOT']);
        $base_path .= trailingslashit($this->get_base_from_minify_args());

        foreach ($css_locations as $location) {
          $path = $base_path.$location;
          $mtime = filemtime($path);
          if ($latest_modified < $mtime)
            $latest_modified = $mtime;
        }

        foreach ($minify_urls as $minify_url) {
          if ( $this->debug )
            echo 'Minify URL:'.$minify_url;
          $css_tags .= "<link rel='stylesheet' href='$minify_url&m=$latest_modified' type='text/css' media='screen' />";
        }

        // HTML5 has <header> tags so account for those in regex
        $content = preg_replace('/<head(>|\s[^>]*?>)/', "\\0\n$css_tags", $content, 1); // limit 1 replacement
      }
      return $content;
    }

    function extract_conditionals($content) {
      preg_match_all('/<!--\[if[^\]]*?\]>.*?<!\[endif\]-->/is', $content, $conditionals_match);
      $content = preg_replace('/<!--\[if[^\]]*?\]>.*?<!\[endif\]-->/is', '###WPM-CSS-CONDITIONAL###', $content);

      $conditionals = array();
      foreach ($conditionals_match[0] as $conditional) {
        $conditionals[] = $conditional;
      }

      return array($content, $conditionals);
    }

    function inject_conditionals($content, $conditionals) {
      while (count($conditionals) > 0 && strpos($content, '###WPM-CSS-CONDITIONAL###')) {
        $conditional = array_shift($conditionals);
        $content = preg_replace('/###WPM-CSS-CONDITIONAL###/', $conditional, $content, 1);
      }

      return $content;
    }

    function extract_js($content) {
      $wpm_options = get_option($this->name);
      $js_locations = array();

      preg_match_all('/<script([^>]*?)><\/script>/i', $content, $script_tags_match);

      foreach ($script_tags_match[0] as $script_tag) {
        if(strpos(strtolower($script_tag), 'text/javascript') !== false) {
          preg_match('/src=[\'"]([^\'"]+)/', $script_tag, $src_match);
          if ( $src_match[1] ) {
            // support external files?
            if (!$wpm_options['cache_external'] && $this->is_external($src_match[1])) {
              continue; // skip if we don't cache externals and this file is external
            }

            // do not include anything in excluded list
            $skip = false;
            $exclusions = array_merge($this->default_exclude, $wpm_options['js_exclude']);
            foreach ($exclusions as $exclude_pat) {
              $exclude_pat = trim($exclude_pat);
              if ( strlen($exclude_pat) > 0 && strpos($src_match[1], $exclude_pat) !== false ) {
                $skip = true;
                break;
              }
            }
            if ( $skip ) continue;

            $content = str_replace($script_tag, '', $content);
            $js_locations[] = $this->get_js_location($src_match[1]);
          }
        }
      }

      foreach ($wpm_options['js_include'] as $src) {
        $js_locations[] = $this->get_js_location($src);
      }

      return array($content, $js_locations);
    }

    function inject_js($content, $js_locations) {
      if ( count($js_locations) > 0 ) {
        // build minify URLS
        $js_tags = '';
        $minify_urls = $this->build_minify_urls($js_locations);

        $latest_modified = '';
        $base_path = trailingslashit($_SERVER['DOCUMENT_ROOT']);
        $base_path .= trailingslashit($this->get_base_from_minify_args());

        foreach ($js_locations as $location) {
          $path = $base_path.$location;
          $mtime = filemtime($path);
          if ($latest_modified < $mtime)
            $latest_modified = $mtime;
        }
  
        foreach ($minify_urls as $minify_url) {
          if ( $this->debug )
            echo 'Minify URL:'.$minify_url;
          $js_tags .= "<script type='text/javascript' src='$minify_url&m=$latest_modified'></script>";
        }

        $wpm_options = get_option($this->name);
        if ($wpm_options['js_in_footer']) {
          $content = preg_replace('/<\/body>/', "$js_tags\n</body>", $content, 1); // limit 1 replacement
        } else {
          // HTML5 has <header> tags so account for those in regex
          $content = preg_replace('/<head(>|\s[^>]*?>)/', "\\0\n$js_tags", $content, 1); // limit 1 replacement
        }
      }
      return $content;
    }

    function pre_content() {
      ob_start(array($this, 'modify_buffer'));

      // variable for sanity checking
      $this->buffer_started = true;
    }

    function modify_buffer($buffer) {
      $wpm_options = get_option($this->name);

      // minify JS
      if($wpm_options['enable_js']) {
        list($buffer, $js_locations) = $this->extract_js($buffer);
        $buffer= $this->inject_js($buffer, $js_locations);
      }

      // minify CSS (make sure to exclude CSS conditionals)
      if($wpm_options['enable_css']) {
        list($buffer, $conditionals) = $this->extract_conditionals($buffer);
        list($buffer, $css_locations) = $this->extract_css($buffer);
        $buffer = $this->inject_css($buffer, $css_locations);
        $buffer = $this->inject_conditionals($buffer, $conditionals);
      }

      return $buffer;
    }

    function post_content() {
      // sanity checking
      if($this->buffer_started) {
        ob_end_flush();
      }
    }
  
    function advertise() {
      $wpm_options = get_option($this->name);
      if ($wpm_options['show_link']) {
        printf("<p align='center'><small>Page optimized by <a href='$this->homepage' title='$this->name_proper WordPress Plugin' style='text-decoration:none;'>$this->name_proper</a> <a href='$this->author_homepage' title='WordPress Plugin' style='text-decoration:none;'>WordPress Plugin</a></small></p>");
      }
    }

  } // class wpm

} // if !class_exists('WPMinify')

require_once('common.php');

if (class_exists('WPMinify')) {
  $wp_minify = new WPMinify();
}

?>
