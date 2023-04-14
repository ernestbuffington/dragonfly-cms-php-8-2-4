<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/db/postgresql.php,v $
  $Revision: 9.7 $
  $Author: nanocaiordo $
  $Date: 2007/12/16 09:17:41 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

define('SQL_LAYER','postgresql');

class sql_db extends sql_parent
{

	public $rownum = array();
	public $last_insert_table;

	//
	// Constructor
	//
	function __construct($server, $user, $password, $database, $persistent=false)
	{
		if (!function_exists('pg_connect')) {
			cpg_error('PostgreSQL extension not loaded in PHP.<br />Recompile PHP, edit php.ini or choose a different SQL layer.');
		}
		$stime = get_microtime();
		$this->persistent = $persistent;

		$connect_string = '';
		if ($server) {
			if (strpos($server, ':')) {
				$server = explode(':', $server, 2);
				$connect_string .= "host=$server[0] port=$server[1] ";
			} else if ($server != 'localhost') {
				$connect_string .= "host=$server ";
			}
		}
		if (!empty($database)) {
			$connect_string .= "dbname=".pg_escape_string($database)." ";
		}
		if ($user) { $connect_string .= "user=".pg_escape_string($user)." "; }
		if ($password) { $connect_string .= "password=".pg_escape_string($password); }
		$this->connect_id = ($this->persistent) ? pg_pconnect($connect_string) : pg_connect($connect_string);
		if ($this->connect_id) {
			//pg_set_client_encoding($this->connect_id, 'UNICODE');
			pg_query($this->connect_id, "SET client_encoding = 'UTF8'");
			$this->time += (get_microtime()-$stime);
		} else {
			define('NO_DB', 'Connection to the database server failed.');
		}
	}

	function close() { pg_close($this->connect_id); }
	function select_db($db) { return pg_query($this->connect_id, "\\c $db"); }

	//
	// Base query methods
	//
	function query($query, $bypass_error=FALSE, $unbufferd=false)
	{
		$this->querytime = get_microtime();
		$this->last_insert_table = false;
		$type = strtoupper($query[0]);
		if ($type == 'I') { $query = preg_replace('/^INSERT[\s]+IGNORE/i', 'INSERT', $query); }
		else { $query = preg_replace("/LIMIT[\s]([0-9]+)[,\s]+([0-9]+)/i", "LIMIT \\2 OFFSET \\1", $query); }
		$this->query_result = pg_query($this->connect_id, $query);
		if ($this->query_result) {
			$this->_log($query);
			if ($type == 'S') {
				$this->rownum[$this->query_result] = 0;
			} else if ($type == 'I') {
				if (preg_match("/^INSERT[\s]+INTO[\s]+([a-z0-9\_\.]+)/i", $query, $tablename)) {
					$this->last_insert_table = $tablename[1];
				}
			}
			return $this->query_result;
		} else if ($bypass_error) {
			$this->_log($query, true);
			return NULL;
		} else {
			$this->show_error("While executing query \"$query\"\n\nthe following error occured: ".pg_last_error($this->connect_id));
		}
	}

	function affected_rows($query_id) { return pg_affected_rows($query_id); }
	function data_seek($query_id, $rownum)
	{
		if ($rownum > -1) {
			$this->rownum[$query_id] = $rownum;
			return true;
		}
		return NULL;
	}
	function fetch_array($query_id, $type)
	{
		$num = pg_num_rows($query_id);
		if ($this->rownum[$query_id] < $num) {
			$row = pg_fetch_array($query_id, $this->rownum[$query_id], $type);
			if ($row) {
				$this->rownum[$query_id]++;
				return $row;
			}
		}
		return false;
	}
	function insert_id($idfield)
	{
		if (empty($idfield)) { $this->show_error('You must specify an \'idfield\' in $db->insert_id($idfield)'); }
		if ($this->last_insert_table) {
			$table = $this->last_insert_table.'_'.$idfield;
			$temp_q_id = pg_query($this->connect_id, "SELECT currval('" . $table . "_seq') AS last_value");
			if (!$temp_q_id) return NULL;
			$temp_result = pg_fetch_array($temp_q_id, 0, PGSQL_ASSOC);
			pg_free_result($temp_q_id);
			$temp_result = ($temp_result) ? $temp_result['last_value'] : NULL;
			return $temp_result;
		}
		return NULL;
	}
	function field_name($query_id, $offset) { return pg_field_name($query_id, $offset); }
	function field_type($query_id, $offset) { return pg_field_type($query_id, $offset); }
	function free_result($query_id)
	{
		pg_free_result($query_id);
		unset($this->rownum[$query_id]);
	}
	function num_fields($query_id)  { return pg_num_fields($query_id); }
	function num_rows($query_id)    { return pg_num_rows($query_id); }
	function error($query_id)       { return array('message' => pg_last_error($this->connect_id), 'code' => -1); }

	function escape_string($str)    { return pg_escape_string($str); }
	function escape_binary($data)   { return pg_escape_bytea($data); }
	function unescape_binary($data) { return pg_unescape_bytea($data); }
	function binary_safe($data)     { return '\''.pg_escape_bytea($data).'\''; }

}
