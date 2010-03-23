<?php
/**
 * PayPal Express
 * @class PayPalExpress
 *
 * @author Jonathan Davis
 * @version 1.0.4
 * @copyright Ingenesis Limited, 26 August, 2008
 * @package Shopp
 * 
 * $Id: PayPalExpress.php 661 2009-11-25 21:09:19Z jond $
 **/

class PayPalExpress {          
	var $type = "xco"; // Define as an External CheckOut/remote checkout processor
	var $button = 'https://www.paypal.com/%s/i/btn/btn_xpressCheckout.gif';
	var $sandbox_url = 'https://www.sandbox.paypal.com/%s/cgi-bin/webscr?cmd=_express-checkout';
	var $checkout_url = 'https://www.paypal.com/%s/cgi-bin/webscr?cmd=_express-checkout';
	var $transaction = array();
	var $settings = array();
	var $Response = false;
	var $currencies = array("USD", "AUD", "CAD", "CHF", "CZK", "DKK", "EUR", "GBP", 
							"HKD", "HUF", "JPY", "NOK", "NZD", "PLN", "SEK", "SGD");
	var $locales = array("AT" => "de_DE", "AU" => "en_AU", "BE" => "en_US", "CA" => "en_US",
							"CH" => "de_DE", "CN" => "zh_CN", "DE" => "de_DE", "ES" => "es_ES",
							"FR" => "fr_FR", "GB" => "en_GB", "GF" => "fr_FR", "GI" => "en_US",
							"GP" => "fr_FR", "IE" => "en_US", "IT" => "it_IT", "JP" => "ja_JP",
							"MQ" => "fr_FR", "NL" => "nl_NL", "PL" => "pl_PL", "RE" => "fr_FR",
							"US" => "en_US");
	var $status = array('' => 'UNKNOWN','Canceled-Reversal' => 'CHARGED','Completed' => 'CHARGED', 
						'Denied' => 'VOID', 'Expired' => 'VOID','Failed' => 'VOID','Pending' => 'PENDING',
						'Refunded' => 'VOID','Reversed' => 'VOID','Processed' => 'PENDING','Voided' => 'VOID');

	var $shiprequired = array('en_GB');

	function PayPalExpress () {
		global $Shopp;
		$this->settings = $Shopp->Settings->get('PayPalExpress');
		$this->settings['merchant_email'] = $Shopp->Settings->get('merchant_email');
		$this->settings['base_operations'] = $Shopp->Settings->get('base_operations');
		$this->settings['currency_code'] = $this->currencies[0];

		if (in_array($this->settings['base_operations']['currency']['code'],$this->currencies))
			$this->settings['currency_code'] = $this->settings['base_operations']['currency']['code'];

		if (array_key_exists($this->settings['base_operations']['country'],$this->locales))
			$this->settings['locale'] = $this->locales[$this->settings['base_operations']['country']];
		else $this->settings['locale'] = $this->locales["US"];

		$this->button = sprintf($this->button, $this->settings['locale']);
		$this->sandbox_url = sprintf($this->sandbox_url, strtolower($this->settings['base_operations']['country']));
		$this->checkout_url = sprintf($this->checkout_url, strtolower($this->settings['base_operations']['country']));
				
		// Capture PayPal Express transaction information as it becomes available
		if (!isset($Shopp->Cart->data->PayPalExpress)) $Shopp->Cart->data->PayPalExpress = new stdClass();
		if (!empty($_GET['PayerID'])) $Shopp->Cart->data->PayPalExpress->payerid = $_GET['PayerID'];
		if (!empty($_GET['token'])) {
			if (empty($Shopp->Cart->data->PayPalExpress->token)) {
				$Shopp->Cart->data->PayPalExpress->token = $_GET['token'];
			} else $Shopp->Cart->data->PayPalExpress->token = $_GET['token'];
			$this->details();
		}

		return true;
	}
	
	function actions () {}
	
	function notax ($rate) { return false; }
	
	function headers () {
		$_ = array();

		$_['USER'] 					= $this->settings['username'];
		$_['PWD'] 					= $this->settings['password'];
		$_['SIGNATURE']				= $this->settings['signature'];
		$_['VERSION']				= "53.0";

		return $_;
	}
	
