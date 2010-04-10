<?php

function wp_cache_phase2() {
	global $cache_filename, $cache_acceptable_files, $wp_cache_gzip_encoding, $super_cache_enabled, $cache_rebuild_files, $wp_cache_gmt_offset, $wp_cache_blog_charset, $wp_cache_last_gc;
	global $cache_max_time, $wp_cache_request_uri, $super_cache_enabled, $wp_cache_object_cache;
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'In WP Cache Phase 2', 5 );

	$wp_cache_gmt_offset   = get_option( 'gmt_offset' ); // caching for later use when wpdb is gone. http://wordpress.org/support/topic/224349
	$wp_cache_blog_charset = get_option( 'blog_charset' );

	wp_cache_mutex_init();
	if(function_exists('add_action') && ( !defined( 'WPLOCKDOWN' ) || ( defined( 'WPLOCKDOWN' ) && constant( 'WPLOCKDOWN' ) == '0' ) ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Setting up WordPress actions', 5 );
		// Post ID is received
		add_action('publish_post', 'wp_cache_post_edit', 0);
		add_action('edit_post', 'wp_cache_post_change', 0); // leaving a comment called edit_post
		add_action('delete_post', 'wp_cache_post_edit', 0);
		add_action('publish_phone', 'wp_cache_post_edit', 0);
		// Coment ID is received
		add_action('trackback_post', 'wp_cache_get_postid_from_comment', 99);
		add_action('pingback_post', 'wp_cache_get_postid_from_comment', 99);
		add_action('comment_post', 'wp_cache_get_postid_from_comment', 99);
		add_action('edit_comment', 'wp_cache_get_postid_from_comment', 99);
		add_action('wp_set_comment_status', 'wp_cache_get_postid_from_comment', 99, 2);
		// No post_id is available
		add_action('switch_theme', 'wp_cache_no_postid', 99); 
		add_action('edit_user_profile_update', 'wp_cache_no_postid', 99); 

		add_action('wp_cache_gc','wp_cache_gc_cron');

		do_cacheaction( 'add_cacheaction' );
	}

	if ( is_admin() ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Not caching wp-admin requests.', 5 );
		return false;
	}

	if ( $_SERVER["REQUEST_METHOD"] == 'POST' || !empty( $_POST ) || get_option( 'gzipcompression' ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Not caching POST request.', 5 );
		return false;
	}

	if ( $wp_cache_object_cache && !empty( $_GET ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Not caching GET request while object cache storage enabled.', 5 );
		return false;
	}

	if ( isset( $_GET[ 'preview' ] ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Not caching preview post.', 2 );
		return false;
	}

	if ( !empty( $_GET ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Supercache caching disabled. Non empty GET request.', 5 );
		$super_cache_enabled = false;
	}

	$script = basename($_SERVER['PHP_SELF']);
	if (!in_array($script, $cache_acceptable_files) && wp_cache_is_rejected($wp_cache_request_uri)) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'URI rejected. Not Caching', 2 );
		return false;
	}
	if (wp_cache_user_agent_is_rejected()) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "USER AGENT ({$_SERVER[ 'HTTP_USER_AGENT' ]}) rejected. Not Caching", 4 );
		return;
	}
	if($wp_cache_gzip_encoding)
		header('Vary: Accept-Encoding, Cookie');
	else
		header('Vary: Cookie');
	ob_start( 'wp_cache_ob_callback' ); 
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Created output buffer', 4 );

	// restore old supercache file temporarily
	if( $super_cache_enabled && $cache_rebuild_files ) {
		$user_info = wp_cache_get_cookies_values();
		$do_cache = apply_filters( 'do_createsupercache', $user_info );
		if( $user_info == '' || $do_cache === true ) {
			$dir = get_current_url_supercache_dir();
			$files_to_check = array( $dir . 'index.html', $dir . 'index.html.gz' );
			foreach( $files_to_check as $cache_file ) {
				if( !@file_exists( $cache_file . '.needs-rebuild' ) )
					continue;
				$mtime = @filemtime($cache_file . '.needs-rebuild');
				if( $mtime && (time() - $mtime) < 30 ) {
					if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Rebuild file renamed to cache file temporarily", 3 );
					@rename( $cache_file . '.needs-rebuild', $cache_file );
				}
				// cleanup old files or if rename fails
				if( @file_exists( $cache_file . '.needs-rebuild' ) ) {
					if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Rebuild file deleted", 3 );
					@unlink( $cache_file . '.needs-rebuild' );
				}
			}
		}
	}

	if( !isset( $cache_max_time ) )
		$cache_max_time = 600;
	$last_gc = get_option( "wpsupercache_gc_time" );

	if( !$last_gc ) {
		update_option( 'wpsupercache_gc_time', time() );
	}
	$next_gc = $cache_max_time < 1800 ? $cache_max_time : 600;
	if( $last_gc < ( time() - $next_gc ) ) {
		update_option( 'wpsupercache_gc_time', time() );

		global $wp_cache_shutdown_gc;
		if( !isset( $wp_cache_shutdown_gc ) || $wp_cache_shutdown_gc == 0 ) {
			if(!wp_next_scheduled('wp_cache_gc')) {
				wp_schedule_single_event(time() + 10 , 'wp_cache_gc');
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'scheduled wp_cache_gc for 10 seconds time.', 5 );
			}
		} else {
			global $time_to_gc_cache;
			$time_to_gc_cache = 1; // tell the "shutdown gc" to run!
		}
	}
}

