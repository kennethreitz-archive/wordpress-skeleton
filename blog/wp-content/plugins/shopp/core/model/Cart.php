<?php
/**
 * Cart class
 * Shopping session handling
 *
 * @author Jonathan Davis
 * @version 1.1
 * @copyright Ingenesis Limited, 23 July, 2009
 * @package shopp
 **/

require("Error.php");
require("Item.php");
require("Customer.php");
require("Billing.php");
require("Shipping.php");

class Cart {

	// properties
	var $_table;
	var $session;
	var $created;
	var $modified;
	var $ip;
	var $data;
	var $path;
	var $contents = array();
	var $shipped = array();
	var $freeshipping = false;
	var $looping = false;
	var $runaway = 0;
	var $updated = false;
	var $retotal = false;
	var $handlers = false;
	
	// methods
	
	/* Cart()
	 * Constructor that creates a new shopping Cart runtime object */
	function Cart () {
		$this->_table = DatabaseObject::tablename('cart');
		
		// Close out any early session calls
		if(session_id()) session_write_close();		
		
		$this->handlers = session_set_save_handler(
			array( &$this, 'open' ),	// Open
			array( &$this, 'close' ),	// Close
			array( &$this, 'load' ),	// Read
			array( &$this, 'save' ),	// Write
			array( &$this, 'unload' ),	// Destroy
			array( &$this, 'trash' )	// Garbage Collection
		);
		register_shutdown_function('session_write_close');
		
		define('SHOPP_SECURE_KEY','shopp_sec_'.COOKIEHASH);
		
		$this->data = new stdClass();				// Session data
		$this->data->Totals = new stdClass();		// Cart totals
		$this->data->Totals->subtotal = 0;			// Subtotal of item totals
		$this->data->Totals->quantity = 0;			// Total quantity of all items
		$this->data->Totals->discount = 0;			// Total discount applied
		$this->data->Totals->shipping = 0;			// Total shipping cost
		$this->data->Totals->tax = 0;				// Total tax cost
		$this->data->Totals->taxrate = 0;			// Current tax rate
		$this->data->Totals->total = 0;				// Grand total

		$this->data->Order = new stdClass();		// Order object
		$this->data->Order->data = array();			// Custom order data registry
		$this->data->Order->Customer = false;		// Order's customer record
		$this->data->Order->Billing = false;		// Order's billing address record
		$this->data->Order->Shipping = false;		// Order's shipping address record

		$this->data->added = 0;						// Recently added item index
		$this->data->login = false;					// Customer logged in flag
		$this->data->secure = false;				// Security flag
		
		$this->data->Errors = new ShoppErrors();	// Tracks errors
		$this->data->Shipping = false;				// Cart has shipped items
		$this->data->ShippingDisabled = false;		// Shipping is disabled
		$this->data->Estimates = false;				// Order needs shipping estimates
		$this->data->Promotions = array();			// Promotions available (cache)
		$this->data->PromosApplied = array();		// Promotions applied to order
		$this->data->PromoCode = false;				// Recent promo code attempt
		$this->data->PromoCodes = array();			// Promo codes applied
		$this->data->PromoCodeResult = false;		// Result of recent promo code attempt
		$this->data->ShipCosts = array();			// Shipping method costs
		$this->data->ShippingPostcode = false;		// Shipping calcs require postcode
		$this->data->ShippingPostcodeError = false;	// Postal code invalid error
		$this->data->Purchase = false;				// Final purchase receipt
		$this->data->Category = array();			// Session related category settings
		$this->data->Search = false;				// Search processed
		
		// Total the cart once, and only if there are changes
		add_action('parse_request',array(&$this,'totals'),99);

	}
			
	/* open()
	 * Initializing routine for the session management. */
	function open ($path,$name) {
		$this->path = $path;
		if (empty($this->path)) $this->path = dirname(realpath(tempnam('','tmp_')));
		$this->trash();	// Clear out any residual session information before loading new data
		if (empty($this->session)) $this->session = session_id();	// Grab our session id
		$this->ip = $_SERVER['REMOTE_ADDR'];						// Save the IP address making the request
		return true;
	}
	
	/* close()
	 * Placeholder function as we are working with a persistant 
	 * database as opposed to file handlers. */
	function close () { return true; }

