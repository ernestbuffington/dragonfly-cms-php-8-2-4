<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/cpg_inst.php,v $
  $Revision: 9.6 $
  $Author: phoenix $
  $Date: 2007/05/01 11:05:28 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }
class News {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
	function News() {
		$this->radmin = false;
		$this->version = '1.1';
		$this->modname = 'News';
		$this->description = 'Manage news articles that can be sorted between categories and topics';
		$this->author = 'CPG-Nuke Dev Team';
		$this->website = 'dragonflycms.org';
		$this->dbtables = array('autonews', 'queue', 'stories', 'stories_cat', 'topics', 'comments');
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
