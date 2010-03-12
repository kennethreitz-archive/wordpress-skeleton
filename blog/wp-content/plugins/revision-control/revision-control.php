<?php
/*
Plugin Name: Revision Control
Plugin URI: http://dd32.id.au/wordpress-plugins/revision-control/
Description: Allows finer control over the number of Revisions stored on a global & per-post/page basis.
Author: Dion Hulse
Version: 1.9.7
*/

/**
 * This function defines WP_POST_REVISIONS for the current post.
 * Note: The define is auto-defined to true shortly after this hook is run.
 */
add_action('plugins_loaded', 'rc_loaded');
function rc_loaded() {
	if ( defined('WP_POST_REVISIONS') && defined('WP_ADMIN') ) {
		define('RC_DEFINED_BAD', true); //Lets notify the user about this on the Revision control menu item.
		return;
	}

	if ( ! defined('WP_ADMIN') || ! WP_ADMIN )
		return;

	//Ok, Time to add Admin related hooks :)
	add_action('do_meta_boxes', 'rc_meta_box_manip', 15, 2);
	add_action('admin_menu', 'rc_admin_menu');

	//Now the Defines.
	rc_define();
}

/**
 * Add the Menu items. 
 */
function rc_admin_menu() {
	//Hack into the Menu ordering
	add_options_page( __('Revision Control', 'revision-control'), __('Revisions', 'revision-control'), 'manage_options', 'revision-control', 'rc_admin');

	//Load any translations.
	load_plugin_textdomain(	'revision-control', 
							PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/langs/', //2.5 Compatibility
							dirname(plugin_basename(__FILE__)) . '/langs/'); //2.6+, Works with custom wp-content dirs.
}

/**
 * Defined WP_POST_REVISIONS for the current post/page/etc
 * Looks for Post ID's in the post and post_ID fields.
 */
function rc_define() {

	$defaults = get_option('revision-control', true);
	if ( ! is_array($defaults) ) { //Upgrade from 1.0 to 1.1
		$defaults = array('post' => $defaults, 'page' => $defaults);
		update_option('revision-control', $defaults);
	}

	$current_post = rc_get_page_id();

	//Post or Page:
	if ( ! $type = rc_get_page_type($current_post) )
		return;
	
	$revision_status = isset($defaults[ $type ]) ? $defaults[ $type ] : true;
	
	define('RC_REVISION_DEFAULT', $revision_status);

	if ( $current_post ) {
		//Handle per-post/page settings.
		$post_revision_status = get_post_meta($current_post, '_revision-control', true);
		if ('' !== trim($post_revision_status) ) {
			$revision_status = $post_revision_status;
	
			//Eugh.. maybe_serialize() bug #7383 means integers/booleans are stored as string!
			if ( is_string($revision_status) ) {
				$revision_status = (int)$revision_status;
				if ( 1 == $revision_status )
					$revision_status = true;
			}
		}
	}

	@define('WP_POST_REVISIONS', $revision_status);
}

/**
 * Determines if this is a Page or a Post, Or other.
 */
function rc_get_page_type( $id = 0 ) {
	global $pagenow;

	if ( isset($_POST['post_type']) )
		return $_POST['post_type'];
	else if ( 'page.php' == $pagenow || 'page-new.php' == $pagenow)
		return 'page';
	else if ( 'post.php' == $pagenow || 'post-new.php' == $pagenow)
		return 'post';
	else if ( $id && $post = get_post($id) )
		return $post->post_type;

	return false;
}

/**
 * Determines the post/page's ID based on the 'post' and 'post_ID' POST/GET fields.
 */
function rc_get_page_id() {
	foreach ( array( 'post_ID', 'post' ) as $field )
		if ( isset( $_REQUEST[ $field ] ) )
			return absint($_REQUEST[ $field ]);

	if ( isset($_REQUEST['revision']) )
		if ( $post = get_post( $id = absint($_REQUEST['revision']) ) )
			return absint($post->post_parent);

	return false;
}

/**
 * Custom Revisions box
 * Should use the API, But remove_meta_box followed by add_meta_box doesnt appear to add the new box
 */
