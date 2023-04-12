<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Your_Account/blocks/news.php,v $
  $Revision: 9.3 $
  $Author: djmaze $
  $Date: 2006/01/06 13:11:11 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

// Last 10 Submissions
$result = $db->sql_query("SELECT sid, title FROM ".$prefix."_stories WHERE informant='$username' ORDER BY sid DESC LIMIT 0,10");
if ($db->sql_numrows($result) > 0) {
	echo '<br />';
	OpenTable();
	echo '<div align="left"><strong>'.$username.'\'s '._LAST10SUBMISSION.':</strong><ul>';
	while (list($sid, $title) = $db->sql_fetchrow($result)) {
		echo '<li><a href="'.getlink('News&amp;file=article&amp;sid='.$sid).'">'.$title.'</a></li>';
	}
	echo '</ul></div>';
	CloseTable();
}