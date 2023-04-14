<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/imaging/netpbm.inc,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:26:14 $
**********************************************/

class Graphic {

  function _process($src_file, &$dest_info, &$imginfo)
  {
	if (!$imginfo) $imginfo = getimagesize($src_file);
	if (!$imginfo) return false;
	global $MAIN_CFG;
	$pbmpath = $MAIN_CFG['imaging']['pbmpath'];
	if (WINDOWS) {
		$src_file = strtr($src_file, '/', '\\'); //'
	}
	$src_file = escapeshellarg($src_file);
	$commands = array();
	switch ($imginfo[2]){
		case IMAGETYPE_GIF:  $op_in = 'giftopnm';  break;
		case IMAGETYPE_JPEG: $op_in = 'jpegtopnm'; break;
		case IMAGETYPE_PNG:  $op_in = 'pngtopnm';  break;
	}
	$commands[] = $pbmpath.$op_in." $src_file";
	$commands[] = $pbmpath."pnmscale -xsize={$dest_info[0]} -ysize={$dest_info[1]}";
	if (!isset($dest_info[2])) { $dest_info[2] = $imginfo[2]; }
	switch ($dest_info[2]){
		case IMAGETYPE_GIF:
			$commands[] = $pbmpath."ppmquant 255";
			$commands[] = $pbmpath."ppmtogif";
			break;

		case IMAGETYPE_JPEG:
			$commands[] = $pbmpath."pnmtojpeg -quality={$dest_info['quality']}";
//			$commands[] = $pbmpath."ppmtojpeg -quality={$dest_info['quality']}";
			break;

		case IMAGETYPE_PNG:
			$commands[] = $pbmpath."pnmtopng";
			break;
	}
	return implode('|', $commands);
  }

  function resize($src_file, $dest_info, $dest_file, $imginfo=false, $force_extension=false)
  {
	$cmd = Graphic::_process($src_file, $dest_info, $imginfo);
	if (!$imginfo) { return -1; }
	if (!$force_extension) { $dest_file = image_file_to_extension($dest_file, $dest_info[2]); }
	if (eregi('win',$_ENV['OS'])) {
		$im_dest_file = str_replace('%', '%%', ('"'.strtr($dest_file, '/', '\\').'"')); //"
	} else {
		$im_dest_file = str_replace('%', '%%', escapeshellarg($dest_file));
	}
	exec($cmd." > $im_dest_file", $output, $retval);
	if ($retval) {
		$ERROR = "Error executing netPBM - Return value: $retval<br />";
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
	$cmd = Graphic::_process($src_file, $dest_info, $imginfo);
	if (!$imginfo) return -1;
	header('Content-type: '.image_type_to_mime_type($dest_info[2]));
	passthru($cmd.' 2>&1', $retval);
	if ($retval) {
		echo "<br /><br />".$cmd."<br />retval: $retval<br />";
	}
	exit;
  }
}
