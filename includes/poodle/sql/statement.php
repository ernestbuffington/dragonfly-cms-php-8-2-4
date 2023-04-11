<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL;

class Statement
{
	protected
		$query,
		$params = array(),

		$SQL;

	function __construct(\Poodle\SQL $SQL, $query)
	{
		$this->SQL = $SQL;
		$this->prepare($query);
	}

	public function bindParam($name, $value)
	{
		$key = ":{$name}";
		if (!array_key_exists($key, $this->params)) {
			throw new \Exception("Unknown parameter: {$name}");
		}
		$this->params[$key] = $value;
		return $this;
	}

	public function execute(array $params = array())
	{
		$sql = $this->query;
		if ($params) {
			$params = $this->SQL->prepareValues($params);
			$sql = preg_replace_callback('/\\$([1-9][0-9]*)/', function($m) use ($params) {return $params[$m[1]-1];}, $sql);
		}
		if ($this->params) {
			$sql = strtr($sql, $this->SQL->prepareValues($this->params));
		}
		return $this->SQL->query($sql);
	}

	public function prepare($query)
	{
		$prefix = $this->SQL->TBL->prefix;
		$this->query = preg_replace_callback('/{([a-z0-9_]+)}/', function($m) use ($prefix) {return $prefix . $m[1];}, $query);
		if (preg_match_all('/(:[0-9a-zA-Z_]+)/', $query, $m)) {
			foreach ($m[1] as $param) {
				$this->params[$param] = null;
			}
		}
		return $this;
	}

	public function reset()
	{
		$this->params = array_fill_keys(array_keys($this->params), null);
	}

}
