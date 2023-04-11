<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL\XML;

abstract class Importer extends \XMLReader
{
	use \Poodle\Events;

	public
		$errors;

	protected
		$SQL,
		$db_platform,
		$db_tables,
		$tbl_prefix,
		$counts = array();

	protected static
		$onDuplicateActions = array('ERROR','IGNORE','UPDATE');

	function __construct(\Poodle\SQL $SQL)
	{
		$this->SQL = $SQL;
		$this->tbl_prefix = $SQL->TBL->prefix;
		$this->db_platform = strtolower($SQL->engine);
	}

	public function syncSchemaFromFile($file, $old_version = 0)  { return $this->importData($file, $old_version); }
	public function syncSchemaFromString($str, $old_version = 0) { return $this->importData($str, $old_version, true); }

	// importDataFromXML
	// importTableData
	// importQueries
	public function importDataFromFile($file, $old_version = 0)  { return $this->importData($file, $old_version); }
	public function importDataFromString($str, $old_version = 0) { return $this->importData($str, $old_version, true); }
	protected function importData($file, $old_version, $file_is_data = false)
	{
		if (!$this->validate($file, $file_is_data)) {
			return false;
		}
		if (!$this->loadData($this, $file, $file_is_data)) {
			$this->setXMLErrors();
			$this->errors[] = array('message' => __CLASS__ . '::importData() failed to open');
			return false;
		}
		$this->db_tables = $this->SQL->listTables();
		$table = $record = $cell = $key = $trigger = $query = $view = null;
		$ci = 0;
		$this->aq_event = new \Poodle\Events\Event('afterquery');
		$this->aq_event->count = array_sum($this->counts);
		$this->aq_event->index = 0;
		try {
			$this->SQL->begin();
			while ($this->read()) {
				switch ($this->nodeType)
				{
				case self::ELEMENT:
					$data = '';
					if ($table) {
						if ($key) {
							if ('col' === $this->name) {
								$key['columns'][] = $this->getElementAttributes();
							}
						} else if ('col' === $this->name) {
							$col = $this->getElementAttributes();
							if (!empty($col['type'])) {
								$col['notnull'] = (empty($col['nullable']) || 'true' !== $col['nullable']);
								$col['binary']  = (!empty($col['binary']) && 'true' === $col['binary']);
								$col['default'] = isset($col['default']) ? $col['default'] : null;
								$col['comment'] = isset($col['comment']) ? $col['comment'] : null;
							}
							$table['columns'][] = $col;
						} else if ('key' === $this->name) {
							$key = $this->getElementAttributes();
							$key['type'] = empty($key['type']) ? '' : $key['type'];
							$key['columns'] = array();
						} else if ('trigger' === $this->name) {
							$trigger = $this->getElementAttributes();
						} else if ('tr' === $this->name) {
							$record = $this->getElementAttributes();
							$record['data'] = array();
							$ci = 0;
						} else if ($record && 'td' === $this->name) {
							$cell = $this->getElementAttributes();
							$cell['nil'] = isset($cell['nil']) && 'true' === $cell['nil'];
							if ($this->isEmptyElement) {
								$name = $table['columns'][$ci++]['name'];
								$record['data'][$name] = $this->prepareCellValue($cell, '');
								$cell = null;
							}
						}
					} else if ('table' === $this->name) {
						$attributes = $this->getElementAttributes();
						$table = $attributes;
						try {
							$table['isempty'] = !$this->SQL->count($table['name']);
						} catch (\Exception $e) {
							$table['isempty'] = true;
						}
						$table['onduplicate'] = isset($table['onduplicate']) ? (int)array_search($table['onduplicate'], self::$onDuplicateActions) : 0;
						$table['columns'] = $table['keys'] = $table['triggers'] = array();
					} else if ('query' === $this->name) {
						$query = $this->getElementAttributes();
					} else if ('view' === $this->name) {
						$view = $this->getElementAttributes();
					}
					break;

				case self::TEXT:
				case self::CDATA:
					if ($this->hasValue) {
						$data .= $this->value;
					}
					break;

				case self::END_ELEMENT:
					if ($table) {
						if ($key && 'key' === $this->name) {
							$table['keys'][] = $key;
							$key = null;
						} else if ($trigger && 'trigger' === $this->name) {
							if (empty($table['processed'])) {
								$this->syncTable($table);
								$table['processed'] = true;
							}
							$trigger['statement'] = preg_replace('#{([a-z0-9_]+)}#s', $this->tbl_prefix.'$1', $data);
							$this->syncTableTrigger($table, $trigger);
							$trigger = null;
						} else if ($cell && 'td' === $this->name) {
							$name = $table['columns'][$ci++]['name'];
							$record['data'][$name] = $this->prepareCellValue($cell, $data);
							$cell = null;
						} else if ('table' === $this->name) {
							if (empty($table['processed'])) {
								$this->syncTable($table);
							}
							$table = null;
						} else if ('tr' === $this->name) {
							if (empty($table['processed'])) {
								$this->syncTable($table);
								$table['processed'] = true;
							}
							if ($record) {
								$this->importDataRecord($table, $record);
								$record = null;
							}
						}
					} else if (null !== $query && 'query' === $this->name) {
						if ($this->checkVersion($query, $old_version) && $this->validPlatform($query)) {
							$this->SQL->query(preg_replace('#{([a-z0-9_]+)}#s', $this->tbl_prefix.'$1', $data));
							$this->triggerAfterQuery($data);
						} else {
							$this->triggerAfterQuery("SKIPPED: {$data}");
						}
						$query = null;
					} else if (null !== $view && 'view' === $this->name) {
						if ($data && $this->validPlatform($view)) {
							$data = preg_replace('#{([a-z0-9_]+)}#s', $this->tbl_prefix.'$1', $data);
							$this->SQL->query("CREATE OR REPLACE VIEW {$this->tbl_prefix}{$view['name']} AS {$data}");
							$this->triggerAfterQuery("CREATE OR REPLACE VIEW {$view['name']}");
						} else {
							$this->triggerAfterQuery("SKIPPED VIEW {$view['name']}");
						}
						$view = null;
					}
					$data = '';
					break;
				}
			}
			$this->close();
			$this->SQL->commit();
			return true;
		} catch (\Poodle\SQL\Exception $e) {
			$this->errors[] = array('message' => $e->getMessage(), 'query' => $e->getQuery());
			$this->SQL->rollback();
		}
		return false;
	}

