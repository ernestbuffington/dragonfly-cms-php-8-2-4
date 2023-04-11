<?php
/*
	Dragonfly™ CMS, Copyright © since 2012
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

/*
0:200 = ip:shield
0:203 = ip
0:204 = unknown user-agent
0:800 = ip:bad
0:802 = not compilant user-agent
0:803 = ip:flood
1:200 = bot:verified
1:203 = bot:
1:800 = bot:bad
1:802 = bot:empty
1:803 = bot:flood
3:801 = referer:bad
4:801 = email, referer:bad (deprecated)
6:200 = mac:verified-
6:800 = mac:bad-
9:200 = hostname:verified
9:800 = hostname:bad
10:800 = in_dns_bl

Tables:
	security_agents
	security_cache
	security_domains
	security_flood
	security_ips
*/

class Security
{
	const
		TYPE_DOMAIN_BLOCKED          = 2,
		TYPE_DOMAIN_REFERER_BLOCKED  = 3,
		TYPE_DOMAIN_PROTECTED        = 8,
		TYPE_DOMAIN_HOSTNAME_BLOCKED = 9,

		TYPE_IP_BLOCKED   = 0,
		TYPE_IP_PROTECTED = 8;

	public static function isTorExitNode($ip /*=$_SERVER['REMOTE_ADDR']*/)
	{
		// https://www.torproject.org/projects/tordnsel.html.en
		$rev_ip = implode('.',array_reverse(explode('.',$ip)));
		$s_addr = implode('.',array_reverse(explode('.',$_SERVER['SERVER_ADDR'])));
		return ('127.0.0.2' == Poodle\INET::getHostIP("{$rev_ip}.{$_SERVER['SERVER_PORT']}.{$s_addr}.ip-port.exitlist.torproject.org"));
	}

