<?php
/***************************************************************************
 *								index.php
 *							-------------------
 *	 begin				: Saturday, Feb 13, 2001
 *	 copyright			: (C) 2001 The phpBB Group
 *	 email				: support@phpbb.com
 *
  Last modification notes:
  $Source: /public_html/modules/Forums/viewtopic.php,v $
  $Revision: 9.39 $
  $Author: phoenix $
  $Date: 2007/09/18 01:22:51 $
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

//
// Start initial var setup
//
$topic_id = $post_id = 0;
if ( isset($_GET[POST_TOPIC_URL]) ) {
	$topic_id = intval($_GET[POST_TOPIC_URL]);
} else if ( isset($_GET['topic']) ) {
	$topic_id = intval($_GET['topic']);
}

if ( isset($_GET[POST_POST_URL])) {
	$post_id = intval($_GET[POST_POST_URL]);
}
//
// Start session management
//
$userdata = session_pagestart();
init_userprefs($userdata);
//
// End session management
//

if ( !isset($topic_id) && !isset($post_id) ) {
	message_die(GENERAL_MESSAGE, 'Topic_post_not_exist');
}

$start = ( isset($_GET['start']) ) ? intval($_GET['start']) : 0;

if (isset($_GET['printertopic'])) {
	$start = ( isset($_GET['start_rel']) && isset($_GET['printertopic']) ) ? intval($_GET['start_rel']) - 1 : $start;
	// $finish when positive indicates last message; when negative it indicates range; can't be 0
	if(isset($_GET['finish_rel'])) {
		$finish = intval($_GET['finish_rel']);
	}
	if($finish >= 0 && ($finish - $start) <=0) {
		unset($finish);
	}
}

//
// Find topic id if user requested a newer or older topic
//
if ( isset($_GET['view']) && empty($_GET[POST_POST_URL]) ) {
	if ( $_GET['view'] == 'newest' ) {
		if (is_user()) {
			$sql = "SELECT p.post_id FROM ".POSTS_TABLE." p
					WHERE p.topic_id = $topic_id
						AND p.post_time >= ".$userinfo['user_lastvisit']."
					ORDER BY p.post_time ASC LIMIT 0,1";
			$result = $db->sql_query($sql);
			if ( !($row = $db->sql_fetchrow($result, SQL_ASSOC)) ) {
				message_die(GENERAL_MESSAGE, 'No_new_posts_last_visit');
			}
			$post_id = $row['post_id'];
			url_redirect(getlink("&file=viewtopic&".POST_POST_URL."=$post_id")."#$post_id");
		}
		url_redirect(getlink("&file=viewtopic&".POST_TOPIC_URL."=$topic_id"));
	} else if ( $_GET['view'] == 'next' || $_GET['view'] == 'previous' ) {
		$sql_condition = ( $_GET['view'] == 'next' ) ? '>' : '<';
		$sql_ordering = ( $_GET['view'] == 'next' ) ? 'ASC' : 'DESC';
		$sql = "SELECT t.topic_id FROM ".TOPICS_TABLE." t, ".TOPICS_TABLE." t2
			WHERE t2.topic_id = $topic_id
				AND t.forum_id = t2.forum_id
				AND t.topic_last_post_id $sql_condition t2.topic_last_post_id
			ORDER BY t.topic_last_post_id $sql_ordering LIMIT 0,1";
		$result = $db->sql_query($sql);
		if ( $row = $db->sql_fetchrow($result, SQL_ASSOC) ) {
			$topic_id = intval($row['topic_id']);
			$db->sql_freeresult($result);
		} else {
			$message = ( $_GET['view'] == 'next' ) ? 'No_newer_topics' : 'No_older_topics';
			message_die(GENERAL_MESSAGE, $message);
		}
	}
}

//
// This rather complex gaggle of code handles querying for topics but
// also allows for direct linking to a post (and the calculation of which
// page the post is on and the correct display of viewtopic)
//
$join_sql_table = ( empty($post_id) ) ? '' : ", ".POSTS_TABLE." p, ".POSTS_TABLE." p2 ";
$join_sql  = ( empty($post_id) ) ? "t.topic_id = $topic_id" : "p.post_id = $post_id AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_id <= $post_id";
$count_sql = ( empty($post_id) ) ? '' : ", COUNT(p2.post_id) AS prev_posts";
$order_sql = ( empty($post_id) ) ? '' : "GROUP BY p.post_id, t.topic_id, t.topic_title, t.topic_status, t.topic_replies, t.topic_time, t.topic_type, t.topic_vote, t.topic_last_post_id,
f.forum_name, f.forum_status, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_edit, f.auth_delete, f.auth_sticky, f.auth_announce, f.auth_pollcreate, f.auth_vote, f.auth_attachments, f.auth_download, f.forum_desc,
t.topic_attachment, c.cat_title ORDER BY p.post_id ASC";

$sql = "SELECT t.topic_id, t.topic_title, t.topic_status, t.topic_replies, t.topic_time, t.topic_type, t.topic_vote, t.topic_last_post_id, f.forum_name, f.forum_desc, f.forum_status, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_edit, f.auth_delete, f.auth_sticky, f.auth_announce, f.auth_pollcreate, f.auth_vote, f.auth_attachments, f.auth_download, t.topic_attachment, c.cat_title ".$count_sql."
		FROM ".TOPICS_TABLE." t, ".FORUMS_TABLE." f, ".CATEGORIES_TABLE." c ". $join_sql_table."
		WHERE $join_sql
			AND f.forum_id = t.forum_id
			AND f.cat_id = c.cat_id
		$order_sql";
$result = $db->sql_query($sql);
if ( !($forum_topic_data = $db->sql_fetchrow($result, SQL_ASSOC)) ) {
	message_die(GENERAL_MESSAGE, $lang['Topic_post_not_exist']);
}
$db->sql_freeresult($result);

$forum_id = intval($forum_topic_data['forum_id']);



//
// Start auth check
//
$is_auth = auth(AUTH_ALL, $forum_id, $userdata, $forum_topic_data);
if (!$is_auth['auth_view'] || !$is_auth['auth_read']) {
	if (!is_user()) { url_redirect(getlink('Your_Account'), true); }
	$message = ( !$is_auth['auth_view'] ) ? $lang['Topic_post_not_exist'] : sprintf($lang['Sorry_auth_read'], $is_auth['auth_read_type']);
	message_die(GENERAL_MESSAGE, $message);
}
//
// End auth check
//

$forum_name = $forum_topic_data['forum_name'];
$forum_desc = $forum_topic_data['forum_desc'];
$topic_title = $forum_topic_data['topic_title'];
$topic_id = intval($forum_topic_data['topic_id']);
$topic_time = $forum_topic_data['topic_time'];

if ( !empty($post_id) ) {
	$start = floor(($forum_topic_data['prev_posts'] - 1) / intval($board_config['posts_per_page'])) * intval($board_config['posts_per_page']);
}

//
// Is user watching this thread?
//
$is_watching_topic = $can_watch_topic = 0;
if (is_user()) {
	$can_watch_topic = TRUE;
	$sql = "SELECT notify_status
		FROM ".TOPICS_WATCH_TABLE."
		WHERE topic_id = $topic_id
			AND user_id = ".$userdata['user_id'];
	$result = $db->sql_query($sql);

	if ( $row = $db->sql_fetchrow($result) ) {
		if ( isset($_GET['unwatch']) ) {
			if ( $_GET['unwatch'] == 'topic' ) {
				$sql_priority = (SQL_LAYER == "mysql") ? "LOW_PRIORITY" : '';
				$db->sql_query("DELETE $sql_priority FROM ".TOPICS_WATCH_TABLE."
					WHERE topic_id = $topic_id
						AND user_id = ".$userdata['user_id']);
			}
			url_refresh(getlink("&file=viewtopic&".POST_TOPIC_URL."=$topic_id&start=$start"));
			$message = $lang['No_longer_watching'].'<br /><br />'.sprintf($lang['Click_return_topic'], '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;start=$start").'">', '</a>');
			message_die(GENERAL_MESSAGE, $message);
		} else {
			$is_watching_topic = TRUE;
			if ( $row['notify_status'] ) {
				$sql_priority = (SQL_LAYER == "mysql") ? "LOW_PRIORITY" : '';
				$sql = "UPDATE $sql_priority ".TOPICS_WATCH_TABLE."
					SET notify_status = 0
					WHERE topic_id = $topic_id
						AND user_id = ".$userdata['user_id'];
				$result = $db->sql_query($sql);
			}
		}
	} else {
		if ( isset($_GET['watch']) ) {
			if ( $_GET['watch'] == 'topic' ) {
				$is_watching_topic = TRUE;
				$sql_priority = (SQL_LAYER == "mysql") ? "LOW_PRIORITY" : '';
				$sql = "INSERT $sql_priority INTO ".TOPICS_WATCH_TABLE." (user_id, topic_id, notify_status)
					VALUES (".$userdata['user_id'].", $topic_id, 0)";
				$result = $db->sql_query($sql);
			}
			url_refresh(getlink("&file=viewtopic&".POST_TOPIC_URL."=$topic_id&start=$start"));
			$message = $lang['You_are_watching'].'<br /><br />'.sprintf($lang['Click_return_topic'], '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;start=$start").'">', '</a>');
			message_die(GENERAL_MESSAGE, $message);
		}
	}
} else {
	if ( isset($_GET['unwatch']) ) {
		if ( $_GET['unwatch'] == 'topic' ) {
			url_redirect(getlink('Your_Account'), true);
		}
	}
}

//
// Generate a 'Show posts in previous x days' select box. If the postdays var is POSTed
// then get it's value, find the number of topics with dates newer than it (to properly
// handle pagination) and alter the main query
//
$previous_days = array(0, 1, 7, 14, 30, 90, 180, 364);
$previous_days_text = array($lang['All_Posts'], $lang['1_Day'], $lang['7_Days'], $lang['2_Weeks'], $lang['1_Month'], $lang['3_Months'], $lang['6_Months'], $lang['1_Year']);

if( !empty($_POST['postdays']) || !empty($_GET['postdays']) ) {
	$post_days = ( !empty($_POST['postdays']) ) ? intval($_POST['postdays']) : intval($_GET['postdays']);
	$min_post_time = gmtime() - (intval($post_days) * 86400);
	$sql = "SELECT COUNT(p.post_id) AS num_posts
		FROM ".TOPICS_TABLE." t, ".POSTS_TABLE." p
		WHERE t.topic_id = $topic_id
			AND p.topic_id = t.topic_id
			AND p.post_time >= $min_post_time";
	$result = $db->sql_query($sql);
	$total_replies = ( $row = $db->sql_fetchrow($result, SQL_NUM) ) ? intval($row[0]) : 0;
	$db->sql_freeresult($result);
	$limit_posts_time = "AND p.post_time >= $min_post_time ";
	if ( !empty($_POST['postdays'])) {
		$start = 0;
	}
} else {
	$total_replies = intval($forum_topic_data['topic_replies']) + 1;
	$limit_posts_time = '';
	$post_days = 0;
}

$select_post_days = '<select name="postdays">';
for($i = 0; $i < count($previous_days); $i++) {
	$selected = ($post_days == $previous_days[$i]) ? ' selected="selected"' : '';
	$select_post_days .= '<option value="'.$previous_days[$i].'"'.$selected.'>'.$previous_days_text[$i].'</option>';
}
$select_post_days .= '</select>';

//
// Decide how to order the post display
//
if ( !empty($_POST['postorder']) || !empty($_GET['postorder']) ) {
	$post_order = (!empty($_POST['postorder'])) ? htmlprepare($_POST['postorder']) : htmlprepare($_GET['postorder']);
	$post_time_order = ($post_order == "asc") ? "ASC" : "DESC";
} else {
	$post_order = 'asc';
	$post_time_order = 'ASC';
}

$select_post_order = '<select name="postorder">';
if ( $post_time_order == 'ASC' ) {
	$select_post_order .= '<option value="asc" selected="selected">'.$lang['Oldest_First'].'</option><option value="desc">'.$lang['Newest_First'].'</option>';
} else {
	$select_post_order .= '<option value="asc">'.$lang['Oldest_First'].'</option><option value="desc" selected="selected">'.$lang['Newest_First'].'</option>';
}
$select_post_order .= '</select>';

//
// Go ahead and pull all data for this topic
//
/* lanzer speedup for large forums
$total_pages = ceil($total_replies/$board_config['posts_per_page']);
$on_page = floor($start / $board_config['posts_per_page']) + 1;
if ($start > 100 && ($total_replies / 2) < $start) {
	$reverse = TRUE;
	$last_page_posts = $total_replies - ($board_config['posts_per_page'] * ($total_pages - 1));
}
if (isset($reverse)) {
	$limit_string = ($total_pages == $on_page) ? $last_page_posts : ($last_page_posts + ($total_pages - $on_page - 1) * $board_config['posts_per_page'] ).','. $board_config['posts_per_page'];
	$sql = "SELECT p.post_id FROM ".POSTS_TABLE." p USE INDEX(topic_n_id) WHERE p.topic_id = $topic_id $limit_posts_time ORDER BY p.post_id DESC LIMIT $limit_string" ;
} else {
	$sql = "SELECT p.post_id FROM ".POSTS_TABLE." p WHERE p.topic_id = $topic_id $limit_posts_time LIMIT $start, ".$board_config['posts_per_page'];
}
$result = $db->sql_query($sql);
while (list($p_id) = $db->sql_fetchrow($result)) {
	$p_array[] = $p_id;
}
$post_index = implode(",",$p_array);
$sql = "SELECT u.username, u.user_id, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_regdate, u.user_msnm, u.user_viewemail, u.user_rank, u.user_sig, u.user_avatar, u.user_allow_viewonline, u.user_allowsmile, p.*,  pt.post_text, pt.post_subject
   FROM ".POSTS_TABLE." p, ".USERS_TABLE." u, ".POSTS_TEXT_TABLE." pt
   WHERE p.post_id in ($post_index)
	  AND pt.post_id = p.post_id
	  AND u.user_id = p.poster_id
   ORDER BY p.post_time $post_time_order";
*/
$sql = "SELECT u.username, u.user_id, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_regdate, u.user_msnm, u.user_viewemail, u.user_rank, u.user_sig, u.user_avatar, u.user_avatar_type, u.user_allowavatar, u.user_allowsmile, u.bio, u.user_timezone, u.user_occ, u.user_interests, p.*, pt.post_text, pt.post_subject";
if (isset($userinfo['server_specs'])){ $sql .= ", u.server_specs"; }
$sql .=" FROM ".POSTS_TABLE." p, ".USERS_TABLE." u, ".POSTS_TEXT_TABLE." pt
		WHERE p.topic_id = $topic_id
				$limit_posts_time
				AND pt.post_id = p.post_id
				AND u.user_id = p.poster_id
		ORDER BY p.post_time $post_time_order
		LIMIT $start, ".(isset($finish)? (($finish - $start) > 0 ? $finish - $start : -$finish): $board_config['posts_per_page']);
