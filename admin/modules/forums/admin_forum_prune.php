<?php
/***************************************************************************
*				  admin_forum_prune.php
*				   -------------------
*	  begin		   : Mon Jul 31, 2001
*	  copyright		   : (C) 2001 The phpBB Group
*	  email		   : support@phpbb.com
*
*	  $Id: admin_forum_prune.php,v 9.4 2007/05/15 09:21:53 phoenix Exp $
*
****************************************************************************/
/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/
/* Modifications made by CPG Dev Team http://cpgnuke.com		*/
/* Last modification notes:							*/
/*										*/
/*	 $Id: admin_forum_prune.php,v 9.4 2007/05/15 09:21:53 phoenix Exp $		*/
/*										*/
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
require("includes/phpBB/prune.php");
require("includes/phpBB/functions_admin.php");

//
// Get the forum ID for pruning
//
if( isset($_GET[POST_FORUM_URL]) || isset($_POST[POST_FORUM_URL]) ) {
	$forum_id = $_POST[POST_FORUM_URL] ?? $_GET[POST_FORUM_URL];
	if( $forum_id == -1 ) {
		$forum_sql = '';
	} else {
		$forum_id = intval($forum_id);
		$forum_sql = "AND forum_id = $forum_id";
	}
} else {
	$forum_id = '';
	$forum_sql = '';
}
//
// Get a list of forum's or the data for the forum that we are pruning.
//
$sql = "SELECT f.*
	FROM " . FORUMS_TABLE . " f, " . CATEGORIES_TABLE . " c
	WHERE c.cat_id = f.cat_id
	$forum_sql
	ORDER BY c.cat_order ASC, f.forum_order ASC";
$result = $db->sql_query($sql);
$forum_rows = array();
while( $row = $db->sql_fetchrow($result) ) {
	$forum_rows[] = $row;
}

//
// Check for submit to be equal to Prune. If so then proceed with the pruning.
//
if( isset($_POST['doprune']) ) {
	$prunedays = ( isset($_POST['prunedays']) ) ? intval($_POST['prunedays']) : 0;

	// Convert days to seconds for timestamp functions...
	$prunedate = gmtime() - ( $prunedays * 86400 );

	$template->set_filenames(array('body' => 'forums/admin/forum_prune_result_body.html'));

	for($i = 0; $i < count($forum_rows); $i++) {
		$p_result = prune($forum_rows[$i]['forum_id'], $prunedate);
		sync('forum', $forum_rows[$i]['forum_id']);

		$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		$template->assign_block_vars('prune_results', array(
			'ROW_COLOR' => $row_color,
			'ROW_CLASS' => $row_class,
			'FORUM_NAME' => $forum_rows[$i]['forum_name'],
			'FORUM_TOPICS' => $p_result['topics'],
			'FORUM_POSTS' => $p_result['posts'])
		);
	}
	$template->assign_vars(array(
		'L_FORUM_PRUNE' => $lang['Forum_Prune'],
		'L_FORUM' => $lang['Forum'],
		'L_TOPICS_PRUNED' => $lang['Topics_pruned'],
		'L_POSTS_PRUNED' => $lang['Posts_pruned'],
		'L_PRUNE_RESULT' => $lang['Prune_success'])
	);
} else {
	//
	// If they haven't selected a forum for pruning yet then
	// display a select box to use for pruning.
	//
	if( empty($_POST[POST_FORUM_URL]) ) {
		//
		// Output a selection table if no forum id has been specified.
		//
		$template->set_filenames(array('body' => 'forums/admin/forum_prune_select_body.html'));
		$select_list = '<select name="' . POST_FORUM_URL . '">';
		$select_list .= '<option value="-1">' . $lang['All_Forums'] . '</option>';
		for($i = 0; $i < count($forum_rows); $i++) {
			$select_list .= '<option value="' . $forum_rows[$i]['forum_id'] . '">' . $forum_rows[$i]['forum_name'] . '</option>';
		}
		$select_list .= '</select>';
		//
		// Assign the template variables.
		//
		$template->assign_vars(array(
			'L_FORUM_PRUNE' => $lang['Forum_Prune'],
			'L_SELECT_FORUM' => $lang['Select_a_Forum'],
			'L_LOOK_UP' => $lang['Look_up_Forum'],

			'S_FORUMPRUNE_ACTION' => adminlink("&amp;do=forum_prune"),
			'S_FORUMS_SELECT' => $select_list)
		);
	} else {
		$forum_id = intval($_POST[POST_FORUM_URL]);
		//
		// Output the form to retrieve Prune information.
		//
		$template->set_filenames(array('body' => 'forums/admin/forum_prune_body.html'));
		$forum_name = ( $forum_id == -1 ) ? $lang['All_Forums'] : $forum_rows[0]['forum_name'];
		$prune_data = $lang['Prune_topics_not_posted'] . " ";
		$prune_data .= '<input class="post" type="text" name="prunedays" size="4"> ' . $lang['Days'];
		$hidden_input = '<input type="hidden" name="' . POST_FORUM_URL . '" value="' . $forum_id . '">';
		//
		// Assign the template variables.
		//
		$template->assign_vars(array(
			'FORUM_NAME' => $forum_name,

			'L_FORUM' => $lang['Forum'],
			'L_FORUM_PRUNE' => $lang['Forum_Prune'],
			'L_FORUM_PRUNE_EXPLAIN' => $lang['Forum_Prune_explain'],
			'L_DO_PRUNE' => $lang['Do_Prune'],

			'S_FORUMPRUNE_ACTION' => adminlink("&amp;do=forum_prune"),
			'S_PRUNE_DATA' => $prune_data,
			'S_HIDDEN_VARS' => $hidden_input)
		);
	}
}
