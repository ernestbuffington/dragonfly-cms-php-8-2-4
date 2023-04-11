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
if (!can_admin('groups')) { exit('Access Denied'); }
\Dragonfly\Page::title('Groups Administration');

$group_id = $_GET->uint('id');

function group_msg($message)
{
	cpg_error($message, 'Groups', URL::admin('Groups'));
}

// Save or delete a group
if ('POST' === $_SERVER['REQUEST_METHOD'])
{
	if (isset($_POST['delete'])) {
		$view = $group_id+3;
//		$db->query("UPDATE {$db->TBL->blocks} SET view_to = 2 WHERE view_to = {$view}");
		$db->query("UPDATE {$db->TBL->message} SET view = 2 WHERE view = {$view}");
		$db->query("UPDATE {$db->TBL->modules} SET view = 2 WHERE view = {$view}");
		if (isset($db->TBL->downloads_downloads)) {
			$db->query("UPDATE {$db->TBL->downloads_downloads} SET access = 2 WHERE access = {$view}");
		}
		if (isset($db->TBL->digest_digest)) {
			$db->query("UPDATE {$db->TBL->digest_digest} SET access = 2 WHERE access = {$view}");
		}
		if (isset($db->TBL->bbauth_access)) {
			$db->query("DELETE FROM {$db->TBL->bbauth_access} WHERE group_id={$group_id}");
		}
		$db->query("DELETE FROM {$db->TBL->bbuser_group} WHERE group_id={$group_id}");
		$db->query("DELETE FROM {$db->TBL->bbgroups} WHERE group_id={$group_id}");
		group_msg('The group has been deleted');
	}
	else {
		$group_moderator = trim($_POST['username']);
		if (!$group_moderator) {
			cpg_error('You must specify a moderator for this group');
		}
		$this_userdata = getusrdata($group_moderator, 'user_id');
		if (!$this_userdata) {
			cpg_error('The member '.$group_moderator.' doesn\'t exist');
		}
		$group_moderator = $this_userdata['user_id'];

		$group = array(
			'group_type'         => (int)$_POST->uint('group_type'),
			'group_name'         => trim($_POST['group_name']),
			'group_description'  => trim($_POST['group_description']),
			'group_moderator'    => $group_moderator,
			'group_upload_quota' => (int)$_POST->uint('group_upload_quota')
		);
		if (!strlen($group['group_name'])) {
			cpg_error('You must specify a name for this group');
		}

		if ($group_id) {
			$group_info = $db->uFetchAssoc("SELECT group_moderator FROM {$db->TBL->bbgroups}
				WHERE group_id = {$group_id}
				  AND group_single_user = 0");
			if (empty($group_info)) {
				cpg_error('The group doesn\'t exist');
			}
			if ($group_info['group_moderator'] != $group_moderator) {
				if (isset($_POST['delete_old_moderator'])) {
					$db->query("DELETE FROM {$db->TBL->bbuser_group}
						WHERE user_id = {$group_info['group_moderator']}
						  AND group_id = {$group_id}");
				}
				$row = $db->uFetchRow("SELECT user_id FROM {$db->TBL->bbuser_group}
					WHERE user_id = {$group_moderator}
					  AND group_id = {$group_id}");
				if (!$row) {
					$db->TBL->bbuser_group->insert(array('group_id'=>$new_group_id, 'user_id'=>$group_moderator, 'user_pending'=>0));
				}
			}
			$db->TBL->bbgroups->update($group, "group_id = {$group_id}");
			group_msg('The group has been updated');
		} else {
			$group['group_single_user'] = 0;
			$new_group_id = $db->TBL->bbgroups->insert($group, 'group_id');
			$db->TBL->bbuser_group->insert(array('group_id'=>$new_group_id, 'user_id'=>$group_moderator, 'user_pending'=>0));
			group_msg('The group has been added');
		}
	}
}

// Edit or create a group
else if (isset($_GET['id']))
{
	$TPL = Dragonfly::getKernel()->OUT;
	if ($group_id) {
		$group = $db->uFetchAssoc("SELECT
			group_id id,
			group_type type,
			group_name name,
			group_description description,
			group_upload_quota upload_quota,
			u.username
		FROM {$db->TBL->bbgroups} g
		LEFT JOIN {$db->TBL->users} u ON (u.user_id = g.group_moderator)
		WHERE group_single_user = 0 AND group_id={$group_id}");
		if (empty($group)) {
			cpg_error('Group doesn\'t exist');
		}
		$TPL->title = 'Edit group';
		$TPL->group = $group;
	} else {
		$TPL->title = 'Create new group';
		$TPL->group = array('id'=>0, 'type' => 0, 'name' => '', 'description' => '', 'username' => '', 'upload_quota' => 0);
	}
	$TPL->display('Groups/admin/group');
}

// View all groups
else
{
	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->groups = $db->query("SELECT group_id as id, group_name as name FROM {$db->TBL->bbgroups} WHERE group_single_user = 0 ORDER BY group_name");
	$TPL->display('Groups/admin/index');
}
