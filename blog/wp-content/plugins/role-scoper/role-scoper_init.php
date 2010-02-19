<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('defaults_rs.php');
	
require_once('hardway/cache-persistent.php');

if ( is_admin() )
	require_once( 'admin/admin-init_rs.php' );

if ( IS_MU_RS )
	require_once( 'mu-init_rs.php' );

if ( IS_MU_RS || defined('SCOPER_FORCE_FILE_INCLUSIONS') ) {
	// workaround to avoid file error on get_home_path() call
	if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
		include_once( ABSPATH . '/wp-admin/includes/file.php' );	
}

// If an htaccess regeneration is triggered by somebody else, insert our rules (normal non-MU installations).
add_filter('mod_rewrite_rules', 'scoper_mod_rewrite_rules');

// some options can be overridden by constant definition
add_filter( 'site_options_rs', 'scoper_apply_constants', 99 );
add_filter( 'options_rs', 'scoper_apply_constants', 99 );
	
add_action( 'delete_option', 'scoper_maybe_rewrite_inclusions' );
add_action( 'delete_transient_rewrite_rules', 'scoper_rewrite_inclusions' );

function scoper_maybe_rewrite_inclusions ( $option_name = '' ) {
	if ( $option_name == 'rewrite_rules' )
		scoper_rewrite_inclusions();
}

function scoper_rewrite_inclusions ( $option_name = '' ) {
	// force inclusion of required files in case flush_rules() is called from outside wp-admin, to prevent error when calling get_home_path() function
	if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
		include_once( ABSPATH . '/wp-admin/includes/misc.php' );
	
	if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
		include_once( ABSPATH . '/wp-admin/includes/file.php' );	
}

// htaccess directive intercepts direct access to uploaded files, converts to WP call with custom args to be caught by subsequent parse_query filter
// parse_query filter will return content only if user can read a containing post/page
function scoper_mod_rewrite_rules ( $rules ) {
	$file_filtering = scoper_get_option( 'file_filtering' );

	global $scoper;
	if ( ! isset($scoper) || is_null($scoper) )
		scoper_init();
	
	require_once( 'rewrite-rules_rs.php' );

	if ( IS_MU_RS ) {
		if ( $file_filtering ) {
			require_once( 'rewrite-mu_rs.php' );
			$rules = ScoperRewriteMU::insert_site_rules( $rules );
		}
	} else {
		$rs_rules = ScoperRewrite::build_site_rules();
		
		if ( $pos_endif = strpos( $rules, '</IfModule>' ) )
			$rules = substr( $rules, 0, $pos_endif ) . $rs_rules . substr($rules, $pos_endif);
		else
			$rules .= $rs_rules;
	}

	return $rules;
}

function scoper_flush_site_rules() {
	require_once( 'rewrite-rules_rs.php' );
	ScoperRewrite::update_site_rules( true );
}

function scoper_clear_site_rules() {
	require_once( 'rewrite-rules_rs.php' );
	remove_filter('mod_rewrite_rules', 'scoper_mod_rewrite_rules');
	ScoperRewrite::update_site_rules( false );
}

function scoper_flush_file_rules() {
	require_once( 'rewrite-rules_rs.php' );
	ScoperRewrite::update_blog_file_rules();
}


function scoper_clear_all_file_rules() {
	if ( IS_MU_RS ) {
		require_once( 'rewrite-mu_rs.php' );
		ScoperRewriteMU::clear_all_file_rules();
	} else {
		require_once( 'rewrite-rules_rs.php' );
		ScoperRewrite::update_blog_file_rules( false );
	} 
}


