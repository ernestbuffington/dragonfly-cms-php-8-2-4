<?php 
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-CPG-scroll-least_view_pictures.php,v $
  $Revision: 9.7 $
  $Author: phoenix $
  $Date: 2006/08/17 14:44:09 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $prefix, $db, $CONFIG, $cpg_dir, $cpg_prefix;
$cpg_dir = 'coppermine';

if (!is_active($cpg_dir)) {
	$content = 'ERROR';
	return trigger_error($cpg_dir.' module is inactive', E_USER_WARNING);
}

$cpg_block = true;
require("modules/" . $cpg_dir . "/include/load.inc");
$cpg_block = false;
global $ALBUM_SET;
// $length = $CONFIG['thumbcols']; //number of thumbs
$length = 25; //number of thumbs
$title_length = 12; //length of title below thumb 
// marquee info at http://www.faqs.org/docs/htmltut/_MARQUEE.html
$content = '<p align="center"><a name="scroller"></a><marquee loop="1" behavior="scroll" direction="up" height="150" scrollamount="1" scrolldelay="1" onmouseover=\'this.stop()\' onmouseout=\'this.start()\'><center>';

//END USER DEFINABLES
$thumb_title = $views_title = '';

$result = $db->sql_query("SELECT a.aid, pid, filepath, filename, hits, p.title FROM ".$cpg_prefix."pictures AS p INNER JOIN ".$cpg_prefix."albums AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE approved=1 GROUP BY pid ORDER BY hits ASC LIMIT $length");

while ($row = $db->sql_fetchrow($result)) {
	if ($CONFIG['seo_alts'] == 0) {
		$thumb_title = $row['filename'];
	} else {
		if ($row['title'] != '') {
			$thumb_title = $row['title'];
		} else {
			$thumb_title = substr($row['filename'], 0, -4);
		} 
	} 
	truncate_stringblocks($thumb_title, $title_length);
	$views_title = sprintf($lang_get_pic_data['n_views'], $row['hits']);
	$content .= '<a href="' . getlink($cpg_dir . '&amp;file=displayimage&amp;album='.$row['aid'].'&amp;pos=' . $row["pid"]) . '"><img src="' . get_pic_url($row, 'thumb') . '" border="0" alt="' . $thumb_title . '" title="' . $thumb_title . '" /><br />' . $views_title . '</a><br /><br />';
} 

$content .= '</marquee></p><p align="center"><a href="' . getlink($cpg_dir) . '">' . _coppermineLANG . '</a></p>';
