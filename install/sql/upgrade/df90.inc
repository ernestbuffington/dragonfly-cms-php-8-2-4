<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/sql/upgrade/df90.inc,v $
  $Revision: 1.7 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:40 $
**********************************************/
if (!defined('INSTALL')) { exit; }

if (version_compare($version, '9.0.0 RC1', '<') && isset($tablelist['history'])) {
	$installer->add_query('CHANGE', 'history', '`elanguage` `language` varchar(30) NOT NULL DEFAULT ""', '`language` `elanguage` varchar(30) NOT NULL DEFAULT ""');
		//$db->alter_field('CHANGE', $prefix.'_history', array('elanguage', 'language'), 'varchar(30)', false, '');
}

if (version_compare($version, '9.0.3.1', '<')) {
	$installer->add_query('UPDATE', 'modules', "uninstall=1 WHERE title='coppermine'");
	$installer->add_query('UPDATE', 'modules', "uninstall=1 WHERE title='Surveys'");
	if (isset($tablelist['stories'])) {
		$db->sql_query('ALTER TABLE `'.$prefix.'_stories` CHANGE `pollID` `poll_id` INT4 DEFAULT "0" NOT NULL');
	}
	if (isset($tablelist['poll_data'])) {
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_check` CHANGE `pollID` `poll_id` INT4 DEFAULT "0" NOT NULL');
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_data` CHANGE `pollID` `poll_id` INT4 DEFAULT "0" NOT NULL');
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_data` CHANGE `optionTEXT` `option_text` CHAR(50) NOT NULL');
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_data` CHANGE `optionCount` `option_count` INT4 DEFAULT "0" NOT NULL');
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_data` CHANGE `voteID` `vote_id` INT4 DEFAULT "0" NOT NULL');
		if (DB_TYPE == 'postgresql') { 
			$db->sql_query('ALTER TABLE '.$prefix.'_poll_desc RENAME COLUMN pollID TO poll_id');
		} else {
			$db->sql_query('ALTER TABLE `'.$prefix.'_poll_desc` CHANGE `pollID` `poll_id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
		}
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_desc` CHANGE `pollTitle` `poll_title` VARCHAR(100) NOT NULL');
		$db->sql_query('ALTER TABLE `'.$prefix.'_poll_desc` CHANGE `timeStamp` `time_stamp` INT4 DEFAULT "0" NOT NULL');
		$db->sql_query('ALTER TABLE `'.$prefix.'_pollcomments` CHANGE `pollID` `poll_id` INT4 DEFAULT "0" NOT NULL');
	}
}

if (isset($tablelist['config_custom'])) {
	if (version_compare($version, '9.0.3.2', '<')) {
		global $db, $prefix;
		list($gfx_chk) = $db->sql_ufetchrow('SELECT cfg_value FROM '.$prefix."_config_custom WHERE cfg_name='global' AND cfg_field='gfx_chk'", SQL_NUM);
		$sec_code = 0;
		if ($gfx_chk == 1 || $gfx_chk >= 5) $sec_code |= 1;
		if ($gfx_chk >= 2 && $gfx_chk != 3 && $gfx_chk != 6) $sec_code |= 2;
		if ($gfx_chk >= 3 && $gfx_chk != 5) $sec_code |= 4;
		$db->sql_query('INSERT INTO '.$prefix."_config_custom (cfg_name, cfg_field, cfg_value) VALUES ('global', 'sec_code', $sec_code)");
		$db->sql_query('DELETE FROM '.$prefix."_config_custom WHERE cfg_name='global' AND cfg_field='gfx_chk'");
	}
	if (version_compare($version, '9.0.3.3', '<')) {
		$db->sql_query('INSERT INTO '.$prefix."_config_custom (cfg_name, cfg_field, cfg_value) VALUES ('sec_code', 'back_img', 1)");
		$db->sql_query('INSERT INTO '.$prefix."_config_custom (cfg_name, cfg_field, cfg_value) VALUES ('sec_code', 'font', 'bahamas.ttf')");
		$db->sql_query('INSERT INTO '.$prefix."_config_custom (cfg_name, cfg_field, cfg_value) VALUES ('sec_code', 'font_size', 12)");
	}
}

if (version_compare($version, '9.0.3.4', '<')) {
	$tmp_list = $db->list_columns($tablelist['modules_links']);
	//db_check will repack correctly the table and add `pos`
	if (!isset($tmp_list['pos'])) { 
		$installer->add_query('INSERT', 'modules_links', "NULL, '_Submit_NewsLANG', 0, 'News&amp;file=submit', 0, 0, 0");
	} else {
		$installer->add_query('INSERT', 'modules_links', "NULL, '_Submit_NewsLANG', 0, 'News&amp;file=submit', 0, 0, 0, 0");
	}
	unset($tmp_list);
}

// 9.1.0.2 >= version <= 9.1.0.4
if (preg_match('#^9.1.0.[234]$#', $version)) {
	$db->sql_query('DROP TABLE '.$prefix.'_ban');
	$db->sql_query('DROP TABLE '.$prefix.'_agents');
}

if (version_compare($version, '9.1.0.9', '<') && isset($tablelist['cpg_exif'])) {
	$tmp_list = $db->list_columns($tablelist['cpg_exif']);
	if (isset($tmp_list['exifData'])) { 
		$db->alter_field('change', $prefix.'_cpg_exif', array('exifData', 'exif_data'), 'TEXT');
	}
	unset($tmp_list);
}

if (version_compare($version, '9.1.1 RC1', '<') && DB_TYPE == 'postgresql') {
	$schema = $db->sql_ufetchrowset('SELECT current_schema()', SQL_NUM);
	// we dont want our tables inside the public schema wich is generally owned by the "postgre" superuser
	if ($schema[0][0] == 'public') {
		$db->query("SET SESSION AUTHORIZATION $dbuname");
		$db->query("CREATE SCHEMA $dbname AUTHORIZATION $dbuname");
		$db->query("REVOKE ALL ON SCHEMA $dbname FROM PUBLIC");
		// fail safe temporary search_path change
		$db->query("SET search_path TO public"); 
		$tmp_tables = $db->list_tables('public');
		foreach ($tmp_tables as $table){
			$db->query("ALTER TABLE $table SET SCHEMA $dbname");
		}
		unset($tmp_tables);
		 // definitive change applied for the current session as well?
		$db->query("ALTER USER $dbuname SET search_path TO $dbname");
	}
}

if (version_compare($version, '9.1.1.RC2', '<') && isset($tablelist['poll_data'])) {
	$tmp_list = $db->list_columns($tablelist['poll_data']);
	if (isset($tmp_list['option_TEXT'])) { 
		$db->alter_field('change', $prefix.'_poll_data', array('option_TEXT', 'option_text'), 'CHAR(50)', false);
	}
}
