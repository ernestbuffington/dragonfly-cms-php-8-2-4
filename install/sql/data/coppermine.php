<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2006 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /cvs/html/install/sql/data/coppermine.php,v $
	$Revision: 1.2 $
	$Author: djmaze $
	$Date: 2006/01/26 12:23:59 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$records['cpg_installs']['compare'] = DF_DATA_CHECK_ONLY;
$records['cpg_installs']['content'] = array(
	array('coppermine', $prefix.'_cpg_', '1.3.1')
);

$records['cpg_categories']['compare'] = DF_DATA_CHECK_ONLY;
$records['cpg_categories']['content'] = array(
	array(0, 'User galleries', 'This category contains albums that belong to Coppermine users.', 0, 0, 0, 0, 0, 0)
);

$records['cpg_config']['compare'] = DF_DATA_EXIST_LEVEL1;
$records['cpg_config']['query'] = 'name';
$records['cpg_config']['content'] = array(
	'albums_per_page' => 12,
	'album_list_cols' => 2,
	'display_pic_info' => 1,
	'alb_list_thumb_size' => 50,
	'allowed_file_extensions' => "'GIF/PNG/JPG/JPEG/TIF/TIFF'",
	'allowed_img_types' => "'JPG/GIF/PNG/TIFF'",
	'allow_private_albums' => 1,
	'allow_user_registration' => 0,
	'allow_duplicate_emails_addr' => 0,
	'caption_in_thumbview' => 1,
	'charset' => "'language file'",
	'cookie_name' => "'nuke_cpg_nuke'",
	'cookie_path' => "'/'",
	'debug_mode' => 1,
	'default_sort_order' => "'na'",
	'ecards_more_pic_target' => "'http://www.localhost.com/'",
	'enable_smilies' => 1,
	'filter_bad_words' => 0,
	'forbiden_fname_char' => "'$/\\:*?\"\\'<>|`'",
	'fullpath' => "'modules/coppermine/albums/'",
	'gallery_admin_email' => "'you@somewhere.com'",
	'gallery_description' => "'Your online photo album'",
	'gallery_name' => "'Coppermine Photo Gallery'",
	'im_options' => "'-antialias'",
	'impath' => "''",
	'jpeg_qual' => 80,
	'keep_votes_time' => 30,
	'lang' => "'english'",
	'main_page_layout' => "'breadcrumb/catlist/alblist/lastalb,1/lastup,1/lastcom,1/topn,1/toprated,1/random,1/anycontent/favpics,3'",
	'main_table_width' => "'100%'",
	'make_intermediate' => 1,
	'max_com_lines' => 10,
	'max_com_size' => 512,
	'max_com_wlength' => 38,
	'max_img_desc_length' => 512,
	'max_tabs' => 12,
	'max_upl_size' => 1024,
	'max_upl_width_height' => 2048,
	'min_votes_for_rating' => 1,
	'normal_pfx' => "'normal_'",
	'picture_table_width' => 600,
	'picture_width' => 400,
	'randpos_interval' => 5,
	'read_exif_data' => 0,
	'reg_requires_valid_email' => 1,
	'subcat_level' => 2,
	'theme' => "'default'",
	'thumbcols' => 4,
	'thumbrows' => 3,
	'thumb_method' => "'gd2'",
	'thumb_pfx' => "'thumb_'",
	'thumb_width' => 100,
	'userpics' => "'modules/coppermine/albums/userpics/'",
	'user_field1_name' => "''",
	'user_field2_name' => "''",
	'user_field3_name' => "''",
	'user_field4_name' => "''",
	'display_comment_count' => 0,
	'display_film_strip' => 1,
	'max_film_strip_items' => 5,
	'samename' => 0,
	'first_level' => 1,
	'show_private' => 0,
	'thumb_use' => "'ht'",
	'comment_email_notification' => 0,
	'disable_flood_protection' => 0,
	'nice_titles' => 1,
	'seo_alts' => 0,
	'read_iptc_data' => 0,
	'picinfo_display_favorites' => 1,
	'picinfo_display_filename' => 0,
	'picinfo_display_album_name' => 1,
	'picinfo_display_file_size' => 0,
	'picinfo_display_dimensions' => 0,
	'picinfo_display_count_displayed' => 0,
	'picinfo_display_URL' => 0,
	'picinfo_display_URL_bookmark' => 1,
	'allow_anon_fullsize' => 1,
	'right_blocks' => 0,
	'avatar_private_album' => 0,
	/*'avatar_private_album' => 0,*/
	'watermark' => 0,
	'fullsize_slideshow' => 0
);

$records['cpg_usergroups']['compare'] = DF_DATA_CHECK_ONLY;
$records['cpg_usergroups']['content'] = array(
	array('Administrators', 0, 1, 1, 1, 1, 1, 1, 0, 0),
	array('Registered', 1024, 0, 1, 1, 1, 1, 1, 1, 0),
	array('Anonymous', 0, 0, 1, 0, 0, 0, 0, 1, 1),
	array('Banned', 0, 0, 0, 0, 0, 0, 0, 1, 1)
);
