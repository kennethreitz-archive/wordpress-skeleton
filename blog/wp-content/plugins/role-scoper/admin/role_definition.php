<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );


function scoper_display_rs_roledefs( $args = array() ) {
	
global $scoper;

echo "<div id='rs-roledefs' style='clear:both;margin:0;' class='rs-options agp_js_hide {$args['bgcolor_class']}'>";

if ( scoper_get_option('display_hints') ) {
	echo '<div class="rs-optionhint">';
	echo '<p style="margin-top:0">';
	_e('These roles are defined by Role Scoper (and possibly other plugins) for your use in designating content-specific access or supplemental blog-wide access.  Although the default capabilities are ideal for most installations, you may modify them at your discretion.', 'scoper');
	echo '</p>';
	
	echo '<p>';
	_e('Since Role Scoper role definitions pertain to a particular object type, available capabilities are defined by the provider of that object type. WordPress core or plugins can add or revise default role definitions based on available capabilities.', 'scoper');
	echo '</p>';
	
	echo '<p>';
	_e('WordPress Role assignments function as a default which may be supplemented or overriden by blog-wide or content-specific assignment of these RS Roles.', 'scoper');  

	echo '</p>';
	echo '</div>';
}

echo "<input type='hidden' name='rs_role_defs' value='1' />";


if ( empty( $args['customize_defaults'] ) ) {
	$rs_role_defs = $scoper->role_defs;
} else {
	global $scoper_role_types;
	$rs_role_defs = new WP_Scoped_Roles($scoper->cap_defs, $scoper_role_types);
	
	//$this->load_role_caps();
	$rs_role_defs->role_caps = apply_filters('define_role_caps_rs', scoper_core_role_caps() );
	
	if ( $user_role_caps = scoper_get_option( 'user_role_caps', -1, true ) )
		$rs_role_defs->add_role_caps( $user_role_caps );
		
	if ( $disabled_role_caps = scoper_get_option( 'disabled_role_caps', -1, true ) )
		$rs_role_defs->remove_role_caps( $disabled_role_caps );
	
	$rs_role_defs->add_member_objects( scoper_core_role_defs() );

	$rs_role_defs = apply_filters('define_roles_rs', $rs_role_defs);
	$rs_role_defs->remove_invalid(); // currently don't allow additional custom-defined post, page or link roles

	// To support merging in of WP role assignments, always note actual WP-defined roles 
	// regardless of which role type we are scoping with.
	$rs_role_defs->populate_with_wp_roles();
	$rs_role_defs->lock(); // prevent inadvertant improper API usage
}


// object_type association of roles needs to be based on default role_caps, otherwise roles with all caps disabled will be excluded from UI
// This also allows the default bolding to be based on custom default settings when role defs are defined per-blog in wp-mu
global $scoper_role_types;
$rs_default_role_defs = new WP_Scoped_Roles($scoper->cap_defs, $scoper_role_types);

$rs_default_role_defs->role_caps = apply_filters('define_role_caps_rs', scoper_core_role_caps() );

if ( IS_MU_RS && ! $args['customize_defaults'] && ! $args['sitewide'] ) {
	if ( $user_role_caps = scoper_get_option( 'user_role_caps', -1, true ) )
		$rs_default_role_defs->add_role_caps( $user_role_caps );
		
	if ( $disabled_role_caps = scoper_get_option( 'disabled_role_caps', -1, true ) )
		$rs_default_role_defs->remove_role_caps( $disabled_role_caps );
}

$rs_default_role_defs->add_member_objects( scoper_core_role_defs() );

$rs_default_role_defs = apply_filters('define_roles_rs', $rs_default_role_defs);
$rs_default_role_defs->remove_invalid();



foreach ( $scoper->data_sources->get_all() as $src_name => $src) {
	
	$include_taxonomy_otypes = true;

	foreach ( $src->object_types as $object_type => $otype ) {
		$otype_roles = array();
		$otype_display_names = array();
		
		if ( $obj_roles = $rs_default_role_defs->get_matching( 'rs', $src_name, $object_type ) ) {
			$otype_roles[$object_type] = $obj_roles;
			$otype_display_names[$object_type] = $otype->display_name;
		}
		
		if ( $include_taxonomy_otypes ) {
			if ( scoper_get_otype_option('use_term_roles', $src_name, $object_type) ) {
				foreach ( $src->uses_taxonomies as $taxonomy) {
					$tx_display_name = $scoper->taxonomies->member_property($taxonomy, 'display_name');
				
					if ( $tx_roles = $rs_role_defs->get_matching( 'rs', $src_name, $taxonomy ) ) {
						$otype_roles[$taxonomy] = $tx_roles;
						$otype_display_names[$taxonomy] = $tx_display_name;
					}
				}	
				$include_taxonomy_otypes = false;
			}	
		}
		
		if ( ! $otype_roles )
			continue;

		foreach ( $otype_roles as $object_type => $roles ) {
			//display each role which has capabilities for this object type
			echo '<br />';
			echo '<h3>' . sprintf( __('%s Roles'), $otype_display_names[$object_type] ) . '</h3>';
?>
<table class='widefat rs-backwhite'>
<thead>
<tr class="thead">
	<th width="15%"><?php echo __awp('Role') ?></th>
	<th><?php _e('Capabilities (defaults are bolded)', 'scoper');?></th>
</tr>
</thead>
<tbody>
<?php		
			$wp_role_sync = array( 
				'rs_post_contributor' 	=> 'contributor',
				'rs_post_revisor' 		=> 'revisor',
				'rs_post_author' 		=> 'author',
				'rs_post_editor' 		=> 'editor',
				'rs_page_editor'		=> 'editor'
			);
			
			if ( defined( 'RVY_VERSION' ) )
				$wp_role_sync['rs_page_revisor'] = 'revisor';
			
			global $wp_roles;

			foreach ( $roles as $rs_role_handle => $role_def ) {
				$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';

				echo "\n\t"
					. "<tr$style><td><strong>" . $rs_role_defs->get_display_name($rs_role_handle) . '</strong>';
					
				if ( isset( $wp_role_sync[$rs_role_handle] ) ) {
					if ( isset( $wp_roles->role_objects[ $wp_role_sync[$rs_role_handle] ] ) ) {
						$wp_role_handle = "wp_" . $wp_role_sync[$rs_role_handle];
						$wp_display_name = $wp_roles->role_names[ $wp_role_sync[$rs_role_handle] ];
						
						$contained_roles = $rs_role_defs->get_contained_roles( $wp_role_handle );
	
						if ( ! isset( $contained_roles[$rs_role_handle] ) ) {
							echo( '<br /><br /><span class="rs-warning">' );
							printf( __( 'Warning: Since the WP %1$s role def lacks some caps selected here, it will be treated as a lesser role if Restrictions are applied.', 'scoper' ), $wp_display_name );
							echo( '</span>' );
							$missing_caps = true;
						} else
							$missing_caps = false;
						
						// only display "sync WP role" checkbox if the WP role has missing caps or extra caps
						$otype_caps = $scoper->cap_defs->get_matching( $src_name, $object_type, '', STATUS_ANY_RS );
						$wp_defined_caps = array_intersect_key( $wp_roles->role_objects[ $wp_role_sync[$rs_role_handle] ]->capabilities, $otype_caps );
						$wp_extra_caps = array_diff_key( $wp_defined_caps, $rs_role_defs->role_caps[$rs_role_handle] );
							
						/*
						if ( $wp_extra_caps )
							$sync_caption = sprintf( _ x( 'sync WP %1$s <br />to these selections (currently includes %2$s)', 'role name', 'scoper' ), $wp_display_name, implode( ", ", array_keys($wp_extra_caps) ) );
						else
							$sync_caption = sprintf( _ x( 'sync WP %s <br />to these selections', 'role name', 'scoper' ), $wp_display_name);
						*/
						
						if ( $wp_extra_caps )
							$sync_caption = sprintf( __( 'sync WP %1$s <br />to these selections (currently includes %2$s)', 'scoper' ), $wp_display_name, implode( ", ", array_keys($wp_extra_caps) ) );
						else
							$sync_caption = sprintf( __( 'sync WP %s <br />to these selections', 'scoper' ), $wp_display_name);
								
						echo '<br /><br />' ;
						$title = __( 'note: only the capabilities listed here will be affected', 'scoper' );
						echo "<input type='checkbox' name='sync_wp_roles[]' id='sync_wp_role_{$rs_role_handle}' value='{$rs_role_handle}:{$wp_role_handle}' title='$title' />"
						. "<label for='sync_wp_role_{$rs_role_handle}' title='$title'>" . $sync_caption  . '</label>';
					}
				}	
					
				echo "</td><td><ul class='rs-cap_list'>";

				$active_cap_names = array_keys($rs_role_defs->role_caps[$rs_role_handle]);

				if ( ! empty($role_def->anon_user_blogrole) || ! empty($role_def->no_custom_caps) ) {
					$disabled_role = 'disabled="disabled"';
					$available_cap_names = $active_cap_names;
				} else {
					$disabled_role = '';
					$available_caps = $rs_default_role_defs->cap_defs->get_matching($src_name, $object_type, '', STATUS_ANY_RS);
					$available_cap_names = array_keys($available_caps);
					sort($available_cap_names);
				}

				foreach($available_cap_names as $cap_name) {
					$checked = ( in_array($cap_name, $active_cap_names) ) ? 'checked="checked"' : '';
					$is_default = ! empty($rs_default_role_defs->role_caps[$rs_role_handle][$cap_name]);
					$disabled_cap = $disabled_role || ( $is_default && ! empty($available_caps[$cap_name]->no_custom_remove) ) || ( ! $is_default && ! empty($available_caps[$cap_name]->no_custom_add) );
					$disabled = ( $disabled_cap ) ? 'disabled="disabled"' : '';

					$style = ( $is_default ) ? "style='font-weight: bold'" : '';

					$cap_safename = str_replace( ' ', '_', $cap_name );
					
					echo "<li><input type='checkbox' name='{$rs_role_handle}_caps[]' id='{$rs_role_handle}_{$cap_safename}' value='$cap_name' $checked $disabled />"
						. "<label for='{$rs_role_handle}_{$cap_safename}' $style>" . str_replace( ' ', '&nbsp;', ucwords( str_replace('_', ' ', $cap_name) ) ) . '</label></li>';
				}

				echo '</ul></td></tr>';
			}

			echo '</tbody></table>';
			echo '<br /><br />';
		} // foreach otype_role (distinguish object roles from term roles)
	} // end foreach object_type
	
} // end foreach data source

echo '<span class="alignright">';
echo '<label for="rs_role_resync"><input name="rs_role_resync" type="checkbox" id="rs_role_resync" value="1" />';
echo '&nbsp;';
_e ( 'Re-sync with WordPress roles on next Update', 'scoper' );
echo '</label></span>';
echo '<br />'

?>
</div>

<?php
} // end function scoper_display_rs_roledefs
?>