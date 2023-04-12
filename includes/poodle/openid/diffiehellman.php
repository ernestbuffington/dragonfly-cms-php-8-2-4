<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\OpenID;

class DiffieHellman
{
	public const
		DEF_GEN = 2,
		DEF_MOD = '155172898181473697471232257763715539915724801966915404479707795314057629378541917580651227423698188993727816152646631438561595825688188889951272158842675419950341258706556549803580104870537681476726513255747040765857479291291572334510643245094715007229621094194349783925984760375594985848253359305585439638443';

	public
		$mod,
		$gen,
		$private,
		$public;

	function __construct($mod = null, $gen = null, $private = null)
	{
		$this->mod = $mod ?: self::DEF_MOD;
		$this->gen = $gen ?: self::DEF_GEN;
		$this->private = $private ?: \Poodle\Math::add(\Poodle\Math::rand($this->mod), 1);
		$this->public  = \Poodle\Math::powmod($this->gen, $this->private, $this->mod);
	}

	public function usingDefaultValues()
	{
		return (self::DEF_MOD == $this->mod && self::DEF_GEN == $this->gen);
	}

	public function xorSecret($composite, $secret, $algo)
	{
		$dh_shared = \Poodle\Math::powmod($composite, $this->private, $this->mod); # shared secret
		$dh_shared_str = \Poodle\Math::longToBinary($dh_shared);
		$hash_dh_shared = hash($algo, $dh_shared_str, true);

		$xsecret = '';
		$l = strlen($secret);
		for ($i = 0; $i < $l; ++$i) {
			$xsecret .= chr(ord($secret[$i]) ^ ord($hash_dh_shared[$i]));
		}
		return $xsecret;
	}

}
