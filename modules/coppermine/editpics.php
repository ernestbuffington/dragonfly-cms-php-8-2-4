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
  $Source: /cvs/html/modules/coppermine/editpics.php,v $
  $Revision: 9.7 $
  $Author: akamu $
  $Date: 2006/01/07 01:15:57 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }
define('EDITPICS_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (!(GALLERY_ADMIN_MODE || USER_ADMIN_MODE)) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);


define('UPLOAD_APPROVAL_MODE', isset($_GET['mode']));
define('EDIT_PICTURES_MODE', !isset($_GET['mode']));

if (isset($album)) {
	$album_id = $album;
} else {
	$album_id = -1;
} 

if (UPLOAD_APPROVAL_MODE && !GALLERY_ADMIN_MODE) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);

if (EDIT_PICTURES_MODE) {
	$result = $db->sql_query("SELECT title, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = '$album_id'");
	if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
	$ALBUM_DATA = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	$cat = $ALBUM_DATA['category'];
	$actual_cat = $cat;
	if ($cat != FIRST_USER_CAT + USER_ID && !GALLERY_ADMIN_MODE) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
} else {
	$ALBUM_DATA = array();
} 

$THUMB_ROWSPAN = 5;
if ($CONFIG['user_field1_name'] != '') $THUMB_ROWSPAN++;
if ($CONFIG['user_field2_name'] != '') $THUMB_ROWSPAN++;
if ($CONFIG['user_field3_name'] != '') $THUMB_ROWSPAN++;
if ($CONFIG['user_field4_name'] != '') $THUMB_ROWSPAN++;

$USER_ALBUMS_ARRAY = array(0 => array());
// Type 0 => input
// 1 => album list
// 2 => text_area
// 3 => picture information
$data = array(
	array(PIC_INFO, '', 3),
	array(ALBUM, 'aid', 1),
	array(EDIT_TITLE, 'title', 0, 255),
	array(DESC, 'caption', 2, $CONFIG['max_img_desc_length']),
	array(KEYWORDS, 'keywords', 0, 255),
	array($CONFIG['user_field1_name'], 'user1', 0, 255),
	array($CONFIG['user_field2_name'], 'user2', 0, 255),
	array($CONFIG['user_field3_name'], 'user3', 0, 255),
	array($CONFIG['user_field4_name'], 'user4', 0, 255),
	array('', '', 4)
	);

function get_post_var($var, $pid,$html2bb=false)
{
	$var_name = $var . $pid;
	if (!isset($_POST[$var_name])) cpg_die(_CRITICAL_ERROR, PARAM_MISSING . " ($var_name)", __FILE__, __LINE__);
	if ($html2bb){
			return Fix_Quotes(html2bb($_POST[$var_name]));		
	}else{
			return Fix_Quotes($_POST[$var_name],1);
	}
} 

