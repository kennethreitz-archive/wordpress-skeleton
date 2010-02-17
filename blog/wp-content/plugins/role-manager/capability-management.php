<?php

class CapabilityManager {
  var $file_basename;   /* under wp-content/plugins */
  var $manage_capabilities_uri;

  /**
   * the constructor
   * R23
   * @param str $path_file
   * @return CapabilityManager
   */
   function CapabilityManager($path_file='') {
   	global $wp_db_version;
   	$this->file_basename = 'role-manager/'.basename(__FILE__);
   	$this->manage_capabilities_uri = get_settings('siteurl') . '/wp-admin/'.$path_file.'?page=' . $this->file_basename;
   }

  /**
   * create a new capability
   * R22 
   * TODO if cap already exists shouldn't send the create message
   */
   function handle_new_cap_creation() {
     global $iwg_rolemanagement;

     $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_create_cap');

     if (empty($_POST['cap-name'])) {
       $iwg_rolemanagement->error_number = 2000;
       $iwg_rolemanagement->handle_error();
     }
     $cap_name = $iwg_rolemanagement->check_input($_POST['cap-name'], null, false, true);
     if ( !$cap_name ) {
       $iwg_rolemanagement->error_number += 2000;
       $iwg_rolemanagement->handle_error();
     }
     if ( $iwg_rolemanagement->already_exists($cap_name, 'cap') ) {
       $iwg_rolemanagement->handle_error($cap_name);
     }
     
     $cap = strtolower($cap_name);
     // TODO This strips out also multibyte-chars - should it changed? 
     $cap = preg_replace('#[^a-z0-9]#', '_', $cap);

     $caps = $iwg_rolemanagement->get_cap_list();
     if ( !in_array($cap, $caps) ) $caps[] = $cap;
     $iwg_rolemanagement->set_cap_list($caps);
     header('Location: ' . $this->manage_capabilities_uri . '&new-cap=true');
   }

  /**
   * purge unused caps from wordpress
   * R22
   */
   function handle_cap_purge() {
     global $iwg_rolemanagement;

     $iwg_rolemanagement->check_admin_ref('iwg_rolemanager_purge_caps');

     $unused_caps=$this->get_unused_caps(FALSE);
     $all_caps = $iwg_rolemanagement->get_cap_list();
     $new_caps = array_diff ($all_caps, $unused_caps);
     $iwg_rolemanagement->set_cap_list($new_caps);
     header('Location: ' . $this->manage_capabilities_uri . '&purge-caps=true');
   }

  /**
   * the complete capability page
   * R22
   */
   function manage_caps_page() {
     global $wp_roles, $current_user, $iwg_rolemanagement;

     if ( ! $iwg_rolemanagement->user_has_permissions() ) {
       $iwg_rolemanagement->error_number = 9000;
       $iwg_rolemanagement->handle_error();
     }

     $action = $_POST['action'] ? $_POST['action'] : $_GET['action'];

     // Display a message if we've made changes
     if ($_GET['new-cap']) {
       echo '<div class="updated fade" id="message"><p>' . __('New capability created', 'role-manager') . '</p></div>';
     } elseif ($_GET['purge-caps']) {
       echo '<div class="updated fade" id="message"><p>' . __('Unused capabilites purged', 'role-manager') . '</p></div>';
     }

     // Output an admin page
     ?><div class="wrap" id="main_page">
     <h2><?php _e('Manage Capabilities', 'role-manager'); ?></h2>
     <p><?php _e('This page is for editing capabilities.', 'role-manager'); ?></p>

     <h2 id="new-cap"><?php _e('Custom Capabilities', 'role-manager'); ?></h2>
		 <form method="post" class="role-manager">
		 	<label style="display: inline;" for="cap-name"><?php _e('New Capability Name: ', 'role-manager'); ?>
		 	<input type="text" name="cap-name" id="cap-name" /></label>
		 	<?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_create_cap'); ?>
			<input style="margin-left: 2em;" type="submit" name="new-cap" value="<?php _e('Create Capability', 'role-manager'); ?>" />
		 </form>
		 <h2 id="purge-cap"><?php _e('Purge Unused Capabilities', 'role-manager'); ?></h2>
     <?php
     $unused_caps=$this->get_unused_caps();
     if ( count($unused_caps) > 0 ) { ?>
     	 <p><?php _e('This capabilities are unused and will be purged', 'role-manager');?>:</p>
			 <ul>
	      <?php foreach ($unused_caps as $cap) { ?>
				<li><?php echo $iwg_rolemanagement->capmanager->get_cap_name($cap); ?></li>
		    <?php } ?>
			 </ul>
			 <form method="post" class="role-manager">
			 	<?php $iwg_rolemanagement->nonce_field('iwg_rolemanager_purge_caps'); ?>
			 	<input type="submit" name="purge-caps" value="<?php _e('Purge Unused Capabilities', 'role-manager'); ?>" onclick="return confirm('<?php _e('If core capabilities are not currently assigned to any Role, then you must manually re-add them after this action if you want to use them.  Are you sure you want to do this?', 'role-manager'); ?>');" />
			 </form>
 	    <?php 
     } else {
       _e('You don\'t have unused capabilities.', 'role-manager');
     }
   }

