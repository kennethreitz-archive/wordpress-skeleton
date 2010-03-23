<?php if (SHOPP_WP27): ?>
<div class="wrap shopp"> 
	<?php if (!empty($Shopp->Flow->Notice)): ?><div id="message" class="updated fade"><p><?php echo $Shopp->Flow->Notice; ?></p></div><?php endif; ?>

	<h2><?php _e('Product Editor','Shopp'); ?></h2> 

	<div id="ajax-response"></div> 
	<form name="product" id="product" action="<?php echo $Shopp->wpadminurl; ?>admin.php" method="post">
		<?php wp_nonce_field('shopp-save-product'); ?>

		<div id="poststuff" class="metabox-holder has-right-sidebar">

			<div id="side-info-column" class="inner-sidebar">
			<?php
			do_action('submitpage_box');
			$side_meta_boxes = do_meta_boxes('admin_page_shopp-products-edit', 'side', $Product);
			?>
			</div>

			<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
			<div id="post-body-content" class="has-sidebar-content">

				<div id="titlediv">
					<div id="titlewrap">
						<input name="name" id="title" type="text" value="<?php echo attribute_escape($Product->name); ?>" size="30" tabindex="1" autocomplete="off" />
					</div>
					<div class="inside">
						<?php if (SHOPP_PERMALINKS && !empty($Product->id)): ?>
							<div id="edit-slug-box"><strong><?php _e('Permalink','Shopp'); ?>:</strong>
							<span id="sample-permalink"><?php echo $permalink; ?><span id="editable-slug" title="<?php _e('Click to edit this part of the permalink','Shopp'); ?>"><?php echo attribute_escape($Product->slug); ?></span><span id="editable-slug-full"><?php echo attribute_escape($Product->slug); ?></span>/</span>
							<span id="edit-slug-buttons"><button type="button" class="edit-slug button"><?php _e('Edit','Shopp'); ?></button></span>
							</div>
						<?php else: ?>
							<?php if (!empty($Product->id)): ?>
							<div id="edit-slug-box"><strong><?php _e('Product ID','Shopp'); ?>:</strong>
							<span id="editable-slug"><?php echo $Product->id; ?></span>
							</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
				<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
				<?php the_editor($Product->description,'content','Description', false); ?>
				<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
				</div>
				
			<?php
			do_meta_boxes('admin_page_shopp-products-edit', 'normal', $Product);
			do_meta_boxes('admin_page_shopp-products-edit', 'advanced', $Product);
			?>

			</div>
			</div>
				
		</div> <!-- #poststuff -->
	</form>
</div>
	
<?php else: ?>
<?php

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

<div class="wrap shopp"> 
	<?php if (!empty($Shopp->Flow->Notice)): ?><div id="message" class="updated fade"><p><?php echo $Shopp->Flow->Notice; ?></p></div><?php endif; ?>

<h2><?php _e('Product Editor','Shopp'); ?></h2> 

