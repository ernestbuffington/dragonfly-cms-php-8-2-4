<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/comments.php,v $
  $Revision: 9.20 $
  $Author: phoenix $
  $Date: 2007/09/11 15:28:07 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
require_once('includes/nbbcode.php');

function modtwo($tid, $score, $reason) {
	global $moderate;
	$return = '';
	if((is_admin() && $moderate > 0) || ($moderate == 2 && is_user())) {
		$return = "<select name=dkn$tid>";
		for($i=0; $i<11; $i++) {
			$return .= "<option value=\"$score:$i\">".constant('_REASONS_'.$i)."</option>\n";
		}
		$return .= '</select>';
	}
	return $return;
}

function modthree($sid) {
	global $moderate, $cpgtpl;
	$cpgtpl->assign_vars(array(
		'F_MODHIDE'	 => '<input type="hidden" name="sid" value="'.$sid.'" /><input type="hidden" name="op" value="moderate" />'
	));
}

function nocomm() {
	global $cpgtpl;
	$cpgtpl->assign_var('S_NOCOMMENTS', _NOCOMMENTSACT);
}

function navbar($sid, $title, $thold, $mode, $order) {
	global $anonpost, $prefix, $db, $mainindex, $cpgtpl, $textcolor1, $textcolor2, $bgcolor1, $bgcolor2;
	list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_comments WHERE sid='$sid'", SQL_NUM);
	$thold = intval($thold);
	$cpgtpl->assign_vars(array(
		'F_NEWSHIDE' => '',
		'S_COUNT'    => $count,
		'S_COMMENTS' => ($count==1) ? _COMMENT : _COMMENTS,
		'S_COMMWARN' => _COMMENTSWARNING,
		'S_POSTCOMM' => ($anonpost || is_user()) ? _REPLYMAIN : '',
		'S_SELTHOLD' => select_option('thold', $thold, array('-1','0','1','2','3','4','5')),
		'S_SELMODE'	 => select_box('mode', $mode, array('nested' => _NESTED, 'flat' => _FLAT, 'thread' => _THREAD)),
		'S_SELORDER' => select_box('order', $order, array('0' => _OLDEST, '1' => _NEWEST, '2' => _HIGHEST)),
		'S_THRESHOLD'=> _THRESHOLD,
		'S_TITLE'    => $title,
		'S_USER'     => (is_user() ? _CONFIGURE : _LOGINCREATE),
		'S_REFRESH'	 => _REFRESH,
		'S_TEXTCOLOR1' => $textcolor1,
		'S_TEXTCOLOR2' => $textcolor2,
		'S_BGCOLOR1' => $bgcolor1,
		'S_BGCOLOR2' => $bgcolor2,
		'U_ARTICLE'	 => getlink('&amp;file=article&amp;sid='.$sid),
		'U_TOPREPLY' => getlink('&amp;reply=0&amp;sid='.$sid),
		'U_USER'     => (is_user() ? getlink('Your_Account&amp;op=editcomm') : getlink('Your_Account'))
	));
}

