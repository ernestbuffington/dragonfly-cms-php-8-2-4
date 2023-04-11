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

class Topic implements \ArrayAccess
{
	const
		STATUS_UNLOCKED = 0, // TOPIC_UNLOCKED
		STATUS_LOCKED   = 1, // TOPIC_LOCKED
		STATUS_MOVED    = 2, // TOPIC_MOVED

		TYPE_NORMAL          = 0, // POST_NORMAL
		TYPE_STICKY          = 1, // POST_STICKY
		TYPE_ANNOUNCE        = 2, // POST_ANNOUNCE
		TYPE_GLOBAL_ANNOUNCE = 3; // POST_GLOBAL_ANNOUNCE

	protected
		$data = array(
			'topic_id'            => 0,
			'forum_id'            => 0,
			'topic_title'         => '',
			'topic_poster'        => 0,
			'topic_time'          => 0,
			'topic_views'         => 0,
			'topic_replies'       => 0,
			'topic_status'        => 0, // STATUS_UNLOCKED
			'topic_vote'          => false,
			'topic_type'          => 0, // TYPE_NORMAL
			'topic_last_post_id'  => 0,
			'topic_first_post_id' => 0,
			'topic_moved_id'      => 0,
			'topic_attachment'    => false,
			'icon_id'             => 0,
			'topic_archive_flag'  => false,
			'topic_archive_id'    => 0,
		),
		$url   = null,
		$forum = null,
		$poll  = null,
		$poll_id = null;

