<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );


function scoper_version_updated( $prev_version ) {
		
	if ( function_exists( 'wpp_cache_flush' ) )
		wpp_cache_flush();
		
	// single-pass do loop to easily skip unnecessary version checks
	do {
		if ( version_compare( $prev_version, '1.1', '<') ) {
			// htaccess rules modified in v1.1
			scoper_flush_site_rules();
			scoper_expire_file_rules();
			
			// Option update did not set autoload to no prior to 1.1
			global $wpdb;
			$wpdb->query( "UPDATE $wpdb->options SET autoload = 'no' WHERE option_name LIKE 'scoper_%' AND option_name != 'scoper_version'" );
			
			// stopped storing needless postmeta data for parent=0 in 1.1
			global $wpdb;
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_value = '0' AND meta_key = '_scoper_last_parent'" );
		} else break;
		
		// stopped using rs_get_page_children() in 1.0.8
		if ( version_compare( $prev_version, '1.0.8', '<') ) {
			delete_option('scoper_page_children');
		} else break;
		
		if ( version_compare( $prev_version, '1.0.0-rc6', '<') && version_compare( $prev_version, '1.0.0-rc2', '>=') ) {
			// In rc2 through rc4, we forced invalid img src attribute for image attachments on servers deemed non-apache
			// note: false === stripos( php_sapi_name(), 'apache' ) was the criteria used by the offending code
			// Need to update all affected post_content to convert attachment_id URL to file URL
			if ( false === stripos( php_sapi_name(), 'apache' ) && ! get_site_option('scoper_fixed_img_urls') ) {
				global $wpdb, $wp_rewrite;

				if ( ! empty($wp_rewrite) ) {
					$blog_url = get_bloginfo('url');
					if ( $results = $wpdb->get_results( "SELECT ID, guid, post_parent FROM $wpdb->posts WHERE post_type = 'attachment' && post_date > '2008-12-7'" ) ) {
						foreach ( $results as $row ) {
							$data = array();
							$data['post_content'] = $wpdb->get_var( "SELECT post_content FROM $wpdb->posts WHERE ID = '$row->post_parent'" );
							
							if ( $row->guid ) {
								$attachment_link_raw = $blog_url . "/?attachment_id={$row->ID}";
								$data['post_content'] = str_replace('src="' . $attachment_link_raw, 'src="' . $row->guid, $data['post_content']);
								
								$attachment_link = get_attachment_link($row->ID);
								$data['post_content'] = str_replace('src="' . $attachment_link, 'src="' . $row->guid, $data['post_content']);
							}
	
							if ( ! empty($data['post_content']) ) {
								$wpdb->update($wpdb->posts, $data, array("ID" => $row->post_parent) );
							}
						}
					}
				
					update_option('scoper_fixed_img_urls', true);
				}
			}
		} else break;

		
		// fixed failure to properly maintain scoper_page_ancestors options in 1.0.0-rc5
		if ( version_compare( $prev_version, '1.0.0-rc5', '<') ) {
			delete_option('scoper_page_ancestors');
		} else break;

		
		// changed default teaser_hide_private otype option to separate entries for posts, pages in v1.0.0-rc4
		if ( version_compare( $prev_version, '1.0.0-rc4', '<') ) {
			$teaser_hide_private = get_option('scoper_teaser_hide_private');

			if ( isset($teaser_hide_private['post']) && ! is_array($teaser_hide_private['post']) ) {
				if ( $teaser_hide_private['post'] )
					// despite "for posts and pages" caption, previously this option caused pages to be hidden but posts still teased
					update_option( 'scoper_teaser_hide_private', array( 'post:post' => 0, 'post:page' => 1 ) );
				else
					update_option( 'scoper_teaser_hide_private', array( 'post:post' => 0, 'post:page' => 0 ) );
			}
		} else break;
		
		// 0.9.15 eliminated ability to set recursive page parents
		if ( version_compare( $prev_version, '0.9.15', '<') ) { 
			scoper_fix_page_parent_recursion();
		} else break;
		
		
		// added WP role metagroups in v0.9.9
		if ( ( ! empty($prev_version) && version_compare( $prev_version, '0.9.9', '<') ) ) {
			global $wp_roles;
			
			if ( ! empty($wp_roles) )
				scoper_sync_wproles();
				
		} else break;
	
	} while ( 0 ); // end single-pass version check loop
}
		

