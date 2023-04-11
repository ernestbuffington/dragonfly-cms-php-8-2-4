<?php
/***************************************************************************
 *								  attach_rules.php
 *							  -------------------
 *	 begin				  : Monday, Apr 1, 2002
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

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

$forum_id = $_POST->uint('f') ?: $_GET->uint('f');
if (!$forum_id) {
	//\Poodle\Report::error(404);
	cpg_error($lang['Forum_not_exist'], 404);
}

# Display the allowed Extension Groups and Upload Size
$auth = \Dragonfly\Forums\Auth::all($forum_id);

if (!$auth['auth_attachments']) {
	//\Poodle\Report::error(403);
	cpg_error($lang['Attachment_feature_disabled'], 403);
}
if (!$auth['auth_view']) {
	//\Poodle\Report::error(403);
	cpg_error($lang['Sorry_auth_view_attach'], 403);
}

$result = $db->query("SELECT group_id, group_name, max_filesize, forum_permissions
	FROM {$db->TBL->bbextension_groups} WHERE allow_group = 1 ORDER BY group_name ASC");

// Ok, only process those Groups allowed within this forum
$template->ext_groups = array();
while ($row = $result->fetch_assoc()) {
	$auth_cache = trim($row['forum_permissions']);
	if (!$auth_cache || is_forum_authed($auth_cache, $forum_id)) {
		$eresult = $db->query("SELECT extension FROM {$db->TBL->bbextensions}
		WHERE group_id = {$row['group_id']}
		ORDER BY extension ASC");
		if ($eresult->num_rows) {
			$def_filesize = intval(trim($row['max_filesize']));
			$max_filesize = $def_filesize ? $template->L10N->filesizeToHuman($def_filesize) : $lang['Unlimited'];
			$group = array(
				'name' => $row['group_name'],
				'max_filesize' => $max_filesize,
				'label' => sprintf($lang['Group_rule_header'], $row['group_name'], $max_filesize),
				'extensions' => array(),
			);
			while ($erow = $eresult->fetch_row()) {
				$group['extensions'][] = $erow[0];
			}

			$template->ext_groups[] = $group;
		}
	}
}

echo '<!DOCTYPE html>';
echo $template->toString('forums/posting_attach_rules_window');
exit;
