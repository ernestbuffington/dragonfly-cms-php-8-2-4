<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
define('HEADER_OPEN', true);

if ('HEAD' === $_SERVER['REQUEST_METHOD']) {
	\Dragonfly\Net\Http::headersFlush();
	exit;
}

function head()
{
	global $METATAGS, $Blocks,
		$bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4, $textcolor1, $textcolor2, $CPG_SESS,
		$Module, $pagetitle;
	$K = \Dragonfly::getKernel();

	//if (300 < $_SESSION['SECURITY']['status']) { online(); }
	if (empty($_SESSION['SECURITY']['banned'])) {
		if ($K->SESSION) {
			$K->SESSION->online();
		}
	}

	define('THEME_PATH', 'themes/'.($K->OUT->theme?:'default').'/');
	header('imagetoolbar: no');
	header(\Dragonfly\Net\Http::$contentType['html']);
	$MAIN_CFG = $K->CFG;
	if ($MAIN_CFG->global->block_frames) {
		header('X-Frame-Options: SAMEORIGIN');
	}

	$header = '';
	$userinfo = $K->IDENTITY;

	$GLOBALS['DF']->setState(DF::BOOT_HEADER);

	$Blocks = new \Dragonfly\Blocks($Module ? $Module->mid : 0);
	$Blocks->prepare(\Dragonfly\Blocks::LEFT);
	$Blocks->prepare(\Dragonfly\Blocks::CENTER);
	$Blocks->prepare(\Dragonfly\Blocks::RIGHT);
	$Blocks->prepare(\Dragonfly\Blocks::DOWN);

	// v9
	$bgcolor1 = $bgcolor2 = $bgcolor3 = $bgcolor4 = '#FFFFFF';
	$textcolor1 = $textcolor2 = '#000000';
	$cpgtpl = $K->OUT;
	$CPG_SESS = $_SESSION['CPG_SESS'];

	// include theme code
	require_once(THEME_PATH .'theme.php');
	if (!defined('THEME_VERSION')) { define('THEME_VERSION', '9.0'); }

	themeheader();
	if (!function_exists('themefooter')) {
		function themefooter()
		{
			\Dragonfly::getKernel()->OUT->display('footer');
		}
	}

	if ($METATAGS) {
		foreach ($METATAGS as $name => $content) {
			\Dragonfly\Page::metatag($name, $content);
		}
	}
/*
	if (!defined('ADMIN_PAGES')) {
		\Dragonfly\Page::tag('link rel="canonical" href="'.BASEHREF.URL::canonical(true).'"');
	}
	$L10N = $K->L10N;
	if ($L10N->multilingual) {
		foreach ($L10N->getActiveList() as $lng) {
			if ($lng['value'] != $L10N->lng) {
				\Dragonfly\Page::link('alternate', URL::lang($lng['value']), $lng['value']);
			}
		}
		unset($lng);
	}
*/
	if (is_user() && $userinfo->popup_pm && $userinfo->new_privmsg && $Module && $Module->name != 'Private_Messages' && \Dragonfly\Modules::isActive('Private_Messages')) {
		$L10N = $K->L10N;
		$L10N->load('Private_Messages');
		\Poodle\Notify::message(
			$L10N->get(($userinfo->new_privmsg > 1) ? 'You_new_pms' : 'You_new_pm')
			.'. '.sprintf($L10N['Click_view_privmsg'], '<a href="'.htmlspecialchars(URL::index('Private_Messages&folder=inbox')).'">', '</a>')
		);
	}
	if ($MAIN_CFG->global->maintenance) {
		\Poodle\Notify::warning(_SYS_MAINTENANCE .' [<a href="'.htmlspecialchars(URL::admin('settings&s=1')).'">'._RESET.'</a>]');
	}
	if (is_admin() && DF_MODE_INSTALL) {
		\Poodle\Notify::warning('Installer mode still active, [<a href="'.htmlspecialchars(URL::admin('settings&s=11')).'">'._RESET.'</a>].');
	}
	if (Dragonfly::isDemo()) {
		\Poodle\Notify::warning(_SYS_DEMO);
	}
	if (is_file('header_hooks.php')) {
		include_once('header_hooks.php');
	}
	if (!\Dragonfly\Page::get('title')) {
		if (!empty($pagetitle)) { \Dragonfly\Page::title($pagetitle, false); }
	}

	$K->OUT->assign_vars(array(
		'I18N'         => 'enctype="multipart/form-data"',
		'B_PAGETITLE'  => 1 < strlen(strip_tags(\Dragonfly\Page::get('title'))), #TODO check strip_tags in class
		'S_HEADER_TAGS'=> \Dragonfly\Page::getHeaders(),
		'S_SITENAME'   => $MAIN_CFG->global->sitename,
		'S_BLOCK_FRAMES' => intval($MAIN_CFG->global->block_frames),
		'B_SIDE_LEFT'  => !!$K->OUT->leftblock,
		'B_SIDE_CENTER'=> !!$K->OUT->centerblock,
		'B_SIDE_RIGHT' => !!$K->OUT->rightblock,
		'B_SIDE_DOWN'  => !!$K->OUT->bottomblock,
		'CSS_DATA'     => \Dragonfly\Output\Css::flushToTpl(),
		'JS_DATA'      => \Dragonfly\Output\Js::flushToTpl(),
	));

	if (!defined('ADMIN_PAGES')) {
		if (class_exists('Dragonfly\\Modules\\Statistics\\Counter')) {
			\Dragonfly\Modules\Statistics\Counter::inc();
		}
		require_once('includes/functions/messagebox.php');
	}

	$K->OUT->display('header');
}

head();
