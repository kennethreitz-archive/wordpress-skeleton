<?php
// note: This file was moved into admin/misc subdirectory to avoid detection as a plugin file by the WP plugin updater (due to Plugin Name search string)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

require_once( SCOPER_ABSPATH . '/lib/agapetry_wp_admin_lib.php' ); // function awp_remote_fopen()
	
// this version update function derived from cforms by Oliver Seidel
function scoper_new_version_notice() {
	$rechecked = false;
	$check_minutes = scoper_get_option('version_check_minutes');
	$last_check = scoper_get_option('last_version_update_check');
	if ( ( (time() - $last_check) > ( $check_minutes * 60 ) ) || ( ! $vcheck = get_site_option('scoper_version_info') ) ) {
		$vcheck = wp_remote_fopen( 'http://agapetry.net/downloads/role-scoper_version.chk' );
		$rechecked = true;
		update_option( 'scoper_version_info', $vcheck );
	}

	if ( ( (time() - $last_check) > ( $check_minutes * 60 ) ) || ( ! $vcheck_ext = get_site_option('scoper_extension_info') ) ) {
		$vcheck_ext = awp_remote_fopen( 'http://agapetry.net/downloads/role-scoper-extensions.chk', 5 );
		$rechecked = true;
		update_option( 'scoper_extension_info', $vcheck_ext );
	}
	
	if ( $rechecked )
		update_option( 'scoper_last_version_update_check', time() );

	if($vcheck)
	{
		$status = explode('@', $vcheck);
		$theVersion = $status[1];
			
		if( ( version_compare( strval($theVersion), strval(SCOPER_VERSION), '>' ) == 1 ) )
		{
			$msg = '<strong>' . sprintf(__( "A new version of Role Scoper is available (%s)", "scoper" ), $theVersion);

			if ( $rechecked || ( ! $vcheck = get_site_option('scoper_version_message') ) ) {
				$vcheck = awp_remote_fopen( 'http://agapetry.net/downloads/role-scoper.chk', 5 );
				add_site_option( 'scoper_version_message', $vcheck );
			}
			
			if ( $vcheck ) {
				$status = explode('@', $vcheck);

				$theMessage = $status[3];
				if ( $ver_pos = strpos($theMessage, '<br />' . SCOPER_VERSION) )
					$theMessage = substr($theMessage, 0, $ver_pos);
			
				$theMessage = str_replace( "'", '&#39;', $theMessage );	// Despite this precaution, don't include apostrophes in .chk file because older RS versions (< 1.0.0-rc9) will choke on it.
				$theMessage = str_replace( '"', '&quot;', $theMessage );
				
				$msg .= '</strong><small>' . $theMessage . '</small>';
			}

			if ( strpos( $msg, '<!--more-->' ) ) {
				$more_caption = __( 'read more...', 'scoper');
				$msg = preg_replace( '/\<\!\-\-more\-\-\>/', '<a href="javascript:void(0)" onclick="rs_display_version_more();">' . $more_caption . '</a><p id="rs_version_more" class="rs_more" style="display:none;">', $msg, 1);
				$msg .= '</p>';
			} else
				$msg .= '<br />';

			$msg .= '<a href="http://agapetry.net/category/role-scoper/" target="_blank">' . __('Read about the update', 'scoper') . '</a>';
			$msg .= '&nbsp;&nbsp;&nbsp;<a href="http://wordpress.org/extend/plugins/role-scoper/changelog/" target="_blank">' . __('View full changelog', 'scoper') . '</a>';

			if ( version_compare( strval($theVersion), '1.0.0', '>=' ) ) {
				$url = awp_plugin_update_url( SCOPER_BASENAME );
				$msg .= '&nbsp;&nbsp;&nbsp;<a href="' . $url . '">' . __awp('Upgrade Automatically') . '</a>';
			} else
				$msg .= '&nbsp;&nbsp;&nbsp;<a href="http://agapetry.net/downloads/role-scoper_current" target="_blank">' . __('Download for manual install', 'scoper') . '</a>';

			// slick method copied from NextGEN Gallery plugin
			add_action('admin_notices', create_function('', 'echo \'<div id="rs-ver_msg" class="plugin-update rs-ver_msg fade" style="margin:0;"><p>' . $msg . '</p></div>\';'));
		}
	}
	
	if($vcheck_ext)
	{
		$plugin_titles = array();
		$plugin_links = array();
		if ( $extensions = explode(';', $vcheck_ext) ) {
			foreach ( $extensions as $ext ) {
				if ( $ext_info = explode( ',', $ext ) ) {
					if ( count($ext_info) < 4 )
						continue;

					if ( ( $plugin_file = awp_is_plugin_active($ext_info[0]) ) && ! awp_is_plugin_active($ext_info[1]) ) {
						$plugin_path = WP_CONTENT_DIR . '/plugins/' . $plugin_file;
						if ( file_exists($plugin_path) ) {
							$plugin_data = implode( '', file( $plugin_path ));
							
							preg_match( '|Plugin Name:(.*)$|mi', $plugin_data, $name );

							if ( $name ) {
								$name = trim($name[1]);
								$plugin_titles [$ext_info[0]]= $name;

								if ( ( false === strpos($ext_info[3], 'wp_repository') ) || ( false !== strpos($ext_info[3], 'is_alpha') ) )
									$plugin_links[$ext_info[0]] = "http://agapetry.net/category/role-scoper-extensions/";
								else 
									$plugin_links[$ext_info[0]] = awp_plugin_info_url($ext_info[2]);
							}
						}
					}
				}
			}
			
			$plugins = get_option('active_plugins');

			if ( $plugin_titles ) {
				$plugin_array = array();
				foreach ( $plugin_titles as $name => $title )
					$plugin_array []= "<a href=\"{$plugin_links[$name]}\">$title</a>";

				$msg = '<strong>' . sprintf(__( "Role Scoper Extensions are available for the following plugins: %s", "scoper" ), implode(', ', $plugin_array) ) . '</strong><br />';

				// slick method copied from NextGEN Gallery plugin
				add_action('admin_notices', create_function('', 'echo \'<div id="rs-ext_ver_msg" class="plugin-update rs-ver_msg fade"><p>' . $msg . '</p></div>\';'));
			}
		}
	}
}

?>