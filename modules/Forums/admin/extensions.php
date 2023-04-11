<?php
/***************************************************************************
 *								  admin_extensions.php
 *								  -------------------
 *	 begin				  : Wednesday, Jan 09, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
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
 
/* Applied rules:
 * TernaryToNullCoalescingRector
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */

if (!defined('ADMIN_PAGES')) { exit; }

function size_exponent_select($select_name, $filesize)
{
	global $template;
	$filesize = floor(log($filesize, 1024));
	$size_types = array($template->L10N['Bytes'], $template->L10N['KB'], $template->L10N['MB']);
	$select_field = '<select name="' . $select_name . '">';
	foreach ($size_types as $exp => $label) {
		$select_field .= '<option value="' . $exp . '"' . ($filesize == $exp ? ' selected="selected"' : '') . '>' . $label . '</option>';
	}
	$select_field .= '</select>';
	return $select_field;
}

function size_exponent_value($size, $precision=2)
{
	$size = max(0, (int)$size);
	$i = ($size > 0) ? min(2, floor(log($size, 1024))) : 0;
	if ($i>0) { $size /= pow(1024, $i); }
	else { $precision = 0; }
	return round($size, max(0, $precision));
}

//
// Init Vars
//
$error_messages = array();

$mode = $_POST['mode'] ?: $_GET['mode'];

