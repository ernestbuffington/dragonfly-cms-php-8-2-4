<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Your_Account/index.php,v $
  $Revision: 9.27 $
  $Author: nanocaiordo $
  $Date: 2007/09/07 03:17:11 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _Your_AccountLANG;
$filepath = 'modules/'.basename(dirname(__FILE__));
require_once("$filepath/functions.php");
if (PHPVERS >= 43) { // version_compare()
	extract($MAIN_CFG['member'], EXTR_OVERWRITE | EXTR_REFS);
} else {
	extract($MAIN_CFG['member'], EXTR_OVERWRITE);
}
$op = (isset($_POST['op']) && !empty($_POST['op'])) ? $_POST['op'] : ((isset($_GET['op']) && $_GET['op']!='') ? $_GET['op'] : '');

function account_login($error='') {
	if (is_user()) { return; }
	global $CPG_SESS, $MAIN_CFG, $pagetitle;
	$pagetitle .= ' '._BC_DELIM.' '._USERLOGIN;
	require_once('header.php');
	if (isset($_GET['redirect']) && !isset($CPG_SESS['user']['redirect'])) { $CPG_SESS['user']['redirect'] = $CPG_SESS['user']['uri']; }
	$redirect = ($CPG_SESS['user']['redirect'] ?? getlink());
	echo '<form action="'.$redirect.'" method="post"  enctype="multipart/form-data" accept-charset="utf-8"><table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">';
	if ($error) {
		echo '<tr><td align="center" class="catleft" colspan="2"><b><span class="gen">'._ERROR.'</span></b></td></tr>
	<tr><td class="row1" colspan="2" align="center">'.$error.'</td></tr>';
	}
	echo '<tr>
		<td class="row1"><span class="gen"><label for="ulogin2">'._NICKNAME.'</label></span><br />'
		.(($MAIN_CFG['member']['allowuserreg']) ? '<a  href="'.getlink('&amp;file=register').'">'._REGNEWUSER.'</a>' : '')
		.'</td><td class="row2"><input type="text" name="ulogin" id="ulogin2" class="set" tabindex="1" size="20" maxlength="25" /></td></tr>
	<tr>
		<td class="row1"><span class="gen"><label for="user_password2">'._PASSWORD.'</label></span><br /><a href="'.getlink('&amp;op=pass_lost').'">'._PASSWORDLOST.'</a></td>
		<td class="row2"><input type="password" name="user_password" id="user_password2" class="set" tabindex="2" size="20" maxlength="20" /></td>
	</tr>';
	if ($MAIN_CFG['global']['sec_code'] & 2) {
		echo '<tr>
		<td class="row1"><span class="gen"><label for="gfx_check">'._SECURITYCODE.'</label></span></td>
		<td class="row2">'.generate_secimg().'</td>
	</tr><tr>
		<td class="row1"><span class="gen"><label for="gfx_check">'._TYPESECCODE.'</label></span></td>
		<td class="row2"><input type="text" name="gfx_check" id="gfx_check" class="set" tabindex="3" size="7" maxlength="6" /></td>
	</tr>';
	}
	echo '<tr><td class="catbottom" colspan="2" align="center" height="28">
	<input type="submit" class="mainoption" value="'._LOGIN.'" />
	</td></tr></table></form>';
}
function pass_lost() {
	global $pagetitle;
	$pagetitle .= ' '._BC_DELIM.' '._PASSWORDLOST;
	require_once('header.php');
	echo '<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
	<tr><td class="row1" colspan="2">'._NOPROBLEM.'</td></tr>
	<form action="'.getlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<tr><td class="row1"><span class="gen"><label for="lost_username">'._NICKNAME.'</label></span></td><td class="row2"><input type="text" name="lost_username" id="lost_username" class="set" tabindex="1" size="25" maxlength="25" /></td></tr>
	<tr><td class="row1"><span class="gen"><label for="lost_email"><strong>'._OR.'</strong> '._EMAILADDRESS.'</label></span></td><td class="row2"><input type="text" name="lost_email" id="lost_email" class="set" tabindex="2" size="25" maxlength="255" /></td></tr>
	<tr><td class="row1"><span class="gen"><label for="code">'._CONFIRMATIONCODE.'</label></span></td><td class="row2"><input type="text" name="code" id="code" class="set" tabindex="3" size="11" maxlength="10" /></td></tr>
	<tr><td class="catbottom" colspan="2" align="center" height="18">
	<input type="hidden" name="op" value="mailpasswd" />
	<input type="submit" class="mainoption" value="'._SENDPASSWORD.'" /></form></td></tr></table>';
}
function mail_password() {
	$mailer_message = null;
 global $user_prefix, $db, $pagetitle, $userinfo;
	if ((!isset($_POST['lost_username']) || empty($_POST['lost_username'])) && (!isset($_POST['lost_email']) || empty($_POST['lost_email']))) { cpg_error('Please enter either a username or email address'); }
	if (isset($_POST['lost_username']) && (!isset($_POST['lost_email']) || empty($_POST['lost_email']))) {
		$username = Fix_Quotes($_POST['lost_username']);
		if (empty($username) || strtolower($username) == 'anonymous') { cpg_error('Invalid username'); }
		$sql = "username='$username'";
	} else {
		$sql = "user_email='".Fix_Quotes($_POST['lost_email'])."'";
	}
	$result = $db->sql_query('SELECT username, user_email, user_password, user_level FROM '.$user_prefix.'_users WHERE '.$sql);
	$pagetitle .= ' '._BC_DELIM.' '._PASSWORDLOST;
	if ($db->sql_numrows($result) != 1) {
		cpg_error(_SORRYNOUSERINFO);
	} else {
		$row = $db->sql_fetchrow($result);
		$username = $row['username'];
		if ($row['user_level'] > 0) {
			global $sitename, $MAIN_CFG;
			$code = $_POST['code'];
			$areyou = substr($row['user_password'], 0, 10);
			$from = 'noreply@'.preg_replace('#www.#m', '', $MAIN_CFG['server']['domain']);
			if ($areyou == $code) {
				$newpass = make_pass(8, 5);
				$message = _USERACCOUNT." '$username' "._AT." $sitename "._HASTHISEMAIL."  "._AWEBUSERFROM." ".decode_ip($userinfo["user_ip"])." "._HASREQUESTED."\n\n"._YOURNEWPASSWORD." $newpass\n\n "._YOUCANCHANGE." ".getlink('Your_Account', true, true)."\n\n"._IFYOUDIDNOTASK;
				$subject = _USERPASSWORD4." $username";
				if (!send_mail($mailer_message,$message,0,$subject,$row['user_email'],$username,$from)) {
					cpg_error($mailer_message);
				}
				// Next step: add the new password to the database
				$cryptpass = md5($newpass);
				$query = "UPDATE ".$user_prefix."_users SET user_password='$cryptpass' WHERE username='$username'";
				if (!$db->sql_query($query)) { cpg_error(_UPDATEFAILED); }
				cpg_error(_PASSWORD4." $username "._MAILED, _TB_INFO, getlink());
				// If no code, send it
			} else {
				$message = _USERACCOUNT." '$username' "._AT." $sitename "._HASTHISEMAIL." "._AWEBUSERFROM." ".decode_ip($userinfo["user_ip"])." "._CODEREQUESTED."\n\n"._YOURCODEIS." $areyou \n\n"._WITHTHISCODE." ".getlink('&op=pass_lost', true, true)."\n"._IFYOUDIDNOTASK2;
				$subject = _CODEFOR." $username";
				if (!send_mail($mailer_message,$message,0,$subject,$row['user_email'],$username,$from)) {
					cpg_error($mailer_message);
				}
				cpg_error(_CODEFOR." $username "._MAILED, _TB_INFO, getlink('&op=pass_lost'));
			}
		} elseif ($row['user_level'] == 0) {
			cpg_error(_ACCSUSPENDED);
		} elseif ($row['user_level'] == -1) {
			cpg_error(_ACCDELETED);
		}
	}
}
function date_short($raw_date) {
	if ($raw_date == '0000-00-00' || empty($raw_date)) return '';
	$year = substr($raw_date, 0, 4);
	$month = (int)substr($raw_date, 5, 2);
	$day = (int)substr($raw_date, 8, 2);
	if (date('Y', mktime(0, 0, 0, $month, $day, $year)) == $year) {
		return date('m/d/Y', mktime(0, 0, 0, $month, $day, $year));
	} else {
		return preg_replace('#2037$#m', $year, date('m/d/Y', mktime(0, 0, 0, $month, $day, 2037)));
	}
}
function date_raw($date) {
	return substr($date, 6, 4) . substr($date, 0, 2) . substr($date, 3, 2);
}
function edithome() {
	global $userinfo, $pagetitle, $MAIN_CFG, $Blocks;
	$block = array(
		'bid' => 10000,
		'view' => 1,
		'side' => 'l',
		'title' => _TB_BLOCK,
		'content' => member_block()
	);
	$Blocks->custom($block);
	$block = NULL;
	$pagetitle .= ' '._BC_DELIM.' '._MA_HOMECONFIG;
	require_once('header.php');
	require_once(CORE_PATH.'nbbcode.php');
	echo '<form name="edit_home" action="'.getlink('&amp;op=savehome').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">';
	if ($MAIN_CFG['member']['user_news']) {
		echo '<tr><td class="row1"><span class="gen">'._STORIESHOME.'</span></td><td class="row2">'.select_option('storynum', $userinfo['storynum'], array('4','6','8','10','12','14','16','18','20')).'</td></tr>';
	} else {
		echo '<input type="hidden" name="storynum" value="'.$MAIN_CFG['global']['storyhome'].'" />';
	}
	echo '<tr><td class="row1"><span class="gen">'._ACTIVATEPERSONAL.'</span></td><td class="row2">'.yesno_option('ublockon', $userinfo['ublockon']).'</td></tr>
	<tr><td class="row1"><span class="gen">'._PERSONALMENUCONTENT.'</span><br />'.sprintf(_M_CHARS, 255).'</td><td class="row2">'.
	bbcode_table('ublock', 'edit_home', 1)
	.'<textarea cols="63" rows="7" name="ublock" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$userinfo['ublock'].'</textarea></td></tr>
	<tr><td class="catbottom" colspan="2" align="center" height="28">
	<input type="submit" value="'._SAVECHANGES.'" class="mainoption" />&nbsp;&nbsp;<input type="reset" value="'._RESET.'" name="reset" class="liteoption" /></td></tr></table>
	</form>';
}
function editcomm() {
	global $userinfo, $pagetitle, $Blocks;
	$block = array(
		'bid' => 10000,
		'view' => 1,
		'side' => 'l',
		'title' => _TB_BLOCK,
		'content' => member_block()
	);
	$Blocks->custom($block);
	$block = NULL;
	$pagetitle .= ' '._BC_DELIM.' '._COMMENTSCONFIG;
	require_once('header.php');
	echo '<form action="'.getlink('&amp;op=savecomm').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
	<tr><td class="row1"><span class="gen">'._DISPLAYMODE.'</span></td><td class="row2">
	<select name="umode">
	<option value="nocomments"'.(($userinfo['umode'] == 'nocomments') ? ' selected="selected"' : '').' />'._NOCOMMENTS.'
	<option value="nested"'.(($userinfo['umode'] == 'nested') ? ' selected="selected"' : '').' />'._NESTED.'
	<option value="flat"'.(($userinfo['umode'] == 'flat') ? ' selected="selected"' : '').' />'._FLAT.'
	<option value="thread"'.((empty($userinfo['umode']) || $userinfo['umode']=='thread') ? ' selected="selected"' : '').' />'._THREAD.'
	</select></td></tr>
	<tr><td class="row1"><span class="gen">'._SORTORDER.'</span></td><td class="row2">
	<select name="uorder">
	<option value="0"'.((!$userinfo['uorder']) ? ' selected="selected"' : '').' />'._OLDEST.'
	<option value="1"'.(($userinfo['uorder']==1) ? ' selected="selected"' : '').' />'._NEWEST.'
	<option value="2"'.(($userinfo['uorder']==2) ? ' selected="selected"' : '').' />'._HIGHEST.'
	</select></td></tr>
	<tr><td class="row1"><span class="gen">'._THRESHOLD.'</span><br />'._SCORENOTE.'<br />
	'._COMMENTSWILLIGNORED.'</td><td class="row2">
	<select name="thold">
	<option value="-1"'.(($userinfo['thold']==-1) ? ' selected="selected"' : '').' />'._UNCUT.'
	<option value="0"'.(($userinfo['thold']==0) ? ' selected="selected"' : '').' />'._EVERYTHING.'
	<option value="1"'.(($userinfo['thold']==1) ? ' selected="selected"' : '').' />'._FILTERMOSTANON.'
	<option value="2"'.(($userinfo['thold']==2) ? ' selected="selected"' : '').' />'._USCORE.' +2
	<option value="3"'.(($userinfo['thold']==3) ? ' selected="selected"' : '').' />'._USCORE.' +3
	<option value="4"'.(($userinfo['thold']==4) ? ' selected="selected"' : '').' />'._USCORE.' +4
	<option value="5"'.(($userinfo['thold']==5) ? ' selected="selected"' : '').' />'._USCORE.' +5
	</select></td></tr>
	<tr><td class="row1"><span class="gen">'._NOSCORES.'</span></td><td class="row2">'.yesno_option('noscore', $userinfo['noscore']).'</td></tr>
	<tr><td class="row1"><span class="gen">'._MAXCOMMENT.'</span></td><td class="row2">
	<input type="text" name="commentmax" value="'.$userinfo['commentmax'].'" size="11" maxlength="11" /> '._BYTESNOTE.'</td></tr>
	<tr><td class="catbottom" colspan="2" align="center" height="28">
	<input type="submit" value="'._SAVECHANGES.'" class="mainoption" />&nbsp;&nbsp;<input type="reset" value="'._RESET.'" name="reset" class="liteoption" /></td></tr></table>
	</form>';
}
function my_headlines() {
	if (!is_user()) { return; }
	global $prefix, $db;
	list($url) = $db->sql_ufetchrow("SELECT headlinesurl FROM ".$prefix."_headlines WHERE hid='".intval($_POST['hid'])."'", SQL_NUM);
	$content = '<div class="content">';
	if (!($content = rss_content($url))) { $content = _RSSPROBLEM; }
	echo $content.'</div>';
}