   /* start of user-edit-widget funcions */

  /**
   * enhance the edit user page for an single user
   * R22
   * TODO better disable the needed caps edit_users and manage_roles from input
   */
   function manage_user_caps_page() {
     global $profileuser, $wp_roles, $iwg_rolemanagement;

     // if a user don't has the needed cap for the role manager don't show this additional part
     if ( ! $iwg_rolemanagement->user_has_permissions() ) return;
     
     ?><fieldset style="width:90%;" id="rolemanager_singleuser_fs" ><legend><?php _e('Assign extra capabilites', 'role-manager') ;?></legend><?php
     foreach($iwg_rolemanagement->get_cap_list() as $cap) {
       $capname = $this->get_cap_name($cap);
       $checked = $profileuser->has_cap($cap) ? 'checked="checked"' : '';
       if(isset($profileuser->allcaps[$cap]) && !isset($profileuser->caps[$cap]))
       /* caps from users role */
       $inherited = ' style="font-weight:bold;"';
       elseif(isset($profileuser->allcaps[$cap]) && ($profileuser->allcaps[$cap] == false)) {
         /* caps from users role are disabled for this user */
         $inherited = ' ';
         foreach($profileuser->roles as $role) {
           if($wp_roles->role_objects[$role]->has_cap($cap)) {
             $inherited = ' style="font-weight:bold;font-style:italic;color:red;"';
             break;
           }
         }
       } else {
         $inherited = ' ';
         if(isset($profileuser->allcaps[$cap]) && isset($profileuser->caps[$cap])) {
           /* extra capabilities for this user */
           $inherited = ' style="font-weight:bold;font-style:italic;color:green;"';
         }
       }
       echo '<label for="cap-' . $cap . '" class="cap-label" ' . $inherited . '>' .
        '<input type="checkbox" style="width: auto;margin-right: 5px;" name="caps[' . $cap . ']" id="cap-' . $cap . '" ' . $checked . '/>'
        . $capname . '</label>';
     }
     ?></fieldset><br style="clear:both;" /><?php
   }

