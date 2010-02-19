<?php

class ScoperRoleStrings {

	function get_display_name( $role_handle, $context = '' ) {
		switch( $role_handle ) {
			case 'rs_post_reader' :
				$str = __('Post Reader', 'scoper');
				break;
			case 'rs_private_post_reader' :
				// We want the object-assigned reading role to enable the user/group regardless of post status setting.
				// But we don't want the caption to imply that assigning this object role MAKES the post_status private
				// Also want the "role from other scope" indication in post edit UI to reflect the post's current status
				$str = ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) ? __('Post Reader', 'scoper') : __('Private Post Reader', 'scoper');
				break;
			case 'rs_post_contributor' :
				$str = __('Post Contributor', 'scoper');
				break;
			case 'rs_post_author' :
				$str = __('Post Author', 'scoper');
				break;
			case 'rs_post_revisor' :
				$str = __('Post Revisor', 'scoper');
				break;
			case 'rs_post_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					$str = __('Post Publisher', 'scoper');
				else
					$str = __('Post Editor', 'scoper');
				break;
			case 'rs_page_reader' :
				$str = __('Page Reader', 'scoper');
				break;
			case 'rs_private_page_reader' :
				$str = ( OBJECT_UI_RS == $context ) ? __('Page Reader', 'scoper') : __('Private Page Reader', 'scoper');
				break;
			case 'rs_page_associate' :
				$str = __('Page Associate', 'scoper');
				break;
			case 'rs_page_contributor' :
				$str = __('Page Contributor', 'scoper');
				break;
			case 'rs_page_author' :
				$str = __('Page Author', 'scoper');
				break;
			case 'rs_page_revisor' :
				$str = __('Page Revisor', 'scoper');
				break;
			case 'rs_page_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					$str = __('Page Publisher', 'scoper');
				else
					$str = __('Page Editor', 'scoper');
				break;
			case 'rs_link_editor' :
				$str = __('Link Admin', 'scoper');
				break;
			case 'rs_category_manager' :
				$str = __('Category Manager', 'scoper');
				break;
			case 'rs_group_manager' :
				$str = __('Group Manager', 'scoper');
				break;
			default :
				$str = '';
		} // end switch
		
		return apply_filters( 'role_display_name_rs', $str, $role_handle );			
	}
	
	function get_abbrev( $role_handle, $context = '' ) {
		switch( $role_handle ) {
			case 'rs_post_reader' :
			case 'rs_page_reader' :
				$str = __('Readers', 'scoper');
				break;
			case 'rs_private_post_reader' :
			case 'rs_private_page_reader' :
				$str = ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) ? __('Readers', 'scoper') : __('Private Readers', 'scoper');
				break;
			case 'rs_post_contributor' :
			case 'rs_page_contributor' :
				$str = __('Contributors', 'scoper');
				break;
			case 'rs_post_author' :
			case 'rs_page_author' :
				$str = __('Authors', 'scoper');
				break;
			case 'rs_post_revisor' :
			case 'rs_page_revisor' :
				$str = __('Revisors', 'scoper');
				break;
			case 'rs_post_editor' :
			case 'rs_page_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					$str = __('Publishers', 'scoper');
				else
					$str = __('Editors', 'scoper');
				break;
			case 'rs_page_associate' :
				$str = __('Associates', 'scoper');
				break;
			
			case 'rs_link_editor' :
				$str = __('Admins', 'scoper');
				break;
			case 'rs_category_manager' :
			case 'rs_group_manager' :
				$str = __('Managers', 'scoper');
				break;
			default :
				$str = '';
		} // end switch
		
		return apply_filters( 'role_abbrev_rs', $str, $role_handle );
	}
	
	function get_micro_abbrev( $role_handle, $context = '' ) {
		switch( $role_handle ) {
			case 'rs_post_reader' :
			case 'rs_page_reader' :
				$str = __('Reader', 'scoper');
				break;
			case 'rs_private_post_reader' :
			case 'rs_private_page_reader' :
				$str = ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) ? __('Reader', 'scoper') : __('Pvt Reader', 'scoper');
				break;
			case 'rs_post_contributor' :
			case 'rs_page_contributor' :
				$str = __('Contrib', 'scoper');
				break;
			case 'rs_post_author' :
			case 'rs_page_author' :
				$str = __('Author', 'scoper');
				break;
			case 'rs_post_revisor' :
			case 'rs_page_revisor' :
				$str = __('Revisor', 'scoper');
				break;
			case 'rs_post_editor' :
			case 'rs_page_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					$str = __('Publisher', 'scoper');
				else
					$str = __('Editor', 'scoper');
				break;
			case 'rs_page_associate' :
				$str = __('Assoc', 'scoper');
				break;
			
			case 'rs_link_editor' :
				$str = __('Admin', 'scoper');
				break;
			case 'rs_category_manager' :
			case 'rs_group_manager' :
				$str = __('Manager', 'scoper');
				break;
			default :
				$str = '';
		} // end switch
		
		return apply_filters( 'role_micro_abbrev_rs', $str, $role_handle );
	}
} // end class
?>