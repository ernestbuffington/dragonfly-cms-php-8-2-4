<?php
/*********************************************
  Copyright (c) 2011 by Dragonfly CMS team
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly;

/**
 * This class provides methods to read and write IP addresses.
 *
 * 1. Validating methods will only check if true or false
 * 2. Filtering methods: will modify the input data, for example:
 *    ::ffff:127.0.0.1 will be returned as 127.0.0.1
 *    ::1 will be returned as 0000:0000:0000:0000:0000:0000:0000:0001
 * 3. When you encounter the word "IP", it means either an IPv4 or an IPv6 network address
 *
 * @package Network
 */
class Net
{
	/**
	 * Retrive the current, human readable, visitor IP address.
	 *
	 * @final
	 * @public
	 * @static
	 * @return string|null Returns a filtered IP, NULL on failure.
	 */
	final public static function ip()
	{
		static $ip;
		if (empty($ip)) {
			$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : $_ENV['REMOTE_ADDR'];
			$ips = array();
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
				$ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
			}
			if (!empty($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
				$ips[] = $_SERVER['HTTP_CLIENT_IP'];
			}
			foreach ($ips as $_ip) {
				if ($tmp = self::filterIP($_ip, false)) { $ip = $tmp['ip']; break; }
			}
		}
		return $ip;
	}

	/**
	 * Retrive the current, varbinary, visitor IP address.
	 *
	 * @final
	 * @public
	 * @static
	 * @return string|null Returns varbinary or NULL on failure.
	 */
	final public static function ipn()
	{
		return inet_pton(self::ip());
	}

	/**
	 * Check if the supplied IP is within a private or reserved range.
	 *
	 * @public
	 * @static
	 * @param string $ip The IPv4 to check.
	 * @return bool Returns TRUE when the IP is within a private or reserved subclass, FALSE otherwise.
	 * @todo IPv4/IPv6 compatibility
	 */
	public static function is_lan($ip)
	{
		return ('localhost' === $ip || (self::filterIPv4($ip) && preg_match('#^(10|127.0.0|172.(1[6-9]|2\d|3[0-1])|192\.168)\.#', $ip)));
	}

	/**
	 * Retrive the Network address for the supplied IPv4/CIDR.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip IPv4 address.
	 * @param int $cidr Classless Inter-Domain Routing.
	 * @return string|false The IPv4 Network Address, FALSE on failure.
	 * @deprecated use \Dragonfly\Net::range($ip, $cidr)
	 */
	final public static function network($ip, $cidr)
	{
		trigger_deprecated('use \Dragonfly\Net::range($ip, $cidr)');
		if ($ip = self::filterIPv4($ip)) {
			return long2ip(self::ip2long($ip, true) & self::ip2long(self::mask($cidr, true)));
		}
	}

	/**
	 * Retrive the Broadcast address for the supplied IPv4/CIDR.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip The IPv4 address.
	 * @param int $cidr Classless Inter-Domain Routing.
	 * @return string|false The IPv4 Broadcast Address or FALSE on failure.
	 * @deprecated use \Dragonfly\Net::range($ip, $cidr)
	 */
	final public static function broadcast($ip, $cidr)
	{
		trigger_deprecated('use \Dragonfly\Net::range($ip, $cidr)');
		if ($ip = self::filterIPv4($ip)) {
			return long2ip(self::ip2long($ip['ip'], true) - (0xffffffff << (32 - $ip['cidr'])) - 1);
		}
	}

	/**
	 * Retrive the network Mask.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip Either an IPv4 or an IPv6, human readable or packet.
	 * @param int $cidr Classless Inter-Domain Routing.
	 * @return string|false Returns the network Mask, FALSE on error
	 * @deprecated use \Dragonfly\Net::range($ip, $cidr)
	 */
	final public static function mask($ip, $cidr)
	{
		trigger_deprecated('use \Dragonfly\Net::range($ip, $cidr)');
		if ($cidr > 32) return false;
		return long2ip((0xffffffff << (32 - $cidr)));
	}

