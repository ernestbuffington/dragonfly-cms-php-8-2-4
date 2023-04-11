<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (class_exists('Dragonfly', false)) {
	contact_index();
}

function contact_index()
{
	$K = \Dragonfly::getKernel();
	$OUT = $K->OUT;
	\Dragonfly\Page::title(_ContactLANG, false);

	$sender_name  = $sender_email = $message = '';
	if (is_user()) {
		$userinfo     = $K->IDENTITY;
		$sender_name  = $userinfo->name ?: $userinfo->nickname;
		$sender_email = $userinfo->email;
	} else if (is_admin()) {
		$sender_email = $K->CFG->global->adminmail;
		$sender_name  = $K->CFG->global->sitename;
	}

	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		$sender_name  = $_POST['sender_name'];
		$sender_email = $_POST['sender_email'];
		$message      = $_POST['message'];
		$error = false;
		if (empty($sender_name)) {
			$error = true;
			\Poodle\Notify::error($OUT->L10N['_ENT_NAME_LABEL']);
		}
		if (empty($message)) {
			$error = true;
			\Poodle\Notify::error($OUT->L10N['_ENT_MESSAGE_LABEL']);
		}
		if (!is_email($sender_email)) {
			$error = true;
			\Poodle\Notify::error('ERROR: Invalid email address');
		}
		if (!\Dragonfly\Output\Captcha::validate($_POST)) {
			$error = true;
			\Poodle\Notify::error('ERROR: Invalid form submission');
		}
		if (!$error) {
			$subject = $K->CFG->global->sitename .' '.$OUT->L10N['_FEEDBACK'];
			$msg = $K->CFG->global->sitename ." {$OUT->L10N['_FEEDBACK']}\n\n";
			$msg .= _SENDERNAME.": {$sender_name}\n";
			$msg .= _SENDEREMAIL.": {$sender_email}\n";
			$msg .= _MESSAGE.": {$message}\n\n--\n";
			if (is_admin() && !empty($_POST['send_to'])) {
				$recip_email = $recip_name = $_POST['send_to'];
				$msg .= _POSTEDBY." IP: {$_SERVER['SERVER_ADDR']}";
			} else {
				$recip_email = $K->CFG->global->adminmail;
				$recip_name = $K->CFG->global->sitename;
				$msg .= _POSTEDBY." IP: {$_SERVER['REMOTE_ADDR']}";
			}
			if (\Dragonfly\Email::send($error, $subject, $msg, $recip_email, $recip_name, $sender_email, $sender_name)) {
				cpg_error($OUT->L10N['_SUCCESS_MESSAGE_SENT'].'<br /><br />'.\Dragonfly\BBCode::decode("[quote=\"{$sender_name}\"]{$msg}[/quote]", 1).'<br />'.$OUT->L10N['_MAHALO'], _ContactLANG, \Dragonfly::$URI_INDEX);
			}
			\Poodle\Notify::error($error);
		}
	}

	$OUT->allow_bbcode = ($K->CFG->email->allow_html_email || is_admin());
	if ($OUT->allow_bbcode) {
		\Dragonfly\BBCode::pushHeaders();
	}
	$OUT->assign_vars(array(
		'S_SENDER' => $sender_name,
		'S_SENDER_MAIL' => $sender_email,
		'S_MESSAGE' => $message,
	));
	$OUT->display('Contact/index');
}
