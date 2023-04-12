<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-Groups.php,v $
  $Revision: 9.8 $
  $Author: phoenix $
  $Date: 2007/09/12 02:58:32 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('Groups')) {
	$content = 'ERROR';
	return trigger_error('Groups module is inactive', E_USER_WARNING);
}

global $db, $prefix, $userinfo,$lang;
get_lang('forums');
//define('GROUPS_TABLE', $prefix.'_bbgroups');
//define('USER_GROUP_TABLE', $prefix.'_bbuser_group');
//define('GROUP_HIDDEN', 2);
//define('POST_GROUPS_URL', 'g');

$in_group = array();
// Select all groups where the user is a member
if (isset($userinfo['_mem_of_groups'])) {
	$s_member_groups = '';
	foreach ($userinfo['_mem_of_groups'] as $id => $name) {
		$in_group[] = $id;
		if (!empty($name)){
			$s_member_groups .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>&#8226;</b>&nbsp;<a title="'.$name.'" href="'.getlink('Groups&amp;g='.$id).'">'.$name.'</a><br />';
		}
	}
}
// Select all groups where the user has a pending membership.
if (is_user()) {
	$result = $db->sql_query('SELECT g.group_id, g.group_name, g.group_type
			FROM ' . $prefix.'_bbgroups g, ' . $prefix.'_bbuser_group ug
			WHERE ug.user_id = ' . $userinfo['user_id'] . '
				AND ug.group_id = g.group_id
				AND ug.user_pending = 1
				AND g.group_single_user = 0
			ORDER BY g.group_name, ug.user_id');
	if ($db->sql_numrows($result)) {
		$s_pending_groups = '';
		while ( $row = $db->sql_fetchrow($result) ) {
			$in_group[] = $row['group_id'];
			$s_pending_groups .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>&#8226;</b>&nbsp;<a title="'.$row['group_name'].'" href="'.getlink('Groups&amp;g='.$row['group_id']).'">'.$row['group_name'].'</a><br />';
		}
	}
}

// Select all other groups i.e. groups that this user is not a member of
$ignore_group_sql = ( count($in_group) ) ? "AND group_id NOT IN (" . implode(', ', $in_group) . ")" : '';
$result = $db->sql_query("SELECT group_id, group_name, group_type
		FROM " . $prefix."_bbgroups
		WHERE group_single_user = 0
			$ignore_group_sql
		ORDER BY group_name");

$s_group_list = '';
while ($row = $db->sql_fetchrow($result)) {
	if ($row['group_type'] != 2 || is_admin()) {
		$s_group_list .='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>&#8226;</b>&nbsp;<a title="'.$row['group_name'].'" href="'.getlink('Groups&amp;g='.$row['group_id']).'">'.$row['group_name'].'</a><br />';
	}
}

$content = '';
if (isset($s_member_groups)) {
	$content .= '<img src="images/blocks/group-1.gif" alt="'.$lang['Current_memberships'].'" style="height:14px; width:17px;" /> '.$lang['Current_memberships'].'<br />'.$s_member_groups;
}
if (isset($s_pending_groups)) {
	$content .= '<img src="images/blocks/group-3.gif" alt="'.$lang['Memberships_pending'].'" style="height:14px; width:17px;" /> '.$lang['Memberships_pending'].'<br />'.$s_pending_groups;
}
if ($s_group_list != '') {
	$content .= '<img src="images/blocks/group-4.gif" alt="'.$lang['Group_member_join'].'" style="height:14px; width:17px;" /> '.$lang['Group_member_join'].'<br />'.$s_group_list;
}
if (!is_user()) {
	$content .= '<br />'.$lang['Login_to_join'];
}
