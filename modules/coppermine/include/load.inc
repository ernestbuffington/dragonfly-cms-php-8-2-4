<?php
/*********************************************
  CPG-Dragonfly™ CMS
  ********************************************
  Copyright (c) 2004-2005 by CPG Dev Team
  http://www.dragonflycms.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/coppermine/include/load.inc,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:52:41 $

****************************************************************************/
/*
if (intval(ini_get(safe_mode))){
   if (ini_get("safe_mode_include_dir")){
		//SEE IF SAFE MODE IS SETUP CORRECTLY
		if(!strpos(ini_get(safe_mode_include_dir),dirname(__FILE__))){
		   // cpg_die("_ERROR", "Safe mode is not setup properly, if it were '>dirname(__FILE__)." would be in the php config safe_mode_include_dir ".ini_get(safe_mode_include_dir).".",__FILE__,__LINE__);
		}
	}
}
*/
global $cpg_dir, $module_name;

$CPG_VERSION = '1.3.1';
if (!isset($cpg_block)) $cpg_block = false;
if (!defined('IN_COPPERMINE')) define('IN_COPPERMINE', true);
if (!isset($cpg_dir)) $cpg_dir = $module_name;
if ($cpg_block && !defined('NO_HEADER')) define('NO_HEADER', true);
if ($module_name == $cpg_dir && (is_user() || is_admin()) && !defined('MEMBER_BLOCK')) {
	define('MEMBER_BLOCK', 1);
}

global $CONFIG, $CURRENT_ALBUM_DATA, $CPG_URL;
// language variables not declared global else where
/*
global $lang_user_admin_menu, $lang_gallery_admin_menu, $lang_cat_list,
	   $lang_get_pic_data,	$lang_pagetitle_php,	  $lang_charset,
	   $lang_text_dir,		$lang_byte_units,		 $lang_day_of_week,
	   $lang_month,		   $lang_yes,				$lang_no,
	   $lang_back,			$lang_continue,		   $lang_info,
	   $lang_picinfo,		 $lang_album_admin_menu;
	   $album_date_fmt,	   $lastcom_date_fmt,
	   $lastup_date_fmt,	  $lasthit_date_fmt,		$comment_date_fmt;
*/
	
global $template_tab_display, $template_user_list_info_box, $template_album_admin_menu; // index.php
global $template_img_navbar,  $template_display_picture,	$template_image_rating,
	   $template_image_comments, $template_add_your_comment; // displayimage.php
global $template_cpg_die, $template_msg_box; // functions.inc.php
global $template_ecard;					  // displayecard.php
global $template_edit_one_pic;			   // editDesc.inc
global $first_install_M_DIR, $CPG_PROFILE_URL, $cpg_all_installs,$CLASS, $first_install_prefix;
$CPG_M_DIR = "modules/" . $cpg_dir;

$CPG_URL = getlink($cpg_dir, false);
$CPG_M_URL = $CPG_URL;
$CPG_PROFILE_URL = getlink("Your_Account&amp;profile=", false);
if (!defined('LOGOUT_URL')) {
	define('LOGOUT_URL', getlink("Your_Account&amp;op=logout"));
	define('LOGIN_URL', getlink("Your_Account"));
	define('NEWUSER_URL', getlink("Your_Account&amp;file=register"));
	define('ADDUSER_URL', adminlink("users&amp;mode=add"));
	define('USERPROF_URL', getlink("$cpg_dir&amp;file=profile"));
}

if (!defined('INSTALL_PHP')) {
	// load required settings for this install
	if (empty($cpg_all_installs) ) {
		$result = $db->sql_query("SELECT prefix, dirname FROM ".$prefix."_cpg_installs ORDER BY cpg_id", false,__FILE__,__LINE__);
		while ($row = $db->sql_fetchrow($result)) {
			if (empty($first_install_M_DIR)) { $first_install_M_DIR = $row['dirname']; }
			if (empty($first_install_prefix)) { $first_install_prefix = $row['prefix']; }
			$cpg_all_installs[$row['dirname']]['prefix'] = $row['prefix'];
		}
	}
	if (!isset($cpg_all_installs[$cpg_dir])) {
		url_redirect(getlink("&file=install"));
	}
	$cpg_prefix = $cpg_all_installs[$cpg_dir]['prefix'];
	require_once('includes/coppermine/init.inc');
	if (!defined('FIRST_USER_CAT')) die('FIRST_USER_CAT not defined');

	if (!defined('NO_HEADER')) {
		global $Blocks;
		$block = array(
			'bid' => 10000,
			'view' => 1,
			'side' => 'l',
			'title' => _coppermineLANG.' '._TB_TASKS,
			'content' => coppermine_block()
		);
		$Blocks->custom($block);
		$block = NULL;

		// Set nice page title
		$pagetitle .= $CONFIG['gallery_name'];
		$value = $db->sql_ufetchrow("SELECT value FROM " . $cpg_prefix . "config WHERE name='nice_titles'", SQL_NUM, __FILE__,__LINE__);
		if ($value[0]) {
			$breadcrumb = array();
			if (isset($album)&&(is_numeric($album) && $album > 0)) {
				$result = $db->sql_query("SELECT aid AS id, title, category AS parent FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = ".$album);
				if ($db->sql_numrows($result) > 0) {
					$breadcrumb[] = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
				}
			}
			else if (is_numeric($cat) && $cat > 0) {
				if ($cat > FIRST_USER_CAT) {
					$result = $db->sql_query("SELECT username AS title FROM {$CONFIG['TABLE_USERS']} WHERE user_id = " . ($cat - FIRST_USER_CAT));
					if ($db->sql_numrows($result) != 0) {
						$row = $db->sql_fetchrow($result);
						$row['id'] = $cat;
						$row['parent'] = 1;
						$breadcrumb[] = $row;
						$db->sql_freeresult($result);
					}
				}
				else {
					$result = $db->sql_query("SELECT cid as id, parent, catname AS title FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid='$cat'");
					if ($db->sql_numrows($result) > 0) {
						$breadcrumb[] = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
					}
				}
			}
			get_breadcrumb($breadcrumb);
			foreach($breadcrumb as $crumb) {
				$pagetitle .= " "._BC_DELIM." ".$crumb['title'];
			}
			if (!defined('PIC_VIEWS')) {
				get_lang('coppermine', __FILE__, __LINE__);
			}
			if (isset($_GET['meta']) && ($_GET['meta']!='')) {
				$pagetitle .= " "._BC_DELIM." ".$lang_meta_album_names[$_GET['meta']];
			}	
			if ($pagetitle == "") $pagetitle = $CONFIG['gallery_name'];
		}
		else $pagetitle .= $CONFIG['gallery_name'];
	}
}