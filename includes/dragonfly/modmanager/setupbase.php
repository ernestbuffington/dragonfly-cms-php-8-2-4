<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\ModManager;

define('_INST_NO_DB_CLASS', 'There\'s no database class defined !');
define('_INST_ERROR_EXECUTE', '<strong>MySQL Error</strong> while executing:<br />');

abstract class SetupBase
{
	public
		/**
		 * v9
		 */
		$author      = '',
		$dbtables    = array(/*'table1','table2'*/), // uninstall
		$description = '',
		$modname     = '',
		$radmin      = false,
		$version     = '0.0.0.1',
		$website     = '',
		/**
		 * v10
		 */
		$blocks      = false, // place all active blocks in this module
		$bypass      = false, // bypass 42* standard mysql error states
		$test        = false, // true: don't execute the db queries
		$config      = array(/* 'cfg_field' => 'cfg_value' */), // cfg_name = directory name
		$userconfig  = array(); // not used yet

	/**
	 * v9
	 *
	public function install() {}
	public function uninstall() {}
	public function upgrade($prev_version) {}
	*/

	/**
	 * v10 module must define these, each must return boolean
	 */

//	public function pre_downgrade($prev_version) { return false; }
//	public function post_downgrade($prev_version) { return false; }

	abstract public function pre_install();

	abstract public function post_install();

	abstract public function pre_upgrade($prev_version);

	abstract public function post_upgrade($prev_version);

	abstract public function pre_uninstall();

	abstract public function post_uninstall();

	public function getXMLSchema() { return null; }

	public function getXMLData() { return null; }

	/**
	 * v10 internals
	 */

	public
		$progress;

	protected
		$queries = array(),
		$rollbacks = array();
	protected static
		$tables = array();

	private
		$bypass_sql_states = array(
			1050 => '42S01', 1051 => '42S02', 1060 => '42S21', 1061 => '42000',
			1068 => '42000',  1091 => '42000', 1109 => '42S02', 1146 => '42S02',
			/*1062 => '23000', 1072 => '42000', 1586 => '23000',*/),
		$installer,
		$prep = array(
			'CREATE' => 'CREATE TABLE %s (%s)',
			'REN' => 'ALTER TABLE %s RENAME TO %s',
			'DROP' => 'DROP TABLE %s',
			'DELETE' => 'DELETE FROM %s WHERE %s',
			'INSERT' => 'INSERT IGNORE INTO %s VALUES (%s)',
			'INSERT_MULTIPLE' => 'INSERT IGNORE INTO %s (%s) VALUES %s',
			'UPDATE' => 'UPDATE %s SET %s',
			'ADD' => 'alter_field(add, %s, %s, %s, %s, %s)',
			'DEL' => 'alter_field(drop, %s, %s)',
			'CHANGE' => 'alter_field(change, %s, %s, %s, %s, %s)',
			'INDEX' => 'alter_index(%s, %s, %s, %s)',
			'UNIQUE' => 'alter_index(%s, %s, %s, %s)',
			'FULLTEXT' => 'alter_index(%s, %s, %s, %s)',
			'DROP_INDEX' => 'alter_index(drop, %s, %s)',
			'INC_SERIAL' => 'incremental_serial(%s, %s, %s)');

	public function __construct()
	{
		global $db;
		if ($db && !static::$tables) {
			static::$tables = $db->list_tables();
		}
	}

	final public static function clearCache()
	{
		$C = \Dragonfly::getKernel()->CACHE;
		$C->delete('Dragonfly/Config');
		$C->delete('Dragonfly/Page/Menu/Admin');
		$C->delete('modules_active');
		$C->delete('waitlist');
	}

	final public function table_exists($table)
	{
		return isset(static::$tables[$table]);
	}

	final public function add_query($type, $table, $values='', $rollback='')
	{
		if ('CREATE' === $type && $this->table_exists($table)) {
			return;
		}
		$this->rollbacks[] = $this->queries[] = array(
			$type,
			$GLOBALS['db']->TBL->$table,
			$values,
			$rollback
		);
		return true;
	}

	final public function bypass($sqlstate)
	{
		// '00000' when debugging always getting the same error?
		return $this->bypass && (0 === strpos($sqlstate, '42') || 0 == $sqlstate);
	}

