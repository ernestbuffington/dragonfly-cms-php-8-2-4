<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * TernaryToNullCoalescingRector
 */

class Dragonfly_Module_Statistics
{

	public static
		$misc,
		$metered = array();

	private static
		$db,
		$CFG,
		$TPL;

	public static function update()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		if (isset($SQL->TBL->stats_counters)) {
			$ua = \Poodle\UserAgent::getInfo();
//			if ((SEARCHBOT && $ua->verified) || (!SEARCHBOT && $ua->name)) {
			if (SEARCHBOT || $ua->name) {
				$var = $SQL->escape_string(mb_strtolower($ua->name));
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

	public static function run()
	{
		self::$db = Dragonfly::getKernel()->SQL;
		self::$TPL = Dragonfly::getKernel()->OUT;

		self::$TPL->sitename = Dragonfly::getKernel()->CFG->global->sitename;
		self::$TPL->startdate = Dragonfly::getKernel()->CFG->global->startdate;
		self::$TPL->metered = array();
		self::$TPL->misc = array();
		list(self::$TPL->total) = self::$db->uFetchRow('SELECT SUM(sc_hits) FROM '.self::$db->TBL->stats_counters.' WHERE sc_type IN (1,3)');
		self::$TPL->total++; # this page view

		\Dragonfly\Page::title(_StatisticsLANG, false);

		$year = $_GET->int('year');
		$month = $_GET->int('month');
		$date = $_GET->int('date');

		if ($year) {
			self::$TPL->main_stats = false;
			self::$TPL->details = false;
			self::$TPL->stats   = true;
			\Dragonfly\Output\Css::add('Statistics/statistics');
			require_once('header.php');
			self::$TPL->display('Statistics/stats');
			if ($month) {
				if ($date) {
					self::hourlyStat($year, $month, $date);
				} else {
					self::yearlyStat($year);
					self::monthlyStat($year, $month);
					self::dailyStat($year, $month, $date);
				}
			} else {
				self::yearlyStat($year);
				self::monthlyStat($year, $month);
			}
			self::$TPL->display('Statistics/metered');
		} else if (isset($_GET['details'])) {
			self::details();
		} else {
			self::statsMain();
		}
	}

	protected static function details()
	{
		self::$TPL->main_stats = false;
		self::$TPL->details = true;
		self::$TPL->stats   = false;

		$L10N = Dragonfly::getKernel()->L10N;
		self::$TPL->today = $L10N->date('F d, Y', time());

		\Dragonfly\Output\Css::add('Statistics/statistics');
		require_once('header.php');

		self::$TPL->topMonth = self::$db->uFetchRow("
			SELECT year, month, SUM(hits) FROM ".self::$db->TBL->stats_hour."
			GROUP BY month, year
			ORDER BY hits DESC");
		self::$TPL->topMonth = self::getmonth(self::$TPL->topMonth[1]).' '.self::$TPL->topMonth[0].' ('.self::$TPL->topMonth[2].' '._HITS.')';

		self::$TPL->topDay = self::$db->uFetchRow("SELECT year, month, date, SUM(hits) FROM ".self::$db->TBL->stats_hour."
			GROUP BY date, month, year
			ORDER BY hits DESC");
		self::$TPL->topDay = self::$TPL->topDay[2].' '.self::getmonth(self::$TPL->topDay[1]).' '.self::$TPL->topDay[0].' ('.self::$TPL->topDay[3].' '._HITS.')';

		self::$TPL->topHour = self::$db->uFetchRow("SELECT year, month, date, hour, hits from ".self::$db->TBL->stats_hour." ORDER BY hits DESC");
		self::$TPL->topHour[3] = str_pad(self::$TPL->topHour[3], 2, '0', STR_PAD_LEFT);
		self::$TPL->topHour[3] = self::$TPL->topHour[3].':00 - '.self::$TPL->topHour[3].':59';
		self::$TPL->topHour = self::$TPL->topHour[3].' '._ON.' '.self::getmonth(self::$TPL->topHour[1]).' '.self::$TPL->topHour[2].', '.self::$TPL->topHour[0].' ('.self::$TPL->topHour[4].' '._HITS.')';

		$now = explode('-',  $L10N->date('d-m-Y', time()));
		self::yearlyStat ($now[2]);
		self::monthlyStat($now[2], $now[1]);
		self::dailyStat  ($now[2], $now[1], $now[0]);
		self::hourlyStat ($now[2], $now[1], $now[0]);

		self::$TPL->display('Statistics/stats');
		self::$TPL->display('Statistics/metered');
	}

	protected static function statsMain()
	{
		self::$TPL->main_stats = true;
		self::$TPL->details = false;
		self::$TPL->stats   = false;

		\Dragonfly\Output\Css::add('Statistics/statistics');
		require_once('header.php');

		# Built-in metered stats
		self::$TPL->metered = array(
			'browser' => array(
				'title' => _BROWSERS,
				'most_hits' => 0,
				'total_hits' => 0,
				'rows' => array()
			),
			'os' => array(
				'title' => _OPERATINGSYS,
				'most_hits' => 0,
				'total_hits' => 0,
				'rows' => array()
			),
			'bot' => array(
				'title' => _SEARCH_ENGINES,
				'most_hits' => 0,
				'total_hits' => 0,
				'rows' => array()
			)
		);

		$result = self::$db->query('SELECT sc_type, sc_value, SUM(sc_hits) FROM '.self::$db->TBL->stats_counters.' WHERE sc_hits > 0 GROUP BY 1, 2 ORDER BY 3 DESC, 2');
		$types = array('','browser','os','bot');
		while (list($type, $var, $count) = $result->fetch_row()) {
			$type = $types[$type];
			if (isset(self::$TPL->metered[$type])) {
				self::$TPL->metered[$type]['most_hits'] = max(self::$TPL->metered[$type]['most_hits'], $count);
				self::$TPL->metered[$type]['total_hits'] += $count;
				self::$TPL->metered[$type]['rows'][] = array(
					'name'  => $var,
					'url'   => '',
					'hits'  => $count,
					'class' => strtolower(str_replace(array('/',' '),'',$var))
				);
			}
		}
		$result->free();

		# Built-in miscellaneous Stats
		$count = self::$db->TBL->users->count('user_level > 0') - 1;
		self::$TPL->misc[] = array('name' => _REGUSERS, 'url' => '', 'hits' => $count, 'class' => 'users');

		# Plugins
		self::$metered = self::$misc = array();
		$plugins = Dragonfly\Modules::ls('plugins/statistics.inc');
		foreach ($plugins as $file) {
			include_once($file);
		}
		self::$TPL->metered = array_merge(self::$TPL->metered, self::$metered);
		self::$TPL->misc = array_merge(self::$TPL->misc, self::$misc);
		self::$misc = self::$metered = array();

		self::$TPL->display('Statistics/stats');
		self::$TPL->display('Statistics/metered');
		self::$TPL->display('Statistics/misc');
	}

	protected static function yearlyStat($nowyear)
	{
		self::$TPL->metered['yearly'] = array(
			'title' => _YEARLYSTATS,
			'most_hits' => 0,
			'total_hits' => 0,
			'rows' => array()
		);
		$total = 0;
		$max = 0;
		$result = self::$db->query('SELECT year, SUM(hits) FROM '.self::$db->TBL->stats_hour.' GROUP BY year ORDER BY year');
		while (list($year,$hits) = $result->fetch_row()) {
			$total += $hits;
			$max = max($max, $hits);
			self::$TPL->metered['yearly']['rows'][] = array(
				'name' => $year,
				'url' => $year != $nowyear && $hits ? "&year={$year}" : '',
				'hits' => $hits,
				'class' => ''
			);
		}
		self::$TPL->metered['yearly']['most_hits'] = $max;
		self::$TPL->metered['yearly']['total_hits'] = $total;
	}

	protected static function monthlyStat($nowyear, $nowmonth)
	{
		self::$TPL->metered['monthly'] = array(
			'title' => _MONTLYSTATS.' '.$nowyear,
			'most_hits' => 0,
			'total_hits' => 0,
			'rows' => array()
		);
		$total = 0;
		$max = 0;
		$result = self::$db->query('SELECT month, SUM(hits) as hits FROM '.self::$db->TBL->stats_hour." WHERE year={$nowyear} GROUP BY month ORDER BY month");
		while ($row = $result->fetch_assoc()) {
			$total += $row['hits'];
			$max = max($max, $row['hits']);
			self::$TPL->metered['monthly']['rows'][] = array(
				'name' => self::getmonth($row['month']),
				'url' => $row['month'] != $nowmonth && $row['hits'] ? "&year={$nowyear}&month={$row['month']}" : '',
				'hits' => $row['hits'],
				'class' => ''
			);
		}
		self::$TPL->metered['monthly']['most_hits'] = $max;
		self::$TPL->metered['monthly']['total_hits'] = $total;
	}

	protected static function dailyStat($year, $month, $nowdate=0)
	{
		self::$TPL->metered['daily'] = array(
			'title' => _DAILYSTATS.' '.self::getmonth(intval($month)),
			'most_hits' => 0,
			'total_hits' => 0,
			'rows' => array()
		);
		$total = 0;
		$max = 0;
		$result = self::$db->query('SELECT date, SUM(hits) as hits FROM '.self::$db->TBL->stats_hour." WHERE year={$year} AND month={$month} GROUP BY date ORDER BY date");
		while ($row = $result->fetch_assoc()) {
			$total += $row['hits'];
			$max = max($max, $row['hits']);
			self::$TPL->metered['daily']['rows'][] = array(
				'name' => $row['date'],
				'url' => $row['date'] != $nowdate && $row['hits']  ? "&year={$year}&month={$month}&date={$row['date']}" : '',
				'hits' => $row['hits'],
				'class' => ''
			);
		}
		self::$TPL->metered['daily']['most_hits'] = $max;
		self::$TPL->metered['daily']['total_hits'] = $total;
	}

	protected static function hourlyStat($year, $month, $date)
	{
		$max = 0;
		$total = 0;
		$data = array();
		$hours = array_fill_keys(range(0,23), 0);

		$result = self::$db->query('SELECT hour, hits FROM '.self::$db->TBL->stats_hour." WHERE year={$year} AND month={$month} AND date={$date} GROUP BY hour, hits ORDER BY hour");
		while ($row = $result->fetch_assoc()) {
			$total += $row['hits'];
			$hours[$row['hour']] = $row['hits'];
		}
		self::$TPL->metered['hourly'] = array(
			'title' => _HOURLYSTATS.' '.self::getmonth($month).' '.$date.', '.$year,
			'most_hits' => 0,
			'total_hits' => $total,
			'rows' => array()
		);

		foreach ($hours as $hour => $hits) {
			$max = max($max, $hits);
			$hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
			self::$TPL->metered['hourly']['rows'][] = array(
				'name'  => "{$hour}:00 - {$hour}:59",
				'url'   => '',
				'hits'  => $hits,
				'class' => ''
			);
		}
		self::$TPL->metered['hourly']['most_hits'] = $max;
	}

	protected static function getmonth($k)
	{
		static $months;
		if (empty($months)) {
			$months = Dragonfly::getKernel()->L10N->get('_time','F');
		}
		return $months[$k] ?? '';
	}
}

if ('Statistics' === $Module->name && 'GET' === $_SERVER['REQUEST_METHOD']) {
	Dragonfly_Module_Statistics::run();
}
