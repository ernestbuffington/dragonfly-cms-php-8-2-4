<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Result;

class Redirect
{
	protected $uri;

	public function __construct($uri)
	{
		$this->uri = $uri;
	}

	public function __get($key)
	{
		if ('uri' === $key) return $this->uri;
		return null;
	}
}
