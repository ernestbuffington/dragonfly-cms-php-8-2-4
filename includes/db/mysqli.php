<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/db/mysqli.php,v $
  $Revision: 9.24 $
  $Author: djmaze $
  $Date: 2007/12/22 02:14:41 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

// Note: The mysqli extension is designed to work with version 4.1.3 or above of MySQL.
define('SQL_LAYER','mysql');
class sql_db extends sql_parent
{
	//
	// Constructor
	//
	function __construct($server, $user, $password, $database)
	{
		$stime = get_microtime();
		if (strpos($server, ':')) $server = explode(':', $server, 2);
		else $server = array($server, NULL);
		$this->connect_id = new mysqli($server[0], $user, $password, $database, $server[1]);
		if (mysqli_connect_errno()) {
			define('NO_DB', mysqli_connect_error());
		} else if (DB_CHARSET) {
			$this->connect_id->query('SET NAMES \''.DB_CHARSET."'");
			$this->connect_id->query('SET CHARACTER SET \''.DB_CHARSET."'");
		}
		$this->time += (get_microtime()-$stime);
	}

	function close() { $this->connect_id->close(); }
	function select_db($db) { return $this->connect_id->select_db($db); }

	//
	// Base query method
	//
	function query($query, $bypass_error = false, $unbufferd = false)
	{
		$this->querytime = get_microtime();
		$this->query_result = $this->connect_id->query($query, ($unbufferd ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT));
		if ($this->query_result) {
			$this->_log($query);
			return $this->query_result;
		}
		# try to fix table one time
		else if ((($this->connect_id->errno == 1030 && strpos($this->connect_id->error,'127')) || $this->connect_id->errno == 1034 || $this->connect_id->errno == 1035 )&&
			preg_match('#(INTO|FROM)\s+([a-z_]+)#i',$query,$match) &&
			$this->connect_id->query('REPAIR TABLE '.$match[2])) 
		{
			$this->query_result = $this->connect_id->query($query, ($unbufferd ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT));
			if ($this->query_result) {
				$this->_log($query);
				return $this->query_result;
			}
		}
		else if	(($this->connect_id->errno == 1062 &&
				preg_match('#ALTER\s+TABLE\s+([a-z_]+)\s+ADD\s+PRIMARY\s+KEY\s+\(([a-z_]+)\)#i',$query,$table) &&
				preg_match("#Duplicate\s+entry\s+'(.*)'\s+for\s+key#i",$this->connect_id->error,$entry) &&
				$this->connect_id->query("DELETE FROM $table[1] WHERE $table[2] LIKE '$entry[1]%' LIMIT ".(($this->connect_id->query("SELECT $table[2] FROM $table[1] WHERE $table[2] LIKE '$entry[1]%'")->num_rows)-1))) ||
			($this->connect_id->errno == 1062 &&
				preg_match('#CREATE\s+UNIQUE\s+INDEX\s+([a-z_]+)\s+ON\s+([a-z_]+)\s+\(([a-z_]+)\)#i',$query,$table) &&
				preg_match("#Duplicate\s+entry\s+'(.*)'\s+for\s+key#i",$this->connect_id->error,$entry) &&
				$this->connect_id->query("DELETE FROM $table[2] WHERE $table[3] LIKE '$entry[1]%' LIMIT ".(($this->connect_id->query("SELECT $table[3] FROM $table[2] WHERE $table[3] LIKE '$entry[1]%'")->num_rows)-1))))
		{
			return $this->query($query, $bypass_error, $unbufferd);
		} else if ($this->connect_id->errno == 1007 && preg_match('#CREATE\s+DATABASE\s+#i',$query)) {
			return true;
		}
		if ($bypass_error) {
			$this->_log($query, true);
			return NULL;
		} else {
			$this->show_error("While executing query \"$query\"\n\nthe following error occured: " . $this->connect_id->error);
		}
	}

	function insert_id($idfield) {
		if (empty($idfield)) {
			$this->show_error('You must specify an \'idfield\' in $db->insert_id($idfield)');
		}
		return $this->connect_id->insert_id;
	}
	function affected_rows()             { return $this->connect_id->affected_rows; }
	function data_seek($result, $rownum) { return $result->data_seek($rownum); }
	function fetch_array($result, $type) { return $result->fetch_array($type); }
	function field_name($result, $offset){
		$finfo = $result->fetch_field_direct($offset);
		return $finfo->name;
	}
	function field_type($result, $offset) {
		$finfo = $result->fetch_field_direct($offset);
		return $finfo->type;
	}
	function free_result($result)   { return $result->close(); }
	function num_fields($result)    { return $result->field_count; }
	function num_rows($result)      { return $result->num_rows; }
	function error()                { return array('message' => $this->connect_id->error, 'code' => $this->connect_id->errno); }

	function escape_string($string) { return $this->connect_id->real_escape_string($string); }
	function escape_binary($str)    { return (is_string($str) && strlen($str)) ? '0x'.bin2hex($str) : "''"; }
	function unescape_binary($data) { return $data; }
	function binary_safe($str)      { return $this->escape_binary($str); }

}
