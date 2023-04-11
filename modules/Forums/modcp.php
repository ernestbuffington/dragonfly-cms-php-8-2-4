<?php
/***************************************************************************
 *			modcp.php
 *		  -------------------
 *	begin	  : July 4, 2001
 *	copyright	  : (C) 2001 The phpBB Group
 *	email	  : support@phpbb.com
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 ***************************************************************************/

/**
 * Moderator Control Panel
 *
 * From this 'Control Panel' the moderator of a forum will be able to do
 * mass topic operations (locking/unlocking/moving/deleteing), and it will
 * provide an interface to do quick locking/unlocking/moving/deleting of
 * topics via the moderator operations buttons on all of the viewtopic pages.
 */
 
/* Applied rules:
 * TernaryToNullCoalescingRector
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */ 

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

# Obtain initial var settings
$forum_id = $_POST->uint('f') ?: $_GET->uint('f');
$post_id  = $_POST->uint('p') ?: $_GET->uint('p');
$topic_id = $_POST->uint('t') ?: $_GET->uint('t');
$confirm  = (isset($_POST['confirm']) && !isset($_POST['cancel']));

# Continue var definitions
$start = isset($_POST['start']) ? intval($_POST['start']) : (isset($_GET['start']) ? intval($_GET['start']) : 0);

if (isset($_POST['mode']) || isset($_GET['mode'])) {
	$mode = $_POST->txt('mode') ?: $_GET->txt('mode');
	$mode = preg_replace('/[^a-z]/','',$mode);
} else {
	if (isset($_POST['archive'])) {
		$mode = 'archive';
	} elseif (isset($_POST['delete'])) {
		$mode = 'delete';
	} else if (isset($_POST['move'])) {
		$mode = 'move';
	} else if (isset($_POST['lock'])) {
		$mode = 'lock';
	} else if (isset($_POST['unlock'])) {
		$mode = 'unlock';
	} else {
		$mode = '';
	}
}

