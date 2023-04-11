<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth;

abstract class Provider2fa extends Provider
{
	public function createForIdentity(\Poodle\Identity $identity)
	{
	}

	public static function getQRCode($name, $secret, $issuer = '')
	{
	}
}
