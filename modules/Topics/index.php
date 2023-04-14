<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Topics/index.php,v $
  $Revision: 9.9 $
  $Author: phoenix $
  $Date: 2007/08/30 07:17:58 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _TopicsLANG;

global $bgcolor2, $bgcolor3, $CPG_SESS, $db, $prefix;
$result = $db->sql_query("SELECT t.topicid, t.topicimage, t.topictext, count(s.sid) AS stories, SUM(s.counter) AS readcount
	FROM {$prefix}_topics t
	LEFT JOIN {$prefix}_stories s ON (s.topic = t.topicid)
	GROUP BY t.topicid, t.topicimage, t.topictext
	ORDER BY t.topictext");
if ($db->sql_numrows($result) > 0) {
	require_once('header.php');
	OpenTable();
	echo '<form action="'.getlink('Search').'" method="post" enctype="multipart/form-data" accept-charset="utf-8" style="margin:0;">
	<div style="text-align:center;"><span class="genmed"><strong>'._ACTIVETOPICS.'</strong></span><br /><br />
	<input type="text" name="search" size="30" maxlength="255" />&nbsp;&nbsp;<input type="submit" value="'._SEARCH.'" />
	<input type="hidden" name="modules[]" value="News" />
	</div></form><br />';
	echo '<table border="0" width="100%" cellpadding="3">';
	while ($row = $db->sql_fetchrow($result)) {
		$topicid = $row['topicid'];
		$topicimage = $row['topicimage'];
		$topictext = $row['topictext'];
		$t_image = (file_exists("themes/$CPG_SESS[theme]/images/topics/$topicimage") ? "themes/$CPG_SESS[theme]/" : '')."images/topics/$topicimage";
		echo '<tr><td valign="top" style="width:25%; background:'.$bgcolor2.';">
		<a href="'.getlink("News&amp;topic=$topicid")."\"><img src=\"$t_image\" alt=\"$topictext\" title=\"$topictext\" style=\"margin:5px 0 0 5px;\" /></a><br /><br />
		<span class=\"content\">
		<strong>&#8226;</strong>&nbsp;<strong>"._TOPIC.":</strong> $topictext<br />
		<strong>&#8226;</strong>&nbsp;<strong>"._TOTNEWS.":</strong> $row[stories]<br />
		<strong>&#8226;</strong>&nbsp;<strong>"._TOTREADS.":</strong> ".($row['readcount'] ?? 0)."</span>
		</td>
		<td valign=\"top\" style=\"background:$bgcolor3;\">";

		if ($row['stories'] > 0) {
			$result2 = $db->sql_query('SELECT s.sid, s.catid, s.title, c.title AS cat_title FROM '.$prefix.'_stories s
			LEFT JOIN '.$prefix."_stories_cat c ON s.catid=c.catid
			WHERE s.topic='$topicid' ORDER BY s.sid DESC LIMIT 0,10");
			while ($row2 = $db->sql_fetchrow($result2)) {
				$cat_link = ($row2['catid'] > 0) ? '<a href="'.getlink('News&amp;catid='.$row2['catid']).'"><strong>'.$row2['cat_title'].'</strong></a>: ' : '';
				echo '<b>&#8226;</b>&nbsp;'.$cat_link.'<a href="'.getlink('News&amp;file=article&amp;sid='.$row2['sid']).'">'.$row2['title'].'</a><br />';
			}
			if ($row['stories'] > 10) {
				echo '<div style="text-align:right;"><b>&#8226;</b>&nbsp;<a href="'.getlink('News&amp;topic='.$topicid).'"><strong>'._MORE.' --&gt;</strong></a></div>';
			}
		} else {
			echo sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_ARTICLES));
		}
		echo '</td></tr>';
	}
	echo '</table><br />';
	CloseTable();
} else {
	cpg_error(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_TopicsLANG)));
}