$result = $db->sql_query($sql);

$postrow = array();
if ($row = $db->sql_fetchrow($result)) {
	do {
		$postrow[] = $row;
	}
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC));
	$db->sql_freeresult($result);
	$total_posts = count($postrow);
} else {
	require_once('includes/phpBB/functions_admin.php');
	sync('topic', $topic_id);
	message_die(GENERAL_MESSAGE, $lang['No_posts_topic']);
}

$resync = FALSE;
if ($forum_topic_data['topic_replies'] + 1 < $start + count($postrow)) {
	$resync = TRUE;
} elseif ($start + $board_config['posts_per_page'] > $forum_topic_data['topic_replies']) {
	$row_id = intval($forum_topic_data['topic_replies']) % intval($board_config['posts_per_page']);
	$resync = ($postrow[$row_id]['post_id'] != $forum_topic_data['topic_last_post_id'] || $start + count($postrow) < $forum_topic_data['topic_replies']);
} elseif (count($postrow) < $board_config['posts_per_page']) {
	$resync = TRUE;
}

if ($resync) {
	require_once('includes/phpBB/functions_admin.php');
	sync('topic', $topic_id);
	$result = $db->sql_query('SELECT COUNT(post_id) AS total FROM '.POSTS_TABLE.' WHERE topic_id = '.$topic_id);
	$row = $db->sql_fetchrow($result);
	$total_replies = $row['total'];
	$db->sql_freeresult($result);
}