function scoper_sync_wproles($user_ids = '', $role_name_arg = '', $blog_id_arg = '' ) {
	global $wpdb, $wp_roles;
	
	if ( $user_ids && ( ! is_array($user_ids) ) )
		$user_ids = array($user_ids);
	
	if ( empty($wp_roles->role_objects) )
		return;
	
	$wp_rolenames = array_keys($wp_roles->role_objects);

	$uro_table = ( $blog_id_arg ) ? $wpdb->base_prefix . $blog_id_arg . '_' . 'user2role2object_rs' : $wpdb->user2role2object_rs;

	$groups_table = $wpdb->groups_rs;
	$user2group_table = $wpdb->user2group_rs;
	
	// Delete any role entries for WP roles which were deleted or renamed while Role Scoper was deactivated
	// (users will be re-synched to new role name)
	$name_in = "'" . implode("', '", $wp_rolenames) . "'";
	$qry = "DELETE FROM $uro_table WHERE role_type = 'wp' AND scope = 'blog' AND role_name NOT IN ($name_in)";
	scoper_query($qry);
	
	// also sync WP Role metagroups
	if ( ! empty($user_ids) )
		foreach ( $user_ids as $user_id )
			wpp_cache_delete( $user_id, 'group_membership_for_user' );
	
	$metagroup_ids = array();
	$metagroup_names = array();
	$metagroup_descripts = array();
	foreach ( $wp_rolenames as $role_name ) {
		$metagroup_id = "wp_role_" . trim(substr($role_name, 0, 40));
		
		// if the name is too long and its truncated ID already taken, just exclude it from eligible metagroups
		if ( in_array( $metagroup_id, $metagroup_ids ) )
			continue;

		$metagroup_ids []= $metagroup_id;
		$metagroup_names [ "wp_role_{$role_name}" ] = sprintf( '[WP %s]', $role_name );
		$metagroup_descripts[ "wp_role_{$role_name}" ] = sprintf( 'All users with the WordPress %s blog role', $role_name );
	}

	// add a metagroup for anonymous users
	$metagroup_ids []= "wp_anon";
	$metagroup_names [ "wp_anon" ] = '[Anonymous]';
	$metagroup_descripts[ "wp_anon" ] = 'Anonymous users (not logged in)';

	// add a metagroup for pending revision e-mail notification recipients
	$metagroup_ids []= "rv_pending_rev_notice_ed_nr_";
	$metagroup_names [ "rv_pending_rev_notice_ed_nr_" ] = '[Pending Revision Monitors]';
	$metagroup_descripts[ "rv_pending_rev_notice_ed_nr_" ] = 'Administrators / Publishers to notify (by default) of pending revisions';
	
	// add a metagroup for pending revision e-mail notification recipients
	$metagroup_ids []= "rv_scheduled_rev_notice_ed_nr_";
	$metagroup_names [ "rv_scheduled_rev_notice_ed_nr_" ] = '[Scheduled Revision Monitors]';
	$metagroup_descripts[ "rv_scheduled_rev_notice_ed_nr_" ] = 'Administrators / Publishers to notify when any scheduled revision is published';
	
	$stored_metagroup_ids = array();
	$qry = "SELECT $wpdb->groups_meta_id_col, $wpdb->groups_id_col, $wpdb->groups_name_col FROM $groups_table WHERE NOT ISNULL($wpdb->groups_meta_id_col) AND ( $wpdb->groups_meta_id_col != '' )"; // LIKE 'wp_%'";
	if ( $results = scoper_get_results($qry) ) {
		//rs_errlog("metagroup results: " . serialize($stored_metagroup_ids)');
	
		$delete_metagroup_ids = array();
		$update_metagroup_ids = array();
				
		foreach ( $results as $row ) {
			if ( ! in_array( $row->{$wpdb->groups_meta_id_col}, $metagroup_ids ) )
				$delete_metagroup_ids []= $row->{$wpdb->groups_id_col};
			else {
				$stored_metagroup_ids []= $row->{$wpdb->groups_meta_id_col};
				
				if ( $row->{$wpdb->groups_name_col} != $metagroup_names[$row->{$wpdb->groups_meta_id_col}] )
					$update_metagroup_ids[] = $row->{$wpdb->groups_meta_id_col};
			}
		}
		
		if ( $delete_metagroup_ids ) {
			$id_in = "'" . implode("', '", $delete_metagroup_ids) . "'";
			scoper_query( "DELETE FROM $groups_table WHERE $wpdb->groups_id_col IN ($id_in)" );
		}
		
		if ( $update_metagroup_ids ) {
			foreach ( $update_metagroup_ids as $metagroup_id ) {
				if ( $metagroup_id )
					scoper_query( "UPDATE $groups_table SET $wpdb->groups_name_col = '$metagroup_names[$metagroup_id]', $wpdb->groups_descript_col = '$metagroup_descripts[$metagroup_id]' WHERE $wpdb->groups_meta_id_col = '$metagroup_id'" );
			}
		}
	}
	

	if ( $insert_metagroup_ids = array_diff( $metagroup_ids, $stored_metagroup_ids ) ) {
		//rs_errlog("inserting metagroup ids: " . serialize($insert_metagroup_ids)');
	
		foreach ( $insert_metagroup_ids as $metagroup_id ) {
			scoper_query( "INSERT INTO $groups_table ( $wpdb->groups_meta_id_col, $wpdb->groups_name_col, $wpdb->groups_descript_col ) VALUES ( '$metagroup_id', '$metagroup_names[$metagroup_id]', '$metagroup_descripts[$metagroup_id]' )" );
			//rs_errlog( "INSERT INTO $groups_table ( $wpdb->groups_meta_id_col, $wpdb->groups_name_col, $wpdb->groups_descript_col ) VALUES ( '$metagroup_id', '$metagroup_names[$metagroup_id]', '$metagroup_descripts[$metagroup_id]' )" );
		}
	}
	
	if ( ! empty($delete_metagroup_ids) || ! empty($update_metagroup_ids) ) {
		wpp_cache_flush();  // role deletion / rename might affect other cached data or settings, so flush the whole cache

	} elseif ( ! empty($insert_group_ids) ) {
		wpp_cache_flush_group( 'all_usergroups' );
		wpp_cache_flush_group( 'usergroups_for_groups' );
		wpp_cache_flush_group( 'usergroups_for_user' );
		wpp_cache_flush_group( 'usergroups_for_ug' );
	}

	// Now step through every WP usermeta record, 
	// synchronizing the user's user2role2object_rs blog role entries with their WP role and custom caps

	// get each user's WP roles and caps
	$user_clause = ( $user_ids ) ? 'AND user_id IN (' . implode(', ', $user_ids) . ')' : ''; 
	
	$qry = "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = '{$wpdb->prefix}capabilities' $user_clause";
	if ( ! $usermeta = scoper_get_results($qry) )
		return;

	//rs_errlog("got " . count($usermeta) . " usermeta records");
		
	$wp_rolecaps = array();
	foreach ( $wp_roles->role_objects as $role_name => $role )
		$wp_rolecaps[$role_name] = $role->capabilities;

	//rs_errlog(serialize($wp_rolecaps));
	
	$strip_vals = array('', 0, false);

	$stored_assignments = array( 'wp' => array(), 'wp_cap' => array() );
	foreach ( array_keys($stored_assignments) as $role_type ) {
		$results = scoper_get_results("SELECT user_id, role_name, assignment_id FROM $uro_table WHERE role_type = '$role_type' AND user_id > 0 $user_clause");
		foreach ( $results as $key => $row ) {
			$stored_assignments[$role_type][$row->user_id][$row->assignment_id] = $row->role_name;
			unset( $results[$key] );
		}
	}
	
	foreach ( array_keys($usermeta) as $key ) {
		$user_id = $usermeta[$key]->user_id;
		$user_caps = maybe_unserialize($usermeta[$key]->meta_value);
		if ( empty($user_caps) || ! is_array($user_caps) )
			continue;
		
		//rs_errlog("user caps: " . serialize($user_caps));
			
		$user_roles = array();
			
		// just in case, strip out any entries with false value
		$user_caps = array_diff($user_caps, $strip_vals);
		
		$user_roles = array( 'wp' => array(), 'wp_cap' => array() );
		
		//Filter out caps that are not role names
		$user_roles['wp'] = array_intersect(array_keys($user_caps), $wp_rolenames);
		
		
		// Store any custom-assigned caps as single-cap roles
		// This will be invisible and only used to support the users query filter
		// With current implementation, the custom cap will only be honored when
		// users_who_can is called with a single capreq 
		$user_roles['wp_cap'] = array_diff( array_keys($user_caps), $user_roles['wp'] );
		

		// which roles are already stored in user2role2object_rs table?
		$stored_roles = array();
		$delete_roles = array();
		foreach ( array_keys($user_roles) as $role_type ) {
			//$results = scoper_get_results("SELECT role_name, assignment_id FROM $uro_table WHERE role_type = '$role_type' AND user_id = '$user_id'");
			//if ( $results ) {
			if ( isset( $stored_assignments[$role_type][$user_id] ) ) {
				//rs_errlog("results: " . serialize($results));
				foreach ( $stored_assignments[$role_type][$user_id] as $assignment_id => $role_name ) {
					// Log stored roles, and delete any roles which user no longer has (possibly because the WP role definition was deleted).
					// Only Role Scoper's mirroring of WP blog roles is involved here unless Role Scoper was configured and used with a Role Type of "WP".
					// This also covers any WP role changes made while Role Scoper was deactivated.
					if ( in_array( $role_name, $user_roles[$role_type]) )
						$stored_roles[$role_type] []= $role_name;
					else
						$delete_roles []= $assignment_id;
				}
			} else
				$stored_roles[$role_type] = array();
		}
		
		if ( $delete_roles ) {
			$id_in = implode(', ', $delete_roles);
			scoper_query("DELETE FROM $uro_table WHERE assignment_id IN ($id_in)");
		}
		
		//rs_errlog("user roles " . serialize($user_roles) ');
		//rs_errlog("stored roles " . serialize($stored_roles)');
		
		// add any missing roles
		foreach ( array_keys($user_roles) as $role_type ) {
			if ( $stored_roles[$role_type] )
				$user_roles[$role_type] = array_diff($user_roles[$role_type], $stored_roles[$role_type]);
			
			if ( $user_roles[$role_type] )
				foreach ( $user_roles[$role_type] as $role_name ) {
					//rs_errlog("INSERT INTO $uro_table (user_id, role_name, role_type, scope) VALUES ('$user_id', '$role_name', '$role_type', 'blog')");
					scoper_query("INSERT INTO $uro_table (user_id, role_name, role_type, scope) VALUES ('$user_id', '$role_name', '$role_type', 'blog')");	
				}
		}
		
	} // end foreach WP usermeta

	
	// Delete any role assignments for users which no longer exist
	delete_roles_orphaned_from_user();
	
	// Delete any role assignments for WP groups which no longer exist
	delete_roles_orphaned_from_group();
	
	// Delete any role assignments for posts/pages which no longer exist
	delete_roles_orphaned_from_item( OBJECT_SCOPE_RS, 'post' );
	//delete_restrictions_orphaned_from_item( OBJECT_SCOPE_RS, 'post' );	// hold off on this until delete_roles_orphaned_from_item() call has a long, clear track record
	
	// Delete any role assignments for categories which no longer exist
	delete_roles_orphaned_from_item( TERM_SCOPE_RS, 'category' );
	//delete_restrictions_orphaned_from_item( TERM_SCOPE_RS, 'category' );
	
	//rs_errlog("finished syncroles "');
	
} // end scoper_sync_wproles function