//
// Extension Management
//
if ('extensions' == $mode)
{
	if ('POST' === $_SERVER['REQUEST_METHOD'])
	{
		$delete_extensions = array();
		if ($_POST['delete_extensions']) {
			foreach ($_POST['delete_extensions'] as $id) {
				$delete_extensions[] = (int)$id;
			}
		}

		// Change Extensions
		foreach ($_POST['extensions'] as $id => $extension) {
			if (!in_array($id, $delete_extensions)) {
				$db->query("UPDATE {$db->TBL->bbextensions}
				SET comment = " . $db->quote(trim(strip_tags($extension['comment']))) . ", group_id = " . intval($extension['group_id']) . "
				WHERE ext_id = " . intval($id));
			}
		}

		// Delete Extension
		if ($delete_extensions) {
			$db->query('DELETE FROM ' . $db->TBL->bbextensions . ' WHERE ext_id IN (' . implode(', ', $delete_extensions) . ')');
		}

		// Add Extension
		$extension = strtolower($_POST->text('add_extension'));
		if (isset($_POST['add_extension_check']) && $extension) {
			// check extension
			if ($db->sql_count($db->TBL->bbextensions, "LOWER(extension) = {$db->quote($extension)}")) {
				$error_messages[] = sprintf($lang['Extension_exist'], $extension);
			}
			// check forbidden
			else if ($db->sql_count($db->TBL->bbforbidden_extensions, "LOWER(extension) = {$db->quote($extension)}")) {
				$error_messages[] = sprintf($lang['Unable_add_forbidden_extension'], $extension);
			}
			// else add
			else {
				$db->query("INSERT INTO {$db->TBL->bbextensions} (group_id, extension, comment)
				VALUES (" . $_POST->uint('add_extension_group') . ", " . $db->quote($extension) . ", " . $db->quote($_POST->text('add_extension_comment')) . "')");
			}
		}

		if (!$error_messages) {
			\Dragonfly::closeRequest($lang['Attach_config_updated'], 200, $_SERVER['REQUEST_URI']);
			return;
		}
	}

	$result = $db->query("SELECT group_id id, group_name name FROM {$db->TBL->bbextension_groups} ORDER BY group_name");
	$ext_groups = array(array(
		'id' => 0,
		'name' => $lang['Not_assigned'],
		'current' => 0
	));
	while ($row = $result->fetch_assoc()) {
		$row['current'] = 0;
		$ext_groups[$row['id']] = $row;
	}

	$template->set_handle('body', 'Forums/admin/attach_extensions');
	$template->add_ext_groups = $ext_groups;
	$result = $db->query("SELECT * FROM {$db->TBL->bbextensions} ORDER BY group_id, extension");
	$template->extensions = array();
	while ($row = $result->fetch_assoc()) {
		if (isset($_POST['extensions'])) {
			$row['comment']  = $_POST['extensions'][$row['ext_id']]['comment'];
			$row['group_id'] = $_POST['extensions'][$row['ext_id']]['group_id'];
		}
		$row['groups'] = $ext_groups;
		if (isset($ext_groups[$row['group_id']])) {
			$row['groups'][$row['group_id']]['current'] = 1;
		}
		$template->extensions[] = $row;
	}
}

//
// Extension Groups
//
if ('groups' == $mode) {
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		$delete_groups = array();
		if ($_POST['delete_extgroups']) {
			foreach ($_POST['delete_extgroups'] as $id) {
				$delete_groups[] = (int)$id;
			}
		}

		//
		// Change Extension Groups
		//
		foreach ($_POST['ext_groups'] as $group_id => $group) {
			if (!in_array($id, $delete_groups)) {
				$db->query("UPDATE {$db->TBL->bbextension_groups}
				SET group_name = {$db->quote($group['group_name'])},
					cat_id = " . intval($group['cat_id']) . ",
					allow_group = " . (empty($group['allow_group'])?0:1) . ",
					download_mode = " . intval($group['download_mode']) . ",
					max_filesize = " . (intval($group['max_filesize']) * pow(1024,(int)$group['max_filesize_exponent'])) . "
				WHERE group_id = " . intval($group_id));
			}
		}

		//
		// Delete Extension Groups
		//
		if ($delete_groups) {
			$db->query('DELETE FROM ' . $db->TBL->bbextension_groups . ' WHERE group_id IN (' . implode(', ', $delete_groups) . ')');
			//
			// Set corresponding Extensions to a pending Group
			//
			$db->query('UPDATE ' . $db->TBL->bbextensions . ' SET group_id = 0 WHERE group_id IN (' . implode(', ', $delete_groups) . ')');
		}

		//
		// Add Extensions ?
		//
		$extension_group = $_POST->text('add_extension_group');
		if ($extension_group && isset($_POST['add_extension_group_check'])) {
			//
			// check Extension Group
			//
			if ($db->sql_count($db->TBL->bbextension_groups, "LOWER(extension) = LOWER({$db->quote($extension_group)})")) {
				$error_messages[] = sprintf($lang['Extension_group_exist'], $extension_group);
			} else {
				$db->query("INSERT INTO {$db->TBL->bbextension_groups} (group_name, cat_id, allow_group, download_mode, max_filesize)
				VALUES (" . $db->quote($extension_group)
				 . ", " . $_POST->uint('add_category')
				 . ", " . ($_POST->bool('add_allowed') ? 1 : 0)
				 . ", " . $_POST->uint('add_download_mode')
				 . ", " . $_POST->uint('add_max_filesize') * pow(1024,(int)$_POST->uint('add_size_select')) . ")");
			}
		}

		if (!$error_messages) {
			\Dragonfly::closeRequest($lang['Attach_config_updated'], 200, $_SERVER['REQUEST_URI']);
		}
	}

	$template->set_handle('body', 'Forums/admin/attach_extension_groups');

	$template->group_categories = array(
		0 => array('id'=>0, 'label'=>''),
		IMAGE_CAT  => array('id'=>IMAGE_CAT,  'label'=>$lang['Category_images']),
		STREAM_CAT => array('id'=>STREAM_CAT, 'label'=>$lang['Category_stream_files']),
		SWF_CAT    => array('id'=>SWF_CAT,    'label'=>$lang['Category_swf_files']),
	);

	$max_filesize = \Poodle\Input\FILES::max_filesize();
	$template->assign_vars(array(
		'MAX_FILESIZE'        => size_exponent_value($max_filesize),
		'S_FILESIZE'          => size_exponent_select('add_size_select', $max_filesize),
	));

	$viewgroup = $_GET->uint('g') ?: -1;
	$result = $db->query("SELECT * FROM {$db->TBL->bbextension_groups}");
	$template->ext_groups = array();
	while ($extension_group = $result->fetch_assoc()) {
		$max_filesize = $extension_group['max_filesize'] ?: \Poodle\Input\FILES::max_filesize();
		$extension_group['max_filesize']    = size_exponent_value($max_filesize);
		$extension_group['S_FILESIZE']      = size_exponent_select("ext_groups[{$extension_group['group_id']}][max_filesize_exponent]", $max_filesize);
		$extension_group['CAT_BOX']         = ($viewgroup == $extension_group['group_id']) ? '-' : '+';
		$extension_group['U_VIEWGROUP']     = URL::admin("&do=extensions&mode=groups" . ($viewgroup == $extension_group['group_id']?'':"&g={$extension_group['group_id']}"));
		$extension_group['U_FORUM_PERMISSIONS'] = URL::admin("&do=extensions&mode=groupperm&group=" . $extension_group['group_id']);
		$extension_group['extensions'] = array();

		if (($viewgroup != -1) && ($viewgroup == $extension_group['group_id'])) {
			$eresult = $db->query("SELECT comment, extension FROM {$db->TBL->bbextensions} WHERE group_id = " . $viewgroup);
			while ($extension = $eresult->fetch_row()) {
				$extension_group['extensions'][] = array(
					'EXPLANATION' => $extension[0],
					'EXTENSION' => $extension[1]
				);
			}
		}

		$template->ext_groups[] = $extension_group;
	}
}

//
// Forbidden Extensions
//
if ('forbidden' == $mode) {
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		//
		// Store new forbidden extension or delete selected forbidden extensions
		//
		$delete_forbidden = array();
		if ($_POST['delete_forbidden']) {
			foreach ($_POST['delete_forbidden'] as $id) {
				$delete_forbidden[] = (int)$id;
			}
		}
		if ($delete_forbidden) {
			$db->TBL->bbforbidden_extensions->delete("ext_id IN (".implode(',', $delete_forbidden).")");
		}

		$extension = strtolower($_POST->text('add_extension'));
		if ($extension && isset($_POST['add_extension_check'])) {
			// Check Extension
			if ($db->TBL->bbforbidden_extensions->count("LOWER(extension) = {$db->quote($extension)}")) {
				$error_messages[] = sprintf($lang['Forbidden_extension_exist'], $extension);
			}
			// Check, if extension is allowed
			else if ($db->TBL->bbextensions->count("LOWER(extension) = {$db->quote($extension)}")) {
				$error_messages[] = sprintf($lang['Extension_exist_forbidden'], $extension);
			}
			else {
				$db->query("INSERT INTO {$db->TBL->bbforbidden_extensions} (extension) VALUES ({$db->quote($extension)})");
			}
		}

		if (!$error_messages) {
			\Dragonfly::closeRequest($lang['Attach_config_updated'], 200, $_SERVER['REQUEST_URI']);
		}
	}

	$template->set_handle('body', 'Forums/admin/attach_forbidden_extensions');

	$template->forbidden_extensions = $db->uFetchAll("SELECT ext_id id, extension FROM {$db->TBL->bbforbidden_extensions} ORDER BY extension");
}

$group = $_POST->uint('group') ?: $_GET->uint('group');
if ('groupperm' == $mode && $group) {
	// Add Forums
	if (isset($_POST['add_forum'])) {
		$add_forums_list = $_POST['entries'] ?? array();
		$add_all_forums = FALSE;

		for ($i = 0; $i < (is_countable($add_forums_list) ? count($add_forums_list) : 0); $i++) {
			if ($add_forums_list[$i] == 0) {
				$add_all_forums = TRUE;
			}
		}

		// If we add ALL FORUMS, we are able to overwrite the Permissions
		if ($add_all_forums) {
			$db->query("UPDATE {$db->TBL->bbextension_groups} SET forum_permissions = '' WHERE group_id = {$group}");
		}

		// Else we have to add Permissions
		if (!$add_all_forums) {
			$row = $db->uFetchRow("SELECT forum_permissions FROM {$db->TBL->bbextension_groups} WHERE group_id = {$group}");
			$auth_p = auth_pack(array_merge(auth_unpack($row[0]), array_values($add_forums_list)));
			$db->query("UPDATE {$db->TBL->bbextension_groups} SET forum_permissions = '{$auth_p}' WHERE group_id = {$group}");
		}

	}

	// Delete Forums
	if (isset($_POST['del_forum'])) {
		$delete_forums_list = $_POST['entries'] ?? array();
		// Get the current Forums
		$row = $db->uFetchRow("SELECT forum_permissions FROM {$db->TBL->bbextension_groups} WHERE group_id = {$group}");
		$auth_p = auth_pack(array_diff(auth_unpack($row[0]), $delete_forums_list));
		$db->query("UPDATE {$db->TBL->bbextension_groups} SET forum_permissions = '{$auth_p}' WHERE group_id = {$group}");
	}

	// Display the Group Permissions Box for configuring it
	$template->set_handle('body', 'Forums/admin/extension_groups_permissions');
	$row = $db->uFetchRow("SELECT group_name, forum_permissions FROM {$db->TBL->bbextension_groups} WHERE group_id = {$group}");
	$group_name = $row[0];
	$allowed_forums = trim($row[1]);
	$forum_perm = array();
	if (!$allowed_forums) {
		$forum_perm[0] = $lang['Perm_all_forums'];
	} else {
		$forum_p = auth_unpack($allowed_forums);
		$result = $db->query("SELECT forum_id, forum_name FROM " . FORUMS_TABLE . " WHERE forum_id IN (" . implode(', ', $forum_p) . ")");
		while ($row = $result->fetch_row()) {
			$forum_perm[$row[0]] = $row[1];
		}
	}

	$template->assign_vars(array(
		'GROUP_PERMISSIONS_TITLE' => sprintf($lang['Group_permissions_title'], trim($group_name)),
	));

	$template->allow_option_values = array();
	foreach ($forum_perm as $forum_id => $forum_name) {
		$template->allow_option_values[] = array(
			'value' => $forum_id,
			'label' => $forum_name
		);
	}

	$template->forum_option_values = array();
	if (!isset($forum_perm[0])) {
		$template->forum_option_values[] = array(
			'value' => 0,
			'label' => $lang['Perm_all_forums']
		);
	}
	$result = $db->query("SELECT forum_id, forum_name FROM " . FORUMS_TABLE . " WHERE forum_id NOT IN (" . implode(', ', array_keys($forum_perm)) . ")");
	while ($row = $result->fetch_row()) {
		$template->forum_option_values[] = array(
			'value' => $row[0],
			'label' => $row[1]
		);
	}

	$empty_perm_forums = array();

	$f_result = $db->query("SELECT forum_id, forum_name FROM " . FORUMS_TABLE . " WHERE auth_attachments < " . AUTH_ADMIN);
	while ($row = $f_result->fetch_row()) {
		$forum_id = $row[0];
		$result = $db->query("SELECT forum_permissions FROM {$db->TBL->bbextension_groups} WHERE allow_group = 1 ORDER BY group_name ASC");
		$found_forum = FALSE;
		while ($perm = $result->fetch_row()) {
			if (!trim($perm[0]) || in_array($forum_id, auth_unpack(trim($perm[0])))) {
				$found_forum = TRUE;
				break;
			}
		}

		if (!$found_forum) {
			$empty_perm_forums[$forum_id] = $row[1];
		}
	}

	if ($empty_perm_forums) {
		$error_messages[] = $lang['Note_admin_empty_group_permissions'] . implode('<br/>',$empty_perm_forums);
	}
}

$template->ERROR_MESSAGE = $error_messages ? implode('<br/>',$error_messages) : '';
