<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );


function scoper_admin_section_roles($taxonomy) {
global $scoper, $wpdb;

if ( ! $tx = $scoper->taxonomies->get($taxonomy) )
	wp_die(__('Invalid taxonomy', 'scoper'));

$is_administrator = is_administrator_rs($tx, 'user');
	
$role_bases = array();

if ( USER_ROLES_RS && ( $is_administrator || $scoper->admin->user_can_admin_terms($taxonomy) ) )
	$role_bases []= ROLE_BASIS_USER;
	
if ( GROUP_ROLES_RS && ( $is_administrator || $scoper->admin->user_can_admin_terms($taxonomy) || current_user_can('manage_groups') ) )
	$role_bases []= ROLE_BASIS_GROUPS;

if ( empty($role_bases) )
	wp_die(__awp('Cheatin&#8217; uh?'));

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

// retrieve all terms to track hierarchical relationship, even though some may not be adminable by current user
$val = ORDERBY_HIERARCHY_RS;
$args = array( 'order_by' => $val );
$all_terms = $scoper->get_terms($taxonomy, UNFILTERED_RS, COLS_ALL_RS, 0, $args);


// =========================== Submission Handling =========================
if ( isset($_POST['rs_submit']) )
	$err = ScoperAdminBulk::role_submission(TERM_SCOPE_RS, ROLE_ASSIGNMENT_RS, $role_bases, $taxonomy, $role_codes, $agent_caption_plural, $nonce_id);
else
	$err = 0;


// =========================== Prepare Data ===============================

//$term_roles [role_basis] [src_name] [object_id] [role_handle] [agent_id] = array( 'assign_for' => ENUM , 'inherited_from' => assignment_id)
$term_roles = array();
foreach ( $role_bases as $role_basis )
	$term_roles[$role_basis] = ScoperRoleAssignments::get_assigned_roles( TERM_SCOPE_RS, $role_basis, $taxonomy );

if ( $col_id = $scoper->taxonomies->member_property($taxonomy, 'source', 'cols', 'id') ) {
	// determine which terms current user can admin
	if ( $admin_terms = $scoper->get_terms($taxonomy, ADMIN_TERMS_FILTER_RS, COL_ID_RS) ) {
		$admin_terms = array_fill_keys( $admin_terms, true );
	}
} else
	$admin_terms = array();

// =========================== Display UI ===============================
?>
<div class="wrap agp-width97" id="rs_admin_wrap">
<?php
echo '<h2>' . sprintf(__('%s Roles', 'scoper'), $tx->display_name)
	. '&nbsp;&nbsp;<span style="font-size: 0.6em; font-style: normal">(<a href="#scoper_notes">' . __('see notes', 'scoper') . '</a>)</span>'
	. '</h2>';

if ( scoper_get_option('display_hints') ) {
	echo '<div class="rs-hint">';
	if ( ! empty($tx->requires_term) ) {
		//printf(_ x('Grant capabilities within a specific %2$s, potentially more than a user\'s WP role would allow. To reduce access, define %1$s%2$s&nbsp;Restrictions%3$s.', 'arguments are link open, taxonomy name, link close', 'scoper'), "<a href='admin.php?page=rs-$taxonomy-restrictions_t'>", $tx->display_name, '</a>');
		printf(__('Grant capabilities within a specific %2$s, potentially more than a user\'s WP role would allow. To reduce access, define %1$s%2$s&nbsp;Restrictions%3$s.', 'scoper'), "<a href='admin.php?page=rs-$taxonomy-restrictions_t'>", $tx->display_name, '</a>');
	} else
		printf(__('Grant capabilities within a specific %s, potentially more than a user\'s WP role would allow.', 'scoper'), $tx->display_name);
		
	echo '</div>';
}

if ( ! $role_defs_by_otype = $scoper->role_defs->get_for_taxonomy($tx->object_source, $taxonomy) ) {
	echo '<br />' . sprintf (__( 'Role definition error (taxonomy: %s).', 'scoper'), $taxonomy);
	echo '</div>';
	return;
}

if ( empty($admin_terms) ) {
	echo '<br />' . sprintf(__( 'Either you do not have permission to administer any %s, or none exist.', 'scoper'), $tx->display_name_plural);
	echo '</div>';
	return;
}
?>
<form action="" method="post" name="role_assign" id="role_assign">
<?php
wp_nonce_field( $nonce_id );

echo '<br /><div id="rs-term-scroll-links">';
echo ScoperAdminUI::taxonomy_scroll_links($tx, $all_terms, $admin_terms);
echo '</div><hr />';

// ============ Users / Groups and Assignment Mode Selection Display ================
// little hack to avoid awkward caption with "link category" {
$display_name_plural = ( 'link_category' == $taxonomy ) ? strtolower( $scoper->taxonomies->member_property('category', 'display_name_plural') ) : strtolower($tx->display_name_plural);

if ( empty($tx->source->cols->parent) || ( ! empty($tx->uses_standard_schema) && empty($tx->hierarchical) ) )
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
$args = array( 'role_bases' => $role_bases, 'agents' => $agents, 'agent_caption_plural' => $agent_caption_plural, 'scope' => TERM_SCOPE_RS, 'src_or_tx_name' => $taxonomy   );
ScoperAdminBulk::display_inputs(ROLE_ASSIGNMENT_RS, $assignment_modes, $args);

$args = array( 'role_bases' => $role_bases );
ScoperAdminBulk::item_tree_jslinks(ROLE_ASSIGNMENT_RS, $args);

echo '<div id="rs-section-roles">';

// IE (6 at least) won't obey link color directive in a.classname CSS
$ie_link_style = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) ? ' style="color:white;" ' : '';

