<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/admin/submissions.inc,v $
  $Revision: 9.17 $
  $Author: nanocaiordo $
  $Date: 2008/02/02 07:33:40 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('news')) { die('Access Denied'); }
get_lang('News');
require_once('includes/nbbcode.php');
require_once('modules/News/admin/functions.inc');
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';

if (isset($_GET['view'])) {
	show_head(_SUBMISSIONSADMIN);
	$result = $db->sql_query('SELECT qid, uid, uname, subject, story, storyext, topic, alanguage FROM '.$prefix."_queue where qid=".intval($_GET['view']));
	list($qid, $uid, $uname, $subject, $story, $storyext, $topic, $alanguage) = $db->sql_fetchrow($result);
	story_form($qid, $subject, $story, $storyext, $topic, $uid, $uname, $alanguage);
} else if (isset($_GET['del']) || $mode == 'delete') {
	if ($_GET['del'] == 'all') {
		$db->sql_query('DELETE FROM '.$prefix.'_queue');
		url_redirect(adminlink());
	} else {
		$qid = ($mode == 'delete') ? $_POST['qid'] : $_GET['del'];
		$db->sql_query('DELETE FROM '.$prefix.'_queue WHERE qid='.intval($qid));
		url_redirect(adminlink('News&file=submissions'));
	}
} else if ($mode == 'preview') {
	show_head();
	story_form(intval($_POST['qid']), $_POST['subject'], $_POST['hometext'], $_POST['bodytext'], intval($_POST['topic']), intval($_POST['uid']), $_POST['author']);
} else if ($mode == 'post') {
	postStory(intval($_POST['uid']), $_POST['bodytext'], intval($_POST['topic']), $_POST['notes']);
} else {
	global $bgcolor3;
	show_head(_SUBMISSIONSADMIN);
	$result = $db->sql_query("SELECT qid, subject, timestamp, alanguage FROM ".$prefix."_queue order by timestamp DESC");
	if ($db->sql_numrows($result) < 1) {
		echo sprintf(_ERROR_NONE_TO_DISPLAY,strtolower(_NEWSUBMISSIONS));
	} else {
		echo '<table width="100%">';
		$bgcolor = '';
		while (list($qid, $subject, $timestamp, $alanguage) = $db->sql_fetchrow($result)) {
			$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
			$timestamp = formatDateTime($timestamp, _DATESTRING3);
			if ($subject == '') { $subject = _NOSUBJECT; }
			if ($alanguage == '') { $alanguage = _ALL; }
			echo '<tr'.$bgcolor.'>
			<td align="center"><font class="content">(<a href="'.adminlink("News&amp;file=submissions&amp;del=$qid").'">'._DELETE.'</a>)&nbsp;</td>
			<td width="100%"><font class="content"><a href="'.adminlink("News&amp;file=submissions&amp;view=$qid").'">'.$subject.'</a></font></td>
			<td align="center"><font size="2">'.$alanguage.'</font></td>
			<td align="right"><font class="content">'.$timestamp.'</font></td>
			</tr>';
		}
		echo '</table>';
	}
	if ($db->sql_numrows($result) >= 1) {
		echo '<br /><center>[ <a href="'.adminlink('News&amp;file=submissions&amp;del=all').'">'._DELETE.' '._ALL.'</a> ]</center>';
	}
	CloseTable();
}