function process_post_data()
{
	global $db,$CONFIG;
	global $user_albums_list;

	$user_album_set = array();
	foreach($user_albums_list as $album) $user_album_set[$album['aid']] = 1;

	if (!is_array($_POST['pid'])) cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);
	$pid_array = &$_POST['pid'];
	
   
		foreach($pid_array as $pid) {
		//init.inc  $pid = (int)$pid;
		if (!is_numeric($aid.$pid))cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);
		$aid = get_post_var('aid', $pid);
		$title = get_post_var('title', $pid);
		$caption = get_post_var('caption', $pid,1);
		$keywords = get_post_var('keywords', $pid);
		$user1 = get_post_var('user1', $pid);
		$user2 = get_post_var('user2', $pid);
		$user3 = get_post_var('user3', $pid);
		$user4 = get_post_var('user4', $pid);

		$delete = isset($_POST['delete' . $pid]);
		$reset_vcount = isset($_POST['reset_vcount' . $pid]);
		$reset_votes = isset($_POST['reset_votes' . $pid]);
		$del_comments = isset($_POST['del_comments' . $pid]) || $delete;

		$query = "SELECT category, filepath, filename FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND pid='$pid'";
		$result = $db->sql_query($query);
		if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
		$pic = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!GALLERY_ADMIN_MODE) {
			if ($pic['category'] != FIRST_USER_CAT + USER_ID) cpg_die(_ERROR, PERM_DENIED . "<br />(picture category = {$pic['category']}/ $pid)", __FILE__, __LINE__);
			if (!isset($user_album_set[$aid])) cpg_die(_ERROR, PERM_DENIED . "<br />(target album = $aid)", __FILE__, __LINE__);
		} 

		$update = "aid = '" . $aid . "' ";
		$update .= ", title = '" . $title . " ' ";
		$update .= ", caption = '" . $caption . "' ";
		$update .= ", keywords = '" . $keywords . "' ";
		$update .= ", user1 = '" . $user1 . "' ";
		$update .= ", user2 = '" . $user2 . "' ";
		$update .= ", user3 = '" . $user3 . "' ";
		$update .= ", user4 = '" . $user4 . "' ";

		if ($reset_vcount) $update .= ", hits = '0'";
		if ($reset_votes) $update .= ", pic_rating = '0', votes = '0'";

		if (UPLOAD_APPROVAL_MODE) {
			$approved = get_post_var('approved', $pid);
			if ($approved == '1') {
				$update .= ", approved = '1'";
			} elseif ($approved == 'DELETE') {
				$del_comments = 1;
				$delete = 1;
			} 
		} 

		if ($del_comments) {
			$result = $db->sql_query("DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid='$pid'");
		} 

		if ($delete) {
			$dir = $CONFIG['fullpath'];
			$file = $pic['filename'];
			if (!is_writable($dir)) cpg_die(_CRITICAL_ERROR, sprintf(DIRECTORY_RO, $dir), __FILE__, __LINE__);

			$files = array($dir . $file, $dir . $CONFIG['normal_pfx'] . $file, $dir . $CONFIG['thumb_pfx'] . $file);
			foreach ($files as $currFile) {
				if (is_file($currFile)) unlink($currFile);
			} 
			$result = $db->sql_query("DELETE FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='$pid'");
		} else {
			$result = $db->sql_query("UPDATE {$CONFIG['TABLE_PICTURES']} SET $update WHERE pid='$pid'");
		} 
	} 
	speedup_pictures();
}

function form_label($text)
{
	global $CURENT_PIC;

	echo <<<EOT
		<tr>
				<td class="tableh2" colspan="3">
						<b>$text</b>
				</td>
		</tr>

EOT;
} 

