<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('lib/agapetry_config_items.php');

class WP_Scoped_Data_Sources extends AGP_Config_Items {

	// optionally, populate with StdObject objects by passing in array of members
	function WP_Scoped_Data_Sources() {  
		global $wpdb;

		// add default WP taxonomy data source
		// note: term_taxonomy must be registered as the source table to support abstract descendant retrieval function.
		// 		 code will account for location of name column in related terms table
		$args = array(
			'is_taxonomy' => 1,		'taxonomy_only' => 1,	
			'table_basename' => 'term_taxonomy',  'table' => $wpdb->prefix . 'term_taxonomy',
			'table_alias' => 'tt', 	'edit_url' => '%1$s/wp-admin/categories.php?action=edit&cat_ID=%2$d',
			'cols' => (object) array( 'id' => 'term_id', 'name' => 'name', 'parent' => 'parent' ),			// NOTE on ID col: DB queries actually use term_taxonomy_id based on attributes returned by get_terms_query_vars.  term_id here is used for categories queries.  Possible Todo: resolve this discrepancy and potential bug source
			'http_post_vars' => (object) array( 'id' => 'cat_ID', 'parent' => 'category_parent' ),
			'uri_vars' => (object) array( 'id' => 'cat_ID' )
		); // end outer array
		
		// $src_name, $table_basename, $display_name, $display_name_plural, $col_id, $col_name
		$this->add('term', 'role-scoper', __('Term', 'scoper'), __('Terms', 'scoper'), 'terms', 'term_id', 'name', $args );
	}
	
	function &add( $name, $defining_module_name, $display_name, $display_name_plural, $table_basename, $col_id, $col_name, $args) {	
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a data source (%2$s).', $defining_module_name, $name)
					. '<br /><br />' . 'This must be done via the define_data_sources_rs hook.';
			rs_notice($notice);
			return;
		}
	
		if ( isset($this->members[$name]) )
			unset($this->members[$name]);
			
		$this->members[$name] = new WP_Scoped_Data_Source($name, $defining_module_name, $display_name, $display_name_plural, $table_basename, $col_id, $col_name, $args);
		
