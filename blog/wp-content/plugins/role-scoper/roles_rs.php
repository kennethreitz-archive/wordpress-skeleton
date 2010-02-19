<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('lib/agapetry_config_items.php');

class WP_Scoped_Roles extends AGP_Config_Items {
	var $cap_defs;		// object reference to WP_Scoped_Capabilities object
	var $role_caps = array();
	var $role_caps_anon = array();
	var $role_types = array();
	var $display_names = array();	// display_names, abbrevs necessary for WP roles.
	var $abbrevs = array();
	var $micro_abbrevs = array();
	
	function WP_Scoped_Roles(&$cap_defs, $role_types) {
		$this->cap_defs =& $cap_defs;
		$this->role_types = $role_types;

		foreach ( $cap_defs->get_all() as $cap_name => $cap )
			if ( ! empty($cap->anon_user_has) )
				$this->role_caps_anon[$cap_name] = 1;
	}

	function get_display_name( $role_handle, $context = '' ) {
		if ( isset( $this->display_names[$role_handle] ) )
			return $this->display_names[$role_handle];
		
		require_once( 'roles-strings_rs.php' );
		return ScoperRoleStrings::get_display_name( $role_handle, $context );
	}
	
	function get_abbrev( $role_handle, $context = '' ) {
		if ( isset( $this->abbrevs[$role_handle] ) )
			return $this->abbrevs[$role_handle];
		
		require_once( 'roles-strings_rs.php' );
		return ScoperRoleStrings::get_abbrev( $role_handle, $context );
	}
	
	function get_micro_abbrev( $role_handle, $context = '' ) {
		if ( isset( $this->micro_abbrevs[$role_handle] ) )
			return $this->micro_abbrevs[$role_handle];

		require_once( 'roles-strings_rs.php' );
		
		if( ! $return = ScoperRoleStrings::get_micro_abbrev( $role_handle, $context ) )
			$return = ScoperRoleStrings::get_abbrev( $role_handle, $context );
		
		return $return;
	}
	
	function &add($name, $defining_module_name, $display_name = '', $abbrev = '', $role_type = 'rs', $args = '') {
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a role (%2$s).', $defining_module_name, $name)
					. '<br /><br />' . 'This must be done via the define_data_sources_rs hook.';
			rs_notice($notice);
			return;
		}
		
		$key = ( $name == ANON_ROLEHANDLE_RS ) ? $name : scoper_get_role_handle($name, $role_type);
		
		if ( 'wp' == $role_type ) {
			if ( ! $display_name )
				$display_name = ucwords( str_replace('_', ' ', $name) );
								
			if ( ! $abbrev )
				$abbrev = $display_name;
		}
			
		if ( $display_name )
			$this->display_names[$key] = $display_name;
			
		if ( $abbrev )
			$this->abbrevs[$key] = $abbrev;

		
		if ( isset($this->members[$key]) )
			unset($this->members[$key]);
			
		$this->members[$key] = new WP_Scoped_Role($name, $defining_module_name, $role_type, $args);
		$this->process($this->members[$key]);
		
