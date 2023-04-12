<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2006 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /cvs/html/install/sql/data/core.php,v $
	$Revision: 1.17 $
	$Author: nanocaiordo $
	$Date: 2007/09/13 10:18:53 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$records['blocks_custom']['compare'] = DF_DATA_CHECK_ONLY_MULTIPLE;
$records['blocks_custom']['fields'] = 'bid, mid, side, weight';
#$records['blocks_custom']['rollback'] = 'bid IN(1,2,3,4,5,6,7,8,9)';
$records['blocks_custom']['content'] = array(
	array(1,-1,'l',1),array(1,1,'l',1),array(1,2,'l',1),array(1,3,'l',1),array(1,4,'l',1),array(1,5,'l',1),array(1,6,'l',1),array(1,7,'l',1),array(1,8,'l',1),array(1,9,'l',1),array(1,10,'l',1),array(1,11,'l',1),array(1,12,'l',1),array(1,13,'l',1),array(1,14,'l',1),array(1,15,'l',1),
	array(2,-1,'l',1),array(2,1,'l',2),array(2,2,'l',2),array(2,3,'l',2),array(2,4,'l',2),array(2,5,'l',2),array(2,6,'l',2),array(2,7,'l',2),array(2,8,'l',2),array(2,9,'l',2),array(2,10,'l',2),array(2,11,'l',2),array(2,12,'l',2),array(2,13,'l',2),array(2,14,'l',2),array(2,15,'l',2),
	array(3,-1,'l',1),array(3,1,'l',3),array(3,2,'l',3),array(3,3,'l',3),array(3,4,'l',3),array(3,5,'l',3),array(3,6,'l',3),array(3,7,'l',3),array(3,8,'l',3),array(3,9,'l',3),array(3,10,'l',3),array(3,11,'l',3),array(3,12,'l',3),array(3,13,'l',3),array(3,14,'l',3),array(3,15,'l',3),
	array(4,-1,'l',1),array(4,1,'l',4),array(4,2,'l',4),array(4,3,'l',4),array(4,4,'l',4),array(4,5,'l',4),array(4,6,'l',4),array(4,7,'l',4),array(4,8,'l',4),array(4,9,'l',4),array(4,10,'l',4),array(4,11,'l',4),array(4,12,'l',4),array(4,13,'l',4),array(4,14,'l',4),array(4,15,'l',4),
	array(5,-1,'r',1),array(5,1,'r',1),array(5,2,'r',1),array(5,3,'r',1),array(5,4,'r',1),array(5,5,'r',1),array(5,6,'r',1),array(5,7,'r',1),array(5,8,'r',1),array(5,9,'r',1),array(5,10,'r',1),array(5,11,'r',1),array(5,12,'r',1),array(5,13,'r',1),array(5,14,'r',1),array(5,15,'r',1),
	array(6,-1,'r',1),array(6,1,'r',2),array(6,2,'r',2),array(6,3,'r',2),array(6,4,'r',2),array(6,5,'r',2),array(6,6,'r',2),array(6,7,'r',2),array(6,8,'r',2),array(6,9,'r',2),array(6,10,'r',2),array(6,11,'r',2),array(6,12,'r',2),array(6,13,'r',2),array(6,14,'r',2),array(6,15,'r',2),
	array(7,-1,'r',1),array(7,1,'r',3),array(7,2,'r',3),array(7,3,'r',3),array(7,4,'r',3),array(7,5,'r',3),array(7,6,'r',3),array(7,7,'r',3),array(7,8,'r',3),array(7,9,'r',3),array(7,10,'r',3),array(7,11,'r',3),array(7,12,'r',3),array(7,13,'r',3),array(7,14,'r',3),array(7,15,'r',3),
	array(8,-1,'r',1),array(8,1,'r',4),array(8,2,'r',4),array(8,3,'r',4),array(8,4,'r',4),array(8,5,'r',4),array(8,6,'r',4),array(8,7,'r',4),array(8,8,'r',4),array(8,9,'r',4),array(8,10,'r',4),array(8,11,'r',4),array(8,12,'r',4),array(8,13,'r',4),array(8,14,'r',4),array(8,15,'r',4),
	array(9,-1,'r',1),array(9,1,'r',5),array(9,2,'r',5),array(9,3,'r',5),array(9,4,'r',5),array(9,5,'r',5),array(9,6,'r',5),array(9,7,'r',5),array(9,8,'r',5),array(9,9,'r',5),array(9,10,'r',5),array(9,11,'r',5),array(9,12,'r',5),array(9,13,'r',5),array(9,14,'r',5),array(9,15,'l',5),
);

