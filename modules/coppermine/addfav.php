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
  $Source: /cvs/html/modules/coppermine/addfav.php,v $
  $Revision: 9.1 $
  $Author: djmaze $
  $Date: 2005/09/11 02:07:44 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
define('RATEPIC_PHP', true);
define('NO_HEADER', true);
require("modules/" . $module_name . "/include/load.inc");
// Check if required parameters are present
/*
if (!isset($_GET['pid'])) {
    cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);
}*/
$pic = (isset($_GET['pid'])&& intval($_GET['pid']) ? $_GET['pid'] : cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__));

// If user does not accept script's cookies, we don't accept the vote
if (!isset($_COOKIE[$CONFIG['cookie_name'] . '_data'])) {
    url_redirect(getlink('&file=displayimage&pid='.$pic));
}
$added =false;
// See if this picture is already present in the array
if (!in_array($pic, $FAVPICS)) {
    $FAVPICS[] = $pic;
    $added = true;
} else {
    $key = array_search($pic, $FAVPICS);
    unset ($FAVPICS[$key]);
    $added =false;
} 

$data = base64_encode(serialize($FAVPICS));
setcookie($CONFIG['cookie_name'] . '_fav', $data, time() + 86400 * 30, $MAIN_CFG['cookie']['path'], $MAIN_CFG['cookie']['domain']);

$location = getlink("&file=displayimage&pid=".$pic);
pageheader(INFO, $location);
msg_box(INFO, $added ? ADDEDTOFAV : REMOVEFAV, CONTINU, $location);
pagefooter();
