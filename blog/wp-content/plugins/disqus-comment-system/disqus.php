<?php
/*
Plugin Name: DISQUS Comment System
Plugin URI: http://disqus.com/
Description: The DISQUS comment system replaces your WordPress comment system with your comments hosted and powered by DISQUS. Head over to the Comments admin page to set up your DISQUS Comment System.
Author: DISQUS.com <team@disqus.com>
Version: 2.12.7121
Author URI: http://disqus.com/

*/

require_once('lib/api.php');

define('DISQUS_URL',			'http://disqus.com');
define('DISQUS_API_URL',		DISQUS_URL);
define('DISQUS_DOMAIN',			'disqus.com');
define('DISQUS_IMPORTER_URL',	'http://import.disqus.net');
define('DISQUS_MEDIA_URL',		'http://media.disqus.com');
define('DISQUS_RSS_PATH',		'/latest.rss');

function dsq_plugin_basename($file) {
	$file = dirname($file);

	// From WP2.5 wp-includes/plugin.php:plugin_basename()
	$file = str_replace('\\','/',$file); // sanitize for Win32 installs
	$file = preg_replace('|/+|','/', $file); // remove any duplicate slash
	$file = preg_replace('|^.*/' . PLUGINDIR . '/|','',$file); // get relative path from plugins dir

	if ( strstr($file, '/') === false ) {
		return $file;
	}

	$pieces = explode('/', $file);
	return !empty($pieces[count($pieces)-1]) ? $pieces[count($pieces)-1] : $pieces[count($pieces)-2];
}

if ( !defined('WP_CONTENT_URL') ) {
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}
if ( !defined('PLUGINDIR') ) {
	define('PLUGINDIR', 'wp-content/plugins'); // Relative to ABSPATH.  For back compat.
}

define('DSQ_PLUGIN_URL', WP_CONTENT_URL . '/plugins/' . dsq_plugin_basename(__FILE__));

/**
 * DISQUS WordPress plugin version.
 *
 * @global	string	$dsq_version
 * @since	1.0
 */
$dsq_version = '2.12';
$mt_dsq_version = '2.01';
/**
 * Response from DISQUS get_thread API call for comments template.
 *
 * @global	string	$dsq_response
 * @since	1.0
 */
$dsq_response = '';
/**
 * Comment sort option.
 *
 * @global	string	$dsq_sort
 * @since	1.0
 */
$dsq_sort = 1;
/**
 * Flag to determine whether or not the comment count script has been embedded.
 *
 * @global	string	$dsq_cc_script_embedded
 * @since	1.0
 */
$dsq_cc_script_embedded = false;
/**
 * DISQUS API instance.
 *
 * @global	string	$dsq_api
 * @since	1.0
 */
$dsq_api = new DisqusAPI(get_option('disqus_forum_url'), get_option('disqus_api_key'));
/**
 * Copy of global wp_query.
 *
 * @global	WP_Query	$dsq_wp_query
 * @since	1.0
 */
$dsq_wp_query = NULL;

/**
 * Helper functions.
 */

function dsq_legacy_mode() {
	return get_option('disqus_forum_url') && !get_option('disqus_api_key');
}

function dsq_is_installed() {
	return get_option('disqus_forum_url') && get_option('disqus_api_key');
}

function dsq_can_replace() {
	global $id, $post;
	$replace = get_option('disqus_replace');

	if ( 'draft' == $post->post_status )   { return false; }
	if ( !get_option('disqus_forum_url') ) { return false; }
	else if ( 'all' == $replace )          { return true; }

	if ( !isset($post->comment_count) ) {
		$num_comments = 0;
	} else {
		if ( 'empty' == $replace ) {
			// Only get count of comments, not including pings.

			// If there are comments, make sure there are comments (that are not track/pingbacks)
			if ( $post->comment_count > 0 ) {
				// Yuck, this causes a DB query for each post.  This can be
				// replaced with a lighter query, but this is still not optimal.
				$comments = get_approved_comments($post->ID);
				foreach ( $comments as $comment ) {
					if ( $comment->comment_type != 'trackback' && $comment->comment_type != 'pingback' ) {
						$num_comments++;
					}
				}
			} else {
				$num_comments = 0;
			}
		}
		else {
			$num_comments = $post->comment_count;
		}
	}

	return ( ('empty' == $replace && 0 == $num_comments)
		|| ('closed' == $replace && 'closed' == $post->comment_status) );
}

