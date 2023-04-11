<?php

/* Applied rules:
 * TernaryToNullCoalescingRector
 */
 
if (!defined('ADMIN_PAGES')) { exit; }

// check for confirmed remove
if (isset($_POST['confirm_remove'])) {
	if ($icon_id = $_GET->uint('id')) {
		// remove this icon from all topics that use it
		$db->query("UPDATE ".TOPICS_TABLE." SET icon_id = NULL WHERE icon_id = {$icon_id}");
		// remove this icon
		$db->query("DELETE FROM ".TOPIC_ICONS_TABLE." WHERE icon_id = {$icon_id}");
	}
}

// check for remove request
if (isset($_GET['remove'])) {
	$icon_id = $_GET->uint('id');
	if ($icon_id) {
		$template->set_handle('body', 'Forums/admin/topic_icons_remove');

		// grab the forum_id and the url of the icon so we can confirm it
		$icon = $db->uFetchAssoc("SELECT
			icon_url url,
			forum_id
		FROM ".TOPIC_ICONS_TABLE."
		WHERE icon_id = {$icon_id}");

		if (1 > $icon['forum_id']) {
			// global
			$icon['from_forum'] = 'from the global icons?';
		} else {
			// forum specific
			// grab the forum name so we can confirm it
			$forum_row = $db->uFetchRow("SELECT forum_name FROM " . FORUMS_TABLE . " WHERE forum_id = {$icon['forum_id']}");
			$icon['from_forum'] = 'from the forum titled: '.$forum_row[0];
		}
		$template->icon = $icon;
		return;
	}
}

// check for add request
if (isset($_POST['addicon'])) {
	if (!empty($_POST['icon_name']) && !empty($_POST['icon_path'])) {
		if ($_POST->bool('addglobal')) {
			// add global
			$forum_ids = array(-1);
		} else {
			// add forum specific
			$forum_ids = $_POST['forum_id_list'];
		}
		if (!empty($forum_ids)) {
			$icon_name = $db->quote($_POST['icon_name']);
			$icon_path = $db->quote($_POST['icon_path']);
			// create the icon for each forum
			foreach ($forum_ids as $forum_id) {
				$forum_id = (int)$forum_id;
				$db->query("INSERT INTO ".TOPIC_ICONS_TABLE." (forum_id, icon_url, icon_name) VALUES ({$forum_id}, {$icon_path}, {$icon_name})");
			}
		}
	}
}

$q_icons = $db->query("SELECT
	forum_id,
	icon_id   id,
	icon_url  url,
	icon_name name,
	'".URL::admin('&do=topic_icons&remove=true&id=')."' || icon_id as u_delete
FROM ".TOPIC_ICONS_TABLE);
$forum_icons = array(-1 => array());
while ($icon = $q_icons->fetch_assoc()) {
	$fid = $icon['forum_id'];
	unset($icon['forum_id']);
	if (!isset($forum_icons[$fid])) { $forum_icons[$fid] = array(); }
	$forum_icons[$fid][] = $icon;
}

// Draws up the forums and categories.
$q_categories = $db->query("SELECT
	cat_id    id,
	cat_title title
FROM " . CATEGORIES_TABLE . " ORDER BY cat_order");
$template->categories = array();
while ($category = $q_categories->fetch_assoc()) {
	$category['forums'] = array();

	$q_forums = $db->query("SELECT
		forum_id   id,
		forum_name name,
		'".URL::index('&file=viewforum&f=')."' || forum_id as u_view
	FROM " . FORUMS_TABLE . "
	WHERE cat_id = {$category['id']}
	ORDER BY forum_order");
	while ($forum = $q_forums->fetch_assoc()) {
		$forum['icons'] = $forum_icons[$forum['id']] ?? false;
		$category['forums'][] = $forum;
	}

	$template->categories[] = $category;
}

// get global custom icons
$template->globalicons = $forum_icons[-1];

$template->set_handle('body', 'Forums/admin/topic_icons_select');
