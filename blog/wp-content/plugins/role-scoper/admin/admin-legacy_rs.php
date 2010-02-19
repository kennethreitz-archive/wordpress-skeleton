<?php

function scoper_set_legacy_menu_links( &$roles_link, &$restrictions_link, $uri, $can_admin_terms, $can_admin_objects ) {	
	if ( strpos($uri, '-restrictions') && strpos($uri, 'page=rs-') )
		$roles_link = str_replace('-restrictions', '-roles', $uri);
	
	elseif ( strpos($uri, '-roles') && strpos($uri, 'page=rs-') )
		$restrictions_link = str_replace('-roles', '-restrictions', $uri);

	elseif ( ! empty($can_admin_objects['post']['post']) && ( strpos($uri, 'p-admin/edit.php') || strpos($uri, 'p-admin/post.php') || strpos($uri, 'p-admin/post-new.php') ) ) {
		$roles_link = 'admin.php?page=rs-post-roles';
		$restrictions_link = 'admin.php?page=rs-post-restrictions';
	
	} elseif ( ! empty($can_admin_objects['post']['page']) && ( strpos($uri, 'p-admin/edit-pages.php') || strpos($uri, 'p-admin/page.php') || strpos($uri, 'p-admin/page-new.php') ) ) {
		$roles_link = 'admin.php?page=rs-page-roles';
		$restrictions_link = 'admin.php?page=rs-page-restrictions';
	
	} elseif ( ! empty($can_admin_terms['category']) && strpos($uri, 'p-admin/categories.php') ) {
		$roles_link = 'admin.php?page=rs-category-roles';
		$restrictions_link = 'admin.php?page=rs-category-restrictions';
	
	} elseif ( ! empty($can_admin_terms['link_category']) && strpos($uri, 'p-admin/edit-link-categories.php') ) {
		$roles_link = 'admin.php?page=rs-link_category-roles';
		$restrictions_link = 'admin.php?page=rs-link_category-restrictions';
	}
}

?>