<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Stories_Archive/index.php,v $
  $Revision: 9.13 $
  $Author: phoenix $
  $Date: 2007/08/30 06:23:31 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _STORIESARCHIVE;

$sa = $_GET['sa'] ?? '';

if ($sa == 'show_month') {
	$year = (isset($_GET['year']) && intval($_GET['year']) > 0) ? intval($_GET['year']) : gmdate('Y');
	$month = (isset($_GET['month']) && intval($_GET['month']) > 0) ? intval($_GET['month']) : gmdate('m');
	$months = array(_JANUARY, _FEBRUARY, _MARCH, _APRIL, _MAY, _JUNE, _JULY, _AUGUST, _SEPTEMBER, _OCTOBER, _NOVEMBER, _DECEMBER);
	$pagetitle .= ' '._BC_DELIM.' '.$months[$month-1]." $year";
	require_once('header.php');
	OpenTable();
	echo '<table border="0" width="100%"><tr>'
	."<td style=\"background:$bgcolor2; text-align:left;\"><strong>"._ARTICLES."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._COMMENTS."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._READS."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._USCORE."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._DATE."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._ACTIONS."</strong></td></tr>";

	$date	 = L10NTime::toGMT(mktime(0, 0, 0, $month, 1, $year), $userinfo['user_dst'], $userinfo['user_timezone']);
	$enddate = L10NTime::toGMT(mktime(0, 0, 0, $month+1, 1, $year), $userinfo['user_dst'], $userinfo['user_timezone']);
	$result = $db->sql_query("SELECT sid, catid, title, time, comments, counter, topic, alanguage, score, ratings FROM ".$prefix."_stories WHERE time >= $date AND time < $enddate ORDER BY sid DESC");
	while (list($sid, $catid, $title, $time, $comments, $counter, $topic, $alanguage, $score, $ratings) = $db->sql_fetchrow($result)) {
		$actions = '<a href="'.getlink("News&amp;file=print&amp;sid=$sid").'"><img src="images/news/print.gif" alt="'._PRINTER.'" title="'._PRINTER.'" /></a>
		<a href="'.getlink('News&amp;file=friend&amp;sid='.$sid).'"><img src="images/news/friend.gif" alt="'._FRIEND.'" title="'._FRIEND.'" /></a>';
		$rated = ($score != 0) ? substr($score / $ratings, 0, 4) : 0;
		if ($catid == 0) {
			$title = '<a href="'.getlink('News&amp;file=article&amp;sid='.$sid)."\">$title</a>";
		} elseif ($catid != 0) {
			list($cat_title) = $db->sql_ufetchrow("SELECT title FROM ".$prefix."_stories_cat WHERE catid='$catid'", SQL_NUM);
			$title = '<a href="'.getlink('News&amp;catid='.$catid).'"><i>'.$cat_title.'</i></a>: <a href="'.getlink('News&amp;file=article&amp;sid='.$sid).'">'.$title.'</a>';
		}
		if ($multilingual) {
			if ($alanguage == '') { $alanguage = $language; }
			$alt_language = ucfirst($alanguage);
			$lang_img = "<img src=\"images/language/flag-$alanguage.png\" hspace=\"2\" alt=\"$alt_language\" title=\"$alt_language\" />";
		} else {
			$lang_img = '<strong>&middot;</strong>';
		}
		echo '<tr>'
		."<td style=\"background:$bgcolor1; text-align:left;\">$lang_img $title</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$comments</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$counter</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$rated</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">".formatDateTime($time, _DATESTRING3)."</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$actions</td></tr>";
	}
	echo '</table>
	<br /><br /><br /><hr />
	<span class="content">'._SELECTMONTH2VIEW.'</span><br />'
	.monthlist()
	.'<br /><br /><div style="text-align:center;">
	<form action="'.getlink('Search').'" method="post" enctype="multipart/form-data" accept-charset="utf-8"><div>
	<input type="text" name="search" size="30" maxlength="255" />&nbsp;
	<input type="hidden" name="modules[]" value="News" />
	<input type="submit" value="'._SEARCH.'" />
	</div></form>
	[ <a href="'.getlink().'">'._ARCHIVESINDEX.'</a> | <a href="'.getlink('&amp;sa=show_all').'">'._SHOWALLSTORIES.'</a> ]</div>';
	CloseTable();
}
else if ($sa == 'show_all') {
	$min = isset($_GET['min']) ? intval($_GET['min']) : 0;
	$max = 250;
	$a = 0;
	$pagetitle .= ' '._BC_DELIM.' '._ALLSTORIESARCH;
	require_once('header.php');
	OpenTable();
	echo '<table border="0" width="100%"><tr>'
	."<td style=\"background:$bgcolor2; text-align:left;\"><strong>"._ARTICLES."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._COMMENTS."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._READS."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._USCORE."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._DATE."</strong></td>"
	."<td style=\"background:$bgcolor2; text-align:center;\"><strong>"._ACTIONS."</strong></td></tr>";

	$numrows = $db->sql_count($prefix.'_stories');
	$result = $db->sql_query("SELECT sid, catid, title, time, comments, counter, topic, alanguage, score, ratings FROM ".$prefix."_stories ORDER BY sid DESC LIMIT $min,$max");
	while (list($sid, $catid, $title, $time, $comments, $counter, $topic, $alanguage, $score, $ratings) = $db->sql_fetchrow($result)) {
	$actions = '<a href="'.getlink('News&amp;file=print&amp;sid='.$sid).'"><img src="images/news/print.gif" alt="'._PRINTER.'" title="'._PRINTER.'" /></a>
	<a href="'.getlink('News&amp;file=friend&amp;sid='.$sid).'"><img src="images/news/friend.gif" alt="'._FRIEND.'" title="'._FRIEND.'" /></a>';
	$rated = ($score != 0) ? substr($score / $ratings, 0, 4) : 0;
	if ($catid == 0) {
		$title = '<a href="'.getlink('News&amp;file=article&amp;sid='.$sid).'">'.$title.'</a>';
	} else {
		list($cat_title) = $db->sql_ufetchrow("SELECT title FROM ".$prefix."_stories_cat WHERE catid='$catid'", SQL_NUM);
		$title = '<a href="'.getlink('News&amp;catid='.$catid).'"><i>'.$cat_title.'</i></a>: <a href="'.getlink('News&amp;file=article&amp;sid='.$sid).'">'.$title.'</a>';
	}
	if ($multilingual) {
		if ($alanguage == '') { $alanguage = $language; }
		$alt_language = ucfirst($alanguage);
		$lang_img = "<img src=\"images/language/flag-$alanguage.png\" hspace=\"2\" alt=\"$alt_language\" title=\"$alt_language\" />";
	} else {
		$lang_img = '<strong>&middot;</strong>';
	}
	echo '<tr>'
		."<td style=\"background:$bgcolor1; text-align:left;\">$lang_img $title</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$comments</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$counter</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">$rated</td>"
		."<td style=\"background:$bgcolor1; text-align:center;\">".formatDateTime($time, _DATESTRING3).'</td>'
		."<td style=\"background:$bgcolor1; text-align:center;\">$actions</td></tr>";
	}
	echo '</table><br /><br /><br />';
	if ($numrows > 250 && $min == 0) {
		$min = $min+250;
		$a++;
		echo '<div style="text-align:center;">[ <a href="'.getlink("&amp;sa=show_all&amp;min=$min").'">'._NEXTPAGE.'</a> ]</div><br />';
	}
	if ($numrows > 250 && $min >= 250 && $a != 1) {
		$pmin = $min-250;
		$min = $min+250;
		$a++;
		echo '<div style="text-align:center;">[ <a href="'.getlink('&amp;sa=show_all&amp;min='.$pmin).'">'._PREVIOUSPAGE.'</a> | <a href="'.getlink('&amp;sa=show_all&amp;min='.$min).'">'._NEXTPAGE.'</a> ]</div><br />';
	}
	if ($numrows <= 250 && $a != 1 && $min != 0) {
		$pmin = $min-250;
		echo '<div style="text-align:center;">[ <a href="'.getlink('&amp;sa=show_all&amp;min='.$pmin).'">'._PREVIOUSPAGE.'</a> ]</div><br />';
	}
	echo '<hr />
	<span class="content">'._SELECTMONTH2VIEW.'</span><br />'
	.monthlist()
	.'<br /><br /><div style="text-align:center;">
	<form action="'.getlink('Search').'" method="post" enctype="multipart/form-data" accept-charset="utf-8"><div>
	<input type="text" name="search" size="30" maxlength="255" />&nbsp;
	<input type="hidden" name="modules[]" value="News" />
	<input type="submit" value="'._SEARCH.'" />
	</div></form>
	[ <a href="'.getlink().'">'._ARCHIVESINDEX.'</a> ]</div>';
	CloseTable();
}
else {
	require_once('header.php');
	OpenTable();
	echo '<div style="text-align:center;" class="content">'._SELECTMONTH2VIEW.'</div><br /><br />'
	.monthlist()
	.'<br /><br /><div style="text-align:center;">
	<form action="'.getlink('Search').'" method="post" enctype="multipart/form-data" accept-charset="utf-8"><div>
	<input type="text" name="search" size="30" maxlength="255" />&nbsp;
	<input type="hidden" name="modules[]" value="News" />
	<input type="submit" value="'._SEARCH.'" />
	</div></form><br /><br />
	[ <a href="'.getlink('&amp;sa=show_all').'">'._SHOWALLSTORIES.'</a> ]</div>';
	CloseTable();
}

