<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

class News_search
{
	public $title;
	public $options;
	public $result = array();
	public $result_count = 0;
	public $link_next;
	public $link_prev;

	// class constructor
	function __construct()
	{
		$db = Dragonfly::getKernel()->SQL;
		$cats[0] = _ALL;
		$result = $db->query("SELECT catid, title FROM {$db->TBL->stories_cat} ORDER BY title");
		while (list($id, $title) = $result->fetch_row()) {
			$cats[$id] = $title;
		}
		$result->free();

		$topics[0] = _ALLTOPICS;
		$result = $db->query("SELECT topicid, topictext FROM {$db->TBL->topics} ORDER BY topictext");
		while (list($id, $title) = $result->fetch_row()) {
			$topics[$id] = $title;
		}
		$result->free();

		$this->title = _NewsLANG;
		$this->options = '<table ><tr><td>'._TOPIC.'</td><td>'.\Dragonfly\Output\HTML::select_box('topic', 0, $topics).'</td></tr>'
			.'<tr><td>'._CATEGORY.'</td><td>'.\Dragonfly\Output\HTML::select_box('cat', 0, $cats).'</td></tr>'
			.'<tr><td>'._PCOMMENTS.'</td><td><input type="checkbox" name="news_search_comments"/></td></tr>'
			.'</table>';
	}

	public function search($search, $url_search, $limit=10, $page=0)
	{
		$db = Dragonfly::getKernel()->SQL;
		$L10N = \Dragonfly::getKernel()->L10N;

		$first = $page*$limit;

		$cat   = (isset($_POST['cat']) ? intval($_POST['cat']) : 0);
		$days  = (isset($_POST['days']) ? (intval($_POST['days'])*86400) : 0);
		$topic = (isset($_POST['topic']) ? intval($_POST['topic']) : 0);
		$search_comments = $_POST->bool('news_search_comments') ?: $_GET->bool('news_search_comments');

		$categ = ($cat > 0) ? "catid=$cat" : 'catid>-1';
		$q = "SELECT s.sid, s.informant, s.title, s.ptime, s.comments, s.topic, s.hometext, t.topictext
		FROM {$db->TBL->stories} s
		LEFT JOIN {$db->TBL->topics} t ON s.topic = t.topicid
		WHERE s.ptime<".time()." AND $categ";
		if ($L10N->multilingual) $q .= " AND (s.alanguage='{$L10N->lng}' OR s.alanguage='')";
		if ($search != '') $q .= " AND (s.title LIKE '%$search%' OR s.hometext LIKE '%$search%' OR s.bodytext LIKE '%$search%' OR s.notes LIKE '%$search%')";
		if ($topic > 0) $q .= " AND s.topic='$topic'";
		if ($days > 0) $q .= " AND s.ptime >= ".(time()-$days);
		$q .= " ORDER BY s.ptime DESC LIMIT ".($limit+1)." OFFSET $first";
		$result = $db->query($q);
		$nrows	= $result->num_rows;
		$crows  = 0;

		$x = ($limit > $nrows) ? $nrows : $limit;
		if ($nrows > 0) {
			while($x > 0) {
				list($sid, $informant, $title, $ptime, $comments, $ntopic, $hometext, $topictext) = $result->fetch_row();
				if ($informant == '') {
					$item['author'] = _ANONYMOUS;
				} else {
					$item['author'] = "<a href=\"".htmlspecialchars(\Dragonfly\Identity::getProfileURL($informant))."\">$informant</a>";
				}
				$item['title'] = $title;
				$item['date'] = $ptime;
				$item['url'] = URL::index("News&file=article&sid=$sid");
				$item['short_text'] = substr(strip_tags(\Dragonfly\BBCode::decode($hometext)), 0, 128);
				$item['footer'] = _TOPIC . ': <a href="'.htmlspecialchars(URL::index("&search=$url_search&topic=$ntopic"))."\">$topictext</a>";
				if ($comments == 0) {
					$item['footer'] .= " ({$L10N['_NOCOMMENTS']})";
				} elseif ($comments == 1) {
					$item['footer'] .= " ({$comments} {$L10N['_UCOMMENT']})";
				} elseif ($comments >1) {
					$item['footer'] .= " ({$comments} {$L10N['_UCOMMENTS']})";
				}

				if (can_admin('news')) {
					$item['footer'] .= ' [ <a href="'.htmlspecialchars(URL::admin("News&edit=$sid")).'">'._EDIT.'</a> | <a href="'.htmlspecialchars(URL::admin("News&del=$sid")).'">'._DELETE.'</a> ]';
				}

				$this->add_result($item);
				unset($item);

				$x--;
			}
		}

		$result->free();

		// this would be much nicer if we'd join the comments with the articles and include
		// the comments results as a subset of the article results. Consider fetching comments
		// inside the above loop.
		if ($search_comments) {
			$result = $db->query("SELECT c.tid, c.sid, c.date, c.user_id, u.username, c.comment, s.topic, t.topictext
			FROM {$db->TBL->comments} c
			LEFT JOIN {$db->TBL->stories} s ON s.sid = c.sid
			LEFT JOIN {$db->TBL->topics} t ON t.topic = s.topicid
			LEFT JOIN {$db->TBL->users} u ON u.user_id = c.user_id
			WHERE (subject like '%{$search}%' OR comment like '%{$search}%')
			ORDER BY date DESC LIMIT ".($limit+1)." OFFSET $first");
			$crows = $result->num_rows;

			if ($crows>0) {
				while(list($tid, $sid, $subject, $date, $user_id, $username, $comment, $title, $topictext) = $result->fetch_row) {
					$reply = $db->TBL->comments->count('pid='.$tid);

					if (!$username) {
						$item['author'] = _ANONYMOUS;
					} else {
						$item['author'] = "<a href=\"".htmlspecialchars(\Dragonfly\Identity::getProfileURL($user_id))."\">{$username}</a>";
					}

					$item['footer'] = _TOPIC . ': <a href="'.htmlspecialchars(URL::index("&search=$url_search&topic=$ntopic"))."\">$topictext</a>";

					if ($reply == 1) {
						$item['footer'] .= " ({$reply} "._SREPLY.")";
					} else {
						$item['footer'] .= " ({$reply} "._SREPLIES.")";
					}
					if (is_admin()) {
						$item['footer'] .= ' [ <a href="'.htmlspecialchars(URL::admin("News&del_comment={$tid}")).'">'._DELETE.'</a> ]';
					}
					$item['title'] = $subject;
					$item['date'] = $date;
					$item['url'] = URL::index("News&file=article&sid=$sid#$tid");
					$item['short_text'] = substr(strip_tags(\Dragonfly\BBCode::decode($comment)), 0, 128);

					$this->add_result($item);
					unset($item);
				}
			}
		}

		if ($page > 0) {
			$this->link_prev = '<a href="'.htmlspecialchars(URL::index('Search&page='.($page-1)."&topic=$topic&cat=$cat&search=$url_search&news_search_comments=$search_comments&mod=News")).'"><strong>'._PREVMATCHES.' '._IN.' '._NewsLANG.'</strong></a>';
		}
		if (($nrows > $limit) || ($crows > $limit)) {
			$this->link_next = '<a href="'.htmlspecialchars(URL::index('Search&page='.($page+1)."&topic=$topic&cat=$cat&search=$url_search&news_search_comments=$search_comments&mod=News")).'"><strong>'._NEXTMATCHES.' '._IN.' '._NewsLANG.'</strong></a>';
		}

	}

	protected function add_result($resultarray)
	{
		$this->result[$this->result_count++] = $resultarray;
	}
}
