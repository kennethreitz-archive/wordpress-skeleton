<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Modules', 'theme-my-login'); ?></th>
        <td>
            <?php $modules = get_plugins('/theme-my-login/modules'); if ( !empty($modules) ) : foreach ( $modules as $module_file => $module_data ) : ?>
            <input name="theme_my_login[modules][]" type="checkbox" id="theme_my_login_modules_<?php echo $module_file; ?>" value="<?php echo $module_file; ?>"<?php checked(1, in_array($module_file, $theme_my_login->options['active_modules'])); ?> />
            <label for="theme_my_login_modules_<?php echo $module_file; ?>"><?php printf(__('Enable %s', 'theme-my-login'), $module_data['Name']); ?></label><br />
            <?php if ( $module_data['Description'] ) echo '<p class="description">' . $module_data['Description'] . '</p>'; ?>
            <?php endforeach; else : _e('No modules found.', 'theme-my-login'); endif; ?>
        </td>
    </tr>
    <?php do_action('tml_settings_modules', $theme_my_login->options); ?>
</table>