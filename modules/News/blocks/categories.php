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

global $cat, $prefix, $MAIN_CFG, $currentlang, $db;
$result = $db->query('SELECT catid, title FROM '.$prefix.'_stories_cat ORDER BY title');
$numrows = $result->num_rows;

$content = '';
if ($numrows == 0) {
	return;
} else {
	$a = 0;
	$querylang = (Dragonfly::getKernel()->L10N->multilingual ? "AND (alanguage='$currentlang' OR alanguage='')" : '');
	while (list($catid, $ntitle) = $result->fetch_row()) {
		$numrows = $db->count('stories', "catid='$catid' $querylang LIMIT 1");
		if ($numrows > 0) {
			if ($cat == 0 && !$a) {
				$content .= "<strong>"._ALLCATEGORIES."</strong><br />";
				$a = 1;
			} elseif ($cat != 0 && !$a) {
				$content .= "<a href=\"".URL::index("News")."\">"._ALLCATEGORIES."</a><br />";
				$a = 1;
			}
			if ($cat == $catid) {
				$content .= "<strong>$ntitle</strong><br />";
			} else {
				$content .= "<a href=\"".htmlspecialchars(URL::index("News&catid=$catid"))."\">$ntitle</a><br />";
			}
		}
	}
}
