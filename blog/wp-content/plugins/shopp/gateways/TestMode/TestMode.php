<?php
/**
 * Test Mode
 * @class TestMode
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 9 April, 2008
 * @package Shopp
 * 
 * $Id: TestMode.php 661 2009-11-25 21:09:19Z jond $
 **/

class TestMode {
	var $transaction = array();
	var $settings = array();
	var $Response = false;
	var $cards = array("Visa","MasterCard","Discover","American Express");

	function TestMode (&$Order="") {
		global $Shopp;
		$this->settings = $Shopp->Settings->get('TestMode');
		return true;
	}
	
	function process () {
		if ($this->settings['response'] == "error") return false;
		return true;
	}
	
	function transactionid () {
		if ($this->settings['response'] == "error") return "";
		return "TESTMODE";
	}
	
	function error () {
		if (!$this->Response)
			return new ShoppError(__("This is an example error message. Disable the 'always show an error' setting to stop displaying this error.","Shopp"),'test_mode_error',SHOPP_TRXN_ERR);
	}
	
	function build (&$Order) {
	}
	
	function response () {
	}
	
	function settings () {
		?>
		<tr id="testmode-settings" class="addon">
			<th scope="row" valign="top">Test Mode</th>
			<td>
				<?php foreach ($this->cards as $card): ?>
				<input type="hidden" name="settings[TestMode][cards][]" value="<?php echo $card; ?>" />
				<?php endforeach; ?>
				<input type="hidden" name="settings[TestMode][response]" value="success" /><input type="checkbox" name="settings[TestMode][response]" id="testmode_response" value="error"<?php echo ($this->settings['response'] == "error")?' checked="checked"':''; ?> /><label for="testmode_response"> <?php _e('Always show an error','Shopp'); ?></label><br />
				<?php _e('Use to test and style error messages','Shopp'); ?>
			</td>
		</tr>
		<?php
	}
	
	function registerSettings () {
		?>
		gatewayHandlers.register('<?php echo addslashes(gateway_path(__FILE__)); ?>','testmode-settings');
		<?php
	}
	

} // end TestMode class

?>