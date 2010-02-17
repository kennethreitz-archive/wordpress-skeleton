<?php

class IWG_RoleManagementHelp {
  var $file_basename;   /* under wp-content/plugins */
  var $manage_help_uri;

	/**
	 * the constructor
	 * R23
	 * @param str $path_file
	 * @return IWG_RoleManagementHelp
	 */
  function IWG_RoleManagementHelp($path_file='') {
  	$this->file_basename = basename(__FILE__);
    $this->manage_help_uri = get_settings('siteurl') . '/wp-admin/'.$path_file.'?page=' . $this->file_basename;
  }
  
  /**
   * show the complete help page
   * R22
   */
  function manage_help_page() {
    global $wp, $wp_version, $wp_db_version, $wp_roles, $current_user, $iwg_rolemanagement;
    $lang = get_locale();
    if ( empty($lang) ) $lang = 'en_EN';
    $help_file = dirname(__FILE__).'/help/help.'.$lang;
    $def_help_file = dirname(__FILE__).'/help/help.en_EN';
    ?>
    <div class="wrap" id="main_page">
    <h2><?php _e('Help for the Role Manager Plugin', 'role-manager');?></h2>
    <div class="rolemanagement_help">
    <h3><?php _e('General Help Contents', 'role-manager');?></h3>
    <ul>
      <li><a href="#help_toc"><?php _e('Help TOC', 'role-manager');?></a></li>
      <li><a href="#help_system_info"><?php _e('System Information for your Support Request', 'role-manager');?></a></li>
    </ul>
    <?php
    if ( file_exists($help_file) ) {
      $this->printout_help($help_file);
    } elseif ( file_exists($def_help_file) ) {
      $this->printout_help($def_help_file);
    } else {
      _e('No help file found!', 'role-manager');
    }
    ?><h2 id="help_system_info"><?php _e('System Information for your Support Request', 'role-manager');?></h2>
    <p><?php _e('Use this information if you have any problems or questions with the "Role Manager" at the "<a href="http://www.im-web-gefunden.de/wordpress-plugins/role-manager" title="Role Manager Plugin Homepage">Role Manager Plugin Homepage</a>".','role-manager');?></p>
    <div class="rolemanagement_sysinfo">
    <?php
    echo __('WordPress-Version','role-manager').' : '. $wp_version;?><br /><?php
    echo __('WordPress-Db-Version','role-manager').' : '. $wp_db_version;?><br /><?php
    echo __('Role-Manager-Version','role-manager').' : '. $iwg_rolemanagement->get_version('l_str') . '(' .$iwg_rolemanagement->get_version('int') . ')';?><br /><?php
    echo __('PHP-Version','role-manager').' : '. phpversion();?><br /><br /><?php
    
    echo __('preg_replace','role-manager').' : '. (function_exists('preg_replace') ? __('yes', 'role-manager') : __('no', 'role-manager'));?><br /><br /><?php
    echo __('Help-URI','role-manager').' : ' . $this->manage_help_uri;?><br /><?php
    ?>
    </div>
    <p><?php _e('Your comments are also welcome!','role-manager');?></p>
    </div>
    </div><?php
  }
  
  /**
   * read and printout the given helpfile
   * R22
   * @param str $file
   */
  function printout_help($file) {
    $handle=fopen($file, 'r');
    while(!feof($handle)) {
      $buffer = fgets($handle);
      echo $buffer;
    }
    fclose($handle);
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