$ranksrow = $db->sql_ufetchrowset("SELECT * FROM ".RANKS_TABLE." ORDER BY rank_special, rank_min",SQL_ASSOC);

//
// Define censored word matches
//
$orig_word = array();
$replacement_word = array();
obtain_word_list($orig_word, $replacement_word);

//
// Censor topic title
//
if (count($orig_word)) { $topic_title = preg_replace($orig_word, $replacement_word, $topic_title); }

//
// Was a highlight request part of the URI?
//
$highlight_match = $highlight = '';
if (isset($_GET['highlight'])) {
	// Split words and phrases
	$words = explode(' ', htmlprepare($_GET['highlight']));
	for($i = 0; $i < sizeof($words); $i++) {
		$words[$i] = trim($words[$i]);
		if (trim($words[$i]) != '') {
			$highlight_match .= (($highlight_match != '') ? '|' : '').str_replace('*', '\w*', phpbb_preg_quote($words[$i], '#'));
		}
	}
	unset($words);
	$highlight = urlencode($_GET['highlight']);
}

//
// Post, reply and other URL generation for
// templating vars
//
$printer_topic_url = getlink("&amp;file=viewtopic&amp;printertopic=1&amp;".POST_TOPIC_URL."=$topic_id&amp;start=$start&amp;postdays=$post_days&amp;postorder=$post_order&amp;vote=viewresult");
$new_topic_url = getlink("&amp;file=posting&amp;mode=newtopic&amp;".POST_FORUM_URL."=$forum_id");
$reply_topic_url = getlink("&amp;file=posting&amp;mode=reply&amp;".POST_TOPIC_URL."=$topic_id");
$view_forum_url = getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=$forum_id");
$view_prev_topic_url = getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;view=previous");
$view_next_topic_url = getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;view=next");

//
// Mozilla navigation bar
//
$nav_links['prev'] = array(
	'url' => $view_prev_topic_url,
	'title' => $lang['View_previous_topic']
);
$nav_links['next'] = array(
	'url' => $view_next_topic_url,
	'title' => $lang['View_next_topic']
);
$nav_links['up'] = array(
	'url' => $view_forum_url,
	'title' => $forum_name
);

$reply_img = ( $forum_topic_data['forum_status'] == FORUM_LOCKED || $forum_topic_data['topic_status'] == TOPIC_LOCKED ) ? $images['reply_locked'] : $images['reply_new'];
$reply_alt = ( $forum_topic_data['forum_status'] == FORUM_LOCKED || $forum_topic_data['topic_status'] == TOPIC_LOCKED ) ? $lang['Topic_locked'] : $lang['Reply_to_topic'];
$post_img = ( $forum_topic_data['forum_status'] == FORUM_LOCKED ) ? $images['post_locked'] : $images['post_new'];
$post_alt = ( $forum_topic_data['forum_status'] == FORUM_LOCKED ) ? $lang['Forum_locked'] : $lang['Post_new_topic'];
$printer_img = $images['printer'];
$printer_alt =  _PRINTER;
//
// Set a cookie for this topic
//
if (is_user()) {
	$tracking_topics = $CPG_SESS[$module_name]['track_topics'] ?? array();
	$tracking_forums = $CPG_SESS[$module_name]['track_forums'] ?? array();
	if ( !empty($tracking_topics[$topic_id]) && !empty($tracking_forums[$forum_id]) ) {
		$topic_last_read = ( $tracking_topics[$topic_id] > $tracking_forums[$forum_id] ) ? $tracking_topics[$topic_id] : $tracking_forums[$forum_id];
	} else if ( !empty($tracking_topics[$topic_id]) || !empty($tracking_forums[$forum_id]) ) {
		$topic_last_read = ( !empty($tracking_topics[$topic_id]) ) ? $tracking_topics[$topic_id] : $tracking_forums[$forum_id];
	} else {
		$topic_last_read = $userdata['user_lastvisit'];
	}
	$CPG_SESS[$module_name]['track_topics'][$topic_id] = gmtime();
}

//
// Output page header
//
$page_title = $forum_topic_data['cat_title'].' '._BC_DELIM.' '.$forum_name.' '._BC_DELIM.' '. $topic_title;
if(isset($_GET['printertopic'])) {
	$gen_print_header = true;
}
require_once('includes/phpBB/page_header.php');

make_jumpbox('viewforum', $forum_id);

//
// User authorisation levels output
//
$s_auth_can = ( ( $is_auth['auth_post'] ) ? $lang['Rules_post_can'] : $lang['Rules_post_cannot'] ).'<br />';
$s_auth_can .= ( ( $is_auth['auth_reply'] ) ? $lang['Rules_reply_can'] : $lang['Rules_reply_cannot'] ).'<br />';
$s_auth_can .= ( ( $is_auth['auth_edit'] ) ? $lang['Rules_edit_can'] : $lang['Rules_edit_cannot'] ).'<br />';
$s_auth_can .= ( ( $is_auth['auth_delete'] ) ? $lang['Rules_delete_can'] : $lang['Rules_delete_cannot'] ).'<br />';
$s_auth_can .= ( ( $is_auth['auth_vote'] ) ? $lang['Rules_vote_can'] : $lang['Rules_vote_cannot'] ).'<br />';
//if (defined('BBAttach_mod')) {
	if (!intval($attach_config['disable_mod'])) {
		$s_auth_can .= ( ( $is_auth['auth_attachments'] && $is_auth['auth_post'] ) ? $lang['Rules_attach_can'] : $lang['Rules_attach_cannot'] ).'<br />';
		$s_auth_can .= ( ( $is_auth['auth_download']) ? $lang['Rules_download_can'] : $lang['Rules_download_cannot'] ).'<br />';
	}
$topic_mod = '';

if ( $is_auth['auth_mod'] ) {
	$s_auth_can .= sprintf($lang['Rules_moderate'], '<a href="'.getlink("&amp;file=modcp&amp;".POST_FORUM_URL."=$forum_id").'">', '</a>');
	$topic_mod .= '<a href="'.getlink("&amp;file=modcp&amp;".POST_TOPIC_URL."=$topic_id&amp;mode=delete").'"><img src="'.$images['topic_mod_delete'].'" alt="'.$lang['Delete_topic'].'" title="'.$lang['Delete_topic'].'" style="border:0;" /></a>&nbsp;';
	$topic_mod .= '<a href="'.getlink("&amp;file=modcp&amp;".POST_TOPIC_URL."=$topic_id&amp;mode=move"). '"><img src="'.$images['topic_mod_move'].'" alt="'.$lang['Move_topic'].'" title="'.$lang['Move_topic'].'" style="border:0;" /></a>&nbsp;';
	$topic_mod .= ( $forum_topic_data['topic_status'] == TOPIC_UNLOCKED ) ? '<a href="'.getlink("&amp;file=modcp&amp;".POST_TOPIC_URL."=$topic_id&amp;mode=lock").'"><img src="'.$images['topic_mod_lock'].'" alt="'.$lang['Lock_topic'].'" title="'.$lang['Lock_topic'].'" style="border:0;" /></a>&nbsp;' : '<a href="'.getlink("&amp;file=modcp&amp;".POST_TOPIC_URL."=$topic_id&amp;mode=unlock").'"><img src="'.$images['topic_mod_unlock'].'" alt="'.$lang['Unlock_topic'].'" title="'.$lang['Unlock_topic'].'" style="border:0;" /></a>&nbsp;';
	$topic_mod .= '<a href="'.getlink("&amp;file=modcp&amp;".POST_TOPIC_URL."=$topic_id&amp;mode=split").'"><img src="'.$images['topic_mod_split'].'" alt="'.$lang['Split_topic'].'" title="'.$lang['Split_topic'].'" style="border:0;" /></a>&nbsp;';
//-- mod : merge -----------------------------------------------------------------------------------
	$topic_mod .= '<a href="'.getlink("&amp;file=merge&amp;".POST_TOPIC_URL.'='.$topic_id).'"><img src="'.$images['topic_mod_merge'].'" alt="'.$lang['Merge_topics'].'" title="'.$lang['Merge_topics'].'" style="border:0;" /></a>&nbsp;';
//-- fin mod : merge -------------------------------------------------------------------------------
}

