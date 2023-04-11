<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	This is the old v9 login handler.
	It only supports database based logins.
*/

namespace Dragonfly\Identity;

class Login
{

	public static function member()
	{
		$K = \Dragonfly::getKernel();
		if (empty($_POST['ulogin']) || ($K->CFG->global->sec_code & 2 && !static::checkSecCode())) {
			// \Poodle\Notify::error(_LOGININCOR);
			\URL::redirect(\Dragonfly\Identity::loginURL());
		}

		$provider = \Poodle\Auth\Provider::getById(1);
		if ($provider instanceof \Poodle\Auth\Provider)
		{
			$result = $K->IDENTITY->authenticate(
				array(
					'auth_claimed_id' => $_POST['ulogin'],
					'auth_password'   => $_POST['user_password'],
				),
				$provider);

			self::processAuthProviderResult($result, $provider);
		}
	}

	protected static function processAuthProviderResult($result, $provider)
	{
		$K = \Dragonfly::getKernel();
		if ($result instanceof \Poodle\Auth\Result\Success) {
/*
			if ($setinfo['user_level'] == 0) { \URL::redirect(\Dragonfly\Identity::getProfileURL($setinfo['user_id'])); }
			else if ($setinfo['user_level'] == -1) { \URL::redirect(\Dragonfly\Identity::getProfileURL($setinfo['user_id'])); }
*/
			$K->IDENTITY->updateLastVisit();

			$K->SQL->query("DELETE FROM {$K->SQL->TBL->session} WHERE host_addr=".$K->SQL->quoteBinary(\Dragonfly\Net::ipn())." AND guest=1");

			unset($_SESSION['CPG_SESS']['session_start']);

			$class = isset($K->CFG->auth_cookie->class) ? $K->CFG->auth_cookie->class : 'Dragonfly\\Identity\\Cookie';
			$class::set();
		}
		else
		{
			// \Poodle\Notify::error(_LOGININCOR);
			\URL::redirect(\Dragonfly\Identity::loginURL());
		}
	}

	public static function admin()
	{
		if (!empty($_POST['alogin']) && !empty($_POST['pwd']) && static::checkSecCode()) {
			if ($_SESSION['DF_VISITOR']->admin->authenticate($_POST['alogin'], $_POST['pwd'], $_POST['totp'])) {
				if (isset($_POST['persistent'])) {
					\Dragonfly\Admin\Cookie::set();
				}
				unset($_SESSION['CPG_SESS']['admin']);
				\URL::redirect($_SERVER['REQUEST_URI']);
			}
		}
	}

	protected static function checkSecCode()
	{
		return \Dragonfly\Output\Captcha::validate($_POST)
		// or v9 security image
		 || (!empty($_POST['gfx_check']) && !empty($_POST['gfxid']) && validate_secimg());
	}

}