<div id="ajax-response"></div> 
<form name="product" id="product" action="<?php echo $Shopp->wpadminurl; ?>admin.php" method="post">
	<?php wp_nonce_field('shopp-save-product'); ?>
	
	<table class="form-table"> 
	<tbody>
		<tr class="form-required"> 
			<th scope="row" valign="top"><label for="name"><?php _e('Product Name','Shopp'); ?></label></th> 
			<td><input name="name" id="name" type="text" value="<?php echo attribute_escape($Product->name); ?>" size="40" tabindex="1" /><br />
				<?php if (SHOPP_PERMALINKS && !empty($Product->id)): ?>
				<div id="edit-slug-box"><strong><?php _e('Permalink','Shopp'); ?>:</strong>
				<span id="sample-permalink"><?php echo $permalink; ?><span id="editable-slug" title="<?php _e('Click to edit this part of the permalink','Shopp'); ?>"><?php echo attribute_escape($Product->slug); ?></span><span id="editable-slug-full"><?php echo attribute_escape($Product->slug); ?></span>/</span>
				<span id="edit-slug-buttons"><button type="button" class="edit-slug button">Edit</button></span>
				</div>
				<?php else: ?>
					<?php if (!empty($Product->id)): ?>
					<div id="edit-slug-box"><strong><?php _e('Product ID','Shopp'); ?>:</strong>
					<span id="editable-slug"><?php echo $Product->id; ?></span>
					</div>
					<?php endif; ?>
				<?php endif; ?>
		</tr>
		<tr class="">
			<th scope="row" valign="top"><label for="category-menu"><?php _e('Categories','Shopp'); ?></label>
				<div id="new-category">
				<input type="text" name="new-category" value="" size="15" id="new-category" /><br />
				<select name="new-category-parent"><?php echo $categories_menu; ?></select>
				<button id="add-new-category" type="button" class="button-secondary" tabindex="2"><small><?php _e('Add New Category','Shopp'); ?></small></button>
				</div>
				</th> 
			<td> 
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
				</div><br />
                <?php _e('Use categories to organize the products in your catalog.','Shopp'); ?></td> 
		</tr>
		<tr class="form-required"> 
			<th scope="row" valign="top"><label for="name"><?php _e('Tags','Shopp'); ?></label></th> 
			<td><input name="newtags" id="newtags" type="text" size="16" tabindex="4" autocomplete="off" value="<?php _e('enter, new, tags','Shopp'); ?>…" title="<?php _e('enter, new, tags','Shopp'); ?>…" class="form-input-tip" />
				<button type="button" name="addtags" id="add-tags" class="button-secondary" tabindex="5"><small><?php _e('Add','Shopp'); ?></small></button><input type="hidden" name="taglist" id="tags" value="<?php echo join(",",$taglist); ?>"><br />
            <?php _e('Separate tags with commas','Shopp'); ?><br />
			<div id="taglist">
				<label><big><strong><?php _e('Tags for this product:','Shopp'); ?></strong></big></label><br />
				<div id="tagchecklist"></div>
			</div>
			</td> 
		<tr class=""> 
			<th scope="row" valign="top"><label for="summary"><?php _e('Summary','Shopp'); ?></label></th> 
			<td><textarea name="summary" id="summary" rows="2" cols="50" tabindex="6" style="width: 97%;"><?php echo $Product->summary ?></textarea><br /> 
            <?php _e('A brief description of the product to draw the customer\'s attention.','Shopp'); ?></td> 
		</tr> 
		<tr class=""> 
			<th scope="row" valign="top"><label for="description"><?php _e('Description','Shopp'); ?></label></th> 
			<td><textarea name="description" id="content" rows="8" cols="50" tabindex="7" style="width: 97%;"><?php echo $Product->description ?></textarea><br /> 
            <?php _e('Provide in-depth information about the product to be displayed on the product page.','Shopp'); ?></td> 
		</tr> 
		<tr class="">
			<th><label><?php _e('Details &amp; Specs','Shopp'); ?></label>
				<div id="new-detail">
				<button type="button" id="addDetail" class="button-secondary" tabindex="8"><small><?php _e('Add Product Detail','Shopp'); ?></small></button>
				</div>
			</th>
			<td>
				<ul class="details multipane">
					<li>
						<div id="details-menu" class="multiple-select menu">
						<input type="hidden" name="deletedSpecs" id="test" class="deletes" value="" />
						<ul></ul>
						</div>
					</li>
					<li><div id="details-list" class="list"><ul></ul></div></li>
				</ul>
				<div class="clear"></div>
				<?php _e('Build a list of detailed information such as dimensions or features of the product.','Shopp'); ?>
			</td>
		</tr>		
		<tr id="product-images" class="form-required"> 
			<th scope="row" valign="top"><label><?php _e('Product Images','Shopp'); ?></label>
				<input type="hidden" name="product" value="<?php echo $_GET['id']; ?>" id="image-product-id" />
				<input type="hidden" name="deleteImages" id="deleteImages" value="" />
				<div id="swf-uploader-button"></div>
				<div id="browser-uploader">
					<button type="button" name="image_upload" id="image-upload" class="button-secondary"><small><?php _e('Add New Image','Shopp'); ?></small></button><br class="clear"/>
				</div>
				</th> 
			<td>
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
				<p><?php _e('Images shown here will be out of proportion, but will be correctly sized for shoppers.','Shopp'); ?><br />
				<?php _e('Double-click images to edit their details. Save the product to confirm deleted images.','Shopp'); ?></p>
			</td> 
		</tr>
		<tr> 
			<th scope="row" valign="top"><label for="published"><?php _e('Settings','Shopp'); ?></label></th> 
			<td><p><input type="hidden" name="published" value="off" /><input type="checkbox" name="published" value="on" id="published" tabindex="11" <?php if ($Product->published == "on") echo ' checked="checked"'?> /><label for="published"> <?php _e('Published','Shopp'); ?></label></p>
				<p><input type="hidden" name="featured" value="off" /><input type="checkbox" name="featured" value="on" id="featured" tabindex="12" <?php if ($Product->featured == "on") echo ' checked="checked"'?> /><label for="featured"> <?php _e('Featured Product','Shopp'); ?></label></p>
				<ul>
					<li><input type="hidden" name="variations" value="off" /><input type="checkbox" name="variations" value="on" id="variations-setting" tabindex="13"<?php if ($Product->variations == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php _e('Variations &mdash; Selectable product options','Shopp'); ?></label></li>
				</ul>
				</td>
		</tr>
	</tbody>
	<tbody id="product-pricing">
	</tbody>
	</table>
	
	<div id="variations">
	<h3><?php _e('Variations','Shopp'); ?></h3>
	<table class="form-table pricing">
	<tbody>
		<tr>
			<th><?php _e('Option Menus','Shopp'); ?></th>
			<td>
				<?php _e('Create the menus and menu options for the product\'s variations.','Shopp'); ?><br />
				<ul class="multipane options">
					<li><div id="variations-menu" class="multiple-select menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="addVariationMenu" class="button-secondary" tabindex="14"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="-" width="16" height="16" /><small> <?php _e('Add Option Menu','Shopp'); ?></small></button>
					</div>
				</li>
				
				<li>
					<div id="variations-list" class="multiple-select options"></div>
					<div class="controls right">
						<button type="button" id="linkOptionVariations" class="button-secondary" tabindex="17"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/linked.png" alt="link" width="16" height="16" /><small> <?php _e('Link All Variations','Shopp'); ?></small></button>
					<button type="button" id="addVariationOption" class="button-secondary" tabindex="15"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="-" width="16" height="16" /><small> <?php _e('Add Option','Shopp'); ?></small></button>
					</div>
				</li>
				</ul>
			</td>
		</tr>
	</tbody>
	<tbody id="variations-pricing"></tbody>
	</table>
	</div>
	
	<div><input type="hidden" name="deletePrices" id="deletePrices" value="" />
		<input type="hidden" name="prices" value="" id="prices" /></div>
		
	
	<p class="submit"><input type="submit" class="button" name="save" value="<?php _e('Save Product','Shopp'); ?>" />	<select name="settings[workflow]" id="workflow">
		<?php echo menuoptions($workflows,$Shopp->Settings->get('workflow'),true); ?>
		</select>
	</p>
</form>

</div>

<?php endif; ?>

<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Editing_a_Product";

var flashuploader = <?php echo ($uploader == 'flash' && !(false !== strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mac') && apache_mod_loaded('mod_security')))?'true':'false'; ?>;
var wp26 = <?php echo (SHOPP_WP27)?'false':'true'; ?>;
var product = <?php echo (!empty($Product->id))?$Product->id:'false'; ?>;
var prices = <?php echo json_encode($Product->prices) ?>;
var specs = <?php echo json_encode($Product->specs) ?>;
var options = <?php echo json_encode($Product->options) ?>;
var priceTypes = <?php echo json_encode($priceTypes) ?>;
var shiprates = <?php echo json_encode($shiprates); ?>;
var buttonrsrc = '<?php echo includes_url('images/upload.png'); ?>';
var rsrcdir = '<?php echo SHOPP_PLUGINURI; ?>';
var siteurl = '<?php echo $Shopp->siteurl; ?>';
var adminurl = '<?php echo $Shopp->wpadminurl; ?>';
var ajaxurl = adminurl+'admin-ajax.php';
var addcategory_url = '<?php echo wp_nonce_url($Shopp->wpadminurl."admin-ajax.php", "shopp-ajax_add_category"); ?>';
var editslug_url = '<?php echo wp_nonce_url($Shopp->wpadminurl."admin-ajax.php", "shopp-ajax_edit_slug"); ?>';
var fileverify_url = '<?php echo wp_nonce_url($Shopp->wpadminurl."admin-ajax.php", "shopp-ajax_verify_file"); ?>';
var manager_page = '<?php echo $this->Admin->products; ?>';
var editor_page = '<?php echo $this->Admin->editproduct; ?>';
var request = <?php echo json_encode(stripslashes_deep($_GET)); ?>;
var workflow = {'continue':editor_page, 'close':manager_page, 'new':editor_page, 'next':editor_page, 'previous':editor_page};
var worklist = <?php echo json_encode($this->products_list(true)); ?>;
var filesizeLimit = <?php echo wp_max_upload_size(); ?>;
var weightUnit = '<?php echo $this->Settings->get('weight_unit'); ?>';
var storage = '<?php echo $this->Settings->get('product_storage'); ?>';
var productspath = '<?php echo addslashes(trailingslashit($this->Settings->get('products_path'))); ?>';

// Warning/Error Dialogs
var DELETE_IMAGE_WARNING = "<?php _e('Are you sure you want to delete this product image?','Shopp'); ?>";
var SERVER_COMM_ERROR = "<?php _e('There was an error communicating with the server.','Shopp'); ?>";

// Dynamic interface label translations
var LINK_ALL_VARIATIONS = "<?php _e('Link All Variations','Shopp'); ?>";
var UNLINK_ALL_VARIATIONS = "<?php _e('Unlink All Variations','Shopp'); ?>";
var LINK_VARIATIONS = "<?php _e('Link Variations','Shopp'); ?>";
var UNLINK_VARIATIONS = "<?php _e('Unlink Variations','Shopp'); ?>";
var ADD_IMAGE_BUTTON_TEXT = "<?php _e('Add New Image','Shopp'); ?>";
var UPLOAD_FILE_BUTTON_TEXT = "<?php _e('Upload&nbsp;File','Shopp'); ?>";
var SAVE_BUTTON_TEXT = "<?php _e('Save','Shopp'); ?>";
var CANCEL_BUTTON_TEXT = "<?php _e('Cancel','Shopp'); ?>";
var TYPE_LABEL = "<?php _e('Type','Shopp'); ?>";
var PRICE_LABEL = "<?php _e('Price','Shopp'); ?>";
var AMOUNT_LABEL = "<?php _e('Amount','Shopp'); ?>";
var SALE_PRICE_LABEL = "<?php _e('Sale Price','Shopp'); ?>";
var NOT_ON_SALE_TEXT = "<?php _e('Not on Sale','Shopp'); ?>";
var NOTAX_LABEL = "<?php _e('Not Taxed','Shopp'); ?>";
var SHIPPING_LABEL = "<?php _e('Shipping','Shopp'); ?>";
var FREE_SHIPPING_TEXT = "<?php _e('Free Shipping','Shopp'); ?>";
var WEIGHT_LABEL = "<?php _e('Weight','Shopp'); ?>";
var SHIPFEE_LABEL = "<?php _e('Handling Fee','Shopp'); ?>";
var INVENTORY_LABEL = "<?php _e('Inventory','Shopp'); ?>";
var NOT_TRACKED_TEXT = "<?php _e('Not Tracked','Shopp'); ?>";
var IN_STOCK_LABEL = "<?php _e('In Stock','Shopp'); ?>";
var OPTION_MENU_DEFAULT = "<?php _e('Option Menu','Shopp'); ?>";
var NEW_OPTION_DEFAULT = "<?php _e('New Option','Shopp'); ?>";
var SKU_LABEL = "<?php _e('SKU','Shopp'); ?>";
var SKU_LABEL_HELP = "<?php _e('Stock Keeping Unit','Shopp'); ?>";
var DONATIONS_VAR_LABEL = "<?php _e('Accept variable amounts','Shopp'); ?>";
var DONATIONS_MIN_LABEL = "<?php _e('Amount required as minimum','Shopp'); ?>";
var PRODUCT_DOWNLOAD_LABEL = "<?php _e('Product Download','Shopp'); ?>";
var NO_PRODUCT_DOWNLOAD_TEXT = "<?php _e('No product download.','Shopp'); ?>";
var NO_DOWNLOAD = "<?php _e('No download file.','Shopp'); ?>";
var DEFAULT_PRICELINE_LABEL = "<?php _e('Price & Delivery','Shopp'); ?>";
var FILE_NOT_FOUND_TEXT = "<?php _e('The file you specified could not be found.','Shopp'); ?>";
var FILE_NOT_READ_TEXT = "<?php _e('The file you specified is not readable and cannot be used.','Shopp'); ?>";
var FILE_ISDIR_TEXT = "<?php _e('The file you specified is a directory and cannot be used.','Shopp'); ?>";

</script>