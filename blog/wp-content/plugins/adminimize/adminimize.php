<?php
/**
 * @package Adminimize
 * @author Frank B&uuml;ltge
 * @version 1.7.6
 */
 
/*
Plugin Name: Adminimize
Plugin URI: http://bueltge.de/wordpress-admin-theme-adminimize/674/
Description: Visually compresses the administratrive meta-boxes so that more admin page content can be initially seen. The plugin that lets you hide 'unnecessary' items from the WordPress administration menu, for alle roles of your install. You can also hide post meta controls on the edit-area to simplify the interface. It is possible to simplify the admin in different for all roles.
Author: Frank B&uuml;ltge
Author URI: http://bueltge.de/
Version: 1.7.6
License: GNU
Last Update: 20.01.2010 12:16:49
*/

/**
 * The stylesheet and the initial idea is from Eric A. Meyer, http://meyerweb.com/
 * and i have written a plugin with many options on the basis
 * of differently user-right and a user-friendly range in admin-area.
 */


global $wp_version;
if ( !function_exists ('add_action') || version_compare($wp_version, "2.5alpha", "<") ) {
	if ( function_exists ('add_action') )
		$exit_msg = 'The plugin <em><a href="http://bueltge.de/wordpress-admin-theme-adminimize/674/">Adminimize</a></em> requires WordPress 2.5 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update WordPress</a> or delete the plugin.';
	else
		$exit_msg = '';
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit($exit_msg);
}

if ( function_exists('add_action') ) {
	// Pre-2.6 compatibility
	if ( !defined( 'WP_CONTENT_URL' ) )
		define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
	if ( !defined( 'WP_CONTENT_DIR' ) )
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( !defined( 'WP_PLUGIN_URL' ) )
		define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( !defined( 'WP_PLUGIN_DIR' ) )
		define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

	// plugin definitions
	define( 'FB_ADMINIMIZE_BASENAME', plugin_basename( __FILE__ ) );
	define( 'FB_ADMINIMIZE_BASEFOLDER', plugin_basename( dirname( __FILE__ ) ) );
	define( 'FB_ADMINIMIZE_TEXTDOMAIN', 'adminimize' );
}

function _mw_adminimize_textdomain() {
	
	if ( function_exists('load_plugin_textdomain') )
		load_plugin_textdomain( FB_ADMINIMIZE_TEXTDOMAIN, false, dirname( FB_ADMINIMIZE_BASENAME ) . '/languages');
}


function recursive_in_array($needle, $haystack) {
	if ($haystack != '') {
		foreach ($haystack as $stalk) {
			if ( $needle == $stalk || ( is_array($stalk) && recursive_in_array($needle, $stalk) ) ) {
				return true;
			}
		}
		return false;
	}
}


/**
 * some basics for message
 */
class _mw_adminimize_message_class {
	function _mw_adminimize_message_class() {
		$this->localizionName = FB_ADMINIMIZE_TEXTDOMAIN;
		$this->errors = new WP_Error();
		$this->initialize_errors();
	}

	/**
	get_error - Returns an error message based on the passed code
	Parameters - $code (the error code as a string)
	Returns an error message
	*/
	function get_error($code = '') {
		$errorMessage = $this->errors->get_error_message($code);
		if ($errorMessage == null) {
			return __("Unknown error.", $this->localizionName);
		}
		return $errorMessage;
	}

	// Initializes all the error messages
	function initialize_errors() {
		$this->errors->add('_mw_adminimize_update', __('The updates was saved.', $this->localizionName));
		$this->errors->add('_mw_adminimize_access_denied', __('You have not enough rights for edit entries in the database.', $this->localizionName));
		$this->errors->add('_mw_adminimize_import', __('All entries in the database was imported.', $this->localizionName));
		$this->errors->add('_mw_adminimize_deinstall', __('All entries in the database was delleted.', $this->localizionName));
		$this->errors->add('_mw_adminimize_deinstall_yes', __('Set the checkbox on deinstall-button.', $this->localizionName));
		$this->errors->add('_mw_adminimize_get_option', __('Can\'t load menu and submenu.', $this->localizionName));
		$this->errors->add('_mw_adminimize_set_theme', __('Backend-Theme was activated!', $this->localizionName));
		$this->errors->add('_mw_adminimize_load_theme', __('Load user data to themes was successful.', $this->localizionName));
	}
}


/**
 * get_all_user_roles() - Returns an array with all user roles(names) in it.
 * Inclusive self defined roles (for example with the 'Role Manager' plugin).
 * code by Vincent Weber, www.webRtistik.nl
 * @uses $wp_roles
 */
function get_all_user_roles() {
	global $wp_roles;
	
	$user_roles = array();

	foreach ($wp_roles->roles as $role => $data) {
		array_push($user_roles, $role);
		//$data contains caps, maybe for later use..
	}
	
	return $user_roles;
}


/**
 * get_all_user_roles_names() - Returns an array with all user roles_names in it.
 * Inclusive self defined roles (for example with the 'Role Manager' plugin).
 * @uses $wp_roles
 */
function get_all_user_roles_names() {
	global $wp_roles;
	
	$user_roles_names = array();

	foreach ($wp_roles->role_names as $role_name => $data) {
		if ( function_exists('translate_user_role') )
			$data = translate_user_role( $data );
		else
			$data = translate_with_context( $data );
		array_push($user_roles_names, $data );
	}
	
	return $user_roles_names;
}


function _mw_adminimize_control_flashloader() {
	$_mw_adminimize_control_flashloader = _mw_adminimize_getOptionValue('_mw_adminimize_control_flashloader');
	
	if ($_mw_adminimize_control_flashloader == '1') {
		return false;
	} else {
		return true;
	}
}


/**
 * check user-option and add new style
 * @uses $pagenow
 */
