<?php
/*********************************************************************
	admin_forums.php
	-------------------

	begin     : Thursday, Jul 12, 2001
	copyright : (C) 2001 The phpBB Group
	email     : support@phpbb.com
	-------------------

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	-------------------

	Modifications made by CPG Dragonfly CMS https://dragonfly.coders.exchange
**********************************************************************/

namespace Dragonfly\Module\Forums\Admin;

if (!defined('ADMIN_PAGES')) { exit; }

# Begin function block
function renumber_order($mode, $cat = 0)
{
	global $db;
	switch ($mode)
	{
		case 'category':
			$table = CATEGORIES_TABLE;
			$idfield = 'cat_id';
			$orderfield = 'cat_order';
			$cat = 0;
			break;

		case 'forum':
			$table = FORUMS_TABLE;
			$idfield = 'forum_id';
			$orderfield = 'forum_order';
			$catfield = 'cat_id';
			break;

		default:
			message_die(GENERAL_ERROR, 'Wrong mode for generating select list');
			return;
	}

	$where = $cat ? " WHERE {$catfield} = {$cat}" : "";
	$result = $db->query("SELECT {$idfield} FROM {$table} {$where} ORDER BY {$orderfield} ASC");
	$i = 10;
	while ($row = $result->fetch_row()) {
		$db->query("UPDATE {$table} SET {$orderfield} = {$i} WHERE {$idfield} = ". $row[0]);
		$i += 10;
	}
}
# End function block

abstract class Forums
{
	# Show form to create/modify a forum
	public static function edit($forum_id)
	{
		$forum = new \Dragonfly\Forums\Forum($forum_id);

		if (!$forum->id) {
			(int) list ($forum->cat_id) = array_keys($_POST['addforum']);
			$forum->name   = $_POST['forumname'][$forum->cat_id];
		}

		global $template;
		$template->forum = $forum;
		$template->set_handle('body', 'Forums/admin/forum_edit');
	}

	public static function getList($exclude_id)
	{
		global $db;

		$result = $db->query("SELECT forum_id, forum_name FROM ".FORUMS_TABLE." WHERE forum_id <> ".intval($exclude_id));

		$list = '';
		while ($row = $result->fetch_row()) {
			$list .= "<option value=\"{$row[0]}\">{$row[1]}</option>\n";
		}
		$result->free();
		return $list;
	}

	public static function getParentsList($cat_id, $parent_id)
	{
		global $db;
		$list = "<option value=\"0\">None</option>\n";
		$result = $db->query("SELECT forum_id, forum_name FROM ".FORUMS_TABLE." WHERE forum_type < 2 AND cat_id = ".intval($cat_id));
		if ($result->num_rows) {
			while ($row = $result->fetch_row()) {
				$s = '';
				if ($parent_id == $row[0]) { $s = ' selected="selected"'; }
				$list .= "<option value=\"{$row[0]}\"{$s}>{$row[1]}</option>\n";
			}
		}
		$result->free();
		return $list;
	}

	# Create/Modify a forum in the DB
	public static function save()
	{
		if (!trim($_POST['forumname'])) {
			message_die(GENERAL_ERROR, 'Can\'t have a forum without a name');
			return;
		}

		$forum = new \Dragonfly\Forums\Forum($_POST['f']);
		$forum->id        = $_POST['forum_id'];
		$forum->name      = $_POST['forumname'];
		$forum->cat_id    = $_POST['c'];
		$forum->parent_id = $_POST['parentid'];
		$forum->desc      = $_POST['forumdesc'];
		$forum->status    = $_POST['forumstatus'];
		$forum->type      = $_POST['forumtype'];
		$forum->link      = $_POST['forumlink'];
		$forum->prune_enable = !empty($_POST['prune_enable']);
		$forum->prune_days   = $_POST['prune_days'];
		$forum->prune_freq   = $_POST['prune_freq'];
		$forum->archive_enable = !empty($_POST['archive_enable']);
		$forum->archive_days   = $_POST['archive_days'];
		$forum->archive_freq   = $_POST['archive_freq'];
		return $forum->save();
	}
}

