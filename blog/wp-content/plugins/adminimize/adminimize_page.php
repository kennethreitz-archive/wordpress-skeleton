<?php
/**
 * options-page in wp-admin
 */

// export options
if ( isset( $_GET['_mw_adminimize_export'] ) ) {
	_mw_adminimize_export();
	die();
}

function _mw_adminimize_options() {
	global $wpdb, $_wp_admin_css_colors, $wp_version, $wp_roles;

	$_mw_adminimize_user_info = '';

	//get array with userroles
	$user_roles = get_all_user_roles();
	$user_roles_names = get_all_user_roles_names();

	// update options
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_insert') && $_POST['_mw_adminimize_save'] ) {

		if ( function_exists('current_user_can') && current_user_can('manage_options') ) {
			check_admin_referer('mw_adminimize_nonce');

			_mw_adminimize_update();

		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}
	
	// import options
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_import') && $_POST['_mw_adminimize_save'] ) {

		if ( function_exists('current_user_can') && current_user_can('manage_options') ) {
			check_admin_referer('mw_adminimize_nonce');
			
			_mw_adminimize_import();
			
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}
	
	// deinstall options
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_deinstall') &&  ($_POST['_mw_adminimize_deinstall_yes'] != '_mw_adminimize_deinstall') ) {

		$myErrors = new _mw_adminimize_message_class();
		$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_deinstall_yes') . '</p></div>';
		wp_die($myErrors);
	}

	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_deinstall') && $_POST['_mw_adminimize_deinstall'] && ($_POST['_mw_adminimize_deinstall_yes'] == '_mw_adminimize_deinstall') ) {

		if ( function_exists('current_user_can') && current_user_can('manage_options') ) {
			check_admin_referer('mw_adminimize_nonce');

			_mw_adminimize_deinstall();

			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_deinstall') . '</p></div>';
			echo $myErrors;
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}
	
	// load theme user data
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_load_theme') && $_POST['_mw_adminimize_load'] ) {
		if ( function_exists('current_user_can') && current_user_can('edit_users') ) {
			check_admin_referer('mw_adminimize_nonce');
			
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_load_theme') . '</p></div>';
			echo $myErrors;
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}
	
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_set_theme') && $_POST['_mw_adminimize_save'] ) {
		if ( function_exists('current_user_can') && current_user_can('edit_users') ) {
			check_admin_referer('mw_adminimize_nonce');
			
			_mw_adminimize_set_theme();
			
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_set_theme') . '</p></div>';
			echo $myErrors;
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}
?>
	<div class="wrap">
		<?php screen_icon('tools'); ?>
		<h2><?php _e('Adminimize', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h2>
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			
			<div id="side-info-column" class="inner-sidebar">
				<div class="meta-box-sortables">
					<div id="about" class="postbox ">
						<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
						<h3 class="hndle" id="about-sidebar"><?php _e('About the plugin', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
						<div class="inside">
							<p><?php _e('Further information: Visit the <a href="http://bueltge.de/wordpress-admin-theme-adminimize/674/">plugin homepage</a> for further information or to grab the latest version of this plugin.', FB_ADMINIMIZE_TEXTDOMAIN); ?></p>
							<p>
							<span style="float: left;">
								<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
								<input type="hidden" name="cmd" value="_s-xclick">
								<input type="hidden" name="hosted_button_id" value="4578111">
								<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="<?php _e('PayPal - The safer, easier way to pay online!', FB_ADMINIMIZE_TEXTDOMAIN); ?>">
								<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
							</form>
							</span>
							<?php _e('You want to thank me? Visit my <a href="http://bueltge.de/wunschliste/">wishlist</a> or donate.', FB_ADMINIMIZE_TEXTDOMAIN); ?>
							</p>
							<p>&copy; Copyright 2008 - <?php echo date('Y'); ?> <a href="http://bueltge.de">Frank B&uuml;ltge</a></p>
						</div>
					</div>
				</div>
			</div>
			
			<div id="post-body" class="has-sidebar">
				<div id="post-body-content" class="has-sidebar-content">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="about" class="postbox ">
							<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
							<h3 class="hndle" id="menu"><?php _e('MiniMenu', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
							<div class="inside">
								<table class="widefat" cellspacing="0">
									<tr class="alternate">
										<td class="row-title"><a href="#backend_options"><?php _e('Backend Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr>
										<td class="row-title"><a href="#global_options"><?php _e('Global options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr class="alternate">
										<td class="row-title"><a href="#config_menu"><?php _e('Menu Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr>
										<td class="row-title"><a href="#config_edit_post"><?php _e('Write options - Post', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr class="alternate">
										<td class="row-title"><a href="#config_edit_page"><?php _e('Write options - Page', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr>
										<td class="row-title"><a href="#links_options"><?php _e('Links options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr class="alternate">
										<td class="row-title"><a href="#set_theme"><?php _e('Set Theme', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr>
										<td class="row-title"><a href="#import"><?php _e('Export/Import Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr class="alternate">
										<td class="row-title"><a href="#uninstall"><?php _e('Deinstall Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
									<tr>
										<td class="row-title"><a href="#about"><?php _e('About the plugin', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<br class="clear"/>
		
		</div>
		
		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="backend_options"><?php _e('Backend Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h3>
				<div class="inside">

				<form name="backend_option" method="post" id="_mw_adminimize_options" action="?page=<?php echo $_GET['page'];?>" >
					<?php wp_nonce_field('mw_adminimize_nonce'); ?>
					<br class="clear" />
					<table summary="config" class="widefat">
						<tbody>
							<tr valign="top">
								<td><?php _e('User-Info', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_user_info = _mw_adminimize_getOptionValue('_mw_adminimize_user_info'); ?>
									<select name="_mw_adminimize_user_info">
										<option value="0"<?php if ($_mw_adminimize_user_info == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_user_info == '1') { echo ' selected="selected"'; } ?>><?php _e('Hide', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="2"<?php if ($_mw_adminimize_user_info == '2') { echo ' selected="selected"'; } ?>><?php _e('Only logout', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="3"<?php if ($_mw_adminimize_user_info == '3') { echo ' selected="selected"'; } ?>><?php _e('User &amp; Logout', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('The &quot;User-Info-area&quot; is on the top right side of the backend. You can hide or reduced show.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<?php if ( ($_mw_adminimize_user_info == '') || ($_mw_adminimize_user_info == '1') || ($_mw_adminimize_user_info == '0') ) $disabled_item = ' disabled="disabled"' ?>
							<tr valign="top" class="form-invalid">
								<td><?php _e('Change User-Info, redirect to', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_ui_redirect = _mw_adminimize_getOptionValue('_mw_adminimize_ui_redirect'); ?>
									<select name="_mw_adminimize_ui_redirect" <?php echo $disabled_item ?>>
										<option value="0"<?php if ($_mw_adminimize_ui_redirect == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_ui_redirect == '1') { echo ' selected="selected"'; } ?>><?php _e('Frontpage of the Blog', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
									</select> <?php _e('When the &quot;User-Info-area&quot; change it, then it is possible to change the redirect.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Footer', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_footer = _mw_adminimize_getOptionValue('_mw_adminimize_footer'); ?>
									<select name="_mw_adminimize_footer">
										<option value="0"<?php if ($_mw_adminimize_footer == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_footer == '1') { echo ' selected="selected"'; } ?>><?php _e('Hide', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('The Footer-area kann hide, include all links and details.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('WriteScroll', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_writescroll = _mw_adminimize_getOptionValue('_mw_adminimize_writescroll'); ?>
									<select name="_mw_adminimize_writescroll">
										<option value="0"<?php if ($_mw_adminimize_writescroll == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_writescroll == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('With the WriteScroll option active, these pages will automatically scroll to an optimal position for editing, when you visit Write Post or Write Page.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Timestamp', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_timestamp = _mw_adminimize_getOptionValue('_mw_adminimize_timestamp'); ?>
									<select name="_mw_adminimize_timestamp">
										<option value="0"<?php if ($_mw_adminimize_timestamp == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_timestamp == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('Opens the post timestamp editing fields without you having to click the "Edit" link every time.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Thickbox FullScreen', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_tb_window = _mw_adminimize_getOptionValue('_mw_adminimize_tb_window'); ?>
									<select name="_mw_adminimize_tb_window">
										<option value="0"<?php if ($_mw_adminimize_tb_window == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_tb_window == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('All Thickbox-function use the full area of the browser. Thickbox is for examble in upload media-files.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Flashuploader', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_control_flashloader = _mw_adminimize_getOptionValue('_mw_adminimize_control_flashloader'); ?>
									<select name="_mw_adminimize_control_flashloader">
										<option value="0"<?php if ($_mw_adminimize_control_flashloader == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_control_flashloader == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('Disable the flashuploader and users use only the standard uploader.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Category Height', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_cat_full = _mw_adminimize_getOptionValue('_mw_adminimize_cat_full'); ?>
									<select name="_mw_adminimize_cat_full">
										<option value="0"<?php if ($_mw_adminimize_cat_full == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_cat_full == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select> <?php _e('View the Meta Box with Categories in the full height, no scrollbar or whitespace.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Advice in Footer', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
								<td>
									<?php $_mw_adminimize_advice = _mw_adminimize_getOptionValue('_mw_adminimize_advice'); ?>
									<select name="_mw_adminimize_advice">
										<option value="0"<?php if ($_mw_adminimize_advice == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										<option value="1"<?php if ($_mw_adminimize_advice == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
									</select>
									<textarea style="width: 85%;" class="code" rows="1" cols="60" name="_mw_adminimize_advice_txt" id="_mw_adminimize_advice_txt" ><?php echo htmlspecialchars(stripslashes(_mw_adminimize_getOptionValue('_mw_adminimize_advice_txt'))); ?></textarea><br /><?php _e('In Footer kann you display a advice for change the Default-design, (x)HTML is possible.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
							<?php
							// when remove dashboard
							foreach ($user_roles as $role) {
								$disabled_menu_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_menu_'. $role .'_items');
								$disabled_submenu_[$role] = _mw_adminimize_getOptionValue('mw_adminimize_disabled_submenu_'. $role .'_items');
							}

							$disabled_menu_all = array();
							foreach ($user_roles as $role) {
								array_push($disabled_menu_all, $disabled_menu_[$role]);
								array_push($disabled_menu_all, $disabled_submenu_[$role]);
							}

							if ($disabled_menu_all != '') {
								if ( !recursive_in_array('index.php', $disabled_menu_all) ) {
									$disabled_item2 = ' disabled="disabled"';
								}
								?>
								<tr valign="top" class="form-invalid">
									<td><?php _e('Dashboard deactivate, redirect to', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
									<td>
										<?php $_mw_adminimize_db_redirect = _mw_adminimize_getOptionValue('_mw_adminimize_db_redirect'); ?>
										<select name="_mw_adminimize_db_redirect"<?php echo $disabled_item2; ?>>
											<option value="0"<?php if ($_mw_adminimize_db_redirect == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', FB_ADMINIMIZE_TEXTDOMAIN ); ?> (profile.php)</option>
											<option value="1"<?php if ($_mw_adminimize_db_redirect == '1') { echo ' selected="selected"'; } ?>><?php _e('Manage Posts', FB_ADMINIMIZE_TEXTDOMAIN ); ?> (edit.php)</option>
											<option value="2"<?php if ($_mw_adminimize_db_redirect == '2') { echo ' selected="selected"'; } ?>><?php _e('Manage Pages', FB_ADMINIMIZE_TEXTDOMAIN ); ?> (edit-pages.php)</option>
											<option value="3"<?php if ($_mw_adminimize_db_redirect == '3') { echo ' selected="selected"'; } ?>><?php _e('Write Post', FB_ADMINIMIZE_TEXTDOMAIN ); ?> (post-new.php)</option>
											<option value="4"<?php if ($_mw_adminimize_db_redirect == '4') { echo ' selected="selected"'; } ?>><?php _e('Write Page', FB_ADMINIMIZE_TEXTDOMAIN ); ?> (page-new.php)</option>
											<option value="5"<?php if ($_mw_adminimize_db_redirect == '5') { echo ' selected="selected"'; } ?>><?php _e('Comments', FB_ADMINIMIZE_TEXTDOMAIN ); ?> (edit-comments.php)</option>
											<option value="6"<?php if ($_mw_adminimize_db_redirect == '6') { echo ' selected="selected"'; } ?>><?php _e('other Page', FB_ADMINIMIZE_TEXTDOMAIN ); ?></option>
										</select>
										<textarea style="width: 85%;" class="code" rows="1" cols="60" name="_mw_adminimize_db_redirect_txt" id="_mw_adminimize_db_redirect_txt" ><?php echo htmlspecialchars(stripslashes(_mw_adminimize_getOptionValue('_mw_adminimize_db_redirect_txt'))); ?></textarea>
										<br /><?php _e('You have deactivate the Dashboard, please select a page for redirect?', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<p id="submitbutton">
						<input class="button button-primary" type="submit" name="_mw_adminimize_save" value="<?php _e('Update Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="global_options"><?php _e('Global options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h3>
				<div class="inside">
					<br class="clear" />

					<table summary="config_edit_post" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Option', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<?php
									foreach ($user_roles_names as $role_name) { ?>
										<th><?php _e('Deactivate for', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br/>' . $role_name; ?></th>
								<?php } ?>
							</tr>
						</thead>

						<tbody>
						<?php
							foreach ($user_roles as $role) {
								$disabled_global_option_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_global_option_'. $role .'_items');
							}
								
							$global_options = array(
																			'#favorite-actions',
																			'#screen-meta',
																			'#screen-options, #screen-options-link-wrap',
																			'#contextual-help-link-wrap',
																			'#your-profile .form-table fieldset'
																			);
							
							$global_options_names = array(
																			__('Favorite Actions', FB_ADMINIMIZE_TEXTDOMAIN),
																			__('Screen-Meta', FB_ADMINIMIZE_TEXTDOMAIN),
																			__('Screen Options', FB_ADMINIMIZE_TEXTDOMAIN),
																			__('Contextual Help', FB_ADMINIMIZE_TEXTDOMAIN),
																			__('Admin Color Scheme', FB_ADMINIMIZE_TEXTDOMAIN)
																			);
							
							$_mw_adminimize_own_values  = _mw_adminimize_getOptionValue('_mw_adminimize_own_values');
							$_mw_adminimize_own_values = preg_split( "/\r\n/", $_mw_adminimize_own_values );
							foreach ( (array) $_mw_adminimize_own_values as $key => $_mw_adminimize_own_value ) {
								$_mw_adminimize_own_value = trim($_mw_adminimize_own_value);
								array_push($global_options, $_mw_adminimize_own_value);
							}
							
							$_mw_adminimize_own_options = _mw_adminimize_getOptionValue('_mw_adminimize_own_options');
							$_mw_adminimize_own_options = preg_split( "/\r\n/", $_mw_adminimize_own_options );
							foreach ( (array) $_mw_adminimize_own_options as $key => $_mw_adminimize_own_option ) {
								$_mw_adminimize_own_option = trim($_mw_adminimize_own_option);
								array_push($global_options_names, $_mw_adminimize_own_option);
							}
							
							$x = 0;
							foreach ($global_options as $index => $global_option) {
								if ( $global_option != '') {
									$checked_user_role_ = array();
									foreach ($user_roles as $role) {
										$checked_user_role_[$role]  = ( isset($disabled_global_option_[$role]) && in_array($global_option, $disabled_global_option_[$role]) ) ? ' checked="checked"' : '';
									}
									echo '<tr>' . "\n";
									echo '<td>' . $global_options_names[$index] . ' <span style="color:#ccc; font-weight: 400;">(' . $global_option . ')</span> </td>' . "\n";
									foreach ($user_roles as $role) {
										echo '<td class="num"><input id="check_post'. $role . $x .'" type="checkbox"' . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_global_option_'. $role .'_items[]" value="' . $global_option . '" /></td>' . "\n";
									}
									echo '</tr>' . "\n";
									$x++;
								}
							}
						?>
						</tbody>
					</table>
					
					<?php
					//your own global options
					?>
					<br style="margin-top: 10px;" />
					<table summary="config_edit_post" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Your own options', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br />'; _e('ID or class', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<th><?php echo '<br />'; _e('Option', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
							</tr>
						</thead>

						<tbody>
							<tr valign="top">
								<td colspan="2"><?php _e('It is possible to add your own IDs or classes from elements and tags. You can find IDs and classes with the FireBug Add-on for Firefox. Assign a value and the associate name per line.', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
							</tr>
							<tr valign="top">
								<td>
									<textarea name="_mw_adminimize_own_options" cols="60" rows="3" id="_mw_adminimize_own_options" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_options'); ?></textarea>
									<br />
									<?php _e('Possible nomination for ID or class. Separate multiple nomination through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
								<td>
									<textarea class="code" name="_mw_adminimize_own_values" cols="60" rows="3" id="_mw_adminimize_own_values" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_values'); ?></textarea>
									<br />
									<?php _e('Possible IDs or classes. Separate multiple values through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p id="submitbutton">
						<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_insert" />
						<input class="button button-primary" type="submit" name="_mw_adminimize_save" value="<?php _e('Update Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="config_menu"><?php _e('Menu Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h3>
				<div class="inside">
					<br class="clear" />
					
					<table summary="config_menu" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Menu options - Menu, <span style=\"font-weight: 400;\">Submenu</span>', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>

								<?php foreach ($user_roles_names as $role_name) { ?>
										<th><?php _e('Deactivate for', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br/>' . $role_name; ?></th>
								<?php } ?>

							</tr>
						</thead>
						<tbody>
							<?php
							$menu    = _mw_adminimize_getOptionValue('mw_adminimize_default_menu');
							$submenu = _mw_adminimize_getOptionValue('mw_adminimize_default_submenu');

							foreach ($user_roles as $role) {
								$disabled_metaboxes_post_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_post_'. $role .'_items');
								$disabled_metaboxes_page_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_page_'. $role .'_items');
							}

							$metaboxes = array(
								'#contextual-help-link-wrap',
								'#screen-options-link-wrap',
								'#pageslugdiv',
								'#tagsdiv,#tagsdivsb,#tagsdiv-post_tag',
								'#categorydiv,#categorydivsb',
								'#category-add-toggle',
								'#postexcerpt',
								'#trackbacksdiv',
								'#postcustom',
								'#commentsdiv',
								'#passworddiv',
								'#authordiv',
								'#revisionsdiv',
								'.side-info',
								'#notice',
								'#post-body h2',
								'#media-buttons',
								'#wp-word-count',
								'#slugdiv,#edit-slug-box',
								'#misc-publishing-actions',
								'#commentstatusdiv',
								'#editor-toolbar #edButtonHTML, #quicktags'
							);

							if ( function_exists('current_theme_supports') && current_theme_supports( 'post-thumbnails', 'post' ) )
								array_push($metaboxes, '#postimagediv');
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes, '#suggestedtags');
							if (function_exists('tc_post'))
								array_push($metaboxes, '#textcontroldiv');
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes, '#htmlspecialchars');
							if (class_exists('All_in_One_SEO_Pack'))
								array_push($metaboxes, '#postaiosp, #aiosp');
							if (function_exists('tdomf_edit_post_panel_admin_head'))
								array_push($metaboxes, '#tdomf');
							if (function_exists('post_notification_form'))
								array_push($metaboxes, '#post_notification');
							if (function_exists('sticky_add_meta_box'))
								array_push($metaboxes, '#poststickystatusdiv');

							$metaboxes_names = array(
								__('Help'),
								__('Screen Options'),
								__('Permalink', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Tags', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Categories', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Add New Category', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Excerpt', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Trackbacks', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Custom Fields'),
								__('Comments', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Password Protect This Post', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Post Author'),
								__('Post Revisions'),
								__('Related, Shortcuts', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Messenges', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('h2: Advanced Options', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Media Buttons (all)', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Word count', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Post Slug'),
								__('Publish Actions', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Discussion'),
								__('HTML Editor Button')
							);
							
							if ( function_exists('current_theme_supports') && current_theme_supports( 'post-thumbnails', 'post' ) )
								array_push($metaboxes_names, __('Post Thumbnail') );
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes_names, __('Suggested tags from'));
							if (function_exists('tc_post'))
								array_push($metaboxes_names, __('Text Control'));
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes_names, __('HTML Special Characters'));
							if (class_exists('All_in_One_SEO_Pack'))
								array_push($metaboxes_names, __('All in One SEO Pack'));
							if (function_exists('tdomf_edit_post_panel_admin_head'))
								array_push($metaboxes_names, 'TDOMF');
							if (function_exists('post_notification_form'))
								array_push($metaboxes_names, 'Post Notification');
							if (function_exists('sticky_add_meta_box'))
								array_push($metaboxes, 'Post Sticky Status');
							
							// add own post options
							$_mw_adminimize_own_post_values  = _mw_adminimize_getOptionValue('_mw_adminimize_own_post_values');
							$_mw_adminimize_own_post_values = preg_split( "/\r\n/", $_mw_adminimize_own_post_values );
							foreach ( (array) $_mw_adminimize_own_post_values as $key => $_mw_adminimize_own_post_value ) {
								$_mw_adminimize_own_post_value = trim($_mw_adminimize_own_post_value);
								array_push($metaboxes, $_mw_adminimize_own_post_value);
							}
							
							$_mw_adminimize_own_post_options = _mw_adminimize_getOptionValue('_mw_adminimize_own_post_options');
							$_mw_adminimize_own_post_options = preg_split( "/\r\n/", $_mw_adminimize_own_post_options );
							foreach ( (array) $_mw_adminimize_own_post_options as $key => $_mw_adminimize_own_post_option ) {
								$_mw_adminimize_own_post_option = trim($_mw_adminimize_own_post_option);
								array_push($metaboxes_names, $_mw_adminimize_own_post_option);
							}
							
							// pages
							$metaboxes_page = array(
								'#contextual-help-link-wrap',
								'#screen-options-link-wrap',
								'#pageslugdiv',
								'#pagepostcustom, #pagecustomdiv, #postcustom',
								'#pagecommentstatusdiv',
								'#pagepassworddiv',
								'#pageparentdiv',
								'#pagetemplatediv',
								'#pageorderdiv',
								'#pageauthordiv',
								'#revisionsdiv',
								'.side-info',
								'#notice',
								'#post-body h2',
								'#media-buttons',
								'#wp-word-count',
								'#slugdiv,#edit-slug-box',
								'#misc-publishing-actions',
								'#commentstatusdiv',
								'#editor-toolbar #edButtonHTML, #quicktags'
							);

							if ( function_exists('current_theme_supports') && current_theme_supports( 'post-thumbnails', 'page' ) )
								array_push($metaboxes_page, '#postimagediv' );
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes_page, '#suggestedtags');
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes_page, '#htmlspecialchars');
							if (class_exists('All_in_One_SEO_Pack'))
								array_push($metaboxes_page, '#postaiosp, #aiosp');
							if (function_exists('tdomf_edit_post_panel_admin_head'))
								array_push($metaboxes_page, '#tdomf');
							if (function_exists('post_notification_form'))
								array_push($metaboxes_page, '#post_notification');

							$metaboxes_names_page = array(
								__('Help'),
								__('Screen Options'),
								__('Permalink', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Custom Fields'),
								__('Comments &amp; Pings', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Password Protect This Page', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Attributes'),
								__('Page Template', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Page Order', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Page Author'),
								__('Page Revisions'),
								__('Related', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Messenges', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('h2: Advanced Options', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Media Buttons (all)', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Word count', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Page Slug'),
								__('Publish Actions', FB_ADMINIMIZE_TEXTDOMAIN ),
								__('Discussion'),
								__('HTML Editor Button')
							);

							if ( function_exists('current_theme_supports') && current_theme_supports( 'post-thumbnails', 'page' ) )
								array_push($metaboxes_names_page, __('Page Image') );
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes_names_page, __('Suggested tags from', FB_ADMINIMIZE_TEXTDOMAIN ));
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes_names_page, __('HTML Special Characters'));
							if (class_exists('All_in_One_SEO_Pack'))
								array_push($metaboxes_names_page, 'All in One SEO Pack');
							if (function_exists('tdomf_edit_post_panel_admin_head'))
								array_push($metaboxes_names_page, 'TDOMF');
							if (function_exists('post_notification_form'))
								array_push($metaboxes_names_page, 'Post Notification');
							
							// add own page options
							$_mw_adminimize_own_page_values = _mw_adminimize_getOptionValue('_mw_adminimize_own_page_values');
							$_mw_adminimize_own_page_values = preg_split( "/\r\n/", $_mw_adminimize_own_page_values );
							foreach ( (array) $_mw_adminimize_own_page_values as $key => $_mw_adminimize_own_page_value ) {
								$_mw_adminimize_own_page_value = trim($_mw_adminimize_own_page_value);
								array_push($metaboxes_page, $_mw_adminimize_own_page_value);
							}
							
							$_mw_adminimize_own_page_options = _mw_adminimize_getOptionValue('_mw_adminimize_own_page_options');
							$_mw_adminimize_own_page_options = preg_split( "/\r\n/", $_mw_adminimize_own_page_options );
							foreach ( (array) $_mw_adminimize_own_page_options as $key => $_mw_adminimize_own_page_option ) {
								$_mw_adminimize_own_page_option = trim($_mw_adminimize_own_page_option);
								array_push($metaboxes_names_page, $_mw_adminimize_own_page_option);
							}
							
							// print menu, submenu
							if ( isset($menu) && $menu != '') {

								$i = 0;
								$x = 0;
								$class = '';
								
								$users = array( 0 => 'Profile', 1 => 'edit_users', 2 => 'profile.php', 3 => '', 4 => 'menu-top', 5 => 'menu-users', 6 => 'div' );
								//array_push( $menu, $users );
								
								foreach ($menu as $item) {
									
									// non checked items
									if ( $item[2] === 'options-general.php' ) {
										//$disabled_item_adm = ' disabled="disabled"';
										$disabled_item_adm_hint = '<abbr title="' . __( 'After activate the check box it heavy attitudes will change.', FB_ADMINIMIZE_TEXTDOMAIN ) . '" style="cursor:pointer;"> ! </acronym>';
									} else {
										$disabled_item_adm = '';
										$disabled_item_adm_hint = '';
									}
									
									if ( $item[0] != '' ) {
										foreach($user_roles as $role) {
											// checkbox checked
												if ( isset( $disabled_menu_[$role]) && in_array($item[2],  $disabled_menu_[$role]) ) {
												$checked_user_role_[$role] = ' checked="checked"';
											} else {
												$checked_user_role_[$role] = '';
											}
										}
	
										echo '<tr class="form-invalid">' . "\n";
										echo "\t" . '<th>' . $item[0] . ' <span style="color:#ccc; font-weight: 400;">(' . $item[2] . ')</span> </th>';
										foreach ($user_roles as $role) {
											if ( $role != 'administrator' ) { // only admin disable items
												$disabled_item_adm = '';
												$disabled_item_adm_hint = '';
											}
											echo "\t" . '<td class="num">' . $disabled_item_adm_hint . '<input id="check_menu'. $role . $x .'" type="checkbox"' . $disabled_item_adm . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_menu_'. $role .'_items[]" value="' . $item[2] . '" />' . $disabled_item_adm_hint . '</td>' . "\n";
										}
										echo '</tr>';
										
										// only for user smaller administrator, change user-Profile-File
										if ( $item[2] === 'users.php' ) {
											$x++;
											echo '<tr class="form-invalid">' . "\n";
											echo "\t" . '<th>' . __('Profile') . ' <span style="color:#ccc; font-weight: 400;">(profile.php)</span> </th>';
											foreach ($user_roles as $role) {
												echo "\t" . '<td class="num"><input disabled="disabled" id="check_menu'. $role . $x .'" type="checkbox"' . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_menu_'. $role .'_items[]" value="profile.php" /></td>' . "\n";
											}
											echo '</tr>';
										}

										$x++;

										if ( !isset($submenu[$item[2]]) )
											continue;

										// submenu items
										foreach ( $submenu[ $item[2] ] as $subitem ) {
											$class = ( ' class="alternate"' == $class ) ? '' : ' class="alternate"';
											if ( $subitem[2] === 'adminimize/adminimize.php' ) {
												//$disabled_subitem_adm = ' disabled="disabled"';
												$disabled_subitem_adm_hint = '<abbr title="' . __( 'After activate the check box it heavy attitudes will change.', FB_ADMINIMIZE_TEXTDOMAIN ) . '" style="cursor:pointer;"> ! </acronym>';
											} else {
												$disabled_subitem_adm = '';
												$disabled_subitem_adm_hint = '';
											}
											
											echo '<tr' . $class . '>' . "\n";
											foreach ($user_roles as $role) {
												if ( isset($disabled_submenu_[$role]) )
													$checked_user_role_[$role]  = ( in_array($subitem[2], $disabled_submenu_[$role] ) ) ? ' checked="checked"' : '';
											}
											echo '<td> &mdash; ' . $subitem[0] . ' <span style="color:#ccc; font-weight: 400;">(' . $subitem[2] . ')</span> </td>' . "\n";
											foreach ($user_roles as $role) {
												if ( $role != 'administrator' ) { // only admin disable items
													$disabled_subitem_adm = '';
													$disabled_subitem_adm_hint = '';
												}
												echo '<td class="num">' . $disabled_subitem_adm_hint . '<input id="check_menu'. $role.$x .'" type="checkbox"' . $disabled_subitem_adm . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_submenu_'. $role .'_items[]" value="' . $subitem[2] . '" />' . $disabled_subitem_adm_hint . '</td>' . "\n";
											}
											echo '</tr>' . "\n";
											$x++;
										}
										$i++;
										$x++;
									}
								}

							} else {
								$myErrors = new _mw_adminimize_message_class();
								$myErrors = '<tr><td style="color: red;">' . $myErrors->get_error('_mw_adminimize_get_option') . '</td></tr>';
								echo $myErrors;
							} ?>
						</tbody>
					</table>
					
					<p id="submitbutton">
						<input class="button button-primary" type="submit" name="_mw_adminimize_save" value="<?php _e('Update Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="config_edit_post"><?php _e('Write options - Post', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h3>
				<div class="inside">
					<br class="clear" />

					<table summary="config_edit_post" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Write options - Post', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<?php
									foreach ($user_roles_names as $role_name) { ?>
										<th><?php _e('Deactivate for', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br/>' . $role_name; ?></th>
								<?php } ?>
							</tr>
						</thead>

						<tbody>
						<?php
							$x = 0;
							$class = '';
							foreach ($metaboxes as $index => $metabox) {
								if ($metabox != '') {
									$class = ( ' class="alternate"' == $class ) ? '' : ' class="alternate"';
									$checked_user_role_ = array();
									foreach ($user_roles as $role) {
										$checked_user_role_[$role]  = ( isset($disabled_metaboxes_post_[$role]) && in_array($metabox, $disabled_metaboxes_post_[$role]) ) ? ' checked="checked"' : '';
									}
									echo '<tr' . $class . '>' . "\n";
									echo '<td>' . $metaboxes_names[$index] . ' <span style="color:#ccc; font-weight: 400;">(' . $metabox . ')</span> </td>' . "\n";
									foreach ($user_roles as $role) {
										echo '<td class="num"><input id="check_post'. $role.$x .'" type="checkbox"' . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_metaboxes_post_'. $role .'_items[]" value="' . $metabox . '" /></td>' . "\n";
									}
									echo '</tr>' . "\n";
									$x++;
								}
							}
						?>
						</tbody>
					</table>
					
					<?php
					//your own post options
					?>
					<br style="margin-top: 10px;" />
					<table summary="config_own_post" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Your own post options', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br />'; _e('ID or class', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<th><?php echo '<br />'; _e('Option', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
							</tr>
						</thead>

						<tbody>
							<tr valign="top">
								<td colspan="2"><?php _e('It is possible to add your own IDs or classes from elements and tags. You can find IDs and classes with the FireBug Add-on for Firefox. Assign a value and the associate name per line.', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
							</tr>
							<tr valign="top">
								<td>
									<textarea name="_mw_adminimize_own_post_options" cols="60" rows="3" id="_mw_adminimize_own_post_options" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_post_options'); ?></textarea>
									<br />
									<?php _e('Possible nomination for ID or class. Separate multiple nomination through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
								<td>
									<textarea class="code" name="_mw_adminimize_own_post_values" cols="60" rows="3" id="_mw_adminimize_own_post_values" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_post_values'); ?></textarea>
									<br />
									<?php _e('Possible IDs or classes. Separate multiple values through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p id="submitbutton">
						<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_insert" />
						<input class="button button-primary" type="submit" name="_mw_adminimize_save" value="<?php _e('Update Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="config_edit_page"><?php _e('Write options - Page', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h3>
				<div class="inside">
					<br class="clear" />

					<table summary="config_edit_page" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Write options - Page', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<?php
									foreach ($user_roles_names as $role_name) { ?>
										<th><?php _e('Deactivate for', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br />' . $role_name; ?></th>
								<?php } ?>
							</tr>
						</thead>

						<tbody>
						<?php
							$x = 0;
							$class = '';
							foreach ($metaboxes_page as $index => $metabox) {
								if ($metabox != '') {
									$class = ( ' class="alternate"' == $class ) ? '' : ' class="alternate"';
									$checked_user_role_ = array();
									foreach ($user_roles as $role) {
										$checked_user_role_[$role]  = ( isset($disabled_metaboxes_page_[$role]) && in_array($metabox, $disabled_metaboxes_page_[$role]) ) ? ' checked="checked"' : '';
									}
									echo '<tr' . $class . '>' . "\n";
									echo '<td>' . $metaboxes_names_page[$index] . ' <span style="color:#ccc; font-weight: 400;">(' . $metabox . ')</span> </td>' . "\n";
									foreach ($user_roles as $role) {
										echo '<td class="num"><input id="check_page'. $role.$x .'" type="checkbox"' . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_metaboxes_page_'. $role .'_items[]" value="' . $metabox . '" /></td>' . "\n";
									}
									echo '</tr>' . "\n";
									$x++;
								}
							}
						?>
						</tbody>
					</table>
					
					<?php
					//ypur own page options
					?>
					<br style="margin-top: 10px;" />
					<table summary="config_own_page" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Your own page options', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br />'; _e('ID or class', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<th><?php echo '<br />'; _e('Option', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
							</tr>
						</thead>

						<tbody>
							<tr valign="top">
								<td colspan="2"><?php _e('It is possible to add your own IDs or classes from elements and tags. You can find IDs and classes with the FireBug Add-on for Firefox. Assign a value and the associate name per line.', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
							</tr>
							<tr valign="top">
								<td>
									<textarea name="_mw_adminimize_own_page_options" cols="60" rows="3" id="_mw_adminimize_own_page_options" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_page_options'); ?></textarea>
									<br />
									<?php _e('Possible nomination for ID or class. Separate multiple nomination through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
								<td>
									<textarea class="code" name="_mw_adminimize_own_page_values" cols="60" rows="3" id="_mw_adminimize_own_page_values" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_page_values'); ?></textarea>
									<br />
									<?php _e('Possible IDs or classes. Separate multiple values through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p id="submitbutton">
						<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_insert" />
						<input class="button button-primary" type="submit" name="_mw_adminimize_save" value="<?php _e('Update Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="links_options"><?php _e('Links options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h3>
				<div class="inside">
					<br class="clear" />

					<table summary="config_edit_links" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Option', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<?php
									foreach ($user_roles_names as $role_name) { ?>
										<th><?php _e('Deactivate for', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br/>' . $role_name; ?></th>
								<?php } ?>
							</tr>
						</thead>

						<tbody>
						<?php
							foreach ($user_roles as $role) {
								$disabled_link_option_[$role]  = _mw_adminimize_getOptionValue('mw_adminimize_disabled_link_option_'. $role .'_items');
							}
								
							$link_options = array(
																			'#namediv',
																			'#addressdiv',
																			'#descriptiondiv',
																			'#linkcategorydiv',
																			'#linktargetdiv',
																			'#linkxfndiv',
																			'#linkadvanceddiv',
																			'#misc-publishing-actions'
																			);
							
							$link_options_names = array(
																			__('Name'),
																			__('Web Address'),
																			__('Description'),
																			__('Categories'),
																			__('Target'),
																			__('Link Relationship (XFN)'),
																			__('Advanced'),
																			__('Publish Actions', FB_ADMINIMIZE_TEXTDOMAIN)
																			);
							
							$_mw_adminimize_own_link_values  = _mw_adminimize_getOptionValue('_mw_adminimize_own_link_values');
							$_mw_adminimize_own_link_values = preg_split( "/\r\n/", $_mw_adminimize_own_link_values );
							foreach ( (array) $_mw_adminimize_own_link_values as $key => $_mw_adminimize_own_link_value ) {
								$_mw_adminimize_own_link_value = trim($_mw_adminimize_own_link_value);
								array_push($link_options, $_mw_adminimize_own_link_value);
							}
							
							$_mw_adminimize_own_link_options = _mw_adminimize_getOptionValue('_mw_adminimize_own_link_options');
							$_mw_adminimize_own_link_options = preg_split( "/\r\n/", $_mw_adminimize_own_link_options );
							foreach ( (array) $_mw_adminimize_own_link_options as $key => $_mw_adminimize_own_link_option ) {
								$_mw_adminimize_own_link_option = trim($_mw_adminimize_own_link_option);
								array_push($link_options_names, $_mw_adminimize_own_link_option);
							}
							
							$x = 0;
							foreach ($link_options as $index => $link_option) {
								if ( $link_option != '') {
									$checked_user_role_ = array();
									foreach ($user_roles as $role) {
										$checked_user_role_[$role]  = ( isset($disabled_link_option_[$role]) && in_array($link_option, $disabled_link_option_[$role]) ) ? ' checked="checked"' : '';
									}
									echo '<tr>' . "\n";
									echo '<td>' . $link_options_names[$index] . ' <span style="color:#ccc; font-weight: 400;">(' . $link_option . ')</span> </td>' . "\n";
									foreach ($user_roles as $role) {
										echo '<td class="num"><input id="check_post'. $role . $x .'" type="checkbox"' . $checked_user_role_[$role] . ' name="mw_adminimize_disabled_link_option_'. $role .'_items[]" value="' . $link_option . '" /></td>' . "\n";
									}
									echo '</tr>' . "\n";
									$x++;
								}
							}
						?>
						</tbody>
					</table>
					
					<?php
					//your own global options
					?>
					<br style="margin-top: 10px;" />
					<table summary="config_edit_post" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Your own options', FB_ADMINIMIZE_TEXTDOMAIN ); echo '<br />'; _e('ID or class', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
								<th><?php echo '<br />'; _e('Option', FB_ADMINIMIZE_TEXTDOMAIN ); ?></th>
							</tr>
						</thead>

						<tbody>
							<tr valign="top">
								<td colspan="2"><?php _e('It is possible to add your own IDs or classes from elements and tags. You can find IDs and classes with the FireBug Add-on for Firefox. Assign a value and the associate name per line.', FB_ADMINIMIZE_TEXTDOMAIN ); ?></td>
							</tr>
							<tr valign="top">
								<td>
									<textarea name="_mw_adminimize_own_link_options" cols="60" rows="3" id="_mw_adminimize_own_link_options" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_link_options'); ?></textarea>
									<br />
									<?php _e('Possible nomination for ID or class. Separate multiple nomination through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
								<td>
									<textarea class="code" name="_mw_adminimize_own_link_values" cols="60" rows="3" id="_mw_adminimize_own_link_values" style="width: 95%;" ><?php echo _mw_adminimize_getOptionValue('_mw_adminimize_own_link_values'); ?></textarea>
									<br />
									<?php _e('Possible IDs or classes. Separate multiple values through a carriage return.', FB_ADMINIMIZE_TEXTDOMAIN ); ?>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p id="submitbutton">
						<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_insert" />
						<input class="button button-primary" type="submit" name="_mw_adminimize_save" value="<?php _e('Update Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
				</form>
				<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>
				
				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="set_theme"><?php _e('Set Theme', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
				<div class="inside">
					<br class="clear" />
					
					<?php if ( !($_POST['_mw_adminimize_action'] == '_mw_adminimize_load_theme') ) { ?>
					<form name="set_theme" method="post" id="_mw_adminimize_set_theme" action="?page=<?php echo $_GET['page'];?>" >
							<?php wp_nonce_field('mw_adminimize_nonce'); ?>
							<p><?php _e('For better peformance with many users on your blog; load only userlist, when you will change the theme options for users.', FB_ADMINIMIZE_TEXTDOMAIN ); ?></p>
							<p id="submitbutton">
								<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_load_theme" />
								<input type="submit" name="_mw_adminimize_load" value="<?php _e('Load User Data', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" class="button button-primary" />
							</p>
					</form>
					<?php }
					if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_load_theme') ) { ?>
						<form name="set_theme" method="post" id="_mw_adminimize_set_theme" action="?page=<?php echo $_GET['page'];?>" >
							<?php wp_nonce_field('mw_adminimize_nonce'); ?>
							<table class="widefat">
								<thead>
									<tr class="thead">
										<th>&nbsp;</th>
										<th class="num"><?php _e('User-ID') ?></th>
										<th><?php _e('Username') ?></th>
										<th><?php _e('Display name publicly as') ?></th>
										<th><?php _e('Admin-Color Scheme') ?></th>
										<th><?php _e('User Level') ?></th>
										<th><?php _e('Role') ?></th>
									</tr>
								</thead>
								<tbody id="users" class="list:user user-list">
									<?php
									$wp_user_search = $wpdb->get_results("SELECT ID, user_login, display_name FROM $wpdb->users ORDER BY ID");
	
									$style = '';
									foreach ( $wp_user_search as $userid ) {
										$user_id       = (int) $userid->ID;
										$user_login    = stripslashes($userid->user_login);
										$display_name  = stripslashes($userid->display_name);
										$current_color = get_user_option('admin_color', $user_id);
										$user_level    = (int) get_user_option($table_prefix . 'user_level', $user_id);
										$user_object   = new WP_User($user_id);
										$roles         = $user_object->roles;
										$role          = array_shift($roles);
										if ( function_exists('translate_user_role') )
											$role_name   = translate_user_role( $wp_roles->role_names[$role] );
										elseif ( function_exists('before_last_bar') )
											$role_name   = before_last_bar( $wp_roles->role_names[$role], 'User role' );
										else
											$role_name   = strrpos( $wp_roles->role_names[$role], '|' );
										
										$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
										$return  = '';
										$return .= '<tr>' . "\n";
										$return .= "\t" . '<td><input type="checkbox" name="mw_adminimize_theme_items[]" value="' . $user_id . '" /></td>' . "\n";
										$return .= "\t" . '<td class="num">'. $user_id .'</td>' . "\n";
										$return .= "\t" . '<td>'. $user_login .'</td>' . "\n";
										$return .= "\t" . '<td>'. $display_name .'</td>' . "\n";
										$return .= "\t" . '<td>'. $current_color . '</td>' . "\n";
										$return .= "\t" . '<td class="num">'. $user_level . '</td>' . "\n";
										$return .= "\t" . '<td>'. $role_name . '</td>' . "\n";
										$return .= '</tr>' . "\n";
	
										echo $return;
									}
									?>
										<tr valign="top">
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>
												<select name="_mw_adminimize_set_theme">
													<?php foreach ( $_wp_admin_css_colors as $color => $color_info ): ?>
														<option value="<?php echo $color; ?>"><?php echo $color_info->name . ' (' . $color . ')' ?></option>
													<?php endforeach; ?>
													</select>
											</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
										</tr>
								</tbody>
							</table>
							<p id="submitbutton">
								<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_set_theme" />
								<input type="hidden" name="_mw_adminimize_load" value="_mw_adminimize_load_theme" />
								<input type="submit" name="_mw_adminimize_save" value="<?php _e('Set Theme', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" class="button button-primary" />
							</p>
						</form>
					<?php } ?>
					
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>
				</div>
			</div>
		</div>
		
		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="import"><?php _e('Export/Import Options', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
				<div class="inside">
					<br class="clear" />
					
					<h4><?php _e('Export', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h4>
					<form name="export_options" method="get" action="">
						<p><?php _e('You can save a .seq file with your options.', FB_ADMINIMIZE_TEXTDOMAIN ) ?></p>
						<p id="submitbutton">
							<input type="hidden" name="_mw_adminimize_export" value="true" />
							<input type="submit" name="_mw_adminimize_save" value="<?php _e('Export &raquo;', FB_ADMINIMIZE_TEXTDOMAIN ) ?>" class="button" />
						</p>
					</form>
					
					<h4><?php _e('Import', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h4>
					<form name="import_options" enctype="multipart/form-data" method="post" action="?page=<?php echo $_GET['page'];?>">
						<?php wp_nonce_field('mw_adminimize_nonce'); ?> 
						<p><?php _e('Choose a Adminimize (<em>.seq</em>) file to upload, then click <em>Upload file and import</em>.', FB_ADMINIMIZE_TEXTDOMAIN ) ?></p>
						<p>
							<label for="datei_id"><?php _e('Choose a file from your computer', FB_ADMINIMIZE_TEXTDOMAIN ) ?>: </label>
							<input name="datei" id="datei_id" type="file" />
						</p>
						<p id="submitbutton">
							<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_import" />
							<input type="submit" name="_mw_adminimize_save" value="<?php _e('Upload file and import &raquo;', FB_ADMINIMIZE_TEXTDOMAIN ) ?>" class="button" />
						</p>
					</form>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>
					
				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="uninstall"><?php _e('Deinstall Options', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
				<div class="inside">

					<p><?php _e('Use this option for clean your database from all entries of this plugin. When you deactivate the plugin, the deinstall of the plugin <strong>clean not</strong> all entries in the database.', FB_ADMINIMIZE_TEXTDOMAIN ); ?></p>
					<form name="deinstall_options" method="post" id="_mw_adminimize_options_deinstall" action="?page=<?php echo $_GET['page'];?>">
						<?php wp_nonce_field('mw_adminimize_nonce'); ?>
						<p id="submitbutton">
							<input type="submit" name="_mw_adminimize_deinstall" value="<?php _e('Delete Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?> &raquo;" class="button-secondary" />
							<input type="checkbox" name="_mw_adminimize_deinstall_yes" value="_mw_adminimize_deinstall" />
							<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_deinstall" />
						</p>
					</form>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div class="postbox" >
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="about"><?php _e('About the plugin', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
				<div class="inside">
				
					<p><?php _e('Further information: Visit the <a href="http://bueltge.de/wordpress-admin-theme-adminimize/674/">plugin homepage</a> for further information or to grab the latest version of this plugin.', FB_ADMINIMIZE_TEXTDOMAIN); ?></p>
					<p>
					<span style="float: left;">
						<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="4578111">
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="<?php _e('PayPal - The safer, easier way to pay online!', FB_ADMINIMIZE_TEXTDOMAIN); ?>">
						<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
					</form>
					</span>
					<?php _e('You want to thank me? Visit my <a href="http://bueltge.de/wunschliste/">wishlist</a> or donate.', FB_ADMINIMIZE_TEXTDOMAIN); ?>
					</p>
					<p>&copy; Copyright 2008 - <?php echo date('Y'); ?> <a href="http://bueltge.de">Frank B&uuml;ltge</a></p>
					<p class="textright" style="color:#ccc"><small><?php echo $wpdb->num_queries; ?>q, <?php timer_stop(1); ?>s</small></p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>
					
				</div>
			</div>
		</div>

		<script type="text/javascript">
		<!--
		<?php if ( version_compare( $wp_version, '2.7alpha', '<' ) ) { ?>
		jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
		<?php } ?>
		jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox .handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function() {
			jQuery(this).addClass("closed");
		});
		//-->
		</script>

	</div>
<?php
}
?>