function rc_meta_box_manip($page, $context) {
	global $wp_meta_boxes;
	$type = version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? 'normal' : 'advanced';

	if ( 'dashboard' == $page )
		return;

	if ( $type != $context )
		return;

	if ( isset($wp_meta_boxes[ $page ][ $type ][ 'core' ][ 'revisionsdiv' ]) )
		$wp_meta_boxes[ $page ][ $type ][ 'core' ][ 'revisionsdiv' ]['callback'] = 'rc_revisions_meta_box';
	else
		add_meta_box('revisionsdiv', __('Post Revisions'), 'rc_revisions_meta_box', $page, $type, 'core');
}

/**
 * The new Revision Meta box
 */
function rc_revisions_meta_box( $post ) {
	rc_list_post_revisions();
	?>
	<strong><?php _e('Revisions', 'revision-control') ?>:</strong>
	<input name="revision-control" id="revision-control-true" type="radio" value="true" <?php
		if ( WP_POST_REVISIONS === true ) echo ' checked="checked"' ?> /><label for="revision-control-true"><?php _e('Enabled', 'revision-control');
		if ( RC_REVISION_DEFAULT === true ) echo '<strong>' . __(' (default)', 'revision-control') . '</strong>' ?></label>&nbsp;&nbsp;
	<input name="revision-control" id="revision-control-false" type="radio" value="false" <?php
		if ( WP_POST_REVISIONS === 0 ) echo ' checked="checked"' ?>/><label for="revision-control-false"><?php _e('Disabled', 'revision-control');
		if ( RC_REVISION_DEFAULT === 0 ) echo '<strong>' . __(' (default)', 'revision-control') . '</strong>' ?></label> &nbsp;&nbsp;
	<input name="revision-control" id="revision-control-number" type="radio" value="number" <?php
		if ( WP_POST_REVISIONS > 1 ) echo ' checked="checked"' ?>/>
	<label for="revision-control-number" onclick="return false;">
	<select name="revision-control-number" onclick="jQuery('#revision-control-number').attr('checked', 'checked');">
		<?php for ( $i = 2; $i < 15; $i++ ) : ?>
		<option value="<?php echo $i ?>"<?php if ( WP_POST_REVISIONS === $i ) echo ' selected="selected"'
			?>><?php printf( __('Limit to %d Revisions', 'revision-control'), $i);
				if ( RC_REVISION_DEFAULT === $i ) _e(' (default)', 'revision-control'); ?></option>
		<?php endfor; ?>
	</select>
	</label>
	<?php
}

/**
 * Sets the per-post revision status. Also deletes any now-stale revisions.
 */
add_action('save_post', 'rc_perpost_value');
function rc_perpost_value($post_ID) {
	if ( ! isset($_POST['revision-control']) )
		return;
	if ( 'number' == $_POST['revision-control'] && ! isset($_POST['revision-control-number']) )
		return;

	switch ( $_POST['revision-control'] ) {
		case 'true':
			if ( RC_REVISION_DEFAULT === true ) {
				if ('' !== get_post_meta($post_ID, '_revision-control') )
					delete_post_meta($post_ID, '_revision-control');
				return;
			}

			update_post_meta($post_ID, '_revision-control', true);
			$number_to_delete = false;
			break;
		case 'false':
			if ( RC_REVISION_DEFAULT === 0 ) {
				if ('' !== get_post_meta($post_ID, '_revision-control') )
					delete_post_meta($post_ID, '_revision-control');
				return;
			}

			update_post_meta($post_ID, '_revision-control', 0);
			$number_to_delete = 0;
			break;
		case 'number':
			$number_to_delete = (int)$_POST['revision-control-number'];
			if ( RC_REVISION_DEFAULT === $number_to_delete ) {
				if ('' !== get_post_meta($post_ID, '_revision-control') )
					delete_post_meta($post_ID, '_revision-control');
				return;
			}

			update_post_meta($post_ID, '_revision-control', $number_to_delete);
			break;
		default:
			//Abort! Abort!
			return;
	}

	if ( is_integer($number_to_delete) ) {
		// all revisions and (possibly) one autosave
		$revisions = wp_get_post_revisions( $post_ID, array( 'order' => 'ASC' ) );

		//Number to delete, based on option.
		$delete = count($revisions) - $number_to_delete;
	
		if ( $delete < 1 )
			return;

		$revisions = array_slice( $revisions, 0, $delete );

		foreach ( (array)$revisions as $revision )
			if ( false === strpos( $revision->post_name, 'autosave' ) )
				wp_delete_post_revision( $revision->ID );
	}
}

