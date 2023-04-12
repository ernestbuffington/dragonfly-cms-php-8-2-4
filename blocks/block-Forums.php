<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-Forums.php,v $
  $Revision: 9.9 $
  $Author: phoenix $
  $Date: 2007/09/12 02:33:13 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('Forums')) {
	$content = 'ERROR';
	return trigger_error('Forums module is inactive', E_USER_WARNING);
}

global $db, $prefix, $sitename;
$content = '';
$result = $db->sql_query("SELECT t.forum_id, topic_id, topic_title, auth_view, auth_read FROM (".$prefix."_bbtopics AS t, ".$prefix."_bbforums AS f) WHERE f.forum_id=t.forum_id ORDER BY topic_time DESC LIMIT 10");
if ($db->sql_numrows($result) < 1) {
	$content = 'ERROR';
	return trigger_error('There are no forum posts', E_USER_NOTICE);
} else {
	while (list($forum_id, $topic_id, $topic_title, $auth_view, $auth_read) = $db->sql_fetchrow($result)) {
		if ($auth_view < 2 || $auth_read < 2) {
			$topic_title = check_words($topic_title);
			$content .= '<b>&#8226;</b>&nbsp;
			<a href="'.getlink('Forums&amp;file=viewtopic&amp;t='.$topic_id).'">'.$topic_title.'</a><br />';
		}
	}
	$content .= '<div style="text-align:center;"><br /><a href="'.getlink('Forums').'"><strong>'.$sitename.' '._BBFORUMS.'</strong></a></div>';
}
