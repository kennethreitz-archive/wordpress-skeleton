<?php
// ------------------- Begin Common Code ------------------------
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

global $scoper;

if ( isset($_POST['rs_submit']) ) {
	$src_name = $_POST['src_name'];
	$object_type = $_POST['object_type'];
	$object_name = $_POST['object_name'];
	$object_id = $_POST['object_id'];
} else {
	$src_name = isset($_GET['src_name']) ? $_GET['src_name'] : '';
	$object_type = isset($_GET['object_type']) ? $_GET['object_type'] : '';
	$object_name = isset($_GET['object_name']) ? $_GET['object_name'] : '';
	$object_id = isset($_GET['object_id']) ? $_GET['object_id'] : '';
}

if ( ! $is_administrator && ! $scoper->admin->user_can_admin_object($src_name, $object_type, $object_id) )
	wp_die( __('You do not have permission to assign roles for this object.', 'scoper') );

// ==== Process Submission =====
$err = 0;
if ( isset($_POST['rs_submit'] ) ) {
	$scoper->filters_admin->mnt_save_object($src_name, '', $object_id);

	echo '<div id="message" class="updated fade"><p>';
	_e('Object Roles Updated.', 'scoper');
	echo '</p></div>';
}
?>

<div class="wrap agp-width97">
<h2><?php
printf(__('Assign Roles for %1$s "%2$s"', 'scoper'), $display_name, $object_name);
?></h2>

<form action="" method="post" name="role_assign" id="role_assign">
<input type="hidden" name="src_name" value="<?php echo $src_name ?>" />
<input type="hidden" name="object_type" value="<?php echo $object_type ?>" />
<input type="hidden" name="object_name" value="<?php echo $object_name ?>" />
<input type="hidden" name="object_id" value="<?php echo (int) $object_id ?>" />

<?php
$display_name = $scoper->data_sources->member_property($src_name, 'object_types', $object_type, 'display_name');
?>
<ul class='rs-list_horiz'>
<li style='float:right;margin: 1em 0.25em 0.25em 0.25em;'><span class="submit" style="border:none;">
<input type="submit" name="rs_submit" class="button-primary" value="<?php _e('Update &raquo;', 'scoper');?>" />
</span></li>
</ul>

<br />

<?php
$args = array();
$args['default_hide_threshold'] = 0;
$args['html_inserts'] = $scoper->data_sources->member_property('post', 'admin_inserts', 'bottom');
$args['default_role_basis'] = $role_basis;

if ( isset($args['html_inserts']->close->container) )
	$args['html_inserts']->close->container .= '<br />';

	include_once('item_roles_ui_rs.php');
	$item_roles_ui = new ScoperItemRolesUI();
	$item_roles_ui->single_object_roles_ui($src_name, $object_type, $object_id, $args);
?>

<p class="submit alignright" style="clear: both;border:none">
<input type="submit" name="rs_submit" class="button-primary" value="<?php _e('Update &raquo;', 'scoper');?>" />
</p>
<p style='clear:both'>
</p>
</form>

</div>