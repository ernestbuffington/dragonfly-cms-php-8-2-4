<?php
/***************************************************************************
 *				functions_post.php
 *				-------------------
 *	 begin		: Saturday, Feb 13, 2001
 *	 copyright		: (C) 2001 The phpBB Group
 *	 email		: support@phpbb.com
 *
 *	 Modifications made by CPG Dev Team http://cpgnuke.com
 *	 Last modification notes:
 *
 *	 $Id: functions_post.php,v 9.18 2007/12/12 12:54:20 nanocaiordo Exp $
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
 ***************************************************************************/

if (!defined('IN_PHPBB')) { exit; }

//
// Prepare a message for posting
//
function prepare_post(&$mode, &$post_data, &$bbcode_on, &$html_on, &$error_msg, &$username, &$subject, &$message, &$poll_title, &$poll_options, &$poll_length)
{
	global $board_config, $userdata, $lang, $phpbb_root_path;

	// Check username
	$subject = Fix_Quotes($subject, true);
	if (!empty($username)) {
		$username = Fix_Quotes($username, true);
		if (!is_user() || (is_user() && $username != $userdata['username'])) {
			include("includes/phpBB/functions_validate.php");
			$result = validate_username($username);
			if ($result['error']) {
				$error_msg .= (!empty($error_msg)) ? '<br />' . $result['error_msg'] : $result['error_msg'];
			}
		} else {
			$username = '';
		}
	}

	// Check subject
	if (!empty($subject)) {
		$subject = htmlprepare($subject, false, ENT_QUOTES, true);
	}
	else if ($mode == 'newtopic' || ($mode == 'editpost' && $post_data['first_post'])) {
		$error_msg .= (!empty($error_msg)) ? '<br />' . $lang['Empty_subject'] : $lang['Empty_subject'];
	}

	// Check message
	if (!empty($message))
	{
		$message = Fix_Quotes(message_prepare($message, $html_on, $bbcode_on));
	}
	else if ($mode != 'delete' && $mode != 'poll_delete') {
		$error_msg .= (!empty($error_msg)) ? '<br />' . $lang['Empty_message'] : $lang['Empty_message'];
	}

	//
	// Handle poll stuff
	//
	if ($mode == 'newtopic' || ($mode == 'editpost' && $post_data['first_post'])) {
		$poll_length = (isset($poll_length)) ? max(0, intval($poll_length)) : 0;

		if (!empty($poll_title)) {
			$poll_title = htmlprepare($poll_title, false, ENT_QUOTES, true);
		}

		if(!empty($poll_options)) {
			$temp_option_text = array();
			foreach ($poll_options as $option_id => $option_text) {
       $option_text = trim($option_text);
       if (!empty($option_text)) {
   					$temp_option_text[$option_id] = htmlprepare($option_text, false, ENT_QUOTES, true);
   				}
   }
			$poll_options = $temp_option_text;

			if (count($poll_options) < 2) {
				$error_msg .= (!empty($error_msg)) ? '<br />' . $lang['To_few_poll_options'] : $lang['To_few_poll_options'];
			}
			else if (count($poll_options) > $board_config['max_poll_options']) {
				$error_msg .= (!empty($error_msg)) ? '<br />' . $lang['To_many_poll_options'] : $lang['To_many_poll_options'];
			}
			else if ($poll_title == '') {
				$error_msg .= (!empty($error_msg)) ? '<br />' . $lang['Empty_poll_title'] : $lang['Empty_poll_title'];
			}
		}
	}

	return;
}

