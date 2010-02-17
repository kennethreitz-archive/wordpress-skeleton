<?php
/*
Plugin Name: Custom Redirection
Description: Enabling this module will initialize custom redirection. You will then have to configure the settings via the "Redirection" tab.
*/

add_action('tml_init', 'jkf_tml_custom_redirect_init');
function jkf_tml_custom_redirect_init() {
	include( TML_MODULE_DIR . '/custom-redirection/hook-functions.php' );
	add_filter('login_redirect', 'jkf_tml_custom_redirect_login', 10, 3);
	add_filter('logout_redirect', 'jkf_tml_custom_redirect_logout', 10, 3);
	add_action('login_form', 'jkf_tml_custom_redirect_login_form');
}

add_action('tml_admin_init', 'jkf_tml_custom_redirect_admin_init');
function jkf_tml_custom_redirect_admin_init() {
    require_once (TML_MODULE_DIR . '/custom-redirection/admin.php');
	add_action('tml_admin_menu', 'jkf_tml_custom_redirect_admin_menu');
	add_filter('tml_save_settings', 'jkf_tml_custom_redirect_save_settings');
}

add_action('activate_custom-redirection/custom-redirection.php', 'jkf_tml_custom_redirection_install');
function jkf_tml_custom_redirection_install() {
	global $theme_my_login;
	
	if ( ! isset($theme_my_login->options['redirection']) )
		$theme_my_login->options['redirection'] = jkf_tml_custom_redirect_default_settings();
		
	update_option('theme_my_login', $theme_my_login->options);
}

function jkf_tml_custom_redirect_default_settings() {
	global $wp_roles;
	
	$user_roles = $wp_roles->get_names();
	foreach ( $user_roles as $role => $label ) {
		$options[$role] = array('login_type' => 'default', 'login_url' => '', 'logout_type' => 'default', 'logout_url' => '');
	}
    return $options;
}
        
?>