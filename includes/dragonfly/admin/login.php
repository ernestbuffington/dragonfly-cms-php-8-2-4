<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Admin;

class Login
{

	public static function getMinPassLength()
	{
		return \Dragonfly::getKernel()->CFG ? max(10, \Dragonfly::getKernel()->CFG->admin->minpass) : 10;
	}

	public static function isValidPassword($pwd)
	{
		return (strlen($pwd) >= static::getMinPassLength());
/*
		return (preg_match('#[0-9]#', $pass)
		 && preg_match('#[a-z]#', $pass)
		 && preg_match('#[A-Z]#', $pass)
		);
*/
	}

	public static function process()
	{
		if (!is_admin()) {
			$K = \Dragonfly::getKernel();
			if ($K->SQL->count('admins')) {
				static::showForm();
			} else {
				if (!isset($_POST['name'])) {
					$TPL = $K->OUT;
					$TPL->login_action = \Dragonfly::$URI_ADMIN;
					$TPL->admin_pass_pattern = '.{'.static::getMinPassLength().',}';
					$TPL->admin_pass_info = sprintf($TPL->L10N['The password must be at least %d characters'], static::getMinPassLength());
					$TPL->display('admin/account/create-first');
					require('footer.php');
				} else if (isset($_POST['create_admin'])) {
					if (static::isValidPassword($_POST['pwd'])) {
						$K->SQL->TBL->admins->insert(array(
							'aid'   => $_POST['name'],
							'email' => $_POST['email'],
							'pwd'   => \Poodle\Auth::hashPassword($_POST['pwd']),
							'radminsuper' => 1
						));
						if (!empty($_POST['user_new'])) {
							$timezone = date_default_timezone_get();
							$user_timezone = date_default_timezone_set($_POST['timezone']) ? $_POST['timezone'] : $timezone;
							date_default_timezone_set($timezone);

							$user_id = $K->SQL->TBL->users->insert(array(
								'username'         => $_POST['name'],
								'user_nickname_lc' => mb_strtolower($_POST['name']),
								'user_email'       => $_POST['email'],
								'user_avatar'      => $K->CFG['avatar']['default'],
								'user_regdate'     => time(),
								'theme'            => $K->CFG['global']['Default_Theme'],
								'user_level'       => 2,
								'user_timezone'    => $user_timezone,
							), 'user_id');
							\Poodle\Identity\Search::byID($user_id)->updateAuth(1, $_POST['name'], $_POST['pwd']);
						}
						static::showForm();
					} else {
						cpg_error(sprintf($K->L10N['The password must be at least %d characters'], static::getMinPassLength()));
//						cpg_error(_PASSWORD_MALFORMED);
					}
				}
				exit;
			}
		}
	}

	public static function showForm()
	{
		\Dragonfly\Page::title(_ADMINLOGIN, false);
		$K = \Dragonfly::getKernel();
		$K->OUT->display('admin/account/login');
		require('footer.php');
	}

}
