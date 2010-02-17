<?php
global $mf0, $mfo;
$action = attribute_escape($_GET['action']);

switch ($action) {
	
	case 'delete_post_type':

		$ok = $mfo->condition(($id = attribute_escape($_GET['id'])), __('Cannot delete this post type', 'more-fields'));
		$pages = $mf0->get_pages();
		if ($ok) {
			unset($pages[$id]);
			update_option('more_fields_pages', $pages);
			$action = '';
		}
		break;
			
}


switch (attribute_escape($_POST['post_action'])) {

	case 'save_post_type':

		$ok = $mfo->condition(($name = attribute_escape($_POST['name'])), __('You must provide a name for the post type!', 'more-fields'));
		$pages = $mf0->get_pages();
		
		// If this field is new, make sure the name is not taken
		if (!($id = attribute_escape($_POST['id']))) {
			$ok = $mfo->condition(empty($pages[$name]), __('That name already exists!', 'more-fields'));
		} else {
			// Handle a change of post type name
			if ($id != $name) {
				$ok = $mfo->condition((empty($pages[$name])), __('That name already exists!', 'more-fields'));
				$pages[$name] = $pages[id];
				unset($pages[$id]);
			}
		}
		

		if ($ok) {

			// If the post type has changed remove the old one and change all the posts with mf_page_type
			$fields = array('name', 'category', 'based_on', 'template',
							'post_parent', 'tags', 'icon', 'icon_hover', 'plural');

			foreach ($fields as $field) {
				$pages[$name][$field] = attribute_escape($_POST[$field]);		
			}

			$pages[$name]['cats'] = $_POST['post_category']; 
			$pages[$name]['user_level'] = $_POST['user_level'];

			// Can't attribute_escape $_POST arrays - thanks Tobias
			if (!$_POST['visible_boxes']) $pages[$name]['visible_boxes'] = array();
			else $pages[$name]['visible_boxes'] = $_POST['visible_boxes'];

			// Checkboxes
			$a = array('_category_dropdown', '_advanced_options', '_remove_related');
			foreach ($a as $b) $pages[$name][$b] = ($_POST[$b]) ? true : false;

			update_option('more_fields_pages', $pages);			

		} else $action = 'edit_post_type';

		break;
	

}

?>

<?php if (!$action) : ?>

<?php	
	$pages = $mf0->get_pages();
//	$more_fields = $mf0->get_boxes();

//	$b = 0; foreach ($pages['Post']['boxes']) as $a) $b += a; echo ($a) ? $a : '-';

?>
			<p><?php _e('These are the Write/Edit pages, you can edit which boxes you wish to appear on each, and create new ones.', 'more-fields'); ?></p>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e('Post type', 'more-fields'); ?></th>
						<th><?php _e('Based on', 'more-fields'); ?></th>
						<th><?php _e('Actions', 'more-fields'); ?></th>
					</tr>
				</thead>
				<tr class="alternate">
					<td><a href="options-general.php?page=more-fields.php&action=edit_post_type&id=Post&tab=pages">Post</a></td>
					<td><?php _e('Post'); ?></td>
					<td><a href="options-general.php?page=more-fields.php&tab=pages&action=edit_post_type&id=Post">Edit</a></td>
				</tr>
				<tr>
					<td><a href="options-general.php?page=more-fields.php&action=edit_post_type&id=Page&tab=pages">Page</a></td>
					<td><?php _e('Page'); ?></td>
					<td><a href="options-general.php?page=more-fields.php&tab=pages&action=edit_post_type&id=Page">Edit</a></td>
				</tr>
				<?php
					//$mf_pages = mf_get_pages();
					$nbr = 0;
					foreach ($pages as $page) :
						if ($page['name'] == 'Post' || $page['name'] == 'Page') continue;
						$class = ($nbr++ % 2) ? '' : 'alternate' ;
				?>
				<tr class="<?php echo $class; ?>">
					<td><a href="options-general.php?page=more-fields.php&tab=pages&action=edit_post_type&id=<?php echo urlencode($page['name']); ?>"><?php echo $page['name']; ?></a></td>
					<td><?php _e(ucwords($pages[$page['name']]['based_on'])); ?></td>
					<td><a href="options-general.php?page=more-fields.php&tab=pages&action=edit_post_type&id=<?php echo urlencode($page['name']); ?>"><?php _e('Edit', 'more-fields'); ?></a> | <a class="delete_me" href="options-general.php?page=more-fields.php&tab=pages&action=delete_post_type&id=<?php echo urlencode($page['name']); ?>"><?php _e('Delete', 'more-fields'); ?></a></td>
				</tr>		
				<?php endforeach; ?>
			</table>
			<form method="GET" ACTION="options-general.php">
			<input type="hidden" name="tab" value="pages">			
			<input type="hidden" name="page" value="more-fields.php">
			<input type="hidden" name="action" value="edit_post_type">
			<p><input class="button-primary" type="submit" value="<?php _e('Add new post type' ,'more-fields'); ?>!"></p>
			</form>
	
