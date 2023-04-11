<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class News extends \Dragonfly\ModManager\SetupBase
{
	public
		$author      = 'CPG-Nuke Dev Team',
		$dbtables    = array('queue', 'stories', 'stories_cat', 'topics', 'comments'),
		$description = 'Manage news articles that can be sorted between categories and topics',
		$modname     = 'News',
		$radmin      = true,
		$version     = '10.0.0',
		$website     = 'dragonflycms.org',
		$blocks      = true;

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
		if (version_compare($prev_version, '1.3', '<')) {
			foreach (\Dragonfly\L10N\V9::$browserlang as $new => $old) {
				$db->query("UPDATE {$db->TBL->queue} SET alanguage='{$new}' WHERE alanguage='{$old}'");
				$db->query("UPDATE {$db->TBL->stories} SET alanguage='{$new}' WHERE alanguage='{$old}'");
			}
		}
		return true;
	}

	public function post_upgrade($prev_version)
	{
		global $db;
		if (version_compare($prev_version, '2.0', '<')) {
			$db->query("UPDATE {$db->TBL->comments} p SET user_id = COALESCE((SELECT user_id FROM {$db->TBL->users} WHERE username = p.name),0)");
			$db->query("ALTER TABLE {$db->TBL->comments} DROP COLUMN subject, DROP COLUMN name, DROP COLUMN email, DROP COLUMN url");
		}
		if (version_compare($prev_version, '2.1', '<')) {
			if (isset($db->TBL->autonews)) {
				$db->query("INSERT INTO {$db->TBL->stories} (
					catid, aid, title, ptime, hometext, bodytext, topic, informant, notes, ihome, alanguage, acomm, associated
				) SELECT
					catid, aid, title, time, hometext, bodytext, topic, informant, notes, ihome, alanguage, acomm, associated
				FROM {$db->TBL->autonews}");
				$db->query("DROP TABLE {$db->TBL->autonews}");
			}
			$db->query("UPDATE {$db->TBL->stories} s SET identity_id = COALESCE((SELECT user_id FROM {$db->TBL->users} WHERE username = s.informant),0)");
		}
		if (version_compare($prev_version, '10.0.0', '<')) {
			$qr = $db->query("SELECT tid, host_name, score FROM {$db->TBL->comments}");
			while ($r = $qr->fetch_row()) {
				$r[1] = \Dragonfly\Net::decode_ip($r[1]);
				$db->query("UPDATE {$db->TBL->comments} SET remote_ip = {$db->quote($r[1])} WHERE tid = {$r[0]}");
				$db->query("INSERT INTO {$db->TBL->comments_scores} (comment_id, identity_id, comment_score) VALUES ({$r[0]}, 0, {$r[2]})");
			}
			$db->query("ALTER TABLE {$db->TBL->comments} DROP COLUMN host_name");
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
