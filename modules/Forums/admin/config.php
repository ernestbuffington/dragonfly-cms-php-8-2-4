<?php
/***************************************************************************
 *					admin_board.php
 *				  -------------------
 *	 begin		  : Thursday, Jul 12, 2001
 *	 copyright	  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 * Last modification made by CPG Dev Team http://cpgnuke.com:
 *
 ***************************************************************************/
 
/* Applied rules:
 * TernaryToNullCoalescingRector
 */
  
if (!defined('ADMIN_PAGES')) { exit; }

if ('POST' === $_SERVER['REQUEST_METHOD']) {
	$options = array(
		'flood_interval',
		'topics_per_page',
		'posts_per_page',
		'hot_threshold',
		'default_dateformat',
		'archive_enable',
		'prune_enable',
		'allow_topic_recycle',
		'topic_recycle_forum',
		'allow_online_index',
		'online_index_group',
		'allow_online_today',
		'online_today_group',
		'admin_color',
		'moderator_color',
		'member_color',
		'allow_online_posts',
		'restricted_group',
		'max_poll_options',
		'allow_html',
		'allow_html_tags',
		'allow_bbcode',
		'allow_smilies',
		'allow_sig',
		'allow_forum_watch',
		'edit_last_post_only',
		'ropm_quick_reply',
		'user_reg_date_age',
	);
	foreach ($options as $config_name) {
		$value = $_POST[$config_name] ?? '';
		$db->exec("UPDATE {$db->TBL->bbconfig} SET
		config_value = " . $db->quote($value) . "
		WHERE config_name = " . $db->quote($config_name));
	}
	BoardCache::cacheDelete('board_config');

	global $lang;
	\Dragonfly::closeRequest($lang['Config_updated'], 200, $_SERVER['REQUEST_URI']);
}

# Pull all config data
$board_config = BoardCache::conf();

$result = $db->query("SELECT
	f.forum_id id,
	f.forum_name name,
	CASE WHEN f.forum_id = {$board_config['topic_recycle_forum']} THEN 1 ELSE 0 END current
FROM ".FORUMS_TABLE." f, ".CATEGORIES_TABLE." c
WHERE c.cat_id = f.cat_id
ORDER BY c.cat_order ASC, f.forum_order ASC");

$template->set_handle('body', 'Forums/admin/config');

$template->assign_vars(array(
	'forum_cfg' => $board_config,
	'recycle_forums' => $result,
));