$records['config_custom']['compare'] = DF_DATA_EXIST_LEVEL2;
$records['config_custom']['query'] = 'cfg_name, cfg_field';
$records['config_custom']['content'] = array(
	'global' => array(
		'sitename' => 'My Dragonfly™ Website',
		'site_logo' => 'logo.gif',
		'slogan' => 'The speed and security that we need',
		'startdate' => date('F j, Y'),
		'adminmail' => 'admin@mysite.tld',
		'anonpost' => '0',
		'Default_Theme' => 'default',
		'foot1' => '<a href=\"http://www.spreadfirefox.com/?q=affiliates&amp;id=9981&amp;t=86\" target=\"_blank\"><img src=\"images/firefox.png\" alt=\"Get Firefox!\" title=\"Get Firefox!\" /></a>',
		'foot2' => 'The logos and trademarks used on this site are the property of their respective owners<br />\r\nWe are not responsible for comments posted by our users, as they are the property of the poster',
		'foot3' => '',
		'commentlimit' => '4096',
		'pollcomm' => '1',
		'articlecomm' => '1',
		'top' => '10',
		'storyhome' => '10',
		'oldnum' => '10',
		'banners' => '0',
		'backend_title' => 'My Dragonfly™ Website',
		'backend_language' => 'en-us',
		'language' => 'english',
		'multilingual' => '0',
		'useflags' => '0',
		'notify' => '0',
		'notify_email' => 'admin@mysite.tld',
		'notify_subject' => 'Pending News Submission',
		'notify_message' => 'A user has submitted an article on your site that requires admin approval. When you get a chance, please look into it.',
		'notify_from' => 'noreply@mysite.tld',
		'moderate' => '0',
		'admingraphic' => '7',
		'httpref' => '1',
		'httprefmax' => '1000',
		'CensorMode' => '3',
		'CensorReplace' => '(edited)',
		'GoogleTap' => '0',
		'sec_code' => '0',
		'main_module' => 'News',
		'Version_Num' => CPG_NUKE,
		'block_frames' => '1',
		'maintenance' => '1',
		'maintenance_text' => 'We\\\'re doing a bit of work on our site, so we\\\'ll be down for a while.<br />Check back later to see if we\\\'re up.',
		'admin_help' => '1',
		'crumb' => '&rsaquo;',
		'update_monitor' => '1'
	),
	'sec_code' => array(
		'back_img' => '1',
		'font' => 'bahamas.ttf',
		'font_size' => '12'
	),
	'server' => array(
		'path' => '/',
		'domain' => ''
	),
	'cookie' => array(
		'path' => '/',
		'domain' => '',
		'admin' => 'admin',
		'member' => 'my_login',
		'server' => '0'
	),
	'debug' => array(
		'error_level' => '514',
		'database' => '0',
		'session' => '0'
	),
	'email' => array(
		'allow_html_email' => '1',
		'smtp_on' => '0',
		'smtp_auth' => '0',
		'smtp_uname' => '',
		'smtp_pass' => '',
		'smtphost' => 'smtp.myhost.tld'
	),
	'avatar' => array(
		'allow_upload' => '0',
		'allow_remote' => '1',
		'allow_local' => '1',
		'animated' => '1',
		'path' => 'uploads/avatars',
		'gallery_path' => 'images/avatars',
		'default' => 'gallery/blank.gif',
		'max_height' => '80',
		'max_width' => '80',
		'filesize' => '6144'
	),
	'member' => array(
		'minpass' => '5',
		'my_headlines' => '1',
		'user_news' => '1',
		'allowuserreg' => '1',
		'sendaddmail' => '0',
		'allowuserdelete' => '0',
		'senddeletemail' => '0',
		'allowusertheme' => '1',
		'allowmailchange' => '0',
		'requireadmin' => '0',
		'useactivate' => '1',
		'show_registermsg' => '1',
		'registermsg' => 'While the administrators and moderators of this website will attempt to remove or edit any generally objectionable material as quickly as possible, it is impossible to review every message.
Therefore you acknowledge that all posts made to this website express the views and opinions of the author and not the administrators, moderators or webmaster (except for messages by these people) and hence will not be held liable.
<br /><br />
You agree not to post any abusive, obscene, vulgar, slanderous, hateful, threatening, sexually-oriented or any other material that may violate any applicable laws.
Doing so may lead to you being immediately and permanently banned (and your service provider being informed).
The IP address of all posts is recorded to aid in enforcing these conditions.
You agree that the webmaster, administrator and moderators of this website have the right to remove, edit, move or close any message at any time should they see fit.
As a user you agree to any information you have entered above being stored in a database.
While this information will not be disclosed to any third party without your consent, the webmaster, administrator and moderators cannot be held responsible for any hacking attempt that may lead to the safety of the data being compromised.
<br /><br />
This website uses cookies to store information on your local computer.
These cookies do not contain any of the information you have entered above; they serve only to improve your viewing pleasure.
The e-mail address is used only for confirming your registration details and password (and for sending new passwords should you forget your current one).',
		'send_welcomepm' => '0',
		'welcomepm_msg' => 'Hi There! We\\\'d like to thank you for registering on our site, and we wish you all the best!'
	),
	'notepad' => array(
		'lock' => '0',
		'text' => 'Swap messages with other administrators by using this handy notepad'
	),
	'_security' => array(
		'bots' => '1',
		'email' => '1',
		'ips' => '1',
		'referers' => '1',
		'uas'  => '1',
		'flooding' => '1',
		'delay' => '1',
		'unban' => '1',
		'bantime' => '86400',
		'debug' => '0',
		'shield' => '0'
	),
	'header' => array(
		'P3P' => 'CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE',
		'P3P_default' => 'CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE'
	)
);

$records['counter']['compare'] = DF_DATA_EXIST_LEVEL2;
$records['counter']['query'] = 'type, var';
$records['counter']['content'] = array(
	'total' => array('hits' => 0),
	'browser' => array(
		'Avant' => 0,
		'Camino' => 0,
		'Crazy' => 0,
		'DEVONtech' => 0,
		'Dillo' => 0,
		'Galeon' => 0,
		'ELinks' => 0,
		'Epiphany' => 0,
		'Firefox' => 0,
		'iRider' => 0,
		'K-Meleon' => 0,
		'Konqueror' => 0,
		'Linspire' => 0,
		'Lynx' => 0,
		'Maxthon' => 0,
		'Mozilla' => 0,
		'MSIE' => 0,
		'MultiZilla' => 0,
		'NetCaptor' => 0,
		'Netscape' => 0,
		'OmniWeb' => 0,
		'Opera' => 0,
		'PlayStation' => 0,
		'Safari' => 0,
		'SeaMonkey' => 0,
		'Shiira' => 0,
		'Voyager' => 0,
		'w3m' => 0,
		'WAP' => 0,
		'WebWasher' => 0,
		'Bot' => 0,
		'Other' => 0
	),
	'os' => array(
		'Windows' => 0,
		'Linux' => 0,
		'Mac' => 0,
		'FreeBSD' => 0,
		'SunOS' => 0,
		'IRIX' => 0,
		'BeOS' => 0,
		'OS/2' => 0,
		'AIX' => 0,
		'Other' => 0
	)
);

$records['credits']['compare'] = DF_DATA_CHECK_ONLY;
$records['credits']['content'] = array(
	array('User Info Block', 'Based on All Info Block by <a href="http://www.gnaunited.com" target="_new">Alex Hession</a>. Major modifications made by <a href="http://dragonflycms.org" target="_new">DJMaze</a>', 'Alex Hession', 'www.gnaunited.com')
);

