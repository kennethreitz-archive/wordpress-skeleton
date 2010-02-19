<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

function scoper_maybe_expire_file_rules( $new_option_value, $old_option_value ) {
	if ( $old_option_value !== $new_option_value )
		scoper_expire_file_rules();
	
	return $new_option_value;
}

function scoper_maybe_flush_site_rules( $new_option_value, $old_option_value ) {
	if ( $old_option_value !== $new_option_value )
		scoper_flush_site_rules();
	
	return $new_option_value;
}

class Scoper_Submittee {

	function handle_submission($action, $sitewide = false, $customize_defaults = false) {
		if ( ( $sitewide || $customize_defaults ) ) {
			if ( function_exists('is_site_admin') && ! is_site_admin() )
				wp_die(__awp('Cheatin&#8217; uh?'));
		}
	
		if ( $customize_defaults )
			$sitewide = true;		// default customization is only for per-blog options, but is sitewide in terms of DB storage in sitemeta table
			
		if ( 'flush' == $action ) {
			wpp_cache_flush();
			return;	
		}

		if ( ! in_array( $_GET["page"], array( 'rs-options', 'rs-site_options') ) )
			return;
		
		if ( empty($_POST['rs_submission_topic']) )
			return;
		
		if ( 'options' == $_POST['rs_submission_topic'] ) {
			if ( ! is_option_administrator_rs() )
				wp_die(__awp('Cheatin&#8217; uh?'));

			scoper_refresh_default_options();
			scoper_refresh_default_otype_options();
			
			$method = "{$action}_options";
			if ( method_exists( $this, $method ) )
				call_user_func( array($this, $method), $sitewide, $customize_defaults );
			
			$method = "{$action}_realm";
			if ( method_exists( $this, $method ) )
				call_user_func( array($this, $method), $sitewide, $customize_defaults );
			
			if ( $sitewide && ! $customize_defaults ) {
				$method = "{$action}_sitewide";
				if ( method_exists( $this, $method ) )
					call_user_func( array($this, $method) );
			}
				
			if ( isset($_POST['rs_role_defs']) && empty($_POST['rs_defaults']) ) {
				if ( $customize_defaults )
					$function = 'update_rs_role_defs_customize_defaults';
				elseif( $sitewide )
					$function = 'update_rs_role_defs_sitewide';
				else
					$function = 'update_rs_role_defs';
				add_action( 'init', array(&$this, $function), 20 );	// this must execute after other plugins have added rs config filters
			}
		}

		scoper_refresh_options();
		
		// force DB schema update if sitewide_groups option was changed
		require( SCOPER_ABSPATH . '/db-config_rs.php');
	}
	
	function update_options( $sitewide = false, $customize_defaults = false ) {
		check_admin_referer( 'scoper-update-options' );
	
		$this->update_page_options( $sitewide, $customize_defaults );
		$this->update_page_otype_options( $sitewide, $customize_defaults );
		
		global $wpdb;
		$wpdb->query( "UPDATE $wpdb->options SET autoload = 'no' WHERE option_name LIKE 'scoper_%' AND option_name != 'scoper_version'" );
	}
	
	function default_options( $sitewide = false, $customize_defaults = false ) {
		check_admin_referer( 'scoper-update-options' );
	
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';

		$reviewed_options = explode(',', $_POST['all_options']);
		foreach ( $reviewed_options as $option_name )
			scoper_delete_option($default_prefix . $option_name, $sitewide );

		$reviewed_otype_options = explode(',', $_POST['all_otype_options']);
		foreach ( $reviewed_otype_options as $option_name )
			scoper_delete_option($default_prefix . $option_name, $sitewide );

		scoper_delete_option($default_prefix . 'disabled_role_caps', $sitewide );
		scoper_delete_option($default_prefix . 'user_role_caps', $sitewide );
		
		scoper_set_conditional_defaults();
	}
	
