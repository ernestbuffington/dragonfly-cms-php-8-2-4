<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Module © 2004 by Akamu

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Contact/index.php,v $
  $Revision: 9.13 $
  $Author: nanocaiordo $
  $Date: 2008/01/13 11:13:59 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _ContactLANG;
require_once('includes/nbbcode.php');

$subject = $MAIN_CFG['global']['sitename'] .' '._FEEDBACK;
$recip = '';

$sender_name = $_POST['sender_name'] ?? '';
$sender_email = $_POST['sender_email'] ?? '';
$send_to = (is_admin() && isset($_POST['send_to'])) ? $_POST['send_to'] : '';
$message = $_POST['message'] ?? '';
$bb = ($MAIN_CFG['email']['allow_html_email'] || is_admin()) ? bbcode_table('message', 'email_mod', 0) : '';
$html = ($MAIN_CFG['email']['allow_html_email'] || is_admin()) ? 1 : 0;
if (is_admin()) {
	$sender_email = $MAIN_CFG['global']['adminmail'];
	$sender_name = $MAIN_CFG['global']['sitename'];
	$recip = '<label for="send_to"><strong>'._SEND_TO.'</strong></label><br /><input type="text" name="send_to" id="send_to" size="30" maxlength="255" /><br />';
}
if (!isset($_POST['opi'])) {
	if (is_user()) {
		$sender_name = (!empty($userinfo['name'])) ? $userinfo['name'] : $userinfo['username'];
		$sender_email = $userinfo['user_email'];
	}
	require_once('header.php');
	generate_secimg();
	$cpgtpl->set_handle('body', 'contact/index.html');
	$cpgtpl->assign_vars(array(
		'S_SITENAME' => $MAIN_CFG['global']['sitename'],
		'S_SENDER' => $sender_name,
		'S_SENDER_MAIL' => $sender_email,
		'S_MESSAGE' => $message,
		'S_BB' => $bb,
		'S_RECIP' => $recip,
		'S_GFX_IMG' => generate_secimg(),
		'U_ACTION' => getlink($module_name)
	));
	$cpgtpl->display('body');
	
} elseif ($_POST['opi'] == 'ds') {
	if (!Security::check_post()) { cpg_error(_SEC_ERROR); }
	if (empty($sender_name)) { $error = _ENT_NAME_LABEL; }
	if (empty($message)) { $error = _ENT_MESSAGE_LABEL; }
	if (!is_email($sender_email)) { $error = $PHPMAILER_LANG['from_failed'].' '.$sender_email; }
	if (!isset($error)) {
		$gfxid = $_POST['gfxid'] ?? 0;
		$code = $CPG_SESS['gfx'][$gfxid];
		$gfx_check  = $_POST['gfx_contact_check'] ?? '';
		if (strlen($gfx_check) < 2 || $code != $gfx_check) {
			$error = _SECURITYCODE.' incorrect';
		}
	}
	if (!isset($error)) {
		if (isset($_SESSION[$module_name])) unset($_SESSION[$module_name]);
		$msg = $MAIN_CFG['global']['sitename'] ." "._FEEDBACK."\n\n";
		$msg .= _SENDERNAME.": $sender_name\n";
		$msg .= _SENDEREMAIL.": $sender_email\n";
		$msg .= _MESSAGE.": ".$message."\n\n--\n";
		if (is_admin() && !empty($send_to)) {
			$recip_email = $send_to;
			$recip_name = $send_to;
			$msg .= _POSTEDBY." IP: ".$_SERVER['SERVER_ADDR'];
		} else {
			$recip_email = $MAIN_CFG['global']['adminmail'];
			$recip_name = $MAIN_CFG['global']['sitename'];
			$msg .= _POSTEDBY." IP: ".decode_ip($userinfo['user_ip']);
		}
		if (send_mail($error, $msg, $html, $subject, $recip_email, $recip_name, $sender_email, $sender_name)) {
			cpg_error(_SUCCESS_MESSAGE_SENT.'<br /><br />'.decode_bbcode("[quote=\"".$sender_name."\"]".$msg."[/quote]", 1).'<br />'._MAHALO, _ContactLANG, $mainindex);
		}
	}
	if (isset($error) && !empty($message)) {
		$_SESSION[$module_name]['message'] = $message;
	}
	cpg_error($error);
}
