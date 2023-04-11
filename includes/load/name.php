<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
require('includes/cmsinit.inc');

if (DF_HTTP_SSL_REQUIRED && 'https' !== $_SERVER['REQUEST_SCHEME']) {
	URL::redirect(str_replace('http://','https://',BASEHREF).URL::canonical(), 301);
}

//
//header('X-Content-Type-Options: nosniff'); already sent from bootstrap

if ($SESS->new && $MAIN_CFG['global']['httpref'] && !empty($_SERVER['HTTP_REFERER'])) {
	$referer = $_SERVER['HTTP_REFERER'];
	if (strpos($referer, '://') && !stripos($referer, $MAIN_CFG['server']['domain'])) try {
		$tbl = $db->TBL->referer;
		if (!$tbl->update(array('lasttime'=>time()), array('url'=>$referer))) {
			$tbl->insert(array('lasttime'=>time(), 'url'=>$referer));
		}
		$numrows = $tbl->count();
		$httprefmax = (int)$MAIN_CFG['global']['httprefmax'];
		if ($numrows >= $httprefmax) {
			$db->query("DELETE FROM {$tbl} ORDER BY lasttime LIMIT ".($numrows-($httprefmax/2)));
		}
	} catch (\Exception $e){}
}

/* mid needs work, temporary expanded for readibility */
Dragonfly\Modules\Module::$custom[-2] = array(
	'mid' => -2,
	'get' => 'credits',
	'title' => 'credits',
	'file' => 'includes/info.inc',
	'view' => 0,
	'blocks' => \Dragonfly\Blocks::NONE);
Dragonfly\Modules\Module::$custom[-3] = array(
	'mid' => -3,
	'get' => 'privacy_policy',
	'title' => 'privacy_policy',
	'file' => 'includes/info.inc',
	'view' => 0,
	'blocks' => \Dragonfly\Blocks::NONE);

\Dragonfly\Page\Router::fwdSrc();

$Module = new Dragonfly\Modules\Module('name', 'file');
if (!is_file($Module->path.$Module->file)) {
	//cpg_error(sprintf(_MODULENOEXIST, CPG_DEBUG ? $Module->path.$Module->file : ''), 404);
	error_log('404: '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['REQUEST_URI'].(empty($_SERVER['HTTP_REFERER'])?'':' referer '.$_SERVER['HTTP_REFERER']));
	$_SERVER['REDIRECT_STATUS'] = 404;
	$_SERVER['REDIRECT_ERROR_NOTES'] = 'File not found';
	require_once('error.php');
	exit;
}

/* Doesn't work: issue with OpenID Connect logins
if ('GET' === $_SERVER['REQUEST_METHOD'] || 'HEAD' === $_SERVER['REQUEST_METHOD']) {
	if (URL::query() && URL::query() !== URL::canonical()) {
		URL::redirect(BASEHREF.URL::canonical(), 301);
	}
}
*/

# check for permissions
$Module->allow();
$home = !count($_GET) || (1 == count($_GET) && isset($_GET['newlang']));

/* compatibility */
$module_name  = $Module->name;
$showblocks   = &$Module->sides;
$module_title = $Module->title;
/* end compatibility */

# get module custom language
//Dragonfly::getKernel()->L10N->load($Module->name);

# good place where to run hooks
Dragonfly::getKernel()->extend('SOCIAL', '\Dragonfly\Social');
//$GLOBALS['DF']->setState(DF::PRE_MODULE);

$L10N = \Dragonfly::getKernel()->L10N;
if ($L10N->multilingual) {
	foreach ($L10N->getActiveList() as $lng) {
		if ($lng['value'] != $L10N->lng) {
			\Dragonfly\Page::link('alternate', URL::lang($lng['value']), $lng['value']);
		}
	}
	unset($lng);
}
unset($L10N);

//MetaTag::add(Module::$metatag); // must include Page data already
include('includes/meta.php');
require($Module->path.$Module->file);
if (defined('HEADER_OPEN')) { require_once('footer.php'); }