	function purchase () {
		global $Shopp;
		$_ = array();
		
		// Transaction
		$_['CURRENCYCODE']			= $this->settings['currency_code'];
		$_['AMT']					= number_format($Shopp->Cart->data->Totals->total,2);
		$_['ITEMAMT']				= number_format($Shopp->Cart->data->Totals->subtotal - 
													$Shopp->Cart->data->Totals->discount,2);
		$_['SHIPPINGAMT']			= number_format($Shopp->Cart->data->Totals->shipping,2);
		$_['TAXAMT']				= number_format($Shopp->Cart->data->Totals->tax,2);

		// Disable shipping fields if no shipped items in cart
		if (!$Shopp->Cart->data->Shipping && 
				!in_array($this->settings['locale'],$this->shiprequired)) $_['NOSHIPPING'] = 1;

		// Line Items
		foreach($Shopp->Cart->contents as $i => $Item) {
			$_['L_NUMBER'.$i]		= $i;
			$_['L_NAME'.$i]			= $Item->name.((!empty($Item->optionlabel))?' '.$Item->optionlabel:'');
			$_['L_AMT'.$i]			= number_format($Item->unitprice,2);
			$_['L_QTY'.$i]			= $Item->quantity;
			$_['L_TAXAMT'.$i]		= number_format($Item->taxes,2);
		}
		
		if ($Shopp->Cart->data->Totals->discount != 0) {
			$discounts = array();
			foreach($Shopp->Cart->data->PromosApplied as $promo)
				$discounts[] = $promo->name;
			
			$i++;
			$_['L_NUMBER'.$i]		= $i;
			$_['L_NAME'.$i]			= join(", ",$discounts);
			$_['L_AMT'.$i]			= number_format($Shopp->Cart->data->Totals->discount*-1,2);
			$_['L_QTY'.$i]			= 1;
			$_['L_TAXAMT'.$i]		= number_format(0,2);
		}
		
		return $_;
	}
		
	function checkout () {
		global $Shopp;
		
		if ($Shopp->Cart->orderisfree()) shopp_redirect($Shopp->link('checkout'));
		
		$_ = $this->headers();

		// Options
		$_['METHOD']				= "SetExpressCheckout";
		$_['PAYMENTACTION']			= "Sale";
		$_['LANDINGPAGE']			= "Billing";

		// Include page style option, if provided
		if (isset($_GET['pagestyle'])) $_['PAGESTYLE'] = $_GET['pagestyle'];

		if (isset($Shopp->Cart->data->Order->data['paypal-custom']))
			$_['CUSTOM'] = htmlentities($Shopp->Cart->data->Order->data['paypal-custom']);

		if (SHOPP_PERMALINKS)
			$_['RETURNURL']			= $Shopp->link('confirm-order').'?shopp_xco=PayPal/PayPalExpress';
		else
			$_['RETURNURL']			= add_query_arg('shopp_xco','PayPal/PayPalExpress',$Shopp->link('confirm-order'));

		$_['CANCELURL']				= $Shopp->link('cart');
		
		$_ = array_merge($_,$this->purchase());
		
		$this->transaction = $this->encode($_);
		$result = $this->send();
		
		if (!empty($result) && isset($result->token)){
			if ($this->settings['testmode'] == "on")
				shopp_redirect(add_query_arg('token',$result->token,$this->sandbox_url));
			else shopp_redirect(add_query_arg('token',$result->token,$this->checkout_url));
		}
		
		if ($result->ack == "Failure") $this->Response = &$result;
		
		return false;	
	}
	
	function details () {
		global $Shopp;
		if (!isset($Shopp->Cart->data->PayPalExpress->token) && 
			!isset($Shopp->Cart->data->PayPalExpress->payerid)) return false;
		
		$_ = $this->headers();

   		$_['METHOD'] 				= "GetExpressCheckoutDetails";
		$_['TOKEN'] 				= $Shopp->Cart->data->PayPalExpress->token;

		$this->transaction = $this->encode($_);
		$this->send();
		
		if (!$this->Response) { // Try one more time
			$this->transaction = $this->encode($_);
			$this->send();
		}
		
		$Customer = $Shopp->Cart->data->Order->Customer;
		$Customer->firstname = $this->Response->firstname;
		$Customer->lastname = $this->Response->lastname;
		$Customer->email = $this->Response->email;
		$Customer->phone = $this->Response->phonenum;
		
		$Shipping = $Shopp->Cart->data->Order->Shipping;		
		$Shipping->address = $this->Response->shiptostreet;
		$Shipping->xaddress = $this->Response->shiptostreet2;
		$Shipping->city = $this->Response->shiptocity;
		$Shipping->state = $this->Response->shiptostate;
		$Shipping->country = $this->Response->shiptocountrycode;
		$Shipping->postcode = $this->Response->shiptozip;
		
		if (empty($Shipping->state) && empty($Shipping->country))
			add_filter('shopp_cart_taxrate',array(&$this,'notax'));
		
		$Billing = $Shopp->Cart->data->Order->Billing;
		$Billing->cardtype = "PayPal";
		$Billing->address = $Shipping->address;
		$Billing->xaddress = $Shipping->xaddress;
		$Billing->city = $Shipping->city;
		$Billing->state = $Shipping->state;
		$Billing->country = $this->Response->countrycode;
		$Billing->postcode = $Shipping->postcode;
		
		$Shopp->Cart->updated();
		$Shopp->Cart->totals();
		
		$targets = $Shopp->Settings->get('target_markets');
		if (!in_array($Billing->country,array_keys($targets))) {
			new ShoppError(__('The location you are purchasing from is outside of our market regions. This transaction cannot be processed.','Shopp'),'paypalexpress_market',SHOPP_TRXN_ERR);
			shopp_redirect($Shopp->link('cart'));
		}
		
	} 
	
