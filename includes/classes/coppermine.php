<?php
/***************************************************************************
   Coppermine for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2015 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

/* Applied rules:
 * PublicConstantVisibilityRector (https://wiki.php.net/rfc/class_const_visibility)
 * TypedPropertyFromAssignsRector
 */

class Coppermine
{
	public const
		USER_GAL_CAT = 1,
		FIRST_USER_CAT = 10000,
		RANDPOS_MAX_PIC = 200,

		ANON_GROUP = 3;

	public static
		$MAIN_DIR = '';

	private static
		?array $instances = null;

	protected
		$dir,
		$prefix,
		$config;

	function __construct($dir, $prefix)
	{
		$this->dir = $dir;
		$this->prefix = $prefix;
	}

	// Retrieve configuration
	public function __get($k)
	{
		if ('config' === $k && !$this->config) {
			$db = \Dragonfly::getKernel()->SQL;
			// Initialise the $CONFIG array and some other variables
			$cpg_tbl_prefix = $this->prefix;
			$CONFIG = array(
				'TABLE_PICTURES'   => $db->TBL->getTable($cpg_tbl_prefix . 'pictures'),
				'TABLE_ALBUMS'     => $db->TBL->getTable($cpg_tbl_prefix . 'albums'),
				'TABLE_COMMENTS'   => $db->TBL->getTable($cpg_tbl_prefix . 'comments'),
				'TABLE_CATEGORIES' => $db->TBL->getTable($cpg_tbl_prefix . 'categories'),
				'TABLE_CONFIG'     => $db->TBL->getTable($cpg_tbl_prefix . 'config'),
				'TABLE_FAVORITES'  => $db->TBL->getTable($cpg_tbl_prefix . 'favorites'),
				'TABLE_VOTES'      => $db->TBL->getTable($cpg_tbl_prefix . 'votes'),
			);
			$results = $db->query("SELECT name, value FROM {$CONFIG['TABLE_CONFIG']}");
			while ($row = $results->fetch_row()) {
				$CONFIG[$row[0]] = $row[1];
			}
			$CONFIG['cookie_name'] = $this->dir;
			$CONFIG['cookie_path'] = \Dragonfly::getKernel()->CFG->cookie->path;
			$this->config = $CONFIG;
		}
		if (property_exists($this, $k)) {
			return $this->$k;
		}
		trigger_error('Unknown property ' . __CLASS__ . '::' . $k);
	}

	public function buildUrl($file, array $params = array())
	{
		$uri = $this->dir;
		if ($file) {
			$uri .= "&file={$file}";
		}
		if (!empty($params['album'])) {
			$uri .= "&album={$params['album']}";
		} else if (!empty($params['cat'])) {
			$uri .= "&cat={$params['cat']}";
		}
		if ('ecard' !== $file && empty($params['slideshow'])) {
			if (!empty($params['meta'])) {
				if ('random' === $params['meta']) {
					$params['pos'] = null;
				} else {
					$params['pid'] = null;
				}
			}
			if (!empty($params['album']) && isset($params['pos'])) {
				$params['pid'] = null;
			}
		}
		if (!empty($params['sort'])) {
			$CONFIG = $this->__get('config');
			if (!empty($params['meta']) || $params['sort'] == $CONFIG['default_sort_order']) {
				$params['sort'] = null;
			}
		}
		foreach (array('meta', 'pos', 'pid', 'page', 'sort', 'slideshow', 'addfav') as $p) {
			if (!empty($params[$p])) {
				$uri .= "&{$p}={$params[$p]}";
			}
		}
		return \URL::index($uri);
	}

	// Return the url for a picture, allows to have pictures spreaded over multiple servers
	// old: get_pic_url
	public function getImageUrl(&$pic)
	{
		return \URL::index("{$this->dir}&file=displayimage&pid={$pic['pid']}");
	}

	public function getImageMetaUrl($meta, $pos, $cat = 0)
	{
		return $this->buildUrl('displayimage', array('cat' => $cat, 'meta' => $meta, 'pos' => $pos));
	}

	public function getPicSrc(&$pic_row, $mode)
	{
		$CONFIG = $this->__get('config');
		$filename = $pic_row['filename'];
		switch ($mode)
		{
			case 'thumb':
				$filename = $CONFIG['thumb_pfx'] . $filename;
				break;
			case 'normal':
				$filename = $CONFIG['normal_pfx'] . $filename;
				break;
		}
		return DOMAIN_PATH . str_replace('%2F', '/', rawurlencode($pic_row['filepath'] . $filename));
	}

