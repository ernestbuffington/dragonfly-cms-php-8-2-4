<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL\XML;

class PostgreSQL extends Importer
{

	protected function syncTable(array $table)
	{
		$name = $this->tbl_prefix . $table['name'];

		// Alter table
		if (in_array($name, $this->db_tables)) {
			$q = $keys = array();

			# columns
			$cols = $this->SQL->listColumns($name);
			foreach ($table['columns'] as $field) {
				if (empty($field['type'])) {
					continue;
				}
				$n = $field['name'];
				$t = $this->get_type($field);
				if (isset($cols[$n])) {
					$col = $cols[$n];
					if ($col['type'] !== $t) {
						$q[] = "ALTER COLUMN {$field['name']} TYPE {$t}";
					}
					if ($col['notnull'] !== $field['notnull']) {
						$q[] = "ALTER COLUMN {$field['name']} ".($field['notnull']?'SET':'DROP')." NOT NULL";
					}
					if ($col['default'] !== $field['default']) {
						$q[] = "ALTER COLUMN {$field['name']} ".(isset($field['default']) ? "SET DEFAULT '{$field['default']}'" : 'DROP DEFAULT');
					}
					if (!empty($field['comment']) && $col['comment'] !== $field['comment']) {
						$this->pushQuery("COMMENT ON COLUMN {$name}.{$field['name']} IS ".$this->SQL->quote($field['comment']));
					}
				} else
				if (!empty($field['oldname']) && isset($cols[$field['oldname']])) {
					$q[] = "RENAME COLUMN {$field['oldname']} TO {$n}";
				} else {
					$m = 'ADD COLUMN '.$this->get_field_specification($field);
				}
			}

			# indices and foreign keys
			$ckeys = $this->SQL->listIndices($name);
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
				} else if ('FULLTEXT' === $key['type']) {
					if (!isset($ckeys[$n])) {
						$keys[] = "CREATE INDEX {$n} ON {$name} USING gin({$key['columns'][0]['name']})";
//						$q[] = "ADD INDEX {$n} gin({$key['columns'][0]['name']})";
					}
				} else {
					$fields = array();
					foreach ($key['columns'] as $field) {
						$fn = $field['name'];
						$fields[$fn] = $fn; //. (empty($field['length']) ? '' : "({$field['length']})");
					}
					$primary = 'PRIMARY' === $n;
					$ADD = false;
					if (isset($ckeys[$n])) {
						$ADD = !$primary && $ckeys[$n]['type'] !== $key['type'];
						if (!$ADD) {
							foreach ($fields as $k => $v) {
								if (!isset($ckeys[$n]['columns'][$k]) || $ckeys[$n]['columns'][$k] !== $v) {
									$ADD = true;
									break;
								}
							}
						}
						if ($ADD) {
							$q[] = ($primary ? "DROP PRIMARY KEY" : "DROP INDEX {$n}");
						}
					}
					if (!isset($ckeys[$n]) || $ADD) {
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
			$this->triggerAfterQuery($q, 1 + ((is_countable($table['keys']) ? count($table['keys']) : 0) - count($keys)));

			foreach ($keys as $query) {
				$this->SQL->query($query);
				$this->triggerAfterQuery($query);
			}
		}

		// Create table
		else {
			$fields = $keys = $comments = array();

			if (!empty($table['comment'])) {
				$comments[] = "COMMENT ON TABLE {$name} IS ".$this->SQL->quote($table['comment']);
			}

			foreach ($table['columns'] as $field) {
				if (empty($field['type'])) {
					$this->aq_event->index += (1 + (is_countable($table['keys']) ? count($table['keys']) : 0));
					return;
				}
				$fields[] = $this->get_field_specification($field);
				if (!empty($field['comment'])) {
					$comments[] = "COMMENT ON COLUMN {$name}.{$field['name']} IS ".$this->SQL->quote($field['comment']);
				}
			}

			foreach ($table['keys'] as $key) {
				if (!$this->validPlatform($key)) {
					continue;
				}
				$key_fields = array();
				foreach ($key['columns'] as $field) {
					$n = $field['name'];
					$key_fields[$n] = $n; //. (empty($field['length']) ? '' : "({$field['length']})");
				}
				$key_fields = implode(',',$key_fields);
				if ('PRIMARY' === $key['name']) {
					$fields[] = "PRIMARY KEY ({$key_fields})";
				} else if ('FOREIGN' === $key['type']) {
					$fields[] = $this->get_foreign_key($key);
				} else if ('FULLTEXT' === $key['type']) {
					$keys[] = "CREATE INDEX {$key['name']} ON {$name} USING gin({$key['columns'][0]['name']})";
				} else {
					$keys[] = "CREATE {$key['type']} INDEX {$key['name']} ON {$name} ({$key_fields})";
				}
			}

			$this->SQL->query("CREATE TABLE {$name} (".implode(', ',$fields).")");
			foreach ($comments as $query) {
				$this->SQL->query($query);
			}
			$this->triggerAfterQuery("CREATE TABLE {$name}", 1 + ((is_countable($table['keys']) ? count($table['keys']) : 0) - count($keys)));

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
				$this->SQL->query("DROP TRIGGER IF EXISTS {$trigger['name']} ON {$table}");
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
		return $field['name'].' '.$this->get_type($field)
			. (empty($field['notnull']) ? '' : ' NOT NULL')
			. (isset($field['default']) ? " DEFAULT '{$field['default']}'" : '');
	}

	private function get_type($field)
	{
		$t = $field['type'];
		if ('BLOB' === $t || 'BINARY' === $t || 'VARBINARY' === $t) {
			return 'BYTEA';
		}
		if ('SEARCH' === $t)    { return 'tsvector'; }
		if ('TINYINT' === $t)   { return 'SMALLINT'; }
		if ('MEDIUMINT' === $t) { return 'INTEGER'; }
		return $t . (empty($field['length']) ? '': "({$field['length']})");
	}

}
