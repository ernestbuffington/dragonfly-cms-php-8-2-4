<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Admin;

class Cookie extends \Dragonfly\Identity\Cookie
{

	protected static function getConfig()
	{
		$cfg = \Dragonfly::getKernel()->CFG;
		$cfg->set('admin_cookie', 'path', $cfg->cookie->path);
		$ac = $cfg->admin_cookie;
		if (!isset($ac->allow))       { $cfg->set('admin_cookie', 'allow', 1); }
		if (!isset($ac->compression)) { $cfg->set('admin_cookie', 'compression', ''); }
		if (!$ac->cryptkey) { $cfg->set('admin_cookie', 'cryptkey', sha1(microtime())); }
		if (!$ac->cipher) {
			$ciphers = \Poodle\Crypt\Symmetric::listCiphers();
			$cfg->set('admin_cookie', 'cipher', $ciphers[array_rand($ciphers)]);
		}
		// If no timeout defined or timeout invalid: set to 180 days
		if (1 > $ac->timeout) {
			$cfg->set('admin_cookie', 'timeout', 180);
		}
		return $ac;
	}

	protected static function getIdentity($id)
	{
		$admin = new Identity($id);
		return $admin->id ? $admin : false;
	}

	public static function set($setuid=false, $secure=false)
	{
		if (!$setuid) {
			$setuid = $_SESSION['DF_VISITOR']->admin->id;
		}
		if (0 < $setuid) {
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

}
