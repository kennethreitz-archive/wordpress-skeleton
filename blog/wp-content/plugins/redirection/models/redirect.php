<?php
/**
 * Redirection
 *
 * @package Redirection
 * @author John Godley
 * @copyright Copyright (C) John Godley
 **/

/*
============================================================================================================
This software is provided "as is" and any express or implied warranties, including, but not limited to, the
implied warranties of merchantibility and fitness for a particular purpose are disclaimed. In no event shall
the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
consequential damages (including, but not limited to, procurement of substitute goods or services; loss of
use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of
this software, even if advised of the possibility of such damage.

For full license details see license.txt
============================================================================================================ */
class Red_Item
{
  var $id          = null;
	var $url         = null;
	var $regex       = false;
	var $action_data = null;
	
	var $last_access   = null;
	var $last_count    = 0;
	
	var $tracking      = true;
	
	function Red_Item ($values, $type = '', $match = '')
	{
		if (is_array ($values))
		{
			foreach ($values AS $key => $value)
			 	$this->$key = $value;

			if ($this->match_type)
			{
				$this->match              = Red_Match::create ($this->match_type, $this->action_data);
				$this->match->id          = $this->id;
				$this->match->action_code = $this->action_code;
			}
			
			if ($this->action_type)
			{
				$this->action        = Red_Action::create ($this->action_type, $this->action_code);
				$this->match->action = $this->action;
			}
			else
				$this->action = Red_Action::create ('nothing', 0);

			if ($this->last_access == '0000-00-00 00:00:00')
				$this->last_access = 0;
			else
				$this->last_access = mysql2date ('U', $this->last_access);
		}
		else
		{
			$this->url   = $values;
			$this->type  = $type;
			$this->match = $match;
		}
	}
	
	function get_all_for_module ($module)
	{
		global $wpdb;
		
		$sql = "SELECT @redirection_items.*,@redirection_groups.tracking FROM @redirection_items INNER JOIN @redirection_groups ON @redirection_groups.id=@redirection_items.group_id AND @redirection_groups.status='enabled' AND @redirection_groups.module_id='$module' WHERE @redirection_items.status='enabled' ORDER BY @redirection_groups.position,@redirection_items.position";
		$sql = str_replace ('@', $wpdb->prefix, $sql);

		$rows = $wpdb->get_results ($sql, ARRAY_A);
		$items = array ();
		if (count ($rows) > 0)
		{
			foreach ($rows AS $row)
				$items[] = new Red_Item ($row);
		}
		
		return $items;
		
	}
	
	function get_for_url ($url, $type)
	{
		global $wpdb;
		
		$sql = "SELECT @redirection_items.*,@redirection_groups.tracking,@redirection_modules.id AS module_id FROM @redirection_items INNER JOIN @redirection_groups ON @redirection_groups.id=@redirection_items.group_id AND @redirection_groups.status='enabled' INNER JOIN @redirection_modules ON @redirection_modules.id=@redirection_groups.module_id AND @redirection_modules.type='$type' WHERE (@redirection_items.regex=1 OR @redirection_items.url='".$wpdb->escape ($url)."' OR @redirection_items.url='".$wpdb->escape( urldecode( $url ) )."') ORDER BY @redirection_groups.position,@redirection_items.position";
		$sql = str_replace ('@', $wpdb->prefix, $sql);

		$rows = $wpdb->get_results ($sql, ARRAY_A);
		$items = array ();
		if (count ($rows) > 0)
		{
			foreach ($rows AS $row)
				$items[] = new Red_Item ($row);
		}
		
		return $items;
	}
	
	function get_by_module (&$pager, $module)
	{
		global $wpdb;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}redirection_items INNER JOIN {$wpdb->prefix}redirection_groups ON {$wpdb->prefix}redirection_groups.id={$wpdb->prefix}redirection_items.group_id";
		$sql .= $pager->to_limits ("{$wpdb->prefix}redirection_groups.module_id=".$module, array ('url', 'action_data'));
		
		$rows = $wpdb->get_results ($sql, ARRAY_A);
		$pager->set_total ($wpdb->get_var ("SELECT FOUND_ROWS()"));
		$items = array ();
		if (count ($rows) > 0)
		{
			foreach ($rows AS $row)
				$items[] = new Red_Item ($row);
		}
		
