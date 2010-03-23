<?php
/**
 * Item class
 * Cart items
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

class Item {
	var $product = false;
	var $price = false;
	var $category = false;
	var $sku = false;
	var $type = false;
	var $name = false;
	var $description = false;
	var $optionlabel = false;
	var $variation = array();
	var $option = false;
	var $menus = array();
	var $options = array();
	var $saved = 0;
	var $savings = 0;
	var $quantity = 0;
	var $unitprice = 0;
	var $total = 0;
	var $weight = 0;
	var $shipfee = 0;
	var $tax = 0;
	var $download = false;
	var $shipping = false;
	var $inventory = false;
	var $taxable = false;
	var $freeshipping = false;

	function Item ($Product,$pricing,$category,$data=array()) {
		global $Shopp; // To access settings

		$Product->load_data(array('prices','images'));
		// If product variations are enabled, disregard the first priceline
		if ($Product->variations == "on") array_shift($Product->prices);

		// If option ids are passed, lookup by option key, otherwise by id
		if (is_array($pricing)) {
			$Price = $Product->pricekey[$Product->optionkey($pricing)];
			if (empty($Price)) $Price = $Product->pricekey[$Product->optionkey($pricing,true)];
		} elseif ($pricing) $Price = $Product->priceid[$pricing];
		else {
			foreach ($Product->prices as &$Price)
				if ($Price->type != "N/A" && 
					(!$Price->stocked || 
					($Price->stocked && $Price->stock > 0))) break;
				
		}
		if (isset($Product->id)) $this->product = $Product->id;
		if (isset($Price->id)) $this->price = $Price->id;
		$this->category = $category;
		$this->option = $Price;
		$this->name = $Product->name;
		$this->slug = $Product->slug;
		$this->description = $Product->summary;
		if (isset($Product->thumbnail)) $this->thumbnail = $Product->thumbnail;
		$this->menus = $Product->options;
		if ($Product->variations == "on") $this->options = $Product->prices;
		$this->sku = $Price->sku;
		$this->type = $Price->type;
		$this->sale = $Price->onsale;
		$this->freeshipping = $Price->freeshipping;
		$this->saved = ($Price->price - $Price->promoprice);
		$this->savings = ($Price->price > 0)?percentage($this->saved/$Price->price)*100:0;
		$this->unitprice = (($Price->onsale)?$Price->promoprice:$Price->price);
		$this->optionlabel = (count($Product->prices) > 1)?$Price->label:'';
		$this->donation = $Price->donation;
		$this->data = stripslashes_deep(attribute_escape_deep($data));
		
		// Map out the selected menu name and option
		if ($Product->variations == "on") {
			$selected = explode(",",$this->option->options); $s = 0;
			foreach ($this->menus as $i => $menu) {
				foreach($menu['options'] as $option) {
					if ($option['id'] == $selected[$s]) {
						$this->variation[$menu['name']] = $option['name']; break;
					}
				}
				$s++;
			}
		}

		if (!empty($Price->download)) $this->download = $Price->download;
		if ($Price->type == "Shipped") {
			$this->shipping = true;
			if ($Price->shipping == "on") {
				$this->weight = $Price->weight;
				$this->shipfee = $Price->shipfee;
			} else $this->freeshipping = true;
		}
		
		$this->inventory = ($Price->inventory == "on")?true:false;
		$this->taxable = ($Price->tax == "on" && $Shopp->Settings->get('taxes') == "on")?true:false;
	}
	
	function valid () {
		if (!$this->product || !$this->price) {
			new ShoppError(__('The product could not be added to the cart because it could not be found.','cart_item_invalid',SHOPP_ERR));
			return false;
		}
		if ($this->inventory && $this->option->stock == 0) {
			new ShoppError(__('The product could not be added to the cart because it is not in stock.','cart_item_invalid',SHOPP_ERR));
			return false;
		}
		return true;
	}

	function quantity ($qty) {

		if ($this->type == "Donation" && $this->donation['var'] == "on") {
			if ($this->donation['min'] == "on" && floatnum($qty) < $this->unitprice) 
				$this->unitprice = $this->unitprice;
			else $this->unitprice = floatnum($qty);
			$this->quantity = 1;
			$qty = 1;
		}

		$qty = preg_replace('/[^\d+]/','',$qty);
		if ($this->inventory) {
			if ($qty > $this->option->stock) {
				new ShoppError(__('Not enough of the product is available in stock to fulfill your request.','Shopp'),'item_low_stock');
				$this->quantity = $this->option->stock;
			}
			else $this->quantity = $qty;
		} else $this->quantity = $qty;
		
		$this->total = $this->quantity * $this->unitprice;
	}
	
	function add ($qty) {
		if ($this->type == "Donation" && $this->donation['var'] == "on") {
			$qty = floatnum($qty);
			$this->quantity = $this->unitprice;
		}
		$this->quantity($this->quantity+$qty);
	}
	
	function options ($selection = "",$taxrate=0) {
		if (empty($this->options)) return "";

		$string = "";
		foreach($this->options as $option) {
			if ($option->type == "N/A") continue;
			$currently = ($option->onsale)?$option->promoprice:$option->price;

			$difference = (float)($currently+($currently*$taxrate))-($this->unitprice+($this->unitprice*$taxrate));
			// $difference = $currently-$this->unitprice;

			$price = '';
			if ($difference > 0) $price = '  (+'.money($difference).')';
			if ($difference < 0) $price = '  (-'.money(abs($difference)).')';
			
			$selected = "";
			if ($selection == $option->id) $selected = ' selected="Selected"';
			$disabled = "";
			if ($option->inventory == "on" && $option->stock < $this->quantity)
				$disabled = ' disabled="disabled"';
			
			$string .= '<option value="'.$option->id.'"'.$selected.$disabled.'>'.$option->label.$price.'</option>';
		}
		return $string;
	}
	
	function unstock () {
		if (!$this->inventory) return;
		global $Shopp;
		$db = DB::get();
		
		// Update stock in the database
		$table = DatabaseObject::tablename(Price::$table);
		$db->query("UPDATE $table SET stock=stock-{$this->quantity} WHERE id='{$this->price}' AND stock > 0");
		
		// Update stock in the model
		$this->option->stock -= $this->quantity;

		// Handle notifications
		$product = $this->name.' ('.$this->option->label.')';
		if ($this->option->stock == 0)
			return new ShoppError(sprintf(__('%s is now out-of-stock!','Shopp'),$product),'outofstock_warning',SHOPP_STOCK_ERR);
		
		if ($this->option->stock <= $Shopp->Settings->get('lowstock_level'))
			return new ShoppError(sprintf(__('%s has low stock levels and should be re-ordered soon.','Shopp'),$product),'lowstock_warning',SHOPP_STOCK_ERR);

	}
	
	function shipping (&$Shipping) {
	}
	
	function tag ($id,$property,$options=array()) {
		global $Shopp;

		// Return strings with no options
		switch ($property) {
			case "id": return $id;
			case "name": return $this->name;
			case "link":
			case "url": 
				return (SHOPP_PERMALINKS)?
					$Shopp->shopuri.$this->slug:
					add_query_arg('shopp_pid',$this->product,$Shopp->shopuri);
			case "sku": return $this->sku;
		}
		
		$taxes = false;
		if (isset($options['taxes'])) $taxes = $options['taxes'];
		if ($property == "unitprice" || $property == "total" || $property == "options")
			$taxrate = shopp_taxrate($taxes,$this->taxable);

		// Handle currency values
		$result = "";
		switch ($property) {
			case "unitprice": $result = (float)$this->unitprice+($this->unitprice*$taxrate); break;
			case "total": $result = (float)$this->total+($this->total*$taxrate); break;
			case "tax": $result = (float)$this->tax; break;			
		}
		if (is_float($result)) {
			if (isset($options['currency']) && !value_is_true($options['currency'])) return $result;
			else return money($result);
		}
		
		// Handle values with complex options
		switch ($property) {
			case "quantity": 
				$result = $this->quantity;
				if ($this->type == "Donation" && $this->donation['var'] == "on") return $result;
				if (isset($options['input']) && $options['input'] == "menu") {
					if (!isset($options['value'])) $options['value'] = $this->quantity;
					if (!isset($options['options'])) 
						$values = "1-15,20,25,30,35,40,45,50,60,70,80,90,100";
					else $values = $options['options'];
					
					if (strpos($values,",") !== false) $values = explode(",",$values);
					else $values = array($values);
					$qtys = array();
					foreach ($values as $value) {
						if (strpos($value,"-") !== false) {
							$value = explode("-",$value);
							if ($value[0] >= $value[1]) $qtys[] = $value[0];
							else for ($i = $value[0]; $i < $value[1]+1; $i++) $qtys[] = $i;
						} else $qtys[] = $value;
					}
					$result = '<select name="items['.$id.']['.$property.']">';
					foreach ($qtys as $qty) 
						$result .= '<option'.(($qty == $this->quantity)?' selected="selected"':'').' value="'.$qty.'">'.$qty.'</option>';
					$result .= '</select>';
				} elseif (isset($options['input']) && valid_input($options['input'])) {
					if (!isset($options['size'])) $options['size'] = 5;
					if (!isset($options['value'])) $options['value'] = $this->quantity;
					$result = '<input type="'.$options['input'].'" name="items['.$id.']['.$property.']" id="items-'.$id.'-'.$property.'" '.inputattrs($options).'/>';
				} else $result = $this->quantity;
				break;
			case "remove":
				$label = __("Remove");
				if (isset($options['label'])) $label = $options['label'];
				if (isset($options['class'])) $class = ' class="'.$options['class'].'"';
				else $class = ' class="remove"';
				if (isset($options['input'])) {
					switch ($options['input']) {
						case "button":
							$result = '<button type="submit" name="remove['.$id.']" value="'.$id.'"'.$class.' tabindex="">'.$label.'</button>'; break;
						case "checkbox":
						    $result = '<input type="checkbox" name="remove['.$id.']" value="'.$id.'"'.$class.' tabindex="" title="'.$label.'"/>'; break;
					}
				} else {
					$result = '<a href="'.add_query_arg(array('cart'=>'update','item'=>$id,'quantity'=>0),$Shopp->link('cart')).'"'.$class.'>'.$label.'</a>';
				}
				break;
			case "optionlabel": $result = $this->optionlabel; break;
			case "options":
				$class = "";
				if (isset($options['show']) && 
					strtolower($options['show']) == "selected") 
					return (!empty($this->optionlabel))?
						$options['before'].$this->optionlabel.$options['after']:'';
					
				if (isset($options['class'])) $class = ' class="'.$options['class'].'" ';
				if (count($this->options) > 1) {
					$result .= $options['before'];
					$result .= '<input type="hidden" name="items['.$id.'][product]" value="'.$this->product.'"/>';
					$result .= ' <select name="items['.$id.'][price]" id="items-'.$id.'-price"'.$class.'>';
					$result .= $this->options($this->price,$taxrate);
					$result .= '</select>';
					$result .= $options['after'];
				}
				break;
			case "hasinputs": 
			case "has-inputs": return (count($this->data) > 0); break;
			case "inputs":			
				if (!$this->dataloop) {
					reset($this->data);
					$this->dataloop = true;
				} else next($this->data);

				if (current($this->data)) return true;
				else {
					$this->dataloop = false;
					return false;
				}
				break;
			case "input":
				$data = current($this->data);
				$name = key($this->data);
				if (isset($options['name'])) return $name;
				return $data;
				break;
			case "inputs-list":
			case "inputslist":
				if (empty($this->data)) return false;
				$before = ""; $after = ""; $classes = ""; $excludes = array();
				if (!empty($options['class'])) $classes = ' class="'.$options['class'].'"';
				if (!empty($options['exclude'])) $excludes = explode(",",$options['exclude']);
				if (!empty($options['before'])) $before = $options['before'];
				if (!empty($options['after'])) $after = $options['after'];
				
				$result .= $before.'<ul'.$classes.'>';
				foreach ($this->data as $name => $data) {
					if (in_array($name,$excludes)) continue;
					$result .= '<li><strong>'.$name.'</strong>: '.$data.'</li>';
				}
				$result .= '</ul>'.$after;
				return $result;
				break;
			case "thumbnail":
				if (!empty($options['class'])) $options['class'] = ' class="'.$options['class'].'"';
				if (isset($this->thumbnail)) {
					$img = $this->thumbnail;
					$width = (isset($options['width']))?$options['width']:$img->properties['height'];
					$height = (isset($options['height']))?$options['height']:$img->properties['height'];

					return '<img src="'.$Shopp->imguri.$img->id.'" alt="'.$this->name.' '.$img->datatype.'" width="'.$width.'" height="'.$height.'" '.$options['class'].' />'; break;
				}
			
		}
		if (!empty($result)) return $result;
		
		
		return false;
	}

} // end Item class

?>