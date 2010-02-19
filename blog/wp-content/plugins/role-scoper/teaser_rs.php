<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
class ScoperTeaser {
	// normally called by QueryInterceptor::flt_objects_teaser
	function objects_teaser($results, $src_name, $object_types, $tease_otypes, $args = '') {
		$defaults = array('user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 'request' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		global $wpdb, $scoper, $wp_query;

		if ( did_action('wp_meta') && ! did_action('wp_head') )
			return $results;

		if ( ! $src = $scoper->data_sources->get($src_name) )
			return array();

		if ( empty($request) ) {
			if ( empty ($scoper->last_request[$src_name]) ) {
				// try to get it from wpdb instead
				if ( ! empty($wpdb->last_query) )
					$request = $wpdb->last_query;
				else {
					// don't risk exposing hidden content if something goes wrong with query logging
					return array();
				}
			} else
				$request = $scoper->last_request[$src_name];
		}

		if ( count($results) >= $wp_query->query_vars['posts_per_page'] ) {
			// pagination could be broken by subsequent query for filtered ids, so buffer current paging parameters
			$restore_pagination = true;
			
			// this code mimics WP_Query::get_posts().
			$found_posts_query = apply_filters( 'found_posts_query', 'SELECT FOUND_ROWS()' );
			$buffer_found_posts = $wpdb->get_var( $found_posts_query );
			$buffer_found_posts = apply_filters( 'found_posts', $buffer_found_posts );
		} else
			$restore_pagination = false;
		
		$col_id = $src->cols->id;
		$col_content = $src->cols->content;
		$col_type = $src->cols->type;
		
		if ( isset($src->cols->excerpt) )
			$col_excerpt = $src->cols->excerpt;
			
		if ( isset($src->cols->name) )
			$col_name = $src->cols->name;
		
		if ( isset($src->cols->status) && isset($src->statuses['private']) && isset($src->statuses['published']) ) {
			$list_private = array();
			$col_status = $src->cols->status;
			$status_private = $src->statuses['private'];
			$status_published = $src->statuses['published'];
			
			if ( is_single() || is_page() )
				$maybe_fudge_private = true;
			else
				$maybe_strip_private = true;
		}

		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}

		$teaser_replace = array();
		$teaser_prepend = array();
		$teaser_append = array();
		
		foreach ( $tease_otypes as $object_type ) {
			if ( isset($src->cols->content) ) {
				$teaser_replace[$object_type][$col_content] = ScoperTeaser::get_teaser_text( 'replace', 'content', $src_name, $object_type, $user );
				$teaser_prepend[$object_type][$col_content] = ScoperTeaser::get_teaser_text( 'prepend', 'content', $src_name, $object_type, $user );
				$teaser_append[$object_type][$col_content] = ScoperTeaser::get_teaser_text( 'append', 'content', $src_name, $object_type, $user );
			}
			
			if ( isset($src->cols->excerpt) ) {
				$teaser_replace[$object_type][$col_excerpt] = ScoperTeaser::get_teaser_text( 'replace', 'excerpt', $src_name, $object_type, $user );
				$teaser_prepend[$object_type][$col_excerpt] = ScoperTeaser::get_teaser_text( 'prepend', 'excerpt', $src_name, $object_type, $user );
				$teaser_append[$object_type][$col_excerpt] = ScoperTeaser::get_teaser_text( 'append', 'excerpt', $src_name, $object_type, $user );
			}
			
			if ( isset($src->cols->name) ) {
				$teaser_prepend[$object_type][$col_name] = ScoperTeaser::get_teaser_text( 'prepend', 'name', $src_name, $object_type, $user );
				$teaser_append[$object_type][$col_name] = ScoperTeaser::get_teaser_text( 'append', 'name', $src_name, $object_type, $user );
			}
		}
		
		// don't risk exposing hidden content if there is a problem with query parsing
		if ( ! $pos = strpos(strtoupper($request), " FROM") )
			return array();
		
		$distinct = ( stripos( $request, " DISTINCT " ) ) ? 'DISTINCT' : ''; // RS does not add any joins, but if DISTINCT clause exists in query, retain it
		$request = "SELECT $distinct {$src->table}.$col_id " . substr($request, $pos);

		if ( $limitpos = strpos($request, ' LIMIT ') )
			$request = substr($request, 0, $limitpos);

		$args['skip_teaser'] = true;
		$filtered_request = $scoper->query_interceptor->flt_objects_request($request, $src_name, '', $args);
		
		$filtered_ids = scoper_get_col($filtered_request);
		
		if ( ! isset($scoper->teaser_ids) )
			$scoper->teaser_ids = array();
		
		$excerpt_teaser = array();
		$more_teaser = array();
		$x_chars_teaser = array();
		$hide_ungranted_private = array();
		foreach ( $tease_otypes as $object_type ) {
			$teaser_type = scoper_get_otype_option( 'use_teaser', $src_name, $object_type );
			if ( 'excerpt' == $teaser_type )
				$excerpt_teaser[$object_type] = true;
			elseif ( 'more' == $teaser_type ) {
				$excerpt_teaser[$object_type] = true;
				$more_teaser[$object_type] = true;
			} elseif ( 'x_chars' == $teaser_type ) {
				$excerpt_teaser[$object_type] = true;
				$more_teaser[$object_type] = true;
				$x_chars_teaser[$object_type] = true;
			}
			
			$hide_ungranted_private[$object_type] = scoper_get_otype_option('teaser_hide_private', $src_name, $object_type);
		}
		
		// strip content from all $results rows not in $items
		$args = array( 'col_excerpt' => $col_excerpt, 		'col_content' => $col_content, 		'col_id' => $col_id,
				'teaser_prepend' => $teaser_prepend, 		'teaser_append' => $teaser_append, 	'teaser_replace' => $teaser_replace, 
				'excerpt_teaser' => $excerpt_teaser,		'more_teaser' => $more_teaser,		'x_chars_teaser' => $x_chars_teaser );

		foreach ( array_keys($results) as $key ) {
			if ( is_array($results[$key]) )
				$id = $results[$key][$col_id];
			else
				$id = $results[$key]->$col_id;
				
			if ( ! $filtered_ids || ! in_array($id, $filtered_ids) ) {
				if ( isset($results[$key]->$col_type) )
					$object_type = $results[$key]->$col_type;
				else
					$object_type = $scoper->data_sources->get_from_db('type', $src_name, $id);
					
				if ( ! in_array($object_type, $tease_otypes) )
					continue;
					
				ScoperTeaser::apply_teaser( $results[$key], $src_name, $object_type, $args );
				
				// Defeat a WP core secondary safeguard so we can apply the teaser message rather than 404
				if ( ! empty($status_private) && ( $results[$key]->$col_status == $status_private ) ) {
					// don't want the teaser message (or presence in category archive listing) if we're hiding a page from listing
					// (not ready to abstract this yet)
					if ( 'page' == $object_type ) {
						if ( ! isset($list_private[$object_type]) )
							 $list_private[$object_type] = scoper_get_otype_option('private_items_listable', $src_name, $object_type);
					} else
						$list_private[$object_type] = true;
					
					if ( ! empty($maybe_fudge_private) && $list_private[$object_type] ) {
						$results[$key]->$col_status = $status_published;
					} elseif ( $hide_ungranted_private[$object_type] || ( $maybe_strip_private && ! $list_private[$object_type] ) ) {
						$need_reindex = true;
						unset ($results[$key]);
						
						// Actually, don't do this because the current method of removing private items from the paged result set will not move items from one result page to another
						//$buffer_found_posts--;	// since we're removing this item from the teased results, decrement the paging total
		
						continue;
					}
				}
			}
		}
		
		if ( ! empty($need_reindex) )  // re-index the array so paging isn't confused
			$results = array_values($results);
		
		// pagination could be broken by the filtered ids query performed in this function, so original paging parameters were buffered
		if ( $restore_pagination ) {
			// WP query will apply found_posts filter shortly after this function returns.  Feed it the buffered value from original unfiltered results.
			// Static flag in created function ensures it is only applied once.
			$func_name = create_function( '$a', 'static $been_here; if ( ! empty($been_here) ) return $a; else {$been_here = true; ' . "return $buffer_found_posts;}" );
			add_filter( 'found_posts', $func_name, 99);
		}
		
		return $results;
	}
	