function wpcache_logged_in_message() {
	echo '<!-- WP Super Cache did not cache this page because you are logged in and "Don\'t cache pages for logged in users" is enabled. -->';
}

if ( !function_exists( 'wp_cache_user_agent_is_rejected' ) ) {
	function wp_cache_user_agent_is_rejected() {
		global $cache_rejected_user_agent;

		if (!function_exists('apache_request_headers')) return false;
		$headers = apache_request_headers();
		if (!isset($headers["User-Agent"])) return false;
		foreach ($cache_rejected_user_agent as $expr) {
			if (strlen($expr) > 0 && stristr($headers["User-Agent"], $expr))
				return true;
		}
		return false;
	}
}

function wp_cache_get_response_headers() {
	if(function_exists('apache_response_headers')) {
		flush();
		$headers = apache_response_headers();
	} else if(function_exists('headers_list')) {
		$headers = array();
		foreach(headers_list() as $hdr) {
			list($header_name, $header_value) = explode(': ', $hdr, 2);
			$headers[$header_name] = $header_value;
		}
	} else
		$headers = null;

	return $headers;
}

function wp_cache_is_rejected($uri) {
	global $cache_rejected_uri;

	$auto_rejected = array( '/wp-admin/', 'xmlrpc.php', 'wp-app.php' );
	foreach( $auto_rejected as $u ) {
		if( strstr( $uri, $u ) )
			return true; // we don't allow caching of wp-admin for security reasons
	}
	foreach ($cache_rejected_uri as $expr) {
		if( $expr != '' && preg_match( "~$expr~", $uri ) )
			return true;
	}
	return false;
}

function wp_cache_mutex_init() {
	global $use_flock, $mutex, $cache_path, $mutex_filename, $sem_id, $blog_cache_dir, $wp_cache_mutex_disabled;

	if( isset( $wp_cache_mutex_disabled ) && $wp_cache_mutex_disabled )
		return true;

	if(!is_bool($use_flock)) {
		if(function_exists('sem_get')) 
			$use_flock = false;
		else
			$use_flock = true;
	}

	$mutex = false;
	if ($use_flock)  {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Created mutex lock on filename: {$blog_cache_dir}{$mutex_filename}", 5 );
		$mutex = @fopen($blog_cache_dir . $mutex_filename, 'w');
	} else {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Created mutex lock on semaphore: $sem_id", 5 );
		$mutex = @sem_get($sem_id, 1, 0644 | IPC_CREAT, 1);
	}
}

function wp_cache_writers_entry() {
	global $use_flock, $mutex, $cache_path, $mutex_filename, $wp_cache_mutex_disabled;

	if( isset( $wp_cache_mutex_disabled ) && $wp_cache_mutex_disabled )
		return true;

	if( !$mutex ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "(writers entry) mutex lock not created. not caching.", 2 );
		return false;
	}

	if ($use_flock) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "grabbing lock using flock()", 5 );
		flock($mutex,  LOCK_EX);
	} else {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "grabbing lock using sem_acquire()", 5 );
		sem_acquire($mutex);
	}

	return true;
}

function wp_cache_writers_exit() {
	global $use_flock, $mutex, $cache_path, $mutex_filename, $wp_cache_mutex_disabled;

	if( isset( $wp_cache_mutex_disabled ) && $wp_cache_mutex_disabled )
		return true;

	if( !$mutex ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "(writers exit) mutex lock not created. not caching.", 2 );
		return false;
	}

	if ($use_flock) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "releasing lock using flock()", 5 );
		flock($mutex,  LOCK_UN);
	} else {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "releasing lock using sem_release()", 5 );
		sem_release($mutex);
	}
}

function wp_cache_ob_callback( $buffer ) {
	global $wp_cache_pages;
	if( defined( 'DONOTCACHEPAGE' ) )
		return $buffer;
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Output buffer callback', 4 );

	if ( isset( $wp_cache_pages[ 'single' ] ) && $wp_cache_pages[ 'single' ] == 1 && is_single() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'pages' ] ) && $wp_cache_pages[ 'pages' ] == 1 && is_page() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'archives' ] ) && $wp_cache_pages[ 'archives' ] == 1 && is_archive() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'tag' ] ) && $wp_cache_pages[ 'tag' ] == 1 && is_tag() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'category' ] ) && $wp_cache_pages[ 'category' ] == 1 && is_category() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'frontpage' ] ) && $wp_cache_pages[ 'frontpage' ] == 1 && is_front_page() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'home' ] ) && $wp_cache_pages[ 'home' ] == 1 && is_home() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'search' ] ) && $wp_cache_pages[ 'search' ] == 1 && is_search() ) {
		return $buffer;
	} elseif ( isset( $wp_cache_pages[ 'feed' ] ) && $wp_cache_pages[ 'feed' ] == 1 && is_feed() ) {
		return $buffer;
	}
	$buffer = &wp_cache_get_ob( $buffer );
	wp_cache_shutdown_callback();
	return $buffer;
}