function delete_roles_orphaned_from_user() {	
	global $wpdb;
	
	// Delete any role entries for WP metagroups (or other groups) which no longer exists
	if ( $users_table_valid = scoper_get_var( "SELECT ID FROM $wpdb->users LIMIT 1" ) ) {
		$qry = "DELETE FROM $wpdb->user2role2object_rs WHERE user_id >= '1' AND user_id NOT IN ( SELECT ID FROM $wpdb->users )";
		scoper_query($qry);
	}
}

function delete_roles_orphaned_from_group() {	
	global $wpdb;
	
	// Delete any role entries for WP metagroups (or other groups) which no longer exists
	if ( ! empty($wpdb->groups_id_col) && ! empty($wpdb->groups_rs) ) {
		if ( $groups_table_valid = scoper_get_var( "SELECT $wpdb->groups_id_col FROM $wpdb->groups_rs LIMIT 1" ) ) {
			$qry = "DELETE FROM $wpdb->user2role2object_rs WHERE group_id >= '1' AND group_id NOT IN ( SELECT $wpdb->groups_id_col FROM $wpdb->groups_rs )";
			//rs_errlog( $qry );
			scoper_query($qry);
		}
	}
}
	
// delete roles for any terms/objects which no longer exist
function delete_roles_orphaned_from_item( $scope, $src_or_tx_name ) {
	global $scoper, $wpdb;

	if ( 'term' == $scope ) {
		if ( 'category' == $src_or_tx_name ) {	// this is called early by sync_roles
			$item_table = $wpdb->term_taxonomy;
			$col_item_id = 'term_id';
		} elseif ( ! empty($scoper) ) {
			$qv = $scoper->taxonomies->get_terms_query_vars($src_or_tx_name, true);  // arg: terms only
			$item_table = $qv->term->table;
			$col_item_id = $qv->term->col_id;
		}
	} else {
		if ( 'post' == $src_or_tx_name ) { // this is called early by sync_roles
			$col_item_id = 'ID';
			$item_table = $wpdb->posts;
		} elseif( ! empty($scoper) ) {
			$col_item_id = $scoper->data_sources->member_property($src_or_tx_name, 'cols', 'id');
			$item_table = $scoper->data_sources->member_property($src_or_tx_name, 'table');
		}
	}
	
	if ( $is_valid_items = scoper_get_var( "SELECT $col_item_id FROM $item_table LIMIT 1" ) ) {
		$where = "AND scope = '$scope' AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id NOT IN ( SELECT $col_item_id FROM $item_table ) AND obj_or_term_id >= 1 ";
		if ( $items_to_delete = scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE 1=1 $where LIMIT 1" ) ) {
			$qry = "DELETE FROM $wpdb->user2role2object_rs WHERE 1=1 $where";
			scoper_query( $qry );
			wpp_cache_flush();
		}
	}
}

