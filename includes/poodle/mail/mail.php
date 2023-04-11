<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Mail
{
	public static function reader($agent=null)
	{
		// pop3, imap4, none, debug
		return self::open('read', $agent);
	}

	public static function sender()
	{
		$CFG = \Poodle::getKernel()->CFG->mail;
		$handler = $CFG->sender;
		return self::open('send', $handler, isset($CFG->$handler) ? $CFG->$handler : '');
	}

	# Removes the "\015\012" ("\r\n") which causes linebreaks in SMTP email.
	public static function removeCRLF($str)
	{
		return trim(preg_replace('#\\R+#', ' ', $str));
	}

	private static function open($direction, $agent, $cfg='')
	{
		if (!$agent) { $agent = 'php'; }
		$cname = 'Poodle\\Mail\\'.$direction.'\\'.$agent;
		return new $cname($cfg);
	}

	protected static function B_encode($value)
	{
		return base64_encode($value);
	}

	protected static function Q_encode($value)
	{
		return preg_replace_callback('#[^!*+/\-A-Za-z]#', function($m){return '='.strtoupper(bin2hex($m[0]));}, $value);
	}

	# Encode a header string to best of Q, B, quoted or none.
	public static function encodeHeader($name, $value, $phrase=false, $encoding='Q', $charset='UTF-8')
	{
		$name  = trim($name);
		$name  = strlen($name) ? "{$name}: " : '';
		$value = trim($value);

		$x = 0;
		if ($phrase) {
			if (!preg_match('/[\200-\377]/', $value)) {
				$encoded = addcslashes($value, "\0..\37\177\\\"");
				return $name . (($value === $encoded && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $value)) ? $encoded : "\"{$encoded}\"");
			}
			$x = preg_match('/[^\040\041\043-\133\135-\176]/', $value);
		} else {
			$x = preg_match('#[\000-\010\013\014\016-\037\177-\377]#', $value);
		}
		if (!$x) {
			return $name.$value;
		}

		# iconv_mime_encode() might go here but it fails on UTF-8 data
		if ('Q' === $encoding) {
			# [\000-\037\040\075\077\137\177-\377] # iconv_mime_encode compatible
			# [^!*+/\-A-Za-z]                      # mb_encode_mimeheader compatible
			$value_q = self::Q_encode($value);
			$value_b = self::B_encode($value);
			if (strlen($value_b) < strlen($value_q)) {
				$encoding = 'B';
			}
			# Replace all spaces to _ (more readable than =20)
//			else { $value = str_replace('=20', '_', $value); }
			unset($value_b, $value_q);
		}

		$charset = strtoupper($charset);

		if (function_exists('mb_encode_mimeheader')) {
			return $name.mb_encode_mimeheader($value, $charset, $encoding, "\r\n", strlen($name));
		}

		$start = " =?{$charset}?{$encoding}?";
		$end   = "?=\r\n";
		$chars = floor((76-strlen($start.$end))*0.67);
		$fn = $encoding.'_encode';
		return trim($name) . preg_replace_callback(
			'#(.{1,'.$chars.'})#',
			function($m) use ($start, $fn, $end) {return $start . self::$fn($m[1]) . $end;},
			$value);
	}

}