# Obtain relevant data
$topic_archived = false;
if (!empty($topic_id)) {
	$topic_row = $db->uFetchRow("SELECT forum_id, topic_archive_flag FROM ".TOPICS_TABLE."
		WHERE topic_id = {$topic_id}");
	if (!$topic_row) {
		\Poodle\HTTP\Status::set(404);
		message_die(GENERAL_MESSAGE, 'Forum_not_exist');
	}
	$forum_id = $topic_row[0];
	$topic_archived = $topic_row[1];
	$recycle = ($board_config['allow_topic_recycle'] && $forum_id != $board_config['topic_recycle_forum']) ? true : false;
	$mode = ($recycle && 'delete' == $mode) ? 'recycle' : $mode;
}
if (empty($forum_id)) {
	\Poodle\HTTP\Status::set(404);
	message_die(GENERAL_MESSAGE, 'Forum_not_exist');
}

# Check if user did or did not confirm
# If they did not, forward them to the last page they were on
if (isset($_POST['cancel'])) {
	$redirect = '';
	if ($topic_id) {
		$redirect = "&file=viewtopic&t={$topic_id}";
	} else if ($forum_id && isset($_POST['archive'])) {
		$redirect = "&file=viewarchive&f={$forum_id}";
	} else if ($forum_id) {
		$redirect = "&file=viewforum&f={$forum_id}";
	}
	URL::redirect(URL::index($redirect));
}

$forum = new \Dragonfly\Forums\Forum($forum_id);

# Start auth check
$is_auth = $forum->getUserPermissions();

if (!$is_auth['auth_mod']) {
	message_die(GENERAL_MESSAGE, $lang['Not_Moderator'], $lang['Not_Authorised']);
}
# End Auth Check

function getValidTopicIds($topics)
{
	return implode(', ', array_map('intval',$topics));
/*
	$topic_ids = implode(', ', array_map('intval',$topics));
	$result = $db->query("SELECT topic_id FROM ".TOPICS_TABLE."
	WHERE topic_id IN ({$topic_ids}) AND forum_id = {$forum_id}");
	$topic_ids = array();
	while ($row = $result->fetch_row()) {
		$topic_ids[] = (int)$row[0];
	}
	return implode(', ', $topics);
*/
}

# Do major work ...
switch ($mode)
{
	case 'archive':
		if (!$is_auth['auth_delete']) {
			message_die(MESSAGE, sprintf($lang['Sorry_auth_delete'], $is_auth['auth_delete_type']));
		}
		\Dragonfly\Page::title($lang['Mod_CP']);
		if ($confirm) {
			\Dragonfly\Forums\Archive::topics($_POST['topic_id_list'] ?? array($topic_id));
			$forum->sync();
			if (!empty($topic_id)) {
				$redirect_page = "&file=viewforum&f={$forum_id}";
			} else {
				$redirect_page = "&file=modcp&f={$forum_id}&start={$start}";
			}
			\Dragonfly::closeRequest($lang['Topics_Archived'], 200, URL::index($redirect_page));
		} else {
			# Not confirmed, show confirmation message
			if (empty($_POST['topic_id_list']) && empty($topic_id)) {
				message_die(GENERAL_MESSAGE, $lang['None_selected']);
			}
			$hidden_fields = array(
				array('name'=>'mode','value'=>$mode),
				array('name'=>'f','value'=>$forum_id),
			);
			if (isset($_POST['topic_id_list'])) {
				foreach ($_POST['topic_id_list'] as $id) {
					$hidden_fields[] = array('name'=>'topic_id_list[]','value'=>$id);
				}
			} else {
				$hidden_fields[] = array('name'=>'t','value'=>$topic_id);
			}
			\Dragonfly\Page::confirm(
				URL::index('&file=modcp&start='.$start),
				$lang['Confirm_archive_topic'],
				$hidden_fields);
		}
		break;

	case 'delete':
		if (!$is_auth['auth_delete']) {
			message_die(MESSAGE, sprintf($lang['Sorry_auth_delete'], $is_auth['auth_delete_type']));
		}
		\Dragonfly\Page::title($lang['Mod_CP']);
		if ($confirm) {
			$topic_id_sql = getValidTopicIds($_POST['topic_id_list'] ?? array($topic_id));

			$result = $db->query("SELECT poster_id, COUNT(post_id) FROM ".POSTS_TABLE."
			WHERE topic_id IN ({$topic_id_sql}) GROUP BY poster_id");
			while ($row = $result->fetch_row()) {
				$db->query("UPDATE {$db->TBL->users}
				SET user_posts = user_posts - {$row[1]}
				WHERE user_id = {$row[0]}");
			}
			$result->free();

			# Got all required info so go ahead and start deleting everything
			$db->query("DELETE FROM ".TOPICS_TABLE."
			WHERE topic_id IN ({$topic_id_sql}) OR topic_moved_id IN ({$topic_id_sql})");

			$result = $db->query("SELECT post_id FROM ".POSTS_TABLE." WHERE topic_id IN ({$topic_id_sql})");
			if ($result->num_rows) {
				$post_ids = array();
				while ($row = $result->fetch_row()) {
					$post_ids[] = $row[0];
				}
				$post_ids_sql = implode(',', $post_ids);
				$db->query("DELETE FROM ".POSTS_TABLE." WHERE post_id IN ({$post_ids_sql})");
				$db->query("DELETE FROM ".POSTS_TEXT_TABLE." WHERE post_id IN ({$post_ids_sql})");
				\Dragonfly\Forums\Search::removeForPosts($post_ids);
				\Dragonfly\Forums\Attachments::deleteFromPosts($post_ids);
			}
			unset($result);

			\Dragonfly\Forums\Poll::deleteFromTopics(explode(',', $topic_id_sql));

			$db->query("DELETE FROM ".TOPICS_WATCH_TABLE." WHERE topic_id IN ({$topic_id_sql})");

			$forum->sync();

			if (!empty($topic_id)) {
				$redirect_page = "&file=viewforum&f={$forum_id}";
			} else {
				$redirect_page ="&file=modcp&f={$forum_id}&start={$start}";
			}
			\Dragonfly::closeRequest($lang['Topics_Removed'], 200, URL::index($redirect_page));
		} else {
			# Not confirmed, show confirmation message
			if (empty($_POST['topic_id_list']) && empty($topic_id)) {
				message_die(GENERAL_MESSAGE, $lang['None_selected']);
			}
			$hidden_fields = array(
				array('name'=>'mode','value'=>$mode),
				array('name'=>'f','value'=>$forum_id),
			);
			if (isset($_POST['topic_id_list'])) {
				foreach ($_POST['topic_id_list'] as $id) {
					$hidden_fields[] = array('name'=>'topic_id_list[]','value'=>$id);
				}
			} else {
				$hidden_fields[] = array('name'=>'t','value'=>$topic_id);
			}
			\Dragonfly\Page::confirm(
				URL::index('&file=modcp&start='.$start),
				$lang['Confirm_delete_topic'],
				$hidden_fields);
		}
		break;

	case 'recycle':
		if (!$is_auth['auth_delete']) {
			message_die(MESSAGE, sprintf($lang['Sorry_auth_delete'], $is_auth['auth_delete_type']));
		}
		\Dragonfly\Page::title($lang['Mod_CP']);
		if ($confirm) {
			if (empty($_POST['topic_id_list']) && empty($topic_id)) {
				message_die(GENERAL_MESSAGE, $lang['None_selected']);
			}
			if ($board_config['topic_recycle_forum'] != $forum_id) {
				$topic_id_sql = getValidTopicIds($_POST['topic_id_list'] ?? array($topic_id));
				$result = $db->query("SELECT topic_id FROM ".TOPICS_TABLE."
				WHERE topic_id IN ({$topic_id_sql}) AND forum_id = {$forum_id} AND topic_status <> ".\Dragonfly\Forums\Topic::STATUS_MOVED);
				while ($row = $result->fetch_row()) {
					$topic_id = $row[0];
					$db->query("UPDATE ".TOPICS_TABLE."
						SET forum_id = {$board_config['topic_recycle_forum']}
						WHERE topic_id = {$topic_id}");
					$db->query("UPDATE ".POSTS_TABLE."
						SET forum_id = {$board_config['topic_recycle_forum']}
						WHERE topic_id = {$topic_id}");
				}
				$result->free();
				# Sync the forum indexes
				$forum->sync();
				$recycle_forum = new \Dragonfly\Forums\Forum($board_config['topic_recycle_forum']);
				$recycle_forum->sync();

				$message = $lang['Topics_Removed'];
			} else {
				$message = $lang['None_Selected'];
			}
			if (!empty($topic_id)) {
				$redirect_page = "&file=viewforum&f={$forum_id}";
			} else {
				$redirect_page = "&file=modcp&f={$forum_id}&start={$start}";
			}
			\Dragonfly::closeRequest($message, 200, URL::index($redirect_page));
		} else {
			if (empty($_POST['topic_id_list']) && empty($topic_id)) {
				message_die(GENERAL_MESSAGE, $lang['None_selected']);
			}
			$hidden_fields = array(
				array('name'=>'mode','value'=>$mode),
				array('name'=>'f','value'=>$forum_id),
			);
			if (isset($_POST['topic_id_list'])) {
				foreach ($_POST['topic_id_list'] as $id) {
					$hidden_fields[] = array('name'=>'topic_id_list[]','value'=>$id);
				}
			} else {
				$hidden_fields[] = array('name'=>'t','value'=>$topic_id);
			}
			\Dragonfly\Page::confirm(
				URL::index('&file=modcp&start='.$start),
				$lang['Confirm_delete_topic'],
				$hidden_fields);
		}
		break;

	case 'move':
		\Dragonfly\Page::title($lang['Mod_CP']);
		if ($confirm) {
			if (empty($_POST['topic_id_list']) && empty($topic_id)) {
				message_die(GENERAL_MESSAGE, $lang['None_selected']);
			}
			$new_forum_id = $_POST->uint('new_forum');
			if ($new_forum_id && $new_forum_id != $forum_id) {
				$topic_id_sql = getValidTopicIds($_POST['topic_id_list'] ?? array($topic_id));
				$result = $db->query("SELECT * FROM ".TOPICS_TABLE."
				WHERE topic_id IN ({$topic_id_sql}) AND forum_id = {$forum_id} AND topic_status <> ".\Dragonfly\Forums\Topic::STATUS_MOVED);
				while ($row = $result->fetch_assoc()) {
					$topic_id = $row['topic_id'];
					if (isset($_POST['move_leave_shadow'])) {
						# Insert topic in the old forum that indicates that the forum has moved.
						$db->query("INSERT INTO ".TOPICS_TABLE."
						(forum_id, topic_title, topic_poster, topic_time, topic_status, topic_type, topic_vote, topic_views, topic_replies, topic_first_post_id, topic_last_post_id, topic_moved_id)
						VALUES
						({$forum_id}, {$db->quote($row['topic_title'])},
						 {$db->quote($row['topic_poster'])}, {$row['topic_time']},
						 ".\Dragonfly\Forums\Topic::STATUS_MOVED.", ".\Dragonfly\Forums\Topic::TYPE_NORMAL.", {$row['topic_vote']},
						 {$row['topic_views']}, {$row['topic_replies']},
						 {$row['topic_first_post_id']}, {$row['topic_last_post_id']},
						 {$topic_id})");
					}
					$db->query("UPDATE ".TOPICS_TABLE."
					SET forum_id = {$new_forum_id}
					WHERE topic_id = {$topic_id}");
					$db->query("UPDATE ".POSTS_TABLE."
					SET forum_id = {$new_forum_id}
					WHERE topic_id = {$topic_id}");
				}
				$result->free();

				# Sync the forum indexes
				$forum->sync();
				$new_forum = new \Dragonfly\Forums\Forum($new_forum_id);
				$new_forum->sync();
				$message = $lang['Topics_Moved'];
			} else {
				$message = $lang['No_Topics_Moved'];
			}
			if (!empty($topic_id)) {
				$redirect_page = "&file=viewtopic&t={$topic_id}";
			} else {
				$redirect_page = "&file=modcp&f={$forum_id}&start={$start}";
			}
			\Dragonfly::closeRequest($message, 200, URL::index($redirect_page));
		} else {
			if (empty($_POST['topic_id_list']) && empty($topic_id)) {
				message_die(GENERAL_MESSAGE, $lang['None_selected']);
			}
			$hidden_fields = array(
				array('name'=>'mode','value'=>$mode),
				array('name'=>'f','value'=>$forum_id)
			);
			if (isset($_POST['topic_id_list'])) {
				foreach ($_POST['topic_id_list'] as $id) {
					$hidden_fields[] = array('name'=>'topic_id_list[]','value'=>$id);
				}
			} else {
				$hidden_fields[] = array('name'=>'t','value'=>$topic_id);
			}
			$template->assign_vars(array(
				'S_FORUM_SELECT' => make_forum_select('new_forum', $forum_id),
				'hidden_form_fields' => $hidden_fields
			));
			$template->set_handle('body', 'forums/modcp_move');
		}
		break;

	case 'lock':
		if ( empty($_POST['topic_id_list']) && empty($topic_id) ) {
			message_die(GENERAL_MESSAGE, $lang['None_selected']);
		}
		$topic_id_sql = getValidTopicIds($_POST['topic_id_list'] ?? array($topic_id));
		$result = $db->query("UPDATE ".TOPICS_TABLE."
		SET topic_status = ".\Dragonfly\Forums\Topic::STATUS_LOCKED."
		WHERE topic_id IN ({$topic_id_sql})
		  AND forum_id = {$forum_id}
		  AND topic_moved_id = 0");
		if (!empty($topic_id)) {
			$redirect_page = "&file=viewtopic&t={$topic_id}";
		} else {
			$redirect_page = "&file=modcp&f={$forum_id}&start={$start}";
		}
		\Dragonfly::closeRequest($lang['Topics_Locked'], 200, URL::index($redirect_page));
		break;

	case 'unlock':
		if ( empty($_POST['topic_id_list']) && empty($topic_id) ) {
			message_die(GENERAL_MESSAGE, $lang['None_selected']);
		}
		$topic_id_sql = getValidTopicIds($_POST['topic_id_list'] ?? array($topic_id));
		$result = $db->query("UPDATE ".TOPICS_TABLE."
		SET topic_status = ".\Dragonfly\Forums\Topic::STATUS_UNLOCKED."
		WHERE topic_id IN ({$topic_id_sql})
		  AND forum_id = {$forum_id}
		  AND topic_moved_id = 0");
		if (!empty($topic_id)) {
			$redirect_page = "&file=viewtopic&t={$topic_id}";
		} else {
			$redirect_page = "&file=modcp&f={$forum_id}&start={$start}";
		}
		\Dragonfly::closeRequest($lang['Topics_Unlocked'], 200, URL::index($redirect_page));
		break;

	case 'split':
		\Dragonfly\Page::title($lang['Mod_CP']);
		$post_id_sql = array();
		if (isset($_POST['split_type_all']) || isset($_POST['split_type_beyond'])) {
			$i = is_countable($_POST['post_id_list']) ? count($_POST['post_id_list']) : 0;
			while ($i--) { $post_id_sql[] = (int)$_POST['post_id_list'][$i]; }
		}
		$post_id_sql = implode(',',$post_id_sql);
		if ($post_id_sql) {
			$result = $db->query("SELECT post_id FROM ".POSTS_TABLE."
			WHERE post_id IN ({$post_id_sql}) AND forum_id = {$forum_id}");
			$post_id_sql = '';
			while ($row = $result->fetch_row()) {
				$post_id_sql .= (($post_id_sql != '') ? ', ' : '').intval($row[0]);
			}
			$result->free();
			$result = $db->query("SELECT post_id, poster_id, topic_id, post_time FROM ".POSTS_TABLE."
			WHERE post_id IN ({$post_id_sql}) ORDER BY post_time ASC");
			$new_forum_id = $_POST->uint('new_forum_id');
			if ($new_forum_id && $row = $result->fetch_assoc()) {
				$first_poster = $row['poster_id'];
				$topic_id = $row['topic_id'];
				$post_time = $row['post_time'];
				$user_id_sql = $post_id_sql = '';
				do {
					$user_id_sql .= (($user_id_sql != '') ? ', ' : '').intval($row['poster_id']);
					$post_id_sql .= (($post_id_sql != '') ? ', ' : '').intval($row['post_id']);
				}
				while ($row = $result->fetch_assoc());
				$post_subject = $_POST['subject'];
				if (empty($post_subject)) {
					message_die(GENERAL_MESSAGE, $lang['Empty_subject']);
				}
				$topic_time = time();
				$db->query("INSERT INTO ".TOPICS_TABLE."
				(topic_title, topic_poster, topic_time, forum_id, topic_status, topic_type)
				VALUES
				({$db->quote(htmlprepare($post_subject))}, {$first_poster}, {$topic_time}, {$new_forum_id}, ".\Dragonfly\Forums\Topic::STATUS_UNLOCKED.", ".\Dragonfly\Forums\Topic::TYPE_NORMAL.")");
				$new_topic_id = $db->insert_id('topic_id');

				# Update topic watch table, switch users whose posts
				# have moved, over to watching the new topic
				$db->query("UPDATE ".TOPICS_WATCH_TABLE."
					SET topic_id = {$new_topic_id}
					WHERE topic_id = {$topic_id}
					  AND user_id IN ({$user_id_sql})");

				$sql_where = (!empty($_POST['split_type_beyond'])) ? " post_time >= {$post_time} AND topic_id = {$topic_id}" : "post_id IN ({$post_id_sql})";

				$db->query("UPDATE ".POSTS_TABLE."
					SET topic_id = {$new_topic_id}, forum_id = {$new_forum_id}
					WHERE {$sql_where}");

				$forum->sync();
				$new_forum = new \Dragonfly\Forums\Forum($new_forum_id);
				$new_forum->sync();

				\Dragonfly::closeRequest($lang['Topic_split'], 200, URL::index("&file=viewtopic&t={$topic_id}"));
			}
		} else {
			$result = $db->query("SELECT
				u.username,
				p.*,
				pt.post_text,
				pt.post_subject,
				p.post_username
			FROM ".POSTS_TABLE." p, {$db->TBL->users} u, ".POSTS_TEXT_TABLE." pt
			WHERE p.topic_id = {$topic_id}
			  AND p.poster_id = u.user_id
			  AND p.post_id = pt.post_id
			ORDER BY p.post_time ASC");
			if (!$result->num_rows) {
				cpg_error('Could not get topic/post information', $lang['General_Error']);
			}
			$hidden_fields = array(
				array('name'=>'mode','value'=>$mode),
				array('name'=>'f','value'=>$forum_id),
				array('name'=>'t','value'=>$topic_id)
			);
			if ($total_posts = $result->num_rows) {
				$template->assign_vars(array(
					'forum' => $forum,
					'hidden_form_fields' => $hidden_fields,
					'S_FORUM_SELECT' => make_forum_select("new_forum_id", false, $forum_id)
				));
				$template->postrow = array();
				$i = 0;
				$orig_word = $replacement_word = array();
				obtain_word_list($orig_word, $replacement_word);
				while ($post = $result->fetch_assoc()) {
					$apost = new \Dragonfly\Forums\Post();
					$apost->message        = $post['post_text'];
					$apost->enable_bbcode  = $post['enable_bbcode'];
					$apost->enable_html    = $post['enable_html'];
					$apost->enable_smilies = $post['enable_smilies'];
					$message = $apost->message2html();
					$template->postrow[] = array(
						'id' => $post['post_id'],
						'username' => $post['username'],
						'date' => $lang->date($board_config['default_dateformat'], $post['post_time']),
						'message' => $orig_word ? preg_replace($orig_word, $replacement_word, $message) : $message,
						'S_SPLIT_CHECKBOX' => $i++
					);
				}
				$template->set_handle('body', 'forums/modcp_split');
			}
		}
		break;

	case 'ip':
		\Dragonfly\Page::title($lang['Mod_CP']);
		$images = get_forums_images();

		$rdns_ip_num = $_GET['rdns'] ?? '';

		if (!$post_id) {
			message_die(GENERAL_MESSAGE, $lang['No_such_post']);
		}

		# Look up relevent data for this post
		$post_row = $db->uFetchAssoc("SELECT poster_ip, poster_id FROM ".($topic_archived?POSTS_ARCHIVE_TABLE:POSTS_TABLE)."
		WHERE post_id = $post_id AND forum_id = $forum_id");
		if (!$post_row) {
			message_die(GENERAL_MESSAGE, $lang['No_such_post']);
		}

		$ip_this_post = \Dragonfly\Net::decode_ip($post_row['poster_ip']);
		$ip_this_post = ( $rdns_ip_num == $ip_this_post ) ? \Poodle\INET::getHostName($ip_this_post) : $ip_this_post;

		$template->assign_vars(array(
			'SEARCH_IMG' => $images['icon_search'],
			'IP' => $ip_this_post,
			'U_LOOKUP_IP' => URL::index("&file=modcp&mode=ip&p={$post_id}&t={$topic_id}&rdns=".$ip_this_post)
		));

		# Get other IP's this user has posted under
		$result = $db->query("SELECT poster_ip, COUNT(*) AS postings
		FROM ".POSTS_TABLE."
		WHERE poster_id = {$post_row['poster_id']}
		GROUP BY poster_ip
		ORDER BY postings DESC");
		while ($row = $result->fetch_assoc()) {
			if ( $row['poster_ip'] == $post_row['poster_ip'] ) {
				$template->assign_vars(array(
					'POSTS' => $row['postings'].' '.( ( $row['postings'] == 1 ) ? $lang['Post'] : $lang['Posts'] )
				));
				continue;
			}
			$ip = \Dragonfly\Net::decode_ip($row['poster_ip']);
			$ip = ( $rdns_ip_num == $row['poster_ip'] || $rdns_ip_num == 'all') ? \Poodle\INET::getHostName($ip) : $ip;
			$template->assign_block_vars('iprow', array(
				'ip' => $ip,
				'posts' => $row['postings'].' '.( ( $row['postings'] == 1 ) ? $lang['Post'] : $lang['Posts'] ),
				'U_LOOKUP_IP' => URL::index("&file=modcp&mode=ip&p={$post_id}&t={$topic_id}&rdns=".\Dragonfly\Net::decode_ip($row['poster_ip']))
			));
		}

		# Get other users who've posted under this IP
		$result = $db->query("SELECT u.user_id, u.username, COUNT(*) as postings
		FROM {$db->TBL->users} u, ".POSTS_TABLE." p
		WHERE p.poster_id = u.user_id
		  AND p.poster_ip = {$db->quote($post_row['poster_ip'])}
		GROUP BY u.user_id, u.username
		ORDER BY postings DESC");
		while ($row = $result->fetch_assoc()) {
			$id = $row['user_id'];
			$username = ($id == \Dragonfly\Identity::ANONYMOUS_ID) ? $lang['Guest'] : $row['username'];
			$template->assign_block_vars('userrow', array(
				'USERNAME' => $username,
				'POSTS' => $row['postings'].' '.( ( $row['postings'] == 1 ) ? $lang['Post'] : $lang['Posts'] ),
				'L_SEARCH_POSTS' => sprintf($lang['Search_user_posts'], $username),
				'U_PROFILE' => \Dragonfly\Identity::getProfileURL($id),
				'U_SEARCHPOSTS' => URL::index("&file=search&search_author=".urlencode($username)."&showresults=topics")
			));
		}

		$template->set_handle('body', 'forums/modcp_viewip');
		break;

	default:
		\Dragonfly\Page::title($lang['Mod_CP']);
		$images = get_forums_images();
		$template->forum = $forum;
		make_jumpbox('modcp');
		$result = $db->query("SELECT
			t.topic_id       id,
			topic_title      title,
			topic_type       type,
			topic_status     status,
			topic_vote       vote,
			topic_replies    replies,
			topic_attachment attachment,
			post_time
		FROM ".TOPICS_TABLE." t, ".POSTS_TABLE." p
		WHERE t.forum_id = {$forum->id}
		  AND p.post_id = t.topic_last_post_id
		ORDER BY t.topic_type DESC, p.post_time DESC
		LIMIT {$board_config['topics_per_page']} OFFSET {$start}");
		while ($row = $result->fetch_assoc()) {
			$topic_type = $row['type'];
			if (\Dragonfly\Forums\Topic::STATUS_LOCKED == $row['status']) {
				$folder_img = $images['folder_locked'];
				$folder_alt = $lang['Topic_locked'];
			} else {
				if (\Dragonfly\Forums\Topic::TYPE_ANNOUNCE == $topic_type) {
					$folder_img = $images['folder_announce'];
					$folder_alt = $lang['Topic_Announcement'];
				} else if (\Dragonfly\Forums\Topic::TYPE_STICKY == $topic_type) {
					$folder_img = $images['folder_sticky'];
					$folder_alt = $lang['Topic_Sticky'];
				} else {
					$folder_img = $images['folder'];
					$folder_alt = $lang['No_new_posts'];
				}
			}
			$row += array(
				'uri' => URL::index("&file=modcp&mode=split&t={$row['id']}"),
				'attachment_img' => \Dragonfly\Forums\Attachments::getTopicImage($row['attachment'], $forum),
				'last_post_time' => $lang->date($board_config['default_dateformat'], $row['post_time']),
				'image' => $folder_img,
				'image_alt' => strip_tags($folder_alt)
			);
			$template->assign_block_vars('topicrow', $row);
		}

		$template->assign_vars(array(
		'PAGINATION' => generate_pagination("&file=modcp&f={$forum->id}", $forum->topics, $board_config['topics_per_page'], $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], (floor($start / $board_config['topics_per_page']) + 1), max(ceil($forum->topics / $board_config['topics_per_page']),1)),
		));

		$template->set_handle('body', 'forums/modcp_body');
		break;
}

require_once('includes/phpBB/page_header.php');
$template->display('body');
