<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL\Manager;

class MySQL implements \Poodle\SQL\Interfaces\Manager
{
	protected $SQL;

	function __construct(\Poodle\SQL $SQL) { $this->SQL = $SQL; }

	public function listDatabases()
	{
		$result = $this->SQL->query('SHOW DATABASES');
		$databases = array();
		while (list($name) = $result->fetch_row()) { $databases[$name] = $name; }
		return $databases;
	}

	public function listTables($detailed=false)
	{
		$tables = array();
		if ($detailed) {
			# SELECT TABLE_NAME, TABLE_COMMENT, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='poodle' AND TABLE_TYPE='BASE TABLE'
			$result = $this->SQL->query('SHOW TABLE STATUS'); // v5: WHERE Engine IS NOT NULL
			while ($row = $result->fetch_assoc()) {
				if ($row['Engine']) {
					$tables[] = array(
						'name'    => $row['Name'],
						'comment' => preg_replace('#InnoDB free:.*#','',$row['Comment']),
						'engine'  => $row['Engine'],
					);
				}
			}
		} else {
			$result = $this->SQL->query('SHOW'
				.(version_compare($this->SQL->server_info, '5.0.2', '>=') ? ' FULL' : '')
				.' TABLES');
			while ($row = $result->fetch_row()) {
				if (!isset($row[1]) || 'BASE TABLE' === $row[1]) { $tables[] = $row[0]; }
			}
		}
		return $tables;
	}

	public function listColumns($table, $full=true)
	{
		// TODO: issue with: DEFAULT 'CURRENT_TIMESTAMP'

		$full = $full?'FULL':'';
		/*SELECT
			column_name,
			CASE WHEN 'auto_increment'=extra THEN REPLACE(data_type,'int','serial') ELSE data_type END AS type,
			character_maximum_length AS length,
			CASE WHEN collation_name LIKE '%_bin' THEN 1 ELSE 0 END AS 'binary',
			CASE WHEN is_nullable!='NO' THEN 1 ELSE 0 END AS notnull,
			column_default AS 'default',
			column_comment AS comment
		 FROM information_schema.columns WHERE table_schema='{$this->SQL->database}' AND table_name='{$table}' ORDER BY ordinal_position*/
		if ($result = $this->SQL->query("SHOW {$full} COLUMNS FROM {$table}"))
		{
			$return = array();
			$re_cb = fn($m) => strtoupper($m[1]);
			while ($row = $result->fetch_assoc()) {
				$row['Type'] = preg_replace_callback('#^([a-z\s]+)#', $re_cb, $row['Type']);
				$row['Type'] = str_replace(' unsigned', '', $row['Type']);
				if ($full && strpos($row['Collation'], '_bin')) { $row['Type'] .= ' BINARY'; }
				if (false !== strpos($row['Type'], 'INT(')) {
					$row['Type'] = preg_replace('#INT\(\d+\)#', 'INT', $row['Type']);
				}
				if ('auto_increment' === $row['Extra']) {
					$row['Type'] = (strpos($row['Type'], 'BIGINT') === false) ? 'SERIAL' : 'BIGSERIAL';
					$row['Default'] = null;
				}
				$row['Type'] = str_replace('DECIMAL','NUMERIC',$row['Type']);
				$row['Type'] = str_replace('LONGTEXT','TEXT',$row['Type']);
				$row['Type'] = str_replace('LONGBLOB','BLOB',$row['Type']);
				$return[$row['Field']] = array(
					'type'  => $row['Type'],
					'notnull' => $row['Null'] === 'NO',
					'default' => $row['Default'],
					'comment' => $full ? $row['Comment'] : null,
					'extra' => null // strtoupper($row['Extra'])
				);
			}
			return $return;
		}
		return false;
	}

