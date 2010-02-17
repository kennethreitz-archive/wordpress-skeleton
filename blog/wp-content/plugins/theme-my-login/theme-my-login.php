<?php
/*
Plugin Name: Theme My Login
Plugin URI: http://www.jfarthing.com/wordpress-plugins/theme-my-login-plugin
Description: Themes the WordPress login, registration and forgot password pages according to your theme.
Version: 5.0-pre-alpha
Author: Jeff Farthing
Author URI: http://www.jfarthing.com
Text Domain: theme-my-login
*/

// Set the default module directory
if ( !defined('TML_MODULE_DIR') )
    define('TML_MODULE_DIR', WP_PLUGIN_DIR . '/theme-my-login/modules');
	
require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/functions.php');

// Initialize global configuration object
$theme_my_login = (object) array(
    'options' => get_option('theme_my_login', jkf_tml_default_settings()),
    'errors' => '',
    'request_action' => isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login',
    'request_instance' => isset($_REQUEST['instance']) ? $_REQUEST['instance'] : 'tml-page',
    'current_instance' => '',
    'redirect_to' => ''
    );

// Load the plugin textdomain
load_plugin_textdomain('theme-my-login', '', 'theme-my-login/language');

jkf_tml_load_active_modules();

require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/pluggable-functions.php' );

// Include admin-functions.php for install/uninstall process
if ( defined('WP_ADMIN') && true == WP_ADMIN ) {
    require_once( WP_PLUGIN_DIR . '/theme-my-login/admin/includes/admin.php' );
    require_once( WP_PLUGIN_DIR . '/theme-my-login/admin/includes/module.php' );
	
    register_activation_hook(__FILE__, 'jkf_tml_install');
    register_uninstall_hook(__FILE__, 'jkf_tml_uninstall');
	
	add_action('admin_init', 'jkf_tml_admin_init');
    add_action('admin_menu', 'jkf_tml_admin_menu');
}

add_action('plugins_loaded', 'jkf_tml_load');
function jkf_tml_load() {
    global $theme_my_login;
	
	require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/hook-functions.php');
	
    do_action('tml_load', $theme_my_login);

    add_action('template_redirect', 'jkf_tml_template_redirect');
    
    add_filter('the_title', 'jkf_tml_the_title', 10, 2);
    add_filter('single_post_title', 'jkf_tml_single_post_title');
	
	if ( $theme_my_login->options['rewrite_links'] )
		add_filter('site_url', 'jkf_tml_site_url', 10, 3);
	
	if ( $theme_my_login->options['show_page'] ) {
		add_filter('page_link', 'jkf_tml_page_link', 10, 2);
		add_filter('get_pages', 'jkf_tml_get_pages', 10, 2);
	} elseif ( !$theme_my_login->options['show_page'] ) {
		add_filter('wp_list_pages_excludes', 'jkf_tml_list_pages_excludes');
	}
    
	add_shortcode('theme-my-login', 'jkf_tml_shortcode');
    
    if ( $theme_my_login->options['enable_widget'] ) {
        require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/widget.php');
		function jkf_tml_register_widget() {
			return register_widget("Theme_My_Login_Widget");
		}
        add_action('widgets_init', 'jkf_tml_register_widget');
    }
}

function jkf_tml_template_redirect() {
    global $theme_my_login;
	
	do_action('tml_init');
        
    if ( is_page($theme_my_login->options['page_id']) || is_active_widget(false, null, 'theme-my-login') || $theme_my_login->options['enable_template_tag'] ) {

        if ( $theme_my_login->options['enable_css'] )
            jkf_tml_get_css();
            
        require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/login-actions.php');
    }
}

?>