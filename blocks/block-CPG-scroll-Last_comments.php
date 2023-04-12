<?php 
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-CPG-scroll-Last_comments.php,v $
  $Revision: 9.10 $
  $Author: phoenix $
  $Date: 2007/05/01 02:27:36 $
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
require('modules/'.$cpg_dir.'/include/load.inc');
$cpg_block = false;
// $length = $CONFIG['thumbcols']; //number of thumbs
$length = 5; //number of thumbs
$title_length = 15; //length of title below thumb 
// marquee info at http://www.faqs.org/docs/htmltut/_MARQUEE.html
$body_length = 50; //length of body of comment to show
$auth_length = 10; //length of author name to show
$content = '<a name="scroller"></a><marquee loop="1" behavior="scroll" direction="up" height="150" scrollamount="1" scrolldelay="1" onmouseover=\'this.stop()\' onmouseout=\'this.start()\'><p style="text-align:center;">'; 
// END USER DEFINEABLES

$result = $db->sql_query("SELECT p.pid, p.title, filepath, filename, msg_author, msg_date, msg_body FROM (".$cpg_prefix."comments as c, ".$cpg_prefix."pictures AS p) INNER JOIN ".$cpg_prefix."albums AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE c.pid=p.pid AND approved=1 ORDER BY msg_date DESC LIMIT $length");
$pic = 0;
$thumb_title = $date = $author = $messagebody = '';
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
	$date = formatDateTime($row['msg_date'], LASTCOM_DATE_FMT);
	$author = $row["msg_author"];
	$messagebody = $row["msg_body"];
	$content .= '<a href="'.getlink($cpg_dir. '&amp;file=displayimage&amp;meta=lastcom&amp;cat=0&amp;pos='.$pic).'"><img src="'.get_pic_url($row, 'thumb').'" style="border:0;" alt="'.$thumb_title.'" title="'.$thumb_title.'" /><br />'.truncate_stringblocks($author, $auth_length).'</a> <br />'.truncate_stringblocks($messagebody, $body_length).'<br />('.$date.')<br /><br />';
	$pic++;
} 
$content .= '</p></marquee><p style="text-align:center;"><a href="'.getlink($cpg_dir).'">'._coppermineLANG.'</a></p>';
