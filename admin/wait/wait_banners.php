<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/wait/wait_banners.php,v $
  $Revision: 9.6 $
  $Author: phoenix $
  $Date: 2007/09/12 02:32:43 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (is_active('Our_Sponsors') && can_admin() && $MAIN_CFG['global']['banners']) {
	$num = $db->sql_count($prefix.'_banner', 'active=0');
	$content .= '<b>&#8226;</b>&nbsp;<a href="'.adminlink('Our_Sponsors').'">'._EDITBANNER."</a>: $num<br />";
}
