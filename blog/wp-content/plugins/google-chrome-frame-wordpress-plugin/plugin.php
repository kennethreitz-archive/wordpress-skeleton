<?php 
/*
 Plugin Name: Trigger Google Chrome Frame
 Plugin URI: http://github.com/kennethreitz/google-chrome-frame-wordpress-plugin
 Description: Triggers your site to be rendered with <a href="http://code.google.com/chrome/chromeframe/" target="_none">Google Chrome Frame</a> if available. And it's all thanks to <a href="http://kennethreitz.com">Kenneth Reitz</a>!
 Author: Kenneth Reitz
 Author URI: http://kennethreitz.com
 Version: 1.0
 */

add_action('admin_menu', 'gopenid_admin_menu');

function chromeframe_snippet() {  ?>
	<meta http-equiv="X-UA-Compatible" content="chrome=1"/> 
<?php }

add_action('wp_head', 'chromeframe_snippet');
