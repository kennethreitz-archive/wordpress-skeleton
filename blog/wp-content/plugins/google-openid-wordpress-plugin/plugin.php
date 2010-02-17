<?php

/*
 Plugin Name: My Google OpenID
 Plugin URI: http://github.com/kennethreitz/google-openid-wordpress-plugin
 Description: Adds your Google OpenID to a WordPress Wordpress site, allowing you to use that site as an OpenID provider. And it's all thanks to <a href="http://kennethreitz.com">Kenneth Reitz</a>!
 Author: Kenneth Reitz
 Author URI: http://kennethreitz.com
 Version: 0.3
 */

add_action('admin_menu', 'gopenid_admin_menu');

function gopenid_snippet() {  ?>
	<link rel="openid2.provider" href="https://www.google.com/accounts/o8/ud" />
	<link rel="openid2.local_id" href="https://www.google.com/profiles/<?php echo get_option('googleid') ?>" />
    <meta http-equiv="X-XRDS-Location" content="https://www.google.com/profiles/<?php echo get_option('googleid') ?>" /> <?php 
}

function gopenid_menu() {
	add_options_page('Google OpenID Settings', 'Google OpenID', 'edit_plugins', 'google-openid-settings', 'gopenid_options');
}

function gopenid_options() { 
	if( $_POST[ 'updated' ] == 'True' ) {
		update_option( 'googleid', $_POST['googleid'] );
		
		echo '<div class="updated"><p><strong>';
		 _e('Options saved.', 'google_id' );
		echo '</strong></p></div>';
    }

    echo '<div class="wrap">';
    echo "<h2>" . __( 'My Google OpenID Settings', 'google_id' ) . "</h2>"; 
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
	<p style="max-width: 660px;">To take advantage of Google Profile's fantastic OpenID on your blog, <br/> enter your <a href="http://www.google.com/profiles/me" target="_none">Google Profile ID</a> (last part of the URL) below, and everything will feel just right. </p>
	
	
	<p class="setting"><strong><?php _e("Google Profile ID:", 'google_id' ); ?></strong> 
		<input type="text" name="<?php echo 'googleid'; ?>" value="<?php echo get_option('googleid'); ?>" size="15">
	</p>

	<p style="color:#A2A2A2;"> <strong>Note</strong>: If you don't know what your Google Profile ID is, then you don't need this anyway.</p>

	<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Update Options', 'google_id' ) ?>" />
	</p>

	</form>
	</div>
	
	
<?php }

add_option('googleid', 'defaultusername');
add_action('wp_head', 'gopenid_snippet');
add_action('admin_menu', 'gopenid_menu');
