<?php
/***************************************************************************
 *							  functions_thumbs.php
 *							  -------------------
 *	 begin				  : Sat, Jul 27, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: functions_thumbs.php,v 9.2 2005/02/22 05:08:24 trevor Exp $
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 *
 ***************************************************************************/

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

$imagick = '';

//
// Calculate the needed size for Thumbnail
//
function get_img_size_format($width, $height)
{
	// Change these two values to define the Thumbnail Size
	$max_width = 400;
	$max_height = 200;
	if ($width > $max_width) {
		$tag_height = ($max_width / $width) * $height;
		$tag_width = $max_width;
		if ($tag_height > $max_height) {
			$tag_width = ($max_height / $tag_height) * $tag_width;
			$tag_height = $max_height;
		}
	} else if ($height > $max_height) {
		$tag_width = ($max_height / $height) * $width;
		$tag_height = $max_height;
		if ($tag_width > $max_width) {
			$tag_height = ($max_width / $tag_width) * $tag_height;
			$tag_width = $max_width;
		}
	} else {
		$tag_width = $width;
		$tag_height = $height;
	}
	return array(
		round($tag_width),
		round($tag_height)
	);
}

function create_thumbnail($source, $new_file)
{
	global $attach_config;
	$source = amod_realpath($source);
	$min_filesize = intval($attach_config['img_min_thumb_filesize']);
	$img_filesize = (file_exists(amod_realpath($source))) ? filesize($source) : false;
	if (!$img_filesize || $img_filesize <= $min_filesize) { return FALSE; }
	
	$size = image_getdimension($source);

	if ($size[0] <= 0 && $size[1] <= 0) { return FALSE; }

	$new_size = get_img_size_format($size[0], $size[1]);

	$tmp_path = '';
	$old_file = '';

	if (intval($attach_config['allow_ftp_upload'])) {
		$old_file = $new_file;
		$tmp_path = explode('/', $source);
		$tmp_path[count($tmp_path)-1] = '';
		$tmp_path = implode('/', $tmp_path);
		if ($tmp_path == '') { $tmp_path = '/tmp'; }
		$value = trim($tmp_path);
		if ($value[strlen($value)-1] == '/') { $value[strlen($value)-1] = ' '; }
		$new_file = trim($value) . '/t00000';
	}
	
	global $MAIN_CFG;
	if (!isset($MAIN_CFG['imaging']['type'])) {
//$attach_config['use_gd2']
		$MAIN_CFG['imaging']['type'] = empty($attach_config['img_imagick'])?'gd2':'im';
		$MAIN_CFG['imaging']['impath'] = $attach_config['img_imagick'];
		$MAIN_CFG['imaging']['pbmpath'] = $attach_config['img_imagick'];
	}
	require_once('includes/imaging/imaging.inc');
	Graphic::resize($source, $new_size, $new_file, $size);

	if (!file_exists(amod_realpath($new_file))) { return FALSE; }

	if (intval($attach_config['allow_ftp_upload'])) {
		$result = ftp_file($new_file, $old_file, $this->type, TRUE); // True for disable error-mode
		if (!$result) { return FALSE; }
	} else {
		chmod($new_file, (PHP_AS_NOBODY ? 0666 : 0644));
	}
	
	return TRUE;
}
