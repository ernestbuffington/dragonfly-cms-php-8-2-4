<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/sql/upgrade/phpnuke.inc,v $
  $Revision: 1.3 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:40 $
**********************************************/
if (!defined('INSTALL')) { exit; }
global $db, $prefix, $user_prefix, $install, $tablelist;

if (isset($tablelist['config'])) $db->sql_query("UPDATE ".$prefix."_config SET Default_Theme='cpgnuke', backend_title= 'CPG-Nuke Powered Site', locale ='en'");
// php-nuke 7.x DROPs
$db->sql_query("ALTER TABLE ".$prefix."_blocks DROP expire", true);
$db->sql_query("ALTER TABLE ".$prefix."_blocks DROP action", true);
$db->sql_query("ALTER TABLE ".$prefix."_blocks DROP subscription", true);
$db->sql_query("ALTER TABLE ".$prefix."_modules DROP mod_group", true);
$db->sql_query("ALTER TABLE ".$prefix."_modules DROP admins", true);
$db->sql_query("ALTER TABLE ".$user_prefix."_users DROP points", true);
$db->sql_query("ALTER TABLE ".$user_prefix."_users DROP last_ip", true);

# Correct avatars
$installer->add_query('UPDATE', 'users', 'user_avatar = \'gallery/blank.gif\' WHERE user_avatar = \'blank.gif\'');

if (isset($tablelist['priv_msgs'])) $installer->add_query('DROP', 'priv_msgs');
if (isset($tablelist['userinfo_where'])) $installer->add_query('DROP', 'userinfo_where');
if (isset($tablelist['stats_year'])) $installer->add_query('DROP', 'stats_year');
if (isset($tablelist['confirm'])) $installer->add_query('DROP', 'confirm');
if (isset($tablelist['subscriptions'])) $installer->add_query('DROP', 'subscriptions');
if (isset($tablelist['quotes'])) $installer->add_query('DROP', 'quotes');
if (isset($tablelist['banned_ip'])) $installer->add_query('DROP', 'banned_ip');

// remove WebMail Tables
if (isset($tablelist['contactbook'])) $installer->add_query('DROP', 'contactbook');
if (isset($tablelist['popsettings'])) $installer->add_query('DROP', 'popsettings');
// remove Groups Tables
if (isset($tablelist['groups'])) $installer->add_query('DROP', 'groups');
if (isset($tablelist['groups_points'])) $installer->add_query('DROP', 'groups_points');

unset($tablelist['priv_msgs']);
unset($tablelist['userinfo_where']);
unset($tablelist['stats_year']);
unset($tablelist['confirm']);
unset($tablelist['subscriptions']);
unset($tablelist['userinfo_where']);
unset($tablelist['stats_year']);
unset($tablelist['confirm']);
unset($tablelist['subscriptions']);
unset($tablelist['quotes']);
unset($tablelist['banned_ip']);
unset($tablelist['contactbook']);
unset($tablelist['popsettings']);
unset($tablelist['groups']);
unset($tablelist['groups_points']);
