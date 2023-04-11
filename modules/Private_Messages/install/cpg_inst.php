<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Private_Messages extends \Dragonfly\ModManager\SetupBase
{
	public
		/**
		 * v9
		 */
		$author      = 'CPG-Nuke Dev Team',
		$dbtables    = array('privatemessages', 'privatemessages_recipients'),
		$description = 'Send and recieve private messages with members',
		$modname     = 'Private Messaging',
		$radmin      = true,
		$version     = '10.0.0',
		$website     = 'dragonflycms.org',
		/**
		 * v10
		 */
		$blocks     = true, // place all active blocks in this module
		$bypass     = false, // bypass 42* standard mysql error states
		$test       = false, // true: don't execute the db queries
		$config     = array(
			'per_page' => 50,
			'inbox_max' => 100,
			'sentbox_max' => 100,
			'outbox_max' => 10,
			'savebox_max' => 100,
			'graphic_length' => 300,
			'graphic_style' => 'px',
			'allow_bbcode' => 1,
			'allow_smilies' => 1,
			'flood_interval' => 15
		),
		$userconfig = array(); // not used yet

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
		if (version_compare($prev_version, '1.2', '<')) {
			$this->add_query('REN', 'bbprivmsgs', 'privmsgs');
			$this->add_query('REN', 'bbprivmsgs_text', 'privmsgs_text');
		}
		return true;
	}

	public function post_upgrade($prev_version)
	{
		if (version_compare($prev_version, '1.2', '<')) {
			$this->add_query('UPDATE', 'modules', 'uninstall=1 WHERE title="Private_Messages"');
		}
		if (version_compare($prev_version, '10.0.0', '<')) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$SQL->query("DELETE FROM {$SQL->TBL->privmsgs_text} WHERE privmsgs_text_id IN (SELECT privmsgs_id FROM {$SQL->TBL->privmsgs} WHERE privmsgs_to_userid < 2)");
			$SQL->query("DELETE FROM {$SQL->TBL->privmsgs} WHERE privmsgs_to_userid < 2");
			// privmsgs_type 2 and 4 are copies, in case the recipient deleted the original
			// Need to check for duplicates
			// This means
			$SQL->query("INSERT INTO {$SQL->TBL->privatemessages} (
				pm_id,
				user_id,
				pm_status,
				pm_subject,
				pm_date,
				pm_ip,
				pm_enable_bbcode,
				pm_enable_smilies,
				pm_text
			) SELECT DISTINCT
				pm.privmsgs_id,
				pm.privmsgs_from_userid,
				CASE
					WHEN pm2.privmsgs_type IS NULL THEN 4 /* DELETED */
					WHEN 2 = pm2.privmsgs_type THEN 1 /* SENT */
					WHEN 4 = pm2.privmsgs_type THEN 3 /* SAVED */
					WHEN 0 = pm.privmsgs_type THEN 2
					WHEN 1 = pm.privmsgs_type THEN 0
					WHEN 3 = pm.privmsgs_type THEN 3
					WHEN 5 = pm.privmsgs_type THEN 1
				END,
				pm.privmsgs_subject,
				pm.privmsgs_date,
				pm.privmsgs_ip,
				pm.privmsgs_enable_bbcode,
				pm.privmsgs_enable_smilies,
				privmsgs_text
			FROM {$SQL->TBL->privmsgs} pm
			INNER JOIN {$SQL->TBL->privmsgs_text} ON (privmsgs_text_id = pm.privmsgs_id)
			LEFT JOIN {$SQL->TBL->privmsgs} pm2 ON (pm2.privmsgs_from_userid = pm.privmsgs_from_userid AND pm2.privmsgs_date = pm.privmsgs_date AND pm2.privmsgs_type IN (2,4))
			WHERE pm.privmsgs_type NOT IN (2,4)");

			$SQL->query("INSERT INTO {$SQL->TBL->privatemessages} (
				pm_id,
				user_id,
				pm_status,
				pm_subject,
				pm_date,
				pm_ip,
				pm_enable_bbcode,
				pm_enable_smilies,
				pm_text
			) SELECT DISTINCT
				pm.privmsgs_id,
				pm.privmsgs_from_userid,
				CASE
					WHEN 2 = pm.privmsgs_type THEN 1 /* SENT */
					WHEN 4 = pm.privmsgs_type THEN 3 /* SAVED */
				END,
				pm.privmsgs_subject,
				pm.privmsgs_date,
				pm.privmsgs_ip,
				pm.privmsgs_enable_bbcode,
				pm.privmsgs_enable_smilies,
				privmsgs_text
			FROM {$SQL->TBL->privmsgs} pm
			INNER JOIN {$SQL->TBL->privmsgs_text} ON (privmsgs_text_id = pm.privmsgs_id)
			LEFT JOIN {$SQL->TBL->privmsgs} pm2 ON (pm2.privmsgs_from_userid = pm.privmsgs_from_userid AND pm2.privmsgs_date = pm.privmsgs_date AND pm2.privmsgs_type NOT IN (2,4))
			WHERE pm2.privmsgs_id IS NULL AND pm.privmsgs_type IN (2,4)");

			$SQL->query("INSERT INTO {$SQL->TBL->privatemessages_recipients} (
				pm_id,
				user_id,
				pmr_status
			) SELECT DISTINCT
				privmsgs_id,
				privmsgs_to_userid,
				CASE
					WHEN 0 = privmsgs_type THEN 2
					WHEN 1 = privmsgs_type THEN 0
					WHEN 2 = privmsgs_type THEN 1
					WHEN 3 = privmsgs_type THEN 3
					WHEN 4 = privmsgs_type THEN 3
					WHEN 5 = privmsgs_type THEN 1
				END
			FROM {$SQL->TBL->privmsgs}
			INNER JOIN {$SQL->TBL->privmsgs_text} ON (privmsgs_text_id = privmsgs_id)
			WHERE privmsgs_type NOT IN (2,4)");

			$SQL->query("INSERT INTO {$SQL->TBL->privatemessages_recipients} (
				pm_id,
				user_id,
				pmr_status
			) SELECT DISTINCT
				pm.privmsgs_id,
				pm.privmsgs_to_userid,
				4
			FROM {$SQL->TBL->privmsgs} pm
			INNER JOIN {$SQL->TBL->privmsgs_text} ON (privmsgs_text_id = pm.privmsgs_id)
			LEFT JOIN {$SQL->TBL->privmsgs} pm2 ON (pm2.privmsgs_from_userid = pm.privmsgs_from_userid AND pm2.privmsgs_date = pm.privmsgs_date AND pm2.privmsgs_type NOT IN (2,4))
			WHERE pm2.privmsgs_id IS NULL AND pm.privmsgs_type IN (2,4)");
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