$records['users_fields']['compare'] = DF_DATA_MUST_BE_SAME;
$records['users_fields']['query'] = 'fid, field';
$records['users_fields']['del'] = 'fid';
$records['users_fields']['content'] = array(
	1 => array('name', 1, 0, 0, 60, '_MA_REALNAME'),
	2 => array('femail', 1, 0, 0, 255, '_MA_FAKEMAIL'),
	3 => array('user_website', 1, 0, 0, 255, '_MA_HOMEPAGE'),
	4 => array('user_icq', 1, 0, 4, 15, '_MA_ICQ'),
	5 => array('user_aim', 1, 0, 0, 35, '_MA_AIM'),
	6 => array('user_msnm', 1, 0, 0, 40, '_MA_MSNM'),
	7 => array('user_yim', 1, 0, 0, 40, '_MA_YIM'),
	8 => array('user_skype', 1, 0, 0, 40, 'Skype'),
	9 => array('user_from', 1, 0, 0, 100, '_MA_LOCATION'),
	10 => array('user_occ', 1, 0, 0, 100, '_MA_OCCUPATION'),
	11 => array('user_interests', 1, 0, 0, 150, '_MA_INTERESTS'),
	12 => array('user_sig', 1, 0, 2, 255, '_MA_SIGNATURE'),
	13 => array('bio', 1, 0, 2, 255, '_MA_EXTRAINFO'),
	14 => array('theme', 5, 0, 7, 25, '_THEME'),
	15 => array('user_timezone', 5, 0, 3, 2, '_MA_TIMEZONE'),
	16 => array('user_dateformat', 5, 0, 0, 14, '_MA_DATEFORMAT'),
	17 => array('newsletter', 5, 0, 1, 1, '_MA_RECEIVENEWSLETTER'),
	18 => array('user_viewemail', 5, 0, 1, 0, '_MA_SHOWEMAIL'),
	19 => array('user_allow_viewonline', 5, 0, 1, 1, '_MA_SHOWONLINE'),
	20 => array('user_attachsig', 5, 0, 1, 1, '_MA_ATTACHSIG'),
	21 => array('user_allowhtml', 5, 0, 1, 0, '_MA_ALLOWHTML'),
	22 => array('user_allowbbcode', 5, 0, 1, 1, '_MA_ALLOWBBCODE'),
	23 => array('user_allowsmile', 5, 0, 1, 1, '_MA_ALLOWSMILIES'),
	24 => array('user_notify', 5, 0, 1, 0, '_MA_NOTIFYREPLY'),
	25 => array('user_notify_pm', 5, 0, 1, 0, '_MA_NOTIFYPM'),
	26 => array('user_popup_pm', 5, 0, 1, 1, '_MA_POPUPPM'),
	27 => array('user_lang', 5, 0, 8, 255, '_SELECTLANGUAGE')
);
$records['users_fields']['serial'] = 'fid';

$records['modules']['compare'] = DF_DATA_CHECK_ONLY;
$records['modules']['content'] = array(
	array('coppermine', '', '1.3.1', 1, 0, 1, 9, 0, 2, 1),
	array('Contact', '', '', 1, 0, 1, 2, 0, 1, 1),
	array('Tell_a_Friend', '', '', 1, 0, 1, 4, 0, 1, 1),
	array('Search', '', '', 1, 0, 1, 14, 0, 5, 1),
	array('Statistics', '', '', 1, 0, 1, 17, 0, 6, 1),
	array('Top', '', '', 0, 0, 1, 18, 0, 6, 1),
	array('Your_Account', '', '1.2', 1, 0, 1, 11, 0, 3, 1),
	array('Private_Messages', '', '1.1', 1, 1, 1, 12, 0, 3, 1),
	array('Forums', '', '1.0.0', 0, 0, 1, 13, 0, 4, 1),
	array('Members_List', '', '', 0, 1, 1, 8, 0, 2, 1),
	array('Groups', '', '1.1', 0, 0, 1, 10, 0, 2, 1),
	array('News', '', '1.1', 1, 0, 1, 3, 0, 1, 1),
	array('Topics', '', '', 0, 0, 1, 5, 0, 1, 1),
	array('Stories_Archive', '', '', 0, 0, 1, 6, 0, 1, 1),
	array('Surveys', '', '1.2', 1, 0, 1, 7, 1, 1, 1)
);
#$records['modules']['serial'] = 'cid';

$records['modules_cat']['compare'] = DF_DATA_CHECK_ONLY;
$records['modules_cat']['content'] = array(
	array('_HOME', 'icon_home.gif', 0, 1, $mainindex),
	array('_COMMUNITY', 'icon_community.gif', 1, 0, ''),
	array('_MEMBERSOPTIONS', 'icon_members.gif', 2, 0, ''),
	array('_BBFORUMS', 'icon_forums.gif', 3, 0, ''),
	array('_SEARCH', 'icon_search.gif', 4, 0, ''),
	array('Web', 'icon_web.gif', 5, 0, '')
);

$records['modules_links']['compare'] = DF_DATA_CHECK_ONLY;
$records['modules_links']['content'] = array(
	array('Forums', 0, 'Forums&amp;file=search', 1, 0, 15, 5),
	array('Coppermine', 0, 'coppermine&amp;file=search', 1, 0, 16, 5),
	array('_Submit_NewsLANG', 0, 'News&amp;file=submit', 1, 0, 1, 1)
);

$records['users']['compare'] = DF_DATA_EXIST_LEVEL1;
$records['users']['query'] = 'user_id';
$records['users']['fields'] = 'user_id, name, username, user_email, femail, user_website, user_avatar, user_regdate, user_icq, user_occ, user_from, user_interests, user_sig, user_viewemail, user_aim, user_yim, user_skype, user_msnm, user_password, storynum, umode, uorder, thold, noscore, bio, ublockon, ublock, theme, commentmax, counter, newsletter, user_posts, user_attachsig, user_rank, user_level, user_active, user_session_time, user_lastvisit, user_timezone, user_dst, user_style, user_lang, user_dateformat, user_new_privmsg, user_unread_privmsg, user_last_privmsg, user_emailtime, user_allowhtml, user_allowbbcode, user_allowsmile, user_allowavatar, user_allow_pm, user_allow_viewonline, user_notify, user_notify_pm, user_popup_pm, user_avatar_type, user_actkey, user_newpasswd, user_group_cp, user_group_list_cp, user_active_cp, susdel_reason';
$records['users']['content'] = array(
	1 => array(1, '', 'Anonymous', '', '', '', 'gallery/blank.gif', gmtime(), '', '', '', '', 0, 0, '', '', '', '', '', 10, '', 0, 0, 0, '', 0, '', '', 4096, 0, 0, 0, 0, 0, 1, 1, 0, 0, -5, 0, NULL, 'english', 'D M d, Y g:i a', 0, 0, 0, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 0, 3, NULL, NULL, 3, 3, 1, NULL)
);
$records['users']['serial'] = 'user_id';