		return $this->members[$key];
	}
	
	function add_role_capability($name, $cap_name, $restricted_object_assignment = 0) {
		if ( $this->locked ) {
			rs_notice('A plugin or theme is too late in its attempt to add a capability for the following role' . ': ' . $name . '<br />' . 'This must be done via the define_roles_rs hook.');
			return;
		}
	
		$assignment_type = ( $restricted_object_assignment ) ? EXCLUSIVE_OBJECT_ASSIGNMENT_RS : SUPPLEMENTAL_OBJECT_ASSIGNMENT_RS;
		
		$this->role_caps[$name][$cap_name] = $assignment_type;
		$this->process($this->members[$name]);
	}
	
	function process( &$role_def ) {
		// role type was prefixed for array key, but should remove for name property
		foreach ( $this->role_types as $role_type )
			$role_def->name = str_replace("{$role_type}_", '', $role_def->name);
		
		if ( ! isset($role_def->valid_scopes) )
			$role_def->valid_scopes = array('blog' => 1, 'term' => 1, 'object' => 1);
			
		if ( ! isset($role_def->object_type) )
			$role_def->object_type = '';
	}
	
	function add_role_caps( $user_role_caps ) {
		if ( ! is_array( $user_role_caps ) )
			return;
		
		foreach ( array_keys($this->role_caps) as $role_handle )
			if ( ! empty($user_role_caps[$role_handle]) )
				$this->role_caps[$role_handle] = array_merge($this->role_caps[$role_handle], $user_role_caps[$role_handle]);
	}
	
	function remove_role_caps( $disabled_role_caps ) {
		if ( ! is_array( $disabled_role_caps ) )
			return;
		
		foreach ( array_keys($this->role_caps) as $role_handle )
			if ( ! empty($disabled_role_caps[$role_handle]) )
				$this->role_caps[$role_handle] = array_diff_key($this->role_caps[$role_handle], $disabled_role_caps[$role_handle]);
	}
	
	function filter_role_handles_by_type($role_handles, $role_type) {
		$qualifying_handles = array();
		
		foreach ( array_keys($this->members) as $role_handle)
			if ( $role_type == $this->members[$role_handle]->role_type )
				$qualifying_handles []= $role_handle;
				
		return array_intersect($role_handles, $qualifying_handles);
	}
	
	function filter_roles_by_type($roles, $role_type) {
		$qualifying_handles = array();
		
		if ( ! $roles )
			return array();
		
		foreach ( array_keys($this->members) as $role_handle)
			if ( $role_type == $this->members[$role_handle]->role_type )
				$qualifying_handles [$role_handle] = 1;
		
		return array_intersect_key($roles, $qualifying_handles);
	}
	
	// return roledefs which match the specified parameters
	function get_matching($role_types = '', $src_names = '', $object_types = '', $op_types = '') {
		$arr = array();

		if ( $role_types && ! is_array($role_types) )
			$role_types = array($role_types);
			
		if ( $src_names && ! is_array($src_names) )
			$src_names = array($src_names);
	
		if ( $object_types && ! is_array($object_types) )
			$object_types = array($object_types);
			
		foreach ( $this->members as $role_handle => $role_def ) {			
			if ( ! $role_types || in_array($role_def->role_type, $role_types) ) {
				if ( ! $src_names ) {
					$arr[$role_handle] = $role_def;
					continue;
				}
				
				if ( isset($this->role_caps[$role_handle]) ) {
					foreach ( array_keys($this->role_caps[$role_handle]) as $cap_name ) {
						if ( ! $cap_def = $this->cap_defs->get($cap_name) )
							continue;

						if ( 
							( ! $src_names || in_array($cap_def->src_name, $src_names) )
						&& 	( ! $op_types || in_array($cap_def->op_type, $op_types) )
						&& 	( ! $object_types || in_array($cap_def->object_type, $object_types)
								// special provision for 'read' cap and any others which apply to multiple object types
								|| ( ! $cap_def->object_type && isset($role_def->object_type) && in_array($role_def->object_type, $object_types) ) 
							)
						) {
							$arr[$role_handle] = $role_def;
							continue 2;
						} // endif cap properties match this function call args
					} // end foreach role_cap
				} // endif role_caps isset
				
				// special provision for 'read' cap with WP-defined roles
				if ( ('wp' == SCOPER_ROLE_TYPE) && isset($this->role_caps[$role_handle]['read']) && in_array('post', $src_names) ) {
					$arr[$role_handle] = $role_def;
				}
			} // endif role_type
		} // end foreach members
		
		return $arr;
	}
	
	function get_for_taxonomy($src, $taxonomy = '', $args = '') {
		$defaults = array( 'one_otype_per_role' => true, 'ignore_usage_settings' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
	
		if ( ! $src)
			return;
	
		$otype_roles = array();
		
		foreach ( array_keys($src->object_types) as $object_type ) {
			if ( ! $ignore_usage_settings && ! scoper_get_otype_option('use_term_roles', $src->name, $object_type) )
				continue;

			if ( $roles = $this->get_matching( SCOPER_ROLE_TYPE, $src->name, $object_type ) ) {
				if ( $one_otype_per_role )
					foreach ( array_keys($otype_roles) as $existing_object_type )
						$roles = array_diff_key($roles, $otype_roles[$existing_object_type]);
				
				$otype_roles[$object_type] = $roles;
			}
		}
			
		//note: term roles are defined with src_name property corresponding to their object source (i.e. manage_categories has src_name 'post')
		if ( $taxonomy ) {
			if ( $roles = $this->get_matching( SCOPER_ROLE_TYPE, $src->name, $taxonomy, array(OP_ADMIN_RS) ) ) {
				if ( $one_otype_per_role )
					foreach ( array_keys($otype_roles) as $object_type )
						$roles = array_diff_key($roles, $otype_roles[$object_type]);
				
				if ( $roles )
					$otype_roles[$taxonomy] = $roles;	
			}
		}
		
		return $otype_roles;
	}
	
	function add_contained_roles($assigned, $term_array = false) {
		if ( empty($assigned) )
			return array();
	
		if ( ! is_array($assigned) )
			$assigned = array($assigned);

		if ( $term_array ) {
			$role_terms = $assigned;

			// $assigned roles[role_key] = array of terms for which the role is assigned.
			// Add contained roles directly into the provided assigned_roles array
			foreach ( $assigned as $assigned_role_handle => $terms ) {
				
				// if a user has role assigned for term(s), he also effectively has all its contained roles assigned for same term(s)  	
				foreach ( array_keys( $this->get_contained_roles($assigned_role_handle, true) ) as $contained_role_handle ) {
					
					// may or may not already have roles assigned explicitly or via containment in another assigned role
					if ( ! isset($role_terms[$contained_role_handle]) )
						$role_terms[$contained_role_handle] = $terms;
					else
						$role_terms[$contained_role_handle] = array_unique( array_merge($role_terms[$contained_role_handle], $terms) );
				}
			}
			
			return $role_terms;
			
		} else {
			$roles = $assigned;
			foreach ( array_keys($assigned) as $assigned_role_handle ) {
				if ( $contained_roles = $this->get_contained_roles($assigned_role_handle) )
					$roles = array_merge( $roles, $contained_roles );
			}
			return $roles;
		}
	}
	
	function add_containing_roles($roles, $role_type = '') {
		$return_roles = $roles;
	
		foreach ( array_keys($roles) as $role_handle )
			if ( $containing = $this->get_containing_roles($role_handle, $role_type) )
				$return_roles = array_merge($return_roles, $containing);
				
		return $return_roles;
	}
	
	function get_containing_roles($role_handle, $role_type = '') {
		if ( ! isset($this->role_caps[$role_handle]) )
			return array();
		
		$containing_roles = array();
		foreach ( array_keys($this->role_caps) as $other_role_handle )
			if ( $other_role_handle != $role_handle )
				if ( ! array_diff_key($this->role_caps[$role_handle], $this->role_caps[$other_role_handle]) )
					$containing_roles[$other_role_handle] = 1;
		
		if ( $role_type ) {
			if ( $containing_roles = $this->filter_role_handles_by_type(array_keys($containing_roles), $role_type) )
				$containing_roles = array_flip($containing_roles);
		}
			
		return $containing_roles;
	}
	
	function get_contained_roles($role_handles, $include_this_role = false, $role_type = '') {
		if ( ! $role_handles )
			return array();

		if ( ! is_array($role_handles) )
			$role_handles = array($role_handles);
		
		$contained_roles = array();

		foreach ( $role_handles as $role_handle ) {
			if ( ! isset($this->role_caps[$role_handle]) )
				continue;
		
			foreach ( array_keys($this->role_caps) as $other_role_handle ) {
				if ( ($other_role_handle != $role_handle) || $include_this_role )
					if ( ! array_diff_key($this->role_caps[$other_role_handle], $this->role_caps[$role_handle]) )
						if ( $this->role_caps[$other_role_handle] ) { // don't take credit for including roles that have no pertinent caps
							
							// don't count Post Reader as being contained by Page roles, and vice versa	
							$ambiguous_roles = array( 'post' => 'rs_post_reader', 'page' => 'rs_page_reader' );
							if ( in_array( $other_role_handle, $ambiguous_roles ) ) {
								
								$role_info = scoper_explode_role_handle( $role_handle );
								if ( 'rs' == $role_info->role_type ) {
									foreach ( $ambiguous_roles as $a_object_type => $a_role_handle ) {
										if ( $a_role_handle == $other_role_handle ) {
											$a_role_attrib = $this->get_role_attributes( $role_handle );
											if ( ! in_array( $a_object_type, $a_role_attrib->object_types ) )
												continue 2;
										}
									}
								}
							}
							
							$contained_roles[$other_role_handle] = 1;
						}
			}
		}
		
		if ( $role_type ) {
			$filtered_role_handles = $this->filter_role_handles_by_type( array_keys($this->members), $role_type );
			$contained_roles = array_intersect_key($contained_roles, array_flip($filtered_role_handles) );
		}
		
		if ( $contained_roles && ! $include_this_role )
			$contained_roles = array_diff_key( $contained_roles, array_flip($role_handles) );

		return $contained_roles;
	}
	
	function get_role_attributes($role_handle) {
		if ( ! isset($this->role_caps[$role_handle]) )
			return array();
			
		$arr = array( 'src_names' => array(), 'object_types' => array() );

		if ( $src_otypes = $this->cap_defs->organize_caps_by_otype( array_keys($this->role_caps[$role_handle]) ) ) {
			foreach ( $src_otypes as $src_name => $otypes ) {
				$arr['src_names'] []= $src_name;
				$arr['object_types'] = array_unique( array_merge($arr['object_types'], array_keys($otypes) ) );
			}
		} 
		
		if ( ! $arr['object_types'] || ( ( 1 == count($arr['object_types']) ) && ! $arr['object_types'][0] ) ) {
			if ( $role_def = $this->get($role_handle) ) {
				// special provision for 'read' cap and any others which apply to multiple object types
				if ( isset($role_def->object_type) )
					$arr['object_types'] = array($role_def->object_type);
			}
		}
		
		return (object) $arr;
	}
	
	function populate_with_wp_roles() {
		global $wp_roles;
		if ( ! isset($wp_roles) )
			$wp_roles = new WP_Roles();
		
		if ( ('wp' == SCOPER_ROLE_TYPE) && ! empty($this->role_caps_anon) ) {
			scoper_load_textdomain(); // otherwise this is only loaded for wp-admin

			$args = array( 'anon_user_blogrole' => true, 'anon_only' => true, 'valid_scopes' => array('blog' => true, 'term' => true, 'object'=> true) );
			$this->add( 'public_reader', 'wordpress', __('Anonymous Reader', 'scoper'), __('Anonymous Reader', 'scoper'), 'wp', $args);
			
			$this->role_caps['wp_public_reader'] = $this->role_caps_anon;
		}
			
		// populate WP roles least-role-first to match RS roles
		$keys = array_keys($wp_roles->role_objects);
		$keys = array_reverse($keys);

		foreach ( $keys as $role_name ) {
			$role = $wp_roles->role_objects[$role_name];
			
			// remove any WP caps which are in array, but have value = false
			$caps = array_intersect($role->capabilities, array(true) );
			
			// we only care about WP caps that are RS-defined
			if ( $caps && is_array($caps) )
				$caps = array_intersect_key($caps, array_flip($this->cap_defs->get_all_keys()) );

			$this->add( $role_name, 'wordpress', '', '', 'wp' );

			$this->role_caps['wp_' . $role_name] = $caps;
		}
	}

	function get_anon_role_handles() {
		$arr = array();
		
		foreach ( $this->members as $role_handle => $role )
			if ( ! empty($role->anon_user_blogrole) )
				$arr[] = $role_handle;
				
		return $arr;
	}
	
	// reqd_caps: array of cap names and/or role handles.  Role handle format is {$role_type}_{role_name}
	function role_handles_to_caps($reqd_caps, $find_unprefixed_wproles = false) {
		foreach ( $reqd_caps as $role_handle ) {
			if ( isset($this->role_caps[$role_handle]) ) {
				$reqd_caps = array_merge( $reqd_caps, array_keys($this->role_caps[$role_handle]) );	
				$reqd_caps = array_diff( $reqd_caps, array($role_handle) );
			}
		}
			
		if ( $find_unprefixed_wproles ) {
			global $wp_roles;
			foreach ( $reqd_caps as $role_name ) {
				if ( isset($wp_roles->role_objects[$role_name]->capabilities ) ) {
					$reqd_caps = array_merge( $reqd_caps, array_keys($wp_roles->role_objects[$role_name]->capabilities) );	
					$reqd_caps = array_diff( $reqd_caps, array($role_name) );
				}
			}
		}
				
		return array_unique($reqd_caps);
	}
	
	function get_role_ops($role_handle, $src_name = '', $object_type = '') {
		if ( ! isset($this->role_caps[$role_handle]) )
			return array();
			
		$ops = array();
		foreach (array_keys($this->role_caps[$role_handle]) as $cap_name) {
			if ( $cap_def = $this->cap_defs->get($cap_name) )
				if ( ! empty($cap_def->op_type) )
					$ops[$cap_def->op_type] = 1;
		}

		return $ops;
	}

	//$reqd_caps = single cap name string OR array of cap name strings
	//$rolecaps[role_handle] = array of cap names
	// returns array of role_handles
	function qualify_roles($reqd_caps, $role_type = SCOPER_ROLE_TYPE, $object_type = '', $args = '') {
		$defaults = array( 'exclude_object_types' => array(), 'all_wp_caps' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		if ( 'wp' == SCOPER_ROLE_TYPE )
			$object_type = '';
		
		if ( ! is_array($reqd_caps) )
			$reqd_caps = ($reqd_caps) ? array($reqd_caps) : '';
		
		$reqd_caps = $this->role_handles_to_caps($reqd_caps, true); // arg: also check for unprefixed WP rolenames
		
		extract($args);
		
		if ( is_array($role_type) ) {
			if ( count($role_type) == 1 )
				$role_type = current($role_type);
			elseif ( in_array(SCOPER_ROLE_TYPE, $role_type) && in_array('wp', $role_type) )
				$role_type = '';
			else
				$role_type = SCOPER_ROLE_TYPE;
		}
		
		// WP roles are always defined.  Skip them if scoping with RS roles and this request is for taxonomy/object scope 
		if ( $role_type )
			$role_handles = $this->filter_role_handles_by_type( array_keys($this->members), $role_type );
		else
			$role_handles = array_keys($this->members);
			
		$good_roles = array();
		foreach ( $role_handles as $role_handle )
			if ( isset($this->role_caps[$role_handle]) ) {
				
				if ( $all_wp_caps && ( 0 === strpos( $role_handle, 'wp_' ) ) ) {
					global $wp_roles;
					if ( isset( $wp_roles->roles[ substr($role_handle, 3) ]['capabilities'] ) )
						$role_caps = $wp_roles->roles[ substr($role_handle, 3) ]['capabilities'];
					else
						$role_caps = $this->role_caps[$role_handle];
				} else
					$role_caps = $this->role_caps[$role_handle];
				
				if ( ! array_diff($reqd_caps, array_keys($role_caps) ) ) {
					
					// the role qualifies unless its object type is a mismatch
					if ( $object_type && ($object_type != $this->members[$role_handle]->object_type) ) {
						$matched = false;
						foreach ( array_keys($this->role_caps[$role_handle]) as $cap_name ) {
							if ( $object_type == $this->cap_defs->member_property($cap_name, 'object_type') ) {
								$matched = true;
								break;
							}
						}
						if ( ! $matched )
							continue; // don't add the role unless it or one of its caps matches objtype	
					}
					
					// complication due to ambiguous 'read' cap (otherwise blog-ownership of page roles circumvents exclusivity check for get_terms display)
					if ( $exclude_object_types && ( 0 !== strpos($role_handle, 'wp_') ) ) {  // but wp roles are not type-specific, so don't exclude them by type
						foreach ( $exclude_object_types as $exclude_type ) {
							if ( $exclude_type == $this->member_property($role_handle, 'object_type') )
								continue 2;	
							
							foreach ( array_keys($this->role_caps[$role_handle]) as $cap_name )
								if ( $exclude_type == $this->cap_defs->member_property($cap_name, 'object_type') )
									continue 3;	
						}
					}
					
					$good_roles[$role_handle] = 1;
				}
			}
		
		return $good_roles;
	}
	
	// returns array of role names which have the required caps (or their basecap equivalent)
	// AND have been applied to at least one object, for any user or group
	function qualify_object_roles($reqd_caps, $object_type = '', $applied_obj_roles = '', $base_caps_only = true) {
		$roles = array();
		
		if ( $base_caps_only )
			$reqd_caps = $this->cap_defs->get_base_caps($reqd_caps);
		
		$roles = $this->qualify_roles($reqd_caps, SCOPER_ROLE_TYPE, $object_type);
		
		if ( ! $applied_obj_roles )
			$applied_obj_roles = $this->get_applied_object_roles();
		
		return array_intersect_key( $roles, $applied_obj_roles );
	}

	
	// returns array of role_handles which have been applied to any object
	// if $user arg is supplied, returns only roles applied for that user (or that user's groups) 
	function get_applied_object_roles( $user = '' ) {
		$SCOPER_ROLE_TYPE = SCOPER_ROLE_TYPE;
		
		if ( is_object( $user ) ) {
			$cache_flag = SCOPER_ROLE_TYPE . '_object-roles';			// v 1.1: changed cache key from "object_roles" to "object-roles" to match new key format for blog, term roles
			$cache = $user->cache_get($cache_flag);
			
			$limit = '';
			$u_g_clause = $user->get_user_clause('');
			
		} else {
			$cache_flag = SCOPER_ROLE_TYPE . '_applied_object-roles';	// v 1.1: changed cache key from "object_roles" to "object-roles" to match new key format for blog, term roles
			$cache_id = 'all';
			$cache = wpp_cache_get($cache_id, $cache_flag);
			
			$u_g_clause = '';
		}
		
		if ( is_array($cache) )
			return $cache;
		
		$role_handles = array();
			
		global $wpdb;
		
		// object roles support date limits, but content date limits (would be redundant and a needless performance hit)
		$duration_clause = scoper_get_duration_clause( '', $wpdb->user2role2object_rs );
		
		if ( $role_names = scoper_get_col("SELECT DISTINCT role_name FROM $wpdb->user2role2object_rs WHERE role_type='$SCOPER_ROLE_TYPE' AND scope='object' $duration_clause $u_g_clause") ) {
			
			$role_handles = scoper_role_names_to_handles($role_names, SCOPER_ROLE_TYPE, true); //arg: return role keys as array key
			
			//$role_handles = array_intersect_key($role_handles, $this->members);
		}
		
		if ( is_object($user) )
			$user->cache_set($role_handles, $cache_flag);
		else
			wpp_cache_set($cache_id, $role_handles, $cache_flag);
		
		return $role_handles;
	}

	
	// Currently, new custom-defined post, page or link roles are problematic because objects or categories with all roles restricted 
	// will suddenly be non-restricted to users whose WP role contains the newly defined RS role.
	//
	// TODO: make all custom-defined roles default restricted
	function remove_invalid() {
		if ( 'rs' == SCOPER_ROLE_TYPE ) {
			if ( $custom_members = array_diff( array_keys($this->members), array( 'rs_post_reader', 'rs_private_post_reader', 'rs_post_contributor', 'rs_post_author', 'rs_post_revisor', 'rs_post_editor', 'rs_page_reader', 'rs_private_page_reader', 'rs_page_contributor', 'rs_page_author', 'rs_page_revisor', 'rs_page_editor', 'rs_page_associate', 'rs_link_editor', 'rs_category_manager', 'rs_group_manager' ) ) ) {
				foreach ( $custom_members as $role_handle ) {
					if ( $role_attrib = $this->get_role_attributes($role_handle) ) {
						if ( in_array( 'post', $role_attrib->src_names ) ) {
							if ( array_intersect( $role_attrib->object_types, array( 'post', 'page' ) ) ) {
								unset( $this->members[$role_handle] );
								continue;
							}
						}
						
						if ( in_array('link', $role_attrib->src_names) && in_array('link', $role_attrib->object_types) )
							unset( $this->members[$role_handle] );
					}
				}
			}
		}
	}
} // end class WP_Scoped_Roles

class WP_Scoped_Role extends AGP_Config_Item {
	var $valid_scopes;
	var $role_type;
	var $objscope_equivalents;
	var $anon_user_has;
	
	function WP_Scoped_Role($name, $defining_module_name, $role_type = 'rs', $args = '' ) {
		$this->AGP_Config_Item($name, $defining_module_name, $args);
		
		$this->role_type = $role_type;
	}
}
?>