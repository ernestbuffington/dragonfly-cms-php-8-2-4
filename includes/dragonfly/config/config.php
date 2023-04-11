<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

class Config implements \ArrayAccess
{
	const
		TYPE_STR = 0,
		TYPE_INT = 1;

	protected
		$refresh_cache = false;

	# loads the global configuration system
	function __construct($__set_state=false)
	{
		if ($__set_state) { return; }
		$K = \Dragonfly::getKernel();
		// cfg_type
		$result = $K->SQL->query("SELECT cfg_name, cfg_field, cfg_value FROM {$K->SQL->TBL->config_custom} ORDER BY 1, 2");
		if (!$result) { return; }
		while ($row = $result->fetch_row()) {
			if (!isset($this->{$row[0]})) { $this->{$row[0]} = new Config_Section(); }
			$this->{$row[0]}->{$row[1]} = trim($row[2]);
		}
		$result->free();

		# Cache everything
		$K->CACHE->set(__CLASS__, $this);
		$this->init();
	}

	public static function load()
	{
		$c = \Dragonfly::getKernel()->CACHE->get(__CLASS__);
		return ($c ? $c : new static());
	}

	public static function removeFromCache()
	{
		\Dragonfly::getKernel()->CACHE->delete(__CLASS__);
	}

	protected function init()
	{
		$this->refresh_cache = false;
		if (!$this->global->timezone) { $this->set('global', 'timezone', 'UTC'); }
		if (empty($_COOKIE['PoodleTimezone'])) { date_default_timezone_set($this->global->timezone); }
		$this->set('poodle','identity_class', 'Dragonfly\\Identity');

		if (empty($this->auth_cookie->name)) {
			$this->set('auth_cookie','name', 'member');
		}
		if (empty($this->admin_cookie->name)) {
			$this->set('admin_cookie','name', 'admin');
		}
		if ($this->global->admingraphic < 1) {
			$this->set('global','admingraphic', 3); /* \Dragonfly\Page\Menu\Admin::GRAPH & \Dragonfly\Page\Menu\Admin::BLOCK */
		}
		if (empty($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = $this->server->domain;
		}
		// v9 compatibility
		if (preg_match('#^(localhost|127.0.0.1|192.168|10\.|172.(1[6-9]|2[0-9]|3[0-1])\.)#', $_SERVER['HTTP_HOST'])) {
			$this->cookie->domain = NULL;
		} else if ($this->cookie->server) {
			$this->cookie->domain = $this->server->domain = str_replace('www.', '', $_SERVER['SERVER_NAME']);
		}
		$this->server->domain = preg_replace('#^.+://#i', '', $this->server->domain);
		if (!extension_loaded('gd')) {
			$this->global->sec_code = 0;
		}
		$this->global->nukeurl = 'http://'.$this->server->domain.rtrim($this->server->path, '/');
	}

	public function onDestroy()
	{
		if ($this->refresh_cache) { self::removeFromCache(); }
		$this->refresh_cache = false;
	}

	# when class gets destroyed check for changes and delete cache when needed
	function __destruct()
	{
		$this->onDestroy();
	}

	# retrieve section key value
	public function get($section, $key)
	{
		return isset($this->$section->$key) ? $this->$section->$key : null;
	}

	# set value for section key
	public function set($section, $key, $value)
	{
		if (!$section || !$key) { return false; }
		if ($value instanceof \DateTime) { $value = $value->getTimestamp(); }
		if (!isset($this->$section->$key)) {
			# section key doesn't exist so we create it
			\Poodle\LOG::notice(\Poodle\LOG::CREATE, __CLASS__ . '->'.$section.'->'.$key.' did not exist.');
			$this->add($section, $key, $value);
			return;
		}
		// issue: 0=='value' results in true
		if ($value instanceof \DateTime) { $value = $value->getTimestamp(); }
		if (is_bool($value)) { $value = (int)$value; }
		if ((string)$this->$section->$key === (string)$value) { return; }
		$this->$section->$key = $value;
		\Dragonfly::getKernel()->SQL->TBL->config_custom->update(
			array('cfg_value'=>$value),
			array('cfg_name'=>$section, 'cfg_field'=>$key));
		$this->refresh_cache = true;
	}

	# create a new section key with value
	public function add($section, $key, $value)
	{
		if (isset($this->$section->$key)) { return; }
		if (!isset($this->$section)) { $this->$section = new Config_Section(); }
		if ($value instanceof \DateTime) { $value = $value->getTimestamp(); }
		if (is_bool($value)) { $value = (int)$value; }
		$this->$section->$key = $value;
		\Dragonfly::getKernel()->SQL->TBL->config_custom->insert(array(
			'cfg_name'  => $section,
			'cfg_field' => $key,
			'cfg_value' => $value
		));
		$this->refresh_cache = true;
	}

	# destroy section key or whole section
	public function delete($section, $key=false)
	{
		if (!is_object($this->$section)) { return; }
		if (!$key) {
			$this->$section = null;
		} else {
			if (!isset($this->$section->$key)) { return; }
			$this->$section->$key = null;
		}
		$SQL = \Dragonfly::getKernel()->SQL;
		$SQL->query('DELETE FROM '.$SQL->TBL->config_custom." WHERE cfg_name='{$section}'".($key ? " AND cfg_field='{$key}'" : ''));
		$this->refresh_cache = true;
	}

/*
	public function __sleep()
	{
		return get_object_vars($this);
	}
*/
	public function __wakeup()
	{
		$this->init();
	}

	# ArrayAccess
	public function offsetExists($k)  { return property_exists($this, $k); }
	public function offsetGet($k)     { return $this->$k; }
	public function offsetSet($k, $v) {}
	public function offsetUnset($k)   {}
}

class Config_Section extends \ArrayIterator
{
	function __get($k)     { return $this->offsetGet($k); }
	function __isset($k)   { return $this->offsetExists($k); }
	function __unset($k)   { $this->offsetUnset($k); }
	function __set($k, $v) { $this->offsetSet($k, $v); }
}
