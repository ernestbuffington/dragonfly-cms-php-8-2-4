<?php
/***************************************************************************
 *								index.php
 *							-------------------
 *	 begin				: Saturday, Feb 13, 2001
 *	 copyright			: (C) 2001 The phpBB Group
 *	 email				: support@phpbb.com
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

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

# Start initial var setup
$post_id  = $_GET->uint('p');
$topic_id = $_GET->uint('t') ?: $_GET->uint('topic');

if (!$topic_id && !$post_id) {
	\Poodle\HTTP\Status::set(404);
	cpg_error(\Dragonfly::getKernel()->L10N->get('Topic_post_not_exist'));
}

# Find topic id if user requested a newer or older topic
if (isset($_GET['view']) && !$post_id) {
	if ('newest' == $_GET['view']) {
		if (is_user()) {
			$row = $db->uFetchRow("SELECT p.post_id FROM ".POSTS_TABLE." p
				WHERE p.topic_id = {$topic_id}
				  AND p.post_time >= {$userinfo['user_lastvisit']}
				ORDER BY p.post_time ASC");
			if (!$row) {
				message_die(GENERAL_MESSAGE, 'No_new_posts_last_visit');
			}
			URL::redirect(URL::index("&file=viewtopic&p={$row[0]}")."#{$row[0]}");
		}
		URL::redirect(URL::index("&file=viewtopic&t={$topic_id}"));
	} else if ('next' == $_GET['view'] || 'previous' == $_GET['view']) {
		$sql_condition = ('next' == $_GET['view']) ? '>' : '<';
		$sql_ordering  = ('next' == $_GET['view']) ? 'ASC' : 'DESC';
		$row = $db->uFetchRow("SELECT t.topic_id FROM ".TOPICS_TABLE." t, ".TOPICS_TABLE." t2
			WHERE t2.topic_id = {$topic_id}
				AND t.forum_id = t2.forum_id
				AND t.topic_last_post_id {$sql_condition} t2.topic_last_post_id
			ORDER BY t.topic_last_post_id {$sql_ordering}");
		if (!$row) {
			message_die(GENERAL_MESSAGE, ('next' == $_GET['view']) ? 'No_newer_topics' : 'No_older_topics');
		}
		URL::redirect(URL::index("&file=viewtopic&t={$row[0]}"));
	}
}

# Find topic by direct link to a post (and the calculation of which
# page the post is on and the correct display of viewtopic)
$prev_posts = 0;
if ($post_id) {
	# Find topic
	$post_info = $db->uFetchRow("SELECT
		p.topic_id,
		COUNT(p2.post_id) AS prev_posts
	FROM ".POSTS_TABLE." p
	LEFT JOIN ".POSTS_TABLE." p2 ON (p2.topic_id = p.topic_id AND p2.post_id < p.post_id)
	WHERE p.post_id = {$post_id}
	GROUP BY p.topic_id");
	if (!$post_info) {
		# Check for archived topic
		$post_info = $db->uFetchRow("SELECT
			p.topic_id,
			COUNT(p2.post_id) AS prev_posts
		FROM ".POSTS_ARCHIVE_TABLE." p
		LEFT JOIN ".POSTS_ARCHIVE_TABLE." p2 ON (p2.topic_id = p.topic_id AND p2.post_id < p.post_id)
		WHERE p.post_id = {$post_id}
		GROUP BY p.topic_id");
		if (!$post_info) {
			\Poodle\HTTP\Status::set(404);
			message_die(GENERAL_MESSAGE, 'Topic_post_not_exist');
		}
	}
	$topic_id = $post_info[0];
	$prev_posts = $post_info[1];
}

try {
	$topic = new \Dragonfly\Forums\Topic($topic_id);
	$forum = $topic->forum;
} catch (\Exception $e) {
	\Poodle\HTTP\Status::set(404);
	message_die(GENERAL_MESSAGE, 'Topic_post_not_exist');
}

# Check for archived topic and set posts table
$archived = $topic->archive_flag;
$posts_table      = $archived ? POSTS_ARCHIVE_TABLE : POSTS_TABLE;
$posts_text_table = $archived ? POSTS_TEXT_ARCHIVE_TABLE : POSTS_TEXT_TABLE;

# Start auth check
$is_auth = $forum->getUserPermissions();
if (!$is_auth['auth_view'] || !$is_auth['auth_read']) {
	if (!is_user()) { \URL::redirect(\Dragonfly\Identity::loginURL()); }
	$message = $is_auth['auth_view'] ? sprintf($lang['Sorry_auth_read'], $is_auth['auth_read_type']) : $lang['Topic_post_not_exist'];
	message_die(GENERAL_MESSAGE, $message);
}
# End auth check

$topic_id = $topic->id;

if ($prev_posts) {
	$start = floor($prev_posts / intval($board_config['posts_per_page'])) * intval($board_config['posts_per_page']);
} else {
	$start = (int)$_GET->uint('start');
}

$canonical_q = "&file=viewtopic&t={$topic_id}";

$post_days = $_POST->uint('postdays') ?: $_GET->uint('postdays');
if ($post_days) {
	if (!empty($_POST['postdays'])) {
		$start = 0;
	}
	$canonical_q = "&postdays={$post_days}";
}

# Decide how to order the post display
$post_time_order = $_POST->txt('postorder') ?: $_GET->txt('postorder');
if ($post_time_order) {
	$post_time_order = ('desc' == $post_time_order) ? 'DESC' : 'ASC';
	if ('DESC' === $post_time_order) {
		$canonical_q .= '&postorder=' . strtolower($post_time_order);
	}
} else {
	$post_time_order = 'ASC';
}

# Was a highlight request part of the URI?
$highlight_match = array();
if (isset($_GET['highlight'])) {
	// Split words and phrases
	$words = explode(' ', htmlprepare($_GET['highlight']));
	$c = sizeof($words);
	for ($i = 0; $i < $c; ++$i) {
		$words[$i] = trim($words[$i]);
		if (trim($words[$i])) {
			$highlight_match[] = $words[$i];
		}
	}
	unset($words);
	$canonical_q .= '&highlight=' . urlencode($_GET['highlight']);
}

if ($start) {
	$canonical_q .= "&start={$start}";
}

if (isset($_GET['printertopic'])) {
	\URL::redirect(\URL::index($canonical_q));
}

# Is user watching this thread?
$can_watch_topic = $topic->userCanWatch();
if ($can_watch_topic) {
	if (isset($_GET['unwatch'])) {
		$db->query("DELETE FROM ".TOPICS_WATCH_TABLE."
			WHERE topic_id = {$topic_id}
			  AND user_id = {$userinfo['user_id']}");
		\Poodle\Notify::warning($lang['No_longer_watching']);
		\URL::redirect(\URL::index($canonical_q));
	}
	if (isset($_GET['watch'])) {
		$db->query("INSERT INTO ".TOPICS_WATCH_TABLE."
			(user_id, topic_id, notify_status)
			VALUES
			({$userinfo['user_id']}, {$topic_id}, 0)");
		\Poodle\Notify::success($lang['You_are_watching']);
		\URL::redirect(\URL::index($canonical_q));
	}
} else if (isset($_GET['unwatch'])) {
	\URL::redirect(\Dragonfly\Identity::loginURL());
}

# Generate a 'Show posts in previous x days' select box. If the postdays var is POSTed
# then get it's value, find the number of topics with dates newer than it (to properly
# handle pagination) and alter the main query

if ($post_days) {
	$min_post_time = time() - ($post_days * 86400);
	$row = $db->uFetchRow("SELECT COUNT(p.post_id) AS num_posts
		FROM ".TOPICS_TABLE." t, {$posts_table} p
		WHERE t.topic_id = {$topic_id}
			AND p.topic_id = t.topic_id
			AND p.post_time >= {$min_post_time}");
	$total_replies = ($row ? (int)$row[0] : 0);
	$limit_posts_time = "AND p.post_time >= {$min_post_time} ";
} else {
	$total_replies = $topic->replies + 1;
	$limit_posts_time = '';
}

if (!is_user() && $board_config['user_reg_date_age']) {
	$age = time() - 86400 * $board_config['user_reg_date_age'];
	$limit_posts_time .= " AND u.user_regdate < {$age}";
}

# Go ahead and pull all data for this topic
/* lanzer speedup for large forums
$total_pages = ceil($total_replies/$board_config['posts_per_page']);
$on_page = floor($start / $board_config['posts_per_page']) + 1;
if ($start > 100 && ($total_replies / 2) < $start) {
	$reverse = true;
	$last_page_posts = $total_replies - ($board_config['posts_per_page'] * ($total_pages - 1));
}
if (isset($reverse)) {
	$limit_string = ($total_pages == $on_page) ? $last_page_posts : ($last_page_posts + ($total_pages - $on_page - 1) * $board_config['posts_per_page'] ).','. $board_config['posts_per_page'];
	$sql = "SELECT p.post_id FROM {$posts_table} p USE INDEX(topic_n_id) WHERE p.topic_id = $topic_id $limit_posts_time ORDER BY p.post_id DESC LIMIT $limit_string" ;
} else {
	$sql = "SELECT p.post_id FROM {$posts_table} p WHERE p.topic_id = $topic_id $limit_posts_time LIMIT $start, ".$board_config['posts_per_page'];
}
$result = $db->query($sql);
while (list($p_id) = $result->fetch_row()) {
	$p_array[] = $p_id;
}
$post_index = implode(",",$p_array);
$sql = "SELECT u.username, u.user_id, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_regdate, u.user_viewemail, u.user_rank, u.user_sig, u.user_avatar, u.user_allow_viewonline, u.user_allowsmile, p.*,  pt.post_text, pt.post_subject
   FROM {$posts_table} p, {$db->TBL->users} u, {$posts_text_table} pt
   WHERE p.post_id in ($post_index)
	  AND pt.post_id = p.post_id
	  AND u.user_id = p.poster_id
   ORDER BY p.post_time $post_time_order";
*/
$sql = "SELECT p.*, pt.post_text, pt.post_subject, u.username, u.user_id, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_regdate, u.user_viewemail, u.user_rank, u.user_sig, u.user_avatar, u.user_avatar_type, u.user_allowavatar, u.user_allowsmile, u.bio, u.user_occ, u.user_interests, u.user_session_time, u.user_allow_viewonline, u.user_level";
if (isset($userinfo['server_specs'])) { $sql .= ', u.server_specs'; }
$sql .= " FROM {$posts_table} p";
$sql .= " INNER JOIN {$posts_text_table} pt ON (pt.post_id = p.post_id)";
$sql .= " LEFT JOIN {$db->TBL->users} u ON (u.user_id = p.poster_id)";
$sql .= " WHERE p.topic_id = {$topic_id} {$limit_posts_time}
	ORDER BY p.post_time {$post_time_order}
	LIMIT {$board_config['posts_per_page']} OFFSET {$start}";
