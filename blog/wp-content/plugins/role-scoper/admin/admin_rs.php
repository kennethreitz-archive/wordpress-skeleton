<?php
// menu icons by Jonas Rask: http://www.jonasraskdesign.com/
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
$wp_content = ( is_ssl() || ( is_admin() && defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ) ) ? str_replace( 'http:', 'https:', WP_CONTENT_URL ) : WP_CONTENT_URL;
define ('SCOPER_URLPATH', $wp_content . '/plugins/' . SCOPER_FOLDER);

define ('ROLE_ASSIGNMENT_RS', 'role_assignment');
define ('ROLE_RESTRICTION_RS', 'role_restriction');

define ('REMOVE_ASSIGNMENT_RS', '');
define ('ASSIGN_FOR_ENTITY_RS', 'entity');
define ('ASSIGN_FOR_CHILDREN_RS', 'children');
define ('ASSIGN_FOR_BOTH_RS', 'both');

define( 'OBJECT_UI_RS', 'object_ui' );
	

require_once( 'admin_lib_rs.php' );

require_once( 'admin_ui_lib_rs.php' );

if ( IS_MU_RS )
	require_once( 'admin_lib-mu_rs.php' );

if ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/index.php' ) && ! defined( 'USE_RVY_RIGHTNOW' )  )
	include_once( 'admin-dashboard_rs.php' );

	
class ScoperAdmin
{
	var $role_assigner;	//object reference
	var $tinymce_readonly;
	
	function ScoperAdmin() {
		add_action('admin_head', array(&$this, 'admin_head_base'));

		if ( ! defined('DISABLE_QUERYFILTERS_RS') || is_content_administrator_rs() ) {
			add_action('admin_head', array(&$this, 'admin_head'));
			
			if ( ! defined('XMLRPC_REQUEST') && ! strpos($_SERVER['SCRIPT_NAME'], 'p-admin/async-upload.php' ) ) {
				add_action('admin_menu', array(&$this,'build_menu'));
				
				if ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php') ) {
					if ( awp_ver( '2.8' ) )
						add_filter( 'plugin_row_meta', array(&$this, 'flt_plugin_action_links'), 10, 2 );
					else
						add_filter( 'plugin_action_links', array(&$this, 'flt_plugin_action_links'), 10, 2 );
				}
						
				if ( awp_is_plugin_active( 'ozh-admin-drop-down-menu' ) && ! awp_ver( '2.8' ) ) // direct-hacked menu element IDs in the ozh filters are not applicable for WP versions >= 2.8
					include_once( 'ozh_helper_rs.php' );
			}
		}

		if ( ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/categories.php') || ( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'p-admin/categories.php') ) ) && awp_is_plugin_active('subscribe2') )
			require_once('subscribe2_helper_rs.php');
			
		if ( defined( 'FLUTTER_NAME' ) )
			require_once('flutter_helper_rs.php');
	}

	// adds an Options link next to Deactivate, Edit in Plugins listing
	function flt_plugin_action_links($links, $file) {
		if ( $file == SCOPER_BASENAME ) {
			if ( awp_ver('2.8') )
				$links[] = "<a href='http://agapetry.net/forum/'>" . __awp('Support Forum') . "</a>";
			
			$page = ( IS_MU_RS ) ? 'rs-site_options' : 'rs-options';
			$links[] = "<a href='admin.php?page=$page'>" . __awp('Options') . "</a>";
		}
	
		return $links;
	}
	
	function admin_head_base() {		
		if ( isset( $_POST['rs_defaults'] ) ) {
			// User asked to restore default options, so restore htaccess rule for attachment filtering (if it's not disabled)
			scoper_flush_site_rules();
			scoper_expire_file_rules();
		}
	}
	
