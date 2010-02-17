<?php

function jkf_tml_display() {
    global $theme_my_login;

    $action = isset($theme_my_login->current_instance['default_action']) ? $theme_my_login->current_instance['default_action'] : 'login';
    if ( $theme_my_login->request_instance == $theme_my_login->current_instance['instance_id'] )
        $action = $theme_my_login->request_action;

    ob_start();
    echo $theme_my_login->current_instance['before_widget'];
    if ( $theme_my_login->current_instance['show_title'] )
        echo $theme_my_login->current_instance['before_title'] . jkf_tml_get_title($action) . $theme_my_login->current_instance['after_title'] . "\n";
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $user_role = reset($user->roles);
        if ( $theme_my_login->current_instance['show_gravatar'] )
            echo '<div class="tml-user-avatar">' . get_avatar( $user->ID, $theme_my_login->current_instance['gravatar_size'] ) . '</div>' . "\n";
        echo '<ul class="tml-user-links">' . "\n";
        $user_links = array(
            array('title' => __('Dashboard'), 'url' => admin_url()),
            array('title' => __('Profile'), 'url' => admin_url('profile.php'))
            );
        $user_links = apply_filters('tml_user_links', $user_links);
        if ( $user_links ) {
            foreach ( $user_links as $link_data ) {
                echo '<li><a href="' . $link_data['url'] . '">' . $link_data['title'] . '</a></li>' . "\n";
            }
        }
        echo '<li><a href="' . wp_logout_url() . '">' . __('Log out') . '</a></li>' . "\n" . '</ul>' . "\n";
    } else {
		if ( has_filter('login_form_' . $action) ) {
			do_action('login_form_' . $action);
		} else {
			switch ( $action ) {
				case 'lostpassword' :
				case 'retrievepassword' :
					jkf_tml_get_lost_password_form();
					break;
				case 'register' :
					jkf_tml_get_register_form();
					break;
				case 'login' :
				default :
					jkf_tml_get_login_form();
                break;
			}
        }
    }
    echo $theme_my_login->current_instance['after_widget'] . "\n";
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}

function jkf_tml_get_display_options() {
    $display_options = array(
        'instance_id' => 'tml-page',
        'is_active' => 0,
        'default_action' => 'login',
        'show_title' => 1,
        'show_log_link' => 1,
        'show_reg_link' => 1,
        'show_pass_link' => 1,
        'register_widget' => 0,
        'lost_pass_widget' => 0,
        'logged_in_widget' => 1,
        'show_gravatar' => 1,
        'gravatar_size' => 50,
        'before_widget' => '<li>',
        'after_widget' => '</li>',
        'before_title' => '<h2>',
        'after_title' => '</h2>'
        );
    return apply_filters('tml_display_options', $display_options);
}

function jkf_tml_get_title($action = '') {
    if ( empty($action) )
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $title = sprintf(__('Welcome, %s', 'theme-my-login'), $user->display_name);
    } else {
        switch ( $action ) {
            case 'register':
                $title = __('Register');
                break;
            case 'lostpassword':
            case 'retrievepassword':
            case 'resetpass':
            case 'rp':
                $title = __('Lost Password');
                break;
            case 'login':
            default:
                $title = __('Log In');
        }
    }
    return apply_filters('tml_title', $title, $action);
}

function jkf_tml_get_header($message = '') {
    global $theme_my_login, $error;

    if ( empty($theme_my_login->errors) )
        $theme_my_login->errors = new WP_Error();

    echo '<div class="login" id="' . $theme_my_login->current_instance['instance_id'] . '">';

	$message = apply_filters('login_message', $message);
    if ( !empty($message) )
        echo '<p class="message">' . $message . "</p>\n";

    // Incase a plugin uses $error rather than the $errors object
    if ( !empty( $error ) ) {
        $theme_my_login->errors->add('error', $error);
        unset($error);
    }

    if ( $theme_my_login->current_instance['is_active'] ) {
        if ( $theme_my_login->errors->get_error_code() ) {
            $errors = '';
            $messages = '';
            foreach ( $theme_my_login->errors->get_error_codes() as $code ) {
                $severity = $theme_my_login->errors->get_error_data($code);
                foreach ( $theme_my_login->errors->get_error_messages($code) as $error ) {
                    if ( 'message' == $severity )
                        $messages .= '    ' . $error . "<br />\n";
                    else
                        $errors .= '    ' . $error . "<br />\n";
                }
            }
            if ( !empty($errors) )
                echo '<p class="error">' . apply_filters('login_errors', $errors) . "</p>\n";
            if ( !empty($messages) )
                echo '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
        }
    }
}

