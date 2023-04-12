<?php
/***************************************************************************
 *				  admin_forumauth.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 $Id: admin_forumauth.php,v 9.6 2005/10/11 12:31:44 djmaze Exp $
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
/* Modifications made by CPG Dev Team http://cpgnuke.com		*/
/* Last modification notes:							*/
/*										*/
/*	 $Id: admin_forumauth.php,v 9.6 2005/10/11 12:31:44 djmaze Exp $	  */
/*										*/
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
//
// Start program - define vars
//
//				View	  Read		Post	  Reply		Edit	 Delete	   Sticky	Announce	Vote	  Poll
$simple_auth_ary = array(
	0  => array(AUTH_ALL, AUTH_ALL, AUTH_ALL, AUTH_ALL, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_MOD, AUTH_REG, AUTH_REG),
	1  => array(AUTH_ALL, AUTH_ALL, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_MOD, AUTH_REG, AUTH_REG),
	2  => array(AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_REG, AUTH_MOD, AUTH_MOD, AUTH_REG, AUTH_REG),
	3  => array(AUTH_ALL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_MOD, AUTH_ACL, AUTH_ACL),
	4  => array(AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_ACL, AUTH_MOD, AUTH_ACL, AUTH_ACL),
	5  => array(AUTH_ALL, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD),
	6  => array(AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD, AUTH_MOD),
);

$simple_auth_types = array($lang['Public'], $lang['Registered'], $lang['Registered'] . ' [' . $lang['Hidden'] . ']', $lang['Private'], $lang['Private'] . ' [' . $lang['Hidden'] . ']', $lang['Moderators'], $lang['Moderators'] . ' [' . $lang['Hidden'] . ']');

$forum_auth_fields = array('auth_view', 'auth_read', 'auth_post', 'auth_reply', 'auth_edit', 'auth_delete', 'auth_sticky', 'auth_announce', 'auth_vote', 'auth_pollcreate');

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
	'auth_pollcreate' => $lang['Pollcreate']);

$forum_auth_levels = array('ALL', 'REG', 'PRIVATE', 'MOD', 'ADMIN');
$forum_auth_const = array(AUTH_ALL, AUTH_REG, AUTH_ACL, AUTH_MOD, AUTH_ADMIN);
//if (defined('BBAttach_mod')) {
	attach_setup_forum_auth($simple_auth_ary, $forum_auth_fields, $field_names);

if (isset($_GET[POST_FORUM_URL]) || isset($_POST[POST_FORUM_URL])) {
	$forum_id = (isset($_POST[POST_FORUM_URL])) ? intval($_POST[POST_FORUM_URL]) : intval($_GET[POST_FORUM_URL]);
	$forum_sql = "AND forum_id = $forum_id";
} else {
	unset($forum_id);
	$forum_sql = '';
}

if (isset($_GET['adv'])) {
	$adv = intval($_GET['adv']);
} else {
	unset($adv);
}

//
// Start program proper
//
if (isset($_POST['submit'])) {
	if (!empty($forum_id)) {
		$sql = '';
		if (isset($_POST['simpleauth'])) {
			$simple_ary = $simple_auth_ary[$_POST['simpleauth']];
			for($i = 0; $i < count($simple_ary); $i++) {
				$sql .= ( ( $sql != '' ) ? ', ' : '' ) . $forum_auth_fields[$i] . ' = ' . $simple_ary[$i];
			}
			if (is_array($simple_ary)) {
				$sql = "UPDATE " . FORUMS_TABLE . " SET $sql WHERE forum_id = $forum_id";
			}
		} else {
			for($i = 0; $i < count($forum_auth_fields); $i++) {
				$value = $_POST[$forum_auth_fields[$i]];
				if ( $forum_auth_fields[$i] == 'auth_vote' ) {
					if ( $_POST['auth_vote'] == AUTH_ALL ) {
						$value = AUTH_REG;
					}
				}
				$sql .= ( ( $sql != '' ) ? ', ' : '' ) .$forum_auth_fields[$i] . ' = ' . $value;
			}
			$sql = "UPDATE " . FORUMS_TABLE . " SET $sql WHERE forum_id = $forum_id";
		}

		if ($sql != '') { $db->sql_query($sql); }
		$forum_sql = '';
		$adv = 0;
	}

	url_refresh(adminlink('&do=forumauth&'.POST_FORUM_URL.'='.$forum_id));
	$message = $lang['Forum_auth_updated'] . '<br /><br />' . sprintf($lang['Click_return_forumauth'],	'<a href="'.adminlink('&amp;do=forumauth').'">', "</a>");
	message_die(GENERAL_MESSAGE, $message);
	return;

} // End of submit

//
// Get required information, either all forums if
// no id was specified or just the requsted if it
// was
//
$sql = "SELECT f.*
	FROM " . FORUMS_TABLE . " f, " . CATEGORIES_TABLE . " c
	WHERE c.cat_id = f.cat_id
	$forum_sql
	ORDER BY c.cat_order ASC, f.forum_order ASC";
$result = $db->sql_query($sql);
$forum_rows = $db->sql_fetchrowset($result);
$db->sql_freeresult($result);

