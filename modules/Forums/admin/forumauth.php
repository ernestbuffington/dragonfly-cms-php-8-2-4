<?php
/***************************************************************************
 *				  admin_forumauth.php
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
/* Modifications made by CPG Dev Team http://cpgnuke.com		*/
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
//
// Start program - define vars
//
//	      View      Read      Post      Reply     Edit      Delete    Sticky    Announce  Vote      Poll      Upload    Download
$simple_auth_ary = array(
	array(AUTH_ALL, AUTH_ALL, AUTH_ALL, AUTH_ALL, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_MOD, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_ALL),
	array(AUTH_ALL, AUTH_ALL, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_MOD, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_ALL),
	array(AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_MOD, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_REG),
	array(AUTH_ALL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_MOD, AUTH_ACL, AUTH_ACL, AUTH_MOD, AUTH_ACL),
	array(AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_MOD, AUTH_ACL, AUTH_ACL, AUTH_MOD, AUTH_ACL),
	array(AUTH_ALL, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD),
	array(AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD),
	array(AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN, AUTH_ADMIN),
);

$simple_auth_types = array(
	$lang['Public'],
	$lang['Registered'],
	$lang['Registered'] . ' [' . $lang['Hidden'] . ']',
	$lang['Private'],
	$lang['Private'] . ' [' . $lang['Hidden'] . ']',
	$lang['Moderators'],
	$lang['Moderators'] . ' [' . $lang['Hidden'] . ']',
	$lang['Administrators'],
);

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
	'auth_download' => $lang['Auth_download'],
);
$forum_auth_fields = array_keys($field_names);

$forum_auth_levels = array(
	AUTH_ALL   => $lang['Forum_ALL'],
	AUTH_REG   => $lang['Forum_REG'],
	AUTH_ACL   => $lang['Forum_PRIVATE'],
	AUTH_MOD   => $lang['Forum_MOD'],
	AUTH_ADMIN => $lang['Forum_ADMIN']
);

if (isset($_POST['f'])) {
	URL::redirect(URL::admin('&do=forumauth&f='.$_POST->uint('f')));
	exit;
}
$forum_id = $_GET->uint('f');

//
// Start program proper
//
if (isset($_POST['save'])) {
	if ($forum_id) {
		$sql = array();
		if (isset($_POST['simpleauth'])) {
			$simple_ary = $simple_auth_ary[$_POST['simpleauth']];
			if (is_array($simple_ary)) {
				for ($i = 0; $i < count($simple_ary); $i++) {
					$sql[] = $forum_auth_fields[$i] . ' = ' . $simple_ary[$i];
				}
			}
		} else {
			for ($i = 0; $i < count($forum_auth_fields); $i++) {
				$value = $_POST[$forum_auth_fields[$i]];
				if ( $forum_auth_fields[$i] == 'auth_vote' ) {
					if ( $_POST['auth_vote'] == AUTH_ALL ) {
						$value = AUTH_REG;
					}
				}
				$sql[] = $forum_auth_fields[$i] . ' = ' . $value;
			}
		}

		if ($sql) {
			$db->query("UPDATE " . FORUMS_TABLE . " SET ".implode(', ',$sql)." WHERE forum_id = {$forum_id}");
		}
	}

	\Dragonfly::closeRequest($lang['Forum_auth_updated'], 200, URL::admin('&do=forumauth&f='.$forum_id));
	return;

} // End of save

function hasAuthAccess($forum, $ug, $key, $is_admin=false)
{
	switch ($forum[$key])
	{
	case AUTH_ALL:
	case AUTH_REG:   return 1;
	case AUTH_ACL:   return ($is_admin || !empty($ug['auth_mod']) || !empty($ug[$key]));
	case AUTH_MOD:   return ($is_admin || !empty($ug['auth_mod']));
	case AUTH_ADMIN: return $is_admin;
	}
	return 0;
}

