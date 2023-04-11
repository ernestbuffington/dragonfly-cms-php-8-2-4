<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\News;

class Story
{
	protected
		$id             = 0,
		$catid          = 0,
		$identity_id    = 0,
		$aid            = '',
		$title          = '',
		$ptime          = 0,
		$hometext       = '',
		$bodytext       = '',
		$comments_count = 0,
		$counter        = 0,
		$topic          = 0,
		$informant      = '',
		$notes          = '',
		$ihome          = false,
		$language       = '',
		$allow_comments = false,
		$allow_reply    = false,
		$poll_id        = 0,
		$score          = 0,
		$ratings        = 0,
		$associated     = '',
		$display_order  = 0,

		$cattitle       = '',
		$topicimage     = 'news.png',
		$topicimage_uri = null,
		$topictext      = '',

		$comments;

	function __construct($id=0)
	{
		if ($id) {
			$id   = (int)$id;
			$K    = \Dragonfly::getKernel();
			$db   = $K->SQL;
			$L10N = $K->L10N;

			$story = $db->uFetchAssoc("SELECT
				s.catid,
				s.identity_id,
				s.aid,
				s.title,
				s.ptime,
				s.hometext,
				s.bodytext,
				s.comments comments_count,
				s.counter,
				s.topic,
				s.informant,
				s.notes,
				s.ihome,
				s.alanguage language,
				s.acomm allow_comments,
				s.poll_id,
				s.score,
				s.ratings,
				s.associated,
				s.display_order,
				c.title as cattitle,
				t.topicimage,
				t.topictext
			FROM {$db->TBL->stories} s
			LEFT JOIN {$K->SQL->TBL->stories_cat} c USING (catid)
			LEFT JOIN {$K->SQL->TBL->topics} t ON t.topicid = s.topic
			WHERE s.sid = {$id} AND s.ptime <= ".time());
			if (!$story) {
				throw new \Exception(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_NewsLANG)));
			}

			$this->id             = $id;
			$this->catid          = (int) $story['catid'];
			$this->identity_id    = (int) $story['identity_id'];
			$this->aid            = $story['aid'];
			$this->title          = $story['title'];
			$this->ptime          = (int) $story['ptime'];
			$this->hometext       = $story['hometext'];
			$this->bodytext       = $story['bodytext'];
			$this->comments_count = (int) $story['comments_count'];
//			$this->comments_count = \Dragonfly::getKernel()->SQL->count('comments', "sid={$this->id}");
			$this->counter        = (int) $story['counter'];
			$this->topic          = (int) $story['topic'];
			$this->informant      = array(
				'uri' => $story['informant'] ? \Dragonfly\Identity::getProfileURL($story['identity_id']?:$story['informant']) : null,
				'name' => $story['informant'] ?: _ANONYMOUS,
			);
			$this->notes          = $story['notes'];
			$this->ihome          = !!$story['ihome'];
			$this->language       = $story['language'];
			$this->allow_comments = ($story['allow_comments'] && $K->CFG->global->articlecomm);
			$this->allow_reply    = ($this->allow_comments && $K->IDENTITY->isMember());
			$this->poll_id        = (int) $story['poll_id'];
			$this->score          = (int) $story['score'];
			$this->ratings        = (int) $story['ratings'];
			$this->associated     = $story['associated'];
			$this->display_order  = (int) $story['display_order'];
			$this->cattitle       = $story['cattitle'];
			$this->topicimage     = $story['topicimage'] ?: 'news.png';
			$this->topictext      = $story['topictext'];

			// Fix very old entries
			if (false !== strpos($this->associated,'-')) {
				$this->associated = trim(str_replace('-', ',', $this->associated), ',');
			}
		}
	}

	function __get($k)
	{
		if ('comments_count_txt' === $k) {
			$this->comments_count_txt = \Dragonfly::getKernel()->L10N->plural($this->comments_count, '%d comments');
		}

		if ('views' === $k) {
			$this->views = \Dragonfly::getKernel()->L10N->plural($this->counter,'%d views');
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
				FROM {$SQL->TBL->comments}
				WHERE sid = {$this->id} {$where}
				ORDER BY {$order}");
				while ($row = $result->fetch_assoc()) {
					$this->comments[] = Comment::factory($this, $row);
				}
			}
		}

		if ('topicimage_uri' === $k && !$this->topicimage_uri) {
			$theme = \Dragonfly::getKernel()->OUT->theme;
			$this->topicimage_uri = "themes/{$theme}/images/topics/{$this->topicimage}";
			if (!is_file($this->topicimage_uri)) {
				$this->topicimage_uri = preg_replace('#^.+/(images/topics/)#', '$1', $this->topicimage_uri);
			}
		}

		return $this->$k;
	}

}
