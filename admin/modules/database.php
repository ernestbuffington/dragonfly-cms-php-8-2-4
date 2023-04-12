<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/database.php,v $
  $Revision: 9.25 $
  $Author: nanocaiordo $
  $Date: 2007/08/25 17:11:22 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
// SHOW FIELDS FROM `cms_stories`
// SHOW KEYS FROM `cms_stories`
$crlf = "\n";
if (isset($_POST['database'])) {
		$database = $schema = $_POST['database'];
} else {
	if (DB_TYPE == 'postgresql') {
		$schema = $db->sql_ufetchrowset('SELECT current_schema()');
		$database = $schema = $schema[0][0];
	} else {
		$database = $dbname;
	}
}
$filename = $database.'_'.formatDateTime(gmtime(), _DATESTRING3).'.sql';
$mode = (isset($_POST['mode']) && !$CLASS['member']->demo) ? $_POST['mode'] : '';
$type = strtoupper(substr($mode,0,-2));
if (isset($_POST['switchdb'])) $mode = '';
if (isset($_POST['tablelist']) && !isset($_POST['switchdb'])) {
	$tablelist = $_POST['tablelist'];
	$full = false;
} else {
	$tablelist = $db->list_tables($database);
	$full = true;
}

$pagetitle .= ' '._BC_DELIM.' '._DATABASE;

set_time_limit(0);

function show($mode, $database, $tablelist, $query) {
	global $db, $bgcolor2, $bgcolor3, $type;
		require_once('header.php');
		GraphicAdmin('System');
		OpenTable();
		if ($query === null) {
			echo 'Mode: <b>'.$mode.'</b> not available yet';
			return;
		} 
		if (is_countable($tablelist) ? count($tablelist) : 0) {
			$result = $db->sql_query($query);
			$numfields = $db->sql_numfields($result);
			echo '<span class="genmed"><strong>'._DATABASE.':</strong> '.$database.'</span><br /><br />Here are the results of your '.strtolower($type).'<br /><br />
			<table border="0" cellpadding="2"><tr bgcolor="'.$bgcolor2.'">';
			for ($j=0; $j<$numfields; $j++) {
				echo '<td><strong>'.$db->sql_fieldname($j, $result).'</strong></td>';
			}
			echo '</tr>';
			$bgcolor = $bgcolor3;
			while ($row = $db->sql_fetchrow($result)) {
				$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
				echo '<tr'.$bgcolor.'>';
				for($j=0; $j<$numfields; $j++) {
					echo '<td>'.$row[$j].'</td>';
				}
				echo '</tr>';
			}
			echo '</table>';
		}
		CloseTable();
}

