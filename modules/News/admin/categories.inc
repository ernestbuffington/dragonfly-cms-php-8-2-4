<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/admin/categories.inc,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 02:10:48 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('news')) { die('Access Denied'); }
$mode = isset($_POST['mode']) ? $_POST['mode'] : $_GET['mode'];

if ($mode == 'catadd') {
	show_head(_CATEGORIESADMIN);
	echo open_form(adminlink('&amp;mode=catsave'), 'addcategory', _CATEGORYADD).'
	<strong>'._CATEGORYNAME.':</strong> <input type="text" name="title" size="22" maxlength="20" /><br />
	<input type="submit" value="'._ADD.'" />'.close_form();
}

else if ($mode == 'catedit') {
	show_head(_CATEGORIESADMIN);
	if (!isset($_POST['catid'])) {
		$selcat = $db->sql_query("select catid, title from ".$prefix."_stories_cat");
		$cats = array();
		while(list($catid, $title) = $db->sql_fetchrow($selcat)) { $cats[$catid] = $title; }
		echo open_form(adminlink('&amp;mode=catedit'), 'editcategory', _EDITCATEGORY).'
		<strong>'._ASELECTCATEGORY.'</strong> '
		.select_box('catid', 0, $cats).'<br /><input type="submit" value="'._EDIT.'" />';
	} else {
		$catid = intval($_POST['catid']);
		$result = $db->sql_query("SELECT title FROM ".$prefix."_stories_cat WHERE catid='$catid'");
		list($title) = $db->sql_fetchrow($result);
		echo open_form(adminlink('&amp;mode=catsave'), 'editcategory', _EDITCATEGORY).'<strong>'._CATEGORYNAME.':</strong>
		<input type="text" name="title" size="22" maxlength="20" value="'.$title.'" />
		<input type="hidden" name="catid" value="'.$catid.'" />
		<br /><input type="submit" value="'._SAVECHANGES.'" />';
	}
	echo close_form();
}

else if ($mode == 'catdel') {
	show_head(_CATEGORIESADMIN.' '._BC_DELIM.' '._DELETECATEGORY);
	if (!isset($_POST['cat'])) {
		$selcat = $db->sql_query('SELECT catid, title FROM '.$prefix.'_stories_cat');
		$cats = array();
		while(list($catid, $title) = $db->sql_fetchrow($selcat)) { $cats[$catid] = $title; }
		echo open_form(adminlink('&amp;mode=catdel'), 'deletecategory').'<strong>'._SELECTCATDEL.': </strong><br /><br />'
		.select_box('cat', 0, $cats).'<br /><input type="submit" value="Delete" />'.close_form();
	} else {
		$cat = intval($_POST['cat']);
		$result = $db->sql_query("SELECT title FROM ".$prefix."_stories_cat WHERE catid='$cat'");
		list($title) = $db->sql_fetchrow($result);
		$result2 = $db->sql_query('SELECT * FROM '.$prefix."_stories WHERE catid='$cat'");
		$numrows = $db->sql_numrows($result2);
		if ($numrows < 1) {
			$db->sql_query('DELETE FROM '.$prefix."_stories_cat WHERE catid='$cat'");
			echo '<br /><br />'._CATDELETED.'<br /><br />'._GOTOADMIN;
		} else {
			echo '<br /><br /><strong>'._WARNING.':</strong> '._THECATEGORY." <strong>$title</strong> "._HAS." <strong>$numrows</strong> "._STORIESINSIDE."<br />"
		._DELCATWARNING1.'<br />'._DELCATWARNING2.'<br /><br />'._DELCATWARNING3.'<br /><br />'
		.'<strong>[ <a href="'.adminlink("&amp;mode=catdely&amp;catid=$cat").'">'._YESDEL.'</a> | '
		.'<a href="'.adminlink("&amp;mode=catdeln&amp;catid=$cat").'">'._NOMOVE.'</a> ]</strong>';
		}
	}
}

else if ($mode == 'catdely') {
	$catid = intval($_GET['catid']);
	$result = $db->sql_query("SELECT sid FROM ".$prefix."_stories WHERE catid='$catid'");
	while(list($sid) = $db->sql_fetchrow($result)) {
		$db->sql_query("DELETE FROM ".$prefix."_comments WHERE sid='$sid'");
	}
	$db->sql_query("DELETE FROM ".$prefix."_stories WHERE catid='$catid'");
	$db->sql_query("DELETE FROM ".$prefix."_stories_cat WHERE catid='$catid'");
	url_redirect(adminlink());
}

else if ($mode == 'catdeln') {
	$catid = isset($_POST['catid']) ? intval($_POST['catid']) : intval($_GET['catid']);
	show_head(_CATEGORIESADMIN.' '._BC_DELIM.' '._MOVESTORIES);
	if (!isset($_POST['newcat'])) {
		$result = $db->sql_query("SELECT title FROM ".$prefix."_stories_cat WHERE catid='$catid'");
		list($title) = $db->sql_fetchrow($result);
		$selcat = $db->sql_query("SELECT catid, title FROM ".$prefix."_stories_cat");
		$cats[0] = _ARTICLES;
		while(list($cid, $title) = $db->sql_fetchrow($selcat)) { $cats[$cid] = $title; }
		echo _ALLSTORIES." <strong>$title</strong> "._WILLBEMOVED.'<br /><br />'.open_form(adminlink('&amp;mode=catdeln'), 'catdeleten').'
		<strong>'._SELECTNEWCAT.':</strong> '.select_box('newcat', 0, $cats).'
		<input type="hidden" name="catid" value="'.$catid.'" />
		<input type="submit" value="'._GO.'" />'.close_form();
	} else {
		$resultm = $db->sql_query('SELECT sid FROM '.$prefix."_stories WHERE catid='$catid'");
		while (list($sid) = $db->sql_fetchrow($resultm)) {
			$db->sql_query('UPDATE '.$prefix.'_stories SET catid='.intval($_POST['newcat']).' WHERE sid='.$sid);
		}
		$db->sql_query('DELETE FROM '.$prefix."_stories_cat WHERE catid='$catid'");
		print _MOVEDONE;
	}
}

else if ($mode == 'catsave') {
	$title = Fix_Quotes($_POST['title'], 1);
	if (empty($title) || $db->sql_count($prefix.'_stories_cat', "title='$title'")) {
		cpg_error(_CATEXISTS);
	} elseif (isset($_POST['catid'])) {
		$db->sql_query("UPDATE ".$prefix."_stories_cat SET title='$title' WHERE catid=".intval($_POST['catid']));
		$what = _TASK_COMPLETED;
	} else {
		$db->sql_query("INSERT INTO ".$prefix."_stories_cat (catid, title, counter) VALUES (DEFAULT, '$title', '0')");
		$what = _CATADDED;
	}
	show_head(_CATEGORIESADMIN);
	echo '<center><font class="content"><strong>'.$what.'</strong></font><br /><br />'._GOTOADMIN.'</center>';
}

CloseTable();
