<?php
/***************************************************************************
 *							  functions_admin.php
 *							  -------------------
 *	 begin				  : Sunday, Mar 31, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: functions_admin.php,v 9.3 2005/12/23 15:22:02 djmaze Exp $
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
// All Attachment Functions only needed in Admin
//

//
// Set/Change Quotas
//
function process_quota_settings($mode, $id, $quota_type, $quota_limit_id = -1)
{
	global $db;

	if ($mode == 'user')
	{
		if ($quota_limit_id == -1) {
			$sql = "DELETE FROM " . QUOTA_TABLE . " WHERE user_id = " . $id . " AND quota_type = " . $quota_type;
		} else {
			// Check if user is already entered
			$result = $db->sql_query("SELECT user_id FROM " . QUOTA_TABLE . " WHERE user_id = " . $id . " AND quota_type = " . $quota_type);
			if ($db->sql_numrows($result) == 0) {
				$sql = "INSERT INTO " . QUOTA_TABLE . " (user_id, group_id, quota_type, quota_limit_id) 
				VALUES (" . $id . ", 0, " . $quota_type . ", " . $quota_limit_id . ")";
			} else {
				$sql = "UPDATE " . QUOTA_TABLE . " SET quota_limit_id = " . $quota_limit_id . " WHERE user_id = " . $id . " AND quota_type = " . $quota_type;
			}
		}
		$db->sql_query($sql);
	}
	else if ($mode == 'group')
	{
		if ($quota_limit_id == -1) {
			$db->sql_query("DELETE FROM " . QUOTA_TABLE . " WHERE group_id = " . $id . " AND quota_type = " . $quota_type);
		} else {
			// Check if user is already entered
			$result = $db->sql_query("SELECT group_id FROM " . QUOTA_TABLE . " WHERE group_id = " . $id . " AND quota_type = " . $quota_type);
			if ($db->sql_numrows($result) == 0) {
				$sql = "INSERT INTO " . QUOTA_TABLE . " (user_id, group_id, quota_type, quota_limit_id) 
				VALUES (0, " . $id . ", " . $quota_type . ", " . $quota_limit_id . ")";
			} else {
				$sql = "UPDATE " . QUOTA_TABLE . " SET quota_limit_id = " . $quota_limit_id . " WHERE group_id = " . $id . " AND quota_type = " . $quota_type;
			}
			$db->sql_query($sql);
		}
	}
}

//
// sort multi-dimensional Array
//
function sort_multi_array ($sort_array, $key, $sort_order, $pre_string_sort = -1) 
{
	$volume = [];
 foreach ($sort_array as $k => $c) {
		$volume[$k] = $c[$key];
	}
	if ($sort_order == 'DESC') {
		array_multisort($volume, SORT_DESC, $sort_array);
	} else {
		array_multisort($volume, SORT_ASC, $sort_array);
	}
}

//
// See if a post or pm really exist
//
function entry_exists($attach_id)
{
	global $db;
	if (empty($attach_id)) { return FALSE; }
	$exists = FALSE;
	$sql = "SELECT post_id, privmsgs_id FROM " . ATTACHMENTS_TABLE . "
	WHERE attach_id = " . $attach_id;
	$result = $db->sql_query($sql);
	$ids = $db->sql_fetchrowset($result);
	$num_ids = $db->sql_numrows($result);
	for ($i = 0; $i < $num_ids; $i++) {
		if (intval($ids[$i]['post_id']) != 0) {
			$sql = 'SELECT post_id FROM ' . POSTS_TABLE . '
			WHERE post_id = ' . intval($ids[$i]['post_id']);
		} else if (intval($ids[$i]['privmsgs_id']) != 0) {
			$sql = 'SELECT privmsgs_id FROM ' . PRIVMSGS_TABLE . '
			WHERE privmsgs_id = ' . intval($ids[$i]['privmsgs_id']);
		}
		$db->sql_query($sql);
		if ($db->sql_numrows($result) > 0) {
			$exists = TRUE;
			break;
		}
	}
	return $exists;
}

//
// Collect all Attachments in Filesystem
//
function collect_attachments()
{
	global $upload_dir, $attach_config;
	$file_attachments = array();
	if (!intval($attach_config['allow_ftp_upload'])) {
		if ($dir = opendir($upload_dir)) {
			while ($file = readdir($dir)) {
				if (($file != 'index.php') && ($file != '.htaccess') && (!is_dir($upload_dir . '/' . $file)) && (!is_link($upload_dir . '/' . $file)) ) {
					$file_attachments[] = trim($file);
				}
			}
			closedir($dir);
		} else {
			message_die(GENERAL_ERROR, 'Is Safe Mode Restriction in effect on '.$upload_dir.'? The Attachment Mod seems to be unable to collect the Attachments within the upload Directory. Try to use FTP Upload to circumvent this error.');
		}
	}
	else {
		include_once('includes/classes/cpg_ftp.php');
		$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $attach_config['ftp_path'], $attach_config['ftp_pasv_mode']);
		$file_listing = $ftp->dirlist();
		$ftp->close();
		if (!$file_listing) {
			message_die(GENERAL_ERROR, 'Unable to get Raw File Listing. Please be sure the LIST command is enabled at your FTP Server.');
		}
		for ($i = 0; $i < (is_countable($file_listing) ? count($file_listing) : 0); $i++) {
			if (!$file_listing[0] && $file_listing[4] != 'index.php' && $file_listing[4] != '.htaccess') {
				$file_attachments[] = $file_listing[4];
			}
		}
	}
	return $file_attachments;
}

//
// Returns the filesize of the upload directory in human readable format
//
function get_formatted_dirsize()
{
	global $attach_config, $upload_dir, $lang;

	$upload_dir_size = 0;

	if (!intval($attach_config['allow_ftp_upload']))
	{
	
		if ($dirname = opendir($upload_dir))
		{
			while( $file = readdir($dirname) )
			{
				if( ($file != 'index.php') && ($file != '.htaccess') && (!is_dir($upload_dir . '/' . $file)) && (!is_link($upload_dir . '/' . $file)) )
				{
					$upload_dir_size += filesize($upload_dir . '/' . $file);
				}
			}
			closedir($dirname);
		}
		else
		{
			$upload_dir_size = $lang['Not_available'];
			return ($upload_dir_size);
		}
	}
	else
	{
		include_once('includes/classes/cpg_ftp.php');
		$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $attach_config['ftp_path'], $attach_config['ftp_pasv_mode']);
		$file_listing = $ftp->dirlist();
		$ftp->close();
		if (!$file_listing) {
			return $lang['Not_available'];
		}
		for ($i = 0; $i < (is_countable($file_listing) ? count($file_listing) : 0); $i++) {
			if (!$file_listing[1] && $file_listing[4] != 'index.php' && $file_listing[4] != '.htaccess') {
				$upload_dir_size += $file_listing[1];
			}
		}
	}
	return filesize_to_human($upload_dir_size);
}

//
// Build SQL-Statement for the search feature
//
function search_attachments($order_by, &$total_rows)
{
	$search_author = null;
 $search_keyword_fname = null;
 $search_keyword_comment = null;
 $search_count_smaller = null;
 $search_count_greater = null;
 $search_size_smaller = null;
 $search_size_greater = null;
 $search_days_greater = null;
 global $db, $_POST, $_GET, $lang;
	
	$where_sql = array();

	//
	// Get submitted Vars
	//
	$search_vars = array('search_keyword_fname', 'search_keyword_comment', 'search_author', 'search_size_smaller', 'search_size_greater', 'search_count_smaller', 'search_count_greater', 'search_days_greater', 'search_forum', 'search_cat');
	
	for ($i = 0; $i < count($search_vars); $i++)
	{
		if( isset($_POST[$search_vars[$i]]) || isset($_GET[$search_vars[$i]]) )
		{
			${$search_vars}[$i] = $_POST[$search_vars[$i]] ?? $_GET[$search_vars[$i]];
		}
		else
		{
			${$search_vars}[$i] = '';
		}
	}

	//
	// Author name search 
	//
	if ($search_author != '') {
		$search_author = str_replace('*', '%', trim(Fix_Quotes($search_author)));
		//
		// We need the post_id's, because we want to query the Attachment Table
		//
		$result = $db->sql_query('SELECT user_id FROM ' . USERS_TABLE . ' WHERE username LIKE \'' . $search_author . '\'');
		$matching_userids = '';
		if ($row = $db->sql_fetchrow($result)) {
			do {
				$matching_userids .= ( ( $matching_userids != '' ) ? ', ' : '' ) . $row['user_id'];
			}
			while ($row = $db->sql_fetchrow($result));
		} else {
			message_die(GENERAL_MESSAGE, $lang['No_attach_search_match']);
		}
		$where_sql[] = ' (t.user_id_1 IN (' . $matching_userids . ')) ';
	}

	//
	// Search Keyword
	//
	if ( $search_keyword_fname != '' )
	{
		$match_word = str_replace('*', '%', $search_keyword_fname);
		$where_sql[] = ' (a.real_filename LIKE \'' . $match_word . '\') ';
	}

	if ( $search_keyword_comment != '' )
	{
		$match_word = str_replace('*', '%', $search_keyword_comment);
		$where_sql[] = ' (a.comment LIKE \'' . $match_word . '\') ';
	}

	//
	// Search Download Count
	//
	if ( $search_count_smaller != '' || $search_count_greater != '' )
	{
		if ($search_count_smaller != '')
		{
			$where_sql[] = ' (a.download_count < ' . $search_count_smaller . ') ';
		}
		else if ($search_count_greater != '')
		{
			$where_sql[] = ' (a.download_count > ' . $search_count_greater . ') ';
		}
	}

	//
	// Search Filesize
	//
	if ( $search_size_smaller != '' || $search_size_greater != '' )
	{
		if ($search_size_smaller != '')
		{
			$where_sql[] = ' (a.filesize < ' . $search_size_smaller . ') ';
		}
		else if ($search_size_greater != '')
		{
			$where_sql[] = ' (a.filesize > ' . $search_size_greater . ') ';
		}
	}

	//
	// Search Attachment Time
	//
	if ($search_days_greater != '') {
		$where_sql[] = ' (a.filetime < ' . ( gmtime() - ( $search_days_greater * 86400 ) ) . ') ';
	}

	$sql = 'SELECT a.*, t.post_id, p.post_time, p.topic_id
	FROM ' . ATTACHMENTS_TABLE . ' t, ' . ATTACHMENTS_DESC_TABLE . ' a, ' . POSTS_TABLE . ' p WHERE ';
	
	if (count($where_sql) > 0) {
		$sql .= implode('AND', $where_sql) . ' AND ';
	}
	$sql .= '(t.post_id = p.post_id) AND (a.attach_id = t.attach_id) ';
	$total_rows_sql = $sql;
	$sql .= $order_by; 
	$result = $db->sql_query($sql);
	$attachments = $db->sql_fetchrowset($result);
	$num_attach = $db->sql_numrows($result);
	if ($num_attach == 0) {
		message_die(GENERAL_MESSAGE, $lang['No_attach_search_match']);
	}
	$result = $db->sql_query($total_rows_sql);
	$total_rows = $db->sql_numrows($result);
	return ($attachments);
}

//
// perform LIMIT statement on arrays
//
function limit_array($array, $start, $pagelimit)
{
	//
	// array from start - start+pagelimit
	//
	$limit = ( (is_countable($array) ? count($array) : 0) < $start + $pagelimit ) ? count($array) : $start + $pagelimit;
	$limit_array = array();
	for ($i = $start; $i < $limit; $i++) {
		$limit_array[] = $array[$i];
	}
	return $limit_array;
}