  /**
   * handle the changes for an specific user from the edit user page
   * R22
   */
   function handle_user_caps_edit() {
     global $user_ID, $iwg_rolemanagement;

     // if a user don't has the needed cap for the role manager don't show this additional part
     if ( ! $iwg_rolemanagement->user_has_permissions() ) return;
     // only on update we have $_POST['user_id']
     if ( ! $_POST['user_id'] ) return;
     
     get_currentuserinfo();
     $submitted_uid = $iwg_rolemanagement->check_input($_POST['user_id']);

     if ( $submitted_uid !== false ) {
       $submitted_uid = (int) $submitted_uid;
       if ( $submitted_uid < 1 ) {
         $iwg_rolemanagement->error_number = 9100;
         $iwg_rolemanagement->handle_error();
       } else {
         $user = new WP_User($submitted_uid);
         if ( ! $user) {
           $iwg_rolemanagement->error_number = 9101;
           $iwg_rolemanagement->handle_error();
         }
       }
     } else {
       $iwg_rolemanagement->error_number = 9100;
       $iwg_rolemanagement->handle_error();
     }
     
     // don't let users remove edit_users from themselves
     if ($user->id == $user_ID && 
                 ( empty($_POST['caps']['edit_users']) || ($_POST['caps']['edit_users'] != 'on') ||
                   empty($_POST['caps'][$iwg_rolemanagement->neededcap]) || 
                 ($_POST['caps'][$iwg_rolemanagement->neededcap] != 'on')) ) {
        $iwg_rolemanagement->error_number = 9002;
        $iwg_rolemanagement->handle_error();
     }
     $rcvd_caps = $_POST['caps'];
     if($rcvd_caps) {
       foreach ($rcvd_caps as $k => $cap) {
         $k = $iwg_rolemanagement->check_input($k, 'int_cap', true);
         if ( $k ) {
           if ( ! $iwg_rolemanagement->capmanager->cap_exists($k) ) {
             $iwg_rolemanagement->error_number = 2010;
             $iwg_rolemanagement->handle_error();
           }
         } else {
           $iwg_rolemanagement->error_number += 2000;
           $iwg_rolemanagement->handle_error();
         }
       }
     } else {
       $rcvd_caps = array();
     }

     foreach ($iwg_rolemanagement->get_cap_list(false) as $cap) {
       //this looks stupid but it's really quite simple :)
       //if the user has just been denied an 'extra' cap (i.e., one that isn't a role), use remove_cap
       //(note that $user->caps is the array where role-caps aren't listed)
       if ($user->has_cap($cap) && array_key_exists($cap, $user->caps) && (!isset($rcvd_caps[$cap]) || $rcvd_caps[$cap] == 'off')) {
         //die( 'removing ' . $cap);
         $user->remove_cap($cap);
         //if the user has been denied a cap that was inherited from a role, explicity deny it
       } elseif ($user->has_cap($cap) && (!isset($rcvd_caps[$cap]) || $rcvd_caps[$cap] == 'off')) {
         $user->add_cap($cap, false);
         //if the user has been given a cap, that was previously denied (i.e., its a role cap), undeny it
       } elseif (!$user->has_cap($cap) && array_key_exists($cap, $user->allcaps) && !$user->allcaps[$cap] && $rcvd_caps[$cap] == 'on') {
         //die( 'undenying ' . $cap);
         unset($user->caps[$cap]); //remove the ban
         update_usermeta($user->id, $user->cap_key, $user->caps);
         //if we're just adding a new, extra cap, just good ol' fashioned add_cap :)
       } elseif (!$user->has_cap($cap) && $rcvd_caps[$cap] == 'on') {
         $user->add_cap($cap);
       }
     }
   }
   /* end of user-edit-widget functions */

   /**
    * get all assigned capabilities
    * R22
    * @param boolean $flip
    * @return array
    */
   function get_assigned_caps ( $flip = FALSE ) {
     /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
     global $wp_roles, $iwg_rolemanagement;

     $assigned_caps = array();
     $used_caps_in_roles = array();
     $caps = $iwg_rolemanagement->get_cap_list();
     foreach($wp_roles->role_names as $int_role => $role_name) {
       $role = $wp_roles->get_role($int_role);
       foreach($caps as $cap) {
         if ($role->has_cap($cap)) {
           $assigned_caps[$cap] = 1 ;
         }
       }
     }
     if ( ! $flip ) {
       $assigned_caps = array_keys($assigned_caps);
     }
     return $assigned_caps;
   }

   /**
    * get an array with unassigned capabilities
    * R22
    * @param boolean $flip
    * @return array
    */
   function get_unassigned_caps ( $flip = FALSE ) {
     /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
     global $wp_roles, $iwg_rolemanagement;

     $unassigned_caps = array();
     $assigned_caps = $this->get_assigned_caps(FALSE);
     $caps = $iwg_rolemanagement->get_cap_list();
     $unassigned_caps = array_diff($caps, $assigned_caps);
     if ( $flip ) {
       $unassigned_caps=$iwg_rolemanagement->fill_array_keys_with_true($unassigned_caps);
     }
     return $unassigned_caps;
   }

