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
if (!defined('ADMIN_PAGES') || !can_admin($op)) { exit; }

if (isset($_GET['picfile'])) {
	define('NO_HEADER', true);
	require(dirname(__DIR__) . '/include/load.inc');
	try
	{
		$src_file = \Poodle\Base64::urlDecode($_GET->text('picfile'));
		if (!$src_file || false !== strpos($src_file, '..')) {
			throw new \Exception('invalid picfile');
		}
		$img = \Poodle\Image::open($CONFIG['fullpath'] . $src_file);
		$img->thumbnailImage(48, 48, true);
		$img->setImageCompressionQuality(intval($CONFIG['jpeg_qual']));
		header('Content-type: '.$img->getImageMimeType());
		header('Connection: Close');
		echo $img;
	}
	catch (\Exception $e)
	{
		header("Content-type: image/gif");
		header("X-Image-Error: ".$e->getMessage());
		fpassthru(fopen('themes/default/images/coppermine/read_error48x48.gif', 'rb'));
	}
	exit;
}

require(dirname(__DIR__) . '/include/load.inc');

/**
 * Local functions definition
 */

function generateAlbumsTree($folder='')
{
	global $CONFIG;

	$dirs = array();
	$dir_path = $CONFIG['fullpath'] . $folder;

	if (is_readable($dir_path)) {
		$dir = opendir($dir_path);
		$files = array();
		while ($file = readdir($dir)) {
			if (is_dir($CONFIG['fullpath'] . $folder . $file) && $file != '.' && $file != '..' && $file != 'CVS') {
				$files[] = $file;
			}
		}
		closedir($dir);
		natcasesort($files);
		foreach ($files as $file) {
			$start_target = $folder . $file;
			$dir_path = $CONFIG['fullpath'] . $folder . $file;
			$warnings = array();
			if (!is_writable($dir_path)) { $warnings[] = DIR_RO; }
			if (!is_readable($dir_path)) { $warnings[] = DIR_CANT_READ; }
			$dirs[] = array(
				'name' => $file,
				'uri' => URL::admin('&file=searchnew&startdir='.rawurlencode($start_target)),
				'errors' => $warnings,
				'items' => generateAlbumsTree($folder . $file . '/', 1)
			);
		}
	}
	return $dirs;
}

/**
 * CPGscandir() //renamed because php5 has scandir()func
 *
 * recursive function that scan a directory, create the HTML code for each
 * picture and add new pictures in an array
 *
 * @param  $dir the directory to be scanned
 * @param  $expic_array the array that contains pictures already in DB
 * @param  $newpic_array the array that contains new pictures found
 * @return
 */
function CPGscandir($dir)
{
	static $dir_id = 0;
	static $expic_array = null;
	global $db, $CONFIG;

	if (null === $expic_array) {
		$expic_array = array();
		$l = strlen($CONFIG['fullpath']) + 1;
		$result = $db->query("SELECT SUBSTRING(filepath, {$l}), filename FROM {$CONFIG['TABLE_PICTURES']} WHERE filepath LIKE '{$CONFIG['fullpath']}{$dir}%'");
		while ($row = $result->fetch_row()) {
			$expic_array[] = $row[0] . $row[1];
		}
	}

	$pic_array = array();
	$dir_array = array();

	$img_to_find = str_replace('jpg', 'jpe?g', str_replace('/', '|', strtolower($CONFIG['allowed_img_types'])));
	$fullpath = $CONFIG['fullpath'] . $dir;
	$cdir = opendir($fullpath);
	while ($file = readdir($cdir)) {
		if (is_dir($fullpath . $file)) {
			if ($file != '.' && $file != '..') {
				$dir_array[] = $file;
			}
		} else if (is_file($fullpath . $file) && preg_match('#\.('.$img_to_find.')$#i', $file)) {
			if (strncmp($file, $CONFIG['thumb_pfx'], strlen($CONFIG['thumb_pfx'])) != 0 && strncmp($file, $CONFIG['normal_pfx'], strlen($CONFIG['normal_pfx'])) != 0 && $file != 'index.html') {
				if (!in_array($dir.$file, $expic_array)) {
					$picfile = $dir . $file;
					$encoded_picfile = \Poodle\Base64::urlEncode($picfile);
					$picname = $CONFIG['fullpath'] . $picfile;
					$pic_fname = basename($picfile);
					$pic_dname = substr($picname, 0, -(strlen($pic_fname)));
					$thumb_file = dirname($picname) . '/' . $CONFIG['thumb_pfx'] . $pic_fname;
					if (file_exists($thumb_file)) {
						$img = DF_STATIC_DOMAIN . path2url($thumb_file);
//						$img = DF_STATIC_DOMAIN . path2url($picname);
					} else {
						$img = URL::admin("&file=searchnew&picfile={$encoded_picfile}");
					}
					$piclink = URL::index("&file=displayimagepopup&fullsize=1&picfile={$encoded_picfile}");
					if (filesize($picname) && is_readable($picname)) {
						$fullimagesize = getimagesize($picname);
						$winsizeX = ($fullimagesize[0] + 16);
						$winsizeY = ($fullimagesize[1] + 16);
						$pic_array[mb_strtolower($file)] = array(
							'dir_id' => $dir_id,
							'name' => $pic_fname,
							'img' => $img,
							'value' => $encoded_picfile,
							'onclick' => "open('{$piclink}', 'ImageViewer', 'toolbar=yes, status=yes, resizable=yes, scrollbars=yes, width={$winsizeX}, height={$winsizeY}')"
						);
					}
				}
			}
		}
	}
	closedir($cdir);
	natcasesort($dir_array);
	ksort($pic_array);

	$result = array();
	if ($pic_array) {
		$result[] = array(
			'id' => $dir_id,
			'label' => html_entity_decode(strip_tags(sprintf(TARGET_ALBUM, $dir, ''))),
			'warning' => is_writable($fullpath) ? false : CHANGE_PERM,
			'pictures' => array_values($pic_array),
		);
		++$dir_id;
	}
	foreach ($dir_array as $directory) {
		$result = array_merge($result, CPGscandir($dir . $directory . '/'));
	}
	return $result;
}

