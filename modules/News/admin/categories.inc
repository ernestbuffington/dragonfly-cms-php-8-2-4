<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('news')) { die('Access Denied'); }

$mode = isset($_POST['mode']) ? $_POST['mode'] : $_GET['mode'];

$OUT = \Dragonfly::getKernel()->OUT;

if (isset($_GET['edit'])) {
	\Dragonfly\Page::title($OUT->L10N['_CATEGORIESADMIN']);
	if (!empty($_GET['edit'])) {
		$OUT->news_category = $db->uFetchAssoc("SELECT catid id, title FROM {$db->TBL->stories_cat} WHERE catid = " . $_GET->uint('edit'));
	}
	$OUT->display('News/admin/category');
}

else if (isset($_GET['del'])) {
	$cat = $_GET->uint('del');
	if (isset($_POST['move'])) {
		$db->query("UPDATE {$db->TBL->stories} SET catid={$_POST->uint('newcat')} WHERE catid={$cat}");
		$db->query("DELETE FROM {$db->TBL->stories_cat} WHERE catid={$cat}");
		\Poodle\Notify::success($OUT->L10N['_MOVEDONE']);
		\Dragonfly::closeRequest($OUT->L10N['_CATDELETED'], 200, URL::admin('&file=categories'));
	}
	else if (isset($_POST['delete'])) {
		$db->query("DELETE FROM {$db->TBL->comments} WHERE sid IN (SELECT sid FROM {$db->TBL->stories} WHERE catid = {$cat})");
		$db->query("DELETE FROM {$db->TBL->stories} WHERE catid = {$cat}");
		$db->query("DELETE FROM {$db->TBL->stories_cat} WHERE catid = {$cat}");
		\Dragonfly::closeRequest($OUT->L10N['_CATDELETED'], 200, URL::admin('&file=categories'));
	}
	else {
		list($title) = $db->uFetchRow("SELECT title FROM {$db->TBL->stories_cat} WHERE catid={$cat}");
		\Dragonfly\Page::title($OUT->L10N['_DELETECATEGORY'].' '._BC_DELIM.' '.$OUT->L10N['_CATEGORIESADMIN']);
		$OUT->news_category = array(
			'title' => $title,
			'stories' => $db->TBL->stories->count("catid={$cat}"),
		);
		if ($OUT->news_category['stories']) {
			$OUT->news_categories = $db->uFetchAll("SELECT catid id, title FROM {$db->TBL->stories_cat}");
		}
		$OUT->display('News/admin/category_del');
	}
}

else if ('save' == $mode) {
	$title = trim($_POST['title']);
	if (empty($title) || $db->TBL->stories_cat->count("LOWER(title) = LOWER({$db->quote($title)})")) {
		cpg_error(sprintf(_ERROR_ALREADYEXIST, $OUT->L10N['_CATEGORYNAME']));
	} else if (isset($_POST['catid'])) {
		$db->TBL->stories_cat->update(array('title' => $title), 'catid='.$_POST->uint('catid'));
		$what = _TASK_COMPLETED;
	} else {
		$db->TBL->stories_cat->insert(array('title' => $title));
		$what = $OUT->L10N['_CATADDED'];
	}
	\Dragonfly::closeRequest($what, 200, URL::admin('&file=categories'));
}

else {
	\Dragonfly\Page::title($OUT->L10N['_CATEGORIESADMIN']);
	$OUT->news_categories = $db->query("SELECT catid id, title FROM {$db->TBL->stories_cat}");
	$OUT->display('News/admin/categories');
}

CloseTable();
