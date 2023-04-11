<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Cache\Adapter;

class File extends \Poodle\Cache implements \Poodle\Cache\Interfaces\Adapter
{

	const
		INFO_NAME = 'File system',
		INFO_DESC = 'File system based caching',
		INFO_URL  = '';

	protected
		$path,
		$writable = false;

	function __construct(array $config)
	{
		if (empty($config['path'])) {
			throw new \Exception(__CLASS__.' missing path');
		}
		if (WINDOWS_OS && !empty($config['host'])) {
			$config['path'] = $config['host'] . ':' . $config['path'];
		}
		if (is_dir($config['path'])) {
			$this->path = rtrim($config['path'],'/').'/';
			if ($this->writable = is_writable($config['path'])) {
				$filename = $this->path . 'CACHEDIR.TAG';
				if (!is_file($filename)) {
					file_put_contents($filename, 'Signature: 8a477f597d28d172789f06886806bc55
# This file is a cache directory tag created by Poodle WCMS.
# For information about cache directory tags, see:
#	http://www.brynosaurus.com/cachedir/');
				}
			}
		}
	}

	public function clear()
	{
		if ($this->path) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($iterator as $path) {
				if ($path->isDir()) {
					rmdir($path);
				} else if (0 !== strpos($path->getBasename(),'.')) {
					unlink($path);
				}
			}
			clearstatcache();
		}
	}

	public function delete($key)
	{
		if (!is_string($key)) {
			throw new \InvalidArgumentException('Cache->delete(): $key is of invalid type '.gettype($key));
		}
		if ($file = $this->findFile($key)) {
			return unlink($file);
		}
		return false;
	}

	public function exists($keys)
	{
		if (is_string($keys)) {
			return !!$this->findFile($keys);
		}
		if (is_array($keys)) {
			$ret = array();
			foreach ($keys as $i => $key) {
				if (is_string($key)) {
					if ($this->findFile($keys)) {
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
			$file = $this->findFile($keys);
			return $file ? unserialize(file_get_contents($file)) : false;
		}
		if (is_array($keys)) {
			$ret = array();
			foreach ($keys as $i => $key) {
				if (is_string($key)) {
					if ($file = $this->findFile($key)) {
						$ret[$key] = unserialize(file_get_contents($file));
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
		$ret = array();
		if ($this->path) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS)
			);
			foreach ($iterator as $path) {
				if (!$path->isDir()) {
					$ret[] = str_replace($this->path,'',$path->__toString());
				}
			}
		}
		natcasesort($ret);
		return $ret;
	}

	public function mtime($key)
	{
		if (!is_string($key)) {
			throw new \InvalidArgumentException('Cache->mtime(): $key is of invalid type '.gettype($key));
		}
		if ($file = $this->findFile($key)) {
			return filemtime($file);
		}
		return false;
	}

	public function set($key, $var, $ttl=0)
	{
		if (!is_string($key)) {
			throw new \InvalidArgumentException('Cache->set(): $key is of invalid type '.gettype($key));
		}
		if ($this->writable) {
			$key = static::fixKey($key);
			$filename = $this->path . $key;

			$ttl = (int)$ttl;
			if ($ttl) { $filename .= '#'.(time()+$ttl); }

			$dir = dirname($filename);
			if ((!strpos($key,'/') || is_dir($dir) || mkdir($dir,0777,true)) && is_writable($dir)) {
				return (false !== file_put_contents($filename, serialize($var), LOCK_EX));
			}
		}
		return false;
	}

	protected function findFile($key)
	{
		$filename = $this->path . static::fixKey($key);
		if (is_file($filename) && is_writable($filename)) { return $filename; }
		if (is_writable(dirname($filename)) && $files = glob($filename.'#*')) {
			foreach ($files as $file) {
				if (preg_match('/#([0-9]+)$/D',$file,$m)) {
					if ($m[1] < time()) {
						unlink($file);
					} else {
						return $file;
					}
				}
			}
		}
		return false;
	}

	public function isWritable()
	{
		return $this->writable;
	}

}
