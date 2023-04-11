<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE') || !defined('CORE_PATH')) { exit; }

if (!defined('SKIP_GZIP')) define('SKIP_GZIP', 0);
if (!defined('SKIP_BUFFERING')) define('SKIP_BUFFERING', 0);

# Are we allowed to modify php.ini on the fly ?
define('CAN_MOD_INI', \Poodle\PHP\INI::canSet());

define('GZIP_ACTIVE', ini_get('zlib.output_compression') || (!SKIP_GZIP && GZIP_OUT && ob_start('ob_gzhandler')));
if (!ob_get_level()) {
	ob_start();
}

# we will always try to buffer the output
# note: defining SKIP_BUFFERING within your application will also turn on implicit flushings
#       ONLY if no output bufferings are active, eg: ini_set(zlib.output_compression, 0) fails
ob_implicit_flush(SKIP_BUFFERING && !GZIP_ACTIVE);

/* Notify state change */
$DF->setState(DF::BOOT_BASE);

Dragonfly::$DEBUG = DF_MODE_DEVELOPER ? Dragonfly::DBG_ALL : Dragonfly::$DEBUG;
Dragonfly::$UMASK = umask();
Dragonfly::loadKernel('dragonfly', array('dragonfly'=>array(
	'cache_dir' => CACHE_PATH,
	'cache_uri' => 'file://'.CACHE_PATH,
	'db_user_prefix' => $user_prefix,

	/**
	 * When set to true, a cached template file date is compared to the
	 * original file. If the cache file is older it will be recompiled.
	 */
	'design_mode' => DF_MODE_DEVELOPER,

	/**
	 * Database Management System configuration
	 * For database replication copy ['dbms']['master'] as ['dbms']['slave']
	 */
	'dbms' => array(
		'adapter' => 'mysqli',
		'tbl_prefix' => $prefix.'_',
		'master' => array(
			'host' => $dbhost,
			'username' => $dbuname,
			'password' => $dbpass,
			'database' => $dbname,
			'charset' => DB_CHARSET,
		),
	),
)));
unset($dbhost, $dbuname, $dbpass, $dbname);
if (!defined('INSTALL')) {
	try {
		$db = Dragonfly::getKernel()->SQL;
	} catch (Exception $e) {
		cpg_error('<b>'.$e.'.<br />Sorry for the inconvenience, we should be back shortly</b>');
	}
//	$db->debug = Dragonfly::DBG_SQL | Dragonfly::DBG_SQL_QUERIES;
//	$db->debug = Dragonfly::$DEBUG & Dragonfly::DBG_SQL | Dragonfly::$DEBUG & Dragonfly::DBG_SQL_QUERIES;
}

if (defined('INSTALL')) return;

if (!defined('XMLFEED')) {
	require_once(CORE_PATH.'functions/display.php');
	require_once(CLASS_PATH.'cpg_member.php');
}

$MAIN_CFG = Dragonfly::getKernel()->CFG;

if (!CPG_DEBUG) {
	$Debugger->setErrorLevel($MAIN_CFG->debug->error_level);
	$Debugger->setLogLevel($MAIN_CFG->debug->log_level);
}
if ($MAIN_CFG->debug->database) {
	$db->debug |= Dragonfly::DBG_SQL_QUERIES;
}

if (version_compare($MAIN_CFG->global->db_version, \Dragonfly::DB_VERSION, '<')) {
	if ($MAIN_CFG->global->db_auto_upgrade) {
		\Dragonfly::ob_clean();
		$upgrade = new \Dragonfly\Setup\Upgrade();
		register_shutdown_function(array($upgrade, 'run'));
	}
	define('HEADER_OPEN', true);
	$OUT = Dragonfly::getKernel()->OUT;
	$OUT->tpl_layout = 'maintenance';
	$OUT->display('footer');
	flush();
	exit;
}

