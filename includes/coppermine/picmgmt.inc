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

// Add a picture to an album
function add_picture($aid, $filepath, $filename, $title = '', $caption = '', $keywords = '', $user1 = '', $user2 = '', $user3 = '', $user4 = '', $category = 0, $watermark=false, $src_file=false)
{
	global $db, $module_name, $CONFIG, $USER_DATA, $PIC_NEED_APPROVAL, $username;

	$image = $filepath.$filename;
	$normal = $filepath.$CONFIG['normal_pfx'].$filename;
	$thumb = $filepath.$CONFIG['thumb_pfx'].$filename;
	if (!$src_file) { $src_file = $image; }
	if (!($imginfo = getimagesize($src_file))) { return false; }

	if (!file_exists($thumb) &&
	  !resize_image($src_file, $imginfo, $thumb, $CONFIG['thumb_width'], $CONFIG['thumb_use'])) {
		return false;
	}

	if (max($imginfo[0], $imginfo[1]) > $CONFIG['picture_width'] && $CONFIG['make_intermediate'] && !file_exists($normal) &&
	  !resize_image($src_file, $imginfo, $normal, $CONFIG['picture_width'], $CONFIG['thumb_use'], $watermark)) {
		unlink($thumb);
		return false;
	}

	if (!($imginfo = getimagesize($image))) {
		unlink($normal);
		unlink($thumb);
		return false;
	}
	$image_filesize = filesize($image);
	$total_filesize = $image_filesize + (file_exists($normal) ? filesize($normal) : 0) + filesize($thumb);
	// Test if disk quota exceeded
	if (!can_admin($module_name) && $USER_DATA['group_quota']) {
		list($total_space_used) = $db->uFetchRow("SELECT SUM(total_filesize) FROM {$CONFIG['TABLE_PICTURES']} WHERE owner_id = " . USER_ID);
		if ($total_space_used + $total_filesize > $USER_DATA['group_quota'] << 10) {
			unlink($normal);
			unlink($thumb);
			$msg = strtr(QUOTA_EXCEEDED, array(
				'[quota]' => ($USER_DATA['group_quota']),
				'[space]' => ($total_space_used >> 10)));
			cpg_error($msg);
		}
	}
	// Test if picture requires approval
	$approved = intval(can_admin($module_name)
	 || (!$USER_DATA['priv_upl_need_approval'] && (\Coppermine::USER_GAL_CAT == $category || \Coppermine::FIRST_USER_CAT + USER_ID == $category))
	 || !$USER_DATA['pub_upl_need_approval']);
	$PIC_NEED_APPROVAL = !$approved;

	$pid = $CONFIG['TABLE_PICTURES']->insert(array(
		'aid'        => $aid,
		'filepath'   => $filepath,
		'filename'   => $filename,
		'filesize'   => $image_filesize,
		'total_filesize' => $total_filesize,
		'pwidth'     => $imginfo[0],
		'pheight'    => $imginfo[1],
		'ctime'      => time(),
		'owner_id'   => USER_ID, // can_admin($module_name) ? 0 : USER_ID
		'title'      => $title,
		'caption'    => $caption,
		'keywords'   => $keywords,
		'approved'   => $approved,
		'user1'      => (string)$user1,
		'user2'      => (string)$user2,
		'user3'      => (string)$user3,
		'user4'      => (string)$user4,
		'pic_raw_ip' => $_SERVER['REMOTE_ADDR'],
	), 'pid');

	speedup_pictures();

	return $pid;
}

/**
 * resize_image()
 *
 * Create a file containing a resized image
 *
 * @param  $src_file the source file
 * @param  $dest_file the destination file
 * @param  $new_size the size of the square within which the new image must fit
 * @return 'true' in case of success
*/
function resize_image($src_file, $imginfo, &$dest_file, $new_size, $thumb_use, $watermark=false)
{
	global $CONFIG, $ERROR;

	$img = \Poodle\Image::open($src_file, $CONFIG['thumb_method']);

	$exif = exif_read_data($src_file, 'IFD0', true);
	if (!empty($exif['IFD0']['Orientation'])) {
		switch ($exif['IFD0']['Orientation'])
		{
		case 2: // camera flipped horizontal
			$img->flopImage();
			break;
		case 3: // camera upside down
			$img->rotateImage('', 180);
			break;
		case 4: // camera upside down flipped horizontal
			$img->flipImage();
			break;
		case 5: // camera left side up flipped vertical
			$img->transposeImage();
			break;
		case 6: // camera left side up
			$img->rotateImage('', 90);
			break;
		case 7: // camera left side down flipped vertical
			$img->transverseImage();
			break;
		case 8: // camera left side down
			$img->rotateImage('', 270);
			break;
		}
	}

	if ('ht' == $thumb_use) {
		$img->thumbnailImage(0, $new_size);
	} else if ('wd' == $thumb_use) {
		$img->thumbnailImage($new_size, 0);
	} else {
		$img->thumbnailImage($new_size, $new_size, true);
	}
	$img->setImageCompressionQuality(intval($CONFIG['jpeg_qual']));

	if ($watermark) {
		$img->add_text(array(
			'text'  => $watermark,
//			'x'     => 10,
//			'y'     => 20,
//			'size'  => 12,
//			'color' => '#000000',
//			'font'  => 'includes/poodle/image/fonts/default.ttf',
//			'angle' => 0,
		));
	}

	if (!$img->writeImage($dest_file)) {
		$ERROR = RESIZE_FAILED;
		return false;
	}

	$dest_file = $img->getImageFilename();
	// Set mode of uploaded picture
	chmod($dest_file, 0666);
	return true;
}

function getExifData($filename, $exiffile = null)
{
	$exiffile = preg_replace('/\\.[^.]+$/','.exif', $exiffile ?: $filename);
	if (is_file($exiffile)) {
		return json_decode(file_get_contents($exiffile), true);
	}
	if (function_exists('exif_read_data') && is_file($filename)) {
		$size = getimagesize($filename);
		if (IMAGETYPE_JPEG != $size[2]) return false; // Not a JPEG file
		if ($exif = exif_read_data($filename, null, true)) {
			if (!empty($exif['IFD0'])) {
				$exif = array('IFD0' => $exif['IFD0'], 'EXIF' => $exif['EXIF']);
				unset($exif['EXIF']['MakerNote']);
				unset($exif['IFD0']['PrintIM']);unset($exif['IFD0']['UndefinedTag:0xC4A5']);
				foreach ($exif as $section => $entries) {
					foreach ($entries as $key => $value) {
						if (is_string($value)) {
							$exif[$section][$key] = trim($value);
						}
					}
				}
				file_put_contents($exiffile, json_encode($exif));
				return $exif;
			}
		}
	}
	return false;
}
