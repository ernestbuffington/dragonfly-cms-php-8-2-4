<?php
/***************************************************************************
*				  admin_forum_prune.php
*				   -------------------
*	  begin		   : Mon Jul 31, 2001
*	  copyright		   : (C) 2001 The phpBB Group
*	  email		   : support@phpbb.com
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
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }

if (isset($_POST['f'])) {
	URL::redirect(URL::admin('&do=forum_prune&f='.$_POST->uint('f')));
	exit;
}

//
// Get the forum ID for pruning
//

$forum_id = $_GET->uint('f');

//
// Check for submit to be equal to Prune. If so then proceed with the pruning.
//
if (isset($_POST['doprune'])) {
	// Convert days to seconds for timestamp functions...
	$prunedate = time() - (max(1,$_POST->uint('prunedays')) * 86400);
	$template->set_handle('body', 'Forums/admin/forum_prune_result');
	$forums = $forum_id ? array(array('forum_id'=>$forum_id)) : $db->query("SELECT forum_id FROM " . FORUMS_TABLE);
	foreach ($forums as $forum) {
		$forum = new \Dragonfly\Forums\Forum($forum['forum_id']);
		$p_result = $forum->prune($prunedate);
		$forum->sync();
		$template->assign_block_vars('prune_results', array(
			'FORUM_NAME'   => $forum->name,
			'FORUM_TOPICS' => $p_result['topics'],
			'FORUM_POSTS'  => $p_result['posts']
		));
	}
}

else {
	//
	// If they haven't selected a forum for pruning yet then
	// display a select box to use for pruning.
	//
	if (!isset($_GET['f'])) {
		//
		// Output a selection table if no forum id has been specified.
		//
		$template->set_handle('body', 'Forums/admin/forum_prune_select');
		$template->board_categories = array();
		$result = $db->query("SELECT cat_id id, cat_title title FROM " . CATEGORIES_TABLE . " ORDER BY cat_order");
		while ($row = $result->fetch_assoc()) {
			$row['forums'] = array();
			$template->board_categories[$row['id']] = $row;
		}
		$result = $db->query("SELECT cat_id, forum_id, forum_name FROM " . FORUMS_TABLE . " ORDER BY forum_order ASC");
		while ($forum = $result->fetch_row()) {
			$template->board_categories[$forum[0]]['forums'][] = array(
				'id' => $forum[1],
				'name' => $forum[2],
			);
		}
	} else {
		//
		// Output the form to retrieve Prune information.
		//
		$template->set_handle('body', 'Forums/admin/forum_prune');
		if ($forum_id) {
			list($template->FORUM_NAME) = $db->uFetchRow("SELECT forum_name FROM " . FORUMS_TABLE . " WHERE forum_id={$forum_id}");
		} else {
			$template->FORUM_NAME = $lang['All_Forums'];
		}
	}
}
