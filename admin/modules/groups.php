<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/admin/modules/groups.php,v $
  $Revision: 9.12 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:57 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('groups')) { die('Access Denied'); }
get_lang('forums');
$pagetitle .= ' '._BC_DELIM.' '.$lang['Group_Control_Panel'];

// Table names
define('AUTH_ACCESS_TABLE', $prefix.'_bbauth_access');

if (isset($_POST['gid']) || isset($_GET['gid'])) {
	$group_id = ( isset($_POST['gid']) ) ? intval($_POST['gid']) : intval($_GET['gid']);
}
else { $group_id = 0; }

if (isset($_POST['mode']) || isset($_GET['mode'])) {
	$mode = $_POST['mode'] ?? $_GET['mode'];
	$mode = htmlprepare($mode);
} else {
	$mode = '';
}

function group_msg($message) {
	cpg_error($message, 'Groups', adminlink('groups'));
}

function group_head() {
	global $lang;
	require_once('header.php');
	GraphicAdmin('_AMENU2');
	OpenTable();
	echo '<div align="center"><h1>Groups Administration</h1>';
}

// Select main mode
if (isset($_GET['edit']) || isset($_POST['new'])) {
	group_head();
	// Ok someone is editing or creating a group
	echo '<form action="'.adminlink('groups').'" method="post" name="post" enctype="multipart/form-data" accept-charset="utf-8">
  <table border="0" cellpadding="3" cellspacing="1" class="forumline" align="center">
	<tr>
	  <th class="thHead" colspan="2">';

	if (isset($_GET['edit'])) {
		$group_info = $db->sql_ufetchrow('SELECT g.*, u.username FROM '.$prefix.'_bbgroups g LEFT JOIN '.$user_prefix.'_users u ON (u.user_id = g.group_moderator) WHERE group_single_user = 0 AND group_id='.$group_id);
		if (empty($group_info)) {
			cpg_error('Group doesn\'t exist');
		}
		$mode = 'editgroup';
		echo 'Edit group';
	} else {
		$group_info = array ('group_name' => '', 'group_description' => '', 'group_moderator' => '', 'group_type' => 0, 'username' => '');
		$mode = 'newgroup';
		echo 'Create new group';
	}

	$s_hidden_fields = '<input type="hidden" name="mode" value="' . $mode . '" /><input type="hidden" name="gid" value="' . $group_id . '" />';
	echo '</th>
	</tr>
	<tr>
	  <td class="row1" width="38%"><span class="gen">Group name:</span></td>
	  <td class="row2" width="62%">
		<input type="text" name="group_name" size="35" maxlength="40" value="'.htmlprepare($group_info['group_name']).'" />
	  </td>
	</tr><tr>
	  <td class="row1" width="38%"><span class="gen">Group description:</span></td>
	  <td class="row2" width="62%">
		<textarea name="group_description" rows="10" cols="63">'.$group_info['group_description'].'</textarea>
	  </td>
	</tr><tr>
	  <td class="row1" width="38%"><span class="gen">Group moderator:</span></td>
	  <td class="row2" width="62%"><input type="text" class="post" name="username" maxlength="50" size="20" value="'.$group_info['username'].'" /> &nbsp; <input type="submit" name="usersubmit" value="Find a username" class="liteoption" onclick="window.open(\''.getlink('Forums&amp;file=search&amp;mode=searchuser&amp;popup=1&amp;menu=1').'\', \'_phpbbsearch\', \'HEIGHT=250,resizable=yes,WIDTH=400\');return false;" /></td>
	</tr><tr>
	  <td class="row1" width="38%"><span class="gen">Group status:</span></td>
	  <td class="row2" width="62%">
		<input type="radio" name="group_type" value="0" '.(($group_info['group_type'] == 0) ? ' checked="checked"' : '').' /> Open group &nbsp;
		<input type="radio" name="group_type" value="1" '.(($group_info['group_type'] == 1) ? ' checked="checked"' : '').' /> Closed group &nbsp;
		<input type="radio" name="group_type" value="2" '.(($group_info['group_type'] == 2) ? ' checked="checked"' : '').' /> Hidden group</td>
	</tr>';
	if (isset($_GET['edit'])) {
		echo '<tr>
	  <td class="row1" width="38%"><span class="gen">Delete the old group moderator?</span>
	  <br />
	  <span class="gensmall">If you\'re changing the group moderator, check this box to remove the old moderator from the group. Otherwise, do not check it, and the user will become a regular member of the group.</span></td>
	  <td class="row2" width="62%"><input type="checkbox" name="delete_old_moderator" value="1" />'._YES.'</td>
	</tr><tr>
	  <td class="row1" width="38%"><span class="gen">Delete this group:</span></td>
	  <td class="row2" width="62%"><input type="checkbox" name="delete" value="1" />'._YES.'</td>
	</tr>';
	}
	echo '<tr>
	  <td class="catBottom" colspan="2" align="center"><span class="cattitle">
		<input type="submit" name="update" value="'._SAVECHANGES.'" class="mainoption" />
		&nbsp;&nbsp;
		<input type="reset" name="reset" value="Reset" class="liteoption" />
		</span></td>
	</tr>
</table>'.$s_hidden_fields.'</form>';
}
else if (isset($_POST['update'])) {
	// Ok, they are submitting a group, let's save the data based on if it's new or editing
	if (isset($_POST['delete'])) {
		// Is the group moderating a forum ?
		$row = $db->sql_ufetchrow("SELECT auth_mod FROM " . AUTH_ACCESS_TABLE . " WHERE group_id = " . $group_id.' LIMIT 0,1', SQL_ASSOC);
		if (intval($row['auth_mod']) == 1) {
			// Yes, get the assigned users and update their Permission if they are no longer moderator of one of the forums
			$rows = $db->sql_ufetchrowset("SELECT user_id FROM ".$prefix.'_bbuser_group WHERE group_id='.$group_id);
			for ($i = 0; $i < (is_countable($rows) ? count($rows) : 0); $i++) {
				$result = $db->sql_query("SELECT g.group_id FROM (" . AUTH_ACCESS_TABLE . " a, ".$prefix.'_bbgroups g, '.$prefix.'_bbuser_group ug)
						WHERE (a.auth_mod = 1) AND (g.group_id = a.group_id) AND (a.group_id = ug.group_id) AND (g.group_id = ug.group_id)
						AND (ug.user_id = '.intval($rows[$i]['user_id']).') AND (ug.group_id <> '.$group_id.')');
				if ($db->sql_numrows($result) == 0) {
					$db->sql_query("UPDATE ".$user_prefix.'_users SET user_level=1 WHERE user_level=3 AND user_id=' . intval($rows[$i]['user_id']));
				}
			}
		}
		// Delete Group
		$db->sql_query("DELETE FROM ".$prefix.'_bbgroups WHERE group_id = '.$group_id);
		$db->sql_query('DELETE FROM '.$prefix.'_bbuser_group WHERE group_id = '.$group_id);
		$db->sql_query('DELETE FROM '.AUTH_ACCESS_TABLE.' WHERE group_id = '.$group_id);
		group_msg('The group has been deleted');
	}
	else {
		$group_type = isset($_POST['group_type']) ? intval($_POST['group_type']) : 0;
		$group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
		$group_description = isset($_POST['group_description']) ? trim($_POST['group_description']) : '';
		$group_moderator = isset($_POST['username']) ? Fix_Quotes($_POST['username'], true) : '';
		$delete_old_moderator = isset($_POST['delete_old_moderator']) ? true : false;

		if ( $group_name == '' ) {
			cpg_error('No_group_name');
		} else if ( $group_moderator == '' ) {
			cpg_error('No_group_moderator');
		}

		$this_userdata = getusrdata($group_moderator, "user_id");
		$group_moderator = $this_userdata['user_id'];

		if (!$group_moderator) {
			cpg_error('The member '.$group_moderator.' doesn\'t exist');
		}

		if( $mode == "editgroup" ) {
			$group_info = $db->sql_ufetchrow('SELECT * FROM '.$prefix.'_bbgroups WHERE group_single_user = 0 AND group_id = '.$group_id);
			if(empty($group_info)) {
				cpg_error('The group doesn\'t exist');
			}
			if ( $group_info['group_moderator'] != $group_moderator ) {
				if ( $delete_old_moderator ) {
					$db->sql_query('DELETE FROM '.$prefix.'_bbuser_group WHERE user_id = '.$group_info['group_moderator'].' AND group_id = '.$group_id);
				}
				$result = $db->sql_query("SELECT user_id FROM " . $prefix."_bbuser_group WHERE user_id = $group_moderator AND group_id = $group_id");
				if ( !($row = $db->sql_fetchrow($result)) ) {
					$db->sql_query("INSERT INTO ".$prefix."_bbuser_group (group_id, user_id, user_pending) VALUES (".$group_id.", ".$group_moderator.", 0)");
				}
			}
			$db->sql_query("UPDATE " . $prefix.'_bbgroups' . "
					SET group_type = $group_type, group_name = '" . Fix_Quotes($group_name) . "', group_description = '" . Fix_Quotes($group_description) . "', group_moderator = $group_moderator
					WHERE group_id = $group_id");
			group_msg('The group has been updated');
		}
		else if( $mode == 'newgroup' )
		{
			$db->sql_query("INSERT INTO " . $prefix.'_bbgroups' . " (group_type, group_name, group_description, group_moderator, group_single_user)
					VALUES ($group_type, '" . Fix_Quotes($group_name) . "', '" . Fix_Quotes($group_description) . "', $group_moderator,		   '0')");
			$new_group_id = $db->sql_nextid('group_id');
			$db->sql_query("INSERT INTO ".$prefix."_bbuser_group (group_id, user_id, user_pending) VALUES ($new_group_id, $group_moderator, 0)");
			group_msg('The group has been added');
		}
		else {
			cpg_error('No_group_action');
		}
	}
}
else {
	group_head();
	// This is the main display of the page before the admin has selected any options.
	$result = $db->sql_query('SELECT group_id, group_name FROM '.$prefix.'_bbgroups WHERE group_single_user = 0 ORDER BY group_name');
	$select_list = '';
	$fa = can_admin('forums') ? 4 : 3;
	if ($row = $db->sql_fetchrow($result)) {
		$x = 1;
		do {
			$rownum = (is_integer($x/2)) ?	2 : 1 ;
			$select_list .= '<tr><td class="row'.$rownum.'" align="left" width="25%">'.$row['group_name'].'</td><td class="row'.$rownum.'" align="left" width="25%"> <a title="'.$lang['Edit_group'].'" href="' . adminlink('&amp;gid='.$row['group_id'].'&amp;edit=group') . '"> ' .  $lang['Edit_group'] . '</a></td>';
			if (can_admin('forums') ){
				$select_list .= '<td class="row'.$rownum.'" align="left" width="25%"> <a title="'.$row['group_name'].'" href="' . adminlink('Forums&amp;do=ug_auth&amp;mode=group&amp;g='.$row['group_id']) . '">'.$lang['Auth_Control_Forum']. '</a></td>';
			}
			$select_list .= '<td class="row'.$rownum.'" align="left" width="25%"> <a title="'._MODULES.' '.$lang['Permissions'].'" href="' . adminlink('modules') . '">'._MODULES.' '.$lang['Permissions'].'</a></td></tr>';
			$x++;
		}
		while ($row = $db->sql_fetchrow($result));		  
	}
	echo '<p>From this panel you can administer all of your usergroups. You can delete, create and edit existing groups.<br />
You may choose moderators, toggle open/closed group status and set the group name and description</p>
<table width="100%" cellspacing="1" cellpadding="4" border="0" align="center" class="forumline">
  <tr>
	<th class="thHead" colspan="'.$fa.'" align="center">Select a group</th>
  </tr>'.$select_list.'<tr>
	<td class="catBottom" align="center"  colspan="'.$fa.'"><form method="post" action="'.adminlink('groups').'" enctype="multipart/form-data" accept-charset="utf-8">
<input type="submit" class="liteoption" name="new" value="Create new group" /></form></td>
  </tr>
</table>';
}

echo '</div>';
CloseTable();
