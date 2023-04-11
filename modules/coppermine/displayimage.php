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

if (isset($_GET['addfav'])) {
	require_once('includes/coppermine/favorites.inc');
	$pid = ($_GET->uint('addfav') ?: cpg_error(PARAM_MISSING, 404));
	\Poodle\Notify::success(coppermine_switch_favorite($pid) ? ADDEDTOFAV : REMOVEFAV);
	\URL::redirect(preg_replace('/.addfav=[0-9]+/','',$_SERVER['REQUEST_URI']));
}

// Prints the image-navigation menu
function html_img_nav_menu($PIC_DATA)
{
	global $CONFIG, $USER_DATA, $meta, $album, $cat, $pos, $pic_count;
	$CPG = \Coppermine::getInstance();
	$human_pos = $pos + 1;
	$page = ceil($human_pos / ($CONFIG['thumbrows'] * $CONFIG['thumbcols']));
	$ecard_tgt = $slideshow_tgt = null;
	$sort = $_GET->text('sort');
	if ($USER_DATA['can_send_ecards'] && (USER_ID || $CONFIG['allow_anon_fullsize'] || USER_IS_ADMIN)) {
		$ecard_tgt = $CPG->buildUrl('ecard', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'pid' => $PIC_DATA['pid'], 'pos' => $pos));
	}
	// Only show the slideshow to registered user, admin, or if admin allows anon access to full size images
	if (USER_ID || $CONFIG['allow_anon_fullsize'] || USER_IS_ADMIN ) {
		$slideshow_tgt = $CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'pid' => $PIC_DATA['pid'], 'pos' => $pos, 'sort' => $sort, 'slideshow' => 1));
	}
	return array(
		'thumb'     => $CPG->buildUrl('thumbnails', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'page' => $page)),
		'slideshow' => $slideshow_tgt,
		'pic_pos'   => sprintf(PIC_POS, $human_pos, $pic_count),
		'ecard'     => $ecard_tgt,
		'prev'      => $pos > 0 ? $CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'pos' => $pos - 1, 'sort' => $sort)) : null,
		'next'      => $human_pos < $pic_count ? $CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'pos' => $human_pos, 'sort' => $sort)) : null,
	);
}

// Displays a picture
function html_picture($PIC_DATA)
{
	global $module_name, $CONFIG, $USER, $album, $meta;

	$edit = false;

	$pid = $PIC_DATA['pid'];
	// Check for anon picture viewing - only for registered user, admin, or if admin allows anon access to full size images
	if (USER_ID > 1 || $CONFIG['allow_anon_fullsize'] || USER_IS_ADMIN) {
		// Add 1 to hit counter unless the user reloaded the page
		if (!isset($USER['liv']) || !is_array($USER['liv'])) {
			$USER['liv'] = array();
		}
		// Add 1 to hit counter
		if ('lasthits' != $meta && !in_array($pid, $USER['liv']) && isset($_COOKIE[$CONFIG['cookie_name'] . '_data'])) {
			add_hit($pid);
			if (count($USER['liv']) > 4) array_shift($USER['liv']);
			$USER['liv'][] = $pid;
		}

		if ($CONFIG['make_intermediate'] && max($PIC_DATA['pwidth'], $PIC_DATA['pheight']) > $CONFIG['picture_width']) {
			$picture_url = get_pic_url($PIC_DATA, 'normal');
		} else {
			$picture_url = get_pic_url($PIC_DATA, 'fullsize');
		}

		$edit = ((USER_ADMIN_MODE && $GLOBALS['CURRENT_ALBUM_DATA']['user_id'] == USER_ID)
		 || can_admin($module_name) || $PIC_DATA['owner_id'] == USER_ID);

		$image_size = compute_img_size($PIC_DATA['pwidth'], $PIC_DATA['pheight'], $CONFIG['picture_width']);

		$url = array();
		if (isset($image_size['reduced'])) {
			$winsizeX = $PIC_DATA['pwidth'] + 16;
			$winsizeY = $PIC_DATA['pheight'] + 16;
			$target = uniqid(mt_rand(), true);
			$url = URL::index("&file=displayimagepopup&pid={$pid}&fullsize=1",true,true);
			$url = array(
				'href' => $url,
				'target' => $target,
				'title' => VIEW_FS,
				'onclick' => "window.open('{$url}','{$target}','resizable=yes,scrollbars=yes,width={$winsizeX},height={$winsizeY},left=0,top=0');return false",
			);
		}
	} else {
		// $picture_url is where the Registered Only picture is
		$picture_url = \Dragonfly::getKernel()->OUT->theme;
		if (!file_exists("themes/{$picture_url}/images/coppermine/ina.jpg")) {
			$picture_url = 'default';
		}
		$picture_url = "themes/{$picture_url}/images/coppermine/ina.jpg";
		$imagesize = getimagesize($picture_url);
		$image_size = compute_img_size($imagesize[0], $imagesize[1], $CONFIG['picture_width']);
		$PIC_DATA['title'] = MEMBERS_ONLY;
		$PIC_DATA['caption'] = '';
		$picture_url = DOMAIN_PATH . $picture_url;
		if ($url = \Dragonfly\Identity::getRegisterURL()) {
			$url = array(
				'href' => $url,
				'target' => null,
				'title' => 'Click to register',
				'onclick' => null,
			);
		}
	}

	return array(
		'id' => $PIC_DATA['pid'],
		'src' => $picture_url,
		'width' => $image_size['width'],
		'height' => $image_size['height'],
		'edit' => $edit,
		'title' => $PIC_DATA['title'],
		'caption' => $PIC_DATA['caption'],
		'link' => $url,
	);
}

