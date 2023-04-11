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

function coppermine_block()
{
	global $module_name, $USER_DATA;
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->coppermine_member_menu = array(
		'items' => array(),
		'admin' => array(),
	);
	if (is_user()) {
		if (USER_CAN_CREATE_ALBUMS){
			$OUT->coppermine_member_menu['items'][] = array(
				'name' => ALBMGR_LNK,
				'uri' => URL::index('&file=albmgr&cat=1')
			);
		}
		$OUT->coppermine_member_menu['items'][] = array(
			'name' => MY_PROF_LNK,
			'uri' => URL::index('&file=profile')
		);
		if (USER_CAN_CREATE_ALBUMS){
			$OUT->coppermine_member_menu['items'][] = array(
				'name' => MY_GAL_LNK,
				'uri' => URL::index('&file=users&id='.USER_ID)
			);
		}
		$OUT->coppermine_member_menu['items'][] = array(
			'name' => $OUT->L10N['cpg_meta_album_names']['lastcomby'],
			'uri' => URL::index('&file=thumbnails&meta=lastcomby')
		);
		if ($USER_DATA['can_upload_pictures']) {
			$OUT->coppermine_member_menu['items'][] = array(
				'name' => UPLOAD_PIC_LNK,
				'uri' => URL::index("&file=upload")
			);
		}
	}
	if (can_admin($module_name)) {
		$cat = $_POST->uint('cat') ?: $_GET->uint('cat') ?: 0;
		$OUT->coppermine_member_menu['admin'] = array(
			array('name' => SEARCHNEW_LNK, 'uri' => URL::admin('&file=searchnew')),
			array('name' => COMMENTS_LNK, 'uri' => URL::admin('&file=reviewcom')),
			array('name' => GROUPS_LNK, 'uri' => URL::admin('&file=groups')),
			array('name' => USERS_LNK, 'uri' => URL::admin('&file=users')),
			array('name' => ALBUMS_LNK, 'uri' => URL::index('&file=albmgr&cat='.$cat)),
			array('name' => CATEGORIES_LNK, 'uri' => URL::admin('&file=categories')),
			array('name' => CONFIG_LNK, 'uri' => URL::admin()),
		);
		if ($num = $GLOBALS['CONFIG']['TABLE_PICTURES']->count('approved=0')) {
			array_unshift($OUT->coppermine_member_menu['admin'], array('name' => UPL_APP_LNK . " ({$num})", 'uri' => URL::index('&file=editpics&mode=upload_approval')));
		}
		if (isset($_GET['album'])) {
			$OUT->coppermine_member_menu['admin'][] = array('name' => EDIT_PICS, 'uri' => URL::index('&file=editpics&album='.$_GET->uint('album')));
		}
	}
	return $OUT->toString($module_name.'/memberblock');
}

// check if user is member of group
function user_ingroup($group_id, &$usergroups)
{
	if (!is_array($usergroups)) $usergroups = explode(',', $usergroups);
	foreach ($usergroups as $group) {
		if ($group == $group_id) return true;
	}
	return false;
}

/**
 * Database functions
 */

// get a table count
// cpg_tablecount("cpg_pictures", "sum(hits)")
function cpg_tablecount($table, $type='COUNT(*)')
{
	list($count) = \Dragonfly::getKernel()->SQL->uFetchRow("SELECT {$type} FROM {$table}");
	return $count;
}

/**
 * Utilities functions
 */

