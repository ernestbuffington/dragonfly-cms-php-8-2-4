<?php

/* Applied rules:
 * CountOnNullRector (https://3v4l.org/Bndc9)
 * ParenthesizeNestedTernaryRector (https://www.php.net/manual/en/migration74.deprecated.php)
 */

if (!defined('CPG_NUKE') || !defined('IN_PHPBB')) { exit; }

$template->S_HAS_POLL = !empty($template->topic_poll);
if ($template->S_HAS_POLL) {
	if ($template->S_POLL_RESULTS) {
		$vote_graphic = 0;
		$vote_graphic_max = is_countable($images['voting_graphic']) ? count($images['voting_graphic']) : 0;
		$vote_graphic_length = empty($board_config['vote_graphic_length']) ? 1 : $board_config['vote_graphic_length'];
		$votes_max = $template->topic_poll->votes_max;
		foreach ($template->topic_poll->options as $option) {
			$vote_graphic_img = $images['voting_graphic'][$vote_graphic++];
			$vote_graphic = ($vote_graphic < $vote_graphic_max) ? $vote_graphic : 0;
			$template->assign_block_vars('poll_option', array(
				'POLL_OPTION_CAPTION' => $option['text'],
				'POLL_OPTION_RESULT' => $option['votes'],
				'POLL_OPTION_PERCENT' => $option['percentage'],
				'POLL_OPTION_RESULT_MAX' => $votes_max,
				'POLL_OPTION_IMG' => $vote_graphic_img,
				'POLL_OPTION_IMG_WIDTH' => round($option['percentage'] / 100 * max(1, $vote_graphic_length))
			));
		}
		$template->TOTAL_VOTES = $template->topic_poll->votes;
	} else {
		foreach ($template->topic_poll->options as $option) {
			$template->assign_block_vars('poll_option', array(
				'POLL_OPTION_ID' => $option['id'],
				'POLL_OPTION_CAPTION' => $option['text']
			));
		}
	}
	$template->assign_vars(array(
		'POLL_QUESTION' => $template->topic_poll->title,
		'S_HIDDEN_FIELDS' => '',
	));
}

$topic_mod = '';
if ($is_auth['auth_mod']) {
	$topic_mod .= '<a href="'.htmlspecialchars(URL::index("&file=modcp&t={$topic_id}&mode=archive")).'"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_archive'].'" alt="'.$lang['Archive_topic'].'" title="'.$lang['Archive_topic'].'"/></a> ';
	$topic_mod .= '<a href="'.htmlspecialchars(URL::index("&file=modcp&t={$topic_id}&mode=delete")).'"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_delete'].'" alt="'.$lang['Delete_topic'].'" title="'.$lang['Delete_topic'].'"/></a> ';
	$topic_mod .= '<a href="'.htmlspecialchars(URL::index("&file=modcp&t={$topic_id}&mode=move")). '"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_move'].'" alt="'.$lang['Move_topic'].'" title="'.$lang['Move_topic'].'"/></a> ';
	$topic_mod .= $topic->isLocked()
		? '<a href="'.htmlspecialchars(URL::index("&file=modcp&t={$topic_id}&mode=unlock")).'"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_unlock'].'" alt="'.$lang['Unlock_topic'].'" title="'.$lang['Unlock_topic'].'"/></a> ;'
		: '<a href="'.htmlspecialchars(URL::index("&file=modcp&t={$topic_id}&mode=lock")).'"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_lock'].'" alt="'.$lang['Lock_topic'].'" title="'.$lang['Lock_topic'].'"/></a> ';
	$topic_mod .= '<a href="'.htmlspecialchars(URL::index("&file=modcp&t={$topic_id}&mode=split")).'"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_split'].'" alt="'.$lang['Split_topic'].'" title="'.$lang['Split_topic'].'"/></a> ';
	$topic_mod .= '<a href="'.htmlspecialchars(URL::index("&file=merge&t={$topic_id}")).'"><img src="'.DF_STATIC_DOMAIN.$images['topic_mod_merge'].'" alt="'.$lang['Merge_topics'].'" title="'.$lang['Merge_topics'].'"/></a> ';
}

