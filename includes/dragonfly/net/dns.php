<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Net;

/**
 * This is a DNS resolver class
 *
 * Supported query classes: IN
 * Supported query types: A, NS, CNAME, SOA, MB, MG, MR, PTR, MX, TXT, AAAA
 *
 * @package Network
 * @link http://php.net/manual/en/function.gethostbyaddr.php#46869 Where everything started.
 * @link http://tools.ietf.org/html/rfc974  Mail Routing.
 * @link http://tools.ietf.org/html/rfc1035 Domain Names.
 * @link http://tools.ietf.org/html/rfc3596 IPV6 Extension.
 * @link http://tools.ietf.org/html/rfc2181 Clarifications.
 * @link http://tools.ietf.org/html/rfc2308 Negavite Caching.
 * @link http://tools.ietf.org/html/rfc3425 Obsoleting IQuery.
 * @link http://tools.ietf.org/html/rfc4033 Security.
 * @link http://tools.ietf.org/html/rfc4343 Case Insensitivity Clarification.
 * @link http://www.netfor2.com/dns.htm Data trasmission examples.
 * @todo WKS (still need to find one), truncated messages, international hostnames.
 */
abstract class Dns
{

	public static $server;

	const A     = 1;
	const NS    = 2;
	const CNAME = 5;
	const SOA   = 6;
	const MB    = 7;
	const MG    = 8;
	const MR    = 9;
	#const NILL = 10;
	const WKS   = 11;
	const PTR   = 12;
	#const HINFO= 13;
	#const MINFO= 14;
	const MX    = 15;
	const TXT   = 16;
	const AAAA  = 28;
	#const A6   = 38;

	const IN = 1;
	const CH = 3;

	const OFFSET = 0x0fff;
	const POINTER = 0xc000;

	private static $response, $now_at, $offsets, $data;
	private static $types = array(
		self::A     => 'A',
		self::NS    => 'NS',
		self::CNAME => 'CNAME',
		self::SOA   => 'SOA',
		self::MG    => 'MG',
		self::MB    => 'MB',
		self::MR    => 'MR',
		self::WKS   => 'WKS',
		self::PTR   => 'PTR',
		self::MX    => 'MX',
		self::TXT   => 'TXT',
		self::AAAA  => 'AAAA'
	);

	final public static function reverse($ip)
	{
		$resolve = self::resolve($ip, self::PTR);
		if (is_array($resolve) && !empty($resolve['PTR'])) {
			$ret = array('verified' => false, 'hostname' => $resolve['PTR']);
			$resolve = self::resolve($ret['hostname']);
			if ('SERVFAIL' === $resolve) return $resolve;
			if (is_array($resolve) && isset($resolve['A'][$ret['hostname']]) && in_array($ip, $resolve['A'][$ret['hostname']])) {
				$ret['verified'] = true;
			}
			return $ret;
		}
		return $resolve;
	}