	function process () {
		global $Shopp;
		if (!isset($Shopp->Cart->data->PayPalExpress->token) && 
			!isset($Shopp->Cart->data->PayPalExpress->payerid)) return false;
				
		$_ = $this->headers();

		$_['METHOD'] 				= "DoExpressCheckoutPayment";
		$_['PAYMENTACTION']			= "Sale";
		$_['TOKEN'] 				= $Shopp->Cart->data->PayPalExpress->token;
		$_['PAYERID'] 				= $Shopp->Cart->data->PayPalExpress->payerid;

		// Transaction
		$_ = array_merge($_,$this->purchase());

		$this->transaction = $this->encode($_);
		$result = $this->send();
		if (!$result) {
			new ShoppError(__('No response was received from PayPal. The order cannot be processed.','Shopp'),'paypalexpress_noresults',SHOPP_COMM_ERR);
		}
		if(!$Shopp->Cart->validorder()){
			new ShoppError(__('There is not enough customer information to process the order.','Shopp'),'invalid_order',SHOPP_TRXN_ERR);
			return false;	
		}
		// If the transaction is a success, get the transaction details, 
		// build the purchase receipt, save it and return it
		if (strtolower($result->ack) == "success") {
			$_ = $this->headers();
			
			$_['METHOD'] 				= "GetTransactionDetails";
			$_['TRANSACTIONID']			= $this->Response->transactionid;
			
			$this->transaction = $this->encode($_);
			$result = $this->send();
			if (!$result) {
				new ShoppError(__('Details for the order were not provided by PayPal.','Shopp'),'paypalexpress_notrxn_details',SHOPP_COMM_ERR);
				return false;
			}

			$Order = $Shopp->Cart->data->Order;
			$Order->Totals = $Shopp->Cart->data->Totals;
			$Order->Items = $Shopp->Cart->contents;
			$Order->Cart = $Shopp->Cart->session;

			$authentication = $Shopp->Settings->get('account_system');

			if ($authentication == "wordpress") {
				// Check if they've logged in
				// If the shopper is already logged-in, save their updated customer info
				if ($Shopp->Cart->data->login) {
					if (SHOPP_DEBUG) new ShoppError('Customer logged in, linking Shopp customer account to existing WordPress account.',false,SHOPP_DEBUG_ERR);
					get_currentuserinfo();
					global $user_ID;
					$Order->Customer->wpuser = $user_ID;
				}

				// Create WordPress account (if necessary)
				if (!$Order->Customer->wpuser) {
					if (SHOPP_DEBUG) new ShoppError('Creating a new WordPress account for this customer.',false,SHOPP_DEBUG_ERR);
					if(!$Order->Customer->new_wpuser()) new ShoppError(__('Account creation failed on order for customer id:' . $Order->Customer->id, "Shopp"), false,SHOPP_TRXN_ERR);
				}
			}

			// Create a WP-compatible password hash to go in the db
			if (empty($Order->Customer->id))
				$Order->Customer->password = wp_hash_password($Order->Customer->password);
			$Order->Customer->save();

			$Order->Billing->customer = $Order->Customer->id;
			$Order->Billing->cardtype = "PayPal";
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
			$Purchase->gateway = "PayPal Express";
			$Purchase->transactionid = $this->Response->transactionid;
			$Purchase->transtatus = $this->status[$this->Response->paymentstatus];
			$Purchase->ip = $Shopp->Cart->ip;
			$Purchase->fees = $this->Response->feeamt;
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
		
		// Fail by default
		return false;
	}
	
	function error () {
		if (!empty($this->Response)) {
			
			$message = join("; ",$this->Response->l_longmessage);
			if (empty($message)) return false;
			return new ShoppError($message,'paypal_express_transacton_error',SHOPP_TRXN_ERR,
				array('code'=>$code));
		}
	}
		
	function send () {
		$connection = curl_init();
		if ($this->settings['testmode'] == "on")
			curl_setopt($connection,CURLOPT_URL,"https://api-3t.sandbox.paypal.com/nvp"); // Sandbox testing
		else curl_setopt($connection,CURLOPT_URL,"https://api-3t.paypal.com/nvp"); // Live		
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0); 
		curl_setopt($connection, CURLOPT_NOPROGRESS, 1); 
		curl_setopt($connection, CURLOPT_VERBOSE, 0); 
		curl_setopt($connection, CURLOPT_FOLLOWLOCATION,0); 
		curl_setopt($connection, CURLOPT_POST, 1); 
		curl_setopt($connection, CURLOPT_POSTFIELDS, $this->transaction); 
		curl_setopt($connection, CURLOPT_TIMEOUT, 60); 
		curl_setopt($connection, CURLOPT_USERAGENT, SHOPP_GATEWAY_USERAGENT); 
		curl_setopt($connection, CURLOPT_REFERER, "https://".$_SERVER['SERVER_NAME']); 
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		$buffer = curl_exec($connection);   
		if ($error = curl_error($connection)) 
			new ShoppError($error,'paypal_express_connection',SHOPP_COMM_ERR);
		curl_close($connection);

		$this->Response = false;
		$this->Response = $this->response($buffer);
		return $this->Response;
	}
	
