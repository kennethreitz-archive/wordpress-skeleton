<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();


// thanks to Edward Dale for documenting these hooks: http://scompt.com/archives/2007/10/20/adding-custom-columns-to-the-wordpress-manage-posts-screen
add_filter('manage_posts_columns', array('ScoperEditListingFilters', 'flt_manage_posts_columns'));
add_action('manage_posts_custom_column', array('ScoperEditListingFilters', 'flt_manage_posts_custom_column'), 10, 2);

add_filter('manage_pages_columns', array('ScoperEditListingFilters', 'flt_manage_posts_columns'));
add_action('manage_pages_custom_column', array('ScoperEditListingFilters', 'flt_manage_posts_custom_column'), 10, 2);

add_action('admin_notices', array('ScoperEditListingFilters', 'act_maybe_hide_quickedit') );

class ScoperEditListingFilters {
	
	function flt_manage_posts_columns($defaults) {
		global $current_user, $scoper;

		$object_type = $scoper->data_sources->detect('type', 'post');
			
		if ( $blogwide_role_requirement = scoper_get_option( 'role_admin_blogwide_editor_only' ) ) {
			if ( ( 'admin' == $blogwide_role_requirement ) && ! is_user_administrator_rs() )
				return $defaults;
			elseif ( ( 'content_admin' == $blogwide_role_requirement ) && ! is_content_administrator_rs() )
				return $defaults;
			elseif ( $blogwide_role_requirement ) {
				$cap_req = "edit_others_{$object_type}s";

				if ( empty( $current_user->allcaps[$cap_req] ) )
					return $defaults;	
			}
		}
		
		$use_object_roles = scoper_get_otype_option('use_object_roles', 'post', $object_type);
		
		$use_term_roles = scoper_get_otype_option('use_term_roles', 'post', $object_type);
			
		if ( 'rs' == SCOPER_ROLE_TYPE ) {
			if ( ( $use_term_roles && ! empty($scoper->any_restricted_terms) ) || ( $use_object_roles && ! empty($scoper->any_restricted_objects) ) )
				if ( scoper_get_otype_option('restrictions_column', 'post', $object_type) )
					$defaults['restricted'] = __('Restrict', 'scoper');
		}
		
		if ( ! empty($scoper->have_termrole_ids['post']) )
			if ( scoper_get_otype_option('term_roles_column', 'post', $object_type) )
				$defaults['termroles'] = __('Term Roles', 'scoper');
			
		if ( $use_object_roles && ! empty($scoper->have_objrole_ids['post']) )
			if ( scoper_get_otype_option('object_roles_column', 'post', $object_type) ) {
				$otype_display_name = $scoper->data_sources->member_property('post', 'object_types', $object_type, 'display_name');
				//$defaults['objroles'] = sprintf( _ x('%s Roles', 'Post or Page', 'scoper'), $otype_display_name);
				$defaults['objroles'] = sprintf( __('%s Roles', 'scoper'), $otype_display_name);
			}	
				
		return $defaults;
	}
	
	function flt_manage_posts_custom_column($column_name, $id) {
		global $scoper, $posts;

		switch ( $column_name ) {
			case 'restricted':
				$restricted_ops = array();
				if ( isset($scoper->objscoped_ids['post'][$id]['read']) )
					$restricted_ops []= '<strong>' . __('Read', 'scoper') . '</strong>';
				elseif ( isset($scoper->termscoped_ids['post'][$id]['read']) )
					$restricted_ops []= __('Read', 'scoper');
				
				if ( isset($scoper->objscoped_ids['post'][$id]['edit']) )
					$restricted_ops []= '<strong>' . __awp('Edit') . '</strong>';
				elseif ( isset($scoper->termscoped_ids['post'][$id]['edit']) )
					$restricted_ops []= __awp('Edit');
				
				if ( $restricted_ops )
					echo implode(", ", $restricted_ops);
					
				break;
				
			case 'termroles':
				$role_names = array();
			
				if ( isset($scoper->have_termrole_ids['post'][$id]) ) {
					foreach ( array_keys($scoper->have_termrole_ids['post'][$id]) as $role_handle)
						if ( 'rs' == SCOPER_ROLE_TYPE )
							$role_names []= str_replace( ' ', '&nbsp;', $scoper->role_defs->get_micro_abbrev($role_handle) );
						else
							$role_names []= str_replace( ' ', '&nbsp;', $scoper->role_defs->get_abbrev($role_handle) ); 
						
					sort($role_names);
					echo implode(", ", $role_names);
				}
				break;
				
			case 'objroles':
				$role_names = array();
			
				if ( isset($scoper->have_objrole_ids['post'][$id]) ) {
					foreach ( array_keys($scoper->have_objrole_ids['post'][$id]) as $role_handle)
						$role_names []= str_replace( ' ', '&nbsp;', $scoper->role_defs->get_micro_abbrev($role_handle, OBJECT_UI_RS) );

					sort($role_names);
					echo implode(", ", $role_names);
				}
				break;
		}
	}
	
	function act_maybe_hide_quickedit() {
		global $current_user, $scoper;

		$reqd_cap = ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/edit-pages.php') ) ? array('edit_others_pages', 'edit_published_pages') : array('edit_others_posts', 'edit_published_posts');

		if ( is_content_administrator_rs() )
			return;
		
		$qualifying_roles = $scoper->role_defs->qualify_roles($reqd_cap);
		if ( ! array_intersect_key($qualifying_roles, $current_user->blog_roles[ANY_CONTENT_DATE_RS]) )
			echo "<div id='rs_hide_quickedit'></div>";
	}

} // end class
?>