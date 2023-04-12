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
  $Source: /cvs/html/modules/coppermine/thumbnails.php,v $
  $Revision: 9.1 $
  $Author: djmaze $
  $Date: 2005/04/01 13:42:59 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('THUMBNAILS_PHP', true);
define('INDEX_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

if (isset($_GET['sort'])) $USER['sort'] = $_GET['sort'];
if (isset($_GET['uid'])) $USER['uid'] = intval($_GET['uid']);
if (isset($_GET['search'])) {
    $USER['search'] = $_GET['search'];
    if (isset($_GET['type']) && $_GET['type'] == 'full') {
        $USER['search'] = '###' . $USER['search'];
    } 
}
if (isset($_POST['search'])) {
    $USER['search'] = $_POST['search'];
    if (isset($_POST['type']) && $_POST['type'] == 'full') {
        $USER['search'] = '###' . $USER['search'];
    } 
}  
//if (!isset($page)) $page = 1;
$page = isset($_GET['page']) ? intval($_GET['page']) : (isset($_POST['page']) ? intval($_POST['page']) : 1) ;
$album = isset($_GET['album']) ? intval($_GET['album']) : '';
$meta = $_GET['meta'] ?? $_POST['meta'] ?? '';
//$cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
if ($meta != '') {
    if ($album != '') {
        $thisalbum = "a.aid = $album";
    } elseif ($cat == 0) {
        $thisalbum = "a.category >= 0";
    } else {
        if ($cat == 1) $thisalbum = "a.category > ".FIRST_USER_CAT;
        else $thisalbum = "a.category = $cat";
    }
} else {
    $thisalbum = "a.category = cat";
}
pageheader((isset($CURRENT_ALBUM_DATA) ? $CURRENT_ALBUM_DATA['description'] : isset($_GET["meta"])) ? $lang_meta_album_names[$_GET['meta']] : '');
set_breadcrumb(!is_numeric($album));
display_thumbnails($meta, $album, $cat, $page, $CONFIG['thumbcols'], $CONFIG['thumbrows'], true);
// strpos ( string haystack, string needle [, int offset])
$mpl=$CONFIG['main_page_layout'];
if (strpos("$mpl","anycontent")=== true) {
    require_once("$CPG_M_DIR/anycontent.php");
}
pagefooter();

?>
