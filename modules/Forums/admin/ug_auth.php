<?php
/***************************************************************************
 *				  admin_ug_auth.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 ***************************************************************************/
/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/
/* Modifications made by CPG Dev Team http://cpgnuke.com		         */
/************************************************************************/

/* Applied rules:
 * TernaryToNullCoalescingRector
 */
 
if (!defined('ADMIN_PAGES')) { exit; }
if (!($count = $db->sql_count(FORUMS_TABLE))) {
	message_die(GENERAL_MESSAGE, 'You need to create a forum before you can set permissions.');
	return;
}

$mode = $_GET->txt('mode') ?: 'group';
$user_mode = ('user' == $mode);

if (!$user_mode && isset($_POST['g'])) {
	URL::redirect(URL::admin('&do=ug_auth&mode=group&g='.$_POST->uint('g')));
	exit;
}

if ($user_mode && isset($_POST['username'])) {
	$user = getusrdata($_POST['username'], 'user_id');
	if (!is_array($user)) {
		message_die(GENERAL_MESSAGE, $lang['No_such_user']);
		return;
	}
	URL::redirect(URL::admin('&do=ug_auth&mode=user&u='.$user['user_id']));
	exit;
}

$user_id  = $_GET->uint('u');
$group_id = $_GET->uint('g');
$adv = $_POST->bool('adv') ?: $_GET->bool('adv');

$field_names = array(
	'auth_view' => $lang['View'],
	'auth_read' => $lang['Read'],
	'auth_post' => $lang['Post'],
	'auth_reply' => $lang['Reply'],
	'auth_edit' => $lang['Edit'],
	'auth_delete' => $lang['Delete'],
	'auth_sticky' => $lang['Sticky'],
	'auth_announce' => $lang['Announce'],
	'auth_vote' => $lang['Vote'],
	'auth_pollcreate' => $lang['Pollcreate'],
	'auth_attachments' => $lang['Auth_attach'],
	'auth_download' => $lang['Auth_download']
);

$forum_auth_fields = array_keys($field_names);

function getAuthAccess()
{
	global $db, $forum_auth_fields, $group_id;
	$result = $db->query("SELECT forum_id, auth_mod, ".implode(',',$forum_auth_fields)." FROM ".AUTH_ACCESS_TABLE." WHERE group_id = {$group_id}");
	$auth_access = array();
	while ($forum = $result->fetch_assoc()) {
		$forum_id = $forum['forum_id'];
		unset($forum['forum_id']);
		$auth_access[$forum_id] = $forum;
	}
	return $auth_access;
}

