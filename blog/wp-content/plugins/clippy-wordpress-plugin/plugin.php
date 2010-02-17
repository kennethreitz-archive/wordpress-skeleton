<?php

/*
 Plugin Name: Clippy Plugin for Wordpress
 Plugin URI: http://github.com/kennethreitz/clippy-wordpress-plugin
 Description: Adds Clippy to WordPress, thanks to <a href="http://kennethreitz.com">Kenneth Reitz</a>.
 Author: Kenneth Reitz
 Author URI: http://kennethreitz.com
 Version: 0.3
 */



function clippy($text='copy-me') { ?>
<?php $dir = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)); ?>
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="110" height="14" id="clippy" >
<param name="movie" value="<?php echo $dir ?>lib/clippy.swf"/>
<param name="allowScriptAccess" value="always" />
<param name="quality" value="high" />
<param name="scale" value="noscale" />
<param NAME="FlashVars" value="text=<?php echo $text ?>">
<param name="bgcolor" value="#FFFFFF">
<embed src="<?php echo $dir ?>lib/clippy.swf"
width="110"
height="14"
name="clippy"
quality="high"
allowScriptAccess="always"
type="application/x-shockwave-flash"
pluginspage="http://www.macromedia.com/go/getflashplayer"
FlashVars="text=<?php echo $text ?>"
bgcolor="#FFFFFF"
/>
</object>
<?php } ?>