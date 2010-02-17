<?php

function jkf_tml_custom_email_admin_menu() {
	$parent = plugin_basename(TML_MODULE_DIR . '/custom-email/admin/options.php');
	jkf_tml_add_menu_page(__('E-mail', 'theme-my-login'), $parent);
	jkf_tml_add_submenu_page($parent, __('General', 'theme-my-login'), TML_MODULE_DIR . '/custom-email/admin/options-general.php');
	jkf_tml_add_submenu_page($parent, __('New User', 'theme-my-login'), TML_MODULE_DIR . '/custom-email/admin/options-new-user.php');
	jkf_tml_add_submenu_page($parent, __('Retrieve Password', 'theme-my-login'), TML_MODULE_DIR . '/custom-email/admin/options-retrieve-pass.php');
	jkf_tml_add_submenu_page($parent, __('Reset Password', 'theme-my-login'), TML_MODULE_DIR . '/custom-email/admin/options-reset-pass.php');
}

function jkf_tml_custom_email_sanitize_html($text) {
	$text = addslashes($text);
	$text = wp_filter_post_kses($text);
	$text = stripslashes($text);
	$text = esc_html($text);
	return $text;
}

function jkf_tml_custom_email_sanitize_text($text) {
	$text = strip_tags($text);
	$text = addslashes($text);
	$text = wp_filter_post_kses($text);
	$text = stripslashes($text);
	return $text;
}

function jkf_tml_custom_email_save_settings($settings) {
	$settings['email']['mail_from_name'] = jkf_tml_custom_email_sanitize_text($settings['email']['mail_from_name']);
	$settings['email']['mail_from'] = sanitize_email($settings['email']['mail_from']);
	$settings['email']['mail_content_type'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $settings['email']['mail_content_type']);
	
	$settings['email']['new_user']['admin_disable'] = absint($settings['email']['new_user']['admin_disable']);
	$settings['email']['reset_pass']['admin_disable'] = absint($settings['email']['reset_pass']['admin_disable']);
	return $settings;
}

?>