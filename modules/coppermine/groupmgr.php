<?php 
/***************************************************************************
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  Last modification notes:
  $Source: /cvs/html/modules/coppermine/groupmgr.php,v $
  $Revision: 9.7 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 02:34:40 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('GROUPMGR_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

if (!GALLERY_ADMIN_MODE) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);

function display_group_list()
{
	global $db,$CONFIG;
	global $lang_byte_units, $lang_yes, $lang_no;

	$result = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_USERGROUPS']} ORDER BY group_id");
	if (!$db->sql_numrows($result)) {
		$fields = '(group_id, group_name, group_quota, has_admin_access, can_rate_pictures, can_send_ecards, can_post_comments, can_upload_pictures, can_create_albums, pub_upl_need_approval, priv_upl_need_approval)';
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_USERGROUPS']} $fields VALUES (DEFAULT, 'Administrators', 0, 1, 1, 1, 1, 1, 1)");
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_USERGROUPS']} $fields VALUES (DEFAULT, 'Registered', 1024, 0, 1, 1, 1, 1, 1)");
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_USERGROUPS']} $fields VALUES (DEFAULT, 'Anonymous', 0, 0, 0, 0, 1, 0, 0)");
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_USERGROUPS']} $fields VALUES (DEFAULT, 'Banned', 0, 0, 0, 0, 0, 0, 0);");
		cpg_die(_CRITICAL_ERROR, 'Group table was empty !<br /><br />Default groups created, please reload this page', __FILE__, __LINE__);
	} 

	$field_list = array('can_rate_pictures', 'can_send_ecards', 'can_post_comments', 'can_upload_pictures', 'pub_upl_need_approval', 'can_create_albums', 'priv_upl_need_approval');

	while ($group = $db->sql_fetchrow($result)) {
		$group['group_name'] = $group['group_name'];

		if ($group['group_id'] > 4) {
			echo '
		<tr>
			<td class="tableb" style="padding-left: 1px; padding-right: 1px">
				<input type="checkbox" name="delete_group[]" value="'.$group['group_id'].'" class="checkbox" />
			</td>
';
		} else {
			echo '
		<tr>
			<td class="tableb">&nbsp;</td>
';
		} 
		echo '
			<td class="tableb">
				<input type="hidden" name="group_id[]" value="'.$group['group_id'].'" />
				<input type="text" name="group_name_'.$group['group_id'].'" value="'.$group['group_name'].'" class="textinput" />
			</td>
			<td class="tableb" style="white-space: nowrap;">
				<input type="text" name="group_quota_'.$group['group_id'].'" value="'.$group['group_quota'].'" size="10" class="textinput" /> '.$lang_byte_units[1].'
			</td>
';
		foreach ($field_list as $field_name) {
			$value = $group[$field_name];
			$yes_selected = ($value == 1) ? 'selected="selected"' : '';
			$no_selected = ($value == 0) ? 'selected="selected"' : '';
			echo '
			<td class="tableb" align="center">
				<select name="'.$field_name.'_'.$group['group_id'].'" class="listbox">
					<option value="1" '.$yes_selected.'>'.YES.'</option>
					<option value="0" '.$no_selected.'>'.NO.'</option>
				</select>
			</td>
';
		} 
		echo '
		</tr>
';
	} // while
	$db->sql_freeresult($result);
} 

function get_post_var($var)
{
   
	if (!isset($_POST[$var])) cpg_die(_CRITICAL_ERROR, PARAM_MISSING . " ($var)", __FILE__, __LINE__);
	return $_POST[$var];
} 

function process_post_data()
{
	global $db,$CONFIG;

	$field_list = array('group_name', 'group_quota', 'can_rate_pictures', 'can_send_ecards', 'can_post_comments', 'can_upload_pictures', 'pub_upl_need_approval', 'can_create_albums', 'priv_upl_need_approval');

	$group_id_array = get_post_var('group_id');
	foreach ($group_id_array as $key => $group_id) {
		$set_statment = '';
		foreach ($field_list as $field) {
			if (!isset($_POST[$field . '_' . $group_id])) cpg_die(_CRITICAL_ERROR, PARAM_MISSING . " ({$field}_{$group_id})", __FILE__, __LINE__);
			if ($field == 'group_name') {
				$set_statment .= $field . "='" . $_POST[$field . '_' . $group_id] . "',";
			} else {
				$set_statment .= $field . "='" . intval($_POST[$field . '_' . $group_id]) . "',";
			} 
		} 
		$set_statment = substr($set_statment, 0, -1);
		$db->sql_query("UPDATE {$CONFIG['TABLE_USERGROUPS']} SET $set_statment WHERE group_id = '$group_id'");
	} 
} 

if (isset($_POST) && count($_POST)) {
	if (isset($_POST['del_sel']) && isset($_POST['delete_group']) && is_array($_POST['delete_group'])) {
		foreach($_POST['delete_group'] as $group_id) {
			$db->sql_query("DELETE FROM {$CONFIG['TABLE_USERGROUPS']} WHERE group_id = '" . intval($group_id) . "' LIMIT 1");
			$db->sql_query("UPDATE {$CONFIG['TABLE_USERS']} SET user_group_cp = '2' WHERE user_group_cp = '" . intval($group_id) . "'");
		} 
	} elseif (isset($_POST['new_group'])) {
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_USERGROUPS']} (group_name) VALUES ('')");
	} elseif (isset($_POST['apply_modifs'])) {
		process_post_data();
	} 
} 

pageheader(GROUP_TITLE);
echo '
<script language="javascript">
function confirmDel() { return confirm("'.CONFIRM_DEL.'"); }
</script>
';

starttable('100%');
echo '
	<tr>
		<td class="tableh1" colspan="2"><b><span class="statlink">'.GROUP_NAME.'</span></b></td>
		<td class="tableh1"><b><span class="statlink">'.DISK_QUOTA.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.CAN_RATE.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.CAN_SEND_ECARDS.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.CAN_POST_COM.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.CAN_UPLOAD.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.APPROVAL_1.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.CAN_HAVE_GALLERY.'</span></b></td>
		<td class="tableh1" align="center"><b><span class="statlink">'.APPROVAL_2.'</span></b></td>
	</tr>
	<form method="post" action="'.getlink("&amp;file=groupmgr").'" enctype="multipart/form-data" accept-charset="utf-8">
';

display_group_list();

echo '
	<tr>
		<td colspan="10" class="tableh2"><b>'.NOTES.'</b></td>
	</tr><tr>
		<td colspan="10" class="tableb">'.NOTE1.'</td>
	</tr><tr>
		<td colspan="10" class="tableb">'.NOTE2.'</td>
	</tr><tr>
		<td colspan="10" align="center" class="tablef">
			<input type="submit" name="apply_modifs" value="'.APPLY.'" class="button" />&nbsp;&nbsp;&nbsp;
			<input type="submit" name="new_group" value="'.CREATE_NEW_GROUP.'" class="button" />&nbsp;&nbsp;&nbsp;
			<input type="submit" name="del_sel" value="'.DEL_GROUPS.'" onclick="return confirmDel()" class="button" />
		</td>
	</tr>
	</form>
';

endtable();
pagefooter();
