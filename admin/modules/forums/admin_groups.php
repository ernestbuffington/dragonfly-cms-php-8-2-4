<?php
/***************************************************************************
 *				   admin_groups.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 $Id: admin_groups.php,v 9.8 2006/06/03 14:52:59 djmaze Exp $
 *
 *
 ***************************************************************************/
/***************************************************************************
* phpbb2 forums port version 2.0.5 (c) 2003 - Nuke Cops (http://nukecops.com)
*
* Ported by Nuke Cops to phpbb2 standalone 2.0.5 Test
* and debugging completed by the Elite Nukers and site members.
*
* You run this package at your sole risk. Nuke Cops and affiliates cannot
* be held liable if anything goes wrong. You are advised to test this
* package on a development system. Backup everything before implementing
* in a production environment. If something goes wrong, you can always
* backout and restore your backups.
*
* Installing and running this also means you agree to the terms of the AUP
* found at Nuke Cops.
*
* This is version 2.0.5 of the phpbb2 forum port for PHP-Nuke. Work is based
* on Tom Nitzschner's forum port version 2.0.6. Tom's 2.0.6 port was based
* on the phpbb2 standalone version 2.0.3. Our version 2.0.5 from Nuke Cops is
* now reflecting phpbb2 standalone 2.0.5 that fixes some bugs and the
* invalid_session error message.
***************************************************************************/
/***************************************************************************
 *	 This file is part of the phpBB2 port to Nuke 6.0 (c) copyright 2002
 *	 by Tom Nitzschner (tom@toms-home.com)
 *	 http://bbtonuke.sourceforge.net (or http://www.toms-home.com)
 *
 *	 As always, make a backup before messing with anything. All code
 *	 release by me is considered sample code only. It may be fully
 *	 functual, but you use it at your own risk, if you break it,
 *	 you get to fix it too. No waranty is given or implied.
 *
 *	 Please post all questions/request about this port on http://bbtonuke.sourceforge.net first,
 *	 then on my site. All original header code and copyright messages will be maintained
 *	 to give credit where credit is due. If you modify this, the only requirement is
 *	 that you also maintain all original copyright messages. All my work is released
 *	 under the GNU GENERAL PUBLIC LICENSE. Please see the README for more information.
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
if (!defined('ADMIN_PAGES')) { exit; }

if ( isset($_POST[POST_GROUPS_URL]) || isset($_GET[POST_GROUPS_URL]) ) {
	$group_id = ( isset($_POST[POST_GROUPS_URL]) ) ? intval($_POST[POST_GROUPS_URL]) : intval($_GET[POST_GROUPS_URL]);
} else {
	$group_id = 0;
}

if ( isset($_POST['mode']) || isset($_GET['mode']) ) {
	$mode = ( isset($_POST['mode']) ) ? $_POST['mode'] : $_GET['mode'];
} else {
	$mode = '';
}

//if (defined('BBAttach_mod')) {
	attachment_quota_settings('group', (isset($_POST['group_update']) ? $_POST['group_update']:''), $mode);

if (isset($_POST['group_update'])) {
	//
	// Ok, they are submitting a group, let's save the data based on if it's new or editing
	//
	if ( isset($_POST['group_delete']) ) {
		//
		// Reset User Moderator Level
		//

		// Is Group moderating a forum ?
		$db->sql_query("SELECT auth_mod FROM " . AUTH_ACCESS_TABLE . " WHERE group_id = " . $group_id);

		$row = $db->sql_fetchrow($result);
		if (intval($row['auth_mod']) == 1)
		{
			// Yes, get the assigned users and update their Permission if they are no longer moderator of one of the forums
			$result = $db->sql_query("SELECT user_id FROM " . USER_GROUP_TABLE . " WHERE group_id = " . $group_id);

			$rows = $db->sql_fetchrowset($result);
			for ($i = 0; $i < count($rows); $i++)
			{
				$sql = "SELECT g.group_id FROM " . AUTH_ACCESS_TABLE . " a, " . GROUPS_TABLE . " g, " . USER_GROUP_TABLE . " ug
				WHERE (a.auth_mod = 1) AND (g.group_id = a.group_id) AND (a.group_id = ug.group_id) AND (g.group_id = ug.group_id)
					AND (ug.user_id = " . intval($rows[$i]['user_id']) . ") AND (ug.group_id <> " . $group_id . ")";
				if ( !($result = $db->sql_query($sql)) )
				{
					message_die(GENERAL_ERROR, 'Could not obtain moderator permissions', '', __LINE__, __FILE__, $sql);
				}

				if ($db->sql_numrows($result) == 0) {
					$db->sql_query("UPDATE " . USERS_TABLE . " SET user_level = " . USER . " WHERE user_level = " . MOD . " AND user_id = " . intval($rows[$i]['user_id']));
				}
			}
		}

		//
		// Delete Group
		//
		$db->sql_query("DELETE FROM " . GROUPS_TABLE . " WHERE group_id = " . $group_id);
		$db->sql_query("DELETE FROM " . USER_GROUP_TABLE . " WHERE group_id = " . $group_id);
		$db->sql_query("DELETE FROM " . AUTH_ACCESS_TABLE . " WHERE group_id = " . $group_id);

		$message = $lang['Deleted_group'] . '<br /><br />' . sprintf($lang['Click_return_groupsadmin'], '<a href="'.adminlink("&amp;do=groups").'">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="'.adminlink("forums").'">', '</a>');
		message_die(GENERAL_MESSAGE, $message);
	}
	else
	{
		$group_type = isset($_POST['group_type']) ? intval($_POST['group_type']) : GROUP_OPEN;
		$group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
		$group_description = isset($_POST['group_description']) ? trim($_POST['group_description']) : '';
		$group_moderator = isset($_POST['username']) ? $_POST['username'] : '';
		$delete_old_moderator = isset($_POST['delete_old_moderator']) ? true : false;

		if ( $group_name == '' ) {
			message_die(GENERAL_MESSAGE, $lang['No_group_name']);
		} else if ( $group_moderator == '' ) {
			message_die(GENERAL_MESSAGE, $lang['No_group_moderator']);
		}

		$this_userdata = getusrdata($group_moderator, true);
		$group_moderator = $this_userdata['user_id'];

		if ( !$group_moderator ) {
			message_die(GENERAL_MESSAGE, $lang['No_group_moderator']);
		}

		if( $mode == "editgroup" ) {
			$sql = "SELECT * FROM " . GROUPS_TABLE . "
				WHERE group_single_user <> " . TRUE . "
				AND group_id = " . $group_id;
			if ( !($result = $db->sql_query($sql)) )
			{
				message_die(GENERAL_ERROR, 'Error getting group information', '', __LINE__, __FILE__, $sql);
			}

			if( !($group_info = $db->sql_fetchrow($result)) )
			{
				message_die(GENERAL_MESSAGE, $lang['Group_not_exist']);
			}

			if ( $group_info['group_moderator'] != $group_moderator )
			{
				if ( $delete_old_moderator )
				{
					$sql = "DELETE FROM " . USER_GROUP_TABLE . "
						WHERE user_id = " . $group_info['group_moderator'] . "
							AND group_id = " . $group_id;
					if ( !$db->sql_query($sql) )
					{
						message_die(GENERAL_ERROR, 'Could not update group moderator', '', __LINE__, __FILE__, $sql);
					}
				}

				$sql = "SELECT user_id
					FROM " . USER_GROUP_TABLE . "
					WHERE user_id = $group_moderator
						AND group_id = $group_id";
				if ( !($result = $db->sql_query($sql)) )
				{
					message_die(GENERAL_ERROR, 'Failed to obtain current group moderator info', '', __LINE__, __FILE__, $sql);
				}

				if ( !($row = $db->sql_fetchrow($result)) )
				{
					$sql = "INSERT INTO " . USER_GROUP_TABLE . " (group_id, user_id, user_pending)
						VALUES (" . $group_id . ", " . $group_moderator . ", 0)";
					if ( !$db->sql_query($sql) )
					{
						message_die(GENERAL_ERROR, 'Could not update group moderator', '', __LINE__, __FILE__, $sql);
					}
				}
			}

			$sql = "UPDATE " . GROUPS_TABLE . "
				SET group_type = $group_type, group_name = '" . Fix_Quotes($group_name) . "', group_description = '" . Fix_Quotes($group_description) . "', group_moderator = $group_moderator
				WHERE group_id = $group_id";
			if ( !$db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not update group', '', __LINE__, __FILE__, $sql);
			}

			$message = $lang['Updated_group'] . '<br /><br />' . sprintf($lang['Click_return_groupsadmin'], '<a href="'.adminlink("&amp;do=groups").'">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="'.adminlink("forums").'">', '</a>');;

			message_die(GENERAL_MESSAGE, $message);
		}
		else if( $mode == 'newgroup' )
		{
			$sql = "INSERT INTO " . GROUPS_TABLE . " (group_type, group_name, group_description, group_moderator, group_single_user)
				VALUES ($group_type, '" . Fix_Quotes($group_name) . "', '" . Fix_Quotes($group_description) . "', $group_moderator,	   '0')";
			if ( !$db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not insert new group', '', __LINE__, __FILE__, $sql);
			}
			$new_group_id = $db->sql_nextid('group_id');

			$sql = "INSERT INTO " . USER_GROUP_TABLE . " (group_id, user_id, user_pending)
				VALUES ($new_group_id, $group_moderator, 0)";
			if ( !$db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not insert new user-group info', '', __LINE__, __FILE__, $sql);
			}

			$message = $lang['Added_new_group'] . '<br /><br />' . sprintf($lang['Click_return_groupsadmin'], '<a href="'.adminlink("&amp;do=groups").'">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="'.adminlink("forums").'">', '</a>');;

			message_die(GENERAL_MESSAGE, $message);

		}
		else
		{
			message_die(GENERAL_MESSAGE, $lang['No_group_action']);
		}
	}
}
elseif (isset($_POST[POST_GROUPS_URL])) {
	//
	// Ok they are editing a group or creating a new group
	//
	$template->set_filenames(array('body' => 'forums/admin/group_edit_body.html'));

	//
	// They're editing. Grab the vars.
	//
	$result = $db->sql_query("SELECT * FROM " . GROUPS_TABLE . " WHERE group_single_user <> " . TRUE . " AND group_id = $group_id");
	if (!($group_info = $db->sql_fetchrow($result))) {
		message_die(GENERAL_MESSAGE, $lang['Group_not_exist']);
	}
	$mode = 'editgroup';
	$template->assign_block_vars('group_edit', array());

	//
	// Ok, now we know everything about them, let's show the page.
	//
	$result = $db->sql_query("SELECT user_id, username FROM " . USERS_TABLE . " WHERE user_id <> " . ANONYMOUS . " ORDER BY username");
	while ($row = $db->sql_fetchrow($result)) {
		if ($row['user_id'] == $group_info['group_moderator']) {
			$group_moderator = $row['username'];
		}
	}

	$group_open = ( $group_info['group_type'] == GROUP_OPEN ) ? ' checked="checked"' : '';
	$group_closed = ( $group_info['group_type'] == GROUP_CLOSED ) ? ' checked="checked"' : '';
	$group_hidden = ( $group_info['group_type'] == GROUP_HIDDEN ) ? ' checked="checked"' : '';

	$s_hidden_fields = '<input type="hidden" name="mode" value="' . $mode . '" /><input type="hidden" name="' . POST_GROUPS_URL . '" value="' . $group_id . '" />';

	$template->assign_vars(array(
		'GROUP_NAME' => $group_info['group_name'],
		'GROUP_DESCRIPTION' => $group_info['group_description'],
		'GROUP_MODERATOR' => $group_moderator,

		'L_GROUP_TITLE' => $lang['Group_administration'],
		'L_GROUP_EDIT_DELETE' => $lang['Edit_group'],
		'L_GROUP_NAME' => $lang['group_name'],
		'L_GROUP_DESCRIPTION' => $lang['group_description'],
		'L_GROUP_MODERATOR' => $lang['group_moderator'],
		'L_FIND_USERNAME' => $lang['Find_username'],
		'L_GROUP_STATUS' => $lang['group_status'],
		'L_GROUP_OPEN' => $lang['group_open'],
		'L_GROUP_CLOSED' => $lang['group_closed'],
		'L_GROUP_HIDDEN' => $lang['group_hidden'],
		'L_GROUP_DELETE' => $lang['group_delete'],
		'L_GROUP_DELETE_CHECK' => $lang['group_delete_check'],
		'L_SUBMIT' => $lang['Submit'],
		'L_RESET' => $lang['Reset'],
		'L_DELETE_MODERATOR' => $lang['delete_group_moderator'],
		'L_DELETE_MODERATOR_EXPLAIN' => $lang['delete_moderator_explain'],
		'L_YES' => $lang['Yes'],

		'U_SEARCH_USER' => getlink("&file=search&mode=searchuser&popup=1&menu=1", true, true),

		'S_GROUP_OPEN_TYPE' => GROUP_OPEN,
		'S_GROUP_CLOSED_TYPE' => GROUP_CLOSED,
		'S_GROUP_HIDDEN_TYPE' => GROUP_HIDDEN,
		'S_GROUP_OPEN_CHECKED' => $group_open,
		'S_GROUP_CLOSED_CHECKED' => $group_closed,
		'S_GROUP_HIDDEN_CHECKED' => $group_hidden,
		'S_GROUP_ACTION' => adminlink("&amp;do=groups"),
		'S_HIDDEN_FIELDS' => $s_hidden_fields)
	);
}
else
{
	$result = $db->sql_query("SELECT group_id, group_name FROM " . GROUPS_TABLE . " WHERE group_single_user <> " . TRUE . " ORDER BY group_name");
	$select_list = '';
	if ($row = $db->sql_fetchrow($result)) {
		$select_list .= '<select name="' . POST_GROUPS_URL . '">';
		do {
			$select_list .= '<option value="' . $row['group_id'] . '">' . $row['group_name'] . '</option>';
		}
		while ($row = $db->sql_fetchrow($result));
		$select_list .= '</select>';
	}

	$template->set_filenames(array('body' => 'forums/admin/auth_select_body.html'));
	$template->assign_vars(array(
		'L_AUTH_TITLE' => $lang['Group_administration'],
		'L_AUTH_EXPLAIN' => $lang['Group_admin_explain'],
		'L_AUTH_SELECT' => $lang['Select_group'],
		'L_LOOK_UP' => $lang['Look_up_group'],

		'S_HIDDEN_FIELDS' => '<input type="hidden" name="edit" value="true">',
		'S_AUTH_ACTION' => adminlink("&amp;do=groups"),
		'S_AUTH_SELECT' => $select_list
		)
	);

	if ( $select_list != '' ) {
		$template->assign_block_vars('select_box', array());
	}
}
