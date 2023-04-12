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
global $sql, $table_prefix, $CPG_M_DIR, $prefix, $dirname;

$sql[] = "CREATE TABLE ".$table_prefix."albums (
  aid int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  visibility int(11) NOT NULL default '0',
  uploads tinyint(1) NOT NULL default '0',
  comments tinyint(1) NOT NULL default '1',
  votes tinyint(1) NOT NULL default '1',
  pos int(11) NOT NULL default '0',
  category int(11) NOT NULL default '0',
  pic_count int(11) NOT NULL default '0',
  thumb int(11) NOT NULL default '0',
  last_addition datetime NOT NULL default '0000-00-00 00:00:00',
  stat_uptodate tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (aid),
  KEY alb_category (category)
) ENGINE=MyISAM";

$sql[] = "CREATE TABLE ".$table_prefix."categories (
  cid int(11) NOT NULL auto_increment,
  owner_id int(11) NOT NULL default '0',
  catname varchar(255) NOT NULL default '',
  description text NOT NULL,
  pos int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  subcat_count int(11) NOT NULL default '0',
  alb_count int(11) NOT NULL default '0',
  pic_count int(11) NOT NULL default '0',
  stat_uptodate tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (cid),
  KEY cat_parent (parent),
  KEY cat_pos (pos),
  KEY cat_owner_id (owner_id)
) ENGINE=MyISAM";

$sql[] = "INSERT INTO ".$table_prefix."categories VALUES (1, 0, 'User galleries', 'This category contains albums that belong to Coppermine users.', 0, 0, 0, 0, 0, 'NO')";

$sql[] = "CREATE TABLE ".$table_prefix."comments (
  pid mediumint(10) NOT NULL default '0',
  msg_id mediumint(10) NOT NULL auto_increment,
  msg_author varchar(25) NOT NULL default '',
  msg_body text NOT NULL,
  msg_date datetime NOT NULL default '0000-00-00 00:00:00',
  author_md5_id varchar(32) NOT NULL default '',
  author_id int(11) NOT NULL default '0',
  msg_raw_ip tinytext,
  msg_hdr_ip tinytext,
  PRIMARY KEY  (msg_id),
  KEY com_pic_id (pid)
) ENGINE=MyISAM";

$sql[] = "CREATE TABLE ".$table_prefix."config (
  name varchar(40) NOT NULL default '',
  value varchar(255) NOT NULL default '',
  PRIMARY KEY  (name)
) ENGINE=MyISAM";

$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('albums_per_page', '12')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('album_list_cols', '2')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('display_pic_info', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('alb_list_thumb_size', '50')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allowed_file_extensions', 'GIF/PNG/JPG/JPEG/TIF/TIFF')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allowed_img_types', 'JPG/GIF/PNG/TIFF')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allow_private_albums', '1')";
//$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allow_user_registration', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allow_duplicate_emails_addr', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('caption_in_thumbview', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('charset', 'language file')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('cookie_name', '".$_POST['table_prefix']."nuke')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('cookie_path', '/')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('debug_mode', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('default_sort_order', 'na')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('ecards_more_pic_target', 'http://www.localhost.com/')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('enable_smilies', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('filter_bad_words', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('forbiden_fname_char', '$/\\\\\\\\:*?&quot;\\'&lt;&gt;|`')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('fullpath', '".$CPG_M_DIR."/albums/')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('gallery_admin_email', 'you@somewhere.com')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('gallery_description', 'Your online photo album')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('gallery_name', 'Coppermine Photo Gallery')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('im_options', '-antialias')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('impath', '')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('jpeg_qual', '80')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('keep_votes_time', '30')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('lang', 'english')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('main_page_layout', 'breadcrumb/catlist/alblist/lastalb,1/lastup,1/lastcom,1/topn,1/toprated,1/random,1/anycontent')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('main_table_width', '100%')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('make_intermediate', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_com_lines', '10')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_com_size', '512')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_com_wlength', '38')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_img_desc_length', '512')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_tabs', '12')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_upl_size', '1024')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_upl_width_height', '2048')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('min_votes_for_rating', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('normal_pfx', 'normal_')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picture_table_width', '600')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picture_width', '400')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('randpos_interval', '5')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('read_exif_data', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('reg_requires_valid_email', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('subcat_level', '2')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('theme', 'default')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumbcols', '4')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumbrows', '3')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumb_method', 'gd2')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumb_pfx', 'thumb_')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumb_width', '100')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('userpics', '".$CPG_M_DIR."/albums/userpics/')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('user_field1_name', '')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('user_field2_name', '')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('user_field3_name', '')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('user_field4_name', '')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('display_comment_count', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('display_film_strip', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('max_film_strip_items', '5')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('first_level', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('show_private', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('thumb_use', 'ht')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('comment_email_notification', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('disable_flood_protection', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('nice_titles', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('advanced_debug_mode', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('seo_alts', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('read_iptc_data', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_favorites', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_filename', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_album_name', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_file_size', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_dimensions', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_count_displayed', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_URL', '0')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('picinfo_display_URL_bookmark', '1')";
$sql[] = "INSERT INTO ".$table_prefix."config VALUES ('allow_anon_fullsize', '1')";