function form_pic_info($text)
{
	global $CURRENT_PIC, $THUMB_ROWSPAN, $CONFIG, $lang_byte_units, $db;

	if (UPLOAD_APPROVAL_MODE) {
			$vf_sql = "SELECT username FROM " . $CONFIG['TABLE_USERS'] . " WHERE user_id='" . $CURRENT_PIC['owner_id'] . "'";
	$vf_result = $db->sql_query($vf_sql);
	$vf_row = $db->sql_fetchrow($vf_result);
	$up_by = $vf_row[0];
	$up_by_link = '<a title="View Profile" href="'.getlink('Your_Account&amp;profile=' . $CURRENT_PIC['owner_id']) . '" target="_blank">' .$up_by. '</a>';
	$up_by_edit_link = 'Edit User: <a title="Edit Profile" href="' .getlink('&amp;file=usermgr&amp;opp=edit&amp;user_id=' . $CURRENT_PIC['owner_id']) . '" target="_blank">' .$up_by. '</a>';
	$pic_info = $CURRENT_PIC['pwidth'] . 'x' . $CURRENT_PIC['pheight'] . ' - ' . ($CURRENT_PIC['filesize'] >> 10) . $lang_byte_units[1].' Uploaded by: '.$up_by_link.' '.$up_by_edit_link;
	} else {
		$pic_info = sprintf(PIC_INFO_STR, $CURRENT_PIC['pwidth'], $CURRENT_PIC['pheight'], ($CURRENT_PIC['filesize'] >> 10), $CURRENT_PIC['hits'], $CURRENT_PIC['votes']);
	} 

	$winsizeX = $CURRENT_PIC['pwidth'] + 16;
	$winsizeY = $CURRENT_PIC['pheight'] + 16;
	$thispid = $CURRENT_PIC['pid'];
			$thumb_link = '<a href="'.getlink("&amp;file=displayimagepopup&amp;pid=$thispid&amp;fullsize=1")."\" target=\"" . uniqid(rand()) . "\" onClick=\"MM_openBrWindow('".getlink("&amp;file=displayimagepopup&amp;pid=$thispid&amp;fullsize=1")."','" . uniqid(rand()) . "','toolbar=yes,status=yes,resizable=yes,scrollbars=yes,width=$winsizeX,height=$winsizeY');return false\">";
			$thumb_url = get_pic_url($CURRENT_PIC, 'thumb');
	//$thumb_link = $CPG_URL . '&amp;file=displayimage&amp;pos=' . (- $CURRENT_PIC['pid']);
	$filename = htmlprepare($CURRENT_PIC['filename']);
	echo <<<EOT
		<input type="hidden" name="pid[]" value="{$CURRENT_PIC['pid']}" />
		<tr>
				<td class="tableh2" colspan="3">
						<b>$filename</b>
				</td>
		</tr>
		<tr>
				<td class="tableb">
						$text
				</td>
				<td class="tableb">
						$pic_info
				</td>
				   <td class="tableb" align="center" rowspan="$THUMB_ROWSPAN">
						$thumb_link<img src="$thumb_url" class="image" border="0" alt="" /><br /></a>
			</td>
		</tr>

EOT;
} 

function form_options()
{
	global $CURRENT_PIC;

	if (UPLOAD_APPROVAL_MODE) {
		echo '
		<tr>
				<td class="tableb" colspan="3" align="center">
						<b><input type="radio" name="approved'.$CURRENT_PIC['pid'].'" value="1" class="radio" />'.APPROVE.'</b>&nbsp;
						<b><input type="radio" name="approved'.$CURRENT_PIC['pid'].'" value="0" class="radio" checked="checked" />'.POSTPONE_APP.'</b>&nbsp;
						<b><input type="radio" name="approved'.$CURRENT_PIC['pid'].'" value="DELETE" class="radio" />'.DEL_PIC.'</b>&nbsp;
				</td>
		</tr>

';
	} else {
		echo '
		<tr>
				<td class="tableb" colspan="3" align="center">
						<b><input type="checkbox" name="delete'.$CURRENT_PIC['pid'].'" value="1" class="checkbox" />'.DEL_PIC.'</b>&nbsp;
						<b><input type="checkbox" name="reset_vcount'.$CURRENT_PIC['pid'].'" value="1" class="checkbox" />'.RESET_VIEW_COUNT.'</b>&nbsp;
						<b><input type="checkbox" name="reset_votes'.$CURRENT_PIC['pid'].'" value="1" class="checkbox" />'.RESET_VOTES.'</b>&nbsp;
						<b><input type="checkbox" name="del_comments'.$CURRENT_PIC['pid'].'" value="1" class="checkbox" />'.DEL_COMM.'</b>&nbsp;
				</td>
		</tr>

';
	} 
} 

function form_input($text, $name, $max_length)
{
	global $CURRENT_PIC;

	$value = $CURRENT_PIC[$name];
	$name .= $CURRENT_PIC['pid'];
	if ($text == '') {
		echo "		<input type=\"hidden\" name=\"$name\" value=\"\" />\n";
		return;
	} 

	echo <<<EOT
		<tr>
			<td class="tableb">
						$text
		</td>
		<td width="100%" class="tableb" valign="top">
				<input type="text" style="width: 100%" name="$name" maxlength="$max_length" value="$value" class="textinput" />
				</td>
		</tr>

EOT;
} 

