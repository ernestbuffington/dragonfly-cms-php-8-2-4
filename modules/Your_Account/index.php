<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }
\Dragonfly\Page::title(_Your_AccountLANG, false);
require_once(__DIR__ . '/functions.php');
$op = $_POST->text('op') ?: $_GET->text('op');

if (!empty($_GET['profile']))
{
	$class = new \Dragonfly\Modules\Your_Account\Userinfo();
	$class->display($_GET['profile']);
}
else if ('userinfo' == $op && isset($_GET['username']) && !empty($_GET['username']))
{
	$class = new \Dragonfly\Modules\Your_Account\Userinfo();
	$class->display($_GET['username']);
}
else if ('logout' == $op)
{
	\Dragonfly\Page::title(_LOGOUT, false);
	$uri = null;
	if (!empty($_GET['redirect_uri'])) {
		$uri = \Poodle\Base64::urlDecode($_GET['redirect_uri'], true);
	}
	if (!$uri || '/' !== $uri[0]) {
		$uri = (isset($_GET['redirect']) ? $_SESSION['CPG_SESS']['user']['uri'] : \Dragonfly::$URI_INDEX);
	}
	\Dragonfly::closeRequest(_YOUARELOGGEDOUT, 200, $uri);
}
else if (is_user())
{
	$userinfo = Dragonfly::getKernel()->IDENTITY;
	if (isset($_POST['avatargallery']) || isset($_GET['avatargallery']))
	{
		require_once(__DIR__ . '/avatars.php');
		display_avatar_gallery($userinfo);
	}
	else if (isset($_GET['edit']) || isset($_GET['auth']))
	{
		require_once(__DIR__ . '/edit_profile.php');
		if ('POST' === $_SERVER['REQUEST_METHOD']) {
			saveuser($userinfo);
		} else {
			edituser($userinfo);
		}
	}
	else switch ($op)
	{
		case 'edithome':
			display_member_block();
			\Dragonfly\Page::title(_MA_HOMECONFIG, false);
			\Dragonfly\BBCode::pushHeaders();
			$OUT = \Dragonfly::getKernel()->OUT;
			$OUT->U_SAVEHOME = URL::index('&op=savehome');
			$OUT->display('Your_Account/edit_home');
			break;
		case 'savehome':
			if (isset($_POST['storynum'])) {
				$userinfo->storynum = max(0, min(20, $_POST->uint('storynum')));
			}
			$userinfo->ublockon = $_POST->bool('ublockon');
			$userinfo->ublock   = $_POST['ublock'];
			$userinfo->save();
			$msg = sprintf(\Dragonfly::getKernel()->L10N['%s saved'], _MA_HOMECONFIG);
			\Dragonfly::closeRequest($msg, 200, URL::index());
			break;

		case 'editcomm':
			display_member_block();
			\Dragonfly\Page::title(_COMMENTSCONFIG, false);
			$OUT = \Dragonfly::getKernel()->OUT;
			$OUT->U_SAVECOMM = URL::index('&op=savecomm');
			$OUT->display('Your_Account/edit_comm');
			break;
		case 'savecomm':
			$userinfo->umode      = $_POST->text('umode');
			$userinfo->uorder     = max(0, min(2, $_POST->uint('uorder')));
//			$userinfo->thold      = max(-1, min(5, $_POST->uint('thold')));
			$userinfo->noscore    = $_POST->bool('noscore');
			$userinfo->commentmax = max(0, $_POST->uint('commentmax'));
			$userinfo->save();
			$msg = sprintf(\Dragonfly::getKernel()->L10N['%s saved'], _COMMENTSCONFIG);
			\Dragonfly::closeRequest($msg, 200, URL::index());
			break;

		default:
			$class = new \Dragonfly\Modules\Your_Account\Userinfo();
			$class->display(is_user());
			break;
	}
} else {
	// Login first
	\URL::redirect(\Dragonfly\Identity::loginURL());
}
