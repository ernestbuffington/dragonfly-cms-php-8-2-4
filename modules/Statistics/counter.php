<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Modules\Statistics;

abstract class Counter
{
	public static function inc()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		if (defined('SEARCHBOT') && isset($SQL->TBL->stats_counters)) {
			$ua = \Poodle\UserAgent::getInfo();
//			if ((SEARCHBOT && $ua->verified) || (!SEARCHBOT && $ua->name)) {
			if (SEARCHBOT || $ua->name) {
				$var = $SQL->escape_string(mb_substr(mb_strtolower($ua->name), 0, 80));
			} else {
				$var = 'other';
			}
			$type = SEARCHBOT ? 3 : 1;
			if (!$SQL->exec("UPDATE {$SQL->TBL->stats_counters} SET sc_hits=sc_hits+1 WHERE sc_type={$type} AND sc_value='{$var}'")) {
				$SQL->query("INSERT INTO {$SQL->TBL->stats_counters} (sc_type, sc_value, sc_hits) VALUES ({$type}, '{$var}', 1)");
			}

			if (!SEARCHBOT) {
				$os = $SQL->quote($ua->OS->name ? mb_strtolower($ua->OS->name) : 'other');
				if (!$SQL->exec("UPDATE {$SQL->TBL->stats_counters} SET sc_hits=sc_hits+1 WHERE sc_type=2 AND sc_value={$os}")) {
					$SQL->query("INSERT INTO {$SQL->TBL->stats_counters} (sc_type, sc_value, sc_hits) VALUES (2, {$os}, 1)");
				}
			}
		}

		$now = explode('-', date('d-m-Y-H'));
		if (!$SQL->exec("UPDATE {$SQL->TBL->stats_hour} SET hits=hits+1 WHERE (year={$now[2]}) AND (month={$now[1]}) AND (date={$now[0]}) AND (hour={$now[3]})")) {
			$SQL->query("INSERT INTO {$SQL->TBL->stats_hour} (year, month, date, hour, hits) VALUES ({$now[2]}, {$now[1]}, {$now[0]}, {$now[3]}, 1)");
		}
	}
}
