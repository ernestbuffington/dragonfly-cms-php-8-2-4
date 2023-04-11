<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Events;

class Event
{
	public
		$target,
		$type;

	function __construct($type)
	{
		$this->type = $type;
	}
}
