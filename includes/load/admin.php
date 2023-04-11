<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }
if (php_sapi_name() == 'cli' || empty($_SERVER['PHP_SELF'])) { die('This script cannot be accessed through the command line'); }

define('ADMIN_PAGES', true);
require 'includes/cmsinit.inc';
if (DF_HTTP_SSL_REQUIRED && 'https' !== $_SERVER['REQUEST_SCHEME']) {
	URL::redirect('https://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI']);
}
header('Last-Modified: '.date('D, d M Y H:i:s', time()).' GMT');
header('X-Content-Type-Options: nosniff');
// Only Internet Explorer needs the useless P3P header to accept cookies
header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');

$Module = new Dragonfly\Modules\Module('op', 'file');
$module_name = $Module->name;

# backward compatibility
global $pagetitle, $modheader;
$pagetitle = $modheader = '';

\Dragonfly\Output\Js::add('includes/javascript/poodle.js');

// if (empty($_SERVER['HTTPS']) && $op != 'logout') { URL::redirect('https://'.$MAIN_CFG['server']['domain'].$_SERVER['REQUEST_URI']); }

\Dragonfly\Page::title(_ADMINISTRATION, false);

/***********************************************************************************
 Echo the big graphical menu, function called by the admin modules
	$cat: Which menucategory to show, default = all
************************************************************************************/

$op = (!empty($_GET['op']) ? $_GET['op'] : (isset($_POST['op']) ? $_POST['op'] : 'index'));
if ('logout' == $op) {
	unset($_SESSION['CPG_SESS']['admin']);
	cpg_error(_YOUARELOGGEDOUT, _ADMINMENU_LOGOUT, \Dragonfly::$URI_INDEX);
}
else if (is_admin()) {
//	if (!can_admin($op)) { cpg_error('Access Denied', 403); }
	include($Module->chroot.$Module->file);
	if (defined('HEADER_OPEN')) { require_once('footer.php'); }
	else if (!XMLHTTPRequest) { cpg_error("The requested file, {$Module->file}, didn't output data correctly"); }
}
else {
	if (SEARCHBOT) {
		\Dragonfly\Net\Http::headersFlush(403);
	}
	\Dragonfly\Admin\Login::process();
}
