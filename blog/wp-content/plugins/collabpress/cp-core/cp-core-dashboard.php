<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Define page name
define('CP_DASHBOARD_METABOX_PAGE_NAME', 'cp-dashboard-page');

// Create our class
class cp_core_dashboard {

	// Constructor
	function cp_core_dashboard() {
		
		// Add filter for WP 2.8 box system
		add_filter('screen_layout_columns', array(&$this, 'cp_onscreen_layout_columns'), 10, 2);
		
		// Register callback
		add_action('admin_menu', array(&$this, 'cp_onadmin_menu'));
		
		// Register the callback been used if options of page been submitted and needs to be processed
		add_action('admin_post_save_cp_dashboard_metaboxes_general', array(&$this, 'cp_onsave_changes'));
		
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
		
		// Add our own options page
		$this->pagehook = add_menu_page('CollabPress - Project, Collaboration and Task Management', "CollabPress", CP_MINIMUM_USER, CP_DASHBOARD_METABOX_PAGE_NAME, array(&$this, 'cp_onshow_page'));
		
		add_submenu_page( CP_DASHBOARD_METABOX_PAGE_NAME, 'CollabPress - Project, Collaboration and Task Management', "Dashboard", CP_MINIMUM_USER, CP_DASHBOARD_METABOX_PAGE_NAME, array(&$this, 'cp_onshow_page'));
		
		// Register callback gets call prior your own page gets rendered
		add_action('load-'.$this->pagehook, array(&$this, 'cp_onload_page'));
		
	}
	
	// Will be executed if wordpress core detects this page has to be rendered
	function cp_onload_page() {
		
		// Ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');

		// Add several metaboxes now, all metaboxes registered during load page can be switched off/on at "Screen Options" automatically, nothing special to do therefore
		add_meta_box('cp-dashboard-metaboxes-sidebox-1', __( 'Calendar', 'collabpress' ), array(&$this, 'cp_onsidebox_1_content'), $this->pagehook, 'side', 'core');
		add_meta_box('cp-dashboard-metaboxes-sidebox-2', __( 'Projects', 'collabpress' ), array(&$this, 'cp_onsidebox_2_content'), $this->pagehook, 'side', 'core');
		add_meta_box('cp-dashboard-metaboxes-sidebox-3', __( 'Users', 'collabpress' ), array(&$this, 'cp_onsidebox_3_content'), $this->pagehook, 'side', 'core');
		
		// Toggle dashboard view
		if ($_GET['view'] == 'allactivity') {
			
			add_meta_box('cp-dashboard-metaboxes-contentbox-3', __( 'Activity', 'collabpress' ), array(&$this, 'cp_oncontentbox_3_content'), $this->pagehook, 'normal', 'core');
			
		} else if ($_GET['view'] == 'allmytasks') {
			
			add_meta_box('cp-dashboard-metaboxes-contentbox-4', __( 'My Tasks', 'collabpress' ), array(&$this, 'cp_oncontentbox_4_content'), $this->pagehook, 'normal', 'core');
			
			} else if ($_GET['view'] == 'alltasks') {
				
				add_meta_box('cp-dashboard-metaboxes-contentbox-5', __( 'Tasks', 'collabpress' ), array(&$this, 'cp_oncontentbox_5_content'), $this->pagehook, 'normal', 'core');
				
				} else if ($_GET['view'] == 'userpage') {
					
					$user_info = get_userdata($_GET['user']);

					add_meta_box('cp-dashboard-metaboxes-contentbox-6', 'Tasks for ' . $user_info->user_nicename, array(&$this, 'cp_oncontentbox_6_content'), $this->pagehook, 'normal', 'core');
					
					} else {
			
					add_meta_box('cp-dashboard-metaboxes-contentbox-1', __( 'Recent Activity', 'collabpress' ), array(&$this, 'cp_oncontentbox_1_content'), $this->pagehook, 'normal', 'core');
					add_meta_box('cp-dashboard-metaboxes-contentbox-2', __( 'My Tasks', 'collabpress' ), array(&$this, 'cp_oncontentbox_2_content'), $this->pagehook, 'normal', 'core');
					add_meta_box('cp-dashboard-metaboxes-contentbox-additional-2', __( 'About', 'collabpress' ), array(&$this, 'cp_oncontentbox_additional_2_content'), $this->pagehook, 'additional', 'core');
			
		}
		
	}
	
