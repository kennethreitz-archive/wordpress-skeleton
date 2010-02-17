<?php

function jkf_tml_is_module_active($module) {
    global $theme_my_login;
	$current = apply_filters('tml_active_modules', $theme_my_login->options['active_modules']);
    return in_array($module, $current);
}

function jkf_tml_activate_module($module) {
	global $theme_my_login;
	$module = plugin_basename(trim($module));

	$valid = jkf_tml_validate_module($module);
	if ( is_wp_error($valid) )
		return $valid;
		
	if ( !in_array($module, $theme_my_login->options['active_modules']) ) {
		//ob_start();
		@include (TML_MODULE_DIR . '/' . $module);
		$theme_my_login->options['active_modules'][] = $module;
		sort($theme_my_login->options['active_modules']);
		do_action('tml_activate_module', trim($module));
		// We will not use this since our function modifies the global plugin object instead of saving to the DB
		//update_option('theme_my_login', $current);
		do_action('activate_' . trim($module));
		do_action('tml_activated_module', trim($module));
		//ob_end_clean();
	}

	return null;
}

function jkf_tml_deactivate_modules($modules, $silent= false) {
	global $theme_my_login;

	if ( ! is_array($modules) )
		$modules = array($modules);

	foreach ( $modules as $module ) {
		$module = plugin_basename($module);
		if( ! jkf_tml_is_module_active($module) )
			continue;
		if ( ! $silent )
			do_action('tml_deactivate_module', trim($module));

		$key = array_search( $module, (array) $theme_my_login->options['active_modules'] );

		if ( false !== $key )
			array_splice( $theme_my_login->options['active_modules'], $key, 1 );

		if ( ! $silent ) {
			do_action( 'deactivate_' . trim( $module ) );
			do_action( 'tml_deactivated_module', trim( $module ) );
		}
	}
	
	// We will not use this since the function modifies our global plugin object instead of saving to the DB
	//update_option('theme_my_login', $current);
}

function jkf_tml_activate_modules($modules) {
	if ( !is_array($modules) )
		$modules = array($modules);

	$errors = array();
	foreach ( (array) $modules as $module ) {
		$result = jkf_tml_activate_module($module);
		if ( is_wp_error($result) )
			$errors[$module] = $result;
	}

	if ( !empty($errors) )
		return new WP_Error('plugins_invalid', __('One of the plugins is invalid.'), $errors);

	return true;
}

function jkf_tml_validate_module($module) {
	if ( validate_file($module) )
		return new WP_Error('plugin_invalid', __('Invalid plugin path.'));
	if ( ! file_exists(TML_MODULE_DIR . '/' . $module) )
		return new WP_Error('plugin_not_found', __('Plugin file does not exist.'));

	$installed_modules = get_plugins('/theme-my-login/modules');
	if ( ! isset($installed_modules[$module]) )
		return new WP_Error('no_plugin_header', __('The plugin does not have a valid header.'));
	return 0;
}

function jkf_tml_add_menu_page($menu_title, $file, $function = '', $position = NULL) {
    global $jkf_tml_admin_menu;

    $file = plugin_basename($file);

    $hookname = get_plugin_page_hookname($file, '');
	$hookname = preg_replace('|[^a-zA-Z0-9_:.]|', '-', $hookname);
    if ( !empty($function) && !empty($hookname) )
        add_action($hookname, $function);

    $new_menu = array($menu_title, $file, $hookname);

    if ( NULL === $position )
        $jkf_tml_admin_menu[] = $new_menu;
    else
        $jkf_tml_admin_menu[$position] = $new_menu;

    return $hookname;
}

function jkf_tml_add_submenu_page($parent, $menu_title, $file, $function = '') {
	global $jkf_tml_admin_submenu;
	
	$file = plugin_basename($file);
	$parent = plugin_basename($parent);
	
	$count = is_array($jkf_tml_admin_submenu[$parent]) ? count($jkf_tml_admin_submenu[$parent]) + 1 : 1;
	
	$hookname = get_plugin_page_hookname($parent . '-' . $count, '');
	$hookname = preg_replace('|[^a-zA-Z0-9_:.]|', '-', $hookname);
	if ( !empty($function) && !empty($hookname) )
		add_action($hookname, $function);
	
	$jkf_tml_admin_submenu[$parent][] = array($menu_title, $file, $hookname);
	
	return $hookname;
}

function jkf_tml_load_admin_page($file, $hook = '') {
	global $theme_my_login;
    if ( $hook && has_action($hook) ) {
        do_action("load-$hook");
        do_action($hook);
    } else {
        if ( validate_file($file) )
            return false;

        if ( ! ( file_exists(WP_PLUGIN_DIR . "/$file") && is_file(WP_PLUGIN_DIR . "/$file") ) )
            return false;

        do_action("load-$file");
        include (WP_PLUGIN_DIR . "/$file");
    }
    return true;
}

?>