function _mw_adminimize_init() {
	global $pagenow, $menu, $submenu, $adminimizeoptions, $wp_version;
	
	$user_roles = get_all_user_roles();

	$adminimizeoptions = get_option('mw_adminimize');

	foreach ($user_roles as $role) {
		$disabled_global_option_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_global_option_'. $role .'_items');
		$disabled_metaboxes_post_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_post_'. $role .'_items');
		$disabled_metaboxes_page_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_page_'. $role .'_items');
		$disabled_link_option_[$role]    = _mw_adminimize_getOptionValue('mw_adminimize_disabled_link_option_'. $role .'_items');
	}

	$disabled_metaboxes_post_all = array();
	$disabled_metaboxes_page_all = array();

	foreach ($user_roles as $role) {
		array_push($disabled_metaboxes_post_all, $disabled_metaboxes_post_[$role]);
		array_push($disabled_metaboxes_page_all, $disabled_metaboxes_page_[$role]);
	}

	$_mw_admin_color = get_user_option('admin_color');

	//global options
	$_mw_adminimize_footer = _mw_adminimize_getOptionValue('_mw_adminimize_footer');
	switch ($_mw_adminimize_footer) {
	case 1:
		wp_enqueue_script( '_mw_adminimize_remove_footer', WP_PLUGIN_URL . '/' . FB_ADMINIMIZE_BASEFOLDER . '/js/remove_footer.js', array('jquery') );
		break;
	}

	//post-page options
	$post_page_pages = array('post-new.php', 'post.php', 'page-new.php', 'page.php');
	if ( in_array( $pagenow, $post_page_pages ) ) {

		$_mw_adminimize_writescroll = _mw_adminimize_getOptionValue('_mw_adminimize_writescroll');
		switch ($_mw_adminimize_writescroll) {
		case 1:
			wp_enqueue_script( '_mw_adminimize_writescroll', WP_PLUGIN_URL . '/' . FB_ADMINIMIZE_BASEFOLDER . '/js/writescroll.js', array('jquery') );
			break;
		}
		$_mw_adminimize_tb_window = _mw_adminimize_getOptionValue('_mw_adminimize_tb_window');
		switch ($_mw_adminimize_tb_window) {
		case 1:
			wp_deregister_script('media-upload');
			wp_enqueue_script('media-upload', WP_PLUGIN_URL . '/' . FB_ADMINIMIZE_BASEFOLDER . '/js/tb_window.js', array('thickbox'));
			break;
		}
		$_mw_adminimize_timestamp = _mw_adminimize_getOptionValue('_mw_adminimize_timestamp');
		switch ($_mw_adminimize_timestamp) {
		case 1:
			wp_enqueue_script( '_mw_adminimize_timestamp', WP_PLUGIN_URL . '/' . FB_ADMINIMIZE_BASEFOLDER . '/js/timestamp.js', array('jquery') );
			break;
		}
		
		//category options
		$_mw_adminimize_cat_full = _mw_adminimize_getOptionValue('_mw_adminimize_cat_full');
		switch ($_mw_adminimize_cat_full) {
		case 1:
			wp_enqueue_style( 'adminimize-ful-category', WP_PLUGIN_URL . '/' . FB_ADMINIMIZE_BASEFOLDER . '/css/mw_cat_full.css' );
			break;
		}
		
		// set default editor tinymce
		if ( recursive_in_array('#editor-toolbar #edButtonHTML, #quicktags', $disabled_metaboxes_page_all)
			|| recursive_in_array('#editor-toolbar #edButtonHTML, #quicktags', $disabled_metaboxes_post_all) )
			add_filter( 'wp_default_editor', create_function('', 'return "tinymce";') );
		
		// remove media bottons
		if ( recursive_in_array('media_buttons', $disabled_metaboxes_page_all)
			|| recursive_in_array('media_buttons', $disabled_metaboxes_post_all) )
			remove_action('media_buttons', 'media_buttons');
			
		//add_filter('image_downsize', '_mw_adminimize_image_downsize', 1, 3);
	}
	
	$_mw_adminimize_control_flashloader = _mw_adminimize_getOptionValue('_mw_adminimize_control_flashloader');
	switch ($_mw_adminimize_control_flashloader) {
	case 1:
		add_filter( 'flash_uploader', '_mw_adminimize_control_flashloader', 1 );
		break;
	}

	if ( ($_mw_admin_color == 'mw_fresh') ||
				($_mw_admin_color == 'mw_classic') ||
				($_mw_admin_color == 'mw_colorblind') ||
				($_mw_admin_color == 'mw_grey') ||
				($_mw_admin_color == 'mw_fresh_ozh_am') ||
				($_mw_admin_color == 'mw_classic_ozh_am') ||
				($_mw_admin_color == 'mw_fresh_lm') ||
				($_mw_admin_color == 'mw_classic_lm') ||
				($_mw_admin_color == 'mw_wp23')
		 ) {
		
		// only posts
		if ( ('post-new.php' == $pagenow) || ('post.php' == $pagenow) ) {
			if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) )
				add_action('admin_head', '_mw_adminimize_remove_box', 99);

			// check for array empty
			if ( !isset($disabled_metaboxes_post_['editor']['0']) )
				$disabled_metaboxes_post_['editor']['0'] = '';
			if ( isset($disabled_metaboxes_post_['administrator']['0']) )
			 $disabled_metaboxes_post_['administrator']['0'] = '';
			if ( version_compare(substr($wp_version, 0, 3), '2.7', '<') ) {
				if ( !recursive_in_array('#categorydivsb', $disabled_metaboxes_post_all) )
					add_action('submitpost_box', '_mw_adminimize_sidecat_list_category_box');
				if ( !recursive_in_array('#tagsdivsb', $disabled_metaboxes_post_all) )
					add_action('submitpost_box', '_mw_adminimize_sidecat_list_tag_box');
			}
		}
		
		// only pages
		if ( ('page-new.php' == $pagenow) || ('page.php' == $pagenow) ) {

			// check for array empty
			if ( !isset($disabled_metaboxes_page_['editor']['0']) )
				$disabled_metaboxes_page_['editor']['0'] = '';
			if ( isset($disabled_metaboxes_page_['administrator']['0']) )
			 $disabled_metaboxes_page_['administrator']['0'] = '';
		}
	
	}

	// set menu option
	add_action('admin_head', '_mw_adminimize_set_menu_option', 1);
	
	// global_options
	add_action('admin_head', '_mw_adminimize_set_global_option', 1);
	
	// set metabox post option
	$post_pages = array('post-new.php', 'post.php');
	if ( in_array( $pagenow, $post_pages ) )
		add_action('admin_head', '_mw_adminimize_set_metabox_post_option', 1);
	
	// set metabox page option
	$page_pages = array('page-new.php', 'page.php');
	if ( in_array( $pagenow, $page_pages ) )
		add_action('admin_head', '_mw_adminimize_set_metabox_page_option', 1);
	
	// set link option
	$link_pages = array('link-manager.php', 'link-add.php', 'edit-link-categories.php');
	if ( in_array( $pagenow, $link_pages ) )
		add_action('admin_head', '_mw_adminimize_set_link_option', 1);

	add_action('in_admin_footer', '_mw_adminimize_admin_footer');

	$adminimizeoptions['mw_adminimize_default_menu'] = $menu;
	$adminimizeoptions['mw_adminimize_default_submenu'] = $submenu;
}

add_action('init', '_mw_adminimize_textdomain');
if ( is_admin() ) {
	add_action('admin_menu', '_mw_adminimize_add_settings_page');
	add_action('admin_menu', '_mw_adminimize_remove_dashboard');
	add_action('admin_init', '_mw_adminimize_init', 1);
	add_action('admin_init', '_mw_adminimize_admin_styles', 1);
}

if ( function_exists('register_activation_hook') )
	register_activation_hook(__FILE__, '_mw_adminimize_install');
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, '_mw_adminimize_deinstall');
//register_deactivation_hook(__FILE__, '_mw_adminimize_deinstall');


/**
 * Uses WordPress filter for image_downsize, kill wp-image-dimension
 * code by Andrew Rickmann
 * http://www.wp-fun.co.uk/
 * @param $value, $id, $size
 */