\Poodle\PHP\INI::set('sendmail_from', $MAIN_CFG->global->adminmail);
//trigger_error('sendmail_from: '.ini_get('sendmail_from'), E_USER_NOTICE);
$BASEHREF = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $MAIN_CFG->server->path;
//$BASEHREF = BASEHREF;
if (defined('XMLFEED')) return; # no need to load everything if it's a feed

/**
 * Start the session
 */
$SESS = Dragonfly::getKernel()->SESSION;

/**
 * Set default metatags
 */
# http://wiki.whatwg.org/wiki/MetaExtensions
\Dragonfly\Page::metatag('author', $MAIN_CFG->global->sitename);
\Dragonfly\Page::metatag('description', Dragonfly::getKernel()->L10N->dbget(Dragonfly::getKernel()->CFG->global->slogan));
\Dragonfly\Page::metatag('dcterms.rightsHolder', 'Copyright (c) '.date('Y').' by '.$MAIN_CFG->global->sitename);
\Dragonfly\Page::metatag('dcterms.dateCopyrighted', substr($MAIN_CFG->global->startdate, strpos($MAIN_CFG->global->startdate, ',')+2, 4));
//\Dragonfly\Page::metatag('dcterms.dateCopyrighted', trim(substr($MAIN_CFG->global->startdate, strpos($MAIN_CFG->global->startdate, ',')+1, 5)));
\Dragonfly\Page::metatag('robots', 'index, follow');
\Dragonfly\Page::metatag('rating', 'general');
\Dragonfly\Page::metatag('generator', 'CPG Dragonfly CMS: Copyright (c) 2003-'.date('Y').' by CPG-Nuke Development Team, http://dragonflycms.org');
//\Dragonfly\Page::metatag('revisit-after', '1 days');

/**
 * Include language to detect languages from browser setting and user preferences
 * Join our language initiative (NLI) http://dragonflycms.org
 */
$DF->setState(DF::BOOT_ETH);
$currentlang = Dragonfly::getKernel()->L10N->lng;
Dragonfly::getKernel()->L10N->load('main');

/**
 * DF < 10 Legacy code
 */
define('_LANGCODE', Dragonfly::getKernel()->L10N->lng);
if (!defined('_CHARSET')) { define('_CHARSET', Dragonfly::CHARSET); }
$userinfo = Dragonfly::getKernel()->IDENTITY;

/**
 * Load member/visitor details
 */
$CLASS['member'] = new cpg_member();

/**
 * Load the administrator
 */
$CLASS['member']->loadadmin();

/**
 * Check if the <form> POST is comming from this server
 */
if (!is_user() && 'POST' == $_SERVER['REQUEST_METHOD'] && \Dragonfly::getKernel()->SESSION->is_new()) {
	\Poodle\LOG::error('POST', 'POST request on new session "'.session_id().'" (maybe changed IP?)');
	cpg_error('Please enable cookies to post on this site. If you feel that you have reached this message in error please go back to the preceding page and post again');
}

/**
 * Check if maintenance is turned on
 */
if ($MAIN_CFG->global->maintenance && !is_admin() && !defined('ADMIN_PAGES') && !defined('INSTALL')) {
	cpg_error('<strong>'.$MAIN_CFG->global->maintenance_text.'</strong>', 'Maintenance');
}

/**
 * Load the theme template system
 */
$DF->setState(DF::BOOT_USER);
# Load template handler
$template = $cpgtpl = Dragonfly::getKernel()->OUT;

# backward compatibility
$GLOBALS['pagetitle'] = $GLOBALS['modheader'] = '';

Dragonfly::getKernel()->SESSION->init_info();

/**
 * Do security check with session/member related settings
 */
require_once(CORE_PATH.'classes/security.php');
Security::init();
if (!is_admin()) {
	Security::check();
}

# Function which removes \015\012 which causes linebreaks in SMTP email
function removecrlf($str) {
	return \Poodle\Mail::removeCRLF($str);
}

