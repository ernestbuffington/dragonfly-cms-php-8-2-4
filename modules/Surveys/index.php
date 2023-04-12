<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Surveys/index.php,v $
  $Revision: 9.10 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:29 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _SurveysLANG;

if(!isset($_POST['pollid']) && !isset($_GET['pollid'])) {
	$editing = '';
	require_once('header.php');
	OpenTable();

	OpenTable();
	echo '<div style="text-align:center;" class="title">'._PASTSURVEYS.'</div>';
	CloseTable();
	echo '<table border="0" cellpadding="8"><tr><td>';
	$querylang = '';
	if ($multilingual) { $querylang = "AND (planguage='$currentlang' OR planguage='')"; }
	$result = $db->sql_query("SELECT poll_id, poll_title, voters FROM ".$prefix."_poll_desc WHERE artid=0 $querylang ORDER BY time_stamp DESC");
	while(list($id, $title, $voters) = $db->sql_fetchrow($result)) {
		if (is_admin()) { $editing = ' - <a href="'.adminlink('Surveys&amp;mode=edit&amp;id='.$id).'">'._EDIT.'</a>'; }
		echo "<b>&#8226;</b>&nbsp;<a href=\"".getlink("&amp;pollid=$id")."\">$title</a> ";
		echo "(<a href=\"".getlink("&amp;op=results&amp;pollid=$id")."\">"._RESULTS."</a> - $voters "._LVOTES."$editing)<br />\n";
	}
	$db->sql_freeresult($result);
	echo '</td></tr></table><br />';

	$result = $db->sql_query("SELECT poll_id, poll_title, voters FROM ".$prefix."_poll_desc WHERE artid > 0 $querylang ORDER BY time_stamp DESC");
	if ($db->sql_numrows($result)) {
		OpenTable();
		echo '<div style="text-align:center;" class="title">'._SURVEYSATTACHED.'</div>';
		CloseTable();
		echo '<table border="0" cellpadding="8"><tr><td>';
		while(list($id, $title, $voters) = $db->sql_fetchrow($result)) {
			if (is_admin()) { $editing = ' - <a href="'.adminlink("Surveys&amp;mode=edit&amp;id=$id").'">'._EDIT.'</a>'; }
			echo '<b>&#8226;</b>&nbsp;<a href="'.getlink('&amp;pollid='.$id).'">'.$title.'</a> ';
			$res = $db->sql_query("SELECT sid, title FROM ".$prefix."_stories WHERE poll_id='$id'");
			list($sid, $title) = $db->sql_fetchrow($res);
			echo '(<a href="'.getlink('&amp;op=results&amp;pollid='.$id).'">'._RESULTS."</a> - $voters "._LVOTES."$editing)<br />\n"
			._ATTACHEDTOARTICLE.' <a href="'.getlink('News&amp;file=article&amp;sid='.$sid)."\">$title</a><br /><br />\n";
		}
		echo '</td></tr></table>';
	}
	$db->sql_freeresult($result);
	CloseTable();
	return;
} else {
	$poll_id = isset($_POST['pollid']) ? intval($_POST['pollid']) : intval($_GET['pollid']);
	$op = $_POST['op'] ?? $_GET['op'] ?? '';
}

require_once("modules/$module_name/comments.php");
if (isset($_POST['vote_id'])) {
	$past = gmtime()-86400*30; // 86400 is one day
	$db->sql_query('DELETE FROM '.$prefix."_poll_check WHERE time < $past");
	if (!pollVoted($poll_id)) {
		$ctime = gmtime();
		$db->sql_query('INSERT INTO '.$prefix."_poll_check (user_id, ip, time, poll_id) VALUES ('".$userinfo['user_id']."', ".$userinfo['user_ip'].", '$ctime', '$poll_id')");
		$db->sql_query('UPDATE '.$prefix."_poll_data SET option_count=option_count+1 WHERE poll_id='$poll_id' AND vote_id=".intval($_POST['vote_id']));
		$db->sql_query('UPDATE '.$prefix."_poll_desc SET voters=voters+1 WHERE poll_id='$poll_id'");
	}
	$forwarder = $_POST['forwarder'] ?? 0;
	if (strlen($forwarder<5)) $forwarder=getlink('&op=results&pollid='.$poll_id);
	url_redirect($forwarder);
}
elseif (isset($_POST['postreply'])) {
	// store the reply
	replyPost($poll_id);
}

