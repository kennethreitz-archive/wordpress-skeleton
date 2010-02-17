<?php
global $mf0, $mfo;

if ($action = attribute_escape($_POST['action'])) {

	switch ($action) {
	
		case 'save' :

			$options = get_option('more_fields_options');

			$options['enable_pages'] = ($_POST['enable_pages'] == 'on') ? true : false;
			
			$slug = sanitize_title(attribute_escape($_POST['slugbase']));

//			if (!ereg('^\/', $slug)) $slug = '/' . $slug;

			$options['slugbase'] = $slug; // sanitize_title(attribute_escape($_POST['slugbase']));
			$options['remove_link'] = sanitize_title(attribute_escape($_POST['remove_link']));
			$options['remove_page'] = sanitize_title(attribute_escape($_POST['remove_page']));
			$options['remove_post'] = sanitize_title(attribute_escape($_POST['remove_post']));
			$options['show_page_type_select'] = sanitize_title(attribute_escape($_POST['show_page_type_select']));

			update_option('more_fields_options', $options);

			$mfo->condition(0, __('The options were saved.', 'more-fields'));

			break;
		
		case 'reset';

			$options = array();
			update_option('more_fields_options', $options);
			update_option('more_fields_pages', $options);
			update_option('more_fields_boxes', $options);
			
			$mfo->condition(0, __('More-Fields was reset. All boxes and pages were removed.', 'more-fields'));

			break;
	}
}


$options = $mf0->get_option('options');

	?>
	<form method="post"  action="options-general.php?page=more-fields&tab=options">
<!--
	<h2><?php _e('General options', 'more-fields'); ?></h2>

	<table class="form-table">
	
 		<tr>
 			<th scope="row" valign="top"><?php _e('Enable Pages', 'more-fields'); ?></th>
 			<td>
 				<label>
	 				<input type="checkbox" <?php if ($options['enable_pages']) echo ' checked="checked"'; ?> name="enable_pages"> 
 					<?php _e('Enable the use of custom Write/Edit pages', 'more-fields'); ?>	
				</label>
			</td>
		</tr>
	</table>
-->
	
	<h2><?php _e('Write/Edit options', 'more-fields'); ?></h2>
	
	<table class="form-table">	
 		<tr>
 			<th scope="row" valign="top"><?php _e("Remove 'Posts'", 'more-fields'); ?></th>
 			<td>
 				<label>
 					<input type="checkbox" <?php if ($options['remove_post']) echo ' checked="checked"'; ?> name="remove_post"> 
 					<?php _e("Remove 'Posts' under the Write and Manage menus", 'more-fields'); ?>	
				</label>
			</td>
		</tr>			

 		<tr>
 			<th scope="row" valign="top"><?php _e("Remove 'Pages'", 'more-fields'); ?></th>
 			<td>
 				<label>
 					<input type="checkbox" <?php if ($options['remove_page']) echo ' checked="checked"'; ?> name="remove_page"> 
 					<?php _e("Remove 'Pages' under the Write and Manage menus", 'more-fields'); ?>	
				</label>
			</td>
		</tr>			

		
 		<tr>
 			<th scope="row" valign="top"><?php _e("Remove 'Links'", 'more-fields'); ?></th>
 			<td>
 				<label>
 					<input type="checkbox" <?php if ($options['remove_link']) echo ' checked="checked"'; ?> name="remove_link"> 
 					<?php _e("Remove 'Links' under the Write and Manage menus", 'more-fields'); ?>	
				</label>
			</td>
		</tr>
 		<tr>
 			<th scope="row" valign="top"><?php _e("Show type select", 'more-fields'); ?></th>
 			<td>
 				<label>
 					<input type="checkbox" <?php if ($options['show_page_type_select']) echo ' checked="checked"'; ?> name="show_page_type_select"> 
 					<?php _e("Show page type selector in the Save/Publish box. This appears under 'Status' in the 'Publish box'.", 'more-fields'); ?>	
				</label>
			</td>
		</tr>	
	</table>

<!--
	<h2><?php _e('Slug rules', 'more-fields'); ?></h2>

	<table class="form-table">			
 		<tr>
 			<th scope="row" valign="top"><?php _e('Slug base', 'more-fields'); ?></th>
 			<td>
				<input type="text" name="slugbase" value="<?php echo $options['slugbase']; ?>">
 				<?php _e('URL base for Custom Fields', 'more-fields'); ?>	
 			</td>
 		</tr>
	</table>
-->

	</table>
	<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
	<input type="hidden" name="action" value="save">
	<input type="hidden" name="type" value="post">
	<p><input class="button-primary" type="submit" value="<?php _e('Save options', 'more-fields'); ?>"></p>
	</form>

	<hr>

	<form method="POST"  action="options-general.php?page=more-fields&tab=options&action=reset">
		<p>
			<?php _e('This will delete all boxes, pages and options. Be warned.', 'more-fields'); ?>
			<input class="button resetall" type="submit" value="<?php _e('Reset More-Fields', 'more-fields'); ?>">
		</p>
		<input type="hidden" name="action" value="reset">
	</form>	
	