//
// Topic watch information
//
$s_watching_topic = $s_watching_topic_img ='';
if ( $can_watch_topic ) {
	if ( $is_watching_topic ) {
		$s_watching_topic = '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;unwatch=topic&amp;start=$start").'">'.$lang['Stop_watching_topic'].'</a>';
		$s_watching_topic_img = ( isset($images['Topic_un_watch']) ) ? '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;unwatch=topic&amp;start=$start").'"><img src="'.$images['Topic_un_watch'].'" alt="'.$lang['Stop_watching_topic'].'" title="'.$lang['Stop_watching_topic'].'" style="border:0;" /></a>' : '';
	} else {
		$s_watching_topic = '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;watch=topic&amp;start=$start").'">'.$lang['Start_watching_topic'].'</a>';
		$s_watching_topic_img = ( isset($images['Topic_watch']) ) ? '<a href="'.getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;watch=topic&amp;start=$start").'"><img src="'.$images['Topic_watch'].'" alt="'.$lang['Stop_watching_topic'].'" title="'.$lang['Start_watching_topic'].'" style="border:0;" /></a>' : '';
	}
}

//
// If we've got a hightlight set pass it on to pagination,
// I get annoyed when I lose my highlight after the first page.
//
$pagination_printertopic = $pagination_highlight = $pagination_finish_rel ='';
if(isset($_GET['printertopic'])) {
	$pagination_printertopic = "printertopic=1&amp;";
}
if ($highlight != '') {
	$pagination_highlight = "&amp;highlight=$highlight";
}
$pagination_ppp = $board_config['posts_per_page'];
if(isset($finish)) {
	$pagination_ppp = ($finish < 0)? -$finish: ($finish - $start);
	$pagination_finish_rel = "&amp;finish_rel=". -$pagination_ppp;
}

$pagination = generate_pagination("&amp;file=viewtopic&amp;". $pagination_printertopic.POST_TOPIC_URL."=$topic_id&amp;postdays=$post_days&amp;postorder=$post_order".$pagination_highlight.$pagination_finish_rel, $total_replies, $pagination_ppp, $start);
if ($pagination != '' && !empty($pagination_printertopic)) {
	$pagination .= ' &nbsp;<a href="'.getlink('&amp;file=viewtopic&amp;?'. $pagination_printertopic. POST_TOPIC_URL."=$topic_id&amp;postdays=$post_days&amp;postorder=$post_order".$pagination_highlight.'&amp;start=0&amp;finish_rel=-10000').'" title="	 :| |:	">:|&nbsp;|:</a>';
}

//
// Send vars to template
//
$template->assign_vars(array(
		'START_REL' => ($start + 1),
		'FINISH_REL' => (isset($_GET['finish_rel'])? intval($_GET['finish_rel']) : ($board_config['posts_per_page'] - $start)),
		'FORUM_ID' => $forum_id,
		'FORUM_NAME' => $forum_name,
		'FORUM_DESC' => $forum_desc,
		'TOPIC_ID' => $topic_id,
		'TOPIC_TITLE' => $topic_title,
		'PAGINATION' => $pagination,
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $pagination_ppp ) + 1 ), ceil( $total_replies / $pagination_ppp )),

		'POST_IMG' => $post_img,
		'REPLY_IMG' => $reply_img,
		'PRINTER_IMG' => $printer_img,
		'BC_DELIM' => _BC_DELIM,
		'L_AUTHOR' => $lang['Author'],
		'L_MESSAGE' => $lang['Message'],
		'L_POSTED' => $lang['Posted'],
		'L_POST_SUBJECT' => $lang['Post_subject'],
		'L_VIEW_NEXT_TOPIC' => $lang['View_next_topic'],
		'L_VIEW_PREVIOUS_TOPIC' => $lang['View_previous_topic'],
		'L_POST_NEW_TOPIC' => $post_alt,
		'L_POST_REPLY_TOPIC' => $reply_alt,
		'L_PRINTER_TOPIC' => $printer_alt,
		'L_BACK_TO_TOP_LINK' =>get_uri(),
		'L_BACK_TO_TOP' => $lang['Back_to_top'],
		'L_DISPLAY_POSTS' => $lang['Display_posts'],
		'L_LOCK_TOPIC' => $lang['Lock_topic'],
		'L_UNLOCK_TOPIC' => $lang['Unlock_topic'],
		'L_MOVE_TOPIC' => $lang['Move_topic'],
		'L_SPLIT_TOPIC' => $lang['Split_topic'],
		'L_DELETE_TOPIC' => $lang['Delete_topic'],
		'L_GOTO_PAGE' => $lang['Goto_page'],
		'L_GO' => $lang['Go'],

		'S_TOPIC_LINK' => POST_TOPIC_URL,
		'S_SELECT_POST_DAYS' => $select_post_days,
		'S_SELECT_POST_ORDER' => $select_post_order,
		'S_POST_DAYS_ACTION' => getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;start=$start"),
		'S_AUTH_LIST' => $s_auth_can,
		'S_TOPIC_ADMIN' => $topic_mod,
		'S_WATCH_TOPIC' => $s_watching_topic,
		'S_WATCH_TOPIC_IMG' => $s_watching_topic_img,

		'U_VIEW_TOPIC' => getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;start=$start&amp;postdays=$post_days&amp;postorder=$post_order&amp;highlight=$highlight"),
		'U_VIEW_FORUM' => $view_forum_url,
		'U_VIEW_OLDER_TOPIC' => $view_prev_topic_url,
		'U_VIEW_NEWER_TOPIC' => $view_next_topic_url,
		'U_POST_NEW_TOPIC' => $new_topic_url,
		'U_PRINTER_TOPIC' => $printer_topic_url,
		'U_POST_REPLY_TOPIC' => $reply_topic_url)
);
//
// Does this topic contain a poll?
//
if ( !empty($forum_topic_data['topic_vote']) )
{
	$s_hidden_fields = '';

	$sql = "SELECT vd.vote_id, vd.vote_text, vd.vote_start, vd.vote_length, vr.vote_option_id, vr.vote_option_text, vr.vote_result
		FROM ".VOTE_DESC_TABLE." vd, ".VOTE_RESULTS_TABLE." vr
		WHERE vd.topic_id = $topic_id AND vr.vote_id = vd.vote_id
		ORDER BY vr.vote_option_id ASC";
	$vote_info = $db->sql_ufetchrowset($sql, SQL_ASSOC);
	if ( $vote_info ) {
		$vote_options = is_countable($vote_info) ? count($vote_info) : 0;
		$vote_id = $vote_info[0]['vote_id'];
		$vote_title = $vote_info[0]['vote_text'];

		$result = $db->sql_query("SELECT vote_id FROM ".VOTE_USERS_TABLE." WHERE vote_id = $vote_id AND vote_user_id = ".intval($userdata['user_id']));
		$user_voted = ( $row = $db->sql_fetchrow($result) ) ? TRUE : 0;
		$db->sql_freeresult($result);
		if ( isset($_GET['vote']) || isset($_POST['vote']) ) {
			$view_result = ( ( $_GET['vote'] ?? $_POST['vote'] ) == 'viewresult' ) ? TRUE : 0;
		} else {
			$view_result = 0;
		}

		$poll_expired = ( $vote_info[0]['vote_length'] ) ? ( ( $vote_info[0]['vote_start'] + $vote_info[0]['vote_length'] < gmtime() ) ? TRUE : 0 ) : 0;

		if ( $user_voted || $view_result || $poll_expired || !$is_auth['auth_vote'] || $forum_topic_data['topic_status'] == TOPIC_LOCKED )
		{
			$vote_results_sum = 0;

			for($i = 0; $i < $vote_options; $i++) {
				$vote_results_sum += $vote_info[$i]['vote_result'];
			}

			$vote_graphic = 0;
			$vote_graphic_max = is_countable($images['voting_graphic']) ? count($images['voting_graphic']) : 0;

			for($i = 0; $i < $vote_options; $i++) {
				$vote_percent = ( $vote_results_sum > 0 ) ? $vote_info[$i]['vote_result'] / $vote_results_sum : 0;
				$vote_graphic_length = round($vote_percent * $board_config['vote_graphic_length']);

				$vote_graphic_img = $images['voting_graphic'][$vote_graphic];
				$vote_graphic = ($vote_graphic < $vote_graphic_max - 1) ? $vote_graphic + 1 : 0;

				if ( count($orig_word) ) {
					$vote_info[$i]['vote_option_text'] = preg_replace($orig_word, $replacement_word, $vote_info[$i]['vote_option_text']);
				}

				$template->assign_block_vars('poll_option', array(
					'POLL_OPTION_CAPTION' => $vote_info[$i]['vote_option_text'],
					'POLL_OPTION_RESULT' => $vote_info[$i]['vote_result'],
					'POLL_OPTION_PERCENT' => sprintf("%.1d%%", ($vote_percent * 100)),

					'POLL_OPTION_IMG' => $vote_graphic_img,
					'POLL_OPTION_IMG_WIDTH' => $vote_graphic_length)
				);
			}

			$template->assign_vars(array(
				'S_POLL_RESULTS' => true,
				'L_TOTAL_VOTES' => $lang['Total_votes'],
				'TOTAL_VOTES' => $vote_results_sum)
			);
		}
		else
		{
			for($i = 0; $i < $vote_options; $i++)
			{
				if ( count($orig_word) ) {
					$vote_info[$i]['vote_option_text'] = preg_replace($orig_word, $replacement_word, $vote_info[$i]['vote_option_text']);
				}

				$template->assign_block_vars('poll_option', array(
					'POLL_OPTION_ID' => $vote_info[$i]['vote_option_id'],
					'POLL_OPTION_CAPTION' => $vote_info[$i]['vote_option_text'])
				);
			}

			$template->assign_vars(array(
				'S_POLL_RESULTS' => false,
				'L_SUBMIT_VOTE' => $lang['Submit_vote'],
				'L_VIEW_RESULTS' => $lang['View_results'],

				'U_VIEW_RESULTS' => getlink("&amp;file=viewtopic&amp;".POST_TOPIC_URL."=$topic_id&amp;postdays=$post_days&amp;postorder=$post_order&amp;vote=viewresult"))
			);

			$s_hidden_fields = '<input type="hidden" name="topic_id" value="'.$topic_id.'" /><input type="hidden" name="mode" value="vote" />';
		}

		if ( count($orig_word) ) {
			$vote_title = preg_replace($orig_word, $replacement_word, $vote_title);
		}

		$template->assign_vars(array(
			'POLL_QUESTION' => $vote_title,

			'S_HIDDEN_FIELDS' => $s_hidden_fields,
			'S_POLL_ACTION' => getlink("&amp;file=posting&amp;mode=vote&amp;".POST_TOPIC_URL."=$topic_id"))
		);
	}
	$template->assign_var('S_HAS_POLL', is_array($vote_info));
}else{
	$template->assign_var('S_HAS_POLL', 0);
}
//
// Initializes some templating variables for displaying Attachments in Posts
//
//if (defined('BBAttach_mod')) {
	$switch_attachment = (empty($forum_topic_data) && !empty($forum_row)) ? $forum_row['topic_attachment'] : $forum_topic_data['topic_attachment'];
	if ( intval($switch_attachment) != 0 && !intval($attach_config['disable_mod']) && $is_auth['auth_download'] && $is_auth['auth_view'] ) {
		$post_id_array = array();
		for ($i = 0; $i < $total_posts; $i++) {
			if ($postrow[$i]['post_attachment'] == 1) {
				$post_id_array[] = $postrow[$i]['post_id'];
			}
		}
		if (count($post_id_array) > 0) {
			$rows = get_attachments_from_post($post_id_array);
			$num_rows = is_countable($rows) ? count($rows) : 0;
			if ($num_rows > 0) {
				reset($attachments);
				for ($i = 0; $i < $num_rows; $i++) {
					$attachments['_'.$rows[$i]['post_id']][] = $rows[$i];
				}
				init_complete_extensions_data();
				$template->assign_vars(array(
					'L_POSTED_ATTACHMENTS' => $lang['Posted_attachments'],
					'L_KILOBYTE' => $lang['KB'])
				);
			}
		}
	}

