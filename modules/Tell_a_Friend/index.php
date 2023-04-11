<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

if (!\Dragonfly::getKernel()->IDENTITY->isAdmin()) {
	exit('Tell_a_friend is a spammers beloved tool. Therefore it is removed');
}

$pagetitle = 'Test Mail Settings';

if ('POST' == $_SERVER['REQUEST_METHOD'])
{
	$sender_name = strip_tags($_POST['sender_name']);
	$sender_email = strip_tags($_POST['sender_email']);
	$recipient_name = strip_tags($_POST['recipient_name']);
	$recipient_email = strip_tags($_POST['recipient_email']);
	$personal_message = $_POST['personal_message'];
	if (empty($sender_name)) { cpg_error(_MISSINGSNAME); }
	if (empty($recipient_name)) { cpg_error(_MISSINGRNAME); }
	if (empty($personal_message)) { cpg_error(_MISSINGPMESSAGE); }
	$subject = 'Test mail '.$MAIN_CFG['global']['sitename'];
	$personal_message = str_replace(
		array('{sender}', '{recipient}', '{url}'),
		array($sender_name, $recipient_name, $MAIN_CFG['global']['nukeurl']),
		$personal_message);
	if (!\Dragonfly\Email::send($mailer_message, $subject, $personal_message, $recipient_email, $recipient_name, $sender_email, $sender_name)) {
		cpg_error($mailer_message);
	} else {
		cpg_error(_MESSAGESENT, _Tell_a_FriendLANG, \Dragonfly::$URI_INDEX);
	}
}
else
{
	$sender_name = $sender_email = '';
	if (is_user()) {
		$sender_name = (!empty($userinfo['name'])) ? $userinfo['name'] : $userinfo['username'];
		$sender_email = $userinfo['user_email'];
	}
	\Dragonfly\BBCode::pushHeaders();
	require_once('header.php');
	echo '<form action="'.URL::index().'" method="post" accept-charset="utf-8">
	<div>
		<label>
			<span>'._SENDERNAME.'</span>
			<input type="text" name="sender_name" size="25" maxlength="255" value="'.$sender_name.'" />
		</label>
		<br/>
		<label>
			<span>'._SENDEREMAIL.'</span>
			<input type="text" name="sender_email" size="25" maxlength="255" value="'.$sender_email.'" />
		</label>
		<br/>
		<label>
			<span>'._RECIPIENTNAME.'</span>
			<input type="text" name="recipient_name" size="25" maxlength="255" />
		</label>
		<br/>
		<label>
			<span>'._RECIPIENTEMAIL.'</span>
			<input type="text" name="recipient_email" size="25" maxlength="255" />
		</label>
		<br/>
		<textarea name="personal_message" cols="63" rows="17" class="bbcode">'
			._HEY ." {recipient},\n\n"
			._KINDREGARDS .",\n\n{sender}\n"
			.($html ? ' [url={url}]{url}[/url]' : ' {url}')
		.'</textarea>
		<br /><br />
		<button type="submit" name="sendMessage">'._SEND.'</button>
	</div>
	</form>';
}
