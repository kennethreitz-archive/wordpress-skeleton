<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * CapInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * cap-interceptor_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */

class CapInterceptor_RS
{	
	var $skip_id_generation = false;
	var $skip_any_term_check = false;
	var $skip_any_object_check = false;

	function CapInterceptor_RS() {
		global $scoper;
	
		// Since scoper installation implies that this plugin should take custody
		// of access control, set priority high so we have the final say on group-controlled caps.
		// This filter will not mess with any caps which are not scoper-defined.
		//
		// (note: custom caps from other plugins can be scoper-controlled if they are defined via a Role Scoper Extension plugin)
		add_filter('user_has_cap', array(&$this, 'flt_user_has_cap'), 99, 3);  // scoping will be defeated if our filter isn't applied last
	}
	
	// CapInterceptor_RS::flt_user_has_cap
	//
	// Capability filter applied by WP_User->has_cap (usually via WP current_user_can function)
	// Pertains to logged user's capabilities blog-wide, or for a single item
	//
	// $wp_blogcaps = current user's blog-wide capabilities
	// $reqd_caps = primitive capabilities being tested / requested
	// $args = array with:
	// 		$args[0] = original capability requirement passed to current_user_can (possibly a meta cap)
	// 		$args[1] = user being tested
	// 		$args[2] = object id (could be a postID, linkID, catID or something else)
	//
	// The intent here is to add to (or take away from) $wp_blogcaps based on scoper role assignments
	// (only offer an opinion on scoper-defined caps, with others left in $allcaps array as blog-wide caps)
	//
	function flt_user_has_cap($wp_blogcaps, $orig_reqd_caps, $args)	{
		global $scoper;

		static $tested_object_ids;
		static $hascap_object_ids;	//	$hascap_object_ids[src_name][object_type][capreqs key] = array of object ids for which user has the required caps
									// 		capreqs key = md5(sorted array of required capnames)
						
		static $in_process;			// prevent recursion
		if ( ! empty($in_process) )
			return $wp_blogcaps;
			
		$in_process = true;
	
		if ( empty($hascap_object_ids) ) {
			$hascap_object_ids = array();
			$tested_object_ids = array();	
		}
		
		// work around bug in mw_EditPost method (requires publish_pages AND publish_posts cap)
		if ( defined('XMLRPC_REQUEST') && ( 'publish_posts' == $orig_reqd_caps[0] ) ) {
			global $xmlrpc_post_type_rs;
			if ( 'page' == $xmlrpc_post_type_rs ) {
				$in_process = false;
				return array('publish_posts' => true);
			}
		}
		
		if ( defined('UNSCOPED_CAPS_RS') ) {
			$unscoped_caps = explode( ',', UNSCOPED_CAPS_RS );

			if ( ( 1 == count($orig_reqd_caps[0]) ) && in_array( $orig_reqd_caps[0], $unscoped_caps ) ) {
				$in_process = false;
				return $wp_blogcaps;
			}
		}
		
		//dump($orig_reqd_caps);
		//dump($args);
		
		/*
		rs_errlog(' ');
		rs_errlog('flt_user_has_cap');
		rs_errlog(serialize($orig_reqd_caps));
		//rs_errlog(serialize($args));
		rs_errlog(' ');
		*/

		
		// permitting this filter to execute early in an attachment request resets the found_posts record, preventing display in the template
		if ( is_attachment() && ! is_admin() && ! did_action('template_redirect') ) {
			global $scoper_checking_attachment_access;
			
			if ( empty( $scoper_checking_attachment_access ) ) {
				$in_process = false;
				return $wp_blogcaps;
			}
		}
		
		// convert 'rs_role_name' to corresponding caps (and also make a tinkerable copy of reqd_caps)
		$reqd_caps = $scoper->role_defs->role_handles_to_caps($orig_reqd_caps);
		
		// Disregard caps which are not defined in Role Scoper config
		if ( ! $rs_reqd_caps = array_intersect( $reqd_caps, $scoper->cap_defs->get_all_keys() ) ) {
			$in_process = false;
			return $wp_blogcaps;		
		}
		
		global $current_user;
		
		$user_id = ( isset($args[1]) ) ? $args[1] : 0;

		if ($user_id && ($user_id != $current_user->ID) )
			$user = new WP_Scoped_User($user_id);
		else
			$user = $current_user;

		// If something blew away the scoped allcaps array (which includes RS blogroles), regenerate it.  This has only been reported with MU, but check under WP just in case
		//if ( $user->ID && ! isset( $user->allcaps['is_scoped_user'] ) )
		//	$user->merge_scoped_blogcaps();

		$script_name = $_SERVER['SCRIPT_NAME'];

		if ( defined('RVY_VERSION') ) {
			global $revisionary;
			
			if ( ! $revisionary->skip_revision_allowance ) {
				// Allow contributors to edit published post/page, with change stored as a revision pending review
				$replace_caps = array('edit_published_posts', 'edit_private_posts', 'publish_posts');
				if ( array_intersect( $rs_reqd_caps, $replace_caps) ) {	// don't need to fudge the capreq for post.php unless existing post has public/private status
				
					$revision_uris = apply_filters( 'scoper_revision_uris', array( 'p-admin/edit.php', 'p-admin/edit-pages.php', 'p-admin/widgets.php' ) );
					
					if ( is_preview() || agp_strpos_any( $script_name, $revision_uris ) || ( in_array( get_post_field('post_status', $scoper->data_sources->detect('id', 'post') ), array('publish', 'private') ) ) ) {
						if ( rvy_get_option('pending_revisions') ) {
							if ( strpos($script_name, 'p-admin/page.php') || strpos($script_name, 'p-admin/edit-pages.php') )
								$use_cap_req = 'edit_pages';
							else
								$use_cap_req = 'edit_posts';
						
							foreach ( $rs_reqd_caps as $key => $cap_name )
								if ( in_array($cap_name, $replace_caps) )
									$rs_reqd_caps[$key] = $use_cap_req;
						}
					}
				}
				
				$replace_caps = array('edit_published_pages', 'edit_private_pages', 'publish_pages');
				if ( array_intersect( $rs_reqd_caps, $replace_caps) ) {	// don't need to fudge the capreq for page.php unless existing page has public/private status
					if ( empty($revision_uris) )
						$revision_uris = apply_filters( 'scoper_revision_uris', array( 'p-admin/edit.php', 'p-admin/edit-pages.php', 'p-admin/widgets.php' ) );
					
					if ( is_preview() || agp_strpos_any( $script_name, $revision_uris ) || ( in_array( get_post_field('post_status', $scoper->data_sources->detect('id', 'post') ), array('publish', 'private') ) ) ) {
							if ( rvy_get_option('pending_revisions') ) {
								foreach ( $rs_reqd_caps as $key => $cap_name )
									if ( in_array($cap_name, $replace_caps) )
										$rs_reqd_caps[$key] = 'edit_pages';
						}
					}
				}
			}
		}
			
		// WP core quirk workaround: edit_others_posts is required as preliminary check for populating authors dropdown for pages
		// (but we are doing are own validation, so just short circuit the WP get_editable_user_ids safeguard 
		if ( ('edit_others_posts' == $reqd_caps[0]) && ( strpos($script_name, 'p-admin/page.php') || strpos($script_name, 'p-admin/page-new.php') ) ) {
			
			$key = array_search( 'edit_others_posts', $rs_reqd_caps );

			if ( awp_ver('2.6') ) { // Allow contributors to edit published post/page, with change stored as a revision pending review
				$object_types = $scoper->cap_defs->object_types_from_caps($rs_reqd_caps);
				$object_type = key($object_types);
				
				require_once( 'lib/agapetry_wp_admin_lib.php' ); // function awp_metaboxes_started()
				
				if ( ! awp_metaboxes_started($object_type) && ! strpos($script_name, 'p-admin/revision.php') && false === strpos(urldecode($_SERVER['REQUEST_URI']), 'page=revisions' )  ) // don't enable contributors to view/restore revisions
					$rs_reqd_caps[$key] = 'edit_pages';
				else
					$rs_reqd_caps[$key] = 'edit_published_pages';
				
			} elseif ( $args[2] ) {
				// $wp_blogcaps = array_merge( $wp_blogcaps, array('edit_others_posts' => true) );
				$rs_reqd_caps[$key] = 'edit_others_pages';
			}
		}
		
		// also short circuit any unnecessary edit_posts checks within page edit form, but only after admin menu is drawn
		if ( ('edit_posts' == $reqd_caps[0]) && ( strpos($script_name, 'p-admin/page.php') || strpos($script_name, 'p-admin/page-new.php') ) && did_action('admin_notices') ) {
			$key = array_search( 'edit_posts', $rs_reqd_caps );
		
			$wp_blogcaps = array_merge( $wp_blogcaps, array('edit_posts' => true) );

			if ( ! empty($args[2]) ) // since we're in edit page form, convert id-specific edit_posts requirement to edit_pages
				$rs_reqd_caps[$key] = 'edit_pages';
		}
		

		// If no object id was passed in, we won't do much.
		if ( empty($args[2]) ) {
			if ( ! $this->skip_id_generation && ! defined('XMLRPC_REQUEST') ) {
				// Try to generate missing object_id argument for problematic current_user_can calls 
				if ( empty( $scoper->generate_id_caps ) ) {
					$scoper->generate_id_caps = array('moderate_comments', 'manage_categories', 'edit_published_posts', 'edit_published_pages', 'edit_others_posts', 'edit_others_pages', 'publish_posts', 'publish_pages', 'delete_others_posts', 'delete_others_pages', 'upload_files');
					$scoper->generate_id_caps = apply_filters( 'caps_to_generate_object_id_rs', $scoper->generate_id_caps );

					if ( ! strpos($script_name, 'p-admin/page.php') && ! strpos($script_name, 'p-admin/page-new.php') )
						$scoper->generate_id_caps []= 'edit_posts';
					
					if ( ! strpos($script_name, 'p-admin/post.php') && ! strpos($script_name, 'p-admin/post-new.php') )
						$scoper->generate_id_caps []= 'edit_pages';
				}
	
				if ( in_array( $reqd_caps[0], $scoper->generate_id_caps ) ) {
					//rs_errlog("trying to determine ID for {$reqd_caps[0]}");
	
					if ( $gen_id = $this->generate_missing_object_id( $reqd_caps[0]) ) {
						if ( ! is_array($gen_id) ) {
							// Special case for upload scripts: don't do scoped role query if the post doesn't have any categories saved yet
							if ( strpos($script_name, 'p-admin/media-upload.php') || strpos($script_name, 'p-admin/async-upload.php') ) {
								if ( ! wp_get_post_categories($gen_id) )
									$gen_id = 0;
							}
		
							if ( $gen_id ) {
								$args[2] = $gen_id;
							}
						}
					}
				}

			} else
				$this->skip_id_generation = false; // too risky to leave this set
			
				
			if ( empty($args[2]) ) {
				
				if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) ) {
					// These checks are only relevant since no object_id was provided.  
					// Otherwise (in the main body of this function), taxonomy and object caps will be credited via scoped query
				
					// If we are about to fail the blogcap requirement, credit a missing cap if 
					// the user has it by term role for ANY term.
					// This prevents failing initial UI entrance exams that assume blogroles-only
					if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) )
						if ( ! $this->skip_any_term_check )
							if ( $tax_caps = $this->user_can_for_any_term($missing_caps) )
								$wp_blogcaps = array_merge($wp_blogcaps, $tax_caps);
								
