<table class="form-table">
    <tr valign="top">
        <td>
            <label for="theme_my_login_mail_from_name"><?php _e('From Name', 'theme-my-login'); ?></label><br />
            <input name="theme_my_login[email][mail_from_name]" type="text" id="theme_my_login_mail_from_name" value="<?php echo $theme_my_login->options['email']['mail_from_name']; ?>" class="regular-text" />
        </td>
    </tr>
    <tr valign="top">
        <td>
            <label for="theme_my_login_mail_from"><?php _e('From E-mail', 'theme-my-login'); ?></label><br />
            <input name="theme_my_login[email][mail_from]" type="text" id="theme_my_login_mail_from" value="<?php echo $theme_my_login->options['email']['mail_from']; ?>" class="regular-text" />
        </td>
    </tr>
    <tr valign="top">
        <td>
            <label for="theme_my_login_mail_content_type"><?php _e('E-mail Format', 'theme-my-login'); ?></label><br />
            <select name="theme_my_login[email][mail_content_type]" id="theme_my_login_mail_content_type">
            <option value="plain"<?php if ('plain' == $theme_my_login->options['email']['mail_content_type']) echo ' selected="selected"'; ?>>Plain Text</option>
            <option value="html"<?php if ('html' == $theme_my_login->options['email']['mail_content_type']) echo ' selected="selected"'; ?>>HTML</option>
            </select>
        </td>
    </tr>
</table>