<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Insert a project
function insert_cp_project() {	
}

// Delete a project
function delete_cp_project($project_id) {	
	global $wpdb, $current_user;
	
	$cp_auth = $current_user->ID;
	$cp_date =  date("Y-m-d H:m:s");
	$title = get_cp_project_title($project_id);

	//delete project
	$table_name = $wpdb->prefix . "cp_projects";
	$wpdb->query("DELETE FROM $table_name WHERE id = $project_id");
	
	//delete all tasks for project
	$table_name = $wpdb->prefix . "cp_tasks";
	$wpdb->query("DELETE FROM $table_name WHERE proj_id = $project_id");

	insert_cp_activity($cp_auth, $cp_date, 'deleted', $title, 'project', NULL);
	
}

// Get project id by title
function get_cp_project_id($title) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_get_project_id = $wpdb->get_var("SELECT DISTINCT id FROM " . $table_name . " WHERE title = '".$title."'");
	
	if ($cp_get_project_id) {
	
		return $cp_get_project_id;
		
	} else {
		
		return false;
	
	}
	
}

// Get project title by id
function get_cp_project_title($id) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_get_project_title = $wpdb->get_var("SELECT DISTINCT title FROM " . $table_name . " WHERE id = '".$id."'");
	
	if ($cp_get_project_title) {
	
		return $cp_get_project_title;
		
	} else {
		
		return false;
	
	}
	
}

// Get project details by id
function get_cp_project_details($id) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_get_project_details = $wpdb->get_var("SELECT DISTINCT details FROM " . $table_name . " WHERE id = '".$id."'");
	
	if ($cp_get_project_details) {
	
		return $cp_get_project_details;
		
	} else {
		
		return false;
	
	}
	
}

// List projects
function list_cp_projects() {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_list_projects = $wpdb->get_results("SELECT * FROM $table_name WHERE 1");
	
	if ($cp_list_projects) {
	
		$project_count = 1;
		
		foreach ($cp_list_projects as $cp_list_project) {
			
		echo "<p>" . $project_count . ": <a href='admin.php?page=cp-projects-page&view=project&project=".$cp_list_project->id."'>" . $cp_list_project->title . "</a></p>";
		
		$project_count++;
		
		}
		
	} else {
		
		echo "<p>No projects...</p>";
		
	}
	
}

// Check if projects exist
function check_cp_project() {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_check_projects = $wpdb->get_results("SELECT * FROM $table_name WHERE 1");
	
	if ($cp_check_projects) {
		
		return true;
		
	} else {
		
		return false;
		
	}
	
}

// Check if specific project exists
function check_cp_project_exists($id) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_check_projects = $wpdb->get_results("SELECT id FROM $table_name WHERE id = '".$id."' ");
	
	if ($cp_check_projects) {
		
		return true;
		
	} else {
		
		return false;
		
	}
	
}

?>