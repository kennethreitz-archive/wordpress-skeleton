<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<h2><?php _e('Payments Settings','Shopp'); ?></h2>

	<form name="settings" id="payments" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-payments'); ?>

		<?php include("navigation.php"); ?>

		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="payment-gateway"><?php _e('Payment Gateway','Shopp'); ?></label></th> 
				<td><select name="settings[payment_gateway]" id="payment-gateway">
					<option value=""><?php _e('No On-site Checkout','Shopp'); ?></option>
					<?php echo menuoptions($gateways,$payment_gateway,true); ?>
					</select><br /> 
	            <?php _e('Select the payment gateway processor you will be using to process credit card transactions.','Shopp'); ?></td>
			</tr>
			<tbody id="payment-settings">
				<?php if (is_array($LocalProcessors)) foreach ($LocalProcessors as &$Processor) $Processor->settings(); ?>
			</tbody>
			<?php  if (is_array($XcoProcessors)): foreach ($XcoProcessors as &$Processor): ?>
				<tr><?php $Processor->settings(); ?></tr>
			<?php endforeach; endif; ?>
 		</table>
		
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Payments_Settings";

function xcosettings (toggle,settings) {
  	(function($) {
	toggle = $(toggle);
	settings = $(settings);
	if (!toggle.attr('checked')) settings.hide();
	toggle.change(function () { settings.slideToggle(250); });
	})(jQuery);
}


jQuery(document).ready( function() {
	var $=jQuery.noConflict();
var gatewayHandlers = new CallbackRegistry();

<?php foreach ($LocalProcessors as &$Processor) $Processor->registerSettings(); ?>
<?php foreach ($XcoProcessors as &$Processor) $Processor->registerSettings(); ?>

$('#payment-gateway').change(function () {
	$('#payment-settings tr').hide();
	var target = '#'+gatewayHandlers.get(this.value);
	if (this.value.length > 0) $(target).show();
}).change();

});
</script>