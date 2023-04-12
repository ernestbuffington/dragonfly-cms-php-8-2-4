<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com
  
  Enhanced with NukeStats Module Version 1.0
   Sudirman <sudirman@akademika.net>
   http://www.nuketest.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Statistics/functions.inc,v $
  $Revision: 1.4 $
  $Author: phoenix $
  $Date: 2007/09/11 11:26:08 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _StatisticsLANG;
$now = explode('-', formatDateTime( gmtime(),'%d-%m-%Y'));
$nowdate = $now[0];
$nowmonth = $now[1];
$nowyear = $now[2];

function showYearStats($nowyear) {
	global $db, $prefix, $ThemeSel;
	$l_size = getimagesize("themes/$ThemeSel/images/leftbar.gif");
	$m_size = getimagesize("themes/$ThemeSel/images/mainbar.gif");
	$r_size = getimagesize("themes/$ThemeSel/images/rightbar.gif");
	list($TotalHitsYear) = $db->sql_ufetchrow("SELECT SUM(hits) AS TotalHitsYear FROM ".$prefix."_stats_hour",SQL_NUM);
	$result = $db->sql_query("SELECT year, SUM(hits) FROM ".$prefix."_stats_hour GROUP BY year ORDER BY year");
	echo '<table class="forumline" cellspacing="1" width="100%">
	<tr>
		<td colspan="3" class="cat"><div class="cattitle" style="text-align:center;">'._YEARLYSTATS.'</div></td>
	</tr><tr>
		<td width="25%" class="row2"><span class="gen"><b>'._YEAR.'</b></span></td><td colspan="2" class="row2"><span class="gen"><b>'._SPAGESVIEWS.'</b></span></td>
	</tr>';
	while (list($year,$hits) = $db->sql_fetchrow($result)){
		echo '<tr>
		<td class="row1"><span class="gen">';
		if ($year != $nowyear) {
			echo '<a href="'.getlink("&amp;year=$year").'">'.$year.'</a>';
		} else {
			echo $year;
		}
		echo '</span></td><td class="row1" style="white-space:nowrap;">';
		$WidthIMG = round(100 * $hits/$TotalHitsYear,0);
		echo "<img src=\"themes/$ThemeSel/images/leftbar.gif\" alt=\"\" width=\"$l_size[0]\" height=\"$l_size[1]\" /><img src=\"themes/$ThemeSel/images/mainbar.gif\" height=\"".$m_size[1]."\" width=\"",$WidthIMG * 2,"\" alt=\"\" />"
		."<img src=\"themes/$ThemeSel/images/rightbar.gif\" alt=\"\" width=\"$r_size[0]\" height=\"$r_size[1]\" /></td><td class=\"row1\"><span class=\"gen\">$hits</span></td>
	</tr>";
	}
	$db->sql_freeresult($result);
	echo '</table>';
}

function showMonthStats($nowyear, $nowmonth) {
	global $prefix, $db, $ThemeSel;
	$l_size = getimagesize("themes/$ThemeSel/images/leftbar.gif");
	$m_size = getimagesize("themes/$ThemeSel/images/mainbar.gif");
	$r_size = getimagesize("themes/$ThemeSel/images/rightbar.gif");
	list($TotalHitsMonth) = $db->sql_ufetchrow("SELECT sum(hits) AS TotalHitsMonth FROM ".$prefix."_stats_hour WHERE year='$nowyear'",SQL_NUM);
	echo '<table class="forumline" cellspacing="1" width="100%">
	<tr>
		<td colspan="3" class="cat"><div class="cattitle" style="text-align:center;">'._MONTLYSTATS.' '.$nowyear.'</div></td>
	</tr><tr>
		<td width="25%" class="row2"><span class="gen"><b>'._UMONTH.'</b></span></td><td class="row2" colspan="2"><span class="gen"><b>'._SPAGESVIEWS.'</b></span></td>
	</tr>';
	$result = $db->sql_query("SELECT month, SUM(hits) FROM ".$prefix."_stats_hour WHERE year='$nowyear' GROUP BY month ORDER BY month");
	while (list($month,$hits) = $db->sql_fetchrow($result)){
		echo '<tr>
		<td class="row1"><span class="gen">';
		if ($month != $nowmonth) {
			echo "<a href=\"".getlink("&amp;year=$nowyear&amp;month=$month")."\" class=\"hover_orange\">".getmonth($month)."</a>";
		} else {
			echo getmonth($month);
		}
		echo '</span></td><td class="row1" style="white-space:nowrap;">';
		$WidthIMG = round(100 * $hits/$TotalHitsMonth,0);
		echo "<img src=\"themes/$ThemeSel/images/leftbar.gif\" alt=\"\" width=\"$l_size[0]\" height=\"$l_size[1]\" /><img src=\"themes/$ThemeSel/images/mainbar.gif\" height=\"".$m_size[1]."\" width=\"",$WidthIMG * 2,"\" alt=\"\" />";
		echo "<img src=\"themes/$ThemeSel/images/rightbar.gif\" alt=\"\" width=\"$r_size[0]\" height=\"$r_size[1]\" /></td><td class=\"row1\"><span class=\"gen\">$hits</span></td>
	</tr>";
	}
	$db->sql_freeresult($result);
	echo '</table>';
}

