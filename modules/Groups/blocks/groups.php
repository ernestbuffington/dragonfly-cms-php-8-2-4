<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $userinfo;
$L10N = \Dragonfly::getKernel()->L10N;
$L10N->load('Groups');

$in_group = array();
// Select all groups where the user is a member
if (isset($userinfo['_mem_of_groups'])) {
	$s_member_groups = '';
	foreach ($userinfo['_mem_of_groups'] as $id => $name) {
		$in_group[] = $id;
		if (!empty($name)){
			$s_member_groups .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.$name.'" href="'.htmlspecialchars(URL::index('Groups&g='.$id)).'">'.$name.'</a><br />';
		}
	}
}
// Select all groups where the user has a pending membership.
if (is_user()) {
	$result = $db->query('SELECT g.group_id, g.group_name, g.group_type
			FROM ' . $db->TBL->bbgroups.' g, ' . $db->TBL->bbuser_group.' ug
			WHERE ug.user_id = ' . $userinfo['user_id'] . '
				AND ug.group_id = g.group_id
				AND ug.user_pending = 1
				AND g.group_single_user = 0
			ORDER BY g.group_name, ug.user_id');
	if ($result->num_rows) {
		$s_pending_groups = '';
		while ($row = $result->fetch_assoc()) {
			$in_group[] = $row['group_id'];
			$s_pending_groups .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.$row['group_name'].'" href="'.htmlspecialchars(URL::index('Groups&g='.$row['group_id'])).'">'.$row['group_name'].'</a><br />';
		}
	}
}

// Select all other groups i.e. groups that this user is not a member of
$ignore_group_sql = ( count($in_group) ) ? "AND group_id NOT IN (" . implode(', ', $in_group) . ")" : '';
$result = $db->query("SELECT group_id, group_name, group_type
		FROM {$db->TBL->bbgroups}
		WHERE group_single_user = 0 {$ignore_group_sql}
		ORDER BY group_name");

$s_group_list = '';
while ($row = $result->fetch_assoc()) {
	if ($row['group_type'] != 2 || is_admin()) {
		$s_group_list .='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.$row['group_name'].'" href="'.htmlspecialchars(URL::index('Groups&g='.$row['group_id'])).'">'.$row['group_name'].'</a><br />';
	}
}

$content = '';
if (isset($s_member_groups)) {
	$content .= '<b> '.$L10N['Current memberships'].'</b><br />'.$s_member_groups;
}
if (isset($s_pending_groups)) {
	$content .= '<b> '.$L10N['Memberships pending'].'</b><br />'.$s_pending_groups;
}
if ($s_group_list != '') {
	$content .= '<b> '.$L10N['Join a Group'].'</b><br />'.$s_group_list;
}
if (!is_user()) {
	$content .= '<br />'.$L10N['Login_to_join'];
}
