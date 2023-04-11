<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\SASL;

class Scram extends \Poodle\Auth\SASL
{

	protected
		$algo,
		$nonce,
		$password,
		$gs2_header,
		$auth_message,
		$server_key;

	function __construct($algo)
	{
		$algo = str_replace('-', '', strtolower($algo));
		if (!in_array($algo, hash_algos())) {
			throw new \Exception("Unsupported SASL SCRAM algorithm: {$algo}");
		}
		$this->algo = $algo;
	}

	public function authenticate($authcid, $password, $authzid = null)
	{
		// SASLprep
		$authcid = str_replace(array('=',','), array('=3D','=2C'), $authcid);

		$this->nonce = bin2hex(random_bytes(16));
		$this->password = $password;
		$this->gs2_header = 'n,' . (empty($authzid) ? '' : 'a=' . $authzid) . ',';
		$this->auth_message = "n={$authcid},r={$this->nonce}";
		return $this->encode($this->gs2_header . $this->auth_message);
	}

	public function challenge($challenge)
	{
		$challenge = $this->decode($challenge);
		$values = static::parseMessage($challenge);

		if (empty($values['r'])) {
			throw new \Exception('Server nonce not found');
		}
		if (empty($values['s'])) {
			throw new \Exception('Server salt not found');
		}
		if (empty($values['i'])) {
			throw new \Exception('Server iterator not found');
		}

		if (substr($values['r'], 0, strlen($this->nonce)) !== $this->nonce) {
			throw new \Exception('Server invalid nonce');
		}

		$salt = base64_decode($values['s']);
		if (!$salt) {
			throw new \Exception('Server invalid salt');
		}

		$pass = hash_pbkdf2($this->algo, $this->password, $salt, intval($values['i']), 0, true);
		$this->password = null;

		$ckey = hash_hmac($this->algo, 'Client Key', $pass, true);
		$skey = hash($this->algo, $ckey, true);

		$cfmb = 'c='.base64_encode($this->gs2_header).',r='.$values['r'];
		$amsg = "{$this->auth_message},{$challenge},{$cfmb}";

		$csig = hash_hmac($this->algo, $amsg, $skey, true);
		$proof = base64_encode($ckey ^ $csig);

		$skey = hash_hmac($this->algo, 'Server Key', $pass, true);
		$this->server_key = hash_hmac($this->algo, $amsg, $skey, true);

		return $this->encode("{$cfmb},p={$proof}");
	}

	public function verify($data)
	{
		$v = static::parseMessage($this->decode($data));
		if (empty($v['v'])) {
			throw new \Exception('Server signature not found');
		}
		return base64_encode($this->server_key) === $v['v'];
	}

	protected static function parseMessage($msg)
	{
		if ($msg && preg_match_all('#(\w+)\=(?:"([^"]+)"|([^,]+))#', $msg, $m)) {
			return array_combine(
				$m[1],
				array_replace(
					array_filter($m[2]),
					array_filter($m[3])
				)
			);
		}
		return array();
	}

	public static function isSupported($param)
	{
		return in_array(str_replace('-', '', strtolower($param)), hash_algos());
	}

}
