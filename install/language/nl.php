<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2014 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Installer';
$instlang['s_progress'] = 'Vooruitgang Installatie';
$instlang['s_license'] = 'Licensie';
$instlang['s_server'] = 'Check server';
$instlang['s_setconfig'] = 'Set config.php';
$instlang['s_builddb'] = 'Bouw database';
$instlang['s_gather'] = 'Belangrijke info';
$instlang['s_create'] = 'Maak "hoofd" account';
$instlang['welcome'] = 'Welkom bij Dragonfly!';
$instlang['info'] = 'Dit installatie proces zal u lijden naar een koele installatie van een Dragonfly '.CPG_NUKE.' website binnen een paar minuten.<br />De installeerder zal de noodzakelijke databank en eerste gebruiker aanmaken of zal je reeds geïnstalleerde CPG of PHP-Nuke verbeteren.';
$instlang['click'] = 'Klik "Ik ga AKKOORD" indien u de volgende vergunning aanneemt:';
$instlang['no_zlib'] = 'Your server does not support Zlib Compression. Thus you cannot read our license from this page. Please consult GPL.txt found in your CPG-Nuke distribution and click "I Agree" below';
$instlang['agree'] = 'Ik ga AKKOORD';
$instlang['next'] = 'Next';

$instlang['s1_already'] = 'You already have Dragonfly <b>'.CPG_NUKE.'</b> installed.';
$instlang['s1_new'] = 'The installer couldn\'t find a previous version, so it will install a new version for you';
$instlang['s1_upgrade'] = 'Your current version is <b>%s</b>, and it will be upgraded/converted to Dragonfly '.CPG_NUKE.'<br /><b>Be sure you have a backup of your database.</b>';
$instlang['s1_unknown'] = 'The installer couldn\'t detect which version of CPG-Nuke/PHP-Nuke you are using.<br />You can\'t continue the installation.<br />Please contact the CPG Dev Team';
$instlang['s1_database'] = 'This is a summary of what you\\\'ve setup in config.php for the database connection';

$instlang['s1_dbconfig'] = 'Database Configuration';
$instlang['s1_server2'] = 'The version of %s which is currently active on your server';
$instlang['s1_layer'] = 'SQL Layer';
$instlang['s1_layer2'] = 'The SQL layer to use with your website';
$instlang['s1_host'] = 'Hostname';
$instlang['s1_host2'] = 'The DNS name or IP of the server which runs MySQL';
$instlang['s1_username'] = 'Login name';
$instlang['s1_username2'] = 'The username used to logon the SQL server';
$instlang['s1_password'] = 'Login password';
$instlang['s1_password2'] = 'The password of the username to logon the SQL server';
$instlang['s1_dbname'] = 'Database name';
$instlang['s1_dbname2'] = 'The name of a specific database which contains the desired tables with data';
$instlang['s1_prefix'] = 'Table prefix';
$instlang['s1_prefix2'] = 'A default prefix for tablenames';
$instlang['s1_directory_write'] = 'Directory Write Access';
$instlang['s1_directory_write2'] = 'Directories that need write access to store information like uploaded images.<br />If one failed then "CHMOD 777" the directory';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Failed but not critical';
$instlang['s1_dot_critical'] = 'Critical';

$instlang['s1_server_settings'] = 'Server instellingen';
$instlang['s1_setting'] = 'instelling';
$instlang['s1_preferred'] = 'voorkeur';
$instlang['s1_yours'] = 'yours';
$instlang['s1_on'] = 'Aan';
$instlang['s1_off'] = 'Uit';

$instlang['s1_correct'] = 'If the above information is correct then let\'s start building the database';
$instlang['s1_fixerrors'] = 'Please fix the errors mentioned above first';
$instlang['s1_fatalerror'] = 'Please contact the CPG-Nuke Dev Team about the error<br />You cannot continue with the installation';
$instlang['s1_build_db'] = 'Let\'s build the database';
$instlang['s1_necessary_info'] = 'Necessary Info';
$instlang['s1_donenew'] = 'The database has been properly installed, now let\'s setup some necessary information!';
$instlang['s1_doneup'] = 'The database has been properly updated, have fun with your incredible Dragonfly!<br /><h2>Remove install.php and the install directory right now!</h2>';
$instlang['s1_trying_to_connect'] = 'Trying to connect to SQL server';
$instlang['s1_wrong_database_name'] = 'You need to choose a different database name.<br />Sorry for the inconvenience but you cannot continue with the installation with "<b>public</b>" as database name.';
$instlang['s1_save_conf_succeed'] = 'Saving configuration succeeded';
$instlang['s1_save_conf_failed'] = 'Saving configuration failed';
$instlang['s1_db_connection_succeeded'] = 'Database connection succeeded';

$instlang['s2_info'] = 'Lets setup the necessary info:';
$instlang['s2_account'] = 'The necessary info has been added. Let\'s setup your first account!';
$instlang['s2_create'] = 'Create Account';

$instlang['s2_domain'] = 'Domein Naam';
$instlang['s2_domain2'] = 'The domain name where your Dragonfly powered website is hosted, for example <i>www.mysite.com</i>';
$instlang['s2_path'] = 'Pad';
$instlang['s2_path2'] = 'The path where your Dragonfly powered website is hosted, for example <i>/html/</i>';
$instlang['s2_email2'] = 'The main email address where website information should be sent to';
$instlang['s2_session_path'] = 'Session Save Path';
$instlang['s2_session_path2'] = 'This is the path where data files are stored.<br />You must change this variable in order to use Dragonfly\\\'s session functions.<br />The path must be accessible by PHP like /home/myname/tmp/sessiondata and probably CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Cookie Domain';
$instlang['s2_cookie_domain2'] = 'The full or top-level domain to store the cookies in, for example <i>mysite.com</i> or just leave empty';
$instlang['s2_cookie_path'] = 'Cookie Path';
$instlang['s2_cookie_path2'] = 'The web address to limit the cookie to, for example <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Admin cookie name';
$instlang['s2_cookie_admin2'] = 'The name of the cookie to store administrator login information of this website';
$instlang['s2_cookie_member'] ='Member cookie name';
$instlang['s2_cookie_member2'] = 'The name of the cookie to store member login information of this website';

$instlang['s2_error_email'] = 'Invalide e-mailadres';
$instlang['s2_error_empty'] = 'Some fields were left empty';
$instlang['s2_error_cookiename'] = 'Invalid cookie name';
$instlang['s2_error_cookiesettings'] = 'Invalid cookie settings';
$instlang['s2_error_sessionsettings'] = 'Wrong session settings';

$instlang['s2_cookietest'] = 'We will test the cookie settings that you\'ve specified before we proceed.';
$instlang['s2_test_settings'] = 'Test Settings';

$instlang['s3_sync_schema'] = 'Synchronizing Database Schema';
$instlang['s3_sync_data']   = 'Synchronizing Database Data';
$instlang['s3_sync_done']   = 'Synchronization done';
$instlang['s3_exec_queries'] = 'Executing additional queries';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'The name you use to login on this website as administrator';
$instlang['s3_email2'] = 'Your email address';
$instlang['s3_pass2'] = 'The password you use to login on this website. You may use any character';
$instlang['s3_timezone'] = 'Tijdzone';
$instlang['s3_timezone2'] = 'The timezone in which you want to see the time of posted messages';

$instlang['s3_warning'] = 'Be sure that you use at least 10 characters in your password.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' has been installed successfully.<br />Remove the install directory right now!<br />Then have loads of fun!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Enter my site to set everything up</a>';
