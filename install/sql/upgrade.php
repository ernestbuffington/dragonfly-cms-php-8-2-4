<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/sql/upgrade.php,v $
  $Revision: 1.20 $
  $Author: nanocaiordo $
  $Date: 2007/12/17 10:50:52 $
**********************************************/
if (!defined('INSTALL')) { exit; }
if ($version[0] == 6 || $version[0] == 7) {
	require('install/sql/upgrade/phpnuke.inc');
}
$upgrade_failed = "<br /><br />Please contact the CPG-Nuke Dev Team about the error<br />You cannot continue with the installation";
if ($version[0] < 9) {
	require('install/sql/upgrade/cpg8x.inc');
	require('install/sql/upgrade/cpg9x.inc');
	if ($installer->install(false, '. ')) {
		echo "<br /><b>The conversion from $version to Dragonfly is complete</b><br />Upgrade still in process, please wait...<br />";
		echo '<p align="center">
	<input type="hidden" name="step" value="3" />
	<input type="hidden" name="oldversion" value="'.$version.'" />
	<input type="hidden" name="version" value="9.0" />
	<input type="submit" value="Let\'s build the database" class="formfield" /></p>';
	} else {
		echo $installer->error . $upgrade_failed;
	}
	exit;
}

if (version_compare($version, '9.1.1', '<')) {
	require('install/sql/upgrade/df90.inc');
	if (!$installer->install(false, '. ')) {
		echo $installer->error . $upgrade_failed;
		exit;
	}
}

if (version_compare($version, '9.2', '<')) {
	require('install/sql/upgrade/df91.inc');
	if (!$installer->install(false, '. ')) {
		echo $installer->error . $upgrade_failed;
		exit;
	}
	# after we add a new table we must refresh the table list
	$tablelist = $db->list_tables();
}

echo '<br />Upgrade steps completed<br /><b>Comparing database structure for</b>';

# Check core tables
echo '<br /><br /><b>Core tables:</b>';
$tables = $indexes = $table_ids = array();
require('install/sql/tables/core.php');
foreach ($tables AS $table => $columns) {
	if (!isset($tablelist[$table]) &&
	    ($table == 'admins' && isset($tablelist['authors']) ||
	    $table == 'config_custom' && isset($tablelist['config'])))
	{
		require("install/sql/upgrade/tbl_$table.inc");
	} else {
		db_check::table_structure($table, $columns, $indexes[$table]);
	}
}
$tables = $indexes = $records = $table_ids = array();
require('install/sql/data/core.php');
foreach ($records AS $table => $content) {
	db_check::table_data($table, $content);
}
if (!isset($prefix)) { global $prefix; }
# Check news module
if (isset($tablelist['autonews']) && $db->sql_count($prefix.'_modules', "title='News'")) {
	echo '<br /><br /><b>News tables:</b>';
	$tables = $indexes = $records = $table_ids = array();
	require('install/sql/tables/news.php');
	foreach ($tables AS $table => $columns) { db_check::table_structure($table, $columns, $indexes[$table]); }
	$installer->add_query('UPDATE', 'modules', "version='1.1' WHERE title='News'"); /*uninstall=1 */
}

# Check surveys module
if (isset($tablelist['poll_desc']) && $db->sql_count($prefix.'_modules', "title='Surveys'")) {
	echo '<br /><br /><b>Surveys tables:</b>';
	$tables = $indexes = $records = $table_ids = array();
	require('install/sql/tables/surveys.php');
	foreach ($tables AS $table => $columns) { db_check::table_structure($table, $columns, $indexes[$table]); }
	$installer->add_query('UPDATE', 'modules', "version='1.1' WHERE title='Surveys'"); /*uninstall=1 */
}

# Check forums module
if (isset($tablelist['bbconfig']) && $db->sql_count($prefix.'_modules', "title='Forums'")) {
	echo '<br /><br /><b>Forums tables:</b>';
	$tables = $indexes = $records = $table_ids = array();
	require('install/sql/tables/forums.php');
	foreach ($tables AS $table => $columns) { db_check::table_structure($table, $columns, $indexes[$table]); }
	require('install/sql/data/forums.php');
	foreach ($records AS $table => $content) { db_check::table_data($table, $content); }
	$installer->add_query('UPDATE', 'modules', "version='1.0.0' WHERE title='Forums'"); /*uninstall=1 */
}

# Check coppermine module
if (isset($tablelist['cpg_installs']) && $db->sql_count($prefix.'_modules', "title='coppermine'")) {
	echo '<br /><br /><b>Coppermine tables:</b>';
	$tables = $indexes = $records = $table_ids = array();
	require('install/sql/tables/coppermine.php');
	foreach ($tables AS $table => $columns)	 { db_check::table_structure($table, $columns, $indexes[$table]); }
	require('install/sql/data/coppermine.php');
	foreach ($records AS $table => $content) { db_check::table_data($table, $content); }
	$installer->add_query('UPDATE', 'modules', "version='1.3.1' WHERE title='coppermine'"); /*uninstall=1 */
}
$tables = $indexes = $records = $table_ids = array();
echo '<br /><br />';
