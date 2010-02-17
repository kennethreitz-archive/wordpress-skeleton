<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Insert a task
function insert_cp_task() {	
}

// Delete a task
function delete_cp_task($task, $title = NULL) {
	
	global $wpdb, $current_user;
	
	$cp_auth = $current_user->ID;
	$cp_date =  date("Y-m-d H:m:s");
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	$wpdb->query("
	DELETE FROM $table_name WHERE id = $task");
	
	insert_cp_activity($cp_auth, $cp_date, 'deleted', $title, 'task', NULL);
	
}

// Update task status

function update_cp_task($task, $status) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	$wpdb->query("
	UPDATE $table_name SET status = $status
	WHERE id = $task");
	
}

// Get tasks project id
function get_cp_task_project_id($id) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	$cp_get_task_project_id = $wpdb->get_var("SELECT DISTINCT proj_id FROM " . $table_name . " WHERE id = '".$id."'");
	
	if ($cp_get_task_project_id) {
	
		return $cp_get_task_project_id;
		
	} else {
		
		return false;
	
	}
	
}

// Get tasks title
function get_cp_task_title($id) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	$get_cp_task_title = $wpdb->get_var("SELECT DISTINCT title FROM " . $table_name . " WHERE id = '".$id."'");
	
	if ($get_cp_task_title) {
	
		return $get_cp_task_title;
		
	} else {
		
		return false;
	
	}
	
}

// List tasks
function list_cp_tasks($project_id=NULL, $page_name=NULL) {
	
	global $wpdb, $current_user;
	
	// If $page_name
	if ($page_name != NULL) {
		
		$cp_list_tasks_view = $_GET['view'];
	
		$cp_list_tasks_project = $_GET['project'];
		
		$page_name .= '&view=' . $cp_list_tasks_view . '&project=' . $cp_list_tasks_project;
		
	}
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	if($project_id) {
		
		$cp_list_my_tasks = $wpdb->get_results("SELECT * FROM $table_name WHERE proj_id = $project_id");
		
	} else {
	
		$cp_list_my_tasks = $wpdb->get_results("SELECT * FROM $table_name WHERE 1");
	
	}
	
	if ($cp_list_my_tasks) {
		
		foreach ($cp_list_my_tasks as $cp_list_my_task) {
			
			$user_info = get_userdata($cp_list_my_task->users);
			
			if ($cp_list_my_task->status != 1) {
				
				echo '<div style="height:auto">';
			
				echo '<div id="cp-gravatar" style="height:62px;width:62px;background:#F0F0F0;">';
			
				// Default gravatar
				$def_gravatar = "http://www.gravatar.com/avatar/c11f04eee71dfd0f49132786c34ea4ff?s=50&d=&r=G&forcedefault=1";
			
				// Get gravatar
				echo get_avatar( $user_info->user_email, $size = '50', $default = $def_gravatar );
			
				echo '</div>';
			
				if ($cp_list_my_task->users == $current_user->ID) {
			
					echo '<div style="background:#FFFFCC" id="cp-task-summary">';
				
				} else {
					
					echo '<div id="cp-task-summary">';
					
				}
			
				if ($cp_list_my_task->status != 1) {
			
					$link = 'admin.php?page='.$page_name.'&completed-task=' .$cp_list_my_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-complete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				} else {
					// This should never get executed / remove in future version
					$link = 'admin.php?page='.$page_name.'&reopened-task=' .$cp_list_my_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-uncomplete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				}
			
				if ($cp_list_my_task->status == 1) {
				
					echo ' <span style="text-decoration:line-through">';
				
				}
			
				echo "<strong>". $cp_list_my_task->title . "</strong>";
				
				$today = date("n-d-Y", mktime(date("n"), date("d"), date("Y")));
				
				if ($cp_list_my_task->due_date < $today) {
				
					$date_color = "#CC3333";
					
				} else {
					
					$date_color = "#33FF99";

				}
				
				echo " <code style='background:".$date_color."'>" . __('Due', 'collabpress') . ": " . $cp_list_my_task->due_date . "</code>";
			
				if ($cp_list_my_task->status == 1) {
					echo '</span>';
				}
			
				$link = 'admin.php?page='.$page_name.'&delete-task=' . $cp_list_my_task->id . '&task-title=' . $cp_list_my_task->title;
				$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_task') : $link;
				
				echo '<a style="color:#D54E21" href="'.$link.'">delete</a></p>';
				
				// If there is a description
				if ($cp_list_my_task->details) {
				
					echo '<p><strong>Description:</strong> ' . $cp_list_my_task->details . '</p>';
				
				}
				
				$my_task_count++;
				
				echo '</div>';
				
				echo '</div>';
				
			} else {
				
				$cp_completed_tasks[] = $cp_list_my_task;
				
			}
			
		}
		
		if ($cp_completed_tasks) {
			
			foreach ($cp_completed_tasks as $cp_completed_task) {
				
				$user_info = get_userdata($cp_completed_task->users);
				
				echo '<div style="height:auto">';
				
				echo '<div id="cp-gravatar" style="height:62px;width:62px;background:#F0F0F0;">';
			
				// Default gravatar
				$def_gravatar = "http://www.gravatar.com/avatar/c11f04eee71dfd0f49132786c34ea4ff?s=50&d=&r=G&forcedefault=1";
			
				// Get gravatar
				echo get_avatar( $user_info->user_email, $size = '50', $default = $def_gravatar );
			
				echo '</div>';
			
				if ($cp_completed_task->users == $current_user->ID) {
			
					echo '<div style="background:#FFFFCC" id="cp-task-summary">';
				
				} else {
					
					echo '<div style="background:#EEEEEE" id="cp-task-summary">';
					
				}
			
			
				if ($cp_completed_task->status != 1) {
					// This should never get executed - remove in future version
					?><p><input onclick="window.location='admin.php?page=<?php echo $page_name; ?>&completed-task=<?php echo $cp_completed_task->id; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				} else {
						
					$link = 'admin.php?page='.$page_name.'&reopened-task=' .$cp_completed_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-uncomplete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				}
			
				if ($cp_completed_task->status == 1) {
				
					echo ' <span style="text-decoration:line-through">';
				
				}
			
				echo "<strong>". $cp_completed_task->title . "</strong>";
				echo " <code>" . __('Due', 'collabpress') . ": " . $cp_completed_task->due_date . "</code>";
			
				if ($cp_completed_task->status == 1) {
					echo '</span>';
				}
			
				$link = 'admin.php?page='.$page_name.'&delete-task=' . $cp_completed_task->id . '&task-title=' . $cp_completed_task->title;
				$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_task') : $link;
				
				echo '<a style="color:#D54E21" href="'.$link.'">delete</a></p>';
				
				// If there is a description
				if ($cp_list_my_task->details) {
					
					echo '<p><strong>Description:</strong> ' . $cp_list_my_task->details . '</p>';
				
				}
				
				$my_task_count++;
				
				echo '</div>';
				
				echo '</div>';
				
			}
			
		}
		
		// View more
		// echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-dashboard-page&view=alltasks">' . __('View More', 'collabpress') . '</a></p>';	
	
	} else {
		
		echo "<p>No tasks......</p>";
		
	}
	
}

