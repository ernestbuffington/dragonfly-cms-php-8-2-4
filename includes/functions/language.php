<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/functions/language.php,v $
  $Revision: 9.17 $
  $Author: nanocaiordo $
  $Date: 2007/04/07 13:18:08 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$browserlang = array(
	'af' => 'afrikaans', // ISO-8859-1
	'sq' => 'albanian',  // ISO-8859-1
	'ar' => 'arabic',	// 1256
	'ar-dz' => 'arabic', // algeria
	'ar-bh' => 'arabic', // bahrain
	'ar-eg' => 'arabic', // egypt
	'ar-iq' => 'arabic', // iraq
	'ar-jo' => 'arabic', // jordan
	'ar-kw' => 'arabic', // kuwait
	'ar-lb' => 'arabic', // lebanon
	'ar-ly' => 'arabic', // libya
	'ar-ma' => 'arabic', // morocco
	'ar-om' => 'arabic', // oman
	'ar-qa' => 'arabic', // qatar
	'ar-sa' => 'arabic', // Saudi Arabia
	'ar-sy' => 'arabic', // syria
	'ar-tn' => 'arabic', // tunisia
	'ar-ae' => 'arabic', // U.A.E
	'ar-ye' => 'arabic', // yemen
	'hy' => 'armenian',
	'ast' => 'asturian',
	'eu' => 'basque',
	'be' => 'belarusian',
	'bs' => 'bosanski',//bosnian -bosanski is nuke lang name
	'bg' => 'bulgarian',
	'ca' => 'catalan',
	'zh' => 'chinese',
	'zh-cn' => 'chinese', // China
	'zh-hk' => 'chinese', // Hong Kong
	'zh-sg' => 'chinese', // Singapore
	'zh-tw' => 'chinese', // Taiwan
	'hr' => 'croatian',   // 1250
	'cs' => 'czech',
	'da' => 'danish',   // ISO-8859-1
	'dcc' => 'desi',	// Deccan, India
	'nl' => 'dutch',	// ISO-8859-1
	'nl-be' => 'dutch', // Belgium
	'en' => 'english',
	'en-au' => 'english', // Australia
	'en-bz' => 'english', // Belize
	'en-ca' => 'english', // Canada
	'en-ie' => 'english', // Ireland
	'en-jm' => 'english', // Jamaica
	'en-nz' => 'english', // New Zealand
	'en-ph' => 'english', // Philippines
	'en-za' => 'english', // South Africa
	'en-tt' => 'english', // Trinidad
	'en-gb' => 'english', // United Kingdom
	'en-us' => 'english', // United States
	'en-zw' => 'english', // Zimbabwe
	'eo' => 'esperanto',
	'et' => 'estonian',
	'eu' => 'euraska',   // ISO-8859-1
	'fo' => 'faeroese',
	'fi' => 'finnish',   // ISO-8859-1
	'fr' => 'french',	// ISO-8859-1
	'fr-be' => 'french', // Belgium
	'fr-ca' => 'french', // Canada
	'fr-fr' => 'french', // France
	'fr-lu' => 'french', // Luxembourg
	'fr-mc' => 'french', // Monaco
	'fr-ch' => 'french', // Switzerland
	'gl' => 'galego', //galician- galego is nuke lang name // ISO-8859-1
	'ka' => 'georgian',
	'de' => 'german',	// ISO-8859-1
	'de-at' => 'german', // Austria
	'de-de' => 'german', // Germany
	'de-li' => 'german', // Liechtenstein
	'de-lu' => 'german', // Luxembourg
	'de-ch' => 'german', // Switzerland
	'el' => 'greek',	  // ISO-8859-7
	'he' => 'hebrew',
	'hu' => 'hungarian',  // ISO-8859-2
	'is' => 'icelandic',  // ISO-8859-1
	'id' => 'indonesian', // ISO-8859-1
	'ga' => 'irish',
	'it' => 'italian',	// ISO-8859-1
	'it-ch' => 'italian', // Switzerland
	'ja' => 'japanese',
	'ko' => 'korean',
	'ko-kp' => 'korean', // North Korea
	'ko-kr' => 'korean', // South Korea
	'ku' => 'kurdish',	  // 1254
	'lv' => 'latvian',
	'lt' => 'lithuanian',   // 1257
	'mk' => 'macedonian',   // 1251
	'ms' => 'malayu',
	'no' => 'norwegian',	// ISO-8859-1
	'nb' => 'norwegian',	// bokmal
	'nn' => 'norwegian',	// nynorsk
	'pl' => 'polish',	   // ISO-8859-2
	'pt' => 'portuguese',   // 28591, Latin-I, iso-8859-1
	'pt-br' => 'brazilian', // Brazil
	'ro' => 'romanian',	 // 28592, Central Europe, iso-8859-2
	'ru' => 'russian',	  // 1251 ANSI
	'gd' => 'scots gealic',
	'sr' => 'serbian',
	'sk' => 'slovak',	   // 1250 ANSI
	'sl' => 'slovenian',	// 28592, Central Europe, iso-8859-2
	'es' => 'spanish',	  // 28591, Latin-I, iso-8859-1
	'es-ar' => 'spanish',   // Argentina
	'es-bo' => 'spanish', // Bolivia
	'es-cl' => 'spanish', // Chile
	'es-co' => 'spanish', // Colombia
	'es-cr' => 'spanish', // Costa Rica
	'es-do' => 'spanish', // Dominican Republic
	'es-ec' => 'spanish', // Ecuador
	'es-sv' => 'spanish', // El Salvador
	'es-gt' => 'spanish', // Guatemala
	'es-hn' => 'spanish', // Honduras
	'es-mx' => 'spanish', // Mexico
	'es-ni' => 'spanish', // Nicaragua
	'es-pa' => 'spanish', // Panama
	'es-py' => 'spanish', // Paraguay
	'es-pe' => 'spanish', // Peru
	'es-pr' => 'spanish', // Puerto Rico
	'es-es' => 'castellano', // Spain
	'es-uy' => 'spanish', // Uruguay
	'es-ve' => 'spanish', // Venezuela
	'sv' => 'swedish',
	'sv-fi' => 'swedish',   // Finland
	'sw' => 'swahili',	  // Kenya and Tanzania
	'th' => 'thai',		 // 874
	'tr' => 'turkish',	  // 1254
	'ug' => 'uighur',	   // ISO-8859-1, 28591 Turkish, Uzbek, China
	'uk' => 'ukrainian',
	'vi' => 'vietnamese',
	'cy' => 'welsh',
	'xh' => 'xhosa',
	'yi' => 'yiddish',
	'zu' => 'zulu'
);

