<?php
/**
 * FlatRates
 * Provides flat rate shipping calculations
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 27 April, 2008
 * @package shopp
 * 
 * $Id: FlatRates.php 661 2009-11-25 21:09:19Z jond $
 **/

class FlatRates {

	function FlatRates () {
	}
	
	function methods (&$ShipCalc) {
		$ShipCalc->methods[get_class($this).'::order'] = __("Flat Rate on order","Shopp");
		$ShipCalc->methods[get_class($this).'::item'] = __("Flat Rate per item","Shopp");
	}
	
	function calculate (&$Cart,$fees,$rate,$column) {
		$ShipCosts = &$Cart->data->ShipCosts;
		list($ShipCalcClass,$process) = explode("::",$rate['method']);
		switch($process) {
			case "item":
				$shipping = 0;
				foreach($Cart->shipped as $Item)
 					$shipping += $Item->quantity * $rate[$column][0];
				$rate['cost'] = $shipping+$fees;
				break;
			default:
				$rate['cost'] = $rate[$column][0]+$fees;
		}
		$ShipCosts[$rate['name']] = $rate;
		return $rate;
	}
	
	function ui () {
		?>
var FlatRates = function (methodid,table,rates) {
	table.empty();
	var headingsRow = $('<tr class="headings"/>').appendTo(table);

	$('<th scope="col">').appendTo(headingsRow);

	var domesticHeading = new Array();
	$.each(domesticAreas,function(key,area) {
		domesticHeading[key] = $('<th scope="col"><label for="'+area+'['+methodid+']">'+area+'</label></th>').appendTo(headingsRow);
	});
	var regionHeading = $('<th scope="col"><label for="'+region+'['+methodid+']">'+region+'</label></th>').appendTo(headingsRow);
	var worldwideHeading = $('<th scope="col"><label for="worldwide['+methodid+']"><?php echo addslashes(__('Worldwide','Shopp')); ?></label></th>').appendTo(headingsRow);
	$('<th scope="col">').appendTo(headingsRow);

	var row = $('<tr/>').appendTo(table);

	$('<td/>').appendTo(row);

	$.each(domesticAreas,function(key,area) {
		var inputCell = $('<td/>').appendTo(row);
		if (!isNaN(key)) key = area;
		if (rates && rates[key] && rates[key][0]) var value = rates[key][0];
		else value = 0;
		$('<input name="settings[shipping_rates]['+methodid+']['+key+'][]" id="'+area+'['+methodid+']" class="selectall right" size="7" tabindex="'+(methodid+1)+'04" />').change(function() {
			this.value = asMoney(this.value);
		}).val(value).appendTo(inputCell).change();
	});
	
	var inputCell = $('<td/>').appendTo(row);
	if (rates && rates[region] && rates[region][0]) value = rates[region][0];
	else value = 0;
	$('<input name="settings[shipping_rates]['+methodid+']['+region+'][]"  id="'+region+'['+methodid+']" class="selectall right" size="7" tabindex="'+(methodid+1)+'05" />').change(function() {
		this.value = asMoney(this.value);
	}).val(value).appendTo(inputCell).change();
	
	var inputCell = $('<td/>').appendTo(row);
	if (rates && rates['Worldwide'] && rates['Worldwide'][0]) value = rates['Worldwide'][0];
	else value = 0;
	intlInput = $('<input name="settings[shipping_rates]['+methodid+'][Worldwide][]" id="worldwide['+methodid+']" class="selectall right" size="7" tabindex="'+(methodid+1)+'06" />').change(function() {
		this.value = asMoney(this.value);
	}).val(value).appendTo(inputCell).change();	
	
	$('<td/>').appendTo(row);
	
	quickSelects();
}

methodHandlers.register('<?php echo get_class($this); ?>::order',FlatRates);
methodHandlers.register('<?php echo get_class($this); ?>::item',FlatRates);

		<?php		
	}

} // end flatrates class

?>