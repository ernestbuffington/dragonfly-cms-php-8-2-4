<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004-2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
  
  $Source: /cvs/html/modules/coppermine/admin/adwait.inc,v $
  $Revision: 1.9 $
  $Author: phoenix $
  $Date: 2007/09/12 02:35:04 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
*******************************************************/
if (!defined('CPG_NUKE')) { exit; }
if (is_active($module) && can_admin($module)) {
	list($cpg_prefix) = $db->sql_ufetchrow('SELECT prefix FROM '.$prefix."_cpg_installs WHERE dirname = '$module'");
	list($coppermine_lang_dir) = $db->sql_ufetchrow("SELECT dirname FROM ".$cpg_prefix."installs WHERE cpg_id=1", SQL_NUM, __FILE__, __LINE__);
	get_lang($coppermine_lang_dir, __FILE__, __LINE__);
    $count = $db->sql_count($cpg_prefix.'pictures', "approved=0");
    $content .= '<b>&#8226;</b>&nbsp;<a href="'.getlink($module.'&amp;file=editpics&amp;mode=upload_approval').'">'.ucfirst($module).' Approval</a>: ' . $count.'<br />';
}
