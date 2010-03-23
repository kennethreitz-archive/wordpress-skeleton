<?php if (SHOPP_WP27): ?>
	<div class="wrap shopp"> 
		<?php if (!empty($Shopp->Flow->Notice)): ?><div id="message" class="updated fade"><p><?php echo $Shopp->Flow->Notice; ?></p></div><?php endif; ?>

		<h2><?php _e('Customer Editor','Shopp'); ?></h2> 

		<div id="ajax-response"></div> 
		<form name="customer" id="customer" action="<?php echo add_query_arg('page',$this->Admin->customers,$Shopp->wpadminurl."admin.php"); ?>" method="post">
			<?php wp_nonce_field('shopp-save-customer'); ?>

			<div class="hidden"><input type="hidden" name="id" value="<?php echo $Customer->id; ?>" /></div>

			<div id="poststuff" class="metabox-holder has-right-sidebar">

				<div id="side-info-column" class="inner-sidebar">
				<?php
				do_action('submitpage_box');
				$side_meta_boxes = do_meta_boxes('admin_page_shopp-customers-edit', 'side', $Customer);
				?>
				</div>

				<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
				<div id="post-body-content" class="has-sidebar-content">
				<?php
				do_meta_boxes('admin_page_shopp-customers-edit', 'normal', $Customer);
				do_meta_boxes('admin_page_shopp-customers-edit', 'advanced', $Customer);
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				?>

				</div>
				</div>

			</div> <!-- #poststuff -->
		</form>
	</div>

<?php else: ?>
	
<div class="wrap shopp">

	<h2><?php _e('Customer Editor','Shopp'); ?></h2>
	
	<form name="customer" id="customer" method="post" action="<?php echo add_query_arg('page',$Shopp->Flow->Admin->customers,$Shopp->wpadminurl."admin.php"); ?>">
		<?php wp_nonce_field('shopp-save-customer'); ?>

		<div class="hidden"><input type="hidden" name="id" value="<?php echo $Customer->id; ?>" /></div>

		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="name"><?php _e('Name','Shopp'); ?></label></th> 
				<td>
					<div>
					<span><input type="text" name="firstname" value="<?php echo attribute_escape($Customer->firstname); ?>" id="firstname" size="14" /><br /> 
	            	<label for="firstname"><?php _e('First Name','Shopp'); ?></label></span>
					<span><input type="text" name="lastname" value="<?php echo attribute_escape($Customer->lastname); ?>" id="lastname" size="30" /><br />
	            	<label for="lastname"><?php _e('Last Name','Shopp'); ?></label></span><br class="clear" />
					</div>
	
					<p><input type="text" name="company" value="<?php echo attribute_escape($Customer->company); ?>" id="company" size="46" /><br /> 
            		<label for="lastname"><?php _e('Company','Shopp'); ?></label></p>
				</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="email"><?php _e('Contact','Shopp'); ?></label></th> 
				<td>
					<div>
					<span><input type="text" name="email" value="<?php echo attribute_escape($Customer->email); ?>" id="email" size="24" /><br /> 
	            	<label for="email"><?php _e('Email','Shopp'); ?> <em><?php _e('(required)')?></em></label></span>
					<span><input type="text" name="phone" value="<?php echo attribute_escape($Customer->phone); ?>" id="phone" size="20" /><br />
	            	<label for="phone"><?php _e('Phone','Shopp'); ?></label></span>
					</div>
				</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="email"><?php _e('Password','Shopp'); ?></label></th> 
				<td>
					<div>
					<span><input type="password" name="new-password" id="new-password" value="" size="20" class="selectall" /><br />
					<label for="new-password"><?php _e('Enter a new password to change it.','Shopp'); ?></label></span>
					<span><input type="password" name="confirm-password" id="confirm-password" value="" size="20" class="selectall" /><br />
					<label for="confirm-password"><?php _e('Confirm the new password.','Shopp'); ?></label></span>
					</div>
					<br class="clear" />
					<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
					<br class="clear" />
				</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="billing-address"><?php _e('Billing Address','Shopp'); ?></label></th> 
				<td>
					<div>
						<input type="text" name="billing[address]" id="billing-address" value="<?php echo $Customer->Billing->address; ?>" size="46" /><br />
						<input type="text" name="billing[xaddress]" id="billing-xaddress" value="<?php echo $Customer->Billing->xaddress; ?>" size="46" /><br />
						<label for="billing-address"><?php _e('Street Address','Shopp'); ?></label>
					</div>
					<p>
						<span>
						<input type="text" name="billing[city]" id="billing-city" value="<?php echo $Customer->Billing->city; ?>" size="14" /><br />
						<label for="billing-city"><?php _e('City','Shopp'); ?></label>
						</span>
						<span id="billing-state-inputs">
							<select name="billing[state]" id="billing-state">
								<?php echo menuoptions($Customer->billing_states,$Customer->Billing->state,true); ?>
							</select>
							<input name="billing[state]" id="billing-state-text" value="<?php echo $Customer->Billing->state; ?>" size="12" disabled="disabled"  class="hidden" /><br />
						<label for="billing-state"><?php _e('State / Province','Shopp'); ?></label>
						</span>
						<span>
						<input type="text" name="billing[postcode]" id="billing-postcode" value="<?php echo $Customer->Billing->postcode; ?>" size="10" /><br />
						<label for="billing-postcode"><?php _e('Postal Code','Shopp'); ?></label>
						</span>
						<br class="clear" />
					</p>
					<p>
						<select name="billing[country]" id="billing-country">
							<?php echo menuoptions($Customer->countries,$Customer->Billing->country,true); ?>
						</select><br />
						<label for="billing-country"><?php _e('Country','Shopp'); ?></label>
					</p>
				</td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="shipping-address"><?php _e('Shipping Address','Shopp'); ?></label></th> 
				<td>
					<p>
						<input type="text" name="shipping[address]" id="shipping-address" value="<?php echo $Customer->Shipping->address; ?>" size="46" /><br />
						<input type="text" name="shipping[xaddress]" id="shipping-xaddress" value="<?php echo $Customer->Shipping->xaddress; ?>" size="46" /><br />
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
							<input name="shipping[state]" id="shipping-state-text" value="<?php echo $Customer->Shipping->state; ?>" size="12" disabled="disabled"  class="hidden" /><br />
						<label for="shipping-state"><?php _e('State / Province','Shopp'); ?></label>
						</span>
						<span>
						<input type="text" name="shipping[postcode]" id="shipping-postcode" value="<?php echo $Customer->Shipping->postcode; ?>" size="10" /><br />
						<label for="shipping-postcode"><?php _e('Postal Code','Shopp'); ?></label>
						</span>
						<br class="clear" />
					</p>
					<p>
						<select name="shipping[country]" id="shipping-country">
							<?php echo menuoptions($Customer->countries,$Customer->Shipping->country,true); ?>
						</select><br />
						<label for="shipping-country"><?php _e('Country','Shopp'); ?></label>
					</p>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" name="save" value="Save Changes" /></p>
	</form>
