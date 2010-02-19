<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
/**
 * UsersInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * users-interceptor_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
 
class UsersInterceptor_RS
{
	var $scoper;

	function UsersInterceptor_RS() {
		global $scoper;
		$this->scoper =& $scoper;	
		
		// ---------------- HANDLERS for ROLE SCOPER HOOKS ---------------
		// 
		// args: ($where, $reqd_caps='', $object_src_name, $object_id='') 
		// Note: If any of the optional args are missing or nullstring, an attempt is made
		// to determine them from URI based on Scoped_DataSource properties
		add_filter('users_request_rs', array(&$this, 'flt_users_request'), 50, 5);
	}
	
	function get_all_terms_count($taxonomy) {
		if ( ! isset($this->scoper->all_terms_count[$taxonomy]) )
			$this->scoper->all_terms_count[$taxonomy] = $this->scoper->get_terms($taxonomy, UNFILTERED_RS, COL_COUNT_RS);
		
		return $this->scoper->all_terms_count[$taxonomy];
	}
	
	// if an src_name and object_id are provided, returns all roles which require object assignment for that object
	// otherwise, returns all roles which require object assignment for any object
	//
	// returns array[rolename] = 1
	//	  OR (if rolename specified) 
	// returns boolean
	function get_objscope_roles($src_name, $object_id, $role_handle = '') {
		static $objscope_objects;
		
		if ( ! isset($objscope_objects) )
			$objscope_objects = array();
		
		$objscope_roles = array();
		$args = array( 'id' => $object_id );

		if ( $objscope_objects = $this->scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name, $args) ) {
		
			if ( $role_handle )
				$role_handles = (array) $role_handle;
			else {
				$role_handles = array();
				if ( isset($objscope_objects['restrictions']) )
					$role_handles = array_keys($objscope_objects['restrictions']);
					
				if ( isset($objscope_objects['unrestrictions']) )
					$role_handles = array_merge( $role_handles, array_keys($objscope_objects['unrestrictions']) );
			}
		
			foreach ( $role_handles as $this_role_handle )
				// If a restriction is set for this object and role, 
				// OR if the role is default-restricted with no unrestriction for this object...
				if ( isset($objscope_objects['restrictions'][$this_role_handle][$object_id])
				|| ( isset($objscope_objects['unrestrictions'][$this_role_handle]) && is_array($objscope_objects['unrestrictions'][$this_role_handle]) && ! isset($objscope_objects['unrestrictions'][$this_role_handle][$object_id]) ) ) {
					$objscope_roles[$this_role_handle] = 1;
				}
		}
		
		return $objscope_roles;
	}
	
	// $term_roles[role_handle] = 1
	// $taxonomies = array of taxonomy_names
	// $object_terms[taxonomy_name] = array of term_ids 
	function get_unrestricted_term_roles($term_roles, $taxonomies, $object_id, $object_terms = '') {
		$taxonomies = array_intersect($taxonomies, $this->scoper->taxonomies->get_all_keys());
		
		if ( ! $taxonomies )
			return false;

		if ( $object_id && ! is_array($object_terms) )
			$object_terms = array();
			
		$loose_terms = array();
		$all_terms_count = array();
		
		$any_strict_taxonomy = false;
		foreach ($taxonomies as $taxonomy)
			if ( $this->scoper->taxonomies->member_property($taxonomy, 'requires_term') ) {
				$any_strict_taxonomy = true;
				break;
			}
		
		if ( ! $any_strict_taxonomy )
			return $term_roles;
			
		foreach ( $taxonomies as $taxonomy ) {

			if ( $object_id ) {
				// Determine whether any of this object's terms blend in blog_roles
				if ( ! isset($object_terms[$taxonomy]) )
					$object_terms[$taxonomy] = $this->scoper->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS, $object_id);

				$strict_terms = $this->scoper->get_restrictions(TERM_SCOPE_RS, $taxonomy );

				//dump($object_terms[$taxonomy]);
				//dump($strict_terms);
				
				foreach ( array_keys($term_roles) as $role_handle ) {
					if ( isset($strict_terms['unrestrictions'][$role_handle]) ) {
						// role is default restricted, so note if any of its terms have an unrestriction set
						if ( array_intersect($object_terms[$taxonomy], array_keys($strict_terms['unrestrictions'][$role_handle]) ) )
							$loose_terms[$role_handle] = 1;
							
					} else {  
						// role is default unrestricted, so note if none of its terms have a restriction set
						if ( empty($strict_terms['restrictions'][$role_handle]) 
						|| ! array_intersect($object_terms[$taxonomy], array_keys($strict_terms['restrictions'][$role_handle]) ) )
							$loose_terms[$role_handle] = 1;
					}
				}
				
				//dump($loose_terms);
				
			} else {
				// Request is not object-specific. Determine whether any terms blend in blog_roles
				$args = array();
				$args['cols'] = COL_COUNT_RS;
				$strict_count = $this->scoper->get_restrictions(TERM_SCOPE_RS, $taxonomy, $args);
				$all_terms_count = $this->get_all_terms_count($taxonomy);

				foreach ( array_keys($term_roles) as $role_handle ) {
					if ( ! empty($strict_count['unrestrictions'][$role_handle])
					|| empty($strict_count['restrictions'][$role_handle]) || ( $strict_count['restrictions'][$role_handle] < $all_terms_count ) )
						$loose_terms[$role_handle] = 1;
				}
			}

			// if all term_roles are already known loose, no need to check any other taxonomies
			if ( $loose_terms )
				if ( ! array_diff_key($term_roles, $loose_terms) )
					return $term_roles;
		} // end foreach taxonomies

		return $loose_terms;
	}
	
	// if an object_id is provided, object_src_name must also be included
	function flt_users_where($where, $reqd_caps = '', $object_src_name = '', $object_id = '', $args = '') {
		if ( ! USER_ROLES_RS && ! GROUP_ROLES_RS )
			return $where;

		global $wpdb;
		static $stored_owner_id;
		
		if ( ! isset($stored_owner_id) )
			$stored_owner_id = array();
		
		$defaults = array('use_term_roles' => 1, 'use_blog_roles' => 1, 'skip_object_roles' => 0, 'querying_groups' => 0, 
						  'ignore_group_roles' => false, 'ignore_user_roles' => false, 'object_type' => '',
						  'objscope_roles' => '', 'preserve_or_clause' => '', 'enforce_duration_limits' => true, 'enforce_content_date_limits' => true );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		// Default to not honoring custom user caps, but support option
		$custom_user_blogcaps = SCOPER_CUSTOM_USER_BLOGCAPS;
		
		// if reqd_caps are missing, try to determine context from URI
		if ( ! $reqd_caps )
			return $where; // no basis for filtering without required caps

		if ( ! is_array($reqd_caps) )
			$reqd_caps = array($reqd_caps);

		// if rolenames are intermingled with caps in reqd_caps array, convert them to caps
		$reqd_caps = $this->scoper->role_defs->role_handles_to_caps($reqd_caps, true);  //arg: also check for unprefixed WP rolenames

		if ( $object_id && ! $object_src_name )
			$object_id = 0;
		
		if ( $object_id ) {
			foreach ( $reqd_caps as $cap_name ) {
				if ( $meta_caps = map_meta_cap_rs($cap_name, -1, $object_id) ) {
					$reqd_caps = array_diff( $reqd_caps, array($cap_name) );
					$reqd_caps = array_unique( array_merge( $reqd_caps, $meta_caps ) );
				}		
			}
			
			if ( 'post' == $object_src_name && ( $use_term_roles || $use_blog_roles ) ) {
				if ( $post = get_post( $object_id ) )
					$object_date_gmt = $post->post_date_gmt;
			} else
				$object_date_gmt = '';
		}
		
		$owner_has_all_caps = true; // IMPORTANT: set this false downstream as appropriate
		$rs_where = array();
		
		// Group the required caps by object type (as defined by $scoper->cap_defs). 
		// The 2nd arg causes caps without an otype association to be included with a nullstring src_name key
		// The 3rd arg forces caps with a data source other than $object_src to be also lumped in with sourceless caps
		// $caps_by_otype[src_name][object_type] = array of cap names
		$caps_by_otype = $this->scoper->cap_defs->organize_caps_by_otype($reqd_caps, true, $object_src_name, $object_type);

		foreach ( $caps_by_otype as $src_name => $otypes ) {
			// Cap reqs that pertain to other data sources or have no data source association
			// will only be satisfied by blog roles.
			$args['use_term_roles'] = $use_term_roles && ( $src_name == $object_src_name );
			$args['skip_object_roles'] = $skip_object_roles || ( $src_name != $object_src_name );
			
			$this_src_object_id = ( $src_name == $object_src_name ) ? $object_id : 0;
	
			if ( $src_name ) {
				if ( ! $src = $this->scoper->data_sources->get($src_name) )
					continue;

				if ( $this_src_object_id && $args['use_term_roles'] && ! empty($src->uses_taxonomies) ) {
					$args['object_terms'] = array();
					foreach ( $src->uses_taxonomies as $taxonomy ) 
						$args['object_terms'][$taxonomy] = $this->scoper->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS, $this_src_object_id);
				}
			}
			
			foreach ( $otypes as $object_type => $this_otype_caps ) {
				$qry_roles = array();
				
				$args['use_term_roles'] = $args['use_term_roles'] && scoper_get_otype_option( 'use_term_roles', $src_name, $object_type );

				//$caps_by_op = $this->scoper->cap_defs->organize_caps_by_op($this_otype_caps, true); //arg: retain caps which are not scoper-defined
				//foreach ( $caps_by_op as $op => $this_op_caps ) {
				foreach ( $this_otype_caps as $cap_name ) {
					// If supporting custom user blogcaps, a separate role clause for each cap
					// Otherwise (default) all reqd_caps from one role assignment (whatever scope it may be)
					if ( $custom_user_blogcaps ) {
						$reqd_caps_arg = array($cap_name);
					} else {
						$reqd_caps_arg = $this_otype_caps;
						$cap_name = '';
					}
				
					// 'blog' argument forces inclusion of qualifying WP roles even if scoping with RS roles 
					// (will later strip out non-scopable roles for term role / object role clauses)
					$args['roles'] = $this->scoper->role_defs->qualify_roles($reqd_caps_arg, array('rs', 'wp'), '', array( 'all_wp_caps' => true ) );

					if ( $args['roles'] || ! $src_name ) {
						if ( USER_ROLES_RS && ! $ignore_user_roles )
							$qry_roles[$cap_name]['general'][ROLE_BASIS_USER] = $this->users_queryroles($reqd_caps_arg, $src_name, $this_src_object_id, $args );
						
						if ( GROUP_ROLES_RS && ! $ignore_group_roles ) {
							$qry_roles[$cap_name]['general'][ROLE_BASIS_GROUPS] = $this->users_queryroles($reqd_caps_arg, $src_name, $this_src_object_id, $args );
						}
					}
					
					// potentially, a separate set of role clauses for object owner
					if ( $this_src_object_id && $src->cols->owner ) {
						$owner_needs_caps = $this->scoper->cap_defs->remove_owner_caps($reqd_caps_arg);  //returns array of caps the owner needs, after removing which are credited to owners automatically
						
						if ( $owner_needs_caps )
							$owner_has_all_caps = false;
						
						if ( $owner_needs_caps != $reqd_caps_arg ) {
							if ( ! isset($stored_owner_id[$src_name][$this_src_object_id]) ) // DON'T initialize this at top of function 
								$stored_owner_id[$src_name][$this_src_object_id] = scoper_get_var("SELECT {$src->cols->owner} from $src->table WHERE {$src->cols->id} = '$object_id' LIMIT 1");	
						
							if ( $stored_owner_id[$src_name][$this_src_object_id] ) {
								$owner_roles = $this->scoper->role_defs->qualify_roles($owner_needs_caps);
								if ( $args['roles'] = array_diff_key($owner_roles, $args['roles']) ) { // if owners (needing fewer caps) qualify under different roles than other users:

									if ( GROUP_ROLES_RS && ! $ignore_group_roles ) {
										if ( ! isset($owner_groups) )
											$owner_groups = WP_Scoped_User::get_groups_for_user( $stored_owner_id[$src_name][$this_src_object_id] );
											//$owner_groups = scoper_get_col("SELECT $wpdb->user2group_gid_col from $wpdb->user2group_rs WHERE $wpdb->user2group_uid_col = '{$stored_owner_id[$src_name][$this_src_object_id]}'");
										
										if ( $owner_groups )
											$qry_roles[$cap_name]['owner'][ROLE_BASIS_GROUPS] = $this->users_queryroles($owner_needs_caps, $src_name, $this_src_object_id, $args);
									}
									
									if ( USER_ROLES_RS && ! $ignore_user_roles )
										$qry_roles[$cap_name]['owner'][ROLE_BASIS_USER] = $this->users_queryroles($owner_needs_caps, $src_name, $this_src_object_id, $args);

								} // endif owner needs any caps assigned by role
							} //endif stored owner_id found
						} // endif any required caps are automatically granted to owner
					} // endif request is for a specific object from a data source which stores owner_id 
					
					// If not supporting custom blogcaps, we actually passed all of this object type's caps together 
					if ( ! $custom_user_blogcaps )
						break;
				} // end foreach this_otype_caps
				
			
				//d_echo ('scope data');
				//dump($qry_roles);
				
				// ------------ Construct this object type's where clause from $qry_roles: -----------------
				// ( note: if custom user blogcaps are not enabled, all roles stored into one cap_name dimension )
				// $qry_roles[cap_name][general/owner][user/groups]['object'][''] = array of role handles
				// $qry_roles[cap_name][general/owner][user/groups]['term'][taxonomy] = array of role handles
				// $qry_roles[cap_name][general/owner][user/groups]['blog'][role_type] = array of role handles

				// now construct the query for this iteration's operation type
				$table_aliases = array( ROLE_BASIS_USER => 'uro', ROLE_BASIS_GROUPS => 'gro' );
				$SCOPER_ROLE_TYPE = SCOPER_ROLE_TYPE;
				
				if ( ! empty($stored_owner_id) && $owner_has_all_caps && USER_ROLES_RS && ! $ignore_user_roles )
					$ot_where['owner'][ROLE_BASIS_USER] = "uro.user_id = '$stored_owner_id'";
				
				foreach ( $qry_roles as $cap_name => $user_types ) { // note: default is to put qualifying roles from all reqd_caps into a single "cap_name" element
					$ot_where = array();
					
					foreach ( $user_types as $user_type => $role_bases ) {
						foreach ( $role_bases as $role_basis => $scopes ) {
							
							$alias = $table_aliases[$role_basis];

							$content_date_comparison = ( $enforce_content_date_limits && ! empty($object_date_gmt) ) ? "'$object_date_gmt'" : '';
							$duration_clause = scoper_get_duration_clause( $content_date_comparison, $alias, $enforce_duration_limits );	// arg: skip duration clause

							foreach ( $scopes as $scope => $keys ) {
								foreach ( $keys as $key => $role_names ) {
									if ( empty($role_names) )
										continue;
							
									$role_in = "'" . implode("','", $role_names ) . "'";
										
									switch ( $scope ) {
										case OBJECT_SCOPE_RS:
											$id_clause = ( $object_id ) ? "AND $alias.obj_or_term_id = '$object_id'" : '';
											$ot_where[$user_type][$role_basis][$scope][$key] = "$alias.scope = 'object' AND $alias.assign_for IN ('entity', 'both') AND $alias.src_or_tx_name = '$src_name' AND $alias.role_type = '$SCOPER_ROLE_TYPE' AND $alias.role_name IN ($role_in) $duration_clause $id_clause";
											break;
										case TERM_SCOPE_RS:
											$terms_clause = ( $object_id && $args['object_terms'][$key] ) ? "AND $alias.obj_or_term_id IN ('" . implode( "', '", $args['object_terms'][$taxonomy] ) . "')" : '';
											$ot_where[$user_type][$role_basis][$scope][$key] = "$alias.scope = 'term' AND $alias.assign_for IN ('entity', 'both') AND $alias.src_or_tx_name = '$key' $terms_clause AND $alias.role_type = '$SCOPER_ROLE_TYPE' AND $alias.role_name IN ($role_in) $duration_clause";
											break;
										case BLOG_SCOPE_RS:
											$ot_where[$user_type][$role_basis][$scope][$key] = "$alias.scope = 'blog' AND $alias.role_type = '$key' AND $alias.role_name IN ($role_in) $duration_clause";
											break;
									} // end scope switch
								} // end foreach key
								
								if ( ! empty($ot_where[$user_type][$role_basis][$scope]) )	// [key 1 clause] [OR] [key 2 clause] [OR] ...
									$ot_where[$user_type][$role_basis][$scope] = agp_implode(' ) OR ( ', $ot_where[$user_type][$role_basis][$scope], ' ( ', ' ) ');	
							} // end foreach scope
							
							if ( ! empty($ot_where[$user_type][$role_basis]) ) {  // [object scope clauses] [OR] [taxonomy scope clauses] [OR] [blog scope clauses]
								$ot_where[$user_type][$role_basis] = agp_implode(' ) OR ( ', $ot_where[$user_type][$role_basis], ' ( ', ' ) ');	
								
								if ( 'owner' == $user_type ) {
									switch ( $role_basis ) {
										case ROLE_BASIS_GROUPS:
											$ot_where[$user_type][$role_basis] .= "AND gro.group_id IN ('" . implode("', '", $owner_groups) . "')";
											break;
										case ROLE_BASIS_USER:
											$ot_where[$user_type][$role_basis] .= "AND uro.user_id = '$stored_owner_id'";
									} // end role basis switch
								} // endif owner
							} // endif any role clauses for this user_type/role_basis
						} // end foreach role basis (user or groups)
						
						if ( ! empty($ot_where[$user_type]) )   // [group role clauses] [OR] [user role clauses]
							$ot_where[$user_type] = agp_implode(' ) OR ( ', $ot_where[$user_type], ' ( ', ' ) ');	
					} // end foreach user type (general or owner)

					if ( ! empty($ot_where) )  // [general user clauses] [OR] [owner clauses]
						$rs_where[$src_name][$object_type][$cap_name] = agp_implode(' ) OR ( ', $ot_where, ' ( ', ' ) ');
					
				} // end foreach cap name (for optional support of custom user blogcaps)
				
				if ( ! empty($rs_where[$src_name][$object_type]) )  // [cap1 clauses] [AND] [cap2 clauses]
					$rs_where[$src_name][$object_type] = agp_implode(' ) AND ( ', $rs_where[$src_name][$object_type], ' ( ', ' ) ');
			} // end foreach otypes
			
			if ( isset( $rs_where[$src_name]) ) {	// object_type1 clauses [AND] [object_type2 clauses] [AND] ...
				$rs_where[$src_name] = agp_implode(' ) AND ( ', $rs_where[$src_name], ' ( ', ' ) ');	
			}
			
			//if ( isset($rs_where[$src_name]) && is_array($rs_where[$src_name]) ) {  // user basis clause [OR] groups basis clause ...
			//	$rs_where[$src_name] = agp_implode(' ) OR ( ', $rs_where[$src_name], ' ( ', ' ) ');
			//}
		} // end foreach data source
		
		// data_source 1 clauses [AND] [data_source 2 clauses] [AND] ...
		$rs_where = agp_implode(' ) AND ( ', $rs_where, ' ( ', ' ) ');	
		
		if ( $rs_where ) {
			if ( false !== strpos($where, $rs_where) )
				return $where;
			
			if ( ! empty($preserve_or_clause) )
				$rs_where = "( ( $rs_where ) OR ( $preserve_or_clause ) )";
				
			if ( $where )
				$where = " AND ( $rs_where ) $where";
			else
				$where = " AND $rs_where";
			
		} else {
			// if no valid role clauses were constructed, required caps are invalid; no users can do it
			$where =  ' AND 1=2';
		}

		return $where;
	} // end function flt_users_where
	
	
	function users_queryroles ($reqd_caps, $src_name, $object_id = '', $args = '') {
		$defaults = array('roles' => '', 'user' => '', 'querying_groups' => 0,
						'use_term_roles' => 1, 'use_blog_roles' => 1, 'skip_object_roles' => false, 
						'ignore_strict_terms' => 0, 'object_terms' => array(), 'object_type' => '',
						'objscope_roles' => '',	'any_object' => false );
		
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		$src = $this->scoper->data_sources->get($src_name);

		// ---- The following default argument generation is included to support potential direct usage of this function 
		//								(not needed for flt_users_where call -----------------
		
		// Treat empty reqd_caps array as an error
		if ( empty ($reqd_caps) )
			return array();
		
		if ( ! is_array($reqd_caps) )
			$reqd_caps = array($reqd_caps);

		// Calling function may save us a little work if it has already made this call
		if ( ! $roles ) {
			if ( ! $roles = $this->scoper->role_defs->qualify_roles($reqd_caps, array('rs', 'wp') ) )
				return array();
				
		} elseif ( ! is_array($roles) )
			$roles = array($roles);
			
		// this set of reqd_caps cannot be satisfied by any role, either WP-defined or (if scoping with RS roles) RS-defined
		if ( ! $reqd_caps && ! $roles )
			return;
			
		if ( $object_id && ! $src_name )
			$object_id = 0;
		// -----------------------------------------------------------------------------------
	
		
		// Default to not honoring custom user caps, but support option
		$custom_user_blogcaps = SCOPER_CUSTOM_USER_BLOGCAPS;
		
		if ( 'rs' == SCOPER_ROLE_TYPE ) {
			if ( ! $object_type ) {
				$object_types = $this->scoper->cap_defs->object_types_from_caps($reqd_caps);
			
				if ( count($object_types) == 1 ) {
					$src_name = key($object_types);
					
					if ( (count($object_types[$src_name]) == 1) && key($object_types[$src_name]) )
						$object_type = key($object_types[$src_name]);
					else
						$object_type = $this->scoper->data_sources->detect('type', $src, $object_id);
				}
			}

			// RS roles are object type-specific
			$roles_wp = $this->scoper->role_defs->filter_roles_by_type($roles, 'wp');
			
			$roles_rs = $this->scoper->role_defs->filter_roles_by_type($roles, 'rs');
			$this_otype_roles = $this->scoper->role_defs->get_matching('rs', $src_name, $object_type);
			$roles_rs = array_intersect_key($roles_rs, $this_otype_roles);
			
			$roles = array_merge($roles_rs, $roles_wp);
		}
		
		$qualifying_roles = array();
		
		// --------- ACCOUNT FOR OBJECT ROLES -----------
		// If this set of reqd_caps can be satisfied by a scopable role, check for object role assignements
		if ( ! $skip_object_roles && ( $object_id || $any_object ) ) {
		
			// exclude roles which have never been assigned to any object
			if ( $object_roles = $this->scoper->role_defs->qualify_object_roles($reqd_caps, $object_type) )
				$qualifying_roles[OBJECT_SCOPE_RS][''] = scoper_role_handles_to_names(array_keys($roles));
		}
		
		// If this inquiry is for a particular object, find out which roles must be object-assigned for it
		if ( $object_id ) {
			// For term and blog role clauses, exclude roles which require object assignment for that object
			// But don't disqualify a role if any of the roles it "contains" also qualify and are not object-scoped.
			// (i.e. If the required caps are satisfied by admin, editor and contributor, the actual minimum requirement
			// is contributor.  A specification that admin and editor roles "require object assignment" does not apply
			// in this scenario.
			if ( ! is_array($objscope_roles) )
				$objscope_roles = $this->get_objscope_roles($src_name, $object_id, '', true);
			
			if ( $objscope_roles ) {
				$contained_roles = array();
				$roles_wp = $this->scoper->role_defs->filter_roles_by_type($roles, 'wp');
				
				foreach ( array_keys($roles_wp) as $role_handle ) {
					if ( 'rs' == SCOPER_ROLE_TYPE )
						// If scoping with RS roles, this will also have the effect of disqualifying a WP blog role if all of the qualifying RS roles it contains are objscoped.
						$contained_roles[$role_handle] = $this->scoper->role_defs->get_contained_roles( $role_handle, false, 'rs' );
					else
						$contained_roles[$role_handle] = $this->scoper->role_defs->get_contained_roles( $role_handle, true, 'wp'  );	//true: include this role in return array
						
					$contained_roles[$role_handle] = array_intersect_key($contained_roles[$role_handle], $roles);
					
					if ( ! array_diff_key( $contained_roles[$role_handle], $objscope_roles ) )
						unset ($roles[$role_handle]);
				}

				foreach ( array_keys($roles) as $role_handle ) {
					$contained_roles[$role_handle] = $this->scoper->role_defs->get_contained_roles( $role_handle, true, SCOPER_ROLE_TYPE  );	//true: include this role in return array
					
					$contained_roles[$role_handle] = array_intersect_key($contained_roles[$role_handle], $roles);
					
					if ( ! array_diff_key( $contained_roles[$role_handle], $objscope_roles ) )
						unset ($roles[$role_handle]);
				}
			}
		}
		
		// --------- ACCOUNT FOR TERM ROLES -----------
		// Consider term scope settings and role assignments
		//
		if ( $use_term_roles && $src_name && $roles && ! empty($src->uses_taxonomies) ) {

			// If scoping with RS roles, strip out WP role definitions (which were included for blogrole clause)
			$var = SCOPER_ROLE_TYPE;
			$term_roles = ( 'rs' == SCOPER_ROLE_TYPE ) ? $this->scoper->role_defs->filter_roles_by_type($roles, 'rs') : $roles;
			
			if ( $term_roles )
				foreach ( $src->uses_taxonomies as $taxonomy )
					// include users with a sufficient term role assignment in any term
					$qualifying_roles[TERM_SCOPE_RS][$taxonomy] = scoper_role_handles_to_names(array_keys($term_roles));
				
			// Honor blog-wide assignment of any non-objscope role, but only if at least one term
			// is not "strict" (i.e. merges blogroles into term-specific assignments).
			if ( ! $ignore_strict_terms ) {
				$term_roles = $this->get_unrestricted_term_roles($term_roles, $src->uses_taxonomies, $object_id, $object_terms);
				
				// If scoping with RS roles, disqualify a WP blog role if all of the qualifying RS roles it contains were excluded by the strict terms filter.
				if ( ('rs' == SCOPER_ROLE_TYPE) ) {
					if ( $roles_wp = $this->scoper->role_defs->filter_roles_by_type($roles, 'wp') ) {
						$contained_roles = array();
						foreach ( array_keys($roles_wp) as $role_handle ) {
							$contained_roles[$role_handle] = $this->scoper->role_defs->get_contained_roles( $role_handle, false, 'rs' );
							$contained_roles[$role_handle] = array_intersect_key($contained_roles[$role_handle], $roles);
					
							if ( ! $term_roles || ! $contained_roles[$role_handle] || ! array_intersect_key( $contained_roles[$role_handle], $term_roles ) )
								unset ($roles[$role_handle]);
						}
					}
				}
				
				$roles_current = $this->scoper->role_defs->filter_roles_by_type($roles, SCOPER_ROLE_TYPE);
				foreach ( array_keys($roles_current) as $role_handle )
					if ( ! isset($term_roles[$role_handle]) )
						unset ($roles[$role_handle]);			// Since this term role is restricted for all terms, prevent corresponding blog role from being added to qualifying_roles array by subsequent code
			}
		}
		
		// --------- ACCOUNT FOR BLOG ROLES -----------
		// For each qualifying role, recognize blog assignment if the reqd_caps set is not associated 
		// with a defined data source, if this source/object type does not use term roles,
		// or if some of the the terms are not strict.
		//
		// Note that WP blogrole assignments (if not taxonomy or object-scoped) are honored 
		// regardless of Role Scoper role_type setting.
		if ( $use_blog_roles ) {
			if ( $admin_roles = awp_administrator_roles() )
				$roles = ( $roles ) ? array_merge($roles, $admin_roles) : $admin_roles;
			
			if ( $roles ) {
				$role_types = array('rs', 'wp');
				foreach ( $role_types as $role_type ) {
					//if ( ('rs' == $role_type) && ! RS_BLOG_ROLES )  // rs_blog_roles option has never been active in any RS release; leave commented here in case need arises
					//		continue;
				
					$this_type_roles = $this->scoper->role_defs->filter_roles_by_type($roles, $role_type);
					$qualifying_roles[BLOG_SCOPE_RS] [$role_type] = scoper_role_handles_to_names(array_keys($this_type_roles));
				}
			}
			
			if ( $custom_user_blogcaps && $use_blog_roles ) {
				// If custom user blogcaps option is enabled, this function is called separately for each reqd cap.
				// Custom user caps are stored as "hidden" single-cap role of type WP_CAP, sync'd to WP usermeta storage.
				if ( $custom_user_blogcaps )
					$qualifying_roles[BLOG_SCOPE_RS] ['wp_cap'] = $reqd_caps; // ...which contains one cap
			}
		}
		
		return $qualifying_roles;
	}
	
	
	/**
	 * function UsersInterceptor_RS::users_who_can
	 * 
	 * Get all users with required capabilities, applying scoped roles where pertinent.
	 *
	 * reqd_caps: array of capability names, or string value containing single capability name
	 * cols: enumeration COLS_ALL_RS, COL_ID_RS or COLS_ID_DISPLAYNAME_RS. Determines return array dimensions.
	 * object_src_name: object data source name as defined in $scoper->data_sources ( 'post' for posts OR pages )
	 * object_id: array(reqd_cap => object_id), or string value containing single object_id
	 *
	 * Any WP-defined or RS-defined cap may be included to filter users on blog-wide capabilities.
	 *
	 * In addition, object-specific calls filter users for RS-defined caps based on 
	 * Taxonomy/Object role assignment and role scoping requirements.
	 * Any reqd_caps lacking a Role Scoper definition are still tested for blog-wide users roles.
	 *
	 * returns query results: 1D array of user_ids for $cols = COL_ID_RS, otherwise 2D array with all user columns
	 */
	function users_who_can($reqd_caps, $cols = COLS_ALL_RS, $object_src_name = '', $object_id = 0, $args = '' ) {
		global $wpdb;
		
		$defaults = array( 'where' => '', 'orderby' => '', 'disable_memcache' => false, 'group_ids' => '', 'force_refresh' => false, 'force_all_users' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		if ( ! $orderby && ( ( COLS_ALL_RS == $cols ) || ( COLS_ID_DISPLAYNAME_RS == $cols ) ) )
			$orderby = " ORDER BY display_name";
		
		if ( COL_ID_RS == $cols ) {
			if ( $force_all_users )
				$qry = "SELECT ID FROM $wpdb->users";
			else
				$qry = "SELECT DISTINCT uro.user_id AS ID FROM $wpdb->user2role2object_rs AS uro";
		} else {
			if ( COLS_ID_DISPLAYNAME_RS == $cols )
				$qcols = "$wpdb->users.ID, $wpdb->users.display_name";
			elseif ( COLS_ALL_RS == $cols )
				$qcols = "$wpdb->users.*";
			else
				$qcols = $cols;
			
			$qry = "SELECT DISTINCT $qcols FROM $wpdb->users";
			$where = '';
		}

		if ( $reqd_caps || ! $force_all_users ) {
			if ( COL_ID_RS != $cols )
				$qry .= " INNER JOIN $wpdb->user2role2object_rs AS uro ON uro.user_id = $wpdb->users.ID";

			if ( ! is_array($args) )
				$args = array();
				
			if ( isset($args['ignore_user_roles']) )
				unset($args['ignore_user_roles']);
			
			$do_groups = empty($args['ignore_group_roles']);
			
			$args['ignore_group_roles'] = 1;
			
			$args['enforce_duration_limits'] = scoper_get_option( 'role_duration_limits' );
			$args['enforce_content_date_limits'] = scoper_get_option( 'role_content_date_limits' );
			
			//log_mem_usage_rs( 'before flt_users_where' );
			
			$where = $this->flt_users_where($where, $reqd_caps, $object_src_name, $object_id, $args);
			
			//log_mem_usage_rs( 'flt_users_where' );
		}
		
		$id_clause = ( $force_all_users ) ? '' : 'AND uro.user_id > 0';
		
		$qry = "$qry WHERE 1=1 $id_clause $where $orderby";
			
		$qry_key = $qry . serialize($args);
		
		// if we've already run this query before, return the result
		if ( empty($disable_memcache) && isset($this->scoper->user_cache[$qry_key]) )
			return $this->scoper->user_cache[$qry_key];
		
		if ( COL_ID_RS == $cols )
			$users = scoper_get_col($qry);
		else
			$users = scoper_get_results($qry);
			
		//log_mem_usage_rs( 'users query' );
			
		if ( ! empty($do_groups) ) {
			if ( ! empty($args['preserve_or_clause']) && strpos($args['preserve_or_clause'], 'uro.') )
				unset($args['preserve_or_clause']);
			
			if ( ! empty($args['orderby']) )
				unset($args['orderby']);
				
			if ( empty($group_ids) ) {
				$group_ids = $this->groups_who_can($reqd_caps, COL_ID_RS, $object_src_name, $object_id, $args);
			}
			
			if ( ! empty($group_ids) ) {
				if ( defined('ENABLE_PERSISTENT_CACHE') && ! defined('DISABLE_PERSISTENT_CACHE') ) {
					// if persistent cache is enabled, use cached members list for each group instead of querying for all groups
					foreach ( $group_ids as $group_id )
						if ( $group_members = ScoperAdminLib::get_group_members($group_id, $cols, true) )
							$users = array_merge( $users, $group_members );
				} else {
					// avoid separate query for each group if persistent cache is not enabled
					if ( $group_members = ScoperAdminLib::get_group_members($group_ids, $cols, true) )
						$users = array_merge( $users, $group_members );
				}
			}

			if ( COL_ID_RS == $cols )
				$users = array_unique( $users );
			else
				$users = agp_array_unique_md( $users );
		}
		
		$this->scoper->user_cache[$qry_key] = $users;
		
		//log_mem_usage_rs( 'end UsersInt::users_who_can' );
		
		return $users;
	}
	
	function groups_who_can($reqd_caps, $cols = COLS_ALL_RS, $object_src_name = '', $object_id = 0, $args = '' ) {
		global $wpdb;
		
		$defaults = array( 'orderby' => '', 'disable_memcache' => false, 'force_refresh' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		$role_type = SCOPER_ROLE_TYPE;
		$cache_flag = "{$role_type}_groups_who_can";
		$cache_id = md5(serialize($reqd_caps) . $cols . 'src' . $object_src_name . 'id' . $object_id . serialize($args) );

		if ( ! $force_refresh ) {
			$groups = wpp_cache_get($cache_id, $cache_flag);

			if ( is_array($groups) )
				return $groups;
		}
		
		if ( ! is_array($reqd_caps) )
			$reqd_caps = ($reqd_caps) ? array($reqd_caps) : array();
		
		if ( ! $orderby && ( ( COLS_ALL_RS == $cols ) || ( COLS_ID_DISPLAYNAME_RS == $cols ) ) )
			$orderby = " ORDER BY display_name";
			
		if ( ! is_array($args) )
			$args = array();
			
		if ( isset($args['ignore_group_roles']) )
			unset($args['ignore_group_roles']);
			
		$args['ignore_user_roles'] = 1;
		$args['querying_groups'] = 1;
		
		$where = $this->flt_users_where('', $reqd_caps, $object_src_name, $object_id, $args);
		
		if ( COL_ID_RS == $cols ) {
			$qry = "SELECT DISTINCT group_id as ID FROM $wpdb->user2role2object_rs AS gro WHERE 1=1 $where AND gro.group_id > 0 $orderby";

			$groups = scoper_get_col($qry);
		} else {
			$grp = $wpdb->groups_rs;
			$qry = "SELECT DISTINCT $grp.{$wpdb->groups_id_col} AS ID, $grp.{$wpdb->groups_name_col} AS display_name, $grp.$wpdb->groups_descript_col as descript"
				. " INNER JOIN $wpdb->user2group_rs as u2g ON u2g.{$wpdb->user2group_gid_col} = $grp.{$wpdb->groups_id_col}"
				. " INNER JOIN $wpdb->user2role2object_rs AS gro ON $grp.{$wpdb->groups_id_col} = gro.group_id WHERE 1=1 $where $orderby";
		
			$groups = scoper_get_results($qry);
		}
		
		wpp_cache_set($cache_id, $groups, $cache_flag);
		
		return $groups;
	}
	
}



/**
 * Map meta capabilities to primitive capabilities (with option to disregard to current user if user_id === -1)
 *
 * This does not actually compare whether the user ID has the actual capability,
 * just what the capability or capabilities are. Meta capability list value can
 * be 'delete_user', 'edit_user', 'delete_post', 'delete_page', 'edit_post',
 * 'edit_page', 'read_post', or 'read_page'.
 *
 * @since 2.0.0
 *
 * @param string $cap Capability name.
 * @param int $user_id User ID.
 * @return array Actual capabilities for meta capability.
 */
function map_meta_cap_rs( $cap, $user_id ) {
	$args = array_slice( func_get_args(), 2 );
	$caps = array();

	// support usage by RS users_who_can function, which needs to remap meta caps to simple equivalent but builds owner cap adjustment into DB query
	$adjust_for_user = ( -1 !== $user_id );

	switch ( $cap ) {
	case 'delete_user':
		$caps[] = 'delete_users';
		break;
	case 'edit_user':
		if ( !isset( $args[0] ) || $user_id != $args[0] ) {
			$caps[] = 'edit_users';
		}
		break;
	case 'delete_post':
		if ( $adjust_for_user )
			$author_data = get_userdata( $user_id );
		
		//echo "post ID: {$args[0]}<br />";
		$post = get_post( $args[0] );
		if ( 'page' == $post->post_type ) {
			$args = array_merge( array( 'delete_page', $user_id ), $args );
			return call_user_func_array( 'map_meta_cap_rs', $args );
		}
		
		if ( $adjust_for_user )
			$post_author_data = get_userdata( $post->post_author );
		
		//echo "current user id : $user_id, post author id: " . $post_author_data->ID . "<br />";
		// If the user is the author...
		if ( $adjust_for_user && ( $user_id == $post_author_data->ID ) ) {
			// If the post is published...
			if ( 'publish' == $post->post_status )
				$caps[] = 'delete_published_posts';
			else
				// If the post is draft...
				$caps[] = 'delete_posts';
		} else {
			// The user is trying to edit someone else's post.
			$caps[] = 'delete_others_posts';
			// The post is published, extra cap required.
			if ( 'publish' == $post->post_status )
				$caps[] = 'delete_published_posts';
			elseif ( 'private' == $post->post_status )
				$caps[] = 'delete_private_posts';
		}
		break;
	case 'delete_page':
		if ( $adjust_for_user )
			$author_data = get_userdata( $user_id );
		
		//echo "post ID: {$args[0]}<br />";
		$page = get_page( $args[0] );
		
		if ( $adjust_for_user )
			$page_author_data = get_userdata( $page->post_author );

		//echo "current user id : $user_id, page author id: " . $page_author_data->ID . "<br />";
		// If the user is the author...
		if ( $adjust_for_user && ( $user_id == $page_author_data->ID ) ) {
			// If the page is published...
			if ( $page->post_status == 'publish' )
				$caps[] = 'delete_published_pages';
			else
				// If the page is draft...
				$caps[] = 'delete_pages';
		} else {
			// The user is trying to edit someone else's page.
			$caps[] = 'delete_others_pages';
			// The page is published, extra cap required.
			if ( $page->post_status == 'publish' )
				$caps[] = 'delete_published_pages';
			elseif ( $page->post_status == 'private' )
				$caps[] = 'delete_private_pages';
		}
		break;
		// edit_post breaks down to edit_posts, edit_published_posts, or
		// edit_others_posts
	case 'edit_post':
		if ( $adjust_for_user )
			$author_data = get_userdata( $user_id );
		
		//echo "post ID: {$args[0]}<br />";
		$post = get_post( $args[0] );
		if ( 'page' == $post->post_type ) {
			$args = array_merge( array( 'edit_page', $user_id ), $args );
			return call_user_func_array( 'map_meta_cap_rs', $args );
		}
		
		if ( $adjust_for_user )
			$post_author_data = get_userdata( $post->post_author );
		
		//echo "current user id : $user_id, post author id: " . $post_author_data->ID . "<br />";
		// If the user is the author...
		if ( $adjust_for_user && ( $user_id == $post_author_data->ID ) ) {
			// If the post is published...
			if ( 'publish' == $post->post_status )
				$caps[] = 'edit_published_posts';
			else
				// If the post is draft...
				$caps[] = 'edit_posts';
		} else {
			// The user is trying to edit someone else's post.
			$caps[] = 'edit_others_posts';
			// The post is published, extra cap required.
			if ( 'publish' == $post->post_status )
				$caps[] = 'edit_published_posts';
			elseif ( 'private' == $post->post_status )
				$caps[] = 'edit_private_posts';
		}
		break;
	case 'edit_page':
		if ( $adjust_for_user )
			$author_data = get_userdata( $user_id );
		
		//echo "post ID: {$args[0]}<br />";
		$page = get_page( $args[0] );
		
		if ( $adjust_for_user )
			$page_author_data = get_userdata( $page->post_author );
		
		//echo "current user id : $user_id, page author id: " . $page_author_data->ID . "<br />";
		// If the user is the author...
		if ( $adjust_for_user && ( $user_id == $page_author_data->ID ) ) {
			// If the page is published...
			if ( 'publish' == $page->post_status )
				$caps[] = 'edit_published_pages';
			else
				// If the page is draft...
				$caps[] = 'edit_pages';
		} else {
			// The user is trying to edit someone else's page.
			$caps[] = 'edit_others_pages';
			// The page is published, extra cap required.
			if ( 'publish' == $page->post_status )
				$caps[] = 'edit_published_pages';
			elseif ( 'private' == $page->post_status )
				$caps[] = 'edit_private_pages';
		}
		break;
	case 'read_post':
		$post = get_post( $args[0] );
		if ( 'page' == $post->post_type ) {
			$args = array_merge( array( 'read_page', $user_id ), $args );
			return call_user_func_array( 'map_meta_cap_rs', $args );
		}

		if ( 'private' != $post->post_status ) {
			$caps[] = 'read';
			break;
		}

		if ( $adjust_for_user ) {
			$author_data = get_userdata( $user_id );
			$post_author_data = get_userdata( $post->post_author );
		}
			
		if ( $adjust_for_user && ( $user_id == $post_author_data->ID ) )
			$caps[] = 'read';
		else
			$caps[] = 'read_private_posts';
		break;
	case 'read_page':
		$page = get_page( $args[0] );

		if ( 'private' != $page->post_status ) {
			$caps[] = 'read';
			break;
		}

		if ( $adjust_for_user ) {
			$author_data = get_userdata( $user_id );
			$page_author_data = get_userdata( $page->post_author );
		}
		
		if ( $adjust_for_user && ( $user_id == $page_author_data->ID ) )
			$caps[] = 'read';
		else
			$caps[] = 'read_private_pages';
		break;
	default:
		// If no meta caps match, return the original cap.
		$caps[] = $cap;
	}

	return apply_filters('map_meta_cap_rs', $caps, $cap, $user_id, $args);
}

?>