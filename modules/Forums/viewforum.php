<?php
/***************************************************************************
 *								index.php
 *							-------------------
 *	 begin				: Saturday, Feb 13, 2001
 *	 copyright			: (C) 2001 The phpBB Group
 *	 email				: support@phpbb.com
 *
  Last modification notes:
  $Source: /cvs/html/modules/Forums/viewforum.php,v $
  $Revision: 9.17 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:25 $
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
if (!defined('CPG_NUKE')) { exit; }
require_once('modules/'.$module_name.'/nukebb.php');

//
// Start initial var setup
//
if (isset($_GET[POST_FORUM_URL]) || isset($_POST[POST_FORUM_URL])) {
	$forum_id = intval($_GET[POST_FORUM_URL] ?? $_POST[POST_FORUM_URL]);
} else {
	$forum_id = '';
}
$start = (isset($_GET['start']) ? intval($_GET['start']) : 0);
//
// Start session management
//
$userdata = session_pagestart($user_ip, $forum_id);
init_userprefs($userdata);
//
// End session management
//

//
// End initial var setup
//

//
// Check if the user has actually sent a forum ID with his/her request
// If not give them a nice error page.
//
if (is_numeric($forum_id)) {
	$result = $db->sql_query("SELECT * FROM ".FORUMS_TABLE." f, ".CATEGORIES_TABLE." c
	WHERE f.forum_id = $forum_id
	AND f.cat_id = c.cat_id");
} else {
	message_die(GENERAL_MESSAGE, $lang['Forum_not_exist']);
	//cpg_error('Forum_not_exist', GENERAL_MESSAGE);
}

//
// If the query doesn't return any rows this isn't a valid forum. Inform
// the user.
//
if (!($forum_row = $db->sql_fetchrow($result))) {
	message_die(GENERAL_MESSAGE, $lang['Forum_not_exist']);
	//cpg_error('Forum_not_exist', GENERAL_MESSAGE);
}



//
// Start auth check
//
$is_auth = array();
$is_auth = auth(AUTH_ALL, $forum_id, $userdata, $forum_row);

if (!$is_auth['auth_read'] || !$is_auth['auth_view']) {
	if (!is_user()) {
		url_redirect(getlink('Your_Account'), true);
	}
	//
	// The user is not authed to read this forum ...
	//
	$message = (!$is_auth['auth_view']) ? $lang['Forum_not_exist'] : sprintf($lang['Sorry_auth_read'], $is_auth['auth_read_type']);
	message_die(GENERAL_MESSAGE, $message);
	//cpg_error($message, GENERAL_MESSAGE);

}
//
// End of auth check
//

//
// Handle marking posts
//
if (isset($_GET['mark']) || isset($_POST['mark'])) {
	$mark_read = $_POST['mark'] ?? $_GET['mark'];
	if ($mark_read == 'topics') {
		if (is_user()) {
			$CPG_SESS[$module_name]['track_forums'][$forum_id] = gmtime();
			url_refresh(getlink("&file=viewforum&".POST_FORUM_URL."=$forum_id"));
		}
		$message = $lang['Topics_marked_read'].'<br /><br />'.sprintf($lang['Click_return_forum'], '<a href="'.getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id").'">', '</a> ');
		message_die(GENERAL_MESSAGE, $message);
		//cpg_error($message, GENERAL_MESSAGE);
	}
}
//
// End handle marking posts
//

$tracking_topics = $CPG_SESS[$module_name]['track_topics'] ?? array();
$tracking_forums = $CPG_SESS[$module_name]['track_forums'] ?? array();

//
// Do the forum Prune
//
if ($is_auth['auth_mod'] && $board_config['prune_enable']) {
	if ($forum_row['prune_next'] < gmtime() && $forum_row['prune_enable']) {
		require_once('includes/phpBB/prune.php');
		require_once('includes/phpBB/functions_admin.php');
		auto_prune($forum_id);
	}
}
//
// End of forum prune
//

//
// Obtain list of moderators of each forum
// First users, then groups ... broken into two queries
//
$moderators = array();
if (!cache_load_array('forum_moderators', $module_name)) {
	$sql = "SELECT u.user_id, u.username
		FROM ".AUTH_ACCESS_TABLE." aa, ".USER_GROUP_TABLE." ug, ".GROUPS_TABLE." g, ".USERS_TABLE." u
		WHERE aa.forum_id = $forum_id AND aa.auth_mod = ".TRUE."
			AND g.group_single_user = 1 AND ug.group_id = aa.group_id
			AND g.group_id = aa.group_id AND u.user_id = ug.user_id
		GROUP BY u.user_id, u.username
		ORDER BY u.user_id";
	$result = $db->sql_query($sql);
	while( $row = $db->sql_fetchrow($result, SQL_ASSOC) ) {
	$moderators[] = '<a href="'.getlink("Your_Account&amp;profile=".$row['user_id']).'">'.$row['username'].'</a>';
	}
	$db->sql_freeresult($result);

	$sql = "SELECT g.group_id, g.group_name
		FROM ".AUTH_ACCESS_TABLE." aa, ".USER_GROUP_TABLE." ug, ".GROUPS_TABLE." g
		WHERE aa.forum_id = $forum_id
			AND aa.auth_mod = ".TRUE."
			AND g.group_single_user = 0
			AND g.group_type <> ". GROUP_HIDDEN ."
			AND ug.group_id = aa.group_id
			AND g.group_id = aa.group_id
		GROUP BY g.group_id, g.group_name
		ORDER BY g.group_id";
	$result = $db->sql_query($sql);
	while( $row = $db->sql_fetchrow($result, SQL_ASSOC) ) {
	$moderators[] = '<a href="'.getlink("Groups&amp;".POST_GROUPS_URL."=".$row['group_id']).'">'.$row['group_name'].'</a>';
	}
	$db->sql_freeresult($result);
} else {
	$moderators = $forum_moderators[$forum_id];
}

$l_moderators = ( (is_countable($moderators) ? count($moderators) : 0) == 1 ) ? $lang['Moderator'] : $lang['Moderators'];
$forum_moderators = ( count($moderators) ) ? implode(', ', $moderators) : $lang['None'];
unset($moderators);

//
// Generate a 'Show topics in previous x days' select box. If the topicsdays var is sent
// then get it's value, find the number of topics with dates newer than it (to properly
// handle pagination) and alter the main query
//
$previous_days = array(0, 1, 7, 14, 30, 91, 182, 364);
$previous_days_text = array($lang['All_Topics'], $lang['1_Day'], $lang['7_Days'], $lang['2_Weeks'], $lang['1_Month'], $lang['3_Months'], $lang['6_Months'], $lang['1_Year']);

if (!empty($_POST['topicdays']) || !empty($_GET['topicdays'])) {
	$topic_days = intval(empty($_POST['topicdays']) ? $_GET['topicdays'] : $_POST['topicdays']);
	$min_topic_time = gmtime() - ($topic_days * 86400);

	$sql = "SELECT COUNT(t.topic_id) AS forum_topics
			FROM ".TOPICS_TABLE." t, ".POSTS_TABLE." p
			WHERE t.forum_id = $forum_id
				AND p.post_id = t.topic_last_post_id
				AND p.post_time >= $min_topic_time";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result, SQL_ASSOC);
	$db->sql_freeresult($result);

	$topics_count = ( $row['forum_topics'] ) ? $row['forum_topics'] : 1;
	$limit_topics_time = "AND p.post_time >= $min_topic_time";

	if (!empty($_POST['topicdays'])) { $start = 0; }
} else {
	$topics_count = ($forum_row['forum_topics']) ? $forum_row['forum_topics'] : 1;
	$limit_topics_time = '';
	$topic_days = 0;
}

$select_topic_days = '<select name="topicdays">';
for($i = 0; $i < count($previous_days); $i++) {
	$selected = ($topic_days == $previous_days[$i]) ? ' selected="selected"' : '';
	$select_topic_days .= '<option value="'.$previous_days[$i].'"'.$selected.'>'.$previous_days_text[$i].'</option>';
}
$select_topic_days .= '</select>';


//
// All announcement data, this keeps announcements
// on each viewforum page ...
//
$sql = "SELECT t.*, u.username, u.user_id, u2.username as user2, u2.user_id as id2, p.post_time, p.post_username
		FROM ".TOPICS_TABLE." t, ".USERS_TABLE." u, ".POSTS_TABLE." p, ".USERS_TABLE." u2
		WHERE t.forum_id = $forum_id
			AND t.topic_poster = u.user_id AND p.post_id = t.topic_last_post_id
			AND p.poster_id = u2.user_id AND t.topic_type = ".POST_ANNOUNCE."
		ORDER BY t.topic_last_post_id DESC ";
$result = $db->sql_query($sql);

$topic_rowset = array();
$total_announcements = 0;
while( $row = $db->sql_fetchrow($result) ) {
	$topic_rowset[] = $row;
	$total_announcements++;
}
$db->sql_freeresult($result);

// TopicIcon_mod
$topic_icons = get_topic_icons($forum_id);

//
// Grab all the basic data (all topics except announcements)
// for this forum
//
$sql = "SELECT t.*, u.username, u.user_id, u2.username as user2, u2.user_id as id2, p.post_username, p2.post_username AS post_username2, p2.post_time
		FROM ".TOPICS_TABLE." t, ".USERS_TABLE." u, ".POSTS_TABLE." p, ".POSTS_TABLE." p2, ".USERS_TABLE." u2
		WHERE t.forum_id = $forum_id
			AND t.topic_poster = u.user_id
			AND p.post_id = t.topic_first_post_id
			AND p2.post_id = t.topic_last_post_id
			AND u2.user_id = p2.poster_id
			AND t.topic_type <> ".POST_ANNOUNCE."
			$limit_topics_time
		ORDER BY t.topic_type DESC, t.topic_last_post_id DESC
		LIMIT $start, ".$board_config['topics_per_page'];
$result = $db->sql_query($sql);
$total_topics = 0;
while( $row = $db->sql_fetchrow($result) ) {
	$topic_rowset[] = $row;
	$total_topics++;
}
$db->sql_freeresult($result);

//
// Total topics ...
//
$total_topics += $total_announcements;

//
// Define censored word matches
//
$orig_word = array();
$replacement_word = array();
obtain_word_list($orig_word, $replacement_word);

//
// User authorisation levels output
//
$s_auth_can	 = (($is_auth['auth_post']) ? $lang['Rules_post_can'] : $lang['Rules_post_cannot']).'<br />';
$s_auth_can .= (($is_auth['auth_reply']) ? $lang['Rules_reply_can'] : $lang['Rules_reply_cannot']).'<br />';
$s_auth_can .= (($is_auth['auth_edit']) ? $lang['Rules_edit_can'] : $lang['Rules_edit_cannot']).'<br />';
$s_auth_can .= (($is_auth['auth_delete']) ? $lang['Rules_delete_can'] : $lang['Rules_delete_cannot']).'<br />';
$s_auth_can .= (($is_auth['auth_vote']) ? $lang['Rules_vote_can'] : $lang['Rules_vote_cannot']).'<br />';
//if (defined('BBAttach_mod')) {
	attach_build_auth_levels($is_auth, $s_auth_can);

if ($is_auth['auth_mod']) {
	$s_auth_can .= sprintf($lang['Rules_moderate'], '<a href="' .getlink("&amp;file=modcp&amp;".POST_FORUM_URL."=$forum_id").'">', '</a>');
}

//
// Mozilla navigation bar
//
$nav_links['up'] = array(
	'url' => getlink(),
	'title' => sprintf($lang['Forum_Index'], $board_config['sitename'])
);

//
// Dump out the page header and load viewforum template
//
$page_title = !empty($forum_row['cat_title']) ? $forum_row['cat_title']: '';
$page_title .= !empty($forum_row['forum_name']) ? ' '._BC_DELIM.' '. $forum_row['forum_name'] : '';
require_once('includes/phpBB/page_header.php');

if ($forum_row['forum_type'] == 1) {
	require_once('includes/phpBB/functions_display.php');
	$forum_data = display_forums($forum_id);
	$template->assign_vars(array(
		'BC_DELIM' => _BC_DELIM,
		'L_FORUM' => $lang['Forum'],
		'L_TOPICS' => $lang['Topics'],
		'L_POSTS' => $lang['Posts'],
		'L_LAST_POST' => $lang['Last_Post'],
		'U_MARK_READ' => getlink("&amp;mark=forums"),
	));
	for($j = 0; $j < (is_countable($forum_data) ? count($forum_data) : 0); $j++) {
		$sub_forum_id = $forum_data[$j]['forum_id'];
		if ($forum_data[$j]['forum_type'] == 2) {
			$forumlink = getlink($forum_data[$j]['forum_link']);
		} else if ($forum_data[$j]['forum_type'] == 3) {
			$forumlink = $forum_data[$j]['forum_link'];
		} else {
			$forumlink = getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$sub_forum_id");
		}
		$template->assign_block_vars('forumrow', array(
			'S_IS_CAT'			 => false,
			'S_IS_LINK'			 => ($forum_data[$j]['forum_type'] >= 2),
			'LAST_POST_IMG'		 => $images['icon_latest_reply'],
			'FORUM_ID'			 => $sub_forum_id,
			'FORUM_FOLDER_IMG'	 => $forum_data[$j]['folder_image'],
			'FORUM_NAME'		 => $forum_data[$j]['forum_name'],
			'FORUM_DESC'		 => $forum_data[$j]['forum_desc'],
			'POSTS'				 => $forum_data[$j]['forum_posts'],
			'TOPICS'			 => $forum_data[$j]['forum_topics'],
			'LAST_POST_TIME'	 => ($forum_data[$j]['forum_last_post_id'] ) ? create_date($board_config['default_dateformat'], $forum_data[$j]['post_time']) : '',
			'LAST_POSTER'		 => ($forum_data[$j]['username']) ? $forum_data[$j]['username'] : $lang['Guest'],
			'MODERATORS'		 => $forum_data[$j]['moderator_list'],
//			'SUBFORUMS'		  => $subforums_list,

//			'L_SUBFORUM_STR'	 => $l_subforums,
			'L_MODERATOR_STR' => $forum_data[$j]['l_moderators'],
			'L_FORUM_FOLDER_ALT' => $forum_data[$j]['folder_alt'],
			'BC_DELIM'		  => _BC_DELIM,
			'U_LAST_POSTER'	  => ($forum_data[$j]['user_id'] > ANONYMOUS) ? getlink("Your_Account&amp;profile=".$forum_data[$j]['user_id']) : '',
			'U_LAST_POST'	  => ($forum_data[$j]['forum_last_post_id']) ? getlink("&amp;file=viewtopic&amp;" .POST_POST_URL.'='.$forum_data[$j]['forum_last_post_id']).'#'.$forum_data[$j]['forum_last_post_id'] : '',
			'U_VIEWFORUM'	  => $forumlink
			)
		);
	}
}

$template->assign_vars(array(
	'SUB_FORUMS' => ($forum_row['forum_type'] == 1),
	'FORUM_ID' => $forum_id,
	'FORUM_NAME' => $forum_row['forum_name'],
	'FORUM_DESC' => $forum_row['forum_desc'],
	'MODERATORS' => $forum_moderators,
	'POST_IMG' => ( $forum_row['forum_status'] == FORUM_LOCKED ) ? $images['post_locked'] : $images['post_new'],
	'BC_DELIM' => _BC_DELIM,
	'FOLDER_IMG' => $images['folder'],
	'FOLDER_NEW_IMG' => $images['folder_new'],
	'FOLDER_HOT_IMG' => $images['folder_hot'],
	'FOLDER_HOT_NEW_IMG' => $images['folder_hot_new'],
	'FOLDER_LOCKED_IMG' => $images['folder_locked'],
	'FOLDER_LOCKED_NEW_IMG' => $images['folder_locked_new'],
	'FOLDER_STICKY_IMG' => $images['folder_sticky'],
	'FOLDER_STICKY_NEW_IMG' => $images['folder_sticky_new'],
	'FOLDER_ANNOUNCE_IMG' => $images['folder_announce'],
	'FOLDER_ANNOUNCE_NEW_IMG' => $images['folder_announce_new'],

	'L_TOPICS' => $lang['Topics'],
	'L_REPLIES' => $lang['Replies'],
	'L_VIEWS' => $lang['Views'],
	'L_POSTS' => $lang['Posts'],
	'L_LASTPOST' => $lang['Last_Post'],
	'L_MODERATOR' => $l_moderators,
	'L_MARK_TOPICS_READ' => $lang['Mark_all_topics'],
	'L_POST_NEW_TOPIC' => ( $forum_row['forum_status'] == FORUM_LOCKED ) ? $lang['Forum_locked'] : $lang['Post_new_topic'],
	'L_NO_POSTS' => $lang['No_Posts'],
	'L_NO_NEW_POSTS' => $lang['No_new_posts'],
	'L_NEW_POSTS' => $lang['New_posts'],
	'L_NO_NEW_POSTS_LOCKED' => $lang['No_new_posts_locked'],
	'L_NEW_POSTS_LOCKED' => $lang['New_posts_locked'],
	'L_NO_NEW_POSTS_HOT' => $lang['No_new_posts_hot'],
	'L_NEW_POSTS_HOT' => $lang['New_posts_hot'],
	'L_ANNOUNCEMENT' => $lang['Post_Announcement'],
	'L_STICKY' => $lang['Post_Sticky'],
	'L_POSTED' => $lang['Posted'],
	'L_JOINED' => $lang['Joined'],
	'L_AUTHOR' => $lang['Author'],
	'L_DISPLAY_TOPICS' => $lang['Display_topics'],
	'L_GO' => $lang['Go'],

	'S_AUTH_LIST' => $s_auth_can,
	'S_SELECT_TOPIC_DAYS' => $select_topic_days,
	'S_POST_DAYS_ACTION' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=".$forum_id."&amp;start=$start"),

	'U_MARK_READ' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id&amp;mark=topics"),
	'U_POST_NEW_TOPIC' => getlink("&amp;file=posting&amp;mode=newtopic&amp;".POST_FORUM_URL."=$forum_id"),
	'U_VIEW_FORUM' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL ."=$forum_id")
	)
);
make_jumpbox('viewforum');
//
// End header
//

//
// Okay, lets dump out the page ...
//
if ($total_topics) {
	$announces = $stickies = $normalposts = false;
	for ($i = 0; $i < $total_topics; $i++) {
		$topic_id = $topic_rowset[$i]['topic_id'];

		$topic_title = (count($orig_word)) ? preg_replace($orig_word, $replacement_word, $topic_rowset[$i]['topic_title']) : $topic_rowset[$i]['topic_title'];

		$replies = $topic_rowset[$i]['topic_replies'];

		$topic_type = $topic_rowset[$i]['topic_type'];

// TopicIcon_mod
		//grab this topic's icon_id
		$topic_icon_id = $topic_rowset[$i]['icon_id'];
		$topic_icon_source = '<img width="20" src="images/spacer.gif" alt="" />';
		//if we have an icon
		if ($topic_icon_id != NULL && $topic_icon_id != 0) {
			//create the path
			$topic_icon_source = '<img src="'.$topic_icons[$topic_icon_id]['icon_url'].'" alt="'.$topic_icons[$topic_icon_id]['icon_name'].'" title="'.$topic_icons[$topic_icon_id]['icon_name'].'" align="middle" />';
		}
// TopicIcon_mod end
		if ($topic_type == POST_ANNOUNCE) {
			$topic_type = '';
			$topics_header = (!$announces) ? $lang['Post_Announcement'] : '';
			$announces = true;
		} else if ($topic_type == POST_STICKY) {
			$topic_type = '';
			$topics_header = (!$stickies) ? $lang['Post_Sticky'] : '';
			$stickies = true;
		} else {
			$topic_type = '';
			$topics_header = (!$normalposts) ? $lang['Post_Normal'] : '';
			$normalposts = true;
		}

		if ($topic_rowset[$i]['topic_vote']) {
			$topic_type .= $lang['Topic_Poll'].' ';
		}

		if ($topic_rowset[$i]['topic_status'] == TOPIC_MOVED) {
			$topic_type = $lang['Topic_Moved'].' ';
			$topic_id = $topic_rowset[$i]['topic_moved_id'];
			$folder_image =	 $images['folder'];
			$folder_alt = $lang['Topics_Moved'];
			$newest_post_img = '';
		} else {
			if ($topic_rowset[$i]['topic_type'] == POST_ANNOUNCE) {
				$folder = $images['folder_announce'];
				$folder_new = $images['folder_announce_new'];
			} else if ($topic_rowset[$i]['topic_type'] == POST_STICKY) {
				$folder = $images['folder_sticky'];
				$folder_new = $images['folder_sticky_new'];
			} else if ($topic_rowset[$i]['topic_status'] == TOPIC_LOCKED) {
				$folder = $images['folder_locked'];
				$folder_new = $images['folder_locked_new'];
			} else {
				if ($replies >= $board_config['hot_threshold']) {
					$folder = $images['folder_hot'];
					$folder_new = $images['folder_hot_new'];
				} else {
					$folder = $images['folder'];
					$folder_new = $images['folder_new'];
				}
			}

			$newest_post_img = '';
			if (is_user()) {
				if ($topic_rowset[$i]['post_time'] > $userdata['user_lastvisit']) {
					if (!empty($tracking_topics) || !empty($tracking_forums) || isset($CPG_SESS[$module_name]['track_all'])) {
						$unread_topics = true;
						if (!empty($tracking_topics[$topic_id])) {
							if ($tracking_topics[$topic_id] >= $topic_rowset[$i]['post_time']) {
								$unread_topics = false;
							}
						}
						if (!empty($tracking_forums[$forum_id])) {
							if ($tracking_forums[$forum_id] >= $topic_rowset[$i]['post_time']) {
								$unread_topics = false;
							}
						}
						if (isset($CPG_SESS[$module_name]['track_all'])) {
							if ($CPG_SESS[$module_name]['track_all'] >= $topic_rowset[$i]['post_time']) {
								$unread_topics = false;
							}
						}
						if ($unread_topics) {
							$folder_image = $folder_new;
							$folder_alt = $lang['New_posts'];
							$newest_post_img = '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;view=newest").'"><img src="'.$images['icon_newest_reply'].'" alt="'.$lang['View_newest_post'].'" title="'.$lang['View_newest_post'].'" /></a> ';
						} else {
							$folder_image = $folder;
							$folder_alt = ( $topic_rowset[$i]['topic_status'] == TOPIC_LOCKED ) ? $lang['Topic_locked'] : $lang['No_new_posts'];
							$newest_post_img = '';
						}
					} else {
						$folder_image = $folder_new;
						$folder_alt = ( $topic_rowset[$i]['topic_status'] == TOPIC_LOCKED ) ? $lang['Topic_locked'] : $lang['New_posts'];
						$newest_post_img = '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;view=newest").'"><img src="'.$images['icon_newest_reply'].'" alt="'.$lang['View_newest_post'].'" title="'.$lang['View_newest_post'].'" /></a> ';
					}
				} else {
					$folder_image = $folder;
					$folder_alt = ( $topic_rowset[$i]['topic_status'] == TOPIC_LOCKED ) ? $lang['Topic_locked'] : $lang['No_new_posts'];
					$newest_post_img = '';
				}
			} else {
				$folder_image = $folder;
				$folder_alt = ( $topic_rowset[$i]['topic_status'] == TOPIC_LOCKED ) ? $lang['Topic_locked'] : $lang['No_new_posts'];
				$newest_post_img = '';
			}
		}

		if (($replies+1) > $board_config['posts_per_page']) {
			$total_pages = ceil(($replies + 1) / $board_config['posts_per_page']);
			$goto_page = ' [ <img src="'.$images['icon_gotopost'].'" alt="'.$lang['Goto_page'].'" title="'.$lang['Goto_page'].'" />'.$lang['Goto_page'].': ';
			$times = 1;
			for ($j = 0; $j < $replies + 1; $j += $board_config['posts_per_page']) {
				$goto_page .= '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=".$topic_id."&amp;start=$j").'">'.$times.'</a>';
				if ($times == 1 && $total_pages > 4) {
					$goto_page .= ' ... ';
					$times = $total_pages - 3;
					$j += ($total_pages - 4) * $board_config['posts_per_page'];
				} else if ($times < $total_pages ) {
					$goto_page .= ', ';
				}
				$times++;
			}
			$goto_page .= ' ] ';
		} else {
			$goto_page = '';
		}

		$view_topic_url = getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id");

		$topic_author  = ($topic_rowset[$i]['user_id'] != ANONYMOUS) ? '<a href="'.getlink("Your_Account&amp;profile=".$topic_rowset[$i]['user_id']).'">' : '';
		$topic_author .= ($topic_rowset[$i]['user_id'] != ANONYMOUS) ? $topic_rowset[$i]['username'] : (($topic_rowset[$i]['post_username'] != '') ? $topic_rowset[$i]['post_username'] : $lang['Guest']);
		$topic_author .= ($topic_rowset[$i]['user_id'] != ANONYMOUS) ? '</a>' : '';

		$row_color = (!($i % 2)) ? $bgcolor2 : $bgcolor1;
		$row_class = (!($i % 2)) ? 'row1' : 'row2';

		$template->assign_block_vars('topicrow', array(
			'L_HEADER' => $topics_header,
			'ROW_COLOR' => $row_color,
			'ROW_CLASS' => $row_class,
			'FORUM_ID' => $forum_id,
			'TOPIC_ID' => $topic_id,
			'TOPIC_FOLDER_IMG' => $folder_image,
			'TOPIC_AUTHOR' => $topic_author,
			'GOTO_PAGE' => $goto_page,
			'REPLIES' => $replies,
			'NEWEST_POST_IMG' => $newest_post_img,
// BBAttach_mod
			'TOPIC_ATTACHMENT_IMG' => topic_attachment_image($topic_rowset[$i]['topic_attachment']),
			'TOPIC_TITLE' => $topic_title,
			'TOPIC_TYPE' => $topic_type,
			'VIEWS' => $topic_rowset[$i]['topic_views'],
			'FIRST_POST_TIME' => create_date($board_config['default_dateformat'], $topic_rowset[$i]['topic_time']),
			'LAST_POST_TIME' => create_date($board_config['default_dateformat'], $topic_rowset[$i]['post_time']),
			'LAST_POST_AUTHOR' => ($topic_rowset[$i]['id2'] == ANONYMOUS) ? (($topic_rowset[$i]['post_username2'] != '') ? $topic_rowset[$i]['post_username2'].' ' : $lang['Guest'].' ' ) : '<a href="'.getlink("Your_Account&amp;profile=".$topic_rowset[$i]['id2']).'">'.$topic_rowset[$i]['user2'].'</a>',
			'LAST_POST_IMG' => '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_POST_URL.'='.$topic_rowset[$i]['topic_last_post_id']).'#'.$topic_rowset[$i]['topic_last_post_id'].'"><img src="'.$images['icon_latest_reply'].'" alt="'.$lang['View_latest_post'].'" title="'.$lang['View_latest_post'].'" /></a>',

			'L_TOPIC_FOLDER_ALT' => $folder_alt,
// TopicIcon_mod
			'TOPIC_ICON' => $topic_icon_source,

			'U_VIEW_TOPIC' => $view_topic_url)
		);
	}

	if ($topics_count > $total_announcements) { $topics_count -= $total_announcements; }

	$template->assign_vars(array(
		'PAGINATION' => generate_pagination("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id&amp;topicdays=$topic_days", $topics_count, $board_config['topics_per_page'], $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $topics_count / $board_config['topics_per_page'] )),
		'L_GOTO_PAGE' => $lang['Goto_page'])
	);
} else {
	//
	// No topics
	//
	$template->assign_vars(array(
		'L_NO_TOPICS' => ( $forum_row['forum_status'] == FORUM_LOCKED ) ? $lang['Forum_locked'] : $lang['No_topics_post_one'])
	);
	$template->assign_block_vars('switch_no_topics', array() );
}

//
// Parse the page and print
//
$template->set_filenames(array('body' => 'forums/viewforum_body.html'));

//
// Page footer
//
require_once('includes/phpBB/page_tail.php');
