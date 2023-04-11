<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL\XML;

class Exporter
{
	public
		$errors;

	protected
		$SQL,
		$prefix;

	protected static
		$onDuplicateActions = array('ERROR','IGNORE','UPDATE'),
		$dataModes = array('ON-EMPTY','ON-UPDATE','IDENTICAL','UNIQUE');

	function __construct(\Poodle\SQL $SQL)
	{
		$this->SQL = $SQL;
		$this->prefix = $this->SQL->TBL->prefix;
	}

	public function getDocFoot() { return "\n\n</database>"; }
	public function getDocHead()
	{
		return '<?xml version="1.0"?>'."\n"
		.'<database version="1.0" name="'.$this->SQL->database.'" charset="'.$this->SQL->get_charset().'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
	}

	public function getTableDataXML($name, $table, array $config=array())
	{
//		\Poodle\PHP\INI::set('memory_limit', '16M');
		$mem_limit = \Poodle\PHP\INI::getInt('memory_limit');

		// Sort on max first 6 columns, else might have error: Out of sort memory; increase server sort buffer size
//		$result = $this->SQL->query("SELECT * FROM {$table} ORDER BY 1");
		$result = $this->SQL->query("SELECT * FROM {$table}");
		$data   = '';
		if ($result->num_rows) {
			$data .= "\n\n\t".'<table name="'.$name.'"';
			if (isset($config['onduplicate']) && in_array($config['onduplicate'], self::$onDuplicateActions)) {
				$data .= ' onduplicate="'.$config['onduplicate'].'"';
			}
			if (isset($config['datamode']) && in_array($config['datamode'], self::$dataModes)) {
				$data .= ' datamode="'.$config['datamode'].'"';
			}
			$data .= '>';

			$r = $result->fetch_assoc();

			# columns
			$data .= "\n\t\t<col name=\"".implode("\"/>\n\t\t<col name=\"",array_keys($r))."\"/>";
			//$data .= "\n\t\t<col name=\"{$name}\"/>";

			do {
				foreach ($r as $k => $v) {
					if (is_null($v)) {
						$r[$k] = '<td xsi:nil="true"/>';
					} else if ($v && preg_match('#[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]#', $v)) {
						$r[$k] = '<td encoding="hex">'.bin2hex($v).'</td>';
					} else {
//						if (false !== strpbrk($v,'&<>')) { slower ???
						if (false !== strpos($v,'&') || false !== strpos($v,'<') || false !== strpos($v,'>')) {
							if (false !== strpos($v,']]>')) {
								$v = htmlspecialchars($v, ENT_NOQUOTES);
							} else {
								$v = '<![CDATA['.$v.']]>';
							}
						}
						$r[$k] = "<td>{$v}</td>";
					}
				}
				$data .= "\n\t\t<tr>" . implode('',$r) . "</tr>";
				// Push stream intermediate to reduce memory usage
				if (!empty($config['stream'])
				 && (strlen($data) > 65536 || $mem_limit < memory_get_usage(true)+1024000)
				 && false !== fwrite($config['stream'], $data))
				{
					$data = '';
				}
			} while ($r = $result->fetch_row());

			return $data."\n\t".'</table>';
		}
	}

	public function exportData($stream=null, array $config=array())
	{
//		$fp = $stream ? $stream : fopen('php://memory:16777216','rw');

		$re = '#^'.$this->prefix.'(.+)$#D';
		$data = $this->getDocHead();
		if ($stream && false !== fwrite($stream, $data)) { $data = ''; }

		$config['stream'] = $stream;
		if (!isset($config['skip_tables'])) {
			$config['skip_tables'] = array(
				'auth_providers_assoc',
				'auth_providers_endpoints',
				'auth_providers_nonce',
				'log',
				'sessions',
			);
		}

		foreach ($this->SQL->listTables() as $table) {
			if (!in_array(substr($table,strlen($this->prefix)), $config['skip_tables'])
			 && preg_match($re, $table, $t)
			) {
				$data .= $this->getTableDataXML($t[1], $table, $config);
				if ($stream && $data && (false !== fwrite($stream, $data))) { $data = ''; }
			}
		}

		$data .= $this->getDocFoot();
		if ($stream && (false !== fwrite($stream, $data))) { return true; }
		return $data;
	}

	public function getFunctionXML($name, $func)
	{
		$func = $this->SQL->getFunction($func);
		$data = "\t".'<function name="'.$name.'" returns="'.$func['returns'].'">';
		foreach ($func['parameters'] as $param) {
			$data .= "\n\t\t".'<param name="'.$param['name'].'"'
				.($param['dir']?' direction="'.$param['dir'].'"':'')
				.($param['type']?' type="'.$param['type'].'"':'')
				.($param['length']?' length="'.$param['length'].'"':'').'/>';
		}
		return $data
			."\n\t\t".'<body><![CDATA['.$func['definition'].']]></body>'
			."\n\t".'</function>';
	}

	public function getProcedureXML($name, $proc)
	{
		$proc = $this->SQL->getProcedure($proc);
		$data = "\t".'<procedure name="'.$name.'">';
		foreach ($proc['parameters'] as $param) {
			$data .= "\n\t\t".'<param name="'.$param['name'].'"'
				.($param['dir']?' direction="'.$param['dir'].'"':'')
				.($param['type']?' type="'.$param['type'].'"':'')
				.($param['length']?' length="'.$param['length'].'"':'').'/>';
		}
		return $data
			."\n\t\t".'<body><![CDATA['.$proc['definition'].']]></body>'
			."\n\t".'</procedure>';
	}

