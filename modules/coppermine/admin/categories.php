<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($op)) { exit; }
require("modules/{$op}/include/load.inc");

abstract class Coppermine_CatMgr
{

	public static
		$categories = array();

	public static function getSelectBox($highlight = 0, $curr_cat)
	{
		static::get_subcat_data();
		$lb = array(array(
			'value' => 0,
			'label' => NO_CATEGORY,
			'current' => $highlight == 0,
		));
		foreach (static::$categories as $category) {
			if ($category['cid'] != 1 && $category['cid'] != $curr_cat) {
				$lb[] = array(
					'value' => $category['cid'],
					'label' => $category['name'],
					'current' => $highlight == $category['cid'],
				);
			}
		}
		return $lb;
	}

	public static function getCategories($parent=0)
	{
		global $CONFIG, $db;
		$parent = intval($parent);
		$items  = array();
		$result = $db->query("SELECT cid id, catname name FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent = {$parent} ORDER BY pos");
		foreach ($result as $cat) {
			$cat['children'] = self::getCategories($cat['id']);
			$items[] = $cat;
		}
		return $items;
	}

	// Fix categories that have an invalid parent
	public static function fix_cat_table()
	{
		global $CONFIG, $db;
		$result = $db->query("SELECT cid FROM {$CONFIG['TABLE_CATEGORIES']}");
		if ($result->num_rows) {
			$set = array();
			while ($row = $result->fetch_row()) { $set[] = $row[0]; }
			$db->exec('UPDATE '.$CONFIG['TABLE_CATEGORIES'].' SET parent = 0 WHERE parent=cid OR parent NOT IN ('.implode(',',$set).')');
		}
		static::get_subcat_data();
		foreach (static::$categories as $category) {
			$db->exec("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos={$category['pos']} WHERE cid = {$category['cid']}");
		}
	}

	private static function get_subcat_data($parent = 0, $ident = '')
	{
		global $CONFIG, $db;
		$parent = intval($parent);
		$result = $db->query("SELECT cid, catname name, parent FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent = {$parent} ORDER BY pos");
		$prev_cid = $last_index = 0;
		foreach ($result as $pos => $subcat) {
			$subcat['pos']  = $pos;
			$subcat['last'] = $pos==$result->num_rows-1;
			$subcat['name'] = $ident . $subcat['name'];
			if ($pos > 0) {
				$subcat['prev']  = $prev_cid;
				static::$categories[count(static::$categories)-1]['next'] = $subcat['cid'];
			}
			static::$categories[] = $subcat;
			$prev_cid   = $subcat['cid'];
			static::get_subcat_data($subcat['cid'], $ident . '&nbsp;&nbsp;&nbsp;');
		}
	}

}

if (isset($_GET['delete'])) {
	$cid = $_GET->uint('delete');
	if (1 == $cid) { cpg_error(USERGAL_CAT_RO); }
	$cat = $db->uFetchRow("SELECT catname, parent FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid = {$cid}");
	if (!$cat) { cpg_error(UNKNOWN_CAT, 404); }
	if ('POST' !== $_SERVER['REQUEST_METHOD']) {
		\Dragonfly\Page::confirm(URL::admin("&file=categories&delete={$cid}"), CONFIRM_DELETE_CAT.': '.$cat[0]);
	} else if (isset($_POST['confirm'])) {
		$parent = $cat[1];
		$db->exec("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent={$parent} WHERE parent = {$cid}");
		$db->exec("UPDATE {$CONFIG['TABLE_ALBUMS']} SET category={$parent} WHERE category = {$cid}");
		$db->exec("DELETE FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid={$cid}");
		Coppermine_CatMgr::fix_cat_table();
	}
}

if ('POST' === $_SERVER['REQUEST_METHOD'])
{

	if (isset($_POST['move_item'])) {
		$cid = $_POST->uint('move_item');
		$cat = $db->uFetchAssoc("SELECT pos, parent FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid={$cid}");
		if (!$cat) cpg_error(UNKNOWN_CAT, 404);

		$db->exec("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos=pos-1 WHERE pos>{$cat['pos']} AND parent={$cat['parent']}");

		$pid = $_POST->uint('parent_id');
		$aid = $_POST->uint('after_id');
		$pos = -1;
		if ($aid) {
			$p = $db->uFetchRow("SELECT pos, parent FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid={$aid}");
			if ($p) {
				$pos = $p[0];
				$pid = $p[1];
			}
		}
		$db->exec("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos=pos+1 WHERE pos>{$pos} AND parent={$pid}");

		++$pos;
		$db->exec("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos={$pos}, parent={$pid} WHERE cid={$cid}");

		Coppermine_CatMgr::fix_cat_table();

		header('Content-Type: application/json');
		exit(json_encode(array('moved'=>true)));
	}

	switch ($_POST->raw('oppe'))
	{
		case 'savecat':
			$cid = $_POST->uint('cid');
			if (!isset($_POST['parent'], $_POST['catname'], $_POST['description'])) {
				cpg_error(sprintf(MISS_PARAM, 'savecat'));
			}
			$parent = $_POST->uint('parent');
			$catname = $db->escape_string(trim($_POST['catname'])) ?: '&lt;???&gt;';
			$description = $db->escape_string(trim($_POST['description']));
			if ($cid) {
				$db->exec("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent={$parent}, catname='{$catname}', description='{$description}' WHERE cid = {$cid}");
			} else {
				$db->exec("INSERT INTO {$CONFIG['TABLE_CATEGORIES']} (pos, parent, catname, description) VALUES (10000, {$parent}, '{$catname}', '{$description}')");
			}
			Coppermine_CatMgr::fix_cat_table();
			break;
	}
	URL::redirect(URL::admin("&file=categories"));
}

$OUT = Dragonfly::getKernel()->OUT;

if (isset($_GET['edit'])) {
	$cid = $_GET->uint('edit');
	if (!$cid) { cpg_error(UNKNOWN_CAT, 404); }
	$current_category = $db->uFetchAssoc("SELECT cid, catname name, parent, description FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid = {$cid}");
	if (!$current_category) { cpg_error(UNKNOWN_CAT, 404); }
	$OUT->catlist = false;
}

else {
	\Dragonfly\Output\Css::add('poodle/tree');
	\Dragonfly\Output\Js::add('includes/javascript/poodle.js');
//	\Dragonfly\Output\Js::add('includes/poodle/javascript/tree.js');
	\Dragonfly\Output\Js::add('modules/coppermine/javascript/catmgr.js');
	$OUT->catlist = true;
	$current_category = array('cid' => 0, 'parent' => 0, 'name' => '', 'description' => '');
}

\Dragonfly\Page::title(MANAGE_CAT);
$OUT->catselect = Coppermine_CatMgr::getSelectBox($current_category['parent'], $current_category['cid']);
$OUT->category  = $current_category;
$OUT->display('coppermine/admin/categories');