	/**
	 * Convert an IPv4 address family into a (un)signet numerical representation of the supplied IP address, 32bit and 64bit OS compatible.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip The IPv4 address.
	 * @param bool $unsigned Default FALSE.
	 * @return int|false Returns the (un)signed IP address, FALSE on failure.
	 */
	final public static function ip2long($ip, $unsigned=false) {
		$ip = ip2long($ip);
		if ($ip > 2147483647) { $ip -= 4294967296; }
		if ($unsigned && $ip < 0) { $ip += 4294967296; }
		return $ip;
	}

	/**
	 * Filter an IP with Classes Inter-Domain Routing, eg: IP/CIDR.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $str IP/CIDR.
	 * @return array|null Returns an array with the filtered IP (plus additional data) and the CIDR on success, NULL on failure.
	 */
	final public static function filterIPwCIDR($str)
	{
		$str = explode('/', $str);
		if (empty($str[1]) || !$str[1] = intval($str[1])) return;
		return self::range($str[0], $str[1]);
	}

	/**
	 * 8.x & 9.x compatible converter, use inet_ntop() for 9.2+ here only for compatibility reasons
	 *
	 * 5 or 17 = IPv4 & IPv6 varbinary IP saved with a leading "/", old MySQL varbinary bug
	 * 4 or 16 = IPv4 & IPv6 varbinary IP
	 * 8 base64_encoded IP
	 *
	 * @final
	 * @public
	 * @static
	 * @param mixed $ip A varbinary, integer or base64 encoded IPv4 address.
	 * @return string|false Returns an IPv4 address or FALSE on failure.
	 */
	final public static function decode_ip($ip) {
		if (1 < substr_count($ip,':')) {
			return $ip;
		}
		if (4 == substr_count($ip,'.')) {
			return $ip;
		}
		global $db;
		$ip = $db->unescape_binary($ip);
		$l = strlen($ip);
		if ($l == 5 || $l == 17) { --$l; $ip = substr($ip,0,-1); }
		if ($l == 4 || $l == 16) {
			return inet_ntop($ip);
		}
		if ($l == 8) {
			$ip = explode('.', chunk_split($ip, 2, '.'));
			return hexdec($ip[0]).'.'.hexdec($ip[1]).'.'.hexdec($ip[2]).'.'.hexdec($ip[3]);
		}
		if ($tmp = self::filterIPv4($ip)) {
			return $tmp;
		}
		return is_numeric($ip) ? long2ip($ip) : false;
	}

	/**
	 * Filter the supplied IP address.
	 *
	 * <code>
	 * array(
	 *  'v4'  => bool,
	 *  'v6'  => bool,
	 *  'ip'  => 'human readable',
	 *  'ipn' => 'packed'
	 * );
	 * </code>
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip Accept a human readable IPv4 or IPv6 address.
	 * @param bool $any Allow private and reserved IP addresses when TRUE (default), otherwise extranet only.
	 * @return array|null Returns an array containing informations about the IP, FALSE on failure.
	 */
	final public static function filterIP($ip, $any=true) {
		$ret = array('v4' => false, 'v6' => false);

		if (!$ret['v4'] = (bool)($ret['ip'] = self::filterIPv4($ip, $any)))
			$ret['v6'] = (bool)($ret['ip'] = self::filterIPv6($ip, $any));
		if (!$ret['v4'] && !$ret['v6']) return false;

		$ret['ipn'] = inet_pton($ret['ip']);
		return $ret;
	}

	/**
	 * Validates hostnames as per RFC1123#page-13.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $hostname The hostname to check.
	 * @return bool Returns TRUE on success, FALSE on failure.
	 * @todo International hostnames.
	 */
	final public static function validHostname($hostname, $any=false)
	{
		if ($any && 'localhost' === $hostname) return true;
		if (false === strpos($hostname, '.') || 255 < strlen($hostname)) return false;
		$labels = explode('.', $hostname);
		if (empty($labels[0])) unset($labels[0]);
		if (empty($labels[count($labels)-1])) unset($labels[count($labels)-1]);
		foreach ($labels as $label) {
			if ('-' == substr($label, -1) || !preg_match('#^[a-z0-9][a-z0-9\-]{0,62}$#i', $label)) return false;
		}
		return true;
	}