function _mw_adminimize_image_downsize($value = false, $id = 0, $size = "medium") {

	if ( !wp_attachment_is_image($id) )
		return false;

	$img_url = wp_get_attachment_url($id);
	// Mimic functionality in image_downsize function in wp-includes/media.php
	if ( $intermediate = image_get_intermediate_size($id, $size) ) {
		$img_url = str_replace(basename($img_url), $intermediate['file'], $img_url);
	}
	elseif ( $size == 'thumbnail' ) {
		// fall back to the old thumbnail
		if ( $thumb_file = wp_get_attachment_thumb_file() && $info = getimagesize($thumb_file) ) {
			$img_url = str_replace(basename($img_url), basename($thumb_file), $img_url);
		}
	}
	if ( $img_url)
		return array($img_url, 0, 0);
	
	return false;
}


/**
 * list category-box in sidebar
 * @uses $post_ID
 */
function _mw_adminimize_sidecat_list_category_box() {
	global $post_ID;
	?>

	<div class="inside" id="categorydivsb">
		<p><strong><?php _e("Categories"); ?></strong></p>
		<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
		<?php wp_category_checklist($post_ID); ?>
		</ul>
	<?php if ( !defined('WP_PLUGIN_DIR') ) { // for wp <2.6 ?>
		<div id="category-adder" class="wp-hidden-children">
			<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>
			<p id="category-add" class="wp-hidden-child">
				<input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php _e( 'New category name' ); ?>" tabindex="3" />
				<?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) ); ?>
				<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php _e( 'Add' ); ?>" tabindex="3" />
				<?php wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
				<span id="category-ajax-response"></span>
			</p>
		</div>
	<?php } else { ?>
		<div id="category-adder" class="wp-hidden-children">
			<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>
			<p id="category-add" class="wp-hidden-child">
				<label class="hidden" for="newcat"><?php _e( 'Add New Category' ); ?></label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php _e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>
				<br />
				<label class="hidden" for="newcat_parent"><?php _e('Parent category'); ?>:</label><?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) ); ?>
				<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php _e( 'Add' ); ?>" tabindex="3" />
				<?php wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
				<span id="category-ajax-response"></span>
			</p>
		</div>
	<?php } ?>
	</div>
<?php
}


/**
 * list tag-box in sidebar
 * @uses $post_ID
 */
function _mw_adminimize_sidecat_list_tag_box() {
	global $post_ID;

	if ( !class_exists('SimpleTagsAdmin') ) {
	?>
	<div class="inside" id="tagsdivsb">
		<p><strong><?php _e('Tags'); ?></strong></p>
		<p id="jaxtag"><label class="hidden" for="newtag"><?php _e('Tags'); ?></label><input type="text" name="tags_input" class="tags-input" id="tags-input" size="40" tabindex="3" value="<?php echo get_tags_to_edit($post_ID); ?>" /></p>
		<div id="tagchecklist"></div>
	</div>
	<?php
	}
}


/**
 * remove default categorydiv
 * @echo script
 */
function _mw_adminimize_remove_box() {

	if ( function_exists('remove_meta_box') ) {
		if ( !class_exists('SimpleTagsAdmin') )
			remove_meta_box('tagsdiv', 'post', 'normal');
		remove_meta_box('categorydiv', 'post', 'normal');
	} else {
		$_mw_adminimize_sidecat_admin_head  = "\n" . '<script type="text/javascript">' . "\n";
		$_mw_adminimize_sidecat_admin_head .= "\t" . 'jQuery(document).ready(function() { jQuery(\'#categorydiv\').remove(); });' . "\n";
		$_mw_adminimize_sidecat_admin_head .= "\t" . 'jQuery(document).ready(function() { jQuery(\'#tagsdiv\').remove(); });' . "\n";
		$_mw_adminimize_sidecat_admin_head .= '</script>' . "\n";

		echo $_mw_adminimize_sidecat_admin_head;
	}
}


/**
 * add new adminstyle to usersettings
 * @param $file
 */