// Function for writing a pageheader
function pageheader($section, $breadcrumbs = false)
{
	global $CONFIG, $USER, $album, $cat, $meta;
	$aid = (int)$album;
	$cat = (int)$cat;
	$K = \Dragonfly::getKernel();
	if ($meta) {
		\Dragonfly\Page::metatag('robots', 'noindex, follow');
	}
	$K->setCookie($CONFIG['cookie_name'] . '_data', base64_encode(\Dragonfly::dataToJSON($USER)), time() + 86400 * 30);
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->gallery = array(
		'meta_lnk' => $aid ? '&album='.$aid : ($cat ? '&cat='.$cat : ''),
		'user_gallery' => URL::index('&file=users&id='.USER_ID),
		'name' => $CONFIG['gallery_name'],
		'section' => $section,
		'description' => $CONFIG['gallery_description'],
	);
	// Build the breadcrumb array
	if ($breadcrumbs || $CONFIG['nice_titles']) {
		$breadcrumb = array();
		if ($aid) {
			$row = $K->SQL->uFetchAssoc("SELECT aid AS id, title, category AS parent FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = {$aid}");
			if ($row) {
				$row['album'] = 1;
				$breadcrumb[] = $row;
			}
		}
		else if ($cat) {
			if ($cat > \Coppermine::FIRST_USER_CAT) {
				$row = $K->SQL->uFetchAssoc("SELECT username AS title FROM {$K->SQL->TBL->users} WHERE user_id = " . ($cat - \Coppermine::FIRST_USER_CAT));
				if ($row) {
					$row['id'] = $cat;
					$row['parent'] = 1;
					$breadcrumb[] = $row;
				}
			} else {
				$row = $K->SQL->uFetchAssoc("SELECT cid as id, parent, catname AS title FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid={$cat}");
				if ($row) {
					$breadcrumb[] = $row;
				}
			}
		}

		get_breadcrumb($breadcrumb);
		$crumbs = array();
		foreach ($breadcrumb as &$crumb) {
			if (isset($crumb['album'])) {
				$crumbs[] = array('title' => $crumb['title'], 'href' => URL::index("&file=thumbnails&album={$crumb['id']}"));
			} else {
				$crumbs[] = array('title' => $crumb['title'], 'href' => URL::index("&cat={$crumb['id']}"));
			}
		}
		if ($meta) {
			$crumbs[] = array('title' => \Dragonfly::getKernel()->L10N['cpg_meta_album_names'][$meta], 'href' => null);
		} else if ($crumbs && 'displayimage' != $_GET->text('file')) {
			$crumbs[count($crumbs)-1]['href'] = null;
		}

		$K->OUT->coppermine_breadcrumbs = $crumbs;
	}
	if ($CONFIG['nice_titles']) {
		foreach ($OUT->coppermine_breadcrumbs as $crumb) {
			\Dragonfly\Page::title($crumb['title']);
		}
	}
	if (!$breadcrumbs) {
		$OUT->coppermine_breadcrumbs = null;
	}

	$OUT->display('coppermine/header');
}

// Function for writing a pagefooter
function pagefooter()
{
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->gallery_debug = false;
	if (USER_IS_ADMIN && !Dragonfly::isDemo()) {
		if ($GLOBALS['CONFIG']['debug_mode']) {
			$OUT->gallery_debug = array(
				'config' => $GLOBALS['CONFIG'],
				'user_groups' => $GLOBALS['USER_DATA']['GROUPS'],
				'language_dir' => 'modules/'.Coppermine::$MAIN_DIR.'/l10n/',
				'GET' => var_export((array)$_GET,true),
				'POST' => var_export((array)$_POST,true),
			);
		}
	}
	$OUT->display('coppermine/footer');
	require('footer.php');
}

// Function to create correct URLs for image name with space or exotic characters
function path2url($path)
{
	return str_replace('%2F', '/', rawurlencode($path));
}

// Display a 'message box like' table
function msg_box($title, $msg_text, $button_text = '', $button_link = '')
{
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->msg_box = array(
		'title' => $title,
		'message' => $msg_text,
		'btn_link' => $button_link,
		'btn_text' => $button_text
	);
	$OUT->display('coppermine/msg_box');
}

