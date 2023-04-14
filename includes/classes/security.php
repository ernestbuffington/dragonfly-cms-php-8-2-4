<?php
/*********************************************
	MOO CMS, Copyright (c) 2007 The MOO Dev. Group. All rights reserved.

	This source file is free software; you can redistribute it and/or
	modify it under the terms of the MOO Public License as published
	by the MOO Development Group; either version 1 of the License, or
	(at your option) any later version.

  $Source: /public_html/includes/classes/security.php,v $
  $Revision: 9.56 $
  $Author: djmaze $
  $Date: 2007/12/16 22:16:14 $
**********************************************/
/*
ban_type: 0 = just ban a ip
                  1 = it's a bot
                  2 = email
                  3 = referer
                  4 = email and referer
                  5 = disallowed usernames
                  6 = MAC address
                  7 = flood ban
                  8 = protected ip
*/

class Security
{

	public static function init()
	{
		$ipn = null;
        $mac = null;
        # Show error page if the http server sends an error
		if (isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] >= 400 && $_SERVER['REDIRECT_STATUS'] <= 503) {
			cpg_error('', $_SERVER['REDIRECT_STATUS']);
		}
		if (!empty($_SESSION['SECURITY']['banned'])) { cpg_error('', $_SESSION['SECURITY']['banned']); }
		global $MAIN_CFG, $SESS, $db, $prefix;
		# get the visitor IP
		$ip = Security::get_ip();
		# If not a member check for bot or ban
		if ($SESS->new) {
			$_SESSION['SECURITY']['banned'] = false;
			# is it a bot or a ban?
			if (strlen($ip) == 4) {
				list(,$ip4) = unpack('N',$ip);
				if ($result = $db->query('SELECT * FROM '.$prefix."_security WHERE ban_ipv4_s = $ip4 OR (ban_ipv4_s < $ip4 AND ban_ipv4_e >= $ip4) LIMIT 0,1", TRUE, TRUE)) {
					$row = $db->fetch_array($result, SQL_ASSOC);
					$db->free_result($result);
				}
			}
			if (empty($row)) {
				$mac = (strlen($ip) == 16) ? ' OR ban_ipn='.$db->binary_safe(substr($ip,-8)) : '';
				$ipn = $db->binary_safe($ip);
				if ($result = $db->query('SELECT * FROM '.$prefix."_security WHERE ban_ipn=$ipn$mac LIMIT 0,1", TRUE, TRUE)) {
					$row = $db->fetch_array($result, SQL_ASSOC);
					$db->free_result($result);
				}
			}
			if (!empty($row)) {
				if ($row['ban_type'] == 1) {
					$agent = Security::_detectBot($row['ban_string']);
				} else if ($row['ban_type'] == 7 && $row['ban_time'] < gmtime() && $MAIN_CFG['_security']['unban']) {
						$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_ipn=$ipn$mac");
				} else if ($row['ban_type'] == 8) {
						$_SESSION['SECURITY']['shield'] = strlen($ip);
				} else {
					$_SESSION['SECURITY']['banned'] = 800;
				}
			}
			# is it a referer spam?
			if ($MAIN_CFG['_security']['referers'] && !$_SESSION['SECURITY']['banned'] &&
			    !empty($_SERVER['HTTP_REFERER']) &&
			    strpos($_SERVER['HTTP_REFERER'], (string) $MAIN_CFG['server']['domain']) === false &&
			    !Security::check_domain($_SERVER['HTTP_REFERER']))
			{
				$_SESSION['SECURITY']['banned'] = 801;
			}
		}
		# Detect User-Agent and Operating System
		if (empty($_SESSION['SECURITY']['UA'])) {
			if (empty($agent) && !$_SESSION['SECURITY']['banned']) {
			 include(CORE_PATH.'data/ua.inc');
			}
			if (!empty($agent['bot'])) {
				$_SESSION['SECURITY']['nick'] = $agent['bot'];
				$_SESSION['SECURITY']['banned'] = $agent['banned'];
			}
			$_SESSION['SECURITY']['UA'] = empty($agent['ua']) ? 'N/A' : $agent['ua'];
			$_SESSION['SECURITY']['OS'] = empty($agent['os']) ? 'N/A' : $agent['os'];
			$_SESSION['SECURITY']['UA_ENGINE'] = empty($agent['engine']) ? 'N/A' : $agent['engine'];
			if (empty($agent) && !$_SESSION['SECURITY']['banned'] && $MAIN_CFG['_security']['uas']) {
				$_SESSION['SECURITY']['banned'] = 802;
			}
		}

