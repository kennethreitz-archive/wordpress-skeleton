<?php
// In effect, override corresponding WP functions with a scoped equivalent, 
// including per-group wp_cache.  Any previous result set modifications by other plugins
// would be discarded.  These filters are set to execute as early as possible to avoid such conflict.
//
// (note: if wp_cache is not enabled, WP core queries will execute pointlessly before these filters have a chance)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

global $scoper;

require_once( SCOPER_ABSPATH . '/lib/ancestry_lib_rs.php' );

if ( $scoper->is_front() )
	require_once('hardway-front_rs.php');

if ( $scoper->is_front() || ! is_content_administrator_rs() )
	require_once('hardway-taxonomy_rs.php');


// flt_get_pages is required on the front end (even for administrators) to enable the inclusion of private pages
// flt_get_pages also needed for inclusion of private pages in some 3rd party plugin config UI (Simple Section Nav)

// flt_get_terms '' so private posts are included in count, as basis for display when hide_empty arg is used


if ( $scoper->data_sources->member_property('post', 'object_types', 'page') )
	add_filter('get_pages', array('ScoperHardway', 'flt_get_pages'), 1, 2);

/**
 * ScoperHardway PHP class for the WordPress plugin Role Scoper
 * hardway_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 * Used by Role Scoper Plugin as a container for statically-called functions
 *
 */	
