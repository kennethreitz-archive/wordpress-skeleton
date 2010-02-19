<?php
// In effect, override corresponding WP functions with a scoped equivalent, 
// including per-group wp_cache.  Any previous result set modifications by other plugins
// would be discarded.  These filters are set to execute as early as possible to avoid such conflict.
//
// (note: if wp_cache is not enabled, WP core queries will execute pointlessly before these filters have a chance)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

global $scoper;

require_once( 'hardway_rs.php' );
require_once( SCOPER_ABSPATH . '/lib/ancestry_lib_rs.php' );

// flt_get_pages is required on the front end (even for administrators) to enable the inclusion of private pages
// flt_get_terms '' so private posts are included in count, as basis for display when hide_empty arg is used
if ( $scoper->is_front() || ! is_content_administrator_rs() ) {	
	add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
	
	// Since the NOT IN subquery is a painful aberration for filtering, replace it with the separate term query used by WP prior to 2.7
	add_filter('posts_where', array('ScoperHardwayTaxonomy', 'flt_cat_not_in_subquery'), 1);
}


/**
 * ScoperHardwayTaxonomy PHP class for the WordPress plugin Role Scoper
 * hardway-taxonomy_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 * Used by Role Scoper Plugin as a container for statically-called functions
 *
 */	
class ScoperHardwayTaxonomy
{	
	//  Scoped equivalent to WP 2.8.3 core get_terms
	//	Currently, scoped roles cannot be enforced without replicating the whole function 
	// 
	// Cap requirements depend on access type, and are specified in the WP_Scoped_Data_Source->get_terms_reqd_caps corresponding to taxonomy in question
	function flt_get_terms($results, $taxonomies, $args) {
		global $wpdb;
		$empty_array = array();
		
		//d_echo( 'flt_get_terms input:' );
		
		$single_taxonomy = false;
		if ( !is_array($taxonomies) ) {
			$single_taxonomy = true;
			$taxonomies = array($taxonomies);
		}
		// === BEGIN Role Scoper MODIFICATION: single-item array is still a single taxonomy ===
		elseif( count($taxonomies) < 2 )
			$single_taxonomy = true;
		// === END Role Scoper MODIFICATION ===
		
		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( ! is_taxonomy($taxonomy) ) {
				// === BEGIN Role Scoper MODIFICATION: this caused plugin activation error in some situations (though at that time, the error object was created and return on a single line, not byRef as now) ===
				//
				//$error = & new WP_Error('invalid_taxonomy', __awp('Invalid Taxonomy'));
				//return $error;
				return array();
				//
				// === END Role Scoper MODIFICATION ===
			}
		}
		
		// === BEGIN Role Scoper ADDITION: global var; various special case exemption checks ===
		//
		global $scoper;
		
		if ( ! $scoper->taxonomies->is_member( $taxonomies[0] ) )
			return $results;
		
		// no backend filter for administrators
		$parent_or = '';
		if ( ( is_admin() || defined('XMLRPC_REQUEST') ) ) {
			if ( is_content_administrator_rs() ) {
				return $results;
			} else {
				$tx = $scoper->taxonomies->get($taxonomies[0]);
				
				// is a Category Edit form being displayed?
				if ( ! empty( $tx->uri_vars ) )
					$term_id = $scoper->data_sources->detect('id', $tx);
				else
					$term_id = $scoper->data_sources->detect('id', $tx->source);
				
				if ( $term_id )
					// don't filter current parent category out of selection UI even if current user can't manage it
					$parent_or = " OR t.term_id = (SELECT parent FROM $wpdb->term_taxonomy WHERE term_id = '$term_id') ";
			}
		}
		
		// need to skip cache retrieval if QTranslate is filtering get_terms with a priority of 1 or less
		static $no_cache;
		if ( ! isset($no_cache) )
			$no_cache = defined( 'SCOPER_NO_TERMS_CACHE' ) || ( ! defined('SCOPER_QTRANSLATE_COMPAT') && awp_is_plugin_active('qtranslate') );

		// this filter currently only supports a single taxonomy for each get_terms call
		// (although the terms_where filter does support multiple taxonomies and this function could be made to do so)
		if ( ! $single_taxonomy )
				return $results;

