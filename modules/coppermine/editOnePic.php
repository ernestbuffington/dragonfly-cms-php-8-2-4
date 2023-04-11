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

require(__DIR__ . '/include/load.inc');

if (!can_admin($module_name) && !USER_ADMIN_MODE) {
	cpg_error(ACCESS_DENIED, 403);
}

$pid = $_POST->uint('id') ?: cpg_error(NON_EXIST_AP, 404);

$CPG = \Coppermine::getInstance();

// Edit description of the picture
// Upload new thumbnail
// Rotate Image
// Just imagine

if (isset($_POST['submitDescription'])){
	$aid = $_POST->uint('aid') ?: cpg_error(NON_EXIST_AP, 404);
	$pic = $db->uFetchAssoc("SELECT owner_id FROM {$CONFIG['TABLE_PICTURES']} WHERE pid = {$pid}");
	if (!$pic) {
		cpg_error(NON_EXIST_AP, 404);
	}

	if (!can_admin($module_name) && USER_ID != $pic['owner_id']) {
		cpg_error(PERM_DENIED, 403);
	}

	$found = false;
	foreach ($CPG->getUploadableAlbums($pic['owner_id']) as $group) {
		foreach ($group['albums'] as $group_album) {
			$found |= ($group_album['aid'] == $aid);
		}
	}
	if (!$found) {
		cpg_error(PERM_DENIED, 403);
	}

	if (isset($_POST['del_comments'])) {
		$db->query("DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid = {$pid}");
	} else {
		$title = isset($_POST['title']) ? Fix_Quotes($_POST['title'],1) : NULL;
		check_words($title);
		$caption = isset($_POST['caption']) ? Fix_Quotes(html2bb($_POST['caption'])) : NULL;
		check_words($caption);
		$keywords = isset($_POST['keywords']) ? Fix_Quotes($_POST['keywords'],1) : NULL;
		check_words($keywords);
		$user1 = isset($_POST['user1']) ? Fix_Quotes($_POST['user1'],1) : NULL;
		check_words($user1);
		$user2 = isset($_POST['user2']) ? Fix_Quotes($_POST['user2'],1) : NULL;
		check_words($user2);
		$user3 = isset($_POST['user3']) ? Fix_Quotes($_POST['user3'],1) : NULL;
		check_words($user3);
		$user4 = isset($_POST['user4']) ? Fix_Quotes($_POST['user4'],1) : NULL;
		check_words($user4);
		$update = "aid = {$aid}";
		$update .= ", title = '{$title}'";
		$update .= ", caption = '{$caption}'";
		$update .= ", keywords = '{$keywords}'";
		$update .= ", user1 = '{$user1}'";
		$update .= ", user2 = '{$user2}'";
		$update .= ", user3 = '{$user3}'";
		$update .= ", user4 = '{$user4}'";
		if (isset($_POST['reset_vcount'])) $update .= ", hits = '0'";
		if (isset($_POST['reset_votes'])) $update .= ", pic_rating = '0', votes = '0'";
		$db->query("UPDATE {$CONFIG['TABLE_PICTURES']} SET {$update} WHERE pid = {$pid}");
	}

	URL::redirect(URL::index("&file=displayimage&pid={$pid}"));
}

pageheader(EDIT_PICS);

$CURRENT_PIC = $db->uFetchAssoc("SELECT
	pid,
	aid,
	filepath,
	filename,
	filesize,
	pwidth,
	pheight,
	hits,
	owner_id,
	CASE WHEN owner_id > 1 THEN username ELSE owner_name END AS owner_name,
	votes,
	title,
	caption,
	keywords,
	user1,
	user2,
	user3,
	user4
FROM {$CONFIG['TABLE_PICTURES']}
LEFT JOIN {$db->TBL->users} ON user_id = owner_id
WHERE pid = {$pid}");
if (!$CURRENT_PIC) {
	cpg_error(NON_EXIST_AP, 404);
}

$OUT = \Dragonfly::getKernel()->OUT;
$OUT->coppermine_cfg = $CONFIG;
$OUT->CURRENT_PIC    = $CURRENT_PIC;
$OUT->pic_info       = sprintf(PIC_INFO_STR, $CURRENT_PIC['pwidth'], $CURRENT_PIC['pheight'], ($CURRENT_PIC['filesize'] >> 10), $CURRENT_PIC['hits'], $CURRENT_PIC['votes']);
$OUT->upload_albums  = $CPG->getUploadableAlbums($CURRENT_PIC['owner_id']);
$OUT->thumb_url      = get_pic_url($CURRENT_PIC, 'thumb');
$OUT->thumb_link     = URL::index("&file=displayimage&pid={$pid}");
$OUT->user_fields    = array();
for ($i = 1; $i < 5; ++$i) {
	if ($CONFIG["user_field{$i}_name"]) {
		$OUT->user_fields[] = array(
			'label' => $CONFIG["user_field{$i}_name"],
			'name'  => "user{$i}",
			'value' => $CURRENT_PIC["user{$i}"],
		);
	}
}
$OUT->display('coppermine/editonepic');

pagefooter();
