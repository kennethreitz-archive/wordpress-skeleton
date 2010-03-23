<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>
	
	<h2><?php _e('Settings','Shopp'); ?></h2>
	
	<form name="settings" id="general" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-general'); ?>

		<?php include("navigation.php"); ?>
		
		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="dashboard-toggle"><?php _e('Dashboard Widgets','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[dashboard]" value="off" /><input type="checkbox" name="settings[dashboard]" value="on" id="dashboard-toggle"<?php if ($this->Settings->get('dashboard') == "on") echo ' checked="checked"'?> /><label for="dashboard-toggle"> <?php _e('Enabled','Shopp'); ?></label><br /> 
	            <?php _e('Check this to display store performance metrics and more on the WordPress Dashboard.','Shopp'); ?></td>
			</tr>			
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="merchant_email"><?php _e('Merchant Email','Shopp'); ?></label></th> 
				<td><input type="text" name="settings[merchant_email]" value="<?php echo attribute_escape($this->Settings->get('merchant_email')); ?>" id="merchant_email" size="30" /><br /> 
	            <?php _e('Enter the email address for the owner of this shop to receive e-mail notifications.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="base_operations"><?php _e('Base of Operations','Shopp'); ?></label></th> 
				<td><select name="settings[base_operations][country]" id="base_operations">
					<option></option>
						<?php echo menuoptions($countries,$operations['country'],true); ?>
					</select>
					<select name="settings[base_operations][zone]" id="base_operations_zone">
						<?php if (isset($zones)) echo menuoptions($zones,$operations['zone'],true); ?>
					</select>
					<br /> 
	            	<?php _e('Select your primary business location.','Shopp'); ?><br />
					<?php if (!empty($operations['country'])): ?>
		            <strong><?php _e('Currency','Shopp'); ?>: </strong><?php echo money(1000.00); ?>
					<?php if ($operations['vat']): ?><strong>+(VAT)</strong><?php endif; ?>
					<?php endif; ?>
				</td> 
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="target_markets"><?php _e('Target Markets','Shopp'); ?></label></th> 
				<td>
					<div id="target_markets" class="multiple-select">
						<ul>
							
							<?php $even = true; foreach ($targets as $iso => $country): ?>
								<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" checked="checked" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso,0,1); ?>"><?php echo $country; ?></label></li>
							<?php endforeach; ?>
							<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="selectall_targetmarkets"  id="selectall_targetmarkets" /><label for="selectall_targetmarkets"><strong><?php _e('Select All','Shopp'); ?></strong></label></li>							
							<?php foreach ($countries as $iso => $country): ?>
							<?php if (!in_array($country,$targets)): ?>
							<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso,0,1); ?>"><?php echo $country; ?></label></li>
							<?php endif; endforeach; ?>
						</ul>
					</div>
					<br /> 
	            <?php _e('Select the markets you are selling products to.','Shopp'); ?></td> 
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="cart-toggle"><?php _e('Order Status Labels','Shopp'); ?></label></th> 
				<td>
				<ol id="order-statuslabels">
				</ol>
				<?php _e('Add your own order processing status labels. Be sure to click','Shopp'); ?> <strong><?php _e('Save Changes','Shopp'); ?></strong> <?php _e('below!','Shopp'); ?></td>
			</tr>
		</table>
		
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>
<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>General_Settings";

(function($){
var labels = <?php echo json_encode($statusLabels); ?>;
var labelInputs = new Array();

if (!$('#base_operations').val() || $('#base_operations').val() == '') $('#base_operations_zone').hide();
if (!$('#base_operations_zone').val()) $('#base_operations_zone').hide();

$('#base_operations').change(function() {
	if ($('#base_operations').val() == '') {
		$('#base_operations_zone').hide();
		return true;
	}

	$.getJSON($('#general').attr('action')+'&lookup=zones&country='+$('#base_operations').val(),
		function(data) {
			$('#base_operations_zone').hide();
			$('#base_operations_zone').empty();
			if (!data) return true;
			
			$.each(data, function(value,label) {
				option = $('<option></option>').val(value).html(label).appendTo('#base_operations_zone');
			});
			$('#base_operations_zone').show();
			
	});
});

$('#selectall_targetmarkets').change(function () { 
	if ($(this).attr('checked')) $('#target_markets input').not(this).attr('checked',true); 
	else $('#target_markets input').not(this).attr('checked',false); 
});

var addLabel = function (id,label,location) {
	
	var i = labelInputs.length+1;
	if (!id) id = i;
	
	if (!location) var li = $('<li id="item-'+i+'"></li>').appendTo('#order-statuslabels');
	else var li = $('<li id="item-'+i+'"></li>').insertAfter(location);

	var wrap = $('<span></span>').appendTo(li);
	var input = $('<input type="text" name="settings[order_status]['+id+']" id="label-'+i+'" size="14" />').appendTo(wrap);
	var deleteButton = $('<button type="button" class="delete"></button>').appendTo(wrap).hide();
	var deleteIcon = $('<img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/delete.png" alt="Delete" width="16" height="16" />').appendTo(deleteButton);
	var addButton = $('<button type="button" class="add"></button>').appendTo(wrap);
	var addIcon = $('<img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="Add" width="16" height="16" />').appendTo(addButton);
	
	if (i > 0) {
		wrap.hover(function() {
			deleteButton.show();
		},function () {
			deleteButton.hide();
		});
	}
	
	addButton.click(function () {
		addLabel(null,null,'#'+$(li).attr('id'));
	});
	
	deleteButton.click(function () {
		if (confirm("<?php echo addslashes(__('Are you sure you want to remove this order status label?','Shopp')); ?>"))
			li.remove();
	});
	
	if (label) input.val(label);
	
	labelInputs.push(li);
	
}

if (labels) {
	for (var id in labels) {		
		addLabel(id,labels[id]);
	}
} else addLabel();

})(jQuery)
</script>