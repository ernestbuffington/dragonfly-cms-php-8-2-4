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
global $db;

$cpg_dir = basename(dirname(__DIR__));
$CPG = \Coppermine::getInstance($cpg_dir);
$USER_DATA = \Coppermine::getCurrentUserData();
$vis_groups = can_admin($cpg_dir) ? '' : " AND visibility IN (0,{$USER_DATA['GROUPS']})";
// $limit=$CPG->config['thumbcols']; //table cols
$limit = 5; //number of thumbs
$result = $db->query("SELECT pid, filepath, filename, hits, p.title
	FROM {$CPG->config['TABLE_PICTURES']} AS p
	INNER JOIN {$CPG->config['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND {$vis_groups})
	WHERE approved=1 ORDER BY hits DESC LIMIT {$limit}");
$pic = 0;
$content = '<p style="text-align:center;"><a name="scroller"></a><marquee loop="1" behavior="scroll" direction="left" height="135" width="80%" scrollamount="1" scrolldelay="1" onmouseover=\'this.stop()\' onmouseout=\'this.start()\'><table><tr align="center">';
while ($row = $result->fetch_assoc()) {
	if ($CPG->config['seo_alts'] == 0) {
		$thumb_title = $row['filename'];
	} else if ($row['title']) {
		$thumb_title = $row['title'];
	} else {
		$thumb_title = substr($row['filename'], 0, -4);
	}
	Coppermine::truncateString($thumb_title, 20);
	$view_title = \Dragonfly::getKernel()->L10N->plural($row['hits'],'%d views');
	$content .= '<td align="center" valign="baseline"><a href="' . htmlspecialchars($CPG->getImageMetaUrl('topn', $pic)) . '"><img src="' . $CPG->getPicSrc($row, 'thumb') . '" alt="' . $thumb_title . '" title="' . $thumb_title . '" /><br />' . Coppermine::truncateString($view_title, 20) . '</a>&nbsp;&nbsp;</td>';
	++$pic;
}

$content .= '</tr></table></marquee></p><p style="text-align:center;"><table><tr><td valign="baseline" align="center"><a href="' . URL::index($cpg_dir) . '">' . _coppermineLANG . '</a><br /></td></tr></table></p>';
