<?php

function jkf_tml_custom_pass_form() {
    global $theme_my_login;
    ?>
    <p><label for="pass1-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Password:', 'theme-my-login');?></label>
    <input autocomplete="off" name="pass1" id="pass1-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" size="20" value="" type="password" /></p>
    <p><label for="pass2-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Confirm Password:', 'theme-my-login');?></label>
    <input autocomplete="off" name="pass2" id="pass2-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" size="20" value="" type="password" /></p>
<?php
}

function jkf_tml_custom_pass_errors($errors = '') {
    if ( empty($_POST['pass1']) || $_POST['pass1'] == '' || empty($_POST['pass2']) || $_POST['pass2'] == '' ) {
        $errors->add('empty_password', __('<strong>ERROR</strong>: Please enter a password.'));
    } elseif ( $_POST['pass1'] !== $_POST['pass2'] ) {
        $errors->add('password_mismatch', __('<strong>ERROR</strong>: Your passwords do not match.'));
    } elseif ( strlen($_POST['pass1']) < 6 ) {
        $errors->add('password_length', __('<strong>ERROR</strong>: Your password must be at least 6 characters in length.'));
    } else {
        $_POST['user_pw'] = $_POST['pass1'];
    }	
    return $errors;
}

function jkf_tml_custom_pass_set_pass($user_pass) {
    if ( isset($_POST['user_pw']) && !empty($_POST['user_pw']) )
        $user_pass = $_POST['user_pw'];
    return $user_pass;
}

function jkf_tml_custom_pass_reset_action() {
	global $theme_my_login;
	
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
		$theme_my_login->errors = jkf_tml_custom_pass_reset_pass();
        if ( ! is_wp_error($theme_my_login->errors) ) {
            $redirect_to = site_url('wp-login.php?resetpass=complete');
            if ( 'tml-page' != $theme_my_login->request_instance )
                $redirect_to = jkf_tml_get_current_url('resetpass=complete&instance=' . $theme_my_login->request_instance);
            wp_redirect($redirect_to);
            exit();
        }		
	}
	
	$user = jkf_tml_custom_pass_validate_reset_key($_GET['key'], $_GET['login']);
	if ( is_wp_error($user) ) {
       $redirect_to = site_url('wp-login.php?action=lostpassword&error=invalidkey');
        if ( 'tml-page' != $theme_my_login->request_instance )
            $redirect_to = jkf_tml_get_current_url('action=lostpassword&error=invalidkey&instance=' . $theme_my_login->request_instance);
        wp_redirect($redirect_to);
        exit();
	}
}

function jkf_tml_custom_pass_reset_form() {
	global $theme_my_login;
	
	$message = apply_filters('resetpass_message', __('Please enter a new password.', 'theme-my-login'));
	
	jkf_tml_get_header($message);
	
	if ( !$theme_my_login->errors->get_error_message('invalid_key') ) {
	?>
    <form name="resetpasswordform" id="resetpasswordform-<?php echo $theme_my_login->current_instance['instance_id']; ?>" action="<?php echo esc_url(jkf_tml_get_current_url('action=rp&key=' . $_GET['key'] . '&login=' . $_GET['login'] . '&instance=' . $theme_my_login->current_instance['instance_id'])); ?>" method="post">
		<p>
			<label for="pass1-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('New Password:', 'theme-my-login');?></label>
			<input autocomplete="off" name="pass1" id="pass1-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" size="20" value="" type="password" />
		</p>
		<p>
			<label for="pass2-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Confirm Password:', 'theme-my-login');?></label>
			<input autocomplete="off" name="pass2" id="pass2-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" size="20" value="" type="password" />
		</p>
        <?php do_action('resetpassword_form', $theme_my_login->current_instance['instance_id']); ?>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit-<?php echo $theme_my_login->current_instance['instance_id']; ?>" value="<?php _e('Change Password', 'theme-my-login'); ?>" />
        </p>
    </form>
<?php
	}
	jkf_tml_get_footer(true, true, false);
}

function jkf_tml_custom_pass_validate_reset_key($key, $login) {
    global $wpdb;

    $key = preg_replace('/[^a-z0-9]/i', '', $key);

    if ( empty( $key ) || !is_string( $key ) )
        return new WP_Error('invalid_key', __('Invalid key'));

    if ( empty($login) || !is_string($login) )
        return new WP_Error('invalid_key', __('Invalid key'));

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));
    if ( empty( $user ) )
        return new WP_Error('invalid_key', __('Invalid key'));
		
	return $user;
}

function jkf_tml_custom_pass_reset_pass() {
    global $theme_my_login;
	
	$user = jkf_tml_custom_pass_validate_reset_key($_REQUEST['key'], $_REQUEST['login']);
	if ( is_wp_error($user) )
		return $user;
	
	$errors = jkf_tml_custom_pass_errors(new WP_Error());
	if ( $errors->get_error_code() )
		return $errors;
	
	$new_pass = $_POST['pass1'];

    do_action('password_reset', $user->user_login, $new_pass);

    wp_set_password($new_pass, $user->ID);
	update_usermeta($user->ID, 'default_password_nag', false);
    $message  = sprintf(__('Username: %s'), $user->user_login) . "\r\n";
    $message .= sprintf(__('Password: %s'), $new_pass) . "\r\n";
    $message .= site_url('wp-login.php', 'login') . "\r\n";

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    $title = sprintf(__('[%s] Your new password'), $blogname);

    $title = apply_filters('password_reset_title', $title, $user);
    $message = apply_filters('password_reset_message', $message, $new_pass, $user);

    if ( $message && !wp_mail($user->user_email, $title, $message) )
		die('<p>' . __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...') . '</p>');

    wp_password_change_notification($user);

    return true;
}

function jkf_tml_custom_pass_register_redirect($redirect_to) {
	global $theme_my_login;
	$redirect_to = site_url('wp-login.php?registration=complete');
	if ( 'tml-page' != $theme_my_login->request_instance )
		$redirect_to = jkf_tml_get_current_url('registration=complete&instance=' . $theme_my_login->request_instance);	
	return $redirect_to;
}

function jkf_tml_custom_pass_resetpass_redirect($redirect_to) {
	global $theme_my_login;
	$redirect_to = site_url('wp-login.php?resetpass=complete');
	if ( 'tml-page' != $theme_my_login->request_instance )
		$redirect_to = jkf_tml_get_current_url('resetpass=complete&instance=' . $theme_my_login->request_instance);	
	return $redirect_to;
}

function jkf_tml_custom_pass_login_message($message) {
	if ( isset($_GET['action']) && 'register' == $_GET['action'] )
		$message = '';
	elseif ( isset($_GET['registration']) && 'complete' == $_GET['registration'] )
		$message = __('Registration complete. You may now log in.', 'theme-my-login');
	elseif ( isset($_GET['resetpass']) && 'complete' == $_GET['resetpass'] )
		$message = __('Your password has been saved. You may now log in.', 'theme-my-login');
	return $message;
}

?>