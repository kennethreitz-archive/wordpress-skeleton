<?php

require_once( 'rewrite-rules_rs.php' );

/**
 * ScoperRewriteMU PHP class for the WordPress plugin Role Scoper
 * rewrite-mu_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
class ScoperRewriteMU {

	// directly inserts essential RS rules into the main wp-mu .htaccess file
	function update_mu_htaccess( $include_rs_rules = true ) {
		$include_rs_rules = $include_rs_rules && scoper_get_option( 'file_filtering' );
		
		if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
			include_once( ABSPATH . '/wp-admin/includes/file.php' );
		
		$home_path = get_home_path();
		$htaccess_path = $home_path .'.htaccess';

		if ( ! file_exists($htaccess_path) )
			return;
		
		$contents = file_get_contents( $htaccess_path );

		$pos_rs_start = strpos( $contents, "\n# BEGIN Role Scoper" );

		$default_rule = 'RewriteRule ^(.*/)?files/$ index.php [L]';
		
		if ( $pos_def = strpos( $contents, $default_rule ) ) {
			$fp = fopen($htaccess_path, 'w');
			
			if ( $pos_rs_start )
				fwrite($fp, substr( $contents, 0, $pos_rs_start ) );
			else
				fwrite($fp, substr( $contents, 0, $pos_def ) );
				
			if ( $include_rs_rules )
				fwrite($fp, ScoperRewrite::build_site_rules() );

			fwrite($fp, substr( $contents, $pos_def ) );
	
			fclose($fp);
		}
	}
	
	// In case a modified or future MU regenerates the site .htaccess, filter contents to include RS rules
	function insert_site_rules( $rules = '' ) {
		$default_rule = 'RewriteRule ^(.*/)?files/$ index.php [L]';
		
		if ( $pos_def = strpos( $rules, $default_rule ) )
			$rules = substr( $rules, 0, $pos_def ) . ScoperRewrite::build_site_rules() . substr( $rules, $pos_def );
		
		return $rules;
	}
	
	function build_blog_file_redirects() {
		global $wpdb, $blog_id, $base;
		
		if ( ! ScoperRewrite::site_config_supports_rewrite() )
			return '';

		$new_rules = '';
		$orig_blog_id = $blog_id;	
		
		$strip_path = str_replace( '\\', '/', trailingslashit(ABSPATH) );
				
		require_once( 'analyst_rs.php' );
		
		$new_rules .= "\n#Run file requests through blog-specific .htaccess to support filtering.  Files that pass through filtering will be redirected to blogs.php for cache handling.\n";
		
		$results = scoper_get_results( "SELECT blog_id, path FROM $wpdb->blogs ORDER BY blog_id" );
		
		foreach ( $results as $row ) {
			switch_to_blog( $row->blog_id );
			
			if ( $results = ScoperAnalyst::identify_protected_attachments() ) {
				// WP-mu content rules are only inserted if defined uploads path matches this default structure
				$dir = ABSPATH . UPLOADBLOGSDIR . "/{$row->blog_id}/files/";
				$url = trailingslashit( $siteurl ) . UPLOADBLOGSDIR . "/{$row->blog_id}/files/";
				
				$uploads = apply_filters( 'upload_dir', array( 'path' => $dir, 'url' => $url, 'subdir' => '', 'basedir' => $dir, 'baseurl' => $url, 'error' => false ) );
				
				$content_base = str_replace( $strip_path, '', str_replace( '\\', '/', $uploads['basedir'] ) );

				$path = trailingslashit($row->path);
						
				if ( $base && ( '/' != $base ) )
					if ( 0 === strpos( $path, $base ) )
						$path = substr( $path, strlen($base) );

				// If a filter has changed basedir, don't filter file attachments for this blog
				if ( strpos( $content_base, "/blogs.dir/{$row->blog_id}/files/" ) )
					$new_rules .= "RewriteRule ^{$path}files/(.*) {$content_base}$1 [L]\n";			//RewriteRule ^blog1/files/(.*) wp-content/blogs.dir/2/files/$1 [L]
			}
		}
		
		switch_to_blog( $orig_blog_id );
		
		return $new_rules;
	}
	
	
	// remove RS rules from every .htaccess file in the wp-MU "files" folders
	function clear_all_file_rules() {
		global $wpdb, $blog_id;
		$blog_ids = scoper_get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id" );
		$orig_blog_id = $blog_id;
		
		$siteurl = get_option( 'siteurl' );

			
		foreach ( $blog_ids as $id ) {
			switch_to_blog( $id );

			// WP-mu content rules are only inserted if uploads path matches this default structure
			$dir = ABSPATH . UPLOADBLOGSDIR . "/$id/files/";
			$url = trailingslashit( $siteurl ) . UPLOADBLOGSDIR . "/$id/files/";
			
			$uploads = apply_filters( 'upload_dir', array( 'path' => $dir, 'url' => $url, 'subdir' => '', 'basedir' => $dir, 'baseurl' => $url, 'error' => false ) );

			$htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';
			
			ScoperRewrite::insert_with_markers( $htaccess_path, 'Role Scoper', '' );
		}
		
		switch_to_blog( $orig_blog_id );
	}

}

?>