function html_rating_box($PIC_DATA)
{
	global $CONFIG, $USER_DATA;
	if ($USER_DATA['can_rate_pictures'] && $GLOBALS['CURRENT_ALBUM_DATA']['votes']) {
		if (USER_ID || $CONFIG['allow_anon_fullsize'] || USER_IS_ADMIN) {
			return array(
				'id' => $PIC_DATA['pid'],
				'votes' => empty($PIC_DATA['pic_rating'])
					? NO_VOTES
					: sprintf(RATING, round($PIC_DATA['pic_rating'] / 2000, 1), $PIC_DATA['votes']),
			);
		}
	}
	return false;
}

// Display picture information
function html_picinfo($PIC_DATA)
{
	global $module_name, $CONFIG, $album, $meta, $cat, $pos, $db;
	$CPG = \Coppermine::getInstance();

	if (!USER_ID && !$CONFIG['allow_anon_fullsize'] && !USER_IS_ADMIN) {
		return false;
	}

	if ($CONFIG['picinfo_display_filename']) {
		$info[] = array(
			'label' => PIC_INF_FILENAME,
			'value' => $PIC_DATA['filename'],
			'uri' => null
		);
	}
	if (!empty($PIC_DATA['owner_id'])) {
		$vf_row = $db->uFetchRow("SELECT username FROM {$db->TBL->users} WHERE user_id = {$PIC_DATA['owner_id']}");
		if ($vf_row) {
			$info[] = array(
				'label' => 'Upload by',
				'value' => $vf_row[0],
				'uri' => \Dragonfly\Identity::getProfileURL($PIC_DATA['owner_id'])
			);
		}
	}
	if (can_admin($module_name) && !empty($PIC_DATA['pic_raw_ip'])) {
		$info[] = array(
			'label' => 'Upload IP',
			'value' => $PIC_DATA['pic_raw_ip'],
			'uri' => null
		);
	}
	if ($CONFIG['picinfo_display_album_name']) {
		$info[] = array(
			'label' => ALBUM_NAME,
			'value' => $GLOBALS['CURRENT_ALBUM_DATA']['title'],
			'uri' => $CPG->buildUrl('thumbnails', array('album' => $PIC_DATA['aid']))
		);
	}
	if (!empty($PIC_DATA['votes'])) {
		$info[] = array(
			'label' => sprintf(PIC_INFO_RATING, $PIC_DATA['votes']),
			'value' => \Coppermine::getRatingStars($PIC_DATA['pic_rating']),
			'uri' => null
		);
	}
	//$info[test] = "SELECT pid FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} ON visibility IN ({$USER_DATA['GROUPS']}) WHERE p.pid='".$PIC_DATA['pid']."' GROUP BY pid LIMIT 1";
	for ($i = 1; $i <= 4; ++$i) {
		if ($CONFIG['user_field' . $i . '_name'] && '' != $PIC_DATA['user' . $i]) {
			$info[] = array(
				'label' => $CONFIG['user_field' . $i . '_name'],
				'value' => $PIC_DATA['user' . $i],
				'uri' => null
			);
		}
	}
	if ($CONFIG['picinfo_display_file_size']) {
		$info[] = array(
			'label' => PIC_INF_FILE_SIZE,
			'value' => \Dragonfly::getKernel()->L10N->filesizeToHuman($PIC_DATA['filesize']),
			'uri' => null
		);
	}
	if ($CONFIG['picinfo_display_dimensions']) {
		$info[] = array(
			'label' => PIC_INF_DIMENSIONS,
			'value' => sprintf(SIZE, $PIC_DATA['pwidth'], $PIC_DATA['pheight']),
			'uri' => null
		);
	}
	if ($CONFIG['picinfo_display_dimensions']) {
		$info[] = array(
			'label' => DISPLAYED,
			'value' => sprintf(VIEWS, $PIC_DATA['hits']),
			'uri' => null
		);
	}

	$path_to_pic = $PIC_DATA['filepath'] . $PIC_DATA['filename'];

	if ($CONFIG['read_exif_data'] && function_exists('exif_read_data')) {
		require_once('includes/coppermine/exif_php.inc');
		if ($exif = exif_parse_file($path_to_pic)) {
			foreach (\Dragonfly::getKernel()->OUT->L10N['EXIF'] as $key => $label) {
				if (isset($exif[$key])) {
					$info[] = array(
						'label' => $label,
						'value' => strip_tags(trim($exif[$key], "\x0..\x1f")),
						'uri' => null
					);
				}
			}
		}
	}

	if ($CONFIG['read_iptc_data']) {
		require_once('includes/coppermine/iptc.inc');
		if ($iptc = get_IPTC($path_to_pic)) {
			foreach (\Dragonfly::getKernel()->OUT->L10N['IPTC'] as $key => $label) {
				if (isset($iptc[$key])) {
					$info[] = array(
						'label' => $label,
						'value' => strip_tags(trim(is_array($iptc[$key]) ? implode(' ', $iptc[$key]) : $iptc[$key], "\x0..\x1f")),
						'uri' => null
					);
				}
			}
		}
	}

	// with subdomains the variable is $_SERVER["SERVER_NAME"] does not return the right value instead of using a new config variable I reused $GLOBALS['BASEHREF'] with trailing slash in the configure
	// Create the add to fav link
	if ($CONFIG['picinfo_display_favorites']) {
		require_once('includes/coppermine/favorites.inc');
		$FAVPICS = coppermine_get_favorites();
		$info[] = array(
			'label' => ADDFAVPHRASE,
			'value' => in_array($PIC_DATA['pid'], $FAVPICS) ? REMFAV : ADDFAV,
			'uri' => $CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $cat, 'meta' => $meta, 'pos' => $pos, 'addfav' => $PIC_DATA['pid']))
		);
	}

	return $info;
}