		// link category roles / restrictions are only scoped for management (TODO: abstract this)
		if ( $single_taxonomy && ( 'link_category' == $taxonomies[0] ) && $scoper->is_front() )
			return $results;

		// depth is not really a get_terms arg, but remap exclude arg to exclude_tree if wp_list_terms called with depth=1
		if ( ! empty($args['exclude']) && empty($args['exclude_tree']) && ! empty($args['depth']) && ( 1 == $args['depth'] ) )
			$args['exclude_tree'] = $args['exclude'];
	
		// don't offer to set a category as its own parent
		if ( is_admin() && ( 'category' == $taxonomy ) ) {
			if ( strpos(urldecode($_SERVER['REQUEST_URI']), 'categories.php') ) {
				if ( $editing_cat_id = $scoper->data_sources->get_from_uri('id', 'term') ) {
					if ( ! empty($args['exclude']) )
						$args['exclude'] .= ',';
	
					$args['exclude'] .= $editing_cat_id;
				}
			}
		}
		
		// we'll need this array in most cases, to support a disjointed tree with some parents missing (note alternate function call - was _get_term_hierarchy)
		$children = ScoperAncestry::get_terms_children($taxonomies[0]);
		//
		// === END Role Scoper ADDITION ===
		// =================================
		

		$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";
		
		$defaults = array('orderby' => 'name', 'order' => 'ASC',
			'hide_empty' => true, 'exclude' => '', 'exclude_tree' => '', 'include' => '',
			'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
			'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '',
			'pad_counts' => false, 'offset' => '', 'search' => '', 'skip_teaser' => false,
			
			'depth' => 0,	
			'remap_parents' => -1,	'enforce_actual_depth' => -1,	'remap_thru_excluded_parent' => -1
			  );	// Role Scoper arguments added above

		$args = wp_parse_args( $args, $defaults );
		$args['number'] = (int) $args['number'];
		$args['offset'] = absint( $args['offset'] );
		if ( !$single_taxonomy || !is_taxonomy_hierarchical($taxonomies[0]) ||
			'' !== $args['parent'] ) {
			$args['child_of'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}
	
		if ( 'all' == $args['get'] ) {
			$args['child_of'] = 0;
			$args['hide_empty'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}
		
		extract($args, EXTR_SKIP);
		
		// === BEGIN Role Scoper MODIFICATION: use the $children array we already have ===		
		//
		if ( $child_of && ! isset($children[$child_of]) )
			return array();
	
		if ( $parent && ! isset($children[$parent]) )
			return array();
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================


		$filter_key = ( has_filter('list_terms_exclusions') ) ? serialize($GLOBALS['wp_filter']['list_terms_exclusions']) : '';
		$key = md5( serialize( compact(array_keys($defaults)) ) . serialize( $taxonomies ) . $filter_key );
		
		
		// === BEGIN Role Scoper MODIFICATION: cache key specific to access type and user/groups ===
		//
		$object_src_name = $scoper->taxonomies->member_property($taxonomies[0], 'object_source', 'name');
		$ckey = md5( $key . serialize($scoper->get_terms_reqd_caps($object_src_name)) );
		
		global $current_user;
		$cache_flag = SCOPER_ROLE_TYPE . '_get_terms';
		
		$cache = $current_user->cache_get( $cache_flag );
		
		if ( false !== $cache ) {
			if ( !is_array($cache) )
				$cache = array();
			
			if ( ! $no_cache && isset( $cache[ $ckey ] ) ) {
				// RS Modification: alternate filter name (get_terms filter is already applied by WP)
				remove_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
				$terms = apply_filters('get_terms', $cache[ $ckey ], $taxonomies, $args);
				$terms = apply_filters('get_terms_rs', $terms, $taxonomies, $args);
				add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
				return $terms;
			}
		}
		
		// buffer term names in case they were filtered previously
		if ( 'all' == $fields )
			$term_names = scoper_get_property_array( $results, 'term_id', 'name' );
		
		
		//
		// === END Role Scoper MODIFICATION ===
		// =====================================
		

		$_orderby = strtolower($orderby);
		if ( 'count' == $_orderby )
			$orderby = 'tt.count';
		else if ( 'name' == $_orderby )
			$orderby = 't.name';
		else if ( 'slug' == $_orderby )
			$orderby = 't.slug';
		else if ( 'term_group' == $_orderby )
			$orderby = 't.term_group';
		elseif ( empty($_orderby) || 'id' == $_orderby )
			$orderby = 't.term_id';
		elseif ( 'order' == $_orderby )
			$orderby = 't.term_order';
	
		$orderby = apply_filters( 'get_terms_orderby', $orderby, $args );

		$where = '';
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$interms = preg_split('/[\s,]+/',$include);
			if ( count($interms) ) {
				foreach ( $interms as $interm ) {
					if (empty($inclusions))
						$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
					else
						$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
				}
			}
		}
	
		if ( !empty($inclusions) )
			$inclusions .= ')';
		$where .= $inclusions;
	
		$exclusions = '';
		
		if ( ! empty( $exclude_tree ) ) {
			// === BEGIN Role Scoper MODIFICATION: temporarily unhook this filter for unfiltered get_terms calls ===
			remove_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
			// === END Role Scoper MODIFICATION ===
			
			$excluded_trunks = preg_split('/[\s,]+/',$exclude_tree);
			foreach( (array) $excluded_trunks as $extrunk ) {
				$excluded_children = (array) get_terms($taxonomies[0], array('child_of' => intval($extrunk), 'fields' => 'ids'));
				$excluded_children[] = $extrunk;
				foreach( (array) $excluded_children as $exterm ) {
					if ( empty($exclusions) )
						$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
					else
						$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
	
				}
			}
			
			// === BEGIN Role Scoper MODIFICATION: re-hook this filter
			add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
			// === END Role Scoper MODIFICATION ===
		}

		if ( !empty($exclude) ) {
			$exterms = preg_split('/[\s,]+/',$exclude);
			if ( count($exterms) ) {
				foreach ( $exterms as $exterm ) {
					if (empty($exclusions))
						$exclusions = ' AND ( t.term_id <> "' . intval($exterm) . '" ';
					else
						$exclusions .= ' AND t.term_id <> "' . intval($exterm) . '" ';
				}
			}
		}
	
		if ( !empty($exclusions) )
			$exclusions .= ')';
		
		$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args);
		$where .= $exclusions;
	
