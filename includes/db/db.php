<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/db/db.php,v $
  $Revision: 9.39 $
  $Author: nanocaiordo $
  $Date: 2007/12/16 09:17:41 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

define('SQL_ASSOC', 1<<0); // MYSQL_ASSOC, PGSQL_ASSOC
define('SQL_NUM', 1<<1);   // MYSQL_NUM,   PGSQL_NUM
define('SQL_BOTH', (SQL_ASSOC|SQL_NUM));  // MYSQL_BOTH,  PGSQL_BOTH

class sql_parent
{

	public $connect_id;
	public $persistent;
	public $query_result;
	public $num_queries = 0;
	public $time = 0;
	public $querylist = array();
	public $querytime = 0;

	public $file;
	public $line;

	public function _log($query, $failed=false)
	{
		global $MAIN_CFG;
		if (CPG_DEBUG || (is_admin() && !empty($MAIN_CFG['debug']['database']))) {
			$this->_backtrace();
			if ($failed) {
				$this->querylist[$this->file][] = '<b style="font-color: #f00">'.round((get_microtime()-$this->querytime), 4).' - FAILED LINE '.$this->line.':</b> '.htmlprepare($query);
			} else {
				$this->querylist[$this->file][] = '<b>'.round((get_microtime()-$this->querytime), 4).' - LINE '.$this->line.':</b> '.htmlprepare($query);
			}
		}
	}
	public function _backtrace()
	{
		$this->file = 'unknown';
		$this->line = 0;
		if (PHPVERS >= 43) {
			$tmp = debug_backtrace();
			for ($i=0; $i<count($tmp); ++$i) {
				if (!preg_match('#[\\\/]{1}includes[\\\/]{1}db[\\\/]{1}[a-z_]+.php$#', $tmp[$i]['file'])) {
					$this->file = $tmp[$i]['file'];
					$this->line = $tmp[$i]['line'];
					break;
				}
			}
		}
	}

	public function show_error($the_error, $bypass_error = FALSE, $no_connection = 0)
	{
		$mailer_message = null;
  global $sitename, $adminmail, $cpgdebugger, $userinfo;

		$this->_backtrace();
		$the_error .= "\n\nIn: ".$this->file." on line: ".$this->line;

		$the_error = 'On '.(function_exists('get_uri') ? get_uri() : $_SERVER['REQUEST_URI'])."\n".$the_error;
		$show = ($no_connection || defined('INSTALL')) ? 1 : (is_admin() || CPG_DEBUG);
		if ($show) {
			if (!defined('INSTALL') && is_object($cpgdebugger)) {
				trigger_error($the_error, E_USER_WARNING);
			} else {
				$the_error = '<html><body><center><h1>ERROR</h1><form><textarea rows="8" cols="60">'.htmlspecialchars($the_error, ENT_QUOTES, 'UTF-8').'</textarea></form></body></html>';
				die($the_error);
			}
		} else if ($adminmail && $adminmail != '') {
			$addr = decode_ip($userinfo['user_ip']);
			$host = (isset($_SERVER['REMOTE_HOST']) && $_SERVER['REMOTE_HOST'] != '') ? $_SERVER['REMOTE_HOST'] : gethostbyaddr($addr);
			$the_error .= "\r\n\r\nGuest information:\r\nUser id: ".$userinfo['user_id']."\r\nUsername: ".$userinfo['username']."\r\nAdmin: ".($show ? 'Yes' : 'No')."\r\nIP: $addr\r\nHost: $host";
			if (!send_mail($mailer_message, $the_error, 1, 'SQL Error on '.$sitename)) { echo $mailer_message; }
		}
		if (!$bypass_error) {
			$errorpage = '<b>A database error has occurred<br /><br />';
			if (CPG_DEBUG) $errorpage .= "</b><textarea cols='60' rows='6'>$the_error</textarea>";
			else $errorpage .= 'The webmaster has been notified of the error</b>';
			//header("HTTP/1.0 500 Internal Server Error");
			if (function_exists('cpg_error')) {
				cpg_error($errorpage, 'Database Error');
			} else {
				require_once('includes/cpg_page.php');
				$errorpage = cpg_header('Database Error').$errorpage.cpg_footer();
				die($errorpage);
			}
		}
	}