// Displays comments for a specific picture
function html_comments($PIC_DATA)
{
	global $db, $module_name, $CONFIG, $USER, $USER_DATA;
	$comments = array();
	if (USER_ID > 1 || $CONFIG['allow_anon_fullsize'] || USER_IS_ADMIN) {
		\Dragonfly\Output\Js::add('modules/coppermine/javascript/displayimage.js');
		require_once('includes/coppermine/favorites.inc');
		$FAVPICS = coppermine_get_favorites();
		$result = $db->query("SELECT msg_id, msg_author, msg_body, msg_date, author_id, author_md5_id, msg_raw_ip FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid={$PIC_DATA['pid']} ORDER BY msg_id ASC");
		while ($row = $result->fetch_assoc()) {
			$comments[] = array(
				'user_can_edit' => (can_admin($module_name) || (USER_ID > 1 && USER_ID == $row['author_id'] && $USER_DATA['can_post_comments']) || (USER_ID < 2 && $USER_DATA['can_post_comments'] && md5(session_id()) == $row['author_md5_id'])),
				'author'        => $row['msg_author'],
				'id'            => $row['msg_id'],
				'type'          => can_admin($module_name) ? 'text' : 'hidden',
				'delete_text'   => DELETE.' '.COMMENT,
				'date'          => \Dragonfly::getKernel()->L10N->strftime(COMMENT_DATE_FMT, $row['msg_date']),
				'body'          => \Dragonfly\Smilies::parse(\URL::makeClickable($row['msg_body'])),
				'body_raw'      => $row['msg_body'],
				'ip'            => ($row['msg_raw_ip'] && can_admin($module_name)) ? \Dragonfly\Net::decode_ip($row['msg_raw_ip']) : null,
			);
		}
		if ($USER_DATA['can_post_comments'] && $GLOBALS['CURRENT_ALBUM_DATA']['comments']) {
			$OUT = \Dragonfly::getKernel()->OUT;
			$OUT->picture['comment'] = array(
				'username' => isset($USER['name']) ? $USER['name'] : '',
				'max_length' => $CONFIG['max_com_size'],
			);
		}
	}
	return $comments;
}

