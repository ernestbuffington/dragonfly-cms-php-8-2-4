<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2008 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * TernaryToNullCoalescingRector
 */

//namespace Dragonfly\Modules\Forums;

class BoardCache
{

	public static function conf($key=null)
	{
		static $board_config;
		if (!is_array($board_config)) {
			$board_config = self::cacheGet('board_config');
			$K = \Dragonfly::getKernel();
			if (!$board_config) {
				$board_config = array();
				$result = $K->SQL->query("SELECT config_name, config_value FROM {$K->SQL->TBL->bbconfig}");
				while ($row = $result->fetch_row()) {
					$board_config[$row[0]] = $row[1];
				}
				$result->free();
				ksort($board_config);
				self::cacheSet('board_config', $board_config);
			}
			$board_config['sitename']    = $K->CFG->global->sitename;
			$board_config['board_email'] = $K->CFG->global->adminmail;

			if (is_user()) {
				$userinfo = $K->IDENTITY;
				if (!empty($userinfo['user_dateformat'])) {
					$board_config['default_dateformat'] = $userinfo['user_dateformat'];
				}
			}
		}
		if ($key) { return ($board_config[$key] ?? false); }
		return $board_config;
	}

	public static function categories()
	{
		static $categories;
		if (!is_array($categories)) {
			$categories = self::cacheGet('categories');
			if (!$categories) {
				global $db;
				$categories = array();
				$qr = $db->query('SELECT
					cat_id    id,
					cat_title title
				FROM '.CATEGORIES_TABLE.'
				ORDER BY cat_order');
				while ($c = $qr->fetch_assoc()) {
					$categories[$c['id']] = $c;
				}
				self::cacheSet('categories', $categories);
			}
		}
		return $categories;
	}

	public static function topic_icons()
	{
		static $topic_icons;
		if (!is_array($topic_icons)) {
			$topic_icons = self::cacheGet('topic_icons');
			if (!$topic_icons) {
				global $db;
				$topic_icons = array();
				$result = $db->query("SELECT * FROM ".TOPIC_ICONS_TABLE);
				while ($row = $result->fetch_assoc()) {
					$topic_icons[$row['icon_id']] = $row;
				}
				self::cacheSet('topic_icons', $topic_icons);
			}
		}
		return $topic_icons;
	}

	public static function forums_rows()
	{
		static $forums;
		if (!is_array($forums)) {
			$forums = self::cacheGet('forums');
			if (!$forums) {
				global $db;
				$forums = array();
				$result = $db->query("SELECT f.*
				FROM ".FORUMS_TABLE." f, ".CATEGORIES_TABLE." c
				WHERE c.cat_id = f.cat_id
				ORDER BY c.cat_order ASC, f.forum_order ASC");
				while ($row = $result->fetch_assoc()) {
					$forums[$row['forum_id']] = $row;
				}
				self::cacheSet('forums', $forums);
			}
		}
		return $forums;
	}

	# Obtain list of moderators of each forum
	# First users, then groups ... broken into two queries
	public static function moderators($forum_id=0)
	{
		static $moderators;
		if (!is_array($moderators)) {
			$moderators = self::cacheGet('moderators');
			if (!$moderators) {
				global $db;
				$moderators = array();
				$result = $db->query('SELECT aa.forum_id, u.user_id, u.username
				FROM '.AUTH_ACCESS_TABLE .' aa, '. $db->TBL->bbuser_group. ' ug, '. $db->TBL->bbgroups. ' g, '. $db->TBL->users. ' u
				WHERE aa.auth_mod = 1 AND g.group_single_user = 1
					AND ug.group_id = aa.group_id AND g.group_id = aa.group_id
					AND u.user_id = ug.user_id
				GROUP BY u.user_id, u.username, aa.forum_id
				ORDER BY aa.forum_id, u.user_id');
				while ($row = $result->fetch_row()) {
					$moderators[$row[0]][] = array('user_id' => $row[1], 'name' => $row[2]);
				}

				$result = $db->query('SELECT aa.forum_id, g.group_id, g.group_name
				FROM '.AUTH_ACCESS_TABLE.' aa, '.$db->TBL->bbuser_group.' ug, '.$db->TBL->bbgroups.' g
				WHERE aa.auth_mod = 1 AND g.group_single_user = 0
					AND g.group_type <> '.\Dragonfly\Groups::TYPE_HIDDEN.'
					AND ug.group_id = aa.group_id AND g.group_id = aa.group_id
				GROUP BY g.group_id, g.group_name, aa.forum_id
				ORDER BY aa.forum_id, g.group_id');
				while ($row = $result->fetch_row()) {
					$moderators[$row[0]][] = array('group_id' => $row[1], 'name' => $row[2]);
				}

				self::cacheSet('moderators', $moderators);
			}
		}
		if ($forum_id) {
			return ($moderators[$forum_id] ?? array());
		}
		return $moderators;
	}

	public static function forumModeratorsHTML($forum_id=0)
	{
		$moderators = array();
		$forum_moderators = static::moderators($forum_id);
		foreach ($forum_moderators as $moderator) {
			if (!empty($moderator['user_id'])) {
				$moderators[] = '<a href="'.htmlspecialchars(\Dragonfly\Identity::getProfileURL($moderator['user_id'])).'">'.htmlspecialchars($moderator['name']).'</a>';
			} else {
				$moderators[] = '<a href="'.htmlspecialchars(URL::index("Groups&g={$moderator['group_id']}")).'">'.htmlspecialchars($moderator['name']).'</a>';
			}
		}
		return $moderators;
	}

	protected static function cacheGet($name)
	{
		$module = basename(dirname(__DIR__));
		return \Dragonfly::getKernel()->CACHE->get("modules/{$module}/{$name}");
	}

	protected static function cacheSet($name, &$data)
	{
		$module = basename(dirname(__DIR__));
		return \Dragonfly::getKernel()->CACHE->set("modules/{$module}/{$name}", $data);
	}

	public static function cacheDelete($name)
	{
		$module = basename(dirname(__DIR__));
		\Dragonfly::getKernel()->CACHE->delete("modules/{$module}/{$name}");
	}

}
