<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /public_html/install/sql/tables/core.php,v $
	$Revision: 1.29 $
	$Author: nanocaiordo $
	$Date: 2007/08/19 09:13:43 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$table_ids['admin'] = 'admin_id';
$tables['admins'] = array(
	'admin_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'aid' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'email' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'pwd' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'counter' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'radminsuper' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radminnews' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radmintopics' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radminmembers' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radminsurveys' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radminhistory' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radminnewsletter' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radminforums' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'radmingroups' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0)
);
// upgrade || new install
if (isset($tablelist['cpg_installs']) || file_exists(BASEDIR.'install/sql/tables/coppermine.php')) {
	$tables['admins']['radmincoppermine'] = array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0);
}
$indexes['admins'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'admin_id', 'Sub_part' => '', 'Null' => 0)),
	'aid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'aid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['banner'] = 'bid';
$tables['banner'] = array(
	'bid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'cid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'imptotal' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'impmade' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'clicks' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'imageurl' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'clickurl' => array('Type' => 'VARCHAR(200)', 'Null' => 0, 'Default' => ''),
	'alttext' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'date' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'dateend' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'active' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'textban' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'text_width' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'text_height' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'text_title' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'text_bg' => array('Type' => 'VARCHAR(6)', 'Null' => 0, 'Default' => 'FFFFFF'),
	'text_clr' => array('Type' => 'VARCHAR(6)', 'Null' => 0, 'Default' => '000000'),
);
$indexes['banner'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'bid', 'Sub_part' => '', 'Null' => 0)),
	'cid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'cid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['blocks'] = 'bid';
$tables['blocks'] = array(
	'bid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'bkey' => array('Type' => 'VARCHAR(15)', 'Null' => 0, 'Default' => ''),
	'title' => array('Type' => 'VARCHAR(60)', 'Null' => 0, 'Default' => ''),
	'content' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'url' => array('Type' => 'VARCHAR(200)', 'Null' => 0, 'Default' => ''),
	'bposition' => array('Type' => 'CHAR(1)', 'Null' => 0, 'Default' => ''),
	'weight' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 1),
	'active' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'refresh' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'time' => array('Type' => 'VARCHAR(14)', 'Null' => 0, 'Default' => 0),
	'blanguage' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'blockfile' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'view' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'in_module' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
);
$indexes['blocks'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'bid', 'Sub_part' => '', 'Null' => 0)),
	'title' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'title', 'Sub_part' => '', 'Null' => 0))
);

#$table_ids['blocks'][0] = 'bid';
#$table_ids['blocks'][1] = 'mid';
$tables['blocks_custom'] = array(
	'bid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'mid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'side' => array('Type' => 'CHAR(1)', 'Null' => 0, 'Default' => 'l'),
	'weight' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 1),
);
$indexes['blocks_custom'] = array(
	'bid' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'bid', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'mid', 'Sub_part' => '', 'Null' => 0),
		2 => array('name' => 'weight', 'Sub_part' => '', 'Null' => 0))
);

$tables['config_custom'] = array(
	'cfg_name' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'cfg_field' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'cfg_value' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
);
$indexes['config_custom'] = array(
	'unique_cfg' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'cfg_name', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'cfg_field', 'Sub_part' => '', 'Null' => 0))
);

$tables['counter'] = array(
	'type' => array('Type' => 'VARCHAR(80)', 'Null' => 0, 'Default' => ''),
	'var' => array('Type' => 'VARCHAR(80)', 'Null' => 0, 'Default' => ''),
	'count' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
);
$indexes['counter'] = array(
	'agent' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'type', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'var', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['credits'] = 'cid';
$tables['credits'] = array(
	'cid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'modname' => array('Type' => 'VARCHAR(25)', 'Null' => 0, 'Default' => ''),
	'description' => array('Type' => 'TEXT', 'Null' => 1),
	'author' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'url' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
);
$indexes['credits'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'cid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['headlines'] = 'hid';
$tables['headlines'] = array(
	'hid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'sitename' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'headlinesurl' => array('Type' => 'VARCHAR(200)', 'Null' => 0, 'Default' => ''),
);
$indexes['headlines'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'hid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['history'] = 'eid';
$tables['history'] = array(
	'eid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'did' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'mid' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'yid' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'content' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'language' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
);
$indexes['history'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'eid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['log'] = 'log_id';
$tables['log'] = array(
	'log_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'log_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'log_type' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'log_mod_id' => array('Type' => DBFT_INT4, 'Null' => 1, 'Default' => 0),
	'log_user_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'log_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'log_uri' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'log_msg' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
);
$indexes['log'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'log_id', 'Sub_part' => '', 'Null' => 0)),
	'type' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'log_type', 'Sub_part' => '', 'Null' => 0)),
	'types' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'log_mod_id', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'log_type', 'Sub_part' => '', 'Null' => 0),
		2 => array('name' => 'log_uri', 'Sub_part' => '', 'Null' => 0)),
	'mod_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'log_mod_id', 'Sub_part' => '', 'Null' => 0)),
	'time' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'log_time', 'Sub_part' => '', 'Null' => 0)),
	'error' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'log_ip', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'log_mod_id', 'Sub_part' => '', 'Null' => 0),
		2 => array('name' => 'log_type', 'Sub_part' => '', 'Null' => 0),
		3 => array('name' => 'log_uri', 'Sub_part' => '', 'Null' => 0),
		4 => array('name' => 'log_user_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['message'] = 'mid';
$tables['message'] = array(
	'mid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'title' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'content' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'date' => array('Type' => 'VARCHAR(14)', 'Null' => 0, 'Default' => ''),
	'expire' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'active' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'view' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'mlanguage' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
);
$indexes['message'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'mid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['modules'] = 'mid';
$tables['modules'] = array(
	'mid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'title' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'custom_title' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'version' => array('Type' => 'VARCHAR(10)', 'Null' => 1, 'Default' => ''),
	'active' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'view' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'inmenu' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'pos' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'uninstall' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'cat_id' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'blocks' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
);
$indexes['modules'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'mid', 'Sub_part' => '', 'Null' => 0)),
	'title' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'title', 'Sub_part' => '', 'Null' => 0)),
	'custom_title' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'custom_title', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['modules_cat'] = 'cid';
