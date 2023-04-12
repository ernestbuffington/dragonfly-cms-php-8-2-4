<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-CPG-center-Top_rate_pictures.php,v $
  $Revision: 9.8 $
  $Author: djmaze $
  $Date: 2006/01/16 12:19:32 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $prefix, $db, $CONFIG, $cpg_dir, $lang_get_pic_data;

$cpg_dir = 'coppermine';

if (!is_active($cpg_dir)) {
	$content = 'ERROR';
	return trigger_error($cpg_dir.' module is inactive', E_USER_WARNING);
}

$cpg_block = true;
require("modules/".$cpg_dir."/include/load.inc");
$cpg_block = false; 
$limit=$CONFIG['thumbcols']; //number of thumbs
// $limit = 5; //number of pictures
$content = '';
// END USER DEFINEABLES
$result = $db->sql_query("SELECT pid, filepath, filename, p.aid, pic_rating, p.votes, p.title FROM ".$cpg_prefix."pictures AS p INNER JOIN ".$cpg_prefix."albums AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE approved=1 AND p.votes >= '{$CONFIG['min_votes_for_rating']}' GROUP BY pid ORDER BY ROUND((pic_rating+1)/2000) DESC, p.votes DESC LIMIT $limit");
$content .= '<br /><table width="100%" cols="'.$limit.'" border="0" cellpadding="0" cellspacing="0" align="center"><tr align="center">';
$pic = 0;
while ($row = $db->sql_fetchrow($result)) {
	if (defined('THEME_HAS_RATING_GRAPHICS')) {
		$theme_prefix = $CONFIG['theme'].'/';
	} else {
		$theme_prefix = '';
	} 
	$caption = '<img src="' . $CPG_M_DIR . '/' . $theme_prefix . 'images/rating' . round($row["pic_rating"] / 2000) . '.gif" align="center" border="0">' . '<br />' . sprintf($lang_get_pic_data['n_votes'], $row['votes']) . '<br />';
	if ($CONFIG['seo_alts'] == 0) {
   $thumb_title = $row['filename'];
	} else {
		if ($row['title'] != '') {
			$thumb_title = $row['title'];
		} else {
			$thumb_title = substr($row['filename'], 0, -4);
		} 
	} 
	$content .= '<td align="center" valign="baseline"><a href="' . getlink($cpg_dir . '&amp;file=displayimage&amp;meta=toprated&amp;cat=0&amp;pos=' . $pic) . '"><img src="' . get_pic_url($row, 'thumb') . '" border="0" alt="' . $thumb_title . '" title="' . $thumb_title . '" /><br />' . $caption . '</a></td>';
	$pic++;
} 
$content .= '</tr><tr><td colspan="'.$limit.'" valign="baseline" align="center"><a href = "'. getlink($cpg_dir) . '">'._coppermineLANG.'</a></td></tr></table>';