	private array $USER_ALBUMS = array(0 => array());
	public function getUserAlbums($user_id)
	{
		$user_id = (int)$user_id;
		if (!isset($this->USER_ALBUMS[$user_id])) {
			$db = \Dragonfly::getKernel()->SQL;
			$CONFIG = $this->__get('config');
			$this->USER_ALBUMS[$user_id] = $db->uFetchAll("SELECT aid, title, category FROM {$CONFIG['TABLE_ALBUMS']}
			WHERE user_id = {$user_id} AND (category=".static::USER_GAL_CAT." OR category=" . (static::FIRST_USER_CAT + $user_id) . ") ORDER BY title");
		}
		return $this->USER_ALBUMS[$user_id];
	}

	public function getUploadableAlbums($user_id)
	{
		$result = array();
		$user_id = (int)$user_id;

		$albums = $this->getUserAlbums($user_id);
		if ($albums) {
			$SQL = \Dragonfly::getKernel()->SQL;
			list($nickname) = $SQL->uFetchRow("SELECT username FROM {$SQL->TBL->users} WHERE user_id = {$user_id}");
			$result[] = array(
				'label' => $nickname,
				'albums' => $albums
			);
		}

		$albums = $this->getPublicUploadableAlbums(static::getUserGroups($user_id));
		if ($albums) {
			$result[] = array(
				'label' => \Dragonfly::getKernel()->L10N['Public albums'],
				'albums' => $albums
			);
		}

		return $result;
	}

	protected function getPublicUploadableAlbums($grouplist, $cat = 0, $title = '')
	{
		global $module_name;
		$db = \Dragonfly::getKernel()->SQL;
		$CONFIG = $this->__get('config');
		$list = array();

		$upload = can_admin($module_name) ? '' : "AND uploads = 1 AND visibility IN (0,{$grouplist})";
		$result = $db->query("SELECT aid, title, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = {$cat} {$upload} ORDER BY title");
		while ($row = $result->fetch_assoc()) {
			$row['title'] = $title . $row['title'];
			$list[] = $row;
		}

		$result = $db->query("SELECT cid, catname FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent = {$cat} ORDER BY catname");
		while ($row = $result->fetch_row()) {
			$list = array_merge($list, $this->getPublicUploadableAlbums($grouplist, $row[0], $title . $row[1] . ' > '));
		}

		return $list;
	}

	public static function getInstances()
	{
		// load required settings for this install
		if (!is_array(static::$instances)) {
			static::$instances = array();
			$db = \Dragonfly::getKernel()->SQL;
			$result = $db->query("SELECT dirname, prefix FROM {$db->TBL->cpg_installs} ORDER BY cpg_id");
			while ($row = $result->fetch_row()) {
				if (!static::$MAIN_DIR) {
					static::$MAIN_DIR = $row[0];
				}
				static::$instances[$row[0]] = new self($row[0], $row[1]);
			}
		}
		return static::$instances;
	}

	public static function getInstance($dirname = null)
	{
		static::getInstances();
		if (!$dirname) { $dirname = $GLOBALS['module_name']; }
		if (isset(static::$instances[$dirname])) {
			return static::$instances[$dirname];
		}
	}

	public static function getInstanceDBPrefix($dirname)
	{
		$instance = static::getInstance($dirname);
		return $instance ? $instance->prefix : false;
	}

	// Retrieve configuration
	public static function getConfigInstance($dirname)
	{
		$instance = static::getInstance($dirname);
		if (!$instance) {
			URL::redirect(URL::admin('modules'));
		}
		return $instance->__get('config');
	}

	public static function getCurrentUserData()
	{
		static $USER_DATA;
		if (!is_array($USER_DATA)) {
			$db = \Dragonfly::getKernel()->SQL;
			$ID = \Dragonfly::getKernel()->IDENTITY;
			$uid = (int)$ID->isMember();
			$group_id = ($uid && $ID->active_cp) ? $ID->group_cp : self::ANON_GROUP;
			$USER_DATA = $db->uFetchAssoc("SELECT * FROM {$db->TBL->cpg_usergroups} WHERE group_id = {$group_id}") ?: array();
			$USER_DATA['ID'] = $USER_DATA ? $uid : 0;
			if (self::ANON_GROUP == $group_id) {
				$USER_DATA['GROUPS'] = $group_id;
				$USER_DATA['has_admin_access'] = false;
			} else {
				$USER_DATA['GROUPS'] = static::getUserGroups($uid);
				$USER_DATA['has_admin_access'] = !empty($USER_DATA['has_admin_access']);
			}
			$USER_DATA['can_create_albums']   = !empty($USER_DATA['can_create_albums']);
			$USER_DATA['can_post_comments']   = !empty($USER_DATA['can_post_comments']);
			$USER_DATA['can_upload_pictures'] = !empty($USER_DATA['can_upload_pictures']);
			$USER_DATA['can_rate_pictures']   = !empty($USER_DATA['can_rate_pictures']);
			$USER_DATA['can_send_ecards']     = !empty($USER_DATA['can_send_ecards']);
		}
		return $USER_DATA;
	}

	protected static function getUserGroups($user_id)
	{
		static $USER_GROUPS = array();
		if (!isset($USER_GROUPS[$user_id])) {
			$user_id = (int)$user_id;
			$SQL = \Dragonfly::getKernel()->SQL;
			$user = $SQL->uFetchRow("SELECT user_active_cp, user_group_cp, user_group_list_cp FROM {$SQL->TBL->users} WHERE user_id = {$user_id}");
			$group_id = ($user && $user[0]) ? $user[1] : self::ANON_GROUP;
			$grouplist = array($group_id);
			if (self::ANON_GROUP != $group_id) {
				$grouplist[] = static::FIRST_USER_CAT + $user_id;
				foreach (explode(',', $user[2]) as $group) {
					$grouplist[] = (int)$group;
				}
			}
			$USER_GROUPS[$user_id] = implode(',', array_unique($grouplist));
		}
		return $USER_GROUPS[$user_id];
	}

	// function added for blocks, takes a string and shortens it for display with ellipsis
	// old: truncate_stringblocks
	public static function truncateString($str, $maxlength = 20)
	{
		if (mb_strlen($str) > $maxlength) {
			return mb_substr($str, 0, $maxlength) . '…';
		}
		return $str;
	}

	public static function getRatingStars($rating)
	{
		$rating = round($rating / 2000);
		$stars = '';
		for ($i = 0; $i < $rating; ++$i) {
			$stars .= '★';
		}
		for (; $i < 5; ++$i) {
			$stars .= '☆';
		}
		return $stars;
	}

}
