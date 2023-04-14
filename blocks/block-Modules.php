<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Modules.php,v $
  $Revision: 9.7 $
  $Author: phoenix $
  $Date: 2007/10/03 04:38:34 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $prefix, $db, $main_module, $language, $currentlang, $nukeurl, $mainindex;

$content = '';

// make home link show which module is there
$home_title = _HOME;
$home_title .= ' - ';
$home_title .= (defined('_'.$main_module.'LANG'))? (constant('_'.$main_module.'LANG')) : preg_replace('#_#m', ' ', $main_module);

/* Now we make the Modules block with the correspondent links */
//$content .= "<b>&#8226;</b>&nbsp;<a href=\"".getlink()."\">"._HOME."</a><br />\n";
$content .= '<b>&#8226;</b>&nbsp;<a href="'.$nukeurl.'/'.$mainindex.'">'.$home_title.'</a><br />';
$result = $db->sql_query("SELECT title, custom_title, view FROM ".$prefix."_modules 
WHERE active='1' AND inmenu='1' 
ORDER BY custom_title ASC");
while (list($m_title, $custom_title, $m_view) = $db->sql_fetchrow($result)) {
	//$m_title2 = ereg_replace("_", " ", $m_title);
	if ($custom_title != '' && $custom_title != $m_title && $language === $currentlang){
		$m_title2 = $custom_title;
	} else { 
		if ($custom_title != '') {
			$m_title2 = (defined('_'.$m_title.'LANG'))? (constant('_'.$m_title.'LANG')) : $custom_title;
		} else {
			$m_title2 = (defined('_'.$m_title.'LANG'))? (constant('_'.$m_title.'LANG')) : ($m_title2 = preg_replace('#_#m', ' ', $m_title));
		}
	}
	if ($m_title != $main_module) {
		if ((is_admin() && $m_view == 2) || $m_view != 2) {
			if ($m_view == 1 && !is_user()) $content .= '<img src="images/blocks/CPG_Main_Menu/noaccess.gif" width="10" height="10" alt="" title="" />';
			else $content .= '<b>&#8226;</b>';
			$content .= '&nbsp;<a href="'.getlink($m_title).'">'.$m_title2.'</a><br />';
		}
	}
}
$db->sql_freeresult($result);

/* If you're Admin you and only you can see Inactive modules and test it */
/* If you copied a new module is the /modules/ directory, it will be added to the database */
	
if (is_admin()) {
	$content .= '<p style="text-align:center;"><b>'._INVISIBLEMODULES.'</b></p>';
	$content .= '<div class="tiny">'._ACTIVEBUTNOTSEE.'</div>';
	$result = $db->sql_query("SELECT title, custom_title FROM ".$prefix."_modules 
	WHERE active='1' AND inmenu='0' 
	ORDER BY title ASC");
	$dummy = 1;
	while (list($mn_title, $custom_title) = $db->sql_fetchrow($result)) {
		$mn_title2 = preg_replace('#_#m', ' ', $mn_title);
		if ($custom_title != '') {
			$mn_title2 = $custom_title;
		}
		if ($mn_title2 != '') {
			$content .= '<b>&#8226;</b>&nbsp;<a href="'.getlink($mn_title).'">'.$mn_title2.'</a><br />';
			$dummy = 0;
		}
	}
	$db->sql_freeresult($result);
	if ($dummy) {
		$content .= '<b>&#8226;</b>&nbsp;<i>'._NONE.'</i><br />';
	}
	$content .= '<p style="text-align:center;"><b>'._NOACTIVEMODULES.'</b></p>';
	$content .= '<div class="tiny">'._FORADMINTESTS.'</div>';
	$result = $db->sql_query("SELECT title, custom_title FROM ".$prefix."_modules 
	WHERE active='0' 
	ORDER BY title ASC");
	$dummy = 1;
	while (list($mn_title, $custom_title) = $db->sql_fetchrow($result)) {
		$mn_title2 = preg_replace('#_#m', ' ', $mn_title);
		if ($custom_title != '') {
			$mn_title2 = $custom_title;
		}
		if ($mn_title2 != '') {
			$content .= '<b>&#8226;</b>&nbsp;<a href="'.getlink($mn_title).'">'.$mn_title2.'</a><br />';
			$dummy = 0;
		}
	}
	$db->sql_freeresult($result);
	if ($dummy) {
		$content .= '<b>&#8226;</b>&nbsp;<i>'._NONE.'</i><br />'."\n";
	}
}