# Topic watch information
$s_watching_topic = $s_watching_topic_img ='';
if ($can_watch_topic) {
	if ($is_watching_topic) {
		$s_watching_topic = '<a href="'.htmlspecialchars(URL::index("{$canonical_q}&unwatch")).'">'.$lang['Stop_watching_topic'].'</a>';
	} else {
		$s_watching_topic = '<a href="'.htmlspecialchars(URL::index("{$canonical_q}&watch")).'">'.$lang['Start_watching_topic'].'</a>';
	}
}

$previous_days = array(
	0   => $lang['All_Posts'],
	1   => $lang->timeReadable(86400, '%d'),
	7   => $lang->timeReadable(604800, '%d'),
	14  => $lang->timeReadable(604800*2, '%w'),
	30  => $lang->timeReadable(2628000, '%m'),
	91  => $lang->timeReadable(2628000*3, '%m'),
	183 => $lang->timeReadable(2628000*6, '%m'),
	365 => $lang->timeReadable(31536000, '%y'),
);
$select_post_days = '<select name="postdays">';
foreach ($previous_days as $k => $v) {
	$selected = ($post_days == $k) ? ' selected="selected"' : '';
	$select_post_days .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
}
$select_post_days .= '</select>';

foreach ($template->postrow as $i => $row) {
	$row['POSTER_AVATAR'] = '<img src="'.$row['POSTER_AVATAR_URI'].'" alt=""/>';
	$row['POSTER_BIO'] .= '<br/>';
	$row['POSTER_OCC'] .= '<br/>';

	$row['SEARCH_IMG'] = '<a href="'.htmlspecialchars($row['search_uri']).'"><img src="'.DF_STATIC_DOMAIN.$images['icon_search'].'" alt="'.$lang['Search_user_posts'].'" title="'.$lang['Search_user_posts'].'"/></a>';
	$row['SEARCH']     = '<a href="'.htmlspecialchars($row['search_uri']).'">'.$lang['Search_user_posts'].'</a>';

	$temp_url = $row['quote_uri'] ? htmlspecialchars($row['quote_uri']) : '';
	$row['QUOTE_IMG'] = $temp_url ? '<a href="'.$temp_url.'" rel="nofollow"><img src="'.DF_STATIC_DOMAIN.$images['icon_quote'].'" alt="'.$lang['Reply_with_quote'].'" title="'.$lang['Reply_with_quote'].'"/></a>' : '';
	$row['QUOTE']     = $temp_url ? '<a href="'.$temp_url.'" rel="nofollow">'.$lang['Reply_with_quote'].'</a>' : '';

	$temp_url = $row['edit_uri'] ? htmlspecialchars($row['edit_uri']) : '';
	$row['EDIT_IMG'] = $temp_url ? '<a href="'.$temp_url.'"><img src="'.DF_STATIC_DOMAIN.$images['icon_edit'].'" alt="'.$lang['Edit_delete_post'].'" title="'.$lang['Edit_delete_post'].'"/></a>' : '';
	$row['EDIT']     = $temp_url ? '<a href="'.$temp_url.'">'.$lang['Edit_delete_post'].'</a>' : '';

	$temp_url = $row['delete_uri'] ? htmlspecialchars($row['delete_uri']) : '';
	$row['DELETE_IMG'] = $temp_url ? '<a href="'.$temp_url.'"><img src="'.DF_STATIC_DOMAIN.$images['icon_delpost'].'" alt="'.$lang['Delete_post'].'" title="'.$lang['Delete_post'].'"/></a>' : '';
	$row['DELETE']     = $temp_url ? '<a href="'.$temp_url.'">'.$lang['Delete_post'].'</a>' : '';

	$temp_url = $row['view_ip_uri'] ? htmlspecialchars($row['view_ip_uri']) : '';
	$row['IP_IMG'] = $temp_url ? '<a href="'.$temp_url.'"><img src="'.DF_STATIC_DOMAIN.$images['icon_ip'].'" alt="'.$lang['View_IP'].'" title="'.$lang['View_IP'].'"/></a>' : '';
	$row['IP']     = $temp_url ? '<a href="'.$temp_url.'">'.$lang['View_IP'].'</a>' : '';

	if ($row['RANK_IMAGE']) {
		$row['RANK_IMAGE'] = '<img src="'.$row['RANK_IMAGE'].'" alt="'.$row['POSTER_RANK'].'" title="'.$row['POSTER_RANK'].'"/>';
	}

	if ($row['SIGNATURE']) {
		$row['SIGNATURE'] = '<br/>_________________<br/>' . $row['SIGNATURE'];
	}

	if ($row['EDITED_MESSAGE']) {
		$row['EDITED_MESSAGE'] = '<br/><br/>' . $row['EDITED_MESSAGE'];
	}

	foreach (array('profile', 'pm', 'email', 'www', 'icq', 'aim', 'msn', 'yim', 'skype', 'gal') as $k) {
		$p = strtoupper($k);
		$user_details = $row['user_details'];
		if (isset($user_details[$k])) {
			$row["{$p}_IMG"] = '<a href="'.htmlspecialchars($user_details[$k]['URL']).'"><img src="'.DF_STATIC_DOMAIN.$user_details[$k]['IMG'].'" alt="'.$user_details[$k]['TITLE'].'" title="'.$user_details[$k]['TITLE'].'"/></a>';
			$row[$p]         = '<a href="'.htmlspecialchars($user_details[$k]['URL']).'">'.$user_details[$k]['TITLE'].'</a>';
		} else {
			$row[$p] = $row["{$p}_IMG"] = '';
		}
	}

	$icq_status_img = $online_img = '';
	if ($row['poster_id'] != \Dragonfly\Identity::ANONYMOUS_ID) {
		if ($board_config['allow_online_posts']) {
			$online_img = $is_online
				? '<img src="'.DF_STATIC_DOMAIN.$images['icon_online'].'" alt="'.$lang['Online'].'" title="'.$lang['Online'].'"/> '.$lang['Online']
				: '<img src="'.DF_STATIC_DOMAIN.$images['icon_offline'].'" alt="'.$lang['Offline'].'" title="'.$lang['Offline'].'"/> '.$lang['Offline'];
		}
		if (!empty($post['user_icq'])) {
			$icq_status_img = '<a href="http://www.icq.com/people/'.$post['user_icq'].'"><img src="http://web.icq.com/whitepages/online?icq='.$post['user_icq'].'&img=5" style="width:18px; height:18px;"/></a>';
		}
	}

	$template->postrow[$i] = array_merge($row, array(
		'S_CLOAK' => false,
		'U_CLOAK' => false,
		'U_CLOAK_STOP' => false,
		'POSTER_TZ' => '',
		'ONLINE_IMG' => $online_img,
		'ICQ_STATUS_IMG' => $icq_status_img,
		'MINI_POST_IMG' => $images[$is_new_post ? 'icon_minipost_new' : 'icon_minipost'],
		'L_MINI_POST_ALT' => $lang[$is_new_post ? 'New_post' : 'Post'],
	));
}

