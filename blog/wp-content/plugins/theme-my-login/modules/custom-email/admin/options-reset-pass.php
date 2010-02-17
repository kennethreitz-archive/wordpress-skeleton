<table class="form-table">
	<tr>
		<td>
			<p><em><?php _e('Available Variables', 'theme-my-login'); ?>: %blogname%, %siteurl%, %user_login%, %user_email%, %user_pass%, %user_ip%</em></p>
			<label for="theme_my_login_reset_pass_title"><?php _e('Subject', 'theme-my-login'); ?></label><br />
			<input name="theme_my_login[email][reset_pass][title]" type="text" id="theme_my_login_reset_pass_title" value="<?php echo $theme_my_login->options['email']['reset_pass']['title']; ?>" class="full-text" /><br />
			<label for="theme_my_login_reset_pass_message"><?php _e('Message', 'theme-my-login'); ?></label><br />
			<textarea name="theme_my_login[email][reset_pass][message]" id="theme_my_login_reset_pass_message" class="large-text" rows="10"><?php echo $theme_my_login->options['email']['reset_pass']['message']; ?></textarea><br />
			<p>
			<label for="theme_my_login_reset_pass_admin_disable"><input name="theme_my_login[email][reset_pass][admin_disable]" type="checkbox" id="theme_my_login_reset_pass_admin_disable" value="1"<?php checked(1, $theme_my_login->options['email']['reset_pass']['admin_disable']); ?> /> <?php _e('Disable Admin Notification', 'theme-my-login'); ?></label>
			</p>
		</td>
	</tr>
</table>