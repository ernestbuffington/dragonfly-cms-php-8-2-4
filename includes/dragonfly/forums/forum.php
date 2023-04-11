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

class Forum implements \ArrayAccess
{
	const
		STATUS_UNLOCKED = 0, // FORUM_UNLOCKED
		STATUS_LOCKED   = 1, // FORUM_LOCKED

		TYPE_NORMAL     = 0,
		TYPE_PARENT     = 1,
		TYPE_URL_LOCAL  = 2,
		TYPE_URL_REMOTE = 3;

	protected
		$data = array(
			'forum_id'           => 0,
			'cat_id'             => 0,
			'parent_id'          => 0,
			'forum_name'         => '',
			'forum_desc'         => '',
			'forum_status'       => 0, // STATUS_UNLOCKED,
			'forum_order'        => 0,
			'forum_posts'        => 0,
			'forum_topics'       => 0,
			'forum_last_post_id' => 0,
			'forum_type'         => 0,
			'forum_link'         => '',

			'auth_view'          => Auth::LEVEL_ALL,
			'auth_read'          => Auth::LEVEL_ALL,
			'auth_post'          => Auth::LEVEL_ALL,
			'auth_reply'         => Auth::LEVEL_ALL,
			'auth_edit'          => Auth::LEVEL_REG,
			'auth_delete'        => Auth::LEVEL_REG,
			'auth_sticky'        => Auth::LEVEL_MOD,
			'auth_announce'      => Auth::LEVEL_MOD,
			'auth_vote'          => Auth::LEVEL_REG,
			'auth_pollcreate'    => Auth::LEVEL_REG,
			'auth_attachments'   => Auth::LEVEL_REG,
			'auth_download'      => Auth::LEVEL_REG,

			'archive_enable'     => false,
			'archive_freq'       => 1, // per N days
			'archive_days'       => 7, // older then N days
			'archive_next'       => 0,
			'archive_topics'     => 0,
			'archive_posts'      => 0,

			'prune_enable'       => false,
			'prune_freq'         => 1, // per N days
			'prune_days'         => 7, // older then N days
			'prune_next'         => 0,
		),
		$uri,
		$archive_uri,
		$category;

	function __construct($forum_id=0)
	{
		if ($forum_id) {
			$forum_id = (int) $forum_id;
			$SQL = \Dragonfly::getKernel()->SQL;
			$data = $SQL->uFetchAssoc('SELECT * FROM '. FORUMS_TABLE. ' WHERE forum_id = '. $forum_id);
			if (!$data) {
				throw new \Exception(\Dragonfly::getKernel()->L10N->get('Forum_not_exist'));
//				throw new \Exception("Unknown forum_id {$forum_id}");
			}
			foreach ($data as $k => $v) {
				$this->__set($k, $v);
			}
		}
	}

	protected function getKey($k)
	{
		if (array_key_exists($k, $this->data)) {
			return $k;
		}
		if (array_key_exists($v = 'forum_'.$k, $this->data)) {
			return $v;
		}
		$bt = \Dragonfly\Debugger::backtrace(2,1);
		trigger_error("Undefined property: {$k} by: {$bt[0]['file']}#{$bt[0]['line']}");
	}

	function __get($k)
	{
		if ('uri' === $k) {
			return \URL::index("&file=viewforum&f={$this->data['forum_id']}");
		}
		if ('archive_uri' === $k) {
			return $this->data['archive_topics'] ? \URL::index("&file=viewarchive&f={$this->data['forum_id']}") : false;
		}
		if ('cat_title' === $k || 'cat_order' === $k) {
			if (!$this->category) {
				$SQL = \Dragonfly::getKernel()->SQL;
				$this->category = $SQL->uFetchAssoc('SELECT cat_title, cat_order FROM '. CATEGORIES_TABLE. ' WHERE cat_id = '. $this->data['cat_id']);
			}
			return $this->category[$k];
		}
		if ($k = $this->getKey($k)) {
			return $this->data[$k];
		}
	}

