<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
	
	// called by ScoperAdminFilters::mnt_save_object
	// This handler is meant to fire whenever an object is inserted or updated.
	// If the client does use such a hook, we will force it by calling internally from mnt_create and mnt_edit
	function scoper_mnt_save_object($src_name, $args, $object_id, $object = '') {
		global $scoper;

		static $saved_objects;
		
		if ( ! isset($saved_objects) )
			$saved_objects = array();

		if ( isset($saved_objects[$src_name][$object_id]) )
			return;

		$defaults = array( 'object_type' => '' );
		$args = array_intersect_key( $defaults, (array) $args );
		extract($args);
			
		$is_new_object = false;
		
		if ( empty($object_type) )
			$object_type = scoper_determine_object_type($src_name, $object_id, $object);

		$saved_objects[$src_name][$object_id] = 1;

		// parent settings can affect the auto-assignment of propagating roles/restrictions
		$last_parent = 0;
		
		if ( $col_parent = $scoper->data_sources->member_property($src_name, 'cols', 'parent') )
			if ( isset($_POST[$col_parent]) ) 
				$set_parent = $_POST[$col_parent];
	
		// Determine whether this object is new (first time this RS filter has run for it, though the object may already be inserted into db)
		if ( 'post' == $src_name ) {
			$last_parent = ( $object_id > 0 ) ? get_post_meta($object_id, '_scoper_last_parent', true) : '';
				
			$is_new_object = ! is_numeric($last_parent);
				
			if ( isset($set_parent) && ($set_parent != $last_parent) && ($set_parent || $last_parent) )
				update_post_meta($obj_or_term_id, '_scoper_last_parent', (int) $set_parent);
			
			/* // This ugly workaround should not be necessary now
			$time = agp_time_gmt();
	
			if ( $is_new_object ) {
				update_post_meta($obj_or_term_id, '_scoper_creation_date', $time);
			} else {
				if ( $creation_date = get_post_meta($object_id, '_scoper_creation_date', true) )
					if ( $time - $creation_date < 1 )
						$is_new_object = true;
			}
			*/
			
		} else {
			// for other data sources, we have to assume object is new unless it has a role or restriction stored already.
			$is_new_object = true;
			
			$qry = "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND src_or_tx_name = '$src_name' AND obj_or_term_id = '$object_id'";
			if ( $assignment_ids = scoper_get_col($qry) )
				$is_new_object = false;
			else {	
				$qry = "SELECT requirement_id FROM $wpdb->role_scope_rs WHERE topic = 'object' AND src_or_tx_name = '$src_name' AND obj_or_term_id = '$object_id'";
				if ( $requirement_ids = scoper_get_col($qry) )
					$is_new_object = false;
			}
			
			if ( $col_parent ) {
				if ( ! $is_new_object ) {
					$last_parents = get_option( "scoper_last_parents_{$src_name}");
					if ( ! is_array($last_parents) )
						$last_parents = array();
					
					if ( isset( $last_parents[$object_id] ) )
						$last_parent = $last_parents[$object_id];
				}
			
				if ( isset($set_parent) && ($set_parent != $last_parent) && ($set_parent || $last_parent) ) {
					$last_parents[$object_id] = $set_parent;
					update_option( "scoper_last_parents_{$src_name}", $last_parents);
				}
			}
		}
	
		// used here and in UI display to enumerate role definitions
		$role_defs = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_name, $object_type);
		$role_handles = array_keys($role_defs);
		

		// Were roles / restrictions previously customized by direct edit?
		if ( 'post' == $src_name )
			$roles_customized = $is_new_object ? false : get_post_meta($object_id, '_scoper_custom', true);
		else {
			$roles_customized = false;
			if ( ! $is_new_object )
				if ( $custom_role_objects = get_option( "scoper_custom_{$src_name}" ) )
					$roles_customized = isset( $custom_role_objects[$object_id] );
				
			if ( ! is_array($custom_role_objects) )
				$custom_role_objects = array();
		}
		
		$new_role_settings = false;
		$new_restriction_settings = false;
		
		// Were roles / restrictions custom-edited just now?
		if ( ! defined('XMLRPC_REQUEST') ) {
			// Now determine if roles/restrictions have changed since the edit form load
			foreach ( $role_defs as $role_handle => $role_def) {
				$role_code = 'r' . array_search($role_handle, $role_handles);

				// make sure the role assignment UI for this role was actually reviewed
				if ( ! isset($_POST["last_objscope_{$role_code}"]) )
					continue;

				// did user change roles?
				$compare_vars = array( 
				"{$role_code}u" => "last_{$role_code}u", 
				"{$role_code}g" => "last_{$role_code}g"
				);
				
				if ( $col_parent ) {
					$compare_vars ["p_{$role_code}u"] = "last_p_{$role_code}u";
					$compare_vars ["p_{$role_code}g"] = "last_p_{$role_code}g";
				}
				
				foreach ( $compare_vars as $var => $var_last ) {
					$agents = ( isset($_POST[$var]) ) ? $_POST[$var] : array();
					$last_agents = ( ! empty($_POST[$var_last]) ) ? explode("~", $_POST[$var_last]) : array();
					
					sort($agents);
					sort($last_agents);

					if ( $last_agents != $agents ) {
						$new_role_settings = true;
						break;
					}
				}
				
				// did user change restrictions?
				$compare_vars = array(
				"objscope_{$role_code}" => "last_objscope_{$role_code}"
				);
				
				if ( $col_parent )
					$compare_vars ["objscope_children_{$role_code}"] = "last_objscope_children_{$role_code}";
				
				foreach ( $compare_vars as $var => $var_last ) {
					$val = ( isset($_POST[$var]) ) ? $_POST[$var] : 0;
					$last_val = ( isset($_POST[$var_last]) ) ? $_POST[$var_last] : 0;
					
					if ( $val != $last_val ) {
						$new_role_settings = true;
						$new_restriction_settings = true;
						break;
					}
				}
				
				if ( $new_role_settings && $new_restriction_settings )
					break;
			}
		
			if ( $new_role_settings && ! $roles_customized ) {
				if ( 'post' == $src_name )
					update_post_meta($object_id, '_scoper_custom', true);
				else {
					$custom_role_objects [$object_id] = true;
					update_option( "scoper_custom_{$src_name}", $custom_role_objects );
				}
			}
		} // endif user-modified roles/restrictions weren't already saved

		// Inherit parent roles / restrictions, but only for new objects, 
		// or if a new parent is set and roles haven't been manually edited for this object
		if ( ! $roles_customized && ! $new_role_settings && ( $is_new_object || ( isset($set_parent) && ($set_parent != $last_parent) ) ) ) {
			// apply default roles for new object
			if ( $is_new_object ) {
				scoper_inherit_parent_roles($object_id, OBJECT_SCOPE_RS, $src_name, 0, $object_type);
			} else {
				$args = array( 'inherited_only' => true, 'clear_propagated' => true );
				ScoperAdminLib::clear_restrictions(OBJECT_SCOPE_RS, $src_name, $object_id, $args);
				ScoperAdminLib::clear_roles(OBJECT_SCOPE_RS, $src_name, $object_id, $args);
			}
			
			// apply propagating roles,restrictions from specific parent
			if ( ! empty($set_parent) ) {
				//d_echo( 'inherit parent roles' );
				scoper_inherit_parent_roles($object_id, OBJECT_SCOPE_RS, $src_name, $set_parent, $object_type);
				scoper_inherit_parent_restrictions($object_id, OBJECT_SCOPE_RS, $src_name, $set_parent, $object_type);
			}
		} // endif new parent selection (or new object)

		// Roles/Restrictions were just edited manually, so store role settings (which may contain default roles even if no manual settings were made)
		if ( $new_role_settings && ! empty($_POST['rs_object_roles']) && ( empty($_POST['action']) || ( 'autosave' != $_POST['action'] ) ) && ! defined('XMLRPC_REQUEST') ) {
			$role_assigner = init_role_assigner();
		
			$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');
			if ( 
			( ( 'admin' != $require_blogwide_editor ) || is_user_administrator_rs() ) &&
			( ( 'admin_content' != $require_blogwide_editor ) || is_content_administrator_rs() ) 
			) {
				if ( $object_type && $scoper->admin->user_can_admin_object($src_name, $object_type, $object_id) ) {
					// store any object role (read/write/admin access group) selections
					$role_bases = array();
					if ( GROUP_ROLES_RS )
						$role_bases []= ROLE_BASIS_GROUPS;
					if ( USER_ROLES_RS )
						$role_bases []= ROLE_BASIS_USER;
					
					$set_roles = array_fill_keys( $role_bases, array() );
					$set_restrictions = array();
					
					$default_restrictions = $scoper->get_default_restrictions(OBJECT_SCOPE_RS);
					
					foreach ( $role_defs as $role_handle => $role_def) {
						if ( ! isset($role_def->valid_scopes[OBJECT_SCOPE_RS]) )
							continue;
	
						$role_code = 'r' . array_search($role_handle, $role_handles);
							
						// make sure the role assignment UI for this role was actually reviewed
						if ( ! isset($_POST["last_objscope_{$role_code}"]) )
							continue;

						$role_ops = $scoper->role_defs->get_role_ops($role_handle);
				
						// user can't view or edit role assignments unless they have all rolecaps
						// however, if this is a new post, allow read role to be assigned even if contributor doesn't have read_private cap blog-wide
						if ( ! is_user_administrator_rs() && ( ! $is_new_object || $role_ops != array( 'read' => 1 ) ) ) {
							$reqd_caps = $scoper->role_defs->role_caps[$role_handle];
							if ( ! awp_user_can(array_keys($reqd_caps), $object_id) )
								continue;
		
							// a user must have a blog-wide edit cap to modify editing role assignments (even if they have Editor role assigned for some current object)
							if ( isset($role_ops[OP_EDIT_RS]) || isset($role_ops[OP_ASSOCIATE_RS]) ) 
								if ( $require_blogwide_editor ) {
									$required_cap = ( 'page' == $object_type ) ? 'edit_others_pages' : 'edit_others_posts';
									
									global $current_user;
									if ( empty( $current_user->allcaps[$required_cap] ) )
										continue;
								}
						}
		
						foreach ( $role_bases as $role_basis ) {
							$id_prefix = $role_code . substr($role_basis, 0, 1);
							
							$for_entity_agent_ids = (isset( $_POST[$id_prefix]) ) ? $_POST[$id_prefix] : array();
							$for_children_agent_ids = ( isset($_POST["p_$id_prefix"]) ) ? $_POST["p_$id_prefix"] : array();
							
	
							// handle csv-entered agent names
							$csv_id = "{$id_prefix}_csv";
							
							if ( $csv_for_item = ScoperAdminLib::agent_ids_from_csv( $csv_id, $role_basis ) )
								$for_entity_agent_ids = array_merge($for_entity_agent_ids, $csv_for_item);
							
							if ( $csv_for_children = ScoperAdminLib::agent_ids_from_csv( "p_$csv_id", $role_basis ) )
								$for_children_agent_ids = array_merge($for_children_agent_ids, $csv_for_children);
								
							$set_roles[$role_basis][$role_handle] = array();
		
							if ( $for_both_agent_ids = array_intersect($for_entity_agent_ids, $for_children_agent_ids) )
								$set_roles[$role_basis][$role_handle] = $set_roles[$role_basis][$role_handle] + array_fill_keys($for_both_agent_ids, ASSIGN_FOR_BOTH_RS);
							
							if ( $for_entity_agent_ids = array_diff( $for_entity_agent_ids, $for_children_agent_ids ) )
								$set_roles[$role_basis][$role_handle] = $set_roles[$role_basis][$role_handle] + array_fill_keys($for_entity_agent_ids, ASSIGN_FOR_ENTITY_RS);
					
							if ( $for_children_agent_ids = array_diff( $for_children_agent_ids, $for_entity_agent_ids ) )
								$set_roles[$role_basis][$role_handle] = $set_roles[$role_basis][$role_handle] + array_fill_keys($for_children_agent_ids, ASSIGN_FOR_CHILDREN_RS);
						}
						
						if ( isset($default_restrictions[$src_name][$role_handle]) ) {
							$max_scope = BLOG_SCOPE_RS;
							$item_restrict = empty($_POST["objscope_{$role_code}"]);
							$child_restrict = empty($_POST["objscope_children_{$role_code}"]);
						} else {
							$max_scope = OBJECT_SCOPE_RS;
							$item_restrict = ! empty($_POST["objscope_{$role_code}"]);
							$child_restrict = ! empty($_POST["objscope_children_{$role_code}"]);
						}
						
						$set_restrictions[$role_handle] = array( 'max_scope' => $max_scope, 'for_item' => $item_restrict, 'for_children' => $child_restrict );
					}
					
					$args = array('implicit_removal' => true, 'object_type' => $object_type);
					
					// don't record first-time storage of default roles as custom settings
					if ( ! $new_role_settings )
						$args['is_auto_insertion'] = true;
					
					// Add or remove object role restrictions as needed (no DB update in nothing has changed)
					$role_assigner->restrict_roles(OBJECT_SCOPE_RS, $src_name, $object_id, $set_restrictions, $args );
					
					// Add or remove object role assignments as needed (no DB update if nothing has changed)
					foreach ( $role_bases as $role_basis )
						$role_assigner->assign_roles(OBJECT_SCOPE_RS, $src_name, $object_id, $set_roles[$role_basis], $role_basis, $args );
				} // endif object type is known and user can admin this object
			} // end if current user is an Administrator, or doesn't need to be
		} //endif roles were manually edited by user (and not autosave)
		
		
		if ( $new_restriction_settings )
			scoper_flush_file_rules();
		else { 
			if ( isset( $scoper->filters_admin->last_post_status[$object_id] ) ) {
				$new_status = ( isset($_POST['post_status']) ) ? $_POST['post_status'] : ''; // assume for now that XML-RPC will not modify post status

				if ( $scoper->filters_admin->last_post_status[$object_id] != $new_status )
					if ( ( 'private' == $new_status ) || ( 'private' == $scoper->filters_admin->last_post_status[$object_id] ) )
						scoper_flush_file_rules();

			} elseif ( isset($_POST['post_status']) && ( 'private' == $_POST['post_status'] ) )
				scoper_flush_file_rules();
		}
		
		if ( 'page' == $object_type ) {
			delete_option('scoper_page_ancestors');
			scoper_flush_cache_groups('get_pages');
		}
		
		// need this to make metabox captions update in first refresh following edit & save
		if ( is_admin() && isset( $scoper->filters_admin_item_ui ) )
			$scoper->filters_admin_item_ui->act_tweak_metaboxes();
		
		// possible TODO: remove other conditional calls since we're doing it here on every save
		scoper_flush_results_cache();	
	}

	
	// Filtering of Page Parent selection.  
	// This is a required after-the-fact operation for WP < 2.7 (due to inability to control inclusion of Main Page in UI dropdown)
	// For WP >= 2.7, it is an anti-hacking precaution
	//
	// There is currently no way to explictly restrict or grant Page Association rights to Main Page (root). Instead:
	// 	* Require blog-wide edit_others_pages cap for association of a page with Main
	//  * If an unqualified user tries to associate or un-associate a page with Main Page,
	//	  revert page to previously stored parent if possible. Otherwise set status to "unpublished".
	function scoper_flt_post_status ($status) {
		if ( isset($_POST['post_type']) && ( $_POST['post_type'] == 'page' ) && ('autosave' != $_POST['action']) ) {
			global $scoper, $current_user;

			// overcome any denials of publishing rights which were not filterable by user_has_cap
			if ( ('pending' == $status) && ( ('publish' == $_POST['post_status']) || ('Publish' == $_POST['original_publish'] ) ) )
				if ( ! empty( $current_user->allcaps['publish_pages'] ) )
					$status = 'publish';
			
			// user can't associate / un-associate a page with Main page unless they have edit_pages blog-wide
			if ( isset($_POST['post_ID']) ) {
				$post = $scoper->data_sources->get_object( 'post', $_POST['post_ID'] );
				
				// if neither the stored nor selected parent is Main, we have no beef with it		// is it actually saved (if just auto-saved draft, don't provide these exceptions)
				if ( ! empty($_POST['parent_id']) && ( ! empty($post->post_parent) || ( ('publish' != $post->post_status) && ('private' != $post->post_status) ) ) )
					return $status;
				
				$already_published = ( ('publish' == $post->post_status) || ('private' == $post->post_status) );

				// if the page is and was associated with Main Page, don't mess
				if ( empty($_POST['parent_id']) && empty( $post->post_parent ) && $already_published )
					return $status;
			} else
				$already_published = false;
			
			
			$top_pages_locked = scoper_get_option( 'lock_top_pages' );
				
			if ( is_content_administrator_rs() )
				$can_associate_main = true;
	
			elseif ( '1' !== $top_pages_locked ) {
				$reqd_caps = ( 'author' == $top_pages_locked ) ? array('publish_pages') : array('edit_others_pages');
				$roles = $scoper->role_defs->qualify_roles($reqd_caps, '');

				$can_associate_main = array_intersect_key($roles, $current_user->blog_roles[ANY_CONTENT_DATE_RS]);

			} else	// only administrators can change top level structure
				$can_associate_main = false;


			if ( ! $can_associate_main ) {
				// If post was previously published to another parent, allow subsequent page_parent filter to revert it
				if ( $already_published ) {
					if ( ! isset($scoper->revert_post_parent) )
						$scoper->revert_post_parent = array();
						
					$scoper->revert_post_parent[ $_POST['post_ID'] ] = $post->post_parent;
					
					// message display should not be necessary with legitimate WP 2.7+ usage, since the Main Page item is filtered out of UI dropdown as necessary
					global $revisionary;
					if ( ! awp_ver('2.7-dev') && ( ! defined( 'RVY_VERSION' ) || empty($revisionary->admin->impose_pending_rev) ) ) {
						$src = $scoper->data_sources->get('post');
						$src_edit_url = sprintf($src->edit_url, $_POST['post_ID']);
						
						if ( empty($post->post_parent) )
							$msg = __('The page %s was saved, but the new Page Parent setting was discarded. You do not have permission to disassociate it from the Main Page.', 'scoper');
						else
							$msg = __('The Page Parent setting for %s was reverted to the previously stored value. You do not have permission to associate it with the Main Page.', 'scoper');
						
						$msg = sprintf($msg, '&quot;<a href="' . $src_edit_url . '">' . $_POST['post_title'] . '</a>&quot;');
						update_option("scoper_notice_{$current_user->ID}", $msg );
					}
					
				} elseif ( empty($_POST['parent_id']) && ( ('publish' == $_POST['post_status']) || ('private' == $_POST['post_status']) ) ) {
					// This should only ever happen with WP < 2.7 or if the POST data is manually fudged
					$status = 'draft';

					global $current_user;
					$src = $scoper->data_sources->get('post');
					$src_edit_url = sprintf($src->edit_url, $_POST['post_ID']);

					$msg = sprintf(__('The page %s cannot be published because you do not have permission to associate it with the Main Page. Please select a different Page Parent and try again.', 'scoper'), '&quot;<a href="' . $src_edit_url . '">' . $_POST['post_title'] . '</a>&quot;');
					
					update_option("scoper_notice_{$current_user->ID}", $msg );
				}
			}
		}

		return $status;
	}
	
	
	// Enforce any page parent filtering which may have been dictated by the flt_post_status filter, which executes earlier.
	function scoper_flt_page_parent ($parent_id) {
		if ( defined( 'RVY_VERSION' ) ) {
			global $revisionary;
			if ( ! empty($revisionary->admin->revision_save_in_progress) )
				return $parent_id;
		}
		
		global $scoper;
				
		if ( isset($_POST['post_ID']) && isset($scoper->revert_post_parent) && isset( $scoper->revert_post_parent[ $_POST['post_ID'] ] ) )
			return $scoper->revert_post_parent[ $_POST['post_ID'] ];

		// Page parent will not be reverted due to Main Page (un)association with insufficient blog role
		// ... but make sure the selected parent is valid.  Merely an anti-hacking precaution to deal with manually fudged POST data
		if ( $parent_id && isset($_POST['post_ID']) && isset($_POST['post_type']) && ( 'page' == $_POST['post_type']) ) {
			global $wpdb;
			$args = array();
			$args['alternate_reqd_caps'][0] = array('create_child_pages');
		
			$qry_parents = "SELECT ID FROM $wpdb->posts WHERE post_type = 'page'";
			$qry_parents = apply_filters('objects_request_rs', $qry_parents, 'post', 'page', $args);
			$valid_parents = scoper_get_col($qry_parents);
			
			if ( ! in_array($parent_id, $valid_parents) ) {
				$post = $scoper->data_sources->get_object( 'post', $_POST['post_ID'] );
				$parent_id = $post->post_parent;
			}
		}
			
		return $parent_id;
	}
	
	
	function scoper_flt_pre_object_terms ($selected_terms, $taxonomy, $args = '') {
		// strip out fake term_id -1 (if applied)
		if ( $selected_terms )
			$selected_terms = array_diff($selected_terms, array(-1));
			
		// TODO: skip this for content admins?
		if ( empty($selected_terms) || empty($selected_terms[0]) ) {  // not sure who is changing empty $_POST['post_category'] array to an array with nullstring element, but we have to deal with that
			global $scoper;

			if ( $tx = $scoper->taxonomies->get( $taxonomy ) ) {

				if ( ! empty($tx->default_term_option ) ) {
					// get_option call sometimes fails here. Todo: why?
					global $wpdb;
					$selected_terms = (array) maybe_unserialize( scoper_get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = '$tx->default_term_option'" ) );
					
					//$selected_terms = (array) get_option( $tx->default_term_option );
				}
			}
		}

		if ( is_content_administrator_rs() || defined('DISABLE_QUERYFILTERS_RS') )
			return $selected_terms;
			

		global $scoper, $current_user;
			
		if ( ! $src = $scoper->taxonomies->member_property($taxonomy, 'object_source') )
			return $selected_terms;
		
		if ( defined( 'RVY_VERSION' ) ) {
			global $revisionary;
				 
			if ( ! empty($revisionary->admin->impose_pending_rev) )
				return $selected_terms;
		}
			
		$orig_selected_terms = $selected_terms;

		if ( ! is_array($selected_terms) )
			$selected_terms = array();

		$user_terms = array(); // will be returned by filter_terms_for_status
		$selected_terms = scoper_filter_terms_for_status($taxonomy, $selected_terms, $user_terms);

		if ( $object_id = $scoper->data_sources->detect('id', $src) ) {
			$selected_terms = scoper_reinstate_hidden_terms($taxonomy, $selected_terms);
			
			/*
			if ( ! $selected_terms = scoper_reinstate_hidden_terms($taxonomy, $selected_terms) ) {
				if ( $orig_selected_terms )
					return $orig_selected_terms;
			}
			*/
		}

		if ( empty($selected_terms) || empty($selected_terms[0]) ) {
			// if array empty, insert default term (wp_create_post check is only subverted on updates)
			if ( $option_name = $scoper->taxonomies->member_property($taxonomy, 'default_term_option') ) {
				$default_terms = get_option($option_name);
			} else
				$default_terms = 0;

			// but if the default term is not defined or is not in user's subset of usable terms, substitute first available
			if ( $user_terms ) {
				if ( ! is_array($default_terms) )
					$default_terms = (array) $default_terms;
			
				$default_terms = array_intersect($default_terms, $user_terms);

				if ( empty($default_terms) )
					$default_terms = $user_terms[0];
			}

			$selected_terms = (array) $default_terms;
		}
		
		return $selected_terms;
	}
	
	// Reinstate any object terms which the object already has, but were hidden from the user due to lack of edit caps
	// (if a user does not have edit cap within some term, he can neither add nor remove them from an object)
	function scoper_reinstate_hidden_terms($taxonomy, $object_terms) {
		if ( defined( 'DISABLE_QUERYFILTERS_RS' ) )
			return $object_terms;
		
		// strip out any fake placeholder IDs which may have been applied
		if ( $object_terms )
			$object_terms = array_diff($object_terms, array(-1));
			
		global $scoper;
			
		if ( ! $src = $scoper->taxonomies->member_property($taxonomy, 'object_source') )
			return $object_terms;
			
		if ( ! $object_id = $scoper->data_sources->get_from_http_post('id', $src) )
			return $object_terms;
		
		if ( ! $object_type = $scoper->data_sources->detect('type', $src, $object_id) )
			return $object_terms;
		
		$orig_object_terms = $object_terms;
			
		// make sure _others caps are required only for objects current user doesn't own
		$base_caps_only = false;
		if ( ! empty($src->cols->owner) ) {
			$col_owner = $src->cols->owner;
			if ( $object = $scoper->data_sources->get_object($src->name, $object_id) ) {

				// don't reinstate terms which were only inserted by autosave
				if ( empty( $object->post_modified_gmt ) )
					return $object_terms;
				
				global $current_user;
				if ( ! empty($object->$col_owner) && ( $object->$col_owner == $current_user->ID) )
					$base_caps_only = true;
			}
		}
			
		$reqd_caps = array();
		if ( ! empty($src->statuses) ) {
			// determine object's previous status so we know what terms were hidden
			if ( $stored_status = $scoper->data_sources->get_from_db('status', $src, $object_id) )
				$reqd_caps = $scoper->cap_defs->get_matching($src->name, $object_type, OP_EDIT_RS, $stored_status, $base_caps_only);
		}
		
		// if no status-specific caps are defined, or if this source doesn't define statuses...
		if ( ! $reqd_caps )
			if ( ! $reqd_caps = $scoper->cap_defs->get_matching($src->name, $object_type, OP_EDIT_RS, STATUS_ANY_RS, $base_caps_only) )
				return $object_terms;
				
		$user_terms = $scoper->qualify_terms_daterange(array_keys($reqd_caps), $taxonomy);
		
		foreach ( array_keys($user_terms) as $date_key ) {
			$date_clause = '';
			
			if ( $date_key && is_serialized($date_key) ) {
				// Check stored post date against any role date limits associated whith this set of terms (if not stored, check current date)
				
				$content_date_limits = unserialize($date_key);
				
				$post_date_gmt = ( $object_id ) ? $scoper->data_sources->get_from_db('date', $src, $object_id) : 0;
				
				if ( ! $post_date_gmt )
					$post_date_gmt = agp_time_gmt();

				if ( ( $post_date_gmt < $content_date_limits->content_min_date_gmt ) || ( $post_date_gmt > $content_date_limits->content_max_date_gmt ) )
					unset( $user_terms[$date_key] );
			}
		}
		
		$user_terms = agp_array_flatten( $user_terms );
			
		// this is a security precaution
		$object_terms = array_intersect($object_terms, $user_terms);
		
		// current object terms which were hidden from user's admin UI must be retained
		if ( $stored_object_terms = $scoper->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS, $object_id) ) {
			$dropped_terms = array_diff($stored_object_terms, $object_terms);
			
			//terms which were dropped due to being filtered out of user UI should be reinstated
			$object_terms = array_merge($object_terms, array_diff($dropped_terms, $user_terms) );
			
			return array_unique($object_terms);
		} else
			return $orig_object_terms;
	}
	
	
	// Removes terms for which the user has edit cap, but not edit_[status] cap
	// If the removed terms are already stored to the post (by a user who does have edit_[status] cap), they will be reinstated by reinstate_hidden_terms
	function scoper_filter_terms_for_status($taxonomy, $selected_terms, &$user_terms) {
		if ( defined( 'DISABLE_QUERYFILTERS_RS' ) )
			return $selected_terms;
		
		global $scoper;
			
		if ( ! $src = $scoper->taxonomies->member_property($taxonomy, 'object_source') )
			return $selected_terms;

		if ( ! isset($src->statuses) || (count($src->statuses) < 2) )
			return $selected_terms;
		
		$object_id = $scoper->data_sources->detect('id', $src);

		if ( ! $status = $scoper->data_sources->get_from_http_post('status', $src) )
			if ( $object_id )
				$status = $scoper->data_sources->get_from_db('status', $src, $object_id);
		
		if ( ! $object_type = $scoper->data_sources->detect('type', $src, $object_id) )
			return $selected_terms;

	
		// make sure _others caps are required only for objects current user doesn't own
		$base_caps_only = true;
		if ( ! empty($src->cols->owner) ) {
			$col_owner = $src->cols->owner;
			if ( $object_id ) {
				if ( $object = $scoper->data_sources->get_object($src->name, $object_id) ) {
					global $current_user;
					if ( ! empty($object->$col_owner) && ( $object->$col_owner != $current_user->ID) )
						$base_caps_only = false;
				}
			}
		}
				
		
		if( ! isset( $src->reqd_caps[OP_EDIT_RS][$object_type][$status] ) )
			return $selected_terms;
		
		$reqd_caps = $src->reqd_caps[OP_EDIT_RS][$object_type][$status];

		if ( $base_caps_only ) {
			foreach( $reqd_caps as $key => $cap_name ) {
				if ( $cap_def = $scoper->cap_defs->get( $cap_name ) )
					if ( ! empty($cap_def->base_cap ) ) {
						unset( $reqd_caps[$key] );

						if ( ! in_array($cap_def->base_cap, $reqd_caps) )
							$reqd_caps[] = $cap_def->base_cap;
						
					} elseif ( ! empty($cap_def->owner_privilege) && ! empty($cap_def->status) )  // don't remove edit_posts / edit_pages
						unset( $reqd_caps[$key] );			
			}
			
			if ( empty( $reqd_caps ) )
				return $selected_terms;
		}
		
		// now using $src->reqd_caps array instead
		//if ( $reqd_caps = $scoper->cap_defs->get_matching($src->name, $object_type, OP_EDIT_RS, $status, $base_caps_only) ) {
			
			$user_terms = $scoper->qualify_terms_daterange($reqd_caps, $taxonomy);
			
			foreach ( array_keys($user_terms) as $date_key ) {
				$date_clause = '';
				
				if ( $date_key && is_serialized($date_key) ) {
					// Check stored post date against any role date limits associated whith this set of terms (if not stored, check current date)
					
					$content_date_limits = unserialize($date_key);
					
					$post_date_gmt = ( $object_id ) ? $scoper->data_sources->get_from_db('date', $src, $object_id) : 0;
					
					if ( ! $post_date_gmt )
						$post_date_gmt = agp_time_gmt();

					if ( ( $post_date_gmt < $content_date_limits->content_min_date_gmt ) || ( $post_date_gmt > $content_date_limits->content_max_date_gmt ) )
						unset( $user_terms[$date_key] );
				}
			}
			
			$user_terms = agp_array_flatten( $user_terms );
			$selected_terms = array_intersect($selected_terms, $user_terms);
		//}

		return $selected_terms;
	}
	
	
	// This handler is meant to fire whenever a term is inserted or updated.
	// If the client does use such a hook, we will force it by calling internally from mnt_create and mnt_edit
	function scoper_mnt_save_term($taxonomy, $args, $term_id, $term = '') {
		static $saved_terms;
		
		if ( ! isset($saved_terms) )
			$saved_terms = array();
	
		// so this filter doesn't get called by hook AND internally
		if ( isset($saved_terms[$taxonomy][$term_id]) )
			return;
			
		
		global $scoper;
			
		// parent settings can affect the auto-assignment of propagating roles/restrictions
		$set_parent = 0;
		
		if ( $col_parent = $scoper->taxonomies->member_property($taxonomy, 'source', 'cols', 'parent') ) {
			$tx_src_name = $scoper->taxonomies->member_property($taxonomy, 'source', 'name');
			
			$set_parent = $scoper->data_sources->get_from_http_post('parent', $tx_src_name);
		}

		if ( empty($term_id) )
			$term_id = $scoper->data_sources->get_from_http_post('id', $tx_src_name);
		
		$saved_terms[$taxonomy][$term_id] = 1;
		
		// Determine whether this object is new (first time this RS filter has run for it, though the object may already be inserted into db)
		$last_parent = 0;
		
		$last_parents = get_option( "scoper_last_{$taxonomy}_parents" );
		if ( ! is_array($last_parents) )
			$last_parents = array();
		
		if ( ! isset($last_parents[$term_id]) ) {
			$is_new_term = true;
			$last_parents = array();
		} else
			$is_new_term = false;
		
		if ( isset( $last_parents[$term_id] ) )
			$last_parent = $last_parents[$term_id];

		if ( ($set_parent != $last_parent) && ($set_parent || $last_parent) ) {
			$last_parents[$term_id] = $set_parent;
			update_option( "scoper_last_{$taxonomy}_parents", $last_parents);
		}
		
		$roles_customized = false;
		if ( ! $is_new_term )
			if ( $custom_role_objects = get_option( "scoper_custom_{$taxonomy}" ) )
				$roles_customized = isset( $custom_role_objects[$term_id] );
			
		// Inherit parent roles / restrictions, but only for new terms, 
		// or if a new parent is set and no roles have been manually assigned to this term
		if ( $is_new_term || ( ! $roles_customized && ($set_parent != $last_parent) ) ) {
			// apply default roles for new term
			if ( $is_new_term )
				scoper_inherit_parent_roles($term_id, TERM_SCOPE_RS, $taxonomy, 0);
			else {
				$args = array( 'inherited_only' => true, 'clear_propagated' => true );
				ScoperAdminLib::clear_restrictions(TERM_SCOPE_RS, $taxonomy, $term_id, $args);
				ScoperAdminLib::clear_roles(TERM_SCOPE_RS, $taxonomy, $term_id, $args);
			}
			
			// apply propagating roles,restrictions from specific parent
			if ( $set_parent ) {
				scoper_inherit_parent_roles($term_id, TERM_SCOPE_RS, $taxonomy, $set_parent);
				scoper_inherit_parent_restrictions($term_id, TERM_SCOPE_RS, $taxonomy, $set_parent);
			}
		} // endif new parent selection (or new object)
		
		scoper_term_cache_flush();
		delete_option( "{$taxonomy}_children_rs" );
		delete_option( "{$taxonomy}_ancestors_rs" );
	}
	
	
