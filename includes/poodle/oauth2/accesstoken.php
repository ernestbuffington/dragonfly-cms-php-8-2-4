<?php

namespace Poodle\OAuth2;

use \Poodle\JSON;

class AccessToken implements \ArrayAccess, \JsonSerializable
{
	protected $data = array();

	public function __construct($data)
	{
		if (!is_array($data)) {
			if (is_object($data)) {
				$data = JSON::encode($data);
			}
			$data = JSON::decode($data, JSON_OBJECT_AS_ARRAY);
			if (empty($data)) {
				throw new \InvalidArgumentException("Invalid data");
			}
		}

		if (empty($data['access_token']) || !is_string($data['access_token'])) {
			throw new \UnexpectedValueException('Missing required option "access_token"');
		}

		if (isset($data['expires_in'])) {
			if (!is_numeric($data['expires_in'])) {
				throw new \UnexpectedValueException('expires_in value must be numeric');
			}
			$data['expires'] = $data['expires_in'] ? time() + $data['expires_in'] : 0;
			unset($data['expires_in']);
		} else if (empty($data['expires'])) {
			$data['expires'] = 0;
		} else if ($data['expires'] < 1349102012) {
			$data['expires'] += time();
		}

		$this->data = $data;
	}

	public function __get($key)
	{
		if (!isset($this->data[$key])) {
			$key .= '_token';
		}
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function __isset($key)
	{
		return isset($this->data[$key]);
	}

	public function __set($key, $v) {}

	public function __toString()
	{
		return $this->data['access_token'];
	}

	public function hasExpired($discrepancy = 10)
	{
		return $this->data['expires'] < (time() + $discrepancy);
	}

	public function offsetExists($key) { return $this->__isset($key); }
	public function offsetGet($key)    { return $this->__get($key); }
	public function offsetSet($k, $v)  {}
	public function offsetUnset($k)    {}

	public function jsonSerialize()
	{
		return $this->data;
	}
}
