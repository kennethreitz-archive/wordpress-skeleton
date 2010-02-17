<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Optimization', 'theme-my-login'); ?></th>
        <td>
            <input name="theme_my_login[enable_template_tag]" type="checkbox" id="theme_my_login_enable_template_tag" value="1"<?php checked('1', $theme_my_login->options['enable_template_tag']); ?> />
            <label for="theme_my_login_enable_template_tag"><?php _e('Enable Template Tag', 'theme-my-login'); ?></label><br />
            <p class="description"><?php _e('Enable this setting if you wish to use the theme_my_login() template tag. Otherwise, leave it disabled for optimization purposes.', 'theme-my-login'); ?></p>

            <input name="theme_my_login[enable_widget]" type="checkbox" id="theme_my_login_enable_widget" value="1"<?php checked('1', $theme_my_login->options['enable_widget']); ?> />
            <label for="theme_my_login_enable_widget"><?php _e('Enable Widget', 'theme-my-login'); ?></label><br />
            <p class="description"><?php _e('Enable this setting if you wish to use the "Theme My Login" widget. Otherwise, leave it disabled for optimization purposes.', 'theme-my-login'); ?></p>
        </td>
    </tr>
    <?php do_action('tml_settings_optimization', $theme_my_login->options); ?>
</table>