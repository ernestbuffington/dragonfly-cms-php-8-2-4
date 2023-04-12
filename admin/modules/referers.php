<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/referers.php,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:58 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._HTTPREFERERS;
global $bgcolor3, $db, $prefix;

if (isset($_GET['del']) && $_GET['del'] == 'all') {
	$db->sql_query('DELETE FROM '.$prefix.'_referer');
	url_redirect(adminlink());
} else {
	require_once('header.php');
	GraphicAdmin('_AMENU6');
	$result = $db->sql_query('SELECT url FROM '.$prefix.'_referer');
	$bgcolor = '';
	if ($db->sql_numrows($result) > 0) {
		$cpgtpl->assign_vars(array(
			'WHOLINKS' => _WHOLINKS,
			'DELETEREFERERS' => _DELETEREFERERS,
			'U_DELREFERERS' => adminlink('&amp;del=all'),
		));
		while (list($url) = $db->sql_fetchrow($result)) {
			$bgcolor = ($bgcolor == '') ? ' style="background: '.$bgcolor3.'"' : '';
			$cpgtpl->assign_block_vars('referer', array(
				'URL' => $url,
				'CLR' => $bgcolor,
			));
		}
		$cpgtpl->set_filenames(array('body' => 'admin/referers.html'));
		$cpgtpl->display('body');
		$cpgtpl->destroy();
	} else {
		OpenTable();
		echo sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_HTTPREFERERS));
		CloseTable();
	}
}