function form_alb_list_box($text, $name)
{
	global $CONFIG, $CURRENT_PIC;
	global $user_albums_list, $public_albums_list;

	$sel_album = $CURRENT_PIC['aid'];

	$name .= $CURRENT_PIC['pid'];
	echo <<<EOT
		<tr>
			<td class="tableb">
						$text
		</td>
		<td class="tableb" valign="top">
				<select name="$name" class="listbox">

EOT;
	foreach($public_albums_list as $album) {
		echo '						<option value="' . $album['aid'] . '"' . ($album['aid'] == $sel_album ? ' selected="selected"' : '') . '>' . $album['title'] . "</option>\n";
	} 
	if (!GALLERY_ADMIN_MODE) {
		foreach($user_albums_list as $album) {
			echo '						<option value="' . $album['aid'] . '"' . ($album['aid'] == $sel_album ? ' selected="selected"' : '') . '>* ' . $album['title'] . "</option>\n";
		}
	}

	echo <<<EOT
						</select>
				</td>
		</tr>

EOT;
} 

function form_textarea($text, $name, $max_length)
{
	global $ALBUM_DATA, $CURRENT_PIC;

	$value = $CURRENT_PIC[$name];

	$name .= $CURRENT_PIC['pid'];
	echo <<<EOT
		<tr>
				<td class="tableb" valign="top">
						$text
				</td>
				<td class="tableb" valign="top">
						<textarea name="$name" rows="5" cols="40" wrap="virtual" class="textinput" style="width: 100%;" onkeydown="textCounter(this, $max_length);" onkeyup="textCounter(this, $max_length);">$value</textarea>
				</td>
		</tr>
EOT;
} 

function create_form(&$data)
{
	foreach($data as $element) {
		if ((is_array($element))) {
			switch ($element[2]) {
				case 0 :
					form_input($element[0], $element[1], $element[3]);
					break;
				case 1 :
					form_alb_list_box($element[0], $element[1]);
					break;
				case 2 :
					form_textarea($element[0], $element[1], $element[3]);
					break;
				case 3 :
					form_pic_info($element[0]);
					break;
				case 4 :
					form_options();
					break;
				default:
					cpg_die(_CRITICAL_ERROR, 'Invalid action for form creation', __FILE__, __LINE__);
			} // switch
		} else {
			form_label($element);
		} 
	} 
} 

function get_user_albums($user_id)
{
	global $db, $CONFIG, $USER_ALBUMS_ARRAY, $user_albums_list,$title;

	if (!isset($USER_ALBUMS_ARRAY[$user_id])) {
		$user_albums = $db->sql_query("SELECT aid, title FROM {$CONFIG['TABLE_ALBUMS']} WHERE category='" . (FIRST_USER_CAT + $user_id) . "' ORDER BY title");
		if ($db->sql_numrows($user_albums)) {
			$user_albums_list = $db->sql_fetchrowset($user_albums);
		} else {
			$user_albums_list = array();
		} 
		$db->sql_freeresult($user_albums);
		$USER_ALBUMS_ARRAY[$user_id] = $user_albums_list;
	} else {
		$user_albums_list = &$USER_ALBUMS_ARRAY[$user_id];
	} 
} 

//define('META_LNK','&amp;cat=0');
pageheader((isset($title) ? $title : ''));

if (GALLERY_ADMIN_MODE) {
	$public_albums_list = get_albumlist();
}
else {
	$public_albums_list = array();
} 

get_user_albums(USER_ID);

if (isset($_POST['pid']) && is_array($_POST['pid'])){ process_post_data(); }!

