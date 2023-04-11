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

require(__DIR__ . '/include/load.inc');

function display_user_galleries()
{
	global $CONFIG;
	$K = \Dragonfly::getKernel();
	$db = $K->SQL;
	$OUT = $K->OUT;
	$def_avatar = $K->CFG->avatar->gallery_path.'/'.$K->CFG->avatar->default;

	if (!USER_IS_ADMIN && !$CONFIG['show_private']) {
		$vis = ' AND ' . VIS_GROUPS;
	} else {
		$vis = '';
	}

	$PAGE = $_GET->uint('page') ?: 1;
	if (1 > $PAGE) {
		cpg_error('', 404);
	}
	$columns = $CONFIG['thumbcols'];
	$user_per_page = $columns * $CONFIG['thumbrows'];
	$offset = ($PAGE-1) * $user_per_page;

	list($user_count) = $db->uFetchRow("SELECT COUNT(DISTINCT u.user_id)
	FROM {$CONFIG['TABLE_ALBUMS']} AS a
	INNER JOIN {$db->TBL->users} AS u ON (u.user_id = a.user_id)
	INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.aid = a.aid AND p.approved = 1)
	WHERE (category = ".\Coppermine::USER_GAL_CAT." OR category > " . \Coppermine::FIRST_USER_CAT . ") {$vis}");
	if (!$user_count) {
		msg_box(USER_LIST, NO_USER_GAL, '', '', '100%');
		return;
	}

	$totalPages = ceil($user_count / $user_per_page);
	if (1 > $PAGE || $PAGE > $totalPages) {
		cpg_error('', 404);
	}

	$result = $db->query("SELECT
		u.user_id,
		u.username,
		u.user_avatar,
		u.user_avatar_type,
		u.user_allowavatar,
		p.filepath,
		p.filename,
		p.pwidth,
		p.pheight,
		c.pic_count as pic_count,
		c.alb_count as alb_count
	FROM {$db->TBL->users} AS u
	INNER JOIN (SELECT
			a.user_id,
			COUNT(DISTINCT a.aid) as alb_count,
			COUNT(DISTINCT p.pid) as pic_count,
			MAX(p.pid) as pid
		FROM {$CONFIG['TABLE_ALBUMS']} AS a
		INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.aid = a.aid AND p.approved = 1)
		WHERE (category = ".\Coppermine::USER_GAL_CAT." OR category > " . \Coppermine::FIRST_USER_CAT . ") {$vis}
		GROUP BY 1) as c ON (c.user_id = u.user_id)
	INNER JOIN {$CONFIG['TABLE_PICTURES']} p ON (p.pid = c.pid)
	ORDER BY username
	LIMIT {$user_per_page} OFFSET {$offset}");

	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->thumbnails_rows = array();
	$count = 0;
	$thumbs = array();
	while ($user = $result->fetch_assoc()) {
		$user_thumb = array(
			'src' => 'themes/default/images/coppermine/nopic.jpg',
			'title' => NO_IMG_TO_DISPLAY
		);
		// User avatar as config opt
		$avatar = \Dragonfly\Identity\Avatar::getURL($user, false);
		if ($avatar && $CONFIG['avatar_private_album'] && false === strpos($avatar, $def_avatar)) {
			$user_thumb = array(
				'src' => $avatar,
				'title' => $user['username']
			);
		} else {
			$image_size = compute_img_size($user['pwidth'], $user['pheight'], $CONFIG['thumb_width']);
			$user_thumb = array(
				'src' => get_pic_url($user, 'thumb'),
				'title' => $user['username']
			);
		}

		$thumbs[] = array(
			'cat' => \Coppermine::FIRST_USER_CAT + $user['user_id'],
			'image' => $user_thumb,
			'url' => URL::index("&file=users&id={$user['user_id']}"),
			'username' => $user['username'],
			'USER_PROFILE_LINK' => \Dragonfly\Identity::getProfileURL($user['user_id']),
			'ALBUMS'   => sprintf(N_ALBUMS, $user['alb_count']),
			'PICTURES' => sprintf(N_PICS, $user['pic_count']),
		);
		if (++$count % $columns == 0) {
			$OUT->thumbnails_rows[] = $thumbs;
			$thumbs = array();
		}
	}
	if ($thumbs) {
		while ($count++ % $columns != 0) {
			$thumbs[] = array('url' => null);
		}
		$OUT->thumbnails_rows[] = $thumbs;
		$thumbs = array();
	}

	$OUT->thumbnail_column_width = ceil(100/$columns);
	$OUT->thumbnails_stats = sprintf(USER_ON_PAGE, $user_count, $totalPages);
	$OUT->thumbnails_pagination = new \Poodle\Pagination(URL::index('&file=users&page=${page}'), $totalPages, $PAGE-1);
	$OUT->display('coppermine/user_galleries');
}

