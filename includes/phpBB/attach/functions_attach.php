<?php
/*********************************************
  CPG-NUKE: Advanced Content Management System
  ********************************************
  Copyright (c) 2004 by CPG-Nuke Dev Team
  http://www.cpgnuke.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/phpBB/attach/functions_attach.php,v $
  $Revision: 9.6 $
  $Author: nanocaiordo $
  $Date: 2007/09/13 06:21:38 $

***********************************************************************/
if (!defined('CPG_NUKE')) { die('You can\'t access this file directly...'); }

//
// A simple dectobase64 function
//
function base64_pack($number) 
{ 
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+-';
	$base = strlen($chars);
	if ($number > 4096) {
		return;
	} else if ($number < $base) {
		return $chars[$number];
	}
	$hexval = '';
	while ($number > 0) {
		$remainder = $number%$base;
		if ($remainder < $base) {
			$hexval = $chars[$remainder].$hexval;
		}
		$number = floor($number/$base);
	}
	return $hexval;
}

//
// base64todec function
//
function base64_unpack($string)
{
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+-';
	$base = strlen($chars);
	$length = strlen($string);
	$number = 0; 
	for($i = 1; $i <= $length; $i++) {
		$pos = $length - $i;
		$operand = strpos($chars, substr($string,$pos,1));
		$exponent = pow($base, $i-1);
		$decValue = $operand * $exponent;
		$number += $decValue;
	}
	return $number;
}

//
// Per Forum based Extension Group Permissions (Encode Number) -> Theoretically up to 158 Forums saveable. :)
// We are using a base of 64, but splitting it to one-char and two-char numbers. :)
//
function auth_pack($auth_array)
{
	$one_char_encoding = '#';
	$two_char_encoding = '.';
	$one_char = FALSE;
	$two_char = FALSE;
	$auth_cache = '';
	for ($i = 0; $i < (is_countable($auth_array) ? count($auth_array) : 0); $i++) {
		$val = base64_pack(intval($auth_array[$i]));
		if ((strlen($val) == 1) && ($one_char == FALSE)) {
			$auth_cache .= $one_char_encoding;
			$one_char = TRUE;
		} else if ((strlen($val) == 2) && ($two_char == FALSE)) {
			$auth_cache .= $two_char_encoding;
			$two_char = TRUE;
		}
		$auth_cache .= $val;
	}
	return $auth_cache;
}

// Reverse the auth_pack process
function auth_unpack($auth_cache)
{
	$one_char_encoding = '#';
	$two_char_encoding = '.';
	$auth = array();
	$auth_len = 1;
	for ($pos = 0; $pos < strlen($auth_cache); $pos+=$auth_len) {
		$forum_auth = substr($auth_cache, $pos, 1);
		if ($forum_auth == $one_char_encoding) {
			$auth_len = 1;
			continue;
		} else if ($forum_auth == $two_char_encoding) {
			$auth_len = 2;
			$pos--;
			continue;
		}
		$forum_auth = substr($auth_cache, $pos, $auth_len);
		$forum_id = base64_unpack($forum_auth);
		$auth[] = intval($forum_id);
	}
	return $auth;
}

// Used for determining if Forum ID is authed, please use this Function on all Posting Screens
function is_forum_authed($auth_cache, $check_forum_id)
{
	$one_char_encoding = '#';
	$two_char_encoding = '.';
	if (trim($auth_cache) == '') {
		return (TRUE);
	}
	$auth = array();
	$auth_len = 1;
	for ($pos = 0; $pos < strlen($auth_cache); $pos+=$auth_len) {
		$forum_auth = substr($auth_cache, $pos, 1);
		if ($forum_auth == $one_char_encoding) {
			$auth_len = 1;
			continue;
		} else if ($forum_auth == $two_char_encoding) {
			$auth_len = 2;
			$pos--;
			continue;
		}
		$forum_auth = substr($auth_cache, $pos, $auth_len);
		$forum_id = base64_unpack($forum_auth);
		if (intval($forum_id) == intval($check_forum_id)) {
			return (TRUE);
		}
	}
	return (FALSE);
}

// Delete an Attachment
function unlink_attach($filename, $mode = FALSE)
{
	$filesys = null;
 global $upload_dir, $attach_config, $lang;
	if (!intval($attach_config['allow_ftp_upload'])) {
		if ($mode == MODE_THUMBNAIL) {
			$filename = $upload_dir.'/'.THUMB_DIR.'/t_'.$filename;
		} else {
			$filename = $upload_dir.'/'.$filename;
		}
		$deleted = unlink($filename);
		if (file_exists(amod_realpath($filename)) ) {
			$deleted = system("del $filesys");
			if (file_exists(amod_realpath($filename))) {
				$deleted = chmod($filename, 0666);
				$deleted = unlink($filename);
				$deleted = system("del $filesys");
			}
		}
	} else {
		$ftp_path = $attach_config['ftp_path'];
		if ($mode == MODE_THUMBNAIL) {
			$ftp_path .= '/'.THUMB_DIR;
			$filename = 't_'.$filename;
		}
		include_once('includes/classes/cpg_ftp.php');
		$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $ftp_path, $attach_config['ftp_pasv_mode']);
		if (!$ftp->del($filename) && ATTACH_DEBUG) {
			$ftp->close();
			message_die(GENERAL_ERROR, sprintf($lang['Ftp_error_delete'], $ftp_path));
		}
		$ftp->close();
		$deleted = true;
	}
	return $deleted;
}