function send_mail(&$mailer_message, $message, $html=0, $subject='',
                   $to='', $to_name='', $from='', $from_name='')
{
	return \Dragonfly\Email::send($mailer_message, $subject, $message, $to, $to_name, $from, $from_name);
}

/**
 * Returns if the emailaddress is valid or not
 */
function is_email($email)
{
	try {
		\Dragonfly\Net\Validate::emailaddress($email, 1);
	} catch (Exception $e) {
		return false;
	}
	return true;
}

/**
 * Returns admin name if the user is an administrator, otherwise false
 */
function is_admin() {
	return empty($_SESSION['DF_VISITOR']) ? false : Dragonfly::getKernel()->IDENTITY->isAdmin();
}
function can_admin($module='super') {
	return empty($_SESSION['DF_VISITOR']) ? false : $_SESSION['DF_VISITOR']->admin->canAdmin($module);
}
/**
 * Returns member ID if the user is an registered member, otherwise false
 */
function is_user() {
	return Dragonfly::getKernel()->IDENTITY->isMember();
}
/**
 * Returns groupname if visitor is in the group, otherwise false
 */
function in_group($id) {
	return Dragonfly::getKernel()->IDENTITY->inGroup($id);
}
/**
 * See includes/classes/cpg_member -> getmemdata() for details
 */
function getusrdata($user, $data='*') {
	return $GLOBALS['CLASS']['member']->getmemdata($user, $data);
}

function is_active($module) {
	return Dragonfly\Modules::isActive($module);
}

function strip_javascript($text, $events=true) {
	$patterns[] = '/<\s*script[^>]*?>.*?<\/\s*script\s*>/i';
	$patterns[] = '/href\s*=\s*\'\s*javascript.*\'/i';
	$patterns[] = '/href\s*=\s*"\s*javascript.*"/i';
	if ($events) {
		$events = 'blur|click|dblclick|focus|load|unload|select|change'
		.'|submit|abort|error|reset|dragdrop|keydown|keypress|keyup'
		.'|mouseout|mouseover|mousedown|mousemove|mouseup|move|resize';
		$patterns[] = '/on('.$events.')\s*=\s*\'.*?\'/i';
		$patterns[] = '/on('.$events.')\s*=\s*".*?"/i';
	}
	return preg_replace($patterns,'',$text);
}

function htmlprepare($str, $nl2br=false, $spchar=ENT_QUOTES, $nohtml=false) {
	if ($nohtml) { $str = strip_tags($str, $nohtml); } # $nohtml : <a><br><b><i><img><li><ol><p><strong><u><ul>
	$str = htmlspecialchars($str,$spchar); # convert only everyday web programming chars
	if ($nl2br) { $str = nl2br($str); } # \r\n, \n\r, \n and \r to <br />
	return trim($str);
}
function br2nl($str) {
	return preg_replace("#<br[^>]*>\R?#i", "\n", $str);
}
function htmlunprepare($str, $br2nl=false) {
	return htmlspecialchars_decode($br2nl?br2nl($str):$str, ENT_QUOTES);
}
function html2bb($text) {
	return \Dragonfly\BBCode::fromHTML($text);
}

function Fix_Quotes($str, $nohtml=false, $spchar=false) {
	global $db;
	if ($nohtml) { $str = strip_tags($str); }
	if ($spchar) { $str = htmlspecialchars($str,ENT_NOQUOTES,'UTF-8'); }
	return $db->escape_string(trim($str));
}

# Text Filtering
function check_words(&$Message)
{
	$CFG = \Dragonfly::getKernel()->CFG->global;
	if (!empty($CFG->CensorList)) {
		switch ($CFG->CensorMode)
		{
		case 1:
			return preg_replace('#('.$CFG->CensorList.')([^A-Z0-9])#i', $CFG->CensorReplace.'$2', $Message);
		case 2:
			return preg_replace('#(^|[^[:alnum:]])('.$CFG->CensorList.')#i', '$1'.$CFG->CensorReplace, $Message);
		case 3:
			return preg_replace('#'.$CFG->CensorList.'#i', $CFG->CensorReplace, $Message);
		}
	}
	return $Message;
}

