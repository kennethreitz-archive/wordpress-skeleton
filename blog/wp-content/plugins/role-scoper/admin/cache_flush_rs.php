<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// flush caches that are potentially affected by ANY role assignment
function scoper_flush_results_cache( $role_bases = '', $user_ids = '' ) {
	global $scoper_role_types;
	
	$wp_cache_flags = array();
	$wp_cache_base_flags = array( 'get_pages', 'get_bookmarks', 'get_terms', 'scoper_get_terms' );
	
	foreach ($scoper_role_types as $role_type) {
		wpp_cache_flush_group("{$role_type}_users_who_can");
		wpp_cache_flush_group("{$role_type}_groups_who_can");

		foreach($wp_cache_base_flags as $base_flag)
			$wp_cache_flags []= $role_type . '_' . $base_flag;
	}
	
	$wp_cache_flags []= 'usergroups';
	
	if ( empty($role_bases) ) {
		$role_bases = array();
		$role_bases []= ROLE_BASIS_USER;
		$role_bases []= ROLE_BASIS_GROUPS;
		$role_bases []= ROLE_BASIS_USER_AND_GROUPS;
	}
	
	if ( ! is_array($role_bases) )
		$role_bases = (array) $role_bases;
		
	foreach ( $role_bases as $role_basis ) {
		if ( ROLE_BASIS_GROUPS == $role_basis ) {
			foreach($wp_cache_flags as $cache_flag)
				wpp_cache_flush_group("{$cache_flag}_for_groups");
		} else {
			if ( $user_ids ) {
				if ( ! is_array( $user_ids) )
					$user_ids = array($user_ids);
				
				foreach($wp_cache_flags as $cache_flag)
					foreach ( $user_ids as $user_id )
						wpp_cache_delete($user_id, "{$cache_flag}_for_{$role_basis}");
			} else {
				foreach($wp_cache_flags as $cache_flag)
					wpp_cache_flush_group("{$cache_flag}_for_{$role_basis}");
			}
		}
	}
}

// flush role assignment caches - separate caches for each role basis
function scoper_flush_roles_cache( $scope, $role_bases = '', $user_ids = '', $taxonomies = '' ) {
	global $scoper_role_types;
	
	if ( OBJECT_SCOPE_RS == $scope )
		foreach ($scoper_role_types as $role_type)
			// this cache stores roles which have been applied for any user or group (currently only uses 'all' key)
			wpp_cache_flush_group("{$role_type}_applied_object_roles");
	
	if ( $user_ids && ! is_array( $user_ids) )
		$user_ids = array($user_ids);
	
	if ( empty($role_bases) ) {
		$role_bases = array();
		$role_bases []= ROLE_BASIS_USER;
		$role_bases []= ROLE_BASIS_GROUPS;
		$role_bases []= ROLE_BASIS_USER_AND_GROUPS;
	}
	
	if ( ! is_array($role_bases) )
		$role_bases = (array) $role_bases;
		
	foreach ( $role_bases as $role_basis ) {
		if ( TERM_SCOPE_RS == $scope ) {
			if ( ! $taxonomies ) {
				global $scoper;
				if ( ! empty($scoper->taxonomies) )
					$taxonomies = $scoper->taxonomies->get_all_keys();
					
				if ( ! $taxonomies ) {
					update_option( 'scoper_need_cache_flush', true ); // if taxonomies could not be determined, invalidate entire cache
					return;
				}
			}
			
			if ( ! is_array( $taxonomies) )
				$taxonomies = array($taxonomies);
	
			if ( $user_ids ) {
				foreach ($scoper_role_types as $role_type)
					foreach ( $user_ids as $user_id )
						foreach ($taxonomies as $taxonomy)
							wpp_cache_delete($user_id, "{$role_type}_term-roles_{$taxonomy}_for_{$role_basis}");
			} else {
				foreach ($scoper_role_types as $role_type)
					foreach ($taxonomies as $taxonomy)
						wpp_cache_flush_group("{$role_type}_term-roles_{$taxonomy}_for_{$role_basis}");
			}
		
		} else {
			if ( $user_ids ) {
				foreach ($scoper_role_types as $role_type)
					foreach ( $user_ids as $user_id )
						wpp_cache_delete($user_id, "{$role_type}_{$scope}-roles_for_{$role_basis}");
			} else
				foreach ($scoper_role_types as $role_type)
					wpp_cache_flush_group("{$role_type}_{$scope}-roles_for_{$role_basis}");
		}
	}
}	

function scoper_flush_restriction_cache( $scope, $src_or_tx_name = '' ) {
	global $scoper_role_types;
	
	if ( OBJECT_SCOPE_RS == $scope ) {
		if ( ! $src_or_tx_name ) {
			global $scoper;
			if ( ! empty($scoper->data_sources) )
				$src_or_tx_name = $scoper->data_sources->get_all_keys();
		}

	} elseif ( TERM_SCOPE_RS == $scope ) {
		if ( ! $src_or_tx_name ) {
			global $scoper;
			if ( ! empty($scoper->taxonomies) )
				$src_or_tx_name = $scoper->taxonomies->get_all_keys();
		}
	}

	if ( ! $src_or_tx_name ) {
		update_option( 'scoper_need_cache_flush', true ); // if taxonomies / data sources could not be determined, invalidate entire cache
		return;
	}
	
	$names = (array) $src_or_tx_name;	
	
	foreach ($scoper_role_types as $role_type)
		foreach ($names as $src_or_tx_name)
			wpp_cache_flush_group("{$role_type}_{$scope}_restrictions_{$src_or_tx_name}");

	foreach ($scoper_role_types as $role_type)
		wpp_cache_flush_group( "{$role_type}_{$scope}_def_restrictions" );
}

?>