		return $items;
	}
	
	function get_by_group ($group, &$pager)
	{
		global $wpdb;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}redirection_items ";
		$sql .= $pager->to_limits ('group_id='.$group, array ('url', 'action_data'));
		
		$rows = $wpdb->get_results ($sql, ARRAY_A);
		$pager->set_total ($wpdb->get_var ("SELECT FOUND_ROWS()"));
		$items = array ();
		if (count ($rows) > 0)
		{
			foreach ($rows AS $row)
				$items[] = new Red_Item ($row);
		}
		
		return $items;
	}
	
	function get_by_id ($id)
	{
		global $wpdb;
		
		$id = intval ($id);
		$row = $wpdb->get_row ("SELECT * FROM {$wpdb->prefix}redirection_items WHERE id='$id'", ARRAY_A);
		if ($row)
			return new Red_Item ($row);
		return false;
	}
	
	function auto_generate ()
	{
		global $redirection;
		
		$options = $redirection->get_options ();
		$id = time ();

		$url = $options['auto_target'];
		$url = str_replace ('$dec$', $id, $url);
		$url = str_replace ('$hex$', sprintf ('%x', $id), $url);
		return $url;
	}
	
	function create ($details)
	{
		global $wpdb;
		
		// Auto generate URLs
		if ($details['source'] == '')
			$details['source'] = Red_Item::auto_generate ();

		if ($details['target'] == '')
			$details['target'] = Red_Item::auto_generate ();

		// Make sure we don't redirect to ourself
		if ($details['source'] == $details['target'])
			$details['target'] .= '-1';
		
		$matcher = Red_Match::create ($details['match']);
		$group_id  = intval ($details['group']);

		if ($group_id > 0 && $matcher)
		{
			$match    = $wpdb->escape ($details['match']);
			$regex    = (isset ($details['regex']) && $details['regex'] != false) ? true : false;
			$url      = $wpdb->escape (Red_Item::sanitize_url ($details['source'], $regex));
			$action   = $details['red_action'];
			$position = $wpdb->get_var ("SELECT COUNT(id) FROM {$wpdb->prefix}redirection_items WHERE group_id='{$group_id}'");

			$data = $wpdb->escape ($matcher->data ($details));
			
			if ($action == 'url' || $action == 'random')
				$action_code = 301;
			else if ($action == 'error')
				$action_code = 404;
			else
				$action_code = 0;
				
			if (isset ($details['action_code']))
				$action_code = intval ($details['action_code']);

			// Quick check for loop
//			if ($wpdb->get_var ("SELECT COUNT(id) FROM {$wpdb->prefix}redirection_items WHERE url='$url'") == 0)
			{
				$wpdb->query ("INSERT INTO {$wpdb->prefix}redirection_items (url,action_type,regex,position,match_type,action_data,action_code,last_access,group_id) VALUES ('$url','$action','".($regex ? 1 : 0)."','$position','$match','$data',$action_code,0,'$group_id')");
			
				$group = Red_Group::get ($group_id);
				Red_Module::flush ($group->module_id);
			
				return Red_Item::get_by_id ($wpdb->insert_id);
			}
		}

		return false;
	}
	
	function delete_by_group ($group)
	{
		global $wpdb;

		RE_Log::delete_for_group ($group);

		$wpdb->query ("DELETE FROM {$wpdb->prefix}redirection_items WHERE group_id='$group'");
		
		$group = Red_Group::get ($group_id);
		Red_Module::flush ($group->module_id);
	}
	
	function delete ($id)
	{
		global $wpdb;
		
		$id = intval ($id);
		$wpdb->query ("DELETE FROM {$wpdb->prefix}redirection_items WHERE id='$id'");
		
		RE_Log::delete_for_id ($id);
		
		// Reorder all elements
		$rows = $wpdb->get_results ("SELECT id FROM {$wpdb->prefix}redirection_items ORDER BY position");
		if (count ($rows) > 0)
		{
			foreach ($rows AS $pos => $row)
				$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET position='$pos' WHERE id='{$row->id}'");
		}
	}
	
	
	function sanitize_url ($url, $regex)
	{
		// Make sure that the old URL is relative
		$url = preg_replace ('@https?://(.*?)/@', '/', $url);
		$url = preg_replace ('@https?://(.*?)$@', '/', $url);
		$url = preg_replace ('@/{2,}@', '/', $url);

		if (substr ($url, 0, 1) != '/' && $regex == false)
			$url = '/'.$url;
		return $url;
	}
	
	
	function update ($details)
	{
		if (strlen ($details['old']) > 0)
		{
			global $wpdb;
			
			$this->url   = $details['old'];
			$this->regex = isset ($details['regex']) ? true : false;
			$this->title = $details['title'];
		
			// Update the match
			$this->url = $this->sanitize_url ($this->url, $this->regex);
			
			$data  = $wpdb->escape ($this->match->data ($details));
			$url   = $wpdb->escape ($this->url);
			$title = $wpdb->escape ($this->title);
			$regex = isset ($details['regex']) ? 1 : 0;
			
			if (isset ($details['action_code']))
				$action_code = intval ($details['action_code']);
			else
				$action_code = 0;

			$this->action_code = $action_code;
			$group_id = $this->group_id;
			if (isset ($details['group_id']))
				$group_id = intval ($details['group_id']);
			
			// Save this
			global $wpdb;
			$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET url='$url', regex='{$regex}', action_code='$action_code', action_data='$data', group_id='$group_id', title='$title' WHERE id='{$this->id}'");
			
			$group = Red_Group::get ($group_id);
			Red_Module::flush ($group->module_id);
		}
	}
	
	function save_order ($items, $start)
	{
		global $wpdb;
		
		foreach ($items AS $pos => $id)
			$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET position='".($pos + $start)."' WHERE id='{$id}'");
		
		$item  = Red_Item::get_by_id ($id);
		$group = Red_Group::get ($item->group_id);
		Red_Module::flush ($group->module_id);
	}

	function matches( $url ) {
		$this->url = str_replace (' ', '%20', $this->url);
		$matches = false;

		// Check if we match the URL
		if (($this->regex == false && ($this->url == $url || $this->url == rtrim ($url, '/') || $this->url == urldecode( $url ))) || ($this->regex == true && @preg_match ('@'.str_replace ('@', '\\@', $this->url).'@', $url, $matches) > 0) || ($this->regex == true && @preg_match ('@'.str_replace ('@', '\\@', $this->url).'@', urldecode( $url ), $matches) > 0))
		{
			// Check if our match wants this URL
			$target = $this->match->get_target ($url, $this->url, $this->regex);
			if ($target)
			{
				$target = $this->replaceSpecialTags ($target);
				$this->visit ($url, $target);
				if ($this->status == 'enabled')
					return $this->action->process_before ($this->action_code, $target);
			}
		}
		
		return false;
	}
	
	function replaceSpecialTags ($target)
	{
		if (is_numeric($target))
			$target = get_permalink($target);
		else {
			$user = wp_get_current_user ();
			if (!empty($user))
			{
				$target = str_replace ('%userid%', $user->ID, $target);
				$target = str_replace ('%userlogin%', isset($user->user_login) ? $user->user_login : '', $target);
				$target = str_replace ('%userurl%', isset($user->user_url) ? $user->user_url : '', $target);
			}
		}
		
		return $target;
	}

	function visit ($url, $target)
	{
		if ($this->tracking && $this->id)
		{
			global $wpdb, $redirection;

			// Update the counters
			$count = $this->last_count + 1;
			$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET last_count='$count', last_access=NOW() WHERE id='{$this->id}'");

			if (isset ($_SERVER['REMOTE_ADDR']))
			  $ip = $_SERVER['REMOTE_ADDR'];
			else if (isset ($_SERVER['HTTP_X_FORWARDED_FOR']))
			  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			
			$options = $redirection->get_options ();
			if ($options['log_redirections'])
				$log = RE_Log::create ($url, $target, $_SERVER['HTTP_USER_AGENT'], $ip, isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', $this->id, $this->module_id, $this->group_id);
		}
	}
	
	function reset ()
	{
		global $wpdb;
		
		$this->last_count  = 0;
		$this->last_access = '0000-00-00 00:00:00';
		
		$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET last_count=0, last_access='{$this->last_access}' WHERE id='{$this->id}'");
		
		RE_Log::delete_for_id ($this->id);
	}

	function show_url ($url)
	{
		return implode ('&#8203;/', explode ('/', $url));
	}
	
	function move_to ($group)
	{
		global $wpdb;

		$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET group_id='$group' WHERE id='{$this->id}'");
	}
	
	function toggle_status ()
	{
		global $wpdb;

		$this->status = ($this->status == 'enabled') ? 'disabled' : 'enabled';
		$wpdb->query ("UPDATE {$wpdb->prefix}redirection_items SET status='{$this->status}' WHERE id='{$this->id}'");
	}

	function actions ($action = '')
	{
		$actions = array
		(
			'url'     => __ ('Redirect to URL', 'redirection'),
			'random'  => __ ('Redirect to random post', 'redirection'),
			'pass'    => __ ('Pass-through', 'redirection'),
			'error'   => __ ('Error (404)', 'redirection'),
			'nothing' => __ ('Do nothing', 'redirection'),
		);
		
		if ($action)
			return $actions[$action];
		return $actions;
	}
	
	function match_name () { return $this->match->match_name ();	}
	
	function type ()
	{
		if (($this->action_type == 'url' || $this->action_type == 'error' || $this->action_type == 'random') && $this->action_code > 0)
			return $this->action_code;
		else if ($this->action_type == 'pass')
			return 'pass';
		return '&mdash;';
	}
}
?>