if (empty($forum_id)) {
	//
	// Output the selection table if no forum id was specified
	//
	$result = $db->sql_uquery("SELECT cat_id, cat_title FROM " . CATEGORIES_TABLE . " ORDER BY cat_order");
	while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
		$category_rows[$row[0]] = $row[1];
	}
	$cat_id = 0;
	$template->set_filenames(array('body' => 'forums/admin/auth_select_body.html'));
	$select_list = '<select name="' . POST_FORUM_URL . '">';
	for ($i = 0; $i < (is_countable($forum_rows) ? count($forum_rows) : 0); $i++) {
		if ($cat_id != $forum_rows[$i]['cat_id']) {
			if ($cat_id > 0) $select_list .= '</optgroup>';
			$cat_id = $forum_rows[$i]['cat_id'];
			$select_list .= '<optgroup label="'.$category_rows[$forum_rows[$i]['cat_id']].'">';
		}
		$select_list .= '<option value="'.$forum_rows[$i]['forum_id'] . '">' . $forum_rows[$i]['forum_name'].'</option>';
	}
	$select_list .= '</optgroup></select>';
	$template->assign_vars(array(
		'L_AUTH_TITLE' => $lang['Auth_Control_Forum'],
		'L_AUTH_EXPLAIN' => $lang['Forum_auth_explain'],
		'L_AUTH_SELECT' => $lang['Select_a_Forum'],
		'L_LOOK_UP' => $lang['Look_up_Forum'],

		'S_AUTH_ACTION' => adminlink("&amp;do=forumauth"),
		'S_AUTH_SELECT' => $select_list,
		'S_HIDDEN_FIELDS' => ''
	));
} else {
	//
	// Output the authorisation details if an id was
	// specified
	//
	$template->set_filenames(array('body' => 'forums/admin/auth_forum_body.html'));

	$forum_name = $forum_rows[0]['forum_name'];

	reset($simple_auth_ary);
	foreach ($simple_auth_ary as $key => $auth_levels) {
     $matched = 1;
     for ($k = 0; $k < (is_countable($auth_levels) ? count($auth_levels) : 0); $k++) {
   			$matched_type = $key;
   			if ($forum_rows[0][$forum_auth_fields[$k]] != $auth_levels[$k]) {
   				$matched = 0;
   			}
   		}
     if ($matched) { break; }
 }

	//
	// If we didn't get a match above then we
	// automatically switch into 'advanced' mode
	//
	if (!isset($adv) && !$matched) { $adv = 1; }

	$s_column_span = 0;

	if (empty($adv)) {
		$simple_auth = '<select name="simpleauth">';
		for($j = 0; $j < count($simple_auth_types); $j++) {
			$selected = ( $matched_type == $j ) ? ' selected="selected"' : '';
			$simple_auth .= '<option value="' . $j . '"' . $selected . '>' . $simple_auth_types[$j] . '</option>';
		}
		$simple_auth .= '</select>';
		$template->assign_block_vars('forum_auth_titles', array(
			'CELL_TITLE' => $lang['Simple_mode'])
		);
		$template->assign_block_vars('forum_auth_data', array(
			'S_AUTH_LEVELS_SELECT' => $simple_auth)
		);
		$s_column_span++;
	} else {
		//
		// Output values of individual
		// fields
		//
		for($j = 0; $j < count($forum_auth_fields); $j++) {
			$custom_auth[$j] = '&nbsp;<select name="' . $forum_auth_fields[$j] . '">';
			for($k = 0; $k < count($forum_auth_levels); $k++) {
				$selected = ( $forum_rows[0][$forum_auth_fields[$j]] == $forum_auth_const[$k] ) ? ' selected="selected"' : '';
				$custom_auth[$j] .= '<option value="' . $forum_auth_const[$k] . '"' . $selected . '>' . $lang['Forum_' . $forum_auth_levels[$k]] . '</option>';
			}
			$custom_auth[$j] .= '</select>&nbsp;';
			$cell_title = $field_names[$forum_auth_fields[$j]];
			$template->assign_block_vars('forum_auth_titles', array(
				'CELL_TITLE' => $cell_title)
			);
			$template->assign_block_vars('forum_auth_data', array(
				'S_AUTH_LEVELS_SELECT' => $custom_auth[$j])
			);
			$s_column_span++;
		}
	}

	$adv_mode = empty($adv) ? '1' : '0';
	$switch_mode = adminlink("&amp;do=forumauth&amp;" . POST_FORUM_URL . "=" . $forum_id . "&adv=". $adv_mode);
	$switch_mode_text = empty($adv) ? $lang['Advanced_mode'] : $lang['Simple_mode'];
	$u_switch_mode = '<a href="' . $switch_mode . '">' . $switch_mode_text . '</a>';

	$s_hidden_fields = '<input type="hidden" name="' . POST_FORUM_URL . '" value="' . $forum_id . '">';

	$template->assign_vars(array(
		'FORUM_NAME' => $forum_name,

		'L_FORUM' => $lang['Forum'],
		'L_AUTH_TITLE' => $lang['Auth_Control_Forum'],
		'L_AUTH_EXPLAIN' => $lang['Forum_auth_explain'],
		'L_SUBMIT' => $lang['Submit'],
		'L_RESET' => $lang['Reset'],

		'U_SWITCH_MODE' => $u_switch_mode,

		'S_FORUMAUTH_ACTION' => adminlink("&amp;do=forumauth"),
		'S_COLUMN_SPAN' => $s_column_span,
		'S_HIDDEN_FIELDS' => $s_hidden_fields)
	);

}
