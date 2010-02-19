<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * QueryInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * query-interceptor_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */

class QueryInterceptor_RS
{	
	//var $scoper;
	var $skip_teaser; 	// this is only used by templates making a dircect call to query_posts but wishing to get non-teased results
	var $require_full_object_role;
	
	function QueryInterceptor_RS() {
		global $scoper;
		//$this->scoper =& $scoper;	

		$is_administrator = is_content_administrator_rs();

		// ---- ABSTRACT ROLE SCOPER HOOKS - wrap around source-specific hooks based on DataSources config ------
		//
		//	Request / Where Filter:
		//  Support filtering of any query request (WP or plugin-defined) based on scoped roles.
		//  Resulting content may be narrowed or expanded from WP core results. 
		//		(currently require request/where/join string as first hook arg, ignore other args)
		// 
		//	Results Teaser:
		//  Alternately, if a results hook is defined, unqualified records can 
		//  be left in the result set, but with the content stripped and the excerpt
		//  replaced or appended with a teaser message.
		//		(currently require results as array of objects in first hook arg, ignore other args)
		//
		//	suported filter interface:
		//		request hooks: $arg1 = full request query
		//		results hooks: $arg1 = results set
		
		// filter args: $item, $src_name, $object_type, $args (note: to customize other args, filter must be called directly)
		
		//add_filter('objects_distinct_rs', array($this, 'flt_objects_distinct'), 50);
		//add_filter('objects_join_rs', array($this, 'flt_objects_join'), 2, 4);
		add_filter('objects_where_rs', array(&$this, 'flt_objects_where'), 2, 4);
		add_filter('objects_request_rs', array(&$this, 'flt_objects_request'), 2, 4);
		add_filter('objects_results_rs', array(&$this, 'flt_objects_results'), 50, 4);
		add_filter('objects_teaser_rs', array(&$this, 'flt_objects_teaser'), 50, 4);
		
		if ( ! $scoper->direct_file_access ) {
			// Append any limiting clauses to WHERE clause for taxonomy query
			// args: ($where, $taxonomy, $object_type = '', $reqd_op = '')  e.g. ($where, 'categories', 'post', 'edit')
			// Note: If any of the optional args are missing or nullstring, an attempt is made
			// to determine them from URI based on Scoped_Taxonomy properties
			add_filter('terms_request_rs', array(&$this, 'flt_terms_request'), 50, 4);
		}
		
		// note: If DISABLE_QUERYFILTERS_RS is set, the RS filters are still defined above for selective internal use,
		//		 but in that case are not mapped to the defined data source hooks ('posts_where', etc.) below
		if ( ! defined('DISABLE_QUERYFILTERS_RS') ) {
			//in effect, make WP pass the hook name so multiple hooks can be registered to a single handler 
			$rs_hooks = array();
	
			foreach ( $scoper->data_sources->get_all() as $src_name => $src ) {
				if ( empty($src->query_hooks) )
					continue;
			
				if ( ! $is_administrator ) {
					//if ( isset($src->query_hooks->where) && isset($src->query_hooks->join) ) {
					if ( isset($src->query_hooks->where) ) {
						$rs_hooks[$src->query_hooks->where] = 	(object) array( 'name' => 'objects_where_rs',	'rs_args' => "'$src_name', '', '' ");	
						//$rs_hooks[$src->query_hooks->join] =  	(object) array( 'name' => 'objects_join_rs', 	'rs_args' => "'$src_name', '', '' ");	
					
					} elseif ( isset($src->query_hooks->request) )
						$rs_hooks[$src->query_hooks->request] = (object) array( 'name' => 'objects_request_rs',	'rs_args' => "'$src_name', '', '' ");
				}
				
				// log results (to identify restricted posts) even for admin.  Also, possibly apply front end teaser
				if ( isset($src->query_hooks->results) )
					$rs_hooks[$src->query_hooks->results] = 	(object) array( 'name' => 'objects_results_rs', 'rs_args' => "'$src_name', '', '' ");
	
				//if ( isset($src->query_hooks->distinct) )
				//	$rs_hooks[$src->query_hooks->distinct] = 	(object) array( 'name' => 'objects_distinct_rs','rs_args' => '');	
			} //foreach data_sources

			// call our abstract handlers with a lambda function that passes in original hook name
			foreach ( $rs_hooks as $original_hook => $rs_hook ) {
				if ( ! $original_hook )
					continue;
				
				$orig_hook_numargs = 1;
				$arg_str = agp_get_lambda_argstring($orig_hook_numargs);
				$comma = ( $rs_hook->rs_args ) ? ',' : '';
				$func = "return apply_filters( '$rs_hook->name', $arg_str $comma $rs_hook->rs_args );";
				add_filter( $original_hook, create_function( $arg_str, $func ), 50, $orig_hook_numargs );	

				//d_echo ("adding filter: $original_hook -> $func <br />");
			}
		}
		
		//add_filter( 'posts_request', array( &$this, 'flt_debug_query'), 999 );
	}
	
	
	//function flt_debug_query( $query ) {
	//	d_echo( $query . '<br /><br />' );
	//	return $query;
	//}
	
	
	
	// Append any limiting clauses to WHERE clause for taxonomy query
	//$reqd_caps_by_taxonomy[tx_name][op_type] = array of cap names
	function flt_terms_request($request, $taxonomies, $reqd_caps = '', $args = '') {
		$defaults = array( 'use_object_roles' => -1, 'reqd_caps_by_otype' => array(), 'skip_teaser' => true );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $scoper;
		
		if ( ! $taxonomies )
			return $request;
		
		if ( ! strpos($request, ' WHERE 1=1 ') )
			$request = str_replace(' WHERE ', ' WHERE 1=1 AND ', $request);
			
		$pos_where = 0;
		$pos_suffix = 0;
		$where = agp_parse_after_WHERE_11( $request, $pos_where, $pos_suffix );  // any existing where, orderby or group by clauses remain in $where
		if ( ! $pos_where && $pos_suffix ) {
			$request = substr($request, 0, $pos_suffix) . ' WHERE 1=1 ' .  substr($request, $pos_suffix);
			$pos_where = $pos_suffix;
		}
		$args['request'] = $request;
		
		if ( ! is_array($taxonomies) )
			$taxonomies = array($taxonomies);
			
		// currently support multiple taxonomies, but only if they all use the same data source
		$taxonomy_sources = array();
		foreach ( $taxonomies as $key => $taxonomy ) {
			$src = $scoper->taxonomies->member_property($taxonomy, 'object_source');
			$src_name = $src->name;
			$taxonomy_sources[$src_name] = true;
		}
		if ( count($taxonomy_sources) != 1 )
			return $request;
			
		if ( $reqd_caps && ! $reqd_caps_by_otype ) {
			$reqd_caps_by_src = $scoper->cap_defs->organize_caps_by_otype($reqd_caps);
			if ( ! isset($reqd_caps_by_src[$src_name]) )
				return $request;
			
			$reqd_caps_by_otype = $reqd_caps_by_src[$src_name];
		}
		
		// if the filter call did not specify required caps...
		if ( ! $reqd_caps_by_otype ) {
			$object_src_name = $scoper->taxonomies->member_property($taxonomy, 'object_source', 'name');
		
			// try to determine context from URI (if taxonomy definition includes such clues)
			$reqd_caps_by_otype = $scoper->get_terms_reqd_caps($object_src_name);
			
			//TODO: can this be safely put right in default_rs.php?
			if ( $reqd_caps_by_otype == array( 'post' => array('read') ) )
				if ( scoper_get_otype_option('use_term_roles', 'post', 'page') )
					$reqd_caps_by_otype ['page'] = array('read');
		
			// if required operation still unknown, default based on access type
			if ( ! $reqd_caps_by_otype )
				return $request;
		} 
		
		// object_types never passed in as of RC9
		//if ( ! empty($object_types) && is_array($reqd_caps_by_otype) & (count($reqd_caps_by_otype) > 1)  )
		//	$reqd_caps_by_otype = array_intersect_key($reqd_caps_by_otype, array_flip( (array) $object_types));

		
		// Note that capabilities (i.e. "manage_categories") a user/group has on a particular 
		// term are implemented as Term Roles on the taxonomy data source.
		//
		// Call objects_where_role_clauses() with src_name of the taxonomy source
		// This works as a slight subversion of the normal flt_objects_where query building
		// because we are also forcing taxonomies explicitly and passing the terms_query arg
		
		$args['terms_query'] = true;
		$args['use_object_roles'] = false;

		$args['skip_owner_clause'] = true;
		$args['terms_reqd_caps'] = $reqd_caps_by_otype;
		$args['taxonomies'] = $taxonomies;

		// As of RS 1.1, using subselects in where clause instead
		//$rs_join = $this->flt_objects_join('', $src_name, '', $args);
		
		$where = $this->flt_objects_where($where, $src_name, '', $args);
		
		if ( $pos_where === false )
			$request = $request . ' WHERE 1=1 ' . $where;
		else
			$request = substr($request, 0, $pos_where) . ' WHERE 1=1 ' . $where; // any pre-exising join clauses remain in $request
		
		/*
		if ( $pos_where === false )
			$request = $request . ' ' . $rs_join . ' WHERE 1=1 ' . $where;
		else
			$request = substr($request, 0, $pos_where) . ' ' . $rs_join . 'WHERE 1=1 ' . $where; // any pre-exising join clauses remain in $request
		*/
			
		//d_echo ("<br /><br />terms_request output:$request<br /><br />");
		
		return $request;
	}
	
