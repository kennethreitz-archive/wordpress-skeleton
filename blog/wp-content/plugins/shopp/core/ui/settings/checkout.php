<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<h2><?php _e('Checkout Settings','Shopp'); ?></h2>

	<form name="settings" id="checkout" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-checkout'); ?>
		
		<?php include("navigation.php"); ?>

		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="confirm_url"><?php _e('Order Confirmation','Shopp'); ?></label></th> 
				<td><input type="radio" name="settings[order_confirmation]" value="ontax" id="order_confirmation_ontax"<?php if($this->Settings->get('order_confirmation') == "ontax") echo ' checked="checked"' ?> /> <label for="order_confirmation_ontax"><?php _e('Show for taxed orders only','Shopp'); ?></label><br />
					<input type="radio" name="settings[order_confirmation]" value="always" id="order_confirmation_always"<?php if($this->Settings->get('order_confirmation') == "always") echo ' checked="checked"' ?> /> <label for="order_confirmation_always"><?php _e('Show for all orders','Shopp') ?></label></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="receipt_copy_both"><?php _e('Receipt Emails','Shopp'); ?></label></th> 
				<td><input type="radio" name="settings[receipt_copy]" value="0" id="receipt_copy_customer_only"<?php if ($this->Settings->get('receipt_copy') == "0") echo ' checked="checked"'; ?> /> <label for="receipt_copy_customer_only"><?php _e('Send to Customer Only','Shopp'); ?></label><br />
					<input type="radio" name="settings[receipt_copy]" value="1" id="receipt_copy_both"<?php if ($this->Settings->get('receipt_copy') == "1") echo ' checked="checked"'; ?> /> <label for="receipt_copy_both"><?php _e('Send to Customer &amp; Shop Owner Email','Shopp'); ?></label> (<?php _e('see','Shopp'); ?> <a href="?page=shopp-settings"><?php _e('General Settings','Shopp'); ?></a>)</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="account-system-none"><?php _e('Customer Accounts','Shopp'); ?></label></th> 
				<td><input type="radio" name="settings[account_system]" value="none" id="account-system-none"<?php if($this->Settings->get('account_system') == "none") echo ' checked="checked"' ?> /> <label for="account-system-none"><?php _e('No Accounts','Shopp'); ?></label><br />
					<input type="radio" name="settings[account_system]" value="shopp" id="account-system-shopp"<?php if($this->Settings->get('account_system') == "shopp") echo ' checked="checked"' ?> /> <label for="account-system-shopp"><?php _e('Enable Account Logins','Shopp'); ?></label><br />
					<input type="radio" name="settings[account_system]" value="wordpress" id="account-system-wp"<?php if($this->Settings->get('account_system') == "wordpress") echo ' checked="checked"' ?> /> <label for="account-system-wp"><?php _e('Enable Account Logins integrated with WordPress Accounts','Shopp'); ?></label></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="accounting-serial"><?php _e('Next Order Number','Shopp'); ?></label></th> 
				<td><input type="text" name="settings[next_order_id]" id="accounting-serial" value="<?php echo attribute_escape($next_setting); ?>" size="7" class="selectall" /><br />
					<?php _e('Set the next order number to sync with your accounting systems.','Shopp'); ?></td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="promo-limit"><?php _e('Promotions Limit','Shopp'); ?></label></th> 
				<td><select name="settings[promo_limit]" id="promo-limit">
					<option value="">&infin;</option>
					<?php echo menuoptions($promolimit,$this->Settings->get('promo_limit')); ?>
					</select>
					<label> <?php _e('per order','Shopp'); ?></label>
				</td>
			</tr>

		</table>
		<h3><?php _e('Digtal Product Downloads','Shopp')?></h3>
		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="download-limit"><?php _e('Download Limit','Shopp'); ?></label></th> 
				<td><select name="settings[download_limit]" id="download-limit">
					<option value="">&infin;</option>
					<?php echo menuoptions($downloads,$this->Settings->get('download_limit')); ?>
					</select>
				</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="download-timelimit"><?php _e('Time Limit','Shopp'); ?></label></th> 
				<td><select name="settings[download_timelimit]" id="download-timelimit">
					<option value=""><?php _e('No Limit','Shopp'); ?></option>
					<?php echo menuoptions($time,$this->Settings->get('download_timelimit'),true); ?>
						</select>
				</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="download-restriction"><?php _e('IP Restriction','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[download_restriction]" value="off" />
					<label for="download-restriction"><input type="checkbox" name="settings[download_restriction]" id="download-restriction" value="ip" <?php echo ($this->Settings->get('download_restriction') == "ip")?'checked="checked" ':'';?> /> <?php _e('Restrict to the computer the product is purchased from','Shopp'); ?></label></td> 
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>
<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Checkout_Settings";
</script>