function DisplayKids($tid, $mode, $order=0, $thold=0, $level=0, $dummy=0) {
	$parentid = null;
 global $userinfo, $anonpost, $commentlimit, $prefix, $db, $cpgtpl;
	$comments = 0;
	$result = $db->sql_query("SELECT tid, pid, sid, date, name, email, host_name, subject, comment, score, reason FROM ".$prefix."_comments WHERE pid='$tid' ORDER BY date, tid");
	if ($mode == 'nested' || $mode == 'flat') {
		while ($row = $db->sql_fetchrow($result)) {
			if($row['score'] >= $thold) {
				$tid = $row['tid'];
				$pid = $row['pid'];
				$sid = $row['sid'];
				$comment = $row['comment'];
				if($userinfo['commentmax'] && strlen($comment) > $userinfo['commentmax']) {
				   $comment = substr($comment, 0, $userinfo['commentmax']);
				   $commext = 1;
				}
				elseif(strlen($comment) > $commentlimit) {
				   $comment = substr($comment, 0, $commentlimit);
				   $commext = 1;
				}
				$comment = decode_bb_all($comment);
				if ($row['name'] == '') { $row['name'] = _ANONYMOUS; }
				else { $usrdata = getusrdata($row['name']); }
				if ($row['subject'] == '') { $row['subject'] = '['._NOSUBJECT.']'; }
				if ($pid != 0) {
					list($parentid) = $db->sql_ufetchrow("SELECT pid FROM ".$prefix."_comments WHERE tid='$pid'", SQL_NUM);
				}
				if ($row['name'] == '') { $row['name'] = _ANONYMOUS; }
				else { $usrdata = getusrdata($row['name']); }
				if ($row['subject'] == '') $row['subject'] = '['._NOSUBJECT.']';
				$cpgtpl->assign_block_vars('comment', array(
					'IS_NESTED'    => ($comments == 0 && $mode != 'flat'),
					'IS_NESTED_END'=> false,
					'IS_FIRST'     => false,
					'IS_FIRST_END' => false,
					'IS_LIST'      => false,
					'I_COMMENTID'  => $tid,
					'I_SCORE'      => $row['score'],
					'S_SCORE'      => _SCORE,
					'S_SUBJECT'    => $row['subject'],
					'S_REASON'     => ($row['reason']>0) ? constant('_REASONS_'.$row['reason']) : '',
					'S_BYNAME'     => _BY.' '.$row['name'],
					'S_BY'         => _BY,
					'S_NAME'       => $row['name'],
					'S_ON'         => _ON,
					'S_DATE'       => formatDateTime($row['date'], _DATESTRING),
					'S_IP'         => (is_admin() ? '(IP: '.decode_ip($row['host_name']).')' : ''),
					'S_COMMENT'    => $comment,
					'S_COMMENTEXT' => _READREST,
					'S_DELETE'     => _DELETE,
					'S_REPLY'      => _REPLY,
					'S_MODERATE'   => modtwo($tid, $row['score'], $row['reason']),
					'S_PARENT'     => _PARENT,
					'S_USERINFO'   => _USERINFO,
					'U_USERINFO'   => (isset($usrdata) ? getlink("Your_Account&amp;profile=$usrdata[user_id]") : ''),
					'S_USERPM'     => _SENDAMSG,
					'U_USERPM'     => ((isset($usrdata) && is_active('Private_Messages')) ? getlink("Private_Messages&amp;mode=post&amp;u=$usrdata[user_id]") : ''),
					'U_COMMENTEXT' => (isset($commentext) ? getlink("&amp;comment=$tid&amp;sid=$sid") : ''),
					'U_DELETE'     => is_admin() ? adminlink('comments&amp;del='.$tid) : '',
					'U_PARENT'     => ($pid != 0) ? getlink("&amp;comment=$parentid&amp;sid=$sid") : '',
					'U_REPLY'      => ($anonpost || is_user()) ? getlink("&amp;reply=$tid&amp;sid=$sid") : '',
				));
				$comments++;
				DisplayKids($tid, $mode, $order, $thold, $level+1, $dummy+1);
			}
		}
	} else {
		while ($row = $db->sql_fetchrow($result)) {
			if($row['score'] >= $thold) {
				if ($row['name'] == '') { $row['name'] = _ANONYMOUS; }
				if ($row['subject'] == '') { $row['subject'] = "["._NOSUBJECT."]"; }
				$cpgtpl->assign_block_vars('comment', array(
					'IS_NESTED'	 => ($comments == 0),
					'IS_NESTED_END' => false,
					'IS_FIRST'   => false,
					'IS_FIRST_END' => false,
					'IS_LIST'    => true,
					'U_READCOMM' => getlink("&amp;comment=$row[tid]&amp;sid=$row[sid]&amp;pid=$row[pid]")."#$row[tid]",
					'S_READCOMM' => $row['subject'],
					'S_BYNAME'	 => _BY.' '.$row['name'],
					'S_ON'       => _ON,
					'S_DATE'     => formatDateTime($row['date'], _DATESTRING2),
				));
				$comments++;
				DisplayKids($row['tid'], $mode, $order, $thold, $level+1, $dummy+1);
			}
		}
	}
	$db->sql_freeresult($result);
	if ($comments && $mode != 'flat') {
		$cpgtpl->assign_block_vars('comment', array(
			'IS_NESTED'    => false,
			'IS_NESTED_END'=> true,
			'IS_FIRST'     => false
		));
	}
}

