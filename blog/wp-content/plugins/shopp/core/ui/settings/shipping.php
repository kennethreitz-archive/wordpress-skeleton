<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<h2><?php _e('Shipping Settings','Shopp'); ?></h2>

	<form name="settings" id="shipping" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-shipping'); ?>

		<?php include("navigation.php"); ?>
		
		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="shipping-toggle"><?php _e('Calculate Shipping','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[shipping]" value="off" /><input type="checkbox" name="settings[shipping]" value="on" id="shipping-toggle"<?php if ($this->Settings->get('shipping') == "on") echo ' checked="checked"'?> /><label for="shipping-toggle"> <?php _e('Enabled','Shopp'); ?></label><br /> 
	            <?php _e('Check this to use shipping. Leave un-checked to disable shipping &mdash; helpful if you are only selling subscriptions or downloads.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="weight-unit"><?php _e('Weight Unit','Shopp'); ?></label></th> 
				<td>
				<select name="settings[weight_unit]" id="weight-unit">
					<option></option>
						<?php
							if ($base['units'] == "imperial") $units = array("oz" => __("ounces (oz)","Shopp"),"lb" => __("pounds (lbs)","Shopp"));
							else $units = array("g"=>__("gram (g)","Shopp"),"kg"=>__("kilogram (kg)","Shopp"));
							echo menuoptions($units,$this->Settings->get('weight_unit'),true);
						?>
				</select><br />
				<?php _e('Used as unit of weight for all products.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="order_handling_fee"><?php _e('Order Handling Fee','Shopp'); ?></label></th> 
				<td><input type="text" name="settings[order_shipfee]" value="<?php echo money($this->Settings->get('order_shipfee')); ?>" id="order_handling_fee" size="7" class="right selectall" /><br /> 
	            <?php _e('Handling fee applied once to each order with shipped products.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="free_shipping_text"><?php _e('Free Shipping Text','Shopp'); ?></label></th> 
				<td><input type="text" name="settings[free_shipping_text]" value="<?php echo attribute_escape($this->Settings->get('free_shipping_text')); ?>" id="free_shipping_text" /><br /> 
	            <?php _e('Text used to highlight no shipping costs (examples: Free shipping! or Shipping Included)','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="outofstock-text"><?php _e('Out-of-stock Notice','Shopp'); ?></label></th> 
				<td><input type="text" name="settings[outofstock_text]" value="<?php echo attribute_escape($this->Settings->get('outofstock_text')); ?>" id="outofstock-text" /><br /> 
	            <?php _e('Text used to notify the customer the product is out-of-stock or on backorder.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="lowstock-level"><?php _e('Low Inventory','Shopp'); ?></label></th> 
				<td><input type="text" name="settings[lowstock_level]" value="<?php echo attribute_escape($lowstock); ?>" id="lowstock-level" size="5" /><br /> 
	            <?php _e('Enter the number for low stock level warnings.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="regional_rates"><?php _e('Domestic Regions','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[shipping_regions]" value="off" /><input type="checkbox" name="settings[shipping_regions]" value="on" id="regional_rates"<?php echo ($this->Settings->get('shipping_regions') == "on")?' checked="checked"':''; ?> /><label for="regional_rates"> <?php _e('Enabled','Shopp'); ?></label><br /> 
	            <?php _e('Used for domestic regional shipping rates (only applies to operations based in the U.S. &amp; Canada)','Shopp'); ?><br />
				<strong><?php _e('Note:','Shopp'); ?></strong> <?php _e('You must click the "Save Changes" button for changes to take effect.','Shopp'); ?></td>
			</tr>
		</table>

		<h3><?php _e('Shipping Methods &amp; Rates','Shopp'); ?></h3>
		<p><small><?php _e('Shipping rates based on the order amount are calculated once against the order subtotal (which does not include tax).  Shipping rates based on weight are calculated once against the total order weight.  Shipping rates based on item quantity are calculated against the total quantity of each different item ordered.','Shopp'); ?></small></p>
		<?php $base = $this->Settings->get('base_operations'); if (!empty($base['country'])): ?>
		<table id="shipping-rates" class="form-table"><tr><td></td></tr></table>
		<div class="tablenav"><div class="alignright actions"><button type="button" name="add-shippingrate" id="add-shippingrate" class="button-secondary" tabindex="9999"><?php _e('Add Shipping Method Rates','Shopp'); ?></button></div></div>
		<?php else: ?>
			<p class="tablenav"><small><strong>Note:</strong> <?php _e('You must select a Base of Operations location under','Shopp'); ?> <a href="?page=<?php echo $this->Admin->settings['settings'][0] ?>"><?php _e('General settings','Shopp'); ?></a> <?php _e('before you can configure shipping rates.','Shopp'); ?></small></p>
		<?php endif; ?>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Shipping_Settings";

var currencyFormat = <?php $base = $this->Settings->get('base_operations'); echo json_encode($base['currency']['format']); ?>;
var weight_units = '<?php echo $Shopp->Settings->get("weight_unit"); ?>';
var disabledMethods = new Array();

(function($) {
	
$(document).ready( function() {
	
$('#order_handling_fee').change(function() { this.value = asMoney(this.value); });

$('#weight-unit').change(function () {
	weight_units = $(this).val();
	$('#shipping-rates table.rate td.units span.weightunit').html(weight_units+' = ');
});

var addShippingRate = function (r) {
	if (!r) r = false;
	var i = shippingRates.length;
	var row = $('<tr class="form-required"></tr>').appendTo($('#shipping-rates'));
	var heading = $('<th scope="row" valign="top"><label for="name['+i+']" id="label-'+i+'"><?php echo addslashes(__('Option Name','Shopp')); ?></label></th>').appendTo(row);
	$('<br />').appendTo(heading);
	var name = $('<input type="text" name="settings[shipping_rates]['+i+'][name]" value="" id="name-'+i+'" size="16" tabindex="'+(i+1)+'00" class="selectall" />').appendTo(heading);
	$('<br />').appendTo(heading);

	
	var deliveryTimesMenu = $('<select name="settings[shipping_rates]['+i+'][delivery]" id="delivery-'+i+'" class="methods" tabindex="'+(i+1)+'01"></select>').appendTo(heading);
	var lastGroup = false;
	$.each(deliveryTimes,function(range,label){
		if (range.indexOf("group")==0) {
			lastGroup = $('<optgroup label="'+label+'"></optgroup>').appendTo(deliveryTimesMenu);	
		} else {
			if (lastGroup) $('<option value="'+range+'">'+label+'</option>').appendTo(lastGroup);
			else $('<option value="'+range+'">'+label+'</option>').appendTo(deliveryTimesMenu);
		}
	});

	$('<br />').appendTo(heading);
	
	var methodMenu = $('<select name="settings[shipping_rates]['+i+'][method]" id="method-'+i+'" class="methods" tabindex="'+(i+1)+'02"></select>').appendTo(heading);
	var lastGroup = false;
	$.each(methods,function(m,methodtype){
		if (m.indexOf("group")==0) {
			lastGroup = $('<optgroup label="'+methodtype+'"></optgroup>').appendTo(methodMenu);	
		} else {
			var methodOption = $('<option value="'+m+'">'+methodtype+'</option>');
			for (var disabled in disabledMethods) 
				if (disabledMethods[disabled] == m) methodOption.attr('disabled',true);
			if (lastGroup) methodOption.appendTo(lastGroup);
			else methodOption.appendTo(methodMenu);
		}
	});	
	var rateTableCell = $('<td/>').appendTo(row);
	var deleteRateButton = $('<button type="button" name="deleteRate" class="delete deleteRate"></button>').appendTo(rateTableCell).hide();
	$('<img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/delete.png" width="16" height="16"  />').appendTo(deleteRateButton);
	

	var rowBG = row.css("background-color");
	var deletingBG = "#ffebe8";
	row.hover(function () {
			deleteRateButton.show();
		}, function () {
			deleteRateButton.hide();
	});

	deleteRateButton.hover (function () {
			row.animate({backgroundColor:deletingBG},250);
		},function() {
			row.animate({backgroundColor:rowBG},250);		
	});

	deleteRateButton.click(function () {
		if (shippingRates.length > 1) {
			if (confirm("<?php echo addslashes(__('Are you sure you want to delete this shipping option?','Shopp')); ?>")) {
				row.remove();
				shippingRates.splice(i,1);
			}
		}
	});
		
	var rateTable = $('<table class="rate"/>').appendTo(rateTableCell);

	$(methodMenu).change(function() {
		methodHandlers.call($(this).val(),i,rateTable,r);
	});

	if (r) {
		name.val(r.name);
		methodMenu.val(r.method).change();
		deliveryTimesMenu.val(r.delivery);
	} else {
		methodMenu.change();
	}

	quickSelects();	
	shippingRates.push(row);

}

function uniqueMethod (methodid,option) {
	disabledMethods.push(option);
	$('#label-'+methodid).hide();
	$('#name-'+methodid).hide();
	$('#delivery-'+methodid).hide();
	
	var methodMenu = $('#method-'+methodid);
	var methodOptions = methodMenu.children();
	var optionid = false;

	methodOptions.each(function (i,o) {
		if ($(o).val() == option) optionid = i;
	});

	$('#shipping-rates select.methods').not($(methodMenu)).find('option[value='+option+']').attr('disabled',true);
	
	methodMenu.change(function() {
		if ($(this).val() != option) {
			$('#label-'+methodid).show();
			$('#name-'+methodid).show();
			$('#delivery-'+methodid).show();
			$('#shipping-rates select.methods option[value='+option+']').each(function () {
				$(this).attr('disabled',false);
			});
		}
	});
}

var methodHandlers = new CallbackRegistry();

<?php $Shopp->ShipCalcs->ui(); ?>

if ($('#shipping-rates')) {
	var shippingRates = new Array();
	var rates = <?php echo json_encode($rates); ?>;
	var methods = <?php echo json_encode($methods); ?>;
	var deliveryTimes = {"prompt":"<?php _e('Delivery time','Shopp'); ?>&hellip;","group1":"<?php _e('Business Days','Shopp'); ?>","1d-1d":"1 <?php _e('business day','Shopp'); ?>","1d-2d":"1-2 <?php _e('business days','Shopp'); ?>","1d-3d":"1-3 <?php _e('business days','Shopp'); ?>","1d-5d":"1-5 <?php _e('business days','Shopp'); ?>","2d-3d":"2-3 <?php _e('business days','Shopp'); ?>","2d-5d":"2-5 <?php _e('business days','Shopp'); ?>","2d-7d":"2-7 <?php _e('business days','Shopp'); ?>","3d-5d":"3-5 <?php _e('business days','Shopp'); ?>","group2":"<?php _e('Weeks','Shopp'); ?>","1w-2w":"1-2 <?php _e('weeks','Shopp'); ?>","2w-3w":"2-3 <?php _e('weeks','Shopp'); ?>"};
	var domesticAreas = <?php echo json_encode($areas); ?>;
	var region = '<?php echo $region; ?>';
	
	$('#add-shippingrate').click(function() {
		addShippingRate();
	});

	$('#shipping-rates').empty();
	if (!rates) $('#add-shippingrate').click();
	else {
		$.each(rates,function(i,rate) {
			addShippingRate(rate);
		});	
	}
}

});

})(jQuery)

</script>