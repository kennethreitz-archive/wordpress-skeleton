<?php

if ( !class_exists('Theme_My_Login_Widget') ) :
class Theme_My_Login_Widget extends WP_Widget {

    function Theme_My_Login_Widget(){
        $widget_ops = array('classname' => 'widget_theme_my_login', 'description' => __('A login form for your blog.', 'theme-my-login') );
        parent::WP_Widget('theme-my-login', __('Theme My Login', 'theme-my-login'), $widget_ops);
    }

    function widget($args, $instance){
        if ( is_user_logged_in() && !$instance['logged_in_widget'] )
            return;
        $args = array_merge($args, $instance);
        echo jkf_tml_shortcode($args);
    }

    function update($new_instance, $old_instance){
        $instance = $old_instance;
        $instance['default_action']     = in_array($new_instance['default_action'], array('login', 'register', 'lostpassword')) ? $new_instance['default_action'] : 'login';
        $instance['logged_in_widget']   = empty($new_instance['logged_in_widget']) ? false : true;
        $instance['show_title']         = empty($new_instance['show_title']) ? false : true;
        $instance['show_log_link']      = empty($new_instance['show_log_link']) ? false: true;
        $instance['show_reg_link']      = empty($new_instance['show_reg_link']) ? false: true;
        $instance['show_pass_link']     = empty($new_instance['show_pass_link']) ? false: true;
        $instance['show_gravatar']      = empty($new_instance['show_gravatar']) ? false : true;
        $instance['gravatar_size']      = absint($new_instance['gravatar_size']);
        $instance['register_widget']    = empty($new_instance['register_widget']) ? false : true;
        $instance['lost_pass_widget']   = empty($new_instance['lost_pass_widget']) ? false : true;
        return $instance;
    }

    function form($instance){
        $defaults = array(
            'default_action' => 'login',
            'logged_in_widget' => 1,
            'show_title' => 1,
            'show_log_link' => 1,
            'show_reg_link' => 1,
            'show_pass_link' => 1,
            'show_gravatar' => 1,
            'gravatar_size' => 50,
            'register_widget' => 1,
            'lost_pass_widget' => 1
            );

        $instance = wp_parse_args($instance, $defaults);
        $actions = array('login' => 'Login', 'register' => 'Register', 'lostpassword' => 'Lost Password');
        echo '<p>Default Action<br /><select name="' . $this->get_field_name('default_action') . '" id="' . $this->get_field_id('default_action') . '">';
        foreach ($actions as $action => $title) {
            $is_selected = ($instance['default_action'] == $action) ? ' selected="selected"' : '';
            echo '<option value="' . $action . '"' . $is_selected . '>' . $title . '</option>';
        }
        echo '</select></p>' . "\n";
        $is_checked = (empty($instance['logged_in_widget'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('logged_in_widget') . '" type="checkbox" id="' . $this->get_field_id('logged_in_widget') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('logged_in_widget') . '">' . __('Show When Logged In', 'theme-my-login') . '</label></p>' . "\n";
        $is_checked = (empty($instance['show_title'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('show_title') . '" type="checkbox" id="' . $this->get_field_id('show_title') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_title') . '">' . __('Show Title', 'theme-my-login') . '</label></p>' . "\n";
        $is_checked = (empty($instance['show_log_link'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('show_log_link') . '" type="checkbox" id="' . $this->get_field_id('show_log_link') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_log_link') . '">' . __('Show Login Link', 'theme-my-login') . '</label></p>' . "\n";
        $is_checked = (empty($instance['show_reg_link'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('show_reg_link') . '" type="checkbox" id="' . $this->get_field_id('show_reg_link') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_reg_link') . '">' . __('Show Register Link', 'theme-my-login') . '</label></p>' . "\n";
        $is_checked = (empty($instance['show_pass_link'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('show_pass_link') . '" type="checkbox" id="' . $this->get_field_id('show_pass_link') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_pass_link') . '">' . __('Show Lost Password Link', 'theme-my-login') . '</label></p>' . "\n";
        $is_checked = (empty($instance['show_gravatar'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('show_gravatar') . '" type="checkbox" id="' . $this->get_field_id('show_gravatar') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_gravatar') . '">' . __('Show Gravatar', 'theme-my-login') . '</label></p>' . "\n";
        echo '<p>' . __('Gravatar Size', 'theme-my-login') . ': <input name="' . $this->get_field_name('gravatar_size') . '" type="text" id="' . $this->get_field_id('gravatar_size') . '" value="' . $instance['gravatar_size'] . '" size="3" /> <label for="' . $this->get_field_id('gravatar_size') . '"></label></p>' . "\n";
        $is_checked = (empty($instance['register_widget'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('register_widget') . '" type="checkbox" id="' . $this->get_field_id('register_widget') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('register_widget') . '">' . __('Allow Registration', 'theme-my-login') . '</label></p>' . "\n";
        $is_checked = (empty($instance['lost_pass_widget'])) ? '' : 'checked="checked" ';
        echo '<p><input name="' . $this->get_field_name('lost_pass_widget') . '" type="checkbox" id="' . $this->get_field_id('lost_pass_widget') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('lost_pass_widget') . '">' . __('Allow Password Recovery', 'theme-my-login') . '</label></p>' . "\n";
    }

}
endif;

?>
