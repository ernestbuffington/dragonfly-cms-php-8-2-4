<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/ranks.php,v $
  $Revision: 9.9 $
  $Author: phoenix $
  $Date: 2007/05/15 00:02:32 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('members')) { die('Access Denied'); }

$pagetitle .= ' '._BC_DELIM.' Ranks';
require('header.php');
GraphicAdmin('_AMENU2');
OpenTable();

define('IN_PHPBB', 1);
define('IN_ADMIN', true);
$module_name = 'Forums';
$phpbb_root_path = "modules/$module_name/";
include('modules/Forums/common.php');
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);

if( isset($_GET['mode']) || isset($_POST['mode']) ) {
	$mode = htmlprepare($_GET['mode'] ?? $_POST['mode']);
} else {
	if( isset($_POST['add']) ) {
		$mode = 'add';
	} else if( isset($_POST['save']) ) {
		$mode = 'save';
	} else {
		$mode = '';
	}
}


if( $mode != '' ) {
	if( $mode == 'edit' || $mode == 'add' ) {
		//
		// They want to add a new rank, show the form.
		//
		$rank_id = ( isset($_GET['id']) ) ? intval($_GET['id']) : 0;

		$s_hidden_fields = '';

		if( $mode == 'edit' ) {
			if( empty($rank_id) ) {
				message_die(GENERAL_MESSAGE, $lang['Must_select_rank']);
			}
			$result = $db->sql_query("SELECT * FROM " . RANKS_TABLE . " WHERE rank_id = $rank_id");
			$rank_info = $db->sql_fetchrow($result);
			$s_hidden_fields .= '<input type="hidden" name="id" value="' . $rank_id . '" />';
		} else {
			$rank_info['rank_special'] = 0;
		}

		$s_hidden_fields .= '<input type="hidden" name="mode" value="save" />';

		$rank_is_special = ( $rank_info['rank_special'] ) ? "checked=\"checked\"" : "";
		$rank_is_not_special = ( !$rank_info['rank_special'] ) ? "checked=\"checked\"" : "";

		$template->set_filenames(array('body' => 'forums/admin/ranks_edit_body.html'));

		$template->assign_vars(array(
			"RANK" => $rank_info['rank_title'] ?? '',
			"SPECIAL_RANK" => $rank_is_special,
			"NOT_SPECIAL_RANK" => $rank_is_not_special,
			"MINIMUM" => ( $rank_is_special ) ? "" : isset($rank_info['rank_min']) ? $rank_info['rank_min'] :'',
			"IMAGE" => ( isset($rank_info['rank_image']) && $rank_info['rank_image']!= "" ) ? $rank_info['rank_image'] : "",
			"IMAGE_DISPLAY" => ( isset($rank_info['rank_image']) && $rank_info['rank_image']!= "" ) ? '<img src="' . $rank_info['rank_image'] . '" alt="" />' : "",

			"L_RANKS_TITLE" => $lang['Ranks_title'],
			"L_RANKS_TEXT" => $lang['Ranks_explain'],
			"L_RANK_TITLE" => $lang['Rank_title'],
			"L_RANK_SPECIAL" => $lang['Rank_special'],
			"L_RANK_MINIMUM" => $lang['Rank_minimum'],
			"L_RANK_IMAGE" => $lang['Rank_image'],
			"L_RANK_IMAGE_EXPLAIN" => $lang['Rank_image_explain'],
			"L_SUBMIT" => $lang['Submit'],
			"L_RESET" => $lang['Reset'],
			"L_YES" => $lang['Yes'],
			"L_NO" => $lang['No'],

			"S_RANK_ACTION" => adminlink("$op"),
			"S_HIDDEN_FIELDS" => $s_hidden_fields)
		);

	}
	else if( $mode == "save" )
	{
		//
		// Ok, they sent us our info, let's update it.
		//

		$rank_id = ( isset($_POST['id']) ) ? intval($_POST['id']) : 0;
		$rank_title = ( isset($_POST['title']) ) ? trim($_POST['title']) : "";
		$special_rank = ( $_POST['special_rank'] == 1 ) ? TRUE : 0;
		$min_posts = ( isset($_POST['min_posts']) ) ? intval($_POST['min_posts']) : -1;
		$rank_image = ( (isset($_POST['rank_image'])) ) ? trim($_POST['rank_image']) : "";

		if( $rank_title == "" )
		{
			message_die(GENERAL_MESSAGE, $lang['Must_select_rank']);
		}

		if( $special_rank == 1 )
		{
			$max_posts = -1;
			$min_posts = -1;
		}

		//
		// The rank image has to be a jpg, gif or png
		//
		if($rank_image != "")
		{
			if ( !preg_match("/(\.gif|\.png|\.jpg)$/is", $rank_image))
			{
				$rank_image = "";
			}
		}

		if ($rank_id) {
			if (!$special_rank) {
				$db->sql_query("UPDATE " . USERS_TABLE . " SET user_rank = 0 WHERE user_rank = $rank_id");
			}
			$sql = "UPDATE " . RANKS_TABLE . "
				SET rank_title = '" . Fix_Quotes($rank_title) . "', rank_special = $special_rank, rank_min = $min_posts, rank_image = '" . Fix_Quotes($rank_image) . "'
				WHERE rank_id = $rank_id";
			$message = $lang['Rank_updated'];
		} else {
			$sql = "INSERT INTO " . RANKS_TABLE . " (rank_title, rank_special, rank_min, rank_image)
				VALUES ('" . Fix_Quotes($rank_title) . "', $special_rank, $min_posts, '" . Fix_Quotes($rank_image) . "')";
			$message = $lang['Rank_added'];
		}
		$db->sql_query($sql);
		$message .= "<br /><br />" . sprintf($lang['Click_return_rankadmin'], "<a href=\"".adminlink("$op")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
		message_die(GENERAL_MESSAGE, $message);
	} else if( $mode == "delete" ) {
		//
		// Ok, they want to delete their rank
		//
		if( isset($_POST['id']) || isset($_GET['id']) ) {
			$rank_id = ( isset($_POST['id']) ) ? intval($_POST['id']) : intval($_GET['id']);
		} else {
			$rank_id = 0;
		}

		if( $rank_id ) {
			$db->sql_query("DELETE FROM " . RANKS_TABLE . " WHERE rank_id = $rank_id");
			$db->sql_query("UPDATE " . USERS_TABLE . " SET user_rank = 0 WHERE user_rank = $rank_id");
			$message = $lang['Rank_removed'] . "<br /><br />" . sprintf($lang['Click_return_rankadmin'], "<a href=\"".adminlink("$op")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
			message_die(GENERAL_MESSAGE, $message);
		} else {
			message_die(GENERAL_MESSAGE, $lang['Must_select_rank']);
		}
	} else {
		//
		// They didn't feel like giving us any information. Oh, too bad, we'll just display the
		// list then...
		//
		$template->set_filenames(array('body' => 'forums/admin/ranks_list_body.html'));

		$result = $db->sql_query("SELECT * FROM " . RANKS_TABLE . " ORDER BY rank_min, rank_title");
		$rank_rows = $db->sql_fetchrowset($result);
		$rank_count = is_countable($rank_rows) ? count($rank_rows) : 0;

		$template->assign_vars(array(
			"L_RANKS_TITLE" => $lang['Ranks_title'],
			"L_RANKS_TEXT" => $lang['Ranks_explain'],
			"L_RANK" => $lang['Rank_title'],
			"L_RANK_MINIMUM" => $lang['Rank_minimum'],
			"L_SPECIAL_RANK" => $lang['Special_rank'],
			"L_EDIT" => $lang['Edit'],
			"L_DELETE" => $lang['Delete'],
			"L_ADD_RANK" => $lang['Add_new_rank'],
			"L_ACTION" => $lang['Action'],

			"S_RANKS_ACTION" => adminlink("$op")
			)
		);

		for( $i = 0; $i < $rank_count; $i++)
		{
			$rank = $rank_rows[$i]['rank_title'];
			$special_rank = $rank_rows[$i]['rank_special'];
			$rank_id = $rank_rows[$i]['rank_id'];
			$rank_min = $rank_rows[$i]['rank_min'];

			if($special_rank)
			{
				$rank_min = $rank_max = "-";
			}

			$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
			$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

			$template->assign_block_vars("ranks", array(
				"ROW_COLOR" => $row_color,
				"ROW_CLASS" => $row_class,
				"RANK" => $rank,
				"RANK_MIN" => $rank_min,

				"SPECIAL_RANK" => ( $special_rank == 1 ) ? $lang['Yes'] : $lang['No'],

				"U_RANK_EDIT" => adminlink("$op&amp;mode=edit&amp;id=$rank_id"),
				"U_RANK_DELETE" => adminlink("$op&amp;mode=delete&amp;id=$rank_id")
				)
			);
		}
	}
} else {
	//
	// Show the default page
	//
	$template->set_filenames(array('body' => 'forums/admin/ranks_list_body.html'));

	$result = $db->sql_query("SELECT * FROM " . RANKS_TABLE . " ORDER BY rank_min ASC, rank_special ASC");
	$rank_count = $db->sql_numrows($result);
	$rank_rows = $db->sql_fetchrowset($result);

	$template->assign_vars(array(
		"L_RANKS_TITLE" => $lang['Ranks_title'],
		"L_RANKS_TEXT" => $lang['Ranks_explain'],
		"L_RANK" => $lang['Rank_title'],
		"L_RANK_MINIMUM" => $lang['Rank_minimum'],
		"L_SPECIAL_RANK" => $lang['Rank_special'],
		"L_EDIT" => $lang['Edit'],
		"L_DELETE" => $lang['Delete'],
		"L_ADD_RANK" => $lang['Add_new_rank'],
		"L_ACTION" => $lang['Action'],

		"S_RANKS_ACTION" => adminlink("$op")
		)
	);

	for($i = 0; $i < $rank_count; $i++)
	{
		$rank = $rank_rows[$i]['rank_title'];
		$special_rank = $rank_rows[$i]['rank_special'];
		$rank_id = $rank_rows[$i]['rank_id'];
		$rank_min = $rank_rows[$i]['rank_min'];

		if( $special_rank == 1 )
		{
			$rank_min = $rank_max = "-";
		}

		$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		$rank_is_special = ( $special_rank ) ? $lang['Yes'] : $lang['No'];

		$template->assign_block_vars("ranks", array(
			"ROW_COLOR" => $row_color,
			"ROW_CLASS" => $row_class,
			"RANK" => $rank,
			"SPECIAL_RANK" => $rank_is_special,
			"RANK_MIN" => $rank_min,

			"U_RANK_EDIT" => adminlink("$op&amp;mode=edit&amp;id=$rank_id"),
			"U_RANK_DELETE" => adminlink("$op&amp;mode=delete&amp;id=$rank_id")
			)
		);
	}
}
$template->display('body');
CloseTable();