foreach ($template->SF_PARENTS as $i => $pforum) {
	$template->SF_PARENTS[$i] = '<a href="' . htmlspecialchars($pforum['uri']) . '">' . htmlspecialchars($pforum['name']) . '</a>';
}
$template->SF_PARENTS = implode(' '._BC_DELIM.' ', $template->SF_PARENTS);

# User authorisation levels output
$s_auth_can = array(
	$is_auth['auth_post'] ? $lang['Rules_post_can'] : $lang['Rules_post_cannot'],
	$is_auth['auth_reply'] ? $lang['Rules_reply_can'] : $lang['Rules_reply_cannot'],
	$is_auth['auth_edit'] ? $lang['Rules_edit_can'] : $lang['Rules_edit_cannot'],
	$is_auth['auth_delete'] ? $lang['Rules_delete_can'] : $lang['Rules_delete_cannot'],
	$is_auth['auth_vote'] ? $lang['Rules_vote_can'] : $lang['Rules_vote_cannot'],
);
if (!$attach_config['disable_mod']) {
	$s_auth_can[] = ($is_auth['auth_attachments'] && $is_auth['auth_post']) ? $lang['Rules_attach_can'] : $lang['Rules_attach_cannot'];
	$s_auth_can[] = $is_auth['auth_download'] ? $lang['Rules_download_can'] : $lang['Rules_download_cannot'];
}
if ($is_auth['auth_mod']) {
	$s_auth_can[] = sprintf($lang['Rules_moderate'], '<a href="'.htmlspecialchars(URL::index("&file=modcp&f={$forum->id}")).'">', '</a>');
}

