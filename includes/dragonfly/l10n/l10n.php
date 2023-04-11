<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	locale examples: en, en-US, nl, nl-NL

	DB table l10n_translate fields are named 'v_[rfc1766]' where [rfc1766] is
	lowercase and '_' instead of '-', for example: v_en_us.
	The 'v_' is there to prevent SQL language issues,
	as for example the language 'is' is a preserved word (IS NULL).

	http://www.ietf.org/rfc/rfc1766.txt
	http://www.unc.edu/~rowlett/units/codes/country.htm
	http://www.w3.org/WAI/ER/IG/ert/iso639.htm
	http://www.loc.gov/standards/iso639-2/
	http://www.unicode.org/onlinedat/countries.html
	ftp://ftp.ilog.fr/pub/Users/haible/utf8/ISO_3166
	ftp://ftp.ilog.fr/pub/Users/haible/utf8/Unicode-HOWTO.html
*/

namespace Dragonfly;

class L10N implements \ArrayAccess
{
	protected
		$id = null,
		$lng = null;

	protected static
		$ua_lng = null,
		$data = array();

	const
		REGEX = '#^([a-z]{2,3})(-[a-z]{2})?$#D';

	function __construct()
	{
		if (is_null(self::$ua_lng)) {
			$K = \Dragonfly::getKernel();
			self::$ua_lng = false;
			$lngs = array();

			if (isset($_GET['newlang']) && preg_match(self::REGEX, $_GET['newlang'])) {
				$lngs[8] = $_GET['newlang'];
			}

			# get user session language
			if (!empty($_SESSION['Dragonfly']['lang'])) {
				$lngs[7] = $_SESSION['Dragonfly']['lang'];
			}

			if (!empty($K->IDENTITY) && $K->IDENTITY->isMember()) {
				$lngs[6] = $K->IDENTITY->language;
			}
/*
			if (isset($_COOKIE['lang']) && preg_match(self::REGEX, $_COOKIE['lang']))
			{
				$lngs[5] = $_COOKIE['lang'];
			}
*/
			# get user agent languages
			if (!empty(\Dragonfly::$UA_LANGUAGES)) {
				# split and sort accepted languages by rank (q)
				if (preg_match_all('#(?:^|,)([a-z\-]+)(?:;\s*q=([0-9.]+))?#', \Dragonfly::$UA_LANGUAGES, $accepted_languages, PREG_SET_ORDER))
				{
					foreach ($accepted_languages as $lang) {
						if (!isset($lang[2])) { $lang[2] = 1; }
						if (2 > $lang[2]) {
							$lngs[sprintf('%f%d', $lang[2], rand(0,9999))] = $lang[1];
						}
						else { $lngs[$lang[2]] = $lang[1]; }
					}
				}
			}

			# get default language
			if (is_object($K) && is_object($K->CFG)) {
				$lngs[0] = $K->CFG->global->language;
			}

			# check acceptance
			krsort($lngs);
			foreach ($lngs as $lng) {
				if (self::setGlobalLanguage($lng)) {
					break;
				}
			}
			if (!empty($lngs[9]) && $lngs[9] !== self::$ua_lng) {
//				URI::redirect(str_replace("/{$lngs[9]}/", '/', $_SERVER['REQUEST_URI']));
			}
		}

		$this->__set('lng', self::$ua_lng ?: 'en');
	}

	function __get($key)
	{
		if ('id' === $key) {
			if (!is_int($this->id)) {
				$this->id = 0;
				$SQL = \Dragonfly::getKernel()->SQL;
				if ($SQL && isset($SQL->TBL->l10n)
				 && $r = $SQL->uFetchRow("SELECT l10n_id FROM {$SQL->TBL->l10n} WHERE l10n_rfc1766 = {$SQL->quote($this->lng)}"))
				{
					$this->id = (int)$r[0];
				}
			}
			return $this->id;
		}
		if ('lng' === $key) { return $this->lng; }
		if ('array' === $key) { return $this->getArrayCopy(); }
		if ('multilingual' === $key) { return 1 < count(self::active()); }
		return $this->get($key);
	}

	function __set($key, $value)
	{
		if ('lng' === $key && preg_match(self::REGEX, $value)) {
			$this->id  = null;
			$this->lng = $value;
			$this->initCore();
		} else if (isset(self::$data[$this->lng][$key])) {
			self::$data[$this->lng][$key] = $value;
		}
	}

	function __toString() { return $this->lng; }

	public function getArrayCopy() { return self::$data[$this->lng]; }

	public function init() {}

