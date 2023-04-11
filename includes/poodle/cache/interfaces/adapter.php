<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Cache\Interfaces;

interface Adapter
{
	function __construct(array $config);

	/**
	 * Clears the cache.
	 */
	public function clear();

	/**
	 * Removes a stored variable from the cache.
	 */
	public function delete($key);

	/**
	 * Checks if one ore more keys exist.
	 * $keys: A string, or an array of strings, that contain keys.
	 */
	public function exists($keys);

	/**
	 * Fetch a stored variable from the cache.
	 */
	public function get($keys);

	/**
	 * Return list of all cached objects.
	 */
	public function listAll();

	/**
	 * Return last modified time of key or false on failure
	 */
	public function mtime($key);

	/**
	 * Cache a variable in the data store
	 * $ttl: Time To Live; store var in the cache for ttl seconds.
	 * After the ttl has passed, the stored variable will be expunged from
	 * the cache (on the next request). If no ttl is supplied (or if the
	 * ttl is 0), the value will persist until it is removed from the cache
	 * manually, or otherwise fails to exist in the cache (clear, restart, etc.).
	 */
	public function set($key, $var, $ttl=0);

	public function isWritable();
}
