<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/* this file adapted from:
 Group Restriction plugin
 http://code.google.com/p/wp-group-restriction/
 Tiago Pocinho, Siemens Networks, S.A.
 */

/****************************************************
 * This File loads the Groups -> Members Tab
 * It allows to manage the groups members (users) 
 ****************************************************/ 

require_once('groups-support.php');

$mode = $_REQUEST['mode'];

if($mode == "update"){
	$group_temp = ScoperAdminLib::get_group($_REQUEST['id']);
	UserGroups_tp::write( sprintf( __('<strong>%s</strong> group membership updated.', 'scoper'), $group_temp->display_name) );
}


if($_REQUEST['id'] == "" && ($mode == "edit" || $mode == "update"))
	UserGroups_tp::write( __('Invalid group.', 'scoper') );

$cancel = $_REQUEST['cancel'];
switch($cancel){
	case 1:
		UserGroups_tp::write( __('Group members edit canceled.', 'scoper') );
		break;
	default: 
		break;
}

?>

<div class="wrap agp-width97">
<?php

function printGroupMembers() {
	$results = ScoperAdminLib::get_all_groups(FILTERED_RS);
	
	$alt = false;
	
	if( isset($results) && count($results)>0 ) {
		echo "\n<table class='rs-member_table' width=\"100%\" border=\"0\" cellspacing=\"3\" cellpadding=\"3\">";
		echo "\n\t<tr class=\"thead\">";
		echo "\n\t\t<th>Group Name</th>\n\t\t<th>Members</th>\n\t\t<th>&nbsp;</th>";
		echo "\n\t</tr>";
		
		foreach ($results as $result) {
			$alt = !$alt;
			$style = ( $alt ) ? 'class=\'alternate\'' : 'margin: 1em 0 1em 0;';
	
			echo "<tr " . $style . "><td>" . $result->display_name . "</td><td>";
			
			if( $members = ScoperAdminLib::get_group_members($result->ID) ) {
				printf(_n( '%d user', '%d users', count($members), 'scoper' ), count($members) );
				echo '<br />';
			
				foreach ($members as $member)
					echo "- ".$member->display_name. "<br />";
			} else {
				if ( $result->meta_id )
					_e('(automatic)', 'scoper');
				else
					_e('(no users)', 'scoper');
			}
			
			echo "</td><td ".$style.">";
			
			if ( ! $result->meta_id )
				echo "<a class='edit' href='admin.php?page=rs-group_members&amp;mode=edit&amp;id=$result->ID'>" . __awp('Edit') . "</a>";
	        
	        echo "</td></tr>";
		}
		echo "\n</table>";
	} else
		echo "<p><strong>" . __('No groups available.', 'scoper') . "</strong></p>";
}

switch($mode){
	case "edit":
		if(isset($_REQUEST['id'])){
			$groupID = $_REQUEST['id'];
			
			$group = ScoperAdminLib::get_group($groupID);
			
			if ( $group->meta_id && ! strpos($group->meta_id, '_ed_') )
				die( __('This meta group is automatically populated. You cannot manually add members to it.', 'scoper') );
			
			echo "<h2>";
			printf( __('Edit members of %s group', 'scoper'), $group->display_name);
			echo "</h2>";

			echo '<form id="readWrite" name="readWrite" action="' . 'admin.php?page=rs-group_members&amp;mode=update&amp;id='.$groupID.'" method="post">';
			wp_nonce_field( 'scoper-edit-group-members_' . $groupID );
			echo '<script type="text/javascript"><!--
			      function select_all(name, value) {
			        formblock = document.getElementById("readWrite");
			        forminputs = formblock.getElementsByTagName("input");
			        for (i = 0; i < forminputs.length; i++) {
			          // regex here to check name attribute
			          var regex = new RegExp(name, "i");
			          if (regex.test(forminputs[i].getAttribute("name"))) {
			            forminputs[i].checked = value;
			          }
			        }
			      }
			      //--></script>';
			UserGroups_tp::group_members_checklist( $groupID );

			?> <br />
	<div class="submit">
		<input type="submit" value="Update" />
		<input type="button"
			onclick="javascript:location.href = 'admin.php?page=rs-group_members&amp;cancel=1'"
			value="Cancel" class="button" />
	</div>
</form>
      
<?php
		}
		
		break;
	case "update":
		//update groups members
		if ( $_REQUEST['id'] ) {
			$group_id = $_REQUEST['id'];
			
			check_admin_referer( 'scoper-edit-group-members_' . $group_id );
			
			// add/delete members
			$current_members = ScoperAdminLib::get_group_members($group_id, COL_ID_RS);
			
			if ( $delete_members = array_diff($current_members, $_POST['member']) )
				ScoperAdminLib::remove_group_user($group_id, $delete_members);
			
			if ( $new_members = array_diff($_POST['member'], $current_members) )
				ScoperAdminLib::add_group_user($group_id, $new_members);
		}
	default:
		echo( '<h2>' . __('Group Members', 'scoper') . '</h2>');

		$groups_url = 'admin.php?page=rs-groups';
		echo "<a href='$groups_url'>Back to Groups</a>";
		
	printGroupMembers();
}
?></div>
