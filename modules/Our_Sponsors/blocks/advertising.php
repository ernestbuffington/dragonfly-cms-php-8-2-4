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

global $db, $MAIN_CFG;

list($numrows) = $db->uFetchRow("SELECT COUNT(*) FROM {$db->TBL->banner} WHERE type='1' AND active='1'");
if ($numrows < 1) { return; }

if ($numrows > 1) {
	$numrows = $numrows-1;
	mt_srand((double)microtime()*1000000);
	$numrows = random_int(0, $numrows);
} else {
	$numrows = 0;
}

$result = $db->query("SELECT * FROM {$db->TBL->banner} WHERE type='1' AND active='1' LIMIT 1");
$row = $db->sql_fetchrow($result);

if (!is_admin()) {
	$db->query("UPDATE {$db->TBL->banner} SET impmade=".$row['impmade']."+1 WHERE bid='$row[bid]'");
	$row['impmade']++;
}

/* Check if this impression is the last one and print the banner */
if (($row['imptotal'] <= $row['impmade']) && $row['imptotal'] != 0) {
	Dragonfly::getKernel()->L10N->load('Our_Sponsors');
	$db->query("UPDATE {$db->TBL->banner} SET active='0' WHERE bid='".$row['bid']."'");
	$sql3 = "SELECT	 username, user_email FROM {$db->TBL->users} WHERE user_id = '".$row['cid']."'";
		$result3 = $db->query($sql3);
		$row3 = $db->sql_fetchrow($result3);
		$to = $row3['user_email'];
		$to_name = $row3['username'];
			$message = _HELLO." ".$row3['username'].":\n\n";
			$message .= _THISISAUTOMATED."\n\n";
			$message .= _THERESULTS."\n\n";
			$message .=	 BANNER_ID.": ".$row['bid']."\n";
			$message .= _TOTALIMPRESSIONS." ".$row['imptotal']."\n";
			$message .= _CLICKSRECEIVED." ".$row['clicks']."\n";
			$message .= _IMAGEURL.": ".$row['imageurl']."\n";
			$message .= _TEXT_TITLE.": ".$row['text_title']."\n\n";
			$message .= _HOPEYOULIKED."\n\n";
			$message .= _THANKSUPPORT."\n\n";
			$message .= "- {$MAIN_CFG['global']['sitename']} "._TEAM."\n";
			$message .= $MAIN_CFG['global']['nukeurl'];
			$subject = "{$MAIN_CFG['global']['sitename']}: "._BANNERSFINNISHED;
			\Dragonfly\Email::send($mailer_message, $subject, $message, $to, $to_name);
	  $result3->free();
}
// If Text Banner
if ($row['textban']==1) {
	$content = "<table width=\"".$row['text_width']."\" bgcolor=\"#".$row['text_bg']."\" align=\"center\"><tr>\n"
	."<td valign=\"middle\" height=\"".$row['text_height']."\">\n"
	."<div align=\"center\"><a href=\"banners.php?op=click&amp;bid=".$row['bid']."\" alt=\"".$row['clickurl']."\" title=\"".$row['clickurl']."\" style=\"color:#".$row['text_clr']."\" onclick=\"window.open('banners.php?op=click&amp;bid=".$row['bid']."','textad','toolbar=yes,menubar=yes,scrollbars=yes');return false\" target=\"_blank\">".$row['text_title']."</a></div>\n"
	."</td>\n"
	."</tr></table>";
}
else {
	$content = "<div align=\"center\"><a href=\"banners.php?op=click&amp;bid=".$row['bid']."\"><img src=\"".$row['imageurl']."\" alt=\"".$row['alttext']."\" title='".$row['alttext']."'/></a></div>";
}
