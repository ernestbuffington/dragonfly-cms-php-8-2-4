<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Last_Referers.php,v $
  $Revision: 9.6 $
  $Author: phoenix $
  $Date: 2007/05/31 03:26:56 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $prefix;

# how many referers should the block display?
$ref = 10;
$a = 1;
$content = '';

$result = $db->sql_query("SELECT url FROM ".$prefix."_referer ORDER BY lasttime DESC LIMIT 0,$ref");
$total = $db->sql_numrows($result);
if ($total < 1) {
	$content = 'ERROR';
	return trigger_error(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_HTTPREFERERS)), E_USER_WARNING);
}

while (list($url) = $db->sql_fetchrow($result)) {
	$url2 = preg_replace('#_#m', ' ', $url);
	
	if (strlen($url2) > 18) {
		$url2 = substr($url, 0, 20);
		$url2 .= '..';
	}
	
	$content .= "$a:&nbsp;\n"
			   ."<a href=\"$url\" target=\"_blank\">$url2</a>\n"
			   ."<br />\n";
	$a++;
}

if (can_admin()) {
	$content .= "<br />\n"
			   ."<div style=\"text-align:center;\">\n"
			   ."$total "._HTTPREFERERS."\n"
			   ."<br /><br />\n"
			   ."[ <a href=\"".adminlink('referers&amp;del=all')."\">"._DELETE."</a> ]\n"
			   ."</div>\n";
}
$db->sql_freeresult($result);