function wp_cache_get_ob(&$buffer) {
	global $cache_enabled, $cache_path, $cache_filename, $meta_file, $wp_start_time, $supercachedir;
	global $new_cache, $wp_cache_meta, $file_expired, $blog_id, $cache_compression;
	global $wp_cache_gzip_encoding, $super_cache_enabled, $cached_direct_pages;
	global $wp_cache_404, $gzsize, $supercacheonly, $wp_cache_gzip_first, $wp_cache_gmt_offset;
	global $blog_cache_dir, $wp_cache_request_uri, $wp_supercache_cache_list;
	global $wp_cache_not_logged_in, $wp_cache_object_cache, $cache_max_time;

	$new_cache = true;
	$wp_cache_meta = '';

	/* Mode paranoic, check for closing tags 
	 * we avoid caching incomplete files */
	if ( $buffer == '' ) {
		$new_cache = false;
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) {
			wp_cache_debug( "Buffer is blank. Output buffer may have been corrupted by another plugin or this is a redirected URL. Look for text 'ob_start' in the files of your plugins directory.", 2 );
			$buffer .= "\n<!-- Page not cached by WP Super Cache. Blank Page. Check output buffer usage by plugins. -->\n";
		}
	}

	if ( $wp_cache_404 && false == apply_filters( 'wpsupercache_404', false ) ) {
		$new_cache = false;
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) {
			wp_cache_debug( "404 file not found not cached", 2 );
			$buffer .= "\n<!-- Page not cached by WP Super Cache. 404. -->\n";
		}
	}

	if (!preg_match('/(<\/html>|<\/rss>|<\/feed>)/i',$buffer) ) {
		$new_cache = false;
		if( false === strpos( $_SERVER[ 'REQUEST_URI' ], 'robots.txt' ) ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) {
				wp_cache_debug( "No closing html tag. Not caching.", 2 );
				$buffer .= "\n<!-- Page not cached by WP Super Cache. No closing HTML tag. Check your theme. -->\n";
			}
		} else {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "robots.txt detected. Not caching.", 2 );
		}
	}
	
	if( !$new_cache )
		return $buffer;

	$duration = wp_cache_microtime_diff($wp_start_time, microtime());
	$duration = sprintf("%0.3f", $duration);
	$buffer .= "\n<!-- Dynamic page generated in $duration seconds. -->\n";

	if( !wp_cache_writers_entry() ) {
		$buffer .= "\n<!-- Page not cached by WP Super Cache. Could not get mutex lock. -->\n";
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Could not get mutex lock. Not caching.", 1 );
		return $buffer;
	}

	$dir = get_current_url_supercache_dir();
	$supercachedir = $cache_path . 'supercache/' . preg_replace('/:.*$/', '',  $_SERVER["HTTP_HOST"]);
	if( !empty( $_GET ) || is_feed() || ( $super_cache_enabled == true && is_dir( substr( $supercachedir, 0, -1 ) . '.disabled' ) ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Supercache disabled: GET or feed detected or disabled by config.", 2 );
		$super_cache_enabled = false;
	}

	$tmp_wpcache_filename = $cache_path . uniqid( mt_rand(), true ) . '.tmp';

	$supercacheonly = false;
	if( $super_cache_enabled ) {
		if ( wp_cache_get_cookies_values() == '' ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Anonymous user detected. Only creating Supercache file.", 3 );
			$supercacheonly = true;
		}
	}

	if ( $wp_cache_not_logged_in && wp_cache_get_cookies_values() != '' ) {
		$super_cache_enabled = false;
		$cache_enabled = false;
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Not caching for known user.', 5 );
	}

	if ( $wp_cache_object_cache ) { // half on mode when using the object cache
		if ( wp_cache_get_cookies_values() != '' ) {
			$cache_enabled = false;
		}
		$super_cache_enabled = false;
		$supercacheonly = false;
		wp_cache_init(); // PHP5 destroys objects during shutdown
	}

	if ( $cache_enabled ) {
		// Open wp-cache cache file
		if ( !$supercacheonly && ( !@file_exists( $blog_cache_dir . $cache_filename ) || ( @file_exists( $blog_cache_dir . $cache_filename ) && ( time() - @filemtime( $blog_cache_dir . $cache_filename ) ) > 5 ) ) ) {
			if ( false == $wp_cache_object_cache ) {
				$fr = @fopen($tmp_wpcache_filename, 'w');
				if (!$fr) {
					if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Error. Supercache could not write to " . str_replace( ABSPATH, '', $cache_path ) . $cache_filename, 1 );
					$buffer .= "<!-- File not cached! Super Cache Couldn't write to: " . str_replace( ABSPATH, '', $cache_path ) . $cache_filename . " -->\n";
					wp_cache_writers_exit();
					return $buffer;
				}
			}
		}
		$user_info = wp_cache_get_cookies_values();
		$do_cache = apply_filters( 'do_createsupercache', $user_info );
		if ( $super_cache_enabled && ( $user_info == '' || $do_cache === true ) ) {

			if( @is_dir( $dir ) == false )
				@wp_mkdir_p( $dir );

			$cache_fname = "{$dir}index.html";
			$tmp_cache_filename = $dir . uniqid( mt_rand(), true ) . '.tmp';
			if ( !@file_exists( $cache_fname ) || ( @file_exists( $cache_fname ) && ( time() - @filemtime( $cache_fname ) ) > 5 ) ) {
				$fr2 = @fopen( $tmp_cache_filename, 'w' );
				if (!$fr2) {
					if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Error. Supercache could not write to " . str_replace( ABSPATH, '', $tmp_cache_filename ), 1 );
					$buffer .= "<!-- File not cached! Super Cache Couldn't write to: " . str_replace( ABSPATH, '', $tmp_cache_filename ) . " -->\n";
					@fclose( $fr );
					@unlink( $tmp_wpcache_filename );
					wp_cache_writers_exit();
					return $buffer;
				} elseif ( $cache_compression ) {
					$gz = @fopen( $tmp_cache_filename . ".gz", 'w');
					if (!$gz) {
						if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Error. Supercache could not write to " . str_replace( ABSPATH, '', $tmp_cache_filename ) . ".gz", 1 );
						$buffer .= "<!-- File not cached! Super Cache Couldn't write to: " . str_replace( ABSPATH, '', $tmp_cache_filename ) . ".gz -->\n";
						@fclose( $fr );
						@unlink( $tmp_wpcache_filename );
						@fclose( $fr2 );
						@unlink( $tmp_cache_filename );
						wp_cache_writers_exit();
						return $buffer;
					}
				}
			}
		}
	}

	if ( $cache_enabled || $super_cache_enabled ) {
		$buffer .= "<!-- Cached page generated by WP-Super-Cache on " . gmdate('Y-m-d H:i:s', (time() + ( $wp_cache_gmt_offset * 3600)))  . " -->\n";
	} else {
		$buffer .= "<!-- Live page served on " . gmdate('Y-m-d H:i:s', (time() + ( $wp_cache_gmt_offset * 3600)))  . " -->\n";
	}

	$added_cache = 0;
	$oc_key = get_oc_key();
	if ( preg_match('/<!--mclude|<!--mfunc/', $buffer)) { //Dynamic content
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Dynamic content found in buffer.", 4 );
		$store = preg_replace('|<!--mclude (.*?)-->(.*?)<!--/mclude-->|is', 
				"<!--mclude-->\n<?php include_once('" . ABSPATH . "$1'); ?>\n<!--/mclude-->", $buffer);
		$store = preg_replace('|<!--mfunc (.*?)-->(.*?)<!--/mfunc-->|is', 
				"<!--mfunc-->\n<?php $1 ;?>\n<!--/mfunc-->", $store);
		$store = apply_filters( 'wpsupercache_buffer', $store );
		$wp_cache_meta[ 'dynamic' ] = true;
		/* Clean function calls in tag */
		$buffer = preg_replace('|<!--mclude (.*?)-->|is', '<!--mclude-->', $buffer);
		$buffer = preg_replace('|<!--mfunc (.*?)-->|is', '<!--mfunc-->', $buffer);
		if ( false == $wp_cache_object_cache ) {
			if( $fr )
				fputs($fr, $store);
		} else {
			wp_cache_set( $oc_key, $store, 'supercache', $cache_max_time ); 
		}
	} else {
		$buffer = apply_filters( 'wpsupercache_buffer', $buffer );
		if( $gz || $wp_cache_gzip_encoding ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Gzipping buffer.", 5 );
			$gzdata = gzencode( $buffer . "<!-- Compression = gzip -->", 3, FORCE_GZIP );
			$gzsize = strlen($gzdata);
		}
		if ($wp_cache_gzip_encoding) {
			$wp_cache_meta[ 'headers' ][ 'Content-Encoding' ] = 'Content-Encoding: ' . $wp_cache_gzip_encoding;
			$wp_cache_meta[ 'headers' ][ 'Vary' ] = 'Vary: Accept-Encoding, Cookie';
			// Return uncompressed data & store compressed for later use
			if ( false == $wp_cache_object_cache ) {
				if( $fr ) {
					if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Writing gzipped buffer to wp-cache cache file.", 5 );
					fputs($fr, $gzdata);
				}
			} elseif ( $cache_enabled ) {
				wp_cache_set( $oc_key . ".gz", $gzdata, 'supercache', $cache_max_time ); 
				$added_cache = 1;
			}
		} else { // no compression
			$wp_cache_meta[ 'headers' ][ 'Vary' ] = 'Vary: Cookie';
			if ( false == $wp_cache_object_cache ) {
				if( $fr ) {
					if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Writing non-gzipped buffer to wp-cache cache file.", 5 );
					fputs($fr, $buffer);
				}
			} elseif ( $cache_enabled ) {
				wp_cache_set( $oc_key, $buffer, 'supercache', $cache_max_time ); 
				$added_cache = 1;
			}
		}
		if ( false == $wp_cache_object_cache ) {
			if( $fr2 ) {
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Writing non-gzipped buffer to supercache file.", 5 );
				fputs($fr2, $buffer . '<!-- super cache -->' );
			}
			if( $gz ) {
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Writing gzipped buffer to supercache file.", 5 );
				fwrite($gz, $gzdata );
			}
		}
	}
	$new_cache = true;
	if ( false == $wp_cache_object_cache ) {
		if( $fr ) {
			$supercacheonly = false;
			fclose($fr);
			if ( filesize( $tmp_wpcache_filename ) == 0 ) {
				@unlink( $tmp_wpcache_filename );
			} elseif ( !rename( $tmp_wpcache_filename, $blog_cache_dir . $cache_filename ) ) {
				unlink( $blog_cache_dir . $cache_filename );
				rename( $tmp_wpcache_filename, $blog_cache_dir . $cache_filename );
			}
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Renamed temp wp-cache file to {$blog_cache_dir}$cache_filename", 5 );
			$added_cache = 1;
		}
		if( $fr2 ) {
			fclose($fr2);
			if ( filesize( $tmp_cache_filename ) == 0 ) {
				@unlink( $tmp_cache_filename );
			} elseif ( !@rename( $tmp_cache_filename, $cache_fname ) ) {
				@unlink( $cache_fname );
				@rename( $tmp_cache_filename, $cache_fname );
			}
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Renamed temp supercache file to $cache_fname", 5 );
			$added_cache = 1;
		}
		if( $gz ) {
			fclose($gz);
			if ( filesize( $tmp_cache_filename . '.gz' ) == 0 ) {
				@unlink( $tmp_cache_filename . '.gz' );
			} elseif ( !@rename( $tmp_cache_filename . '.gz', $cache_fname . '.gz' ) ) {
				@unlink( $cache_fname . '.gz' );
				@rename( $tmp_cache_filename . '.gz', $cache_fname . '.gz' );
			}
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Renamed temp supercache gz file to {$cache_fname}.gz", 5 );
			$added_cache = 1;
		}
	}
	if ( $added_cache && isset( $wp_supercache_cache_list ) && $wp_supercache_cache_list ) {
		update_option( 'wpsupercache_count', ( get_option( 'wpsupercache_count' ) + 1 ) );
		$last_urls = (array)get_option( 'supercache_last_cached' );
		if ( count( $last_urls ) >= 10 )
			$last_urls = array_slice( $last_urls, 1, 9 );
		$last_urls[] = array( 'url' => $_SERVER[ 'REQUEST_URI' ], 'date' => date( 'Y-m-d H:i:s' ) );
		update_option( 'supercache_last_cached', $last_urls );
	}
	wp_cache_writers_exit();
	if ( !headers_sent() && isset( $wp_cache_gzip_first ) && 1 == $wp_cache_gzip_first && $wp_cache_gzip_encoding && $gzdata) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Writing gzip content headers. Sending buffer to browser", 5 );
		header( 'Content-Encoding: ' . $wp_cache_gzip_encoding );
		header( 'Vary: Accept-Encoding, Cookie' );
		header( 'Content-Length: ' . $gzsize );
		return $gzdata;
	} else {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Sending buffer to browser", 5 );
		return $buffer;
	}
}