	function response ($buffer) {
		$_ = new stdClass();
		$r = array();
		$pairs = explode("&",$buffer);
		foreach($pairs as $pair) {
			list($key,$value) = explode("=",$pair);
			if (preg_match("/l_(\w+?)(\d+)/i",$key,$matches)) {
				// Capture line item data into an array structure
				if (!isset($r[$matches[1]])) $r[$matches[1]] = array();
				// Skip non-line item data
				if (is_array($r[$matches[1]])) $r[$matches[1]][$matches[2]] = urldecode($value);
			} else $r[$key] = urldecode($value);
		}

		// Remap array to object
		foreach ($r as $key => $value) {
			if (empty($key)) continue;
			$key = strtolower($key);
			$_->{$key} = $value;
		}

		return $_;
	}
	
	
	function encode ($data) {
		$query = "";
		foreach($data as $key => $value) {
			if (is_array($value)) {
				foreach($value as $item) {
					if (strlen($query) > 0) $query .= "&";
					$query .= "$key=".urlencode($item);
				}
			} else {
				if (strlen($query) > 0) $query .= "&";
				$query .= "$key=".urlencode($value);
			}
		}
		return $query;
	}
	
	function tag ($property,$options=array()) {
		global $Shopp;
		switch ($property) {
			case "button":
				$args = array();
				$args['shopp_xco'] = 'PayPal/PayPalExpress';
				if (isset($options['pagestyle'])) $args['pagestyle'] = $options['pagestyle'];
				$url = add_query_arg($args,$Shopp->link('checkout'));
				return '<p class="paypal_express"><a href="'.$url.'"><img src="'.$this->button.'" alt="Checkout with PayPal" /></a></p>';
		}
	}
	
	function settings () {
		?>
			<th scope="row" valign="top"><label for="paypalexpress-enabled">PayPal Express</label></th> 
			<td><input type="hidden" name="settings[PayPalExpress][enabled]" value="off" /><input type="checkbox" name="settings[PayPalExpress][enabled]" value="on" id="paypalexpress-enabled"<?php echo ($this->settings['enabled'] == "on")?' checked="checked"':''; ?>/><label for="paypalexpress-enabled"> <?php _e('Enable','Shopp'); ?> PayPal Express</label>
				<div id="paypalexpress-settings">
		
				<p><input type="text" name="settings[PayPalExpress][username]" id="paypalxp-username" size="30" value="<?php echo $this->settings['username']; ?>"/><br />
				Enter your PayPal Express API Username.</p>
				<p><input type="password" name="settings[PayPalExpress][password]" id="paypalxp-password" size="16" value="<?php echo $this->settings['password']; ?>" /><br />
				Enter your PayPal Express API Password.</p>
				<p><input type="text" name="settings[PayPalExpress][signature]" id="paypalxp-signature" size="48" value="<?php echo $this->settings['signature']; ?>" /><br />
				Enter your PayPal Express API Signature.</p>
				<p><label for="paypalxp-testmode"><input type="hidden" name="settings[PayPalExpress][testmode]" value="off" /><input type="checkbox" name="settings[PayPalExpress][testmode]" id="paypalxp-testmode" value="on"<?php echo ($this->settings['testmode'] == "on")?' checked="checked"':''; ?> /> Use the <a href="http://docs.shopplugin.net/PayPal_Sandbox" target="shoppdocs">PayPal Sandbox</a></label></p>
				
				<input type="hidden" name="settings[xco_gateways][]" value="<?php echo gateway_path(__FILE__); ?>"  />
				
				</div>
			</td>
		<?php
	}
	
	function registerSettings () {
		?>
		xcosettings('#paypalexpress-enabled','#paypalexpress-settings');
		<?php
	}

} // end PayPalExpress class

?>