		$this->process( $this->members[$name] );
		return $this->members[$name];
	}
	
	
	// accepts reference to WP_Data_Source object (must pass object so we can call base class function statically)
	function process( &$src ) {
		global $wpdb;
		
		// apply wp prefix to tablename
		if ( ! isset($src->table) && ! empty($src->table_basename) ) {  // the prefix was already applied, or is unnecessary
			if ( empty($src->table_no_prefix) )
				$src->table = $wpdb->prefix . $src->table_basename;
			else
				$src->table = $src->table_basename;
		}
		
		// if no alias specified, set alias property to table name
		if ( empty($src->table_alias) )
			$src->table_alias = $src->table;
		
		if ( ! empty($src->uses_rs_data_table) ) {
			$src->cols->id = 'actual_id';
			$src->cols->name = 'name';
			// Extension code must set cols->parent = 'parent', cols->owner = 'owner', cols->status = 'status' manually if column will be used 
		}

		// default object_types array to single member matching source name
		if ( empty($src->object_types) ) {
			$src->object_types = array($src->name => (object) array() );
			
			if ( is_admin() ) {
				$src->object_types[$src->name]->display_name = $src->display_name;
				$src->object_types[$src->name]->display_name_plural = $src->display_name_plural;
			}
		}
		
		if ( empty($src->statuses) )
			$src->statuses = array('' => '');
		
		foreach ( array_keys($src->object_types) as $name )
			$src->object_types[$name]->name = $name;
		
		if ( empty($src->taxonomy_only) ) {
			$src_name = $src->name;
		
			// note: This defaults to source action names create_[src_name], save_[src_name], edit_[src_name], delete_[src_name]
			// unless otherwise specified in data source definition.  Better to support a logical default even if it means registering hooks that are never used.
			// (since WP core uses save_post, edit_post, delete_post only, the create_post registration is suppressed in default_data_sources by admin_hooks 'create_object' => '')
			$defaults = array( 
				'save_object' => "save_$src_name", 		'edit_object' => "edit_$src_name", 		'create_object' => '', 
				'delete_object' => "delete_$src_name", 	'object_edit_ui' => '' );
			if ( isset($src->admin_actions) )
				$src->admin_actions = (object) array_merge($defaults, array_intersect_key( (array) $src->admin_actions, $defaults ) );
			else
				$src->admin_actions = (object) $defaults;
			
			$def_obj_status_hook = ( ! empty($src->statuses) && ( count($src->statuses) > 1 ) ) ? "pre_{$src_name}_status" : '';
			$defaults = array( 'pre_object_status' => $def_obj_status_hook );
			if ( isset($src->admin_filters) )
				$src->admin_filters = (object) array_merge($defaults, array_intersect_key( (array) $src->admin_filters, $defaults ) );
			else
				$src->admin_filters = (object) $defaults;
		}
	}
	
	// note: this is called by the parent class in AGP_Data_Sources::get_from_db
	function get_object($src_name, $object_id, $cols = '') {
		// special cases to take advantage of cached post/link
		if ( ('post' == $src_name) && ! $cols )
			return get_post($object_id);
				
		elseif ( 'link' == $src_name )
			return get_bookmark($object_id);
		
		else {
			if ( ! $src = $this->get($src_name) )
				return;
				
			if ( ! isset($src->cols->type) )
				return;
				
			global $wpdb;
			
			if ( ! $cols )
				$cols = '*';

			if ( empty($object_id) )
				return array();
				
			return scoper_get_row("SELECT $cols FROM $src->table WHERE {$src->cols->id} = '$object_id' LIMIT 1");
		} // end switch
	}
	

	function detect($what, $src, $object_id = 0, $object_type = '', $query = '') {
		// so we can pass in $src object or $src_name string
		if ( ! $src = $this->get($src) )
			return;
	
		// if there is only one possible answer, give it
		if ( 'id' != $what ) {
			if ( $it = $this->get_the_only($what, $src) )
				return $it;
			
			if ( $it = $this->get_from_func($what, $src) )
				return $it;
		} else {
			if ( defined('XMLRPC_REQUEST') && ! empty( $xmlrpc_post_id_rs ) )
				return $xmlrpc_post_id_rs;
		}
	
		// Is it set as a $_POST variable?
		if ( $it = $this->get_from_http_post($what, $src, $object_type) )
			return $it;
		
		/*
		// TODO: test this (it would eliminate unnecessary clauses in some queries where object type can be determined)
		// Is it one of the query variables in current WP query?
		if ( 'post' == $src->name ) {
			global $wp_query;
			if ( ! empty($wp_query->query) ) {
				if ( $it = $this->get_from_queryvars($what, $src, $wp_query->query, $object_type) )
					return $it;
			}
		}
		*/
		
		// If we have the object ID, go to the source
		if ( $object_id )
			if ( $it = $this->get_from_db($what, $src, $object_id) )
				return $it;
				
		// Is it one of the query variables in current URI?
		if ( $it = $this->get_from_uri($what, $src, $object_type) )
			return $it;
			
		// Does the last database query include a helpful equality clause?
		if ( $it = $this->get_from_query($what, $src, $query) )
			return $it;
			
		// if detection failed and the desired quanity is a member of a config array, default to first array element
		if ( $it = $this->get_the_only($what, $src, true) )
			return $it;
	}
	
	function get_the_only($what, $src, $force_first = false) {
		if ( ! $src = $this->get($src) )
			return;
		
		if ( isset( $src->collections[$what] ) ) {
			$collection_property = $src->collections[$what];
			
			if ( isset( $src->$collection_property ) ) {
				// If only one object type is defined, we have a winner.
				if ( $force_first || ( 1 == count( $src->$collection_property ) ) ) {
					reset( $src->$collection_property );
					return key( $src->$collection_property );
				}
			}
		} elseif ( ('type' == $what) && ( ! isset($src->object_types) || ( count($src->object_types) < 2 ) ) )
			return $src->name;
	}
	
	function get_from_func($what, $src) {
		if ( ! $src = $this->get($src) )
			return;
	
		if ( ! isset( $src->collections[$what] ) )
			return;
			
		$collection_property = $src->collections[$what];
		if ( isset( $src->$collection_property ) )
			foreach ( $src->$collection_property as $it => $prop )
				if ( isset( $prop->function ) )
					if ( call_user_func($prop->function) )
						return $it;	
	}
	
	function get_from_db($what, $src, $object_id) {
		if ( ! method_exists($this, 'get_object') )
			return;
	
		if ( ! $src = $this->get($src) )
			return;
	
		if ( ! isset($src->cols->$what) )
			return;
			
		$col = $src->cols->$what;

		if ( $object = $this->get_object($src->name, $object_id, $col) )
			if ( isset( $object->$col ) ) {
				$val = $object->$col;
				return $this->get_from_val($what, $val, $src);
			}
	}
	
	function get_from_http($what, $src) {
		if ( $val = $this->get_from_http_post($what, $src) )
			return $val;
	
		if ( $val = $this->get_from_urivars($what, $src) )
			return $val;
	}
	
	// determines, using cfg->data_sources config, the http POST variable for desired information, then returns its value if present
	function get_from_http_post($what, $src, $object_type = '') {
		if ( empty($_POST) )
			return;
		
		if ( ! $src = $this->get($src) )
			return;
			
		/*
		rs_errlog('');
		rs_errlog("get $what from_http_post");
		rs_errlog( serialize($_POST) );
		*/
		
		$varname = $this->get_varname('http_post', $what, $src, $object_type);
			
		//rs_errlog('varname: '. $varname);
		
		if ( isset($_POST[$varname]) ) {
			$it = $this->get_from_val($what, $_POST[$varname], $src);
			//rs_errlog("got $it");
			return $it;
		} else {
			if ( isset($src->http_post_vars_alt->$what) ) {
				$vars_alt = (array) $src->http_post_vars_alt->$what;
				foreach ( $vars_alt as $varname_alt ) {
					if ( isset($_POST[$varname_alt]) ) {
						$it = $this->get_from_val($what, $_POST[$varname_alt], $src);
						//rs_errlog("got $it");
						return $it;
					}
				}
			}
		}
	}
	
	// determines, using cfg->data_sources config, the URI query variable for desired information, then returns its value if present
	function get_from_uri($what, $src, $object_type = '') {
		$full_uri = urldecode($_SERVER['REQUEST_URI']);

		if ( ! $src = $this->get($src) )
			return;
		
		/*
		rs_errlog('');
		rs_errlog("get $what from_uri");
		rs_errlog('URI: '. $full_uri);
		*/
		
		// First, does the URI match a uri substring (i.e. php filename) defined for the data we're seeking?
		if ( isset( $src->collections[$what] ) ) {
		
			$collection_property = $src->collections[$what];
		
			if ( isset( $src->$collection_property ) )
				foreach( $src->$collection_property as $it => $it_properties )
					if ( isset($it_properties->uri) )
						foreach ( $it_properties->uri as $uri_sub )
							if ( strpos($full_uri, $uri_sub) )
								return $it;
		}
			
		// Try to pull the desired value from URI variables, 
		// using data_sources definition to convert the abstract $what into URI variable name 
		$varname = $this->get_varname('uri', $what, $src, $object_type);
		
		//rs_errlog('varname: '. $varname);

		if ( isset($_GET[$varname]) ) {
			$it = $this->get_from_val($what, $_GET[$varname], $src);
			//rs_errlog("got $it");
			return $it;
		} else {
			if ( isset($src->uri_vars_alt->$what) ) {
				$vars_alt = (array) $src->uri_vars_alt->$what;
				foreach ( $vars_alt as $varname_alt ) {
					if ( isset($_GET[$varname_alt]) ) {
						$it = $this->get_from_val($what, $_GET[$varname_alt], $src);
						//rs_errlog("got $it");
						return $it;
					}
				}
			}
		}
	}
	
	/*
	function get_from_queryvars($what, $src, $query_vars, $object_type = '') {
		$varname = $this->get_varname('uri', $what, $src, $object_type);
	
		if ( isset($query_vars[$varname]) )
			return $this->get_from_val($what, $query_vars[$varname], $src);
	}
	*/
	
	function get_from_query($what, $src, $query) {
		if ( ! $query ) 
			return;
	
		if ( ! $src = $this->get($src) )
			return;
		
		if ( empty($src->cols->$what) )
			return;
	
		$col = $src->cols->$what;
			
		// force standard query padding
		$query = preg_replace("/$col\s*=\s*'/", "$col = '", $query);
		
		$found = array();
		$search = "$col = '";
		$pos = -1;
		do {
			$pos = strpos($query, $search, $pos + 1);
			if ( false !== $pos ) {
				$startpos = strpos($query, "'", $pos + strlen($search) );
				$val = substr($query, $pos + strlen($search), $startpos - $pos - strlen($search) );
				$found[$val] = 1;
			}
		} while ( false !== $pos );
		
		if ( ! $found || ( count($found) > 1 ) )
			return;	// query contains zero or multiple equality clauses for requested variable (currently not considering IN clauses)
		
		return $this->get_from_val($what, $val, $src);
	}
	
	function get_from_val( $what, $val, $src ) {
		if ( ! $src = $this->get($src) )
			return $val;

		if ( ! empty ( $src->value_arrays[$what] ) ) {
			$array_property = $src->value_arrays[$what];
			if ( $it = array_search($val, $src->$array_property ) )
				return $it;
		}
		
		if ( empty( $src->collections[$what] ) )
			return $val;
			
		$collection_property = $src->collections[$what];
		if ( empty( $src->$collection_property ) )
			return $val;
		
		// Our value should match a value in the Data_Source::$collection_property array
		// ... but if that array contains objects, our value should match the "val" property of one of those objects.  Blah; this is not fit for human consumption.
		if ( is_object( current($src->$collection_property) ) ) {
			foreach( array_keys($src->$collection_property) as $it )
				if ( $src->$collection_property[$it]->val == $val )
					return $it;
		} else
			if ( $it = array_search($val, $src->$collection_property) )
				return $it;
				
		return $val;
	}
	
	function get_varname( $var_type, $what_for, $src, $object_type = '') {
		if ( ! $src = $this->get($src) )
			return;
		
		$vars = "{$var_type}_vars";
			
		// return otype-specific variable, if defined
		if ( ! empty($object_type) )
			if ( isset($src->object_types[$object_type]->$vars->$what_for[CURRENT_ACCESS_NAME_RS] ) ) 
				return $src->object_types[$object_type]->$vars->$what_for[CURRENT_ACCESS_NAME_RS];

		if ( isset($src->$vars->$what_for) )
			return $src->$vars->$what_for;
		elseif ( isset($src->cols->$what_for) )
			return $src->cols->$what_for;
		else
			return $what_for;
	}
}


