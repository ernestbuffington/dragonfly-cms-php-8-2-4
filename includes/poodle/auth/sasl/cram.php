<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\SASL;

class Cram extends \Poodle\Auth\SASL
{

	protected
		$authcid,
		$password;

	function __construct($algo)
	{
		$algo = str_replace('-', '', strtolower($algo));
		if (!in_array($algo, hash_algos())) {
			throw new \Exception("Unsupported SASL SCRAM algorithm: {$algo}");
		}
		$this->algo = $algo;
	}

	public function authenticate($authcid, $password, $challenge = null)
	{
		return $this->encode($authcid . ' ' . hash_hmac($this->algo, $this->decode($challenge), $password));
	}

	public static function isSupported($param)
	{
		return in_array(str_replace('-', '', strtolower($param)), hash_algos());
	}

}