/**
 * Main code
 */
$pos = $_GET->uint('pos');
$pid = $_GET->uint('pid');
$album = $_GET->uint('album');
$meta = $_GET->raw('meta');
$cat = $_GET->uint('cat');

// $thisalbum is passed to get_pic_data as a varible used in queries
// to limit meta queries to the current album or category
$thisalbum = "category >= 0"; // just something that is true
if ($album) { //  && $cat<0 Meta albums, we need to restrict the albums to the current category
	$thisalbum = "a.aid = {$album}";
} else if ($cat) {
	if ($cat == \Coppermine::USER_GAL_CAT) {
		$thisalbum = '(category = '.\Coppermine::USER_GAL_CAT.' OR category > '.\Coppermine::FIRST_USER_CAT.')';
	} else {
		$thisalbum = "category = {$cat}";
	}
}

// Retrieve data for the current picture
if ($pid > 0 && ('random' == $meta || (!$meta && !$album))) {
	$row = $db->uFetchRow("SELECT p.aid FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE approved = 1 AND p.pid={$pid}");
	if (!$row) {
		list($visibility) = $db->uFetchRow("SELECT a.visibility FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid) AND p.pid={$pid}");
		if ($visibility == 2) {
			cpg_error(MEMBERS_ONLY, 403);
		// works needs translation
//		} else if ($visibility >= \Coppermine::FIRST_USER_CAT) {
//			cpg_error('Users Private Gallery', 403);
		}
		cpg_error(_MODULESADMINS, 403);
	}
	$album = $row[0];
	if (!isset($pos)) {
		$pos = 0;
		if (!$meta) {
			// pos is ordered by filename
			$result = $db->query("SELECT pid FROM {$CONFIG['TABLE_PICTURES']} WHERE aid = {$album} ORDER BY filename");
			foreach ($result as $i => $row) {
				if ($row['pid'] == $pid) {
					$pos = $i;
					break;
				}
			}
		}
	}
	$pic_data = get_pic_data('', $album, $pic_count, $album_name, 0, 0);
	if ($pic_data) {
		while ($row = $pic_data->fetch_assoc()) {
			if ($row['pid'] == $pid) {
				$CURRENT_PIC_DATA = $row;
				break;
			}
		}
		unset($pic_data);
	}
} else {
	$pos = $pos?:0;
	$pic_data = get_pic_data($meta, $album, $pic_count, $album_name, 1, $pos);
	if (!$pic_data || !$pic_data->num_rows) {
		if ($pos >= $pic_count) {
			cpg_error(NO_IMG_TO_DISPLAY, 404);
		}
		# last comment removed from an album and search engine cached the url
		cpg_error(sprintf(_ERROR_NONE_TO_DISPLAY, _COMMENTS), 404);
	}
	if (!$pic_count) {
		list($visibility) = $db->uFetchRow("SELECT visibility FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid=".$album);
		if (2 == $visibility){
			cpg_error(MEMBERS_ONLY, 403);
		//works
//		} else if (\Coppermine::FIRST_USER_CAT == $visibility) {
//			cpg_error('Users Private Gallery', 403);
		} else{
			cpg_error(_MODULESADMINS, 403);
		}
	}
	$CURRENT_PIC_DATA = $pic_data->fetch_assoc();
	unset($pic_data);
}

// Retrieve data for the current album
if (empty($CURRENT_PIC_DATA)) {
	cpg_error('not found', 404);
}

\Dragonfly\Page::tag('link rel="canonical" href="'.\URL::index("&file=displayimage&pid={$CURRENT_PIC_DATA['pid']}").'"');

