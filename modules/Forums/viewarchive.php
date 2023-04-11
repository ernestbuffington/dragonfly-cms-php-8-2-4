<?php
/***************************************************************************
 *								index.php
 *							-------------------
 *	 begin				: Saturday, Feb 13, 2001
 *	 copyright			: (C) 2001 The phpBB Group
 *	 email				: support@phpbb.com
 *
 ***************************************************************************/
/***************************************************************************
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 ***************************************************************************/
 
/* Applied rules:
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

use \Dragonfly\Forums\Forum;
use \Dragonfly\Forums\Topic;

# Start initial var setup
$forum_id = $_GET->uint('f') ?: $_POST->uint('f');
$start = $_GET->uint('start') ?: 0;
# End initial var setup

// Check if the user has actually sent a forum ID with his/her request
// If not give them a nice error page.
if (!$forum_id) {
	\Poodle\HTTP\Status::set(404);
	message_die(GENERAL_MESSAGE, $lang['Forum_not_exist']);
	//cpg_error('Forum_not_exist', GENERAL_MESSAGE);
}

try {
	$forum = new Forum($forum_id);
} catch (\Exception $e) {
	\Poodle\HTTP\Status::set(404);
	message_die(GENERAL_MESSAGE, $e->getMessage());
}

if (Forum::TYPE_URL_REMOTE == $forum['forum_type']) {
	URL::redirect($forum['forum_link']);
}

# Start auth check
$is_auth = $forum->getUserPermissions();

if (!$is_auth['auth_read'] || !$is_auth['auth_view']) {
	if (!is_user()) {
		\URL::redirect(\Dragonfly\Identity::loginURL());
	}
	# The user is not authed to read this forum ...
	$message = (!$is_auth['auth_view']) ? $lang['Forum_not_exist'] : sprintf($lang['Sorry_auth_read'], $is_auth['auth_read_type']);
	message_die(GENERAL_MESSAGE, $message);
	//cpg_error($message, GENERAL_MESSAGE);
}
# End of auth check

$topics_count = $forum->archive_topics;

$query = "SELECT
	t.topic_id,
	t.topic_title,
	t.topic_time,
	t.topic_views,
	t.topic_replies,
	t.topic_status,
	t.topic_vote,
	t.topic_type,
	t.topic_last_post_id,
	t.topic_moved_id,
	t.topic_attachment,
	t.icon_id,
	u.username,
	u.user_id,
	u2.username as user2,
	u2.user_id as id2,
	p.post_username,
	p2.post_username as post_username2,
	p2.post_time
FROM ".TOPICS_TABLE." t, {$db->TBL->users} u, ".POSTS_ARCHIVE_TABLE." p, ".POSTS_ARCHIVE_TABLE." p2, {$db->TBL->users} u2
WHERE t.forum_id = {$forum_id}
  AND t.topic_poster = u.user_id
  AND p.post_id = t.topic_first_post_id
  AND p2.post_id = t.topic_last_post_id
  AND u2.user_id = p2.poster_id";

// All announcement data, this keeps announcements on each viewforum page ...
$topics = $db->uFetchAll($query . "
	  AND t.topic_type = ".Topic::TYPE_ANNOUNCE."
	ORDER BY t.topic_last_post_id DESC");
$total_announcements = is_countable($topics) ? count($topics) : 0;

// Grab all the basic data (all topics except announcements) for this forum
$result = $db->query($query . "
	  AND t.topic_type <> ".Topic::TYPE_ANNOUNCE."
	ORDER BY t.topic_type DESC, t.topic_last_post_id DESC
	LIMIT {$board_config['topics_per_page']} OFFSET {$start}");
while ($row = $result->fetch_assoc()) {
	$topics[] = $row;
}
$result->free();

unset($query);

// Define censored word matches
$orig_word = $replacement_word = array();
obtain_word_list($orig_word, $replacement_word);

// Dump out the page header and load template
\Dragonfly\Page::title($forum['cat_title'].' '._BC_DELIM.' '. $forum['forum_name'].' :: '.$lang['Archives']);

$images = get_forums_images();

if (Forum::TYPE_PARENT == $forum['forum_type']) {
	$subforums = \Dragonfly\Forums\Display::forums($forum_id);
	$template->forumrow = array();
	foreach ($subforums as &$subforum) {
		$sub_forum_id = $subforum['forum_id'];
		if (Forum::TYPE_URL_LOCAL == $subforum['forum_type']) {
			$subforum['U_VIEWFORUM'] = URL::index($subforum['forum_link']);
		} else if (Forum::TYPE_URL_REMOTE == $subforum['forum_type']) {
			$subforum['U_VIEWFORUM'] = $subforum['forum_link'];
		} else {
			$subforum['U_VIEWFORUM'] = URL::index("&file=viewarchive&f={$sub_forum_id}");
		}
		$subforum['SUB_FORUMS'] = (Forum::TYPE_PARENT == $subforum['forum_type']);
		$subforum['IS_LINK'] = ($subforum['forum_type'] >= Forum::TYPE_URL_LOCAL);
		$template->forumrow[] = $subforum;
	}
	unset($subforums);
}

$parents = array();
if ($forum->parent_id) {
	$parent_id = $forum->parent_id;
	while ($parent_id) {
		list ($parent_name, $parent_id, $parent_forum_id) = $db->uFetchRow("SELECT forum_name AS parent_name, parent_id, forum_id FROM " . FORUMS_TABLE . " WHERE forum_id = $parent_id");
		$parents[] = array(
			'name' => $parent_name,
			'uri' => URL::index("&file=viewforum&f={$parent_forum_id}")
		);
	}
	$parents = array_reverse($parents);
}

# Obtain list of moderators of each forum, first users, then groups - 2 queries
$moderators = BoardCache::forumModeratorsHTML($forum_id);

$template->assign_vars(array(
	'SF_PARENTS' => $parents,
	'SUB_FORUMS' => (Forum::TYPE_PARENT == $forum['forum_type']),
	'MODERATORS' => count($moderators) ? implode(', ', $moderators) : $lang['None'],
	'L_MODERATOR' => ((is_countable($moderators) ? count($moderators) : 0) == 1) ? $lang['Moderator'] : $lang['Moderators'],
	'U_ARCHIVES' => URL::index('&file=archives'),
	'S_POST_DAYS_ACTION'=> URL::index("&file=viewarchive&f={$forum_id}&start={$start}"),
));
make_jumpbox('viewforum');
// End header

unset($moderators);

// Okay, lets dump out the page ...
$template->forum_topics = array();
if ($topics) {
	$announces = $stickies = $normalposts = false;
	$topic_icons = BoardCache::topic_icons();
	foreach ($topics as &$topic) {
		$topic_id = $topic['topic_id'];
		$replies = $topic['topic_replies'];

		// grab this topic's icon_id
		$topic_icon_id = $topic['icon_id'];
		$topic_icon = false;
		// if we have an icon
		if ($topic_icon_id && isset($topic_icons[$topic_icon_id])) {
			$topic_icon = array(
				'uri' => DF_STATIC_DOMAIN.$topic_icons[$topic_icon_id]['icon_url'],
				'name' => $topic_icons[$topic_icon_id]['icon_name'],
			);
		}

		if (Topic::TYPE_ANNOUNCE == $topic['topic_type']) {
			$topics_header = (!$announces) ? $lang['Post_Announcement'] : '';
			$announces = true;
		} else if (Topic::TYPE_STICKY == $topic['topic_type']) {
			$topics_header = (!$stickies) ? $lang['Post_Sticky'] : '';
			$stickies = true;
		} else {
			$topics_header = (!$normalposts) ? $lang['Post_Normal'] : '';
			$normalposts = true;
		}

		$topic_image = 'folder';
		if (Topic::STATUS_MOVED == $topic['topic_status']) {
			$topic_type = $lang['Topic_Moved'].' ';
			$topic_id   = $topic['topic_moved_id'];
			$topic_image_alt = $lang['Topics_Moved'];
		} else {
			$topic_type = $topic['topic_vote'] ? $lang['Topic_Poll'].' ' : '';
			if (Topic::TYPE_ANNOUNCE == $topic['topic_type']) {
				$topic_image .= '_announce';
			} else if (Topic::TYPE_STICKY == $topic['topic_type']) {
				$topic_image .= '_sticky';
			} else if (Topic::STATUS_LOCKED == $topic['topic_status']) {
				$topic_image .= '_locked';
			} else if ($replies >= $board_config['hot_threshold']) {
				$topic_image .= '_hot';
			}
			$topic_image_alt = (Topic::STATUS_LOCKED == $topic['topic_status']) ? $lang['Topic_locked'] : $lang['No_new_posts'];
		}
		$topic_image = $images[$topic_image];

		$goto_page = array();
		if ($replies >= $board_config['posts_per_page']) {
			$total_pages = ceil(($replies + 1) / $board_config['posts_per_page']);
			$page = max(2, $total_pages - 2);
			for (; $page <= $total_pages; ++$page) {
				$j = ($page - 1) * $board_config['posts_per_page'];
				$goto_page[] = array('no' => $page, 'uri' => URL::index("&file=viewtopic&t={$topic_id}&start={$j}"));
			}
		}

		$view_topic_url = URL::index("&file=viewtopic&t={$topic_id}");

		$last_post_url = URL::index('&file=viewtopic&p='.$topic['topic_last_post_id']).'#'.$topic['topic_last_post_id'];

		if ($orig_word) {
			$topic['topic_title'] = preg_replace($orig_word, $replacement_word, $topic['topic_title']);
		}

		$template->forum_topics[] = $topic + array(
			'attachment_img' => \Dragonfly\Forums\Attachments::getTopicImage($topic['topic_attachment'], $forum),
			'image' => array(
				'uri' => $topic_image,
				'name' => $topic_image_alt,
			),
			'icon' => $topic_icon,
			'author' => array(
				'name' => (\Dragonfly\Identity::ANONYMOUS_ID == $topic['user_id']) ? ($topic['post_username'] ?: $lang['Guest']) : $topic['username'],
				'uri'  => (\Dragonfly\Identity::ANONYMOUS_ID == $topic['user_id']) ? false : \Dragonfly\Identity::getProfileURL($topic['user_id']),
			),
			'last_poster' => array(
				'name' => (\Dragonfly\Identity::ANONYMOUS_ID == $topic['id2']) ? ($topic['post_username2'] ?: $lang['Guest']) : $topic['username'],
				'uri'  => (\Dragonfly\Identity::ANONYMOUS_ID == $topic['id2']) ? false : \Dragonfly\Identity::getProfileURL($topic['user2']),
			),
			'L_HEADER' => $topics_header,
			'goto_page' => $goto_page,
			'TOPIC_TYPE' => $topic_type,
			'U_LAST_POST' => $last_post_url,
			'U_VIEW_TOPIC' => $view_topic_url,
		);
	}
	unset($topics);

	if ($topics_count > $total_announcements) {
		$topics_count -= $total_announcements;
	}

	$template->assign_vars(array(
		'PAGINATION'  => generate_pagination("&file=viewarchive&f={$forum_id}", $topics_count, $board_config['topics_per_page'], $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $topics_count / $board_config['topics_per_page'] )),
	));
} else {
	// No topics
	$template->assign_vars(array(
		'PAGINATION'  => '',
		'PAGE_NUMBER' => '',
	));
}

// Parse the page and print
if ($template->isTALThemeFile('forums/viewarchive_body')) {
	$template->forum = $forum;
	$template->board_config = $board_config;
	$template->board_images = $images;
	$template->user_auth = $is_auth;
	$template->set_handle('body', 'forums/viewarchive_body');
} else {
	require __DIR__ . '/v9/viewarchive.php';
}

require_once('includes/phpBB/page_header.php');
$template->display('body');
