<?php
/**
 * 2Checkout.com
 * @class _2Checkout
 *
 * @author Jonathan Davis
 * @version 1.0.2
 * @copyright Ingenesis Limited, 27 May, 2009
 * @package Shopp
 * 
 * $Id: 2Checkout.php 661 2009-11-25 21:09:19Z jond $
 **/

require_once(SHOPP_PATH."/core/model/XMLdata.php");

class _2Checkout {          
	var $type = "xco"; // Define as an External CheckOut/remote checkout processor
	var $url = 'https://www.2checkout.com/checkout/purchase';
	var $transaction = array();
	var $settings = array();
	var $Response = false;
	var $checkout = true;

	function _2Checkout () {
		global $Shopp,$wp;
		$this->settings = $Shopp->Settings->get('_2Checkout');
		$this->settings['merchant_email'] = $Shopp->Settings->get('merchant_email');
		$this->settings['base_operations'] = $Shopp->Settings->get('base_operations');
		
		$loginproc = (isset($_POST['process-login']))?$_POST['process-login']:false;

		if (isset($_POST['checkout']) && 
			$_POST['checkout'] == "process" && 
			!$loginproc) $this->checkout();

		// Intercept INS messages
		if (isset($_REQUEST['message_type'])) $this->notification();

		// Capture processed payment
		if (isset($_POST['order_number'])
			&& isset($_POST['credit_card_processed'])
			 && $_POST['credit_card_processed'] == "Y") $_POST['checkout'] = "confirmed";
		
	}
	
	function actions () {
		add_filter('shopp_confirm_url',array(&$this,'url'));
		add_filter('shopp_confirm_form',array(&$this,'form'));
	}
		
	function checkout () {
		global $Shopp;
		if (empty($_POST['checkout'])) return false;

		// Save checkout data
		$Order = $Shopp->Cart->data->Order;

		if (isset($_POST['data'])) $Order->data = $_POST['data'];
		if (empty($Order->Customer))
			$Order->Customer = new Customer();
		$Order->Customer->updates($_POST);

		if (isset($_POST['confirm-password']))
			$Order->Customer->confirm_password = $_POST['confirm-password'];

		if (empty($Order->Billing))
			$Order->Billing = new Billing();
		$Order->Billing->updates($_POST['billing']);

		if (empty($Order->Shipping))
			$Order->Shipping = new Shipping();
			
		if ($_POST['shipping']) $Order->Shipping->updates($_POST['shipping']);
		if (!empty($_POST['shipmethod'])) $Order->Shipping->method = $_POST['shipmethod'];
		else $Order->Shipping->method = key($Shopp->Cart->data->ShipCosts);

		// Override posted shipping updates with billing address
		if ($_POST['sameshipaddress'] == "on")
			$Order->Shipping->updates($Order->Billing,
				array("_datatypes","_table","_key","_lists","id","created","modified"));

		$estimatedTotal = $Shopp->Cart->data->Totals->total;
		$Shopp->Cart->updated();
		$Shopp->Cart->totals();
		
		if ($Shopp->Cart->validate() !== true) {
			$_POST['checkout'] = false;
			return;
		} else $Order->Customer->updates($_POST); // Catch changes from validation

		if ($Shopp->Cart->orderisfree()) 
			return ($_POST['checkout'] = 'confirmed');
		
		shopp_redirect(add_query_arg('shopp_xco','2Checkout/2Checkout',$Shopp->link('confirm-order',false)));
	}
	
	function url ($url) {
		return $this->url;
	}
	
