<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Your_Account/cpg_inst.php,v $
  $Revision: 9.3 $
  $Author: trevor $
  $Date: 2005/05/09 20:42:11 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }
class Your_Account {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
	function __construct() {
		$this->radmin = false;
		$this->version = '1.2';
		$this->modname = 'My Account';
		$this->description = 'Powerful member management system';
		$this->author = 'DJ Maze';
		$this->website = 'dragonflycms.org';
		$this->dbtables = array('users', 'users_fields');
	}
	function install() {
		return true;
	}
	function uninstall() {
		return true;
	}
	function upgrade($prev_version) {
		return true;
	}
}