function jkf_tml_get_footer($login_link = true, $register_link = true, $password_link = true) {
    global $theme_my_login;
    
    echo '<ul class="tml-links">' . "\n";
    if ( $login_link && $theme_my_login->current_instance['show_log_link'] ) {
        $url = jkf_tml_get_current_url('instance=' . $theme_my_login->current_instance['instance_id']);
        echo '<li><a href="' . esc_url($url) . '">' . jkf_tml_get_title('login') . '</a></li>' . "\n";
    }
    if ( $register_link && $theme_my_login->current_instance['show_reg_link'] && get_option('users_can_register') ) {
        $url = ( $theme_my_login->current_instance['register_widget'] ) ? jkf_tml_get_current_url('action=register&instance=' . $theme_my_login->current_instance['instance_id']) : site_url('wp-login.php?action=register', 'login');
        echo '<li><a href="' . esc_url($url) . '">' . jkf_tml_get_title('register') . '</a></li>' . "\n";
    }
    if ( $password_link && $theme_my_login->current_instance['show_pass_link'] ) {
        $url = ( $theme_my_login->current_instance['lost_pass_widget'] ) ? jkf_tml_get_current_url('action=lostpassword&instance=' . $theme_my_login->current_instance['instance_id']) : site_url('wp-login.php?action=lostpassword', 'login');
        echo '<li><a href="' . esc_url($url) . '">' . jkf_tml_get_title('lostpassword') . '</a></li>' . "\n";
    }
    echo '</ul>' . "\n";
    echo '</div>' . "\n";
}

function jkf_tml_get_login_form() {
    global $theme_my_login;

    // Clear errors if loggedout is set.
    if ( !empty($_GET['loggedout']) )
        $theme_my_login->errors = new WP_Error();

    // If cookies are disabled we can't log in even with a valid user+pass
    if ( isset($_POST['testcookie']) && empty($_COOKIE[TEST_COOKIE]) )
        $theme_my_login->errors->add('test_cookie', __("<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use WordPress.", 'theme-my-login'));

    // Some parts of this script use the main login form to display a message
    if ( $theme_my_login->current_instance['is_active'] ) {
        if        ( isset($_GET['loggedout']) && TRUE == $_GET['loggedout'] )
            $theme_my_login->errors->add('loggedout', __('You are now logged out.'), 'message');
        elseif    ( isset($_GET['registration']) && 'disabled' == $_GET['registration'] )
            $theme_my_login->errors->add('registerdisabled', __('User registration is currently not allowed.'));
        elseif    ( isset($_GET['checkemail']) && 'confirm' == $_GET['checkemail'] )
            $theme_my_login->errors->add('confirm', __('Check your e-mail for the confirmation link.'), 'message');
        elseif    ( isset($_GET['checkemail']) && 'newpass' == $_GET['checkemail'] )
            $theme_my_login->errors->add('newpass', __('Check your e-mail for your new password.'), 'message');
        elseif    ( isset($_GET['checkemail']) && 'registered' == $_GET['checkemail'] )
            $theme_my_login->errors->add('registered', __('Registration complete. Please check your e-mail.'), 'message');
    }

    jkf_tml_get_header();

    if ( isset($_POST['log']) )
        $user_login = ( 'incorrect_password' == $theme_my_login->errors->get_error_code() || 'empty_password' == $theme_my_login->errors->get_error_code() ) ? attribute_escape(stripslashes($_POST['log'])) : '';

    $user_login = ( $theme_my_login->current_instance['is_active'] && isset($user_login) ) ? $user_login : '';

    if ( !isset($_GET['checkemail']) ||
        ( isset($_GET['checkemail']) && !$theme_my_login->current_instance['is_active'] ) ||
        ( !in_array( $_GET['checkemail'], array('confirm', 'newpass') ) && $theme_my_login->current_instance['is_active'] ) ||
        ( in_array( $_GET['checkemail'], array('confirm', 'newpass') ) && !$theme_my_login->current_instance['is_active'] ) ) {
        ?>
        <form name="loginform" id="loginform-<?php echo $theme_my_login->current_instance['instance_id']; ?>" action="<?php echo esc_url(jkf_tml_get_current_url('action=login&instance=' . $theme_my_login->current_instance['instance_id'])); ?>" method="post">
            <p>
                <label for="log-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Username') ?></label>
                <input type="text" name="log" id="log-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" value="<?php echo isset($user_login) ? $user_login : ''; ?>" size="20" />
            </p>
            <p>
                <label for="pwd-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Password') ?></label>
                <input type="password" name="pwd" id="pwd-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" value="" size="20" />
            </p>
        <?php do_action('login_form', $theme_my_login->current_instance['instance_id']); ?>
            <p class="forgetmenot"><input name="rememberme" type="checkbox" id="rememberme-<?php echo $theme_my_login->current_instance['instance_id']; ?>" value="forever" /> <label for="rememberme-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Remember Me'); ?></label></p>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit-<?php echo $theme_my_login->current_instance['instance_id']; ?>" value="<?php _e('Log In'); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($theme_my_login->redirect_to); ?>" />
                <input type="hidden" name="testcookie" value="1" />
            </p>
        </form>
        <?php
    }
    if ( $theme_my_login->current_instance['is_active'] && isset($_GET['checkemail']) && in_array( $_GET['checkemail'], array('confirm', 'newpass') ) )
        $login_link = true;
    else
        $login_link = false;
    jkf_tml_get_footer($login_link, true, true);
}

