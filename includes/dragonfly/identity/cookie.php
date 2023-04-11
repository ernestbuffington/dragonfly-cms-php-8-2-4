<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

class Cookie extends \Poodle\Auth\Provider
{
	protected static function getConfig()
	{
		$cfg = \Dragonfly::getKernel()->CFG;
		$cfg->set('auth_cookie', 'path', $cfg->cookie->path);
		$ac = $cfg->auth_cookie;
		if (!isset($ac->allow))       { $cfg->set('auth_cookie', 'allow', 1); }
		if (!isset($ac->compression)) { $cfg->set('auth_cookie', 'compression', ''); }
		if (!$ac->cryptkey) { $cfg->set('auth_cookie', 'cryptkey', sha1(microtime())); }
		if (!$ac->cipher) {
			$ciphers = \Poodle\Crypt\Symmetric::listCiphers();
			$cfg->set('auth_cookie', 'cipher', $ciphers[array_rand($ciphers)]);
		}
		// If no timeout defined or timeout invalid: set to 180 days
		if (1 > $ac->timeout) {
			$cfg->set('auth_cookie', 'timeout', 180);
		}
		if (!isset($ac->ip_protection)) {
			$cfg->set('auth_cookie', 'ip_protection', 1);
		}
		return $ac;
	}

	public function getAction($credentials=array()) {}

	public function authenticate($credentials)
	{
		$ac = static::getConfig();

		# Check if cookie exists
		if (!isset($credentials[$ac->name])) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'No Cookie found');
		}

		if (!($cookie = base64_decode($credentials[$ac->name]))) {
			static::remove();
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Cookie decoding failed');
		}

		# Decrypt and validate cookie
		$mc = new \Poodle\Crypt\Symmetric(array(
			'cipher'      => $ac->cipher,
			'salt'        => $ac->cryptkey,
			'compression' => $ac->compression,
		));
		$cookie = $mc->Decrypt($cookie, $ac->name);
		if (!is_array($cookie) || 3!=count($cookie)) {
			static::remove();
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Cookie decryption failed');
		}

		# Validate identity_id
		$cookie[0] = (int)$cookie[0];
		if (1 > $cookie[0]) {
			static::remove();
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'credential \'identity_id\' failed');
		}

		# Validate client IP
		if (!empty($ac->ip_protection) && \Dragonfly\Net::ip() !== $cookie[2]) {
			static::remove();
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'credential \'IP\' failed');
		}

		# Cookie is correct so check the data
		# Lookup user in the database
		$user = static::getIdentity($cookie[0]);
		if (!$user) {
			static::remove();
			return new \Poodle\Auth\Result\Error(self::ERR_IDENTITY_NOT_FOUND, 'A database record with the supplied identity_id ('.$cookie[0].') could not be found.');
		}

		//Dragonfly::getKernel()->SESSION->setTimeout($cookie[1]);

		return new \Poodle\Auth\Result\Success($user);
	}

	protected static function getIdentity($id)
	{
		return \Poodle\Identity\Search::byID($id);
	}

	public static function set($setuid=false, $secure=false)
	{
		if (!$setuid) {
			$setuid = \Dragonfly::getKernel()->IDENTITY->id;
		}
		if (1 < $setuid) {
			$ac = static::getConfig();
			if ($ac->allow) {
				$data = array(str_pad($setuid,10,'0',STR_PAD_LEFT), $secure, \Dragonfly\Net::ip());
				$pc = new \Poodle\Crypt\Symmetric(array(
					'cipher'      => $ac->cipher,
					'salt'        => $ac->cryptkey,
					'compression' => $ac->compression,
				));
				$data = base64_encode($pc->Encrypt($data, $ac->name));
				setcookie($ac->name, $data, time()+($ac->timeout*86400), $ac->path, '', ('https' === $_SERVER['REQUEST_SCHEME']), true);
				return $data;
			}
		}
	}

	public static function remove()
	{
		$ac = static::getConfig();
		setcookie($ac->name, '', -1, $ac->path, '', ('https' === $_SERVER['REQUEST_SCHEME']), true);
	}
}

class_alias('Dragonfly\\Identity\\Cookie','Dragonfly_Identity_Cookie');
