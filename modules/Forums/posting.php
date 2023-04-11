<?php
/***************************************************************************
 *				posting.php
 *				-------------------
 *	 begin		: Saturday, Feb 13, 2001
 *	 copyright	: (C) 2001 The phpBB Group
 *	 email		: support@phpbb.com
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
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

# Check and set various parameters
$mode     = $_POST->text('mode') ?: $_GET->text('mode');
$forum_id = $_POST->uint('f') ?: $_GET->uint('f');
$topic_id = $_POST->uint('t') ?: $_GET->uint('t');
$post_id  = $_POST->uint('p') ?: $_GET->uint('p');
$error = false;

# If the mode is set to topic review then output that review ...
if ('topicreview' === $mode) {
	exit;
}

if (isset($_POST['delete'])) {
	$mode = 'delete';
}

# Was cancel pressed? If so then redirect to the appropriate
# page, no point in continuing with any further checks
if (isset($_POST['cancel'])) {
	if ($post_id) {
		URL::redirect(URL::index("&file=viewtopic&p={$post_id}")."#{$post_id}");
	} else if ($topic_id) {
		URL::redirect(URL::index("&file=viewtopic&t={$topic_id}"));
	} else if ($forum_id) {
		URL::redirect(URL::index("&file=viewforum&f={$forum_id}"));
	} else {
		URL::redirect(URL::index());
	}
}

# Check and set various parameters
$poll_add    = isset($_POST['add_poll_option']);
$poll_delete = isset($_POST['poll_delete']) || ('poll_delete' === $mode);
$preview     = isset($_POST['preview']);
$refresh     = $preview || $poll_add || $poll_delete || isset($_POST['edit_poll_option']);
$submit      = isset($_POST['post']) && !$refresh;

# Here we do various lookups to find topic_id, forum_id, post_id etc.
# Doing it here prevents spoofing (eg. faking forum_id, topic_id or post_id
switch ($mode)
{
	case 'newtopic':
		if (empty($forum_id)) {
			cpg_error($lang['Forum_not_exist'], 404);
		}
		$post = new \Dragonfly\Forums\Post();
		$post->forum_id = $forum_id;
		if (isset($_POST['topictype'])) {
			$post->topic_type = $_POST->uint('topictype');
		}
		$topic = null;
		break;

	case 'reply':
	case 'vote':
		if (empty($topic_id)) {
			cpg_error($lang['No_topic_id'], 404);
		}
		$post = new \Dragonfly\Forums\Post();
		$post->topic_id = $topic_id;
		$post->forum_id = $post->topic->forum_id;
		$topic = $post->topic;
		break;

	case 'quote':
	case 'editpost':
	case 'delete':
	case 'poll_delete':
		if (empty($post_id)) {
			cpg_error($lang['No_post_id'], 404);
		}
		$post = new \Dragonfly\Forums\Post($post_id);
		$topic = $post->topic;
		if ('quote' === $mode) {
			$tmp_post = $post;
			$post = new \Dragonfly\Forums\Post();
			$post->topic_id = $tmp_post->topic_id;
			$post->forum_id = $tmp_post->forum_id;
			$post->message = '[quote="'.$tmp_post->username.'"]'.$tmp_post->message.'[/quote]';
			$mode = 'reply';
			unset($tmp_post);
		}
		break;

	default:
		cpg_error($lang['No_valid_mode'], 404);
}

$forum = $post->forum;

$is_auth = $forum->getUserPermissions();

if (!$is_auth['auth_mod']) {
	if ($forum->isLocked()) {
		cpg_error($lang['Forum_locked'], 403);
	}
	if ($topic && $topic->isLocked()) {
		cpg_error($lang['Topic_locked'], 403);
	}
}

$poll_edit = false;
if ('editpost' === $mode || 'delete' === $mode || $poll_delete) {
	# Can this user edit/delete the post/poll?
	if (!$is_auth['auth_mod']) {
		if ($post->poster_id != $userinfo->id) {
			\Poodle\HTTP\Status::set(403);
			message_die(GENERAL_MESSAGE, ('delete' === $mode) ? $lang['Delete_own_posts'] : $lang['Edit_own_posts']);
		}
		if (!$post->last_post) {
			if ('editpost' === $mode && $board_config['edit_last_post_only']) {
				\Poodle\HTTP\Status::set(403);
				message_die(GENERAL_MESSAGE, $lang['Cannot_edit_replied']);
			}
			if ('delete' === $mode) {
				\Poodle\HTTP\Status::set(403);
				message_die(GENERAL_MESSAGE, $lang['Cannot_delete_replied']);
			}
		}
	}

	if ($post->first_post) {
		if ($post->has_poll) {
			$poll_edit = ($is_auth['auth_mod'] || !$topic->poll->votes);
		} else {
			$poll_edit = $is_auth['auth_pollcreate'];
		}
	}
	if ($poll_delete && !$is_auth['auth_mod'] && !$poll_edit) {
		\Poodle\HTTP\Status::set(403);
		message_die(GENERAL_MESSAGE, $lang['Cannot_delete_poll']);
	}
}

# The user is not authed, if they're not logged in then redirect
# them, else show them an error message
# What auth type do we need to check?
switch ($mode)
{
	case 'newtopic':
		if (\Dragonfly\Forums\Topic::TYPE_ANNOUNCE == $post->topic_type) {
			$is_auth_type = 'auth_announce';
		} else if (\Dragonfly\Forums\Topic::TYPE_STICKY == $post->topic_type) {
			$is_auth_type = 'auth_sticky';
		} else {
			$is_auth_type = 'auth_post';
		}
		break;
	case 'reply':
		$is_auth_type = 'auth_reply';
		break;
	case 'editpost':
		$is_auth_type = 'auth_edit';
		break;
	case 'delete':
	case 'poll_delete':
		$is_auth_type = 'auth_delete';
		break;
	case 'vote':
		$is_auth_type = 'auth_vote';
		break;
}
if (empty($is_auth[$is_auth_type])) {
	if (is_user()) {
		\Poodle\HTTP\Status::set(403);
		message_die(GENERAL_MESSAGE, sprintf($lang['Sorry_'.$is_auth_type], \Dragonfly\Forums\Auth::getLevelName($is_auth[$is_auth_type.'_type'])));
	}
	\URL::redirect(\Dragonfly\Identity::loginURL());
}

if (($poll_delete || 'delete' === $mode) && !isset($_POST['confirm'])) {
	# Confirm deletion
	\Dragonfly\Page::confirm(
		URL::index('&file=posting'),
		$poll_delete ? $lang['Confirm_delete_poll'] : $lang['Confirm_delete'],
		array(
			array('name'=>'p','value'=>$post->id),
			array('name'=>'mode','value'=>$poll_delete ? 'poll_delete' : 'delete'),
		));
	return;
}

if ('vote' === $mode) {
	# Vote in a poll
	$vote_option_id = $_POST->uint('vote_id');
	if ($vote_option_id) {
		$poll = $topic->poll;
		if (isset($poll->options[$vote_option_id])) {
			if ($poll->vote($vote_option_id)) {
				\Poodle\Notify::success($lang['Vote_cast']);
			} else {
				\Poodle\Notify::error($lang['Already_voted']);
			}
		} else {
			\Poodle\Notify::error($lang['No_vote_option']);
		}
	} else {
		\Poodle\Notify::error($lang['No_vote_option']);
	}
	URL::redirect(URL::index("&file=viewtopic&t={$topic->id}"));
	return;
}

// Handle Attachments (Add/Delete/Edit/Show) - This is the first function called from every message handler
require_once('includes/phpBB/class.attachments.main.php');
$post_attachments = new attach_posting();
if (!$refresh) {
	$refresh = !empty($_POST['add_attachment_box']) || !empty($_POST['posted_attachments_box']);
}
// Choose what to display
if ($post_attachments->handle_attachments($mode, $post)) {
	$post_attachments->display_attachment_bodies($post);
}

$notify_user = 0;
if (is_user() && $is_auth['auth_read']) {
	$notify_user = !!$userinfo->notify;
	if ($submit || $refresh) {
		$notify_user = $_POST->bool('notify');
	} else if ($mode != 'newtopic') {
		$notify_user = ($notify_user || $db->query("SELECT topic_id FROM ".TOPICS_WATCH_TABLE."
			WHERE topic_id = {$topic->id} AND user_id = {$userinfo->id}")->num_rows);
	}
}

if ($submit || $refresh || isset($_POST['del_poll_option'])) {
	$post->subject    = $_POST->raw('subject');
	$post->message    = $_POST->raw('message');
	$post->topic_icon = $_POST->uint('topic_icon');
	if ('newtopic' === $mode) {
		$post->archive_id = $_POST->uint('archive_id');
		if ($post->archive_id) {
			$post->subject = "[revived] {$post->subject}";
		}
	} else {
		if (!empty($_POST['quick_quote'])) {
			$post->message = "{$_POST['quick_quote']}\n{$post->message}";
		}
		if (isset($_POST['topictype']) && ($is_auth['auth_sticky'] || $is_auth['auth_announce'])) {
			$post->topic_type = $_POST->uint('topictype');
		}
	}
	$post->enable_bbcode  = !$_POST->bool('disable_bbcode');
	$post->enable_html    = !$_POST->bool('disable_html');
	$post->enable_smilies = !$_POST->bool('disable_smilies');
	$post->enable_sig     = $_POST->bool('attach_sig');
}

if ($submit || isset($_POST['confirm'])) {
	# Submit post (newtopic, edit, reply, etc.)
	$return_message = $return_url = '';

	switch ($mode)
	{
		case 'newtopic':
		case 'reply':
			if (!\Dragonfly\Output\Captcha::validate($_POST)) {
				$error = true;
				\Poodle\Notify::error('CSRF security failed. Are cookies enabled?');
				break;
			}
		case 'editpost':
//			$post->attachment     = false;

			if ($is_auth['auth_pollcreate']) {
				$post->poll_title  = $_POST['poll_title'];
				$post->poll_length = $_POST->uint('poll_length');
				$poll_options = $_POST['poll_option_text'] ?? null;
				if (is_array($poll_options)) {
					foreach ($poll_options as $option_id => $option_text) {
						$option_text = trim($option_text);
						if (!empty($option_text)) {
							$post->poll_options[$option_id] = $option_text;
						}
					}
				}
			}

			try {
				$post->save();
				$topic = $post->topic;
				$return_url = $post->url;
				$return_message = $lang['Stored'];
			} catch (\Exception $e) {
				$error = true;
				\Poodle\Notify::error($e->getMessage());
			}
			break;

		case 'delete':
			$post->delete();
			$return_message = $lang['Deleted'];
			if ($post->first_post && $post->last_post) {
				$return_url = URL::index("&file=viewforum&f={$forum->id}");
			} else {
				$return_url = URL::index("&file=viewtopic&t={$topic->id}");
			}
			break;

		case 'poll_delete':
			\Dragonfly\Forums\Poll::deleteFromTopics($topic->id);
			$return_url = URL::index("&file=viewtopic&t={$topic->id}");
			$return_message = $lang['Poll_delete'];
			break;
	}

	if (!$error) {
		$post_attachments->save_attachments($mode, $post);
		if (!$poll_delete && 'delete' !== $mode) {
			\Dragonfly\Forums\Notify::users($mode, $post, $notify_user);
		}
		\Poodle\Notify::success($return_message);
		\URL::redirect($return_url);
	}
}

$poll = null;
if ($is_auth['auth_pollcreate']) {
	if ('newtopic' === $mode) {
		$poll = new \Dragonfly\Forums\Poll();
	} else if ($post->first_post) {
		$poll = $topic->poll ?: new \Dragonfly\Forums\Poll();
	}
}

$template->POST_PREVIEW_BOX = false;

if ($refresh || isset($_POST['del_poll_option']) || $error) {
	if ($poll) {
		$poll->title = empty($_POST['poll_title']) ? '' : $_POST['poll_title'];
		$poll->length = $_POST->uint('poll_length');
		$poll->options = array();
		if (!empty($_POST['poll_option_text'])) {
			foreach ($_POST['poll_option_text'] as $option_id => $option_text) {
				if (isset($_POST['del_poll_option'][$option_id])) {
					unset($poll->options[$option_id]);
				} else if (!empty($option_text)) {
					$poll->options[$option_id] = $option_text;
				}
			}
		}
		if (isset($poll_add) && !empty($_POST['add_poll_option_text'])) {
			$poll->options[] = $_POST['add_poll_option_text'];
		}
	}

	if ($preview) {
		$images = get_forums_images();
		$template->assign_vars(array(
			'POST_DATE' => $lang->date($board_config['default_dateformat']),
			'MINI_POST_IMG' => $images['icon_minipost'],
		));
		$template->POST_PREVIEW_BOX = true;
	}
}
else if ('reply' === $mode) {
	$subject = $topic->title;
	if (strlen($subject) && 0 !== strpos($subject, 'Re:')) {
		$subject = substr('Re: '.$subject, 0, 60);
	}
	$post->subject = $subject;
	unset($subject);
}

# Signature toggle selection
$user_sig = ('editpost' === $mode) ? !!$post->user_sig : !!$userinfo->sig;
$template->show_signature_checkbox = ($board_config['allow_sig'] && $user_sig);

# Notify checkbox - only show if user is logged in
$template->show_notify_checkbox = (is_user() && $is_auth['auth_read'] && ('editpost' != $mode  || $post->poster_id != \Dragonfly\Identity::ANONYMOUS_ID));

# Delete selection
$template->show_delete_checkbox = ('editpost' === $mode && ($is_auth['auth_mod'] || ($is_auth['auth_delete'] && $post->last_post && (!$post->has_poll || $poll_edit))));

\Dragonfly\Page::title($forum->name, false);
$hidden_form_fields = array(array('name'=>'mode','value'=>$mode));

switch ($mode)
{
	case 'newtopic':
		\Dragonfly\Page::title($lang['Post_a_new_topic'], false);
		//\Dragonfly\Page::title($forum->name.' '._BC_DELIM.' '.$lang['Post_a_new_topic']);
		$hidden_form_fields[] = array('name'=>'f','value'=>$forum->id);
		break;

	case 'reply':
		\Dragonfly\Page::title($lang['Post_a_reply'], false);
		\Dragonfly\Page::title($post->subject, false);
		$hidden_form_fields[] = array('name'=>'t','value'=>$topic->id);
		break;

	case 'editpost':
		\Dragonfly\Page::title($lang['Edit_Post'], false);
		\Dragonfly\Page::title($post->subject, false);
		$hidden_form_fields[] = array('name'=>'p','value'=>$post->id);
		break;
}

\Dragonfly\BBCode::pushHeaders(true);

# Output the data to the template
$template->assign_vars(array(
	'FORUM_NAME' => $forum->name,
	'FORUM_DESC' => $forum->desc,
	'U_VIEW_FORUM' => URL::index("&file=viewforum&f={$forum->id}"),
	'S_POST_ACTION' => URL::index('&file=posting'),
	'HIDDEN_FORM_FIELDS' => $hidden_form_fields,
));

$template->board_config = $board_config;
$template->topic_icon_options = array();
$template->forum_post = $post;
$template->notify_user = $notify_user;
# Topic type selection
$template->topic_types = array();
if ($post->first_post && ($is_auth['auth_sticky'] || $is_auth['auth_announce'])) {
	$template->topic_types[] = array(
		'id' => \Dragonfly\Forums\Topic::TYPE_NORMAL,
		'label' => $lang['Post_Normal'],
		'current' => \Dragonfly\Forums\Topic::TYPE_NORMAL == $post->topic_type
	);
	if ($is_auth['auth_sticky']) {
		$template->topic_types[] = array(
			'id' => \Dragonfly\Forums\Topic::TYPE_STICKY,
			'label' => $lang['Post_Sticky'],
			'current' => \Dragonfly\Forums\Topic::TYPE_STICKY == $post->topic_type
		);
	}
	if ($is_auth['auth_announce']) {
		$template->topic_types[] = array(
			'id' => \Dragonfly\Forums\Topic::TYPE_ANNOUNCE,
			'label' => $lang['Post_Announcement'],
			'current' => \Dragonfly\Forums\Topic::TYPE_ANNOUNCE == $post->topic_type
		);
	}
}

# if this forum has icons
$set_topic_icon = ($forum->id && ('newtopic' === $mode || ('editpost' === $mode && $post->first_post)));
if ($set_topic_icon) {
	$topic_icons = $db->query("SELECT icon_id id, icon_name name, icon_url url
	FROM ".TOPIC_ICONS_TABLE." WHERE forum_id = {$forum->id} OR forum_id < 1");
	#if new topic, or we are editing the first post of a topic
	$topic_icon_post = $_POST->uint('topic_icon') ?: $post->topic_icon;
	foreach ($topic_icons as $icon) {
		$icon['url'] = DF_STATIC_DOMAIN.$icon['url'];
		$icon['current'] = ($icon['id'] == $topic_icon_post);
		$template->topic_icon_options[] = $icon;
	}
}

# Poll
$template->topic_poll = $poll;
if ($poll) {
	$template->allow_poll_delete = ($poll->id && $poll_edit);
	$template->poll_options = array();
	foreach ($poll->options as $option_id => $option) {
		$template->poll_options[] = array(
			'id' => $option_id,
			'text' => is_array($option) ? $option['text'] : $option
		);
	}
}

# Topic review
$template->topic_posts = array();
if ('reply' === $mode && $is_auth['auth_read']) {
	// Define censored word matches
	if (empty($orig_word) && empty($replacement_word)) {
		$orig_word = $replacement_word = array();
		obtain_word_list($orig_word, $replacement_word);
	}

	// Go ahead and pull all data for this topic
	$result = $db->query("SELECT
		u.username,
		u.user_id,
		p.post_username,
		p.enable_bbcode,
		p.enable_html,
		p.enable_smilies,
		p.post_time,
		pt.post_text,
		pt.post_subject
	FROM " . POSTS_TABLE . " p, " . $db->TBL->users . " u, " . POSTS_TEXT_TABLE . " pt
	WHERE p.topic_id = {$topic->id}
	  AND p.poster_id = u.user_id
	  AND p.post_id = pt.post_id
	ORDER BY p.post_time DESC
	LIMIT {$board_config['posts_per_page']}");
	while ($row = $result->fetch_assoc()) {
		// Handle anon users posting with usernames
		$poster = $row['username'];
		if (\Dragonfly\Identity::ANONYMOUS_ID == $row['user_id']) {
			$poster = $row['post_username'] ? $row['post_username'] : $template->L10N['Guest'];
		}

		$post = new \Dragonfly\Forums\Post();
		$post->message        = $row['post_text'];
		$post->enable_bbcode  = $row['enable_bbcode'];
		$post->enable_html    = $row['enable_html'];
		$post->enable_smilies = $row['enable_smilies'];
		$message = $post->message2html();

		if (is_countable($orig_word) ? count($orig_word) : 0) {
			$row['post_subject'] = preg_replace($orig_word, $replacement_word, $row['post_subject']);
			$message = preg_replace($orig_word, $replacement_word, $message);
		}

		$template->topic_posts[] = array(
			'author'  => $poster,
			'date'    => $template->L10N->date($board_config['default_dateformat'], $row['post_time']),
			'subject' => $row['post_subject'],
			'message' => $message,
		);
	}
	$result->free();
}

# Include page header
require_once('includes/phpBB/page_header.php');
make_jumpbox('viewforum');
$template->display('forums/posting_form');
