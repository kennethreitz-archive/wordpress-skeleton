<?php
/*
Plugin Name: PS Disable Auto Formatting
Plugin URI: http://www.web-strategy.jp/wp_plugin/ps_disable_auto_formatting/
Description: PS Disable Auto Formatting is able to disable function auto formatting (wpautop) and save &lt;p&gt; and &lt;br /&gt; formatted content.
Version: 1.0.3
Author: Hitoshi Omagari
Author URI: http://www.web-strategy.jp/
*/

class ps_disable_auto_formatting {
	
var $setting_items = array(
	'content formatting' => 'the_content',
	'comment formatting' => 'comment_text',
	'excerpt formatting' => 'the_excerpt',
	'term description formatting' => 'term_description',
);

var $mce_version = '20080121';

function __construct() {
	global $wp_version;

	if ( version_compare( $wp_version, '2.5', '>=' ) ) {
		add_action( 'init', array( &$this, 'disable_auto_formatting_init' ) );
		add_action( 'admin_menu', array( &$this, 'add_disable_formatting_setting_page') );
		add_filter( 'print_scripts_array', array( &$this, 'rewrite_default_script' ) );
		add_filter( 'wp_insert_post_data', array( &$this, 'formatting_quickpress_post' ) );
		add_action( 'media_buttons', array( &$this, 'check_edit_mode_and_add_ichedit_pre' ), 9 );
		add_action( 'media_buttons', array( &$this, 'delete_filtering_wp_richedit_pre' ) );
	} else {
		add_action('admin_notices', array( &$this, 'version_too_old' ) );
	}
}


function ps_disable_auto_formatting() {
	$this->__construct();
}


function disable_auto_formatting_init() {
	$locale = get_locale();
	$lang_file = dirname( __file__ ) . '/language/ps_disable_auto_formatting-' . $locale . '.mo';

	if ( file_exists( $lang_file ) ) {
		load_textdomain( 'ps_disable_auto_formatting', $lang_file );
	}
	
	$this->option_settings = get_option( 'ps_disable_auto_formatting' );
	
	if ( $this->option_settings === false ) {
		$this->set_default_settings();
		$this->option_settings = get_option( 'ps_disable_auto_formatting' );
	} elseif ( ! $this->option_settings ) {
		$this->option_settings = array();
	}
	$this->delete_default_filters();
}


function delete_default_filters() {
	global $wp_filter;

	foreach ( $this->option_settings as $hook ) {
		if ( $hook == 'comment_text' ) {
			$priority = 30;
		} else {
			$priority = 10;
		}
		remove_filter( $hook, 'wpautop', $priority );
		if ( $hook == 'the_content' ) {
			foreach ( array_keys( $wp_filter['the_content'][10] ) as $hook_name ) {
				if ( strpos( $hook_name, 'tam_contact_form_sevenwpautop_substitute' ) !== false ) {
					remove_filter( 'the_content', $hook_name );
				}
			}
		}
	}
}


function set_default_settings() {
	$default = array( 'the_content' );
	update_option( 'ps_disable_auto_formatting', $default );
}


function rewrite_default_script( $todo ) {
	global $wp_version, $wp_scripts;

	if ( version_compare( $wp_version, '2.8', '>=' ) ) {
		$scripyt_src = get_option( 'siteurl' ) . '/' . str_replace( str_replace( '\\', '/', ABSPATH ), '', str_replace( '\\', '/', dirname( __file__ ) ) ) . '/js/280/ps_editor.js';
	} elseif ( version_compare( $wp_version, '2.7', '>=' ) ) {
		$scripyt_src = get_option( 'siteurl' ) . '/' . str_replace( str_replace( '\\', '/', ABSPATH ), '', str_replace( '\\', '/', dirname( __file__ ) ) ) . '/js/270/ps_editor.js';
	} else {
		$scripyt_src = get_option( 'siteurl' ) . '/' . str_replace( str_replace( '\\', '/', ABSPATH ), '', str_replace( '\\', '/', dirname( __file__ ) ) ) . '/js/250/ps_editor.js';
		if ( version_compare( $wp_version, '2.6', '>=' ) ) {
			$wp_scripts->registered['editor_functions']->src = $scripyt_src;
		} else {
			$wp_scripts->scripts['editor_functions']->src = $scripyt_src;
		}
	}
	$wp_scripts->add( 'ps_editor', $scripyt_src, false, $this->mce_version );
	$key = array_search( 'editor', $todo );
	if ( $key !== false ) {
		if ( version_compare( $wp_version, '2.7', '>=' ) ) {
			$todo[$key] = 'ps_editor';
		} else {
			unset( $todo[$key] );
		}
	}
	return $todo;
}


function formatting_quickpress_post( $data ) {
	global $action;

	if ( in_array( $action, array( 'post-quickpress-publish', 'post-quickpress-save' ) ) ) {
		if ( empty( $_POST['quickpress_post_ID'] ) ) {
			$data['post_content'] = wpautop( $data['post_content'] );
		}
	}
	return $data;
}


function delete_filtering_wp_richedit_pre() {
	remove_filter( 'the_editor_content', 'wp_richedit_pre' );
}


function check_edit_mode_and_add_ichedit_pre() {
	global $wp_filter;
	if ( isset( $wp_filter['the_editor_content'][10]['wp_richedit_pre'] ) ) {
		add_filter( 'the_editor_content', array( &$this, 'ps_richedit_pre' ) );
	}
}


function ps_richedit_pre( $text ) {
	if ( empty($text) ) return apply_filters('richedit_pre', '');

	$output = convert_chars($text);
	$output = htmlspecialchars($output, ENT_NOQUOTES);

	return apply_filters('richedit_pre', $output);
}


function add_disable_formatting_setting_page() {
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page( 'PS Disable Auto Formatting',
				__( 'Auto Formatting', 'ps_disable_auto_formatting' ),
				8,
				basename( __FILE__ ),
				array( &$this, 'output_disable_formatting_setting_page') );
		}
}