function getUserGroupsAuthAccess($include_single_user=false)
{
	global $db, $forum_auth_fields, $user_mode, $user_id;
	$auth_access = array();
	/**
	 * This looks cool (and it is), but it might confuse people when they
	 * want to alter a user and visualy no changes are shown as another
	 * group membership still allows them the permission to do so.
	 */
	if ($user_mode) {
		/**
		 * First cleanup tables
		 *
		$db->exec("DELETE FROM {$db->TBL->bbuser_group} WHERE group_id NOT IN (SELECT group_id FROM {$db->TBL->bbgroups})");
		$db->exec("DELETE FROM ".AUTH_ACCESS_TABLE."
			WHERE group_id NOT IN (SELECT group_id FROM {$db->TBL->bbgroups})
			   OR forum_id NOT IN (SELECT forum_id FROM ".FORUMS_TABLE.")");
		*/
		$include_single_user = ($include_single_user ? '' : 'AND g.group_single_user = 0');
		$fields = array();
		foreach ($forum_auth_fields as $field) {
			$fields[] = "MAX({$field}) {$field}";
		}
		$result = $db->query("SELECT forum_id, MAX(auth_mod) auth_mod, ".implode(',',$fields)."
			FROM ".AUTH_ACCESS_TABLE." aa, {$db->TBL->bbuser_group} ug, {$db->TBL->bbgroups} g
			WHERE ug.user_id = {$user_id}
			  AND aa.group_id = ug.group_id
			  AND g.group_id = ug.group_id
			  {$include_single_user}
			GROUP BY forum_id");

		while ($forum = $result->fetch_assoc()) {
			$forum_id = $forum['forum_id'];
			unset($forum['forum_id']);
			$auth_access[$forum_id] = $forum;
		}
	}
	return $auth_access;
}

$user_level = 0;
if ($user_mode && $user_id) {
	list($user_level) = $db->uFetchRow("SELECT user_level FROM {$db->TBL->users} WHERE user_id = {$user_id}");
	//
	// Get group_id for this user_id
	//
	$row = $db->uFetchRow("SELECT g.group_id
		FROM {$db->TBL->bbuser_group} ug, {$db->TBL->bbgroups} g
		WHERE ug.user_id = {$user_id}
		  AND g.group_id = ug.group_id
		  AND g.group_single_user = 1");
	$group_id = $row ? $row[0] : 0;
}

if (isset($_POST['save']) && (($user_mode && $user_id) || (!$user_mode && $group_id))) {
	//
	// If a private user group does not exists for this user, create one.
	//
	if ($user_mode && $group_id < 1) {
		$group_id = \Dragonfly\Groups::createPrivate($user_id);
	}

	//
	// Carry out requests
	//
	if ($user_mode && $_POST['userlevel'] == 'admin' && $user_level != \Dragonfly\Identity::LEVEL_ADMIN) {
		//
		// Make user an admin
		//
		if ($userinfo['user_id'] != $user_id && $user_id != \Dragonfly\Identity::ANONYMOUS_ID) {
			$db->query("UPDATE {$db->TBL->users} SET user_level = ".\Dragonfly\Identity::LEVEL_ADMIN." WHERE user_id = {$user_id}");
		}
	} else {
		if ($user_mode && $_POST['userlevel'] == 'user' && $user_level == \Dragonfly\Identity::LEVEL_ADMIN) {
			//
			// Make admin a user (if already admin) ... ignore if you're trying
			// to change yourself from an admin to user!
			//
			if ($userinfo['user_id'] != $user_id) {
				$db->query("UPDATE {$db->TBL->users} SET user_level = ".\Dragonfly\Identity::LEVEL_USER." WHERE user_id = {$user_id}");
			}
		}
		// When administrator there's no need to provide custom access
		else if (!$user_mode || $user_level != \Dragonfly\Identity::LEVEL_ADMIN) {
			//
			// Set permissions for each forum
			// Only for the auth_ fields that are AUTH_ACL
			//
			$auth_access = getAuthAccess();
			$auth_groups_access = ($user_mode ? getUserGroupsAuthAccess() : array());

			$delete_ids = array();
			$result = $db->query("SELECT forum_id, ".implode(',',$forum_auth_fields)." FROM " . FORUMS_TABLE);
			while ($forum = $result->fetch_assoc()) {
				$forum_id = $forum['forum_id'];
				unset($forum['forum_id']);

				// When already in a group as moderator there's no need to provide custom access
				if (!empty($auth_groups_access[$forum_id]['auth_mod'])) {
					continue;
				}

				$fields = array('auth_mod');
				foreach ($forum as $auth_field => $acl) {
					if ($acl == AUTH_ACL) {
						$fields[] = $auth_field;
					}
				}

				$auth_group = $auth_access[$forum_id] ?? array_fill_keys($fields, 0);
				$auth_post  = $_POST['private'][$forum_id] ?? array_fill_keys($fields, 0);
				if (!is_array($auth_post)) {
					// Simple mode is used
					$auth_post = array_fill_keys($fields, $auth_post);
				}
				$auth_post['auth_mod'] = !empty($_POST['moderator'][$forum_id]);

				$update = array();
				foreach ($fields as $auth_field) {
					$auth_post[$auth_field] = ($auth_post['auth_mod'] || $auth_post[$auth_field]);
					if (empty($auth_post[$auth_field]) != empty($auth_group[$auth_field])
					 || (empty($auth_post['auth_mod']) && !empty($auth_group['auth_mod'])))
					{
						$update[$auth_field] = $auth_post[$auth_field] ? 1 : 0;
					}
				}
				if (count($update)) {
					if (!array_sum($update)) {
						$delete_ids[] = $forum_id;
					} else if (isset($auth_access[$forum_id])) {
						$sql_values = array();
						foreach ($update as $auth_type => $value) {
							$sql_values[] = "{$auth_type} = {$value}";
						}
						$db->query("UPDATE " . AUTH_ACCESS_TABLE . " SET ".implode(',',$sql_values)."
							WHERE group_id = {$group_id} AND forum_id = {$forum_id}");
					} else {
						$db->query("INSERT INTO " . AUTH_ACCESS_TABLE . " (forum_id, group_id, ".implode(',',array_keys($update)).")
							VALUES ({$forum_id}, {$group_id}, ".implode(',',$update).")");
					}
				}
			}

			if ($delete_ids) {
				$db->query("DELETE FROM " . AUTH_ACCESS_TABLE . " WHERE group_id = {$group_id} AND forum_id IN (".implode(',',$delete_ids).")");
			}
		}

		//
		// Update user level to mod for appropriate users
		//
		$mods = array();
		$result = $db->query("SELECT u.user_id
			FROM " . AUTH_ACCESS_TABLE . " aa, {$db->TBL->bbuser_group} ug, {$db->TBL->users} u
			WHERE ug.group_id = aa.group_id
			  AND u.user_id = ug.user_id
			  AND u.user_level NOT IN (".\Dragonfly\Identity::LEVEL_MOD.", ".\Dragonfly\Identity::LEVEL_ADMIN.")
			GROUP BY u.user_id
			HAVING SUM(aa.auth_mod) > 0");
		while ($row = $result->fetch_row()) {
			$mods[] = $row[0];
		}
		$result->free();
		if (!empty($mods)) {
			$db->query("UPDATE {$db->TBL->users} SET user_level = ".\Dragonfly\Identity::LEVEL_MOD.' WHERE user_id IN ('.implode(',', $mods).')');
		}

		//
		// Update user level to user for appropriate users
		//
		$mods = array();
		$result = $db->query("SELECT u.user_id
			FROM {$db->TBL->users} u
			LEFT JOIN {$db->TBL->bbuser_group} ug ON ug.user_id = u.user_id
			LEFT JOIN " . AUTH_ACCESS_TABLE . " aa ON aa.group_id = ug.group_id
			WHERE u.user_level = " . \Dragonfly\Identity::LEVEL_MOD . "
			GROUP BY u.user_id
			HAVING COALESCE(MAX(aa.auth_mod),0) = 0");
		while ($row = $result->fetch_row()) { $mods[] = $row[0]; }
		$result->free();
		if (!empty($mods)) {
			$db->query("UPDATE {$db->TBL->users} SET user_level = ".\Dragonfly\Identity::LEVEL_USER." WHERE user_id IN (".implode(',', $mods).")");
		}
	}
	BoardCache::cacheDelete('moderators');
	\Dragonfly::closeRequest($lang['Auth_updated'], 200, $_SERVER['REQUEST_URI']);
}

else if (($user_mode && $user_id) || (!$user_mode && $group_id)) {
	//
	// Front end
	//

	$forum_access = $db->uFetchAll("SELECT forum_id, cat_id, forum_name, ".implode(',',$forum_auth_fields)." FROM " . FORUMS_TABLE . " ORDER BY forum_order");

	$auth_access = getAuthAccess();

	$is_admin = ($user_mode && $user_level == \Dragonfly\Identity::LEVEL_ADMIN && $user_id != \Dragonfly\Identity::ANONYMOUS_ID);

	// Force advanced mode when required, else fill $forum_auth_level
	if (!$adv && !$is_admin) {
		$forum_auth_level = array();
		foreach ($forum_access as $forum) {
			$forum_id = $forum['forum_id'];
			$forum_auth_level[$forum_id] = AUTH_ALL;
			if (isset($auth_access[$forum_id]) && empty($auth_access[$forum_id]['auth_mod'])) {
				$auth_ug = $auth_access[$forum_id];
				unset($prev_acl_setting);
				foreach ($forum_auth_fields as $key) {
					if (AUTH_ACL == $forum[$key]) {
						$auth_ug[$key] = !empty($auth_ug[$key]);
						if (isset($prev_acl_setting) && $prev_acl_setting != $auth_ug[$key]) {
							$adv = 1;
							break 2;
						}
						$prev_acl_setting = $auth_ug[$key];
						$forum_auth_level[$forum_id] = AUTH_ACL;
					}
				}
			} else {
				foreach ($forum_auth_fields as $key) {
					if (AUTH_ACL == $forum[$key]) {
						$forum_auth_level[$forum_id] = AUTH_ACL;
					}
				}
			}
		}
	}

	$auth_groups_access = (!$is_admin&&$user_mode) ? getUserGroupsAuthAccess() : array();

	$forums = array();
	foreach (BoardCache::categories() as $cat) {
		$forums[$cat['id']] = array('label' => $cat['title'], 'forums'=>array());
	}

	foreach ($forum_access as $forum) {
		$forum_id = $forum['forum_id'];
		$auth_ug = $auth_access[$forum_id] ?? array();
		$auth_gr = $auth_groups_access[$forum_id] ?? array();
		foreach ($forum_auth_fields as $key) {
			switch ($forum[$key])
			{
				case AUTH_ALL:
				case AUTH_REG:
					$auth_ug[$key] = $auth_gr[$key] = 1;
					break;

				case AUTH_ACL:
					$auth_ug[$key] = ($is_admin || !empty($auth_ug['auth_mod']) || !empty($auth_ug[$key]));
					$auth_gr[$key] = ($is_admin || !empty($auth_gr['auth_mod']) || !empty($auth_gr[$key]));
					break;

				case AUTH_MOD:
					$auth_ug[$key] = ($is_admin || !empty($auth_ug['auth_mod']));
					$auth_gr[$key] = ($is_admin || !empty($auth_gr['auth_mod']));
					break;

				case AUTH_ADMIN:
					$auth_ug[$key] = $auth_gr[$key] = $is_admin;
					break;

				default:
					$auth_ug[$key] = $auth_gr[$key] = 0;
					break;
			}
		}

		$optionlist_acl = array();
		if (!$adv) {
			$select = '✓'; // ✔
			// When administrator or already in a group as moderator there's no need to provide custom access
			if (!$is_admin && empty($auth_gr['auth_mod'])) {
				if (AUTH_ACL == $forum_auth_level[$forum_id]) {
					$allowed = 1;
					foreach ($forum_auth_fields as $field_name) {
						if (!$auth_ug[$field_name] && $forum[$field_name] == AUTH_ACL) {
							$allowed = 0;
							break;
						}
					}
					$select = '<input type="checkbox" name="private[' . $forum_id . ']"';
					if ($allowed) {
						$select .= ' checked=""';
					}
					$select .= '/>';
				} else {
					foreach ($forum_auth_fields as $field_name) {
						if (empty($auth_ug[$field_name]) && empty($auth_gr[$field_name])) {
							$select = '';
							break;
						}
					}
				}
			}
			$optionlist_acl[] = $select;
		} else {
			foreach ($forum_auth_fields as $field_name) {
				$select = '✓'; // ✔
				// When administrator or already in a group as moderator there's no need to provide custom access
				if (!$is_admin && empty($auth_gr['auth_mod'])) {
					if ($forum[$field_name] == AUTH_ACL) {
						$select = '<input type="checkbox" name="private[' . $forum_id . '][' . $field_name . ']"';
						if (!empty($auth_ug[$field_name])) {
							$select .= ' checked=""';
						}
						$select .= '/>';
					} else if (empty($auth_ug[$field_name]) && empty($auth_gr[$field_name])) {
						$select = '';
					}
				}
				$optionlist_acl[] = $select;
			}
		}

		//
		// Is user a moderator?
		//
		if ($is_admin || !empty($auth_gr['auth_mod'])) {
			$optionlist_acl[] = '✓'; // ✔
		} else {
			$optionlist_acl[] = '<input type="checkbox" name="moderator[' . $forum_id . ']"' . (empty($auth_ug['auth_mod']) ? '' : ' checked=""') . '/>';
		}

		$forums[$forum['cat_id']]['forums'][] = array(
			'id' => $forum_id,
			'name' => $forum['forum_name'],
			'href_auth' => URL::admin("&do=forumauth&f={$forum_id}"),
			'acl_options' => $optionlist_acl,
		);
	}

	$template->forums_grouped = $forums;
	unset($forums);

	$auth_name = null;
	$t_usergroup_list = array();
	if ($user_mode) {
		list($auth_name) = $db->uFetchRow("SELECT username FROM {$db->TBL->users} WHERE user_id = {$user_id}");
		$result = $db->query("SELECT g.group_id, g.group_name
		FROM {$db->TBL->bbgroups} g, {$db->TBL->bbuser_group} ug
		WHERE ug.user_id = {$user_id} AND g.group_id = ug.group_id AND g.group_single_user=0");
		while ($row = $result->fetch_assoc()) {
			$t_usergroup_list[] = '<a href="'.URL::admin('&do=ug_auth&mode=group&g='.$row['group_id']).'">' . $row['group_name'] . '</a>';
		}
		$t_usergroup_list = ($t_usergroup_list ? implode(', ',$t_usergroup_list) : $lang['None']);
		$template->USER_GROUPS = $lang['Group_memberships'] . ' : ' . $t_usergroup_list;
	} else {
		list($auth_name) = $db->uFetchRow("SELECT group_name FROM {$db->TBL->bbgroups} WHERE group_id = {$group_id}");
		$result = $db->query("SELECT u.user_id, u.username
		FROM {$db->TBL->users} u, {$db->TBL->bbuser_group} ug
		WHERE ug.group_id = {$group_id} AND u.user_id = ug.user_id AND ug.user_pending = 0");
		while ($row = $result->fetch_assoc()) {
			$t_usergroup_list[] = '<a href="'.URL::admin('&do=ug_auth&mode=user&u='.$row['user_id']).'">' . $row['username'] . '</a>';
		}
		$t_usergroup_list = ($t_usergroup_list ? implode(', ',$t_usergroup_list) : $lang['None']);
		$template->GROUP_MEMBERS = $lang['Usergroup_members'] . ' : ' . $t_usergroup_list;
	}
	unset($t_usergroup_list);
	$result->free();

	$template->acl_types = $adv ? $field_names : array($lang['Simple_Permission']);
	$template->acl_types[] = $lang['Moderator'];

	$template->set_handle('body', 'Forums/admin/auth_ug');

	$u_ug_switch = ($user_mode ? "u={$user_id}" : "g={$group_id}");
	$template->assign_vars(array(
		'AUTH_NAME' => $auth_name,
		'auth_user' => $user_mode,
		'auth_user_is_admin' => $is_admin,
		'U_SWITCH_MODE' => URL::admin("&do=ug_auth&mode={$mode}&{$u_ug_switch}&adv=".($adv?0:1)),
		'S_ADV_MODE'    => $adv,
	));
}

else if ($user_mode) {
	//
	// Select a user
	//
	$template->set_handle('body', 'Forums/admin/ug_auth_search_user');
	$template->assign_vars(array(
		'U_SEARCH_USER' => URL::index("Your_Account&file=search&window", true, true)
	));
}

else {
	//
	// Select a group
	//
	$template->set_handle('body', 'Forums/admin/ug_auth_select_group');
	$template->forum_groups = $db->query("SELECT
		group_id id,
		group_name name
	FROM {$db->TBL->bbgroups}
	WHERE group_single_user <> 1
	ORDER BY group_name");
}
