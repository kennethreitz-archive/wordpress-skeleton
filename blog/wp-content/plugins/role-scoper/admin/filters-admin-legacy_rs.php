<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperAdminFilters_Legacy {

	// remove "Write Post" / "Write Page" menu items if user only has role for certain existing objects
	function act_admin_menu_26() {
		global $scoper, $menu, $submenu;

		if ( isset( $submenu['post-new.php'] ) ) {
			foreach ( $submenu['post-new.php'] as $key => $menu_array ) {
				if ( isset($menu_array[1]) && is_string($menu_array[1]) ) {
					$scoper->ignore_object_roles = true;
					
					if ( ! current_user_can( $menu_array[1] ) ) {
						// remove this "Write" sublink if the user can't do it
						unset( $submenu['post-new.php'][$key] );
						
						// Don't allow WP to default the Write link to page-new.php if page creation is disabled for this user
						if ( isset($menu_array[2]) && ( 'page-new.php' == $menu_array[2] ) && ( 'page-new.php' == $menu_array[2] ) ) {
							if ( isset($menu[5][2]) && ( $menu[5][2] == 'page-new.php' ) )
								$menu[5] = array(__awp('Write'), 'edit_posts', 'post-new.php');
						}
					}
					
					$scoper->ignore_object_roles = false;
				}
			}
			
			if ( empty( $submenu['post-new.php'] ) ) {
				global $menu;
				
				unset ( $menu[5] );
				unset ( $submenu['post-new.php'] );
			}
		}
		
		// default Manage menu to Categories if currently in Category Roles / Restrictions
		$uri = $_SERVER['SCRIPT_NAME'];
		if ( strpos($uri, 'role-scoper/admin/restrictions/category') || strpos($uri, 'role-scoper/admin/roles/category') )
			$menu[10][2] = 'categories.php';
		
		elseif ( strpos($uri, 'role-scoper/admin/restrictions/post/page') || strpos($uri, 'role-scoper/admin/roles/post/page') ) {
			$menu[5][2] = 'page-new.php';
			$menu[10][2] = 'edit-pages.php';
		
		} elseif ( strpos($uri, 'role-scoper/admin/restrictions/link_category') || strpos($uri, 'role-scoper/admin/roles/link_category') )
			$menu[10][2] = 'edit-link-categories.php';
	}

} // end class
?>