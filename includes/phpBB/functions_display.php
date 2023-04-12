<?php

/*********************************************
  CPG-NUKE: Advanced Content Management System
  ********************************************
  Copyright (c) 2004 by CPG Dev Team

  Under the GNU General Public License version 2

  Last modification notes:

	$Id: functions_display.php,v 9.5 2006/06/12 20:07:45 djmaze Exp $

*************************************************************/

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

function display_forums($parent=0, $display_moderators = TRUE) {
	global $db, $board_config, $lang, $images, $userdata, $CPG_SESS, $module_name;

	$tracking_topics = isset($CPG_SESS[$module_name]['track_topics']) ? $CPG_SESS[$module_name]['track_topics'] : array();
	$tracking_forums = isset($CPG_SESS[$module_name]['track_forums']) ? $CPG_SESS[$module_name]['track_forums'] : array();

	//
	// Define appropriate SQL
	//
	switch(SQL_LAYER)
	{
		case 'oracle':
			$sql = "SELECT f.*, p.post_time, p.post_username, u.username, u.user_id
					FROM ".FORUMS_TABLE." f, ".POSTS_TABLE." p, ".USERS_TABLE." u
					WHERE p.post_id = f.forum_last_post_id(+)
						AND u.user_id = p.poster_id(+)
					ORDER BY f.cat_id, f.forum_order";
			break;

		default:
			$sql = "SELECT f.*, p.post_time, p.post_username, u.username, u.user_id
					FROM (( ".FORUMS_TABLE." f
					LEFT JOIN ".POSTS_TABLE." p ON p.post_id = f.forum_last_post_id )
					LEFT JOIN ".USERS_TABLE." u ON u.user_id = p.poster_id )
					WHERE f.parent_id=$parent
					ORDER BY f.cat_id, f.forum_order";
			break;
	}
	$result = $db->sql_query($sql);

	$tmp_forums = array();
	while ( $row = $db->sql_fetchrow($result) ) {
		$tmp_forums[] = $row;
// Lanzer speedup
//		  if ($row['post_time'] > $userdata['user_lastvisit']) $new_topic_data[$row['forum_id']][$row['topic_id']] = $row['post_time'];
	}
	$db->sql_freeresult($result);

	//
	// Obtain a list of topic ids which contain
	// posts made since user last visited
	//
	if ( is_user() ) {
		$lastvisit = $userdata['user_lastvisit'];
		if ( isset($CPG_SESS[$module_name]['track_all']) ) {
			$lastvisit = $CPG_SESS[$module_name]['track_all'];
		}
		$result = $db->sql_query('SELECT t.forum_id, t.topic_id, p.post_time
				FROM '.TOPICS_TABLE.' t, '.POSTS_TABLE.' p
				WHERE p.post_id = t.topic_last_post_id
					AND p.post_time > '.$lastvisit.'
					AND t.topic_moved_id = 0
				ORDER BY p.post_time DESC');
		$new_topic_data = array();
		while( $topic_data = $db->sql_fetchrow($result) ) {
			if ( empty($new_topic_data[$topic_data['forum_id']])) {
				if ( empty($tracking_topics[$topic_data['topic_id']]) ) {
					$new_topic_data[$topic_data['forum_id']] = true;
				} elseif ( $tracking_topics[$topic_data['topic_id']] < $topic_data['post_time'] ) {
					$new_topic_data[$topic_data['forum_id']] = true;
				}
				if ( !empty($tracking_forums[$topic_data['forum_id']]) ) {
					$new_topic_data[$topic_data['forum_id']] = ( $tracking_forums[$topic_data['forum_id']] < $topic_data['post_time'] );
				}
			}
		}
		$db->sql_freeresult($result);
	}

	//
	// Obtain list of moderators of each forum
	// First users, then groups ... broken into two queries
	//
	global $forum_moderators;
	$forum_moderators = array();
	if ($display_moderators) {
	if (!cache_load_array('forum_moderators', $module_name)) {
		$sql = "SELECT aa.forum_id, u.user_id, u.username
				FROM ".AUTH_ACCESS_TABLE." aa, ".USER_GROUP_TABLE." ug, ".GROUPS_TABLE." g, ".USERS_TABLE." u
				WHERE aa.auth_mod = ".TRUE." AND g.group_single_user = 1
					AND ug.group_id = aa.group_id AND g.group_id = aa.group_id
					AND u.user_id = ug.user_id
				GROUP BY u.user_id, u.username, aa.forum_id
				ORDER BY aa.forum_id, u.user_id";
		$result = $db->sql_query($sql);
		while( $row = $db->sql_fetchrow($result) ) {
			$forum_moderators[$row['forum_id']][] = '<a href="'.getlink("Your_Account&amp;profile=".$row['user_id']).'">'.$row['username'].'</a>';
		}
		$db->sql_freeresult($result);

		$sql = "SELECT aa.forum_id, g.group_id, g.group_name
				FROM ".AUTH_ACCESS_TABLE." aa, ".USER_GROUP_TABLE." ug, ".GROUPS_TABLE." g
				WHERE aa.auth_mod = ".TRUE." AND g.group_single_user = 0
					AND g.group_type <> ".GROUP_HIDDEN."
					AND ug.group_id = aa.group_id AND g.group_id = aa.group_id
				GROUP BY g.group_id, g.group_name, aa.forum_id
				ORDER BY aa.forum_id, g.group_id";
		$result = $db->sql_query($sql);

		while( $row = $db->sql_fetchrow($result) ) {
			$forum_moderators[$row['forum_id']][] = '<a href="'.getlink("Groups&amp;".POST_GROUPS_URL."=".$row['group_id']).'">'.$row['group_name'].'</a>';
		}
		$db->sql_freeresult($result);
		cache_save_array('forum_moderators', $module_name);
	}
	}
	
	//
	// Find which forums are visible for this user
	//
	$is_auth_ary = auth(AUTH_VIEW, AUTH_LIST_ALL, $userdata, $tmp_forums);

	$forum_data = array();
	for($j = 0; $j < count($tmp_forums); $j++) {
		$forum_id = $tmp_forums[$j]['forum_id'];
		if ( $is_auth_ary[$forum_id]['auth_view'] ) {
			if ($tmp_forums[$j]['forum_type'] >= 2) {
				$folder_image = $images['forum_link'];
				$folder_alt = 'link';
			} else if ( $tmp_forums[$j]['forum_status'] == FORUM_LOCKED ) {
				$folder_image = $images['forum_locked'];
				$folder_alt = $lang['Forum_locked'];
			} else {
				$unread_topics = false;
				if (is_user()) {
					$unread_topics = !empty($new_topic_data[$forum_id]);
				}
				if ($tmp_forums[$j]['forum_type'] == 1) {
					$folder_image = ( $unread_topics ) ? $images['forum_new_sub'] : $images['forum_sub'];
				} else {
					$folder_image = ( $unread_topics ) ? $images['forum_new'] : $images['forum'];
				}
				$folder_alt = ( $unread_topics ) ? $lang['New_posts'] : $lang['No_new_posts'];

			}

			if (isset($forum_moderators[$forum_id]) && count($forum_moderators[$forum_id]) > 0) {
				$l_moderators = ( count($forum_moderators[$forum_id]) == 1 ) ? $lang['Moderator'] : $lang['Moderators'];
				$moderator_list = implode(', ', $forum_moderators[$forum_id]);
			} else {
				$l_moderators = '&nbsp;';
				$moderator_list = '';
			}
			$tmp_forums[$j]['post_username'] = ($tmp_forums[$j]['post_username']) ? $tmp_forums[$j]['post_username'] : $lang['Guest'];
			$tmp_forums[$j]['folder_image'] = $folder_image;
			$tmp_forums[$j]['folder_alt']	= $folder_alt;
			$tmp_forums[$j]['l_moderators']	  = $l_moderators;
			$tmp_forums[$j]['moderator_list'] = $moderator_list;
			$forum_data[] = $tmp_forums[$j];
		}
	}
	return $forum_data;
}
