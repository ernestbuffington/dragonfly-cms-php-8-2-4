<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail\Send;

class None extends \Poodle\Mail\Send
{
	# Fake mail sender for servers without mail support.
	public function send() { return true; }
	public function close() {}
}
