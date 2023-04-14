<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /public_html/install/sql/tables/forums.php,v $
	$Revision: 1.22 $
	$Author: nanocaiordo $
	$Date: 2007/08/19 09:13:43 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$table_ids['bbattach_quota'] = 'user_id';
$tables['bbattach_quota'] = array(
	'user_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'group_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'quota_type' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'quota_limit_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0)
);
$indexes['bbattach_quota'] = array(
	'quota_type' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'quota_type', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbattachments'] = 'attach_id';
$tables['bbattachments'] = array(
	'attach_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'post_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'privmsgs_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'user_id_1' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'user_id_2' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0)
);
$indexes['bbattachments'] = array(
	'attach_id_post_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'attach_id', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'post_id', 'Sub_part' => '', 'Null' => 0)),
	'attach_id_privmsgs_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'attach_id', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'privmsgs_id', 'Sub_part' => '', 'Null' => 0))
);

$tables['bbattachments_config'] = array(
	'config_name' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'config_value' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => '')
);
$indexes['bbattachments_config'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'config_name', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbattachments_desc'] = 'attach_id';
$tables['bbattachments_desc'] = array(
	'attach_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'physical_filename' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'real_filename' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'download_count' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'comment' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'extension' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'mimetype' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'filesize' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'filetime' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'thumbnail' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
$indexes['bbattachments_desc'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'attach_id', 'Sub_part' => '', 'Null' => 0)),
	'filetime' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'filetime', 'Sub_part' => '', 'Null' => 0)),
	'physical_filename' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'physical_filename', 'Sub_part' => '', 'Null' => 0)),
	'filesize' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'filesize', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbauth_access'] = 'group_id';
$tables['bbauth_access'] = array(
	'group_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'forum_id' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'auth_view' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_read' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_post' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_reply' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_edit' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_delete' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_sticky' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_announce' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_vote' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_pollcreate' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_attachments' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_mod' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_download' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0)
);
$indexes['bbauth_access'] = array(
	'group_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'group_id', 'Sub_part' => '', 'Null' => 0)),
	'forum_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbcategories'] = 'cat_id';
$tables['bbcategories'] = array(
	'cat_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'cat_title' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'cat_order' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0)
);
$indexes['bbcategories'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'cat_id', 'Sub_part' => '', 'Null' => 0)),
	'cat_order' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'cat_order', 'Sub_part' => '', 'Null' => 0))
);

$tables['bbconfig'] = array(
	'config_name' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'config_value' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => '')
);
$indexes['bbconfig'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'config_name', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbdisallow'] = 'disallow_id';
$tables['bbdisallow'] = array(
	'disallow_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'disallow_username' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => '')
);
$indexes['bbdisallow'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'disallow_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbextension_groups'] = 'group_id';
$tables['bbextension_groups'] = array(
	'group_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'group_name' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'cat_id' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'allow_group' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'download_mode' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'upload_icon' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'max_filesize' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'forum_permissions' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => '')
);
$indexes['bbextension_groups'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'group_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbextensions'] = 'ext_id';
$tables['bbextensions'] = array(
	'ext_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'group_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'extension' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'comment' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => '')
);
$indexes['bbextensions'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'ext_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbforbidden_extensions'] = 'ext_id';
$tables['bbforbidden_extensions'] = array(
	'ext_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'extension' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => '')
);
$indexes['bbforbidden_extensions'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'ext_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbforum_prune'] = 'prune_id';
$tables['bbforum_prune'] = array(
	'prune_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'forum_id' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'prune_days' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'prune_freq' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0)
);
$indexes['bbforum_prune'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'prune_id', 'Sub_part' => '', 'Null' => 0)),
	'forum_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbforums'] = 'forum_id';
$tables['bbforums'] = array(
	'forum_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'cat_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'parent_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'forum_name' => array('Type' => 'VARCHAR(150)', 'Null' => 1, 'Default' => ''),
	'forum_desc' => array('Type' => 'TEXT', 'Null' => 1),
	'forum_status' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'forum_order' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 1),
	'forum_posts' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'forum_topics' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'forum_last_post_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'forum_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'forum_link' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'prune_next' => array('Type' => DBFT_INT4, 'Null' => 1, 'Default' => 0),
	'prune_enable' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'auth_view' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_read' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_post' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_reply' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_edit' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_delete' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_sticky' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_announce' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_vote' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_pollcreate' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_attachments' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'auth_download' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0)
);
$indexes['bbforums'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'forum_id', 'Sub_part' => '', 'Null' => 0)),
	'forums_order' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_order', 'Sub_part' => '', 'Null' => 0)),
	'cat_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'cat_id', 'Sub_part' => '', 'Null' => 0)),
	'forum_last_post_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_last_post_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbgroups'] = 'group_id';