function scoper_get_parent_restrictions($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '') {
	global $wpdb, $scoper;
	
	$role_clause = '';
		
	if ( ! $parent_id && (OBJECT_SCOPE_RS == $scope) ) {
		// for default restrictions, need to distinguish between otype-specific roles 
		// (note: this only works w/ RS role type. Default object restrictions are disabled for WP role type because we'd be stuck setting all default restrictions to both post & page.)
		$src = $scoper->data_sources->get($src_or_tx_name);
		if ( ! empty($src->cols->type) ) {
			if ( ! $object_type )
				$object_type = scoper_determine_object_type($src_name, $object_id);
				
			if ( $object_type ) {
				$role_type = SCOPER_ROLE_TYPE;
				$role_defs = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_or_tx_name, $object_type);
				if ( $role_names = scoper_role_handles_to_names( array_keys($role_defs) ) )
					$role_clause = "AND role_type = '$role_type' AND role_name IN ('" . implode("', '", $role_names) . "')";
			}
		}
	}
		
	// Since this is a new object, propagate restrictions from parent (if any are marked for propagation)
	$qry = "SELECT * FROM $wpdb->role_scope_rs WHERE topic = '$scope' AND require_for IN ('children', 'both') $role_clause AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id = '$parent_id' ORDER BY role_type, role_name";
	$results = scoper_get_results($qry);
	return $results;
}