	function update_realm( $sitewide = false, $customize_defaults = false ) {
		check_admin_referer( 'scoper-update-options' );
		
		// changes to these options will trigger .htaccess regen
		if ( $sitewide ) {
			add_action( 'add_site_option_scoper_disabled_access_types', 'scoper_expire_file_rules' );
			add_action( 'update_site_option_scoper_disabled_access_types', 'scoper_expire_file_rules' );
		} else
			add_action( 'update_option_scoper_disabled_access_types', 'scoper_maybe_expire_file_rules', 10, 2 );
		
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';
		
		$disabled = array();
		$access_names = explode(',', $_POST['all_access_types'] );
		foreach ( $access_names as $access_name )
			$disabled[$access_name] = empty( $_POST['access_types-' . $access_name ] );	
		scoper_update_option($default_prefix . 'disabled_access_types', $disabled, $sitewide );
		
		$enable_taxonomies = array();
		
		//$reviewed_wp_taxonomies = explode( ',', $_POST['all_wp_taxonomies'] );
		$selected_wp_taxonomies = isset($_POST['enable_wp_taxonomies']) ? $_POST['enable_wp_taxonomies'] : array();
		
		if ( isset($_POST['locked_wp_taxonomies']) ) {
			$locked_wp_taxonomies  = explode( ',', $_POST['locked_wp_taxonomies'] );
			$selected_wp_taxonomies = array_merge( $selected_wp_taxonomies, $locked_wp_taxonomies);
		}
		
		$selected_wp_taxonomies = array_fill_keys($selected_wp_taxonomies, 1);
		scoper_update_option($default_prefix . 'enable_wp_taxonomies', $selected_wp_taxonomies, $sitewide );

		$this->update_page_otype_options( $sitewide, $customize_defaults );
	}
	
	function default_realm( $sitewide = false, $customize_defaults = false ) {
		check_admin_referer( 'scoper-update-options' );
		
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';
		
		scoper_delete_option( $default_prefix . 'enable_wp_taxonomies', $sitewide );
		scoper_delete_option( $default_prefix . 'disabled_access_types', $sitewide );

		$reviewed_otype_options = explode(',', $_POST['all_otype_options']);
		foreach ( $reviewed_otype_options as $option_name )
			scoper_delete_option($default_prefix . $option_name, $sitewide );
	}
	
	function update_sitewide() {
		check_admin_referer( 'scoper-update-options' );

		$reviewed_options = isset($_POST['rs_all_movable_options']) ? explode(',', $_POST['rs_all_movable_options']) : array();
		
		$options_sitewide = isset($_POST['rs_options_sitewide']) ? (array) $_POST['rs_options_sitewide'] : array();
		
		
		// must force disabled_role_caps scope setting to follow user_role_caps
		$reviewed_options []= 'disabled_role_caps';
		
		if ( in_array( 'user_role_caps', $options_sitewide ) )
			$options_sitewide = array_merge( $options_sitewide, array( 'disabled_role_caps' ) );
		
			
		// must force use_term_roles and use_object_roles scope setting to follow enable_wp_taxonomies
		$reviewed_options []= 'use_term_roles';
		$reviewed_options []= 'use_object_roles';
		
		if ( in_array( 'enable_wp_taxonomies', $options_sitewide ) )
			$options_sitewide = array_merge( $options_sitewide, array( 'use_term_roles', 'use_object_roles' ) );

			
		// must force all teaser option to follow scope of do_teaser
		$teaser_options = array( 'use_teaser' );
		
		global $scoper_default_otype_options;
		foreach ( array_keys($scoper_default_otype_options) as $option_name ) {
			if ( 0 === strpos( $option_name, 'teaser_' ) )
				$teaser_options []= $option_name; 
		}
		
		$reviewed_options = array_merge( $reviewed_options, $teaser_options );
		
		if ( in_array( 'do_teaser', $options_sitewide ) )
			$options_sitewide = array_merge( $options_sitewide, $teaser_options );

			
		add_site_option( "scoper_options_sitewide_reviewed", $reviewed_options );
		add_site_option( "scoper_options_sitewide", $options_sitewide );
	}
	
	function default_sitewide() {
		check_admin_referer( 'scoper-update-options' );

		scoper_delete_option( 'options_sitewide', true );
		scoper_delete_option( 'options_sitewide_reviewed', true );
	}
	
