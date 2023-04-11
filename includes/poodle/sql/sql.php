<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

class SQL
{
	const
		STORE_RESULT = 0,
		UNBUFFERED   = 1,
		ADD_PREFIX   = 2,

		ASSOC = 1,
		NUM   = 2,
		BOTH  = 3;

	public
		$mngr;

	protected
		$total_queries = 0,
		$total_time = 0.0,
		$querylist = array(),

		$transaction = false,
		$tbl_pattern = null,
		$tbl_replace = null,

		$TBL,        // database tables
		$DBM = null, // master database
		$DBS = null, // slave database (optional)
		$XML;

	function __construct($adapter, $master_config, $prefix, $slave_config=null)
	{
		$adapter = 'Poodle\\SQL\\Adapter\\'.$adapter;
		if (!class_exists($adapter)) {
			throw new \Exception('Poodle SQL adapter not found');
		}
		$this->DBM = new $adapter($master_config);
		$this->DBS = $slave_config ? new $adapter($slave_config) : $this->DBM;
		$this->TBL = new \Poodle\SQL\Tables($this, $prefix);
		$this->__set('debug', \Poodle::$DEBUG);
	}

	public function close() { return $this->DBM->close() && ($this->DBS!==$this->DBM ? $this->DBS->close() : true); }

	public function query($query, $options=0)
	{
		$qtime = microtime(true);
		$query = trim($query);
		$query[0] = strtoupper($query[0]);
		if ($this->DBS !== $this->DBM && strspn($query, 'SE', 0, 1)) {
			$result = $this->DBS->query($query, $options & self::UNBUFFERED);
		} else
		try {
			if ($options & self::ADD_PREFIX) {
				if (strspn($query, 'CA', 0, 1)) {
					if (!$this->tbl_pattern) { require __DIR__ . '/convert/'.strtolower($this->engine).'.php'; }
					$query = preg_replace($this->tbl_pattern, $this->tbl_replace, $query);
					if (!$query) { return true; }
				}
				if ('I' === $query[0]) {
					$query = preg_replace_callback('#([\(,]\s*)0x(([0-9a-f]{2}?)+)(\s*)#',
						function($m){return $m[1] . $this->DBM->quoteBinary(pack('H*', $m[2])) . $m[4];},
						$query);
				}
				$query = preg_replace('#{([a-z0-9_]+)}#', "{$this->TBL->prefix}\$1", $query);
/*
				$query = preg_replace(
					'#^(INSERT INTO|(?:CREATE|ALTER)[\w\s]+|DROP TABLE|UPDATE|COMMENT ON (?:COLUMN|TABLE)|\) REFERENCES|FROM|JOIN)\s+{([a-z0-9_]+)}#',
					"\$1 {$this->TBL->prefix}\$2", $query);
*/
			}
			$result = $this->DBM->query($query, $options & self::UNBUFFERED);
		} catch (\Poodle\SQL\Exception $e) {
			$this->rollback();
			throw $e;
		}
		$this->log($query, microtime(true)-$qtime);
		return $result;
	}

	public function exec($query, $add_prefix=false)
	{
		$this->query($query, self::UNBUFFERED | ($add_prefix ? self::ADD_PREFIX : 0));
		return $this->DBM->affected_rows;
	}

	protected function log(&$query, $qtime)
	{
		++$this->total_queries;
		$this->total_time += $qtime;
		if ($this->DBM->debug || $qtime > 1) {
			$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$file = ''; $line = 0;
			$c = count($bt);
			for ($i=0; $i<$c; ++$i) {
				if (isset($bt[$i]['file'], $bt[$i]['line'])) {
					$file = $bt[$i]['file'];
					$line = $bt[$i]['line'];
					if (!strpos($file, '\\sql\\') && !strpos($file, '/sql/')) { break; }
				}
			}
			$file = \Poodle::shortFilePath($file);
			if ($qtime > 1) {
				\Poodle\LOG::notice('Slow SQL', "Slow Query {$qtime} in {$file}#{$line}:\n{$query}\n");
			}
			if ($this->DBM->debug & \Poodle::DBG_SQL_QUERIES) {
				$this->querylist[$file][] = array('line'=>$line, 'query'=>$query, 'time'=>$qtime);
			}
		}
	}

	/***********************************
	 *     Special added functions
	 ***********************************/

