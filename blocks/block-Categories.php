<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-Categories.php,v $
  $Revision: 9.7 $
  $Author: phoenix $
  $Date: 2007/09/12 02:33:13 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('News')) {
	$content = 'ERROR';
	return trigger_error('News module is inactive', E_USER_WARNING);
}

global $cat, $prefix, $multilingual, $currentlang, $db;
$result = $db->sql_query('SELECT catid, title FROM '.$prefix.'_stories_cat ORDER BY title');
$numrows = $db->sql_numrows($result);

$content = '';
if ($numrows == 0) {
	return;
} else {
	$a = 0;
	$querylang = ($multilingual) ? "AND (alanguage='$currentlang' OR alanguage='')" : '';
	while (list($catid, $ntitle) = $db->sql_fetchrow($result)) {
		$numrows = $db->sql_count($prefix."_stories", "catid='$catid' $querylang LIMIT 0,1");
		if ($numrows > 0) {
			if ($cat == 0 && !$a) {
				$content .= "<b>&#8226;</b>&nbsp;<strong>"._ALLCATEGORIES."</strong><br />";
				$a = 1;
			} elseif ($cat != 0 && !$a) {
				$content .= "<b>&#8226;</b>&nbsp;<a href=\"".getlink("News")."\">"._ALLCATEGORIES."</a><br />";
				$a = 1;
			}
			if ($cat == $catid) {
				$content .= "<b>&#8226;</b>&nbsp;<strong>$ntitle</strong><br />";
			} else {
				$content .= "<strong>&middot;</strong>&nbsp;<a href=\"".getlink("News&amp;catid=$catid")."\">$ntitle</a><br />";
			}
		}
	}
}