//
// Update the topic view counter
//
$db->sql_query("UPDATE ".TOPICS_TABLE." SET topic_views = topic_views + 1 WHERE topic_id = $topic_id");

if (is_active('coppermine')) {
	list($ugall, $ugalldir) = $db->sql_ufetchrow("SELECT prefix, dirname FROM ".$prefix."_cpg_installs LIMIT 1");
} else {
	$ugall = false;
}
$ugalleries = array();
//
// Okay, let's do the loop, yeah come on baby let's do the loop
// and it goes like this ...
//
for ($i = 0; $i < $total_posts; $i++) {
	$poster_id = $postrow[$i]['user_id'];
	$poster = ($poster_id == ANONYMOUS) ? $lang['Guest'] : $postrow[$i]['username'];

	$post_date = create_date($board_config['default_dateformat'], $postrow[$i]['post_time']);

	$poster_posts = ($poster_id != ANONYMOUS) ? $lang['Posts'].': '.$postrow[$i]['user_posts'] : '';

	$poster_from = ($postrow[$i]['user_from'] && $poster_id != ANONYMOUS) ? $lang['Location'].': '.$postrow[$i]['user_from'] : '';
	$poster_from = preg_replace('#.gif#m', '', $poster_from);
	$poster_joined = ($poster_id != ANONYMOUS) ? $lang['Joined'].': '.formatDateTime($postrow[$i]['user_regdate'], '%b %d, %Y') : '';
	$poster_bio = ($poster_id != ANONYMOUS && $postrow[$i]['bio'] != '') ? sprintf($lang['About_user'],$postrow[$i]['username']).': '.$postrow[$i]['bio'].'<br/ >' : ''; 
	$poster_timezone = ($poster_id != ANONYMOUS && $postrow[$i]['user_timezone'] != '') ? $lang['Timezone']. ': '.$lang['tz'][$postrow[$i]['user_timezone']].'<br/ >' : '';
	$poster_occ = ($poster_id != ANONYMOUS && $postrow[$i]['user_occ'] != '') ? $lang['Occupation'].': '.$postrow[$i]['user_occ'].'<br/ >' : ''; 
	$poster_interests = ($poster_id != ANONYMOUS && (!empty($postrow[$i]['user_interests'])) ) ? $lang['Interests'].': '.$postrow[$i]['user_interests'] : '';
	$poster_avatar = '';
	if ( $postrow[$i]['user_avatar_type'] && $poster_id != ANONYMOUS && $postrow[$i]['user_allowavatar'] ) {
		switch( $postrow[$i]['user_avatar_type'] )
		{
			case USER_AVATAR_UPLOAD:
				$poster_avatar = ( $MAIN_CFG['avatar']['allow_upload'] ) ? '<img src="'.$MAIN_CFG['avatar']['path'].'/'.$postrow[$i]['user_avatar'].'" alt="" style="border:0;" />' : '';
				break;
			case USER_AVATAR_REMOTE:
				$poster_avatar = ( $MAIN_CFG['avatar']['allow_remote'] ) ? '<img src="'.$postrow[$i]['user_avatar'].'" alt="" style="border:0;" />' : '';
				break;
			case USER_AVATAR_GALLERY:
				$poster_avatar = ( $MAIN_CFG['avatar']['allow_local'] ) ? '<img src="'.$MAIN_CFG['avatar']['gallery_path'].'/'.$postrow[$i]['user_avatar'].'" alt="" style="border:0;" />' : '';
				break;
		}
	}

	//
	// Default Avatar MOD - Begin
	//
	if (empty($poster_avatar) && $poster_id != ANONYMOUS && isset($images['default_avatar'])) {
		$poster_avatar = '<img src="'.	$images['default_avatar'] .'" alt="" style="border:0;" />';
	}
	if ($poster_id == ANONYMOUS && isset($images['guest_avatar'])) {
		$poster_avatar = '<img src="'.	$images['guest_avatar'] .'" alt="" style="border:0;" />';
	}

	//
	// Define the little post icon
	//
	if (is_user() && $postrow[$i]['post_time'] > $userdata['user_lastvisit'] && $postrow[$i]['post_time'] > $topic_last_read) {
		$mini_post_img = $images['icon_minipost_new'];
		$mini_post_alt = $lang['New_post'];
	} else {
		$mini_post_img = $images['icon_minipost'];
		$mini_post_alt = $lang['Post'];
	}

	$mini_post_url = getlink("&amp;file=viewtopic&amp;".POST_POST_URL.'='.$postrow[$i]['post_id']).'#'.$postrow[$i]['post_id'];

	//
	// Generate ranks, set them to empty string initially.
	//
	$poster_rank = '';
	$rank_image = '';
	if ($poster_id != ANONYMOUS) {
		if ($postrow[$i]['user_rank']) {
			for($j = 0; $j < (is_countable($ranksrow) ? count($ranksrow) : 0); $j++) {
				if ( $postrow[$i]['user_rank'] == $ranksrow[$j]['rank_id'] && $ranksrow[$j]['rank_special'] ) {
					$poster_rank = $ranksrow[$j]['rank_title'];
					$rank_image = ( $ranksrow[$j]['rank_image'] ) ? '<img src="'.$ranksrow[$j]['rank_image'].'" alt="'.$poster_rank.'" title="'.$poster_rank.'" style="border:0;" /><br />' : '';
				}
			}
		} else {
			for($j = 0; $j < (is_countable($ranksrow) ? count($ranksrow) : 0); $j++) {
				if ( $postrow[$i]['user_posts'] >= $ranksrow[$j]['rank_min'] && !$ranksrow[$j]['rank_special'] ) {
					$poster_rank = $ranksrow[$j]['rank_title'];
					$rank_image = ($ranksrow[$j]['rank_image']) ? '<img src="'.$ranksrow[$j]['rank_image'].'" alt="'.$poster_rank.'" title="'.$poster_rank.'" style="border:0;" /><br />' : '';
				}
			}
		}
	}

	//
	// Handle anon users posting with usernames
	//
	if ($poster_id == ANONYMOUS && $postrow[$i]['post_username'] != '') {
		$poster = $postrow[$i]['post_username'];
		$poster_rank = $lang['Guest'];
	}

	$old_theme = version_compare(THEME_VERSION, '9.1', '<');
	$profile_img = $profile = $pm_img = $pm = $email_img = $email = $www_img = $www = $icq_status_img = $icq_img = $icq = $aim_img = $aim = $msn_img = $msn = $yim_img = $yim = $skype_img = $skype = $gal_img = $gal = '';
	if ($poster_id != ANONYMOUS) {
		$profile = array(
			'IMG' => $images['icon_profile'],
			'TITLE' => $lang['Read_profile'],
			'URL' => getlink("Your_Account&amp;profile=$poster_id"),
			'TARGET' => false
		);
		if ($old_theme) {
			$profile_img = '<a href="'.$profile['URL'].'"><img src="'.$profile['IMG'].'" alt="'.$profile['TITLE'].'" title="'.$profile['TITLE'].'" style="border:0;" /></a>';
			$profile = '<a href="'.$profile['URL'].'">'.$profile['TITLE'].'</a>';
		}
		if (is_user() && is_active("Private_Messages")) {
			$pm = array(
				'IMG' => $images['icon_pm'],
				'TITLE' => $lang['Send_private_message'],
				'URL' => getlink("Private_Messages&amp;mode=post&amp;".POST_USERS_URL."=$poster_id"),
				'TARGET' => false
			);
			if ($old_theme) {
				$pm_img = '<a href="'.$pm['URL'].'"><img src="'.$pm['IMG'].'" alt="'.$pm['TITLE'].'" title="'.$pm['TITLE'].'" style="border:0;" /></a>';
				$pm = '<a href="'.$pm['URL'].'">'.$pm['TITLE'].'</a>';
			}
		}
		if (!empty($postrow[$i]['user_viewemail']) || $is_auth['auth_mod']) {
			$email = array(
				'IMG' => $images['icon_email'],
				'TITLE' => $lang['Send_email'],
				'URL' => ($board_config['board_email_form']) ? getlink("&amp;file=profile&amp;mode=email&amp;".POST_USERS_URL.'='.$poster_id) : 'mailto:'.$postrow[$i]['user_email'],
				'TARGET' => false
			);
			if ($old_theme) {
				$email_img = '<a href="'.$email['URL'].'"><img src="'.$email['IMG'].'" alt="'.$email['TITLE'].'" title="'.$email['TITLE'].'" style="border:0;" /></a>';
				$email = '<a href="'.$email['URL'].'">'.$email['TITLE'].'</a>';
			}
		}
		if ($postrow[$i]['user_website'] == 'http:///' || $postrow[$i]['user_website'] == 'http://'){
			$postrow[$i]['user_website'] = '';
		}
		if (!empty($postrow[$i]['user_website'])) {
			if (!str_starts_with($postrow[$i]['user_website'], 'http://')) {
				$postrow[$i]['user_website'] = 'http://'.$postrow[$i]['user_website'];
			}
			$www = array(
				'IMG' => $images['icon_www'],
				'TITLE' => $lang['Visit_website'],
				'URL' => $postrow[$i]['user_website'],
				'TARGET' => '_blank'
			);
			if ($old_theme) {
				$www_img = '<a href="'.$www['URL'].'" target="_blank"><img src="'.$www['IMG'].'" alt="'.$www['TITLE'].'" title="'.$www['TITLE'].'" style="border:0;" /></a>';
				$www = '<a href="'.$www['URL'].'" target="_blank">'.$www['TITLE'].'</a>';
			}
		}
		if (!empty($postrow[$i]['user_icq'])) {
			$icq = array(
				'IMG' => $images['icon_icq'],
				'TITLE' => $lang['ICQ'],
				'URL' => 'http://www.icq.com/people/about_me.php?uin='.$postrow[$i]['user_icq'],
				'TARGET' => '_blank'
			);
			if ($old_theme) {
				$icq_status_img = '<a href="http://wwp.icq.com/'.$postrow[$i]['user_icq'].'#pager"><img src="http://web.icq.com/whitepages/online?icq='.$postrow[$i]['user_icq'].'&img=5" style="border:0; width:18px; height:18px;" /></a>';
				$icq_img = '<a href="'.$icq['URL'].'" target="_blank"><img src="'.$icq['IMG'].'" alt="'.$icq['TITLE'].'" title="'.$icq['TITLE'].'" style="border:0;" /></a>';
				$icq = '<a href="'.$icq['URL'].'" target="_blank">'.$icq['TITLE'].'</a>';
			}
		}
		if (!empty($postrow[$i]['user_aim'])) {
			$aim = array(
				'IMG' => $images['icon_aim'],
				'TITLE' => $lang['AIM'],
				'URL' => 'aim:goim?screenname='.$postrow[$i]['user_aim'].'&amp;message=Hey+are+you+there?',
				'TARGET' => false
			);
			if ($old_theme) {
				$aim_img = '<a href="'.$aim['URL'].'"><img src="'.$aim['IMG'].'" alt="'.$aim['TITLE'].'" title="'.$aim['TITLE'].'" style="border:0;" /></a>';
				$aim = '<a href="'.$aim['URL'].'">'.$aim['TITLE'].'</a>';
			}
		}
		if (!empty($postrow[$i]['user_msnm'])) {
			$msn = array(
				'IMG' => $images['icon_msnm'],
				'TITLE' => $lang['MSNM'],
				'URL' => 'http://members.msn.com/'.$postrow[$i]['user_msnm'],
				'TARGET' => '_blank'
			);
			if ($old_theme) {
				$msn_img = '<a href="'.$msn['URL'].'" target="_blank"><img src="'.$msn['IMG'].'" alt="'.$msn['TITLE'].'" title="'.$msn['TITLE'].'" style="border:0;" /></a>';
				$msn = '<a href="'.$msn['URL'].'" target="_blank">'.$msn['TITLE'].'</a>';
			}
		}
		if (!empty($postrow[$i]['user_yim'])) {
			$yim = array(
				'IMG' => $images['icon_yim'],
				'TITLE' => $lang['YIM'],
				'URL' => 'http://edit.yahoo.com/config/send_webmesg?.target='.$postrow[$i]['user_yim'].'&amp;.src=pg',
				'TARGET' => '_blank'
			);
			if ($old_theme) {
				$yim_img = '<a href="'.$yim['URL'].'" target="_blank"><img src="'.$yim['IMG'].'" alt="'.$yim['TITLE'].'" title="'.$yim['TITLE'].'" style="border:0;" /></a>';
				$yim = '<a href="'.$yim['URL'].'" target="_blank">'.$yim['TITLE'].'</a>';
			}
		}
		if (!empty($postrow[$i]['user_skype'])) {
			$skype = array(
				'IMG' => $images['icon_skype'],
				'TITLE' => 'Skype',
				'URL' => 'callto://'.$postrow[$i]['user_skype'],
				'TARGET' => false
			);
			if ($old_theme) {
				$skype_img = '<a href="'.$skype['URL'].'"><img src="'.$skype['IMG'].'" alt="'.$skype['TITLE'].'" title="'.$skype['TITLE'].'" style="border:0;" /></a>';
				$skype = '<a href="'.$skype['URL'].'">'.$skype['TITLE'].'</a>';
			}
		}
		if ($ugall) {
			$user_gallery = 10000+$poster_id;
			if (!isset($ugalleries[$user_gallery])) {
				$ugall_result = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$ugall."pictures AS p, ".$ugall."albums AS a WHERE a.aid=p.aid AND a.category=$user_gallery LIMIT 0,1");
				$ugalleries[$user_gallery] = $ugall_result[0];
			}
			if ($ugalleries[$user_gallery]){
				$gal = array(
					'IMG' => $images['icon_cpg'],
					'TITLE' => _coppermineLANG,
					'URL' => getlink($ugalldir."&amp;cat=".$user_gallery),
					'TARGET' => false
				);
				if ($old_theme) {
					$gal_img = '<a href="'.$gal['URL'].'"><img src="'.$gal['IMG'].'" alt="'.$gal['TITLE'].'" title="'.$gal['TITLE'].'" style="border:0;" /></a>';
					$gal = '<a href="'.$gal['URL'].'">'.$gal['TITLE'].'</a>';
				}
			}
		}
	}

	$temp_url = getlink("&amp;file=posting&amp;mode=quote&amp;".POST_POST_URL."=".$postrow[$i]['post_id']);
	$quote_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_quote'].'" alt="'.$lang['Reply_with_quote'].'" title="'.$lang['Reply_with_quote'].'" style="border:0;" /></a>';
	$quote = '<a href="'.$temp_url.'">'.$lang['Reply_with_quote'].'</a>';

	$temp_url = getlink("&amp;file=search&amp;search_author=".urlencode($postrow[$i]['username'])."&amp;showresults=posts");
	$search_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_search'].'" alt="'.$lang['Search_user_posts'].'" title="'.$lang['Search_user_posts'].'" style="border:0;" /></a>';
	$search = '<a href="'.$temp_url.'">'.$lang['Search_user_posts'].'</a>';

	if ( ( $userdata['user_id'] == $poster_id && $is_auth['auth_edit'] ) || $is_auth['auth_mod'] ) {
		$temp_url = getlink("&amp;file=posting&amp;mode=editpost&amp;".POST_POST_URL."=".$postrow[$i]['post_id']);
		$edit_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_edit'].'" alt="'.$lang['Edit_delete_post'].'" title="'.$lang['Edit_delete_post'].'" style="border:0;" /></a>';
		$edit = '<a href="'.$temp_url.'">'.$lang['Edit_delete_post'].'</a>';
	} else {
		$edit_img = $edit = '';
	}

	if ( $is_auth['auth_mod'] ) {
		$temp_url = getlink("&amp;file=modcp&amp;mode=ip&amp;".POST_POST_URL."=".$postrow[$i]['post_id']."&amp;".POST_TOPIC_URL."=".$topic_id);
		$ip_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_ip'].'" alt="'.$lang['View_IP'].'" title="'.$lang['View_IP'].'" style="border:0;" /></a>';
		$ip = '<a href="'.$temp_url.'">'.$lang['View_IP'].'</a>';
		$temp_url = getlink("&amp;file=posting&amp;mode=delete&amp;".POST_POST_URL."=".$postrow[$i]['post_id']);
		$delpost_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_delpost'].'" alt="'.$lang['Delete_post'].'" title="'.$lang['Delete_post'].'" style="border:0;" /></a>';
		$delpost = '<a href="'.$temp_url.'">'.$lang['Delete_post'].'</a>';
	} else {
		$ip_img = '';
		$ip = '';
		if ($userdata['user_id'] == $poster_id && $is_auth['auth_delete'] && $forum_topic_data['topic_last_post_id'] == $postrow[$i]['post_id']) {
			$temp_url = getlink("&amp;file=posting&amp;mode=delete&amp;".POST_POST_URL."=".$postrow[$i]['post_id']);
			$delpost_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_delpost'].'" alt="'.$lang['Delete_post'].'" title="'.$lang['Delete_post'].'" style="border:0;" /></a>';
			$delpost = '<a href="'.$temp_url.'">'.$lang['Delete_post'].'</a>';
		} else {
			$delpost_img = $delpost = '';
		}
	}

	$post_subject = ( $postrow[$i]['post_subject'] != '' ) ? $postrow[$i]['post_subject'] : '';

	$message = $postrow[$i]['post_text'];
	$user_sig = ( $postrow[$i]['enable_sig'] && $postrow[$i]['user_sig'] != '' && $board_config['allow_sig'] ) ? $postrow[$i]['user_sig'] : '';
	//
	// Note! The order used for parsing the message _is_ important, moving things around could break any output
	//

	//
	// If the board has HTML off but the post has HTML
	// on then we process it, else leave it alone
	//
	if (!$board_config['allow_html'] || !$userdata['user_allowhtml']) {
		if ($user_sig != '') {
			$user_sig = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $user_sig);
		}
		if ($postrow[$i]['enable_html']) {
			$message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $message);
		}
	}

	//
	// Parse message and/or sig for BBCode if reqd
	//
	if ($user_sig != '') {
		$user_sig = ($board_config['allow_bbcode']) ? decode_bbcode($user_sig, 1, false) : nl2br($user_sig);
	}
	if ($postrow[$i]['enable_bbcode']) {
		$message = ($board_config['allow_bbcode']) ? decode_bbcode($message, 1, false) : nl2br($message);
	} else {
		$message = nl2br($message);
	}

	if ($user_sig != '') { $user_sig = make_clickable($user_sig); }
	$message = make_clickable($message);

	//
	// Parse smilies
	//
	if ($board_config['allow_smilies']) {
		if ( $postrow[$i]['user_allowsmile'] && $user_sig != '' ) {
			$user_sig = set_smilies($user_sig);
		}
		if ($postrow[$i]['enable_smilies']) {
			$message = set_smilies($message);
		}
	}

	//
	// Highlight active words (primarily for search)
	//
	if ($highlight_match) {
		// This was shamelessly 'borrowed' from volker at multiartstudio dot de
		// via php.net's annotated manual
		$message = str_replace('\"', '"', substr(preg_replace_callback('#(\>(((?>([^><]+|(?R)))*)\<))#s', fn($matches) => preg_replace('#($highlight_match)#i', $matches[1], $matches[0]), '>'.$message.'<'), 1, -1));
	}

	//
	// Replace naughty words
	//
	if (count($orig_word)) {
		$post_subject = preg_replace($orig_word, $replacement_word, $post_subject);
		if ($user_sig != '') {
			$user_sig = str_replace('\"', '"', substr(preg_replace_callback('#(\>(((?>([^><]+|(?R)))*)\<))#s', fn($matches) => preg_replace($orig_word, $replacement_word, $matches[0]), '>'.$user_sig.'<'), 1, -1));
		}
		$message = str_replace('\"', '"', substr(preg_replace_callback('#(\>(((?>([^><]+|(?R)))*)\<))#s', fn($matches) => preg_replace($orig_word, $replacement_word, $matches[0]), '>'.$message.'<'), 1, -1));
	}

	//
	// Replace newlines (we use this rather than nl2br because
	// till recently it wasn't XHTML compliant)
	//
	if ($user_sig != '') {
		$user_sig = '<br />_________________<br />'.$user_sig;
	}
	/* added for dragonflycms.org 9/3/ 2004 10:41PM akamu*/
	if ($poster_id != ANONYMOUS && isset($postrow[$i]['server_specs'])) {
		if ($postrow[$i]['server_specs'] != '' ) {
			$user_sig .= '<br /><br /><span class="postdetails" style="color: #333399">'.$postrow[$i]['username'].'\'s server specs (Server OS / Apache / MySQL / PHP / DragonflyCMS)<br />'.preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $postrow[$i]['server_specs']).'</span>';
		} else {
			$user_sig .= '<br /><br /><span class="postdetails" style="color: #333399">'.$postrow[$i]['username'].' please enter your server specs in your user profile!</span> '.set_smilies(' :cry: ');
		}
	}
	