class ScoperHardway
{	
	//  Scoped equivalent to WP 2.8.3 core get_pages
	//	Currently, scoped roles cannot be enforced without replicating the whole function  
	//
	//	Enforces cap requirements as specified in WP_Scoped_Data_Source::reqd_caps
	function flt_get_pages($results, $args = '') {
		if ( isset( $args['show_option_none'] ) && ( __('Main Page (no parent)') == $args['show_option_none'] ) ) {
			// avoid redundant filtering (currently replacing parent dropdown on flt_dropdown_pages filter)
			return $results;
		}

		if ( ! is_array($results) )
			$results = (array) $results;
		
		global $wpdb;

		// === BEGIN Role Scoper ADDITION: global var; various special case exemption checks ===
		//
		global $scoper, $current_user;
		
		// need to skip cache retrieval if QTranslate is filtering get_pages with a priority of 1 or less
		$no_cache = ! defined('SCOPER_QTRANSLATE_COMPAT') && awp_is_plugin_active('qtranslate');
		
		// buffer titles in case they were filtered previously
		$titles = scoper_get_property_array( $results, 'ID', 'post_title' );

		if ( ! scoper_get_otype_option( 'use_object_roles', 'post', 'page' ) )
			return $results;

		// depth is not really a get_pages arg, but remap exclude arg to exclude_tree if wp_list_terms called with depth=1
		if ( ! empty($args['exclude']) && empty($args['exclude_tree']) && ! empty($args['depth']) && ( 1 == $args['depth'] ) )
			if ( 0 !== strpos( $args['exclude'], ',' ) ) // work around wp_list_pages() bug of attaching leading comma if a plugin uses wp_list_pages_excludes filter
				$args['exclude_tree'] = $args['exclude'];
		//
		// === END Role Scoper ADDITION ===
		// =================================
	
		$defaults = array(
			'child_of' => 0, 'sort_order' => 'ASC',
			'sort_column' => 'post_title', 'hierarchical' => 1,
			'exclude' => '', 'include' => '',
			'meta_key' => '', 'meta_value' => '',
			'authors' => '', 'parent' => -1, 'exclude_tree' => '',
			'number' => '', 'offset' => 0,
			
			'depth' => 0,
			'remap_parents' => -1,	'enforce_actual_depth' => -1,	'remap_thru_excluded_parent' => -1
		);		// Role Scoper arguments added above
		
		// === BEGIN Role Scoper ADDITION: support front-end optimization
		if ( $scoper->is_front() ) {
			if ( defined( 'SCOPER_GET_PAGES_LEAN' ) )
				$defaults['fields'] = "$wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_parent, $wpdb->posts.post_date, $wpdb->posts.post_date_gmt, $wpdb->posts.post_status, $wpdb->posts.post_name, $wpdb->posts.post_modified, $wpdb->posts.post_modified_gmt, $wpdb->posts.guid, $wpdb->posts.menu_order, $wpdb->posts.comment_count";
			else {
				$defaults['fields'] = "$wpdb->posts.*";
				
				if ( ! defined( 'SCOPER_FORCE_PAGES_CACHE' ) )
					$no_cache = true;	// serialization / unserialization of post_content for all pages is too memory-intensive for sites with a lot of pages
			}
		} else {
			// required for xmlrpc getpagelist method	
			$defaults['fields'] = "$wpdb->posts.*";
			
			if ( ! defined( 'SCOPER_FORCE_PAGES_CACHE' ) )
				$no_cache = true;
		}
		// === END Role Scoper MODIFICATION ===


		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		$number = (int) $number;
		$offset = (int) $offset;


		// === BEGIN Role Scoper MODIFICATION: wp-cache key and flag specific to access type and user/groups
		//
		$key = md5( serialize( compact(array_keys($defaults)) ) );
		$ckey = md5 ( $key . CURRENT_ACCESS_NAME_RS );
		
		global $current_user;
		$cache_flag = SCOPER_ROLE_TYPE . '_get_pages';

		$cache = $current_user->cache_get($cache_flag);
		
		if ( false !== $cache ) {
			if ( !is_array($cache) )
				$cache = array();

			if ( ! $no_cache && isset( $cache[ $ckey ] ) )
				// alternate filter name (WP core already applied get_pages filter)
				return apply_filters('get_pages_rs', $cache[ $ckey ], $r);
		}
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================


		$inclusions = '';
		if ( !empty($include) ) {
			$child_of = 0; //ignore child_of, parent, exclude, meta_key, and meta_value params if using include
			$parent = -1;
			$exclude = '';
			$meta_key = '';
			$meta_value = '';
			$hierarchical = false;
			$incpages = preg_split('/[\s,]+/',$include);
			if ( count($incpages) ) {
				foreach ( $incpages as $incpage ) {
					if (empty($inclusions))
						$inclusions = ' AND ( ID = ' . intval($incpage) . ' ';
					else
						$inclusions .= ' OR ID = ' . intval($incpage) . ' ';
				}
			}
		}
		if (!empty($inclusions))
			$inclusions .= ')';
	
		$exclusions = '';
		if ( !empty($exclude) ) {
			$expages = preg_split('/[\s,]+/',$exclude);
			if ( count($expages) ) {
				foreach ( $expages as $expage ) {
					if (empty($exclusions))
						$exclusions = ' AND ( ID <> ' . intval($expage) . ' ';
					else
						$exclusions .= ' AND ID <> ' . intval($expage) . ' ';
				}
			}
		}
		if (!empty($exclusions))
			$exclusions .= ')';
	
		$author_query = '';
		if (!empty($authors)) {
			$post_authors = preg_split('/[\s,]+/',$authors);
	
			if ( count($post_authors) ) {
				foreach ( $post_authors as $post_author ) {
					//Do we have an author id or an author login?
					if ( 0 == intval($post_author) ) {
						$post_author = get_userdatabylogin($post_author);
						if ( empty($post_author) )
							continue;
						if ( empty($post_author->ID) )
							continue;
						$post_author = $post_author->ID;
					}
	
					if ( '' == $author_query )
						$author_query = ' post_author = ' . intval($post_author) . ' ';
					else
						$author_query .= ' OR post_author = ' . intval($post_author) . ' ';
				}
				if ( '' != $author_query )
					$author_query = " AND ($author_query)";
			}
		}
	
		
		// === BEGIN Role Scoper MODIFICATION: split query into join, where clause for filtering
		//
		$where_base = " AND post_type = 'page' AND post_status='publish' $exclusions $inclusions $author_query ";
		
		if ( $parent >= 0 )
			$where_base .= $wpdb->prepare(' AND post_parent = %d ', $parent);

		if ( ! empty( $meta_key ) && ! empty($meta_value) ) {
			// meta_key and meta_value might be slashed
			$meta_key = stripslashes($meta_key);
			$meta_value = stripslashes($meta_value);
			$join_base = " INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id";
			$where_base .= " AND $wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value = '$meta_value'";
		} else
			$join_base = '';
	
		$request = "SELECT $fields FROM $wpdb->posts $join_base WHERE 1=1 $where_base ORDER BY $sort_column $sort_order ";

		$list_private_pages = scoper_get_otype_option('private_items_listable', 'post', 'page');

		$def_caps = $scoper->data_sources->members['post']->reqd_caps['read']['page']['private'];
		
		if ( ! is_admin() && ! $list_private_pages ) {
			// As an extra precaution to make sure we can PREVENT private page listing even if private status is included in query,
			// temporarily set the required cap for reading private pages to a nonstandard cap name (which is probably not owned by any user)
			$scoper->data_sources->members['post']->reqd_caps['read']['page']['private'] = array('list_private_pages');
		} else {
			// WP core does not include private pages in query.  Include private status clause in anticipation of user-specific filtering
			$request = str_replace("AND post_status='publish'", "AND ( post_status IN ('publish','private') )", $request);
		}
		
		if ( $scoper->is_front() && scoper_get_otype_option('do_teaser', 'post') && scoper_get_otype_option('use_teaser', 'post', 'page') && ! defined('SCOPER_TEASER_HIDE_PAGE_LISTING') ) {
			// We are in the front end and the teaser is enabled for pages	

			$pages = scoper_get_results($request);			// execute unfiltered query
			
			// Pass results of unfiltered query through the teaser filter.
			// If listing private pages is disabled, they will be omitted completely, but restricted published pages
			// will still be teased.  This is a slight design compromise to satisfy potentially conflicting user goals without yet another option
			$pages = apply_filters('objects_teaser_rs', $pages, 'post', 'page', array('request' => $request, 'force_teaser' => true) );
			
			if ( $list_private_pages ) {
				if ( ! scoper_get_otype_option('teaser_hide_private', 'post', 'page') )
					$tease_all = true;
			} else
				// now that the teaser filter has been applied, restore reqd_caps value to normal
				$scoper->data_sources->members['post']->reqd_caps['read']['page']['private'] = $def_caps;
	
		} else {
			// Pass query through the request filter
			$request = apply_filters('objects_request_rs', $request, 'post', 'page', array('skip_teaser' => true));
			
			// now that the request filter has been applied, restore reqd_caps value to normal
			if ( ! $list_private_pages )
				$scoper->data_sources->members['post']->reqd_caps['read']['page']['private'] = $def_caps;

			// Execute the filtered query
			$pages = scoper_get_results($request);
		}
		
		if ( empty($pages) )
			// alternate hook name (WP core already applied get_pages filter)
			return apply_filters('get_pages_rs', array(), $r);
		
		// restore buffered titles in case they were filtered previously
		scoper_restore_property_array( $pages, $titles, 'ID', 'post_title' );
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		
		// Role Scoper note: WP core get_pages has already updated wp_cache and pagecache with unfiltered results.
		update_page_cache($pages);
		
		
		// === BEGIN Role Scoper MODIFICATION: Support a disjointed pages tree with some parents hidden ========
		if ( $child_of || empty($tease_all) ) {  // if we're including all pages with teaser, no need to continue thru tree remapping

			$ancestors = ScoperAncestry::get_page_ancestors(); // array of all ancestor IDs for keyed page_id, with direct parent first

			$orderby = $sort_column;

			if ( ( $parent > 0 ) || ! $hierarchical )
				$remap_parents = false;
			else {
				// if these settings were passed into this get_pages call, use them
				if ( -1 === $remap_parents )
					$remap_parents = scoper_get_option( 'remap_page_parents' );
					
				if ( $remap_parents ) {
					if ( -1 === $enforce_actual_depth )
						$enforce_actual_depth = scoper_get_option( 'enforce_actual_page_depth' );
						
					if ( -1 === $remap_thru_excluded_parent )
						$remap_thru_excluded_parent = scoper_get_option( 'remap_thru_excluded_page_parent' );
				}
			}
			
			$remap_args = compact( 'child_of', 'parent', 'exclude', 'depth', 'orderby', 'remap_parents', 'enforce_actual_depth', 'remap_thru_excluded_parent' );  // one or more of these args may have been modified after extraction 
			
			ScoperHardway::remap_tree( $pages, $ancestors, 'ID', 'post_parent', $remap_args );
		}
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		if ( ! empty($exclude_tree) ) {
			$exclude = array();
	
			$exclude = (int) $exclude_tree;
			$children = get_page_children($exclude, $pages);	// RS note: okay to use unfiltered function here since it's only used for excluding
			$excludes = array();
			foreach ( $children as $child )
				$excludes[] = $child->ID;
			$excludes[] = $exclude;
			$total = count($pages);
			for ( $i = 0; $i < $total; $i++ ) {
				if ( in_array($pages[$i]->ID, $excludes) )
					unset($pages[$i]);
			}
		}
		
		// re-index the array, just in case anyone cares
        $pages = array_values($pages);
		
			
		// === BEGIN Role Scoper MODIFICATION: cache key and flag specific to access type and user/groups
		//
		if ( ! $no_cache ) {
			$cache[ $ckey ] = $pages;
			$current_user->cache_set($cache, $cache_flag);
		}

		// alternate hook name (WP core already applied get_pages filter)
		$pages = apply_filters('get_pages_rs', $pages, $r);
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================

		return $pages;
	}
	
	
	