	public function getTableXML($name, $table, array $info=array())
	{
		if (!$info) $info = $this->SQL->getTableInfo($table);

		$data = "\t".'<table name="'.$name.'"';
		if ($info['comment']) { $data .= ' comment="'.htmlspecialchars($info['comment']).'"'; }
		if ($info['engine'] ) { $data .= ' engine="'.htmlspecialchars($info['engine']).'"'; }
		$data .= '>';

		# columns
		foreach ($this->SQL->listColumns($table) as $name => $col) {
			preg_match('#([A-Z]+)(?:\s*\(([^\(\)]+)\))?(\s+BINARY)?#i',$col['type'], $m);
			$attr = array('type="'.$m[1].'"');
			if (!empty($m[2])) $attr[] = 'length="'.$m[2].'"';
			if (!empty($m[3])) $attr[] = 'binary="true"';
			if (!$col['notnull']) $attr[] = 'nullable="true"';
			if (isset($col['default'])) $attr[] = 'default="'.$col['default'].'"';
			if ($col['comment']) $attr[] = 'comment="'.htmlspecialchars($col['comment']).'"';
			$data .= "\n\t\t".'<col name="'.$name.'" '.implode(' ',$attr).'/>';
		}

		# indices
		$indices = $this->SQL->listIndices($table);
		ksort($indices);
		foreach ($indices as $name => $key) {
			$data .= "\n\t\t".'<key name="'.$name.'"'.($key['type']?" type=\"{$key['type']}\"":'').'>';
			foreach ($key['columns'] as $name => $v)
				$data .= "\n\t\t\t".'<col name="'.$name.'"'.(strlen($name)!=strlen($v)?' length="'.substr($v,strlen($name)+1,-1).'"':'').'/>';
			$data .= "\n\t\t".'</key>';
		}

		# foreign keys
		$re = '#^'.$this->prefix.'(.+)$#D';
		foreach ($this->SQL->listForeignKeys($table) as $name => $key) {
			$name = preg_replace($re, '$1', $name);
			$key['references'] = preg_replace($re, '$1', $key['references']);
			$data .= "\n\t\t<key name=\"{$name}\" type=\"FOREIGN\" references=\"{$key['references']}\" ondelete=\"{$key['ondelete']}\" onupdate=\"{$key['onupdate']}\">";
			foreach ($key['columns'] as $name => $v) {
				$data .= "\n\t\t\t<col name=\"{$name}\"";
				if ($name !== $v) { $data .= " refcolumn=\"{$v}\""; }
				$data .= "/>";
			}
			$data .= "\n\t\t</key>";
		}

		# triggers
		foreach ($this->SQL->listTriggers($table) as $name => $trigger) {
			$data .= "\n\t\t".'<trigger name="'.$name.'" timing="'.$trigger['timing'].'" event="'.$trigger['event'].'"><![CDATA['.$trigger['statement'].']]></trigger>';
		}

		return $data."\n\t</table>";
	}

	public function getViewXML($name, $view)
	{
		$view = $this->SQL->getView($view);
		$prfx = $this->SQL->TBL->prefix;
		if ($prfx) { $view['definition'] = preg_replace("#([^a-z_]){$prfx}([a-z0-9_]+)#si", '$1{$2}', $view['definition']); }
		$view['definition'] = preg_replace('# (from|left join) #',"\n\$1 ", preg_replace('#(^select |,)#',"\$1\n\t",$view['definition']));
		return "\t".'<view name="'.$name.'"><![CDATA['.$view['definition'].']]></view>';
	}

	public function exportSchema($stream=null)
	{
		$re = '#^'.$this->prefix.'(.+)$#D';
		$data = $this->getDocHead();

		foreach ($this->SQL->listFunctions() as $name) {
			if (!preg_match($re, $name, $t)) continue;
			$data .= "\n\n".$this->getFunctionXML($t[1], $name);
			if ($stream && (false !== fwrite($stream, $data))) { $data = ''; }
		}

		foreach ($this->SQL->listProcedures() as $name) {
			if (!preg_match($re, $name, $t)) continue;
			$data .= "\n\n".$this->getProcedureXML($t[1], $name);
			if ($stream && (false !== fwrite($stream, $data))) { $data = ''; }
		}

		foreach ($this->SQL->listTables(true) as $info) {
			if (!preg_match($re, $info['name'], $t)) continue;
			$data .= "\n\n".$this->getTableXML($t[1], $info['name'], $info);
			if ($stream && (false !== fwrite($stream, $data))) { $data = ''; }
		}

		foreach ($this->SQL->listViews() as $view) {
			if (!preg_match($re, $view, $t)) continue;
			$data .= "\n\n".$this->getViewXML($t[1], $view);
			if ($stream && (false !== fwrite($stream, $data))) { $data = ''; }
		}

		$data .= $this->getDocFoot();
		if ($stream && (false !== fwrite($stream, $data))) { return true; }
		return $data;
	}

	public function exportTableData($table, array $config=array())
	{
		$name = preg_match('#^'.$this->prefix.'(.+)$#D', $table, $t) ? $t[1] : $table;
		if (!empty($config['stream'])) {
			return false !== fwrite($config['stream'], $this->getDocHead())
			    && false !== fwrite($config['stream'], $this->getTableDataXML($name, $table, $config))
			    && false !== fwrite($config['stream'], $this->getDocFoot());
		}
		return $this->getDocHead()
			. $this->getTableDataXML($name, $table, $config)
			. $this->getDocFoot();
	}
}