function output_disable_formatting_setting_page() {
	global $wpdb, $wp_error;
	if( $_POST['_wpnonce'] ) {
		check_admin_referer();
		
		if ( $_POST['batch_formatting'] ) {
			if ( $_POST['allow_batch_formatting'] ) {
				$time_limit = sprintf( '%04d-%02d-%02d %02d:%02d:00', $_POST['aa'], $_POST['mm'], $_POST['jj'], $_POST['hh'], $_POST['mn'] );
				$sql = "
SELECT	`ID`
FROM	$wpdb->posts
WHERE	`post_status` IN ( 'publish', 'draft', 'pending' )
AND		`post_type` IN ( 'post', 'page' )
AND		`post_modified` < '$time_limit'
";
				$formatting_posts =  $wpdb->get_results( $sql, ARRAY_A );
				$formatted_posts = array();

				if ( $formatting_posts ) {
					foreach ( $formatting_posts as $row ) {
						$data = array();
						$post = get_post( $row['ID'] );
						$data['post_content'] = wpautop( $post->post_content );
						if ( $post->post_content_filtered ) {
							$data['post_content_filtered'] = wpautop( $post->post_content_filtered );
						}
						$data['post_modified_gmt'] = current_time( 'mysql', 1 );
						$data['post_modified'] = current_time( 'mysql' );

						do_action( 'pre_post_update', $post->ID );
						if ( false === $wpdb->update( $wpdb->posts, $data, array( 'ID' => $post->ID ) ) ) {
							if ( $wp_error ) {
								$error_mes = new WP_Error('db_update_error', __('Could not update post in the database'), $wpdb->last_error);
								break;
							} else {
								$error_mes = __( 'Database is not found.', 'ps_disable_auto_formatting' );
								break;
							}
						}
						$formatted_posts[] = $row['ID'];
					}
					if ( ! $error_mes ) {
						$batch_ret = true;
					}
				} else {
					$error_mes = __( 'No formatting post or page exists.', 'ps_disable_auto_formatting' );
				}
			} else {
				$error_mes = __( 'Require checked allow batch formatting.', 'ps_disable_auto_formatting' );
			}
		} else {
			foreach ( $_POST['ps_disable_auto_formatting'] as $key => $func ) {
				if ( ! in_array( $func, $this->setting_items) ) {
					unset( $_POST['ps_disable_auto_formatting'][$key] );
				}
			}
			$ret = update_option( 'ps_disable_auto_formatting', $_POST['ps_disable_auto_formatting'] );
			if ( $ret ) {
				$this->option_settings = get_option( 'ps_disable_auto_formatting' );
			}
		}
	}

	?>
		<div class=wrap>
			<?php if ( function_exists( 'screen_icon' ) ) { screen_icon(); } ?>
			<h2><?php _e( 'Auto Formatting', 'ps_disable_auto_formatting' ); ?></h2>
			<?php if ( $ret ) { ?>
			<div id="message" class="updated">
				<p><?php _e('The settings has changed successfully.', 'ps_disable_auto_formatting' );?></p>
			</div>
			<?php } elseif ( $batch_ret ) { ?>
			<div id="message" class="updated">
				<p><?php printf( __( 'Batch fomatting process has completed. total %d posts formatted.', 'ps_disable_auto_formatting' ), count( $formatting_posts ) );?></p>
			</div>
			<?php } elseif ( $error_mes ) { ?>
			<div id="notice" class="error">
				<p><?php echo wp_specialchars( $error_mes ); ?></p>
			</div>
			<?php } elseif ( $_POST['ps_disable_auto_formatting'] && ! $ret ) { ?>
			<div id="notice" class="error">
				<p><?php _e('The settings has not been changed. There were no changes or failed to update the data base.', 'ps_disable_auto_formatting' );?></p>
			</div>
			<?php } ?>
			<form method="post" action="">
				<?php wp_nonce_field(); ?>
				<table class="form-table">
<?php foreach( $this->setting_items as $id => $func ) { ?>
					<tr>
						<th><?php _e( $id, 'ps_disable_auto_formatting' ); ?></th>
						<td>
							<input type="checkbox" id="ps_disable_auto_formatting_<?php echo $func ?>" name="ps_disable_auto_formatting[]" value="<?php echo $func ?>"<?php if ( in_array( $func, $this->option_settings ) ) { echo ' checked="checked"'; } ?> />
							<label for="ps_disable_auto_formatting_<?php echo $func ?>"><?php _e( 'disable', 'ps_disable_auto_formatting' ); ?></label>
						</td>
					</tr>
<?php } ?>
				</table>
				<p class="submit">
					<input type="submit" name="ps_disable_auto_formatting_submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" />
				</p>
<?php if ( current_user_can( 'edit_post' ) && current_user_can( 'edit_page' ) ) { ?>
				<h3><?php _e( 'Batch formatting for past posts' ,'ps_disable_auto_formatting' ); ?></h3>
				<?php _e( '<p>To make it display the same as the format before run this plug-in,
automatic operation process to the specified period of the posts.<br />
Even if some unexpected errors occur, the data is restorable because
rivision on the processed post is made.<br />
This process is safe even if you do two or more times, perhaps. We cannot assure though.<br />
* It is strongly recommended to take the <a href="http://codex.wordpress.org/Backing_Up_Your_Database" title="Backing Up Your Database">backup your database</a> before processing.</p>' ,'ps_disable_auto_formatting' ); ?>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Formatting before' ,'ps_disable_auto_formatting' ); ?></th>
						<td>
							<?php touch_time( 0, 0, 0, 1 ); ?><br />
							<?php _e( '* Formatting posts and pages are modified before this time.' ,'ps_disable_auto_formatting' ); ?>
						</td>
					</tr>
				</table>
				<div>
					<span class="submit"><input type="submit" name="batch_formatting" value="<?php _e( 'Batch formatting', 'ps_disable_auto_formatting' ); ?>" /></span>
					&nbsp;&nbsp;&nbsp;<input type="checkbox" id="allow_batch_formatting" name="allow_batch_formatting" value="1" />
					<label for="allow_batch_formatting"><?php _e( 'Allow batch formatting', 'ps_disable_auto_formatting' ); ?></label>
				</div>
<?php } ?>
			</form>
			<p><?php _e( 'If you have any problems or find a bug in this plugin, please <a href="http://www.web-strategy.jp/wp_plugin/ps_disable_auto_formatting/#postcomment">report to us</a>.' , 'ps_disable_auto_formatting' ); ?></p>
		</div>
	<?php
}


function version_too_old() {
	global $wp_version;
	echo '<div class="updated fade"><p>' . sprintf( __( 'Sorry, Your WordPress (version %s) is old to use PS Disable Auto Formatting plugin. Please upgrade to version 2.5 or higher.', 'ps_disable_auto_formatting' ), $wp_version ) . '</p></div>';
	$active_plugins = get_option('active_plugins');
	$search_plugin = str_replace( str_replace( '\\', '/', ABSPATH . PLUGINDIR . '/' ), '', str_replace( '\\', '/', __file__ ) );
	$key = array_search( $search_plugin, $active_plugins );
	if ( $key !== false ) {
		unset( $active_plugins[$key] );
	}
	update_option( 'active_plugins', $active_plugins );
}

} // class end

$ps_disable_auto_formatting =& new ps_disable_auto_formatting();
