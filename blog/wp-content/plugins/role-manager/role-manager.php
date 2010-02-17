<?php
/*
Plugin Name: Role Manager
Plugin URI: http://www.im-web-gefunden.de/wordpress-plugins/role-manager/
Description: Role Management for WordPress 2.0.x, up to 2.6.x..
Version: 2.2.3
Author: Thomas Schneider
Author URI: http://www.im-web-gefunden.de/
Update Server:  http://www.im-web-gefunden.de/
Min WP Version: 2.0
Max WP Version: 2.6
License: MIT License - http://www.opensource.org/licenses/mit-license.php

Original coding by David House and Owen Winkler
Icons were provided by http://www.famfamfam.com/lab/icons/silk/ under
a Creative Commons Attribution 2.5 license.
 
*/

class IWG_RoleManagement {
	var $wp_content_url;
	var $wp_content_dir;
  var $path_file;
  var $file_basename;   /* under wp-content/plugins */
  var $plugin_base_uri;
  var $file_uri;
  var $rolemanager_file_basename;
  var $capmanager_file_basename;
  var $image_dir;
  var $style_dir;
  var $version_nr;
  var $version_txt;
  var $rolemanager;
  var $manage_roles_uri;
  var $capmanager;
  var $manage_capabilities_uri;
  var $neededcap;
  var $max_input_len;
  var $error_number;
  var $spaces_in_caps;
  var $new_admin_if;
  
  /**
   * the constructor
   * R23
   * @return IWG_RoleManagement
   */
  function IWG_RoleManagement() {
    global $wp_db_version;
    
    if ( !defined('WP_CONTENT_URL') ) {
    	$this->wp_content_url = get_option('siteurl') . '/wp-content';
    } else {
    	$this->wp_content_url = WP_CONTENT_URL;
    }
    $plugin_url = $this->wp_content_url.'/plugins/'.plugin_basename(dirname(__FILE__));
    if ( !defined('WP_CONTENT_DIR') ) {
    	$this->wp_content_dir = ABSPATH . 'wp-content';
    } else {
    	$this->wp_content_dir = WP_CONTENT_DIR;
    }
    $plugin_path = $this->wp_content_dir.'/plugins/'.plugin_basename(dirname(__FILE__));

    require_once($plugin_path.'/role-management.php');
    require_once($plugin_path.'/capability-management.php');
    require_once($plugin_path.'/general.php');
    require_once($plugin_path.'/help.php');
    
    $wp_db_version < 4772 ? $this->path_file='profile.php' : $this->path_file='users.php';
    $wp_db_version < 7558 ? $this->new_admin_if=FALSE : $this->new_admin_if=TRUE;
    $this->file_basename = plugin_basename(dirname(__FILE__)).'/'.basename(__FILE__);
    $this->file_uri = $plugin_url.'/'.basename(__FILE__);
    $this->plugin_base_uri = $plugin_url;
    $this->image_dir = $this->plugin_base_uri . '/images/';
    $this->style_dir = $this->plugin_base_uri . '/styles/';
    $this->neededcap = 'manage_roles';
    $this->version_nr = (2 << 16) | (2 << 8) | 3; /* major.minor.patch */
    $this->version_txt = '';
    $this->rolemanager = new RoleManager($this->path_file);
    $this->manage_roles_uri = $this->rolemanager->manage_roles_uri;
    $this->capmanager = new CapabilityManager($this->path_file);
    $this->manage_capabilities_uri = $this->capmanager->manage_capabilities_uri;
    $this->general = new IWG_RoleManagementGeneral($this->path_file);
    $this->manage_general_uri = $this->general->manage_general_uri;
    $this->help = new IWG_RoleManagementHelp($this->path_file);
    $this->manage_help_uri = $this->help->manage_help_uri;
    $this->max_input_len = 30;
    $this->error_number = 0;

    add_action('init', array(&$this, 'role_manager_init'));
    
    $this->spaces_in_caps = get_option('IWG_RoleMan_Spaces_in_Caps');
    if ( empty($this->spaces_in_caps) ) {
    	$this->spaces_in_caps = FALSE;
    }

    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('edit_user_profile', array(&$this->capmanager, 'manage_user_caps_page'));
    if (strstr($_SERVER['REQUEST_URI'], 'user-edit.php') !== false) {
      add_action('init', array(&$this->capmanager, 'handle_user_caps_edit'));
      add_action('admin_head', array(&$this, 'admin_head'));
    }
    if (strstr($_SERVER['REQUEST_URI'], $this->path_file) !== false) {
      add_action('init', array(&$this, 'handle_role_caps_edit'));
      add_action('init', array(&$this->rolemanager, 'process_role_changes'));
      add_action('admin_head', array(&$this, 'admin_head'));
    }
  }

