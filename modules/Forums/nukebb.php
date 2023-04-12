<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Forums/nukebb.php,v $
  $Revision: 9.3 $
  $Author: djmaze $
  $Date: 2005/04/10 15:57:03 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
	
define('IN_PHPBB', true);
global $phpbb_root_path;
$phpbb_root_path = 'modules/'.$module_name.'/';
include($phpbb_root_path.'common.php');

if (isset($_GET['printertopic'])) $showblocks = 0;
if ($module_title == '') {
	$mod_name = ereg_replace('_', ' ', $name);
} else {
	$mod_name = $module_title;
}
$pagetitle .= $mod_name.' '._BC_DELIM.' ';
