<?php
/***************************************************************************
 *							  functions_includes.php
 *							  -------------------
 *	 begin				  : Sunday, Mar 31, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: functions_includes.php,v 9.4 2005/03/12 03:14:54 djmaze Exp $
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 *
 ***************************************************************************/

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

//
// These are functions called directly from phpBB2 Files
//

//
// Setup Forum Authentication (admin_forumauth.php)
//
function attach_setup_forum_auth(&$simple_auth_ary, &$forum_auth_fields, &$field_names)
{
	global $lang;

	//
	// Add Attachment Auth
	//
	//					  Post Attachments
	$simple_auth_ary[0][] = AUTH_MOD;
	$simple_auth_ary[1][] = AUTH_MOD;
	$simple_auth_ary[2][] = AUTH_MOD;
	$simple_auth_ary[3][] = AUTH_MOD;
	$simple_auth_ary[4][] = AUTH_MOD;
	$simple_auth_ary[5][] = AUTH_MOD;
	$simple_auth_ary[6][] = AUTH_MOD;

	//					  Download Attachments
	$simple_auth_ary[0][] = AUTH_ALL;
	$simple_auth_ary[1][] = AUTH_ALL;
	$simple_auth_ary[2][] = AUTH_REG;
	$simple_auth_ary[3][] = AUTH_ACL;
	$simple_auth_ary[4][] = AUTH_ACL;
	$simple_auth_ary[5][] = AUTH_MOD;
	$simple_auth_ary[6][] = AUTH_MOD;

	$forum_auth_fields[] = 'auth_attachments';
	$field_names['auth_attachments'] = $lang['Auth_attach'];

	$forum_auth_fields[] = 'auth_download';
	$field_names['auth_download'] = $lang['Auth_download'];
}

//
// Setup s_auth_can in viewforum and viewtopic
//
function attach_build_auth_levels($is_auth, &$s_auth_can)
{
	global $lang, $attach_config, $forum_id;
	if (intval($attach_config['disable_mod'])) { return; }
	// If you want to have the rules window link within the forum view too, comment out the two lines, and comment the third line
//	  $rules_link = '(<a href=$phpbb_root_path . "attach_rules.php' . '?f=' . $forum_id . '" target="_blank">Rules</a>)';
//	  $s_auth_can .= ( ( $is_auth['auth_attachments'] ) ? $rules_link . ' ' . $lang['Rules_attach_can'] : $lang['Rules_attach_cannot'] ) . '<br />';
	$s_auth_can .= ( ( $is_auth['auth_attachments'] && $is_auth['auth_post'] ) ? $lang['Rules_attach_can'] : $lang['Rules_attach_cannot'] ) . '<br />';
	$s_auth_can .= (($is_auth['auth_download']) ? $lang['Rules_download_can'] : $lang['Rules_download_cannot'] ) . '<br />';
}