		if ( !empty($slug) ) {
			$slug = sanitize_title($slug);
			$where .= " AND t.slug = '$slug'";
		}
	
		if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";
		
		if ( '' != $parent ) {
			$parent = (int) $parent;
			
			// === BEGIN Role Scoper MODIFICATION: otherwise termroles only work if parent terms also have role
			if ( ( $parent ) || ('ids' != $fields) )
				$where .= " AND tt.parent = '$parent'";
			// === END Role Scoper MODIFICATION ===
		}
			
		
		// === BEGIN Role Scoper MODIFICATION: instead, manually remove truly empty cats at the bottom of this function, so we don't exclude cats with private but readable posts
		//if ( $hide_empty && !$hierarchical )
		//	$where .= ' AND tt.count > 0';
		// === END Role Scoper MODIFICATION ===
		

		// don't limit the query results when we have to descend the family tree 
		if ( ! empty($number) && ! $hierarchical && empty( $child_of ) && '' == $parent ) {
			if( $offset )
				$limit = 'LIMIT ' . $offset . ',' . $number;
			else
				$limit = 'LIMIT ' . $number;
	
		} else
			$limit = '';
	
		if ( ! empty($search) ) {
			$search = like_escape($search);
			$where .= " AND (t.name LIKE '%$search%')";
		}
			
		$selects = array();
		if ( 'all' == $fields )
			$selects = array('t.*', 'tt.*');
		else if ( 'ids' == $fields )
			$selects = array('t.term_id', 'tt.parent', 'tt.count');
		else if ( 'names' == $fields )
			$selects = array('t.term_id', 'tt.parent', 'tt.count', 't.name');
	        
		$select_this = implode(', ', apply_filters( 'get_terms_fields', $selects, $args ));


		// === BEGIN Role Scoper MODIFICATION: run the query through scoping filter
		//
		$query_base = "SELECT DISTINCT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE 1=1 AND tt.taxonomy IN ($in_taxonomies) $where $parent_or ORDER BY $orderby $order $limit";

