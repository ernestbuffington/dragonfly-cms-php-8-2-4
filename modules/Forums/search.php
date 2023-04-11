<?php
/***************************************************************************
 *				  search.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
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

global $module_name;

\Dragonfly\Page::metatag('robots', 'noindex, nofollow');

$mode = $_POST['mode'] ?: $_GET['mode'];

# removing topics from the watchlist
if ('stopwatch' == $mode && is_user()) {
	$topics = isset($_POST['topic_id_list']) ? $_POST['topic_id_list'] : array($topic_id);
	$topics = implode(',', array_map('intval', $topics));
	$db->query("DELETE FROM ".TOPICS_WATCH_TABLE."
	WHERE topic_id IN ({$topics})
	AND user_id={$userinfo->id}");
	URL::redirect(URL::index('&file=search&search_id=watch&start='.$start));
}

# removing forums from the watchlist
if ('stopwatchf' == $mode && is_user() && $board_config['allow_forum_watch']) {
	$forums = isset($_POST['forum_id_list']) ? $_POST['forum_id_list'] : array($forum_id);
	$forums = implode(',', array_map('intval', $forums));
	$db->query("DELETE FROM ".FORUMS_WATCH_TABLE."
	WHERE forum_id IN ({$forums})
	AND user_id={$userinfo->id}");
	URL::redirect(URL::index('&file=search&search_id=fwatch&start='.$start));
}

# Handles the simple windowed user search functions called from various other scripts
if ('searchuser' == $mode) {
	require 'modules/Your_Account/search.php';
	exit;
}

# Begin core code
$search = new \Dragonfly\Forums\Search();
$search_id       = $_GET['search_id'];
$search_keywords = $_POST['search_keywords'] ?: $_GET['search_keywords'];
$search_author   = trim($_POST['search_author'] ?: $_GET['search_author']);
if ($search_keywords || $search_author || $search_id) {
	$search->id       = $search_id;
	$search->keywords = $search_keywords;
	$search->author   = $search_author;
	$search->show     = $_POST['show_results'] ?: $_GET['show_results'] ?: 'topics';
	$search->terms    = ($_POST['search_terms']  == 'all');
	$search->fields   = ($_POST['search_fields'] == 'all');
	$search->chars    = (null===$_POST->int('return_chars')) ? 200 : $_POST->int('return_chars');
	$search->cat      = $_POST->uint('search_cat');
	$search->forum    = $_POST->uint('search_forum');
	$search->sort_by  = (int)$_POST->uint('sort_by');
	$search->sort_dir = ('ASC' == $_POST['sort_dir']) ? 'ASC' : 'DESC';
	$search->days     = (int)($_POST->uint('topic_days') ?: $_GET->uint('topic_days'));
	$search->search();
	if (!$search->results) {
		\Poodle\HTTP\Status::set(404);
		message_die(GENERAL_MESSAGE, $lang['No_search_match']);
	}

	$start = (int)$_GET->uint('start');

	# Look up data ...
	if ('posts' == $search->show) {
		$sql = "SELECT pt.post_text, pt.post_subject, p.*, f.forum_id, f.forum_name, t.*, u.username, u.user_id, u.user_sig
			FROM " . FORUMS_TABLE . " f, " . TOPICS_TABLE . " t, {$db->TBL->users} u, " . POSTS_TABLE . " p, " . POSTS_TEXT_TABLE . " pt
			WHERE p.post_id IN ({$search->results})
			  AND pt.post_id = p.post_id
			  AND f.forum_id = p.forum_id
			  AND p.topic_id = t.topic_id
			  AND p.poster_id = u.user_id";
	} else if ('forums' == $search->show) {
		$sql = "SELECT t.*, f.forum_id, f.forum_name, u.username, u.user_id, u2.username as user2, u2.user_id as id2, p.post_username, p2.post_username AS post_username2, p2.post_time
			FROM ".TOPICS_TABLE." t, ".FORUMS_TABLE." f, {$db->TBL->users} u, ".POSTS_TABLE." p, ".POSTS_TABLE." p2, {$db->TBL->users} u2
			WHERE f.forum_id IN ({$search->results})
			  AND f.forum_id = t.forum_id
			  AND t.topic_last_post_id = f.forum_last_post_id
			  AND t.topic_poster = u.user_id
			  AND p.post_id = f.forum_last_post_id
			  AND p2.post_id = f.forum_last_post_id
			  AND u2.user_id = p2.poster_id";
	} else {
		$sql = "SELECT t.*, f.forum_id, f.forum_name, u.username, u.user_id, u2.username as user2, u2.user_id as id2, p.post_username, p2.post_username AS post_username2, p2.post_time
			FROM " . TOPICS_TABLE . " t, " . FORUMS_TABLE . " f, {$db->TBL->users} u, " . POSTS_TABLE . " p, " . POSTS_TABLE . " p2, {$db->TBL->users} u2
			WHERE t.topic_id IN ({$search->results})
			  AND t.topic_poster = u.user_id
			  AND f.forum_id = t.forum_id
			  AND p.post_id = t.topic_first_post_id
			  AND p2.post_id = t.topic_last_post_id
			  AND u2.user_id = p2.poster_id";
	}

	$per_page = ('posts' == $search->show) ? $board_config['posts_per_page'] : $board_config['topics_per_page'];

	$sql .= ' ORDER BY ';
	switch ($search->sort_by)
	{
		case 1:
			$sql .= ('posts' == $search->show) ? 'pt.post_subject' : 't.topic_title';
			break;
		case 2:
			$sql .= 't.topic_title';
			break;
		case 3:
			$sql .= 'u.username';
			break;
		case 4:
			$sql .= "t.topic_last_post_id {$search->sort_dir}, f.forum_id";
			break;
		default:
			$sql .= ('posts' == $search->show) ? 'p.post_time' : 'p2.post_time';
			break;
	}
	$sql .= " {$search->sort_dir} LIMIT {$per_page} OFFSET {$start}";

	try {
		$result = $db->query($sql);
	} catch (\Exception $e) {
		\Poodle\HTTP\Status::set(404);
		message_die(GENERAL_ERROR, 'Could not obtain search results');
	}

	# Define censored word matches
	$orig_word = $replacement_word = array();
	obtain_word_list($orig_word, $replacement_word);

	$highlight_match = array();
	$synonym_array = file("includes/l10n/{$lang->lng}/Forums/search_synonyms.txt");
	$ck = count($synonym_array);
	foreach ($search->keywords_split as $split_word) {
		if ($split_word != 'and' && $split_word != 'or' && $split_word != 'not') {
			$highlight_match[] = $split_word;
			for ($k = 0; $k < $ck; ++$k) {
				list($replace_synonym, $match_synonym) = explode(' ', trim(mb_strtolower($synonym_array[$k])));
				if ($replace_synonym == $split_word) {
					$highlight_match[] = $replace_synonym;
				}
			}
		}
	}

	$highlight_active = urlencode(trim(implode(' ',$highlight_match)));
	$highlight_active = $highlight_active ? "&highlight={$highlight_active}" : "";

	$images = get_forums_images();
	$topic_icons = BoardCache::topic_icons();
	$template->searchresults = array();
	while ($searchrow = $result->fetch_assoc()) {
		$forum_url = URL::index('&file=viewforum&f=' . $searchrow['forum_id']);
		$topic_url = URL::index('&file=viewtopic&t=' . $searchrow['topic_id'] . $highlight_active);
		if (isset($searchrow['post_id'])) {
			$post_url = URL::index('&file=viewtopic&p=' . $searchrow['post_id'] . $highlight_active) . '#' . $searchrow['post_id'];
		} else {
			$post_url = '';
		}
		$post_date = $lang->date($board_config['default_dateformat'], $searchrow['post_time']);

		$message = isset($searchrow['post_text']) ? $searchrow['post_text'] : '';
		$topic_title = $searchrow['topic_title'];

		$forum_id = $searchrow['forum_id'];
		$topic_id = $searchrow['topic_id'];

		if ('posts' == $search->show) {
			if (isset($search->chars)) {
				# If the board has HTML off but the post has HTML
				# on then we process it, else leave it alone
				if ($search->chars > -1) {
					$message = strip_tags($message);
					$message = preg_replace('/\[url\]|\[\/url\]/si', '', $message);
					$message = (strlen($message) > $search->chars) ? substr($message, 0, $search->chars) . ' ...' : $message;
				} else {
					$apost = new \Dragonfly\Forums\Post();
					$apost->message        = $message;
					$apost->enable_bbcode  = $searchrow['enable_bbcode'];
					$apost->enable_html    = $searchrow['enable_html'];
					$apost->enable_smilies = $searchrow['enable_smilies'];
					$message = $apost->message2html($highlight_match);
				}

				if (count($orig_word)) {
					$topic_title = preg_replace($orig_word, $replacement_word, $topic_title);
					$post_subject = $searchrow['post_subject'] ? preg_replace($orig_word, $replacement_word, $searchrow['post_subject']) : $topic_title;
					$message = preg_replace($orig_word, $replacement_word, $message);
				} else {
					$post_subject = $searchrow['post_subject'] ?: $topic_title;
				}
			}

			if ($searchrow['user_id'] != \Dragonfly\Identity::ANONYMOUS_ID) {
				$poster = '<a href="' . htmlspecialchars(\Dragonfly\Identity::getProfileURL($searchrow['user_id'])) . '">' . $searchrow['username'] . '</a>';
			} else {
				$poster = $searchrow['post_username'] ?: $lang['Guest'];
			}

			$topic_last_read = \Dragonfly\Forums\Display::getForumTopicLastVisit($forum_id, $topic_id);
			if (is_user() && $searchrow['post_time'] > $topic_last_read) {
				$mini_post_img = $images['icon_minipost_new'];
				$mini_post_alt = $lang['New_post'];
			} else {
				$mini_post_img = $images['icon_minipost'];
				$mini_post_alt = $lang['Post'];
			}

			$template->assign_block_vars("searchresults", array(
				'TOPIC_TITLE' => htmlspecialchars($topic_title, ENT_NOQUOTES),
				'FORUM_NAME' => $searchrow['forum_name'],
				'POST_SUBJECT' => $post_subject,
				'POST_DATE' => $post_date,
				'POST_DATETIME' => gmdate('Y-m-d\\TH:i:s\\Z', $searchrow['post_time']),
				'POSTER_NAME' => $poster,
				'TOPIC_REPLIES' => $searchrow['topic_replies'],
				'TOPIC_VIEWS' => $searchrow['topic_views'],
				'MESSAGE' => $message,
				'MINI_POST_IMG' => $mini_post_img,

				'L_MINI_POST_ALT' => $mini_post_alt,

				'U_POST' => $post_url,
				'U_TOPIC' => $topic_url,
				'U_FORUM' => $forum_url
			));
		}
		else
		{
			$message = '';

			if (count($orig_word)) {
				$topic_title = preg_replace($orig_word, $replacement_word, $searchrow['topic_title']);
			}

			$topic_type = '';

			if ($searchrow['topic_vote']) {
				$topic_type .= $lang['Topic_Poll'] . ' ';
			}

			$views = $searchrow['topic_views'];
			$replies = $searchrow['topic_replies'];

			// Pagination
			if (($replies + 1) > $board_config['posts_per_page']) {
				$total_pages = ceil(($replies + 1) / $board_config['posts_per_page']);
				$goto_page = $lang['Goto_page'] . ': ';

				$times = 1;
				for($j = 0; $j < $replies + 1; $j += $board_config['posts_per_page']) {
					$goto_page .= '<a href="' . htmlspecialchars(URL::index("&file=viewtopic&t={$topic_id}&start={$j}{$highlight_active}")) . '">' . $times . '</a>';
					if ($times == 1 && $total_pages > 4) {
						$goto_page .= ' ... ';
						$times = $total_pages - 3;
						$j += ($total_pages - 4) * $board_config['posts_per_page'];
					} else if ($times < $total_pages) {
						$goto_page .= ', ';
					}
					++$times;
				}
			} else {
				$goto_page = '';
			}

			if (\Dragonfly\Forums\Topic::STATUS_MOVED == $searchrow['topic_status']) {
				$topic_type = $lang['Topic_Moved'] . ' ';
				$topic_id = $searchrow['topic_moved_id'];

				$folder_image = '<img src="' . DOMAIN_PATH . $images['folder'] . '" alt="' . $lang['No_new_posts'] . '" />';
				$newest_post_img = '';
			} else {
				if (\Dragonfly\Forums\Topic::STATUS_LOCKED == $searchrow['topic_status']) {
					$folder = $images['folder_locked'];
					$folder_new = $images['folder_locked_new'];
				}
				else if (\Dragonfly\Forums\Topic::TYPE_ANNOUNCE == $searchrow['topic_type']) {
					$folder = $images['folder_announce'];
					$folder_new = $images['folder_announce_new'];
				} else if (\Dragonfly\Forums\Topic::TYPE_STICKY == $searchrow['topic_type']) {
					$folder = $images['folder_sticky'];
					$folder_new = $images['folder_sticky_new'];
				} else {
					if ($replies >= $board_config['hot_threshold']) {
						$folder = $images['folder_hot'];
						$folder_new = $images['folder_hot_new'];
					} else {
						$folder = $images['folder'];
						$folder_new = $images['folder_new'];
					}
				}

				$topic_last_read = \Dragonfly\Forums\Display::getForumTopicLastVisit($forum_id, $topic_id);
				if (is_user() && $searchrow['post_time'] > $topic_last_read) {
					$folder_image = $folder_new;
					$folder_alt = $lang['New_posts'];
					$newest_post_img = '<a href="' . htmlspecialchars(URL::index("&file=viewtopic&t={$topic_id}&view=newest")) . '"><img src="' . DOMAIN_PATH . $images['icon_newest_reply'] . '" alt="' . $lang['View_newest_post'] . '" title="' . $lang['View_newest_post'] . '" /></a> ';
				} else {
					$folder_image = $folder;
					$folder_alt = (\Dragonfly\Forums\Topic::STATUS_LOCKED == $searchrow['topic_status']) ? $lang['Topic_locked'] : $lang['No_new_posts'];
					$newest_post_img = '';
				}
			}

			$topic_author = '';
			if ($searchrow['user_id'] != \Dragonfly\Identity::ANONYMOUS_ID) {
				$topic_author  = '<a href="' . htmlspecialchars(\Dragonfly\Identity::getProfileURL($searchrow['user_id'])) . '">';
				$topic_author .= $searchrow['username'];
				$topic_author .= '</a>';
			} else {
				$topic_author .= ($searchrow['post_username'] ?: $lang['Guest']);
			}

			$last_post_author = ($searchrow['id2'] == \Dragonfly\Identity::ANONYMOUS_ID)
				? ($searchrow['post_username2'] ?: $lang['Guest'])
				: '<a href="' . htmlspecialchars(\Dragonfly\Identity::getProfileURL($searchrow['id2'])) . '">' . $searchrow['user2'] . '</a>';

			$last_post_url = URL::index('&file=viewtopic&p=' . $searchrow['topic_last_post_id'] . $highlight_active) . '#' . $searchrow['topic_last_post_id'];

			$template->assign_block_vars('searchresults', array(
				'FORUM_NAME' => $searchrow['forum_name'],
				'FORUM_ID' => $forum_id,
				'TOPIC_ID' => $topic_id,
				'TOPIC_ICON' => !empty($topic_icons[$searchrow['icon_id']])
					? '<img src="'.DOMAIN_PATH.$topic_icons[$searchrow['icon_id']]['icon_url'].'" alt="'.$topic_icons[$searchrow['icon_id']]['icon_name'].'" title="'.$topic_icons[$searchrow['icon_id']]['icon_name'].'" style="vertical-align:middle;" />'
					: '',
				'FOLDER' => $folder_image,
				'NEWEST_POST_IMG' => $newest_post_img,
				'TOPIC_FOLDER_IMG' => DOMAIN_PATH . $folder_image,
				'GOTO_PAGE' => $goto_page ? $goto_page : false,
				'REPLIES' => $replies,
				'TOPIC_TITLE' => htmlspecialchars($topic_title, ENT_NOQUOTES),
				'TOPIC_TYPE' => $topic_type,
				'VIEWS' => $views,
				'TOPIC_AUTHOR' => $topic_author,
				'FIRST_POST_TIME' => $lang->date($board_config['default_dateformat'], $searchrow['topic_time']),
				'LAST_POST_TIME' => $post_date,
				'LAST_POST_AGO' => $lang->timeReadable(time() - $searchrow['post_time']) . ' ago',
				'LAST_POST_AUTHOR' => $last_post_author,
				'LAST_POST_IMG' => '<a href="' . htmlspecialchars($last_post_url) . '"><img src="' . DOMAIN_PATH . $images['icon_latest_reply'] . '" alt="' . $lang['View_latest_post'] . '" title="' . $lang['View_latest_post'] . '" /></a>',
				'L_TOPIC_FOLDER_ALT' => $folder_alt,
				'U_LAST_POST' => $last_post_url,
				'U_VIEW_FORUM' => $forum_url,
				'U_VIEW_TOPIC' => $topic_url
			));
		}
	}

	# Output header
	\Dragonfly\Page::title($lang['Search']);
	require_once('includes/phpBB/page_header.php');
	make_jumpbox('viewforum');
	$total_match_count = $search->total_match_count;
	$l_search_matches = ($total_match_count == 1) ? sprintf($lang['Found_search_match'], $total_match_count) : sprintf($lang['Found_search_matches'], $total_match_count);
	$template->assign_vars(array(
		'PAGINATION' => 'watch' == $search->id
			? generate_pagination('&file=search&search_id=watch', $total_match_count, $per_page, $start)
			: ('fwatch' == $search->id
				? generate_pagination('&file=search&search_id=fwatch', $total_match_count, $per_page, $start)
				: generate_pagination('&file=search&search_id='.$search->id, $total_match_count, $per_page, $start)),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], (floor($start / $per_page) + 1), ceil($total_match_count / $per_page)),
		'L_SELECT' => $lang['Select'],
		'L_AUTHOR' => $lang['Author'],
		'L_MESSAGE' => $lang['Message'],
		'L_FORUM' => $lang['Forum'],
		'L_TOPICS' => $lang['Topics'],
		'L_REPLIES' => $lang['Replies'],
		'L_VIEWS' => $lang['Views'],
		'L_POSTS' => $lang['Posts'],
		'L_LASTPOST' => $lang['Last_Post'],
		'L_POSTED' => $lang['Posted'],
		'L_SUBJECT' => $lang['Subject'],
		'L_MARK_ALL' => $lang['Mark_all'],
		'L_UNMARK_ALL' => $lang['Unmark_all'],
		'L_TOPIC' => $lang['Topic'],
		'L_SEARCH_MATCHES' => $l_search_matches,
		'S_WATCH_ACTION' => URL::index('&file=search')
	));

	if ('posts' == $search->show) {
		$template->display('forums/search_results_posts');
	} else if ('watch' == $search->id) {
		$template->display('forums/search_results_watch');
	} else if ('fwatch' == $search->id) {
		$template->display('forums/search_results_fwatch');
	} else {
		$template->display('forums/search_results_topics');
	}
	return;
}

$categories = $forums = array();
foreach (BoardCache::categories() as $cat) {
	$categories[]       = array('value' => $cat['id'], 'current' => $cat['id']==$search->cat, 'label' => $cat['title']);
	$forums[$cat['id']] = array('label' => $cat['title'], 'options'=>array());
}
$result = $db->query("SELECT cat_id, forum_id, forum_name FROM " . FORUMS_TABLE . " ORDER BY forum_order");
if (!$result->num_rows) {
	message_die(GENERAL_MESSAGE, $lang['No_searchable_forums']);
}
$is_auth_ary = \Dragonfly\Forums\Auth::read(0);
while ($row = $result->fetch_row()) {
	if ($is_auth_ary[$row[1]]['auth_read']) {
		$forums[$row[0]]['options'][] = array('value' => $row[1], 'current' => $row[1]==$search->forum, 'label' => $row[2]);
	}
}

# Output the basic page
\Dragonfly\Page::title($lang['Search']);

$template->assign_vars(array(
	'SEARCH_KEYWORDS_EXPLAIN' => $lang['Search_keywords_explain'],
	'SEARCH_AUTHOR_EXPLAIN' => $lang['Search_author_explain'],
	'S_SEARCH_ACTION' => URL::index('&file=search&mode=results'),
));

$template->category_options = $categories;
$template->forums_grouped   = $forums;

$template->topic_days_options = array(
	array('value' =>   0, 'current' =>   0==$search->days, 'label' => $lang['All_Posts']),
	array('value' =>   1, 'current' =>   1==$search->days, 'label' => $lang->timeReadable(86400, '%d')),
	array('value' =>   7, 'current' =>   7==$search->days, 'label' => $lang->timeReadable(604800, '%d')),
	array('value' =>  14, 'current' =>  14==$search->days, 'label' => $lang->timeReadable(604800*2, '%w')),
	array('value' =>  30, 'current' =>  30==$search->days, 'label' => $lang->timeReadable(2628000, '%m')),
	array('value' =>  90, 'current' =>  90==$search->days, 'label' => $lang->timeReadable(2628000*3, '%m')),
	array('value' => 180, 'current' => 180==$search->days, 'label' => $lang->timeReadable(2628000*6, '%m')),
	array('value' => 364, 'current' => 364==$search->days, 'label' => $lang->timeReadable(31536000, '%y')),
);

$template->sort_by_options = array(
	array('value' => 0, 'current' => 0==$search->sort_by, 'label' => $lang['Sort_Time']),
	array('value' => 1, 'current' => 1==$search->sort_by, 'label' => $lang['Sort_Post_Subject']),
	array('value' => 2, 'current' => 2==$search->sort_by, 'label' => $lang['Sort_Topic_Title']),
	array('value' => 3, 'current' => 3==$search->sort_by, 'label' => $lang['Sort_Author']),
	array('value' => 4, 'current' => 4==$search->sort_by, 'label' => $lang['Sort_Forum']),
);

$template->return_chars_options = array(
	array('value' => -1, 'current' => -1==$search->chars, 'label' => $lang['All_available']),
	array('value' =>  0, 'current' =>  0==$search->chars, 'label' =>  0),
	array('value' => 25, 'current' => 25==$search->chars, 'label' => 25),
	array('value' => 50, 'current' => 50==$search->chars, 'label' => 50),
);
for ($i = 100; $i < 1100 ; $i += 100) {
	$template->return_chars_options[] = array('value' => $i, 'current' => $i==$search->chars, 'label' => $i);
}

require_once('includes/phpBB/page_header.php');
make_jumpbox('viewforum');
$template->display('forums/search');
