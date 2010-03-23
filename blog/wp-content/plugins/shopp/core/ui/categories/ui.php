<?php
function save_meta_box ($Category) {
	global $Shopp;
	
	$workflows = array(
		"continue" => __('Continue Editing','Shopp'),
		"close" => __('Category Manager','Shopp'),
		"new" => __('New Category','Shopp'),
		"next" => __('Edit Next','Shopp'),
		"previous" => __('Edit Previous','Shopp')
		);
	
?>
	<div id="major-publishing-actions">
		<select name="settings[workflow]" id="workflow">
		<?php echo menuoptions($workflows,$Shopp->Settings->get('workflow'),true); ?>
		</select>
		<input type="submit" class="button-primary" name="save" value="<?php _e('Save Category','Shopp'); ?>" />
	</div>
<?php
}
add_meta_box('save-category', __('Save','Shopp'), 'save_meta_box', 'admin_page_shopp-categories-edit', 'side', 'core');

function settings_meta_box ($Category) {
	global $Shopp;
	$categories_menu = $Shopp->Flow->category_menu($Category->parent,$Category->id);
	$categories_menu = '<option value="0" rel="-1,-1">'.__('Parent Category','Shopp').'&hellip;</option>'.$categories_menu;
?>
	<p><select name="parent" id="category_parent"><?php echo $categories_menu; ?></select><br /> 
<?php _e('Categories, unlike tags, can be or have nested sub-categories.','Shopp'); ?></p>

	<p><input type="hidden" name="spectemplate" value="off" /><input type="checkbox" name="spectemplate" value="on" id="spectemplates-setting" tabindex="11" <?php if ($Category->spectemplate == "on") echo ' checked="checked"'?> /><label for="spectemplates-setting"> <?php _e('Product Details Template','Shopp'); ?></label><br /><?php _e('Predefined details for products created in this category','Shopp'); ?></p>
	<p id="facetedmenus-setting"><input type="hidden" name="facetedmenus" value="off" /><input type="checkbox" name="facetedmenus" value="on" id="faceted-setting" tabindex="12" <?php if ($Category->facetedmenus == "on") echo ' checked="checked"'?> /><label for="faceted-setting"> <?php _e('Faceted Menus','Shopp'); ?></label><br /><?php _e('Build drill-down filter menus based on the details template of this category','Shopp'); ?></p>
	<p><input type="hidden" name="variations" value="off" /><input type="checkbox" name="variations" value="on" id="variations-setting" tabindex="13"<?php if ($Category->variations == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php _e('Variations','Shopp'); ?></label><br /><?php _e('Predefined selectable product options for products created in this category','Shopp'); ?></p>
	<?php
}
add_meta_box('category-settings', __('Settings','Shopp'), 'settings_meta_box', 'admin_page_shopp-categories-edit', 'side', 'core');

function images_meta_box ($Category) {
	$db =& DB::get();
	$Images = array();
	if (!empty($Category->id)) {
		$asset_table = DatabaseObject::tablename(Asset::$table);
		$Images = $db->query("SELECT id,src,properties FROM $asset_table WHERE context='category' AND parent=$Category->id AND datatype='thumbnail' ORDER BY sortorder",AS_ARRAY);
	}
?>
	<ul id="lightbox">
		<?php foreach ($Images as $i => $thumbnail): $thumbnail->properties = unserialize($thumbnail->properties); ?>
			<li id="image-<?php echo $thumbnail->src; ?>"><input type="hidden" name="images[]" value="<?php echo $thumbnail->src; ?>" />
				<div id="image-<?php echo $thumbnail->src; ?>-details">
				<img src="?shopp_image=<?php echo $thumbnail->id; ?>" width="96" height="96" />
					<div class="details">
						<input type="hidden" name="imagedetails[<?php echo $i; ?>][id]" value="<?php echo $thumbnail->id; ?>" />
						<p><label>Title: </label><input type="text" name="imagedetails[<?php echo $i; ?>][title]" value="<?php echo $thumbnail->properties['title']; ?>" /></p>
						<p><label>Alt: </label><input type="text" name="imagedetails[<?php echo $i; ?>][alt]" value="<?php echo $thumbnail->properties['alt']; ?>" /></p>
						<p class="submit"><input type="button" name="close" value="Close" class="button close" /></p>
					</div>
				</div>
				<button type="button" name="deleteImage" value="<?php echo $thumbnail->src; ?>" title="Delete category image&hellip;" class="deleteButton"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/delete.png" alt="-" width="16" height="16" /></button></li>
		<?php endforeach; ?>
	</ul>
	<div class="clear"></div>
	<input type="hidden" name="category" value="<?php echo $_GET['id']; ?>" id="image-category-id" />
	<input type="hidden" name="deleteImages" id="deleteImages" value="" />
	<div id="swf-uploader-button"></div>
	<div id="swf-uploader">
	<button type="button" class="button-secondary" name="add-image" id="add-image" tabindex="10"><small><?php _e('Add New Image','Shopp'); ?></small></button></div>
	<div id="browser-uploader">
		<button type="button" name="image_upload" id="image-upload" class="button-secondary"><small><?php _e('Add New Image','Shopp'); ?></small></button><br class="clear"/>
	</div>
	<p><?php _e('The first image will be the default image. These thumbnails are out of proportion, but will be correctly sized for shoppers.','Shopp'); ?></p>
<?php
}
add_meta_box('product-images', __('Category Images','Shopp'), 'images_meta_box', 'admin_page_shopp-categories-edit', 'normal', 'core');

function templates_meta_box ($Category) {
	$pricerange_menu = array(
		"disabled" => __('Price ranges disabled','Shopp'),
		"auto" => __('Build price ranges automatically','Shopp'),
		"custom" => __('Use custom price ranges','Shopp'),
	);

?>
<p><?php _e('Setup template values that will be copied into new products that are created and assigned this category.','Shopp'); ?></p>
<div id="templates"></div>

<div id="details-template" class="panel">
	<div class="pricing-label">
		<label><?php _e('Product Details','Shopp'); ?></label>
	</div>
	<div class="pricing-ui">

	<ul class="details multipane">
		<li><input type="hidden" name="deletedSpecs" id="deletedSpecs" value="" />
			<div id="details-menu" class="multiple-select options">
				<ul></ul>
			</div>
			<div class="controls">
			<button type="button" id="addDetail" class="button-secondary"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /><small> <?php _e('Add Detail','Shopp'); ?></small></button>
			</div>
		</li>
		<li id="details-facetedmenu">
			<div id="details-list" class="multiple-select options">
				<ul></ul>
			</div>
			<div class="controls">
			<button type="button" id="addDetailOption" class="button-secondary"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /><small> <?php _e('Add Option','Shopp'); ?></small></button>
			</div>
		</li>
	</ul>
	<div class="clear"></div>	
	</div>

<div id="price-ranges" class="panel">
	<div class="pricing-label">
		<label><?php _e('Price Range Search','Shopp'); ?></label>
	</div>
	<div class="pricing-ui">
	<select name="pricerange" id="pricerange-facetedmenu">
		<?php echo menuoptions($pricerange_menu,$Category->pricerange,true); ?>
	</select>
	<ul class="details multipane">
		<li><div id="pricerange-menu" class="multiple-select options"><ul class=""></ul></div>
			<div class="controls">
			<button type="button" id="addPriceLevel" class="button-secondary"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="-" width="16" height="16" /><small> <?php _e('Add Price Range','Shopp'); ?></small></button>
			</div>
		</li>
	</ul>
</div>
<div class="clear"></div>
<div id="pricerange"></div>
<p><?php _e('Configure how you want price range options in this category to appear.','Shopp'); ?></p>
</div>
</div>

<div id="variations-template">
	<div id="variations-menus" class="panel">
		<div class="pricing-label">
			<label><?php _e('Variation Option Menus','Shopp'); ?></label>
		</div>
		<div class="pricing-ui">
			<p><?php _e('Create a predefined set of variation options for products in this category.','Shopp'); ?></p>
			<ul class="multipane">
				<li><div id="variations-menu" class="multiple-select options menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="addVariationMenu" class="button-secondary"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /><small> <?php _e('Add Option Menu','Shopp'); ?></small></button>
					</div>
				</li>
			
				<li>
					<div id="variations-list" class="multiple-select options"></div>
					<div class="controls">
					<button type="button" id="addVariationOption" class="button-secondary"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /><small> <?php _e('Add Option','Shopp'); ?></small></button>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
<br />
<div id="variations-pricing"></div>
</div>


<?php
}
add_meta_box('templates_menus', __('Product Templates &amp; Menus','Shopp'), 'templates_meta_box', 'admin_page_shopp-categories-edit', 'advanced', 'core');


do_action('do_meta_boxes', 'admin_page_shopp-categories-edit', 'normal', $Shopp->Category);
do_action('do_meta_boxes', 'admin_page_shopp-categories-edit', 'advanced', $Shopp->Category);
do_action('do_meta_boxes', 'admin_page_shopp-categories-edit', 'side', $Shopp->Category);
?>