	public function resultToCSV($result, $filename, $headers=true, $delimiter=',', $enclosure='"')
	{
		if (is_resource($filename)) {
			$fp = $filename;
		} else
		if (false === strpos($filename, '/')) {
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment;filename='.$filename);
			$fp = fopen('php://output', 'w');
		} else {
			$fp = fopen($filename, 'w');
		}
		if ($fp) {
			$r = $result->fetch_assoc();
			if ($headers) {
				fputcsv($fp, array_keys($r), $delimiter, $enclosure);
			}
			do {
				fputcsv($fp, $r, $delimiter, $enclosure);
			} while ($r = $result->fetch_row());
			if ($fp !== $filename) {
				fclose($fp);
			}
			return true;
		}
		return false;
	}

	public function quote($str) { return '\''.$this->DBM->escape_string($str).'\''; }

	public function count($table, $where='')
	{
		$tbl = $this->TBL->getTable($table);
		return $tbl ? $tbl->count($where) : false;
	}

	public function uQuery($query) { return $this->query($query, self::UNBUFFERED); }

	public function uFetchAssoc($query, $type_cast=false)
	{
		$result = $this->query($query.' LIMIT 1', self::UNBUFFERED);
		return is_bool($result) ? $result : $result->fetch_assoc($type_cast);
	}

	public function uFetchObject($query, $class_name = null, array $params = null)
	{
		$result = $this->query($query.' LIMIT 1', self::UNBUFFERED);
		return is_bool($result) ? $result : $result->fetch_object($class_name, $params);
	}

	public function uFetchRow($query, $type_cast=false)
	{
		$result = $this->query($query.' LIMIT 1', self::UNBUFFERED);
		return is_bool($result) ? $result : $result->fetch_row($type_cast);
	}

	public function uFetchAll($query, $type=self::ASSOC)
	{
		$result = $this->query($query, self::UNBUFFERED);
		return is_bool($result) ? $result : $result->fetch_all($type);
	}

	public function fetchFieldNames($result)
	{
		$prefixes = func_get_args();
		array_shift($prefixes);
		$prefixes = '#^('.implode('|',$prefixes).')_#';
		$fields = array();
		$result->field_seek(0);
		$i = $result->field_count;
		while ($i>0 && $field = $result->fetch_field()) { $fields[preg_replace($prefixes, '', $field->name)] = $field->name; --$i; }
		return $fields;
	}

	public function delete($table, $where)
	{
		return $this->TBL->getTable($table)->delete($where);
	}

	public function insert($table, $array, $id='')
	{
		return $this->TBL->getTable($table)->insert($array, $id);
	}

	public function insertPrepared($table, $array, $id='')
	{
		return $this->TBL->getTable($table)->insertPrepared($array, $id);
	}

	public function update($table, $array, $where)
	{
		return $this->TBL->getTable($table)->update($array, $where);
	}

	public function updatePrepared($table, $array, $where)
	{
		return $this->TBL->getTable($table)->updatePrepared($array, $where);
	}

	public function prepareValues(array $array, $concat_key = false)
	{
		if ($concat_key) {
			foreach ($array as $field => &$value) {
				$value = $field.'='.$this->prepareValue($value);
			}
			return $array;
		}
		return array_map(array($this,'prepareValue'), $array);
	}

