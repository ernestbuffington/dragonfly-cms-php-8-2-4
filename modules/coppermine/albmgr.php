<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

/* Applied rules:
 * ParenthesizeNestedTernaryRector (https://www.php.net/manual/en/migration74.deprecated.php)
 */

require(__DIR__ . '/include/load.inc');

if (!can_admin($module_name) && !USER_ADMIN_MODE) {
	cpg_error(ACCESS_DENIED, 403);
}

function get_subcat_data(&$CAT_LIST, $parent, $ident = '')
{
	global $CONFIG, $cat;
	$parent = (int)$parent;
	$result = \Dragonfly::getKernel()->SQL->query("SELECT cid id, catname name FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent = {$parent} AND cid > 1 ORDER BY pos");
	foreach ($result as $subcat) {
		$CAT_LIST[] = array(
			'id' => $subcat['id'],
			'name' => $ident . $subcat['name'],
			'current' => $subcat['id'] == $cat,
		);
		get_subcat_data($CAT_LIST, $subcat['id'], $ident . '- ');
	}
}

$USER_ID = USER_ID;
$USER_CAT = \Coppermine::FIRST_USER_CAT + USER_ID;
$album = $_POST->uint('album') ?: $_GET->uint('album');

if ($album) {
	$ALBUM_DATA = $db->uFetchAssoc("SELECT * FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid={$album}");
	if (!$ALBUM_DATA) {
		cpg_error(NON_EXIST_AP, 404);
	}
	$cat = (int)$ALBUM_DATA['category'];
	$USER_ID = (int)$ALBUM_DATA['user_id'];
	$USER_CAT = \Coppermine::FIRST_USER_CAT + $USER_ID;
	if ($cat == $USER_CAT) {
		$cat = \Coppermine::USER_GAL_CAT;
	}
	if (!can_admin($module_name) && (USER_ID != $USER_ID || \Coppermine::USER_GAL_CAT != $cat)) {
		cpg_error(PERM_DENIED, 403);
	}
} else if (can_admin($module_name)) {
	$cat = ($_POST->uint('cat') ?: $_GET->uint('cat')) ?: 0;
} else {
	$cat = \Coppermine::USER_GAL_CAT;
}

if (\Coppermine::USER_GAL_CAT == $cat) {
	$where = "user_id = {$USER_ID} AND (category = {$cat} OR category = {$USER_CAT})";
} else {
	$where = "category = {$cat}";
}

