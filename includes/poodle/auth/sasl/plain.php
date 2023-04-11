<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\SASL;

class Plain extends \Poodle\Auth\SASL
{

	public function authenticate($username, $password, $authzid = null)
	{
		return $this->encode("{$authzid}\x00{$username}\x00{$password}");
	}

	public static function isSupported($param)
	{
		return true;
	}

}