	public function prepareValue($value)
	{
//		if (SQLDEFAULT===$value) { return 'DEFAULT'; }
		if (is_null($value))   { return 'NULL'; }
		if (is_bool($value))   { return ($value?1:0); }
		if (is_int($value))    { return $value; }
		if (is_float($value))  { return number_format($value, 14, '.', ''); }
		if (is_array($value))  { return $this->quote(\Poodle::dataToJSON($value)); }
		if ($value instanceof \DateTime) {
			if ($value instanceof \Poodle\Timestamp) { return $value->getTimestamp(); }
			if ($value instanceof \Poodle\Date) { return $this->quote($value->format('Y-m-d')); }
			if ($value instanceof \Poodle\Time) { return $this->quote($value->format('H:i:s')); }
			// Store in UTC
			$value = clone $value;
			return $this->quote($value->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\\TH:i:s'));
		}
		if (preg_match('#[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]#', $value)) {
			return $this->DBM->quoteBinary($value);
		}
		return $this->quote($value);
	}

	public function parseWhere($where)
	{
		if (is_array($where)) {
			$where = implode(' AND ', $this->prepareValues($where, true));
		}
		return $where ? ' WHERE '.$where : '';
	}

	public function begin()
	{
		if ($this->transaction) { throw new \Exception('SQL Transaction already started.'); }
		return $this->transaction = $this->DBM->begin();
	}

	public function commit()
	{
		if (!$this->transaction) { return true; }
		$this->transaction = false;
		return $this->DBM->commit();
	}

	public function rollback()
	{
		if (!$this->transaction) { return false; }
		$this->transaction = false;
		return $this->DBM->rollback();
	}

	public function removePrefix(&$array)
	{
		$newarray = array();
		$prefixes = func_get_args();
		array_shift($prefixes);
		$prefixes = '#^('.implode('|',$prefixes).')_#';
		foreach ($array as $key => $value) {
			$newarray[preg_replace($prefixes, '', $key)] = $value;
		}
		$array = $newarray;
	}

	public function search(array $fields, &$text)
	{
		return $this->DBS->search($fields, $text);
	}

	function __get($key)
	{
		switch ($key)
		{
		case 'total_queries':
		case 'total_time':
		case 'querylist':
			return $this->$key;

		case 'database': return $this->DBM->dbname();
		case 'TBL':      return $this->TBL;
		case 'MASTER':   return $this->DBM;
		case 'SLAVE':    return $this->DBS;
		case 'XML':
			if (!$this->XML) {
				$this->XML = new \Poodle\SQL\XML($this);
			}
			return $this->XML;
		case 'engine':   return constant(get_class($this->DBM).'::ENGINE');
		case 'tbl_quote':return constant(get_class($this->DBM).'::TBL_QUOTE');
		case 'debug':
		case 'affected_rows':
		case 'client_info':
		case 'client_version':
//		case 'connect_errno';
//		case 'connect_error';
		case 'errno':
		case 'error':
//		case 'field_count':
		case 'host_info':
//		case 'protocol_version':
		case 'server_info':
		case 'server_version':
//		case 'info':
		case 'insert_id':
		case 'sqlstate':
//		case 'thread_id':
//		case 'warning_count':
			return $this->DBM->$key;
		}
		return null;
	}

	function __set($key, $value)
	{
		if ('debug' === $key) {
			$this->DBM->debug = $this->DBS->debug = ($value & \Poodle::DBG_SQL | $value & \Poodle::DBG_SQL_QUERIES);
		}
	}

	function __call($method, $args)
	{
		switch ($method)
		{
		# bugs.php.net/bug.php?id=49535
//		case 'get_client_info':
//		case 'get_client_version':
//			return $this->DBM->substr($method,4);

		case 'character_set_name':
		case 'dbname':
		case 'get_charset':
		case 'ping':
		case 'stat':
		case 'listProcesses':
			return $this->DBM->$method();

		case 'autocommit':
		case 'change_user':
		case 'debug':
		case 'dump_debug_info':
		case 'get_cache_stats':      # 5.3.0 mysqlnd
		case 'get_client_stats':     # 5.3.0 mysqlnd
		case 'get_connection_stats': # 5.3.0 mysqlnd
		case 'get_warnings':
		case 'kill':
		case 'more_results':
		case 'multi_query':
		case 'next_result':
		case 'options':
		case 'poll':
		case 'prepare':
		case 'reap_async_query':
		# internal
		case 'init':
		case 'ssl_set':
		case 'store_result':
		case 'use_result':
			trigger_error('Call to unsupported method \Poodle\SQL::'.$method.'()', E_USER_ERROR);

		case 'quoteBinary':
		case 'escapeBinary':
		case 'unescapeBinary':
		case 'escape_string':
		case 'insert_id':
			return $this->DBM->$method($args[0]);
		case 'showTables':
			return $this->DBS->$method($args[0]);
		}
		if (empty($this->mngr)) {
			$mngr = 'Poodle\\SQL\\Manager\\'.$this->engine;
			$this->mngr = new $mngr($this);
		}
		if ('load_manager' === $method) { return; }
		return call_user_func_array(array($this->mngr, $method), $args); # this is slow
	}

	public function prepare($query)
	{
		return new \Poodle\SQL\Statement($this, $query);
	}

}
