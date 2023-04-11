<?php
if (!defined('CPG_NUKE') || !defined('IN_PHPBB')) { exit; }

# Okay, let's build the v9 index
$i = 0;
$OUT->forumrow = array();
foreach ($categories as &$category) {
	$cat_id = $category['id'];
	$cat_display = false;
	foreach ($forums as &$forum) {
		if ($forum['cat_id'] == $cat_id) {
			if (!$cat_display) {
				$OUT->forumrow[] = array(
					'S_IS_CAT'    => true,
					'CAT_ID'      => $cat_id,
					'CAT_DESC'    => $category['title'],
					'S_NOT_FIRST' => !$i++,
					'U_VIEWCAT'   => URL::index("&c={$cat_id}"),
					'S_BID'       => 1000 + $cat_id,
					'S_VISIBLE'   => true,
				);
				$cat_display = true;
			}

			$total_posts  += $forum['forum_posts'];
			$total_topics += $forum['forum_topics'];
			$total_posta  += $forum['archive_posts'];
			$total_topica += $forum['archive_topics'];
			if (!$viewcat || $viewcat == $cat_id) {
				$forum_id = $forum['forum_id'];
				if ($forum['forum_type'] == 2) {
					$forumlink = URL::index($forum['forum_link']);
				} else if ($forum['forum_type'] == 3) {
					$forumlink = $forum['forum_link'];
				} else {
					$forumlink = URL::index("&file=viewforum&f={$forum_id}");
				}
				$OUT->forumrow[] = array(
					'S_IS_CAT'           => false,
					'S_IS_LINK'          => ($forum['forum_type'] >= 2),
					'LAST_POST_IMG'      => $OUT->board_images['icon_latest_reply'],
					'FORUM_ID'           => $forum_id,
					'FORUM_FOLDER_IMG'   => $forum['folder_image'],
					'FORUM_NAME'         => $forum['forum_name'],
					'FORUM_DESC'         => $forum['forum_desc'],
					'POSTS'              => $forum['forum_posts'],
					'TOPICS'             => $forum['forum_topics'],
					'POSTS_ARCHIVED'     => $forum['archive_posts'],
					'TOPICS_ARCHIVED'    => $forum['archive_topics'],
					'LAST_POST_TIME'     => ($forum['forum_last_post_id'] ) ? $lang->date($board_config['default_dateformat'], $forum['post_time']) : '',
					'LAST_POSTER'        => ($forum['username'] != 'Anonymous') ? $forum['username'] : $forum['post_username'],
					'MODERATORS'         => $forum['moderator_list'],
					'SUB_FORUMS'         => ($forum['forum_type'] == 1),
					'SUBFORUMS'          => $forum['subforums_list'],
					'L_SUBFORUM_STR'     => $forum['subforums_lang'],
					'L_MODERATOR_STR'    => $forum['l_moderators'],
					'L_FORUM_FOLDER_ALT' => $forum['folder_alt'],
					'U_LAST_POSTER'      => ($forum['user_id'] > \Dragonfly\Identity::ANONYMOUS_ID) ? Dragonfly\Identity::getProfileURL($forum['user_id']) : '',
					'U_LAST_POST'        => ($forum['forum_last_post_id']) ? URL::index("&file=viewtopic&p={$forum['forum_last_post_id']}") . '#' . $forum['forum_last_post_id'] : '',
					'U_VIEWFORUM'        => $forumlink,
					'U_VIEWARCHIVE'      => !empty($forum['archive_topics']) ? URL::index("&file=viewarchive&f={$forum_id}") : '',
					'U_NOFOLLOW'         => '',
				);
			}
		}
	}
}

$OUT->assign_vars(array(
	'FORUM_IMG'              => $OUT->board_images['forum'],
	'FORUM_NEW_IMG'          => $OUT->board_images['forum_new'],
	'FORUM_LOCKED_IMG'       => $OUT->board_images['forum_locked'],
	'FORUM_SUB_IMG'          => $OUT->board_images['forum_sub'],
	'FORUM_NEW_SUB_IMG'      => $OUT->board_images['forum_new_sub'],
	'FORUM_LOCKED_SUB_IMG'   => $OUT->board_images['forum_locked_sub'],

//	'L_ONLINE_EXPLAIN'       => $lang['Online_explain'],
	'U_ARCHIVES'             => URL::index('&file=archives'),
	'S_ADMINCOLOR'           => $board_config['admin_color'],
	'S_MODCOLOR'             => $board_config['moderator_color'],
	'S_MEMCOLOR'             => $board_config['member_color'],
	'L_ARCHIVES'             => $lang['Archives'],
	'L_FORUM'                => $lang['Forum'],
	'L_TOPICS'               => $lang['Topics'],
	'L_REPLIES'              => $lang['Replies'],
	'L_VIEWS'                => $lang['Views'],
	'L_POSTS'                => $lang['Posts'],
	'L_LAST_POST'            => $lang['Last_Post'],
	'L_NO_POSTS'             => $lang['No_Posts'],
	'L_NO_NEW_POSTS'         => $lang['No_new_posts'],
	'L_NO_NEW_SUB_POSTS'     => $lang['No_new_sub_posts'],
	'L_NEW_POSTS'            => $lang['New_posts'],
	'L_NEW_SUB_POSTS'        => $lang['New_sub_posts'],
	'L_NO_NEW_POSTS_LOCKED'  => $lang['No_new_posts_locked'],
	'L_NEW_POSTS_LOCKED'     => $lang['New_posts_locked'],
	'L_MODERATOR'            => $lang['Moderator'],
	'L_ADMINISTRATOR'        => $lang['Auth_Admin'],
	'L_FORUM_LOCKED'         => $lang['Forum_is_locked'],
	'L_SUBFORUM_LOCKED'      => $lang['Subforum_is_locked'],
	'L_MARK_FORUMS_READ'     => $lang['Mark_all_forums'],
	'L_WHO_IS_ONLINE'        => $lang['Who_is_Online'],
	'L_WHO_ONLINE_NOW'       => $lang['Who_online_now'],
	'L_WHO_ONLINE_TODAY'     => $lang['Who_online_today'],
	'L_LEGEND'               => 'Legend',
	'U_MARK_READ'            => URL::index('&mark=forums')
));

# Generate the page
$OUT->set_handle('body', 'forums/index_body.html');