		// only force application of scoped query filter if we're NOT doing a teaser  
		if ( 'all' == $fields )
			$do_teaser = ( $scoper->is_front() && empty($skip_teaser) && scoper_get_otype_option('do_teaser', 'post') );
		else
			$do_teaser = false;
			
		$query = apply_filters('terms_request_rs', $query_base, $taxonomies[0], '', array('skip_teaser' => ! $do_teaser));

		
		// if no filering was applied because the teaser is enabled, prevent a redundant query
		if ( ! empty($exclude_tree) || ($query_base != $query) || $parent || ( 'all' != $fields ) ) {
			$terms = scoper_get_results($query);
		} else
			$terms = $results;
			
		if ( 'all' == $fields )
			update_term_cache($terms);
			
		// RS: don't cache an empty array, just in case something went wrong
		if ( empty($terms) )
			return array();
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		// === BEGIN Role Scoper ADDITION: Support a disjointed terms tree with some parents hidden
		//
		if ( 'all' == $fields ) {
			$ancestors = ScoperAncestry::get_term_ancestors( $taxonomy ); // array of all ancestor IDs for keyed term_id, with direct parent first

			if ( ( $parent > 0 ) || ! $hierarchical ) {
				// in Category Edit form, need to list all editable cats even if parent is not editable
				$remap_parents = false;
				$enforce_actual_depth = true;
				$remap_thru_excluded_parent = false;
			} else {
				// if these settings were passed into this get_terms call, use them
				if ( is_admin() ) {
					$remap_parents = true;
					
				} else {
					if ( -1 === $remap_parents )
						$remap_parents = scoper_get_option( 'remap_term_parents' );
					
					if ( $remap_parents ) {
						if ( -1 === $enforce_actual_depth )
							$enforce_actual_depth = scoper_get_option( 'enforce_actual_term_depth' );
							
						if ( -1 === $remap_thru_excluded_parent )
							$remap_thru_excluded_parent = scoper_get_option( 'remap_thru_excluded_term_parent' );
					}
				}
			}
			
			$remap_args = compact( 'child_of', 'parent', 'depth', 'orderby', 'remap_parents', 'enforce_actual_depth', 'remap_thru_excluded_parent' );	// one or more of these args may have been modified after extraction 
			
			ScoperHardway::remap_tree( $terms, $ancestors, 'term_id', 'parent', $remap_args );
		}
		//
		// === END Role Scoper ADDITION ===
		// ================================

	
		// === BEGIN Role Scoper MODIFICATION: call alternate functions 
		// rs_tally_term_counts() replaces _pad_term_counts()
		// rs_get_term_descendants replaces _get_term_children()
		//
		if ( ( $child_of || $hierarchical ) && ! empty($children) )
			$terms = rs_get_term_descendants($child_of, $terms, $taxonomies[0]);
			
		if ( ! $terms )
			return array();
		
		// Replace DB-stored term counts with actual number of posts this user can read.
		// In addition, without the rs_tally_term_counts call, WP will hide categories that have no public posts (even if this user can read some of the pvt posts).
		// Post counts will be incremented to include child categories only if $pad_counts is true
		if ( ! defined('XMLRPC_REQUEST') && ( 'all' == $fields ) )
			//-- RoleScoper Modification - alternate function call (was _pad_term_counts) --//
			rs_tally_term_counts($terms, $taxonomies[0], '', array('pad_counts' => $pad_counts, 'skip_teaser' => ! $do_teaser ) );
		