	function admin_head() {
		global $scoper;
		
		echo '<link rel="stylesheet" href="' . SCOPER_URLPATH . '/admin/role-scoper.css" type="text/css" />'."\n";

		if ( false !== strpos(urldecode($_SERVER['REQUEST_URI']), 'page=rs-options') ) {
			if ( scoper_get_option('version_update_notice') ) {
				require_once('misc/version_notice_rs.php');
				scoper_new_version_notice();
			}

		} elseif ( false !== strpos(urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rs-about') ) {
			echo '<link rel="stylesheet" href="' . SCOPER_URLPATH . '/admin/about/about.css" type="text/css" />'."\n";
		}
				
		// dynamically set checkbox titles for user/group object role selection
		if ( isset($_GET['src_name']) && isset($_GET['object_type']) ) {
			$src_name = $_GET['src_name'];
			$object_type = $_GET['object_type'];
			$src = $scoper->data_sources->get($src_name);
			$otype_def = $scoper->data_sources->member_property($src_name, 'object_types', $object_type);
		} else {
			$context = $this->get_context();
			if ( ! empty($context->source) && ! empty($context->object_type_def) ) {
				$src = $context->source;
				$otype_def = $context->object_type_def;
			}
		}
		
		if ( ! empty($src) && ! empty($src->cols->parent) && ! empty($otype_def->ignore_object_hierarchy) ) {
			$obj_title = sprintf( __('assign role for this %s', 'scoper'), strtolower($otype_def->display_name) );
			$child_title = sprintf( __('assign role for sub-%s', 'scoper'), strtolower($otype_def->display_name_plural) );
		
			$js_params = "var role_for_object_title = '$obj_title';"
					. "var role_for_children_title = '$child_title';";
	
			// TODO: replace some of this JS with equivalent JQuery
			echo "\n" . '<script type="text/javascript">' . $js_params . '</script>';
			echo "\n" . "<script type='text/javascript' src='" . SCOPER_URLPATH . "/admin/rs-objrole-cbox-maint.js'></script>";
		}

		add_filter( 'contextual_help_list', array(&$this, 'flt_contextual_help_list'), 10, 2 );
		
		if( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rs-' ) 
		&& false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'roles' ) ) {
			
			// add Ajax goodies we need for role duration/content date limit editing Bulk Role Admin
			wp_print_scripts( array( 'page' ) );
			
			require_once( 'admin_lib-bulk_rs.php' );
			ScoperAdminBulkLib::date_limits_js();
		}
		
		// TODO: replace some of this JS with equivalent JQuery
		echo "\n" . "<script type='text/javascript' src='" . SCOPER_URLPATH . "/admin/agapetry.js'></script>";
		echo "\n" . "<script type='text/javascript' src='" . SCOPER_URLPATH . "/admin/role-scoper.js'></script>";
	}
	
	function flt_contextual_help_list ($help, $screen) {
		$link_section = '';
		
		// WP < 3.0 passes ID as string
		if ( is_object($screen) )
			$screen = $screen->id;
		
		if ( strpos( $screen, 'rs-' ) ) {
			$match = array();
			if ( ! preg_match( "/admin_page_rs-[^@]*-*/", $screen, $match ) )
				if ( ! preg_match( "/_page_rs-[^@]*-*/", $screen, $match ) )
					preg_match( "/rs-[^@]*-*/", $screen, $match );

			if ( $match )
				if ( $pos = strpos( $match[0], 'rs-' ) ) {
					$link_section = substr( $match[0], $pos + strlen('rs-') );
					$link_section = str_replace( '_t', '', $link_section );	
				}
					
		} elseif ( ('post' == $screen) || ('page' == $screen) ) {
			$link_section = $screen;
		}

		if ( $link_section ) {
			$link_section = str_replace( '.php', '', $link_section);
			$link_section = str_replace( '/', '~', $link_section);
			
			if ( ! isset($help[$screen]) )
				$help[$screen] = '';
			
			$help[$screen] .= ' ' . sprintf(__('%1$s Role Scoper Documentation%2$s', 'scoper'), "<a href='http://agapetry.net/downloads/RoleScoper_UsageGuide.htm#$link_section' target='_blank'>", '</a>')
			. ', ' . sprintf(__('%1$s Role Scoper Support Forum%2$s', 'scoper'), "<a href='http://agapetry.net/forum/' target='_blank'>", '</a>');
		}

		return $help;
	}
			
