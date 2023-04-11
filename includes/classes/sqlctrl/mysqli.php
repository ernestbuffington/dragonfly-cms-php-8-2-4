<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('DBCtrl')) { exit; }

class SQLCtrl extends DBCtrl {

	public static function backup($database, $tables, $filename, $structure=true, $data=true, $drop=true, $compress=true, $full=false)
	{
		if (!is_array($tables) || empty($tables)) {
			trigger_error('No tables to backup', E_USER_WARNING);
			return false;
		}
		$crlf = "\n";
		$esc = '#';
		# doing some DOS-CRLF magic...
		# this looks better under WinX
		if (preg_match('#[^(]*\((.*)\)[^)]*#',$_SERVER['HTTP_USER_AGENT'],$regs)) {
			if (false !== stripos($regs[1], 'Win')) { $crlf = "\r\n"; }
		}

		if (GZIPSUPPORT) {
			\Dragonfly::ob_clean();
		} else {
			$compress = false;
		}
		self::$filename = $filename;
		if ($compress) {
			$filename .= '.gz';
			header("Content-Type: application/x-gzip; name=\"$filename\"");
		} else {
			header("Content-Type: text/x-delimtext; name=\"$filename\"");
		}
		header("Content-disposition: attachment; filename=$filename");

		self::output("$esc ========================================================$crlf"
			."$esc$crlf"
			."$esc Database : $database$crlf"
			."$esc "._ON." ".date('Y-m-d H:i:s')." !$crlf"
			."$esc$crlf"
			."$esc ========================================================$crlf"
			."$crlf", $compress);
		set_time_limit(0);
		foreach ($tables AS $table) {
			if ($structure) {
				self::output("$crlf$esc$crlf"."$esc Table structure for table '$table'$crlf"."$esc$crlf$crlf", $compress);
				self::output(SQLCtrl::get_table_struct($database, $table, $crlf, $drop).";$crlf$crlf", $compress);
			}
			if ($data) {
				self::output("$crlf$esc$crlf"."$esc Dumping data for table '$table'$crlf"."$esc$crlf$crlf", $compress);
				SQLCtrl::get_table_content($database, $table, $crlf, false, true, $compress);
			}
		}
		self::output('', $compress, true);
		exit;
	}

	// Return $table's CREATE definition
	// Returns a string containing the CREATE statement on success
	protected static function get_table_struct($database, $table, $crlf, $drop)
	{
		$db = \Dragonfly::getKernel()->SQL;
		$schema_create = '';
		if ($drop) { $schema_create .= "DROP TABLE IF EXISTS $table;$crlf"; }
		$schema_create .= "CREATE TABLE $table ($crlf";

		$result = $db->list_columns($table, false);
		foreach ($result as $name => $row) {
			$schema_create .= "	{$name} {$row['Type']}";
			if (!empty($row['Default']) || $row['Default'] == '0') {
				$schema_create .= " DEFAULT '{$row['Default']}'";
			}
			if (!$row['Null']) { $schema_create .= ' NOT NULL'; }
			if (!empty($row['Extra'])) { $schema_create .= " {$row['Extra']}"; }
			$schema_create .= ",$crlf";
		}
		$schema_create = substr($schema_create, 0, -strlen(",$crlf"));

		$result = $db->list_indexes($table);
		foreach ($result as $key => $row) {
			$schema_create .= ",$crlf\t";
			if ($row['type']) {
				$schema_create .= "{$row['type']} {$key}";
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
	protected static function get_table_content($database, $table, $crlf, $complete=false, $echo=false, $compress=false)
	{
		$db = \Dragonfly::getKernel()->SQL;
		$str = $fields = '';
		$result = $db->query("SELECT * FROM $table");
		$fieldcount = $result->field_count;
		if ($complete) {
			$fields = array();
			for ($j=0; $j<$fieldcount; ++$j) {
				$fields[] = $result->fetch_field_direct($j)->name;
			}
			$fields = '('.implode(', ', $fields).') ';
		}
		while ($row = $result->fetch_row()) {
			foreach ($row as $j => $col) {
				$row[$j] = isset($col) ? $db->quote($col) : 'NULL';
			}
			$str .= "INSERT INTO {$table} {$fields} VALUES (" . implode(',',$row) . ");{$crlf}";
			if ($echo) {
				self::output($str, $compress);
				$str = '';
			}
		}
		$result->free();
		return $str;
	}
}