	final public function exec()
	{
		global $db, $prefix;
		foreach ($this->queries as $i => $query) {
			$sql = null;
			switch ($query[0]) {
				case 'DROP':
				case 'INSERT':
				case 'DELETE':
				case 'UPDATE':
				case 'INSERT_MULTIPLE':
				case 'DEL':
				case 'CHANGE':
					if (DF_MODE_DEVELOPER && empty($this->rollbacks[$i][3])) {
						trigger_error($query[0] .' without rollback value.');
					}
			}
			switch ($query[0]) {
				case 'CREATE':
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], $query[1], $query[2]);
					} else {
						try {
							$db->create_table("$query[1] ($query[2])");
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass()) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'REN':
					$sql = sprintf($this->prep[$query[0]], $query[1], $prefix.'_'.$query[2]);
					break;

				case 'DROP':
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], $query[1]);
					} else {
						try {
							$db->drop_table($query[1]);
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass($db->sqlstate)) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'INSERT':
				case 'DELETE':
				case 'UPDATE':
					$sql = sprintf($this->prep[$query[0]], $query[1], $query[2]);
					break;

				case 'INSERT_MULTIPLE':
					$sql = sprintf($this->prep[$query[0]], $query[1], $query[2][0], $query[2][1]);
					break;

				case 'ADD':
					if (!is_array($query[2])) {
						preg_match('/([a-z0-9\_]+)[\s\'"`]+([a-z0-9\(\)]+)(.*[\s]+DEFAULT[\s]+([0-9]+|NULL|[\'"](.*)[\'"]))?/is', $query[2], $match);
						$query[2] = array(
							$match[1],
							(false !== stripos($match[2],'INT') && false !== stripos($query[2], 'UNSIGNED')) ? trim($match[2]).' UNSIGNED' : $match[2],
							false === stripos($query[2], 'NOT NULL'),
							!empty($match[3]) ? (isset($match[5]) ? $match[5] : $match[4]) : false
						);
					}
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], $query[1], $query[2][0], $query[2][1], $query[2][2], $query[2][3]);
					} else {
						try {
							$db->alter_field('add', $query[1], $query[2][0], $query[2][1], $query[2][2], $query[2][3]);
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass($db->sqlstate)) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'DEL':
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], $query[1], $query[2]);
					} else {
						try {
							$db->alter_field('drop', $query[1], $query[2]);
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass($db->sqlstate)) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'CHANGE':
					if (!is_array($query[2])) {
						preg_match('/([a-z0-9\_]+)[\s\'"`]+([a-z0-9\_]+)[\s\'"`]+([a-z0-9\(\)]+)(.*[\s]+DEFAULT[\s]+([0-9]+|NULL|[\'"].*[\'"]))?/is', $query[2], $match);
						$query[2] = array(
							array($match[1], $match[2]),
							$match[3],
							false === stripos($query[2], 'NOT NULL'),
							!empty($match[4]) ? (preg_match('#[\'"]#', $match[5][0]) ? substr($match[5],1, -1) : $match[5]) : false
						);
					}
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], $query[1], $query[2][0], $query[2][1], $query[2][2], $query[2][3]);
					} else {
						try {
							$db->alter_field('change', $query[1], $query[2][0], $query[2][1], $query[2][2], $query[2][3]);
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass($db->sqlstate)) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'INDEX':
				case 'UNIQUE':
				case 'FULLTEXT':
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], strtolower($query[0]), $query[1], $query[2], $query[3]);
					} else {
						try {
							$db->alter_index(strtolower($query[0]), $query[1], $query[2], $query[3]);
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass($db->sqlstate)) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'DROP_INDEX':
					if ($this->test) {
						$sql = sprintf($this->prep[$query[0]], $query[1], $query[2]);
					} else {
						try {
							$db->alter_index('drop', $query[1], $query[2]);
						} catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
							if (!$this->bypass($db->sqlstate)) {
								throw $e;
							}
						}
						echo $this->progress;
						flush();
					}
					break;

				case 'INC_SERIAL':
					# Don't necessarily have to run the query, but don't want to kill the install.
					$sql = true;
					if (SQL_LAYER == 'postgresql') {
						list($start) = $db->uFetchRow("SELECT CASE WHEN is_called THEN last_value ELSE last_value-increment_by END FROM ".$query[1]."_".$query[2]['field']."_seq");
						if ($start <= $query[2]['value']) {
							if ($this->test) {
								$sql = sprintf($this->prep[$query[0]], $query[2]['value'], $query[1], $query[2]['field']);
							} else {
								try {
									$db->increment_serial($query[2]['value'], $query[1], $query[2]['field']);
								} catch (\Poodle\SQL\Exception $e) {
									trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
									trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
									if (!$this->bypass($db->sqlstate)) {
										throw $e;
									}
								}
								echo $this->progress;
								flush();
							}
						}
					}
					break;

			}
			if ($this->test) {
				echo $sql.'<br />';
			} elseif ($sql) {
				try {
					$db->query($sql);
				} catch (\Poodle\SQL\Exception $e) {
					trigger_error('Failed: ' .$sql, E_USER_WARNING);
					trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
					if (!$this->bypass($db->sqlstate)) {
						throw $e;
					}
				}
				if ($this->progress) {
					echo $this->progress;
					flush();
				}
			}
			$query = $this->queries[$i] = null;
		}
		$db->TBL->loadTables(true);
		static::$tables = $db->list_tables();
		return true;
	}

	final public function rollback()
	{
		$this->queries = array();
		global $db, $prefix;
		for ($i=count($this->rollbacks)-1; $i>=0; --$i) {
			switch($this->rollbacks[$i][0]) {
				case 'CREATE':
					try { $db->drop_table($this->rollbacks[$i][1]); }
					catch (\Poodle\SQL\Exception $e) {
						trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
					}
					break;

				case 'DROP':
					if ($this->rollbacks[$i][3]) {
						try { $db->create_table("{$this->rollbacks[$i][1]} ({$this->rollbacks[$i][3]})"); }
						catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
						}
					}
					break;

				case 'INSERT':
				case 'INSERT_MULTIPLE':
					if ($this->rollbacks[$i][3]) {
						try { $db->query(sprintf($this->prep['DELETE'], $this->rollbacks[$i][1], $this->rollbacks[$i][3])); }
						catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
						}
					}
					break;

				case 'DELETE':
					if ($this->rollbacks[$i][3]) {
						try { $db->query(sprintf($this->prep['INSERT'], $this->rollbacks[$i][1], $this->rollbacks[$i][3])); }
						catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
						}
					}
					break;

				case 'UPDATE':
					if ($this->rollbacks[$i][3]) {
						try { $db->query(sprintf($this->prep['UPDATE'], $this->rollbacks[$i][1], $this->rollbacks[$i][3])); }
						catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
						}
					}
					break;

				case 'ADD':
					try { $db->alter_field('drop', $this->rollbacks[$i][1], $this->rollbacks[$i][2][0]); }
					catch (\Poodle\SQL\Exception $e) {
						trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
						trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
					}
				break;

				case 'DEL':
					if ($this->rollbacks[$i][3]) {
						try { $db->alter_field('add', $this->rollbacks[$i][1], $this->rollbacks[$i][3][0], $this->rollbacks[$i][3][1], $this->rollbacks[$i][3][2], $this->rollbacks[$i][3][3]); }
						catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
						}
					}
					break;

				case 'CHANGE':
					if ($this->rollbacks[$i][3]) {
						try { $db->query('ALTER TABLE '.$this->rollbacks[$i][1].' CHANGE '.$this->rollbacks[$i][3]); }
						catch (\Poodle\SQL\Exception $e) {
							trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
							trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
						}
					}
					break;

				case 'REN':
					try { $db->query('ALTER TABLE '.$prefix.'_'.$this->rollbacks[$i][2].' RENAME TO '.$this->rollbacks[$i][1]); }
					catch (\Poodle\SQL\Exception $e) {
						trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
						trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
					}
					break;

				case 'FULLTEXT':
				case 'UNIQUE':
				case 'INDEX':
					try { $db->alter_index('drop', $this->rollbacks[$i][1], $this->rollbacks[$i][2]); }
					catch (\Poodle\SQL\Exception $e) {
						trigger_error('Failed: ' .$e->getQuery(), E_USER_WARNING);
						trigger_error('Reason: ' .$e->getMessage(), E_USER_WARNING);
					}
					break;
			}
			$this->rollbacks[$i] = null;
		}
		return true;
	}
}