					// If we are about to fail the blogcap requirement, credit a missing scoper-defined cap if 
					// the user has it by object role for ANY object.
					// (i.e. don't bar user from edit-pages.php if they have edit_pages cap for at least one page)
					if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) ) {
						
						$honor_objrole = awp_ver('2.7') || ! strpos($script_name, 'p-admin/index.php') || ! did_action('admin_notices') || ! empty($scoper->honor_any_objrole);
						
						if ( ! $this->skip_any_object_check && $honor_objrole ) {  // credit object role assignment for menu visibility check and Dashboard Post/Page total, but not for Dashboard "Write Post" / "Write Page" links

							// Complication due to the dual usage of 'edit_posts' / 'edit_pages' caps for creation AND editing permission:
							// We don't want to allow a user to create a new page or post simply because they have an editing role assigned directly to some other post/page
							$any_objrole_skip_uris = array( 'p-admin/page-new.php', 'p-admin/post-new.php' );
							$any_objrole_skip_uris = apply_filters( 'any_objrole_skip_uris_rs', $any_objrole_skip_uris );
							
							$skip = false;
							foreach ( $any_objrole_skip_uris as $uri_sub ) {
								if ( strpos(urldecode($_SERVER['REQUEST_URI']), $uri_sub) ) {
									$skip = true;
									break;
								}
							}
							
							if ( ! $skip ) {
								$any_objrole_caps = array( 'edit_posts', 'edit_pages', 'edit_comments', 'manage_links', 'manage_categories', 'manage_groups', 'upload_files' );
								$any_objrole_caps = apply_filters( 'caps_granted_from_any_objrole_rs', $any_objrole_caps );
			
								//dump($any_objrole_caps);
								
								$missing_caps = array_intersect($missing_caps, $any_objrole_caps);

								//dump($missing_caps);
								
								$this->skip_any_object_check = true;
							
								if ( $object_caps = $this->user_can_for_any_object( $missing_caps ) )
									$wp_blogcaps = array_merge($wp_blogcaps, $object_caps);
									
								$this->skip_any_object_check = false;
							}
						}
					}
				}

				$in_process = false;
				return $wp_blogcaps;
			}
		} else { // endif no object_id provided
			
			// if the top level page structure is locked, don't allow non-administrator to delete a top level page either
			if ( 'delete_page' == $args[0] ) {
				if ( ! is_content_administrator_rs() && scoper_get_option( 'lock_top_pages' ) ) {
					if ( $page = get_post( $args[2] ) ) {
						if ( empty( $page->post_parent ) ) {
							$in_process = false;
							return false;
						}
					}
				}
			}
		}
		
		$object_id = (int) $args[2];
		
		global $wpdb;
		
		// since WP user_has_cap filter does not provide an object type / data source arg,
		// we determine data source and object type based on association to required cap(s)
		$object_types = $scoper->cap_defs->object_types_from_caps($rs_reqd_caps);

		// If an object id was provided, all required caps must share a common data source (object_types array is indexed by src_name)
		if ( count($object_types) > 1 || ! count($object_types) ) {
			
			if ( $object_type = $scoper->data_sources->get_from_uri('type', 'post', $object_id) ) {
				$object_types = array( 'post' => array( $object_type => true ) );
			} else {
				rs_notice ( 'Error: user has_cap call is not valid for specified object_id because required capabilities pertain to more than one data source.' . ' ' . implode(', ', $orig_reqd_caps) );
				$in_process = false;
				return array();
			}
		}
		
		$src_name = key($object_types);
		if ( ! $src = $scoper->data_sources->get($src_name) ) {
			rs_notice ( sprintf( 'Role Scoper Config Error (%1$s): Data source (%2$s) is not defined', 'flt_user_has_cap', $src_name ) );  
			$in_process = false;
			return array();
		}
		
		// If cap definition(s) did not specify object type (as with "read" cap), enlist help detecting it
		reset($object_types);
		if ( (count($object_types[$src_name]) == 1) && key($object_types[$src_name]) )
			$object_type = key($object_types[$src_name]);
		else {
			$object_type = $scoper->data_sources->detect('type', $src, $object_id);
		}
		
		// if this is a term administration request, route to user_can_admin_terms()
		if ( ! isset($src->object_types[$object_type]) && $scoper->taxonomies->is_member($object_type) ) {
			if ( count($rs_reqd_caps) == 1 ) {  // technically, should support multiple caps here
				if ( $cap_def = $scoper->cap_defs->get( $reqd_caps[0] ) ) {  
					if ( $cap_def->op_type == OP_ADMIN_RS ) {
						// always pass through any assigned blog caps which will not be involved in this filtering
						$rs_reqd_caps = array_fill_keys( $rs_reqd_caps, 1 );
						$undefined_reqd_caps = array_diff_key( $wp_blogcaps, $rs_reqd_caps);
					
						if ( $scoper->admin->user_can_admin_terms($object_type, $object_id, $user) ) {
							$in_process = false;
							return array_merge($undefined_reqd_caps, $rs_reqd_caps);
						} else {
							$in_process = false;
							return $undefined_reqd_caps;	// required caps we scrutinized are excluded from this array
						}
					}
				}
			}
		}
		
		// Workaround to deal with WP core's checking of publish cap prior to storing categories
		// Store terms to DB in advance of any cap-checking query which may use those terms to qualify an operation
		if ( in_array('publish_posts', $rs_reqd_caps) && ! empty($_POST) && $object_id ) {
			foreach ( $src->uses_taxonomies as $taxonomy ) {
				$stored_terms = $scoper->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS, $object_id);

				$post_var = isset( $src->http_post_vars->$taxonomy ) ? $src->http_post_vars->$taxonomy : $taxonomy;
				
				$selected_terms =  isset( $_POST[$post_var] ) ? $_POST[$post_var] : array();
				
				if ( $set_terms = $scoper->filters_admin->flt_pre_object_terms($selected_terms, $taxonomy) ) {
					$set_terms = array_map('intval', $set_terms);
					$set_terms = array_unique($set_terms);

					if ( $set_terms != $stored_terms )
						wp_set_object_terms( $object_id, $set_terms, $taxonomy );
						
					// delete any buffered cap check results which were queried prior to storage of these object terms
					if ( isset($hascap_object_ids[$src_name][$object_type]) )
						unset($hascap_object_ids[$src_name][$object_type]);
				}
			}
		}

		
		// If caps pertain to more than one object type, filter will probably return empty set, but let it pass in case of strange and unanticipated (yet valid) usage
		
		// Before querying for caps on this object, check whether it was put in the
		// global buffer (page_cache / post_cache / listed_ids).  If so, run the same
		// query for ALL the pages/posts/entities in the buffer, and buffer the results. 
		//
		// (This is useful when front end code must check caps for each post 
		// to determine whether to display 'edit' link, etc.)

		// now that object type is known, retrieve / construct memory cache of all ids which satisfy capreqs
		sort($rs_reqd_caps);
		$capreqs_key = md5( serialize($rs_reqd_caps) . ! $scoper->query_interceptor->require_full_object_role );  // see ScoperAdmin::user_can_admin_object
		
		
		// is the requested object a revision or attachment?
		$maybe_revision = ( 'post' == $src_name &&  awp_ver('2.6') && ! isset($hascap_object_ids[$src_name][$object_type][$capreqs_key][$object_id]) );

		$maybe_attachment = strpos($_SERVER['SCRIPT_NAME'], 'p-admin/upload.php') || strpos($_SERVER['SCRIPT_NAME'], 'p-admin/media.php');

		if ( $object_id && ( $maybe_revision || $maybe_attachment ) ) {
			if ( ! $_post = wp_cache_get($object_id, 'posts') ) {	
				if ( $_post = & scoper_get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $object_id)) )
					wp_cache_add($_post->ID, $_post, 'posts');
			}
		
			if ( $_post ) {
				if ( 'revision' == $_post->post_type ) {
					require_once( 'lib/revisions_lib_rs.php' );					
					$revisions = rs_get_post_revisions($_post->post_parent, 'inherit', array( 'fields' => constant('COL_ID_RS'), 'return_flipped' => true ) );						
				}

				//todo: eliminate redundant post query (above by detect method to determine object type)
				if ( ( 'revision' == $_post->post_type ) || ( 'attachment' == $_post->post_type ) ) {
					$object_id = $_post->post_parent;
				
					if ( ! $_parent = wp_cache_get($_post->post_parent, 'posts') ) {
						if ( $object_id )
							if ( $_parent = & scoper_get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $_post->post_parent)) )
								wp_cache_add($_post->post_parent, $_parent, 'posts');
					}
				
					if ( $_parent ) {
						$object_type = $_parent->post_type;
					
						// compensate for WP's requirement of posts cap for attachment editing, regardless of whether it's attached to a post or page
						if ( ( $maybe_attachment || ( 'revision' == $_post->post_type ) ) && ( 'page' == $object_type ) ) {
							if ( 'edit_others_posts' == $rs_reqd_caps[0] )
								$rs_reqd_caps[0] = 'edit_others_pages';
								
							elseif ( 'delete_others_posts' == $rs_reqd_caps[0] )
								$rs_reqd_caps[0] = 'delete_others_pages';
								
							elseif ( 'edit_posts' == $rs_reqd_caps[0] )
								$rs_reqd_caps[0] = 'edit_pages';
								
							elseif ( 'delete_posts' == $rs_reqd_caps[0] )
								$rs_reqd_caps[0] = 'delete_pages';
						}
					} elseif ( 'attachment' == $_post->post_type ) {
						// special case for unattached uploads: uploading user should have their way with them
						if( $_post->post_author == $current_user->ID )
							$rs_reqd_caps[0] = 'read';
					}
				}
			}
		}

		if ( ! isset($hascap_object_ids[$src_name][$object_type][$capreqs_key]) || ! isset($tested_object_ids[$src_name][$object_type][$capreqs_key][$object_id]) ) {
			// Check whether Object ids meeting specified capreqs were already memcached during this http request
			if ( 'post' == $src_name ) {
				global $wp_object_cache;
			}
	
			// there's too much happening on the dashboard (and too much low-level query filtering) to buffer listed IDs reliably
			if ( ! strpos($script_name, 'p-admin/index.php') ) {
				// If we have a cache of all currently listed object ids, limit capreq query to those ids
				if ( isset($scoper->listed_ids[$src_name]) )
					$listed_ids = array_keys($scoper->listed_ids[$src_name]);
	
				elseif ( ( 'post' == $src_name ) && ! empty($wp_object_cache->cache['posts']) && is_array($wp_object_cache->cache['posts']) )
					$listed_ids = array_keys($wp_object_cache->cache['posts']);
				else
					$listed_ids = array();
			} else
				$listed_ids = array();
			
			// make sure this object is in the list
			$listed_ids[] = $object_id;
			
			if ( isset( $tested_object_ids[$src_name][$object_type][$capreqs_key] ) )
				$tested_object_ids[$src_name][$object_type][$capreqs_key] = $tested_object_ids[$src_name][$object_type][$capreqs_key] + array_fill_keys($listed_ids, true);
			else
				$tested_object_ids[$src_name][$object_type][$capreqs_key] = array_fill_keys($listed_ids, true);
				
			// If a listing buffer exists, query on its IDs.  Otherwise just for this object_id
			$id_in = " AND $src->table.{$src->cols->id} IN ('" . implode("', '", array_unique($listed_ids)) . "')";

			$query_key = $capreqs_key . $id_in;
			
			// As of 1.1, using subselects in where clause instead
			//$join = $scoper->query_interceptor->flt_objects_join('', $src_name, $object_type, $this_args );
			
			if ( isset($hascap_object_ids[$src_name][$object_type][$query_key]) )
				$okay_ids = $hascap_object_ids[$src_name][$object_type][$query_key];
			
			else {
				if ( isset($args['use_term_roles']) )
					$use_term_roles = $args['use_term_roles'];
				else
					$use_term_roles = $src->uses_taxonomies && scoper_get_otype_option( 'use_term_roles', $src_name, $object_type );	
	
				$use_object_roles = ( empty($src->no_object_roles) ) ? scoper_get_otype_option( 'use_object_roles', $src_name, $object_type ) : false;
				
				$this_args = array('object_type' => $object_type, 'user' => $user, 'use_term_roles' => $use_term_roles, 'use_object_roles' => $use_object_roles, 'skip_teaser' => true );
				
				//dump($rs_reqd_caps);
				
				$where = $scoper->query_interceptor->objects_where_role_clauses($src_name, $rs_reqd_caps, $this_args );
			
				if ( $use_object_roles && $scoper->query_interceptor->require_full_object_role )
					$this->require_full_object_role = false;	// return just-used temporary switch back to normal
				
				if ( $where )
					$where = "AND ( $where )";

				$query = "SELECT $src->table.{$src->cols->id} FROM $src->table WHERE 1=1 $where $id_in";

				$okay_ids = scoper_get_col($query);
				
				// If set of listed ids is not known, each current_user_can call will generate a new query construction
				// But if the same query is generated, use buffered result
				if ( ! empty($okay_ids) )
					$okay_ids = array_fill_keys($okay_ids, true);

				if ( count($listed_ids) > 1 ) {
					// bulk post/page deletion is broken by hascap buffering
					if ( empty($_GET['doaction']) || ( ('delete_post' != $args[0]) && ('delete_page' != $args[0]) ) )
						$hascap_object_ids[$src_name][$object_type][$capreqs_key] = $okay_ids;
				}
				$hascap_object_ids[$src_name][$object_type][$query_key] = $okay_ids;
			}

		} else {
			 // results of this same has_cap inquiry are already stored (from another call within current http request)
			$okay_ids = $hascap_object_ids[$src_name][$object_type][$capreqs_key];
		}

		// if we redirected the cap check to revision parent, credit all the revisions for passing results
		if ( isset($okay_ids[$object_id]) && ! empty($revisions) ) {
			$okay_ids = $okay_ids + $revisions;

			// bulk post/page deletion is broken by hascap buffering
			if ( empty($_GET['doaction']) || ( ('delete_post' != $args[0]) && ('delete_page' != $args[0]) ) )
				$hascap_object_ids[$src_name][$object_type][$capreqs_key] = $okay_ids;
			
			if ( ! empty($query_key) )
				$hascap_object_ids[$src_name][$object_type][$query_key] = $okay_ids;
		}
		
		//dump($okay_ids);
		
		$rs_reqd_caps = array_fill_keys( $rs_reqd_caps, true );
		
		if ( ! $okay_ids || ! isset($okay_ids[$object_id]) ) {
			//d_echo("object_id $object_id not okay!" );
			//rs_errlog( "object_id $object_id not okay!" );
			
			$in_process = false;
			return array_diff_key( $wp_blogcaps, $rs_reqd_caps);	// required caps we scrutinized are excluded from this array
		} else {
			if ( $restore_caps = array_diff($orig_reqd_caps, array_keys($rs_reqd_caps) ) )
				$rs_reqd_caps = $rs_reqd_caps + array_fill_keys($restore_caps, true);

			//$test = array_merge( $wp_blogcaps, $rs_reqd_caps );
			//dump($test);
			
			//rs_errlog( 'RETURNING:' );
			//rs_errlog( serialize(array_merge($wp_blogcaps, $rs_reqd_caps)) );

			$in_process = false;
			return array_merge($wp_blogcaps, $rs_reqd_caps);
		}
	}
	
	
	// Try to generate missing has_cap object_id arguments for problematic caps
	// Ideally, this would be rendered unnecessary by updated current_user_can calls in WP core or other offenders
	function generate_missing_object_id($required_cap) {
		global $scoper;
		
		if ( has_filter('generate_missing_object_id_rs') ) {
			if ( $object_id = apply_filters('generate_missing_object_id_rs', 0, $required_cap) )
				return $object_id;
		}
		
		if ( ! $cap_def = $scoper->cap_defs->get($required_cap) )
			return;
		
		if ( ! empty($cap_def->is_taxonomy_cap) ) {
			if ( ! $src_name = $scoper->taxonomies->member_property($cap_def->is_taxonomy_cap, 'source', 'name') )
				return;
		}
		
		// WP core edit_post function requires edit_published_posts or edit_published_pages cap to save a post to "publish" status, but does not pass a post ID
		// Similar situation with edit_others_posts, publish_posts.
		// So... insert the object ID from POST vars
		if ( empty($src_name) )
			$src_name = $scoper->cap_defs->member_property($required_cap, 'src_name');
		
		if ( ! empty( $_POST ) ) {
			// special case for comment post ID
			if ( ! empty( $_POST['comment_post_ID'] ) )
				$_POST['post_ID'] = $_POST['comment_post_ID'];
				
			if ( ! $id = $scoper->data_sources->get_from_http_post('id', $src_name) ) {

				if ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/async-upload.php' ) ) {
					if ( $attach_id = $scoper->data_sources->get_from_http_post('attachment_id', $src_name) ) {
						if ( $attach_id ) {
							global $wpdb;
							$id = scoper_get_var( "SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = '$attach_id'" );
							if ( $id > 0 )
								return $id;
						}
					}
				} elseif ( ! $id && ! empty($_POST['id']) ) // in case normal POST variable differs from ajax variable
					$id = $_POST['id'];
			}

			/* on the moderation page, admin-ajax tests for moderate_comments without passing any ID */
			if ( ('moderate_comments' == $required_cap) )
				if ( $comment = get_comment( $id ) )
					return $comment->comment_post_ID;
			
			if ( $id > 0 )
				return $id;
				
			// special case for adding categories
			if ( ( 'manage_categories' == $required_cap ) ) {
				if ( ! empty($_POST['newcat_parent']) )
					return $_POST['newcat_parent'];
				elseif ( ! empty($_POST['category_parent']) )
					return $_POST['category_parent'];
			}
				
			
		} elseif ( defined('XMLRPC_REQUEST') ) {
			global $xmlrpc_post_id_rs;
			if ( ! empty($xmlrpc_post_id_rs) )
				return $xmlrpc_post_id_rs;
		} else {
			//rs_errlog("checking uri for source $src_name");
			$id = $scoper->data_sources->get_from_uri('id', $src_name);
			if ( $id > 0 )
				return $id;
		}
	}
	
	
	// Some users with term or object roles are now able to view and edit certain 
	// content, if only the unscoped core would let them in the door.  For example, you can't 
	// load edit-pages.php unless current_user_can('edit_pages') blog-wide.
	//
	// This policy is sensible for unscoped users, as it hides stuff they can't have.
	// But it is needlessly oppressive to those who walk according to the law of the scoping. 
	// Subvert the all-or-nothing paradigm by reporting a blog-wide cap if the user has 
	// the capability for any taxonomy.
	//
	// Due to subsequent query filtering, this does not unlock additional content blog-wide.  
	// It merely enables us to run all pertinent content through our gauntlet (rather than having 
	// some contestants disqualified before we arrive at the judging stand).
	//
	// A happy side effect is that, in a fully scoped blog, all non-administrator users can be set
	// to "Subscriber" blogrole so the failure state upon accidental Role Scoper disabling 
	// is overly narrow access, not overly open.
	function user_can_for_any_term($reqd_caps, $user = '') {
		global $scoper;
	
		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}
		
		// Instead of just intersecting the missing reqd_caps with termcaps from all term_roles,
		// require each subset of caps with matching src_name, object type and op_type to 
		// all be satisfied by the same role (any assigned term role).  This simulates flt_objects_where behaviour.
		
		$grant_caps = array();

		$caps_by_otype = $scoper->cap_defs->organize_caps_by_otype($reqd_caps);
		
		foreach ( $caps_by_otype as $src_name => $otypes ) {
			$src = $scoper->data_sources->get($src_name);
		
			if ( empty($src->uses_taxonomies) )
				continue;
			
			foreach ( $otypes as $this_otype_caps ) { // keyed by object_type
				$caps_by_op = $scoper->cap_defs->organize_caps_by_op($this_otype_caps);

				foreach ( $caps_by_op as $this_op_caps ) { // keyed by op_type
					$roles = $scoper->role_defs->qualify_roles($this_op_caps);

					foreach ($src->uses_taxonomies as $taxonomy) {
						if ( ! isset($user->term_roles[$taxonomy]) )
							$user->term_roles[$taxonomy] = $user->get_term_roles_daterange($taxonomy);				// call daterange function populate term_roles property - possible perf enhancement for subsequent code even though we don't conider content_date-limited roles here
							
						if ( array_intersect_key($roles, agp_array_flatten( $user->term_roles[$taxonomy], false ) ) )	// okay to include all content date ranges because can_for_any_term checks are only preliminary measures to keep the admin UI open
							$grant_caps = array_merge($grant_caps, $this_op_caps);
					}
				}
			}
		}	
		
		if ( $grant_caps )
			return array_fill_keys($reqd_caps, true);
		else
			return array();
	}
	
	// used by flt_user_has_cap prior to failing blogcaps requirement
	// Note that this is not to be called if an object_id was provided to (or detected by) flt_user_has_cap
	// This is primarily a way to ram open a closed gate prior to selectively re-closing it ourself
	function user_can_for_any_object($reqd_caps, $user = '') {
		global $wpdb;
		global $scoper;
		
		if ( ! empty( $scoper->ignore_object_roles ) ) {
			// use this to force cap via blog/term role for Write Menu item
			$scoper->ignore_object_roles = false;
			return array();
		}
		
		$check_caps = $scoper->cap_defs->get_base_caps($reqd_caps); // convert 'edit_others', etc. to equivalent base cap
		
		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}
		
		if ( $roles = $scoper->role_defs->qualify_object_roles($check_caps) ) {
			
			// a user might have the caps via object role even if not via blog role or term role
			if ( $user_object_roles = $scoper->role_defs->get_applied_object_roles($user) ) {
				
				if ( array_intersect_key($roles, $user_object_roles) )
					return array_fill_keys($reqd_caps, true);
			}
		}
		
		return array();
	}
}
 
?>