	function get_teaser_text( $teaser_operation, $variable, $src_name, $object_type, $user = '' ) {
		if ( ! is_object($user) )
			$user = $current_user;

		$anon = ( $user->ID == 0 ) ? '_anon' : '';

		if ( $msg = scoper_get_otype_option( "teaser_{$teaser_operation}_{$variable}{$anon}", $src_name, $object_type, CURRENT_ACCESS_NAME_RS) ) {
			if ( defined('SCOPER_TRANSLATE_TEASER') ) {
				scoper_load_textdomain(); // otherwise this is only loaded for wp-admin

				$msg = translate( $msg, 'scoper');

				if ( ! empty($msg) && ! is_null($msg) && is_string($msg) )
					$msg = htmlspecialchars_decode( $msg );
			}
			return $msg;
		}
	}
	
	function apply_teaser( &$object, $src_name, $object_type, $args = '' ) {
		$defaults = array( 'col_excerpt' => '', 'col_content' => '', 		'excerpt_teaser' => '', 'col_id' => '',
				'teaser_prepend' => '',		 	'teaser_append' => '', 		'teaser_replace' => '', 'more_teaser' => '',
				'x_chars_teaser' => ''	);
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $scoper;
		
		if ( is_array($object) )
			$id = $object[$col_id];
		else
			$id = $object->$col_id;

		$object->scoper_teaser = true;
		$scoper->teaser_ids[$src_name][$id] = true;

		if ( ! empty( $object->post_password ) ) {
			$excerpt_teaser[$object_type] = false;
			$more_teaser[$object_type] = false;
			$x_chars_teaser[$object_type] = false;
		}

		if ( ! empty($x_chars_teaser[$object_type]) )
			$num_chars = ( defined('SCOPER_TEASER_NUM_CHARS') ) ? SCOPER_TEASER_NUM_CHARS : 50;
		
		// Content replacement mode is applied in the following preference order:
		// 1. Custom excerpt, if available and if selected teaser mode is "excerpt", "excerpt or more", or "excerpt, pre-more or first x chars"
		// 2. Pre-more content, if applicable and if selected teaser mode is "excerpt or more", or "excerpt, pre-more or first x chars"
		// 3. First X Characters (defined by SCOPER_TEASER_NUM_CHARS), if total content is longer than that and selected teaser mode is "excerpt, pre-more or first x chars"
			
		$teaser_set = false;
		
		// optionally, use post excerpt as the hidden content teaser instead of a fixed replacement
		if ( ! empty($excerpt_teaser[$object_type]) && isset($col_content) && isset($col_excerpt) && ! empty($object->$col_excerpt) ) {
			$object->$col_content = $object->$col_excerpt;
			
		} elseif ( ! empty($more_teaser[$object_type]) && isset($col_content) && ( $more_pos = strpos($object->$col_content, '<!--more-->') ) ) {
			$object->$col_content = substr( $object->$col_content, 0, $more_pos + 11 );
			$object->$col_excerpt = $object->$col_content;
			if ( is_single() || is_page() )
				$object->$col_content .= '<p class="scoper_more_teaser">' . $teaser_replace[$object_type][$col_content] . '</p>';

		// since no custom excerpt or more tag is stored, use first X characters as teaser - but only if the total length is more than that
		} elseif ( ! empty($x_chars_teaser[$object_type]) && ! empty($object->$col_content) && ( strlen( strip_tags($object->$col_content) ) > $num_chars ) ) {
			scoper_load_textdomain(); // otherwise this is only loaded for wp-admin

			// since we are stripping out img tag, also strip out image caption applied by WP
			$object->$col_content = preg_replace( "/\[caption.*\]/", '', $object->$col_content );
			$object->$col_content = str_replace( "[/caption]", '', $object->$col_content );
			
			$object->$col_content = sprintf(_x('%s...', 'teaser suffix', 'scoper'), substr( strip_tags($object->$col_content), 0, $num_chars ) );
			$object->$col_excerpt = $object->$col_content;
			
			if ( is_single() || is_page() )
				$object->$col_content .= '<p class="scoper_x_chars_teaser">' . $teaser_replace[$object_type][$col_content] . '</p>';
		
		} else {
			if ( isset($teaser_replace[$object_type][$col_content]) )
				$object->$col_content = $teaser_replace[$object_type][$col_content];
			else
				$object->$col_content = '';

			// Replace excerpt with a user-specified fixed teaser message, 
			// but only if since no custom excerpt exists or teaser options aren't set to some variation of "use excerpt as teaser"
			if ( ! empty($teaser_replace[$object_type][$col_excerpt]) )
				$object->$col_excerpt = $teaser_replace[$object_type][$col_excerpt];
		}


		// NOTE: fixed teaser prepends / appends are always applied to the specified entity regardless of what the content / excerpt was replaced with.
		// (i.e. the fixed excerpt suffix is NOT applied to the teaser content due to an "excerpt as teaser" setting)
		// Likewise, we don't suppress a fixed content suffix because the content was replaced with pre-more_tag content
		foreach ( $teaser_prepend[$object_type] as $col => $entry )
			if ( isset($object->$col) )
				$object->$col = $entry . $object->$col;
			
		foreach ( $teaser_append[$object_type] as $col => $entry )
			if ( isset($object->$col) )
				$object->$col .= $entry;
			
		// no need to display password form if we're blocking content anyway
		if ( 'post' == $src_name )
			if ( ! empty( $object->post_password ) )
				$object->post_password = '';
	}
} // end class
?>