<?php
/***************************************************************************
 *					admin_words.php
 *				  -------------------
 *	 begin		  : Thursday, Jul 12, 2001
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
/* Modifications made by CPG Dev Team http://cpgnuke.com		*/
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }

if (isset($_GET['mode']) || isset($_POST['mode'])) {
	$mode = $_POST->txt('mode') ?: $_GET->txt('mode');
} else {
	$mode = isset($_POST['add']) ? 'add' : '';
}

if ($mode) {
	if ('edit' == $mode || 'add' == $mode) {
		$word_id = $_GET->uint('id');

		if (isset($_POST['word'], $_POST['replacement'])) {
			$word = trim($_POST['word']);
			$replacement = trim($_POST['replacement']);
			if (!$word || !$replacement) {
				message_die(GENERAL_MESSAGE, $lang['Must_enter_word']);
			}
			if ($word_id) {
				$db->query("UPDATE {$db->TBL->bbwords}
					SET word = ".$db->quote($word).", replacement = ".$db->quote($replacement)."
					WHERE word_id = {$word_id}");
				$message = $lang['Word_updated'];
			} else {
				$db->query("INSERT INTO {$db->TBL->bbwords} (word, replacement)
					VALUES (".$db->quote($word).", ".$db->quote($replacement).")");
				$message = $lang['Word_added'];
			}
			\Dragonfly::closeRequest($message, 200, $_SERVER['REQUEST_URI']);
		}

		$template->set_handle('body', 'Forums/admin/words_edit');

		if ('edit' == $mode) {
			$word = $db->uFetchAssoc("SELECT word, replacement FROM {$db->TBL->bbwords} WHERE word_id = {$word_id}");
			if (!$word) {
				message_die(GENERAL_MESSAGE, $lang['No_word_selected']);
			}
		} else {
			$word = array('word'=>'','replacement'=>'');
		}
		$template->censor_word = $word;
	}

	else if ('delete' == $mode) {
		$word_id = $_POST->uint('id') ?: $_GET->uint('id');
		if ($word_id) {
			$db->query("DELETE FROM {$db->TBL->bbwords} WHERE word_id = {$word_id}");
			\Dragonfly::closeRequest($lang['Word_removed'], 200, $_SERVER['REQUEST_URI']);
		} else {
			\Dragonfly::closeRequest($lang['No_word_selected'], 404);
		}
	}
}

else {
	$template->set_handle('body', 'Forums/admin/words_list');
	$template->censor_words = $db->query("SELECT
		word,
		replacement,
		'".URL::admin('&do=words&mode=edit&id=')."' || word_id as u_edit,
		'".URL::admin('&do=words&mode=delete&id=')."' || word_id as u_delete
	FROM {$db->TBL->bbwords}
	ORDER BY word");
}