function story_form($qid, $subject, $hometext, $bodytext, $topic, $uid, $author, $alanguage) {
	global $boxstuff, $bgcolor1, $bgcolor2, $bgcolor3, $prefix, $user_prefix, $db, $multilingual;

	$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

	$subject = htmlprepare($subject);
	echo '<font class="content">'
	.'<form action="'.adminlink('News&amp;file=submissions').'" name="postnews" method="post" enctype="multipart/form-data" accept-charset="utf-8"><b>'._NAME.'</b><br />';
	if ($uid > 1) {
		$res = $db->sql_query("SELECT user_email FROM ".$user_prefix."_users WHERE user_id='$uid'");
		list($email) = $db->sql_fetchrow($res);
		echo '<input type="hidden" name="author" value="'.htmlprepare($author).'" />'.htmlprepare($author)
			."&nbsp;&nbsp;<font class=\"content\">[ <a href=\"mailto:$email?subject=Re: $subject\">"._EMAILUSER."</a> | <a href=\"".getlink("Your_Account&amp;profile=$uid")."\">"._USERPROFILE."</a> | <a href=\"".getlink("Private_Messages&amp;mode=post&amp;u=$uid")."\">"._SENDPM."</a> ]</font>";
	} else {
		echo '<input type="text" name="author" size="25" value="'.htmlprepare($author).'" />';
	}
	echo "<br /><br /><b>"._TITLE."</b><br /><input type=\"text\" name=\"subject\" size=\"50\" value=\"$subject\" /><br /><br />";
	if ($topic < 1) {
		$topicimage = 'AllTopics.gif';
		$warning = '<span style="color:red;background:white;font-weight:bold">'._SELECTTOPIC.'</span> ';
	} else {
		$result = $db->sql_query("SELECT topicimage FROM ".$prefix."_topics WHERE topicid='$topic'");
		list($topicimage) = $db->sql_fetchrow($result);
		$warning = _TOPIC;
	}
	echo "<table width=\"70%\" bgcolor=\"$bgcolor2\" cellpadding=\"0\" cellspacing=\"1\" border=\"0\" align=\"center\"><tr><td>
	<table width=\"100%\" bgcolor=\"$bgcolor1\" cellpadding=\"8\" cellspacing=\"1\" border=\"0\"><tr><td>
	<img src=\"images/topics/$topicimage\" border=\"0\" align=\"right\" alt=\""._TOPIC."\" title=\""._TOPIC."\" />";
	themepreview($subject, $hometext, $bodytext, $notes);
	echo "</td></tr></table></td></tr></table>
	<br /><b>$warning</b><select name=\"topic\">";
	$toplist = $db->sql_query("SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext");
	echo "<option value=\"\">"._ALLTOPICS."</option>\n";
	while (list($topicid, $topics) = $db->sql_fetchrow($toplist)) {
		$sel = ($topicid == $topic) ? $sel = ' selected="selected"' : '';
		echo "<option value=\"$topicid\"$sel>$topics</option>\n";
	}
	echo '</select>';
	echo '<br /><br />';
	echo "<table border='0' width='100%' cellspacing='0'><tr><td width='20%'><b>"._ASSOTOPIC."</b></td><td width='100%'>"
		."<table border='0' cellspacing='3' cellpadding='8'><tr>";
	$result = $db->sql_query("SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext");
	$bgcolor = '';
	$a = 0;
	while (list($topicid, $topictext) = $db->sql_fetchrow($result)) {
		$checked = '';
		$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
		if ($a == 3) {
			echo '</tr><tr>';
			$a = 0;
		}
		$assotop = isset($_POST['assotop'])?$_POST['assotop']:array();
		for ($i=0; $i<sizeof($assotop); $i++) {
			if ($assotop[$i] == $topicid) {
				$checked = 'checked="checked"';
				break;
			}
		}
		echo "<td$bgcolor><input type='checkbox' name='assotop[]' value='$topicid' $checked />$topictext</td>";
		$a++;
	}
	echo '</tr></table></td></tr></table><br /><br />';
	SelectCategory(isset($_POST['catid']) ? intval($_POST['catid']) : 0);
	echo '<br />';
	puthome((isset($_POST['ihome'])?intval($_POST['ihome']):1), (isset($_POST['acomm'])?intval($_POST['acomm']):1));
	echo newsorder(isset($_POST['display_order']) ? intval($_POST['display_order']) : 0);
	if ($multilingual) {
		echo '<br /><b>'._LANGUAGE.': </b>'.lang_selectbox(isset($_POST['alanguage'])?$_POST['alanguage']:$alanguage);
	} else {
		echo '<input type="hidden" name="alanguage" value="" />';
	}
	shownews_edit($hometext, $bodytext);
	echo "<span class=\"content\">"._ARESUREURL."</span><br /><br />"
	."<b>"._NOTES."</b><br />"
	."<textarea wrap=\"virtual\" cols=\"50\" rows=\"4\" name=\"notes\">$notes</textarea><br /><br />"
	."<input type=\"hidden\" name=\"qid\" size=\"50\" value=\"".$qid."\" />"
	."<input type=\"hidden\" name=\"uid\" size=\"50\" value=\"$uid\" />";
	echo '<b>'._PROGRAMSTORY.'</b>&nbsp;&nbsp;'.yesno_option('automated', (isset($_POST['automated'])?$_POST['automated']:0));
	if (isset($_POST['day'])) {
		autonews_edit($_POST['day'], $_POST['month'], $_POST['year'], $_POST['hour'], $_POST['min']);
	} else {
		$today = getdate();
		autonews_edit($today['mday'], $today['mon'], $today['year'], $today['hours'], $today['minutes']);
	}
	echo '
<select name="mode">
	<option value="delete">'._DELETESTORY.'</option>
	<option value="preview" selected="selected">'._PREVIEWSTORY.'</option>
	<option value="post">'._POSTSTORY.'</option>
</select> &nbsp; <input type="submit" value="'._GO.'" />';
	CloseTable();
	putpoll((isset($_POST['poll_title'])?$_POST['poll_title']:''), (isset($_POST['option_text'])?$_POST['option_text']:array()));
	echo '</form>';
}