if (isset($_GET['error'])) {
	if ($_GET['error'] == 1) {
		account_login('Our records do not indicate an existing user named <i>'.htmlspecialchars(base64_decode($_GET['uname'])).'</i>');
	} else {
		account_login(_LOGININCOR);
	}
}
elseif (isset($_GET['profile']) && !empty($_GET['profile'])) {
	require_once("$filepath/userinfo.php");
	userinfo($_GET['profile']);
}
elseif ($op == 'userinfo' && isset($_GET['username']) && !empty($_GET['username'])) {
	require_once("$filepath/userinfo.php");
	userinfo($_GET['username']);
}
elseif ($op == 'logout') {
	$pagetitle .= ' '._BC_DELIM.' '._LOGOUT;
	$redir = (isset($_GET['redirect']) ? $CPG_SESS['user']['uri'] : $mainindex);
	cpg_error(_YOUARELOGGEDOUT, _YOUARELOGGEDOUT, $redir);
}
elseif (is_user()) {
	if (isset($_POST['avatargallery']) || isset($_GET['avatargallery'])) {
		require_once("$filepath/avatars.php");
		display_avatar_gallery($userinfo);
	} elseif (isset($_GET['edit'])) {
		require_once("$filepath/edit_profile.php");
		edituser($userinfo);
	} elseif (isset($_POST['save'])) {
		require_once("$filepath/edit_profile.php");
		saveuser($userinfo);
	} else switch($op) {
		case 'edithome':  edithome(); break;
		case 'editcomm':  editcomm(); break;
		case 'savehome':
			$db->sql_query('UPDATE '.$user_prefix.'_users SET storynum='.intval($_POST['storynum']).', ublockon='.intval($_POST['ublockon']).', ublock=\''.Fix_Quotes($_POST['ublock']).'\' WHERE user_id='.$userinfo['user_id']);
			$_SESSION['CPG_USER'] = false;
			unset($_SESSION['CPG_USER']);
			url_redirect(getlink());
			break;
		case 'savecomm':
			$db->sql_query("UPDATE ".$user_prefix."_users SET umode='".Fix_Quotes($_POST['umode'])."', uorder='".intval($_POST['uorder'])."', thold='".intval($_POST['thold'])."', noscore='".($_POST['noscore'] ?? 0)."', commentmax='".intval($_POST['commentmax'])."' WHERE user_id='".$userinfo['user_id']."'");
			$_SESSION['CPG_USER'] = false;
			unset($_SESSION['CPG_USER']);
			url_redirect(getlink());
			break;
		case 'my_headlines': my_headlines(); break;
		default:
			require_once("$filepath/userinfo.php");
			userinfo(is_user());
			break;
	}
} else {
	switch($op) {
		case 'mailpasswd': mail_password(); break;
		case 'pass_lost':  pass_lost();	 break;
		default: account_login(); break;
	}
}