/**
 * Copy of wp_list_post_revisions() w/ non-list support stripped out.
 * 
 */
function rc_list_post_revisions( $post_id = 0 ) {
	if ( !$post = get_post( $post_id ) )
		return;

	if ( !$revisions = wp_get_post_revisions( $post->ID ) )
		return;

	$titlef = _c( '%1$s by %2$s|post revision 1:datetime, 2:name' );

	echo "<ul class='post-revisions'>\n";
	foreach ( $revisions as $revision ) {
		if ( !current_user_can( 'read_post', $revision->ID ) )
			continue;

		$date = wp_post_revision_title( $revision );
		$name = get_author_name( $revision->post_author );

		$title = sprintf( $titlef, $date, $name );

		if ( current_user_can( 'edit_post', $revision->ID ) && ! wp_is_post_autosave( $revision ) ) {
			$url = wp_nonce_url('admin-post.php?action=delete-revision&revision=' . $revision->ID, 'delete_revision-' . $revision->ID);
			$title .= sprintf(' <a href="' . $url . '" onclick="return confirm(\'%s\')">%s</a>', js_escape(__('Are you sure you wish to delete this Revision?', 'revision-control')), __('(delete)', 'revision-control')); 
		}
		echo "\t<li>$title</li>\n";
	}
	echo "</ul>";

}

/**
 * Deletes a Revision
 */
add_action('admin_post_delete-revision', 'rc_delete_revision');
function rc_delete_revision() {
	$revision = absint($_REQUEST['revision']);
	if ( $revision ) {
		check_admin_referer('delete_revision-' . $revision);
		if ( current_user_can('delete_post', $revision) )
			wp_delete_post_revision( $revision );
	}
	wp_safe_redirect(wp_get_referer());
}

/**
 * Add the Plugin action link.
 */
add_filter('plugin_action_links', 'rc_plugins_filter', 10, 2);
function rc_plugins_filter($links, $plugin) {
	static $this_plugin;
	if( ! $this_plugin )
		$this_plugin = plugin_basename(__FILE__);

	if( $plugin == $this_plugin ) {
		$links = array_merge( array('<a href="options-general.php?page=revision-control">' . __('Revisions', 'revision-control') . '</a>'), $links);
		remove_filter('plugin_action_links', 'rc_plugins_filter'); //Nice citizens, We dont *really* need to check anymore.
	}

	return $links;
}

/**
 * The admin page, Handles saving the setting too.
 * checked() / selected() cannot save us, need a === instead of ==
 */
