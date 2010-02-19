<?php

	add_filter('ozh_adminmenu_menu', array(&$this, 'ozh_adminmenu_hack') );
	add_filter('ozh_adminmenu_altmenu', array(&$this, 'ozh_altmenu_hack') );
	
	function ozh_altmenu_hack($altmenu) {
		// not sure why ozh adds extra page argument to these URLs:
		$bad_string = '?page=' . get_option('siteurl') . 'p-admin/admin.php?page=';
		
		foreach ( array_keys($altmenu) as $key )
			if ( isset($altmenu[$key]['url']) && strpos( $altmenu[$key]['url'], $bad_string ) )
				$altmenu[$key]['url'] = str_replace( $bad_string, '?page=', $altmenu[$key]['url'] );

		return $altmenu;
	}
	
	// this is only applicable for WP < 2.8
	function ozh_adminmenu_hack($menu) {
		if ( current_user_can('edit_posts') ) {
			$menu[5][0] = __awp("Write");
			$menu[5][1] = "edit_posts";
			$menu[5][2] = "post-new.php";
			$menu[10][0] = __awp("Manage");
			$menu[10][1] = "edit_posts";
			$menu[10][2] = "edit.php";
		} else {
			$menu[5][0] = __awp("Write");
			$menu[5][1] = "edit_pages";
			$menu[5][2] = ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/edit-pages.php') ) ? "post-new.php" : "page-new.php";  //don't ask.
			$menu[10][0] = __awp("Manage");
			$menu[10][1] = "edit_pages";
			$menu[10][2] = "edit-pages.php";
		}

		return $menu;
	}

?>