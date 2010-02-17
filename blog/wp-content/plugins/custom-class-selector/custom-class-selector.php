<?php
/*
Plugin Name: Custom Class Selector
Plugin URI: http://wordpress.org/extend/plugins/custom-class-selector/
Description: Allows users to style their post content using custom classes made available by the active theme.
Version: 0.1
Author: Aaron Forgue & Tammy Hart
License: GPLv3 (http://www.fsf.org/licensing/licenses/gpl.html)

Copyright 2009  Aaron Forgue (http://www.aaronforgue.com), Tammy Hart (http://www.tammyhartdesigns.com)

*/


// Add all appropriate filters
add_filter('tiny_mce_before_init', 'ccs_styleselect_init');
add_filter('mce_css', 'ccs_custom_style');
add_filter('mce_buttons', 'ccs_styleselect_button');

/**
  * Establishes the 'ccs_styleselect_init' filter hook that developers can use
  * to define the array of custom style classes that should be included in the
  * styleselect menu.
  *
  * @param array $initArray
  * @return array
  */
function ccs_styleselect_init($initArray = array()) {
	
	// If there are already values defined for theme_advanced_styles, go ahead
	// and add them into our array. Developer can decide the fate of these styles.
	$configuration['custom_styles'] = array();
	if (!empty($initArray['theme_advanced_styles'])) {
		$initStyles = explode(';', $initArray['theme_advanced_styles']);
		foreach ($initStyles as $style) {
			list($displayName, $className) = explode('=', $style);
			$configuration['custom_styles'][$displayName] = $className;
		}
	}
	
	// Hook!
	$configuration = apply_filters('customclassselector_configuration', $configuration);
	
	// There may be several types of values that we get back, handle each
	if (empty($configuration['custom_styles'])) {
		$themeAdvancedStylesString = '';
	} else if (is_string($configuration['custom_styles'])) {
		$themeAdvancedStylesString = $configuration['custom_styles'];
	} else if (is_array($configuration['custom_styles'])) {
		$themeAdvancedStylesString = array();
		
		foreach ($configuration['custom_styles'] as $displayName => $className) {
			$themeAdvancedStylesString[] =  $displayName.'='.$className;
		}
		
		$themeAdvancedStylesString = implode(';', $themeAdvancedStylesString);
	}

	// Update the editor init configuration with our custom styles
	$initArray['theme_advanced_styles'] = $themeAdvancedStylesString;
	
	return $initArray;
}

/**
  * Imports any custom stylesheets into the editor
  *
  * @param string $css
  * @return string
  */
function ccs_custom_style($css) {
	// Hook!
	$configuration = apply_filters('customclassselector_configuration', array('stylesheet_url' => $css));
	
	return $configuration['stylesheet_url'];
}

/**
  * Adds the 'styleselect' control to the top row of editor buttons.
  *
  * @param array $buttons
  * @return array
  */
function ccs_styleselect_button($buttons){
	array_push($buttons, 'separator', 'styleselect');
	return $buttons;
}

?>
