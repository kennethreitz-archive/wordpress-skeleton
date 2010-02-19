<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('role-scoper_init.php');
	
global $sitewide_groups;

if ( IS_MU_RS ) {
	if ( ! isset($sitewide_groups) )
		$sitewide_groups = scoper_establish_group_scope();
} else
	$sitewide_groups = false;
	
global $wpdb;

//table names for scoper-specific data; usually no reason to alter these
$wpdb->user2role2object_rs = $wpdb->prefix . 'user2role2object_rs'; 
$wpdb->role_scope_rs = $wpdb->prefix . 'role_scope_rs';

//default names for tables which might otherwise be replaced by existing groups & user2group tables from an external forum app
// (must be stored within the Wordpress DB)
$prefix = ( ! empty($wpdb->base_prefix) && $sitewide_groups ) ? $wpdb->base_prefix : $wpdb->prefix;

$wpdb->groups_basename =	'groups_rs';
$wpdb->groups_rs = 			$prefix . $wpdb->groups_basename; 

$wpdb->user2group_rs = 	$prefix . 'user2group_rs';


//default column names for groups table; may need to change if using another app's table
// (note, if no equivalent column exists in the existing table, scoper column can usually be created without bothering the other app)
$wpdb->groups_id_col = 		 'ID';
$wpdb->groups_name_col = 	 'group_name';
$wpdb->groups_descript_col = 'group_description';
$wpdb->groups_homepage_col = 'group_homepage';
$wpdb->groups_meta_id_col =  'group_meta_id';

//default column names for user2group table; may need to change if using another app's table
// (note, if no equivalent column exists in the existing table, scoper column can usually be created without bothering the other app)
$wpdb->user2group_gid_col = 		'group_id';
$wpdb->user2group_uid_col = 		'user_id';
$wpdb->user2group_assigner_id_col = 'assigner_id';


// sample integration with Vanilla (not recently tested or officially supported):
// For groups and user2groups tables, user Vanilla forum's lum_role and lum_userrole (possible only if these are installed within WP database)
//$wpdb->groups_rs = 		'lum_role';
//$wpdb->user2group_rs = 	'lum_userrole';

//$wpdb->groups_id_col = 		'RoleID';
//$wpdb->groups_name_col = 		'Name';
//$wpdb->groups_descript_col = 	'Description';

//$wpdb->user2group_gid_col = 	'RoleID';
//$wpdb->user2group_uid_col = 	'UserID';

// NOTE: default value insertion for custom group tables is not yet implemented by RS
//default values to insert into specified columns of shared tables
//  i.e. when using Vanilla 1.03 "Roles" table for scoper groups, insert default Vanilla Member permissions: 
//$default_value[$groups]['Permissions'] = 'a:33:{s:23:"PERMISSION_ADD_COMMENTS";i:1;s:27:"PERMISSION_START_DISCUSSION";i:1; ...

?>