	final public static function resolve($input, $qtype=self::A)
	{
		$input = trim($input);
		$qtype = intval($qtype);

		if (\Dragonfly::getKernel()->CFG) {
			self::$server = \Dragonfly::getKernel()->CFG->server->dns;
		}

		if (empty(self::$server) || !($server = self::parse_input(self::$server, true))) return;
		if ('ipv6' == $server['type']) $server['data'] = '['.self::$server.']';
		if ('host' != $server['type'] && 'ipv4' != $server['type']) return;

		if ('.' == substr($input, -1)) $input = substr($input, 0, -1);
		if (!($input = self::parse_input($input))) return;

		self::$data = self::$offsets = array();

		switch ($input['type'])
		{
			case 'host':
			case 'a4':
			case 'a6':
				$var = explode('.', $input['data']);
				break;
			case 'ipv4':
				$var = array_reverse(explode('.', $input['data']));
				$var[] = 'in-addr';
				$var[] = 'arpa';
				break;
			case 'ipv6':
				$var = array_reverse(str_split(str_replace(':', '', $input['data'])));
				$var[] = 'ip6';
				$var[] = 'arpa';
			break;
			default:
				return;
		}
		$var[] = $qname = '';
		self::$data['header'] = pack('CCn*', rand(0x01, 0xff), rand(0x01,0xff), 0x0100, 1, 0, 0, 0);
		foreach ($var as $val) {
			$qname .= chr(strlen($val)).$val;
		}
		self::$data['header'].= $qname.pack('nn', $qtype, self::IN);

		$host = 'udp://'.$server['data'];
		if (!($handle = fsockopen($host, 53, $errno, $errstr, 2))) {
			trigger_error(__METHOD__ ." connection to {$host} failed {$errno}: {$errstr}", E_USER_WARNING);
			return;
		}
		if (!stream_set_timeout($handle, 2)) {
			fclose($handle);
			trigger_error(__METHOD__ ." setting connection timeout for {$host} failed", E_USER_WARNING);
			return;
		}
		if (!($req_len = fwrite($handle, self::$data['header']))) {
			fclose($handle);
			trigger_error(__METHOD__ ." could not write request to {$host}", E_USER_WARNING);
			return;
		}
		self::$response = fread($handle, 1024);
		fclose($handle);
		if (!self::$response) {
			trigger_error(__METHOD__ ." no response from {$host}. Check 'DNS server' setting at Admin -> Security", E_USER_WARNING);
			if (self::MX === $qtype) {
				return \Poodle\INET::getHostMX($input['data']);
			}
			return;
		}

		self::$data['header'] = 'H4id/ncodes/nqdcount/nancount/nnscount/narcount/H'.(($req_len-16)*2).'name/ntype/nclass';
		self::$data['header'] = unpack(self::$data['header'], self::$response);
		if (!self::$data['header']) {
			return;
		}
		self::$data['header'] = array_merge(self::$data['header'], array(
			'qr'    => intval((bool) (0x8000 & self::$data['header']['codes'])),
			'opcode'=> 0x7800 & self::$data['header']['codes'],
			'aa'    => intval((bool) (0x0400 & self::$data['header']['codes'])),
			'tc'    => intval((bool) (0x0200 & self::$data['header']['codes'])),
			'rd'    => intval((bool) (0x0100 & self::$data['header']['codes'])),
			'ra'    => intval((bool) (0x0080 & self::$data['header']['codes'])),
			'z'     => 0x0070 & self::$data['header']['codes'],
			'rcode' => 0x000f & self::$data['header']['codes']
		));
		unset(self::$data['header']['codes']);

		// RCODEs errors at rfc1035 p.26
		if (self::$data['header']['rcode']) {
			switch (self::$data['header']['rcode']):
				case 1:
					return 'FORMERR';
				case 2:
					return 'SERVFAIL';
				case 3:
					return 'NXDOMAIN';
				case 4:
					return 'NOTIMPL';
				case 5:
					return 'REFUSED';
			endswitch;
			return;
		}
		if (!self::$data['header']['ra'] && !self::$data['header']['ancount']) {
			trigger_error(__METHOD__ ." recursion not available on {$host}. Change 'DNS server' setting at Admin -> Security", E_USER_WARNING);
			return;
		}
		if (self::$data['header']['tc']) {
			trigger_error(__METHOD__ .' message truncated', E_USER_WARNING);
		//	return;
		}

		$total = self::$data['header']['ancount'] + self::$data['header']['nscount'] + self::$data['header']['arcount'];
		if (!$total) {
			trigger_error(__METHOD__ .', no answers received', E_USER_WARNING);
			return false;
		}

		self::$now_at = 12;
		self::$data['header']['name'] = self::get_pointer($qname);
		self::$data['header']['type'] = self::$types[self::$data['header']['type']];
		self::$response = substr(self::$response, $req_len);
		self::$now_at = $req_len;

		while (!empty(self::$response)) self::rr();

		return self::$data;
	}

	private static function rr()
	{
		$rr_header = unpack('H4parent/ntype/nclass/Nttl/nrdlength', self::$response);
		$rrdata = substr(self::$response, 12, $rr_header['rdlength']);
		self::$response = substr(self::$response, 12+$rr_header['rdlength']);
		self::$now_at += 12;

		switch ($rr_header['type']):
			case self::A:
				self::get_a($rrdata, $rr_header);
				break;
			case self::NS:
			case self::CNAME:
			case self::MB:
			case self::MG:
			case self::MR:
			case self::PTR:
				self::$data[self::$types[$rr_header['type']]] = self::get_pointer($rrdata);
				break;
			case self::MX:
				self::get_mx($rrdata, $rr_header);
				break;
			case self::SOA:
				self::get_soa($rrdata, $rr_header);
				break;
			case self::WKS:
				//self::get_wks($rrdata, $rr_header);
				break;
			case self::TXT:
				self::$data[self::$types[$rr_header['type']]] = self::get_char_string($rrdata);
				break;
			case self::AAAA:
				self::get_aaaa($rrdata, $rr_header);
				break;
			default:
				break;
		endswitch;
		self::$now_at += $rr_header['rdlength'];
	}

	private static function get_mx($rrdata, $rr_header)
	{
		$pref = unpack('n', $rrdata);
		$rrdata = substr($rrdata, 2);
		self::$now_at += 2;
		self::$data[self::$types[$rr_header['type']]][self::get_pointer($rrdata)] = $pref[1];
		self::$now_at -= 2;
	}

