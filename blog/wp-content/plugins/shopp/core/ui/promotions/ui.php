<?php
function save_meta_box ($Promotion) {
?>

<div id="misc-publishing-actions">
	<label for="discount-status"><input type="hidden" name="status" value="disabled" /><input type="checkbox" name="status" id="discount-status" value="enabled"<?php echo ($Promotion->status == "enabled")?' checked="checked"':''; ?> /> &nbsp;<?php _e('Enabled','Shopp'); ?></label>

	<p></p>
	
	<div id="start-position" class="calendar-wrap"><input type="text" name="starts[month]" id="starts-month" title="<?php _e('Month','Shopp'); ?>" size="3" value="<?php echo ($Promotion->starts>1)?date("n",$Promotion->starts):''; ?>" class="selectall" />/<input type="text" name="starts[date]" id="starts-date" title="<?php _e('Day','Shopp'); ?>" size="3"  value="<?php echo ($Promotion->starts>1)?date("j",$Promotion->starts):''; ?>" class="selectall" />/<input type="text" name="starts[year]" id="starts-year" title="<?php _e('Year','Shopp'); ?>" size="5" value="<?php echo ($Promotion->starts>1)?date("Y",$Promotion->starts):''; ?>" class="selectall" /></div>
	<p><?php _e('Start promotion on this date.','Shopp'); ?></p>
	
	<div id="end-position" class="calendar-wrap"><input type="text" name="ends[month]" id="ends-month" title="<?php _e('Month','Shopp'); ?>" size="3" value="<?php echo ($Promotion->ends>1)?date("n",$Promotion->ends):''; ?>" class="selectall" />/<input type="text" name="ends[date]" id="ends-date" title="<?php _e('Day','Shopp'); ?>" size="3" value="<?php echo ($Promotion->ends>1)?date("j",$Promotion->ends):''; ?>" class="selectall" />/<input type="text" name="ends[year]" id="ends-year" title="<?php _e('Year','Shopp'); ?>" size="5" value="<?php echo ($Promotion->ends>1)?date("Y",$Promotion->ends):''; ?>" class="selectall" /></div>
	<p><?php _e('End the promotion on this date.','Shopp'); ?></p>
</div>

<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Promotion','Shopp'); ?>" />
</div>
<?php
}
add_meta_box('save-promotion', __('Save','Shopp'), 'save_meta_box', 'admin_page_shopp-promotions-edit', 'side', 'core');

function discount_meta_box ($Promotion) {
	$types = array(
		'Percentage Off' => __('Percentage Off','Shopp'),
		'Amount Off' => __('Amount Off','Shopp'),
		'Free Shipping' => __('Free Shipping','Shopp'),
		'Buy X Get Y Free' => __('Buy X Get Y Free','Shopp')			
	);
	
?>
<p><span>
<select name="type" id="discount-type">
	<?php echo menuoptions($types,$Promotion->type,true); ?>
</select></span>
<span id="discount-row"> 
	&mdash;
	<input type="text" name="discount" id="discount-amount" value="<?php echo $Promotion->discount; ?>" size="10" class="selectall" />
</span>
<span id="beyget-row"> 
	&mdash;
	&nbsp;<?php _e('Buy','Shopp'); ?> <input type="text" name="buyqty" id="buy-x" value="<?php echo $Promotion->buyqty; ?>" size="5" class="selectall" /> <?php _e('Get','Shopp'); ?> <input type="text" name="getqty" id="get-y" value="<?php echo $Promotion->getqty; ?>" size="5" class="selectall" />
</span></p>
<p><?php _e('Select the discount type and amount.','Shopp'); ?></p>

<?php
}
add_meta_box('promotion-discount', __('Discount','Shopp'), 'discount_meta_box', 'admin_page_shopp-promotions-edit', 'normal', 'core');

function rules_meta_box ($Promotion) {
	$scopes = array(
		'Catalog' => __('Catalog','Shopp'),
		'Order' => __('Order','Shopp')
	);
	$scope = '<select name="scope" id="promotion-scope" class="small">';
	$scope .= menuoptions($scopes,$Promotion->scope,true);
	$scope .= '</select>';

	if (empty($Promotion->search)) $Promotion->search = "all";
	
	$logic = '<select name="search" class="small">';
	$logic .= menuoptions(array('any'=>__('any','Shopp'),'all' => __('all','Shopp')),$Promotion->search,true);
	$logic .= '</select>';

?>
<p><strong><?php printf(__('Apply discount to %s products where %s of these conditions are met','Shopp'),$scope,$logic); ?>:</strong></p>
<table class="form-table" id="rules"></table>
<?php
}
add_meta_box('promotion-rules', __('Conditions','Shopp'), 'rules_meta_box', 'admin_page_shopp-promotions-edit', 'normal', 'core');

?>