$currentlang = $MAIN_CFG['global']['language'];
if ($MAIN_CFG['global']['multilingual']) {
	if (isset($_GET['newlang']) && ereg("^([a-zA-Z0-9_\-]+)$", $_GET['newlang'])) {
		$currentlang = $_GET['newlang'];
	} elseif (isset($_COOKIE['lang']) && ereg("^([a-zA-Z0-9_\-]+)$", $_COOKIE['lang']) &&
		(file_exists(BASEDIR."language/$_COOKIE[lang]/main.php"))) {
		$currentlang = $_COOKIE['lang'];
	} elseif (is_user()) {
		$currentlang = $userinfo['user_lang'];
	} else {
		detect_lang($currentlang);
	}
	if (!file_exists(BASEDIR."language/$currentlang/main.php")) {
		$currentlang = $MAIN_CFG['global']['language'];
	}
	setcookie('lang',$currentlang,gmtime()+31536000, $MAIN_CFG['cookie']['path']);
}
/*
else if () {
	$lng = split('.', $_SERVER['SERVER_NAME']);
	if (isset($browserlang[$lng[0]])) {
		$currentlang = $language = $browserlang[$lng[0]];
	}
	unset($lng);
}
*/
if (file_exists(BASEDIR."language/$currentlang/main.php")) {
	require_once(BASEDIR."language/$currentlang/main.php");
} else {
	require_once(BASEDIR.'language/english/main.php');
}
if (!setlocale(LC_TIME, 'en_US.UTF-8')) {
if (!setlocale(LC_TIME, 'en_US.utf8')) {
	if (!setlocale(LC_TIME, 'en_US')) {
		if (!setlocale(LC_TIME, 'english') && PHPVERS >= 43) {
			setlocale(LC_TIME, array('en', 'eng', 'ISO-8859-1'));
		}
	}
}}
define('_LANGCODE', array_search($currentlang, $browserlang));
unset($browserlang);

