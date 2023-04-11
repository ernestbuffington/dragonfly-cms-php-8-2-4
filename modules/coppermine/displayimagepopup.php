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

	?name=coppermine&file=displayimagepopup&pid=63&fullsize=1
****************************************************************************/

define('NO_HEADER', true);
require(__DIR__ . '/include/load.inc');
header('Content-Type: text/html; charset=utf-8');

$pic_title = 'Error';
$pic_url = DF_STATIC_DOMAIN . 'images/error.gif';
if (isset($_GET['picfile'])) {
	$picfile = \Poodle\Base64::urlDecode($_GET['picfile']);
	if (false === strpos($picfile, '..')) {
		$pic_url = $CONFIG['fullpath'] . $picfile;
		$pic_url = DF_STATIC_DOMAIN . path2url($pic_url);
		$pic_title = $picfile;
	}
} else if (isset($_GET['pid'])) {
	$pid = $_GET->uint('pid') ?: 0;
	$row = $db->uFetchAssoc("SELECT p.filepath, p.filename, p.title FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE approved = 1 AND p.pid={$pid}");
	if ($row) {
		$pic_url = DOMAIN_PATH . path2url($row['filepath'] . $row['filename']);
		$pic_title = $row['title'];
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo CLICK_TO_CLOSE ?></title>
<style type="text/css">html, body { margin:0; padding:0; }</style>
</head>
<body onclick="window.close()">
<?php
	echo '<img src="' . $pic_url . '" alt="' . $pic_title . '"/>';
?>
</body>
</html>