	public function sql_close()
	{
		if ($this->connect_id && !$this->persistent) {
			$this->close();
			$this->connect_id = false;
		}
	}
	public function sql_uquery($query, $bypass_error=FALSE)
	{
		return $this->sql_query($query, $bypass_error, TRUE);
	}
	public function sql_query($query, $bypass_error=FALSE, $unbufferd=FALSE)
	{
		if (empty($query)) { return NULL; }
		global $CLASS;
		if (isset($CLASS['member']) && !defined('INSTALL') && $CLASS['member']->demo && strtoupper($query[0]) != 'S') {
			return NULL;
		}
		if (!$this->connect_id) {
			$the_error = "While executing query \"$query\"\n\nIt seems that the connection to the database server was closed.";
			$this->show_error($the_error, $bypass_error, 1);
		}
		$stime = get_microtime();
		// Remove any pre-existing query
		unset($this->query_result);
		if (SQL_LAYER == 'mysql') {
			// check if it is a SELECT query
			if (strtoupper($query[0]) == 'S') {
				// SPLIT when theres 'UNION (ALL|DISTINT|SELECT)'
				$query_parts = preg_split('/(union)([\s\ \*\/]+)(all|distinct|select)/i', $query, -1, PREG_SPLIT_NO_EMPTY);
				// and then merge the query_parts:
				if ((is_countable($query_parts) ? count($query_parts) : 0) > 1) {
					$query = '';
					foreach($query_parts AS $part) {
						if ($query != '') $query .= 'UNI0N SELECT'; // a ZERO
						$query .= $part;
					}
				}
			}
		}
		if (!is_bool($unbufferd)) {
			$unbufferd = (func_num_args() == 5) ? func_get_args(4) : false;
		}
		$this->query($query, $bypass_error, $unbufferd);
		$this->num_queries++;
		$this->time += (get_microtime()-$stime);
		return $this->query_result;
	}

	public function sql_numrows($result=0)
	{
		if (!$result) { $result = $this->query_result; }
		return ($result) ? $this->num_rows($result) : NULL;
	}

	public function sql_affectedrows($query_id=0)
	{
		if (!$query_id) { $query_id = $this->query_result; }
		return ($this->connect_id && $query_id) ? $this->affected_rows($query_id) : NULL;
	}

	public function sql_numfields($result=0)
	{
		if (!$result) { $result = $this->query_result; }
		return ($result) ? $this->num_fields($result) : NULL;
	}

	public function sql_fieldname($offset, $result=0)
	{
		if (!$result) { $result = $this->query_result; }
		return ($result) ? $this->field_name($result, $offset) : NULL;
	}

	public function sql_fieldtype($offset, $result=0)
	{
		if (!$result) { $result = $this->query_result; }
		return ($result) ? $this->field_type($result, $offset) : NULL;
	}

	public function sql_fetchrow($query_id=0, $result_type=SQL_BOTH)
	{
		$stime = get_microtime();
		if (!$query_id) { $query_id = $this->query_result; }
		$row = ($query_id) ? $this->fetch_array($query_id, $result_type) : NULL;
		$this->time += (get_microtime()-$stime);
		return $row;
	}
	public function sql_ufetchrow($query='', $result_type=SQL_BOTH)
	{
		$query_id = $this->sql_query($query, false, true);
		$result = $this->sql_fetchrow($query_id, $result_type);
		$this->sql_freeresult($query_id);
		return $result;
	}

	public function sql_fetchrowset($query_id=0, $result_type=SQL_BOTH)
	{
		$result = [];
  $stime = get_microtime();
		if (!$query_id) { $query_id = $this->query_result; }
		if ($query_id) {
			while ($row = $this->fetch_array($query_id, $result_type)) {
				$result[] = $row;
			}
		}
		$this->time += (get_microtime()-$stime);
		return $result ?? NULL;
	}
	public function sql_ufetchrowset($query='', $result_type=SQL_BOTH)
	{
		$query_id = $this->sql_query($query, false, true);
		return $this->sql_fetchrowset($query_id, $result_type);
	}

	public function sql_fetchfield()
	{
		return false;
	}

	public function sql_rowseek($rownum, $result=0)
	{
		if (!$result) { $result = $this->query_result; }
		return ($result) ? $this->data_seek($result, $rownum) : NULL;
	}

	public function sql_freeresult(&$query_id)
	{
		if (!$query_id) { $query_id = $this->query_result; }
		if ($query_id) {
			$this->free_result($query_id);
			unset($query_id);
		}
	}

