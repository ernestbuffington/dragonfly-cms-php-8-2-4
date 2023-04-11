<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }

$OUT = Dragonfly::getKernel()->OUT;
\Dragonfly\Page::title($OUT->L10N['_STORIESARCHIVE'], false);

$sa = isset($_GET['sa']) ? $_GET['sa'] : '';

if ($sa == 'show_month')
{
	$year = $_GET->uint('year') ?: date('Y');
	$month = $_GET->uint('month') ?: date('m');
	$months = array(_JANUARY, _FEBRUARY, _MARCH, _APRIL, _MAY, _JUNE, _JULY, _AUGUST, _SEPTEMBER, _OCTOBER, _NOVEMBER, _DECEMBER);
	\Dragonfly\Page::title($months[$month-1]." $year", false);
	$date	 = mktime(0, 0, 0, $month, 1, $year);
	$enddate = min(time(), mktime(0, 0, 0, $month+1, 1, $year));
	$OUT->search_news = true;
	$OUT->stories = $db->query("SELECT
			sid,
			s.title,
			s.ptime,
			s.comments,
			s.counter,
			s.topic,
			s.alanguage,
			s.score,
			s.ratings,
			ROUND(s.score / s.ratings, 2) rated,
			c.catid,
			c.title cat_title
		FROM {$db->TBL->stories} s
		LEFT JOIN {$db->TBL->stories_cat} c ON (c.catid = s.catid)
		WHERE s.ptime >= {$date} AND s.ptime < {$enddate}
		ORDER BY s.ptime DESC");
	$OUT->display('News/archive-list');
}

else if ($sa == 'show_all')
{
	\Dragonfly\Page::title($OUT->L10N['_ALLSTORIESARCH'], false);
	$min = max(0, $_GET->uint('min'));
	$limit = 50;
	$OUT->search_news = false;
	$OUT->stories = $db->query("SELECT
			sid,
			s.title,
			s.ptime,
			s.comments,
			s.counter,
			s.topic,
			s.alanguage,
			s.score,
			s.ratings,
			ROUND(s.score / s.ratings, 2) rated,
			c.catid,
			c.title cat_title
		FROM {$db->TBL->stories} s
		LEFT JOIN {$db->TBL->stories_cat} c ON (c.catid = s.catid)
		WHERE s.ptime <= ".time()."
		ORDER BY s.ptime DESC
		LIMIT {$limit} OFFSET {$min}");
	$numrows = $db->TBL->stories->count();
	if ($numrows > $limit) {
		$OUT->archive_pagination = array(
			'prev' => $min ? URL::index('&file=archive&sa=show_all&min='.max(0, $min-$limit)) : null,
			'next' => ($numrows > $min+$limit) ? URL::index('&file=archive&sa=show_all&min='.($min+$limit)) : null
		);
	}
	$OUT->display('News/archive-list');
}

else
{
	$years = array();
	$months = array(_JANUARY, _FEBRUARY, _MARCH, _APRIL, _MAY, _JUNE, _JULY, _AUGUST, _SEPTEMBER, _OCTOBER, _NOVEMBER, _DECEMBER);
	$result = $db->query("SELECT
		DATE_FORMAT(FROM_UNIXTIME(ptime-".date('Z')."), '%Y%m'),
		COUNT(sid)
	FROM {$db->TBL->stories}
	GROUP BY 1
	ORDER BY 1 DESC");
	while ($r = $result->fetch_row()) {
		$y = substr($r[0],0,4);
		if (!isset($years[$y])) {
			$years[$y] = array('label'=>$y, 'months'=>array());
		}
		$m = (int)substr($r[0],-2);
		$years[$y]['months'][] = array(
			'label' => $months[$m-1],
			'url' => URL::index("&file=archive&sa=show_month&year={$y}&month={$m}"),
			'stories' => $OUT->L10N->plural($r[1], '%d items')
		);
	}
	$OUT->stories_archive_years = $years;
	$OUT->display('News/archive');
}