function wp_cache_phase2_clean_cache($file_prefix) {
	global $cache_path, $blog_cache_dir;

	if( !wp_cache_writers_entry() )
		return false;
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Cleaning cache in $blog_cache_dir", 3 );
	if ( ( $handle = @opendir( $blog_cache_dir ) ) ) { 
		while ( false !== ($file = @readdir($handle))) {
			if ( preg_match("/^$file_prefix/", $file) )
				@unlink( $blog_cache_dir . $file );
		}
		closedir($handle);
	}
	wp_cache_writers_exit();
}

function prune_super_cache( $directory, $force = false, $rename = false ) {
	global $cache_max_time, $cache_path, $super_cache_enabled, $cache_rebuild_files, $blog_cache_dir;

	if( !is_admin() && $super_cache_enabled == 0 )
		return false;

	if( !isset( $cache_max_time ) )
		$cache_max_time = 3600;

	$now = time();

	$protected_directories = array( $cache_path . '.htaccess', $cache_path . $blog_cache_dir . 'meta', $cache_path . 'supercache' );

	$oktodelete = false;
	if (is_dir($directory)) {
		if( $dh = @opendir( $directory ) ) {
			$directory = trailingslashit( $directory );
			while( ( $entry = @readdir( $dh ) ) !== false ) {
				if ($entry == '.' || $entry == '..')
					continue;
				$entry = $directory . $entry;
				prune_super_cache( $entry, $force, $rename );
				// If entry is a directory, AND it's not a protected one, AND we're either forcing the delete, OR the file is out of date, 
				if( is_dir( $entry ) && !in_array( $entry, $protected_directories ) && ( $force || @filemtime( $entry ) + $cache_max_time <= $now ) ) {
					// if the directory isn't empty can't delete it
					if( $handle = @opendir( $entry ) ) {
						$donotdelete = false;
						while( !$donotdelete && ( $file = @readdir( $handle ) ) !== false ) {
							if ($file == '.' || $file == '..')
								continue;
							$donotdelete = true;
						}
						closedir($handle);
					}
					if( $donotdelete )
						continue;
					if( !$rename ) {
						@rmdir( $entry );
						if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "gc: deleted $entry", 2 );
					}
				}
			}
			closedir($dh);
		}
	} elseif( is_file($directory) && ($force || @filemtime( $directory ) + $cache_max_time <= $now ) ) {
		$oktodelete = true;
		if( in_array( $directory, $protected_directories ) )
			$oktodelete = false;
		if( $oktodelete && !$rename ) {
			@unlink( $directory );
		} elseif( $oktodelete && $rename ) {
			wp_cache_rebuild_or_delete( $directory );
		}
	}
}

