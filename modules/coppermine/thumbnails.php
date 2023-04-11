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

if (isset($_GET['uid']))  { $USER['uid'] = intval($_GET['uid']); }
if (isset($_GET['search'])) {
	$USER['search'] = $_GET['search'];
	if (isset($_GET['type']) && $_GET['type'] == 'full') {
		$USER['search'] = '###' . $USER['search'];
	}
}
if (isset($_POST['search'])) {
	$USER['search'] = $_POST['search'];
	if (isset($_POST['type']) && $_POST['type'] == 'full') {
		$USER['search'] = '###' . $USER['search'];
	}
}

$album = $_GET->uint('album');
$cat   = $_POST->uint('cat')  ?: $_GET->uint('cat');
$meta  = $_POST->text('meta') ?: $_GET->text('meta');
$page  = $_POST->uint('page') ?: $_GET->uint('page') ?: 1;
if (!preg_match('#^[a-z]*$#D', $meta)) {
	cpg_error(sprintf(_ERROR_BAD_CHAR, ''), _SEC_ERROR);
}

if ($meta) {
	if ($album) {
		$thisalbum = "a.aid = {$album}";
	} else if (\Coppermine::USER_GAL_CAT == $cat) {
		$thisalbum = "(a.category = ".\Coppermine::USER_GAL_CAT." OR a.category > ".\Coppermine::FIRST_USER_CAT.")";
	} else if (\Coppermine::FIRST_USER_CAT < $cat) {
		$thisalbum = "a.user_id = ".($cat - \Coppermine::FIRST_USER_CAT)." AND (a.category = ".\Coppermine::USER_GAL_CAT." OR a.category = {$cat})";
	} else if ($cat) {
		$thisalbum = "a.category = {$cat}";
	} else {
		$thisalbum = "a.category >= 0";
	}
} else {
	$thisalbum = "a.category = cat";
}

pageheader($meta ? \Dragonfly::getKernel()->L10N['cpg_meta_album_names'][$meta] : '', true);

display_thumbnails($meta, $album, $cat, $page, $CONFIG['thumbrows'], true);

pagefooter();