/*
// delete restrictions for any terms/objects which no longer exist
function delete_restrictions_orphaned_from_item( $scope, $src_or_tx_name ) {
	global $wpdb;

	if ( 'term' == $scope ) {
		if ( 'category' == $src_or_tx_name ) {	// this is called early by sync_roles
			$item_table = $wpdb->term_taxonomy;
			$col_item_id = 'term_id';
		} elseif ( ! empty($scoper) ) {
			$qv = $scoper->taxonomies->get_terms_query_vars($src_or_tx_name, true);  // arg: terms only
			$item_table = $qv->term->table;
			$col_item_id = $qv->term->col_id;
		}
	} else {
		if ( 'post' == $src_or_tx_name ) { // this is called early by sync_roles
			$col_item_id = 'ID';
			$item_table = $wpdb->posts;
		} elseif( ! empty($scoper) ) {
			$col_item_id = $scoper->data_sources->member_property($src_or_tx_name, 'cols', 'id');
			$item_table = $scoper->data_sources->member_property($src_or_tx_name, 'table');
		}
	}
	
	if ( $is_valid_items = scoper_get_var( "SELECT $col_item_id FROM $item_table LIMIT 1" ) ) {
		$where = "AND topic = '$scope' AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id NOT IN ( SELECT $col_item_id FROM $item_table ) AND obj_or_term_id >= 1";
		if ( $items_to_delete = scoper_get_var( "SELECT requirement_id FROM $wpdb->role_scope_rs WHERE 1=1 $where LIMIT 1" ) ) {
			$qry = "DELETE FROM $wpdb->role_scope_rs WHERE 1=1 $where";
			scoper_query( $qry );
			wpp_cache_flush();
		}
	}
}
*/


