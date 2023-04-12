<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/sql/upgrade/df91.inc,v $
  $Revision: 1.17.2.2 $
  $Author: nanocaiordo $
  $Date: 2008/02/15 15:03:29 $
**********************************************/
if (!defined('INSTALL')) { exit; }

if (version_compare($version, '9.1.1', '<')) {
	$db->alter_field('add', $prefix.'_blocks', 'in_module', 'VARCHAR(255)');
	$tmp_list = $db->list_columns($tablelist['blocks']);
	if (isset($tmp_list['types'])) {
		$result = $db->sql_query('SELECT mid, title FROM '.$prefix.'_modules');
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$mods[$row[0]] = $row[1];
		}
		$db->sql_freeresult($result);
		$result = $db->sql_query('SELECT bid, types FROM '.$prefix.'_blocks');
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$mids = '-1';
			foreach ($mods as $mid => $title) {
				if (preg_match('/\b'.$title.'\b/', $row[1])) {
					$mids .= ','.$mid;
				}
			}
			$db->sql_query('UPDATE '.$prefix."_blocks SET in_module='$mids' WHERE bid=".$row[0]);
		}
		unset($mods);
	} else {
		$mids[-1] = -1;
		$result = $db->sql_query('SELECT mid FROM '.$prefix.'_modules');
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$mids[] = $row[0];
		}
		$db->sql_freeresult($result);
		$mids = implode(',', $mids);
		$db->sql_query('UPDATE '.$prefix."_blocks SET in_module='$mids'");
	}
	unset($tmp_list, $mids);
}

if (version_compare($version, '9.1.2.4', '<')) {
	$tmp_list = $db->list_columns($tablelist['bbposts_text']);
	if (isset($tmp_list['bbcode_uid'])) {
	$installer->add_query('CHANGE', 'bbposts_text', '`bbcode_uid` `bbcode_uid` VARCHAR(10) NULL');
	}
	$tmp_list = $db->list_columns($tablelist['bbprivmsgs_text']);
	if (isset($tmp_list['privmsgs_bbcode_uid'])) {
		$installer->add_query('CHANGE',  'bbprivmsgs_text', '`privmsgs_bbcode_uid` `privmsgs_bbcode_uid` VARCHAR(10) NULL');
	}
	$installer->add_query('CHANGE', 'comments', array('host_name', DBFT_VARBINARY.'(17)', TRUE, TRUE));

}

if (version_compare($version, '9.1.2.6', '<')) {
	# smilies fix
	$smilies_fix = false;
	$tmp_list = $db->list_columns($tablelist['bbsmilies']);
	if (!isset($tmp_list['pos'])) {
		$installer->add_query('ADD', 'bbsmilies', array('pos', DBFT_INT2, false, 0), 'pos');
		$smilies_fix = true;
	} else if ($db->sql_count($prefix.'_bbsmilies', 'pos=0') > 0) {
			$smilies_fix = true;
	}
	if ($smilies_fix) {
		$result = $db->query('SELECT smilies_id FROM '.$prefix.'_bbsmilies');
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$installer->add_query('UPDATE', 'bbsmilies', "pos='{$row['0']}' WHERE smilies_id='{$row['0']}'");
		}
	}
	# cpgmm fix
	if ($db->sql_count($prefix.'_modules', 'pos=0') > 0) {
		$categories = array();
		$cats = $db->sql_uquery("SELECT cid FROM ".$prefix."_modules_cat ORDER BY pos");
		while ($cat = $db->sql_fetchrow($cats, SQL_ASSOC)) {
			$categories[$cat['cid']] = array();
		}
		$categories[0] = array();
		$items = $db->sql_uquery("SELECT mid AS lid, title, cat_id FROM ".$prefix."_modules");
		while ($item = $db->sql_fetchrow($items, SQL_ASSOC)) {
			$categories[$item['cat_id']][$item['title']]['lid'] = $item['lid'];
			$categories[$item['cat_id']][$item['title']]['table'] = 'modules';
			$categories[$item['cat_id']][$item['title']]['field'] = 'mid';
		}
		$items = $db->sql_uquery("SELECT lid, title, cat_id FROM ".$prefix."_modules_links");
		while ($item = $db->sql_fetchrow($items, SQL_ASSOC)) {
			$categories[$item['cat_id']][$item['title']]['lid'] = $item['lid'];
			$categories[$item['cat_id']][$item['title']]['table'] = 'modules_links';
			$categories[$item['cat_id']][$item['title']]['field'] = 'lid';
		}
		$pos = 0;
		$count = count($categories);
		foreach ($categories as $cat => $data) {
			ksort($categories[$cat]);
			foreach ($categories[$cat] as $title => $item) {
				++$pos; # starts from 1
				$installer->add_query('UPDATE', $item['table'], "pos=$pos WHERE {$item['field']}={$item['lid']}");
			}
		}
		$categories = null;
	}
	# blocks upgrade
	require('install/sql/tables/core.php');
	$table = 'blocks_custom';
	db_check::create_table($table, $tables['blocks_custom'], $indexes['blocks_custom']);
	$mod_list = array();
	$result = $db->sql_uquery('SELECT mid from '.$prefix.'_modules');
	while($row = $db->sql_fetchrow($result, SQL_NUM)) {
		$mod_list[$row[0]] = '';
	}
	$result = $db->sql_uquery('SELECT bid, bposition, weight, in_module FROM '.$prefix.'_blocks');
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$mids = explode(',',$row['in_module']);
		foreach ($mids as $mid) {
			if (!isset($mod_list[$mid]) && $mid != -1) continue; # skip data still pointing to uninstalled modules, probably old bug
			$blocks[] = "('$row[bid]', '$mid', '$row[bposition]', '$row[weight]')";
		}
	}
	$mod_list = NULL;
	$installer->add_query('INSERT_MULTIPLE', 'blocks_custom', array('bid,mid,side,weight', implode(',', $blocks)));
	$db->query('DELETE FROM '.$prefix.'_security_flood', true);
}

if (version_compare($version, '9.1.2.8', '<')) {
	$installer->add_query('UPDATE', 'blocks', "bkey='file' WHERE blockfile!=''");
	$installer->add_query('UPDATE', 'blocks', "bkey='rss' WHERE content!='' AND url!=''");
	$installer->add_query('UPDATE', 'blocks', "bkey='custom' WHERE bkey!='admin' AND content!='' AND url='' AND blockfile=''");
	$installer->add_query('UPDATE', 'blocks', "bkey='custom' WHERE bkey=''");
}

if (version_compare($version, '9.1.2.8', '==')) {
	$result = $db->query('SELECT bid FROM '.$prefix."_blocks WHERE title='_ADMIN' OR title='Administration'");
	if ($db->sql_numrows($result) == 1) {
		$row = $db->sql_fetchrow($result, SQL_NUM);
		$installer->add_query('UPDATE', 'blocks', "bkey='admin' WHERE bid=".$row[0]);
	}
}
if (version_compare($version, '9.1.2.82', '<')) {
	$installer->add_query('UPDATE', 'modules', "version='1.2' WHERE title='Surveys'");
}
