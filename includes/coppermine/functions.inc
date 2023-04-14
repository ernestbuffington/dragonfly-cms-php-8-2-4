<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify	   
   it under the terms of the GNU General Public License as published by	   
   the Free Software Foundation; either version 2 of the License, or		  
   (at your option) any later version.										
  **************************************************************************  
  Last modification notes:
  $Source: /cvs/html/includes/coppermine/functions.inc,v $
  $Revision: 9.46 $
  $Author: nanocaiordo $
  $Date: 2008/01/31 15:22:41 $
****************************************************************************/
global $THEME_DIR, $template, $block_name, $template_cpg_die, $template_msg_box;

function coppermine_block() {
	global $module_name, $cpg_dir;
	if ($module_name != $cpg_dir) return;
	global $db, $CONFIG;
	$cat = intval(isset($_GET['cat']) ? $_GET['cat'] : (isset($_POST['cat']) ? $_POST['cat'] : '0'));
	$content = '<!-- coppermine user block -->';
	$dot = '<b>&#8226;&nbsp;</b>';
	if (USER_CAN_CREATE_ALBUMS){
		$content .= $dot.'<a href="'.getlink('&amp;file=albmgr&amp;cat='.USER_GAL_CAT).'">' . ALBMGR_LNK . '</a><br />';
		$content .= $dot.'<a href="'.getlink('&amp;file=modifyalb').'">' . MODIFYALB_LNK . '</a><br />';
	}
	$content .= $dot.'<a href="'.getlink('&amp;file=profile').'">' . MY_PROF_LNK . '</a><br />';
	if (USER_CAN_CREATE_ALBUMS){
		$content .= $dot.'<a href="'.getlink("&amp;cat=".(FIRST_USER_CAT + USER_ID)).'" title="'.MY_GAL_TITLE.'">'.MY_GAL_LNK.'</a><br />';
	}
	if (USER_CAN_UPLOAD_PICTURES) {
		$content .= $dot.'<a href="'.getlink("&amp;file=upload").'" title="'.UPLOAD_PIC_TITLE.'">'.UPLOAD_PIC_LNK.'</a>';
	}
	if (GALLERY_ADMIN_MODE) {
		$content .= '<hr />';
		$num = $db->sql_count($CONFIG['TABLE_PICTURES'], "approved=0");
		$content .= $dot.'<a href="'.getlink('&amp;file=editpics&amp;mode=upload_approval').'">' . UPL_APP_LNK . '</a>: ' . $num[0] . '<br />' .
		            $dot.'<a href="'.getlink('&amp;file=searchnew').'">' . SEARCHNEW_LNK . '</a><br />' .
		            $dot.'<a href="'.getlink('&amp;file=reviewcom').'">' . COMMENTS_LNK . '</a><br />' .
		            $dot.'<a href="'.getlink('&amp;file=groupmgr').'">' . GROUPS_LNK . '</a><br />' .
		            $dot.'<a href="'.getlink('&amp;file=usermgr').'">' . USERS_LNK . '</a><br />' .
		            $dot.'<a href="'.getlink('&amp;file=albmgr&amp;cat='.$cat).'">' . ALBUMS_LNK . '</a><br />' .
		            $dot.'<a href="'.getlink('&amp;file=catmgr').'">' . CATEGORIES_LNK . '</a><br />';
		if (isset($_GET['album'])) {
			$content .= $dot.'<a href="'.getlink('&amp;file=editpics&amp;album='.$_GET['album']).'">' . EDIT_PICS . '</a><br />';
		}
		if (is_admin()) {
			$content .= "$dot<a href=\"".adminlink().'">' . CONFIG_LNK . "</a><br />";
		}		
	}
	return $content;
}

// Decode the user profile contained in a cookie
function user_get_profile()
{
	global $CONFIG, $USER, $SESS;
	$chars = array(4=>'0-9a-f',5=>'0-9a-v',6=>'0-9a-zA-Z\-,');
	$bits = (ini_get('session.hash_function')=='1')?160:128;
	$size = $bits / 4;
	$minsize = (int)ini_get('session.hash_bits_per_character');
	if ($minsize < 4) $minsize = 4;
	if ($minsize != 4) $size = ceil($bits/$minsize).",$size";
	$chars = $chars[$minsize];

	if (isset($_COOKIE[$CONFIG['cookie_name'] . '_data'])) {
		$USER = unserialize(base64_decode($_COOKIE[$CONFIG['cookie_name'] . '_data']));
	} 
	if (!isset($USER) || !is_array($USER)) { $USER = array(); }
	if (!isset($USER['ID']) || preg_match("#^[$chars]{{$size}}$#", $USER['ID'])) {
		$USER['ID'] = $SESS->sess_id;
	}
}

// check if user is member of group
function user_ingroup($group_id, &$usergroups) {
	if (!is_array($usergroups)) $usergroups = split(',', $usergroups);
	foreach($usergroups as $group) {
		if ($group == $group_id) return true;
	}
	return false;
}

/**
 * Database functions
 */
// get a table count
// cpg_tablecount("cpg_pictures", "count(*)")
// cpg_tablecount("cpg_pictures", "sum(hits)")
function cpg_tablecount($table, $type, $file='', $line='') {
	global $db;
	list($count) = $db->sql_ufetchrow("SELECT $type FROM $table",SQL_NUM,$file,$line);
	return $count;	
}
/**
 * Utilities functions
 */

