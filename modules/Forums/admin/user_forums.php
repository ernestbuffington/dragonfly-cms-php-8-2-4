<?php
/***************************************************************************
 *				  admin_user_forums.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 ***************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }

if (!$db->sql_count(FORUMS_TABLE)) {
	message_die(GENERAL_MESSAGE, 'You need to create a forum before you can set permissions.');
	return;
}

$user_id = $_POST->uint('user_id');

if ($user_id) {
	$db->query("DELETE FROM ".FORUMS_TABLE."_privileges WHERE user_id={$user_id}");
	if (!empty($_POST['forum_id_list']) && is_array($_POST['forum_id_list'])) {
		foreach ($_POST['forum_id_list'] as $forum_id) {
			$forum_id = (int)$forum_id;
			$db->query("INSERT INTO ".FORUMS_TABLE."_privileges (user_id, forum_id)
			VALUES ({$user_id}, {$forum_id})");
		}
	}
}

if (isset($_POST['username']) || $user_id) {
	if (isset($_POST['username'])) {
		$this_userdata = \Poodle\Identity\Search::byNickname($_POST['username'], true);
		if ($this_userdata) {
			$user_id = $this_userdata['user_id'];
		}
	} else {
		$this_userdata = \Poodle\Identity\Search::byID($user_id, true);
	}
	if (!$this_userdata) {
		message_die(GENERAL_MESSAGE, $lang['No_such_user']);
		return;
	}

	if ($this_userdata['user_level'] > 1) {
		message_die(GENERAL_MESSAGE, $_POST['username'].' - '.($this_userdata['user_level'] == 2 ? $lang['Auth_Admin'] :  $lang['Moderator']).'<br />'.$lang['No_cigar']);
		return;
	}

	$forums = array();
	foreach (BoardCache::categories() as $cat) {
		$forums[$cat['id']] = array('label' => $cat['title'], 'forums'=>array());
	}
	$tmp_forums = $db->uFetchAll("SELECT cat_id, f.forum_id, f.forum_name, f.auth_view, p.user_id as priv
	FROM ".FORUMS_TABLE." f
	LEFT JOIN ".FORUMS_TABLE."_privileges p ON (p.user_id= '{$user_id}' AND p.forum_id = f.forum_id)
	WHERE forum_type < 3
	ORDER BY forum_order");
	foreach ($tmp_forums as $forum) {
		$forums[$forum['cat_id']]['forums'][] = array(
			'id'    => $forum['forum_id'],
			'name'  => $forum['forum_name'],
			'priv'  => $forum['priv']
		);
	}
	$template->forums_grouped = $forums;
	unset($tmp_forums, $forums);

	$ug_info = $db->query("SELECT g.group_id, g.group_name
	FROM {$db->TBL->bbgroups} g, {$db->TBL->bbuser_group} ug
	WHERE ug.user_id = {$user_id}
	  AND g.group_id = ug.group_id
	  AND group_single_user = 0");
	$t_usergroup_list = array();
	if ($ug_info->num_rows) {
		while ($info = $ug_info->fetch_row()) {
			$t_usergroup_list[] = array('name'=>$info[1], 'href'=>URL::admin("&do=ug_auth&mode=group&g={$info[0]}"));
		}
	}

	$template->set_handle('body', 'Forums/admin/auth_uf');
	$template->assign_block_vars('switch_user_auth', array());
	$template->assign_vars(array(
		'USER_ID'     => $user_id,
		'USER_NAME'   => $this_userdata['username'],
		'USER_GROUPS' => $t_usergroup_list,
	));
}

else {
	# Select a user
	$template->set_handle('body', 'Forums/admin/user_select');
	$template->U_SEARCH_USER = URL::index("Your_Account&file=search&window", true, true);
}
