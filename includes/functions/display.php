<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/functions/display.php,v $
  $Revision: 9.51 $
  $Author: nanocaiordo $
  $Date: 2008/02/02 05:22:44 $
**********************************************/

function public_message() { return ''; }
function get_theme() {
	static $theme;  // Thanks to steven111 at NukeCops for pointing to static vars
	if (isset($theme)) return $theme;
	if (isset($_GET['prevtheme'])) {
		$prevtheme = $_GET['prevtheme'];
		if (!preg_match('#^([a-zA-Z0-9_\\\\\-]+)$#m', $prevtheme)) { cpg_error(sprintf(_ERROR_BAD_CHAR,'theme'), _SEC_ERROR); }
	}
	global $userinfo, $MAIN_CFG, $CPG_SESS;
	if (!is_admin() && !$MAIN_CFG['member']['allowusertheme']) {
		$CPG_SESS['theme'] = $MAIN_CFG['global']['Default_Theme'];
	}
	if (isset($prevtheme) && file_exists("themes/$prevtheme/theme.php")) {
		$theme = $prevtheme;
	} else if (isset($CPG_SESS['theme']) && file_exists("themes/$CPG_SESS[theme]/theme.php")) {
		$theme = $CPG_SESS['theme'];
	} else if (is_user() && file_exists("themes/$userinfo[theme]/theme.php")) {
		$theme = $userinfo['theme'];
	} else if (file_exists('themes/'.$MAIN_CFG['global']['Default_Theme'].'/theme.php')) {
		$theme = $MAIN_CFG['global']['Default_Theme'];
	}
	$CPG_SESS['theme'] = empty($theme) ? 'default' : $theme;
	return $CPG_SESS['theme'];
}

