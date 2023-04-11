<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2004 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Forums;

abstract class Display
{

	public static function forums($parent=0, $display_moderators = TRUE)
	{
		global $db, $board_config, $forum_info, $template, $userinfo, $module_name;
		$lang = $template->L10N;
		$images = get_forums_images();

		# Find which forums are visible for this user
		$tmp_forums = $db->uFetchAll('SELECT forum_id, auth_view FROM '. FORUMS_TABLE);
		$is_auth_ary = \Dragonfly\Forums\Auth::view(0, $tmp_forums);
		$c = count($tmp_forums);
		$forum_ids = array();
		for ($i=0; $i<$c; ++$i) {
			if ($is_auth_ary[$tmp_forums[$i]['forum_id']]['auth_view'] && isset($tmp_forums[$i]['forum_id'])) {
				$forum_ids[] = $tmp_forums[$i]['forum_id'];
			}
		}
		if (!$forum_ids) { return array(); }
		unset($tmp_forums);
		$forum_ids = implode(',', $forum_ids);

		# Define appropriate SQL
		$where = (isset($_GET['file']) && $_GET['file'] == 'viewarchive') ? 'AND f.archive_topics > 0' : '';
		$result = $db->query('SELECT
			f.*,
			p.post_time,
			p.post_username,
			u.username,
			u.user_id
		FROM '.FORUMS_TABLE.' f
		LEFT JOIN '.POSTS_TABLE.' p ON p.post_id = f.forum_last_post_id
		LEFT JOIN '.$db->TBL->users.' u ON u.user_id = p.poster_id
		WHERE f.forum_id IN ('.$forum_ids.') '.$where.'
		ORDER BY f.cat_id, f.forum_order');

		# Save a copy of (child) forums
		$forums = $childs = array();
		while ($row = $result->fetch_assoc()) {
			if (!$parent && 0 < $row['parent_id']) {
				$childs[$row['parent_id']][] = $row;
			} else if ($parent && $row['parent_id'] != $parent) {
				$childs[$row['parent_id']][] = $row;
			} else {
				$forums[] = $row;
			}
		}
		$result->free();

		# Obtain a list of topic ids which contain posts made since user last visited
		$new_topic_data = array();
		if (is_user()) {
			$lastvisit = static::getLastVisit();
			$result = $db->query('SELECT t.forum_id, t.topic_id, f.parent_id, p.post_time
				FROM '.TOPICS_TABLE.' t, '.POSTS_TABLE.' p, '.FORUMS_TABLE.' f
				WHERE p.post_id = t.topic_last_post_id
				  AND p.post_time > '.$lastvisit.'
				  AND t.topic_moved_id = 0
				  AND t.forum_id = f.forum_id
				  AND f.forum_id IN ('.$forum_ids.')
				  '.$where.'
				ORDER BY p.post_time DESC');
			while ($topic_data = $result->fetch_assoc()) {
				$forum_id = $topic_data['forum_id'];
				if (empty($new_topic_data[$forum_id])) {
					if (static::getForumTopicLastVisit($forum_id, $topic_data['topic_id'], false) < $topic_data['post_time']) {
						$new_topic_data[$forum_id] = true;
						if ($topic_data['parent_id']) {
							$new_topic_data[$topic_data['parent_id']] = true;
						}
					}
				}
			}
			$result->free();
		}

		$c = count($forums);
		for ($i=0; $i<$c; ++$i) {
			$forum_id = $forums[$i]['forum_id'];
			$forums[$i]['subforums'] = '';
			$forums[$i]['subforums_list'] = '';
			$forums[$i]['subforums_lang'] = '';
			$ftopics = $forums[$i]['forum_topics'];
			$fposts = $forums[$i]['forum_posts'];
			$atopics = $forums[$i]['archive_topics'];
			$aposts = $forums[$i]['archive_posts'];
			if ($forums[$i]['forum_type'] >= 2) {
				$folder_image = '_link';
				$folder_alt = 'link';
			} else if ($forums[$i]['forum_status'] == \Dragonfly\Forums\Forum::STATUS_LOCKED) {
				$folder_image = '_locked';
				$folder_alt = $lang['Forum_locked'];
			} else {
				$unread_topics = false;
				if (is_user()) {
					$unread_topics = !empty($new_topic_data[$forum_id]);
				}
				if (!empty($childs[$forum_id])) {
					$forums[$i]['subforums'] = $childs[$forum_id];
					$subforums = array();
					$s = 0;
					foreach ($childs[$forum_id] as $subforum) {
						$ftopics += $subforum['forum_topics'];
						$fposts += $subforum['forum_posts'];
						$atopics += $subforum['archive_topics'];
						$aposts += $subforum['archive_posts'];
						if (isset($_GET['file']) && $_GET['file'] == 'archives') {
							if ($subforum['parent_id'] == $forum_id && $subforum['archive_topics'] > 0) {
								$subforums[] = '<a href="'.htmlspecialchars(\URL::index($module_name.'&viewarchive&f='.$subforum['forum_id'])).'">'.$subforum['forum_name'].'</a>';
							}
						} else if ($subforum['parent_id'] == $forum_id) {
							if ($subforum['forum_type'] == 2) {
								$subforums[] = '<a href="'.\URL::index($subforum['forum_link']).'">'.$subforum['forum_name'].'</a>';
							} elseif ($subforum['forum_type'] == 3) {
								$subforums[] = '<a href="'.$subforum['forum_link'].'">'.$subforum['forum_name'].'</a>';
							} else {
								$subforums[] = '<a href="'.htmlspecialchars(\URL::index($module_name.'&file=viewforum&f='.$subforum['forum_id'])).'">'.$subforum['forum_name'].'</a>';
							}
						}
						if ($subforum['post_time'] > $forums[$i]['post_time']) {
							$forums[$i]['post_time'] = $subforum['post_time'];
							$forums[$i]['username'] = $subforum['username'];
							$forums[$i]['user_id'] = $subforum['user_id'];
							$forums[$i]['forum_last_post_id'] = $subforum['forum_last_post_id'];
						}
						!empty($new_topic_data[$subforum['forum_id']]) ? ++$s : $s;
					}
					$forums[$i]['subforums_list'] = implode(', ', $subforums);
					$forums[$i]['subforums_lang'] = (count($subforums) == 1) ? $lang['Subforum'] : $lang['Subforums'];
				}
				if ($forums[$i]['forum_type'] == 1) {
					$folder_image = ($unread_topics || $s >0) ? '_new_sub' : '_sub';
				} else {
					$folder_image = ( $unread_topics ) ? '_new' : '';
				}
				$folder_alt = ( $unread_topics ) ? $lang['New_posts'] : $lang['No_new_posts'];
			}

			$moderator_list = $display_moderators ? \BoardCache::forumModeratorsHTML($forum_id) : '';
			if ($moderator_list) {
				$l_moderators = (count($moderator_list) === 1) ? $lang['Moderator'] : $lang['Moderators'];
				$moderator_list = implode(', ', $moderator_list);
			} else {
				$l_moderators = '';
			}
			$forums[$i]['post_username']  = ($forums[$i]['post_username']) ? $forums[$i]['post_username'] : $lang['Guest'];
			$forums[$i]['icon']           = 'forum-icon'.str_replace('_','-',$folder_image);
			$forums[$i]['folder_image']   = DF_STATIC_DOMAIN . $images['forum'.$folder_image];
			$forums[$i]['folder_alt']     = $folder_alt;
			$forums[$i]['l_moderators']   = $l_moderators;
			$forums[$i]['moderator_list'] = $moderator_list;
			$forums[$i]['forum_topics']   = $ftopics;
			$forums[$i]['forum_posts']    = $fposts;
			$forums[$i]['archive_topics'] = $atopics;
			$forums[$i]['archive_posts']  = $aposts;
		}
		return $forums;
	}

	public static function onlineNow()
	{
		return static::getOnline('index', 300);
	}

	public static function onlineToday()
	{
		return static::getOnline('today', 86400);
	}

	public static function getLastVisit()
	{
		global $module_name;
		$lastvisit = \Dragonfly::getKernel()->IDENTITY->lastvisit;
		if (isset($_SESSION['CPG_SESS'][$module_name]['track_all'])) {
			$lastvisit = max($lastvisit, $_SESSION['CPG_SESS'][$module_name]['track_all']);
		}
		return $lastvisit;
	}

	public static function getForumLastVisit($forum_id, $all = true)
	{
		global $module_name;
		$lastvisit = $all ? static::getLastVisit() : 0;
		if (!empty($_SESSION['CPG_SESS'][$module_name]['track_forums'][$forum_id])) {
			$lastvisit = max($lastvisit, $_SESSION['CPG_SESS'][$module_name]['track_forums'][$forum_id]);
		}
		return $lastvisit ?: null;
	}

	public static function getForumTopicLastVisit($forum_id, $topic_id, $all = true)
	{
		global $module_name;
		$lastvisit = static::getForumLastVisit($forum_id, $all);
		if (!empty($_SESSION['CPG_SESS'][$module_name]['track_topics'][$topic_id])) {
			$lastvisit = max($lastvisit, $_SESSION['CPG_SESS'][$module_name]['track_topics'][$topic_id]);
		}
		return $lastvisit ?: null;
	}

	protected static function getOnline($type, $seconds)
	{
		global $db, $board_config, $module_name;
		$K = \Dragonfly::getKernel();
		$group = $board_config["online_{$type}_group"];
		if (!$board_config["allow_online_{$type}"] || (1 == $group && !is_user()) || (2 == $group && !can_admin($module_name)) || ($group > 3 && !$K->IDENTITY->inGroup($group-3))) {
			return '';
		}
		$seconds = time() - $seconds;
		$result = $db->query("SELECT user_id, username, user_level, user_allow_viewonline
		FROM {$db->TBL->users}
		WHERE user_id <> 1
		  AND user_session_time > {$seconds}
		ORDER BY username");
		$hidden = 0;
		$online = array();
		while (list($user_id, $username, $user_level, $allow_view) = $result->fetch_row()) {
			$color = ($user_level == \Dragonfly\Identity::LEVEL_ADMIN) ? $board_config['admin_color'] : (($user_level == \Dragonfly\Identity::LEVEL_MOD) ? $board_config['moderator_color'] : $board_config['member_color']);
			if (!$allow_view) { ++$hidden; }
			if ($allow_view || is_admin()) {
				$online[] = '<a href="'.htmlspecialchars(\Dragonfly\Identity::getProfileURL($user_id)).'" style="color:'.$color.';">'.$username.'</a>';
			}
		}

		if ('index' === $type) {
			list($guests, $bots) = $db->uFetchRow("SELECT COUNT(IF(guest=1,1,NULL)) AS guests, COUNT(IF(guest=3,1,NULL)) AS bots FROM {$db->TBL->session}");
			return implode(', ',$online) . "<br />Members {$result->num_rows} (hidden {$hidden}), Guests {$guests}, Bots {$bots}.";
		} else {
			return implode(', ',$online);
		}
	}

}