	public function listIndices($table)
	{
		$return = array();
/*		SELECT constraint_name, column_name, constraint_type
		FROM information_schema.key_column_usage
		LEFT JOIN information_schema.table_constraints USING (constraint_name, table_schema, table_name)
		WHERE table_schema='{$this->SQL->database}' AND table_name='{$table}' ORDER BY ordinal_position*/
		if ($result = $this->SQL->query('SHOW INDEX FROM '.$table)) {
			while ($row = $result->fetch_assoc()) {
				$key = $row['Key_name'];
				if ('PRIMARY' === $key) {
					$return[$key]['type'] = 'PRIMARY';
				} else if (empty($row['Non_unique'])) {
					$return[$key]['type'] = 'UNIQUE';
				} else {
					$return[$key]['type'] = ('FULLTEXT'==$row['Index_type']?'FULLTEXT':''); # BTREE or FULLTEXT
				}
				$col = $row['Column_name']/*(int)$row['Seq_in_index']-1*/;
				$return[$key]['columns'][$col] = $col.($row['Sub_part']?'('.$row['Sub_part'].')':'');
			}
		}
		return $return;
	}

	public function listForeignKeys($table)
	{
		$return = array();
		try {
			// Don't JOIN, it makes the query very slow!
			if ($result = $this->SQL->query("SELECT constraint_name, referenced_table_name, delete_rule, update_rule
			FROM information_schema.referential_constraints
			WHERE constraint_schema='{$this->SQL->database}' AND table_name='{$table}'"))
			{
				while ($row = $result->fetch_row()) {
					$key = $row[0];
					$return[$key]['references'] = $row[1];
					$return[$key]['ondelete']   = $row[2];
					$return[$key]['onupdate']   = $row[3];
					$return[$key]['columns']    = array();
					$cols = $this->SQL->query("SELECT column_name, referenced_column_name
					FROM information_schema.key_column_usage
					WHERE table_schema='{$this->SQL->database}'
					  AND table_name='{$table}'
					  AND constraint_name='{$row[0]}'
					ORDER BY ordinal_position");
					while ($col = $cols->fetch_row()) {
						$return[$key]['columns'][$col[0]] = $col[1];
					}
				}
			}
		} catch (\Poodle\SQL\Exception $e) {}
		return $return;
	}

	public function listTriggers($table)
	{
		if ($result = $this->SQL->query("SELECT trigger_name, action_timing, event_manipulation, action_statement FROM information_schema.triggers
		WHERE event_object_schema='{$this->SQL->database}' AND event_object_table='{$table}'")) {
			$return = array();
			while ($row = $result->fetch_row()) {
				$return[$row[0]] = array(
					'name'  =>$row[0],
					'timing'=>$row[1],
					'event' =>$row[2],
					'statement'=>$row[3],
				);
			}
			return $return;
		}
		return false;
	}

	public function listViews()
	{
		try {
			if ($result = $this->SQL->query("SELECT table_name FROM information_schema.views WHERE table_schema='{$this->SQL->database}'")) {
				$return = array();
				while ($row = $result->fetch_row()) { $return[] = $row[0]; }
				return $return;
			}
		} catch (\Poodle\SQL\Exception $e) {}
		return false;
	}

	public function listFunctions()  { return $this->list_definition('FUNCTION'); }
	public function listProcedures() { return $this->list_definition('PROCEDURE'); }
	private function list_definition($type/*FUNCTION|PROCEDURE*/)
	{
		# SELECT routine_name FROM information_schema.routines WHERE routine_schema='{$this->SQL->database}' AND routine_type='{$type}'
		if ($result = $this->SQL->query("SHOW {$type} STATUS WHERE Db='{$this->SQL->database}'")) {
			$return = array();
			while ($row = $result->fetch_assoc()) { $return[] = $row['Name']; }
			return $return;
		}
		return false;
	}
/*
	public function getView($name)
	{
		if ($result = $this->SQL->query("SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA='{$this->SQL->database}' AND TABLE_NAME='{$name}'")) {
			if ($row = $result->fetch_row()) {
				return array('definition' => trim(str_replace('`','',$row[0])));
			}
		}
		return false;
	}
*/
	public function getView     ($name) { return $this->getMySQLDefinitionFor('VIEW', $name); }
	public function getFunction ($name) { return $this->getMySQLDefinitionFor('FUNCTION', $name); }
	public function getProcedure($name) { return $this->getMySQLDefinitionFor('PROCEDURE', $name); }
	private function getMySQLDefinitionFor($type/*FUNCTION|PROCEDURE|VIEW*/, $name)
	{
		# CREATE DEFINER=`root`@`localhost` $type `$name`(dtstart DATETIME, dtend DATETIME) RETURNS double(5,1)
		try {
			if ($result = $this->SQL->query("SHOW CREATE {$type} {$name}")) {
				if ($row = $result->fetch_assoc()) {
					$row = str_replace('`','',$row);
					# CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_*` AS select
					if (preg_match('#^CREATE[^\r\n]+?(\([^\r\n]*\)| AS )(?:\s+RETURNS\s+([^\s]+)\s+)?(.*)$#Ds', $row['Create '.ucfirst(strtolower($type))], $match)) {
						$params = array();
						if ($match[1] && preg_match_all('#[\(,]\s*(IN|OUT|INOUT)?\s*([a-zA-Z0-9_]+)\s+([a-zA-Z0-9]+)(?:\(([^\(\)]+)\))?#s', $match[1], $m, PREG_SET_ORDER)) {
							foreach ($m as $p) $params[] = array(
								'dir' =>$p[1],
								'name'=>$p[2],
								'type'=>$p[3],
								'length'=>$p[4],
							);
						}
						return array(
							'parameters' => $params,
							'returns'    => strtoupper($match[2]),
							'definition' => trim(preg_replace('#^BEGIN(.+)END$#Dsi','$1',preg_replace('#(\s|[^\s]+\.)([^\s\.]+)\s+AS\s+\\2#','$1$2',preg_replace('#\s+AS\s+[a-z_]+\(.*?\)#i', '$1', trim($match[3])))))
						);
					}
				}
			}
		} catch (\Exception $e){}
		return false;
	}

	public function getTableInfo($name)
	{
		# SELECT TABLE_NAME, TABLE_COMMENT, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='poodle' AND TABLE_TYPE='BASE TABLE'
		$row = $this->SQL->query("SHOW TABLE STATUS LIKE '$name'"); // v5: WHERE Engine IS NOT NULL
		$row = $row->fetch_assoc();
		if ($row['Engine']) {
			return array(
				'name'    => $row['Name'],
				'comment' => preg_replace('#InnoDB free:.*#','',$row['Comment']),
				'engine'  => $row['Engine'],
			);
		}
	}

	public function analyze($table=null)  { return $this->SQL->query('ANALYZE TABLE ' .($table?:implode(', ', $this->listTables()))); }
	public function check($table=null)    { return $this->SQL->query('CHECK TABLE '   .($table?:implode(', ', $this->listTables()))); }
	public function optimize($table=null) { return $this->SQL->query('OPTIMIZE TABLE '.($table?:implode(', ', $this->listTables()))); }
	public function repair($table=null)   { return $this->SQL->query('REPAIR TABLE '  .($table?:implode(', ', $this->listTables()))); }

	public function tablesStatus()   { return $this->SQL->query('SHOW TABLE STATUS'); }
	public function serverStatus()   { return $this->SQL->uFetchAll('SHOW STATUS', \Poodle\SQL::NUM); }
	public function serverProcesses(){ return $this->SQL->uFetchAll('SHOW PROCESSLIST'); }

	public function setSchemaCharset()
	{
		$v = $this->SQL->get_charset();
		$this->SQL->query("ALTER DATABASE CHARACTER SET {$v} COLLATE {$v}_bin");
	}
}