$postrows = $db->uFetchAll($sql);
if (!$postrows) {
	\Poodle\HTTP\Status::set(404);
	message_die(GENERAL_MESSAGE, $lang['No_posts_topic']);
}
$total_posts = count($postrows);

$resync = false;
if ($topic->replies + 1 < $start + $total_posts) {
	$resync = true;
} else if ($start + $total_posts > $topic->replies + 1) {
	$row_id = $topic->replies % intval($board_config['posts_per_page']);
	$resync = (('ASC' == $post_time_order && $postrows[$row_id]['post_id'] != $topic->last_post_id) || $start + $total_posts < $topic->replies);
}
if ($resync) {
	$topic->sync();
	$total_replies = $topic->replies + 1;
}

# Define censored word matches
$orig_word = $replacement_word = array();
obtain_word_list($orig_word, $replacement_word);
# Censor topic title
$topic_title = $orig_word ? preg_replace($orig_word, $replacement_word, $topic->title) : $topic->title;

$topic_last_read = \Dragonfly\Forums\Display::getForumTopicLastVisit($forum->id, $topic_id);

\Dragonfly\Page::tag('link rel="canonical" href="'.URL::index(preg_replace('/&highlight=[^&]*/','',$canonical_q)).'"');

# Output page header
\Dragonfly\Page::title($forum->cat_title.' '._BC_DELIM.' '.$forum->name.($archived ? ' :: '.$lang['Archives'] : '').' '._BC_DELIM.' '. $topic_title.($archived ? ' :: '.$lang['Archived'] : ''));

