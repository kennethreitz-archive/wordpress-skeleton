<?php

$theme_my_login->errors = new WP_Error();

// validate action so as to default to the login screen
if ( !in_array($theme_my_login->request_action, array('logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login'), true) && false === has_filter('login_action_' . $theme_my_login->request_action) )
    $theme_my_login->request_action = 'login';

//Set a cookie now to see if they are supported by the browser.
setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
if ( SITECOOKIEPATH != COOKIEPATH )
    setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);

// allow plugins to override the default actions, and to add extra actions if they want
if ( has_filter('login_action_' . $theme_my_login->request_action) ) :

do_action('login_action_' . $theme_my_login->request_action);

else :

$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
switch ( $theme_my_login->request_action ) {
    case 'logout' :
        check_admin_referer('log-out');

        $user = wp_get_current_user();

        $redirect_to = site_url('wp-login.php?loggedout=true');
        $redirect_to = apply_filters('logout_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user);

        wp_logout();

        wp_safe_redirect($redirect_to);
        exit();
        break;
    case 'lostpassword' :
    case 'retrievepassword' :
        if ( $http_post ) {
            require_once(WP_PLUGIN_DIR . '/theme-my-login/includes/login-functions.php');
            $theme_my_login->errors = retrieve_password();
            if ( !is_wp_error($theme_my_login->errors) ) {
                $redirect_to = site_url('wp-login.php?checkemail=confirm');
                if ( 'tml-page' != $theme_my_login->request_instance )
                    $redirect_to = jkf_tml_get_current_url('checkemail=confirm&instance=' . $theme_my_login->request_instance);
                wp_redirect($redirect_to);
                exit();
            }
        }

        if ( isset($_REQUEST['error']) && 'invalidkey' == $_REQUEST['error'] ) $theme_my_login->errors->add('invalidkey', __('Sorry, that key does not appear to be valid.'));
        break;
    case 'resetpass' :
    case 'rp' :
        require_once(WP_PLUGIN_DIR . '/theme-my-login/includes/login-functions.php');
        $theme_my_login->errors = reset_password($_GET['key'], $_GET['login']);

        if ( ! is_wp_error($theme_my_login->errors) ) {
            $redirect_to = site_url('wp-login.php?checkemail=newpass');
            if ( 'tml-page' != $theme_my_login->request_instance )
                $redirect_to = jkf_tml_get_current_url('checkemail=newpass&instance=' . $theme_my_login->request_instance);
			$redirect_to = apply_filters('resetpass_redirect', $redirect_to);
            wp_redirect($redirect_to);
            exit();
        }

        $redirect_to = site_url('wp-login.php?action=lostpassword&error=invalidkey');
        if ( 'tml-page' != $theme_my_login->request_instance )
            $redirect_to = jkf_tml_get_current_url('action=lostpassword&error=invalidkey&instance=' . $theme_my_login->request_instance);
        wp_redirect($redirect_to);
        exit();
        break;
    case 'register' :
        if ( !get_option('users_can_register') ) {
            wp_redirect(jkf_tml_get_current_url('registration=disabled'));
            exit();
        }

        $user_login = '';
        $user_email = '';
        $user_pass = '';
        if ( $http_post ) {
            require_once(ABSPATH . WPINC . '/registration.php');
            require_once(WP_PLUGIN_DIR . '/theme-my-login/includes/login-functions.php');

            $user_login = $_POST['user_login'];
            $user_email = $_POST['user_email'];
            if ( $options['custom_pass'] && isset($_POST['pass1']) && '' != $_POST['pass1'] )
                $user_pass = stripslashes($_POST['pass1']);
            $theme_my_login->errors = register_new_user($user_login, $user_email);
            if ( !is_wp_error($theme_my_login->errors) ) {
				$redirect_to = site_url('wp-login.php?checkemail=registered');
				if ( 'tml-page' != $theme_my_login->request_instance )
					$redirect_to = jkf_tml_get_current_url('checkemail=registered&instance=' . $theme_my_login->request_instance);
				$redirect_to = apply_filters('register_redirect', $redirect_to);
                wp_redirect($redirect_to);
                exit();
            }
        }
        break;
    case 'login' :
    default:
        $secure_cookie = '';

        // If the user wants ssl but the session is not ssl, force a secure cookie.
        if ( !empty($_POST['log']) && !force_ssl_admin() ) {
            $user_name = sanitize_user($_POST['log']);
            if ( $user = get_userdatabylogin($user_name) ) {
                if ( get_user_option('use_ssl', $user->ID) ) {
                    $secure_cookie = true;
                    force_ssl_admin(true);
                }
            }
        }

        if ( isset($_REQUEST['redirect_to']) && !empty($_REQUEST['redirect_to']) ) {
            $theme_my_login->redirect_to = $_REQUEST['redirect_to'];
            // Redirect to https if user wants ssl
            if ( $secure_cookie && false !== strpos($theme_my_login->redirect_to, 'wp-admin') )
                $theme_my_login->redirect_to = preg_replace('|^http://|', 'https://', $theme_my_login->redirect_to);
        } else {
            $theme_my_login->redirect_to = admin_url();
        }

        if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($theme_my_login->redirect_to, 'https') ) && ( 0 === strpos($theme_my_login->redirect_to, 'http') ) )
            $secure_cookie = false;

        $user = wp_signon('', $secure_cookie);

        $theme_my_login->redirect_to = apply_filters('login_redirect', $theme_my_login->redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user);

        if ( !is_wp_error($user) ) {
            // If the user can't edit posts, send them to their profile.
            if ( !$user->has_cap('edit_posts') && ( empty( $theme_my_login->redirect_to ) || $theme_my_login->redirect_to == 'wp-admin/' || $theme_my_login->redirect_to == admin_url() ) )
                $theme_my_login->redirect_to = admin_url('profile.php');
            wp_safe_redirect($theme_my_login->redirect_to);
            exit();
        }

        $theme_my_login->errors = $user;
        break;
}

endif;

?>
