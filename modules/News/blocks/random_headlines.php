<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $currentlang, $db;

$content = $topic_array = $r_topic = $topic = '';
$querylang = (Dragonfly::getKernel()->L10N->multilingual ? "AND (alanguage='$currentlang' OR alanguage='')" : '');

$result = $db->query("SELECT topicid FROM ".$db->TBL->topics);
$numrows = $result->num_rows;
if ($numrows > 1) {
	while (list($topicid) = $result->fetch_row()) {
		$topic_array .= "$topicid-";
	}
	$r_topic = explode('-', $topic_array);
	mt_srand((double)microtime()*1000000);
	$numrows = $numrows-1;
	$topic = mt_rand(0, $numrows);
	$topic = $r_topic[$topic];
} else {
	$topic = 1;
}
$result->free();

list($topicimage, $topictext) = $db->uFetchRow("SELECT topicimage, topictext FROM {$db->TBL->topics} WHERE topicid='$topic'");

$content = '<div style="text-align:center;">
<a href="'.htmlspecialchars(URL::index('News&topic='.$topic)).'"><img src="images/topics/'.$topicimage.'" alt="'.$topictext.'" title="'.$topictext.'" /></a><br />
[ <a href="'.htmlspecialchars(URL::index('Search&topic='.$topic)).'">'.$topictext.'</a> ]
<br /></div>
<div>';

$result = $db->query("SELECT sid, title FROM {$db->TBL->stories} WHERE ptime<=".time()." topic='$topic' $querylang ORDER BY sid DESC LIMIT 9");
while (list($sid, $s_title) = $result->fetch_row()) {
	$content .= '<a href="'.htmlspecialchars(URL::index('News&file=article&sid='.$sid)).'">'.$s_title.'</a><br/>';
}
$result->free();

$content .= '</div>';