<?php elseif ($action == 'edit_post_type') : ?>
<?php

	$id = ($_GET['id']) ? $_GET['id'] : $_POST['name'];
	$pages = $mf0->get_pages($id);

	// Default is post
	if (!$pages[$id]['based_on']) $pages[$id]['based_on'] = 'post';
echo $id;
	?>
	<form method="post"  action="options-general.php?page=more-fields&tab=pages&id=<?php echo $id; ?>">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Post type', 'more-fields'); ?></th>
 				<td>
					<?php
 						if (in_array($id, array('Post', 'Page'))) { 
 							echo $id;
							$type = 'hidden';
 						}
 						if (!$type) $type = 'text';
 						echo '<input type="' . $type . '" name="name" value="' . $id . '">';
					?>
 				</td>
 			</tr>
 			<tr>
 				<th scope="row" valign="top"><?php _e('Post type plural', 'more-fields'); ?></th>
		 		<td>
					<input type="text" name="plural" value="<?php echo $pages[$id]['plural']; ?>">
 				</td>
 			</tr>
 			<tr>
 				<th scope="row" valign="top"><?php _e('Based on', 'more-fields'); ?></th>
 				<td>
					<?php if (!in_array($id, array('Post', 'Page'))) : ?>
 					<select name="based_on">
						<option value='post' <?php if ($pages[$id]['based_on'] == 'post') echo 'selected="selected"'; ?>>Post</option>
						<option value='page' <?php if ($pages[$id]['based_on'] == 'page') echo 'selected="selected"'; ?>>Page</option>
 					</select> <?php _e('If you change this, save before proceeding further', 'more-fields'); ?>
					<?php else : ?>
						<?php echo $id; ?>
						<input type="hidden" name="based_on" value="<?php echo strtolower($id); ?>">
 					<?php endif; ?>
 					
				</td>
 			</tr>
 			<tr>
 				<th scope="row" valign="top"><?php _e('Template', 'more-fields'); ?></th>
 				<td>
					<select name="template">
					<option value='default' <?php if ($pages[$id]['template'] == 'default') echo ' selected="selected"'; ?>><?php _e('Default Template'); ?></option>
					<?php page_template_dropdown($pages[$id]['template']); ?>
					</select>			
 				</td>
 			</tr>
 
 		<?php if ($pages[$id]['based_on'] == 'page') : ?>
 			<tr>
 				<th scope="row" valign="top"><?php _e('Post parent', 'more-fields'); ?></th>
 				<td>
					<select name="post_parent">
					<option value='0' <?php if ($pages[$id]['post_parent'] == '0') echo ' selected="selected"'; ?>><?php _e('Main Page (no parent)'); ?></option>
					<?php parent_dropdown($pages[$id]['post_parent']); ?>
					</select>			
 				</td>
 			</tr>
		<?php endif; ?>
		
		<?php if ($pages[$id]['based_on'] == 'post') : ?>
 			<tr>
 				<th scope="row" valign="top"><?php _e('Default tags', 'more-fields'); ?></th>
 				<td>
					<input type="text" name="tags" value="<?php echo $pages[$id]['tags']; ?>"> <?php _e('Comma separated', 'more-fields'); ?>
 				</td>
 			</tr>
 		<?php endif; ?>	
 			
 			<tr>
 				<th scope="row" valign="top"><?php _e('Menu icon url', 'more-fields'); ?></th>
 				<td>
					<input type="text" name="icon" value="<?php echo $pages[$id]['icon']; ?>"> <?php _e('Normal', 'more-fields'); ?><br>
					<input type="text" name="icon_hover" value="<?php echo $pages[$id]['icon_hover']; ?>"> <?php _e('Hover', 'more-fields'); ?>
 				</td>
 			</tr>	

			<?php if ($pages[$id]['based_on'] == 'post') : ?>
			<tr>
 				<th scope="row" valign="top"><?php _e('Default category', 'more-fields'); ?></th>
 				<td>
 					<?php $args = array( 'hide_empty' => 0, 'name' => 'category', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Select category', 'more-fields'), 'tab_index' => 3 , 'selected' => $mf_pages[$id]['category']); ?>
				<div id="categorydiv">
	 				<ul class="categorychecklist">
	 				<?php
//	 					$cats = get_categories('get=all'); 
//						$selected = array();
//						foreach ($cats as $cat) 
//							if ($mf_pages[$id]['cats'][$cat->cat_ID]) array_push($selected, $cat['cat_ID']);
						 wp_category_checklist('', '', $pages[$id]['cats']); ?>
					</ul>					
				</div>


<?php 


?>
					<a href="categories.php"><?php _e('Edit categories', 'more-fields'); ?></a>
<?php /*
					<p><label><?php echo $mfo->checkbox('_category_dropdown', $pages[$id], false); ?> <?php _e('Add drop down list for child-categories?', 'more-fields'); ?></label></p>
?*/ ?>
 				</td>
 			</tr>
 		<?php endif; ?> 				
 			<tr>
 				<th scope="row" valign="top"><?php _e('User access', 'more-fields'); ?></th>
 				<td>
					<?php _e('Check the user levels that can acces this post type', 'more-fields'); ?><br />
 					<?php
	 					global $wp_roles;
						$ul = $pages[$id]['user_level'];
						foreach($wp_roles->roles as $role) : 
							$name = str_replace('|User role', '', $role['name']);
							$value = sanitize_title($name); 
							$checked = (in_array($value, (array) $pages[$id]['user_level'])) ? ' checked="checked"' : '';
							?>
							<label><input type="checkbox" name="user_level[]" value="<?php echo $value; ?>" <?php echo $checked; ?>> <?php echo $name; ?></label><br />
					<?php endforeach; ?>
 				</td>
 			</tr>	
			<tr>
 				<th scope="row" valign="top"><?php _e('Boxes', 'more-fields'); ?></th>
 				<td>
 					<?php _e('These sets the global availability of boxes for each post-type. Individual users can toggle visibility of these on the Write/Edit pages by clicking the <em>Screen Options</em> tab in the upper right corner.', 'more-fields'); ?><br>
 					<?php
 						// Default boxes
 						$boxes = $mfo->divs;
 						$based_on = ($a = $pages[$id]['based_on']) ? strtolower($a) : 'post';
 						foreach ($boxes[$based_on] as $box) {
 							$value = $box[1];
 							$checked = (in_array($value, (array) $pages[$id]['visible_boxes'])) ? " checked='checked'" : '';
							if (!isset($pages[$id]['visible_boxes'])) $checked = " checked='checked'";
 							$checkbox =  "<input type='checkbox' name='visible_boxes[]' value='$value'$checked />\n";
							echo '<label>' . $checkbox . ' ' . $box[0] . "</label><br />\n"; 
 						}

 						// Boxes created within More Fields
 						$more_fields = $mf0->get_boxes();
						foreach ($more_fields as $box) {
							$value = attribute_escape($box['name']);
 							$checked = (in_array($value, (array) $pages[$id]['visible_boxes'])) ? " checked='checked'" : '';							
							if (!isset($pages[$id]['visible_boxes'])) $checked = " checked='checked'";
							$checkbox =  "<input type='checkbox' name='visible_boxes[]' value='$value'$checked />\n";
							echo '<label>' . $checkbox . ' <strong>' . $box['name'] . "</strong></label><br />\n"; 
						}

						// The other boxes
						global $wp_meta_boxes;
						$data = $wp_meta_boxes[$pages[$id]['based_on']];
						foreach ((array) $data as $context) {
							foreach ((array) $context as $priority) {
								foreach ((array) $priority as $box) {
									if (array_key_exists($box['title'], $more_fields)) continue;
									if ($box['title']) {
										$value = attribute_escape($box['id']);
 										$checked = (in_array($value, (array) $pages[$id]['visible_boxes'])) ? " checked='checked'" : '';							
										if (!isset($pages[$id]['visible_boxes'])) $checked = " checked='checked'";
										$checkbox =  "<input type='checkbox' name='visible_boxes[]' value='$value'$checked />\n";
										echo '<label>' . $checkbox . ' <em>' . $box['title'] . "</em></label><br />\n"; 
									}
								}
							}						
						}

						?>
						<p>												
							<strong><?php _e('bold', 'more-fields'); ?></strong> - <?php _e('More Fields box', 'more-fields'); ?><br />
							<em><?php _e('italic', 'more-fields'); ?></em> - <?php _e('Box added by <a href="plugins.php">another plugin</a>.', 'more-fields'); ?><br />
						</p>
					</td>
 				</tr>			
			</table>
			<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
			<input type="hidden" name="post_action" value="save_post_type">
			<input type="hidden" name="type" value="post">
			<p><input class="button-primary" type="submit" value="Save post type"></p>
	</form>


<?php endif; ?>