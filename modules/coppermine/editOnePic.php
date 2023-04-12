<?php 
/***************************************************************************  
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke and Postnuke                                
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
  $Source: /cvs/html/modules/coppermine/editOnePic.php,v $
  $Revision: 9.0 $
  $Author: djmaze $
  $Date: 2005/01/12 03:32:54 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
define('EDITPICS_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

if (!(GALLERY_ADMIN_MODE || USER_ADMIN_MODE)) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);
/*
if (isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $pid = (int)$_POST['id'];
} else {
    $pid = -1;
}*/ 
$pid = ($_POST['id'] ?? cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'id'), __FILE__, __LINE__)); 
$title = EDIT_PICS;
pageheader($title);
// Code after this is Specific to the individual actions - it would be preferable to have each actions in their own inc file
// Crop picture
// require_once("includes/coppermine/crop.inc.php");
// Edit description of the picture
require_once("includes/coppermine/editDesc.inc");
// Upload new thumbnail
// Rotate Image
// Just imagine
pagefooter();