/**
 * Main code
 */

$result = $db->query("SELECT
	aid,
	user_id,
	title,
	username
FROM {$CONFIG['TABLE_ALBUMS']}
LEFT JOIN {$db->TBL->users} USING(user_id)
ORDER BY 4, 3");
// We need at least one album
if (!$result->num_rows) {
	cpg_error(NEED_ONE_ALBUM);
}

if (isset($_POST['insert'])) {
	if (!isset($_POST['pics'])) {
		cpg_error(NO_PIC_TO_ADD);
	}
	\Dragonfly\Page::title(PAGE_TITLE);
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->cpg_insert_pics = array();
	$r1 = uniqid(mt_rand(), true);
	$r2 = uniqid(mt_rand(), true);
	$album_array = array();
	while ($row = $result->fetch_row()) {
		$album_array[$row[0]] = $row[3] ? "({$row[3]}) {$row[2]}" : $row[2];
	}
	foreach ($_POST['pics'] as $dir_id => $pics) {
		// check to see if select has changed
		$album_id = $_POST->uint('dir',$dir_id);
		if (!$album_id) {
			continue;
		}
		// To avoid problems with PHP scripts max execution time limit, each picture is
		// added individually using a separate script that returns an image
		foreach ($pics as $picfile) {
			$pic_file = \Poodle\Base64::urlDecode($picfile);
			$uri = "&file=addpic&aid={$album_id}&pic_file={$picfile}&reload=";
			$OUT->cpg_insert_pics[] = array(
				'dir' => dirname($pic_file),
				'name' => basename($pic_file),
				'album' => $album_array[$album_id],
				'uri' => URL::index($uri . $r1),
				'img' => URL::index($uri . $r2),
			);
		}
	}
	$OUT->display('coppermine/admin/searchnew-insert');
}

else if (isset($_GET['startdir'])) {
	$startdir = $_GET['startdir'];
	if (false !== strpos($startdir, '..')) {
		cpg_error('Access denied: '.$startdir, 403);
	}
	$user_dir = preg_match("#/([1-9][0-9]+)($|/)#D", $startdir, $m) ? $m[1] : 0;
	if ($user_dir > \Coppermine::FIRST_USER_CAT) {
		$user_dir -= \Coppermine::FIRST_USER_CAT;
	} else {
		$user_dir = 0;
	}
	\Dragonfly\Page::title(PAGE_TITLE);
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->cpg_albums = array();
	while ($row = $result->fetch_row()) {
		if (!isset($OUT->cpg_albums[$row[1]])) {
			$OUT->cpg_albums[$row[1]] = array(
				'name' => $row[3],
				'albums' => array(array($row[0], $row[2], ($user_dir && $user_dir == $row[1]))),
			);
		} else {
			$OUT->cpg_albums[$row[1]]['albums'][] = array($row[0], $row[2], 0);
		}
	}
	$OUT->cpg_dirs = CPGscandir($startdir.'/');
	$OUT->display('coppermine/admin/searchnew-dir');
}

else {
	\Dragonfly\Page::title(PAGE_TITLE);
	\Dragonfly\Output\Css::add('poodle/tree');
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->cpg_dir_tree = generateAlbumsTree();
	$OUT->display('coppermine/admin/searchnew');
}
