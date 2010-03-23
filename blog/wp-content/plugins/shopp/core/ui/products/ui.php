<?php

function save_meta_box ($Product) {
	global $Shopp;
	
	$workflows = array(
		"continue" => __('Continue Editing','Shopp'),
		"close" => __('Products Manager','Shopp'),
		"new" => __('New Product','Shopp'),
		"next" => __('Edit Next','Shopp'),
		"previous" => __('Edit Previous','Shopp')
		);
	
?>
	<div><input type="hidden" name="id" value="<?php echo $Product->id; ?>" /></div>
	<div id="major-publishing-actions">
		<select name="settings[workflow]" id="workflow">
		<?php echo menuoptions($workflows,$Shopp->Settings->get('workflow'),true); ?>
		</select>
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Product','Shopp'); ?>" />
	</div>
<?php
}
add_meta_box('save-product', __('Save','Shopp'), 'save_meta_box', 'admin_page_shopp-products-edit', 'side', 'core');

function categories_meta_box ($Product) {
	$db =& DB::get();
	$category_table = DatabaseObject::tablename(Category::$table);
	$categories = $db->query("SELECT id,name,parent FROM $category_table ORDER BY parent,name",AS_ARRAY);
	$categories = sort_tree($categories);
	if (empty($categories)) $categories = array();
	
	$categories_menu = '<option value="0" rel="-1,-1">'.__('Parent Category','Shopp').'&hellip;</option>';
	foreach ($categories as $category) {
		$padding = str_repeat("&nbsp;",$category->depth*3);
		$categories_menu .= '<option value="'.$category->id.'" rel="'.$category->parent.','.$category->depth.'">'.$padding.$category->name.'</option>';
	}		

	$selectedCategories = array();
	foreach ($Product->categories as $category) $selectedCategories[] = $category->id;
?>
<div id="category-menu" class="multiple-select short">
	<ul>
		<?php $depth = 0; foreach ($categories as $category): 
		if ($category->depth > $depth) echo "<li><ul>"; ?>
		<?php if ($category->depth < $depth): ?>
			<?php for ($i = $category->depth; $i < $depth; $i++): ?>
				</ul></li>
			<?php endfor; ?>
		<?php endif; ?>
		<li id="category-element-<?php echo $category->id; ?>"><input type="checkbox" name="categories[]" value="<?php echo $category->id; ?>" id="category-<?php echo $category->id; ?>" tabindex="3"<?php if (in_array($category->id,$selectedCategories)) echo ' checked="checked"'; ?> class="category-toggle" /><label for="category-<?php echo $category->id; ?>"><?php echo $category->name; ?></label></li>
		<?php $depth = $category->depth; endforeach; ?>
		<?php for ($i = 0; $i < $depth; $i++): ?>
			</ul></li>
		<?php endfor; ?>
	</ul>
</div>
<div id="new-category">
<input type="text" name="new-category" value="" size="15" id="new-category" /><br />
<select name="new-category-parent"><?php echo $categories_menu; ?></select>
<button id="add-new-category" type="button" class="button-secondary" tabindex="2"><small><?php _e('Add','Shopp'); ?></small></button>
</div>

<?php
}
add_meta_box('categories-box', __('Categories','Shopp'), 'categories_meta_box', 'admin_page_shopp-products-edit', 'side', 'core');

function tags_meta_box ($Product) {
	$taglist = array();
	foreach ($Product->tags as $tag) $taglist[] = $tag->name;
?>
<input name="newtags" id="newtags" type="text" size="16" tabindex="4" autocomplete="off" value="<?php _e('enter, new, tags','Shopp'); ?>…" title="<?php _e('enter, new, tags','Shopp'); ?>…" class="form-input-tip" />
	<button type="button" name="addtags" id="add-tags" class="button-secondary" tabindex="5"><small><?php _e('Add','Shopp'); ?></small></button><input type="hidden" name="taglist" id="tags" value="<?php echo join(",",attribute_escape_deep($taglist)); ?>"><br />
<label><?php _e('Separate tags with commas','Shopp'); ?></label>
<div id="taglist">
	<label><big><strong><?php _e('Tags for this product:','Shopp'); ?></strong></big></label><br />
	<div id="tagchecklist" class="tagchecklist"></div>
</div>
<?php
}
add_meta_box('product-tags', __('Tags','Shopp'), 'tags_meta_box', 'admin_page_shopp-products-edit', 'side', 'core');

