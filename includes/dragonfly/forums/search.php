<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Forums;

class Search
{

	public static function splitWords($mode, $entry)
	{
		static $drop_char_match   = array('^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '-', '~', '+', '.', '[', ']', '{', '}', ':', '\\', '/', '=', '#', '\'', ';', '!');
		static $drop_char_replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',   ' ', ' ', ' ', ' ', '',  ' ', ' ', '',  ' ',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ' , ' ', ' ', ' ', ' ', ' ', ' ');
		static $stopword_list, $synonym_list;
		if (!$stopword_list) {
			$lng = \Dragonfly::getKernel()->L10N->lng;
			$stopword_list = file("includes/l10n/{$lng}/Forums/search_stopwords.txt");
			$synonym_list = file("includes/l10n/{$lng}/Forums/search_synonyms.txt");
		}

		$entry = ' ' . mb_strtolower(strip_tags($entry)) . ' ';

		if ('post' == $mode) {
			// Replace line endings by a space
			$entry = preg_replace('/[\n\r]/is', ' ', $entry);
			// HTML entities like &nbsp;
			$entry = preg_replace('/\b&[a-z]+;\b/', ' ', $entry);
			// Remove URL's
			$entry = preg_replace('/\b[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/', ' ', $entry);
			// Quickly remove BBcode.
			$entry = preg_replace('/\[img:[a-z0-9]{10,}\].*?\[\/img:[a-z0-9]{10,}\]/', ' ', $entry);
			$entry = preg_replace('/\[\/?url(=.*?)?\]/', ' ', $entry);
			$entry = preg_replace('/\[\/?[a-z\*=\+\-]+(\:?[0-9a-z]+)?:[a-z0-9]{10,}(\:[a-z0-9]+)?=?.*?\]/', ' ', $entry);
		}
		else if ('search' == $mode) {
			$entry = str_replace(' +', ' and ', $entry);
			$entry = str_replace(' -', ' not ', $entry);
		}

		//
		// Filter out strange characters like ^, $, &, change "it's" to "its"
		//
		for ($i = 0; $i < count($drop_char_match); ++$i) {
			$entry = str_replace($drop_char_match[$i], $drop_char_replace[$i], $entry);
		}

		if ('post' == $mode) {
			$entry = str_replace('*', ' ', $entry);
			// 'words' that consist of <3 or >20 characters are removed.
			$entry = preg_replace('/[ ]([\S]{1,2}|[\S]{21,})[ ]/u',' ', $entry);
		}

		if (!empty($stopword_list)) {
			for ($j = 0; $j < count($stopword_list); ++$j) {
				$stopword = trim($stopword_list[$j]);
				if ('post' == $mode || ($stopword != 'not' && $stopword != 'and' && $stopword != 'or')) {
					$entry = str_replace(' ' . trim($stopword) . ' ', ' ', $entry);
				}
			}
		}

		if (!empty($synonym_list)) {
			for ($j = 0; $j < count($synonym_list); ++$j) {
				list($replace_synonym, $match_synonym) = explode(' ', trim(strtolower($synonym_list[$j])));
				if ('post' == $mode || ( $match_synonym != 'not' && $match_synonym != 'and' && $match_synonym != 'or' ) ) {
					$entry =  str_replace(' ' . trim($match_synonym) . ' ', ' ' . trim($replace_synonym) . ' ', $entry);
				}
			}
		}

		// Trim 1+ spaces to one space and split this trimmed string into words.
		//return explode(' ', trim(preg_replace('#\s+#', ' ', $entry)));
		return preg_split('#\s+#', trim($entry));
	}

	public static function addWords($post_id, $post_text, $post_title)
	{
	}

	public static function removeForPosts($post_ids)
	{
	}

	public
		$id       = '',
		$keywords = '',
		$author   = '',
		$show     = 'topics',
		$terms    = true,
		$fields   = true,
		$chars    = 200,
		$cat      = 0,
		$forum    = 0,
		$sort_by  = 0,
		$sort_dir = 'DESC',
		$days     = 0,
		$limit    = 1000,
		$results  = '',
		$keywords_split = array();

	public function __get($k)
	{
		if ('total_match_count' === $k) {
			return 1 + substr_count($this->results, ',');
		}
	}

