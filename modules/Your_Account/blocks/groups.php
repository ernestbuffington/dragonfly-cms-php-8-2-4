<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Your_Account/blocks/groups.php,v $
  $Revision: 9.6 $
  $Author: nanocaiordo $
  $Date: 2006/05/20 00:56:50 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

// Group Memberships
$result = $db->sql_query("SELECT ug.group_id, g.group_name, g.group_type FROM ".$prefix."_bbuser_group ug INNER JOIN ".$prefix."_bbgroups g ON (g.group_id = ug.group_id AND g.group_single_user = 0) WHERE ug.user_pending = 0 AND ug.user_id = ".intval($userinfo['user_id']));
if ($db->sql_numrows($result)) {
	$g = array();
	while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
		if ($row[2] == 2 && (!in_group($row[0]) && !can_admin()))  { continue; }
		else  { $g[$row[0]] = $row[1]; }
	}
	if (count($g)) {
		echo '<br />';
		OpenTable();
		echo '<div align="left"><strong>'.$userinfo['username'].'\'s '._MEMBERGROUPS.':</strong><ul>';
		foreach ($g as $gid => $gname) {
			echo '<li><a href="'.getlink('Groups&amp;g='.$gid).'">'.$gname.'</a></li>';
		}
		echo '</ul></div>';
		CloseTable();
	}
}