function wp_cache_rebuild_or_delete( $file ) {
	global $cache_rebuild_files;
	if( strpos( $file, '?' ) !== false )
		$file = substr( $file, 0, strpos( $file, '?' ) );
	if( $cache_rebuild_files && substr( $file, -14 ) != '.needs-rebuild' ) {
		if( @rename($file, $file . '.needs-rebuild') ) {
			@touch( $file . '.needs-rebuild' );
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "rebuild_or_gc: rename to {$file}.needs-rebuild", 2 );
		} else {
			@unlink( $file );
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "rebuild_or_gc: deleted $file", 2 );
		}
	} else {
		@unlink( $file );
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "rebuild_or_gc: deleted $file", 2 );
	}
}

function wp_cache_phase2_clean_expired($file_prefix) {
	global $cache_path, $cache_max_time, $blog_cache_dir;

	clearstatcache();
	if( !wp_cache_writers_entry() )
		return false;
	$now = time();
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Cleaning expired cache files in $blog_cache_dir", 2 );
	if ( ( $handle = @opendir( $blog_cache_dir ) ) ) { 
		while ( false !== ($file = readdir($handle))) {
			if ( preg_match("/^$file_prefix/", $file) && 
				(@filemtime( $blog_cache_dir . $file) + $cache_max_time) <= $now  ) {
				@unlink( $blog_cache_dir . $file );
				@unlink( $blog_cache_dir . 'meta/' . str_replace( '.html', '.meta', $file ) );
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Deleting $blog_cache_dir{$file} (plus meta)", 5 );
				continue;
			}
			if($file != '.' && $file != '..') {
				if( is_dir( $blog_cache_dir . $file ) == false && (@filemtime($blog_cache_dir . $file) + $cache_max_time) <= $now  ) {
					if( substr( $file, -9 ) != '.htaccess' ) {
						@unlink($blog_cache_dir . $file);
						if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Deleting $blog_cache_dir{$file}", 5 );
					}
				}
			}
		}
		closedir($handle);
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Doing GC on supercache dir: {$cache_path}supercache", 2 );
		prune_super_cache( $cache_path . 'supercache' );
	}

	wp_cache_writers_exit();
	return true;
}

