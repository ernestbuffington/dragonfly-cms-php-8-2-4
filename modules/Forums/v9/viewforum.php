<?php
if (!defined('CPG_NUKE') || !defined('IN_PHPBB')) { exit; }

if (!empty($template->forumrow)) {
	foreach ($template->forumrow as $i => $subforum) {
		$template->forumrow[$i] = array(
			'S_IS_CAT'           => false,
			'S_IS_LINK'          => $subforum['IS_LINK'],
			'LAST_POST_IMG'      => $images['icon_latest_reply'],
			'FORUM_ID'           => $subforum['forum_id'],
			'FORUM_FOLDER_IMG'   => $subforum['folder_image'],
			'FORUM_NAME'         => $subforum['forum_name'],
			'FORUM_DESC'         => $subforum['forum_desc'],
			'POSTS'              => $subforum['forum_posts'],
			'TOPICS'             => $subforum['forum_topics'],
			'POSTS_ARCHIVED'     => $subforum['archive_posts'],
			'TOPICS_ARCHIVED'    => $subforum['archive_topics'],
			'LAST_POST_TIME'     => $subforum['forum_last_post_id'] ? $lang->date($board_config['default_dateformat'], $subforum['post_time']) : '',
			'LAST_POSTER'        => $subforum['username'] ?: $lang['Guest'],
			'MODERATORS'         => $subforum['moderator_list'],
			'SUBFORUMS'          => $subforum['subforums_list'],
			'SUB_FORUMS'         => $subforum['SUB_FORUMS'],
			'L_SUBFORUM_STR'     => $subforum['subforums_lang'],
			'L_MODERATOR_STR'    => $subforum['l_moderators'],
			'L_FORUM_FOLDER_ALT' => $subforum['folder_alt'],
			'U_VIEWFORUM'        => $subforum['U_VIEWFORUM'],
			'U_VIEWARCHIVE'      => $subforum['U_VIEWARCHIVE'],
			'U_LAST_POSTER'      => ($subforum['user_id'] > \Dragonfly\Identity::ANONYMOUS_ID) ? \Dragonfly\Identity::getProfileURL($subforum['user_id']) : '',
			'U_LAST_POST'        => $subforum['forum_last_post_id'] ? URL::index('&file=viewtopic&p='.$subforum['forum_last_post_id']).'#'.$subforum['forum_last_post_id'] : '',
		);
	}
} else {
	$template->assign_block_vars('switch_no_topics', array() );
}

if (!empty($template->forum_topics)) {
	foreach ($template->forum_topics as $topic) {
		$goto_page = $topic['goto_page'];
		if ($goto_page) {
			foreach ($goto_page as $i => $page) {
				$goto_page[$i] = '<a href="'.htmlspecialchars($page['uri']).'">'.$page['no'].'</a>';
			}
			$goto_page = ' [ … , ' . implode(', ', $goto_page) . ' ] ';
		} else {
			$goto_page = '';
		}

		$template->assign_block_vars('topicrow', array(
			'L_HEADER' => $topic['L_HEADER'],
			'TOPIC_FOLDER_IMG' => $topic['image']['uri'],
			'TOPIC_AUTHOR' => $topic['author']['uri']
				? '<a href="'.htmlspecialchars($topic['author']['uri']).'">' . $topic['author']['name'] . '</a>'
				: $topic['author']['name'],
			'GOTO_PAGE' => $goto_page,
			'REPLIES' => $topic['topic_replies'],
			'NEWEST_POST_IMG' => $topic['U_NEWEST_POST']
				? '<a href="'.htmlspecialchars($topic['U_NEWEST_POST']).'"><img src="'.DF_STATIC_DOMAIN.$images['icon_newest_reply'].'" alt="'.$lang['View_newest_post'].'" title="'.$lang['View_newest_post'].'" /></a> '
				: '',
			'TOPIC_ATTACHMENT_IMG' => $topic['attachment_img'] ? '<img src="' . $topic['attachment_img'] . '" alt="" /> ' : '',
			'TOPIC_TITLE' => htmlspecialchars($topic['topic_title'], ENT_NOQUOTES),
			'TOPIC_TYPE' => $topic['TOPIC_TYPE'],
			'VIEWS' => $topic['topic_views'],
			'FIRST_POST_TIME' => $lang->date($board_config['default_dateformat'], $topic['topic_time']),
			'LAST_POST_TIME' => $lang->date($board_config['default_dateformat'], $topic['post_time']),
			'LAST_POSTER' => $topic['last_poster']['name'],
			'U_LAST_POSTER' => $topic['last_poster']['uri'],
			'LAST_POST_AUTHOR' => $topic['last_poster']['uri']
				? '<a href="'.htmlspecialchars($topic['last_poster']['uri']).'">'.$topic['last_poster']['name'].'</a>'
				: $topic['last_poster']['name'],
			'LAST_POST_IMG' => '<a href="'.htmlspecialchars($topic['U_LAST_POST']).'"><img src="'.DF_STATIC_DOMAIN.$images['icon_latest_reply'].'" alt="'.$lang['View_latest_post'].'" title="'.$lang['View_latest_post'].'" /></a>',
			'L_TOPIC_FOLDER_ALT' => $topic['image']['name'],
			'TOPIC_ICON' => $topic['icon'] ? '<img src="'.htmlspecialchars($topic['icon']['uri']).'" alt="'.htmlspecialchars($topic['icon']['name']).'" title="'.htmlspecialchars($topic['icon']['name']).'"/>' : '',
			'U_VIEW_TOPIC' => $topic['U_VIEW_TOPIC'],
		));
	}
}
unset($template->forum_topics);

