<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Surveys/sql/cpg_inst.php,v $
  $Revision: 1.1 $
  $Author: nanocaiordo $
  $Date: 2007/09/13 10:00:35 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Surveys {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
// class constructor
	function __construct() {
		$this->radmin = true;
		$this->version = '1.2';
		$this->modname = 'Surveys';
		$this->description = 'Manage Surveys to gain information from your visitors';
		$this->author = 'CPG-Nuke Dev Team';
		$this->website = 'dragonflycms.org';
		$this->dbtables = array('poll_check', 'poll_data', 'poll_desc', 'pollcomments');
	}

# module installer
	function install() {
		global $tablelist, $tables, $indexes, $records;
		foreach ($tables AS $table => $columns) {
			if (isset($tablelist[$table])) { $db->query('DROP TABLE '.$tablelist[$table]); }
			db_check::create_table($table, $columns, $indexes[$table]);
		}
		if (is_array($records) && !empty($records)) {
			foreach ($records AS $table => $content) {
				db_check::table_data($table, $content);
			}
		}
		return true;
	}

# module uninstaller
	function uninstall() {
		global $installer;
		foreach ($this->dbtables as $table) {
			$installer->add_query('DROP', $table);
		}
		return true;
	}

# module upgrader
	function upgrade($prev_version) {
		global $tablelist, $tables, $indexes, $records;
		# add your staff here

		# do not touch belove here
		foreach ($tables AS $table => $columns) {
			db_check::table_structure($table, $columns, $indexes[$table]);
		}
		if (is_array($records) && !empty($records)) {
			foreach ($records AS $table => $content) {
				db_check::table_data($table, $content);
			}
		}
		return true;
	}
}
