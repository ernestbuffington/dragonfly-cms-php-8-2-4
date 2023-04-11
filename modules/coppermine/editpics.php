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

function process_post_data()
{
	if (!$_POST->map('pics')) {
		cpg_error(PARAM_MISSING);
	}

	global $db, $CONFIG;
	$CPG = \Coppermine::getInstance();

	foreach (array_keys($_POST['pics']) as $pid)
	{
		$pid = (int) $pid;
		$aid = $_POST->uint('pics',$pid,'aid');

		$pic = $db->uFetchAssoc("SELECT upload_id, owner_id, category, filepath, filename
			FROM {$CONFIG['TABLE_PICTURES']} p, {$CONFIG['TABLE_ALBUMS']} a
			WHERE pid={$pid}
			  AND a.aid = p.aid");
		if (!$pic) { continue; }

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

		if (in_array($pid, $_POST['delete']) || 'DELETE' === $_POST->txt('pics',$pid,'approved')) {
			$dir = $CONFIG['fullpath'];
			$file = $pic['filename'];
			if (!is_writable($dir)) {
				cpg_error(sprintf(DIRECTORY_RO, $dir));
			}
			$files = array(
				$dir . $file,
				$dir . $CONFIG['normal_pfx'] . $file,
				$dir . $CONFIG['thumb_pfx'] . $file,
				preg_replace('/\\.[^.]+$/','.exif', $dir . $file)
			);
			foreach ($files as $currFile) {
				if (is_file($currFile)) {
					unlink($currFile);
				}
			}
			$CONFIG['TABLE_COMMENTS']->delete("pid = {$pid}");
			$CONFIG['TABLE_PICTURES']->delete("pid = {$pid}");
			$db->TBL->users_uploads->delete("upload_id = {$pic['upload_id']}");
			continue;
		}

		if (in_array($pid, $_POST['del_comments'])) {
			$CONFIG['TABLE_COMMENTS']->delete("pid = {$pid}");
		}

		$update = "aid={$aid}"
			.", title=".$db->quote($_POST->txt('pics',$pid,'title'))
			.", caption=".$db->quote(html2bb($_POST->raw('pics',$pid,'caption')))
			.", keywords=".$db->quote($_POST->txt('pics',$pid,'keywords'))
			.", user1=".$db->quote($_POST->txt('pics',$pid,'user1'))
			.", user2=".$db->quote($_POST->txt('pics',$pid,'user2'))
			.", user3=".$db->quote($_POST->txt('pics',$pid,'user3'))
			.", user4=".$db->quote($_POST->txt('pics',$pid,'user4'));
		if (UPLOAD_APPROVAL_MODE) {
			if ($_POST->bool('pics',$pid,'approved')) { $update .= ', approved=1'; }
		} else {
			if (in_array($pid, $_POST['reset_vcount'])) { $update .= ', hits=0'; }
			if (in_array($pid, $_POST['reset_votes']))  { $update .= ', pic_rating=0, votes=0'; }
		}
		$db->exec("UPDATE {$CONFIG['TABLE_PICTURES']} SET {$update} WHERE pid={$pid}");
	}
	speedup_pictures();
	URL::redirect($_SERVER['REQUEST_URI']);
}

define('UPLOAD_APPROVAL_MODE', isset($_GET['mode']));

$album = $_POST->uint('album') ?: $_GET->uint('album');
$album_id = (int)$album;

if (!UPLOAD_APPROVAL_MODE) {
	if ($album_id) {
		$ALBUM_DATA = $db->uFetchAssoc("SELECT title, category, user_id FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = {$album_id}");
		if (!$ALBUM_DATA) {
			cpg_error(NON_EXIST_AP, 404);
		}
		$cat = $ALBUM_DATA['category'];
		if (!can_admin($module_name) && USER_ID != $ALBUM_DATA['user_id']) {
			cpg_error(PERM_DENIED, 403);
		}
	}
}
else if (!can_admin($module_name)) {
	cpg_error(ACCESS_DENIED, 403);
}

if (!empty($_POST['pics'])) {
	process_post_data();
}

$start = $_GET->uint('start') ?: 0;
$count = $_GET->uint('count') ?: 25;

if (UPLOAD_APPROVAL_MODE) {
	$title = UPL_APPROVAL;
	$uri_mode = '&mode=upload_approval';
	$pic_count = $CONFIG['TABLE_PICTURES']->count('approved=0');
	$result = $db->query("SELECT
		p.*,
		u.username
	FROM {$CONFIG['TABLE_PICTURES']} p
	LEFT JOIN {$db->TBL->users} u ON (u.user_id = p.owner_id)
	WHERE approved=0 ORDER BY pid LIMIT {$count} OFFSET {$start}");
} else {
	$title = EDIT_PICS;
	$uri_mode = '&album='.$album_id;
	if ($album_id) {
		$pic_count = $CONFIG['TABLE_PICTURES']->count("aid={$album_id}");
		$result = $db->query("SELECT * FROM {$CONFIG['TABLE_PICTURES']} WHERE aid={$album_id} ORDER BY filename LIMIT {$count} OFFSET {$start}");
	} else {
		$pic_count = $CONFIG['TABLE_PICTURES']->count('owner_id=' . USER_ID);
		$result = $db->query("SELECT * FROM {$CONFIG['TABLE_PICTURES']} WHERE owner_id=".USER_ID." ORDER BY filename LIMIT {$count} OFFSET {$start}");
	}
}

if (!$result->num_rows){
	$redirect = URL::index($module_name);
	$title = \Dragonfly::getKernel()->L10N['Information'];
	pageheader($title);
	msg_box($title, NO_MORE_IMAGES, CONTINU, $redirect);
} else {
	$CPG = \Coppermine::getInstance();
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->cpg = array(
		'CONFIG' => $CONFIG,
		'title' => $title,
		'pic_count' => sprintf(N_PIC, $pic_count),
		'prev_link' => $start ? URL::index("&file=editpics{$uri_mode}&start=" . max(0, $start - $count) . '&count=' . $count) : false,
		'next_link' => ($start + $count < $pic_count) ? URL::index("&file=editpics{$uri_mode}&start=" . ($start + $count) . '&count=' . $count) : false,
		'pictures' => array(),
	);
	$user_albums = array();
	while ($row = $result->fetch_assoc()) {
		$user_id = $row['owner_id'];
		$row['dimensions'] = $row['pwidth'] . 'x' . $row['pheight'];
		if (UPLOAD_APPROVAL_MODE) {
			$row['uploader'] = array(
				'profile_url' => \Dragonfly\Identity::getProfileURL($user_id),
				'edit_url' => URL::admin('&file=users&opp=edit&user_id=' . $user_id)
			);
		} else {
			$row['uploader'] = false;
			$row['hits'] = $OUT->L10N->plural($row['hits'], '%d views');
			$row['votes'] = $OUT->L10N->plural($row['votes'], '%d votes');
		}
		if (!isset($user_albums[$user_id])) {
			$user_albums[$user_id] = $CPG->getUploadableAlbums($row['owner_id']);
		}
		$row['thumb_url'] = get_pic_url($row, 'thumb');
		$row['large_url'] = URL::index("&amp;file=displayimagepopup&amp;pid={$row['pid']}&amp;fullsize=1");
		$row['albumgroups'] = &$user_albums[$user_id];
		$OUT->cpg['pictures'][] = $row;
	}
	pageheader($title);
	$OUT->display('coppermine/editpics');
	unset($OUT->cpg, $user_albums);
}

pagefooter();
