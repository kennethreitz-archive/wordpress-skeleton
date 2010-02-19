<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Define page name
define('CP_PROJECTS_METABOX_PAGE_NAME', 'cp-projects-page');

// Create projects class
class cp_core_projects {

	// Constructor
	function cp_core_projects() {
		
		// Add filter for WP 2.8 box system
		add_filter('screen_layout_columns', array(&$this, 'cp_onscreen_layout_columns'), 10, 2);
		
		// Register callback
		add_action('admin_menu', array(&$this, 'cp_onadmin_menu')); 
		
		// Register the callback been used if options of page been submitted and needs to be processed
		add_action('admin_post_save_cp_projects_metaboxes_general', array(&$this, 'cp_onsave_changes'));
		
	}
	
	// For WordPress 2.8 column support
	function cp_onscreen_layout_columns($columns, $screen) {
		
		if ($screen == $this->pagehook) {
			$columns[$this->pagehook] = 2;
		}
		
		return $columns;
		
	}
	
	// Extend the admin menu
	function cp_onadmin_menu() {
		
		// Add our own option page, you can also add it to different sections or use your own one
		$this->pagehook = add_submenu_page(CP_DASHBOARD_METABOX_PAGE_NAME, 'CollabPress - Projects', "Projects", CP_MINIMUM_USER, CP_PROJECTS_METABOX_PAGE_NAME, array(&$this, 'cp_onshow_page'));
		
		// Register callback gets call prior your own page gets rendered
		add_action('load-'.$this->pagehook, array(&$this, 'cp_onload_page'));
		
	}
	
	// Will be executed if wordpress core detects this page has to be rendered
	function cp_onload_page() {
		
		// Ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		
		add_meta_box('cp-projects-metaboxes-sidebox-1', __( 'Calendar', 'collabpress' ), array(&$this, 'cp_projects_sidebox_1_content'), $this->pagehook, 'side', 'core');
		add_meta_box('cp-projects-metaboxes-sidebox-2', __( 'Projects', 'collabpress' ), array(&$this, 'cp_projects_onsidebox_2_content'), $this->pagehook, 'side', 'core');
		add_meta_box('cp-projects-metaboxes-sidebox-3', __( 'Users', 'collabpress' ), array(&$this, 'cp_projects_onsidebox_3_content'), $this->pagehook, 'side', 'core');
		
		
		// Project page
		if (isset($_GET['view']) && $_GET['view'] == 'project') {
			
			add_meta_box('cp-projects-metaboxes-contentbox-2', 'Tasks', array(&$this, 'cp_projects_oncontentbox_2_content'), $this->pagehook, 'normal', 'core');
			add_meta_box('cp-projects-metaboxes-contentbox-3', 'Add A New Task', array(&$this, 'cp_projects_oncontentbox_3_content'), $this->pagehook, 'normal', 'core');
		
		// Edit project	
		} else if (isset($_GET['view']) && $_GET['view'] == 'edit-project') {
		
			add_meta_box('cp-projects-metaboxes-contentbox-4', 'Edit Project', array(&$this, 'cp_projects_oncontentbox_4_content'), $this->pagehook, 'normal', 'core');
		
		// Edit task	
		} else if (isset($_GET['view']) && $_GET['view'] == 'edit-task') {
			
			add_meta_box('cp-projects-metaboxes-contentbox-5', 'Edit Task', array(&$this, 'cp_projects_oncontentbox_5_content'), $this->pagehook, 'normal', 'core');
		
		// Add project
		} else  {
			
			add_meta_box('cp-projects-metaboxes-contentbox-1', 'Create New Project', array(&$this, 'cp_projects_oncontentbox_1_content'), $this->pagehook, 'normal', 'core');
			add_meta_box('cp-projects-metaboxes-contentbox-additional-2', __( 'About', 'collabpress' ), array(&$this, 'cp_projects_oncontentbox_additional_2_content'), $this->pagehook, 'additional', 'core');
			
		}
	
	}
	
