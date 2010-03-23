<div class="wrap shopp">
	<h2><?php _e('Categories','Shopp'); ?></h2>
	<?php if (!empty($Shopp->Flow->Notice)): ?><div id="message" class="updated fade"><p><?php echo $Shopp->Flow->Notice; ?></p></div><?php endif; ?>
	
	<form action="" id="categories" method="get">
	<div>
		<input type="hidden" name="page" value="<?php echo $this->Admin->categories; ?>" />
	</div>

	<p id="post-search" class="search-box">
		<input type="text" id="categories-search-input" class="search-input" name="s" value="<?php echo attribute_escape(stripslashes($s)); ?>" />
		<input type="submit" value="<?php _e('Search Categories','Shopp'); ?>" class="button" />
	</p>
	<p><a href="<?php echo esc_url(add_query_arg(array_merge(stripslashes_deep($_GET),array('page'=>$this->Admin->editcategory,'id'=>'new')),$Shopp->wpadminurl."admin.php")); ?>" class="button"><?php _e('New Category','Shopp'); ?></a></p>

	<div class="tablenav">
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="alignleft actions"><button type="submit" id="delete-button" name="deleting" value="category" class="button-secondary"><?php _e('Delete','Shopp'); ?></button></div>
		<div class="clear"></div>
	</div>
	<?php if (SHOPP_WP27): ?><div class="clear"></div>
	<?php else: ?><br class="clear" /><?php endif; ?>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php shopp_print_column_headers('shopp_page_shopp-categories'); ?></tr>
		</thead>
		<?php if (SHOPP_WP27): ?>
		<tfoot>
		<tr><?php shopp_print_column_headers('shopp_page_shopp-categories',false); ?></tr>
		</tfoot>
		<?php endif; ?>
	<?php if (sizeof($Categories) > 0): ?>
		<tbody id="categories-table" class="list categories">
		<?php 
		$hidden = array();
		if (SHOPP_WP27) $hidden = get_hidden_columns('shopp_page_shopp-categories');
				
		$even = false;
		foreach ($Categories as $Category): 
		
		$editurl = esc_url(attribute_escape(add_query_arg(array_merge(stripslashes_deep($_GET),
			array('page'=>$this->Admin->editcategory,
					'id'=>$Category->id)),
					$Shopp->wpadminurl."admin.php")));
		
		$CategoryName = empty($Category->name)?'('.__('no category name','Shopp').')':$Category->name;
		?>
		<tr<?php if (!$even) echo " class='alternate'"; $even = !$even; ?>>
			<th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $Category->id; ?>' /></th>
			<td width="33%"><a class='row-title' href='<?php echo $editurl; ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo $CategoryName; ?>&quot;'><?php echo str_repeat("&#8212; ",$Category->depth); echo $CategoryName; ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo $editurl; ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo $CategoryName; ?>&quot;"><?php _e('Edit','Shopp'); ?></a> | </span>
					<span class='delete'><a class='submitdelete' title='<?php _e('Delete','Shopp'); ?> &quot;<?php echo $CategoryName; ?>&quot;' href='' rel="<?php echo $Category->id; ?>"><?php _e('Delete','Shopp'); ?></a> | </span>
					<span class='view'><a href="<?php echo (SHOPP_PERMALINKS)?$Shopp->shopuri."category/$Category->uri":add_query_arg('shopp_category',$Category->id,$Shopp->shopuri); ?>" title="<?php _e('View','Shopp'); ?> &quot;<?php echo $CategoryName; ?>&quot;" rel="permalink" target="_blank"><?php _e('View','Shopp'); ?></a></span>
				</div>				
			</td>
			<td width="30%" class="description column-description<?php echo in_array('description',$hidden)?' hidden':''; ?>"><?php echo $Category->description; ?>&nbsp;</td>
			<td class="num links column-links<?php echo in_array('links',$hidden)?' hidden':''; ?>"><?php echo $Category->total; ?></td>
			<td width="5%" class="templates column-templates<?php echo ($Category->spectemplate == "on")?' spectemplates':''; echo in_array('templates',$hidden)?' hidden':''; ?>">&nbsp;</td>
			<td width="5%" class="menus column-menus<?php echo ($Category->facetedmenus == "on")?' facetedmenus':''; echo in_array('menus',$hidden)?' hidden':''; ?>">&nbsp;</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php _e('No categories found.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	</form>
	<div class="tablenav">
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
</div>

<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Categories";

jQuery(document).ready( function() {

	var $ = jQuery.noConflict();

	$('#selectall').change( function() {
		$('#categories-table th input').each( function () {
			if (this.checked) this.checked = false;
			else this.checked = true;
		});
	});

	$('a.submitdelete').click(function () {
		if (confirm("<?php _e('You are about to delete this category!\n \'Cancel\' to stop, \'OK\' to delete.','Shopp'); ?>")) {
			$('<input type="hidden" name="delete[]" />').val($(this).attr('rel')).appendTo('#categories');
			$('<input type="hidden" name="deleting" />').val('category').appendTo('#categories');
			$('#categories').submit();
			return false;
		} else return false;
	});

	$('#delete-button').click(function() {
		if (confirm("<?php echo addslashes(__('Are you sure you want to delete the selected categories?','Shopp')); ?>")) {
			$('<input type="hidden" name="categories" value="list" />').appendTo($('#categories'));
			return true;
		} else return false;
	});

<?php if (SHOPP_WP27): ?>
	pagenow = 'shopp_page_shopp-categories';
	columns.init(pagenow);
<?php endif; ?>
});
</script>