<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Big_Story_of_Today.php,v $
  $Revision: 9.5 $
  $Author: djmaze $
  $Date: 2006/01/16 12:19:32 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('News')) {
	$content = 'ERROR';
	return trigger_error('News module is inactive', E_USER_WARNING);
}

global $userinfo, $prefix, $multilingual, $currentlang, $db;

$content = '';

$querylang = ($multilingual) ? "AND (alanguage='$currentlang' OR alanguage='')" : '';
$result = $db->sql_query('SELECT sid, title FROM '.$prefix.'_stories WHERE time > '.(gmtime()-86400).' '.$querylang.' ORDER BY counter DESC LIMIT 0,1');
list($fsid, $ftitle) = $db->sql_fetchrow($result);
if (!$fsid && !$ftitle) {
	$result = $db->sql_query("SELECT sid, title FROM ".$prefix."_stories ORDER BY sid DESC LIMIT 0,1");
	list($fsid, $ftitle) = $db->sql_fetchrow($result);
	$content .= "<a href=\"".getlink("News&amp;file=article&amp;sid=$fsid")."\">$ftitle</a>";
} else {
	$content .= _BIGSTORY."<br /><br />";
	$content .= "<a href=\"".getlink("News&amp;file=article&amp;sid=$fsid")."\">$ftitle</a>";
}
