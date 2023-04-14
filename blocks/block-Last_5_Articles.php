<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Last_5_Articles.php,v $
  $Revision: 9.6 $
  $Author: phoenix $
  $Date: 2007/08/30 04:54:29 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('News')) {
	$content = 'ERROR';
	return trigger_error('News module is inactive', E_USER_WARNING);
}

global $currentlang, $db, $multilingual, $prefix;

$querylang = ($multilingual) ? "WHERE (alanguage='$currentlang' OR alanguage='')" : '';
$content = '<table width="100%" border="0">';

$result = $db->sql_query("SELECT sid, title, comments, counter FROM ".$prefix."_stories $querylang ORDER BY sid DESC LIMIT 0,5");
while (list($sid, $ntitle, $comtotal, $counter) = $db->sql_fetchrow($result)) {
	$content .= '<tr><td align="left">
	<b>&#8226;</b>&nbsp;<a href="'.getlink('News&amp;file=article&amp;sid='.$sid).'">'.$ntitle.'</a>
	</td><td align="right">
	[ '.$comtotal.' '._COMMENTS.' - '.$counter.' '._READS.' ]
	</td></tr>';
}

$content .= '</table><br />
<div style="text-align:center;">
[ <a href="'.getlink('News').'">'._MORENEWS.'</a> ]
</div>';