   /**
    * get an array with all detached capabilities
    * R22
    * @param boolean $flip
    * @return array
    */
   function get_detached_caps ( $flip = FALSE ) {
     /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
     global $wp_roles, $iwg_rolemanagement;

     $detached_caps = array();
     $assigned_caps = $this->get_assigned_caps(FALSE);
     $attached_roles = $iwg_rolemanagement->rolemanager->get_attached_roles(FALSE);
     foreach($attached_roles as $int_role => $role_name) {
       $role = $wp_roles->get_role($role_name);
       foreach($assigned_caps as $cap) {
         if ($role->has_cap($cap)) {
           $detached_caps[$cap] = 1 ;
         }
       }
     }

     $detached_caps = array_diff($assigned_caps, array_keys($detached_caps));
     if ($flip) {
       $detached_caps = $iwg_rolemanagement->fill_array_keys_with_true($detached_caps);
     }
     return $detached_caps;
   }

   /**
    * get an array with all dedicated capabilities
    * R22
    * @param boolean $flip
    * @return array
    */
   function get_dedicated_caps( $flip = FALSE ) {
     /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
     global $wp_roles, $iwg_rolemanagement;

     $dedicated_caps = array();
     $all_uids = $iwg_rolemanagement->get_all_user_ids();
     $unassigned_caps = $this->get_unassigned_caps(FALSE);
     //$this->debug('UNASSIGNED_CAPS ', $unassigned_caps);

     foreach ($all_uids as $user_id) {
       $user = new WP_User($user_id);
       foreach ($unassigned_caps as $cap) {
         if(isset($user->allcaps[$cap]) && $user->allcaps[$cap] == TRUE && isset($user->caps[$cap])) {
           $dedicated_caps[$cap] = 1 ;
         }
       }
     }
     if ( ! $flip) {
       $dedicated_caps=array_keys($dedicated_caps);
     }
     return $dedicated_caps;
   }

   /**
    * get an array with all unused capabilities
    * R22
    * @param boolean $flip
    * @return array
    */
   function get_unused_caps( $flip = FALSE ) {
     /* flip == TRUE -> result: [cap]=>true; flip == FALSE -> result: []=>cap */
     global $iwg_rolemanagement;
     $unused_caps=array();

     $caps = $iwg_rolemanagement->get_cap_list();
     $detached_caps=$this->get_detached_caps(FALSE);
     $dedicated_caps=$this->get_dedicated_caps(FALSE);
     $unassigned_caps = $this->get_unassigned_caps(FALSE);
     //$unused_caps=array_merge($detached_caps, $dedicated_caps);
     $unused_caps=array_diff($unassigned_caps, $dedicated_caps);
     if ($flip) {
       $unused_caps=$iwg_rolemanagement->fill_array_keys_with_true($unused_caps);
     }
     //$this->debug('DETACHED', $detached_caps);
     //$this->debug('DEDICATED', $dedicated_caps);
     //$this->debug('UNUSED', $unused_caps);
     //$this->debug('ASSIGNED', ($this->get_assigned_caps(FALSE)));
     //$this->debug('ASSIGNED', count($caps));
     //$this->debug('ASSIGNED', $caps);
     return $unused_caps;
   }

   /**
    * check if a given capability exists
    * R22
    * @param str $cap
    * @return boolean
    */
   function cap_exists($cap='') {
     global $iwg_rolemanagement;

     $ret_val = FALSE;
     if ( ! empty($cap) ) {
       $caps = $iwg_rolemanagement->get_cap_list();
       if(in_array($cap, $caps)) $ret_val = TRUE;
     }
     return $ret_val;
   }

   /* Utility Functions */
   /**
    * make a given capability readable
    * - edit_posts -> Edit Post
    * R22
    * @param str $cap
    * @return str
    */
   function get_cap_name($cap) {
     return ucwords(str_replace('_', ' ', $cap));
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