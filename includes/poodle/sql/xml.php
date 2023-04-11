<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL;

class XML
{

	protected
		$SQL,
		$EXPORTER,
		$IMPORTER;

	function __construct(\Poodle\SQL $SQL)
	{
		$this->SQL = $SQL;
	}

	function __call($method, $args)
	{
		$class = null;
		switch ($method)
		{
		case 'syncSchemaFromFile':
		case 'syncSchemaFromString':
		case 'validateFile':
		case 'validateString':
			$class = $this->getImporter();
			break;

		case 'exportData':
		case 'exportSchema':
		case 'exportTableData':
		case 'getDocFoot':
		case 'getDocHead':
		case 'getTableDataXML':
		case 'getFunctionXML':
		case 'getProcedureXML':
		case 'getTableXML':
		case 'getViewXML':
			$class = $this->getExporter();
			break;
		}
		if ($class) {
			// This is faster then call_user_func_array()
			switch (count($args))
			{
			case 0: $ret = $class->{$method}(); break;
			case 1: $ret = $class->{$method}($args[0]); break;
			case 2: $ret = $class->{$method}($args[0], $args[1]); break;
			case 3: $ret = $class->{$method}($args[0], $args[1], $args[2]); break;
			default: $ret = call_user_func_array(array($class, $method), $args);
			}
			$this->errors = $class->errors;
			return $ret;
		}
	}

	public function getImporter()
	{
		if (!$this->IMPORTER) {
			$class = 'Poodle\\SQL\\XML\\'.$this->SQL->engine;
			$this->IMPORTER = new $class($this->SQL);
		}
		return $this->IMPORTER;
	}

	public function getExporter()
	{
		if (!$this->EXPORTER) {
			$this->EXPORTER = new \Poodle\SQL\XML\Exporter($this->SQL);
		}
		return $this->EXPORTER;
	}

}