$images = get_forums_images();

make_jumpbox('viewforum', $forum->id);

$pagination_ppp = $board_config['posts_per_page'];

$parents = array();
if ($forum->parent_id) {
	$parent_id = $forum->parent_id;
	while ($parent_id) {
		list ($parent_name, $parent_id, $parent_forum_id) = $db->uFetchRow("SELECT forum_name AS parent_name, parent_id, forum_id FROM " . FORUMS_TABLE . " WHERE forum_id = $parent_id");
		$parents[] = array(
			'name' => $parent_name,
			'uri' => URL::index("&file=viewforum&f={$parent_forum_id}")
		);
	}
	$parents = array_reverse($parents);
}

$pagination = new \Poodle\Pagination(URL::index(preg_replace('/&start=[0-9]+/', '', "{$canonical_q}&start=\${offset}")), $total_replies, $start, $pagination_ppp);

# Send vars to template
$template->assign_vars(array(
	'PAGINATION' => generate_pagination_from_class($pagination),
	'topic_pagination' => $pagination,
	'PAGE_NUMBER' => sprintf($lang['Page_of'], (floor($start / $pagination_ppp) + 1), ceil($total_replies / $pagination_ppp)),

	'SF_PARENTS' => $parents,

	'U_VIEW_OLDER_TOPIC' => URL::index("&file=viewtopic&t={$topic_id}&view=previous"),
	'U_VIEW_NEWER_TOPIC' => URL::index("&file=viewtopic&t={$topic_id}&view=next"),
));