function DisplayComments($sid, $title, $pid=0, $tid=0) {
	$parentid = null;
 global $hr, $userinfo, $commentlimit, $anonpost, $prefix, $db, $bgcolor3;
	global $moderate, $cpgtpl, $CPG_SESS;
	if (empty($CPG_SESS['comments']['mode'])) { $CPG_SESS['comments']['mode'] = 'thread'; }
	$order =& $CPG_SESS['comments']['order'];
	$thold =& $CPG_SESS['comments']['thold'];
	require_once('header.php');
	$q = 'SELECT tid, pid, sid, date, name, email, host_name, subject, comment, score, reason FROM '.$prefix."_comments WHERE sid='$sid' and pid='$pid' AND score>=".(($thold != "") ? "'$thold'" : "'0'");
	if ($order==1) $q .= ' ORDER BY date DESC';
	if ($order==2) $q .= ' ORDER BY score DESC';
	$something = $db->sql_query($q);
	navbar($sid, $title, $thold, $CPG_SESS['comments']['mode'], $order);
	$moderate = ($db->sql_numrows($something) > 0 && ((is_admin() && $moderate > 0) || ($moderate == 2 && is_user())));
	if ($moderate) {
		$cpgtpl->assign_var('U_MODERATE', getlink());
	} else {
		$cpgtpl->assign_var('U_MODERATE', false);
	}
	while ($row = $db->sql_fetchrow($something)) {
		$tid = $row['tid'];
		$pid = $row['pid'];
		$sid = $row['sid'];
		$comment = decode_bb_all($row['comment']);
		if ($row['name'] == '') { $row['name'] = _ANONYMOUS; }
		else { $usrdata = getusrdata($row['name']); }
		if ($row['subject'] == '') { $row['subject'] = "["._NOSUBJECT."]"; }
		if($userinfo['commentmax'] && strlen($comment) > $userinfo['commentmax']) {
		   $comment = substr($comment, 0, $userinfo['commentmax']);
		   $commext = 1;
		}
		elseif(strlen($comment) > $commentlimit) {
		   $comment = substr($comment, 0, $commentlimit);
		   $commext = 1;
		}
		if ($pid != 0) {
			list($parentid) = $db->sql_ufetchrow("SELECT pid FROM ".$prefix."_comments WHERE tid='$pid'", SQL_NUM);
		}
		$cpgtpl->assign_block_vars('comment', array(
			'IS_NESTED'    => false,
			'IS_NESTED_END'=> false,
			'IS_FIRST'     => true,
			'IS_FIRST_END' => false,
			'IS_LIST'      => false,
			'I_COMMENTID'  => $tid,
			'I_SCORE'      => $row['score'],
			'S_SCORE'      => _SCORE,
			'S_BGCOLOR3'   => $bgcolor3,
			'S_SUBJECT'    => $row['subject'],
			'S_REASON'     => ($row['reason']>0) ? constant('_REASONS_'.$row['reason']) : '',
			'S_BYNAME'     => _BY.' '.$row['name'],
			'S_BY'         => _BY,
			'S_NAME'       => $row['name'],
			'S_ON'         => _ON,
			'S_DATE'       => formatDateTime($row['date'], _DATESTRING),
			'S_IP'         => (is_admin() ? '(IP: '.decode_ip($row['host_name']).')' : ''),
			'S_COMMENT'    => $comment,
			'S_COMMENTEXT' => _READREST,
			'S_DELETE'     => _DELETE,
			'S_REPLY'      => _REPLY,
			'S_MODERATE'   => modtwo($tid, $row['score'], $row['reason']),
			'S_PARENT'     => _PARENT,
			'S_USERINFO'   => _USERINFO,
			'U_USERINFO'   => (isset($usrdata) ? getlink("Your_Account&amp;profile=$usrdata[user_id]") : ''),
			'S_USERPM'     => _SENDAMSG,
			'U_USERPM'     => ((isset($usrdata) && is_active('Private_Messages')) ? getlink("Private_Messages&amp;mode=post&amp;u=$usrdata[user_id]") : ''),
			'U_COMMENTEXT' => (isset($commentext) ? getlink("&amp;file=comments&amp;sid=$sid&amp;tid=$tid") : ''),
			'U_DELETE'     => is_admin() ? adminlink("comments&amp;del=$tid") : '',
			'U_PARENT'     => ($pid != 0) ? getlink("&amp;comment=0&amp;sid=$sid&amp;pid=$parentid") : '',
			'U_REPLY'      => ($anonpost || is_user()) ? getlink("&amp;reply=$tid&amp;sid=$sid") : '',
		));
		DisplayKids($tid, $CPG_SESS['comments']['mode'], $order, $thold, 0);
		$cpgtpl->assign_block_vars('comment', array(
			'IS_NESTED'    => false,
			'IS_NESTED_END'=> false,
			'IS_FIRST'     => false,
			'IS_FIRST_END' => true
		));
	}
	$db->sql_freeresult($something);
	if ($moderate) modthree($sid);
	$cpgtpl->assign_var('S_NOCOMMENTS', false);
	$cpgtpl->assign_var('S_ONECOMMENT', false);
	$cpgtpl->set_filenames(array('comments' => 'news/comments.html'));
	$cpgtpl->display('comments');
}

