<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<h2><?php _e('Tax Settings','Shopp'); ?></h2>

	<form name="settings" id="taxes" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-taxes'); ?>

		<?php include("navigation.php"); ?>
		
		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="taxes-toggle"><?php _e('Calculate Taxes','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[taxes]" value="off" /><input type="checkbox" name="settings[taxes]" value="on" id="taxes-toggle"<?php if ($this->Settings->get('taxes') == "on") echo ' checked="checked"'?> /><label for="taxes-toggle"> <?php _e('Enabled','Shopp'); ?></label><br /> 
	            <?php _e('Enables tax calculations.  Disable if you are exclusively selling non-taxable items.','Shopp'); ?></td>
			</tr>
			<tr class="form-required">
					<th scope="row" valign="top"><label for="tax-shipping-toggle"><?php _e('Tax Shipping','Shopp'); ?></label></th> 
					<td><input type="hidden" name="settings[tax_shipping]" value="off" /><input type="checkbox" name="settings[tax_shipping]" value="on" id="tax-shipping-toggle"<?php if ($this->Settings->get('tax_shipping') == "on") echo ' checked="checked"'?> /><label for="tax-shipping-toggle"> <?php _e('Enabled','Shopp'); ?></label><br /> 
		            <?php _e('Enable to include shipping and handling in taxes.','Shopp'); ?></td>
				</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="taxrate[i]"><?php _e('Tax Rates','Shopp'); ?></label></th> 
				<td>
					<?php if ($this->Settings->get('target_markets')): ?>
					<table id="taxrates-table"><tr><td></td></tr></table>
	            <button type="button" id="add-taxrate" class="button-secondary"><img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" alt="+" width="16" height="16" /> <?php _e('Add a Tax Rate','Shopp'); ?></button>
					<?php else: ?>
					<p><strong><?php _e('Note:','Shopp'); ?></strong> <?php _e('You must select the target markets you will be selling to under','Shopp'); ?> <a href="?page=<?php echo $this->Admin->settings['settings'][0] ?>"><?php _e('General settings','Shopp'); ?></a> <?php _e('before you can setup tax rates.','Shopp'); ?></p>
					<?php endif; ?>
				</td> 
			</tr>

		</table>
				
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
//<![CDATA[
helpurl = "<?php echo SHOPP_DOCS; ?>Taxes_Settings";

(function($) {

var disableCountriesInUse = function () {
	$('#taxrates-table tr select.country option').each (function () {
		$(this).attr('disabled',false);
		if ($.inArray($(this).val(),countriesInUse) != -1 && !this.selected)
			$(this).attr('disabled',true);
		if ($.inArray($(this).val(),allCountryZonesInUse) != -1 && !this.selected)
			$(this).attr('disabled',true);
	});
}

var disableZonesInUse = function () {
	$('#taxrates-table tr select.zone option').each (function () {
		if ($.inArray($(this).val(),zonesInUse) != -1 && !this.selected)
			$(this).attr('disabled',true);
		else $(this).attr('disabled',false);
	});
}

var addTaxRate = function (r) {
	i = taxrates.length;
	var row = $('<tr/>').appendTo('#taxrates-table');
	
	var rateCell = $('<td></td>').html(' %').appendTo(row);
	var rate = $('<input type="text" name="settings[taxrates]['+i+'][rate]" value="" id="settings-taxrates-'+i+'-rate" size="4" class="selectall right" />').prependTo(rateCell);
	var deleteButton = $('<button id="deleteButton-'+i+'" class="deleteButton" type="button" title="Delete tax rate"></button>').prependTo(rateCell).hide();
	var deleteIcon = $('<img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/delete.png" width="16" height="16" />').appendTo(deleteButton);
	
	var countryCell = $('<td></td>').appendTo(row);
	var countryMenu = $('<select name="settings[taxrates]['+i+'][country]" id="country-'+i+'" class="country"></select>').appendTo(countryCell);
	
	$.each(countries, function(value,label) {
		option = $('<option></option>').val(value).html(label).appendTo(countryMenu);
	});
	
	var zoneCell = $('<td></td>').appendTo(row);
	var zoneMenu = $('<select name="settings[taxrates]['+i+'][zone]" id="zone-'+i+'" class="zone"></select>').appendTo(zoneCell);

	var updateZoneMenu = function () {
		zoneMenu.empty(); // Clear out the zone menu to start from scratch
		if (zones[$(countryMenu).val()]) {
			var selectNext = false;
			// Add country zones to the zone menu
			$.each(zones[$(countryMenu).val()], function(value,label) {
				if ($.inArray(value,zonesInUse) != -1) option = $('<option></option>').attr('disabled',true).val(value).html(label).appendTo(zoneMenu);				
				else option = $('<option></option>').val(value).html(label).appendTo(zoneMenu);
				if (selectNext) { // If the previous option was disabled, select this one in the menu
					selectNext = false;
					option.attr('selected',true);
				}
				// This option is seleted but disabled, we need to select the next option
				if (option.attr('selected') && option.attr('disabled')) selectNext = true;
			});
			// All of the zones have been selected, disable the country in the country menu
			if (selectNext) {
				allCountryZonesInUse.push($(countryMenu).val());
				disableCountriesInUse();
				countryMenu.attr('selectedIndex',countryMenu.attr('selectedIndex')+1).change();
			}
		}
		// Hide the zone menu if there are no zones for the selected country
		if (zoneMenu.children().length == 0) {
			zoneMenu.hide();
		} else zoneMenu.show(); // Show the zone menu when there are zones
		zoneMenu.change();
	}
	
	$(row).hover(function() {
			if (i > 0) deleteButton.show();
		},function() {
			deleteButton.hide();
	});
	
	$(deleteButton).click(function () {
		if (taxrates.length > 1) {
			if (confirm("<?php echo addslashes(__('Are you sure you want to delete this tax rate?','Shopp')); ?>")) {
				row.remove();
				taxrates.splice(i,1);
			}
		}
	});
	
	$(countryMenu).change(function () {
		if (!this.currentCountry) this.currentCountry = $(countryMenu).val();
		if ($.inArray(this.currentCountry,countriesInUse) != -1)
			countriesInUse.splice($.inArray(this.currentCountry,countriesInUse),1);
		this.currentCountry = $(this).val();
		if (!zones[this.currentCountry]) countriesInUse.push(this.currentCountry);
		disableCountriesInUse();
		updateZoneMenu();
	});
	
	$(zoneMenu).change(function () {
		if (!this.currentZone) this.currentZone = $(this).val();
		if ($.inArray(this.currentZone,zonesInUse) != -1)
			zonesInUse.splice($.inArray(this.currentZone,zonesInUse),1);
		this.currentZone = $(this).val();
		zonesInUse.push(this.currentZone);
		disableZonesInUse();
	});
	
	if (r) {
		rate.val(r.rate);
		$(countryMenu).val(r.country).change();
		if (r.zone)	zoneMenu.val(r.zone).change();
	} else {
		if ($.inArray(base.country,countriesInUse) == -1) {
			countryMenu.val(base.country).change();
			if (base.zone) {
				if ($.inArray(base.zone,zonesInUse) == -1)
					zoneMenu.val(base.zone).change();
				else zoneMenu.change();
			} else zoneMenu.change();
		} else countryMenu.change();
	}
	
	taxrates.push(row);
	quickSelects();
	
}

if ($('#taxrates-table')) {
	var rates = <?php echo json_encode($rates); ?>;
	var base = <?php echo json_encode($base); ?>;
	var countries = <?php echo json_encode($countries); ?>;
	var zones = <?php echo json_encode($zones); ?>;
	var taxrates = new Array();
	var countriesInUse = new Array();
	var zonesInUse = new Array();
	var allCountryZonesInUse = new Array();

	$('#add-taxrate').click(function() { addTaxRate(); });
	
	$(window).ready(function () {
		$('#taxrates-table').empty();
		if (rates) $(rates).each(function () { addTaxRate(this); });
		else addTaxRate();	
	});
}

})(jQuery)

//]]>
</script>