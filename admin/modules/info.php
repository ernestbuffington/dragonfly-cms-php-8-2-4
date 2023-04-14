<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/admin/modules/info.php,v $
  $Revision: 9.16 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:57 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
$showblocks = 0;
$MAIN_CFG['global']['admingraphic'] =~ 2;
if ($MAIN_CFG['global']['admingraphic'] == 0)  $MAIN_CFG['global']['admingraphic'] = 4;

$pagetitle .= ' '._BC_DELIM.' System Info';

function get_phpinfo($mode) {
	ob_start();
	phpinfo($mode);
	$cache = ob_get_contents();
	ob_end_clean();
	$cache = preg_split('/(<body>|<\/body>)/', $cache, -1, PREG_SPLIT_NO_EMPTY);
	if ($mode != INFO_MODULES) {
		$cache = preg_split('/(<table border="0" cellpadding="3" width="600">|<\/table>)/', $cache[1], -1, PREG_SPLIT_NO_EMPTY);
		if ($mode == INFO_GENERAL) {
			return $cache[3];
		}
	}
	$cache = preg_replace('#(<th>)#m', '<td class="infohead">', $cache[1]);
	$cache = preg_replace('#(<\/th>)#m', '</td>', $cache);
	return $cache;
}

if (isset($_GET['mods'])) {
	$info = 'PHP Modules';
} else if (isset($_GET['core'])) {
	$info = 'PHP Core';
} else if (isset($_GET['envi'])) {
	$info = 'PHP Environment';
} else if (isset($_GET['vars'])) {
	$info = 'PHP Variables';
} else if (isset($_GET['database'])) {
	$info = 'SQL Server';
} else {
	$info = 'General';
}
$pagetitle .= ' '._BC_DELIM.' '.$info;

require('header.php');
OpenTable();
echo (($info == 'General') ? '<strong>General</strong>' : '<a href="'.adminlink('info&amp;general').'">General</a>').' |
'.(($info == 'PHP Core') ? '<strong>PHP Core</strong>' : '<a href="'.adminlink('info&amp;core').'">PHP Core</a>').' |
'.(($info == 'PHP Environment') ? '<strong>PHP Environment</strong>' : '<a href="'.adminlink('info&amp;envi').'">PHP Environment</a>').' |
'.(($info == 'PHP Modules') ? '<strong>PHP Modules</strong>' : '<a href="'.adminlink('info&amp;mods').'">PHP Modules</a>').' |
'.(($info == 'PHP Variables') ? '<strong>PHP Variables</strong>' : '<a href="'.adminlink('info&amp;vars').'">PHP Variables</a>').' |
'.(($info == 'SQL Server') ? '<strong>SQL Server</strong>' : '<a href="'.adminlink('info&amp;database').'">SQL Server</a>');
CloseTable();
OpenTable();

echo '<style>
.infohead {
	background-color: '.$bgcolor3.';
	color	   : #000;
	font-size  : 11px;
	font-weight: bold;
	height: 10px;
	border-width : 0px;
	border-collapse: collapse;
	border-spacing: 0px;
	padding: 1px;
}</style>';

echo '<div class="genmed"><strong>'.$info.'</strong></div><br />';

if (isset($_GET['mods'])) {
	$cache = get_phpinfo(INFO_MODULES);
	$cache = preg_replace('#(<th colspan="2">)#m', '<td class="infohead" colspan="2">', $cache);
	$cache = preg_replace('#(<div class="center">|<\/div>)#m', '', $cache);
	$cache = preg_replace('#(<h2>)#m', '<div class="genmed"><strong>', $cache);
	$cache = preg_replace('#(<\/h2>)#m', '</strong></div>', $cache);
	$cache = preg_split('/(<table border="0" cellpadding="3" width="600">|<\/table><br>|<\/table><br \/>)/', $cache, -1, PREG_SPLIT_NO_EMPTY);
	for ($i=0; $i<(is_countable($cache) ? count($cache) : 0); $i++) {
		if ($cache[$i] != '') {
			if (preg_match('#<div#m', $cache[$i])) {
				echo '<hr/>'.$cache[$i];
			} else {
				echo '<table width="500">'.$cache[$i].'</table>';
			}
		}
	}
}

else if (isset($_GET['core'])) {
	echo '<table width="500">'.get_phpinfo(INFO_CONFIGURATION).'</table>';
}

else if (isset($_GET['envi'])) {
	echo '<table width="500">'.get_phpinfo(INFO_ENVIRONMENT).'</table>';
}

else if (isset($_GET['vars'])) {
	echo '<table width="500">'.get_phpinfo(INFO_VARIABLES).'</table>';
}

