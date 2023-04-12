<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Cache\Adapter;

class Memcached extends \Poodle\Cache implements \Poodle\Cache\Interfaces\Adapter
{

	public const
		INFO_NAME = 'Memcached',
		INFO_DESC = 'A high-performance, distributed memory object caching system',
		INFO_URL  = 'http://php.net/memcached';

	protected
		$memcache,
		$prefix;

	function __construct(array $config)
	{
		if (!class_exists('Memcached',false)) {
			throw new \Exception('Memcached not loaded');
		}

		$this->memcache = new \Memcached();

		$connected = false;

		if (!empty($config['path'])) {
			$connected |= $this->memcache->addServer('unix://'.$config['path'], 0);
		} else if (!empty($config['host'])) {
			$connected |= $this->memcache->addServer($config['host'], empty($config['port']) ? 11211 : $config['port']);
		}

		if (!empty($config['servers']) && is_array($config['servers'])) {
			foreach ($config['servers'] as $server) {
				$server = parse_url($server);
				if ($server) {
					if (!empty($server['path'])) {
						$connected |= $this->memcache->addServer('unix://'.$server['path'], 0);
					} else if (!empty($config['host'])) {
						$connected |= $this->memcache->addServer($server['host'], empty($server['port']) ? 11211 : $server['port']);
					}
				}
			}
		}

		if (!$connected) {
			throw new \Exception('Memcached connection failed');
		}

		if (empty($config['options'])) {
			$config['options'] = array();
		}
		if (!isset($config['options'][\Memcached::OPT_PREFIX_KEY])) {
			$config['options'][\Memcached::OPT_PREFIX_KEY] = $_SERVER['HTTP_HOST'].\Poodle::$URI_BASE.'/cache/';
		}
		$this->memcache->setOptions($config['options']);
	}

	function __destruct()
	{
		if ($this->memcache) {
			$this->memcache->quit();
			$this->memcache = null;
		}
	}

	public function clear()
	{
		$this->memcache->flush();
	}

	public function delete($key)
	{
		if (!is_string($key)) {
			throw new \InvalidArgumentException('Cache->delete(): $key is of invalid type '.gettype($key));
		}
		return $this->memcache->delete(static::fixKey($key));
	}

	public function exists($keys)
	{
		if (is_string($keys)) {
			return false !== $this->memcache->get(static::fixKey($keys));
		}
		if (is_array($keys)) {
			$ret = array();
			foreach ($keys as $i => $key) {
				if (is_string($key)) {
					if (false !== $this->memcache->get(static::fixKey($key))) {
						$ret[$key] = true;
					}
				} else {
					throw new \InvalidArgumentException('Cache->exists(): $keys['.$i.'] is of invalid type '.gettype($key));
				}
			}
			return $ret;
		}
		throw new \InvalidArgumentException('Cache->exists(): $keys is of invalid type '.gettype($keys));
	}

	public function get($keys)
	{
		if (is_string($keys)) {
			return $this->getVarData($keys);
		}
		if (is_array($keys)) {
			$ret = array();
			foreach ($keys as $i => $key) {
				if (is_string($key)) {
					$var = $this->getVarData($key);
					if (false !== $var) {
						$ret[$key] = $var;
					}
				} else {
					throw new \InvalidArgumentException('Cache->get(): $keys['.$i.'] is of invalid type '.gettype($key));
				}
			}
			return $ret;
		}
		throw new \InvalidArgumentException('Cache->get(): $keys is of invalid type '.gettype($keys));
	}

	public function listAll()
	{
		return $this->memcache->getAllKeys();
	}

	public function mtime($key)
	{
		if (!is_string($key)) {
			throw new \InvalidArgumentException('Cache->mtime(): $key is of invalid type '.gettype($key));
		}
		$var = $this->memcache->get(static::fixKey($key));
		return (is_array($var) && isset($var['t'])) ? $var['t'] : false;
	}

	public function set($key, $var, $ttl=0)
	{
		$key = static::fixKey($key);
		$var = array('d'=>serialize($var),'t'=>time());
		if ($ttl) { $ttl += time(); }
		return $this->memcache->replace($key, $var, $ttl)
		    || $this->memcache->add($key, $var, $ttl);
	}

	public function isWritable()
	{
		return true;
	}

	protected function getVarData($key)
	{
		if ($var = $this->memcache->get(static::fixKey($key))) {
			if (is_array($var) && isset($var['d'], $var['t'])) {
				return unserialize($var['d']);
			}
		}
		return false;
	}

}
