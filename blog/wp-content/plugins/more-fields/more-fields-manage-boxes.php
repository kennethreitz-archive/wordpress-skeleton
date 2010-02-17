<?php

	global $mf0, $mfo;
	$action = attribute_escape($_GET['action']);
	$pages = $mf0->get_pages();
	$more_fields = $mf0->get_boxes();

	

	switch ($_GET['action']) {
	
	case 'delete_box' :
	
		$boxes = $mf0->get_boxes('i');

		$ok = $mfo->condition(($box = attribute_escape($_GET['box'])), __('Error! Something strange happened.', 'more-fields'));
		$ok = $mfo->condition(($name = $boxes[$box]['name']), __('Your are trying to delete a box that doesn\'t exist.', 'more-fields'));

		if ($ok) {
			unset($boxes[$box]);
			update_option('more_fields_boxes', $boxes);
			$mfo->condition(false, __('Box deleted!', 'more-fields'), 'notification');
		}
		$_GET['action'] = '';

		break;

	case 'delete_field' :
	
		$boxes = $mf0->get_boxes('i');

		$ret = $mfo->options_validate_field_input_get(__('delete', 'more-fields')); //);

		if ($ret['ok']) {
			unset($boxes[$ret['box']]['field'][$ret['index']]);
			$new = array();
			foreach ($boxes[$ret['box']]['field'] as $field )
				array_push($new, $field);
			$boxes[$ret['box']]['field'] = $new;
			update_option('more_fields_boxes', $boxes);
			$mfo->condition(false, __('Deleted!', 'more-fields'), 'notification');
		}
		$_GET['action'] = 'edit_box';

		break;
		
	case 'move_up_field':

		$mfo->options_move_field ( true );
	
		break;
	
	case 'move_down_field' :

		$mfo->options_move_field ( false );

		break;
	}
	
	switch (attribute_escape($_POST['post_action'])) {

	case 'save_box':
	
		$boxes = $mf0->get_boxes('');
	
		$ok = $mfo->condition(($name = attribute_escape($_POST['name'])), __('The box must have a name!', 'more-fields'));
		// $ok = mf_condition(($name == 'Post'), __('The box must have a name!', 'more-fields'));
		$name_old = attribute_escape($_POST['name_old']);
		if ($name != $name_old)
			$ok = $mfo->condition((!array_key_exists($name, $boxes)), __('A box with that name already exists!', 'more-fields'));
		
		if ($name_old) 
			$nochange = $mfo->condition(($name_old == $name), __('You changed the name of the box!', 'more-fields'), 'warning');
//		else $ok = mf_condition(($boxes[$name] != ''), __('The already key exists!', 'more-fields'));
					
		if ($ok) {

//			if (!is_array($boxes)) $boxes = array();

			if (!$nochange) {
				$ok = $mfo->condition(($name != 'Page'), __('The page type <strong>Page</strong> is protected!', 'more-fields'));
				$ok = $mfo->condition(($name != 'Post'), __('The page type <strong>Post</strong> is protected!', 'more-fields'));
				if ($ok) {
					$old = $boxes[$name_old];
					$boxes[$name] = $old;
					unset($boxes[$name_old]);
				}
			}
			
			if ($ok) {
				$boxes[$name]['name'] = $name;
				$boxes[$name]['position'] = attribute_escape($_POST['position']);

				update_option('more_fields_boxes', $boxes);	
			
				$mfo->condition(false, str_replace('%s', "<strong>$name</strong>", __('Box %s was saved!', 'more-fields')), 'notification');
				
				$_GET['box'] = $name;
			}

		}
		
		break;
		
		
	case 'save_field':

		$boxes  = $mf0->get_boxes('i');
		$ok = $mfo->condition(($box = attribute_escape($_POST['box'])), __('Hang on, someting strange happend!', 'more-fields'));
		$ok = $mfo->condition(($key = attribute_escape($_POST['key'])), __('You must specify a key for the field!', 'more-fields'));
// print_r($_POST);
		if ($key_old = attribute_escape($_POST['key_old']))
			$nochange = $mfo->condition(($key_old == $key), __('You changed the name of the field!', 'more-fields'), 'warning');

		foreach($boxes as $b) {
			if (!is_array($b['field'])) continue;
			foreach ($b['field'] as $field) {
				$key = (!$nochange) ? $key : $key_old;	

				// If the field is new
				if (!$key_old)
					$ok = $mfo->condition(($field['key'] != $key), __('The key your are trying to create already exists in the box called', 'more-fields')  . ' <strong>' . $b['name'] . '</strong>.');

				// If the name changed
				if (!$nochange)
					$ok = $mfo->condition(($field['key'] != $key), __('The key your are renaming to already exists', 'more-fields'));
	
			}
		}

		$v = ($nochange) ? $key : $key_old;
		$post_index = attribute_escape($_POST['index']);
		$index = ($boxes[$box]['field'][$post_index]['key'] == $v) ? $post_index : count($boxes[$box]['field']);
		if ($index == '') $index = 0;

		if (!$key_old) $index = count($boxes[$box]['field']);


		if ($ok) {

			$boxes[$box]['field'][$index]['key'] = $key;
			$boxes[$box]['field'][$index]['title'] = attribute_escape($_POST['title']);
			$boxes[$box]['field'][$index]['slug'] = $mfo->slugize(attribute_escape($_POST['slug']));
			$boxes[$box]['field'][$index]['type'] = attribute_escape($_POST['type']);
			$boxes[$box]['field'][$index]['select_values'] = attribute_escape($_POST['select_values']);

			update_option('more_fields_boxes', $boxes);
			
			// Generate the rewrite rules for this field
			$mf0->rewrite_rules();
			$mf0->flush_rewrite_rules();
			
			$mfo->condition(false, __('Field was saved!', 'more-fields'), 'notification');
		}
	
		break;

	case 'delete_box':

		$boxes = get_option('more_fields_boxes');
		if (!is_array($boxes)) $boxes = array();

		$ok = $mfo->condition(($box = attribute_escape($_GET['box'])), __('Error! Something strange happened.', 'more-fields'));
		$ok = $mfo->condition(($name = $boxes[$box]['name']), __('Your are trying to delete a box that doesn\'t exist', 'more-fields'));

		if ($ok) {
			unset($boxes[$box]);
			update_option('more_fields_boxes', $boxes);
		}

		break;
		
	case 'delete_field' :
	
		$boxes = get_option('more_fields_boxes');
		if (!is_array($boxes)) $boxes = array();

		$ok = $mfo->condition(($box = $_POST['box']), __('Error! Something strange happened.', 'more-fields'));
		$ok = $mfo->condition(($field = $_POST['field']), __('Error! Something strange happened.', 'more-fields'));
		$ok = $mfo->condition((in_array($field, $more_fields[$box]['fields'])), __('Your are trying to delete a field that doesn\'t exist', 'more-fields'));

		if ($ok) {
		
			$id = array_search($field, $more_fields[$box]['field']);
			unset($boxes[$box]['field'][$id]);
			update_option('more_fields_boxes', $boxes);
			
		}
		
		break;
		
	case 'edit_field' :

		$boxes = get_option('more_fields_boxes');
		if (!is_array($boxes)) $boxes = array();

		$ok = $mfo->condition(($box = $_POST['box']), __('Error! Something strange happened.', 'more-fields'));
		$ok = $mfo->condition(($field = $_POST['field']), __('Error! Something strange happened.', 'more-fields'));
		$ok = $mfo->condition((in_array($field, $more_fields[$box]['fields'])), __('Your are trying to delete a field that doesn\'t exist', 'more-fields'));
	
		if ($ok) {
		
		
		
		}

	}
	
	$action = attribute_escape($_GET['action']);
	if (!$action) :
