<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/install/language/english.php,v $
  $Revision: 9.14 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 14:20:58 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Installer';
$instlang['s_progress'] = 'Install Progress';
$instlang['s_license'] = 'License';
$instlang['s_builddb'] = 'Build database';
$instlang['s_gather'] = 'Gather important info';
$instlang['s_create'] = 'Create super admin account';
$instlang['welcome'] = 'Welcome to Dragonfly!';
$instlang['info'] = 'This installation will guide you to setup Dragonfly on your website within minutes.<br />The installer will build the necessary database and first user or will upgrade your already installed CPG or PHP-Nuke.';
$instlang['click'] = 'Click "I Agree" if you accept the following license:';
//$instlang['license'] = 'The modifications and fixes made by the CPG-Nuke Dev Team may not be used in any Nuke version/plan or website that requires payment, registration or compensation for installation, support or download of GPL licensed software without compensation agreed to by the CPG-Nuke Dev Team who undertook this considerable, consequential, and distinguished rewriting.<br /><b>That means you can\'t sell our code as part of any commercial version.</b>';
$instlang['license_edited'] = 'Your license has been edited. Please contact the Development Team at dragonflycms.com immediately. Thank You.';
$instlang['no_zlib'] = 'Your server does not support Zlib Compression. Thus you cannot read our license from this page. Please consult GPL.txt found in your CPG-Nuke distribution and click "I Agree" below';
$instlang['agree'] = 'I Agree';
$instlang['next'] = 'Next';

$instlang['s1_good'] = 'We\'re glad you made the decision to use Dragonfly';
$instlang['s1_already'] = 'You already have '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b> installed.';
$instlang['s1_splatt'] = '<b>Warning</b> The old Splatt forum database will be deleted! If you still want to try to use it, then keep the tables.<br />Keep the Splatt Forums database? <select name="splatt" class="formfield"><option value="0">No</option><option value="1">Yes</option></select>';
$instlang['s1_new'] = 'The installer couldn\'t find a previous version, so it will install a new version for you';
$instlang['s1_upgrade'] = 'Your current version is <b>%s</b>, and it will be upgraded/converted to Dragonfly '.CPG_NUKE.'<br /><b>Be sure you have a backup of your database.</b>';
$instlang['s1_unknown'] = 'The installer couldn\'t detect which version of CPG-Nuke/PHP-Nuke you are using.<br />You can\'t continue the installation.<br />Please contact the CPG Dev Team';
$instlang['s1_database'] = 'This is a summary of what we setup in config.php for the database connection';

$instlang['s1_dbconfig'] = 'Database Configuration';
$instlang['s1_server'] = 'Server Version';
$instlang['s1_server2'] = 'The version of %s which is currently active on your server';
$instlang['s1_layer'] = 'SQL Layer';
$instlang['s1_layer2'] = 'The SQL layer to use with your website';
$instlang['s1_host'] = 'Hostname';
$instlang['s1_host2'] = 'The DNS name or IP of the server which runs the SQL server';
$instlang['s1_username'] = 'Login name';
$instlang['s1_username2'] = 'The username used to logon the SQL server';
$instlang['s1_password'] = 'Login password';
$instlang['s1_password2'] = 'The password of the username to logon the SQL server';
$instlang['s1_dbname'] = 'Database name';
$instlang['s1_dbname2'] = 'The name of a specific database which contains the desired tables with data';
$instlang['s1_prefix'] = 'Table prefix';
$instlang['s1_prefix2'] = 'A default prefix for tablenames';
$instlang['s1_userprefix'] = 'Users table prefix';
$instlang['s1_userprefix2'] = 'A default prefix for the table which contains all user data';
$instlang['s1_directory_write'] = 'Directory Write Access';
$instlang['s1_directory_write2'] = 'Directories that need write access to store information like uploaded images.<br />If one failed then "CHMOD 777" the directory';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Failed but not critical';
$instlang['s1_dot_critical'] = 'Critical';

