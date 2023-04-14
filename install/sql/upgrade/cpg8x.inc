<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/install/sql/upgrade/cpg8x.inc,v $
  $Revision: 1.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:40 $
**********************************************/
if (!defined('INSTALL')) { exit; }
global $db, $prefix, $user_prefix, $install, $tablelist;

/*****************
*  CPG-Nuke 8.0	 *
*****************/

if ($version < 8.0) {
	$installer->add_query('DEL', 'users', "popmeson");
	$installer->add_query('DEL', 'users', "user_theme");
	$installer->add_query('DEL', 'users', "user_regdate_cp");

	$installer->add_query('UPDATE', 'stories', 'ihome=ihome+1, acomm=acomm+1 WHERE sid<>0', 'ihome=ihome-1, acomm=acomm-1 WHERE sid<>0');
	$installer->add_query('UPDATE', 'stories', 'ihome=0 WHERE ihome=2', 'ihome=2 WHERE ihome=0');
	$installer->add_query('UPDATE', 'stories', 'acomm=0 WHERE acomm=2', 'acomm=2 WHERE acomm=0');
}

/*****************
*  CPG-Nuke 8.1	 *
*****************/

if ($version < 8.1 && isset($tablelist['bannerclient']) && isset($tablelist['banner'])) {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_bannerclient GROUP BY cid', true);
	if ($db->sql_numrows($result) > 0) {
		while ($row = $db->sql_fetchrow($result)) {
			$emailres = $db->sql_query("SELECT user_id FROM ".$user_prefix."_users WHERE user_email = '$row[email]'", true);
			if ($db->sql_numrows($emailres) > 0) {
				list($userid) = $db->sql_fetchrow($emailres);
				$installer->add_query('UPDATE', 'banner', "cid=$userid WHERE cid=$row[cid]");
			} else {
				$nameres = $db->sql_query("SELECT user_id FROM ".$user_prefix."_users WHERE username = '$row[login]'", true);
				if ($db->sql_numrows($nameres) < 1) {
					if ($db->sql_query("INSERT INTO ".$user_prefix."_users (username, name, user_email, user_password) VALUES ('$row[login]', '$row[name]', '$row[email]', '$row[passwd]')", true)) {
						$userid = $db->sql_nextid('user_id');
						$installer->add_query('UPDATE', 'banner', "cid=$userid WHERE cid=$row[cid]");
					}
				}
			}
			$db->sql_freeresult($emailres);
		}
	}
	$db->sql_freeresult($result);
}
if (isset($tablelist['bannerclient'])) $installer->add_query('DROP', 'bannerclient');
unset($tablelist['bannerclient']);

/*****************
*  CPG-Nuke 8.2	 *
*****************/

if ($version < 8.2) {
	if (isset($tablelist['modules_cat'])) $installer->add_query('DROP', 'modules_cat');
	if (isset($tablelist['modules_links'])) $installer->add_query('DROP', 'modules_links');
	if (isset($tablelist['config_custom'])) $installer->add_query('DROP', 'config_custom');
	if (isset($tablelist['userinfo_admin'])) $installer->add_query('DROP', 'userinfo_admin');
	unset($tablelist['modules_cat']);
	unset($tablelist['modules_links']);
	unset($tablelist['config_custom']);
	unset($tablelist['userinfo_admin']);
}
