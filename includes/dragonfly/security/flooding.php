<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Security;

class Flooding
{

	public static function detect()
	{
		if (preg_match('/\\.(jpe?g|png|gif|css|js)$/Di', $_SERVER['PATH_INFO'])) {
			return;
		}
		$db = \Dragonfly::getKernel()->SQL;
		$ip = inet_pton($_SERVER['REMOTE_ADDR']);
		$qip = $db->quoteBinary($ip);
		$tbl = $db->TBL->security_flood;
		$CFG = \Dragonfly::getKernel()->CFG->_security;
		/**
		 * 5 requests per $CFG->delay seconds
		 */
		if ($row = $db->uFetchAssoc("SELECT * FROM {$tbl} WHERE flood_ip = {$qip}")) {
			if ($row['flood_time'] >= time()) {
				# die with message and report
				if (++$row['flood_count'] <= 5) {
					unset($row['flood_ip']);
					$row['log'] = $row['log'] ? json_decode($row['log'], true) : array();
					$row['log'] = $db->quote($CFG->debug ? \Dragonfly::dataToJSON(static::data_log($row['flood_count'], $row['log'])) : '');
					if ($row['flood_count'] > 2) {
						$row['flood_time'] += 1;
						$tbl->updatePrepared($row, "flood_ip = {$qip}");
						$LNG = \Dragonfly::getKernel()->L10N;
						$LNG->load('errors');
						\Poodle\HTTP\Status::set(503);
						$floodtime = $row['flood_time'] - time();
						header('Retry-After: '.$floodtime);
						$msg = sprintf($LNG['_SECURITY_MSG']['_FLOOD'], $floodtime);
						if (5 == $row['flood_count']) {
							$msg .= $LNG['_SECURITY_MSG']['Last_warning'];
						}
						cpg_error($msg, 'Flood Protection');
					} else {
						$tbl->updatePrepared($row, "flood_ip = {$qip}");
					}
				} else {
					if (6 == $row['flood_count']) {
						$row['log'] = $row['log'] ? json_decode($row['log'], true) : array();
						$row['log'] = \Dragonfly::dataToJSON(static::data_log($row['flood_count'], $row['log']));
						\Poodle\LOG::error('flooding', $row['log']);
						$tbl->updatePrepared(array('flood_count' => 'flood_count + 1'), "flood_ip = {$qip}");
					}
					$log = $db->quote(($CFG->debug && $row['log']) ? $row['log'] : '');
					$ttl = time() + $CFG->bantime;
					$db->query("UPDATE {$db->TBL->security_cache} SET status=803, ttl={$ttl}, log={$log} WHERE ipn={$qip}");
					$SESS = \Dragonfly::getKernel()->SESSION;
					if (is_object($SESS)) { $SESS->delete(); }
					cpg_error('', 803);
				}
			} else {
				$tbl->updatePrepared(array(
					'flood_time'  => $CFG->delay + time(),
					'flood_count' => 0,
					'log'         => $db->quote($CFG->debug ? \Dragonfly::dataToJSON(static::data_log(0, array())) : '')
				), "flood_ip = {$qip}");
			}
		} else {
			$tbl->insertPrepared(array(
				'flood_ip'    => $qip,
				'flood_time'  => $CFG->delay + time(),
				'flood_count' => 0,
				'log'         => $db->quote($CFG->debug ? \Dragonfly::dataToJSON(static::data_log(0, array())) : '')
			));
		}
	}

	private static function data_log($c, $l)
	{
		$l[$c]['TIME']    = time();
		$l[$c]['USER']    = empty($_SESSION['DF_VISITOR']) ? '' : $_SESSION['DF_VISITOR']->identity->nickname;
		$l[$c]['UA']      = $_SERVER['HTTP_USER_AGENT'];
		$l[$c]['METHOD']  = $_SERVER['REQUEST_METHOD'];
		$l[$c]['URI']     = $_SERVER['REQUEST_URI'];
		$l[$c]['REFERER'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		# if proxy is behind a firewall then bypass-client will contain the firewall ip
		$l[$c]['PROXY']   = self::detectProxy();
		return $l;
	}

	private static function detectProxy()
	{
		if (SEARCHBOT) { return SEARCHBOT; }
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
		if (!empty($_SERVER['VIA'])) return $_SERVER['VIA'];
		$rating = 0;
		if ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0') $rating += 1;
		if (!isset($_SERVER['HTTP_ACCEPT']) || $_SERVER['HTTP_ACCEPT'] == '*/*') $rating += 3;
//		if (intval($_SERVER['REMOTE_PORT']) > 5000) $rating += 5;
		if (!$rating || $rating == 1) return 'None';
		if ($rating <= 4) $rating = 'Probably anonymous';
		else $rating = 'Yes, anonymous';
		return $rating;
	}

}
