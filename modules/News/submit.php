<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/submit.php,v $
  $Revision: 1.10 $
  $Author: phoenix $
  $Date: 2007/09/11 11:25:51 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
get_lang('Submit_News');
require_once('includes/nbbcode.php');
$pagetitle .= _Submit_NewsLANG;
global $notify, $notify_email, $notify_subject, $notify_message, $notify_from, $CPG_SESS;

if (!$MAIN_CFG['global']['anonpost'] && !is_user()) {
	cpg_error('<p>'._MODULEUSERS.($MAIN_CFG['member']['allowuserreg'] ? _MODULEUSERS2 : '').'</p>', 401);
}
else if (isset($_POST['submit'])) {
	if (!isset($CPG_SESS['submit_story']) && !$CPG_SESS['submit_story']) { cpg_error(_SPAMGUARDPROTECTED); }
	$uid = is_user() ? $userinfo['user_id'] : 1;
	$name = is_user() ? $userinfo['username'] : _ANONYMOUS;
	$subject = isset($_POST['subject']) ? Fix_Quotes($_POST['subject']) : '';
	$story = isset($_POST['story']) ? Fix_Quotes(html2bb($_POST['story'])) : '';
	$storyext = isset($_POST['storyext']) ? Fix_Quotes(html2bb($_POST['storyext'])) : '';
	$topic = isset($_POST['topic']) ? intval($_POST['topic']) : 1;
	$alanguage = isset($_POST['alanguage']) ? Fix_Quotes($_POST['alanguage']) : '';
	$subject = check_words($subject);
	$story = encode_bbcode(check_words($story));
	$storyext = encode_bbcode(check_words($storyext));
	$db->sql_query('INSERT INTO '.$prefix.'_queue (qid, uid, uname, subject, story, storyext, timestamp, topic, alanguage) '.
								"VALUES (DEFAULT, '$uid', '$name', '$subject', '$story', '$storyext', ".gmtime().", $topic, '$alanguage')");
	if ($notify) {
		$notify_message = "$notify_message\n\n\n========================================================\n$subject\n\n\n".decode_bbcode($story, 1, true)."\n\n".decode_bbcode($storyext, 1, true)."\n\n$name";
		if (!send_mail($mailer_message,$notify_message,0,$notify_subject,$notify_email,$notify_email,$notify_from,$name)) {
			echo $mailer_message;
		}
	}
	$CPG_SESS['submit_story'] = false;
	unset($CPG_SESS['submit_story']);
	list($waiting) = $db->sql_ufetchrow("SELECT COUNT(*) FROM {$prefix}_queue", SQL_NUM);
	cpg_error(_SUBTEXT.'<br />'._WEHAVESUB.' <strong>'.$waiting.'</strong> '._WAITING, _Submit_NewsLANG, getlink('&file=submit'));
}
else {
	$CPG_SESS['submit_story'] = true;
	$story = $_POST['story'] ?? false;
	$storyext = $_POST['storyext'] ?? false;
	$subject = isset($_POST['subject']) ? htmlprepare($_POST['subject']) : false;
	$topic = isset($_POST['topic']) ? intval($_POST['topic']) : 0;
	$alanguage = $_POST['alanguage'] ?? '';

	require_once(BASEDIR.'includes/wysiwyg/wysiwyg.inc');
	$story_editor = new Wysiwyg('submitnews', 'story', '100%', '200px', $story);
	$storyext_editor = new Wysiwyg('submitnews', 'storyext', '100%', '300px', $storyext);

	$story_editor->setHeader();
	require_once('header.php');

	OpenTable();
	if ($story) {
		$f_story = decode_bb_all(encode_bbcode($story), 1, true);
		$f_storyext = decode_bb_all(encode_bbcode($storyext), 1, true);
		if ($topic < 1) {
			$topicimage = 'AllTopics.gif';
			$warning = '<div style="text-align:center;" class="option">'._SELECTTOPIC.'</div>';
		} else {
			$warning = '';
			$result = $db->sql_query('SELECT topicimage, topictext FROM '.$prefix."_topics WHERE topicid='$topic'");
			list($topicimage, $topictext) = $db->sql_fetchrow($result);
		}
		echo '<div style="text-align:center;" class="gen"><b>'._NEWSUBPREVIEW.'</b></div><br />
		<div style="text-align:center;">'._CHECKSTORY.'</div><br />
		<table class="newsarticle" style="width:70%; margin:auto;"><tr><td>
		<img src="images/topics/'.$topicimage.'" style="border:0; float:right;" alt="'.($topictext ?? '').'" title="'.($topictext ?? '').'" />
		<span class="gen"><b>'.$subject.'</b></span><br /><br />
		<span style="font-size:10px;">'.$f_story;
		if ($f_storyext != '') {
			echo '<br /><br />'.$f_storyext;
		}
		echo '</span></tr></td></table>'.(($warning != '') ? '<br />'.$warning : '');
	} else {
		echo '<div style="text-align:center;" class="genmed">'._SUBMITADVICE.'</div>';
	}
	CloseTable();
	OpenTable();
	echo open_form(getlink('&amp;file=submit'), 'submitnews', _Submit_NewsLANG).'
	<label class="ulog" for="subject">'._SUBTITLE.'</label>
	  <input type="text" name="subject" id="subject" size="65" maxlength="80" value="'.$subject.'" /><br /><br />
	<label class="ulog" for="topic">'._TOPIC.'</label>
	  <select name="topic" id="topic">';
	$result = $db->sql_query('SELECT topicid, topictext FROM '.$prefix.'_topics ORDER BY topictext');
	echo '<option value="">'._SELECTTOPIC."</option>\n";
	while ($row = $db->sql_fetchrow($result)) {
		$sel = ($row['topicid'] == $topic) ? 'selected="selected" ' : '';
		echo "<option $sel value=\"$row[topicid]\">$row[topictext]</option>\n";
	}
	echo '</select><br /><br />';
	if ($multilingual) {
		echo '
		<label class="ulog" for="alanguage">'._LANGUAGE.'</label>
		  '.lang_selectbox($alanguage).'<br /><br />';
	}
	echo '
	<label class="ulog">Editor style</label>
		'.$story_editor->getSelect().'<br /><br />
	'._STORYTEXT.'<br />
		'.$story_editor->getHTML().'<br />
	'._EXTENDEDTEXT.'<br />
		'.$storyext_editor->getHTML().'<br /><br />
	<div style="text-align:center;"><input type="submit" value="'._PREVIEW.'" />';
	if ($story != '') { echo '&nbsp;&nbsp;<input type="submit" name="submit" value="'._OK.'" />'; }
	echo '</div>'.
	close_form();
	CloseTable();
}