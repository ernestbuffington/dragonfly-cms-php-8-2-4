<?php
/***************************************************************************
 *			modcp.php
 *		  -------------------
 *	begin	  : July 4, 2001
 *	copyright	  : (C) 2001 The phpBB Group
 *	email	  : support@phpbb.com
 *
 *	$Id: modcp.php,v 9.15 2007/10/18 14:38:23 phoenix Exp $
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
if (!defined('CPG_NUKE')) { exit; }

/**
 * Moderator Control Panel
 *
 * From this 'Control Panel' the moderator of a forum will be able to do
 * mass topic operations (locking/unlocking/moving/deleteing), and it will
 * provide an interface to do quick locking/unlocking/moving/deleting of
 * topics via the moderator operations buttons on all of the viewtopic pages.
 */
require_once('modules/'.$module_name.'/nukebb.php');
require_once('includes/nbbcode.php');
require_once('includes/phpBB/functions_admin.php');

//
// Obtain initial var settings
//
if (isset($_GET[POST_FORUM_URL]) || isset($_POST[POST_FORUM_URL])) {
	$forum_id = (isset($_POST[POST_FORUM_URL])) ? intval($_POST[POST_FORUM_URL]) : intval($_GET[POST_FORUM_URL]);
} else {
	$forum_id = '';
}

if (isset($_GET[POST_POST_URL]) || isset($_POST[POST_POST_URL])) {
	$post_id = (isset($_POST[POST_POST_URL])) ? intval($_POST[POST_POST_URL]) : intval($_GET[POST_POST_URL]);
} else {
	$post_id = '';
}

if (isset($_GET[POST_TOPIC_URL]) || isset($_POST[POST_TOPIC_URL])) {
	$topic_id = (isset($_POST[POST_TOPIC_URL])) ? intval($_POST[POST_TOPIC_URL]) : intval($_GET[POST_TOPIC_URL]);
} else {
	$topic_id = '';
}

$confirm = (isset($_POST['confirm']) && $_POST['confirm']) ? TRUE : 0;

//
// Continue var definitions
//
$start = ( isset($_GET['start']) ) ? intval($_GET['start']) : 0;

$delete = ( isset($_POST['delete']) ) ? TRUE : FALSE;
$move = ( isset($_POST['move']) ) ? TRUE : FALSE;
$lock = ( isset($_POST['lock']) ) ? TRUE : FALSE;
$unlock = ( isset($_POST['unlock']) ) ? TRUE : FALSE;

if ( isset($_POST['mode']) || isset($_GET['mode']) ) {
	$mode = $_POST['mode'] ?? $_GET['mode'];
	$mode = htmlprepare($mode);
} else {
	if ( $delete ) {
		$mode = 'delete';
	} else if ( $move ) {
		$mode = 'move';
	} else if ( $lock ) {
		$mode = 'lock';
	} else if ( $unlock ) {
		$mode = 'unlock';
	} else {
		$mode = '';
	}
}