	/**
	 * Validates a IPv4 address. Note, will also sanitize an IPv4 to IPv6 mapped address.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip The IPv4 to check.
	 * @param bool $any Allow private and reserved IPv4 address when TRUE (default), otherwise extranet only.
	 * @return string|false Returns a sanitizied IPv4 address or FALSE on failure.
	 */
	final public static function filterIPv4($ip, $any=true)
	{
		if (false === strpos($ip, '.')) return false;
		# check for ipv4 mapped to ipv6
		$ip = str_replace('::ffff:', '', trim($ip));

		if ($any) return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
		else return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}

	/**
	 * Validates a IPv6 address. Note, will modify a shortened IPv6 into the long version.
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip The IPv6 to check.
	 * @param bool $any Allow private and reserved IPv6 addresses when TRUE (default), otherwise extranet only.
	 * @return string|false Returns a sanitizied IPv6 address or FALSE on failure.
	 */
	final public static function filterIPv6($ip, $any=true)
	{
		if (false === strpos($ip, ':')) { return false; }
		$ip = inet_pton(trim($ip));
		if (false === $ip) { return false; }
		$ip = inet_ntop($ip);
		if ($any) return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
		else return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}

	/**
	 * Expand shortened IPv6, rarely needed in common use
	 *
	 * @final
	 * @public
	 * @static
	 * @param $ipv6 A shorted IPv6: 12:3::4
	 * @return string The expanded IPv6: 0012:0003:0000:0000:0000:0000:0000:0004
	 */
	final public static function expandIPv6($ipv6)
	{
		$ipv6 = unpack('H*', inet_pton($ipv6));
		return implode(':', str_split($ipv6[1], 4));
	}

	/**
	 * Filter the supplied IP address.
	 *
	 * <code>
	 * array(
	 *  'cidr'  => CIDR,
	 *  'ip_s'  => IPv4/IPv6 human readable start range,
	 *  'ipn_s' => Packed start end address,
	 *  'ip_e'  => IPv4/IPv6 human readable end range,
	 *  'ipn_s' => Packed end range address,
	 *  'mask'  => IPv4/IPv6 human readable network mask,
	 *  'maskn' => Packed netwrok mask address
	 * );
	 * </code>
	 *
	 * @final
	 * @public
	 * @static
	 * @param string $ip Accept human readable IPv4/IPv6.
	 * @param int $cidr The CIDR to process.
	 * @return array|false Returns an array containing informations about the IP range, NULL on failure.
	 */
	final public static function range($ip, $cidr)
	{
		if (!$cidr = intval($cidr)) return;
		if (!$ip = self::filterIP($ip)) return;
		list (,$iph) = unpack('H*', $ip['ipn']);
		$len = (8*strlen($ip['ipn']))>>2;
		if ($cidr > $len*4) return;
		$var   = (floor($cidr/2)>>2)*2;
		$sig   = '0x'.substr($iph, $var, 2) + 0;
		$start = substr(str_pad(substr($iph,0,$var).str_pad(dechex($sig&(0xff-(0xff>>($cidr%8)))),2,'0',STR_PAD_LEFT),$len,'0'),0,$len);
		$end   = substr(str_pad(substr($iph,0,$var).str_pad(dechex($sig|(0xff>>($cidr%8))),2,'0',STR_PAD_LEFT),$len,'f'),0,$len);
		$mask  = substr(str_pad(str_repeat('f',$var).dechex(0xff-(0xff>>($cidr%8))),$len,'0'),0,$len);
		if (32 == $len) {
			$start = implode(':', str_split($start, 4));
			$end   = implode(':', str_split($end, 4));
			$mask  = implode(':', str_split($mask, 4));
		} else {
			$start = implode('.', array_map('hexdec', str_split($start, 2)));
			$end   = implode('.', array_map('hexdec', str_split($end,   2)));
			$mask  = implode('.', array_map('hexdec', str_split($mask,  2)));
		}
		return array(
			'v4'    => $ip['v4'],
			'v6'    => $ip['v6'],
			'cidr'  => $cidr,
			'ip_s'  => $start,
			'ipn_s' => inet_pton($start),
			'ip_e'  => $end,
			'ipn_e' => inet_pton($end),
			'mask'  => $mask,
			'maskn' => inet_pton($mask)
		);
	}

}

class_alias('Dragonfly\\Net','Dragonfly_Net');