// Retrieve the data for a picture or a set of picture
function get_pic_data($meta, $album, &$count, &$album_name, $limit = 0, $offset = 0, $author_id = 0)
{
	global $USER, $CONFIG, $thisalbum;
	static $all_columns;
	$K = \Dragonfly::getKernel();
	$db = $K->SQL;
	$user_id = \Dragonfly::getKernel()->IDENTITY->id;
	$limit  = max(0, (int)$limit);
	$offset = max(0, (int)$offset);
	$all = (1 === $limit);
	if (!$all_columns) {
		$all_columns = $db->list_columns($CONFIG['TABLE_PICTURES']);
		$all_columns = 'p.'.implode(', p.',array_keys($all_columns));
	}
	$select_columns = $all_columns;
	$sql_limit = '';
	if ($limit) {
		$sql_limit = 'LIMIT ' .$limit;
		if ($offset) { $sql_limit .= ' OFFSET '. $offset; }
	}

	// Regular albums
	if ($album && !$meta) {
		$result = $db->uFetchRow("SELECT title from {$GLOBALS['CONFIG']['TABLE_ALBUMS']} WHERE aid={$album}");
		if (!$result) {
			cpg_error(NON_EXIST_AP.' '.$album, 404);
		}
		$album_name = $result[0];

		list($count) = $db->uFetchRow("SELECT COUNT(*) from {$CONFIG['TABLE_PICTURES']} as p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (a.aid = {$album} AND ".VIS_GROUPS.") WHERE p.aid={$album} AND approved=1");
		if (!$all) {
			$select_columns .= ', a.description';
		}
		$sort_array = array('na' => 'filename ASC', 'nd' => 'filename DESC', 'ta' => 'title ASC', 'td' => 'title DESC', 'da' => 'pid ASC', 'dd' => 'pid DESC', 'ra' => 'pic_rating ASC', 'rd' => 'pic_rating DESC', 'sd' => 'max(pwidth,pheight) DESC', 'sa' => 'max(pwidth,pheight) ASC'); //'sa' => 'max(pwidth,pheight) DESC'
		$sort_code  = isset($_GET['sort']) ? $_GET['sort'] : $CONFIG['default_sort_order'];
		$sort_order = isset($sort_array[$sort_code]) ? $sort_array[$sort_code] : $sort_array[$CONFIG['default_sort_order']];
		return $db->query("SELECT
			{$select_columns}
		FROM {$CONFIG['TABLE_PICTURES']} as p
		INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (a.aid = {$album} AND ".VIS_GROUPS.")
		WHERE p.aid = {$album}
		  AND approved = 1
		GROUP BY {$select_columns}
		ORDER BY {$sort_order} {$sql_limit}");
	}

	// Meta albums
	$album_name = $meta ? $K->L10N['cpg_meta_album_names'][$meta] : '';
	// Limits pictures to the amount shown
	switch ($meta) {
		// Last comments
		case 'lastcom':
			list($count) = $db->uFetchRow("SELECT COUNT(*) FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum})");
			if (!$count) { return null; }
			if (!$all) {
				$select_columns .= ', c.msg_id, c.author_id, c.msg_author, c.msg_date, c.msg_body';
			}
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) ORDER BY msg_date DESC {$sql_limit}");

		// Last comments by a specific user
		case 'lastcomby':
			list($count) = $db->uFetchRow("SELECT COUNT(*) FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE author_id = " .($author_id?:$user_id));
			if (!$count) { return null; }
			if (!$all) {
				$select_columns .= ', c.pid, c.msg_id, c.author_id, c.msg_author, c.msg_date, c.msg_body';
			}
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_COMMENTS']} as c INNER JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.pid = c.pid AND approved=1) INNER JOIN {$CONFIG['TABLE_ALBUMS']} as a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE author_id = " .($author_id?:$user_id). " ORDER BY msg_date DESC {$sql_limit}");

		// Last uploads
		case 'lastup':
			$count = get_album_pictures_count($thisalbum);
			if (!$count) { return null; }
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) WHERE approved=1 ORDER BY p.pid DESC {$sql_limit}");

		// Last uploads by a specific user
		case 'lastupby':
			list($count) = $db->uFetchRow("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']} WHERE owner_id = ".($author_id?:$user_id)." AND approved=1");
			if (!$count) { return null; }
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) WHERE owner_id = ".($author_id?:$user_id)." AND approved=1 ORDER BY p.pid DESC {$sql_limit}");

		// Most viewed pictures
		case 'topn':
			$count = get_album_pictures_count($thisalbum, "AND hits > 0");
			if (!$count) { return null; }
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) WHERE approved=1 AND p.hits > 0 ORDER BY p.hits DESC, p.filename ASC {$sql_limit}");

		// Top rated pictures
		case 'toprated':
			$count = get_album_pictures_count($thisalbum, "AND p.votes >= {$CONFIG['min_votes_for_rating']}");
			if (!$count) { return null; }
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) WHERE approved=1 AND p.votes >= '{$CONFIG['min_votes_for_rating']}' ORDER BY ROUND((pic_rating+1)/2000,2) DESC, p.votes DESC, filename {$sql_limit}");

		// Last viewed pictures
		case 'lasthits':
			$count = get_album_pictures_count($thisalbum);
			if (!$count) { return null; }
			// count the number of pics to show
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) WHERE approved=1 GROUP BY {$select_columns} ORDER BY mtime DESC {$sql_limit}");

		// Random pictures
		case 'random':
			$count = get_album_pictures_count($thisalbum);
			if (!$count) { return null; }
			// count the number of pics to show
			// if we have more than 1000 pictures, we limit the number of picture returned
			// by the SELECT statement as ORDER BY RAND() is time consuming
			if ($count > 1000) {
//				$total_count = get_album_pictures_count($thisalbum, " AND {$thisalbum} GROUP BY p.pid");
				$granularity = floor($total_count / \Coppermine::RANDPOS_MAX_PIC);
				$cor_gran = ceil($total_count / $count);
				srand(time());
				for ($i = 1; $i <= $cor_gran; $i++) $random_num_set = rand(0, $granularity) . ', ';
				$random_num_set = substr($random_num_set, 0, -2);
				return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (".VIS_GROUPS." AND {$thisalbum}) WHERE  randpos IN ({$random_num_set}) AND approved = 1 GROUP BY {$select_columns} ORDER BY RAND() {$sql_limit}");
			}
			return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum}) WHERE approved=1 GROUP BY {$select_columns} ORDER BY RAND() {$sql_limit}");

		//All pictures by member
		case 'allby':
			$m_id = $_GET->uint('member_id') ?: 1;
			$query = "{$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE owner_id = '{$m_id}' AND approved=1 GROUP BY {$select_columns} ORDER BY p.pid DESC";
			list($count) = $db->uFetchRow("SELECT COUNT(*) FROM {$query}");
			if (!$count) { return null; }
			return $db->query("SELECT {$select_columns} FROM $query {$sql_limit}");

		// Search results
		case 'search':
			if (isset($_GET['search'])) {
				$USER['search'] = Fix_Quotes($_GET['search']);
				if (isset($_GET['type']) && 'full' === $_GET['type']) {
				   $USER['search'] = '###' . $USER['search'];
				}
			} else if (isset($_POST['search'])) {
				$USER['search'] = Fix_Quotes($_POST['search']);
				if (isset($_POST['type']) && 'full' === $_POST['type']) {
					$USER['search'] = '###' . $USER['search'];
				}
			}
			if (isset($USER['search'])) {
				$search_string = Fix_Quotes($USER['search']);
			} else {
				cpg_error('The searchstring is empty');
				$search_string = '';
			}

			if ('###' === substr($search_string, 0, 3)) {
				$query_all = 1;
				$search_string = substr($search_string, 3);
			} else {
				$query_all = 0;
			}
			$album_name .= ' - "' . htmlprepare($search_string) . '"';

			require_once(CORE_PATH.'coppermine/search.inc');
			return search_pics($search_string, $select_columns, $query_all, $sql_limit, $count);

		// Last albums to which uploads
		case 'lastalb':
			$pids = array();
			$qr = $db->query("SELECT MAX(p.pid)
				FROM {$CONFIG['TABLE_PICTURES']} AS p
				INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum})
				WHERE approved = 1
				GROUP BY p.aid
				ORDER BY 1 DESC");
			$count = $qr->num_rows;
			if ($count && $qr->data_seek($offset)) {
				if ($limit) {
					while ($limit-- && $pid = $qr->fetch_row()) {
						$pids[] = $pid[0];
					}
				} else {
					while ($pid = $qr->fetch_row()) {
						$pids[] = $pid[0];
					}
				}
			}
			if (!$pids) {
				return null;
			}
			return $db->query("SELECT {$select_columns}, a.title AS title, a.aid AS aid
				FROM {$CONFIG['TABLE_PICTURES']} AS p
				INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a USING (aid)
				WHERE pid IN (".implode(',', $pids).")
				ORDER BY p.ctime DESC");

		// Favourite Pictures
		case 'favpics':
			require_once(__DIR__ . '/favorites.inc');
			$favs = coppermine_get_favorites();
			$count = count($favs);
			if ($count) {
				$favs = implode(',', $favs);
				return $db->query("SELECT {$select_columns} FROM {$CONFIG['TABLE_PICTURES']} AS p WHERE approved = 1 AND pid IN ({$favs}) GROUP BY {$select_columns} {$sql_limit}");
			}
			return null;

		// Invalid meta album
		default :
			cpg_error(NON_EXIST_AP.' '.$_GET['meta'].' '.$_POST['meta'].$meta, 404);
	}
} // End of get_pic_data

