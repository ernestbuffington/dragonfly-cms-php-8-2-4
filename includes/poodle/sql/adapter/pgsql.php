<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL\Adapter;

class PgSQL implements \Poodle\SQL\Interfaces\Adapter
{

	public const
		ENGINE    = 'PostgreSQL',
		TBL_QUOTE = '"';

	protected
		$affected_rows,
		$connection,
		$last_insert_table,
		$v81,
		$server_info,
		$server_version,
		$cfg = array(
			'host' => null,
			'port' => null, # 5432
			'username' => null,
			'password' => null,
			'database' => null,
			'charset'  => 'utf8',
			# PostgreSQL advanced options http://www.postgresql.org/docs/8.3/static/libpq-connect.html
			'hostaddr' => null,
			'connect_timeout' => null,
			'options'  => null,
			'sslmode'  => null, # requiressl = 7.x
			'service'  => null,
		);

	private
		$client_info,
		$client_version,
		$last_result;

	function __construct($config)
	{
		if (!function_exists('pg_connect')) {
			throw new \Poodle\SQL\Exception('PostgreSQL', 0, \Poodle\SQL\Exception::NO_EXTENSION);
		}
		if (empty($config['charset'])) {
			$config['charset'] = $this->cfg['charset'];
		}
		$this->cfg = array_merge($this->cfg, $config);
		# IPv4
		if (preg_match('#^((?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]{1,2})\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]{1,2}))(?::(\d+))?$#', $this->cfg['host'], $match)) {
			$this->cfg['hostaddr'] = $match[1];
			$this->cfg['port']     = (empty($match[2]) ? null : $match[2]);
		}
		# IPv6
		else if (preg_match('#^\[([a-z0-9:]+)\](?::(\d+))?$#', $this->cfg['host'], $match)) {
			$this->cfg['hostaddr'] = $match[1];
			$this->cfg['port']     = (empty($match[2]) ? null : $match[2]);
		}
		else if (preg_match('#^(.*)?:(\d+)?$#', $this->cfg['host'], $match)) {
			$this->cfg['host'] = (empty($match[1]) ? null : $match[1]);
			$this->cfg['port'] = (empty($match[2]) ? null : $match[2]);
		}
		$this->connect();
	}

	function __destruct()
	{
		$this->close();
	}

	function __get($key)
	{
		switch ($key) {
		case 'affected_rows': return $this->affected_rows;
		case 'client_info':   return $this->client_info;
		case 'client_version':return $this->client_version;
		case 'server_info':   return $this->server_info;
		case 'server_version':return $this->server_version;
		case 'host_info':     return pg_host($this->connection);
		case 'errno':         return 0;
		case 'error':         return pg_last_error($this->connection);
		case 'insert_id':     return $this->insert_id();
		default: return null;
		}
	}

	public function connect()
	{
		$connect_string = '';
		if ($this->cfg['host'])     { $connect_string .= ' host='.$this->cfg['host']; }
		if ($this->cfg['hostaddr']) { $connect_string .= ' hostaddr='.$this->cfg['hostaddr']; }
		if ($this->cfg['port'])     { $connect_string .= ' port='.$this->cfg['port']; }
		if ($this->cfg['username']) { $connect_string .= ' user='.$this->cfg['username']; }
		if ($this->cfg['password']) { $connect_string .= ' password='.$this->cfg['password']; }
		if ($this->cfg['database']) { $connect_string .= ' dbname='.$this->cfg['database']; }
		if (!($this->connection = pg_connect($connect_string))) {
			throw new \Poodle\SQL\Exception(pg_last_error(), 0, \Poodle\SQL\Exception::NO_CONNECTION);
		}

		$this->server_info = pg_parameter_status($this->connection, 'server_version');
		$v = explode('.', $this->server_info);
		$this->server_version = ($v[0]*10000) + ($v[1]*100) + min(99, $v[2]);

		$v = pg_version($this->connection);
		$this->client_info = $v['client'];
		$v = explode('.', $v['client']);
		$this->client_version = ($v[0]*10000) + ($v[1]*100) + min(99, $v[2]);

		$this->v81 = version_compare($this->server_info, '8.1', '>=');
		$this->set_charset();
	}

	public function ping()  { return pg_ping($this->connection); }

	public function real_connect() { $this->connect(); }

