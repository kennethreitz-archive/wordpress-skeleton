<?php
/*
Plugin Name: Custom Passwords
Description: Enabling this module will initialize and enable custom passwords. There are no other settings for this module.
*/

add_action('tml_init', 'jkf_tml_custom_pass_init');
function jkf_tml_custom_pass_init() {
	global $theme_my_login;
	if ( is_page($theme_my_login->options['page_id']) || is_active_widget(false, null, 'theme-my-login') || $theme_my_login->options['enable_template_tag'] ) {
		require_once (TML_MODULE_DIR . '/custom-passwords/functions.php');
		add_action('register_form', 'jkf_tml_custom_pass_form');
		add_action('registration_errors', 'jkf_tml_custom_pass_errors');
		add_action('login_action_resetpass', 'jkf_tml_custom_pass_reset_action');
		add_action('login_action_rp', 'jkf_tml_custom_pass_reset_action');
		add_action('login_form_resetpass', 'jkf_tml_custom_pass_reset_form');
		add_action('login_form_rp', 'jkf_tml_custom_pass_reset_form');
		add_filter('user_registration_pass', 'jkf_tml_custom_pass_set_pass');
		add_filter('login_message', 'jkf_tml_custom_pass_login_message');
		add_filter('register_redirect', 'jkf_tml_custom_pass_register_redirect');
		add_filter('resetpass_redirect', 'jkf_tml_custom_pass_resetpass_redirect');
	}
}

?>