$records['history']['compare'] = DF_DATA_CHECK_ONLY;
$records['history']['content'] = array(
	array(4, 4, 2000, 'ThatPHPware 0.1.0 released', ''),
	array(26, 6, 2000, 'Francisco Burzi registers the PHP-Nuke project at SourceForge, releasing version 1.0', ''),
	array(1, 12, 2001, 'Final release of ThatPHPware', ''),
	array(7, 9, 2003, 'Coppermine project rescued by forum members after the author disappears.\r\nHe was later found and was thankful for their efforts', ''),
	array(25, 9, 2003, 'Coppermine for PHP-Nuke 1.2 released', ''),
	array(25, 12, 2003, 'CPG-Nuke is born, coined as CPG-Nuke 6.5 CPG', ''),
	array(9, 1, 2004, 'Last cross-platform release (1.2b) of Coppermine for CMS from the authors of CPG-Nuke', ''),
	array(13, 1, 2004, 'cpgnuke.com, the official site of CPG-Nuke, is launched', ''),
	array(11, 4, 2004, 'cpgnuke.de (Germany) goes live', ''),
	array(16, 4, 2004, 'pitcher.no becomes official support site for CPG-Nuke in Norway', ''),
	array(17, 4, 2004, 'cpgnuke.com goes offline during the release of 8.1.1 as a result of a bad web host', ''),
	array(18, 4, 2004, 'Despite cpgnuke.com being offline, version 8.1.1 is still released through cpgnuke.de, pitcher.no and nukephotogallery.com', ''),
	array(19, 4, 2004, 'cpgnuke.com resumes operation with the help of webdev1, getting us a sponsored server through DedicatedNOW', ''),
	array(7, 5, 2004, 'The 8.2 series of CPG-Nuke is introduced', ''),
	array(23, 6, 2004, 'cpgnuke.dk (Denmark) goes live', ''),
	array(24, 6, 2004, 'cpgnuke.it (Italy) goes live', ''),
	array(30, 6, 2004, 'CPG-Nuke Brazil goes live', ''),
	array(19, 7, 2004, 'CPG-Nuke 8.2b is released, a maintenance release for 8.2a', ''),
	array(24, 7, 2004, 'cpgnuke.nl (Dutch) goes live', ''),
	array(1, 10, 2004, 'OpenSpirit name created for the release of 9.0', ''),
	array(9, 10, 2004, 'phpnuke-guatemala.net switches to CPG-Nuke support', ''),
	array(11, 10, 2004, 'cpg-nuke.se (Sweden) goes live', ''),
	array(26, 10, 2004, 'OpenSpirit name found to be taken already', ''),
	array(27, 10, 2004, 'Dragonfly™ is created to replace OpenSpirit', ''),
	array(3, 12, 2004, 'Dragonfly PR1 Released', '')
);

