<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('DF_MODE_INSTALL') || !DF_MODE_INSTALL) exit('Installer mode is not active. Set includes/config.php define(\'DF_MODE_INSTALL\', true);');

$DF->setState(DF::BOOT_INSTALL);

$go = $_POST->uint('step') ? : 0;
if ($go <= 2) define('DF_MODE_NOSQL', 1);
if ($go <= 3) define('DF_MODE_NOCFG', 1);

define('INSTALL', true);
define('SKIP_GZIP', true);
require_once(CORE_PATH.'cmsinit.inc');
require_once(CORE_PATH.'cpg_page.php');
ob_implicit_flush();
\Dragonfly::ob_clean();


$images = array();
for ($i=0; $i<6; ++$i) {
	$images[$i] = (($go == $i) ? '➔' : (($go > $i) ? '✔' : '☐'));
}

if ($go < 4 && isset($_COOKIE['installtest'])) { setcookie('installtest','',-1); }

$config_file = CORE_PATH.'config.php';
if (is_file($config_file)) {
	$dbms = \Dragonfly::getKernel()->dbms;
	$db = new \Dragonfly\SQL(
		$dbms['adapter'],
		array(
			'host'     => $dbms['master']['host'],
			'username' => $dbms['master']['username'],
			'password' => $dbms['master']['password'],
			'database' => $dbms['master']['database'],
			'charset'  => $dbms['master']['charset']
		),
		$dbms['tbl_prefix']
	);
} else {
	define('CPG_DEBUG', false);
}

# Load the language
$currentlang = 'en';
$instlang = array();
$tmplang = array();
if (isset($_GET['newlang']) && preg_match('#^[a-z]+$#', $_GET['newlang'])) {
	setcookie('installlang', $_GET['newlang']);
	$tmplang[9] = $_GET['newlang'];
}
if (isset($_COOKIE['installlang']) && preg_match('#^[a-z]+$#', $_COOKIE['installlang'])) {
	$tmplang[8] = $_COOKIE['installlang'];
}
if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$tmplang[1] = 'en';
} else {
	# split and sort accepted languages by rank (q)
	if (preg_match_all('#(?:^|,)([a-z\-]+)(?:;\s*q=([0-9.]+))?#', mb_strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), $languages, PREG_SET_ORDER))
	{
		foreach ($languages as $lang) {
			if (!isset($lang[2])) { $lang[2] = 1; }
			if (2 > $lang[2]) {
				$tmplang[sprintf('%f%d', $lang[2], rand(0,9999))] = $lang[1];
			}
			else { $tmplang[$lang[2]] = $lang[1]; }
		}
		krsort($tmplang);
	}
}
foreach ($tmplang as $var) {
	if (is_file(BASEDIR."install/language/{$var}.php")) {
		$currentlang = $var;
		break;
	}
}
$tmplang = $currentlang;
require(BASEDIR."install/language/{$currentlang}.php");

$MAIN_CFG['global'] = array('language' => $currentlang, 'multilingual' => '0', 'GoogleTap' => '0', 'top' => '', 'adminmail' => '');

if (is_file($config_file)) {
	$current_version = check_inst($go < 3);
}

function check_inst($die=false)
{
	global $db, $prefix, $dbname, $instlang;
	$version = 0;
	if (isset($db->TBL->config_custom)) {
		$result = $db->uFetchRow("SELECT MAX(cfg_value) FROM {$db->TBL->config_custom} WHERE cfg_name='global' AND cfg_field IN ('db_version','Version_Num')");
		if ($result) {
			$version = $result[0];
		}
		if ($die && $version == Dragonfly::DB_VERSION) {
			inst_header();
			echo $instlang['s1_already'];
			footer();
			exit;
		}
	}
	return $version;
}

function inst_header()
{
	global $images, $instlang, $go, $tmplang;
	echo cpg_header($instlang['installer']).
(DF_MODE_DEVELOPER ? '<h2 style="color:red">Developer Mode</h2>' : '').'
<form action="?install" method="post">
<table width="100%" height="350px">
<tr><td style="min-width:200px; vertical-align:baseline; text-align:left">
<b>'.$instlang['s_progress'].'</b><br />
'.$images[0].' '.$instlang['s_license'].'<br />
'.$images[1].' '.$instlang['s_server'].'<br />
'.$images[2].' '.$instlang['s_setconfig'].'<br />
'.$images[3].' '.$instlang['s_builddb'].'<br />
'.$images[4].' '.$instlang['s_gather'].'<br />
'.$images[5].' '.$instlang['s_create'].'<br />';
	if (!$go) {
		echo '<br /><br />
<select name="newlanguage" onchange="top.location.href=\'?install&amp;newlang=\'+this.options[this.selectedIndex].value" class="formfield">';
		$content = '';
		$handle = opendir(BASEDIR.'install/language');
		while ($file = readdir($handle)) {
			if (preg_match('#(.*).php#', $file, $matches)) {
				$languageslist[] = $matches[1];
			}
		}
		closedir($handle);
		sort($languageslist);
		for ($i=0; $i < sizeof($languageslist); $i++) {
			if ($languageslist[$i]!='') {
				$content .= '<option value="'.$languageslist[$i].'"';
				if ($languageslist[$i]==$tmplang) $content .= ' selected="selected"';
				$content .= '>'.ucfirst(\Dragonfly\L10N\V9::$browserlang[$languageslist[$i]])."</option>\n";
			}
		}
		echo $content.'</select>';
	}
	echo '
</td><td valign="top" align="center">';
	flush();

}

function footer()
{
	echo '</td></tr></table></form></div></body></html>';
}

if (!$go) {
	inst_header();
	echo '<h2>'.$instlang['welcome'].'</h2>
<p style="font-size:12px">'.$instlang['info'].'</p>
<p style="font-size:12px">'.$instlang['click'].'</p>';
	if (function_exists('readgzfile')) {
		echo '<pre id="license">';
		readgzfile('install/GPL.gz');
		echo '</pre>';
	} else {
		echo '<h2 align="center">'.$instlang['no_zlib'].'</h2>';
	}
	echo '<p>
<input type="hidden" name="step" value="'.(!empty($current_version) ? '3' : '1').'" />
<input type="submit" value="'.$instlang['agree'].'" class="formfield" /></p>';
}
elseif (isset($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 0 && !stripos($_SERVER['HTTP_REFERER'], '://'.$_SERVER['HTTP_HOST'])) {
	echo 'Posting from another server is not allowed';
}
elseif (is_file(BASEDIR."install/step{$go}.php")) {
	include(BASEDIR."install/step{$go}.php");
}
else {
	echo '<h1>'.sprintf(_ERROR_NO_EXIST, $go).'</h1>';
}

footer();