function wp_cache_shutdown_callback() {
	global $cache_path, $cache_max_time, $file_expired, $file_prefix, $meta_file, $new_cache, $wp_cache_meta, $known_headers, $blog_id, $wp_cache_gzip_encoding, $gzsize, $cache_filename, $supercacheonly, $blog_cache_dir;
	global $wp_cache_blog_charset, $wp_cache_request_uri, $wp_cache_key, $wp_cache_object_cache, $cache_enabled;

	$wp_cache_meta[ 'uri' ] = $_SERVER["SERVER_NAME"].preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', $wp_cache_request_uri); // To avoid XSS attacks
	$wp_cache_meta[ 'blog_id' ] = $blog_id;
	$wp_cache_meta[ 'post' ] = wp_cache_post_id();
	$wp_cache_meta[ 'key' ] = $wp_cache_key;
	$wp_cache_meta = apply_filters( 'wp_cache_meta', $wp_cache_meta );

	$response = wp_cache_get_response_headers();
	foreach ($known_headers as $key) {
		if(isset($response[$key])) {
			$wp_cache_meta[ 'headers' ][ $key ] = "$key: " . $response[$key];
		}
	}
	if (!isset( $response['Last-Modified'] )) {
		$value = gmdate('D, d M Y H:i:s') . ' GMT';
		/* Dont send this the first time */
		/* @header('Last-Modified: ' . $value); */
		$wp_cache_meta[ 'headers' ][ 'Last-Modified' ] = "Last-Modified: $value";
	}
	if ( !isset( $response[ 'Content-Type' ] ) && !isset( $response[ 'Content-type' ] ) ) {
		// On some systems, headers set by PHP can't be fetched from
		// the output buffer. This is a last ditch effort to set the
		// correct Content-Type header for feeds, if we didn't see
		// it in the response headers already. -- dougal
		if (is_feed()) {
			$type = get_query_var('feed');
			$type = str_replace('/','',$type);
			switch ($type) {
				case 'atom':
					$value = "application/atom+xml";
					break;
				case 'rdf':
					$value = "application/rdf+xml";
					break;
				case 'rss':
				case 'rss2':
				default:
					$value = "application/rss+xml";
			}
		} else { // not a feed
			$value = get_option( 'html_type' );
			if( $value == '' )
				$value = 'text/html';
		}
		$value .=  "; charset=\"" . $wp_cache_blog_charset . "\"";

		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Sending 'Content-Type: $value' header.", 2 );
		@header("Content-Type: $value");
		$wp_cache_meta[ 'headers' ][ 'Content-Type' ] = "Content-Type: $value";
	}

	if ( ! $supercacheonly && $new_cache ) {
		if( $wp_cache_gzip_encoding && !in_array( 'Content-Encoding: ' . $wp_cache_gzip_encoding, $wp_cache_meta[ 'headers' ] ) ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Sending gzip headers.", 2 );
			$wp_cache_meta[ 'headers' ][ 'Content-Encoding' ] = 'Content-Encoding: ' . $wp_cache_gzip_encoding;
			$wp_cache_meta[ 'headers' ][ 'Vary' ] = 'Vary: Accept-Encoding, Cookie';
		}

		$serial = serialize($wp_cache_meta);
		if( wp_cache_writers_entry() ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Writing meta file: {$blog_cache_dir}meta/{$meta_file}", 2 );
			if ( false == $wp_cache_object_cache ) {
				$tmp_meta_filename = $blog_cache_dir . 'meta/' . uniqid( mt_rand(), true ) . '.tmp';
				$fr = @fopen( $tmp_meta_filename, 'w');
				if( !$fr )
					@mkdir( $blog_cache_dir . 'meta' );
				$fr = fopen( $tmp_meta_filename, 'w');
				fputs($fr, $serial);
				fclose($fr);
				@chmod( $tmp_meta_filename, 0666 & ~umask());
				if( !@rename( $tmp_meta_filename, $blog_cache_dir . 'meta/' . $meta_file ) ) {
					unlink( $blog_cache_dir . 'meta/' . $meta_file );
					rename( $tmp_meta_filename, $blog_cache_dir . 'meta/' . $meta_file );
				}
			} elseif ( $cache_enabled ) {
				$oc_key = get_oc_key() . ".meta";
				if ( gzip_accepted() )
					$oc_key .= ".gz";
				wp_cache_set( $oc_key, $serial, 'supercache', $cache_max_time );
			}
			wp_cache_writers_exit();
		}
	}
	global $time_to_gc_cache;
	if( isset( $time_to_gc_cache ) && $time_to_gc_cache == 1 ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Executing wp_cache_gc action.", 3 );
		do_action( 'wp_cache_gc' );
	}
}

