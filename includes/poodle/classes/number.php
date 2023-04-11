<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

class Number
{
	protected
		$value = '0';

	function __construct($number='0') { $this->set($number); }

	public function set($n)
	{
		$v = Math::getValid($n);
		if (!is_string($v)) {
			throw new \InvalidArgumentException("Invalid number: {$n}");
		}
		$this->value = $v;
	}

	public function add($n) { $this->value = Math::add($this->value, $n); return $this; }
	public function div($n) { $this->value = Math::div($this->value, $n) ?: '0'; return $this; }
	public function mul($n) { $this->value = Math::mul($this->value, $n) ?: '0'; return $this; }
	public function sub($n) { $this->value = Math::sub($this->value, $n); return $this; }

	public function cmp($n) { return Math::cmp($this->value, $n); }
	public function mod($m) { return Math::mod($this->value, $m); }
	public function pow($n) { return Math::pow($this->value, $n); }

	public function asFloat()   { return (float)$this->value; }
	public function asInteger() { return (int)$this->value; }
	public function asClone()   { return clone $this; }

	public function __toString() { return $this->value; }

	public function ceil()  { return ceil($this->value); }
	public function floor() { return floor($this->value); }

	public function round($decimals = 0, $mode = PHP_ROUND_HALF_UP)
	{
		return round($this->value, $decimals, $mode);
	}

	public function format($decimals = 2, $dec_point = '.', $thousands_sep = '')
	{
		return number_format($this->round($decimals), $decimals, $dec_point, $thousands_sep);
	}

	public function getPercentageOf($total)
	{
		return $total ? (int)Math::div(Math::mul($this->value, 100), $total) : 0;
	}

}
