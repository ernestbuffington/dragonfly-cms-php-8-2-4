<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Private_Messages/cpg_inst.php,v $
  $Revision: 9.5 $
  $Author: phoenix $
  $Date: 2007/05/08 03:50:06 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }
class Private_Messages {
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
		$this->modname = 'Private Messaging';
		$this->description = 'Send and recieve private messages with members';
		$this->dbtables = array('bbprivmsgs', 'bbprivmsgs_text');
	}
	function install() {
		global $installer;
$installer->add_query('CREATE', 'bbprivmsgs', '
  privmsgs_id mediumint(8) unsigned NOT NULL auto_increment,
  privmsgs_type tinyint(4) NOT NULL default "0",
  privmsgs_subject varchar(255) NOT NULL default "0",
  privmsgs_from_userid mediumint(8) NOT NULL default "0",
  privmsgs_to_userid mediumint(8) NOT NULL default "0",
  privmsgs_date int(11) NOT NULL default "0",
  privmsgs_ip varchar(16) binary NOT NULL default "",
  privmsgs_enable_bbcode tinyint(1) NOT NULL default "1",
  privmsgs_enable_html tinyint(1) NOT NULL default "0",
  privmsgs_enable_smilies tinyint(1) NOT NULL default "1",
  privmsgs_attach_sig tinyint(1) NOT NULL default "1",
  privmsgs_attachment TINYINT(1) DEFAULT "0" NOT NULL,
  PRIMARY KEY (privmsgs_id),
  KEY privmsgs_from_userid (privmsgs_from_userid),
  KEY privmsgs_to_userid (privmsgs_to_userid)', 'bbprivmsgs');
$installer->add_query('CREATE', 'bbprivmsgs_text', '
  privmsgs_text_id mediumint(8) unsigned NOT NULL default "0",
  privmsgs_text text,
  PRIMARY KEY (privmsgs_text_id)', 'bbprivmsgs_text');
		return true;
	}
	function uninstall() {
		global $installer;
		$installer->add_query('DROP', 'bbprivmsgs');
		$installer->add_query('DROP', 'bbprivmsgs_text');
		return true;
	}
	function upgrade($prev_version) {
		return true;
	}
}