?>

	<p><?php _e('Listed below are the boxes that were created in More Fields. These boxes appear on the Write/Edit page. If a box cannot be edited, it was created programatically. Click on the name of a box to edit and add fields', 'more-fields'); ?></p>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php _e('Box name', 'more-fields'); ?></th>
				<th><?php _e('Number of fields', 'more-fields'); ?></th>
				<th><?php _e('Position', 'more-fields'); ?></th>
				<th><?php _e('Actions', 'more-fields'); ?></th>
			</tr>
		</thead>
		<?php if (empty($more_fields)) { ?>
			<tr>
				<td colspan="4" class="alternate">There are no boxes defined - set some up, it is easy!</td>
			</tr>
<?php
		}
		$fnbr = 1;
		foreach ($more_fields as $field) {
			$position = ($field['position'] == 'right') ? __('Right', 'more-fields') : __('Left', 'more-fields');
			$edit_link = 'options-general.php?page=more-fields.php&tab=boxes&action=edit_box&box=' . urlencode($field['name']);
			$delete_link = 'options-general.php?page=more-fields.php&tab=boxes&action=delete_box&box=' . urlencode($field['name']);

			// Was this box programatically added?
			$boxes = get_option('more_fields_boxes');
			$editable = (!empty($boxes[$field['name']])) ? true : false;
?>
			<tr<?php if ($fnbr++ % 2) echo ' class="alternate"'; ?>>
				<td>
					<?php if ($editable) { ?>
						<a href="<?php echo wp_nonce_url($edit_link, 'mf_edit_box'); ?>">
					<?php } ?>
						<?php echo $field['name'] ?>
					<?php if ($editable) { ?>
						</a>
					<?php } ?>
				</td>
				<td><?php echo count($field['field']); ?></td>
				<td><?php echo $position; ?></td>
				<?php if ($editable) { ?>
					<td><a href="<?php echo $edit_link; ?>"><?php _e('Edit', 'more-fields'); ?></a> | <a class="delete_me" href="<?php echo wp_nonce_url($delete_link, 'mf_delete_box'); ?>"><?php _e('Delete', 'more-fields'); ?></a></td>
				<?php } else { ?>
					<td>-</td>
				<?php } ?>
			</tr>

<?php
		}