		// Make sure we show empty categories that have children.
		if ( $hierarchical && $hide_empty ) {
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					//-- RoleScoper Modification - call alternate function (was _get_term_children) --//
					if ( $children = rs_get_term_descendants($term->term_id, $terms, $taxonomies[0]) )
						foreach ( $children as $child )
							if ( $child->count )
								continue 2;
		
					// It really is empty
					unset($terms[$k]);
				}
			}
		}
		reset ( $terms );
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		
		// === BEGIN Role Scoper ADDITION: hide empty cats based on actual query result instead of 'count > 0' clause, so we don't exclude cats with private but readable posts
		if ( $terms && empty( $hierarchical ) && ! empty( $hide_empty ) ) {
			foreach ( $terms as $key => $term )
				if ( ! $term->count )
					unset( $terms[$key] );
		}
		//
		// === END Role Scoper ADDITION ===
		// ================================
		
		$_terms = array();
		if ( 'ids' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->term_id;
			$terms = $_terms;
		} elseif ( 'names' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->name;
			$terms = $_terms;
		}
	
		if ( 0 < $number && intval(@count($terms)) > $number ) {
			$terms = array_slice($terms, $offset, $number);
		}
		
		
		// === BEGIN Role Scoper MODIFICATION: cache key is specific to user/group
		//
		if ( ! $no_cache ) {
			$cache[ $ckey ] = $terms;
			$current_user->cache_set( $cache, $cache_flag );
		}
		
		// RS Modification: alternate filter name (get_terms filter is already applied by WP)
		remove_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
		$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
		$terms = apply_filters('get_terms_rs', $terms, $taxonomies, $args);
		add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3);
		
		// restore buffered term names in case they were filtered previously
		if ( 'all' == $fields )
			scoper_restore_property_array( $terms, $term_names, 'term_id', 'name' );
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		//dump($terms);
		
		return $terms;
	}
	
	
	function flt_cat_not_in_subquery( $where ) {
		if ( strpos( $where, "ID NOT IN ( SELECT tr.object_id" ) ) {
			global $wp_query;
			global $wpdb;
			
			// Since the NOT IN subquery is a painful aberration for filtering, 
			// replace it with the separatare term query used by WP prior to 2.7
			if ( strpos( $where, "AND {$wpdb->posts}.ID NOT IN ( SELECT tr.object_id" ) ) { // global wp_query is not set on manual WP_Query calls by template code
				$whichcat = '';	
			
			//if ( ! empty($wp_query->query_vars['category__not_in']) ) {
				$ids = get_objects_in_term($wp_query->query_vars['category__not_in'], 'category');
				if ( is_wp_error( $ids ) )
					$ids = array();
				if ( is_array($ids) && count($ids > 0) ) {
					$out_posts = "'" . implode("', '", $ids) . "'";
					$whichcat .= " AND $wpdb->posts.ID NOT IN ($out_posts)";
				}
				$where = preg_replace( "/ AND {$wpdb->posts}\.ID NOT IN \( SELECT tr\.object_id [^)]*\) \)/", $whichcat, $where );
			}
		}
		
		return $where;
	}
}


//
// Private
//
function rs_get_term($term_id, $taxonomy) {
	global $scoper;
	
	if ( ! isset($scoper->taxonomies[$taxonomy]) ) {
		return new WP_Error('invalid_taxonomy', __awp('Invalid Taxonomy') . ': ' . __($taxonomy));
	}

	$tx = $scoper->taxonomies[$taxonomy];
	
	if ( $tx->uses_standard_schema )
		return get_term_by( 'id', $term_id, $taxonomy);  // this is a WP core taxonomy, let core get_term() handle it
		
	if ( ! $_term = wpp_cache_get($term_id, "rs_$taxonomy") ) {   //TODO: is this caching useful?
		$_term = scoper_get_row("SELECT * FROM $tx->source WHERE $tx->source->cols->id = '$term_id' LIMIT 1");
		wpp_cache_set($term_id, $_term, "rs_$taxonomy");
	}
}


// renamed for clarity (was get_term_children)
// Also adds support for taxonomies that don't use wp_term_taxonomy schema
function &rs_get_term_descendants($requested_parent_id, $qualified_terms, $taxonomy) {
	if ( empty($qualified_terms) )
		return array();

	$term_list = array();
	$has_children = ScoperAncestry::get_terms_children($taxonomy);
	
	if  ( $requested_parent_id && ! isset($has_children[$requested_parent_id]) ) {
		$arr = array();
		return $arr;
	}
	
	global $scoper;
	$tx = $scoper->taxonomies->get($taxonomy);	
	$col_id = $tx->source->cols->id;	
	$col_parent = $tx->source->cols->parent;
	
	foreach ( $qualified_terms as $term ) {
		$use_id = false;
		if ( !is_object($term) ) {
			$term = rs_get_term($term, $taxonomy);
			if ( is_wp_error( $term ) )
				return $term;
			$use_id = true;
		}

		if ( $term->$col_id == $requested_parent_id )
			continue;
		
		// if this qualified term has the requested parent, log it and all its descendants
		if ( $term->$col_parent == $requested_parent_id ) {
			if ( $use_id )
				$descendant_list[] = $term->$col_id;
			else
				$descendant_list[] = $term;

			if ( !isset($has_children[$term->$col_id]) )
				continue;
	
			if ( $descendants = rs_get_term_descendants($term->$col_id, $qualified_terms, $taxonomy) )
				$descendant_list = array_merge($descendant_list, $descendants);
		}
	}
	
	return $descendant_list;
}


