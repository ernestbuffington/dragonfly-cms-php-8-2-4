<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail;

class Address
{
	protected
		$address = '',
		$name    = '';

	function __construct($address, $name='')
	{
		$this->setAddress($address);
		$this->setName($name);
	}

	protected function setAddress($v)
	{
		$v = \Poodle\Mail::removeCRLF($v);
//		\Poodle\Security::checkEmail($v,0);
		$this->address = $v;
	}

	protected function setName($v)
	{
		$this->name = \Poodle\Mail::removeCRLF($v);
	}

	function __get($k)
	{
		if (property_exists($this, $k)) { return $this->$k; }
		trigger_error('Undefined property '.get_class($this).'->'.$k);
	}

	function __set($k, $v)
	{
		if ('address'   === $k) { $this->setAddress($v); }
		else if ('name' === $k) { $this->setName($v); }
		else { trigger_error('Undefined property '.get_class($this).'->'.$k); }
	}

	function __toString()
	{
		return $this->name ? "{$this->name} <{$this->address}>" : "<{$this->address}>";
	}

	public function asEncodedString($charset = 'UTF-8', $encoding = 'Q', $phrase = true)
	{
		# RFC 6530 SMTPUTF8
		if ($this->name) {
			return \Poodle\Mail::encodeHeader('', $this->name, $phrase, $encoding, $charset)
			 ." <{$this->address}>";
//			 .' <'.\Poodle\Mail::encodeHeader('', $this->address, true).'>';
		}
		return "<{$this->address}>";
	}

}