  /**
   * the first init for role manager
   * - only load the translations 
   * R23
   */
  function role_manager_init() {
  	$path = substr($this->wp_content_dir,strlen(ABSPATH)).'/plugins/role-manager/languages';
		load_plugin_textdomain('role-manager', $path);
	}
  
	/**
	 * show role manger parts in the admin menu
	 * R22
	 */
  function admin_menu() {
		if ( $this->user_has_permissions() ) {
      if ( function_exists('wp_enqueue_script') ) {
        wp_enqueue_script('sack');
      } else {
        global $sack_js;
        $sack_js = true;
      }
      add_submenu_page($this->path_file, __('Role Management', 'role-manager'), __('Roles', 'role-manager'), 'edit_users', $this->rolemanager->file_basename,
                       array(&$this->rolemanager, 'manage_roles_page'));
      add_submenu_page($this->path_file, __('Capability Management', 'role-manager'), __('Capabilities', 'role-manager'), 'edit_users', $this->capmanager->file_basename,
                        array(&$this->capmanager, 'manage_caps_page'));
      add_submenu_page($this->path_file, __('Role-/Capability Management General', 'role-manager'), __('Role-/Capability Management General', 'role-manager'), 'edit_users',  $this->general->file_basename,
                      array(&$this->general, 'manage_general_page'));
			add_submenu_page($this->path_file, __('Role-/Capability Management Help', 'role-manager'), __('Role-/Capability Management Help', 'role-manager'), 'edit_users', $this->help->file_basename,
                      array(&$this->help, 'manage_help_page'));
    }
  }

