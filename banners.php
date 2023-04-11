<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) {
	define('XMLFEED', 1);
	require_once('includes/cmsinit.inc');
	$bid = $_GET->uint('bid');
	$row = $db->uFetchRow("SELECT clickurl FROM {$db->TBL->banner} WHERE bid = {$bid}");
	$db->query("UPDATE {$db->TBL->banner} SET clicks = clicks + 1 WHERE bid = {$bid}");
	URL::redirect($row[0]);
}
