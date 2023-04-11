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

/* Applied rules:
 * RandomFunctionRector
 */
 
if (!defined('CPG_NUKE')) { exit; }
global $db;

$cpg_dir = basename(dirname(__DIR__));
$CPG = \Coppermine::getInstance($cpg_dir);
$USER_DATA = \Coppermine::getCurrentUserData();
$vis_groups = can_admin($cpg_dir) ? '' : " AND visibility IN (0,{$USER_DATA['GROUPS']})";
$limit = $CPG->config['thumbcols']; //number of thumbs
//$limit = 4; //number of thumbs
list($pic_count) = $db->uFetchRow("SELECT COUNT(*)
	FROM {$CPG->config['TABLE_PICTURES']} AS p
	INNER JOIN {$CPG->config['TABLE_ALBUMS']} AS a ON (p.aid = a.aid {$vis_groups})
	WHERE approved=1");
// if we have more than 1000 pictures, we limit the number of picture returned
// by the SELECT statement as ORDER BY RAND() is time consuming
if ($pic_count > 1000) {
	list($total_count) = $db->uFetchRow("SELECT COUNT(*) FROM {$CPG->config['TABLE_PICTURES']} WHERE approved = 1");
	$granularity = floor($total_count / 1000);
	$cor_gran = ceil($total_count / $pic_count);
	$random_num_set = array();
	for ($i = 0; $i < $cor_gran; ++$i) {
		$random_num_set[] = random_int(0, $granularity);
	}
	$random_num_set = implode(',', $random_num_set);
	$randpos = " AND randpos IN ({$random_num_set})";
} else {
	$randpos = '';
}
$result = $db->query("SELECT pid, filepath, filename, p.aid, p.title
	FROM {$CPG->config['TABLE_PICTURES']} AS p
	INNER JOIN {$CPG->config['TABLE_ALBUMS']} AS a ON (p.aid = a.aid {$vis_groups})
	WHERE approved=1 {$randpos} ORDER BY RAND() DESC LIMIT {$limit}");
$content = '<table style="margin:auto;" cols="' . $limit . '"><tr>';
while ($row = $result->fetch_assoc()) {
	if ($CPG->config['seo_alts'] == 0) {
		$thumb_title = $row['filename'];
	} else if ($row['title']) {
		$thumb_title = $row['title'];
	} else {
		$thumb_title = substr($row['filename'], 0, -4);
	}
	$content .= '<td align="center" valign="baseline"><a href="' . htmlspecialchars($CPG->getImageUrl($row)) . '"><img src="' . $CPG->getPicSrc($row, 'thumb') . '" alt="' . $thumb_title . '" title="' . $thumb_title . '" /><br />' . $thumb_title . '</a></td>';
}
$content .= '</tr><tr align="center"><td colspan="' . $limit . '" valign="baseline"><a href="' . URL::index($cpg_dir) . '">' . _coppermineLANG . '</a></td></tr></table>';