// Edit album?
if ($album) {
	if (isset($_POST['move'])) {
		if ('up' == $_POST['move'] || 'top' == $_POST['move']) {
			if ($ALBUM_DATA['pos'] > 0) {
				$newpos = ('up' == $_POST['move']) ? ($ALBUM_DATA['pos']-1) : 0;
				$db->exec("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos = pos+1 WHERE {$where} AND pos < {$ALBUM_DATA['pos']} AND pos > {$newpos}-1");
				$db->exec("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos = {$newpos} WHERE aid = {$album}");
			}
		} else if ('down' == $_POST['move'] || 'bottom' == $_POST['move']) {
			list($last) = $db->uFetchRow("SELECT MAX(pos) FROM {$CONFIG['TABLE_ALBUMS']} WHERE {$where}");
			if ($ALBUM_DATA['pos'] < $last) {
				$newpos = ('down' == $_POST['move']) ? ($ALBUM_DATA['pos']+1) : $last;
				$db->exec("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos = pos-1 WHERE {$where} AND pos > {$ALBUM_DATA['pos']} AND pos < {$newpos}+1");
				$db->exec("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos = {$newpos} WHERE aid = {$album}");
			}
		}
		\Dragonfly::closeRequest(ALB_UPDATED, 200, URL::index("&file=albmgr&cat={$ALBUM_DATA['category']}"));
	}
	if (isset($_POST['update'])) {
		$ALBUM_DATA = array(
			'title' => $_POST->txt('title'),
			'description' => html2bb($_POST->txt('description')),
			'thumb' => $_POST->uint('thumb'),
			'comments' => $_POST->bool('comments'),
			'votes' => $_POST->bool('votes'),
			'visibility' => $_POST->uint('visibility'),
		);
		check_words($ALBUM_DATA['title']);
		check_words($ALBUM_DATA['description']);
		if (can_admin($module_name)) {
			$ALBUM_DATA['category'] = $_POST->uint('category');
			$ALBUM_DATA['uploads']  = $_POST->bool('uploads');
		} else {
			if ($ALBUM_DATA['visibility'] != $USER_CAT && $ALBUM_DATA['visibility'] != $USER_DATA['group_id']) {
				$ALBUM_DATA['visibility'] = 0; // not in 1.2.0
			}
		}
		$CONFIG['TABLE_ALBUMS']->update($ALBUM_DATA, "aid={$album}");
		\Dragonfly::closeRequest(ALB_UPDATED, 200, URL::index("&file=albmgr&album={$album}"));
	}

	pageheader(sprintf(UPD_ALB_N, $ALBUM_DATA['title']));

	$OUT = Dragonfly::getKernel()->OUT;
	$OUT->album = $ALBUM_DATA;

	$result = $db->query("SELECT pid, filepath, filename FROM {$CONFIG['TABLE_PICTURES']} WHERE aid={$ALBUM_DATA['aid']} AND approved=1 ORDER BY filename");
	$current_thumb = \Dragonfly::getKernel()->OUT->theme;
	if (!file_exists("themes/{$current_thumb}/images/coppermine/nopic.jpg")) {
		$current_thumb = 'default';
	}
	$current_thumb = DF_STATIC_DOMAIN . "themes/{$current_thumb}/images/coppermine/nopic.jpg";
	$options = array(array(
		'value' => 0,
		'label' => LAST_UPLOADED,
		'image' => $current_thumb,
		'selected' => false
	));
	while ($picture = $result->fetch_assoc()) {
		$thumb_url = get_pic_url($picture, 'thumb');
		if ($picture['pid'] == $ALBUM_DATA['thumb']) { $current_thumb = $thumb_url; }
		$options[] = array(
			'value' => $picture['pid'],
			'label' => $picture['filename'],
			'image' => $thumb_url,
			'selected' => $picture['pid'] == $ALBUM_DATA['thumb'],
		);
	}
	$OUT->album_thumb  = $current_thumb;
	$OUT->album_thumbs = $options;

	if (!can_admin($module_name) || \Coppermine::USER_GAL_CAT == $cat) {
		$CAT_LIST = array(array('id'=>$ALBUM_DATA['category'], 'name'=>USER_GAL, 'current'=>true));
	} else {
		$CAT_LIST = array(array('id'=>0, 'name'=>NO_CAT, 'current'=>false));
		get_subcat_data($CAT_LIST, 0, '');
	}
	$OUT->album_categories = $CAT_LIST;

	$options = array(array(
		'value' => 0,
		'label' => PUBLIC_ALB,
		'selected' => false
	));
	if (can_admin($module_name)) {
		$options[] = array(
			'value' => $USER_CAT,
			'label' => ME_ONLY,
			'selected' => ($USER_CAT == $ALBUM_DATA['visibility']),
		);
		if (\Coppermine::USER_GAL_CAT == $cat) {
			$user = $db->uFetchRow("SELECT username FROM {$db->TBL->users} WHERE user_id={$ALBUM_DATA['user_id']}");
			if ($user) {
				$options[] = array(
					'value' => $ALBUM_DATA['category'],
					'label' => sprintf(OWNER_ONLY, $user[0]),
					'selected' => ($ALBUM_DATA['category'] == $ALBUM_DATA['visibility']),
				);
			}
		}
		$result = $db->query("SELECT group_id, group_name FROM {$db->TBL->cpg_usergroups}");
		while ($group = $result->fetch_row()) {
			$options[] = array(
				'value' => $group[0],
				'label' => sprintf(GROUPP_ONLY, $group[1]),
				'selected' => ($group[0] == $ALBUM_DATA['visibility']),
			);
		}
	} else if ($CONFIG['allow_private_albums']) {
		$options[] = array(
			'value' => $USER_CAT,
			'label' => ME_ONLY,
			'selected' => ($USER_CAT == $ALBUM_DATA['visibility']),
		);
		$options[] = array(
			'value' => $USER_DATA['group_id'],
			'label' => $USER_DATA['group_name'],
			'selected' => ($USER_DATA['group_id'] == $ALBUM_DATA['visibility']),
		);
	}
	$OUT->visibility_options = $options;

	$OUT->display('coppermine/modifyalb');

	pagefooter();
}

