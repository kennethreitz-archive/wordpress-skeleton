<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
?>

<div class="wrap agp-width97">
<h2><?php _e( 'Default Groups', 'scoper');?>
<span style="font-size: 0.6em; font-style: normal">&nbsp;&nbsp;(&nbsp;<a href="#scoper_notes"><?php _e('see notes', 'scoper');?></a>&nbsp;)</span>
</h2>

<?php
$groups_url = 'admin.php?page=rs-groups';
echo "<a href='$groups_url'>Back to Groups</a>";
?>

<form action="" method="post" name="role_assign" id="role_assign">

<?php
require_once('groups-support.php');
wp_nonce_field( 'scoper-assign-termroles' );

global $scoper;

if ( isset($_POST['rs_submit']) ) {
	$groups = ( isset($_POST['group']) ) ? $_POST['group'] : array();

	scoper_update_option( 'default_groups', $groups );

	echo '<div id="message" class="updated fade"><p>';
	printf(__('Default Groups Updated: %s groups', 'scoper'), count($groups) );
	echo '</p></div>';
}


if ( ! $all_groups = ScoperAdminLib::get_all_groups(UNFILTERED_RS) )
	return;	

if ( $editable_ids = ScoperAdminLib::get_all_groups(FILTERED_RS, COL_ID_RS) ) {
	echo "<div id='default_groupsdiv_rs' style='margin-top:1em'>";
	
	if ( ! $stored_groups = scoper_get_option( 'default_groups' ) ) {
		$stored_groups = array();
		echo '<p><strong>';
		_e( 'No default groups defined.', 'scoper' );
		echo '</strong></p>';
	}
	
	// WP Roles groups, other metagroups can't be a default group
	foreach ( $all_groups as $key => $group )
		if ( ! empty($group->meta_id) && in_array( $group->ID, $editable_ids ) )
			$editable_ids = array_diff( $editable_ids, array($group->ID) );
	
	$css_id = 'group';
	$locked_ids = array_diff($stored_groups, $editable_ids );
	$args = array( 'suppress_extra_prefix' => true, 'eligible_ids' => $editable_ids, 'locked_ids' => $locked_ids );
	
	require_once('agents_checklist_rs.php');
	ScoperAgentsChecklist::agents_checklist( ROLE_BASIS_GROUPS, $all_groups, $css_id, array_flip($stored_groups), $args);
	?>
	</div>
	<span class="submit" style="border:none;">
	<input type="submit" name="rs_submit" value="<?php _e('Update &raquo;', 'scoper');?>" />
	</span>
	<?php
} else
	_e( 'No groups defined.', 'scoper' );

?>
	
<a name="scoper_notes"></a>
<?php
echo '<br /><br /><h4>' . __("Notes", 'scoper') . ':</h4><ul class="rs-notes">';	
echo '<li>';
_e( 'Each new user will be added to the default groups. Existing users are not affected.', 'scoper');
echo '</li>';
echo '<li>';
_e( 'Use default groups only if you need the ability to manually remove a user from one of the groups later. To affect all users (or all users of a certain WP role), assign roles to the corresponding [WP role] group instead.', 'scoper');
echo '</li></ul>';
?>

</form>
</div>