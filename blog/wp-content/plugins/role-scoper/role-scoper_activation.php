<?php
if ( ! function_exists( 'scoper_activate' ) ) {
function scoper_activate() {
	// set_current_user may have triggered DB setup already
	global $scoper_db_setup_done;
	if ( empty ($scoper_db_setup_done) ) {
		require_once('db-setup_rs.php');
		scoper_db_setup('');  // TODO: is it safe to call get_option here to pass in last DB version, avoiding unnecessary ALTER TABLE statement?
	}
	
	require_once('admin/admin_lib_rs.php');
	ScoperAdminLib::sync_wproles();
	
	scoper_flush_site_rules();
	scoper_expire_file_rules();
}
}

if ( ! function_exists( 'scoper_deactivate' ) ) {
function scoper_deactivate() {
	if ( function_exists( 'wpp_cache_flush' ) )
		wpp_cache_flush();
	
	delete_option('scoper_page_ancestors');
	
	global $wp_taxonomies;
	if ( ! empty($wp_taxonomies) ) {
		foreach ( array_keys($wp_taxonomies) as $taxonomy ) {
			delete_option("{$taxonomy}_children");
			delete_option("{$taxonomy}_children_rs");
			delete_option("{$taxonomy}_ancestors_rs");
		}
	}

	require_once('role-scoper_init.php');
	scoper_clear_site_rules();
	scoper_clear_all_file_rules();
}
}

?>