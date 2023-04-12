<?php
/***************************************************************************
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  $Header: /cvs/html/includes/coppermine/init.inc,v 9.9 2007/12/12 12:54:18 nanocaiordo Exp $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

global $template, $cpg_all_installs,$first_install_prefix, $USER;
global $userinfo, $user_prefix, $FAVPICS, $CLASS, $CPG_SESS;

// Define some constants
if (!defined('USER_GAL_CAT')) {
	define('USER_GAL_CAT', 1);
	define('FIRST_USER_CAT', 10000);
	define('RANDPOS_MAX_PIC', 200);
// Constants used by the cpg_die function
	define('INFORMATION', 1);
	define('ERROR', 2);
	define('CRITICAL_ERROR', 3);
}

// Include functions file
require_once("includes/coppermine/functions.inc");

// Initialise the $CONFIG array and some other variables
$CONFIG = array();
// Record User's IP address
//$raw_ip = stripslashes($_SERVER['REMOTE_ADDR']);
$hdr_ip = $raw_ip = $userinfo['user_ip'];

$CONFIG['TABLE_PICTURES'] = $cpg_prefix . "pictures";
$CONFIG['TABLE_ALBUMS'] = $cpg_prefix . "albums";
$CONFIG['TABLE_COMMENTS'] = $cpg_prefix . "comments";
$CONFIG['TABLE_CATEGORIES'] = $cpg_prefix . "categories";
$CONFIG['TABLE_CONFIG'] = $cpg_prefix . "config";
$CONFIG['TABLE_USERGROUPS'] = $first_install_prefix . "usergroups";
$CONFIG['TABLE_VOTES'] = $cpg_prefix . "votes";
$CONFIG['TABLE_USERS'] = $user_prefix . "_users"; //default nuke_

// Retrieve DB stored configuration
if (!isset($cpg_all_installs[$cpg_dir]['config'])) {
	$results = $db->sql_query('SELECT * FROM '. $CONFIG['TABLE_CONFIG']);
	while ($row = $db->sql_fetchrow($results)) {
		$CONFIG[$row['name']] = $row['value'];
	} // while
	$db->sql_freeresult($results);
	$cpg_all_installs[$cpg_dir]['config'] = $CONFIG;
} else {
	$CONFIG = $cpg_all_installs[$cpg_dir]['config'];
}

// Start laoding viewer settings
if (!defined('USER_ID')) {

// Parse cookie stored user profile
user_get_profile();
if (is_user() && $userinfo['user_active_cp']) {
	$sql = "SELECT * FROM {$CONFIG['TABLE_USERGROUPS']} WHERE group_id = ".$userinfo['user_group_cp'];
} else {
	$sql = "SELECT * FROM {$CONFIG['TABLE_USERGROUPS']} WHERE group_id = 3";
}
$results = $db->sql_query($sql);
if ($db->sql_numrows($results)) {
	$USER_DATA = $db->sql_fetchrow($results);
	$USER_DATA = array_merge($USER_DATA, $userinfo);
	define('USER_ID', $USER_DATA['user_id']<=1 ? 0 : $USER_DATA['user_id']);
}
$db->sql_freeresult($results);

if (can_admin($module_name)) {
	define('CPG_USERNAME',($userinfo['user_id']>1) ? $userinfo['username'] : $CLASS['member']->admin['aid']);
	define('GALLERY_ADMIN_MODE',1);
	define('USER_IN_GROUPS', 1);
	define('USER_IS_ADMIN', 1);
	define('USER_CAN_CREATE_ALBUMS', 1);
	define('USER_CAN_POST_COMMENTS',1);
	define('USER_CAN_UPLOAD_PICTURES',1);
	define('USER_CAN_RATE_PICTURES', 1);
	define('USER_CAN_SEND_ECARDS', 1);
} elseif (is_user() && $userinfo['user_active_cp']) {
	$sql = "SELECT * FROM {$CONFIG['TABLE_USERGROUPS']} WHERE group_id = ".$userinfo['user_group_cp'];
	$grouplist = '';
	$user_group_list_cp = explode(',', $USER_DATA['user_group_list_cp']);
	foreach ($user_group_list_cp AS $group) {
		if (is_numeric($group)) {
			$grouplist .= ",".$group;
		}
	}
	define('CPG_USERNAME', $userinfo['username']);
	define('GALLERY_ADMIN_MODE', false);
	define('USER_IN_GROUPS', $USER_DATA['user_group_cp'].$grouplist.",".(FIRST_USER_CAT+$userinfo['user_id']));
	define('USER_IS_ADMIN', intval($USER_DATA['has_admin_access']));
	define('USER_CAN_CREATE_ALBUMS', intval($USER_DATA['can_create_albums']));
	define('USER_CAN_POST_COMMENTS', intval($USER_DATA['can_post_comments']));
	define('USER_CAN_UPLOAD_PICTURES', intval($USER_DATA['can_upload_pictures']));
	define('USER_CAN_RATE_PICTURES', intval($USER_DATA['can_rate_pictures']));
	define('USER_CAN_SEND_ECARDS', intval($USER_DATA['can_send_ecards']));
} else {
	define('CPG_USERNAME', _ANONYMOUS);//'Anonymous'
	define('GALLERY_ADMIN_MODE', false);
	define('USER_IN_GROUPS', 3);
	define('USER_IS_ADMIN', false);
	define('USER_CAN_CREATE_ALBUMS', intval($USER_DATA['can_create_albums']));
	define('USER_CAN_POST_COMMENTS', intval($USER_DATA['can_post_comments']));
	define('USER_CAN_UPLOAD_PICTURES', intval($USER_DATA['can_upload_pictures']));
	define('USER_CAN_RATE_PICTURES', intval($USER_DATA['can_rate_pictures']));
	define('USER_CAN_SEND_ECARDS', intval($USER_DATA['can_send_ecards']));
}

define('USER_ADMIN_MODE', (USER_CAN_CREATE_ALBUMS && USER_ID));

if (GALLERY_ADMIN_MODE) define('VIS_GROUPS', 'visibility > -1');
else define('VIS_GROUPS', 'visibility IN (0,'.USER_IN_GROUPS.')');

// Load language, mod by sengsara/coppermine for CMS crew
// Process language selection if present in URI or in user profile or try
// autodetection if default charset is utf-8
// load language from first install

if (!defined('PIC_VIEWS')) {
	 get_lang('coppermine', __FILE__, __LINE__);
}

} // end if already loaded

$FAVPICS = array();
// See if the fav cookie is set else set it
if (isset($_COOKIE[$CONFIG['cookie_name'] . '_fav'])) {
	$FAVPICS = unserialize(base64_decode($_COOKIE[$CONFIG['cookie_name'] . '_fav']));
	if (!is_array($FAVPICS) || empty($FAVPICS)) {
		$FAVPICS = array();
	} else {
		foreach ($FAVPICS as $key => $id ){
			$FAVPICS[$key] = intval($id);
		}
	}
}
// global var cleansing 9/26/2004 5:12PM akamu
$cat = $_POST['cat'] ?? isset($_GET['cat']) ? $_GET['cat'] : 0;
if (!is_numeric($cat)) $cat = NULL;

$album = $_POST['album'] ?? $_GET['album'] ?? NULL;
if (!is_numeric($album)) $album = NULL;

$pid = $_POST['pid'] ?? $_GET['pid'] ?? NULL;
if (!is_numeric($pid)) $pid = NULL;

$pos = $_POST['pos'] ?? $_GET['pos'] ?? NULL;
if (!is_numeric($pos)) $pos = NULL;


if (isset($album)) {
	$meta_link = '&amp;album='.$album;
} else {
	if (intval($cat) < 0)
		$meta_link = '&amp;cat='.(-$cat);
	else
		$meta_link = '&amp;cat='.$cat;
}
define('META_LNK', $meta_link);
$THEME_DIR ='';
if (!$cpg_block) {
	// Added nuke theme coppermine theme addition hack
	// Thanks to helius theme for coppermine and Sengsara
	$ThemeSel = $CPG_SESS['theme'];
	if (file_exists("themes/$ThemeSel/coppermine/theme.inc") && $CONFIG['theme']== 'default') {
		$THEME_DIR = "themes/$ThemeSel/coppermine";
	} else {
		// Load theme
		if (!file_exists($CPG_M_DIR . "/themes/{$CONFIG['theme']}/theme.inc")) {
			$CONFIG['theme'] = 'default';
		}
		$THEME_DIR = $CPG_M_DIR . "/themes/".$CONFIG['theme'];
	}
	require_once("$THEME_DIR/theme.inc");

	// load the main template
	load_template();
	if (isset($_GET['file']) && !$cpg_block && $_GET['file'] == 'displayimage' && $CONFIG['right_blocks'] == 0 && $showblocks >= 2) {
		$showblocks = 1;
	}
	if ((isset($_GET['slideshow']) && $CONFIG['fullsize_slideshow']) && !$cpg_block && $_GET['file'] == 'displayimage') {
		$showblocks = 0;
	}
	if (isset($_GET['file']) && in_array($_GET['file'],array('editOnePic', 'editpics', 'config', 'upload', 'reviewcom','groupmgr','upload_approval','searchnew','usermgr','albmgr','catmgr')) ){
		$showblocks = 1;
	}
}
