<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-Random_Headlines.php,v $
  $Revision: 9.7 $
  $Author: phoenix $
  $Date: 2007/08/30 04:54:29 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('News')) {
	$content = 'ERROR';
	return trigger_error('News module is inactive', E_USER_WARNING);
}

global $currentlang, $db, $multilingual, $prefix, $userinfo;

$content = $topic_array = $r_topic = $topic = '';
$querylang = ($multilingual) ? "AND (alanguage='$currentlang' OR alanguage='')" : '';

$result = $db->sql_query("SELECT topicid FROM ".$prefix."_topics");
list($numrows) = $db->sql_numrows($result);
if ($numrows > 1) {
	while (list($topicid) = $db->sql_fetchrow($result)) {
		$topic_array .= "$topicid-";
	}
	$r_topic = explode('-', $topic_array);
	mt_srand((double)microtime()*1000000);
	$numrows = $numrows-1;
	$topic = random_int(0, $numrows);
	$topic = $r_topic[$topic];
} else {
	$topic = 1;
}
$db->sql_freeresult($result);

list($topicimage, $topictext) = $db->sql_ufetchrow("SELECT topicimage, topictext FROM ".$prefix."_topics WHERE topicid='$topic'",SQL_NUM);

$content = '<div style="text-align:center;">
<a href="'.getlink('News&amp;topic='.$topic).'"><img src="images/topics/'.$topicimage.'" alt="'.$topictext.'" title="'.$topictext.'" /></a><br />
[ <a href="'.getlink('Search&amp;topic='.$topic).'">'.$topictext.'</a> ]
<br /></div>
<div>';

$result = $db->sql_query("SELECT sid, title FROM ".$prefix."_stories WHERE topic='$topic' $querylang ORDER BY sid DESC LIMIT 0,9");
while (list($sid, $s_title) = $db->sql_fetchrow($result)) {
	$content .= '<b>&#8226;</b>&nbsp;
	<a href="'.getlink('News&amp;file=article&amp;sid='.$sid).'">'.$s_title.'</a>';
}
$db->sql_freeresult($result);

$content .= '</div>';
