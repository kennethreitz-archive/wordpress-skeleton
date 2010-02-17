<?php
	require_once(dirname(__FILE__).'/../../../wp-load.php');
	require_once(dirname(__FILE__).'/../../../wp-admin/admin.php');
	header("Content-Type: text/javascript");

	// Need to initalize a new instance as this is a separate instance
	$mf0 = new more_fields_object;
	$mf0->init();

//	do_action('');

	$type = attribute_escape($_GET['type']);	
	$pages = $mf0->get_pages();
	$option = $mf0->get_option('options');
	
	// If the page type does not exist, then stop!
	// if (!array_key_exists($type, $pages)) exit();
	
	
	if ($_GET['action'] == 'get_file_list') {
		$post_id = attribute_escape($_GET['post_id']);
		$attachments['data'] = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );
		$attachments['clicked'] = attribute_escape($_GET['clicked']);
		echo json_encode($attachments);
		exit();
	}
	
?>

jQuery(document).ready(function(){	
	if (jQuery("#titlediv")) {

	<?php if ($option['show_page_type_select']) : ?>
		var html = '<p><strong><label for="mf_page_type"><?php _e('Page Type', 'more-fields'); ?></label></strong></p>';
		html = html + '<p><select name="mf_page_type">';
		html = html + '<option value=\'mf_none\'><?php _e('None', 'more-fields'); ?></option>';
		<?php foreach ((array) $pages as $page) :	?>
			<?php
				if (in_array($page['name'], array('Page', 'Post'))) continue;
				// We can't convert between post and pages
				if ($pages[$type]['based_on'] != $page['based_on']) continue;
			?>
			<?php $selected = ($type == $page['name']) ? ' selected="selected"' : ''; ?>
			html = html + '<option value="<?php echo $page['name']; ?>" <?php echo $selected?>><?php echo $page['name']; ?></option>';
		<?php endforeach; ?>
		html = html + '</select>';
		jQuery("#post_status").after(html);
	<?php endif; ?>

		<?php if ( $type ) :
		
?>
		if (typeof(postboxes) != 'undefined')
			postboxes.add_postbox_toggles('<?php echo sanitize_title($type); ?>');
		
		// Show the file list box
		jQuery('.mf_file_list_show').each(function(one){
			if (!jQuery('#' + this.id.replace('mf_file_list_show_', '')).val()) {
				jQuery('#' + this.id).hide();
				jQuery('#' + this.id.replace('show', 'edit')).show();
			} else {
				jQuery('#' + this.id).show();
				jQuery('#' + this.id.replace('show', 'edit')).hide();			
			}
		});

		// Bind the file list field type
		jQuery('.file_list_update').bind('click', function(){
		
			var regexp = /edit/;
			if (regexp.test(this.id)) { 
				jQuery('#' + this.id.replace('edit_button', 'edit')).show();
				jQuery('#' + this.id.replace('edit_button', 'show')).hide();
				var clicked = this.id.replace('mf_file_list_edit_button_', '');
			} else {
				var clicked = this.id.replace('mf_file_list_update_button_', '');
			
			}
		
			var url  = '<?php echo get_option('siteurl'); ?>/wp-content/plugins/more-fields/more-fields-write-js.php?';
			url = url + 'post_id=' + jQuery('#post_ID').val() + '&action=get_file_list&clicked=' + clicked;
			jQuery.getJSON(url, function(json){
				html = '';
				jQuery.each(json['data'], function(i, file){
					html = html + '<option value="' + file['guid'] + '">' + file['post_title'] + ' (' + file['post_mime_type']  + ')</option>';				
				});
				jQuery('#' + json['clicked'] + '_temp').html(html);
			});
		});
		
		// Bind the file list select list to set the real id
		jQuery('.mf_file_list_select').bind('select', function(){
			if (this.value) jQuery('#' + this.id.replace('_temp', '')).val(this.value);
		});


		// Hide some boxes
		<?php
			$allboxes = $mf0->all_boxes($pages[$type]['based_on']);
			foreach ($allboxes as $box_id) { 
				if (!isset($pages[$type]['visible_boxes'])) continue;
				if (!in_array($box_id, (array) $pages[$type]['visible_boxes'])) {
				?>
				jQuery('#<?php echo sanitize_title($box_id); ?>').hide();
				jQuery("label[for='<?php echo sanitize_title($box_id); ?>-hide']").hide();
				<?php
				
				}
			}
		?>

			<?php if (!in_array($type, array('Post', 'Page'))) : ?>

			// Make sure correct sub-menu is current
			jQuery(".wp-has-current-submenu").removeClass('wp-has-current-submenu').removeClass('wp-menu-open');
			jQuery(".current").removeClass('current');
			jQuery("#menu-<?php echo sanitize_title($type); ?>").addClass("wp-has-current-submenu").addClass("wp-menu-open");

			// Check the right sub-sub-menu		
			jQuery("#menu-<?php echo sanitize_title($type); ?> > .wp-has-submenu").addClass("wp-has-current-submenu").addClass("wp-menu-open");

			if (jQuery("h2").filter(":contains('<?php _e('Add New'); ?>')").html()) {
				jQuery("#menu-<?php echo sanitize_title($type); ?> .wp-first-item").addClass("current");
			} else if (jQuery("h2").filter(":contains('<?php _e('Edit'); ?>')").html()) {
				jQuery("#menu-<?php echo sanitize_title($type); ?> .wp-first-item").next("li > a").addClass("current");
				jQuery(".current > a").addClass("current");
			}
			
			jQuery("h2").filter(":contains('<?php _e('Add New'); ?>')").html('<?php echo __('Add New') . ' ' . $type; ?>');
			jQuery("h2").filter(":contains('<?php _e('Edit'); ?>')").html('<?php echo __('Edit') . ' ' . $type; ?>');

		<?php endif; ?>
	
			// Fix the menu
			//jQuery("#submenu a.current").removeClass("current");
			//jQuery('#submenu a').filter(":contains('<?php echo $type; ?>')").addClass("current");
	

			<?php
				$cats = implode(',', (array) $pages[$type]['cats']);
				$cats = (array) $pages[$type]['cats'];
				if ($cats) :
			?>
				// These are the category ids for this post type

				<?php if (false) : ?>
					// Uncheck all the cats (strict enforcement)
					jQuery("input[name*='post_category']").each(function(i){
						var id = this.id.replace('in-category');			
						jQuery('#in-category-' + id).attr({checked: ''});
						jQuery('#in-popular-category-' + id).attr({checked: ''});					
					});
				<?php endif; ?>
				
				<?php foreach($cats as $cat) : ?>				
				jQuery("#in-category-<?php echo $cat; ?>").attr({checked: 'checked'});
				jQuery("#in-popular-category-<?php echo $cat; ?>").attr({checked: 'checked'});				
				<?php endforeach; ?>
				
			<?php endif; ?>

			// Add tags if there are none.
			<?php if ($tags = $pages[$type]['tags']) : ?>						
				var tags = jQuery("#tags-input").val();
				if (tags) {
					if (!tags.length) {
						if (jQuery("#newtag")) {
							jQuery("#newtag").val("<?php echo $tags; ?>");	
							tag_flush_to_text();
						} else {
							jQuery("#tags-input").val("<?php echo $tags; ?>, " + tags);
						}
					}	
				}
			<?php endif; ?>
	
		<?php endif; ?>
	}		
});

