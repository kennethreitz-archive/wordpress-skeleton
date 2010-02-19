<?php

add_filter('the_content', 'scoper_teaser_login');


function scoper_teaser_login( $content ) {

global $user_login;

// login form
$output = '<form action="'.get_bloginfo('wpurl').'/wp-login.php" method="post" >';     
	$output .= '<p><label for="user_login">'.__('Username:', 'user-access-manager').'<input name="log" value="'.wp_specialchars(stripslashes($user_login), 1).'" class="input" id="user_login" type="text" /></label></p>';
	$output .= '<p><label for="user_pass">'.__('Password:', 'user-access-manager').'<input name="pwd" class="imput" id="user_pass" type="password" /></label></p>';
	$output .= '<p class="forgetmenot"><label for="rememberme"><input name="rememberme" class="checkbox" id="rememberme" value="forever" type="checkbox" /> '. __('Remember me', 'user-access-manager').'</label></p>';
	$output .= '<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" value="'.__('Login', 'user-access-manager').' &raquo;" />';
	$output .= '<input type="hidden" name="redirect_to" value="' . $_SERVER['REQUEST_URI'] . '" />';
$output .= '</form>';

$output .= '<p>';
	if (get_option('users_can_register'))
		$output .= '<a href="'.get_bloginfo('wpurl').'/wp-login.php?action=register">'.__('Register', 'user-access-manager').'</a></br>';
	$output .= '<a href="'.get_bloginfo('wpurl').'/wp-login.php?action=lostpassword" title="'. __('Password Lost and Found', 'user-access-manager').'">'. __('Lost your password?', 'user-access-manager').'</a>';
$output .= '</p>';
		

	return $content;
}
			
?>