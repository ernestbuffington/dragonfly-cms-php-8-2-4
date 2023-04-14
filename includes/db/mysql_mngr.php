<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/db/mysql_mngr.php,v $
  $Revision: 1.39 $
  $Author: nanocaiordo $
  $Date: 2008/02/05 12:44:43 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

define('DBFT_INT4', 'INT4');
define('DBFT_INT3', 'INT3');
define('DBFT_INT2', 'INT2');
define('DBFT_INT1', 'INT1');
define('DBFT_VARBINARY', 'VARBINARY');
define('DBFT_BLOB', 'BLOB');
define('DBFT_INDEX_FULLTEXT', 'FULLTEXT');
define('DBFT_BOOL', 'BOOL');

class sql_mngr
{

	//
	// Constructor
	//
	function __construct(&$owner)
	{
		$this->_owner =& $owner;
		$this->fields = array(
			'SERIAL4' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
			'SERIAL8' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
			'TEXT' => 'TEXT',       # 1 to 65535 bytes
			'BLOB' => 'BLOB',       # see TEXT
			'BOOL' => 'BOOL',       # synonyms for TINYINT(1)
//			'BOOL' => 'TINYINT(1)', # MySQL >= 5.0.3 -> BIT
//			'BIT' => 'BIT',         # MySQL < 5.0.3 -> BIT == TINYINT(1); # MySQL >= 5.0.3 -> BIT(N) == bits per value
			'INT1' => 'TINYINT',    # (1-4) -128 to 127 signed || 0 to 255 unsigned
			'INT2' => 'SMALLINT',   # (1-6) -32768 to 32767 signed || 0 to 65535 unsigned
			'INT3' => 'MEDIUMINT',  # (1-8) -8388608 to 8388607 signed || 0 to 16777215 unsigned
			'INT4' => 'INT',        # (1-11) -2147483648 to 2147483647 signed || 0 to 4294967295 unsigned
			'INT8' => 'BIGINT',     # (1-20) -9223372036854775808 to 9223372036854775807 || 0 to 18446744073709551615 unsigned
			'CHAR' => 'CHAR',       # (1-255)
			'VARCHAR' => 'VARCHAR', # (1-255)
			'FLOAT4' => 'FLOAT',
			'FLOAT8' => 'DOUBLE',
			'DECIMAL' => 'DECIMAL'  # (precision, scale)
		);
	}
	function _create_patterns()
	{
		if (!empty($this->query_pattern)) { return; }
		# fix uniform field types to DB specific types in ALTER TABLE and CREATE TABLE
//		$this->query_pattern = array('/ VARBINARY(\([0-9]+\))/s');
//		$this->query_replace = array(' VARCHAR\\1 BINARY');
		foreach ($this->fields as $uni => $field) {
			# if we don't use this then everything messes up
			$this->query_pattern[] = "/ $uni([,\ \(])/s";
			$this->query_replace[] = " $field\\1";
		}
	}

	function get_versions()
	{
		$version = [];
        $version['engine'] = 'MySQL';
		$version['client'] = mysql_get_client_info();
		$version['server'] = mysql_get_server_info();
		return $version;
	}

	function get_details()
	{
		$details = [];
        $result = $this->_owner->query('SHOW VARIABLES', false, true);
		while ($row = mysql_fetch_row($result)) { $details[$row[0]] = $row[1]; }
		mysql_free_result($result);
		$details['engine']  = 'MySQL';
		$details['client']  = mysql_get_client_info();
		$details['server']  = mysql_get_server_info();
		$details['unicode'] = (version_compare($details['server'], '4.1') >= 0);
		$details['host'] = mysql_get_host_info();
		return $details;
	}

	function create_table($query)
	{
		$this->_create_patterns();
		$query = preg_replace($this->query_pattern, $this->query_replace, $query);
		return $this->_owner->query('CREATE TABLE '.$query.' ENGINE=MyISAM'.(DB_CHARSET ? ' DEFAULT CHARSET='.DB_CHARSET : ''));
		//return $this->_owner->query('CREATE TABLE IF NOT EXISTS '.$query.' ENGINE=MyISAM');
	}

