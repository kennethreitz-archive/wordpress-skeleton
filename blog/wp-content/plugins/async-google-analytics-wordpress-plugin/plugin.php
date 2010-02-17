<?php

/*
 Plugin Name: Asynchronous Google Analytics
 Plugin URI: http://github.com/kennethreitz/async-google-analytics-wordpress-plugin
 Description: Adds Asynchronous Google Analytics support to WordPress, thanks to <a href="http://kennethreitz.com">Kenneth Reitz</a>.
 Author: Kenneth Reitz
 Author URI: http://kennethreitz.com
 Version: 0.3
 */

add_action('admin_menu', 'asyncga_admin_menu');

function asyncga_snippet() {  ?>
	<script type="text/javascript" charset="utf-8">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo get_option('gaaccount') ?>']);
		_gaq.push(['_trackPageview']);

		(function() {
		var ga = document.createElement('script');
		ga.src = ('https:' == document.location.protocol ?
		    'https://ssl' : 'http://www') +
		    '.google-analytics.com/ga.js';
		ga.setAttribute('async', 'true');
		document.documentElement.firstChild.appendChild(ga);
		})();
	</script> <?php 
}

function asyncga_menu() {
	add_options_page('Google Analytics Settings', 'Google Analytics', 'edit_plugins', 'async-google-analytics', 'asyncga_options');
}

function asyncga_options() { 
	if( $_POST[ 'updated' ] == 'True' ) {
		update_option( 'gaaccount', $_POST['gaaccount'] );
		
		echo '<div class="updated"><p><strong>';
		 _e('Options saved.', 'google_analytics_id' );
		echo '</strong></p></div>';
    }

    echo '<div class="wrap">';
    echo "<h2>" . __( 'Asynchronous Google Analytics Settings', 'google_analytics_id' ) . "</h2>"; 
?>
	<h3>Given the opportunity, this will&hellip; surprise you.</h3>
	<style type="text/css" media="screen">
		p.setting {
			padding: 6px 5px 9px 9px;
			margin: 12px 0 8px 15px;
		}
	</style>
	<form name="form1" method="post" action="">
	<input type="hidden" name="<?php echo 'updated'; ?>" value="True">
	<p style="color:#A2A2A2;">It's time, my son.</p>
	<p style="max-width: 660px;">To take advantage of Google Analytics' ultra-fast Asynchronous JavaScript Loader, <br/> enter your Tracking ID below, and everything will suddenly fit together. </p>
	
	
	<p class="setting"><strong><?php _e("Google Analytics ID:", 'google_analytics_id' ); ?></strong> 
		<input type="text" name="<?php echo 'gaaccount'; ?>" value="<?php echo get_option('gaaccount'); ?>" size="15">
	</p>

	<p style="color:#A2A2A2;"> <strong>Note</strong>: If you don't know what your Tracking ID is, then you don't deserve to benefit from this.</p>

	<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Update Options', 'google_analytics_id' ) ?>" />
	</p>

	</form>
	</div>
	
	
<?php }

add_option('gaaccount', 'UA-XXXX-X');
add_action( 'wp_head', 'asyncga_snippet' );
add_action('admin_menu', 'asyncga_menu');
