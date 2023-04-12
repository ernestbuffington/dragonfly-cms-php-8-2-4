<?php
/***************************************************************************
*				 common.php
*				 -------------------
*	begin		 : Saturday, Feb 23, 2001
*	copyright		 : (C) 2001 The phpBB Group
*	email		 : support@phpbb.com
*
*	$Id: common.php,v 9.16 2007/12/12 12:54:23 nanocaiordo Exp $
*
*
***************************************************************************/

/***************************************************************************
*
*	This program is free software; you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation; either version 2 of the License, or
*	(at your option) any later version.
*
***************************************************************************/
if (!defined('IN_PHPBB')) { exit; }

$this_base = basename(dirname(__FILE__));
$this_prefix = $prefix.'_'.(($this_base == 'Forums') ? 'bb' : strtolower($this_base).'_');
// Table names
define('AUTH_ACCESS_TABLE', $this_prefix.'auth_access');
define('CATEGORIES_TABLE', $this_prefix.'categories');
define('CONFIG_TABLE', $prefix.'_bbconfig');
define('DISALLOW_TABLE', $prefix.'_bbdisallow');
define('FORUMS_TABLE', $this_prefix.'forums');
define('GROUPS_TABLE', $prefix.'_bbgroups');
define('POSTS_TABLE', $this_prefix.'posts');
define('POSTS_TEXT_TABLE', $this_prefix.'posts_text');
define('PRIVMSGS_TABLE', $prefix.'_bbprivmsgs');
define('PRIVMSGS_TEXT_TABLE', $prefix.'_bbprivmsgs_text');
define('PRIVMSGS_IGNORE_TABLE', $prefix.'_bbprivmsgs_ignore');
define('PRUNE_TABLE', $this_prefix.'forum_prune');
define('RANKS_TABLE', $prefix.'_bbranks');
define('SEARCH_WORD_TABLE', $this_prefix.'search_wordlist');
define('SEARCH_MATCH_TABLE', $this_prefix.'search_wordmatch');
define('SMILIES_TABLE', $prefix.'_bbsmilies');
define('THEMES_TABLE', $prefix.'_bbthemes');
define('THEMES_NAME_TABLE', $prefix.'_bbthemes_name');
define('TOPICS_TABLE', $this_prefix.'topics');
define('TOPICS_WATCH_TABLE', $this_prefix.'topics_watch');
define('USER_GROUP_TABLE', $prefix.'_bbuser_group');
define('USERS_TABLE', $user_prefix.'_users');
define('WORDS_TABLE', $prefix.'_bbwords');
define('VOTE_DESC_TABLE', $this_prefix.'vote_desc');
define('VOTE_RESULTS_TABLE', $this_prefix.'vote_results');
define('VOTE_USERS_TABLE', $this_prefix.'vote_voters');
// attach mod
define('ATTACH_CONFIG_TABLE', $prefix.'_bbattachments_config');
define('EXTENSION_GROUPS_TABLE', $prefix.'_bbextension_groups');
define('EXTENSIONS_TABLE', $prefix.'_bbextensions');
define('FORBIDDEN_EXTENSIONS_TABLE', $prefix.'_bbforbidden_extensions');
define('ATTACHMENTS_DESC_TABLE', $this_prefix.'attachments_desc');
define('ATTACHMENTS_TABLE', $this_prefix.'attachments');
define('QUOTA_TABLE', $prefix.'_bbattach_quota');
define('QUOTA_LIMITS_TABLE', $prefix.'_bbquota_limits');
// Topic icons
define('TOPIC_ICONS_TABLE', $this_prefix.'topic_icons');

//
// Define some basic configuration arrays this also prevents
// malicious rewriting of language and otherarray values via
// URI params
//
$board_config = $userdata = $theme = $images = $lang = $nav_links = $attach_config = array();
$gen_simple_header = FALSE;
require_once('includes/phpBB/constants.php');
require_once('includes/phpBB/auth.php');
require_once('includes/phpBB/functions.php');

if (isset($module_name) && can_admin($module_name)) { $userinfo['user_level'] = ADMIN; }
$user_ip = !empty($userinfo['user_ip']) ? $userinfo['user_ip'] : ($user_ip = $userinfo['user_ip'] = $db->binary_safe(Security::get_ip()));

//
// Setup forum wide options, if this fails
// then we output a BB_CRITICAL_ERROR since
// basic forum information is not available
//
if (!Cache::array_load('board_config', basename(dirname(__FILE__)), true)) {
	$result = $db->sql_query("SELECT * FROM ".CONFIG_TABLE);
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$board_config[$row['config_name']] = $row['config_value'];
	}
	$db->sql_freeresult($result);
	Cache::array_save('board_config', basename(dirname(__FILE__)));
}
//if (defined('BBAttach_mod')) {
require_once('includes/phpBB/attach/functions_includes.php');
require_once('includes/phpBB/attach/functions_attach.php');
require_once('includes/phpBB/attach/functions_delete.php');
require_once('includes/phpBB/attach/functions_thumbs.php');
require_once('includes/phpBB/attach/functions_filetypes.php');

//
// Get Attachment Config
//
if (!Cache::array_load('attach_config', 'Forums', true)) {
	$result = $db->sql_query('SELECT * FROM '.ATTACH_CONFIG_TABLE);
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$attach_config[$row['config_name']] = $row['config_value'];
	}
	$db->sql_freeresult($result);
	$attach_config['board_lang'] = $board_config['default_lang'];
	Cache::array_save('attach_config', 'Forums', $attach_config);
}

// Functions for displaying Attachment Things
require_once('includes/phpBB/displaying.php');
require_once('includes/phpBB/class.attachments.main.php');

if (isset($attach_config['allow_ftp_upload']) && !intval($attach_config['allow_ftp_upload'])) {
	$upload_dir = $attach_config['upload_dir'];
} elseif (isset($attach_config['allow_ftp_upload'])) {
	$upload_dir = $attach_config['download_path'];
}
//}

global $MAIN_CFG;
$board_config['smilies_path'] = 'images/smiles';
$board_config['sitename'] =& $MAIN_CFG['global']['sitename'];
$board_config['site_desc'] =& $MAIN_CFG['global']['slogan'];
$board_config['board_email'] =& $MAIN_CFG['global']['adminmail'];
$board_config['server_name'] =& $MAIN_CFG['server']['domain'];
$board_config['board_email_sig'] = '';
