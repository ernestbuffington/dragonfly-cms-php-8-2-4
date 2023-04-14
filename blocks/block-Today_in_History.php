<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Today_in_History.php,v $
  $Revision: 9.6 $
  $Author: djmaze $
  $Date: 2006/01/16 12:19:33 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $currentlang, $multilingual, $prefix, $userinfo;

$querylang = ($multilingual) ? "AND (language='$currentlang' OR language='')" : '';

$time = gmtime();
if (is_user() && intval($userinfo['user_timezone']) != 0) {
	$time = $time+(3600*intval($userinfo['user_timezone']));
}
$today = getdate($time);
$eday = $today['mday'];
$emonth = $today['mon'];
$content = $cnt = '';

$result = $db->sql_query("SELECT yid, content FROM ".$prefix."_history WHERE did='$eday' AND mid='$emonth' $querylang");
if ($db->sql_numrows($result) < 1) {
	$content = 'ERROR';
	return trigger_error('There are no entries for today', E_USER_NOTICE);
} else {
	$content = '<strong>'._ONEDAY.'</strong><br />';
	while (list($entry_yid, $entry_content) = $db->sql_fetchrow($result)) {
		if ($cnt == 1) { $content .= '<br /><br />'; }
		$content .= '<strong>'.$entry_yid.'</strong><br />'.$entry_content;
		$cnt = 1;
	}
}
