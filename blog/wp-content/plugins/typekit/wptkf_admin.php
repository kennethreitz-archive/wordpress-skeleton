<?php 
	if($_POST['wptkf_embed_code_hidden'] == 'YES') {
		if ( current_user_can('edit_plugins') ) {

			$wptkf_embed_code = stripslashes($_POST['wptkf_embed_code']);
			update_option('wptkf_embed_code', $wptkf_embed_code);
			?>
			<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
		<?php
		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.', 'typekit').'</p>');
		}
	} else {
		$wptkf_embed_code = get_option('wptkf_embed_code');
	}
	
	if ( ($_POST['wptkf_activate_hidden'] == 'YES') ) {
		if ( current_user_can('edit_plugins') ) {

			$wptkf_post_activate_settings = array();
			for ($w = 0; $w < count($_POST['activate_settings_arrays']['wptkf_activate_settings']); $w++){
				$e = $_POST['activate_settings_arrays']['wptkf_activate_settings'][$w];
					$e['condition']    = ($e['condition']);
					
					if($_POST['activate_setting_enabled'] != '') {
					if(in_array($e['condition'], $_POST['activate_setting_enabled'])) {
						$e['enabled'] = 'true';
					} else {
						$e['enabled'] = 'false';
					}
					}
					$wptkf_post_activate_settings[]    = $e;
			}

			$_POST['activate_settings_arrays']['wptkf_activate_settings'] = $wptkf_post_activate_settings;
			
			update_option('wptkf_activate_settings', $_POST['activate_settings_arrays']);
			
			$message_export = '<br class="clear" /><div class="updated fade"><p>';
			$message_export.= __('Activate Typekit!', 'typekit');
			$message_export.= '</p></div>';

		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.', 'typekit').'</p>');
		}
	}
	
	if ( ($_POST['browser_detection'] == 'YES') ) {
		if ( current_user_can('edit_plugins') ) {

			$message_export = '<br class="clear" /><div class="updated fade"><p>';

			if($_POST['wptkf_browser_support_settings'] == '1' ) {
				$wptkf_bss = 'true';
				$message_export.= __('Browser Detection Enabled!', 'ccrdfa');
			} else {
				$wptkf_bss = 'false';
				$message_export.= __('Browser Detection Disabled!', 'ccrdfa');
			}

			update_option('wptkf_browser_support_settings', $wptkf_bss);

			$message_export.= '</p></div>';

		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.', 'ccrdfa').'</p>');
		}
	}


?>

<div class="wrap">
		<h2><?php _e('Typekit Config Options', 'typekit'); ?></h2>
		<?php echo $message . $message_export; ?>
		<br class="clear" />
		<div id="poststuff" class="ui-sortable">
			<div class="postbox">
				<h3><?php echo "Typekit Embed Code:";  ?></h3>
				<div class="inside">
					<br class="clear" />
					<form name="wptkf_embed_code_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
						<input type="hidden" name="wptkf_embed_code_hidden" value="YES" />
						<p><textarea id="embed_code" rows="4" cols="65" name="wptkf_embed_code"><?php echo $wptkf_embed_code; ?></textarea></p>
						<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?>" /></p>
					</form>
				</div>
			</div>
		</div>
		<br class="clear" />
		<div id="poststuff" class="ui-sortable">
			<div class="postbox ">
				<h3><?php _e('Activate Typekit', 'typekit'); ?></h3>
				<div class="inside">
					<h4><?php _e('Typekit Activate if condition is true', 'typekit'); ?></h4>
					<form name="wptkf_activate_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
						<input type="hidden" name="wptkf_activate_hidden" value="YES" />
						<div>
					
				<?php
					$wptkf_activate_settings = get_option('wptkf_activate_settings');
					$activate_settings_checked = '';

					
					for ($q = 0; $q < count($wptkf_activate_settings['wptkf_activate_settings']); $q++) {
						$activate_settings_array = $wptkf_activate_settings['wptkf_activate_settings'][$q];
						if($activate_settings_array['enabled'] == 'true' ) { 
							$activate_settings_checked = ' checked="checked"';
						}
						echo '	
							<div>
								<label for="prefixes_' . $activate_settings_array['condition'] . '">
								<input type="checkbox"'.$activate_settings_checked. ' value="' . $activate_settings_array['condition'] . '" id="prefixes_' . $activate_settings_array['condition'] . '" name="activate_setting_enabled[]"/> 
								<input type="hidden" name="activate_settings_arrays[wptkf_activate_settings][' . $q . '][condition]" value="'.$activate_settings_array['condition'].'" />
								<strong>' . $activate_settings_array['condition'] . '</strong>			
								</label>
							</div>
						';
						$activate_settings_checked = '';
						
					}
					?>					
						</div>
						<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?>" /></p>
					</form>
				</div>
			</div>
		</div>
		<br class="clear" />
		<div id="poststuff" class="ui-sortable">
			<div class="postbox ">
				<h3><?php _e('Browser Detection', 'typekit'); ?> </h3>

				<div class="inside">
					<h4><?php _e('If Enabled typekit font will only enable @font-face supported browsers only', 'typekit'); ?> </h4>
					<form name="browser_support_form" method="post" action="">

						<div><?php $wptkf_browser_support_settings = get_option('wptkf_browser_support_settings'); ?>
							<div><label for="wptkf_browser_support_settings_disabled"><input id="wptkf_browser_support_settings_disabled" type="radio" <?php if($wptkf_browser_support_settings == 'false' ) { ?>checked="checked" <?php } ?>value="0" name="wptkf_browser_support_settings" /> Disabled</label></div>
							<div><label for="wptkf_browser_support_settings_enabled"><input id="wptkf_browser_support_settings_enabled" type="radio" <?php if($wptkf_browser_support_settings == 'true' ) { ?>checked="checked" <?php } ?>value="1" name="wptkf_browser_support_settings"/> Enabled</label></div>
						</div>

						<p id="submitbutton">
							<input class="button" type="submit" name="submit_browser_detection" value="<?php _e('Update Options'); ?>" /> 
							<input type="hidden" name="browser_detection" value="YES" />
						</p>
					</form>
					
				</div>
			</div>
	
		
</div><!-- /wrap -->