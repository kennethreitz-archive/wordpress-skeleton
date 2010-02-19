<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( 'admin_lib_rs.php' );

/**
 * ScoperAdminFiltersUI PHP class for the WordPress plugin Role Scoper
 * filters-admin-ui_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
class ScoperAdminFiltersUI
{
	var $scoper;
	var $item_filters;
	
	function ScoperAdminFiltersUI() {
		global $scoper;
		$this->scoper =& $scoper;
		
		$current_script = $_SERVER['SCRIPT_NAME'];
		$item_edit_scripts = apply_filters( 'item_edit_scripts_rs', array('p-admin/post-new.php', 'p-admin/post.php', 'p-admin/page.php', 'p-admin/page-new.php', 'p-admin/categories.php') );
		$item_edit_scripts []= 'p-admin/admin-ajax.php';

		foreach( $item_edit_scripts as $edit_script ) {
			if ( strpos( $current_script, $edit_script ) ) {
				require_once( 'filters-admin-ui-item_rs.php' );
				$scoper->filters_admin_item_ui = new ScoperAdminFiltersItemUI();
				break;
			}
		}

		if ( awp_ver('2.7-dev') )
			add_action( '_admin_menu', array(&$this, 'act_admin_menu') );
		else {
			require_once( 'filters-admin-legacy_rs.php' );
			add_action( '_admin_menu', array('ScoperAdminFilters_Legacy', 'act_admin_menu_26') );
		}
		
		add_action('admin_head', array(&$this, 'ui_hide_admin_divs') );

		if ( is_user_administrator_rs() || strpos($_SERVER['REQUEST_URI'], 'admin.php?page=rs-') )
			add_action('in_admin_footer', array(&$this, 'ui_admin_footer') );

		if ( GROUP_ROLES_RS ) {
			add_action('show_user_profile', array(&$this, 'ui_user_groups'), 2);
			add_action('edit_user_profile', array(&$this, 'ui_user_groups'), 2);
			add_action('edit_group_profile_rs', array(&$this, 'ui_group_roles'), 10);
		}
		
		add_action('show_user_profile', array(&$this, 'ui_user_roles'), 2);
		add_action('edit_user_profile', array(&$this, 'ui_user_roles'), 2);
		
		$script_name = $_SERVER['SCRIPT_NAME'];
		
		if ( strpos($_SERVER['SCRIPT_NAME'], 'role-management.php') && empty($_POST) )
			add_filter( 'capabilities_list', array(&$this, 'flt_capabilities_list') );	// capabilities_list is a Role Manager hook
		
		//temporary solution to alert user of filtering performed on saved page - see corresponding code in hardway-admin
		if ( strpos($script_name, 'p-admin/page.php') || strpos($script_name, 'p-admin/page-new.php') || strpos($script_name, 'p-admin/edit-pages.php') ) {
			global $current_user;
			if ( $notice = get_option("scoper_notice_{$current_user->ID}") ) {
				delete_option( "scoper_notice_{$current_user->ID}" );
				rs_notice($notice);
			}
		}
	} // end class constructor
	
	// remove "Write Post" / "Write Page" menu items if user only has role for certain existing objects
	function act_admin_menu() {
		global $submenu;

		if ( isset($submenu['edit.php'][10]) ) {
			$this->scoper->ignore_object_roles = true;
			if ( ! current_user_can( 'edit_posts' ) )
				unset( $submenu['edit.php'][10] );

			$this->scoper->ignore_object_roles = false;
		}
		
		if ( isset($submenu['edit-pages.php'][10]) ) {
			$this->scoper->ignore_object_roles = true;
			if ( ! current_user_can( 'edit_pages' ) )
				unset( $submenu['edit-pages.php'][10] );

			$this->scoper->ignore_object_roles = false;
		}
		
		if ( isset($submenu['link-manager.php'][10]) ) {
			$this->scoper->ignore_object_roles = true;
			if ( ! current_user_can( 'manage_links' ) )
				unset( $submenu['link-manager.php'][10] );

			$this->scoper->ignore_object_roles = false;
		}
	}
	
	function ui_hide_admin_divs() {
		// Determine data source based on URI
		// However, this will only consider URIs included in Scoped_Data_Source->users_where_reqd_caps
		if ( ! ( $context = $this->scoper->admin->get_context() ) || empty($context->source ) || empty($context->object_type_def ) )
			return;

		$src_name = $context->source->name;
		$object_type = $context->object_type_def->name;
		
		
		// For this data source, is there any html content to hide from non-administrators?
		$css_ids = scoper_get_otype_option('admin_css_ids', $src_name, $object_type);
		$css_ids = str_replace(' ', '', $css_ids);
		$css_ids = str_replace(',', ';', $css_ids);
		$css_ids = explode(';', $css_ids);	// option storage is as semicolon-delimited string
	
		if ( empty($css_ids) )
			return;

		$object_id = $this->scoper->data_sources->detect('id', $src_name, '', $object_type);

		$can_edit_blogwide = $this->scoper->admin->user_can_edit_blogwide($src_name, $object_type);
		
		$blogwide_requirement = scoper_get_option('hide_non_editor_admin_divs');
		
		if ( 'admin_option' == $blogwide_requirement )
			$blogwide_requirement_met = is_option_administrator_rs();
		
		elseif ( 'admin_user' == $blogwide_requirement )
			$blogwide_requirement_met = is_user_administrator_rs();
			
		elseif ( 'admin_content' == $blogwide_requirement )
			$blogwide_requirement_met = is_content_administrator_rs();
			
		elseif ( 'editor' == $blogwide_requirement )
			$blogwide_requirement_met = $this->scoper->admin->user_can_edit_blogwide($src_name, $object_type, array( 'status' => 'published', 'require_others_cap' => true ) );
		
		elseif ( 'author' == $blogwide_requirement )
			$blogwide_requirement_met = $this->scoper->admin->user_can_edit_blogwide($src_name, $object_type, array( 'status' => 'published' ) );
		
		elseif ( $blogwide_requirement )
			$blogwide_requirement_met = $can_edit_blogwide;
		else
			$blogwide_requirement_met = true;
			
		if ( $can_edit_blogwide && $blogwide_requirement_met ) {
			// don't hide anything if a user with sufficient blog-wide role is creating a new object
			if ( ! $object_id )
				return;

			if ( ! $object = $this->scoper->data_sources->get_object($src_name, $object_id) )
				return;

			if ( ('post' == $src_name ) && empty($object->post_date) ) // don't prevent the full editing of new posts/pages
				return;

			// don't hide anything if a user with sufficient blog-wide role is editing their own object
			if ( ! empty($col_owner) ) {
				global $current_user;
				if ( empty($object->$col_owner) || ( $object->$col_owner == $current_user->ID) )
					return;
			}
		}

		
		if ( ( $blogwide_requirement && ! $blogwide_requirement_met ) || ! $this->scoper->admin->user_can_admin_object($src_name, $object_type, $object_id, '', '', true) ) {

			if ( ! awp_ver('2.7-dev') )	// side-info with related posts, links, etc.
				echo "\n<!-- " . __('Role Scoper Plugin CSS:') . " -->\n<style type='text/css'>\n<!--\n.side-info { display: none !important; }\n-->\n</style>\n";	

			echo( "\n<style type='text/css'>\n<!--\n" );
			
			$removeable_metaboxes = apply_filters( 'scoper_removeable_metaboxes', array( 'categorydiv', 'tagsdiv-post_tag', 'postcustom', 'pagecustomdiv', 'authordiv', 'pageauthordiv', 'trackbacksdiv', 'revisionsdiv', 'pending_revisions_div', 'future_revisionsdiv' ) );
			
			foreach ( $css_ids as $id ) {
				if ( in_array( $id, $removeable_metaboxes ) ) {
					// thanks to piemanek for tip on using remove_meta_box for any core admin div
					remove_meta_box($id, $object_type, 'normal');
					remove_meta_box($id, $object_type, 'advanced');
				} else {
					// hide via CSS if the element is not a removeable metabox
					echo "#$id { display: none !important; }\n";  // this line adapted from Clutter Free plugin by Mark Jaquith
				}
			}
			
			echo "-->\n</style>\n";
		}
		
	}

	function ui_admin_footer() {
		echo '<span style="float:right; margin-left: 2em"><a href="http://agapetry.net/">' . __('Role Scoper', 'scoper') . '</a> ' . SCOPER_VERSION . ' | ' . '<a href="http://agapetry.net/forum/">' . __('Support Forum', 'scoper') . '</a>&nbsp;</span>';
	}
	
	function ui_user_groups() {
		if ( ! is_user_administrator_rs() && ! scoper_get_option( 'display_user_profile_groups' ) )
			return;
			
		include_once('profile_ui_rs.php');
		ScoperProfileUI::display_ui_user_groups();
	} // end function act_edit_user_groups
	

	function ui_group_roles($group_id) {
		if ( ! $group_id )
			return;

		include_once('profile_ui_rs.php');
		ScoperProfileUI::display_ui_group_roles($group_id);
	}
	
	function ui_user_roles() {
		if ( ! is_user_administrator_rs() && ! scoper_get_option( 'display_user_profile_roles' ) )
			return;
		
		global $profileuser, $current_user;

		$profile_user_rs = ( $profileuser->ID == $current_user->ID ) ? $current_user : new WP_Scoped_User($profileuser->ID);
		
		include_once('profile_ui_rs.php');
		ScoperProfileUI::display_ui_user_roles($profile_user_rs);
	}
	
	function flt_capabilities_list($unused) {
		echo '<p>';
		_e("<strong>Note:</strong> Role Manager settings determine each user's default blog-wide capabilities. Since the Role Scoper plugin is also enabled, <strong>Term-specific or Object-specific Role Assignments</strong> may increase or decrease a user's actual capabilities.", 'scoper');
		echo '</p>';

		return $unused;
	}
}

?>