</div>
<?php endif; ?>

<div id="starts-calendar" class="calendar"></div>
<div id="ends-calendar" class="calendar"></div>

<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Editing_a_Customer";

var PWD_INDICATOR = "<?php _e('Strength indicator'); ?>";

var PWD_GOOD = "<?php _e('Good'); ?>";
var PWD_BAD = "<?php _e('Bad'); ?>";
var PWD_SHORT = "<?php _e('Short'); ?>";
var PWD_STRONG = "<?php _e('Strong'); ?>";

jQuery(document).ready( function() {

var $=jQuery.noConflict();

var wp26 = <?php echo (SHOPP_WP27)?'false':'true'; ?>;
var regions = <?php echo json_encode($regions); ?>;

if (!wp26) {
	postboxes.add_postbox_toggles('admin_page_shopp-customers-edit');
	// close postboxes that should be closed
	jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
}

$('#username').click(function () {
	document.location.href = '/wp-admin/user-edit.php?user_id='+$('#userid').val();
});

updateStates('#billing-country','#billing-state-inputs');
updateStates('#shipping-country','#shipping-state-inputs');

function updateStates (country,state)  {
	var selector = $(state).find('select');
	var text = $(state).find('input');
	var label = $(state).find('label');

	function toggleStateInputs () {
		if ($(selector).children().length > 1) {
			$(selector).show().attr('disabled',false);
			$(text).hide().attr('disabled',true);
			$(label).attr('for',$(selector).attr('id'))
		} else {
			$(selector).hide().attr('disabled',true);
			$(text).show().attr('disabled',false).val('');
			$(label).attr('for',$(text).attr('id'))
		}
		
	}

	$(country).change(function() {
		if ($(selector).attr('type') == "text") return true;
		$(selector).empty().attr('disabled',true);
		$('<option></option>').val('').html('').appendTo(selector);
		if (regions[this.value]) {
			$.each(regions[this.value], function (value,label) {
				option = $('<option></option>').val(value).html(label).appendTo(selector);
			});
			$(selector).attr('disabled',false);
		}
		toggleStateInputs();
	});
	
	toggleStateInputs();
	
}

// Included from the WP 2.8 password strength meter
// Copyright by Automattic
$('#new-password').val('').keyup( check_pass_strength );

function check_pass_strength () {
	var pass = $('#new-password').val(), user = $('#email').val(), strength;

	$('#pass-strength-result').removeClass('short bad good strong');
	if ( ! pass ) {
		$('#pass-strength-result').html( PWD_INDICATOR );
		return;
	}

	strength = passwordStrength(pass, user);

	switch ( strength ) {
		case 2:
			$('#pass-strength-result').addClass('bad').html( PWD_BAD );
			break;
		case 3:
			$('#pass-strength-result').addClass('good').html( PWD_GOOD );
			break;
		case 4:
			$('#pass-strength-result').addClass('strong').html( PWD_STRONG );
			break;
		default:
			$('#pass-strength-result').addClass('short').html( PWD_SHORT );
	}
}

function passwordStrength(password,username) {
    var shortPass = 1, badPass = 2, goodPass = 3, strongPass = 4, symbolSize = 0, natLog, score;

	//password < 4
    if (password.length < 4 ) { return shortPass };

    //password == username
    if (password.toLowerCase()==username.toLowerCase()) return badPass;

	if (password.match(/[0-9]/)) symbolSize +=10;
	if (password.match(/[a-z]/)) symbolSize +=26;
	if (password.match(/[A-Z]/)) symbolSize +=26;
	if (password.match(/[^a-zA-Z0-9]/)) symbolSize +=31;

	natLog = Math.log( Math.pow(symbolSize,password.length) );
	score = natLog / Math.LN2;
	if (score < 40 )  return badPass
	if (score < 56 )  return goodPass
    return strongPass;
}

});

</script>