$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$count = isset($_GET['count']) ? (int)$_GET['count'] : 25;
$next_target = getlink('&amp;file=editpics&amp;album=' . $album_id . '&amp;start=' . ($start + $count) . '&amp;count=' . $count);
$prev_target = getlink('&amp;file=editpics&amp;album=' . $album_id . '&amp;start=' . max(0, $start - $count) . '&amp;count=' . $count);
$s50 = $count == 50 ? 'selected="selected"' : '';
$s75 = $count == 75 ? 'selected="selected"' : '';
$s100 = $count == 100 ? 'selected="selected"' : '';

if (UPLOAD_APPROVAL_MODE) {
	$result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_PICTURES']} WHERE approved = '0'");
	$nbEnr = $db->sql_fetchrow($result);
	$pic_count = $nbEnr[0];

	$result = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_PICTURES']} WHERE approved = '0' ORDER BY pid LIMIT $start, $count");
	$form_target = getlink('&amp;file=editpics&amp;mode=upload_approval&amp;start=' . $start . '&amp;count=' . $count);
	$title = UPL_APPROVAL;
} else {
	$result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_PICTURES']} WHERE aid = '$album_id'");
	$nbEnr = $db->sql_fetchrow($result);
	$pic_count = $nbEnr[0];
	$db->sql_freeresult($result);

	$result = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_PICTURES']} WHERE aid = '$album_id' ORDER BY filename LIMIT $start, $count");
	$form_target = getlink('&amp;file=editpics&amp;album=' . $album_id . '&amp;start=' . $start . '&amp;count=' . $count);
	$title = EDIT_PICS;
} 

if (!$db->sql_numrows($result)){
	$redirect = getlink("$module_name");
	pageheader(INFO, $redirect);
	msg_box(INFO, NO_MORE_IMAGES, CONTINU, $redirect);
	pagefooter();
}

if ($start + $count < $pic_count) {
	$next_link = "<a href=\"$next_target\"><b>".SEE_NEXT."</b></a>&nbsp;&nbsp;-&nbsp;&nbsp;";
} else {
	$next_link = '';
} 

if ($start > 0) {
	$prev_link = "<a href=\"$prev_target\"><b>".SEE_PREV."</b></a>&nbsp;&nbsp;-&nbsp;&nbsp;";
} else {
	$prev_link = '';
} 

$pic_count_text = sprintf(N_PIC, $pic_count);

starttable("100%", $title, 3);
echo <<<EOT
<script language="JavaScript">
function textCounter(field, maxlimit) {
	if (field.value.length > maxlimit) // if too long...trim it!
	field.value = field.value.substring(0, maxlimit);
}
</script>
EOT;
$chset =_CHARSET;
echo '
	<form method="post" action="'.$form_target.'" enctype="multipart/form-data" accept-charset="utf-8">
	<tr>
		<td class="tableb" colspan="3" align="center">
			<b>'.$pic_count_text.'</b>&nbsp;&nbsp;-&nbsp;&nbsp;
			'.$prev_link.'
			'.$next_link.'
			<b>'.N_OF_PIC_TO_DISP.'</b>
			<select onchange="if(this.options[this.selectedIndex].value) window.location.href=\''.getlink('',0,1).'&file=editpics&album='.$album_id.'&start='.$start.'&count=\'+this.options[this.selectedIndex].value;"  name="count" class="listbox">
				<option value="25">25</option>
				<option value="50" '.$s50.'>50</option>
				<option value="75" '.$s75.'>75</option>
				<option value="100" '.$s100.'>100</option>
			</select>
		</td>
	</tr>
';

while ($CURRENT_PIC = $db->sql_fetchrow($result)) {
	if (GALLERY_ADMIN_MODE) get_user_albums($CURRENT_PIC['owner_id']);
	create_form($data);
} // while
$db->sql_freeresult($result);

echo '
	<tr>
		<td colspan="3" align="center" class="tablef"><input type="submit" value="'.APPLY.'" class="button" /></td>
	</tr>
	</form>
';
endtable();
pagefooter();