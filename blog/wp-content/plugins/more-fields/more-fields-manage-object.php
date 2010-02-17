<?php

class more_fields_manage {

	var $mf_object, $divs;
	
	/*
	**
	**
	*/
	function init($object) {
		$this->mf_object = $object;
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
				array(__('Custom Fields'), 'pagecustomdiv'),
				array(__('Discussion'), 'pagecommentstatusdiv'),
				array(__('Slug'), 'pageslugdiv'),
				array(__('Attributes'), 'pageparentdiv'),
				array(__('Page Author'), 'pageauthordiv'),
				array(__('Page Revisions'), 'revisionsdiv')
			)
		);
		// Add the Options JS and CSS
		add_action('settings_page_more-fields', array(&$this, 'admin_options_head'));
		add_action('admin_init', array(&$this, 'pre_queue_js'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}
	
	/*
	**
	**
	*/	
	function options_validate_field_input_get ($action) {
		$boxes = $this->mf_object->get_boxes('i');
		$ok = $this->condition(($box = attribute_escape($_GET['box'])), __('Error! Seems to be missing a box.', 'more-fields'));
		$ok = $this->condition(($key = attribute_escape($_GET['key'])), __('Error! Seems to be missing a key.', 'more-fields'));
		$ok = $this->condition(($name = $boxes[$box]['name']), __('Your are trying to ' . $action . ' a field in a box that doesn\'t exist.', 'more-fields'));
		$index = attribute_escape($_GET['field']);
		$ok = $this->condition(($boxes[$box]['field'][$index]['key'] == $key), __('Error! The field you are trying to ' . $action . ' does not exist.', 'more-fields'));
		return array('ok' => $ok, 'index' => $index, 'box' => $box, 'key' => $key, 'name' => $name, 'boxes' => $boxes);
	}
	
	function slugize($slug) {
		$newslug = '';
		$parts = explode('/', $slug);
		foreach ($parts as $part) if ($part) $newslug .= '/' . sanitize_title($part);
		return $newslug;
	}
	
	
	/*
	**
	**
	*/
	function options_move_field ($up = true) {

		$text = ($up) ? __('move up', 'more-fields') : __('move down', 'more-fields');

		extract($this->options_validate_field_input_get($text));

		if ($ok) {
			$new = array();
			$fields = ($up) ? array_reverse($boxes[$box]['field']) : $boxes[$box]['field'];
			foreach ($fields as $field) {
				if ($field['key'] == $key) { 
					$data = $field;
					continue;
				}
				array_push($new, $field);
				if ($data) {
					array_push($new, $data);
					unset($data);
				}
			}
			$boxes[$box]['field'] = ($up) ? array_reverse($new) : $new;
			update_option('more_fields_boxes', $boxes);
		}	
		$_GET['action'] = 'edit_box';
	}
	
	/*
	**
	**
	*/
	function condition($condition, $message, $type = 'error') {

		if (!isset($this->is_ok)) $this->is_ok = true;

		// If there is an error already return
		if (!$this->is_ok && $type = 'error') return $this->is_ok;

		if ($condition == false && $type != 'silent') {
			echo '<div class="updated fade"><p>' . $message . '</p></div>';

			// Don't set the error flag if this is a warning.
			if ($type == 'error') $this->is_ok = false;
		}
	
		return ($condition == true);
	}
	
	/*
	**
	**
	*/
	function admin_options_head() {
		global $page_hook;

		$siteurl = get_settings('siteurl');
		$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/more-fields-options.css';
		echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
		// I'd rather use wp_enqueue script for WP pre 2.5
		if (is_callable('add_meta_box')) {
			$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/more-fields-manage-js.php';
			$prourl = $siteurl . '/wp-includes/js/prototype.js';
			echo "<script type='text/javascript' src='$prourl'></script>\n";
			// echo "<script type='text/javascript' src='$url'></script>\n";
		}
	}
	// Load the javascript in 2.5
	function queue_js () {
		wp_enqueue_script('jquery');
//		wp_enqueue_script('postbox');
//		wp_enqueue_script('prototype');
		$path = get_option('siteurl') . '/wp-content/plugins/more-fields/more-fields-manage-js.php';
		wp_enqueue_script('mf_js', $path, 'jquery');
	}

	function pre_queue_js () {
		add_action('load-' . sanitize_title(__('Settings')) . '_page_more-fields', array(&$this, 'queue_js'));
	}
	/*
	**	get_boxes()
	**
	*/
	function checkbox($name, $arr, $default = true) {
		$id = ($_GET['id']) ? $_GET['id'] : $_POST['name'];
		$pages = $this->mf_object->get_pages();
		$checked = ($arr[sanitize_title($name)]) ? ' checked="checked"' : '';
//		if (!isset($pages[$id]) && $default) $checked = ' checked="checked"';
		if (!$arr) $checked = ' checked="checked"';
		$name = sanitize_title($name);
		return "<input type='checkbox' name='$name' value='$name'$checked />\n";
	}
	function admin_menu() {	
		add_options_page('More Fields', 'More Fields', 8, 'more-fields', array(&$this, 'options_page'));
	}
	function options_page () {
	
		$tab = attribute_escape($_GET['tab']);
		if (!$tab) $tab = 'boxes';
		$url = get_option('siteurl') . '/wp-admin/options-general.php?page=' . dirname(plugin_basename(__FILE__)) . '&tab=';
		?>	
		<div class="wrap">
			<div id="poststuff">
<?php /* 	
				<h3>Kal:</h3>
				<ul>
					<li>MF permalinkar? E.g. /meta/artist/bjork/ for en archive sida baserad pa metadata, hor bor en generisk struktur se ut? </li>
					<li>Write/edit pages? Funkar det som koncept? Ska det vara baserat pa kategori eller helt fritt - ar en page type en kategori? </li>
					<li>Interaktion - vad bor andras och forbattras? Navigation.</li>
					<li>Todo:
						<ul>
							<li>Texts</li>	
						</ul>
					</li>
				</ul>	
*/ ?>			
			
				<h2>More Fields</h2>
				<p><?php // _e('More Fields enables you to add boxes to the Write/Edit page, and to make Write/Edit pages, that include subsets of boxes, either the prediefined ones or the ones you create yourself.', 'more-fields'); ?> </p>
				<p>
					<ul class="subsubsub mf">
						<li><a <?php if ($tab == 'boxes') echo 'class="current"'; ?> href="<?php echo $url; ?>boxes"><?php _e('Manage boxes', 'more-fields'); ?></a></li>
						<li><a <?php if ($tab == 'pages') echo 'class="current"'; ?> href="<?php echo $url; ?>pages"><?php _e('Manage post types', 'more-fields'); ?></a></li>
						<!-- <li><a <?php if ($tab == 'rc') echo 'class="current"'; ?> href="<?php echo $url; ?>rc">Metadata RC</a></li> -->
						<?php do_action('more_fields_options_menu', $tab); ?>
						<li><a <?php if ($tab == 'options') echo 'class="current"'; ?> href="<?php echo $url; ?>options"><?php _e('Options', 'more-fields'); ?></a></li>
						<li><a <?php if ($tab == 'help') echo 'class="current"'; ?> href="<?php echo $url; ?>help">Help</a></li>
					</ul>
				</p>
				<?php	
				if ($tab == 'pages') include('more-fields-manage-pages.php');
				else if ($tab == 'boxes') include('more-fields-manage-boxes.php');
				else if ($tab == 'options') include('more-fields-manage-options.php');
				else if ($tab == 'help') include('more-fields-manage-help.php');
				do_action('more_fields_options_load_content', $tab);
				?>
			</div> 
		</div>
		<?php
	}

}

?>