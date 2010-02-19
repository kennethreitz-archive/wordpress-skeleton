<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	

function scoper_display_wp_roledefs( $args = array() ) {
	
global $scoper;

echo "<div id='wp-roledefs' style='clear:both;margin:0;' class='rs-options agp_js_hide {$args['bgcolor_class']}'>";

if ( scoper_get_option('display_hints') ) {
	echo '<div class="rs-optionhint">';
	echo '<p style="margin-top:0">';
	_e('Note that only <strong>capabilities configured for filtering by Role Scoper</strong> are listed here.', 'scoper');
	echo ' ';
	_e('These WordPress role definitions may be modified via the Capability Manager or Role Manager plugin.', 'scoper');
	echo '</p>';
	
	if ( 'rs' == SCOPER_ROLE_TYPE ) {
		echo '<p style="margin-top:0">';
		_e('To understand how your WordPress roles relate to type-specific RS Roles, see <a href="#wp_rs_equiv">WP/RS Role Equivalence</a>.', 'scoper');
		echo '</p>';
	}
	
	echo '</div>';
}

	$roles = $scoper->role_defs->get_matching( 'wp', '', '' );
	
	echo '<h3>' . __('WordPress Roles', 'scoper'), '</h3>';
?>
<table class='widefat rs-backwhite'>
<thead>
<tr class="thead">
	<th width="15%"><?php echo __awp('Role') ?></th>
	<th><?php _e('Capabilities', 'scoper');?></th>
</tr>
</thead>
<tbody>
<?php		
	$style = '';

	global $wp_roles;
	
	$wp_role_names = $wp_roles->role_names;
	uasort($wp_role_names, "strnatcasecmp");  // sort by array values, but maintain keys
	
	// order WP roles by display name
	foreach ( array_keys($wp_role_names) as $wp_role_name ) {
		$role_handle = scoper_get_role_handle( $wp_role_name, 'wp' ); 
	
		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
	
		if ( empty($scoper->role_defs->role_caps[$role_handle]) )
			continue;

		$cap_names = array_keys($scoper->role_defs->role_caps[$role_handle]);	
		sort($cap_names);
		$cap_display_names = array();
		foreach($cap_names as $cap_name)
			$cap_display_names[] = ucwords( str_replace('_', ' ', $cap_name) );
		
		$caplist = "<li>" . implode("</li><li>", $cap_display_names) . "</li>";

		echo "\n\t"
			. "<tr$style><td>" . $scoper->role_defs->get_display_name($role_handle) . "</td><td><ul class='rs-cap_list'>$caplist</ul></td></tr>";
	} // end foreach role


	
	echo '</tbody></table>';
	echo '<br /><br />';
	
if ( 'rs' == SCOPER_ROLE_TYPE ) {
	echo '<a name="wp_rs_equiv"></a>';
	echo '<h3>' . __('WP / RS Role Equivalence', 'scoper'), '</h3>';
?>
<table class='widefat rs-backwhite'>
<thead>
<tr class="thead">
	<th width="15%"><?php _e('WP Role', 'scoper') ?></th>
	<th><?php _e('Contained RS Roles', 'scoper');?></th>
</tr>
</thead>
<tbody>
<?php	
	$style = '';

	// order WP roles by display name
	foreach ( array_keys($wp_role_names) as $wp_role_name ) {
		$role_handle = scoper_get_role_handle( $wp_role_name, 'wp' ); 

		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
	
		$display_names = array();
		$contained_roles_handles = $scoper->role_defs->get_contained_roles($role_handle, false, 'rs');
	
		foreach( array_keys($contained_roles_handles) as $contained_role_handle )
			$display_names[] = $scoper->role_defs->get_display_name($contained_role_handle);

		$list = "<li>" . implode("</li><li>", $display_names) . "</li>";

		echo "\n\t"
			. "<tr$style><td>" . $scoper->role_defs->get_display_name($role_handle) . "</td><td><ul class='rs-cap_list'>$list</ul></td></tr>";
	} // end foreach role
		
	echo '</tbody></table>';
	echo '<br /><br />';
} // endif 'rs' == SCOPER_ROLE_TYPE
?>
</div>

<?php
} // end function scoper_display_wp_roledefs
?>