<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth;

abstract class SASL
{
	public
		$base64 = false;

	abstract public function authenticate($authcid, $password, $authzid = null);

	public function challenge($challenge)
	{
		return null;
	}

	public function verify($data)
	{
		return null;
	}

	final public static function factory($type)
	{
		if (preg_match('/^([A-Z]+)(-.+)?$/Di', $type, $m)) {
			$class = __CLASS__ . "\\{$m[1]}";
			if (class_exists($class)) {
				return new $class(isset($m[2]) ? $m[2] : null);
			}
		}
		throw new \Exception("Unsupported SASL mechanism type: {$type}");
	}

	public static function isSupported($type)
	{
		if (preg_match('/^([A-Z]+)(-.+)?$/Di', $type, $m)) {
			$class = __CLASS__ . "\\{$m[1]}";
			return class_exists($class) && $class::isSupported(isset($m[2]) ? $m[2] : null);
		}
		return false;
	}

	final protected function decode($data)
	{
		return $this->base64 ? base64_decode($data) : $data;
	}

	final protected function encode($data)
	{
		return $this->base64 ? base64_encode($data) : $data;
	}

}
