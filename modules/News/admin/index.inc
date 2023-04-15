<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/admin/index.inc,v $
  $Revision: 9.23 $
  $Author: nanocaiordo $
  $Date: 2007/10/22 08:14:59 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
require_once('includes/nbbcode.php');
require_once('modules/News/admin/functions.inc');
global $bgcolor1, $bgcolor2, $bgcolor3;

if (isset($_POST['save']) && isset($_POST['news'])) {
	if ($_POST['save'] != $CPG_SESS['admin']['page']) { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	foreach ($_POST['news'] as $key => $value) {
		if ($MAIN_CFG['global'][$key] != $value) {
			$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".Fix_Quotes($value)."' WHERE cfg_name='global' AND cfg_field='".Fix_Quotes($key)."'");
		}
	}
	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink('&mode=cfg'));
}
elseif (isset($_POST['mode'])) {

	if ($_POST['mode'] == 'save') {
		$subject = Fix_Quotes($_POST['subject'], 1);
		$hometext = Fix_Quotes($_POST['hometext']);
		$bodytext = Fix_Quotes($_POST['bodytext']);
		$notes = Fix_Quotes($_POST['notes']);
		
		//$associated = implode(',', $_POST['assotop']);
		
        $checked_array = $_POST['assotop'];
		foreach ($_POST['assotop'] as $key => $value) 
		{
           if (in_array($_POST['assotop'][$key], $checked_array)) 
		   {
              $associated = array_push($_POST['assotop'][$key]);      // Push the values 
              $associated = implode(" ", $associated);                // Do your work !!
           }
        }
		
		$catid = intval($_POST['catid']);
		$ihome = intval($_POST['ihome']);
		$acomm = intval($_POST['acomm']);
		$alanguage = Fix_Quotes($_POST['alanguage']);
		$topic = intval($_POST['topic']);
		$display_order = intval($_POST['display_order']);
		if (isset($_POST['sid'])) {
			$db->sql_query("UPDATE ".$prefix."_stories SET catid='$catid', title='$subject', hometext='$hometext', bodytext='$bodytext', topic='$topic', notes='$notes', ihome='$ihome', alanguage='$alanguage', acomm='$acomm', associated='$associated', display_order=".$display_order.' WHERE sid='.intval($_POST['sid']));
		} else if (isset($_POST['anid'])) {
			$date = mktime($_POST['hour'], $_POST['min'], 0, $_POST['month'], $_POST['day'], $_POST['year']);
			$result = $db->sql_query("UPDATE ".$prefix."_autonews SET catid='$catid', title='$subject', time='$date', hometext='$hometext', bodytext='$bodytext', topic='$topic', notes='$notes', ihome='$ihome', alanguage='$alanguage', acomm='$acomm' WHERE anid=".intval($_POST['anid']));
			url_redirect(adminlink('News'));
		} else {
			$poll_title = Fix_Quotes($_POST['poll_title']);
			$option_text = $_POST['option_text'];
			$aid = is_admin();
			$author = ($userinfo['user_id']>1) ? $userinfo['username'] : $aid;
			if (intval($_POST['automated'])) {
				$date = mktime($_POST['hour'], $_POST['min'], 0, $_POST['month'], $_POST['day'], $_POST['year']);
				$notes = '';
				$result = $db->sql_query('INSERT INTO '.$prefix.'_autonews (anid, catid, aid, title, time, hometext, bodytext, topic, informant, notes, ihome, alanguage, acomm, associated) '.
																	"VALUES (DEFAULT, '$catid', '$aid', '$subject', '$date', '$hometext', '$bodytext', '$topic', '$author', '$notes', '$ihome', '$alanguage', '$acomm', '$associated')");
			} else {
				if ($poll_title != '' && $option_text[1] != '' && $option_text[2] != '') {
					$haspoll = 1;
					$time_stamp = gmtime();
					$db->sql_query("INSERT INTO ".$prefix."_poll_desc (poll_id, poll_title, time_stamp, voters, planguage, artid, comments) VALUES (DEFAULT, '$poll_title', '$time_stamp', 0, '$alanguage', '0', '$acomm')");
					$id = $db->sql_nextid('poll_id');
					for ($i = 1; $i <= sizeof($option_text); $i++) {
						if ($option_text[$i] != '') { 
							$option_text[$i] = Fix_Quotes($option_text[$i]); }
						$db->sql_query("INSERT INTO ".$prefix."_poll_data (poll_id, option_text, option_count, vote_id) VALUES ($id, '$option_text[$i]', 0, $i)");
					}
				} else {
					$haspoll = 0;
					$id = 0;
				}
				$db->sql_query('INSERT INTO '.$prefix.'_stories (sid, catid, aid, title, time, hometext, bodytext, comments, counter, topic, informant, notes, ihome, alanguage, acomm, haspoll, poll_id, score, ratings, associated, display_order) '.
							"VALUES (DEFAULT, '$catid', '$aid', '$subject', ".gmtime().", '$hometext', '$bodytext', '0', '0', '$topic', '$author', '$notes', '$ihome', '$alanguage', '$acomm', '$haspoll', '$id', '0', '0', '$associated', '$display_order')");
				$artid = $db->sql_nextid('sid');
				$db->sql_query("UPDATE ".$prefix."_poll_desc SET artid='$artid' WHERE poll_id='$id'");
			}
			$db->sql_query("UPDATE ".$prefix."_admins SET counter=counter+1 WHERE aid='$aid'");
		}
		url_redirect(adminlink('News'));
	}

	else if ($_POST['mode'] == 'preview') {
		$automated = intval($_POST['automated']);
		$subject = htmlprepare($_POST['subject']);
		$hometext = $_POST['hometext'];
		$bodytext = $_POST['bodytext'];
		$catid = intval($_POST['catid']);
		$topic = intval($_POST['topic']);
		$assotop = isset($_POST['assotop']) ? $_POST['assotop'] : false;
		show_head(_PREVIEWSTORY);
		if ($topic<1) { $topic = 1; }
		echo '<form action="'.adminlink('News').'" name="postnews" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
		.'<input type="hidden" name="catid" value="'.$catid.'" />';
		$result = $db->sql_query("SELECT topicimage FROM ".$prefix."_topics WHERE topicid='$topic'");
		list($topicimage) = $db->sql_fetchrow($result);
		echo "<table border=\"0\" width=\"75%\" cellpadding=\"0\" cellspacing=\"1\" bgcolor=\"$bgcolor2\" align=\"center\"><tr><td>"
		."<table border=\"0\" width=\"100%\" cellpadding=\"8\" cellspacing=\"1\" bgcolor=\"$bgcolor1\"><tr><td>"
		."<img src=\"images/topics/$topicimage\" border=\"0\" align=\"right\" alt=\"\" />";
		themepreview($subject, $hometext, $bodytext);
		echo '</td></tr></table></td></tr></table>'
		.'<br /><br /><strong>'._TITLE.'</strong><br />'
		.'<input type="text" name="subject" size="50" value="'.$subject.'" /><br /><br />'
		.'<strong>'._TOPIC.'</strong> <select name="topic">';
		$toplist = $db->sql_query("SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext");
		echo "<option value=\"\">"._ALLTOPICS."</option>\n";
		while (list($topicid, $topics) = $db->sql_fetchrow($toplist)) {
			$sel = ($topicid==$topic) ? 'selected' : '';
			echo "<option $sel value=\"$topicid\">$topics</option>\n";
		}
		echo '</select><br /><br />';
		echo '<table border="0" width="100%" cellspacing="0"><tr><td width="20%"><strong>'._ASSOTOPIC.'</strong></td><td width="100%">'
			.'<table border="0" cellspacing="3" cellpadding="8"><tr>';
		$result = $db->sql_query('SELECT topicid, topictext FROM '.$prefix.'_topics ORDER BY topictext');
		$bgcolor = '';
		$a = 0;
		while ($row = $db->sql_fetchrow($result)) {
			$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
			if ($a == 3) {
				echo '</tr><tr>';
				$a = 0;
			}
			$checked = empty($_POST['assotop']) ? '' : (in_array($row['topicid'], $_POST['assotop']) ? ' checked="checked"' : '');
			echo "<td$bgcolor><input type='checkbox' name='assotop[]' value='$row[topicid]'$checked />$row[topictext]</td>";
			$a++;
		}
		echo '</tr></table></td></tr></table><br /><br />';
		SelectCategory($catid);
		echo '<br />';
		puthome(intval($_POST['ihome']), intval($_POST['acomm']));
		echo newsorder(intval($_POST['display_order']));
		if ($multilingual) {
			echo '<br /><strong>'._LANGUAGE.': </strong>'.lang_selectbox($_POST['alanguage']);
		} else {
			echo '<input type="hidden" name="alanguage" value="" />';
		}
		shownews_edit($hometext, $bodytext);
		echo '<br /><strong>'._PROGRAMSTORY.'</strong>&nbsp;&nbsp;'.yesno_option('automated', $automated);
		autonews_edit($_POST['day'], $_POST['month'], $_POST['year'], $_POST['hour'], $_POST['min']);
		echo '<select name="mode">'
		.'<option value="preview" selected>'._PREVIEWSTORY.'</option>'
		.'<option value="save">'._POSTSTORY.'</option>'
		.'</select> <input type="submit" value="'._GO.'" />';
		CloseTable();
		putpoll($_POST['poll_title'], $_POST['option_text']);
		echo '</form>';
	}
}
else if (isset($_GET['edit'])) {
	$sid = intval($_GET['edit']);
	$result = $db->sql_query("SELECT catid, title, hometext, bodytext, topic, notes, ihome, alanguage, acomm, topicimage, display_order, associated FROM ".$prefix."_stories LEFT JOIN ".$prefix."_topics ON (topicid=topic) where sid='$sid'");
	list($catid, $subject, $hometext, $bodytext, $topic, $notes, $ihome, $alanguage, $acomm, $topicimage, $display_order, $associated) = $db->sql_fetchrow($result);
	$subject = htmlprepare($subject);
	show_head();
	echo "<center><font class=\"option\"><strong>"._EDITARTICLE."</strong></font></center><br />"
	."<table width=\"80%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\" bgcolor=\"$bgcolor2\" align=\"center\"><tr><td>"
	."<table width=\"100%\" border=\"0\" cellpadding=\"8\" cellspacing=\"1\" bgcolor=\"$bgcolor1\"><tr><td>"
	."<img src=\"images/topics/$topicimage\" border=\"0\" align=\"right\" />";
	themepreview($subject, $hometext, $bodytext, $notes);
	echo "</td></tr></table></td></tr></table><br /><br />"
		.'<form action="'.adminlink('News').'" name="postnews" method="post" enctype="multipart/form-data" accept-charset="utf-8"><strong>'._TITLE.'</strong><br />'
		."<input type=\"text\" name=\"subject\" size=\"50\" value=\"$subject\" /><br /><br />"
		."<strong>"._TOPIC."</strong> ";
	$topics[0] = _ALLTOPICS;
	$toplist = $db->sql_query("SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext");
	while(list($t_id, $t_text) = $db->sql_fetchrow($toplist)) {
		$topics[$t_id] = $t_text;
	}
	echo select_box('topic', $topic, $topics).'<br /><br />';

	if (substr($associated, -1) == '-') $associated = substr($associated, 0, -1);
    $associated = preg_replace('#\-#m', ',', $associated);
	$associated = explode(',', $associated);
	echo "<table border='0' width='100%' cellspacing='0'><tr><td width='20%'><strong>"._ASSOTOPIC."</strong></td><td width='100%'>"
		."<table border='0' cellspacing='3' cellpadding='8'><tr>";
	$result = $db->sql_query('SELECT topicid, topictext FROM '.$prefix.'_topics ORDER BY topictext');
	$bgcolor = '';
	$a = 0;
	while ($row = $db->sql_fetchrow($result)) {
		$bgcolor = empty($bgcolor) ? ' bgcolor="'.$bgcolor3.'"' : '';
		$checked = '';
		if ($a == 3) {
			echo '</tr><tr>';
			$a = 0;
		}
		for ($i=0; $i<count($associated); $i++) {
			if ($associated[$i] == $row['topicid']) {
				$checked = 'checked="checked"';
				break;
			}
		}
		echo "<td$bgcolor><input type='checkbox' name='assotop[]' value='$row[topicid]' $checked />$row[topictext]</td>";
		$checked = '';
		$a++;
	}
	echo '</tr></table></td></tr></table><br /><br />';
	SelectCategory($catid);
	echo '<br />';
	puthome($ihome, $acomm);
	echo newsorder($display_order);
	if ($multilingual) {
		echo '<br /><strong>'._LANGUAGE.': </strong>'.lang_selectbox($alanguage);
	} else {
		echo '<input type="hidden" name="alanguage" value="" />';
	}
	shownews_edit($hometext, $bodytext);
	echo '<font class="content">'._ARESUREURL.'</font><br /><br />
	<strong>'._NOTES.'</strong><br />
	<textarea wrap="virtual" cols="50" rows="4" name="notes">'.htmlprepare($notes).'</textarea><br /><br />
	<input type="hidden" name="sid" value="'.$sid.'" />
	<input type="hidden" name="mode" value="save" />
	<input type="submit" value="'._SAVECHANGES.'" />
	</form>';
	CloseTable();
}
else if (isset($_GET['del'])) {
	$sid = intval($_GET['del']);
	if (isset($_POST['cancel'])) { url_redirect(adminlink()); }
	if (isset($_POST['confirm'])) {
		if ($CPG_SESS['admin']['page'] != 'News') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
		$db->sql_query('DELETE FROM '.$prefix."_stories where sid='$sid'");
		$db->sql_query('DELETE FROM '.$prefix."_comments where sid='$sid'");
		//$db->sql_query('UPDATE '.$prefix."_poll_desc SET artid='0' WHERE artid='$sid'");
		list($poll_id) = $db->sql_ufetchrow("SELECT poll_id FROM ".$prefix."_poll_desc WHERE artid='$sid'", SQL_NUM);
		if ($poll_id) {
			$db->sql_query("DELETE FROM ".$prefix."_poll_desc WHERE poll_id='$poll_id'");
			$db->sql_query("DELETE FROM ".$prefix."_poll_data WHERE poll_id='$poll_id'");
		}
		url_redirect(adminlink());
	}
	cpg_delete_msg(adminlink('&amp;del='.$sid), sprintf(_ERROR_DELETE_CONF, 'this story'));
}
else if (isset($_GET['mode'])) {

	if (substr($_GET['mode'],0,3) == 'cat') {
		require_once('modules/News/admin/categories.inc');
	}

	if ($_GET['mode'] == 'cfg') {
		show_head();
		echo '<form action="'.adminlink().'" method="post" name="news" enctype="multipart/form-data" accept-charset="utf-8">
	<table border="0" width="75%" cellpadding="3" cellspacing="1" class="forumline">
	 <tr>
	   <td class="row1"><span class="genmed">'._STORIESHOME.'</span></td>
	   <td class="row2">'.select_option('news[storyhome]', $MAIN_CFG['global']['storyhome'], array('5', '10', '15', '20', '25', '30')).'</td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._OLDSTORIES.'</span></td>
	   <td class="row2">'.select_option('news[oldnum]', $MAIN_CFG['global']['oldnum'], array('10', '20', '30', '40', '50')).'</td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._ALLOWANONPOST.'</span></td>
	   <td class="row2">'.yesno_option('news[anonpost]', $MAIN_CFG['global']['anonpost']).'</td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._COMMENTSARTICLES.'</span></td>
	   <td class="row2">'.yesno_option('news[articlecomm]', $MAIN_CFG['global']['articlecomm']).'</td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._NOTIFYSUBMISSION.'</span></td>
	   <td class="row2">'.yesno_option('news[notify]', $MAIN_CFG['global']['notify']).'</td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._EMAIL2SENDMSG.'</span></td>
	   <td class="row2"><input type="text" name="news[notify_email]" value="'.$MAIN_CFG['global']['notify_email'].'" size="35" maxlength="255" /></td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._EMAILSUBJECT.'</span></td>
	   <td class="row2"><input type="text" name="news[notify_subject]" value="'.$MAIN_CFG['global']['notify_subject'].'" size="35" maxlength="255" /></td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._EMAILMSG.'</span></td>
	   <td class="row2"><textarea name="news[notify_message]" cols="32" rows="8">'.htmlprepare($MAIN_CFG['global']['notify_message']).'</textarea></td>
	 </tr><tr>
	   <td class="row1"><span class="genmed">'._EMAILFROM.'</span></td>
	   <td class="row2"><input type="text" name="news[notify_from]" value="'.$MAIN_CFG['global']['notify_from'].'" size="35" maxlength="255" /></td>
	 </tr><tr>
	   <td class="catbottom" colspan="2" align="center"><input type="hidden" name="save" value="News" /><input class="mainoption" type="submit" value="'._SAVECHANGES.'" />&nbsp;&nbsp;<input class="liteoption" type="reset" value="'._RESET.'" /></td>
	 </tr>
	</table></form>';
		CloseTable();
  }
  elseif ($_GET['mode'] == 'add') {
		show_head();
		echo '<center><font class="option"><strong>'._ADDARTICLE.'</strong></font></center><br /><br />'
			.'<form action="'.adminlink('News').'" name="postnews" method="post"  enctype="multipart/form-data" accept-charset="utf-8"><strong>'._TITLE.'</strong><br />'
			.'<input type="text" name="subject" size="50" /><br /><br /><strong>'._TOPIC.'</strong> ';
		$toplist = $db->sql_query("select topicid, topictext from ".$prefix."_topics order by topictext");
		echo '<select name="topic"><option>'._SELECTTOPIC."</option>\n";
		while(list($topicid, $topics) = $db->sql_fetchrow($toplist)) {
			echo "<option value=\"$topicid\">$topics</option>\n";
		}
		echo '</select><br /><br />';
		echo '<table border="0" width="100%" cellspacing="0"><tr><td width="20%"><strong>'._ASSOTOPIC.'</strong></td><td width="100%">'
			.'<table border="0" cellspacing="3" cellpadding="8"><tr>';
		$sql = "SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext";
		$result = $db->sql_query($sql);
		$bgcolor = '';
		$a = 0;
		while ($row = $db->sql_fetchrow($result)) {
			$bgcolor = empty($bgcolor) ? ' bgcolor="'.$bgcolor3.'"' : '';
			if ($a == 3) {
				echo '</tr><tr>';
				$a = 0;
			}
			echo '<td'.$bgcolor.'><input type="checkbox" name="assotop[]" value="'.$row['topicid'].'" />'.$row['topictext'].'</td>';
			$a++;
		}
		echo '</tr></table></td></tr></table><br /><br />'."\n";
		SelectCategory();
		echo '<br />';
		puthome(1, $articlecomm);
		echo newsorder(0);
		if ($multilingual) {
			echo '<br /><strong>'._LANGUAGE.': </strong>'.lang_selectbox($language);
		} else {
			echo "<input type=\"hidden\" name=\"alanguage\" value=\"$language\" />";
		}
		shownews_edit('', '');
		echo "<font class=\"content\">"._ARESUREURL."</font>"
			.'<br /><br /><strong>'._PROGRAMSTORY.'</strong>&nbsp;&nbsp;'.yesno_option('automated', 0);
		$today = getdate(gmtime());
		autonews_edit($today['mday'], $today['mon'], $today['year'], $today['hours'], $today['minutes']);
		echo "<select name=\"mode\">"
		."<option value=\"preview\" selected>"._PREVIEWSTORY."</option>"
		."<option value=\"save\">"._POSTSTORY."</option>"
		."</select> "
		."<input type=\"submit\" value=\""._GO."\" />";
		CloseTable();
		putpoll('', array());
		print '</form>';
	}
}
else if (isset($_GET['adel'])) {
	if (isset($_POST['cancel'])) { url_redirect(adminlink()); }
	if (isset($_POST['confirm'])) {
		$db->sql_query('DELETE FROM '.$prefix.'_autonews WHERE anid='.intval($_GET['adel']));
		url_redirect(adminlink());
	}
	cpg_delete_msg(adminlink('&amp;adel='.intval($_GET['adel'])), sprintf(_ERROR_DELETE_CONF, 'this scheduled story'));
}
else if (isset($_GET['aedit'])) {
	$anid = intval($_GET['aedit']);
	$result = $db->sql_query("SELECT catid, aid, title, time, hometext, bodytext, topic, informant, notes, ihome, alanguage, acomm FROM ".$prefix."_autonews WHERE anid='$anid'");
	list($catid, $aaid, $title, $time, $hometext, $bodytext, $topic, $informant, $notes, $ihome, $alanguage, $acomm) = $db->sql_fetchrow($result);
	show_head();
	echo '<center><font class="option"><strong>'._AUTOSTORYEDIT.'</strong></font></center><br /><br />'
		.'<form action="'.adminlink('News').'" name="postnews" method="post" enctype="multipart/form-data" accept-charset="utf-8">';
	$result = $db->sql_query("SELECT topicimage FROM ".$prefix."_topics WHERE topicid='$topic'");
	list($topicimage) = $db->sql_fetchrow($result);
	echo "<table border=\"0\" width=\"75%\" cellpadding=\"0\" cellspacing=\"1\" bgcolor=\"$bgcolor2\" align=\"center\"><tr><td>"
		."<table border=\"0\" width=\"100%\" cellpadding=\"8\" cellspacing=\"1\" bgcolor=\"$bgcolor1\"><tr><td>"
		."<img src=\"images/topics/$topicimage\" border=\"0\" align=\"right\" />";
	themepreview($title, $hometext, $bodytext);
	echo "</td></tr></table></td></tr></table>"
		."<br /><br /><strong>"._TITLE."</strong><br />"
		."<input type=\"text\" name=\"subject\" size=\"50\" value=\"$title\"><br /><br />"
		."<strong>"._TOPIC."</strong> <select name=\"topic\">";
	$toplist = $db->sql_query("SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext");
	echo "<option value=\"\">"._ALLTOPICS."</option>\n";
	while (list($topicid, $topics) = $db->sql_fetchrow($toplist)) {
		$sel = '';
		if ($topicid==$topic) { $sel = ' selected="selected"'; }
		echo "<option $sel value=\"$topicid\">$topics</option>\n";
	}
	echo '</select><br /><br />';
	SelectCategory($catid);
	echo '<br />';
	puthome($ihome, $acomm);
	if ($multilingual) {
		echo '<br /><strong>'._LANGUAGE.': </strong>'.lang_selectbox($alanguage);
	} else {
		echo '<input type="hidden" name="alanguage" value="" />';
	}
	shownews_edit($hometext, $bodytext);
	echo "<font class=\"content\">"._ARESUREURL."</font><br /><br />";
	if ($aaid != $informant) {
		echo '<strong>'._NOTES.'</strong><br /><textarea wrap="virtual" cols="50" rows="4" name="notes" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$notes.'</textarea><br /><br />';
	}
	echo '<br /><strong>'._CHNGPROGRAMSTORY.'</strong>';
	ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2})', date('Y-m-d H:i', $time), $datetime);
	autonews_edit($datetime[3], $datetime[2], $datetime[1], $datetime[4], $datetime[5]);
	echo '<input type="hidden" name="anid" value="'.$anid.'" />
		<input type="hidden" name="mode" value="save" />
		<input type="submit" value="'._SAVECHANGES.'" />
		</form>';
	CloseTable();
}
else if (isset($_GET['disporder'])) {
	$db->sql_query('UPDATE '.$prefix.'_stories SET display_order='.intval($_GET['disporder']).' WHERE sid='.intval($_GET['sidrep']));
	$db->sql_query('UPDATE '.$prefix.'_stories SET display_order='.intval($_GET['disporderrep']).' WHERE sid='.intval($_GET['sidori']));
	url_redirect(adminlink('News'));
}
else if (isset($_GET['unstick'])) {
	$db->sql_query("UPDATE ".$prefix."_stories SET display_order='0' WHERE sid=".intval($_GET['unstick']));
	url_redirect(adminlink('News'));
}
else if (isset($_GET['stick'])) {
	$maxresult = $db->sql_query('SELECT max(display_order) AS maxnumber FROM '.$prefix.'_stories');
	list($sticky) = $db->sql_fetchrow($maxresult);
	$sticky++;
	$db->sql_query('UPDATE '.$prefix."_stories SET display_order='$sticky' WHERE sid=".intval($_GET['stick']));
	url_redirect(adminlink('News'));
}
else {
	show_head();
	echo '<span class="genmed"><strong>'._AUTOMATEDARTICLES.'</strong></span><br /><br />';
	$count = 0;
	$result = $db->sql_query("SELECT anid, aid, title, time, alanguage FROM ".$prefix."_autonews ORDER BY time ASC");
	$bgcolor = '';
	while (list($anid, $said, $title, $time, $alanguage) = $db->sql_fetchrow($result)) {
		$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
		if ($alanguage == '') { $alanguage = _ALL; }
		if ($anid != '') {
			if ($count == 0) {
				echo '<table border="0" width="100%" bgcolor="'.$bgcolor1.'">';
				$count = 1;
			}
			$time = formatDateTime($time, _DATESTRING);
			if (can_admin('news')) {
				echo '<tr'.$bgcolor.'><td nowrap="nowrap">&nbsp;(<a href="'.adminlink("News&amp;aedit=$anid").'">'._EDIT.'</a>-<a href="'.adminlink("News&amp;adel=$anid").'">'._DELETE."</a>)&nbsp;</td><td width=\"100%\">&nbsp;$title&nbsp;</td><td align=\"center\">&nbsp;$alanguage&nbsp;</td><td nowrap=\"nowrap\">&nbsp;$time&nbsp;</td></tr>";
			} else {
				echo "<tr$bgcolor><td width=\"100%\">&nbsp;$title&nbsp;</td><td align=\"center\">&nbsp;$alanguage&nbsp;</td><td nowrap=\"nowrap\">&nbsp;$time&nbsp;</td></tr>";
			}
		}
	}
	if ($count == 0) { echo sprintf(_ERROR_NONE_TO_DISPLAY,strtolower(_AUTOMATEDARTICLES)); }
	if ($count == 1) { echo '</table>'; }
	$db->sql_freeresult($result);
	echo '<br /><br /><span class="genmed"><strong>'._STICKY.'</strong></span><br /><br />';
	$result = $db->sql_query("SELECT sid, aid, title, alanguage, display_order FROM ".$prefix."_stories LEFT JOIN ".$prefix."_topics AS t ON (topicid=topic) WHERE display_order <> '0' ORDER BY display_order DESC");
	if ($db->sql_numrows($result) < 1) {
		echo sprintf(_ERROR_NONE_TO_DISPLAY,strtolower(_STICKY));
	} else {
		echo '<table border="0" width="100%" bgcolor="'.$bgcolor1.'">';
		$bgcolor = '';
		while (list($sid, $said, $title, $alanguage, $display_order) = $db->sql_fetchrow($result)) {
			$disporder = is_null($display_order) ? '&nbsp;' : $display_order;
			$disporder3 = $disporder - 1;
			$disporder1 = $disporder + 1;
			$result2 = $db->sql_query("SELECT sid FROM ".$prefix."_stories WHERE display_order='$disporder1'");
			list($sid1) = $db->sql_fetchrow($result2);
			$con1 = $sid1;
			$db->sql_freeresult($result2);
			$result2 = $db->sql_query("SELECT sid FROM ".$prefix."_stories WHERE display_order='$disporder3'");
			list($sid2) = $db->sql_fetchrow($result2);
			$con2 = $sid2;
			$db->sql_freeresult($result2);
			if ($alanguage == '') { $alanguage = _ALL; }
			$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
			echo "<tr$bgcolor><td align=\"right\"><strong>$sid</strong>"
				."</td><td align=\"left\" width=\"100%\"><a href=\"".getlink("News&amp;file=article&amp;sid=$sid")."\">$title</a>"
				."</td><td align=\"center\">$alanguage"
				."<td align=\"center\">";
			if ($con1) {
				echo '<a href="'.adminlink("News&amp;disporder=$disporder&amp;sidori=$sid&amp;disporderrep=$disporder1&amp;sidrep=$con1")."\"><img src=\"images/up.gif\" alt=\""._ARTICLEUP."\" title=\""._ARTICLEUP."\" border=\"0\" hspace=\"3\" /></a>";
			}
			if ($con2 && $disporder3 <> '0') {
				echo '<a href="'.adminlink("News&amp;disporder=$disporder&amp;sidori=$sid&amp;disporderrep=$disporder3&amp;sidrep=$con2")."\"><img src=\"images/down.gif\" alt=\""._ARTICLEDOWN."\" title=\""._ARTICLEDOWN."\" border=\"0\" hspace=\"3\" /></a>";
			}
			echo '</td><td align="center">'.$disporder;
			if (can_admin('news')) {
				echo "</td><td align=\"right\" nowrap=\"nowrap\">(<a href=\"".adminlink("News&amp;edit=$sid").'">'._EDIT."</a>-<a href=\"".adminlink("News&amp;del=$sid").'">'._DELETE."</a>-<a href=\"".adminlink("News&amp;unstick=$sid").'">'._UNSTICK.'</a>)';
			}
			echo '</td></tr>';
		}
		$db->sql_freeresult($result);
		echo '</table>';
	}
	echo '<br /><br /><span class="genmed"><strong>'._LAST.' 20 '._ARTICLES.'</strong></span><br /><br />';
	$result = $db->sql_query("SELECT sid, aid, title, alanguage, display_order FROM ".$prefix."_stories LEFT JOIN ".$prefix."_topics AS t ON (topicid=topic) WHERE display_order = '0' ORDER BY time DESC LIMIT 0,20");
	if ($db->sql_numrows($result) < 1) {
		echo sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_ARTICLES));
	} else {
		echo '<table border="0" width="100%" bgcolor="'.$bgcolor1.'">';
		$bgcolor = '';
		while (list($sid, $said, $title, $alanguage, $display_order) = $db->sql_fetchrow($result)) {
			$disporder = is_null($display_order) ? '&nbsp;': $display_order;
			if ($alanguage == '') { $alanguage = _ALL; }
			$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
			echo "<tr$bgcolor><td align=\"right\"><strong>$sid</strong>"
				."</td><td align=\"left\" width=\"100%\"><a href=\"".getlink("News&amp;file=article&amp;sid=$sid")."\">$title</a>"
				."</td><td align=\"center\">$alanguage"
				."</td><td align=\"center\">$disporder";
			if (can_admin('news')) {
				echo "</td><td align=\"right\" nowrap=\"nowrap\">(<a href=\"".adminlink("News&amp;edit=$sid").'">'._EDIT."</a>-<a href=\"".adminlink("News&amp;del=$sid").'">'._DELETE."</a>-<a href=\"".adminlink("News&amp;stick=$sid").'">'._STICK.'</a>)';
			}
			echo '</td></tr>';
		}
		$db->sql_freeresult($result);
		echo '</table>';
	}
	CloseTable();
}
