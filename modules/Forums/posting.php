<?php
/***************************************************************************
 *				posting.php
 *				-------------------
 *	 begin		: Saturday, Feb 13, 2001
 *	 copyright	: (C) 2001 The phpBB Group
 *	 email		: support@phpbb.com
 *
 *	 $Id: posting.php,v 9.20 2007/12/12 12:54:23 nanocaiordo Exp $
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
if (!defined('CPG_NUKE')) { exit; }
require_once('modules/'.$module_name.'/nukebb.php');
require_once('includes/nbbcode.php');
require_once('includes/phpBB/functions_post.php');

//
// Check and set various parameters
//
$params = array('submit' => 'post', 'preview' => 'preview', 'delete' => 'delete', 'poll_delete' => 'poll_delete', 'poll_add' => 'add_poll_option', 'poll_edit' => 'edit_poll_option', 'mode' => 'mode');
foreach ($params as $var => $param) {
	$$var = (isset($_POST[$param]) ? htmlprepare($_POST[$param]) : (isset($_GET[$param]) ? htmlprepare($_GET[$param]) : ''));
}
$confirm = isset($_POST['confirm']);

$params = array('forum_id' => POST_FORUM_URL, 'topic_id' => POST_TOPIC_URL, 'post_id' => POST_POST_URL);
foreach ($params as $var => $param) {
	$$var = (isset($_POST[$param]) ? intval($_POST[$param]) : (isset($_GET[$param]) ? intval($_GET[$param]) : 0));
}

$refresh = $preview || $poll_add || $poll_edit || $poll_delete;

//
// Set topic type
//
$topic_type = ( !empty($_POST['topictype']) ) ? intval($_POST['topictype']) : POST_NORMAL;

//
// If the mode is set to topic review then output
// that review ...
//
if ( $mode == 'topicreview' ) {
	require_once('includes/phpBB/topic_review.php');
	topic_review($topic_id, false);
	exit;
}

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_POSTING);
init_userprefs($userdata);
//
// End session management
//

//
// Was cancel pressed? If so then redirect to the appropriate
// page, no point in continuing with any further checks
//
if ( isset($_POST['cancel']) ) {
	if ( $post_id ) {
	url_redirect(getlink('&file=viewtopic&'.POST_POST_URL."=$post_id")."#$post_id");
	} else if ( $topic_id ) {
	url_redirect(getlink('&file=viewtopic&'.POST_TOPIC_URL."=$topic_id"));
	} else if ( $forum_id ) {
	url_redirect(getlink('&file=viewforum&'.POST_FORUM_URL."=$forum_id"));
	} else {
	url_redirect(getlink());
	}
}

//
// What auth type do we need to check?
//
$is_auth = array();
switch( $mode )
{
	case 'newtopic':
		if ( $topic_type == POST_ANNOUNCE ) {
			$is_auth_type = 'auth_announce';
		} else if ( $topic_type == POST_STICKY ) {
			$is_auth_type = 'auth_sticky';
		} else {
			$is_auth_type = 'auth_post';
		}
		break;
	case 'reply':
	case 'quote':
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
	case 'topicreview':
		$is_auth_type = 'auth_read';
		break;
	default:
		message_die(GENERAL_MESSAGE, $lang['No_post_mode']);
		break;
}

//
// Here we do various lookups to find topic_id, forum_id, post_id etc.
// Doing it here prevents spoofing (eg. faking forum_id, topic_id or post_id
//
$error_msg = '';
$post_data = array();
switch ( $mode )
{
	case 'newtopic':
		if (empty($forum_id)) {
			message_die(GENERAL_MESSAGE, $lang['Forum_not_exist']);
		}
		$sql = "SELECT * FROM ".FORUMS_TABLE." WHERE forum_id = $forum_id";
		break;

	case 'reply':
	case 'vote':
		if (empty($topic_id)) {
			message_die(GENERAL_MESSAGE, $lang['No_topic_id']);
		}
		$sql = "SELECT f.*, t.topic_status, t.topic_title FROM ".FORUMS_TABLE." f, ".TOPICS_TABLE." t
			WHERE t.topic_id = $topic_id AND f.forum_id = t.forum_id";
		break;

	case 'quote':
	case 'editpost':
	case 'delete':
	case 'poll_delete':
		if (empty($post_id)) {
			message_die(GENERAL_MESSAGE, $lang['No_post_id']);
		}
		$select_sql = ( !$submit ) ? ", t.topic_title, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_sig, p.post_username, pt.post_subject, pt.post_text, u.username, u.user_id, u.user_sig" : '';
		$from_sql = ( !$submit ) ? ", ".POSTS_TEXT_TABLE." pt, ".USERS_TABLE." u" : '';
		$where_sql = ( !$submit ) ? "AND pt.post_id = p.post_id AND u.user_id = p.poster_id" : '';
		$sql = "SELECT f.*, t.topic_id, t.topic_status, t.topic_type, t.topic_first_post_id, t.topic_last_post_id, t.topic_vote, p.post_id, p.poster_id, t.icon_id".$select_sql."
			FROM ".POSTS_TABLE." p, ".TOPICS_TABLE." t, ".FORUMS_TABLE." f".$from_sql."
			WHERE p.post_id = $post_id
				AND t.topic_id = p.topic_id
				AND f.forum_id = p.forum_id
				$where_sql";
		break;

	default:
		message_die(GENERAL_MESSAGE, $lang['No_valid_mode']);
}

if ( $result = $db->sql_query($sql) )
{
	$post_info = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	$forum_id = $post_info['forum_id'];
	$forum_name = $post_info['forum_name'];
	$forum_desc = $post_info['forum_desc'];


	$is_auth = auth(AUTH_ALL, $forum_id, $userdata, $post_info);

	if ( $post_info['forum_status'] == FORUM_LOCKED && !$is_auth['auth_mod']) {
	   message_die(GENERAL_MESSAGE, $lang['Forum_locked']);
	} else if ( $mode != 'newtopic' && $post_info['topic_status'] == TOPIC_LOCKED && !$is_auth['auth_mod']) {
	   message_die(GENERAL_MESSAGE, $lang['Topic_locked']);
	}

	if ( $mode == 'editpost' || $mode == 'delete' || $mode == 'poll_delete' ) {
		$topic_id = $post_info['topic_id'];
		$post_data['poster_post'] = ( $post_info['poster_id'] == $userdata['user_id'] ) ? true : false;
		$post_data['first_post'] = ( $post_info['topic_first_post_id'] == $post_id ) ? true : false;
		$post_data['last_post'] = ( $post_info['topic_last_post_id'] == $post_id ) ? true : false;
		$post_data['last_topic'] = ( $post_info['forum_last_post_id'] == $post_id ) ? true : false;
		$post_data['has_poll'] = ( $post_info['topic_vote'] ) ? true : false;
		$post_data['topic_type'] = $post_info['topic_type'];
		$post_data['poster_id'] = $post_info['poster_id'];
		$post_data['icon_id'] = $post_info['icon_id'];
		$poll_title = $poll_length = '';
		if ( $post_data['first_post'] && $post_data['has_poll'] ) {
			$sql = "SELECT *
				FROM ".VOTE_DESC_TABLE." vd, ".VOTE_RESULTS_TABLE." vr
				WHERE vd.topic_id = $topic_id
					AND vr.vote_id = vd.vote_id
				ORDER BY vr.vote_option_id";
			$result = $db->sql_query($sql);
			$poll_options = array();
			$poll_results_sum = 0;
			if ( $row = $db->sql_fetchrow($result) ) {
				$poll_title = $row['vote_text'];
				$poll_id = $row['vote_id'];
				$poll_length = $row['vote_length'] / 86400;
				do {
					$poll_options[$row['vote_option_id']] = $row['vote_option_text'];
					$poll_results_sum += $row['vote_result'];
				}
				while ( $row = $db->sql_fetchrow($result) );
				$db->sql_freeresult($result);
			}
			$post_data['edit_poll'] = ( ( !$poll_results_sum || $is_auth['auth_mod'] ) && $post_data['first_post'] ) ? true : 0;
		} else {
			$post_data['edit_poll'] = ($post_data['first_post'] && $is_auth['auth_pollcreate']) ? true : false;
		}

		//
		// Can this user edit/delete the post/poll?
		//
		if ($post_info['poster_id'] != $userdata['user_id'] && !$is_auth['auth_mod']) {
			$message = ($delete || $mode == 'delete') ? $lang['Delete_own_posts'] : $lang['Edit_own_posts'];
			$message .= '<br /><br />'.sprintf($lang['Click_return_topic'], '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id").'">', '</a>');
			message_die(GENERAL_MESSAGE, $message);
		} else if ( !$post_data['last_post'] && !$is_auth['auth_mod'] && ( $mode == 'delete' || $delete ) ) {
			message_die(GENERAL_MESSAGE, $lang['Cannot_delete_replied']);
		} else if ( !$post_data['edit_poll'] && !$is_auth['auth_mod'] && ( $mode == 'poll_delete' || $poll_delete ) ) {
			message_die(GENERAL_MESSAGE, $lang['Cannot_delete_poll']);
		}
	} else {
		if ( $mode == 'quote' ) {
			$topic_id = $post_info['topic_id'];
		}
		$post_data['first_post'] = ( $mode == 'newtopic' ) ? true : 0;
		$post_data['last_post'] = $post_data['has_poll'] = $post_data['edit_poll'] = false;
	}
} else {
	message_die(GENERAL_MESSAGE, $lang['No_such_post']);
}

//
// The user is not authed, if they're not logged in then redirect
// them, else show them an error message
//
if (!$is_auth[$is_auth_type]) {
	if (is_user()) {
		message_die(GENERAL_MESSAGE, sprintf($lang['Sorry_'.$is_auth_type], $is_auth[$is_auth_type."_type"]));
	}
	url_redirect(getlink('Your_Account'), true);
}

//
// Set toggles for various options
//
if ( !$board_config['allow_html'] ) {
	$html_on = 0;
} else {
	$html_on = ($submit || $refresh) ? ( ( !empty($_POST['disable_html']) ) ? 0 : TRUE ) : ( ( $userdata['user_id'] == ANONYMOUS ) ? $board_config['allow_html'] : $userdata['user_allowhtml'] );
}

if ( !$board_config['allow_bbcode'] ) {
	$bbcode_on = 0;
} else {
	$bbcode_on = ($submit || $refresh) ? ( ( !empty($_POST['disable_bbcode']) ) ? 0 : TRUE ) : ( ( $userdata['user_id'] == ANONYMOUS ) ? $board_config['allow_bbcode'] : $userdata['user_allowbbcode'] );
}

if ( !$board_config['allow_smilies'] ) {
	$smilies_on = 0;
} else {
	$smilies_on = ($submit || $refresh) ? ( ( !empty($_POST['disable_smilies']) ) ? 0 : TRUE ) : ( ( $userdata['user_id'] == ANONYMOUS ) ? $board_config['allow_smilies'] : $userdata['user_allowsmile'] );
}

if (($submit || $refresh) && $is_auth['auth_read']) {
	$notify_user = ( !empty($_POST['notify']) ) ? TRUE : 0;
} else {
	if ($mode != 'newtopic' && is_user() && $is_auth['auth_read']) {
		$sql = "SELECT topic_id FROM ".TOPICS_WATCH_TABLE."
			WHERE topic_id = $topic_id AND user_id = ".$userdata['user_id'];
		$result = $db->sql_query($sql);
		$notify_user = ( $db->sql_fetchrow($result) ) ? TRUE : $userdata['user_notify'];
		$db->sql_freeresult($result);
	} else {
		$notify_user = (is_user() && $is_auth['auth_read']) ? $userdata['user_notify'] : 0;
	}
}

$attach_sig = ($submit || $refresh) ? ( ( !empty($_POST['attach_sig']) ) ? TRUE : 0 ) : ( ( $userdata['user_id'] == ANONYMOUS ) ? 0 : $userdata['user_attachsig'] );

$username = $userdata['username'];
$template->assign_vars(array(
	'POST_PREVIEW_BOX' => '',
	'ERROR_BOX' => '',
	'POLLBOX' => '',
	'TOPIC_REVIEW_BOX' => ''
));
	global $attachment_mod;
	$attachment_mod['posting'] = new attach_posting();
	$attachment_mod['posting']->posting_attachment_mod();

// --------------------
//	What shall we do?
//
if (($delete || $poll_delete || $mode == 'delete') && !$confirm) {
	//
	// Confirm deletion
	//
	$s_hidden_fields = '<input type="hidden" name="'.POST_POST_URL.'" value="'.$post_id.'" />';
	$s_hidden_fields .= ( $delete || $mode == "delete" ) ? '<input type="hidden" name="mode" value="delete" />' : '<input type="hidden" name="mode" value="poll_delete" />';
	$l_confirm = ( $delete || $mode == 'delete' ) ? $lang['Confirm_delete'] : $lang['Confirm_delete_poll'];
	//
	// Output confirmation page
	//
	require_once('includes/phpBB/page_header.php');
	$template->assign_vars(array(
		'MESSAGE_TITLE' => $lang['Information'],
		'MESSAGE_TEXT' => $l_confirm,

		'L_YES' => $lang['Yes'],
		'L_NO' => $lang['No'],

		'S_CONFIRM_ACTION' => getlink("&amp;file=posting"),
		'S_HIDDEN_FIELDS' => $s_hidden_fields)
	);
	$template->set_filenames(array('body' => 'confirm_body.html'));
	require_once('includes/phpBB/page_tail.php');
} else if ($mode == 'vote') {
	//
	// Vote in a poll
	//
	if (!empty($_POST['vote_id'])) {
		$vote_option_id = intval($_POST['vote_id']);
		$sql = 'SELECT vd.vote_id FROM '.VOTE_DESC_TABLE.' vd, '.VOTE_RESULTS_TABLE." vr
			WHERE vd.topic_id = $topic_id
				AND vr.vote_id = vd.vote_id
				AND vr.vote_option_id = $vote_option_id
			GROUP BY vd.vote_id";
		$result = $db->sql_query($sql);
		if ($vote_info = $db->sql_fetchrow($result)) {
			$vote_id = $vote_info['vote_id'];
			$sql = 'SELECT * FROM '.VOTE_USERS_TABLE."
				WHERE vote_id = $vote_id
					AND vote_user_id = ".$userdata['user_id'];
			$result2 = $db->sql_query($sql);
			if (!($row = $db->sql_fetchrow($result2))) {
				$sql = 'UPDATE '.VOTE_RESULTS_TABLE."
					SET vote_result = vote_result + 1
					WHERE vote_id = $vote_id
						AND vote_option_id = $vote_option_id";
				$db->sql_query($sql);
				$sql = 'INSERT INTO '.VOTE_USERS_TABLE." (vote_id, vote_user_id, vote_user_ip)
					VALUES ('$vote_id', ".$userdata['user_id'].", ".$userinfo['user_ip'].")";
				$db->sql_query($sql);
				$message = $lang['Vote_cast'];
			} else {
				$message = $lang['Already_voted'];
			}
			$db->sql_freeresult($result2);
		}
		else {
			$message = $lang['No_vote_option'];
		}
		$db->sql_freeresult($result);

		url_refresh(getlink("&file=viewtopic&".POST_TOPIC_URL."=$topic_id"));
		$message .=	 '<br /><br />'.sprintf($lang['Click_view_message'], '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id").'">', '</a>');
		message_die(GENERAL_MESSAGE, $message);
	} else {
		url_redirect(getlink("&file=viewtopic&".POST_TOPIC_URL."=$topic_id", true));
	}
} else if ($submit || $confirm) {
	//
	// Submit post/vote (newtopic, edit, reply, etc.)
	//
	$return_message = $return_meta = '';

	switch ($mode)
	{
		case 'editpost':
		case 'newtopic':
		case 'reply':
			$topic_icon = ( !empty($_POST['topic_icon']) ) ? intval($_POST['topic_icon']) : 0;
			$username = ( !empty($_POST['username']) ) ? $_POST['username'] : '';
			$subject = ( !empty($_POST['subject']) ) ? $_POST['subject'] : '';
			$message = ( !empty($_POST['message']) ) ? $_POST['message'] : '';
			if (isset($_POST['quick_quote']) && !empty($_POST['quick_quote'])) {
				$message = $_POST['quick_quote'].$message;
			}
			$poll_title = ( isset($_POST['poll_title']) && $is_auth['auth_pollcreate'] ) ? $_POST['poll_title'] : '';
			$poll_options = ( isset($_POST['poll_option_text']) && $is_auth['auth_pollcreate'] ) ? $_POST['poll_option_text'] : '';
			$poll_length = ( isset($_POST['poll_length']) && $is_auth['auth_pollcreate'] ) ? $_POST['poll_length'] : '';

			prepare_post($mode, $post_data, $bbcode_on, $html_on, $error_msg, $username, $subject, $message, $poll_title, $poll_options, $poll_length);

			if ($error_msg == '') {
				$topic_type = ($topic_type != $post_data['topic_type'] && !$is_auth['auth_sticky'] && !$is_auth['auth_announce']) ? $post_data['topic_type'] : $topic_type;
				submit_post($mode, $post_data, $return_message, $return_meta, $forum_id, $topic_id, $post_id, $poll_id, $topic_type, $bbcode_on, $html_on, $smilies_on, $attach_sig, $username, $subject, $message, $poll_title, $poll_options, $poll_length, $topic_icon);
			}
			break;

		case 'delete':
		case 'poll_delete':
			delete_post($mode, $post_data, $return_message, $return_meta, $forum_id, $topic_id, $post_id, $poll_id);
			break;
	}

	if ($error_msg == '') {
		if ($mode != 'editpost') {
			$user_id = ($mode == 'reply' || $mode == 'newtopic') ? $userdata['user_id'] : $post_data['poster_id'];
			update_post_stats($mode, $post_data, $forum_id, $topic_id, $post_id, $user_id);
		}
//		if (defined('BBAttach_mod')) {
			$attachment_mod['posting']->insert_attachment($post_id);
		if ($error_msg == '' && $mode != 'poll_delete') {
			user_notification($mode, $post_data, $post_info['topic_title'], $forum_id, $topic_id, $post_id, $notify_user);
		}
		if ($mode == 'newtopic' || $mode == 'reply') {
			$tracking_topics = isset($CPG_SESS[$module_name]['track_topics']) ? $CPG_SESS[$module_name]['track_topics'] : array();
			$tracking_forums = isset($CPG_SESS[$module_name]['track_forums']) ? $CPG_SESS[$module_name]['track_forums'] : array();
			if ( count($tracking_topics) + count($tracking_forums) == 100 && empty($tracking_topics[$topic_id]) ) {
				asort($tracking_topics);
				unset($tracking_topics[key($tracking_topics)]);
			}
			$CPG_SESS[$module_name]['track_topics'][$topic_id] = gmtime();
		}
		$template->assign_vars(array('META' => $return_meta));
		message_die(GENERAL_MESSAGE, $return_message);
	}
}

if ($refresh || isset($_POST['del_poll_option']) || $error_msg != '')
{
	$username = (!empty($_POST['username'])) ? htmlprepare($_POST['username']) : '';
	$subject = (!empty($_POST['subject'])) ? htmlprepare($_POST['subject']) : '';
	$message = (!empty($_POST['message'])) ? $_POST['message'] : '';
	if (isset($_POST['quick_quote']) && !empty($_POST['quick_quote'])) {
		$message = $_POST['quick_quote']."\n$message";
	}

	$poll_title = (!empty($_POST['poll_title'])) ? htmlprepare($_POST['poll_title']) : '';
	$poll_length = (isset($_POST['poll_length'])) ? max(0, intval($_POST['poll_length'])) : 0;

	$poll_options = array();
	if ( !empty($_POST['poll_option_text']) ) {
		foreach ($_POST['poll_option_text'] as $option_id => $option_text) {
			if (isset($_POST['del_poll_option'][$option_id])) {
				unset($poll_options[$option_id]);
			} elseif (!empty($option_text)) {
				$poll_options[$option_id] = htmlprepare($option_text);
			}
		}
	}

	if ( isset($poll_add) && !empty($_POST['add_poll_option_text']) ) {
		$poll_options[] = htmlprepare($_POST['add_poll_option_text']);
	}

	if ($mode == 'newtopic' || $mode == 'reply') {
		$user_sig = ($userdata['user_sig'] != '' && $board_config['allow_sig']) ? $userdata['user_sig'] : '';
	} else if ( $mode == 'editpost' ) {
		$user_sig = ($post_info['user_sig'] != '' && $board_config['allow_sig']) ? $post_info['user_sig'] : '';
	}

	if ($preview) {
		$orig_word = array();
		$replacement_word = array();
		obtain_word_list($orig_word, $replacement_word);

		$preview_message = message_prepare($message, $html_on, $bbcode_on);
		$preview_subject = $subject;
		$preview_username = $username;

		//
		// Finalise processing as per viewtopic
		//
		if (!$html_on) {
			if ($user_sig != '' || !$userdata['user_allowhtml']) {
				$user_sig = BBCode::encode_html($user_sig);
			}
		}
		if ($attach_sig && $user_sig != '') {
			$user_sig = decode_bbcode($user_sig, 1, false);
		}

		if ($bbcode_on) {
			$preview_message = decode_bbcode($preview_message, 1, true);
		}

		if (!empty($orig_word)) {
			$preview_username = (!empty($username)) ? preg_replace($orig_word, $replacement_word, $preview_username) : '';
			$preview_subject = (!empty($subject)) ? preg_replace($orig_word, $replacement_word, $preview_subject) : '';
			$preview_message = (!empty($preview_message)) ? preg_replace($orig_word, $replacement_word, $preview_message) : '';
		}

		if ($user_sig != '') {
			$user_sig = make_clickable($user_sig);
		}
		$preview_message = make_clickable($preview_message);

		if ($smilies_on) {
			if ($userdata['user_allowsmile'] && $user_sig != '') {
				$user_sig = set_smilies($user_sig);
			}
			$preview_message = set_smilies($preview_message);
		}

		if ($attach_sig && $user_sig != '') {
			$preview_message = $preview_message.'<br /><br />_________________<br />'.$user_sig;
		}

		$template->set_filenames(array('preview' => 'forums/posting_preview.html'));
//		if (defined('BBAttach_mod')) {
		$attachment_mod['posting']->preview_attachments();
		$template->assign_vars(array(
			'TOPIC_TITLE' => $preview_subject,
			'POST_SUBJECT' => $preview_subject,
			'POSTER_NAME' => $preview_username,
			'POST_DATE' => create_date($board_config['default_dateformat'], gmtime()),
			'MESSAGE' => $preview_message,

			'L_POST_SUBJECT' => $lang['Post_subject'],
			'L_PREVIEW' => $lang['Preview'],
			'L_POSTED' => $lang['Posted'],
			'L_POST' => $lang['Post'])
		);
		$template->assign_var_from_handle('POST_PREVIEW_BOX', 'preview');
	} else if ( $error_msg != '' ) {
		$template->set_filenames(array('reg_header' => 'forums/error_body.html'));
		$template->assign_vars(array('ERROR_MESSAGE' => $error_msg));
		$template->assign_var_from_handle('ERROR_BOX', 'reg_header');
	}
	$message = htmlprepare($message);
} else {
	//
	// User default entry point
	//
	if ( $mode == 'newtopic' ) {
		$user_sig = ($userdata['user_sig'] != '') ? $userdata['user_sig'] : '';
		$username = (is_user()) ? $userdata['username'] : '';
		$poll_title = $poll_length = $subject = $message = '';
	} else if ( $mode == 'reply' ) {
		$user_sig = ($userdata['user_sig'] != '') ? $userdata['user_sig'] : '';
		$username = (is_user()) ? $userdata['username'] : '';
		// begin Automatic Subject on Reply mod
		$subject = htmlunprepare($post_info['topic_title']);
		if ( !preg_match('/^Re:/', $subject) && strlen($subject) > 0) {
			$subject = substr('Re: '.$subject, 0, 60);
		}
		$subject = htmlprepare($subject);
		// end Automatic Subject on Reply mod
		$message = '';
	} else if ( $mode == 'quote' || $mode == 'editpost' ) {
		$subject = ( $post_data['first_post'] ) ? $post_info['topic_title'] : $post_info['post_subject'];
		$message = $post_info['post_text'];
		if ( $mode == 'editpost' ) {
			$attach_sig = ( $post_info['enable_sig'] && $post_info['user_sig'] != '' ) ? TRUE : 0;
			$user_sig = $post_info['user_sig'];
			$html_on = ( $post_info['enable_html'] ) ? true : false;
			$bbcode_on = ( $post_info['enable_bbcode'] ) ? true : false;
			$smilies_on = ( $post_info['enable_smilies'] ) ? true : false;
		} else {
			$attach_sig = ( $userdata['user_attachsig'] ) ? TRUE : 0;
			$user_sig = $userdata['user_sig'];
		}
		$message = str_replace('<', '&lt;', $message);
		$message = str_replace('>', '&gt;', $message);
		$message = str_replace('<br />', "\n", $message);
		if ( $mode == 'quote' ) {
			$orig_word = array();
			$replacement_word = array();
			obtain_word_list($orig_word, $replace_word);
			$msg_date = create_date($board_config['default_dateformat'], $postrow['post_time']);
			// Use trim to get rid of spaces placed there by MS-SQL 2000
			$quote_username = ( trim($post_info['post_username']) != '' ) ? $post_info['post_username'] : $post_info['username'];
			$message = '[quote="'.$quote_username.'"]'.$message.'[/quote]';
			if ( !empty($orig_word) ) {
				$subject = ( !empty($subject) ) ? preg_replace($orig_word, $replace_word, $subject) : '';
				$message = ( !empty($message) ) ? preg_replace($orig_word, $replace_word, $message) : '';
			}
			if ( !preg_match('/^Re:/', $subject) && strlen($subject) > 0 ) {
				$subject = 'Re: '.$subject;
			}
			$mode = 'reply';
		} else {
			$username = ( $post_info['user_id'] == ANONYMOUS && !empty($post_info['post_username']) ) ? $post_info['post_username'] : '';
		}
	}
}

//
// Signature toggle selection
//
if( $user_sig != '' ) {
	$template->assign_block_vars('switch_signature_checkbox', array());
}

//
// HTML toggle selection
//
if ( $board_config['allow_html'] ) {
	$html_status = $lang['HTML_is_ON'];
	$template->assign_block_vars('switch_html_checkbox', array());
} else {
	$html_status = $lang['HTML_is_OFF'];
}

//
// BBCode toggle selection
//
if ( $board_config['allow_bbcode'] ) {
	$bbcode_status = $lang['BBCode_is_ON'];
	$template->assign_block_vars('switch_bbcode_checkbox', array());
} else {
	$bbcode_status = $lang['BBCode_is_OFF'];
}

//
// Smilies toggle selection
//
if ($board_config['allow_smilies']) {
	$smilies_status = $lang['Smilies_are_ON'];
	$template->assign_block_vars('switch_smilies_checkbox', array());
} else {
	$smilies_status = $lang['Smilies_are_OFF'];
}

if (!is_user() || ($mode == 'editpost' && $post_info['poster_id'] == ANONYMOUS)) {
	$template->assign_block_vars('switch_username_select', array());
}

//
// Notify checkbox - only show if user is logged in
//
if (is_user() && $is_auth['auth_read']) {
	if ($mode != 'editpost' || ($mode == 'editpost' && $post_info['poster_id'] != ANONYMOUS)) {
		$template->assign_block_vars('switch_notify_checkbox', array());
	}
}

//
// Delete selection
//
if ( $mode == 'editpost' && ( ( $is_auth['auth_delete'] && $post_data['last_post'] && ( !$post_data['has_poll'] || $post_data['edit_poll'] ) ) || $is_auth['auth_mod'] ) ) {
	$template->assign_block_vars('switch_delete_checkbox', array());
}

//
// Topic type selection
//
$topic_type_toggle = '';
if(!isset($post_data['topic_type'])) $post_data['topic_type'] = 0;
if ( $mode == 'newtopic' || ( $mode == 'editpost' && $post_data['first_post'] ) ) {
	$template->assign_block_vars('switch_type_toggle', array());
	if( $is_auth['auth_sticky'] ) {
		$topic_type_toggle .= '<input type="radio" name="topictype" value="'.POST_STICKY.'"';
		if ($post_data['topic_type'] == POST_STICKY || $topic_type == POST_STICKY ) {
			$topic_type_toggle .= ' checked="checked"';
		}
		$topic_type_toggle .= ' /> '.$lang['Post_Sticky'].'&nbsp;&nbsp;';
	}
	if( $is_auth['auth_announce'] ) {
		$topic_type_toggle .= '<input type="radio" name="topictype" value="'.POST_ANNOUNCE.'"';
		if ( $post_data['topic_type'] == POST_ANNOUNCE || $topic_type == POST_ANNOUNCE ) {
			$topic_type_toggle .= ' checked="checked"';
		}
		$topic_type_toggle .= ' /> '.$lang['Post_Announcement'].'&nbsp;&nbsp;';
	}
	if ( $topic_type_toggle != '' ) {
		$topic_type_toggle = $lang['Post_topic_as'].': <input type="radio" name="topictype" value="'.POST_NORMAL .'"'.( ( $post_data['topic_type'] == POST_NORMAL || $topic_type == POST_NORMAL ) ? ' checked="checked"' : '' ).' /> '.$lang['Post_Normal'].'&nbsp;&nbsp;'.$topic_type_toggle;
	}
}

$hidden_form_fields = '<input type="hidden" name="mode" value="'.$mode.'" />';

switch( $mode )
{
	case 'newtopic':
		$page_title = $forum_name.' '._BC_DELIM.' '.$lang['Post_a_new_topic'];
		$hidden_form_fields .= '<input type="hidden" name="'.POST_FORUM_URL.'" value="'.$forum_id.'" />';
		break;

	case 'reply':
		$page_title = $forum_name.' '._BC_DELIM.' '.$lang['Post_a_reply'].' '._BC_DELIM.' '. $subject.' ';
		$hidden_form_fields .= '<input type="hidden" name="'.POST_TOPIC_URL.'" value="'.$topic_id.'" />';
		break;

	case 'editpost':
		$page_title = $forum_name.' '._BC_DELIM.' '.$lang['Edit_Post'].' '._BC_DELIM.' '. $subject.' ';
		$hidden_form_fields .= '<input type="hidden" name="'.POST_POST_URL.'" value="'.$post_id.'" />';
		break;
}

//
// Include page header
//
require_once('includes/phpBB/page_header.php');

$template->set_filenames(array(
	'body' => 'forums/posting_body.html',
	'pollbody' => 'forums/posting_poll_body.html',
	'reviewbody' => 'forums/posting_topic_review.html')
);
make_jumpbox('viewforum');

$template->assign_vars(array(
	'FORUM_NAME' => $forum_name,
	'FORUM_DESC' => $forum_desc,
	'BC_DELIM'	=> _BC_DELIM,
	'L_POST_A' => $page_title,
	'L_POST_SUBJECT' => $lang['Post_subject'],

	'U_VIEW_FORUM' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id"))
);

//
// This enables the forum/topic title to be output for posting
// but not for privmsg (where it makes no sense)
//
$template->assign_block_vars('switch_not_privmsg', array());

//get custom icons for this forum
if ( !empty($forum_id)) $topic_icons = get_topic_icons($forum_id);

//if this forum has icons
$set_topic_icon = (!empty($topic_icons) && ($mode == 'newtopic' || ($mode == 'editpost' && $post_data['first_post'])) );
if ($set_topic_icon)
{
	//if new topic, or we are editing the first post of a topic
	$icons_array = "var icons_array = new Array();icons_array[0]='images/spacer.gif';";
	$topic_icon_post = isset($_POST['topic_icon']) ? intval($_POST['topic_icon']) : NULL;
foreach ($topic_icons as $key => $val) {
    $selected = '';
    if (isset($topic_icon_post) && $val['icon_id'] == $topic_icon_post) {
  			//icon is set in post data EG preview. this must override all
  			//note: no icon is 0 in which case this will never pass
  			$selected = 'selected';
  		} else if (isset($post_data['icon_id']) && $val['icon_id'] == $post_data['icon_id']) {
  			//this is the current icon for this thread (stored)
  			$selected = 'selected';
  		}
    $template->assign_block_vars('topic_icon_option', array(
  			'S_ICON_ID' => $val['icon_id'],
  			'S_ICON_NAME' => $val['icon_name'],
  			'S_SELECTED' => $selected)
  		);
    //add this icons url to the javascript array
    $icons_array .= "icons_array[".$val['icon_id']."] = '".$val['icon_url']."';";
}
	$template->assign_vars(array(
		'ICONS_ARRAY' => $icons_array)
	);
}

//
// Output the data to the template
//
$template->assign_vars(array(
	'USERNAME' => $username,
	'SUBJECT' => $subject,
	'MESSAGE' => $message,
	'HTML_STATUS' => $html_status,
	'BBCODE_STATUS' => sprintf($bbcode_status, '<a href="'.getlink("&amp;file=faq&amp;mode=bbcode").'" target="_phpbbcode">', '</a>'),
	'SMILIES_STATUS' => $smilies_status,
	'BBCODE_TABLE' => bbcode_table('message', 'post', 1),
	'SMILES_TABLE' => smilies_table('inline', 'message', 'post'),

	'L_EMOTICONS' => $lang['Emoticons'],
	'L_SUBJECT' => $lang['Subject'],
	'L_MESSAGE_BODY' => $lang['Message_body'],
	'L_OPTIONS' => $lang['Options'],
	'L_PREVIEW' => $lang['Preview'],
	'L_SPELLCHECK' => $lang['Spellcheck'],
	'L_SUBMIT' => $lang['Submit'],
	'L_CANCEL' => $lang['Cancel'],
	'L_CONFIRM_DELETE' => $lang['Confirm_delete'],
	'L_DISABLE_HTML' => $lang['Disable_HTML_post'],
	'L_DISABLE_BBCODE' => $lang['Disable_BBCode_post'],
	'L_DISABLE_SMILIES' => $lang['Disable_Smilies_post'],
	'L_ATTACH_SIGNATURE' => $lang['Attach_signature'],
	'L_NOTIFY_ON_REPLY' => $lang['Notify'],
	'L_DELETE_POST' => $lang['Delete_post'],
	'L_EMPTY_MESSAGE' => $lang['Empty_message'],
	'L_GO' => $lang['Go'],

	'U_VIEWTOPIC' => ( $mode == 'reply' ) ? getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;postorder=desc") : '',
	'U_REVIEW_TOPIC' => ( $mode == 'reply' ) ? getlink("&amp;file=posting&amp;mode=topicreview&amp;".POST_TOPIC_URL."=$topic_id&amp;popup=1") : '',

	'S_HTML_CHECKED' => ( !$html_on ) ? 'checked="checked"' : '',
	'S_BBCODE_CHECKED' => ( !$bbcode_on ) ? 'checked="checked"' : '',
	'S_SMILIES_CHECKED' => ( !$smilies_on ) ? 'checked="checked"' : '',
	'S_SIGNATURE_CHECKED' => ( $attach_sig ) ? 'checked="checked"' : '',
	'S_NOTIFY_CHECKED' => ( $notify_user ) ? 'checked="checked"' : '',
	'S_TYPE_TOGGLE' => $topic_type_toggle,
	'S_TOPIC_ID' => $topic_id,
	'S_POST_ACTION' => getlink('&amp;file=posting'),
	'S_HIDDEN_FORM_FIELDS' => $hidden_form_fields,
	'S_TOPIC_ICON_SELECT' => $set_topic_icon
	)
);

//
// Poll entry switch/output
//
if( ( $mode == 'newtopic' || ( $mode == 'editpost' && $post_data['edit_poll']) ) && $is_auth['auth_pollcreate'] )
{
	$template->assign_vars(array(
		'L_ADD_A_POLL' => $lang['Add_poll'],
		'L_ADD_POLL_EXPLAIN' => $lang['Add_poll_explain'],
		'L_POLL_QUESTION' => $lang['Poll_question'],
		'L_POLL_OPTION' => $lang['Poll_option'],
		'L_ADD_OPTION' => $lang['Add_option'],
		'L_UPDATE_OPTION' => $lang['Update'],
		'L_DELETE_OPTION' => $lang['Delete'],
		'L_POLL_LENGTH' => $lang['Poll_for'],
		'L_DAYS' => $lang['Days'],
		'L_POLL_LENGTH_EXPLAIN' => $lang['Poll_for_explain'],
		'L_POLL_DELETE' => $lang['Delete_poll'],

		'POLL_TITLE' => $poll_title,
		'POLL_LENGTH' => $poll_length)
	);
	if( $mode == 'editpost' && $post_data['edit_poll'] && $post_data['has_poll']) {
		$template->assign_block_vars('switch_poll_delete_toggle', array());
	}
	if( !empty($poll_options) ) {
		while( list($option_id, $option_text) = each($poll_options) ) {
			$template->assign_block_vars('poll_option_rows', array(
				'POLL_OPTION' => str_replace('"', '&quot;', $option_text),
				'S_POLL_OPTION_NUM' => $option_id)
			);
		}
	}
	$template->assign_var_from_handle('POLLBOX', 'pollbody');
}

//
// Topic review
//
if( $mode == 'reply' && $is_auth['auth_read'] ) {
	require_once('includes/phpBB/topic_review.php');
	topic_review($topic_id, true);
	$template->assign_block_vars('switch_inline_mode', array());
	$template->assign_var_from_handle('TOPIC_REVIEW_BOX', 'reviewbody');
}

require_once('includes/phpBB/page_tail.php');