function dsq_manage_dialog($message, $error = false) {
	global $wp_version;

	echo '<div '
		. ( $error ? 'id="disqus_warning" ' : '')
		. 'class="updated fade'
		. ( ($wp_version < 2.5 && $error) ? '-ff0000' : '' )
		. '"><p><strong>'
		. $message
		. '</strong></p></div>';
}

function dsq_sync_comments($post, $comments) {
	global $wpdb;

	// Get last_comment_date id for $post with Disqus metadata
	// (This is the date that is stored in the Disqus DB.)
	$last_comment_date = $wpdb->get_var('SELECT max(comment_date) FROM ' . $wpdb->prefix . 'comments WHERE comment_post_ID=' . intval($post->ID) . " AND comment_agent LIKE 'Disqus/%';");
	if ( $last_comment_date ) {
		$last_comment_date = strtotime($last_comment_date);
	}

	if ( !$last_comment_date ) {
		$last_comment_date = 0;
	}

	foreach ( $comments as $comment ) {
		if ( $comment['imported'] ) {
			continue;
		} else if ( $comment['date'] <= $last_comment_date ) {
			// If comment date of comment is <= last_comment_date, skip comment.
			continue;
		} else {
			// Else, insert_comment
			$commentdata = array(
				'comment_post_ID' => $post->ID,
				'comment_author' => $comment['user']['display_name'],
				'comment_author_email' => $comment['user']['email'],
				'comment_author_url' => $comment['user']['url'],
				'comment_author_IP' => $comment['user']['ip_address'],
				'comment_date' => date('Y-m-d H:i:s', $comment['date']),
				'comment_date_gmt' => date('Y-m-d H:i:s', $comment['date_gmt']),
				'comment_content' => $comment['message'],
				'comment_approved' => 1,
				'comment_agent' => 'Disqus/1.0:' . intval($comment['id']),
				'comment_type' => '',
			);
			wp_insert_comment($commentdata);
		}
	}

	if( isset($_POST['dsq_api_key']) && $_POST['dsq_api_key'] == get_option('disqus_api_key') ) {
		if( isset($_GET['dsq_sync_action']) && isset($_GET['dsq_sync_comment_id']) ) {
			$comment_parts = explode('=', $_GET['dsq_sync_comment_id']);
			if( 'wp_id' == $comment_parts[0] ) {
				$comment_id = intval($comment_parts[1]);
			} else {
				$comment_id = $wpdb->get_var('SELECT comment_ID FROM ' . $wpdb->prefix . 'comments WHERE comment_post_ID=' . intval($post->ID) . " AND comment_agent LIKE 'Disqus/1.0:" . intval($comment_parts[1]) . "'");
			}

			switch( $_GET['dsq_sync_action'] ) {
				case 'mark_spam':
					wp_set_comment_status($comment_id, 'spam');
					echo "<!-- dsq_sync: wp_set_comment_status($comment_id, 'spam') -->";
					break;
				case 'mark_approved':
					wp_set_comment_status($comment_id, 'approve');
					echo "<!-- dsq_sync: wp_set_comment_status($comment_id, 'approve') -->";
					break;
				case 'mark_killed':
					wp_set_comment_status($comment_id, 'hold');
					echo "<!-- dsq_sync: wp_set_comment_status($comment_id, 'hold') -->";
					break;
			}
		}
	}
}

/**
 *  Filters/Actions
 */

function dsq_get_style() {
	echo "<link rel=\"stylesheet\" href=\"" . DISQUS_API_URL ."/stylesheets/" .  strtolower(get_option('disqus_forum_url')) . "/disqus.css?v=2.0\" type=\"text/css\" media=\"screen\" />";
}

add_action('wp_head','dsq_get_style');

function dsq_comments_template($value) {
	global $dsq_response;
	global $dsq_sort;
	global $dsq_api;
	global $post;

	if ( ! (is_single() || is_page() || $withcomments) ) {
		return;
	}

	if ( !dsq_can_replace() ) {
		return $value;
	}

	if ( dsq_legacy_mode() ) {
		return dirname(__FILE__) . '/comments-legacy.php';
	}

	$permalink = get_permalink();
	$title = get_the_title();
	$excerpt = get_the_excerpt();

	$dsq_sort = get_option('disqus_sort');
	if ( is_numeric($_COOKIE['disqus_sort']) ) {
		$dsq_sort = $_COOKIE['disqus_sort'];
	}

	if ( is_numeric($_GET['dsq_sort']) ) {
		setcookie('disqus_sort', $_GET['dsq_sort']);
		$dsq_sort = $_GET['dsq_sort'];
	}

	// Call "get_thread" API method.
	$dsq_response = $dsq_api->get_thread($post, $permalink, $title, $excerpt);
	if( $dsq_response < 0 ) {
		return false;
	}
	// Sync comments with database.
	dsq_sync_comments($post, $dsq_response['posts']);

	// TODO: If a disqus-comments.php is found in the current template's
	// path, use that instead of the default bundled comments.php
	//return TEMPLATEPATH . '/disqus-comments.php';

	return dirname(__FILE__) . '/comments.php';
}

