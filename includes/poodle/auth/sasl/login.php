<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\SASL;

class Login extends \Poodle\Auth\SASL
{
	protected
		$password;

	public function authenticate($username, $password, $challenge = null)
	{
		if ($challenge && 'Username:' !== $this->decode($challenge)) {
			throw new \Exception("Invalid response: {$challenge}");
		}
		$this->password = $password;
		return $this->encode($username);
	}

	public function challenge($challenge)
	{
		if ($challenge && 'Password:' !== $this->decode($challenge)) {
			throw new \Exception("invalid response: {$challenge}");
		}
		return $this->encode($this->password);
	}

	public static function isSupported($param)
	{
		return true;
	}

}