	public function select_db()
	{
//		CREATE DATABASE poodle WITH ENCODING='UTF8' OWNER=poodle CONNECTION LIMIT=-1;
//		CREATE LANGUAGE plpgsql;
	}
	public function dbname() { return $this->cfg['database']; }

	public function set_charset()
	{
		# SET NAMES | SET CLIENT_ENCODING TO
		if (-1 == pg_set_client_encoding($this->connection, $this->cfg['charset'])
		 || -1 == pg_set_client_encoding($this->connection, str_replace('utf8','unicode',$this->cfg['charset'])))
		{
			return false;
		}
		return true;
	}
	public function character_set_name() { return pg_client_encoding($this->connection); }

	public function close() { return pg_close($this->connection); }

	public function get_charset()
	{
		$r = new \stdClass;
		$r->charset = pg_client_encoding($this->connection); # pg_parameter_status($this->connection, 'server_encoding')
		$r->collation = null;
		$r->comment = 'UTF-8 Unicode';
		$r->dir = '';
		$r->min_length = 1;
		$r->max_length = 3;
		$r->number = 33;
		$r->state = 993;
		return $r;
	}

	protected function _query($query) { return pg_query($this->connection, $query); }
	public function query($query, $unbuffered=0)
	{
		$ignore = false;
		$this->last_insert_table = false;
		if ('S' === $query[0]) {
			# MySQL regular expression
			$query = preg_replace('#\s+NOT\s+REGEXP\s+\'#i',  ' !~ \'', $query);
			$query = preg_replace('#\s+(REGEXP|RLIKE)\s+\'#i', ' ~ \'', $query);
		} else if ('I' === $query[0] && str_starts_with($query, 'INSERT IGNORE')) {
			# MySQL INSERT IGNORE
			$ignore = true;
			$query = 'INSERT'.substr($query,13);
		}
		$this->affected_rows = 0;
//		if (!strspn($query, 'CI', 0, 1)) { $query = preg_replace('#FIND_IN_SET\s*\(([^,]+),\s*([^\(\)]+)\)#i', '$1 = ANY(STRING_TO_ARRAY($2, \',\'))', $query); }
		$result = pg_query($this->connection, $query);
		$this->last_result = $result;
		if (!$result) {
			if ($ignore) { return true; }
			throw new \Poodle\SQL\Exception(pg_last_error($this->connection), 0, $query);
		}
		$this->affected_rows = pg_affected_rows($result);
//		if ($this->field_count = is_resource($result) ? max(0,pg_num_fields($result)) : 0) {
		if (is_resource($result)) {
			if ($unbuffered) {
				return new PgSQL_Result($this->last_result);
			}
			return new PgSQL_Result($this->last_result);
		}
		if (!$this->v81 && preg_match('#^INSERT\s+INTO\s+([\w\-]+)\s+\(#i', $query, $tablename)) {
			$this->last_insert_table = $tablename[1];
		}
		return true;
	}

	public function showTables($prefix)
	{
//		$q = 'SELECT tablename FROM pg_tables WHERE tableowner = current_user ORDER BY tablename';
//		$q = 'SELECT relname FROM pg_class WHERE relname !~ \'^(pg_|sql_)\' AND relkind = \'r\'';
//		$q = 'SELECT table_name FROM information_schema.tables WHERE table_type = \'BASE TABLE\' AND table_schema NOT IN (\'pg_catalog\', \'information_schema\')';
		$q = 'SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\'';
		if ($prefix) $q .= " AND table_name LIKE '{$prefix}%'";
		return $this->query($q);
	}

	public function quoteBinary($str)     { return '\''.pg_escape_bytea($str).'\''; }
	public function escapeBinary($data)   { return pg_escape_bytea($data); }
	public function unescapeBinary($data) { return pg_unescape_bytea($data); }
	public function escape_string($data)  { return pg_escape_string($data); }