// FTP File to Location
function ftp_file($source_file, $dest_file, $mimetype, $disable_error_mode = FALSE)
{
	global $attach_config, $lang, $error, $error_msg;
	include_once('includes/classes/cpg_ftp.php');
	$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $attach_config['ftp_path'], $attach_config['ftp_pasv_mode']);
	$res = $ftp->up($source_file, $dest_file, $mimetype);
	if (!$res && !$disable_error_mode) {
		$error = TRUE;
		if(!empty($error_msg)) { $error_msg .= '<br />'; }
		$error_msg = sprintf($lang['Ftp_error_upload'], $attach_config['ftp_path']).'<br />';
	}
	$ftp->close();
	return $res;
}

// Check if Attachment exist
function attachment_exists($filename)
{
	global $upload_dir, $attach_config;
	if (!intval($attach_config['allow_ftp_upload'])) {
		$found = file_exists(amod_realpath($upload_dir.'/'.$filename));
	} else {
		include_once('includes/classes/cpg_ftp.php');
		$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $attach_config['ftp_path'], $attach_config['ftp_pasv_mode']);
		$found = $ftp->exists($filename);
		$ftp->close();
	}
	return $found;
}

// Check if Thumbnail exist
function thumbnail_exists($filename)
{
	global $upload_dir, $attach_config;
	if (!intval($attach_config['allow_ftp_upload'])) {
		$found = file_exists(amod_realpath($upload_dir.'/'.THUMB_DIR.'/t_'.$filename));
	} else {
		include_once('includes/classes/cpg_ftp.php');
		$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $attach_config['ftp_path'].'/'.THUMB_DIR, $attach_config['ftp_pasv_mode']);
		$found = $ftp->exists($filename);
		$ftp->close();
	}
	return $found;
}

// Determine if an Attachment exist in a post/pm
function attachment_exists_db($post_id, $page = -1)
{
	global $db;
	$sql_id = (($page == PAGE_PRIVMSGS) ? 'privmsgs_id' : 'post_id');
	$sql = 'SELECT attach_id FROM '.ATTACHMENTS_TABLE.' WHERE '.$sql_id.' = '.$post_id.' LIMIT 1';
	$result = $db->sql_uquery($sql);
	return ($db->sql_numrows($result) > 0);
}

// get all attachments from a post (could be an post array too)
function get_attachments_from_post($post_id_array)
{
	global $db, $attach_config;
	if (is_array($post_id_array)) {
		$post_id_array = implode(', ', $post_id_array);
	} else {
		$post_id_array = intval($post_id_array);
	}
	if (empty($post_id_array)) { return array(); }
	$display_order = ( intval($attach_config['display_order']) == 0 ) ? 'DESC' : 'ASC';
	$sql = "SELECT a.post_id, d.* FROM ".ATTACHMENTS_TABLE." a
	LEFT JOIN ".ATTACHMENTS_DESC_TABLE." d ON (d.attach_id = a.attach_id)
	WHERE a.post_id IN (".$post_id_array.")
	ORDER BY d.filetime ".$display_order;
	return $db->sql_ufetchrowset($sql, SQL_ASSOC);
}

//
// get all attachments from a pm
//
function get_attachments_from_pm($privmsgs_id_array)
{
	global $db, $attach_config;
	if (!is_array($privmsgs_id_array)) {
		if (empty($privmsgs_id_array)) {
			return array();
		}
		$privmsgs_id = intval($privmsgs_id_array);
		$privmsgs_id_array = array();
		$privmsgs_id_array[] = $privmsgs_id;
	}
	$privmsgs_id_array = implode(', ', $privmsgs_id_array);
	if ($privmsgs_id_array == '') {
		return array();
	}

	$display_order = ( intval($attach_config['display_order']) == 0 ) ? 'DESC' : 'ASC';
	
	$sql = "SELECT a.privmsgs_id, d.* FROM ".ATTACHMENTS_TABLE." a, ".ATTACHMENTS_DESC_TABLE." d
	WHERE ( a.privmsgs_id IN (".$privmsgs_id_array.")) AND (a.attach_id = d.attach_id)
	ORDER BY d.filetime ".$display_order;
	$result = $db->sql_uquery($sql);
	if ($db->sql_numrows($result) == 0) {
		return array();
	}
	return $db->sql_fetchrowset($result);
}