function _mw_adminimize_admin_styles($file) {
	global $wp_version;

	$_mw_adminimize_path = WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/css/';

	if ( version_compare( $wp_version, '2.7alpha', '>=' ) ) {
		// MW Adminimize Classic
		$styleName = 'Adminimize:' . ' ' . __('Blue');
		wp_admin_css_color (
			'mw_classic', $styleName, $_mw_adminimize_path . 'mw_classic27.css',
			array('#073447', '#21759b', '#eaf3fa', '#bbd8e7')
		);

		// MW Adminimize Fresh
		$styleName = 'Adminimize:' . ' ' . __('Gray');
		wp_admin_css_color (
			'mw_fresh', $styleName, $_mw_adminimize_path . 'mw_fresh27.css',
			array('#464646', '#6d6d6d', '#f1f1f1', '#dfdfdf')
		);
		
		// MW Adminimize Classic Fixed
		$styleName = 'Adminimize:' . ' Fixed ' . __('Blue');
		wp_admin_css_color (
			'mw_classic_fixed', $styleName, $_mw_adminimize_path . 'mw_classic28_fixed.css',
			array('#073447', '#21759b', '#eaf3fa', '#bbd8e7')
		);

		// MW Adminimize Fresh Fixed
		$styleName = 'Adminimize:' . ' Fixed ' . __('Gray');
		wp_admin_css_color (
			'mw_fresh_fixed', $styleName, $_mw_adminimize_path . 'mw_fresh28_fixed.css',
			array('#464646', '#6d6d6d', '#f1f1f1', '#dfdfdf')
		);
		
		// MW Adminimize Classic Tweak
		$styleName = 'Adminimize:' . ' Tweak ' . __('Blue');
		wp_admin_css_color (
			'mw_classic_tweak', $styleName, $_mw_adminimize_path . 'mw_classic28_tweak.css',
			array('#073447', '#21759b', '#eaf3fa', '#bbd8e7')
		);

		// MW Adminimize Fresh Tweak
		$styleName = 'Adminimize:' . ' Tweak ' . __('Gray');
		wp_admin_css_color (
			'mw_fresh_tweak', $styleName, $_mw_adminimize_path . 'mw_fresh28_tweak.css',
			array('#464646', '#6d6d6d', '#f1f1f1', '#dfdfdf')
		);
		
	} else {
		// MW Adminimize Classic
		$styleName = 'MW Adminimize:' . ' ' . __('Classic');
		wp_admin_css_color (
			'mw_classic', $styleName, $_mw_adminimize_path . 'mw_classic.css',
			array('#07273E', '#14568A', '#D54E21', '#2683AE')
		);

		// MW Adminimize Fresh
		$styleName = 'MW Adminimize:' . ' ' . __('Fresh');
		wp_admin_css_color (
			'mw_fresh', $styleName, $_mw_adminimize_path . 'mw_fresh.css',
			array('#464646', '#CEE1EF', '#D54E21', '#2683AE')
		);

		// MW Adminimize WordPress 2.3
		$styleName = 'MW Adminimize:' . ' ' . __('WordPress 2.3');
		wp_admin_css_color (
			'mw_wp23', $styleName, $_mw_adminimize_path . 'mw_wp23.css',
			array('#000000', '#14568A', '#448ABD', '#83B4D8')
		);

		// MW Adminimize Colorblind
		$styleName = 'MW Adminimize:' . ' ' . __('Maybe i\'m colorblind');
		wp_admin_css_color (
			'mw_colorblind', $styleName, $_mw_adminimize_path . 'mw_colorblind.css',
			array('#FF9419', '#F0720C', '#710001', '#550007', '#CF4529')
		);

		// MW Adminimize Grey
		$styleName = 'MW Adminimize:' . ' ' . __('Grey');
		wp_admin_css_color (
			'mw_grey', $styleName, $_mw_adminimize_path . 'mw_grey.css',
			array('#000000', '#787878', '#F0F0F0', '#D8D8D8', '#909090')
		);
	}
	/**
	 * style and changes for plugin Admin Drop Down Menu
	 * by Ozh
	 * http://planetozh.com/blog/my-projects/wordpress-admin-menu-drop-down-css/
	 */
	if ( function_exists('wp_ozh_adminmenu') ) {

		// MW Adminimize Classic include ozh adminmenu
		$styleName = 'MW Adminimize inc. Admin Drop Down Menu' . ' ' . __('Classic');
		wp_admin_css_color (
			'mw_classic_ozh_am', $styleName, $_mw_adminimize_path . 'mw_classic_ozh_am.css',
			array('#07273E', '#14568A', '#D54E21', '#2683AE')
		);

		// MW Adminimize Fresh include ozh adminmenu
		$styleName = 'MW Adminimize inc. Admin Drop Down Menu' . ' ' . __('Fresh');
		wp_admin_css_color (
			'mw_fresh_ozh_am', $styleName, $_mw_adminimize_path . 'mw_fresh_ozh_am.css',
			array('#464646', '#CEE1EF', '#D54E21', '#2683AE')
		);

	}

	/**
	 * style and changes for plugin Lighter Menus
	 * by corpodibacco
	 * http://www.italyisfalling.com/lighter-menus
	 */
	if ( function_exists('lm_build') ) {

		// MW Adminimize Classic include Lighter Menus
		$styleName = 'MW Adminimize inc. Lighter Menus' . ' ' . __('Classic');
		wp_admin_css_color (
			'mw_classic_lm', $styleName, $_mw_adminimize_path . 'mw_classic_lm.css',
			array('#07273E', '#14568A', '#D54E21', '#2683AE')
		);

		// MW Adminimize Fresh include Lighter Menus
		$styleName = 'MW Adminimize inc. Lighter Menus' . ' ' . __('Fresh');
		wp_admin_css_color (
			'mw_fresh_lm', $styleName, $_mw_adminimize_path . 'mw_fresh_lm.css',
			array('#464646', '#CEE1EF', '#D54E21', '#2683AE')
		);

	}
}


/**
 * remove the dashbord
 * @author of basic Austin Matzko
 * http://www.ilfilosofo.com/blog/2006/05/24/plugin-remove-the-wordpress-dashboard/
 */
function _mw_adminimize_remove_dashboard() {
	global $menu, $submenu, $user_ID;
	
	$user_roles = get_all_user_roles();

	foreach ($user_roles as $role) {
		$disabled_menu_[$role]     = _mw_adminimize_getOptionValue('mw_adminimize_disabled_menu_'. $role .'_items');
		$disabled_submenu_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_submenu_'. $role .'_items');
	}

	$disabled_menu_all     = array();
	$disabled_submenu_all  = array();

	foreach ($user_roles as $role) {
		array_push($disabled_menu_all, $disabled_menu_[$role]);
		array_push($disabled_submenu_all, $disabled_submenu_[$role]);
	}

	// remove dashboard
	if ( $disabled_menu_all != '' || $disabled_submenu_all  != '' ) {

		$i = 0;
		foreach ($user_roles as $role) {

			if ( current_user_can($role) && $i == 0 ) {
				if (
						recursive_in_array('index.php', $disabled_menu_[$role]) ||
						recursive_in_array('index.php', $disabled_submenu_[$role])
					 )
					 $redirect = 'true';
			} elseif ( current_user_can($role) ) {
				if (
						recursive_in_array('index.php', $disabled_menu_[$role]) ||
						recursive_in_array('index.php', $disabled_submenu_[$role])
					 )
					$redirect = 'true';
			}
		$i++;
		}

		if ( isset($redirect) && $redirect == 'true' ) {
			$_mw_adminimize_db_redirect = _mw_adminimize_getOptionValue('_mw_adminimize_db_redirect');
			switch ($_mw_adminimize_db_redirect) {
			case 0:
				$_mw_adminimize_db_redirect = 'profile.php';
				break;
			case 1:
				$_mw_adminimize_db_redirect = 'edit.php';
				break;
			case 2:
				$_mw_adminimize_db_redirect = 'edit-pages.php';
				break;
			case 3:
				$_mw_adminimize_db_redirect = 'post-new.php';
				break;
			case 4:
				$_mw_adminimize_db_redirect = 'page-new.php';
				break;
			case 5:
				$_mw_adminimize_db_redirect = 'edit-comments.php';
				break;
			case 6:
				$_mw_adminimize_db_redirect = htmlspecialchars( stripslashes( _mw_adminimize_getOptionValue('_mw_adminimize_db_redirect_txt') ) );
				break;
			}

			$the_user = new WP_User($user_ID);
			reset($menu); $page = key($menu);

			while ( (__('Dashboard') != $menu[$page][0]) && next($menu) || (__('Dashboard') != $menu[$page][1]) && next($menu) )
				$page = key($menu);

			if (__('Dashboard') == $menu[$page][0] || __('Dashboard') == $menu[$page][1])
				unset($menu[$page]);
			reset($menu); $page = key($menu);

			while ( !$the_user->has_cap($menu[$page][1]) && next($menu) )
				$page = key($menu);

			if ( preg_match('#wp-admin/?(index.php)?$#', $_SERVER['REQUEST_URI']) ) {
				if ( function_exists('admin_url') ) {
					wp_redirect( admin_url($_mw_adminimize_db_redirect) );
				} else {
					wp_redirect( get_option('siteurl') . '/wp-admin/' . $_mw_adminimize_db_redirect );
				}
			}
		}
	}
}


/**
 * remove the flash_uploader
 */
function _mw_adminimize_disable_flash_uploader() {
	return false;
}


/**
 * set menu options from database
 */