function display_user_albums($id)
{
	global $module_name, $CONFIG, $USER_DATA, $userinfo;

	if (!$id) {
		return;
	}

	if (!USER_IS_ADMIN && !$CONFIG['show_private']) {
		$vis = ' AND ' . VIS_GROUPS;
	} else {
		$vis = '';
	}

	$db = \Dragonfly::getKernel()->SQL;
	$albums_data = $db->query("SELECT
		a.aid,
		COUNT(DISTINCT p.pid) as pic_count,
		MAX(p.pid) as pid
	FROM {$CONFIG['TABLE_ALBUMS']} AS a
	INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.aid = a.aid AND p.approved = 1)
	WHERE user_id = {$id} AND (category = ".\Coppermine::USER_GAL_CAT." OR category = ".(\Coppermine::FIRST_USER_CAT + $id).") {$vis}
	GROUP BY 1
	ORDER BY pos");
	$last_pids = $pic_counts = array();
	while ($row = $albums_data->fetch_row()) {
		$last_pids[] = $row[2];
		$pic_counts[$row[0]] = $row[1];
	}
	if (!$pic_counts) {
		return;
	}
	$nbAlb = count($pic_counts);

	$totalPages = ceil($nbAlb / $CONFIG['albums_per_page']);
	$PAGE = $_GET->uint('page') ?: 1;
	if ($PAGE > $totalPages) { cpg_error('Page not found', 404); }
	$lower_limit = ($PAGE-1) * $CONFIG['albums_per_page'];
	$upper_limit = min($nbAlb, $PAGE * $CONFIG['albums_per_page']);
	$album_set = implode(',', array_slice(array_keys($pic_counts), $lower_limit, ($upper_limit - $lower_limit)));

	$last_pids = implode(',', $last_pids) ?: 0;

	$qr = $db->query("SELECT
		a.aid,
		a.title,
		a.description,
		a.visibility,
		COALESCE(p.filepath, l.filepath) filepath,
		COALESCE(p.filename, l.filename) filename,
		COALESCE(p.pwidth, l.pwidth) pwidth,
		COALESCE(p.pheight, l.pheight) pheight,
		l.pid as last_pid,
		l.ctime as last_upload
	FROM {$CONFIG['TABLE_ALBUMS']} as a
	LEFT JOIN {$CONFIG['TABLE_PICTURES']} as p ON pid = thumb
	LEFT JOIN {$CONFIG['TABLE_PICTURES']} as l ON l.aid = a.aid AND l.pid IN ({$last_pids})
	WHERE a.aid IN ({$album_set})
	ORDER BY pos");
	$albums = array();
	$a_count = $qr->num_rows;
	$count = 0;
	$columns = $CONFIG['album_list_cols'];
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->album_rows = array();
	$private_img = \Dragonfly::getKernel()->OUT->theme;
	if (!file_exists("themes/{$private_img}/images/coppermine/private.jpg")) {
		$private_img = 'default';
	}
	$private_img = "themes/{$private_img}/images/coppermine/private.jpg";
	foreach ($qr as $alb_idx => $alb_thumb) {
		// Prepare everything
		$alb_thumb['pic_count'] = $pic_counts[$alb_thumb['aid']];
		$last_upload_date = $alb_thumb['pic_count'] ? $OUT->L10N->strftime(LASTUP_DATE_FMT, $alb_thumb['last_upload']) : '';
		$album = array(
			'aid' => $alb_thumb['aid'],
			'album_title' => $alb_thumb['title'],
			'album_desc' => $alb_thumb['description'],
			'pic_count' => $alb_thumb['pic_count'],
			'last_upl' => $last_upload_date,
			'album_info' => sprintf(N_PICTURES, $alb_thumb['pic_count']) . ($alb_thumb['pic_count'] ? sprintf(LAST_ADDED, $last_upload_date) : ''),
			'album_adm_menu' => (can_admin($module_name) || (USER_ADMIN_MODE && USER_ID == $id))
				? array(
				'edit_images_uri' => URL::index("&file=editpics&album={$alb_thumb['aid']}"),
				'edit_album_uri' => URL::index("&file=albmgr&album={$alb_thumb['aid']}"),
				'del_album_uri' =>  URL::index("&file=delete&album={$alb_thumb['aid']}"),
				)
				: false,
		);
		// Inserts a thumbnail if the album contains 1 or more images
		$visibility = $alb_thumb['visibility'];
		if ($visibility == '0' || $visibility == (\Coppermine::FIRST_USER_CAT + USER_ID) || $visibility == $USER_DATA['group_id'] || USER_IS_ADMIN || user_ingroup($visibility, $userinfo['group_list_cp'])) {
			if ($alb_thumb['pic_count'] > 0) { // Inserts a thumbnail if the album contains 1 or more images
				$image_size = compute_img_size($alb_thumb['pwidth'], $alb_thumb['pheight'], $CONFIG['alb_list_thumb_size']);
				$album['image'] = array(
					'src' => get_pic_url($alb_thumb, 'thumb'),
					'title' => $alb_thumb['title']
				);
			} else { // Inserts an empty thumbnail if the album contains 0 images
				$image_size = compute_img_size(100, 75, $CONFIG['alb_list_thumb_size']);
				$album['image'] = array(
					'src' => 'themes/default/images/coppermine/nopic.jpg',
					'title' => NO_IMG_TO_DISPLAY
				);
			}
		} else if ($CONFIG['show_private']) {
			$image_size = compute_img_size(100, 75, $CONFIG['alb_list_thumb_size']);
			$album['image'] = array(
				'src' => $private_img,
				'title' => MEMBERS_ONLY
			);
		}

		$album['uri'] = URL::index("&file=thumbnails&album={$album['aid']}");
		$albums[] = $album;
		if (++$count % $columns == 0 && $count < $a_count) {
			$OUT->album_rows[] = $albums;
			$albums = array();
		}
	}
	while ($count++ % $columns != 0) {
		$albums[] = array('aid' => 0,);
	}
	if ($albums) {
		$OUT->album_rows[] = $albums;
	}

	$OUT->album_column_width = ceil(100/$columns);
	$OUT->albums_statistics = null;
	$OUT->albums_stats = sprintf(ALBUM_ON_PAGE, $nbAlb, $totalPages);
	$OUT->albums_pagination = new \Poodle\Pagination(URL::index("&file=users&id={$id}&page=\${page}"), $totalPages, $PAGE-1);
	$OUT->display('coppermine/album_list');
	unset($OUT->album_rows);
}

/**
 * Main code
 */

// limit meta blocks to the current album or category
global $thisalbum;
if ($id = $_GET->uint('id')) {
	$cat = \Coppermine::FIRST_USER_CAT + $id;
	$thisalbum = "a.user_id = {$id} AND (category = ".\Coppermine::USER_GAL_CAT." OR category = {$cat})";
} else {
	$cat = \Coppermine::USER_GAL_CAT;
	$thisalbum = "(category = {$cat} OR category > " . \Coppermine::FIRST_USER_CAT . ")";
}

$elements = explode('/', $CONFIG['main_page_layout']);

pageheader(WELCOME, in_array('breadcrumb', $elements));

foreach ($elements as $element) {
	if (preg_match('/(\w+)(?:,(\d+))?/', $element, $matches)) {
		$thumbrows = isset($matches[2]) ? max(1, $matches[2]) : 1;
		switch ($matches[1]) {
			case 'catlist':
				if (!$id) {
					display_user_galleries();
				}
				break;

			case 'alblist':
				if ($id) {
					display_user_albums($id);
				}
				break;

			case 'lastupby':
			case 'lastcomby':
			case 'random':
			case 'lastup':
			case 'lastalb':
			case 'topn':
			case 'toprated':
			case 'lastcom':
				if ($id) {
					display_thumbnails($matches[1], '', $cat, 1, $thumbrows, false, $id);
				}
				break;
		}
	}
}

pagefooter();