// forces content rules to be regenerated in every MU blog at next access
function scoper_expire_file_rules() {
	if ( IS_MU_RS )
		scoper_update_option( 'file_htaccess_min_date', agp_time_gmt(), true );
	else {
		if ( did_action( 'scoper_init' ) )
			scoper_flush_file_rules();  // for non-MU, just regenerate the file rules (for uploads folder) now
		else
			add_action( 'scoper_init', 'scoper_flush_file_rules' );
	}
}
	
	
function scoper_version_check() {
	$ver_change = false;

	$ver = get_option('scoper_version');

	if ( empty($ver['db_version']) || version_compare( SCOPER_DB_VERSION, $ver['db_version'], '!=') ) {
		$ver_change = true;
		
		require_once('db-setup_rs.php');
		scoper_db_setup($ver['db_version']);
	}
	
	// temp debug
	//if ( defined('RS_DEBUG') )
	//	$ver['version'] = '1.0.8';
	
	// These maintenance operations only apply when a previous version of RS was installed 
	if ( ! empty($ver['version']) ) {
		
		if ( version_compare( SCOPER_VERSION, $ver['version'], '!=') ) {
			$ver_change = true;
			
			require_once('admin/update_rs.php');
			scoper_version_updated( $ver['version'] );
			
			scoper_check_revision_settings();
		}
		
	} else {
		// first-time install (or previous install was totally wiped)
		require_once( 'admin/update_rs.php');
		scoper_set_default_rs_roledefs();
	}

	if ( $ver_change ) {
		$ver = array(
			'version' => SCOPER_VERSION, 
			'db_version' => SCOPER_DB_VERSION
		);
		
		update_option( 'scoper_version', $ver );
	}
}

function scoper_load_textdomain() {
	if ( defined( 'SCOPER_TEXTDOMAIN_LOADED' ) )
		return;

	load_plugin_textdomain( 'scoper', '', SCOPER_FOLDER . '/languages' );

	define('SCOPER_TEXTDOMAIN_LOADED', true);
}

function scoper_log_init_action() {
	define ( 'SCOPER_INIT_ACTION_DONE', true );

	require_once('db-config_rs.php');
	
	$func = "require('db-config_rs.php');";
	add_action( 'switch_blog', create_function( '', $func ) );
	
	if ( is_admin() )
		scoper_load_textdomain();

	elseif ( defined('XMLRPC_REQUEST') )
		require_once('xmlrpc_rs.php');
}

// since sequence of set_current_user and init actions seems unreliable, make sure our current_user is loaded first
function scoper_maybe_init() {
	if ( defined('SCOPER_INIT_ACTION_DONE') )
		scoper_init();
	else
		add_action('init', 'scoper_init', 2);
}

function scoper_init() {
	global $scoper;

	if ( IS_MU_RS ) {
		global $scoper_sitewide_options;
		$scoper_sitewide_options = apply_filters( 'sitewide_options_rs' , $scoper_sitewide_options );	
	}
	
	if ( is_admin() ) {
		require_once( 'admin/admin-init_rs.php' );
		scoper_admin_init();	
	}
		
	log_mem_usage_rs( 'scoper_admin_init done' );
		
	require_once('scoped-user.php');
	require_once('role-scoper_main.php'); // ensure that is_administrator() functions are defined if $scoper is used prior to get_current_user()

	log_mem_usage_rs( 'require role-scoper_main' );
	
	if ( empty($scoper) )		// set_current_user may have already triggered scoper creation and role_cap load
		$scoper = new Scoper();

	log_mem_usage_rs( 'new Scoper done' );
		
	$scoper->init();
	
	log_mem_usage_rs( 'scoper->init() done' );
}

// called by Extension plugins if data_rs table is required
function scoper_db_setup_data_table() {
	require_once('db-setup_rs.php');
	return scoper_update_supplemental_schema('data_rs');
}

function scoper_get_init_options() {
	define ( 'SCOPER_ROLE_TYPE', scoper_get_option('role_type') );
	define ( 'SCOPER_CUSTOM_USER_BLOGCAPS', scoper_get_option('custom_user_blogcaps') );
	
	$define_groups = scoper_get_option('define_usergroups');
	define ( 'DEFINE_GROUPS_RS', $define_groups );
	define ( 'GROUP_ROLES_RS', $define_groups && scoper_get_option('enable_group_roles') );
	define ( 'USER_ROLES_RS', scoper_get_option('enable_user_roles') );
	
	if ( ! defined('DISABLE_PERSISTENT_CACHE') && ! scoper_get_option('persistent_cache') )
		define ( 'DISABLE_PERSISTENT_CACHE', true );
	
	wpp_cache_init( IS_MU_RS && scoper_establish_group_scope() );
}

