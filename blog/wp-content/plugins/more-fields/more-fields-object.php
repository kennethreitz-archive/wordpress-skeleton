<?php

global $mf0;

class mf_field_type {

	var $title;
	var $values;
	var $html_before = '';
	var $html_after = '';
	var $html_item;
	var $html_selected = '';
}



class more_fields_object {

	var $plugin_path;
	var $plugin_url;
	var $field_types;
	var $is_ok;
	var $divs;


	/*
	**	init()
	**
	*/
	function init () {

		$this->divs = array(
			'post' => array (
			//	array(__('Title'), 'titlediv'),
			//	array(__('Post'), 'postdiv'),
				array(__('Tags'), 'tagsdiv'),
				array(__('Categories'), 'categorydiv'),
				array(__('Excerpt'), 'postexcerpt'),
				array(__('Send Trackbacks'), 'trackbacksdiv'),
				array(__('Custom Fields'), 'postcustom'),
				array(__('Post Author'), 'authordiv'),
				array(__('Post Slug'), 'slugdiv'),
//				array(__('Password Protect This Post'), 'passworddiv', true),
				array(__('Discussion'), 'commentstatusdiv'),
				array(__('Post Revisions'), 'revisionsdiv')
			),
			'page' => array(		
			//	array(__('Title'), 'titlediv'),
			//	array(__('Post'), 'postdiv'),
				array(__('Custom Fields'), 'postcustom'),
				array(__('Discussion'), 'commentstatusdiv'),
				array(__('Slug'), 'pageslugdiv'),
				array(__('Attributes'), 'pageparentdiv'),
				array(__('Page Author'), 'pageauthordiv'),
				array(__('Page Revisions'), 'revisionsdiv')
			)
		);

	
		// Pre-2.6 compatibility
		if ( !defined('WP_CONTENT_URL') )
			define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
		if ( !defined('WP_CONTENT_DIR') )
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		// Guess the location
		$this->plugin_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
		$this->plugin_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));

 		add_action('admin_init', array(&$this, 'admin_init'));
 		
 		// Save the Custom Fields when saving the post
 		add_action('save_post', array(&$this, 'save_post_meta'), 11, 2);
		add_action('save_page', array(&$this, 'save_post_meta'), 11, 2);
		add_action('publish_post', array(&$this, 'save_post_meta'), 11, 2);
		add_action('publish_page', array(&$this, 'save_post_meta'), 11, 2);
	
		// Translate this plugin
		add_action('init', array(&$this, 'translate'));
		
		// Add the JS and CSS for the Write/Edit page
		add_action('admin_head', array(&$this, 'admin_head'));

		// Add the Options page
		//add_action('admin_menu', array(&$this, 'admin_menu'));

		// Do the Write menu
		add_action('_admin_menu', array(&$this, 'write_menu'));
		
		// Enable querying of Custom Fields using rewrite engine
		add_filter('query_vars', array(&$this, 'query_vars'));
		add_filter('posts_join', array(&$this, 'query_join'));
		add_filter('posts_where', array(&$this, 'query_where'));
		//add_filter('init', array(&$this, 'flush_rewrite_rules'));
		add_filter('generate_rewrite_rules', array(&$this, 'generate_rewrite_rules'));

		// add_action('admin_head', array(&$this, 'admin_options_head'));
		// add_action('admin_init', array(&$this, 'pre_queue_js'));

		// Redirect if the type has a template associated with it...
		add_filter('template_redirect', array(&$this, 'post_template'), 9);

		add_filter('restrict_manage_posts', array(&$this, 'restrict_manage_posts'));

		// Enable external modification of field types
		add_filter('more_fields_field_types', array(&$this, 'return_unmodified'));

		// 
		add_filter('submitpage_box', array(&$this, 'submitpage_box'));
		add_filter('post_submitbox_start', array(&$this, 'submitpage_box'));

		add_filter('remove_boxes', array(&$this, 'submitpage_box'));
		add_filter('remove_boxes', array(&$this, 'submitpage_box'));
		
		add_filter('screen_meta_screen', array(&$this, 'screen_meta_screen'));
		add_filter('in_admin_footer', array(&$this, 'in_admin_footer'));
		
		// Fix user option
		add_filter("get_user_option_metaboxhidden_post", array(&$this, 'fix_user_meta_hidden'), 10, 3);
		add_filter("get_user_option_metaboxhidden_page", array(&$this, 'fix_user_meta_hidden'), 10, 3);
		add_filter("get_user_option_closedpostboxes_post", array(&$this, 'fix_user_meta_closed'), 10, 3);
		add_filter("get_user_option_closedpostboxes_page", array(&$this, 'fix_user_meta_closed'), 10, 3);

		add_filter('screen_layout_columns', array(&$this, 'screen_layout_columns'), 10, 2);
		
		add_action( 'wp_default_scripts', array(&$this, 'wp_default_scripts'));
	}
	function remove_boxes() {
		if ($type = $this->get_type()) {
			echo $type;
		}
	}
	function in_admin_footer() {

		
	}
	function wp_default_scripts(&$scripts) {
		global $wp_version;
		$src = get_option('siteurl') . '/wp-content/plugins/more-fields/post-' . $wp_version . '.js';
		if (!file_exists($src)) $src = get_option('siteurl') . '/wp-content/plugins/more-fields/post-2.9.2.js';
		$scripts->registered['post']->src = $src;
	}
	function return_unmodified ($value) {
		return $value;
	}
	
	function screen_meta_screen ($screen) {
		global $wp_meta_boxes, $mf_settings_temp;
	
		// Add the boxes to context
		$pages = $this->get_pages();
		foreach ($pages as $page) {
			$pagename = sanitize_title($page['name']);
			$wp_meta_boxes[$pagename] = $wp_meta_boxes[$page['based_on']];
		}

		// But what if we're not on an edit page?
		if (!strpos($_SERVER['PHP_SELF'], 'edit.php'))
			if ($type = attribute_escape($_GET['type'])) $screen = sanitize_title($type);
		return $screen;
	}
	
	
	/*
	**	edit_page_form()
	**
	*/
	function submitpage_box () {
		global $post;
		$pages = $this->get_pages();
		if ($id = attribute_escape($_GET['type'])) {
			echo "<input type='hidden' name='mf_page_type' value='$id'>\n";
			// Is this a new page?
			if (!$post->ID && $pages[$id]['based_on'] == 'page') {
				// Set the defaults
				$post->page_template = $pages[$id]['template'];
				$post->post_parent = $pages[$id]['post_parent'];
			}
		}
	}
	
	/*
	**	admin_init()
	**
	*/
	function admin_init() {
		global $wp_meta_boxes;
	
		$pages = $this->get_pages();
		foreach ($pages as $page) { 
			if ($page['based_on'] == 'post') 
				add_action('manage_' . $page['name'] . '_columns', array(&$this, 'get_page_columns'));
			if ($page['based_on'] == 'page') 
				add_action('manage_' . $page['name'] . '_columns', array(&$this, 'get_page_columns'));
		}

	
		if (is_callable('add_meta_box')) {
			$boxes = $this->get_boxes();

			// Hook the More Fields boxes into the WP meta box framework
			foreach($boxes as $box) {
				if (!($box = apply_filters('mf_box', $box))) continue;
				
				// If it's positioned to the right, then add an additional page type, not processed by WP
				$context = ($box['position'] == 'right') ? 'side' : 'normal';
				add_meta_box(sanitize_title($box['name']), $box['name'], 'mf_ua_callback', 'post', $context);
				add_meta_box(sanitize_title($box['name']), $box['name'], 'mf_ua_callback', 'page', $context);
			}
		}
		
//		$pages = $this->get_pages();
//		foreach ($pages as $page) {
//			$option = 'meta-box-hidden_' . $page['name'];
//		}


		global $scripts;
		print_r($scripts);

	}

	function fix_user_meta_hidden($res, $asd, $user) {
		if ($type = sanitize_title(attribute_escape($_GET['type']))) {
			if (in_array($type, array('post' ,'page'))) return $res;
			return (array) get_user_option( "meta-box-hidden_$type", 0, false );
		}
		else return $res;
	}
	function fix_user_meta_closed($res, $asd, $user) {
		if ($type = sanitize_title(attribute_escape($_GET['type']))) {
			if (in_array($type, array('post' ,'page'))) return $res;
			return (array) get_user_option( "closedpostboxes_$type", 0, false );
		}
		else return $res;
	}
	
	function get_page_columns ($page) {
		return wp_manage_pages_columns();
	}

	function get_post_columns ($page) {
		return wp_manage_posts_columns();
	}

	
	/*
	**	init_field_types()
	**
	*/
	function init_field_types () {
		$text = new mf_field_type;
		$text->title = __('Text', 'more-fields');
		$text->html_item = '<input class="%class%" type="text" name="%key%" value="%value%">';
		$this->field_types[] = $text;
	
		$textarea = new mf_field_type;
		$textarea->title = __('Textarea', 'more-fields');
		$textarea->html_item = "<textarea class='%class%' name='%key%'>%value%</textarea>";
		$this->field_types[] = $textarea;

		$wysiwyg = new mf_field_type;
		$wysiwyg->title = __('WYSIWYG', 'more-fields');
		$wysiwyg->html_before = '
		
		<script type="text/javascript">
			/* <![CDATA[ */
/*
	//	jQuery(document).ready( function () { 
	//		tinyMCE2 = tinyMCE;
			tinyMCE.init({
				mode:"textareas",
				width:"100%",
				theme:"advanced",
				skin:"wp_theme",
				theme_advanced_buttons1:"bold,italic,strikethrough,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,link,unlink,wp_more,|,spellchecker,fullscreen,wp_adv",
				theme_advanced_buttons2:"", //formatselect,underline,justifyfull,forecolor,|,pastetext,pasteword,removeformat,|,media,charmap,|,outdent,indent,|,undo,redo,wp_help",
				theme_advanced_buttons3:"",
				theme_advanced_buttons4:"",language:"en",spellchecker_languages:"+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv",
				theme_advanced_toolbar_location:"top",
				theme_advanced_toolbar_align:"left",
				theme_advanced_statusbar_location:"bottom",
				theme_advanced_resizing:"0",
				theme_advanced_resize_horizontal:"",
				plugins:"safari,inlinepopups,spellchecker,paste,wordpress,media,fullscreen,wpeditimage",
				editor_selector:"%key%"
			});
	//	});		
*/

			jQuery(document).ready( function () { 
				jQuery("#%key%").addClass("mceEditor");
				if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
				tinyMCE.execCommand("mceAddControl", false, "%key%");
				}
			}); 
		
			/* ]]> */
			</script>
			<div style="width: 100%">
		<textarea class="%class% %key%" name="%key%" id="%key%">' . "\n";

		$wysiwyg->html_item = "%value%";
		$wysiwyg->html_after = "</textarea></div>\n";
		$this->field_types[] = $wysiwyg;

		$select = new mf_field_type;
		$select->title = __('Select', 'more-fields');
		$select->html_before = "<select class='%class%' name='%key%'>\n";
		$select->html_item = "<option value='%value%' %selected%>%value%</option>";
		$select->html_after = "</select>\n";
		$select->html_selected = 'selected="selected"';
		$select->values = true;
		$this->field_types[] = $select;
		
		$radio = new mf_field_type;
		$radio->title = __('Radio', 'more-fields');
		$radio->html_item = "<label class='mf_radio'><input class='%class%' type='radio' name='%key%' value='%value%' %selected%> %value%</label>";
		$radio->html_selected = 'checked="checked"';
		$radio->values = true;
		$this->field_types[] = $radio;

		$checkbox = new mf_field_type;
		$checkbox->title = __('Checkbox', 'more-fields');
		$checkbox->html_item = "<label class='mf_check'><input class='%class%' type='checkbox' id='%key%' name='%key%' %selected%> %title%</label>";
		$checkbox->html_selected = 'checked="checked"';
		$checkbox->values = false;
		$this->field_types[] = $checkbox;

		$checkbox = new mf_field_type;
		$checkbox->title = __('File list', 'more-fields');
		$checkbox->html_item = "		
		<input type='hidden' id='%key%' name='%key%' value='%value%'>
		<div class='mf_file_list_show' id='mf_file_list_show_%key%'>
			<a href='%value%'>%value%</a> <input type='button' class='button file_list_update' id='mf_file_list_edit_button_%key%' value='Edit' />
		</div>
		<div class='mf_file_list_edit' id='mf_file_list_edit_%key%'>
			<label class='mf_filelist'>	
				<select class='%class% mf_file_list_select' type='checkbox' id='%key%_temp' name='%key%' %selected%></select> 
				<input type='button' class='button file_list_update' id='mf_file_list_update_button_%key%' value='Update list' /> 
			</label>
		</div>
			";
		$checkbox->html_selected = 'checked="checked"';
		$checkbox->values = false;
		$this->field_types[] = $checkbox;
		
		
		
		$this->field_types = apply_filters('more_fields_field_types', $this->field_types);
	}
	
 	/*
 	**	translate()
 	**
 	*/
	function translate(){
		load_plugin_textdomain('more-fields', $this->plugin_url);	
	}
 
 
 	/*
 	**	admin_head()
 	**
 	*/
	function admin_head() {
		$on_write_page = (preg_match("/\/post/", $_SERVER['PHP_SELF']) || preg_match("/\/page/", $_SERVER['PHP_SELF']));
		$on_edit_listing = (preg_match("/\/edit/", $_SERVER['PHP_SELF']));

		echo "\n" . '<link rel="stylesheet" type="text/css" href="' . $this->plugin_url . '/more-fields-css.php" />' . "\n";
		
		if ($on_write_page || $on_edit_listing) {

			$post_id = attribute_escape($_GET['post']);
			$type = ($t = attribute_escape($_GET['type'])) ? $t : get_post_meta($post_id, 'mf_page_type', true);
			if (!$type) {
				if (strpos($_SERVER['REQUEST_URI'], 'post')) $type = 'Post';
				if (strpos($_SERVER['REQUEST_URI'], 'page')) $type = 'Page';
			}
			$_GET['type'] = $type;
			$url = $this->plugin_url . '/more-fields-write-js.php?type=' . urlencode($type);
			echo "<script type='text/javascript' src='$url'></script>\n";		
		}
	}
	/*
	**  dsc_admin_pages (  )
	*/
