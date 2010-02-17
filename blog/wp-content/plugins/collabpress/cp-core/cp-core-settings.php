<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Register callback
add_action('admin_menu', 'cp_settings_onadmin_menu');

function cp_settings_onadmin_menu() {

	// Add settings submenu
	add_submenu_page('cp-dashboard-page', 'CollabPress - Settings', 'Settings', 'manage_options', 'cp-settings-page', 'cp_show_settings_page');
	
	// Call register settings function.
	add_action( 'admin_init', 'cp_register_settings' );

}

// Register Settings
function cp_register_settings() {
	register_setting( 'cp_settings_group', 'cp_email_config' );
	register_setting( 'cp_settings_group', 'cp_user_level' );
}

function cp_show_settings_page() {
	?>
	<div class="wrap">
	<p><h2><?php _e('CollabPress Settings', 'collabpress') ?></h2></p>

	<form method="post" action="options.php">
	    <?php settings_fields( 'cp_settings_group' ); ?>
	    <table class="form-table">

	        <tr valign="top">
		        <th scope="row"><?php _e('Email Notifications:', 'collabpress') ?></th>
		        <td>
			        <select name="cp_email_config">
					<option <?php if (get_option('cp_email_config') == 1) echo "selected"; ?> value="1">Enabled</option>
					<option <?php if (get_option('cp_email_config') == 0) echo "selected"; ?> value="0">Disabled</option>
					</select>
		        </td>
	        </tr>
	
	        <tr valign="top">
		        <th scope="row"><?php _e('User Level:', 'collabpress') ?></th>
		        <td>
			        <select name="cp_user_level">
					<option <?php if (get_option('cp_user_level') == 10) echo "selected"; ?> value="10">Administrator</option>
					<option <?php if (get_option('cp_user_level') == 7) echo "selected"; ?> value="7">Author</option>
					<option <?php if (get_option('cp_user_level') == 2) echo "selected"; ?> value="2">Editor</option>
					<option <?php if (get_option('cp_user_level') == 1) echo "selected"; ?> value="1">Contributor</option>
					<option <?php if (get_option('cp_user_level') == 0) echo "selected"; ?> value="0">Subscriber</option>
					</select>
		        </td>
	        </tr>

	    </table>

	    <p class="submit">
	    <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'collabpress') ?>" />
	    </p>

	</form>
    <hr />
    <p><a target="_blank" href="http://webdevstudios.com/support/forum/collabpress/">CollabPress</a> v<?php echo CP_VERSION; ?> - <?php _e( 'Copyright', 'collabpress' ) ?> &copy; 2010 - <a href="http://webdevstudios.com/support/forum/collabpress/" target="_blank">Please Report Bugs</a> &middot; Follow us on Twitter: <a href="http://twitter.com/scottbasgaard" target="_blank">Scott</a> &middot; <a href="http://twitter.com/williamsba" target="_blank">Brad</a> &middot; <a href="http://twitter.com/webdevstudios" target="_blank">WDS</a></p>
	</div>
	<?php
}

?>