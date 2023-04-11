<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	ban_type: 0 = just ban a ip
	          1 = it's a bot
	          2 = email
	          3 = referer/domains
	          4 = email and referer/domains
	          5 = disallowed usernames
	          6 = MAC address
*/

namespace Dragonfly\Net;

abstract class Validate
{
	const
		EMAIL_DB = 1,
		EMAIL_MX = 2;

	public static function domain($domain, $full=false)
	{
		if (!preg_match('@^(?:[a-z]+://)?([^/:?&#]+)@', $domain, $match)) { return false; }
		$domain = mb_strtolower($match[1]);

		if ($full) {
			if (!($fp = fsockopen($domain, 80, $errno, $errstr, 2))) { return false; }
			fclose($fp);
		}

		static $domains = null;
		$K = Dragonfly::getKernel();
		if (is_null($domains) && $K && $K->SQL && isset($K->SQL->TBL->security)) {
			$domains = '';
			if ($result = $K->SQL->query('SELECT ban_string FROM '.$K->SQL->TBL->security.' WHERE ban_type IN (3,4)')) {
				while ($e = $result->fetch_row()) { $domains .= '|'.preg_quote($e[0]); }
			}
			if ($domains) { $domains = '#('.substr($domains,1).')#'; };
		}
		return !($domains && preg_match($domains, $domain));
	}

	protected static
		$re_email,
		$re_uri;

	protected static function initRegEx()
	{
		if (self::$re_email && self::$re_uri) return;

		$ub = "25[0-5]|2[0-4]\\d|[01]?\\d\\d?";  # IPv4 part, unsigned byte (0-255)
		$h4 = "[0-9A-Fa-f]{1,4}";                # IPv6 part, hex
		$dp = "[a-z0-9](?:[a-z0-9-]*[a-z0-9])?"; # domain part
	  $loc = "[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+"; # e-mail local-part part
		$local = $loc."(?:\\.".$loc.")*";        # e-mail local-part
		$domain = "(?:".$dp."\\.)+".$dp;
		$IPv4 = "(?:(?:".$ub.")\\.){3}".$ub;
		$IPv6 = "\\[".implode('|',array(
			"(?:(?:".$h4.":){7}(?:".$h4."|:))",
			"(?:(?:".$h4.":){6}(?::".$h4."|".$IPv4."|:))",
			"(?:(?:".$h4.":){5}(?:(?:(?::".$h4."){1,2})|:".$IPv4."|:))",
			"(?:(?:".$h4.":){4}(?:(?:(?::".$h4."){1,3})|(?:(?::".$h4.")?:".$IPv4.")|:))",
			"(?:(?:".$h4.":){3}(?:(?:(?::".$h4."){1,4})|(?:(?::".$h4."){0,2}:".$IPv4.")|:))",
			"(?:(?:".$h4.":){2}(?:(?:(?::".$h4."){1,5})|(?:(?::".$h4."){0,3}:".$IPv4.")|:))",
			"(?:(?:".$h4.":){1}(?:(?:(?::".$h4."){1,6})|(?:(?::".$h4."){0,4}:".$IPv4.")|:))",
			"(?::(?:(?:(?::".$h4."){1,7})|(?:(?::".$h4."){0,5}:".$IPv4.")|:))"
		))."\\]";
		$host = "(".$domain."|".$IPv4."|".$IPv6.")";

		# RFC 2822 is used as
		# RFC 1035 doesn't allow 1 char subdomains, we allow it due to some bugs in mail servers
		# RFC 5321 discourages case-sensitivity
		# RFC 6530 SMTPUTF8 not supported, like: To: "=?utf-8?q?j=E2=9C=82sper?=" <=?utf-8?q?j=E2=9C=82sper?=@example.org>
		//self::$re_email = ';^((?:"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")(?:'.$local.')?|'.$local.')@'.$host.'$;i';
		self::$re_email = ';^((?:"[\\w\\s-]+")(?:'.$local.')?|'.$local.')@'.$host.'$;i';

		self::$re_uri   = ';^([a-z][a-z0-9\\+\\.\\-]+):\\/\\/'.$host.'(:[0-9]+)?(\\/[^\\x00-\\x1F#?]+)?(\\?[^\\x00-\\x1F#]+)?(#[^\\x00-\\x1F]+)?$;i';
	}

	public static function emailaddress($email, $flags=3)
	{
		$K = \Dragonfly::getKernel();

		if (strlen($email) < 6) {
			throw new \Exception(sprintf($K->L10N['%s is too short.'], $K->L10N['Email address']));
		}

		self::initRegEx();
		if (!preg_match(self::$re_email, $email, $domain)) {
			throw new \Exception(sprintf($K->L10N['Invalid %s'], $K->L10N['Email_address']));
		}
		$domain = $domain[2];

		# Check disallowed domains
		if ($flags & self::EMAIL_DB && $K->SQL && isset($K->SQL->TBL->security))
		{
			static $domains = null;
			if (is_null($domains)) {
				$domains = preg_quote('example.');
				if ($result = $K->SQL->query('SELECT ban_string FROM '.$K->SQL->TBL->security_domains.' WHERE ban_type IN (2,4)')) {
					while ($e = $result->fetch_row()) { $domains .= '|'.preg_quote($e[0]); }
				}
				$domains = '#('.$domains.')#';
			}
			if (preg_match($domains, $domain, $match)) {
				throw new \Exception(sprintf($K->L10N['The mail domain "%s" is disallowed for registration.'], $match[1]), self::EMAIL_DB);
			}
		}

		# Does domain have a valid MX
		if ($flags & self::EMAIL_MX) {
			$result = \Dragonfly\Net\Dns::resolve($domain, \Dragonfly\Net\Dns::MX);
			if (!$result && !is_null($result)) {
				throw new \Exception(sprintf($K->L10N['The mail domain "%s" does not exist.'], $domain), self::EMAIL_MX);
			}
		}

		return true;
	}

	public static function uri($uri)
	{
		// Shortest possible uri is: tn://x.nu/
		if (strlen($uri) < 10) return false;

		self::initRegEx();
		if (!preg_match(self::$re_uri, $uri, $domain)) {
			return false;
		}
		$domain = $domain[2];

		return true;
	}

}