	/**
	 * put needed javascript in the header of each page
	 * R22
	 */
  function admin_head() {
  	if ($this->new_admin_if) {
  		$levelAnimation = 'animateRoleManagerMessage(\'#\'+ rolename + "___user_level", true);';
  		$messageAnimation = 'animateRoleManagerMessage(\'.fade\', false);';
  		$capAnimation = 'animateRoleManagerMessage(\'#\'+ btn.parentNode.id, true);';
  		$jQueryAnimation = '
  		function animateRoleManagerMessage(element, backToOrigBG) {
				var endBG = \'#fffbcc\';
				if (backToOrigBG) {
					endBG = jQuery(element).css("backgroundColor");
				}			
				jQuery(element).animate( { backgroundColor: \'#ffffe0\' }, 300).animate( { backgroundColor: \'#fffbcc\' }, 300).animate( { backgroundColor: \'#ffffe0\' }, 300).animate( { backgroundColor: endBG }, 1000);
			};
  		';
  	} else {
  		$levelAnimation = 'Fat.fade_element(rolename + "___user_level");';
  		$messageAnimation = 'Fat.fade_all();';
  		$capAnimation = 'Fat.fade_element(btn.parentNode.id);';  		
  		$jQueryAnimation = '';
  	}
   echo '
  <link rel="stylesheet" href="'.$this->style_dir.'style.css" type="text/css" />
    <script type="text/javascript">
    function badidea() {
      alert("' . addslashes(__("You can't remove this permission from a role assigned to you!", 'role-manager')) . '");
			return false;
  	}
    function setdefaultrole(rolename) {
      var ajax = new sack();
      ajax.requestFile = "' . $this->manage_roles_uri . '";
      ajax.setVar("action", "makedefault");
      ajax.setVar("role", rolename);      
      ajax.setVar("ajax", "1");
      ajax.execute = true;
      //ajax.element = "toast";  // Debug ajax returned script
      ajax.runAJAX();
      return true;    
    }
    function setlevel(level,rolename) {
      var ajax = new sack();
      ajax.requestFile = "' . $this->manage_roles_uri . '";
      ajax.setVar("action", "setuserlevel");
      ajax.setVar("role", rolename);
      ajax.setVar("level", level);
      ajax.setVar("ajax", "1");
      ajax.execute = true;
      //ajax.element = "toast";  // Debug ajax returned script
      ajax.runAJAX();
      return true;      
    }
    function fadeuserlevel(rolename) {
      '.$levelAnimation.'
    }
    function showdefaultrole(rolename) {
      var imgs = document.getElementsByTagName("IMG");
      for(z=0;z<imgs.length;z++) {
        if(imgs[z].id == "defrole_" + rolename) {
          imgs[z].src = "' . $this->image_dir . 'star.png";
          imgs[z].className = "defrole";
        }
        else if(imgs[z].className == "defrole") {
          imgs[z].src = "' . $this->image_dir . 'star_disabled.png";
          imgs[z].className = "nondefrole";
        }
      }
    }
    function submitme(frm) {
      var ajax = new sack();
      ajax.requestFile = "' . $this->manage_roles_uri . '";
      inputs = frm.getElementsByTagName("INPUT");
      for(z=0;z<inputs.length;z++) {
        ajax.setVar(inputs[z].name, inputs[z].value);
      }
      ajax.setVar("ajax", "1");
      ajax.execute = true;
      //ajax.element = "toast";  // Debug ajax returned script
      ajax.runAJAX();
      return false;
    }
    function toggleCap(capbtnname) {
      var btn = document.getElementById(capbtnname);
      btn.value = (btn.value == "0") ? "1" : "0";
      btn.src = "' . $this->image_dir . '" + ((btn.value == "0") ? "accept.png" : "cancel.png");
      '.$capAnimation.'
	  }
    function setMessage(message) {
      var msg = document.getElementById("message");
      try {
        msg.innerHTML = "<p>" + message + "</p>";
      }
      catch(e) {
        msg = document.createElement("DIV");
        msg.className = "updated fade";
        msg.setAttribute("id", "message");
        main = document.getElementById("main_page");
        main.parentNode.insertBefore(msg, main);
        msg.innerHTML = "<p>" + message + "</p>";
      }'.
      $messageAnimation.'
    }'
    .$jQueryAnimation.'
		</script>
		';
   if ($this->new_admin_if) {
   	echo '<link rel="stylesheet" href="'.$this->style_dir.'new_admin_style.css" type="text/css" />';
   }
  }


  /**
   * dispatch the action grand a cap for a role, create new role, create new cap and purge unused caps
   * R22
   */
  function handle_role_caps_edit() {
    if( $this->user_has_permissions () ) {
      if (isset ($_POST['grant'])) {
        $this->rolemanager->handle_role_changes();
      } elseif (isset($_POST['new-role'])) {
        $this->rolemanager->handle_new_role_creation();
      } elseif (isset($_POST['new-cap'])) {
        $this->capmanager->handle_new_cap_creation();
      } elseif (isset($_POST['purge-caps'])) {
        $this->capmanager->handle_cap_purge();
      }
    } else {
    	// now make more checks here to give the right feedback to the user
    	// TODO make better feedback if user without cap to use role manger call a role manger url
    	//      such a user got no feedback in the moment 
    	if ( ! current_user_can('edit_users')) {
    		// if a user has cap to edit users he don't see this message but he can't use the role manager
    		$this->error_number = 9000;
    		$this->handle_error();
    	}
    	//else {
    	//if ( ! current_user_can($this->neededcap)) {
    	// if a user has cap to edit users he don't see this message but he can't use the role manager
    	//$this->error('<p>You are not allowed to manage roles here.</p>');
    	//}
    	//}
    }
  }

  /**
   * check the input after transmission to the server
   * R22
   * @param str $input - the input
   * @param str $type - not used in the moment
   * @param boolean $us_allowed - is "_" allowed
   * @param boolean $sp_allowed - is " " allowed
   * @param boolean $min_allowed - is "-" allowed
   * @param boolean $trim - should the input trimed
   * @return mixed: (not)trimmed input or false
   */
  function check_input($input, $type = "", $us_allowed = false, $sp_allowed = false, $min_allowed = false, $trim = true) {
    $ret_val = $trim ? trim($input) : $input;
    // $this->debug("mbstrlen", ($this->mbstrlen($ret_val)));

    if ( $type == 'int_cap' ) {
    	/* check for transmitted caps, some plugins using caps with spaces */
    	$sp_allowed = $this->spaces_in_caps;
    }
    if ( ($ret_val == "") || ( $this->mbstrlen($ret_val) > $this->max_input_len ) ) {
      $ret_val = false;
      $this->error_number = 100;
    } else {
      $t_ret_val = $ret_val;
      $regexp = '#[\W_]#';
      /* handle us_allowed */
      if ( $us_allowed ) {
            $t_ret_val = preg_replace('#_#', '', $t_ret_val);
            $regexp = '#[\W]#';
      }
      /* handle space allowed */
      if ( $sp_allowed ) {
            $t_ret_val = preg_replace('#\s#', '', $t_ret_val);
            /* remove duplicates and set all to one whitespace */
            $ret_val = preg_replace('#\s#', ' ', $ret_val);
      }
      /* handle minus sign allowed */
      if ( $min_allowed ) {
            $t_ret_val = preg_replace('#-#', '', $t_ret_val);
      }
      if ( preg_match($regexp, $t_ret_val) ) {
        /* input isn't allowed or utf-8 - check against utf-8 */
        if ( preg_match('/[\x80-\xff]/', $t_ret_val) ) {
          /* input has utf-8 chars inside - need more checks here */
          $t_ret_val = remove_accents($t_ret_val);
          if ( preg_match($regexp, $t_ret_val) ) {
            /* after removing utf-8 chars input isn't valid */
            $this->error_number = 120;
            $ret_val = false;
          } else {
            /* input has utf-8 chars but is valid */
          }
        } else {
          /* input has no utf-8 chars and is not valid */
          $this->error_number = 110;
          $ret_val = false;
        }
      } else {
        /* input is valid and has no utf-8 chars inside */
      }
    }
    // $this->debug('input_err', ($this->input_err));
    // $this->debug('ret_val', $ret_val);
    return $ret_val;
  }
  
  /**
   * Check if a given newone already exists as a role or as a capability
   * both checks are doing in every case to prevent duplicates or naming conflicts between roles and capabilities
   * R22
   * TODO need a little bit styling
   * @param str $newone
   * @param str $type
   * @return boolean
   */
   function already_exists($newone="", $type='role') {
    global $wp_roles, $iwg_rolemanagement;
    $ret_val = FALSE;
    $newone = strtolower($newone);
    
    if ($type == 'role') {
      // TODO This strips out also multibyte-chars - should it changed?
      $newone = preg_replace('#[^a-z0-9]#', '_', $newone);
      if ( $iwg_rolemanagement->capmanager->cap_exists($newone) ) {
        $this->error_number = 1130;
        $ret_val = TRUE;
      }
    } elseif ( $type == 'cap' ) {
      //$this->debug($newone);
      //$this->debug($wp_roles);
      //die();
      if ( $wp_roles->is_role($newone) ) {
        $this->error_number = 2130;
        $ret_val = TRUE;
      }
    } else {
      // wrong type!
      $ret_val = TRUE;
    }
    return $ret_val;
  }

  /**
   * check if the user has the right permissions to use the role manager
   * R22
   * @return boolean
   */
  function user_has_permissions () {
    $ret_val = false;
    if( current_user_can('edit_users') && current_user_can($this->neededcap)) {
      $ret_val = true;
    }
    return $ret_val;
  }

  /**
   * get an array with all capabilities
   * R22
   * @param boolean $roles
   * @param boolean $kill_levels
   * @return array
   */
  function get_cap_list($roles = true, $kill_levels = true) {
    global $wp_roles;
    
    // Get Role List
    foreach($wp_roles->role_objects as $key => $role) {
      foreach($role->capabilities as $cap => $grant) {
        $capnames[$cap] = $cap;
        //$this->debug('grant', ($role->capabilities));
      }
    }
    
    if ($caplist = get_option('IWG_RoleMan_CapList')) {
      $capnames = array_unique(array_merge($caplist, $capnames));
    }
    
    $capnames = apply_filters('capabilities_list', $capnames);
    if(!is_array($capnames)) $capnames = array();
    $capnames = array_unique($capnames);
    sort($capnames);

    //Filter out the level_x caps, they're obsolete
    if($kill_levels) {
      $capnames = array_diff($capnames, array('level_0', 'level_1', 'level_2', 'level_3', 'level_4', 'level_5',
        'level_6', 'level_7', 'level_8', 'level_9', 'level_10'));
    }
    
    //Filter out roles if required
    if (!$roles) {
      foreach ($wp_roles->get_names() as $role) {
        $key = array_search($role, $capnames);
        if ($key !== false && $key !== null) { //array_search() returns null if not found in 4.1
          unset($capnames[$key]);
        }
      }
    }
    return $capnames;
  }

  /**
   * store all capabilities in the option table
   * R22
   * @param array $caplist
   */
  function set_cap_list ($caplist) {
    if(!is_array($caplist)) $caplist = array();
    update_option('IWG_RoleMan_CapList', $caplist);
  }

  /**
   * get all user ids for a role
   * R22
   * @param str $role
   * @return array
   */
  function get_all_userids_with_role($role) {
    global $wpdb;
    $userids_in_role = array();
    
    if ( $userids = $this->get_all_user_ids() ) {
      foreach($userids as $userid) {
        $user = new WP_User($userid);
        $user->roles = $this->fill_array_keys_with_true( $user->roles );
        if ( in_array($role, array_keys($user->roles))) {
          $userids_in_role[]=$userid;
        }
      }
    }
    return $userids_in_role;
  }

  /**
   * get all user_ids
   * R22
   * @return array
   */
  function get_all_user_ids() {
    if ($ids = wp_cache_get('all_user_ids', 'users')) {
      return $ids;
    } else {
      global $wpdb;
      $ids = $wpdb->get_col('SELECT ID from ' . $wpdb->users);
      wp_cache_set('all_user_ids', $ids, 'users');
      return $ids;
    }
  }

  /**
   * wrapper for wp_nonce_field for wordpress
   * R22
   * @param str $action
   * @return str
   */
  function nonce_field ( $action = -1 ) {
    if ( !function_exists('wp_nonce_field') ) {
      return;
    } else {
      return wp_nonce_field($action);
    }
  }

  /**
   * wrapper for wp_nonce_url for wordpress
   * R22
   * @param str $action_url
   * @param str $action
   * @return str
   */
  function nonce_url ( $action_url, $action = -1 ) {
    if ( !function_exists('wp_nonce_url') ) {
      return;
    } else {
      return wp_nonce_url($action_url, $action);
    }
  }

  /**
   * wrapper for check_admin_referer for wordpress
   * R22
   * @param str $action
   */
  function check_admin_ref ($action = -1 ) {
    if ( function_exists('check_admin_referer') ) {
      check_admin_referer($action);
    }
  }

  /**
   * multibyte strlen wrapper for php
   * R22
   * @param string $str
   * @return len
   */
  function mbstrlen ($str = "") {
    if ( function_exists('mb_strlen') ) {
      $len = mb_strlen($str);
    } else {
      $len = strlen(remove_accents($str));
    }
    return $len;
  }

	/**
	 * flips an assoz. array an fill all keys with true
	 * R22 
	 * @param array $arr
	 * @return array
	 */
  function fill_array_keys_with_true( $arr ) {
    $arr = array_flip($arr);
    foreach ($arr as $key=>$val) { $arr[$key] = true ; };
    return ($arr);
  }

	/**
	 * get the role manager plugin version
	 * R22
	 * @param str $form
	 * @return version in different forms
	 */
  function get_version($form='str') {
    switch ($form) {
      case 'int': $ret_val = $this->version_nr;
                  break;
      case 'l_str': $ret_val = sprintf("%02d.%02d.%02d %s", $this->version_nr >> 16, ($this->version_nr & 0x00FF00) >> 8,
                                ($this->version_nr & 0xFF), $this->version_txt);
                    break;
      default: $ret_val = sprintf("%02d.%02d.%02d", $this->version_nr >> 16, ($this->version_nr & 0x00FF00) >> 8, ($this->version_nr & 0xFF));
                    break;
    }
    return $ret_val; 
  }

  /**
   * handle errors
   * - translate error-number to error-text
   * R22
   * @param str $detailinfo
   */
  function handle_error ($detailinfo ="") {
    /* error_number values:
     * 1000 - 1999 -> Role Errors
     * 2000 - 2999 -> Caps Errors
     * 9000 - 9999 -> Generic Errors (no permissions and so on ) 
     */
    $error_text = array (
      '1000' => __('You must enter a role name.','role-manger'),
      '1010' => sprintf(__('Can\'t rename. A Role with the name %s already exists.','role-manger'), $detailinfo),
      '1020' => __('You cannot delete the default role.', 'role-manager'),
      '1030' => sprintf(__('Can\'t make %s the default. Not a role.', 'role-manager'), $detailinfo),
      '1040' => sprintf(__('Can\'t create the new Role. A Role with the name %s already exists.','role-manger'), $detailinfo),
      '1100' => __('Role name to short or to long','role-manger'),
      '1110' => __('A valid role name can only have letters, digits and spaces','role-manger'),
      '1120' => __('A valid role name can only have letters, digits and spaces','role-manger'),
      '1130' => sprintf(__('Can\'t create the new Role. A Capability with the name %s already exists.','role-manger'), $detailinfo),
      '1900' => __('You must enter a valid user level','role-manger'),
      '2000' => __('You must enter a capability name.','role-manger'),
      '2010' => __('Capability don\'t exists.','role-manger'),
      '2100' => __('Capability name to short or to long','role-manger'),
      '2110' => __('A valid capability name can only have letters, digits and spaces. Try Role Managers "Spaces allowed in Capabilities" option.','role-manger'),
      '2120' => __('A valid capability name can only have letters, digits and spaces. Try Role Managers "Spaces allowed in Capabilities" option.','role-manger'),
      '2130' => sprintf(__('Can\'t create the new Capability. A Role with the name %s already exists.','role-manger'), $detailinfo),
      '9000' => __('You are not allowed to do this here','role-manger'),
      '9001' => __("You can't remove this permission from a role assigned to you!", 'role-manager'),
      '9002' => __('You cannot remove the Edit Users or Manage Roles capability from yourself.'),
      '9100' => __("Wrong User-ID!", 'role-manager'),
      '9101' => __("User don't exists!", 'role-manager')
    );
    $this->error($error_text[$this->error_number]);
  }

  /**
   * store how the capability-checker should handle spaces in capabilities
   * - problem with other plugins
   * - many plugins add caps with spaces
   *
   * @param boolean $spaces_allowed
   */
  function store_cap_spaces_handling( $spaces_allowed = FALSE ) {
  	if ( $spaces_allowed !== TRUE) $spaces_allowed = FALSE;
  	update_option('IWG_RoleMan_Spaces_in_Caps', $spaces_allowed);
  	$this->spaces_in_caps = $spaces_allowed;
  }
  
  /**
   * output error and die
   * TODO Better errorhandling
   * @param unknown_type $error
   */
  function error($error) {
    die($error);
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

$iwg_rolemanagement = new IWG_RoleManagement();

/* Plugin activation/deactivation-functions */
function RoleManager_Plugin_Activate () {
  global $iwg_rolemanagement, $wp_roles;
  $has_old_caplist = TRUE;
  $has_new_caplist = TRUE;
  
  if ( empty($iwg_rolemanagement) ) {
    $iwg_rolemanagement = new IWG_RoleManagement();
  }
  /* check if an older version have exist before */
  $old_caplist = get_option('caplist');
  if ( empty($old_caplist) ) {
    $has_old_caplist = FALSE;
  }
  /* check if an newer version have exist before */
  $new_caplist = get_option('IWG_RoleMan_CapList');
  if ( empty($new_caplist) ) {
    $has_new_caplist = FALSE;
  }
  if ( (!$has_old_caplist) && (!$has_new_caplist) ) {
    $iwg_rolemanagement->set_cap_list($iwg_rolemanagement->get_cap_list());
  } elseif ( (!$has_old_caplist) && ($has_new_caplist) ) {
    /* in next versions do a update check here if needed */
  } elseif ( ($has_old_caplist) && (!$has_new_caplist) ) {
    $iwg_rolemanagement->set_cap_list($old_caplist);
    delete_option('caplist');
  } elseif ( ($has_old_caplist) && ($has_new_caplist) ) {
    delete_option('caplist');
  }
  $status = array ('status' => 'active', 'version' => $iwg_rolemanagement->get_version('int'));
  update_option('IWG_RoleManager', $status);
  if ( $iwg_rolemanagement->capmanager->cap_exists($iwg_rolemanagement->neededcap) ) {
    $unused_caps=$iwg_rolemanagement->capmanager->get_unused_caps(FALSE);
    if(in_array($iwg_rolemanagement->neededcap, $unused_caps)) {
      $wp_roles->add_cap('administrator', $iwg_rolemanagement->neededcap, true);
    }
  } else {
    $wp_roles->add_cap('administrator', $iwg_rolemanagement->neededcap, true);
    $iwg_rolemanagement->set_cap_list($iwg_rolemanagement->get_cap_list());
  }
}
function RoleManager_Plugin_Deactivate () {
  global $iwg_rolemanagement;
  $status = array ('status' => 'not active', 'version' => $iwg_rolemanagement->get_version('int'));
  update_option('IWG_RoleManager', $status);
}
add_action('activate_role-manager/role-manager.php', 'RoleManager_Plugin_Activate');
add_action('deactivate_role-manager/role-manager.php', 'RoleManager_Plugin_Deactivate');
?>
