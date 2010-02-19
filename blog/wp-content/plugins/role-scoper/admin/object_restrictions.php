<?php
// ------------------- Begin Common Code ------------------------
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

function scoper_admin_object_restrictions($src_name, $object_type) {
global $scoper;

if ( ! ( $src = $scoper->data_sources->get($src_name) ) || ! empty($src->no_object_roles) || ! empty($src->taxonomy_only) || ($src_name == 'group') )
	wp_die(__('Invalid data source', 'scoper'));

$is_administrator = is_administrator_rs($src, 'user');

$role_bases = array();

if ( USER_ROLES_RS && ( $is_administrator || $scoper->admin->user_can_admin_object($src_name, $object_type, 0, true) ) )
	$role_bases []= ROLE_BASIS_USER;
	
if ( GROUP_ROLES_RS && ( $is_administrator || $scoper->admin->user_can_admin_object($src_name, $object_type, 0, true) || current_user_can('manage_groups') ) )
	$role_bases []= ROLE_BASIS_GROUPS;

if ( empty($role_bases) )
	wp_die(__awp('Cheatin&#8217; uh?'));
	
$otype = $scoper->data_sources->member_property($src_name, 'object_types', $object_type);
	
require_once('admin_ui_lib_rs.php');
require_once( 'admin-bulk_rs.php' );
$role_assigner = init_role_assigner();
	
$nonce_id = 'scoper-assign-roles';

$role_codes = ScoperAdminBulk::get_role_codes();

echo '<a name="scoper_top"></a>';


// ==== Process Submission =====
$err = 0;
if ( isset($_POST['rs_submit'] ) ) {
	$err = ScoperAdminBulk::role_submission(OBJECT_SCOPE_RS, ROLE_RESTRICTION_RS, '', $src_name, $role_codes, '', $nonce_id);
	
	if ( scoper_get_option( 'file_filtering' ) )
		scoper_flush_file_rules();
}
?>

<div class="wrap agp-width97">
<?php

$src_otype = ( isset($src->object_types) ) ? "{$src_name}:{$object_type}" : $src_name;
$display_name = $scoper->admin->interpret_src_otype($src_otype, false);
$display_name_plural = $scoper->admin->interpret_src_otype($src_otype, true);
echo '<h2>' . sprintf(__('%s Restrictions', 'scoper'), $display_name)
	. '&nbsp;&nbsp;<span style="font-size: 0.6em; font-style: normal">(<a href="#scoper_notes">' . __('see notes', 'scoper') . '</a>)</span>'
	. '</h2>';

if ( scoper_get_option('display_hints') ) {
	echo '<div class="rs-hint">';
	
	$link_open = "<a href='admin.php?page=rs-$object_type-roles'>";
	
	$tx_names = $scoper->data_sources->member_property($src_name, 'uses_taxonomies');
	if ( $tx_names && (1 == count($tx_names) ) && scoper_get_otype_option('use_term_roles', $src_name, $object_type) ) {
		$tx_display = $scoper->taxonomies->member_property( current($tx_names), 'display_name' );
		printf(__('Reduce access to a specific %1$s by requiring some role(s) to be %2$s%3$s-assigned%4$s. Corresponding WP-assigned Roles and RS-assigned General and %5$s Role assignments are ignored.', 'scoper'), $display_name, $link_open, $display_name, '</a>', $tx_display);
	} elseif ( count($tx_names) > 1 )
		printf(__('Reduce access to a specific %1$s by requiring some role(s) to be %2$s%3$s-assigned%4$s. Corresponding WP-assigned Roles and RS-assigned General and Section Role assignments are ignored.', 'scoper'), $display_name, $link_open, $display_name, '</a>');
	else
		printf(__('Reduce access to a specific %1$s by requiring some role(s) to be %2$s%3$s-assigned%4$s. Corresponding WP-assigned Roles and RS-assigned General Role assignments are ignored.', 'scoper'), $display_name, $link_open, $display_name, '</a>');

	echo '</div>';
}

$ignore_hierarchy = ! empty($otype->ignore_object_hierarchy);
?>

<form action="" method="post" name="role_assign" id="role_assign">
<?php
wp_nonce_field( $nonce_id );

// ============ Users / Groups and Assignment Mode Selection Display ================
if ( empty($src->cols->parent) || $ignore_hierarchy )
	$assignment_modes = array( 
		ASSIGN_FOR_ENTITY_RS => sprintf(__('for selected %s', 'scoper'), $display_name_plural)
	);
else
	$assignment_modes = array(
		ASSIGN_FOR_ENTITY_RS => sprintf(__('for selected %s', 'scoper'), $display_name_plural), 
		ASSIGN_FOR_CHILDREN_RS => sprintf(__('for sub-%s of selected', 'scoper'), $display_name_plural), 
		ASSIGN_FOR_BOTH_RS => sprintf(__('for selected and sub-%s', 'scoper'), $display_name_plural)
	);

$max_scopes = array( 'object' => __('Restrict selected roles', 'scoper'), 'blog' => __('Unrestrict selected roles', 'scoper')  );
$args = array( 'max_scopes' => $max_scopes, 'scope' => OBJECT_SCOPE_RS );
ScoperAdminBulk::display_inputs(ROLE_RESTRICTION_RS, $assignment_modes, $args);

$role_display = array();
$editable_roles = array();
foreach ( $scoper->role_defs->get_all_keys() as $role_handle ) {
	$role_display[$role_handle] = $scoper->role_defs->get_abbrev( $role_handle, OBJECT_UI_RS );
	if ( $scoper->admin->user_can_admin_role($role_handle, 0, $src_name, $object_type) )
		$editable_roles[0][$role_handle] = true;
}
	
echo '<br />';
$args = array( 'default_hide_empty' => ! empty($otype->admin_default_hide_empty), 'hide_roles' => true, 'scope' => OBJECT_SCOPE_RS, 'src' => $src, 'otype' => $otype );
ScoperAdminBulk::item_tree_jslinks(ROLE_RESTRICTION_RS, $args );

// buffer prev/next caption for display with each obj type
//$prevtext = _ x('prev', 'abbreviated link to previous item', 'scoper');
//$nexttext = _ x('next', 'abbreviated link to next item', 'scoper');
$prevtext = __('prev', 'scoper');
$nexttext = __('next', 'scoper');

$site_url = get_option('siteurl');

$args = array( 'include_child_restrictions' => true, 'return_array' => true, 'role_type' => SCOPER_ROLE_TYPE, 'force_refresh' => true );
$strict_objects = $scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name, $args);