	/* load()
	 * Gets data from the session data table and loads Member 
	 * objects into the User from the loaded data. */
	function load ($id) {
		global $Shopp;
		$db = DB::get();
		
		if (is_robot()) return true;
		
		$query = "SELECT * FROM $this->_table WHERE session='$this->session'";
		// echo "$query".BR;

		if ($result = $db->query($query)) {
			if (substr($result->data,0,1) == "!") {
				$key = $_COOKIE[SHOPP_SECURE_KEY];
				$readable = $db->query("SELECT AES_DECRYPT('".
										mysql_real_escape_string(
											base64_decode(
												substr($result->data,1)
											)
										)."','$key') AS data");
				$result->data = $readable->data;
			}
			$this->ip = $result->ip;
			$this->data = unserialize($result->data);
			if (empty($result->contents)) $this->contents = array();
			else $this->contents = unserialize($result->contents);
			$this->created = mktimestamp($result->created);
			$this->modified = mktimestamp($result->modified);
		} else {
			$db->query("INSERT INTO $this->_table (session, ip, data, contents, created, modified) 
							VALUES ('$this->session','$this->ip','','',now(),now())");
		}
		
		if (empty($this->data->Errors)) $this->data->Errors = new ShoppErrors();
		if ($Shopp->Settings->get('shipping') == "off") $this->data->ShippingDisabled = true;

		// Read standard session data
		if (file_exists("$this->path/sess_$id"))
			return (string) file_get_contents("$this->path/sess_$id");

		return true;
	}
	
	/* unload()
	 * Deletes the session data from the database, unregisters the 
	 * session and releases all the objects. */
	function unload () {
		$db = DB::get();		
		if (!$db->query("DELETE FROM $this->_table WHERE session='$this->session'")) 
			trigger_error("Could not clear session data.");
		unset($this->session,$this->ip,$this->data,$this->contents);
		return true;
	}
	
	/* save() 
	 * Save the session data to our session table in the database. */
	function save ($id,$session) {
		global $Shopp;
		$db = DB::get();

		if (!$Shopp->Settings->unavailable) {
			$data = $db->escape(addslashes(serialize($this->data)));
			$contents = $db->escape(serialize($this->contents));
			
			if ($this->secured() && is_shopp_secure()) {
				if (!isset($_COOKIE[SHOPP_SECURE_KEY])) $key = $this->securekey();
				else $key = isset($_COOKIE[SHOPP_SECURE_KEY])?$_COOKIE[SHOPP_SECURE_KEY]:'';
				if (!empty($key)) {
					$secure = $db->query("SELECT AES_ENCRYPT('$data','$key') AS data");
					$data = "!".base64_encode($secure->data);
				}
			}
			$query = "UPDATE $this->_table SET ip='$this->ip',data='$data',contents='$contents',modified=now() WHERE session='$this->session'";
			if (!$db->query($query)) 
				trigger_error("Could not save session updates to the database.");

		}

		// Save standard session data for compatibility
		if (!empty($session)) {
			if ($sf = fopen("$this->path/sess_$id","w")) {
				$result = fwrite($sf, $session);
				fclose($sf);
				return $result;
			} return false;
		}
		return true;
	}

	/* trash()
	 * Garbage collection routine for cleaning up old and expired
	 * sessions. */
	function trash () {
		$db = DB::get();
				
		// 1800 seconds = 30 minutes, 3600 seconds = 1 hour
		if (!$db->query("DELETE LOW_PRIORITY FROM $this->_table WHERE UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(modified) > ".SHOPP_SESSION_TIMEOUT)) 
			trigger_error("Could not delete cached session data.");
		return true;
	}
		
	/**
	 * add()
	 * Adds a product as an item to the cart */
	function add ($quantity,&$Product,&$Price,$category,$data=array()) {

		$NewItem = new Item($Product,$Price,$category,$data);
		if (!$NewItem->valid()) return false;
		
		if (($item = $this->hasitem($NewItem)) !== false) {
			$this->contents[$item]->add($quantity);
			$this->added = $this->contents[$item];
			$this->data->added = $item;
		} else {
			$NewItem->quantity($quantity);
			$this->contents[] = $NewItem;
			$this->data->added = count($this->contents)-1;
			$this->added = $this->contents[$this->data->added];
			if ($NewItem->shipping && !$this->data->ShippingDisabled) 
				$this->data->Shipping = true;
		}
		do_action_ref_array('shopp_cart_add_item',array(&$NewItem));

		$this->updated();
		return true;
	}
	
	/**
	 * remove()
	 * Removes an item from the cart */
	function remove ($item) {
		array_splice($this->contents,$item,1);
		$this->updated();
		return true;
	}
	
	/**
	 * update()
	 * Changes the quantity of an item in the cart */
	function update ($item,$quantity) {
		if (empty($this->contents)) return false;
		if ($quantity == 0) return $this->remove($item);
		elseif (isset($this->contents[$item])) {
			$this->contents[$item]->quantity($quantity);
			if ($this->contents[$item]->quantity == 0) $this->remove($item);
			$this->updated();
		}
		return true;
	}
	
	/**
	 * updated()
	 * Changes the quantity of an item in the cart */
	function updated () {
		$this->updated = true;
	}
	
	/**
	 * clear()
	 * Empties the contents of the cart */
	function clear () {
		$this->contents = array();
		return true;
	}
	
	/**
	 * reset()
	 * Resets the entire session */
	function reset () {
		$this->unload();
		$this->session = session_regenerate_id();
	}
	
	/**
	 * change()
	 * Changes an item to a different product/price variation */
	function change ($item,&$Product,$pricing) {
		// Don't change anything if everything is the same
		if ($this->contents[$item]->product == $Product->id &&
				$this->contents[$item]->price == $pricing) return true;

		// If the updated product and price variation match
		// add the updated quantity of this item to the other item
		// and remove this one
		foreach ($this->contents as $id => $thisitem) {
			if ($thisitem->product == $Product->id && $thisitem->price == $pricing) {
				$this->update($id,$thisitem->quantity+$this->contents[$item]->quantity);
				$this->remove($item);
				$this->updated();
				return true;
			}
		}

		// No existing item, so change this one
		$qty = $this->contents[$item]->quantity;
		$category = $this->contents[$item]->category;
		$this->contents[$item] = new Item($Product,$pricing,$category);
		$this->contents[$item]->quantity($qty);
		$this->updated();
		if ($this->contents[$item]->shipping && !$this->data->ShippingDisabled) 
			$this->data->Shipping = true;
		
		return true;
	}
	
	/**
	 * hasitem()
	 * Determines if a specified item is already in this cart */
	function hasitem($NewItem) {
		$i = 0;
		foreach ($this->contents as $Item) {
			if ($Item->product == $NewItem->product && 
					$Item->price == $NewItem->price && 
					(empty($NewItem->data) || 
					(serialize($Item->data) == serialize($NewItem->data)))) 
				return $i;
			$i++;
		}
		return false;
	}
	
	/**
	 * shipzone()
	 * Sets the shipping address location 
	 * for calculating shipping estimates */
	function shipzone ($data) {
		if (!isset($this->data->Order->Shipping))
			$this->data->Order->Shipping = new Shipping();
		$this->data->Order->Shipping->updates($data);

		// Update state if postcode changes for tax updates
		if (isset($data['postcode']))
			$this->data->Order->Shipping->postarea();

		if (!isset($this->data->Order->Billing))
			$this->data->Order->Billing = new Billing();
		$this->data->Order->Billing->updates($data);

		if (isset($data['region'])) {
			$this->data->Order->Shipping->region = $data['region'];
			$this->data->Order->Billing->region = $data['region'];
		}

		if (!empty($data)) $this->updated();
	}
			
	/**
	 * shipping()
	 * Calulates shipping costs based on the contents
	 * of the cart and the currently available shipping
	 * location set with shipzone() */
	function shipping () {
		if (!$this->data->Order->Shipping) return false;
		if ($this->freeshipping) return 0;
         
		global $Shopp;
        
		$ShipCosts = &$this->data->ShipCosts;
		$Shipping = $this->data->Order->Shipping;
		$base = $Shopp->Settings->get('base_operations');
		$handling = $Shopp->Settings->get('order_shipfee');
		$methods = $Shopp->Settings->get('shipping_rates');
		if (!is_array($methods)) return 0;

		if (empty($Shipping->country)) $Shipping->country = $base['country'];
		
		if (!$this->retotal) {
			
			$fees = 0;
			
			// Calculate any product-specific shipping fee markups
			$shipflag = false;
			foreach ($this->contents as $Item) {
				if ($Item->shipping) $shipflag = true;
				if ($Item->shipfee > 0) $fees += ($Item->quantity * $Item->shipfee);
			}
			if ($shipflag) $this->data->Shipping = true;
			else {
				$this->data->Shipping = false;
				return 0;
			}
		
			// Add order handling fee
			if ($handling > 0) $fees += $handling;

			$estimate = false;
			foreach ($methods as $id => $option) {
				if (isset($option['postcode-required'])) {
					$this->data->ShippingPostcode = true;
					if (empty($Shipping->postcode)) {
						$this->data->ShippingPostcodeError = true;
						new ShoppError(__('A postal code for calculating shipping estimates and taxes is required before you can proceed to checkout.','Shopp','cart_required_postcode',SHOPP_ERR));
						return null;
					} else $this->data->ShippingPostcodeError = false;
				} else {
					$this->data->ShippingPostcode = false;
					$this->data->ShippingPostcodeError = false;	
				}
			
				if ($Shipping->country == $base['country']) {
					// Use country/domestic region
					if (isset($option[$base['country']]))
						$column = $base['country'];  // Use the country rate
					else $column = $Shipping->postarea(); // Try to get domestic regional rate
				} else if (isset($option[$Shipping->region])) {
					// Global region rate
					$column = $Shipping->region;
				} else {
					// Worldwide shipping rate, last rate entry
					end($option);
					$column = key($option);
				}

				list($ShipCalcClass,$process) = explode("::",$option['method']);
				if (isset($Shopp->ShipCalcs->modules[$ShipCalcClass]))
					$estimated = apply_filters('shopp_shipping_estimate', $Shopp->ShipCalcs->modules[$ShipCalcClass]->calculate(
						$this, $fees, $option, $column));

				if ($estimated === false) continue; // Skip the cost estimates
				if (!$estimate || $estimated['cost'] < $estimate['cost'])
					$estimate = $estimated; // Get lowest estimate

			} // end foreach ($methods)         

        } // end if (!$this->retotal)

		if (!isset($ShipCosts[$this->data->Order->Shipping->method]))
			$this->data->Order->Shipping->method = false;
		
		if (!empty($this->data->Order->Shipping->method))
			return $ShipCosts[$this->data->Order->Shipping->method]['cost'];
		
		$this->data->Order->Shipping->method = $estimate['name'];
		
		return $estimate['cost'];
	}
	
	/**
	 * promotions()
	 * Matches, calculates and applies promotion discounts */
	function promotions () {
		global $Shopp;
		$db = DB::get();
		$limit = $Shopp->Settings->get('promo_limit');

		// Load promotions if they've not yet been loaded
		if (empty($this->data->Promotions)) {
			$promo_table = DatabaseObject::tablename(Promotion::$table);
			// Add date-based lookup too
			$this->data->Promotions = $db->query("SELECT * FROM $promo_table WHERE scope='Order' AND ((status='enabled' AND UNIX_TIMESTAMP(starts) > 0 AND UNIX_TIMESTAMP(starts) < UNIX_TIMESTAMP() AND UNIX_TIMESTAMP(ends) > UNIX_TIMESTAMP()) OR status='enabled')",AS_ARRAY);
		}

		$PromoCodeFound = false; $PromoCodeExists = false; $PromoLimit = false;
		$this->data->PromosApplied = array();
		foreach ($this->data->Promotions as &$promo) {
			if (!is_array($promo->rules))
				$promo->rules = unserialize($promo->rules);
			
			// Add quantity rule automatically for buy x get y promos
			if ($promo->type == "Buy X Get Y Free") {
				$promo->search = "all";
				if ($promo->rules[count($promo->rules)-1]['property'] != "Item quantity") {
					$qtyrule = array(
						'property' => 'Item quantity',
						'logic' => "Is greater than",
						'value' => $promo->buyqty);
					$promo->rules[] = $qtyrule;
				}
			}
			
			$items = array();
			
			$match = false;
			$rulematches = 0;
			foreach ($promo->rules as $rule) {
				$rulematch = false;
				switch($rule['property']) {
					case "Item name": 
						foreach ($this->contents as $id => &$Item) {
							if (Promotion::match_rule($Item->name,$rule['logic'],$rule['value'], $rule['property'])) {
								$items[$id] = &$Item;
								$rulematch = true;
							}
						}
						break;
					case "Item quantity":
						foreach ($this->contents as $id => &$Item) {
							if (Promotion::match_rule(number_format($Item->quantity,0),$rule['logic'],$rule['value'], $rule['property'])) {
								$items[$id] = &$Item;
								$rulematch = true;
							}
						}
						break;
					case "Item amount":
						foreach ($this->contents as $id => &$Item) {
							if (Promotion::match_rule(number_format($Item->total,2),$rule['logic'],$rule['value'], $rule['property'])) {
								$items[$id] = &$Item;
								$rulematch = true;
							}
						}
						break;
					case "Total quantity":
						if (Promotion::match_rule(number_format($this->data->Totals->quantity,0),$rule['logic'],$rule['value'], $rule['property'])) {
							$rulematch = true;
						}
						break;
					case "Shipping amount": 
						if (Promotion::match_rule(number_format($this->data->Totals->shipping,2),$rule['logic'],$rule['value'], $rule['property'])) {
							$rulematch = true;
						}
						break;
					case "Subtotal amount": 
						if (Promotion::match_rule(number_format($this->data->Totals->subtotal,2),$rule['logic'],$rule['value'], $rule['property'])) {
							$rulematch = true;
						}
						break;
					case "Promo code":
						// Match previously applied codes
						if (is_array($this->data->PromoCodes) && in_array($rule['value'],$this->data->PromoCodes)) {							
							$rulematch = true;
							break;
						}
						// Match a new code
						if (!empty($this->data->PromoCode)) {
							if (Promotion::match_rule($this->data->PromoCode,$rule['logic'],$rule['value'], $rule['property'])) {
 								if (is_array($this->data->PromoCodes) && 
									!in_array($this->data->PromoCode, $this->data->PromoCodes)) {
									$this->data->PromoCodes[] = $rule['value'];
									$PromoCodeFound = $rule['value'];
								} else $PromoCodeExists = true;
								$this->data->PromoCode = false;
								$rulematch = true;
							}
						}
						break;
				}

				if ($rulematch && $promo->search == "all") $rulematches++;
				if ($rulematch && $promo->search == "any") {
					$match = true;
					break; // One matched, no need to match any more
				}
			} // end foreach ($promo->rules)

			if ($promo->search == "all" && $rulematches == count($promo->rules))
				$match = true;

			// Everything matches up, apply the promotion
			if ($match && !$PromoLimit) {
				// echo "Matched $promo->name".BR;
				if (!empty($items)) {
					$freeshipping = 0;
					// Apply promo calculation to specific cart items
					foreach ($items as $item) {
						switch ($promo->type) {
							case "Percentage Off": $this->data->Totals->discount += $item->total*($promo->discount/100); break;
							case "Amount Off": $this->data->Totals->discount += $promo->discount; break;
							case "Buy X Get Y Free": $this->data->Totals->discount += floor($item->quantity / ($promo->buyqty + $promo->getqty))*($item->unitprice); break;
							case "Free Shipping": $freeshipping++; break;
						}
					}
					if ($freeshipping == count($this->contents) && $promo->scope == "Order") $this->freeshipping = true;
					else $this->freeshipping = false;
				} else {
					// Apply promo calculation to entire order
					switch ($promo->type) {
						case "Percentage Off": $this->data->Totals->discount += $this->data->Totals->subtotal*($promo->discount/100); break;
						case "Amount Off": $this->data->Totals->discount += $promo->discount; break;
						case "Free Shipping": $this->freeshipping = true; break;
					}
				}
				$this->data->PromosApplied[] = $promo;
				if ($limit > 0 && count($this->data->PromosApplied)+1 > $limit) {
					$PromoLimit = true;
					break;
				}
			}
			
			if ($match && $promo->exclusive == "on") break;
			
		} // end foreach ($Promotions)

		// Promo code found, but ran into promotion limits
		if (!empty($this->data->PromoCode) && $PromoLimit) { 
			$this->data->PromoCodeResult = __("No additional codes can be applied.","Shopp");
			$this->data->PromoCodes = array_diff($this->data->PromoCodes,array($PromoCodeFound));
			$this->data->PromoCode = false;
		}

		// Promo code not found
		if (!empty($this->data->PromoCode) && !$PromoCodeFound && !$PromoCodeExists) {
			$this->data->PromoCodeResult = $this->data->PromoCode.' '.__("is not a valid code.","Shopp");
			$this->data->PromoCodes = array_diff($this->data->PromoCodes,array($this->data->PromoCode));
			$this->data->PromoCode = false;
		}
	}

	/**
	 * taxrate()
	 * Determines the taxrate based on the currently
	 * available shipping information set by shipzone() */
	function taxrate () {
		global $Shopp;
		if ($Shopp->Settings->get('taxes') == "off") return false;
		
		$taxrates = $Shopp->Settings->get('taxrates');
		$base = $Shopp->Settings->get('base_operations');
		if (!is_array($taxrates)) return false;

		if (!empty($this->data->Order->Shipping->country)) $country = $this->data->Order->Shipping->country;
		elseif (!empty($this->data->Order->Billing->country)) $country = $this->data->Order->Billing->country;
		else $country = $base['country'];

		if (!empty($this->data->Order->Shipping->state)) $zone = $this->data->Order->Shipping->state;
		elseif (!empty($this->data->Order->Billing->state)) $zone = $this->data->Order->Billing->state;
		else $zone = $base['zone'];
		
		$global = false;
		foreach ($taxrates as $setting) {
			// Grab the global setting if found
			if ($setting['country'] == "*") {
				$global = $setting;
				continue;
			}
			
			if (isset($setting['zone'])) {
				if ($country == $setting['country'] &&
					$zone == $setting['zone'])
						return apply_filters('shopp_cart_taxrate',$setting['rate']/100);
			} elseif ($country == $setting['country']) {
				return apply_filters('shopp_cart_taxrate',$setting['rate']/100);
			}
		}
		
		if ($global) return apply_filters('shopp_cart_taxrate',$global['rate']/100);
		
	}
	
	/**
	 * totals()
	 * Calculates subtotal, shipping, tax and 
	 * order total amounts */
	function totals () {
		global $Shopp;
		if (!$this->retotal && !$this->updated) return true;

		$shippingTaxed = ($Shopp->Settings->get('tax_shipping') == "on");
		$Totals =& $this->data->Totals;
		$Totals->quantity = 0;
		$Totals->subtotal = 0;
		$Totals->discount = 0;
		$Totals->shipping = 0;
		$Totals->taxed = 0;
		$Totals->tax = 0;
		$Totals->total = 0;
        
	    $Totals->taxrate = $this->taxrate();
		
		$freeshipping = true;	// Assume free shipping for the cart unless proven wrong
		foreach ($this->contents as $key => $Item) {

			// Add the item to the shipped list
			if ($Item->shipping && !$Item->freeshipping) $this->shipped[$key] = $Item;
			
			// Item does not have free shipping, 
			// so the cart shouldn't have free shipping
			if (!$Item->freeshipping) $freeshipping = false;

			$Totals->quantity += $Item->quantity;
			$Totals->subtotal +=  $Item->total;
			
			// Tabulate the taxable total to be calculated after discounts
			if ($Item->taxable && $Totals->taxrate > 0) 
				$Totals->taxed += $Item->total;
		}
		$this->freeshipping = $freeshipping;
		if ($this->data->ShippingDisabled) $this->freeshipping = false;
		
		// Calculate discounts
		$this->promotions();
		$discount = ($Totals->discount > $Totals->subtotal)?$Totals->subtotal:$Totals->discount;

		// Calculate shipping
		if (!$this->data->ShippingDisabled && $this->data->Shipping && !$this->freeshipping) 
			$Totals->shipping = $this->shipping();

		// Calculate taxes
		if ($discount > $Totals->taxed) $Totals->taxed = 0;
		else $Totals->taxed -= $discount;
		if($shippingTaxed) $Totals->taxed += $Totals->shipping;
		$Totals->tax = round($Totals->taxed*$Totals->taxrate,2);

		// Calculate final totals
		$Totals->total = round($Totals->subtotal - round($discount,2) + 
			$Totals->shipping + $Totals->tax,2);

		do_action_ref_array('shopp_cart_retotal',array(&$Totals));
	}
	
	/**
	 * logins ()
	 * Handle login processing */
	function logins () {
		global $Shopp;
		if (!$this->data->Order->Customer) {
			$this->data->Order->Customer = new Customer();
			$this->data->Order->Billing = new Billing();
			$this->data->Order->Shipping = new Shipping();
		}
		
		$authentication = $Shopp->Settings->get('account_system');

		if (isset($_GET['acct']) && isset($this->data->Order->Customer) 
			&& $_GET['acct'] == "logout") {
				if ($authentication == "wordpress" && $this->data->login)
					add_action('shopp_logout','wp_clear_auth_cookie');					
				return $this->logout();
		}

		switch ($authentication) {
			case "wordpress":
				if ($this->data->login) add_action('wp_logout',array(&$this,'logout'));

				// See if the wordpress user is already logged in
				$user = wp_get_current_user();

				if (!empty($user->ID) && !$this->data->login) {
					if ($Account = new Customer($user->ID,'wpuser')) {
						$this->loggedin($Account);
						$this->data->Order->Customer->wpuser = $user->ID;
						break;
					}
				}
				
				if (empty($_POST['process-login'])) return false;
				
				if (!empty($_POST['account-login'])) {
					if (strpos($_POST['account-login'],'@') !== false) $mode = "email";
					else $mode = "loginname";
					$loginname = $_POST['account-login'];
				} else {
					new ShoppError(__('You must provide a valid login name or email address to proceed.'), 'missing_account', SHOPP_AUTH_ERR);
				}
									
				if ($loginname) {
					$this->auth($loginname,$_POST['password-login'],$mode);			
				}				
				break;
			case "shopp":
				if (!isset($_POST['process-login'])) return false;
				if ($_POST['process-login'] != "true") return false;
				$mode = "loginname";
				if (!empty($_POST['account-login']) && strpos($_POST['account-login'],'@') !== false)
					$mode = "email";
				$this->auth($_POST['account-login'],$_POST['password-login'],$mode);
				break;
		}

	}
	
	/**
	 * auth ()
	 * Authorize login credentials */
	function auth ($id,$password,$type='email') {
		global $Shopp;
		$db = DB::get();
		$authentication = $Shopp->Settings->get('account_system');
		switch($authentication) {
			case "shopp":
				$Account = new Customer($id,'email');

				if (empty($Account)) {
					new ShoppError(__("No customer account was found with that email.","Shopp"),'invalid_account',SHOPP_AUTH_ERR);
					return false;
				} 

				if (!wp_check_password($password,$Account->password)) {
					new ShoppError(__("The password is incorrect.","Shopp"),'invalid_password',SHOPP_AUTH_ERR);
					return false;
				}	
						
				break;
				
  		case "wordpress":
			if($type == 'email'){
				$user = get_user_by_email($id);
				if ($user) $loginname = $user->user_login;
				else {
					new ShoppError(__("No customer account was found with that email.","Shopp"),'invalid_account',SHOPP_AUTH_ERR);
					return false;
				}
			} else $loginname = $id;
			$user = wp_authenticate($loginname,$password);
			if (!is_wp_error($user)) {
				wp_set_auth_cookie($user->ID, false, $Shopp->secure);
				do_action('wp_login', $loginname);

				if ($Account = new Customer($user->ID,'wpuser')) {
					$this->loggedin($Account);
					$this->data->Order->Customer->wpuser = $user->ID;
					add_action('wp_logout',array(&$this,'logout'));
				}
				return true;
			} else { // WordPress User Authentication failed
				$_e = $user->get_error_code();
				if($_e == 'invalid_username') new ShoppError(__("No customer account was found with that login.","Shopp"),'invalid_account',SHOPP_AUTH_ERR);
				else if($_e == 'incorrect_password') new ShoppError(__("The password is incorrect.","Shopp"),'invalid_password',SHOPP_AUTH_ERR);
				else new ShoppError(__('Unknown login error: ').$_e,false,SHOPP_AUTH_ERR);
				return false;
			}
  			break;
			default: return false;
		}

		$this->loggedin($Account);
		
	}
	
	/**
	 * loggedin()
	 * Initialize login data */
	function loggedin ($Account) {
		$this->data->login = true;
		$this->data->Order->Customer = $Account;
		unset($this->data->Order->Customer->password);
		$this->data->Order->Billing = new Billing($Account->id,'customer');
		$this->data->Order->Billing->card = "";
		$this->data->Order->Billing->cardexpires = "";
		$this->data->Order->Billing->cardholder = "";
		$this->data->Order->Billing->cardtype = "";
		$this->data->Order->Shipping = new Shipping($Account->id,'customer');
		if (empty($this->data->Order->Shipping->id))
			$this->data->Order->Shipping->copydata($this->data->Order->Billing);
		do_action_ref_array('shopp_login',array(&$Account));
	}
	
	/**
	 * logout()
	 * Clear the session account data */
	function logout () {
		do_action('shopp_logout');
		$this->data->login = false;
		$this->data->Order->wpuser = false;
		$this->data->Order->Customer->id = false;
		$this->data->Order->Billing->id = false;
		$this->data->Order->Billing->customer = false;
		$this->data->Order->Shipping->id = false;
		$this->data->Order->Shipping->customer = false;
		session_commit();
	}
	
	/**
	 * secured()
	 * Check or set the security setting for the cart */
	function secured ($setting=null) {
		if (is_null($setting)) return $this->data->secure;
		$this->data->secure = ($setting);
		if (SHOPP_DEBUG) {
			if ($this->data->secure) new ShoppError('Switching the cart to secure mode.',false,SHOPP_DEBUG_ERR);
			else new ShoppError('Switching the cart to unsecure mode.',false,SHOPP_DEBUG_ERR);
		}
		return $this->data->secure;
	}

	/**
	 * securekey()
	 * Generate the security key */
	function securekey () {
		global $Shopp;
		require_once(ABSPATH . WPINC . '/pluggable.php');
		if (!is_shopp_secure()) return false;
		$expiration = time()+SHOPP_SESSION_TIMEOUT;
		if (defined('SECRET_AUTH_KEY') && SECRET_AUTH_KEY != '') $key = SECRET_AUTH_KEY;
		else $key = md5(serialize($this->data).time());
		$content = hash_hmac('sha256', $this->session . '|' . $expiration, $key);
		if ( version_compare(phpversion(), '5.2.0', 'ge') )
			setcookie(SHOPP_SECURE_KEY,$content,0,'/','',true,true);
		else setcookie(SHOPP_SECURE_KEY,$content,0,'/','',true);
		return $content;
	}
	
	/**
	 * request()
	 * Processes cart requests and updates the cart
	 * accordingly */
	function request () {
		global $Shopp;
		do_action('shopp_cart_request');

		if (isset($_REQUEST['checkout'])) shopp_redirect($Shopp->link('checkout',true));
		
		if (isset($_REQUEST['shopping'])) shopp_redirect($Shopp->link('catalog'));
		
		if (isset($_REQUEST['shipping'])) {
			$countries = $Shopp->Settings->get('countries');
			$regions = $Shopp->Settings->get('regions');
			$_REQUEST['shipping']['region'] = $regions[$countries[$_REQUEST['shipping']['country']]['region']];
			if (!empty($_REQUEST['shipping']['postcode'])) // Protect input field from XSS
				$_REQUEST['shipping']['postcode'] = attribute_escape($_REQUEST['shipping']['postcode']);
			unset($countries,$regions);
			$this->shipzone($_REQUEST['shipping']);
		} else if (!isset($this->data->Order->Shipping->country)) {
			$base = $Shopp->Settings->get('base_operations');
			$_REQUEST['shipping']['country'] = $base['country'];
			$this->shipzone($_REQUEST['shipping']);
		}

		if (!empty($_REQUEST['promocode'])) {
			$this->data->PromoCodeResult = "";
			if (!in_array($_REQUEST['promocode'],$this->data->PromoCodes)) {
				$this->data->PromoCode = attribute_escape($_REQUEST['promocode']); // Protect from XSS
				$this->updated();
			} else $this->data->PromoCodeResult = __("That code has already been applied.","Shopp");
		}
		
		if (isset($_REQUEST['remove'])) $_REQUEST['cart'] = "remove";
		if (isset($_REQUEST['update'])) $_REQUEST['cart'] = "update";
		if (isset($_REQUEST['empty'])) $_REQUEST['cart'] = "empty";
		
		if (!isset($_REQUEST['quantity'])) $_REQUEST['quantity'] = 1;

		switch($_REQUEST['cart']) {
			case "add":			
				if (isset($_REQUEST['product'])) {
					
					$quantity = (empty($product['quantity']) && 
						$product['quantity'] !== 0)?1:$product['quantity']; // Add 1 by default
					$Product = new Product($_REQUEST['product']);
					$pricing = false;
					if (!empty($_REQUEST['options']) && !empty($_REQUEST['options'][0])) 
						$pricing = $_REQUEST['options'];
					else $pricing = $_REQUEST['price'];
					
					$category = false;
					if (!empty($_REQUEST['category'])) $category = $_REQUEST['category'];
					
					if (isset($_REQUEST['data'])) $data = $_REQUEST['data'];
					else $data = array();

					if (isset($_REQUEST['item'])) $result = $this->change($_REQUEST['item'],$Product,$pricing);
					else $result = $this->add($quantity,$Product,$pricing,$category,$data);
					
				}
				
				if (isset($_REQUEST['products']) && is_array($_REQUEST['products'])) {
					foreach ($_REQUEST['products'] as $id => $product) {
						$quantity = (empty($product['quantity']) && 
							$product['quantity'] !== 0)?1:$product['quantity']; // Add 1 by default
						$Product = new Product($product['product']);
						$pricing = false;
						if (!empty($product['options']) && !empty($product['options'][0])) 
							$pricing = $product['options'];
						elseif (isset($product['price'])) $pricing = $product['price'];
						
						$category = false;
						if (!empty($product['category'])) $category = $product['category'];

						if (!empty($product['data'])) $data = $product['data'];
						else $data = array();

						if (!empty($Product->id)) {
							if (isset($product['item'])) $result = $this->change($product['item'],$Product,$pricing);
							else $result = $this->add($quantity,$Product,$pricing,$category,$data);
						}
					}
					
				}
				break;
			case "remove":
				if (!empty($this->contents)) $this->remove(current($_REQUEST['remove']));
				break;
			case "empty":
				$this->clear();
				break;
			default:
				if (isset($_REQUEST['item']) && isset($_REQUEST['quantity'])) {
					$this->update($_REQUEST['item'],$_REQUEST['quantity']);
				} elseif (!empty($_REQUEST['items'])) {
					foreach ($_REQUEST['items'] as $id => $item) {
						if (isset($item['quantity'])) {
							$item['quantity'] = ceil(preg_replace('/[^\d\.]+/','',$item['quantity']));
							if (!empty($item['quantity'])) $this->update($id,$item['quantity']);
						    if (isset($_REQUEST['remove'][$id])) $this->remove($_REQUEST['remove'][$id]);
						}
						if (isset($item['product']) && isset($item['price']) && 
							$item['product'] == $this->contents[$id]->product &&
							$item['price'] != $this->contents[$id]->price) {
							$Product = new Product($item['product']);
							$this->change($id,$Product,$item['price']);
						}
					}
				}
		}

		do_action('shopp_cart_updated',$this);
	}

	/**
	 * ajax()
	 * Handles AJAX-based cart request responses */
	function ajax () { 
		global $Shopp;
		
		if ($_REQUEST['response'] == "html") {
			echo $this->tag('sidecart');
			exit();
		}
		$AjaxCart = new StdClass();
		$AjaxCart->url = $Shopp->link('cart');
		$AjaxCart->Totals = clone($this->data->Totals);
		$AjaxCart->Contents = array();
		foreach($this->contents as $item) {
			$cartitem = clone($item);
			unset($cartitem->options);
			$AjaxCart->Contents[] = $cartitem;
		}
		if (isset($this->data->added))
			$AjaxCart->Item = clone($this->contents[$this->data->added]);
		else $AjaxCart->Item = new Item();
		unset($AjaxCart->Item->options);
		
		echo json_encode($AjaxCart);
		exit();
	}
	
	/**
	 * validate()
	 * Validate checkout form order data before processing */
	function validate () {
		global $Shopp;
		$authentication = $Shopp->Settings->get('account_system');
		
		if (empty($_POST['firstname']))
			return new ShoppError(__('You must provide your first name.','Shopp'),'cart_validation');

		if (empty($_POST['lastname']))
			return new ShoppError(__('You must provide your last name.','Shopp'),'cart_validation');

		$rfc822email =	'([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d'.
						'\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e'.
						'\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*'.
						'\\x22))*\\x40([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+'.
						'|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d)(\\x2e([^\\x00-\\x20\\x22\\x28'.
						'\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]'.
						'|\\x5c[\\x00-\\x7f])*\\x5d))*';
		if(!preg_match("!^$rfc822email$!", $_POST['email']))
			return new ShoppError(__('You must provide a valid e-mail address.','Shopp'),'cart_validation');
			
		if ($authentication == "wordpress" && !$this->data->login) {
			require_once(ABSPATH."/wp-includes/registration.php");
			
			// Validate possible wp account names for availability
			if(isset($_POST['login'])){
				if(username_exists($_POST['login'])) 
					return new ShoppError(__('The login name you provided is not available.  Try logging in if you have previously created an account.'), 'cart_validation');
			} else { // need to find a usuable login
				list($handle,$domain) = explode("@",$_POST['email']);
				if(!username_exists($handle)) $_POST['login'] = $handle;
				
				$handle = $_POST['firstname'].substr($_POST['lastname'],0,1);				
				if(!isset($_POST['login']) && !username_exists($handle)) $_POST['login'] = $handle;
				
				$handle = substr($_POST['firstname'],0,1).$_POST['lastname'];
				if(!isset($_POST['login']) && !username_exists($handle)) $_POST['login'] = $handle;
				
				$handle .= rand(1000,9999);
				if(!isset($_POST['login']) && !username_exists($handle)) $_POST['login'] = $handle;
				
				if(!isset($_POST['login'])) return new ShoppError(__('A login is not available for creation with the information you provided.  Please try a different email address or name, or try logging in if you previously created an account.'),'cart_validation');
			}
			if(SHOPP_DEBUG) new ShoppError('Login set to '. $_POST['login'] . ' for WordPress account creation.',false,SHOPP_DEBUG_ERR);			 
			$ExistingCustomer = new Customer($_POST['email'],'email');
			if (email_exists($_POST['email']) || !empty($ExistingCustomer->id))
				return new ShoppError(__('The email address you entered is already in use. Try logging in if you previously created an account, or enter another email address to create your new account.','Shopp'),'cart_validation');
		} elseif ($authentication == "shopp"  && !$this->data->login) {
			$ExistingCustomer = new Customer($_POST['email'],'email');
			if (!empty($ExistingCustomer->id)) 
				return new ShoppError(__('The email address you entered is already in use. Try logging in if you previously created an account, or enter another email address to create a new account.','Shopp'),'cart_validation');
		}

		// Validate WP account
		if (isset($_POST['login']) && empty($_POST['login']))
			return new ShoppError(__('You must enter a login name for your account.','Shopp'),'cart_validation');

		if (isset($_POST['login'])) {
			require_once(ABSPATH."/wp-includes/registration.php");
			if (username_exists($_POST['login']))
				return new ShoppError(__('The login name you provided is already in use. Try logging in if you previously created that account, or enter another login name for your new account.','Shopp'),'cart_validation');
		}

		if (isset($_POST['password'])) {
			if (empty($_POST['password']) || empty($_POST['confirm-password']))
				return new ShoppError(__('You must provide a password for your account and confirm it to ensure correct spelling.','Shopp'),'cart_validation');
			if ($_POST['password'] != $_POST['confirm-password']) {
				$_POST['password'] = ""; $_POST['confirm-password'] = "";
				return new ShoppError(__('The passwords you entered do not match. Please re-enter your passwords.','Shopp'),'cart_validation');				
			}
		}

		if (empty($_POST['billing']['address']) || strlen($_POST['billing']['address']) < 4) 
			return new ShoppError(__('You must enter a valid street address for your billing information.','Shopp'),'cart_validation');

		if (empty($_POST['billing']['postcode'])) 
			return new ShoppError(__('You must enter a valid postal code for your billing information.','Shopp'),'cart_validation');

		if (empty($_POST['billing']['country'])) 
			return new ShoppError(__('You must select a country for your billing information.','Shopp'),'cart_validation');

		// Skip validating billing details for free purchases 
		// and remote checkout systems
		if ((int)$this->data->Totals->total == 0
			|| !empty($_GET['shopp_xco'])) return apply_filters('shopp_validate_checkout',true);

		if (empty($_POST['billing']['card'])) 
			return new ShoppError(__('You did not provide a credit card number.','Shopp'),'cart_validation');

		if (empty($_POST['billing']['cardtype'])) 
			return new ShoppError(__('You did not select a credit card type.','Shopp'),'cart_validation');
			
		// credit card validation
		switch(strtolower($_POST['billing']['cardtype'])) {
			case "american express":
			case "amex": $pattern = '/^3[4,7]\d{13}$/'; break;
			case "diner's club":
			case "diners club": $pattern = '/^3[0,6,8]\d{12}$/'; break;
			case "discover": $pattern = '/^6011-?\d{4}-?\d{4}-?\d{4}$/'; break;
			case "mastercard": $pattern = '/^5[1-5]\d{2}-?\d{4}-?\d{4}-?\d{4}$/'; break;
			case "visa": $pattern = '/^4\d{3}-?\d{4}-?\d{4}-?\d{4}$/'; break;
			default: $pattern = false;
		}
		if ($pattern && !preg_match($pattern,$_POST['billing']['card'])) 
			return new ShoppError(__('The credit card number you provided is invalid.','Shopp'),'cart_validation');

		// credit card checksum validation
		$cs = 0;
		$cc = str_replace("-","",$_POST['billing']['card']);
		$code = strrev(str_replace("-","",$_POST['billing']['card']));
		for ($i = 0; $i < strlen($code); $i++) {
			$d = intval($code[$i]);
			if ($i & 1) $d *= 2;
			$cs += $d % 10;
			if ($d > 9) $cs += 1;
		}
		if ($cs % 10 != 0)
			return new ShoppError(__('The credit card number you provided is not valid.','Shopp'),'cart_validation');
			
		if (empty($_POST['billing']['cardexpires-mm'])) 
			return new ShoppError(__('You did not enter the month the credit card expires.','Shopp'),'cart_validation');

		if (empty($_POST['billing']['cardexpires-yy'])) 
			return new ShoppError(__('You did not enter the year the credit card expires.','Shopp'),'cart_validation');

		if (!empty($_POST['billing']['cardexpires-mm']) && !empty($_POST['billing']['cardexpires-yy']) 
		 	&& $_POST['billing']['cardexpires-mm'] < date('n') && $_POST['billing']['cardexpires-yy'] <= date('y')) 
			return new ShoppError(__('The credit card expiration date you provided has already expired.','Shopp'),'cart_validation');
		
		if (strlen($_POST['billing']['cardholder']) < 2) 
			return new ShoppError(__('You did not enter the name on the credit card you provided.','Shopp'),'cart_validation');
		
		if (strlen($_POST['billing']['cvv']) < 3) 
			return new ShoppError(__('You did not enter a valid security ID for the card you provided. The security ID is a 3 or 4 digit number found on the back of the credit card.','Shopp'),'cart_validation');
				
		return apply_filters('shopp_validate_checkout',true);
	}

	/**
	 * validorder()
	 * Validates order data during checkout processing to verify that sufficient information exists to process. */
	function validorder () {		
		$Order = $this->data->Order;
		$Customer = $Order->Customer;
		$Shipping = $this->data->Order->Shipping;

		if(empty($this->contents)) return false;  // No items
		if(empty($Order)) return false;  // No order data
		if(!$Customer) return false; // No Customer

		// Always require name and email
		if( empty($Customer->firstname) || empty($Customer->lastname)) return false;
		if( empty($Customer->email) ) return false;

		// Check for shipped items but no Shipping information
		if ($this->data->Shipping) {
			if(empty($Shipping->address)) return false;
			if(empty($Shipping->city)) return false;
			if(empty($Shipping->country)) return false;
			if(empty($Shipping->postcode)) return false;
		}
		return true;
	}
	
	/**
	 * orderisfree()
	 * Determines if the current order has no cost */
	function orderisfree() {
		$status = (count($this->contents) > 0 && number_format($this->data->Totals->total,2) == 0)?true:false;
		return apply_filters('shopp_free_order',$status);
	}
	
	function tag ($property,$options=array()) {
		global $Shopp;
		$submit_attrs = array('title','value','disabled','tabindex','accesskey');
		
		// Return strings with no options
		switch ($property) {
			case "url": return $Shopp->link('cart'); break;
			case "hasitems": return (count($this->contents) > 0); break;
			case "totalitems": return $this->data->Totals->quantity; break;
			case "items":
				if (!$this->looping) {
					reset($this->contents);
					$this->looping = true;
				} else next($this->contents);
				
				if (current($this->contents)) return true;
				else {
					$this->looping = false;
					reset($this->contents);
					return false;
				}
			case "lastitem": return $this->contents[$this->data->added]; break;
			case "totalpromos": return count($this->data->PromosApplied); break;
			case "haspromos": return (count($this->data->PromosApplied) > 0); break;
			case "promos":
				if (!$this->looping) {
					reset($this->data->PromosApplied);
					$this->looping = true;
				} else next($this->data->PromosApplied);

				if (current($this->data->PromosApplied)) return true;
				else {
					$this->looping = false;
					reset($this->data->PromosApplied);
					return false;
				}
			case "promo-name":
				$promo = current($this->data->PromosApplied);
				return $promo->name;
				break;
			case "promo-discount":
				$promo = current($this->data->PromosApplied);
				if (empty($options['label'])) $options['label'] = __('Off!','Shopp');
				if (!empty($options['before'])) $string = $options['before'];
				switch($promo->type) {
					case "Free Shipping": $string .= $Shopp->Settings->get('free_shipping_text');
					case "Percentage Off": $string .= percentage($promo->discount)." ".$options['label'];
					case "Amount Off": $string .= money($promo->discount)." ".$options['label'];
					case "Buy X Get Y Free": return "";
				}
				if (!empty($options['after'])) $string = $options['after'];
				return $string;
				
				break;
			case "function": 
				$result = '<div class="hidden"><input type="hidden" id="cart-action" name="cart" value="true" /></div><input type="submit" name="update" id="hidden-update" />';
				if (!$this->data->Errors->exist()) return $result;
				$errors = $this->data->Errors->get(SHOPP_COMM_ERR);
				foreach ((array)$errors as $error) 
					if (!empty($error)) $result .= '<p class="error">'.$error->message().'</p>';
				$this->data->Errors->reset(); // Reset after display
				return $result;
				break;
			case "empty-button": 
				if (!isset($options['value'])) $options['value'] = __('Empty Cart','Shopp');
				return '<input type="submit" name="empty" id="empty-button" '.inputattrs($options,$submit_attrs).' />';
				break;
			case "update-button": 
				if (!isset($options['value'])) $options['value'] = __('Update Subtotal','Shopp');
				return '<input type="submit" name="update" class="update-button" '.inputattrs($options,$submit_attrs).' />';
				break;
			case "sidecart":
				ob_start();
				include(SHOPP_TEMPLATES."/sidecart.php");
				$content = ob_get_contents();
				ob_end_clean();
				return $content;
				break;
			case "hasdiscount": return ($this->data->Totals->discount > 0); break;
			case "discount": return money($this->data->Totals->discount); break;
		}
		
		$result = "";
		switch ($property) {
			case "promos-available":
				if (empty($this->data->Promotions)) return false;
				// Skip if the promo limit has been reached
				if ($Shopp->Settings->get('promo_limit') > 0 && 
					count($this->data->PromosApplied) >= $Shopp->Settings->get('promo_limit')) return false;
				return true;
				break;
			case "promo-code": 
				// Skip if no promotions exist
				if (empty($this->data->Promotions)) return false;
				// Skip if the promo limit has been reached
				if ($Shopp->Settings->get('promo_limit') > 0 && 
					count($this->data->PromosApplied) >= $Shopp->Settings->get('promo_limit')) return false;
				if (!isset($options['value'])) $options['value'] = __("Apply Promo Code","Shopp");
				$result .= '<ul><li>';
				
				if (!empty($this->data->PromoCodeResult)) {
					$result .= '<p class="error">'.$this->data->PromoCodeResult.'</p>';
					$this->data->PromoCodeResult = "";
				}
					
				$result .= '<span><input type="text" id="promocode" name="promocode" value="" size="10" /></span>';
				$result .= '<span><input type="submit" id="apply-code" name="update" '.inputattrs($options,$submit_attrs).' /></span>';
				$result .= '</li></ul>';
				return $result;
			case "has-shipping-methods": 
				return (!$this->data->ShippingDisabled
						&& count($this->data->ShipCosts) > 1
						&& $this->data->Shipping); break;				
			case "needs-shipped": return $this->data->Shipping; break;
			case "hasshipcosts":
			case "has-ship-costs": return ($this->data->Totals->shipping > 0); break;
			case "needs-shipping-estimates":
				$markets = $Shopp->Settings->get('target_markets');
				return ($this->data->Shipping && ($this->data->ShippingPostcode || count($markets) > 1));
				break;
			case "shipping-estimates":
				if (!$this->data->Shipping) return "";
				$base = $Shopp->Settings->get('base_operations');
				$markets = $Shopp->Settings->get('target_markets');
				if (empty($markets)) return "";
				foreach ($markets as $iso => $country) $countries[$iso] = $country;
				if (!empty($this->data->Order->Shipping->country)) $selected = $this->data->Order->Shipping->country;
				else $selected = $base['country'];
				$result .= '<ul><li>';
				if ((isset($options['postcode']) && value_is_true($options['postcode'])) ||
				 		$this->data->ShippingPostcode) {
					$result .= '<span>';
					$result .= '<input name="shipping[postcode]" id="shipping-postcode" size="6" value="'.$this->data->Order->Shipping->postcode.'" />&nbsp;';
					$result .= '</span>';
				}
				if (count($countries) > 1) {
					$result .= '<span>';
					$result .= '<select name="shipping[country]" id="shipping-country">';
					$result .= menuoptions($countries,$selected,true);
					$result .= '</select>';
					$result .= '</span>';
				} else $result .= '<input type="hidden" name="shipping[country]" id="shipping-country" value="'.key($markets).'" />';
				$result .= '</li></ul>';
				return $result;
				break;
		}
		
		$result = "";
		switch ($property) {
			case "subtotal": $result = $this->data->Totals->subtotal; break;
			case "shipping": 
				if (!$this->data->Shipping) return "";
				if (isset($options['label'])) {
					$options['currency'] = "false";
					if ($this->data->Totals->shipping === 0) {
						$result = $Shopp->Settings->get('free_shipping_text');
						if (empty($result)) $result = __('Free Shipping!','Shopp');
					}
						
					else $result = $options['label'];
				} else {
					if ($this->data->Totals->shipping === null) 
						return __("Enter Postal Code","Shopp");
					elseif ($this->data->Totals->shipping === false) 
						return __("Not Available","Shopp");
					else $result = $this->data->Totals->shipping;
				}
				break;
			case "hastaxes":
			case "has-taxes":
				return ($this->data->Totals->tax > 0); break;
			case "tax": 
				if ($this->data->Totals->tax > 0) {
					if (isset($options['label'])) {
						$options['currency'] = "false";
						$result = $options['label'];
					} else $result = $this->data->Totals->tax;
				} else $options['currency'] = "false";
				break;
			case "total": 
				$result = $this->data->Totals->total; 
				break;
		}
		
		if (isset($options['currency']) && !value_is_true($options['currency'])) return $result;
		else return '<span class="shopp_cart_'.$property.'">'.money($result).'</span>';
		
		return false;
	}
	
	function itemtag ($property,$options=array()) {
		if ($this->looping) {
			$Item = current($this->contents);
			if ($Item !== false) {
				$id = key($this->contents);
				if ($property == "id") return $id;
				return $Item->tag($id,$property,$options);
			}
		} else return false;
	}


	/**
	 * shippingtag()
	 * shopp('shipping','...')
	 * Used primarily in the summary.php template
	 **/
	function shippingtag ($property,$options=array()) {
		global $Shopp;
		$ShipCosts =& $this->data->ShipCosts;
		$result = "";
		
		switch ($property) {
			case "hasestimates": return (count($ShipCosts) > 0); break;
			case "methods":
				if (!isset($this->sclooping)) $this->sclooping = false;
				if (!$this->sclooping) {
					reset($ShipCosts);
					$this->sclooping = true;
				} else next($ShipCosts);
				
				if (current($ShipCosts)) return true;
				else {
					$this->sclooping = false;
					reset($ShipCosts);
					return false;
				}
				break;
			case "method-name": 
				return key($ShipCosts);
				break;
			case "method-cost": 
				$method = current($ShipCosts);
				return money($method['cost']);
				break;
			case "method-selector":
				$method = current($ShipCosts);
	
				$checked = '';
				if ((isset($this->data->Order->Shipping->method) && 
					$this->data->Order->Shipping->method == $method['name']) ||
					($method['cost'] == $this->data->Totals->shipping))
						$checked = ' checked="checked"';
	
				$result .= '<input type="radio" name="shipmethod" value="'.$method['name'].'" class="shipmethod" '.$checked.' rel="'.$method['cost'].'" />';
				return $result;
				
				break;
			case "method-delivery":
				$periods = array("h"=>3600,"d"=>86400,"w"=>604800,"m"=>2592000);
				$method = current($ShipCosts);
				$estimates = explode("-",$method['delivery']);
				$format = get_option('date_format');
				if ($estimates[0] == $estimates[1]) $estimates = array($estimates[0]);
				$result = "";
				for ($i = 0; $i < count($estimates); $i++){
					list($interval,$p) = sscanf($estimates[$i],'%d%s');
					if (!empty($result)) $result .= "&mdash;";
					$result .= _d($format,mktime()+($interval*$periods[$p]));
				}				
				return $result;
		}
	}
	
	function checkouttag ($property,$options=array()) {
		global $Shopp,$wp;
		$gateway = $Shopp->Settings->get('payment_gateway');
		$xcos = $Shopp->Settings->get('xco_gateways');
		$pages = $Shopp->Settings->get('pages');
		$base = $Shopp->Settings->get('base_operations');
		$countries = $Shopp->Settings->get('target_markets');
		$process = get_query_var('shopp_proc');
		$xco = get_query_var('shopp_xco');

		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');
		$submit_attrs = array('title','class','value','disabled','tabindex','accesskey');
		
		
		if (!isset($options['mode'])) $options['mode'] = "input";
		
		switch ($property) {
			case "url": 
				$ssl = true;
				// Test Mode will not require encrypted checkout
				if (strpos($gateway,"TestMode.php") !== false 
					|| isset($_GET['shopp_xco']) 
					|| $this->orderisfree() 
					|| SHOPP_NOSSL) 
					$ssl = false;
				$link = $Shopp->link('checkout',$ssl);
				
				// Pass any arguments along
				$args = $_GET;
				if (isset($args['page_id'])) unset($args['page_id']);
				$link = esc_url(add_query_arg($args,$link));
				if ($process == "confirm-order") $link = apply_filters('shopp_confirm_url',$link);
				else $link = apply_filters('shopp_checkout_url',$link);
				return $link;
				break;
			case "function":
				if (!isset($options['shipcalc'])) $options['shipcalc'] = '<img src="'.SHOPP_PLUGINURI.'/core/ui/icons/updating.gif" width="16" height="16" />';
				$regions = $Shopp->Settings->get('zones');
				$base = $Shopp->Settings->get('base_operations');
				$output = '<script type="text/javascript">'."\n";
				$output .= '//<![CDATA['."\n";
				$output .= 'var currencyFormat = '.json_encode($base['currency']['format']).';'."\n";
				$output .= 'var regions = '.json_encode($regions).';'."\n";
				$output .= 'var SHIPCALC_STATUS = \''.$options['shipcalc'].'\'';
				$output .= '//]]>'."\n";
				$output .= '</script>'."\n";
				if (!empty($options['value'])) $value = $options['value'];
				else $value = "process";
				$output .= '<div><input type="hidden" name="checkout" value="'.$value.'" /></div>'; 
				if ($value == "confirmed") $output = apply_filters('shopp_confirm_form',$output);
				else $output = apply_filters('shopp_checkout_form',$output);
				return $output;
				break;
			case "error":
				$result = "";
				if (!$this->data->Errors->exist(SHOPP_COMM_ERR)) return false;
				$errors = $this->data->Errors->get(SHOPP_COMM_ERR);
				foreach ((array)$errors as $error) if (!empty($error)) $result .= $error->message();
				return $result;
				// if (isset($options['show']) && $options['show'] == "code") return $this->data->OrderError->code;
				// return $this->data->OrderError->message;
				break;
			case "cart-summary":
				ob_start();
				include(SHOPP_TEMPLATES."/summary.php");
				$content = ob_get_contents();
				ob_end_clean();
				return $content;
				break;
			case "loggedin": return $this->data->login; break;
			case "notloggedin": return (!$this->data->login && $Shopp->Settings->get('account_system') != "none"); break;
			case "email-login":  // Deprecating
			case "loginname-login":  // Deprecating
			case "account-login": 
				if (!empty($_POST['account-login']))
					$options['value'] = $_POST['account-login']; 
				return '<input type="text" name="account-login" id="account-login"'.inputattrs($options).' />';
				break;
			case "password-login": 
				if (!empty($_POST['password-login']))
					$options['value'] = $_POST['password-login']; 
				return '<input type="password" name="password-login" id="password-login" '.inputattrs($options).' />';
				break;
			case "submit-login": // Deprecating
			case "login-button":
				$string = '<input type="hidden" name="process-login" id="process-login" value="false" />';
				$string .= '<input type="submit" name="submit-login" id="submit-login" '.inputattrs($options).' />';
				return $string;
				break;

			case "firstname": 
				if ($options['mode'] == "value") return $this->data->Order->Customer->firstname;
				if (!empty($this->data->Order->Customer->firstname))
					$options['value'] = $this->data->Order->Customer->firstname; 
				return '<input type="text" name="firstname" id="firstname" '.inputattrs($options).' />';
				break;
			case "lastname":
				if ($options['mode'] == "value") return $this->data->Order->Customer->lastname;
				if (!empty($this->data->Order->Customer->lastname))
					$options['value'] = $this->data->Order->Customer->lastname; 
				return '<input type="text" name="lastname" id="lastname" '.inputattrs($options).' />'; 
				break;
			case "email":
				if ($options['mode'] == "value") return $this->data->Order->Customer->email;
				if (!empty($this->data->Order->Customer->email))
					$options['value'] = $this->data->Order->Customer->email; 
				return '<input type="text" name="email" id="email" '.inputattrs($options).' />';
				break;
			case "loginname":
				if ($options['mode'] == "value") return $this->data->Order->Customer->login;
				if (!empty($this->data->Order->Customer->login))
					$options['value'] = $this->data->Order->Customer->login; 
				return '<input type="text" name="login" id="login" '.inputattrs($options).' />';
				break;
			case "password":
				if ($options['mode'] == "value") return $this->data->Order->Customer->password;
				if (!empty($this->data->Order->Customer->password))
					$options['value'] = $this->data->Order->Customer->password; 
				return '<input type="password" name="password" id="password" '.inputattrs($options).' />';
				break;
			case "confirm-password":
				if (!empty($this->data->Order->Customer->confirm_password))
					$options['value'] = $this->data->Order->Customer->confirm_password; 
				return '<input type="password" name="confirm-password" id="confirm-password" '.inputattrs($options).' />';
				break;
			case "phone": 
				if ($options['mode'] == "value") return $this->data->Order->Customer->phone;
				if (!empty($this->data->Order->Customer->phone))
					$options['value'] = $this->data->Order->Customer->phone; 
				return '<input type="text" name="phone" id="phone" '.inputattrs($options).' />'; 
				break;
			case "organization": 
			case "company": 
				if ($options['mode'] == "value") return $this->data->Order->Customer->company;
				if (!empty($this->data->Order->Customer->company))
					$options['value'] = $this->data->Order->Customer->company; 
				return '<input type="text" name="company" id="company" '.inputattrs($options).' />'; 
				break;
			case "customer-info":
				$allowed_types = array("text","password","hidden","checkbox","radio");
				if (empty($options['type'])) $options['type'] = "hidden";
				if (isset($options['name']) && $options['mode'] == "value") 
					return $this->data->Order->Customer->info[$options['name']];
				if (isset($options['name']) && in_array($options['type'],$allowed_types)) {
					if (isset($this->data->Order->Customer->info[$options['name']])) 
						$options['value'] = $this->data->Order->Customer->info[$options['name']]; 
					return '<input type="text" name="info['.$options['name'].']" id="customer-info-'.$options['name'].'" '.inputattrs($options).' />'; 
				}
				break;

			// SHIPPING TAGS
			case "shipping": return $this->data->Shipping;
			case "shipping-address": 
				if ($options['mode'] == "value") return $this->data->Order->Shipping->address;
				if (!empty($this->data->Order->Shipping->address))
					$options['value'] = $this->data->Order->Shipping->address; 
				return '<input type="text" name="shipping[address]" id="shipping-address" '.inputattrs($options).' />';
				break;
			case "shipping-xaddress":
				if ($options['mode'] == "value") return $this->data->Order->Shipping->xaddress;
				if (!empty($this->data->Order->Shipping->xaddress))
					$options['value'] = $this->data->Order->Shipping->xaddress; 
				return '<input type="text" name="shipping[xaddress]" id="shipping-xaddress" '.inputattrs($options).' />';
				break;
			case "shipping-city":
				if ($options['mode'] == "value") return $this->data->Order->Shipping->city;
				if (!empty($this->data->Order->Shipping->city))
					$options['value'] = $this->data->Order->Shipping->city; 
				return '<input type="text" name="shipping[city]" id="shipping-city" '.inputattrs($options).' />';
				break;
			case "shipping-province":
			case "shipping-state":
				if ($options['mode'] == "value") return $this->data->Order->Shipping->state;
				if (!isset($options['selected'])) $options['selected'] = false;
				if (!empty($this->data->Order->Shipping->state)) {
					$options['selected'] = $this->data->Order->Shipping->state;
					$options['value'] = $this->data->Order->Shipping->state;
				}
				
				$country = $base['country'];
				if (!empty($this->data->Order->Shipping->country))
					$country = $this->data->Order->Shipping->country;
				if (!array_key_exists($country,$countries)) $country = key($countries);

				if (empty($options['type'])) $options['type'] = "menu";
				$regions = $Shopp->Settings->get('zones');
				$states = $regions[$country];
				if (is_array($states) && $options['type'] == "menu") {
					$label = (!empty($options['label']))?$options['label']:'';
					$output = '<select name="shipping[state]" id="shipping-state" '.inputattrs($options,$select_attrs).'>';
					$output .= '<option value="" selected="selected">'.$label.'</option>';
				 	$output .= menuoptions($states,$options['selected'],true);
					$output .= '</select>';
				} else $output .= '<input type="text" name="shipping[state]" id="shipping-state" '.inputattrs($options).'/>';
				return $output;
				break;
			case "shipping-postcode":
				if ($options['mode'] == "value") return $this->data->Order->Shipping->postcode;
				if (!empty($this->data->Order->Shipping->postcode))
					$options['value'] = $this->data->Order->Shipping->postcode; 				
				return '<input type="text" name="shipping[postcode]" id="shipping-postcode" '.inputattrs($options).' />'; break;
			case "shipping-country": 
				if ($options['mode'] == "value") return $this->data->Order->Shipping->country;
				if (!empty($this->data->Order->Shipping->country))
					$options['selected'] = $this->data->Order->Shipping->country;
				else if (empty($options['selected'])) $options['selected'] = $base['country'];
				$output = '<select name="shipping[country]" id="shipping-country" '.inputattrs($options,$select_attrs).'>';
			 	$output .= menuoptions($countries,$options['selected'],true);
				$output .= '</select>';
				return $output;
				break;
			case "same-shipping-address":
				$label = __("Same shipping address","Shopp");
				if (isset($options['label'])) $label = $options['label'];
				$checked = ' checked="checked"';
				if (isset($options['checked']) && !value_is_true($options['checked'])) $checked = '';
				$output = '<label for="same-shipping"><input type="checkbox" name="sameshipaddress" value="on" id="same-shipping" '.$checked.' /> '.$label.'</label>';
				return $output;
				break;
				
			// BILLING TAGS
			case "billing-required": 
				if ($this->data->Totals->total == 0) return false;
				if (isset($_GET['shopp_xco'])) {
					$xco = join(DIRECTORY_SEPARATOR,array($Shopp->path,'gateways',$_GET['shopp_xco'].".php"));
					if (file_exists($xco)) {
						$meta = $Shopp->Flow->scan_gateway_meta($xco);
						$PaymentSettings = $Shopp->Settings->get($meta->tags['class']);
						return ($PaymentSettings['billing-required'] != "off");
					}
				}
				return ($this->data->Totals->total > 0); break;
			case "billing-address":
				if ($options['mode'] == "value") return $this->data->Order->Billing->address;
				if (!empty($this->data->Order->Billing->address))
					$options['value'] = $this->data->Order->Billing->address;			
				return '<input type="text" name="billing[address]" id="billing-address" '.inputattrs($options).' />';
				break;
			case "billing-xaddress":
				if ($options['mode'] == "value") return $this->data->Order->Billing->xaddress;
				if (!empty($this->data->Order->Billing->xaddress))
					$options['value'] = $this->data->Order->Billing->xaddress;			
				return '<input type="text" name="billing[xaddress]" id="billing-xaddress" '.inputattrs($options).' />';
				break;
			case "billing-city":
				if (!empty($this->data->Order->Billing->city))
					$options['value'] = $this->data->Order->Billing->city;			
				return '<input type="text" name="billing[city]" id="billing-city" '.inputattrs($options).' />'; 
				break;
			case "billing-province": 
			case "billing-state": 
				if ($options['mode'] == "value") return $this->data->Order->Billing->state;
				if (!isset($options['selected'])) $options['selected'] = false;
				if (!empty($this->data->Order->Billing->state)) {
					$options['selected'] = $this->data->Order->Billing->state;
					$options['value'] = $this->data->Order->Billing->state;
				}
				if (empty($options['type'])) $options['type'] = "menu";
				
				$country = $base['country'];
				if (!empty($this->data->Order->Billing->country))
					$country = $this->data->Order->Billing->country;
				if (!array_key_exists($country,$countries)) $country = key($countries);

				$regions = $Shopp->Settings->get('zones');
				$states = $regions[$country];
				if (is_array($states) && $options['type'] == "menu") {
					$label = (!empty($options['label']))?$options['label']:'';
					$output = '<select name="billing[state]" id="billing-state" '.inputattrs($options,$select_attrs).'>';
					$output .= '<option value="" selected="selected">'.$label.'</option>';
				 	$output .= menuoptions($states,$options['selected'],true);
					$output .= '</select>';
				} else $output .= '<input type="text" name="billing[state]" id="billing-state" '.inputattrs($options).'/>';
				return $output;
				break;
			case "billing-postcode":
				if ($options['mode'] == "value") return $this->data->Order->Billing->postcode;
				if (!empty($this->data->Order->Billing->postcode))
					$options['value'] = $this->data->Order->Billing->postcode;			
				return '<input type="text" name="billing[postcode]" id="billing-postcode" '.inputattrs($options).' />';
				break;
			case "billing-country": 
				if ($options['mode'] == "value") return $this->data->Order->Billing->country;
				if (!empty($this->data->Order->Billing->country))
					$options['selected'] = $this->data->Order->Billing->country;
				else if (empty($options['selected'])) $options['selected'] = $base['country'];			
				$output = '<select name="billing[country]" id="billing-country" '.inputattrs($options,$select_attrs).'>';
			 	$output .= menuoptions($countries,$options['selected'],true);
				$output .= '</select>';
				return $output;
				break;
			case "billing-card":
				if ($options['mode'] == "value") 
					return str_repeat('X',strlen($this->data->Order->Billing->card)-4)
						.substr($this->data->Order->Billing->card,-4);
				if (!empty($this->data->Order->Billing->card)) {
					$options['value'] = $this->data->Order->Billing->card;
					$this->data->Order->Billing->card = "";
				}
				return '<input type="text" name="billing[card]" id="billing-card" '.inputattrs($options).' />';
				break;
			case "billing-cardexpires-mm":
				if ($options['mode'] == "value") return date("m",$this->data->Order->Billing->cardexpires);
				if (!empty($this->data->Order->Billing->cardexpires))
					$options['value'] = date("m",$this->data->Order->Billing->cardexpires);				
				return '<input type="text" name="billing[cardexpires-mm]" id="billing-cardexpires-mm" '.inputattrs($options).' />'; 	
				break;
			case "billing-cardexpires-yy": 
				if ($options['mode'] == "value") return date("y",$this->data->Order->Billing->cardexpires);
				if (!empty($this->data->Order->Billing->cardexpires))
					$options['value'] = date("y",$this->data->Order->Billing->cardexpires);							
				return '<input type="text" name="billing[cardexpires-yy]" id="billing-cardexpires-yy" '.inputattrs($options).' />'; 
				break;
			case "billing-cardtype":
				if ($options['mode'] == "value") return $this->data->Order->Billing->cardtype;
				if (!isset($options['selected'])) $options['selected'] = false;
				if (!empty($this->data->Order->Billing->cardtype))
					$options['selected'] = $this->data->Order->Billing->cardtype;	
				$cards = $Shopp->Settings->get('gateway_cardtypes');
				$label = (!empty($options['label']))?$options['label']:'';
				$output = '<select name="billing[cardtype]" id="billing-cardtype" '.inputattrs($options,$select_attrs).'>';
				$output .= '<option value="" selected="selected">'.$label.'</option>';
			 	$output .= menuoptions($cards,$options['selected']);
				$output .= '</select>';
				return $output;
				break;
			case "billing-cardholder":
				if ($options['mode'] == "value") return $this->data->Order->Billing->cardholder;
				if (!empty($this->data->Order->Billing->cardholder))
					$options['value'] = $this->data->Order->Billing->cardholder;			
				return '<input type="text" name="billing[cardholder]" id="billing-cardholder" '.inputattrs($options).' />';
				break;
			case "billing-cvv":
				if (!empty($this->data->Order->Billing->cardholder))
					$options['value'] = $_POST['billing']['cvv'];
				return '<input type="text" name="billing[cvv]" id="billing-cvv" '.inputattrs($options).' />';
				break;
			case "billing-xco":     
				if (isset($_GET['shopp_xco'])) {
					if ($this->data->Totals->total == 0) return false;
					$xco = join(DIRECTORY_SEPARATOR,array($Shopp->path,'gateways',$_GET['shopp_xco'].".php"));
					if (file_exists($xco)) {
						$meta = $Shopp->Flow->scan_gateway_meta($xco);
						$ProcessorClass = $meta->tags['class'];
						include_once($xco);
						$Payment = new $ProcessorClass();
						if (method_exists($Payment,'billing')) return $Payment->billing($options);
					}
				}
				break;
				
			case "has-data":
			case "hasdata": return (is_array($this->data->Order->data) && count($this->data->Order->data) > 0); break;
			case "order-data":
			case "orderdata":
				if (isset($options['name']) && $options['mode'] == "value") 
					return $this->data->Order->data[$options['name']];
				$allowed_types = array("text","hidden",'password','checkbox','radio','textarea');
				$value_override = array("text","hidden","password","textarea");
				if (empty($options['type'])) $options['type'] = "hidden";
				if (isset($options['name']) && in_array($options['type'],$allowed_types)) {
					if (!isset($options['title'])) $options['title'] = $options['name'];
					if (in_array($options['type'],$value_override) && isset($this->data->Order->data[$options['name']])) 
						$options['value'] = $this->data->Order->data[$options['name']];
					if (!isset($options['cols'])) $options['cols'] = "30";
					if (!isset($options['rows'])) $options['rows'] = "3";
					if ($options['type'] == "textarea") 
						return '<textarea name="data['.$options['name'].']" cols="'.$options['cols'].'" rows="'.$options['rows'].'" id="order-data-'.$options['name'].'" '.inputattrs($options,array('accesskey','title','tabindex','class','disabled','required')).'>'.$options['value'].'</textarea>';
					return '<input type="'.$options['type'].'" name="data['.$options['name'].']" id="order-data-'.$options['name'].'" '.inputattrs($options).' />'; 
				}

				// Looping for data value output
				if (!$this->dataloop) {
					reset($this->data->Order->data);
					$this->dataloop = true;
				} else next($this->data->Order->data);

				if (current($this->data->Order->data) !== false) return true;
				else {
					$this->dataloop = false;
					return false;
				}
				
				break;
			case "data":
				if (!is_array($this->data->Order->data)) return false;
				$data = current($this->data->Order->data);
				$name = key($this->data->Order->data);
				if (isset($options['name'])) return $name;
				return $data;
				break;
			case "submit": 
				if (!isset($options['value'])) $options['value'] = __('Submit Order','Shopp');
				return '<input type="submit" name="process" id="checkout-button" '.inputattrs($options,$submit_attrs).' />'; break;
			case "confirm-button": 
				if (!isset($options['value'])) $options['value'] = __('Confirm Order','Shopp');
				return '<input type="submit" name="confirmed" id="confirm-button" '.inputattrs($options,$submit_attrs).' />'; break;
			case "local-payment": 
				return (!empty($gateway)); break;
			case "xco-buttons":     
				if (!is_array($xcos)) return false;
				$buttons = "";
				foreach ($xcos as $xco) {
					$xco = str_replace('/',DIRECTORY_SEPARATOR,$xco);
					$xcopath = join(DIRECTORY_SEPARATOR,array($Shopp->path,'gateways',$xco));
					if (!file_exists($xcopath)) continue;
					$meta = $Shopp->Flow->scan_gateway_meta($xcopath);
					$ProcessorClass = $meta->tags['class'];
					if (!empty($ProcessorClass)) {
						$PaymentSettings = $Shopp->Settings->get($ProcessorClass);
						if ($PaymentSettings['enabled'] == "on") {
							include_once($xcopath);
							$Payment = new $ProcessorClass();
							$buttons .= $Payment->tag('button',$options);
						}
					}
				}
				return $buttons;
				break;
		}
	}
		
} // end Cart class

?>