	function update_page_options( $sitewide = false, $customize_defaults = false ) {
		global $scoper_role_types;

		// changes to these options will trigger .htaccess regen
		if ( $sitewide ) {
			add_action( 'update_site_option_scoper_file_filtering', 'scoper_flush_site_rules' );
			add_action( 'add_site_option_scoper_file_filtering', 'scoper_flush_site_rules' );
			add_action( 'update_site_option_scoper_file_filtering', 'scoper_expire_file_rules' );
			add_action( 'add_site_option_scoper_file_filtering', 'scoper_expire_file_rules' );
		} else {
			add_action( 'update_option_scoper_file_filtering', 'scoper_maybe_expire_file_rules', 10, 2 );
			add_action( 'update_option_scoper_feed_link_http_auth', 'scoper_maybe_flush_site_rules', 10, 2 );
		}
		
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';
		
		$reviewed_options = explode(',', $_POST['all_options']);
		
		foreach ( $reviewed_options as $option_basename ) {
			$value = isset($_POST[$option_basename]) ? $_POST[$option_basename] : '';
			
			if ( 'role_type' == $option_basename )
				$value = $scoper_role_types[$value];
			elseif ( 'mu_sitewide_groups' == $option_basename ) {
				$current_setting = get_site_option( 'scoper_mu_sitewide_groups' );
				if ( $current_setting != $value ) {
					//delete_option( 'scoper_version' ); // this forces DB schema update on next access (to create site-wide / blog-specific groups table)
					$ver = get_option( 'scoper_version' );
					require_once( 'db-setup_rs.php' );
					scoper_db_setup( $ver['db_version'] );
				}
				
				$value = intval( $value );
			}

			if ( ! is_array($value) )
				$value = trim($value);
			$value = stripslashes_deep($value);
	
			scoper_update_option( $default_prefix . $option_basename, $value, $sitewide );
		}
		
		//dump($_POST);
		//die;
	}
	
	function update_page_otype_options( $sitewide = false, $customize_defaults = false ) {
		global $scoper_default_otype_options;
		
		// changes to these options will trigger .htaccess regen
		if ( $sitewide ) {
			add_action( 'add_site_option_scoper_use_term_roles', 'scoper_expire_file_rules' );
			add_action( 'add_site_option_scoper_use_object_roles', 'scoper_expire_file_rules' );
			add_action( 'pre_update_site_option_scoper_use_term_roles', 'scoper_expire_file_rules' );
			add_action( 'pre_update_site_option_scoper_use_object_roles', 'scoper_expire_file_rules' );
		} else {
			add_action( 'update_option_scoper_use_term_roles', 'scoper_maybe_expire_file_rules', 10, 2 );
			add_action( 'update_option_scoper_use_object_roles', 'scoper_maybe_expire_file_rules', 10, 2 );
		}
			
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';
		
		$reviewed_otype_options = explode(',', $_POST['all_otype_options']);
		$otype_option_vals = array();
		foreach ( $reviewed_otype_options as $option_basename ) {
			if ( isset( $scoper_default_otype_options[$option_basename] ) ) {
				if ( $opt = $scoper_default_otype_options[$option_basename] ) {
					foreach ( array_keys($opt) as $src_otype ) {
						$postvar = $option_basename . '-' . str_replace(':', '_', $src_otype);
						$value = isset($_POST[$postvar]) ? $_POST[$postvar] : '';
						if ( ! is_array($value) ) 
							$value = trim($value);
						
						$otype_option_vals[ $option_basename ] [ $src_otype ] = stripslashes_deep($value);
					}
				}
			}
		}

		foreach ( $otype_option_vals as $option_basename => $value )
			scoper_update_option( $default_prefix . $option_basename , $value, $sitewide);
	}
	
	function update_rs_role_defs_customize_defaults () {
		$this->update_rs_role_defs( true, true );
	}
	
	function update_rs_role_defs_sitewide () {
		$this->update_rs_role_defs( true, false );
	}
	
