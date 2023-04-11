<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

/* Applied rules:
 * ClosureToArrowFunctionRector (https://wiki.php.net/rfc/arrow_functions_v2)
 */

abstract class URL
{
	public static function load($url='', $full=false)
	{
		return ($full ? BASEHREF : DOMAIN_PATH) . '?' . str_ireplace('&amp;', '&', $url);
	}

	public static function index($url='', $UseLEO=true, $full=false)
	{
		$url = str_ireplace('&amp;', '&', $url);
		global $module_name;
		if (empty($url) || $url[0] == '&') $url = $module_name.$url;
		$scheme = '';
		if ($url !== '/') {
			$MAIN_CFG = \Dragonfly::getKernel()->CFG;
			if (is_object($MAIN_CFG) && $MAIN_CFG->auth->https && 'http' === $_SERVER['REQUEST_SCHEME'] && preg_match('#^login(&.*)?$#D',$url)) {
				$scheme = 'https:';
			}
			if ($UseLEO && is_object($MAIN_CFG) && $MAIN_CFG->seo->leo) {
				$end = $MAIN_CFG->seo->leoend;
				$url = str_replace(array('&', '?'), '/', \Dragonfly\Page\Router::fwdDst($url));
				if (false !== strpos($url, '/file=')) {
					$url = str_replace('/file=', '/', $url);
				}
				if (false !== strpos($url, '#')) {
					$url = str_replace('#', $end.'#', $url);
				} else {
					$url .= $end;
				}
			} else {
				$url = '?name=' .$url;
			}
		} else {
			$url = '';
		}
		if ($full) return BASEHREF .$url;
		return $scheme . '//' . $_SERVER['HTTP_HOST'] . DOMAIN_PATH . $url;
	}

	public static function admin($url='', $full=false)
	{
		$url = str_ireplace('&amp;', '&', $url);
		global $op, $module_name;
		if (empty($op) && !empty($module_name)) $op = $module_name;
		if (empty($url)) { $url = $op; }
		if ($url[0] == '&') { $url = \Dragonfly::$URI_ADMIN .'&op=' .$op .$url; }
		else { $url = \Dragonfly::$URI_ADMIN .'&op=' .$url; }
		if ($full) { $url = BASEHREF . $url; }
		else { $url = DOMAIN_PATH . $url; }
		return $url;
	}

	public static function lang($lng)
	{
		global $home;
		$qs = Poodle\URI::parseQuery(self::canonical(2));
		$qs['newlang'] = $lng;
		$qs = str_replace('=&','&', self::buildQuery($qs));
		$qs = $home ? '&'.$qs : $qs;
		return htmlspecialchars(self::index($qs, true, true));
	}

	public static function encode($url)
	{
		$url = str_replace('&', '%26', $url);
		$url = str_replace('/', '%2F', $url);
		$url = str_replace('.', '%2E', $url);
		return $url;
	}

	public static function refresh($url='', $time=3)
	{
		$MAIN_CFG = \Dragonfly::getKernel()->CFG;
		$url = str_ireplace('&amp;', '&', $url);
		if (false === strpos($url, '://') && 0 !== strpos($url, '//')
		 && $_SERVER['PHP_SELF'] != substr($url, 0, strlen($_SERVER['PHP_SELF']))) {
			$url = BASEHREF . $url;
		}
		header('Refresh: ' .intval($time) .'; url='.$url);
	}

	public static function redirect($url='', $code=0)
	{
		$SESS = \Dragonfly::getKernel()->SESSION;
		if (is_object($SESS)) { $SESS->write_close(); }

		# comment out only the respective line of code
		# (1 || 2) && 3

		# 1. Detect a Private Network install and rewrite RFC 3986-4.2 on redirects.
		#    My local IIS 7.5 suffer from this, even if it seems to be a unique issue.
		#$prot = \Dragonfly::isLocal() ? (DF_HTTP_SSL_REQUIRED ? 'https' : 'http') : '';

		# 2. Do not RFC 3986-4.2 on redirects since RFC 2616-14.30 requires absoluteURI
		#$prot = DF_HTTP_SSL_REQUIRED ? 'https' : 'http';

		# 3. rewrite the URI
		#$url = preg_replace('#^(//)(.*?)$#D', "{$prot}://$2", $url);

		$url = str_ireplace('&amp;', '&', $url);
		if (false === strpos($url, '://') && 0 !== strpos($url, '//')) {
			$b = \Dragonfly::$URI_BASE.'/';
			if (0 === strpos($url, $b)) {
				$url = substr($url, strlen($b));
			}
			$url = BASEHREF . $url;
		}

		$code = ((int)$code >= 300 ? (int)$code : 303);
		//\Poodle\HTTP\Status::set(303);
		//header("Status: $code");
		header('Location: ' . $url, true, $code);
		exit;
	}

	public static function uri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	# It tries to extend URL::index() rebuild the link of the current page as URL::index() would.
	# 0 -default- returns Module/key=val.html (URL::index() output LEO on)
	# 1 returns ?name=Module&key=val (URL::index() output LEO off, old index.php?name=Module&key=val)
	# 2 returns Module&key=val (as URL::index() inputs)
	public static function canonical($type=0)
	{
		if (empty($_GET['name']) && empty($_GET['newlang'])) return '';
		$q = preg_replace('/=(&|$)/D', '$1', str_replace('name=', '', $_GET->__toString()));
		if (2 === $type) return $q;
		return str_replace('//'. $_SERVER['HTTP_HOST']. DOMAIN_PATH, '', self::index($q, !$type));
	}

	public static function query()
	{
		return substr($_SERVER['REQUEST_URI'], strlen(DOMAIN_PATH));
	}

	public static function buildQuery($data, $enc=PHP_QUERY_RFC3986)
	{
		return http_build_query($data, '', '&', $enc);
	}

	public static function shrink($url)
	{
		$url = preg_replace("#(^[\w]+?://)#", '', $url);
		return (strlen($url) > 35) ? substr($url,0,22).'...'.substr($url,-10) : $url;
	}

	public static function makeClickable($text)
	{
		$text = preg_replace_callback(
			'#(^|[\n ])([\w]+?://[\w]+[^ "\n\r\t<]*)#is',
			fn($m) => "{$m[1]}<a href=\"{$m[2]}\" rel=\"nofollow\" title=\"{$m[2]}\" target=\"_blank\">".\URL::shrink($m[2]).'</a>',
			$text);
		$text = preg_replace_callback(
			'#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#is',
			fn($m) => "{$m[1]}<a href=\"http://{$m[2]}\" rel=\"nofollow\" title=\"{$m[2]}\" target=\"_blank\">".\URL::shrink($m[2]).'</a>',
			$text);
		return preg_replace("#(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1 \\2 &#64; \\3", $text);
	}

}