	function flt_objects_request($request, $src_name, $object_types = '', $args = '') {
		if ( $args ) {
			$defaults = array( 'skip_teaser' => false );
			$args = array_diff_key($args, array_flip( array('request', 'src_name', 'object_types' ) ) );
			$args = array_merge( $defaults, (array) $args );
			extract($args);
		}
		
		global $scoper;

		// Filtering in user_has_cap sufficiently controls revision access; a match here should be for internal, pre-validation purposes
		if ( strpos( $request, "post_type = 'revision'") )
			return $request; 

		if ( empty($skip_teaser) ) {
			$scoper->last_request[$src_name] = $request;	// Store for potential use by subsequent teaser filter
		}
		
		//$request = agp_force_distinct($request); // in case data source didn't provide a hook for objects_distinct
		
		if ( ! strpos($request, ' WHERE 1=1 ') )
			$request = str_replace(' WHERE ', ' WHERE 1=1 AND ', $request);
			
		$pos_where = 0;
		$pos_suffix = 0;
		$where = agp_parse_after_WHERE_11( $request, $pos_where, $pos_suffix );  // any existing where, orderby or group by clauses remain in $where
		if ( ! $pos_where && $pos_suffix ) {
			$request = substr($request, 0, $pos_suffix) . ' WHERE 1=1' .  substr($request, $pos_suffix);
			$pos_where = $pos_suffix;
		}

		// As of RS 1.1, using subselects in where clause instead
		//$args['request'] = $request;
		//$rs_join = $this->flt_objects_join('', $src_name, $object_types, $args);
		
		// TODO: abstract this
		if ( strpos( $request, "post_type = 'attachment'" ) ) {
			// The listed objects are attachments, so query filter is based on objects they inherit from
			global $wpdb;
			// filter attachments on upload page by inserting a scoped subquery based on user roles on the post/page attachment is tied to
			$rs_where = $this->flt_objects_where('', $src_name, $object_types, $args);
			$subqry = "SELECT ID FROM $wpdb->posts WHERE 1=1 $rs_where";
			
			if ( ! defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') || ! SCOPER_BLOCK_UNATTACHED_UPLOADS ) {
				$author_clause = '';

				if ( is_admin() ) {
					// optionally hide other users' unattached uploads, but not from blog-wide Editors
					global $current_user;
					if ( ( empty( $current_user->allcaps['edit_others_posts'] ) && empty( $current_user->allcaps['edit_others_pages'] ) ) && ! scoper_get_option( 'admin_others_unattached_files' ) )
						$author_clause = "AND $wpdb->posts.post_author = '{$current_user->ID}'";
				}
				
				$unattached_clause = "( $wpdb->posts.post_parent = 0 $author_clause ) OR";
			} else
				$unattached_clause = '';
			
			$request = str_replace( "$wpdb->posts.post_type = 'attachment'", "( $wpdb->posts.post_type = 'attachment' AND ( $unattached_clause $wpdb->posts.post_parent IN ($subqry) ) )", $request);
		
		} else {
			// used by objects_where_scope_clauses() to determine whether to add a subselect or make use of existing JOIN	
			$args['join'] = ( $pos_where ) ? substr( $request, 0, $pos_where + 1 ) : '';

			// Generate a query filter based on roles for the listed objects
			$rs_where = $this->flt_objects_where($where, $src_name, $object_types, $args);

			if ( $pos_where === false )
				$request = $request . ' WHERE 1=1 ' . $where;
			else
				$request = substr($request, 0, $pos_where) . ' WHERE 1=1 ' . $rs_where; // any pre-exising join clauses remain in $request
			
			/*
			if ( $pos_where === false )
				$request = $request . ' ' . $rs_join . ' WHERE 1=1 ' . $where;
			else
				$request = substr($request, 0, $pos_where) . ' ' . $rs_join . ' WHERE 1=1 ' . $rs_where; // any pre-exising join clauses remain in $request
			*/
		}
	
		return $request;
	}
	
	function is_term_table_joined( $request, $taxonomy ) {
		global $scoper;
	
		if ( $qvars = $scoper->taxonomies->get_terms_query_vars($taxonomy) )
			if ( false !== strpos($request, " {$qvars->term->table} {$qvars->term->as} ") )
				return true;
	}
	
	// called by flt_objects_where, flt_objects_results
	function _get_object_types($src, $object_types = '') {
		global $scoper;
	
		if ( ! is_object($src) )
			if ( ! $src = $scoper->data_sources->get($src) )
				return array();
	
		if ( ! $object_types ) {
			/*
			// TODO: test this (it would eliminate unnecessary clauses in some queries where object type can be determined)
			if ( 'post' == $src->name ) {
				// special case for detecting page type by presence of page_id arg
				global $wp_query;
				if ( ! empty($wp_query->query) )
					if ( $object_type = $scoper->data_sources->get_from_queryvars('type', $src, $wp_query->query) )
						$object_types = array($object_type);
			}
		
			if ( ! $object_types )
			*/
				$object_types = array_keys($src->object_types);  // include all defined otypes in the query if none were specified
		}
		if ( ! is_array($object_types) )
			$object_types = array($object_types);
			
		return $object_types;
	}
	
	// called by flt_objects_where, flt_objects_results
	function _get_teaser_object_types($src_name, $object_types, $args = '') {
		if ( ! is_array($args) )
			$args = array();

		if ( ! empty($args['skip_teaser']) || is_admin() || is_content_administrator_rs() || is_attachment_rs() || defined('XMLRPC_REQUEST') || ! empty($this->skip_teaser) )
			return array();

		if ( is_feed() && defined( 'SCOPER_NO_FEED_TEASER' ) )
			return array();
			
		if ( empty($object_types) )
			$object_types = $this->_get_object_types($src_name);
			
		$tease_otypes = array();
		
		if ( scoper_get_otype_option('do_teaser', $src_name) ) {
			global $current_user;

			foreach ( $object_types as $object_type )
				if ( scoper_get_otype_option('use_teaser', $src_name, $object_type) ) {
					$teased_users = scoper_get_otype_option('teaser_logged_only', $src_name, $object_type);
					if ( empty( $teased_users )
					|| ( ( 'anon' == $teased_users ) && empty($current_user->ID) )
					|| ( ( 'anon' != $teased_users ) && ! empty($current_user->ID) ) )
						$tease_otypes []= $object_type;
				}
		}
		
		return $tease_otypes;
	}
	