//
// Post a new topic/reply/poll or edit existing post/poll
//
function submit_post($mode, &$post_data, &$message, &$meta, &$forum_id, &$topic_id, &$post_id, &$poll_id, &$topic_type, &$bbcode_on, &$html_on, &$smilies_on, &$attach_sig, &$post_username, &$post_subject, &$post_message, &$poll_title, &$poll_options, &$poll_length, &$topic_icon)
{
	global $board_config, $lang, $db, $phpbb_root_path;
	global $userdata, $userinfo;

	include("includes/phpBB/functions_search.php");

	$current_time = gmtime();

	if ($mode == 'newtopic' || $mode == 'reply' || $mode == 'editpost') {
		//
		// Flood control
		//
		$where_sql = ($userdata['user_id'] > ANONYMOUS) ? 'poster_id = ' . $userdata['user_id'] : 'poster_ip='.$userinfo['user_ip'];
		$sql = "SELECT MAX(post_time) AS last_post_time FROM " . POSTS_TABLE . " WHERE $where_sql";
		$result = $db->sql_query($sql);
		if ($row = $db->sql_fetchrow($result)) {
			if (intval($row['last_post_time']) > 0 && ($current_time - intval($row['last_post_time'])) < intval($board_config['flood_interval'])) {
				message_die(GENERAL_MESSAGE, $lang['Flood_Error']);
			}
		}
	}

	if ($mode == 'editpost') {
		remove_search_post($post_id);
	}

	if ($mode == 'newtopic' || ($mode == 'editpost' && $post_data['first_post'])) {
		$topic_vote = (!empty($poll_title) && (is_countable($poll_options) ? count($poll_options) : 0) >= 2) ? 1 : 0;

		if ($mode != "editpost") {
			$sql = "INSERT INTO " . TOPICS_TABLE . " (topic_title, topic_poster, topic_time, forum_id, topic_status, topic_type, topic_vote, icon_id) VALUES ('$post_subject', " . $userdata['user_id'] . ", $current_time, $forum_id, " . TOPIC_UNLOCKED . ", $topic_type, $topic_vote, $topic_icon)";
		} else {
			$sql  = "UPDATE " . TOPICS_TABLE . " SET topic_title = '$post_subject', topic_type = $topic_type, icon_id = $topic_icon " . (!empty($poll_title) ? ", topic_vote = " . $topic_vote : "") . " WHERE topic_id = $topic_id";
		}
		$db->sql_query($sql);
		if ($mode == 'newtopic') {
			$topic_id = $db->sql_nextid('topic_id');
		}
	}

	$edited_sql = ($mode == 'editpost' && !$post_data['last_post'] && $post_data['poster_post']) ? ", post_edit_time = $current_time, post_edit_count = post_edit_count + 1 " : "";
	if ($mode != "editpost") {
		$sql = "INSERT INTO " . POSTS_TABLE . " (topic_id, forum_id, poster_id, post_username, post_time, poster_ip, enable_bbcode, enable_html, enable_smilies, enable_sig) VALUES ($topic_id, $forum_id, " . $userdata['user_id'] . ", '$post_username', $current_time, ".$userinfo['user_ip'].", $bbcode_on, $html_on, $smilies_on, $attach_sig)";
	} else {
		$sql = "UPDATE " . POSTS_TABLE . " SET post_username = '$post_username', enable_bbcode = $bbcode_on, enable_html = $html_on, enable_smilies = $smilies_on, enable_sig = $attach_sig" . $edited_sql . " WHERE post_id = $post_id";
	}
	$db->sql_query($sql);
	if ($mode != 'editpost') {
		$post_id = $db->sql_nextid('post_id');
	}
	$sql = ($mode != 'editpost') ? "INSERT INTO " . POSTS_TEXT_TABLE . " (post_id, post_subject, post_text) VALUES ($post_id, '$post_subject', '$post_message')" : "UPDATE " . POSTS_TEXT_TABLE . " SET post_text = '$post_message',  post_subject = '$post_subject' WHERE post_id = $post_id";
	$db->sql_query($sql);

	add_search_words('single', $post_id, $post_message, $post_subject);

	//
	// Add poll
	//
	if (($mode == 'newtopic' || ($mode == 'editpost' && $post_data['edit_poll'])) && !empty($poll_title) && (is_countable($poll_options) ? count($poll_options) : 0) >= 2) {
		$sql = (!$post_data['has_poll']) ? "INSERT INTO " . VOTE_DESC_TABLE . " (topic_id, vote_text, vote_start, vote_length) VALUES ($topic_id, '$poll_title', $current_time, " . ($poll_length * 86400) . ")" : "UPDATE " . VOTE_DESC_TABLE . " SET vote_text = '$poll_title', vote_length = " . ($poll_length * 86400) . " WHERE topic_id = $topic_id";
		$db->sql_query($sql);

		$delete_option_sql = '';
		$old_poll_result = array();
		if ($mode == 'editpost' && $post_data['has_poll']) {
			$sql = "SELECT vote_option_id, vote_result FROM " . VOTE_RESULTS_TABLE . "
				WHERE vote_id = $poll_id ORDER BY vote_option_id ASC";
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result)) {
				$old_poll_result[$row['vote_option_id']] = $row['vote_result'];

				if (!isset($poll_options[$row['vote_option_id']])) {
					$delete_option_sql .= ($delete_option_sql != '') ? ', ' . $row['vote_option_id'] : $row['vote_option_id'];
				}
			}
		} else {
			$poll_id = $db->sql_nextid('vote_id');
		}

		reset($poll_options);

		$poll_option_id = 1;
		foreach ($poll_options as $option_id => $option_text) {
			if (!empty($option_text)) {
				$option_text = Fix_Quotes($option_text);
				$poll_result = ($mode == "editpost" && isset($old_poll_result[$option_id])) ? $old_poll_result[$option_id] : 0;
				if ($mode != "editpost" || !isset($old_poll_result[$option_id])) {
					$sql = "INSERT INTO " . VOTE_RESULTS_TABLE . " (vote_id, vote_option_id, vote_option_text, vote_result) VALUES ($poll_id, $poll_option_id, '$option_text', $poll_result)";
				} else {
					$sql = "UPDATE " . VOTE_RESULTS_TABLE . " SET vote_option_text = '$option_text', vote_result = $poll_result WHERE vote_option_id = $option_id AND vote_id = $poll_id";
				}
				$db->sql_query($sql);
				$poll_option_id++;
			}
		}

		if ($delete_option_sql != '') {
			$db->sql_query("DELETE FROM " . VOTE_RESULTS_TABLE . " WHERE vote_option_id IN ($delete_option_sql) AND vote_id = $poll_id");
		}
	}
	url_refresh(getlink("&file=viewtopic&" . POST_POST_URL . "=" . $post_id).'#'.$post_id);
	$message = $lang['Stored'] . '<br /><br />' . sprintf($lang['Click_view_message'], '<a href="' . getlink("&amp;file=viewtopic&amp;" . POST_POST_URL . "=" . $post_id).'#'.$post_id . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_forum'], '<a href="' . getlink("&amp;file=viewforum&amp;" . POST_FORUM_URL . "=$forum_id") . '">', '</a>');
	return false;
}

