<?php

// This is a modified copy of blogs.php, to provide compatibility with Role Scoper file filtering.
// Role Scoper file filtering can still be used without this modification, but by default bypasses this script.
// As a result, the header manipulations done here for caching optimization are not applied. 
//
// To reinstate this functionality:
//		1) Copy this file into your wp-content folder
//
//		2) Add the following definition to wp-config.php:
//			define( 'SCOPER_MU_FILE_PROCESSING' );
//
//		3) De/Re-activate Role Scoper
//
// This modified copy of blogs.php is derived from WordPress MU 2.8.4.  
// It will probably also work with future version of MU, but in that case any new mime types or other header / cache handling introduced by the new version would be reverted.
//
// The changes are marked by "Begin Modification for Role Scoper file filtering"
//

define( 'SHORTINIT', true ); // this prevents most of WP from being loaded
require_once( dirname( dirname( __FILE__) ) . '/wp-load.php' ); // absolute includes are faster

if ( $current_blog->archived == '1' || $current_blog->spam == '1' || $current_blog->deleted == '1' ) {
	status_header( 404 );
	die('404 &#8212; File not found.');
}

if ( !function_exists('wp_check_filetype') ) :
function wp_check_filetype($filename, $mimes = null) {
	// Accepted MIME types are set here as PCRE unless provided.
	$mimes = is_array($mimes) ? $mimes : array (
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'bmp' => 'image/bmp',
		'tif|tiff' => 'image/tiff',
		'ico' => 'image/x-icon',
		'asf|asx|wax|wmv|wmx' => 'video/asf',
		'avi' => 'video/avi',
		'mov|qt' => 'video/quicktime',
		'mpeg|mpg|mpe' => 'video/mpeg',
		'txt|c|cc|h' => 'text/plain',
		'rtx' => 'text/richtext',
		'css' => 'text/css',
		'htm|html' => 'text/html',
		'mp3|mp4' => 'audio/mpeg',
		'ra|ram' => 'audio/x-realaudio',
		'wav' => 'audio/wav',
		'ogg' => 'audio/ogg',
		'mid|midi' => 'audio/midi',
		'wma' => 'audio/wma',
		'rtf' => 'application/rtf',
		'js' => 'application/javascript',
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
		'wri' => 'application/vnd.ms-write',
		'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
		'mdb' => 'application/vnd.ms-access',
		'mpp' => 'application/vnd.ms-project',
		'swf' => 'application/x-shockwave-flash',
		'class' => 'application/java',
		'tar' => 'application/x-tar',
		'zip' => 'application/zip',
		'gz|gzip' => 'application/x-gzip',
		'exe' => 'application/x-msdownload'
	);

	$type = false;
	$ext = false;

	foreach ( (array)$mimes as $ext_preg => $mime_match ) {
		$ext_preg = '!\.(' . $ext_preg . ')$!i';
		if ( preg_match($ext_preg, $filename, $ext_matches) ) {
			$type = $mime_match;
			$ext = $ext_matches[1];
			break;
		}
	}

	return compact('ext', 'type');
}
endif;

$file = BLOGUPLOADDIR . str_replace( '..', '', $_GET[ 'file' ] );

// --- Begin Modification for Role Scoper file filtering ---
$uploads_blog_dir = str_replace('/', '\/', UPLOADBLOGSDIR );
$uploads_blog_dir = str_replace('.', '\.', $uploads_blog_dir );
$match = array();

if ( preg_match( "/" . $uploads_blog_dir . "(.*)\/files\/(.*)\?(.*)/", $_SERVER['REQUEST_URI'], $match ) )
	$file = preg_replace( "/" . $uploads_blog_dir . "(.*)\/files/", UPLOADBLOGSDIR . $match[1] . '/files', $file );

if ( $pos = strpos( $_SERVER[ 'REQUEST_URI' ], '?' ) )
	$_SERVER[ 'REQUEST_URI' ] = substr( $_SERVER[ 'REQUEST_URI' ], 0, $pos );  // if this is a problem, will have to buffer to a local var and change calls below accordingly
// --- End Role Scoper modification ---


if ( !is_file( $file ) ) {
	status_header( 404 );
	die('404 &#8212; File not found.');
}


$mime = wp_check_filetype( $_SERVER[ 'REQUEST_URI' ] );

if( $mime[ 'type' ] === false && function_exists( 'mime_content_type' ) )
		$mime[ 'type' ] = mime_content_type( $file );

if( $mime[ 'type' ] != false ) {
	$mimetype = $mime[ 'type' ];
} else {
	$ext = substr( $_SERVER[ 'REQUEST_URI' ], strrpos( $_SERVER[ 'REQUEST_URI' ], '.' ) + 1 );
	$mimetype = "image/$ext";
}
@header( 'Content-type: ' . $mimetype ); // always send this
@header( 'Content-Length: ' . filesize( $file ) );

$last_modified = gmdate('D, d M Y H:i:s', filemtime( $file ));
$etag = '"' . md5($last_modified) . '"';
@header( "Last-Modified: $last_modified GMT" );
@header( 'ETag: ' . $etag );
@header( 'Expires: ' . gmdate('D, d M Y H:i:s', time() + 100000000) . ' GMT' );

// Support for Conditional GET
if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) 
	$client_etag = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
else
	$client_etag = false;

if( !isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
	$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE']);
// If string is empty, return 0. If not, attempt to parse into a timestamp
$client_modified_timestamp = $client_last_modified ? strtotime($client_last_modified) : 0;

// Make a timestamp for our most recent modification...	
$modified_timestamp = strtotime($last_modified);

if ( ($client_last_modified && $client_etag) ?
	 (($client_modified_timestamp >= $modified_timestamp) && ($client_etag == $etag)) :
	 (($client_modified_timestamp >= $modified_timestamp) || ($client_etag == $etag)) ) {
	status_header( 304 );
	exit;
}

// If we made it this far, just serve the file

readfile( $file );

?>
