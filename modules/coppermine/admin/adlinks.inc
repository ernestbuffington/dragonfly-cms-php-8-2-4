<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/coppermine/admin/adlinks.inc,v $
  $Revision: 1.1 $
  $Author: djmaze $
  $Date: 2005/03/24 01:52:37 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
if (can_admin($module)) {
	$menuitems['_AMENU8'][$module]['URL'] = adminlink($module);
	$menuitems['_AMENU8'][$module]['IMG'] = 'coppermine';
	$menuitems['_AMENU8'][$module]['MOD'] = $module;
}
