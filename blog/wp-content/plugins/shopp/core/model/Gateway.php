<?php
/**
 * Gateway classes
 * Generic prototype classes for local and remote payment systems
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 17 March, 2009
 * @package shopp
 **/

class Gateway {
	var $transaction = array();
	var $settings = array();
	var $Response = false;
	var $cards = array("Visa", "MasterCard", "Amex", "Discover");
	var $type = "local";

	function Gateway (&$Order="") {
		global $Shopp;
		$this->classname = get_class($this);
		$this->uid = strtolower($this->classname)."-settings";
		$this->settings = $Shopp->Settings->get($this->classname);
		$this->settings['merchant_email'] = $Shopp->Settings->get('merchant_email');
		if (!isset($this->settings['cards'])) $this->settings['cards'] = $this->cards;
		
		// $this->file = __FILE__;
		// if (!empty($Order)) $this->build($Order);
		// return true;
	}
	
	function build ($Order) {
		$_ = array();
		$this->transaction = join("",$_);
	}
	
	function process () {
		$this->Response = $this->send();
		$status = $this->Response; // Get the response status

		if ($status == "APPROVED") return true;
		else return false;
	}
	
	function transactionid () {
		$transaction = $this->Response->transaction;
		if (!empty($transaction)) return $transaction;
		return false;
	}
	
	function error () {
		if (empty($this->Response)) return false;
		$message = __('Gateway response error.','Shopp');
		if (class_exists('ShoppError')) {
			if (empty($message)) return new ShoppError(__("An unknown error occurred while processing this transaction.  Please contact the site administrator.","Shopp"),'gateway_trxn_error',SHOPP_TRXN_ERR);
			return new ShoppError($message,'gateway_trxn_error',SHOPP_TRXN_ERR);
		} else {
			$Error = new stdClass();
			$Error->code = 'gateway_trxn_error';
			$Error->message = $message;
			return $Error;
		}
	}
	
	function send () {
		$connection = curl_init();
		curl_setopt($connection,CURLOPT_URL,$this->url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0); 
		curl_setopt($connection, CURLOPT_NOPROGRESS, 1); 
		curl_setopt($connection, CURLOPT_VERBOSE, 1); 
		curl_setopt($connection, CURLOPT_FOLLOWLOCATION,0); 
		curl_setopt($connection, CURLOPT_POST, 1); 
		curl_setopt($connection, CURLOPT_POSTFIELDS, $this->transaction); 
		curl_setopt($connection, CURLOPT_TIMEOUT, 30); 
		curl_setopt($connection, CURLOPT_USERAGENT, SHOPP_GATEWAY_USERAGENT); 
		curl_setopt($connection, CURLOPT_REFERER, "https://".$_SERVER['SERVER_NAME']); 
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		$buffer = curl_exec($connection);
		if ($error = curl_error($connection)) {
			if (class_exists('ShoppError')) new ShoppError($error,'gateway_connection',SHOPP_COMM_ERR);
		}
		curl_close($connection);
		
		$this->Response = $this->response($buffer);
		return $this->Response;
	}
	
	function response ($string) {
		return $string;
	}
	
	
	function settings () {
		global $Shopp;
		?>
		<tr id="<?php echo $this->uid; ?>-settings" class="addon">
			<th scope="row" valign="top">Gateway Name</th>
			<td>
				<div><input type="text" name="settings[<?php echo $this->classname; ?>][account]" id="<?php echo $this->uid; ?>_account" value="<?php echo $this->settings['account']; ?>" size="16" /><br /><label for="<?php echo $this->uid; ?>_account"><?php _e('Enter your Gateway account.'); ?></label></div>
				<div><input type="password" name="settings[<?php echo $this->classname; ?>][password]" id="<?php echo $this->uid; ?>_password" value="<?php echo $this->settings['password']; ?>" size="24" /><br /><label for="<?php echo $this->uid; ?>_password"><?php _e('Enter your Gateway password.'); ?></label></div>
				<div><input type="hidden" name="settings[<?php echo $this->classname; ?>][testmode]" value="off"><input type="checkbox" name="settings[<?php echo $this->classname; ?>][testmode]" id="<?php echo $this->uid; ?>_testmode" value="on"<?php echo ($this->settings['testmode'] == "on")?' checked="checked"':''; ?> /><label for="<?php echo $this->uid; ?>_testmode"> <?php _e('Enable test mode'); ?></label></div>
				<div><strong>Accept these cards:</strong>
				<ul class="cards"><?php foreach($this->cards as $id => $card): 
					$checked = "";
					if (in_array($card,$this->settings['cards'])) $checked = ' checked="checked"';
				?>
					<li><input type="checkbox" name="settings[<?php echo $this->classname; ?>][cards][]" id="<?php echo $this->uid; ?>_cards_<?php echo $id; ?>" value="<?php echo $card; ?>" <?php echo $checked; ?> /><label for="<?php echo $this->uid; ?>_cards_<?php echo $id; ?>"> <?php echo $card; ?></label></li>
				<?php endforeach; ?></ul></div>
				
				<input type="hidden" name="module[<?php echo basename($this->file); ?>]" value="<?php echo $this->classname; ?>" />
			</td>
		</tr>
		<?php
	}
	
	function registerSettings () {
		?>
		gatewayHandlers.register('<?php echo addslashes($this->file); ?>',
								 '<?php echo $this->uid; ?>-settings');<?php
	}
	
	
	function order () {
		
	}
	
} // end Gateway class



?>