//
// Update post stats and details
//
function update_post_stats(&$mode, &$post_data, &$forum_id, &$topic_id, &$post_id, &$user_id)
{
	global $db;

	$sign = ($mode == 'delete') ? '- 1' : '+ 1';
	$forum_update_sql = "forum_posts = forum_posts $sign";
	$topic_update_sql = '';

	if ($mode == 'delete') {
		if ($post_data['last_post']) {
			if ($post_data['first_post']) {
				$forum_update_sql .= ', forum_topics = forum_topics - 1';
			} else {
				$topic_update_sql .= 'topic_replies = topic_replies - 1';
				$sql = "SELECT MAX(post_id) AS last_post_id FROM ".POSTS_TABLE." WHERE topic_id = $topic_id";
				$result = $db->sql_query($sql);
				if ($row = $db->sql_fetchrow($result)) {
					$topic_update_sql .= ', topic_last_post_id = ' . $row['last_post_id'];
				}
			}

			if ($post_data['last_topic']) {
				$sql = "SELECT MAX(post_id) AS last_post_id FROM " . POSTS_TABLE . "
					WHERE forum_id = $forum_id";
				$result = $db->sql_query($sql);

				if ($row = $db->sql_fetchrow($result)) {
					$forum_update_sql .= ($row['last_post_id']) ? ', forum_last_post_id = ' . $row['last_post_id'] : ', forum_last_post_id = 0';
				}
			}
		}
		else if ($post_data['first_post']) {
			$sql = "SELECT MIN(post_id) AS first_post_id FROM " . POSTS_TABLE . "
				WHERE topic_id = $topic_id";

			$result = $db->sql_query($sql);
			if ($row = $db->sql_fetchrow($result)) {
				$topic_update_sql .= 'topic_replies = topic_replies - 1, topic_first_post_id = ' . $row['first_post_id'];
			}
		} else {
			$topic_update_sql .= 'topic_replies = topic_replies - 1';
		}
	}
	else if ($mode != 'poll_delete') {
		$forum_update_sql .= ", forum_last_post_id = $post_id" . (($mode == 'newtopic') ? ", forum_topics = forum_topics $sign" : "");
		$topic_update_sql = "topic_last_post_id = $post_id" . (($mode == 'reply') ? ", topic_replies = topic_replies $sign" : ", topic_first_post_id = $post_id");
	} else {
		$topic_update_sql .= 'topic_vote = 0';
	}

	$db->sql_query("UPDATE " . FORUMS_TABLE . " SET $forum_update_sql WHERE forum_id = $forum_id");

	if ($topic_update_sql != '') {
		$db->sql_query("UPDATE " . TOPICS_TABLE . " SET $topic_update_sql WHERE topic_id = $topic_id");
	}

	if ($mode != 'poll_delete') {
		$db->sql_query("UPDATE " . USERS_TABLE . " SET user_posts = user_posts $sign WHERE user_id = $user_id");
	}

	return;
}