$instlang['s1_cache'] = 'Cache';
$instlang['s1_cache2'] = 'Stores cached settings and template files for a faster page generation';
$instlang['s1_avatars'] = 'Avatars';
$instlang['s1_avatars2'] = 'When members are allowed to upload an avatar, this directory will contain their uploaded avatar';
$instlang['s1_albums'] = 'Albums';
$instlang['s1_albums2'] = 'Holds all images from the Photo Gallery which are uploaded thru FTP or any other method';
$instlang['s1_userpics'] = 'Userpics';
$instlang['s1_userpics2'] = 'Keeps sub-folders of each member ID and stores the member uploaded images in there';
$instlang['s1_config'] = 'Includes';
$instlang['s1_config2'] = 'Stores core files needed to run the CMS';

$instlang['s1_correct'] = 'If the above information is correct then let\'s start building the database';
$instlang['s1_fixerrors'] = 'Please fix the errors mentioned above first';
$instlang['s1_fatalerror'] = 'Please contact the CPG-Nuke Dev Team about the error<br />You cannot continue with the installation';
$instlang['s1_build_db'] = 'Let\'s build the database';
$instlang['s1_necessary_info'] = 'Necessary Info';
$instlang['s1_php'] = '<p style="color:#FF0000; font-style:bold">We can\'t guarantee that Dragonfly will run properly with your old PHP version<br />Ask your server administrator about upgrading to PHP 4.3.10 or 5.0.3 or higher</p>';
$instlang['s1_mysql'] = '<p style="color: #FF0000; font-style: bold;">We\'re sorry, but only MySQL 4 or higher is supported<br />Ask your server administrator about upgrading to MySQL 4 or higher</p>Your current version is: %s';
$instlang['s1_donenew'] = 'The database has been properly installed, now let\'s setup some necessary information!';
$instlang['s1_optimiz'] = 'Database optimization. The execution of this step might take a while for a big database.';
$instlang['s1_doneup'] = 'The database has been properly updated, have fun with your incredible Dragonfly!<br /><h2>Remove install.php and the install directory right now!</h2>';

$instlang['s2_info'] = 'Lets setup the necessary info:';
$instlang['s2_error'] = 'All information must be entered.';
$instlang['s2_account'] = 'The necessary info has been added. Let\'s setup your first account!';
$instlang['s2_create'] = 'Create Account';

$instlang['s2_domain'] = 'Domain Name';
$instlang['s2_domain2'] = 'The domain name where your Dragonfly powered website is hosted, for example <i>www.mysite.com</i>';
$instlang['s2_path'] = 'Path';
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
$instlang['s2_cookie_cpg'] = 'Photo Gallery cookie name';
$instlang['s2_cookie_cpg2'] = 'The name of the cookie to store photo gallery specific information of this website';

$instlang['s2_error_email'] = 'Invalid email address';
$instlang['s2_error_empty'] = 'Some fields were left empty';
$instlang['s2_error_cookiename'] = 'Invalid cookie name';
$instlang['s2_error_cookiesettings'] = 'Invalid cookie settings';
$instlang['s2_error_sessionsettings'] = 'Wrong session settings';

$instlang['s2_cookietest'] = 'We will test the cookie settings that you\'ve specified before we proceed.';
$instlang['s2_test_settings'] = 'Test Settings';

$instlang['s3_nick2'] = 'The name you use to login on this website as administrator';
$instlang['s3_email2'] = 'Your email address';
$instlang['s3_pass2'] = 'The password you use to login on this website. You may use any character';
$instlang['s3_timezone'] = 'Timezone';
$instlang['s3_timezone2'] = 'The timezone in which you want to see the time of posted messages';

$instlang['s3_warning'] = 'Be sure that you use at least: 1 uppercase, 1 lowercase and 1 number in your password.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' has been installed successfully.<br />Remove install.php and the install directory right now!<br />Then have loads of fun!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Enter my site to set everything up</a>';