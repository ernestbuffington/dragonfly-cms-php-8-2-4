<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('messages')) { die('Access Denied'); }
\Dragonfly\Page::title(_MESSAGES);

if (isset($_GET['status'])) {
	$db->exec("UPDATE {$db->TBL->message} SET active=ABS(active-1) WHERE mid=".$_GET->uint('status'));
	URL::redirect(URL::admin('messages'));
}
elseif (isset($_GET['edit']) && isset($_POST['content'])) {
	$id  = $_GET->uint('edit');
	$msg = array(
		'title'     => $_POST->txt('title'),
		'content'   => \Dragonfly\BBCode::encode($_POST['content']),
		'date'      => time(),
		'mlanguage' => (string)$_POST->txt('language'),
		'expire'    => 86400 * (int)$_POST->uint('expire_days'),
		'active'    => $_POST->bool('active'),
		'view'      => (int)$_POST->uint('view'),
	);
	if ($id) {
		if (!$msg['active'] || !$_POST->bool('chng_date')) {
			unset($msg['date']);
		}
		$db->TBL->message->update($msg, "mid={$id}");
	} else {
		$db->TBL->message->insert($msg);
	}
	URL::redirect(URL::admin('messages'));
}

if (isset($_GET['del']))
{
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		if (isset($_POST['confirm'])) {
			$db->exec("DELETE FROM {$db->TBL->message} WHERE mid=".$_GET->uint('del'));
			$db->optimize($db->TBL->message);
		}
		URL::redirect(URL::admin('messages'));
	}
	\Dragonfly\Page::confirm(URL::admin('&del='.$_GET->uint('del')), _REMOVEMSG);
}
else
{
	\Dragonfly\BBCode::pushHeaders(true);

	if (isset($_GET['edit'])) {
		$id = $_GET->uint('edit');
		$row = $db->uFetchAssoc("SELECT title, content, date, FLOOR(expire/86400) expire, active, view, mlanguage FROM {$db->TBL->message} WHERE mid={$id}");
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->msg = $row;
		$OUT->display('admin/messages/edit');
	}

	else {
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->messages = $db->query("SELECT
			mid,
			title,
			date,
			FLOOR(expire/86400) expire,
			active,
			view,
			mlanguage,
			CASE
				WHEN 0 = view THEN ".$db->quote(_MVALL)."
				WHEN 1 = view THEN ".$db->quote(_MVUSERS)."
				WHEN 2 = view THEN ".$db->quote(_MVADMIN)."
				WHEN 3 = view THEN ".$db->quote(_MVANON)."
				ELSE group_name
			END view_group,
			{$db->quote('?admin&op=messages&status=')} || mid as uri_status,
			{$db->quote('?admin&op=messages&edit=')} || mid as uri_edit,
			{$db->quote('?admin&op=messages&del=')} || mid as uri_delete
		FROM {$db->TBL->message}
		LEFT JOIN {$db->TBL->bbgroups} ON (group_id=view-3)
		ORDER BY date DESC");
		$OUT->display('admin/messages/index');
	}
}
