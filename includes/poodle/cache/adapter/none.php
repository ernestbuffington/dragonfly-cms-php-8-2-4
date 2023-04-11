<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Cache\Adapter;

class None extends \Poodle\Cache implements \Poodle\Cache\Interfaces\Adapter
{

	const
		INFO_NAME = 'None',
		INFO_DESC = 'No caching',
		INFO_URL  = '';

	function __construct(array $config)
	{
	}

	public function clear()
	{
	}

	public function delete($key)
	{
		return true;
	}

	public function exists($keys)
	{
		return false;
	}

	public function get($keys)
	{
		return false;
	}

	public function listAll()
	{
		return array();
	}

	public function mtime($key)
	{
		return false;
	}

	public function set($key, $var, $ttl=0)
	{
		return false;
	}

	public function isWritable()
	{
		return false;
	}

}