function scoper_inherit_parent_restrictions($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '', $parent_restrictions = '') {
	global $scoper;

	if ( ! $parent_restrictions )
		$parent_restrictions = scoper_get_parent_restrictions($obj_or_term_id, $scope, $src_or_tx_name, $parent_id); 
	
	if ( $parent_restrictions ) {
		$role_assigner = init_role_assigner();

		if ( OBJECT_SCOPE_RS == $scope )
			$role_defs = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_or_tx_name, $object_type);
		else
			$role_defs = $scoper->role_defs->get_all();
		
		foreach ( $parent_restrictions as $row ) {
			$role_handle = scoper_get_role_handle($row->role_name, $row->role_type);
			if ( isset($role_defs[$role_handle]) ) {
				$inherited_from = ( $row->obj_or_term_id ) ? $row->requirement_id : 0;
			
				$args = array ( 'is_auto_insertion' => true, 'inherited_from' => $inherited_from );
				
				$role_assigner->insert_role_restrictions ($scope, $row->max_scope, $role_handle, $src_or_tx_name, $obj_or_term_id, 'both', $row->requirement_id, $args);
				$did_insert = true;	
			}
		}
		
		if ( ! empty($did_insert) )
			$role_assigner->role_restriction_aftermath( $scope );
	}
}

