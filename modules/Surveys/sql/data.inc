<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /public_html/modules/Surveys/sql/data.inc,v $
	$Revision: 1.1 $
	$Author: nanocaiordo $
	$Date: 2007/12/18 10:42:47 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

$table_ids['poll_check'] = 'ip';
$tables['poll_check'] = array(
	'user_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'ip' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'time' => array('Type' => 'VARCHAR(14)', 'Null' => 0, 'Default' => ''),
	'poll_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0)
);

$table_ids['poll_data'] = 'poll_id';
$tables['poll_data'] = array(
	'poll_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'option_text' => array('Type' => 'VARCHAR(50)', 'Null' => 0, 'Default' => ''),
	'option_count' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'vote_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0)
);

$table_ids['poll_desc'] = 'poll_id';
$tables['poll_desc'] = array(
	'poll_id' => array('Type' => 'SERIAL4', 'Null' => 0),
	'poll_title' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'time_stamp' => array('Type' => DBFT_INT4.' UNSIGNED', 'Null' => 0, 'Default' => 0),
	'voters' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'planguage' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'artid' => array('Type' => DBFT_INT4.' UNSIGNED', 'Null' => 0, 'Default' => 0),
	'comments' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'anonymous' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0)
);
$indexes['poll_desc'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'poll_id', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['pollcomments'] = 'tid';
$tables['pollcomments'] = array(
	'tid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'pid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'poll_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'date' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'name' => array('Type' => 'VARCHAR(60)', 'Null' => 0, 'Default' => ''),
	'email' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'url' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'host_name' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'subject' => array('Type' => 'VARCHAR(60)', 'Null' => 0, 'Default' => ''),
	'comment' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'score' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'reason' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0)
);
$indexes['pollcomments'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'tid', 'Sub_part' => '', 'Null' => 0)),
	'pid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'pid', 'Sub_part' => '', 'Null' => 0)),
	'poll_id' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'poll_id', 'Sub_part' => '', 'Null' => 0))
);
