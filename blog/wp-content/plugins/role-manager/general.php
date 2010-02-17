<?php
class IWG_RoleManagementGeneral {
  var $file_basename;   /* under wp-content/plugins */
  var $manage_general_uri;

  /**
   * the constructor
   * R23
   * @param str $path_file
   * @return IWG_RoleManagementGeneral
   */
  function IWG_RoleManagementGeneral($path_file='') {
  	$this->file_basename = basename(__FILE__);
    $this->manage_general_uri = get_settings('siteurl') . '/wp-admin/'.$path_file.'?page=' . $this->file_basename;
  }
  
  /**
   * show the complete general page
   * R22
   */
  function manage_general_page() {
    global $wp_roles, $current_user, $iwg_rolemanagement;

    $action = $_POST['action'] ? $_POST['action'] : $_GET['action'];
    if ( $action === 'config_change') {
    	$iwg_rolemanagement->check_admin_ref('iwg_rolemanager_capsspaceconfig');
    	$spaces_allowed = TRUE;
    	if (empty($_POST['spaces_in_caps_allowed']) || ($_POST['spaces_in_caps_allowed'] != 'on')) {
    		$spaces_allowed = FALSE;
    	}
    	$iwg_rolemanagement->store_cap_spaces_handling($spaces_allowed);
    	echo '<div class="updated fade" id="message"><p>' . __('Configuration saved', 'role-manager') . '</p></div>';
    }
    $checked = $iwg_rolemanagement->spaces_in_caps ? 'checked="checked"' : '';
    ?>
    <div class="wrap" id="rolemanager-config">
    <h2><?php _e('Configuration', 'role-manager');?></h2>
    
		<form name="capspaceconfig" id="cap_space_config" method="post">
			<input type="hidden" name="action" value="config_change" />
			<?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_capsspaceconfig'); ?>
			<table class="optiontable">
				<tr>
					<th scope="row" valign="top"><label for="spaces_in_caps_allowed"><?php _e('Spaces allowed in Capabilities', 'role-manager');?></label></th>
					<td>
						<input name="spaces_in_caps_allowed" type="checkbox" id="spaces_in_caps_allowed" <?php echo $checked; ?> />
<?php _e('If you have trouble with other plugins and Role Managers Capability Check', 'role-manager');?>
					</td>
				</tr>
			<?php if ($iwg_rolemanagement->new_admin_if) {?>
				<tr><td><input type="submit" name="submit" value="<?php _e('Store', 'role-manager');?>" /></td></tr>
			</table>
			<?php } else { ?>
			</table>
			<p class="submit"><input type="submit" name="submit" value="<?php _e('Store', 'role-manager');?>" /></p>
			<?php } ?>
		</form>
    </div>
    <div class="wrap" id="main_page">
    <h2><?php _e('General information about your Roles and Capabilities and this Plugin', 'role-manager');?></h2>
    <div class="rolemanagement_general">
    <h2><?php _e('Statistics', 'role-manager');?></h2>
    <div class="rolemanagement_sysinfo">
      <h4><?php _e('User-Statistic', 'role-manager');?></h4>
      <ul>
        <li><?php _e('Users in your Blog: ', 'role-manager'); echo count($iwg_rolemanagement->get_all_user_ids()); ?></li>
      </ul>
      <h4><?php _e('Roles-Statistic', 'role-manager');?></h4>
      <ul>
        <li><?php _e('Roles in your Blog: ', 'role-manager'); echo count($wp_roles->roles); ?></li>
        <li><?php _e('Attached Roles: ', 'role-manager');?><ul>
        <?php
        foreach ($iwg_rolemanagement->rolemanager->get_attached_roles(FALSE) as $role ) { ?>
          <li><?php echo $wp_roles->role_names[$role]; ?></li>
        <?php
        }
        ?></ul></li>
        <li><?php _e('Detached Roles: ', 'role-manager');?><ul>
        <?php
        foreach ($iwg_rolemanagement->rolemanager->get_detached_roles(FALSE) as $role ) { ?>
          <li><?php echo $wp_roles->role_names[$role]; ?></li>
        <?php
        }
        ?></ul></li>
      </ul>
      <h4><?php _e('Capabilities-Statistic', 'role-manager');?></h4>
      <ul>
        <li><?php _e('Capabilities in your Blog: ', 'role-manager'); echo count($iwg_rolemanagement->get_cap_list()); ?></li>
      <?php
      $detached_caps = $iwg_rolemanagement->capmanager->get_detached_caps(FALSE);
      $dedicated_caps = $iwg_rolemanagement->capmanager->get_dedicated_caps(FALSE);
      $unused_caps = $iwg_rolemanagement->capmanager->get_unused_caps(FALSE);
      ?>
        <li><?php _e('Detached Capabilities: ', 'role-manager');?><ul>
        <?php
        if (count($detached_caps) > 0 ) {
          foreach ($detached_caps as $cap) {
            ?><li><?php echo $iwg_rolemanagement->capmanager->get_cap_name($cap); ?></li><?php
          }
        } else {
          _e('You don\'t have detached capabilities.', 'role-manager');
        }
        ?>
        </ul></li>
        <li><?php _e('Dedicated Capabilities: ', 'role-manager');?><ul>
        <?php
        if (count($dedicated_caps) > 0 ) {
          foreach ($dedicated_caps as $cap) {
            ?><li><?php echo $iwg_rolemanagement->capmanager->get_cap_name($cap); ?></li><?php
          }
        } else {
          _e('You don\'t have dedicated capabilities.', 'role-manager');
        }
        ?>
        </ul></li>
        <li><?php _e('Unused Capabilities: ', 'role-manager');?><ul>
        <?php
        if (count($unused_caps) > 0 ) {
          foreach ($unused_caps as $cap) {
            ?><li><?php echo $iwg_rolemanagement->capmanager->get_cap_name($cap); ?></li><?php
          }
        } else {
          _e('You don\'t have unused capabilities.', 'role-manager');
        }
        ?>
        </ul></li>
      </ul>
    </div>
    </div>
    </div><?php
  }

  function debug($foo) {
  	$args = func_get_args();
  	echo "<pre style=\"background-color:#ffeeee;border:1px solid red;\">";
  	foreach($args as $arg1) {
  		echo htmlentities(print_r($arg1, 1)) . "<br/>";
  	}
  	echo "</pre>";
  }

}

?>