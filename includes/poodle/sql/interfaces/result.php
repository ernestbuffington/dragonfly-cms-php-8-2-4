<?php
/*	Dragonfly™ CMS, Copyright © since 2010 by CPG-Nuke Dev Team. All rights reserved.

	Only use when class has Traversable, else use \Poodle\SQL\Interfaces\ResultIterator
	See https://bugs.php.net/bug.php?id=61808
*/

namespace Poodle\SQL\Interfaces;

interface Result extends \ArrayAccess, \Countable
{
	public function data_seek($offset);
	public function fetch_all($resulttype=\Poodle\SQL::NUM); # PHP 5.3.0 + mysqlnd
	public function fetch_array();
	public function fetch_assoc();
	public function fetch_field_direct($offset);
	public function fetch_field();
	public function fetch_fields();
	public function fetch_object($class_name=null, array $params=null);
	public function fetch_row();
	public function field_seek($offset);
	public function free();
	# ArrayAccess: offsetExists($k), offsetGet($k), offsetSet($k, $v), offsetUnset($k)
	# Countable: count()

	public function setFetchObjectParams($class_name=null, array $params=array());
}
