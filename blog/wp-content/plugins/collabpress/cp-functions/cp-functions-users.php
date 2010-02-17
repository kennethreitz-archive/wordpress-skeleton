<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// List users
function list_cp_users() {

	global $wpdb;
	
	$cp_proj_table_name = $wpdb->prefix . "cp_projects";
	
	$cp_tasks_table_name = $wpdb->prefix . "cp_tasks";
	
	$cp_list_users = $wpdb->get_results("SELECT auth FROM $cp_proj_table_name UNION SELECT users FROM $cp_tasks_table_name");
	
	if ($cp_list_users) {
		
		foreach ($cp_list_users as $cp_list_user) {
			
			$user_info = get_userdata($cp_list_user->auth);
			
			echo '<div id="cp-gravatar" style="height:62px;width:62px;background:#F0F0F0;">';
			
			// Default gravatar
			$def_gravatar = "http://www.gravatar.com/avatar/c11f04eee71dfd0f49132786c34ea4ff?s=50&d=&r=G&forcedefault=1";
			
			// User link
			echo '<a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">';
			
			// Get gravatar
			echo get_avatar( $user_info->user_email, $size = '50', $default = $def_gravatar );
			
			echo '</a>';
			
			echo '</div>';
			
			echo '<div id="cp-task-summary">';
			
			// User link
			echo '<a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">';
			
			// Display username
			echo '<p><strong>' . $user_info->user_nicename . '</strong></p>';
			
			echo '</a>';
			
			echo '</div>';
			
		}
		
	} else {
		
		// There are no users yet
		echo "<p>No users...</p>";
		
	}

}

?>