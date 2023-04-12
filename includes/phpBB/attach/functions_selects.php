<?php
/***************************************************************************
 *							  functions_selects.php
 *							  -------------------
 *	 begin				  : Saturday, Mar 30, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: functions_selects.php,v 9.1 2005/02/22 05:08:24 trevor Exp $
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
// Functions to build select boxes ;)
//

function group_select($select_name, $default_group = 0)
{
	global $db, $lang;
	$result = $db->sql_query("SELECT group_id, group_name FROM " . EXTENSION_GROUPS_TABLE . " ORDER BY group_name");
	$group_select = '<select name="' . $select_name . '">';
	if ( $db->sql_numrows($result) > 0 ) {
		$group_name = $db->sql_fetchrowset($result);
		$group_name[$db->sql_numrows($result)]['group_id'] = 0;
		$group_name[$db->sql_numrows($result)]['group_name'] = $lang['Not_assigned'];

		for($i = 0; $i < (is_countable($group_name) ? count($group_name) : 0); $i++) {
			if ($default_group < 1) {
				$selected = ($i == 0) ? ' selected="selected"' : '';
			} else {
				$selected = ( $group_name[$i]['group_id'] == $default_group ) ? ' selected="selected"' : '';
			}
			$group_select .= '<option value="' . $group_name[$i]['group_id'] . '"' . $selected . '>' . $group_name[$i]['group_name'] . '</option>';
		}
	}
	$group_select .= '</select>';
	return $group_select;
}

function download_select($select_name, $group_id = 0)
{
	global $db, $types_download, $modes_download;
	if ($group_id > 0) {
		$result = $db->sql_query("SELECT download_mode FROM " . EXTENSION_GROUPS_TABLE . " WHERE group_id = " . $group_id);
		if ( $db->sql_numrows($result) == 0 ) { return (''); }
		$row = $db->sql_fetchrow($result);
		$download_mode = $row['download_mode'];
	}
	$group_select = '<select name="' . $select_name . '">';
	for ($i = 0; $i < count($types_download); $i++) {
		if ($group_id < 1) {
			$selected = ( $types_download[$i] == INLINE_LINK ) ? ' selected="selected"' : '';
		} else {
			$selected = ( $row['download_mode'] == $types_download[$i] ) ? ' selected="selected"' : '';
		}
		$group_select .= '<option value="' . $types_download[$i] . '"' . $selected . '>' . $modes_download[$i] . '</option>';
	}
	$group_select .= '</select>';
	return($group_select);
}

function category_select($select_name, $group_id = 0)
{
	$category_type = null;
 global $db, $types_category, $modes_category;
	$result = $db->sql_query("SELECT group_id, cat_id FROM " . EXTENSION_GROUPS_TABLE);
	$rows = $db->sql_fetchrowset($result);
	$num_rows = $db->sql_numrows($result);
	$type_category = -1;
	if ( $num_rows > 0 ) {
		for ($i = 0; $i < $num_rows; $i++) {
			if ($group_id == $rows[$i]['group_id']) {
				$category_type = $rows[$i]['cat_id'];
			}
		}
	}
	$types = array(NONE_CAT);
	$modes = array('none');
	for ($i = 0; $i < count($types_category); $i++) {
		$types[] = $types_category[$i];
		$modes[] = $modes_category[$i];
	}
	$group_select = '<select name="' . $select_name . '" style="width:100px">';
	for($i = 0; $i < count($types); $i++) {
		if ($group_id < 1) {
			$selected = ( $types[$i] == NONE_CAT ) ? ' selected="selected"' : '';
		} else {
			$selected = ( $types[$i] == $category_type ) ? ' selected="selected"' : '';
		}
		$group_select .= '<option value="' . $types[$i] . '"' . $selected . '>' . $modes[$i] . '</option>';
	}
	$group_select .= '</select>';
	return($group_select);
}

function size_select($select_name, $size_compare)
{
	global $lang;
	$size_types_text = array($lang['Bytes'], $lang['KB'], $lang['MB']);
	$size_types = array('b', 'kb', 'mb');
	$select_field = '<select name="' . $select_name . '">';
	for ($i = 0; $i < count($size_types_text); $i++) {
		$selected = ($size_compare == $size_types[$i]) ? ' selected="selected"' : '';
		$select_field .= '<option value="' . $size_types[$i] . '"' . $selected . '>' . $size_types_text[$i] . '</option>';
	}
	$select_field .= '</select>';
	return ($select_field);
}

function quota_limit_select($select_name, $default_quota = -1)
{
	$quota_name = [];
 global $db, $lang;
	$result = $db->sql_query("SELECT quota_limit_id, quota_desc FROM " . QUOTA_LIMITS_TABLE . " ORDER BY quota_limit ASC");
	$quota_select = '<select name="' . $select_name . '">';
	$quota_name[0]['quota_limit_id'] = -1;
	$quota_name[0]['quota_desc'] = $lang['Not_assigned'];
	if ( ($db->sql_numrows($result)) > 0 ) {
		$rows = $db->sql_fetchrowset($result);
		for ($i = 0; $i < (is_countable($rows) ? count($rows) : 0); $i++) {
			$quota_name[] = $rows[$i];
		}
	}
	for($i = 0; $i < count($quota_name); $i++) {
		$selected = ( $quota_name[$i]['quota_limit_id'] == $default_quota ) ? ' selected="selected"' : '';
		$quota_select .= '<option value="' . $quota_name[$i]['quota_limit_id'] . '"' . $selected . '>' . $quota_name[$i]['quota_desc'] . '</option>';
	}
	$quota_select .= '</select>';
	return($quota_select);
}

function default_quota_limit_select($select_name, $default_quota = 0)
{
	$quota_name = [];
 global $db, $lang;
	$result = $db->sql_query("SELECT quota_limit_id, quota_desc FROM " . QUOTA_LIMITS_TABLE . " ORDER BY quota_limit ASC");
	$quota_select = '<select name="' . $select_name . '">';
	$quota_name[0]['quota_limit_id'] = 0;
	$quota_name[0]['quota_desc'] = $lang['No_quota_limit'];
	if ( ($db->sql_numrows($result)) > 0 ) {
		$rows = $db->sql_fetchrowset($result);
		for ($i = 0; $i < (is_countable($rows) ? count($rows) : 0); $i++) {
			$quota_name[] = $rows[$i];
		}
	}
	for($i = 0; $i < count($quota_name); $i++) {
		$selected = ( $quota_name[$i]['quota_limit_id'] == $default_quota ) ? ' selected="selected"' : '';
		$quota_select .= '<option value="' . $quota_name[$i]['quota_limit_id'] . '"' . $selected . '>' . $quota_name[$i]['quota_desc'] . '</option>';
	}
	$quota_select .= '</select>';
	return($quota_select);
}