	public function search()
	{
		if (!$this->keywords && !$this->author && !$this->id) {
			return false;
		}

		global $module_name, $board_config, $userinfo;
		$db = \Dragonfly::getKernel()->SQL;
		$store_vars = array('results', 'keywords_split', 'sort_by', 'sort_dir', 'show', 'chars');

		# Author name search
		if ($this->author) {
			if (3 > strlen(preg_replace('#[\\*%\\s]+#', '', $this->author))) {
				$this->author = '';
			} else {
				$this->author = str_replace('*', '%', $db->quote(mb_strtolower($this->author)));
			}
		}

		# Cycle through options ...
		if ('watch' == $this->id || 'fwatch' == $this->id || 'last40' == $this->id || 'last' == $this->id || 'newposts' == $this->id || 'egosearch' == $this->id || 'unanswered' == $this->id || $this->keywords || $this->author) {
			if ('last40' == $this->id) {
				if (!can_admin('forums')) {
					\Poodle\HTTP\Status::set(403);
					message_die(GENERAL_MESSAGE, 'Sorry, for administrators only.');
				}
				$this->limit = 40;
			} else if ('fwatch' == $this->id) {
				if (!$board_config['allow_forum_watch']) {
					\Poodle\HTTP\Status::set(403);
					message_die(GENERAL_MESSAGE, 'Sorry, disabled.');
				}
				if (!is_user()) {
					\URL::redirect(\Dragonfly\Identity::loginURL());
				}
				$this->sort_by = 4;
				$this->sort_dir = 'ASC';
				$this->show = 'forums';
			} else if ('watch' == $this->id || 'newposts' == $this->id || 'egosearch' == $this->id) {
				if (!is_user()) {
					\URL::redirect(\Dragonfly\Identity::loginURL());
				}
			}

			# If user is logged in then we'll check to see which (if any) private
			# forums they are allowed to view and include them in the search.
			# If not logged in we explicitly prevent searching of private forums
			$auth_sql = '';
			if ($this->forum) {
				$is_auth = \Dragonfly\Forums\Auth::read($this->forum);
				if (!$is_auth['auth_read']) {
					message_die(GENERAL_MESSAGE, $lang['No_searchable_forums']);
				}
				$auth_sql = "f.forum_id = {$this->forum}";
			} else {
				$is_auth_ary = \Dragonfly\Forums\Auth::read(0);
				$ignore_forum_ids = array();
				foreach ($is_auth_ary as $key => $value) {
					if (!$value['auth_read']) {
						$ignore_forum_ids[] = $key;
					}
				}
				if ($ignore_forum_ids) {
					$auth_sql = 'f.forum_id NOT IN ('.implode(',',$ignore_forum_ids).')';
				}
				if ($this->cat) {
					$auth_sql .= ($auth_sql ? " AND " : "") . "f.cat_id = {$this->cat}";
				}
			}

			$sql = '';
			$where_sql = '';
			if ('last40' == $this->id) {
				if ($this->cat) {
					$from_sql = TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f';
					$where_sql .= " AND f.forum_id = t.forum_id AND {$auth_sql}";
				} else if ($auth_sql) {
					$from_sql = TOPICS_TABLE . ' f';
					$where_sql .= " AND {$auth_sql}";
				} else {
					$from_sql = TOPICS_TABLE;
				}
				$sql = "SELECT topic_id FROM {$from_sql} ORDER BY topic_time DESC";
			} else if ('watch' == $this->id) {
				if ($this->cat) {
					$from_sql = TOPICS_WATCH_TABLE.' w, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f';
					$where_sql .= " AND t.topic_id = w.topic_id AND f.forum_id = t.forum_id AND {$auth_sql}";
				} else if ($auth_sql) {
					$from_sql = TOPICS_WATCH_TABLE.' w, ' . TOPICS_TABLE . ' t';
					$where_sql .= " AND t.topic_id = w.topic_id AND {$auth_sql}";
				} else {
					$from_sql = TOPICS_WATCH_TABLE.' w';
				}
				$sql = "SELECT w.topic_id FROM {$from_sql} WHERE w.user_id = {$userinfo->id} {$where_sql}";
			} else if ('fwatch' == $this->id) {
				if ($this->cat || $auth_sql) {
					$from_sql = FORUMS_WATCH_TABLE.' w, ' . FORUMS_TABLE . ' f';
					$where_sql .= " AND f.forum_id = w.forum_id AND {$auth_sql}";
				} else {
					$from_sql = FORUMS_WATCH_TABLE.' w';
				}
				$sql = "SELECT w.forum_id FROM {$from_sql} WHERE w.user_id = {$userinfo->id} {$where_sql}";
			} else if ('last' == $this->id) {
				if ($this->cat || $auth_sql) {
					$from_sql = POSTS_TABLE.' p, ' . FORUMS_TABLE . ' f';
					$where_sql .= " AND f.forum_id = p.forum_id AND {$auth_sql}";
				} else {
					$from_sql = POSTS_TABLE;
				}
				$sql = "SELECT DISTINCT topic_id FROM {$from_sql} WHERE post_time >= ".(time() - 86400).$where_sql;
			} else if ('newposts' == $this->id) {
				if ($this->cat || $auth_sql) {
					$from_sql = POSTS_TABLE.' p, ' . FORUMS_TABLE . ' f';
					$where_sql .= " AND f.forum_id = p.forum_id AND {$auth_sql}";
				} else {
					$from_sql = POSTS_TABLE;
				}
				$sql = "SELECT DISTINCT topic_id FROM {$from_sql} WHERE post_time >= {$userinfo->lastvisit}{$where_sql}";
			} else if ('egosearch' == $this->id) {
				if ($this->cat || $auth_sql) {
					$from_sql = POSTS_TABLE.' p, ' . FORUMS_TABLE . ' f';
					$where_sql .= " AND f.forum_id = p.forum_id AND {$auth_sql}";
				} else {
					$from_sql = POSTS_TABLE;
				}
				$sql = "SELECT DISTINCT topic_id FROM {$from_sql} WHERE poster_id = {$userinfo->id}{$where_sql}";
			} else if ('unanswered' == $this->id) {
				if ($auth_sql) {
					$sql = "SELECT t.topic_id
						FROM " . TOPICS_TABLE . "  t, " . FORUMS_TABLE . " f
						WHERE t.topic_replies = 0
							AND t.forum_id = f.forum_id
							AND t.topic_moved_id = 0
							AND {$auth_sql}";
				} else {
					$sql = "SELECT topic_id FROM " . TOPICS_TABLE . "
						WHERE topic_replies = 0 AND topic_moved_id = 0";
				}
			} else {
				$from_sql = POSTS_TABLE . ' p';
				if ($this->days) {
					$where_sql .= " AND p.post_time >= " . (time() - ($this->days * 86400));
				}
				if ($auth_sql) {
					$from_sql .= " INNER JOIN ". FORUMS_TABLE . " f ON (f.forum_id = p.forum_id AND {$auth_sql})";
				}
				if ($this->author) {
/*
					$result = $this->author ? $db->query("SELECT user_id FROM {$db->TBL->users} WHERE username LIKE {$this->author}") : false;
					if (!$result || !$result->num_rows) {
						return false;
					}
					$matching_userids = array();
					while ($row = $result->fetch_row()) {
						$matching_userids[] = $row[0];
					}
					$matching_userids = implode(',', $matching_userids);
*/
					$from_sql .= " INNER JOIN {$db->TBL->users} u ON (u.user_id = p.poster_id AND u.user_nickname_lc LIKE {$this->author})";
				}
				if ($this->keywords) {
					$this->keywords_split = static::splitWords('search', $this->keywords);
					if ($this->terms && 1 < preg_match_all('/"[^"]+"|\'[^\']+\'|\\S+/', $this->keywords, $m)) {
						$this->keywords = implode(' +', $m[0]);
						$this->keywords = str_replace(' ++', ' +', $this->keywords);
						$this->keywords = str_replace(' +-', ' -', $this->keywords);
					}
					$from_sql .= " INNER JOIN ". POSTS_TEXT_TABLE . " t USING (post_id)";
					$where_sql .= " AND {$db->search(array('t.post_search'), $this->keywords)}";
				}
				$where_sql = $where_sql ? ' WHERE'.substr($where_sql, 4) : '';
				if ('topics' == $this->show) {
					$sql = "SELECT DISTINCT p.topic_id FROM {$from_sql}{$where_sql}";
				} else {
					$sql = "SELECT p.post_id FROM {$from_sql}{$where_sql}";
				}
			}

			$search_results = array();
			if ($sql) {
				$result = $db->query($sql . ($this->limit ? " LIMIT {$this->limit}" : ''));
				while ($row = $result->fetch_row()) {
					$search_results[] = $row[0];
				}
				$result->free();
			}
			if (!$search_results) {
				return false;
			}

			# Store result data
			$this->results = implode(', ', $search_results);
			$per_page = ('posts' == $this->show) ? $board_config['posts_per_page'] : $board_config['topics_per_page'];

			# Limit the character length (and with this the results displayed at all following pages) to prevent
			# truncated result arrays. Normally, search results above 12000 are affected.
			# - to include or not to include
			/*
			$max_result_length = 60000;
			if (strlen($this->results) > $max_result_length)
			{
				$this->results = substr($this->results, 0, $max_result_length);
				$this->results = substr($this->results, 0, strrpos($this->results, ','));
			}
			*/

			mt_srand((double) microtime() * 1000000);
			$this->id = mt_rand();
			foreach ($store_vars as $store_var) {
				$_SESSION['CPG_SESS'][$module_name]['search'][$this->id][$store_var] = $this->$store_var;
			}
			$_SESSION['CPG_SESS'][$module_name]['search'] = array_slice($_SESSION['CPG_SESS'][$module_name]['search'], -4, 4, true);
		} else {
			$this->id = intval($this->id);
			if ($this->id) {
				if (empty($_SESSION['CPG_SESS'][$module_name]['search'][$this->id])) {
					\Poodle\HTTP\Status::set(404);
					message_die(GENERAL_ERROR, 'Could not obtain search results');
				}
				foreach ($store_vars as $store_var) {
					$this->$store_var = $_SESSION['CPG_SESS'][$module_name]['search'][$this->id][$store_var];
				}
			}
		}

		return !!$this->results;
	}

}
