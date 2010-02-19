<?php

/**
 * ScoperRewrite PHP class for the WordPress plugin Role Scoper
 * rewrite-rules_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
 
class ScoperRewrite {
	
	function insert_with_markers( $file_path, $marker_text, $insertion ) {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/misc.php' );
			else
				return;
		}

		if ( $insertion || file_exists($file_path) )	
			insert_with_markers( $file_path, $marker_text, explode( "\n", $insertion ) );
	}
	
	
	function update_site_rules( $include_rs_rules = true ) {
		$const_name = ( $include_rs_rules ) ? 'FLUSHING_RULES_RS' : 'CLEARING_RULES_RS';
			
		if ( defined( $const_name ) )
			return;
	
		define( $const_name, true );

		if ( IS_MU_RS ) {
			add_action( 'shutdown', create_function( '', "require_once( 'rewrite-mu_rs.php' ); ScoperRewriteMU::update_mu_htaccess( '$include_rs_rules' );" ) );
		} else {
			if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/misc.php' );
			
			if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/file.php' );

			add_action( 'shutdown', create_function( '', 'global $wp_rewrite; if ( ! empty($wp_rewrite) ) { $wp_rewrite->flush_rules(true); }' ) );
		}
	}
	
	function build_site_rules() {
		$new_rules = '';

		require_once( 'uploads_rs.php' );

		$new_rules .= "\n# BEGIN Role Scoper\n";
		
		$new_rules .= "RewriteEngine On\n\n";

		if ( scoper_get_option( 'feed_link_http_auth' ) ) {
			// workaround for HTTP Authentication with PHP running as CGI
			$new_rules .= "RewriteCond %{HTTP:Authorization} ^(.*)\n";
			$new_rules .= "RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]\n";
		}
	
		if ( IS_MU_RS && scoper_get_option( 'file_filtering' ) )
			$new_rules .= ScoperRewriteMU::build_blog_file_redirects();
		
		$new_rules .= "\n# END Role Scoper\n\n";

		return $new_rules;
	}

	
	function site_config_supports_rewrite() {
		require_once( 'uploads_rs.php' );
		$uploads = scoper_get_upload_info();
		
		if ( false === strpos( $uploads['baseurl'], untrailingslashit( get_option('siteurl') ) ) )
			return false;
		
		// don't risk leaving custom .htaccess files in content folder at deactivation due to difficulty of reconstructing custom path for each blog
		if ( IS_MU_RS ) {
			global $blog_id;
			
			if ( UPLOADS != UPLOADBLOGSDIR . "/$blog_id/files/" )
				return false;
				
			if ( BLOGUPLOADDIR != WP_CONTENT_DIR . "/blogs.dir/$blog_id/files/" )
				return false;
		}
		
		return true;
	}
	
	function update_blog_file_rules( $include_rs_rules = true ) {
		global $blog_id;
		
		scoper_update_option( 'file_htaccess_date', agp_time_gmt() );
		
		$include_rs_rules = $include_rs_rules && scoper_get_option( 'file_filtering' );
		
		if ( ! ScoperRewrite::site_config_supports_rewrite() )
			return;
		elseif ( ! $include_rs_rules )
			$rules = '';
		else
			$rules = ScoperRewrite::build_blog_file_rules();
			
		require_once( 'uploads_rs.php' );
		$uploads = scoper_get_upload_info();
		
		// If a filter has changed MU basedir, don't filter file attachments for this blog because we might not be able to regenerate the basedir for rule removal at RS deactivation
		if ( ! IS_MU_RS || strpos( $uploads['basedir'], "/blogs.dir/$blog_id/files/" ) ) {
			$htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';

			ScoperRewrite::insert_with_markers( $htaccess_path, 'Role Scoper', $rules );
		}
	}
	
	function &build_blog_file_rules() {
		$new_rules = '';
		
		require_once( 'analyst_rs.php' );
		if ( ! $attachment_results = ScoperAnalyst::identify_protected_attachments() )
			return $new_rules;
			
		global $wpdb;

		require_once( 'uploads_rs.php' );
		
		$home_root = parse_url(get_option('home'));
		$home_root = trailingslashit( $home_root['path'] );
		
		$uploads = scoper_get_upload_info();
		
		$baseurl = trailingslashit( $uploads['baseurl'] );
		
		$arr_url = parse_url( $baseurl );
		$rewrite_base = $arr_url['path'];
		
		$file_keys = array();

		if ( $key_results = scoper_get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_rs_file_key'" ) ) {
			foreach ( $key_results as $row )
				$file_keys[$row->post_id] = $row->meta_value;		
		} 
	
		$new_rules = "<IfModule mod_rewrite.c>\n";
		$new_rules .= "RewriteEngine On\n";
		$new_rules .= "RewriteBase $rewrite_base\n\n";
	
		$main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&rs_rewrite=1 [NC,L]\n";
	
		foreach ( $attachment_results as $row ) {
			if ( isset($file_keys[ $row->ID ] ) ) {
				$key = $file_keys[ $row->ID ];
			} else {
				$key = urlencode( str_replace( '.', '', uniqid( strval( rand() ), true ) ) );
				update_post_meta( $row->ID, "_rs_file_key", $key );
			}

			//dump($row->guid);

			if ( false !== strpos( $row->guid, $baseurl ) ) {	// no need to include any attachments which are not in the uploads folder
				$file_path =  str_replace( $baseurl, '', $row->guid );
				$file_path =  str_replace('.', '\.', $file_path );

				$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$file_path" . "$ [NC]\n";
				$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
				$new_rules .= $main_rewrite_rule;
						
				if ( $pos_ext = strrpos( $file_path, '\.' ) ) {
					$thumb_path = substr( $file_path, 0, $pos_ext );
					$ext = substr( $file_path, $pos_ext + 2 );	
							
					$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '-[0-9]{2,4}x[0-9]{2,4}\.' . $ext . "$ [NC]\n";
					$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
					$new_rules .= $main_rewrite_rule;
				}
						
			}
		} // end foreach protected attachment
		
		
		if ( IS_MU_RS && defined('SCOPER_MU_FILE_PROCESSING') ) { // unless SCOPER_MU_FILE_PROCESSING is defined (indicating blogs.php has been modified for compatibility), blogs.php processing will be bypassed for all files
			$content_path = trailingslashit( str_replace( $strip_path, '', str_replace( '\\', '/', WP_CONTENT_DIR ) ) );

			$new_rules .= "\n# Default WordPress cache handling\n";
			$new_rules .= "RewriteRule ^(.*) {$content_path}blogs.php?file=$1 [L]\n";
		}
		
		$new_rules .= "</IfModule>\n";
		
		return $new_rules;
	}
	
	// called by agp_return_file() in abnormal cases where file access is approved, but key for protected file is lost/corrupted in postmeta record or .htaccess file
	function resync_file_rules() {
		// Don't allow this to execute too frequently, to prevent abuse or accidental recursion
		if ( agp_time_gmt() - get_option( 'last_htaccess_resync_rs' ) > 30 ) {
			update_option( 'last_htaccess_resync_rs', agp_time_gmt() );
			
			// Only the files / uploads .htaccess for current blog is regenerated
			scoper_flush_file_rules();
			
			usleep(10000); // Allow 10 milliseconds for server to regather itself following .htaccess update
		} 
	}
} // end class ScoperRewrite
?>