function get_album_pictures_count($thisalbum, $sql_extend = '')
{
	global $CONFIG;
	list($count) = \Dragonfly::getKernel()->SQL->uFetchRow("SELECT COUNT(*)
		FROM {$CONFIG['TABLE_PICTURES']} AS p
		INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS." AND {$thisalbum})
		WHERE approved=1 {$sql_extend}");
	return $count;
}

// Add 1 everytime a picture is viewed.
function add_hit($pid)
{
	$GLOBALS['CONFIG']['TABLE_PICTURES']->updatePrepared(array('hits' => 'hits+1'), "pid={$pid}");
}

// Get the breadcrumb array
function get_breadcrumb(&$breadcrumb)
{
	if (!$breadcrumb || empty($breadcrumb[0]['parent']) || !is_numeric($breadcrumb[0]['parent'])) {
		return;
	}
	$last = count($breadcrumb);
	if ($breadcrumb[$last-1]['parent'] > \Coppermine::FIRST_USER_CAT) {
		$db = \Dragonfly::getKernel()->SQL;
		$cat = $breadcrumb[$last-1]['parent'];
		$row = $db->uFetchAssoc("SELECT username AS title FROM {$db->TBL->users} WHERE user_id = " . ($cat - \Coppermine::FIRST_USER_CAT));
		if ($row) {
			$row['id'] = $cat;
			$row['parent'] = 1;
			$breadcrumb[] = $row;
			get_breadcrumb($breadcrumb);
		}
		return;
	}
	foreach (get_categories_data() as $row) {
		if ($row['cid'] != $breadcrumb[$last-1]['parent']) continue;
		$row['id'] = $row['cid'];
		$row['title'] = $row['catname'];
		unset($row['cid'], $row['catname'], $row['description']);
		$breadcrumb[] = $row;
		if ($row['parent'] > 0 && is_numeric($row['parent'])) {
			get_breadcrumb($breadcrumb);
		} else {
			$breadcrumb = array_reverse($breadcrumb);
			return;
		}
	}
}

// Compute image geometry based on max width / height
function compute_img_size($width, $height, $max)
{
	$thumb_use = $GLOBALS['CONFIG']['thumb_use'];
	if ('ht' === $thumb_use) {
		$ratio = $height / $max;
	} else if ('wd' === $thumb_use) {
		$ratio = $width / $max;
	} else {
		$ratio = max($width, $height) / $max;
	}
	if ($ratio > 1.0) {
		$image_size['reduced'] = true;
	}
	$ratio = max($ratio, 1.0);
	$image_size['width'] = ceil($width / $ratio);
	$image_size['height'] = ceil($height / $ratio);
	$image_size['geom'] = 'width="' . $image_size['width'] . '" height="' . $image_size['height'] . '"';

	return $image_size;
}

// Prints thumbnails of pictures in an album
function display_thumbnails($meta, $album, $cat, $page, $thumbrows, $display_tabs, $author_id = 0)
{
	global $CONFIG;
	$CPG = \Coppermine::getInstance();

	$limit = ($CONFIG['thumbcols'] * $thumbrows);
	$offset = $limit * max(0, $page-1);
	$pic_data = get_pic_data($meta, $album, $thumb_count, $album_name, $limit, $offset, $author_id);
	if (!$pic_data || !$pic_data->num_rows) {
		return;
	}

	$sort = ($album && !$meta)
		? ($_GET->text('sort') ?: $CONFIG['default_sort_order'])
		: null;

	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->thumbnails_rows = array();
	$thumbs = array();
	$columns = $CONFIG['thumbcols'];
	$i = 0;
	foreach ($pic_data as $key => $row) {
		$i++;
		$thumb = array(
			'pid' => $row['pid'],
			'pos' => $key < 0 ? $key : $i - 1 + $offset,
			'title' => '',
			'caption' => '',
			'image' => array(
				'src' => get_pic_url($row, 'thumb'),
				'title' => $row['filename']
			)
		);

		//$th_search = isset($_POST['search']) ? $_POST['search'] : '';
		//$th_search = isset($_GET['search']) ? $_GET['search']: $th_search;
		//$srh = isset($_GET['meta']) && 'search' === $_GET['meta'] ? "&search={$th_search}" : '';
		$thumb['url'] = $CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'pid' => $thumb['pid'], 'pos' => $thumb['pos'], 'sort' => $sort));

		$row['title'] = trim($row['title']);

		$image_size = compute_img_size($row['pwidth'], $row['pheight'], $CONFIG['thumb_width']);
		if ($CONFIG['seo_alts']) {
			if ($row['title']) {
				$pic_title = $row['title'];
				if ($row['keywords']) {
					$pic_title .= "\n" . $row['keywords'];
				}
			} else if ($row['keywords']) {
				$pic_title = $row['keywords'];
			} else {
				$pic_title = substr($row['filename'], 0, -4);
			}
			$thumb['image']['title'] = $pic_title;
		}

		// Set picture caption
		if ($album && !$meta) {
			// Regular albums
			if ($row['title'] || $row['hits']) {
				$thumb['title'] = htmlspecialchars($row['title']) . ($row['title']?' - ':'') . \Dragonfly::getKernel()->L10N->plural($row['hits'],'%d views');
			}
			if ($CONFIG['caption_in_thumbview'] && $row['caption']) {
				$thumb['caption'] = \Dragonfly\BBCode::decode($row['caption']);
			}
			if ($CONFIG['display_comment_count'] && $comments_nr = $GLOBALS['CONFIG']['TABLE_COMMENTS']->count("pid={$row['pid']}")) {
				$thumb['caption'] .= ' (' . \Dragonfly::getKernel()->L10N->plural($comments_nr,'%d comments') . ')';
			}
		}
		else switch ($meta) {
			// Last comments
			case 'lastcom':
				if ($row['author_id']) {
					$thumb['title'] = '<a href="'.\Dragonfly\Identity::getProfileURL($row['author_id']).'">'.htmlspecialchars($row['msg_author']).'</a>';
				} else {
					$thumb['title'] = htmlspecialchars($row['msg_author']);
				}
				$thumb['title'] .= ' ' . \Dragonfly::getKernel()->L10N->strftime(LASTCOM_DATE_FMT, $row['msg_date']);
				$thumb['caption'] = htmlspecialchars(mb_strlen($row['msg_body']) > 50 ? substr($row['msg_body'], 0, 50) . '…': $row['msg_body']);
				break;

			// Last comments by a specific user
			case 'lastcomby':
				$thumb['title'] = htmlspecialchars($row['msg_author']) . ' ' . \Dragonfly::getKernel()->L10N->strftime(LASTCOM_DATE_FMT, $row['msg_date']);
				$thumb['caption'] = htmlspecialchars(mb_strlen($row['msg_body']) > 50 ? substr($row['msg_body'], 0, 50) . '…': $row['msg_body']);
				break;

			// Last uploads
			case 'lastup':
				if ($row['owner_id'] && $row['owner_name']) {
					$thumb['title'] = '<a href="'.\Dragonfly\Identity::getProfileURL($row['owner_id']).'">' . htmlspecialchars($row['msg_author']) . '</a>';
				}
				$thumb['caption'] = \Dragonfly::getKernel()->L10N->strftime(LASTUP_DATE_FMT, $row['ctime']);
				break;

			// Last uploads by a specific user
			case 'lastupby':
				if ($row['owner_id'] && $row['owner_name']) {
					$thumb['title'] = '<a href="'.\Dragonfly\Identity::getProfileURL($row['owner_id']).'">'.htmlspecialchars($row['msg_author']).'</a>';
				}
				$thumb['caption'] = \Dragonfly::getKernel()->L10N->strftime(LASTUP_DATE_FMT, $row['ctime']);
				break;

			// Most viewed pictures
			case 'topn':
				$thumb['caption'] = \Dragonfly::getKernel()->L10N->plural($row['hits'],'%d views');
				break;

			// Top rated pictures
			case 'toprated':
				$thumb['title'] = \Coppermine::getRatingStars($row['pic_rating']);
				$thumb['caption'] = round($row['pic_rating'] / 2000, 2) . '/5 ('.\Dragonfly::getKernel()->L10N->plural($row['votes'],'%d votes').')';
				break;

			// Last viewed pictures
			case 'lasthits':
				$thumb['caption'] = \Dragonfly::getKernel()->L10N->strftime(LASTHIT_DATE_FMT, $row['mtime']);
				break;

			// Random pictures
			case 'random':
				break;

			//All pictures by member
			case 'allby':
				break;

			// Search results
			case 'search':
				$thumb['title'] = htmlspecialchars($row['title']);
				if ($CONFIG['caption_in_thumbview'] && $row['caption']) {
					$thumb['caption'] = \Dragonfly\BBCode::decode($row['caption']);
				}
				break;

			// Last albums to which uploads
			case 'lastalb':
				$thumb['title'] = htmlspecialchars($row['title']);
				$thumb['caption'] = \Dragonfly::getKernel()->L10N->strftime(LASTUP_DATE_FMT, $row['ctime']);
				break;

			// Favourite Pictures
			case 'favpics':
				$thumb['title'] = htmlspecialchars($row['title']);
				break;
		}

		$thumbs[] = $thumb;
		if (!($i % $columns)) {
			$OUT->thumbnails_rows[] = $thumbs;
			$thumbs = array();
		}
	}

	if ($thumbs) {
		while ($i++ % $columns) {
			$thumbs[] = array('url' => null, 'image' => null, 'title' => null, 'caption' => null);
		}
		$OUT->thumbnails_rows[] = $thumbs;
		$thumbs = array();
	}

	// The sort order options are not available for meta albums
	if ($sort) {
		$OUT->thumbnails_sort = array(
			'name' => $GLOBALS['module_name'],
			'album' => $_GET->uint('album'),
			'page' => $_GET->uint('page') ?: 1,
			'options' => array(
				array('value' => 'ta', 'selected' => ('ta' === $sort), 'label' => SORT_TA),
				array('value' => 'td', 'selected' => ('td' === $sort), 'label' => SORT_TD),
				array('value' => 'na', 'selected' => ('na' === $sort), 'label' => SORT_NA),
				array('value' => 'nd', 'selected' => ('nd' === $sort), 'label' => SORT_ND),
				array('value' => 'da', 'selected' => ('da' === $sort), 'label' => SORT_DA),
				array('value' => 'dd', 'selected' => ('dd' === $sort), 'label' => SORT_DD),
				array('value' => 'ra', 'selected' => ('ra' === $sort), 'label' => SORT_RA),
				array('value' => 'rd', 'selected' => ('rd' === $sort), 'label' => SORT_RD),
			),
		);
	} else {
		$OUT->thumbnails_sort = false;
	}

	$OUT->album_name = $album_name;
	$OUT->thumbnail_column_width = ceil(100/$columns);

	if ($display_tabs) {
		$total_pages = ceil($thumb_count / $limit);
		$OUT->thumbnails_stats = sprintf(PIC_ON_PAGE, $thumb_count, $total_pages);
		$OUT->thumbnails_pagination = new \Poodle\Pagination(
			$CPG->buildUrl('thumbnails', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'page' => '${page}', 'sort' => $sort)),
			$total_pages, $page-1);
	} else {
		$OUT->thumbnails_stats = null;
		$OUT->thumbnails_pagination = null;
	}

	$OUT->display('coppermine/thumbnail_view');
}

