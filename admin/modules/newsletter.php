<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('newsletter')) { die('Access Denied'); }
\Dragonfly\Page::title(_NEWSLETTER);

function newsletter_selection($fieldname, $current) {
	static $groups;
	if (!isset($groups)) {
		global $db;
		$groups = array(0=>_NL_ALLUSERS, 1=>_SUBSCRIBEDUSERS, 2=>_NL_ADMINS);
		$groupsResult = $db->query("SELECT group_id, group_name FROM {$db->TBL->bbgroups} WHERE group_single_user=0");
		while (list($groupID, $groupName) = $groupsResult->fetch_row()) {
			$groups[($groupID+2)] = $groupName;
		}
	}
	$tmpgroups = $groups;
	return \Dragonfly\Output\HTML::select_box($fieldname, $current, $tmpgroups);
}

$subject = isset($_POST['subject']) ? $_POST['subject'] : '';
$content = isset($_POST['content']) ? $_POST->html('content') : '';
$group = isset($_POST['group']) ? intval($_POST['group']) : 1;

$html = $content
	? _HELLO.",<br/><br/>{$content}<p>"
		._NL_REGARDS.",</p><p>{$MAIN_CFG['global']['sitename']} "._STAFF
		."</p>"._NLUNSUBSCRIBE
	: '';

if (isset($_POST['discard'])) {
	URL::redirect(URL::admin('newsletter'));
} else if (isset($_POST['send'])) {
	$subject = $_POST['subject'];
	$n_group = intval($_POST['n_group']);

	if (empty($subject)) { cpg_error(sprintf(_ERROR_NOT_SET, _SUBJECT)); }
	if (empty($content)) { cpg_error(sprintf(_ERROR_NOT_SET, _CONTENT)); }
	ignore_user_abort(true);
	if ($n_group == 0) {
		$query = 'SELECT username, user_email FROM '.$db->TBL->users.' WHERE user_level > 0 AND user_id > 1';
		$count = $db->count('users', 'user_level > 0 AND user_id > 1');
	} else if ($n_group == 2) {
		$query = "SELECT aid, email FROM {$db->TBL->admins}";
		$count = $db->count('admins');
	} else if ($n_group > 2) {
		$n_group -= 2;
		$query = 'SELECT u.username, u.user_email FROM '.$db->TBL->users.' u, '.$db->TBL->bbuser_group.' g WHERE u.user_level>0 AND g.group_id='.$n_group.' AND u.user_id = g.user_id AND user_pending=0';
		$count = $db->sql_count($db->TBL->users.' u, '.$db->TBL->bbuser_group.' g WHERE u.user_level>0 AND g.group_id='.$n_group.' AND u.user_id = g.user_id AND user_pending=0');
	} else {
		$query = 'SELECT username, user_email FROM '.$db->TBL->users.' WHERE user_level > 0 AND user_id > 1 AND newsletter=1';
		$count = $db->count('users', 'user_level > 0 AND user_id > 1 AND newsletter=1');
	}

	$recipients = array();
	$limit = 1000; //$MAIN_CFG['email']['limit']
	set_time_limit(0);
	for ($offset=0; $offset<$count; $offset+=$limit) {
		$result = $db->query($query." LIMIT {$limit} OFFSET {$offset}");
		while (list($u_name, $u_email) = $result->fetch_row()) {
			if (is_email($u_email) > 0) { $recipients[$u_email] = $u_name; }
		}
	}
	if (empty($recipients)) {
		cpg_error('0 '._NL_RECIPS, _NEWSLETTER);
	}
	if (count($recipients) > 50) {
		while ($part_recips = array_splice($recipients,0,50)) {
			\Dragonfly\Email::send($mailer_message, $subject, $html, $part_recips, '', $MAIN_CFG['global']['adminmail'], $MAIN_CFG['global']['sitename']);
		}
	} else {
		\Dragonfly\Email::send($mailer_message, $subject, $html, $recipients, '', $MAIN_CFG['global']['adminmail'], $MAIN_CFG['global']['sitename']);
	}
/*
	foreach ($recipients AS $email => $name) {
		\Dragonfly\Email::send($mailer_message, $subject, sprintf($html, $name), $email, $name, $MAIN_CFG['global']['adminmail'], $MAIN_CFG['global']['sitename']);
	}
*/
	cpg_error(_NEWSLETTERSENT, _NEWSLETTER, \Dragonfly::$URI_ADMIN);
}

$title = _NEWSLETTER;
$num_users = 0;
$notes = $group_name = '';
if (isset($_POST['preview'])) {
	$title .= ' '._PREVIEW;
	\Dragonfly\Page::title($title, false);
	if (empty($subject)) { cpg_error(sprintf(_ERROR_NOT_SET, _SUBJECT)); }
	if (empty($html)) { cpg_error(sprintf(_ERROR_NOT_SET, _CONTENT)); }
	if ($group == 0) {
		$num_users = $db->count('users', 'user_level > 0 AND user_id > 1');
		$group_name = strtolower(_NL_ALLUSERS);
	} else if ($group == 2) {
		$num_users = $db->count('admins');
		$group_name = strtolower(_NL_ADMINS);
	} else if ($group > 2) {
		$group_id = $group-2;
		$num_users = $db->count('bbuser_group', "group_id={$group_id} AND user_pending=0");
		list($group_name) = $db->uFetchRow("SELECT group_name FROM {$db->TBL->bbgroups} WHERE group_id={$group_id}");
	} else {
		$num_users = $db->count('users', 'user_level > 0 AND newsletter=1');
		$group_name = strtolower(_SUBSCRIBEDUSERS);
	}
	if ($num_users > 500) {
		$notes = _MANYUSERSNOTE;
	} else if ($num_users < 1) {
		$notes = _NL_NOUSERS;
	}
}

\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
\Dragonfly\Output\Css::add('wysiwyg');

$OUT = \Dragonfly::getKernel()->OUT;
$OUT->newsletter = array(
	'title' => $title,
	'subject' => $subject,
	'body' => $content,
	'group_select' => newsletter_selection('group', $group),
	'group_name' => $group_name,
	'group_id' => $group,
	'recipients_count' => $num_users,
	'send_disabled' => ($num_users < 1),
	'preview' => $html,
	'notes' => $notes,
);
$OUT->display('admin/newsletter/index');
