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
		$db = \Dragonfly::getKernel()->SQL;
		$schema = $database;
		if (!is_array($tables) || empty($tables)) {
			trigger_error('No tables to backup', E_USER_WARNING);
			return false;
		}
		$crlf = "\n";
		$current_user = $db->uFetchRow('SELECT CURRENT_USER');
		$current_user = $current_user[0];
		//$search_path = $db->uFetchRow('SELECT current_schemas(true)');
		//$search_path = preg_replace('#^{(.*?)}$#', '\\1', $search_path[0]);
		# doing some DOS-CRLF magic...
		# this looks better under WinX
		if (preg_match('#[^(]*\((.*)\)[^)]*#', $_SERVER['HTTP_USER_AGENT'], $regs)) {
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

		$controls = "--$crlf-- PostgreSQL dump : $database$crlf"
			."-- "._ON." ".date('Y-m-d H:i:s')." !$crlf--$crlf$crlf"
			."SET client_encoding = '".pg_client_encoding()."';$crlf"
			."SET check_function_bodies = false;$crlf"
			."SET SESSION AUTHORIZATION '$current_user';$crlf$crlf";
		if ($full) {
			if ($drop) {
				$controls .='DROP SCHEMA '.$schema.' CASCADE;'.$crlf;
			}
			$controls .="CREATE SCHEMA $schema AUTHORIZATION $current_user;$crlf"
				."REVOKE ALL ON SCHEMA $schema FROM PUBLIC;$crlf"
				.'ALTER USER '.$current_user.' SET search_path TO '.$schema.";$crlf"
				."$crlf";
		}
		self::output($controls, $compress);
		set_time_limit(0);
		if ($drop && !$full) {
			SQLCtrl::drop_table_struct($schema, $tables, $crlf, $compress);
		}
		if ($structure) {
			if ($full) {
				self::output(SQLCtrl::get_function($schema, $crlf), $compress);
			}
			SQLCtrl::get_table_struct($schema, $tables, $crlf, $compress);
		}
		if ($data) {
			SQLCtrl::get_table_content($schema, $tables, $crlf, false, $compress);
		}
		if ($structure) {
			SQLCtrl::get_index($schema, $tables, $crlf, $compress);
			self::output(SQLCtrl::get_sequence($schema, $tables, $crlf, $full), $compress);
		}
		self::output($crlf.'VACUUM ANALYZE;', $compress);
		self::output('', $compress, true);
		exit;
	}

	protected static function get_index($schema, $tables, $crlf, $compress)
	{
		//pg_get_constraintdef(constraint_oid)
		$db = \Dragonfly::getKernel()->SQL;
		foreach ($tables as $table) {
			$indexes = $db->list_indexes($table);
			if (!count($indexes)) continue;
			$list = $crlf.'--'.$crlf.'-- Index and Constraint for table '.$table.$crlf.'--'.$crlf;
			foreach ($indexes as $relname => $data) {
//				$row = $db->uFetchRow("SELECT pg_get_indexdef($data[oid])");
//				self::output($list.$row[0].';', $compress);
				if ($relname != 'PRIMARY') {
					$columns = array();
					foreach ($data['columns'] as $dummy => $column_name) {
						$columns[] = $column_name['name'];
					}
					$columns = implode(', ', $columns);
					$list .= 'CREATE '.(($data['unique'] == 1) ? 'UNIQUE ' : '') .'INDEX '.$table.'_'.$relname.' ON '.$table.' USING btree ('.$columns.');'.$crlf;
				}
			}
			if (isset($indexes['PRIMARY'])) {
				$columns = array();
				foreach ($indexes['PRIMARY']['columns'] as $dummy => $column_name) {
					$columns[] = $column_name['name'];
				}
				$columns = implode(', ', $columns);
				$list .= "ALTER TABLE ONLY $table$crlf\t ADD CONSTRAINT ".$table."_pkey PRIMARY KEY ($columns);$crlf";
			}
			self::output($list, $compress);
			$list = '';
		}
		return;
	}

	protected static function drop_table_struct($schema, $tables, $crlf, $compress)
	{
		$db = \Dragonfly::getKernel()->SQL;
		foreach ($tables as $table) {
			$schema_create = $crlf.'--'.$crlf.'-- Table structure for table '.$table.$crlf.'--'.$crlf;
			$indexes = $db->list_indexes($table);
			if (0 < count($indexes)) {
				if (isset($indexes['PRIMARY'])) {
					$schema_create .='ALTER TABLE ONLY '.$schema.'.'.$table.' DROP CONSTRAINT '.$table."_pkey;$crlf";
					unset($indexes['PRIMARY']);
				}
				foreach ($indexes as $relname => $data) {
					$schema_create .= 'DROP INDEX '.$schema.'.'.$table.'_'.$relname.";$crlf";
				}
			}
			$schema_create .= 'DROP TABLE '.$schema.'.'.$table.';'.$crlf;
			self::output($schema_create, $compress);
		}
		return;
	}

	// Return $table's CREATE definition
	// Returns a string containing the CREATE statement on success
	protected static function get_table_struct($schema, $tables, $crlf, $compress)
	{
		$db = \Dragonfly::getKernel()->SQL;
		foreach ($tables as $table) {
			$schema_create = $crlf.'--'.$crlf.'-- Table structure for table '.$table.$crlf.'--'.$crlf;
			$schema_create .= "CREATE TABLE $table ($crlf";
			$result = $db->list_columns($table, false, true);
			foreach ($result as $name => $row) {
				$schema_create .= "	{$name} {$row['Type']}";
				if (!empty($row['Default']) || $row['Default'] == '0') {
					$schema_create .= " DEFAULT {$row['Default']}";
				}
				if (!$row['Null']) { $schema_create .= ' NOT NULL'; }
				if (!empty($row['Extra'])) { $schema_create .= " {$row['Extra']}"; }
				if (next($result)) {
					$schema_create .= ",$crlf";
				} else {
					$schema_create .= "$crlf";
				}
			}
			$schema_create .= ') WITHOUT OIDS;'.$crlf;
			self::output($schema_create, $compress);
		}
		return;
	}

	// Get the content of $table as a series of INSERT statements.
	protected static function get_table_content($schema, $tables, $crlf, $complete, $compress)
	{
		$db = \Dragonfly::getKernel()->SQL;
		foreach ($tables as $table) {
			$str = $fields = '';
			$result = $db->query("SELECT * FROM $schema.$table");
			$fieldcount = $result->field_count;
			if ($complete) {
				$fields = array();
				for ($j=0; $j<$fieldcount;$j++) {
					$fields[] = $result->fetch_field_direct($j)->name;
				}
				$fields = '('.implode(', ', $fields).') ';
			}
			while ($row = $result->fetch_row()) {
				$str .= "INSERT INTO $table $fields VALUES (";
				for ($j=0; $j<$fieldcount;$j++) {
					if ($j > 0) $str .= ', ';
					# Can't use addslashes() as we don't know the value of magic_quotes_sybase.
					if (!isset($row[$j])) { $str .= 'NULL'; }
					else if ($row[$j]) { $str .= "'".$db->escape_string($row[$j])."'"; }
					else { $str .= "''"; }
				}
				$str .= ");$crlf";
				self::output($str, $compress);
				$str = '';
			}
			$result->free();
		}
		return;
	}

	protected static function get_function($schema, $crlf)
	{
		$db = \Dragonfly::getKernel()->SQL;
		$ary_function = $db->uFetchAll(
			"SELECT
				proname,
				proisstrict AS strict,
				provolatile AS volatile,
				prosrc AS definition,
				pt.typname AS proresult,
				pl.lanname AS prolanguage,
				proname || '(' || oidvectortypes(pc.proargtypes) || ')' AS proproto,
				CASE WHEN proretset THEN 'setof '::text ELSE '' END || pt.typname AS proreturns,
				usename as proowner
			FROM
				pg_proc pc, pg_user pu, pg_type pt, pg_language pl
			WHERE
				pc.proowner = pu.usesysid
				AND pronamespace = (SELECT oid FROM pg_namespace WHERE nspname='$schema')
				AND pc.prorettype = pt.oid
				AND pc.prolang = pl.oid
			ORDER BY proname, proresult");
		if (empty($ary_function)) return;
		$list = $crlf.'--'.$crlf.'-- Function structure'.$crlf.'--'.$crlf;
		$db->load_manager();
		foreach($ary_function as $func) {
			$provolatile = array('v'=>'', 'i'=>' IMMUTABLE', 's'=>' STABLE');
			$strict = array('f'=>'', 't'=>' STRICT');
			$uni = strtoupper($func['proresult']);
			$definition = str_replace("'", "''", $func['definition']);
			$result = isset($db->mngr->fields[$uni]) ? strtolower($db->mngr->fields[$uni]) : $func['proresult'];
			$list .= "CREATE OR REPLACE FUNCTION $func[proproto] RETURNS $result$crlf\t"
				."AS '$definition'$crlf\t"
				.'LANGUAGE '.$func['prolanguage'].''.$provolatile[$func['volatile']].''.$strict[$func['strict']].";$crlf$crlf";
		}
		return $list;
	}

	protected static function get_sequence($schema, $tables, $crlf, $full)
	{
		$db = \Dragonfly::getKernel()->SQL;
		$list = '';
		if ($full) {
			$sequence = $db->list_sequences($schema);
			foreach ($sequence as $seq_name => $seq_data) {
				$list .= "$crlf--$crlf-- SEQUENCE SET $seq_name$crlf--$crlf"
					."SELECT pg_catalog.setval('$seq_name', {$seq_data['last_value']}, {$seq_data['is_called']});$crlf";
			}
		} else {
			foreach ($tables as $table) {
				if ($sequence = $db->get_sequence($table)) {
				$list .= "$crlf--$crlf-- SEQUENCE SET {$sequence['seqname']}$crlf--$crlf"
					."SELECT pg_catalog.setval('{$sequence['seqname']}', {$sequence['last_value']}, {$sequence['is_called']});$crlf";
				}
			}
		}
		return $list;
	}

}
