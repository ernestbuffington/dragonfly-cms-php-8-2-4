<?php
/*********************************************
  CPG-NUKE: Advanced Content Management System
  ********************************************
  Copyright (c) 2004 by CPG-Nuke Dev Team
  http://www.cpgnuke.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/phpBB/functions.php,v $
  $Revision: 9.18 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:18 $

***********************************************************************/
if (!defined('CPG_NUKE')) { die('You do not have permission to access this file'); }

function make_jumpbox($action, $match_forum_id = 0) {
	global $template, $userdata, $lang, $db, $nav_links;
	$category_rows = $db->sql_ufetchrowset("SELECT cat_id, cat_title FROM ".CATEGORIES_TABLE." ORDER BY cat_order", SQL_ASSOC);
/*	$sql = "SELECT c.cat_id, c.cat_title, c.cat_order
		FROM ".CATEGORIES_TABLE." c, ".FORUMS_TABLE." f
		WHERE f.cat_id = c.cat_id
		GROUP BY c.cat_id, c.cat_title, c.cat_order
		ORDER BY c.cat_order";
	$result = $db->sql_query($sql);

	$category_rows = array();
	while ($row = $db->sql_fetchrow($result)) {
		$category_rows[] = $row;
	}*/

	$boxstring = '<select name="'.POST_FORUM_URL.'" onchange="if(this.options[this.selectedIndex].value != -1){ forms[\'jumpbox\'].submit() }">';
	if ($total_categories = is_countable($category_rows) ? count($category_rows) : 0) {
		$boxstring .= '<option value="-1">'.$lang['Select_forum'].'</option>';
		$result = $db->sql_query("SELECT * FROM ".FORUMS_TABLE." ORDER BY cat_id, forum_order");
		$forum_rows = array();
		while ($row = $db->sql_fetchrow($result)) {
			$forum_rows[] = $row;
		}
		if ($total_forums = count($forum_rows)) {
			for ($i = 0; $i < $total_categories; $i++) {
				$boxstring_forums = '';
				for($j = 0; $j < $total_forums; $j++) {
					if ($forum_rows[$j]['cat_id'] == $category_rows[$i]['cat_id'] && $forum_rows[$j]['auth_view'] <= AUTH_REG) {
						$selected = ($forum_rows[$j]['forum_id'] == $match_forum_id) ? ' selected="selected"' : '';
						$boxstring_forums .=  '<option value="'.$forum_rows[$j]['forum_id'].'"'.$selected.'>'.$forum_rows[$j]['forum_name'].'</option>';
						//
						// Add an array to $nav_links for the Mozilla navigation bar.
						// 'chapter' and 'forum' can create multiple items, therefore we are using a nested array.
						//
						$nav_links['chapter forum'][$forum_rows[$j]['forum_id']] = array (
							'url' => getlink("&amp;file=viewforum&amp;".POST_FORUM_URL."=".$forum_rows[$j]['forum_id']),
							'title' => $forum_rows[$j]['forum_name']
						);
					}
				}
				if ($boxstring_forums != '') {
					$boxstring .= '<optgroup label="'.$category_rows[$i]['cat_title'].'">';
					$boxstring .= $boxstring_forums;
					$boxstring .= '</optgroup>';
				}
			}
		}
	}
	$boxstring .= '</select>';
	$template->assign_vars(array(
		'L_JUMP_TO' => $lang['Jump_to'],
		'L_SELECT_FORUM' => $lang['Select_forum'],

		'S_JUMPBOX_SELECT' => $boxstring,
		'S_JUMPBOX_ACTION' => getlink("&amp;file=$action")
	));
	return;
}