// Add album?
else if (isset($_POST['cat']) && isset($_POST['addalb']))
{
	$title = $_POST->text('title');
	if (!$title) { cpg_error('Album title can\'t be empty'); }
	list($pos) = $db->uFetchRow("SELECT MAX(pos) FROM {$CONFIG['TABLE_ALBUMS']} WHERE {$where}");
	$CONFIG['TABLE_ALBUMS']->insert(array(
		'title' => $title,
		'pos' => $pos ? $pos+1 : 0,
		'category' => (\Coppermine::USER_GAL_CAT == $cat) ? $USER_CAT : $cat,
		'description' => '',
		'user_id' => USER_ID
	));
	URL::redirect(URL::index("&file=albmgr&cat={$cat}"));
}

// Show category albums
else
{
	pageheader(ALB_MRG);

	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->cpg_cat = $cat;
	$OUT->cpg_albums = array();
	$OUT->cpg_categories = array();

	if (can_admin($module_name)) {
		$CAT_LIST = array();
		if (USER_ID) {
			$CAT_LIST[] = array(
				'id' => 1,
				'name' => MY_GALLERY,
				'current' => 1 == $cat,
			);
		}
		$CAT_LIST[] = array(
			'id' => 0,
			'name' => NO_CATEGORY,
			'current' => !$cat,
		);
		get_subcat_data($CAT_LIST, 0, '');
		$OUT->cpg_categories = array(
			'action' => URL::index('&file=albmgr'),
			'options' => $CAT_LIST,
		);
	}

	$thumb_image = \Dragonfly::getKernel()->OUT->theme;
	if (!file_exists("themes/{$thumb_image}/images/coppermine/nopic.jpg")) {
		$thumb_image = 'default';
	}
	$thumb_image = DF_STATIC_DOMAIN . "themes/{$thumb_image}/images/coppermine/nopic.jpg";
	$result = $db->query("SELECT aid, title, pos, description, thumb FROM {$CONFIG['TABLE_ALBUMS']} WHERE {$where} ORDER BY pos ASC");
	foreach ($result as $ALBUM_DATA) {
		$ALBUM_DATA['thumb_image'] = $thumb_image;
		if ($ALBUM_DATA['thumb']) {
			$picture = $db->uFetchAssoc("SELECT filepath, filename FROM {$CONFIG['TABLE_PICTURES']} WHERE pid = {$ALBUM_DATA['thumb']}");
			if ($picture) {
				$ALBUM_DATA['thumb_image'] = get_pic_url($picture, 'thumb');
			}
		}
		$ALBUM_DATA['uri_edit_pics'] = URL::index("&file=editpics&album={$ALBUM_DATA['aid']}");
		$ALBUM_DATA['uri_edit'] = URL::index("&file=albmgr&album={$ALBUM_DATA['aid']}");
		$ALBUM_DATA['uri_delete'] = URL::index("&file=delete");
		$OUT->cpg_albums[] = $ALBUM_DATA;
	}

	$OUT->display('coppermine/albmgr');

	pagefooter();
}