		define('SEARCHBOT', ($_SESSION['SECURITY']['UA'] == 'bot') ? $_SESSION['SECURITY']['nick'] : false);

		if (!empty($_SESSION['SECURITY']['banned'])) { cpg_error('', $_SESSION['SECURITY']['banned']); }
	}

	public static function check()
	{
		if ($_SESSION['SECURITY']['banned']) { return; }
		global $MAIN_CFG;
		# anti-flood protection
		if ($MAIN_CFG['_security']['flooding'] && SEARCHBOT != 'Google') {
			Security::_flood();
		}
	}

	public static function check_post()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') { return false; }
		global $module_name;
		if ($_SESSION['SECURITY']['page'] != $module_name) { cpg_error(_SEC_ERROR, _ERROR_BAD_LINK); }
		return true;
	}

	public static function check_domain($domain)
	{
		if (!preg_match('#[^\./]+\.[\w]+($|/)#', $domain)) { return false; }
		$domains = '';
		global $db, $prefix;
		if ($result = $db->query('SELECT ban_string FROM '.$prefix."_security WHERE ban_type IN (3,4)", TRUE, TRUE)) {
			while ($e = $db->fetch_array($result, SQL_NUM)) { $domains .= "|$e[0]"; }
		}
		if (empty($domains)) { return true; }
		return (preg_match('#('.str_replace('.', '\.', substr($domains,1).')#i'), $domain) < 1);
	}

	public static function check_email(&$email)
	{
		static $domains;
		if (strlen($email) < 6) return 0;
		$email = strtolower($email);
		# Although RFC 1035 doesn't allow 1 char subdomains we
		# allow it due to bug report 641
		if (!preg_match('#^[\w\.\+\-]+@(([\w]{1,25}\.)?[0-9a-z\-]{2,63}\.[a-z]{2,6}(\.[a-z]{2,6})?)$#', $email, $domain)) {
			return -1;
		}
		if (empty($domains)) {
			$domains = 'domain.tld';
			global $db, $prefix;
			if ($result = $db->query('SELECT ban_string FROM '.$prefix."_security WHERE ban_type IN (2,4)", TRUE, TRUE)) {
				while ($e = $db->fetch_array($result, SQL_NUM)) { $domains .= "|$e[0]"; }
			}
			$domains = '#('.str_replace('.', '\.', $domains).')#i';
		}
		if (preg_match($domains, $domain[1], $match)) {
			$email = array($email, $match[1]);
			return -2;
		}
		return 1;
	}

	public static function get_ip()
	{
		static $visitor_ip;
		if (!empty($visitor_ip)) { return $visitor_ip; }
		$visitor_ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : $_ENV['REMOTE_ADDR'];
		$ips = array();
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
			$ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		if (!empty($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
			$ips[] = $_SERVER['HTTP_CLIENT_IP'];
		}
		for ($i = 0; $i < count($ips); $i++) {
			$ips[$i] = trim($ips[$i]);
			# IPv4
			if (strpos($ips[$i], '.') !== FALSE) {
				# check for a hybrid IPv4-compatible address
				$pos = strrpos($ips[$i], ':');
				if ($pos !== FALSE) { $ips[$i] = substr($ips[$i], $pos+1); }
				# Don't assign local network ip's
				if (preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $ips[$i]) &&
						!preg_match('#^(10|127.0.0|172.(1[6-9]|2\d|3[0-1])|192\.168)\.#', $ips[$i]))
			{
					$visitor_ip = $ips[$i];
					break;
				}
			}
			# IPv6
			else if (strpos($ips[$i], ':') !== FALSE) {
				# fix shortened ip's
				$c = substr_count($ips[$i], ':');
				if ($c < 7) { $ips[$i] = str_replace('::', str_pad('::', 9-$c, ':'), $ips[$i]); }
				if (preg_match('#^([0-9A-F]{0,4}:){7}[0-9A-F]{0,4}$#i', $ips[$i])) {
					$visitor_ip = $ips[$i];
					break;
				}
			}
		}
		if (!function_exists('inet_pton')) { require(CORE_PATH.'functions/inet.php'); }
		$visitor_ip = inet_pton($visitor_ip);
		return $visitor_ip;
	}

	public static function _flood()
	{
		global $db, $prefix, $MAIN_CFG;
		$ip = Security::get_ip();
		$ipn = $db->binary_safe($ip);
		$delay = $MAIN_CFG['_security']['delay'];
		$flood_time = $flood_count = 0;
		$log = null;
		$gmtime = gmtime();
		if (!isset($_SESSION['SECURITY']['flood_start'])) {
			$db->query('DELETE FROM '.$prefix.'_security_flood WHERE flood_time <= '.$gmtime);
		} else {
			$_SESSION['SECURITY']['flood_start'] = false;
		}

		if ($MAIN_CFG['_security']['debug'] || empty($_SESSION['SECURITY']['flood_time'])) {
			# try to load time from log
			if ($row = $db->sql_ufetchrow('SELECT * FROM '.$prefix.'_security_flood WHERE flood_ip='.$ipn, SQL_ASSOC)) {
				if (!empty($row)) {
					$flood_time = $row['flood_time'];
					$flood_count = $row['flood_count'];
					if (!empty($row['log']) && $MAIN_CFG['_security']['debug']) {
						$log = unserialize($row['log']);
					}
				}
			}
		} else {
			$flood_time = $_SESSION['SECURITY']['flood_time'];
			$flood_count = $_SESSION['SECURITY']['flood_count'];
		}
		if ($flood_time >= $gmtime) {
			# die with message and report
			++$flood_count;
			if ($flood_count <= 5) {
				if (empty($_SESSION['SECURITY']['shield']) && $flood_count > 2 && $flood_count <= 5) {
					Security::_flood_log($ipn, !empty($row), $delay, $gmtime, $log, $flood_count);
					global $LNG;
					get_lang('errors');
					$flood_time = (($flood_count+1)*2)/$delay;
					header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable');
					header('Retry-After: '.$flood_time);
					$msg = sprintf($LNG['_SECURITY_MSG']['_FLOOD'], $flood_time);
					if ($flood_count == 5) { $msg .= $LNG['_SECURITY_MSG']['Last_warning']; }
					cpg_error($msg, 'Flood Protection');
				}
			} else {
				if ($MAIN_CFG['_security']['debug']) {
					if (!empty($log)) { $log = Security::_log_serializer($log); }
					else if (!empty($_SESSION['FLOODING'])) { $log = Security::_log_serializer($_SESSION['FLOODING']); }
					$log = "'".$log."'";
					if (!empty($_SESSION['SECURITY']['shield'])) {
						if ($_SESSION['SECURITY']['shield'] == 4) {
							list(,$ip4) = unpack('N',$ip);
							$db->sql_query('UPDATE '.$prefix."_security SET log='$log' WHERE ban_type=8 AND (ban_ipv4_s = $ip4 OR (ban_ipv4_s < $ip4 AND ban_ipv4_e >= $ip4))");
						} else {
							$mac = (strlen($ip) == 16) ? ' OR ban_ipn='.$db->binary_safe(substr($ip,-8)) : '';
							$db->sql_query('UPDATE '.$prefix."_security SET log='$log' WHERE ban_type=8 AND (ban_ipn=$ipn$mac)");
						}
						$flood_time = $_SESSION['SECURITY']['flood_time'] = 0;
						$flood_count = $_SESSION['SECURITY']['flood_count'] = 0;
						return;
					}
				} else {
					$log = 'DEFAULT';
				}
				$db->query('INSERT INTO '.$prefix."_security (ban_ipn, ban_type, ban_time, ban_details, log) VALUES ($ipn, '7', '".($gmtime+$MAIN_CFG['_security']['bantime'])."', 'Flooding detected by User-Agent:\n{$_SERVER['HTTP_USER_AGENT']}', '$log')", TRUE, TRUE);
				global $SESS;
				if (is_object($SESS)) $SESS->destroy();
				cpg_error('', 803);
			}
		} else {
			$log = null;
			$flood_count = 0;
			$_SESSION['FLOODING'] = array();
		}
		Security::_flood_log($ipn, !empty($row), $delay, $gmtime, $log, $flood_count);
	}

	public static function _detectBot($where=false)
	{
		global $db, $prefix;
		$bot = false;
		# Identify bot by UA
		$where = ($where ? " WHERE agent_name LIKE '$where%'" : '');
		$result = $db->query('SELECT agent_name, agent_fullname, agent_ban FROM '.$prefix."_security_agents$where ORDER BY agent_name", TRUE, TRUE);
		while ($row = $db->fetch_array($result, SQL_NUM)) {
			if (empty($row[1])) { continue; }
			if ($bot && empty($where)) {
				break;
			} else if (preg_match(preg_quote($row[1]), $_SERVER['HTTP_USER_AGENT'])) {
				$bot = $row;
			}
		}
		$db->free_result($result);
		return ($bot === false) ? false : array('ua' => 'bot', 'bot' => $bot[0], 'engine' => 'bot', 'banned' => (($bot[2] == -1) ? 410 : null));
	}

	public static function _flood_log($ip, $update=false, $delay, $gmtime, $log, $times)
	{
		global $MAIN_CFG;
		$timeout = ((($times+1)*2)/$delay)+$gmtime;
		# maybe the UA doesn't accept cookies so we use another session log as well
		if ($MAIN_CFG['_security']['debug'] || empty($_SESSION['SECURITY']['flood_time'])) {
			global $db, $prefix;
			if ($MAIN_CFG['_security']['debug'] && $log) { $log = "'".Security::_log_serializer(Security::_data_log($times, $log))."'"; }
			else { $log = 'DEFAULT'; }
			if ($update) {
				$db->query('UPDATE '.$prefix."_security_flood SET flood_time='$timeout', flood_count='$times', log='$log' WHERE flood_ip=$ip");
			} else {
				$db->query('INSERT INTO '.$prefix."_security_flood (flood_ip,flood_time,flood_count,log) VALUES ($ip, '$timeout', '$times', $log)");
			}
			$_SESSION['SECURITY']['flood_start'] = true;
		} 
		$_SESSION['SECURITY']['flood_time'] = $timeout;
		$_SESSION['SECURITY']['flood_count'] = $times;
	}

	public static function _detectProxy()
	{
		if (SEARCHBOT) { return $_SESSION['SECURITY']['nick']; }
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
		if (!empty($_SERVER['VIA'])) return $_SERVER['VIA'];
		$rating = 0;
		if ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0') $rating += 1;
		if ($_SERVER['HTTP_ACCEPT'] == '*/*') $rating += 3;
		if (intval($_SERVER['REMOTE_PORT']) > 5000) $rating += 5;
		if (!$rating || $rating == 1) return 'None';
		if ($rating <= 4) $rating = 'Probably anonymous';
		else $rating = 'Yes, anonymous';
		return $rating;
	}

	public static function _data_log($c, $l)
	{
		$l[$c]['S_TIME'] = gmtime();
		$l[$c]['S_USER'] = !empty($_SESSION['CPG_USER']) ? $_SESSION['CPG_USER']['username'] : '';
		$l[$c]['S_UA'] = $_SERVER['HTTP_USER_AGENT'];
		$l[$c]['S_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
		$l[$c]['S_METHOD'] = $_SERVER['REQUEST_METHOD'];
		$l[$c]['S_URI'] = $_SERVER['REQUEST_URI'];
		$l[$c]['S_REFERER'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		# if proxy is behind a firewall then bypass-client will contain the firewall ip 
		$l[$c]['S_CACHE_CONTROL'] = !empty($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : '';
		$l[$c]['S_PROXY'] = Security::_detectProxy();
		$_SESSION['FLOODING'][$c] = $l[$c];
		return $l;
	}

	public static function _log_serializer($log)
	{
		for($i=0; $i<(is_countable($log) ? count($log) : 0); ++$i) {
			foreach ($log[$i] as $key => $val) {
				$log[$i][$key] = Fix_Quotes($val, true);
			}
		}
		return serialize($log);
	}
}