if (empty($version)) {
	$records['security_agents']['compare'] = DF_DATA_CHECK_ONLY_MULTIPLE;
} else {
	$records['security_agents']['compare'] = DF_DATA_EXIST_LEVEL1;
}
$records['security_agents']['query'] = 'agent_name';
$records['security_agents']['fields'] = 'agent_name, agent_fullname, agent_hostname, agent_url, agent_ban, agent_desc';
$records['security_agents']['content'] = array(
	'1Noon' => array('1Noon', '1Noonbot', NULL, '1nooncorp.com', -1, 'Doesn\\\'t follow robots.txt'),
	'1NMoreoon' => array('1-More', '1-More', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'AI' => array('AI', 'AIBOT', NULL, '21seek.com', 0, '(China) robot (218.17.90.xxx)'),
	'Accoona' => array('Accoona', 'Accoona-AI-Agent/', NULL, 'accoona.com', 0, NULL),
	'aip' => array('aip', 'aipbot/', NULL, 'nameprotect.com', 0, 'copyright search robot (24.177.134.x, NULL), s. also\n- np/0.1_(np;_http://www.nameprotect.com...\n- abot/0.1 (abot; http://www.abot.com...'),
	'Alexa' => array('Alexa', 'ia_archiver', '.alexa.com', 'alexa.com', 0, 'Alexa (209.237.238.1xx)'),
	'Archive' => array('Archive', 'ia_archiver', '.archive.org', 'archive.org', 0, 'The Internet Archive (209.237.238.1xx)'),
	'AltaVista' => array('AltaVista', 'Scooter', NULL, 'altavista.com', 0, NULL),
	'Amfibi' => array('Amfibi', 'Amfibibot', NULL, 'amfibi.com', 0, NULL),
	'Ansearch' => array('Ansearch', 'AnsearchBot/', NULL, 'ansearch.com.au', 0, NULL),
	'AnswerBus' => array('AnswerBus', 'AnswerBus', NULL, 'answerbus.com', 0, NULL),
	'Argus' => array('Argus', 'Argus/', NULL, 'simpy.com/bot.html', 0, NULL),
	'Arachmo' => array('Arachmo', 'Arachmo', NULL, NULL, -1, 'Impolite bandwidth sucker. Netblock owned by SOFTBANK BB CORP, Japan.\nDoesn\\\'t follow robots.txt'),
	'Ask Jeeves' => array('Ask Jeeves', 'Ask Jeeves/Teoma', '.ask.com', 'sp.ask.com/docs/about/tech_crawling.html', 0, NULL),
	'ASPseek' => array('ASPseek', 'ASPseek/', NULL, 'aspseek.org', 0, 'search engine software'),
	'AvantGo' => array('AvantGo', 'AvantGo', 'avantgo.com', 'avantgo.com', 0, NULL),
	'Axadine' => array('Axadine', 'Axadine Crawler', NULL, 'axada.de', 0, NULL),
	'Baidu' => array('Baidu', 'Baiduspider', NULL, 'baidu.com/search/spider.htm', 0, NULL),
	'Become' => array('Become', 'BecomeBot', NULL, NULL, 0, NULL),
	'BigClique' => array('BigClique', 'BigCliqueBOT', NULL, 'bigclique.com', 0, NULL),
	'BilderSauger' => array('BilderSauger', 'BilderSauger', NULL, 'google.com/search?q=BilderSauger+data+becker', -1, NULL),
	'BitTorrent' => array('BitTorrent', 'btbot/', NULL, 'btbot.com/btbot.html', 0, NULL),
	'Blogpulse' => array('Blogpulse', 'Blogpulse', NULL, 'blogpulse.com', 0, 'IntelliSeek service'),
	'blogsearchbot' => array('blogsearchbot', 'blogsearchbot', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Bruin' => array('Bruin', 'BruinBot', NULL, 'webarchive.cs.ucla.edu/bruinbot.html', 0, NULL),
	'cfetch' => array('cfetch', 'cfetch/', NULL, NULL, 0, NULL),
	'Cipinet' => array('Cipinet', 'Cipinet', NULL, 'cipinet.com/bot.html', 0, NULL),
	'Combine' => array('Combine', 'Combine/', NULL, 'lub.lu.se/combine/', -1, 'harvesting robot'),
	'Convera' => array('Convera', 'ConveraCrawler/', NULL, 'authoritativeweb.com/crawl', -1, 'Impolite robot. Netblock owned by Convera Corp, Vienna'),
	'Cydral' => array('Cydral', 'CydralSpider', NULL, 'cydral.com', 0, 'Cydral Web Image Search'),
	'curl' => array('curl', 'curl/', NULL, 'curl.haxx.se', 0, 'file transferring tool'),
	'Datapark' => array('Datapark', 'DataparkSearch/', NULL, 'dataparksearch.org', 0, NULL),
	'Demo' => array('Demo', 'Demo Bot', NULL, NULL, -1, NULL),
	'DHCrawler' => array('DHCrawler', 'DHCrawler', NULL, NULL, 0, NULL),
	'Diamond' => array('Diamond', 'DiamondBot', NULL, 'searchscout.com', -1, 'Claria (ex Gator) robot (64.152.73.xx, NULL), s. also Claria'),
	'DISCo' => array('DISCo', 'DISCo Pump', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Dragonfly CMS' => array('Dragonfly CMS', 'Dragonfly File Reader', NULL, NULL, 0, NULL),
	'Drecom' => array('Drecom', 'Drecombot/', 'drecom.jp', 'career.drecom.jp/bot.html', -1, 'Doesn\\\'t follow robots.txt'),
	'Dumbfind' => array('Dumbfind', 'Dumbot', NULL, 'dumbfind.com/dumbot.html', 0, NULL),
	'e-Society' => array('e-Society', 'e-SocietyRobot', NULL, 'yama.info.waseda.ac.jp/~yamana/es/', 0, NULL),
	'EmailSiphon' => array('EmailSiphon', 'EmailSiphon', NULL, NULL, -1, NULL),
	'EmeraldShield' => array('EmeraldShield', 'EmeraldShield.com WebBot', NULL, 'emeraldshield.com/webbot.aspx', 0, NULL),
	'Educate' => array('Educate', 'Educate Search', NULL, NULL, -1, NULL),
	'Envolk' => array('Envolk', 'envolk[ITS]spider/', NULL, 'envolk.com/envolkspider.html', 0, NULL),
	'Eruvo' => array('Eruvo', 'EruvoBot', NULL, 'eruvo.com', 0, NULL),
	'Esperanza' => array('Esperanza', 'EsperanzaBot', NULL, 'esperanza.to/bot/', 0, NULL),
	'eStyle' => array('eStyle', 'eStyleSearch', NULL, NULL, 0, NULL),
	'Eurip' => array('Eurip', 'EuripBot', NULL, 'eurip.com', 0, NULL),
	'Fast' => array('Fast', 'FAST MetaWeb Crawler', NULL, NULL, 0, NULL),
	'FAST Enterprise' => array('FAST Enterprise', 'FAST Enterprise Crawler', 'fastsearch.net', NULL, 0, NULL),
	'Feedster' => array('Feedster', 'Feedster Crawler', NULL, NULL, 0, NULL),
	'FetchAPI' => array('FetchAPI', 'Fetch API Request', NULL, NULL, -1, 'Some sort of application that tries to download and store your full website.\nDoesn\\\'t follow robots.txt'),
	'fg' => array('fg', 'fgcrawler', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Filangy' => array('Filangy', 'Filangy', NULL, 'filangy.com/filangyinfo.jsp?inc=robots.jsp', 0, NULL),
	'Findexa' => array('Findexa', 'Findexa Crawler', 'gulesider.no', 'findexa.no/gulesider/article26548.ece', 0, NULL),
	'FindLinks' => array('FindLinks', 'findlinks', NULL, 'wortschatz.uni-leipzig.de/findlinks/', 0, NULL),
	'Franklin' => array('Franklin', 'Franklin locator', NULL, NULL, -1, NULL),
	'FullWeb' => array('FullWeb', 'Full Web Bot', NULL, NULL, -1, NULL),
	'Fyber' => array('Fyber', 'FyberSpider', NULL, 'fybersearch.com/fyberspider.php', 0, NULL),
	'Gais' => array('Gais', 'Gaisbot', NULL, 'gais.cs.ccu.edu.tw/robot.php', 0, NULL),
	'Genie' => array('Genie', 'geniebot', NULL, 'genieknows.com', 0, NULL),
	'GetRight' => array('GetRight', 'GetRight/', NULL, NULL, 0, NULL),
	'Giga' => array('Giga', 'Gigabot/', NULL, 'gigablast.com/spider.html', 0, NULL),
	'Girafa' => array('Girafa', 'Girafabot', NULL, 'girafa.com', 0, NULL),
	'GoForIt' => array('GoForIt', 'GOFORITBOT', NULL, 'goforit.com/about/', 0, NULL),
	'Gonzo' => array('Gonzo', 'gonzo1', '.t-ipconnect.de', 'telekom.de', 0, NULL),
	'Google' => array('Google', 'Googlebot', 'crawl[0-9\\-]+.googlebot.com', 'google.com/bot.html', 0, NULL),
	'GoogleAds' => array('GoogleAds', 'Mediapartners-Google', NULL, NULL, 0, NULL),
	'GoogleImg' => array('GoogleImg', 'Googlebot-Image', NULL, NULL, 0, NULL),
	'GPU' => array('GPU', 'GPU p2p crawler', NULL, 'gpu.sourceforge.net/search_engine.php', 0, NULL),
	'Grub' => array('Grub', 'grub-client', NULL, 'grub.org', 0, NULL),
	'GSA' => array('GSA', 'gsa-crawler', NULL, 'arsenaldigital.com', 0, NULL),
	'HappyFun' => array('HappyFun', 'HappyFunBot/', NULL, 'happyfunsearch.com/bot.html', 0, NULL),
	'Harvest' => array('Harvest', 'Harvest/', NULL, NULL, 0, NULL),
	'HeadScan' => array('HeadScan', 'head-scan.pl/', NULL, NULL, -1, NULL),
	'Heritrix' => array('Heritrix', 'heritrix/', NULL, 'crawler.xtramind.com', 0, NULL),
	'hl_ftien_spider' => array('hl_ftien_spider', 'hl_ftien_spider', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'HMSE' => array('HMSE', 'HMSE_Robot', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'HooWWWer' => array('HooWWWer', 'HooWWWer', NULL, 'cosco.hiit.fi/search/hoowwwer/', 0, NULL),
	'htdig' => array('htdig', 'htdig/', NULL, NULL, -1, NULL),
	'HTMLParser' => array('HTMLParser', 'HTMLParser/', NULL, 'htmlparser.sourceforge.net', -1, 'Doesn\\\'t follow robots.txt'),
	'HTTrack' => array('HTTrack', 'HTTrack', NULL, 'httrack.com', -1, 'Website copier'),
	'Ichiro' => array('Ichiro', 'ichiro/', NULL, 'nttr.co.jp', 0, NULL),
	'IconSurf' => array('IconSurf', 'IconSurf/', NULL, 'iconsurf.com/robot.html', 0, NULL),
	'IlTrovatore' => array('IlTrovatore', 'IlTrovatore-Setaccio/', NULL, 'iltrovatore.it/bot.html', -1, NULL),
	'Industry' => array('Industry', 'Industry Program', NULL, NULL, -1, NULL),
	'Indy' => array('Indy', 'Indy Library', NULL, NULL, -1, 'Originally, the Indy Library is a programming library which is available at http://www.nevrona.com/Indy or http://indy.torry.net under an Open Source license. This library is included with Borland Delphi 6, 7, C++Builder 6, plus all of the Kylix versions. Unfortunately, this library is hi-jacked and abused by some Chinese spam bots. All recent user-agents with the unmodified \"Indy Library\" string were of Chinese origin.\nDoesn\\\'t follow robots.txt'),
	'InetURL' => array('InetURL', 'InetURL/', NULL, NULL, 0, NULL),
	'Infocious' => array('Infocious', 'InfociousBot', NULL, 'corp.infocious.com/tech_crawler.php', 0, NULL),
	'Ingrid' => array('Ingrid', 'INGRID', NULL, 'webmaster.ilse.nl/jsp/webmaster.jsp', 0, NULL),
	'Interseek' => array('Interseek', 'InterseekWeb/', NULL, NULL, 0, NULL),
	'Ipwalk' => array('Ipwalk', 'IpwalkBot/', NULL, NULL, 0, NULL),
	'iSiloX' => array('iSiloX', 'iSiloX/', NULL, 'isilox.com', -1, 'Doesn\\\'t follow robots.txt'),
	'IRL' => array('IRL', 'IRLbot', NULL, 'irl.cs.tamu.edu/crawler', 0, NULL),
	'Java' => array('Java', 'Java/', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Jyxo' => array('Jyxo', 'Jyxobot/', NULL, NULL, 0, NULL),
	'KnackAttack' => array('KnackAttack', 'KnackAttack', NULL, NULL, -1, NULL),
	'KnowItAll' => array('KnowItAll', 'KnowItAll(', NULL, 'cs.washington.edu', 0, NULL),
	'Kumm' => array('Kumm', 'KummHttp/', NULL, NULL, 0, NULL),
	'Lapozz' => array('Lapozz', 'LapozzBot', NULL, 'robot.lapozz.hu/', 0, NULL),
	'Larbin' => array('Larbin', 'larbin', NULL, 'larbin.sourceforge.net/index-eng.html', 0, NULL),
	'LeechGet' => array('LeechGet', 'LeechGet', NULL, 'leechget.net', 0, NULL),
	'libwww-perl' => array('libwww-perl', 'libwww-perl/', NULL, NULL, 0, NULL),
	'lmspider' => array('lmspider', 'lmspider', NULL, 'scansoft.com', 0, NULL),
	'Local' => array('Local', 'LocalcomBot/', NULL, 'local.com/bot.htm', 0, NULL),
	'Looksmart' => array('Looksmart', 'ZyBorg/', '.looksmart.com', 'WISEnutbot.com', 0, NULL),
	'Lorkyll' => array('Lorkyll', 'Lorkyll', NULL, '444.net', -1, NULL),
	'LoveSMS' => array('LoveSMS', 'LoveSMS Search Engine', NULL, 'cauta.lovesms.ro', 0, NULL),
	'Lycos' => array('Lycos', 'Lycos_Spider', '.lycos.com', NULL, 0, NULL),
	'Mac Finder' => array('Mac Finder', 'Mac Finder', NULL, NULL, 0, NULL),
	'Majestic-12' => array('Majestic-12', 'MJ12bot', NULL, 'majestic12.co.uk/bot.php', 0, NULL),
	'MapoftheInternet' => array('MapoftheInternet', 'MapoftheInternet.com', NULL, 'mapoftheinternet.com', 0, NULL),
	'McBot' => array('McBot', 'McBot/', NULL, NULL, 0, NULL),
	'Medusa' => array('Medusa', 'Medusa', NULL, NULL, -1, 'Medusa is a tool for finding images, movie-clips or other kinds of files on webpages and downloading them. You start by entering a starting URL and Medusa searches for the filetypes you are interested in on this page and all pages found up to a given depth.\nDoesn\\\'t follow robots.txt'),
	'Metaspinner' => array('Metaspinner', 'Metaspinner/', NULL, 'meta-spinner.de', 0, NULL),
	'MetaTag' => array('MetaTag', 'MetaTagRobot', NULL, 'widexl.com/remote/search-engines/metatag-analyzer.html', 0, NULL),
	'Minuteman' => array('Minuteman', 'Minuteman', NULL, NULL, 0, NULL),
	'Miva' => array('Miva', 'Miva', NULL, 'miva.com', 0, NULL),
	'Mirago' => array('Mirago', 'HenryTheMiragoRobot', NULL, 'miragorobot.com/scripts/mrinfo.asp', 0, NULL),
	'Missauga' => array('Missauga', 'Missauga Locate', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Missigua' => array('Missigua', 'Missigua Locator', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Mister PiX' => array('Mister PiX', 'Mister PiX', NULL, NULL, 0, NULL),
	'Mizzu' => array('Mizzu', 'Mizzu Labs', NULL, NULL, -1, 'Some spam bot from Jasmine Internet - Bangkok\nDoesn\\\'t follow robots.txt'),
	'Mojeek' => array('Mojeek', 'MojeekBot', NULL, 'mojeek.com/bot.html', 0, NULL),
	'MSCCDS' => array('MSCCDS', 'Microsoft Scheduled Cache Cont', NULL, 'google.com/search?q=Scheduled+Cache+Content+Download+Service', -1, NULL),
	'MDAIPP' => array('MDAIPP', 'Microsoft Data Access Internet', NULL, 'google.com/search?q=Microsoft+Data+Access+Internet+Publishin', -1, 'This agent is used to exploit your system regarding the following security issue in FrontPage2000: http://lists.grok.org.uk/pipermail/full-disclosure/2004-December/030467.html'),
	'MSIECrawler' => array('MSIECrawler', 'MSIECrawler', NULL, NULL, -1, NULL),
	'MSN' => array('MSN', 'msnbot', 'msnbot.msn.com', 'search.msn.com/msnbot.htm', 0, NULL),
	'MSR' => array('MSR', 'MSRBOT/', NULL, NULL, 0, NULL),
	'MUC' => array('MUC', 'Microsoft URL Control', NULL, NULL, 0, NULL),
	'Naver' => array('Naver', 'NaverBot', NULL, NULL, 0, NULL),
	'NetMechanic' => array('NetMechanic', 'NetMechanic', NULL, NULL, 0, NULL),
	'NetSprint' => array('NetSprint', 'NetSprint', NULL, NULL, -1, NULL),
	'NextGen' => array('NextGen', 'NextGenSearchBot', NULL, 'about.zoominfo.com/PublicSite/NextGenSearchBot.asp', 0, NULL),
	'nicebot' => array('nicebot', 'nicebot', NULL, NULL, -1, NULL),
	'Nimble' => array('Nimble', 'NimbleCrawler', NULL, 'healthline.com', -1, 'Doesn\\\'t follow robots.txt'),
	'Ninja' => array('Ninja', 'Download Ninja', NULL, NULL, 0, NULL),
	'Norbert' => array('Norbert', 'Norbert the Spider', NULL, 'burf.com', -1, 'Doesn\\\'t follow robots.txt'),
	'Noxtrum' => array('Noxtrum', 'noxtrumbot', NULL, 'noxtrum.com', 0, NULL),
	'NRS' => array('NRS', 'NetResearchServer', NULL, 'loopimprovements.com/robot.html', 0, NULL),
	'Nutch' => array('Nutch', 'Nutch', NULL, 'nutch.org/docs/en/bot.html', 0, NULL),
	'NutchCVS' => array('NutchCVS', 'NutchCVS/', NULL, 'lucene.apache.org/nutch/bot.html', 0, NULL),
	'Nutscrape' => array('Nutscrape', 'Nutscrape/', NULL, NULL, 0, NULL),
	'oegp' => array('oegp', 'oegp', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Offline Explorer' => array('Offline Explorer', 'Offline Explorer/', NULL, 'metaproducts.com', 0, 'A Windows offline browser that allows you to download an unlimited number of your favorite Web and FTP sites for later offline viewing, editing or browsing.'),
	'OmniExplorer' => array('OmniExplorer', 'OmniExplorer_Bot/', NULL, 'omni-explorer.com', -1, 'Doesn\\\'t follow robots.txt'),
	'Onet' => array('Onet', 'OnetSzukaj/', NULL, 'szukaj.onet.pl', 0, NULL),
	'Openfind' => array('Openfind', 'Openbot/', NULL, 'openfind.com.tw/robot.html', 0, NULL),
	'Orbit' => array('Orbit', 'Orbiter', NULL, 'dailyorbit.com/bot.htm', 0, NULL),
	'P3P Validator' => array('P3P Validator', 'P3P Validator', NULL, NULL, 0, NULL),
	'Patsearch' => array('Patsearch', 'Patwebbot', NULL, 'herz-power.de/technik.html', 0, NULL),
	'PhpDig' => array('PhpDig', 'PhpDig/', NULL, 'phpdig.net/robot.php', 0, NULL),
	'PicSearch' => array('PicSearch', 'psbot/', NULL, 'picsearch.com/bot.html', 0, NULL),
	'PictureRipper' => array('PictureRipper', 'PictureRipper/', NULL, 'pictureripper.com', -1, NULL),
	'Pipeline' => array('Pipeline', 'pipeLiner', NULL, 'pipeline-search.com/webmaster.html', 0, NULL),
	'Pogodak' => array('Pogodak', 'Pogodak', NULL, NULL, 0, NULL),
	'PoI' => array('PoI', 'PictureOfInternet/', NULL, 'malfunction.org/poi', -1, NULL),
	'Poirot' => array('Poirot', 'Poirot', NULL, NULL, -1, 'ThePlanet/jaja-jak-globusy.com Google Adsense refferer spam bot\nDoesn\\\'t follow robots.txt'),
	'Poly' => array('Poly', 'polybot', NULL, 'cis.poly.edu/polybot/', 0, NULL),
	'Pompos' => array('Pompos', 'Pompos/', NULL, 'dir.com/pompos.html', 0, NULL),
	'Poodle' => array('Poodle', 'Poodle predictor', NULL, NULL, 0, NULL),
	'Powermarks' => array('Powermarks', 'Powermarks/', NULL, 'kaylon.com/power.html', 0, NULL),
	'PrivacyFinder' => array('PrivacyFinder', 'PrivacyFinder Cache Bot', NULL, NULL, 0, NULL),
	'Privatizer' => array('Privatizer', 'privatizer.net', NULL, 'privatizer.net/whatis.php', 0, NULL),
	'Production' => array('Production', 'Production Bot', NULL, NULL, 0, NULL),
	'PS' => array('PS', 'Program Shareware', NULL, NULL, 0, NULL),
	'PuxaRapido' => array('PuxaRapido', 'PuxaRapido v1.0', NULL, NULL, 0, NULL),
	'Python-urllib' => array('Python-urllib', 'Python-urllib/', NULL, NULL, 0, NULL),
	'Qweery' => array('Qweery', 'qweery', NULL, NULL, 0, NULL),
	'Rambler' => array('Rambler', 'StackRambler/', NULL, 'rambler.ru', 0, NULL),
	'Roffle' => array('Roffle', 'Roffle/', NULL, NULL, -1, NULL),
	'RPT-HTTP' => array('RPT-HTTP', 'RPT-HTTPClient/', NULL, NULL, -1, NULL),
	'rssImages' => array('rssImages', 'rssImagesBot', NULL, 'herbert.groot.jebbink.nl/?app=rssImages', 0, NULL),
	'RSSOwl' => array('RSSOwl', 'RSSOwl/', NULL, 'rssowl.org', 0, NULL),
	'Ryan' => array('Ryan', 'Ryanbot/', NULL, NULL, 0, NULL),
	'Rufus' => array('Rufus', 'RufusBot', NULL, '64.124.122.252/feedback.html', -1, NULL),
	'SBIder' => array('SBIder', 'SBIder/', NULL, 'sitesell.com/sbider.html', 0, NULL),
	'schibstedsok' => array('schibstedsok', 'schibstedsokbot', NULL, 'schibstedsok.no', 0, NULL),
	'Schmozilla' => array('Schmozilla', 'Schmozilla/', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Scrubby' => array('Scrubby', 'Scrubby', NULL, 'scrubtheweb.com/abs/meta-check.html', 0, NULL),
	'ScSpider' => array('ScSpider', 'ScSpider/', NULL, NULL, 0, NULL),
	'SearchGuild' => array('SearchGuild', 'SearchGuild/', NULL, NULL, 0, 'DMOZ Experiment'),
	'Seekbot' => array('Seekbot', 'Seekbot', NULL, 'seekbot.net', 0, NULL),
	'Sensis' => array('Sensis', 'Sensis Web Crawler', NULL, 'sensis.com.au', 0, NULL),
	'Seznam' => array('Seznam', 'SeznamBot/', NULL, 'fulltext.seznam.cz', 0, NULL),
	'Siets' => array('Siets', 'SietsCrawler/', NULL, NULL, 0, NULL),
	'SitiDi' => array('SitiDi', '/SitiDiBot/', NULL, 'SitiDi.net', 0, NULL),
	'Snap' => array('Snap', 'snap.com', NULL, 'snap.com', -1, 'Doesn\\\'t follow robots.txt'),
	'Snoopy' => array('Snoopy', 'Snoopy', NULL, 'sourceforge.net/projects/snoopy/', 0, 'Snoopy is a PHP class that simulates a web browser. It automates the task of retrieving web page content and posting forms, for example.'),
	'Sohu' => array('Sohu', 'sohu-search', NULL, 'sogou.com', 0, 'Searchbot of sohu.com'),
	'Space Bison' => array('Space Bison', 'Space Bison/', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Spider' => array('Spider', 'SpiderKU/', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'SpiderMan' => array('SpiderMan', 'SpiderMan', NULL, NULL, -1, 'Yahoo Search user agent or spider\nDoesn\\\'t follow robots.txt'),
	'Spip' => array('Spip', 'SPIP-', NULL, 'spip.net', 0, NULL),
	'SurveyBot' => array('SurveyBot', 'SurveyBot/', NULL, 'whois.sc', 0, NULL),
	'Susie' => array('Susie', '!Susie', NULL, 'sync2it.com/susie', 0, NULL),
	'SVSpider' => array('SVSpider', 'SVSpider/', NULL, 'bildkiste.de', -1, NULL),
	'SVSearch' => array('SVSearch', 'SVSearchRobot/', NULL, NULL, -1, NULL),
	'Syntryx' => array('Syntryx', 'Syntryx', NULL, 'syntryx.com', -1, 'Syntryx Solution Suite - domain / keyword crawler\nDoesn\\\'t follow robots.txt'),
	'T8Abot' => array('T8Abot', 'T8Abot/', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'Teleport' => array('Teleport', 'Teleport Pro/', NULL, NULL, -1, NULL),
	'Thumbshots' => array('Thumbshots', 'thumbshots-de-Bot', NULL, 'thumbshots.de', 0, NULL),
	'Turnitin' => array('Turnitin', 'TurnitinBot', NULL, 'turnitin.com/robot/crawlerinfo.html', 0, NULL),
	'TutorGig' => array('TutorGig', 'TutorGigBot', NULL, 'tutorgig.info', 0, NULL),
	'Twiceler' => array('Twiceler', 'Twiceler', NULL, 'cuill.com/robots.html', 0, NULL),
	'Updated' => array('Updated', 'updated/', NULL, 'updated.com', 0, NULL),
	'Versus' => array('Versus', 'versus crawler', NULL, 'eda.baykan@epfl.ch', 0, NULL),
	'Vagabondo' => array('Vagabondo', 'Vagabondo', NULL, NULL, 0, NULL),
	'Virgo' => array('Virgo', 'Virgo/', NULL, NULL, 0, NULL),
	'Voila' => array('Voila', 'VoilaBot', NULL, 'voila.com', 0, NULL),
	'VRLImage' => array('VRLImage', 'Vision Research Lab', NULL, '(beeld|vision).ece.ucsb.edu', -1, 'Image spider\nDoesn\\\'t follow robots.txt'),
	'vspider' => array('vspider', 'vspider', NULL, NULL, 0, NULL),
	'W3C Checklink' => array('W3C Checklink', 'W3C-checklink', NULL, NULL, 0, NULL),
	'W3C Validator' => array('W3C Validator', 'W3C_Validator', NULL, NULL, 0, NULL),
	'Walhello' => array('Walhello', 'appie', NULL, 'walhello.com', 0, NULL),
	'webbot' => array('webbot', 'webbot(', NULL, 'webbot.com/bot.htm', -1, 'Doesn\\\'t follow robots.txt'),
	'WebIndexer' => array('WebIndexer', 'WebIndexer/', NULL, NULL, 0, NULL),
	'WebMiner' => array('WebMiner', 'WebMiner', NULL, NULL, -1, 'See RufusBot'),
	'WebReaper' => array('WebReaper', 'WebReaper', NULL, 'webreaper.net', 0, NULL),
	'WebSauger' => array('WebSauger', 'WebSauger', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'WebStripper' => array('WebStripper', 'WebStripper/', NULL, NULL, 0, NULL),
	'Wget' => array('Wget', 'Wget/', NULL, NULL, 0, NULL),
	'Wire' => array('Wire', 'WIRE', NULL, NULL, 0, NULL),
	'WWWeasel' => array('WWWeasel', 'WWWeasel', NULL, 'wwweasel.de', -1, 'Doesn\\\'t follow robots.txt'),
	'wwwster' => array('wwwster', 'wwwster/', NULL, NULL, -1, 'Doesn\\\'t follow robots.txt'),
	'YaCy' => array('YaCy', 'yacy', NULL, 'yacy.net/yacy/', -1, 'p2p-based distributed Web Search Engine\nDoesn\\\'t follow robots.txt'),
	'Yadows' => array('Yadows', 'YadowsCrawler', NULL, 'yadows.com', 0, NULL),
	'Yahoo' => array('Yahoo', 'Yahoo! Slurp', NULL, 'help.yahoo.com/help/us/ysearch/slurp', 0, NULL),
	'YahooFS' => array('YahooFS', 'YahooFeedSeeker/', '.yahoo.', 'help.yahoo.com/help/us/ysearch/slurp', 0, NULL),
	'YahooMM' => array('YahooMM', 'Yahoo-MMCrawler', NULL, 'help.yahoo.com/help/us/ysearch/slurp', 0, NULL),
	'YANDEX' => array('YANDEX', 'YANDEX', NULL, NULL, 0, NULL),
	'Yeti' => array('Yeti', 'Yeti', NULL, NULL, -1, '1noon.com search Korea robot\nDoesn\\\'t follow robots.txt'),
	'Xirq' => array('Xirq', 'xirq/', NULL, 'xirq.com/', 0, NULL),
	'Zeus' => array('Zeus', 'Zeus', NULL, NULL, 0, NULL)
);