function jkf_tml_get_register_form() {
    global $theme_my_login;
    
    $user_login = isset($_POST['user_login']) ? $_POST['user_login'] : '';
    $user_email = isset($_POST['user_email']) ? $_POST['user_email'] : '';

    $message = apply_filters('register_message', __('A password will be e-mailed to you.'));

    jkf_tml_get_header($message);
    ?>
    <form name="registerform" id="registerform-<?php echo $theme_my_login->current_instance['instance_id']; ?>" action="<?php echo esc_url(jkf_tml_get_current_url('action=register&instance=' . $theme_my_login->current_instance['instance_id'])); ?>" method="post">
        <p>
            <label for="user_login-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Username') ?></label>
            <input type="text" name="user_login" id="user_login-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" value="<?php echo attribute_escape(stripslashes($user_login)); ?>" size="20" />
        </p>
        <p>
            <label for="user_email-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('E-mail') ?></label>
            <input type="text" name="user_email" id="user_email-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" value="<?php echo attribute_escape(stripslashes($user_email)); ?>" size="20" />
        </p>
        <?php do_action('register_form', $theme_my_login->current_instance['instance_id']); ?>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit-<?php echo $theme_my_login->current_instance['instance_id']; ?>" value="<?php _e('Register'); ?>" />
        </p>
    </form>
    <?php
    jkf_tml_get_footer(true, false, true);
}

function jkf_tml_get_lost_password_form() {
    global $theme_my_login;
    
    do_action('lost_password', $theme_my_login->current_instance['instance_id']);
    
    $message = apply_filters('lostpassword_message', __('Please enter your username or e-mail address. You will receive a new password via e-mail.'));
    
    jkf_tml_get_header($message);
    
    $user_login = isset($_POST['user_login']) ? stripslashes($_POST['user_login']) : '';
    ?>
    <form name="lostpasswordform" id="lostpasswordform-<?php echo $theme_my_login->current_instance['instance_id']; ?>" action="<?php echo esc_url(jkf_tml_get_current_url('action=lostpassword&instance=' . $theme_my_login->current_instance['instance_id'])); ?>" method="post">
        <p>
            <label for="user_login-<?php echo $theme_my_login->current_instance['instance_id']; ?>"><?php _e('Username or E-mail:') ?></label>
            <input type="text" name="user_login" id="user_login-<?php echo $theme_my_login->current_instance['instance_id']; ?>" class="input" value="<?php echo attribute_escape($user_login); ?>" size="20" />
        </p>
        <?php do_action('lostpassword_form', $theme_my_login->current_instance['instance_id']); ?>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit-<?php echo $theme_my_login->current_instance['instance_id']; ?>" value="<?php _e('Get New Password'); ?>" />
        </p>
    </form>
    <?php
    jkf_tml_get_footer(true, true, false);
}

?>
