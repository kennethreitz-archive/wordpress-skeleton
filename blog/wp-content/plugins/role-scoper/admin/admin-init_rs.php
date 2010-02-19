<?php

function scoper_admin_init() {
	if ( ! empty($_POST['rs_submit']) || ! empty($_POST['rs_defaults']) || ! empty($_POST['rs_flush_cache']) ) {
		// For 'options' and 'realm' admin panels, handle updated options right after current_user load (and before scoper init).
		// By then, check_admin_referer is available, but Scoper config and WP admin menu has not been loaded yet.
		
		require_once( SCOPER_ABSPATH . '/submittee_rs.php');	
		$handler = new Scoper_Submittee();
	
		if ( isset($_POST['rs_submit']) ) {
			$sitewide = isset($_POST['rs_options_doing_sitewide']);
			$customize_defaults = isset($_POST['rs_options_customize_defaults']);
			$handler->handle_submission( 'update', $sitewide, $customize_defaults );
			
		} elseif ( isset($_POST['rs_defaults']) ) {
			$sitewide = isset($_POST['rs_options_doing_sitewide']);
			$customize_defaults = isset($_POST['rs_options_customize_defaults']);
			$handler->handle_submission( 'default', $sitewide, $customize_defaults );
			
		} elseif ( isset($_POST['rs_flush_cache']) )
			$handler->handle_submission( 'flush' );
	} 
}


function scoper_use_posted_init_options() {
	if ( ! isset( $_POST['role_type'] ) || ! strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rs-' ) || defined('SCOPER_ROLE_TYPE') )
		return;
	
	if ( isset( $_POST['rs_defaults'] ) ) {
		$arr = scoper_default_options();
		
		// arr['role_type'] is numeric input index on update, string value on defaults.
		$posted_role_type = $arr['role_type'];
	} else {
		$arr = $_POST;
		
		global $scoper_role_types;
		$posted_role_type = $scoper_role_types[ $arr['role_type'] ];
	}
	
	define ( 'SCOPER_ROLE_TYPE', $posted_role_type);
	define ( 'SCOPER_CUSTOM_USER_BLOGCAPS', ! empty( $arr['custom_user_blogcaps'] ) );
	
	define ( 'DEFINE_GROUPS_RS', ! empty($arr['define_usergroups']) );
	define ( 'GROUP_ROLES_RS', ! empty($arr['define_usergroups']) && ! empty($arr['enable_group_roles']) );
	define ( 'USER_ROLES_RS', ! empty($arr['enable_user_roles']) );
	
	if ( empty ($arr['persistent_cache']) && ! defined('DISABLE_PERSISTENT_CACHE') )
		define ( 'DISABLE_PERSISTENT_CACHE', true );

	wpp_cache_init( IS_MU_RS && scoper_establish_group_scope() );
}
	
?>