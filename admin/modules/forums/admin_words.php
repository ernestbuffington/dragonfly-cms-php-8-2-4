<?php
/***************************************************************************
 *					admin_words.php
 *				  -------------------
 *	 begin		  : Thursday, Jul 12, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 $Id: admin_words.php,v 9.7 2007/05/15 09:21:54 phoenix Exp $
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
/* Modifications made by CPG Dev Team http://cpgnuke.com		*/
/* Last modification notes:							*/
/*										*/
/*	 $Id: admin_words.php,v 9.7 2007/05/15 09:21:54 phoenix Exp $		 */
/*										*/
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if( isset($_GET['mode']) || isset($_POST['mode']) ) {
	$mode = isset($_POST['mode']) ? $_POST['mode'] : isset($_GET['mode']) ? $_GET['mode'] : '';
	$mode = htmlprepare($mode);
} else {
	//
	// These could be entered via a form button
	//
	if( isset($_POST['add']) ) {
		$mode = "add";
	} else if( isset($_POST['save']) ) {
		$mode = "save";
	} else {
		$mode = "";
	}
}

if( $mode != "" ) {
	if( $mode == "edit" || $mode == "add" ) {
		$word_id = ( isset($_GET['id']) ) ? intval($_GET['id']) : 0;
		$template->set_filenames(array('body' => 'forums/admin/words_edit_body.html'));
		$s_hidden_fields = '';
		if( $mode == "edit" ) {
			if( $word_id ) {
				$result = $db->sql_query("SELECT * FROM ".WORDS_TABLE." WHERE word_id = $word_id");
				$word_info = $db->sql_fetchrow($result);
				$s_hidden_fields .= '<input type="hidden" name="id" value="'.$word_id.'" />';
			} else {
				message_die(GENERAL_MESSAGE, $lang['No_word_selected']);
			}
		}

		$template->assign_vars(array(
			"WORD" => (isset($word_info['word']) ? htmlprepare($word_info['word']) : ''),
			"REPLACEMENT" => (isset($word_info['replacement']) ? htmlprepare($word_info['replacement']) : ''),

			"L_WORDS_TITLE" => $lang['Words_title'],
			"L_WORDS_TEXT" => $lang['Words_explain'],
			"L_WORD_CENSOR" => $lang['Edit_word_censor'],
			"L_WORD" => $lang['Word'],
			"L_REPLACEMENT" => $lang['Replacement'],
			"L_SUBMIT" => $lang['Submit'],

			"S_WORDS_ACTION" => adminlink("&amp;do=words"),
			"S_HIDDEN_FIELDS" => $s_hidden_fields)
		);

	} else if( $mode == "save" ) {
		$word_id = ( isset($_POST['id']) ) ? intval($_POST['id']) : 0;
		$word = ( isset($_POST['word']) ) ? trim($_POST['word']) : "";
		$replacement = ( isset($_POST['replacement']) ) ? trim($_POST['replacement']) : "";
		if($word == "" || $replacement == "") {
			message_die(GENERAL_MESSAGE, $lang['Must_enter_word']);
		}
		if( $word_id ) {
			$sql = "UPDATE ".WORDS_TABLE."
				SET word = '".Fix_Quotes($word)."', replacement = '".Fix_Quotes($replacement)."'
				WHERE word_id = $word_id";
			$message = $lang['Word_updated'];
		} else {
			$sql = "INSERT INTO ".WORDS_TABLE." (word, replacement)
				VALUES ('".Fix_Quotes($word)."', '".Fix_Quotes($replacement)."')";
			$message = $lang['Word_added'];
		}
		if(!$result = $db->sql_query($sql)) {
			message_die(GENERAL_ERROR, "Could not insert data into words table", $lang['Error'], __LINE__, __FILE__, $sql);
		}
		$message .= "<br /><br />".sprintf($lang['Click_return_wordadmin'], "<a href=\"".adminlink("&amp;do=words")."\">", "</a>")."<br /><br />".sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
		message_die(GENERAL_MESSAGE, $message);
	} else if( $mode == "delete" ) {
		if( isset($_POST['id']) ||	isset($_GET['id']) ) {
			$word_id = ( isset($_POST['id']) ) ? $_POST['id'] : $_GET['id'];
			$word_id = intval($word_id);
		} else {
			$word_id = 0;
		}
		if( $word_id ) {
			$db->sql_query("DELETE FROM ".WORDS_TABLE." WHERE word_id = $word_id");
			$message = $lang['Word_removed']."<br /><br />".sprintf($lang['Click_return_wordadmin'], "<a href=\"".adminlink("&amp;do=words")."\">", "</a>")."<br /><br />".sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
			message_die(GENERAL_MESSAGE, $message);
		} else {
			message_die(GENERAL_MESSAGE, $lang['No_word_selected']);
		}
	}
} else {
	$template->set_filenames(array('body' => 'forums/admin/words_list_body.html'));

	$result = $db->sql_query("SELECT * FROM ".WORDS_TABLE." ORDER BY word");
	$word_rows = $db->sql_fetchrowset($result);
	$word_count = count($word_rows);
	$template->assign_vars(array(
		"L_WORDS_TITLE" => $lang['Words_title'],
		"L_WORDS_TEXT" => $lang['Words_explain'],
		"L_WORD" => $lang['Word'],
		"L_REPLACEMENT" => $lang['Replacement'],
		"L_EDIT" => $lang['Edit'],
		"L_DELETE" => $lang['Delete'],
		"L_ADD_WORD" => $lang['Add_new_word'],
		"L_ACTION" => $lang['Action'],

		"S_WORDS_ACTION" => adminlink("&amp;do=words"),
		"S_HIDDEN_FIELDS" => '')
	);

	for($i = 0; $i < $word_count; $i++) {
		$word = $word_rows[$i]['word'];
		$replacement = $word_rows[$i]['replacement'];
		$word_id = $word_rows[$i]['word_id'];

		$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		$template->assign_block_vars("words", array(
			"ROW_COLOR" => $row_color,
			"ROW_CLASS" => $row_class,
			"WORD" => $word,
			"REPLACEMENT" => $replacement,

			"U_WORD_EDIT" => adminlink("&amp;do=words&amp;mode=edit&amp;id=$word_id"),
			"U_WORD_DELETE" => adminlink("&amp;do=words&amp;mode=delete&amp;id=$word_id")
			)
		);
	}
}
