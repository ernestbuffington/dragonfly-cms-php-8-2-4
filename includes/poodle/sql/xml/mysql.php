<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL\XML;

class MySQL extends Importer
{

	protected function syncTable(array $table)
	{
		$v5 = version_compare($this->SQL->server_version, '5.0.3', '>=');
		$name = $this->tbl_prefix . $table['name'];

		// Alter table
		if (in_array($name, $this->db_tables)) {
			$q = array();

			# columns
			$cols = $this->SQL->listColumns($name);
			foreach ($table['columns'] as $field) {
				if (empty($field['type'])) {
					continue;
				}
				$m = null;
				$n = $field['name'];
				$t = $field['type'];
				if (!empty($field['length'])) {
					if (!$v5 && 255 < $field['length'] && 'VARCHAR' === $t) {
						$t = 'TEXT';
					} else {
						$t .= "({$field['length']})";
					}
				}
				if ($field['binary']) {
					$t .= ' BINARY';
				}
				if (isset($cols[$n])) {
					$col = $cols[$n];
					if ($col['type'] !== $t
					 || $col['notnull'] != $field['notnull']
					 || $col['comment'] != $field['comment']
					 || $col['default'] !== $field['default']
					){
						$m = 'MODIFY COLUMN';
						if ($field['notnull'] && !$col['notnull'] && isset($field['default'])) {
							$default = $field['default'];
							if ('CURRENT_TIMESTAMP' !== $default) {
								$default = "'{$default}'";
							}
							$this->SQL->query("UPDATE {$name} SET {$n} = {$default} WHERE {$n} IS NULL");
						}
					}
				} else
				if (!empty($field['oldname']) && isset($cols[$field['oldname']])) {
					$m = 'CHANGE COLUMN '.$field['oldname'];
				} else {
					$m = 'ADD COLUMN';
				}
				if ($m) {
					$q[] = $m.' '.$this->get_field_specification($field);
				}
			}

			# indices and foreign keys
			$keys = $this->SQL->listIndices($name);
			$fkeys = null;
			foreach ($table['keys'] as $key) {
				if (!$this->validPlatform($key)) {
					continue;
				}
				$n = $key['name'];
				if ('FOREIGN' === $key['type']) {
					$n = $this->tbl_prefix . $n;
					$fkeys = is_null($fkeys) ? $this->SQL->listForeignKeys($name) : $fkeys;
					if (!isset($fkeys[$n])
					 || $fkeys[$n]['references'] !== $this->tbl_prefix.$key['references']
					 || $fkeys[$n]['ondelete'] !== $key['ondelete']
					 || $fkeys[$n]['onupdate'] !== $key['onupdate']
					) {
						if (isset($fkeys[$n])) {
							$q[] = "DROP FOREIGN KEY {$n}";
						}
						$q[] = "ADD ".$this->get_foreign_key($key);
					}
				} else {
					$fields = array();
					foreach ($key['columns'] as $field) {
						$fn = $field['name'];
						$fields[$fn] = $fn . (empty($field['length']) ? '' : "({$field['length']})");
					}
					$primary = 'PRIMARY' === $n;
					$ADD = false;
					if (isset($keys[$n])) {
						$ADD = !$primary && $keys[$n]['type'] !== $key['type'];
						if (!$ADD) {
							foreach ($fields as $k => $v) {
								if (!isset($keys[$n]['columns'][$k]) || $keys[$n]['columns'][$k] !== $v) {
									$ADD = true;
									break;
								}
							}
						}
						if ($ADD) {
							$q[] = ($primary ? "DROP PRIMARY KEY" : "DROP INDEX {$n}");
						}
					}
					if (!isset($keys[$n]) || $ADD) {
						$q[] = ($primary ? "ADD PRIMARY KEY" : "ADD {$key['type']} INDEX {$n}").' ('.implode(', ',$fields).')';
					}
				}
			}
			if ($q) {
				$q = "ALTER TABLE {$name} ".implode(', ',$q);
				$this->SQL->query($q);
			} else {
				$q = "TABLE {$name} up to date";
			}
			$this->triggerAfterQuery($q, (1 + count($table['keys'])));
		}

		// Create table
		else {
			$fields = $keys = array();

			foreach ($table['columns'] as $field) {
				if (empty($field['type'])) {
					$this->aq_event->index += (1 + count($table['keys']));
					return;
				}
				$fields[] = $this->get_field_specification($field);
			}

			foreach ($table['keys'] as $key) {
				if (!$this->validPlatform($key)) {
					continue;
				}
				$key_fields = array();
				foreach ($key['columns'] as $field) {
					$n = $field['name'];
					$key_fields[$n] = $n . (empty($field['length']) ? '' : "({$field['length']})");
				}
				$key_fields = implode(',',$key_fields);
				if ('PRIMARY' === $key['name']) {
					$fields[] = "PRIMARY KEY ({$key_fields})";
				} else
				if ('FOREIGN' === $key['type']) {
					$fields[] = $this->get_foreign_key($key);
				} else {
					$keys[] = "CREATE {$key['type']} INDEX {$key['name']} ON {$name} ({$key_fields})";
				}
			}

			$charset = $this->SQL->get_charset();
			$this->SQL->query("CREATE TABLE {$name} (".implode(',',$fields).")"
				.(empty($table['engine'])?'':" ENGINE = {$table['engine']}")
				.(empty($table['comment'])?'':" COMMENT = ".$this->SQL->quote($table['comment']))
				." DEFAULT CHARACTER SET = {$charset} COLLATE = {$charset}_bin");
			$this->triggerAfterQuery("CREATE TABLE {$name}", 1 + (count($table['keys']) - count($keys)));

			foreach ($keys as $query) {
				$this->SQL->query($query);
				$this->triggerAfterQuery($query);
			}

			$this->db_tables[$name] = $name;
		}
	}