//
// Delete a post/poll
//
function delete_post($mode, &$post_data, &$message, &$meta, &$forum_id, &$topic_id, &$post_id, &$poll_id)
{
	$forum_update_sql = null;
 global $board_config, $lang, $db, $phpbb_root_path;
	global $userdata;

	if ($mode != 'poll_delete') {
		include("includes/phpBB/functions_search.php");
		$db->sql_query("DELETE FROM " . POSTS_TABLE . " WHERE post_id = $post_id");
		$db->sql_query("DELETE FROM " . POSTS_TEXT_TABLE . " WHERE post_id = $post_id");
		if ($post_data['last_post']) {
			if ($post_data['first_post']) {
				$forum_update_sql .= ', forum_topics = forum_topics - 1';
				$sql = "DELETE FROM " . TOPICS_TABLE . "
				   WHERE topic_id = $topic_id OR topic_moved_id = $topic_id";
				$db->sql_query($sql);
				$db->sql_query("DELETE FROM " . TOPICS_WATCH_TABLE . " WHERE topic_id = $topic_id");
			}
		}
		remove_search_post($post_id);
	}

	if ($mode == 'poll_delete' || ($mode == 'delete' && $post_data['first_post'] && $post_data['last_post']) && $post_data['has_poll'] && $post_data['edit_poll']) {
		$db->sql_query("DELETE FROM " . VOTE_DESC_TABLE . " WHERE topic_id = $topic_id");
		$db->sql_query("DELETE FROM " . VOTE_RESULTS_TABLE . " WHERE vote_id = $poll_id");
		$db->sql_query("DELETE FROM " . VOTE_USERS_TABLE . " WHERE vote_id = $poll_id");
	}

	if ($mode == 'delete' && $post_data['first_post'] && $post_data['last_post']) {
		url_refresh(getlink("&file=viewforum&" . POST_FORUM_URL . "=$forum_id"));
		$message = $lang['Deleted'];
	} else {
		url_refresh(getlink("&file=viewtopic&" . POST_TOPIC_URL . "=$topic_id"));
		$message = (($mode == 'poll_delete') ? $lang['Poll_delete'] : $lang['Deleted']) . '<br /><br />' . sprintf($lang['Click_return_topic'], '<a href="' . getlink("&amp;file=viewtopic&amp;" . POST_TOPIC_URL . "=$topic_id") . '">', '</a>');
	}

	$message .=	 '<br /><br />' . sprintf($lang['Click_return_forum'], '<a href="' . getlink("&amp;file=viewforum&amp;" . POST_FORUM_URL . "=$forum_id") . '">', '</a>');

	return;
}