function singlecomment($tid, $sid) {
	global $bgcolor1, $bgcolor2, $textcolor2, $anonpost, $prefix, $db, $cpgtpl;
	require_once('header.php');
	list($date, $name, $email, $subject, $comment, $score, $reason) = $db->sql_ufetchrow("SELECT date, name, email, subject, comment, score, reason FROM ".$prefix."_comments WHERE tid='$tid' AND sid='$sid'", SQL_NUM);
	$titlebar = '<b>'.$subject.'</b>';
	global $moderate;
	if((is_admin() && $moderate > 0) || ($moderate == 2 && is_user())) {
		$cpgtpl->assign_var('U_MODERATE', getlink());
	}

	$cpgtpl->assign_vars('single', array(
		'U_REPLY' => ($anonpost || is_admin() || is_user()) ? '<a href="'.getlink("&amp;reply=$tid&amp;sid=$sid").'">'._REPLY.'</a> | <a href="'.getlink('&amp;file=article&amp;sid='.$sid).'">'._ROOT.'</a>' : '',
		'S_TITLEBAR'	=> '<b>'.$subject.'</b>',
		'I_SCORE'		=> $score,
		'S_SCORE'		=> _SCORE,
		'S_ON'			=> _ON,
		'S_BY'			=> _BY,
		'S_BGCOLOR1'	=> $bgcolor1,
		'S_BGCOLOR2'	=> $bgcolor2,
		'S_TEXTCOLOR2'	=> $textcolor2,
		'S_EMAIL'		=> $email,
		'S_DATE'		=> formatDateTime($date, _DATESTRING),
		'S_NAME'		=> ($name == '') ? _ANONYMOUS : $name,
		'S_SUBJECT'		=> ($subject == '') ? '['._NOSUBJECT.']' : $subject,
		'S_COMMENT'		=> decode_bb_all($comment),
		'S_MODERATE2'	=> modtwo($tid, $score, $reason),
		'S_MODERATE3'	=> ($moderate) ? modthree($sid) : '',
	));

	$cpgtpl->assign_var('S_NOCOMMENTS', false);
	$cpgtpl->assign_var('S_ONECOMMENT', true);
	$cpgtpl->set_filenames(array('single' => 'news/comments.html'));
	$cpgtpl->display('single');
}

