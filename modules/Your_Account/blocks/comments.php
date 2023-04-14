<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Your_Account/blocks/comments.php,v $
  $Revision: 9.3 $
  $Author: djmaze $
  $Date: 2006/01/06 13:11:11 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

// Last 10 Comments
$result = $db->sql_query("SELECT tid, sid, subject FROM ".$prefix."_comments WHERE name='$username' ORDER BY tid DESC LIMIT 0,10");
if ($db->sql_numrows($result)) {
	echo '<br />';
	OpenTable();
	echo '<div align="left"><strong>'.$username.'\'s '._LAST10COMMENT.':</strong><ul>';
	while (list($tid, $sid, $subject) = $db->sql_fetchrow($result)) {
		echo '<li><a href="'.getlink('News&amp;file=article&amp;sid='.$sid.'#'.$tid).'">'.$subject.'</a></li>';
	}
	echo '</ul></div>';
	CloseTable();
}