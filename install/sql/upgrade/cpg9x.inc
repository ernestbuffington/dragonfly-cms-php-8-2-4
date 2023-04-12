<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/sql/upgrade/cpg9x.inc,v $
  $Revision: 1.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:40 $
**********************************************/
if (!defined('INSTALL')) { exit; }

if (isset($tablelist['config_custom'])) {
	$installer->add_query('UPDATE', 'config_custom', 'cfg_name=\'member\' WHERE cfg_field=\'my_headlines\' AND cfg_name=\'global\'');
	$installer->add_query('UPDATE', 'config_custom', 'cfg_name=\'member\' WHERE cfg_field=\'minpass\' AND cfg_name=\'global\'');
	$installer->add_query('UPDATE', 'config_custom', 'cfg_name=\'member\' WHERE cfg_field=\'user_news\' AND cfg_name=\'global\'');

	$installer->add_query('DELETE', 'config_custom', 'cfg_field=\'servermail\' AND cfg_name=\'global\'');
	$installer->add_query('DELETE', 'config_custom', 'cfg_field=\'ultramode\' AND cfg_name=\'global\'');
	$installer->add_query('DELETE', 'config_custom', 'cfg_field=\'anonymous\' AND cfg_name=\'global\'');
	$installer->add_query('DELETE', 'config_custom', 'cfg_field=\'servermail\' AND cfg_name=\'global\'');
	$installer->add_query('DELETE', 'config_custom', 'cfg_name=\'global\' AND cfg_field=\'locale\'');
}
if (isset($tablelist['credits'])) {
	$installer->add_query('INSERT', 'credits', 'DEFAULT, "User Info Block", "Based on All Info Block by <a href=\"http://www.gnaunited.com\" target=\"_new\">Alex Hession</a>. Major modifications made by <a href=\"http://dragonflycms.org\" target=\"_new\">DJMaze</a>", "Alex Hession", "www.gnaunited.com"');
}
$installer->add_query('UPDATE', 'modules', 'view=view+1 WHERE view>2');
if (isset($tablelist['modules_links'])) {
	$installer->add_query('UPDATE', 'modules_links', 'view=view+1 WHERE view>2');
}

$installer->add_query('UPDATE', 'message', 'view=0 WHERE view=1');
$installer->add_query('UPDATE', 'message', 'view=1 WHERE view=3');
$installer->add_query('UPDATE', 'message', 'view=3 WHERE view=2');
$installer->add_query('UPDATE', 'message', 'view=2 WHERE view=4');
$installer->add_query('UPDATE', 'message', 'view=view-1 WHERE view>3');

if (isset($tablelist['users_fields'])) {
	$installer->add_query('UPDATE', 'users_fields', 'fid=fid+1 WHERE fid>12 ORDER BY fid DESC');
	$installer->add_query('INSERT', 'users_fields', '13, \'theme\', 5, 0, 7, 25, \'_THEME\'');
}

$installer->add_query('DEL', 'referer', 'rid');
$installer->add_query('DEL', 'topics', 'topicname');

if (isset($tablelist['pages'])) {
	$installer->add_query('CHANGE', 'pages', '`clanguage` `language` varchar(30) NOT NULL DEFAULT ""', '`language` `clanguage` varchar(30) NOT NULL DEFAULT ""');
}
if (isset($tablelist['ephem'])) {
	$db->sql_query('RENAME TABLE '.$tablelist['ephem'].' TO '.$prefix.'_history');
	$tablelist['history'] = $prefix.'history';
}
if (isset($tablelist['reviews'])) {
	$installer->add_query('CHANGE', 'reviews', '`rlanguage` `language` varchar(30) NOT NULL DEFAULT ""', '`language` `rlanguage` varchar(30) NOT NULL DEFAULT ""');
	$installer->add_query('CHANGE', 'reviews_add', '`rlanguage` `language` varchar(30) NOT NULL DEFAULT ""', '`language` `rlanguage` varchar(30) NOT NULL DEFAULT ""');
}

if (isset($tablelist['faqAnswer'])) {
	$installer->add_query('REN', 'faqAnswer', 'faqanswer');
	$installer->add_query('REN', 'faqCategories', 'faqcategories');
	$tablelist['faqanswer'] = $prefix.'_faqanswer';
	$tablelist['faqcategories'] = $prefix.'_faqcategories';
}

if (isset($tablelist['stats_month'])) $installer->add_query('DROP', 'stats_month');
if (isset($tablelist['stats_date'])) $installer->add_query('DROP', 'stats_date');
if (isset($tablelist['bbsearch_results'])) $installer->add_query('DROP', 'bbsearch_results');
if (isset($tablelist['sessions'])) $installer->add_query('DROP', 'sessions');
unset($tablelist['stats_month']);
unset($tablelist['stats_date']);
unset($tablelist['bbsearch_results']);
unset($tablelist['sessions']);
