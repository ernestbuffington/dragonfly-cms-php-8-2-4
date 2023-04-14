<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange
  
  Enhanced with NukeStats Module Version 1.0
   Sudirman <sudirman@akademika.net>
   http://www.nuketest.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Statistics/details.php,v $
  $Revision: 9.2 $
  $Author: djmaze $
  $Date: 2006/01/06 13:19:57 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
require("modules/$module_name/functions.inc");

global $nowyear, $nowmonth, $nowdate, $nowhour, $sitename, $startdate, $prefix, $db, $now;

list($total) = $db->sql_ufetchrow('SELECT SUM(count) FROM '.$prefix."_counter WHERE type='os'", SQL_NUM);
require_once('header.php');
$total++;
OpenTable();
echo '<table class="forumline" cellspacing="1" width="100%">
<tr>
	<td class="cat"><div class="cattitle" align="center">'.$sitename.' '._STATS.'</div></td>
</tr><tr>
	<td class="row1" align="center"><span class="gen">'._WERECEIVED." <b>$total</b> "._PAGESVIEWS." $startdate<br />"._TODAYIS.": ".sprintf(_DATESTRING4,getmonth($now[1]),$now[0],$now[2])."<br />";

list($year, $month, $hits) = $db->sql_ufetchrow("SELECT year, month, SUM(hits) as hits FROM ".$prefix."_stats_hour GROUP BY month, year ORDER BY hits DESC limit 0,1",SQL_NUM);
echo _MOSTMONTH.": ".getmonth($month)." $year ($hits "._HITS.")<br />";

list($year, $month, $date, $hits) = $db->sql_ufetchrow("SELECT year, month, date, SUM(hits) as hits FROM ".$prefix."_stats_hour GROUP BY date, month, year ORDER BY hits DESC limit 0,1",SQL_NUM);
echo _MOSTDAY.": $date ".getmonth($month)." $year ($hits "._HITS.")<br />";

list($year, $month, $date, $hour, $hits) = $db->sql_ufetchrow("SELECT year, month, date, hour, hits from ".$prefix."_stats_hour ORDER BY hits DESC limit 0,1",SQL_NUM);
if ($hour < 10) {
	$hour = "0$hour:00 - 0$hour:59";
} else {
	$hour = "$hour:00 - $hour:59";
}
echo _MOSTHOUR.": $hour "._ON." ".getmonth($month)." $date, $year ($hits "._HITS.")<br /><br />[ <a href=\"".getlink()."\">"._RETURNBASICSTATS.'</a> ]</span><br />&nbsp;</td>
</tr></table><br />';

showYearStats($nowyear);
echo '<br />';
showMonthStats($nowyear,$nowmonth);
echo '<br />';
showDailyStats($nowyear,$nowmonth,$nowdate);
echo '<br />';
showHourlyStats($nowyear,$nowmonth,$nowdate);
CloseTable();
