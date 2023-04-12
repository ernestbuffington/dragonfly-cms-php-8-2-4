<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Top/index.php,v $
  $Revision: 9.10 $
  $Author: phoenix $
  $Date: 2007/08/30 06:26:12 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _TopLANG;
require_once('header.php');

if ($multilingual) {
	$querylang = "WHERE language='$currentlang' OR alanguage=''";
	$queryalang = "WHERE (alanguage='$currentlang' OR alanguage='')"; // top stories
	$querya1lang = "WHERE (alanguage='$currentlang' OR alanguage='') AND"; // top stories
	$queryslang = "WHERE slanguage='$currentlang' "; // top section articles
	$queryplang = "WHERE planguage='$currentlang' "; // top polls
	$queryrlang = "WHERE language='$currentlang' OR language=''"; // top reviews
} else {
	$querylang = $queryalang = $queryslang = $queryplang = $queryrlang = '';
	$querya1lang = 'WHERE';
}

OpenTable();

/* Top 10 read stories */
$result = $db->sql_query("SELECT sid, title, counter FROM ".$prefix."_stories $queryalang ORDER BY counter DESC LIMIT 0,$top");
if ($db->sql_numrows($result) > 0) {
	echo '<div style="padding:10px;">
	<span class="option"><strong>'.$top.' '._READSTORIES.'</strong></span><br /><br /><span class="content">';
	$rank = 1;
	while (list($sid, $title, $counter) = $db->sql_fetchrow($result)) {
		if ($counter > 0) {
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("News&amp;file=article&amp;sid=$sid").'">'.$title.'</a> - ('.$counter.' '._READS.')<br />';
			$rank++;
		}
	}
	echo '</span></div><hr />';
}
$db->sql_freeresult($result);

/* Top 10 most voted stories */
$result = $db->sql_query("SELECT sid, title, ratings FROM ".$prefix."_stories $querya1lang score!='0' ORDER BY ratings DESC LIMIT 0,$top");
if ($db->sql_numrows($result) > 0) {
	echo '<div style="padding:10px;">
	<span class="option"><strong>'.$top.' '._MOSTVOTEDSTORIES.'</strong></span><br /><br /><span class="content">';
	$rank = 1;
	while (list($sid, $title, $ratings) = $db->sql_fetchrow($result)) {
		if ($ratings > 0) {
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("News&amp;file=article&amp;sid=$sid").'">'.$title.'</a> - ('.$ratings.' '._LVOTES.')<br />';
			$rank++;
		}
	}
	echo '</span></div><hr />';
}
$db->sql_freeresult($result);

/* Top 10 best rated stories */
$result = $db->sql_query("SELECT sid, title, score, ratings FROM ".$prefix."_stories $querya1lang score!='0' ORDER BY ratings+score DESC LIMIT 0,$top");
if ($db->sql_numrows($result) > 0) {
	echo '<div style="padding:10px;">
	<span class="option"><strong>'.$top.' '._BESTRATEDSTORIES.'</strong></span><br /><br /><span class="content">';
	$rank = 1;
	while (list($sid, $title, $score, $ratings) = $db->sql_fetchrow($result)) {
		if ($score > 0) {
			$rate = substr($score / $ratings, 0, 4);
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("News&amp;file=article&amp;sid=$sid").'">'.$title.'</a> - ('.$rate.' '._POINTS.')<br />';
			$rank++;
		}
	}
	echo '</span></div><hr />';
}
$db->sql_freeresult($result);

/* Top 10 commented stories */
if ($articlecomm) {
	$result = $db->sql_query("SELECT sid, title, comments FROM ".$prefix."_stories $queryalang ORDER BY comments DESC LIMIT 0,$top");
	if ($db->sql_numrows($result) > 0) {
		echo '<div style="padding:10px;">
		<span class="option"><strong>'.$top.' '._COMMENTEDSTORIES.'</strong></span><br /><br /><span class="content">';
		$rank = 1;
		while (list($sid, $title, $comments) = $db->sql_fetchrow($result)) {
			if ($comments > 0) {
				echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("News&amp;file=article&amp;sid=$sid").'">'.$title.'</a> - ('.$comments.' '._COMMENTS.')<br />';
				$rank++;
			}
		}
		echo '</span></div><hr />';
	}
	$db->sql_freeresult($result);
}

/* Top 10 categories */
$result = $db->sql_query("SELECT catid, title, counter FROM ".$prefix."_stories_cat ORDER BY counter DESC LIMIT 0,$top");
if ($db->sql_numrows($result) > 0) {
	echo '<div style="padding:10px;">
	<span class="option"><strong>'.$top.' '._ACTIVECAT.'</strong></span><br /><br /><span  class="content">';
	$rank = 1;
	while (list($catid, $title, $counter) = $db->sql_fetchrow($result)) {
		if ($counter > 0) {
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("News&amp;catid=$catid").'">'.$title.'</a> - ('.$counter.' '._HITS.')<br />';
			$rank++;
		}
	}
	echo '</span></div><hr />';
}
$db->sql_freeresult($result);