function adminblock($bid, $title, &$data) {
	if (is_admin()) {
		$waitlist = $content = $imgcontent = '';
		if (empty($data)) $data = '';
		global $prefix, $db, $MAIN_CFG, $waitlist;
		if (!defined('ADMIN_PAGES') && $MAIN_CFG['global']['admingraphic'] & 1) {
			global $CLASS;
			require_once(CORE_PATH.'classes/cpg_adminmenu.php');
			$imgcontent = $CLASS['adminmenu']->display('all', 'blockgfx').'<hr>';
		}
		if (!empty($data)) $data = $imgcontent.$data.'<hr />';
		$imgcontent = '';
		// $title = _WAITINGCONT;
		// Contributed by sengsara
		if (!Cache::array_load('waitlist')) {
			if ($waitdir = dir('admin/wait')) {
				while($waitfile = $waitdir->read()) {
					if (preg_match('/^wait_(.*?)\.php$/', $waitfile, $match)) {
						$waitlist[$match[1]] = "admin/wait/$waitfile";
					}
				}
				$waitdir->close();
			}
			// Dragonfly system
			$waitdir = dir('modules');
			while($module = $waitdir->read()) {
				if (!is_active($module)) continue;
				if (!preg_match('#[\.]#m',$module) && $module != 'CVS' && file_exists("modules/$module/admin/adwait.inc")) {
					$waitlist[$module] = "modules/$module/admin/adwait.inc";
				}
			}
			$waitdir->close();
			Cache::array_save('waitlist');
		}
		ksort($waitlist);
		foreach($waitlist as $module => $file) {
			require($file);
		}
		$block = array(
			'bid' => $bid,
			'view' => 2,
			'title' => $title,
			'content' => $data.$content
		);
		return $block;
	}
	return false;
}
function title($text) {
	# obsolete
}
function yesno_option($name, $value=0) {
	$value = ($value>0) ? 1 : 0;
	if (function_exists('theme_yesno_option')) {
		return theme_yesno_option($name, $value);
	} else {
		$sel = array('','');
		$sel[$value] = ' checked="checked"';
		return '<input type="radio" name="'.$name.'" id="'.$name.'" value="1"'.$sel[1].' /><label class="rdr" for="'.$name.'">'._YES.'</label><input type="radio" name="'.$name.'" id="'.$name.'" value="0" '.$sel[0].' /><label class="rd" for="'.$name.'">'._NO.'</label> ';
	}
}
function select_option($name, $default, $options) {
	if (function_exists('theme_select_option')) {
		return theme_select_option($name, $default, $options);
	} else {
		$select = '<select class="set" name="'.$name.'" id="'.$name."\">\n";
		foreach($options as $var) {
			$select .= '<option'.(($var == $default)?' selected="selected"':'').">$var</option>\n";
		}
		return $select.'</select>';
	}
}
function select_box($name, $default, $options) {
	if (function_exists('theme_select_box')) {
		return theme_select_box($name, $default, $options);
	} else {
		$select = '<select class="set" name="'.$name.'" id="'.$name."\">\n";
		foreach($options as $value => $title) {
			$select .= "<option value=\"$value\"".(($value == $default)?' selected="selected"':'').">$title</option>\n";
		}
		return $select.'</select>';
	}
}
function viewbanner() {
	$impmade = null;
 $bid = null;
 $imptotal = null;
 $cid = null;
 $clicks = null;
 $imageurl = null;
 $clickurl = null;
 $alttext = null;
 $text_title = null;
 $mailer_message = null;
 $textban = null;
 $text_width = null;
 $text_height = null;
 //if (is_admin()) { return ''; }
	global $prefix, $db;
	$result = $db->sql_query("SELECT * FROM ".$prefix."_banner WHERE type='0' AND active='1' ORDER BY RAND() LIMIT 0,1");
	if ($db->sql_numrows($result) < 1) return;
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	foreach($row as $var => $value) {
		if (isset(${$var})) unset(${$var});
		${$var} = $value;
	}
	if (!is_admin()) {
		$db->sql_query('UPDATE '.$prefix.'_banner SET impmade=' . $impmade . "+1 WHERE bid='$bid'");
	}
	/* Check if this impression is the last one and print the banner */
	if ($imptotal <= $impmade && $imptotal != 0) {
		global $sitename, $nukeurl, $user_prefix;
		$db->sql_query('UPDATE '.$prefix."_banner SET active='0' WHERE bid='$bid'");
		$result = $db->sql_query('SELECT username, user_email FROM '.$user_prefix."_users WHERE user_id='$cid'");
		$row = $db->sql_fetchrow($result);
		$to_name = $row['username'];
		$message = _HELLO." $to_name,\n\n"
			._THISISAUTOMATED."\n\n"
			._THERESULTS."\n\n"
			.BANNER_ID.": $bid\n"
			._TOTALIMPRESSIONS." $imptotal\n"
			._CLICKSRECEIVED." $clicks\n"
			._IMAGEURL." $imageurl\n"
			._CLICKURL." $clickurl\n"
			._ALTERNATETEXT." $alttext\n\n"
			._TEXT_TITLE.": $text_title\n\n"
			._HOPEYOULIKED."\n\n"
			._THANKSUPPORT."\n\n"
			."- $sitename "._TEAM."\n".$nukeurl;
		send_mail($mailer_message, $message,0, "$sitename: "._BANNERSFINNISHED, $row['user_email'], $row['username']);
		$db->sql_freeresult($result);
	}
	if ($textban) {
		return '<div style="text-align:center; margin:auto; width:'.$text_width.'px; height:'.$text_height.'px; '.(!empty($text_bg) ? 'background-color:#'.$text_bg.';"' : '"').'><a href="banners.php?bid='.$bid.'"'.(!empty($text_clr) ? ' style="color:#'.$text_clr.';"' : '').' target="_blank">'.$text_title.'</a></div>';
	} else {
		return '<a href="banners.php?bid='.$bid.'" target="_blank"><img src="'.$imageurl.'" style="border:0;" alt="'.$alttext.'" title="'.$alttext.'" /></a>';
	}
}
function show_tooltip($tip) {
	global $MAIN_CFG;
	return $MAIN_CFG['global']['admin_help'] ? ' onmouseover="tip(\''.$tip.'\')" onmouseout="untip()"' : '';
//	<img src="images/help.gif" alt="" onmouseover="tip(\''.$tip.'\')" onmouseout="untip()" style="cursor: help;" />
}
function open_form($link='', $form_name=false, $legend=false, $tborder=false) {
	if (function_exists('theme_open_form')) {
		return theme_open_form($link, $form_name, $legend, $tborder);
	} else {
		$leg = ($legend ? "<legend>$legend</legend>" : '');
		$bord = ($tborder ? $tborder : '');
		$form_name = ($form_name ? ' id="'.$form_name.'"' : '');
		return '<form method="post" action="'.$link.'"'.$form_name.' enctype="multipart/form-data" accept-charset="utf-8"><fieldset '.$bord.'>'.$leg;
	}
}
function close_form() {
	if (function_exists('theme_close_form')) {
		return theme_close_form();
	} else {
		return '</fieldset></form>';
	}
}
function generate_secimg($chars=6) {
	global $CPG_SESS;
	mt_srand((double)microtime()*1000000);
	$id = random_int(0, 1000000);
	$time = explode(' ', microtime());
	$CPG_SESS['gfx'][$id] = substr(dechex($time[0]*3581692740), 0, $chars);
	return '<img src="'.getlink("gfx&amp;id=$id").'" alt="'._SECURITYCODE.'" title="'._SECURITYCODE.'" />
	<input type="hidden" name="gfxid" value="'.$id.'" />';
}
function validate_secimg($chars=6) {
	global $CPG_SESS;
	if (!isset($_POST['gfx_check']) || !isset($_POST['gfxid'])) { return false; }
	$code = $CPG_SESS['gfx'][$_POST['gfxid']];
	return (strlen($code) == $chars && $code == $_POST['gfx_check']);
}
function group_selectbox($fieldname, $current=0, $mvanon=false, $all=true) {
	static $groups;
	if (!isset($groups)) {
		global $db, $prefix;
		$groups = array(0=>_MVALL, 1=>_MVUSERS, 2=>_MVADMIN, 3=>_MVANON);
		$groupsResult = $db->sql_query('SELECT group_id, group_name FROM '.$prefix.'_bbgroups WHERE group_single_user=0');
		while (list($groupID, $groupName) = $db->sql_fetchrow($groupsResult)) {
			$groups[($groupID+3)] = $groupName;
		}
	}
	$tmpgroups = $groups;
	if (!$all) { unset($tmpgroups[0]); }
	if (!$mvanon) { unset($tmpgroups[3]); }
	return select_box($fieldname, $current, $tmpgroups);
}
function cpg_delete_msg($link, $msg, $hidden='') {
	require_once('header.php');
	OpenTable();
	if (function_exists('theme_delete_msg')) {
		echo theme_delete_msg($link, $msg, $hidden);
	}
	global $cpgtpl;
	$cpgtpl->assign_vars(array(
		'MESSAGE_TITLE' => _CONFIRM,
		'MESSAGE_TEXT' => $msg,
		'L_YES' => _YES,
		'L_NO' => _NO,
		'S_CONFIRM_ACTION' => $link,
		'S_HIDDEN_FIELDS' => $hidden
	));
	$cpgtpl->set_filenames(array('confirm' => 'confirm_body.html'));
	$cpgtpl->display('confirm');
	CloseTable();
	require('footer.php');
}

function pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext=TRUE)
{
	function pagination_page($page, $url, $first=false) {
		global $cpgtpl;
		$cpgtpl->assign_block_vars('pagination', array('PAGE' => $page, 'URL' => $url, 'FIRST' => $first));
	}
	function pagination_link($url) {
		if (defined('ADMIN_PAGES')) { return adminlink($url); }
		return getlink($url);
	}
	global $cpgtpl;
	$total_pages = ceil($num_items/$per_page);
	$on_page = floor($start_item / $per_page);
	if ($total_pages < 2) { return $cpgtpl->assign_var('B_PAGINATION', false); }
	$cpgtpl->assign_vars(array(
		'B_PAGINATION' => true,
		'PAGINATION_PREV' => ($add_prevnext && $on_page > 1) ? pagination_link($base_url.(($on_page-1)*$per_page)) : false,
		'PAGINATION_NEXT' => ($add_prevnext && $on_page < $total_pages) ? pagination_link($base_url.($on_page+$per_page)) : false,
		'L_PREVIOUS' => _PREVIOUSPAGE,
		'L_NEXT' => _NEXTPAGE,
		'L_GOTO_PAGE' => 'Go to:',
	));
	if ($total_pages > 10) {
		$init_page_max = ($total_pages > 3) ? 3 : $total_pages;
		for ($i = 1; $i <= $init_page_max; $i++) {
			pagination_page($i, ($i == $on_page) ? false : pagination_link($base_url.($i*$per_page)), ($i == 1));
		}
		if ($total_pages > 3) {
			if ($on_page > 1 && $on_page < $total_pages) {
				if ($on_page > 5) { pagination_page(' ... ', false, true); }
				$init_page_min = ($on_page > 4) ? $on_page : 5;
				$init_page_max = ($on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;
				for ($i = $init_page_min - 1; $i < $init_page_max + 2; $i++) {
					pagination_page($i, ($i == $on_page) ? false : pagination_link($base_url.($i*$per_page)), ($on_page <= 5 && $i == $init_page_min-1));
				}
				if ($on_page < $total_pages-4) { pagination_page(' ... ', false, true); }
			} else {
				pagination_page(' ... ', false, true);
			}
			for ($i = $total_pages - 2; $i <= $total_pages; $i++) {
				pagination_page($i, ($i == $on_page) ? false : pagination_link($base_url.($i*$per_page)));
			}
		}
	} else {
		for ($i = 1; $i <= $total_pages; $i++) {
			pagination_page($i, ($i == $on_page) ? false : pagination_link($base_url.($i*$per_page)));
		}
	}
}
