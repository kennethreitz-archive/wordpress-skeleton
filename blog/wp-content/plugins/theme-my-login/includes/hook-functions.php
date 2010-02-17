<?php

function jkf_tml_the_title($title, $post_id = '') {
    global $theme_my_login;
    if ( is_admin() )
        return $title;
    if ( $theme_my_login->options['page_id'] == $post_id ) {
        require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/template-functions.php');
        $action = ( 'tml-page' == $theme_my_login->request_instance ) ? $theme_my_login->request_action : 'login';
        $title = jkf_tml_get_title($action);
    }
    return $title;
}

function jkf_tml_single_post_title($title) {
    global $theme_my_login;
    if ( is_page($theme_my_login->options['page_id']) ) {
        require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/template-functions.php');
        $action = ( 'tml-page' == $theme_my_login->request_instance ) ? $theme_my_login->request_action : 'login';
        $title = jkf_tml_get_title($action);
    }
    return $title;
}

function jkf_tml_site_url($url, $path, $orig_scheme) {
    global $theme_my_login;
    if ( strpos($url, 'wp-login.php') !== false && !isset($_REQUEST['interim-login']) ) {
        $orig_url = $url;
        $url = get_permalink($theme_my_login->options['page_id']);
        if ( strpos($orig_url, '?') ) {
            $query = substr($orig_url, strpos($orig_url, '?') + 1);
            parse_str($query, $r);
            $url = add_query_arg($r, $url);
        }
    }
    return $url;
}

function jkf_tml_list_pages_excludes($exclude_array) {
	global $theme_my_login;
	if ( !$theme_my_login->options['show_page'] )
		$exclude_array[] = $theme_my_login->options['page_id'];
	return $exclude_array;
}

function jkf_tml_page_link($link, $id) {
	global $theme_my_login;
	if ( $id == $theme_my_login->options['page_id'] ) {
		if ( is_user_logged_in() )
			$link = wp_nonce_url(add_query_arg('action', 'logout', $link), 'log-out');
	}
	return $link;
}

function jkf_tml_get_pages($pages, $attributes) {
	global $theme_my_login;
	if ( is_admin() )
		return $pages;
	// It sucks there's not really a better way to do this
	if ( $theme_my_login->options['show_page'] ) {
		foreach ( $pages as $page ) {
			if ( $page->ID == $theme_my_login->options['page_id'] ) {
				if ( is_user_logged_in() )
					$page->post_title = __('Log out');
				else
					$page->post_title = __('Log In');
			}
		}
	}
	return $pages;
}

function jkf_tml_shortcode($atts = '') {
    global $theme_my_login;

    require_once (WP_PLUGIN_DIR . '/theme-my-login/includes/template-functions.php');
    
    if ( empty($atts['instance_id']) )
        $atts['instance_id'] = jkf_tml_get_instance();

    if ( $theme_my_login->request_instance == $atts['instance_id'] )
        $atts['is_active'] = 1;

    $theme_my_login->current_instance = shortcode_atts(jkf_tml_get_display_options(), $atts);
    return jkf_tml_display();
}

?>