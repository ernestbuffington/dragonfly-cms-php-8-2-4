<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Auth
{

	const
		ERR_FAILURE            = 1,
		ERR_IDENTITY_NOT_FOUND = 2, # Failure due to identity not being found.
		ERR_IDENTITY_AMBIGUOUS = 3, # Failure due to identity being ambiguous.
		ERR_CREDENTIAL_INVALID = 4, # Failure due to invalid credential being supplied.
		ERR_UNKNOWN            = 5; # Failure due to unknown reasons.

	public static function secureClaimedID($id)
	{
		return \Poodle\Hash::string('sha1', mb_strtolower(mb_substr($id,0,4096)));
	}

	public static function algos()
	{
		return array_unique(array_merge(
			array(\Poodle::getKernel()->CFG->auth->default_pass_hash_algo),
			\Poodle\Hash::available('argon2i') ? array('argon2i','bcrypt') : array('bcrypt')
		));
	}

	protected static function getAlgo($algo)
	{
		$algos = array_unique(array_merge(array($algo), static::algos()));
		foreach ($algos as $algo) {
			if ($algo && \Poodle\Hash::available($algo)) {
				return $algo;
			}
		}
		// Not secure but always better then none at all
		return 'sha1';
	}

	public static function generatePassword($length=12, $chars='')
	{
		if ($chars)  { $chars = is_array($chars) ? $chars : str_split($chars); }
		if (!$chars) { $chars = range(33, 126); }
		$pass  = '';
		shuffle($chars);
		$l = count($chars)-1;
		for ($x=0; $x<$length; ++$x) {
			$c = $chars[mt_rand(0, $l)];
			$pass .= is_int($c) ? chr($c) : $c;
		}
		return $pass;
	}

	public static function hashPassword($password, $algo=null)
	{
		if ($password && 1024 >= strlen($password)) {
			$algo = self::getAlgo($algo);
			return $algo.':'.\Poodle\Hash::string($algo,$password);
		}
		return null;
	}

	public static function verifyPassword($plain, $hash)
	{
		// No plain password given or deny very long password
		if (!$plain || 1024 < strlen($plain)) {
			return false;
		}

		// Verify given password
		list($algo, $password) = explode(':', $hash, 2);
		return ($algo && $password
		 && (\Poodle\Hash::available($algo)
			? \Poodle\Hash::verify($algo, $plain, $password)
			: (class_exists($algo) && $algo::verify($plain, $password))));
	}

	public static function update($provider, \Poodle\Auth\Credentials $credentials)
	{
		if (!($provider instanceof \Poodle\Auth\Provider)) {
			$provider = \Poodle\Auth\Provider::getById($provider);
		}
		if (!$provider) {
			throw new \Exception('Poodle\Auth::update invalid provider');
		}
		return $provider->updateAuthentication($credentials);
	}
}