//
// Count Filesize of Attachments in Database based on the attachment id
//
function get_total_attach_filesize($attach_ids)
{
	global $db;
	$result = $db->sql_query('SELECT filesize FROM '.ATTACHMENTS_DESC_TABLE.' WHERE attach_id IN ('.$attach_ids.')');
	$num_filesizes = $db->sql_numrows($result);
	$filesizes = $db->sql_fetchrowset($result);
	$total_filesize = 0;
	if ($num_filesizes > 0) {
		for ($i = 0; $i < $num_filesizes; $i++) {
			$total_filesize += intval($filesizes[$i]['filesize']);
		}
	}
	return ($total_filesize);
}

//
// Count Filesize for Attachments in Users PM Boxes (Do not count the SENT Box)
//
function get_total_attach_pm_filesize($direction, $user_id)
{
	global $db;

	if (($direction != 'from_user') && ($direction != 'to_user')) {
		return 0;
	} else {
		$user_sql = ($direction == 'from_user') ? '(a.user_id_1 = '.intval($user_id).')' : '(a.user_id_2 = '.intval($user_id).')';
	}

	$sql = "SELECT a.attach_id FROM ".ATTACHMENTS_TABLE." a, ".PRIVMSGS_TABLE." p
	WHERE ".$user_sql." AND (a.privmsgs_id != 0) AND (a.privmsgs_id = p.privmsgs_id) AND (p.privmsgs_type != ".PRIVMSGS_SENT_MAIL.")";

	$result = $db->sql_query($sql);

	$pm_filesize_total = 0;
	$num_rows = $db->sql_numrows($result);
	$rows = $db->sql_fetchrowset($result);
	$attach_id = array();

	if ($num_rows == 0) {
		return $pm_filesize_total;
	}
	
	for ($i = 0; $i < $num_rows; $i++) {
		$attach_id[] = $rows[$i]['attach_id'];
	}

	$attach_id = implode(', ', $attach_id);
				
	return get_total_attach_filesize($attach_id);
}

//
// Get allowed Extensions and their respective Values
//
function get_extension_informations()
{
	global $db;
	// Don't count on forbidden extensions table, because it is not allowed to allow forbidden extensions at all
	$sql = "SELECT e.extension, g.cat_id, g.download_mode, g.upload_icon
	FROM ".EXTENSIONS_TABLE." e, ".EXTENSION_GROUPS_TABLE." g
	WHERE (e.group_id = g.group_id) AND (g.allow_group = 1)";
	$extensions = $db->sql_fetchrowset($db->sql_uquery($sql));
	return $extensions;
}

//
// Sync Topic
//
function attachment_sync_topic($topic_id)
{
	global $db;
	$result = $db->sql_query('SELECT post_id FROM '.POSTS_TABLE.' WHERE topic_id = '.$topic_id.' GROUP BY post_id');
	$num_posts = $db->sql_numrows($result);
	$post_list = $db->sql_fetchrowset($result);

	if ($num_posts == 0) {
		return;
	}
	
	$post_ids = array();

	for ($i = 0; $i < $num_posts; $i++) {
		$post_ids[] = intval($post_list[$i]['post_id']);
	}

	$post_id_sql = implode(', ', $post_ids);
	
	if ($post_id_sql == '') {
		return;
	}
	
	$result = $db->sql_query('SELECT attach_id FROM '.ATTACHMENTS_TABLE.' WHERE post_id IN ('.$post_id_sql.') LIMIT 1');
	$set_id = ( $db->sql_numrows($result) < 1) ? 0 : 1;
	$db->sql_query('UPDATE '.TOPICS_TABLE.' SET topic_attachment = '.$set_id.' WHERE topic_id = '.$topic_id);

	for ($i = 0; $i < count($post_ids); $i++) {
		$db->sql_query('SELECT attach_id FROM '.ATTACHMENTS_TABLE.' WHERE post_id = '.$post_ids[$i].' LIMIT 1');
		$set_id = ( $db->sql_numrows($result) < 1) ? 0 : 1;
		$db->sql_query('UPDATE '.POSTS_TABLE.' SET post_attachment = '.$set_id.' WHERE post_id = '.$post_ids[$i]);
	}
}

function get_extension($filename) {
	$extension = substr(strrchr($filename, '.'), 1);
	return (!$extension ? '' : strtolower($extension));
}

// -> from phpBB 2.0.4
// This function is for compatibility with PHP 4.x's realpath()
// function.  In later versions of PHP, it needs to be called
// to do checks with some functions.  Older versions of PHP don't
// seem to need this, so we'll just return the original value.
// dougk_ff7 <October 5, 2002>
function amod_realpath($path)
{
	global $phpbb_root_path;

	return (!function_exists('realpath') || !realpath($phpbb_root_path.'includes/functions.php')) ? $path : realpath($path);
}