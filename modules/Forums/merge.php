<?php
/***************************************************************************
 *							merge.php
 *							---------
 *	begin				: 12/07/2003
 *	copyright			: Ptirhiik
 *	email				: admin@rpgnet-fr.com
 *
 *	version				: 0.0.6 - 22/10/2003
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

/* Applied rules:
 * TernaryToNullCoalescingRector
 */

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

use \Dragonfly\Forums\Topic;

\Dragonfly\Page::title($lang['Merge_topics']);

function get_topic_title($topic_id)
{
	if ($topic_id) {
		global $db;
		$row = $db->uFetchRow("SELECT topic_title FROM ".TOPICS_TABLE." WHERE topic_id={$topic_id}");
		if ($row) {
			return $row[0];
		}
	}
	return '';
}

// check if user is a moderator or an admin
if ($userinfo['user_level'] != \Dragonfly\Identity::LEVEL_MOD && !can_admin($module_name)) {
	message_die(GENERAL_MESSAGE, $lang['Not_Authorised']);
}

// from topic
$from_topic_id = $_POST->uint('from_topic') ?: $_GET->uint('t');
if (!$from_topic_id && !empty($_GET['p'])) {
	$row = $db->uFetchRow("SELECT topic_id FROM ".POSTS_TABLE." WHERE post_id=" . $_GET->uint('p'));
	$from_topic_id = $row ? (int)$row[0] : null;
}

// to topic
$to_topic_id = $_POST->uint('to_topic') ?: $_GET->uint('to');

if (isset($_POST['cancel'])) {
	URL::redirect(URL::index("&file=merge&t={$from_topic_id}&to={$to_topic_id}"));
}

// topic title
$topic_title = '';
if (isset($_POST['topic_title'])) {
	$topic_title = $_POST['topic_title'];
}

$shadow      = isset($_POST['shadow']);
$select_from = isset($_POST['select_from']);
$select_to   = isset($_POST['select_to']);
$submit      = ('POST' === $_SERVER['REQUEST_METHOD'] && !$select_from && !$select_to);

// check if a selection has been made
if (isset($_POST['topic_selected']) && ($select_from || $select_to)) {
	$topic_selected = $_POST->uint('topic_selected');
	if ($topic_selected) {
		if ($select_to) {
			$to_topic_id = $topic_selected;
		} else if ($select_from) {
			$from_topic_id = $topic_selected;
		}
		$submit = $select_from = $select_to = false;
	}
}

// forum_id
$forum_id = $_POST->uint('f') ?: $_GET->uint('f');
if (isset($_POST['fid']) || isset($_GET['fid'])) {
	$fid = $_POST['fid'] ?? $_GET['fid'];
	if (substr($fid, 0, 1) == 'f') {
		$forum_id = intval(substr($fid, 1));
	}
}

