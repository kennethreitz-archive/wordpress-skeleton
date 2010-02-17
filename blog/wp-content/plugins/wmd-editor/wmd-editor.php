<?php
/*
Plugin Name: WMD Editor
Plugin URI: http://c.hadcoleman.com/wordpress/wmd-editor
Description: Adds the <a href="http://wmd-editor.com/">WMD Editor</a> to the comment field, to make life easier for your commenters.
Version: 1.0
Author: Chad Coleman
Author URI: http://c.hadcoleman.com


This is a simple plugin that just adds the javascript call to the header of your template. You can gather more info on this project at http://code.google.com/p/wmd/
*/

// function for head output sytles
function wmd_header() {
	global $cb_path;
	$cb_path = get_bloginfo('wpurl')."/wp-content/plugins/wmd-editor";	//URL to the plugin directory
	$hHead = "\n"."<!-- Start WMD Editor -->"."\n";
	$hHead .= "	<script language=\"javascript\" type=\"text/javascript\" src=\"{$cb_path}/wmd.js\" ></script>\n";
	$hHead .= "<!-- End WMD Editor -->"."\n";
	print($hHead);
}

add_action('wp_head', 'wmd_header'); ?>