<?php
/***************************************************************************
 *								  index.php
 *							  -------------------
 *	 begin				  : Saturday, Feb 13, 2001
 *	 copyright			  : (C) 2001 The phpBB Group
 *	 email				  : support@phpbb.com
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

// Some Chinese try to register a new user through some forums ?action=register
if ('register' === $_GET->txt('action')) {
	Security::banIP($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI']);
	exit('Banned');
}

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

function Forums_Index()
{
	global $board_config, $module_name;
	$K = \Dragonfly::getKernel();
	$OUT = $K->OUT;
	$lang = $OUT->L10N;
	$userinfo = $K->IDENTITY;
	$OUT->board_images = get_forums_images();

	$viewcat = $_GET->uint('c');
	\Dragonfly\Page::tag('link rel="canonical" href="'.\URL::index($module_name).'"');

	# Handle marking posts
	if (isset($_GET['mark']) && 'forums' == $_GET->text('mark')) {
		if (is_user()) {
			$_SESSION['CPG_SESS'][$module_name]['track_all'] = time();
		}
		\Poodle\Notify::success($lang['Forums_marked_read']);
		\URL::redirect(\URL::index());
	}
	# End handle marking posts

	# If you don't use these stats on your index you may want to consider removing them
	$total_posts = $total_topics = $total_posta = $total_topica = 0;

	# Start page proper
	$categories = BoardCache::categories();
	if (!$categories) {
		message_die(GENERAL_MESSAGE, $lang['No_forums']);
	}

	$forums = \Dragonfly\Forums\Display::forums();
	if (!$forums) {
		message_die(GENERAL_MESSAGE, $lang['No_forums']);
	}

	# Start output of page
	\Dragonfly\Page::title(_HOME); //$lang['Index'];

	$OUT->S_ONLINE_INDEX = \Dragonfly\Forums\Display::onlineNow();
	$OUT->S_ONLINE_TODAY = \Dragonfly\Forums\Display::onlineToday();

	if ($OUT->isTALThemeFile('forums/index_body')) {
		foreach ($forums as $i => $forum) {
			$forums[$i] = null;

			$total_posts  += $forum['forum_posts'];
			$total_topics += $forum['forum_topics'];
			$total_posta  += $forum['archive_posts'];
			$total_topica += $forum['archive_topics'];

			$cat_id = $forum['cat_id'];
			if (!$viewcat || $viewcat == $cat_id) {
				$forum_id = $forum['forum_id'];
				if ($forum['forum_type'] == 2) {
					$forumlink = URL::index($forum['forum_link']);
				} else if ($forum['forum_type'] == 3) {
					$forumlink = $forum['forum_link'];
				} else {
					$forumlink = URL::index("&file=viewforum&f={$forum_id}");
				}
				$forum['IS_LINK']        = ($forum['forum_type'] >= 2);
				$forum['LAST_POST_TIME'] = ($forum['forum_last_post_id'] ) ? $lang->date($board_config['default_dateformat'], $forum['post_time']) : '';
				$forum['LAST_POSTER']    = ($forum['username'] != 'Anonymous') ? $forum['username'] : $forum['post_username'];
				$forum['SUB_FORUMS']     = ($forum['forum_type'] == 1);
				$forum['U_LAST_POSTER']  = ($forum['user_id'] > \Dragonfly\Identity::ANONYMOUS_ID) ? \Dragonfly\Identity::getProfileURL($forum['user_id']) : '';
				$forum['U_LAST_POST']    = ($forum['forum_last_post_id']) ? URL::index("&file=viewtopic&p={$forum['forum_last_post_id']}") . '#' . $forum['forum_last_post_id'] : '';
				$forum['U_VIEWFORUM']    = $forumlink;
				$forum['U_VIEWARCHIVE']  = !empty($forum['archive_topics']) ? URL::index("&file=viewarchive&f={$forum_id}") : '';

				if (empty($categories[$cat_id]['forums'])) {
					$categories[$cat_id]['forums'] = array();
				}
				$categories[$cat_id]['forums'][] = $forum;
			}
		}

		foreach ($categories as $id => $category) {
			# Should we display this category/forum set?
			if (empty($category['forums'])) {
				unset($categories[$id]);
			} else {
				$categories[$id]['u_view'] = URL::index("&c={$category['id']}");
			}
		}
		$OUT->categories = $categories;

		$OUT->board_config = $board_config;

		# Generate the page
		$OUT->set_handle('body', 'forums/index_body');
	} else {
		require __DIR__ . '/v9/index.php';
	}

	unset($categories, $forums);

	$OUT->TOTAL_POSTS_TOPICS = sprintf($lang['Total_posts_topics'], $total_posts, $total_topics, $total_posta, $total_topica);

	require_once('includes/phpBB/page_header.php');
	$OUT->display('body');
}

Forums_Index();
