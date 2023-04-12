<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/gfxchk.php,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:26:13 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$useimage = $MAIN_CFG['sec_code']['back_img'];
$fontsize = 5;
$ttf = false;
if ($MAIN_CFG['sec_code']['font']) {
	$font = CORE_PATH.'fonts/'.$MAIN_CFG['sec_code']['font'];
	$ttf = (function_exists('imagettftext') && file_exists($font));
	$ttfsize = $MAIN_CFG['sec_code']['font_size'];
}

get_theme();
if (isset($_GET['test'])) {
	$code = '123test';
	include_once('themes/default/theme.php');
} else {
	$gfxid = isset($_GET['id']) ? $_GET['id'] : 0;
	if (isset($CPG_SESS['gfx'][$gfxid])) {
		$code = $CPG_SESS['gfx'][$gfxid];
	} else {
		$fontsize = 3;
		$useimage = $ttf = false;
		$code = 'Please accept cookies';
	}
	include_once("themes/$CPG_SESS[theme]/theme.php");
}
if ($ttf) {
	$fontsize = $ttfsize;
	$border = imagettfbbox($ttfsize, 0, $font, $code);
	$width = $border[2]-$border[0];
} else {
	$width = strlen($code)*(4+$fontsize);
}
if ($useimage) {
	if (file_exists("themes/$CPG_SESS[theme]/images/code_bg.jpg")) {
		$image = ImageCreateFromJPEG("themes/$CPG_SESS[theme]/images/code_bg.jpg");
	} else if (file_exists("themes/$CPG_SESS[theme]/images/code_bg.png")) {
		$image = ImageCreateFromPNG("themes/$CPG_SESS[theme]/images/code_bg.png");
	} else {
		$image = ImageCreateFromJPEG('images/code_bg.jpg');
	}
	if (!isset($gfxcolor)) {
		$gfxcolor = '#505050';
	}
} else {
	if (!isset($gfxcolor)) {
		$txtclr = $textcolor1; //$textcolor1, $textcolor2
	}
	$bgclr  = $bgcolor1;   //$bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4

	$bred   = hexdec(substr($bgclr, 1, 2));
	$bgreen = hexdec(substr($bgclr, 3, 2));
	$bblue  = hexdec(substr($bgclr, -2));

	$image = ImageCreate($width+6,20);
	$background_color = ImageColorAllocate($image, $bred, $bgreen, $bblue);
	ImageFill($image, 0, 0, $background_color);
}

$tred   = hexdec(substr($gfxcolor, 1, 2));
$tgreen = hexdec(substr($gfxcolor, 3, 2));
$tblue  = hexdec(substr($gfxcolor, -2));

$left = (imagesx($image)-$width)/2;
if (function_exists('imagecolorallocatealpha')) {
	$txt_color = imagecolorallocatealpha($image, $tred, $tgreen, $tblue, 50);
	if ($ttf) {
		imagettftext($image, $fontsize, 0, $left+1, 16, $txt_color, $font, $code);
	} else {
		ImageString($image, $fontsize, $left+2, 3, $code, $txt_color);
	}
}
if ($ttf) {
	imagettftext($image, $fontsize, 0, $left, 15, ImageColorAllocate($image, $tred, $tgreen, $tblue), $font, $code);
} else {
	ImageString($image, $fontsize, $left, 2, $code, ImageColorAllocate($image, $tred, $tgreen, $tblue));
}
Header('Content-type: image/png');
//Header('Content-type: image/jpeg');
ImagePNG($image);
//ImageJPEG($image, '', 75);
ImageDestroy($image);
exit;