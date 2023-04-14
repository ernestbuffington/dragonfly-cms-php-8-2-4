<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/CPGlang/cpg_inst.php,v $
  $Revision: 1.2 $
  $Author: trevor $
  $Date: 2005/05/09 20:42:10 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }
class CPGlang {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
	function __construct() {
		$this->radmin = false;
		$this->version = '1.0';
		$this->modname = 'CPG-Lang';
		$this->description = 'A powerful tool used by translators for translating Dragonfly™ into a foreign language';
		$this->author = 'Akamu Akamai';
		$this->website = 'dragonflycms.org';
		$this->dbtables = array();
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