	// Executed to show the plugins complete admin page
	function cp_onshow_page() {
		
		
		// We need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		
		// Check if there are any projects
		if (!check_cp_project()) {
			?>
			<div class="updated">
				<p><strong><?php _e( 'Welcome to CollabPress. To get started create your first <a href="admin.php?page=cp-projects-page">project</a>.', 'collabpress' ); ?></strong></p>
			</div>
			<?php
		}
		
		require ( CP_PLUGIN_DIR . '/cp-core/cp-core-isset.php' );
		
		// Define some data can be given to each metabox during rendering
		$data = array();
		
		?>

		<div id="cp-dashboard-metaboxes-general" class="wrap">
		
		<?php // screen_icon('options-general'); ?>
		
		<p><h2>CollabPress<?php if ($_GET['view']) { echo ' - <a href="admin.php?page=cp-dashboard-page">'.__('Back', 'collabpress').'</a>';}?></h2></p>
		
		<form action="admin-post.php" method="post">
			<?php wp_nonce_field('cp-dashboard-metaboxes-general'); ?>
			<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
			<input type="hidden" name="action" value="save_cp_dashboard_metaboxes_general" />
			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>
				</div>
				
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes($this->pagehook, 'normal', $data); ?>
						<?php do_meta_boxes($this->pagehook, 'additional', $data); ?>
						<p style="display:none">
							<input type="submit" value="<?php _e( 'Save Changes', 'collabpress' ) ?>" class="button-primary" name="Submit"/>	
						</p>
					</div>
				</div>
				
				<br class="clear"/>
								
			</div>	
		</form>
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
		check_admin_referer('cp-dashboard-metaboxes-general');
		
		// Process here your on $_POST validation and / or option saving
		
		// Lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect($_POST['_wp_http_referer']);		
	}

	// Below you will find for each registered metabox the callback method, that produces the content inside the boxes
	function cp_onsidebox_1_content($data) {
		?><center><?php
		$time = time();
    	echo cp_generate_small_calendar(date('Y', $time), date('n', $time));
		echo '<p><a style="text-decoration:none; color:#D54E21" href="#">' . __('Coming Soon', 'collabpress') . '</a></p>';		
    	?></center><?php
	}
	
	function cp_onsidebox_2_content($data) {
		list_cp_projects();
		echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-projects-page">' . __('Add New', 'collabpress') . '</a></p>';	
	}
	
	function cp_onsidebox_3_content($data) {
		list_cp_users();
	}
	
	function cp_oncontentbox_1_content($data) {
		list_cp_activity();
	}
	
	function cp_oncontentbox_2_content($data) {
		list_cp_my_tasks(NULL, CP_DASHBOARD_METABOX_PAGE_NAME);
	}
	
	function cp_oncontentbox_3_content($data) {
		list_cp_activity($view_more = 1);
	}
	
	function cp_oncontentbox_4_content($data) {
		list_cp_my_tasks(NULL, CP_DASHBOARD_METABOX_PAGE_NAME);
	}
	
	function cp_oncontentbox_5_content($data) {
		list_cp_tasks(NULL, CP_DASHBOARD_METABOX_PAGE_NAME);
	}
	
	function cp_oncontentbox_6_content($data) {
		list_cp_users_tasks($_GET['user'], CP_DASHBOARD_METABOX_PAGE_NAME);
	}
	
	function cp_oncontentbox_additional_2_content($data) {
		?>
			<p class="cp_about"><a target="_blank" href="http://webdevstudios.com/support/forum/collabpress/">CollabPress</a> v<?php echo CP_VERSION; ?> - <?php _e( 'Copyright', 'collabpress' ) ?> &copy; 2010 - <a href="http://webdevstudios.com/support/forum/collabpress/" target="_blank">Please Report Bugs</a> &middot; Follow us on Twitter: <a href="http://twitter.com/scottbasgaard" target="_blank">Scott</a> &middot; <a href="http://twitter.com/williamsba" target="_blank">Brad</a> &middot; <a href="http://twitter.com/webdevstudios" target="_blank">WDS</a></p>
		<?php
	}
	
}

?>