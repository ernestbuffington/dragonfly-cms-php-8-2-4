<?php
/***************************************************************************
 *				  functions_admin.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 Modifications made by CPG Dev Team http://cpgnuke.com
 *	 Last modification notes:
 *
 *	 $Id: functions_admin.php,v 9.2 2005/03/12 03:14:55 djmaze Exp $
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
// Simple version of jumpbox, just lists authed forums
//
function make_forum_select($box_name, $ignore_forum = false, $select_forum = '')
{
	global $db, $userdata;
	$is_auth_ary = auth(AUTH_READ, AUTH_LIST_ALL, $userdata);
	$forum_list = '';
	$result = $db->sql_query("SELECT forum_id, forum_name FROM " . FORUMS_TABLE . " ORDER BY cat_id, forum_order");
	while ($row = $db->sql_fetchrow($result)) {
		if ($is_auth_ary[$row['forum_id']]['auth_read'] && $ignore_forum != $row['forum_id']) {
			$selected = ($select_forum == $row['forum_id']) ? ' selected="selected"' : '';
			$forum_list .= '<option value="' . $row['forum_id'] . '"' . $selected .'>' . $row['forum_name'] . '</option>';
		}
	}
	$forum_list = ( $forum_list == '' ) ? '<option value="-1">-- ! No Forums ! --</option>' : '<select name="' . $box_name . '">' . $forum_list . '</select>';
	return $forum_list;
}

//
// Synchronise functions for forums/topics
//
function sync($type, $id = false)
{
	global $db;

	switch($type)
	{
		case 'all forums':
			$result = $db->sql_query("SELECT forum_id FROM " . FORUMS_TABLE);
			while ($row = $db->sql_fetchrow($result)) {
				sync('forum', $row['forum_id']);
			}
			break;

		case 'all topics':
			$result = $db->sql_query("SELECT topic_id FROM ".TOPICS_TABLE);
			while ($row = $db->sql_fetchrow($result)) {
				sync('topic', $row['topic_id']);
			}
			break;

		case 'forum':
			$sql = "SELECT MAX(post_id) AS last_post, COUNT(post_id) AS total
				FROM " . POSTS_TABLE . " WHERE forum_id = $id";
			$result = $db->sql_query($sql);
			if ($row = $db->sql_fetchrow($result)) {
				$last_post = ($row['last_post']) ? $row['last_post'] : 0;
				$total_posts = ($row['total']) ? $row['total'] : 0;
			} else {
				$last_post = 0;
				$total_posts = 0;
			}
			$result = $db->sql_query("SELECT COUNT(topic_id) AS total FROM " . TOPICS_TABLE . " WHERE forum_id = $id");
			$total_topics = ( $row = $db->sql_fetchrow($result) ) ? ( ( $row['total'] ) ? $row['total'] : 0 ) : 0;
			$sql = "UPDATE " . FORUMS_TABLE . "
				SET forum_last_post_id = $last_post, forum_posts = $total_posts, forum_topics = $total_topics
				WHERE forum_id = $id";
			$db->sql_query($sql);
			break;

		case 'topic':
			$sql = "SELECT MAX(post_id) AS last_post, MIN(post_id) AS first_post, COUNT(post_id) AS total_posts
				FROM " . POSTS_TABLE . " WHERE topic_id = $id";
			$result = $db->sql_query($sql);
			if ($row = $db->sql_fetchrow($result))
			{
				$sql = ( $row['total_posts'] ) ? "UPDATE ".TOPICS_TABLE." SET topic_replies = ".($row['total_posts'] - 1).", topic_first_post_id = ".$row['first_post'].", topic_last_post_id = ".$row['last_post'] : "DELETE FROM ".TOPICS_TABLE;
				$db->sql_query($sql." WHERE topic_id = $id");
			}
//			  if (defined('BBAttach_mod')) {
			attachment_sync_topic($id);
			break;
	}

	return true;
}