	public function sql_nextid($idfield) {
		if (empty($idfield)) {
			$this->show_error('You must specify an \'idfield\' in $db->sql_nextid($idfield)');
		}
		return ($this->connect_id) ? $this->insert_id($idfield) : NULL;
	}
	public function sql_error($query_id=0) {
		if (!$query_id) { $query_id = $this->query_result; }
		return $this->error($query_id);
	}
	public function sql_escape_string($string) { return $this->escape_string($string); }

	public function sql_insert($table, $fields, $bypass_error=false)
	{
		if (is_array($fields) && !empty($fields)) {
			foreach ($fields AS $field => $value) {
				$qfields[] = $field;
				$qvalues[] = "'".$this->escape_string($value)."'";
			}
			return $this->sql_query('INSERT INTO '.$table.' ('.implode(', ', $qfields).') VALUES ('.implode(', ', $qvalues).')', $bypass_error);
		}
		return false;
	}
	public function sql_update($table, $fields, $where, $bypass_error=false)
	{
		if (is_array($fields) && !empty($fields)) {
			foreach ($fields AS $field => $value) {
				$qfields[] = $field."='".$this->escape_string($value)."'";
			}
			return $this->sql_query('UPDATE '.$table.' SET '.implode(', ', $qfields).' WHERE '.$where, $bypass_error);
		}
		return false;
	}

	public function sql_count($table, $where='')
	{
		if ($where != '') $where = "WHERE $where";
		$query_id = $this->sql_query("SELECT COUNT(*) FROM $table $where", false, true);
		list($count) = $this->sql_fetchrow($query_id, SQL_NUM);
		$this->sql_freeresult($query_id);
		return $count;
	}

	//
	// Specific database management
	//
	public function load_manager()
	{
		if (!empty($this->mngr) && is_object($this->mngr)) return;
		require_once(CORE_PATH.'db/'.DB_TYPE.'_mngr.php');
		$this->mngr = new sql_mngr($this);
	}

	public function get_versions()
	{
		$this->load_manager();
		return $this->mngr->get_versions();
	}
	public function get_details()
	{
		$this->load_manager();
		return $this->mngr->get_details();
	}
	public function create_table($query)
	{
		$this->load_manager();
		return $this->mngr->create_table($query);
	}
	public function alter_table($query)
	{
		$this->load_manager();
		return $this->mngr->alter_table($query);
	}
	public function drop_table($table)
	{
		$this->load_manager();
		return $this->mngr->drop_table($table);
	}
	public function list_databases()
	{
		$this->load_manager();
		return $this->mngr->list_databases();
	}
	public function get_current_schema()
	{
		$this->load_manager();
		return $this->mngr->get_current_schema();
	}
	public function list_schemas()
	{
		$this->load_manager();
		return $this->mngr->list_schemas();
	}
	public function list_tables($schema='')
	{
		$this->load_manager();
		return $this->mngr->list_tables($schema);
	}
	public function list_columns($table, $uniform=true, $backup=false)
	{
		$this->load_manager();
		return $this->mngr->list_columns($table, $uniform, $backup);
	}
	public function list_indexes($table)
	{
		$this->load_manager();
		return $this->mngr->list_indexes($table);
	}
	public function alter_field($mode, $table, $field, $type='', $null=true, $default='')
	{
		$this->load_manager();
		return $this->mngr->alter_field($mode, $table, $field, $type, $null, $default);
	}
	public function alter_index($mode, $table, $name, $columns='')
	{
		$this->load_manager();
		return $this->mngr->alter_index($mode, $table, $name, $columns);
	}
	public function get_sequence($table='', $schema='')
	{
		$this->load_manager();
		return $this->mngr->get_sequence($table, $schema);
	}
	public function list_sequences($schema='')
	{
		$this->load_manager();
		return $this->mngr->list_sequences($schema);
	}
	public function increment_serial($to, $table, $field)
	{
		$this->load_manager();
		return $this->mngr->increment_serial($to, $table, $field);
	}
	public function optimize_table($table, $full=false)
	{
		$this->load_manager();
		return $this->mngr->optimize_table($table, $full);
	}

} // class sql_parent

if (defined('DB_TYPE')) { require(CORE_PATH.'db/'.DB_TYPE.'.php'); }
if (!defined('INSTALL')) {
	$db = new sql_db($dbhost, $dbuname, $dbpass, $dbname);
	if (defined('NO_DB')) { cpg_error('<b>'.NO_DB.', sorry for the inconvenience<br /><br />We should be back shortly</b>'); }
}