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
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */
   
if (isset($_GET['mode']) && $_GET['mode'] == 'smilies') {
	exit;
}

require(__DIR__ . '/include/load.inc');

function get_subcat_data($parent, &$cat_data, &$stats, $level = 0)
{
	global $CONFIG;
	$db = \Dragonfly::getKernel()->SQL;
	$parent = (int)$parent;
	$categories_data = get_categories_data();
	$datacount = init_cpg_count();
	foreach ($categories_data as $category) {
		if ($category['parent'] != $parent) {
			continue;
		}
		++$stats['categories'];
		if (\Coppermine::USER_GAL_CAT == $category['cid']) {
			if (!USER_IS_ADMIN && !$CONFIG['show_private']) {
				$vis = ' AND ' . VIS_GROUPS;
			} else {
				$vis = '';
			}
			$counts = $db->uFetchRow("SELECT
				COUNT(DISTINCT a.aid),
				COUNT(DISTINCT pid)
			FROM {$GLOBALS['CONFIG']['TABLE_ALBUMS']} a
			INNER JOIN {$CONFIG['TABLE_PICTURES']} p ON (p.aid = a.aid AND p.approved = 1)
			WHERE (category = ".\Coppermine::USER_GAL_CAT." OR category > " . \Coppermine::FIRST_USER_CAT . ") {$vis}");
			$stats['albums'] += $counts[0];
			$stats['pictures'] += $counts[1];
			if ($counts[0]) {
				$cat_data[] = array(
					'name' => $category['catname'],
					'link' => URL::index('&file=users'),
					'description' => $category['description'],
					'albums' => '',
					'album_count' => $counts[0],
					'pic_count' => $counts[1],
					'level' => $level,
				);
			}
		} else {
			$link = URL::index("&cat={$category['cid']}");
			$pic_count = empty($datacount[$category['cid']]['pic_count']) ? 0 : $datacount[$category['cid']]['pic_count'];
			$album_count = empty($datacount[$category['cid']]['album_count']) ? 0 : $datacount[$category['cid']]['album_count'];
			$stats['albums'] += $album_count;
			$stats['pictures'] += $pic_count;
			$cat_albums = '';
			if ($pic_count || $album_count) {
				if (!$level && $CONFIG['first_level']) {
					// Check if you need to show subcat_level
					$cat_albums = list_cat_albums($category['cid']);
				}
			}
			$cat_data[] = array(
				'name' => $category['catname'],
				'link' => $link,
				'description' => $category['description'],
				'albums' => $cat_albums,
				'album_count' => $album_count,
				'pic_count' => $pic_count,
				'level' => $level,
			);
			if ($level < $CONFIG['subcat_level']) {
				get_subcat_data($category['cid'], $cat_data, $stats, $level+1);
			}
		}
	}
}

// List (category) albums
// Redone for a cleaner approach: DJMaze
function list_cat_albums($cat = 0, $buffer = true)
{
	global $module_name, $CONFIG, $USER_DATA, $userinfo;
	$db = Dragonfly::getKernel()->SQL;

	$cat = (int)$cat;
	if ($cat == 0 && $buffer) return '';

	$data = init_cpg_count();
	if (empty($data[$cat]['albums'])) {
		return '';
	}
	$albums = $data[$cat]['albums'];
	$nbAlb = is_countable($albums) ? count($albums) : 0;

	$totalPages = ceil($nbAlb / $CONFIG['albums_per_page']);
	$PAGE = $_GET->uint('page') ?: 1;
	if ($PAGE > $totalPages) { cpg_error('Page not found', 404); }

	$lower_limit = ($PAGE-1) * $CONFIG['albums_per_page'];
	$upper_limit = min($nbAlb, $PAGE * $CONFIG['albums_per_page']);
	$album_set = array_slice($albums, $lower_limit, ($upper_limit - $lower_limit), true);
	$last_pids = $pic_counts = array();
	foreach ($album_set as $aid => $album) {
		$last_pids[] = $album['pid'];
		$pic_counts[$aid] = $album['pic_count'];
	}
	$last_pids = implode(',', $last_pids) ?: 0;
	$album_set = implode(',', array_keys($album_set)) ?: 0;

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
			'album_adm_menu' => can_admin($module_name)
				? array(
				'edit_images_uri' => URL::index("&file=editpics&album={$alb_thumb['aid']}"),
				'edit_album_uri' => URL::index("&file=albmgr&album={$alb_thumb['aid']}"),
				'del_album_uri' =>  URL::index("&file=delete&album={$alb_thumb['aid']}"),
				)
				: false,
		);
		// Inserts a thumbnail if the album contains 1 or more images
		$visibility = $alb_thumb['visibility'];
		if ($visibility == '0' || $visibility == $USER_DATA['group_id'] || USER_IS_ADMIN || user_ingroup($visibility,$userinfo['group_list_cp'])) {
			if ($alb_thumb['pic_count'] > 0) { // Inserts a thumbnail if the album contains 1 or more images
				$image_size = compute_img_size($alb_thumb['pwidth'], $alb_thumb['pheight'], $CONFIG['alb_list_thumb_size']);
				$album['image'] = array(
					'src' => get_pic_url($alb_thumb, 'thumb'),
					'title' => $alb_thumb['title']
				);
			} else { // Inserts an empty thumbnail if the album contains 0 images
				$image_size = compute_img_size(100, 75, $CONFIG['alb_list_thumb_size']);
				$album['image'] = array(
					'src' => "themes/default/images/coppermine/nopic.jpg",
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

	global $STATS_IN_ALB_LIST, $statistics;

	$OUT->album_column_width = ceil(100/$columns);
	$OUT->albums_statistics = $STATS_IN_ALB_LIST ? $statistics : null;
	$OUT->albums_stats = sprintf(ALBUM_ON_PAGE, $nbAlb, $totalPages);
	$OUT->albums_pagination = new \Poodle\Pagination(URL::index("&cat={$cat}&page=\${page}"), $totalPages, $PAGE-1);

	if ($buffer) {
		$result = $OUT->toString('coppermine/album_list');
		unset($OUT->album_rows);
		return $result;
	}
	$OUT->display('coppermine/album_list');
	unset($OUT->album_rows);
}

function init_cpg_count()
{
	global $db, $CONFIG;
	static $data;
	if (!is_array($data)) {
		$result = $db->uFetchAll("SELECT
			COUNT(*) as pic_count,
			MAX(pid) as pid,
			a.aid,
			a.category,
			c.parent
		FROM {$CONFIG['TABLE_PICTURES']} p
		LEFT JOIN {$CONFIG['TABLE_ALBUMS']} a ON (p.aid=a.aid)
		LEFT JOIN {$CONFIG['TABLE_CATEGORIES']} c ON (c.cid=a.category)
		WHERE a.category != " . \Coppermine::USER_GAL_CAT . " AND a.category < ".\Coppermine::FIRST_USER_CAT."
		GROUP by a.category, a.aid, c.parent
		ORDER BY a.pos");
		$data = array();
		if (is_countable($result) ? count($result) : 0) {
			foreach ($result as $row) {
				$cat = (int)$row['category'];
				if (!isset($data[$cat])) {
					$data[$cat] = array(
						'parent'      => empty($row['parent']) ? 0 : $row['parent'],
						'category'    => $cat,
						'albums'      => array($row['aid']=>array('pid'=>$row['pid'], 'pic_count' => $row['pic_count'])),
						'album_count' => 1,
						'pic_count'   => $row['pic_count'],
					);
				} else {
					$data[$cat]['albums'][$row['aid']] = array('pid'=>$row['pid'], 'pic_count' => $row['pic_count']);
					++$data[$cat]['album_count'];
					$data[$cat]['pic_count'] += $row['pic_count'];
				}
			}
		}
	}
	return $data;
}

/**
 * Main code
 */

$cat = $_GET->uint('cat');

if (\Coppermine::USER_GAL_CAT == $cat) {
	URL::redirect(URL::index('&file=users'));
}
if ($cat > \Coppermine::FIRST_USER_CAT) {
	URL::redirect(URL::index('&file=users&id='.($cat - \Coppermine::FIRST_USER_CAT)));
}

// Gather data for categories
$cat_data = array();
$stats = array('categories'=>0, 'albums'=>0, 'pictures'=>0);
get_subcat_data($cat, $cat_data, $stats);
// Add the albums in the current category to the album set
if (!$cat) {
	// Gather gallery statistics
	$statistics = strtr($cat_data ? STAT1 : STAT3, array(
		'[pictures]' => $stats['pictures'],
		'[albums]' => $stats['albums'],
		'[cat]' => $stats['categories'],
		'[comments]' => $CONFIG['TABLE_COMMENTS']->count(),
		'[views]' => cpg_tablecount($CONFIG['TABLE_PICTURES'], 'sum(hits)')
	));
	$STATS_IN_ALB_LIST = !$cat_data;
} else {
	$statistics = '';
	$STATS_IN_ALB_LIST = false;
}

// limit meta blocks to the current album or category
global $thisalbum;
$thisalbum = $cat ? "category = {$cat}" : "category >= 0";

$elements = explode('/', $CONFIG['main_page_layout']);

pageheader(WELCOME, ($cat_data || in_array('breadcrumb', $elements)));

foreach ($elements as $element) {
	if (preg_match('/(\w+)(?:,(\d+))?/', $element, $matches)) {
		$thumbrows = isset($matches[2]) ? max(1, $matches[2]) : 1;
		switch ($matches[1]) {
			case 'catlist':
				if ($cat_data) {
					$OUT = \Dragonfly::getKernel()->OUT;
					$OUT->gallery_categories = $cat_data;
					$OUT->gallery_statistics = $statistics;
					$OUT->display('coppermine/cat_list');
				}
				break;

			case 'alblist':
				list_cat_albums($cat, false);
				break;

			case 'lastupby':
			case 'lastcomby':
				// Skip if not logged in
				if (USER_ID < 2) { break; }
			case 'random':
			case 'lastup':
			case 'lastalb':
			case 'topn':
			case 'toprated':
			case 'lastcom':
				display_thumbnails($matches[1], '', $cat, 1, $thumbrows, false);
				break;

			case 'favpics':
				require_once('includes/coppermine/favorites.inc');
				if (coppermine_get_favorites()) {
					display_thumbnails('favpics', '', '', 1, $thumbrows, false);
				}
				break;
		}
	}
}

pagefooter();