# Does this topic contain a poll?
$poll = $topic->poll;
if ($poll && $poll->options) {
	if ($orig_word) {
		$poll->title = preg_replace($orig_word, $replacement_word, $poll->title);
	}
	$votes = max(1,$poll->votes);
	foreach ($poll->options as &$option) {
		$option['percentage'] = sprintf('%.1d%%', $option['votes'] * 100 / $votes);
		if ($orig_word) {
			$option['text'] = preg_replace($orig_word, $replacement_word, $option['text']);
		}
	}
	$template->S_POLL_RESULTS = ($topic->isLocked()
		|| !$is_auth['auth_vote']
		|| $poll->closed
		|| (($_GET->txt('vote') ?: $_POST->txt('vote')) == 'viewresult')
		|| $poll->hasVoted($userinfo->id));
	$template->assign_vars(array(
		'topic_poll' => $poll,
		'U_VIEW_RESULTS' => URL::index("{$canonical_q}&vote=viewresult"),
		'S_POLL_ACTION' => URL::index("&file=posting&mode=vote&t={$topic_id}")
	));
}

$v9_theme = !$template->isTALThemeFile('forums/viewtopic_body');

# Initializes some templating variables for displaying Attachments in Posts
$posts_attachments = array();
if ($topic->attachment && !$attach_config['disable_mod'] && (!$v9_theme || $is_auth['auth_download'])) {
	$post_id_array = array();
	foreach ($postrows as $row) {
		if ($row['post_attachment']) {
			$post_id_array[] = $row['post_id'];
		}
	}
	if ($post_id_array && $rows = \Dragonfly\Forums\Attachments::getFromPosts($post_id_array)) {
		foreach ($rows as $row) {
			$posts_attachments[$row['post_id']][] = $row;
		}
		unset($rows);
	}
	unset($row, $post_id_array);

	if ($posts_attachments) {
		$allowed_extensions = $display_categories = array();
		// Don't count on forbidden extensions table, because it is not allowed to allow forbidden extensions at all
		$result = $db->query("SELECT e.extension, g.cat_id, g.download_mode
		FROM {$db->TBL->bbextensions} e, {$db->TBL->bbextension_groups} g
		WHERE (e.group_id = g.group_id) AND (g.allow_group = 1)");
		while ($extension_information = $result->fetch_assoc()) {
			$extension = strtolower(trim($extension_information['extension']));
			$allowed_extensions[] = $extension;
			$display_categories[$extension] = intval($extension_information['cat_id']);
		}
	}
}

$topic->incViews();

if (\Dragonfly\Modules::isActive('coppermine')) {
	list($ugall, $ugalldir) = $db->uFetchRow("SELECT prefix, dirname FROM ".$db->TBL->cpg_installs);
	$ugall = $db->TBL->prefix . $ugall;
} else {
	$ugall = false;
}
$ugalleries = array();

$private_messages = is_user() && \Dragonfly\Modules::isActive("Private_Messages");

# Okay, let's do the loop, yeah come on baby let's do the loop and it goes like this ...
$template->postrow = array();
$posters = array();
foreach ($postrows as $i => $post) {
	$poster_id = $post['user_id'] ?: \Dragonfly\Identity::ANONYMOUS_ID;
	if (!isset($posters[$poster_id])) {
		if ($poster_id == \Dragonfly\Identity::ANONYMOUS_ID) {
			$poster = array(
				'name'      => $lang['Guest'],
				'posts'     => '',
				'from'      => '',
				'rank'      => $lang['Guest'],
				'rank_img'  => '',
				'avatar'    => \Dragonfly\Identity\Avatar::getURL($post),
				'details'   => array(),
				'is_online' => false,
				'signature' => '',
				'joined'    => '',
			);
		} else {
			$user_sig = $board_config['allow_sig'] ? $post['user_sig'] : '';
			# Note! The order used for parsing the message _is_ important, moving things around could break any output
			if ($user_sig) {
				# If the board has HTML off but the post has HTML on then we process it, else leave it alone
				if (!$board_config['allow_html'] || !$userinfo['user_allowhtml']) {
					$user_sig = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $user_sig);
				}
				# Parse sig for BBCode if reqd
				$user_sig = ($board_config['allow_bbcode']) ? \Dragonfly\BBCode::decode($user_sig, 1, false) : nl2br($user_sig);
				$user_sig = \URL::makeClickable($user_sig);
				# Parse smilies
				if ($board_config['allow_smilies'] && $post['user_allowsmile']) {
					$user_sig = \Dragonfly\Smilies::parse($user_sig);
				}
				# Replace naughty words
				if ($orig_word) {
					$user_sig = str_replace('\\"', '"', substr(preg_replace_callback(
						'#(\>(((?>([^><]+|(?R)))*)\<))#s',
						function($m) use ($orig_word, $replacement_word) {
							return preg_replace($orig_word, $replacement_word, $m[0]);
						},
						'>'.$user_sig.'<'), 1, -1));
				}
			}
			# Generate ranks, set them to empty string initially.
			$poster_rank = $rank_image = '';
			if ($rank = \Dragonfly\Identity\Rank::get($post['user_rank'], $post['user_posts'])) {
				$poster_rank = $rank['title'];
				$rank_image = $rank['image'] ? DF_STATIC_DOMAIN . $rank['image'] : '';
			}
			$poster = array(
				'name'      => $post['username'],
				'posts'     => $lang['Posts'].': '.$post['user_posts'],
				'from'      => $post['user_from'] ? $lang['Location'].': '.str_replace('.gif', '', $post['user_from']) : '',
				'rank'      => $poster_rank,
				'rank_img'  => $rank_image,
				'avatar'    => \Dragonfly\Identity\Avatar::getURL($post),
				'details'   => array(),
				'is_online' => $board_config['allow_online_posts'] && ($post['user_session_time'] > time()-300) && ($post['user_allow_viewonline'] || is_admin()),
				'signature' => $user_sig,
				'joined'    => $lang['Joined'].': '.$template->L10N->date('M d, Y', $post['user_regdate']),
			);

			# added for dragonflycms.org 9/3/ 2004 10:41PM akamu
			if (array_key_exists('server_specs', $post)) {
				$poster['server_specs'] = \Dragonfly\BBCode::decode($post['server_specs'], 1);
			}

			$poster['details']['profile'] = array(
				'IMG' => $images['icon_profile'],
				'TITLE' => $lang['Read_profile'],
				'URL' => \Dragonfly\Identity::getProfileURL($poster_id),
				'TARGET' => false
			);

			if ($private_messages) {
				$poster['details']['pm'] = array(
					'IMG' => $images['icon_pm'],
					'TITLE' => $lang['Send_private_message'],
					'URL' => URL::index("Private_Messages&compose&u={$poster_id}"),
					'TARGET' => false
				);
			}

			if (!empty($post['user_viewemail']) || $is_auth['auth_mod']) {
				$poster['details']['email'] = array(
					'IMG' => $images['icon_email'],
					'TITLE' => $lang['Send_email'],
					'URL' => 'mailto:'.$post['user_email'],
					'TARGET' => false
				);
			}

			if ($post['user_website'] == 'http:///' || $post['user_website'] == 'http://'){
				$post['user_website'] = '';
			}
			if (!empty($post['user_website'])) {
				if (substr($post['user_website'],0, 7) != 'http://') {
					$post['user_website'] = 'http://'.$post['user_website'];
				}
				$poster['details']['www'] = array(
					'IMG' => $images['icon_www'],
					'TITLE' => $lang['Visit_website'],
					'URL' => $post['user_website'],
					'TARGET' => '_blank'
				);
			}

			if (!empty($post['user_icq'])) {
				$poster['details']['icq'] = array(
					'IMG' => $images['icon_icq'],
					'TITLE' => $lang['ICQ'],
					'URL' => 'http://www.icq.com/people/'.$post['user_icq'],
					'TARGET' => '_blank'
				);
			}

			if (!empty($post['user_aim'])) {
				$poster['details']['aim'] = array(
					'IMG' => $images['icon_aim'],
					'TITLE' => $lang['AIM'],
					'URL' => 'aim:goim?screenname='.$post['user_aim'].'&message=Hey+are+you+there?',
					'TARGET' => false
				);
			}

			if (!empty($post['user_yim'])) {
				$poster['details']['yim'] = array(
					'IMG' => $images['icon_yim'],
					'TITLE' => $lang['YIM'],
					'URL' => 'http://edit.yahoo.com/config/send_webmesg?.target='.$post['user_yim'].'&.src=pg',
					'TARGET' => '_blank'
				);
			}

			if (!empty($post['user_skype'])) {
				$poster['details']['skype'] = array(
					'IMG' => $images['icon_skype'],
					'TITLE' => 'Skype',
					'URL' => 'callto://'.$post['user_skype'],
					'TARGET' => false
				);
			}

			if ($ugall) {
				if (!isset($ugalleries[$poster_id])) {
					$ugall_result = $db->uFetchRow("SELECT COUNT(*) FROM {$ugall}pictures AS p, {$ugall}albums AS a WHERE a.aid=p.aid AND a.user_id={$poster_id}");
					$ugalleries[$poster_id] = $ugall_result[0];
				}
				if ($ugalleries[$poster_id]){
					$poster['details']['gal'] = array(
						'IMG' => $images['icon_cpg'],
						'TITLE' => _coppermineLANG,
						'URL' => URL::index($ugalldir."&file=users&id={$poster_id}"),
						'TARGET' => false
					);
				}
			}
		}
		$posters[$poster_id] = $poster;
	} else {
		$poster = $posters[$poster_id];
	}
	if (!$post['enable_sig']) {
		$poster['signature'] = '';
	}

	# Define the little post icon
	$is_new_post = (is_user() && $post['post_time'] > $topic_last_read);
	if ($is_new_post) {
		$_SESSION['CPG_SESS'][$module_name]['track_topics'][$topic_id] = (int)$post['post_time'];
	}

	# Handle anon users posting with usernames
	if ($poster_id == \Dragonfly\Identity::ANONYMOUS_ID && $post['post_username']) {
		$poster['name'] = $post['post_username'];
	}

	$edit_uri = '';
	if (!$archived && ($is_auth['auth_mod'] || ($userinfo['user_id'] == $poster_id && $is_auth['auth_edit'] && ($i == $total_posts - 1 || !$board_config['edit_last_post_only'])))) {
		$edit_uri = URL::index("&file=posting&mode=editpost&p={$post['post_id']}");
	}

	$delete_uri = '';
	if ($is_auth['auth_mod'] || ($userinfo['user_id'] == $poster_id && $is_auth['auth_delete'])) {
		$delete_uri = URL::index("&file=posting&mode=delete&p={$post['post_id']}");
	}

	$post_subject = $post['post_subject'] ?: '';

	$apost = new \Dragonfly\Forums\Post();
	$apost->message        = $post['post_text'];
	$apost->enable_bbcode  = $post['enable_bbcode'];
	$apost->enable_html    = $post['enable_html'];
	$apost->enable_smilies = $post['enable_smilies'];
	$message = $apost->message2html($highlight_match);

	# Replace naughty words
	if ($orig_word) {
		$post_subject = preg_replace($orig_word, $replacement_word, $post_subject);
		$message = str_replace('\\"', '"', substr(preg_replace_callback(
			'#(\>(((?>([^><]+|(?R)))*)\<))#s',
			function($m) use ($orig_word, $replacement_word) {
				return preg_replace($orig_word, $replacement_word, $m[0]);
			},
			'>'.$message.'<'), 1, -1));
	}

	# Editing information
	if ($post['post_edit_count']) {
		$l_edit_time_total = ( $post['post_edit_count'] == 1 ) ? $lang['Edited_time_total'] : $lang['Edited_times_total'];
		$l_edited_by = sprintf($l_edit_time_total, $poster['name'], $lang->date($board_config['default_dateformat'], $post['post_edit_time']), $post['post_edit_count']);
	} else {
		$l_edited_by = '';
	}

	$postrow = array(
		'S_HAS_ATTACHMENTS' => isset($posts_attachments[$post['post_id']]),
		'POSTER_NAME' => $poster['name'],
		'POSTER_RANK' => $poster['rank'],
		'RANK_IMAGE' => $poster['rank_img'],
		'POSTER_JOINED' => $poster['joined'],
		'POSTER_POSTS' => $poster['posts'],
		'POSTER_FROM' => $poster['from'],
		'POSTER_AVATAR_URI' => $poster['avatar'],
		'POSTER_BIO' => ($poster_id != \Dragonfly\Identity::ANONYMOUS_ID && $post['bio']) ? sprintf($lang['About_user'],$post['username']).': '.$post['bio'] : '',
		'POSTER_OCC' => ($poster_id != \Dragonfly\Identity::ANONYMOUS_ID && $post['user_occ']) ? $lang['Occupation'].': '.$post['user_occ'] : '',
		'POSTER_INTERESTS' => ($poster_id != \Dragonfly\Identity::ANONYMOUS_ID && (!empty($post['user_interests'])) ) ? $lang['Interests'].': '.$post['user_interests'] : '',
		'poster_is_online' => $poster['is_online'],
		'POST_NUMBER' => ($i + $start + 1),
		'POST_DATE' => $lang->date($board_config['default_dateformat'], $post['post_time']),
		'POST_DATETIME' => gmdate('Y-m-d\\TH:i:s\\Z',$post['post_time']),
		'POST_SUBJECT' => $post_subject,
		'reputation_up' => $post['post_reputation_up'],
		'reputation_up_url' => $userinfo->isMember() ? URL::index('&file=reputation&p='.$post['post_id'].'&up='.\Poodle\Base64::urlEncode($_SERVER['REQUEST_URI'])) : null,
		'reputation_down' => $post['post_reputation_down'],
		'reputation_down_url' => $userinfo->isMember() ? URL::index('&file=reputation&p='.$post['post_id'].'&down='.\Poodle\Base64::urlEncode($_SERVER['REQUEST_URI'])) : null,
		'reputation_score' => $post['post_reputation_up'] - $post['post_reputation_down'],
		'MESSAGE' => $message,
		'SIGNATURE' => $poster['signature'],
		'EDITED_MESSAGE' => $l_edited_by,

		'poster_id' => $poster_id,
		'is_new' => $is_new_post,
		'search_uri' => URL::index('&file=search&search_author='.urlencode($post['username']).'&showresults=posts'),
		'edit_uri' => $edit_uri,
		'quote_uri' => ($is_auth['auth_reply'] || $is_auth['auth_mod']) ? URL::index("&file=posting&mode=quote&p={$post['post_id']}") : null,
		'view_ip_uri' => $is_auth['auth_mod'] ? URL::index("&file=modcp&mode=ip&p={$post['post_id']}&t={$topic_id}") : '',
		'delete_uri' => $delete_uri,

		'U_MINI_POST' => URL::index('&file=viewtopic&p='.$post['post_id']).'#'.$post['post_id'],
		'U_POST_ID' => $post['post_id'],

		'user_details' => $poster['details'],
		'attachment' => array(),
	);
	if (array_key_exists('server_specs', $poster)) {
		$postrow['poster_server_specs'] = $poster['server_specs'];
	}

	# Display Attachments in Posts
	$postrow['attachment'] = array();
	if (!empty($posts_attachments[$post['post_id']])) {
		//
		// Assign Variables and Definitions based on the fetched Attachments - internal
		// used by all displaying functions, the Data was collected before, it's only dependend on the template used. :)
		//
		foreach ($posts_attachments[$post['post_id']] as $attachment) {
			//
			// Some basic things...
			//
			$attachment['extension'] = strtolower(trim($attachment['extension']));

			//
			// Admin is allowed to view forbidden Attachments, but the error-message is displayed too to inform the Admin
			//
			$denied = !in_array($attachment['extension'], $allowed_extensions);
			$filename = $attachment['file'];

			//
			// define category
			//
			$display = 'DEF_CAT';
			$cat = (int)$display_categories[$attachment['extension']];
			if (STREAM_CAT == $cat) {
				$display = 'STREAM_CAT';
			} else if (SWF_CAT == $cat) {
				$display = 'SWF_CAT';
			} else if (IMAGE_CAT == $cat && $attachment['thumbnail']) {
				$display = 'THUMB_CAT';
			} else if (IMAGE_CAT == $cat && $attach_config['img_display_inlined']) {
				if ($attach_config['img_link_width'] || $attach_config['img_link_height']) {
					list($width, $height) = getimagesize($filename);
					if (!$width || !$height || ($width <= $attach_config['img_link_width'] && $height <= $attach_config['img_link_height'])) {
						$display = 'IMAGE_CAT';
					}
				} else {
					$display = 'IMAGE_CAT';
				}
			}
			$thumb_source = '';
			$width = $height = 0;
			if ($is_auth['auth_download'] && !$denied) {
				switch ($display)
				{
					// Images
					case 'IMAGE_CAT':
						//
						// Directly Viewed Image ... update the download count
						//
						attachment_inc_download_count($attachment['attach_id']);
						break;

					// Images, but display Thumbnail
					case 'THUMB_CAT':
						// NOTE: If you want to use the download.php everytime an
						// thumnmail is displayed inlined, activate the following:
						//$thumb_source = URL::index('&file=download&id=' . $attachment['attach_id'] . '&thumb=1');
						$thumb_source = preg_replace('#(^|/)([^/]+)$#D', '$1'.THUMB_DIR.'/t_$2', $attachment['file']);
						$filename = URL::index('&file=download&id=' . $attachment['attach_id']);
						break;

					// Streams
					case 'STREAM_CAT':
						//
						// Viewed/Heared File ... update the download count (download.php is not called here)
						//
						attachment_inc_download_count($attachment['attach_id']);
						break;

					// Macromedia Flash Files
					case 'SWF_CAT':
						list($width, $height) = getimagesize($filename);
						//
						// Viewed/Heared File ... update the download count (download.php is not called here)
						//
						attachment_inc_download_count($attachment['attach_id']);
						break;

					// display attachment
					default:
						$filename = URL::index('&file=download&id=' . $attachment['attach_id']);
						break;
				}
			}

			$message = false;
			if (!$is_auth['auth_download']) {
				$message = 'You are not allowed to view/download this attachment';
			} else if ($denied) {
				$message = sprintf($lang['Extension_disabled_after_posting'], $attachment['extension']);
			}

			$postrow['attachment'][] = array(
				'L_ALLOWED' => is_admin() || ($is_auth['auth_download'] && !$denied),
				'L_DENIED'  => $message,
				'S_DEF_CAT'	   => false,
				'S_IMAGE_CAT'  => false,
				'S_THUMB_CAT'  => false,
				'S_STREAM_CAT' => false,
				'S_SWF_CAT'	   => false,
				('S_'.$display) => true,
				'DOWNLOAD_NAME' => $attachment['name'],
				'S_UPLOAD_IMAGE' => '',

				'FILESIZE' => $lang->filesizeToHuman($attachment['filesize']),
				'COMMENT' => htmlprepare($attachment['comment'], true),

				'L_DOWNLOADED_VIEWED' => ($display == 'DEF_CAT') ? $lang['Downloaded'] : $lang['Viewed'],
				'L_DOWNLOAD_COUNT' => sprintf($lang['Download_number'], $attachment['download_count']),

				//images
				'IMG_SRC' => $filename,
				'IMG_THUMB_SRC' => $thumb_source,

				//!images
				'U_DOWNLOAD_LINK' => $filename,

				'WIDTH' => $width,
				'HEIGHT' => $height,

				//default
				'TARGET_BLANK' => (IMAGE_CAT == $cat || $display == 'DEF_CAT') ? 'target="_blank"' : '',
			);
		}
	}
	$template->postrow[] = $postrow;
}

