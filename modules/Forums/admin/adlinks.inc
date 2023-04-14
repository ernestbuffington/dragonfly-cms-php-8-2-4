<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Forums/admin/adlinks.inc,v $
  $Revision: 1.1 $
  $Author: djmaze $
  $Date: 2005/03/28 05:52:37 $
**********************************************/
if (can_admin($module)) {
	$mod_name = ($module == 'Forums') ? _BBFORUMS : ereg_replace('_', ' ', $module);
	$menuitems['_AMENU4'][$mod_name]['URL'] = adminlink($module);
	$menuitems['_AMENU4'][$mod_name]['IMG'] = 'forums';
	$menuitems['_AMENU4'][$mod_name]['MOD'] = $module;
}