function scoper_refresh_options() {
	if ( IS_MU_RS ) {
		scoper_retrieve_options(true);
		scoper_refresh_options_sitewide();
	}
		
	scoper_retrieve_options(false);
	
	scoper_refresh_default_options();
}

function scoper_set_conditional_defaults() {
	// if the WP installation has 100 or more users at initial Role Scoper installation, default to CSV input of username for role assignment	
	global $wpdb;
	$num_users = $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users" );
	if ( $num_users > 99 )
		update_option( 'scoper_user_role_assignment_csv', 1 );
}

function scoper_refresh_default_options() {
	global $scoper_default_options;

	$scoper_default_options = apply_filters( 'default_options_rs', scoper_default_options() );
	
	if ( IS_MU_RS )
		scoper_apply_custom_default_options();
}

function scoper_refresh_default_otype_options() {
	global $scoper_default_otype_options;
	
	$scoper_default_otype_options = apply_filters( 'default_otype_options_rs', scoper_default_otype_options() );
}

function scoper_get_default_otype_options() {
	if ( did_action( 'scoper_init') ) {
		global $scoper_default_otype_options;
		
		if ( ! isset( $scoper_default_otype_options ) )
			scoper_refresh_default_otype_options();
			
		return $scoper_default_otype_options;
	} else
		return scoper_default_otype_options();	
}