require_once('header.php');
OpenTable();
if (isset($_GET['reply'])) {
	// reply to comment
	reply($poll_id);
}
elseif (isset($_POST['preview'])) {
	// Preview the reply before storage
	replyPreview($poll_id);
}
else if (isset($_GET['comment'])) {
	// Show comment X
	if (!isset($_GET['pid'])) {
		singlecomment(intval($_GET['comment']), $poll_id);
	} else {
		DisplayComments($poll_id, '', intval($_GET['pid']), intval($_GET['comment']));
	}
}
elseif ($op == 'results' && $poll_id > 0) {
	echo '<div align="center" class="title">'._CURRENTPOLLRESULTS.'</div>';
	CloseTable();
	echo '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tr><td width="70%" valign="top">';
	OpenTable();
	$title = pollResults($poll_id);
	CloseTable();
	echo '</td><td>&nbsp;</td><td width="30%" valign="top">';
	OpenTable();
	echo '<b>'._LAST5POLLS." $sitename</b><br /><br />";
	$resu = $db->sql_query("SELECT poll_id, poll_title, voters FROM ".$prefix."_poll_desc WHERE artid=0 AND poll_id<>$poll_id ORDER BY time_stamp DESC LIMIT 0,5");
	while (list($id, $ptitle, $votes) = $db->sql_fetchrow($resu)) {
		echo "<b>&#8226;</b>&nbsp;$ptitle ($votes "._LVOTES.")<br /><br />";
	}
	$db->sql_freeresult($resu);
	echo '<a href="'.getlink().'"><b>'._MOREPOLLS.'</b></a>';
	CloseTable();
	echo '</td></tr></table>';
	if ($MAIN_CFG['global']['pollcomm'] && $userinfo['umode'] != 'nocomments') {
		DisplayComments($poll_id, $title);
	}
}
elseif($poll_id != pollLatest()) {
	echo '<div align="center" class="option">'._SURVEY.'</div>';
	CloseTable();
	echo '<br /><br /><table border="0" align="center"><tr><td>';
	if (pollVoted($poll_id)) {
		pollResults($poll_id);
	} else {
	pollMain($poll_id);
	}
	echo '</td></tr></table>';
}
else {
	echo '<div align="center" class="option">'._CURRENTSURVEY.'</div>';
	CloseTable();
	echo '<br /><br /><table border="0" align="center"><tr><td>';
	if (pollVoted($poll_id)) {
		pollResults(pollLatest());
	} else {
	pollMain(pollLatest());
	}
	echo '</td></tr></table>';
}

/*********************************************************/
/* Functions											 */
/*********************************************************/

