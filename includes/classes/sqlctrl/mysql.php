<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/sqlctrl/mysql.php,v $
  $Revision: 1.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:43 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

class SQLCtrl extends DBCtrl {

	function backup($database, $tables, $filename, $structure=true, $data=true, $drop=true, $compress=true, $full=false)
	{
		if (!is_array($tables) || empty($tables)) {
			trigger_error('No tables to backup', E_USER_WARNING);
			return false;
		}
		$crlf = "\n";
		$esc = ((SQL_LAYER == 'postgresql') ? '--': '#');
		# doing some DOS-CRLF magic...
		# this looks better under WinX
		if (ereg('[^(]*\((.*)\)[^)]*',$_SERVER['HTTP_USER_AGENT'],$regs)) {
			if (eregi('Win', $regs[1])) { $crlf = "\r\n"; }
		}

		if (GZIPSUPPORT) {
			while (ob_end_clean());
			header('Content-Encoding: ');
		} else {
			$compress = false;
		}
		if ($compress) {
			$filename .= '.gz';
			header("Content-Type: application/x-gzip; name=\"$filename\"");
		} else {
			header("Content-Type: text/x-delimtext; name=\"$filename\"");
		}
		header("Content-disposition: attachment; filename=$filename");

		DBCtrl::output("$esc ========================================================$crlf"
			."$esc$crlf"
			."$esc Database : $database$crlf"
			."$esc "._ON." ".formatDateTime(gmtime(), _DATESTRING)." !$crlf"
			."$esc$crlf"
			."$esc ========================================================$crlf"
			."$crlf", $compress);
		set_time_limit(0);
		if (SQL_LAYER == 'mysql') $database = "`$database`";
		foreach ($tables AS $table) {
			if ($structure) {
				DBCtrl::output("$crlf$esc$crlf"."$esc Table structure for table '$table'$crlf"."$esc$crlf$crlf", $compress);
				DBCtrl::output(SQLCtrl::get_table_struct($database, $table, $crlf, $drop).";$crlf$crlf", $compress);
			}
			if ($data) {
				DBCtrl::output("$crlf$esc$crlf"."$esc Dumping data for table '$table'$crlf"."$esc$crlf$crlf", $compress);
				SQLCtrl::get_table_content($database, $table, $crlf, false, true, $compress);
			}
		}
		if ($compress) { DBCtrl::output('', true, true); }
		exit;
	}

	// Return $table's CREATE definition
	// Returns a string containing the CREATE statement on success
	function get_table_struct($database, $table, $crlf, $drop)
	{
		global $db;
		$schema_create = '';
		if ($drop) { $schema_create .= "DROP TABLE IF EXISTS $table;$crlf"; }
		$schema_create .= "CREATE TABLE $table ($crlf";

		$result = $db->list_columns("$database.$table", false);
		foreach ($result as $row) {
			$schema_create .= "	$row[Field] $row[Type]";
			if (!empty($row['Default']) || $row['Default'] == '0')
				$schema_create .= " DEFAULT '$row[Default]'";
			if (!$row['Null']) $schema_create .= ' NOT NULL';
			if (!empty($row['Extra'])) $schema_create .= " $row[Extra]";
			$schema_create .= ",$crlf";
		}
		$schema_create = ereg_replace(",$crlf".'$', '', $schema_create);

		$result = $db->list_indexes("$database.$table");
		foreach ($result as $key => $row) {
			$schema_create .= ",$crlf\t";
			if ($key == 'PRIMARY') {
				$schema_create .= 'PRIMARY KEY';
			} else if ($row['unique']) {
				$schema_create .= "UNIQUE $key";
			} else if ($row['type'] == 'FULLTEXT') {
				$schema_create .= "FULLTEXT $key";
			} else {
				$schema_create .= "KEY $key";
			}
			$columns = array();
			foreach ($row['columns'] as $field) { $columns[] = $field['name']; }
			$schema_create .= ' ('.implode(', ', $columns).')';
		}

		return $schema_create."$crlf)";
	}

	// Get the content of $table as a series of INSERT statements.
	function get_table_content($database, $table, $crlf, $complete=false, $echo=false, $compress=false)
	{
		global $db;
		$str = $fields = '';
		$result = $db->sql_query("SELECT * FROM $database.$table");
		$fieldcount = $db->sql_numfields($result);
		if ($complete) {
			$fields = array();
			for ($j=0; $j<$fieldcount;$j++) {
				$fields[] = $db->sql_fieldname($j, $result);
			}
			$fields = '('.implode(', ', $fields).') ';
		}
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$str .= "INSERT INTO $table $fields VALUES (";
			for ($j=0; $j<$fieldcount;$j++) {
				if ($j > 0) $str .= ', ';
				# Can't use addslashes() as we don't know the value of magic_quotes_sybase.
				if (!isset($row[$j])) { $str .= 'NULL'; }
				elseif ($row[$j] != '') { $str .= "'".$db->escape_string($row[$j])."'"; }
				else { $str .= "''"; }
			}
			$str .= ");$crlf";
			if ($echo) {
				DBCtrl::output($str, $compress);
				$str = '';
			}
		}
		$db->sql_freeresult($result);
		return $str;
	}
}
