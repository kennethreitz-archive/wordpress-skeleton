<div class="wrap shopp">
	<h2><?php _e('Products','Shopp'); ?></h2>

	<?php if (!empty($Shopp->Flow->Notice)): ?><div id="message" class="updated fade"><p><?php echo $Shopp->Flow->Notice; ?></p></div><?php endif; ?>

	<form action="" method="get" id="products-manager">
	<div>
		<input type="hidden" name="page" value="<?php echo $this->Admin->products; ?>" />
	</div>

	<p id="post-search" class="search-box">
		<input type="text" id="products-search-input" class="search-input" name="s" value="<?php echo stripslashes(attribute_escape($s)); ?>" />
		<input type="submit" value="<?php _e('Search Products','Shopp'); ?>" class="button" />
	</p>
	
	<p><a href="<?php echo add_query_arg(array('page'=>$this->Admin->editproduct,'id'=>'new'),$Shopp->wpadminurl."admin.php"); ?>" class="button"><?php _e('New Product','Shopp'); ?></a></p>

	<div class="tablenav">
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="alignleft actions">
		<button type="submit" id="delete-button" name="deleting" value="product" class="button-secondary"><?php _e('Delete','Shopp'); ?></button>
		<select name='cat'>
		<?php echo $categories_menu; ?>
		</select>
		<select name='sl'>
		<?php echo $inventory_menu; ?>
		</select>
		<input type="submit" id="filter-button" value="<?php _e('Filter','Shopp'); ?>" class="button-secondary">
		</div>
		<div class="clear"></div>
	</div>
	<?php if (SHOPP_WP27): ?><div class="clear"></div>
	<?php else: ?><br class="clear" /><?php endif; ?>
	
	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php shopp_print_column_headers('shopp_page_shopp-products'); ?></tr>
		</thead>
		<?php if (SHOPP_WP27): ?>
		<tfoot>
		<tr><?php shopp_print_column_headers('shopp_page_shopp-products',false); ?></tr>
		</tfoot>
		<?php endif; ?>
	<?php if (sizeof($Products) > 0): ?>
		<tbody id="products" class="list products">
		<?php 
		$hidden = array();
		if (SHOPP_WP27) $hidden = get_hidden_columns('shopp_page_shopp-products');

		$even = false; 
		foreach ($Products as $key => $Product):
		$editurl = esc_url(attribute_escape(add_query_arg(array_merge(stripslashes_deep($_GET),
			array('page'=>$this->Admin->editproduct,
					'id'=>$Product->id)),
					$Shopp->wpadminurl."admin.php")));
		
		$ProductName = empty($Product->name)?'('.__('no product name','Shopp').')':$Product->name;
		?>
		<tr<?php if (!$even) echo " class='alternate'"; $even = !$even; ?>>
			<th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $Product->id; ?>' /></th>
			<td class="name column-name"><a class='row-title' href='<?php echo $editurl; ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo $ProductName; ?>&quot;'><?php echo $ProductName; ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo $editurl; ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo $ProductName; ?>&quot;"><?php _e('Edit','Shopp'); ?></a> | </span>
					<span class='edit'><a href="<?php echo esc_url(add_query_arg(array_merge($_GET,array('duplicate'=>$Product->id)),$Shopp->wpadminurl)); ?>" title="<?php _e('Duplicate','Shopp'); ?> &quot;<?php echo $ProductName; ?>&quot;"><?php _e('Duplicate','Shopp'); ?></a> | </span>
					<span class='delete'><a class='submitdelete' title='<?php _e('Delete','Shopp'); ?> &quot;<?php echo $ProductName; ?>&quot;' href='' rel="<?php echo $Product->id; ?>"><?php _e('Delete','Shopp'); ?></a> | </span>
					<span class='view'><a href="<?php echo (SHOPP_PERMALINKS)?$Shopp->shopuri.$Product->slug:add_query_arg('shopp_pid',$Product->id,$Shopp->shopuri); ?>" title="<?php _e('View','Shopp'); ?> &quot;<?php echo $ProductName; ?>&quot;" rel="permalink" target="_blank"><?php _e('View','Shopp'); ?></a></span>
				</div>
				</td>
			<td class="category column-category<?php echo in_array('category',$hidden)?' hidden':''; ?>"><?php echo $Product->categories; ?></td>
			<td class="price column-price<?php echo in_array('price',$hidden)?' hidden':''; ?>"><?php
				if ($Product->variations == "off") echo money($Product->mainprice);
				elseif ($Product->maxprice == $Product->minprice) echo money($Product->maxprice);
				else echo money($Product->minprice)."&mdash;".money($Product->maxprice);
			?></td>
			<td class="inventory column-inventory<?php echo in_array('inventory',$hidden)?' hidden':''; ?>"><?php if ($Product->inventory == "on") echo $Product->stock; ?></td> 
			<td class="featured column-featured<?php echo ($Product->featured == "on")?' is-featured':''; echo in_array('featured',$hidden)?' hidden':''; ?>">&nbsp;</td> 
		
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php _e('No products found.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	</form>
	<div class="tablenav">
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
</div>    

<script type="text/javascript">
	helpurl = "<?php echo SHOPP_DOCS; ?>Products";

jQuery(document).ready( function() {
	var $=jQuery.noConflict();
	
	$('#selectall').change( function() {
		$('#products th input').each( function () {
			if (this.checked) this.checked = false;
			else this.checked = true;
		});
	});
	
	$('a.submitdelete').click(function () {
		var name = $(this).attr('title');
		if ( confirm("<?php _e('You are about to delete this product!\n \'Cancel\' to stop, \'OK\' to delete.','Shopp'); ?>")) {
			$('<input type="hidden" name="delete[]" />').val($(this).attr('rel')).appendTo('#products-manager');
			$('<input type="hidden" name="deleting" />').val('product').appendTo('#products-manager');
			$('#products-manager').submit();
			return false;
		} else return false;
	});

	$('#delete-button').click(function() {
		if (confirm("<?php echo addslashes(__('Are you sure you want to delete the selected products?','Shopp')); ?>")) return true;
		else return false;
	});
<?php if (SHOPP_WP27): ?>
	pagenow = 'shopp_page_shopp-products';
	columns.init(pagenow);
<?php endif; ?>
});
</script>