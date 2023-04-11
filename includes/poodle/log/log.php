<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class LOG
{

	const
		CREATE  = 1, # created data
		MODIFY  = 2, # edited data. NOTE: don't confuse with UPDATE
		DELETE  = 3, # removed data. TIP: place deleted data in message
		UPDATE  = 4, # like forum prune, to bring something 'up to date'
		MOVE    = 5, # moved data. TIP: place 'moved from' in message
		MERGE   = 6,
		SPLIT   = 7,
		APPROVE = 8,
		DENY    = 9,
		LOGIN   = 10,
		LOGOUT  = 11;

	// Same as RFC 5424 section 6.2.1 decimal Severity level indicator
	// http://tools.ietf.org/html/rfc5424#section-6.2.1

	public static function debug    ($type, $msg, $lh=false) { self::insert(7, $type, $msg, $lh); }

	public static function info     ($type, $msg, $lh=false) { self::insert(6, $type, $msg, $lh); }

	public static function notice   ($type, $msg, $lh=false) { self::insert(5, $type, $msg, $lh); }

	public static function warning  ($type, $msg, $lh=false) { self::insert(4, $type, $msg, $lh); }

	public static function error    ($type, $msg, $lh=true)  { self::insert(3, $type, $msg, $lh); }

	public static function critical ($type, $msg, $lh=true)  { self::insert(2, $type, $msg, $lh); }

	public static function alert    ($type, $msg, $lh=true)  { self::insert(1, $type, $msg, $lh); }

	public static function emergency($type, $msg, $lh=true)  { self::insert(0, $type, $msg, $lh); }

	protected static function insert($level, $type, $msg, $log_headers=false)
	{
		$K = \Poodle::getKernel();
		if (is_object($K) && $K->SQL && isset($K->SQL->TBL->log)) try
		{
			$request_uri = substr($_SERVER['REQUEST_URI'],0,250);

			$SQL = $K->SQL;
			if (404 == $type && $SQL->count('log', 'log_type=404 AND log_request_uri='.$SQL->quote($request_uri)))
			{
				return;
			}

			$headers = array();
			if ($log_headers) {
				if (function_exists('apache_request_headers')) {
					$headers = apache_request_headers();
				}
				if (!$headers) {
					foreach ($_SERVER as $header => $value) {
						if (0 === strpos($header, 'HTTP_')) {
							$headers[substr($header, 5)] = $value;
						}
					}
				}
				foreach ($headers as $header => $value) {
					$headers[$header] = "{$header}: {$value}";
				}
			}

			$ID = $K->IDENTITY;
			$row = array(
				'log_time'   => time(),
				'log_level'  => (int)$level,
				'log_type'   => $SQL->quote(mb_substr($type,0,20)),
				'identity_id'=> $ID ? $ID->id : 0,
				'log_ip'     => $SQL->quote($_SERVER['REMOTE_ADDR']),
				'log_msg'    => $SQL->quote($msg),
				'log_request_uri'     => $SQL->quote($request_uri),
				'log_request_method'  => $SQL->quote($_SERVER['REQUEST_METHOD']),
				'log_request_headers' => $SQL->quote(implode("\n", $headers)),
			);
			$SQL->insertPrepared('log', $row);
			return;
		} catch (\Exception $e) {}
		error_log("Dragonfly LOG: {$type}, {$msg}");
	}

	public static function cleanup($days = 30)
	{
		$t = time() - (max(30, $days) * 86400);
		\Poodle::getKernel()->SQL->TBL->log->delete("log_time < {$t}");
	}

}
