<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Set Defaults
if (get_option('cp_email_config') == NULL) { update_option('cp_email_config', 1); }
if (get_option('cp_user_level') == NULL) { update_option('cp_user_level', 10); }

// DB Version
$cp_db_version = "0.1";

function cp_install () {
	
   global $wpdb, $cp_db_version;

	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";
		
   // Add activities table
   $activity_table_name = $wpdb->prefix . "cp_activity";
   if($wpdb->get_var("show tables like '$activity_table_name'") != $activity_table_name) {
      
	$sql = "CREATE TABLE " . $activity_table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  cp_id bigint(20) NOT NULL,
	  auth bigint(20) DEFAULT '0' NOT NULL,
	  date datetime NOT NULL,
	  action text NOT NULL,
	  title text NOT NULL,
	  type text NOT NULL,
	  PRIMARY KEY id (id)
	)$charset_collate;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

   }
   
   // Add projects table
   $projects_table_name = $wpdb->prefix . "cp_projects";
   if($wpdb->get_var("show tables like '$projects_table_name'") != $projects_table_name) {
      
	$sql = "CREATE TABLE " . $projects_table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  auth bigint(20) DEFAULT '0' NOT NULL,
	  date datetime NOT NULL,
	  title text NOT NULL,
	  details longtext NOT NULL,
	  PRIMARY KEY id (id)
	)$charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

   }
   
   // Add tasks table
   $tasks_table_name = $wpdb->prefix . "cp_tasks";
   if($wpdb->get_var("show tables like '$tasks_table_name'") != $tasks_table_name) {
      
	$sql = "CREATE TABLE " . $tasks_table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  proj_id bigint(20) DEFAULT '0' NOT NULL,
	  auth bigint(20) DEFAULT '0' NOT NULL,
	  users bigint(20) DEFAULT '0' NOT NULL,
	  date datetime NOT NULL,
	  title text NOT NULL,
	  details longtext NOT NULL,
	  due_date text NOT NULL,
	  status mediumint(9) DEFAULT '0' NOT NULL,
	  PRIMARY KEY id (id)
	)$charset_collate;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

   }

   // Add tasks meta table
   $tasks_table_name = $wpdb->prefix . "cp_tasksmeta";
   if($wpdb->get_var("show tables like '$tasks_table_name'") != $tasks_table_name) {
      
	$sql = "CREATE TABLE " . $tasks_table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  task_id bigint(20) DEFAULT '0' NOT NULL,
	  auth bigint(20) DEFAULT '0' NOT NULL,
	  meta_key varchar(255) DEFAULT NULL,
	  meta_value longtext,
	  date datetime NOT NULL,
	  PRIMARY KEY id (id)
	)$charset_collate;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

   }

	// Update DB Version
	update_option("cp_db_version", $cp_db_version);
   
}

?>