	function alter_table($query)
	{
		$this->_create_patterns();
		$query = preg_replace($this->query_pattern, $this->query_replace, $query);
		return $this->_owner->query('ALTER TABLE '.$query);
	}

	function drop_table($table)
	{
		return $this->_owner->query('DROP TABLE '.$table);
		//return $this->_owner->query('DROP TABLE IF EXISTS '.$query);
	}

	function list_databases()
	{
		$databases = array();
		if (strpos(ini_get('disable_functions'), 'mysql_list_dbs') === false) {
			$result = mysqli_query('SHOW DATABASES');
			if (!$result) $result = $this->_owner->query('SHOW DATABASES', true);
			if ($result) {
				while (list($name) = mysql_fetch_row($result)) { $databases[$name] = $name; }
				mysql_free_result($result);
			}
		}
		if (empty($databases) && defined('ADMIN_PAGES')) {
			global $dbname;
			$databases[$dbname] = $dbname;
		}
		return $databases;
	}

	function list_tables($database)
	{
		global $prefix, $user_prefix;
		$result = $this->_owner->query(empty($database) ? 'SHOW TABLES' : "SHOW TABLES FROM `$database`");
		$tables = array();
		while (list($name) = mysql_fetch_row($result)) {
			$id = preg_replace("#^($prefix|$user_prefix)_#", '', $name);
			$tables[$id] = $name;
		}
		mysql_free_result($result);
		return $tables;
	}

	function list_columns($table, $uniform=true)
	{
		# SHOW FIELDS is a synonym http://dev.mysql.com/doc/mysql/en/SHOW_COLUMNS.html
		if ($result = $this->_owner->query("SHOW COLUMNS FROM $table", defined('INSTALL'), true))
		{
			if (empty($this->type_pattern)) {
				$this->type_pattern = array('/^VARCHAR(\([0-9]+\)) BINARY$/');
				$this->type_replace = array('VARBINARY\\1');
				foreach ($this->fields as $uni => $field) {
					# if we don't use ^$ then everything messes up
					$this->type_pattern[] = "/^$field(\$|\()/";
					$this->type_replace[] = $uni.'\\1';
				}
			}
			$return = array();
			while ($row = mysql_fetch_assoc($result)) {
				$field = $row['Field'];
				$row['Type'] = strtoupper($row['Type']);
				if ($uniform) {
					if ($row['Extra'] == 'auto_increment') {
						$row['Type'] = (strpos($row['Type'], 'BIGINT') === false) ? 'SERIAL4' : 'SERIAL8';
					} elseif (strpos($row['Type'], 'INT(1)') !== false) {
						$row['Type'] = 'BOOL';
					} else {
						# UNSIGNED is not a SQL standard
						$row['Type'] = str_replace(' UNSIGNED', '', $row['Type']);
						if (strpos($row['Type'], 'INT(') !== false) {
							$row['Type'] = preg_replace('#\([0-9]+\)#', '', $row['Type']);
						}
						$row['Type'] = preg_replace($this->type_pattern, $this->type_replace, $row['Type'], 1);
					}
				} else {
					$return[$field]['Extra'] = strtoupper($row['Extra']);
				}
				$return[$field]['Field'] = $field;
				$return[$field]['Type'] = $row['Type'];
				$return[$field]['Null'] = intval($row['Null'] == 'YES');
				$return[$field]['Default'] = $row['Default'];
			}
			mysql_free_result($result);
			return $return;
		}
		return false;
	}

	function list_indexes($table)
	{
		# SHOW KEYS is a synonym http://dev.mysql.com/doc/mysql/en/SHOW_INDEX.html
		if ($result = $this->_owner->query("SHOW INDEX FROM $table", defined('INSTALL'), true)) {
			$return = array();
			while ($row = mysql_fetch_assoc($result)) {
				$key = $row['Key_name'];
				$i = intval($row['Seq_in_index'])-1;
				$return[$key]['name'] = $key;
				$return[$key]['unique'] = ($row['Non_unique'] == '0');
				$return[$key]['type'] = $row['Index_type']; # BTREE or FULLTEXT
				$return[$key]['columns'][$i] = array('name' => $row['Column_name']);
//					'Sub_part' => '',
//					'Packed' => '',
//					'Null' => ''
			}
			mysql_free_result($result);
			return $return;
		}
		return false;
	}