	function update_rs_role_defs( $sitewide = false, $customize_defaults = false ) {
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';
		
		$default_role_caps = apply_filters('define_role_caps_rs', scoper_core_role_caps() );

		$cap_defs = new WP_Scoped_Capabilities();
		$cap_defs = apply_filters('define_capabilities_rs', $cap_defs);
		$cap_defs->add_member_objects( scoper_core_cap_defs() );

		global $scoper, $scoper_role_types;
		$role_defs = new WP_Scoped_Roles($cap_defs, $scoper_role_types);
		$role_defs->add_member_objects( scoper_core_role_defs() );
		$role_defs = apply_filters('define_roles_rs', $role_defs);

		$disable_caps = array();
		$add_caps = array();
		
		foreach ( $default_role_caps as $role_handle => $default_caps ) {
			if ( $role_defs->member_property($role_handle, 'no_custom_caps') || $role_defs->member_property($role_handle, 'anon_user_blogrole') )
				continue;

			$posted_set_caps = ( empty($_POST["{$role_handle}_caps"]) ) ? array() : $_POST["{$role_handle}_caps"];

			// html IDs have any spaces stripped out of cap names.  Replace them for processing.
			$set_caps = array();
			foreach ( $posted_set_caps as $cap_name ) {
				if ( strpos( $cap_name, ' ' ) )
					$set_caps []= str_replace( '_', ' ', $cap_name );
				else
					$set_caps []= $cap_name;
			}
			
			// deal with caps which are locked into role, therefore displayed as a disabled checkbox and not included in $_POST
			foreach ( array_keys($default_caps) as $cap_name ) {
				if ( ! in_array($cap_name, $set_caps) && $cap_defs->member_property($cap_name, 'no_custom_remove') )
					$set_caps []= $cap_name;
			}

			$disable_caps[$role_handle] = array_fill_keys( array_diff( array_keys($default_caps), $set_caps ), true);
			$add_caps[$role_handle] = array_fill_keys( array_diff( $set_caps, array_keys($default_caps) ), true);
		}

		scoper_update_option( $default_prefix . 'disabled_role_caps', $disable_caps, $sitewide);
		scoper_update_option( $default_prefix . 'user_role_caps', $add_caps, $sitewide);
		
		scoper_refresh_options();
		$scoper->load_role_caps();
		
		global $wp_roles;
		
		// synchronize WP roles as requested
		if ( ! empty( $_POST['sync_wp_roles'] ) ) {
			foreach ( $_POST['sync_wp_roles'] as $sync_request ) {
				$sync_handles = explode( ':', $sync_request );
				$rs_role_handle = $sync_handles[0];
				$wp_role_handle = $sync_handles[1];

				$wp_role_name = str_replace( 'wp_', '', $wp_role_handle );
				
				// only remove caps which are defined for this RS role's data source and object type
				$role_attributes = $scoper->role_defs->get_role_attributes( $rs_role_handle );
				$src_name = $role_attributes->src_names[0];
				$object_type = $role_attributes->object_types[0];
				$otype_caps = $scoper->cap_defs->get_matching( $src_name, $object_type, '', STATUS_ANY_RS );
				
				// make the roledef change for all blogs if RS role def is sitewide
				if ( IS_MU_RS && $sitewide ) {
					global $wpdb, $blog_id;
					$blog_ids = scoper_get_col( "SELECT blog_id FROM $wpdb->blogs" );
					$orig_blog_id = $blog_id;	
				} else
					$blog_ids = array( '' );

				foreach ( $blog_ids as $id ) {
					if ( count($blog_ids) > 1 )
						switch_to_blog( $id );

					if ( ! isset( $wp_roles->role_objects[$wp_role_name] ) )
						continue;
					
					if ( $wp_missing_caps = array_diff_key( $scoper->role_defs->role_caps[$rs_role_handle], $wp_roles->role_objects[$wp_role_name]->capabilities ) )
						foreach ( array_keys($wp_missing_caps) as $cap_name )
							$wp_roles->add_cap( $wp_role_name, $cap_name );	
				
					$wp_defined_caps = array_intersect_key( $wp_roles->role_objects[$wp_role_name]->capabilities, $otype_caps );
	
					if ( $wp_extra_caps = array_diff_key( $wp_defined_caps, $scoper->role_defs->role_caps[$rs_role_handle] ) )
						foreach ( array_keys($wp_extra_caps) as $cap_name )
							$wp_roles->remove_cap( $wp_role_name, $cap_name );
				}
						
				if ( count($blog_ids) > 1 )
					switch_to_blog( $orig_blog_id );

				$wp_roles = new WP_Roles();
			}
		}

		$scoper->role_defs->locked = false;
		$scoper->role_defs->populate_with_wp_roles();
		$scoper->role_defs->lock();
	}
}
	
	
?>