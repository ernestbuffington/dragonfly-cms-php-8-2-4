<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

if (!defined('SQL_ASSOC')) {
	define('SQL_ASSOC', \Poodle\SQL::ASSOC);
	define('SQL_NUM',   \Poodle\SQL::NUM);
	define('SQL_BOTH',  \Poodle\SQL::BOTH);
}

class SQL extends \Poodle\SQL
{
	function __construct($adapter, $master_config, $prefix, $slave_config=null)
	{
		parent::__construct($adapter, $master_config, $prefix, $slave_config);
		if (!defined('SQL_LAYER')) {
			define('SQL_LAYER',strtolower($this->engine));
		}
	}

	/**
	 * v9 backward compatibility methods
	 */

	function __get($k)
	{
		switch ($k)
		{
		case 'num_queries': return $this->total_queries;
		case 'time': return $this->total_time;
		}
		return parent::__get($k);
	}

	/**
	 * @deprecated
	 */
	public function sql_close() { $this->close(); }
	/**
	 * @deprecated
	 */
	public function sql_uquery($query, $bypass_error=FALSE)
	{
		return $this->query($query);
	}
	/**
	 * @deprecated
	 */
	public function sql_query($query, $bypass_error=FALSE, $unbufferd=FALSE, $allow_union=FALSE)
	{
		return $this->query($query);
	}
	/**
	 * @deprecated
	 */
	public function sql_numrows($result) { return $result->num_rows; }
	/**
	 * @deprecated
	 */
	public function sql_affectedrows($qresult=0) { return $this->DBM->affected_rows; }
	/**
	 * @deprecated
	 */
	public function sql_numfields($result) { return $result->field_count; }
	/**
	 * @deprecated
	 */
	public function sql_fieldname($offset, $result) { return $result->fetch_field_direct($offset)->name; }
	/**
	 * @deprecated
	 */
	public function sql_fieldtype($offset, $result) { return $result->fetch_field_direct($offset)->type; }
	/**
	 * @deprecated
	 */
	public function sql_fetchrow($qresult, $result_type=SQL_BOTH)
	{
		$stime = microtime(true);
		$row = ($qresult ? $qresult->fetch_array($result_type) : NULL);
		$this->total_time += (microtime(true)-$stime);
		return $row;
	}
	/**
	 * @deprecated
	 */
	public function sql_ufetchrow($query, $result_type=SQL_BOTH) {
		switch ($result_type)
		{
		case SQL_ASSOC:return $this->uFetchAssoc($query);
		case SQL_NUM:  return $this->uFetchRow($query);
		}
		return $this->uFetchRow($query);
	}
	/**
	 * @deprecated
	 */
	public function sql_fetchrowset($qresult, $result_type=SQL_BOTH)
	{
		$stime = microtime(true);
		$result = $qresult->fetch_all($result_type);
		$this->total_time += (microtime(true)-$stime);
		return isset($result) ? $result : NULL;
	}
	/**
	 * @deprecated
	 */
	public function sql_ufetchrowset($query, $result_type=SQL_BOTH) { return $this->uFetchAll($query, $result_type); }
	/**
	 * @deprecated
	 */
	public function sql_fetchfield()             { return false; }
	/**
	 * @deprecated
	 */
	public function sql_rowseek($rownum, $result){ return $result->data_seek($rownum); }
	/**
	 * @deprecated
	 */
	public function sql_freeresult($qresult)     { $qresult->free(); }
	/**
	 * @deprecated
	 */
	public function sql_nextid($idfield)         { return $this->DBM->insert_id($idfield); }
	/**
	 * @deprecated
	 */
	public function sql_error($qresult)          { return $this->error($qresult); }
	/**
	 * @deprecated
	 */
	public function sql_escape_string($string)   { return $this->DBM->escape_string($string); }
	/**
	 * @deprecated
	 */
	public function sql_insert($table, $fields,         $bypass_error=false) { return $this->insert(substr($table,strlen($this->TBL->prefix)), $fields); }
	/**
	 * @deprecated
	 */
	public function sql_update($table, $fields, $where, $bypass_error=false) { return $this->update(substr($table,strlen($this->TBL->prefix)), $fields, $where); }
	/**
	 * @deprecated
	 */
	public function sql_count($table, $where='') {
		if ($where) $where = "WHERE $where";
		list($count) = $this->uFetchRow("SELECT COUNT(*) FROM {$table} {$where}");
		return $count;
	}
	/**
	 * @deprecated
	 */
	public function u_fetch_assoc($query)        { return $this->uFetchAssoc($query); }
	/**
	 * @deprecated
	 */
	public function u_fetch_row  ($query)        { return $this->uFetchRow($query); }

//	public function select_db($db) { return mysql_select_db($db, $this->connect_id); } // USE $db
	/**
	 * @deprecated
	 */
	public function affected_rows()              { return $this->DBM->affected_rows; }
	/**
	 * @deprecated
	 */
	public function data_seek($result, $rownum)  { return $result->data_seek($rownum); }
	/**
	 * @deprecated
	 */
	public function fetch_array($result, $type)  { return $result->fetch_array($type); }
	/**
	 * @deprecated
	 */
	public function field_name($result, $offset) { return $result->field_name($offset); }
	/**
	 * @deprecated
	 */
	public function field_type($result, $offset) { return $result->field_type($offset); }
	/**
	 * @deprecated
	 */
	public function free_result($result)         { return $result->free(); }
	/**
	 * @deprecated
	 */
	public function num_fields($result)          { return $result->field_count; }
	/**
	 * @deprecated
	 */
	public function num_rows($result)            { return $result->num_rows; }
	/**
	 * @deprecated
	 */
	public function error()                      { return array('message' => $this->DBM->error, 'code' => $this->DBM->errno); }

	/**
	 * @deprecated
	 */
	public function binary_safe($str)      { return $this->DBM->escapeBinary($str); }
	/**
	 * @deprecated
	 */
	public function escape_binary($str)    { return $this->DBM->escapeBinary($str); }
	/**
	 * @deprecated
	 */
	public function unescape_binary($str)  { return $this->DBM->unescapeBinary($str); }


	protected $dfmngr;
	function __call($method, $args)
	{
		switch ($method)
		{
		case 'get_versions':
		case 'get_details':
		case 'create_table':
		case 'alter_table':
		case 'drop_table':
		case 'alter_field':
		case 'alter_index':
		case 'increment_serial':
		case 'list_columns':
// 		case 'list_databases':
		case 'list_indexes':
		case 'list_tables':
		case 'optimize_table':
			if (empty($this->dfmngr)) {
				$class = 'Dragonfly\\SQL\\Manager\\'.$this->engine;
				$this->dfmngr = new $class($this);
			}
			return call_user_func_array(array($this->dfmngr, $method), $args); # this is slow
		}

		return parent::__call($method, $args);
	}
}
