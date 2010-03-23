<?php
function save_meta_box () {
?>
<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" />
</div>
<?php
}
add_meta_box('save-customer', __('Save','Shopp'), 'save_meta_box', 'admin_page_shopp-customers-edit', 'side', 'core');

function password_meta_box () {
?>
<p>
	<input type="password" name="new-password" id="new-password" value="" size="20" class="selectall" /><br />
	<label for="new-password"><?php _e('Enter a new password to change it.','Shopp'); ?></label>
</p>
<p>
	<input type="password" name="confirm-password" id="confirm-password" value="" size="20" class="selectall" /><br />
	<label for="confirm-password"><?php _e('Confirm the new password.','Shopp'); ?></label>
</p>
<br class="clear" />
<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
<br class="clear" />
<?php
}
add_meta_box('change-password', __('Change Password','Shopp'), 'password_meta_box', 'admin_page_shopp-customers-edit', 'side', 'core');

function profile_meta_box ($Customer) {
	$wp_user = get_userdata($Customer->wpuser);
	if (!empty($wp_user)):
?>
<p>
	<span>
	<input type="hidden" name="userid" id="userid" value="<?php echo $Customer->wpuser; ?>" />
	<input type="text" name="username" id="username" value="<?php echo $wp_user->user_login; ?>" size="24" readonly="readonly" class="clickable" /><br />
	<label for="username"><?php _e('Login (Click to edit user)','Shopp'); ?></label>
	</span>
<?php endif; ?>
<p> 
	<span>
	<input type="text" name="firstname" id="firstname" value="<?php echo $Customer->firstname; ?>" size="14" /><br />
	<label for="firstname"><?php _e('First Name','Shopp'); ?></label>
	</span>
	<span>
	<input type="text" name="lastname" id="lastname" value="<?php echo $Customer->lastname; ?>" size="30" /><br />
	<label for="lastname"><?php _e('Last Name','Shopp'); ?></label>
	</span>
</p>
<p>
	<input type="text" name="company" id="company" value="<?php echo $Customer->company; ?>" /><br />
	<label for="company"><?php _e('Company','Shopp'); ?></label>
</p>
<p>
	<span>
	<input type="text" name="email" id="email" value="<?php echo $Customer->email; ?>" size="24" /><br />
	<label for="email"><?php _e('Email','Shopp'); ?> <em><?php _e('(required)')?></em></label>
	</span>
	<span>
	<input type="text" name="phone" id="phone" value="<?php echo $Customer->phone; ?>" size="20" /><br />
	<label for="phone"><?php _e('Phone','Shopp'); ?></label>
	</span>
</p>
<?php if (is_array($Customer->info)):
		foreach($Customer->info as $name => $info): ?>
		<p>
			<input type="text" name="info[<?php echo $name; ?>]" id="info-<?php echo sanitize_title_with_dashes($name); ?>" value="<?php echo $info; ?>" /><br />
			<label for="info-<?php echo sanitize_title_with_dashes($name); ?>"><?php echo $name; ?></label>
		</p>	
<?php endforeach; endif;?>


<br class="clear" />

<?php
}
add_meta_box('customer-profile', __('Profile','Shopp'), 'profile_meta_box', 'admin_page_shopp-customers-edit', 'normal', 'core');

function billing_meta_box ($Customer) {
?>
<p>
	<input type="text" name="billing[address]" id="billing-address" value="<?php echo $Customer->Billing->address; ?>" /><br />
	<input type="text" name="billing[xaddress]" id="billing-xaddress" value="<?php echo $Customer->Billing->xaddress; ?>" /><br />
	<label for="billing-address"><?php _e('Street Address','Shopp'); ?></label>
</p>
<p>
	<span>
	<input type="text" name="billing[city]" id="billing-city" value="<?php echo $Customer->Billing->city; ?>" size="14" /><br />
	<label for="billing-city"><?php _e('City','Shopp'); ?></label>
	</span>
	<span id="billing-state-inputs">
		<select name="billing[state]" id="billing-state">
			<?php echo menuoptions($Customer->billing_states,$Customer->Billing->state,true); ?>
		</select>
		<input name="billing[state]" id="billing-state-text" value="<?php echo $Customer->Billing->state; ?>" size="12" disabled="disabled"  class="hidden" />
	<label for="billing-state"><?php _e('State / Province','Shopp'); ?></label>
	</span>
	<span>
	<input type="text" name="billing[postcode]" id="billing-postcode" value="<?php echo $Customer->Billing->postcode; ?>" size="10" /><br />
	<label for="billing-postcode"><?php _e('Postal Code','Shopp'); ?></label>
	</span>
</p>
<p>
	<span>
		<select name="billing[country]" id="billing-country">
			<?php echo menuoptions($Customer->countries,$Customer->Billing->country,true); ?>
		</select>
	<label for="billing-country"><?php _e('Country','Shopp'); ?></label>
	</span>
</p>

<br class="clear" />
<?php
}
add_meta_box('customer-billing', __('Billing Address','Shopp'), 'billing_meta_box', 'admin_page_shopp-customers-edit', 'normal', 'core');

function shipping_meta_box ($Customer) {
?>
<p>
	<input type="text" name="shipping[[address]" id="shipping-address" value="<?php echo $Customer->Shipping->address; ?>" /><br />
	<input type="text" name="shipping[xaddress]" id="shipping-xaddress" value="<?php echo $Customer->Shipping->xaddress; ?>" /><br />
	<label for="shipping-address"><?php _e('Street Address','Shopp'); ?></label>
</p>
<p>
	<span>
	<input type="text" name="shipping[city]" id="shipping-city" value="<?php echo $Customer->Shipping->city; ?>" size="14" /><br />
	<label for="shipping-city"><?php _e('City','Shopp'); ?></label>
	</span>
	<span id="shipping-state-inputs">
		<select name="shipping[state]" id="shipping-state">
			<?php echo menuoptions($Customer->billing_states,$Customer->Shipping->state,true); ?>
		</select>
		<input name="shipping[state]" id="shipping-state-text" value="<?php echo $Customer->Shipping->state; ?>" size="12" disabled="disabled"  class="hidden" />
	<label for="shipping-state"><?php _e('State / Province','Shopp'); ?></label>
	</span>
	<span>
	<input type="text" name="shipping[postcode]" id="shipping-postcode" value="<?php echo $Customer->Shipping->postcode; ?>" size="10" /><br />
	<label for="shipping-postcode"><?php _e('Postal Code','Shopp'); ?></label>
	</span>
</p>
<p>
	<span>
		<select name="shipping[country]" id="shipping-country">
			<?php echo menuoptions($Customer->countries,$Customer->Shipping->country,true); ?>
		</select>
	<label for="shipping-country"><?php _e('Country','Shopp'); ?></label>
	</span>
</p>

<br class="clear" />
<?php
}
add_meta_box('customer-shipping', __('Shipping Address','Shopp'), 'shipping_meta_box', 'admin_page_shopp-customers-edit', 'normal', 'core');

?>