//
// Handle user notification on new post
//
function user_notification($mode, &$post_data, &$topic_title, &$forum_id, &$topic_id, &$post_id, &$notify_user)
{
	global $board_config, $lang, $db, $phpbb_root_path, $MAIN_CFG;
	global $userdata;

	$current_time = gmtime();

	if ($mode == 'delete') {
		$delete_sql = (!$post_data['first_post'] && !$post_data['last_post']) ? " AND user_id = " . $userdata['user_id'] : '';
		$db->sql_query("DELETE FROM " . TOPICS_WATCH_TABLE . " WHERE topic_id = $topic_id" . $delete_sql);
	} else {
		if ($mode == 'reply') {
			$result = $db->sql_query('SELECT user_id FROM '.USERS_TABLE.' WHERE user_level<1');
			$user_id_sql = '';
			while ($row = $db->sql_fetchrow($result)) { $user_id_sql .= ', '.$row['user_id']; }

			$sql = "SELECT u.user_id, u.user_email, u.user_lang
				FROM ".TOPICS_WATCH_TABLE." tw, ".USERS_TABLE." u
				WHERE tw.topic_id = $topic_id
					AND tw.user_id NOT IN (".$userdata['user_id'].", ".ANONYMOUS.$user_id_sql.")
					AND tw.notify_status = " . TOPIC_WATCH_UN_NOTIFIED . "
					AND u.user_id = tw.user_id";
			$result = $db->sql_query($sql);

			$update_watched_sql = '';
			$bcc_list_ary = array();

			if ($row = $db->sql_fetchrow($result)) {
				// Sixty second limit
				set_time_limit(0);
				do {
					if ($row['user_email'] != '') {
						$bcc_list_ary[$row['user_lang']][] = $row['user_email'];
					}
					$update_watched_sql .= ($update_watched_sql != '') ? ', ' . $row['user_id'] : $row['user_id'];
				}
				while ($row = $db->sql_fetchrow($result));

				if (sizeof($bcc_list_ary)) {
					include("includes/phpBB/emailer.php");
					$emailer = new emailer();

					$orig_word = array();
					$replacement_word = array();
					obtain_word_list($orig_word, $replacement_word);

					$emailer->from($board_config['board_email']);
					$emailer->replyto($board_config['board_email']);

					$topic_title = (count($orig_word)) ? preg_replace($orig_word, $replacement_word, htmlunprepare($topic_title)) : htmlunprepare($topic_title);

					reset($bcc_list_ary);
					foreach ($bcc_list_ary as $user_lang => $bcc_list) {
         $emailer->use_template('topic_notify', $user_lang);
         for ($i = 0; $i < (is_countable($bcc_list) ? count($bcc_list) : 0); $i++) {
   							$emailer->bcc($bcc_list[$i]);
   						}
         // The Topic_reply_notification lang string below will be used
         // if for some reason the mail template subject cannot be read
         // ... note it will not necessarily be in the posters own language!
         $emailer->set_subject($lang['Topic_reply_notification']);
         // This is a nasty kludge to remove the username var ... till (if?)
         // translators update their templates
         $emailer->msg = preg_replace('#[ ]?{USERNAME}#', '', $emailer->msg);
         $emailer->assign_vars(array(
   							'EMAIL_SIG' => (!empty($board_config['board_email_sig'])) ? str_replace('<br />', "\n", "-- \n" . $board_config['board_email_sig']) : '',
   							'SITENAME' => $board_config['sitename'],
   							'TOPIC_TITLE' => $topic_title,

   							'U_TOPIC' => getlink('&file=viewtopic&' . POST_POST_URL . "=$post_id", true, true)."#$post_id",
   							'U_STOP_WATCHING_TOPIC' => getlink('&file=viewtopic&' . POST_TOPIC_URL . "=$topic_id&unwatch=topic", true, true))
   						);
         $emailer->send();
         $emailer->reset();
         //send_mail($error, $message, false, $lang['Topic_reply_notification'], $to='', $to_name='')
     }
				}
			}
			$db->sql_freeresult($result);

			if ($update_watched_sql != '') {
				$sql = "UPDATE " . TOPICS_WATCH_TABLE . "
					SET notify_status = " . TOPIC_WATCH_NOTIFIED . "
					WHERE topic_id = $topic_id AND user_id IN ($update_watched_sql)";
				$db->sql_query($sql);
			}
		}

		$sql = "SELECT topic_id FROM " . TOPICS_WATCH_TABLE . "
			WHERE topic_id = $topic_id AND user_id = " . $userdata['user_id'];
		$result = $db->sql_query($sql);

		$row = $db->sql_fetchrow($result);

		if (!$notify_user && !empty($row['topic_id'])) {
			$sql = "DELETE FROM " . TOPICS_WATCH_TABLE . "
					WHERE topic_id = $topic_id AND user_id = " . $userdata['user_id'];
			$db->sql_query($sql);
		}
		else if ($notify_user && empty($row['topic_id'])) {
			$sql = "INSERT INTO " . TOPICS_WATCH_TABLE . " (user_id, topic_id, notify_status)
					VALUES (" . $userdata['user_id'] . ", $topic_id, 0)";
			$db->sql_query($sql);
		}
	}
}