// selection
if ($select_from || $select_to) {
	// how many record in the forum
	$nbpages = 0;
	$limit = intval($board_config['topics_per_page']);

	if (!empty($forum_id)) {
		$result = $db->uFetchRow("SELECT COUNT(*) FROM ".TOPICS_TABLE."
			WHERE forum_id = {$forum_id}
			  AND topic_archive_flag = 0
			  AND topic_status < ".\Dragonfly\Forums\Topic::STATUS_MOVED);
		$nbpages = ceil($result[0] / $limit);
	}

	// change current page
	$start = (int)$_POST->uint('start');
	if (isset($_POST['page_prev']) && ($start > 0)) --$start;
	if (isset($_POST['page_next']) && ($start < ($nbpages-1))) ++$start;

	// get the list of forums
	$template->assign_vars(array(
		'S_FORUM_SELECT' => make_forum_select('f', false, $forum_id),
		'PAGINATION'     => ($nbpages > 1) ? sprintf($lang['Page_of'], ($start+1), $nbpages) : '',
		'PREV_PAGE'      => ($nbpages > 1 && $start > 0),
		'NEXT_PAGE'      => ($nbpages > 1 && $start < $nbpages-1),
	));

	// read the forum
	$start_topic = $start * $limit;
	if (!empty($forum_id)) {
		$topic_rowset = $db->query("SELECT
			t.topic_id      id,
			t.topic_title   title,
			t.topic_type    type,
			t.topic_status  status,
			t.topic_replies replies,
			p.post_time     last_post_time,
			REPLACE(".$db->quote(URL::index('&file=viewtopic&t=%s')).", '%s', t.topic_id) uri
		FROM ".TOPICS_TABLE." t
		LEFT JOIN ".POSTS_TABLE." p ON (p.post_id = t.topic_last_post_id)
		WHERE t.forum_id = {$forum_id}
		  AND topic_status < ".\Dragonfly\Forums\Topic::STATUS_MOVED."
		  AND topic_archive_flag = 0
		ORDER BY t.topic_type DESC, t.topic_last_post_id DESC
		LIMIT {$limit} OFFSET {$start_topic}");
	} else {
		$topic_rowset = array();
	}

	$template->merge_topics = array();
	$template->merge_topics_title = $select_to ? $lang['Merge_topic_to'] : $lang['Merge_topic_from'];

	$forums_images = get_forums_images();
	foreach ($topic_rowset as $topic) {
		$topic_id = $topic['id'];
		$topic_image = 'folder';
		$topic_image_alt = $lang['No_new_posts'];
		if (Topic::TYPE_GLOBAL_ANNOUNCE == $topic['type']) {
			$topic_image .= '_global_announce';
			$topic_image_alt = strip_tags($lang['Topic_Global_Announcement']);
		} else if (Topic::TYPE_ANNOUNCE == $topic['type']) {
			$topic_image .= '_announce';
			$topic_image_alt = strip_tags($lang['Topic_Announcement']);
		} else if (Topic::TYPE_STICKY == $topic['type']) {
			$topic_image .= '_sticky';
			$topic_image_alt = strip_tags($lang['Topic_Sticky']);
		} else if (Topic::STATUS_LOCKED == $topic['status']) {
			$topic_image .= '_locked';
			$topic_image_alt = $lang['Topic_locked'];
		} else if ($topic['replies'] >= $board_config['hot_threshold']) {
			$topic_image .= '_hot';
		}
		$topic_last_read = \Dragonfly\Forums\Display::getForumTopicLastVisit($forum_id, $topic_id);
		if (is_user() && $topic['last_post_time'] > $topic_last_read) {
			$topic_image .= '_new';
			$topic_image_alt = ($topic['status'] == Topic::STATUS_LOCKED) ? $lang['Topic_locked'] : $lang['New_posts'];
		}

		// send topic to template
		$topic['image'] = array(
			'src' => $forums_images[$topic_image],
			'alt' => $topic_image_alt
		);
		$template->merge_topics[] = $topic;
	}

	// system
	$template->MERGE_HIDDEN_FIELDS = array(
		array('name'=>'topic_title','value'=>$topic_title),
		array('name'=>'from_topic','value'=>$from_topic_id),
		array('name'=>'to_topic','value'=>$to_topic_id),
		array('name'=>'start','value'=>$start),
	);
	if ($shadow) {
		$template->MERGE_HIDDEN_FIELDS[] = array('name'=>'shadow','value'=>1);
	}
	if ($select_to) {
		$template->MERGE_HIDDEN_FIELDS[] = array('name'=>'select_to','value'=>1);
	} else if ($select_from) {
		$template->MERGE_HIDDEN_FIELDS[] = array('name'=>'select_from','value'=>1);
	}
	// set the page title and include the page header
	require_once('includes/phpBB/page_header.php');
	$template->display('forums/merge_select');
	return;
}

// submission
if ($submit) {
	// init
	$error = false;

	// check session id
	if (!\Dragonfly\Output\Captcha::validate($_POST)) {
		$error = true;
		\Poodle\Notify::error('Invalid session');
	}

	// verify the topics are not the same
	if ($from_topic_id == $to_topic_id) {
		$error = true;
		\Poodle\Notify::error($lang['Merge_topics_equals']);
	} else {
		// check if the from topic exists and get the forum_id
		$from_forum_id = 0;
		$from_poll = false;
		if ($from_topic_id && $row = $db->uFetchRow("SELECT forum_id, topic_vote FROM ".TOPICS_TABLE." WHERE topic_id={$from_topic_id}")) {
			$from_forum_id = $row[0];
			$from_poll = $row[1];
			if (!\Dragonfly\Forums\Auth::isForumModerator($from_forum_id)) {
				$error = true;
				\Poodle\Notify::error($lang['Merge_from_not_authorized']);
			}
		} else {
			$error = true;
			\Poodle\Notify::error($lang['Merge_from_not_found']);
		}

		// check if the from topic exists and get the forum_id
		$to_forum_id = 0;
		$to_poll = false;
		if ($to_topic_id && $row = $db->uFetchRow("SELECT forum_id, topic_vote FROM ".TOPICS_TABLE." WHERE topic_id={$to_topic_id}")) {
			$to_forum_id = $row[0];
			$to_poll = $row[1];
			if (!\Dragonfly\Forums\Auth::isForumModerator($to_forum_id)) {
				$error = true;
				\Poodle\Notify::error($lang['Merge_to_not_authorized']);
			}
		} else {
			$error = true;
			\Poodle\Notify::error($lang['Merge_to_not_found']);
		}
	}

	if (!$error) {
		// ask for confirmation or process
		if (isset($_POST['confirm'])) {
			// process poll
			if ($from_poll) {
				if ($to_poll) {
					// delete the vote
					\Dragonfly\Forums\Poll::deleteFromTopics($from_topic_id);
				} else {
					// move the poll to the new topic
					$db->query("UPDATE {$SQL->TBL->bbvote_desc} SET topic_id={$to_topic_id} WHERE topic_id={$from_topic_id}");
				}
			}

			// check if the destination is already watched
			$result = $db->query("SELECT user_id FROM ".TOPICS_WATCH_TABLE." WHERE topic_id={$to_topic_id}");
			$user_ids = array();
			while ($row = $result->fetch_row()) { $user_ids[] = $row[0]; }
			$sql_user = $user_ids ? " AND user_id NOT IN (".implode(', ', $user_ids).")" : '';

			// grab the topics watch to the new topic
			$db->query("UPDATE ".TOPICS_WATCH_TABLE." SET topic_id={$to_topic_id} WHERE topic_id={$from_topic_id}{$sql_user}");
			// clean up the old topics watch
			$db->query("DELETE FROM ".TOPICS_WATCH_TABLE." WHERE topic_id={$from_topic_id}");
			// process the posts
			$db->query("UPDATE ".POSTS_TABLE." SET forum_id={$to_forum_id}, topic_id={$to_topic_id} WHERE topic_id={$from_topic_id}");

			if ($shadow) {
				// transform the merged topic in a shadow
				$db->query("UPDATE ".TOPICS_TABLE."
						SET topic_status=".\Dragonfly\Forums\Topic::STATUS_MOVED.", topic_type=".\Dragonfly\Forums\Topic::TYPE_NORMAL.", topic_moved_id={$to_topic_id}
						WHERE topic_id={$from_topic_id}");
			} else {
				// delete the old topic
				$db->query("DELETE FROM ".TOPICS_TABLE." WHERE topic_id={$from_topic_id}");
			}

			// build the update request
			$sql_update = $topic_title ? "topic_title = {$db->quote($topic_title)}" : '';

			// update the poll status
			if ($from_poll && !$to_poll) {
				$sql_update .= (empty($sql_update) ? '' : ', ').'topic_vote=1';
			}

			// final update
			if (!empty($sql_update)) {
				$db->query("UPDATE ".TOPICS_TABLE." SET {$sql_update} WHERE topic_id={$to_topic_id}");
			}

			// synchronise the destination topic, and both forums
			if ($from_forum_id != $to_forum_id) {
				$forum = new \Dragonfly\Forums\Forum($from_forum_id);
				$forum->sync();
			}
			$forum = new \Dragonfly\Forums\Forum($to_forum_id);
			$forum->sync();

			// send end message
			\Dragonfly::closeRequest($lang['Merge_topic_done'], 200, URL::index("&file=viewtopic&t={$to_topic_id}"));
		}
		else
		{
			// ask for confirmation
			// does from topic has a poll ?
			if ($from_poll) {
				if ($to_poll) {
					\Poodle\Notify::warning($lang['Merge_poll_from_and_to']);
				} else {
					\Poodle\Notify::warning($lang['Merge_poll_from']);
				}
			}
			$message = sprintf(
				$lang['Merge_confirm_process'],
				htmlspecialchars(get_topic_title($from_topic_id)),
				htmlspecialchars(get_topic_title($to_topic_id))
			);
			$s_hidden_fields = array(
				array('name'=>'topic_title','value'=>$topic_title),
				array('name'=>'from_topic','value'=>$from_topic_id),
				array('name'=>'to_topic','value'=>$to_topic_id),
			);
			if ($shadow) { $hidden_fields[] = array('name'=>'shadow','value'=>1); }
			\Dragonfly\Page::confirm(
				URL::index('&file=merge'),
				$message,
				$s_hidden_fields
			);
			return;
		}
	}
}

require_once('includes/phpBB/page_header.php');

$template->assign_vars(array(
	'TOPIC_TITLE' => $topic_title ?: get_topic_title($to_topic_id),
	'FROM_TOPIC'  => $from_topic_id,
	'TO_TOPIC'    => $to_topic_id,
	'SHADOW'      => $shadow,
	'S_ACTION'    => URL::index("&file=merge"),
));

$template->display('forums/merge_body');