unset($posters, $postrows, $orig_word, $replacement_word);

$notify = intval($can_watch_topic && ($topic->userIsWatching() || $userinfo['user_notify']));

# Quick Reply Mod
$template->QUICK_REPLY_FORM = !($archived || ((!$is_auth['auth_reply'] || (!$board_config['ropm_quick_reply']) || $forum->isLocked() || $topic->isLocked()) && !$userinfo->isAdmin()));
if ($template->QUICK_REPLY_FORM) {
	\Dragonfly\BBCode::pushHeaders(true);
	$template->assign_vars(array(
		'QUICK_REPLY_FORM' => true,
		'S_QREPLY_MSG' => "[quote=\"{$post['username']}\"]{$post['post_text']}[/quote]",
		'U_POST_ACTION' => URL::index('&file=posting'),
		'hidden_qreply_fields' => array(
			array('name'=>'mode','value'=>'reply'),
			array('name'=>'subject','value'=>'Re: '.$topic_title),
			array('name'=>'t','value'=>$topic_id),
			array('name'=>'notify','value'=>$notify)
		)
	));
}

# Determine if archived topic, can be revived or has been revived.
$template->ARCHIVE_REPLY_FORM = ($is_auth['auth_reply'] && (!$forum->isLocked() || !$topic->isLocked()) && $archived);
if ($template->ARCHIVE_REPLY_FORM) {
	if ($topic->archive_id) {
		$template->U_REVIVED_TOPIC = URL::index('&file=viewtopic&t='.$topic->archive_id);
	} else {
		$template->assign_vars(array(
			'S_HIDDEN_AREPLY_FIELDS' => array(
				array('name'=>'mode','value'=>'newtopic'),
				array('name'=>'confirm','value'=>1),
				array('name'=>'message','value'=>''),
				array('name'=>'quick_quote','value'=>"Archived topic has been revived »» ".URL::index("&file=viewtopic&t={$topic_id}")."\n\n[quote=\"{$post['username']}\"]{$post['post_text']}[/quote]"),
				array('name'=>'subject','value'=>$topic_title),
				array('name'=>'f','value'=>$forum->id),
				array('name'=>'notify','value'=>$notify),
				array('name'=>'archive_id','value'=>$topic_id),
			),
			'U_POST_ACTION' => URL::index('&file=posting')
		));
	}
}

# Send vars to template
if ($v9_theme) {
	require __DIR__ . '/v9/viewtopic.php';
} else {
	$template->forum = $forum;
	$template->forum_topic = $topic;
	$template->forum_topic_uri = $canonical_q;
	$template->posts_days = $post_days;
	$template->posts_order = $post_time_order;
	$template->board_config = $board_config;
	$template->board_images = $images;
	$template->attach_config = $attach_config;
	$template->user_auth = $is_auth;
	$template->TOPIC_TITLE = $topic_title;
}

require_once('includes/phpBB/page_header.php');
$template->display('forums/viewtopic_body');
