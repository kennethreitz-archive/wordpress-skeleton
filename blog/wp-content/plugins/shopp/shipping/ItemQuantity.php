<?php
/**
 * ItemQuantity
 * Provides shipping calculations based on the total quantity of items ordered
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 27 April, 2008
 * @package shopp
 * 
 * $Id: ItemQuantity.php 661 2009-11-25 21:09:19Z jond $
 **/

class ItemQuantity {

	function ItemQuantity () {
	}
	
	function methods (&$ShipCalc) {
		$ShipCalc->methods[get_class($this).'::range'] = __("Item Quantity Tiers","Shopp");
	}
	
	function calculate (&$Cart,$fees,$rate,$column) {
		$ShipCosts = &$Cart->data->ShipCosts;
		$items = 0;
		foreach($Cart->shipped as $Item) $items += $Item->quantity;
		foreach ($rate['max'] as $id => $value) {
			if ($items <= $value) {
				$shipping = $rate[$column][$id];
				break;
			}
		}
		if ($shipping == 0) $shipping = $rate[$column][$id];
		$rate['cost'] = $shipping+$fees;
		$ShipCosts[$rate['name']] = $rate;
		return $rate;
	}
	
	function ui () {
		?>
		
var ItemQuantityRange = function (methodid,table,rates) {
	table.empty();
	var headingsRow = $('<tr class="headings"/>').appendTo(table);

	$('<th scope="col" class="units"><label for="max-'+methodid+'-0"><?php echo addslashes(__('By Item Quantity','Shopp')); ?></label></th>').appendTo(headingsRow);
	$.each(domesticAreas,function(key,area) {
		$('<th scope="col"><label for="'+area+'-'+methodid+'-0">'+area+'</label></th>').appendTo(headingsRow);
	});
	$('<th scope="col"><label for="'+region+'-'+methodid+'-0">'+region+'</label></th>').appendTo(headingsRow);
	$('<th scope="col"><label for="worldwide-'+methodid+'-0"><?php echo addslashes(__('Worldwide','Shopp')); ?></label></th>').appendTo(headingsRow);
	$('<th scope="col">').appendTo(headingsRow);
	
	if (rates && rates['max']) {
		$.each(rates['max'],function(rowid,rate) {
			var row = AddItemQuantityRangeRow(methodid,table,rates);
			row.appendTo(table);
			quickSelects();
		});
	} else {
		var row = AddItemQuantityRangeRow(methodid,table);
		row.appendTo(table);
		quickSelects();
	}
}

function AddItemQuantityRangeRow(methodid,table,rates) {
	var rows = $(table).find('tbody').children().not('tr.headings');
	var id = rows.length;
	
	var row = $('<tr/>');

	var unitCell = $('<td class="units"></td>').appendTo(row);
	$('<label for="max-'+methodid+'-'+id+'"><?php echo addslashes(__("Up to","Shopp")); ?> <label>').appendTo(unitCell);
	if (rates && rates['max'] && rates['max'][id] !== false) value = rates['max'][id];
	else if (id > 1) value = "+";
	else value = 1;
	var maxInput = $('<input type="text" name="settings[shipping_rates]['+methodid+'][max][]" class="selectall right" size="7" id="max-'+methodid+'-'+id+'" tabindex="'+(methodid+1)+'02" />').change(function() {
		if (!(this.value == "+" || this.value == ">")) this.value = asNumber(this.value);
	}).val(value).appendTo(unitCell).change();
	
	$('<span> = </span>').appendTo(unitCell);
	
	$.each(domesticAreas,function(key,area) {
		var inputCell = $('<td/>').appendTo(row);
		if (!isNaN(key)) key = area;
		if (rates && rates[key] && rates[key][id]) value = rates[key][id];
		else value = 0;
		$('<input name="settings[shipping_rates]['+methodid+']['+key+'][]" id="'+area+'-'+methodid+'-'+id+'" class="selectall right" size="7" tabindex="'+(methodid+1)+'04" />').change(function() {
			this.value = asMoney(this.value);
		}).val(value).appendTo(inputCell).change();
	});
	
	var inputCell = $('<td/>').appendTo(row);
	if (rates && rates[region] && rates[region][id]) value = rates[region][id];
	else value = 0;
	$('<input name="settings[shipping_rates]['+methodid+']['+region+'][]"  id="'+region+'-'+methodid+'-'+id+'" class="selectall right" size="7" tabindex="'+(methodid+1)+'05" />').change(function() {
		this.value = asMoney(this.value);
	}).val(value).appendTo(inputCell).change();
	
	var inputCell = $('<td/>').appendTo(row);
	if (rates && rates['Worldwide'] && rates['Worldwide'][id]) value = rates['Worldwide'][id];
	else value = 0;
	worldwideInput = $('<input name="settings[shipping_rates]['+methodid+'][Worldwide][]" id="worldwide-'+methodid+'-'+id+'"  class="selectall right" size="7" tabindex="'+(methodid+1)+'06" />').change(function() {
		this.value = asMoney(this.value);
	}).val(value).appendTo(inputCell).change();
	
	var rowCtrlCell = $('<td class="rowctrl" />').appendTo(row);
	var deleteButton = $('<button type="button" name="delete"></button>').appendTo(rowCtrlCell);
	if (rows.length == 0) {
		deleteButton.attr('class','disabled');
		deleteButton.attr('disabled','disabled');
	}
	deleteButton.click(function() {
		$(row).remove();
	});
	$('<img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/delete.png" width="16" height="16" />').appendTo(deleteButton);
	var addButton = $('<button type="button" name="add" tabindex="'+(methodid+1)+'07"></button>').appendTo(rowCtrlCell);
	$('<img src="<?php echo SHOPP_PLUGINURI; ?>/core/ui/icons/add.png" width="16" height="16" />').appendTo(addButton);
	addButton.click(function() {
		insertedRow = AddItemQuantityRangeRow(methodid,table);
		$(insertedRow).insertAfter($(row));
		quickSelects();
	});
	
	return row;
}

methodHandlers.register('<?php echo get_class($this); ?>::range',ItemQuantityRange);

		<?php		
	}

} // end flatrates class

?>