	// Executed to show the plugins complete admin page
	function cp_onshow_page() {
		
		// We need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		
		require ( CP_PLUGIN_DIR . '/cp-core/cp-core-isset.php' );
		
		// Define some data can be given to each metabox during rendering
		$data = array();
		
		?>
		
		<div id="cp-projects-metaboxes-general" class="wrap">
		
		<?php // screen_icon('options-general'); ?>
		
		<?php
		
		// If we have a project ID
		if (isset($_GET['project'])) {
			// Get Project Title
			$cp_project_title = get_cp_project_title(esc_html($_GET['project']));
			// Get Project Description
			$cp_project_details = get_cp_project_details(esc_html($_GET['project']));
		}
		
		?>
		
		<?php if(isset($cp_project_title) && isset($_GET['view']) && isset($_GET['project']) && $_GET['view'] != 'edit-project') { ?>
        	<?php
			$link = 'admin.php?page=cp-dashboard-page&delete-project='.esc_html($_GET['project']).'&project='.esc_html($_GET['project']);
			$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cp-action-delete_project') : $link;
			?>
			<p><h2><?php echo stripslashes($cp_project_title); ?>  - <a href="admin.php?page=cp-projects-page&view=edit-project&project=<?php echo esc_html($_GET['project']); ?>"><?php _e('edit', 'collabpress'); ?></a> <a style="color:#D54E21" href="<?php echo $link; ?>" onclick="javascript:check=confirm('<?php _e('WARNING: This will delete this project and all project tasks.\n\nChoose [Cancel] to Stop, [OK] to delete.\n'); ?>');if(check==false) return false;"><?php _e('delete', 'collabpress'); ?></a></h2></p>
			
			<?php if($cp_project_details) { ?>
				<p><strong><?php _e('Description: ', 'collabpress'); ?></strong><?php echo stripslashes($cp_project_details); ?></p>
			<?php } ?>
		
		<?php } else if(isset($_GET['view']) && $_GET['view'] == 'edit-project') { ?>
			<p><h2>Edit Project</h2></p>
		<?php } else if(isset($_GET['view']) && $_GET['view'] == 'edit-task') { ?>
			<p><h2>Edit Task</h2></p>
		<?php } else { ?>
			<p><h2>Create New Project</h2></p>
		<?php } ?>
		
		
			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>
				</div>
				
				<div id="post-body" class="has-sidebar">
					
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes($this->pagehook, 'normal', $data); ?>
						<?php do_meta_boxes($this->pagehook, 'additional', $data); ?>
					</div>
				
					<form action="admin-post.php" method="post">
						<?php wp_nonce_field('cp-projects-metaboxes-general'); ?>
						<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
						<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
						<input type="hidden" name="action" value="save_cp_projects_metaboxes_general" />

						<p style="display:none">
							<input type="submit" value="<?php _e( 'Save Changes', 'collabpress' ) ?>" class="button-primary" name="Submit"/>	
						</p>
					</form>
					
				</div>
				
				<br class="clear"/>	
						
			</div>
			
		</div>
		
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// Close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// Postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
			});
			//]]>
		</script>
		
		<?php
	}

	// Executed if the post arrives initiated by pressing the submit button of form
	function cp_onsave_changes() {
		
		// User permission check
		if ( !current_user_can(CP_MINIMUM_USER) )
			wp_die( __('Cheatin&#8217; uh?', 'collabpress') );		
				
		// Cross check the given referer.
		check_admin_referer('cp-projects-metaboxes-general');
		
		// Process here your on $_POST validation and / or option saving
		
		// Lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect($_POST['_wp_http_referer']);	
			
	}

	// Below you will find for each registered metabox the callback method, that produces the content inside the boxes
	function cp_projects_sidebox_1_content($data) {
		?><center><?php
		$time = time();
    	echo cp_generate_small_calendar(date('Y', $time), date('n', $time));
		echo '<p><a style="text-decoration:none; color:#D54E21" href="#">' . __('Coming Soon', 'collabpress') . '</a></p>';	
    	?></center><?php	
	}
	
	function cp_projects_onsidebox_2_content($data) {
		list_cp_projects();
		echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-projects-page">' . __('Add New', 'collabpress') . '</a></p>';	
	}
	
	function cp_projects_onsidebox_3_content($data) {
		list_cp_users();
	}
	
	function cp_projects_oncontentbox_1_content($data) {
	?>
		<form method="post" action="">
		<?php wp_nonce_field('cp-add-project'); ?>
		
		<table class="form-table">
		<tr class="form-field form-required">
		<th scope="row"><label for="cp_project_title"><?php _e('Title', 'collabpress'); ?> <span class="description"><?php _e('(required)', 'collabpress'); ?></span></label></th>
		<td><input name="cp_project_title" type="text" id="cp_project_title" value="" aria-required="true" /></td>
		</tr>
		<tr class="form-field">
		<th scope="row"><label for="cp_project_details"><?php _e('Details', 'collabpress'); ?></label></th>
		<td><input name="cp_project_details" type="text" id="cp_project_details" value="" /></td>
		</tr>			
		</table>
		
		<input type="hidden" name="page_options" value="cp_project_title, cp_project_details" />
		
		<p>
		<input type="submit" class="button-primary" name="cp_add_project_submit" value="<?php _e('Add Project', 'collabpress') ?>" />
		</p>
		
		</form>
	<?php
	}
	
	function cp_projects_oncontentbox_2_content($data) {
		
		$project_id = $_GET['project'];
		
		list_cp_tasks($project_id, CP_PROJECTS_METABOX_PAGE_NAME);
	
	}
	
	function cp_projects_oncontentbox_3_content($data) {
	?>
		<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field('cp-add-task'); ?>
		
		<table class="form-table">
		<tr class="form-field form-required">
		<th scope="row"><label for="cp_title"><?php _e('Title: ', 'collabpress'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
		<td><input name="cp_title" type="text" id="cp_title" value="" aria-required="true" /></td>
		</tr>
		
		<tr class="form-field">
		<th scope="row"><label for="cp_details"><?php _e('Details: ', 'collabpress'); ?></label></th>
		<td><textarea name="cp_details" id="cp_details" rows="10" cols="20" /></textarea></td>
		</tr>
		
		<tr class="form-field">
		<th scope="row"><label for="cp_users"><?php _e('Assign to: ', 'collabpress'); ?></label></th>
		<td><?php wp_dropdown_users(); ?><td>
		</tr>
		
		<tr class="form-field">
		<th scope="row"><label for="cp_notify"><?php _e('Notify via Email?', 'collabpress'); ?></label></th>
		<td align="left">
			<?php 
			//check if email option is enabled
			if (get_option('cp_email_config')) {
				$checked = 'checked="checked"';
			}            
            ?>
            <input type="checkbox" name="notify" <?php echo $checked; ?> style="width:3%;" />
        <td>
		</tr>
        
		<tr class="form-field">
		<th scope="row"><label for="cp_tasks_due"><?php _e('Due: ', 'collabpress'); ?></label></th>
		<td>
		<?php
			$months = array (1 => 'January', 'February', 'March', 'April', 'May', 'June','July', 'August', 'September', 'October', 'November', 'December');
			$days = range (1, 31);
			$years = range (date('Y'), 2025);
			
			// Month
			echo __('Month', 'collabpress') . ": <select name='cp_tasks_due_month'>";
			$cp_month_count = 1;
			foreach ($months as $value) {
				
				if ($value == date('F')) {
					$month_selected = "SELECTED";
				} else {
					$month_selected = '';
				}
				
				echo '<option ' . $month_selected . ' value="'.$cp_month_count.'">'.$value.'</option>\n';
				$cp_month_count++;
			} echo '</select>';
			
			// Day
			echo __('Day', 'collabpress') . ": <select name='cp_tasks_due_day'>";
			foreach ($days as $value) {
				
				if ($value == date('j')) {
					$day_selected = "SELECTED";
				} else {
					$day_selected = '';
				}
				
				echo '<option ' . $day_selected . ' value="'.$value.'">'.$value.'</option>\n';
			} echo '</select>';
			
			
			// Year
			echo __('Year', 'collabpress') . ": <select name='cp_tasks_due_year'>";
			foreach ($years as $value) {
				
				if ($value == date('Y')) {
					$year_selected = "SELECTED";
				} else {
					$year_selected = '';
				}
				
				echo '<option ' . $year_selected . ' value="'.$value.'">'.$value.'</option>\n';
			} 
			echo '</select>';
		?>
		</td>
		</tr>

		</table>
		
		<input type="hidden" name="cp_add_tasks_project" value="<?php echo $_GET['project']; ?>">
		<input type="hidden" name="page_options" value="user, cp_title, cp_details, cp_users, cp_tasks_due_month, cp_tasks_due_day, cp_tasks_due_year, cp_add_tasks_project" />
		
		<p>
		<input type="submit" class="button-primary" name="cp_add_task_button" value="<?php _e('Add Task', 'collabpress') ?>" />
		</p>
		
		</form>
	<?php
	}
	
	function cp_projects_oncontentbox_4_content($data) {
		
		if (check_cp_project_exists(esc_html($_GET['project']))) {
		
			// Get Edit Project Title
			$cp_edit_project_title = get_cp_project_title(esc_html($_GET['project']));
			// Get Edit Project Description
			$cp_edit_project_details = get_cp_project_details(esc_html($_GET['project']));
		
	?>
			<form method="post" action="">
			<?php wp_nonce_field('cp-edit-project'); ?>

			<table class="form-table">
			<tr class="form-field form-required">
			<th scope="row"><label for="cp_edit_project_title"><?php _e('Title', 'collabpress'); ?> <span class="description"><?php _e('(required)', 'collabpress'); ?></span></label></th>
			<td><input name="cp_edit_project_title" type="text" id="cp_edit_project_title" value="<?php echo stripslashes($cp_edit_project_title); ?>" aria-required="true" /></td>
			</tr>
			<tr class="form-field">
			<th scope="row"><label for="cp_edit_project_details"><?php _e('Details', 'collabpress'); ?></label></th>
			<td><input name="cp_edit_project_details" type="text" id="cp_edit_project_details" value="<?php echo stripslashes($cp_edit_project_details); ?>" /></td>
			</tr>			
			</table>

			<input type="hidden" name="cp_edit_project_id" value="<?php echo esc_html($_GET['project']); ?>">
			<input type="hidden" name="edit_project_options" value="cp_edit_project_title, cp_edit_project_details" />

			<p>
			<input type="submit" class="button-primary" name="cp_edit_project_submit" value="<?php _e('Edit Project', 'collabpress') ?>" />
			</p>

			</form>
		<?php
	
		} else {
			
			echo "<p>" . __("Project doesn't exist...", "collabpress") . "</p>";
			
		}
		
	}
	
	function cp_projects_oncontentbox_5_content($data) {
		
		if (check_cp_task_exists(esc_html($_GET['task']))) {
			
		$edit_task = get_taskdata(esc_html($_GET['task']));
		
		?>
		
			<form method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field('cp-edit-task'); ?>

			<table class="form-table">
			<tr class="form-field form-required">
			<th scope="row"><label for="cp_title"><?php _e('Title: ', 'collabpress'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
			<td><input name="cp_title" type="text" id="cp_title" value="<?php echo stripslashes($edit_task->title); ?>" aria-required="true" /></td>
			</tr>

			<tr class="form-field">
			<th scope="row"><label for="cp_details"><?php _e('Details: ', 'collabpress'); ?></label></th>
			<td><textarea name="cp_details" id="cp_details" rows="10" cols="20" /><?php echo stripslashes($edit_task->details); ?></textarea></td>
			</tr>

			<tr class="form-field">
			<th scope="row"><label for="cp_users"><?php _e('Assign to: ', 'collabpress'); ?></label></th>
			<td><?php wp_dropdown_users('selected=' . $edit_task->users); ?><td>
			</tr>
		
			<?php
		
			$edit_task_due_date = explode("-", $edit_task->due_date);
		
			?>

			<tr class="form-field">
			<th scope="row"><label for="cp_tasks_due"><?php _e('Due: ', 'collabpress'); ?></label></th>
			<td>
			<?php
				$months = array (1 => 'January', 'February', 'March', 'April', 'May', 'June','July', 'August', 'September', 'October', 'November', 'December');
				$days = range (1, 31);
				$years = range (date('Y'), 2025);

				// Month
				echo __('Month', 'collabpress') . ": <select name='cp_tasks_due_month'>";
				$cp_month_count = 1;
				foreach ($months as $value) {

					if ($cp_month_count == $edit_task_due_date[0]) {
						$month_selected = "SELECTED";
					} else {
						$month_selected = '';
					}

					echo '<option ' . $month_selected . ' value="'.$cp_month_count.'">'.$value.'</option>\n';
					$cp_month_count++;
				} echo '</select>';

				// Day
				echo __('Day', 'collabpress') . ": <select name='cp_tasks_due_day'>";
				foreach ($days as $value) {

					if ($value == $edit_task_due_date[1]) {
						$day_selected = "SELECTED";
					} else {
						$day_selected = '';
					}

					echo '<option ' . $day_selected . ' value="'.$value.'">'.$value.'</option>\n';
				} echo '</select>';


				// Year
				echo __('Year', 'collabpress') . ": <select name='cp_tasks_due_year'>";
				foreach ($years as $value) {

					if ($value == $edit_task_due_date[2]) {
						$year_selected = "SELECTED";
					} else {
						$year_selected = '';
					}

					echo '<option ' . $year_selected . ' value="'.$value.'">'.$value.'</option>\n';
				} 
				echo '</select>';
			?>
			</td>
			</tr>

			</table>

			<input type="hidden" name="cp_edit_task_id" value="<?php echo esc_html($_GET['task']); ?>">
			<input type="hidden" name="cp_add_tasks_project" value="<?php echo $edit_task->proj_id; ?>">
			<input type="hidden" name="page_options" value="user, cp_title, cp_details, cp_users, cp_tasks_due_month, cp_tasks_due_day, cp_tasks_due_year, cp_add_tasks_project" />

			<p>
			<input type="submit" class="button-primary" name="cp_edit_task_button" value="<?php _e('Edit Task', 'collabpress') ?>" />
			</p>

			</form>
		<?php
		
		} else {
			
			echo "<p>" . __("Task doesn't exist...", "collabpress") . "</p>";
			
		}
		
	}
	
	function cp_projects_oncontentbox_additional_2_content($data) {
		?>
			<p class="cp_about"><a target="_blank" href="http://webdevstudios.com/support/forum/collabpress/">CollabPress</a> v<?php echo CP_VERSION; ?> - <?php _e( 'Copyright', 'collabpress' ) ?> &copy; 2010 - <a href="http://webdevstudios.com/support/forum/collabpress/" target="_blank">Please Report Bugs</a> &middot; Follow us on Twitter: <a href="http://twitter.com/scottbasgaard" target="_blank">Scott</a> &middot; <a href="http://twitter.com/williamsba" target="_blank">Brad</a> &middot; <a href="http://twitter.com/webdevstudios" target="_blank">WDS</a></p>
		<?php
	}
	
}

?>