function _mw_adminimize_set_menu_option() {
	global $pagenow, $menu, $submenu, $user_identity, $wp_version;
	
	$user_roles = get_all_user_roles();

	foreach ($user_roles as $role) {
		$disabled_menu_[$role]     = _mw_adminimize_getOptionValue('mw_adminimize_disabled_menu_'. $role .'_items');
		$disabled_submenu_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_submenu_'. $role .'_items');
	}

	$_mw_adminimize_admin_head       = "\n";
	$_mw_adminimize_user_info        = _mw_adminimize_getOptionValue('_mw_adminimize_user_info');
	$_mw_adminimize_ui_redirect      = _mw_adminimize_getOptionValue('_mw_adminimize_ui_redirect');

	switch ($_mw_adminimize_user_info) {
	case 1:
		$_mw_adminimize_admin_head .= '<script type="text/javascript">' . "\n";
		$_mw_adminimize_admin_head .= "\t" . 'jQuery(document).ready(function() { jQuery(\'#user_info\').remove(); });' . "\n";
		$_mw_adminimize_admin_head .= '</script>' . "\n";
		break;
	case 2:
		if ( version_compare(substr($wp_version, 0, 3), '2.7', '>=') ) {
			$_mw_adminimize_admin_head .= '<link rel="stylesheet" href="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/css/mw_small_user_info27.css" type="text/css" />' . "\n";
		} else {
			$_mw_adminimize_admin_head .= '<link rel="stylesheet" href="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/css/mw_small_user_info.css" type="text/css" />' . "\n";
		}
		$_mw_adminimize_admin_head .= '<script type="text/javascript">' . "\n";
		$_mw_adminimize_admin_head .= "\t" . 'jQuery(document).ready(function() { jQuery(\'#user_info\').remove();';
		if ($_mw_adminimize_ui_redirect == '1') {
			$_mw_adminimize_admin_head .= 'jQuery(\'div#wpcontent\').after(\'<div id="small_user_info"><p><a href="' . get_option('siteurl') . wp_nonce_url( ('/wp-login.php?action=logout&amp;redirect_to=') . get_option('siteurl') , 'log-out' ) . '" title="' . __('Log Out') . '">' . __('Log Out') . '</a></p></div>\') });' . "\n";
		} else {
			$_mw_adminimize_admin_head .= 'jQuery(\'div#wpcontent\').after(\'<div id="small_user_info"><p><a href="' . get_option('siteurl') . wp_nonce_url( ('/wp-login.php?action=logout') , 'log-out' ) . '" title="' . __('Log Out') . '">' . __('Log Out') . '</a></p></div>\') });' . "\n";
		}
		$_mw_adminimize_admin_head .= '</script>' . "\n";
		break;
	case 3:
		if ( version_compare(substr($wp_version, 0, 3), '2.7', '>=') ) {
			$_mw_adminimize_admin_head .= '<link rel="stylesheet" href="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/css/mw_small_user_info27.css" type="text/css" />' . "\n";
		} else {
			$_mw_adminimize_admin_head .= '<link rel="stylesheet" href="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/css/mw_small_user_info.css" type="text/css" />' . "\n";
		}
		$_mw_adminimize_admin_head .= '<script type="text/javascript">' . "\n";
		$_mw_adminimize_admin_head .= "\t" . 'jQuery(document).ready(function() { jQuery(\'#user_info\').remove();';
		if ($_mw_adminimize_ui_redirect == '1') {
			$_mw_adminimize_admin_head .= 'jQuery(\'div#wpcontent\').after(\'<div id="small_user_info"><p><a href="' . get_option('siteurl') . ('/wp-admin/profile.php') . '">' . $user_identity . '</a> | <a href="' . get_option('siteurl') . wp_nonce_url( ('/wp-login.php?action=logout&amp;redirect_to=') . get_option('siteurl'), 'log-out' ) . '" title="' . __('Log Out') . '">' . __('Log Out') . '</a></p></div>\') });' . "\n";
		} else {
			$_mw_adminimize_admin_head .= 'jQuery(\'div#wpcontent\').after(\'<div id="small_user_info"><p><a href="' . get_option('siteurl') . ('/wp-admin/profile.php') . '">' . $user_identity . '</a> | <a href="' . get_option('siteurl') . wp_nonce_url( ('/wp-login.php?action=logout'), 'log-out' ) . '" title="' . __('Log Out') . '">' . __('Log Out') . '</a></p></div>\') });' . "\n";
		}
		$_mw_adminimize_admin_head .= '</script>' . "\n";
		break;
	}

	// set menu
	if ($disabled_menu_['editor'] != '') {

		// set admin-menu
		foreach ($user_roles as $role) {

			if($role == $role[0]){
				if ( current_user_can($role) ) {
					$mw_adminimize_menu     = $disabled_menu_[$role];
					$mw_adminimize_submenu  = $disabled_submenu_[$role];
				}
			} elseif ( current_user_can($role) ) {
					$mw_adminimize_menu     = $disabled_menu_[$role];
					$mw_adminimize_submenu  = $disabled_submenu_[$role];
			}
		}
		
		foreach ($menu as $index => $item) {
			if ($item == 'index.php')
				continue;

			if ( isset($mw_adminimize_menu) && in_array($item[2], $mw_adminimize_menu) )
				unset($menu[$index]);

			if ( isset($submenu) && !empty($submenu[$item[2]]) ) {
				foreach ($submenu[$item[2]] as $subindex => $subitem) {
					if ( isset($mw_adminimize_submenu) && in_array($subitem[2], $mw_adminimize_submenu))
						unset($submenu[$item[2]][$subindex]);
				}
			}
		}

	}

	echo $_mw_adminimize_admin_head;
}


/**
 * set global options in backend in all areas
 */
function _mw_adminimize_set_global_option() {
	
	$user_roles = get_all_user_roles();

	$_mw_adminimize_admin_head = '';

	remove_action('admin_head', 'index_js');

	foreach ($user_roles as $role) {
		$disabled_global_option_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_global_option_'. $role .'_items');
	}

	foreach ($user_roles as $role) {
		if ( !isset($disabled_global_option_[$role]['0']) )
			$disabled_global_option_[$role]['0'] = '';
	}

	foreach ($user_roles as $role) {
		if ($role == $role[0]) {
			if ( current_user_can($role) ) {
				 $global_options = implode(', ', $disabled_global_option_[$role]);
			}
		} elseif ( current_user_can($role) ) {
			$global_options = implode(', ', $disabled_global_option_[$role]);
		}
	}
	$_mw_adminimize_admin_head .= '<!-- global options -->' . "\n";
	$_mw_adminimize_admin_head .= '<style type="text/css">' . $global_options . ' {display: none !important;}</style>' . "\n";
	
	if ($global_options)
		echo $_mw_adminimize_admin_head;
}


/**
 * set metabox options from database an area post
 */