function pollMain($poll_id) {
	global $prefix, $db, $MAIN_CFG, $Blocks;
	if(!isset($poll_id)) $poll_id = 1;
	$boxContent = "<form action=\"".getlink()."\" method=\"post\" enctype=\"multipart/form-data\" accept-charset=\"utf-8\">";
	$boxContent .= "<input type=\"hidden\" name=\"pollid\" value=\"$poll_id\" />";
	$boxContent .= "<input type=\"hidden\" name=\"forwarder\" value=\"".getlink("&amp;op=results&amp;pollid=$poll_id")."\" />";
	list($poll_title, $voters) = $db->sql_ufetchrow("SELECT poll_title, voters FROM ".$prefix."_poll_desc WHERE poll_id='$poll_id'", SQL_NUM);
	$boxContent .= "<font class=\"content\"><b>$poll_title</b></font><br /><br />\n";
	$boxContent .= "<table border=\"0\" width=\"100%\">";
	$sum = 0;
	$result = $db->sql_query("SELECT option_text, vote_id, option_count FROM ".$prefix."_poll_data WHERE poll_id='$poll_id' AND option_text!='' ORDER BY vote_id");
	while ($row = $db->sql_fetchrow($result)) {
		$boxContent .= "<tr><td valign=\"top\"><input type=\"radio\" name=\"vote_id\" value=\"".$row['vote_id']."\" /></td><td width=\"100%\"><font class=\"content\">".$row['option_text']."</font></td></tr>\n";
		$sum += $row['option_count'];
	}
	$db->sql_freeresult($result);
	$boxContent .= "</table><br /><center><font class=\"content\"><input type=\"submit\" value=\""._VOTE."\" /></font><br />";
	$boxContent .= '<br /><font class="content"><a href="'.getlink('&amp;op=results&amp;pollid='.$poll_id).'"><b>'._RESULTS.'</b></a> <b>::</b> <a href="'.getlink().'"><b>'._POLLS.'</b></a><br />';
	$boxContent .= '<br />'._VOTES.": <b>$sum</b>";
	if ($MAIN_CFG['global']['pollcomm']) {
		list($numcom) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_pollcomments WHERE poll_id='$poll_id'", SQL_NUM);
		$boxContent .= '<br />'._PCOMMENTS." <b>$numcom</b>";
	}
	$boxContent .= "\n\n</font></center></form>\n\n";
	$block = array(
		'bid' => 10001,
		'bkey' => 'custom',
		'view' => 0,
		'side' => 'r',
		'title' => _SURVEY,
		'content' => $boxContent
	);
	$Blocks->preview = true;
	$Blocks->preview($block);
}

function pollLatest() {
	global $prefix, $multilingual, $currentlang, $db;
	$querylang = ($multilingual) ? "AND planguage='$currentlang' OR planguage=''" : '';
	$poll_id = $db->sql_ufetchrow('SELECT poll_id FROM '.$prefix."_poll_desc WHERE artid=0 $querylang ORDER BY poll_id DESC LIMIT 0,1", SQL_NUM);
	return $poll_id[0];
}

function pollVoted($poll_id) {
	global $prefix, $db, $userinfo;
	$result = $db->sql_query("SELECT anonymous FROM ".$prefix."_poll_desc WHERE poll_id='$poll_id' LIMIT 0,1");
	list($anonymous) = $db->sql_fetchrow($result);
	// if you are anonymous and no anonymous votes allowed, you can't vote
	if (!$anonymous && !is_user()) {
		return true;
	}
	// if limited by username, but anonymous is allowed, don't disallow multiple anonymous entries
	elseif ($anonymous && !is_user()) {
		return $db->sql_count($prefix.'_poll_check', "user_id='".$userinfo['user_id']."' AND ip=".$userinfo['user_ip']." AND poll_id='$poll_id'");//only one anonymous per ip
	} else {
		return $db->sql_count($prefix.'_poll_check', "user_id='".$userinfo['user_id']."' AND poll_id='$poll_id'");
	}
}