	public static function banIP($ip, $details)
	{
		if ($ip = \Dragonfly\Net::filterIP($ip)) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$ip = $SQL->escapeBinary($ip['ipn']);
			try {
				$SQL->exec("INSERT INTO {$SQL->TBL->security_ips} (ipn_s, type, details) VALUES ({$ip}, 0, {$SQL->quote($details)})");
			} catch (\Exception $e) {}
			$SQL->query("DELETE FROM {$SQL->TBL->security_cache} WHERE ipn={$ip}");
		}
	}

	public static function init()
	{
		if (false !== stripos($_SERVER['REQUEST_URI'], 'modules.php?name=Your_Account&op=new_user')
		 || preg_match('#/((administrator|bitrix|editor|fck|ckeditor|tiny_?mce|trackback|phpmyadmin|phpthumb|wp-admin)/|wp-login\\.php)#i',$_SERVER['PATH_INFO']))
		{
			if (!is_user()) {
				static::banIP($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI']);
			}
			exit('Yoda: bad bot, you are!');
		}

		# Show error page if the http server sends an error
		if (isset($_SERVER['REDIRECT_STATUS']) && between($_SERVER['REDIRECT_STATUS'], 400, 503)) {
			cpg_error('', $_SERVER['REDIRECT_STATUS']);
		}
		if (isset($_SESSION['SECURITY']['status']) && 300 < $_SESSION['SECURITY']['status']) {
			cpg_error('', $_SESSION['SECURITY']['status']);
		}

		$ua = \Poodle\UserAgent::getInfo();
		$MAIN_CFG = \Dragonfly::getKernel()->CFG;
		if (\Dragonfly::getKernel()->SESSION->is_new()) {
			$db = \Dragonfly::getKernel()->SQL;
			$time = time();
			$expire = $time + $MAIN_CFG->_security->cachettl;
			$ipn = \Dragonfly\Net::ipn();
			$data = array(
				'ipn' => $ipn,
				'status' => 203,
				'type' => 0,
				'ttl' => $expire,
				'log' => empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT']
			);

			$tmp = $db->uFetchAssoc('SELECT status, name, hostname, type, ttl FROM '.$db->TBL->security_cache.' WHERE ipn='.$db->escapeBinary($ipn));
			if ($cached = !!$tmp) {
				if (intval($tmp['ttl']) < $time) {
					$cached = 0;
					$db->query("DELETE FROM {$db->TBL->security_cache} WHERE ttl < {$time}");
//					$db->optimize($db->TBL->security_cache);
					$data = array_merge($data, static::exec());
				} else {
					$data = array_merge($data, $tmp);
					if (200 == $data['status']) {
						$db->query("UPDATE {$db->TBL->security_cache} SET ttl={$expire} WHERE ipn=".$db->escapeBinary($ipn));
					}
				}
				$tmp = null;
			} else {
				$data = array_merge($data, static::exec());
			}
			if (1 == $data['type'] && !$ua->bot) {
				$ua->bot = true;
				$ua->name = $data['name'];
			}
//			$ua->verified = (200 == $data['status']);

			if (!$cached) {
				if (!empty($data['name']))     { $data['name'] = $db->quote($data['name']); }
				if (!empty($data['hostname'])) { $data['hostname'] = $db->quote($data['hostname']); }
				$data['ipn'] = $db->escapeBinary($data['ipn']);
				$data['log'] = $db->quote($data['log']);
				$db->TBL->security_cache->insertPrepared($data);
			}
			$_SESSION['SECURITY']['status'] = $data['status'];

			if (!$ua->bot && $MAIN_CFG->_security->deny_tor && static::isTorExitNode($_SERVER['REMOTE_ADDR'])) {
				$SESS = \Dragonfly::getKernel()->SESSION;
				if (is_object($SESS)) { $SESS->delete(); }
				cpg_error('Tor is banned, sorry someone abused the network for you', 'Tor is banned, sorry someone abused the network for you');
			}
		}
		define('SEARCHBOT', $ua->bot ? $ua->name : false);

		if (!empty($_SESSION['SECURITY']['status']) && 300 < $_SESSION['SECURITY']['status']) {
			cpg_error('', $_SESSION['SECURITY']['status']);
		}
	}

	private static function exec()
	{
		$db = \Dragonfly::getKernel()->SQL;
		$MAIN_CFG = \Dragonfly::getKernel()->CFG;
		$ip = \Dragonfly\Net::ip();
		$ipn = $db->escapeBinary(inet_pton($ip));

		if ($MAIN_CFG->_security->ips) {
			if ($row = $db->uFetchAssoc("SELECT type, details FROM {$db->TBL->security_ips} WHERE ipn_s={$ipn} OR (ipn_s < {$ipn} AND ipn_e >= {$ipn})")) {
				if (0 == $row['type']) return array('status' => 800, 'log' => $row['details']);
				if (8 == $row['type']) return array('status' => 200, 'log' => $row['details']);
			}
		}

		$data = array();
		$bot = $reverse = false;
		if ($MAIN_CFG->_security->hostnames || $MAIN_CFG->_security->bots) {
			if (empty($_SERVER['REMOTE_HOST'])) {
				$reverse = \Dragonfly\Net\Dns::reverse($ip);
				if (is_array($reverse) && !empty($reverse['hostname'])) {
					$data['hostname'] = $reverse['hostname'];
				}
			} else {
				$data['hostname'] = $_SERVER['REMOTE_HOST'];
			}
		}
		if (!empty($data['hostname']) && $MAIN_CFG->_security->hostnames && $domain = static::detectDomain(substr($data['hostname'], 0, -1))) {
			$data['type'] = 9;
			if (!empty($domain['ban_string'])) {
				$data['log'] = $domain['ban_string'];
			}
			if (9 == $domain['ban_type']) {
				$data['status'] = 800;
				return $data;
			} else if (8 == $domain['ban_type'] && $reverse['verified']) {
				$data['status'] = 200;
				return $data;
			}
		}
		if ($bot = static::detectBot()) {
			$data['type'] = 1;
			$data['name'] = $bot['agent_name'];
			if ($MAIN_CFG->_security->bots) {
				if (-1 == $bot['agent_ban']) {
					$data['status'] = 802;
					return $data;
				} else if (!empty($data['hostname']) && !empty($bot['agent_hostname']) && $reverse['verified']) {
					if (false === strpos($bot['agent_hostname'], '\\')) $bot['agent_hostname'] = preg_quote($bot['agent_hostname'],'#');
					if (preg_match('#'.$bot['agent_hostname'].'\.$#i', $data['hostname'])) {
						$data['status'] = 200;
						return $data;
					}
				}
			}
		} else if ('SERVFAIL' === $reverse) {
			$data['ttl'] = time() + ($MAIN_CFG->_security->cachettl - 60);
		} else if ('NXDOMAIN' === $reverse /* && $MAIN_CFG->_security->emptyhost*/) {
			$data['log'] = 'Empty domain';
			//$data['status'] = 802;
		}

		if (!$bot && !\Poodle\UserAgent::getInfo()->name) {
			$data['status'] = 204;
			if ($MAIN_CFG->_security->uas && (empty($_SERVER['HTTP_USER_AGENT']) || !preg_match('#^[a-zA-Z]#', $_SERVER['HTTP_USER_AGENT']))) {
				$data['status'] = 802;
				$data['ttl'] = time() + $MAIN_CFG->_security->bantime;
				return $data;
			}
		}

		# Check for dns blacklisted IPs
		if ($MAIN_CFG->_security->dns_bl_active && $bl = static::dns_blocklist($ip)) {
			$data['type'] = 10;
			$data['status'] = 800;
			$data['ttl'] = time() + $MAIN_CFG->_security->bantime;
			$data['log'] = $bl;
			return $data;
		}

		# Referer spam?
		if ($MAIN_CFG->_security->referers
		    && !empty($_SERVER['HTTP_REFERER'])
		    && false === strpos($_SERVER['HTTP_REFERER'], $MAIN_CFG->server->domain)
		    && !static::check_domain($_SERVER['HTTP_REFERER']))
		{
			$data['status'] = 801;
			$data['type'] = 3;
			$data['ttl'] = time() + $MAIN_CFG->_security->bantime;
		}
		return $data;
	}

	public static function check()
	{
		# anti-flood protection
		if (\Dragonfly::getKernel()->CFG->_security->flooding && 200 != $_SESSION['SECURITY']['status']) {
			\Dragonfly\Security\Flooding::detect();
		}
	}

	public static function check_post()
	{
		if ('POST' !== $_SERVER['REQUEST_METHOD']) { return false; }
		global $Module;
		if (defined('ADMIN_PAGES')) {
			if (empty($_SESSION['SECURITY']['page']) || $Module->name != $_SESSION['SECURITY']['page']) {
				cpg_error(_ERROR_BAD_LINK, _SEC_ERROR, URL::admin());
			}
		} else {
			if (empty($_SESSION['CPG_SESS']['user']['page']) || $Module->name != $_SESSION['CPG_SESS']['user']['page']) {
				cpg_error(_ERROR_BAD_LINK, _SEC_ERROR, URL::index());
			}
		}
		return true;
	}

	public static function check_domain($domain)
	{
		if (!preg_match('#[^\./]+\.[\w]+($|/)#', $domain)) { return false; }
		$domains = '';
		$db = \Dragonfly::getKernel()->SQL;
		if ($result = $db->query("SELECT ban_string FROM {$db->TBL->security_domains} WHERE ban_type IN (3,4)", TRUE, TRUE)) {
			while ($e = $result->fetch_row()) { $domains .= "|{$e[0]}"; }
		}
		if (empty($domains)) { return true; }
		return (preg_match('#('.str_replace('.', '\.', substr($domains,1).')#i'), $domain) < 1);
	}

	public static function get_ip()
	{
		trigger_deprecated('Use \\Dragonfly\\Net::ipn() instead.');
		return \Dragonfly\Net::ipn();
	}

	public static function dns_blocklist($ip)
	{
		$MAIN_CFG = \Dragonfly::getKernel()->CFG;
		if (!$MAIN_CFG->_security->dns_bl_active || !$ip = \Dragonfly\Net::filterIP($ip, false)) return false;
		if ($ip['v4']) {
			$ip = implode('.', array_reverse(explode('.', $ip['ip']))).'.';
		} else {
			$ip = unpack('H*', $ip['ipn']);
			$ip = implode('.', array_reverse(str_split($ip[1]))).'.';
		}
		for ($i=1; $i<=3; ++$i) {
			$whitelist = array();
			if (!$bl = $MAIN_CFG->_security->{"dns_bl_srv_{$i}"}) continue;
			$response = \Dragonfly\Net\Dns::resolve($ip.$bl);
			if (is_array($response) && isset($response['A'][$ip.$bl.'.'][0])) {
				$response = $response['A'][$ip.$bl.'.'][0];
			} else {
				continue;
			}
			if (!$exclude = $MAIN_CFG->_security->{"dns_bl_exc_{$i}"}) return "{$bl} ({$response})";
			if (0 === strpos($exclude, 'b:')) {
				$exclude = intval(substr($exclude, 2));
				$ret = explode('.', $response);
				if ((255 - $exclude) & intval($ret[3])) return "{$bl} ({$response})";
			} else {
				$ret = array_map('trim', explode(',', $exclude));
				if ($ret && !in_array($response, $ret)) return "{$bl} ({$response})";
			}
		}
		return false;
	}

	private static function detectBot()
	{
		if (empty($_SERVER['HTTP_USER_AGENT'])) return;
		$db = \Dragonfly::getKernel()->SQL;
		$bot = false;
		# Identify bot by UA
		$result = $db->query('SELECT agent_name, agent_fullname, agent_ban, agent_hostname FROM '.$db->TBL->security_agents);
		while ($row = $result->fetch_assoc()) {
			if ($row['agent_fullname'] && preg_match('#'.preg_quote($row['agent_fullname'],'#').'#i', $_SERVER['HTTP_USER_AGENT'])) {
				$bot = $row;
				break;
			}
		}
		$ua = \Poodle\UserAgent::getInfo();
		if ($ua->bot) {
			if (!$bot) { $bot = array('agent_ban' => 0); }
			$bot['agent_name'] = $ua->name;
		}
		return $bot;
	}

	public static function detectDomain($hostname)
	{
		if (!\Dragonfly\Net::validHostname($hostname)) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		$domain = false;
		$result = $db->query("SELECT ban_string, ban_type FROM {$db->TBL->security_domains} WHERE ban_type IN (8,9)");
		while ($row = $result->fetch_assoc()) {
			if (!$row['ban_string']) {
				continue;
			}
			if (false === strpos($row['ban_string'], '\\')) {
				$row['ban_string'] = preg_quote($row['ban_string'],'#');
			}
			if (preg_match('#'.$row['ban_string'].'$#i', $hostname)) {
				$row['ban_string'] = str_replace('\\', '', $row['ban_string']);
				$domain = $row;
				break;
			}
		}
		return $domain;
	}

}