// Note: These classes are currently for API support only;
// Internal usage (below) mirrors this interface but instantiates via stdObject cast from array
class WP_Scoped_Data_Source extends AGP_Config_Item {
	var $table_basename; // REQUIRED:  database table name, without wp prefix
	var $table; 		 			// generated from table_basename
	var $table_alias = '';			// database table alias, if any, for use in queries
	
	var $display_name;	 // REQUIRED:  proper case display name (singular)
	var $display_name_plural = '';	// REQUIRED: proper case display name (plural) 
	
	var $cols;						// database column names.   required keys: id, name, content  
									//							optional keys: type, owner, parent, status, excerpt
	
	var $http_post_vars = array();	// http_post_vars[agp_key] = POST variable name for data indicated by agp_key, if it differs from cols[agp_key]
	var $uri_vars = array();		// uri_vars[agp_key] = URI variable name for data indicated by agp_key, if it differs from cols[agp_key]
	var $uri_vars_alt;				// (object) array( 'id' => array('post_id') )
	var $http_post_vars_alt;		// (object) array( 'id' => array('post_id') )

	var $collections = array();		// collections[ agp_key ] = child class property name, indicating child class array property ChildClass::collection_name[meaningful value] = whatever
									// (i.e. in child class WP_Scoped_Data_Sources, 
									//	collections['type'] = 'object_types' relates cols[id] to the WP_Scoped_Data_Source::object_types[object type name] = whatever, 
									//  making it possible for AGP_Data_Sources:get_the_only to shortcut the detection process if the array has only one member
	
