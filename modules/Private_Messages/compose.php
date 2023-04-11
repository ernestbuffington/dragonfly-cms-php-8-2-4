<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

use Dragonfly\Modules\Private_Messages\Message as Message;

#Has admin prevented user from sending PM's?
if (!$userinfo->allow_pm) {
	cpg_error($LNG['Cannot_send_privmsg']);
}

require_once(__DIR__ . '/init.inc');

$mode = $mode ?: 'compose';
$submit = $_POST->exist('post');
$preview = $_POST->exist('preview');
$errors = array();

$K = Dragonfly::getKernel();
$MAIN_CFG = $K->CFG;
$template = $K->OUT;

try {
	if ('reply' == $mode || 'quote' == $mode) {
		$prev_msg = new Message(($_POST->uint('p') ?: $_GET->uint('p')));
		$privmsg = new Message();
		if (!$prev_msg || !$prev_msg->getRecipient($userinfo->id)) {
			cpg_error($LNG['No_match'], 404);
		}
	} else {
		$privmsg = new Message(($_POST->uint('p') ?: $_GET->uint('p')));
		if ($privmsg->id && ($privmsg->user_id != $userinfo->id || Message::STATUS_NEW != $privmsg->status)) {
			cpg_error($LNG['Edit_own_posts'], 403);
		}
	}
} catch (\Exception $e) {
	cpg_error($LNG['No_match'], 404);
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
	$privmsg->subject = $_POST->text('subject');
	$privmsg->text    = $_POST->text('message');
	$privmsg->enable_bbcode  = $_POST->bool('enable_bbcode');
	$privmsg->enable_smilies = $_POST->bool('enable_smilies');
}

$template->pm_options = array(
	'allow_bbcode' => $PMCFG->allow_bbcode,
	'allow_smilies' => $PMCFG->allow_smilies,
);

if ($submit || $preview || $mode)
{
	# Flood control
	if ($submit && $mode != 'edit') {
		list($last_post_time) = $db->uFetchRow("SELECT MAX(pm_date) FROM {$db->TBL->privatemessages}
		WHERE user_id = {$userinfo->id}");
		if ((time() - $last_post_time) < $PMCFG->flood_interval) {
			cpg_error($LNG['Flood_Error']);
		}
	}

	# Warn for not-existing user at any given time
	$to_username = '';
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		$to_username = $_POST->text('username');
		if ($to_username) {
			if (!($to_userinfo = $db->uFetchAssoc("SELECT user_id, username, user_notify_pm, user_email, user_lang, user_active FROM {$db->TBL->users}
				WHERE user_nickname_lc = '".$db->escape_string(mb_strtolower($to_username))."'
				  AND user_id > 1 and user_active = 1")))
			{
				$errors[] = $LNG['No_such_user'];
			}
		} else {
			$errors[] = $LNG['No_to_user'];
		}
	}

	if ($submit) {
		if (empty($privmsg->subject)) {
			$errors[] = $LNG['Empty_subject'];
		}
		if (empty($_POST['message'])) {
			$errors[] = $LNG['Empty_message'];
		}
	}

	if ($submit && !$errors) {
		if ('edit' == $mode) {
			$privmsg->save();
		} else {
			# Check to see if the recipient has a full inbox, if so delete oldest
			$where = "user_id = {$to_userinfo['user_id']} AND pmr_status IN (".Message::STATUS_NEW.",".Message::STATUS_UNREAD.",".Message::STATUS_READ.")";
			$count = $db->TBL->privatemessages_recipients->count($where);
			if ($count >= $PMCFG->sentbox_max) {
				$limit = 1 + $count - $PMCFG->sentbox_max;
				$db->TBL->privatemessages_recipients->update(array(
					'pmr_status' => Message::STATUS_DELETED
				), "{$where} ORDER BY pm_id ASC LIMIT {$limit}");
			}

			$privmsg->recipients[] = $to_userinfo['user_id'];
			$privmsg->save();

			$db->exec("UPDATE {$db->TBL->users} SET user_new_privmsg = user_new_privmsg + 1 WHERE user_id = {$to_userinfo['user_id']}");
			if ($to_userinfo['user_notify_pm'] && !empty($to_userinfo['user_email']) && $to_userinfo['user_active']) {
				$template->TO_USERNAME    = $to_userinfo['username'];
				$template->USER_INBOX_URI = URL::index('&folder=inbox', true, true);
				$template->USER_PREFS_URI = URL::index('Your_Account&edit=prefs', true, true);
				\Dragonfly\Email::send($mailer_message,
					$LNG['Notification_subject'],
					$template->toString('Private_Messages/mail-notification'),
					$to_userinfo['user_email'],
					$to_userinfo['username'],
					$MAIN_CFG['global']['notify_from']);
			}
		}
		\Dragonfly::closeRequest($LNG['Message_sent'], 200, URL::index('&folder=inbox'));
	}
	else if (!$preview && !$errors)
	{
		if ($to_userid = $_GET->uint('u')) {
			$row = $db->uFetchRow("SELECT username FROM {$db->TBL->users}
			WHERE user_id = {$to_userid} AND user_id > 1");
			if ($row) {
				$to_username = $row[0];
			} else {
				$errors[] = $LNG['No_such_user'];
			}
		}

		if ('edit' == $mode) {
			$to_username = $privmsg->recipients[0]['username'];
		}
		else if ('reply' == $mode || 'quote' == $mode)
		{
			$privmsg->subject = 'Re: ' . preg_replace('/^Re:\s*/', '', $prev_msg->subject);
			$to_username = $prev_msg->username;
			if ('quote' == $mode) {
				$privmsg->text = '[quote="'.$to_username.'"]'.$prev_msg->text.'[/quote]';
				$mode = 'reply';
			}
		}
	}

	if ('compose' == $mode) {
		\Dragonfly\Page::title($LNG['Send_a_new_message'], false);
	} else if ('reply' == $mode) {
		\Dragonfly\Page::title($LNG['Send_a_reply'], false);
	} else if ('edit' == $mode) {
		\Dragonfly\Page::title($LNG['Edit_message'], false);
	}

	if ($PMCFG->allow_bbcode || $PMCFG->allow_smilies) {
		\Dragonfly\BBCode::pushHeaders($PMCFG->allow_smilies);
	}

	if ($preview && !$errors) {
		$template->assign_vars(array(
			'MESSAGE_TO' => $to_username,
			'MESSAGE_FROM' => $userinfo['username'],
			'POST_DATE' => $template->L10N->date($userinfo->dateformat),
		));
	}

	foreach ($errors as $error) {
		\Poodle\Notify::error($error);
	}

	$s_hidden_fields = array(
		array('name'=>'folder','value'=>$folder),
		array('name'=>'mode','value'=>$mode),
	);
	if ('edit' == $mode) {
		$s_hidden_fields[] = array('name'=>'p','value'=>$privmsg->id);
	}

	$template->private_message = $privmsg;
	$template->assign_vars(array(
		'PREVIEW_BOX' => $preview && !$errors,
		'USERNAME' => $to_username,
		'HIDDEN_FORM_FIELDS' => $s_hidden_fields,
		'U_SEARCH_USER' => URL::index('Your_Account&file=search&window', true, true),
	));

	$template->folder = array('name' => 'compose');
	$template->display('Private_Messages/compose');
}
