<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

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
$limit = 4; //number of thumbs
$result = $db->query("SELECT p.pid, filepath, filename,p.title, msg_author, msg_date, msg_body
	FROM ({$CPG->config['TABLE_COMMENTS']} as c, {$CPG->config['TABLE_PICTURES']} AS p)
	INNER JOIN {$CPG->config['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND {$vis_groups})
	WHERE c.pid=p.pid AND approved=1 ORDER BY msg_date DESC LIMIT {$limit}");
$pic = 0;
$content = '';
while ($row = $result->fetch_assoc()) {
	if ($CPG->config['seo_alts'] == 0) {
		$thumb_title = $row['filename'];
	} else if ($row['title']) {
		$thumb_title = $row['title'];
	} else {
		$thumb_title = substr($row['filename'], 0, -4);
	}
	$date = \Dragonfly::getKernel()->L10N->strftime(LASTCOM_DATE_FMT, $row['msg_date']);
	$content .= '<p style="text-align:center;"><a href="'.htmlspecialchars($CPG->getImageMetaUrl('lastcom',$pic)).'"><img src="'.$CPG->getPicSrc($row, 'thumb').'" alt="'.$thumb_title.'" title="'.$thumb_title.'" /><br />'.Coppermine::truncateString($row['msg_author'], 10).'</a> <br />'.Coppermine::truncateString($row['msg_body'], 20).'<br />('.$date.')';
	++$pic;
}
$content .= '<br /><br /><a href="'.URL::index($cpg_dir).'">'._coppermineLANG.'</a></p>';