function showDailyStats($year, $month, $nowdate) {
	global $prefix, $db, $ThemeSel;
	$l_size = getimagesize("themes/$ThemeSel/images/leftbar.gif");
	$m_size = getimagesize("themes/$ThemeSel/images/mainbar.gif");
	$r_size = getimagesize("themes/$ThemeSel/images/rightbar.gif");

	$result = $db->sql_query("SELECT date, SUM(hits) as hits FROM ".$prefix."_stats_hour WHERE year='$year' AND month='$month' GROUP BY date ORDER BY date");
	$TotalHitsDate = $date = 0;
	$days = array();
	while ($row = $db->sql_fetchrow($result)) {
		$TotalHitsDate = $TotalHitsDate + $row['hits'];
		$date++;
		while ($date < $row['date']) {
			$days[] = array('date'=>$date, 'hits'=>0);
			$date++;
		}
		$days[] = $row;
	}
	$db->sql_freeresult($result);
	echo '<table class="forumline" cellspacing="1" width="100%">
	<tr>
		<td colspan="3" class="cat"><div class="cattitle" style="text-align:center;">'._DAILYSTATS.' '.getmonth($month).', '.$year.'</div></td>
	</tr><tr>
		<td width="25%" class="row2"><span class="gen"><b>'._DATE.'</b></span></td><td class="row2" colspan="2"><span class="gen"><b>'._SPAGESVIEWS.'</b></span></td>
	</tr>';
	foreach ($days as $day) {
		$date = $day['date'];
		$hits = $day['hits'];
		echo '<tr>
		<td class="row1"><span class="gen">';
		if ($date != $nowdate && $hits > 0 ) {
			echo '<a href="'.getlink("&amp;year=$year&amp;month=$month&amp;date=$date").'" class="hover_orange">'.$date.'</a>';
		} else {
			echo $date;
		}
		echo '</span></td><td class="row1" style="white-space:nowrap;">';
		if ($hits == 0) {
			$WidthIMG = 0;
			$d_percent = 0;
		} else {
			$WidthIMG = round(100 * $hits/$TotalHitsDate,0);
			$d_percent = substr(100 * $hits / $TotalHitsDate, 0, 5);
		}
		echo "<img src=\"themes/$ThemeSel/images/leftbar.gif\" alt=\"\" width=\"$l_size[0]\" height=\"$l_size[1]\" /><img src=\"themes/$ThemeSel/images/mainbar.gif\" height=\"".$m_size[1]."\" width=\"",$WidthIMG * 2,"\" alt=\"\" />"
			."<img src=\"themes/$ThemeSel/images/rightbar.gif\" alt=\"\" width=\"$r_size[0]\" height=\"$r_size[1]\" /></td><td class=\"row1\"><span class=\"gen\">$hits ($d_percent%)</span></td>
	</tr>";
	}
	echo '</table>';
}

function showHourlyStats($year, $month, $date) {
	global $prefix, $db, $ThemeSel;
	$l_size = getimagesize("themes/$ThemeSel/images/leftbar.gif");
	$m_size = getimagesize("themes/$ThemeSel/images/mainbar.gif");
	$r_size = getimagesize("themes/$ThemeSel/images/rightbar.gif");
	list($TotalHitsHour) = $db->sql_ufetchrow('SELECT SUM(hits) AS TotalHitsHour FROM '.$prefix."_stats_hour WHERE year='$year' AND month='$month' AND date='$date'",SQL_NUM);
	$nowdate = date('d-m-Y');
	$nowdate_arr = explode('-', $nowdate);
	echo '<table class="forumline" cellspacing="1" width="100%">
	<tr>
		<td colspan="3" class="cat"><div class="cattitle" style="text-align:center;">'._HOURLYSTATS.' '.getmonth($month).' '.$date.', '.$year.'</div></td>
	</tr><tr>
		<td width="25%" class="row2"><span class="gen"><b>'._HOUR.'</b></span></td>
		<td class="row2" colspan="2"><span class="gen"><b>'._SPAGESVIEWS.'</b></span></td>
	</tr>';
	for ($k = 0;$k<=23;$k++) {
	$result = $db->sql_query("SELECT hour, hits FROM ".$prefix."_stats_hour WHERE year='$year' AND month='$month' AND date='$date' AND hour='$k'");
	if ($db->sql_numrows($result) == 0){
		$hits=0;
	} else {
		list($hour,$hits) = $db->sql_fetchrow($result);
	}
	$db->sql_freeresult($result);
	$a = ($k < 10) ? "0$k" : $k;
	echo '<tr>
		<td class="row1"><span class="gen">';
	echo "$a:00 - $a:59";
	$a = '';
	echo '</span></td><td class="row1" style="white-space:nowrap;">';
	if ($hits == 0) {
		$WidthIMG = $d_percent = 0;
	} else {
		$WidthIMG = round(100 * $hits/$TotalHitsHour,0);
		$d_percent = substr(100 * $hits / $TotalHitsHour, 0, 5);
	}
	echo "<img src=\"themes/$ThemeSel/images/leftbar.gif\" alt=\"\" width=\"$l_size[0]\" height=\"$l_size[1]\" /><img src=\"themes/$ThemeSel/images/mainbar.gif\" height=\"".$m_size[1]."\" width=\"",$WidthIMG * 2,"\" alt=\"\" />"
		."<img src=\"themes/$ThemeSel/images/rightbar.gif\" alt=\"\" width=\"$r_size[0]\" height=\"$r_size[1]\" /></td><td class=\"row1\"><span class=\"gen\">$hits ($d_percent%)</span></td>
	</tr>";
	}
	echo '</table>';
}

function getmonth($month) {
	$month = intval($month);
	$months = array(1=>_JANUARY, _FEBRUARY, _MARCH, _APRIL, _MAY, _JUNE, _JULY, _AUGUST, _SEPTEMBER, _OCTOBER, _NOVEMBER, _DECEMBER);
	return (array_key_exists($month, $months) ? $months[$month] : '');
}