else if (isset($_GET['database'])) {
	switch (DB_TYPE) {
	case 'mysql':
	case 'mysqli':
	$details = $db->get_details();
	if (DB_TYPE == 'mysql') {
		$stat = mysql_stat();
	} else {
		$stat = mysqli_stat($db->connect_id);
	}
	$stat = preg_split('/:\s+([0-9]*\.?[0-9]*)/', $stat, -1, PREG_SPLIT_DELIM_CAPTURE ^ PREG_SPLIT_NO_EMPTY);
	// stat[0] had better always be uptime...
	$days = intval($stat[1] / 86400);
	$stat[1] -= ($days * 86400);
	$hrs = intval($stat[1] / 3600);
	$stat[1] -= ($hrs * 3600);
	$mins = intval($stat[1] / 60);
	$stat[1] -= ($mins * 60);
	$secs = $stat[1];
	$stat[1] = $days . "D " . $hrs . "H " . $mins . "M " . $secs . "S";
	echo '<table border="0" width="100%">
<tr><td valign="top">
<table border="0">
<tr><td valign="top"><div class="genmed"><strong>Quick Stats:</strong></div></td></tr>
<tr><td><strong>Server Version:</strong></td><td>' .$details['server'] . '</td></tr>
<tr><td><strong>Client Version:</strong></td><td>' . $details['client'] . '</td></tr>
<tr><td><strong>Host Connection:</strong></td><td>' . $details['host'] . '</td></tr>';

	for ($i = 0; isset($stat[$i]); $i += 2) {
		$val = $stat[$i + 1];
		if (is_numeric($val)) {
			if (fmod($val, 1.0) == 0) {
				$val = number_format($val);
			} else {
				$val = number_format($val, 3);
			}
		}
		echo "<tr><td><strong>" . $stat[$i] . "</strong></td><td>" . $val . "</td></tr>";
	}		
	echo '</table></td>';

	// complete status
	$res = $db->sql_query("SHOW STATUS");
	echo '<td valign="top">
<table border="0"><tr><td><div class="genmed"><strong>Extended Status:</strong></div></td></tr>
<tr><td><select name="status" size="13">';
	while ($row = $db->sql_fetchrow($res, SQL_NUM)) {
		echo '<option>'.$row[0].'&nbsp;=&nbsp;'.$row[1].'</option>';
	}
	echo '</select></td></tr></table></td>';

	// database listing	 
	$data = $db->list_databases();
	echo '<td valign="top"><table border="0"><tr><td><div class="genmed"><strong>Installed Databases:</strong></div></td></tr>
<tr><td><select name="dblist" size="13">';
	foreach ($data as $db_name) {
		echo '<option>'.$db_name.'</option>';
	}
	echo '</select></td></tr></table></td>';

	echo '</tr><tr><td colspan="3"></td></tr><tr><td colspan="3">';
	if (function_exists('mysql_list_processes')) {
		$res = mysql_list_processes();
		echo '
<table width="100%" border="0">
<tr><td colspan="8"><div class="genmed"><strong>Running Processes:</strong></div></td></tr>
<tr><td><strong>Id</strong></td><td><strong>User</strong></td><td><strong>Host</strong></td><td><strong>Database</strong></td><td><strong>Command</strong></td><td><strong>Time</strong></td><td><strong>State</strong></td><td><strong>Info</strong></td></tr>';
	while ($row = $db->sql_fetchrow($res)) {
		echo '<tr><td>' . $row['Id'] . '</td>';
		echo '<td>' . $row['User'] . '</td>';
		echo '<td>' . $row['Host'] . '</td>';
		echo '<td>' . $row['db'] . '</td>';
		echo '<td>' . $row['Command'] . '</td>';
		echo '<td>' . $row['Time'] . '</td>';
		echo '<td>' . $row['State'] . '&nbsp;</td>';
		echo '<td>' . $row['Info'] . '&nbsp;</td></tr>';
	}
	}
	echo '</table></td></tr></table>';
	break;
	default: break;
	}
}

else {
	$sql = $db->get_versions();
	echo '<table width="500">
  <tr><td class="infohead">Setting</td><td class="infohead">Value</td></tr>
  <tr><td>CMS Version</td><td>'.CPG_NUKE.'</td></tr>
  <tr><td>PHP Version</td><td>'.phpversion().'</td></tr>
  <tr><td>'.$sql['engine'].' Version</td><td>'.$sql['server'].' (client: '.$sql['client'].')</td></tr>
';
	if (extension_loaded('gd') && function_exists('gd_info')) {
		$gd = gd_info();
		echo '  <tr><td>GD Version</td><td>'.$gd['GD Version'].'</td></tr>';
	}
	echo '
  <tr><td>CMS path</td><td>'.BASEDIR.'</td></tr>
  <tr><td>Core path</td><td>'.CORE_PATH.'</td></tr>
  <tr><td>Session save_path</td><td>'.session_save_path().'</td></tr>
  <tr><td>Process Owner</td><td>'._PROCESS_OWNER.' ('._PROCESS_UID.')</td></tr>
  <tr><td>File Owner</td><td>'._DRAGONLY_OWNER.' ('.getmyuid().')</td></tr>
  <tr><td>Group</td><td>'.getmygid().'</td></tr>
  '.get_phpinfo(INFO_GENERAL).'
</table>';
}

CloseTable();