abstract class Categories
{
	# Create a category in the DB
	public static function create($categoryname)
	{
		global $db, $module_name;

		if (!trim($categoryname)) {
			return message_die(GENERAL_ERROR, 'Can\'t create a category without a name');
		}

		list($next_order) = $db->uFetchRow('SELECT MAX(cat_order) FROM '. CATEGORIES_TABLE);
		$next_order += 10;

		# There is no problem having duplicate forum names so we won't check for it.
		$db->query('INSERT INTO '. CATEGORIES_TABLE. " (cat_title, cat_order)
		VALUES (". $db->quote(trim($categoryname)). ", $next_order)");
		\BoardCache::cacheDelete('categories');
		return true;
	}

	public static function getList($id, $select)
	{
		global $db;

		$sql = "SELECT cat_id, cat_title FROM ".CATEGORIES_TABLE;
		if (!$select) { $sql .= " WHERE cat_id <> $id"; }
		if (!$result = $db->query($sql)) {
			message_die(GENERAL_ERROR, 'Couldn\'t get list of Categories/Forums');
			return;
		}

		$list = '';
		while ($row = $result->fetch_row()) {
			$s = '';
			if ($id == $row[0]) { $s = ' selected="selected"'; }
			$list .= "<option value=\"{$row[0]}\"{$s}>{$row[1]}</option>\n";
		}
		return $list;
	}

	public static function getInfo($id)
	{
		global $db;
		$table = CATEGORIES_TABLE;
		$return = $db->uFetchAssoc("SELECT * FROM {$table} WHERE cat_id = {$id}");
		if (!$return) {
			message_die(GENERAL_ERROR, 'Forum/Category doesn\'t exist or multiple forums/categories with ID '. $id);
			return;
		}
		list($return['number']) = $db->uFetchRow("SELECT COUNT(*) FROM {$table}");
		return $return;
	}

}

$mode = $_POST->txt('mode') ?: $_GET->txt('mode');
if (isset($_POST['addforum']) || isset($_POST['addcategory'])) {
	$mode = isset($_POST['addforum']) ? 'addforum' : 'addcat';
}

if ($mode) {
	switch ($mode)
	{
		case 'addforum':
		case 'editforum':
			Forums::edit($_GET['f']);
			return;

		case 'saveforum':
			Forums::save();
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, $_SERVER['REQUEST_URI']);
			return;

		case 'addcat':
			Categories::create($_POST['categoryname']);
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, $_SERVER['REQUEST_URI']);
			return;

		case 'editcat':
			# Show form to edit a category
			$template->forums_category = Categories::getInfo(intval($_GET['c']));
			$template->set_handle('body', 'Forums/admin/category_edit');
			return;

		case 'modcat':
			# Modify a category in the DB
			$db->query("UPDATE ". CATEGORIES_TABLE. "
			SET cat_title = ". $db->quote(trim($_POST['cat_title'])). "
			WHERE cat_id = ". intval($_POST['c']));
			\BoardCache::cacheDelete('categories');
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, \URL::admin('Forums&do=forums'));
			return;

		case 'deleteforum':
			# Show form to delete a forum
			$template->forum = new \Dragonfly\Forums\Forum($_GET['f']);
			$template->set_handle('body', 'Forums/admin/forum_delete');
			return;

		case 'movedelforum':
			# Move or delete a forum in the DB
			$from_id = intval($_GET['f']);
			$to_id = intval($_POST['to_id']);
			$delete_old = isset($_POST['delete_old']) ? intval($_POST['delete_old']) : 0;