//
// Get required information, either all forums if
// no id was specified or just the requsted if it
// was
//
if (!$forum_id) {
	//
	// Output the selection table if no forum id was specified
	//
	$template->board_categories = array();
	$result = $db->query("SELECT cat_id id, cat_title title FROM " . CATEGORIES_TABLE . " ORDER BY cat_order");
	while ($row = $result->fetch_assoc()) {
		$row['forums'] = array();
		$template->board_categories[$row['id']] = $row;
	}

	$result = $db->query("SELECT
		cat_id,
		forum_id,
		forum_name
	FROM " . FORUMS_TABLE . "
	ORDER BY forum_order ASC");
	while ($forum = $result->fetch_row()) {
		$template->board_categories[$forum[0]]['forums'][] = array(
			'id' => $forum[1],
			'name' => $forum[2],
		);
	}

	$template->set_handle('body', 'Forums/admin/auth_select_forum');
} else {
	//
	// Output the authorisation details if an id was
	// specified
	//
	$template->set_handle('body', 'Forums/admin/auth_forum');

	$forum = $db->uFetchAssoc("SELECT * FROM " . FORUMS_TABLE . " WHERE forum_id = {$forum_id}");

	$matched_type = -1;
	foreach ($simple_auth_ary as $key => $auth_levels) {
		$matched = 1;
		foreach ($auth_levels as $i => $level) {
			if ($forum[$forum_auth_fields[$i]] != $level) {
				$matched = 0;
			}
		}
		if ($matched) {
			$matched_type = $key;
			break;
		}
	}

	//
	// If we didn't get a match above then we
	// automatically switch into 'advanced' mode
	//
	$adv = isset($_GET['adv']) ? $_GET->bool('adv') : !$matched;

	$template->forum_auth_titles = array();
	$template->forum_auth_selects = array();

	if ($adv) {
		//
		// Output values of individual
		// fields
		//
		foreach ($forum_auth_fields as $field) {
			$select = '';
			foreach ($forum_auth_levels as $level => $label) {
				$selected = ( $forum[$field] == $level ) ? ' selected="selected"' : '';
				$select .= '<option value="' . $level . '"' . $selected . '>' . $label . '</option>';
			}
			$template->forum_auth_titles[] = $field_names[$field];
			$template->forum_auth_selects[] = '<select name="' . $field . '">'.$select.'</select>';
		}
	} else {
		if (0 > $matched_type) {
			if (AUTH_ALL == $forum['auth_post']) {
				$matched_type = 0;
			} else if (AUTH_ADMIN == $forum['auth_post']) {
				$matched_type = 7;
			} else {
				if (AUTH_REG == $forum['auth_post']) { $matched_type = 1; }
				if (AUTH_ACL == $forum['auth_post']) { $matched_type = 3; }
				if (AUTH_MOD == $forum['auth_post']) { $matched_type = 5; }
				if (AUTH_ALL != $forum['auth_view']) { ++$matched_type; }
			}
		}

		$select = '';
		foreach ($simple_auth_types as $i => $type) {
			$selected = ( $matched_type == $i ) ? ' selected="selected"' : '';
			$select .= '<option value="' . $i . '"' . $selected . '>' . $type . '</option>';
		}
		$template->forum_auth_titles[] = $lang['Simple_mode'];
		$template->forum_auth_selects[] = '<select name="simpleauth">'.$select.'</select>';
	}

	$template->forum_auth_groups = array();
	$auth = $db->query("SELECT group_id id, group_name name, auth_mod, ".implode(', ',$forum_auth_fields)."
	FROM ".AUTH_ACCESS_TABLE."
	INNER JOIN {$db->TBL->bbgroups} USING (group_id)
	WHERE forum_id = {$forum_id}
	  AND group_single_user = 0");
	while ($group = $auth->fetch_assoc()) {
		$data = array(
			'name' => $group['name'],
			'href' => URL::admin('&do=ug_auth&mode=group&g='.$group['id']),
			'auth' => array()
		);
		if ($adv) {
			foreach ($forum_auth_fields as $field) {
				$data['auth'][] = hasAuthAccess($forum, $group, $field) ? '✓' : ''; // ✔
			}
		} else {
			$allowed = 1;
			if (empty($group['auth_mod'])) {
				foreach ($forum_auth_fields as $field) {
					if (!$group[$field] && AUTH_ACL == $forum[$field]) {
						$allowed = 0;
						break;
					}
				}
			}
			$data['auth'][] = $allowed ? '✓' : ''; // ✔
		}
		$template->forum_auth_groups[] = $data;
	}

	$template->forum_auth_users = array();
	$auth = $db->query("SELECT user_id id, username name, user_level level, auth_mod, ".implode(', ',$forum_auth_fields)."
	FROM ".AUTH_ACCESS_TABLE."
	INNER JOIN {$db->TBL->bbgroups} USING (group_id)
	INNER JOIN {$db->TBL->bbuser_group} USING (group_id)
	INNER JOIN {$db->TBL->users} USING (user_id)
	WHERE forum_id = {$forum_id}
	  AND group_single_user = 1");
	while ($user = $auth->fetch_assoc()) {
		$data = array(
			'name' => $user['name'],
			'href' => URL::admin('&do=ug_auth&mode=user&u='.$user['id']),
			'auth' => array()
		);
		$is_admin = ($user['level'] == \Dragonfly\Identity::LEVEL_ADMIN && $user['id'] != \Dragonfly\Identity::ANONYMOUS_ID);
		if ($adv) {
			foreach ($forum_auth_fields as $field) {
				$data['auth'][] = hasAuthAccess($forum, $user, $field, $is_admin) ? '✓' : ''; // ✔
			}
		} else {
			$allowed = 1;
			if (!$is_admin && empty($user['auth_mod'])) {
				foreach ($forum_auth_fields as $field) {
					if (!$user[$field] && AUTH_ACL == $forum[$field]) {
						$allowed = 0;
						break;
					}
				}
			}
			$data['auth'][] = $allowed ? '✓' : ''; // ✔
		}
		$template->forum_auth_users[] = $data;
	}

	$template->assign_vars(array(
		'FORUM_NAME' => $forum['forum_name'],
		'U_SWITCH_MODE' => URL::admin("&do=forumauth&f={$forum_id}&adv=". (int)!$adv),
		'S_SWITCH_MODE' => $adv ? $lang['Simple_mode'] : $lang['Advanced_mode'],
	));

}
