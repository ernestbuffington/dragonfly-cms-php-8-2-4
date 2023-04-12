<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	(binary) casting and b prefix forward support was added in PHP 5.2.1
*/

namespace Poodle\SQL;

class Table
{
	protected
		$name,
		$db;

	function __construct($name, \Poodle\SQL $db)
	{
		$this->name = $name;
		$this->db   = $db;
	}

	public function __toString() { return $this->name; }

	public function insert(array $array, $id='')
	{
		return $this->insertPrepared($this->db->prepareValues($array), $id);
	}

	public function insertIgnore(array $array)
	{
		return $this->insertPrepared($this->db->prepareValues($array), '', true);
	}

	public function insertPrepared(array $array, $id='', $ignore=false)
	{
		if (!$array) {
			return false;
		}
		$this->db->query('INSERT'.($ignore?' IGNORE':'').' INTO '.$this->name.' ('.implode(', ', array_keys($array)).') VALUES ('.implode(', ', $array).')');
		return ($id ? $this->db->insert_id($id) : true);
	}

	public function update(array $array, $where)
	{
		return $this->updatePrepared($this->db->prepareValues($array), $where);
	}

	public function updatePrepared(array $array, $where)
	{
		if (!$array || !$where) {
			return false;
		}
		foreach ($array as $field => &$value) {
			$value = $field.'='.$value;
		}
		return $this->db->query('UPDATE '.$this->name
			.' SET '.implode(', ', $array)
			.$this->db->parseWhere($where)
		);
	}

	protected function getFrom($where='') { return ' FROM ' . $this->name . $this->db->parseWhere($where); }

	public function count($where='')
	{
		$result = $this->db->query('SELECT COUNT(*) '.$this->getFrom($where));
		if (!$result) {
			return 0;
		}
		list($count) = $result->fetch_row();
		return (int)$count;
	}

	public function delete($where)
	{
		return $this->db->exec('DELETE '.$this->getFrom($where));
	}

	public function uFetchAssoc(array $fields, $where)
	{
		return $this->db->uFetchAssoc("SELECT ".implode(', ',$fields) . $this->getFrom($where));
	}

	public function uFetchObject(array $fields, $where, $class_name = null, array $params = null)
	{
		return $this->db->uFetchObject("SELECT ".implode(', ',$fields) . $this->getFrom($where), $class_name, $params);
	}

	public function uFetchRow(array $fields, $where)
	{
		return $this->db->uFetchRow("SELECT ".implode(', ',$fields) . $this->getFrom($where));
	}

	public function uFetchAll(array $fields, $where, $type=\Poodle\SQL::ASSOC)
	{
		return $this->db->uFetchAll("SELECT ".implode(', ',$fields) . $this->getFrom($where));
	}

	public function listColumns($full=true) { return $this->db->listColumns($this->name, $full); }
	public function listIndices()           { return $this->db->listIndices($this->name); }
	public function listForeignKeys()       { return $this->db->listForeignKeys($this->name); }
	public function listTriggers()          { return $this->db->listTriggers($this->name); }
	public function analyze()               { return $this->db->analyze($this->name); }
	public function check()                 { return $this->db->check($this->name); }
	public function optimize()              { return $this->db->optimize($this->name); }
	public function repair()                { return $this->db->repair($this->name); }
}

class Tables implements \ArrayAccess, \Countable
{
	protected
		$db,
		$prefix,
		$tables = array();

	function __construct(\Poodle\SQL $sql, $prefix)
	{
		$this->db = $sql;
		$this->prefix = $prefix;
		$this->loadTables();
	}

	function __get($key)
	{
		if ('prefix' === $key) {
			return $this->prefix;
		}
		if (!isset($this->tables[$key])) {
			if (class_exists('Poodle\\Debugger', false)) {
				\Poodle\Debugger::trigger('Unknown database table: '.$key, __DIR__);
			} else {
				trigger_error('Unknown database table: '.$key);
			}
			$this->tables[$key] = $this->prefix.$key;
		}
		return $this->getTable($key);
	}

	function __set($key, $v)
	{
		throw new \Exception('Disallowed to set property: '.$key);
	}

	function __isset($key)
	{
		return isset($this->tables[$key]);
	}

	public function getTable($name)
	{
		$tbl = $this->tables[$name] ?? $this->prefix.$name;
		if (!is_object($tbl)) {
			$tbl = $this->tables[$name] = new Table($tbl, $this->db);
		}
		return $tbl;
	}

	public function prefix() { return $this->prefix; }

	public function loadTables($skip_cache = false)
	{
		$K = \Poodle::getKernel();
		$cache_key = null;
		$this->tables = array();
		try {
			if ($K && $K->CACHE) {
				$cache_key =  __CLASS__ . '/' . md5($this->db->host_info.'-'.$this->db->database.'-'.$this->prefix);
				if (!$skip_cache) {
					$this->tables = $K->CACHE->get($cache_key);
				}
			}
		} catch (\Exception $e) {}
		if (!$this->tables) {
			$result = $this->db->showTables($this->prefix);
			$pl = strlen($this->prefix);
			$tq = $this->db->tbl_quote;
			$this->tables = array();
			$dfup = $K->db_user_prefix ?: $this->prefix;
			$dful = strlen($dfup)+1; // dragonfly custom users table prefix
			while ($name = $result->fetch_row()) {
				// dragonfly custom users table prefix
//				if ($name[0] === $dfup.'_users')
				if (substr($name[0],0,strlen($dful)) === $dfup) {
					$k = substr($name[0],$dful);
					if (!preg_match('#^[a-zA-Z0-9_]+$#D', $name[0])) { $name[0] = $tq.$name[0].$tq; }
					$this->tables[$k] = $name[0];
				} else
				// default table prefix
				if (!$pl || substr($name[0],0,$pl) === $this->prefix) {
					$k = substr($name[0],$pl);
					if (!preg_match('#^[a-zA-Z0-9_]+$#D', $name[0])) { $name[0] = $tq.$name[0].$tq; }
					$this->tables[$k] = $name[0];
				}
			}
			$result->free();
			try {
				if ($cache_key) { $K->CACHE->set($cache_key, $this->tables); }
			} catch (\Exception $e) {}
		}
	}

	# ArrayAccess
	public function offsetExists($k)  { return array_key_exists($k, $this->tables); }
	public function offsetGet($k)     { return $this->getTable($k); }
	public function offsetSet($k, $v) { }
	public function offsetUnset($k)   { }

	# Countable
	public function count() { return is_countable($this->tables) ? count($this->tables) : 0; }
}