	function form ($form) {
		global $Shopp;
		$db =& DB::get();
		$purchasetable = DatabaseObject::tablename(Purchase::$table);
		$next = $db->query("SELECT auto_increment as id FROM information_schema.tables WHERE table_schema=database() AND table_name='$purchasetable' LIMIT 1");

		$Order = $Shopp->Cart->data->Order;
		$Order->_2COcart_order_id = date('mdy').'-'.date('His').'-'.$next->id;

		// Build the transaction
		$_ = array();
		
		// Required
		$_['sid']				= $this->settings['sid'];
		$_['total']				= number_format($Shopp->Cart->data->Totals->total,2);
		$_['cart_order_id']		= $Order->_2COcart_order_id;
		$_['vendor_order_id']	= $Shopp->Cart->session;
		$_['id_type']			= 1;
		
		// Extras
		if ($this->settings['testmode'] == "on")
			$_['demo']			= "Y";
		
		$_['skip_landing'] = "1";
		
		$_['x_Receipt_Link_URL'] = add_query_arg('shopp_xco','2Checkout/2Checkout',$Shopp->link('confirm-order'));
		
		// Line Items
		foreach($Shopp->Cart->contents as $i => $Item) {
			// $description[] = $Item->quantity."x ".$Item->name.((!empty($Item->optionlabel))?' '.$Item->optionlabel:'');
			$id = $i+1;
			$_['c_prod_'.$id]			= 'shopp_pid-'.$Item->product.','.$Item->quantity;
			$_['c_name_'.$id]			= $Item->name;
			$_['c_description_'.$id]	= !empty($Item->optionlabel)?$Item->optionlabel:'';
			$_['c_price_'.$id]			= number_format($Item->unitprice,2);
			
		}
			
		$_['card_holder_name'] 		= $Order->Customer->firstname.' '.$Order->Customer->lastname;
		$_['street_address'] 		= $Order->Billing->address;
		$_['street_address2'] 		= $Order->Billing->xaddress;
		$_['city'] 					= $Order->Billing->city;
		$_['state'] 				= $Order->Billing->state;
		$_['zip'] 					= $Order->Billing->postcode;
		$_['country'] 				= $Order->Billing->country;
		$_['email'] 				= $Order->Customer->email;
		$_['phone'] 				= $Order->Customer->phone;
		
		$_['ship_name'] 			= $Order->Customer->firstname.' '.$Order->Customer->lastname;
		$_['ship_street_address'] 	= $Order->Shipping->address;
		$_['ship_street_address2'] 	= $Order->Shipping->xaddress;
		$_['ship_city'] 			= $Order->Shipping->city;
		$_['ship_state'] 			= $Order->Shipping->state;
		$_['ship_zip'] 				= $Order->Shipping->zip;
		$_['ship_country'] 			= $Order->Shipping->country;

		return $form.$this->encode($_);
	}
	
	function process () {
		global $Shopp;
		
		if (empty($_POST)) {
			new ShoppError(__('Payment could not be confirmed, this order cannot be processed.','Shopp'),'2co_transaction_error',SHOPP_COMM_ERR);
			exit();
		}
		
		session_unset();
		session_destroy();
		
		// Load the cart for the correct order
		$Shopp->Cart = new Cart();
		$Shopp->Cart->session = $_POST['vendor_order_id'];
		session_start();
		$Shopp->Cart->load($Shopp->Cart->session);

		if ($this->settings['verify'] == "on" && !$this->validate($_POST['key'])) {
			new ShoppError(__('The order submitted to 2Checkout could not be verified.','Shopp'),'2co_validation_error',SHOPP_TRXN_ERR);
			exit();
		}			
		
		if ($_POST['credit_card_processed'] == "N") {
			new ShoppError(__('The payment failed. Please try your order again with a different payment method.','Shopp'),'2co_processing_error',SHOPP_TRXN_ERR);
			exit();
		}

		if(!$Shopp->Cart->validorder()){
			new ShoppError(__('There is not enough customer information to process the order.','Shopp'),'invalid_order',SHOPP_TRXN_ERR);
			exit();
		}
		
		$Order = $Shopp->Cart->data->Order;
		$Order->Totals = $Shopp->Cart->data->Totals;
		$Order->Items = $Shopp->Cart->contents;
		$Order->Cart = $Shopp->Cart->session;
	
		$Order->Customer->save();
	
		$Order->Billing->customer = $Order->Customer->id;
		$Order->Billing->cardtype = "2Checkout";
		$Order->Billing->save();
	
		$Order->Shipping->customer = $Order->Customer->id;
		$Order->Shipping->save();
		
		$Purchase = new Purchase();
		$Purchase->customer = $Order->Customer->id;
		$Purchase->billing = $Order->Billing->id;
		$Purchase->shipping = $Order->Shipping->id;
		$Purchase->copydata($Order->Customer);
		$Purchase->copydata($Order->Billing);
		$Purchase->copydata($Order->Shipping,'ship');
		$Purchase->copydata($Order->Totals);
		$Purchase->freight = $Order->Totals->shipping;
		$Purchase->gateway = "2Checkout";
		$Purchase->transtatus = "CHARGED";
		$Purchase->transactionid = $_POST['order_number'];
		$Purchase->ip = $Shopp->Cart->ip;
		$Purchase->save();

		foreach($Shopp->Cart->contents as $Item) {
			$Purchased = new Purchased();
			$Purchased->copydata($Item);
			$Purchased->purchase = $Purchase->id;
			if (!empty($Purchased->download)) $Purchased->keygen();
			$Purchased->save();
			if ($Item->inventory) $Item->unstock();
		}

		return $Purchase;
	}
	