	protected function syncTableTrigger(array $table, array $trigger)
	{
		static $triggers = array();
		if ($this->validPlatform($trigger)) {
			$table = "{$this->tbl_prefix}{$table['name']}";
			if (!isset($triggers[$table])) {
				$triggers[$table] = $this->SQL->listTriggers($table);
			}
			if (!in_array($trigger['name'], $triggers) || $triggers[$trigger['name']]['statement'] != $trigger['statement']) {
				$this->SQL->query("DROP TRIGGER IF EXISTS {$trigger['name']}");
				$this->SQL->query("CREATE TRIGGER {$trigger['name']} {$trigger['timing']} {$trigger['event']} ON {$table} FOR EACH ROW {$trigger['statement']}");
			}
			$this->triggerAfterQuery("CREATE TRIGGER {$trigger['name']} ON {$table}");
		}
	}

	private function get_foreign_key($key)
	{
		if ('FOREIGN' === $key['type']) {
			$ref_fields = array();
			foreach ($key['columns'] as $field) {
				$n = $field['name'];
				$ref_fields[$n] = empty($field['refcolumn']) ? $n : $field['refcolumn'];
			}
			return "CONSTRAINT {$this->tbl_prefix}{$key['name']} FOREIGN KEY (".implode(',',array_keys($ref_fields)).") REFERENCES {$this->tbl_prefix}{$key['references']} (".implode(',',$ref_fields).")"
				.($key['ondelete']?" ON DELETE {$key['ondelete']}":'')
				.($key['onupdate']?" ON UPDATE {$key['onupdate']}":'');
		}
	}

	private function get_field_specification($field)
	{
		if (!isset($field['type'])) {
			return false;
		}
		$t = $field['type'];
		if ('BLOB' === $t) { $t = 'LONGBLOB'; }
		else if ('TEXT' === $t || 'SEARCH' === $t) { $t = 'LONGTEXT'; }
		else { $t = str_replace('SERIAL', 'INT', $t); }

		$v = $field['name'].' '.$t
			. (empty($field['length']) ? '' : "({$field['length']})")
			. (empty($field['binary']) ? '' : ' BINARY')
			. (empty($field['notnull']) ? '' : ' NOT NULL')
			. (false === strpos($field['type'], 'SERIAL') ? '' : ' AUTO_INCREMENT');
		if (isset($field['default'])) {
			if ('CURRENT_TIMESTAMP' === $field['default']) {
				$v .= " DEFAULT {$field['default']}";
			} else {
				$v .= " DEFAULT '{$field['default']}'";
			}
		}
		if (!empty($field['comment'])) {
			$v .= " COMMENT ".$this->SQL->quote($field['comment']);
		}
		return $v;
	}

}
