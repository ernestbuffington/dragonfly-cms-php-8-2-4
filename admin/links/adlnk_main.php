<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

# _AMENU0 System
# _AMENU1 General
# _AMENU2 Members
# _AMENU3 Messages
# _AMENU4 Forums
# _AMENU5 Informative
# _AMENU6 Linking
# _AMENU7 Commerce
# _AMENU8 Multimedia
# _AMENU9 Modules

	/* System */
if (can_admin('settings')) {
	$menuitems['_AMENU0'][_PREFERENCES]['URL'] = URL::admin('settings');
	$menuitems['_AMENU0'][_PREFERENCES]['IMG'] = 'preferences';
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_SYSTEM] = URL::admin('settings&s=0');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_MAINTENANCE] = URL::admin('settings&s=1');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_BROWSER_COOKIES] = URL::admin('settings&s=2');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_FOOTER] = URL::admin('settings&s=3');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_BACKENDCONF] = URL::admin('settings&s=4');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_COMMENTSOPT] = URL::admin('settings&s=5');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_CENSOROPTIONS] = URL::admin('settings&s=6');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_EMAILOPTIONS] = URL::admin('settings&s=7');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_DEBUG] = URL::admin('settings&s=8');
	$menuitems['_AMENU0'][_PREFERENCES]['SUB'][_MISCOPT] = URL::admin('settings&s=9');
	if (is_writeable(CORE_PATH.'config.php')) {
		$menuitems['_AMENU0'][_PREFERENCES]['SUB']['config.php'] = URL::admin('settings&s=11');
	}
}
if (can_admin('security')) {
	$menuitems['_AMENU0'][_SECURITY]['URL'] = URL::admin('security');
	$menuitems['_AMENU0'][_SECURITY]['IMG'] = 'security';
	$menuitems['_AMENU0'][_SECURITY]['SUB']['Logs'] = URL::admin('security&logs');
	$menuitems['_AMENU0'][_SECURITY]['SUB']['Bots'] = URL::admin('security&bots');
	$menuitems['_AMENU0'][_SECURITY]['SUB']['E-Mail Domains'] = URL::admin('security&emails');
	$menuitems['_AMENU0'][_SECURITY]['SUB']['IPs'] = URL::admin('security&ips');
	$menuitems['_AMENU0'][_SECURITY]['SUB']['Referrers'] = URL::admin('security&referers');
	$menuitems['_AMENU0'][_SECURITY]['SUB']['IP Shield'] = URL::admin('security&shields');
	$menuitems['_AMENU0'][_SECURITY]['SUB']['Sessions'] = URL::admin('security&sessions');
}
//if (can_admin()) {
	//$menuitems['_AMENU1']['Uploads']['URL'] = URL::admin('uploads');
	//$menuitems['_AMENU1']['Uploads']['IMG'] = 'uploads';
//}
if (can_admin('cache')) {
	$menuitems['_AMENU0'][_CACHE]['URL'] = URL::admin('cache');
	$menuitems['_AMENU0'][_CACHE]['IMG'] = 'cache';
}
if (can_admin('database')) {
	$menuitems['_AMENU0'][_DATABASE]['URL'] = URL::admin('database');
	$menuitems['_AMENU0'][_DATABASE]['IMG'] = 'database';
}
if (can_admin('info')) {
	$menuitems['_AMENU0'][_SYSINFO]['URL'] = URL::admin('info');
	$menuitems['_AMENU0'][_SYSINFO]['IMG'] = 'info';
}
if (can_admin()) {
	$menuitems['_AMENU0']['Log']['URL'] = URL::admin('log');
	$menuitems['_AMENU0']['Log']['IMG'] = 'log';
	$menuitems['_AMENU0']['Package manager']['URL'] = URL::admin('packagemanager');
	$menuitems['_AMENU0']['Package manager']['IMG'] = 'plugins';
}
	$menuitems['_AMENU0'][_HELP]['URL'] = _HELP_LINK;
	$menuitems['_AMENU0'][_HELP]['TARGET'] = '_blank';
	$menuitems['_AMENU0'][_HELP]['IMG'] = 'help';
	$menuitems['_AMENU0'][_REPORTABUG]['URL'] = 'http://dragonflycms.org/Projects/p=7/';
	$menuitems['_AMENU0'][_REPORTABUG]['TARGET'] = '_blank';
	$menuitems['_AMENU0'][_REPORTABUG]['IMG'] = 'bug';

