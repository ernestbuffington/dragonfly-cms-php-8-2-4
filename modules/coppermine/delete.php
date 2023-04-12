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
  $Source: /cvs/html/modules/coppermine/delete.php,v $
  $Revision: 9.3 $
  $Author: djmaze $
  $Date: 2005/10/14 14:49:39 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('DELETE_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

/**
 * Local functions definition
 */

$header_printed = false;
$need_caption = false;

function output_table_header()
{
	global $header_printed, $need_caption;

	$header_printed = true;
	$need_caption = true;

	?><tr>
<td class="tableh2"><b>Picture</b></td>
<td class="tableh2" align="center"><b>F</b></td>
<td class="tableh2" align="center"><b>N</b></td>
<td class="tableh2" align="center"><b>T</b></td>
<td class="tableh2" align="center"><b>C</b></td>
<td class="tableh2" align="center"><b>D</b></td>
</tr>
<?php
} 

function output_caption()
{
	global  $CPG_M_DIR;

	?><tr><td colspan="6" class="tableb">&nbsp;</td></tr>
<tr><td colspan="6" class="tableh2"><b><?php echo CAPTION ?></b></tr>
<tr><td colspan="6" class="tableb">
<table cellpadding="1" cellspacing="0">
<tr><td><b>F</b></td><td>:</td><td><?php echo FS_PIC ?></td><td width="20">&nbsp;</td><td><img src="<?php echo $CPG_M_DIR;
	?>/images/green.gif" border="0" width="12" height="12" align="absmiddle"></td><td>:</td><td><?php echo DEL_SUCCESS ?></td></tr>
<tr><td><b>N</b></td><td>:</td><td><?php echo NS_PIC ?></td><td width="20">&nbsp;</td><td><img src="<?php echo $CPG_M_DIR;
	?>/images/red.gif" border="0" width="12" height="12" align="absmiddle"></td><td>:</td><td><?php echo ERR_DEL ?></td></tr>
<tr><td><b>T</b></td><td>:</td><td><?php echo THUMB_PIC ?></td></tr>
<tr><td><b>C</b></td><td>:</td><td><?php echo COMMENT ?></td></tr>
<tr><td><b>D</b></td><td>:</td><td><?php echo IM_IN_ALB ?></td></tr>
</table>
</td>
</tr>
<?php
} 

function delete_picture($pid)
{
	global $db, $CONFIG, $header_printed,  $CPG_M_DIR, $CLASS;

	if (!$header_printed)
		output_table_header();

	$green = "<img src=\"" . $CPG_M_DIR . "/images/green.gif\" border=\"0\" width=\"12\" height=\"12\"><br />";
	$red = "<img src=\"" . $CPG_M_DIR . "/images/red.gif\" border=\"0\" width=\"12\" height=\"12\"><br />";
	if ($CLASS['member']->demo){
			cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
	}   
	if (GALLERY_ADMIN_MODE) {
		$query = "SELECT aid, filepath, filename FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='$pid'";
		$result = $db->sql_query($query, false, __FILE__, __LINE__);
		if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
		$pic = $db->sql_fetchrow($result);
	} else {
		$query = "SELECT {$CONFIG['TABLE_PICTURES']}.aid as aid, category, filepath, filename FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND pid='$pid'";
		$result = $db->sql_query($query, false, __FILE__, __LINE__);
		if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
		$pic = $db->sql_fetchrow($result);
		if ($pic['category'] != FIRST_USER_CAT + USER_ID) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
	} 

	$aid = $pic['aid'];
	$dir = $pic['filepath'];
	$file = $pic['filename'];

	if (!is_writable($dir)) cpg_die(_CRITICAL_ERROR, sprintf(DIRECTORY_RO, htmlprepare($dir)), __FILE__, __LINE__);

	echo "<td class=\"tableb\">" . htmlprepare($file) . "</td>";

	$files = array($dir . $file, $dir . $CONFIG['normal_pfx'] . $file, $dir . $CONFIG['thumb_pfx'] . $file);
	foreach ($files as $currFile) {
		echo "<td class=\"tableb\" align=\"center\">";
		if (is_file($currFile)) {
			if (unlink($currFile))
				echo $green;
			else
				echo $red;
		} else
			echo "&nbsp;";
		echo "</td>";
	} 

	$result = $db->sql_query("DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid='$pid'", false, __FILE__, __LINE__);
	echo "<td class=\"tableb\" align=\"center\">";
	if ($db->sql_affectedrows() > 0)
		echo $green;
	else
		echo "&nbsp;";
	echo "</td>";

	$result = $db->sql_query("DELETE FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='$pid'", false, __FILE__, __LINE__);
	echo "<td class=\"tableb\" align=\"center\">";
	if ($db->sql_affectedrows() > 0)
		echo $green;
	else
		echo $red;
	echo "</td>";

	echo "</tr>\n";

	return $aid;
} 

function delete_album($aid)
{
	global $db,$CONFIG;
	
	$result = $db->sql_query("SELECT title, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid ='$aid'",false, __FILE__, __LINE__);
	if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
	$album_data = $db->sql_fetchrow($result);

	if (!GALLERY_ADMIN_MODE) {
		if ($album_data['category'] != FIRST_USER_CAT + USER_ID) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
	} 

	$result = $db->sql_query("SELECT pid FROM {$CONFIG['TABLE_PICTURES']} WHERE aid='$aid'",false, __FILE__, __LINE__);
	// Delete all files
	while ($pic = $db->sql_fetchrow($result)) {
		delete_picture($pic['pid']);
	} 
	speedup_pictures();
	// Delete album
	$result = $db->sql_query("DELETE from {$CONFIG['TABLE_ALBUMS']} WHERE aid='$aid'",false, __FILE__, __LINE__);
	if ($db->sql_affectedrows() > 0)
		echo "<tr><td colspan=\"6\" class=\"tableb\">" . sprintf(ALB_DEL_SUCCESS, $album_data['title']) . "</td></tr>\n";
} 

/**
 * Album manager functions
 */
global $db;
function parse_select_option($value)
{
	if (!preg_match("/.+?no=(\d+),album_nm='(.+?)',album_sort=(\d+),action=(\d)/", $value, $matches))
		return false;

	return array('album_no' => (int)$matches[1],
		'album_nm' => htmlprepare($matches[2]),
		'album_sort' => (int)$matches[3],
		'action' => (int)$matches[4]
	);
} 

function parse_orig_sort_order($value)
{
	if (!preg_match("/(\d+)@(\d+)/", $value, $matches))
		return false;

	return array('aid' => (int)$matches[1],
		'pos' => (int)$matches[2],
		);
} 

function parse_list($value)
{
	return preg_split("/,/", $value, -1, PREG_SPLIT_NO_EMPTY);
} 

/**
 * Main code starts here
 */

$what = $_POST['what'] ?? $_GET['what'];
switch ($what) {
	
	// Album manager (don't necessarily delete something ;-)
	
	case 'albmgr':
		if (!(GALLERY_ADMIN_MODE || USER_ADMIN_MODE)) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);

		if (!GALLERY_ADMIN_MODE) {
			$restrict = "AND category = '" . (FIRST_USER_CAT + USER_ID) . "'";
		} else {
			$restrict = '';
		} 

		pageheader(ALB_MGR);
		starttable("100%", ALB_MGR, 6);

		$orig_sort_order = parse_list($_POST['sort_order']);
		foreach ($orig_sort_order as $album) {
			$op = parse_orig_sort_order($album);
			if ((is_countable($op) ? count ($op) : 0) == 2) {
				$db->sql_query("UPDATE $CONFIG[TABLE_ALBUMS] SET pos='{$op['pos']}' WHERE aid='{$op['aid']}' $restrict", false, __FILE__, __LINE__);
			} else {
				cpg_die (sprintf(CRITICAL_ERROR, ERR_INVALID_DATA, $_POST['sort_order']), __FILE__, __LINE__);
			} 
		} 

		$to_delete = parse_list($_POST['delete_album']);
		foreach ($to_delete as $album_id) {
			delete_album($album_id);
		} 

		if (isset($_POST['to'])) foreach ($_POST['to'] as $option_value) {
			$op = parse_select_option($option_value);
			switch ($op['action']) {
				case '0':
					break;
				case '1':
					if (GALLERY_ADMIN_MODE) {
						$category = intval($_POST['cat']);
					} else {
						$category = FIRST_USER_CAT + USER_ID;
					} 
					echo "<tr><td colspan=\"6\" class=\"tableb\">" . sprintf('CREATE_ALB', $op['album_nm']) . "</td></tr>\n";
					$album_nm = Fix_Quotes($op['album_nm']);
					$db->sql_query("INSERT INTO {$CONFIG['TABLE_ALBUMS']} (category, title, uploads, pos) VALUES ('$category', '".$album_nm."', 'NO',  '{$op['album_sort']}')", false, __FILE__, __LINE__);
					break;
				case '2':
					$album_nm = Fix_Quotes($op['album_nm']);
					echo "<tr><td colspan=\"6\" class=\"tableb\">" . sprintf(UPDATE_ALB, $op['album_no'], $op['album_nm'], $op['album_sort']) . "</td></tr>\n";
					$db->sql_query("UPDATE $CONFIG[TABLE_ALBUMS] SET title='".$album_nm."', pos='{$op['album_sort']}' WHERE aid='{$op['album_no']}' $restrict", false, __FILE__, __LINE__);
					break;
				default:
					cpg_die (CRITICAL_ERROR, $ERR_INVALID_DATA, __FILE__, __LINE__);
			} 
		} 
		if ($need_caption) output_caption();
		echo "<tr><td colspan=\"6\" class=\"tablef\" align=\"center\">\n";
		echo "<div class=\"admin_menu_thumb\"><a href=\"" . getlink("&amp;file=albmgr") . "\"  class=\"adm_menu\">".CONTINU."</a></div>\n";
		echo "</td></tr>";
		endtable();
		pagefooter();
		break;
	
	// Comment
	
	case 'comment':
		$msg_id = intval($_POST['msg_id']);
		  
			$result = $db->sql_query("SELECT pid FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id='$msg_id'", false, __FILE__, __LINE__);
			if (!$db->sql_numrows($result)) {
				cpg_die(_CRITICAL_ERROR, NON_EXIST_COMMENT, __FILE__, __LINE__);
			} else {
				$comment_data = $db->sql_fetchrow($result);
			} 
			$redirect = getlink("&file=displayimage&pid=".$comment_data['pid']);
			if(isset($_POST['cancel'])) {
				url_redirect($redirect);
			}
			if (!isset($_POST['confirm'])) {
			$msg = CONFIRM_DELETE_COM;
			cpg_delete_msg(getlink("&amp;file=delete"),$msg,'<input type="hidden" name="what" value="comment" /><input type="hidden" name="msg_id" value="'.$msg_id.'" />');		
		
			} else {  
			if (is_admin()) {
				$query = "DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id='$msg_id'";
			} elseif (is_user()) {
				$query = "DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id='$msg_id' AND author_id=".is_user();
			} else {
				$query = "DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id='$msg_id' AND author_md5_id='{$USER['ID']}' AND author_id='0'";
			} 
			$result = $db->sql_query($query, false, __FILE__, __LINE__);

			pageheader(INFO, $redirect);
			msg_box(INFO, COMMENT_DELETED, CONTINU, $redirect);
			pagefooter();
		}
		break;
	
	// Picture
	
	case 'picture':
		if (!(GALLERY_ADMIN_MODE || USER_ADMIN_MODE)) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);

		//$pid = (int)$_GET['id'];
		$pid = $_POST['id'] ?? $_GET['id'] ?? NULL;
		if (!is_numeric($pid)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
		if(isset($_POST['cancel'])) {
			$redirect = getlink("&file=displayimage&pid=".$pid);
			url_redirect($redirect);
		}
		if (!isset($_POST['confirm'])) {
			$msg = PIC_CONFIRM_DEL;
			cpg_delete_msg(getlink("&amp;file=delete"),$msg,'<input type="hidden" name="what" value="picture" /><input type="hidden" name="id" value="'.$pid.'" />');	   
		} else {
		
		pageheader(DEL_PIC);
		starttable("100%", DEL_PIC, 6);
		
		output_table_header();
		$aid = delete_picture($pid);
		speedup_pictures();
		output_caption();
		echo "<tr><td colspan=\"6\" class=\"tablef\" align=\"center\">\n";
		echo "<div class=\"admin_menu_thumb\"><a href=\"" . getlink("&amp;file=thumbnails&amp;album=$aid"). "\"  class=\"adm_menu\">".CONTINU."</a></div>\n";
		echo "</td></tr>\n";
		}
		endtable();
		pagefooter();
		break;
	
	// Album
	
	case 'album':
		if (!(GALLERY_ADMIN_MODE || USER_ADMIN_MODE)) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);
		$aid = $_POST['id'] ?? NULL;
		if (!is_numeric($aid)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
		$result = $db->sql_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = ".$aid, false, __FILE__, __LINE__);
		list($cat) = $db->sql_fetchrow($result);
		if(isset($_POST['cancel'])) {
			$redirect = GALLERY_ADMIN_MODE ? getlink('&amp;file=albmgr&amp;cat='.$cat) : getlink('&amp;cat='.$cat);
			//$redirect = getlink("&file=albmgr&album=".$aid);
			url_redirect($redirect);
		}
		if (!isset($_POST['confirm'])) {
			$msg = CONFIRM_DELETE1 . '<br />'.CONFIRM_DELETE2 . '<br />';
			cpg_delete_msg(getlink("&amp;file=delete"),$msg,'<input type="hidden" name="what" value="album" /><input type="hidden" name="id" value="'.$aid.'" />');			
		} else {
		
		$result = $db->sql_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = ".$aid, false, __FILE__, __LINE__);
		list($cat) = $db->sql_fetchrow($result);
		
		pageheader(DEL_ALB);
		starttable("100%", DEL_ALB, 6);
		
		delete_album($aid);
		if ($need_caption) output_caption();

		echo "<tr><td colspan=\"6\" class=\"tablef\" align=\"center\">\n";
		echo "<div class=\"admin_menu_thumb\"><a href=\"" .getlink("&amp;file=albmgr&amp;cat=$cat") ."\"  class=\"adm_menu\">".CONTINU."</a></div>\n";
		echo "</td></tr>";
		endtable();
		pagefooter();
		}
		break;
	
	// User
	
	case 'user':
		//$user_id = (int)$_GET['id'];
		$user_id = $_POST['id'] ?? cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
		if (!is_numeric($user_id)) cpg_die(_CRITICAL_ERROR, $ERR_UNKNOWN_USE, __FILE__, __LINE__);
		if (!is_admin()) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
		if ($CLASS['member']->demo){
			pageheader(PERM_DENIED);
			cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
			pagefooter();
		 }
		if(isset($_POST['cancel'])) {
			$redirect = getlink("&file=usermgr");
			url_redirect($redirect);
		}
		if (!isset($_POST['confirm'])) {
			$msg = DEL_USER . ' - ' . $user_id . '<br />'.USER_CONFIRM_DEL;
			cpg_delete_msg(getlink("&amp;file=delete"),$msg,'<input type="hidden" name="what" value="user" /><input type="hidden" name="id" value="'.$user_id.'" />');
			
		}
		
		$result = $db->sql_query("SELECT username FROM {$CONFIG['TABLE_USERS']} WHERE user_id = '$user_id'", false, __FILE__, __LINE__);
		if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, $ERR_UNKNOWN_USE, __FILE__, __LINE__);
		$user_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		pageheader(DEL_USER);
		starttable("100%", DEL_USER . ' - ' . $user_data['username'], 6); 
		// First delete the albums
		$result = $db->sql_query("SELECT aid FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = '" . (FIRST_USER_CAT + $user_id) . "'", false, __FILE__, __LINE__);
		while ($album = $db->sql_fetchrow($result)) {
			delete_album($album['aid']);
		} // while
		$db->sql_freeresult($result);

		if ($need_caption) output_caption(); 
		// Then anonymize comments posted by the user
		$db->sql_query("UPDATE {$CONFIG['TABLE_COMMENTS']} SET  author_id = '0' WHERE  author_id = '$user_id'", false, __FILE__, __LINE__);
		// Do the same for pictures uploaded in public albums
		$db->sql_query("UPDATE {$CONFIG['TABLE_PICTURES']} SET  owner_id = '0' WHERE  owner_id = '$user_id'", false, __FILE__, __LINE__);
		// Finally delete the user
		//$db->sql_query("DELETE FROM {$CONFIG['TABLE_USERS']} WHERE user_id = '$user_id'", false, __FILE__, __LINE__);
		//suspend user instead
		$db->sql_query("UPDATE {$CONFIG['TABLE_USERS']} SET user_level=0, susdel_reason='".PHOTOGALLERY."' WHERE user_id = '$user_id'", false, __FILE__, __LINE__);
	

		echo "<tr><td colspan=\"6\" class=\"tablef\" align=\"center\">\n";
		echo "<div class=\"admin_menu_thumb\"><a href=\"" . getlink("&amp;file=usermgr"). "\"  class=\"adm_menu\">".CONTINU."</a></div>\n";
		echo "</td></tr>";
		endtable();
		pagefooter();
		break;
   default:
		cpg_die(_ERROR, "$what command not found" , __FILE__, __LINE__);
		break;
} 
