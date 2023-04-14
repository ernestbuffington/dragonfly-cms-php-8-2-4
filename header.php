<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/header.php,v $
  $Revision: 9.33 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:17 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
define('HEADER_OPEN', true);

# 1-2 queries
function online() {
	global $userinfo, $prefix, $db, $module_title, $SESS, $mainindex;
	if ($SESS->dbupdate) {
		$url = get_uri();
		$uname = $SESS->sess_id;
		$guest = 1;
		if (is_user()) {
			$uname = $userinfo['username'];
			$guest = 0;
		} elseif (SEARCHBOT) {
			$uname = SEARCHBOT;
			$guest = 3;
		}
		if (is_admin()) {
			global $CLASS;
			if ($guest == 1) { $uname = $CLASS['member']->admin['aid']; }
			$guest = 2;
			if (defined('ADMIN_PAGES')) $url = $mainindex;
		}
		$uname = Fix_Quotes($uname);
		if (empty($uname)) return; # something screwey
		$ctime = gmtime();
		$custom_title = Fix_Quotes(($module_title) ? $module_title : _HOME);
		$url = Fix_Quotes(str_replace('&', '&amp;', $url));
		if ($db->sql_count($prefix.'_session', "uname='$uname'")) {
			$db->sql_query('UPDATE '.$prefix."_session SET time='$ctime', module='$custom_title', url='$url', guest='$guest' WHERE uname='$uname'", true);
		} else {
			$db->sql_query('INSERT INTO '.$prefix."_session (uname, time, host_addr, guest, module, url) VALUES ('$uname', '$ctime', {$userinfo['user_ip']}, '$guest', '$custom_title', '$url')", true);
		}
	}
}
function head() {
	global $BASEHREF, $METATAGS, $slogan, $sitename, $userinfo, $showblocks, $index,
		$bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4, $textcolor1, $textcolor2, $Blocks,
		$pagetitle, $modheader, $MAIN_CFG, $CPG_SESS, $module_name, $CLASS, $adminindex;
	include_once('themes/'.$CPG_SESS['theme'].'/theme.php');
	if (!defined('THEME_VERSION')) { define('THEME_VERSION', '9.0'); }
	if (!defined('_BROWSER_LANGCODE')) { define('_BROWSER_LANGCODE', _LANGCODE); }
	if (!defined('_CHARSET')) { define('_CHARSET', 'UTF-8'); }

	# Work around for "current" Apache 2 + PHP module which seems to not cope with private cache control setting
	if (!empty($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache/2')) {
		header('Cache-Control: no-cache, pre-check=0, post-check=0');
	} else {
		header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
	}
	header('imagetoolbar: no');
	$message = '';
	$header = '<base href="'.$BASEHREF.'" />'."\n";
	foreach ($METATAGS as $name => $content) {
		$header .= '<meta name="'.$name.'" content="'.$content.'" />'."\n";
	}
	$header .= '<title>'.$sitename.(!empty($pagetitle) ? ' '._BC_DELIM.' '.strip_tags($pagetitle) : '').'</title>';
	if (file_exists('themes/'.$CPG_SESS['theme'].'/images/favicon.ico')) {
		$header .= '<link rel="shortcut icon" href="'.$BASEHREF.'themes/'.$CPG_SESS['theme'].'/images/favicon.ico" type="image/x-icon" />'."\n";
	} else if (file_exists('favicon.ico')) {
		$header .= '<link rel="shortcut icon" href="'.$BASEHREF.'favicon.ico" type="image/x-icon" />'."\n";
	}

	if ($MAIN_CFG['global']['block_frames']) {
		$header .= '<script type="text/javascript">if (self != top) top.location.replace(self.location)</script>'."\n";
	}
	if ($MAIN_CFG['global']['admin_help']) {
		$header .= '<script type="text/javascript" src="includes/javascript/infobox.js"></script>'."\n";
	}
	$header .= '<script type="text/javascript" src="includes/javascript/blockscript.js"></script>
    '.$modheader.'
    <link rel="copyright" href="'.getlink('credits').'" title="Copyrights" />
    <link rel="author" href="'.getlink('Members_List').'" title="'._Members_ListLANG.'" />
    <link rel="alternate" type="application/rss+xml" title="RSS" href="rss/news2.php" />
    ';

	if ($module_name != 'Private_Messages' && is_user() && is_active('Private_Messages') && $userinfo['user_popup_pm'] && $userinfo['user_new_privmsg']) {
		$header .= '
<script type="text/javascript">
<!--
	window.open(\''.getlink('Private_Messages&file=index&mode=newpm', false).'\', \'\', \'height=150,resizable=yes,width=400\');
//-->
</script>';
	}
	if ($MAIN_CFG['global']['maintenance']) { $message = '<strong>'._SYS_MESSAGE.'</strong><br />'._SYS_MAINTENANCE; }
	if (is_admin() && $CLASS['member']->demo) {
		$message .= ($message == '') ? '<strong>'._SYS_MESSAGES.'</strong>' : '<br />';
		$message .= '<br />'._SYS_DEMO;
	}
	$index = ($showblocks > 1) ? 1 : 0;
	themeheader();
	global $cpgtpl, $ownpagetitle, $home;
	$adminmenuitems = $admincssmenuitems = false;
	$cpgtpl->assign_vars(array(
		'IMPORTANT_MESSAGE' => $message,
		'S_TEXTDIR'	 => _TEXT_DIR,
		'S_LANGCODE'	=> _BROWSER_LANGCODE,
		'S_HEADER_TAGS' => $header,
		'S_LEFTBLOCKS'  => ($Blocks->showblocks & 1),
		'S_RIGHTBLOCKS' => ($Blocks->showblocks & 2),
		'I18N'		  => 'enctype="multipart/form-data" accept-charset="utf-8"'
	));
	if (!$home) {
		if (defined('ADMIN_PAGES') && is_admin() && !(isset($_GET['op']) && $_GET['op'] == 'logout')) {
			require_once('includes/classes/cpg_adminmenu.php');
			$adminmenuitems = $CLASS['adminmenu']->display('all', 'jsmenu');
			$admincssmenuitems = ($MAIN_CFG['global']['admingraphic'] & 4);
		}
	}
	$cpgtpl->assign_vars(array(
		'PAGE_TITLE'   => ($home || isset($ownpagetitle[$module_name]))?'':$pagetitle,
		'S_ADMIN_MENU' => $adminmenuitems,
		'S_ADMIN_CSSMENU' => $admincssmenuitems
	));
	unset($modheader);
}

if (empty($_SESSION['SECURITY']['banned'])) { online(); }
global $home, $cpgtpl, $Blocks;
$Blocks->init();
head();
if (!defined('ADMIN_PAGES')) {
	require_once('includes/counter.php');  # 2-3 queries
	if ($home) {
		require_once('includes/functions/messagebox.php');
		message_box();
	}
}
$Blocks->display('c');
$cpgtpl->set_filenames(array('cpgheader' => 'header.html'));
$cpgtpl->display('cpgheader');
