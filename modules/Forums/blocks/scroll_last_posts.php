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

$result = $db->query('SELECT
		t.topic_id, t.topic_last_post_id, t.topic_title,
		f.forum_name, f.forum_id,
		u.username, u.user_id,
		p.post_time
	FROM '.$db->TBL->bbforums.' f
	INNER JOIN '.$db->TBL->bbtopics.' t USING (forum_id)
	INNER JOIN '.$db->TBL->bbposts.' p ON (p.post_id = t.topic_last_post_id)
	LEFT JOIN '.$db->TBL->users.' u ON (u.user_id = p.poster_id)
	WHERE t.forum_id=f.forum_id '.$view.'
	ORDER BY t.topic_last_post_id DESC
	LIMIT 10');
//f.auth_view = 0); // everyone
//f.auth_view = 1); // member
//f.auth_view = 2); // private
//f.auth_view = 3); // moderator
//f.auth_view = 5); // admin

if (!$result->num_rows) {
	$content = 'ERROR';
	return trigger_error('There are no forum posts', E_USER_NOTICE);
}

$iconpath = \Dragonfly::getKernel()->OUT->theme;
if (is_file("themes/{$iconpath}/images/forums/icon_mini_message.gif")) {
	$iconpath = "themes/{$iconpath}/images/forums";
} else {
	$iconpath = "themes/default/images/forums";
}
$content = '<div style="text-align:center"><b>'.sprintf(_LASTMSGS, 10).'</b></div><marquee align="center" direction="up" scrollamount="2" scrolldelay="70" onmouseover=\'this.stop()\' onmouseout=\'this.start()\'><br /><div>';
while (list($topic_id, $topic_last_post_id, $topic_title, $forum_name, $forum_id, $username, $user_id, $post_time) = $result->fetch_row()) {
	$post_time = \Dragonfly::getKernel()->L10N->strftime('%b %d, %Y %T', $post_time);
	$topic_title = htmlspecialchars(check_words($topic_title), ENT_NOQUOTES);
	$content .= '<img src="'.$iconpath.'/icon_mini_message.gif" alt="" />
	<a href="'.htmlspecialchars(URL::index('Forums&file=viewtopic&p='.$topic_last_post_id.'#'.$topic_last_post_id)).'"><strong>'.$topic_title.'</strong></a><br />
	<i>'.sprintf(_LASTPOSTBY, '<a href="'.htmlspecialchars(\Dragonfly\Identity::getProfileURL($username)).'">'.$username.'</a>', '<a href="'.htmlspecialchars(URL::index('Forums&file=viewforum&f='.$forum_id)).'">'.$forum_name.'</a>', $post_time).'</i><br /><br />';
}
$content .= '</div></marquee>';
