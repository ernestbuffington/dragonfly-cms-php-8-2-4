<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify       
   it under the terms of the GNU General Public License as published by       
   the Free Software Foundation; either version 2 of the License, or          
   (at your option) any later version.                                        
  **************************************************************************  
  Last modification notes:
  $Source: /cvs/html/modules/coppermine/showthumbbatch.php,v $
  $Revision: 9.0 $
  $Author: djmaze $
  $Date: 2005/01/12 03:32:54 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('SHOWTHUMB_PHP', true);
define('NO_HEADER', true);
require("modules/" . $module_name . "/include/load.inc");

define("UNKNOW_ICON", $CPG_M_DIR . '/images/unk48x48.gif');
define("GIF_ICON", $CPG_M_DIR . '/images/gif48x48.gif');
define("READ_ERROR_ICON", $CPG_M_DIR . '/images/read_error48x48.gif');

function makethumbnail($src_file, $newSize, $method)
{
    global $CONFIG;

    $content_type = array(
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_PNG => 'png'
    );
    // Checks that file exists and is readable
    if (!filesize($src_file) || !is_readable($src_file)) {
        header("Content-type: image/gif");
        fpassthru(fopen(READ_ERROR_ICON, 'rb'));
        exit;
    } 
    // find the image size, no size => unknow type
    $imginfo = getimagesize($src_file);
    if ($imginfo == null) {
        header("Content-type: image/gif");
        fpassthru(fopen(UNKNOW_ICON, 'rb'));
        exit;
    } 
    // GD can't handle gif images
    if ($imginfo[2] == IMAGETYPE_GIF && ($method == 'gd1' || $method == 'gd2')&& (!function_exists('imagecreatefromgif'))) {
        header("Content-type: image/gif");
        fpassthru(fopen(GIF_ICON, 'rb'));
        exit;
    } 
    // height/width
    $ratio = max((max($imginfo[0], $imginfo[1]) / $newSize), 1.0);
    $dest_info[0] = intval($imginfo[0] / $ratio);
    $dest_info[1] = intval($imginfo[1] / $ratio);
    $dest_info['quality'] = intval($CONFIG['jpeg_qual']);

    require_once('includes/imaging/imaging.inc');
    if (!Graphic::show($src_file, $dest_info)) return false;
}

makethumbnail($CONFIG['fullpath'] . $_GET['picfile'], $_GET['size'], $CONFIG['thumb_method']);