	function alter_field($mode, $table, $field, $type='', $null=TRUE, $default=NULL)
	{
		switch ($mode)
		{
			case 'add':
				if ($type == 'TEXT' || $type == 'BLOB') {
					return $this->alter_table("$table ADD $field $type".($null?'':' NOT').' NULL');
				} else {
					return $this->alter_table("$table ADD $field $type".($null?'':' NOT').' NULL DEFAULT '.(isset($default)?"'$default'":'NULL'));
				}
			case 'drop':
				return $this->_owner->query("ALTER TABLE $table DROP $field");

			case 'change':
				if (!is_array($field)) $field = array($field, $field);
				if ($type == 'TEXT' || $type == 'BLOB') {
					return $this->alter_table("$table CHANGE $field[0] $field[1] $type".($null?'':' NOT').' NULL');
				}
				if (preg_match('#VARBINARY#mi', $type)) {
					$ret = $result = $this->_owner->query("SELECT $field[1] FROM $table GROUP BY $field[1]");
					if ($ret && $this->_owner->num_rows($result) > 0) {
						$ret = $this->_owner->query("ALTER TABLE $table ADD df_varbin_tmp $type NULL DEFAULT NULL");
						if ($ret) {
							$t_indexes = $this->list_indexes($table);
							if (!isset($t_indexes[$field[1]])) { $ret = $this->alter_index('index', $table, $field[1], $field[1].'(8)'); }
							$t_indexes = null;
						}
						if ($ret) {
							if (!function_exists('inet_pton')) { require(CORE_PATH.'functions/inet.php'); }
							while ($row = $this->_owner->fetch_array($result, SQL_NUM)) {
								$ip = inet_pton(decode_ip($row[0]));
								$ip = empty($ip) ? 'DEFAULT' : $this->_owner->binary_safe($ip);
								$ret = $this->_owner->query("UPDATE $table SET df_varbin_tmp=$ip WHERE $field[1]='".$this->_owner->escape_string($row[0])."'");
								if (!$ret) break;
							}
							if ($ret) $ret = $this->_owner->query("ALTER TABLE $table DROP $field[1]");
							if ($ret) $ret = $this->_owner->query("ALTER TABLE $table CHANGE df_varbin_tmp $field[1] $type NULL DEFAULT NULL");
						}
						$this->_owner->free_result($result);
						return $ret;
					} // rows == 0 then contine to alter the table
					$this->_owner->free_result($result);
				}
				return $this->alter_table("$table CHANGE $field[0] $field[1] $type".($null?'':' NOT').' NULL DEFAULT '.(isset($default)?"'$default'":'NULL'));
		}
	}

	function alter_index($mode, $table, $name, $columns='')
	{
		switch ($mode)
		{
			case 'index':
				return $this->_owner->query("CREATE INDEX $name ON $table ($columns)");

			case 'unique':
				if ($name == 'PRIMARY') {
					return $this->_owner->query("ALTER TABLE $table ADD PRIMARY KEY ($columns)");
				} else {
					return $this->_owner->query("CREATE UNIQUE INDEX $name ON $table ($columns)");
				}
			case 'fulltext':
				return $this->_owner->query("CREATE FULLTEXT INDEX $name ON $table ($columns)");
			case 'drop':
				$key = ($name == 'PRIMARY') ? 'PRIMARY KEY' : 'INDEX '.$name;
				return $this->_owner->query("ALTER TABLE $table DROP $key");
		}
	}

	function increment_serial($to, $table, $field)
	{
		# Don't have to do anything for this DB.
	}
	function optimize_table($table, $full)
	{
		return $this->_owner->query("OPTIMIZE TABLE $table");
	}

}