	function notification () {
		// Not implemented
	}
	
	function error () {
		if (empty($this->Response)) return false;
		$code = $this->Response->getElement('errorcode');
		$message = $this->Response->getElementContent('message');
		if (!$code) return false;
		
		return new ShoppError($message,'2co_transaction_error',SHOPP_TRXN_ERR,
			array('code'=>$code));
	}
		
	
	function encode ($data) {
		$query = "";
		foreach($data as $key => $value) {
			if (is_array($value)) {
				foreach($value as $item)
					$query .= '<input type="hidden" name="'.$key.'[]" value="'.attribute_escape($item).'" />';
			} else {
				$query .= '<input type="hidden" name="'.$key.'" value="'.attribute_escape($value).'" />';
			}
		}
		return $query;
	}
	
	function validate ($key) {
		global $Shopp;

		$order = $_POST['order_number'];
		if ($this->settings['testmode'] == "on") $order = 1;
			
		$verification = strtoupper(md5($this->settings['secret'].
							$this->settings['sid'].
							$order.
							number_format($Shopp->Cart->data->Totals->total,2)));

		return ($verification == $key);
	}
	
	function tag ($property,$options=array()) {
		global $Shopp;
		switch ($property) {
			case "button":
				$args = array('shopp_xco' => '2Checkout/2Checkout');
				$url = add_query_arg($args,$Shopp->link('checkout'));				
				return '<p class="xco_2checkout"><a href="'.$url.'">'.__('Pay with 2Checkout.com','Shopp').'</a></p>';
		}
	}
	
	// Required, but not used
	function billing () {}
		
	function settings () {
		?>
			<th scope="row" valign="top"><label for="2co-enabled">2Checkout.com</label></th> 
			<td><input type="hidden" name="settings[_2Checkout][billing-required]" value="off" /><input type="hidden" name="settings[_2Checkout][enabled]" value="off" /><input type="checkbox" name="settings[_2Checkout][enabled]" value="on" id="2co-enabled"<?php echo ($this->settings['enabled'] == "on")?' checked="checked"':''; ?>/><label for="2co-enabled"> <?php _e('Enable','Shopp'); ?> 2Checkout.com</label>
				<div id="2co-settings">
		
				<p><input type="text" name="settings[_2Checkout][sid]" id="2co-sid" size="10" value="<?php echo $this->settings['sid']; ?>"/><br />
				<?php _e('Your 2Checkout vendor account number.','Shopp'); ?></p>
				<p><label for="2co-verify"><input type="hidden" name="settings[_2Checkout][verify]" value="off" /><input type="checkbox" name="settings[_2Checkout][verify]" id="2co-verify" value="on"<?php echo ($this->settings['verify'] == "on")?' checked="checked"':''; ?> /> <?php _e('Enable order verification','Shopp'); ?></label></p>
				<p id="2co-verify-secret" class="hidden"><input type="text" name="settings[_2Checkout][secret]" id="2co-secret" size="18" value="<?php echo $this->settings['secret']; ?>"/><br />
				<?php _e('Your 2Checkout secret word for order verification.','Shopp'); ?></p>				
				<p><label for="2co-testmode"><input type="hidden" name="settings[_2Checkout][testmode]" value="off" /><input type="checkbox" name="settings[_2Checkout][testmode]" id="2co-testmode" value="on"<?php echo ($this->settings['testmode'] == "on")?' checked="checked"':''; ?> /> <?php _e('Enable test mode','Shopp'); ?></label></p>
				
				<input type="hidden" name="settings[xco_gateways][]" value="<?php echo gateway_path(__FILE__); ?>"  />
				
				</div>
			</td>
			<script type="text/javascript">
			(function($) {
				$(window).ready(function () {
					$('#2co-verify').change(function () {
						if ($(this).attr('checked')) $('#2co-verify-secret').show();
						else $('#2co-verify-secret').hide();
					}).change();
				});
			})(jQuery)
			</script>
		<?php
	}
	
	function registerSettings () {
		?>
		xcosettings('#2co-enabled','#2co-settings');
		<?php
	}

} // end _2Checkout class

?>