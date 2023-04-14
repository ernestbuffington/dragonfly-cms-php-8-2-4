<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Your_Account/blocks/forums.php,v $
  $Revision: 9.9 $
  $Author: phoenix $
  $Date: 2006/08/17 13:09:56 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

// Last 10 Forum Topics
if (is_active('Forums')) {
	if (!defined('IN_PHPBB') && !defined('PHPBB_INSTALLED')) {
		define('IN_PHPBB', true);
		define('PHPBB_INSTALLED', true);
		$phpbb_root_path = "./modules/Forums/";
		require_once($phpbb_root_path.'common.php');
	}
	get_lang('Forums');
	global $lang;
	$result = $db->sql_query('SELECT t.topic_id, t.topic_title, f.forum_name, t.forum_id FROM '.$prefix.'_bbtopics t, '.$prefix.'_bbforums f'
				." WHERE t.forum_id=f.forum_id AND t.topic_poster='$userinfo[user_id]' AND auth_read < 2 ORDER BY t.topic_time DESC LIMIT 0,10");
	echo '<br />';
	OpenTable();
	if ($db->sql_numrows($result)) {
		echo '<div align="left"><strong>'.$username.'\'s '._LAST10BBTOPIC.':</strong><ul>';
		while (list($topic_id, $topic_title, $forum_name, $forum_id) = $db->sql_fetchrow($result)) {
			echo '<li><a href="'.getlink('Forums&amp;file=viewforum&amp;f='.$forum_id).'">'.$forum_name.'</a> &#187; <a href="'.getlink('Forums&amp;file=viewtopic&amp;t='.$topic_id).'">'.$topic_title.'</a></li>';
		}
		echo '</ul></div>';
	}
	echo '<a href="'.getlink('Forums&amp;file=search&amp;search_author='.$username).'">'.sprintf($lang['Search_user_posts'], $username).'</a>';
	CloseTable();
}