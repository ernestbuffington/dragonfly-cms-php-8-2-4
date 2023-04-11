<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2009 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
if (is_user()) {
	// Group Memberships
	$result = $db->query("SELECT ug.group_id, g.group_name, g.group_type FROM {$db->TBL->bbuser_group} ug
	INNER JOIN {$db->TBL->bbgroups} g ON (g.group_id = ug.group_id AND g.group_single_user = 0)
	WHERE ug.user_pending = 0 AND ug.user_id = {$userinfo['user_id']}");
	if ($result->num_rows) {
		$g = array();
		while ($row = $result->fetch_row()) {
			if ($row[2] == 2 && (!in_group($row[0]) && !can_admin()))  {
				continue;
			} else {
				$g[$row[0]] = $row[1];
			}
		}
		if (count($g)) {
			$OUT = \Dragonfly::getKernel()->OUT;
			$OUT->assign_vars(array(
				'GROUPS_TITLE' => $userinfo['username'].'\'s '._MEMBERGROUPS,
			));
			foreach ($g as $gid => $gname) {
				$OUT->assign_block_vars('group', array(
					'URL'  => URL::index('Groups&g='.$gid),
					'NAME' => $gname
				));
			}
			$OUT->display('your_account/blocks/groups');
		}
	}
}