# Deprecated Functions
include(CORE_PATH.'deprecated.php');
if (CPG_DEBUG) { include(CORE_PATH.'removed.php'); }

# stops the page creation and shows an error page
function cpg_error($message, $title='ERROR', $redirect=false)
{
	$message = str_replace(BASEDIR, '', (string) $message);
	$GLOBALS['DF']->setState(DF::BOOT_ERROR);
	$K = \Dragonfly::getKernel();

	//if ($redirect) { URL::refresh($redirect); }
	if ($title == 301 || ($title >= 400 && $title <= 503) || ($title >= 800 && $title <= 803)) {
		$code = $title;
		if ($code >= 800) { $code = 403; }
		\Poodle\HTTP\Status::set($code);
		$K->L10N->load('errors');
		$message = $K->L10N['_SECURITY_MSG'][$title].'<br />'.$message;
		$title = $K->L10N['_SECURITY_STATUS'][$title];
	}

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH']) {
		echo $message;
	} else {
		if ($K->OUT) {
			global $showblocks, $home, $Module;
			if (is_object($Module)) {
				$Module->sides = \Dragonfly\Blocks::NONE;
			}
			$home = $showblocks = \Dragonfly\Blocks::$showblocks = 0;
			\Dragonfly\Page::title($title, false);
			\Dragonfly\Page::tag('meta name="robots" content="noindex,follow"');
			\Dragonfly\Page::tag('meta name="robots" content="noarchive"');
			if (!defined('HEADER_OPEN') && !defined('INSTALL')) { require_once(BASEDIR.'header.php'); }
			echo '<div class="border">';
			echo '<center>'.$message.'<br /><br />';
			$link = ($redirect) ? '<a href="'.$redirect.'">'._GO.'</a>' : '[ <a href="'.\Dragonfly::$URI_INDEX.'">'._HOME.'</a> ] '._GOBACK.'</center>';
			echo $link;
			echo '</div>';
			if (is_admin() && $K->CFG->debug->session && !empty($_SESSION['CPG_SESS'])) {
				echo '<div class="table1">';
				echo '<pre>'.print_r($_SESSION['CPG_SESS'], true).'</pre>';
				echo '</div>';
			}
			require_once(BASEDIR.'footer.php');
		} else {
			require_once(BASEDIR.'includes/cpg_page.php');
			$errorpage = '';
			if (!defined('INSTALL')) $errorpage = cpg_header($title);
			$errorpage .= "<center><strong>{$message}</strong></center><br />";
			if ($K->DEBUGGER->showDetails()) {
				$errorpage .= '<div style="text-align:left"';
				if (defined('INSTALL') || (isset($K->CFG) && $K->CFG->debug->database)) {
					$errorpage .= $K->DEBUGGER->get_report('sql');
				}
				$errorpage .= $K->DEBUGGER->get_report('php');
				if (is_admin() && $K->CFG->debug->session && !empty($_SESSION['CPG_SESS'])) {
					$errorpage .= '<br /><strong>Session debug:</strong><pre>'.print_r($_SESSION['CPG_SESS'], true).'</pre>';
				}
				$errorpage .= '</div>';
			}
			if (!defined('INSTALL')) $errorpage .= cpg_footer();
			echo $errorpage;
		}
	}
	if (isset($K->SESSION)) { $K->SESSION->write_close(); }
	exit;
}

if (preg_match('#(<|%3C)(script|iframe|object)#i',$_SERVER['QUERY_STRING'])) {
	cpg_error(sprintf(_ERROR_BAD_CHAR, ''), _SEC_ERROR);
}
