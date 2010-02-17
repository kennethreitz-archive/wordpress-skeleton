<?php

// Default menu
jkf_tml_add_menu_page(__('General', 'theme-my-login'), 'theme-my-login/admin/options.php');
jkf_tml_add_submenu_page('theme-my-login/admin/options.php', __('Basic', 'theme-my-login'), 'theme-my-login/admin/options-basic.php');
jkf_tml_add_submenu_page('theme-my-login/admin/options.php', __('Modules', 'theme-my-login'), 'theme-my-login/admin/options-modules.php');
jkf_tml_add_submenu_page('theme-my-login/admin/options.php', __('Optimization', 'theme-my-login'), 'theme-my-login/admin/options-optimization.php');

// Allow plugins to add to menu
do_action('tml_admin_menu');

?>

<div class="updated" style="background:#f0f8ff; border:1px solid #addae6">
    <p><?php _e('If you like this plugin, please help keep it up to date by <a href="http://www.jfarthing.com/donate">donating through PayPal</a>!', 'theme-my-login'); ?></p>
</div>

<div class="wrap">
    <?php screen_icon('options-general'); ?>
    <h2><?php esc_html_e('Theme My Login Settings', 'theme-my-login'); ?></h2>

    <form action="options.php" method="post">
    <?php settings_fields('theme_my_login'); ?>
    
    <div id="tml-container">

        <ul>
            <?php foreach ( $jkf_tml_admin_menu as $tml_menu ) {
                echo '<li><a href="#' . $tml_menu[2] . '">' . $tml_menu[0] . '</a></li>' . "\n";
            }?>
        </ul>
        
        
        <?php foreach ( $jkf_tml_admin_menu as $tml_menu ) {
            echo '<div id="' . $tml_menu[2] . '">' . "\n";
            if ( isset($jkf_tml_admin_submenu[$tml_menu[1]]) ) {
                echo '<ul>' . "\n";
                foreach ( $jkf_tml_admin_submenu[$tml_menu[1]] as $tml_submenu ) {
                    echo '<li><a href="#' . $tml_submenu[2] . '">' . $tml_submenu[0] . '</a></li>' . "\n";
                }
                echo '</ul>' . "\n";
                
                foreach ( $jkf_tml_admin_submenu[$tml_menu[1]] as $tml_submenu ) {
                    echo '<div id="' . $tml_submenu[2] . '">' . "\n";
                    jkf_tml_load_admin_page($tml_submenu[1], $tml_submenu[2]);
                    echo '</div>' . "\n";
                }
            } else {
                jkf_tml_load_admin_page($tml_menu[1], $tml_menu[2]);
            }
            echo '</div>' . "\n";
        } ?>
        
    </div>
    
    <p><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></p>
    </form>
    
</div>