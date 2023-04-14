<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Tell_a_Friend/index.php,v $
  $Revision: 9.8 $
  $Author: phoenix $
  $Date: 2007/09/20 01:15:51 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle = _TELLFRIEND .' '.$MAIN_CFG['global']['sitename'];

$html = ($MAIN_CFG['email']['allow_html_email'] || is_admin());

list($reg_users) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users WHERE user_id > 1 AND user_level >= 0", SQL_NUM);

list($total_hits) = $db->sql_ufetchrow("SELECT SUM(count) FROM ".$prefix."_counter WHERE type='os'", SQL_NUM);

if (isset($_POST['sendMessage'])) {
	if (!Security::check_post()) { cpg_error(_SEC_ERROR); }
	$sender_name = strip_tags($_POST['sender_name']);
	$sender_email = strip_tags($_POST['sender_email']);
	$recipient_name = strip_tags($_POST['recipient_name']);
	$recipient_email = strip_tags($_POST['recipient_email']);
	$personal_message = $_POST['personal_message'];
	
	if (!isset($CPG_SESS['tell_friend']) && !$CPG_SESS['tell_friend']) { $error = _SPAMGUARDPROTECTED; }
	if (empty($sender_name)) { $error = _MISSINGSNAME; }
	if (empty($recipient_name)) { $error = _MISSINGRNAME; }
	if (empty($personal_message)) { $error = _MISSINGPMESSAGE; }
	
	$subject = _HAVELOOK .' '.$MAIN_CFG['global']['sitename'];
	
	$brackets_array = array('/\{sender\}/', '/\{recipient\}/', '/\{sitename\}/', '/\{slogan\}/', '/\{users\}/', '/\{hits\}/', '/\{founded\}/', '/\{url\}/');
	$brackets_replacements = array($sender_name, $recipient_name, $MAIN_CFG['global']['sitename'], $MAIN_CFG['global']['slogan'], $reg_users, $total_hits, $MAIN_CFG['global']['startdate'], $MAIN_CFG['global']['nukeurl']);
	
	$personal_message = preg_replace($brackets_array, $brackets_replacements, $personal_message);
	
	if (!isset($error)) {
		$gfxid = $_POST['gfxid'] ?? 0;
		$code = $CPG_SESS['gfx'][$gfxid];
		$gfx_check  = $_POST['gfx_contact_check'] ?? '';
		if (strlen($gfx_check) < 2 || $code != $gfx_check) {
			$error = _SECURITYCODE.' incorrect';
		}
	}
	if (isset($error)) {
		cpg_error('<div style="text-align:center;">'.$error.'</div>');
	} else {
		if (!send_mail($mailer_message, $personal_message, $html, $subject, $recipient_email, $recipient_name, $sender_email, $sender_name)) {
			cpg_error('<div style="text-align:center;"><strong>'.$mailer_message.'</strong></div>');
		} else {
			$CPG_SESS['tell_friend'] = false;
			unset($CPG_SESS['tell_friend']);
			cpg_error(_MESSAGESENT, _Tell_a_FriendLANG, $mainindex);
		}
	}
} else {
	$CPG_SESS['tell_friend'] = true;
	$sender_name = $sender_email = '';
	if (is_user()) {
		$sender_name = (!empty($userinfo['name'])) ? $userinfo['name'] : $userinfo['username'];
		$sender_email = $userinfo['user_email'];
	}
	$message_insert = _HEY ." {recipient},\n\n"
					._OURSITE ."\n\n"
					._ITSCALLED ." {sitename} "._SOMESTATS."\n\n"
					._SLOGAN ." {slogan}\n"
					._FOUNDEDON ." {founded}\n"
					._REGISTEREDUSERS ." {users}\n"
					._TOTALSITEHITS ." {hits}\n\n"
					._VISITTHEM .($html ? ' [url={url}]{url}[/url]' : ' {url}')."\n\n"
					._KINDREGARDS .",\n\n{sender}";
	if ($html) {
		require_once('includes/nbbcode.php');
		$bbcode = bbcode_table('personal_message', 'tell_friend', 0);
	} else {
		$bbcode = '<div style="color: #ff0000"><strong>.: '._BBCODEDISABLED.' :.</strong></div><br />';
	}

	require_once('header.php');
	OpenTable();
	
	echo '<form id="tell_friend" action="'.getlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<div style="text-align:center;">
	<span class="content">
	  <strong>'._INFORMATION.'</strong>
	</span>
	<br /><br /><br />
	<strong>'._SENDERNAME.'</strong>
	<br />
	<input type="text" name="sender_name" size="25" maxlength="255" value="'.$sender_name.'" />
	<br /><br />
	<strong>'._SENDEREMAIL.'</strong>
	<br />
	<input type="text" name="sender_email" size="25" maxlength="255" value="'.$sender_email.'" />
	<br /><br /><br />
	<strong>'._RECIPIENTNAME.'</strong>
	<br />
	<input type="text" name="recipient_name" size="25" maxlength="255" />
	<br /><br />
	<strong>'._RECIPIENTEMAIL.'</strong>
	<br />
	<input type="text" name="recipient_email" size="25" maxlength="255" />
	<br /><br /><br />
	<strong>'._PERSONALMESSAGE.'</strong>
	<br /><br />
	'._YOUMAYINCLUDE.'
	<br /><br />
	<table width="400" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
	  <td style="width:100px;"><strong>{sender}</strong></td><td>'._DESCSENDER.'</td>
	</tr><tr>
	  <td><strong>{recipient}</strong></td><td>'._DESCRECIPIENT.'</td>
	</tr><tr>
	  <td><strong>{sitename}</strong></td><td>'._DESCSITENAME.'</td>
	</tr><tr>
	  <td><strong>{slogan}</strong></td><td>'._DESCSLOGAN.'</td>
	</tr><tr>
	  <td><strong>{users}</strong></td><td>'._DESCUSERS.', '.$reg_users.'</td>
	</tr><tr>
	  <td><strong>{hits}</strong></td><td>'._DESCHITS.', '.$total_hits.'</td>
	</tr><tr>
	  <td><strong>{founded}</strong></td><td>'._DESCFOUNDED.'</td>
	</tr><tr>
	  <td><strong>{url}</strong></td><td>'._DESCURL.'</td>
	</tr>
	</table>
	<br />
	<table align="center"><tr><td>'.$bbcode.'</td></tr></table>
	<textarea name="personal_message" cols="63" rows="17" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$message_insert.'</textarea>
	<br /><br />
	'.generate_secimg(7).'<br />'._TYPESECCODE.'<br /><input type="text" name="gfx_contact_check" size="10" maxlength="8" /><br />
	<input type="submit" name="sendMessage" value="'._SEND.'" />
	</div>
	</form>';
	CloseTable();
}