<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Result;

class Success
{
	protected
		$user;

	public function __construct($user)
	{
		$this->user = $user;
	}

	public function __get($key)
	{
		if ('user' === $key) return $this->user;
		return null;
	}
}
