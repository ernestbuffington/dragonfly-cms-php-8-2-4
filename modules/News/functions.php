<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/functions.php,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:52:38 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

function automated_news() {
	global $prefix, $multilingual, $currentlang, $db;
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_autonews WHERE time<='.gmtime());
	while ($row2 = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$title = Fix_Quotes($row2['title']);
		$hometext = Fix_Quotes($row2['hometext']);
		$bodytext = Fix_Quotes($row2['bodytext']);
		$notes = Fix_Quotes($row2['notes']);
				$db->sql_query('INSERT INTO '.$prefix.'_stories (sid, catid, aid, title, time, hometext, bodytext, comments, counter, topic, informant, notes, ihome, alanguage, acomm, haspoll, poll_id, score, ratings, associated, display_order) '.
					"VALUES (DEFAULT, '$row2[catid]', '$row2[aid]', '$title', '$row2[time]', '$hometext', '$bodytext', '0', '0', '$row2[topic]', '$row2[informant]', '$notes', '$row2[ihome]', '$row2[alanguage]', '$row2[acomm]', '0', '0', '0', '0', '$row2[associated]', 0)");
	}
	if ($db->sql_numrows($result)) {
		$db->sql_query('DELETE FROM '.$prefix.'_autonews WHERE time<='.gmtime());
	}
	$db->sql_freeresult($result);
}