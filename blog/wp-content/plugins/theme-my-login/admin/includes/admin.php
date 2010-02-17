<?php

function jkf_tml_admin_menu() {
	// Create our settings link in the default WP "Settings" menu
    add_options_page(__('Theme My Login', 'theme-my-login'), __('Theme My Login', 'theme-my-login'), 8, 'theme-my-login/admin/options.php');
}

function jkf_tml_admin_init() {
	// Register our settings in the global 'whitelist_settings'
    register_setting('theme_my_login', 'theme_my_login',  'jkf_tml_save_settings');
	
	// Hook into the loading of our dedicated settings page
	add_action('load-theme-my-login/admin/options.php', 'jkf_tml_load_settings_page');
	
	// Create a hook for modules to use
    do_action('tml_admin_init');
}

function jkf_tml_load_settings_page() {
	global $theme_my_login, $user_ID;
	
	// Enqueue neccessary scripts and styles
    wp_enqueue_script('theme-my-login-admin', plugins_url('/theme-my-login/admin/js/theme-my-login-admin.js'));
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_style('theme-my-login-admin', plugins_url('/theme-my-login/admin/css/theme-my-login-admin.css'));

	// Set the correct admin style according to user setting (Only supports default admin schemes)
    $admin_color = get_usermeta($user_ID, 'admin_color');
    if ( 'classic' == $admin_color )
		wp_enqueue_style('theme-my-login-colors-classic', plugins_url('/theme-my-login/admin/css/colors-classic.css'));
    else
        wp_enqueue_style('theme-my-login-colors-fresh', plugins_url('/theme-my-login/admin/css/colors-fresh.css'));
	
	// Handle activation/deactivation of modules
	if ( isset($theme_my_login->options['activate_modules']) || isset($theme_my_login->options['deactivate_modules']) ) {		
		// Set a constant so we know that we're editing the modules in the 'update_option' sanatization function
		define('TML_EDITING_MODULES', true);
		
		// If we have modules to activate
		if ( isset($theme_my_login->options['activate_modules']) ) {
			// Attempt to activate them
			$result = jkf_tml_activate_modules($theme_my_login->options['activate_modules']);
			// Check for WP_Error
			if ( is_wp_error($result) ) {
				// Loop through each module in the WP_Error object
				foreach ( $result->get_error_data('plugins_invalid') as $module => $wp_error ) {
					// Store the module and error message to a temporary array which will be passed to 'admin_notices'
					if ( is_wp_error($wp_error) )
						$theme_my_login->options['module_errors'][$module] = $wp_error->get_error_message();
				}
			}
			// Unset the 'activate_modules' array
			unset($theme_my_login->options['activate_modules']);
		}
	
		// If we have modules to deactivate
		if ( isset($theme_my_login->options['deactivate_modules']) ) {
			// Deactive them
			jkf_tml_deactivate_modules($theme_my_login->options['deactivate_modules']);
			// Unset the 'deactivate_modules' array
			unset($theme_my_login->options['deactivate_modules']);
		}
		
		// Unset the constant
		define('TML_EDITING_MODULES', false);
		// Update the options in the DB
		update_option('theme_my_login', $theme_my_login->options);
		
		// Redirect so that the newly activated modules can be included and newly unactivated modules can not be included
		$redirect = isset($theme_my_login->options['module_errors']) ? admin_url('options-general.php?page=theme-my-login/admin/options.php') : add_query_arg('updated', 'true');
		wp_redirect($redirect);
		exit();
	}
	
	// If we have errors to display, hook into 'admin_notices' to display them
	if ( $theme_my_login->options['module_errors'] )
		add_action('admin_notices', 'jkf_tml_module_error_notice');
}

function jkf_tml_module_error_notice() {
	global $theme_my_login;
	
	// If we have errors to display
	if ( isset($theme_my_login->options['module_errors']) ) {
		// Display them
		echo '<div class="error">';
		foreach ( $theme_my_login->options['module_errors'] as $module => $error ) {
			echo "<p><strong>ERROR: The module \"$module\" could not be activated ($error).</strong></p>";
		}
		echo '</div>';
		// Unset the error array
		unset($theme_my_login->options['module_errors']);
		// Update the options in the DB
		update_option('theme_my_login', $theme_my_login->options);
	}
}

function jkf_tml_save_settings($settings) {
	global $theme_my_login;
	
	if ( defined('TML_EDITING_MODULES') )
		return $settings;
	
	// Assign current settings
	$current = $theme_my_login->options;
	
	// Sanitize new settings
	$settings['page_id'] = absint($settings['page_id']);
	$settings['show_page'] = isset($settings['show_page']) ? 1 : 0;
	$settings['rewrite_links'] = isset($settings['rewrite_links']) ? 1 : 0;
	$settings['enable_css'] = isset($settings['enable_css']) ? 1 : 0;
	$settings['enable_template_tag'] = isset($settings['enable_template_tag']) ? 1 : 0;
	$settings['enable_widget'] = isset($settings['enable_widget']) ? 1 : 0;
	//$settings['active_modules'] = isset($settings['active_modules']) ? (array) $settings['active_modules'] : array();
	$settings['modules'] = isset($settings['modules']) ? (array) $settings['modules'] : array();
	
	// Set modules to be activated
	if ( $activate = array_diff($settings['modules'], (array) $current['active_modules']) )
		$settings['activate_modules'] = $activate;
		
	// Set modules to be deactivated
	if ( $deactivate = array_diff((array) $current['active_modules'], $settings['modules']) )
		$settings['deactivate_modules'] = $deactivate;
		
	// Unset 'modules' as it is only relevent here
	unset($settings['modules']);
	
	// Merge current settings
    $settings = wp_parse_args($settings, $current);
	
	// Allow plugins/modules to add/modify settings
    $settings = apply_filters('tml_save_settings', $settings);
        
    return $settings;
}

function jkf_tml_install() {
    $previous_install = get_option('theme_my_login');
    if ( $previous_install ) {
        if ( version_compare($previous_install['version'], '4.4', '<') ) {
            global $wp_roles;
            if ( $wp_roles->is_role('denied') )
                $wp_roles->remove_role('denied');
        }
    }

    $insert = array(
        'post_title' => 'Login',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_author' => 1,
        'post_content' => '[theme-my-login show_title="0" before_widget="" after_widget="" instance_id="tml-page"]',
        'comment_status' => 'closed',
        'ping_status' => 'closed'
        );

    $login_page = get_page_by_title('Login');
    $page_id = ( $login_page ) ? $login_page->ID : wp_insert_post($insert);

    $options = wp_parse_args($previous_install, jkf_tml_default_settings());
        
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/theme-my-login/theme-my-login.php');
    $options = array_merge(array('version' => $plugin_data['Version'], 'page_id' => $page_id), $options);
    return update_option('theme_my_login', $options);
}

function jkf_tml_uninstall() {
    $options = get_option('theme_my_login');
	
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	// Run module uninstall hooks
	$modules = get_plugins('/theme-my-login/modules');
	foreach ( array_keys($modules) as $module ) {
		$module = plugin_basename(trim($module));

		$valid = jkf_tml_validate_module($module);
		if ( is_wp_error($valid) )
			continue;
			
		@include (TML_MODULE_DIR . '/' . $module);
		do_action('uninstall_' . trim($module));
	}

	// Delete the page
    if ( get_page($options['page_id']) )
        wp_delete_post($options['page_id']);
		
	// Delete options
    delete_option('theme_my_login');
	delete_option('widget_theme-my-login');
}

?>