//	function admin_menu() {
//		add_options_page('More Fields', 'More Fields', 8, 'more-fields', 'p2m_meta_box_settings');
//	}
	/*
	**	ua_callback()
	**
	*/
	function ua_callback($object, $box) {
		$boxes = $this->get_boxes();
		$this->build_box_gut($boxes[$box['title']]);	
	}

	/*
	**	get_pages()
	**
	*/
	function get_pages() {
		global $more_fields_pages;
		if (!is_array($more_fields_pages)) $more_fields_pages = array();

		$pages = get_option('more_fields_pages');
		if (!is_array($pages)) $pages = array();

		foreach (array_keys((array) $more_fields_pages) as $key)
			$pages[$key] = $more_fields_pages[$key];

		return $pages;
	}

	/*
	**	get_boxes()
	**
	*/
	function get_boxes($option = false) {
		global $more_fields_boxes;
		$more_fields = get_option('more_fields_boxes');

		// Ignoring programatically set boxes
		if ($option) return $more_fields;

		if (!is_array($more_fields)) $more_fields = array();
		if (!is_array($more_fields_boxes)) $more_fields_boxes = array();
		
		foreach (array_keys($more_fields_boxes) as $key)
			$more_fields[$key] = $more_fields_boxes[$key];
		return $more_fields;
	}	


	
	/*
	**	get_boxes()
	**
	*/
	function field_type_render ($html, $field, $position, $value_raw = '', $html_selected = '') {
		global $post;

		$value_stored = format_to_edit(get_post_meta($post->ID, $field['key'], true));
		if (!$value_raw) $value_raw = $value_stored;
		$value = (strstr($value_raw, '*') && ($html_selected)) ? substr($value_raw, 1) : $value_raw;

		// $value = addslashes($value_stored);

		$html = str_replace('%class%', 'mf_' . $field['type'], $html);
		$html = str_replace('%key%', sanitize_title($field['key']), $html);
		$html = str_replace('%value%', stripslashes($value), $html);
		$html = str_replace('%title%', $field['title'], $html);

		// if ($value_stored) $html = str_replace('%selected%', $html_selected, $html);

		// Does this needs to be checked/selected/ticked?
		if ($value && ($value == $value_stored)) $html = str_replace('%selected%', $html_selected, $html);
		else if ((!$value_stored) && ($value_raw != $value)) $html = str_replace('%selected%', $html_selected, $html);
		else if ($value_stored == 'on') $html = str_replace('%selected%', $html_selected, $html);
		else $html = str_replace('%selected%', '', $html);
		return $html;
	}
	
	/*
	**	get_boxes()
	**
	*/
	function build_box_gut($box) {
		do_action('mf_box_head', $box);

		foreach ((array) $box['field'] as $field) {
			if (!($field = apply_filters('mf_field', $field))) continue;

			$title = '<label class="mf_label" for="' . $field['key'] . '">' . $field['title'] . ':</label>';


 			echo '<p class="mf_field_wraper_' . $field['key'] .' ' . $field['type'] . '">';
			if ($field['title'] && $field['type'] != 'checkbox') echo $title;

			foreach ($this->field_types as $type) {
				if (sanitize_title($type->title) == $field['type']) {

					// Parse out the values, including ascending and descending ranges
					$values = array();
					$parts = explode(',', $field['select_values']);

					// Add empty option at top for select lists

					if ($field['type'] == 'select') $values[] = '';
					foreach ((array) $parts as $part) {
						$range = explode(':', $part);
						if (count($range) == 2) {
							if ($range[0] < $range[1]) {
								for ($j = $range[0]; $j <= $range[1]; $j++)
									$values[] = $j;
							} else {
								for ($j = $range[0]; $j >= $range[1]; $j--)
									$values[] = $j;
							}
						} else $values[] = $part;
					}
					
					// Get the closed boxes
					$post_type = sanitize_title($this->get_type());
					$hidden = (array) get_user_option("meta-box-hidden_${post_type}", 0, false );
					$box_is_hidden = (in_array(sanitize_title($box['name']), $hidden));

					// Write the field
					echo $this->field_type_render($type->html_before, $field, $box['position']);		
					if (empty($values)) echo $this->field_type_render($type->html_item, $field, $box['position']);
					else {
						foreach ($values as $v) {
						
							// If the box is closed ingore default values
							if ($box_is_hidden) {
								if (count($values) > 1) $html_selected = '';
								else $v = '';							
							} else $html_selected = $type->html_selected;
		
							echo $this->field_type_render($type->html_item, $field, $box['position'], rtrim(ltrim($v)), $html_selected);
						}
					}
					echo $this->field_type_render($type->html_after, $field, $box['position']);			
				}
			}
			echo '</p>';
			do_action('mf_box_foot', $box);
		}
	}
	function get_type() {
		if (!($type = attribute_escape($_GET['type'])))
			$type = (strpos($_SERVER['REQUEST_URI'], 'post')) ? 'post' : 'page';
		return $type;
	}


	/*
	**	build_right_boxes_post()
	**
	*/
	function build_right_boxes_post () {
		$this->build_right_boxes('post');
	}
	
	/*
	**	build_right_boxes_page()
	**
	*/
	function build_right_boxes_page () {
		$this->build_right_boxes('page');
	}

	function allow($box) {
		return true;
	}
	
	/*
	**	save_post_meta()
	**
	*/
	function save_post_meta($new_post_id, $post) {
	    global $wpdb, $post;		

		// Ignore autosaves, ignore quick saves
		if (@constant( 'DOING_AUTOSAVE')) return $post;
		if (!$_POST) return $post;
		if (!in_array($_POST['action'], array('editpost', 'post'))) return $post;


		$post_id = attribute_escape($_POST['post_ID']);
		if (!$post_id) $post_id = $new_post_id;
		if (!$post_id) return $post;
		
		// Make sure we're saving the correct version
		if ( $p = wp_is_post_revision($post_id)) $post_id = $p;
		
		// We're saving the postmeta to the revision too.
	/*
		if ($post_id && $new_post_id && ($post_id != $new_post_id)) 
			$ids = array($post_id, $new_post_id);
		else $ids = array($post_id);
	*/
		
		// Save the page type
		if ($type = attribute_escape($_POST['mf_page_type'])) {
			if ($type == 'mf_none') $type = '';
			if (!$type && (!get_post_meta($post_id, 'mf_page_type', true))) {
				// Do nothing
			} else {
				if (!add_post_meta($post_id, 'mf_page_type', $type, true)) 
					update_post_meta($post_id, 'mf_page_type', $type);
				$mf_pages = $this->get_pages();
				// Set the category
				if (!$_POST['post_category']) $_POST['post_category'] = $mf_page[$type]['cats'];
			}
		}
		
		$more_fields = $this->get_boxes();

		// Watch me being very defensive.
		// foreach ($ids as $post_id) {
		foreach ($more_fields as $box) {
			foreach((array) $box['field'] as $field) {
				$key = $field['key'];
				$post_key = sanitize_title($key);
				$meta_data = get_post_custom($post_id);
				// Ok, must do this since an unticked checkbox does not appear in $_POST;
				if (array_key_exists($post_key, (array) $_POST) || array_key_exists($key, (array) $meta_data)) {
					$value = stripslashes($_POST[$post_key]);
					$stored_value = $meta_data[$key][0];
						if ($value || (!$value && get_post_meta($post_id, $key, true))) {
						if ($value != get_post_meta($post_id, $key, true))  {
							if ($field['type'] == 'wysiwyg') $value = wpautop($value);
							if (!add_post_meta($post_id, $key, $value, true)) 
								update_post_meta($post_id, $key, $value);	
						}
					}
				}
			}	
		}
		// }
		// exit();
		return $post;
	}

	function get_option ($what) {
		if ($what == 'options') {
			$options = get_option('more_fields_options');
			$defaults = array(
				'enable_pages' => 'on',
				'remove_link' => '',
				'slugbase' => '/archive/',
				'show_page_type_select' => true,
			);
			$options = wp_parse_args($options, $defaults);
			return $options;
		}
		return false;
	}

	/*
	**  write_menu()
	**
	*/
	function write_menu () {
		global $submenu, $menu, $parent_file, $current_user, $wp_roles;

// print_r($menu);
//print_r($submenu);
// exit();
		// Remove Menu items if needed
		$option = $this->get_option('options');
		if ($option['remove_post']) {
			//unset($submenu['post-new.php'][5]);
			//unset($submenu['edit.php'][5]);
			unset($submenu['edit.php']);
			unset($menu[5]);
		}
		if ($option['remove_page']) {
			//unset($submenu['post-new.php'][10]);
			//unset($submenu['edit.php'][10]);
			unset($submenu['edit-pages.php']);
			unset($menu[20]);
		}
		if ($option['remove_link']) {
			//unset($submenu['post-new.php'][15]);
			//unset($submenu['edit.php'][15]);
			unset($submenu['link-manager.php']);
			unset($menu[15]);
		}

		// Create the new menu
		$pages = $this->get_pages();		
		$nbr = 6;
		get_currentuserinfo();
		foreach ($pages as $page) {
		
			if (in_array($page['name'], array('Page', 'Post'))) continue;
		
			// Does user have access to this post type?
			if (!is_array($page['user_level'])) {
				if ($required_level = $page['user_level'])
					if ($current_user->user_level < $required_level) continue;
			} else {
				$ok = false;
				foreach ($current_user->roles as $role) {
					if (in_array($role, $page['user_level'])) $ok = true;
				}
				if (!$ok) continue;			
			}
			
			while ($menu[$nbr]) $nbr++;

//			if ($page['name'] == 'Post' || $page['name'] == 'Page') continue;
			$based_on = ($a = $page['based_on']) ? $a : 'post';
	//		$menu[21.001] = $menu[20]; //][0] = $page['name'];
			
			// Set the icon
			if (!$page['icon']) $page['icon'] = 'posts.png';
			else if ($page['icon'] == 'url') $icon = $page['alternative_icon'];
			else $icon = 'images/menu/' . $page['icon'];
			// 0 = name, 1 = capability, 2 = file, 3 = class, 4 = id, 5 = icon src
			$link = $based_on . '-new.php?type=' . urlencode($page['name']);
			$id = 'menu-' . sanitize_title($page['name']);
			$icon = 'div';
			$name = ($n = $page['plural']) ? $n : $page['name'];
			$menu[$nbr] = array( $name, 'edit_posts', $link, $id, 'menu-top', $id, $icon );
			
			$edit_link = ($page['based_on'] == 'post') ? 'edit.php?type=' . urlencode($page['name']) :
				'edit-pages.php?type=' . urlencode($page['name']);	
			$submenu[$link][10] = array('Edit', 'edit_posts', $edit_link);
			$submenu[$link][5] = array(__('Add new'), 'edit_posts', $link);

//			$nbr++;
			

/*
			$submenu['post-new.php'][$nbr] = array($page['name'], 'edit_posts', $based_on . '-new.php?type=' . urlencode($page['name']));
			if ($page['based_on'] == 'post')
				$submenu['edit.php'][$nbr + 35] = array($page['name'], 'edit_posts', 'edit.php?type=' . urlencode($page['name']));	
			else
				$submenu['edit.php'][$nbr + 35] = array($page['name'], 'edit_pages', 'edit-pages.php?type=' . urlencode($page['name']));	
			$nbr += 15;
*/
		}
		
		ksort($menu);
		// print_r($menu);
	}

	function flush_rewrite_rules() {
	   	global $wp_rewrite, $wp_query, $wp;
	    	$wp_rewrite->flush_rules();
			$wp_query->query_vars[] = 'mf_key';
			$wp_query->query_vars[] = 'mf_value';
		// $wp->add_query_var('mf_key');
		// $wp->add_query_var('mf_value');
	}


	function restrict_manage_posts () {
		$type = attribute_escape($_GET['type']);
		echo "<input type='hidden' value='$type' name='type'>\n";	

		$pages = $this->get_pages();
//		echo '<select name="type">';
//		echo "<option value='$type'>Post types</option>";
//		foreach ($pages as $page) {
//			echo "<option value="">";
//		}
//		echo '</select>';
	}

	function generate_rewrite_rules () {
		global $wp_taxonomies, $wp_rewrite, $wp;
		$boxes = $this->get_boxes();
		$rules = array();
		foreach ($boxes as $box) {

			foreach ((array) $box['field'] as $field) {

				// If no slug is defined, then skip creating a rewrite rule
				$slug = substr($field['slug'], 1, strlen($field['slug']));
				if (!$slug) continue;				
				$key = $field['key'];
				$wp_rewrite->add_rule("$slug/(.+)", "index.php?mf_key=$key&mf_value=\$matches[1]", "top");
			}

		}
		return $wp_rewrite; 
	}

	
	
	/*
	**
	**
	*/
	function query_vars( $qvars ) {
		$qvars[] = 'mf_key';
		$qvars[] = 'mf_value';
		return $qvars;
	}

	/*
	**
	**
	*/
	function query_join( $join ) {
		global $wpdb, $wp_query;		
		if ($key = $wp_query->get('mf_key') || $type = attribute_escape($_GET['type'])) {
			$join .= " LEFT JOIN $wpdb->postmeta as meta ON $wpdb->posts.ID = meta.post_id";
		}
		return $join;
	}

	/*
	**
	**
	*/
	function query_where( $where ) {
		global $wpdb, $wp_query;

		// Should probably be exclusive instead of inclusive
		if(strpos($_SERVER['SCRIPT_NAME'], '/media-upload.php')) return $where;

		$key = $wp_query->get('mf_key');
		$value = $wp_query->get('mf_value');

		if ( $key )
			$catch = "and $wpdb->posts.post_status='publish'";
			
		if ( $key && $value ) $where .= " AND meta.meta_value='$value'" . $catch;
		else if ($key) $where .= " AND meta.meta_value=!''" . $catch;

		// We want to be able to sort by panel type
		if  (($type = attribute_escape($_GET['type'])) && is_admin()) {
			$where .= " AND meta.meta_key='mf_page_type' AND meta.meta_value='$type'";			
		 }
		 //echo $where;
		//exit(0);
	return $where;
	}	
	
	/*
	** 	post_template ()
	**
	*/	
	function post_template() {
		global $post, $wp_query;
		if (count($wp_query->posts) == 1) {
			$id = $wp_query->posts[0]->ID;
			if ($type = get_post_meta($id, 'mf_page_type', true)) {
				$pages = $this->get_pages();
				if ($template = $pages[$type]['template']) {
					$file = TEMPLATEPATH . '/' .$template;
					if (file_exists($file)) {
						include($file);
						exit(0);
					}
					return false;
				}
			}
		}
	}

	/*
	function admin_options_head() {
		global $page_hook;
		if ($page_hook && strpos($page_hook, 'more-fields')) {
			$siteurl = get_settings('siteurl');
			$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/more-fields-options.css';
			echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
			// I'd rather use wp_enqueue script for WP pre 2.5
			if (!is_callable('add_meta_box')) {
				$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/more-fields-js.php';
				$prourl = $siteurl . '/wp-includes/js/prototype.js';
				echo "<script type='text/javascript' src='$prourl'></script>\n";
				echo "<script type='text/javascript' src='$url'></script>\n";
			}
		}
	}
	// Load the javascript in 2.5
	function queue_js () {
		wp_enqueue_script('jquery');
		wp_enqueue_script('postbox');
		wp_enqueue_script('prototype');
		$path = $this->plugin_url . '/more-fields-js.php';
		wp_enqueue_script('mf_js', $path, 'prototype');
	}

	function pre_queue_js () {
		add_action('load-' . sanitize_title(__('Settings')) . '_page_more-fields', array(&$this, 'queue_js'));
	}
	*/
	function all_boxes($based_on = 'post') {
		global $wp_meta_boxes;

		$allboxes = array();
	
		// Default boxes

		$boxes = $this->divs;
 		foreach ((array) $boxes[$based_on] as $box) 
			$allboxes[] = $box[1];

 		// Boxes created within More Fields
 		$more_fields = $this->get_boxes();
		foreach ((array) $more_fields as $box) 
			$allboxes[] = $box['name'];

		// The other boxes
		$data = $wp_meta_boxes; //[$pages[$id]['based_on']];
		foreach ((array) $data as $context) {
			foreach ((array) $context as $priority) {
				foreach ((array) $priority as $position) {
					foreach ((array) $position as $box) {
						if ($box['callback'] == 'mf_ua_callback') continue;
						if ($box['title'] && !in_array($box['id'], $allboxes)) $allboxes[] = $box['id'];
					}
				}	
			}						
		}
		return $allboxes;
	}
	function screen_layout_columns ($columns, $screen) {
		if ($_GET['type']) $columns[$screen] = 2;
		return $columns;
	}
}

function mf_ua_callback($object, $box) {
	global $mf0;
	$boxes = $mf0->get_boxes();
	$mf0->build_box_gut($boxes[$box['title']]);	
}

?>