// legacy function called when upgrading from versions older than 0.9.15
function scoper_fix_page_parent_recursion() {
	global $wpdb;
	$arr_parent = array();
	$arr_children = array();
	
	if ( $results = scoper_get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page'") ) {
		foreach ( $results as $row ) {
			$arr_parent[$row->ID] = $row->post_parent;
			
			if ( ! isset($arr_children[$row->post_parent]) )
				$arr_children[$row->post_parent] = array();
				
			$arr_children[$row->post_parent] []= $row->ID;
		}
		
		// if a page's parent is also one of its children, set parent to Main
		foreach ( $arr_parent as $page_id => $parent_id )
			if ( isset($arr_children[$page_id]) && in_array($parent_id, $arr_children[$page_id]) )
				scoper_query("UPDATE $wpdb->posts SET post_parent = '0' WHERE ID = '$page_id'");
	}
}


// On first-time install, prevent WP/RS role mismatch by disabling RS rolecaps that are missing from corresponding default WP roles
function scoper_set_default_rs_roledefs() {
	global $wp_roles, $scoper;

	$sitewide = IS_MU_RS;
	
	if ( scoper_get_option( 'disabled_role_caps', $sitewide ) || scoper_get_option( 'default_disabled_role_caps', $sitewide ) )
		return;

	$default_role_caps = scoper_core_role_caps();

	$wp_role_sync = array( 
		'rs_post_contributor' 	=> 'contributor',
		'rs_post_revisor' 		=> 'revisor',
		'rs_post_author' 		=> 'author',
		'rs_post_editor' 		=> 'editor',
		'rs_page_revisor' 		=> 'revisor',
		'rs_page_editor'		=> 'editor'
	);
	
	$disable_caps = array();
	
	foreach ( $wp_role_sync as $rs_role_handle => $wp_role_name ) {
		if ( isset( $wp_roles->role_objects[ $wp_role_name ] ) )
			if ( $wp_missing_caps = array_diff_key( $default_role_caps[$rs_role_handle], $wp_roles->role_objects[$wp_role_name]->capabilities ) )
				$disable_caps[$rs_role_handle] = $wp_missing_caps;
	}

	if ( $disable_caps ) {
		scoper_update_option( 'disabled_role_caps', $disable_caps, $sitewide);
		
		if ( $sitewide )
			scoper_update_option( 'default_disabled_role_caps', $disable_caps, $sitewide);
	}
}


function scoper_check_revision_settings() {
	static $been_here;
	
	if ( defined( 'RVY_VERSION' ) || ! empty($been_here) )
		return;

	$been_here = true;
		
	// Give a heads-up and download link if pending Revisions were active in RS <= 1.0.8, but Revisionary is not installed
	if ( scoper_get_option( 'pending_revisions' ) ) {
		$err_msg = sprintf(__('Pending Revisions were enabled in your previous Role Scoper version.  To retain that feature, you need to install %1$s Revisionary%2$s, another %3$s Agapetry Creations%4$s plugin.', 'revisionary'), "<a href='__rvy-info__'>", '</a>', "<a href='http://agapetry.net'>", '</a>');
		
		$func_body .= '$msg = str_replace( "__rvy-info__", awp_plugin_info_url("revisionary"), "' . $err_msg . '");';
		$func_body .= "echo '" 
		. '<div id="message" class="error fade" style="color: black"><p><strong>' 
		. "'" 
		. ' . $msg . ' 
		. "'</strong></p></div>';";
	
		if ( is_admin() )
			add_action('admin_notices', create_function('', $func_body) );
	}
}
?>