$CURRENT_ALBUM_DATA = $db->uFetchAssoc("SELECT title, comments, votes, category, user_id FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid={$CURRENT_PIC_DATA['aid']}");
if (!$CURRENT_ALBUM_DATA) {
	cpg_error(sprintf(PIC_IN_INVALID_ALBUM, $CURRENT_PIC_DATA['aid']));
}
if (!empty($CURRENT_PIC_DATA['keywords'])) {
	\Dragonfly\Page::metatag('keywords', $CURRENT_PIC_DATA['keywords']);
}

// slideshow control
if (isset($_GET['slideshow'])) {
	require_once 'includes/coppermine/slideshow.inc';
} else {
	$CURRENT_PIC_DATA['title'] = trim($CURRENT_PIC_DATA['title']) ? $CURRENT_PIC_DATA['title'] : strtr(preg_replace('/(.+)\..*?\Z/', '\\1', htmlprepare($CURRENT_PIC_DATA['filename'])), '_', ' ');

	// Display Filmstrip if the album is not search
	$film_strip = array();
	if ($CONFIG['display_film_strip'] && 'search' !== $album) {
		$max_item = $CONFIG['max_film_strip_items'];
		$limit = $max_item * 2;
		$offset = max(0, $pos - $CONFIG['max_film_strip_items']);
		$new_pos = max(0, $pos - $offset);
		$pic_data = get_pic_data($meta, $album, $thumb_count, $album_name, $limit, $offset);
		$pic_data = $pic_data ? $pic_data->fetch_all() : array();
		$max_item = min($max_item, count($pic_data));
		$lower_limit = 3;
		if (!isset($pic_data[$new_pos + 1])) {
			$lower_limit = $new_pos - $max_item + 1;
		} else if (!isset($pic_data[$new_pos + 2])) {
			$lower_limit = $new_pos - $max_item + 2;
		} else if (!isset($pic_data[$new_pos-1])) {
			$lower_limit = $new_pos;
		} else {
			$hf = $max_item / 2;
			$ihf = (int)($max_item / 2);
			if ($new_pos > $hf) {
				$lower_limit = $new_pos - $ihf;
			} else if ($new_pos < $hf) {
				$lower_limit = 0;
			}
		}
		$pic_data = array_slice($pic_data, $lower_limit, $max_item, true);
		if (count($pic_data)) {
			$CPG = \Coppermine::getInstance();
			$sort = $_GET->text('sort');
			foreach ($pic_data as $key => $row) {
				$image_size = compute_img_size($row['pwidth'], $row['pheight'], $CONFIG['thumb_width']);
				if (!$CONFIG['seo_alts']) {
					$pic_title = FILENAME . $row['filename']
						. "\n" . FILESIZE . \Dragonfly::getKernel()->L10N->filesizeToHuman($row['filesize'])
						. "\n" . DIMENSIONS . $row['pwidth'] . "x" . $row['pheight']
						. "\n" . DATE_ADDED . \Dragonfly::getKernel()->L10N->strftime(ALBUM_DATE_FMT, $row['ctime']);
				} else if ($row['title']) {
					$pic_title = $row['title'];
					if ($row['keywords']) {
						$pic_title .= "\n" . $row['keywords'];
					}
				} else if ($row['keywords']) {
					$pic_title = $row['keywords'];
				} else {
					$pic_title = substr($row['filename'], 0, -4);
				}
				$film_strip[] = array(
					'src'    => get_pic_url($row, 'thumb'),
					'title'  => $pic_title,
					'width'  => $image_size['width'].'px',
					'height' => $image_size['height'].'px',
					'url'    => $CPG->buildUrl('displayimage', array(
						'album' => $album, 'cat' => $cat, 'meta' => $meta, 'sort' => $sort,
						'pid' => $row['pid'], 'pos' => $key + $offset
						)),
				);
			}
		}
	}

	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->picture = html_picture($CURRENT_PIC_DATA);
	$OUT->picture['nav_menu']   = html_img_nav_menu($CURRENT_PIC_DATA);
	$OUT->picture['rating']     = html_rating_box($CURRENT_PIC_DATA);
	$OUT->picture['pic_info']   = html_picinfo($CURRENT_PIC_DATA);
	$OUT->picture['comment']    = array();
	$OUT->picture['comments']   = html_comments($CURRENT_PIC_DATA);
	$OUT->picture['film_strip'] = $film_strip;

	pageheader($album_name . ' / ' . $CURRENT_PIC_DATA['title'], true);
	$OUT->display('coppermine/displayimage');
	unset($OUT->picture);

	pagefooter();
}
