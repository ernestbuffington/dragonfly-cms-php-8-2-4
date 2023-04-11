<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

Dragonfly::getKernel()->L10N->load('Forums');
global $lang;

if (can_admin($module)) {
	$menuitems['_AMENU4'][$module]['URL'] = URL::admin($module);
	$menuitems['_AMENU4'][$module]['IMG'] = 'forums';
	$menuitems['_AMENU4'][$module]['MOD'] = $module;
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Manage']] = URL::admin($module.'&do=forums');
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Auth_Control_Forum']] = URL::admin($module.'&do=forumauth');
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Archive']] = URL::admin($module.'&do=forum_archive');
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Prune']] = URL::admin($module.'&do=forum_prune');
	$menuitems['_AMENU4'][$module]['SUB'][] = '';
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Configuration']] = URL::admin($module.'&do=config');
	$menuitems['_AMENU4'][$module]['SUB'][] = '';
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Word_Censor']] = URL::admin($module.'&do=words');
	$menuitems['_AMENU4'][$module]['SUB'][$lang['Topic_icons']] = URL::admin($module.'&do=topic_icons');

	$menuitems['_AMENU4'][_BMEM]['URL'] = URL::admin($module.'&do=user_forums');
	$menuitems['_AMENU4'][_BMEM]['IMG'] = 'users';
	$menuitems['_AMENU4'][_BMEM]['MOD'] = $module;
	$menuitems['_AMENU4'][_BMEM]['SUB'][$lang['User_Forum_Privileges']] = URL::admin($module.'&do=user_forums');
	$menuitems['_AMENU4'][_BMEM]['SUB'][$lang['Auth_Control_User']] = URL::admin($module.'&do=ug_auth&mode=user');
	$menuitems['_AMENU4'][_BMEM]['SUB'][$lang['Auth_Control_Group']] = URL::admin($module.'&do=ug_auth&mode=group');
	$menuitems['_AMENU4'][$lang['Attachments']][] = '';

	$menuitems['_AMENU4'][$lang['Attachments']]['URL'] = URL::admin($module.'&do=attach_cp');
	$menuitems['_AMENU4'][$lang['Attachments']]['IMG'] = 'users';
	$menuitems['_AMENU4'][$lang['Attachments']]['MOD'] = $module;
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][$lang['Control_Panel']] = URL::admin($module.'&do=attach_cp');
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][] = '';
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][$lang['Attach_settings']] = URL::admin($module.'&do=attachments&mode=manage');
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][$lang['Shadow_attachments']] = URL::admin($module.'&do=attachments&mode=shadow');
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][$lang['Sync_attachments']] = URL::admin($module.'&do=attachments&mode=sync');
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][] = '';
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][$lang['Extension_control']] = URL::admin($module.'&do=extensions&mode=extensions');
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB']['Extension group manager'] = URL::admin($module.'&do=extensions&mode=groups');
	$menuitems['_AMENU4'][$lang['Attachments']]['SUB'][$lang['Forbidden_extensions']] = URL::admin($module.'&do=extensions&mode=forbidden');
}
