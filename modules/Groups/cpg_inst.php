<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Groups/cpg_inst.php,v $
  $Revision: 9.4 $
  $Author: trevor $
  $Date: 2005/05/09 20:42:10 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }
class Groups {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
	function __construct() {
		$this->radmin = true;
		$this->version = '1.1';
		$this->modname = 'Groups';
		$this->description = 'Manage user-based groups';
		$this->author = 'CPG-Nuke Dev Team';
		$this->website = 'dragonflycms.org';
		$this->dbtables = array('bbgroups', 'bbuser_group');
	}
	function install() {
		global $installer;
$installer->add_query('CREATE', 'bbgroups', '
  group_id mediumint(8) NOT NULL auto_increment,
  group_type tinyint(4) NOT NULL default "1",
  group_name varchar(40) NOT NULL default "",
  group_description varchar(255) NOT NULL default "",
  group_moderator mediumint(8) NOT NULL default "0",
  group_single_user tinyint(1) NOT NULL default "1",
  PRIMARY KEY (group_id),
  KEY group_single_user (group_single_user)', 'bbgroups');
$installer->add_query('CREATE', 'bbuser_group', '
  group_id mediumint(8) NOT NULL default "0",
  user_id mediumint(8) NOT NULL default "0",
  user_pending tinyint(1) default NULL,
  KEY group_id (group_id),
  KEY user_id (user_id)', 'bbuser_group');
$installer->add_query('INSERT', 'bbgroups', '"1", "1", "Anonymous", "Personal User", "0", "1"');
$installer->add_query('INSERT', 'bbuser_group', '"1", "-1", "0"');
$installer->add_query('INSERT', 'bbuser_group', '"1", "1", "0"');
		return true;
	}
	function uninstall() {
		global $installer;
		$installer->add_query('DROP', 'bbgroups');
		$installer->add_query('DROP', 'bbuser_group');
		return true;
	}
	function upgrade($prev_version) {
		return true;
	}
}
