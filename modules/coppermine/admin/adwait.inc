<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004-2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
*******************************************************/
if (!defined('CPG_NUKE')) { exit; }
if (\Dragonfly\Modules::isActive($module) && can_admin($module)) {
	list($cpgprefix) = $db->uFetchRow("SELECT prefix FROM {$db->TBL->cpg_installs} WHERE dirname = '{$module}'");
	$count = $db->TBL->getTable($cpgprefix.'pictures')->count('approved=0');
	$content .= '<a href="'.htmlspecialchars(URL::index($module.'&file=editpics&mode=upload_approval')).'">'.ucfirst($module).' Approval</a>: ' . $count.'<br />';
}
