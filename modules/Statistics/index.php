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

  $Source: /cvs/html/modules/Statistics/index.php,v $
  $Revision: 9.14 $
  $Author: djmaze $
  $Date: 2006/04/27 19:42:47 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
require("modules/$module_name/functions.inc");

function Stats_Main() {
	global $prefix, $db, $cpgtpl, $startdate, $sitename, $user_prefix;
	require_once('header.php');
	$result  = $db->sql_query('SELECT type, var, count FROM '.$prefix.'_counter ORDER BY count DESC, var');
	$browser = $os = array();
	$totalos = $totalbr = 0;
	while (list($type, $var, $count) = $db->sql_fetchrow($result)) {
		if ($type == 'browser') {
			$browser[$var] = $count;
			$totalbr += $count;
		} elseif ($type == 'os') {
			if ($var == 'OS/2') { $var = 'OS2'; }
			$os[$var] = $count;
			$totalos += $count;
		}
	}
	$db->sql_freeresult($result);

	$cpgtpl->assign_vars(array(
		'S_STATS_TITLE' => $sitename.' '._STATS,
		'S_STATS_TOTAL' => _WERECEIVED.' <b>'.$totalbr.'</b> '._PAGESVIEWS.' '.$startdate,
		'L_STATS_DETAIL' => _VIEWDETAILED,
		'L_STATS_BROWSERS' => _BROWSERS,
		'L_STATS_OS' => _OPERATINGSYS,
		'L_STATS_MISC' => _MISCSTATS,
		'U_STATS_DETAIL' => getlink("&amp;file=details")
	));

// Browsers
	$totalbr = 100 / $totalbr;
	foreach ($browser AS $var => $count) {
		$perc = round(($totalbr * $count), 2);
		$cpgtpl->assign_block_vars('browsers', array(
			'IMG' => strtolower($var),
			'NAME' => $var,
			'PERC' => "$perc %",
			'COUNT' => $count,
			'WIDTH' => $perc
		));
	}
// Operating System
	$totalos = 100 / $totalos;
	foreach ($os AS $var => $count) {
		$perc = round(($totalos * $count), 2);
		$cpgtpl->assign_block_vars('os', array(
			'IMG' => strtolower($var),
			'NAME' => $var,
			'PERC' => "$perc %",
			'COUNT' => $count,
			'WIDTH' => $perc
		));
	}
// Miscellaneous Stats
	list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$user_prefix.'_users WHERE user_id > 1',SQL_NUM);
	$cpgtpl->assign_block_vars('misc', array('IMG' => 'users', 'NAME' => _REGUSERS, 'COUNT' => $count));

	list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_stories',SQL_NUM);
	$cpgtpl->assign_block_vars('misc', array('IMG' => 'news', 'NAME' => _STORIESPUBLISHED, 'COUNT' => $count));

	if (is_active('Topics')) {
		list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_topics",SQL_NUM);
		$cpgtpl->assign_block_vars('misc', array('IMG' => 'topics', 'NAME' => _SACTIVETOPICS, 'COUNT' => $count));
	}

	list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_comments',SQL_NUM);
	$cpgtpl->assign_block_vars('misc', array('IMG' => 'comments', 'NAME' => _COMMENTSPOSTED, 'COUNT' => $count));

	if (is_active('Sections')) {
		list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_sections',SQL_NUM);
		$cpgtpl->assign_block_vars('misc', array('IMG' => 'sections', 'NAME' => _SSPECIALSECT, 'COUNT' => $count));
		list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_seccont',SQL_NUM);
		$cpgtpl->assign_block_vars('misc', array('IMG' => 'articles', 'NAME' => _ARTICLESSEC, 'COUNT' => $count));
	}
	if (is_active('Web_Links')) {
		list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_links_links',SQL_NUM);
		$cpgtpl->assign_block_vars('misc', array('IMG' => 'topics', 'NAME' => _LINKSINLINKS, 'COUNT' => $count));
		list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_links_categories',SQL_NUM);
		$cpgtpl->assign_block_vars('misc', array('IMG' => 'sections', 'NAME' => _LINKSCAT, 'COUNT' => $count));
	}

	list($count) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_queue',SQL_NUM);
	$cpgtpl->assign_block_vars('misc', array('IMG' => 'waiting', 'NAME' => _NEWSWAITING, 'COUNT' => $count));
	$cpgtpl->set_filenames(array('body' => 'statistics/index.html'));
	$cpgtpl->display('body');
}

function YearlyStats($year) {
	global $nowmonth, $sitename;
	require_once('header.php');
	OpenTable();
	showMonthStats($year,$nowmonth);
	echo '<br />';
	echo "<center>[ <a href=\"".getlink()."\">"._BACKTOMAIN."</a> | <a href=\"".getlink("&amp;file=details")."\">"._BACKTODETSTATS."</a> ]</center>";
	CloseTable();
}

function MonthlyStats($year, $month) {
	global $sitename, $nowdate;
	require_once('header.php');
	OpenTable();
	showDailyStats($year,$month,$nowdate);
	echo '<br />';
	echo "<center>[ <a href=\"".getlink()."\">"._BACKTOMAIN."</a> | <a href=\"".getlink("&amp;file=details")."\">"._BACKTODETSTATS."</a> ]</center>";
	CloseTable();
}

function DailyStats($year, $month, $date) {
	global $sitename;
	require_once('header.php');
	OpenTable();
	showHourlyStats($year,$month,$date);
	echo '<br />';
	echo "<center>[ <a href=\"".getlink()."\">"._BACKTOMAIN."</a> | <a href=\"".getlink("&amp;file=details")."\">"._BACKTODETSTATS."</a> ]</center>";
	CloseTable();
}

$op = (isset($_GET['op']) && $_GET['op']!='') ? $_GET['op'] : ((isset($_POST['op']) && $_POST['op']!='') ? $_POST['op'] : '');
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$date = isset($_GET['date']) ? intval($_GET['date']) : 0;

if ($year) {
	if ($month) {
		if ($date) {
			DailyStats($year,$month,$date);
		} else {
			MonthlyStats($year,$month);
		}
	} else {
		YearlyStats($year);
	}
} else {
	Stats_Main();
}