$tables['bbgroups'] = array(
	'group_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'group_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'group_name' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'group_description' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'group_moderator' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'group_single_user' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1)
);
$indexes['bbgroups'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'group_id', 'Sub_part' => '', 'Null' => 0)),
	'group_single_user' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'group_single_user', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbposts'] = 'post_id';
$tables['bbposts'] = array(
	'post_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'topic_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'forum_id' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'poster_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'post_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'poster_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'post_username' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'enable_bbcode' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'enable_html' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'enable_smilies' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'enable_sig' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'post_edit_time' => array('Type' => DBFT_INT4, 'Null' => 1, 'Default' => 0),
	'post_edit_count' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'post_attachment' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
$indexes['bbposts'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'post_id', 'Sub_part' => '', 'Null' => 0)),
	'forum_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_id', 'Sub_part' => '', 'Null' => 0)),
	'topic_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_id', 'Sub_part' => '', 'Null' => 0)),
	'poster_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'poster_id', 'Sub_part' => '', 'Null' => 0)),
	'post_time' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'post_time', 'Sub_part' => '', 'Null' => 0)),
	'topic_n_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'post_id', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'topic_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbposts_text'] = 'post_id';
$tables['bbposts_text'] = array(
	'post_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'post_subject' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'post_text' => array('Type' => 'TEXT', 'Null' => 1)
);
$indexes['bbposts_text'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'post_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbprivmsgs'] = 'privmsgs_id';
$tables['bbprivmsgs'] = array(
	'privmsgs_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'privmsgs_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'privmsgs_subject' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'privmsgs_from_userid' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'privmsgs_to_userid' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'privmsgs_date' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'privmsgs_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'privmsgs_enable_bbcode' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'privmsgs_enable_html' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'privmsgs_enable_smilies' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'privmsgs_attach_sig' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'privmsgs_attachment' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
$indexes['bbprivmsgs'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'privmsgs_id', 'Sub_part' => '', 'Null' => 0)),
	'privmsgs_from_userid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'privmsgs_from_userid', 'Sub_part' => '', 'Null' => 0)),
	'privmsgs_to_userid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'privmsgs_to_userid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbprivmsgs_text'] = 'privmsgs_text_id';
$tables['bbprivmsgs_text'] = array(
	'privmsgs_text_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'privmsgs_text' => array('Type' => 'TEXT', 'Null' => 1)
);
$indexes['bbprivmsgs_text'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'privmsgs_text_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbquota_limits'] = 'quota_limit_id';
$tables['bbquota_limits'] = array(
	'quota_limit_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'quota_desc' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'quota_limit' => array('Type' => 'INT8', 'Null' => 0, 'Default' => 0)
);
$indexes['bbquota_limits'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'quota_limit_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbranks'] = 'rank_id';
$tables['bbranks'] = array(
	'rank_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'rank_title' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'rank_min' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'rank_max' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'rank_special' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 0),
	'rank_image' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => '')
);
$indexes['bbranks'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'rank_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbsearch_wordlist'] = 'word_id';
$tables['bbsearch_wordlist'] = array(
	'word_text' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'word_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'word_common' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
$indexes['bbsearch_wordlist'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'word_id', 'Sub_part' => '', 'Null' => 0)),
	'word_text' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'word_text', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbsearch_wordmatch'] = 'post_id';
$tables['bbsearch_wordmatch'] = array(
	'post_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'word_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'title_match' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
$indexes['bbsearch_wordmatch'] = array(
	'word_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'word_id', 'Sub_part' => '', 'Null' => 0)),
	'post_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'post_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbsmilies'] = 'smilies_id';
$tables['bbsmilies'] = array(
	'smilies_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'code' => array('Type' => 'VARCHAR(50)', 'Null' => 1, 'Default' => ''),
	'smile_url' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'emoticon' => array('Type' => 'VARCHAR(75)', 'Null' => 1, 'Default' => ''),
	'pos' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0)
);
$indexes['bbsmilies'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'smilies_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbthemes'] = 'themes_id';
$tables['bbthemes'] = array(
	'themes_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'template_name' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'style_name' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'head_stylesheet' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'body_background' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'body_bgcolor' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'body_text' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'body_link' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'body_vlink' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'body_alink' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'body_hlink' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'tr_color1' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'tr_color2' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'tr_color3' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'tr_class1' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'tr_class2' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'tr_class3' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'th_color1' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'th_color2' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'th_color3' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'th_class1' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'th_class2' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'th_class3' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'td_color1' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'td_color2' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'td_color3' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'td_class1' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'td_class2' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'td_class3' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'fontface1' => array('Type' => 'VARCHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontface2' => array('Type' => 'VARCHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontface3' => array('Type' => 'VARCHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontsize1' => array('Type' => DBFT_INT1, 'Null' => 1, 'Default' => 0),
	'fontsize2' => array('Type' => DBFT_INT1, 'Null' => 1, 'Default' => 0),
	'fontsize3' => array('Type' => DBFT_INT1, 'Null' => 1, 'Default' => 0),
	'fontcolor1' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'fontcolor2' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'fontcolor3' => array('Type' => 'VARCHAR(6)', 'Null' => 1, 'Default' => ''),
	'span_class1' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'span_class2' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'span_class3' => array('Type' => 'VARCHAR(25)', 'Null' => 1, 'Default' => ''),
	'img_size_poll' => array('Type' => DBFT_INT2, 'Null' => 1, 'Default' => 0),
	'img_size_privmsg' => array('Type' => DBFT_INT2, 'Null' => 1, 'Default' => 0)
);
$indexes['bbthemes'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'themes_id', 'Sub_part' => '', 'Null' => 0))
);

