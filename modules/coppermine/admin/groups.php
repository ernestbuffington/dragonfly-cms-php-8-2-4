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

function get_groups()
{
	global $db, $CONFIG;
	$result = $db->query("SELECT
		group_id               id,
		group_name             name,
		group_quota            quota,
		has_admin_access,
		can_rate_pictures,
		can_send_ecards,
		can_post_comments,
		can_upload_pictures,
		can_create_albums,
		pub_upl_need_approval,
		priv_upl_need_approval
	FROM {$db->TBL->cpg_usergroups}
	ORDER BY group_id");
	if (!$result->num_rows) {
		$fields = '(group_id, group_name, group_quota, has_admin_access, can_rate_pictures, can_send_ecards, can_post_comments, can_upload_pictures, can_create_albums, pub_upl_need_approval, priv_upl_need_approval)';
		$db->exec("INSERT INTO {$db->TBL->cpg_usergroups} $fields VALUES (1, 'Administrators', 0, 1, 1, 1, 1, 1, 1)");
		$db->exec("INSERT INTO {$db->TBL->cpg_usergroups} $fields VALUES (2, 'Registered', 1024, 0, 1, 1, 1, 1, 1)");
		$db->exec("INSERT INTO {$db->TBL->cpg_usergroups} $fields VALUES (3, 'Anonymous', 0, 0, 0, 0, 1, 0, 0)");
		$db->exec("INSERT INTO {$db->TBL->cpg_usergroups} $fields VALUES (4, 'Banned', 0, 0, 0, 0, 0, 0, 0);");
		return get_groups();
	}
	return $result;
}

if ('POST' === $_SERVER['REQUEST_METHOD'])
{
	if (isset($_POST['del_sel'])) {
		$groups = array();
		foreach ($_POST->map('delete_group') as $group_id) {
			$group_id = intval($group_id);
			if ($group_id > 4) $groups[] = $group_id;
		}
		if ($groups) {
			$groups = implode(',',$groups);
			$db->exec("DELETE FROM {$db->TBL->cpg_usergroups} WHERE group_id IN ({$groups})");
			$db->exec("UPDATE {$db->TBL->users} SET user_group_cp=2 WHERE user_group_cp IN ({$groups})");
		}
	}
	else if (isset($_POST['new_group']))
	{
		$db->exec("INSERT INTO {$db->TBL->cpg_usergroups} (group_name) VALUES ('')");
	}
	else if (isset($_POST['apply_modifs']))
	{
		$groups = $_POST->map('groups');
		if (!$groups) cpg_error(PARAM_MISSING);
		$field_list = array('can_rate_pictures', 'can_send_ecards', 'can_post_comments', 'can_upload_pictures', 'pub_upl_need_approval', 'can_create_albums', 'priv_upl_need_approval');
		foreach ($groups as $group_id => $group)
		{
			$group_id = intval($group_id);
			if (!$group_id) continue;
			$set_statment  = 'group_name='.$db->quote($group['name']).',';
			$set_statment .= 'group_quota='.intval($group['quota']).',';
			foreach ($field_list as $field) {
				$set_statment .= $field . '=' . (empty($group[$field])?0:1) . ',';
			}
			$set_statment = substr($set_statment, 0, -1);
			$db->exec("UPDATE {$db->TBL->cpg_usergroups} SET {$set_statment} WHERE group_id = {$group_id}");
		}
	}
	URL::redirect(URL::admin("&file=groups"));
}

\Dragonfly\Page::title(GROUP_TITLE);
$OUT = Dragonfly::getKernel()->OUT;
$OUT->groups = get_groups();
$OUT->display('coppermine/admin/groups');
