<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /cvs/html/install/sql/tables/coppermine.php,v $
	$Revision: 1.17 $
	$Author: nanocaiordo $
	$Date: 2007/08/19 09:13:43 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$table_ids['cpg_albums'] = 'aid';
$tables['cpg_albums'] = array(
	'aid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'title' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'description' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'visibility' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'uploads' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'comments' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'votes' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'pos' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'category' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'pic_count' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'thumb' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'last_addition' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'stat_uptodate' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1)
);
$indexes['cpg_albums'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'aid', 'Sub_part' => '', 'Null' => 0)),
	'alb_category' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'category', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['cpg_categories'] = 'cid';
$tables['cpg_categories'] = array(
	'cid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'owner_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'catname' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'description' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'pos' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'parent' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'subcat_count' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'alb_count' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'pic_count' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'stat_uptodate' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1)
);
$indexes['cpg_categories'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'cid', 'Sub_part' => '', 'Null' => 0)),
	'cat_parent' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'parent', 'Sub_part' => '', 'Null' => 0)),
	'cat_pos' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'pos', 'Sub_part' => '', 'Null' => 0)),
	'cat_owner_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'owner_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['cpg_comments'] = 'msg_id';
$tables['cpg_comments'] = array(
	'pid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'msg_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'msg_author' => array('Type' => 'VARCHAR(25)', 'Null' => 0, 'Default' => ''),
	'msg_body' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'msg_date' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'author_md5_id' => array('Type' => 'VARCHAR(32)', 'Null' => 0, 'Default' => ''),
	'author_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'msg_raw_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'msg_hdr_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1)
);
$indexes['cpg_comments'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'msg_id', 'Sub_part' => '', 'Null' => 0)),
	'com_pic_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'pid', 'Sub_part' => '', 'Null' => 0))
);

$tables['cpg_config'] = array(
	'name' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'value' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => '')
);
$indexes['cpg_config'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'name', 'Sub_part' => '', 'Null' => 0))
);

$tables['cpg_exif'] = array(
	'filename' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'exif_data' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => '')
);
$indexes['cpg_exif'] = array(
	'filename' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'filename', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['cpg_installs'] = 'cpg_id';
$tables['cpg_installs'] = array(
	'cpg_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'dirname' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'prefix' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'version' => array('Type' => 'VARCHAR(10)', 'Null' => 1, 'Default' => '')
);
$indexes['cpg_installs'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'cpg_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['cpg_pictures'] = 'pid';
$tables['cpg_pictures'] = array(
	'pid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'aid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'filepath' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'filename' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'filesize' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'total_filesize' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'pwidth' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'pheight' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'hits' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'mtime' => array('Type' => DBFT_INT4, 'Null' => 1),
	'ctime' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'owner_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'owner_name' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'pic_rating' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'votes' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'title' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'caption' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'keywords' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'approved' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'user1' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'user2' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'user3' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'user4' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'url_prefix' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'randpos' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'pic_raw_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'pic_hdr_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1)
);
$indexes['cpg_pictures'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'pid', 'Sub_part' => '', 'Null' => 0)),
	'pic_hits' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'hits', 'Sub_part' => '', 'Null' => 0)),
	'pic_rate' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'pic_rating', 'Sub_part' => '', 'Null' => 0)),
	'aid_approved' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'aid', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'approved', 'Sub_part' => '', 'Null' => 0)),
	'randpos' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'randpos', 'Sub_part' => '', 'Null' => 0)),
	'pic_aid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'aid', 'Sub_part' => '', 'Null' => 0)),
	'search' => array('unique' => 0, 'type' => DBFT_INDEX_FULLTEXT,
		0 => array('name' => 'caption', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'filename', 'Sub_part' => '', 'Null' => 0),
		2 => array('name' => 'keywords', 'Sub_part' => '', 'Null' => 0),
		3 => array('name' => 'title', 'Sub_part' => '', 'Null' => 0),
		4 => array('name' => 'user1', 'Sub_part' => '', 'Null' => 0),
		5 => array('name' => 'user2', 'Sub_part' => '', 'Null' => 0),
		6 => array('name' => 'user3', 'Sub_part' => '', 'Null' => 0),
		7 => array('name' => 'user4', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['cpg_usergroups'] = 'group_id';
$tables['cpg_usergroups'] = array(
	'group_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'group_name' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'group_quota' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'has_admin_access' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'can_rate_pictures' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'can_send_ecards' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'can_post_comments' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'can_upload_pictures' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'can_create_albums' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'pub_upl_need_approval' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'priv_upl_need_approval' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1)
);
$indexes['cpg_usergroups'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'group_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['cpg_votes'] = 'pic_id';
$tables['cpg_votes'] = array(
	'pic_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_md5_id' => array('Type' => 'VARCHAR(32)', 'Null' => 0, 'Default' => ''),
	'vote_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0)
);
$indexes['cpg_votes'] = array(
	'unique_votes' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'pic_id', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'user_md5_id', 'Sub_part' => '', 'Null' => 0))
);
