<?php
// ------------------- Begin Common Code ------------------------
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

function scoper_admin_object_roles($src_name, $object_type) {
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
require_once('role_assignment_lib_rs.php');
$role_assigner = init_role_assigner();
	
$nonce_id = 'scoper-assign-roles';

$agents = ScoperAdminBulk::get_agents($role_bases);
$agent_names = ScoperAdminBulk::agent_names($agents);
$agent_list_prefix = ScoperAdminBulk::agent_list_prefixes();
$agent_caption_plural = ScoperAdminBulk::agent_captions_plural($role_bases);
$role_bases = array_keys($agents);

$role_codes = ScoperAdminBulk::get_role_codes();

echo '<a name="scoper_top"></a>';


// ==== Process Submission =====
$err = 0;
if ( isset($_POST['rs_submit'] ) )
	$err = ScoperAdminBulk::role_submission(OBJECT_SCOPE_RS, ROLE_ASSIGNMENT_RS, $role_bases, $src_name, $role_codes, $agent_caption_plural, $nonce_id);
?>

<div class="wrap agp-width97">
<?php

$src_otype = ( isset($src->object_types) ) ? "{$src_name}:{$object_type}" : $src_name;
$display_name = $scoper->admin->interpret_src_otype($src_otype, false);
$display_name_plural = $scoper->admin->interpret_src_otype($src_otype, true);

echo '<h2>' . sprintf(__('%s Roles', 'scoper'), $display_name)
	. '&nbsp;&nbsp;<span style="font-size: 0.6em; font-style: normal">(<a href="#scoper_notes">' . __('see notes', 'scoper') . '</a>)</span>'
	. '</h2>';
	
if ( scoper_get_option('display_hints') ) {
	echo '<div class="rs-hint">';
	
	$restrictions_page = "rs-{$object_type}-restrictions";
	
	//printf(_ x('Expand access to a %2$s, potentially beyond what a user\'s WP role would allow. To reduce access, define %1$s%2$s&nbsp;Restrictions%3$s.', 'arguments are link open, object type name, link close', 'scoper'), "<a href='admin.php?page=$restrictions_page'>", $display_name, '</a>');
	printf(__('Expand access to a %2$s, potentially beyond what a user\'s WP role would allow. To reduce access, define %1$s%2$s&nbsp;Restrictions%3$s.', 'scoper'), "<a href='admin.php?page=$restrictions_page'>", $display_name, '</a>');
	echo '</div>';
}

$ignore_hierarchy = ! empty($otype->ignore_object_hierarchy);
?></h2>

<form action="" method="post" name="role_assign" id="role_assign">
<?php
wp_nonce_field( $nonce_id );

// ============ Users / Groups and Assignment Mode Selection Display ================

if ( empty($src->cols->parent) || $ignore_hierarchy )
	$assignment_modes = array( 
		ASSIGN_FOR_ENTITY_RS => __('Assign', 'scoper'),
		REMOVE_ASSIGNMENT_RS =>__('Remove', 'scoper')
	);
else
	$assignment_modes = array( 
		ASSIGN_FOR_ENTITY_RS => sprintf(__('Assign for selected %s', 'scoper'), $display_name_plural),
		ASSIGN_FOR_CHILDREN_RS => sprintf(__('Assign for sub-%s of selected', 'scoper'), $display_name_plural),
		ASSIGN_FOR_BOTH_RS => sprintf(__('Assign for selected and sub-%s', 'scoper'), $display_name_plural),
		REMOVE_ASSIGNMENT_RS =>__('Remove', 'scoper')
	);
$args = array( 'role_bases' => $role_bases, 'agents' => $agents, 'agent_caption_plural' => $agent_caption_plural, 'scope' => OBJECT_SCOPE_RS, 'src_or_tx_name' => $src_name );
ScoperAdminBulk::display_inputs(ROLE_ASSIGNMENT_RS, $assignment_modes, $args);

echo '<br />';
$args = array( 'role_bases' => $role_bases, 'default_hide_empty' => ! empty($otype->admin_default_hide_empty), 'hide_roles' => true, 'scope' => OBJECT_SCOPE_RS, 'src' => $src, 'otype' => $otype );
ScoperAdminBulk::item_tree_jslinks(ROLE_ASSIGNMENT_RS, $args );

// buffer prev/next caption for display with each obj type
//$prevtext = _ x('prev', 'abbreviated link to previous item', 'scoper');
//$nexttext = _ x('next', 'abbreviated link to next item', 'scoper');
$prevtext = __('prev', 'scoper');
$nexttext = __('next', 'scoper');

$site_url = get_option('siteurl');

$role_defs_by_otype = array();
$role_defs_by_otype[$object_type] = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_name, $object_type);