	protected function initCore()
	{
		if (!isset(self::$data[$this->lng])) {
			self::$data[$this->lng] = array();
			$this->load('dragonfly_kernel');
			if (defined('ADMIN_PAGES')) {
				$this->load('dragonfly_admin');
			}
		}
	}

	public static function setGlobalLanguage($lng)
	{
		if (!self::active($lng) && 3 < strlen($lng)) {
			$lng = substr($lng, 0, -3);
		}
		if (!self::active($lng)) {
			return false;
		}

		self::$ua_lng = $lng;

		if (!empty($_SESSION)) {
			if (isset($_SESSION['Dragonfly']['lang']) && self::$ua_lng !== $_SESSION['Dragonfly']['lang']) {
				unset($_SESSION['L10N']);
			}
			$_SESSION['Dragonfly']['lang'] = self::$ua_lng;
		}

//		setcookie('lang', $currentlang, time()+31536000, self::$ua_lng);

		return true;
	}

	public static function active($lng=null)
	{
		$K = \Dragonfly::getKernel();
		static $languages = array();
		if (!$K || !$K->SQL || !$K->SQL->TBL || !isset($K->SQL->TBL->l10n)) {
			return !!self::getIniFile($lng);
		}
		if (!$languages && (!$K->CACHE || !($languages = $K->CACHE->get(__CLASS__ . '/active')))) {
			$result = $K->SQL->query("SELECT l10n_rfc1766 FROM {$K->SQL->TBL->l10n} WHERE l10n_active > 0");
			while ($row = $result->fetch_row()) {
				if (self::getIniFile($row[0])) {
					$languages[$row[0]] = 1;
				}
			}
			$result->free();
			if (!$languages) { $languages = array('en' => 1); }
			if ($K->CACHE) { $K->CACHE->set(__CLASS__ . '/active', $languages); }
		}
		return is_null($lng) ? $languages : isset($languages[$lng]);
	}

	public static function getIniFile($lng)
	{
		$file = "includes/l10n/{$lng}/l10n.ini";
		return is_readable($file) ? $file : false;
	}

	public function getActiveList() { return $this->getList(1); }

	public function getInactiveList() { return $this->getList(0); }