function dsq_comment_count() {
	global $dsq_cc_script_embedded, $dsq_wp_query, $wp_query;

	if ( $dsq_cc_script_embedded ) {
		return;
	} else if ( (is_single() || is_page() || $withcomments || is_feed()) ) {
		return;
	} else if ( $dsq_wp_query->is_feed ) {
		// Protect ourselves from other plugins which begin their own loop
		// and clobber $wp_query.
		return;
	}

	?>



	<?php

	$dsq_cc_script_embedded = true;
}

// Mark entries in index to replace comments link.
function dsq_comments_number($comment_text) {
	global $post;

	if ( dsq_can_replace() ) {
		ob_start();
		the_permalink();
		$the_permalink = ob_get_contents();
		ob_end_clean();

		return '</a><noscript><a href="http://' . strtolower(get_option('disqus_forum_url')) . '.' . DISQUS_DOMAIN . '/?url=' . $the_permalink .'">View comments</a></noscript><a class="dsq-comment-count" href="' . $the_permalink . '#disqus_thread" wpid="' . $post->ID . '">Comments</a>';
	} else {
		return $comment_text;
	}
}

function dsq_bloginfo_url($url) {
	if ( get_feed_link('comments_rss2') == $url ) {
		return 'http://' . strtolower(get_option('disqus_forum_url')) . '.' . DISQUS_DOMAIN . DISQUS_RSS_PATH;
	} else {
		return $url;
	}
}

// For WordPress 2.0.x
function dsq_loop_start() {
	global $comment_count_cache, $dsq_wp_query, $wp_query;

	if ( !isset($dsq_wp_query) || is_null($dsq_wp_query) ) {
		$dsq_wp_query = $wp_query;
	}

	if ( isset($comment_count_cache) ) {
		foreach ( $comment_count_cache as $key => $value ) {
			if ( 0 == $value ) {
				$comment_count_cache[$key] = -1;
			}
		}
	}
}

function dsq_add_pages() {
	global $menu, $submenu;

	add_submenu_page('edit-comments.php', 'DISQUS', 'DISQUS', 8, 'disqus', dsq_manage);

	// TODO: This does not work in WP2.0.

	// Replace Comments top-level menu link with link to our page
	foreach ( $menu as $key => $value ) {
		if ( 'edit-comments.php' == $menu[$key][2] ) {
			$menu[$key][2] = 'edit-comments.php?page=disqus';
		}
	}

	add_options_page('DISQUS', 'DISQUS', 8, 'disqus', dsq_manage);
}

function dsq_manage() {
	require_once('admin-header.php');
	include_once('manage.php');
}

// Always add Disqus management page to the admin menu
add_action('admin_menu', 'dsq_add_pages');

function dsq_warning() {
	global $wp_version;

	if ( !get_option('disqus_forum_url') && !isset($_POST['forum_url']) && $_GET['page'] != 'disqus' ) {
		dsq_manage_dialog('You must <a href="edit-comments.php?page=disqus">configure the plugin</a> to enable Disqus Comments.', true);
	}

	if ( dsq_legacy_mode() && $_GET['page'] == 'disqus' ) {
		dsq_manage_dialog('Disqus Comments has not yet been configured. (<a href="edit-comments.php?page=disqus">Click here to configure</a>)');
	}
}

function dsq_check_version() {
	global $dsq_api;

	$latest_version = $dsq_api->wp_check_version();
	if ( $latest_version ) {
		dsq_manage_dialog('You are running an old version of the Disqus Comments plugin. Please <a href="http://disqus.com/comments/wordpress">check the website</a> for updates.');
	}
}

add_action('admin_notices', 'dsq_warning');
add_action('admin_notices', 'dsq_check_version');

// Only replace comments if the disqus_forum_url option is set.
add_filter('comments_template', 'dsq_comments_template');
add_filter('comments_number', 'dsq_comments_number');
add_filter('bloginfo_url', 'dsq_bloginfo_url');
add_action('loop_start', 'dsq_loop_start');

// For comment count script.
if ( !get_option('disqus_cc_fix') ) {
	add_action('loop_end', 'dsq_comment_count');
}
add_action('wp_footer', 'dsq_comment_count');

?>