function monthlist() {
	global $db, $prefix, $userinfo;
	list($time) = $db->sql_ufetchrow('SELECT time FROM '.$prefix.'_stories ORDER BY time ASC LIMIT 0,1', SQL_NUM);
	if ($time < 1) { return ''; }
	$time = L10NTime::tolocal($time, $userinfo['user_dst'], $userinfo['user_timezone']);
	$firstyear = L10NTime::date('Y', $time);
	$firstmonth = intval(L10NTime::date('m', $time));
	$time = L10NTime::tolocal(gmtime(), $userinfo['user_dst'], $userinfo['user_timezone']);
	$year = L10NTime::date('Y', $time);
	$month = intval(L10NTime::date('m', $time));
	$months = array(_JANUARY, _FEBRUARY, _MARCH, _APRIL, _MAY, _JUNE, _JULY, _AUGUST, _SEPTEMBER, _OCTOBER, _NOVEMBER, _DECEMBER);
	$return = '<ul>';
	while ($year >= $firstyear) {
		if ($year <= $firstyear && $month < $firstmonth) break;
		$return .= '<li><a href="'.getlink("&amp;sa=show_month&amp;year=$year&amp;month=$month").'">'.$months[$month-1]." $year</a></li>";
		$month--;
		if ($month < 1) {
			$month = 12;
			$year--;
		}
	}
	return $return.'</ul>';
}
