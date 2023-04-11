<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { cpg_error('Access Denied'); }
//Dragonfly::getKernel()->L10N->load('Your_Account');

class Dragonfly_Admin_Auth extends \Poodle\Auth\Admin
{
	public function GET()
	{
		\Dragonfly\Page::title('Authentication');

		$OUT = Dragonfly::getKernel()->OUT;
		$OUT->captcha = extension_loaded('gd');
		if (function_exists('imagettftext')) {
			$fontlist = array();
			$handle = opendir(CORE_PATH.'fonts');
			while ($file = readdir($handle)) {
				if (preg_match('#\.ttf$#',$file)) { $fontlist[$file] = substr($file,0 , -4); }
			}
			closedir($handle);
			natcasesort($fontlist);
			array_unshift($fontlist, '[system]');
			$OUT->captcha_fonts = $fontlist;
		}

		parent::GET();
	}

	public function POST()
	{
		if (\Dragonfly::isDemo()) {
			cpg_error('Deactivate demo mode', 403);
		}
		if (empty($_POST['admin_cookie']['name']) || empty($_POST['config']['auth_cookie']['name'])) {
			cpg_error(sprintf(_ERROR_NOT_SET, 'Cookie name'));
		}

		$CFG = Dragonfly::getKernel()->CFG;

		$CFG->set('admin_cookie', 'allow',       $_POST->bool('admin_cookie','allow'));
		$CFG->set('admin_cookie', 'name',        $_POST->text('admin_cookie','name'));
		$CFG->set('admin_cookie', 'timeout',     $_POST->uint('admin_cookie','timeout'));
		$CFG->set('admin_cookie', 'cipher',      $_POST->text('admin_cookie','cipher'));
		$CFG->set('admin_cookie', 'cryptkey',    $_POST->text('admin_cookie','cryptkey') ?: sha1(mt_rand().microtime()));
		$CFG->set('admin_cookie', 'compression', $_POST->text('admin_cookie','compression'));

		if (extension_loaded('gd')) {
			$CFG->set('global',   'sec_code',  empty($_POST['sec_code']['show']) ? 0 : array_sum($_POST['sec_code']['show']));
			$CFG->set('sec_code', 'back_img',  !empty($_POST['sec_code']['back_img']));
			if (function_exists('imagettftext')) {
				$CFG->set('sec_code', 'font',      $_POST['sec_code']['font']);
				$CFG->set('sec_code', 'font_size', intval($_POST['sec_code']['font_size']));
			}
		}

		parent::POST();
	}
}

\Dragonfly::getKernel()->L10N->load('poodle_auth');
$class = new Dragonfly_Admin_Auth;
$class->{$_SERVER['REQUEST_METHOD']}();
