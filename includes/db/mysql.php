<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/db/mysql.php,v $
  $Revision: 9.26 $
  $Author: djmaze $
  $Date: 2007/12/22 02:14:41 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

define('SQL_LAYER','mysql');
class sql_db extends sql_parent
{
	//
	// Constructor
	//
	function __construct($server, $user, $password, $database, $persistent=false)
	{
		if (!function_exists('mysql_connect')) {
			cpg_error('MySQL extension not loaded in PHP.<br />Recompile PHP, edit php.ini or choose a different SQL layer.');
		}
		$stime = get_microtime();
		$this->persistent = $persistent;

		$this->connect_id = ($persistent) ? mysqli_connect('p:' . $server, $user, $password) : mysql_connect($server, $user, $password);
		if ($this->connect_id) {
			if (!empty($database) && !mysql_select_db($database) && (!defined('INSTALL'))) {
				mysql_close();
				$this->connect_id = false;
				define('NO_DB', 'It seems that the database doesn\'t exist');
			}
			$this->time += (get_microtime()-$stime);
			# http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
			if (DB_CHARSET) {
				mysql_query('SET NAMES \''.DB_CHARSET."'");
				mysql_query('SET CHARACTER SET \''.DB_CHARSET."'");
			}
		} else {
			define('NO_DB', 'The connection to the database server failed');
		}
	}

	function close() { mysql_close(); }
	function select_db($db) { return mysql_select_db($db); } // USE $db

	//
	// Base query methods
	//
	function query($query, $bypass_error=FALSE, $unbufferd=false)
	{
		$this->querytime = get_microtime();
		$this->query_result = ($unbufferd) ? mysql_unbuffered_query($query, $this->connect_id) : mysql_query($query);
		if ($this->query_result) {
			$this->_log($query);
			return $this->query_result;
		}
		# try to fix table one time
		else if (((mysql_errno() == 1030 && strpos(mysql_error(),'127')) || mysql_errno() == 1034 || mysql_errno() == 1035) &&
							preg_match('#(INTO|FROM)\s+([a-z_]+)#i',$query,$match) &&
							mysql_query('REPAIR TABLE '.$match[2])) 
		{
			$this->query_result = ($unbufferd) ? mysql_unbuffered_query($query, $this->connect_id) : mysql_query($query);
			if ($this->query_result) {
				$this->_log($query);
				return $this->query_result;
			}
		}
		else if	((mysql_errno() == 1062 &&
					preg_match('#ALTER\s+TABLE\s+([a-z_]+)\s+ADD\s+PRIMARY\s+KEY\s+\(([a-z_]+)\)#i',$query,$table) &&
					preg_match("#Duplicate\s+entry\s+'(.*)'\s+for\s+key#i",mysql_error(),$entry) &&
					mysql_query("DELETE FROM $table[1] WHERE $table[2] LIKE '$entry[1]%' LIMIT ".(mysql_num_rows(mysql_query("SELECT $table[2] FROM $table[1] WHERE $table[2] LIKE '$entry[1]%'"))-1))) ||
				(mysql_errno() == 1062 &&
					preg_match('#CREATE\s+UNIQUE\s+INDEX\s+([a-z_]+)\s+ON\s+([a-z_]+)\s+\(([a-z_]+)\)#i',$query,$table) &&
					preg_match("#Duplicate\s+entry\s+'(.*)'\s+for\s+key#i",mysql_error(),$entry) &&
					mysql_query("DELETE FROM $table[2] WHERE $table[3] LIKE '$entry[1]%' LIMIT ".(mysql_num_rows(mysql_query("SELECT $table[3] FROM $table[2] WHERE $table[3] LIKE '$entry[1]%'"))-1))))
		{
			return $this->query($query, $bypass_error, $unbufferd);
		} else if (mysql_errno() == 1007 && preg_match('#CREATE\s+DATABASE\s+#i',$query)) {
			return true;
		}
		if ($bypass_error) {
			$this->_log($query, true);
			return NULL;
		} else {
			$this->show_error("While executing query \"$query\"\n\nthe following error occured: " . mysql_error());
		}
	}

	//
	// Other query methods
	//
	function insert_id($idfield) {
		if (empty($idfield)) {
			$this->show_error('You must specify an \'idfield\' in $db->insert_id($idfield)');
		}
		return mysql_insert_id();
	}
	function affected_rows()              { return mysql_affected_rows(); }
	function data_seek($result, $rownum)  { return mysql_data_seek($result, $rownum); }
	function fetch_array($result, $type)  { return mysql_fetch_array($result, $type); }
	function field_name($result, $offset) { return mysql_field_name($result, $offset); }
	function field_type($result, $offset) { return mysql_field_type($result, $offset); }
	function free_result($result)         { return mysql_free_result($result); }
	function num_fields($query_id)  { return mysql_num_fields($query_id); }
	function num_rows($result)      { return mysql_num_rows($result); }
	function error()                { return array('message' => mysql_error(), 'code' => mysql_errno()); }

	function escape_string($str)	  { return (PHPVERS >= 43) ? mysql_real_escape_string($str) : mysql_escape_string($str); }
	function escape_binary($str)    { return (is_string($str) && strlen($str)) ? '0x'.bin2hex($str) : "''"; }
	function unescape_binary($data) { return $data; }
	function binary_safe($str)      { return $this->escape_binary($str); }

}
