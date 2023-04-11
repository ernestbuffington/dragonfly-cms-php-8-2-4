<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('members')) { exit('Access Denied'); }
Dragonfly::getKernel()->L10N->load('Your_Account');
\Dragonfly\Page::title(_EDITUSERS, false);

require_once 'modules/Your_Account/functions.php';

function main()
{
	$K = Dragonfly::getKernel();
	$db = $K->SQL;

	if (XMLHTTPRequest) {
		$result = array();
		switch ($_GET['show'])
		{
		case 'tmpusers':
			$result = $db->uFetchAll("SELECT request_key, user_nickname username, user_email FROM {$db->TBL->users_request} WHERE request_type = 0 ORDER BY user_nickname");
			break;
		case 'sususers':
			$result = $db->uFetchAll("SELECT user_id, username, user_email, susdel_reason FROM {$db->TBL->users} WHERE user_level=0 AND user_id>1 ORDER BY username");
			break;
		case 'delusers':
			$result = $db->uFetchAll("SELECT user_id, username, user_email, susdel_reason FROM {$db->TBL->users} WHERE user_level<0 AND user_id>1 ORDER BY username");
			break;
		}
		header('Content-Type: application/json');
		echo json_encode($result);
		return;
	}

	$counts = array($db->TBL->users_request->count('request_type = 0'),0,0,0);
	$qr = $db->query("SELECT
		CASE
			WHEN user_level<0 THEN 3
			WHEN user_level=0 THEN 2
			ELSE 1
		END,
		COUNT(*)
	FROM {$db->TBL->users}
	WHERE user_id>1
	GROUP BY 1");
	while ($r = $qr->fetch_row()) { $counts[$r[0]] = $r[1]; }

	$TPL = $K->OUT;
	$TPL->users_waiting_count   = $counts[0];
	$TPL->users_active_count    = $counts[1];
	$TPL->users_suspended_count = $counts[2];
	$TPL->users_deleted_count   = $counts[3];

	$limit = 20;
	$waiting_offset   = (max(1, $_GET->uint('waiting_page'))-1) * $limit;
	$active_offset    = (max(1, $_GET->uint('active_page'))-1) * $limit;
	$suspended_offset = (max(1, $_GET->uint('suspended_page'))-1) * $limit;
	$deleted_offset   = (max(1, $_GET->uint('deleted_page'))-1) * $limit;

	$TPL->users_waiting_pagination   = new \Poodle\Pagination('?admin&op=users&waiting_page=${page}#waiting-users',     $counts[0], $waiting_offset,   $limit);
	$TPL->users_active_pagination    = new \Poodle\Pagination('?admin&op=users&active_page=${page}#active-users',       $counts[1], $active_offset,    $limit);
	$TPL->users_suspended_pagination = new \Poodle\Pagination('?admin&op=users&suspended_page=${page}#suspended-users', $counts[2], $suspended_offset, $limit);
	$TPL->users_deleted_pagination   = new \Poodle\Pagination('?admin&op=users&deleted_page=${page}#deleted-users',     $counts[3], $deleted_offset,   $limit);

	$TPL->users_search_username = isset($_POST['username']) ? trim($_POST['username']) : null;
	$search = mb_strtolower($TPL->users_search_username);
	if ($search) {
		$search = " AND user_nickname_lc LIKE '%{$db->escape_string($search)}%'";
	}

	if ('regdate' == $_GET->txt('order')) {
		$order = 'user_regdate';
		$sort  = 'asc' === $_GET->txt('sort') ? 'ASC' : 'DESC';
	} else {
		$order = 'username';
		$sort  = 'desc' === $_GET->txt('sort') ? 'DESC' : 'ASC';
	}
	$TPL->active_users = $db->query("SELECT user_id AS id, username AS nickname, user_email AS email
	FROM {$db->TBL->users}
	WHERE user_level>0 AND user_id>1 {$search}
	ORDER BY {$order} {$sort}
	LIMIT {$limit} OFFSET {$active_offset}");

	if ($counts[0]) {
		$TPL->waiting_users = $db->query("SELECT request_key AS id, user_nickname AS nickname, user_email AS email
		FROM {$db->TBL->users_request}
		WHERE request_type = 0
		ORDER BY user_nickname
		LIMIT {$limit} OFFSET {$waiting_offset}");
	} else {
		$TPL->waiting_users = array();
	}

	if ($counts[2]) {
		$TPL->suspended_users = $db->query("SELECT user_id AS id, username AS nickname, user_email AS email, susdel_reason
		FROM {$db->TBL->users}
		WHERE user_level=0 AND user_id>1
		ORDER BY username
		LIMIT {$limit} OFFSET {$suspended_offset}");
	} else {
		$TPL->suspended_users = array();
	}

	if ($counts[3]) {
		$TPL->deleted_users = $db->query("SELECT user_id AS id, username AS nickname, user_email AS email, susdel_reason
		FROM {$db->TBL->users}
		WHERE user_level<0 AND user_id>1
		ORDER BY username
		LIMIT {$limit} OFFSET {$deleted_offset}");
	} else {
		$TPL->deleted_users = array();
	}

	// Add the additional fields to add user form if activated
	$sections = array();
	$result = $db->query("SELECT * FROM {$db->TBL->users_fields} WHERE visible > 0 ORDER BY section");
	if ($result->num_rows) {
		$section = array('id'=>0, 'title'=>_MA_PROFILE_INFO, 'fields'=>array());
		while ($row = $result->fetch_assoc()) {
			if (3 == $row['section'] && !$section['id']) {
				if ($section['fields']) { $sections[] = $section; }
				$section = array('id'=>3, 'title'=>_MA_PRIVATE, 'fields'=>array());
			} else if (5 == $row['section'] && $section['id'] != 5) {
				if ($section['fields']) { $sections[] = $section; }
				$section = array('id'=>5, 'title'=>_MA_PREFERENCES, 'fields'=>array());
			}
			$field = \Dragonfly\Identity\Fields::tpl_field($row);
			if ($field) $section['fields'][] = $field;
		}
		if ($section['fields']) { $sections[] = $section; }
	}
	$TPL->create_user_sections = $sections;

	$TPL->display('admin/users/index');
}

if (Dragonfly::isDemo()) {
	main();
}

if (isset($_POST['wait']))
{
	include('admin/modules/users_wait.inc');
}

else if (isset($_POST['susdel']) && isset($_POST['suspended_users']))
{
	if ($_SESSION['CPG_SESS']['admin']['page'] != 'users') {
		cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
	}

	$susdel = $_POST['susdel'];
	if (empty($susdel) || empty($_POST['suspended_users'])) {
		cpg_error(sprintf(_ERROR_NOT_SET, 'Member'), _SEC_ERROR);
	}

	$members = $_POST['suspended_users'];
	if (is_array($members)) {
		$members = implode(',', $_POST['suspended_users']);
	}

	if ('restoreUser' == $susdel) {
		\Dragonfly\Page::title(_RESTOREUSER, false);
		$TPL = Dragonfly::getKernel()->OUT;
		$TPL->suspended_ids   = $members;
		$TPL->suspended_users = $db->query("SELECT username AS nickname FROM {$db->TBL->users} WHERE user_id IN ({$members})");
		$TPL->display('admin/users/suspended_restore');
	} else if ('restoreUserConf' == $susdel) {
		$result = $db->query("SELECT user_email FROM {$db->TBL->users} WHERE user_id IN ({$members})");
		while ($newuser = $result->fetch_row()) {
			\Dragonfly\Email::send($dummy, _ACCTRESTORE, _SORRYTO." {$MAIN_CFG->global->sitename} "._HASRESTORE, $newuser[0]);
		}
		$db->query("UPDATE {$db->TBL->users} SET user_level=1 WHERE user_id IN ({$members})");
		URL::redirect(URL::admin());
	}
}

else if (isset($_POST['avatargallery']) || isset($_GET['avatargallery']))
{
	\Dragonfly\Page::title(_EDITUSER, false);
	require('header.php');
	OpenTable();
	require('modules/Your_Account/avatars.php');
	if (!($memberinfo = getusrdata($_GET['id']))) {
		echo _NOINFOFOR.' ID '.$_GET['id'];
	} else {
		display_avatar_gallery($memberinfo);
	}
	CloseTable();
}

else if (isset($_GET['add']))
{
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		if ($_SESSION['CPG_SESS']['admin']['page'] != 'users') {
			cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
		}

		$username = trim($_POST['username']);
		$email    = trim($_POST['email']);
		$password = $_POST['password'];
		try {
			\Dragonfly\Identity\Validate::password($password, (string)$_POST['password_confirm']);
			\Dragonfly\Identity\Validate::nickname($username);
			\Dragonfly\Identity\Validate::email($email);
			// Check the additional activated fields
			$fields = \Dragonfly\Identity\Fields::fetchFromPost();
		} catch (Exception $e) {
			cpg_error($e->getMessage(), 409);
		}

		$sqldata = array(
			'username'         => $username,
			'user_email'       => $email,
			'user_regdate'     => time(),
			'user_nickname_lc' => mb_strtolower($username),
			'user_avatar'      => $MAIN_CFG->avatar->default,
		);
		foreach ($fields as $k => $v) {
			$sqldata[$k] = $v['value'];
		}
		$uid = $db->TBL->users->insert($sqldata, 'user_id');

		if (empty($password)) { $password = \Poodle\Auth::generatePassword(max(12, $MAIN_CFG->member->minpass + 2)); }
		\Poodle\Identity\Search::byID($uid)->updateAuth(1, $username, $password);

		$message = _WELCOMETO." {$MAIN_CFG->global->sitename}!\n\n"._YOUUSEDEMAIL.' '._TOREGISTER." {$MAIN_CFG->global->sitename}.\n\n "._FOLLOWINGMEM."\n"._USERNAME.": $username\n"._PASSWORD.": $password";
		\Dragonfly\Email::send($dummy, _ACTIVATIONSUB, $message, $email, $username);
		URL::redirect(URL::admin());
	} else {
		URL::redirect(URL::admin('users').'#add-user');
	}
}

else if (isset($_GET['id']))
{
	if (empty($_GET['id'])) {
		URL::redirect(URL::admin('users').'#active-users');
	}
	if (empty($_GET['edit'])) {
		$_GET['edit'] = 'admin';
	}
	$identity = \Poodle\Identity\Search::byID($_GET['id']);
	if (empty($identity)) {
		cpg_error(_NOINFOFOR.' ID '.$_GET['id']);
	}
	require('modules/Your_Account/edit_profile.php');
	if ('POST' === $_SERVER['REQUEST_METHOD'] && !isset($_POST['submitavatar'])) {
		if ($_SESSION['CPG_SESS']['admin']['page'] != 'users') {
			cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
		}
		saveuser($identity);
	} else {
		\Dragonfly\Page::title(_EDITUSER, false);
		require('header.php');
		OpenTable();
		$identity = \Poodle\Identity\Search::byID($_GET['id']);
		if (empty($identity)) {
			echo _NOINFOFOR.' ID '.$_GET['id'];
		} else {
			edituser($identity);
		}
		CloseTable();
	}
}

else
{
	main();
}
