<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/links/adlnk_main.php,v $
  $Revision: 9.22 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:51:26 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$menuitems['_AMENU2'][_EDITADMINS]['URL'] = adminlink('admins');
$menuitems['_AMENU2'][_EDITADMINS]['IMG'] = 'authors';

$menuitems['System'][_HELP]['URL'] = _HELP_LINK;
$menuitems['System'][_HELP]['IMG'] = 'help';

if (can_admin()) {
	$menuitems['System'][_PREFERENCES]['URL'] = adminlink('settings');
	$menuitems['System'][_PREFERENCES]['IMG'] = 'preferences';
	$menuitems['System'][_PREFERENCES]['SUB'][_MAINTENANCE] = adminlink('settings&amp;s=1');
	$menuitems['System'][_PREFERENCES]['SUB']['Cookies'] = adminlink('settings&amp;s=2');
	$menuitems['System'][_PREFERENCES]['SUB']['Footer'] = adminlink('settings&amp;s=3');
	$menuitems['System'][_PREFERENCES]['SUB']['Syndication'] = adminlink('settings&amp;s=4');
	$menuitems['System'][_PREFERENCES]['SUB']['Comments'] = adminlink('settings&amp;s=5');
	$menuitems['System'][_PREFERENCES]['SUB']['Censor'] = adminlink('settings&amp;s=6');
	$menuitems['System'][_PREFERENCES]['SUB']['Mail'] = adminlink('settings&amp;s=7');
	$menuitems['System'][_PREFERENCES]['SUB']['Debug'] = adminlink('settings&amp;s=8');
	$menuitems['System'][_PREFERENCES]['SUB'][_MISCOPT] = adminlink('settings&amp;s=9');
	$menuitems['System'][_PREFERENCES]['SUB']['Security Code'] = adminlink('settings&amp;s=10');
	if (is_writeable(CORE_PATH.'config.php')) {
		$menuitems['System'][_PREFERENCES]['SUB']['config.php'] = adminlink('settings&amp;s=11');
	}
	$menuitems['System'][_PREFERENCES]['SUB']['P3P'] = adminlink('settings&amp;s=12');
	$menuitems['_AMENU1']['CPG Main Menu']['URL'] = adminlink('cpgmm');
	$menuitems['_AMENU1']['CPG Main Menu']['IMG'] = 'cpgmm';
	$menuitems['System'][_DATABASE]['URL'] = adminlink('database');
	$menuitems['System'][_DATABASE]['IMG'] = 'database';
	$menuitems['_AMENU1'][_BLOCKS]['URL'] = adminlink('blocks');
	$menuitems['_AMENU1'][_BLOCKS]['IMG'] = 'blocks';
	if (function_exists('mmcache') || function_exists('eaccelerator')) {
		$menuitems['System']['Cache']['URL'] = adminlink('cache');
		$menuitems['System']['Cache']['IMG'] = 'cache';
	}
	$menuitems['_AMENU1'][_MODULES]['URL'] = adminlink('modules');
	$menuitems['_AMENU1'][_MODULES]['IMG'] = 'modules';
	$menuitems['_AMENU1']['Smilies']['URL'] = adminlink('smilies');
	$menuitems['_AMENU1']['Smilies']['IMG'] = 'smilies';
	$menuitems['_AMENU1']['Languages']['URL'] = adminlink('l10n');
	$menuitems['_AMENU1']['Languages']['IMG'] = 'l10n';
	//$menuitems['_AMENU1']['Uploads']['URL'] = adminlink('uploads');
	//$menuitems['_AMENU1']['Uploads']['IMG'] = 'uploads';
	$menuitems['System']['System Info']['URL'] = adminlink('info');
	$menuitems['System']['System Info']['IMG'] = 'info';
	$menuitems['System'][_REPORTABUG]['URL'] = 'http://dragonflycms.org/Projects/p=2.html';
	$menuitems['System'][_REPORTABUG]['IMG'] = 'bug';
	$menuitems['System']['Security']['URL'] = adminlink('security');
	$menuitems['System']['Security']['IMG'] = 'security';
	$menuitems['System']['Security']['SUB']['Bots'] = adminlink('security&amp;bots');
	$menuitems['System']['Security']['SUB']['E-Mail Domains'] = adminlink('security&amp;mails');
	$menuitems['System']['Security']['SUB']['Flooding'] = adminlink('security&amp;floods');
	$menuitems['System']['Security']['SUB']['IPs'] = adminlink('security&amp;ips');
	$menuitems['System']['Security']['SUB']['Referers'] = adminlink('security&amp;referers');
	$menuitems['System']['Security']['SUB']['IP Shield'] = adminlink('security&amp;shields');
	$menuitems['_AMENU2'][_USERSCONFIG]['URL'] = adminlink('users_cfg');
	$menuitems['_AMENU2'][_USERSCONFIG]['IMG'] = 'usersconfig';
	$menuitems['_AMENU2'][_USERSCONFIG]['SUB']['Main'] = adminlink('users_cfg');
	$menuitems['_AMENU2'][_USERSCONFIG]['SUB']['Avatars'] = adminlink('users_cfg&amp;mode=avatar');
	$menuitems['_AMENU2'][_USERSCONFIG]['SUB']['Fields'] = adminlink('users_cfg&amp;mode=fields');
	$menuitems['_AMENU3'][_MESSAGES]['URL'] = adminlink('messages');
	$menuitems['_AMENU3'][_MESSAGES]['IMG'] = 'messages';
	$menuitems['_AMENU6'][_HTTPREFERERS]['URL'] = adminlink('referers');
	$menuitems['_AMENU6'][_HTTPREFERERS]['IMG'] = 'referers';
	$menuitems['_AMENU6'][_BANNERS]['URL'] = adminlink('Our_Sponsors');
	$menuitems['_AMENU6'][_BANNERS]['IMG'] = 'banners';
	$menuitems['_AMENU6']['Headlines']['URL'] = adminlink('headlines');
	$menuitems['_AMENU6']['Headlines']['IMG'] = 'headlines';
}
if (can_admin('members')) {
	$menuitems['_AMENU2'][_EDITUSERS]['URL'] = adminlink('users');
	$menuitems['_AMENU2'][_EDITUSERS]['IMG'] = 'users';
	$menuitems['_AMENU2'][_EDITUSERS]['SUB'][_EDIT] = adminlink('users&amp;mode=edit');
	$menuitems['_AMENU2'][_EDITUSERS]['SUB'][_ADD] = adminlink('users&amp;mode=add');
	$menuitems['_AMENU2']['Ranks']['URL'] = adminlink('ranks');
	$menuitems['_AMENU2']['Ranks']['IMG'] = 'ranks';
}
if (can_admin('newsletter')) {
	$menuitems['_AMENU5'][_NEWSLETTER]['URL'] = adminlink('newsletter');
	$menuitems['_AMENU5'][_NEWSLETTER]['IMG'] = 'newsletter';
}
if (can_admin('surveys')) {
	$menuitems['_AMENU3'][_ADMPOLLS]['URL'] = adminlink('Surveys&amp;mode=add');
	$menuitems['_AMENU3'][_ADMPOLLS]['IMG'] = 'surveys';
}
if (can_admin('groups')) {
	$menuitems['_AMENU2']['Groups']['URL'] = adminlink('groups');
	$menuitems['_AMENU2']['Groups']['IMG'] = 'groups';
}
if (can_admin('history')) {
	$menuitems['_AMENU5'][_EPHEMERIDS]['URL'] = adminlink('history');
	$menuitems['_AMENU5'][_EPHEMERIDS]['IMG'] = 'history';
}
