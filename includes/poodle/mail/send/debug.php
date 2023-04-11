<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail\Send;

class Debug extends \Poodle\Mail\Send
{

	# Sends mail using the PHP mail() function.
	public function send()
	{
		$this->prepare($header, $body, self::HEADER_ADD_TO | self::HEADER_ADD_BCC);
		echo htmlentities($header).'<br/><br/>';
		echo htmlentities($body);
		return true;
	}

	public function close() {}

}
