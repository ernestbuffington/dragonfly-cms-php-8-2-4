<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /public_html/install/sql/tables/news.php,v $
	$Revision: 1.13 $
	$Author: nanocaiordo $
	$Date: 2007/08/19 09:13:43 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$table_ids['autonews'] = 'anid';
$tables['autonews'] = array(
	'anid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'catid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'aid' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'title' => array('Type' => 'VARCHAR(80)', 'Null' => 0, 'Default' => ''),
	'time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 1133334197),
	'hometext' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'bodytext' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'topic' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 1),
	'informant' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'notes' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'ihome' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'alanguage' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'acomm' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'associated' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
);
$indexes['autonews'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'anid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['comments'] = 'tid';
$tables['comments'] = array(
	'tid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'pid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'sid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'date' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'name' => array('Type' => 'VARCHAR(60)', 'Null' => 0, 'Default' => ''),
	'email' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'url' => array('Type' => 'VARCHAR(60)', 'Null' => 1, 'Default' => ''),
	'host_name' => array('Type' => DBFT_VARBINARY.'(17)', 'Null' => 1),
	'subject' => array('Type' => 'VARCHAR(85)', 'Null' => 0, 'Default' => ''),
	'comment' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'score' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'reason' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
);
$indexes['comments'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'tid', 'Sub_part' => '', 'Null' => 0)),
	'pid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'pid', 'Sub_part' => '', 'Null' => 0)),
	'sid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'sid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['queue'] = 'qid';
$tables['queue'] = array(
	'qid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'uid' => array('Type' => DBFT_INT3, 'Null' => 0, 'Default' => 0),
	'uname' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'subject' => array('Type' => 'VARCHAR(100)', 'Null' => 0, 'Default' => ''),
	'story' => array('Type' => 'TEXT', 'Null' => 1),
	'storyext' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'timestamp' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 1133334197),
	'topic' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'alanguage' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
);
$indexes['queue'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'qid', 'Sub_part' => '', 'Null' => 0)),
	'uid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'uid', 'Sub_part' => '', 'Null' => 0)),
	'uname' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'uname', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['related'] = 'rid';
$tables['related'] = array(
	'rid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'tid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'name' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'url' => array('Type' => 'VARCHAR(200)', 'Null' => 0, 'Default' => ''),
);
$indexes['related'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'rid', 'Sub_part' => '', 'Null' => 0)),
	'tid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'tid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['stories'] = 'sid';
$tables['stories'] = array(
	'sid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'catid' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'aid' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'title' => array('Type' => 'VARCHAR(80)', 'Null' => 1, 'Default' => ''),
	'time' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 1133334197),
	'hometext' => array('Type' => 'TEXT', 'Null' => 1),
	'bodytext' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'comments' => array('Type' => DBFT_INT4, 'Null' => 1, 'Default' => 0),
	'counter' => array('Type' => DBFT_INT3, 'Null' => 1, 'Default' => 0),
	'topic' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 1),
	'informant' => array('Type' => 'VARCHAR(40)', 'Null' => 0, 'Default' => ''),
	'notes' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'ihome' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'alanguage' => array('Type' => 'VARCHAR(30)', 'Null' => 0, 'Default' => ''),
	'acomm' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'haspoll' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
	'poll_id' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'score' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'ratings' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
	'associated' => array('Type' => 'TEXT', 'Null' => 0, 'Default' => ''),
	'display_order' => array('Type' => DBFT_INT1, 'Null' => 0, 'Default' => 0),
);
$indexes['stories'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'sid', 'Sub_part' => '', 'Null' => 0)),
	'catid' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'catid', 'Sub_part' => '', 'Null' => 0)),
	'counter' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'counter', 'Sub_part' => '', 'Null' => 1)),
	'topic' => array('unique' => 0, 'type' => 'BTREE',
		0 => array('name' => 'topic', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['stories_cat'] = 'catid';
$tables['stories_cat'] = array(
	'catid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'title' => array('Type' => 'VARCHAR(20)', 'Null' => 0, 'Default' => ''),
	'counter' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
);
$indexes['stories_cat'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'catid', 'Sub_part' => '', 'Null' => 0))
);

$table_ids['topics'] = 'topicid';
$tables['topics'] = array(
	'topicid' => array('Type' => 'SERIAL4', 'Null' => 0),
	'topicimage' => array('Type' => 'VARCHAR(20)', 'Null' => 1, 'Default' => ''),
	'topictext' => array('Type' => 'VARCHAR(40)', 'Null' => 1, 'Default' => ''),
	'counter' => array('Type' => DBFT_INT4, 'Null' => 0, 'Default' => 0),
);
$indexes['topics'] = array(
	'PRIMARY' => array('unique' => 1, 'type' => 'BTREE',
		0 => array('name' => 'topicid', 'Sub_part' => '', 'Null' => 0))
);
