<?php 
/*
Plugin Name: Typekit
Plugin URI: http://lucksy.com/sandbox/typekit_plugin_for_wordpress/
Description: Plugin for add Typekit font to your wordpress web site.
Author: Amila Sampath
Version: 1.2.1
Author URI: http://lucksy.com/
*/

function wptkf_install() {
	$wptkf_activate_settings = array('wptkf_activate_settings' => array(
												array(  
														'condition'  => 'Is all',
														'enabled' => 'true',
													)
												)
						);

	$wptkf_activate_settings['wptkf_activate_settings'][1]		=	array(  
														'condition'  => 'Is front page',
														'enabled' => 'false',
													);


	$wptkf_activate_settings['wptkf_activate_settings'][2]		=	array(  
														'condition'  => 'Is home',
														'enabled' => 'false',
													);
													
	$wptkf_activate_settings['wptkf_activate_settings'][3]		=	array(  
														'condition'  => 'Is page',
														'enabled' => 'false',
													);
	
	$wptkf_activate_settings['wptkf_activate_settings'][4]		=	array(  
														'condition'  => 'Is single',
														'enabled' => 'false',
													);

	$wptkf_activate_settings['wptkf_activate_settings'][5]		=	array(  
														'condition'  => 'Is archive',
														'enabled' => 'false',
													);

	$wptkf_activate_settings['wptkf_activate_settings'][6]		=	array(  
														'condition'  => 'Is 404',
														'enabled' => 'false',
													);


	add_option('wptkf_activate_settings', $wptkf_activate_settings);
	add_option('wptkf_browser_support_settings', 'false');

}

function wptkf_activation() {
	$wptkf_activate_settings = array('wptkf_activate_settings' => array(
												array(  
														'condition'  => 'Is all',
														'enabled' => 'true',
													)
												)
						);

	$wptkf_activate_settings['wptkf_activate_settings'][1]		=	array(  
														'condition'  => 'Is front page',
														'enabled' => 'false',
													);

	$wptkf_activate_settings['wptkf_activate_settings'][2]		=	array(  
														'condition'  => 'Is home',
														'enabled' => 'false',
													);
													
	$wptkf_activate_settings['wptkf_activate_settings'][3]		=	array(  
														'condition'  => 'Is page',
														'enabled' => 'false',
													);
	
	$wptkf_activate_settings['wptkf_activate_settings'][4]		=	array(  
														'condition'  => 'Is single',
														'enabled' => 'false',
													);

	$wptkf_activate_settings['wptkf_activate_settings'][5]		=	array(  
														'condition'  => 'Is archive',
														'enabled' => 'false',
													);

	$wptkf_activate_settings['wptkf_activate_settings'][6]		=	array(  
														'condition'  => 'Is 404',
														'enabled' => 'false',
													);

	update_option('wptkf_activate_settings', $wptkf_activate_settings);
	update_option('wptkf_browser_support_settings', 'false');

}


function wptkf_options_page() {
	include('wptkf_admin.php');
}

function wptkf_admin() {
    add_options_page("Typekit Config", "Typekit Config", 1, "Typekit Config", "wptkf_options_page");
}

function print_typekit() {
	$head = "\n<!-- Typekit JS code -->\n";
	$output = get_option('wptkf_embed_code');
	$foot = "\n<!-- Typekit JS code end -->\n";
	if ( '' != $output )
		echo $head . $output . $foot;
}    

function wptkf_active_typekit() {

	$wptkf_activate_settings = get_option('wptkf_activate_settings');
	$activate_settings_checked = '';

	
	for ($q = 0; $q < count($wptkf_activate_settings['wptkf_activate_settings']); $q++) {
		$activate_settings_array = $wptkf_activate_settings['wptkf_activate_settings'][$q];
		if($activate_settings_array['enabled'] == 'true') { 
			if(($activate_settings_array['condition'] == 'Is all')) { 
				$activate_settings_checked = '1';
			}

			if(($activate_settings_array['condition'] == 'Is front page') && is_front_page() ) { 
				$activate_settings_checked = '1';
			}

			if(($activate_settings_array['condition'] == 'Is home') && is_home() ) { 
				$activate_settings_checked = '1';
			}

			if(($activate_settings_array['condition'] == 'Is page') && is_page() ) { 
				$activate_settings_checked = '1';
			}

			if(($activate_settings_array['condition'] == 'Is single') && is_single() ) { 
				$activate_settings_checked = '1';
			}

			if(($activate_settings_array['condition'] == 'Is archive') && is_archive() ) { 
				$activate_settings_checked = '1';
			}

			if(($activate_settings_array['condition'] == 'Is 404 page') && is_404() ) { 
				$activate_settings_checked = '1';
			}
		}
	}

	if($activate_settings_checked == '1' ) {
		print_typekit();	
	}
}

function wptkf_admin_footer() {
	if( basename($_SERVER['REQUEST_URI']) == 'typekit.php') {
		$plugin_data = get_plugin_data( __FILE__ );
		printf('%1$s plugin | ' . __('Version') . ' %2$s | ' . __('Author') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}
}
function is_browser_support() {

	require_once(dirname (__FILE__)."/xbd/xbd.php");

	$wptkf_browser_support_settings = get_option('wptkf_browser_support_settings');

	if ($wptkf_browser_support_settings == 'false') {
			return 1;	
	} else {
		$browser_agent = 0;
	
		if (_browser('chrome', '>= 3.1')) {
				$browser_agent = 1;
		} elseif (_browser('safari', '>= 3.1')) {
				$browser_agent = 1;
		} elseif (_browser('firefox', '>= 3.5')) {
				$browser_agent = 1;
		} elseif (_browser('msie', '>= 4.0')) {
				$browser_agent = 1;
		} elseif (_browser('opera', '>= 10')) {
				$browser_agent = 1;
		} elseif (_browser('netscape', '>= 4.0')) {
				$browser_agent = 1;
		}
	
		if($browser_agent == '1' ) {
			return 1;	
		}
	}
}


if ( function_exists('register_activation_hook') )
	register_activation_hook(__FILE__, 'wptkf_install');

	
	if (version_compare($wp_version, '2.8', '>=')) add_filter('upgrader_pre_install', 'wptkf_activation', 10, 2);

if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'wptkf_uninstall');

if ( is_admin() ) {
	add_action('in_admin_footer', 'wptkf_admin_footer'); 
	add_action('admin_menu', 'wptkf_admin');
} else {
	if ( is_browser_support() ) {
		add_action('wp_head', 'wptkf_active_typekit');
	}
}
?>
