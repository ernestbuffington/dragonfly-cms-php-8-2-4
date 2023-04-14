<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  $Source: /public_html/index.php,v $
  $Revision: 9.37 $
  $Author: phoenix $
  $Date: 2007/10/04 03:04:30 $

  A free program released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Linking CPG Dragonfly™ CMS statically or dynamically with other modules is making a
  combined work based on CPG Dragonfly CMS.  Thus, the terms and conditions of the GNU
  General Public License cover the whole combination.

  As a special exception, the copyright holders of CPG Dragonfly CMS give you
  permission to link CPG Dragonfly CMS with independent modules that communicate with
  CPG Dragonfly CMS solely through the CPG-Core interface, regardless of the license
  terms of these independent modules, and to copy and distribute the
  resulting combined work under terms of your choice, provided that
  every copy of the combined work is accompanied by a complete copy of
  the source code of CPG Dragonfly CMS (the version of CPG Dragonfly CMS used to produce the
  combined work), being distributed under the terms of the GNU General
  Public License plus this exception.  An independent module is a module
  which is not derived from or based on CPG Dragonfly CMS.

  Note that people who make modified versions of CPG Dragonfly CMS are not obligated
  to grant this special exception for their modified versions; it is
  their choice whether to do so.  The GNU General Public License gives
  permission to release a modified version without this exception; this
  exception also makes it possible to release a modified version which
  carries forward this exception.
  http://gnu.org/licenses/gpl-faq.html#LinkingOverControlledInterface

***********************************************************************/
$start_mem = function_exists('memory_get_usage') ? memory_get_usage() : 0;
require_once('includes/cmsinit.inc');

$file = $_POST['file'] ?? $_GET['file'] ?? 'index';
if (!preg_match('#^([a-zA-Z0-9_\\\\\-]+)$#m', $file)) { cpg_error(sprintf(_ERROR_BAD_CHAR, strtolower(_BLOCKFILE2)), _SEC_ERROR); }

if (isset($_GET['name']) || isset($_POST['name'])) {
	$module_name = strtolower($_POST['name'] ?? $_GET['name']);
	$home = 0;
	if (!preg_match('#^([a-z0-9_\\\\\-]+)$#m', $module_name)) {
		cpg_error(sprintf(_ERROR_BAD_CHAR, strtolower(_MODULES)), _SEC_ERROR);
	}
	if ($SESS->new) update_referrer();
	if ($module_name == 'credits' || $module_name == 'privacy_policy') {
		require(CORE_PATH.'info.inc');
	} else if ($module_name == 'smilies') {
		require_once(CORE_PATH.'nbbcode.php');
		echo smilies_table('window', $_GET['field'], $_GET['form']);
		exit;
	}
	$module = $db->sql_ufetchrow('SELECT mid, title, custom_title, active, view, blocks, version FROM '.$prefix."_modules WHERE LOWER(title)='$module_name'", SQL_ASSOC);
	$modpath = isset($module['title']) ? 'modules/'.$module['title'].'/'.$file.'.php' : 'modules/'.($_POST['name'] ?? $_GET['name']).'/'.$file.'.php';
	if (!file_exists($modpath)) {
		cpg_error(sprintf(_MODULENOEXIST, (is_admin() ? $modpath : '')), 404);
	}
	$module_name = $module['title'];
	require('includes/meta.php');
	if ($module_name == 'Your_Account' || $module_name == $MAIN_CFG['global']['main_module']) {
		$module['active'] = true;
		$view = 0;
	} else {
		$view = $module['view'];
	}
	if ($module['active'] || (can_admin($module_name) && !$CLASS['member']->demo)) {
		get_lang($module_name, -1);
		$showblocks = $module['blocks'];
		if ($module['custom_title'] != '') 	{ 
			$module_title = /*defined($module['custom_title']) ? constant($module['custom_title']) :*/ $module['custom_title'];
		} else {
			$module_title = defined('_'.$module_name.'LANG') ? constant('_'.$module_name.'LANG') : preg_replace('#_#m', ' ', $module_name);
		}
		$module_version = $module['version'];
		$module_id = $module['mid'];
		unset($module, $error);
		if ($view > 0 && !is_admin()) {
			if ($view == 1 && !is_user()) {
				$error = _MODULEUSERS.($MAIN_CFG['member']['allowuserreg'] ? _MODULEUSERS2 : '' );
			} elseif ($view == 2) {
				$error = _MODULESADMINS;
			} elseif ($view > 3 && !in_group($view-3)) {
				list($groupName) = $db->sql_ufetchrow('SELECT group_name FROM '.$prefix.'_bbgroups WHERE group_id='.($view-3));
				$error = '<i>'.$groupName.'</i> '._MODULESGROUPS;
			}
		}
		if (isset($error)) {
			cpg_error('<br /><br /><strong>'._RESTRICTEDAREA.'</strong><br /><br />'.$error, 401);
		} else {
			include($modpath);
		}
	} else {
		cpg_error('<br /><br />'._MODULENOTACTIVE, 503);
	}
} else {
	// index.php
	if ($SESS->new) update_referrer();
	$module_name = $MAIN_CFG['global']['main_module'];
	$home = 1;
	$module = $db->sql_ufetchrow('SELECT mid, blocks, version FROM '.$prefix.'_modules WHERE title=\''.$module_name.'\'', SQL_ASSOC);
	$modpath = 'modules/'.$module_name.'/'.$file.'.php';
	if (file_exists($modpath)) {
		get_lang($module_name, -1);
		$showblocks = $module['blocks'];
		$module_title = '';
		$module_version = $module['version'];
		$module_id = $module['mid'];
		unset($module, $error);
		require('includes/meta.php');
		require($modpath);
	} else {
		cpg_error((is_admin() ? '<strong>'._HOMEPROBLEM.'</strong><br /><br />[ <a href="'.adminlink('modules').'">'._ADDAHOME.'</a> ]' : _HOMEPROBLEMUSER), '');
	}
}
function update_referrer() {
	global $db, $prefix, $MAIN_CFG;
	if ($MAIN_CFG['global']['httpref'] && isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
		$referer = Fix_Quotes($_SERVER['HTTP_REFERER']);
		$httprefmax = (int)$MAIN_CFG['global']['httprefmax'];
		if (preg_match('#:\/\/#m', $referer) && !preg_match($MAIN_CFG['server']['domain'], $referer)) {
			if (!$db->sql_query('UPDATE '.$prefix.'_referer SET lasttime='.gmtime().' WHERE url=\''.htmlprepare($referer).'\'', true) || !$db->sql_affectedrows()) {
				$db->sql_query('INSERT INTO '.$prefix."_referer (url, lasttime) VALUES ('".htmlprepare($referer)."', ".gmtime().")", true);
			}
			$numrows = $db->sql_count($prefix.'_referer');
			if ($numrows >= $httprefmax) {
				$db->sql_query('DELETE FROM '.$prefix.'_referer ORDER BY lasttime LIMIT '.($numrows-($httprefmax/2)));
			}
		}
	}
}
if (defined('HEADER_OPEN')) { require_once('footer.php'); }