function scoper_delete_option( $option_basename, $sitewide = -1 ) {
	
	// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
	if ( -1 === $sitewide ) {
		global $scoper_options_sitewide;
		$sitewide = isset( $scoper_options_sitewide ) && ! empty( $scoper_options_sitewide[$option_basename] );
	}

	if ( $sitewide ) {
		global $wpdb;
		scoper_query( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = '$wpdb->siteid' AND meta_key = 'scoper_$option_basename'" );
	} else 
		delete_option( "scoper_$option_basename" );
}

function scoper_update_option( $option_basename, $option_val, $sitewide = -1 ) {
	
	// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
	if ( -1 === $sitewide ) {
		global $scoper_options_sitewide;
		$sitewide = isset( $scoper_options_sitewide ) && ! empty( $scoper_options_sitewide[$option_basename] );
	}
	
	if ( $sitewide ) {
		global $scoper_site_options;
		$scoper_site_options[$option_basename] = $option_val;
		
		//d_echo("<br /><br />sitewide: $option_basename, value '$option_val'" );
		update_site_option( "scoper_$option_basename", $option_val );
	} else {
		//d_echo("<br />blogwide: $option_basename" );
		global $scoper_blog_options;
		$scoper_blog_options[$option_basename] = $option_val;

		update_option( "scoper_$option_basename", $option_val );
	}
}

function scoper_apply_constants($stored_options) {
	// If file filtering option is on but the DISABLE constant has been set, turn the option off and regenerate .htaccess
	if ( defined( 'DISABLE_ATTACHMENT_FILTERING' ) && DISABLE_ATTACHMENT_FILTERING ) {
		if ( ! empty( $stored_options['scoper_file_filtering'] ) ) {
			// in this case, we need to both convert the option value to constant value AND trigger .htaccess regeneration
			$stored_options['file_filtering'] = 0;
			update_option( 'scoper_file_filtering', 0 );
			scoper_flush_site_rules();
			scoper_expire_file_rules();	
		}
	}

	return $stored_options; 
}

function scoper_retrieve_options( $sitewide = false ) {
	global $wpdb;
	
	if ( $sitewide ) {
		global $scoper_site_options;
		
		$scoper_site_options = array();

		if ( $results = scoper_get_results( "SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE site_id = '$wpdb->siteid' AND meta_key LIKE 'scoper_%'" ) )
			foreach ( $results as $row )
				$scoper_site_options[$row->meta_key] = $row->meta_value;
				
		$scoper_site_options = apply_filters( 'site_options_rs', $scoper_site_options );
		return $scoper_site_options;

	} else {
		global $scoper_blog_options;
		
		$scoper_blog_options = array();
		
		if ( $results = scoper_get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'scoper_%'") )
			foreach ( $results as $row )
				$scoper_blog_options[$row->option_name] = $row->option_value;
				
		$scoper_blog_options = apply_filters( 'options_rs', $scoper_blog_options );
		return $scoper_blog_options;
	}
}


function scoper_get_site_option( $option_basename ) {
	return scoper_get_option( $option_basename, true );
}

function scoper_get_option($option_basename, $sitewide = -1, $get_default = false) {
	if ( ! $get_default ) {
		// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
		if ( -1 === $sitewide ) {
			global $scoper_options_sitewide;
			$sitewide = isset( $scoper_options_sitewide ) && ! empty( $scoper_options_sitewide[$option_basename] );
		}
	
		//dump($scoper_options_sitewide);
		
		if ( $sitewide ) {
			// this option is set site-wide
			global $scoper_site_options;
			
			if ( ! isset($scoper_site_options) || is_null($scoper_site_options) )
				$scoper_site_options = scoper_retrieve_options( true );	
				
			if ( isset($scoper_site_options["scoper_{$option_basename}"]) )
				$optval = $scoper_site_options["scoper_{$option_basename}"];
			
		} else {
			//dump($option_basename);
			global $scoper_blog_options;
			
			if ( ! isset($scoper_blog_options) || is_null($scoper_blog_options) )
				$scoper_blog_options = scoper_retrieve_options( false );	
				
			if ( isset($scoper_blog_options["scoper_$option_basename"]) )
				$optval = $scoper_blog_options["scoper_$option_basename"];
		}
	}

	//dump($get_default);
	//dump($scoper_blog_options);
	
	if ( ! isset( $optval ) ) {
		global $scoper_default_options;
	
		if ( empty( $scoper_default_options ) ) {
			if ( did_action( 'scoper_init' ) )	// Make sure other plugins have had a chance to apply any filters to default options
				scoper_refresh_default_options();
			else {
				$hardcode_defaults = scoper_default_options();
				if ( isset($hardcode_defaults[$option_basename]) )
					$optval = $hardcode_defaults[$option_basename];	
			}
		}
		
		if ( ! empty($scoper_default_options) && ! empty( $scoper_default_options[$option_basename] ) )
			$optval = $scoper_default_options[$option_basename];
			
		if ( ! isset($optval) )
			return '';
	}

	return maybe_unserialize($optval);
}

function scoper_get_otype_option( $option_main_key, $src_name, $object_type = '', $access_name = '')  {
	static $otype_options;
	
	$key = "$option_main_key,$src_name,$object_type,$access_name";

	if ( empty($otype_options) )
		$otype_options = array();
	elseif ( isset($otype_options[$key]) )
		return $otype_options[$key];

	$stored_option = scoper_get_option($option_main_key);

	$default_otype_options = scoper_get_default_otype_options();
	
	// RS stores all portions of the otype option array are always set together, but blending is needed because RS Extensions or other plugins can filter the default otype options array for specific taxonomies / object types
	$optval = awp_blend_option_array( 'scoper_', $option_main_key, $default_otype_options, 1, $stored_option );
	
	// note: access_name-specific entries are not valid for most otype options (but possibly for teaser text front vs. rss)
	if ( isset ( $optval[$src_name] ) )
		$retval = $optval[$src_name];
	
	if ( $object_type && isset( $optval["$src_name:$object_type"] ) )
		$retval = $optval["$src_name:$object_type"];
	
	if ( $object_type && $access_name && isset( $optval["$src_name:$object_type:$access_name"] ) )
		$retval = $optval["$src_name:$object_type:$access_name"];
	

	// if no match was found for a source request, accept any non-empty otype match
	if ( ! $object_type && ! isset($retval) )
		foreach ( $optval as $src_otype => $val )
			if ( $val && ( 0 === strpos( $src_otype, "$src_name:" ) ) )
				$retval = $val;

	if ( ! isset($retval) )
		$retval = array();
		
	$otype_options[$key] = $retval;
	return $retval;
}

function scoper_get_role_handle($role_name, $role_type) {
	return $role_type . '_' . str_replace(' ', '_', $role_name);
}

function scoper_role_names_to_handles($role_names, $role_type, $fill_keys = false) {
	if ( ! is_array($role_names) )
		$role_names = array($role_names);	

	$role_handles = array();
	foreach ( $role_names as $role_name )
		if ( $fill_keys )
			$role_handles[ $role_type . '_' . str_replace(' ', '_', $role_name) ] = 1;
		else
			$role_handles[]= $role_type . '_' . str_replace(' ', '_', $role_name);
			
	return $role_handles;
}

function scoper_explode_role_handle($role_handle) {
	global $scoper_role_types;
	$arr = (object) array();
	
	foreach ( $scoper_role_types as $role_type ) {
		if ( 0 === strpos($role_handle, $role_type . '_') ) {
			$arr->role_type = $role_type;
			$arr->role_name = substr($role_handle, strlen($role_type) + 1);
			break;
		}
	}
	
	return $arr;
}

function scoper_role_handles_to_names($role_handles) {
	global $scoper_role_types;

	$role_names = array();
	foreach ( $role_handles as $role_handle ) {
		foreach ( $scoper_role_types as $role_type )
			$role_handle = str_replace( $role_type . '_', '', $role_handle);
			
		$role_names[] = $role_handle;
	}
	
	return $role_names;
}

function rs_notice($message) {
	require_once( 'error_rs.php' );
	awp_notice( $message, 'Role Scoper' );
}


// db wrapper methods allow us to easily avoid re-filtering our own query
function scoper_db_method($method_name, $query) {
	global $wpdb;
	//static $buffer;
	
	if ( is_admin() ) { // Low-level query filtering is necessary due to WP API limitations pertaining to admin GUI.
						// But make sure we don't chew our own cud (currently not an issue for front end)
		global $scoper_status;
	
		if ( empty($scoper_status) )
			$scoper_status = (object) array();
			
		/*
		$use_buffer = ('query' != $method_name ) && empty($_POST);
		
		if ( $use_buffer ) {
			$key = md5($query);
			if ( isset($buffer[$key]) )
				return $buffer[$key];
		}
		*/

		$scoper_status->querying_db = true;
		$results = call_user_func( array(&$wpdb, $method_name), $query );
		$scoper_status->querying_db = false;
		
		//if ( $use_buffer )
		//	$buffer[$key] = $results;
		
		return $results;
	} else
		return call_user_func( array(&$wpdb, $method_name), $query );
}

function scoper_get_results($query) {
	return scoper_db_method('get_results', $query);
}

function scoper_get_row($query) {
	return scoper_db_method('get_row', $query);
}

function scoper_get_col($query) {
	return scoper_db_method('get_col', $query);
}

function scoper_get_var($query) {
	return scoper_db_method('get_var', $query);
}

function scoper_query($query) {
	return scoper_db_method('query', $query);
}

function scoper_querying_db() {
	global $scoper_status;
	if ( isset($scoper_status) )
		return ! empty($scoper_status->querying_db);
}

function scoper_any_role_limits() {
	global $wpdb;
	
	$any_limits = (object) array( 'date_limited' => false, 'start_date_gmt' => false, 'end_date_gmt' => false, 'content_date_limited' => false, 'content_min_date_gmt' => false, 'content_max_date_gmt' => false );
	
	if ( $row = scoper_get_row( "SELECT MAX(date_limited) AS date_limited, MAX(start_date_gmt) AS start_date_gmt, MIN(end_date_gmt) AS end_date_gmt, MAX(content_date_limited) AS content_date_limited, MAX(content_min_date_gmt) AS content_min_date_gmt, MIN(content_max_date_gmt) AS content_max_date_gmt FROM $wpdb->user2role2object_rs" ) ) {
		if ( $row->date_limited ) {
			$any_limits->date_limited = true;
			
			if ( strtotime( $row->start_date_gmt ) )
				$any_limits->start_date_gmt = true;

			if ( $row->end_date_gmt != SCOPER_MAX_DATE_STRING )
				$any_limits->end_date_gmt = true;
		}
		
		if ( $row->content_date_limited ) {
			$any_limits->content_date_limited = true;
			
			if ( strtotime( $row->content_min_date_gmt ) )
				$any_limits->content_min_date_gmt = true;
				
			if ( $row->content_max_date_gmt != SCOPER_MAX_DATE_STRING )
				$any_limits->content_max_date_gmt = true;
		}
	}
	
	return $any_limits;
	
}

function scoper_get_duration_clause( $content_date_comparison = '', $table_prefix = 'uro', $enforce_duration_limits = true ) {
	static $any_role_limits;
	
	$clause = '';
	
	if ( $enforce_duration_limits && scoper_get_option( 'role_duration_limits' ) ) {
		if ( ! isset($any_role_limits) )
			$any_role_limits = scoper_any_role_limits();
		
		if ( $any_role_limits->date_limited ) {
			$current_time = current_time( 'mysql', 1 );
			
			$subclauses = array();
			
			if ( $any_role_limits->start_date_gmt )
				$subclauses []= "$table_prefix.start_date_gmt <= '$current_time'";
			
			if ( $any_role_limits->end_date_gmt )
				$subclauses []= "$table_prefix.end_date_gmt >= '$current_time'";
			
			$role_duration_clause = implode( " AND ", $subclauses );

			$clause = " AND ( $table_prefix.date_limited = '0' OR ( $role_duration_clause ) ) ";
		}
	}

	if ( $content_date_comparison && scoper_get_option( 'role_content_date_limits' ) ) {
		
		if ( ! isset($any_role_limits) )
			$any_role_limits = scoper_any_role_limits();
		
		if ( $any_role_limits->content_date_limited ) {
			$current_time = current_time( 'mysql', 1 );
			
			$subclauses = array();
			
			if ( $any_role_limits->content_min_date_gmt )
				$subclauses []= "$content_date_comparison >= $table_prefix.content_min_date_gmt";
			
			if ( $any_role_limits->content_max_date_gmt )
				$subclauses []= "$content_date_comparison <= $table_prefix.content_max_date_gmt";
			
			$content_date_clause = implode( " AND ", $subclauses );

			$clause .= " AND ( $table_prefix.content_date_limited = '0' OR ( $content_date_clause ) ) ";
		}
	}
		
	return $clause;
}

function scoper_get_property_array( &$arr, $id_prop, $buffer_prop ) {
	if ( ! is_array($arr) )
		return;

	$buffer = array();
		
	foreach ( array_keys($arr) as $key )
		$buffer[ $arr[$key]->$id_prop ] = $arr[$key]->$buffer_prop;

	return $buffer;
}

function scoper_restore_property_array( &$target_arr, $buffer_arr, $id_prop, $buffer_prop ) {
	if ( ! is_array($target_arr) || ! is_array($buffer_arr) )
		return;
		
	foreach ( array_keys($target_arr) as $key )
		if ( isset( $buffer_arr[ $target_arr[$key]->$id_prop ] ) )
			$target_arr[$key]->$buffer_prop = $buffer_arr[ $target_arr[$key]->$id_prop ];
}

if ( ! awp_ver( '2.8' ) && ! function_exists('_x') ) {
	function _x( $text, $context, $domain ) {
		return _c( "$text|$context", $domain );
	}
}

?>