	function build_menu() {
		if ( ! defined('USER_ROLES_RS') && isset( $_POST['role_type'] ) )
			scoper_use_posted_init_options();
	
		$uri = $_SERVER['SCRIPT_NAME'];
		$path = SCOPER_ABSPATH;
		
		global $scoper, $current_user;

		$is_option_administrator = is_option_administrator_rs();
		$is_user_administrator = is_user_administrator_rs();
		$is_content_administrator = is_content_administrator_rs();
			
		/*
		// optional hack to prevent roles / restrictions menu for non-Administrators
		//
		// This is now handled as a Role Scoper Option.
		// In Roles > Options > Features > Content Maintenance, set "Roles and Restrictions can be set" to "Administrators only" 
		//
		// To prevent Role Scoper from filtering the backend at all, go to Roles > Options > Realm > Access Types and deselect "editing and administering content"
		//
		// end optional hack
		*/
		
		$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');

		if ( ! $is_content_administrator && ( 'admin_content' == $require_blogwide_editor ) )
			if ( ! $is_option_administrator )
				return;

		if ( ! $is_user_administrator && ( 'admin' == $require_blogwide_editor ) )
			if ( ! $is_option_administrator )
				return;

		$can_admin_objects = array();
		$can_admin_terms = array();
		
		// which object types does this user have any administration over?
		foreach ( $scoper->data_sources->get_all() as $src_name => $src ) {
			if ( ! empty($src->no_object_roles) || ! empty($src->taxonomy_only) || ('group' == $src_name) )
				continue;
			
			$object_types = ( isset($src->object_types) ) ? $src->object_types : array( $src_name => true );

			foreach ( array_keys($object_types) as $object_type ) {
				if ( is_administrator_rs($src, 'user') || $this->user_can_admin_object($src_name, $object_type, 0, true) )
					if ( scoper_get_otype_option('use_object_roles', "$src_name:$object_type") )
						$can_admin_objects[$src_name][$object_type] = true;
			}
		}

		// which taxonomies does this user have any administration over?
		foreach ( $scoper->taxonomies->get_all() as $taxonomy => $tx ) {
			if ( is_administrator_rs($tx->source, 'user') || $this->user_can_admin_terms($taxonomy) )
				if ( scoper_get_otype_option('use_term_roles', $tx->object_source->name) ) {
					$can_admin_terms[$taxonomy] = true;
				}
		}

		/*
		global $_wp_menu_nopriv;
		if ( ! empty($can_admin_objects['post']['post']) || ! empty($can_admin_terms['category']) )
			if ( isset($_wp_menu_nopriv['edit.php']) )
				unset($_wp_menu_nopriv['edit.php']);
				
		if ( ! empty($can_admin_objects['post']['page']) )
			if ( isset($_wp_menu_nopriv['edit-pages.php']) )
				unset($_wp_menu_nopriv['edit-pages.php']);
		
		dump($_wp_menu_nopriv);
		*/
		
		$can_manage_groups = DEFINE_GROUPS_RS && ( $is_user_administrator || current_user_can('manage_groups') );
		
		// Users Tab
		if ( DEFINE_GROUPS_RS && $can_manage_groups ) {
			$cap_req = ( $can_manage_groups ) ? 'read' : 'manage_groups';
			
			if ( IS_MU_RS && scoper_get_site_option( 'mu_sitewide_groups' ) ) {
				add_submenu_page( 'wpmu-admin.php', __('Role Groups', 'scoper'), __('Role Groups', 'scoper'), $cap_req, 'rs-groups' );
				
				$func = "include_once('$path' . '/admin/groups.php');";
				add_action('wpmu-admin_page_rs-groups' , create_function( '', $func ) );
			} else {
				add_submenu_page( 'users.php', __('Role Groups', 'scoper'), __('Role Groups', 'scoper'), $cap_req, 'rs-groups' );
				
				$func = "include_once('$path' . '/admin/groups.php');";
				add_action( 'users_page_rs-groups' , create_function( '', $func ) );
				add_action( 'profile_page_rs-groups' , create_function( '', $func ) );
			}

			// satisfy WordPress' demand that all admin links be properly defined in menu
			if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'page=rs-default_groups' ) ) {
				add_submenu_page('users.php', __('User Groups', 'scoper'), __('Default Groups', 'scoper'), $cap_req, 'rs-default_groups');

				$func = "include_once('$path' . '/admin/default_groups.php');";
				add_action( 'users_page_rs-default_groups' , create_function( '', $func ) );
			}
			