?>
			</table>
			
			<form method="GET" ACTION="options-general.php?page=more-fields.php&action=edit_box">
			<input type="hidden" name="page" value="more-fields.php">
			<input type="hidden" name="action" value="edit_box">
			<p><input class="button-primary" type="submit" value="<?php _e('Add new box' ,'more-fields'); ?>!"></p>
	</form>


<?php
	elseif ($action == 'edit_box') :

	$box = ($b = $_GET['box']) ? $b : $_POST['name'];
	$more_fields = $mf0->get_boxes('i');
	$field = $more_fields[$box];
	$nav = ($a = $field['name']) ? $a : __('New box', 'more-fields');
?>
		<form method="post" action="options-general.php?page=more-fields.php&action=edit_box&tab=boxes">
		<h4><a href="options-general.php?page=more-fields.php">More Fields</a> &gt; <?php echo $nav; ?></h4>

		<table class="form-table">
			 <tr>
 				<th scope="row" valign="top">Box title</th>
 				<td>
 					<input type="text" name="name" value="<?php echo $field['name']; ?>">
 					<input type="hidden" name="name_old" value="<?php echo $field['name']; ?>">
 				</td>
 			</tr>
			 <tr>
 				<th scope="row" valign="top">Position</th>
 				<td>
					<select name="position"><option value="right" <?php if ($field['position'] == 'right') echo 'selected="selected"'; ?>><?php _e('Right', 'more-fields'); ?></option><option value="left" <?php if ($field['position'] == 'left') echo 'selected="selected"'; ?>><?php _e('Left', 'more-fields'); ?></option></select>
 				</td>
 			</tr>
		</table>

			<br />
			<input type="hidden" name="post_action" value="save_box">
			<input type="hidden" name="">
			<p><input class="button-primary" type="submit" value="<?php _e('Save'); ?>"></p>
		<?php if ($field['name']) : ?>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e('Key' ,'more-fields'); ?></th>
						<th><?php _e('Title' ,'more-fields'); ?></th>
						<th><?php _e('Type' ,'more-fields'); ?></th>
						<th><?php _e('Values' ,'more-fields'); ?></th>
						<th><?php _e('Actions' ,'more-fields'); ?></th>
					</tr>
				</thead>
				<?php 
				$fnbr = 0;
				if (!is_array($field['field'])) $field['field'] = array();
//				foreach($field['field'] as $f) : 
				for ($index = 0; $index < count($field['field']); $index++) {				
					$f = $field['field'][$index];

					foreach ((array) $mf0->field_types as $f0)
						if (sanitize_title($f0->title) == $f['type']) 
							$type = $f0->title;					

					$box = urlencode(attribute_escape($_GET['box']));
					$edit_link = "options-general.php?page=more-fields.php&action=edit_field&tab=boxes&box=" . ($box) . "&field=$fnbr&key=";
					$edit_link .= urlencode($f['key']);
					$delete_link = str_replace('edit_', 'delete_', $edit_link);
					$move_up_link = str_replace('edit_', 'move_up_', $edit_link);
					$move_down_link = str_replace('edit_', 'move_down_', $edit_link);

				?>
					<tr<?php if (($fnbr++ - 1) % 2) echo ' class="alternate"'; ?>>
						<td><a href="<?php echo wp_nonce_url($edit_link, 'mf_edit_field'); ?>"><?php echo $f['key']; ?></a></td>
						<td><?php echo $f['title']; ?></td>
						<td><?php echo $type; ?></td>
						<td><?php echo stripslashes($f['select_values']); ?></td>
						<td>
							<a href="<?php echo wp_nonce_url($edit_link, 'mf_edit_field'); ?>">Edit</a> | 
							<a class="delete_me" href="<?php echo wp_nonce_url($delete_link, 'mf_delete_field'); ?>">Delete</a>
							<?php if ($index) { ?>
								| <a href="<?php echo wp_nonce_url($move_up_link, 'mf_move_up_field'); ?>">&uarr;</a>
							<?php } ?>
							<?php if ($index < count($field['field']) - 1) { ?>
								| <a href="<?php echo wp_nonce_url($move_down_link, 'mf_move_down_field'); ?>">&darr;</a>
							<?php } ?>
						</td>
					</tr>				
				<?php 
				} 
				if (empty($field['field'])) echo '<tr class="alternate"><td colspan="5">' . __('There are no fields in this box.', 'more-fields') . '</td></tr>';
				?>
				
			</table>
			</form>
				<?php $b = ($b = $_GET['box']) ? $b : $_POST[name]; ?>
				<form method="GET" ACTION="options-general.php">
				<input type="hidden" name="page" value="more-fields.php">
				<input type="hidden" name="action" value="edit_field">
				<input type="hidden" name="box" value="<?php echo htmlspecialchars(urldecode($b)); ?>">
				<p><input class="button" type="submit" value="<?php _e('Add new field' ,'more-fields'); ?>!"></p>
				</form>
		<?php endif; ?>
		
		
		
