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
if (!$USER_DATA['can_upload_pictures'] || Dragonfly::isDemo()) {
	cpg_error(PERM_DENIED, 403);
}

$CPG = \Coppermine::getInstance();
$uploadable_albums = $CPG->getUploadableAlbums(USER_ID);
if (!$uploadable_albums){
	$redirect = URL::index("&file=albmgr");
	pageheader(_ERROR);
	msg_box(\Dragonfly::getKernel()->L10N['Information'], ERR_NO_ALB_UPLOADABLES, CONTINU, $redirect);
	pagefooter();
	//cpg_error(ERR_NO_ALB_UPLOADABLES, 404);
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
	$K = \Dragonfly::getKernel();
	$IMG_TYPES = array(
		1 => 'GIF',
		2 => 'JPG',
		3 => 'PNG',
		4 => 'SWF',
		5 => 'PSD',
		6 => 'BMP',
		7 => 'TIFF',
		8 => 'TIFF',
		9 => 'JPC',
		10 => 'JP2',
		11 => 'JPX',
		12 => 'JB2',
		13 => 'SWC',
		14 => 'IFF'
	);

	// Test if the uploaded picture is valid
	$file = $_FILES ? $_FILES->getAsFileObject('userpicture') : null;
	if (!$file){
		cpg_error(NO_PIC_UPLOADED);
	}
	if ($file->errno) {
		cpg_error($file->error);
	}

	$album = $_POST->uint('album') ?: cpg_error(PARAM_MISSING);
	$title = $_POST->text('title');
	$caption = strip_tags(html2bb($_POST['caption']));
	$keywords = $_POST->text('keywords');
	$user1 = $_POST->text('user1');
	$user2 = $_POST->text('user2');
	$user3 = $_POST->text('user3');
	$user4 = $_POST->text('user4');
	check_words($title);
	check_words($caption);
	check_words($keywords);
	check_words($user1);
	check_words($user2);
	check_words($user3);
	check_words($user4);

	// Check if the album id provided is valid
	$category = false;
	foreach ($uploadable_albums as $group) {
		foreach ($group['albums'] as $group_album) {
			if ($group_album['aid'] == $album) {
				$category = $group_album['category'];
				break;
			}
		}
	}
	if (false === $category) {
		cpg_error(PERM_DENIED, 403);
	}

	$picname = strtolower($file->name);
	// Pictures are moved in a directory named 10000 + USER_ID
	$dest_dir = $CONFIG['userpics'].USER_ID;
	if (!is_dir($dest_dir)) {
		if (mkdir($dest_dir, 0777)) {
			$fp = fopen($dest_dir . '/index.html', 'w');
			if ($fp) {
				fwrite($fp, ' ');
				fclose($fp);
			}
		} else {
			trigger_error(sprintf(ERR_MKDIR, $dest_dir), E_USER_WARNING);
		}
	}
	$dest_dir .= '/';
	// Check that target dir is writable
	if (!is_writable($dest_dir)) {
		trigger_error(sprintf(DEST_DIR_RO, $dest_dir), E_USER_WARNING);
		$dest_dir = $CONFIG['userpics'];
		$picname = USER_ID.'-'.$picname;
	}
	if (!is_writable($dest_dir)) {
		cpg_error(sprintf(DEST_DIR_RO, $dest_dir));
	}
	// Replace forbidden chars with underscores
	$picname = explode('.',$picname);
	$ext     = array_pop($picname);
	$picname = implode('.', $picname);
	if (!preg_match('#^[a-zA-Z0-9_\-]+$#', $picname)) {
		$picname = \Poodle\Unicode::stripModifiers($picname);
		$picname = \Poodle\Unicode::to_latin($picname);
		$picname = preg_replace('#[^a-zA-Z0-9_\-]#', '_', $picname);
	}
	// Create a unique name for the uploaded file
	$picture_name = $picname . '.' . $ext;
	$nr = 0;
	while (file_exists($dest_dir . $picture_name)) {
		$picture_name = $picname . '~' . $nr++ . '.' . $ext;
	}
	$uploaded_pic = $dest_dir . $picture_name;

	// open_basedir restriction workaround
	// if (false === stripos(ini_get('open_basedir'), dirname($file->tmp_name)))
	$tmpfile = $file->moveTo($CONFIG['userpics'].md5(microtime()), 'tmp');
	if (!$tmpfile) {
		cpg_error('Couldn\'t create a copy of the uploaded image');
	}
	// Get picture information
	if (!($imginfo = getimagesize($tmpfile))) {
		unlink($tmpfile);
		cpg_error(ERR_INVALID_IMG);
	}

	// Check image type is among those allowed
	if (!stristr($CONFIG['allowed_img_types'], $IMG_TYPES[$imginfo[2]])) {
		unlink($tmpfile);
		cpg_error(sprintf(ALLOWED_IMG_TYPES, $CONFIG['allowed_img_types']));
	}

	// Check that picture size (in pixels) is lower than the maximum allowed
	$max = max($imginfo[0], $imginfo[1]);
	if ($max > $CONFIG['max_upl_width_height']) {
		$max = $CONFIG['max_upl_width_height'];
	}
	// Setup a textual watermark ?
	if ($CONFIG['watermark']) {
		$watermark = '(c)'.date('Y').' '.\Dragonfly::getKernel()->IDENTITY->nickname.' & '
			.(!empty($K->CFG['server']['domain']) ? $K->CFG['server']['domain'] : $K->CFG['global']['sitename']);
	} else {
		$watermark = false;
	}

	require('includes/coppermine/picmgmt.inc');
	// Create the "big" image
	if (!resize_image($tmpfile, $imginfo, $uploaded_pic, $max, '', $watermark)) {
		unlink($tmpfile);
		cpg_error($ERROR);
	}
	// Create thumbnail and intermediate image and add the image into the DB
	$pid = add_picture($album, $dest_dir, $picture_name, $title, $caption, $keywords, $user1, $user2, $user3, $user4, $category, $watermark, $tmpfile);
	if (!$pid) {
		unlink($uploaded_pic);
		unlink($tmpfile);
		cpg_error(sprintf(ERR_INSERT_PIC, $uploaded_pic) . "\n" . $ERROR);
	}

	$upload = new \Dragonfly\Identity\Upload();
	$upload->size = filesize($uploaded_pic); // $file->size;
	$upload->file = $uploaded_pic;
	$upload->name = $file->org_name;
	$upload->save();
	$CONFIG['TABLE_PICTURES']->update(array(
		'upload_id' => $upload->id
	),"pid = {$pid}");

	getExifData($tmpfile, $uploaded_pic);

	unlink($tmpfile);
	$redirect = ($PIC_NEED_APPROVAL) ? URL::index() : URL::index("&file=displayimage&pid={$pid}");
	\Dragonfly::closeRequest(UPLOAD_SUCCESS, 200, $redirect);
}

pageheader(UP_TITLE);

$OUT = \Dragonfly::getKernel()->OUT;
$OUT->cpg_config = $CONFIG;
$OUT->cpg_action = URL::index('&file=db_input');
$OUT->upload_albums = $uploadable_albums;
$OUT->display('coppermine/upload_form');

pagefooter();
