<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

function agp_return_file( $file_path, $attachment_id = 0 ) {
	require_once( 'uploads_rs.php' );
	$uploads = scoper_get_upload_info();
	
	if ( false === strpos( $file_path, $uploads['basedir'] ) )
		$file_path = untrailingslashit($uploads['basedir']) . "/$file_path";
	
	$file_url = str_replace( untrailingslashit($uploads['basedir']), untrailingslashit($uploads['baseurl']), $file_path );
		
	//rs_errlog( "agp_return_file: $file_path" );
	
	if ( ! $attachment_id ) {
		global $wpdb;			// we've already confirmed that this user can read the file; if it is attached to more than one post any corresponding file key will do
		
		// Resized copies have -NNNxNNN suffix, but the base filename is stored as attachment.  Strip the suffix out for db query.
		$orig_file_url = preg_replace( "/-[0-9]{2,4}x[0-9]{2,4}./", '.', $file_url );

		//rs_errlog( "orig_file_url: $orig_file_url" );
		
		if ( ! $attachment_id = scoper_get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = '$orig_file_url' AND post_parent > 0 LIMIT 1" ) )
			return;
	}
	
	if ( ! $key = get_post_meta( $attachment_id, '_rs_file_key' ) ) {
		// The key was lost from DB, so regenerate it (and files / uploads .htaccess)
		require_once( 'rewrite-rules_rs.php' );
		ScoperRewrite::resync_file_rules();
		
		// If the key is still not available, fail out to avoid recursion
		if ( ! $key = get_post_meta( $attachment_id, '_rs_file_key' ) )
			exit(0);
	} elseif ( ! empty($_GET['rs_file_key']) && ( $_GET['rs_file_key'] == $key ) ) {
		// Apparantly, the .htaccess rules contain an entry for this file, but with invalid file key.  URL with this valid key already passed through RewriteRules.  
		// Regenerate .htaccess, but don't risk recursion by redirecting again.
		require_once( 'rewrite-rules_rs.php' );
		ScoperRewrite::resync_file_rules();
		
		exit(0);
	}

	if ( is_array($key) )
		$key = reset($key);
	
	if ( IS_MU_RS ) {
		$basedir = parse_url( $uploads['basedir'] );
		$baseurl = parse_url( $uploads['baseurl'] );
	
		global $base;
		
		$file_url = str_replace( ABSPATH, $baseurl['scheme'] . '://'. $baseurl['host'] . $base , $file_path );
		$file_url = str_replace( '\\', '/', $file_url );
	}
		
	$redirect = $file_url . "?rs_file_key=$key";

	//rs_errlog( "redirect: $redirect" );
	
	wp_redirect( $redirect );
	exit(0);
}


class AttachmentFilters_RS {

	function user_can_read_file( $file ) {
		$return_attachment_id = 0;
		$matched_public_post = array();
		return AttachmentFilters_RS::_user_can_read_file( $file, $return_attachment_id, $matched_public_post );
	}
	
	function _user_can_read_file( $file, &$return_attachment_id, &$matched_published_post, $uploads = '' ) {
		if ( ! is_array( $uploads ) || empty($uploads['basedir']) ) {
			require_once( 'uploads_rs.php' );
			$uploads = scoper_get_upload_info();
		}
	
		//rs_errlog('_user_can_read_file');
		
		$file_path = $uploads['basedir'] . "/$file";
		
		// don't filter the direct file URL request if filtering is disabled, or if the request is from wp-admin
		if ( defined('DISABLE_QUERYFILTERS_RS') || is_content_administrator_rs() || ! scoper_get_option( 'file_filtering' )
		|| ( ! empty($_SERVER['HTTP_REFERER']) && ( false !== strpos($_SERVER['HTTP_REFERER'], '/wp-admin' ) ) && ( false !== strpos($_SERVER['HTTP_REFERER'], get_option('siteurl') . '/wp-admin' ) ) ) ) {
			// note: image links from wp-admin should now never get here due to http_referer RewriteRule, but leave above check just in case - inexpensive since we're checking for wp-admin before calling get_option

			//rs_errlog("skipping filtering for $file_path");
			return true;
		}
		
		//rs_errlog("$file_path exists.");	
		
		global $wpdb, $wp_query;

		$file_url = $uploads['baseurl'] . "/$file";

		// Resized copies have -NNNxNNN suffix, but the base filename is stored as attachment.  Strip the suffix out for db query.
		$orig_file_url = preg_replace( "/-[0-9]{2,4}x[0-9]{2,4}./", '.', $file_url );

		$qry = "SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent > 0 AND guid = '$orig_file_url'";
		$results = scoper_get_results( $qry );
		$matched_published_post = array();
		$return_attachment_id = 0;
		
		if ( empty($results) ) {
			global $scoper;

			$scoper->cap_interceptor->skip_any_object_check = true;
			$scoper->cap_interceptor->skip_any_term_check = true;
			$can_edit_others = current_user_can('edit_others_posts') || current_user_can('edit_others_pages');
			$scoper->cap_interceptor->skip_any_object_check = false;
			$scoper->cap_interceptor->skip_any_term_check = false;

			//rs_errlog( "unattached upload, returning $can_edit_others" );

			return $can_edit_others;
		} else {
			// set global flag (checked by flt_user_has_cap, which filters current_user_Can)
			global $scoper_checking_attachment_access;
			$scoper_checking_attachment_access = true;
			
			foreach ( $results as $attachment ) {
				//rs_errlog( "found attachment: " . serialize($attachment) );
				if ( is_content_administrator_rs() )
					$return_attachment_id = $attachment->ID;

				if ( $attachment->post_parent ) {
					if ( $parent_post = scoper_get_row( "SELECT post_type, post_status FROM $wpdb->posts WHERE ID = '$attachment->post_parent' LIMIT 1" ) ) {
						$object_type = $parent_post->post_type;
						$containing_post_status = $parent_post->post_status;

						// Only return content that is attached to published (potentially including private) posts/pages
						// If some other statuses for published posts are introduced in later WP versions, 
						// the failure mode here will be to overly suppress attachments
						if ( ( 'publish' == $containing_post_status ) || ( 'private' == $containing_post_status ) ) {
							if ( current_user_can( "read_$object_type", $attachment->post_parent ) ) {
								$return_attachment_id = $attachment->ID;
								break;
							} else {
								global $current_user;
								$matched_published_post[$object_type] = $attachment->post_name;
							}
						}
					}
				}
			}
			
			// clear global flag
			$scoper_checking_attachment_access = false;
		}
		
		if ( $return_attachment_id )
			return true;
	}
	
	
	// handle access to uploaded file where request was a direct file URL, which was rewritten according to our .htaccess addition
	function parse_query_for_direct_access ( &$query ) {
		if ( empty($query->query_vars['attachment']) || ( false === strpos($_SERVER['QUERY_STRING'], 'rs_rewrite') ) )
			return;

		$file = $query->query_vars['attachment'];

		require_once( 'uploads_rs.php' );
		$uploads = scoper_get_upload_info();
	
		$return_attachment_id = 0;
		$matched_published_post = array();
		if ( AttachmentFilters_RS::_user_can_read_file( $file, $return_attachment_id, $matched_published_post, $uploads ) ) {
			agp_return_file($file, $return_attachment_id);
			return;
		}
		
		// file access was not granted.  Determine if teaser message should be triggered
		if ( file_exists( $uploads['basedir'] . "/$file" ) ) {
			
			if ( $matched_published_post && scoper_get_otype_option('do_teaser', 'post') ) {
				foreach ( array_keys($matched_published_post) as $object_type ) {
					if ( $use_teaser_type = scoper_get_otype_option('use_teaser', 'post',  $object_type) ) {
						if ( $matched_published_post[$object_type] ) {
							if ( ! defined('SCOPER_QUIET_FILE_404') ) {
								// note: subsequent act_attachment_access will call impose_post_teaser()
								$will_tease = true; // will_tease flag only used within this function
								$wp_query->query_vars['attachment'] = $matched_published_post[$object_type];
								break;
							}
						}
					}
				}
			} 
			
			status_header(401); // Unauthorized
			
			if ( empty($will_tease) ) {
				// User is not qualified to access the requested attachment, and no teaser will apply
				
				// Normally, allow the function to return for WordPress 404 handling 
				// But end script execution here if requested attachment is a media type (or if definition set)
				// Linking pages won't want WP html returned in place of inaccessable image / video
				if ( defined('SCOPER_QUIET_FILE_404') ) {
					exit;
				}
				
				// this may not be necessary
				$wp_query->is_404 = true;
				$wp_query->is_single = true;
				$wp_query->is_singular = true;
				$wp_query->query_vars['is_single'] = true;
			}
				
		}
		
	}
} // end class
?>