foreach ($template->SUB_PARENTS as $i => $pforum) {
	$template->SUB_PARENTS[$i] = '<a href="' . htmlspecialchars($pforum['uri']) . '">' . htmlspecialchars($pforum['name']) . '</a>';
}
$template->SUB_PARENTS = implode(' '._BC_DELIM.' ', $template->SUB_PARENTS);

// User authorisation levels output
$s_auth_can = array();
$s_auth_can[] = (($is_auth['auth_post']) ? $lang['Rules_post_can'] : $lang['Rules_post_cannot']);
$s_auth_can[] = (($is_auth['auth_reply']) ? $lang['Rules_reply_can'] : $lang['Rules_reply_cannot']);
$s_auth_can[] = (($is_auth['auth_edit']) ? $lang['Rules_edit_can'] : $lang['Rules_edit_cannot']);
$s_auth_can[] = (($is_auth['auth_delete']) ? $lang['Rules_delete_can'] : $lang['Rules_delete_cannot']);
$s_auth_can[] = (($is_auth['auth_vote']) ? $lang['Rules_vote_can'] : $lang['Rules_vote_cannot']);
if (!$attach_config['disable_mod']) {
	# If you want to have the rules window link within the forum view too, comment out the two lines, and comment the third line
//	$rules_link = '(<a href="'.URL::index("&file=attach_rules&f={$forum_id}").'" target="_blank">Rules</a>)';
//	$s_auth_can[] = (($is_auth['auth_attachments']) ? $rules_link . ' ' . $lang['Rules_attach_can'] : $lang['Rules_attach_cannot']);
	$s_auth_can[] = (($is_auth['auth_attachments'] && $is_auth['auth_post']) ? $lang['Rules_attach_can'] : $lang['Rules_attach_cannot']);
	$s_auth_can[] = (($is_auth['auth_download']) ? $lang['Rules_download_can'] : $lang['Rules_download_cannot']);
}
if ($is_auth['auth_mod']) {
	$s_auth_can[] = sprintf($lang['Rules_moderate'], '<a href="'.htmlspecialchars(URL::index("&file=modcp&f={$forum_id}")).'">', '</a>');
}

$select_topic_days = '<select name="topicdays">';
foreach ($previous_days as $v) {
	$selected = $v['selected'] ? ' selected="selected"' : '';
	$select_topic_days .= '<option value="'.$v['value'].'"'.$selected.'>'.$v['label'].'</option>';
}
$select_topic_days .= '</select>';

$template->assign_vars(array(
	'FORUM_ID' => $forum_id,
	'FORUM_NAME' => $forum['forum_name'],
	'FORUM_DESC' => $forum['forum_desc'],
	'VIEW_ARCHIVES' => $forum['archive_topics'] > 0,
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
	'L_FORUM' => $lang['Forum'],
	'L_LAST_POST' => $lang['Last_Post'],
	'L_VIEW_ARCHIVES' => $lang['View_Archives'],
	'L_TOPICS' => $lang['Topics'],
	'L_REPLIES' => $lang['Replies'],
	'L_VIEWS' => $lang['Views'],
	'L_POSTS' => $lang['Posts'],
	'L_LASTPOST' => $lang['Last_Post'],
	'L_MARK_TOPICS_READ' => $lang['Mark_all_topics'],
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
	'L_FORUM_WATCH' => $lang['Start_watching_forum'],
	'L_FORUM_UNWATCH' => $lang['Stop_watching_forum'],
	'L_NO_TOPICS' => $forum->isLocked() ? $lang['Forum_locked'] : $lang['No_topics_post_one'],
	'L_POST_NEW_TOPIC' => $forum->isLocked() ? $lang['Forum_locked'] : $lang['Post_new_topic'],
	'POST_IMG' => $forum->isLocked() ? $images['post_locked'] : $images['post_new'],
	'IS_SUBFORUM' => $forum->isSubForum(),
	'U_POST_NEW_TOPIC' => URL::index("&file=posting&mode=newtopic&f={$forum_id}"),
	'U_VIEW_FORUM' => URL::index("&file=viewforum&f={$forum_id}"),
	'S_SELECT_TOPIC_DAYS' => $select_topic_days,
	'S_AUTH_LIST' => implode('<br/>', $s_auth_can),
));

$template->set_handle('body', 'forums/viewforum_body.html');
