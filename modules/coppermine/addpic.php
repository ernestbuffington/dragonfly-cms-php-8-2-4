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

define('NO_HEADER', true);
require(__DIR__ . '/include/load.inc');

$up = 'pb';

if (can_admin($module_name) && !Dragonfly::isDemo()) {
	$aid = $_GET->uint('aid');
	$pic_file = $CONFIG['fullpath'] . \Poodle\Base64::urlDecode($_GET['pic_file']);
	$dir_name = dirname($pic_file) . '/';
	$file_name = basename($pic_file);
	// check if image has the correct extension else try to change the filename
	$imagesize = getimagesize($pic_file);
	if ($imagesize) {
		$file = explode('.', $pic_file);
		array_pop($file);
		$tmpname = implode('.', $file).image_type_to_extension($imagesize[2]);
		if ($pic_file != $tmpname && rename($pic_file, $tmpname)) {
			$file_name = basename($tmpname);
		}
		// check if image already exists in the database
		if ($CONFIG['TABLE_PICTURES']->count("filepath={$db->quote($dir_name)} AND filename={$db->quote($file_name)}")) {
			// Duplicate
			$up = 'dup';
		} else {
			require('includes/coppermine/picmgmt.inc');
			if (add_picture($aid, $dir_name, $file_name)) {
				$up = 'ok';
			}
		}
	}
}

$file_name = "themes/default/images/coppermine/up_{$up}.gif";
\Dragonfly::ob_clean();
header('Content-type: image/gif');
header('Connection: Close');
echo fpassthru(fopen($file_name, 'rb'));
exit;
