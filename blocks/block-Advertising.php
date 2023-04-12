<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-Advertising.php,v $
  $Revision: 9.5 $
  $Author: djmaze $
  $Date: 2006/01/16 12:19:32 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $prefix, $user_prefix, $db, $nukeurl, $sitename;

list($numrows) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_banner WHERE type='1' AND active='1'", SQL_NUM);
if ($numrows < 1) { return; }

if ($numrows > 1) {
	$numrows = $numrows-1;
	mt_srand((double)microtime()*1000000);
	$numrows = mt_rand(0, $numrows);
} else {
	$numrows = 0;
}

$result = $db->sql_query("SELECT * FROM ".$prefix."_banner WHERE type='1' AND active='1' LIMIT $numrows,1");
$row = $db->sql_fetchrow($result);
	
if (!is_admin()) {
	$db->sql_query("UPDATE ".$prefix."_banner SET impmade=".$row['impmade']."+1 WHERE bid='$row[bid]'");
	$row['impmade']++;
}

/* Check if this impression is the last one and print the banner */
if (($row['imptotal'] <= $row['impmade']) && $row['imptotal'] != 0) {
	get_lang('Our_Sponsors');
	$db->sql_query("UPDATE ".$prefix."_banner SET active='0' WHERE bid='".$row['bid']."'");
	$sql3 = "SELECT	 username, user_email FROM ".$user_prefix."_users WHERE user_id = '".$row['cid']."'";
		$result3 = $db->sql_query($sql3);
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
			$message .= "- $sitename "._TEAM."\n";
			$message .= "$nukeurl";
			$subject = "$sitename: "._BANNERSFINNISHED;
			send_mail($mailer_message, $message,0, $subject, $to, $to_name);
	  $db->sql_freeresult($result3);
}
// If Text Banner
if ($row['textban']==1) {
	$content = "<table width=\"".$row['text_width']."\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" bgcolor=\"#".$row['text_bg']."\" align=\"center\"><tr>\n"
	."<td valign=\"middle\" height=\"".$row['text_height']."\">\n"
	."<div align=\"center\"><a href=\"banners.php?op=click&amp;bid=".$row['bid']."\" alt=\"".$row['clickurl']."\" title=\"".$row['clickurl']."\" style=\"color:#".$row['text_clr']."\" onclick=\"window.open('banners.php?op=click&amp;bid=".$row['bid']."','textad','toolbar=yes,menubar=yes,scrollbars=yes');return false\" target=\"_blank\">".$row['text_title']."</a></div>\n"
	."</td>\n"
	."</tr></table>";
}
else {
	$content = "<div align=\"center\"><a href=\"banners.php?op=click&amp;bid=".$row['bid']."\"><img src=\"".$row['imageurl']."\" alt=\"".$row['alttext']."\" title='".$row['alttext']."' border=\"0\" /></a></div>";
}