	function remap_tree( &$items, $ancestors, $col_id, $col_parent, $args ) {
		$defaults = array(
			'child_of' => 0, 			'parent' => -1,
			'orderby' => 'post_title',	'depth' => 0,
			'remap_parents' => true, 	'enforce_actual_depth' => true,
			'exclude' => '',			'remap_thru_excluded_parent' => false
		);

		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);

		if ( $depth < 0 )
			$depth = 0;
		
		$exclude = preg_split('/[\s,]+/',$exclude);
		
		$filtered_items_by_id = array();
		foreach ( $items as $item )
			$filtered_items_by_id[$item->$col_id] = true;

		$remapped_items = array();

		// temporary WP bug workaround
		//$any_top_items = false;
		$first_child_of_match = -1;

		// The desired "root" is included in the ancestor array if using $child_of arg, but not if child_of = 0
		$one_if_root = ( $child_of ) ? 0 : 1;
		
		foreach ( $items as $key => $item ) {
			if ( ! empty($child_of) ) {
				if ( ! isset($ancestors[$item->$col_id]) || ! in_array($child_of, $ancestors[$item->$col_id]) ) {
					unset($items[$key]);
					
					continue;
				}
			}
			
			if ( $remap_parents ) {
				$id = $item->$col_id;
				$parent_id = $item->$col_parent;
				
				if ( $parent_id && ( $child_of != $parent_id ) && isset($ancestors[$id]) ) {
					
					// Don't use any ancestors higher than $child_of
					if ( $child_of ) {
						$max_key = array_search( $child_of, $ancestors[$id] );
						if ( false !== $max_key )
							$ancestors[$id] = array_slice( $ancestors[$id], 0, $max_key + 1 );
					}
					
					// Apply depth cutoff here so Walker is not thrown off by parent remapping.
					if ( $depth && $enforce_actual_depth ) {
						if ( count($ancestors[$id]) > ( $depth - $one_if_root ) )
							unset( $items[$key]	);
					}

					if ( ! isset($filtered_items_by_id[$parent_id]) ) {
					
						// Remap to a visible ancestor, if any 
						if ( ! $depth || isset($items[$key]) ) {
							$visible_ancestor_id = 0;
	
							foreach( $ancestors[$id] as $ancestor_id ) {
								if ( isset($filtered_items_by_id[$ancestor_id]) || ($ancestor_id == $child_of) ) {
									// don't remap through a parent which was explicitly excluded
									if( $exclude && in_array( $items[$key]->$col_parent, $exclude ) && ! $remap_thru_excluded_parent )
										break;

									$visible_ancestor_id = $ancestor_id;
									break;
								}
							}
							
							if ( $visible_ancestor_id )
								$items[$key]->$col_parent = $visible_ancestor_id;

							elseif ( ! $child_of )
								$items[$key]->$col_parent = 0;
	
							// if using custom ordering, force remapped items to the bottom
							if ( ( $visible_ancestor_id == $child_of ) && ( false !== strpos( $orderby, 'order' ) ) ) {
								$remapped_items [$key]= $items[$key];
								unset( $items[$key]	);
							}
						}
					}
				}
			} // end if not skipping page parent remap
			
			
			// temporary WP bug workaround: need to keep track of parent, for reasons described below
			if (  $child_of && ! $remapped_items ) {
				//if ( ! $any_top_items && ( 0 == $items[$key]->$col_parent ) )
				//	$any_top_items = true;

				if ( ( $first_child_of_match < 0 ) && ( $child_of == $items[$key]->$col_parent ) )
					$first_child_of_match = $key;
			}
		}
		
		// temporary WP bug workaround
		//if ( $child_of && ( $parent < 0 ) && ( ! $any_top_items ) && $first_child_of_match ) {
		if ( $child_of && ( $parent < 0 ) && $first_child_of_match ) {
			$first_item = reset($items);
			
			if ( $child_of != $first_item->$col_parent ) {
				// As of WP 2.8.4, Walker class with botch this array because it assumes that the first element in the page array is a child of the display root
				// To work around, we must move first element with the desired child_of up to the top of the array
				$_items = array( $items[$first_child_of_match] );
				
				unset( $items[$first_child_of_match] );
				$items = array_merge( $_items, $items );
			}
		}

		if ( $remapped_items )
			$items = array_merge($items, $remapped_items);

	} // end function rs_remap_tree
	
} // end class ScoperHardway

?>