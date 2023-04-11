<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\SQL\Manager;

if (!defined('DBFT_INT4')) define('DBFT_INT4', 'INT4');
if (!defined('DBFT_INT3')) define('DBFT_INT3', DBFT_INT4);
if (!defined('DBFT_INT2')) define('DBFT_INT2', 'INT2');
if (!defined('DBFT_INT1')) define('DBFT_INT1', DBFT_INT2);
if (!defined('DBFT_VARBINARY')) define('DBFT_VARBINARY', 'BYTEA');
if (!defined('DBFT_BLOB')) define('DBFT_BLOB', DBFT_VARBINARY);
if (!defined('DBFT_INDEX_FULLTEXT')) define('DBFT_INDEX_FULLTEXT', 'BTREE');//gist tsvector
if (!defined('DBFT_BOOL')) define('DBFT_BOOL', 'INT2'); //tmp workaround, pgsql BOOL returns t/f instead of 1/0

class Postgresql extends \Poodle\SQL\Manager\PostgreSQL
{

	//
	// Constructor
	//
	function __construct(\Poodle\SQL $SQL)
	{
		parent::__construct($SQL);
		$this->fields = array(
			'SERIAL4' => 'SERIAL NOT NULL',
			'SERIAL8' => 'BIGSERIAL NOT NULL',
			'TEXT' => 'TEXT',       # unlimited
			'BOOL' => 'BOOLEAN',
			'INT2' => 'SMALLINT',
			'INT4' => 'INTEGER',
			'INT8' => 'BIGINT',
			'CHAR' => 'CHARACTER',
			'VARBINARY' => 'BYTEA',
			'VARCHAR' => 'VARCHAR', # alias for CHARACTER VARYING
			'FLOAT4' => 'REAL',
			'FLOAT8' => 'DOUBLE PRECISION',
			'DECIMAL' => 'NUMERIC'
		);
	}
	private function create_patterns()
	{
		if (!empty($this->query_pattern)) { return; }
		# fix uniform field types to DB specific types in ALTER TABLE and CREATE TABLE
		$this->query_pattern = array('/[\s]UNSIGNED[\s]/s');
		$this->query_replace = array(' ');
		foreach ($this->fields as $uni => $field) {
			# if we don't use this then everything messes up
			$this->query_pattern[] = "/ $uni($|[,\ \(])/s";
			$this->query_replace[] = " $field\\1";
		}
		# PostgreSQL doesn't need any length specification after BYTEA, INTEGER and SMALLINT types
		$this->query_pattern[] = "/BYTEA([\ \(].*?\))/s";
		$this->query_replace[] = "BYTEA";
	}

	public function get_versions()
	{
		$version['engine'] = 'PostgreSQL';
		$version['client'] = 'N/A';
		$version['server'] = 'N/A';
		$version = array_merge($version, pg_version($this->SQL->connect_id));
		if ($version['server'] == 'N/A') { //pgsql not compiled into php
			$version['server'] = pg_parameter_status('server_version');//pgsql 7.4+
		}
		return $version;
	}

	public function get_details()
	{
		$details = $this->get_versions();
		$details['unicode'] = true;
		$details['character_set_client'] = pg_client_encoding();
		return $details;
	}

	public function create_table($query)
	{
		$this->create_patterns();
		$query = preg_replace($this->query_pattern, $this->query_replace, $query);
		if (preg_match('#,[\s]+((UNIQUE[\s]+|)KEY[\s].*)\)#si', $query, $matches)) {
			$matches[0] = substr($matches[0],0,-1);
			$query = str_replace($matches[0], '', $query);
			$ret = $this->SQL->query('CREATE TABLE '.$query.' WITHOUT OIDS');
			if ($ret) {
				$table = trim(substr($query, 0, strpos($query,'(')));
				preg_match_all('#,[\s]+(UNIQUE[\s]+|)KEY[\s]+([a-z]+)[\s]+(\([\(\)a-z0-9_, ]+\))#si', $matches[0], $matches, PREG_SET_ORDER);
				foreach ($matches as $index) {
					if (!$this->SQL->query("CREATE $index[1] INDEX {$table}_$index[2] ON $table USING btree $index[3]")) {
						return false;
					}
				}
			}
			return $ret;
		}
		return $this->SQL->query('CREATE TABLE '.$query.' WITHOUT OIDS');
	}

