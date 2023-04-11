<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $userinfo;

$view = ' AND f.auth_view=0';
if (can_admin('forums')) {
	$view = '';
} else if (is_user() && count($userinfo['_mem_of_groups'])) {
	$result = $db->query("SELECT forum_id FROM {$db->TBL->bbauth_access}
		WHERE group_id IN (".implode(',', array_keys($userinfo['_mem_of_groups'])).")
		  AND (auth_mod = 1 OR auth_view = 1)
		GROUP BY forum_id");
	$forums = array();
	while ($row = $result->fetch_row()) {
		$forums[] = $row[0];
	}
	if (count($forums)) {
		$view = ' AND (f.auth_view=0 OR f.forum_id IN ('.implode(',', $forums).'))';
	}
}

$result = $db->query("SELECT
		t.forum_id, topic_id, topic_title, auth_view, auth_read
	FROM {$db->TBL->bbtopics} AS t, {$db->TBL->bbforums} AS f
	WHERE f.forum_id = t.forum_id {$view}
	ORDER BY topic_time DESC
	LIMIT 10");

if (!$result->num_rows) {
	$content = 'ERROR';
	return trigger_error('There are no forum posts', E_USER_NOTICE);
}

$content = '';
while (list($forum_id, $topic_id, $topic_title, $auth_view, $auth_read) = $result->fetch_row()) {
	$topic_title = htmlspecialchars(check_words($topic_title), ENT_NOQUOTES);
	$content .= '<a href="'.htmlspecialchars(URL::index('Forums&file=viewtopic&t='.$topic_id)).'">'.$topic_title.'</a><br />';
}
$content .= '<div style="text-align:center;"><br /><a href="'.URL::index('Forums').'"><strong>'.\Dragonfly::getKernel()->CFG->global->sitename.' '._BBFORUMS.'</strong></a></div>';