$object_roles = array();
foreach ( $role_bases as $role_basis ) {
	$args = array( 'role_handles' => array_keys( $role_defs_by_otype[$object_type] ) );
	$object_roles[$role_basis] = ScoperRoleAssignments::get_assigned_roles(OBJECT_SCOPE_RS, $role_basis, $src_name, $args);
}

$strict_objects = $scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name);

$object_names = array();
$object_status = array();
$listed_objects = array();
$unlisted_objects = array();

$col_id = $src->cols->id;
$col_parent = ( isset($src->cols->parent) && ! $ignore_hierarchy ) ? $src->cols->parent : '';

if ( $object_roles ) {
	$object_ids = array();
	foreach ( $object_roles as $basis_roles )
		$object_ids = $object_ids + array_keys($basis_roles);

	$object_ids = array_flip( array_unique($object_ids) );

	// Get the obj name, parent associated with each role (also sets $object_names, $unlisted objects)
	$listed_objects = ScoperAdminBulk::get_objects_info($object_ids, $object_names, $object_status, $unlisted_objects, $src, $otype, $ignore_hierarchy);
}

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
		/// convert keys from object ID to title+ID so we can alpha sort them
		$listed_objects_alpha = array();
		foreach ( array_keys($listed_objects) as $id )
			$listed_objects_alpha[ $listed_objects[$id]->{$src->cols->name} . chr(11) . $id ] = $listed_objects[$id];

		uksort($listed_objects_alpha, "strnatcasecmp");
		
		// convert to ordinal integer index
		$temp = array_fill( 0, count($listed_objects_alpha), true );
		$listed_objects = array_combine( array_keys( $temp ), $listed_objects_alpha );
	}
}

if ( ! $is_administrator )
	$admin_items = ScoperAdminBulk::filter_objects_listing(ROLE_ASSIGNMENT_RS, $object_roles, $src, $object_type);  // unsets $object_roles elements as needed
else
	$admin_items = '';	// no need to filter admins

$role_display = array();
$editable_roles = array();
foreach ( $scoper->role_defs->get_all_keys() as $role_handle ) {
	$role_display[$role_handle] = $scoper->role_defs->get_abbrev( $role_handle, OBJECT_UI_RS );
	
	if ( $scoper->admin->user_can_admin_role($role_handle, 0, $src->name, $object_type) )
		$editable_roles[0][$role_handle] = true;

	/* this is too slow for a large number of bulk items.  Will just show checkbox for roles user can edit base on their own termwide / blogwide role
	// Will still show all editable roles if they click on single object role edit (or Post/Page edit)
	if ( ! $is_administrator ) {
		foreach ( $listed_objects as $key => $obj )
			if ( isset($admin_items[$obj->$col_id]) )
				$editable_roles[$obj->$col_id][$role_handle] = $scoper->admin->user_can_admin_role($role_handle, $obj->$col_id, $src->name, $object_type);
	}
	*/
}

$args = array( 
'admin_items' => $admin_items, 	'editable_roles' => $editable_roles,						'role_bases' => $role_bases,
'agent_names' => $agent_names,			'agent_caption_plural' => $agent_caption_plural,	'agent_list_prefix' => $agent_list_prefix,
'ul_class' => 'rs-objlist',				'object_names' => $object_names,			'object_status' => $object_status,
'err' => $err,							'default_hide_empty' => ! empty($otype->admin_default_hide_empty)
);


ScoperAdminBulk::item_tree( OBJECT_SCOPE_RS, ROLE_ASSIGNMENT_RS, $src, $otype, $listed_objects, $object_roles, $strict_objects, $role_defs_by_otype, $role_codes, $args);
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