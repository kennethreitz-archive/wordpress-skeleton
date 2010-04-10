<?php
/**
 * Implementation of the Disqus v2 API.
 *
 * @author		Disqus <team@disqus.com>
 * @copyright	2007-2008 Big Head Labs
 * @link		http://disqus.com/
 * @package		Disqus
 * @subpackage	lib
 * @version		2.0
 */

require_once('url.php');
require_once(ABSPATH.WPINC.'/http.php');

/** @#+
 * Constants
 */
/**
 * Base URL for Disqus.
 */
define('ALLOWED_HTML', '<b><u><i><h1><h2><h3><code><blockquote><br><hr>');

/**
 * Helper methods for all of the Disqus v2 API methods.
 *
 * @package		Disqus
 * @author		DISQUS.com <team@disqus.com>
 * @copyright	2007-2008 Big Head Labs
 * @version		2.0
 */
class DisqusAPI {
	var $short_name;
	var $forum_api_key;

	function DisqusAPI($short_name=NULL, $forum_api_key=NULL) {
		$this->short_name = $short_name;
		$this->forum_api_key = $forum_api_key;
	}

	function get_forum_list($username, $password) {
		$credentials = base64_encode($username . ':' . $password);
		$response = dsq_urlopen(DISQUS_API_URL . '/api/v2/get_forum_list/', array(
			'credentials'	=> $credentials,
			'response_type'	=> 'php'
		));
		$data = unserialize($response['data']);
		if(!$data || $data['stat'] == 'fail') {
			if($data['err']['code'] == 'bad-credentials') {
				return -2;
			} else {
				return -1;
			}
		}
		return $data['forums'];
	}

	function get_forum_api_key($username, $password, $short_name) {
		$credentials = base64_encode($username . ':' . $password);
		$response = dsq_urlopen(DISQUS_API_URL . '/api/v2/get_forum_api_key/', array(
			'credentials'	=> $credentials,
			'short_name'	=> $short_name,
			'response_type' => 'php'
		));
		$data = unserialize($response['data']);
		if(!$data || $data['stat'] == 'fail') {
			if($data['err']['code'] == 'bad-credentials') {
				return -2;
			} else {
				return -1;
			}
		}

		return $data['forum_api_key'];
	}

	function get_thread($post, $permalink, $title, $excerpt) {
		$title = strip_tags($title, ALLOWED_HTML);
		$title = urlencode($title);

		$excerpt = strip_tags($excerpt, ALLOWED_HTML);
		$excerpt = urlencode($excerpt);
		$excerpt = substr($excerpt, 0, 300);

		$thread_meta = $post->ID . ' ' . $post->guid;

		$response = @dsq_urlopen(DISQUS_API_URL . '/api/v2/get_thread/', array(
			'short_name'	=> $this->short_name,
			'thread_url'	=> $permalink,
			'thread_meta'	=> $thread_meta,
			'response_type'	=> 'php',
			'title'			=> $title,
			'message'		=> $excerpt,
			'api_key'		=> $this->forum_api_key,
			'source'		=> 'DsqWordPress20',
			'state_closed'	=> ($post->comment_status == 'closed') ? '1' : '0'
		));

		$data = unserialize($response['data']);
		if(!$data || $data['stat'] == 'fail') {
			if($data['err']['code'] == 'bad-key') {
				return -2;
			} else {
				return -1;
			}
		}

		return $data;
	}

	function import_wordpress_comments($wxr) {
		$http = new WP_Http();
		$response = $http->request(
			DISQUS_IMPORTER_URL . '/api/import-wordpress-comments/',
			array(
				'method' => 'POST',
				'body' => array(
					'forum_url' => $this->short_name,
					'forum_api_key' => $this->forum_api_key,
					'response_type'	=> 'php',
					'wxr' => $wxr,
				)
			)
		);
		$data = unserialize($response['body']);
		if (!$data || $data['stat'] == 'fail') {
			return -1;
		}
		return $data['import_id'];
	}

	function get_import_status($import_id) {
		$response = @dsq_urlopen(DISQUS_IMPORTER_URL . '/api/get-import-status/', array(
			'forum_url' => $this->short_name,
			'forum_api_key' => $this->forum_api_key,
			'import_id' => $import_id,
			'response_type'	=> 'php'
		));

		$data = unserialize($response['data']);
		if(!$data || $data['stat'] == 'fail') {
			return -1;
		}
		return $data;
	}

}

?>