function scoper_get_parent_roles($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '') {
	global $wpdb, $scoper;

	$role_clause = '';
		
	if ( ! $parent_id && (OBJECT_SCOPE_RS == $scope) ) {
		// for default roles, need to distinguish between otype-specific roles 
		// (note: this only works w/ RS role type. Default object roles are disabled for WP role type because we'd be stuck assigning all default roles to both post & page.)
		$src = $scoper->data_sources->get($src_or_tx_name);
		if ( ! empty($src->cols->type) ) {
			if ( ! $object_type )
				$object_type = scoper_determine_object_type($src_name, $object_id);
				
			if ( $object_type ) {
				$role_type = SCOPER_ROLE_TYPE;
				$role_defs = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_or_tx_name, $object_type);
				if ( $role_names = scoper_role_handles_to_names( array_keys($role_defs) ) )
					$role_clause = "AND role_type = '$role_type' AND role_name IN ('" . implode("', '", $role_names) . "')";
			}
		}
	}
	
	// Since this is a new object, propagate roles from parent (if any are marked for propagation)
	$qry = "SELECT * FROM $wpdb->user2role2object_rs WHERE scope = '$scope' AND assign_for IN ('children', 'both') $role_clause AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id = '$parent_id' ORDER BY role_type, role_name";
	$results = scoper_get_results($qry);
	return $results;
}