$tables['modules_cat'] = array(
	'cid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'name' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'image' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'pos' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'link_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'link' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
);
$indexes['modules_cat'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'cid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['modules_links'] = 'lid';
$tables['modules_links'] = array(
	'lid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'title' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'link_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'link' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'active' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'view' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'pos' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'cat_id' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
);
$indexes['modules_links'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'lid', 'Sub_part' => '', 'Null' => 0))
);

$tables['referer'] = array(
	'url' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'lasttime' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
);
$indexes['referer'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'url', 'Sub_part' => '', 'Null' => 0)),
	'time' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'lasttime', 'Sub_part' => '', 'Null' => 0))
);

$tables['security'] = array(
	'ban_ipv4_s' => array('Type' => DBFT_INT4, 'Null' => 1),
	'ban_ipv4_e' => array('Type' => DBFT_INT4, 'Null' => 1),
	'ban_ipn' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'ban_string' => array('Type' => 'VARCHAR(255)', 'Null' => 1),
	'ban_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'ban_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'ban_details' => array('Type' => 'TEXT', 'Null' => 1),
	'log' => array('Type' => 'TEXT', 'Null' => 1)
);
$indexes['security'] = array(
	'ip' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'ban_ipv4_s', 'Sub_part' => '', 'Null' => 0)),
	'ips' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'ban_ipv4_e', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'ban_ipv4_s', 'Sub_part' => '', 'Null' => 0)),
	'string' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'ban_string', 'Sub_part' => '', 'Null' => 0)),
	'level' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'ban_type', 'Sub_part' => '', 'Null' => 0))
);

$tables['security_agents'] = array(
	'agent_name' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'agent_fullname' => array('Type' => 'VARCHAR(30)', 'Null' => 1),
	'agent_hostname' => array('Type' => 'VARCHAR(30)', 'Null' => 1),
	'agent_url' => array('Type' => 'VARCHAR(60)', 'Null' => 1),
	'agent_ban' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'agent_desc' => array('Type' => 'TEXT', 'Null' => 1)
);
$indexes['security_agents'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'agent_name', 'Sub_part' => '', 'Null' => 0))
);

$tables['security_flood'] = array(
	'flood_ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'flood_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => '0'),
	'flood_count' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => '0'),
	'log' => array('Type' => 'TEXT', 'Null' => 1)
);
$indexes['security_flood'] = array(
	'ip' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'flood_ip', 'Sub_part' => '', 'Null' => 0))
);

$tables['session'] = array(
	'uname' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'host_addr' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'guest' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'module' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'url' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
);
$indexes['session'] = array(
	'time' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'time', 'Sub_part' => '', 'Null' => 0)),
	'guest' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'guest', 'Sub_part' => '', 'Null' => 0))
);