	public function alter_table($query)
	{
		$this->create_patterns();
		$query = preg_replace($this->query_pattern, $this->query_replace, $query);
		return $this->SQL->query('ALTER TABLE '.$query);
	}

	public function drop_table($table)
	{
		return $this->SQL->query('DROP TABLE '.$table);
	}

	public function list_databases()
	{
		return array($this->SQL->database => $this->SQL->database);
	}

	public function get_current_schema()
	{
		$schema = $this->SQL->uFetchRow('SELECT current_schema()');
		return $schema[0];
	}

	public function list_schemas()
	{
		$result = $this->SQL->query('
			SELECT
				nspname
			FROM
				pg_namespace
			WHERE
				nspowner=(SELECT usesysid FROM pg_user WHERE usename=current_user)');
		{
			while (list($name) = pg_fetch_row($result)) {
				$schemas[] = $name;
			}
		}
		return $schemas;
	}

	/* note:
	postgresql cannot read a database that is not the "connected database"
	tools to query an "external database" are requierd:
	http://developer.postgresql.org/cvsweb.cgi/pgsql/contrib/dblink/
	http://pgfoundry.org/projects/db-link-tds/
	*/
	public function list_tables($schema=null)
	{
		$prefix = $this->SQL->TBL->prefix;
		$uprefix = \Dragonfly::getKernel()->db_user_prefix;
		if ($schema == '') {
			$schema = $this->get_current_schema();
		}
		$result = $this->SQL->query('
			SELECT
				tablename
			FROM
				pg_catalog.pg_tables ct,
				information_schema.tables it
			WHERE
				ct.tableowner = current_user
				AND ct.tablename = it.table_name
				AND ct.schemaname=\''.$schema.'\'
			ORDER BY tablename' , SQL_ASSOC);
		$tables = array();
		while (list($name) = pg_fetch_row($result)) {
			$id = preg_replace("#^({$prefix}|{$uprefix})_#", '', $name);
			$tables[$id] = $name;
		}
		$this->SQL->free_result($result);
		return $tables;
	}

	public function list_columns($table, $uniform=true, $backup=false)
	{
		if ($result = $this->SQL->query("
			SELECT
				column_name,
				data_type,
				character_maximum_length,
				is_nullable,
				column_default
			FROM
				information_schema.columns
			WHERE
				table_name='$table'
			ORDER BY
				ordinal_position", defined('INSTALL'), true))
		{
			if (empty($this->type_pattern)) {
				$fields = $this->fields;
				$fields['VARCHAR'] = 'CHARACTER VARYING';
				$this->type_pattern = $this->type_replace = array();
				foreach ($fields as $uni => $field) {
					# if we don't use ^$ then everything messes up
					$this->type_pattern[] = "/^$field$/";
					$this->type_replace[] = $uni;
				}
			}
			$return = array();
			while ($row = pg_fetch_assoc($result)) {
				$field = $row['column_name'];
				$row['data_type'] = strtoupper($row['data_type']);
				# do we have an serial ?
				if (strpos($row['column_default'], 'nextval(') !== false) {
					if ($row['data_type'] == 'INTEGER') {
						$row['data_type'] = $uniform ? 'SERIAL4' : 'SERIAL';
					} else {
						$row['data_type'] = $uniform ? 'SERIAL8' : 'BIGSERIAL';
					}
					$row['column_default'] = '';
				} elseif ($uniform || $backup) {
					if (!$backup) $row['data_type'] = preg_replace($this->type_pattern, $this->type_replace, $row['data_type'], 1);
					if (strpos($row['data_type'], 'CHAR') !== false) {
						$row['data_type'] .= "({$row['character_maximum_length']})";
					}
				}
				$return[$field]['Field'] = $field;
				$return[$field]['Type'] = $row['data_type'];
				$return[$field]['Null'] = intval($row['is_nullable'] == 'YES');
				if ($backup) {
					$return[$field]['Default'] = $row['column_default'];
				} else {
					preg_match('/^(\(([\d]+)\)|([\d]+)|\'(.*)?\')(::.*)?.*/',$row['column_default'], $match);
					$return[$field]['Default'] = $match[2] ?: ($match[3] ?: $match[4]);
				}
			}
			$this->SQL->free_result($result);
			return $return;
		}
		return false;
	}

	public function list_indexes($table)
	{
		if ($result = $this->SQL->query("
			SELECT
				ic.relname AS index_name,
				ic.reltuples as called,
				bc.relname AS tab_name,
				ta.attname AS column_name,
				i.indisunique AS unique_key,
				i.indisprimary AS primary_key
			FROM
				pg_class bc,
				pg_class ic,
				pg_index i,
				pg_attribute ta,
				pg_attribute ia
			WHERE (bc.oid = i.indrelid)
				AND (ic.oid = i.indexrelid)
				AND (ia.attrelid = i.indexrelid)
				AND (ta.attrelid = bc.oid)
				AND (bc.relname = '$table')
				AND (ta.attrelid = i.indrelid)
				AND (ta.attnum = i.indkey[ia.attnum-1])
			ORDER BY
				index_name, tab_name, column_name", defined('INSTALL'), true))
		{
			$return = array();
			while ($row = pg_fetch_assoc($result)) {
				$row['index_name'] = str_replace($table.'_', '', $row['index_name']);
				$key = ($row['primary_key'] == 't') ? 'PRIMARY' : $row['index_name'];
				$return[$key]['name'] = $key;
				$return[$key]['unique'] = ($row['unique_key'] == 't') ? 1 : 0;
				$return[$key]['type'] = 'BTREE';
				$return[$key]['called'] = $row['called'];
				$return[$key]['columns'][] = array('name' => $row['column_name']);
			}
			$this->SQL->free_result($result);
			return $return;
		}
		return false;
	}

	public function alter_field($mode, $table, $field, $type='', $null=TRUE, $default=NULL)
	{
		$this->create_patterns();
		switch ($mode)
		{
			case 'add':
				if ($ret = $this->alter_table("$table ADD $field $type")) {
					if (isset($default)) {
						$query = "ALTER TABLE $table ALTER COLUMN $field SET DEFAULT '$default'";
						$query = preg_replace($this->query_pattern, $this->query_replace, $query);
						if ($ret = $this->SQL->query($query)) {
							$query = "UPDATE $table SET $field = '$default'";
							$ret = $this->SQL->query($query);
						}
					}
					if ($ret && !$null) {
						$query = "ALTER TABLE $table ALTER COLUMN $field SET NOT NULL";
						$query = preg_replace($this->query_pattern, $this->query_replace, $query);
						$ret = $this->SQL->query($query);
					}
				}
				return $ret;

			case 'drop':
				$query = "ALTER TABLE $table DROP $field";
				$query = preg_replace($this->query_pattern, $this->query_replace, $query);
				return $this->SQL->query($query);

			case 'change':
				if (!is_array($field)) $field = array($field, $field);
				if ($field[0] == $field[1]) {
					$ret = true;
				} else {
					$query = "ALTER TABLE $table RENAME COLUMN $field[0] TO $field[1]";
					$query = preg_replace($this->query_pattern, $this->query_replace, $query);
					$ret = $this->SQL->query($query);
				}
				if (false !== stripos($type, 'BYTEA')) {
					$ret = $result = $this->SQL->query("SELECT $field[1] FROM $table GROUP BY $field[1]");
					if ($ret && $this->SQL->num_rows($result) > 0) {
						$ret = $this->SQL->query("ALTER TABLE $table ADD COLUMN df_varbin_tmp BYTEA NULL DEFAULT NULL");
						if ($ret) {
							$t_indexes = $this->list_indexes($table);
							if (!isset($t_indexes[$field[1]])) { $ret = $this->SQL->alter_index('index', $table, $field[1], $field[1]); }
							$t_indexes = null;
						}
						if ($ret) {
							while ($row = $result->fetch_row()) {
								$ip = inet_pton(\Dragonfly\Net::decode_ip($row[0]));
								$ip = empty($ip) ? 'DEFAULT' : $this->SQL->binary_safe($ip);
								$ret = $this->SQL->query("UPDATE $table SET df_varbin_tmp=$ip WHERE $field[1]='".$this->SQL->escape_string($row[0])."'");
								if (!$ret) break;
							}
							if ($ret) $ret = $this->SQL->query("ALTER TABLE $table DROP $field[1]");
							if ($ret) $ret = $this->SQL->query("ALTER TABLE $table RENAME COLUMN df_varbin_tmp TO $field[1]");
						}
						$this->SQL->free_result($result);
						return $ret;
					} // rows == 0 then simply alter the column
				}
				if ($ret && $type != '') {
					$query = "ALTER TABLE $table ALTER COLUMN $field[1] TYPE $type";
					$query = preg_replace($this->query_pattern, $this->query_replace, $query);
					$ret = $this->SQL->query($query);
				}
				if ($ret && isset($default)) {
					$query = "ALTER TABLE $table ALTER COLUMN $field SET DEFAULT '$default'";
					$query = preg_replace($this->query_pattern, $this->query_replace, $query);
					$ret = $this->SQL->query($query);
				}
				return $ret;
		}
	}

	public function alter_index($mode, $table, $name, $columns='')
	{
		$this->create_patterns();
		switch ($mode)
		{
			case 'index':
			case 'fulltext':
				if ($name == 'PRIMARY') {
					return $this->SQL->query("ALTER TABLE $table ADD PRIMARY KEY ($columns)");
				} else {
					return $this->SQL->query("CREATE INDEX {$table}_$name ON $table USING btree ($columns)");
				}

			case 'unique':
				return $this->SQL->query("CREATE UNIQUE INDEX {$table}_$name ON $table USING btree ($columns)");
			case 'drop':
				if ($name = 'PRIMARY') {
					return $this->SQL->query("ALTER TABLE ONLY $schema.$table DROP CONSTRAINT {$table}_pkey");
				} else {
					return $this->SQL->query("DROP INDEX {$table}_$name");
				}
		}
	}

	public function get_sequence($table, $schema='')
	{
		if ($schema == '') {
			$schema = $this->get_current_schema();
		}
		if (!$result = $this->SQL->query("
			SELECT
				c.relname AS seqname
			FROM
				pg_class c,
				pg_user u
			WHERE
				c.relowner=u.usesysid
				AND c.relkind = 'S'
				AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname='$schema')
				AND c.relname LIKE '".$table."_%'", true)) { return false; }

		while($row = $this->SQL->fetch_array($result, SQL_ASSOC)) {$seq = $row;}
		if (empty($seq)) return false; // temp workaround
		$this->SQL->free_result($result);

		$result = $this->SQL->query("SELECT last_value, is_called FROM \"$seq[seqname]\"");
		while($row = pg_fetch_object($result)) { $data = $row; }
		$this->SQL->free_result($result);

		$sequence['seqname'] = $seq['seqname'];
		$sequence['last_value'] = $data->last_value;
		$sequence['is_called'] = ($data->is_called == 't') ? 'true' : 'false';
		if (!count($sequence)) return false;
		return $sequence;
	}

	public function list_sequences($schema='')
	{
		if ($schema == '') {
			$schema = $this->get_current_schema();
		}
		$result = $this->SQL->query("
			SELECT
				c.relname AS seqname
			FROM
				pg_class c,
				pg_user u
			WHERE
				c.relowner=u.usesysid
				AND c.relkind = 'S'
				AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname='$schema')
			ORDER BY seqname", true);
		while ($row = $this->SQL->fetch_array($result, SQL_ASSOC)) {
			$result2 = $this->SQL->query("SELECT last_value, is_called FROM \"{$row['seqname']}\"");
			while(list($last_value, $is_called) = $result2->fetch_row()) {
				$sequence[$row['seqname']]['last_value'] = $last_value;
				$sequence[$row['seqname']]['is_called'] = ($is_called == 't') ? 'true' : 'false';
			}
		}
		$this->SQL->free_result($result);
		return $sequence;
	}

	public function increment_serial($to, $table, $field)
	{
		$seq = ($to == 0) ? '1, false' : $to.', true' ;
		return $this->SQL->query("SELECT pg_catalog.setval('".$table."_".$field."_seq', $seq)");
	}

	public function optimize_table($table='', $full=false)
	{
		$analyze = ($table != '') ? 'ANALYZE '.$table : 'ANALYZE';
		return $this->SQL->query("VACUUM".($full ? ' FULL' : '')." $analyze");
	}

}
