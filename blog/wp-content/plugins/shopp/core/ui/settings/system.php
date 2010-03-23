<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>
	<h2><?php _e('System Settings','Shopp'); ?></h2>
	
	<form name="settings" id="system" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-system'); ?>
		
		<?php include("navigation.php"); ?>

		<table class="form-table"> 
			<tr>
				<th scope="row" valign="top"><label for="image-storage"><?php _e('Image Storage','Shopp'); ?></label></th> 
				<td><select name="settings[image_storage_pref]" id="image-storage">
					<?php echo menuoptions($filesystems,$this->Settings->get('image_storage_pref'),true); ?>
					</select>
					<p id="image-path-settings">
						<input type="text" name="settings[image_path]" id="image-path" value="<?php echo attribute_escape($this->Settings->get('image_path')); ?>" size="40" /><br class="clear" />
						<label for="image-path"><?php echo $imagepath_status; ?></label>
					</p>
	            </td>
			</tr>			
			<tr>
				<th scope="row" valign="top"><label for="product-storage"><?php _e('Product File Storage','Shopp'); ?></label></th> 
				<td><select name="settings[product_storage_pref]" id="product-storage">
					<?php echo menuoptions($filesystems,$this->Settings->get('product_storage_pref'),true); ?>
					</select>
					<p id="products-path-settings"><input type="text" name="settings[products_path]" id="products-path" value="<?php echo attribute_escape($this->Settings->get('products_path')); ?>" size="40" /><br class="clear" />
						<label for="products-path"><?php echo $productspath_status; ?></label>
						</p>
	            </td>
			</tr>
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="uploader-toggle"><?php _e('Upload System','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[uploader_pref]" value="browser" /><input type="checkbox" name="settings[uploader_pref]" value="flash" id="uploader-toggle"<?php if ($this->Settings->get('uploader_pref') == "flash") echo ' checked="checked"'?> /><label for="uploader-toggle"> <?php _e('Enable Flash-based uploading','Shopp'); ?></label><br /> 
	            <?php _e('Enable to use Adobe Flash uploads for accurate upload progress. Disable this setting if you are having problems uploading.','Shopp'); ?></td>
			</tr>			
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="script-loading"><?php _e('Script Loading','Shopp'); ?></label></th> 
				<td><input type="hidden" name="settings[script_loading]" value="catalog" /><input type="checkbox" name="settings[script_loading]" value="global" id="script-loading"<?php if ($this->Settings->get('script_loading') == "global") echo ' checked="checked"'?> /><label for="script-loading"> <?php _e('Enable Shopp behavioral scripts site-wide','Shopp'); ?></label><br /> 
	            <?php _e('Enable this to make Shopp behaviors available across all of your WordPress posts and pages.','Shopp'); ?></td>
			</tr>			
			<tr>
				<th scope="row" valign="top"><label for="error-notifications"><?php _e('Error Notifications','Shopp'); ?></label></th> 
				<td><ul id="error_notify">
					<?php foreach ($notification_errors as $id => $level): ?>
						<li><input type="checkbox" name="settings[error_notifications][]" id="error-notification-<?php echo $id; ?>" value="<?php echo $id; ?>"<?php if (in_array($id,$notifications)) echo ' checked="checked"'; ?>/><label for="error-notification-<?php echo $id; ?>"> <?php echo $level; ?></label></li>
					<?php endforeach; ?>
					</ul>
					<label for="error-notifications"><?php _e("Send email notifications of the selected errors to the merchant's email address.","Shopp"); ?></label>
	            </td>
			</tr>			
			<tr>
				<th scope="row" valign="top"><label for="error-logging"><?php _e('Error Logging','Shopp'); ?></label></th> 
				<td><select name="settings[error_logging]" id="error-logging">
					<?php echo menuoptions($errorlog_levels,$this->Settings->get('error_logging'),true); ?>
					</select><br />
					<label for="error-notifications"><?php _e("Limit logging errors up to the level of the selected error type.","Shopp"); ?></label>
	            </td>
			</tr>
			<?php if (count($recentlog) > 0): ?>
			<tr>
				<th scope="row" valign="top"><label for="error-logging"><?php _e('Error Log','Shopp'); ?></label></th> 
				<td><div id="errorlog"><ol><?php foreach ($recentlog as $line): ?>
					<li><?php echo $line; ?></li>
				<?php endforeach; ?></ol></div>
				<p class="alignright"><button name="resetlog" id="resetlog" value="resetlog" class="button"><small><?php _e("Reset Log","Shopp"); ?></small></button></p>
				</td>
			</tr>			
			<?php endif; ?>
		</table>
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>
<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>System_Settings";

(function($){
	
	$('#image-storage').change(function () {
		$('#image-path-settings').slideToggle(300);
	});
	
	$('#image-storage').ready(function () {
		if ($('#image-storage').val() == 'db') $('#image-path-settings').hide();
	});

	$('#product-storage').change(function () {
		$('#products-path-settings').slideToggle(300);
	});
	
	$('#product-storage').ready(function () {
		if ($('#product-storage').val() == 'db') $('#products-path-settings').hide();
	});
	
	$('#errorlog').scrollTop($('#errorlog').attr('scrollHeight'));

})(jQuery);

</script>