			if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'page=rs-group_members' ) ) {
				add_submenu_page('users.php', __('User Groups', 'scoper'), __('Group Members', 'scoper'), $cap_req, 'rs-group_members');

				$func = "include_once('$path' . '/admin/group_members.php');";
				add_action( 'users_page_rs-group_members' , create_function( '', $func ) );
			}
		}

		// the rest of this function pertains to Roles and Restrictions menus
		if ( ! $is_user_administrator && ! $can_admin_terms && ! $is_user_administrator && ! $can_admin_objects )
			return;
	
		$general_roles = ('rs' == SCOPER_ROLE_TYPE) && $is_user_administrator; // && scoper_get_option('rs_blog_roles');  // rs_blog_roles option has never been active in any RS release; leave commented here in case need arises
		
		// determine the official WP-registered URL for roles and restrictions menus
		$object_submenus_first = false;

		if ( ! empty($can_admin_terms['category']) ) {
			$roles_menu = 'rs-category-roles_t';
			$restrictions_menu = 'rs-category-restrictions_t';

		} elseif ( ! empty($can_admin_objects['post']['post']) ) {
			$roles_menu = 'rs-post-roles';
			$restrictions_menu = 'rs-post-restrictions';
			$object_submenus_first = true;
			
		} elseif ( ! empty($can_admin_objects['post']['page']) ) {
			$roles_menu = 'rs-page-roles';
			$restrictions_menu = 'rs-page-restrictions';
			$object_submenus_first = true;

		} elseif ( $can_admin_terms ) {
			$taxonomy = key($can_admin_terms);
			$roles_menu = "rs-$taxonomy-roles_t";
			$restrictions_menu = "rs-$taxonomy-restrictions_t";

		} elseif ( $can_admin_objects ) {
			$src_name = key($can_admin_objects);
			$object_type = key($can_admin_objects[$src_name]);
			
			if ( ( $src_name != $object_type ) && ( 'post' != $src_name ) ) {
				$roles_menu = "rs-{$object_type}-roles_{$src_name}";
				$restrictions_menu = "rs-{$object_type}-restrictions_{$src_name}";
			} else {
				$roles_menu = "rs-$object_type-roles";
				$restrictions_menu = "rs-$object_type-restrictions";
			}

			$object_submenus_first = true;
		} else {
			// shouldn't ever need this
			$roles_menu = 'rs-roles-post';
			$restrictions_menu = 'rs-restrictions-post';
			$object_submenus_first = true;
		}

		if ( $general_roles )
			$roles_menu = 'rs-general_roles';


		if ( $is_user_administrator ) { 
			$roles_menu = 'rs-options';  // user administrators always have RS Options as top level roles submenu
			
			if ( empty( $restrictions_menu ) )
				$restrictions_menu =  'rs-category-restrictions_t';  // If RS Realms are customized, the can_admin_terms / can_admin_objects result can override this default, even for user administrators
		}
		
			
		// For convenience in WP 2.5 - 2.6, set the primary menu link (i.e. default submenu) based on current URI
		// When viewing Category Roles, make Restriction menu default-link to Category Restrictions submenu (and likewise for other terms/objects)
		// (ozh plugin breaks this, and it is not needed in 2.7+ due to core JS dropdown)
		if ( ! awp_ver('2.7-dev') && ! awp_is_plugin_active('wp_ozh_adminmenu.php') ) { 
			require_once( 'admin-legacy_rs.php' );
			scoper_set_legacy_menu_links( $roles_menu, $restrictions_menu, $uri, $can_admin_terms, $can_admin_objects );
		}
		
		// Register the menus with WP using URI and links determined above
		global $menu;
		$tweak_menu = false; // don't mess with menu order unless we know we can get away with it in current WP version
			
		//  Manually set menu indexes for positioning below Users menu,
		//  but not if Flutter (a.k.a. Fresh Page) plugin is active.  It re-indexes menu items 
		if ( ! defined( 'SCOPER_DISABLE_MENU_TWEAK' ) ) {
			if ( awp_ver('2.8-dev') ) {
				if ( ! awp_ver('3.0') ) { // review and increment this with each WP version until there's a clean way to force menu proximity to 'Users'
					if ( isset( $menu[70] ) && $menu[70][2] == 'users.php' ) {
						$tweak_menu = true;
						$restrictions_menu_key = 71;
						$roles_menu_key = 72;
					}
				}
			} elseif ( awp_ver('2.7-dev') && empty($menu[51]) && empty($menu[52]) ) {
				if ( isset( $menu[50] ) && $menu[50][2] == 'users.php' ) {
					$tweak_menu = true;
					$restrictions_menu_key = 51;
					$roles_menu_key = 52;
				}
			} elseif ( empty($menu[37]) && empty($menu[38]) && empty($menu[39]) ) {
				$tweak_menu = true;
				$restrictions_menu_key = 38;
				$roles_menu_key = 39;
				
				$menu[37] = $menu[40];	//move Users menu so we can put Roles, Restrictions after it
				unset($menu[40]);
			}
		}

		$roles_cap = 'read'; // we apply other checks within this function to confirm the menu is valid for current user
		$restrictions_caption = __('Restrictions', 'scoper');
		$roles_caption = __('Roles', 'scoper');
		
		if ( $tweak_menu ) {
			$roles_hook_page = 'admin_page_';
			$restrictions_hook_page = 'admin_page_';

			// note: as of WP 2.7-near-beta, custom content dir for menu icon is not supported
			$menu[$restrictions_menu_key] = array( '0' => $restrictions_caption, 'read', $restrictions_menu, __('Role Restrictions', 'scoper'), 'menu-top' );
			$menu[$restrictions_menu_key][6] = SCOPER_URLPATH . '/admin/images/menu/restrictions.png';
			
			$menu[$roles_menu_key] = array( '0' => $roles_caption, $roles_cap, $roles_menu, __('Role Scoper Options', 'scoper'), 'menu-top' );
			$menu[$roles_menu_key][6] = SCOPER_URLPATH . '/admin/images/menu/roles.png';
			
			global $_registered_pages;
			$_registered_pages["admin_page_$roles_menu"] = true;
			$_registered_pages["admin_page_$restrictions_menu"] = true;
		} else {
			add_menu_page($restrictions_caption, __('Restrictions', 'scoper'), 'read', $restrictions_menu, '', SCOPER_URLPATH . '/admin/images/menu/restrictions.png' );
			add_menu_page($roles_caption, __('Roles', 'scoper'), $roles_cap, $roles_menu, '', SCOPER_URLPATH . '/admin/images/menu/roles.png');
		
			$roles_hook_page_base = sanitize_title( __('Roles', 'scoper') ) . '_page_';	// the page hook names use translated menu title
			$roles_hook_page = $roles_hook_page_base;
			$restrictions_hook_page_base = sanitize_title( __('Restrictions', 'scoper') ) . '_page_';
		}
		
		
		if ( $is_option_administrator ) {
			$func = "include_once('$path' . '/admin/options.php');scoper_options( false );";
			$hook_page = ( $tweak_menu ) ? $roles_hook_page : 'toplevel_page_';
			add_action($hook_page . 'rs-options' , create_function( '', $func ) );
		}
		
		
		if ( $general_roles ) {
			add_submenu_page($roles_menu, __('General Roles', 'scoper'), __('General', 'scoper'), 'read', 'rs-general_roles');
			
			$func = "include_once('$path' . '/admin/general_roles.php');";
			add_action($roles_hook_page . 'rs-general_roles', create_function( '', $func ) );
		}
			
		$first_pass = true;

		$submenu_types = ( $object_submenus_first ) ? array( 'object', 'term' ) : array( 'term', 'object' );
		foreach ( $submenu_types as $scope ) {
			if ( 'term' == $scope ) {
				// Term Roles and Restrictions (will only display objects user can edit)
				if ( $can_admin_terms ) {
					// Will only allow assignment to terms for which current user has admin cap
					// Term Roles page also prevents assignment or removal of roles current user doesn't have
					foreach ( $scoper->taxonomies->get_all() as $taxonomy => $tx ) {
						
						if ( empty($can_admin_terms[$taxonomy]) )
							continue;
						
						if ( $require_blogwide_editor ) {
							global $current_user;
							if ( empty( $current_user->allcaps['edit_others_posts'] ) && empty( $current_user->allcaps['edit_others_pages'] ) && empty( $current_user->allcaps['manage_categories'] ) )
								continue;
						}
						
						$show_roles_menu = true;
						
						// deal with WP hook naming quirk for non-tweaked plugin menus
						if ( ! $tweak_menu ) {
							if ( $first_pass ) {
								$roles_hook_page = ( $is_user_administrator || $general_roles ) ? $roles_hook_page_base : 'toplevel_page_';	// otherwise Options / General are the toplevel submenu
								$restrictions_hook_page = 'toplevel_page_';
							} else {
								$roles_hook_page = $roles_hook_page_base;
								$restrictions_hook_page = $restrictions_hook_page_base;
							}
							
							$first_pass = false;
						}
						
						add_submenu_page($roles_menu, sprintf(__('%s Roles', 'scoper'), $tx->display_name), $tx->display_name_plural, 'read', "rs-$taxonomy-roles_t");
						
						$func = "include_once('$path' . '/admin/section_roles.php');scoper_admin_section_roles('$taxonomy');";
						add_action($roles_hook_page . "rs-$taxonomy-roles_t", create_function( '', $func ) );	
						
						if ( ! awp_ver('2.6') )
							add_action("_page_" . "rs-$taxonomy-roles_t", create_function( '', $func ) );	
						
					
						if ( ! empty($tx->requires_term) ) {
							$show_restrictions_menu = true;
	
							add_submenu_page($restrictions_menu, sprintf(__('%s restrictions', 'scoper'), $tx->display_name), $tx->display_name_plural, 'read', "rs-$taxonomy-restrictions_t");
							
							$func = "include_once('$path' . '/admin/section_restrictions.php');scoper_admin_section_restrictions('$taxonomy');";
							add_action($restrictions_hook_page . "rs-$taxonomy-restrictions_t", create_function( '', $func ) );	
				
							if ( ! awp_ver('2.6') )
								add_action("_page_" . "rs-$taxonomy-restrictions_t", create_function( '', $func ) );
						}
					} // end foreach taxonomy
				} // endif can admin terms
			
			} else {
				// Object Roles (will only display objects user can edit)
				if ( $can_admin_objects ) {
					foreach ( $scoper->data_sources->get_all() as $src_name => $src ) {
						if ( ! empty($src->no_object_roles) || ! empty($src->taxonomy_only) || ('group' == $src_name) )
							continue;
						
						$object_types = ( isset($src->object_types) ) ? $src->object_types : array( $src_name => true );
		
						foreach ( array_keys($object_types) as $object_type ) {
							if ( empty($can_admin_objects[$src_name][$object_type]) )
								continue;
		
							if ( $require_blogwide_editor ) {
								$required_cap = ( 'page' == $object_type ) ? 'edit_others_pages' : 'edit_others_posts';
								
								global $current_user;
								if ( empty( $current_user->allcaps[$required_cap] ) )
									continue;
							}
	
							$show_roles_menu = true;
							$show_restrictions_menu = true;
							
							// deal with WP hook naming quirk for non-tweaked plugin menus
							if ( ! $tweak_menu ) {
								if ( $first_pass ) {
									$roles_hook_page = ( $is_user_administrator || $general_roles ) ? $roles_hook_page_base : 'toplevel_page_';	// otherwise Options / General are the toplevel submenu
									$restrictions_hook_page = 'toplevel_page_';
								} else {
									$roles_hook_page = $roles_hook_page_base;
									$restrictions_hook_page = $restrictions_hook_page_base;
								}
							
								$first_pass = false;
							}
						
							if ( ( $src_name != $object_type ) && ( 'post' != $src_name ) ) {
								$roles_page = "rs-{$object_type}-roles_{$src_name}";
								$restrictions_page = "rs-{$object_type}-restrictions_{$src_name}";
							} else {
								$roles_page = "rs-$object_type-roles";
								$restrictions_page = "rs-$object_type-restrictions";
							}
							
							$src_otype = ( isset($src->object_types) ) ? "{$src_name}:{$object_type}" : $src_name;
							$display_name = $this->interpret_src_otype($src_otype, false);
							$display_name_plural = $this->interpret_src_otype($src_otype, true);
							
							add_submenu_page($roles_menu, sprintf(__('%s Roles', 'scoper'), $display_name), $display_name_plural, 'read', $roles_page);
							
							$func = "include_once('$path' . '/admin/object_roles.php');scoper_admin_object_roles('$src_name', '$object_type');";
							add_action($roles_hook_page . $roles_page, create_function( '', $func ) );
						
							if ( ! awp_ver('2.6') )
								add_action("_page_" . $roles_page, create_function( '', $func ) );
							
								
							add_submenu_page($restrictions_menu, sprintf(__('%s Restrictions', 'scoper'), $display_name), $display_name_plural, 'read', $restrictions_page);
								
							$func = "include_once('$path' . '/admin/object_restrictions.php');scoper_admin_object_restrictions('$src_name', '$object_type');";
							add_action($restrictions_hook_page . $restrictions_page, create_function( '', $func ) );	
					
							if ( ! awp_ver('2.6') )
								add_action("_page_" . $restrictions_page, create_function( '', $func ) );	
						} // end foreach obj type
					} // end foreach data source
				} // endif can admin objects
			} // endif drawing object scope submenus
		} // end foreach submenu scope

		if ( $is_user_administrator ) {
			add_submenu_page($roles_menu, __('About Role Scoper', 'scoper'), __('About', 'scoper'), 'read', 'rs-about');

			$func = "include_once('$path' . '/admin/about.php');";
			add_action( $roles_hook_page . 'rs-about' , create_function( '', $func ) );
		}
			

		global $submenu;
			
		// Change Role Scoper Options submenu title from default "Roles" to "Options"
		if ( $is_option_administrator ) {
			if ( isset($submenu[$roles_menu][0][2]) && ( $roles_menu == $submenu[$roles_menu][0][2] ) )
				$submenu[$roles_menu][0][0] = __awp('Options');

			// satisfy WordPress' demand that all admin links be properly defined in menu
			if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'rs-attachments_utility' ) ) {
				add_submenu_page($roles_menu, __('Attachment Utility', 'scoper'), __('Attachment Utility', 'scoper'), 'read', 'rs-attachments_utility');
				
				$func = "include_once('$path' . '/admin/attachments_utility.php');";
				add_action( 'admin_page_rs-attachments_utility' , create_function( '', $func ) );
			}
		} elseif ( empty($show_restrictions_menu) || empty($show_roles_menu) ) {
			// Remove Roles or Restrictions menu if it has no submenu
			if ( $tweak_menu ) {
				if ( empty($show_restrictions_menu) && isset($menu[$restrictions_menu_key]) )
					unset($menu[$restrictions_menu_key]);
					
				if ( empty($show_roles_menu) && isset($menu[$roles_menu_key]) )
					unset($menu[$roles_menu_key]);
				
			} else {
				global $menu;
				foreach ( array_keys($menu) as $key ) {
					if ( isset( $menu[$key][0]) )
						if ( empty($show_roles_menu) && ( $roles_caption == $menu[$key][0] ) )
							unset($menu[$key]);
						elseif ( empty($show_restrictions_menu) && ( $restrictions_caption == $menu[$key][0] ) )
							unset($menu[$key]);
				}
			}
		}
		
		
		// workaround for WP 2.7's universal inclusion of "Add New" in Posts, Pages menu
		if ( awp_ver('2.7') ) {
			if ( isset($submenu['edit-pages.php']) ) {
				foreach ( $submenu['edit-pages.php'] as $key => $arr ) {
					if ( isset($arr['2']) && ( 'page-new.php' == $arr['2'] ) ) {
						$scoper->cap_interceptor->skip_id_generation = true;
						$scoper->cap_interceptor->skip_any_object_check = true;	
	
						if ( ! current_user_can('edit_pages') )
							unset( $submenu['edit-pages.php'][$key]);
							
						$scoper->cap_interceptor->skip_id_generation = false;
						$scoper->cap_interceptor->skip_any_object_check = false;
					}
				}
			}
			
			if ( isset($submenu['edit.php']) ) {
				foreach ( $submenu['edit.php'] as $key => $arr ) {
					if ( isset($arr['2']) && ( 'post-new.php' == $arr['2'] ) ) {
						$scoper->cap_interceptor->skip_id_generation = true;
						$scoper->cap_interceptor->skip_any_object_check = true;	
	
						if ( ! current_user_can('edit_posts') )
							unset( $submenu['edit.php'][$key]);
							
						$scoper->cap_interceptor->skip_id_generation = false;
						$scoper->cap_interceptor->skip_any_object_check = false;
					}
				}
			}
		}
		
		
		// WP MU site options
		if ( $is_option_administrator && IS_MU_RS )
			scoper_mu_site_menu();
		

		// satisfy WordPress' demand that all admin links be properly defined in menu
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'page=object_role_edit' ) ) {
			add_submenu_page($roles_menu, __('Object Role Edit', 'scoper'), __('Object Role Edit', 'scoper'), 'read', 'rs-object_role_edit');
			
			$func = "include_once('$path' . '/admin/object_role_edit.php');";
			add_action( 'admin_page_rs-object_role_edit' , create_function( '', $func ) );
		}
	}

	
	function interpret_src_otype($src_otype, $use_plural_display_name = true) {
		global $scoper;
		
		if ( ! $arr_src_otype = explode(':', $src_otype) )
			return $display_name;
	
		$display_name_prop = ( $use_plural_display_name ) ? 'display_name_plural' : 'display_name';
		
		if ( isset( $arr_src_otype[1]) )
			$display_name = $scoper->data_sources->member_property($arr_src_otype[0], 'object_types', $arr_src_otype[1], $display_name_prop);
		else
			$display_name = $scoper->data_sources->member_property($arr_src_otype[0], $display_name_prop);
			
		if ( ! $display_name )	// in case of data sources definition error, cryptic fallback better than nullstring
			$display_name = $src_otype;
			
		return $display_name;
	}

	function user_can_admin_role($role_handle, $item_id, $src_name = '', $object_type = '', $user = '' ) {
		if ( is_user_administrator_rs() )
			return true;

		require_once( 'permission_lib_rs.php' );
		return user_can_admin_role_rs($role_handle, $item_id, $src_name, $object_type, $user );
	}
	
	function user_can_admin_object($src_name, $object_type, $object_id = false, $any_obj_role_check = false, $user = '' ) {
		if ( is_content_administrator_rs() )
			return true;
		
		require_once( 'permission_lib_rs.php' );
		return user_can_admin_object_rs($src_name, $object_type, $object_id, $any_obj_role_check, $user );
	}
	
	function user_can_admin_terms($taxonomy = '', $term_id = '', $user = '') {
		if ( is_user_administrator_rs() )
			return true;
		
		require_once( 'permission_lib_rs.php' );
		return user_can_admin_terms_rs($taxonomy, $term_id, $user);
	}
	
	
	function user_can_edit_blogwide( $src_name = '', $object_type = '', $args = '' ) {
		if ( is_administrator_rs($src_name) )
			return true;

		require_once( 'permission_lib_rs.php' );
		return user_can_edit_blogwide_rs($src_name, $object_type, $args);
	}
	
	// primary use is to account for different contexts of users query
	function get_context($src_name = '', $reqd_caps_only = false) {
		global $scoper;
		
		$full_uri = urldecode($_SERVER['REQUEST_URI']);
		$matched = array();
		
		foreach ( $scoper->data_sources->get_all_keys() as $_src_name ) {
			if ( $src_name)
				$_src_name = $src_name;  // if a src_name arg was passed in, short-circuit the loop
			
			if ( $arr = $scoper->data_sources->member_property($_src_name, 'users_where_reqd_caps', CURRENT_ACCESS_NAME_RS) ) {

				foreach ( $arr as $uri_sub => $reqd_caps ) {	// if no uri substrings match, use default (nullstring key), but only if data source was passed in
					if ( ( $uri_sub && strpos($full_uri, $uri_sub) )
					|| ( $src_name && ! $uri_sub && ! $matched ) ) {
						$matched['reqd_caps'] = $reqd_caps;
						
						if ( ! $reqd_caps_only )
							$matched['source'] = $scoper->data_sources->get($_src_name);
						
						if ( $uri_sub) break;
					}
				}
			}
			
			if ( $matched || $src_name) // if a src_name arg was passed in, short-circuit the loop
				break;
		} // data sources loop
		
		if ( $matched && ! $reqd_caps_only ) {
			if ( isset($matched['source']->object_types) ) {
				// if this data source has more than one object type defined, 
				// use the reqd_caps to determine object type for this context
				if ( count($matched['source']->object_types) > 1 ) {
					$src_otypes = $scoper->cap_defs->object_types_from_caps($matched['reqd_caps']);
					if ( isset($src_otypes[$_src_name]) && (count($src_otypes[$_src_name]) == 1) ) {
						reset($src_otypes[$_src_name]);
						$matched['object_type_def'] = $matched['source']->object_types[ key($src_otypes[$_src_name]) ];
					}
				} else
					$matched['object_type_def'] = reset( $matched['source']->object_types );
			}
		}
	
		return (object) $matched;
	}
	
} // end class ScoperAdmin
?>