// List current user's tasks
function list_cp_my_tasks($project_id=NULL, $page_name=NULL) {
	
	global $wpdb, $current_user;
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	if($project_id) {
		
		$cp_list_my_tasks = $wpdb->get_results("SELECT * FROM $table_name WHERE users = $current_user->ID AND proj_id = $project_id");
		
	} else {
	
		$cp_list_my_tasks = $wpdb->get_results("SELECT * FROM $table_name WHERE users = $current_user->ID");
	
	}
	
	if ($cp_list_my_tasks) {
		
		foreach ($cp_list_my_tasks as $cp_list_my_task) {
			
			$user_info = get_userdata($cp_list_my_task->users);
			
			if ($cp_list_my_task->status != 1) {
				
				echo '<div style="height:auto">';
			
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
			
				if ($cp_list_my_task->status != 1) {
			
					$link = 'admin.php?page='.$page_name.'&completed-task=' .$cp_list_my_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-complete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				} else {
					// This should never get executed / remove in future version
					$link = 'admin.php?page='.$page_name.'&reopened-task=' .$cp_list_my_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-uncomplete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				}
			
				if ($cp_list_my_task->status == 1) {
				
					echo ' <span style="text-decoration:line-through">';
				
				}
			
				echo "<strong>". $cp_list_my_task->title . "</strong>";
				
				$today = date("n-d-Y", mktime(date("n"), date("d"), date("Y")));
				
				if ($cp_list_my_task->due_date < $today) {
				
					$date_color = "#CC3333";
					
				} else {
					
					$date_color = "#33FF99";

				}
				
				echo " <code style='background:".$date_color."'>" . __('Due', 'collabpress') . ": " . $cp_list_my_task->due_date . "</code>";
			
				if ($cp_list_my_task->status == 1) {
					echo '</span>';
				}
			
				$link = 'admin.php?page='.$page_name.'&delete-task=' . $cp_list_my_task->id . '&task-title=' . $cp_list_my_task->title;
				$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_task') : $link;
				
				echo '<a style="color:#D54E21" href="'.$link.'">delete</a></p>';
				
				// Display project title
				if ($cp_list_my_task->proj_id) {
					
					echo '<p><strong>' . __('Project:', 'collabpress') . '</strong> ' . get_cp_project_title($cp_list_my_task->proj_id) . '</p>';
				
				}
				
				// If there is a description
				if ($cp_list_my_task->details) {
					
					echo '<p><strong>' . __('Description:', 'collabpress') . '</strong> ' . $cp_list_my_task->details . '</p>';
				
				}
				
				$my_task_count++;
				
				echo '</div>';
				
				echo '</div>';
				
			} else {
				
				$cp_completed_tasks[] = $cp_list_my_task;
				
			}
			
		}
		
		if ($cp_completed_tasks) {
			
			foreach ($cp_completed_tasks as $cp_completed_task) {
				
				$user_info = get_userdata($cp_completed_task->users);
				
				echo '<div style="height:auto">';
				
				echo '<div id="cp-gravatar" style="height:62px;width:62px;background:#F0F0F0;">';
			
				// Default gravatar
				$def_gravatar = "http://www.gravatar.com/avatar/c11f04eee71dfd0f49132786c34ea4ff?s=50&d=&r=G&forcedefault=1";
				
				// User link
				echo '<a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">';
			
				// Get gravatar
				echo get_avatar( $user_info->user_email, $size = '50', $default = $def_gravatar );
				
				echo '</a>';
			
				echo '</div>';
			
				echo '<div style="background:#eeeeee" id="cp-task-summary">';
			
				if ($cp_completed_task->status != 1) {
					// This should never get executed - remove in future version
					?><p><input onclick="window.location='admin.php?page=<?php echo $page_name; ?>&completed-task=<?php echo $cp_completed_task->id; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				} else {
						
					$link = 'admin.php?page='.$page_name.'&reopened-task=' .$cp_completed_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-uncomplete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				}
			
				if ($cp_completed_task->status == 1) {
				
					echo ' <span style="text-decoration:line-through">';
				
				}
			
				echo "<strong>". $cp_completed_task->title . "</strong>";
				echo " <code>" . __('Due', 'collabpress') . ": " . $cp_completed_task->due_date . "</code>";
			
				if ($cp_completed_task->status == 1) {
					echo '</span>';
				}
			
				$link = 'admin.php?page='.$page_name.'&delete-task=' . $cp_completed_task->id . '&task-title=' . $cp_completed_task->title;
				$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_task') : $link;
				
				echo '<a style="color:#D54E21" href="'.$link.'">delete</a></p>';
				
				// Display project title
				if ($cp_list_my_task->proj_id) {
					
					echo '<p><strong>' . __('Project:', 'collabpress') . '</strong> ' . get_cp_project_title($cp_list_my_task->proj_id) . '</p>';
				
				}
				
				// If there is a description
				if ($cp_list_my_task->details) {
					
					echo '<p><strong>' . __('Description:', 'collabpress') . '</strong> ' . $cp_list_my_task->details . '</p>';
				
				}
				
				$my_task_count++;
				
				echo '</div>';
				
				echo '</div>';

			}
			
		}
		
		// View more
		// echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-dashboard-page&view=allmytasks">' . __('View More', 'collabpress') . '</a></p>';	
	
	} else {
		
		echo '<p>No tasks...</p>';
		
	}
	
}

