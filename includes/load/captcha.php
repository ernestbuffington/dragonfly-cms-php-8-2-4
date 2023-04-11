<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
define('SKIP_GZIP', true);

require_once(CORE_PATH. 'cmsinit.inc');
ob_implicit_flush();
\Dragonfly::ob_clean();
\Dragonfly::getKernel()->SESSION->abort();

header('Last-Modified: '.date('D, d M Y H:i:s', time()).' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Connection: Close');

$k = $_GET->keys();

$useimage = $MAIN_CFG->sec_code->back_img;
$fontsize = 5;
$ttf = false;
if ($MAIN_CFG->sec_code->font) {
	$font = CORE_PATH.'fonts/'.$MAIN_CFG->sec_code->font;
	$ttf = (function_exists('imagettftext') && is_file($font));
	$ttfsize = $MAIN_CFG->sec_code->font_size;
}
$theme = !empty($_SESSION['CPG_SESS']['theme']) ? $_SESSION['CPG_SESS']['theme'] : 'default';

if (isset($_GET['test'])) {
	$code = '123test';
	include_once('themes/default/theme.php');
} else {
	include_once("themes/{$theme}/theme.php");
	if (!isset($k[1]) || !preg_match('#^[a-z0-9]{32}$#',$k[1])) {
		$fontsize = 3;
		$useimage = $ttf = false;
		$gfxcolor = '#FFFFFF';
		$bgcolor1 = '#FF0000';
		$code = 'Invalid code';
	} else if (isset($_SESSION['DF_CAPTCHA'][$k[1]])) {
		$code = $_SESSION['DF_CAPTCHA'][$k[1]][0];
	} else {
		$fontsize = 3;
		$useimage = $ttf = false;
		$gfxcolor = '#FFFFFF';
		$bgcolor1 = '#FF0000';
		$code = 'Please accept cookies';
	}
}
if ($ttf) {
	$fontsize = $ttfsize;
	$border = imagettfbbox($ttfsize, 0, $font, $code);
	$width = $border[2]-$border[0];
} else {
	$width = strlen($code)*(4+$fontsize);
}
if ($useimage && isset($gfxcolor)) {
	if (is_file("themes/{$theme}/images/code_bg.jpg")) {
		$image = ImageCreateFromJPEG("themes/{$theme}/images/code_bg.jpg");
	} else if (is_file("themes/{$theme}/images/code_bg.png")) {
		$image = ImageCreateFromPNG("themes/{$theme}/images/code_bg.png");
	} else {
		$image = ImageCreateFromJPEG('images/code_bg.jpg');
	}
} else {
	if (!isset($gfxcolor) || !isset($bgcolor1)) {
		$gfxcolor = '#505050';
		$bgclr    = '#FFFFFF';
	} else {
		if (!isset($gfxcolor)) {
			$gfxcolor = $textcolor1; // $textcolor1, $textcolor2
		}
		$bgclr = $bgcolor1;   // $bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4
	}

	$bred   = hexdec(substr($bgclr, 1, 2));
	$bgreen = hexdec(substr($bgclr, 3, 2));
	$bblue  = hexdec(substr($bgclr, -2));

	$image = imagecreatetruecolor($width+6,20);
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
header('Content-type: image/png');
//Header('Content-type: image/jpeg');
ImagePNG($image);
//ImageJPEG($image, '', 75);
ImageDestroy($image);
exit;
