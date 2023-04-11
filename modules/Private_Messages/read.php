<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

use Dragonfly\Modules\Private_Messages\Message as Message;

try {
	$privmsg = new Message($_GET->uint('p'));
} catch (\Exception $e) {
	cpg_error($LNG['No_match'], 404);
}

$recipient = null;
switch ($folder)
{
	case 'inbox':
		$recipient = $privmsg->getRecipient($userinfo->id);
		if (!$recipient || !in_array($recipient['status'], array(Message::STATUS_READ, Message::STATUS_NEW, Message::STATUS_UNREAD))) {
			cpg_error('', 404);
		}
		break;

	case 'outbox':
		if ($privmsg->user_id != $userinfo->id || !in_array($privmsg->status, array(Message::STATUS_NEW, Message::STATUS_UNREAD))) {
			cpg_error('', 404);
		}
		break;

	case 'sentbox':
		if ($privmsg->user_id != $userinfo->id || Message::STATUS_SENT != $privmsg->status) {
			cpg_error('', 404);
		}
		break;

	case 'savebox':
		$recipient = $privmsg->getRecipient($userinfo->id);
		if (!($recipient && Message::STATUS_SAVED == $recipient['status'])
		 && !($privmsg->user_id == $userinfo->id && Message::STATUS_SAVED == $privmsg->status)) {
			cpg_error('', 404);
		}
		break;

	default:
		cpg_error($LNG['No_such_folder']);
		break;
}

# Is this a new message in the inbox?
if ('inbox' == $folder && Message::STATUS_UNREAD == $recipient['status']) {
	# Update appropriate counter
	switch ($privmsg->status)
	{
		case Message::STATUS_NEW:
			$sql = "user_new_privmsg = user_new_privmsg - 1";
			--$userinfo->new_privmsg;
			break;
		case Message::STATUS_UNREAD:
			$sql = "user_unread_privmsg = user_unread_privmsg - 1";
			--$userinfo->unread_privmsg;
			break;
	}
	$db->query("UPDATE {$db->TBL->users} SET {$sql} WHERE user_id = {$userinfo->id}");
	$db->query("UPDATE {$db->TBL->privatemessages_recipients} SET pmr_status = ".Message::STATUS_READ." WHERE pm_id = {$privmsg->id}");

	# Check to see if the poster has a full sentbox, if so delete oldest
	$where = "user_id = {$privmsg->user_id} AND pm_status = ".Message::STATUS_SENT;
	$count = $db->TBL->privatemessages->count($where);
	if ($count >= $PMCFG->sentbox_max) {
		$limit = 1 + $count - $PMCFG->sentbox_max;
		$db->TBL->privatemessages->update(array(
			'pm_status' => Message::STATUS_DELETED
		), "{$where} ORDER BY pm_date ASC LIMIT {$limit}");
	}
}

#Pick a folder, any folder, so long as it's one below ...
$post_urls = array(
	'post' => URL::index('&file=compose'),
	'reply' => URL::index('&file=compose&mode=reply&p='.$privmsg->id),
	'quote' => URL::index('&file=compose&mode=quote&p='.$privmsg->id),
	'edit' => URL::index('&file=compose&mode=edit&p='.$privmsg->id)
);

$u_reply = $u_quote = $u_edit = '';
if ('inbox' == $folder) {
	$u_reply = $post_urls['reply'];
	$u_quote = $post_urls['quote'];
} else if ('outbox' == $folder) {
	$u_edit = $post_urls['edit'];
} else if ('savebox' == $folder && $recipient && Message::STATUS_SAVED == $recipient['status']) {
	$u_reply = $post_urls['reply'];
	$u_quote = $post_urls['quote'];
}

\Dragonfly\Page::title($privmsg->subject);
if ($PMCFG->allow_bbcode || $PMCFG->allow_smilies) {
	\Dragonfly\BBCode::pushHeaders($PMCFG->allow_smilies);
}

$template->folder = array('name' => $folder);

$template->assign_vars(array(
	'S_PM_ACTION' => URL::index('&folder='.$folder),
	'U_POST' => $post_urls['post'],
	'U_REPLY' => $u_reply,
	'U_QUOTE' => $u_quote,
	'U_EDIT' => $u_edit
));

if (!$recipient) {
	$recipient = $privmsg->recipients[0];
}

$template->assign_vars(array(
	'private_message' => $privmsg,
	'POST_DATE' => $template->L10N->date($userinfo->dateformat, $privmsg->date),
	'U_MESSAGE_FROM' => \Dragonfly\Identity::getProfileURL($privmsg->user_id),
	'U_MESSAGE_TO' => \Dragonfly\Identity::getProfileURL($recipient['id']),
));

if ('inbox' == $folder) {
	$s_hidden_fields = array(
		array('name'=>'folder','value'=>$folder),
		array('name'=>'mode','value'=>'compose'),
		array('name'=>'username','value'=>$privmsg->username),
	);
	$template->quickreply = array(
		'HIDDEN_FIELDS' => $s_hidden_fields,
		'SUBJECT' => 'Re: ' . preg_replace('/^Re:\s*/', '', $privmsg->subject),
		'QREPLY_MSG' => '[quote="'.$privmsg->username.'"]'.$privmsg->text.'[/quote]',
	);
	$template->pm_options = array(
		'allow_bbcode' => $PMCFG->allow_bbcode,
		'allow_smilies' => $PMCFG->allow_smilies,
	);
} else {
	$template->quickreply = false;
}

$template->display('Private_Messages/read');
