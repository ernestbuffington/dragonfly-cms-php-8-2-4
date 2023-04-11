<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\PHP;

abstract class Info
{

	// see php.net/phpinfo
	public static function get($what)
	{
		if (!in_array($what, array(INFO_GENERAL,INFO_CONFIGURATION,INFO_ENVIRONMENT,INFO_MODULES,INFO_VARIABLES))) {
			return false;
		}

		$info = array();

		if (INFO_CONFIGURATION === $what && function_exists('ini_get_all')) {
			foreach (ini_get_all() as $k => $v) {
				$info[$k] = array(
					'value'  => $v['local_value'],
					'master' => $v['global_value'],
				);
			}
		}

		if (INFO_ENVIRONMENT === $what && $_ENV) {
			foreach ($_ENV as $k => $v) { $info[$k] = $v; }
		}

		if (INFO_VARIABLES === $what) {
			foreach ($_COOKIE as $k => $v) { $info["_COOKIE['{$k}']"] = $v; }
			foreach ($_SERVER as $k => $v) { $info["_SERVER['{$k}']"] = trim($v); }
		}

		if ($info) {
			ksort($info);
			return $info;
		}

		ob_start();
		phpinfo($what);
		$cache = preg_split('#(<body>|</body>)#', ob_get_clean(), -1, PREG_SPLIT_NO_EMPTY);
		$cache = str_replace('&nbsp;', ' ', $cache[1]);
		$cache = preg_replace('#\s+#s', ' ', $cache);
		$cache = preg_replace('#(</?)font#s', '$1span', $cache);
		$cache = preg_replace('#><a name="([^"]+)">([^<]+)</a>#s', ' id="$1">$2', $cache);

		if (INFO_MODULES === $what) {
			$module = '';
			preg_match_all('#(?:<h2([^>]*)>([^<]*)</h2>|<tr><td[^>]*>([^<]*)</td>(?:<td[^>]*>([^<]*)</td>)?(?:<td[^>]*>([^<]*)</td>)?</tr>)#s', $cache, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $match) {
				if (isset($match[4])) {
					$match[4] = trim($match[4]);
					if ('<i>no value</i>'===$match[4]) { $match[4] = null; }
					$match[4] = strip_tags($match[4]);
				}
				if (isset($match[5])) {
					$match[5] = trim($match[5]);
					if ('<i>no value</i>'===$match[5]) { $match[5] = null; }
					$match[5] = strip_tags($match[5]);
				}

				switch (count($match))
				{
				case 3: # head
					$match[2] = trim($match[2]);
					if (isset($matches[$key+1])) {
						$module = 'module_'.mb_strtolower(strtr($match[2],' ','_'));
						$info[$module] = array('name' => str_replace('module_','',$match[2]), 'items' => array());
					}
					break;
				case 5: # 2 columns
					$info[$module]['items'][trim($match[3])] = static::decodeEntities($match[4]);
					break;
				case 6: # 3 columns
					$info[$module]['items'][trim($match[3])] = array(
						'value' => static::decodeEntities($match[4]),
						'master' => static::decodeEntities($match[5])
					);
					break;
				}
			}
			// The following two modules are not needed
			unset($info['module_apache_environment']);       // Same as INFO_VARIABLES
			unset($info['module_http_headers_information']); // Headers not needed

			$info = array_filter($info, function($v){return $v['items'];});
		}
		else
		{
			$cache = explode('</table>', $cache);

			if (INFO_GENERAL === $what) {
				$K = \Poodle::getKernel();
				$loadavg = function_exists('sys_getloadavg') ? sys_getloadavg() : array('n/a','n/a','n/a');
				$info['System load'] = $loadavg[0].'% (1 minute), '.$loadavg[1].'% (5 minutes), '.$loadavg[2].'% (15 minutes)';
				$info['PHP Version'] = PHP_VERSION;
				$info['Poodle Version'] = \Poodle::VERSION;
				$info['Database'] = $K->SQL->engine.' '.$K->SQL->server_info.' (client: '.$K->SQL->client_info.')';
				$info['Files Owner'] = get_current_user().' ('.getmyuid().')';
//				$info['Process Owner'] = \Poodle::$PROCESS_OWNER.' ('.\Poodle::$PROCESS_UID.')';
			}

			preg_match_all('#<tr><td[^>]*>([^<]*)</td><td[^>]*>([^<]*)</td>(<td[^>]*>([^<]*)</td>)?</tr>#s', $cache[1], $matches, PREG_SET_ORDER);
			$c = count($matches[0]);
			foreach ($matches as $match) {
				if (isset($match[2])) {
					$match[2] = trim($match[2]);
					if ('<i>no value</i>'===$match[2]) { $match[2] = null; }
					$match[2] = strip_tags($match[2]);
				}
				if (isset($match[4])) {
					$match[4] = trim($match[4]);
					if ('<i>no value</i>'===$match[4]) { $match[4] = null; }
					$match[4] = strip_tags($match[4]);
				}

				$match[1] = strtr(trim($match[1]),'"',"'");
				if (5 === $c) {
					$info[$match[1]] = array(
						'value' => static::decodeEntities($match[2]),
						'master' => static::decodeEntities($match[4])
					);
				} else {
					$info[$match[1]] = static::decodeEntities($match[2]);
				}
			}
		}

		return $info;
	}

	private static function decodeEntities($data)
	{
		return html_entity_decode(trim($data), ENT_QUOTES);
	}

}
