<?php
/***************************************************************************
 *				   (admin) index.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 $Id: index.php,v 9.5 2005/12/30 14:02:31 djmaze Exp $
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
/*	 $Id: index.php,v 9.5 2005/12/30 14:02:31 djmaze Exp $		   */
/*										*/
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
// ---------------
// Begin functions
//
function inarray($needle, $haystack) {
	for ($i = 0; $i < sizeof($haystack); $i++ ) {
	if ($haystack[$i] == $needle) { return true; }
	}
	return false;
}
//
// End functions
// -------------

	$template->set_filenames(array('body' => 'forums/admin/index_body.html'));

	$template->assign_vars(array(
		"L_WELCOME" => $lang['Welcome_phpBB'],
		"L_ADMIN_INTRO" => $lang['Admin_intro'],
		"L_FORUM_STATS" => $lang['Forum_stats'],
		"L_STATISTIC" => $lang['Statistic'],
		"L_VALUE" => $lang['Value'],
		"L_NUMBER_POSTS" => $lang['Number_posts'],
		"L_POSTS_PER_DAY" => $lang['Posts_per_day'],
		"L_NUMBER_TOPICS" => $lang['Number_topics'],
		"L_TOPICS_PER_DAY" => $lang['Topics_per_day'],
		"L_NUMBER_USERS" => $lang['Number_users'],
		"L_USERS_PER_DAY" => $lang['Users_per_day'],
		"L_BOARD_STARTED" => $lang['Board_started'],
		"L_AVATAR_DIR_SIZE" => $lang['Avatar_dir_size'],
		"L_DB_SIZE" => $lang['Database_size'],
		"L_FORUM_LOCATION" => $lang['Forum_Location'],
		"L_GZIP_COMPRESSION" => $lang['Gzip_compression'])
	);

	//
	// Get forum statistics
	//
	list($total_posts) = $db->sql_ufetchrow("SELECT SUM(forum_posts) FROM ".FORUMS_TABLE, SQL_NUM);
	list($total_topics) = $db->sql_ufetchrow("SELECT SUM(forum_topics) FROM ".FORUMS_TABLE, SQL_NUM);

	$start_date = create_date($board_config['default_dateformat'], $board_config['board_startdate']);

	$boarddays = ( gmtime() - $board_config['board_startdate'] ) / 86400;

	$posts_per_day = sprintf("%.2f", $total_posts / $boarddays);
	$topics_per_day = sprintf("%.2f", $total_topics / $boarddays);

	$avatar_dir_size = 0;

	if ($avatar_dir = opendir($MAIN_CFG['avatar']['path']))
	{
		while( $file = readdir($avatar_dir) ) {
			if( $file != "." && $file != ".." ) {
				$avatar_dir_size += filesize($MAIN_CFG['avatar']['path'] . "/" . $file);
			}
		}
		closedir($avatar_dir);

		//
		// This bit of code translates the avatar directory size into human readable format
		// Borrowed the code from the PHP.net annoted manual, origanally written by:
		// Jesse (jesse@jess.on.ca)
		//
		if($avatar_dir_size >= 1048576) {
			$avatar_dir_size = round($avatar_dir_size / 1048576 * 100) / 100 . " MB";
		} else if($avatar_dir_size >= 1024) {
			$avatar_dir_size = round($avatar_dir_size / 1024 * 100) / 100 . " KB";
		} else {
			$avatar_dir_size = $avatar_dir_size . " Bytes";
		}

	} else {
		// Couldn't open Avatar dir.
		$avatar_dir_size = $lang['Not_available'];
	}

	if($posts_per_day > $total_posts) {
		$posts_per_day = $total_posts;
	}

	if($topics_per_day > $total_topics) {
		$topics_per_day = $total_topics;
	}

	//
	// DB size ... MySQL only
	//
	// This code is heavily influenced by a similar routine
	// in phpMyAdmin 2.2.0
	//
	if( preg_match("/^mysql/", SQL_LAYER) ) {
		if($result = $db->sql_query("SELECT VERSION() AS mysql_version")) {
			$row = $db->sql_fetchrow($result);
			$version = $row['mysql_version'];

			if( preg_match("/^(3\.23|4\.)/", $version) ) {
				$db_name = ( preg_match("/^(3\.23\.[6-9])|(3\.23\.[1-9][1-9])|(4\.)/", $version) ) ? "`$dbname`" : $dbname;

				if($result = $db->sql_query("SHOW TABLE STATUS FROM " . $db_name)) {
					$tabledata_ary = $db->sql_fetchrowset($result);
					$dbsize = 0;
					for($i = 0; $i < (is_countable($tabledata_ary) ? count($tabledata_ary) : 0); $i++) {
						if( isset($tabledata_ary[$i]['Type']) && $tabledata_ary[$i]['Type'] != "MRG_MyISAM" ) {
							if( $prefix != "" ) {
								if( strstr($tabledata_ary[$i]['Name'], (string) $prefix) ) {
									$dbsize += $tabledata_ary[$i]['Data_length'] + $tabledata_ary[$i]['Index_length'];
								}
							} else {
								$dbsize += $tabledata_ary[$i]['Data_length'] + $tabledata_ary[$i]['Index_length'];
							}
						}
					}
				} // Else we couldn't get the table status.
			} else {
				$dbsize = $lang['Not_available'];
			}
		} else {
			$dbsize = $lang['Not_available'];
		}
	} else if( preg_match("/^mssql/", SQL_LAYER) ) {
		if( $result = $db->sql_query("SELECT ((SUM(size) * 8.0) * 1024.0) as dbsize FROM sysfiles") ) {
			$dbsize = ( $row = $db->sql_fetchrow($result) ) ? intval($row['dbsize']) : $lang['Not_available'];
		} else {
			$dbsize = $lang['Not_available'];
		}
	} else {
		$dbsize = $lang['Not_available'];
	}

	if ( is_integer($dbsize) ) {
		if( $dbsize >= 1048576 ) {
			$dbsize = sprintf("%.2f MB", ( $dbsize / 1048576 ));
		} else if( $dbsize >= 1024 ) {
			$dbsize = sprintf("%.2f KB", ( $dbsize / 1024 ));
		} else {
			$dbsize = sprintf("%.2f Bytes", $dbsize);
		}
	}

	$template->assign_vars(array(
		"NUMBER_OF_POSTS" => $total_posts,
		"NUMBER_OF_TOPICS" => $total_topics,
		"START_DATE" => $start_date,
		"POSTS_PER_DAY" => $posts_per_day,
		"TOPICS_PER_DAY" => $topics_per_day,
		"AVATAR_DIR_SIZE" => $avatar_dir_size,
		"DB_SIZE" => $dbsize,
		"GZIP_COMPRESSION" => _YES)
	);
	//
	// End forum statistics
	//