function _mw_adminimize_set_metabox_post_option() {
	
	$user_roles = get_all_user_roles();

	$_mw_adminimize_admin_head = '';

	remove_action('admin_head', 'index_js');

	foreach ($user_roles as $role) {
		$disabled_metaboxes_post_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_post_'. $role .'_items');
		
		if ( !isset($disabled_metaboxes_post_[$role]['0']) )
			$disabled_metaboxes_post_[$role]['0'] = '';
		
		if ($role == $role[0]) {
			if ( current_user_can($role) ) {
				 $metaboxes = implode(',', $disabled_metaboxes_post_[$role]);
			}
		} elseif ( current_user_can($role) ) {
			$metaboxes = implode(',', $disabled_metaboxes_post_[$role]);
		}
	}

	$_mw_adminimize_admin_head .= '<style type="text/css">' . $metaboxes . ' {display: none !important;}</style>' . "\n";
	
	if ($metaboxes)
		echo $_mw_adminimize_admin_head;
}


/**
 * set metabox options from database an area page
 */
function _mw_adminimize_set_metabox_page_option() {
	
	$user_roles = get_all_user_roles();
	
	$_mw_adminimize_admin_head = '';
	
	remove_action('admin_head', 'index_js');
	
	foreach ($user_roles as $role) {
		$disabled_metaboxes_page_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_page_'. $role .'_items');
		
		if ( !isset($disabled_metaboxes_page_[$role]['0']) )
			$disabled_metaboxes_page_[$role]['0'] = '';

		if($role == $role[0]){
			if ( current_user_can($role) ) {
				 $metaboxes = implode(',', $disabled_metaboxes_page_[$role]);
			}
		} elseif ( current_user_can($role) ) {
			$metaboxes = implode(',', $disabled_metaboxes_page_[$role]);
		}
	}
	
	$_mw_adminimize_admin_head .= '<style type="text/css">' . $metaboxes . ' {display: none !important;}</style>' . "\n";
	
	if ($metaboxes)
		echo $_mw_adminimize_admin_head;
}


/**
 * set link options in area Links of Backend
 */
function _mw_adminimize_set_link_option() {
	
	$user_roles = get_all_user_roles();

	$_mw_adminimize_admin_head = '';
	
	remove_action('admin_head', 'index_js');
	
	foreach ($user_roles as $role) {
		$disabled_link_option_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_link_option_'. $role .'_items');
	}
	
	foreach ($user_roles as $role) {
		if ( !isset($disabled_link_option_[$role]['0']) )
			$disabled_link_option_[$role]['0'] = '';
	}

	foreach ($user_roles as $role) {
		if ($role == $role[0]) {
			if ( current_user_can($role) ) {
				 $link_options = implode(', ', $disabled_link_option_[$role]);
			}
		} elseif ( current_user_can($role) ) {
			$link_options = implode(', ', $disabled_link_option_[$role]);
		}
	}

	$_mw_adminimize_admin_head .= '<style type="text/css">' . $link_options . ' {display: none !important;}</style>' . "\n";
	
	if ($link_options)
		echo $_mw_adminimize_admin_head;
}


/**
 * small user-info
 * @uses $post_ID
 */
function _mw_adminimize_small_user_info() {
?>
	<div id="small_user_info">
		<p><a href="<?php echo wp_nonce_url( site_url('wp-login.php?action=logout'), 'log-out' ) ?>" title="<?php _e('Log Out') ?>"><?php _e('Log Out'); ?></a></p>
	</div>
<?php
}


/**
 * include options-page in wp-admin
 */
require_once('adminimize_page.php');


/**
 * credit in wp-footer
 */
function _mw_adminimize_admin_footer() {
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_data['Title'] = $plugin_data['Name'];
	if ( !empty($plugin_data['PluginURI']) && !empty($plugin_data['Name']) )
		$plugin_data['Title'] = '<a href="' . $plugin_data['PluginURI'] . '" title="'.__( 'Visit plugin homepage' ).'">' . $plugin_data['Name'] . '</a>';

	if ( basename($_SERVER['REQUEST_URI']) == 'adminimize.php') {
		printf('%1$s ' . __('plugin') . ' | ' . __('Version') . ' <a href="http://bueltge.de/wordpress-admin-theme-adminimize/674/#historie" title="' . __('History', FB_ADMINIMIZE_TEXTDOMAIN ) . '">%2$s</a> | ' . __('Author') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}
	if ( _mw_adminimize_getOptionValue('_mw_adminimize_advice') == 1 && basename($_SERVER['REQUEST_URI']) != 'adminimize.php' ) {
		printf('%1$s ' . __('plugin activate', FB_ADMINIMIZE_TEXTDOMAIN ) . ' | ' . stripslashes( _mw_adminimize_getOptionValue('_mw_adminimize_advice_txt') ) . '<br />', $plugin_data['Title']);
	}
}


/**
 * @version WP 2.8
 * Add action link(s) to plugins page
 *
 * @package Secure WordPress
 *
 * @param $links, $file
 * @return $links
 */
function _mw_adminimize_filter_plugin_meta($links, $file) {
	
	/* create link */
	if ( $file == FB_ADMINIMIZE_BASENAME ) {
		array_unshift(
			$links,
			sprintf( '<a href="options-general.php?page=%s">%s</a>', FB_ADMINIMIZE_BASENAME, __('Settings') )
		);
	}
	
	return $links;
}


/**
 * Images/ Icons in base64-encoding
 * @use function _mw_adminimize_get_resource_url() for display
 */
if( isset($_GET['resource']) && !empty($_GET['resource'])) {
	# base64 encoding performed by base64img.php from http://php.holtsmark.no
	$resources = array(
		'adminimize.gif' =>
		'R0lGODlhCwALAKIEAPb29tTU1Kurq5SUlP///wAAAAAAAAAAAC'.
		'H5BAEAAAQALAAAAAALAAsAAAMlSErTuw1Ix4a8s4mYgxZbE4wf'.
		'OIzkAJqop64nWm7tULHu0+xLAgA7'.
		'');

	if(array_key_exists($_GET['resource'], $resources)) {

		$content = base64_decode($resources[ $_GET['resource'] ]);

		$lastMod = filemtime(__FILE__);
		$client = ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false );
		// Checking if the client is validating his cache and if it is current.
		if (isset($client) && (strtotime($client) == $lastMod)) {
			// Client's cache IS current, so we just respond '304 Not Modified'.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 304);
			exit;
		} else {
			// Image not cached or cache outdated, we respond '200 OK' and output the image.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 200);
			header('Content-Length: '.strlen($content));
			header('Content-Type: image/' . substr(strrchr($_GET['resource'], '.'), 1) );
			echo $content;
			exit;
		}
	}
}


/**
 * Display Images/ Icons in base64-encoding
 * @return $resourceID
 */
function _mw_adminimize_get_resource_url($resourceID) {

	return trailingslashit( get_bloginfo('url') ) . '?resource=' . $resourceID;
}


/**
 * content of help
 *
 * @package Secure WordPress
 */
function _mw_adminimize_contextual_help() {
	
	$content = __('<a href="http://wordpress.org/extend/plugins/adminimize/">Documentation</a>', FB_ADMINIMIZE_TEXTDOMAIN );
	return $content;
}


/**
 * settings in plugin-admin-page
 */
