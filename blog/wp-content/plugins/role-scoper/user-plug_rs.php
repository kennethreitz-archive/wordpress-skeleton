<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();


function set_current_user($id, $name = '') {
	return wp_set_current_user($id, $name);
}

function wp_set_current_user($id, $name = '') {
	global $current_user;

	//log_mem_usage_rs( 'start wp_set_current_user.php' );
	
	require_once('db-config_rs.php');
	
	// need this for async-upload.php (otherwise current_user not loaded)
	if ( ! empty($_POST['auth_cookie']) ) {
		$cookie_key = 'wordpress_' . COOKIEHASH;
		if ( empty($_COOKIE[LOGGED_IN_COOKIE]) && empty($_COOKIE[$cookie_key]) ) {
			$_COOKIE[$cookie_key] = $_POST['auth_cookie'];
			$_COOKIE[LOGGED_IN_COOKIE] = $_POST['auth_cookie'];
			unset($current_user);
			get_currentuserinfo();
		}
	}
	
	if ( $id && isset($current_user) && ($id == $current_user->ID) )
		return $current_user;

	// As of WP 2.5, wp_set_current_user fires once (with user_id 0) before global $wp_roles is set, 
	// then again with $wp_roles and user_id set
	if ( ! $id && ! $name && is_admin() )
		return $current_user;

	scoper_version_check();

	if ( $id || ( $name && get_userdatabylogin($name) ) )
		require_once('scoped-user.php');
	else
		require_once('scoped-user_anon.php');
	
	//log_mem_usage_rs( 'required scoped-user.php' );
		
	$current_user = new WP_Scoped_User($id, $name);
	
	//log_mem_usage_rs( 'new WP_Scoped_User' );
	
	// from default wp_set_current_user: Setup global user vars.  Used by WP set_current_user() for back compat.
	global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_pass_md5, $user_identity;
	
	if ( ! empty($current_user->ID) ) {
		$userdata = $current_user->data;
		$user_login	= $current_user->user_login;
		$user_level	= (int) isset($current_user->user_level) ? $current_user->user_level : 0;
		$user_ID	= (int) $current_user->ID;
		$user_email	= $current_user->user_email;
		$user_url	= $current_user->user_url;
		$user_pass_md5	= md5($current_user->user_pass);
		$user_identity	= $current_user->display_name;
	}
	
	do_action('set_current_user');
	do_action('set_current_scoped_user');
	
	return $current_user;
}
?>