// Replacement for the die function
function cpg_die($msg_code, $msg_text, $error_file, $error_line, $output_buffer = false, $header=false)
{
	global $CONFIG, $lang_cpg_die, $template_cpg_die,$CLASS;
	global $template; 
	// Simple output if theme file is not loaded
	if (!function_exists('starttable')) {
		trigger_error('Fatal error :<br />' . $msg_text,E_USER_ERROR);
		exit;
	} 
	$template_cpg_die = eval_tmplfile($template_cpg_die);
	//this shows error line numbers only to admin
	if (!USER_IS_ADMIN ||($CLASS['member']->demo)) template_extract_block($template_cpg_die, 'file_line');
	if (!$output_buffer && !$CONFIG['debug_mode']) template_extract_block($template_cpg_die, 'output_buffer');

	$params = array('{MESSAGE}' => $msg_text,
		'{FILE_TXT}' => FILE,
		'{FILE}' => $error_file,
		'{LINE_TXT}' => LINE,
		'{LINE}' => $error_line,
		'{OUTPUT_BUFFER}' => "",
		);
	if ($header) {
		pageheader($lang_cpg_die[$msg_code]);
	}
	global $CONFIG;
	global $template_header;
	require_once("header.php");
//	OpenTable();
	starttable(-1, $lang_cpg_die[$msg_code]);
	echo template_eval($template_cpg_die, $params);
	endtable();
	pagefooter();
}
// Function for writing a pageheader
function pageheader($section, $meta='',$time=5)
{
	global $CONFIG, $CPG_VERSION, $CPG_M_DIR, $template_header,$modheader;

//	header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
	global $USER, $MAIN_CFG;
	$data = base64_encode(serialize($USER));
	setcookie($CONFIG['cookie_name'] . '_data', $data, time() + 86400 * 30, $MAIN_CFG['cookie']['path'], $MAIN_CFG['cookie']['domain']);
	$TMPCONFIG = $CONFIG;	
	if ($meta != '') {
		$modheader .= url_refresh($meta,$time);
	}
	require_once("header.php");
	$CONFIG = $TMPCONFIG;

	OpenTable();

	$template_vars = array(
			'{LANG_DIR}' => _TEXT_DIR,
			'{TITLE}' => $CONFIG['gallery_name'].' - '.$section,
			'{CHARSET}' => $CONFIG['charset'] == 'language file' ? _CHARSET : $CONFIG['charset'],
			//'{META}' => $meta,
			'{GAL_NAME}' => $CONFIG['gallery_name'],
			'{GAL_DESCRIPTION}' => $CONFIG['gallery_description'],
			'{MAIN_MENU}' => ($meta == '' ? theme_main_menu():''),
			'{ADMIN_MENU}' => ''
	);
	//theme_admin_mode_menu()
	// coppercop added config var...
	//disabled temp
	echo template_eval($template_header, $template_vars);
}
// Function for writing a pagefooter
function pagefooter()
{
	global $template_footer,$CLASS;
	echo $template_footer;
	CloseTable();
	if (!$CLASS['member']->demo) { print_debug(); }
	require("footer.php");
}
// Display a localised date
function localised_date($timestamp = -1, $datefmt = '') // check later datefmt = ''
{
	return formatDateTime($timestamp, $datefmt);
}
// Function to create correct URLs for image name with space or exotic characters
function path2url($path)
{
	return str_replace("%2F", "/", rawurlencode($path));
} 
// Display a 'message box like' table
function msg_box($title, $msg_text, $button_text = "", $button_link = "", $width = "-1")
{
	global $template_msg_box;

	if (!$button_text) {
		template_extract_block($template_msg_box, 'button');
	} 
	$params = array('{MESSAGE}' => $msg_text,
		'{LINK}' => $button_link,
		'{TEXT}' => $button_text
	);

	starttable($width, $title);
	echo template_eval($template_msg_box, $params);
	endtable();
} 
function create_tabs($items, $curr_page, $total_pages, $template)
{
	global $CONFIG;

	if (function_exists('theme_create_tabs')) {
		theme_create_tabs($items, $curr_page, $total_pages, $template);
		return;
	} 
	$maxTab = $CONFIG['max_tabs'];
	$tabs = sprintf($template['left_text'], $items, $total_pages);
	if (($total_pages == 1)) return $tabs;
	$tabs .= $template['tab_header'];
	if ($curr_page == 1) {
		$tabs .= sprintf($template['active_tab'], 1);
	} else {
		$tabs .= sprintf($template['inactive_tab'], 1, 1);
	} 
	if ($total_pages > $maxTab) {
		$start = max(2, $curr_page - floor(($maxTab -2) / 2));
		$start = min($start, $total_pages - $maxTab + 2);
		$end = $start + $maxTab -3;
	} else {
		$start = 2;
		$end = $total_pages-1;
	} 
	for ($page = $start ; $page <= $end; $page++) {
		if ($page == $curr_page) {
			$tabs .= sprintf($template['active_tab'], $page);
		} else {
			$tabs .= sprintf($template['inactive_tab'], $page, $page);
		} 
	} 
	if ($total_pages > 1) {
		if ($curr_page == $total_pages) {
			$tabs .= sprintf($template['active_tab'], $total_pages);
		} else {
			$tabs .= sprintf($template['inactive_tab'], $total_pages, $total_pages);
		} 
	} 
	return $tabs . $template['tab_trailer'];
} 

// Allow the use of a limited set of phpBB bb codes in albums and image descriptions
// Taken from phpBB code
define ('LOC', 'YToyOntzOjE6ImwiO3M6OToie0dBTExFUll9IjtzOjE6InMiO3M6Mjk1OiI8ZGl2IGNsYXNzPSJmb290ZXIiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJwYWRkaW5nLXRvcDogMTBweDsiPlBvd2VyZWQgYnkgPGEgaHJlZj0iaHR0cDovL2NvcHBlcm1pbmUuc2YubmV0LyIgdGFyZ2V0PSJfYmxhbmsiPkNvcHBlcm1pbmUgUGhvdG8gR2FsbGVyeTwvYT4gdkNQR19WRVJTSU9OLU51a2U8YnI+Zm9yIHBocE51a2UgYW5kIHBvc3ROdWtlIGJ5IDxhIGhyZWY9Imh0dHA6Ly9jb3BwZXJtaW5lLmZpbmRoZXJlLm9yZy8iIHRhcmdldD0iX2JsYW5rIj5Db3BwZXJtaW5lIE51a2UgRGV2IFRlYW08L2E+PC9kaXY+Ijt9');

// function added for blocks takes a string and shortens it for display
// use: truncate_stringblocks($var,int)
function truncate_stringblocks($str, $maxlength = 20)
{
	if (strlen($str) > $maxlength) {
		return substr($str, 0, $maxlength) . " ...";
	} else {
		return $str;
	} 
} 
/**
 * Template functions
 */
function eval_tmplfile($file, $load = true)
{
	global $THEME_DIR, $CPG_M_DIR, $CPG_URL;
	if ($load)
		$thefile = implode("", file($THEME_DIR . '/' . $file));
	else
		$thefile = $file;
	$var = eval("return '" . preg_replace("/'/", "\'", $thefile) . "';");
	if (!$var) {
		cpg_error("There occured an error while processing $THEME_DIR/$file");
	}
	return $var;
} 
// Load and parse the template.html file
function load_template()
{
	global $THEME_DIR, $CONFIG, $template_header, $template_footer, $CPG_VERSION;
	if (file_exists('template.html')) {
		$template_file = 'template.html';
	} elseif (file_exists($THEME_DIR . '/template.html')) {
		$template_file = $THEME_DIR . '/template.html';
	} if (!isset($template_file)) {
		trigger_error("<b>Coppermine critical error</b>:<br />Unable to load template file $THEME_DIR/template.html!</b>", E_USER_ERROR);
	}
	$template = eval_tmplfile('template.html');
	$pos = strpos($template, '{GALLERY}');
	$template_header = substr($template, 0, $pos);
	$template_footer = substr($template, $pos);
	$template_footer = str_replace('{GALLERY}', '', $template_footer);
}
// Eval a template (substitute vars with values)
function template_eval(&$template, &$vars)
{
	return strtr($template, $vars);
} 
// Extract and return block '$block_name' from the template, the block is replaced by $subst
function template_extract_block(&$template, $block_name, $subst = '')
{
	global $THEME_DIR;
	$pattern = "#(<!-- BEGIN $block_name -->)(.*?)(<!-- END $block_name -->)#s";
	if (!preg_match($pattern, $template, $matches)) {
		die('<b>Theme:'.$THEME_DIR.' Template error<b><br />Failed to find block \'' . $block_name . '\'(' . htmlprepare($pattern) . ') in :<br /><pre>' . htmlprepare($template) . '</pre>');
	} 
	$template = str_replace($matches[1] . $matches[2] . $matches[3], $subst, $template);
	return $matches[2];
} 

/**
 * Functions for album/picture management
 */
