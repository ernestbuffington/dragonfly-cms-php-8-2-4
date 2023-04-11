<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($op)) { exit; }
require("modules/{$op}/include/load.inc");

function pushUserOnList($user)
{
	$OUT = \Dragonfly::getKernel()->OUT;
	$user['group_quota'] *= 1024; /* is stored in KB */
	if (!$user['user_active_cp'] || $user['user_level']==0 ) { $user['group_name'] = NULL; }
	$user['disk_usage_perc'] = 100 * $user['disk_usage'] / max(1,$user['group_quota']);
	$user['disk_usage_txt']  = $OUT->L10N->filesizeToHuman((int)$user['disk_usage']);
	$user['group_quota_txt'] = $OUT->L10N->filesizeToHuman((int)$user['group_quota']);
	$user['gallery_uri'] = $user['pic_count'] ? URL::index("&file=users&id={$user['user_id']}") : null;
	$user['edit_uri']    = URL::admin("&file=users&edit={$user['user_id']}");
	$user['delete_uri']  = URL::index("&file=delete&user={$user['user_id']}");
	$OUT->users[] = $user;
}

function list_users()
{
	global $CONFIG;

	$K    = Dragonfly::getKernel();
	$L10N = $K->L10N;
	$OUT  = $K->OUT;
	$SQL  = $K->SQL;

	$sort_codes = array(
		'name_a'  => 'username ASC',
		'name_d'  => 'username DESC',
		'group_a' => 'group_name ASC',
		'group_d' => 'group_name DESC',
		'reg_a'   => 'user_id ASC',
		'reg_d'   => 'user_id DESC',
		'pic_a'   => 'pic_count ASC',
		'pic_d'   => 'pic_count DESC',
		'disku_a' => 'disk_usage ASC',
		'disku_d' => 'disk_usage DESC',
	);

	$sort   = (isset($sort_codes[$_GET->raw('sort')]) ? $_GET['sort'] : 'reg_d');
	$limit  = 25;
	$offset = max(0,$_GET->uint('page')-1) * $limit;

	$OUT->user_search = mb_strtolower($_GET->raw('q'));
	$where = $OUT->user_search ? "WHERE user_nickname_lc LIKE '%{$SQL->escape_string($OUT->user_search)}%'" : '';

	$sort_options = array();
	foreach ($sort_codes as $key => $value) {
		$sort_options[] = array(
			'value'    => $key,
			'selected' => ($key == $sort),
			'label'    => $L10N['cpg_usermgr_php'][$key],
		);
	}
	$OUT->sort_options = $sort_options;

	$OUT->users = array();
	if (in_array($sort, array('pic_a', 'pic_d', 'disku_a', 'disku_d'))) {
		$where = $OUT->user_search ? "WHERE owner_id IN (SELECT user_id FROM {$SQL->TBL->users} WHERE user_nickname_lc LIKE '%{$SQL->escape_string($OUT->user_search)}%')" : '';
		list($user_count) = $SQL->uFetchRow("SELECT COUNT(DISTINCT owner_id) FROM {$CONFIG['TABLE_PICTURES']} {$where}");
		if (!$user_count) { cpg_error(ERR_NO_USERS, 404); }
		$result = $SQL->query("SELECT
			owner_id user_id,
			COUNT(pid) pic_count,
			SUM(total_filesize) disk_usage
		FROM {$CONFIG['TABLE_PICTURES']} {$where}
		GROUP BY 1
		ORDER BY {$sort_codes[$sort]}
		LIMIT {$limit} OFFSET {$offset}");
		while ($user = $result->fetch_assoc()) {
			$user = array_merge($user, $SQL->uFetchAssoc("SELECT
				user_id,
				username,
				user_email,
				group_name,
				user_active_cp,
				user_level,
				group_quota
			FROM {$SQL->TBL->users} u
			LEFT JOIN {$SQL->TBL->cpg_usergroups} g ON group_id = user_group_cp
			WHERE user_id = {$user['user_id']}"));
			pushUserOnList($user);
		}
	} else {
		list($user_count) = $SQL->uFetchRow("SELECT COUNT(*) FROM {$SQL->TBL->users} {$where}");
		if (!$user_count) { cpg_error(ERR_NO_USERS, 404); }
		$result = $SQL->query("SELECT
			user_id,
			username,
			user_email,
			group_name,
			user_active_cp,
			user_level,
			group_quota
		FROM {$SQL->TBL->users} u
		LEFT JOIN {$SQL->TBL->cpg_usergroups} g ON group_id = user_group_cp
		{$where}
		ORDER BY {$sort_codes[$sort]}
		LIMIT {$limit} OFFSET {$offset}");
		while ($user = $result->fetch_assoc()) {
			$user = array_merge($user, $SQL->uFetchAssoc("SELECT
				COUNT(pid) pic_count,
				SUM(total_filesize) disk_usage
			FROM {$CONFIG['TABLE_PICTURES']}
			WHERE owner_id = {$user['user_id']}"));
			pushUserOnList($user);
		}
	}
	$result->free();

	$OUT->pagination   = new \Poodle\Pagination(URL::admin('&file=users&page=${page}&sort='.$sort), $user_count, $offset, $limit);
	$OUT->user_on_page = sprintf(U_USER_ON_P_PAGES, $user_count, $OUT->pagination->count());
	$OUT->uri_new_user = URL::admin('admin&op=users#add-user');
	$OUT->display('coppermine/admin/users');
}

function edit_user($user_id)
{
	global $CONFIG;

	$K   = Dragonfly::getKernel();
	$OUT = $K->OUT;
	$SQL = $K->SQL;

	$user_data = $SQL->uFetchAssoc("SELECT username, user_active_cp, user_group_cp, user_group_list_cp FROM {$SQL->TBL->users} WHERE user_id = {$user_id}");
	if (!$user_data) { cpg_error(ERR_UNKNOWN_USER, 404); }

	$OUT->group_list = array();
	$result = $SQL->query("SELECT
			group_id,
			group_name
		FROM {$SQL->TBL->cpg_usergroups}
		ORDER BY group_name");
	$user_group_list = explode(',', $user_data['user_group_list_cp']);
	while ($group = $result->fetch_assoc()) {
		$group['main_group'] = ($group['group_id'] == $user_data['user_group_cp']);
		$group['in_group']   = in_array($group['group_id'], $user_group_list);
		$OUT->group_list[] = $group;
	}
	$OUT->user_data = $user_data;
	$OUT->display('coppermine/admin/user');
}

if (isset($_GET['edit']))
{
	$user_id = $_GET->uint('edit');
	if (USER_ID == $user_id && !can_admin()) { cpg_error(ERR_EDIT_SELF, 403); }
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		$user_group_cp = $_POST->uint('user_group_cp');
		$group_list = isset($_POST['group_list']) ? $_POST['group_list'] : '';
		$user_group_list = array();
		if (is_array($group_list)) {
			foreach ($group_list as $group) {
				if ($group != $user_group_cp) { $user_group_list[] = intval($group); }
			}
		}

		$db->exec("UPDATE {$db->TBL->users} SET
			user_active_cp     = ".intval($_POST->bool('user_active_cp')).",
			user_group_cp      = ".$user_group_cp.",
			user_group_list_cp = ".$db->quote(implode(',',$user_group_list))."
		WHERE user_id = {$user_id}");

		URL::redirect(URL::admin("&file=users"));
	}
	\Dragonfly\Page::title(U_TITLE);
	edit_user($user_id);
}
else
{
	\Dragonfly\Page::title(U_TITLE);
	list_users();
}
