<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * TernaryToNullCoalescingRector
 */
 
if (!class_exists('Dragonfly', false)) { exit; }

if (isset($_POST['g'])) {
	\URL::redirect(\URL::index('&g='.$_POST['g']));
}
if (isset($_POST['cancel'])) {
	\URL::redirect(\URL::index());
}

\Dragonfly\Page::title($module_title);

$lang = Dragonfly::getKernel()->L10N;

//
// Default var values
//
$group_id = $_GET->uint('g');
$is_moderator = can_admin('groups');

if ($group_id)
{
	$group_info = $db->uFetchAssoc("SELECT
		group_id          id,
		group_type        type,
		group_name        name,
		group_description description,
		group_moderator   moderator
	FROM {$db->TBL->bbgroups}
	WHERE group_id = {$group_id}
	  AND group_single_user = 0");
	if (!$group_info) {
		cpg_error($lang['The group does not exist'], 404);
	}
	\Dragonfly\Page::title($group_info['name']);

	$groupurl = \URL::index("&g={$group_id}");
	if (isset($_POST['groupstatus'])) {
		if (!is_user()) {
			\URL::redirect(\Dragonfly\Identity::loginURL());
		}
		if ($group_info['moderator'] != $userinfo['user_id'] && !$is_moderator) {
//			\Poodle\Notify::error($lang['Not_group_moderator']);
			cpg_error($lang['Not_group_moderator'], 403);
		} else {
			$db->query("UPDATE {$db->TBL->bbgroups} SET group_type = ".intval($_POST['group_type'])." WHERE group_id = {$group_id}");
			\Poodle\Notify::success($lang['Group_type_updated']);
		}
		\URL::redirect($groupurl);
	}
	else if (isset($_POST['joingroup']))
	{
		//
		// Join a group
		//
		if (!is_user()) {
			\URL::redirect(\Dragonfly\Identity::loginURL());
		}
		list($ingroup) = $db->uFetchRow("SELECT COUNT(*) FROM {$db->TBL->bbuser_group} WHERE group_id={$group_id} AND user_id={$userinfo['user_id']}");
		if ($ingroup) {
			\Poodle\Notify::success($lang['Already_member_group']);
//			cpg_error($lang['Already_member_group'], GENERAL_MESSAGE, $groupurl);
		} else if ($group_info['type'] > 0) {
//			\Poodle\Notify::error($lang['This_closed_group']);
			cpg_error($lang['This_closed_group'], 403);
		} else {
			$db->query("INSERT INTO {$db->TBL->bbuser_group} (group_id, user_id, user_pending) VALUES ({$group_id}, {$userinfo['user_id']}, 1)");
			list($moderator_email) = $db->uFetchRow("SELECT user_email FROM {$db->TBL->users} WHERE user_id = {$group_info['moderator']}");
			$message = 'A user has requested to join a group on '.$MAIN_CFG['global']['sitename'].'.
		To approve or deny this request for group membership please visit the following link:

		'.URL::index("&g={$group_id}&validate=true", true, true);
			\Dragonfly\Email::send($dummy, 'A request to join your group has been made.', $message, $moderator_email, '', $userinfo['user_email'], $userinfo['username']);
			\Poodle\Notify::success($lang['Group_joined']);
		}
		\URL::redirect($groupurl);
	}
	else if (isset($_POST['unsub']) || isset($_GET['unsubscribe']))
	{
		//
		// Unsubscribe from a group
		//
		if (!is_user()) {
			\URL::redirect(\Dragonfly\Identity::loginURL());
		}
		if (isset($_POST['confirm'])) {
			$db->query("DELETE FROM {$db->TBL->bbuser_group} WHERE user_id=".$userinfo['user_id']." AND group_id={$group_id}");
			\Poodle\Notify::success($lang['Unsub_success']);
			\URL::redirect($groupurl);
		} else {
			list($is_group_pending_member) = $db->uFetchRow("SELECT
				COUNT(*)
			FROM {$db->TBL->bbuser_group}
			WHERE group_id = {$group_id}
			  AND user_id = {$userinfo['user_id']}
			  AND user_pending = 1");
			\Dragonfly\Page::confirm(\URL::index("&g={$group_id}&unsubscribe"), $is_group_pending_member ? $lang['Confirm_unsub_pending'] : $lang['Confirm_unsub']);
		}
	}

	//
	// Did the group moderator get here through an email?
	// If so, check to see if they are logged in.
	//
	if (isset($_GET['validate']) && !is_user()) {
		\URL::redirect(\Dragonfly\Identity::loginURL());
	}

	//
	// Handle Additions, removals, approvals and denials
	//
	if (isset($_POST['add']) || isset($_POST['remove']) || isset($_POST['approve']) || isset($_POST['deny'])) {
		if (!is_user()) {
			\URL::redirect(\Dragonfly\Identity::loginURL());
		}

		if (!$is_moderator && $group_info['moderator'] != $userinfo['user_id']) {
//			\Poodle\Notify::error($lang['Not_group_moderator']);
//			\URL::redirect($groupurl);
			cpg_error($lang['Not_group_moderator'], 403);
		}

		if (isset($_POST['add'])) {
			$username = $_POST['username'] ?? '';
			$row = $db->uFetchAssoc("SELECT
				u.user_id,
				u.user_email,
				u.user_lang,
				g.group_id
			FROM {$db->TBL->users} u
			LEFT JOIN {$db->TBL->bbuser_group} g ON (g.user_id = u.user_id AND g.group_id = {$group_id})
			WHERE user_nickname_lc = ".$db->quote(mb_strtolower($username)));

			if (!$row) {
				\Poodle\Notify::error($lang['Could_not_add_user']);
				\URL::redirect($groupurl);
			}

			if ($row['user_id'] == \Dragonfly\Identity::ANONYMOUS_ID) {
				\Poodle\Notify::error($lang['Could_not_anon_user']);
				\URL::redirect($groupurl);
			}

			if ($row['group_id']) {
				\Poodle\Notify::error($lang['User_is_member_group']);
				\URL::redirect($groupurl);
			}

			$db->query("INSERT INTO {$db->TBL->bbuser_group} (user_id, group_id, user_pending) VALUES ({$row['user_id']}, {$group_id}, 0)");
			\Poodle\Notify::success($lang['Member added']);

			//
			// Email the user and tell them they're in the group
			//
			$MAIL = \Dragonfly\Email::getMailer();
			$tpl_file = "Groups/emails/added-{$row['user_lang']}";
			if (!$MAIL->themeTALFileExists($tpl_file)) {
				$tpl_file = 'Groups/emails/added-en';
			}
			$MAIL->addTo($row['user_email']);
			$MAIL->subject = $lang['Group_added'];
			$MAIL->GROUP_NAME = $group_info['name'];
			$MAIL->U_GROUPCP  = URL::index("&g={$group_id}", true, true);
			$MAIL->body = $MAIL->toString($tpl_file);
			$MAIL->send();
			unset($MAIL);
		} else if (isset($_POST['deny']) && !empty($_POST['pending_members'])) {
			$members = implode(',', array_map('intval', $_POST['pending_members']));
			$db->query("DELETE FROM {$db->TBL->bbuser_group} WHERE user_id IN ({$members}) AND group_id = {$group_id}");
			\Poodle\Notify::success($lang['Members denied']);
		} else if (isset($_POST['remove']) && !empty($_POST['members'])) {
			$members = implode(',', array_map('intval', $_POST['members']));
			$db->query("DELETE FROM {$db->TBL->bbuser_group} WHERE user_id IN ({$members}) AND group_id = {$group_id}");
			\Poodle\Notify::success($lang['Members removed']);
		} else if (isset($_POST['approve']) && !empty($_POST['pending_members'])) {
			$members = implode(',', array_map('intval', $_POST['pending_members']));
			$db->query("UPDATE {$db->TBL->bbuser_group}
				SET user_pending = 0
				WHERE user_id IN ({$members})
				  AND group_id = {$group_id}");
			\Poodle\Notify::success($lang['Members approved']);
			//
			// Email users when they are approved
			//
			$MAIL = \Dragonfly\Email::getMailer();
			$tpl_file = "Groups/emails/approved-{$row['user_lang']}";
			if (!$MAIL->themeTALFileExists($tpl_file)) {
				$tpl_file = 'Groups/emails/approved-en';
			}
			$result = $db->query("SELECT user_email FROM {$db->TBL->users} WHERE user_id IN ({$members})");
			while ($row = $result->fetch_assoc()) {
				$MAIL->addBCC($row['user_email']);
			}
			$MAIL->subject = $lang['Group_approved'];
			$MAIL->GROUP_NAME = $group_info['name'];
			$MAIL->U_GROUPCP  = URL::index("&g={$group_id}", true, true);
			$MAIL->body = $MAIL->toString($tpl_file);
			$MAIL->send();
			unset($MAIL);
		}
		\URL::redirect($groupurl);
	}
	//
	// END approve or deny
	//

	$offset = (int)$_GET->uint('start');
	$limit  = 50;

	//
	// Get moderator details for this group
	//
	$group_moderator = $db->uFetchAssoc("SELECT
		username, user_id, user_rank, user_posts, user_regdate, user_from, user_website, user_icq, user_aim, user_yim
	FROM {$db->TBL->users}
	WHERE user_id = {$group_info['moderator']}");

	//
	// Get user information for this group
	//
	$members_count = $db->TBL->bbuser_group->count("group_id = {$group_id} AND user_pending = 0") - 1;

	$is_group_member = $is_group_pending_member = 0;
	if (is_user()) {
		$is_member = $db->uFetchRow("SELECT
			user_pending
		FROM {$db->TBL->bbuser_group}
		WHERE group_id = {$group_id}
		  AND user_id = {$userinfo['user_id']}");
		if ($is_member) {
			if ($is_member[0]) {
				$is_group_pending_member = true;
			} else {
				$is_group_member = true;
			}
		}
	}

	$template->can_unsubscribe_group = false;
	$template->can_join_group = false;
	if ($userinfo['user_id'] == $group_info['moderator']) {
		$is_moderator = true;
		$group_info['details'] = $lang['You are the group moderator'];
	} else if ($is_group_member || $is_group_pending_member) {
		$template->can_unsubscribe_group = true;
		$group_info['details'] = ($is_group_pending_member ? $lang['Pending_this_group'] : $lang['Member_this_group']);
	} else if ($userinfo['user_id'] < 2) {
		$group_info['details'] = $lang['Login_to_join'];
	} else if ($group_info['type'] == \Dragonfly\Groups::TYPE_OPEN) {
		$template->can_join_group = true;
		$group_info['details'] = $lang['This_open_group'];
	} else if ($group_info['type'] == \Dragonfly\Groups::TYPE_CLOSED) {
		$group_info['details'] = $lang['This_closed_group'];
	} else if ($group_info['type'] == \Dragonfly\Groups::TYPE_HIDDEN) {
		$group_info['details'] = $lang['This_hidden_group'];
	}

	$template->U_SEARCH_USER = URL::index('Your_Account&file=search&window', true, true);

	$template->switch_no_members   = !$members_count;
	$template->switch_hidden_group = ($group_info['type'] == \Dragonfly\Groups::TYPE_HIDDEN && !$is_group_member && !$is_moderator);
	$template->is_group_moderator  = $is_moderator;

	$template->group_info = $group_info;

	$template->group_moderators = array(array(
		'id' => $group_moderator['user_id'],
		'username' => $group_moderator['username'],
		'profile_url' => \Dragonfly\Identity::getProfileURL($group_moderator['user_id']),
	));

	$template->group_members = array();
	if (!$template->switch_hidden_group) {
		$group_members = $db->query("SELECT
			u.user_id id, u.username
		FROM {$db->TBL->users} u, {$db->TBL->bbuser_group} ug
		WHERE ug.group_id = {$group_id}
		  AND u.user_id = ug.user_id
		  AND ug.user_pending = 0
		  AND NOT ug.user_id = {$group_info['moderator']}
		ORDER BY u.username
		LIMIT {$limit} OFFSET {$offset}");
		while ($member = $group_members->fetch_assoc()) {
			$member['profile_url'] = \Dragonfly\Identity::getProfileURL($member['id']);
			$template->group_members[] = $member;
		}
	}

	$template->group_pending_members = array();
	if ($is_moderator) {
		$group_pending = $db->query("SELECT
			u.user_id id, u.username
		FROM {$db->TBL->bbuser_group} ug, {$db->TBL->users} u
		WHERE ug.group_id = {$group_id}
		  AND ug.user_pending = 1
		  AND u.user_id = ug.user_id
		ORDER BY u.username");
		while ($member = $group_pending->fetch_assoc()) {
			$member['profile_url'] = \Dragonfly\Identity::getProfileURL($member['id']);
			$template->group_pending_members[] = $member;
		}
	}

	$template->group_members_pagination = new \Poodle\Pagination(URL::index("&g={$group_id}&start=\${offset}"), $members_count, $offset, $limit);

	$template->display('Groups/group');
}
else
{
	//
	// Show the main Groups screen where the user can select a group.
	//
	$in_group = $pending_groups = $member_groups = array();

	//
	// Select all group that the user is a member of or where the user has
	// a pending membership.
	//
	if (is_user()) {
		$result = $db->query("SELECT
			g.group_id, g.group_name, ug.user_pending
		FROM {$db->TBL->bbgroups} g, {$db->TBL->bbuser_group} ug
		WHERE ug.user_id = ".$userinfo['user_id']."
		  AND ug.group_id = g.group_id
		  AND g.group_single_user = 0
		ORDER BY g.group_name, ug.user_id");
		while ($row = $result->fetch_row()) {
			$in_group[] = $row[0];
			if ($row[2]) {
				$pending_groups[] = array('id' => $row[0], 'name' => $row[1]);
			} else {
				$member_groups[]  = array('id' => $row[0], 'name' => $row[1]);
			}
		}
	}

	//
	// Select all other groups i.e. groups that this user is not a member of
	//
	$where = 'group_single_user = 0';
	if ($in_group) {
		$where .= ' AND group_id NOT IN ('.implode(', ', $in_group).')';
	}
	if (!$is_moderator) {
		$where .= ' AND NOT group_type = '.\Dragonfly\Groups::TYPE_HIDDEN;
	}
	$group_list = $db->query("SELECT group_id id, group_name name FROM {$db->TBL->bbgroups} WHERE {$where} ORDER BY group_name");

	if ($group_list->num_rows || $pending_groups || $member_groups) {
		//
		// Load and process templates
		//
		$template->groups_pending = $pending_groups;
		$template->groups_member  = $member_groups;
		$template->groups_list    = $group_list;
		$template->display('Groups/index');
	} else {
		cpg_error($lang['No Groups Exist']);
	}
}
