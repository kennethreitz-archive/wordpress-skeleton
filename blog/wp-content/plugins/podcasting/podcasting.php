<?php

/*
Plugin Name: Podcasting
Version: 2.3
Plugin URI: http://plugins.spiralwebconsulting.com/podcasting.html
Description: Podcasting enhances WordPress' existing podcast support by adding multiple iTunes-compatible feeds, media players, and an easy to use interface.
Author: Spiral Web Consulting
Author URI: http://spiralwebconsulting.com/
*/

define('PODCASTING_VERSION', '2.3');

# Register Podcasting's taxonomy
register_taxonomy('podcast_format', 'custom_field');

# Setup the post installation actions
add_action('activate_podcasting/podcasting.php', 'podcasting_install');

# Include the admin CSS
add_action('admin_head', 'podcasting_css');

# Include the settings information
include_once('podcasting-settings.php');

# Include the metabox
include_once('podcasting-metabox.php');

# Include the feed
include_once('podcasting-feed.php');

# Include the player
include_once('podcasting-player.php');

# Include the podPress importer
include_once('podpress-importer.php');

/**
 * Post installation procedures
 */
function podcasting_install() {
	# Setup the default taxonomy
	wp_insert_term('Default Format', 'podcast_format');
	
	# Add Podcasting options to the database
	add_option('pod_title', get_option('blogname'), "The podcast's title");
	add_option('pod_tagline', get_option('blogdescription'), "The podcast's tagline");
	add_option('pod_disabled_enclose', false);
	add_option('pod_itunes_summary', '', 'iTunes summary');
	add_option('pod_itunes_author', '', 'iTunes author');
	add_option('pod_itunes_image', '', 'iTunes image');
	add_option('pod_itunes_cat1', '', 'iTunes category 1');
	add_option('pod_itunes_cat2', '', 'iTunes category 2');
	add_option('pod_itunes_cat3', '', 'iTunes category 3');
	add_option('pod_itunes_keywords', '', 'iTunes keywords');
	add_option('pod_itunes_explicit', '', 'iTunes explicit');
	add_option('pod_itunes_ownername', '', 'iTunes owner name');
	add_option('pod_itunes_owneremail', '', 'iTunes owner email');
	add_option('pod_formats', '', 'Explict settings for podcast formats');
	add_option('pod_player_flashvars', '', 'Podcasting player flashvars');
	add_option('pod_audio_width', '290', 'Podcasting player width');
	add_option('pod_player_use_video', 'no');
	add_option('pod_player_location', '', '');
	add_option('pod_player_text_above', '', '');
	add_option('pod_player_text_before', '', '');
	add_option('pod_player_text_below', '', '');
	add_option('pod_player_text_link', '', '');
	add_option('pod_player_width', '400', 'Podcast player width');
	add_option('pod_player_height', '300', 'Podcast player height');
	add_option('pod_video_flashvars', '', 'Podcasting video flashvars');
	add_option('pod_accept_fail', 'no', 'Accept enclosure failure');
}

/**
 * Adds Podcasting's CSS to the admin section
 **/
function podcasting_css()
{
	echo '<link rel="stylesheet" href="' . plugins_url("/podcasting/podcasting-admin.css") .'" type="text/css" />';
}

add_action('plugin_action_links_' . plugin_basename(__FILE__), 'pod_filter_plugin_actions');

// Add settings option
function pod_filter_plugin_actions($links) {
	$new_links = array();
	
	$new_links[] = '<a href="options-general.php?page=podcasting-settings.php">Settings</a>';
	
	return array_merge($new_links, $links);
}

add_filter('plugin_row_meta', 'pod_filter_plugin_links', 10, 2);

// Add FAQ and support information
function pod_filter_plugin_links($links, $file)
{
	if ( $file == plugin_basename(__FILE__) )
	{
		$links[] = '<a href="http://plugins.spiralwebconsulting.com/forums/viewforum.php?f=8">FAQ</a>';
		$links[] = '<a href="http://plugins.spiralwebconsulting.com/forums/viewforum.php?f=10">Support</a>';
		$links[] = '<a href="http://plugins.spiralwebconsulting.com/podcasting.html#donate">Donate</a>';
	}
	
	return $links;
}

/**
 * Take a potentially invalid URL and corrects it
 * @param p_url - the url
 * @return a valid URL
 */
function podcasting_urlencode($p_url) {
	$ta = parse_url($p_url);
	if (!empty($ta[scheme])) { $ta[scheme].='://'; }
	if (!empty($ta[pass]) and !empty($ta[user])) {
		$ta[user].=':';
		$ta[pass]=rawurlencode($ta[pass]).'@';
	} elseif (!empty($ta[user])) {
		$ta[user].='@';
	}
	if (!empty($ta[port]) and !empty($ta[host])) {
		$ta[host]=''.$ta[host].':';
	} elseif (!empty($ta[host])) {
		$ta[host]=$ta[host];
	}
	if (!empty($ta[path])) {
		$tu='';
		$tok=strtok($ta[path], "\\/");
		while (strlen($tok)) {
			$tu.=rawurlencode($tok).'/';
			$tok=strtok("\\/");
		}
		$ta[path]='/'.trim($tu, '/');
	}
	if (!empty($ta[query])) { $ta[query]='?'.$ta[query]; }
	if (!empty($ta[fragment])) { $ta[fragment]='#'.$ta[fragment]; }
	
	return implode('', array($ta[scheme], $ta[user], $ta[pass], $ta[host], $ta[port], $ta[path], $ta[query], $ta[fragment]));
}

/**
 * A mime_content_type function if the default mime_content_type function does not exist
 *
 * @return The mime content type
 * @author php.net username: svogal
 **/
if (!function_exists('mime_content_type')) {
	function mime_content_type($filename) {
		$mime_types = array(
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.',$filename)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
		else {
			return 'application/octet-stream';
		}
	}
}

?>