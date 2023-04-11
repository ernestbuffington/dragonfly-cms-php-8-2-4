<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($op)) { die('Access Denied'); }

if (empty($_POST['send_file']) && !isset($_POST['cancel'])) {
	if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
	$module_name = ($op == 'forums') ? ucfirst($op) : $op;
	require "modules/{$module_name}/common.php";
}

$file = (isset($_GET['do']) ? $_GET['do'] : (isset($_POST['do']) ? $_POST['do'] : false));
if ($file && !preg_match('#^([a-zA-Z0-9_\-]+)$#', $file)) {
	cpg_error(sprintf(_ERROR_BAD_CHAR, strtolower(_BBFORUMS)), _SEC_ERROR);
}

if ($file) {
	require_once(__DIR__ . "/{$file}.php");
} else {
	$template->set_handle('body', 'Forums/admin/index');

	//
	// Get forum statistics
	//
	list($total_posts)  = $db->uFetchRow('SELECT SUM(forum_posts) FROM '.FORUMS_TABLE);
	list($total_topics) = $db->uFetchRow('SELECT SUM(forum_topics) FROM '.FORUMS_TABLE);

	$boarddays = ceil((time() - $board_config['board_startdate']) / 86400);

	$avatar_dir_size = 0;
	if ($avatar_dir = opendir($MAIN_CFG['avatar']['path'])) {
		while ($file = readdir($avatar_dir)) {
			if ($file != '.' && $file != '..' ) {
				$avatar_dir_size += filesize($MAIN_CFG['avatar']['path'] . '/' . $file);
			}
		}
		closedir($avatar_dir);
		$avatar_dir_size = $lang->filesizeToHuman($avatar_dir_size);
	} else {
		// Couldn't open Avatar dir.
		$avatar_dir_size = $lang['Not_available'];
	}

	$dbsize = 0;
	if ($result = $db->tablesStatus()) {
		foreach ($result as $table) {
//			if (in_array($table['Name'], $dbtables)) {
				$dbsize += $table['Data_length'] + $table['Index_length'];
//			}
		}
	}

	$template->assign_vars(array(
		'NUMBER_OF_POSTS'  => $total_posts,
		'NUMBER_OF_TOPICS' => $total_topics,
		'START_DATE'       => $lang->date($board_config['default_dateformat'], $board_config['board_startdate']),
		'POSTS_PER_DAY'    => sprintf('%.2f', min($total_posts,  $total_posts  / $boarddays)),
		'TOPICS_PER_DAY'   => sprintf('%.2f', min($total_topics, $total_topics / $boarddays)),
		'AVATAR_DIR_SIZE'  => $avatar_dir_size,
		'DB_SIZE'          => $dbsize ? $lang->filesizeToHuman($dbsize) : $lang['Not_available']
	));
}

if (empty($_POST['send_file']) && !isset($_POST['cancel'])) {
	$template->display('body');
}