function _mw_adminimize_add_settings_page() {
	global $wp_version;

	if( current_user_can('switch_themes') && function_exists('add_submenu_page') ) {

		$menutitle = '';
		if ( version_compare( $wp_version, '2.7alpha', '>' ) ) {
			$menutitle = '<img src="' . _mw_adminimize_get_resource_url('adminimize.gif') . '" alt="" />';
		}
		$menutitle .= ' ' . __('Adminimize', FB_ADMINIMIZE_TEXTDOMAIN );

		$pagehook = add_submenu_page('options-general.php', __('Adminimize Options', FB_ADMINIMIZE_TEXTDOMAIN ), $menutitle, 'unfiltered_html', __FILE__, '_mw_adminimize_options');
		if ( version_compare( $wp_version, '2.7alpha', '>' ) )
			add_contextual_help( $pagehook, __( '<a href="http://wordpress.org/extend/plugins/adminimize/">Documentation</a>', FB_ADMINIMIZE_TEXTDOMAIN ) );
		else
			add_filter( 'contextual_help', '_mw_adminimize_contextual_help' );
			
		add_filter( 'plugin_action_links', '_mw_adminimize_filter_plugin_meta', 10, 2 );
	}
}


/**
 * Set theme for users
 */
function _mw_adminimize_set_theme() {

	if ( !current_user_can('edit_users') )
		wp_die(__('Cheatin&#8217; uh?'));

	$user_ids    = $_POST[mw_adminimize_theme_items];
	$admin_color = htmlspecialchars( stripslashes( $_POST[_mw_adminimize_set_theme] ) );

	if ( !$user_ids )
		return false;

	foreach( $user_ids as $user_id) {
		update_usermeta($user_id, 'admin_color', $admin_color);
	}
}


/**
 * read otpions
 */
function _mw_adminimize_getOptionValue($key) {

	$adminimizeoptions = get_option('mw_adminimize');
	if ( isset($adminimizeoptions[$key]) )
		return ($adminimizeoptions[$key]);
}


/**
 * Update options in database
 */