	private static function get_soa($rrdata, $rr_header)
	{
		if ($parent = self::get_parent($rr_header['parent'])) {
			$ret = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($rrdata, -20));
			$rrdata = substr($rrdata, 0, -20);
			$soa = explode('..', self::get_pointer($rrdata));
			$ret['source'] = $soa[0] . ('.' == substr($soa[0], -1) ? '' : '.');
			$ret['contact'] = isset($soa[1]) ? $soa[1] : '';
			self::$data[self::$types[$rr_header['type']]][$parent] = $ret;
		}
	}

	private static function get_a($rrdata, $rr_header)
	{
		$ret = array();
		if ($parent = self::get_parent($rr_header['parent'])) {
			for ($i=0; $i<$rr_header['rdlength']; ++$i) {
				$ret[$i] = unpack('C', $rrdata);
				$ret[$i] = $ret[$i][1];
				$rrdata = substr($rrdata, 1);
			}
			self::$data[self::$types[$rr_header['type']]][$parent][] = implode('.', $ret);
		}
	}

	private static function get_wks($rrdata, $rr_header)
	{
		if ($parent = self::get_parent($rr_header['parent'])) {
			self::get_a($rr_header, $rrdata);
			$proto = unpack('x4/H', $rrdata);
			$proto = $proto[1];
		}
	}

	private static function get_aaaa($rrdata, $rr_header)
	{
		if ($parent = self::get_parent($rr_header['parent'])) {
			$ret = unpack('H*', $rrdata);
			$ret = array_map('intval', str_split($ret[1], 4));
			$ret = inet_ntop(inet_pton(implode(':', $ret)));
			self::$data[self::$types[$rr_header['type']]][$parent] = $ret;
		}
	}

	private static function get_parent($parent)
	{
		if (self::POINTER & intval($parent, 16)) {
			return self::$offsets[(self::OFFSET & intval($parent, 16))];
		}
	}

	private static function get_char_string($rrdata)
	{
		$len = unpack('H2', $rrdata);
		$len = hexdec($len[1]);
		if ($len) return substr($rrdata, 1, $len);
	}

	private static function get_pointer($rrdata)
	{
		$now_at = self::$now_at;
		$part = array();
		while (!empty($rrdata)) {
			if (null === ($var = self::get_char_string($rrdata))) {
				$part[] = '';
				break;
			}
			$pointer = unpack('n', $rrdata);
			if (self::POINTER & $pointer[1]) {
				$var = $part[] = self::$offsets[self::OFFSET & $pointer[1]];
				$rrdata = substr($rrdata, 2);
				$now_at += 2;
				unset($pointer);
			} else {
				self::buffer($rrdata, $now_at);
				$part[] = $var;
				$rrdata = substr($rrdata, strlen($var)+1);
				$now_at += strlen($var)+1;
			}
			if ('.' === substr($var, -1) && empty($rrdata)) break;
		}
		$part = implode('.', $part);
		if (false === strpos($part,'<') && false === strpos($part,'>')) {
			return $part;
		}
		return null;
	}

	private static function parse_input($var, $any=false)
	{
		if ($ipv4 = \Dragonfly\Net::filterIPv4($var))
			return array('data'=>$ipv4, 'type' => 'ipv4');

		if ($ipv6 = \Dragonfly\Net::filterIPv6($var))
			return array('data'=>\Dragonfly\Net::expandIPv6($ipv6), 'type' => 'ipv6');

		if (preg_match('#^[a-f0-9\.]{64}(.?)$#', $var, $match) && \Dragonfly\Net::validHostname($match[1], $any))
			return array('data'=>$var,  'type' => 'a6');

		if (preg_match('#^([0-9\.]{2,4}){4}(.?)$#', $var, $match) && \Dragonfly\Net::validHostname($match[1], $any))
			return array('data'=>$var,  'type' => 'a4');

		if ($pos = strpos($var, '@'))
			return self::parse_input(substr($var, $pos+1));

		if (\Dragonfly\Net::validHostname($var, $any))
			return array('data'=>$var,  'type' => 'host');

		trigger_error(__METHOD__ .' not valid');
		return false;
	}

	private static function buffer($rrdata, $now_at)
	{
		$part = array();
		while (!empty($rrdata)) {
			if (null === ($var = self::get_char_string($rrdata))) {
				$part[] = '';
				break;
			}
			$pointer = unpack('n', $rrdata);
			if (self::POINTER & $pointer[1]) {
				$var = $part[] = self::$offsets[self::OFFSET & $pointer[1]];
				$rrdata = substr($rrdata, 2);
			} else {
				$part[] = $var;
				$tmp_len = strlen($var);
				$rrdata = substr($rrdata, $tmp_len+1);
			}
			if ('.' === substr($var, -1) && empty($rrdata)) break;
		}
		$rrdata = implode('.', $part);
		if (!in_array($rrdata, self::$offsets)) self::$offsets[$now_at] = $rrdata;
	}
}