switch ($mode) {
	
	case 'BackupDB':
		if (empty($tablelist)) { cpg_error('No tables found'); }
		require_once(CORE_PATH.'classes/sqlctrl.php');
		SQLCtrl::backup($database, $tablelist, $filename, isset($_POST['dbstruct']), isset($_POST['dbdata']), isset($_POST['drop']), isset($_POST['gzip']), $full);
		break;

	case 'OptimizeDB':
		if (DB_TYPE == 'postgresql') {
			$db->query('VACUUM ANALYZE');
			$query = 'SELECT cl.relname as tablename, st.* FROM pg_class AS cl, pg_statistic AS st WHERE st.starelid=cl.relfilenode AND cl.relkind IN(\'r\') AND cl.relname NOT LIKE \'pg_%\' AND cl.relname NOT LIKE \'sql_%\' ORDER by cl.relname';
		} else {
			$query = "$type TABLE $database.".implode(", $database.", $tablelist);
		}
		show($mode, $database, $tablelist, $query);
		break;

	case 'CheckDB':
		if (DB_TYPE == 'postgresql') {
			show($mode, $database, $tablelist, null);
			break;
		} else {
		 	$query = "$type TABLE $database.".implode(", $database.", $tablelist).' EXTENDED';
		}
		show($mode, $database, $tablelist, $query);
		break;
	case 'AnalyzeDB':
		if (DB_TYPE == 'postgresql') {
			$showblocks = 0;
			$MAIN_CFG['global']['admingraphic'] =~ 2;
			if ($MAIN_CFG['global']['admingraphic'] == 0)  $MAIN_CFG['global']['admingraphic'] = 4;
			$db->query = 'ANALYZE';
			$query = 'SELECT tablename, attname, null_frac, avg_width, n_distinct, most_common_vals, most_common_freqs, correlation FROM pg_stats WHERE schemaname=\''.$schema.'\' ORDER BY tablename';
			//$query = 'SELECT * FROM pg_statistic';
		} else {
			$query = "$type TABLE $database.".implode(", $database.", $tablelist);
		}
		show($mode, $database, $tablelist, $query);
		break;

	case 'RepairDB':
		if (DB_TYPE == 'postgresql') {
			$query = 'REINDEX '.$database;
		} else {
			$query = "$type TABLE $database.".implode(", $database.", $tablelist);
		}
		show($mode, $database, $tablelist, $query);
		break;

	case 'StatusDB':
		$showblocks = 0;
		if (DB_TYPE == 'postgresql') {
			$schema = $db->sql_ufetchrowset('SELECT current_schema()');
			$query = 'SELECT relname, seq_scan, seq_tup_read, idx_scan, idx_tup_fetch, n_tup_upd, n_tup_del FROM pg_stat_user_tables WHERE schemaname = \''.$schema[0][0].'\' ORDER BY relname';
		} else {
			$query = "SHOW TABLE STATUS FROM $database";
		}
		show($mode, $database, $tablelist, $query);
		break;
			
	case 'RestoreDB':
		require_once('header.php');
		GraphicAdmin('System');
		require_once(CORE_PATH.'classes/sqlctrl.php');
		if (!SQLCtrl::query_file($_FILES['sqlfile'], $error)) { cpg_error($error); }
		OpenTable();
		echo '<span class="genmed"><strong>'._DATABASE.': '.$database.'</strong></span><br /><br />Importation of <em>'.$_FILES['sqlfile']['name'].'</em> was successful';
		CloseTable();
		break;

	default:
		require_once('header.php');
		GraphicAdmin('System');
		OpenTable();
		echo '<form method="post" name="backup" action="'.adminlink().'" enctype="multipart/form-data" accept-charset="utf-8">
		<span class="genmed"><strong>'.((DB_TYPE == 'postgresql') ? 'Schema' : _DATABASE).': <select name="database">';
		// database listing
		$data = (DB_TYPE == 'postgresql') ? $db->list_schemas() : $db->list_databases();
		foreach ($data as $db_name) {
			$sel = ($database == $db_name)?' selected="selected"':'';
			echo "<option$sel>$db_name</option>";
		}
		echo '</select> <input type="submit" name="switchdb" value="Change" /></strong></span><br /><br />
		<table><tr><td>
		<select name="tablelist[]" size="20" multiple="multiple">';
		foreach ($tablelist as $table) {
			echo '<option value="'.$table.'">'.$table.'</option>';
		}
		echo '</select></td><td valign="middle">
		<label class="ulog" for="mode"><span class="genmed">Action</span></label><select name="mode" id="mode"
		onchange="dbback=document.getElementById(\'backuptasks\');dbback.style.display=(this.options[this.selectedIndex].value==\'BackupDB\') ? \'\' : \'none\';">
		<option value="AnalyzeDB">Analyze</option>
		<option value="BackupDB" selected="selected">'._SAVEDATABASE.'</option>
		<option value="CheckDB">Check</option>
		<option value="OptimizeDB">Optimize</option>
		<option value="RepairDB">Repair</option>
		<option value="StatusDB">Status</option>
		</select> <input type="submit" value="Go" /><br /><br /><div id="backuptasks" style="float: center;">Backup Tasks:<br />
		<input type="checkbox" value="1" name="dbdata" checked="checked" style="margin-left: 10px;" />Save Data<br />
		<input type="checkbox" value="1" name="dbstruct" checked="checked" style="margin-left: 10px;" />Include CREATE statement<br />
		<input type="checkbox" value="1" name="drop" checked="checked" style="margin-left: 10px;" />Include DROP statement<br />';
		if (extension_loaded('zlib')) {
			echo '<input type="checkbox" value="1" name="gzip" checked="checked" style="margin-left: 10px;" />Use GZIP compression';
		} else {
			echo 'GZIP Compression not supported';
		}
		echo '</div></td><td valign="top" width="50%">';

		OpenTable();
		echo '<div align="center" class="genmed"><strong>OPTIMIZE</strong></div><br /><div align="justify">Should be used if you have deleted a large part of a table or if you have made many changes to a table with variable-length rows (tables that have VARCHAR, BLOB, or TEXT columns). Deleted records are maintained in a linked list and subsequent INSERT operations reuse old record positions. You can use OPTIMIZE to reclaim the unused space and to defragment the datafile.<br />
In most setups you don\'t have to run OPTIMIZE at all. Even if you do a lot of updates to variable length rows it\'s not likely that you need to do this more than once a month/week and only on certain tables.</div><br />
OPTIMIZE works in the following way:<ul>
<li>If the table has deleted or split rows, repair the table.</li>
<li>If the index pages are not sorted, sort them.</li>
<li>If the statistics are not up to date (and the repair couldn\'t be done by sorting the index), update them.</li>
</ul><strong>Note:</strong> the table is locked during the time in which OPTIMIZE is running!';
		CloseTable();
		echo '</td></tr></table></form><br /><br />
		<span class="genmed"><strong>Import SQL File</strong></span><br /><br />
		<form method="post" action="'.adminlink().'" name="restore" enctype="multipart/form-data" accept-charset="utf-8">
		<input type="file" name="sqlfile" size="100" /> <input type="hidden" name="mode" value="RestoreDB" /><input type="submit" value="Import" />
		</form>';
		CloseTable();

		break;
}
