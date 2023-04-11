<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\SQL\Manager;

if (!defined('DBFT_INT4')) define('DBFT_INT4', 'INT4');
if (!defined('DBFT_INT3')) define('DBFT_INT3', 'INT3');
if (!defined('DBFT_INT2')) define('DBFT_INT2', 'INT2');
if (!defined('DBFT_INT1')) define('DBFT_INT1', 'INT1');
if (!defined('DBFT_VARBINARY')) define('DBFT_VARBINARY', 'VARBINARY');
if (!defined('DBFT_BLOB')) define('DBFT_BLOB', 'BLOB');
if (!defined('DBFT_INDEX_FULLTEXT')) define('DBFT_INDEX_FULLTEXT', 'FULLTEXT');
if (!defined('DBFT_BOOL')) define('DBFT_BOOL', 'BOOL');

class MySQL extends \Poodle\SQL\Manager\MySQL
{

	//
	// Constructor
	//
	function __construct(\Poodle\SQL $SQL)
	{
		parent::__construct($SQL);
		$this->fields = array(
			'SERIAL4' => 'INT NOT NULL AUTO_INCREMENT',
			'SERIAL8' => 'BIGINT NOT NULL AUTO_INCREMENT',
			'TEXT' => 'TEXT',
			'BLOB' => 'BLOB',
			'BOOL' => 'BOOL',       # synonyms for TINYINT(1)
//			'BOOL' => 'TINYINT(1)', # MySQL >= 5.0.3 -> BIT
//			'BIT' => 'BIT',         # MySQL < 5.0.3 -> BIT == TINYINT(1); # MySQL >= 5.0.3 -> BIT(N) == bits per value
			'INT1' => 'TINYINT',
			'INT2' => 'SMALLINT',
			'INT3' => 'MEDIUMINT',
			'INT4' => 'INT',
			'INT8' => 'BIGINT',
			'CHAR' => 'CHAR',
			'VARCHAR' => 'VARCHAR',
			'FLOAT4' => 'FLOAT',
			'FLOAT8' => 'DOUBLE',
			'DECIMAL' => 'DECIMAL'
		);
	}
	private function create_patterns()
	{
		if (!empty($this->query_pattern)) { return; }
		# fix uniform field types to DB specific types in ALTER TABLE and CREATE TABLE
//		$this->query_pattern = array('/ VARBINARY(\([0-9]+\))/s');
//		$this->query_replace = array(' VARCHAR\\1 BINARY');
		foreach ($this->fields as $uni => $field) {
			# if we don't use this then everything messes up
			$this->query_pattern[] = "/ {$uni}([,\ \(])/s";
			$this->query_replace[] = " {$field}\\1";
		}
	}

	public function get_versions()
	{
		$version['engine'] = $this->SQL->engine;
		$version['client'] = $this->SQL->client_info;
		$version['server'] = $this->SQL->server_info;
		return $version;
	}

	public function get_details()
	{
		$result = $this->SQL->query('SHOW VARIABLES');
		while ($row = $result->fetch_row()) { $details[$row[0]] = $row[1]; }
		$result->free();
		$details['engine']  = $this->SQL->engine;
		$details['client']  = $this->SQL->client_info;
		$details['server']  = $this->SQL->server_info;
		$details['unicode'] = (version_compare($details['server'], '4.1') >= 0);
		$details['host']    = $this->SQL->host_info;
		return $details;
	}

