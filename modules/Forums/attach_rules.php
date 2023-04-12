<?php
/***************************************************************************
 *								  attach_rules.php
 *							  -------------------
 *	 begin				  : Monday, Apr 1, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: attach_rules.php,v 9.4 2007/12/12 12:54:23 nanocaiordo Exp $
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
 ***************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('IN_PHPBB', true);
$phpbb_root_path = 'modules/Forums/';
include($phpbb_root_path.'common.php');
if ($module_title == '') {
	$mod_name = ereg_replace('_', ' ', $name);
} else {
	$mod_name = $module_title;
}
if (isset($_POST['f']) || isset($_GET['f'])) {
	$forum_id = ( isset($_POST['f']) ) ? intval($_POST['f']) : intval($_GET['f']);
	$privmsg = ( $forum_id == -1 ) ? TRUE : FALSE;
} else {
	trigger_error('You are not allowed to call this file (ID:1)', E_USER_ERROR);
}

//
// Start Session Management
//
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);

//
// Display the allowed Extension Groups and Upload Size
//
//
if ($privmsg) {
	$auth['auth_attachments'] = ($userdata['user_level'] != ADMIN) ? intval($attach_config['allow_pm_attach']) : TRUE;
	$auth['auth_view'] = TRUE;
} else {
	$auth = auth(AUTH_ALL, $forum_id, $userdata);
}

if (!( ($auth['auth_attachments']) && ($auth['auth_view']))) {
	trigger_error('You are not allowed to call this file (ID:2)',E_USER_ERROR);
}

$sql = "SELECT group_id, group_name, max_filesize, forum_permissions FROM " . EXTENSION_GROUPS_TABLE . " WHERE allow_group = 1 ORDER BY group_name ASC";
$result = $db->sql_query($sql,false,__FILE,__LINE__);

$allowed_filesize = array();
$rows = $db->sql_fetchrowset($result);
$num_rows = $db->sql_numrows($result);

// Ok, only process those Groups allowed within this forum
$nothing = TRUE;
for ($i = 0; $i < $num_rows; $i++) {
	$auth_cache = trim($rows[$i]['forum_permissions']);
	if ($privmsg) {
		$permit = TRUE;
	} else {
		$permit = (is_forum_authed($auth_cache, $forum_id)) || (trim($rows[$i]['forum_permissions']) == '');
	}
	if ( $permit ) {
		$nothing = FALSE;
		$group_name = $rows[$i]['group_name'];
		$det_filesize = intval(trim($rows[$i]['max_filesize']));
		$max_filesize = ($det_filesize == 0) ? $lang['Unlimited'] : filesize_to_human($det_filesize);

		$template->assign_block_vars('group_row', array(
			'GROUP_RULE_HEADER' => sprintf($lang['Group_rule_header'], $group_name, $max_filesize))
		);

		$sql = "SELECT extension FROM " . EXTENSIONS_TABLE . "
		WHERE group_id = " . $rows[$i]['group_id'] . "
		ORDER BY extension ASC";
		$result = $db->sql_query($sql,false,__FILE,__LINE__);

		$e_rows = $db->sql_fetchrowset($result);
		$e_num_rows = $db->sql_numrows($result);

		for ($j = 0; $j < $e_num_rows; $j++) {
			$template->assign_block_vars('group_row.extension_row', array(
				'EXTENSION' => $e_rows[$j]['extension'])
			);
		}
	}
}

$gen_simple_header = TRUE;
$page_title = $lang['Attach_rules_title'];
require_once('includes/phpBB/page_header.php');

$template->assign_vars(array(
	'L_RULES_TITLE' => $lang['Attach_rules_title'],
	'L_CLOSE_WINDOW' => $lang['Close_window'],
	'L_EMPTY_GROUP_PERMS' => $lang['Note_user_empty_group_permissions'])
);

if ($nothing) {
	$template->assign_block_vars('switch_nothing', array());
}

$template->set_filenames(array('body' => 'forums/posting_attach_rules.html'));
require_once('includes/phpBB/page_tail.php');
