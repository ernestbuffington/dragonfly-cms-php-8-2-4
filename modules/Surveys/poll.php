<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\Surveys;

class Poll
{
	protected
		$id             = 0,
		$title          = '',
		$vote_anonymous = false,
		$allow_comments = false,
		$allow_reply    = false,
		$ptime          = 0,
		$votes          = 0,
		$voted          = null,
		$artid          = 0,
		$language       = '',

		$reply_uri      = '',

		$comments_count,
		$comments,
		$results = array();

	function __construct($id=0)
	{
		$id = (int)$id;
		if ($id) {
			$K    = \Dragonfly::getKernel();
			$db   = $K->SQL;
			$L10N = $K->L10N;

			$poll = $db->uFetchAssoc("SELECT
				poll_title,
				poll_ptime,
				anonymous,
				comments,
				voters,
				planguage,
				artid
			FROM {$db->TBL->poll_desc}
			WHERE poll_id = {$id}");
			if (!$poll) {
				throw new \Exception(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_Surveys)));
			}

			$this->id             = $id;
			$this->title          = $poll['poll_title'];
			$this->vote_anonymous = !!$poll['anonymous'];
			$this->allow_comments = ($poll['comments'] && $K->CFG->global->pollcomm);
			$this->allow_reply    = ($this->allow_comments && $K->IDENTITY->isMember());
			$this->ptime          = (int)$poll['poll_ptime'];
			$this->votes          = (int)$poll['voters'];
			$this->artid          = (int)$poll['artid'];
			$this->language       = $poll['planguage'];

			$this->votes_txt = $L10N->plural($this->votes,'%d votes');

			$result = $db->query("SELECT
				vote_id      id,
				option_text  label,
				option_count votes
			FROM {$db->TBL->poll_data}
			WHERE poll_id = {$id} AND option_text != ''
			ORDER BY option_count DESC, option_text");
			$rate = null;
			while ($row = $result->fetch_assoc()) {
				$row['value']   = ($this->votes ? $row['votes'] / $this->votes : 0);
				$row['percent'] = $L10N->round($row['value'] * 100, 1).'%';
				if (null === $rate) $rate = $row['value'];
				$row['rate'] = min(1,$row['value']/$rate);
				$this->results[$row['id']] = $row;
			}
			$result->free();

			$this->reply_uri = $this->allow_reply ? \URL::index("&pollid={$this->id}&reply=0") : null;
		}
	}

	function __get($k)
	{
		if ('options' === $k) {
			$options = $this->results;
			ksort($options);
			return $options;
		}

		if ('results' === $k) {
			return $this->results;
		}

		if (!isset($this->comments_count) && ('comments_count' === $k || 'comments_count_txt' === $k)) {
			$this->comments_count = \Dragonfly::getKernel()->SQL->count('pollcomments', "poll_id={$this->id}");
		}

		if ('comments_count_txt' === $k) {
			$this->comments_count_txt = \Dragonfly::getKernel()->L10N->plural($this->comments_count, '%d comments');
		}

		if ('comments' === $k && !is_array($this->comments)) {
			$this->comments = array();
			$ID = \Dragonfly::getKernel()->IDENTITY;
			if ($this->allow_comments && $this->comments_count && 'nocomments' != $ID->umode) {
				$SQL = \Dragonfly::getKernel()->SQL;
				$where = '';
				if ('flat' !== $ID->umode) {
					$where = 'AND pid = 0';
				}
				$order = 'date ASC';
				if (1 == $ID->uorder) {
					$order = 'date DESC';
				} else if (2 == $ID->uorder) {
					$order = 'score DESC, date ASC';
				}
				$result = $SQL->query("SELECT
					tid id,
					pid parent_id,
					date,
					remote_ip,
					comment body,
					score,
					user_id
				FROM {$SQL->TBL->pollcomments}
				WHERE poll_id = {$this->id} {$where}
				ORDER BY {$order}");
				while ($row = $result->fetch_assoc()) {
					$this->comments[] = Comment::factory($this, $row);
				}
			}
		}

		if ('voted' === $k && is_null($this->voted)) {
			$K = \Dragonfly::getKernel();
			$tbl = $K->SQL->TBL->poll_check;
			// if you are anonymous and no anonymous votes allowed, you can't vote
			if (!$K->IDENTITY->isMember()) {
				if ($this->vote_anonymous) {
					// Only one anonymous per ip per 30 days
					$this->voted = 0 < $tbl->count("user_id = 0 AND ip = {$K->SQL->quote($_SERVER['REMOTE_ADDR'])}
						AND poll_id = {$this->id} AND time < ".(time()-86400*30));
				} else {
					$this->voted = true;
				}
			} else {
				$this->voted = 0 < $tbl->count("user_id = {$K->IDENTITY->id} AND poll_id = {$this->id}");
			}
		}

		return $this->$k;
	}

	public function voteForOption($id)
	{
		// Save the vote
		if (!$this->__get('voted')) {
			$K = \Dragonfly::getKernel();
			$db = $K->SQL;
			$db->TBL->poll_check->insert(array(
				'user_id' => $K->IDENTITY->id,
				'ip'      => $_SERVER['REMOTE_ADDR'],
				'time'    => time(),
				'poll_id' => $this->id
			));
			$db->exec("UPDATE {$db->TBL->poll_data} SET option_count = option_count + 1 WHERE poll_id = {$this->id} AND vote_id = ".intval($id));
			$db->exec("UPDATE {$db->TBL->poll_desc} SET voters = voters + 1 WHERE poll_id = {$this->id}");
		}
	}

}