/*
$char_accept= 0;
$accepted_charsets = explode(',', strtolower(getenv('HTTP_ACCEPT_CHARSET')));

foreach ($accepted_charsets as $browser_charset) {
	echo $browser_charset.'== '._CHARSET.' '."\t";
	if (($browser_charset == _CHARSET)||($browser_charset == '*')){
	$char_accept= true;
	echo $browser_charset.'== '._CHARSET.' '."\t";
	 }
}
if(!strstr($_SERVER["HTTP_USER_AGENT"],"MSIE")) {
// IE doesn't send HTTP_ACCEPT_CHARSET
	$char_accept = 0;
	$accepted_charsets = explode(',', strtolower(getenv('HTTP_ACCEPT_CHARSET')));
	foreach ($accepted_charsets as $browser_charset) {
		if ((strtolower($browser_charset) == strtolower(_CHARSET))||($browser_charset == '*')){
			$char_accept = 1;
			break;
		}
	}

} //end if not IE
*/

function get_langcode($thislang){
	return _LANGCODE;
}

function detect_lang(&$language) {
	global $browserlang;
	$accepted_languages = explode(',', strtolower(getenv('HTTP_ACCEPT_LANGUAGE')));
	foreach ($accepted_languages as $browser_lang) {
		if (isset($browser_lang[2]) && $browser_lang[2] == '-') {
			$langcode = substr($browser_lang, 0, 5);
		} else {
			$langcode = substr($browser_lang, 0, 2);
		}
		$tmplang = $browserlang[$langcode];
		if (file_exists(BASEDIR."language/$tmplang/main.php")) {
			$language = $tmplang;
			break;
		}
	}
}

function get_lang($module, $filename=false, $linenum=false, $once=true) {
	static $loaded;
	$file = strtolower($module);
	if (isset($loaded[$file])) { return true; }
	global $currentlang, $MAIN_CFG, $LNG;
	$language = $MAIN_CFG['global']['language'];
	if (file_exists(BASEDIR."language/$currentlang/$file.php")) {
		$path = BASEDIR."language/$currentlang/$file.php";
	} else if (file_exists(BASEDIR."modules/$file/l10n/id.php")) {
		$id = include(BASEDIR."modules/$file/l10n/id.php");
		if (isset($loaded[$id])) {
			$loaded[$file] = 1;
			return true;
		}
		if (file_exists(BASEDIR."modules/$file/l10n/$currentlang.php")) {
			$path = BASEDIR."modules/$file/l10n/$currentlang.php";
		} else if (file_exists(BASEDIR."modules/$file/l10n/$language.php")) {
			$path = BASEDIR."modules/$file/l10n/$language.php";
		} else if (file_exists(BASEDIR."modules/$file/l10n/english.php")) {
			$path = BASEDIR."modules/$file/l10n/english.php";
		}
	} else if (file_exists(BASEDIR."language/$language/$file.php")) {
		$path = BASEDIR."language/$language/$file.php";
	} else if (file_exists(BASEDIR."language/english/$file.php")) {
		$path = BASEDIR."language/english/$file.php";
	} else {
		if ($filename != -1) {
		  $err = ($module=='') ? 'get_lang called without specifying which module' : 'There is no language file for module '.$module;
		  if ($filename) {
			global $cpgdebugger;
			$cpgdebugger->handler(E_USER_NOTICE, $err, $filename, $linenum);
		  } else {
			trigger_error($err, E_USER_NOTICE);
		  }
		}
		return false;
	}
	$loaded[$file] = 1;
	($once) ? require_once($path) : require($path);
	return true;
}

function lang_selectbox($current, $fieldname='alanguage', $all=true, $return_list=false) {
	static $languages;
	if (!isset($languages)) {
		$handle = opendir('language');
		while ($file = readdir($handle)) {
			if (file_exists(BASEDIR."language/$file/main.php")) { $languages[] = $file; }
			elseif (ereg('lang-(.*).php$', $file, $matches)) { $languages[] = $matches[1]; }
		}
		closedir($handle);
		sort($languages);
	}
	if ($return_list) return $languages;
	$content = '<select name="'.$fieldname.'" id="'.$fieldname.'">';
	if ($all) {
		$content .= '<option value=""'.(($current == '') ? ' selected="selected"' : '').'>'._ALL."</option>\n";
	}
	for ($i=0; $i < sizeof($languages); $i++) {
		if ($languages[$i] != '') {
			$content .= '<option value="'.$languages[$i].'"'.(($current == $languages[$i]) ? ' selected="selected"' : '').'>'.ucfirst($languages[$i])."</option>\n";
		}
	}
	return $content.'</select>';
}