function wp_cache_no_postid($id) {
	return wp_cache_post_change(wp_cache_post_id());
}

function wp_cache_get_postid_from_comment( $comment_id, $status = 'NA' ) {
	global $super_cache_enabled, $wp_cache_request_uri;
	$comment = get_comment($comment_id, ARRAY_A);
	if ( $status != 'NA' ) {
		$comment[ 'old_comment_approved' ] = $comment[ 'comment_approved' ];
		$comment[ 'comment_approved' ] = $status;
	}
	$postid = $comment['comment_post_ID'];
	// Do nothing if comment is not moderated
	// http://ocaoimh.ie/2006/12/05/caching-wordpress-with-wp-cache-in-a-spam-filled-world
	if ( !preg_match('/wp-admin\//', $wp_cache_request_uri) ) {
		if ( $comment['comment_approved'] == 'delete' && ( isset( $comment[ 'old_comment_approved' ] ) && $comment[ 'old_comment_approved' ] == 0 ) ) { // do nothing if moderated comments are deleted
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Moderated comment deleted. Don't delete any cache files.", 4 );
			return $postid;
		} elseif ( $comment['comment_approved'] == 'spam' ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Spam comment. Don't delete any cache files.", 4 );
			return $postid;
		} elseif( $comment['comment_approved'] == '0' ) {
			if ( $comment[ 'content_type' ] == '' ) {
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Moderated comment. Don't delete supercache file until comment approved.", 4 );
				$super_cache_enabled = 0; // don't remove the super cache static file until comment is approved
			} else {
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Moderated ping or trackback. Not deleting cache files..", 4 );
				return $postid;
			}
		}
	}
	// We must check it up again due to WP bugs calling two different actions
	// for delete, for example both wp_set_comment_status and delete_comment 
	// are called when deleting a comment
	if ($postid > 0)  {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Post $postid changed. Update cache.", 4 );
		return wp_cache_post_change($postid);
	} else {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Unknown post changed. Update cache.", 4 );
		return wp_cache_post_change(wp_cache_post_id());
	}
}

/* Clear out the cache directory. */
function wp_cache_clear_cache() {
	global $cache_path, $wp_cache_object_cache;
	if ( $wp_cache_object_cache ) {
		reset_oc_version();
	} else {
		prune_super_cache( $cache_path . 'supercache/', true );
		prune_super_cache( $cache_path, true );
	}
}

function wp_cache_post_edit($post_id) {
	global $wp_cache_clear_on_post_edit, $cache_path, $blog_cache_dir;
	if( $wp_cache_clear_on_post_edit ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Clearing cache $blog_cache_dir and {$cache_path}supercache/ on post edit per config.", 2 );
		if ( $wp_cache_object_cache ) {
			reset_oc_version();
		} else {
			prune_super_cache( $blog_cache_dir, true );
			prune_super_cache( $cache_path . 'supercache/', true );
		}
	} else {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Clearing cache for post $post_id on post edit.", 2 );
		wp_cache_post_change( $post_id );
	}
}

