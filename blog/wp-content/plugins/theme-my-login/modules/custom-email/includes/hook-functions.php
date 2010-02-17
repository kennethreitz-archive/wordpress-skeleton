<?php

function jkf_tml_custom_email_headers() {	
	add_filter('wp_mail_from', 'jkf_tml_custom_email_from');
	add_filter('wp_mail_from_name', 'jkf_tml_custom_email_from_name');
	add_filter('wp_mail_content_type', 'jkf_tml_custom_email_content_type');
}

function jkf_tml_custom_email_from($from_email) {
    global $theme_my_login;
    return empty($theme_my_login->options['email']['mail_from']) ? $from_email : $theme_my_login->options['email']['mail_from'];
}
    
function jkf_tml_custom_email_from_name($from_name) {
    global $theme_my_login;
    return empty($theme_my_login->options['email']['mail_from_name']) ? $from_name : $theme_my_login->options['email']['mail_from_name'];
}

function jkf_tml_custom_email_content_type($content_type) {
    global $theme_my_login;
    return empty($theme_my_login->options['email']['mail_content_type']) ? $content_type : 'text/' . $theme_my_login->options['email']['mail_content_type'];
}

function jkf_tml_custom_email_retrieve_pass_title($title, $user_id) {
	global $theme_my_login;
	return empty($theme_my_login->options['email']['retrieve_pass']['title']) ? $title : jkf_tml_custom_email_replace_vars($theme_my_login->options['email']['retrieve_pass']['title'], $user_id);
}

function jkf_tml_custom_email_retrieve_pass_message($message, $key, $user_id) {
	global $theme_my_login;
	$user = get_userdata($user_id);
	$replacements = array(
		'%loginurl%' => site_url('wp-login.php', 'login'),
		'%reseturl%' => site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login')
		);
	return empty($theme_my_login->options['email']['retrieve_pass']['message']) ? $message : jkf_tml_custom_email_replace_vars($theme_my_login->options['email']['retrieve_pass']['message'], $user_id, $replacements);
}

function jkf_tml_custom_email_reset_pass_title($title, $user_id) {
	global $theme_my_login;
	return empty($theme_my_login->options['email']['reset_pass']['title']) ? $title : jkf_tml_custom_email_replace_vars($theme_my_login->options['email']['reset_pass']['title'], $user_id);
}

function jkf_tml_custom_email_reset_pass_message($message, $new_pass, $user_id) {
	global $theme_my_login;
	$replacements = array(
		'%loginurl%' => site_url('wp-login.php', 'login'),
		'%user_pass%' => $new_pass
		);	
	return empty($theme_my_login->options['email']['reset_pass']['message']) ? $message : jkf_tml_custom_email_replace_vars($theme_my_login->options['email']['reset_pass']['message'], $user_id, $replacements);
}

function jkf_tml_custom_email_reset_pass_disable($enable) {
	global $theme_my_login;
	return ( $theme_my_login->options['email']['reset_pass']['admin_disable'] ) ? 0 : 1;
}

function jkf_tml_custom_email_new_user_title($title, $user_id) {
	global $theme_my_login;
	return empty($theme_my_login->options['email']['new_user']['title']) ? $title : jkf_tml_custom_email_replace_vars($theme_my_login->options['email']['new_user']['title'], $user_id);
}

function jkf_tml_custom_email_new_user_message($message, $new_pass, $user_id) {
	global $theme_my_login;
	$replacements = array(
		'%loginurl%' => site_url('wp-login.php', 'login'),
		'%user_pass%' => $new_pass
		);	
	return empty($theme_my_login->options['email']['new_user']['message']) ? $message : jkf_tml_custom_email_replace_vars($theme_my_login->options['email']['new_user']['message'], $user_id, $replacements);
}

function jkf_tml_custom_email_new_user_admin_disable($enable) {
	global $theme_my_login;
	return ( $theme_my_login->options['email']['new_user']['admin_disable'] ) ? 0 : 1;
}

function jkf_tml_custom_email_replace_vars($text, $user_id = '', $replacements = array()) {
	// Get user data
	if ( $user_id )
		$user = get_userdata($user_id);
		
	// Get all matches ($matches[0] will be '%value%'; $matches[1] will be 'value')
	preg_match_all('/%([^%]*)%/', $text, $matches);
		
	// Iterate through matches
	foreach ( $matches[0] as $key => $match ) {
		if ( isset($replacements[$match]) )
			continue;		
		if ( isset($user) && isset($user->{$matches[1][$key]}) )
			$replacements[$match] = $user->{$matches[1][$key]};
		else
			$replacements[$match] = get_bloginfo($matches[1][$key]);
	}
	return str_replace(array_keys($replacements), array_values($replacements), $text);
}

?>