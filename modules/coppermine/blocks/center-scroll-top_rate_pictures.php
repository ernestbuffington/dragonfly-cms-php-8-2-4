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
// $limit=$CPG->config['thumbcols']; //number of thumbs
$limit = 10; //number of pictures
$result = $db->query("SELECT pid, filepath, filename, p.aid, pic_rating, p.votes, p.title
	FROM {$CPG->config['TABLE_PICTURES']} AS p
	INNER JOIN {$CPG->config['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND {$vis_groups})
	WHERE approved=1 AND p.votes >= '{$CPG->config['min_votes_for_rating']}' ORDER BY ROUND((pic_rating+1)/2000) DESC, p.votes DESC LIMIT $limit");
$pic = 0;
$content = '<p style="text-align:center;"><a name="scroller"></a><marquee loop="1" behavior="scroll" direction="left" height="135" width="80%" scrollamount="1" scrolldelay="1" onmouseover=\'this.stop()\' onmouseout=\'this.start()\'><table><tr>';
while ($row = $result->fetch_assoc()) {
	$caption = \Coppermine::getRatingStars($row['pic_rating']);
	$caption .= "<br />" . round($row['pic_rating'] / 2000, 2) . "/5 ";
	$caption .= '('.\Dragonfly::getKernel()->L10N->plural($row['votes'],'%d votes').')';

	if ($CPG->config['seo_alts'] == 0) {
		$thumb_title = $row['filename'];
	} else if ($row['title']) {
		$thumb_title = $row['title'];
	} else {
		$thumb_title = substr($row['filename'], 0, -4);
	}
	$content .= '<td align="center" valign="baseline"><a href="' . htmlspecialchars($CPG->getImageMetaUrl('toprated', $pic)) . '"><img src="' . $CPG->getPicSrc($row, 'thumb') . '" alt="' . $thumb_title . '" title="' . $thumb_title . '" /><br />' . $caption . '</a>&nbsp;&nbsp;</td>';
	++$pic;
}
$content .= '</tr></table></marquee><table><tr align="center"><td valign="baseline"><a href="' . URL::index($cpg_dir) . '">' . _coppermineLANG . '</a><br /></td></tr></table></p>';