/* General */
if (can_admin('l10n')) {
	$menuitems['_AMENU1']['Languages']['URL'] = URL::admin('l10n');
	$menuitems['_AMENU1']['Languages']['IMG'] = 'l10n';
}
if (can_admin('modules')) {
	$menuitems['_AMENU1'][_MODULES]['URL'] = URL::admin('modules');
	$menuitems['_AMENU1'][_MODULES]['IMG'] = 'modules';
}
if (can_admin('blocks')) {
	$menuitems['_AMENU1'][_BLOCKS]['URL'] = URL::admin('blocks');
	$menuitems['_AMENU1'][_BLOCKS]['IMG'] = 'blocks';
}
if (can_admin('cpgmm')) {
	$menuitems['_AMENU1']['Main Menu']['URL'] = URL::admin('cpgmm');
	$menuitems['_AMENU1']['Main Menu']['IMG'] = 'cpgmm';
}
if (can_admin('smilies')) {
	$menuitems['_AMENU1']['Smilies']['URL'] = URL::admin('smilies');
	$menuitems['_AMENU1']['Smilies']['IMG'] = 'smilies';
}
//	$menuitems['_AMENU1']['Themes']['URL'] = URL::admin('themes');
//	$menuitems['_AMENU1']['Themes']['IMG'] = 'topics';

/* Members */
if (can_admin('groups')) {
	$menuitems['_AMENU2']['Groups']['URL'] = URL::admin('Groups');
	$menuitems['_AMENU2']['Groups']['IMG'] = 'groups';
}
if (can_admin('members')) {
	$menuitems['_AMENU2'][_USERSCONFIG]['URL'] = URL::admin('users_cfg');
	$menuitems['_AMENU2'][_USERSCONFIG]['IMG'] = 'usersconfig';
	$menuitems['_AMENU2'][_USERSCONFIG]['SUB']['Main'] = URL::admin('users_cfg');
	$menuitems['_AMENU2'][_USERSCONFIG]['SUB']['Avatars'] = URL::admin('users_cfg#avatars');
	$menuitems['_AMENU2'][_USERSCONFIG]['SUB']['Fields'] = URL::admin('users_cfg#fields');
	$menuitems['_AMENU2'][_EDITUSERS]['URL'] = URL::admin('users');
	$menuitems['_AMENU2'][_EDITUSERS]['IMG'] = 'users';
	$menuitems['_AMENU2'][_EDITUSERS]['SUB'][_EDIT] = URL::admin('users').'#active-users';
	$menuitems['_AMENU2'][_EDITUSERS]['SUB'][_ADD] = URL::admin('users').'#add-user';
	$menuitems['_AMENU2']['Avatars']['URL'] = URL::admin('avatars');
	$menuitems['_AMENU2']['Avatars']['IMG'] = 'avatars';
	$menuitems['_AMENU2']['Ranks']['URL'] = URL::admin('ranks');
	$menuitems['_AMENU2']['Ranks']['IMG'] = 'ranks';
}
if (can_admin()) {
	$menuitems['_AMENU2']['Authentication']['URL'] = URL::admin('auth');
	$menuitems['_AMENU2']['Authentication']['IMG'] = 'authentication';
}
	$menuitems['_AMENU2'][_EDITADMINS]['URL'] = URL::admin('admins');
	$menuitems['_AMENU2'][_EDITADMINS]['IMG'] = 'authors';

/* Messages */
if (can_admin('messages')) {
	$menuitems['_AMENU3'][_MESSAGES]['URL'] = URL::admin('messages');
	$menuitems['_AMENU3'][_MESSAGES]['IMG'] = 'messages';
}
if (can_admin('surveys')) {
	$menuitems['_AMENU3'][_Surveys]['URL'] = URL::admin('Surveys');
	$menuitems['_AMENU3'][_Surveys]['IMG'] = 'surveys';
}

/* Informative */
if (can_admin('newsletter')) {
	$menuitems['_AMENU5'][_NEWSLETTER]['URL'] = URL::admin('newsletter');
	$menuitems['_AMENU5'][_NEWSLETTER]['IMG'] = 'newsletter';
}
/* Linking */
if (can_admin('headlines')) {
	$menuitems['_AMENU6']['Headlines']['URL'] = URL::admin('headlines');
	$menuitems['_AMENU6']['Headlines']['IMG'] = 'headlines';
}

if (can_admin('referers')) {
	$menuitems['_AMENU6'][_HTTPREFERERS]['URL'] = URL::admin('referers');
	$menuitems['_AMENU6'][_HTTPREFERERS]['IMG'] = 'referers';
}
if (can_admin('social')) {
	$menuitems['_AMENU6']['Social Networks']['URL'] = URL::admin('social');
	$menuitems['_AMENU6']['Social Networks']['IMG'] = 'share';
}