// Rewritten from WP core pad_term_counts to make object count reflect any user-specific roles
// Recalculates term counts by including items from child terms (or if pad_counts is false, simply credits each term for readable private posts)
// Assumes all relevant children are already in the $terms argument
function rs_tally_term_counts(&$terms, $taxonomy, $object_type = '', $args = '') {
	global $wpdb, $scoper;
	
	$defaults = array ( 'pad_counts' => true, 'skip_teaser' => false );
	$args = array_merge( $defaults, $args );
	extract($args);
	
	if ( ! $terms )
		return;
	
	if ( ! $tx = $scoper->taxonomies->get($taxonomy) )
		return;
	
	if ( empty($tx->cols->count) || empty($tx->object_source) )
		return;
		
	$term_items = array();
	$terms_by_id = array();
	foreach ( $terms as $key => $term ) {
		$terms_by_id[$term->{$tx->source->cols->id}] = & $terms[$key];
		$term_ids[$term->{$tx->cols->term2obj_tid}] = $term->{$tx->source->cols->id};  // key and value will match for WP < 2.3 and other non-taxonomy category types
	}

	$src = $scoper->data_sources->get('post');
	$categorized_types = array('post');
	foreach ( array_keys($src->object_types) as $this_object_type )
		if ( 'post' != $this_object_type )
			if ( scoper_get_otype_option( 'use_term_roles', 'post', $this_object_type ) )
				$categorized_types []= $this_object_type;

	// Get the object and term ids and stick them in a lookup table
	$request = "SELECT DISTINCT $wpdb->posts.ID, tt.term_taxonomy_id, tt.term_id, tr.object_id"
			 . " FROM $wpdb->posts"
			 . " INNER JOIN $wpdb->term_relationships AS tr ON $wpdb->posts.ID = tr.object_id "
			 . " INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id "
			 . " WHERE tt.term_id IN ('" . implode("','", $term_ids) . "') "
			 . " AND $wpdb->posts.post_type IN ('" . implode("','", $categorized_types) . "')";
	
	// no need to pass any parameters which do not pertain to the objects_request filter
	$args = array_intersect_key( $args, array_flip( array('skip_teaser') ) );

	if ( 1 == count($categorized_types) )
		$object_type = reset($categorized_types);

	$request = apply_filters('objects_request_rs', $request, $tx->object_source->name, $object_type, $args);

	$results = scoper_get_results($request);
	
	foreach ( $results as $row ) {
		$id = $term_ids[$row->term_taxonomy_id];
		if ( isset($term_items[$id][$row->object_id]) )
			++$term_items[$id][$row->object_id];
		else
			$term_items[$id][$row->object_id] = 1;
	}
	
	// credit each term for every object contained in any of its descendant terms
	if ( $pad_counts && ScoperAncestry::get_terms_children($taxonomy) ) {
		foreach ( $term_ids as $term_id ) {
			$child_term_id = $term_id;
			
			while ( isset($terms_by_id[$child_term_id]->{$tx->source->cols->parent}) ) {
				if ( ! $parent_term_id = $terms_by_id[$child_term_id]->{$tx->source->cols->parent} )
					break;
				
				if ( ! empty($term_items[$term_id]) )
					foreach ( array_keys($term_items[$term_id]) as $item_id )
						$term_items[$parent_term_id][$item_id] = 1;
						
				$child_term_id = $parent_term_id;
			}
		}
	}
	
	// Tally and apply the item credits
	foreach ( $term_items as $term_id => $items )
		if ( isset($terms_by_id[$term_id]) )
			$terms_by_id[$term_id]->count = count($items);
			
	// update count property for zero-item terms too 
	foreach ( array_keys($terms_by_id) as $term_id )
		if ( ! isset($term_items[$term_id]) )
			if ( is_object($terms_by_id[$term_id]) )
				$terms_by_id[$term_id]->count = 0;
}

?>