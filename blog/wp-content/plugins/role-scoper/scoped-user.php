<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
require_once('db-config_rs.php');
	
/**
 * WP_Scoped_User PHP class for the WordPress plugin Role Scoper
 * role-scoper.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
if ( ! class_exists('WP_Scoped_User') ) {
class WP_Scoped_User extends WP_User {
	// note: these arrays are flipped (data stored in key) for better searching performance
	var $groups = array(); 				// 	$groups[group id] = 1
	var $blog_roles = array(); 			//  $blog_roles[date_key][role_handle] = 1
	var $term_roles = array();			//	$term_roles[taxonomy][date_key][role_handle] = array of term ids 
	var $assigned_blog_roles = array(); //  $assigned_blog_roles[role_handle] = 1
	var $assigned_term_roles = array();	//	$assigned_term_roles[taxonomy][role_handle] = array of term ids 
	var $qualified_terms = array();		//  $qualified_terms[taxonomy][$capreqs_key] = previous result for qualify_terms call on this set of capreqs
	
	function WP_Scoped_User($id = 0, $name = '', $args = '') {
		//log_mem_usage_rs( 'begin WP_Scoped_User' );
		
		$this->WP_User($id, $name);
		
		// initialize blog_roles arrays
		$this->assigned_blog_roles[ANY_CONTENT_DATE_RS] = array();
		$this->blog_roles[ANY_CONTENT_DATE_RS] = array();

		//dump($id);
		//dump($this);
		
		//log_mem_usage_rs( 'called this->WP_User' );
		
		$defaults = array( 'disable_user_roles' => false, 'disable_group_roles' => false, 'disable_wp_roles' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $scoper;
		
		if ( empty($scoper) || empty($scoper->role_defs) ) {
			require_once('role-scoper_main.php');
			
			//log_mem_usage_rs( 'Scoped User: require role-scoper_main.php' );
			
			// todo: review this
			//$temp = new Scoper();
			//$scoper =& $temp;
			
			$scoper = new Scoper();

			//log_mem_usage_rs( 'Scoped User: new Scoper' );
		}
		
		if ( $this->ID ) {
			if ( ! $disable_wp_roles ) {
				// include both WP roles and custom caps, which are treated as a hidden single-cap role capable of satisfying single-cap current_user_can calls
				$this->assigned_blog_roles[ANY_CONTENT_DATE_RS] = $this->caps;
			
				// prepend role_type prefix to wp rolenames
				global $wp_roles;
				foreach ( array_keys($this->assigned_blog_roles[ANY_CONTENT_DATE_RS]) as $name) {
					if ( isset($wp_roles->role_objects[$name]) ) {
						$this->assigned_blog_roles[ANY_CONTENT_DATE_RS]['wp_' . $name] = $this->assigned_blog_roles[ANY_CONTENT_DATE_RS][$name];
						unset($this->assigned_blog_roles[ANY_CONTENT_DATE_RS][$name]);
					}
				}
			}
			
			if ( defined('DEFINE_GROUPS_RS') && ! $disable_group_roles ) {
				$this->groups = $this->_get_usergroups();

				if ( ! empty($args['filter_usergroups']) )  // assist group admin
					$this->groups = array_intersect_key($this->groups, $args['filter_usergroups']);
			}
			
			if ( 'rs' == SCOPER_ROLE_TYPE ) { // && RS_BLOG_ROLES ) {  // rs_blog_roles option has never been active in any RS release; leave commented here in case need arises
				if ( $rs_blogroles = $this->get_blog_roles_daterange( SCOPER_ROLE_TYPE ) ) {
					foreach ( array_keys($rs_blogroles) as $date_key ) {
						if ( isset($this->assigned_blog_roles[$date_key]) )
							$this->assigned_blog_roles[$date_key] = array_merge($this->assigned_blog_roles[$date_key], $rs_blogroles[$date_key]);
						else
							$this->assigned_blog_roles[$date_key] = $rs_blogroles[$date_key];
					}
				}

				$this->merge_scoped_blogcaps();
			}
			
			foreach ( array_keys($this->assigned_blog_roles) as $date_key )
				$this->blog_roles[$date_key] = $scoper->role_defs->add_contained_roles( $this->assigned_blog_roles[$date_key] );
			
			// note: The allcaps property still governs current_user_can calls when the cap requirements do not pertain to a specific object.
			// If WP roles fail to provide all required caps, the Role Scoper has_cap filter validate the current_user_can check 
			// if any RS blogrole has all the required caps.
			//
			// The blog_roles array also comes into play for object permission checks such as page or post listing / edit.  
			// In such cases, roles in the Scoper_User::blog_roles array supplement any pertinent taxonomy or role assignments,
			// as long as the object or its terms are not configured to require that role to be term-assigned or object-assigned.
			
			//log_mem_usage_rs( 'new Scoped User done' );
		}
	}
	
	function check_for_user_roles() {
		if ( IS_MU_RS )
			return true;	// this function is only for performance; not currently dealing with multiple uro tables
		
		global $wpdb;
		
		$role_type = SCOPER_ROLE_TYPE;
		return scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE role_type = '$role_type' AND user_id = '$this->ID' LIMIT 1");
	}
	
	function get_user_clause($table_alias) {
		$table_alias = ( $table_alias ) ? "$table_alias." : '';
		
		$arr = array();
		
		if ( GROUP_ROLES_RS && $this->groups )
			$arr []= "{$table_alias}group_id IN ('" . implode("', '", array_keys($this->groups) ) . "')";
		
		if ( USER_ROLES_RS || empty($arr) ) // too risky to allow query with no user or group clause
			$arr []= "{$table_alias}user_id = '$this->ID'";
			
		$clause = implode( ' OR ', $arr );
		
		if ( count($arr) > 1 )
			$clause = "( $clause )";
		
		if ( $clause )
			return " AND $clause";
	}
	
	function cache_get($cache_flag, $append_blog_suffix = true ) {
		if ( GROUP_ROLES_RS && $this->groups ) {
			$cache_id = $this->ID;	
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER_AND_GROUPS;
		} else {
			$cache_id = $this->ID;
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER;
		}
		
		return wpp_cache_get($cache_id, $cache_flag, $append_blog_suffix);
	}
	
	function cache_set($entry, $cache_flag, $append_blog_suffix = true ) {
		if ( GROUP_ROLES_RS && $this->groups ) {
			$cache_id = $this->ID;	
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER_AND_GROUPS;
		} else {
			$cache_id = $this->ID;
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER;
		}
		
		return wpp_cache_set($cache_id, $entry, $cache_flag, $append_blog_suffix);
	}
		

	// can be called statically by external modules
	function get_groups_for_user( $user_id, $args = '' ) {
		if ( empty($args['no_cache']) ) {
			$cache = wpp_cache_get($user_id, 'group_membership_for_user');
			if ( is_array($cache) )
				return $cache;
		}

		global $wpdb;
		
		if ( ! $wpdb->user2group_rs )
			return array();

		$query = "SELECT $wpdb->user2group_gid_col FROM $wpdb->user2group_rs WHERE $wpdb->user2group_uid_col = '$user_id' ORDER BY $wpdb->user2group_gid_col";
		if ( ! $user_groups = scoper_get_col($query) )
			$user_groups = array();
		
		// include WP metagroup(s) for WP blogrole(s)
		$metagroup_ids = array();
		if ( ! empty($args['metagroup_roles']) ) {
			foreach ( array_keys($args['metagroup_roles']) as $role_handle )
				$metagroup_ids []= 'wp_role_' . str_replace( 'wp_', '', $role_handle );
		}
		
		if ( $metagroup_ids ) {
			$meta_id_in = "'" . implode("', '", $metagroup_ids) . "'";

			$query = "SELECT $wpdb->groups_id_col FROM $wpdb->groups_rs"
			. " WHERE {$wpdb->groups_rs}.{$wpdb->groups_meta_id_col} IN ($meta_id_in)"
			. " ORDER BY $wpdb->groups_id_col";
		
			if ( $meta_groups = scoper_get_col($query) )
				$user_groups = array_merge( $user_groups, $meta_groups );
		}
	
		if ( $user_groups && empty($args['no_cache']) ) {  // users should always be in at least a metagroup.  Problem with caching empty result on user creation beginning with WP 2.8
			$user_groups = array_fill_keys($user_groups, 1);

			wpp_cache_set($user_id, $user_groups, 'group_membership_for_user');
		}
	
		return $user_groups;
	}
	
	// return group_id as array keys
	function _get_usergroups($args = '') {
		if ( ! $this->ID )
			return array();
		
		if ( ! is_array($args) )
			$args = array();
		
		if ( ! empty($this->assigned_blog_roles) )
			$args['metagroup_roles'] = $this->assigned_blog_roles[ANY_CONTENT_DATE_RS];

		$user_groups = WP_Scoped_User::get_groups_for_user( $this->ID, $args );
		
		return $user_groups;
	}
	
	// wrapper for back compat with callin code that does not expect date_key dimension
	function get_blog_roles( $role_type = 'rs' ) {
		$blog_roles = $this->get_blog_roles_daterange( $role_type );
		
		if ( isset($blog_roles[ANY_CONTENT_DATE_RS]) && is_array($blog_roles[ANY_CONTENT_DATE_RS]) )
			return $blog_roles[ANY_CONTENT_DATE_RS];
		else
			return array();
	}
	
	function get_blog_roles_daterange( $role_type = 'rs', $args = '' ) {
		$defaults = array( 'enforce_duration_limits' => true, 'retrieve_content_date_limits' => true, 'include_role_duration_key' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		if ( $enforce_duration_limits && $retrieve_content_date_limits && ! $include_role_duration_key ) {
			$cache_flag = "{$role_type}_blog-roles";		// changed cache key from "blog_roles" to "blog-roles" to prevent retrieval of arrays stored without date_key dimension
			$cache = $this->cache_get( $cache_flag );
			if ( is_array($cache) )
				return $cache;
		}

		global $wpdb;
		
		$u_g_clause = $this->get_user_clause('uro');
		
		$duration_clause = ( $enforce_duration_limits ) ? scoper_get_duration_clause() : '';
		
		$extra_cols = ( $include_role_duration_key ) ? ", uro.date_limited, uro.start_date_gmt, uro.end_date_gmt" : '';
		
		$qry = "SELECT uro.role_name, uro.content_date_limited, uro.content_min_date_gmt, uro.content_max_date_gmt $extra_cols FROM $wpdb->user2role2object_rs AS uro WHERE uro.scope = 'blog' AND uro.role_type = '$role_type' $duration_clause $u_g_clause";
		$results =  scoper_get_results($qry);

		$role_handles = array( '' => array() );
		
		foreach ( $results as $row ) {
			$date_key = ( $retrieve_content_date_limits && $row->content_date_limited ) ? serialize( (object) array( 'content_min_date_gmt' => $row->content_min_date_gmt, 'content_max_date_gmt' => $row->content_max_date_gmt ) ) : '';	
			
			if ( $include_role_duration_key ) {
				$role_duration_key = ( $row->date_limited ) ? serialize( (object) array( 'start_date_gmt' => $row->start_date_gmt, 'end_date_gmt' => $row->end_date_gmt ) ) : '';
				$role_handles[$role_duration_key][$date_key] [ scoper_get_role_handle( $row->role_name, $role_type ) ] = true;
			} else
				$role_handles[$date_key] [ scoper_get_role_handle( $row->role_name, $role_type ) ] = true;
		}

		if ( $enforce_duration_limits && $retrieve_content_date_limits && ! $include_role_duration_key )
			$this->cache_set($role_handles, $cache_flag);
		
		return $role_handles;
	}
	
	// wrapper for back compat with callin code that does not expect date_key dimension
	function get_term_roles( $taxonomy = 'category', $role_type = 'rs' ) {
		$term_roles = $this->get_term_roles_daterange( $taxonomy, $role_type );
		
		if ( isset($term_roles[ANY_CONTENT_DATE_RS]) && is_array($term_roles[ANY_CONTENT_DATE_RS]) )
			return $term_roles[ANY_CONTENT_DATE_RS];
		else
			return array();
	}

	// returns array[role name] = array of term ids for which user has the role assigned (based on current role basis)
	function get_term_roles_daterange( $taxonomy = 'category', $role_type = 'rs', $args = '' ) {
		$defaults = array( 'enforce_duration_limits' => true, 'retrieve_content_date_limits' => true, 'include_role_duration_key' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $wpdb;
		
		if ( $enforce_duration_limits && $retrieve_content_date_limits && ! $include_role_duration_key ) {
			$cache_flag = "{$role_type}_term-roles_{$taxonomy}";	// changed cache key from "term_roles" to "term-roles" to prevent retrieval of arrays stored without date_key dimension
		
			$tx_term_roles = $this->cache_get($cache_flag);
		} else 
			$tx_term_roles = '';
			
		if ( ! is_array($tx_term_roles) ) {
			// no need to check for this on cache retrieval, since a role_type change results in a rol_defs change, which triggers a full scoper cache flush
			$role_type = SCOPER_ROLE_TYPE;
			
			$tx_term_roles = array( '' => array() );
			
			$duration_clause = ( $enforce_duration_limits ) ? scoper_get_duration_clause() : '';
			
			$u_g_clause = $this->get_user_clause('uro');

			$extra_cols = ( $include_role_duration_key ) ? ", uro.date_limited, uro.start_date_gmt, uro.end_date_gmt" : '';
			
			$qry = "SELECT uro.obj_or_term_id, uro.role_name, uro.assignment_id, uro.content_date_limited, uro.content_min_date_gmt, uro.content_max_date_gmt $extra_cols FROM $wpdb->user2role2object_rs AS uro ";
			$qry .= "WHERE uro.scope = 'term' AND uro.assign_for IN ('entity', 'both') AND uro.role_type = '$role_type' AND uro.src_or_tx_name = '$taxonomy' $duration_clause $u_g_clause";
							
			if ( $results = scoper_get_results($qry) ) {
				foreach($results as $termrole) {
					$date_key = ( $retrieve_content_date_limits && $termrole->content_date_limited ) ? serialize( (object) array( 'content_min_date_gmt' => $termrole->content_min_date_gmt, 'content_max_date_gmt' => $termrole->content_max_date_gmt ) ) : '';
					
					$role_handle = SCOPER_ROLE_TYPE . '_' . $termrole->role_name;
					
					if ( $include_role_duration_key ) {
						$role_duration_key = ( $termrole->date_limited ) ? serialize( (object) array( 'start_date_gmt' => $termrole->start_date_gmt, 'end_date_gmt' => $termrole->end_date_gmt ) ) : '';
						$tx_term_roles[$role_duration_key][$date_key][$role_handle][] = $termrole->obj_or_term_id;
					} else
						$tx_term_roles[$date_key][$role_handle][] = $termrole->obj_or_term_id;
				}
			}
			
			if ( $enforce_duration_limits && $retrieve_content_date_limits && ! $include_role_duration_key )
				$this->cache_set($tx_term_roles, $cache_flag);
		}
		
		if ( $enforce_duration_limits && $retrieve_content_date_limits && ! $include_role_duration_key ) {
			$this->assigned_term_roles[$taxonomy] = $tx_term_roles;
		
			global $scoper;
			foreach( array_keys($this->assigned_term_roles[$taxonomy]) as $date_key )
				$this->term_roles[$taxonomy][$date_key] = $scoper->role_defs->add_contained_roles( $this->assigned_term_roles[$taxonomy][$date_key], true );  //arg: is term array
		}
				
		return $tx_term_roles;
	}
	
	
	function merge_scoped_blogcaps() {	
		global $scoper;
					
		foreach( array_keys($this->assigned_blog_roles[ANY_CONTENT_DATE_RS]) as $role_handle ) {
			$role_spec = scoper_explode_role_handle($role_handle);
			
			if ( ! empty($role_spec->role_type) && ( 'rs' == $role_spec->role_type ) && $scoper->role_defs->is_member($role_handle) )
				$this->allcaps = array_merge($this->allcaps, $scoper->role_defs->role_caps[$role_handle]);
		}
		
		$this->allcaps['is_scoped_user'] = true; // use this to detect when something tampers with scoped allcaps array
	}
} // end class WP_Scoped_User
}

if ( ! function_exists('is_administrator_rs') ) {
function is_administrator_rs( $src_or_tx = '', $admin_type = 'content', $user = '' ) {
	if ( ! $user ) {
		global $current_user;
		$user = $current_user;
		
		if ( IS_MU_RS && function_exists('is_site_admin') && is_site_admin() )
			return true;
	}

	if ( empty($user->ID) )
		return false;
		
	$return = '';

	$admin_cap_name = scoper_get_administrator_cap( $admin_type );
	$return = ! empty( $user->allcaps[$admin_cap_name] );
	
	if ( ! $return && $src_or_tx ) {	
		// user is not a universal administrator, but are they an administrator for the specified source / taxonomy ?
		
		if ( ! is_object($src_or_tx) ) {
			global $scoper;
			if ( ! $obj = $scoper->data_sources->get($src_or_tx) )
				$obj = $scoper->taxonomies->get($src_or_tx);
				
			if ( $obj )
				$src_or_tx = $obj;
		}
		
		if ( ! in_array( $src_or_tx->name, array( 'post', 'category', 'term', 'link', 'group' ) ) ) {
		
			if ( ! empty($src_or_tx->defining_module_name) ) {
				$defining_module_name = $src_or_tx->defining_module_name;
				if ( ('wordpress' != $defining_module_name) && ('role-scoper' != $defining_module_name) ) {
					static $admin_caps;
					
					if ( ! isset($admin_caps) )
						$admin_caps = apply_filters( 'define_administrator_caps_rs', array() );
		
					if ( ! empty( $admin_caps[$defining_module_name] ) ) {
						$module_admin_cap = $admin_caps[$defining_module_name];
						$return = ! empty( $user->allcaps[$module_admin_cap] );
					}
				}
			}
		}
	}
	
	return $return;
}

function is_option_administrator_rs( $user = '' ) {
	return is_administrator_rs( '', 'option', $user );
}

function is_user_administrator_rs( $user = '' ) {
	return is_administrator_rs( '', 'user', $user );
} 

function is_content_administrator_rs( $user = '' ) {
	return is_administrator_rs( '', 'content', $user );
}

function scoper_get_administrator_cap( $admin_type ) {
	if ( ! $admin_type )
		$admin_type = 'content';
	
	// Note: to differentiate content administrator role, define a custom cap such as "administer_all_content", add it to a custom Role, and add the following line to wp-config.php: define( 'SCOPER_CONTENT_ADMIN_CAP', 'cap_name' );
	$default_cap = array( 'option' => 'manage_options', 'user' => 'edit_users', 'content' => 'activate_plugins' );

	$constant_name = 'SCOPER_' . strtoupper($admin_type) . '_ADMIN_CAP';
	$cap_name = ( defined( $constant_name ) ) ? constant( $constant_name ) : $default_cap[$admin_type];
	
	if ( 'read' == $cap_name )	// avoid catostrophic mistakes
		$cap_name = $default_cap[$admin_type];
		
	return $cap_name;
} 

} // endif is_administrator_rs function not defined

?>