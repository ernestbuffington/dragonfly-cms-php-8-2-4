<?php

class Dragonfly_Identity_v9pass
{
	public static function verify($plain, $password)
	{
		$md5 = md5($plain);
		if (\Poodle\Hash::verify('sha256', $md5, $password) || $md5 === $password) {
			\Dragonfly::getKernel()->SQL->TBL->auth_identities->update(
				array('auth_password' => \Poodle\Auth::hashPassword($plain)),
				"auth_provider_id = 1 AND auth_password = 'Dragonfly_Identity_v9pass:{$password}'"
			);
			return true;
		}
		return false;
	}
}
