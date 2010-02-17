<?php

function CCSConfiguration($configuration) {

	// Define the URL of the stylesheet that holds the CSS definitions for your custom styles
	$configuration['stylesheet_url'] = get_bloginfo('template_url').'/ccs-sample.css';
	
	// Define which custom styles are included in the style menu and how each is labeled
	// Format: 'Display Name' => 'classname'
	$configuration['custom_styles'] = array(
		'Bold and Blue' => 'boldblue',
		'Italic and Red' => 'italicred'
	);
	
	return $configuration;
}
add_filter('customclassselector_configuration', 'CCSConfiguration');

?>