	function __set($k, $v)
	{
		if ($k = $this->getKey($k)) {
			if (!is_null($v)) {
				if (is_int($this->data[$k])) {
					$v = (int)$v;
				} else if (is_bool($this->data[$k])) {
					$v = !!$v;
				} else {
					$v = trim($v);
				}
				$this->data[$k] = $v;
			}
		}
	}

	function __isset($k)
	{
		if ('cat_title' === $k || 'cat_order' === $k) {
			return true;
		}
		return (array_key_exists($k, $this->data) || array_key_exists($k = 'forum_'.$k, $this->data))
			&& !is_null($this->data[$k]);
	}

	// ArrayAccess
	public function offsetExists($k)  { return array_key_exists($k, $this->data); }
	public function offsetGet($k)     { return $this->__get($k); }
	public function offsetSet($k, $v) { $this->__set($k, $v); }
	public function offsetUnset($k)   {}

	public function getTypeOptions()
	{
		return array(
			array(
				'name' => 'Normal Forum',
				'value' => 0,
				'current' => (0 == $this->type),
				'disabled' => (1 == $this->type)
			),
			array(
				'name' => 'Parent Forum',
				'value' => 1,
				'current' => (1 == $this->type),
				'disabled' => true
			),
			array(
				'name' => 'Local URL',
				'value' => 2,
				'current' => (2 == $this->type),
				'disabled' => (1 == $this->type)
			),
			array(
				'name' => 'Remote URL',
				'value' => 3,
				'current' => (3 == $this->type),
				'disabled' => (1 == $this->type)
			),
		);
	}

