<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2016 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Forums;

class Poll
{
	public
		$options  = array();

	protected
		$id       = 0,
		$topic_id = 0,
		$title    = '',
		$length   = 0, // days
		$ctime    = 0,

		$votes    = 0,
		$option_ids = array();

	function __construct($id = 0)
	{
		$id = (int)$id;
		if ($id) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$post = $SQL->uFetchRow("SELECT
				topic_id,
				vote_text,
				vote_start,
				vote_length
			FROM {$SQL->TBL->bbvote_desc}
			WHERE vote_id = {$id}");
			if (!$post) {
				throw new \Exception("Unknown poll {$id}");
			}
			$this->id = $id;
			$this->topic_id = (int)$post[0];
			$this->title    = $post[1];
			$this->ctime    = (int)$post[2];
			$this->length   = $post[3] / 86400;
			$options = $SQL->query("SELECT
				vote_option_id,
				vote_option_text,
				vote_result
			FROM {$SQL->TBL->bbvote_results}
			WHERE vote_id = {$id}");
			while ($option = $options->fetch_row()) {
//				$this->options[$option[0]] = array('id' => $option[0], 'answer' => $option[1], 'votes' => $option[2]);
				$this->options[$option[0]] = array('id' => $option[0], 'text' => $option[1], 'votes' => $option[2]);
				$this->option_ids[$option[0]] = $option[0];
			}
		} else {
			$this->ctime = time();
		}
	}

	function __get($k)
	{
		if ('closed' === $k) {
			return $this->length && $this->ctime + ($this->length * 86400) < time();
		}
		if ('votes' === $k) {
			$votes = 0;
			foreach ($this->options as $option) {
				$votes += is_array($option) ? $option['votes'] : 0;
			}
			return $votes;
		}
		if ('votes_max' === $k) {
			$votes = 0;
			foreach ($this->options as $option) {
				$votes = max(1, $votes, is_array($option) ? $option['votes'] : 0);
			}
			return $votes;
		}
		if (property_exists($this, $k)) {
			return $this->$k;
		}
	}

	function __set($k, $v)
	{
		if ('id' !== $k && 'ctime' !== $k && property_exists($this, $k)) {
			if ('title' === $k) {
				$this->$k = trim($v);
			} else {
				$this->$k = (int)$v;
			}
		}
	}

	function save()
	{
		global $board_config;
		$lang = \Dragonfly::getKernel()->L10N;

		if (!$this->title) {
			throw new \Exception($lang['Empty_poll_title']);
		} else if (!$this->topic_id) {
			throw new \Exception('Invalid topic');
		} else if (count($this->options) < 2) {
			throw new \Exception($lang['To_few_poll_options']);
		} else if (count($this->options) > $board_config['max_poll_options']) {
			throw new \Exception($lang['To_many_poll_options']);
		}

		$SQL = \Dragonfly::getKernel()->SQL;

		$del_options = array();
		if ($this->id) {
			$SQL->TBL->bbvote_desc->update(array(
				'vote_text' => $this->title,
				'vote_length' => $this->length * 86400
			), "vote_id = {$this->id}");
			foreach ($this->option_ids as $option_id) {
				if (!isset($this->options[$option_id])) {
					$del_options[] = $option_id;
				}
			}
		} else {
			$this->id = $SQL->TBL->bbvote_desc->insert(array(
				'topic_id' => $this->topic_id,
				'vote_text' => $this->title,
				'vote_start' => $this->ctime,
				'vote_length' => $this->length * 86400
			), 'vote_id');
		}

		$poll_option_id = 1;
		foreach ($this->options as $option_id => $option) {
			$option_text = trim(is_array($option) ? $option['text'] : $option);
			if ($option_text) {
				$option_text = $SQL->quote($option_text);
				if (!isset($this->option_ids[$option_id])) {
					$SQL->query("INSERT INTO {$SQL->TBL->bbvote_results}
					(vote_id, vote_option_id, vote_option_text)
					VALUES
					({$this->id}, {$poll_option_id}, {$option_text})");
				} else {
					$SQL->query("UPDATE {$SQL->TBL->bbvote_results}
					SET vote_option_text = {$option_text}
					WHERE vote_option_id = {$option_id}
					  AND vote_id = {$this->id}");
				}
				++$poll_option_id;
			}
		}

		if ($del_options) {
			$SQL->query("DELETE FROM {$SQL->TBL->bbvote_results}
			WHERE vote_id = {$this->id}
			  AND vote_option_id IN (".implode(',',$del_options).")");
		}

		return $this->id;
	}

	public static function deleteFromTopics($topic_id)
	{
		if (!$topic_id) { return; }
		if (is_array($topic_id)) {
			$where = 'topic_id IN (' . implode(',', array_map('intval', $topic_id)) . ')';
		} else {
			$where = 'topic_id = ' . intval($topic_id);
		}
		$SQL = \Dragonfly::getKernel()->SQL;
		$result = $SQL->query("SELECT vote_id FROM {$SQL->TBL->bbvote_desc} WHERE {$where}");
		if ($result->num_rows) {
			$SQL->TBL->bbtopics->update(array('topic_vote' => false), $where);
			$vote_ids = array();
			while ($row = $result->fetch_row()) { $vote_ids[] = $row[0]; }
			$where = 'vote_id IN (' . implode(',', $vote_ids) . ')';
			$SQL->TBL->bbvote_voters->delete($where);
			$SQL->TBL->bbvote_results->delete($where);
			$SQL->TBL->bbvote_desc->delete($where);
		}
	}

	public function vote($option_id, $identity_id = null)
	{
		$option_id = (int)$option_id;
		$identity_id = intval($identity_id) ?: \Dragonfly::getKernel()->IDENTITY->id;
		if ($identity_id && isset($this->options[$option_id]) && !$this->hasVoted($identity_id)) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$SQL->query("UPDATE {$SQL->TBL->bbvote_results}
				SET vote_result = vote_result + 1
				WHERE vote_id = {$this->id}
				  AND vote_option_id = {$option_id}");
			$SQL->TBL->bbvote_voters->insert(array(
				'vote_id' => $this->id,
				'vote_user_id' => $identity_id,
				'vote_user_ip' => $_SERVER['REMOTE_ADDR']
			));
			return true;
		}
		return false;
	}

	public function hasVoted($identity_id = null)
	{
		$identity_id = intval($identity_id) ?: \Dragonfly::getKernel()->IDENTITY->id;
		$SQL = \Dragonfly::getKernel()->SQL;
		return $identity_id && $this->id
			&& $SQL->uFetchRow("SELECT vote_id FROM {$SQL->TBL->bbvote_voters} WHERE vote_id = {$this->id} AND vote_user_id = {$identity_id}");
	}

}