<?php elseif ($action == 'edit_field') : ?>
<?php
	$boxes = $mf0->get_boxes('i');
	$key = attribute_escape($_GET['key']);
	$box = attribute_escape($_GET['box']);
	$index = attribute_escape($_GET['field']);
	if ($boxes[$box]['field'][$index]['key'] == $key) $field = $boxes[$box]['field'][$index];
	else $field = array();
	$nav = ($a = $field['title']) ? $a : __('New Field', 'more-fields');

	?>
	<form method="post" action="options-general.php?page=more-fields&action=edit_box&box=<?php echo urlencode($_GET['box']); ?>">
		<h4><a href="options-general.php?page=more-fields.php"><?php _e('Boxes', 'more-fields'); ?></a> &gt; <a href="options-general.php?page=more-fields&action=edit_box&box=<?php echo urlencode($_GET['box']); ?>"><?php echo $_GET['box']; ?></a> &gt; <?php echo $nav; ?></h4>
		<table class="form-table">
				<tr>
 					<th scope="row" valign="top"><?php _e('Key', 'more-fields'); ?></th>
 					<td>
 						<input type="text" name="key" value="<?php echo $field['key']; ?>">
 						<?php _e('The key that is used to access the value of this field', 'more-fields'); ?>
 					</td>
 				</tr>
				<tr>
 					<th scope="row" valign="top"><?php _e('Title', 'more-fields'); ?></th>
 					<td>
 						<input type="text" name="title" value="<?php echo $field['title']; ?>">
 						<?php _e('The title of the field as it appears on the Write/Edit pages', 'more-fields'); ?>
 					</td>
 				</tr>
 				<tr>
 					<th scope="row" valign="top"><?php _e('Slug', 'more-fields'); ?></th>
 					<td>
 						<input type="text" name="slug" value="<?php echo $field['slug']; ?>">
 						<?php _e('URL path for listing based on this field, e.g. \'/baseurl/fieldname/value\'', 'more-fields'); ?>
 					</td>
 				</tr>
				 <tr>
 					<th scope="row" valign="top"><?php _e('Type', 'more-fields'); ?></th>
 					<td>
 						<?php // print_r($mf0); ?>
						<select name="type" id="type">
							<?php foreach ($mf0->field_types as $type) : ?>
								<?php $selected = ($field['type'] == sanitize_title($type->title)) ? ' selected="selected"' : ''; ?>
								<option value='<?php echo sanitize_title($type->title); ?>' <?php echo $selected; ?>><?php echo $type->title; ?></option>
							<?php endforeach; ?>
						</select>
 					</td>
 				</tr>
				 <tr id="values_container">
 					<th scope="row" valign="top"><?php _e('Values', 'more-fields'); ?></th>
 					<td>
						<textarea name="select_values"><?php echo stripslashes($field['select_values']); ?></textarea>
						<br><em>Separate values with comma (,). Preceed default value by a *. E.g: red, *green, blue
 					</td>
 				</tr>
		</table>
		<input type="hidden" name="box" value="<?php echo $_GET['box']; ?>">
		<input type="hidden" name="index" value="<?php echo $_GET['field']; ?>">
		<input type="hidden" name="key_old" value="<?php echo $field['key']; ?>">
		<input type="hidden" name="post_action" value="save_field">
		<p><input class="button-primary" type="submit" value="<?php _e('Save', 'more-fields'); ?>"></p>

	</form>

<?php endif; ?>