$template->assign_vars(array(
	'START_REL' => ($start + 1),
	'FORUM_ID' => $forum->id,
	'FORUM_NAME' => $forum->name,
	'FORUM_DESC' => $forum->desc,
	'TOPIC_ID' => $topic_id,
	'TOPIC_TITLE' => htmlspecialchars($topic_title),
	'L_AUTHOR' => $lang['Author'],
	'L_USERNAME' => $lang['Username'],
	'L_MESSAGE' => $lang['Message'],
	'L_POSTED' => $lang['Posted'],
	'L_POST_SUBJECT' => $lang['Post_subject'],
	'L_VIEW_NEXT_TOPIC' => $lang['View_next_topic'],
	'L_VIEW_PREVIOUS_TOPIC' => $lang['View_previous_topic'],
	'L_TOPIC_ARCHIVED' => $archived ? $lang['Topic_Archived'] : '',
	'L_BACK_TO_TOP_LINK' => $_SERVER['REQUEST_URI'],
	'L_BACK_TO_TOP' => $lang['Back_to_top'],
	'L_DISPLAY_POSTS' => $lang['Display_posts'],
	'L_LOCK_TOPIC' => $lang['Lock_topic'],
	'L_UNLOCK_TOPIC' => $lang['Unlock_topic'],
	'L_MOVE_TOPIC' => $lang['Move_topic'],
	'L_SPLIT_TOPIC' => $lang['Split_topic'],
	'L_DELETE_TOPIC' => $lang['Delete_topic'],
	'L_ARCHIVED' => $lang['Archived'],
	'L_ARCHIVES' => $lang['Archives'],
	'L_ATTACHMENT' => $lang['Attachment'],
	'L_POLL' => $lang['Poll'],
	'L_DESCRIPTION' => $lang['Description'],
	'L_DOWNLOAD' => $lang['Download'],
	'L_FILENAME' => $lang['File_name'],
	'L_FILESIZE' => $lang['Filesize'],
	'L_SUBMIT_VOTE' => $lang['Submit_vote'],
	'L_TOTAL_VOTES' => $lang['Total_votes'],
	'L_VIEW_RESULTS' => $lang['View_results'],
	'L_POSTED_ATTACHMENTS' => $lang['Posted_attachments'],
	'L_KILOBYTE' => $lang['KB'],
	'U_POST_NEW_TOPIC' => $forum->getNewTopicUri(),
	'L_POST_NEW_TOPIC' => $forum->isLocked() ? $lang['Forum_locked'] : $lang['Post_new_topic'],
	'POST_IMG'         => $forum->isLocked() ? $images['post_locked'] : $images['post_new'],
	'U_POST_REPLY_TOPIC' => $topic->getReplyUri(),
	'L_POST_REPLY_TOPIC' => ($forum->isLocked() || $topic->isLocked()) ? $lang['Topic_locked'] : ($archived ? ($archive_revived ? '' : $lang['Revive_topic']) : $lang['Reply_to_topic']),
	'REPLY_IMG'          => ($forum->isLocked() || $topic->isLocked()) ? $images['reply_locked'] : ($archived ? ($archive_revived ? '' : $images['revive_topic']) : $images['reply_new']),
	'S_AUTH_LIST' => implode('<br/>', $s_auth_can),
	'S_POST_DAYS_ACTION'=> URL::index($canonical_q),
	'S_TOPIC_ADMIN' => $topic_mod,
	'S_TOPIC_LINK' => 't',
	'S_ARCHIVED' => $archived,
	'S_REVIVED' => $archive_revived,
	'S_WATCH_TOPIC' => $s_watching_topic,
	'S_WATCH_TOPIC_IMG' => '',
	'IS_SUBFORUM' => $forum->isSubForum(),
	'U_VIEW_FORUM' => $forum->uri,
	'U_VIEW_TOPIC' => URL::index($canonical_q),
	'U_VIEW_ARCHIVE' => $forum->archive_uri,

	'S_SELECT_POST_DAYS'=> $select_post_days,
	'S_SELECT_POST_ORDER' => '<select name="postorder"><option value="asc">'.$lang['Oldest_First'].'</option><option value="desc" '.($post_time_order == 'DESC' ? 'selected="selected"' : '').'>'.$lang['Newest_First'].'</option></select>',
));