//
// Fill smiley templates (or just the variables) with smileys
// Either in a window or inline
//
function generate_smilies($mode, $page_id)
{
	global $db, $board_config, $template, $lang, $images, $theme, $phpbb_root_path;
	global $userdata;

	$inline_columns = 4;
	$inline_rows = 5;

	$result = $db->sql_query('SELECT emoticon, code, smile_url FROM ' . SMILIES_TABLE . ' ORDER BY smilies_id');
	if ($db->sql_numrows($result)) {
		$num_smilies = 0;
		$rowset = array();
		while ($row = $db->sql_fetchrow($result)) {
			if (empty($rowset[$row['smile_url']])) {
				$rowset[$row['smile_url']]['code'] = str_replace("'", "\\'", str_replace('\\', '\\\\', $row['code'])); //'
				$rowset[$row['smile_url']]['emoticon'] = $row['emoticon'];
				$num_smilies++;
			}
		}

		if ($num_smilies) {
			$smilies_count = min(19, $num_smilies);
			$smilies_split_row = $inline_columns - 1;

			$s_colspan = 0;
			$row = 0;
			$col = 0;

			foreach ($rowset as $smile_url => $data) {
       if (!$col) {
   					$template->assign_block_vars('smilies_row', array());
   				}
       $template->assign_block_vars('smilies_row.smilies_col', array(
   					'SMILEY_CODE' => $data['code'],
   					'SMILEY_IMG' => $board_config['smilies_path'] . '/' . $smile_url,
   					'SMILEY_DESC' => $data['emoticon'])
   				);
       $s_colspan = max($s_colspan, $col + 1);
       if ($col == $smilies_split_row) {
   					if ($row == $inline_rows - 1) { break; }
   					$col = 0;
   					$row++;
   				} else {
   					$col++;
   				}
   }

			if ($num_smilies > $inline_rows * $inline_columns) {
				$template->assign_block_vars('switch_smilies_extra', array());
				$template->assign_vars(array(
					'L_MORE_SMILIES' => $lang['More_emoticons'],
					'U_MORE_SMILIES' => getlink("smilies&amp;field=message&amp;form=post")
					)
				);
			}

			$template->assign_vars(array(
				'L_EMOTICONS' => $lang['Emoticons'],
				'L_CLOSE_WINDOW' => $lang['Close_window'],
				'S_SMILIES_COLSPAN' => $s_colspan)
			);
		}
	}
}