//	$message = str_replace("\n", "\n<br />\n", $message);

	//
	// Editing information
	//
	if ($postrow[$i]['post_edit_count']) {
		$l_edit_time_total = ( $postrow[$i]['post_edit_count'] == 1 ) ? $lang['Edited_time_total'] : $lang['Edited_times_total'];
		$l_edited_by = '<br /><br />'.sprintf($l_edit_time_total, $poster, create_date($board_config['default_dateformat'], $postrow[$i]['post_edit_time']), $postrow[$i]['post_edit_count']);
	} else {
		$l_edited_by = '';
	}

	//
	// Again this will be handled by the templating
	// code at some point
	//
	$row_color = ( !($i % 2) ) ? $bgcolor1 : $bgcolor2;
	$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

	$template->assign_block_vars('postrow', array(
		'S_HAS_ATTACHMENTS' => isset($attachments['_'.$postrow[$i]['post_id']]),
		'ROW_COLOR' => $row_color,
		'ROW_CLASS' => $row_class,
		'POSTER_NAME' => $poster,
		'POSTER_RANK' => $poster_rank,
		'RANK_IMAGE' => $rank_image,
		'POSTER_JOINED' => $poster_joined,
		'POSTER_POSTS' => $poster_posts,
		'POSTER_FROM' => $poster_from,
		'POSTER_AVATAR' => $poster_avatar,
		'POSTER_BIO' => $poster_bio,
		'POSTER_TZ' => $poster_timezone,
		'POSTER_OCC' => $poster_occ,
		'POSTER_INTERESTS' => $poster_interests,
		'POST_NUMBER' => ($i + $start + 1),
		'POST_DATE' => $post_date,
		'POST_SUBJECT' => $post_subject,
		'MESSAGE' => $message,
		'SIGNATURE' => $user_sig,
		'EDITED_MESSAGE' => $l_edited_by,

		'MINI_POST_IMG' => $mini_post_img,
		'PROFILE_IMG' => $profile_img,
		'PROFILE' => $profile,
		'SEARCH_IMG' => $search_img,
		'SEARCH' => $search,
		'PM_IMG' => $pm_img,
		'PM' => $pm,
		'EMAIL_IMG' => $email_img,
		'EMAIL' => $email,
		'WWW_IMG' => $www_img,
		'WWW' => $www,
		'ICQ_STATUS_IMG' => $icq_status_img,
		'ICQ_IMG' => $icq_img,
		'ICQ' => $icq,
		'AIM_IMG' => $aim_img,
		'AIM' => $aim,
		'MSN_IMG' => $msn_img,
		'MSN' => $msn,
		'YIM_IMG' => $yim_img,
		'YIM' => $yim,
		//'SKYPE_IMG' => $skype_img,
		//'SKYPE' => $skype,
		'GAL_IMG' => $gal_img,
		'GAL' => $gal,
		'EDIT_IMG' => $edit_img,
		'EDIT' => $edit,
		'QUOTE_IMG' => $quote_img,
		'QUOTE' => $quote,
		'IP_IMG' => $ip_img,
		'IP' => $ip,
		'DELETE_IMG' => $delpost_img,
		'DELETE' => $delpost,

		'L_MINI_POST_ALT' => $mini_post_alt,

		'U_MINI_POST' => $mini_post_url,
		'U_POST_ID' => $postrow[$i]['post_id'])
	);
	if ($poster_id != ANONYMOUS && !$old_theme) {
		$template->assign_block_vars('postrow.user_details', $profile);
		if (!empty($pm)) { $template->assign_block_vars('postrow.user_details', $pm); }
		if (!empty($email)) { $template->assign_block_vars('postrow.user_details', $email); }
		if (!empty($www)) { $template->assign_block_vars('postrow.user_details', $www); }
		if (!empty($icq)) { $template->assign_block_vars('postrow.user_details', $icq); }
		if (!empty($aim)) { $template->assign_block_vars('postrow.user_details', $aim); }
		if (!empty($msn)) { $template->assign_block_vars('postrow.user_details', $msn); }
		if (!empty($yim)) { $template->assign_block_vars('postrow.user_details', $yim); }
		if (!empty($skype)) { $template->assign_block_vars('postrow.user_details', $skype); }
		if (!empty($gal)) { $template->assign_block_vars('postrow.user_details', $gal); }
	}
	//
	// Display Attachments in Posts
	//
