<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/imaging/im.inc,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:26:14 $
**********************************************/

class Graphic {

  function resize($src_file, $dest_info, $dest_file, $imginfo=false, $force_extension=false)
  {
	global $MAIN_CFG;
	$impath = $MAIN_CFG['imaging']['impath'];

	if (!$imginfo) $imginfo = getimagesize($src_file);
	if (!$imginfo) return -1;
	
	if (!isset($dest_info[2])) { $dest_info[2] = $imginfo[2]; }
	if (!$force_extension) { $dest_file = image_file_to_extension($dest_file, $dest_info[2]); }
	if (!isset($dest_info['im_options'])) { $dest_info['im_options'] = ''; }
	if (!isset($dest_info['quality']) || intval($dest_info['quality']) < 10) $dest_info['quality'] = 85;
	$output = array();
	if (WINDOWS) {
		$src_file = '"'.strtr($src_file, '/', '\\').'"'; //"
		$im_dest_file = str_replace('%', '%%', ('"'.strtr($dest_file, '/', '\\').'"')); //"
	} else {
		$src_file = escapeshellarg($src_file);
		$im_dest_file = str_replace('%', '%%', escapeshellarg($dest_file));
	}
	$cmd = $MAIN_CFG['imaging']['impath']."convert $src_file -quality {$dest_info['quality']} {$dest_info['im_options']} -geometry {$dest_info[0]}x{$dest_info[1]} $im_dest_file";
/*
convert -size 320x85 xc:transparent -font Bookman-DemiItalic -pointsize 72 -draw "text 25,60 'Magick'" \
    -channel RGBA -gaussian 0x6 -fill darkred -stroke magenta -draw "text 20,55 'Magick'" fuzzy-magick.png
*/
	exec($cmd.' 2>&1', $output, $retval);
	if ($retval) {
		$ERROR = "Error executing ImageMagick - Return value: $retval<br />";
		$ERROR .= 'Cmd line : '.nl2br(htmlprepare($cmd)).'<br />';
		$ERROR .= nl2br(implode("\n", $output)).'<br />';
		trigger_error($ERROR, E_USER_WARNING);
		unlink($dest_file);
		return -3;
	}
	return $dest_file;
  }

  function show($src_file, $dest_info)
  {
	global $MAIN_CFG;
	$imginfo = getimagesize($src_file);
	if (!$imginfo) return -1;
	// impath = `locate --regexp=/convert$ -n 1` , default = /usr/bin/convert
	$src_file = escapeshellarg($src_file);
	if (!isset($dest_info['quality']) || intval($dest_info['quality']) < 10) $dest_info['quality'] = 85;
	$cmd = $MAIN_CFG['imaging']['impath']."convert -quality $dest_info[quality] -antialias -geometry {$dest_info[0]}x{$dest_info[1]} $src_file -";
	header('Content-type: '.image_type_to_mime_type($imginfo[2]));
	passthru($cmd);
  }
}
