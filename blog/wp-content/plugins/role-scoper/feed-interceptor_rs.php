<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * FeedInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * feed-interceptor_rs.php
 * 
 * Provides optional http authentication for RSS feeds.
 *
 * Also allows replacement of readable RSS feed content with a permalink to the post.
 * This may be desirable since browsers sometimes cache the feed content after user logout.
 *
 * @author 		Sören Weber, with adaptations by Kevin Behrens
 * 
 */

define( 'HTTP_AUTH_RS', 'http_auth' );
define( 'PERMALINK_PLACEHOLDER_RS', '%permalink%' );

define( 'RSS_FULL_CONTENT_RS', 'full_content' );
define( 'RSS_EXCERPT_ONLY_RS', 'excerpt_only' );
define( 'RSS_TITLE_ONLY_RS', 'title_only' );

// Override WP's get_currentuserinfo in order to do the login
// via HTTP auth. Adapted from WP core get_currentuserinfo
if ( ! empty($_GET[HTTP_AUTH_RS]) ) {
	if ( function_exists( 'get_currentuserinfo' ) )
		define( 'HTTP_AUTH_DISABLED_RS', true );
	else {
		function get_currentuserinfo() {
			// Use HTTP auth instead of cookies
			global $current_user;
	
			if (!empty($current_user))
				return;
			
			// Some apache versions prepend "REDIRECT_" to server variable name, according to http://www.besthostratings.com/articles/http-auth-php-cgi.html
			if ( isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && ! isset($_SERVER['HTTP_AUTHORIZATION']) )
				$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

			// Workaround for HTTP Authentication with PHP running as CGI (htaccess rule copies authentication data into HTTP_AUTHORIZATION)
			if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
				$ha = base64_decode( substr($_SERVER['HTTP_AUTHORIZATION'],6) );
				list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $ha);
				unset($ha);
			}

			if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])
				|| !wp_login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			{
				header('WWW-Authenticate: Basic realm="'. get_bloginfo('name'). '"');
				header('HTTP/1.0 401 Unauthorized');
	
				scoper_load_textdomain(); // otherwise this is only loaded for wp-admin
		
				die( __('Access denied: Incorrect credentials supplied.', 'scoper') );
			}
	
			$user_login = $_SERVER['PHP_AUTH_USER'];
			wp_set_current_user(0, $user_login);
		}
	}
}
 
class FeedInterceptor_RS {	
	function FeedInterceptor_RS() {
		$http_auth_if = scoper_get_option( 'feed_link_http_auth' );
		
		if ( 'logged' == $http_auth_if ) {
			global $current_user;
			$http_auth_if = ! empty($current_user->ID);
		}

		if ( $http_auth_if ) {
			add_filter('feed_link', array(&$this, 'filter_feed_link'));
			add_filter('category_feed_link', array(&$this, 'filter_feed_link'));
			add_filter('tag_feed_link', array(&$this, 'filter_feed_link'));
			add_filter('author_feed_link', array(&$this, 'filter_feed_link'));
			add_filter('post_comments_feed_link', array(&$this, 'filter_feed_link'));
		}
			
		add_filter('the_content_rss', array(&$this, 'filter_the_content_rss'));
		add_filter('the_excerpt_rss', array(&$this, 'filter_the_excerpt_rss'));
		
		if ( is_feed() ) {
			// Only filter the_content if we're sure this is an RSS request (TODO: is this still necessary?)
			if ( ! empty($_GET[HTTP_AUTH_RS]) )
				add_filter('the_content', array(&$this, 'filter_the_content_rss'));
		}
	}
	
	function replace_feed_teaser_placeholder($content) {
		global $post;

		$search[] = PERMALINK_PLACEHOLDER_RS;
		$replace[] = get_permalink($post->ID);
		$content = str_replace($search, $replace, $content);
		return $content;
	}

	function filter_rss( $text, $subject = 'content' ) {
		global $post;

		if ( ! empty( $post->scoper_teaser ) )
			return $text;

		if ( $post->post_status == 'private')
			$feed_privacy = scoper_get_option( 'rss_private_feed_mode' );
		else
			$feed_privacy = scoper_get_option( 'rss_nonprivate_feed_mode' );

		switch ($feed_privacy) {
			case RSS_FULL_CONTENT_RS:
				return $text;
		
			case RSS_EXCERPT_ONLY_RS:
				if ( 'content' == $subject )
					return apply_filters( 'the_excerpt_rss', get_the_excerpt(true) );
				else
					return $text;
					
			default:
				if ( $msg = scoper_get_option( 'feed_teaser' ) ) {
					if ( defined('SCOPER_TRANSLATE_TEASER') ) {
						scoper_load_textdomain(); // otherwise this is only loaded for wp-admin
	
						$msg = translate( $msg, 'scoper');
					
						if ( ! empty($msg) && ! is_null($msg) && is_string($msg) )
							$msg = htmlspecialchars_decode( $msg );
					}
				
					return $this->replace_feed_teaser_placeholder( $msg );
				}
		}
	}
	
	// Called when using HTTP auth -- changes the article content for items which are not already filtered by Hidden Content Teaser
	function filter_the_content_rss($content) {
		return $this->filter_rss($content, 'content');
	}

	// Called when using HTTP auth -- changes the article excerpt for items which are not already filtered by Hidden Content Teaser
	function filter_the_excerpt_rss($excerpt) {
		return $this->filter_rss($excerpt, 'excerpt');
	}

	// Rewrites RSS feed links to support http authentication
	// if the user is logged in
	function filter_feed_link($output) {
		$delim = (strpos($output, '?') === false) ? '?' : '&';
		return $output. $delim . HTTP_AUTH_RS . '=1';
	}
}
?>