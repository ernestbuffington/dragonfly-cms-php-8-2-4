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
global $sql, $table_prefix;

$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('showupdate', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('right_blocks', '0')";
$sql[] = "ALTER TABLE ".$table_prefix."exif CHANGE `exifData` `exif_data` text NOT NULL";

?>