<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/sql/upgrade/tbl_config_custom.inc,v $
  $Revision: 1.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:41 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$installer->add_query('CREATE', 'config_custom', "
  cfg_name varchar(20) NOT NULL default '',
  cfg_field varchar(50) NOT NULL default '',
  cfg_value text NOT NULL,
  UNIQUE KEY unique_cfg (cfg_name,cfg_field)", 'config_custom');

if (isset($tablelist['config'])) {
  // Convert cms_config to cms_config_custom
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP copyright", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP footermsgtxt", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP email_send", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP attachmentdir", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP attachments", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP attachments_view", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP download_dir", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP defaultpopserver", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP singleaccount", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP singleaccountname", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP numaccounts", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP imgpath", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP filter_forward", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP max_upfile", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP index_foot", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP index_head", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP servermail", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP ultramode", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP anonymous", true);
  $db->sql_query("ALTER TABLE ".$prefix."_config DROP locale", true);

  $result = $db->sql_query('SELECT * FROM '.$prefix.'_config', true);
  $config = $db->sql_fetchrow($result);
  $db->sql_freeresult($result);

  if (!isset($config['main_module'])) {
	# Merge main_module table in config
	$main = 'News';
	if (isset($tablelist['main'])) {
	  $result = $db->sql_query('SELECT main_module FROM '.$prefix.'_main', true);
	  if ($result) {
		list($main) = $db->sql_fetchrow($result);
	  }
	  $installer->add_query('DROP', 'main');
	}
	$config['main_module'] = $main;
  }

  foreach($config as $var => $value) {
	if (!is_numeric($var)) {
		if (ereg('allow_html_email|smtp_on|smtp_auth|smtp_uname|smtp_pass|smtphost', $var)) {
			$installer->add_query('INSERT', 'config_custom', "'email', '$var', '$value'");
		} else if (ereg('path|domain', $var)) {
			$installer->add_query('INSERT', 'config_custom', "'server', '$var', '$value'");
		} else {
			$installer->add_query('INSERT', 'config_custom', "'global', '$var', '".Fix_Quotes($value)."'");
		}
	}
  }
  $installer->add_query('DROP', 'config');
}

if (isset($tablelist['users_config'])) {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_users_config', true);
	$user_cfg = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	foreach($user_cfg as $key => $val) {
		if (!is_numeric($key)) {
			$installer->add_query('INSERT', 'config_custom', "'member', '$key', '".Fix_Quotes($val)."'");
		}
	}
	$installer->add_query('DROP', 'users_config');
}