//
// Obtain relevant data
//
if ( !empty($topic_id) ) {
	$result = $db->sql_query("SELECT f.forum_id, f.forum_name, f.forum_topics
	  FROM ".TOPICS_TABLE." t, ".FORUMS_TABLE." f
	  WHERE t.topic_id = ".$topic_id." AND f.forum_id = t.forum_id");
	$topic_row = $db->sql_fetchrow($result);
	$forum_topics = ( $topic_row['forum_topics'] == 0 ) ? 1 : $topic_row['forum_topics'];
	$forum_id = $topic_row['forum_id'];
	$forum_name = $topic_row['forum_name'];
} else if ( !empty($forum_id) ) {
	$result = $db->sql_query("SELECT forum_name, forum_topics FROM ".FORUMS_TABLE." WHERE forum_id = ".$forum_id);
	$topic_row = $db->sql_fetchrow($result);
	$forum_topics = ( $topic_row['forum_topics'] == 0 ) ? 1 : $topic_row['forum_topics'];
	$forum_name = $topic_row['forum_name'];
} else {
	message_die(GENERAL_MESSAGE, 'Forum_not_exist');
}

//
// Start session management
//
$userdata = session_pagestart($user_ip, $forum_id);
init_userprefs($userdata);
//
// End session management
//

//
// Check if user did or did not confirm
// If they did not, forward them to the last page they were on
//
if ( isset($_POST['cancel']) ) {
	$redirect = '';
	if ( $topic_id ) {
		$redirect = "&file=viewtopic&".POST_TOPIC_URL."=$topic_id";
	} else if ( $forum_id ) {
		$redirect = "&file=viewforum&".POST_FORUM_URL."=$forum_id";
	}
	url_redirect(getlink($redirect));
}

//
// Start auth check
//
$is_auth = auth(AUTH_ALL, $forum_id, $userdata);

if (!$is_auth['auth_mod']) {
	message_die(GENERAL_MESSAGE, $lang['Not_Moderator'], $lang['Not_Authorised']);
}
//
// End Auth Check
//

//
// Do major work ...
//
switch( $mode )
{
	case 'delete':
	  if (!$is_auth['auth_delete']) {
		message_die(MESSAGE, sprintf($lang['Sorry_auth_delete'], $is_auth['auth_delete_type']));
	  }
	  $page_title = $lang['Mod_CP'];
	  require_once("includes/phpBB/page_header.php");
	  if ( $confirm ) {
		require_once("includes/phpBB/functions_search.php");
		$topics = $_POST['topic_id_list'] ?? array($topic_id);
		$topic_id_sql = '';
		for($i = 0; $i < (is_countable($topics) ? count($topics) : 0); $i++) {
		  $topic_id_sql .= ( ( $topic_id_sql != '' ) ? ', ' : '' ).intval($topics[$i]);
		}
		$result = $db->sql_query("SELECT topic_id FROM ".TOPICS_TABLE
		 ." WHERE topic_id IN ($topic_id_sql) AND forum_id = $forum_id");

		$topic_id_sql = '';
		while ($row = $db->sql_fetchrow($result)) {
		  $topic_id_sql .= (($topic_id_sql != '') ? ', ' : '').intval($row['topic_id']);
		}
		$db->sql_freeresult($result);

		$result = $db->sql_query("SELECT poster_id, COUNT(post_id) AS posts FROM ".POSTS_TABLE
		 ." WHERE topic_id IN ($topic_id_sql) GROUP BY poster_id");

		$count_sql = array();
		while ( $row = $db->sql_fetchrow($result) ) {
		  $count_sql[] = "UPDATE ".USERS_TABLE." SET user_posts = user_posts - ".$row['posts']."
			WHERE user_id = ".$row['poster_id'];
		}
		$db->sql_freeresult($result);
		if ( sizeof($count_sql) ) {
		  for($i = 0; $i < sizeof($count_sql); $i++) {
			$db->sql_query($count_sql[$i]);
		  }
		}

		$result = $db->sql_query("SELECT post_id FROM ".POSTS_TABLE." WHERE topic_id IN ($topic_id_sql)");
		$post_id_sql = '';
		while ( $row = $db->sql_fetchrow($result) ) {
		  $post_id_sql .= ( ( $post_id_sql != '' ) ? ', ' : '' ).intval($row['post_id']);
		}
		$db->sql_freeresult($result);

		$result = $db->sql_query("SELECT vote_id FROM ".VOTE_DESC_TABLE." WHERE topic_id IN ($topic_id_sql)");
		$vote_id_sql = '';
		while ( $row = $db->sql_fetchrow($result) ) {
		  $vote_id_sql .= ( ( $vote_id_sql != '' ) ? ', ' : '' ).$row['vote_id'];
		}
		$db->sql_freeresult($result);

		//
		// Got all required info so go ahead and start deleting everything
		//
		$sql = "DELETE FROM ".TOPICS_TABLE." WHERE topic_id IN ($topic_id_sql) OR topic_moved_id IN ($topic_id_sql)";
		$db->sql_query($sql);
		if ($post_id_sql != '') {
		  $sql = "DELETE FROM ".POSTS_TABLE." WHERE post_id IN ($post_id_sql)";
		  $db->sql_query($sql);
		  $sql = "DELETE FROM ".POSTS_TEXT_TABLE." WHERE post_id IN ($post_id_sql)";
		  $db->sql_query($sql);
		  remove_search_post($post_id_sql);
//		  if (defined('BBAttach_mod')) {
		  delete_attachment(explode(', ', $post_id_sql));
		}

		if ($vote_id_sql != '') {
		  $db->sql_query("DELETE FROM ".VOTE_DESC_TABLE." WHERE vote_id IN ($vote_id_sql)");
		  $db->sql_query("DELETE FROM ".VOTE_RESULTS_TABLE." WHERE vote_id IN ($vote_id_sql)");
		  $db->sql_query("DELETE FROM ".VOTE_USERS_TABLE." WHERE vote_id IN ($vote_id_sql)");
		}

		$db->sql_query("DELETE FROM ".TOPICS_WATCH_TABLE." WHERE topic_id IN ($topic_id_sql)");

		sync('forum', $forum_id);

		if (!empty($topic_id)) {
		  $redirect_page = "&file=viewforum&".POST_FORUM_URL."=$forum_id";
		  $l_redirect = sprintf($lang['Click_return_forum'], '<a href="'.getlink($redirect_page).'">', '</a>');
		} else {
		  $redirect_page ="&file=modcp&".POST_FORUM_URL."=$forum_id";
		  $l_redirect = sprintf($lang['Click_return_modcp'], '<a href="'.getlink($redirect_page).'">', '</a>');
		}
		url_refresh(getlink($redirect_page));
		message_die(GENERAL_MESSAGE, $lang['Topics_Removed'].'<br /><br />'.$l_redirect);
	  } else {
		// Not confirmed, show confirmation message
		if (empty($_POST['topic_id_list']) && empty($topic_id)) {
		  message_die(GENERAL_MESSAGE, $lang['None_selected']);
		}

		$hidden_fields = '<input type="hidden" name="mode" value="'.$mode.'" /><input type="hidden" name="'.POST_FORUM_URL.'" value="'.$forum_id.'" />';

		if (isset($_POST['topic_id_list'])) {
		  $topics = $_POST['topic_id_list'];
		  for($i = 0; $i < (is_countable($topics) ? count($topics) : 0); $i++) {
			$hidden_fields .= '<input type="hidden" name="topic_id_list[]" value="'.intval($topics[$i]).'" />';
		  }
		} else {
		  $hidden_fields .= '<input type="hidden" name="'.POST_TOPIC_URL.'" value="'.$topic_id.'" />';
		}

		$template->assign_vars(array(
		  'MESSAGE_TITLE' => $lang['Confirm'],
		  'MESSAGE_TEXT' => $lang['Confirm_delete_topic'],

		  'L_YES' => $lang['Yes'],
		  'L_NO' => $lang['No'],

		  'S_CONFIRM_ACTION' => getlink("&amp;file=modcp"),
		  'S_HIDDEN_FIELDS' => $hidden_fields)
		);

		$template->set_filenames(array('body' => 'confirm_body.html'));
	  }
	  break;

	case 'move':
	  $page_title = $lang['Mod_CP'];
	  require_once("includes/phpBB/page_header.php");
	  if ($confirm) {
		if (empty($_POST['topic_id_list']) && empty($topic_id)) {
		  message_die(GENERAL_MESSAGE, $lang['None_selected']);
		}
		$new_forum_id = intval($_POST['new_forum']);
		$old_forum_id = $forum_id;
		if ( $new_forum_id != $old_forum_id ) {
		  $topics = $_POST['topic_id_list'] ?? array($topic_id);
		  $topic_list = '';
		  for($i = 0; $i < (is_countable($topics) ? count($topics) : 0); $i++) {
			$topic_list .= ( ( $topic_list != '' ) ? ', ' : '' ).intval($topics[$i]);
		  }
		  $sql = "SELECT * FROM ".TOPICS_TABLE."
			WHERE topic_id IN ($topic_list) AND forum_id = $old_forum_id AND topic_status <> ".TOPIC_MOVED;
		  $result = $db->sql_query($sql);
		  $row = $db->sql_fetchrowset($result);
		  $db->sql_freeresult($result);
		  for($i = 0; $i < (is_countable($row) ? count($row) : 0); $i++) {
			$topic_id = $row[$i]['topic_id'];
			if ( isset($_POST['move_leave_shadow']) ) {
				// Insert topic in the old forum that indicates that the forum has moved.
				$sql = "INSERT INTO ".TOPICS_TABLE." (forum_id, topic_title, topic_poster, topic_time, topic_status, topic_type, topic_vote, topic_views, topic_replies, topic_first_post_id, topic_last_post_id, topic_moved_id)
				  VALUES ($old_forum_id, '".Fix_Quotes($row[$i]['topic_title'])."', '".Fix_Quotes($row[$i]['topic_poster'])."', ".$row[$i]['topic_time'].", ".TOPIC_MOVED.", ".POST_NORMAL.", ".$row[$i]['topic_vote'].", ".$row[$i]['topic_views'].", ".$row[$i]['topic_replies'].", ".$row[$i]['topic_first_post_id'].", ".$row[$i]['topic_last_post_id'].", $topic_id)";
				$db->sql_query($sql);
			}
			$db->sql_query("UPDATE ".TOPICS_TABLE." SET forum_id = $new_forum_id WHERE topic_id = $topic_id");
			$db->sql_query("UPDATE ".POSTS_TABLE." SET forum_id = $new_forum_id WHERE topic_id = $topic_id");
		  }

		  // Sync the forum indexes
		  sync('forum', $new_forum_id);
		  sync('forum', $old_forum_id);
		  $message = $lang['Topics_Moved'].'<br /><br />';
		} else {
		  $message = $lang['No_Topics_Moved'].'<br /><br />';
		}
		if ( !empty($topic_id) ) {
		  $redirect_page = "&file=viewtopic&".POST_TOPIC_URL."=$topic_id";
		  $message .= sprintf($lang['Click_return_topic'], '<a href="'.getlink($redirect_page).'">', '</a>');
		} else {
		  $redirect_page = "&file=modcp&".POST_FORUM_URL."=$forum_id";
		  $message .= sprintf($lang['Click_return_modcp'], '<a href="'.getlink($redirect_page).'">', '</a>');
		}
		$message = $message.'<br \><br \>'.sprintf($lang['Click_return_forum'], '<a href="'.getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$old_forum_id").'">', '</a>');
		url_refresh(getlink($redirect_page));
		message_die(GENERAL_MESSAGE, $message);
	  } else {
		if ( empty($_POST['topic_id_list']) && empty($topic_id) ) {
		  message_die(GENERAL_MESSAGE, $lang['None_selected']);
		}
		$hidden_fields = '<input type="hidden" name="mode" value="'.$mode.'" /><input type="hidden" name="'.POST_FORUM_URL.'" value="'.$forum_id.'" />';
		if ( isset($_POST['topic_id_list']) ) {
		  $topics = $_POST['topic_id_list'];
		  for($i = 0; $i < (is_countable($topics) ? count($topics) : 0); $i++) {
			$hidden_fields .= '<input type="hidden" name="topic_id_list[]" value="'.intval($topics[$i]).'" />';
		  }
		} else {
		  $hidden_fields .= '<input type="hidden" name="'.POST_TOPIC_URL.'" value="'.$topic_id.'" />';
		}

		$template->assign_vars(array(
		  'MESSAGE_TITLE' => $lang['Confirm'],
		  'MESSAGE_TEXT' => $lang['Confirm_move_topic'],

		  'L_MOVE_TO_FORUM' => $lang['Move_to_forum'],
		  'L_LEAVESHADOW' => $lang['Leave_shadow_topic'],
		  'L_YES' => $lang['Yes'],
		  'L_NO' => $lang['No'],

		  'S_FORUM_SELECT' => make_forum_select('new_forum', $forum_id),
		  'S_MODCP_ACTION' => getlink("&amp;file=modcp"),
		  'S_HIDDEN_FIELDS' => $hidden_fields)
		);

		$template->set_filenames(array('body' => 'forums/modcp_move.html'));
	  }
	  break;

	case 'lock':
	  if ( empty($_POST['topic_id_list']) && empty($topic_id) ) {
		message_die(GENERAL_MESSAGE, $lang['None_selected']);
	  }
	  $topics = $_POST['topic_id_list'] ?? array($topic_id);
	  $topic_id_sql = '';
	  for($i = 0; $i < (is_countable($topics) ? count($topics) : 0); $i++) {
		$topic_id_sql .= ( ( $topic_id_sql != '' ) ? ', ' : '' ).intval($topics[$i]);
	  }
	  $sql = "UPDATE ".TOPICS_TABLE."
		SET topic_status = ".TOPIC_LOCKED."
		WHERE topic_id IN ($topic_id_sql)
		  AND forum_id = $forum_id
		  AND topic_moved_id = 0";
	  $result = $db->sql_query($sql);
	  if ( !empty($topic_id) ) {
		$redirect_page = '&file=viewtopic&'. POST_TOPIC_URL ."=$topic_id";
		$message = sprintf($lang['Click_return_topic'], '<a href="'.getlink($redirect_page).'">', '</a>');
	  } else {
		$redirect_page = '&amp;file=modcp&amp;'.POST_FORUM_URL."=$forum_id";
		$message = sprintf($lang['Click_return_modcp'], '<a href="'.getlink($redirect_page).'">', '</a>');
	  }
	  $message = $message.'<br \><br \>'.sprintf($lang['Click_return_forum'], '<a href="'.getlink('&amp;file=viewforum&amp;'.POST_FORUM_URL."=$forum_id").'">', '</a>');
	  url_refresh(getlink($redirect_page));
	  message_die(GENERAL_MESSAGE, $lang['Topics_Locked'].'<br /><br />'.$message);
	  break;

	case 'unlock':
	  if ( empty($_POST['topic_id_list']) && empty($topic_id) ) {
		message_die(GENERAL_MESSAGE, $lang['None_selected']);
	  }
	  $topics = $_POST['topic_id_list'] ?? array($topic_id);
	  $topic_id_sql = '';
	  for($i = 0; $i < (is_countable($topics) ? count($topics) : 0); $i++) {
		$topic_id_sql .= ( ( $topic_id_sql != "") ? ', ' : '' ).intval($topics[$i]);
	  }
	  $sql = "UPDATE ".TOPICS_TABLE."
		SET topic_status = ".TOPIC_UNLOCKED."
		WHERE topic_id IN ($topic_id_sql)
		  AND forum_id = $forum_id
		  AND topic_moved_id = 0";
	  $result = $db->sql_query($sql);
	  if ( !empty($topic_id) ) {
		$redirect_page = "&file=viewtopic&".POST_TOPIC_URL."=$topic_id";
		$message = sprintf($lang['Click_return_topic'], '<a href="'.getlink($redirect_page).'">', '</a>');
	  } else {
		$redirect_page = "&file=modcp&".POST_FORUM_URL."=$forum_id";
		$message = sprintf($lang['Click_return_modcp'], '<a href="'.getlink($redirect_page).'">', '</a>');
	  }
	  $message = $message.'<br \><br \>'.sprintf($lang['Click_return_forum'], '<a href="'.getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id").'">', '</a>');
	  url_refresh(getlink($redirect_page));
	  message_die(GENERAL_MESSAGE, $lang['Topics_Unlocked'].'<br /><br />'.$message);
	  break;

	case 'split':
	  $page_title = $lang['Mod_CP'];
	  require_once("includes/phpBB/page_header.php");
	  $post_id_sql = '';
	  if (isset($_POST['split_type_all']) || isset($_POST['split_type_beyond'])) {
		$posts = $_POST['post_id_list'];
		for ($i = 0; $i < (is_countable($posts) ? count($posts) : 0); $i++) {
		  $post_id_sql .= (($post_id_sql != '') ? ', ' : '').intval($posts[$i]);
		}
	  }
	  if ($post_id_sql != '') {
		$sql = "SELECT post_id FROM ".POSTS_TABLE."
		  WHERE post_id IN ($post_id_sql) AND forum_id = $forum_id";
		$result = $db->sql_query($sql);
		$post_id_sql = '';
		while ($row = $db->sql_fetchrow($result)) {
		  $post_id_sql .= (($post_id_sql != '') ? ', ' : '').intval($row['post_id']);
		}
		$db->sql_freeresult($result);
		$sql = "SELECT post_id, poster_id, topic_id, post_time FROM ".POSTS_TABLE."
		  WHERE post_id IN ($post_id_sql) ORDER BY post_time ASC";
		$result = $db->sql_query($sql);

		if ($row = $db->sql_fetchrow($result)) {
		  $first_poster = $row['poster_id'];
		  $topic_id = $row['topic_id'];
		  $post_time = $row['post_time'];
		  $user_id_sql = $post_id_sql = '';
		  do {
			$user_id_sql .= (($user_id_sql != '') ? ', ' : '').intval($row['poster_id']);
			$post_id_sql .= (($post_id_sql != '') ? ', ' : '').intval($row['post_id']);;
		  }
		  while ($row = $db->sql_fetchrow($result));
		  $post_subject = htmlprepare($_POST['subject']);
		  if (empty($post_subject)) {
			message_die(GENERAL_MESSAGE, $lang['Empty_subject']);
		  }
		  $new_forum_id = intval($_POST['new_forum_id']);
		  $topic_time = gmtime();
		  $sql	= "INSERT INTO ".TOPICS_TABLE." (topic_title, topic_poster, topic_time, forum_id, topic_status, topic_type)
			VALUES ('".Fix_Quotes($post_subject)."', $first_poster, ".$topic_time.", $new_forum_id, ".TOPIC_UNLOCKED.", ".POST_NORMAL.")";
		  $db->sql_query($sql);
		  $new_topic_id = $db->sql_nextid('topic_id');

		  // Update topic watch table, switch users whose posts
		  // have moved, over to watching the new topic
		  $sql = "UPDATE ".TOPICS_WATCH_TABLE."
			SET topic_id = $new_topic_id
			WHERE topic_id = $topic_id
				AND user_id IN ($user_id_sql)";
		  $db->sql_query($sql);

		  $sql_where = (!empty($_POST['split_type_beyond'])) ? " post_time >= $post_time AND topic_id = $topic_id" : "post_id IN ($post_id_sql)";

		  $sql = "UPDATE ".POSTS_TABLE."
			SET topic_id = $new_topic_id, forum_id = $new_forum_id
			WHERE $sql_where";
		  $db->sql_query($sql);

		  sync('topic', $new_topic_id);
		  sync('topic', $topic_id);
		  sync('forum', $new_forum_id);
		  sync('forum', $forum_id);

		  url_refresh(getlink("&file=viewtopic&".POST_TOPIC_URL."=$topic_id"));

		  $message = $lang['Topic_split'].'<br /><br />'.sprintf($lang['Click_return_topic'], '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id").'">', '</a>');
		  message_die(GENERAL_MESSAGE, $message);
		}
	  } else {
		$sql = "SELECT u.username, p.*, pt.post_text, pt.post_subject, p.post_username
		  FROM ".POSTS_TABLE." p, ".USERS_TABLE." u, ".POSTS_TEXT_TABLE." pt
		  WHERE p.topic_id = $topic_id
			AND p.poster_id = u.user_id
			AND p.post_id = pt.post_id
		  ORDER BY p.post_time ASC";
		if ( !($result = $db->sql_query($sql)) )
		{
		  message_die(GENERAL_ERROR, 'Could not get topic/post information', '', __LINE__, __FILE__, $sql);
		}

		$s_hidden_fields = '<input type="hidden" name="'.POST_FORUM_URL.'" value="'.$forum_id.'" /><input type="hidden" name="'.POST_TOPIC_URL.'" value="'.$topic_id.'" /><input type="hidden" name="mode" value="split" />';

		if( ( $total_posts = $db->sql_numrows($result) ) > 0 ) {
		  $postrow = $db->sql_fetchrowset($result);
		  $template->assign_vars(array(
			'L_SPLIT_TOPIC' => $lang['Split_Topic'],
			'L_SPLIT_TOPIC_EXPLAIN' => $lang['Split_Topic_explain'],
			'L_AUTHOR' => $lang['Author'],
			'L_MESSAGE' => $lang['Message'],
			'L_SELECT' => $lang['Select'],
			'L_SPLIT_SUBJECT' => $lang['Split_title'],
			'L_SPLIT_FORUM' => $lang['Split_forum'],
			'L_POSTED' => $lang['Posted'],
			'L_SPLIT_POSTS' => $lang['Split_posts'],
			'L_SUBMIT' => $lang['Submit'],
			'L_SPLIT_AFTER' => $lang['Split_after'],
			'L_POST_SUBJECT' => $lang['Post_subject'],
			'L_MARK_ALL' => $lang['Mark_all'],
			'L_UNMARK_ALL' => $lang['Unmark_all'],
			'L_POST' => $lang['Post'],

			'FORUM_NAME' => $forum_name,

			'U_VIEW_FORUM' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id"),

			'S_SPLIT_ACTION' => getlink("&amp;file=modcp"),
			'S_HIDDEN_FIELDS' => $s_hidden_fields,
			'S_FORUM_SELECT' => make_forum_select("new_forum_id", false, $forum_id))
		  );

		  for($i = 0; $i < $total_posts; $i++)
		  {
			$post_id = $postrow[$i]['post_id'];
			$poster_id = $postrow[$i]['poster_id'];
			$poster = $postrow[$i]['username'];

			$post_date = create_date($board_config['default_dateformat'], $postrow[$i]['post_time']);

			$message = $postrow[$i]['post_text'];
			$post_subject = ( $postrow[$i]['post_subject'] != '' ) ? $postrow[$i]['post_subject'] : $topic_title;

			//
			// If the board has HTML off but the post has HTML
			// on then we process it, else leave it alone
			//
			if ( !$board_config['allow_html'] ) {
				if ( $postrow[$i]['enable_html'] ) {
				  $message = preg_replace('#(<)([\/]?.*?)(>)#is', '&lt;\\2&gt;', $message);
				}
			}

			if ($board_config['allow_bbcode']) {
				$message =  decode_bbcode($message, 1, false);
			}

			//
			// Define censored word matches
			//
			$orig_word = $replacement_word = array();
			obtain_word_list($orig_word, $replacement_word);

			if ( count($orig_word) )
			{
				$post_subject = preg_replace($orig_word, $replacement_word, $post_subject);
				$message = preg_replace($orig_word, $replacement_word, $message);
			}

			$message = make_clickable($message);

			if ( $board_config['allow_smilies'] && $postrow[$i]['enable_smilies'] ) {
				$message = set_smilies($message);
			}

			$message = nl2br($message);

			$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
			$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

			$checkbox = ( $i > 0 ) ? '<input type="checkbox" name="post_id_list[]" value="'.$post_id.'" />' : '&nbsp;';

			$template->assign_block_vars('postrow', array(
				'ROW_COLOR' => $row_color,
				'ROW_CLASS' => $row_class,
				'POSTER_NAME' => $poster,
				'POST_DATE' => $post_date,
				'POST_SUBJECT' => $post_subject,
				'MESSAGE' => $message,
				'U_POST_ID' => $post_id,

				'S_SPLIT_CHECKBOX' => $checkbox)
			);
		  }
		  $template->set_filenames(array('body' => 'forums/modcp_split.html'));
		}
	  }
	  break;

	case 'ip':
	  $page_title = $lang['Mod_CP'];
	  require_once('includes/phpBB/page_header.php');

	  $rdns_ip_num = $_GET['rdns'] ?? '';

	  if ( !$post_id ) {
		message_die(GENERAL_MESSAGE, $lang['No_such_post']);
	  }

	  // Look up relevent data for this post
	  $sql = "SELECT poster_ip, poster_id FROM ".POSTS_TABLE."
		WHERE post_id = $post_id AND forum_id = $forum_id";
	  $result = $db->sql_query($sql);

	  if ( !($post_row = $db->sql_fetchrow($result)) )
	  {
		message_die(GENERAL_MESSAGE, $lang['No_such_post']);
	  }

	  $ip_this_post = decode_ip($post_row['poster_ip']);
	  $ip_this_post = ( $rdns_ip_num == $ip_this_post ) ? gethostbyaddr($ip_this_post) : $ip_this_post;

	  $poster_id = $post_row['poster_id'];

	  $template->assign_vars(array(
		'L_IP_INFO' => $lang['IP_info'],
		'L_THIS_POST_IP' => $lang['This_posts_IP'],
		'L_OTHER_IPS' => $lang['Other_IP_this_user'],
		'L_OTHER_USERS' => $lang['Users_this_IP'],
		'L_LOOKUP_IP' => $lang['Lookup_IP'],
		'L_SEARCH' => $lang['Search'],

		'SEARCH_IMG' => $images['icon_search'],

		'IP' => $ip_this_post,

		'U_LOOKUP_IP' => getlink("&amp;file=modcp&amp;mode=ip&amp;".POST_POST_URL."=$post_id&amp;".POST_TOPIC_URL."=$topic_id&amp;rdns=".$ip_this_post))
	  );

	  //
	  // Get other IP's this user has posted under
	  //
	  $sql = "SELECT poster_ip, COUNT(*) AS postings
		FROM ".POSTS_TABLE."
		WHERE poster_id = '".Fix_Quotes($poster_id)."'
		GROUP BY poster_ip
		ORDER BY ".(( SQL_LAYER == 'msaccess' ) ? 'COUNT(*)' : 'postings' )." DESC";
	  $result = $db->sql_query($sql);

	  if ( $row = $db->sql_fetchrow($result) ) {
		$i = 0;
		do {
		  if ( $row['poster_ip'] == $post_row['poster_ip'] ) {
			$template->assign_vars(array(
				'POSTS' => $row['postings'].' '.( ( $row['postings'] == 1 ) ? $lang['Post'] : $lang['Posts'] ))
			);
			continue;
		  }

		  $ip = decode_ip($row['poster_ip']);
		  $ip = ( $rdns_ip_num == $row['poster_ip'] || $rdns_ip_num == 'all') ? gethostbyaddr($ip) : $ip;

		  $row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		  $row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		  $template->assign_block_vars('iprow', array(
			'ROW_COLOR' => $row_color,
			'ROW_CLASS' => $row_class,
			'IP' => $ip,
			'POSTS' => $row['postings'].' '.( ( $row['postings'] == 1 ) ? $lang['Post'] : $lang['Posts'] ),

			'U_LOOKUP_IP' => getlink("&amp;file=modcp&amp;mode=ip&amp;".POST_POST_URL."=$post_id&amp;".POST_TOPIC_URL."=$topic_id&amp;rdns=".decode_ip($row['poster_ip'])))
		  );

		  $i++;
		}
		while ( $row = $db->sql_fetchrow($result) );
	  }

	  //
	  // Get other users who've posted under this IP
	  //
	  $sql = "SELECT u.user_id, u.username, COUNT(*) as postings
		FROM ".USERS_TABLE ." u, ".POSTS_TABLE." p
		WHERE p.poster_id = u.user_id
		  AND p.poster_ip = '".Fix_Quotes($post_row['poster_ip'])."'
		GROUP BY u.user_id, u.username
		ORDER BY ".(( SQL_LAYER == 'msaccess' ) ? 'COUNT(*)' : 'postings' )." DESC";
	  $result = $db->sql_query($sql);

	  if ( $row = $db->sql_fetchrow($result) ) {
		$i = 0;
		do {
		  $id = $row['user_id'];
		  $username = ( $id == ANONYMOUS ) ? $lang['Guest'] : $row['username'];

		  $row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		  $row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		  $template->assign_block_vars('userrow', array(
			'ROW_COLOR' => $row_color,
			'ROW_CLASS' => $row_class,
			'USERNAME' => $username,
			'POSTS' => $row['postings'].' '.( ( $row['postings'] == 1 ) ? $lang['Post'] : $lang['Posts'] ),
			'L_SEARCH_POSTS' => sprintf($lang['Search_user_posts'], $username),

			'U_PROFILE' => getlink("Your_Account&amp;profile=$id"),
			'U_SEARCHPOSTS' => getlink("&amp;file=search&amp;search_author=".urlencode($username)."&amp;showresults=topics"))
		  );

		  $i++;
		}
		while ( $row = $db->sql_fetchrow($result) );
	  }
	  $template->set_filenames(array('body' => 'forums/modcp_viewip.html'));
	  break;

	default:
		$page_title = $lang['Mod_CP'];
		require_once('includes/phpBB/page_header.php');

		$template->assign_vars(array(
			'FORUM_NAME' => $forum_name,

			'L_MOD_CP' => $lang['Mod_CP'],
			'L_MOD_CP_EXPLAIN' => $lang['Mod_CP_explain'],
			'L_SELECT' => $lang['Select'],
			'L_DELETE' => $lang['Delete'],
			'L_MOVE' => $lang['Move'],
			'L_LOCK' => $lang['Lock'],
			'L_UNLOCK' => $lang['Unlock'],
			'L_TOPICS' => $lang['Topics'],
			'L_REPLIES' => $lang['Replies'],
			'L_LASTPOST' => $lang['Last_Post'],
			'L_SELECT' => $lang['Select'],

			'U_VIEW_FORUM' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id"),
			'S_HIDDEN_FIELDS' => '<input type="hidden" name="'.POST_FORUM_URL.'" value="'.$forum_id.'" />',
			'S_MODCP_ACTION' => getlink("&amp;file=modcp"))
		);

		make_jumpbox('modcp');

		//
		// Define censored word matches
		//
		$orig_word = array();
		$replacement_word = array();
		obtain_word_list($orig_word, $replacement_word);

		$sql = "SELECT t.*, u.username, u.user_id, p.post_time
		FROM ".TOPICS_TABLE." t, ".USERS_TABLE." u, ".POSTS_TABLE." p
		WHERE t.forum_id = $forum_id
		  AND t.topic_poster = u.user_id
		  AND p.post_id = t.topic_last_post_id
		ORDER BY t.topic_type DESC, p.post_time DESC
		LIMIT $start, ".$board_config['topics_per_page'];
		$result = $db->sql_query($sql);

		while ( $row = $db->sql_fetchrow($result) ) {
			$topic_title = '';
			if ( $row['topic_status'] == TOPIC_LOCKED ) {
				$folder_img = $images['folder_locked'];
				$folder_alt = $lang['Topic_locked'];
			} else {
				if ($row['topic_type'] == POST_ANNOUNCE) {
					$folder_img = $images['folder_announce'];
					$folder_alt = $lang['Topic_Announcement'];
				} else if ( $row['topic_type'] == POST_STICKY ) {
					$folder_img = $images['folder_sticky'];
					$folder_alt = $lang['Topic_Sticky'];
				} else {
					$folder_img = $images['folder'];
					$folder_alt = $lang['No_new_posts'];
				}
			}

			$topic_id = $row['topic_id'];
			$topic_type = $row['topic_type'];
			$topic_status = $row['topic_status'];

			if ( $topic_type == POST_ANNOUNCE ) {
				$topic_type = $lang['Topic_Announcement'].' ';
			} else if ( $topic_type == POST_STICKY ) {
				$topic_type = $lang['Topic_Sticky'].' ';
			} else if ( $topic_status == TOPIC_MOVED ) {
				$topic_type = $lang['Topic_Moved'].' ';
			} else {
				$topic_type = '';
			}

			if ( $row['topic_vote'] ) {
				$topic_type .= $lang['Topic_Poll'].' ';
			}

			$topic_title = $row['topic_title'];
			if ( count($orig_word) ) {
				$topic_title = preg_replace($orig_word, $replacement_word, $topic_title);
			}

			$u_view_topic = getlink("&amp;file=modcp&amp;mode=split&amp;".POST_TOPIC_URL."=$topic_id");
			$topic_replies = $row['topic_replies'];

			$last_post_time = create_date($board_config['default_dateformat'], $row['post_time']);

			$template->assign_block_vars('topicrow', array(
				'U_VIEW_TOPIC' => $u_view_topic,

				'TOPIC_FOLDER_IMG' => $folder_img,
				'TOPIC_TYPE' => $topic_type,
				'TOPIC_TITLE' => $topic_title,
				'REPLIES' => $topic_replies,
				'LAST_POST_TIME' => $last_post_time,
				'TOPIC_ID' => $topic_id,
// BBAttach_mod
				'TOPIC_ATTACHMENT_IMG' => topic_attachment_image($row['topic_attachment']),

				'L_TOPIC_FOLDER_ALT' => $folder_alt)
			);
		}

		$template->assign_vars(array(
		'PAGINATION' => generate_pagination("&amp;file=modcp&amp;".POST_FORUM_URL."=$forum_id", $forum_topics, $board_config['topics_per_page'], $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $forum_topics / $board_config['topics_per_page'] )),
		'L_GOTO_PAGE' => $lang['Goto_page'],
		'L_GO' => $lang['Go'])
		);

		$template->set_filenames(array('body' => 'forums/modcp_body.html'));

	break;
}

require_once('includes/phpBB/page_tail.php');