	function flt_objects_where($where, $src_name, $object_types = '', $args = '' ) {
		$defaults = array( 'user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 
							'taxonomies' => array(), 'request' => '', 'terms_query' => 0, 'force_statuses' => '',
							'force_reqd_caps' => '', 'alternate_reqd_caps' => '',	'source_alias' => '',
							'required_operation' => '', 'terms_reqd_caps' => '', 'skip_teaser' => false,
							'join' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $scoper;
		
		// filtering in user_has_cap sufficiently controls revision access; a match here should be for internal, pre-validation purposes
		if ( strpos( $where, "post_type = 'revision'") )
			return $where; 

		$where_prepend = '';

		//d_echo ("<br /><strong>object_where input:</strong> $where<br />");
		//echo "<br />$where<br />";
		
		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
			$args['user'] = $user;
		}

		// TODO: why is this necessary with Facebook Connect plugin?  blog_roles property is somehow cleared.
		if ( empty($user->blog_roles) ) {
			foreach ($scoper->role_defs->get_anon_role_handles() as $role_handle) {
				$user->assigned_blog_roles[ANY_CONTENT_DATE_RS][$role_handle] = true;
				$user->blog_roles[ANY_CONTENT_DATE_RS][$role_handle] = true;
			}
		}
		
		if ( ! $src = $scoper->data_sources->get($src_name) )
			return $where;	// the specified data source is not know to Role Scoper
			
		$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;

		// verify table name and id col definition (the actual existance checked at time of admin entry)
		if ( ! ($src->table && $src->cols->id) )
			rs_notice( sprintf( 'Role Scoper Configuration Error: table_basename or col_id are undefined for the %s data source.', $src_name) );

		// need to allow ambiguous object type for custom cap requirements like comment filtering
		$object_types = $this->_get_object_types($src, $object_types);
		
		$tease_otypes = $this->_get_teaser_object_types($src_name, $object_types, $args);
		$tease_otypes = array_intersect($object_types, $tease_otypes);
		
		if ( empty($src->uses_taxonomies) )
			$use_term_roles = false;

		if ( ! empty($src->no_object_roles) )
			$use_object_roles = false;

		if ( $terms_query && $terms_reqd_caps ) {
			foreach ( array_keys($terms_reqd_caps) as $object_type )
				$otype_status_reqd_caps[$object_type][''] = $terms_reqd_caps[$object_type];  // terms request does not support multiple statuses
				
		} else {
			if ( $force_reqd_caps && is_array($force_reqd_caps) )
				$otype_status_reqd_caps = $force_reqd_caps;
			else {
				if ( ! $required_operation )
					$required_operation = ( 'front' == CURRENT_ACCESS_NAME_RS ) ? OP_READ_RS : OP_EDIT_RS;

				if ( isset( $src->reqd_caps[$required_operation] ) )
					$otype_status_reqd_caps = $src->reqd_caps[$required_operation];
				else
					return $where;
			}
			
			$otype_status_reqd_caps = array_intersect_key($otype_status_reqd_caps, array_flip($object_types) );
		}
		
		// accomodate editing of published posts/pages to revision
		$script_name = $_SERVER['SCRIPT_NAME'];
		$revision_uris = apply_filters( 'scoper_revision_uris', array( 'p-admin/edit.php', 'p-admin/edit-pages.php', 'p-admin/widgets.php' ) );
				
		if ( strpos($script_name, 'p-admin/index.php') || agp_strpos_any($script_name, $revision_uris) ) {
			if ( defined( 'RVY_VERSION' ) && rvy_get_option('pending_revisions') ) {
				$strip_capreqs = array('edit_published_posts', 'edit_private_posts', 'edit_published_pages', 'edit_private_pages');

				foreach ( array_keys($otype_status_reqd_caps) as $listing_otype ) 
					foreach ( array_keys($otype_status_reqd_caps[$listing_otype]) as $status )
						$otype_status_reqd_caps[$listing_otype][$status] = array_diff($otype_status_reqd_caps[$listing_otype][$status], $strip_capreqs);
			}
		}
		
		// Since Role Scoper can restrict or expand access regardless of post_status, query must be modified such that
		//  * the default owner inclusion clause "OR post_author = [user_id] AND post_status = 'private'" is removed
		//  * all statuses are listed apart from owner inclusion clause (and each of these status clauses is subsequently replaced with a scoped equivalent which imposes any necessary access limits)
		//  * a new scoped owner clause is constructed where appropriate (see $where[$cap_name]['owner'] in function objects_where_role_clauses()
		//
		if ( $src->cols->owner && $user->ID ) {
			// force standard query padding
			$where = preg_replace("/{$src->cols->owner}\s*=\s*/", "{$src->cols->owner} = ", $where);
			
			$where = str_replace( " {$src->cols->owner} =", " $src_table.{$src->cols->owner} =", $where);
			$where = str_replace( " {$src->cols->owner} IN", " $src_table.{$src->cols->owner} IN", $where);
		}
		
		if ( ! empty($src->query_replacements) ) {
			foreach ( $src->query_replacements as $find => $replace ) {
				// for posts_request, remove the owner inclusion clause "OR post_author = [user_id] AND post_status = 'private'" because we'll account for that for each status based on properties of required caps
				$find_ = str_replace('[user_id]', $user->ID, $find);
				if ( false !== strpos($find_, '[') ||  false !== strpos($find_, ']') ) {
					rs_notice( sprintf( 'Role Scoper Config Error: invalid query clause search criteria for %1$s (%2$s).<br /><br />Valid placeholders are:<br />', $src_name, $find) . print_r(array_keys($map)) ); 
					return ' AND 1=2 ';
				}
					
				$replace_ = str_replace('[user_id]', $user->ID, $replace);
				if ( false !== strpos($replace_, '[') ||  false !== strpos($replace_, ']') ) {
					rs_notice( sprintf( 'Role Scoper Config Error: invalid query clause replacement criteria for %1$s (%2$s).<br /><br />Valid placeholders are:<br />', $src_name, $replace) . print_r(array_keys($map)) ); 
					return ' AND 1=2 ';
				}
				
				$where = str_replace($find_, $replace_, $where);
			}
		}

		$basic_status_clause = array();
		$force_single_status = false;
		$status_clause_pos = 0;
		
		if ( $col_status = $src->cols->status ) {
			if ( isset( $src->usage->statuses->access_type[CURRENT_ACCESS_NAME_RS] ) ) {
				$use_statuses = ( $force_statuses ) ? $force_statuses : $src->usage->statuses->access_type[CURRENT_ACCESS_NAME_RS];
				$status_vals = array_intersect_key( $src->statuses, array_flip($use_statuses) );
			} else
				$status_vals = $src->statuses->access_type[CURRENT_ACCESS_NAME_RS];
				
			if ( ! $status_vals )
				return $where;
				
			// force standard query padding
			$where = preg_replace("/$col_status\s*=\s*'/", "$col_status = '", $where);
			
			if ( ! $status_col_with_table = ( strpos($where, "{$src_table}.$col_status") ) ) {
				$where = str_replace(" $col_status =", " {$src_table}.$col_status =", $where);
				$where = str_replace(" $col_status IN", " {$src_table}.$col_status IN", $where);
			}
			
			// make sure our status clause expansion is parenthetical
			foreach ($status_vals as $status_name => $status_val) {
				//if ( $status_col_with_table ) {
					$search = "{$src_table}.$col_status = '";
					$basic_status_clause[$status_name] = "{$src_table}.$col_status = '$status_val'";
				//} else {
				//	$search = "$col_status = '";
				//	$basic_status_clause[$status_name] = "$col_status = '$status_val'";
				//}
			}
			
			//$search = ( $status_col_with_table ) ? "{$src_table}.$col_status = '" : "$col_status = '";
			$search = "{$src_table}.$col_status = '";
			
			// If the passed request contains a single status criteria, maintain that status exclusively (otherwise include status-specific conditions for each available status)
			// (But not if user is anon and hidden content teaser is enabled.  In that case, we need to replace the default "status=published" clause)
			
			$status_clause_pos = strpos($where, $search);   // TODO: slim down this parsing code with preg_match
			if ( false !== $status_clause_pos ) {
				// wrap first existing status clause in parenthesis so additional clauses can be ORed to it if necessary
				$endpos = strpos( $where, "'", $status_clause_pos + strlen($search) );
				$where = substr( $where, 0, $status_clause_pos ) . ' ( ' . substr( $where, $status_clause_pos, $endpos - $status_clause_pos + 1 ) . ' ) ' . substr( $where, $endpos + 1 );
				$status_clause_pos = $status_clause_pos + 3;

				$startpos = $status_clause_pos + strlen($search);
				$endpos = strpos($where, "'", $startpos + 1 );
				if ( $endpos > $startpos )
					$first_status_val = substr($where, $startpos, $endpos - $startpos );	
			
				// if col_status appears again, query will include all statuses defined for this access type
				$pos_second = strpos($where, $search, $status_clause_pos + 1);
				if ( ! $pos_second ) {
					// Eliminate a primary plugin incompatibility by skipping this preservation of existing single status requirements if we're on the front end and the requirement is 'publish'.  
					// (i.e. include private posts/pages that this user has access to via RS role assignment).  
					if ( ! $scoper->is_front() || ( 'publish' != $first_status_val ) || empty( $args['user']->ID ) || defined('SCOPER_RETAIN_PUBLISH_FILTER') ) { 
						$force_single_status = true;

						$startpos = $status_clause_pos + strlen($search);
						$endpos = strpos($where, "'", $startpos + 1 );
						if ( $endpos > $startpos ) {
							$this_status_val = substr($where, $startpos, $endpos - $startpos );
							
							foreach ($otype_status_reqd_caps as $object_type => $status_reqd_caps)
								foreach (array_keys($status_reqd_caps) as $status_name)
									if ( $this_status_val != $status_vals[$status_name] )
										unset($otype_status_reqd_caps[$object_type][$status_name]);
						}
					}
				}
			} // endif passed-in where clause has a status clause

		} else {
			// this source doesn't define statuses
			$status_vals = array ( '' => '');
		}

		if ( $col_type = $src->cols->type ) {
			// If the passed request contains a single object type criteria,
			// don't bother including other object types in our own clauses
			$search = "$col_type = '";
			$type_clause_pos = strpos($where, $search);   // TODO: slim down this parsing code with preg_match
			if ( false !== $type_clause_pos ) {
				$startpos = $type_clause_pos + strlen($search);
				$endpos = strpos($where, "'", $startpos + 1 );
				if ( $endpos > $startpos )
					$first_type_val = substr($where, $startpos, $endpos - $startpos );	
			
				// if col_type appears again, RS where clauses will include all object types defined for this access type
				$pos_second = strpos($where, $search, $type_clause_pos + 1);
				if ( ! $pos_second ) {
					$force_single_type = true;

					$startpos = $type_clause_pos + strlen($search);
					$endpos = strpos($where, "'", $startpos + 1 );
					if ( $endpos > $startpos ) {
						$this_type_val = substr($where, $startpos, $endpos - $startpos );
						
						foreach ( array_keys($otype_status_reqd_caps) as $object_type )
							if ( $this_type_val != $object_type )
								unset($otype_status_reqd_caps[$object_type]);
							else
								$object_types = array( $object_type );
					}
				}
			} // endif passed-in where clause has a status clause
		}
		
		if ( empty($skip_teaser) && ! array_diff($object_types, $tease_otypes) ) {
			if ( empty($user->ID) && $status_clause_pos && ! $pos_second ) {
			
				// Since we're dropping out of this function early in advance of teaser filtering, 
				// must take this opportunity to add private status to the query (otherwise WP excludes private for anon user)
				// (But don't do this if the query is for a specific status, or if teaser is configured to hide private content)
				$check_otype = ( count($tease_otypes) && in_array('post', $tease_otypes) ) ? 'post' : $tease_otypes[0];

				if ( ! scoper_get_otype_option('teaser_hide_private', $src_name, $check_otype) ) {
					if ( count($status_vals) == count($src->statuses) ) {
						$where = str_replace( $basic_status_clause['published'], "1=1", $where);
					} else {
						$in_statuses = "'" . implode("', '", $status_vals ) . "'";
						$use_status_col = ( $status_col_with_table ) ? "{$src_table}.$col_status" : $col_status;
						$where = str_replace( $basic_status_clause['published'], "$use_status_col IN ($in_statuses)", $where);
					}
				}
			}
			
			return $where;  // all object types potentially returned by this query will have a teaser filter applied to results
		}


		$is_administrator = is_content_administrator_rs();	// make sure administrators never have content limited
		
		$status_or = '';
		$status_where = array();
		
		foreach ($otype_status_reqd_caps as $object_type => $status_reqd_caps) {
			if ( ! is_array($status_reqd_caps) ) {
				rs_notice( sprintf( 'Role Scoper Configuration Error: reqd_caps for the %s data source must be array[operation][object_type][status] where operation is "read", "edit" or "admin".', $src_name) );
				return $where;
			}
			
			// don't bother generating these parameters if we're just going to pass the object type through for teaser filtering
			if ( ! in_array($object_type, $tease_otypes) ) {
				if ( $terms_query && ! $object_type )
					$otype_use_term_roles = true;
				else {
					$otype_use_term_roles = ( -1 == $use_term_roles ) ? scoper_get_otype_option('use_term_roles', $src_name, $object_type) : $use_term_roles;
					if ( ( ! $otype_use_term_roles ) && ( $terms_query ) )
						continue;	
				}

				$otype_use_object_roles = ( -1 == $use_object_roles ) ? scoper_get_otype_option('use_object_roles', $src_name, $object_type) : $use_object_roles;

				$args['use_term_roles'] = $otype_use_term_roles;
				$args['use_object_roles'] = $otype_use_object_roles;
				$args['object_type'] = $object_type;
			}

			$otype_val = $scoper->data_sources->member_property($src_name, 'object_types', $object_type, 'val');
			if ( ! $otype_val ) $otype_val = $object_type;
			
			//now step through all statuses and corresponding cap requirements for this otype and access type
			// (will replace "col_status = status_name" with "col_status = status_name AND ( [scoper requirements] )
			foreach ($status_reqd_caps as $status_name => $reqd_caps) {
				if ( 'trash' == $status_name )	// in wp-admin, we need to include trash posts for the count query, but not for the listing query unless trash status is requested
					if ( ! strpos($scoper->last_request[$src_name], 'COUNT') && ( empty( $_GET['post_status'] ) || ( 'trash' != $_GET['post_status'] ) ) )
						continue;

				if ( $is_administrator )
					$status_where[$status_name][$object_type] = '1=1';
				elseif ( empty($skip_teaser) && in_array($object_type, $tease_otypes) )
					if ( $terms_query && ! $use_object_roles )
						$status_where[$status_name][$object_type] = '1=1';
					else
						$status_where[$status_name][$object_type] = "{$src_table}.{$src->cols->type} = '$otype_val'"; // this object type will be teaser-filtered
				else {
					// filter defs for otypes which don't define a status will still have a single status element with value ''
					$clause = $this->objects_where_role_clauses($src_name, $reqd_caps, $args);
					
					//dump($clause);
					
					if ( empty($clause) || ( '1=2' == $clause ) )	// this means no qualifying roles are available
						$status_where[$status_name][$object_type] = '1=2';
						
					// array key order status/object is reversed intentionally for subsequent processing
					elseif ( (count($otype_status_reqd_caps) > 1) && ( ! $terms_query || $use_object_roles) ) // more than 1 object type
						$status_where[$status_name][$object_type] = "( {$src_table}.{$src->cols->type} = '$otype_val' AND ( $clause ) )";
					else
						$status_where[$status_name][$object_type] = $clause;
				}
			}
		}
		
		// all otype clauses concat: object_type1 clause [OR] [object_type2 clause] [OR] ...
		foreach ( array_keys($status_where) as $status_name ) {
			if ( isset($preserve_or_clause[$status_name]) )
				$status_where[$status_name][] = $preserve_or_clause[$status_name];

			if ( $tease_otypes )
				$check_otype = ( count($tease_otypes) && in_array('post', $tease_otypes) ) ? 'post' : $tease_otypes[0];

			// extra line of defense: even if upstream logic goes wrong, never disclose a private item to anon user (but if the where clause was passed in with explicit status=private, must include our condition)
			if ( ('private' == $status_name) && ! $force_single_status && empty($current_user->ID) && ( ! $tease_otypes || scoper_get_otype_option('teaser_hide_private', $src_name, $check_otype) ) )
				unset( $status_where[$status_name] );
			else
				$status_where[$status_name] = agp_implode(' ) OR ( ', $status_where[$status_name], ' ( ', ' ) ');
		}	

		// combine identical status clauses
		$duplicate_clause = array();
		$replace_clause = array();
		if (  $col_status && count($status_where) > 1 ) { // more than one status clause
			foreach ( $status_where as $status_name => $status_clause) {
				if ( isset($duplicate_clause[$status_name]) )
					continue;

				reset($status_where);
				if ( $other_name = array_search($status_clause, $status_where) ) {
					if ( $other_name == $status_name ) $other_name = array_search($status_clause, $status_where);
					if ( $other_name && $other_name != $status_name ) {
						$duplicate_clause[$other_name][$status_name] = true;
						$replace_clause[$status_name] = true;
					}
				}
			}
		}
		
		$status_where = array_diff_key($status_where, $replace_clause);
		
		foreach ( $status_where as $status_name => $this_status_where) {
		
			if ( $status_clause_pos && $force_single_status ) {
				//We are maintaining the single status which was specified in original query

				if ( ! $this_status_where || ( $this_status_where == '1=2' ) )
					$where_prepend = '1=2';

				elseif ( $this_status_where == '1=1' )
					$where_prepend = '';

				else {
					//insert at original status clause position
					$where_prepend = '';
					$where = substr($where, 0, $status_clause_pos) . "( $this_status_where ) AND " . substr($where, $status_clause_pos);
				}

				break;
			}

			// We may be replacing or inserting status clauses
			
			if ( ! empty($duplicate_clause[$status_name]) ) {
				// We generated duplicate clauses for some statuses
				foreach ( array_keys($duplicate_clause[$status_name]) as $other_name ) {
					$where = str_replace($basic_status_clause[$other_name], '1=2', $where);
				}
					
				$duplicate_clause[$status_name] = array_merge($duplicate_clause[$status_name], array($status_name=>1) );
				
				// convert status names to status values to revise sql clause
				$clause_status_vals = array();
				foreach ( array_keys($duplicate_clause[$status_name]) as $other_name )
					$clause_status_vals[$status_vals[$other_name]] = true;
			
				if ( count($clause_status_vals) == count($src->statuses) ) {
					$status_prefix = "1=1";
				} else {
					$name_in = "'" . implode("', '", array_keys($clause_status_vals)) . "'";
					$status_prefix = "$col_status IN ($name_in)";
				}
			} elseif ( $col_status && $status_name ) {
				$status_prefix = $basic_status_clause[$status_name];	
			} else
				$status_prefix = '';

			if ( $this_status_where && ( $this_status_where != '1=2' || count($status_where) > 1 ) ) {  //todo: confirm we can OR the 1=2 even if only one status clause
				if ( '1=1' == $this_status_where )
					$status_clause = ( $status_prefix ) ? "$status_prefix " : ''; 
				else {
					$status_clause = ( $col_status && $status_prefix ) ? "$status_prefix AND " : ''; 
					$status_clause .= "( $this_status_where )";
					$status_clause = "( $status_clause )";
				}
			} else
				$status_clause = '1=2';

			if ( $status_clause ) {
				if ( $col_status && $status_name && strpos($where, $basic_status_clause[$status_name]) ) {
					// Replace existing status clause with our scoped equivalent
					$where = str_replace($basic_status_clause[$status_name], "$status_clause", $where);

				} elseif ( $status_clause_pos && ( $status_clause != '1=2' ) ) {
					// This status was not in the original query, but we now insert it with scoping clause at the position of another existing status clause
					$where = substr($where, 0, $status_clause_pos) . "$status_clause OR " . substr($where, $status_clause_pos);
				
				} else {
					// Default query makes no mention of status (perhaps because this data source doesn't define statuses), 
					// so prepend this clause to front of where clause
					$where_prepend .= "$status_or $status_clause";
					$status_or = ' OR';
				}
			}
		}

		// Existance of this variable means no status clause exists in default WHERE.  AND away we go.
		// Prepend so we don't disturb any orderby/groupby/limit clauses which are along for the ride
		if ( $where_prepend ) {
			if ( $where )
				$where = " AND ( $where_prepend ) $where";
			else
				$where = " AND ( $where_prepend )";
		}
	
		//d_echo ("<br /><br /><strong>objects_where output:</strong> $where<br /><br />");
		//echo "<br />$where<br />";
		
		return $where;
	}

	
	// core Role Scoper where clause concatenation called by listing filter (flt_objects_request) and single access filter (flt_user_has_cap)
	// $reqd_caps[cap_name] = min scope
	function objects_where_role_clauses($src_name, $reqd_caps, $args = '' ) {	
		$defaults = array('user' => '', 'taxonomies' => array(), 'use_term_roles' => true, 
						'use_object_roles' => -1, 'terms_query' => false, 'alternate_reqd_caps' => '', 
						'custom_user_blogcaps' => '', 'skip_owner_clause' => false, 'object_type' => '',
						'require_full_object_role' => false, 'join' => '' );

		$args = array_merge( $defaults, (array) $args );
		extract($args);

		global $scoper;

		if ( '' === $custom_user_blogcaps )
			$custom_user_blogcaps = SCOPER_CUSTOM_USER_BLOGCAPS;
		
		if ( ! is_array($reqd_caps) )
			$reqd_caps = ($reqd_caps) ? array($reqd_caps) : array();

		$reqd_caps = $scoper->role_defs->role_handles_to_caps($reqd_caps);
		
		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}
		