function pollResults($poll_id) {
	$salto = null;
 global $db, $prefix, $ThemeSel;
	if (!isset($poll_id)) $poll_id = 1;
	$holdtitle = $db->sql_ufetchrow('SELECT poll_title, artid FROM '.$prefix."_poll_desc WHERE poll_id='$poll_id'", SQL_NUM);
	echo "<b>$holdtitle[0]</b><br /><br />";

	list($sum) = $db->sql_ufetchrow('SELECT SUM(option_count) FROM '.$prefix."_poll_data WHERE poll_id='$poll_id'", SQL_NUM);
	echo '<table border="0">';

	/* cycle through all options */
	$result = $db->sql_query("SELECT option_text, option_count FROM ".$prefix."_poll_data WHERE poll_id='$poll_id' AND option_text!='' ORDER BY vote_id");
	while(list($option_text, $option_count) = $db->sql_fetchrow($result)) {
		echo "<tr><td>$option_text</td>";
		$percent = 0;
		if($sum) {
			$percent = 100 * $option_count / $sum;
		}
		echo '<td>';
		$percentInt = (int)$percent * 4 * 1;
		$percent2 = (int)$percent;
		if (file_exists("themes/$ThemeSel/images/survey_leftbar.gif") && file_exists("themes/$ThemeSel/images/survey_mainbar.gif") && file_exists("themes/$ThemeSel/images/survey_rightbar.gif")) {
			$l_size = getimagesize("themes/$ThemeSel/images/survey_leftbar.gif");
			$m_size = getimagesize("themes/$ThemeSel/images/survey_mainbar.gif");
			$r_size = getimagesize("themes/$ThemeSel/images/survey_rightbar.gif");
			$leftbar = 'survey_leftbar.gif';
			$mainbar = 'survey_mainbar.gif';
			$rightbar = 'survey_rightbar.gif';
		} else {
			$l_size = getimagesize("themes/$ThemeSel/images/leftbar.gif");
			$m_size = getimagesize("themes/$ThemeSel/images/mainbar.gif");
			$r_size = getimagesize("themes/$ThemeSel/images/rightbar.gif");
			$leftbar = 'leftbar.gif';
			$mainbar = 'mainbar.gif';
			$rightbar = 'rightbar.gif';
		}
		if (file_exists("themes/$ThemeSel/images/survey_mainbar_d.gif")) {
			$m1_size = getimagesize("themes/$ThemeSel/images/survey_mainbar_d.gif");
			$mainbar_d = 'survey_mainbar_d.gif';
			if ($percent2 > 0 && $percent2 <= 23) {
				$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$percentInt\" />";
			} elseif ($percent2 > 24 && $percent2 < 50) {
				$a = $percentInt - 100;
				$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$a\" />";
			} elseif ($percent2 > 49 && $percent2 < 75) {
				$a = $percentInt - 200;
				$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$a\" />";
			} elseif ($percent2 > 74 && $percent2 <= 100) {
				$a = $percentInt - 300;
				$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\" />"
						."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$a\" />";
			}
		}
		if ($percent > 0) {
			echo "<img src=\"themes/$ThemeSel/images/$leftbar\" height=\"$l_size[1]\" width=\"$l_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
			if (file_exists("themes/$ThemeSel/images/survey_mainbar_d.gif")) {
				echo "$salto";
			} else {
				echo "<img src=\"themes/$ThemeSel/images/$mainbar\" height=\"$m_size[1]\" width=\"$percentInt\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
			}
			echo "<img src=\"themes/$ThemeSel/images/$rightbar\" height=\"$r_size[1]\" width=\"$r_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
		} else {
			echo "<img src=\"themes/$ThemeSel/images/$leftbar\" height=\"$l_size[1]\" width=\"$l_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
			if (!file_exists("themes/$ThemeSel/images/survey_mainbar_d.gif")) {
				echo "<img src=\"themes/$ThemeSel/images/$mainbar\" height=\"$m_size[1]\" width=\"$m_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
			}
			echo "<img src=\"themes/$ThemeSel/images/$rightbar\" height=\"$r_size[1]\" width=\"$r_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
		}
		printf(" %.2f%% (%s)", $percent, $option_count);
		echo "</td></tr>";
	}
	$db->sql_freeresult($result);
	echo '</table><br />
	<center><font class="content">'._TOTALVOTES.' <b>'.$sum.'</b>
	<br /><br />';
	$article = '';
	if ($holdtitle[1] > 0) { $article = "<br /><br />"._GOBACK; }
	echo '[ <a href="'.getlink("&amp;pollid=$poll_id")."\">"._VOTING."</a> | "
		.'<a href="'.getlink().'">'._OTHERPOLLS."</a> ] $article </font></center>";
	if (can_admin('surveys')) { echo '<br /><center>[ <a href="'.adminlink('Surveys&amp;mode=add').'">'._ADD.'</a> | <a href="'.adminlink("Surveys&amp;mode=edit&amp;id=$poll_id").'">'._EDIT.'</a> ]</center>'; }
	return $holdtitle[0];
}
