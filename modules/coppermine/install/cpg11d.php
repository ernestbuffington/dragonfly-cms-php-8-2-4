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
// This program is free software"; you can redistribute it and/or modify      //
// it under the terms of the GNU General Public License as published by      //
// the Free Software Foundation"; either version 2 of the License, or         //
// (at your option) any later version.                                       //
// ------------------------------------------------------------------------- //
if (!defined('INSTALL_PHP')) {
  die('Your are not allowed to access this page');
}
global $sql, $table_prefix;

$sql[] = "ALTER TABLE ".$table_prefix."categories CHANGE `namee` `catname` VARCHAR(255) NOT NULL";
$sql[] = "ALTER TABLE ".$table_prefix."comments add msg_raw_ip tinytext";
$sql[] = "ALTER TABLE ".$table_prefix."comments add msg_hdr_ip tinytext";
$sql[] = "ALTER TABLE ".$table_prefix."exif CHANGE `exifData` `exif_data` text NOT NULL";
$sql[] = "ALTER TABLE ".$table_prefix."pictures add pic_raw_ip tinytext";
$sql[] = "ALTER TABLE ".$table_prefix."pictures add pic_hdr_ip tinytext";

$sql[] = "UPDATE ".$table_prefix."config SET value= '1' WHERE name='debug_mode'";

$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumb_use', 'any')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('show_private', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('first_level', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('display_film_strip', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_film_strip_items', '5')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('comment_email_notification', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('nice_titles', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('advanced_debug_mode', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('read_iptc_data', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_filename', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_album_name', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_file_size', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_dimensions', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_count_displayed', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_URL', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_URL_bookmark', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_favorites', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('seo_alts', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('reg_notify_admin_email', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('disable_flood_protection', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allow_anon_fullsize', '1')";
$sql[] = "UPDATE ".$table_prefix."config SET value = 'breadcrumb/catlist/alblist/lastalb,1/lastup,1/lastcom,1/topn,1/toprated,1/random,1/anycontent' WHERE name = 'main_page_layout'";
$sql[] = "UPDATE ".$table_prefix."config SET value = 'default' WHERE name = 'theme'";

?>