	public function insert_id($idfield)
	{
		$query = 'SELECT lastval()';
		if (!$this->v81) {
			if (!$this->last_insert_table) { return 0; }
			if (!$idfield && $result = $this->_query('SELECT column_default FROM information_schema.columns WHERE table_name=\''.$this->last_insert_table.'\' AND column_default LIKE (\'nextval%\')'))
			{
				list($idfield) = $result->fetch_row();
				$query = 'SELECT currval('.preg_replace('#^.*(\'[\']+\').+$#D', '$1', $idfield).')';
				$result->free();
			}
			else if (!$idfield) { return null; }
			else {
				$query = 'SELECT currval(\''.$this->last_insert_table.'_'.$idfield.'_seq\')';
			}
		}
		$result = $this->query($query);
		if (!$result) { return 0; }
		list($insert_id) = $result->fetch_row();
		$result->free();
		return (int)$insert_id;
	}
	public function stat() { return false; }

	public function begin() { return $this->_query('BEGIN'); } # START TRANSACTION
	public function commit() { return $this->_query('COMMIT'); }
	public function rollback() { return $this->_query('ROLLBACK'); }

	public function listProcesses() { return false; }

	public function search($fields, $text)
	{
		$db = null;
  $text = \Poodle\Unicode::as_search_txt($text);
		$prefix = $this->server_info < 8.3 ? "'default'," : '';
		// https://www.postgresql.org/docs/9.1/static/textsearch-controls.html
//		$tsquery = "plainto_tsquery({$prefix}'{$this->escape_string($text)}')";
		$text = preg_replace('/\\s+\\+(\\S)/', ' & $1', $text);  // AND
		$text = preg_replace('/\\s+\\-(\\S)/', ' & !$1', $text); // NOT
		if (1 < preg_match_all('/"[^"]+"|\'[^\']+\'|\\S+/', $text, $m)) {
			$text = implode(' | ', $m[0]); // OR
			$text = str_replace(' | & | ', ' & ', $text); // OR
		}
		$tsquery = "to_tsquery('{$prefix}{$db->escape_string($text)}:*')";
		foreach ($fields as &$field) {
			$field = "{$field} @@ {$tsquery}";
		}
		return implode(' OR ', $fields);
/*
		$score = $this->server_info > 8.2 ? 5 : 1;
		$rank  = $this->server_info < 8.3 ? 'rank' : 'ts_rank';
		"$rank($fulltext, {$tsquery}), $score) AS score ".
*/
	}

	public function createLock($name, $timeout = 0)
	{
		list($key1, $key2) = array_values(unpack('n2', sha1($name, true)));
		return $this->_query("SELECT pg_try_advisory_lock({$key1}, {$key2})");
	}

	public function releaseLock($name)
	{
		list($key1, $key2) = array_values(unpack('n2', sha1($name, true)));
		return $this->_query("SELECT pg_advisory_unlock({$key1}, {$key2})");
	}
}

class PgSQL_Result implements \Poodle\SQL\Interfaces\ResultIterator
{
	protected
		$i = 0,
		$row = null,
		$object_name = 'stdClass',
		$object_params = null;

	private
		$result,
		$fields,
		$field_offset = 0,
		$field_count = 0,
		$num_rows = 0;

	/** TODO: fix slow type casting */
	private static
		$ints   = array(1=>1,2=>2,3=>3,8=>8,9=>9),
		$floats = array(4=>4,5=>5,246=>246),
		$field_types = array(
			'int2'      => 2,
			'int4'      => 3,
			'float4'    => 4,
			'float8'    => 5,
			'timestamp' => 7,
			'int8'      => 8,
			'date'      => 10,
			'time'      => 11,
			'datetime'  => 12,
			'bit'       => 16,
			'numeric'   => 246,
			'text'      => 252,
			'bytea'     => 252,
			'varchar'   => 253,
			'bpchar'    => 254,
			'char'      => 254,
		);

	private function type_cast($row, $resulttype)
	{
		if ($row) {
			$fields = $this->fetch_fields();
			foreach ($fields as $field) {
				if (isset(self::$floats[$field->type])) {
					if ($resulttype & 1) { $row[$field->name] = (float)$row[$field->name]; }
					if ($resulttype & 2) { $row[$i] = (float)$row[$i]; }
				}
				else if (isset(self::$ints[$field->type])) {
					if ($resulttype & 1) { $row[$field->name] = (int)$row[$field->name]; }
					if ($resulttype & 2) { $row[$i] = (int)$row[$i]; }
				}
			}
		}
		return $row;
	}

