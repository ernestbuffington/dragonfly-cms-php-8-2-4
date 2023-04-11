<?php
/***************************************************************************
*	moderators.php
*	copyright CPG DragonflyCMS
*
*	This program is free software; you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation; either version 2 of the License, or
*	(at your option) any later version.
***************************************************************************/

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

$categories = BoardCache::categories();
if (!$categories) {
	message_die(GENERAL_MESSAGE, $lang['No_forums']);
}

$forums = get_forums();
if (!$forums) {
	message_die(GENERAL_MESSAGE, $lang['No_forums']);
}

$template->U_INDEX = URL::index();

foreach ($forums as $forum) {
	$cat_id = $forum['cat_id'];
	if (!isset($categories[$cat_id]['forums'])) {
		$categories[$cat_id]['href'] = URL::index("&c={$cat_id}");
		$categories[$cat_id]['forums'] = array();
	}
	$categories[$cat_id]['forums'][] = $forum;
}
unset($forums);

foreach ($categories as $i => $cat) {
	if (empty($cat['forums'])) {
		unset($categories[$i]);
	}
}

$template->forum_moderators = $categories;

# Display the page
\Dragonfly\Page::title($lang['Moderators']);
require_once('includes/phpBB/page_header.php');
$template->display('forums/moderators');

function get_forums()
{
	global $template;
	$lang = $template->L10N;
	$images = get_forums_images();

	$forums_all  = array_values(BoardCache::forums_rows());
	$is_auth_ary = \Dragonfly\Forums\Auth::view(0, $forums_all);
	$forums_ref  = array();
	foreach ($forums_all as &$forum) {
		$forum_id = $forum['forum_id'];
		if ($is_auth_ary[$forum_id]['auth_view']) {
			$moderators = BoardCache::forumModeratorsHTML($forum_id);
			if (2 == $forum['forum_type']) {
				$href = URL::index($forum['forum_link']);
			} else if (3 == $forum['forum_type']) {
				$href = $forum['forum_link'];
			} else {
				$href = URL::index("&file=viewforum&f={$forum_id}");
			}
			$forums_ref[$forum_id] = array(
				'is_link'    => ($forum['forum_type'] >= 2),
				'id'         => $forum_id,
				'cat_id'     => $forum['cat_id'],
				'parent_id'  => $forum['parent_id'],
				'name'       => $forum['forum_name'],
				'image_src'  => ($forum['forum_type'] == 1 ? $images['forum_sub'] : $images['forum']),
				'image_alt'  => ($forum['forum_type'] == 1 ? $lang['Subforums'] : $lang['Forum']),
				'moderators' => $moderators ? implode( ', ', $moderators) : $lang['no_moderators'],
				'href'       => $href,
				'subforums'  => array(),
			);
		}
	}
	unset($forums_all);

	// Set forums tree structure
	$forums = array();
	foreach ($forums_ref as &$forum) {
		if (0 < $forum['parent_id']) {
			$pid = $forum['parent_id'];
			if (isset($forums_ref[$pid])) {
				$forums_ref[$pid]['subforums'][] = &$forum;
			}
		} else{
			$forums[] = &$forum;
		}
	}

	return $forums;
}
