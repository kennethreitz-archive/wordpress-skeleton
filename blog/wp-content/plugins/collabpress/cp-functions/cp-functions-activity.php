<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Insert an activity
function insert_cp_activity($auth, $date, $action, $title, $type, $cp_id) {
	
	global $wpdb;
	
	$cp_auth = $auth;
	$cp_date = $date;
	$cp_action = $action;
	$cp_title = $title;
	$cp_type = $type;
	
	$activity_table_name = $wpdb->prefix . "cp_activity";
	
	$insert = "INSERT INTO " . $activity_table_name .
		" (cp_id, auth, date, action, title, type) " .
	    "VALUES ('" . $cp_id . "','" . $cp_auth . "','" . $cp_date . "','" . $cp_action . "','" . $cp_title . "','" . $cp_type . "')";
	
	$results = $wpdb->query( $insert );
	
}

// Delete an activity
function delete_cp_activity() {
}

// List activities
function list_cp_activity($view_more = NULL) {

	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_activity";
	
	if ($view_more) {
		$cp_list_activities = $wpdb->get_results("SELECT * FROM $table_name WHERE 1 ORDER BY id DESC LIMIT 0,20");
	} else {
		$cp_list_activities = $wpdb->get_results("SELECT * FROM $table_name WHERE 1 ORDER BY id DESC LIMIT 0,4");
	}
	
	if ($cp_list_activities) {
	
		foreach ($cp_list_activities as $cp_list_activity) {
			
			$user_info = get_userdata($cp_list_activity->auth);
			
			echo '<div id="cp-gravatar" style="height:62px;width:62px;background:#F0F0F0;">';
			
			// Default Gravatar
			$def_gravatar = "http://www.gravatar.com/avatar/c11f04eee71dfd0f49132786c34ea4ff?s=50&d=&r=G&forcedefault=1";
			
			// User link
			echo '<a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">';
			
			// Get Gravatar
			echo get_avatar( $user_info->user_email, $size = '50', $default = $def_gravatar );
			
			echo '</a>';
		
			echo '</div>';
			
			if ($cp_list_activity->action == 'created' || $cp_list_activity->action == 'added' || $cp_list_activity->action == 'completed') {
				$activity_color = 'green';
			} else if ($cp_list_activity->action == 'edited') {
				$activity_color = 'orange';
			} else if ($cp_list_activity->action == 'deleted') {
				$activity_color = 'red';
			} else if ($cp_list_activity->action == 'reopened') {
				$activity_color = 'orange';
			} else {
				$activity_color = 'black';
			}
			
			echo '<div id="cp-task-summary">';
		
			echo '<p><strong>Date:</strong> ' . $cp_list_activity->date . '</p>';
			
			// If this is a task
			if ($cp_list_activity->type == 'task' || $cp_list_activity->type == 'comment' || $cp_list_activity->type == 'completed' || $cp_list_activity->type == 'reopened' && $cp_list_activity->cp_id != 0 ) {
			
				$task_project_id =  get_cp_task_project_id($cp_list_activity->cp_id);

				$task_project_title =  get_cp_project_title($task_project_id);
			
				echo '<p><strong>Project:</strong> ' . $task_project_title;
				
				echo '<p><strong>Summary: </strong><a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">' . $user_info->user_nicename . '</a></strong> <span style="color:'.$activity_color.';">' . $cp_list_activity->action . '</span> ' . $cp_list_activity->type . ' "<a href="admin.php?page=cp-projects-page&view=project&project='.$task_project_id.'">' . $cp_list_activity->title . '</a>".</p>';
			
			// If this is a project
			} else if ($cp_list_activity->type == 'project' && $cp_list_activity->cp_id != 0) {
			
				$activity_project_id = $cp_list_activity->cp_id;
			
				echo '<p><strong>Summary: </strong><a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">' . $user_info->user_nicename . '</a></strong> <span style="color:'.$activity_color.';">' . $cp_list_activity->action . '</span> ' . $cp_list_activity->type . ' "<a href="admin.php?page=cp-projects-page&view=project&project='.$activity_project_id.'">' . $cp_list_activity->title . '</a>".</p>';
			
			// If it's neither then it's been deleted and don't display link
			} else {
				
				echo '<p><strong>Summary: </strong><a href="#">' . $user_info->user_nicename . '</a></strong> <span style="color:'.$activity_color.';">' . $cp_list_activity->action . '</span> ' . $cp_list_activity->type . ' ' . $cp_list_activity->title . '.</p>';
				
			}
			
			echo '</div>';
			
		}
		
		if (!$view_more) {
		
			echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-dashboard-page&view=allactivity">' . __('View More', 'collabpress') . '</a></p>';	
		
		} else {
		
			echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-dashboard-page">' . __('Back', 'collabpress') . '</a></p>';
		
		}
	
	} else {
	
		echo "<p>No recent activity...</p>";
	
	}

}

?>