	function __construct($topic_id=0)
	{
		if ($topic_id) {
			$topic_id = (int)$topic_id;
			$SQL = \Dragonfly::getKernel()->SQL;
			$data = $SQL->uFetchAssoc('SELECT * FROM '. TOPICS_TABLE. ' WHERE topic_id = '. $topic_id);
			if (!$data) {
				throw new \Exception(\Dragonfly::getKernel()->L10N->get('Topic_post_not_exist'));
//				throw new \Exception("Unknown topic_id {$topic_id}");
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
		if (array_key_exists($v = 'topic_'.$k, $this->data)) {
			return $v;
		}
		$bt = \Dragonfly\Debugger::backtrace(2,1);
		trigger_error("Undefined property: {$k} by: {$bt[0]['file']}#{$bt[0]['line']}");
	}

	function __get($k)
	{
		if ('forum' === $k) {
			if (!$this->forum && $this->data['forum_id']) {
				$this->forum = new Forum($this->data['forum_id']);
			}
			return $this->forum;
		}
		if ('poll_id' === $k) {
			if ($this->poll) {
				return $this->poll->id;
			}
			if (!$this->poll_id && $this->data['topic_vote']) {
				$this->poll_id = static::getPollId($this->data['topic_id']);
			}
			return $this->poll_id;
		}
		if ('poll' === $k) {
			if (!$this->poll && $poll_id = $this->__get('poll_id')) {
				$this->poll = new Poll($poll_id);
			}
			return $this->poll;
		}
		if ('url' === $k) {
			if (!$this->url && $this->data['topic_id']) {
				$this->url = \URL::index("&file=viewtopic&t={$this->data['topic_id']}");
			}
			return $this->url;
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
		if (('forum' === $k && $this->data['forum_id'])
		 || ('url' === $k && $this->data['topic_id']))
		{
			return true;
		}
		return (array_key_exists($k, $this->data) || array_key_exists($k = 'topic_'.$k, $this->data))
			&& !is_null($this->data[$k]);
	}

	// ArrayAccess
	public function offsetExists($k)  { return array_key_exists($k, $this->data); }
	public function offsetGet($k)     { return $this->__get($k); }
	public function offsetSet($k, $v) { $this->__set($k, $v); }
	public function offsetUnset($k)   {}

	public function save()
	{
		if (!$this->data['forum_id'] || !$this->data['topic_title']) {
			return false;
		}

		$SQL = \Dragonfly::getKernel()->SQL;
/*
		if (!$this->data['forum_order'])
		{
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
*/
		return true;
	}

	public function incViews()
	{
		# Update the topic view counter
		if ($this->data['topic_id']) {
			\Dragonfly::getKernel()->SQL->query("UPDATE ".TOPICS_TABLE."
				SET topic_views = topic_views + 1
				WHERE topic_id = {$this->data['topic_id']}");
		}
	}

	public function isLocked()
	{
		return (self::STATUS_LOCKED == $this->data['topic_status']);
	}

	public function userCanWatch()
	{
		return \Dragonfly::getKernel()->IDENTITY->isMember() && !$this->data['topic_archive_flag'];
	}

	private $is_watching = null;
	public function userIsWatching()
	{
		if (is_null($this->is_watching)) {
			$SQL = \Dragonfly::getKernel()->SQL;
			if ($this->userCanWatch()) {
				list($notify_status) = $SQL->uFetchRow("SELECT notify_status
					FROM ".TOPICS_WATCH_TABLE."
					WHERE topic_id = {$this->data['topic_id']}
					  AND user_id = " . is_user());
				$this->is_watching = (null !== $notify_status);
				if ($notify_status) {
					$SQL->query("UPDATE ".TOPICS_WATCH_TABLE."
						SET notify_status = 0
						WHERE topic_id = {$this->data['topic_id']}
						  AND user_id = " . is_user());
				}
			} else {
				$this->is_watching = false;
			}
		}
		return $this->is_watching;
	}

	public static function delete($id)
	{
		$id  = (int)$id;
		$SQL = \Dragonfly::getKernel()->SQL;
		$topic = $SQL->uFetchRow("SELECT forum_id FROM ". TOPICS_TABLE. " WHERE topic_id = {$id}");
		if ($topic) {
			$SQL->exec("DELETE FROM " . TOPICS_TABLE . " WHERE topic_id = {$id} OR topic_moved_id = {$id}");
			$SQL->exec("DELETE FROM " . TOPICS_WATCH_TABLE . " WHERE topic_id = {$id}");
			$SQL->exec("UPDATE ".FORUMS_TABLE." SET forum_topics = forum_topics -1 WHERE forum_id = {$topic[0]}");
			Poll::deleteFromTopics($id);
		}
	}

	public function sync()
	{
		if ($this->data['topic_status'] > 1) {
			return;
		}

		$SQL = \Dragonfly::getKernel()->SQL;

		$posts_table = ($this->data['topic_archive_flag'] ? POSTS_ARCHIVE_TABLE : POSTS_TABLE);

		// Sync posts

		$SQL->exec("UPDATE {$posts_table} p SET
			post_attachment = (SELECT CASE WHEN COUNT(attach_id) > 0 THEN 1 ELSE 0 END FROM " . ATTACHMENTS_TABLE . " a WHERE a.post_id = p.post_id)
		WHERE topic_id = {$this->data['topic_id']}");

		// Sync topic

		$data = $SQL->uFetchRow("SELECT
			COUNT(post_id) - 1,
			COALESCE(MAX(post_id), 0),
			COALESCE(MIN(post_id), 0),
			COALESCE(MAX(post_attachment), 0)
		FROM {$posts_table}
		WHERE topic_id = {$this->data['topic_id']}");
		if (0 > $data[0]) {
			static::delete($this->data['topic_id']);
		} else {
			$this->data['topic_replies'] = (int)$data[0];
			$this->data['topic_last_post_id'] = (int)$data[1];
			$this->data['topic_first_post_id'] = (int)$data[2];
			$this->data['topic_attachment'] = !!$data[3];
			$SQL->exec("UPDATE " . TOPICS_TABLE . " SET
				topic_replies = {$data[0]},
				topic_last_post_id = {$data[1]},
				topic_first_post_id = {$data[2]},
				topic_attachment = {$data[3]}
			WHERE topic_id = {$this->data['topic_id']}");
		}
	}

	public function getReplyUri()
	{
		if (!$this->data['topic_archive_flag']) {
			return \URL::index("&file=posting&mode=reply&t={$this->data['topic_id']}");
		}
		if (!$this->data['topic_archive_id']) {
			return $_SERVER['REQUEST_URI'].'#archive_reply';
		}
	}

	public static function getPollId($topic_id)
	{
		$topic_id = (int)$topic_id;
		$SQL = \Dragonfly::getKernel()->SQL;
		$poll = $SQL->uFetchRow("SELECT vote_id FROM {$SQL->TBL->bbvote_desc} WHERE topic_id = {$topic_id}");
		return $poll ? (int)$poll[0] : 0;
	}

}
