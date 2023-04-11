<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Result;

class Error extends \Exception /* extends \ErrorException */
{
	public function __construct($number = 0, $message = '', \Exception $previous = NULL)
	{
		parent::__construct($message, $number, $previous);
	}
}