$args = array( 'include_child_restrictions' => true, 'return_array' => true, 'role_type' => SCOPER_ROLE_TYPE, 'force_refresh' => true );
$strict_terms = $scoper->get_restrictions(TERM_SCOPE_RS, $taxonomy, $args );

$src_name = $tx->object_source->name;

$editable_roles = array();
foreach ( $all_terms as $term ) {
	$id = $term->$col_id;

	foreach ( $role_defs_by_otype as $object_type => $role_defs )
		foreach ( array_keys($role_defs) as $role_handle )
			if ( $role_assigner->user_has_role_in_term( array($role_handle=>1), $taxonomy, $id, '', array('src_name' => $src_name, 'object_type' => $object_type) ) )
				$editable_roles[$id][$role_handle] = true;
}

$args = array( 
'admin_items' => $admin_terms, 	'editable_roles' => $editable_roles,	'role_bases' => $role_bases,
'agent_names' => $agent_names,	'agent_caption_plural' => $agent_caption_plural,	'agent_list_prefix' => $agent_list_prefix,
'ul_class' => 'rs-termlist', 	'ie_link_style' => $ie_link_style,					'err' => $err
);

ScoperAdminBulk::item_tree( TERM_SCOPE_RS, ROLE_ASSIGNMENT_RS, $tx->source, $tx, $all_terms, $term_roles, $strict_terms, $role_defs_by_otype, $role_codes, $args);

echo '<hr /><div style="background-color: white;"></div>';
echo '</div>'; //rs-section-roles

//================================ Notes Section ==================================
?>
</form>
<a name="scoper_notes"></a>
<?php
$args = array( 'display_links' => true, 'display_restriction_key' => true );
ScoperAdminUI::role_owners_key($tx, $args);

echo '<br /><h4 style="margin-bottom:0.1em">' . __("Notes", 'scoper') . ':</h4><ul class="rs-notes">';	

echo '<li>';
_e('A Role is a collection of capabilities.', 'scoper');
echo '</li>';

echo '<li>';
_e("Capabilities in a user's WordPress Role (and, optionally, RS-assigned General Roles) enable blog-wide operations (read/edit/delete) on some object type (post/page/link), perhaps of a certain status (private/published/draft).", 'scoper');
echo '</li>';

echo '<li>';
if ( empty($tx->object_source->no_object_roles) )
	printf(__('Scoped Roles can grant users these same WordPress capabilities on a per-%1$s or per-%2$s basis. Useful in fencing off sections your site.', 'scoper'), $tx->display_name, $tx->object_source->display_name);
else
	printf(__('Scoped Roles can grant users these same WordPress capabilities on a per-%1$s basis. Useful in fencing off sections your site.', 'scoper'), $tx->display_name, $tx->object_source->display_name);
echo '</li>';

echo '<li>';
printf(__('Users with a %1$s Role assignment may have capabilities beyond their General Role(s) for %2$s in the specified %1$s.', 'scoper'), $tx->display_name, $tx->object_source->display_name_plural);
echo '</li>';

if ( ! empty($tx->requires_term) ) {
	echo '<li>';
	printf(__('If a role is restricted for some %s, general (blog-wide) assignments of that role are ignored.', 'scoper'), $tx->display_name);
	echo '</li>';

	echo '<li>';
	printf(__('If a %1$s is in multiple %2$s, permission is granted if any %3$s has a qualifying role assignment or permits a qualifying General Role.', 'scoper'), $tx->object_source->display_name, $tx->display_name_plural, $tx->display_name);
	echo '</li>';
}

if ( ('category' == $taxonomy) && ( ! scoper_get_otype_option('use_term_roles', 'post', 'page') ) )
	ScoperAdminUI::common_ui_msg( 'pagecat_plug' );

if ( empty($tx->object_source->no_object_roles) ) {
	echo '<li>';
	printf(__('If a role is restricted for some requested %1$s, %2$s-assignment and General-assignment of that role are ignored.', 'scoper'), $tx->object_source->display_name, $tx->display_name);
	echo '</li>';
}

echo '<li>';
_e('Administrators are exempted from Role Restrictions.', 'scoper');
echo '</li></ul>';

echo('<a href="#scoper_top">' . __('top', 'scoper') . '</a>');
?>
</div>
<?php
} // end wrapper function scoper_admin_section_roles
?>