function replyform($sid, $pid, $subject='', $comment='') {
	global $userinfo;
	if (!preg_match('#Re:#mi',$subject)) $subject = 'Re: '.substr($subject,0,81);
	if (is_user()) {
		$user = '<a href="'.getlink('Your_Account').'">'.$userinfo['username'].'</a>';
	} else {
		$user = _ANONYMOUS.' [ <a href="'.getlink('Your_Account').'">'._NEWUSER.'</a> ]';
	}
	OpenTable();
	echo '<form action="'.getlink().'" method="post" name="postcomment" enctype="multipart/form-data" accept-charset="utf-8">
	<span class="option"><b>'._YOURNAME.':</b></span> '.$user.'
	<br /><br /><span class="option"><b>'._SUBJECT.':</b></span><br />
	<input type="text" name="subject" size="50" maxlength="85" value="'.htmlprepare($subject).'" /><br />
	<table border="0"><tr><td>
	<span class="option"><b>'._UCOMMENT.':</b></span><br />
	'.bbcode_table('comment', 'postcomment').'
	<textarea wrap="virtual" cols="65" rows="10" name="comment">'.$comment.'</textarea></td><td valign="bottom">
	'.smilies_table('inline', 'comment', 'postcomment').'
	</td></tr></table><br />
	<input type="hidden" name="pid" value="'.$pid.'" />
	<input type="hidden" name="sid" value="'.$sid.'" />
	<input type="submit" name="preview" value="'._PREVIEW.'" /> <input type="submit" name="postreply" value="'._OK.'" />
	</form>';
	CloseTable();
}

function reply($sid) {
	$pid = intval($_GET['reply']);
	require_once('header.php');
	global $db, $prefix, $userinfo, $anonpost;
	if (!$anonpost && !is_user()) {
		cpg_error(_NOANONCOMMENTS);
	}
	if ($pid > 0) {
		list($date, $name, $subject, $comment) = $db->sql_ufetchrow("SELECT date, name, subject, comment FROM ".$prefix."_comments WHERE tid=$pid", SQL_NUM);
		$comment = decode_bb_all($comment);
	} else {
		$result = $db->sql_query("SELECT time, title, hometext, bodytext, informant, notes FROM ".$prefix."_stories WHERE sid='$sid'");
		$row = $db->sql_fetchrow($result);
		$date = $row['time'];
		$subject = $row['title'];
		$comment = decode_bb_all($row['hometext']);
		$comment .= '<br /><br />'.decode_bb_all($row['bodytext']);
		$name = $row['informant'];
		$notes = $row['notes'];
		$db->sql_freeresult($result);
	}
	OpenTable();
	echo '<div style="text-align:center;" class="title"><b>'._COMMENTREPLY.'</b></div><br />';
	CloseTable();
	OpenTable();
	if (empty($subject)) $subject = '['._NOSUBJECT.']';
	echo "<b>$subject</b>";
	if ($pid > 0) {
		if (empty($name)) $name = _ANONYMOUS;
		echo '<br />'._BY.' '.$name.' '._ON.' '.formatDateTime($date, _DATESTRING);
		echo '<br /><br />'.$comment.'<br /><br />';
		if (isset($notes) && !empty($notes)) {
			echo '<b>'._NOTE.'</b> <i>'.$notes.'</i><br /><br />';
		}
	}
	CloseTable();
	replyform($sid, $pid, $subject);
}

