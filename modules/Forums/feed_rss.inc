<?php
/*********************************************
  CPG DragonflyCMS, Copyright (c) 2011 by DragonflyCMS Dev Team
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$forum = isset($_GET['f']) ? 't.forum_id='.intval($_GET['f']).' AND ' : '';
$result = $db->query("SELECT
	t.topic_last_post_id as id,
	t.topic_title as title,
	f.forum_name,
	p.post_time as time,
	pt.post_text as description
FROM {$db->TBL->bbforums} f, {$db->TBL->bbtopics} t
LEFT JOIN {$db->TBL->bbposts} p ON (p.post_id = t.topic_last_post_id)
LEFT JOIN {$db->TBL->bbposts_text} pt ON (pt.post_id = t.topic_last_post_id)
WHERE {$forum} t.forum_id=f.forum_id AND f.auth_view=0
ORDER BY t.topic_last_post_id DESC
LIMIT 10");

while ($row = $result->fetch_assoc()) {
	if (!SynFeed::$pubDate) SynFeed::$pubDate = $row['time'];
	$row['title'] = (empty($row['forum_name']) ? '' : $row['forum_name'] .': ') .$row['title'];
	$row['link'] = URL::index(SynFeed::$category ."&file=viewtopic&p={$row['id']}#".$row['id'], true, true);
	SynFeed::$items[] = $row;
	$row = null;
}

SynFeed::template();