$object_names = array();
$object_status = array();
$listed_objects = array();
$unlisted_objects = array();

$col_id = $src->cols->id;
$col_parent = ( isset($src->cols->parent) && ! $ignore_hierarchy ) ? $src->cols->parent : '';

$object_ids = array();
if ( isset($strict_objects['restrictions']) ) {
	foreach ( array_keys($strict_objects['restrictions']) as $role_handle )
		$object_ids = $object_ids + array_keys($strict_objects['restrictions'][$role_handle]);
		
} elseif ( isset($strict_objects['unrestrictions']) ) {
	foreach ( array_keys($strict_objects['unrestrictions']) as $role_handle )
		$object_ids = $object_ids + array_keys($strict_objects['unrestrictions'][$role_handle]);
}

$object_ids = array_flip( array_unique($object_ids) );

// Get the obj name, parent associated with each role (also sets $object_names, $unlisted objects)
$listed_objects = ScoperAdminBulk::get_objects_info($object_ids, $object_names, $object_status, $unlisted_objects, $src, $otype, $ignore_hierarchy);


if ( $col_parent ) {
	if ( $listed_objects ) {
		if ( $unlisted_objects ) // query for any parent objects which don't have their own role assignments
			$listed_objects = ScoperAdminUI::add_missing_parents($listed_objects, $unlisted_objects, $col_parent);

		// convert keys from object ID to title+ID so we can alpha sort them
		$listed_objects_alpha = array();
		foreach ( array_keys($listed_objects) as $id )
			$listed_objects_alpha[ $listed_objects[$id]->{$src->cols->name} . chr(11) . $id ] = $listed_objects[$id];

		uksort($listed_objects_alpha, "strnatcasecmp");

		$listed_objects = ScoperAdminUI::order_by_hierarchy($listed_objects_alpha, $col_id, $col_parent);
	} // endif any listed objects
	
} else { // endif doing object hierarchy
	if ( $listed_objects ) {
		// convert keys from object ID to title+ID so we can alpha sort them
		$listed_objects_alpha = array();
		foreach ( array_keys($listed_objects) as $id )
			$listed_objects_alpha[ $listed_objects[$id]->{$src->cols->name} . chr(11) . $id ] = $listed_objects[$id];

		uksort($listed_objects_alpha, "strnatcasecmp");
		
		// convert to ordinal integer index
		$listed_objects = array_combine( array_keys( array_fill( 0, count($listed_objects_alpha), true ) ), $listed_objects_alpha );
	}
}

