<?php 
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-CPG-center-scroll-Last_pictures_thumb.php,v $
  $Revision: 9.7 $
  $Author: djmaze $
  $Date: 2006/01/16 12:19:32 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $prefix, $db, $CONFIG, $cpg_dir;

$cpg_dir = 'coppermine';

if (!is_active($cpg_dir)) {
	$content = 'ERROR';
	return trigger_error($cpg_dir.' module is inactive', E_USER_WARNING);
}

$cpg_block = true;
require("modules/" . $cpg_dir . "/include/load.inc");
$cpg_block = false;

// $length=$CONFIG['thumbcols']; //number of thumbs
$length = 8; //number of thumbs
$maxlength = 20; // maximum length of name in block

// marquee info at http://www.faqs.org/docs/htmltut/_MARQUEE.html
$content = '<p align="center"><a name="scroller"></a><marquee loop="1" behavior="scroll" direction="left" height="135" width="80%" scrollamount="1" scrolldelay="1" onmouseover=\'this.stop()\' onmouseout=\'this.start()\'>';

//END USER DEFINABLES
$result = $db->sql_query("SELECT pid, filepath, filename, p.aid, p.title FROM ".$cpg_prefix."pictures AS p INNER JOIN ".$cpg_prefix."albums AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE approved=1 GROUP BY pid ORDER BY pid DESC LIMIT $length");

$content .= '<table width="100%" cols="' . $length . '" border="0" cellpadding="0" cellspacing="7" align="center"><tr align="center">';
$pic = 0;
$thumb_title = '';
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
	$content .= '<td align="center" valign="baseline"><a href="' . getlink($cpg_dir . '&amp;file=displayimage&amp;meta=lastup&amp;cat=0&amp;pos=' . $pic) . '"><img src="' . get_pic_url($row, 'thumb') . '" border="0" alt="' . $thumb_title . '" title="' . $thumb_title . '" /><br />' . truncate_stringblocks($thumb_title, $maxlength) . '</a>&nbsp;&nbsp;</td>';
	$pic++;
} 
$content .= '</tr></table></marquee></p><p align="center"><table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"><tr align="center"><td valign="baseline"><a href="' . getlink($cpg_dir) . '">' . _coppermineLANG . '</a><br /></td></tr></table></p>';