function postStory($uid, $bodytext, $topic, $notes) {
	global $aid, $prefix, $db, $user_prefix;
	$associated = '';
	if (!empty($_POST['assotop']) && is_array($_POST['assotop'])) {
		$associated = implode(',', preg_grep('#(\d+)#', $_POST['assotop']));
	}
	$subject = Fix_Quotes($_POST['subject'], 1);
	$hometext = Fix_Quotes($_POST['hometext']);
	$bodytext = Fix_Quotes($bodytext);
	$notes = Fix_Quotes($notes);
	$haspoll = 0;
	$catid = intval($_POST['catid']);
	$ihome = intval($_POST['ihome']);
	$acomm = intval($_POST['acomm']);
	$alanguage = Fix_Quotes($_POST['alanguage']);
	$author = ($uid < 2) ? '' : Fix_Quotes($_POST['author']);
	if ($_POST['automated']) {
		$date = mktime($_POST['hour'], $_POST['min'], 0, $_POST['month'], $_POST['day'], $_POST['year']);
		if ($hometext == $bodytext) $bodytext = '';
		$result = $db->sql_query('INSERT INTO '.$prefix.'_autonews (anid, catid, aid, title, time, hometext, bodytext, topic, informant, notes, ihome, alanguage, acomm, associated) '.
														"VALUES (DEFAULT, '$catid', '$aid', '$subject', '$date', '$hometext', '$bodytext', '$topic', '$author', '$notes', '$ihome', '$alanguage', '$acomm', '$associated')");
		if ($uid > 1) {
			$db->sql_query('UPDATE '.$user_prefix."_users SET counter=counter+1 WHERE user_id='$uid'");
		}
		$db->sql_query('UPDATE '.$prefix."_admins SET counter=counter+1 WHERE aid='$aid'");
		$db->sql_query('DELETE FROM '.$prefix."_queue WHERE qid=".intval($_POST['qid']));
		url_redirect(adminlink('News&file=submissions'));
	}
	if ($hometext == $bodytext) $bodytext = '';
	$id = 0;
	$poll_title = isset($_POST['poll_title']) ? $_POST['poll_title'] : '';
	$option_text = isset($_POST['option_text']) ? $_POST['option_text'] : '';
	if (!empty($poll_title) && !empty($option_text[1]) && !empty($option_text[2])) {
		$haspoll = 1;
		$db->sql_insert($prefix.'_poll_desc', array('poll_title'=>$poll_title, 'time_stamp'=>gmtime(), 'planguage'=>$alanguage, 'comments'=>$acomm));
//		  $time_stamp = gmtime();
//		  $db->sql_query('INSERT INTO '.$prefix."_poll_desc VALUES (DEFAULT, '$poll_title', '$time_stamp', 0, '$alanguage', 0, '$acomm')");
		$id = $db->sql_nextid('poll_id');
		for($i = 1; $i <= sizeof($option_text); $i++) {
			$db->sql_insert($prefix.'_poll_data', array('poll_id'=>$id, 'option_text'=>$option_text[$i], 'vote_id'=>$i));
//			  if($option_text[$i] != '') { Fix_Quotes($option_text[$i]); }
//			  $db->sql_query('INSERT INTO '.$prefix."_poll_data (poll_id, option_text, option_count, vote_id) VALUES ($id, '$option_text[$i]', 0, $i)");
		}
	}
	$db->sql_query('INSERT INTO '.$prefix.'_stories (sid, catid, aid, title, time, hometext, bodytext, comments, counter, topic, informant, notes, ihome, alanguage, acomm, haspoll, poll_id, score, ratings, associated, display_order) '.
		"VALUES (DEFAULT, '$catid', '$aid', '$subject', ".gmtime().", '$hometext', '$bodytext', '0', '0', '$topic', '$author', '$notes', '$ihome', '$alanguage', '$acomm', '$haspoll', '$id', '0', '0', '$associated', ".intval($_POST['display_order']).")");
	$artid = $db->sql_nextid('sid');
	$db->sql_update($prefix.'_poll_desc', array('artid'=>$artid), "poll_id='$id'");
//	  $db->sql_query('UPDATE '.$prefix."_poll_desc SET artid='$artid' WHERE poll_id='$id'");
	if ($uid > 1) {
		$db->sql_query('UPDATE '.$user_prefix."_users SET counter=counter+1 WHERE user_id='$uid'");
	}
	$db->sql_query('UPDATE '.$prefix."_admins SET counter=counter+1 WHERE aid='$aid'");
	$db->sql_query('DELETE FROM '.$prefix.'_queue WHERE qid='.intval($_POST['qid']));
	url_redirect(adminlink('News&file=submissions'));
}
