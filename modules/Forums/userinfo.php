<?php
/******************************************************
  Downloads Pro: Designed exclusively for Dragonfly CMS
  *****************************************************
  Copyright © 2005 - 2006 by Trevor Eckart and DJMaze
  http://dragonflycms.org

  Please see the included LICENSE.txt for the terms and
  conditions that govern your use of this module
******************************************************/

namespace Dragonfly\Modules\Forums;

abstract class Userinfo
{

	public static function displayBlock(\Poodle\Events\Event $event)
	{
		if (\Dragonfly\Modules::isActive('Forums')) {
			// Last 10 Forum Topics
			$K = \Dragonfly::getKernel();
			$userinfo = $event->target->identity;
			$K->L10N->load('Forums');
			$result = $K->SQL->query("SELECT t.topic_id, t.topic_title, f.forum_name, t.forum_id
				FROM {$K->SQL->TBL->bbtopics} t, {$K->SQL->TBL->bbforums} f
				WHERE t.forum_id=f.forum_id AND t.topic_poster={$userinfo->id} AND auth_read < 2
				ORDER BY t.topic_time DESC LIMIT 10");
			if ($result->num_rows) {
				$OUT = \Dragonfly::getKernel()->OUT;
				$OUT->assign_vars(array(
					'TOPICS_TITLE'  => $userinfo->username.'\'s '._LAST10BBTOPIC,
					'ALL_POSTS_URL' => \URL::index('Forums&file=search&search_author='.$userinfo->username),
					'ALL_POSTS'     => sprintf($K->L10N['Search_user_posts'], $userinfo->username)
				));
				while ($topic = $result->fetch_assoc()) {
					$OUT->assign_block_vars('topic', array(
						'FORUM_URL'   => \URL::index('Forums&file=viewforum&f='.$topic['forum_id']),
						'FORUM_NAME'  => $topic['forum_name'],
						'TOPIC_URL'   => \URL::index('Forums&file=viewtopic&t='.$topic['topic_id']),
						'TOPIC_TITLE' => htmlspecialchars($topic['topic_title'], ENT_NOQUOTES)
					));
				}
				$OUT->display('Forums/youraccountblock');
			}
		}
	}

}