//
// Initialise user settings on page load
function init_userprefs($userdata)
{
	global $board_config, $theme, $images;
	global $template, $lang, $phpbb_root_path;
	global $nav_links, $currentlang;
	$board_config['default_lang'] = $currentlang;
	if (is_user()) {
		if (!empty($userdata['user_dateformat'])) {
			$board_config['default_dateformat'] = $userdata['user_dateformat'];
		}
		if (isset($userdata['user_timezone'])) {
			$board_config['board_timezone'] = $userdata['user_timezone'];
			if ($userdata['user_dst']) {
				$localtime = L10NTime::tolocal(gmtime(), 0, $board_config['board_timezone']);
				if (L10NTime::in_dst($localtime, $userdata['user_dst'])) {
					$board_config['board_timezone'] += 1;
				}
			}
		}
	}
	if (file_exists('language/'.$board_config['default_lang'].'/forums.php')) {
		include('language/'.$board_config['default_lang'].'/forums.php');
	} elseif (file_exists('language/'.$board_config['default_lang'].'/Forums/lang_main.php')) {
		include('language/'.$board_config['default_lang'].'/Forums/lang_main.php');
	} else {
		include('language/english/forums.php');
	}
	/* moved to main lang
	if (defined('IN_ADMIN')) {
		if( !file_exists('language/'.$board_config['default_lang'].'/Forums/lang_admin.php') ) {
			$board_config['default_lang'] = 'english';
		}
		include('language/'.$board_config['default_lang'].'/Forums/lang_admin.php');
	}*/
	//
	// Mozilla navigation bar
	// Default items that should be valid on all pages.
	// Defined here to correctly assign the Language Variables
	// and be able to change the variables within code.
	//
	$nav_links['top'] = array (
		'url' => getlink(),
		'title' => sprintf($lang['Forum_Index'], $board_config['sitename'])
	);
	$nav_links['search'] = array (
		'url' => getlink('&amp;file=search'),
		'title' => $lang['Search']
	);
	$nav_links['help'] = array (
		'url' => getlink('&amp;file=faq'),
		'title' => $lang['FAQ']
	);
	//
	// Set up style
	//
	if (!$board_config['override_user_style'] && is_user() && $userdata['user_style'] > 0) {
		if ($theme = setup_style($userdata['user_style']) ) { return; }
	}
	$theme = setup_style($board_config['default_style']);
	return;
}

function setup_style($style)
{
	global $db, $prefix, $board_config, $template, $cpgtpl, $images, $phpbb_root_path, $CPG_SESS;
	$result = $db->sql_query("SELECT * FROM ".$prefix."_bbthemes WHERE themes_id = $style");
	if (!($row = $db->sql_fetchrow($result))) {
		message_die(BB_CRITICAL_ERROR, "Could not get theme data for themes_id [$style]");
	}

	if (file_exists("themes/$CPG_SESS[theme]/template/forums/images.cfg")) {
		$template_name = $CPG_SESS['theme'];
	} else {
		$template_name = 'default';
	}
	$current_template_path = "themes/$template_name/images/forums";
	include("themes/$template_name/template/forums/images.cfg");
	if (!defined('TEMPLATE_CONFIG')) {
		message_die(BB_CRITICAL_ERROR, "Could not open $template_name template config file", '', __LINE__, __FILE__);
	}
	$img_lang = ( file_exists(realpath($current_template_path.'/lang_'.$board_config['default_lang'])) ) ? $board_config['default_lang'] : 'english';
	foreach ($images as $key => $value) {
     if (!is_array($value)) { $images[$key] = str_replace('{LANG}', 'lang_'.$img_lang, $value); }
 }
	return $row;
}

//
// Create date/time from format and timezone
//
function create_date($format, $gmepoch)
{
	global $board_config, $userinfo;
	if (is_user()) {
		return L10NTime::date($format, $gmepoch, $userinfo['user_dst'], $userinfo['user_timezone']);
	} else {
		return L10NTime::date($format, $gmepoch, 0, $board_config['board_timezone']);
	}
}

function get_topic_icons($forum_id, $include_global = true)
{
	global $db, $prefix;
	$sql = "SELECT * FROM ".TOPIC_ICONS_TABLE." WHERE forum_id = $forum_id ";
	if ($include_global) { $sql .= " OR forum_id = -1"; }
	$result = $db->sql_query($sql);
	$topic_icons = array();
	while ($row = $db->sql_fetchrow($result)) {
		$topic_icons[$row['icon_id']] = $row;
	}
	$db->sql_freeresult($result);
	return $topic_icons;
}

