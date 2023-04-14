<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Statistics/cpg_inst.php,v $
  $Revision: 9.4 $
  $Author: trevor $
  $Date: 2005/05/09 20:42:11 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }
class Statistics {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
	function __construct() {
		$this->radmin = false;
		$this->version = '1.1';
		$this->modname = 'Statistics';
		$this->description = 'Keep track of who visits your site and at what time';
		$this->author = 'CPG-Nuke Dev Team';
		$this->website = 'dragonflycms.org';
		$this->dbtables = array('counter', 'stats_hour');
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