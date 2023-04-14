<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/install/sql/upgrade/tbl_admins.inc,v $
  $Revision: 1.5 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:40 $
**********************************************/
if (!defined('INSTALL')) { exit; }

// Convert cms_authors to cms_admins
$radmins = '';
$admins = array();
if ($version >= 7.5 && isset($tablelist['config'])) {
	$rfields = array('news', 'topics', 'your_account', 'surveys', 'ephem', 'newsletter', 'forums', 'groups');
	$result = $db->sql_query('SELECT title, admins FROM '.$prefix.'_modules', true);
	while($row = $db->sql_fetchrow($result)) {
	  $row['title'] = strtolower($row['title']);
	  $rfields[] = $row['title'];
	  $radmins .= 'radmin'.$row['title'].' INT1 NOT NULL default 0, ';
	  if (!empty($row['admins'])) {
		$tmpadmins = explode(',', $row['admins']);
		foreach($tmpadmins as $admin) {
			$admins[$admin][$row['title']] = 1;
		}
	  }
	}
	$db->sql_freeresult($result);
} else {
	$rfields = '$fields=", \'$row[radminarticle]\', \'$row[radmintopic]\', \'$row[radminuser]\', \'$row[radminsurvey]\', \'$row[radminephem]\', \'$row[radminnewsletter]\', \'$row[radminforum]\', \'$row[radmingroups]\'';
	if (isset($tablelist['links_categories'])) {
	  $radmins .= 'radminweb_links INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radminlink]\'';
	}
	if (isset($tablelist['encyclopedia'])) {
	  $radmins .= 'radminencyclopedia INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radminency]\'';
	}
	if (isset($tablelist['pages'])) {
	  $radmins .= 'radmincontent INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radmincontent]\'';
	}
	if (isset($tablelist['sections'])) {
	  $radmins .= 'radminsections INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radminsection]\'';
	}
	if (isset($tablelist['faqAnswer'])) {
	  $radmins .= 'radminfaq INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radminfaq]\'';
	}
	if (isset($tablelist['downloads_categories'])) {
	  $radmins .= 'radmindownloads INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radmindownload]\'';
	}
	if (isset($tablelist['reviews'])) {
	  $radmins .= 'radminreviews INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radminreviews]\'';
	}
	if (isset($tablelist['cpg_installs'])) {
	  $radmins .= 'radmincoppermine INT1 NOT NULL default 0, ';
	  $rfields .= ', \'$row[radmincoppermine]\'';
	}
	$rfields .= '";';
}
$installer->add_query('CREATE', 'admins', "
  admin_id SERIAL4,
  aid VARCHAR(40) NOT NULL default '',
  email VARCHAR(255) NOT NULL default '',
  pwd VARCHAR(40) default NULL,
  counter INT4 NOT NULL default 0,
  radminsuper INT1 NOT NULL default 0,
  radminnews INT1 NOT NULL default 0,
  radmintopics INT1 NOT NULL default 0,
  radminmembers INT1 NOT NULL default 0,
  radminsurveys INT1 NOT NULL default 0,
  radminhistory INT1 NOT NULL default 0,
  radminnewsletter INT1 NOT NULL default 0,
  radminforums INT1 NOT NULL default 0,
  radmingroups INT1 NOT NULL default 0,
  ".$radmins."
  PRIMARY KEY (admin_id),
  KEY aid (aid)", 'admins');

$result = $db->sql_query('SELECT * FROM '.$prefix.'_authors');
while($row = $db->sql_fetchrow($result)) {
	if (is_array($rfields)) {
		// puke >= 7.5
		$fields = '';
		foreach($rfields as $field) { $fields .= ', '.intval(isset($admins[$row['aid']][$field])); }
	} else {
		eval($rfields);
	}
	$installer->add_query('INSERT', 'admins', "DEFAULT, '$row[aid]', '$row[email]', '$row[pwd]', '$row[counter]', '$row[radminsuper]' $fields");
}
$db->sql_freeresult($result);
$installer->add_query('DROP', 'authors');