if ( ! $is_administrator )
	$cu_admin_results = ScoperAdminBulk::filter_objects_listing(ROLE_RESTRICTION_RS, $strict_objects, $src, $object_type);  // unsets $strict_objects elements as needed
else
	$cu_admin_results = '';	// no need to filter admins

$role_defs_by_otype = array();
$role_defs_by_otype[$object_type] = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_name, $object_type);

$table_captions = ScoperAdminUI::restriction_captions(OBJECT_SCOPE_RS, '', $display_name, $display_name_plural);

$args = array( 
'admin_items' => $cu_admin_results, 	'editable_roles' => $editable_roles,	'default_hide_empty' => ! empty($otype->admin_default_hide_empty),
'ul_class' => 'rs-objlist', 			'object_names' => $object_names,		'object_status' => $object_status,
'table_captions' => $table_captions,	'ie_link_style' => '',					'err' => $err
);

ScoperAdminBulk::item_tree( OBJECT_SCOPE_RS, ROLE_RESTRICTION_RS, $src, $otype, $listed_objects, '', $strict_objects, $role_defs_by_otype, $role_codes, $args);
//ScoperAdminBulk::item_tree( OBJECT_SCOPE_RS, ROLE_ASSIGNMENT_RS, $src, $otype, $all_objects, $object_roles, $strict_objects, $role_defs_by_otype, $role_codes, $args);
echo '<hr /><div style="background-color: white;"></div>';

echo '<div class="rs-objlistkey">';
$args = array( 'display_links' => true, 'display_restriction_key' => true );
ScoperAdminUI::role_owners_key($otype, $args);
echo '</div>';

echo '</form><br /><h4 style="margin-bottom:0.1em"><a name="scoper_notes"></a>' . __("Notes", 'scoper') . ':</h4><ul class="rs-notes">';	

echo '<li>';
printf(__('To edit all roles for any %1$s, click on the %1$s name.', 'scoper'), $otype->display_name);
echo '</li>';

echo '<li>';
printf(__("To edit the %s via its default editor, click on the ID link.", 'scoper'), $otype->display_name);
echo '</li>';

if ( ! $is_administrator ) {
	echo '<li>';
	printf(__('To enhance performance, the role editing checkboxes here may not include some roles which you can only edit due to your own %1$s-specific role. In such cases, click on the editing link to edit roles for the individual %1$s.', 'scoper'), $otype->display_name);
	echo '</li>';
}

echo '</ul>';
echo('<a href="#scoper_top">' . __('top', 'scoper') . '</a>');
?>
</div>
<?php
} // end wrapper function scoper_admin_object_roles
?>