//	if (defined('BBAttach_mod') && $postrow[$i]['post_attachment']) {
		if (!intval($attach_config['disable_mod']) && $is_auth['auth_download'] && $postrow[$i]['post_attachment']) {
			display_attachments($postrow[$i]['post_id']);
		}
}

//
// Quick Reply Mod
//
if ((!$is_auth['auth_reply'] || ($board_config['ropm_quick_reply']=='0') || $forum_topic_data['forum_status'] == FORUM_LOCKED || $forum_topic_data['topic_status'] == TOPIC_LOCKED) && $userdata['user_level'] != ADMIN ) {
	$template->assign_vars(array('QUICK_REPLY_FORM' => ''));
} else {
	if ( $can_watch_topic && $is_watching_topic ) {
		$notify = 1;
	} else {
		$notify = $userdata['user_notify'];
	}
	$last_poster = $postrow[$total_posts - 1]['username'];
	$last_msg = $postrow[$total_posts - 1]['post_text'];
	$last_msg = "[quote=\"$last_poster\"]".$last_msg.'[/quote]';
	$last_msg = str_replace("'", "&#39;", $last_msg);
	$last_msg = str_replace('"', '&quot;', $last_msg);

	$quick_reply_form = '<input type="hidden" name="mode" value="reply" />
	<input type="hidden" name="last_msg" value="'.$last_msg.'" />
	<input type="hidden" name="subject" value="Re: '.$topic_title.'" />
	<input type="hidden" name="t" value="'.$topic_id.'" />
	<input type="hidden" name="notify" value="'.$notify.'" />';

	$anon_reply = '';

	$template->set_filenames(array('quickreply' => 'forums/quickreply.html'));
	$template->assign_vars(array(
		'L_ATTACH_SIGNATURE' => $lang['Attach_signature'],
		'L_EMPTY_MESSAGE' => $lang['Empty_message'],
		'L_PREVIEW' => $lang['Preview'],
		'L_QUICK_REPLY' => $lang['Quick_Reply'],
		'L_QUICK_QUOTE' => $lang['Quick_quote'],
		'L_SUBMIT' => $lang['Submit'],
		'L_USERNAME' => $lang['Username'],

		'S_ANON_QREPLY' => $anon_reply,
		'S_HIDDEN_QREPLY_FIELDS' => $quick_reply_form,
		'S_IS_ANON' => !is_user(),
		'S_QREPLY_MSG' => $last_msg,
		'S_QREPLY_SIG' => ( $userdata['user_attachsig'] ) ? ' checked="checked"' : '',

		'U_POST_ACTION' => getlink('&amp;file=posting')
		)
	);
	$template->assign_var_from_handle('QUICK_REPLY_FORM', 'quickreply');
}
//
// END Quick Reply Mod
//

if(isset($_GET['printertopic'])) {
	$template->set_filenames(array('body' => 'forums/printertopic_body.html'));
} else {
	$template->set_filenames(array('body' => 'forums/viewtopic_body.html'));
}

if(isset($_GET['printertopic'])) {
	$gen_simple_header = 1;
}
require_once('includes/phpBB/page_tail.php');
