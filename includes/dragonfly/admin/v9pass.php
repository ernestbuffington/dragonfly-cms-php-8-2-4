<?php

class Dragonfly_Admin_v9pass
{
	public static function verify($plain, $password)
	{
		$md5 = md5($plain);
		if (\Poodle\Hash::verify('sha256', $md5, $password) || $md5 === $password) {
			\Dragonfly::getKernel()->SQL->TBL->admins->update(
				array('pwd' => \Poodle\Auth::hashPassword($plain)),
				"pwd = 'Dragonfly_Admin_v9pass:{$password}'"
			);
			return true;
		}
		return false;
	}
}