function rc_admin() {
	$defaults = get_option('revision-control');
	
	if ( 'POST' == strtoupper($_SERVER['REQUEST_METHOD']) ) {
		check_admin_referer('update-options');
		
		foreach ( array('post', 'page') as $field ) {
			if ( ! isset($_POST['revision-control-' . $field]) )
				continue;

			switch ( $_POST['revision-control-' . $field] ) {
				case 'true':
					$defaults[ $field ] = true;
					break;
				case 'false':
					$defaults[ $field ] = 0;
					break;
				case 'number':
					$defaults[ $field ] = (int)$_POST['revision-control-' . $field . '-number'];
					break;
			}
		}
		if ( $defaults !== get_option('revision-control') ) {
			update_option('revision-control', $defaults);
			echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.', 'revision-control') . '</strong></p></div>';
		}
	}
	?>
	<?php if ( defined('RC_DEFINED_BAD') ) : ?>
		<div class="message error"><p><?php _e('<strong>Error:</strong> You have defined <code>WP_POST_REVISIONS</code> in your <code>wp-config.php</code> file, In order to use this plugin you will need to remove it.', 'revision-control') ?></p></div>
	<?php endif; ?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Revision Control', 'revision-control') ?></h2>
	<form method="post" action="options-general.php?page=revision-control">
	<?php wp_nonce_field('update-options') ?>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="revision-control-post"> <?php _e('Default Revision Status for Posts', 'revision-control') ?></label></th>
			<td>
				<input name="revision-control-post" id="revision-control-post-true" type="radio" value="true" <?php
					if ( $defaults['post'] === true || $defaults['post'] === false ) echo ' checked="checked"' ?> />
					<label for="revision-control-post-true"><?php _e('Enabled', 'revision-control') ?></label><br />
				<input name="revision-control-post" id="revision-control-post-false" type="radio" value="false" <?php
					if ( $defaults['post'] === 0 ) echo ' checked="checked"' ?>/>
					<label for="revision-control-post-false"><?php _e('Disabled', 'revision-control') ?></label><br />
				<input name="revision-control-post" id="revision-control-post-number" type="radio" value="number" <?php
					if ( is_numeric( $defaults['post'] ) && $defaults['post'] > 1 ) echo ' checked="checked"' ?>/>
					<label for="revision-control-post-number" onclick="return false;">
						<select name="revision-control-post-number" onclick="jQuery('#revision-control-post-number').attr('checked', 'checked');">
							<?php for ( $i = 2; $i < 15; $i++ ) : ?>
							<option value="<?php echo $i ?>"<?php if ( $defaults['post'] === $i ) echo ' selected="selected"'
								?>><?php printf( __('Limit to %d Revisions', 'revision-control'), $i) ?></option>
							<?php endfor; ?>
						</select>
					</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="revision-control-page"> <?php _e('Default Revision Status for Pages', 'revision-control') ?></label></th>
			<td>
				<input name="revision-control-page" id="revision-control-page-true" type="radio" value="true" <?php
					if ( $defaults['page'] === true || $defaults['page'] === false ) echo ' checked="checked"' ?> />
					<label for="revision-control-page-true"><?php _e('Enabled', 'revision-control') ?></label><br />
				<input name="revision-control-page" id="revision-control-page-false" type="radio" value="false" <?php
					if ( $defaults['page'] === 0 ) echo ' checked="checked"' ?>/>
					<label for="revision-control-page-false"><?php _e('Disabled', 'revision-control') ?></label><br />
				<input name="revision-control-page" id="revision-control-page-number" type="radio" value="number" <?php
					if ( is_numeric( $defaults['page'] ) && $defaults['post'] > 1 ) echo ' checked="checked"' ?>/>
					<label for="revision-control-page-number" onclick="return false;">
						<select name="revision-control-page-number" onclick="jQuery('#revision-control-page-number').attr('checked', 'checked');">
							<?php for ( $i = 2; $i < 15; $i++ ) : ?>
							<option value="<?php echo $i ?>"<?php if ( $defaults['page'] === $i ) echo ' selected="selected"' ?>><?php printf( __('Limit to %d Revisions', 'revision-control'), $i) ?></option>
							<?php endfor; ?>
						</select>
					</label>
			</td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Save Changes', 'revision-control') ?>" />
	</p>
	</form>
	</div>
	<?php
}

class revision_control {
	//Stub until 2.0 is finalised.
	var $dd32_requires = 3;
	var $basename = '';
	var $folder = '';
	var $version = '1.9.7';
	
	function revision_control() {
		//Set the directory of the plugin:
		$this->basename = plugin_basename(__FILE__);
		$this->folder = dirname($this->basename);

		//Set the version of the DD32 library this plugin requires.
		$GLOBALS['dd32_version'] = isset($GLOBALS['dd32_version']) ? max($GLOBALS['dd32_version'], $this->dd32_requires) : $this->dd32_requires;
		add_action('init', array(&$this, 'load_dd32'), 20);

		//Register general hooks.
		add_action('admin_init', array(&$this, 'admin_init'));
	}
	
	function load_dd32() {
		//Load common library
		include 'inc/class.dd32.php';
	}
	
	function admin_init() {
		DD32::add_changelog($this->basename, 'http://svn.wp-plugins.org/revision-control/trunk/readme.txt');
	}
}
add_action('init', create_function('', '$GLOBALS["revision-control"] = new revision_control();'), 5);

?>