	function __construct(&$result)
	{
		$this->result = &$result;
		$this->field_count = pg_num_fields($result);
		$this->num_rows    = pg_num_rows($result);
	}
	function __destruct() { $this->free(); }
	function __get($key)
	{
		switch ($key) {
		case 'current_field': return $this->field_offset;
		case 'field_count':   return $this->field_count;
		case 'lengths':       return null;
		case 'num_rows':      return $this->num_rows;
		default: return null;
		}
	}
	public function data_seek($offset) { return pg_result_seek($this->result, $offset); }

	public function fetch_array($type_cast=false)
	{
		return $type_cast ? $this->type_cast(pg_fetch_array($this->result, null, PGSQL_BOTH), 3) : pg_fetch_array($this->result, null, PGSQL_BOTH); # PGSQL_ASSOC | PGSQL_NUM;
	}

	public function fetch_assoc($type_cast=false)
	{
		return $type_cast ? $this->type_cast(pg_fetch_assoc($this->result), 1) : pg_fetch_assoc($this->result);
	}

	public function fetch_object($class_name=null, array $params=null)
	{
		$class_name = ($class_name ?: $this->object_name) ?: 'stdClass';
		$params = $params ?: $this->object_params;
		return $params
			? pg_fetch_object($this->result, null, $class_name, $params)
			: pg_fetch_object($this->result, null, $class_name);
	}

	public function fetch_row($type_cast=false)
	{
		return $type_cast ? $this->type_cast(pg_fetch_row($this->result), 2) : pg_fetch_row($this->result);
	}

	public function fetch_all($resulttype=\Poodle\SQL::ASSOC, $type_cast=false)
	{
		$rows = array();
		if ($resulttype === \Poodle\SQL::BOTH) { while ($row = $this->fetch_array($type_cast)) $rows[] = $row; }
		else if ($resulttype === \Poodle\SQL::NUM) { while ($row = $this->fetch_row($type_cast)) $rows[] = $row; }
		else { while ($row = $this->fetch_assoc($type_cast)) $rows[] = $row; }
		return $rows;
	}

	public function fetch_field()  { return $this->fetch_field_direct($this->field_offset++); }
	public function fetch_fields()
	{
		if (!$this->fields) {
			$this->fields = array();
			$i = 0;
			while ($field = $this->fetch_field_direct($i++)) { $this->fields[] = $field; }
		}
		return $this->fields;
	}
	public function fetch_field_direct($offset)
	{
		$field = null;
  if ($this->field_count <= $offset || 0 > $offset) { return false; }
		$type = pg_field_type($this->result, $offset);
		$info = new \stdClass();
		$info->name  = pg_field_name($this->result, $offset);
		$info->table = pg_field_table($this->result, $offset);
		$info->type  = self::$field_types[$type];
		$info->def   = null;
		$info->flags = 0;
		if ('bytea' === $type) { $field->flags |= 16; } # BLOB
//		$info->orgname  = $info->name;
//		$info->orgtable = $info->table;
		return $info;
/*
		$info->max_length = pg_field_size($this->result, $offset);
		$info->max_length = pg_field_prtlen($this->result, $offset);
		decimals   The number of decimals used
*/
	}
	public function field_seek($offset)
	{
		return ($offset >= 0 && $this->field_count <= $offset && $this->field_offset = $offset);
	}
	public function free()
	{
		if (!$this->result) return;
		$ret = pg_free_result($this->result);
		unset($this->result);
		return $ret;
	}
	# ArrayAccess
	public function offsetExists($k)  { return ($k >= 0 && $k < $this->num_rows); }
	public function offsetGet($k)     { return $this->row = ($this->data_seek($this->i = $k) ? $this->fetch_assoc() : null); }
	public function offsetSet($k, $v) { }
	public function offsetUnset($k)   { }
	# Countable
	public function count()   { return $this->num_rows; }
	# Iterator steps: rewind() valid() current() key() next() valid() current() key()
	public function key()     { return $this->i; }
	public function current() { return $this->row; }
	public function next()    { $this->row = $this->fetch_assoc(); ++$this->i; }
	public function rewind()  { $this->data_seek($this->i = 0); $this->row = $this->fetch_assoc(); }
	public function valid()   { return $this->i < $this->num_rows; }

	public function setFetchObjectParams($class_name=null, array $params=array())
	{
		$this->object_name = $class_name;
		$this->object_params = $params;
	}
}