//
// Pagination routine, generates
// page number sequence
//
function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = TRUE)
{
	global $lang;
	$total_pages = ceil($num_items/$per_page);
	if ($total_pages == 1) { return ''; }

	$on_page = floor($start_item / $per_page) + 1;

	$page_string = '';
	if ( $total_pages > 10 ) {
		$init_page_max = ( $total_pages > 3 ) ? 3 : $total_pages;

		for($i = 1; $i < $init_page_max + 1; $i++) {
			$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url."&amp;start=".( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
			if ( $i <  $init_page_max ) { $page_string .= ", "; }
		}

		if ( $total_pages > 3 ) {
			if ( $on_page > 1  && $on_page < $total_pages ) {
				$page_string .= ( $on_page > 5 ) ? ' ... ' : ', ';

				$init_page_min = ( $on_page > 4 ) ? $on_page : 5;
				$init_page_max = ( $on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;

				for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++) {
					$page_string .= ($i == $on_page) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url."&amp;start=".( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
					if ( $i <  $init_page_max + 1 ) { $page_string .= ', '; }
				}

				$page_string .= ( $on_page < $total_pages - 4 ) ? ' ... ' : ', ';
			} else {
				$page_string .= ' ... ';
			}

			for($i = $total_pages - 2; $i < $total_pages + 1; $i++) {
				$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>'  : '<a href="'.getlink($base_url."&amp;start=".( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
				if( $i <  $total_pages ) { $page_string .= ", "; }
			}
		}
	} else {
		for($i = 1; $i < $total_pages + 1; $i++) {
			$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url."&amp;start=".( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
			if ( $i <  $total_pages ) { $page_string .= ', '; }
		}
	}

	if ( $add_prevnext_text ) {
		if ( $on_page > 1 ) {
			$page_string = ' <a href="'.getlink($base_url."&amp;start=".( ( $on_page - 2 ) * $per_page ) ).'">'.$lang['Previous'].'</a>&nbsp;&nbsp;'.$page_string;
		}
		if ( $on_page < $total_pages ) {
			$page_string .= '&nbsp;&nbsp;<a href="'.getlink($base_url."&amp;start=".( $on_page * $per_page ) ).'">'.$lang['Next'].'</a>';
		}

	}

	$page_string = $lang['Goto_page'].' '.$page_string;

	return $page_string;
}

//
// This does exactly what preg_quote() does in PHP 4-ish
// If you just need the 1-parameter preg_quote call, then don't bother using this.
//
function phpbb_preg_quote($str, $delimiter)
{
	$text = preg_quote($str);
	$text = str_replace($delimiter, '\\'.$delimiter, $text); //'
	return $text;
}

// Obtain list of naughty words and build preg style replacement arrays for use by the
// calling script, note that the vars are passed as references this just makes it easier
// to return both sets of arrays
//
function obtain_word_list(&$orig_word, &$replacement_word)
{
	global $db;
	//
	// Define censored word matches
	//
	$result = $db->sql_query("SELECT word, replacement FROM	 ".WORDS_TABLE);
	if ( $row = $db->sql_fetchrow($result) ) {
		do {
			$orig_word[] = '#\b('.str_replace('\*', '\w*?', phpbb_preg_quote($row['word'], '#')).')\b#i';
			$replacement_word[] = $row['replacement'];
		}
		while ( $row = $db->sql_fetchrow($result) );
	}
	return true;
}
//
// This is general replacement for die(), allows templated
// output in users (or default) language, etc.
//
// $msg_code can be one of these constants:
//
// GENERAL_MESSAGE : Use for any simple text message, eg. results
// of an operation, authorisation failures, etc.
//
// GENERAL ERROR : Use for any error which occurs _AFTER_ the
// common.php include and session code, ie. most errors in
// pages/functions
//
// CRITICAL_MESSAGE : Used when basic config data is available but
// a session may not exist, eg. banned users
//
// BB_CRITICAL_ERROR : Used when config data cannot be obtained, eg
// no database connection. Should _not_ be used in 99.5% of cases
//
function message_die($msg_code, $msg_text = '', $msg_title = '', $err_line = '', $err_file = '', $sql = '')
{
	$debug_text = null;
 global $db, $template, $cpgtpl, $board_config, $theme, $lang, $phpbb_root_path, $gen_simple_header, $images;
	global $userdata, $user_ip;

	if(defined('HAS_DIED')) {
		die("message_die() was called multiple times. This isn't supposed to happen. Was message_die() used in page_tail.php?");
	}

	define('HAS_DIED', 1);

	$sql_store = $sql;

	//
	// Get SQL error if we are debugging. Do this as soon as possible to prevent
	// subsequent queries from overwriting the status of sql_error()
	//
	if ( DEBUG && ( $msg_code == GENERAL_ERROR || $msg_code == BB_CRITICAL_ERROR ) ) {
		$sql_error = $db->sql_error();
		$debug_text = '';
		if ( $sql_error['message'] != '' ) {
			$debug_text .= '<br /><br />SQL Error : '.$sql_error['code'].' '.$sql_error['message'];
		}
		if ( $sql_store != '' ) {
			$debug_text .= "<br /><br />$sql_store";
		}
		if ( $err_line != '' && $err_file != '' ) {
			$debug_text .= '</br /><br />Line : '.$err_line.'<br />File : '.(is_admin() ? $err_file : basename($err_file));
		}
	}

	if( empty($userdata) && ( $msg_code == GENERAL_MESSAGE || $msg_code == GENERAL_ERROR ) ) {
		$userdata = session_pagestart($user_ip, PAGE_INDEX);
		init_userprefs($userdata);
	}

	//
	// If the header hasn't been output then do it
	//
	if (!defined('HEADER_INC') && $msg_code != BB_CRITICAL_ERROR) {
		if (empty($lang)) {
			if ( !empty($board_config['default_lang']) ) {
				include('language/'.$board_config['default_lang'].'/forums.php');
				//include('language/'.$board_config['default_lang'].'/Forums/lang_admin.php');
			} else {
				include('language/english/forums.php');
				//include('language/english/Forums/lang_admin.php');
			}
		}

		if (empty($theme)) {
			$theme = setup_style($board_config['default_style']);
		}

		//
		// Load the Page Header
		if ( !defined('IN_ADMIN') ) {
			$temp = preg_match('#<br \/>#m', $msg_text) ? explode('<br />', $msg_text) : explode('.', $msg_text);
			$paget_text = $temp[0];
			///$page_title = ' '._BC_DELIM.' ';
			$page_title = !empty($msg_title) ? strip_tags($msg_title) : strip_tags($paget_text);
			include('includes/phpBB/page_header.php');
		}
	}

	global $cpgdebugger;
	switch($msg_code)
	{
		case GENERAL_MESSAGE:
			if ( $msg_title == '' ) {
				$msg_title = (!empty($lang[$msg_text])) ? $lang[$msg_text] : $msg_text;
				$msg_title = ((empty($msg_title)) && (!empty($msg_text))) ? $msg_text : $lang['Information'];
			}
			//$cpgdebugger->handler(E_USER_WARNING, $debug_text.'<br />'.$msg_title.'<br />'.$msg_text, $err_file, $err_line)
			break;

		case CRITICAL_MESSAGE:
			if ( !empty($lang[$msg_text]) ) {
				$msg_text = $lang[$msg_text];
			}
			if ( $msg_title == '' ) { $msg_title = $lang['Critical_Information']; }
			$cpgdebugger->handler(E_USER_ERROR, $debug_text.'<br />'.$msg_title.'<br />'.$msg_text, $err_file, $err_line);
			return false;
			//break;

		case GENERAL_ERROR:
			if ( !empty($lang[$msg_text]) ) {
				$msg_text = $lang[$msg_text];
			}
			if ( $msg_text == '' )	{ $msg_text = $lang['An_error_occured']; }
			if ( $msg_title == '' ) { $msg_title = $lang['General_Error']; }
			$cpgdebugger->handler(E_USER_ERROR, $debug_text.'<br />'.$msg_title.'<br />'.$msg_text, $err_file, $err_line);
			return false;
			//break;

		case BB_CRITICAL_ERROR:
			//
			// Critical errors mean we cannot rely on _ANY_ DB information being
			// available so we're going to dump out a simple echo'd statement
			//
			include('language/english/forums.php');
			if ( $msg_text == '' )	{ $msg_text = $lang['A_critical_error']; }
			if ( $msg_title == '' ) { $msg_title = 'phpBB : <b>'.$lang['Critical_Error'].'</b>'; }
			$cpgdebugger->handler(E_USER_ERROR, $debug_text.'<br />'.$msg_title.'<br />'.$msg_text, $err_file, $err_line);
			return false;
			//break;
	}

	//
	// Add on DEBUG info if we've enabled debug mode and this is an error. This
	// prevents debug info being output for general messages should DEBUG be
	// set TRUE by accident (preventing confusion for the end user!)
	//
	if (DEBUG && ($msg_code == GENERAL_ERROR || $msg_code == BB_CRITICAL_ERROR)) {
		if ($debug_text != '') {
			$msg_text = $msg_text.'<br /><br /><b><u>DEBUG MODE</u></b>'.$debug_text;
		}
	}

	if ($msg_code != BB_CRITICAL_ERROR) {
		if (!empty($lang[$msg_text])) {
			$msg_text = $lang[$msg_text];
		}
		$template->assign_vars(array(
			'MESSAGE_TITLE' => $msg_title,
			'MESSAGE_TEXT' => $msg_text)
		);
		$template->set_filenames(array('body' => (!defined('IN_ADMIN')) ? 'forums/message_body.html' : 'forums/admin/admin_message_body.html'));

		if (!defined('IN_ADMIN')) {
			include("includes/phpBB/page_tail.php");
		} else {
			$template->display('body');
			$template->destroy();
		}
	} else {
		$cpgdebugger->handler(E_USER_ERROR, $debug_text.'<br />'.$msg_title.'<br />'.$msg_text, $err_file, $err_line);
		//trigger_error($debug_text.'<br />.'$msg_text,E_USER_ERROR, $err_line, $err_file);
		echo "<html>\n<body>\n".$msg_title."\n<br /><br />\n".$msg_text."</body>\n</html>";
	}
	return false;
}
