<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth;

class Credentials
{
	protected
		$identity_id, // Integer or SASL authzid
		$claimed_id,  // String  or SASL authcid
		$password,
		$algo,
		$info,

		$hash_password = true,
		$hash_claimed_id = true;

	function __construct($identity, $claimed_id, $password = null)
	{
		if ($identity instanceof \Poodle\Identity) {
			$identity = $identity->id;
		}
		$this->identity_id = $identity;
		$this->claimed_id  = $claimed_id;
		$this->password    = $password;
	}

	function __get($k)
	{
		if ('info' === $k && !$this->info && $info = parse_url($this->claimed_id)) {
			return empty($info['host']) ? null : $info['host'];
		}
		if (property_exists($this, $k)) {
			return $this->$k;
		}
	}

	function __set($k, $v)
	{
		if (property_exists($this, $k)) {
			if ($v && !$this->$k && ('hash_password' === $k || 'hash_claimed_id' === $k)) {
				throw new \Exception(substr($k,5) . ' already hashed or not allowed');
			}
			$this->$k = $v;
		}
	}

	public function hashClaimedID()
	{
		if ($this->hash_claimed_id && $this->claimed_id) {
			$this->claimed_id = \Poodle\Auth::secureClaimedID($this->claimed_id);
			$this->hash_claimed_id = false;
		}
	}

	public function hashPassword()
	{
		if ($this->hash_password && $this->password) {
			$this->password = \Poodle\Auth::hashPassword($this->password, $this->algo);
			$this->hash_password = false;
		}
	}

}