function _mw_adminimize_update() {
	global $menu, $submenu, $adminimizeoptions;
  $user_roles = get_all_user_roles();

	if (isset($_POST['_mw_adminimize_user_info'])) {
		$adminimizeoptions['_mw_adminimize_user_info'] = strip_tags(stripslashes($_POST['_mw_adminimize_user_info']));
	} else {
		$adminimizeoptions['_mw_adminimize_user_info'] = 0;
	}

	if (isset($_POST['_mw_adminimize_dashmenu'])) {
		$adminimizeoptions['_mw_adminimize_dashmenu'] = strip_tags(stripslashes($_POST['_mw_adminimize_dashmenu']));
	} else {
		$adminimizeoptions['_mw_adminimize_dashmenu'] = 0;
	}

	if (isset($_POST['_mw_adminimize_footer'])) {
		$adminimizeoptions['_mw_adminimize_footer'] = strip_tags(stripslashes($_POST['_mw_adminimize_footer']));
	} else {
		$adminimizeoptions['_mw_adminimize_footer'] = 0;
	}

	if (isset($_POST['_mw_adminimize_writescroll'])) {
		$adminimizeoptions['_mw_adminimize_writescroll'] = strip_tags(stripslashes($_POST['_mw_adminimize_writescroll']));
	} else {
		$adminimizeoptions['_mw_adminimize_writescroll'] = 0;
	}

	if (isset($_POST['_mw_adminimize_tb_window'])) {
		$adminimizeoptions['_mw_adminimize_tb_window'] = strip_tags(stripslashes($_POST['_mw_adminimize_tb_window']));
	} else {
		$adminimizeoptions['_mw_adminimize_tb_window'] = 0;
	}

	if (isset($_POST['_mw_adminimize_cat_full'])) {
		$adminimizeoptions['_mw_adminimize_cat_full'] = strip_tags(stripslashes($_POST['_mw_adminimize_cat_full']));
	} else {
		$adminimizeoptions['_mw_adminimize_cat_full'] = 0;
	}

	if (isset($_POST['_mw_adminimize_db_redirect'])) {
		$adminimizeoptions['_mw_adminimize_db_redirect'] = strip_tags(stripslashes($_POST['_mw_adminimize_db_redirect']));
	} else {
		$adminimizeoptions['_mw_adminimize_db_redirect'] = 0;
	}

	if (isset($_POST['_mw_adminimize_ui_redirect'])) {
		$adminimizeoptions['_mw_adminimize_ui_redirect'] = strip_tags(stripslashes($_POST['_mw_adminimize_ui_redirect']));
	} else {
		$adminimizeoptions['_mw_adminimize_ui_redirect'] = 0;
	}

	if (isset($_POST['_mw_adminimize_advice'])) {
		$adminimizeoptions['_mw_adminimize_advice'] = strip_tags(stripslashes($_POST['_mw_adminimize_advice']));
	} else {
		$adminimizeoptions['_mw_adminimize_advice'] = 0;
	}

	if (isset($_POST['_mw_adminimize_advice_txt'])) {
		$adminimizeoptions['_mw_adminimize_advice_txt'] = stripslashes($_POST['_mw_adminimize_advice_txt']);
	} else {
		$adminimizeoptions['_mw_adminimize_advice_txt'] = 0;
	}

	if (isset($_POST['_mw_adminimize_timestamp'])) {
		$adminimizeoptions['_mw_adminimize_timestamp'] = strip_tags(stripslashes($_POST['_mw_adminimize_timestamp']));
	} else {
		$adminimizeoptions['_mw_adminimize_timestamp'] = 0;
	}
	
	if (isset($_POST['_mw_adminimize_control_flashloader'])) {
		$adminimizeoptions['_mw_adminimize_control_flashloader'] = strip_tags(stripslashes($_POST['_mw_adminimize_control_flashloader']));
	} else {
		$adminimizeoptions['_mw_adminimize_control_flashloader'] = 0;
	}

	if (isset($_POST['_mw_adminimize_db_redirect_txt'])) {
		$adminimizeoptions['_mw_adminimize_db_redirect_txt'] = stripslashes($_POST['_mw_adminimize_db_redirect_txt']);
	} else {
		$adminimizeoptions['_mw_adminimize_db_redirect_txt'] = 0;
	}

	// menu update
	foreach ($user_roles as $role) {
		if (isset($_POST['mw_adminimize_disabled_menu_'. $role .'_items'])) {
			$adminimizeoptions['mw_adminimize_disabled_menu_'. $role .'_items']  = $_POST['mw_adminimize_disabled_menu_'. $role .'_items'];
		} else {
			$adminimizeoptions['mw_adminimize_disabled_menu_'. $role .'_items'] = array();
		}

		if (isset($_POST['mw_adminimize_disabled_submenu_'. $role .'_items'])) {
			$adminimizeoptions['mw_adminimize_disabled_submenu_'. $role .'_items']  = $_POST['mw_adminimize_disabled_submenu_'. $role .'_items'];
		} else {
			$adminimizeoptions['mw_adminimize_disabled_submenu_'. $role .'_items'] = array();
		}
	}

	// global_options, metaboxes update
	foreach ($user_roles as $role) {
		if (isset($_POST['mw_adminimize_disabled_global_option_'. $role . '_items'])) {
			$adminimizeoptions['mw_adminimize_disabled_global_option_'. $role . '_items']  = $_POST['mw_adminimize_disabled_global_option_'. $role . '_items'];
		} else {
			$adminimizeoptions['mw_adminimize_disabled_global_option_'. $role . '_items'] = array();
		}
		
		if (isset($_POST['mw_adminimize_disabled_metaboxes_post_'. $role . '_items'])) {
			$adminimizeoptions['mw_adminimize_disabled_metaboxes_post_'. $role . '_items']  = $_POST['mw_adminimize_disabled_metaboxes_post_'. $role . '_items'];
		} else {
			$adminimizeoptions['mw_adminimize_disabled_metaboxes_post_'. $role . '_items'] = array();
		}

		if (isset($_POST['mw_adminimize_disabled_metaboxes_page_'. $role . '_items'])) {
			$adminimizeoptions['mw_adminimize_disabled_metaboxes_page_'. $role . '_items']  = $_POST['mw_adminimize_disabled_metaboxes_page_'. $role . '_items'];
		} else {
			$adminimizeoptions['mw_adminimize_disabled_metaboxes_page_'. $role . '_items'] = array();
		}
		
		if (isset($_POST['mw_adminimize_disabled_link_option_'. $role . '_items'])) {
			$adminimizeoptions['mw_adminimize_disabled_link_option_'. $role . '_items']  = $_POST['mw_adminimize_disabled_link_option_'. $role . '_items'];
		} else {
			$adminimizeoptions['mw_adminimize_disabled_link_option_'. $role . '_items'] = array();
		}
	}
	
	// own options
	if (isset($_POST['_mw_adminimize_own_values'])) {
		$adminimizeoptions['_mw_adminimize_own_values'] = stripslashes($_POST['_mw_adminimize_own_values']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_values'] = 0;
	}
	
	if (isset($_POST['_mw_adminimize_own_options'])) {
		$adminimizeoptions['_mw_adminimize_own_options'] = stripslashes($_POST['_mw_adminimize_own_options']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_options'] = 0;
	}
	
	// own post options
	if (isset($_POST['_mw_adminimize_own_post_values'])) {
		$adminimizeoptions['_mw_adminimize_own_post_values'] = stripslashes($_POST['_mw_adminimize_own_post_values']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_post_values'] = 0;
	}
	
	if (isset($_POST['_mw_adminimize_own_post_options'])) {
		$adminimizeoptions['_mw_adminimize_own_post_options'] = stripslashes($_POST['_mw_adminimize_own_post_options']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_post_options'] = 0;
	}
	
	// own page options
	if (isset($_POST['_mw_adminimize_own_page_values'])) {
		$adminimizeoptions['_mw_adminimize_own_page_values'] = stripslashes($_POST['_mw_adminimize_own_page_values']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_page_values'] = 0;
	}
	
	if (isset($_POST['_mw_adminimize_own_page_options'])) {
		$adminimizeoptions['_mw_adminimize_own_page_options'] = stripslashes($_POST['_mw_adminimize_own_page_options']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_page_options'] = 0;
	}
	
	// own link options
	if (isset($_POST['_mw_adminimize_own_link_values'])) {
		$adminimizeoptions['_mw_adminimize_own_link_values'] = stripslashes($_POST['_mw_adminimize_own_link_values']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_link_values'] = 0;
	}
	
	if (isset($_POST['_mw_adminimize_own_link_options'])) {
		$adminimizeoptions['_mw_adminimize_own_link_options'] = stripslashes($_POST['_mw_adminimize_own_link_options']);
	} else {
		$adminimizeoptions['_mw_adminimize_own_link_options'] = 0;
	}
	
	// update
	update_option('mw_adminimize', $adminimizeoptions);
	$adminimizeoptions = get_option('mw_adminimize');

	$myErrors = new _mw_adminimize_message_class();
	$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_update') . '</p></div>';
	echo $myErrors;
}


/**
 * Delete options in database
 */
function _mw_adminimize_deinstall() {

	delete_option('mw_adminimize');
}


/**
 * Install options in database
 */
function _mw_adminimize_install() {
	global $menu, $submenu;
	
	$user_roles = get_all_user_roles();
	$adminimizeoptions = array();

	foreach ($user_roles as $role) {
		$adminimizeoptions['mw_adminimize_disabled_menu_'. $role .'_items'] = array();
		$adminimizeoptions['mw_adminimize_disabled_submenu_'. $role .'_items'] = array();
		$adminimizeoptions['mw_adminimize_disabled_global_option_'. $role .'_items'] = array();
		$adminimizeoptions['mw_adminimize_disabled_metaboxes_post_'. $role .'_items'] = array();
		$adminimizeoptions['mw_adminimize_disabled_metaboxes_page_'. $role .'_items'] = array();
	}

	$adminimizeoptions['mw_adminimize_default_menu'] = $menu;
	$adminimizeoptions['mw_adminimize_default_submenu'] = $submenu;

	add_option('mw_adminimize', $adminimizeoptions);
}

/**
 * export options in file 
 */
function _mw_adminimize_export() {
	global $wpdb;

	$filename = 'adminimize_export-' . date('Y-m-d_G-i-s') . '.seq';
		
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=" . urlencode($filename));
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	header('Content-Type: text/seq; charset=' . get_option('blog_charset'), true);
	flush();
		
	$export_data = mysql_query("SELECT option_value FROM $wpdb->options WHERE option_name = 'mw_adminimize'");
	$export_data = mysql_result($export_data, 0);
	echo $export_data;
	flush();
}

/**
 * import options in table _options
 */
function _mw_adminimize_import() {
	
	// check file extension
	$str_file_name = $_FILES['datei']['name'];
	$str_file_ext  = explode(".", $str_file_name);

	if ($str_file_ext[1] != 'seq') {
		$addreferer = 'notexist';
	} elseif (file_exists($_FILES['datei']['name'])) {
		$addreferer = 'exist';
	} else {
		// path for file
		$str_ziel = WP_CONTENT_DIR . '/' . $_FILES['datei']['name'];
		// transfer
		move_uploaded_file($_FILES['datei']['tmp_name'], $str_ziel);
		// access authorisation
		chmod($str_ziel, 0644);
		// SQL import
		ini_set('default_socket_timeout', 120);
		$import_file = file_get_contents($str_ziel);
		_mw_adminimize_deinstall();
		$import_file = unserialize($import_file);
		update_option('mw_adminimize', $import_file);
		unlink($str_ziel);
		$addreferer = 'true';
	}

	$myErrors = new _mw_adminimize_message_class();
	$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_import') . '</p></div>';
	echo $myErrors;
}
?>
