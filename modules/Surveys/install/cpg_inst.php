<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\ModManager;

class Surveys extends SetupBase
{
	public
		$author      = 'CPG-Nuke Dev Team',
		$dbtables    = array('poll_check', 'poll_data', 'poll_desc', 'pollcomments'),
		$description = 'Manage Surveys to gain information from your visitors',
		$modname     = 'Surveys',
		$radmin      = true,
		$version     = '10.0.0',
		$website     = 'dragonflycms.org',
		// v10
		$blocks      = true,
		$test        = false;

	public function pre_install()
	{
		return true;
	}

	public function post_install()
	{
		return true;
	}

	public function pre_upgrade($prev_version)
	{
		global $db;
		if (version_compare($prev_version, '1.5', '<'))
		{
			foreach (\Dragonfly\L10N\V9::$browserlang as $new => $old) {
				$db->query("UPDATE {$db->TBL->poll_desc} SET planguage='{$new}' WHERE planguage='{$old}'");
			}
		}
		return true;
	}

	public function post_upgrade($prev_version)
	{
		global $db;
		if (version_compare($prev_version, '1.6', '<')) {
			$db->query("UPDATE {$db->TBL->poll_desc} SET poll_ptime=time_stamp");
		}
		if (version_compare($prev_version, '1.8', '<')) {
			$db->query("UPDATE {$db->TBL->pollcomments} p SET user_id = COALESCE((SELECT user_id FROM {$db->TBL->users} WHERE username = p.name),0)");
			$db->query("ALTER TABLE {$db->TBL->pollcomments} DROP COLUMN subject, DROP COLUMN name, DROP COLUMN email, DROP COLUMN url");
		}
		if (version_compare($prev_version, '10.0.0', '<')) {
			$qr = $db->query("SELECT tid, host_name, score FROM {$db->TBL->pollcomments}");
			while ($r = $qr->fetch_row()) {
				$r[1] = \Dragonfly\Net::decode_ip($r[1]);
				$db->query("UPDATE {$db->TBL->pollcomments} SET remote_ip = {$db->quote($r[1])} WHERE tid = {$r[0]}");
				$db->query("INSERT INTO {$db->TBL->pollcomments_scores} (comment_id, identity_id, comment_score) VALUES ({$r[0]}, 0, {$r[2]})");
			}
			$db->query("ALTER TABLE {$db->TBL->pollcomments} DROP COLUMN host_name");
		}
		return true;
	}

	public function pre_uninstall()
	{
		return true;
	}

	public function post_uninstall()
	{
		return true;
	}

}
