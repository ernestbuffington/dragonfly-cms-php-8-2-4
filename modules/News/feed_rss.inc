<?php
/*********************************************
  CPG DragonflyCMS, Copyright (c) 2011 by DragonflyCMS Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$where = (isset($_GET['cat']) && is_numeric($_GET['cat'])) ? 'AND catid='.intval($_GET['cat']) : '';
$result = $db->query("SELECT sid as id, title, ptime as time, hometext as description FROM {$db->TBL->stories} WHERE ptime<=".time()." {$where} ORDER BY sid DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
	if (!SynFeed::$pubDate) { SynFeed::$pubDate = $row['time']; }
	$row['link'] = URL::index(SynFeed::$category .'&file=article&sid='.$row['id'], true, true);
	SynFeed::$items[] = $row;
	//$row = null;
}
$result->free();
SynFeed::template();
