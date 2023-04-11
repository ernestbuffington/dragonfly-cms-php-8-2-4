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

$querylang = (Dragonfly::getKernel()->L10N->multilingual ? "AND (alanguage='$currentlang' OR alanguage='')" : '');
$content = '<table>';

$result = $db->query("SELECT sid, title, comments, counter FROM {$db->TBL->stories} WHERE ptime<=".time()." $querylang ORDER BY sid DESC LIMIT 5");
while (list($sid, $ntitle, $comtotal, $counter) = $result->fetch_row()) {
	$content .= '<tr><td align="left">
	<a href="'.htmlspecialchars(URL::index('News&file=article&sid='.$sid)).'">'.$ntitle.'</a>
	</td><td align="right">
	[ '.$comtotal.' '._COMMENTS.' - '.$counter.' '._READS.' ]
	</td></tr>';
}

$content .= '</table><br />
<div style="text-align:center;">
[ <a href="'.URL::index('News').'">'._MORENEWS.'</a> ]
</div>';
