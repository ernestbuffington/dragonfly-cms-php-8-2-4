<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/friend.php,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:26 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _NewsLANG.' '._BC_DELIM.' '._FRIEND;
$sid = isset($_GET['sid']) ? intval($_GET['sid']) : '';

if (isset($_POST['sendStory'])) {
	SendStory();
} else {
	FriendSend($sid);
}

function FriendSend($sid) {
	global $userinfo, $prefix, $db, $CPG_SESS;
	if (empty($sid)) { exit; }
	$CPG_SESS['send_story'] = true;
	require_once('header.php');
	list($title) = $db->sql_ufetchrow("SELECT title FROM ".$prefix."_stories WHERE sid='$sid'", SQL_NUM);
	$yn = $ye = '';
	if (is_user()) {
		$yn = $userinfo['username'];
		$ye = $userinfo['user_email'];
	}
	OpenTable();
	echo open_form(getlink('&amp;file=friend'), false, _FRIEND).'
	'._YOUSENDSTORY.' <strong>'.$title.'</strong> '._TOAFRIEND.'<br /><br />
	<label class="ulog" for="yname">'._FYOURNAME.'</label>
	<input type="text" name="yname" id="yname" value="'.$yn.'" size="25" maxlength="30" /><br />
	<label class="ulog" for="ymail">'._FYOUREMAIL.'</label>
	<input type="text" name="ymail" id="ymail" value="'.$ye.'" size="25" maxlength="255" /><br /><br />
	<label class="ulog" for="fname">'._FFRIENDNAME.'</label>
	<input type="text" name="fname" id="fname" size="25" maxlength="30" /><br />
	<label class="ulog" for="fmail">'._FFRIENDEMAIL.'</label>
	<input type="text" name="fmail" id="fmail" size="25" maxlength="255" /><br /><br />
	<input type="hidden" name="sid" value="'.$sid.'" />
	<input type="submit" name="sendStory" value="'._SEND.'" />'.
	close_form();
	CloseTable();
}

function SendStory() {
	$mailer_message = null;
 global $sitename, $prefix, $db, $CPG_SESS, $pagetitle, $userinfo;

	if (!isset($CPG_SESS['send_story']) && !$CPG_SESS['send_story']) { cpg_error(_SPAMGUARDPROTECTED); }

	$sid = intval($_POST['sid']);
	$yname = Fix_Quotes($_POST['yname'], true);
	$ymail = Fix_Quotes($_POST['ymail'], true);
	$fname = Fix_Quotes($_POST['fname'], true);
	$fmail = Fix_Quotes($_POST['fmail'], true);

	if (empty($sid)) { cpg_error(sprintf(_ERROR_NOT_SET, _ID)); }
	if (empty($yname)) { cpg_error(sprintf(_ERROR_NOT_SET, _FYOURNAME)); }
	if (empty($ymail)) { cpg_error(sprintf(_ERROR_NOT_SET, _FYOUREMAIL)); }
	if (empty($fname)) { cpg_error(sprintf(_ERROR_NOT_SET, _FFRIENDNAME)); }
	if (empty($fmail)) { cpg_error(sprintf(_ERROR_NOT_SET, _FFRIENDEMAIL)); }

	list($s_title, $s_time, $s_topictext) = $db->sql_ufetchrow("SELECT s.title, s.time, t.topictext FROM ".$prefix."_stories s LEFT JOIN ".$prefix."_topics t ON (t.topicid = s.topic) WHERE s.sid='$sid'", SQL_NUM);

	$subject = _INTERESTING." $sitename";
	$message = _HELLO." $fname:\n\n"._YOURFRIEND." $yname "._CONSIDERED."\n\n\n$s_title"._FDATE.' '.formatDateTime($s_time, _DATESTRING)."\n"._FTOPIC." $s_topictext\n\n"._URL.": ".getlink("&file=article&sid=$sid", true, true)."\n\n"._YOUCANREAD." $sitename\n".getlink('', true, true)."\n\n";
	$message .= _POSTEDBY." IP: ".decode_ip($userinfo['user_ip']);
	if (!send_mail($mailer_message,$message,0,$subject,$fmail,$fname,$ymail,$yname)) {
		cpg_error($mailer_message);
	} else {
		$CPG_SESS['send_story'] = false;
		unset($CPG_SESS['send_story']);
		cpg_error($mailer_message, $pagetitle, getlink('', true, true));
	}
}