/* Top 10 articles in special sections */
if (is_active('Sections')) {
	$result = $db->sql_query("SELECT artid, secid, title, content, counter FROM ".$prefix."_seccont $queryslang ORDER BY counter DESC LIMIT 0,$top");
	if ($db->sql_numrows($result) > 0) {
		echo '<div style="padding:10px;">
		<span class="option"><strong>'.$top.' '._READSECTION.'</strong></span><br /><br /><span class="content">';
		$rank = 1;
		while (list($artid, $secid, $title, $content, $counter) = $db->sql_fetchrow($result)) {
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("Sections&amp;op=viewarticle&amp;artid=$artid").'">'.$title.'</a> - ('.$counter.' '._READS.')<br />';
			$rank++;
		}
		echo '</span></div><hr />';
	}
	$db->sql_freeresult($result);
}

/* Top 10 users submitters */
$result = $db->sql_query("SELECT user_id, username, counter FROM ".$user_prefix."_users WHERE counter > '0' ORDER BY counter DESC LIMIT 0,$top");
if ($db->sql_numrows($result) > 0) {
	echo '<div style="padding:10px;">
	<span class="option"><strong>'.$top.' '._NEWSSUBMITTERS.'</strong></span><br /><br /><span class="content">';
	$rank = 1;
	while ($row = $db->sql_fetchrow($result)) {
		if ($row['counter'] > 0) {
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("Your_Account&amp;profile=$row[user_id]").'">'.$row['username'].'</a> - ('.$row['counter'].' '._NEWSSENT.')<br />';
			$rank++;
		}
	}
	echo '</span></div><hr />';
}
$db->sql_freeresult($result);

/* Top 10 Polls */
if (is_active('Surveys')) {
	 $sql = "SELECT a.poll_id,a.poll_title,SUM(b.option_count) AS sum FROM ".$prefix."_poll_desc a LEFT JOIN "
		.$prefix."_poll_data b on a.poll_id = b.poll_id GROUP BY a.poll_id,a.poll_title,a.voters ORDER BY voters "
		."DESC LIMIT 0,$top";
	$result = $db->sql_query($sql);
	if ($db->sql_numrows($result) > 0) {
		echo '<div style="padding:10px;">
	<span class="option"><strong>'.$top.' '._VOTEDPOLLS.'</strong></span><br /><br /><span class="content">';
		$rank = 1;
		while ($row = $db->sql_fetchrow($result)) {
			echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("Surveys&amp;pollid=$row[poll_id]").'">'.$row['poll_title'].'</a> - ('.$row['sum'].' '._LVOTES.')<br />';
			$rank++;
		}
		echo '</span></div><hr />';
	}
	$db->sql_freeresult($result);
}

/* Top 10 reviews */
if (is_active('Reviews')) {
	$result = $db->sql_query("SELECT id, title, hits FROM ".$prefix."_reviews $queryrlang ORDER BY hits DESC LIMIT 0,$top");
	if ($db->sql_numrows($result) > 0) {
		echo '<div style="padding:10px;">
		<span class="option"><strong>'.$top.' '._READREVIEWS.'</strong></span><br /><br /><span class="content">';
		$rank = 1;
		while (list($id, $title, $hits) = $db->sql_fetchrow($result)) {
			if ($hits > 0) {
				echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("Reviews&amp;op=showcontent&amp;id=$id").'">'.$title.'</a> - ('.$hits.' '._READS.')<br />';
				$rank++;
			}
		}
		echo '</span></div><hr />';
	}
	$db->sql_freeresult($result);
}

/* Top 10 Pages in Content */
if (is_active('Content')) {
	$result = $db->sql_query("SELECT pid, title, counter FROM ".$prefix."_pages WHERE active='1' ORDER BY counter DESC LIMIT 0, $top");
	if ($db->sql_numrows($result) > 0) {
		echo '<div style="padding:10px;">
		<span class="option"><strong>'.$top.' '._MOSTREADPAGES.'</strong></span><br /><br /><span class="content">';
		$rank = 1;
		while (list($pid, $title, $counter) = $db->sql_fetchrow($result)) {
			if ($counter > 0) {
				echo '<b>&#8226;</b>&nbsp;'.$rank.': <a href="'.getlink("Content&amp;pa=showpage&amp;pid=$pid").'">'.$title.'</a> ('.$counter.' '._READS.')<br />';
				$rank++;
			}
		}
		echo '</span></div>';
	}
	$db->sql_freeresult($result);
}

CloseTable();
