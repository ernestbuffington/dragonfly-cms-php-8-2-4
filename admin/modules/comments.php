<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/comments.php,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:57 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
$pagetitle .= ' '._BC_DELIM.' '._REMOVECOMMENTS;

function removeSubComments($tid, $table) {
	global $prefix, $db;
	$result = $db->sql_query("SELECT tid FROM ".$prefix."_$table WHERE pid='$tid'");
	$numrows = $db->sql_numrows($result);
	if ($numrows>0) {
		while (list($ctid) = $db->sql_fetchrow($result)) {
			$numrows += removeSubComments($ctid, $table);
		}
		$db->sql_query("DELETE FROM ".$prefix."_$table WHERE pid='$tid'");
	}
	$db->sql_freeresult($result);
	return $numrows;
}

if (isset($_GET['del']) && can_admin('news')) {
	$tid = intval($_GET['del']);
	if (isset($_GET['ok'])) {
		$result = $db->sql_query("SELECT sid FROM ".$prefix."_comments WHERE tid='$tid'");
		list($sid) = $db->sql_fetchrow($result);
		$count = 1;
		$count += removeSubComments($tid, 'comments');
		$db->sql_query("UPDATE ".$prefix."_stories SET comments=comments-$count WHERE sid=$sid");
		$db->sql_query("DELETE FROM ".$prefix."_comments WHERE tid='$tid'");
		url_redirect(getlink("News&file=article&sid=$sid"));
	} else {
		require('header.php');
		GraphicAdmin('_AMENU3');
		OpenTable();
		echo '<div align="center" class="title">'._REMOVECOMMENTS.'</div>';
		CloseTable();
		OpenTable();
		echo '<center>'._SURETODELCOMMENTS.'<br /><br />[ <a href="javascript:history.go(-1)">'._NO.'</a> | <a href="'.adminlink("&amp;del=$tid&amp;ok=1").'">'._YES.'</a> ]</center>';
		CloseTable();
	}
}
else if (isset($_GET['polldel']) && can_admin('surveys')) {
	$tid = intval($_GET['polldel']);
	if (isset($_GET['ok'])) {
		$result = $db->sql_query("SELECT poll_id FROM ".$prefix."_pollcomments WHERE tid=$tid");
		list($poll_id) = $db->sql_fetchrow($result);
		removeSubComments($poll_id, 'pollcomments');
		$db->sql_query("DELETE FROM ".$prefix."_pollcomments WHERE tid='$tid'");
		url_redirect(getlink("Surveys&op=results&pollid=$poll_id"));
	} else {
		require('header.php');
		GraphicAdmin('_AMENU3');
		OpenTable();
		echo '<div align="center" class="title">'._REMOVECOMMENTS.'</div>';
		CloseTable();
		OpenTable();
		echo '<center>'._SURETODELCOMMENTS.'<br /><br />[ <a href="javascript:history.go(-1)">'._NO.'</a> | <a href="'.adminlink("&amp;polldel=$tid&amp;ok=1").'">'._YES.'</a> ]</center>';
		CloseTable();
	}
}
else { die('Access Denied'); }