function replyPreview($sid) {
	global $userinfo;
	$subject = $_POST['subject'];
	$comment = $_POST['comment'];
	require_once('header.php');
	OpenTable();
	echo '<div style="text-align:center;" class="title"><b>'._COMREPLYPRE.'</b></div><br />';
	CloseTable();
	OpenTable();
	echo '<b>'.$subject.'</b>'
	.'<br /><span class="content">'._BY.' '.(is_user() ? $userinfo['username'] : _ANONYMOUS).' '._ONN.'</span><br /><br />';
	echo decode_bb_all(encode_bbcode(htmlprepare($comment)));
	CloseTable();
	replyform($sid, intval($_POST['pid']), $subject, $comment);
}

function replyPost($sid) {
	global $db, $prefix, $anonpost, $userinfo;
	if (!$anonpost && !is_user()) { cpg_error(_NOANONCOMMENTS); }
	$pid = intval($_POST['pid']);
	$subject = Fix_Quotes(check_words($_POST['subject']), 1);
	$comment = Fix_Quotes(encode_bbcode(htmlprepare(check_words($_POST['comment']))), 1);
	if (is_user()) {
		$name = $userinfo['username'];
		$email = $userinfo['femail'];
		$url = $userinfo['user_website'];
		$score = 1;
	} else {
		$name = $email = $url = '';
		$score = 0;
	}
	$ip = $userinfo['user_ip'];
	list($fake) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_stories WHERE sid='$sid'", SQL_NUM);
	if ($fake) {
		$db->sql_query("INSERT INTO ".$prefix."_comments (tid, pid, sid, date, name, email, url, host_name, subject, comment, score, reason) VALUES (DEFAULT, '$pid', '$sid', ".gmtime().", '$name', '$email', '$url', '$ip', '$subject', '$comment', '$score', '0')");
		$db->sql_query("UPDATE ".$prefix."_stories SET comments=comments+1 WHERE sid='$sid'");
		url_redirect(getlink('&amp;file=article&amp;sid='.$sid));
	} else {
		cpg_error("According to our records, the article you are trying to reply to does not exist. If you're just trying to be annoying, well then too bad.");
	}
}

if (isset($_POST['thold'])) {
	$CPG_SESS['comments'] = array('mode' => $_POST['mode'], 'order' => intval($_POST['order']), 'thold' => intval($_POST['thold']));
} else if (!isset($CPG_SESS['comments'])) {
	if (is_user()) {
		$CPG_SESS['comments'] = array('mode' => $userinfo['umode'], 'order' => $userinfo['uorder'], 'thold' => $userinfo['thold']);
	} else {
		$CPG_SESS['comments'] = array('mode' => 'thread', 'order' => 0, 'thold' => 0);
	}
}
if (isset($_POST['op']) && $_POST['op'] == 'moderate') {
	if ((is_admin() && $moderate > 0) || ($moderate == 2 && is_user())) {
		foreach($_POST AS $key => $val) {
		if (preg_match('#dkn#m', $key)) {
			$val = explode(':', $val);
			$val[0] = intval($val[0]);
			$val[1] = intval($val[1]);
			if($val[1] != 0 && $val[0] > -1 && $val[0] < 5) {
				$q = 'UPDATE '.$prefix.'_comments SET score=score';
				if($val[1] == 9 && $val[0]>=0) { # Overrated
					$q .= "-1";
				} elseif ($val[1] == 10 && $val[0]<=4) { # Underrated
					$q .= "+1";
				} elseif ($val[1] > 4 && $val[0]<=4) {
					$q .= "+1, reason='$val[1]'";
				} elseif ($val[1] < 5 && $val[0] > -1) {
					$q .= "-1, reason='$val[1]'";
				}
				$db->sql_query($q.' WHERE tid='.intval(preg_replace('#dkn#m', '', $key)));
			}
		}
		}
	}
	url_redirect(getlink('&amp;file=article&amp;sid='.$sid));
}