if ($template->QUICK_REPLY_FORM) {
	$hidden_fields = '<input class="df-challenge" type="hidden" name="'.\Dragonfly\Output\Captcha::generateHidden().'"/>';
	foreach ($template->hidden_qreply_fields as $field) {
		$hidden_fields .= '<input type="hidden" name="'.htmlspecialchars($field['name']).'" value="'.htmlspecialchars($field['value']).'"/>';
	}
	$template->assign_vars(array(
		'L_ATTACH_SIGNATURE' => $lang['Attach_signature'],
		'L_EMPTY_MESSAGE' => $lang['Empty_message'],
		'L_PREVIEW' => $lang['Preview'],
		'L_QUICK_REPLY' => $lang['Quick_Reply'],
		'L_QUICK_QUOTE' => $lang['Quick_quote'],
		'L_SUBMIT' => $lang['Submit'],
		'L_TYPESECCODE' => _TYPESECCODE,
		'S_IS_ANON' => false,
		'S_QREPLY_MSG' => htmlspecialchars($template->S_QREPLY_MSG),
		'S_GUEST_CAPTCHA' => false,
		'S_GFX_IMG' => null,
		'S_ANON_QREPLY' => '',
		'S_QREPLY_SIG' => $userinfo['user_attachsig'] ? ' checked="checked"' : '',
		'S_HIDDEN_QREPLY_FIELDS' => $hidden_fields,
	));
	$template->QUICK_REPLY_FORM = $template->toString('forums/quickreply');
}

if ($template->ARCHIVE_REPLY_FORM) {
	if (!empty($template->S_HIDDEN_AREPLY_FIELDS)) {
		$hidden_fields = '';
		foreach ($template->S_HIDDEN_AREPLY_FIELDS as $field) {
			$hidden_fields .= '<input type="hidden" name="'.htmlspecialchars($field['name']).'" value="'.htmlspecialchars($field['value']).'"/>';
		}
		$template->S_HIDDEN_AREPLY_FIELDS = $hidden_fields;
		$template->assign_vars(array(
			'L_ARCHIVE_REVIVE' => $lang['Archive_Revive'],
			'L_ARCHIVE_REVIVE_NOTES' => $lang['Archive_Revive_Notes'],
			'L_ARCHIVE_QUOTE' => $lang['Quick_quote'],
			'L_CLICK_TO_REVIVE' => $lang['Click_to_revive'],
			'S_ANON_AREPLY' => '',
			'S_IS_ANON' => !is_user(),
			'S_REVIVED' => $topic->archive_id,
		));
	} else {
		$template->assign_vars(array(
			'L_IS_REVIVED'    => $lang['Is_revived'],
			'L_REVIVED_CLICK' => $lang['Revived_click_here'],
		));
	}
	$template->ARCHIVE_REPLY_FORM = $template->toString('forums/archivereply');
} else {
	$template->ARCHIVE_REPLY_FORM = '';
}
