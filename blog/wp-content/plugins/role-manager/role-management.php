<?php
class RoleManager {
  var $file_basename;   /* under wp-content/plugins */
  var $manage_roles_uri;

  /**
   * the constructor
   * R23
   * @param str $path_file
   * @return RoleManager
   */
  function RoleManager($path_file='') {
    global $wp_db_version, $iwg_rolemanagement;
  	$this->file_basename = basename(__FILE__);
    $this->manage_roles_uri = get_settings('siteurl') . '/wp-admin/'.$path_file.'?page=' . $this->file_basename;
  }

   /**
    * handle cap-changes on a single role
    * R22
    */
  function handle_role_changes() {
    global $wp_roles, $current_user, $iwg_rolemanagement;
    // Handle cap-changes on a single role
    if (empty($_POST['role'])) {
      $iwg_rolemanagement->error_number = 1000;
      $iwg_rolemanagement->handle_error();
    }
    $role = $iwg_rolemanagement->check_input($_POST['role'], null, false, false, true);
    if ( !$role ) {
      $iwg_rolemanagement->error_number += 1000;
      $iwg_rolemanagement->handle_error();
    }
    if (empty($_POST['cap'])) {
      $iwg_rolemanagement->error_number = 2000;
      $iwg_rolemanagement->handle_error();
    }
    $cap = $iwg_rolemanagement->check_input($_POST['cap'], 'int_cap', true);
    if ( !$cap ) {
	    $iwg_rolemanagement->error_number += 2000;
			$iwg_rolemanagement->handle_error();
    }
    if ( ! $iwg_rolemanagement->capmanager->cap_exists($cap) ) {
    	$iwg_rolemanagement->error_number = 2010;
    	$iwg_rolemanagement->handle_error();
    }
    $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_edit_cap_on_role_'.$role.'_'.$cap);

    /*
     * need this check here for users with javascript switched off,
     * if javascript is on the check run before submit and no data aer submitted
     */
     if( ($cap == $iwg_rolemanagement->neededcap && in_array($role, $current_user->roles)) ||
         ($cap == 'edit_users' && in_array($role, $current_user->roles)) ) {
       $iwg_rolemanagement->error_number = 9001;
       $iwg_rolemanagement->handle_error();
     }

    if ($_POST['grant'] == 1) {
      $wp_roles->add_cap($role, $cap, true);
    } else {
      $wp_roles->remove_cap($role, $cap);
    }

    //Redirect them away
    if (isset($_POST['ajax'])) {
      $changed = addslashes($_POST['grant'] ? __('Capability granted', 'role-manager') : __('Capability denied', 'role-manager'));
      die('toggleCap("btn_' . $role . '__' . $cap . '");
        setMessage("' . $changed . '");
        ');
    } else {
      $changed = ($_POST['grant'] ? 'granted' : 'denied') . '=' . $role;
      header('Location: ' . $this->manage_roles_uri . '&' . $changed);
    }
  }
   
	 /**
	  * handle the creation of a new role
	  * R22 
	  */
	 function handle_new_role_creation() {
    global $wp_roles, $iwg_rolemanagement;

    $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_create_new_role');

    // experimental for wp-mu
		// s. http://www.im-web-gefunden.de/wordpress-plugins/role-manager/#comment-6722
		if ( ! isset($wp_roles) ) {
			$wp_roles = new WP_Roles();
		}
		// end of experimental for wp-mu
    $this->_add_new_role();
    header('Location: ' . $this->manage_roles_uri . '&new-role=true');
  }

	 /**
	  * show the form to rename a role
	  * R22
	  * @param str $role
	  */
	 function rename_role_form($role) {
    global $wp_roles, $iwg_rolemanagement;
    $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_rename_role_form_'.$role);

    // no: underscore, space
    // yes: minus
    $role_name = $iwg_rolemanagement->check_input($role, null, false, false, true);
    if ( !$role_name ) {
      $iwg_rolemanagement->error_number += 1000;
      $iwg_rolemanagement->handle_error();
    }

    ?>
    <div class="wrap">
      <h2><?php _e('Rename Role', 'role-manager'); ?> '<?php echo $wp_roles->role_names[$role_name]; ?>'</h2>
      <form method="post" class="role-manager" action="<?php echo $this->manage_roles_uri; ?>">
        <input type="hidden" name="action" value="rename" />
        <input type="hidden" name="role" value="<?php echo $role; ?>" />
        <label for="role-name"><?php _e('New Name:', 'role-manager'); ?>
        <input type="text" name="role-name" id="role-name" value="<?php echo $wp_roles->role_names[$role_name]; ?>" />
        </label>
        <?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_rename_role_'.$role_name); ?>
        <input type="submit" value="<?php _e('Rename Role', 'role-manager'); ?>" />
      </form>
    </div>
    <?php
  }
  
  /**
   * handle rename of a given role
   * R22
   * @param str $role
   */
  function rename_role($role) {
    global $wp_roles, $wpdb, $iwg_rolemanagement;

    if ($_POST['role-name']) {
      $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_rename_role_'.$role);
      $old_role_name = $iwg_rolemanagement->check_input($role, null, false, false, true);
      $new_role_name = $iwg_rolemanagement->check_input($_POST['role-name'], null, false, true);
      if ( !$old_role_name || !$new_role_name ) {
        $iwg_rolemanagement->error_number += 1000;
        $iwg_rolemanagement->handle_error();
      }

      $roletitle = sanitize_title($new_role_name);

      if ( $roletitle != $old_role_name ) {
        if ( $wp_roles->is_role($roletitle) ) {
          $iwg_rolemanagement->error_number = 1010;
          $iwg_rolemanagement->handle_error($roletitle);
        }
        // check if a capability with this name exists
        if ( $iwg_rolemanagement->already_exists($roletitle, 'role') ) {
          $iwg_rolemanagement->handle_error($roletitle);
        }
        $oldrole = $wp_roles->get_role($old_role_name);
        $wp_roles->add_role($roletitle, stripslashes($new_role_name), $oldrole->capabilities);
        //$this->debug('before', $wp_roles);

        $uids_with_role = $iwg_rolemanagement->get_all_userids_with_role($old_role_name);
        if ( count($uids_with_role) > 0 ) {
          foreach ($uids_with_role as $id) {
            $user = new WP_User($id);
            $user->roles = $iwg_rolemanagement->fill_array_keys_with_true( $user->roles );
            $user->add_role($roletitle);
            // Bug in capabilities.php, WP_User::get_role_caps(), Start at Line 176
            // this->roles has [0]=admin and should be [admin]=true
            // wrong form comes from array_keys($this->caps) at line 176
            $user->roles = $iwg_rolemanagement->fill_array_keys_with_true( $user->roles );
            $user->remove_role($old_role_name);
          }
        }
        $wp_roles->remove_role($old_role_name);
        header('Location: ' . $this->manage_roles_uri . '&role-renamed=true');
      }
      header('Location: ' . $this->manage_roles_uri);
    }
  }

	 /**
	  * show the form to copy a role
	  * R22
	  * @param str $role
	  */
	 function copy_role_form($role) {
    global $wp_roles, $iwg_rolemanagement;
    $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_copy_role_form_'.$role);

    // no: underscore, space
    // yes: minus
    $role_name = $iwg_rolemanagement->check_input($role, null, false, false, true);
    if ( !$role_name ) {
      $iwg_rolemanagement->error_number += 1000;
      $iwg_rolemanagement->handle_error();
    }
    $orig_role = $wp_roles->get_role($role);

    ?>
    <div class="wrap">
      <h2><?php _e('Copy Role', 'role-manager'); ?> '<?php echo $wp_roles->role_names[$role_name]; ?>'</h2>
      <form method="post" class="role-manager" action="<?php echo $this->manage_roles_uri; ?>">
        <input type="hidden" name="action" value="copy" />
        <input type="hidden" name="role" value="<?php echo $role; ?>" />
        <label for="role-name"><?php _e('New Name:', 'role-manager'); ?>
        <input type="text" name="role-name" id="role-name" value="<?php echo __('Copy of', 'role-manager')." ".$wp_roles->role_names[$role_name]; ?>" />
        </label>
        <label><?php _e('Capabilities to be included:', 'role-manager'); ?></label>
        <fieldset>
        <?php foreach ($iwg_rolemanagement->get_cap_list(true) as $cap) { 
          $checked = $orig_role->has_cap($cap) ? 'checked="checked"' : '';?>
        <label for="cap-<?php echo $cap; ?>" class="cap-label">
        <input type="checkbox" name="caps[<?php echo $cap; ?>]" id="cap-<?php echo $cap."\" ".$checked; ?>" />
        <?php echo $iwg_rolemanagement->capmanager->get_cap_name($cap); ?></label>
        <?php } ?>
        </fieldest>
        
        <?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_copy_role_'.$role_name); ?>
        <input type="submit" value="<?php _e('Copy Role', 'role-manager'); ?>" />
      </form>
    </div>
    <?php
  }
  
  /**
   * handle copy of a given role
   * R22
   * @param str $role
   */
  function copy_role($role) {
    global $wp_roles, $wpdb, $iwg_rolemanagement;

    if ($_POST['role-name']) {
      $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_copy_role_'.$role);
      $this->_add_new_role();
      header('Location: ' . $this->manage_roles_uri . '&role-copied=true');
      //header('Location: ' . $this->manage_roles_uri);
    }
  }
  
  /**
   * show the form to delete a given role
   * R22
   * @param str $role
   */
  function delete_role_form($role) {
    global $wp_roles, $iwg_rolemanagement;
    $iwg_rolemanagement->check_admin_ref('iwg_rolemanger_delete_role_form_'.$role);

    // no: underscore, space
    // yes: minus
    $role_name = $iwg_rolemanagement->check_input($role, null, false, false, true);
    if ( !$role_name ) {
      $iwg_rolemanagement->error_number += 1000;
      $iwg_rolemanagement->handle_error();
    }
    ?>
    <div class="wrap">
      <h2><?php _e('Delete Role', 'role-manager'); ?> '<?php echo $wp_roles->role_names[$role_name]; ?>'</h2>
      <p><?php echo __('All users with this role will have it removed, and they will lose all capabilities from this role (unless other roles provide it). If a user has only this role, they will be assigned the default role, ', 'role-manager') . get_option('default_role'); ?></p>
      <form method="post" class="role-manager" action="<?php echo $this->manage_roles_uri; ?>">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="role" value="<?php echo $role_name; ?>" />
        <?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_delete_role_'.$role_name); ?>
        <input type="submit" name="confirm" value="<?php _e('Confirm Delete Role', 'role-manager'); ?>" />
      </form>
    </div>
    <?php
  }

  /**
   * delete a given role from wordpress
   * R22
   * @param str $role
   */
  function delete_role($role) {
    global $wp_roles, $iwg_rolemanagement;
    if ($_POST['confirm']) {
        $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_delete_role_'.$role);

      // no: underscore, space
      // yes: minus
      $role_name = $iwg_rolemanagement->check_input($role, null, false, false, true);
      if ( !$role_name ) {
        $iwg_rolemanagement->error_number += 1000;
        $iwg_rolemanagement->handle_error();
      }

      $defaultrole = get_option('default_role');
      if ($role_name == $defaultrole) {
        //LAZY CODE ALERT! we should give the option of changing the default role
        $iwg_rolemanagement->error_number = 1020;
        $iwg_rolemanagement->handle_error();
      }

      //remove the role from all the users
      $uids_with_role = $iwg_rolemanagement->get_all_userids_with_role($role_name);
      if ( count($uids_with_role) > 0 ) {
        foreach ($uids_with_role as $id) {
          $user = new WP_User($id);
          $user->roles = $iwg_rolemanagement->fill_array_keys_with_true( $user->roles );
          //if this role removal would end them up with no roles, assign the default role instead of removing
          if (count($user->roles) <= 1) {
            $user->add_role($defaultrole);
            $user->roles = $iwg_rolemanagement->fill_array_keys_with_true( $user->roles );
          }
          $user->remove_role($role_name);
        }
      }
      $wp_roles->remove_role($role_name);
      header('Location: ' . $this->manage_roles_uri . '&role-deleted=true');
    }
  }

  /**
   * change a given role to wordpress default role
   * R22
   * @param str $role
   */
  function make_default($role) {
    global $wp_roles, $iwg_rolemanagement;
    
   if( $iwg_rolemanagement->user_has_permissions() ) {
     $role_name = $iwg_rolemanagement->check_input($role, null, false, false, true);
     if ( !$role_name ) {
       $iwg_rolemanagement->error_number += 1000;
       $iwg_rolemanagement->handle_error();
     }
     /* don't work for ajax calls
	     $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_chg_def_role_'.$role_name);
		 */
     if ($wp_roles->is_role($role_name)) {
       update_option('default_role', $role_name);
     } else {
       $iwg_rolemanagement->error_number += 1030;
       $iwg_rolemanagement->handle_error($role_name);
     }
     if (isset($_POST['ajax'])) {
       $changed = addslashes(__('Default role changed.', 'role-manager'));
       die('showdefaultrole("' . $role_name .'");
            setMessage("' . $changed . '");
           ');
     }
     header('Location: ' . $this->manage_roles_uri . '&made-default=true');
   } else {
     if (isset($_POST['ajax'])) {
       $changed = addslashes(__('Error: Default role not changed.', 'role-manager'));
       die('setMessage("' . $changed . '");
           ');
     }
     header('Location: ' . $this->manage_roles_uri);
   }
  }

  /**
   * set the userlevel for a given role
   * R22
   * @param str $role
   */
  function set_user_level($role) {
    global $wpdb, $wp_roles, $iwg_rolemanagement;

    $level = isset($_POST['level']) ? $_POST['level'] : $_GET['level'];
    $level = $iwg_rolemanagement->check_input($level);
    if ( $level !== false ) {
      $level = (int) $level;
      if ( ($level < 0) || ($level > 10) ) {
        $iwg_rolemanagement->error_number = 1900;
        $iwg_rolemanagement->handle_error();
      }
    } else {
      $iwg_rolemanagement->error_number = 1900;
      $iwg_rolemanagement->handle_error();
    }
    // no: underscore, space
    // yes: minus
    $role = $iwg_rolemanagement->check_input($role, null, false, false, true);
    if ( !$role ) {
      $iwg_rolemanagement->error_number += 1000;
      $iwg_rolemanagement->handle_error();
    }

    /* don't work for ajax calls
     $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_change_level_'.$role);
     */

    $role_o = $wp_roles->get_role($role);

    $role_user_level = array_reduce(array_keys($role_o->capabilities), array('WP_User', 'level_reduction'), 0);
    $wp_roles->remove_cap($role, "level_{$role_user_level}");
    $wp_roles->add_cap($role, "level_{$level}");

    $uids_with_role = $iwg_rolemanagement->get_all_userids_with_role($role);
    if ( count($uids_with_role) > 0 ) {
      foreach ($uids_with_role as $id) {
        /* normally we shoukd use the next three steps here - but it doesn't work
           because $wp_roles->add_cap isn't flushed to the db here
        $user = new WP_User($id);
        $user->roles = $iwg_rolemanagement->fill_array_keys_with_true( $user->roles );
        $user->update_user_level_from_caps();
           on this reason used this one step: */
        update_usermeta($id, $wpdb->prefix.'user_level', $level);
      }
    }

    if (isset($_POST['ajax'])) {
      $changed = addslashes(__('Set user level of role.', 'role-manager'));
      die('fadeuserlevel("' . $role .'");
           setMessage("' . $changed . '");
          ');
    }
    header('Location: ' . $this->manage_roles_uri . '&set-userlevel=true');
  }
  
  /**
   * show the roles page or switch to call the rename or delete form
   * R22
   */
  function manage_roles_page() {
    global $wp_roles, $current_user, $iwg_rolemanagement;

    if ( ! $iwg_rolemanagement->user_has_permissions() ) {
      $iwg_rolemanagement->error_number = 9000;
      $iwg_rolemanagement->handle_error();
    }

    $action = $_POST['action'] ? $_POST['action'] : $_GET['action'];
    $role = $_POST['role'] ? $_POST['role'] : $_GET['role'];
  
    switch ($action) {
      case 'rename': $this->rename_role_form($role); break;
      case 'copy':   $this->copy_role_form($role); break;
      case 'delete': $this->delete_role_form($role); break;
      default:
      // Display a message if we've made changes
      if ($_GET['granted']) {
        echo '<div class="updated fade" id="message"><p>' . __('Capability granted', 'role-manager') . '</p></div>';
      } elseif ($_GET['denied']) {
        echo '<div class="updated fade" id="message"><p>' . __('Capability denied', 'role-manager') . '</p></div>';
      } elseif ($_GET['new-role']) {
        echo '<div class="updated fade" id="message"><p>' . __('New role created', 'role-manager') . '</p></div>';
      } elseif ($_GET['role-renamed']) {
        echo '<div class="updated fade" id="message"><p>' . __('Role renamed', 'role-manager') . '</p></div>';
      } elseif ($_GET['role-copied']) {
        echo '<div class="updated fade" id="message"><p>' . __('Role copied', 'role-manager') . '</p></div>';
      } elseif ($_GET['role-deleted']) {
        echo '<div class="updated fade" id="message"><p>' . __('Role deleted', 'role-manager') . '</p></div>';
      } elseif ($_GET['made-default']) {
        echo '<div class="updated fade" id="message"><p>' . __('Default role changed', 'role-manager') . '</p></div>';
      } elseif ($_GET['set-userlevel']) {
        echo '<div class="updated fade" id="message"><p>' . __('Set user level of role', 'role-manager') . '</p></div>';
      }
    
      // Output an admin page
      echo '<div class="wrap" id="main_page"><h2>' . __('Manage Roles', 'role-manager') . '</h2>';
      echo '<p>' . __('This page is for editing what capabilities are associated with each role. To change the capabilities of a specific user, click on Authors &amp; Users, then click Edit next to the user you want to change. You can <a href="#new-role">add new roles</a> as well.', 'role-manager') . '</p>';

      $capnames = $iwg_rolemanagement->get_cap_list();
    
      $defaultrole = get_option('default_role');
      foreach($wp_roles->role_names as $roledex => $rolename) {
        if ($roledex == $defaultrole) {
          $lovelylittlestar = '<img src="' . $iwg_rolemanagement->image_dir . 'star.png" alt="Default Role" class="defrole" id="defrole_' . $roledex . '" onclick="return !setdefaultrole(\'' . $roledex . '\');" />';
        } else {
          $lovelylittlestar = '<img src="' . $iwg_rolemanagement->image_dir . 'star_disabled.png" class="nondefrole" id="defrole_' . $roledex . '" alt="Click to make this the default role" onclick="return !setdefaultrole(\'' . $roledex . '\');" />';
        }
				$lovelylittlestar_url = $this->manage_roles_uri . '&amp;action=makedefault&amp;role=' . $roledex;
				$lovelylittlestar_url = $iwg_rolemanagement->nonce_url($lovelylittlestar_url,'iwg_rolemanager_chg_def_role_'.$roledex);
				$lovelylittlestar = '<a href="' . $lovelylittlestar_url . '" class="roledefaulter">' . $lovelylittlestar . '</a>';
        $rename_url = $this->manage_roles_uri . "&amp;action=rename&amp;role=".$roledex;
        $rename_url = $iwg_rolemanagement->nonce_url($rename_url,'iwg_rolemanager_rename_role_form_'.$roledex);
        ?><h3 class="roles_name"><?php echo $lovelylittlestar . ' ' . $rolename . ' '; ?>
        (<a href='<?php echo $rename_url;?>'><?php _e('rename', 'role-manager');?></a><?php
        $copy_url = $this->manage_roles_uri . "&amp;action=copy&amp;role=".$roledex;
        $copy_url = $iwg_rolemanagement->nonce_url($copy_url,'iwg_rolemanager_copy_role_form_'.$roledex);
        ?>, <a href='<?php echo $copy_url;?>'><?php _e('copy', 'role-manager');?></a><?php
        
        $role = $wp_roles->get_role($roledex);
        if(!($role->has_cap($iwg_rolemanagement->neededcap) && in_array($roledex, $current_user->roles))) {
          $delete_url = $this->manage_roles_uri . "&amp;action=delete&amp;role=".$roledex;
          $delete_url = $iwg_rolemanagement->nonce_url($delete_url,'iwg_rolemanger_delete_role_form_'.$roledex);
          echo ", <a href='" . $delete_url . "'>" . __('delete', 'role-manager') . "</a>";
        }
        ?>)</h3><?php

        foreach($capnames as $cap) {
          $capname = $iwg_rolemanagement->capmanager->get_cap_name($cap);
          ?><form onsubmit="submitme(this);return false;" method="post" class="cap_form" id="<?php echo $roledex . '__' . $cap; ?>" action="<?php echo $this->manage_roles_uri;?> ">
          <input type="hidden" name="role" value="<?php echo $roledex;?>" />
          <input type="hidden" name="cap" value="<?php echo $cap;?>" />
          <input type="hidden" name="grant" value="<?php echo ($role->has_cap($cap)?'0':'1');?>" />
	        <?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_edit_cap_on_role_'.$roledex.'_'.$cap);
          if ($role->has_cap($cap)) {
            if( ($cap == $iwg_rolemanagement->neededcap && in_array($roledex, $current_user->roles)) || 
                ($cap == 'edit_users' && in_array($roledex, $current_user->roles)) ) {
              echo '<input type="image" id="btn_' . $roledex . '__' . $cap . '" src="' . $iwg_rolemanagement->image_dir . 'accept2.png"  name="grant" value="0" alt="' . __('Granted', 'role-manager') . '" onclick="return badidea();"/>';
            } else {
              echo '<input type="image" id="btn_' . $roledex . '__' . $cap . '" src="' . $iwg_rolemanagement->image_dir . 'accept.png"  name="grant" value="0" alt="' . __('Granted', 'role-manager') . '" />';
            }
          } else {
            echo '<input type="image" id="btn_' . $roledex . '__' . $cap . '" src="' . $iwg_rolemanagement->image_dir . 'cancel.png"  name="grant" value="1" alt="' . __('Denied', 'role-manager') . '" />';
          }
          echo ' ' . $capname;
          ?></form><?php
        }
        $role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
        ?>
        <form method="post" class="userlevel_form" id="<?php echo $roledex;?>___user_level" action="<?php echo $this->manage_roles_uri;?>" onsubmit="return !setlevel(document.getElementById('<?php echo $roledex;?>___ulvalue).value,'<?php echo $roledex; ?>');">
        <input type="hidden" name="role" value="<?php echo $roledex; ?>" />
        <input type="hidden" name="action" value="setuserlevel" />
        <?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_change_level_'.$roledex); ?>
        <input type="image" src="<?php echo $iwg_rolemanagement->image_dir;?>refresh.png" alt="'<?php _e('Update', 'role-manager');?>" />
        <label style="font-style:italic;">
        <?php $sel = '<select name="level" id="' . $roledex . '___ulvalue" onchange="setlevel(this.value,\'' . $roledex . '\');">';
        for($level=0; $level<=10; $level++) {
          $sel .= '<option value="' . $level . '"';
          if($role_user_level == $level) {
            $sel .= ' selected="selected"';
          }
          $sel .= '>' . $level . '</option>';
        }
        $sel .= '</select>';
        echo sprintf(__(' User Level: %s', 'role-manager'), $sel);
        ?></label></form><?php
      }

      //Echo the new role form
      ?>
      <h2 id="new-role"><?php _e('Create a new Role', 'role-manager'); ?></h2>
      <form method="post" class="role-manager">
        <label for="role-name"><?php _e('Role Name:', 'role-manager'); ?> <input type="text" name="role-name" id="role-name" /></label>
        <label><?php _e('Capabilities to be included:', 'role-manager'); ?></label>
        <fieldset>
        <?php foreach ($iwg_rolemanagement->get_cap_list(true) as $cap) { ?>
        <label for="cap-<?php echo $cap; ?>" class="cap-label">
        <input type="checkbox" name="caps[<?php echo $cap; ?>]" id="cap-<?php echo $cap; ?>" />
        <?php echo $iwg_rolemanagement->capmanager->get_cap_name($cap); ?></label>
        <?php } ?>
        </fieldest>
        <?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_create_new_role'); ?>
        <input type="submit" name="new-role" value="<?php _e('Create Role', 'role-manager'); ?>" />
      </form>
      <?php
    } //switch ($action)
  }
  
  /**
   * dispatcher for the actions rename, delete, makedefault and setuserlevel for a transmitted role
   * R22
   */
  function process_role_changes() {
    global $iwg_rolemanagement;

    if ( $iwg_rolemanagement->user_has_permissions() ) {
      $action = $_POST['action'] ? $_POST['action'] : $_GET['action'];
      $role = $_POST['role'] ? $_POST['role'] : $_GET['role'];

      switch ($action) {
        case 'rename': $this->rename_role($role); break;
        case 'copy':   $this->copy_role($role); break;
        case 'delete': $this->delete_role($role); break;
        case 'makedefault': $this->make_default($role); break;
        case 'setuserlevel': $this->set_user_level($role); break;
      }
    }
  }

  /**
   * called from handle_new_role_creation and copy_role
   * it is the same, because in both functions the add a new role to the core
   * Don't call this from outside!
   * R22
   */
  function _add_new_role() {
    global $wp_roles, $iwg_rolemanagement;
    
    if (empty($_POST['role-name'])) {
      $iwg_rolemanagement->error_number = 1000;
      $iwg_rolemanagement->handle_error();
    }
    $role_name = $iwg_rolemanagement->check_input($_POST['role-name'], null, false, true);
    if ( !$role_name ) {
      $iwg_rolemanagement->error_number += 1000;
      $iwg_rolemanagement->handle_error();
    }
    // check if a role with this name exists
    if ( $wp_roles->is_role($role_name) ) {
      $iwg_rolemanagement->error_number = 1040;
      $iwg_rolemanagement->handle_error($role_name);
    }
    // check if a capability with this name exists
    if ( $iwg_rolemanagement->already_exists($role_name, 'role') ) {
      $iwg_rolemanagement->handle_error($role_name);
    }
    $caps = $_POST['caps'];
    //turn all the 'on's to 1s after a check if the cap is valid and exists
    if($caps) {
      foreach ($caps as $k => $cap) {
        $k = $iwg_rolemanagement->check_input($k, 'int_cap', true);
        if ( $k ) {
          if ( $iwg_rolemanagement->capmanager->cap_exists($k) ) {
            $caps[$k] = 1;
          } else {
            $iwg_rolemanagement->error_number = 2010;
            $iwg_rolemanagement->handle_error();
          }
        } else {
          $iwg_rolemanagement->error_number += 2000;
          $iwg_rolemanagement->handle_error();
        }
      }
    } else {
      $caps = array();
    }

    //$role = $wp_roles->add_role(sanitize_title($_POST['role-name']), stripslashes($_POST['role-name']), $caps);
    $role = $wp_roles->add_role(sanitize_title($role_name), stripslashes($role_name), $caps);
  }

  /**
   * get detached roles
   * R22
   * @param boolean $flip
   * @return array with detached roles
   */
  function get_detached_roles ( $flip = FALSE ) {
    /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
    global $wp_roles, $iwg_rolemanagement;

    $detached_roles = array();
    foreach($wp_roles->role_names as $int_role => $role_name) {
      $role = $wp_roles->get_role($int_role);
      $users = $iwg_rolemanagement->get_all_userids_with_role($int_role);
        if ( count($users) == 0 ) {
            $detached_roles[$int_role] = 1;
        }
    }
    if ( ! $flip ) {
      $detached_roles=array_keys($detached_roles);
    }
    return $detached_roles;
  }

  /**
   * get attaced roles
   * R22
   * @param boolean $flip
   * @return array with attached roles
   */
  function get_attached_roles ( $flip = FALSE ) {
    /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
    global $wp_roles, $iwg_rolemanagement;

    $attached_roles = array();
    $attached_roles = array_keys($wp_roles->role_names);
    //$this->debug('ATTACHED ROLES', $attached_roles);
    $detached_roles = $this->get_detached_roles(FALSE);
    //$this->debug('DETACHED ROLES', $detached_roles);
    $attached_roles = array_diff($attached_roles, $detached_roles);
    //$this->debug('ATTACHED ROLES 2', $attached_roles);
    if ( $flip ) {
      $attached_roles=$iwg_rolemanagement->fill_array_keys_with_true($attached_roles);
    }
    return $attached_roles;
  }

  /* Utility Functions */
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