function get_cat_content(&$list, $cat, $title)
{
	//if (!GALLERY_ADMIN_MODE) $upload = "AND uploads = 1";
	$upload = (!GALLERY_ADMIN_MODE) ? "AND uploads = 1" : '';
	global $db, $CONFIG, $module_name;
	$albums = $db->sql_query("SELECT aid, title, visibility FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = " . $cat . " $upload ORDER BY title");
	$ug = USER_IN_GROUPS;
	while ($album = $db->sql_fetchrow($albums)) {
		if(can_admin($module_name) || user_ingroup($album['visibility'], $ug) || ($album['visibility']=='0')){
			$rowset['aid'] = $album['aid'];
			$rowset['title'] = $title . $album['title'];
			$list[] = $rowset;
		}
	}
	$db->sql_freeresult($albums);

	$cats = $db->sql_query("SELECT cid, catname FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent=".$cat." ORDER BY catname");
	while ($subcat = $db->sql_fetchrow($cats)) {
		$tmptitle = $title . $subcat['catname'] . " > ";
		get_cat_content($list, $subcat['cid'], $tmptitle);
	}
	$db->sql_freeresult($cats);
}
// get a full list of albums to use
function get_albumlist($user_id=0)
{
	if (!GALLERY_ADMIN_MODE) $upload = 'AND uploads = 1';
	global $db, $CONFIG;

	$select = array();
	get_cat_content($select, 0, '');

	if (GALLERY_ADMIN_MODE){
		$sql = "SELECT aid, CONCAT('(', username, ') ', title) AS title FROM {$CONFIG['TABLE_ALBUMS']} AS a "
			."INNER JOIN {$CONFIG['TABLE_USERS']} AS u ON user_id = (category - " . FIRST_USER_CAT . ') '
			.'ORDER BY title';
	} elseif (USER_CAN_CREATE_ALBUMS) {
		$sql = "SELECT aid, CONCAT('* ', title) AS title FROM {$CONFIG['TABLE_ALBUMS']} WHERE category='" . (FIRST_USER_CAT + $user_id) . "' ORDER BY title";
	}
	if (GALLERY_ADMIN_MODE || USER_CAN_CREATE_ALBUMS){
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result)) $select[] = $row;
		$db->sql_freeresult($result);
	}
	return $select;
}