function wp_cache_post_id_gc( $siteurl, $post_id ) {
	global $cache_path, $wp_cache_object_cache;
	
	if ( $wp_cache_object_cache )
		reset_oc_version();

	$post_id = intval( $post_id );
	if( $post_id == 0 )
		return;

	$permalink = trailingslashit( str_replace( get_option( 'home' ), '', post_permalink( $post_id ) ) );
	$dir = $cache_path . 'supercache/' . $siteurl;
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "wp_cache_post_id_gc clearing cache in $dir{$permalink}.", 4 );
	prune_super_cache( $dir . $permalink, true, true );
	@rmdir( $dir . $permalink );
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "wp_cache_post_id_gc clearing cache in {$dir}page/.", 4 );
	prune_super_cache( $dir . 'page/', true );
}

function wp_cache_post_change($post_id) {
	global $file_prefix, $cache_path, $blog_id, $super_cache_enabled, $blog_cache_dir, $blogcacheid;
	static $last_processed = -1;

	if ($post_id == $last_processed) return $post_id;
	$last_processed = $post_id;
	if( !wp_cache_writers_entry() )
		return $post_id;

	if ( $wp_cache_object_cache )
		reset_oc_version();

	$permalink = trailingslashit( str_replace( get_option( 'siteurl' ), '', post_permalink( $post_id ) ) );
	if( $super_cache_enabled ) {
		$siteurl = trailingslashit( strtolower( preg_replace( '/:.*$/', '', str_replace( 'http://', '', get_option( 'home' ) ) ) ) );
		// make sure the front page has a rebuild file
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Post change: deleting cache files in " . $cache_path . 'supercache/' . $siteurl, 4 );
		prune_super_cache( $cache_path . 'supercache/' . $siteurl . 'index.html', true, true ); 
		prune_super_cache( $cache_path . 'supercache/' . $siteurl . 'index.html.gz', true, true );
		wp_cache_post_id_gc( $siteurl, $post_id );
		if( get_option( 'show_on_front' ) == 'page' ) {
			wp_cache_post_id_gc( $siteurl, get_option( 'page_on_front' ) );
			wp_cache_post_id_gc( $siteurl, get_option( 'page_for_posts' ) );
		}
	}

	$matches = array();
	if ( ($handle = @opendir( $blog_cache_dir . 'meta/' )) ) { 
		while ( false !== ($file = readdir($handle))) {
			if ( preg_match("/^({$file_prefix}{$blogcacheid}.*)\.meta/", $file, $matches) ) {
				$meta_pathname = $blog_cache_dir . 'meta/' . $file;
				$content_pathname = $blog_cache_dir . $matches[1] . ".html";
				$meta = unserialize(@file_get_contents($meta_pathname));
				if( false == is_array( $meta ) ) {
					@unlink($meta_pathname);
					@unlink($content_pathname);
					continue;
				}
				if ($post_id > 0 && $meta) {
					if ($meta[ 'blog_id' ] == $blog_id  && (!$meta[ 'post' ] || $meta[ 'post' ] == $post_id) ) {
						@unlink($meta_pathname);
						@unlink($content_pathname);
						@wp_cache_rebuild_or_delete($cache_path . 'supercache/' . trailingslashit( $meta[ 'uri' ] ) . 'index.html');
						@wp_cache_rebuild_or_delete($cache_path . 'supercache/' . trailingslashit( $meta[ 'uri' ] ) . 'index.html.gz');
					}
				} elseif ($meta[ 'blog_id' ] == $blog_id) {
					@unlink($meta_pathname);
					@unlink($content_pathname);
					@wp_cache_rebuild_or_delete($cache_path . 'supercache/' . trailingslashit( $meta[ 'uri' ] ) . 'index.html');
					@wp_cache_rebuild_or_delete($cache_path . 'supercache/' . trailingslashit( $meta[ 'uri' ] ) . 'index.html.gz');
				}

			}
		}
		closedir($handle);
	}
	wp_cache_writers_exit();
	return $post_id;
}

function wp_cache_microtime_diff($a, $b) {
	list($a_dec, $a_sec) = explode(' ', $a);
	list($b_dec, $b_sec) = explode(' ', $b);
	return $b_sec - $a_sec + $b_dec - $a_dec;
}

function wp_cache_post_id() {
	global $posts, $comment_post_ID, $post_ID;
	// We try hard all options. More frequent first.
	if ($post_ID > 0 ) return $post_ID;
	if ($comment_post_ID > 0 )  return $comment_post_ID;
	if (is_single() || is_page()) return $posts[0]->ID;
	if (isset( $_GET[ 'p' ] ) && $_GET['p'] > 0) return $_GET['p'];
	if (isset( $_POST[ 'p' ] ) && $_POST['p'] > 0) return $_POST['p'];
	return 0;
}

function wp_cache_gc_cron() {
	global $file_prefix, $cache_max_time;
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Cache garbage collection.', 5 );

	if( !isset( $cache_max_time ) )
		$cache_max_time = 600;

	$start = time();
	if( !wp_cache_phase2_clean_expired($file_prefix ) ) {
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( 'Cache Expiry cron job failed. Probably mutex locked.', 1 );
		update_option( 'wpsupercache_gc_time', time() - ( $cache_max_time - 10 ) ); // if GC failed then run it again in one minute
	}
	if( time() - $start > 30 )
		if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Cache Expiry cron job took more than 30 seconds to execute.\nYou should reduce the Expiry Time in the WP Super Cache admin page\nas you probably have more cache files than your server can handle efficiently.", 1 );
}

?>