//
// Called from admin_users.php and admin_groups.php in order to process Quota Settings
//
function attachment_quota_settings($admin_mode, $submit = FALSE, $mode)
{
	$this_userdata = [];
 global $template, $db, $_POST, $_GET, $lang, $group_id, $lang, $phpbb_root_path, $attach_config;

	if (!intval($attach_config['allow_ftp_upload'])) {
		if ( ($attach_config['upload_dir'][0] == '/') || ( ($attach_config['upload_dir'][0] != '/') && ($attach_config['upload_dir'][1] == ':') ) ) {
			$upload_dir = $attach_config['upload_dir'];
		} else {
			$upload_dir = $attach_config['upload_dir'];
		}
	} else {
		$upload_dir = $attach_config['download_path'];
	}

	include('includes/phpBB/attach/functions_selects.php');
	include('includes/phpBB/attach/functions_admin.php');

	if ($admin_mode == 'user') {
		$submit = (isset($_POST['submit'])) ? TRUE : FALSE;
		if (!$submit && $mode != 'save') {
			if ( isset($_GET[POST_USERS_URL]) || isset($_POST[POST_USERS_URL]) ) {
				$user_id = (isset($_POST[POST_USERS_URL])) ? intval($_POST[POST_USERS_URL]) : intval($_GET[POST_USERS_URL]);
				$this_userdata['user_id'] = $user_id;
				if (empty($user_id)) {
					message_die(GENERAL_MESSAGE, $lang['No_user_id_specified'] );
				}
			} else {
				$u_name = (isset($_POST['username'])) ? htmlprepare($_POST['username']) : htmlprepare($_GET['username']);
				if (!($this_userdata = getusrdata($u_name))) {
					message_die(GENERAL_MESSAGE, $lang['No_user_id_specified'] );
				}
			}
			$user_id = intval($this_userdata['user_id']);
		} else {
			$user_id = (isset($_POST['id'])) ? intval($_POST['id']) : intval($_GET['id']);
			if (empty($user_id)) {
				message_die(GENERAL_MESSAGE, $lang['No_user_id_specified'] );
			}
		}
	}
	
	if ($admin_mode == 'user' && !$submit && $mode != 'save') {
		// Show the contents
		$result = $db->sql_query("SELECT quota_limit_id, quota_type FROM " . QUOTA_TABLE . " WHERE user_id = " . $user_id);
		$pm_quota = -1;
		$upload_quota = -1;
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['quota_type'] == QUOTA_UPLOAD_LIMIT) {
				$upload_quota = $row['quota_limit_id'];
			} else if ($row['quota_type'] == QUOTA_PM_LIMIT) {
				$pm_quota = $row['quota_limit_id'];
			}
		}
		$template->assign_vars(array(
			'S_SELECT_UPLOAD_QUOTA' => quota_limit_select('user_upload_quota', $upload_quota),
			'S_SELECT_PM_QUOTA' => quota_limit_select('user_pm_quota', $pm_quota),
			'L_UPLOAD_QUOTA' => $lang['Upload_quota'],
			'L_PM_QUOTA' => $lang['Pm_quota'])
		);
	}

	if ($admin_mode == 'user' && $submit && $_POST['deleteuser']) {
		process_quota_settings($admin_mode, $user_id, QUOTA_UPLOAD_LIMIT, -1);
		process_quota_settings($admin_mode, $user_id, QUOTA_PM_LIMIT, -1);
	} else if ($admin_mode == 'user' && $submit && $mode == 'save') {
		// Get the contents
		$upload_quota = intval($_POST['user_upload_quota']);
		$pm_quota = intval($_POST['user_pm_quota']);

		if ($upload_quota <= 0) {
			process_quota_settings($admin_mode, $user_id, QUOTA_UPLOAD_LIMIT, -1);
		} else {
			process_quota_settings($admin_mode, $user_id, QUOTA_UPLOAD_LIMIT, $upload_quota);
		}

		if ($pm_quota <= 0) {
			process_quota_settings($admin_mode, $user_id, QUOTA_PM_LIMIT, -1);
		} else {
			process_quota_settings($admin_mode, $user_id, QUOTA_PM_LIMIT, $pm_quota);
		}
	}

	if ($admin_mode == 'group' && $mode == 'newgroup') {
		return;
	} else if ($admin_mode == 'group') {
		// Get group id again, we do not trust phpBB here, Mods may be installed ;)
		if ( isset($_POST[POST_GROUPS_URL]) || isset($_GET[POST_GROUPS_URL]) ) {
			$group_id = ( isset($_POST[POST_GROUPS_URL]) ) ? intval($_POST[POST_GROUPS_URL]) : intval($_GET[POST_GROUPS_URL]);
		} else {
			// This should not occur :(
			$group_id = '';
		}
	}

	if ($admin_mode == 'group' && !$submit && isset($_POST['edit'])) {
		// Show the contents
		$result = $db->sql_query("SELECT quota_limit_id, quota_type FROM " . QUOTA_TABLE . " WHERE group_id = " . $group_id);
		$pm_quota = -1;
		$upload_quota = -1;
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['quota_type'] == QUOTA_UPLOAD_LIMIT) {
				$upload_quota = $row['quota_limit_id'];
			} else if ($row['quota_type'] == QUOTA_PM_LIMIT) {
				$pm_quota = $row['quota_limit_id'];
			}
		}
		$template->assign_vars(array(
			'S_SELECT_UPLOAD_QUOTA' => quota_limit_select('group_upload_quota', $upload_quota),
			'S_SELECT_PM_QUOTA' => quota_limit_select('group_pm_quota', $pm_quota),
			'L_UPLOAD_QUOTA' => $lang['Upload_quota'],
			'L_PM_QUOTA' => $lang['Pm_quota'])
		);
	}

	if ($admin_mode == 'group' && $submit && isset($_POST['group_delete'])) {
		process_quota_settings($admin_mode, $group_id, QUOTA_UPLOAD_LIMIT, -1);
		process_quota_settings($admin_mode, $group_id, QUOTA_PM_LIMIT, -1);
	} else if ($admin_mode == 'group' && $submit) {
		// Get the contents
		$upload_quota = intval($_POST['group_upload_quota']);
		$pm_quota = intval($_POST['group_pm_quota']);

		if ($upload_quota <= 0) {
			process_quota_settings($admin_mode, $group_id, QUOTA_UPLOAD_LIMIT, -1);
		} else {
			process_quota_settings($admin_mode, $group_id, QUOTA_UPLOAD_LIMIT, $upload_quota);
		}

		if ($pm_quota <= 0) {
			process_quota_settings($admin_mode, $group_id, QUOTA_PM_LIMIT, -1);
		} else {
			process_quota_settings($admin_mode, $group_id, QUOTA_PM_LIMIT, $pm_quota);
		}
	}

}