// Return the url for a picture, allows to have pictures spreaded over multiple servers
function get_pic_url(&$pic_row, $mode)
{
	global $CONFIG;
	static $pic_prefix = array();
	if (!$pic_prefix) {
		$pic_prefix = array(
			'thumb' => $CONFIG['thumb_pfx'],
			'normal' => $CONFIG['normal_pfx'],
			'fullsize' => ''
		);
	}
	return DOMAIN_PATH . path2url($pic_row['filepath'] . $pic_prefix[$mode] . $pic_row['filename']);
}

// Speed-up the random image query by 'keying' the image table
function speedup_pictures()
{
	global $CONFIG;
	$db = \Dragonfly::getKernel()->SQL;
	$pic_count = $CONFIG['TABLE_PICTURES']->count();
	$granularity = floor($pic_count / \Coppermine::RANDPOS_MAX_PIC);
	if ($granularity != $CONFIG['randpos_interval'] && $pic_count > \Coppermine::RANDPOS_MAX_PIC) {
		$result = $db->query("UPDATE {$CONFIG['TABLE_PICTURES']} SET randpos = ROUND(RAND()*{$granularity})");
		$result = $db->query("UPDATE {$CONFIG['TABLE_CONFIG']} SET value = '{$granularity}' WHERE name = 'randpos_interval'");
	}
}

function get_categories_data()
{
	static $categories_data;
	if (!is_array($categories_data)) {
		$categories_data = \Dragonfly::getKernel()->SQL->uFetchAll("SELECT cid, catname, description, parent FROM {$GLOBALS['CONFIG']['TABLE_CATEGORIES']} ORDER BY pos") ?: array();
	}
	return $categories_data;
}
