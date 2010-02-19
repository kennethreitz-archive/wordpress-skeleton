<?php

function scoper_get_upload_info() {
	static $upload_info;
	
	if ( isset($upload_info) )
		return $upload_info;

	$upload_path = trim( get_option( 'upload_path' ) );
	if ( empty($upload_path) )
		$dir = WP_CONTENT_DIR . '/uploads';
	else
		$dir = $upload_path;

	// $dir is absolute, $path is (maybe) relative to ABSPATH
	$dir = path_join( ABSPATH, $dir );

	if ( ! $url = get_option( 'upload_url_path' ) ) {
		if ( empty($upload_path) or ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else {
			$siteurl = get_option( 'siteurl' );
			$url = trailingslashit( $siteurl ) . $upload_path;
		}
	}

	if ( defined('UPLOADS') ) {
		$siteurl = get_option( 'siteurl' );
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}
	
	// we only care about basedir and baseurl
	$uploads = apply_filters( 'upload_dir', array( 'path' => $dir, 'url' => $url, 'subdir' => '', 'basedir' => $dir, 'baseurl' => $url, 'error' => false ) );
	
	$upload_info = array_intersect_key( $uploads, array( 'basedir' => true, 'baseurl' => true ) );
	
	return $upload_info;
}
?>