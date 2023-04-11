<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Statistics extends \Dragonfly\ModManager\SetupBase
{
	public
		$author      = 'CPG-Nuke Dev Team',
		$dbtables    = array('stats_counters', 'stats_hour'),
		$description = 'Keep track of who visits your site and at what time',
		$modname     = 'Statistics',
		$version     = '3.0',
		$website     = 'dragonflycms.org';

	public function pre_install() { return true; }
	public function post_install() { return true; }
	public function pre_uninstall() { return true; }
	public function post_uninstall() { return true; }
	public function pre_upgrade($prev_version) { return true; }
	public function post_upgrade($prev_version)
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		if ($prev_version < 3 && isset($SQL->TBL->counter)) {
			$qr = $SQL->query("INSERT INTO {$SQL->TBL->stats_counters} (sc_type, sc_value, sc_hits)
				SELECT
					CASE
						WHEN 'browser'=type THEN 1
						WHEN 'os'=type THEN 2
						WHEN 'bot'=type THEN 3
						ELSE 0
					END,
					LOWER(var),
					SUM(count)
				FROM {$SQL->TBL->counter}
				GROUP BY 1, 2");
		}
		return true;
	}

}