$tables['stats_hour'] = array(
	'year' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'month' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'date' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'hour' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'hits' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
);
$indexes['stats_hour'] = array(
	'full_date' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'date', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'hour', 'Sub_part' => '', 'Null' => 0),
		2 => array('name' => 'month', 'Sub_part' => '', 'Null' => 0),
		3 => array('name' => 'year', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['users'] = 'user_id';
$tables['users'] = array(
	'user_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'name' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'username' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'user_email' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'femail' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'user_website' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'user_avatar' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'user_regdate' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_icq' => array('Type' => 'VARCHAR(15)', 'Null' => 1, 'Default' => ''),
	'user_occ' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'user_from' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'user_interests' => array('Type' => 'VARCHAR(150)', 'Null' => 1, 'Default' => ''),
	'user_sig' => array('Type' => 'TEXT', 'Null' => 1),
	'user_viewemail' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_aim' => array('Type' => 'VARCHAR(35)', 'Null' => 1, 'Default' => ''),
	'user_yim' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'user_skype' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'user_msnm' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'user_password' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'storynum' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 10),
	'umode' => array('Type' => 'VARCHAR(10)', 'Null' => 1, 'Default' => ''),
	'uorder' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'thold' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'noscore' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'bio' => array('Type' => 'TEXT', 'Null' => 1),
	'ublockon' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'ublock' => array('Type' => 'TEXT', 'Null' => 1),
	'theme' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'commentmax' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 4096),
	'counter' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'newsletter' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'user_posts' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_attachsig' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'user_rank' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_level' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'user_active' => array('Type' => DBFT_INT1, 'Null' => 1, 'Default' => 1),
	'user_session_time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_lastvisit' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_timezone' => array('Type' => 'VARCHAR(6)', 'Null' => 0, 'Default' => 0),
	'user_dst' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'user_style' => array('Type' => DBFT_INT1, 'Null' => 1, 'Default' => 0),
	'user_lang' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => 'english'),
	'user_dateformat' => array('Type' => 'VARCHAR(14)', 'Null' => 0, 'Default' => 'D M d, Y g:i a'),
	'user_new_privmsg' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'user_unread_privmsg' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'user_last_privmsg' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'user_emailtime' => array('Type' => DBFT_INT4, 'Null' => 1, 'Default' => 0),
	'user_allowhtml' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 1),
	'user_allowbbcode' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 1),
	'user_allowsmile' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 1),
	'user_allowavatar' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'user_allow_pm' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'user_allow_viewonline' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'user_notify' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_notify_pm' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_popup_pm' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_avatar_type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 3),
	'user_actkey' => array('Type' => 'VARCHAR(32)', 'Null' => 1, 'Default' => ''),
	'user_newpasswd' => array('Type' => 'VARCHAR(32)', 'Null' => 1, 'Default' => ''),
	'user_group_cp' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 2),
	'user_group_list_cp' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => '2'),
	'user_active_cp' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'susdel_reason' => array('Type' => 'TEXT', 'Null' => 1),
);
$indexes['users'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'user_id', 'Sub_part' => '', 'Null' => 0)),
	'uname' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'username', 'Sub_part' => '', 'Null' => 0)),
	'user_session_time' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_session_time', 'Sub_part' => '', 'Null' => 0)),
	'user_regdate' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_regdate', 'Sub_part' => '', 'Null' => 0)),
	'user_email' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_email', 'Sub_part' => '', 'Null' => 0)),
	'user_id_level' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_id', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'user_level', 'Sub_part' => '', 'Null' => 0)),
	'user_active_level' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'user_active', 'Sub_part' => '', 'Null' => 0),
		1 => array('name' => 'user_level', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['users_fields'] = 'users_fields';
$tables['users_fields'] = array(
	'fid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'field' => array('Type' => 'VARCHAR(25)', 'Null' => 0, 'Default' => ''),
	'section' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'visible' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'type' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'size' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 10),
	'langdef' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
);
$indexes['users_fields'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'fid', 'Sub_part' => '', 'Null' => 0)),
	'section' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'section', 'Sub_part' => '', 'Null' => 0)),
	'visible' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'visible', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['users_temp'] = 'user_id';
$tables['users_temp'] = array(
	'user_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'username' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'user_email' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => ''),
	'user_password' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'user_regdate' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'check_num' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'time' => array('Type' => 'VARCHAR(14)', 'Null' => 0, 'Default' => ''),
	'user_website' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'name' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'femail' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'theme' => array('Type' => 'VARCHAR(255)', 'Null' => 1, 'Default' => ''),
	'user_icq' => array('Type' => 'VARCHAR(15)', 'Null' => 1, 'Default' => ''),
	'user_aim' => array('Type' => 'VARCHAR(35)', 'Null' => 1, 'Default' => ''),
	'user_yim' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'user_skype' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'user_msnm' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'user_from' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'user_occ' => array('Type' => 'VARCHAR(100)', 'Null' => 1, 'Default' => ''),
	'user_interests' => array('Type' => 'VARCHAR(150)', 'Null' => 1, 'Default' => ''),
	'user_sig' => array('Type' => 'TEXT', 'Null' => 1),
	'bio' => array('Type' => 'TEXT', 'Null' => 1),
	'user_timezone' => array('Type' => 'VARCHAR(6)', 'Null' => 0, 'Default' => 0),
	'user_dst' => array('Type' => DBFT_INT2, 'Null' => 0, 'Default' => 0),
	'user_dateformat' => array('Type' => 'VARCHAR(14)', 'Null' => 0, 'Default' => 'D M d, Y g:i a'),
	'newsletter' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_viewemail' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_allow_viewonline' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 1),
	'user_attachsig' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_allowhtml' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 1),
	'user_allowbbcode' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 1),
	'user_allowsmile' => array('Type' => DBFT_BOOL, 'Null' => 1, 'Default' => 1),
	'user_notify' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_notify_pm' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_popup_pm' => array('Type' => DBFT_BOOL, 'Null' => 0, 'Default' => 0),
	'user_lang' => array('Type' => 'VARCHAR(255)', 'Null' => 0, 'Default' => 'english')
);
$indexes['users_temp'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'user_id', 'Sub_part' => '', 'Null' => 0))
);
