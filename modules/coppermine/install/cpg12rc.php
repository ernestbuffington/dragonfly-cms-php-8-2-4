<?php 
// ------------------------------------------------------------------------- //
// Coppermine Photo Gallery for CMS                                          //
// ------------------------------------------------------------------------- //
// Copyright (C) 2002,2003 Gregory DEMAR <gdemar@wanadoo.fr>                 //
// http://www.chezgreg.net/coppermine/                                       //
// ------------------------------------------------------------------------- //
// Updated by the Coppermine Dev Team                                        //
// (http://coppermine.sf.net/team/)                                          //
// see /docs/credits.html for details                                        //
// ------------------------------------------------------------------------- //
// This program is free software; you can redistribute it and/or modify      //
// it under the terms of the GNU General Public License as published by      //
// the Free Software Foundation; either version 2 of the License, or         //
// (at your option) any later version.                                       //
// ------------------------------------------------------------------------- //
if (!defined('INSTALL_PHP')) {
  die('Your are not allowed to access this page');
}
global $sql, $user_prefix;

$sql[] = "ALTER TABLE ".$user_prefix."_users ADD user_group_list_cp VARCHAR(100) DEFAULT '2' NOT NULL AFTER user_group_cp";
$sql[] = "ALTER TABLE ".$table_prefix."exif CHANGE `exifData` `exif_data` text NOT NULL";
$sql[] = "UPDATE ".$user_prefix."_users SET user_group_list_cp = '3' WHERE user_group_cp = '3'";
$sql[] = "UPDATE ".$user_prefix."_users SET user_group_list_cp = '4' WHERE user_group_cp = '4'";


?>