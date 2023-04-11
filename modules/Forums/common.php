<?php
/***************************************************************************
*				 common.php
*				 -------------------
*	begin		 : Saturday, Feb 23, 2001
*	copyright		 : (C) 2001 The phpBB Group
*	email		 : support@phpbb.com
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
if (!class_exists('Dragonfly', false)) { exit; }
if (!defined('IN_PHPBB')) { exit; }

use \Dragonfly\Forums\Auth;

global $prefix;
$this_prefix = $prefix.'_'.((basename(__DIR__) == 'Forums') ? 'bb' : strtolower(basename(__DIR__)).'_');
// Table names
define('AUTH_ACCESS_TABLE', $this_prefix.'auth_access');
define('CATEGORIES_TABLE', $this_prefix.'categories');
//define('CONFIG_TABLE', $db->TBL->bbconfig);
define('FORUMS_TABLE', $this_prefix.'forums');
define('FORUMS_WATCH_TABLE', $this_prefix.'forums_watch');
define('POSTS_TABLE', $this_prefix.'posts');
define('POSTS_TEXT_TABLE', $this_prefix.'posts_text');
define('POSTS_ARCHIVE_TABLE', $this_prefix.'posts_archive');
define('POSTS_TEXT_ARCHIVE_TABLE', $this_prefix.'posts_text_archive');
define('POSTS_REPUTATIONS_TABLE', $this_prefix.'posts_reputations');
define('TOPICS_TABLE', $this_prefix.'topics');
define('TOPICS_WATCH_TABLE', $this_prefix.'topics_watch');
//define('USERS_TABLE', $db->TBL->users);
//define('WORDS_TABLE', $db->TBL->bbwords);
//define('VOTE_DESC_TABLE', $this_prefix.'vote_desc');
//define('VOTE_RESULTS_TABLE', $this_prefix.'vote_results');
//define('VOTE_USERS_TABLE', $this_prefix.'vote_voters');
// attach mod
//define('ATTACH_CONFIG_TABLE', $db->TBL->bbattachments_config);
//define('EXTENSION_GROUPS_TABLE', $db->TBL->bbextension_groups);
//define('EXTENSIONS_TABLE', $db->TBL->bbextensions);
//define('FORBIDDEN_EXTENSIONS_TABLE', $db->TBL->bbforbidden_extensions);
define('ATTACHMENTS_DESC_TABLE', $this_prefix.'attachments_desc');
define('ATTACHMENTS_TABLE', $this_prefix.'attachments');
# Topic icons
define('TOPIC_ICONS_TABLE', $this_prefix.'topic_icons');

# Error codes
define('GENERAL_MESSAGE', 200);
define('GENERAL_ERROR', 202);

# Auth settings
define('AUTH_ALL', Auth::LEVEL_ALL);
define('AUTH_REG', Auth::LEVEL_REG);
define('AUTH_ACL', Auth::LEVEL_ACL);
define('AUTH_MOD', Auth::LEVEL_MOD);
define('AUTH_ADMIN', Auth::LEVEL_ADMIN);

# Attachment categories
define('IMAGE_CAT', 1);
define('STREAM_CAT', 2);
define('SWF_CAT', 3);

# Misc
define('THUMB_DIR', 'thumbs');

// Define some basic configuration arrays
$board_config = $attach_config = array();

require_once('includes/phpBB/functions.php');
require_once('includes/phpBB/functions_attach.php');
require_once(__DIR__ . '/classes/BoardCache.php');

function auth($type, $forum_id, $userinfo = null, $f_access = null)
{
	trigger_deprecated("use \Dragonfly\Forums\Auth::forType()");
	return Auth::forType($type, $forum_id, $f_access);
}

// Setup forum wide options
$board_config = BoardCache::conf();

# Get Attachment Config
$attach_config = \Dragonfly\Forums\Attachments::getConfig();

if (!defined('ADMIN_PAGES')) {
	\Dragonfly\Page::title($module_title ?: $mod_name);
	\Dragonfly\Output\Css::add('forums');
	\Dragonfly\Output\js::add('modules/Forums/javascript/forums.js');
}
