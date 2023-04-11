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

class Post
{
	public
		$poll_options    = array();

	protected
		$id              = 0,
		$topic_id        = 0,
		$forum_id        = 0,
		$poster_id       = 0,
		$ctime           = 0,
		$poster_ip       = '',
		$username        = '',
		$enable_bbcode   = true,
		$enable_html     = false,
		$enable_smilies  = true,
		$enable_sig      = false,
		$mtime           = 0,
		$edit_count      = 0,
		$attachment      = false,
		$reputation_up   = 0,
		$reputation_down = 0,

		// post text
		$subject         = '',
		$message         = '',

		// topic
		$topic_type      = 0, // \Dragonfly\Forums\Topic::TYPE_NORMAL
		$topic_icon      = 0,
		$archive_id      = 0,
		$first_post      = true,
		$last_post       = true,
		$has_poll        = false,

		// topic poll
		$poll_title      = '',
		$poll_length     = 0,

		// user
		$user_sig        = '',

		$url    = null,
		$forum  = null,
		$topic  = null;

	function __construct($id=0)
	{
		$id = (int)$id;
		if ($id) {
/*
			$SQL->TBL->prefix
			POSTS_TABLE         $SQL->TBL->bbposts
			POSTS_TEXT_TABLE    $SQL->TBL->bbposts_text
			TOPICS_TABLE        $SQL->TBL->bbtopics
*/
			$SQL = \Dragonfly::getKernel()->SQL;
			$post = $SQL->uFetchAssoc("SELECT
				p.topic_id,
				p.forum_id,
				p.poster_id,
				p.post_time       ctime,
				p.poster_ip,
				COALESCE(u.username, p.post_username) username,
				p.enable_bbcode,
				p.enable_html,
				p.enable_smilies,
				p.enable_sig,
				p.post_edit_time  mtime,
				p.post_edit_count edit_count,
				p.post_attachment attachment,
				p.post_reputation_up reputation_up,
				p.post_reputation_down reputation_down,
				pt.post_subject   subject,
				pt.post_text      message,
				t.topic_type,
				t.topic_vote      has_poll,
				t.icon_id         topic_icon,
				t.topic_first_post_id,
				t.topic_last_post_id,
				u.user_sig
			FROM {$SQL->TBL->bbposts} p
			INNER JOIN {$SQL->TBL->bbposts_text} pt USING (post_id)
			INNER JOIN {$SQL->TBL->bbtopics} t USING (topic_id)
			LEFT JOIN {$SQL->TBL->users} u ON (u.user_id = p.poster_id AND NOT p.poster_id = ".\Dragonfly\Identity::ANONYMOUS_ID.")
			WHERE p.post_id = {$id}");
			if (!$post) {
				throw new \Exception(\Dragonfly::getKernel()->L10N->get('Topic_post_not_exist'));
//				throw new \Exception("Unknown post_id {$id}");
			}
			$this->id = $id;
			$this->first_post = ($id == $post['topic_first_post_id']);
			$this->last_post  = ($id == $post['topic_last_post_id']);
			unset($post['topic_first_post_id'], $post['topic_last_post_id']);
			foreach ($post as $k => $v) {
				$this->__set($k, $v);
			}
		} else {
			$userinfo = \Dragonfly::getKernel()->IDENTITY;
			if ($userinfo->isMember()) {
				$this->__set('enable_html', $userinfo->allowhtml);
				$this->__set('enable_bbcode', $userinfo->allowbbcode);
				$this->__set('enable_smilies', $userinfo->allowsmile);
				$this->__set('enable_sig', ($userinfo->attachsig && $userinfo->sig));
			} else {
				$this->__set('enable_bbcode', $this->enable_bbcode);
				$this->__set('enable_smilies', $this->enable_smilies);
			}
		}
	}

	function __get($k)
	{
		if ('forum' === $k && !$this->forum && $this->forum_id) {
			$this->forum = new Forum($this->forum_id);
		}
		if ('topic' === $k && !$this->topic && $this->topic_id) {
			$this->topic = new Topic($this->topic_id);
		}
		if ('url' === $k && !$this->url && $this->id) {
			$this->url = \URL::index("&file=viewtopic&p={$this->id}")."#{$this->id}";
		}
		if (property_exists($this, $k)) {
			return $this->$k;
		}
	}

	function __set($k, $v)
	{
		if (property_exists($this, $k)) {
			global $board_config;
			if ('id' == $k) {
				return;
			}
			if ('enable_html' === $k) {
				$v = ($board_config['allow_html'] && $v);
			} else if ('enable_bbcode' === $k) {
				$v = ($board_config['allow_bbcode'] && $v);
			} else if ('enable_smilies' === $k) {
				$v = ($board_config['allow_smilies'] && $v);
			} else if ('enable_sig' === $k) {
				$v = ($v && \Dragonfly::getKernel()->IDENTITY->isMember());
			}
			switch (gettype($this->$k))
			{
			case 'string':
				$this->$k = trim($v);
				break;
			case 'boolean':
				$this->$k = !!$v;
				break;
			case 'integer':
				$this->$k = (int)$v;
				break;
			}
			if (!$this->id && 'topic_id' === $k) {
				$this->first_post = !$this->topic_id;
			}
		}
	}

	function save()
	{
		global $board_config;
		$userinfo = \Dragonfly::getKernel()->IDENTITY;
		$lang = \Dragonfly::getKernel()->L10N;

		if (!$this->id) {
			$this->poster_id = $userinfo->id;
			$this->poster_ip = $_SERVER['REMOTE_ADDR'];
			$this->first_post = !$this->topic_id;
		}

		$errors = array();

		# Check username
		if (!empty($this->username)) {
			if (\Dragonfly\Identity::ANONYMOUS_ID != $this->poster_id) {
				$this->username = '';
			} else {
				try {
					\Dragonfly\Identity\Validate::nickname($this->username);
				} catch (\Exception $e) {
					$errors[] = $e->getMessage();
				}
			}
		}

		# Check subject
		if (empty($this->subject) && $this->first_post) {
			$errors[] = $lang['Empty_subject'];
		}

		# Check message
		if (!empty($this->message)) {
			$this->message = trim($this->message);
			if ($this->enable_bbcode) {
				$this->message = \Dragonfly\BBCode::encode($this->message);
			}
		} else {
			$errors[] = $lang['Empty_message'];
		}

		# Handle poll stuff
		if ($this->first_post && (!empty($this->poll_title) || count($this->poll_options))) {
			if (empty($this->poll_title)) {
				$errors[] = $lang['Empty_poll_title'];
			}
			else if (count($this->poll_options) < 2) {
				$errors[] = $lang['To_few_poll_options'];
			}
			else if (count($this->poll_options) > $board_config['max_poll_options']) {
				$errors[] = $lang['To_many_poll_options'];
			}
		}

		if ($errors) {
			throw new \Exception(implode("\n",$errors));
		}

		$SQL = \Dragonfly::getKernel()->SQL;

		$current_time = time();

		# Flood control
		$where_sql = $userinfo->isMember()
			? 'poster_id = '.$userinfo->id
			: 'poster_ip = '.$SQL->quote($_SERVER['REMOTE_ADDR']);
		$result = $SQL->query("SELECT MAX(post_time) AS last_post_time FROM ".POSTS_TABLE." WHERE {$where_sql}");
		if ($row = $result->fetch_assoc()) {
			if (intval($row['last_post_time']) > 0 && ($current_time - intval($row['last_post_time'])) < intval($board_config['flood_interval'])) {
				message_die(GENERAL_MESSAGE, $lang['Flood_Error']);
			}
		}

		if ($this->id) {
			\Dragonfly\Forums\Search::removeForPosts($this->id);
		}

		// Save topic
		if ($this->first_post) {
			$this->has_poll = (!empty($this->poll_title) && count($this->poll_options) > 1);

			$data = array(
				'topic_title' => $this->subject,
				'topic_type'  => $this->topic_type,
				'topic_vote'  => $this->has_poll,
				'icon_id'     => $this->topic_icon,
			);
			if ($this->id) {
				$SQL->TBL->bbtopics->update($data, "topic_id = {$this->topic_id}");
			} else {
				$data['topic_poster'] = $this->poster_id;
				$data['topic_time']   = $current_time;
				$data['forum_id']     = $this->forum_id;
				$data['topic_status'] = Topic::STATUS_UNLOCKED;
				$this->topic_id = $SQL->TBL->bbtopics->insert($data, 'topic_id');
				if ($this->archive_id) {
					$SQL->query("UPDATE {$SQL->TBL->bbtopics} SET
						topic_archive_id = {$this->topic_id}
					WHERE topic_id = {$this->archive_id}");
				}
			}

			// Topic poll
			// Create/Update poll
			if ($this->has_poll) {
				$poll_id = $SQL->uFetchRow("SELECT vote_id FROM {$SQL->TBL->bbvote_desc} WHERE topic_id = {$this->topic_id}");
				$poll = new Poll($poll_id ? $poll_id[0] : 0);
				$poll->topic_id = $this->topic_id;
				$poll->title = $this->poll_title;
				$poll->length = $this->poll_length;
				$poll->options = $this->poll_options;
				$poll->save();
			}
			// DELETE poll
			else {
				Poll::deleteFromTopics($this->topic_id);
			}
		}

		// Save post
		$data = array(
			'post_username'  => $this->username,
			'enable_bbcode'  => $this->enable_bbcode,
			'enable_html'    => $this->enable_html,
			'enable_smilies' => $this->enable_smilies,
			'enable_sig'     => $this->enable_sig,
		);
		if ($this->id) {
			$data = $SQL->prepareValues($data);
			if ($this->poster_id == $userinfo->id) {
				$data['post_edit_time']  = $current_time;
				$data['post_edit_count'] = 'post_edit_count + 1';
			}
			$SQL->TBL->bbposts->updatePrepared($data, "post_id = {$this->id}");
			$SQL->TBL->bbposts_text->updatePrepared(array(
				'post_subject' => $SQL->quote($this->subject),
				'post_text'    => $SQL->quote($this->message),
				'post_search'  => static::as_search_txt($this->subject . ' ' . $this->message),
			), "post_id = {$this->id}");
		} else {
			$data['topic_id']  = $this->topic_id;
			$data['forum_id']  = $this->forum_id;
			$data['poster_id'] = $this->poster_id;
			$data['post_time'] = $current_time;
			$data['poster_ip'] = $this->poster_ip;
			$this->id = $SQL->TBL->bbposts->insert($data, 'post_id');
			$SQL->TBL->bbposts_text->insertPrepared(array(
				'post_id'      => $this->id,
				'post_subject' => $SQL->quote($this->subject),
				'post_text'    => $SQL->quote($this->message),
				'post_search'  => static::as_search_txt($this->subject . ' ' . $this->message),
			));

			// update post stats

			$SQL->query("UPDATE ".FORUMS_TABLE." SET
				forum_posts = forum_posts + 1,
				forum_last_post_id = {$this->id}
				".($this->first_post ? ", forum_topics = forum_topics + 1" : '')."
			WHERE forum_id = {$this->forum_id}");

			$SQL->query("UPDATE ".TOPICS_TABLE." SET
				topic_last_post_id = {$this->id}
				".($this->first_post ? ", topic_first_post_id = {$this->id}" : ", topic_replies = topic_replies + 1")."
			WHERE topic_id = {$this->topic_id}");

			if (\Dragonfly\Identity::ANONYMOUS_ID != $this->poster_id) {
				$SQL->query("UPDATE {$SQL->TBL->users} SET user_posts = user_posts + 1 WHERE user_id = {$this->poster_id}");
			}

			global $module_name;
			$_SESSION['CPG_SESS'][$module_name]['track_topics'][$this->topic_id] = time();
		}

		\Dragonfly\Forums\Search::addWords($this->id, $this->message, $this->subject);

		return $this->id;
	}

	public function delete()
	{
		global $board_config;
		$SQL = \Dragonfly::getKernel()->SQL;

		if ($board_config['allow_topic_recycle'] == 1 && $this->forum_id != $board_config['topic_recycle_forum'] && !empty($board_config['topic_recycle_forum'])) {
			$recycle_topic_id = $SQL->TBL->bbtopics->insert(array(
				'forum_id'     => $board_config['topic_recycle_forum'],
				'topic_title'  => $this->subject,
				'topic_poster' => $this->poster_id,
				'topic_time'   => $this->ctime,
				'topic_views'  => 0,
				'topic_status' => Topic::STATUS_UNLOCKED,
				'topic_type'   => $this->topic_type,
				'icon_id'      => $this->topic_icon,
				'topic_last_post_id'  => $this->id,
				'topic_first_post_id' => $this->id,
			), 'topic_id');
			$SQL->query("UPDATE ".FORUMS_TABLE." SET forum_topics = forum_topics + 1 WHERE forum_id = {$board_config['topic_recycle_forum']}");
			$SQL->query("UPDATE ".POSTS_TABLE." SET forum_id = {$board_config['topic_recycle_forum']}, topic_id = {$recycle_topic_id} WHERE post_id = {$this->id}");
		} else {
			$SQL->query("DELETE FROM " . POSTS_TABLE . " WHERE post_id = {$this->id}");
			$SQL->query("DELETE FROM " . POSTS_TEXT_TABLE . " WHERE post_id = {$this->id}");
			if (\Dragonfly\Identity::ANONYMOUS_ID < $this->poster_id) {
				$SQL->query("UPDATE {$SQL->TBL->users} SET user_posts = user_posts -1 WHERE user_id = {$this->poster_id}");
			}
			\Dragonfly\Forums\Search::removeForPosts($this->id);
			\Dragonfly\Forums\Attachments::deleteFromPosts($this->id);
		}

		$forum_update_sql = 'forum_posts = forum_posts - 1';
		$topic_update_sql = 'topic_replies = topic_replies - 1';
		if ($this->last_post) {
			if ($this->first_post) {
				$topic_update_sql = '';
				\Dragonfly\Forums\Topic::delete($this->topic_id);
			} else {
				$row = $SQL->uFetchRow("SELECT MAX(post_id) FROM ".POSTS_TABLE." WHERE topic_id = {$this->topic_id}");
				$topic_update_sql .= ', topic_last_post_id = ' . (int)$row[0];
			}
//			if ("SELECT COUNT(*) FROM ".FORUMS_TABLE." WHERE forum_id={$this->forum_id} AND forum_last_post_id={$this->id}")
			$row = $SQL->uFetchRow("SELECT MAX(post_id) FROM ".POSTS_TABLE." WHERE forum_id = {$this->forum_id}");
			$forum_update_sql .= ', forum_last_post_id = ' . (int)$row[0];
		}
		else if ($this->first_post) {
			$row = $SQL->uFetchRow("SELECT MIN(post_id) FROM ".POSTS_TABLE." WHERE topic_id = {$this->topic_id}");
			$topic_update_sql .= ', topic_first_post_id = ' . (int)$row[0];
		}
		$SQL->query("UPDATE ".FORUMS_TABLE." SET {$forum_update_sql} WHERE forum_id = {$this->forum_id}");
		if ($topic_update_sql) {
			$SQL->query("UPDATE ".TOPICS_TABLE." SET {$topic_update_sql} WHERE topic_id = {$this->topic_id}");
		}
	}

	public function message2html(array $highlight_words = array())
	{
		global $board_config;

		$message = $this->message;

		if (!$this->enable_html || !$board_config['allow_html'] || !\Dragonfly::getKernel()->IDENTITY->allowhtml) {
			$message = htmlspecialchars($message, ENT_NOQUOTES);
		}

		if ($this->enable_bbcode && $board_config['allow_bbcode']) {
			$message = \Dragonfly\BBCode::decode($message, 1, false);
		} else {
			$message = nl2br($message);
		}

		$message = \URL::makeClickable($message);

		# Parse smilies
		if ($board_config['allow_smilies'] && $this->enable_smilies) {
			$message = \Dragonfly\Smilies::parse($message);
		}

		# Highlight active words (primarily for search)
		if ($highlight_words) {
			$highlight_words = implode('|', array_map(function($s){
				return str_replace('*', '\w*', preg_quote($s, '#'));
			}, $highlight_words));
			$message = str_replace('\\"', '"', substr(preg_replace_callback(
				'#(\>(((?>([^><]+|(?R)))*)\<))#s',
				function($m) use ($highlight_words) {
//					return preg_replace("#\b({$highlight_words})\b#i", '<b class="highlight">$1</b>', $m[0]);
					return preg_replace("#({$highlight_words})#i", '<b class="highlight">$1</b>', $m[0]);
				},
				'>'.$message.'<'), 1, -1));
		}
/*
		# Replace naughty words
		if (count($orig_word)) {
			$message = str_replace('\\"', '"', substr(preg_replace_callback(
				'#(\>(((?>([^><]+|(?R)))*)\<))#s',
				function($m) use ($orig_word, $replacement_word) {
					return preg_replace($orig_word, $replacement_word, $m[0]);
				},
				'>'.$message.'<'), 1, -1));
		}
*/
		return $message;
	}

	protected static function as_search_txt($str)
	{
		$str = preg_replace('#\\[quote=.*?\\].*?\\[/quote\\]#si', '', $str);
		$str = preg_replace('#\\[/?\w.*?\\]#si', '', $str);
		$SQL = \Dragonfly::getKernel()->SQL;
		if ('postgresql' == strtolower($SQL->engine)) {
			return "to_tsvector({$SQL->quote($str)})";
		}
		$str = \Poodle\Input::fixSpaces($str);
		$str = \Poodle\Unicode::stripModifiers($str);
		$str = preg_replace('#[^\p{L}\p{N}"\-\+]+#su', ' ', $str); # strip non-Letters/non-Numbers
		return $SQL->quote(trim(preg_replace('#\s[^\s]{1,2}\s#u', ' ', " {$str} ")));
	}

}