	/**
	 * if function|key|procedure|query|trigger|view is platform specific
	 */
	protected function validPlatform(array $item)
	{
		return (empty($item['platform']) || $this->db_platform === strtolower($item['platform']));
	}

	protected function checkVersion(array $item, $old_version)
	{
		return (empty($item['version']) || ($old_version && version_compare($old_version, $item['version'], '<')));
	}

	public function validateFile($file)  { return $this->validate($file); }
	public function validateString($str) { return $this->validate($str, true); }
	protected function validate($file, $file_is_data = false)
	{
		$this->counts = array(
			'table' => 0,
				'key' => 0,
				'tr' => 0,
				'trigger' => 0,
			'function' => 0,
			'procedure' => 0,
			'view' => 0,
			'query' => 0,
		);
		$XML = new \XMLReader;
		if (!$this->loadData($XML, $file, $file_is_data)) {
			$this->setXMLErrors();
			$this->errors[] = array('message' => __CLASS__ . '::validate() failed to open');
			return false;
		}
		libxml_disable_entity_loader(false);
		$XML->setSchema(__DIR__ . '/schema.xsd');
		libxml_disable_entity_loader(true);
		while ($XML->read()) {
			// Bug libxml2 2.9.4 https://bugs.php.net/bug.php?id=73053
			if (LIBXML_VERSION != 20904 && !$XML->isValid()) {
				$this->setXMLErrors();
				$XML->close();
				return false;
			}
			// Set estimated queries count
			if (self::ELEMENT === $XML->nodeType) {
				$node = $XML->name;
				if (isset($this->counts[$node])) {
					++$this->counts[$node];
				}
			}
		}
		$XML->close();
		return true;
	}

	protected function loadData($xml, $file, $file_is_data = false)
	{
		libxml_use_internal_errors(true);
		if ($file_is_data) {
			libxml_disable_entity_loader(true);
			return $xml->xml($file, null, LIBXML_COMPACT);
		}
		libxml_disable_entity_loader(false);
		return $xml->open($file, null, LIBXML_COMPACT);
	}

	protected function setXMLErrors()
	{
		foreach (libxml_get_errors() as $error) {
			$this->errors[] = array('message' => "{$error->message} on line {$error->line}");
		}
		libxml_clear_errors();
	}

	protected function getElementAttributes()
	{
		$attributes = array();
		if ($this->hasAttributes) {
			while ($this->moveToNextAttribute()) {
				$attributes[$this->localName] = $this->value;
			}
			// bugs.php.net/bug.php?id=74629
			$this->moveToElement();
		}
		return $attributes;
	}

	protected function prepareCellValue($cell, $v)
	{
		if ($cell['nil']) {
			return 'NULL';
		}
		if (isset($cell['encoding'])) {
			if ('hex' == $cell['encoding']) {
				return $this->SQL->quoteBinary(hex2bin($v));
			}
			if ('raw' == $cell['encoding']) {
				return $v;
			}
		}
		if (isset($cell['israw']) && 'true' === $cell['israw']) {
			return $v;
		}
		return $this->SQL->quote($v);
	}

	protected $aq_event;
	protected function triggerAfterQuery($query, $inc = 1)
	{
		set_time_limit(\Poodle\PHP\ini::get('max_execution_time'));
		$this->aq_event->index += $inc;
		$this->aq_event->query = $query;
		$this->dispatchEvent($this->aq_event);
		$this->aq_event->query = null;
	}

	protected function importDataRecord($table, $record)
	{
		$update = !$table['isempty'];
		if (!empty($table['datamode'])
		 && (($update && 'ON-EMPTY' === $table['datamode']) || (!$update && 'ON-UPDATE' === $table['datamode'])))
		{
			$this->triggerAfterQuery("SKIPPED RECORD {$table['name']}");
			return;
		}
		$name = $this->tbl_prefix . $table['name'];
		$mode = isset($record['onduplicate']) ? (int)array_search($record['onduplicate'], self::$onDuplicateActions) : $table['onduplicate'];
		$duplicate = '';
		if ($update && 2 === $mode) {
			$duplicate = array();
			foreach ($record['data'] as $k => $v) {
				$duplicate[] = "{$k}={$v}";
			}
			$duplicate = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $duplicate);
		}
		try {
			$this->SQL->query("INSERT INTO {$name} (".implode(',',array_keys($record['data'])).") VALUES (".implode(',',$record['data']).") {$duplicate}");
		} catch (\Exception $e) {
			if (!$update || 1 !== $mode) {
				// TODO: UPDATE?
				throw $e;
			}
		}
		$this->triggerAfterQuery("INSERT INTO {$name}");
	}

	abstract protected function syncTable(array $table);
	abstract protected function syncTableTrigger(array $table, array $trigger);

}
