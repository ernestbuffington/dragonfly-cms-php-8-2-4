<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class INET
{
	public static function getHostName($ip_address)
	{
		$value = gethostbyaddr($ip_address);
		// Prevent HTML inside hostname, IDN doesn't allow them anyway
		// http://unicode.org/reports/tr36/idn-chars.html
		if (false === strpos($value,'<') && false === strpos($value,'>')) {
			return $value;
		}
		return $ip_address;
	}

	public static function getHostIP($hostname, $ipv6 = true)
	{
		$ip4 = array();
		// Use \Dragonfly\Net\Dns?
		$dns = dns_get_record($hostname, $ipv6 ? DNS_A | DNS_AAAA : DNS_A) ?: array();
		foreach ($dns as $record) {
			if ('A' === $record['type']) {
				$ip4[] = $record['ip'];
			}
			if ('AAAA' === $record['type']) {
				return $record['ipv6'];
			}
		}
		return $ip4 ? $ip4[0] : gethostbyname($hostname);
	}

	/*
	 * This function should not be used for the purposes of address verification.
	 * Only the mailexchangers found in DNS are returned, however, according to
	 * RFC 5321 5.1 when no mail exchangers are listed, hostname itself should
	 * be used as the only mail exchanger with a priority of 0.
	 */
	public static function getHostMX($hostname)
	{
		$mxhosts = array();
		$dns = dns_get_record($hostname, DNS_MX) ?: array();
		foreach ($dns as $record) {
			$mxhosts[$record['pri']] = $record['target'];
		}
		if (!$mxhosts) {
			getmxrr($hostname, $mxhosts);
		}
		return empty($mxhosts) ? false : $mxhosts;
	}

	public static function checkDNSRecord($host, $type = DNS_MX)
	{
		static $map = array(
			DNS_A     => 'A',
			DNS_CNAME => 'CNAME',
//			DNS_HINFO => 'HINFO',
			DNS_MX    => 'MX',
			DNS_NS    => 'NS',
			DNS_PTR   => 'PTR',
			DNS_SOA   => 'SOA',
			DNS_TXT   => 'TXT',
			DNS_AAAA  => 'AAAA',
			DNS_SRV   => 'SRV',
			DNS_NAPTR => 'NAPTR',
			DNS_A6    => 'A6',
//			DNS_ALL   => 'ALL',
			DNS_ANY   => 'ANY',
		);
		$host = rtrim(idn_to_ascii($host), '.').'.';
		return checkdnsrr($host, $map[$type]);
	}

}