$sql[] = "CREATE TABLE ".$table_prefix."exif (
  filename varchar(255) NOT NULL default '',
  exif_data text NOT NULL,
  UNIQUE KEY filename (filename)
) ENGINE=MyISAM";

$sql[] = "CREATE TABLE ".$table_prefix."pictures (
  pid int(11) NOT NULL auto_increment,
  aid int(11) NOT NULL default '0',
  filepath varchar(255) NOT NULL default '',
  filename varchar(255) NOT NULL default '',
  filesize int(11) NOT NULL default '0',
  total_filesize int(11) NOT NULL default '0',
  pwidth smallint(6) NOT NULL default '0',
  pheight smallint(6) NOT NULL default '0',
  hits int(10) NOT NULL default '0',
  mtime timestamp(14) NOT NULL,
  ctime int(11) NOT NULL default '0',
  owner_id int(11) NOT NULL default '0',
  owner_name varchar(40) NOT NULL default '',
  pic_rating int(11) NOT NULL default '0',
  votes int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  caption text NOT NULL,
  keywords varchar(255) NOT NULL default '',
  approved tinyint(1) NOT NULL default '0',
  user1 varchar(255) NOT NULL default '',
  user2 varchar(255) NOT NULL default '',
  user3 varchar(255) NOT NULL default '',
  user4 varchar(255) NOT NULL default '',
  url_prefix tinyint(4) NOT NULL default '0',
  randpos int(11) NOT NULL default '0',
  pic_raw_ip tinytext,
  pic_hdr_ip tinytext,
  PRIMARY KEY  (pid),
  KEY pic_hits (hits),
  KEY pic_rate (pic_rating),
  KEY aid_approved (aid,approved),
  KEY randpos (randpos),
  KEY pic_aid (aid),
  FULLTEXT KEY search (title,caption,keywords,filename,user1,user2,user3,user4)
) ENGINE=MyISAM";

$sql[] = "CREATE TABLE ".$table_prefix."usergroups (
  group_id int(11) NOT NULL auto_increment,
  group_name varchar(255) NOT NULL default '',
  group_quota int(11) NOT NULL default '0',
  has_admin_access tinyint(4) NOT NULL default '0',
  can_rate_pictures tinyint(4) NOT NULL default '0',
  can_send_ecards tinyint(4) NOT NULL default '0',
  can_post_comments tinyint(4) NOT NULL default '0',
  can_upload_pictures tinyint(4) NOT NULL default '0',
  can_create_albums tinyint(4) NOT NULL default '0',
  pub_upl_need_approval tinyint(4) NOT NULL default '1',
  priv_upl_need_approval tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (group_id)
) ENGINE=MyISAM";

$sql[] = "INSERT INTO ".$table_prefix."usergroups VALUES (1, 'Administrators', 0, 1, 1, 1, 1, 1, 1, 0, 0)";
$sql[] = "INSERT INTO ".$table_prefix."usergroups VALUES (2, 'Registered', 1024, 0, 1, 1, 1, 1, 1, 1, 0)";
$sql[] = "INSERT INTO ".$table_prefix."usergroups VALUES (3, 'Anonymous', 0, 0, 1, 0, 0, 0, 0, 1, 1)";
$sql[] = "INSERT INTO ".$table_prefix."usergroups VALUES (4, 'Banned', 0, 0, 0, 0, 0, 0, 0, 1, 1)";

$sql[] = "CREATE TABLE ".$table_prefix."votes (
  pic_id mediumint(9) NOT NULL default '0',
  user_md5_id varchar(32) NOT NULL default '',
  vote_time int(11) NOT NULL default '0',
  PRIMARY KEY  (pic_id,user_md5_id)
) ENGINE=MyISAM";
