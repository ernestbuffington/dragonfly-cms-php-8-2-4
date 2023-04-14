<?php
/***************************************************************************
 *								  index.php
 *							  -------------------
 *	 begin				  : Saturday, Feb 13, 2001
 *	 copyright			  : (C) 2001 The phpBB Group
 *	 email				  : support@phpbb.com
 *
  Last modification notes:
  $Source: /public_html/modules/Forums/index.php,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 11:56:26 $
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

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);
//
// End session management
//

$viewcat = ( !empty($_GET[POST_CAT_URL]) ) ? $_GET[POST_CAT_URL] : -1;

//
// Handle marking posts
//
if( isset($_GET['mark']) || isset($_POST['mark']) ) {
	$mark_read = $_POST['mark'] ?? $_GET['mark'];
	if ($mark_read == 'forums') {
		if (is_user()) {
			$CPG_SESS[$module_name]['track_all'] = gmtime();
		}
		url_refresh(getlink());
		$message = $lang['Forums_marked_read'] . '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . getlink() . '">', '</a> ');
		message_die(GENERAL_MESSAGE, $message);
	}
}
//
// End handle marking posts
//

//
// If you don't use these stats on your index you may want to consider removing them
//
$l_total_post_s = $total_posts = 0;

//
// Start page proper
//
if (!cache_load_array('category_rows', $module_name)) {
	$category_rows = $db->sql_ufetchrowset('SELECT c.cat_id, c.cat_title, c.cat_order FROM ' . CATEGORIES_TABLE . ' c ORDER BY c.cat_order', SQL_ASSOC);
	cache_save_array('category_rows', $module_name);
}

if( $total_categories = is_countable($category_rows) ? count($category_rows) : 0 ) {
	require_once('includes/phpBB/functions_display.php');
	$forum_data = display_forums();
	if ( !($total_forums = is_countable($forum_data) ? count($forum_data) : 0) ) {
		message_die(GENERAL_MESSAGE, $lang['No_forums']);
	}

	//
	// Start output of page
	//
	$page_title = _HOME; //$lang['Index'];
	require_once("includes/phpBB/page_header.php");

	$template->assign_vars(array(
		'FORUM_IMG' => $images['forum'],
		'FORUM_NEW_IMG' => $images['forum_new'],
		'FORUM_LOCKED_IMG' => $images['forum_locked'],
		'TOTAL_POSTS' => sprintf($l_total_post_s, $total_posts),

	   // 'L_ONLINE_EXPLAIN' => $lang['Online_explain'],
		'U_INDEX' => getlink(),
		'L_INDEX'=> _ForumsLANG,
		'L_FORUM' => $lang['Forum'],
		'L_TOPICS' => $lang['Topics'],
		'L_REPLIES' => $lang['Replies'],
		'L_VIEWS' => $lang['Views'],
		'L_POSTS' => $lang['Posts'],
		'L_LAST_POST' => $lang['Last_Post'],
		'L_NO_POSTS' => $lang['No_Posts'],
		'L_NO_NEW_POSTS' => $lang['No_new_posts'],
		'L_NEW_POSTS' => $lang['New_posts'],
		'L_NO_NEW_POSTS_LOCKED' => $lang['No_new_posts_locked'],
		'L_NEW_POSTS_LOCKED' => $lang['New_posts_locked'],
		'L_MODERATOR' => $lang['Moderators'],
		'L_FORUM_LOCKED' => $lang['Forum_is_locked'],
		'L_MARK_FORUMS_READ' => $lang['Mark_all_forums'],

		'U_MARK_READ' => getlink("&amp;mark=forums")
		)
	);

	//
	// Okay, let's build the index
	//
	for ($i = 0; $i < $total_categories; $i++) {
		$cat_id = $category_rows[$i]['cat_id'];

		//
		// Should we display this category/forum set?
		//
		$display_forums = false;
		for($j = 0; $j < $total_forums; $j++) {
			if ( $forum_data[$j]['cat_id'] == $cat_id ) {
				$display_forums = true;
				break;
			}
		}

		//
		// Yes, we should, so first dump out the category
		// title, then, if appropriate the forum list
		//
		$bid = 1000 + $cat_id;
		if ($display_forums) {
			$cattpl = 'forumrow';
			$template->assign_block_vars($cattpl, array(
				'S_IS_CAT'	  => TRUE,
				'CAT_ID'	=> $cat_id,
				'CAT_DESC'	=> $category_rows[$i]['cat_title'],
				'S_NOT_FIRST'	=> ($i == 0) ? FALSE : TRUE,
				'S_BID'     => $bid,
				'S_VISIBLE' => $Blocks->hideblock($bid) ? 'style="display:none"' : '',
				'S_HIDDEN'  => $Blocks->hideblock($bid) ? '' : 'style="display:none"',
				'U_VIEWCAT' => getlink("&amp;" . POST_CAT_URL . "=$cat_id"))
			);

			if ($viewcat == $cat_id || $viewcat == -1) {
				for ($j = 0; $j < $total_forums; $j++) {
					if ($forum_data[$j]['cat_id'] == $cat_id) {
						$forum_id = $forum_data[$j]['forum_id'];
						if ($forum_data[$j]['forum_type'] == 2) {
							$forumlink = getlink($forum_data[$j]['forum_link']);
						} else if ($forum_data[$j]['forum_type'] == 3) {
							$forumlink = $forum_data[$j]['forum_link'];
						} else {
							$forumlink = getlink("&amp;file=viewforum&amp;" . POST_FORUM_URL . "=$forum_id");
						}
						$template->assign_block_vars('forumrow', array(
							'S_IS_CAT'	=> false,
							'S_IS_LINK' => ($forum_data[$j]['forum_type'] >= 2),

							'LAST_POST_IMG'		 => $images['icon_latest_reply'],

							'FORUM_ID'			 => $forum_id,
							'FORUM_FOLDER_IMG'	 => $forum_data[$j]['folder_image'],
							'FORUM_NAME'		 => $forum_data[$j]['forum_name'],
							'FORUM_DESC'		 => $forum_data[$j]['forum_desc'],
							'POSTS'				 => $forum_data[$j]['forum_posts'],
							'TOPICS'			 => $forum_data[$j]['forum_topics'],
							'LAST_POST_TIME'	 => ($forum_data[$j]['forum_last_post_id'] ) ? create_date($board_config['default_dateformat'], $forum_data[$j]['post_time']) : '',
							'LAST_POSTER'		 => ($forum_data[$j]['username'] != 'Anonymous') ? $forum_data[$j]['username'] : $forum_data[$j]['post_username'],
							'MODERATORS'		 => $forum_data[$j]['moderator_list'],
//							'SUBFORUMS'		   => $subforums_list,

//							'L_SUBFORUM_STR'	   => $l_subforums,
							'L_MODERATOR_STR'	 => $forum_data[$j]['l_moderators'],
							'L_FORUM_FOLDER_ALT' => $forum_data[$j]['folder_alt'],

							'U_LAST_POSTER'		 => ($forum_data[$j]['user_id'] > ANONYMOUS) ? getlink("Your_Account&amp;profile=".$forum_data[$j]['user_id']) : '',
							'U_LAST_POST'		 => ($forum_data[$j]['forum_last_post_id']) ? getlink("&amp;file=viewtopic&amp;"  . POST_POST_URL . '=' . $forum_data[$j]['forum_last_post_id']) . '#' . $forum_data[$j]['forum_last_post_id'] : '',
							'U_VIEWFORUM'		 => $forumlink
						));
					}
				}
			}
		}
	} // for ... categories

}// if ... total_categories
else {
	message_die(GENERAL_MESSAGE, $lang['No_forums']);
}

//
// Generate the page
//
$template->set_filenames(array('body' => 'forums/index_body.html'));

require_once('includes/phpBB/page_tail.php');
