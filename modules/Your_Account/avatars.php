<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
\Dragonfly\Page::title(_AVATAR_GALLERY, false);

use \Dragonfly\Identity\Avatar;

function check_image_type($filetype)
{
	if (preg_match('#image/[x\\-]*([a-z]+)#', $filetype, $type)) {
		switch ($type[1]) {
			case 'jpeg':
			case 'pjpeg':
			case 'jpg':	return '.jpg';
			case 'gif': return '.gif';
			case 'png': return '.png';
		}
	}
	cpg_error(sprintf(_AVATAR_ERR_IMTYPE, $filetype));
}

function avatar_delete(\Dragonfly\Identity $userinfo)
{
	$CFG = \Dragonfly::getKernel()->CFG->avatar;
	if (Avatar::TYPE_UPLOAD == $userinfo->avatar_type && is_file($CFG->path.'/'.$userinfo['user_avatar'])) {
		unlink($CFG->path.'/'.$userinfo['user_avatar']);
	}
	$userinfo->avatar      = '';
	$userinfo->avatar_type = Avatar::TYPE_NONE;
}

function avatar_upload(\Dragonfly\Identity $userinfo, $avatar)
{
	$CFG = \Dragonfly::getKernel()->CFG->avatar;
	if (is_string($avatar)) {
		if (!preg_match('#^https?://.+\\.(gif|jpg|jpeg|png)$#i', $avatar, $url_ary) ) {
			cpg_error('The URL you entered is incomplete');
		}
		$avatar_filename = $avatar;
		$avatar = \Poodle\HTTP\URLInfo::get($avatar_filename, !$CFG->animated, true);
		if (!isset($avatar->size)) {
			cpg_error(_AVATAR_ERR_DATA);
		}
		if (!$CFG->animated && $avatar->animation) {
			cpg_error('Animated avatar not allowed');
		}
		$avatar_filetype = $avatar->type;
		$basename = $userinfo['user_id'].'_'.uniqid(mt_rand(), true).check_image_type($avatar_filetype);
		$avatar_filename = "{$CFG->path}/{$basename}";
		if ($avatar->size > 0 && \Poodle\File::putContents($avatar_filename, $avatar->data) != $avatar->size) {
			trigger_error('Could not write avatar to local storage', E_USER_ERROR);
		}
	} else if ($avatar instanceof \Poodle\Input\File) {
		$avatar_filetype = $avatar->type;
//		$avatar->validateType(array('png','jpeg','gif'))
		check_image_type($avatar_filetype);
		if (!$avatar->moveTo($CFG->path."/{$userinfo['user_id']}_".uniqid(mt_rand(), true))) {
			trigger_error("Could not copy avatar to local storage: {$CFG->path}", E_USER_ERROR);
		}
		$basename = $avatar->basename;
		$avatar_filename = "{$CFG->path}/{$basename}";
		if (!$CFG->animated && $fp = fopen($avatar_filename, 'rb')) {
			$data = fread($fp, min($CFG->filesize, filesize($avatar_filename)));
			fclose($fp);
			$data = preg_split('/\x00[\x00-\xFF]\x00\x2C/', $data); // split GIF frames
			if (count($data) > 2) {
				unlink($avatar_filename);
				cpg_error('Animated avatar not allowed');
			}
			unset($data);
		}
	} else {
		throw new \InvalidArgumentException('$avatar not of type string or Poodle\\Input\\File object');
	}

	if (filesize($avatar_filename) < 40) {
		unlink($avatar_filename);
		cpg_error(sprintf(_AVATAR_FILESIZE, round($CFG->filesize / 1024)));
	}

	// Scale image when needed
	list($width, $height) = getimagesize($avatar_filename);
	if ($height > $CFG->max_height || $width > $CFG->max_width) {
		$img = \Poodle\Image::open($avatar_filename);
		if (($width / $CFG->max_width) > ($height / $CFG->max_height)) {
			$img->thumbnailImage($CFG->max_width, 0);
		} else {
			$img->thumbnailImage(0, $CFG->max_height);
		}
		if (!$img->writeImage($avatar_filename)) {
			unlink($avatar_filename);
			cpg_error(sprintf(_AVATAR_ERR_SIZE, $width, $height), 'ERROR: Image size');
		}
		clearstatcache();
	}

	if (filesize($avatar_filename) > $CFG->filesize) {
		unlink($avatar_filename);
		cpg_error(sprintf(_AVATAR_FILESIZE, round($CFG->filesize / 1024)));
	}

	avatar_delete($userinfo);

	$userinfo->avatar      = $basename;
	$userinfo->avatar_type = Avatar::TYPE_UPLOAD;
}

function display_avatar_gallery(&$userinfo)
{
	$OUT = \Dragonfly::getKernel()->OUT;
	$avatar_path = \Dragonfly::getKernel()->CFG->avatar->gallery_path;

	$dir = opendir($avatar_path);
	$avatar_images = array();
	while ($file = readdir($dir)) {
		$filename = "{$avatar_path}/{$file}";
		if ($file[0] != '.' && !is_file($filename) && !is_link($filename)) {
			$sub_dir = opendir($filename);
			while ($sub_file = readdir($sub_dir)) {
				if (preg_match('/\\.(gif|png|jpe?g)$/Di', $sub_file)) {
					$avatar_images[$file][] = $sub_file;
				}
			}
		}
	}
	closedir($dir);
	ksort($avatar_images);

	$category = $_POST->text('avatarcategory') ?: key($avatar_images);

	$OUT->avatarcategories = array();
	$OUT->avatar_category_images = array();
	foreach ($avatar_images as $key => $images) {
		$OUT->avatarcategories[] = array(
			'value' => $key,
			'label' => ucfirst($key),
			'current' => ($key == $category),
		);
		if ($key == $category) {
			sort($images);
			foreach ($images as $image) {
				$image = $category.'/'.$image;
				$OUT->avatar_category_images[] = array(
					'src' => DF_STATIC_DOMAIN.$avatar_path.'/'.$image,
					'value' => $image,
				);
			}
		}
	}
	unset($avatar_images);

	if (defined('ADMIN_PAGES')) {
		display_admin_account_top_menu('avatar', $userinfo);
		$OUT->avatar_form_action = URL::admin("users&id={$userinfo['user_id']}&edit=avatar");
	} else {
		display_member_block();
		$OUT->avatar_form_action = URL::index('&edit=avatar');
	}

	$OUT->display('Your_Account/edit/avatar-gallery');
}