	public function create_table($query)
	{
		$this->create_patterns();
		$query = preg_replace($this->query_pattern, $this->query_replace, $query);
		return $this->SQL->query('CREATE TABLE '.$query.' ENGINE=MyISAM'.(DB_CHARSET ? ' DEFAULT CHARSET='.DB_CHARSET : ''));
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

	public function list_tables($database=null)
	{
		$prefix = $this->SQL->TBL->prefix;
		$uprefix = \Dragonfly::getKernel()->db_user_prefix;
		$result = $this->SQL->query(empty($database) ? 'SHOW TABLES' : "SHOW TABLES FROM `{$database}`");
		$tables = array();
		while (list($name) = $result->fetch_row()) {
			$id = preg_replace("#^({$prefix}|{$uprefix})_#", '', $name);
			$tables[$id] = $name;
		}
		$result->free();
		return $tables;
	}

	public function list_columns($table, $uniform=true)
	{
		if ($result = $this->SQL->query("SHOW COLUMNS FROM {$table}", defined('INSTALL'), true)) {
			if (empty($this->type_pattern)) {
				$this->type_pattern = array('/^VARCHAR(\([0-9]+\)) BINARY$/');
				$this->type_replace = array('VARBINARY\\1');
				foreach ($this->fields as $uni => $field) {
					# if we don't use ^$ then everything messes up
					$this->type_pattern[] = "/^{$field}($|\()/";
					$this->type_replace[] = $uni.'\\1';
				}
			}
			$return = array();
			while ($row = $result->fetch_assoc()) {
				$field = $row['Field'];
				$row['Type'] = strtoupper($row['Type']);
				if ($uniform) {
					if ($row['Extra'] == 'auto_increment') {
						$row['Type'] = (strpos($row['Type'], 'BIGINT') === false) ? 'SERIAL4' : 'SERIAL8';
					} elseif (strpos($row['Type'], 'INT(1)') !== false) {
						$row['Type'] = 'BOOL';
					} else {
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
				$return[$field]['Null'] = (int)($row['Null'] == 'YES');
				$return[$field]['Default'] = $row['Default'];
			}
			$result->free();
			return $return;
		}
		return false;
	}

	public function list_indexes($table)
	{
		if ($result = $this->SQL->query("SHOW INDEX FROM $table", defined('INSTALL'), true)) {
			$return = array();
			while ($row = $result->fetch_assoc()) {
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
			$result->free();
			return $return;
		}
		return false;
	}

	public function alter_field($mode, $table, $field, $type='', $null=TRUE, $default=NULL)
	{
		switch ($mode)
		{
			case 'add':
				if ($type == 'TEXT') {
					return $this->alter_table("$table ADD $field $type".($null?'':' NOT').' NULL');
				} else {
					return $this->alter_table("$table ADD $field $type".($null?'':' NOT').' NULL DEFAULT '.(isset($default)?"'$default'":'NULL'));
				}

			case 'drop':
				return $this->SQL->query("ALTER TABLE $table DROP $field");

			case 'change':
				if (!is_array($field)) $field = array($field, $field);
				if ($type == 'TEXT' || $type == 'BLOB') {
					return $this->alter_table("$table CHANGE $field[0] $field[1] $type".($null?'':' NOT').' NULL');
				}
				if (false !== stripos($type, 'VARBINARY')) {
					$ret = $result = $this->SQL->query("SELECT $field[1] FROM $table GROUP BY $field[1]");
					if ($ret && $this->SQL->num_rows($result) > 0) {
						$ret = $this->SQL->query("ALTER TABLE $table ADD df_varbin_tmp $type NULL DEFAULT NULL");
						if ($ret) {
							$t_indexes = $this->list_indexes($table);
							if (!isset($t_indexes[$field[1]])) { $ret = $this->alter_index('index', $table, $field[1], $field[1].'(8)'); }
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
							if ($ret) $ret = $this->SQL->query("ALTER TABLE $table CHANGE df_varbin_tmp $field[1] $type NULL DEFAULT NULL");
						}
						$this->SQL->free_result($result);
						return $ret;
					} // rows == 0 then contine to alter the table
					$this->SQL->free_result($result);
				}
				return $this->alter_table("$table CHANGE $field[0] $field[1] $type".($null?'':' NOT').' NULL DEFAULT '.(isset($default)?"'$default'":'NULL'));
		}
	}

	public function alter_index($mode, $table, $name, $columns='')
	{
		switch ($mode)
		{
			case 'index':
				return $this->SQL->query("CREATE INDEX $name ON $table ($columns)");

			case 'unique':
				if ($name == 'PRIMARY') {
					return $this->SQL->query("ALTER TABLE $table ADD PRIMARY KEY ($columns)");
				} else {
					return $this->SQL->query("CREATE UNIQUE INDEX $name ON $table ($columns)");
				}
			case 'fulltext':
				return $this->SQL->query("CREATE FULLTEXT INDEX $name ON $table ($columns)");
			case 'drop':
				$key = ($name == 'PRIMARY') ? 'PRIMARY KEY' : 'INDEX '.$name;
				return $this->SQL->query("ALTER TABLE $table DROP $key");
		}
	}

	public function increment_serial($to, $table, $field)
	{
		# Don't have to do anything for this DB.
	}

	public function optimize_table($table, $full=false)
	{
		parent::optimize($table);
	}

}