// Retrieve the data for a picture or a set of picture
function get_pic_data($meta, $album, &$count, &$album_name, $limit1 = -1, $limit2 = -1, $set_caption = true)
{
	require_once("includes/nbbcode.php");
	global $USER, $CONFIG, $THEME_DIR, $FAVPICS;
	global $thisalbum, $cat, $userinfo;
	global $lang_meta_album_names, $CPG_M_DIR, $CPG_PROFILE_URL;
	global $db;
	$sort_array = array('na' => 'filename ASC', 'nd' => 'filename DESC', 'ta' => 'title ASC', 'td' => 'title DESC', 'da' => 'pid ASC', 'dd' => 'pid DESC', 'ra' => 'pic_rating ASC', 'rd' => 'pic_rating DESC', 'sd' => 'max(pwidth,pheight) DESC', 'sa' => 'max(pwidth,pheight) ASC'); //'sa' => 'max(pwidth,pheight) DESC'
	$sort_code = isset($USER['sort'])? $USER['sort'] : $CONFIG['default_sort_order'];
	$sort_order = isset($sort_array[$sort_code]) ? $sort_array[$sort_code] : $sort_array[$CONFIG['default_sort_order']];
	$cat = (isset($_GET['cat']) && is_numeric($_GET['cat']))  ?  $_GET['cat']  :  0;
  $limit = ($limit1 >= 0) ? 'LIMIT '. intval($limit1) : '';
  $limit .= (($limit1 >= 0) && ($limit2 > 0)) ? ','. intval($limit2) : '';
  /*$limit1 = intval($limit1);
	$limit2 = intval($limit2);
	$limit = ($limit1 >= 0) ? ' LIMIT ' . $limit1 : '';
	$limit .= ($limit2 > 0) ? ' ,' . $limit2 : '';
*/
	if ($limit2 == 1) {
		$select_columns = 'p.*';
	} else {
		$select_columns = 'pid, filepath, filename, p.title, keywords, url_prefix, filesize, pwidth, pheight, ctime, p.aid';
	}
	// Regular albums
	if (is_numeric($album) && $meta == '') {
		$album_name = get_album_name($album);
		$result = $db->sql_query("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} as p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (a.aid = $album AND ".VIS_GROUPS.") WHERE p.aid='$album' AND approved=1");
		$nbEnr = $db->sql_fetchrow($result);
		$count = $nbEnr[0];
		$db->sql_freeresult($result);
		if ($select_columns != '*') {
			$select_columns .= ', p.title, caption, hits, description';
		}
		$result = $db->sql_query("SELECT $select_columns from {$CONFIG['TABLE_PICTURES']} as p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (a.aid = $album AND ".VIS_GROUPS.") WHERE p.aid='$album' AND approved=1 ORDER BY $sort_order $limit");
		$rowset = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		// Set picture caption
		if ($set_caption) foreach ($rowset as $key => $row) {
			$caption = ($rowset[$key]['title'] || $rowset[$key]['hits']) ? "<span class=\"thumb_title\">" . $rowset[$key]['title'] . (($rowset[$key]['title'])?"-":"") . sprintf(N_VIEWS, $rowset[$key]['hits']) . "</span>" : '';
			if ($CONFIG['caption_in_thumbview']) {
				$caption .= $rowset[$key]['caption'] ? "<span class=\"thumb_caption\">" . decode_bbcode($rowset[$key]['caption']) . "</span>" : '';
			}
			if ($CONFIG['display_comment_count']) {
				$comments_nr = count_pic_comments($row['pid']);
				if ($comments_nr > 0) $caption .= "<span class=\"thumb_num_comments\">" . sprintf(N_COMMENTS, $comments_nr) . "</span>";
			}
			$rowset[$key]['caption_text'] = $caption;
		}
		return $rowset;
	}
	// Meta albums
	$album_name = $lang_meta_album_names[$meta];
	// Limits pictures to the amount shown
	switch ($meta) {
		// Last comments
		case 'lastcom': 
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) ORDER BY msg_date DESC", SQL_NUM);
			if (!$count) return array();
			if ($select_columns == '*') {
				$select_columns = 'p.*';
			} else {
				$select_columns = str_replace('pid', 'p.pid', $select_columns) . ', msg_id, author_id, msg_author, msg_date, msg_body, p.aid';
			}
			$rowset = $db->sql_ufetchrowset("SELECT $select_columns FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) ORDER BY msg_date DESC $limit", SQL_BOTH);
			if ($set_caption) foreach ($rowset as $key => $row) {
				// FIX PostNuke
				if ($row['author_id']) {
					$user_link = '<a href ="'.$CPG_PROFILE_URL.$row['author_id'].'">'.$row['msg_author'].'</a>';
				} else {
					$user_link = $row['msg_author'];
				} 
				$msg_body = strlen($row['msg_body']) > 50 ? substr($row['msg_body'], 0, 50) . "...": $row['msg_body'];
				// if ($CONFIG['enable_smilies']) $msg_body = process_smilies($msg_body);
				$caption = '<span class="thumb_title">' . $user_link . '</span>' . '<span class="thumb_caption">' . localised_date($row['msg_date'], LASTCOM_DATE_FMT) . '</span>' . '<span class="thumb_caption">' . $msg_body . '</span>';
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Last comments by a specific user
		case 'lastcomby':
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE author_id = " .USER_ID. " GROUP BY p.pid ORDER BY msg_date DESC", SQL_NUM);
			if (!$count) return array();
			if ($select_columns == '*') {
				$select_columns = 'p.*';
			} else {
				$select_columns = str_replace('pid', 'c.pid', $select_columns) . ', msg_id, author_id, msg_author, msg_date, msg_body, p.aid';
			} 
			$rowset = $db->sql_ufetchrowset("SELECT $select_columns FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE author_id = " .USER_ID. " GROUP BY p.pid ORDER BY msg_date DESC $limit", SQL_BOTH);
			if ($set_caption) foreach ($rowset as $key => $row) {
				require_once("includes/nbbcode.php");
				$comment_body = make_clickable($row['msg_body']);
				$user_link = $row['msg_author'];
				$caption = '<span class="thumb_title">' . $user_link . '</span>' . '<span class="thumb_caption">' . localised_date($row['msg_date'], LASTCOM_DATE_FMT) . '</span>' . '<span class="thumb_caption">' . $comment_body . '</span>';
				$rowset[$key]['caption_text'] = $caption;
				} 
			return $rowset;

		// Last uploads
		case 'lastup': 
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 ORDER BY p.pid DESC", SQL_NUM);
			if (!$count) return array();
			if ($select_columns == '*') {
				$select_columns = 'p.*';
			} else {
				$select_columns = str_replace('pid', 'p.pid', $select_columns) . ', owner_id, owner_name';
			}
			$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 ORDER BY p.pid DESC $limit");
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			if ($set_caption) foreach ($rowset as $key => $row) {
				if ($row['owner_id'] && $row['owner_name']) {
					$user_link = '<span class="thumb_title"><a href="$CPG_PROFILE_URL">' . $row['msg_author'] . '</a></span>';
				} else {
					$user_link = '';
				} 
				$caption = $user_link . '<span class="thumb_caption">' . localised_date($row['ctime'], LASTUP_DATE_FMT) . '</span>';
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Last uploads by a specific user
		case 'lastupby':
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']} WHERE owner_id = '{$userinfo['user_id']}' AND approved=1 GROUP BY pid", SQL_NUM);
			if (!$count) return array();
			if ($select_columns != '*') $select_columns .= ', owner_id, owner_name, p.aid';
			$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE owner_id = '{$userinfo['user_id']}' AND approved=1 ORDER BY p.pid DESC $limit");
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			if ($set_caption) foreach ($rowset as $key => $row) {
				if ($row['owner_id'] && $row['owner_name']) {
					$user_link = '<span class="thumb_title"><a href ="$CPG_PROFILE_URL'.$row['owner_id'].'">'.$row['msg_author'].'</a></span>';
				} else {
					$user_link = '';
				} 
				$caption = $user_link . '<span class="thumb_caption">' . localised_date($row['ctime'], LASTUP_DATE_FMT) . '</span>';
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Most viewed pictures
		case 'topn':
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND hits > 0", SQL_NUM);
			if (!$count) return array();
			if ($select_columns == '*') {
				$select_columns = 'p.*';
			} else {
				$select_columns .= ', p.hits, p.aid';
			}
			$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND p.hits > 0 ORDER BY p.hits DESC, p.filename ASC $limit");
			//$result2 = $db->sql_query("SELECT FOUND_ROWS()");
			$rowset = $db->sql_fetchrowset($result, SQL_ASSOC);
			//$nbEnr = $db->sql_fetchrow($result2);
			//$count = $nbEnr[0];
			$db->sql_freeresult($result);
			if ($set_caption) foreach ($rowset as $key => $row) {
				$caption = "<span class=\"thumb_caption\">" . sprintf(N_VIEWS, $row['hits']) . '</span>';
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Top rated pictures
		case 'toprated':
			list($count) = $db->sql_ufetchrow("SELECT count(*) FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND p.votes >= {$CONFIG['min_votes_for_rating']}", SQL_NUM);
			if (!$count) return array();
			if ($select_columns == '*') {
				$select_columns = 'p.*';
			} else {
				$select_columns .= ', pic_rating, p.votes AS votes';
			}
			$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND p.votes >= '{$CONFIG['min_votes_for_rating']}' ORDER BY ROUND((pic_rating+1)/2000,2) DESC, p.votes DESC, filename $limit");
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			if ($set_caption) foreach ($rowset as $key => $row) {
				if (defined('THEME_HAS_RATING_GRAPHICS')) {
					$theme_prefix = "$CONFIG[theme]/";
				} else {
					$theme_prefix = '';
				} 
				$caption = "<img src=\"" . $CPG_M_DIR . "/" . $theme_prefix . "images/rating" . round($row['pic_rating'] / 2000) . ".gif\" alt=\"".sprintf(RATING, round($row['pic_rating'] / 2000),$row['votes'])."\"  border=\"0\" />" . "<br />" . round($row['pic_rating'] / 2000, 2) . "/5 ";
				$caption .= sprintf(N_VOTES, $row['votes']);
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Last viewed pictures
		case 'lasthits':
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND $thisalbum", SQL_NUM);
			if (!$count) return array();
			// count the number of pics to show
			if ($select_columns != '*') $select_columns .= ', mtime, aid';
			$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND $thisalbum GROUP BY p.pid ORDER BY mtime DESC $limit");
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			if ($set_caption) foreach ($rowset as $key => $row) {
				$caption = "<span class=\"thumb_caption\">" . localised_date($row['mtime'], LASTHIT_DATE_FMT) . '</span>';
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Random pictures
		case 'random':
			list($pic_count) = $db->sql_ufetchrow("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 GROUP BY p.pid", SQL_NUM);
			if (!$pic_count) return array();
			// count the number of pics to show
			if ($select_columns != '*') $select_columns .= ', p.aid';
			// if we have more than 1000 pictures, we limit the number of picture returned
			// by the SELECT statement as ORDER BY RAND() is time consuming
			if ($pic_count > 1000) {
//				$result = $db->sql_query("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND $thisalbum GROUP BY p.pid",false);
//				$nbEnr = $db->sql_fetchrow($result);
				$total_count = $nbEnr[0];
				$db->sql_freeresult($result);
				$granularity = floor($total_count / RANDPOS_MAX_PIC);
				$cor_gran = ceil($total_count / $pic_count);
				srand(time());
				for ($i = 1; $i <= $cor_gran; $i++) $random_num_set = rand(0, $granularity) . ', ';
				$random_num_set = substr($random_num_set, 0, -2);
				$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} ON (".VIS_GROUPS." AND $thisalbum) WHERE  randpos IN ($random_num_set) AND approved = 1 GROUP BY p.pid, p.filepath, p.filename, p.title, p.keywords, p.url_prefix, p.filesize, p.pwidth, p.pheight, p.ctime, p.aid ORDER BY RAND() $limit");
			} else {
				$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 GROUP BY p.pid, p.filepath, p.filename, p.title, p.keywords, p.url_prefix, p.filesize, p.pwidth, p.pheight, p.ctime, p.aid ORDER BY RAND() $limit");
			} 
			$rowset = $db->sql_fetchrowset($result);
/*			$rowset = array();
			while ($row = $db->sql_fetchrow($result)) {
				$row['caption_text'] = '';
				$rowset[$row['pid']] = $row;
			}*/
			$db->sql_freeresult($result);
			return $rowset;

		//All pictures by member
		case 'allby':
			$m_id = (isset($_GET['member_id'])) ? intval($_GET['member_id']) : 1;
			$query = "{$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE owner_id = '$m_id' AND  approved=1 GROUP BY p.pid ORDER BY p.pid DESC";	  
			list($count) = $db->sql_ufetchrow("SELECT * FROM $query", SQL_NUM);
			if (!$count) return array();
			if ($select_columns != '*') $select_columns .= ', owner_id, owner_name, p.aid';
			$result = $db->sql_query("SELECT $select_columns FROM $query $limit");
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			return $rowset;

		// Search results
		case 'search':
			if (isset($_GET['search'])) {
				$USER['search'] = Fix_Quotes($_GET['search']);
				if (isset($_GET['type']) && $_GET['type'] == 'full') {
				   $USER['search'] = '###' . $USER['search'];
				} 
			} elseif (isset($_POST['search'])) {
				$USER['search'] = Fix_Quotes($_POST['search']);
				if (isset($_POST['type']) && $_POST['type'] == 'full') {
					$USER['search'] = '###' . $USER['search'];
				}
			}
			if (isset($USER['search'])) {
				$search_string = Fix_Quotes($USER['search']);
			} else {
				cpg_die(_CRITICAL_ERROR, 'The searchstring is empty', __FILE__, __LINE__);
				$search_string = '';
			}

			if (substr($search_string, 0, 3) == '###') {
				$query_all = 1;
				$search_string = substr($search_string, 3);
			} else {
				$query_all = 0;
			} 
			$album_name .= ' - "' . htmlprepare($search_string) . '"';

			require(CORE_PATH.'coppermine/search.inc');
			$rowset = search_pics($search_string, $select_columns, $query_all, $limit, $pic_count, $set_caption);
			$count = $pic_count;
			return $rowset;

		// Last albums to which uploads
		case 'lastalb':
			list($count) = $db->sql_ufetchrow("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved=1 AND $thisalbum", SQL_NUM);
			if (!$count) return array();
			// query not compatible with postgresql ?
			$result = $db->sql_query("SELECT {$select_columns}, a.title AS title, a.aid AS aid FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved = 1 GROUP BY a.aid ORDER BY p.ctime DESC $limit");
			//$result = $db->sql_query("SELECT *, a.title AS title, a.aid AS aid FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND $thisalbum) WHERE approved = 1 GROUP BY p.aid, p.pid, p.filepath, p.filename, p.filesize, p.total_filesize, p.pwidth, p.pheight, p.hits, p.mtime, p.ctime, p.owner_id, p.owner_name, p.pic_rating, p.votes, p.title, p.caption, p.keywords, p.approved, p.user1, p.user2, p.user3, p.user4, p.url_prefix, p.randpos, p.pic_raw_ip, p.pic_hdr_ip, a.aid, a.title ORDER BY p.ctime DESC $limit");
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			if ($set_caption) foreach ($rowset as $key => $row) {
				$caption = "<span class=\"thumb_caption\">" . $row['title'] . " - " . localised_date($row['ctime'], LASTUP_DATE_FMT) . '</span>';
				$rowset[$key]['caption_text'] = $caption;
			} 
			return $rowset;

		// Favourite Pictures
		case 'favpics':
			if (count($FAVPICS) > 0) {
				$count = count($FAVPICS);//Akamu 5/27/2005 12:44PM http://dragonflycms.org/Projects/bugs/id=288.html
				$favs = implode(",", $FAVPICS);
				/* removed Akamu 5/27/2005 12:44PM http://dragonflycms.org/Projects/bugs/id=288.html
				list($count) = $db->sql_ufetchrow("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} WHERE approved = 1 AND pid IN ($favs) GROUP BY pid", SQL_NUM, __FILE__, __LINE__);
				if (!$count) return array();
				*/
				$select_columns = '*';
				$result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p WHERE approved = 1 AND pid IN ($favs) GROUP BY pid, p.aid, p.filepath, p.filename, p.filesize, p.total_filesize, p.pwidth, p.pheight, p.hits, p.mtime, p.ctime, p.owner_id, p.owner_name, p.pic_rating, p.votes, p.title, p.caption, p.keywords, p.approved, p.user1, p.user2, p.user3, p.user4, p.url_prefix, p.randpos, p.pic_raw_ip, p.pic_hdr_ip $limit");
				$rowset = $db->sql_fetchrowset($result);
				$db->sql_freeresult($result);
				if ($set_caption) foreach ($rowset as $key => $row) {
					$caption = $rowset[$key]['title'] ? "<span class=\"thumb_caption\">" . ($rowset[$key]['title']) . "</span>" : '';
					$rowset[$key]['caption_text'] = $caption;
				} 
				return $rowset;
			}
			else return null;

		// Invalid meta album
		default :
			cpg_die(_ERROR, NON_EXIST_AP.' '.$_GET['meta'].' '.$_POST['meta'].$meta, __FILE__, __LINE__);
	} 
} // End of get_pic_data
// Get the name of an album
function get_album_name($aid)
{
	global $db,$CONFIG;
	//global $lang_errors;
	$result = $db->sql_query("SELECT title from {$CONFIG['TABLE_ALBUMS']} WHERE aid='$aid'");
	$count = $db->sql_numrows($result);
	if ($count > 0) {
		$row = $db->sql_fetchrow($result);
		return $row['title'];
	} else {
		url_redirect('index.php?name=coppermine');
		//cpg_die(_ERROR,NON_EXIST_AP.' '.$aid, __FILE__, __LINE__);
	} 
} 
// Return the name of a user
// obsolete function ?
function get_username($uid)
{
	$uid =intval($uid);
	$data = getusrdata($uid);
	//$data = getusrdata($uid, 'username');
	return $data['username'];
}
// Return the total number of comments for a certain picture
function count_pic_comments($pid, $skip = 0)
{
	global $db, $CONFIG;
	$result = $db->sql_query("SELECT COUNT(*) FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid=$pid and msg_id!=$skip");
	list($count) = $db->sql_fetchrow($result); 
	$db->sql_freeresult($result);
	return $count;
} 
// Add 1 everytime a picture is viewed.
function add_hit($pid)
{
	global $CONFIG,$db;
	$db->sql_query("UPDATE {$CONFIG['TABLE_PICTURES']} SET hits=hits+1 WHERE pid='$pid'");
	//$db->sql_freeresult($result);
} 

// Build the breadcrumb array
function set_breadcrumb($lasturl = 0)
{
	global $db, $CONFIG, $CPG_M_URL, $album, $cat, $lang_meta_album_names,$pagetitle;
	$breadcrumb = array();
	$aid = $album;
	if ($cat < 0 && $cat != -$aid) {
		$aid = -$cat;
		$lasturl = 1;
	}
	if (is_numeric($aid) && $aid > 0) {
		$result = $db->sql_query("SELECT aid AS id, title, category AS parent FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = ".$aid);
		if ($db->sql_numrows($result)) {
			$row = $db->sql_fetchrow($result);
			$row['last'] = !$lasturl;
			$row['album'] = 1;
			$breadcrumb[] = $row;
			$db->sql_freeresult($result);
		}
	}
	else if (is_numeric($cat) && $cat > 0) {
		if ($cat > FIRST_USER_CAT) {
			$row['title'] = getusrdata($cat - FIRST_USER_CAT, 'username');
			$row['title'] = $row['title']['username'];
			if (!empty($row['title'])) {
				$row['id'] = $cat;
				$row['parent'] = 1;
				$row['last'] = !$lasturl;
				$breadcrumb[] = $row;
			} else {
				unset($row['title']);
			}
		}
		else {
			foreach (get_categories_data() as $row) {
				if ($row['cid'] != $cat) continue;
				$row['id'] = $cat;
				$row['title'] = $row['catname'];
				$row['last'] = !$lasturl;
				unset($row['cid'], $row['catname'], $row['description']);
				$breadcrumb[] = $row;
			}
		}
	}
	get_breadcrumb($breadcrumb);
	$navigation = '<a class="statlink" id="statlink" href="'.getlink("").'">' . $CONFIG['gallery_name'] . '</a> ';
	foreach($breadcrumb as $crumb) {
		if (isset($crumb['last'])) {
			$navigation .= ' '._BC_DELIM.' '.$crumb['title'];
		} elseif (isset($crumb['album'])) {
			$navigation .= _BC_DELIM.' <a class="statlink" id="statlink" href="'.getlink('&file=thumbnails&album='.$crumb['id']).'">'.$crumb['title'].'</a>';
		} else {
			$navigation .= _BC_DELIM.' <a class="statlink" id="statlink" href="'.getlink("&amp;cat=".$crumb['id']).'">'.$crumb['title'].'</a>';
		}
	}
	if (isset($_GET['meta']) && ($_GET['meta']!='')) {
	   $navigation .= " "._BC_DELIM." ".$lang_meta_album_names[$_GET['meta']];
	}
	if ($navigation == "") $navigation = _BC_DELIM." Coppermine";
	$dummy = array();
	theme_display_breadcrumb($navigation, $dummy);
}

// Get the breadcrumb array
function get_breadcrumb(&$breadcrumb)
{
	global $db, $CONFIG;
	if (!$breadcrumb) return;
	$last = count($breadcrumb);
	if ($breadcrumb[0]['parent'] == 0 || !is_numeric($breadcrumb[0]['parent'])) return;
	if ($breadcrumb[$last-1]['parent'] > FIRST_USER_CAT) {
		$cat = $breadcrumb[$last-1]['parent'];
		$result = $db->sql_query("SELECT username AS title FROM {$CONFIG['TABLE_USERS']} WHERE user_id = " . ($cat - FIRST_USER_CAT));
		if ($db->sql_numrows($result)) {
			$row = $db->sql_fetchrow($result);
			$row['id'] = $cat;
			$row['parent'] = 1;
			$breadcrumb[] = $row;
			$db->sql_freeresult($result);
			get_breadcrumb($breadcrumb);
		}
		return;
	}
	foreach (get_categories_data() as $row) {
		if ($row['cid'] != $breadcrumb[$last-1]['parent']) continue;
		$row['id'] = $row['cid'];
		$row['title'] = $row['catname'];
		unset($row['cid'], $row['catname'], $row['description']);
		$breadcrumb[] = $row;
		if ($row['parent'] > 0 && is_numeric($row['parent'])) get_breadcrumb($breadcrumb);
		else {
			$breadcrumb = array_reverse($breadcrumb);
			return;
		}
	}
}

// Compute image geometry based on max width / height
function compute_img_size($width, $height, $max)
{
	global $CONFIG;
	$thumb_use = $CONFIG['thumb_use'];
	if ($thumb_use == 'ht') {
		$ratio = $height / $max;
	} elseif ($thumb_use == 'wd') {
		$ratio = $width / $max;
	} else {
		$ratio = max($width, $height) / $max;
	} 
	if ($ratio > 1.0) {
		$image_size['reduced'] = true;
	} 
	$ratio = max($ratio, 1.0);
	$image_size['width'] = ceil($width / $ratio);
	$image_size['height'] = ceil($height / $ratio);
	$image_size['geom'] = 'width="' . $image_size['width'] . '" height="' . $image_size['height'] . '"';

	return $image_size;
} 
function display_film_strip($meta, $album, $cat, $pos)
{
	global $CONFIG, $AUTHORIZED;
	global $album_date_fmt,  $lang_byte_units;
	$max_item = $CONFIG['max_film_strip_items']; 
	$thumb_per_page = $max_item * 2;
	$l_limit = max(0, $pos - $CONFIG['max_film_strip_items']);
	$new_pos = max(0, $pos - $l_limit);
	$pic_data = get_pic_data($meta, $album, $thumb_count, $album_name, $l_limit, $thumb_per_page);
	if (count($pic_data) < $max_item) {
		$max_item = count($pic_data);
	} 
	$lower_limit = 3;
	if (!isset($pic_data[$new_pos + 1])) {
		$lower_limit = $new_pos - $max_item + 1;
	} else if (!isset($pic_data[$new_pos + 2])) {
		$lower_limit = $new_pos - $max_item + 2;
	} else if (!isset($pic_data[$new_pos-1])) {
		$lower_limit = $new_pos;
	} else {
		$hf = $max_item / 2;
		$ihf = (int)($max_item / 2);
		if ($new_pos > $hf) {
			$lower_limit = $new_pos - $ihf;
		} elseif ($new_pos < $hf) {
			$lower_limit = 0;
		} 
	} 
	$pic_data = array_slice($pic_data, $lower_limit, $max_item);
	$i = $l_limit;
	if (count($pic_data) > 0) {
		$meta_link = ($meta != '') ? '&amp;meta='.$meta : '';
		if (is_numeric($album) && $meta != 'random') {
			$alb_link = '&amp;album='.$album;
		} else {
			$alb_link = '&amp;cat='.(isset($cat) ? $cat : '0');
		}
		foreach ($pic_data as $key => $row) {
			$hi = (($pos == ($i + $lower_limit)) ? '1': '');
			$i++;

			$image_size = compute_img_size($row['pwidth'], $row['pheight'], $CONFIG['thumb_width']);

			if ($CONFIG['seo_alts'] == 0) {
				$pic_title = FILENAME . $row['filename'] . "\n" . FILESIZE . ($row['filesize'] >> 10) . $lang_byte_units[1] . "\n" . DIMENSIONS . $row['pwidth'] . "x" . $row['pheight'] . "\n" . DATE_ADDED . localised_date($row['ctime'], ALBUM_DATE_FMT);
			} else {
				if ($row['title'] != '') {
					$pic_title = $row['title'];
					if ($row['keywords'] != '') {
						$pic_title .= "\n";
						$pic_title .= $row['keywords'];
					} 
				} elseif ($row['keywords'] != '') {
					$pic_title = $row['keywords'];
				} else {
					$pic_title = substr($row['filename'], 0, -4);
				} 
			} 
			if ($meta == 'random') {
				$thumb_list[$i]['pos'] = $row['pid'];
			} else {
				$p = $i - 1 + $lower_limit;
				$p = ($p < 0 ? 0 : $p);
				$thumb_list[$i]['pos'] = $key < 0 ? $key : $p; 
			}
			if ($CONFIG['seo_alts'] == 0) {
				$thumb_list[$i]['image'] = "<img src=\"" . get_pic_url($row, 'thumb') . "\" class=\"image\" {$image_size['geom']} border=\"0\" alt=\"{$row['filename']}\" title=\"$pic_title\" />";
			} else {
				$thumb_list[$i]['image'] = "<img src=\"" . get_pic_url($row, 'thumb') . "\" class=\"image\" {$image_size['geom']} border=\"0\" alt=\"$pic_title\" title=\"$pic_title\" />";
			} 
			$thumb_list[$i]['caption'] = isset($row['caption_text']) ? $row['caption_text'] : '';
			$thumb_list[$i]['admin_menu'] = '';
			$thumb_list[$i]['url'] = getlink("&amp;file=displayimage$meta_link$alb_link&amp;pos=".$thumb_list[$i]['pos']);
		}
		return theme_display_film_strip($thumb_list);
	} else {
		//theme_no_img_to_display($album_name);
		trigger_error('film_strip '.NO_IMG_TO_DISPLAY.' '.$album_name,E_USER_WARNING);
	}
} //end display filmstrip

// Prints thumbnails of pictures in an album
function display_thumbnails($meta, $album, $cat, $page, $thumbcols, $thumbrows, $display_tabs)
{
	global $CONFIG, $AUTHORIZED;
	global $album_date_fmt,  $lang_byte_units;
	$thumb_per_page = $thumbcols * $thumbrows;
	$lower_limit = ($page-1) * $thumb_per_page;
	$pic_data = get_pic_data($meta, $album, $thumb_count, $album_name, $lower_limit, $thumb_per_page);
	$total_pages = ceil($thumb_count / $thumb_per_page);
	$i = 0;
	
	if(!isset($pic_data))
	$pic_data = 0;
	
	if ($pic_data > 0) {
		$meta_link = ($meta != '') ? '&amp;meta='.$meta : '';
		if (is_numeric($album)) {
			$alb_link = '&amp;album='.$album;
		} else {
			$alb_link = '&amp;cat='.(isset($cat) ? $cat : '0');
		}
		$thissort = isset($_GET['sort']) ? "sort=".$_GET['sort'] : "sort=".$CONFIG['default_sort_order'];
		$page_link = getlink('&amp;file=thumbnails'.$alb_link.$meta_link."&amp;page=%d&amp;$thissort");
		foreach ($pic_data as $key => $row) {
			$i++;
			$image_size = compute_img_size($row['pwidth'], $row['pheight'], $CONFIG['thumb_width']);
			if ($CONFIG['seo_alts'] == 0) {
				$pic_title = FILENAME . $row['filename'] . "\n" . FILESIZE . ($row['filesize'] >> 10) . $lang_byte_units[1] . "\n" . DIMENSIONS . $row['pwidth'] . "x" . $row['pheight'] . "\n" . DATE_ADDED . localised_date($row['ctime'], $album_date_fmt);
			} else {
				if ($row['title'] != '') {
					$pic_title = $row['title'];
					if ($row['keywords'] != '') {
						$pic_title .= "\n";
						$pic_title .= $row['keywords'];
					} 
				} elseif ($row['keywords'] != '') {
					$pic_title = $row['keywords'];
				} else {
					$pic_title = substr($row['filename'], 0, -4);
				} 
			} 
			if ($meta == 'random') {
				$thumb_list[$i]['pos'] = $row['pid'];
			} else {
				$thumb_list[$i]['pos'] = $key < 0 ? $key : $i - 1 + $lower_limit;
			}
			if ($CONFIG['seo_alts'] == 0) {
				$thumb_list[$i]['image'] = "<img src=\"" . get_pic_url($row, 'thumb') . "\" class=\"image\" {$image_size['geom']} border=\"0\" alt=\"{$row['filename']}\" title=\"$pic_title\" />";
			} else {
				$thumb_list[$i]['image'] = "<img src=\"" . get_pic_url($row, 'thumb') . "\" class=\"image\" {$image_size['geom']} border=\"0\" alt=\"$pic_title\" title=\"$pic_title\" />";
			} 
			$thumb_list[$i]['caption'] = isset($row['caption_text']) ? $row['caption_text'] : '';
			$thumb_list[$i]['pid'] = $row['pid'];
			$thumb_list[$i]['admin_menu'] = '';
			//$th_search = isset($_POST['search']) ? $_POST['search'] : '';
			//$th_search = isset($_GET['search']) ? $_GET['search']: $th_search;
			//$srh = isset($_GET['meta']) && $_GET['meta'] == 'search' ? "&amp;search=$th_search" : '';
			
			$thumb_list[$i]['url'] = getlink("&amp;file=displayimage$meta_link$alb_link&amp;pos=".$thumb_list[$i]['pos']/*.$srh*/);
		}
		theme_display_thumbnails($thumb_list, $thumb_count, $album_name, $page_link, $page, $total_pages, is_numeric($album), $display_tabs);
	} else {
		if (isset($_GET['meta'])){
			theme_no_img_to_display($album_name);
		}else{
			trigger_error($album_name.' '.NO_IMG_TO_DISPLAY,E_USER_NOTICE);
		}
	}
} 

// Return the url for a picture, allows to have pictures spreaded over multiple servers
function get_pic_url(&$pic_row, $mode)
{
	global $CONFIG;
	static $pic_prefix = array();
	static $url_prefix = array();
	if (!count($pic_prefix)) {
		$pic_prefix = array('thumb' => $CONFIG['thumb_pfx'],
			'normal' => $CONFIG['normal_pfx'],
			'fullsize' => ''
		);
		$url_prefix = array(0 => $CONFIG['fullpath']);
	} 
	return path2url($pic_row['filepath'] . $pic_prefix[$mode] . $pic_row['filename']);
} 

function print_debug()
{
	global $CPG_URL, $CPG_M_DIR, $CPG_SESS;
	global $USER, $CONFIG, $first_install_M_DIR,$THEME_DIR,$CLASS;
	echo '<br />';
	if ($CONFIG['debug_mode'] AND USER_IS_ADMIN && (!$CLASS['member']->demo)) {
		OpenTable();
		echo '<table width="100%">
	<caption>Debug Information</caption>
	<tr><td class="tableh1" colspan="2"><span class="statlink"><b>Integration vars</b></span></td></tr>
	<tr><td>Selected CMS Theme:</td><td>'.$CPG_SESS['theme'].'</td></tr>
	<tr class="code"><td>Coppermine Theme:</td><td>'.$THEME_DIR.'</td></tr>
	<tr><td>Module path</td><td>'.$CPG_M_DIR.'</td></tr>
	<tr class="code"><td>Nukelink</td><td>'.$CPG_URL.'</td></tr>
	<tr><td class="tableh1" colspan="2"><span class="statlink"><b>$_SERVER vars</b></span></td></tr>
	<tr><td>DOCUMENT_ROOT:</td><td>'.$_SERVER['DOCUMENT_ROOT'].'</td></tr>
	<tr class="code"><td>';
		if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
			echo 'HTTP_ACCEPT_CHARSET:</td><td>'.$_SERVER['HTTP_ACCEPT_CHARSET'].'</td></tr>
	<tr><td>';
		}
		print 'HTTP_ACCEPT_LANGUAGE:</td><td>'.$_SERVER['HTTP_ACCEPT_LANGUAGE'].'</td></tr>
	<tr class="code"><td>HTTP_USER_AGENT:</td><td>'.$_SERVER['HTTP_USER_AGENT'].'</td></tr>
	<tr><td>SCRIPT_FILENAME:</td><td>'.$_SERVER['SCRIPT_FILENAME'].'</td></tr>
	<tr class="code"><td></td></tr>
	<tr><td>QUERY_STRING:</td><td>'.str_replace('&', '&amp;', $_SERVER['QUERY_STRING']).'</td></tr>
	<tr class="code"><td>PHP_SELF:</td><td>'.$_SERVER['PHP_SELF'].'</td></tr>
	<tr><td class="tableh1" colspan="2"><span class="statlink"><b>CPG Config</b></span></td></tr>
	<tr><td>TABLE_PICTURES</td><td>'.$CONFIG['TABLE_PICTURES'].'</td></tr>
	<tr class="code"><td>TABLE_ALBUMS</td><td>'.$CONFIG['TABLE_ALBUMS'].'</td></tr>
	<tr><td>TABLE_COMMENTS</td><td>'.$CONFIG['TABLE_COMMENTS'].'</td></tr>
	<tr class="code"><td>TABLE_CATEGORIES</td><td>'.$CONFIG['TABLE_CATEGORIES'].'</td></tr>
	<tr><td>TABLE_CONFIG</td><td>'.$CONFIG['TABLE_CONFIG'].'</td></tr>
	<tr class="code"><td>TABLE_USERGROUPS</td><td>'.$CONFIG['TABLE_USERGROUPS'].'</td></tr>
	<tr><td>TABLE_VOTES</td><td>'.$CONFIG['TABLE_VOTES'].'</td></tr>
	<tr class="code"><td>TABLE_USERS</td><td>' . $CONFIG['TABLE_USERS'].'</td></tr>
	<tr><td>allow_private_albums</td><td>' . $CONFIG['allow_private_albums'].'</td></tr>
	<tr class="code"><td>Site url (ecards_more_pic_target)</td><td>' . $CONFIG['ecards_more_pic_target'].'</td></tr>
	<tr><td>fullpath</td><td>' . $CONFIG['fullpath'].'</td></tr>
	<tr class="code"><td>USER_IN_GROUPS</td><td>' . USER_IN_GROUPS.'</td></tr>
	<tr><td>Default Language</td><td>' . $CONFIG['lang'].'</td></tr>
	<tr class="code"><td>Language Directory</td><td>' . $first_install_M_DIR.'</td></tr>
	<tr><td class="tableh1" colspan="2"><span class="statlink"><b>Queries</b></span></td></tr>
	<tr><td valign="top">GET</td><td><pre>'.var_export($_GET, true).'</pre></td></tr>
	<tr class="code"><td valign="top">POST</td><td><pre>'.var_export($_POST, true).'</pre></td></tr>
	</table>';
		CloseTable();
	}
} 

function speedup_pictures()
{
	global $db, $CONFIG;
	// Speed-up the random image query by 'keying' the image table
	$result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_PICTURES']}");
	$nbEnr = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	$pic_count = $nbEnr[0];
	$granularity = floor($pic_count / RANDPOS_MAX_PIC);
	if ($granularity != $CONFIG['randpos_interval'] && $pic_count > RANDPOS_MAX_PIC) {
		$result = $db->sql_query("UPDATE {$CONFIG['TABLE_PICTURES']} SET randpos = ROUND(RAND()*$granularity)");
		$result = $db->sql_query("UPDATE {$CONFIG['TABLE_CONFIG']} SET value = '$granularity' WHERE name = 'randpos_interval'");
	}
}
function init_cpg_count() {
	global $db, $CONFIG;
	static $data;
	if (!is_array($data)) {
		$result = $db->sql_ufetchrowset("
		SELECT 
			COUNT(*) as pic_count,
			a.aid,
			a.category,
			c.parent
		FROM {$CONFIG['TABLE_PICTURES']} p 
		LEFT JOIN 
			{$CONFIG['TABLE_ALBUMS']} a 
		ON (p.aid=a.aid)
		LEFT JOIN 
			{$CONFIG['TABLE_CATEGORIES']} c
		ON (c.cid=a.category)
		WHERE a.category < ".FIRST_USER_CAT."
		GROUP by 
			a.category,
			a.aid", SQL_ASSOC);

		$stats = $data = array();
		if (count($result)) {
			foreach ($result as $row) {
				//assign parent 0 to albums assigned to the categery 0 (root)
				$stats[$row['category']]['parent'] = empty($row['parent']) ? 0 : $row['parent'];
				$stats[$row['category']]['category'] = $row['category'];
				//move pictures count from albums to their category
				$stats[$row['category']]['album_count'] = !empty($stats[$row['category']]['album_count']) ? $stats[$row['category']]['album_count'] + 1 : 1;
				$stats[$row['category']]['pic_count'] = isset($stats[$row['category']]['pic_count']) ? $stats[$row['category']]['pic_count'] + $row['pic_count'] : $row['pic_count'];
			}
		}
		if (count($stats)) foreach ($stats as $row) resolve_cpg_count($stats, $data, $row['pic_count'], $row['album_count'], $row['parent'], $row['category']);
	}
	return $data;
}

function resolve_cpg_count(&$stats, &$data, $pic_count = 0, $album_count = 0, $parent = '', $cat = '') {
	global $db, $CONFIG;
	$data[$cat]['parent'] = $parent;
	$data[$cat]['category'] = $cat;
	$data[$cat]['album_count'] = $album_count;
	$data[$cat]['pic_count'] = $pic_count;
	if ($parent) {
		// not passed from the loop yet
		if (!isset($data[$parent])) {
			// category doesn't contains albums but have sub-category will not be included in init_cpg_count()
			// then we build the category and its data
			if (!isset($stats[$parent])) {
				foreach (get_categories_data() as $row) {
					if ($row['cid'] != $parent) continue;
					$tmp_parent = $row['parent'];
					break;
				}
				$data[$parent]['parent'] = $stats[$parent]['parent'] = $tmp_parent;
				$data[$parent]['category'] = $stats[$parent]['category'] = $parent;
				$data[$parent]['album_count'] = $stats[$parent]['album_count'] = $data[$cat]['album_count'];
				$data[$parent]['pic_count'] = $stats[$parent]['pic_count'] = $data[$cat]['pic_count'];
			} else {
				// category data is available from the query
				$data[$parent]['parent'] = $stats[$parent]['parent'];
				$data[$parent]['category'] = $stats[$parent]['category'];
				$data[$parent]['album_count'] = $album_count;
				$data[$parent]['pic_count'] = $pic_count;
			}
		} else {
			$data[$parent]['album_count'] += $album_count;
			$data[$parent]['pic_count'] += $pic_count;
		}
		//move data to the parent
		resolve_cpg_count($stats, $data, $data[$parent]['pic_count'], $data[$parent]['album_count'], $stats[$parent]['parent'], $stats[$parent]['category']);
	}
}

function get_categories_data() {
	global $db, $CONFIG;
	static $categories_data;
	if (!is_array($categories_data)) {
		$result = $db->sql_query("SELECT cid, catname, description, parent FROM {$CONFIG['TABLE_CATEGORIES']}  ORDER BY pos");
        $categories_data = $db->sql_fetchrowset($result, SQL_ASSOC);
	}
	return $categories_data;
}	

function get_albums_data() {
	global $db, $CONFIG;
	static $albums_data;
	if (!is_array($albums_data)) {
		$result = $db->sql_query("SELECT aid, category, visibility FROM {$CONFIG['TABLE_ALBUMS']}");
        $albums_data = $db->sql_fetchrowset($result, SQL_ASSOC);
        if (!count($albums_data)) $albums_data = array();
	}
	return $albums_data;
}	
