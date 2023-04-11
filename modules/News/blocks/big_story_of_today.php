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

$SQL = \Dragonfly::getKernel()->SQL;
$L10N = \Dragonfly::getKernel()->L10N;

$content = '';

$querylang = ($L10N->multilingual ? "AND (alanguage='{$L10N->lng}' OR alanguage='')" : '');
$result = $SQL->query('SELECT sid, title FROM '.$SQL->TBL->stories.' WHERE ptime<='.time().' AND ptime>'.mktime(0,0,0).' '.$querylang.' ORDER BY counter DESC LIMIT 1');
if ($result->num_rows) {
	$content .= _BIGSTORY."<br /><br />";
} else {
	$result = $SQL->query("SELECT sid, title FROM {$SQL->TBL->stories} WHERE ptime<=".time()." ORDER BY sid DESC LIMIT 1");
}
list($fsid, $ftitle) = $result->fetch_row();
$content .= "<a href=\"".URL::index("News&file=article&sid=$fsid")."\">$ftitle</a>";