// List tasks by user id
function list_cp_users_tasks($userid, $page_name) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
		$cp_list_my_tasks = $wpdb->get_results("SELECT * FROM $table_name WHERE users = $userid");
	
	if ($cp_list_my_tasks) {
		
		foreach ($cp_list_my_tasks as $cp_list_my_task) {
			
			$user_info = get_userdata($userid);
			
			if ($cp_list_my_task->status != 1) {
				
				echo '<div style="height:auto">';
			
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
			
				if ($cp_list_my_task->status != 1) {
			
					$link = 'admin.php?page='.$page_name.'&view=userpage&user=' . $user_info->ID . '&completed-task=' .$cp_list_my_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-complete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				} else {
					// This should never get executed / remove in future version
					$link = 'admin.php?page='.$page_name.'&view=userpage&user=' . $user_info->ID . '&reopened-task=' .$cp_list_my_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-uncomplete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				}
			
				if ($cp_list_my_task->status == 1) {
				
					echo ' <span style="text-decoration:line-through">';
				
				}
			
				echo "<strong>". $cp_list_my_task->title . "</strong>";
				
				$today = date("n-d-Y", mktime(date("n"), date("d"), date("Y")));
				
				if ($cp_list_my_task->due_date < $today) {
				
					$date_color = "#CC3333";
					
				} else {
					
					$date_color = "#33FF99";

				}
				
				echo " <code style='background:".$date_color."'>" . __('Due', 'collabpress') . ": " . $cp_list_my_task->due_date . "</code>";
			
				if ($cp_list_my_task->status == 1) {
					echo '</span>';
				}
			
				$link = 'admin.php?page='.$page_name.'&view=userpage&user=' . $user_info->ID . '&delete-task=' . $cp_list_my_task->id . '&task-title=' . $cp_list_my_task->title;
				$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_task') : $link;
				
				echo '<a style="color:#D54E21" href="'.$link.'">delete</a></p>';
				
				// Display project title
				if ($cp_list_my_task->proj_id) {
					
					echo '<p><strong>' . __('Project:', 'collabpress') . '</strong> ' . get_cp_project_title($cp_list_my_task->proj_id) . '</p>';
				
				}
				
				// If there is a description
				if ($cp_list_my_task->details) {
					
					echo '<p><strong>' . __('Description:', 'collabpress') . '</strong> ' . $cp_list_my_task->details . '</p>';
				
				}
				
				$my_task_count++;
				
				echo '</div>';
				
				echo '</div>';
				
			} else {
				
				$cp_completed_tasks[] = $cp_list_my_task;
				
			}
			
		}
		
		if ($cp_completed_tasks) {
			
			foreach ($cp_completed_tasks as $cp_completed_task) {
				
				$user_info = get_userdata($cp_completed_task->users);
				
				echo '<div style="height:auto">';
				
				echo '<div id="cp-gravatar" style="height:62px;width:62px;background:#F0F0F0;">';
			
				// Default gravatar
				$def_gravatar = "http://www.gravatar.com/avatar/c11f04eee71dfd0f49132786c34ea4ff?s=50&d=&r=G&forcedefault=1";
				
				// User link
				echo '<a href="admin.php?page=cp-dashboard-page&view=userpage&user=' . $user_info->ID . '">';
			
				// Get gravatar
				echo get_avatar( $user_info->user_email, $size = '50', $default = $def_gravatar );
				
				echo '</a>';
			
				echo '</div>';
			
				echo '<div style="background:#eeeeee" id="cp-task-summary">';
			
				if ($cp_completed_task->status != 1) {
					// This should never get executed - remove in future version
					?><p><input onclick="window.location='admin.php?page=<?php echo $page_name; ?>view=userpage&user=<?php echo $user_info->ID; ?>&completed-task=<?php echo $cp_completed_task->id; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				} else {
						
					$link = 'admin.php?page='.$page_name.'&view=userpage&user=' . $user_info->ID . '&reopened-task=' .$cp_completed_task->id;
					$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-uncomplete_task') : $link;
					?><p><input onclick="window.location='<?php echo $link; ?>'; return true;" type='checkbox' name='option1' value='1'><?php
				
				}
			
				if ($cp_completed_task->status == 1) {
				
					echo ' <span style="text-decoration:line-through">';
				
				}
			
				echo "<strong>". $cp_completed_task->title . "</strong>";
				echo " <code>" . __('Due', 'collabpress') . ": " . $cp_completed_task->due_date . "</code>";
			
				if ($cp_completed_task->status == 1) {
					echo '</span>';
				}
			
				$link = 'admin.php?page='.$page_name.'&view=userpage&user=' . $user_info->ID . '&delete-task=' . $cp_completed_task->id . '&task-title=' . $cp_completed_task->title;
				$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_task') : $link;
				
				echo '<a style="color:#D54E21" href="'.$link.'">delete</a></p>';
				
				// Display project title
				if ($cp_list_my_task->proj_id) {
					
					echo '<p><strong>' . __('Project:', 'collabpress') . '</strong> ' . get_cp_project_title($cp_list_my_task->proj_id) . '</p>';
				
				}
				
				// If there is a description
				if ($cp_list_my_task->details) {
					
					echo '<p><strong>' . __('Description:', 'collabpress') . '</strong> ' . $cp_list_my_task->details . '</p>';
				
				}
				
				$my_task_count++;
				
				echo '</div>';
				
				echo '</div>';

			}
			
		}
		
		// View more
		// echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-dashboard-page&view=allmytasks">' . __('View More', 'collabpress') . '</a></p>';	
	
	} else {
		
		echo '<p>No tasks...</p>';
		
	}
	
}

?>