		if ( ! $src = $scoper->data_sources->get($src_name) ) {
			rs_notice ( sprintf( 'Role Scoper Config Error (%1$s): Data source (%2$s) is not defined.', 'objects_where_role_clauses', $src_name) );  
			return ' 1=2 ';
		}
		
		// special case to include pending / scheduled revisions by object role
		if ( ! isset( $args['objrole_revisions_clause'] ) ) {
			$args['objrole_revisions_clause'] = strpos( $_SERVER['REQUEST_URI'], 'p-admin/edit.php' ) || strpos( $_SERVER['REQUEST_URI'], 'p-admin/edit-pages.php' );
		}
		
		
		if ( 'group' ==  $src_name ) {
			$use_object_roles = true;
		} elseif ( ! empty($src->no_object_roles) )
			$use_object_roles = false;
		elseif ( $object_type && ! is_array($object_type) && (-1 == $use_object_roles) )
			$use_object_roles = scoper_get_otype_option('use_object_roles', $src_name, $object_type);
			
		$args['use_object_roles'] = $use_object_roles;	

		if ( $use_object_roles ) {
			$applied_object_roles = $scoper->role_defs->get_applied_object_roles($user); // no content date limits involved for object roles
			
			// Return all object_ids that require any role to be object-assigned 
			// We will use an ID NOT IN clause so these are not satisfied by blog/term role assignment
			$objscope_objects = $scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name);
		} else {
			$applied_object_roles = '';
			$objscope_objects = '';
		}
		
		$where = array();
		
		foreach ( $reqd_caps as $cap_name ) {
			// If supporting custom user blogcaps, a separate role clause for each cap
			// Otherwise (default) all reqd_caps from one role assignment (whatever scope it may be)
			if ( $custom_user_blogcaps ) {
				$reqd_caps_arg = array($cap_name);
			} else {
				$reqd_caps_arg = $reqd_caps;
				$cap_name = '';
			}

			if ( $terms_query && ('rs' == SCOPER_ROLE_TYPE) ) {
				$exclude_object_types = array();
				// Terms query may not specify a single object type.
				// Need to exclude roles which don't apply because their object type has term roles disabled
				// This is a complication created by the otype-ambiguous 'read' cap
				foreach ( array_keys($src->object_types) as $this_otype ) {
					if ( ! scoper_get_otype_option('use_term_roles', $src_name, $this_otype) )
						 $exclude_object_types [] = $this_otype;
				}
			} else
				$exclude_object_types = array();


			$qualifying_roles = $scoper->role_defs->qualify_roles($reqd_caps_arg, '', $object_type, array('exclude_object_types' => $exclude_object_types) );   // 'blog' arg: Account for WP blog_roles even if scoping with RS roles  
			
			// For object assignment, replace any "others" reqd_caps array. 
			// Also exclude any roles which have never been assigned to any object
			if ( $use_object_roles ) {
				$base_caps_only = ! $require_full_object_role && ! $this->require_full_object_role;

				$qualifying_object_roles = $scoper->role_defs->qualify_object_roles($reqd_caps_arg, $object_type, $applied_object_roles, $base_caps_only);
			}
			
			if ( $alternate_reqd_caps ) { // $alternate_reqd_caps[setnum] = array of cap_names
				foreach ( $alternate_reqd_caps as $alternate_capset ) {
					foreach ( $alternate_capset as $alternate_reqd_caps ) {
						if ( $alternate_roles = $scoper->role_defs->qualify_roles($alternate_reqd_caps) )
							$qualifying_roles = array_merge($qualifying_roles, $alternate_roles);
						
						if ( $use_object_roles )
							if ( $alternate_object_roles = $scoper->role_defs->qualify_object_roles($alternate_reqd_caps, '', $applied_object_roles, $base_caps_only) )
								$qualifying_object_roles = array_merge($qualifying_object_roles, $alternate_object_roles);
					}	
				}
			}
			
			// this is needed mainly for the chicken-egg situation of uploading a file prior into a post before a category is stored, when editing rights are based on category
			$ignore_restrictions = ( 1 == count($reqd_caps_arg) ) && $scoper->cap_defs->member_property( reset($reqd_caps_arg), 'ignore_restrictions' );
			
			if ( $qualifying_roles ) {  
				$args = array_merge($args, array( 'qualifying_roles' => $qualifying_roles) );
				
				if ( $use_object_roles )
					$args = array_merge($args, array ('qualifying_object_roles' => $qualifying_object_roles, 'objscope_objects' => $objscope_objects, 'applied_object_roles' => $applied_object_roles, 'ignore_restrictions' => $ignore_restrictions ) );
				
				$where[$cap_name]['user'] = $this->objects_where_scope_clauses($src_name, $reqd_caps_arg, $args );
			}

			if ( ! empty($src->cols->owner) && ! $skip_owner_clause && $user->ID ) {
				if ( ! $require_full_object_role && ! $this->require_full_object_role ) {
			
					// if owner qualifies for the operation by any different roles than other users, add separate owner clause
					$owner_reqd_caps = $scoper->cap_defs->remove_owner_caps($reqd_caps_arg);

					$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;
				
					if ( ! $owner_reqd_caps ) {
						// all reqd_caps are granted to owner automatically
						$where[$cap_name]['owner'] = "$src_table.{$src->cols->owner} = '$user->ID'";
					} elseif ( $owner_reqd_caps != $reqd_caps_arg ) {
						$owner_roles = $scoper->role_defs->qualify_roles($owner_reqd_caps, '', $object_type);
	
						if ( $owner_roles ) {
							$args = array_merge($args, array( 'qualifying_roles' => $owner_roles, 'applied_object_roles' => $applied_object_roles, 'ignore_restrictions' => $ignore_restrictions ) );   //TODO: test whether we should just pass existing $objscope_objects, $applied_object_roles here
							
							$scope_temp = $this->objects_where_scope_clauses($src_name, $owner_reqd_caps, $args );

							if ( ( $scope_temp != $where[$cap_name]['user'] ) && ! is_null($scope_temp) ) // TODO: why is null ever returned?
								$where[$cap_name]['owner'] = '( ' . $scope_temp . " ) AND $src_table.{$src->cols->owner} = '$user->ID'";
						}
					}
				}
			}

			// all role clauses concat: user clauses [OR] [owner clauses]
			if ( ! empty($where[$cap_name]) )
				$where[$cap_name] = agp_implode(' ) OR ( ', $where[$cap_name], ' ( ', ' ) ');
			
			// if not supporting custom caps, we actually passed all reqd_caps in first iteration
			if ( ! $custom_user_blogcaps )
				break;
		}
		
		// all reqd caps concat: cap1 clauses [AND] [cap2 clauses] [AND] ...
		if ( ! empty($where) )
			$where = agp_implode(' ) AND ( ', $where, ' ( ', ' ) ');
		
		return $where;
	}
	
	function objects_where_scope_clauses($src_name, $reqd_caps, $args ) {
		$defaults = array( 'user' => '', 'qualifying_roles' => array(), 'qualifying_object_roles' => array(), 'taxonomies' => '', 
		'use_blog_roles' => true, 'use_term_roles' => true, 'use_object_roles' => true, 'terms_query' => false, 
		'objscope_objects' => '', 'skip_objscope_check' => false, 'applied_object_roles' => '', 'object_type' => '',
		'require_full_object_role' => false, 'objrole_revisions_clause' => false, 'join' => '',
		'ignore_restrictions' => false );
		
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $scoper;
		
		if ( ! $src = $scoper->data_sources->get($src_name) ) {
			rs_notice ( sprintf( 'Role Scoper Config Error (%1$s): Data source (%2$s) is not defined.', 'objects_where_scope_clauses', $src_name ) );  
			return ' 1=2 ';
		}
		
		$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;
		
		if ( 'group' == $src_name )
			$use_object_roles = true;
		elseif ( ! empty($src->no_object_roles) )
			$use_object_roles = false;

		if ( ! $object_type )
			$object_type = $scoper->data_sources->detect('type', $src);
			
		// ---- The following default argument generation is included to support potential direct usage of this function 
		//								(not needed by call from flt_objects_where / objects_where_role_clauses) -----------------
		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}
		
		if ( empty($user) )
			return '1=2';
		
		if ( ! $qualifying_roles )
			$qualifying_roles = $scoper->role_defs->qualify_roles($reqd_caps, SCOPER_ROLE_TYPE, $object_type);
		else
			$qualifying_roles = $scoper->role_defs->filter_roles_by_type($qualifying_roles, SCOPER_ROLE_TYPE);
		
		if ( $skip_objscope_check )
			$objscope_objects = array();
		else {
			if ( ! $terms_query || $use_object_roles )
				if ( ! $objscope_objects )
					$objscope_objects = $scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name);
		}
		
		if ( $use_object_roles && ! $qualifying_object_roles ) {
			// For object assignment, replace any "others" reqd_caps array. 
			// Also exclude any roles which have never been assigned to any object
			$base_caps_only = ! $require_full_object_role && ! $this->require_full_object_role;
			$qualifying_object_roles = $scoper->role_defs->qualify_object_roles($reqd_caps, $object_type, $applied_object_roles, $base_caps_only);
		}
		//---------------------------------------------------------------------------------

		
		//d_echo ("qualifying obj roles");
		
		// for term admin query, object source type is passed in, but rs qualifying roles are by taxonomy
		if ( ! $qualifying_roles && $object_type && ( 'rs' == SCOPER_ROLE_TYPE) ) {
			$caps_by_src = $scoper->cap_defs->organize_caps_by_otype($reqd_caps);
			if ( ! empty($caps_by_src[$src_name]) && is_array($caps_by_src[$src_name]) )
				if ( ! isset($caps_by_src[$src_name][$object_type]) ) {
					$object_type = key($caps_by_src[$src_name]);	// setting taxonomy as the object type
					$qualifying_roles = $scoper->role_defs->qualify_roles($reqd_caps, SCOPER_ROLE_TYPE, $object_type);
				}
		}

		if ( -1 === $use_object_roles )
			$use_object_roles = scoper_get_otype_option('use_object_roles', $src_name, $object_type);
		
		if ( $use_object_roles )
			$user_qualifies_for_obj_roles = ( $user->ID || defined( 'SCOPER_ANON_METAGROUP' ) );

		$where = array();
		
		if ( 'wp' == SCOPER_ROLE_TYPE ) {
			// TODO: confirm that this is no longer needed
			if ( ! $user->ID && isset($scoper->role_defs->role_caps[ANON_ROLEHANDLE_RS]) )
				if ( array_intersect_key($scoper->role_defs->role_caps[ANON_ROLEHANDLE_RS], array_flip($reqd_caps) ) )
					$qualifying_roles = array_merge($qualifying_roles, array(ANON_ROLEHANDLE_RS => 1));
		}
		
		if ( $use_term_roles ) {
			// taxonomies arg is for limiting; default is to include all associated taxonomies in where clause
			if ( $taxonomies )
				$taxonomies = (array) $taxonomies;  // don't do array_intersect with uses_taxonomies here because flt_terms_where will call with src_name=taxonomy source (which does not itself have a uses_taxonomies property)
			else
				$taxonomies = $src->uses_taxonomies;
		}
		
		$user_blog_roles = array( '' => array() );
		
		if ( $use_blog_roles ) {
			foreach( array_keys($user->blog_roles) as $date_key )
				$user_blog_roles[$date_key] = array_intersect_key( $user->blog_roles[$date_key], $qualifying_roles );
				
			if ( 'rs' == SCOPER_ROLE_TYPE ) {
				// Also include user's WP blogrole(s),
				// but via equivalent RS role(s) to support scoping requirements (strict (i.e. restricted) terms, objects)
				if ( $wp_qualifying_roles = $scoper->role_defs->qualify_roles($reqd_caps, 'wp') ) {
					if ( $user_blog_roles_wp = array_intersect_key( $user->blog_roles[ANY_CONTENT_DATE_RS], $wp_qualifying_roles ) ) {
						// Credit user's qualifying WP blogrole via contained RS role(s)
						// so we can also enforce "term restrictions", which are based on RS roles
						$user_blog_roles_via_wp = $scoper->role_defs->get_contained_roles( array_keys($user_blog_roles_wp), false, 'rs');
						$user_blog_roles_via_wp = array_intersect_key($user_blog_roles_via_wp, $qualifying_roles);
						
						$user_blog_roles[ANY_CONTENT_DATE_RS] = array_merge( $user_blog_roles[ANY_CONTENT_DATE_RS], $user_blog_roles_via_wp);
					}
				}
			}
		}

		//dump($objscope_objects);

		/*
		// --- optional hack to require read_private cap via blog role AND object role
		// if the required capabilities include a read_private cap but no edit caps
		$require_blog_and_obj_role = 
		( in_array('read_private_posts', $reqd_caps) || in_array('read_private_pages', $reqd_caps) )
		&& ( ! array_diff( $reqd_caps, array('read_private_posts', 'read_private_pages', 'read') ) );
		// --- end hack ---
		*/
		
		foreach ( array_keys($qualifying_roles) as $role_handle ) {
		
			if ( $use_object_roles && empty($require_blog_and_obj_role) ) {
				if ( ! empty($objscope_objects['restrictions'][$role_handle]) ) {
					$objscope_clause = " AND $src_table.{$src->cols->id} NOT IN ('" . implode("', '", array_keys($objscope_objects['restrictions'][$role_handle])) . "')";
				}
				elseif ( isset($objscope_objects['unrestrictions'][$role_handle]) ) {
					if ( ! empty($objscope_objects['unrestrictions'][$role_handle]) )
						$objscope_clause = " AND $src_table.{$src->cols->id} IN ('" . implode("', '", array_keys($objscope_objects['unrestrictions'][$role_handle])) . "')";
					else
						$objscope_clause = " AND 1=2";  // role is default-restricted for this object type, but objects are unrestrictions are set
				} else
					$objscope_clause = '';
			} else
				$objscope_clause = '';

				
			$all_terms_qualified = array();
			$all_taxonomies_qualified = array();
			
			if ( $use_term_roles ) {
				$args['return_id_type'] = COL_TAXONOMY_ID_RS;
				
				$strict_taxonomies = array();
				
				foreach ($taxonomies as $taxonomy)
					if ( $scoper->taxonomies->member_property($taxonomy, 'requires_term') )
						$strict_taxonomies[$taxonomy] = true;
					
				foreach ($taxonomies as $taxonomy) {
					// we only need a separate clause for each role if considering object roles (and therefore considering that some objects might require some roles to be object-assigned)
					if ( ! $use_object_roles )
						$role_handle_arg = $qualifying_roles;
					else
						$role_handle_arg = array( $role_handle => 1 );

					// If a taxonomy does not require objects to have a term, its term role assignments
					// will be purely supplemental; there is no basis for ignoring blogrole assignments.
					//
					// So if none of the taxonomies require each object to have a term 
					// AND the user has a qualifying role via blog assignment, we can skip the taxonomies clause altogether.
					// Otherwise, will consider current user's termroles 
					if ( ! $strict_taxonomies ) {
						if ( array_intersect_key($role_handle_arg, $user->blog_roles[ANY_CONTENT_DATE_RS]) ) {
							// User has a qualifying role by blog assignment, so term_id clause is not required 	
							$all_taxonomies_qualified[ANY_CONTENT_DATE_RS] = true;
							break;
						}
					}

					// qualify_terms returns:
					// terms for which current user has a qualifying role
					// 		- AND -
					// which are non-restricted (i.e. blend in blog assignments) for a qualifying role which the user has blog-wide 
					//
					// note: $reqd_caps function arg is used; qualify_terms will ignore reqd_caps element in args array
					$args['src_name'] = $src_name;
					$args['object_type'] = $object_type;

					
					if ( $user_terms = $scoper->qualify_terms_daterange($reqd_caps, $taxonomy, $role_handle_arg, $args) ) {
						
						if ( ! isset($term_count[$taxonomy]) )
							$term_count[$taxonomy] = $scoper->get_terms($taxonomy, UNFILTERED_RS, COL_COUNT_RS);
					
						foreach ( array_keys($user_terms) as $date_key ) {
							if ( count($user_terms[$date_key]) ) {
								// don't bother applying term requirements if user has cap for all terms in this taxonomy
								if ( (count($user_terms[$date_key]) >= $term_count[$taxonomy]) && $scoper->taxonomies->member_property($taxonomy, 'requires_term') ) {

									// User is qualified for all terms in this taxonomy; no need for any term_id clauses
									$all_terms_qualified[$date_key][$taxonomy] = true;
								} else {
									$where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] = ( isset($where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy]) ) ? array_unique( array_merge($where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy], $user_terms[$date_key]) ) : $user_terms[$date_key];
								}
							}
							
							$all_taxonomies_qualified[$date_key] = ! empty( $all_terms_qualified[$date_key] ) && ( count($all_terms_qualified[$date_key]) == count($strict_taxonomies) );
						}
					}
				} // end foreach taxonomy
			}

			foreach ( array_keys($user_blog_roles) as $date_key ) {
				if ( ! empty($all_taxonomies_qualified[$date_key]) || ( ! $use_term_roles && ! empty($user_blog_roles[$date_key][$role_handle]) ) ) {
					if ( $date_key || $objscope_clause || ! empty($require_blog_and_obj_role) )
						$where[$date_key][$objscope_clause][BLOG_SCOPE_RS] = "1=1";
					else {
						return "1=1";  // no need to include other clause if user has a qualifying role blog-wide or in all terms, it is not date-limited, and that role does not require object assignment for any objects
					}
				}
			}
		
			// if object roles should be applied, populatate array key to force inclusion of OBJECT_SCOPE_RS query clauses below
			if ( $use_object_roles && isset($qualifying_object_roles[$role_handle]) && $user_qualifies_for_obj_roles ) {  // want to apply objscope requirements for anon user, but not apply any obj roles
				if ( $role_spec = scoper_explode_role_handle($role_handle) )
					$where[ANY_CONTENT_DATE_RS][NO_OBJSCOPE_CLAUSE_RS][OBJECT_SCOPE_RS][$role_spec->role_type][$role_spec->role_name] = true;
			}
			
			// we only need a separate clause for each role if considering object roles (and therefore considering that some objects might require some roles to be object-assigned)
			if ( ! $use_object_roles && ! empty($where[ANY_CONTENT_DATE_RS]) )
				break;
		} // end foreach role

		
		// also include object scope clauses for any roles which qualify only for object-assignment
		if ( $use_object_roles && isset( $qualifying_object_roles ) && $user_qualifies_for_obj_roles ) {	// want to apply objscope requirements for anon user, but not apply any obj roles
			if ( $obj_only_roles = array_diff_key( $qualifying_object_roles, $qualifying_roles ) ) {
				foreach ( array_keys($obj_only_roles) as $role_handle )
					if ( $role_spec = scoper_explode_role_handle($role_handle) )
						$where[ANY_CONTENT_DATE_RS][NO_OBJSCOPE_CLAUSE_RS][OBJECT_SCOPE_RS][$role_spec->role_type][$role_spec->role_name] = true;
			}
		}

		// DB query perf enhancement: if any terms are included regardless of post ID, don't also include those terms in ID-specific clause
		foreach ( array_keys($where) as $date_key ) {
			foreach ( array_keys($where[$date_key]) as $objscope_clause ) {
				if ( $objscope_clause && isset($where[$date_key][$objscope_clause][TERM_SCOPE_RS]) ) {
					foreach ( $where[$date_key][$objscope_clause][TERM_SCOPE_RS] as $taxonomy => $terms ) {
						
						if ( ! empty($terms) && ! empty($where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy]) ) {
							$where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] = array_diff( $where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy], $where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy] );
						
							if ( empty( $where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] ) ) {
								unset( $where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] );
	
								// if we removed a taxonomy array, don't leave behind a term scope array with no taxonomies
								if ( empty( $where[$date_key][$objscope_clause][TERM_SCOPE_RS] ) ) {
									unset( $where[$date_key][$objscope_clause][TERM_SCOPE_RS] );
					
									// if we removed a term scope array, don't leave behind an objscope array with no scopes
									if ( empty( $where[$date_key][$objscope_clause] ) )
										unset( $where[$date_key][$objscope_clause] );
								}
							}
						}
					}
				}
			}
		}

		//d_echo ("where_scope_clauses where array");
		
		// since object roles are not pre-loaded prior to this call, role date limits are handled via subselect, within the date_key = '' iteration
		$object_roles_duration_clause = scoper_get_duration_clause();
		
		// support mirroring of inconveniently stored 3rd party plugin data into data_rs table (by RS Extension plugins)
		$rs_data_clause = ( ! empty($src->uses_rs_data_table) ) ? "AND $src_table.topic = 'object' AND $src_table.src_or_tx_name = '$src_name' AND $src_table.object_type IN ('" . implode("', '", $object_types) . "')" : '';
							
		
		// implode the array of where criteria into a query as concisely as possible 
		foreach ( $where as $date_key => $objscope_clauses ) {
	
			foreach ( $objscope_clauses as $objscope_clause => $scope_criteria ) {
				
				foreach ( array_keys($scope_criteria) as $scope ) {
					
					switch ($scope) {
					case BLOG_SCOPE_RS:
						$where[$date_key][$objscope_clause][BLOG_SCOPE_RS] = $where[$date_key][$objscope_clause][BLOG_SCOPE_RS] . " $objscope_clause";
						break;
					
					case TERM_SCOPE_RS:
						$taxonomy_clauses = array();
						
						foreach ( $scope_criteria[TERM_SCOPE_RS] as $taxonomy => $terms ) {
							$is_strict = ! empty( $strict_taxonomies[$taxonomy] );
							
							if ( $objscope_clause )	
								// Avoid " term_id IN (5) OR ( term_id IN (5) AND ID NOT IN (100) )  
								// Otherwise this redundancy can occur when various qualifying roles require object role assignment for different objects
								if ( ! empty($where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy]) )
									if ( ! $terms = array_diff($terms, $where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy]) ) {  
										//unset($scope_criteria[TERM_SCOPE_RS][$taxonomy]);  // this doesn't affect anything (removed in v1.1)
										continue;
									}

							$terms = array_unique($terms);
							if ( $qvars = $scoper->taxonomies->get_terms_query_vars($taxonomy) )
								if ( $terms_query && ! $use_object_roles ) {
									$qtv = $scoper->taxonomies->get_terms_query_vars($taxonomy, true);
									$taxonomy_clauses[$is_strict] []= "{$qtv->term->alias}.{$qtv->term->col_id} IN ('" . implode("', '", $terms) . "') $objscope_clause";
								} else {
									$this_tx_clause = "{$qvars->term->alias}.{$qvars->term->col_id} IN ('" . implode("', '", $terms) . "')";

									// If the request already has a corresponding join on the object2term (term_relationships) table, use it.  Otherwise, use a subselect rather than adding our own LEFT JOIN.
									// (But if this taxonomy is strict and uses the WP term_relationships table, we cannot add an AND clause referencing the same INNER JOIN as aliases as those used for categories.)  
									//if( $this->is_term_table_joined( $join, $taxonomy ) && ( ! $is_strict || ( 'category' == $taxonomy ) || ! $scoper->taxonomies->member_property( $taxonomy, 'uses_standard_schema' ) ) )
									//	$taxonomy_clauses[$is_strict] []= "$this_tx_clause $objscope_clause";
									//else {
										$terms_subselect = "SELECT {$qvars->term->alias}.{$qvars->term->col_obj_id} FROM {$qvars->term->table} {$qvars->term->as} WHERE $this_tx_clause $rs_data_clause";
										$taxonomy_clauses[$is_strict] []= "$src_table.{$src->cols->id} IN ( $terms_subselect ) $objscope_clause";
									//}
								}
						}

						if ( $taxonomy_clauses ) {
							// all taxonomy clauses concat: [taxonomy 1 clauses] [OR] [taxonomy 2 clauses] [OR] ...
							//$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = agp_implode(' ) OR ( ', $taxonomy_clauses, ' ( ', ' ) ');

							// strict taxonomy clauses
							if ( ! empty( $taxonomy_clauses[true] ) )
								$taxonomy_clauses[true] = agp_implode(' ) AND ( ', $taxonomy_clauses[true], ' ( ', ' ) ');

							// non-strict taxonomy clauses
							if ( ! empty( $taxonomy_clauses[false] ) )
								$taxonomy_clauses[false] = agp_implode(' ) OR ( ', $taxonomy_clauses[false], ' ( ', ' ) ');

							// all taxonomy clauses concat: ( [strict taxonomy clause 1] [AND] [strict taxonomy clause 2]... ) [OR] [taxonomy 3 clauses] [OR] ...
							$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = agp_implode(' ) OR ( ', $taxonomy_clauses, ' ( ', ' ) ');
						}

						break;
	
					case OBJECT_SCOPE_RS:	// should only exist with nullstring objscope_clause
						if ( $user_qualifies_for_obj_roles ) {
							global $wpdb;
							$u_g_clause = $user->get_user_clause('uro');
							
							foreach ( array_keys($scope_criteria[OBJECT_SCOPE_RS]) as $role_type ) { //should be only one
								// Combine all qualifying (and applied) object roles into a single OR clause						
								$role_in = "'" . implode("', '", array_keys($scope_criteria[OBJECT_SCOPE_RS][$role_type])) . "'";
								//$where[$date_key][$objscope_clause][OBJECT_SCOPE_RS] = "uro.role_type = '$role_spec->role_type' AND uro.role_name IN ($role_in) ";
								
								$objrole_subselect = "SELECT uro.obj_or_term_id FROM $wpdb->user2role2object_rs AS uro WHERE uro.role_type = '$role_spec->role_type' AND uro.scope = 'object' AND uro.assign_for IN ('entity', 'both') AND uro.role_name IN ($role_in) AND uro.src_or_tx_name = '$src_name' $object_roles_duration_clause $u_g_clause $rs_data_clause";
								$where[$date_key][$objscope_clause][OBJECT_SCOPE_RS] = "$src_table.{$src->cols->id} IN ( $objrole_subselect )";
								
								if ( $objrole_revisions_clause ) 
									$where[$date_key][$objscope_clause][OBJECT_SCOPE_RS] = "( {$where[$date_key][$objscope_clause]['object']} OR ( $src_table.{$src->cols->type} = 'revision' AND $src_table.{$src->cols->parent} IN ( $objrole_subselect ) ) )";
							}
						}
						
						break;
					} // end scope switch
				} // end foreach scope
				
				/*
				// --- optional hack to require read_private cap via blog role AND object role
				if ( ! empty($require_blog_and_obj_role) ) {
					if ( ! isset($where[$date_key][''][BLOG_SCOPE_RS]) )
						$where[$date_key][''][BLOG_SCOPE_RS] = '1=2';
					
					if ( ! isset($where[$date_key][''][TERM_SCOPE_RS]) )
						$where[$date_key][''][TERM_SCOPE_RS] = '1=2';
						
					if ( ! isset($where[$date_key][''][OBJECT_SCOPE_RS]) )
						$where[$date_key][''][OBJECT_SCOPE_RS] = '1=2';
	
					$where[$date_key][''] = "( ( {$where[$date_key]['']['blog']} ) OR ( {$where[$date_key]['']['term']} ) ) AND ( {$where[$date_key]['']['object']} )";
				} 
				else
				// --- end hack
				*/
				// all scope clauses concat: [object roles] OR [term ids] OR [blogrole1 clause] [OR] [blogrole2 clause] [OR] ...
				// Collapse the array to a string even if it's empty
				$where[$date_key][$objscope_clause] = agp_implode(' ) OR ( ', $where[$date_key][$objscope_clause], ' ( ', ' ) ');
			} // end foreach objscope clause
			
			$date_clause = '';
			
			if ( $date_key && is_serialized($date_key) ) {
				$content_date_limits = unserialize($date_key);
				
				if ( $content_date_limits->content_min_date_gmt )
					$date_clause .= " AND $src_table.{$src->cols->date} >= '" . $content_date_limits->content_min_date_gmt . "'";
					
				if ( $content_date_limits->content_max_date_gmt )
					$date_clause .= " AND $src_table.{$src->cols->date} <= '" . $content_date_limits->content_max_date_gmt . "'";
			}
			
			foreach ( array_keys($where[$date_key]) as $objscope_clause )
				if ( empty ($where[$date_key][$objscope_clause]) )
					unset($where[$date_key][$objscope_clause]);
				
			// all objscope clauses concat: [clauses w/o objscope] [OR] [objscope 1 clauses] [OR] [objscope 2 clauses]
			$where[$date_key] = agp_implode(' ) OR ( ', $where[$date_key], ' ( ', ' ) ');
			
			if ( $date_clause && $where[$date_key] )
				$where[$date_key] = "( $where[$date_key]{$date_clause} )"; 
			
		} // end foreach datekey (set of content date limits for which role(s) apply)
		
		
		foreach ( array_keys($where) as $date_key )
			if ( empty ($where[$date_key]) )
				unset($where[$date_key]);

		// all date clauses concat: [clauses w/o content date limits] [OR] [content date range 1 clauses] [OR] [content date range 2 clauses]
		$where = agp_implode(' ) OR ( ', $where, ' ( ', ' ) ');
		
		if ( empty($where) )
			$where = '1=2';

		return $where;
	}
	
	// currently only used to conditionally launch teaser filtering
	function flt_objects_results($results, $src_name, $object_types, $args = '') {

		if ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/edit-pages.php') && ! is_content_administrator_rs() ) {
			// ScoperAncestry class is loaded by hardway_rs.php
			$ancestors = ScoperAncestry::get_page_ancestors(); // array of all ancestor IDs for keyed page_id, with direct parent first

			$args = array( 'remap_parents' => false );
			ScoperHardway::remap_tree( $results, $ancestors, 'ID', 'post_parent', $args );
		}
				
		if ( empty($this->skip_teaser) )
			// won't do anything unless teaser is enabled for object type(s)
			$results = apply_filters('objects_teaser_rs', $results, $src_name, $object_types, array('force_teaser' => true));
		
		return $results;
	}
	
	function flt_objects_teaser($results, $src_name, $object_types = '', $args = '') {
		$defaults = array('user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 'request' => '', 'force_teaser' => false);
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $wpdb;
		global $scoper;
		
		if ( is_admin() || defined('XMLRPC_REQUEST') )
			return $results;
	
		// don't risk exposing hidden content if there is a problem with data source definition
		if ( ! $src = $scoper->data_sources->get($src_name) )
			return array();
		
		if ( ! isset($src->cols->id) || ! isset($src->cols->content) || ! isset($src->cols->type) )
			return array();

		$object_types = $this->_get_object_types($src, $object_types);
		$tease_otypes = $this->_get_teaser_object_types($src_name, $object_types, $args);
		
		if ( empty($tease_otypes) || ( empty($force_teaser) && ! array_intersect($object_types, $tease_otypes) ) )
			return $results;
		
		require_once('teaser_rs.php');
		return ScoperTeaser::objects_teaser($results, $src_name, $object_types, $tease_otypes, $args);
	}

} // end class


function agp_parse_after_WHERE_11( $request, &$pos_where, &$pos_suffix ) {
	$request_u = strtoupper($request);
	$pos_where = strpos( $request_u, ' WHERE 1=1 ');
	
	if ( ! $pos_where ) {
		$suffix_terms = array(' ORDER BY ', ' GROUP BY ', ' LIMIT ');
		$pos_suffix = strlen($request) + 1;
		foreach ( $suffix_terms as $suffix_term )
			if ( $pos = strrpos($request_u, $suffix_term) )
				if ( $pos < $pos_suffix )
					$pos_suffix = $pos;
		
		if ( $pos_suffix )
			$where =  substr($request, $pos_suffix); 

	} else {
		// note: this will still also contain any orderby/limit/groupby clauses ( okay since we won't append anything to the end )
		$where = substr($request, $pos_where + strlen(' WHERE 1=1 ')); 
	}
	
	return $where;	
}
?>