	protected function getList($active)
	{
		static $list = array();
		$K = \Dragonfly::getKernel();
		$cache_key = 'l10n_'.$this->lng.'_'.($active?'':'in').'active';
		if (!isset($list[$cache_key])) { $list[$cache_key] = array(); }
//		if ($K && $K->SQL && (!$K->CACHE || !($list[$cache_key] = $K->CACHE->get($cache_key)))) {
		if ($K && $K->SQL && empty($list[$cache_key])) {
			$result = $K->SQL->query("SELECT l10n_id, l10n_rfc1766, t.* FROM {$K->SQL->TBL->l10n}
			LEFT JOIN {$K->SQL->TBL->l10n_translate} t ON (msg_id = l10n_rfc1766)
			WHERE l10n_active".($active?'>0':'<1')."
			ORDER BY v_".str_replace('-','_',$this->lng));
			while ($row = $result->fetch_assoc()) {
				$list[$cache_key][] = array(
					'id'    => $row['l10n_id'],
					'label' => $row['v_'.str_replace('-','_',$this->lng)] . " ({$row['l10n_rfc1766']})",
					'title' => $row['v_'.str_replace('-','_',$row['l10n_rfc1766'])],
					'value' => $row['l10n_rfc1766']
				);
			}
//			if ($K->CACHE) { $K->CACHE->set($cache_key, $list[$cache_key]); }
		}
		return $list[$cache_key];
	}

	/**
	 * $name will be looked up as:
	 *     poodle_libname
	 *         includes/poodle/{libname}/l10n/{lng}.php
	 *         includes/l10n/{lng}/poodle/{libname}.php
	 *     dragonfly_libname
	 *         includes/dragonfly/{libname}/l10n/{lng}.php
	 *         includes/l10n/{lng}/dragonfly/{libname}.php
	 *     modulename
	 *         modules/{modulename}/l10n/{lng}.php
	 *         includes/l10n/{lng}/{modulename}.php
	 *
	 * if {lng} has a value like 'nl-be' it will also look in: 'nl' and 'en'
	 */
	protected static $loaded_files = array();
	public function load($name, $skip_error=false)
	{
		if (!$name) {
			if (!$skip_error) { \Poodle\Debugger::trigger(sprintf($this->get('_NO_PARAM'), __CLASS__ . '::load()', 'filename'), __FILE__, E_USER_NOTICE); }
			return false;
		}

		$lname = strtolower($name);
		if ('exception' === $lname) { return false; }

		$lng = $this->lng;

		$file = "{$lng}/{$lname}";
		if (in_array($file,self::$loaded_files)) {
//			$bt = debug_backtrace(0);
//			trigger_error("L10N::load($lname) again by {$bt[0]['file']}#{$bt[0]['line']}");
			return true;
		}
		self::$loaded_files[] = $file;

		$files = array();
		preg_match('#^(?:(dragonfly|poodle)[/_])?(.+)$#D',$lname,$m);
		while ($lng) {
			if ($m[1]) {
				$files[] = "{$m[1]}/{$m[2]}/l10n/{$lng}.php";
				$files[] = "includes/l10n/{$lng}/{$m[1]}/{$m[2]}.php";
			} else {
				$files[] = "modules/{$m[2]}/l10n/{$lng}.php";
				$files[] = "modules/{$name}/l10n/{$lng}.php";
				$files[] = "includes/l10n/{$lng}/{$m[2]}.php";
			}
			if (3 < strlen($lng)) { $lng = substr($lng, 0, -3); }
			else if ('en' !== $lng) { $lng = 'en'; }
			else $lng = null;
		}

		$file = false;
		foreach ($files as $file) {
			if ($file = \Dragonfly::getFile($file)) {
				include_once $file;
				break;
			}
		}

		// Phar
		if (!$file && !$m[1]) {
			$lng = $this->lng;
			while ($lng) {
				$path = \Dragonfly::getModulePath($name)."l10n/{$lng}.php";
				if (is_file($path)) {
					include_once $path;
					$file = true;
					break;
				}
				if (3 < strlen($lng)) { $lng = substr($lng, 0, -3); }
				else if ('en' !== $lng) { $lng = 'en'; }
				else $lng = null;
			}
		}

		// v9
		if (!$file) {
			if ($lng = \Dragonfly\L10N\V9::getV9Language($this->lng)) {
				if (($file = \Dragonfly::getFile("language/{$lng}/{$lname}.php"))
				 || ($file = \Dragonfly::getFile("modules/{$name}/l10n/{$lng}.php"))) {
					include_once $file;
				}
			}
			if (!$file) {
				if (($file = \Dragonfly::getFile("language/english/{$lname}.php"))
				 || ($file = \Dragonfly::getFile("modules/{$name}/l10n/english.php"))) {
					include_once $file;
				}
			}
		}

		if (!$file) {
			if (!$skip_error) { \Poodle\Debugger::trigger(sprintf($this->get('_NO_L10NF'), $name), __FILE__, E_USER_NOTICE); }
			return false;
		}

		if (empty($LNG)) {
			return false;
		}

		self::$data[$this->lng] = array_merge(self::$data[$this->lng], $LNG);
		return true;
	}

	public function get($var, $var2=null)
	{
		if (!strlen($var)) { return ''; }
		$LNG = &self::$data[$this->lng];
		$cf = false;
		$txt = $var;
		if (!isset($LNG[$txt])) {
			if (!$var2) {
				list($txt,$cf) = self::cfirst($txt);
			}
			if (!isset($LNG[$txt])) {
				// Old language system uses constants
				if (defined($var)) { return constant($var); }
				\Poodle\Debugger::trigger(sprintf($LNG['_NO_L10NV'], $var), __FILE__, E_USER_NOTICE);
				$LNG[$var] = $var;
				return $var;
			}
		}
		$txt = &$LNG[$txt];
		if (isset($var2)) {
			if (!isset($txt[$var2])) {
				list($var2,$cf) = self::cfirst($var2);
				if (!isset($txt[$var2])) {
					\Poodle\Debugger::trigger(sprintf($LNG['_NO_L10NV'], $var.']['.$var2), __FILE__, E_USER_NOTICE);
					return ($cf ? $cf($var2) : $var2);
				}
			}
			$txt = &$txt[$var2];
		}
		return $cf ? \Poodle\Unicode::$cf($txt) : $txt;
	}

	protected static function cfirst($str)
	{
		$i = ord($str);
		if ($i >= 0x41 && $i <= 0x5A)
			return array(lcfirst($str), 'ucfirst');
		if ($i >= 0x61 && $i <= 0x7A)
			return array(ucfirst($str), 'lcfirst');
		return array($str, false);
	}

	/*
	 * About Plural array's:
	 * If your language is not compatible with 2 options (default output: 1 comment, 0 comments, 10 comments)
	 * then add a callback function as:	'plural_cb'=>'function_name'
	 * where function_name($number) should return the value index, Russian example:
	 *
	 * $LNG['%d noteboook'] = array('%d тетрадь', '%d тетради', '%d тетрадей', 'plural_cb'=>'ru_plural_noteboook');
	 * function ru_plural_noteboook($n) { return $n%10==1 && $n%100!=11 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? 1 : 2; }
	 *
	 * Another example is 'sheep' which is in english 1 sheep, 2 sheep.
	 * In Dutch for example it is: 1 schaap, 2 schapen
	 * This is easily achieved as:
	 *     en: $LNG['%d sheep'] = '%d sheep';
	 *     nl: $LNG['%d sheep'] = array('%d schaap','%d schapen');
	 */
	public function nget($n, $var, $var2=null)
	{
		$str = $this->get($var, $var2);
		if (is_array($str)) {
			$i = (1 == $n ? 0 : 1);
			if (!empty($str['plural_cb']) && is_callable($str['plural_cb'])) {
				$i = $str['plural_cb']($n);
			}
			unset($str['plural_cb']);
			$str = $str[min(max(0,$i),count($str)-1)];
		}
		return $str;
	}

	public function plural($n, $var, $var2=null)
	{
		return sprintf($this->nget($n, $var, $var2), $n);
	}

	public function dbget($msg_id)
	{
		if (!strlen($msg_id)) {
			return '';
		}
		// Old language system uses constants
		if (defined($msg_id)) {
			return constant($msg_id);
		}
		if (64 < strlen($msg_id) || is_numeric($msg_id)) {
			return $msg_id;
		}
		$LNG = &self::$data[$this->lng];
		if (isset($LNG[$msg_id])) {
			return $LNG[$msg_id];
		}
		list($txt, $cf) = self::cfirst($msg_id);
		if (isset($LNG[$txt])) {
			return \Poodle\Unicode::$cf($txt);
		}
		$id = strtolower($txt);
		$SQL = \Poodle::getKernel()->SQL;
		$LNG[$id] = $msg_id;
		if (isset($SQL->TBL->l10n_translate)) {
			$msg = $SQL->uFetchRow('SELECT v_'.strtr($this->lng,'-','_').', v_en
				FROM '.$SQL->TBL->l10n_translate.' WHERE LOWER(msg_id)='.$SQL->quote($id));
			if ($msg) {
				$LNG[$id] = $msg[0] ?: $msg[1];
				if (empty($LNG[$id])) {
					\Poodle\Debugger::trigger(sprintf($LNG['_NO_L10NDB'], $msg_id, $this->lng), __FILE__, E_USER_NOTICE);
					$LNG[$id] = $msg_id;
				}
			} else {
				\Poodle\Debugger::trigger(sprintf($LNG['_NO_L10NDBC'], $msg_id), __FILE__, E_USER_NOTICE);
				$SQL->insert('l10n_translate', array(
					'msg_id' => $id,
					'v_en' => $LNG[$id]
				));
			}
		}
		return $LNG[$id];
	}

	# crop filesize to human readable format
	public function filesizeToHuman($size, $precision=2)
	{
		if (!is_int($size) && !is_float($size) && !ctype_digit($size)) { return null; }
		$size = max($size, 0);
		$i = $size ? floor(log($size, 1024)) : 0;
		if ($i > 0) { $size /= pow(1024, $i); }
		else { $precision = 0; }
		return sprintf($this->get('_FILESIZES', $i), $this->round($size, max(0, $precision)));
	}

	# language specific number format
	public function round($number, $precision=0)
	{
		if ($number instanceof \Poodle\Number) {
			return $number->format($precision, $this->get('_seperator', 0), $this->get('_seperator', 1));
		}
		return number_format(floatval($number), $precision, $this->get('_seperator', 0), $this->get('_seperator', 1));
	}

	/* Date-Time Methods */

	public static function ISO_d($time=false)  { return gmdate('Y-m-d', ($time?$time:time())); }
	public static function ISO_t($time=false)  { return gmdate('H:i:s', ($time?$time:time())); }
	public static function ISO_dt($time=false) { return gmdate('Y-m-d H:i:s', ($time?$time:time())); }

	public function date($format, $time=null, $timezone=null)
	{
		if (isset(self::$data[$this->lng]['_time']['formats'][$format])) {
			$format = self::$data[$this->lng]['_time']['formats'][$format];
		}
		$count  = 0;
		$format = str_replace(array('D', 'l', 'F', 'M'), array('_\Dw', '_\lw', '_\Fn', '_\Mn'), $format, $count);
		$time   = is_null($time) ? time() : $time;
		if (!$timezone && is_numeric($time) && 2147483647 >= $time) {
			$time = date($format, $time);
		} else {
			$time = new \Poodle\DateTime($time, $timezone ?: date_default_timezone_get());
			$time = $time->format($format);
		}
		return (0 === $count) ? $time : preg_replace_callback('#_([DlFM])(\d{1,2})#', array($this, 'date_cb'), $time);
	}
	protected function date_cb($params) { return self::$data[$this->lng]['_time'][$params[1]][(int)$params[2]]; }

	public function timeReadable($time, $format='%x', $show_0=false)
	{
		if ($time instanceof \DateTime) {
			$time = time() - $time->getTimestamp();
		}
		$rep  = array();
		$desc = array(
			'%y' => array(31536000, 'years'),
			'%m' => array(2628000, 'months'),
			'%w' => array(604800, 'weeks'),
			'%d' => array(86400, 'days'),
			'%h' => array(3600, 'hours'),
			'%i' => array(60, 'minutes'),
			'%s' => array(1, 'seconds')
		);
		$is_x = (false !== strpos($format,'%x'));
		foreach ($desc as $k => $s) {
			$val = '';
			if ($is_x || false !== strpos($format,$k)) {
				$i = floor($time/$s[0]);
				if ($show_0 || $i > 0) {
					$time -= ($i*$s[0]);
					$val = self::plural($i, '%d '.$s[1]);
					if ($is_x && $i > 0) {
						return str_replace('%x', $val, $format);
					}
				}
			}
			$rep[$k] = $val;
		}
		return ('%x' === $format) ? '' : trim(str_replace(array_keys($rep), array_values($rep), $format));
	}

	public function timezones(/*$zone*/)
	{
		# DateTimeZone::AFRICA||DateTimeZone::AMERICA||DateTimeZone::ANTARCTICA||DateTimeZone::ARCTIC||DateTimeZone::ASIA||DateTimeZone::ATLANTIC||DateTimeZone::AUSTRALIA||DateTimeZone||DateTimeZone::INDIAN||DateTimeZone::PACIFIC
		$tz = timezone_identifiers_list(); # DateTimeZone::listIdentifiers(2047); DateTimeZone::ALL
		sort($tz);
		$timezones = array('UTC'=>'UTC');
		foreach ($tz as $v)
		{
			if (preg_match('#^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)/(.*)$#',$v,$m))
			{
				$k = $m[1];
				$timezones[$k][''] = $k;
				$timezones[$k][$v] = str_replace('_', ' ', $m[2]);
			}
		}
		self::$data[$this->lng]['_timezones'] = $timezones;
		return self::$data[$this->lng]['_timezones'];
	}

	public function strftime($format, $time=null)
	{
		static $strftime2date = array(
			// Day
			'%a' => 'D',
			'%A' => 'l',
			'%d' => 'd',
			'%e' => 'j',
			'%j' => 'z',
			'%u' => 'N',
			'%w' => 'w',
			// Week
			'%U' => 'W',
			'%V' => 'W',
			'%W' => 'W',
			// Month
			'%b' => 'M',
			'%B' => 'F',
			'%h' => 'M',
			'%m' => 'm',
			// Year
			'%C' => 'o',
			'%g' => 'o',
			'%G' => 'o',
			'%y' => 'y',
			'%Y' => 'Y',
			// Time
			'%H' => 'H',
			'%k' => 'G',
			'%I' => 'h',
			'%l' => 'g',
			'%M' => 'i',
			'%p' => 'A',
			'%P' => 'a',
			'%r' => 'h:i:s A',
			'%R' => 'H:i',
			'%S' => 's',
			'%T' => 'H:i:s',
			'%X' => 'H:i:s',
			'%z' => 'O',
			'%Z' => 'e',
			// Time and Date Stamps
			'%c' => 'DATE_L',
			'%D' => 'm/d/y',
			'%F' => 'Y-m-d',
			'%s' => 'U',
			'%x' => 'm-d-Y',
			// Miscellaneous
			'%n' => "\n",
			'%t' => "\t",
			'%%' => '%',
		);
		$format = str_replace(array_keys($strftime2date), array_values($strftime2date), $format);
		# return the formatted string
		return $this->date($format, $time);
	}

	# ArrayAccess
	public function offsetExists($k)  { return array_key_exists($k, self::$data[$this->lng]); }
	public function offsetGet($k)     { return $this->__get($k); }
	public function offsetSet($k, $v) { $this->__set($k, $v); }
	public function offsetUnset($k)   {}
}
