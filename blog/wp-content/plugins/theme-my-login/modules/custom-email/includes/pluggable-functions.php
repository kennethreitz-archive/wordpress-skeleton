<?php

if ( !function_exists('wp_password_change_notification') ) :
/**
 * Notify the blog admin of a user changing password, normally via email.
 *
 * @since 2.7
 *
 * @param object $user User Object
 */
function wp_password_change_notification(&$user) {
	// send a copy of password change notification to the admin
	// but check to see if it's the admin whose password we're changing, and skip this
	if ( $user->user_email != get_option('admin_email') ) {
		$message = sprintf(__('Password Lost and Changed for user: %s'), $user->user_login) . "\r\n";
		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		$title = sprintf(__('[%s] Password Lost/Changed'), $blogname);
		
		$title = apply_filters('password_change_notification_title', $title, $user->ID);
		$message = apply_filters('password_change_notification_message', $message, $user->ID);
		
		@wp_mail(get_option('admin_email'), $title, $message);
	}
}
endif;

if ( !function_exists('wp_new_user_notification') ) :
/**
 * Notify the blog admin of a new user, normally via email.
 *
 * @since 2.0
 *
 * @param int $user_id User ID
 * @param string $plaintext_pass Optional. The user's plaintext password
 */
function wp_new_user_notification($user_id, $plaintext_pass = '') {
	$user = new WP_User($user_id);

	$user_login = stripslashes($user->user_login);
	$user_email = stripslashes($user->user_email);
	
	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your blog %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";
	
	$title = sprintf(__('[%s] New User Registration'), $blogname);
	
	$title = apply_filters('new_user_admin_notification_title', $title, $user_id);
	$message = apply_filters('new_user_admin_notification_message', $message, $user_id);

	@wp_mail(get_option('admin_email'), $title, $message);

	if ( empty($plaintext_pass) )
		return;

	$message  = sprintf(__('Username: %s'), $user_login) . "\r\n";
	$message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
	$message .= wp_login_url() . "\r\n";
	
	$title = sprintf(__('[%s] Your username and password'), $blogname);

	$title = apply_filters('new_user_notification_title', $title, $user_id);
	$message = apply_filters('new_user_notification_message', $message, $plaintext_pass, $user_id);
	
	wp_mail($user_email, $title, $message);

}
endif;

?>