$tables['bbthemes_name'] = array(
	'themes_id' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'tr_color1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'tr_color2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'tr_color3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'tr_class1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'tr_class2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'tr_class3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'th_color1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'th_color2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'th_color3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'th_class1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'th_class2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'th_class3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'td_color1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'td_color2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'td_color3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'td_class1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'td_class2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'td_class3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontface1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontface2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontface3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontsize1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontsize2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontsize3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontcolor1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontcolor2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'fontcolor3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'span_class1_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'span_class2_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => ''),
	'span_class3_name' => array('Type' => 'CHAR(50)', 'Null' => 1, 'Default' => '')
);
$indexes['bbthemes_name'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'themes_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbtopic_icons'] = 'icon_id';
$tables['bbtopic_icons'] = array(
	'icon_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'forum_id' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'icon_url' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'icon_name' => array('Type' => 'VARCHAR(25)', 'Null' => 0, 'Default' => '')
);
$indexes['bbtopic_icons'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'icon_id', 'Sub_part' => '', 'Null' => 0)),
	'forum_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbtopics'] = 'topic_id';
$tables['bbtopics'] = array(
	'topic_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'forum_id' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'topic_title' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'topic_poster' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'topic_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'topic_views' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'topic_replies' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'topic_status' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'topic_vote' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'topic_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'topic_last_post_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'topic_first_post_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'topic_moved_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'topic_attachment' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'icon_id' => array('Type' => DBFT_INT4, 'Null' => 1, 'Default' => 0)
);
$indexes['bbtopics'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'topic_id', 'Sub_part' => '', 'Null' => 0)),
	'forum_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'forum_id', 'Sub_part' => '', 'Null' => 0)),
	'topic_moved_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_moved_id', 'Sub_part' => '', 'Null' => 0)),
	'topic_status' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_status', 'Sub_part' => '', 'Null' => 0)),
	'topic_last_post_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_last_post_id', 'Sub_part' => '', 'Null' => 0)),
	'topic_first_post_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_first_post_id', 'Sub_part' => '', 'Null' => 0)),
	'topic_type' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_type', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbtopics_watch'] = 'topic_id';
$tables['bbtopics_watch'] = array(
	'topic_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'user_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'notify_status' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
$indexes['bbtopics_watch'] = array(
	'topic_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_id', 'Sub_part' => '', 'Null' => 0)),
	'user_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_id', 'Sub_part' => '', 'Null' => 0)),
	'notify_status' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'notify_status', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbuser_group'] = 'group_id';
$tables['bbuser_group'] = array(
	'group_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'user_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'user_pending' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 0)
);
$indexes['bbuser_group'] = array(
	'group_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'group_id', 'Sub_part' => '', 'Null' => 0)),
	'user_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbvote_desc'] = 'vote_id';
$tables['bbvote_desc'] = array(
	'vote_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'topic_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'vote_text' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'vote_start' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'vote_length' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0)
);
$indexes['bbvote_desc'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'vote_id', 'Sub_part' => '', 'Null' => 0)),
	'topic_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbvote_results'] = 'vote_id';
$tables['bbvote_results'] = array(
	'vote_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'vote_option_id' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'vote_option_text' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'vote_result' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0)
);
$indexes['bbvote_results'] = array(
	'vote_option_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'vote_option_id', 'Sub_part' => '', 'Null' => 0)),
	'vote_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'vote_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbvote_voters'] = 'vote_id';
$tables['bbvote_voters'] = array(
	'vote_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'vote_user_id' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'vote_user_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
);
$indexes['bbvote_voters'] = array(
	'vote_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'vote_id', 'Sub_part' => '', 'Null' => 0)),
	'vote_user_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'vote_user_id', 'Sub_part' => '', 'Null' => 0)),
	'vote_user_ip' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'vote_user_ip', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['bbwords'] = 'word_id';
$tables['bbwords'] = array(
	'word_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'word' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'replacement' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
);
$indexes['bbwords'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'word_id', 'Sub_part' => '', 'Null' => 0)),
);