	public function save()
	{
		if (!$this->data['cat_id'] || !$this->data['forum_name']) {
			return false;
		}

		$SQL = \Dragonfly::getKernel()->SQL;

		if (!$this->data['forum_order']) {
			list($next_order) = $SQL->uFetchRow('SELECT MAX(forum_order) FROM '. FORUMS_TABLE. ' WHERE cat_id = '. $this->data['cat_id']);
			$this->data['forum_order'] = $next_order + 10;
		}

		$this->data['archive_freq'] = max(1,$this->data['archive_freq']);
		$this->data['archive_days'] = max(1,$this->data['archive_days']);
		$this->data['prune_freq']   = max(1,$this->data['prune_freq']);
		$this->data['prune_days']   = max(1,$this->data['prune_days']);

		$data = $this->data;
		$data['forum_name'] = $SQL->quote($data['forum_name']);
		$data['forum_desc'] = $SQL->quote($data['forum_desc']);
		$data['forum_link'] = $SQL->quote($data['forum_link']);
		$data['archive_enable'] = (int)$data['archive_enable'];
		$data['prune_enable']   = (int)$data['prune_enable'];

		if (!$data['forum_id'])
		{
			unset($data['forum_id']);
			$SQL->query('INSERT INTO '. FORUMS_TABLE. ' ('.implode(',',array_keys($data)).') VALUES ('.implode(',',$data).')');
			$this->data['forum_id'] = $SQL->insert_id('forum_id');
			if ($data['parent_id']) {
				$SQL->query('UPDATE '.FORUMS_TABLE." SET forum_type = 1 WHERE forum_id = {$data['parent_id']} AND forum_type = 0");
			}
		}
		else
		{
			# grab the original data
			list ($category, $parent) = $SQL->uFetchRow('SELECT cat_id, parent_id FROM '.FORUMS_TABLE.' WHERE forum_id = '.$data['forum_id']);

			if ($category != $data['cat_id']) {
				$data['parent_id'] = 0;
			} else {
				# avoid be subforum of it self
				$data['parent_id'] = $data['parent_id'] == $data['forum_id'] ? $parent : $data['parent_id'];
			}

			# moving parent
			if ($data['parent_id'] != $parent) {
				# select the parent of the selected parent
				list ($parent2) = $SQL->uFetchRow('SELECT parent_id FROM '.FORUMS_TABLE.' WHERE forum_id = '.$data['parent_id']);
				# prevent subforums loops and skip everything
				if ($parent2 == $data['forum_id']) {
					$data['parent_id'] = $parent;
				} else {
					# Check if parent has anymore subforums
					$result = $SQL->query('SELECT forum_id FROM '.FORUMS_TABLE.' WHERE forum_id <> '.$data['forum_id'].' AND parent_id = '.$parent);
					# If none set it from parent to normal
					if (!$result->num_rows) {
						$SQL->query('UPDATE '.FORUMS_TABLE.' SET forum_type = 0 WHERE forum_id = '.$parent.' AND forum_type = 1');
					}
					# just make sure parent is set to parent
					$SQL->query('UPDATE '.FORUMS_TABLE." SET forum_type = 1 WHERE forum_id = {$data['parent_id']} AND forum_type = 0");
				}
			}

			$SQL->query('UPDATE '. FORUMS_TABLE. " SET
				forum_name = ". $data['forum_name']. ",
				cat_id = ". $data['cat_id']. ",
				parent_id = ". $data['parent_id']. ",
				forum_desc = ". $data['forum_desc']. ",
				forum_status = ". $data['forum_status']. ",
				forum_link = ". $data['forum_link']. ",
				archive_enable = ". $data['archive_enable']. ",
				archive_freq = ". $data['archive_freq']. ",
				archive_days = ". $data['archive_days']. ",
				prune_enable = ". $data['prune_enable']. ",
				prune_freq = ". $data['prune_freq']. ",
				prune_days = ". $data['prune_days']. "
			WHERE forum_id = ". $this->data['forum_id']);
		}

		\BoardCache::cacheDelete('categories');
		\BoardCache::cacheDelete('forums');
		return true;
	}

	//
	// This function will call the archive function with the necessary info.
	//
	public function autoArchive()
	{
		if ($this->archive_enable && $this->archive_next < time() && $this->archive_freq && $this->archive_days) {
			$this->archive(time() - ($this->archive_days * 86400));
			$this->sync();
			$SQL = \Dragonfly::getKernel()->SQL;
			$SQL->exec("UPDATE ".FORUMS_TABLE."
			SET archive_next = ".(time() + ($this->archive_freq * 86400))."
			WHERE forum_id = {$this->data['forum_id']}");
		}
	}

	public function archive($archive_date, $archive_all = false)
	{
		return Archive::forum($this->data['forum_id'], $archive_date, $archive_all);
	}

	//
	// This function will call the prune function with the necessary info.
	//
	public function autoPrune()
	{
		if ($this->prune_enable && $this->prune_next < time() && $this->prune_freq && $this->prune_days) {
			$this->prune(time() - ($this->prune_days * 86400));
			$this->sync();
			$SQL = \Dragonfly::getKernel()->SQL;
			$SQL->exec("UPDATE ".FORUMS_TABLE."
			SET prune_next = ".(time() + ($this->prune_freq * 86400))."
			WHERE forum_id = {$this->data['forum_id']}");
		}
	}

	public function prune($prune_date, $prune_all = false)
	{
		$SQL = \Dragonfly::getKernel()->SQL;

		$forum_id = $this->data['forum_id'];
		$prune_date = (int)$prune_date;

		$prune_all = ($prune_all) ? '' : 'AND t.topic_vote = 0 AND t.topic_type <> ' . \Dragonfly\Forums\Topic::TYPE_ANNOUNCE;
		//
		// Those without polls and announcements ... unless told otherwise!
		//
		$sql = "SELECT t.topic_id FROM " . POSTS_TABLE . " p, " . TOPICS_TABLE . " t
			WHERE t.forum_id = {$forum_id}
			  {$prune_all}
			  AND (p.post_id = t.topic_last_post_id OR t.topic_last_post_id = 0)";
		if ($prune_date) {
			$sql .= " AND p.post_time < {$prune_date}";
		}
		$result = $SQL->query("{$sql} GROUP BY t.topic_id");
		if ($result->num_rows) {
			$topic_ids = array();
			while ($row = $result->fetch_row()) { $topic_ids[] = $row[0]; }

			Poll::deleteFromTopics($topic_ids);

			$topic_ids = implode(',',$topic_ids);
			$result = $SQL->query("SELECT post_id FROM " . POSTS_TABLE . " WHERE topic_id IN ({$topic_ids})");
			if ($result->num_rows) {
				$post_ids = array();
				while ($row = $result->fetch_row()) {
					$post_ids[] = $row[0];
				}
				$post_ids_sql = implode(',',$post_ids);
				$SQL->exec("DELETE FROM " . TOPICS_WATCH_TABLE . " WHERE topic_id IN ({$topic_ids})");
				$SQL->exec("DELETE FROM " . TOPICS_TABLE . " WHERE topic_id IN ({$topic_ids})");
				$SQL->exec("DELETE FROM " . POSTS_TABLE . " WHERE post_id IN ({$post_ids_sql})");
				$SQL->exec("DELETE FROM " . POSTS_TEXT_TABLE . " WHERE post_id IN ({$post_ids_sql})");
				\Dragonfly\Forums\Search::removeForPosts($post_ids);
				\Dragonfly\Forums\Attachments::deleteFromPosts($post_ids);
				return array('topics' => count($topic_ids), 'posts' => count($post_ids));
			}
		}
		return array('topics' => 0, 'posts' => 0);
	}

	public function sync()
	{
		$SQL = \Dragonfly::getKernel()->SQL;

		// Sync posts

		$SQL->exec("UPDATE " . POSTS_TABLE . " p SET
			post_attachment = (SELECT CASE WHEN COUNT(attach_id) > 0 THEN 1 ELSE 0 END FROM " . ATTACHMENTS_TABLE . " a WHERE a.post_id = p.post_id)
		WHERE forum_id = {$this->data['forum_id']}");

		$SQL->exec("UPDATE " . POSTS_ARCHIVE_TABLE . " p SET
			post_attachment = (SELECT CASE WHEN COUNT(attach_id) > 0 THEN 1 ELSE 0 END FROM " . ATTACHMENTS_TABLE . " a WHERE a.post_id = p.post_id)
		WHERE forum_id = {$this->data['forum_id']}");

		// Sync topics

		$SQL->exec("UPDATE " . TOPICS_TABLE . " t SET
			topic_replies = (SELECT COUNT(post_id) - 1 FROM " . POSTS_TABLE . " p1 WHERE p1.topic_id = t.topic_id),
			topic_last_post_id = (SELECT COALESCE(MAX(post_id), 0) FROM " . POSTS_TABLE . " p2 WHERE p2.topic_id = t.topic_id),
			topic_first_post_id = (SELECT COALESCE(MIN(post_id), 0) FROM " . POSTS_TABLE . " p3 WHERE p3.topic_id = t.topic_id),
			topic_attachment = (SELECT COALESCE(MAX(post_attachment), 0) FROM " . POSTS_TABLE . " p4 WHERE p4.topic_id = t.topic_id)
		WHERE forum_id = {$this->data['forum_id']} AND topic_archive_flag = 0 AND topic_status IN (0, 1)");

		$SQL->exec("UPDATE " . TOPICS_TABLE . " t SET
			topic_replies = (SELECT COUNT(post_id) - 1 FROM " . POSTS_ARCHIVE_TABLE . " p1 WHERE p1.topic_id = t.topic_id),
			topic_last_post_id = (SELECT COALESCE(MAX(post_id), 0) FROM " . POSTS_ARCHIVE_TABLE . " p2 WHERE p2.topic_id = t.topic_id),
			topic_first_post_id = (SELECT COALESCE(MIN(post_id), 0) FROM " . POSTS_ARCHIVE_TABLE . " p3 WHERE p3.topic_id = t.topic_id),
			topic_attachment = (SELECT COALESCE(MAX(post_attachment), 0) FROM " . POSTS_ARCHIVE_TABLE . " p4 WHERE p4.topic_id = t.topic_id)
		WHERE forum_id = {$this->data['forum_id']} AND topic_archive_flag = 1 AND topic_status IN (0, 1)");

		$SQL->exec("DELETE FROM " . TOPICS_TABLE . " WHERE forum_id = {$this->data['forum_id']} AND topic_status IN (0, 1) AND topic_replies = -1");

		// Sync forum

		$row = $SQL->uFetchRow("SELECT
			MAX(post_id),
			COUNT(post_id)
		FROM " . POSTS_TABLE . "
		WHERE forum_id = {$this->data['forum_id']}");
		$this->data['forum_last_post_id'] = (int)$row[0];
		$this->data['forum_posts'] = (int)$row[1];

		$row = $SQL->uFetchRow("SELECT
			COUNT(post_id)
		FROM " . POSTS_ARCHIVE_TABLE . "
		WHERE forum_id = {$this->data['forum_id']}");
		$this->data['archive_posts'] = (int)$row[0];

		$row = $SQL->uFetchRow("SELECT
			COUNT(topic_id)
		FROM " . TOPICS_TABLE . "
		WHERE forum_id = {$this->data['forum_id']}
		  AND topic_archive_flag = 0");
		$this->data['forum_topics'] = (int)$row[0];

		$row = $SQL->uFetchRow("SELECT
			COUNT(topic_id)
		FROM " . TOPICS_TABLE . "
		WHERE forum_id = {$this->data['forum_id']}
		  AND topic_archive_flag = 1");
		$this->data['archive_topics'] = (int)$row[0];

		$SQL->exec("UPDATE " . FORUMS_TABLE . " SET
			forum_last_post_id = {$this->data['forum_last_post_id']},
			forum_posts = {$this->data['forum_posts']},
			forum_topics = {$this->data['forum_topics']},
			archive_posts = {$this->data['archive_posts']},
			archive_topics = {$this->data['archive_topics']}
		WHERE forum_id = {$this->data['forum_id']}");
	}

	private $auth = null;
	public function getUserPermissions()
	{
		if (!isset($this->auth)) {
			$this->auth = Auth::forType(Auth::ALL, $this->data['forum_id'], $this);
		}
		return $this->auth;
	}

	public function userAuth($action)
	{
		$auth = $this->getUserPermissions();
		return !empty($auth["auth_{$action}"]);
	}

	public function userCanWatch()
	{
		return \Dragonfly::getKernel()->IDENTITY->isMember() && $GLOBALS['board_config']['allow_forum_watch'];
	}

	public function userWatch()
	{
		if (empty($GLOBALS['board_config']['allow_forum_watch'])) {
			return false;
		}

		$userinfo = \Dragonfly::getKernel()->IDENTITY;
		if (!$userinfo->isMember()) {
			return false;
		}

		$SQL  = \Dragonfly::getKernel()->SQL;
		$lang = \Dragonfly::getKernel()->OUT->L10N;

		list($notify_status) = $SQL->uFetchRow('SELECT notify_status FROM '.FORUMS_WATCH_TABLE.'
		WHERE forum_id = '.$this->data['forum_id'].' AND user_id = '.$userinfo->id);
		if (null === $notify_status) {
			# User is not watching this forum
			if (isset($_GET['watch'])) {
				$SQL->query('INSERT INTO '.FORUMS_WATCH_TABLE.'
				(user_id, forum_id, notify_status)
				VALUES
				('.$userinfo->id.', '.$this->data['forum_id'].', 0)');
				\Poodle\Notify::success($lang['You_are_watching_forum']);
				\URL::redirect(\URL::index('&file=viewforum&f='.$this->data['forum_id']));
			}
			return false;
		}
		# User is already watching this forum
		if (isset($_GET['unwatch'])) {
			$SQL->query('DELETE FROM '.FORUMS_WATCH_TABLE.'
			WHERE forum_id = '.$this->data['forum_id'].' AND user_id = '.$userinfo->id);
			\Poodle\Notify::warning($lang['No_longer_watching_forum']);
			\URL::redirect(\URL::index('&file=viewforum&f='.$this->data['forum_id']));
		}
		if ($notify_status) {
			$SQL->query('UPDATE '.FORUMS_WATCH_TABLE.'
			SET notify_status = 0
			WHERE forum_id = '.$this->data['forum_id'].' AND user_id = '.$userinfo->id);
		}
		return true;
	}

	public function isLocked()
	{
		return (self::STATUS_LOCKED == $this->data['forum_status']);
	}

	public function getNewTopicUri()
	{
		return \URL::index("&file=posting&mode=newtopic&f={$this->data['forum_id']}");
	}

	public function isSubForum()
	{
		return (0 < $this->data['parent_id']);
	}

}
