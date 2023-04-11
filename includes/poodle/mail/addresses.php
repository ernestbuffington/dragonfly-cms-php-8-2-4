<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail;

class Addresses extends \ArrayIterator
{
	public function append($address, $name='')
	{
		if ($address instanceof Address) {
			parent::append($address);
		} else {
			parent::append(new Address($address, $name));
		}
	}

	public function offsetSet($i, $v)
	{
		if ($v instanceof Address) {
			parent::offsetSet($i, $v);
		} else {
			trigger_error('Value is not a \Poodle\Mail\Address');
		}
	}

	function __toString()
	{
		$result = array();
		foreach ($this as $address) {
			$result[] = $address->__toString();
		}
		return implode("\n", $result);
	}

	public function asEncodedString($charset = 'UTF-8', $encoding = 'Q', $phrase = true)
	{
		$result = array();
		foreach ($this as $address) {
			$result[] = $address->asEncodedString($charset, $encoding, $phrase);
		}
		return implode(', ', $result);
	}
}