function scoper_inherit_parent_roles($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '', $parent_roles = '') {
	global $scoper;

	if ( ! $parent_roles )
		$parent_roles = scoper_get_parent_roles($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type); 

	if ( $parent_roles ) {
		$role_assigner = init_role_assigner();
		
		if ( OBJECT_SCOPE_RS == $scope )
			$role_defs = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_or_tx_name, $object_type);
		else
			$role_defs = $scoper->role_defs->get_all();
			
		$role_handles = array_keys($role_defs);
		
		$role_bases = array();
		if ( GROUP_ROLES_RS )
			$role_bases []= ROLE_BASIS_GROUPS;
		if ( USER_ROLES_RS )
			$role_bases []= ROLE_BASIS_USER;
		
		foreach ( $role_bases as $role_basis ) {
			$col_ug_id = ( ROLE_BASIS_GROUPS == $role_basis ) ? 'group_id' : 'user_id';

			foreach ( $role_handles as $role_handle ) {
				$agents = array();
				$inherited_from = array();
				
				$role_duration_per_agent = array();
				$content_date_limits_per_agent = array();
				
				foreach ( $parent_roles as $row ) {
					$ug_id = $row->$col_ug_id;
					$row_role_handle = scoper_get_role_handle($row->role_name, $row->role_type);
					if ( $ug_id && ($row_role_handle == $role_handle) ) {

						$agents[$ug_id] = 'both';
					
						// Default roles for new objects are stored as direct assignments with no inherited_from setting.
						// 1) to prevent them from being cleared when page parent is changed with no custom role settings in place
						// 2) to prevent them from being cleared when the default for new pages is changed
						if ( $row->obj_or_term_id )
							$inherited_from[$ug_id] = $row->assignment_id;
							
						$role_duration_per_agent[$ug_id] = array( 'date_limited' => $row->date_limited, 'start_date_gmt' => $row->start_date_gmt, 'end_date_gmt' => $row->end_date_gmt );
						$content_date_limits_per_agent[$ug_id] = array( 'content_date_limited' => $row->content_date_limited, 'content_min_date_gmt' => $row->content_min_date_gmt, 'content_max_date_gmt' => $row->content_max_date_gmt );
					}
				}
				
				if ( $agents ) {
					$args = array ( 'is_auto_insertion' => true, 'inherited_from' => $inherited_from, 'role_duration_per_agent' => $role_duration_per_agent, 'content_date_limits_per_agent' => $content_date_limits_per_agent );
					$role_assigner->insert_role_assignments ($scope, $role_handle, $src_or_tx_name, $obj_or_term_id, $col_ug_id, $agents, array(), $args);
				}
			}
		}
	}
}
	
?>