			# Either delete or move all posts in a forum
			if ($to_id == -1) {
				# Delete polls in this forum
				$result = $db->query("SELECT topic_id FROM ". TOPICS_TABLE. " WHERE forum_id = {$from_id}");
				if ($result->num_rows) {
					$topic_ids = array();
					while ($row = $result->fetch_row()) { $topic_ids[] = $row[0]; }
					\Dragonfly\Forums\Poll::deleteFromTopics($topic_ids);
				}
				unset($result);

				$forum = new \Dragonfly\Forums\Forum($from_id);
				$forum->prune(0, true);   # Delete everything from forum
				$forum->archive(0, true); # Relocate everything from forum
			} else {
				$result = $db->query('SELECT * FROM '. FORUMS_TABLE. "
				WHERE forum_id IN ({$from_id}, {$to_id})");
				if ($result->num_rows != 2) {
					message_die(GENERAL_ERROR, 'Ambiguous forum ID\'s');
					return;
				}
				$db->query('UPDATE '. TOPICS_TABLE. " SET forum_id = {$to_id} WHERE forum_id = {$from_id}");
				$db->query('UPDATE '. POSTS_TABLE . " SET forum_id = {$to_id} WHERE forum_id = {$from_id}");
				$db->query('UPDATE '. POSTS_ARCHIVE_TABLE . " SET forum_id = {$to_id} WHERE forum_id = {$from_id}");
				$forum = new \Dragonfly\Forums\Forum($to_id);
				$forum->sync();
			}

			# Alter Mod level if appropriate - 2.0.4
			$result = $db->query("SELECT ug.user_id
			FROM " . AUTH_ACCESS_TABLE . " a, {$db->TBL->bbuser_group} ug
			WHERE a.forum_id <> {$from_id}
			  AND a.auth_mod = 1
			  AND ug.group_id = a.group_id");

			if ($result->num_rows) {
				$user_ids = array();
				while ($row = $result->fetch_row()) { $user_ids[] = $row[0]; }
				$user_ids = implode(',', $user_ids);

				$result = $db->query("SELECT ug.user_id
				FROM " . AUTH_ACCESS_TABLE . " a, {$db->TBL->bbuser_group} ug
				WHERE a.forum_id = {$from_id}
				  AND a.auth_mod = 1
				  AND ug.group_id = a.group_id
				  AND ug.user_id NOT IN ({$user_ids})");
				if ($result->num_rows) {
					$user_ids = array();
					while ($row = $result->fetch_row()) { $user_ids[] = $row[0]; }
					$user_ids = implode(',', $user_ids);

					$db->query("UPDATE {$db->TBL->users}
					SET user_level = " . \Dragonfly\Identity::LEVEL_USER . "
					WHERE user_id IN ({$user_ids})
					  AND user_level <> " . \Dragonfly\Identity::LEVEL_ADMIN);
				}
			}

			$db->query('DELETE FROM '. FORUMS_TABLE. " WHERE forum_id = {$from_id}");
			$db->query('DELETE FROM '. AUTH_ACCESS_TABLE. " WHERE forum_id = {$from_id}");

			\BoardCache::cacheDelete('categories');
			\BoardCache::cacheDelete('forums');
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, \URL::admin('Forums&do=forums'));
			return;

		case 'deletecat':
			# Show form to delete a category
			$cat_id = intval($_GET['c']);

			$catinfo = Categories::getInfo($cat_id);

			if ($catinfo['number'] == 1) {
				list($count) = $db->uFetchRow('SELECT COUNT(*) FROM '. FORUMS_TABLE);

				if ($count > 0) {
					message_die(GENERAL_ERROR, $lang['Must_delete_forums']);
					return;
				} else {
					$select_to = $lang['Nowhere_to_move'];
				}
			} else {
				$select_to = Categories::getList($cat_id, 0);
			}

			$template->forums_category = $catinfo;
			$template->S_SELECT_TO = $select_to;
			$template->set_handle('body', 'Forums/admin/category_delete');
			return;

		case 'movedelcat':
			# Move or delete a category in the DB
			$from_id = intval($_GET['c']);
			$to_id = isset($_POST['to_id']) ? intval($_POST['to_id']) : '';

			if (!empty($to_id)) {
				$result = $db->query('SELECT * FROM '. CATEGORIES_TABLE. "
				WHERE cat_id IN ({$from_id}, {$to_id})");
				if ($result->num_rows != 2) {
					message_die(GENERAL_ERROR, 'Ambiguous category ID\'s');
					return;
				}
				$db->query('UPDATE '. FORUMS_TABLE. "
				SET cat_id = $to_id
				WHERE cat_id = {$from_id}");
			}
			$db->query('DELETE FROM '. CATEGORIES_TABLE. " WHERE cat_id = {$from_id}");

			\BoardCache::cacheDelete('categories');
			\BoardCache::cacheDelete('forums');
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, \URL::admin('Forums&do=forums'));
			return;

		case 'forum_order':
			# Change order of forums in the DB
			$move = intval($_GET['move']);
			$forum = new \Dragonfly\Forums\Forum($_GET['f']);
			$db->query('UPDATE '. FORUMS_TABLE. "
			SET forum_order = forum_order + {$move}
			WHERE forum_id = {$forum->id}");
			renumber_order('forum', $forum->cat_id);
			\BoardCache::cacheDelete('categories');
			\BoardCache::cacheDelete('forums');
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, \URL::admin('Forums&do=forums'));
			break;

		case 'cat_order':
			# Change order of categories in the DB
			$move = intval($_GET['move']);
			$cat_id = intval($_GET['c']);
			$db->query('UPDATE '. CATEGORIES_TABLE. "
				SET cat_order = cat_order + $move
				WHERE cat_id = $cat_id");
			renumber_order('category');
			\BoardCache::cacheDelete('categories');
			\BoardCache::cacheDelete('forums');
			\Dragonfly::closeRequest($lang['Forums_updated'], 200, \URL::admin('Forums&do=forums'));
			break;

		case 'forum_sync':
			$forum = new \Dragonfly\Forums\Forum($_GET['f']);
			$forum->sync();
			break;

		default:
			\Dragonfly::closeRequest($lang['No_mode'], 404);
			return;
	}
}

$auth_vals = array(
	0 => $lang['Forum_ALL'],
	1 => $lang['Forum_REG'],
	2 => $lang['Forum_PRIVATE'],
	3 => $lang['Forum_MOD'],
	5 => $lang['Forum_ADMIN']
);

# Start page proper
$template->set_handle('body', 'Forums/admin/forums_list');

$categories = \BoardCache::categories();

$template->board_categories = array();
if ($categories) {

	$forum_rows = $db->uFetchAll('SELECT * FROM '. FORUMS_TABLE. ' ORDER BY cat_id, forum_order');
	foreach ($categories as $cat)
	{
		$cat_id = $cat['id'];
		$cat = array(
			'id' => $cat_id,
			'title' => $cat['title'],
			'U_EDIT' => \URL::admin("&do=forums&mode=editcat&c={$cat_id}"),
			'U_DELETE' => \URL::admin("&do=forums&mode=deletecat&c={$cat_id}"),
			'U_MOVE_UP' => \URL::admin("&do=forums&mode=cat_order&move=-15&c={$cat_id}"),
			'U_MOVE_DOWN' => \URL::admin("&do=forums&mode=cat_order&move=15&c={$cat_id}"),
			'U_VIEW' => \URL::index("&file=index&c={$cat_id}"),
			'forums' => array(),
		);
		foreach ($forum_rows as $forum) {
			if ($forum['cat_id'] == $cat_id) {
				$forum_id = $forum['forum_id'];
				$cat['forums'][] = array(
					'NAME' => $forum['parent_id'] ? $forum['forum_name'] : '<b>'.$forum['forum_name'].'</b>',
					'DESC' => $forum['forum_desc'],
					'NUM_TOPICS' => $forum['forum_topics'],
					'NUM_POSTS' => $forum['forum_posts'],
					'AUTH_READ' => $auth_vals[$forum['auth_read']],
					'AUTH_POST' => $auth_vals[$forum['auth_post']],
					'U_VIEW' => \URL::index("&file=viewforum&f={$forum_id}"),
					'U_EDIT' => \URL::admin("&do=forums&mode=editforum&f={$forum_id}"),
					'U_DELETE' => \URL::admin("&do=forums&mode=deleteforum&f={$forum_id}"),
					'U_MOVE_UP' => \URL::admin("&do=forums&mode=forum_order&move=-15&f={$forum_id}"),
					'U_MOVE_DOWN' => \URL::admin("&do=forums&mode=forum_order&move=15&f={$forum_id}"),
					'U_RESYNC' => \URL::admin("&do=forums&mode=forum_sync&f={$forum_id}"),
					'U_PERMS' => \URL::admin("&do=forumauth&f={$forum_id}")
				);
			}
		}
		$template->board_categories[] = $cat;
	}
}
