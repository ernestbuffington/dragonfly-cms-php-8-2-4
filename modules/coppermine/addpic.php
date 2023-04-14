<?php 
/***************************************************************************  
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke                                
  **************************************************************************  
   Port Copyright (C) 2004 Coppermine/CPG-Nuke Dev Team                        
   http://cpgnuke.com/                                               
  **************************************************************************  

   http://coppermine.sf.net/team/                                        
   This program is free software; you can redistribute it and/or modify       
   it under the terms of the GNU General Public License as published by       
   the Free Software Foundation; either version 2 of the License, or          
   (at your option) any later version.                                        
  **************************************************************************  
  Last modification notes:
  $Source: /public_html/modules/coppermine/addpic.php,v $
  $Revision: 9.2 $
  $Author: djmaze $
  $Date: 2005/02/17 08:22:57 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('ADDPIC_PHP', true);
define('NO_HEADER', true);
require("modules/" . $module_name . "/include/load.inc");
require('includes/coppermine/picmgmt.inc');
global $THEME_DIR;
// if (!GALLERY_ADMIN_MODE) die('Access denied');
$aid = intval($_GET['aid']);
$pic_file = $CONFIG['fullpath'] . base64_decode($_GET['pic_file']);
$dir_name = dirname($pic_file) . "/";
$file_name = basename($pic_file);

// check if image has the correct extension else try to change the filename
$imagesize = getimagesize($pic_file);
$tmpname = image_file_to_extension($pic_file, $imagesize[2]);
if ($pic_file != $tmpname && rename($pic_file, $tmpname)) {
    $file_name = basename($tmpname);
}

// check if image already exists in the database
$result = $db->sql_count($CONFIG['TABLE_PICTURES'], "filepath='".Fix_Quotes($dir_name)."' AND filename='".Fix_Quotes($file_name)."' LIMIT 0,1");

define('BATCH_MODE', true);
if ($result) {
    $up = 'dup';
} elseif (add_picture($aid, $dir_name, $file_name)) {
    $up = 'ok';
} else {
    $up = 'pb';
    echo $ERROR;
}
$file_name = (file_exists($THEME_DIR."/images/up_$up.gif")?$THEME_DIR:$CPG_M_DIR)."/images/up_$up.gif";

if (ob_get_length()) { exit; }
header('Content-type: image/gif');
echo fread(fopen($file_name, 'rb'), filesize($file_name));