function settings_meta_box ($Product) {
	$taglist = array();
	foreach ($Product->tags as $tag) $taglist[] = $tag->name;
?>
<p><input type="hidden" name="published" value="off" /><input type="checkbox" name="published" value="on" id="published" tabindex="11" <?php if ($Product->published == "on") echo ' checked="checked"'?> /><label for="published"> <?php _e('Published','Shopp'); ?></label></p>
	<p><input type="hidden" name="featured" value="off" /><input type="checkbox" name="featured" value="on" id="featured" tabindex="12" <?php if ($Product->featured == "on") echo ' checked="checked"'?> /><label for="featured"> <?php _e('Featured Product','Shopp'); ?></label></p>
	<p><input type="hidden" name="variations" value="off" /><input type="checkbox" name="variations" value="on" id="variations-setting" tabindex="13"<?php if ($Product->variations == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php _e('Variations &mdash; Selectable product options','Shopp'); ?></label></p>
<?php
}
add_meta_box('product-settings', __('Settings','Shopp'), 'settings_meta_box', 'admin_page_shopp-products-edit', 'side', 'core');


function summary_meta_box ($Product) {
?>
	<textarea name="summary" id="summary" rows="2" cols="50" tabindex="6"><?php echo $Product->summary ?></textarea><br /> 
    <label for="summary"><?php _e('A brief description of the product to draw the customer\'s attention.','Shopp'); ?></label>
<?php
}
add_meta_box('product-summary', __('Summary','Shopp'), 'summary_meta_box', 'admin_page_shopp-products-edit', 'normal', 'core');

function details_meta_box ($Product) {
?>
	<ul class="details multipane">
		<li>
			<div id="details-menu" class="multiple-select menu">
			<input type="hidden" name="deletedSpecs" id="test" class="deletes" value="" />
			<ul></ul>
			</div>
		</li>
		<li><div id="details-list" class="list"><ul></ul></div></li>
	</ul><br class="clear" />
	<div id="new-detail">
	<button type="button" id="addDetail" class="button-secondary" tabindex="8"><small><?php _e('Add Product Detail','Shopp'); ?></small></button>
	<p><?php _e('Build a list of detailed information such as dimensions or features of the product.','Shopp'); ?></p>
	</div>
	
	<p></p>
<?php
}
add_meta_box('product-details-box', __('Details &amp; Specs','Shopp'), 'details_meta_box', 'admin_page_shopp-products-edit', 'normal', 'core');

function images_meta_box ($Product) {
	$db =& DB::get();
	if ($Product->id) {
		$Assets = new Asset();
		$Images = $db->query("SELECT id,src,properties FROM $Assets->_table WHERE context='product' AND parent=$Product->id AND datatype='thumbnail' ORDER BY sortorder",AS_ARRAY);
		unset($Assets);
	}
	if (!isset($Images)) $Images = array();
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
			<button type="button" name="deleteImage" value="<?php echo $thumbnail->src; ?>" title="Delete product image&hellip;" class="deleteButton"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/delete.png" alt="-" width="16" height="16" /></button></li>
	<?php endforeach; ?>
	</ul>
	<div class="clear"></div>
	<input type="hidden" name="product" value="<?php echo $_GET['id']; ?>" id="image-product-id" />
	<input type="hidden" name="deleteImages" id="deleteImages" value="" />
	<div id="swf-uploader-button"></div>
	<div id="browser-uploader">
		<button type="button" name="image_upload" id="image-upload" class="button-secondary"><small><?php _e('Add New Image','Shopp'); ?></small></button><br class="clear"/>
	</div>
		
	<p><?php _e('Images shown here will be out of proportion, but will be correctly sized for shoppers.','Shopp'); ?><br />
	<?php _e('Double-click images to edit their details. Save the product to confirm deleted images.','Shopp'); ?></p>
<?php
}
add_meta_box('product-images', __('Product Images','Shopp'), 'images_meta_box', 'admin_page_shopp-products-edit', 'normal', 'core');

function pricing_meta_box ($Product) {
?>
<div id="product-pricing"></div>

<div id="variations">
	<div id="variations-menus" class="panel">
		<div class="pricing-label">
			<label><?php _e('Variation Option Menus','Shopp'); ?></label>
		</div>
		<div class="pricing-ui">
			<p><?php _e('Create the menus and menu options for the product\'s variations.','Shopp'); ?></p>
			<ul class="multipane options">
				<li><div id="variations-menu" class="multiple-select menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="addVariationMenu" class="button-secondary" tabindex="14"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /><small> <?php _e('Add Option Menu','Shopp'); ?></small></button>
					</div>
				</li>

				<li>
					<div id="variations-list" class="multiple-select options"></div>
					<div class="controls right">
						<button type="button" id="linkOptionVariations" class="button-secondary" tabindex="17"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/linked.png" alt="link" width="16" height="16" /><small> <?php _e('Link All Variations','Shopp'); ?></small></button>
					<button type="button" id="addVariationOption" class="button-secondary" tabindex="15"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /><small> <?php _e('Add Option','Shopp'); ?></small></button>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
<br />
<div id="variations-pricing"></div>
</div>

<div><input type="hidden" name="deletePrices" id="deletePrices" value="" />
	<input type="hidden" name="prices" value="" id="prices" /></div>

<?php
}
add_meta_box('product-pricing-box', __('Pricing','Shopp'), 'pricing_meta_box', 'admin_page_shopp-products-edit', 'advanced', 'core');


do_action('do_meta_boxes', 'admin_page_shopp-products-edit', 'normal', $Shopp->Product);
do_action('do_meta_boxes', 'admin_page_shopp-products-edit', 'advanced', $Shopp->Product);
do_action('do_meta_boxes', 'admin_page_shopp-products-edit', 'side', $Shopp->Product);

?>