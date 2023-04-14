<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/admin/adwait.inc,v $
  $Revision: 9.5 $
  $Author: phoenix $
  $Date: 2007/09/12 02:33:36 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (is_active('News') && can_admin('news')) {
	$num = $db->sql_count($prefix.'_queue');
	$content .= '<b>&#8226;</b>&nbsp;<a href="'.adminlink('News&amp;file=submissions').'">'._SUBMISSIONS.'</a>: '.$num[0].'<br />';
}
