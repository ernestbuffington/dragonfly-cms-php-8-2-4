<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Forums;

class Archive
{
	public static function forum($forum_id, $archive_date, $archive_all = false)
	{
		$db = \Dragonfly::getKernel()->SQL;

		//
		// Those without polls and announcements ... unless told otherwise!
		//
		$sql = "SELECT t.topic_id FROM ".TOPICS_TABLE." t
			LEFT JOIN ".POSTS_TABLE." p ON (p.post_id = t.topic_last_post_id)
			WHERE t.forum_id = {$forum_id}";
		if (!$archive_all) {
			$sql .= ' AND t.topic_vote = 0 AND t.topic_type <> ' . Topic::TYPE_ANNOUNCE;
		}
		if ($archive_date) {
			$sql .= " AND COALESCE(p.post_time,0) < {$archive_date}";
		}
		$result = $db->query($sql);
		$topic_ids = array();
		while ($row = $result->fetch_row()) {
			$topic_ids[] = $row[0];
		}
		$result->free();

		if ($topic_ids) {
			$result = static::topics($topic_ids);
			$db->exec("UPDATE ".FORUMS_TABLE."
			SET archive_topics = archive_topics + {$result['topics']}, archive_posts = archive_posts + {$result['posts']}
			WHERE forum_id = {$forum_id}");
			return $result;
		}
		return array('topics' => 0, 'posts' => 0, 'words' => 0);
	}

	public static function topics(array $topic_ids)
	{
		$sql_topics = implode(',', array_map('intval',$topic_ids));
		$result = $db->query("SELECT post_id FROM ".POSTS_TABLE." WHERE topic_id IN ({$sql_topics})");
		$post_ids = array();
		while ($row = $result->fetch_row()) {
			$post_ids[] = $row[0];
		}
		$result->free();
		if ($post_ids) {
			$result = static::posts($post_ids);
			$db->exec("DELETE FROM ".TOPICS_WATCH_TABLE." WHERE topic_id IN ({$sql_topics})");
			$result['topics'] = $db->exec("UPDATE ".TOPICS_TABLE." SET topic_archive_flag = 1 WHERE topic_id IN ({$sql_topics})");
			return $result;
		}
		return array('topics' => 0, 'posts' => 0, 'words' => 0);
	}

	public static function posts(array $post_ids)
	{
		if ($post_ids) {
			$db = \Dragonfly::getKernel()->SQL;
			$post_ids = array_map('intval', $post_ids);
			$sql_post = implode(',', $post_ids);

			$result = $db->query("SELECT poster_id, COUNT(post_id) FROM ".POSTS_TABLE."
			WHERE post_id IN ({$sql_post})
			  AND poster_id > ".\Dragonfly\Identity::ANONYMOUS_ID."
			GROUP BY poster_id");
			while ($row = $result->fetch_row()) {
				$db->query("UPDATE {$db->TBL->users}
				SET user_posts = user_posts - {$row[1]}
				WHERE user_id = {$row[0]}");
			}
			$result->free();

			$columns = implode(',', array_intersect(
				array_keys($db->listColumns(POSTS_ARCHIVE_TABLE, false)),
				array_keys($db->listColumns(POSTS_TABLE, false))
			));

			$db->exec("INSERT INTO ".POSTS_ARCHIVE_TABLE." ({$columns})
			SELECT {$columns} FROM ".POSTS_TABLE." WHERE post_id IN ({$sql_post})");

			$db->exec("INSERT INTO ".POSTS_TEXT_ARCHIVE_TABLE."
			(post_id, post_subject, post_text)
			SELECT post_id, post_subject, post_text
			FROM ".POSTS_TEXT_TABLE."
			WHERE post_id IN ({$sql_post})");

			$archived_posts = $db->exec("DELETE FROM ".POSTS_TABLE." WHERE post_id IN ({$sql_post})");

			$db->exec("DELETE FROM ".POSTS_TEXT_TABLE." WHERE post_id IN ({$sql_post})");

			$words_removed = Search::removeForPosts($post_ids);

			return array('posts' => $archived_posts, 'words' => $words_removed);
		}
	}

}