	var $value_arrays = array();
									
	var $object_types = array();	// array[obj type name] = various optional properties for object types included in this data source 
									//	(if not set, default to single object type with same name as source)  
									// 	 valid props: 
									//	object_types[obj type name]->val = value string stored to DB, passed as POST variable
									//							   ->uri_vars = array of otype-specific uri query variable names for source id : array( 'id' => array( 'front' => 'page_id' ) )	
									//							   ->uri = array of uri substrings which indicate this object type

	var $statuses = array();		// statuses = array[access_name] = array( status_name => status_type_val )
									//	(indicates statuses valid for display in the specified access type, and the values representing them in DB record and POST vars)
	
	var $usage;						// see core_default_data_sources in defaults_rs.php

	var $is_taxonomy = 0;			// This data source stores taxonomy terms (may be WP core "taxonomy" or other)
	var $taxonomy_only = 0;			// This data source is significant only as a taxonomy for other data sources
	var $uses_taxonomies = array();
	
	var $query_hooks;				// (object) array( 'request' => 'posts_request', 'results' => 'posts_results', 'listing' => 'the_posts' ),
	var $query_replacements = array();

	var $reqd_caps = array();				// see core_default_data_sources in defaults_rs.php
	var $users_where_reqd_caps = array();	// ''
	var $terms_where_reqd_caps = array();	// ''
	
	var $no_object_roles = 0;
	var $edit_url = '';				// URL to object editor, includes [id] placeholder
	
	// usage: $src = new WP_Scoped_Data_Source, then set additional properties on $src
	function WP_Scoped_Data_Source( $name, $defining_module_name, $display_name, $display_name_plural, $table_basename, $col_id, $col_name, $args = '' ) {
		$this->cols = (object) array( 'id' => $col_id, 'name' => $col_name );
	
		$this->AGP_Config_Item($name, $defining_module_name, $args);	
	
		$this->display_name = $display_name;
		$this->display_name_plural = $display_name_plural;
		$this->table_basename = $table_basename;
	}
}

?>