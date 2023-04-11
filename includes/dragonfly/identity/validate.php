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

abstract class Validate
{

	public static function password($password, $confirm=null)
	{
		$K = \Dragonfly::getKernel();
		$CFG = $K->CFG->member;
		if (strlen($password) < $CFG->minpass && '' != $password) {
			throw new \Exception(sprintf($K->L10N['The password must be at least %d characters'], $CFG->minpass));
		}
		if (!is_null($confirm) && $password != $confirm) {
			throw new \Exception(_PASSDIFFERENT);
		}
	}

	# Check to see if the username has been taken, or if it is disallowed.
	# Used for registering, changing names, and posting anonymously with a username
	public static function nickname($username)
	{
		if (50 < strlen($username)) { throw new \Exception(_NICK2LONG); }
		if ( 4 > strlen($username)) { throw new \Exception(_NICK2SHORT); }
		if (is_numeric($username))  { throw new \Exception(_NICKNUMERIC); }
		if (preg_match('~[ *#%"\'`&^@><\\\\/]~',$username)) {
			throw new \Exception(sprintf(_ERROR_BAD_CHAR, _NICKNAME));
		}

		$K = \Dragonfly::getKernel();
		$deniedusers = 'staff|'.$K->CFG->member->DeniedUserNames.'|'.$K->CFG->global->CensorList;
		$words = array();
		if (preg_match('#('.$deniedusers.')#i', $username, $words)) {
			throw new \Exception(_NAMEDENIED." '{$words[0]}'");
		}

		$username = $K->SQL->quote(mb_strtolower($username));
		if ($K->SQL->TBL->users->count("user_nickname_lc = {$username}")
		 || $K->SQL->TBL->users_request->count("request_type = 0 AND LOWER(user_nickname) = {$username}"))
		{
			throw new \Exception(_NICKTAKEN);
		}

		return true;
	}

	public static function email($user_email)
	{
		\Dragonfly\Net\Validate::emailaddress($user_email,1);

		$SQL = \Dragonfly::getKernel()->SQL;
		$email = $SQL->quote(\Poodle\Input::lcEmail($user_email));
		if ($SQL->count('users', "user_email = {$email}") > 0 ||
			$SQL->count('users_request', "user_email = {$email}") > 0) {
			throw new \Exception(_EMAILREGISTERED);
		}

		/* Now check deleted PHP-Nuke account emails */
		if ($SQL->count('users', "user_email='".md5($user_email)."'") > 0) {
			throw new \Exception(_EMAILNOTUSABLE);
		}
		return true;
	}

}
