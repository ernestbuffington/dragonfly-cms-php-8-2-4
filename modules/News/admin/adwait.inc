<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (\Dragonfly\Modules::isActive('News') && can_admin('news')) {
	$num = $db->TBL->queue->count();
	$content .= '<a href="'.htmlspecialchars(URL::admin('News&file=submissions')).'">'._SUBMISSIONS.'</a>: '.$num.'<br />';
}
