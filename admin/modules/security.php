<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
/*
	Future dev notes regarding IPv6
	- http://ietf.org/rfc/rfc2374.txt
	- http://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing
	- IPv6 addresses are usually composed of two logical parts:
	  64-bit network prefix, and a 64-bit host-addressing part (MAC)
	| 3|  13 | 8 |   24   |   16   |          64 bits               |
	+--+-----+---+--------+--------+--------------------------------+
	|FP| TLA |RES|  NLA   |  SLA   |         Interface ID           |
	|  | ID  |   |  ID    |  ID    |                                |
	+--+-----+---+--------+--------+--------------------------------+
     | Routing Prefix   | Subnet |
		 +------------------+--------+
	Where
		FP           Format Prefix (001)
		TLA ID       Top-Level Aggregation Identifier
		RES          Reserved for future use
		NLA ID       Next-Level Aggregation Identifier
		SLA ID       Site-Level Aggregation Identifier
		INTERFACE ID Interface Identifier
	- So the most interesting types for banning is:
	  aaaa:aabb:bbbb:cccc:zzzz:zzzz:zzzz:zzzz
		a 3 bytes TLA (routing) http://sixxs.net/tools/grh/dfp/
		b 3 bytes NLA organizations (may have sub-levels)
		c 2 bytes SLA individual organization subnets (may have sub-levels)
		z The last 8 bytes (although it can be spoofed)
*/

if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('security')) { die('Access Denied'); }

\Dragonfly\Page::title('Security');

$page = $_GET->uint('page');
$page_url = $page ? '&page='.$page : '';
$page = max(1, $page);
$per_page = 30;
$limit = "LIMIT {$per_page} OFFSET ".(($page-1)*$per_page);

/*
0:200 = ip:shield
0:203 = ip
0:800 = ip:bad
0:802 = unknow user-agent
0:803 = ip:flood
1:200 = bot:verified
1:203 = bot:
1:802 = bot:bad
1:803 = bot:flood
3:801 = referer:bad
4:801 = email, referer:bad (deprecated)
6:200 = mac:verified
6:800 = mac:bad
9:200 = hostname:verified
9:800 = hostname:bad
10:800 = in_dns_bl

dnsbl.tornevall.org/?do=usage
1	  Proxy has been scanned
2	  Proxy is working
4	  Will be changed!-- Tagged as proxy from Blitzed (R.I.P) See SFS for more information
8	  Proxy was tested, but timed out on connection
16	Proxy was tested but failed at connection
32	Proxy was tested but the IP was different to the one connected at (Including TOR)
64  IP marked as "abusive host". Primary target is web-form spamming (Includes dnsbl_remote)
128	Proxy has a different anonymous-state (web-based proxies, like anonymouse, etc)
*/

function get_cache_type(array $row)
{
	$code = $row['type'].':'.$row['status'];
	$ret = array('?' => '', 'bg' => '', 'op' => '', 'opval' => '');
	switch ($code):
		case '0:200':
		case '0:800':
			$ret['?'] = htmlprepare($row['log']);
			break;
		case '0:802':
			$ret['?'] = 'unknow user-agent';
			break;
		case '0:803':
			$ret['?'] = 'ip:flood';
			break;
		case '1:802':
			$ret['?'] = 'bot:bad';
			break;
		case '1:803':
			$ret['?'] = 'bot:flood';
			break;
		case '3:801':
			$ret['?'] = 'referer:bad';
			break;
		case '4:801':
			$ret['?'] = 'referer:bad (deprecated)';
			break;
		case '6:800':
			$ret['?'] = 'mac:bad';
			break;
		case '6:803':
			$ret['?'] = 'mac:flood';
			break;
		case '9:200':
			$ret['?'] = 'host:shield';
			break;
		case '9:800':
			$ret['?'] = 'host:bad';
			break;
		case '9:803':
			$ret['?'] = 'host:flood';
			break;
		case '10:800':
			$ret['?'] = htmlprepare($row['log']);
			break;
	endswitch;

	if (200 == $row['status']) $ret['bg'] = 'bg-ok';
	else if (800 <= $row['status']) $ret['bg'] = 'bg-error';
	if (between($row['status'], 802, 803)) $ret['op'] = 'logs';

	return $ret;
}

function displaySecurityTPL($name = 'list')
{
	$OUT = \Dragonfly::getKernel()->OUT;
	require_once 'header.php';
	OpenTable();
	$OUT->display('submenu_inline');
	if ($name) { $OUT->display($name); }
	CloseTable();
}

$firewall = new stdClass();
$firewall->title = 'Dragonfly CMS Firewall';
$firewall->menu = array(
	'logs' => 'Logs',
	'ips' => 'IPs',
	'bots' => _BOTS,
	'hostnames' => 'Hostnames',
	'referers' => 'Referers',
	'emails' => 'e-Mails',
	'sessions' => 'Sessions'
);
\Dragonfly\Admin\Cp::sectionTitle($firewall->title);
\Dragonfly\Admin\Cp::sectionMenu('submenu_inline', $firewall->menu);

# Bots
$OUT = \Dragonfly::getKernel()->OUT;
if (isset($_GET['bots']))
{
	if (Security::check_post())
	{
		# Delete from tables
		if (isset($_POST['change']))
		{
			$db->update('security_agents',
				array('agent_ban'  => intval($_POST['change_to'])),
				array('agent_name' => $_POST['change'])
			);
			$db->delete('security_cache', "name='{$_POST['change']}'");
			$db->optimize($db->TBL->security_cache);
			URL::redirect(URL::admin('&bots'.$page_url));
		}
		else if (!empty($_POST['delete']))
		{
			$db->delete('security_agents',
				array('agent_name' => $_POST['delete'])
			);
			$db->optimize($db->TBL->security_agents);
			URL::redirect(URL::admin('&bots'.$page_url));
		}
		else if (!empty($_POST['save']))
		{
			# Insert new entry
			$_POST['ua_name'] = trim($_POST['ua_name']);
			$_POST['ua_fullname'] = trim($_POST['ua_fullname']);

			if (empty($_POST['ua_name']))     cpg_error(sprintf(_ERROR_NOT_SET, 'User agent name'));
			if (empty($_POST['ua_fullname']))	cpg_error(sprintf(_ERROR_NOT_SET, 'Detection string'));

			if (preg_match('#^(bot|other|crawler)s?$#i', $_POST['ua_name'])) cpg_error('The user agent name is reserved');
			if ('bot' == empty($_POST['ua_fullname'])) cpg_error(sprintf(_ERROR_NOT_SET, 'The user agent detection string is reserved'));

			if (!preg_match('#^[a-z0-9_\- ]+$#i', $_POST['ua_name']))
				cpg_error(sprintf(_ERROR_BAD_FORMAT, 'User Agent name'));
			if (!preg_match('#^[a-z0-9_\- \/\(\)]+$#i', $_POST['ua_fullname']))
				cpg_error(sprintf(_ERROR_BAD_FORMAT, 'User Agent string'));
			if (!empty($_POST['ua_hostname']) && !\Dragonfly\Net::validHostname($_POST['ua_hostname']))
				cpg_error(sprintf(_ERROR_BAD_FORMAT, 'User Agent hostname'));

			$ua_url = empty($_POST['ua_url']) ? null : trim($_POST['ua_url']);

			if (preg_match('#^http://#', $ua_url))
				$ua_url = substr($ua_url, 7);
			if (false === strpos($ua_url, '.'))
				cpg_error(sprintf(_ERROR_BAD_FORMAT, _URL));

				$db->insert('security_agents', array(
				'agent_name'     => $_POST['ua_name'],
				'agent_fullname' => $_POST['ua_fullname'],
				'agent_hostname' => !empty($_POST['ua_hostname']) ? $_POST['ua_hostname'] : null,
				'agent_url'      => $ua_url,
				'agent_ban'      => intval($_POST['ua_ban_type']),
				'agent_desc'     => !empty($_POST['ban_details']) ? $_POST['ban_details'] : null
			));
		}
		URL::redirect(URL::admin('&bots'.$page_url));
	}
	else if (!empty($_GET['bots']))
	{
		\Dragonfly\Page::title('Bot Details');
		$OUT->log = array();
		$bot = $db->escape_string($_GET['bots']);
		if ($row = $db->uFetchAssoc("SELECT * FROM {$db->TBL->security_agents} WHERE agent_name='{$bot}'")) {
			$OUT->assign_vars(array(
				'S_BOT_NAME' => $row['agent_name'],
				'S_BOT_UA' => $row['agent_fullname'],
				'S_BOT_DNS' => $row['agent_hostname'],
				'U_BOT_HOME' => $row['agent_url'] ? 'http://'.$row['agent_url'] : '',
				'S_BOT_DESC' => $row['agent_desc']
			));
		}
		displaySecurityTPL($row ? 'admin/security/bot_details' : false);
		return;
	}
	# Bots admin
	\Dragonfly\Page::title('Bots');
	$count = $db->count('security_agents');
	pagination('&bots&page=', ceil($count/$per_page), 1, $page, true, false);
	\Dragonfly\Admin\Cp::list_header(
		'list',
		'User Agent',
		'Status',
		'Options',
		''
	);
	if ($count && $result = $db->query("SELECT agent_name, agent_ban, agent_hostname FROM {$db->TBL->security_agents} ORDER BY agent_name {$limit}")) {
		while ($row = $result->fetch_row()) {
			$bg = !$row[2] ?: 'bg-ok';
			\Dragonfly\Admin\Cp::list_value(array(
				array(
					'url' => URL::admin('&bots='.$row[0]),
					'text' => $row[0]
				),
				array(
					'text' => $row[1] ? 'Banned' : 'Allowed'
				),
				array(
					'quick' => 'change',
					'action' => URL::admin('&bots'.$page_url),
					'value' => $row[0],
					'change_to' => (0 > $row[1] ? 0 : -1),
					'text' => (0 > $row[1] ? 'Allow' : 'Ban')
				),
				array(
					'quick' => 'delete',
					'action' => URL::admin('&bots'.$page_url),
					'value' => $row[0],
					'text' => _DELETE
				)
			), $bg);
		}
	}
	displaySecurityTPL();
	return;
}

# General
else if (isset($_GET['general']))
{
	if (Security::check_post()) {
		$MAIN_CFG->set('_security', 'cachettl', $_POST->uint('cachettl')*86400);
		$MAIN_CFG->set('_security', 'bantime',  min(60, $_POST->uint('bantime')));
		$MAIN_CFG->set('_security', 'uas',      $_POST->bool('uas'));
		$MAIN_CFG->set('_security', 'deny_tor', $_POST->bool('deny_tor'));
		if (empty($_POST['dns']) || \Dragonfly\Net::validHostname($_POST['dns'], true) || \Dragonfly\Net::filterIP($_POST['dns'])) {
			$MAIN_CFG->set('server', 'dns', trim($_POST['dns']));
		}
	}
	URL::redirect(URL::admin());
}

# Domains
else if (isset($_GET['domains']))
{
	if (Security::check_post()) {
		$MAIN_CFG->set('_security', 'email',     $_POST->bool('emails'));
		$MAIN_CFG->set('_security', 'referers',  $_POST->bool('referers'));
		$MAIN_CFG->set('_security', 'hostnames', $_POST->bool('hostnames'));
		if (!empty($_POST['domain_name']) && (between($_POST['domain_type'], 2, 3) || between($_POST['domain_type'], 8, 9))) {
			$domain_name = $db->escape_string($_POST['domain_name']);
			$domain_type = intval($_POST['domain_type']);
			if (false === strpos($domain_name,'.')) { cpg_error(sprintf(_ERROR_BAD_FORMAT, 'Domain name')); }
			$db->query("INSERT INTO {$db->TBL->security_domains} (ban_string, ban_type) VALUES ('$domain_name', $domain_type)");
		}
	}
	URL::redirect(URL::admin());
}

# Hostnames
else if (isset($_GET['hostnames']))
{
	if (Security::check_post())
	{
		if (!empty($_POST['delete']))
		{
			$delete = $db->escape_string($_POST['delete']);
			$db->query("DELETE FROM {$db->TBL->security_domains} WHERE ban_string='$delete' AND ban_type IN (8,9)");
			$db->optimize($db->TBL->security);
		}
		else if (!empty($_POST['shield']) && preg_match('#^[a-z0-9\.\-]+$#i', $_POST['shield']))
		{
			$db->update('security_domains', array('ban_type'=>8), "ban_string='{$_POST['shield']}'");
		}
		else if (!empty($_POST['ban']) && preg_match('#^[a-z0-9\.\-]+$#i', $_POST['ban']))
		{
			$db->update('security_domains', array('ban_type'=>9), "ban_string='{$_POST['ban']}'");
		}
		URL::redirect(URL::admin('&hostnames'.$page_url));
	}
	\Dragonfly\Page::title('Hostnames');
	$count = $db->count('security_domains', 'ban_type IN (8,9)');
	pagination('&hostnames&page=', ceil($count/$per_page), 1, $page, true, false);
	\Dragonfly\Admin\Cp::list_header(
		'list',
		_NAME,
		'Status',
		'Options',
		''
	);
	if ($count && $result = $db->query("SELECT ban_string, ban_type FROM {$db->TBL->security_domains} WHERE ban_type IN (8,9) ORDER BY ban_string {$limit}")) {
		while ($row = $result->fetch_assoc()) {
			$bg = 8 == $row[1] ? 'bg-ok' : '';
			\Dragonfly\Admin\Cp::list_value(array(
				array(
					'text' => $row[0]
				),
				array(
					'text' => 9 == $row[1] ? 'Banned' : 'Shielded'
				),
				array(
					'quick' => 9 == $row[1] ? 'shield' : 'ban',
					'action' => URL::admin('&hostnames'.$page_url),
					'value' => $row[0],
					'text' => 9 == $row[1] ? 'Shield' : 'Ban'
				),
				array(
					'quick' => 'delete',
					'text' => _DELETE,
					'action' => URL::admin('&hostnames'.$page_url),
					'value' => urlencode($row[0])
				)
			), $bg);
		}
	}
	displaySecurityTPL();
	return;
}

# E-mails
else if (isset($_GET['emails']))
{
	if (Security::check_post()) {
		if (!empty($_POST['delete'])) {
			$delete = $db->escape_string($_POST['delete']);
			$db->query("DELETE FROM {$db->TBL->security_domains} WHERE ban_string='$delete' AND ban_type=2");
			$db->optimize($db->TBL->security);
		}
		URL::redirect(URL::admin('&emails'.$page_url));
	}
	\Dragonfly\Page::title('Email domains');
	$count = $db->count('security_domains', 'ban_type=2');
	pagination('&emails&page=', ceil($count/$per_page), 1, $page, true, false);
	\Dragonfly\Admin\Cp::list_header(
		'list',
		_NAME,
		'Status',
		'Options'
	);
	if ($count && $result = $db->query("SELECT ban_string FROM {$db->TBL->security_domains} WHERE ban_type=2 ORDER BY ban_string {$limit}")) {
		while ($row = $result->fetch_row()) {
			\Dragonfly\Admin\Cp::list_value(array(
				array(
					'text' => $row[0]
				),
				array(
					'text' => 'Banned'
				),
				array(
					'quick' => 'delete',
					'text' => _DELETE,
					'action' => URL::admin('&emails'.$page_url),
					'value' => urlencode($row[0])
				)
			));
		}
	}
	displaySecurityTPL();
	return;
}

# Floods
else if (isset($_GET['flooding']))
{
	if (Security::check_post()) {
		$MAIN_CFG->set('_security', 'delay', $_POST->uint('delay'));
		$MAIN_CFG->set('_security', 'debug', $_POST->bool('debug'));
	}
	URL::redirect(URL::admin());
}

# Logs
elseif (isset($_GET['logs']))
{
	if ('POST' == $_SERVER['REQUEST_METHOD'])
	{
		if (!empty($_POST['shield']) && $ip = \Dragonfly\Net::filterIP(trim($_POST['shield'])))
		{
			$ip = $db->escapeBinary($ip['ipn']);
			if ($db->exec("INSERT INTO {$db->TBL->security_ips} (ipn_s, type) VALUES ($ip, 8)")) {
				$db->query("DELETE FROM {$db->TBL->security_cache} WHERE ipn=$ip");
			}
			URL::redirect(URL::admin('&logs'.$page_url));
		}
		else if (!empty($_POST['ban']) && $ip = \Dragonfly\Net::filterIP(trim($_POST['ban'])))
		{
			$ip = $db->escapeBinary($ip['ipn']);
			if ($db->exec("INSERT INTO {$db->TBL->security_ips} (ipn_s, type) VALUES ($ip, 0)")) {
				$db->query("DELETE FROM {$db->TBL->security_cache} WHERE ipn=$ip");
			}
			URL::redirect(URL::admin('&logs'.$page_url));
		}
		else if (!empty($_POST['delete']) && $ip = \Dragonfly\Net::filterIP(trim($_POST['delete'])))
		{
			$ip = $db->escapeBinary($ip['ipn']);
			$db->query("DELETE FROM {$db->TBL->security_cache} WHERE ipn=$ip");
			$db->optimize($db->TBL->security);
			URL::redirect(URL::admin('&logs'.$page_url));
		}
		else {
			cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IP address'));
		}
	}
	else if (!empty($_GET['logs']))
	{
		$_GET['logs'] = trim($_GET['logs']);
		if (!$ip = \Dragonfly\Net::filterIP($_GET['logs']))
			cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IP address'));

		$ip = $db->escapeBinary($ip['ipn']);
		\Dragonfly\Page::title('Log details');
		$OUT->log = array();
		if ($row = $db->uFetchAssoc("SELECT * FROM {$db->TBL->security_cache} WHERE ipn=$ip")) {
			$cache = get_cache_type($row);
			$log = false;
			if (empty($row['log'])) {
				$row['log'] = '';
			} else if ($log = json_decode($row['log'], true)) {
				for ($i=0; $i<5; ++$i) {
					$log[$i]['TIME'] = \Dragonfly::getKernel()->L10N->date('DATE_F', $log[$i]['TIME']);
					$OUT->log[] = $log[$i];
				}
				$log = true;
			}

			$OUT->assign_vars(array(
				'S_BOT_NAME' => $row['name'] ?: inet_ntop($row['ipn']),
				'S_BOT_UA' => $log ? '' : $row['log'],
				'S_BOT_DNS' => $row['hostname'],
				'U_BOT_HOME' => '',
				'S_BOT_DESC' => $cache['?']
			));
		}
		displaySecurityTPL($row ? 'admin/security/bot_details' : false);
		return;
	}

	\Dragonfly\Page::title('Logs');
	$count = $db->count('security_cache');
	if ($page <= ceil($count/$per_page)) {
		pagination('&logs&page=', ceil($count/$per_page), 1, $page, true, false);
	}
	\Dragonfly\Admin\Cp::list_header(
		'list',
		'Details',
		'Expires in',
		'Options',
		'',
		''
	);
	$date = new DateTime('now', new DateTimeZone('UTC'));
	if ($count && $result = $db->query("SELECT * FROM {$db->TBL->security_cache} ORDER BY ttl {$limit}")) {
		while ($row = $result->fetch_assoc()) {
			$ip = $row['ip'] = inet_ntop($row['ipn']);
			$cache = get_cache_type($row);
			$cache['?'] = $cache['op'] ? '<a href="'.htmlspecialchars(URL::admin("&{$cache['op']}=".$ip))."\">{$cache['?']}</a>" : $cache['?'];

			$name = $ip .'<br /><strong>';
			$name .= $cache['?'].' '.$row['name'];
			$name .= '</strong> ' .htmlprepare($row['hostname']);
			$ttl = new DateTime();
			$ttl->setTimestamp((int) $row['ttl']);
			$ttl = $date->diff($ttl);
			if ($ttl->d) $ttl->h += $ttl->d * 24;

			\Dragonfly\Admin\Cp::list_value(array(
				array(
					'text' => $name
				),
				array(
					'text' => $ttl->invert ? 'Expired' : ( !$ttl->h ? $ttl->format('%im') : $ttl->format('%hh %im'))
				),
				array(
					'quick' => 'shield',
					'action' => URL::admin('&logs'.$page_url),
					'value' => $ip,
					'text' => 'Shield',
					'disabled' => 200 == $row['status']
				),
				array(
					'quick' => 'ban',
					'action' => URL::admin('&logs'.$page_url),
					'value' => $ip,
					'text' => 'Ban',
					'disabled' => between($row['status'], 800, 802)
				),
				array(
					'quick' => 'delete',
					'action' => URL::admin('&logs'.$page_url),
					'value' => $ip,
					'text' => _DELETE
				)
			), $cache['bg']);
		}
	}
	displaySecurityTPL();
	return;
}

# IPs
else if (isset($_GET['ips']))
{
	if (Security::check_post())
	{
		if (!empty($_POST['delete']) && $ip = \Dragonfly\Net::filterIP(trim($_POST['delete'])))
		{
			$ipn = $db->escapeBinary($ip['ipn']);
			$db->query("DELETE FROM {$db->TBL->security_ips} WHERE ipn_s=$ipn");
			$db->optimize($db->TBL->security_ips);
			URL::redirect(URL::admin('&ips'.$page_url));
		}
		else if (!empty($_POST['shield']) && $ip = \Dragonfly\Net::filterIP(trim($_POST['shield'])))
		{
			$ipn = $db->escapeBinary($ip['ipn']);
			$db->update('security_ips', array('type'=>8), 'ipn_s='.$ipn);
			URL::redirect(URL::admin('&ips'.$page_url));
		}
		else if (!empty($_POST['ban']) && $ip = \Dragonfly\Net::filterIP(trim($_POST['ban'])))
		{
			$ipn = $db->escapeBinary($ip['ipn']);
			$db->update('security_ips', array('type'=>0), 'ipn_s='.$ipn);
			URL::redirect(URL::admin('&ips'.$page_url));
		}
		else if (!empty($_POST['ip_s']))
		{
			# y.y.y.y/cidr?
			if (!$ip = \Dragonfly\Net::filterIPwCIDR(trim($_POST['ip_s']))) {
				$ipn_s = \Dragonfly\Net::filterIP(trim($_POST['ip_s']));
				if (!empty($_POST['ip_e'])) {
					$ipn_e = \Dragonfly\Net::filterIP(trim($_POST['ip_e']));
				}
			}
			if (!empty($ip['cidr'])) {
				$ipn_s = $ip['ipn_s'];
				$ipn_e = $ip['ipn_e'];
			} else {
				$ipn_s = $ipn_s['ipn'];
				$ipn_e = !empty($ipn_e) ? $ipn_e['ipn'] : null;
			}
			if ($ipn_e) {
				if ($ipn_s > $ipn_e) {
					$ip = $ipn_e;
					$ipn_s = $ipn_e;
					$ipn_e = $ip;
				}
			}
			$ipn_s = $db->escapeBinary($ipn_s);
			$ipn_e =  $ipn_e ? $db->escapeBinary($ipn_e) : 'DEFAULT';
			$details = !empty($_POST['details']) ? "'".$db->escape_string($_POST['details'])."'" : 'DEFAULT';
			$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
			$type = $type == 8 ? 8 : 0;
			$db->query("INSERT INTO {$db->TBL->security_ips} (ipn_s, ipn_e, type, details) VALUES ($ipn_s, $ipn_e, $type, $details)");
			URL::redirect(URL::admin('&ips'));
		}
		cpg_error('Nothing specified');
	}
	\Dragonfly\Page::title('IP\'s');
	$count = $db->count('security_ips');
	pagination('&ips&page=', ceil($count/$per_page), 1, $page, true, false);
	\Dragonfly\Admin\Cp::list_header(
		'list',
		'IP / IP Range',
		'Status',
		'Options',
		''
	);
	if ($count && $result = $db->query("SELECT * FROM {$db->TBL->security_ips} ORDER BY ipn_s {$limit}")) {
		while ($row = $result->fetch_assoc()) {
			$ip = inet_ntop($row['ipn_s']);
			$url = $text = $ip;
			if ($row['ipn_e'] && $ipe = inet_ntop($row['ipn_e'])) {
				$text .= ' - '.$ipe;
			}
			$bg = !$row['type'] ?: 'bg-ok';
			\Dragonfly\Admin\Cp::list_value(array(
				array(
					//'url' => URL::admin('&ips='.$url),
					'text' => $text . '<br />'.htmlprepare($row['details'])
				),
				array(
					'text' => !$row['type'] ? 'Banned' : 'Shielded'
				),
				array(
					'quick' => !$row['type'] ? 'shield' : 'ban',
					'action' => URL::admin('&ips'.$page_url),
					'value' => $url,
					'text' => !$row['type'] ? 'Shield' : 'Ban'
				),
				array(
					'quick' => 'delete',
					'action' => URL::admin('&ips'.$page_url),
					'value' => $url,
					'text' => _DELETE
				)
			), $bg);
		}
	}
	displaySecurityTPL();
	return;
}

# Referers
else if (isset($_GET['referers']))
{
	if (Security::check_post())
	{
		if (!empty($_POST['delete']))
		{
			$mark = $db->escape_string($_POST['delete']);
			$db->query("DELETE FROM {$db->TBL->security_domains} WHERE ban_string='$mark' AND ban_type=3");
			$db->optimize($db->TBL->security_domains);
		}
		URL::redirect(URL::admin('&referers'.$page_url));
	}
	\Dragonfly\Page::title('Referrers');
	$count = $db->count('security_domains', 'ban_type=3');
	pagination('&referers&page=', ceil($count/$per_page), 1, $page, true, false);
	\Dragonfly\Admin\Cp::list_header(
		'list',
		_NAME,
		'Status',
		'Options'
	);
	if ($count && $result = $db->query('SELECT ban_string FROM {security_domains} WHERE ban_type=3 ORDER BY ban_string '.$limit, \Poodle\SQL::ADD_PREFIX)) {
		while ($row = $result->fetch_row()) {
			\Dragonfly\Admin\Cp::list_value(array(
				array('text' => $row[0]),
				array('text' => 'Banned'),
				array('quick' => 'delete', 'action' => URL::admin('&referers'.$page_url), 'value' => $row[0], 'text' => _DELETE)
			));
		}
	}
	displaySecurityTPL();
	return;
}

# Sessions list
else if (isset($_GET['sessions']))
{
	if (Security::check_post()) {
		if (!empty($_POST['delete'])) {
			$db->TBL->sessions->delete("sess_id={$db->quote($_POST['delete'])}");
		}
		URL::redirect(URL::admin('&sessions'.$page_url));
	}
	\Dragonfly\Page::title('Sessions');
	$sid = $db->quote(session_id());
	$count = $db->TBL->sessions->count("sess_id <> {$sid}");
	pagination('&sessions&page=', ceil($count/$per_page), 1, $page, true, false);
	\Dragonfly\Admin\Cp::list_header(
		'list',
		'Username',
		'IP',
		'URI',
		'User agent',
		'Options'
	);
	$sort_by = 'sess_expiry';
	if (!empty($_GET['sort_by'])) {
		switch ($_GET['sort_by'])
		{
		case 'ip':  $sort_by = 'sess_ip'; break;
		case 'uri': $sort_by = 'sess_uri'; break;
		}
	}
	if ($count && $result = $db->query("SELECT
			sess_id, username, sess_ip, sess_uri, sess_user_agent,
			identity_id, sess_timeout, sess_expiry
		FROM {$db->TBL->sessions}
		LEFT JOIN {$db->TBL->users} ON (user_id = identity_id)
		WHERE sess_id <> {$sid}
		ORDER BY identity_id, {$sort_by} ASC {$limit}"))
	{
		while ($row = $result->fetch_row()) {
			\Dragonfly\Admin\Cp::list_value(array(
				array('text' => htmlspecialchars($row[1])),
				array('text' => htmlspecialchars($row[2])),
				array('text' => htmlspecialchars($row[3])),
				array('text' => htmlspecialchars($row[4])),
				array('quick' => 'delete', 'action' => URL::admin('&sessions'.$page_url), 'value' => $row[0], 'text' => _DELETE)
			));
		}
	}
	displaySecurityTPL();
	return;
}

# DNS black lists
else if (isset($_GET['dns_bl']) && Security::check_post())
{
	$srv = array('dns_bl_srv_1', 'dns_bl_srv_2', 'dns_bl_srv_3');
	$exc = array('dns_bl_exc_1', 'dns_bl_exc_2', 'dns_bl_exc_3');
	foreach ($srv as $val) {
		$_POST[$val] = trim($_POST[$val]);
		if (empty($_POST[$val]) || \Dragonfly\Net::validHostname($_POST[$val]))
			$MAIN_CFG->set('_security', $val, $_POST[$val]);
	}
	foreach ($exc as $val) {
		$_POST[$val] = trim($_POST[$val]);
		if (empty($_POST[$val]) || preg_match('#^[a-f0-9\.\:]+$#i', $_POST[$val]))
			$MAIN_CFG->set('_security', $val, $_POST[$val]);
	}
	URL::redirect(URL::admin());
}

# POST to main page
else if (Security::check_post())
{
	if (isset($_POST['bots']))     $MAIN_CFG->set('_security', 'bots',     intval($_POST['bots']));
	if (isset($_POST['ips']))      $MAIN_CFG->set('_security', 'ips',      intval($_POST['ips']));
	if (isset($_POST['flooding'])) $MAIN_CFG->set('_security', 'flooding', intval($_POST['flooding']));
	if (isset($_POST['dns_bl']))   $MAIN_CFG->set('_security', 'dns_bl_active', intval($_POST['dns_bl']));
	//if (isset($_POST['stopforumspam'])) { $db->query("UPDATE {$db->TBL->config_custom} SET cfg_value='".intval($_POST['stopforumspam'])."' WHERE cfg_name='_security' AND cfg_field='debug'"); }
	URL::redirect(URL::admin());
}

$type = 0;
$ip_s = $ip_e = null;
if (!empty($_GET['ip_s'])) {
	if (!$ip = \Dragonfly\Net::filterIPwCIDR(trim($_GET['ip_s']))) {
		$ip_s = \Dragonfly\Net::filterIP(trim($_GET['ip_s']));
		if (!empty($_GET['ip_e'])) {
			$ip_e = \Dragonfly\Net::filterIP(trim($_GET['ip_e']));
		}
	}
	if (!empty($ip['cidr'])) {
		$ip_s = $ip['ip_s'];
		$ip_e = $ip['ip_e'];
	} else {
		$ip_s = $ip_s['ip'];
		$ip_e = !empty($ip_e) ? $ip_e['ip'] : null;
	}
	$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
	$type = $type == 8 ? 8 : 0;
}
$L10N = \Dragonfly::getKernel()->L10N;
$sections = array(
	'general' => array(
		'id'    => 'general',
		'title' => 'General',
		'items' => array(
			array(
				'id'    => 'cachettl',
				'title' => 'Cache time to live (in days)',
				'value' => $MAIN_CFG->_security->cachettl/86400,
				'type' => 'number',
				'min' => 0,
				'max' => 99,
			),
			array(
				'id' => 'bantime',
				'value' => $MAIN_CFG->_security->bantime,
				'title' => _BAN_TIP,
				'type' => 'select',
				'options' => array(
					60=>$L10N->timeReadable(60, '%x'),
					300=>$L10N->timeReadable(300, '%x'),
					900=>$L10N->timeReadable(900, '%x'),
					3600=>$L10N->timeReadable(3600, '%x'),
					43200=>$L10N->timeReadable(43200, '%x')
				),
			),
			array(
				'id' => 'uas',
				'value' => $MAIN_CFG->_security->uas,
				'title' => 'Block unknown user agent',
				'type' => 'checkbox',
			),
			array(
				'id' => 'deny_tor',
				'value' => $MAIN_CFG->_security->deny_tor,
				'title' => 'Deny TOR network',
				'type' => 'checkbox',
			),
			array(
				'id'    => 'dns',
				'title' => 'DNS server',
				'value' => $MAIN_CFG->server->dns,
				'type' => 'text',
				'maxlength' => null,
				'help' => 'Enter an hostname, an IPv4 or an IPv6.<br />It must be a recursive DNS server'
					. (preg_match('/nameserver (.+)/', file_get_contents('/etc/resolv.conf'), $resolv) ? " (resolv.conf = {$resolv[1]})" :''),
			),
		)
	),
	'domains' => array(
		'id'    => 'domains',
		'title' => 'Domains',
		'items' => array(
			array(
				'id' => 'emails',
				'value' => $MAIN_CFG->_security->email,
				'title' => 'e-Mails',
				'type' => 'select',
				'options' => array(0=>_INACTIVE, 1=>_ACTIVE),
				'help' => 'example.com, example.',
			),
			array(
				'id' => 'referers',
				'value' => $MAIN_CFG->_security->referers,
				'title' => 'Referrers',
				'type' => 'select',
				'options' => array(0=>_INACTIVE, 1=>_ACTIVE),
				'help' => '.example.com, example., .example, example',
			),
			array(
				'id' => 'hostname',
				'value' => $MAIN_CFG->_security->hostnames,
				'title' => 'Hostnames',
				'type' => 'select',
				'options' => array(0=>_INACTIVE, 1=>_ACTIVE),
				'help' => 'example.com',
			),
			array(
				'html' => '<strong>Add a new domain</strong>',
			),
			array(
				'id'    => 'domain_type',
				'title' => 'Domain type',
				'type' => 'select',
				'options' => array(2=>'Blocked e-mail', 3=>'Blocked referer', 8=>'Protected hostname', 9=>'Blocked hostname'),
				'value' => '',
			),
			array(
				'id'    => 'domain_name',
				'title' => 'Detection string',
				'value' => '',
				'type'  => 'text',
				'maxlength' => null,
			)
		)
	),
	'flooding' => array(
		'id'     => 'flooding',
		'title'  => 'Floodings',
		'active' => $MAIN_CFG->_security->flooding,
		'items'  => array(
			array(
				'id' => 'delay',
				'value' => $MAIN_CFG->_security->delay,
				'title' => _FLOODING_TIP,
				'type'  => 'select',
				'options' => array(1=>'Normal', 2=>'High', 4=>'Emergency'),
			),
			array(
				'id' => 'debug',
				'value' => $MAIN_CFG->_security->debug,
				'title' => _DEBUG,
				'type'  => 'checkbox',
			)
		)
	),
	'ips' => array(
		'id'     => 'ips',
		'title'  => 'IPs',
		'active' => $MAIN_CFG->_security->ips,
		'items'  => array(
			array(
				'html' => '<em>To add a range of IPs: use IP start and IP end, or IP start/CIDR</em>',
			),
			array(
				'id'    => 'type',
				'title' => 'Type',
				'value' => ($ip_s ? $type : 0),
				'type' => 'select',
				'options' => array(0=>'Blocked', 8=>'Protected'),
			),
			array(
				'id'    => 'ip_s',
				'title' => 'IPv4/IPv6 start',
				'value' => ($ip_s ? $ip_s : ''),
				'type' => 'text',
				'maxlength' => 43,
			),
			array(
				'id'    => 'ip_e',
				'title' => 'IPv4/IPv6 end',
				'value' => ($ip_e ? $ip_e : ''),
				'type' => 'text',
				'maxlength' => 39,
			),
			array(
				'id'    => 'details',
				'title' => _DESCRIPTION,
				'value' => '',
				'type' => 'textarea',
				'rows' => 2,
				'cols' => 23,
			)
		)
	),
	'bots' => array(
		'id'     => 'bots',
		'title'  => _BOTS,
		'active' => $MAIN_CFG->_security->bots,
		'items'  => array(
			array(
				'html' => '<strong>Add a new bot by user agent</strong>',
			),
			array(
				'id'    => 'ua_name',
				'required' => 1,
				'title' => 'Unique name',
				'type' => 'text',
				'value' => '',
				'maxlength' => 35,
			),
			array(
				'id'    => 'ua_fullname',
				'required' => 1,
				'title' => 'Detection string',
				'type' => 'text',
				'value' => '',
				'maxlength' => null,
			),
			array(
				'id'    => 'ua_hostname',
				'title' => 'Hostname',
				'type' => 'text',
				'value' => '',
				'maxlength' => null,
				'help' => 'will protect the client',
			),
			array(
				'id'    => 'ua_url',
				'title' => 'Informational '._URL,
				'type' => 'text',
				'value' => '',
				'maxlength' => null,
			),
			array(
				'id'    => 'ua_ban_type',
				'title' => 'Type',
				'type' => 'select',
				'options' => array(0=>'Allowed', -1=>'Blocked'),
				'value' => '',
			),
			array(
				'id'    => 'ban_details',
				'title' => 'Description',
				'type' => 'textarea',
				'rows' => 2,
				'cols' => 23,
			)
				/*'item-extra-data0' => '<em>optional: include a CIDR in "IPv4 start" or use "IPv4 end" instead</em>',
				'ban_ipv4_s[]' => array('required' => 1, 'title' => 'IPv4 start', 'type' => 'text', 'size' => 18, 'maxlength' => 18),
				'ban_ipv4_e[]' => array('title' => 'IPv4 end', 'type' => 'text', 'size' => 15, 'maxlength' => 15),
				'new_range'    => array('title' => 'Create additional IP range(s)', 'type' => 'button', 'value' => 'Add another IP range', 'extra' => 'onclick="newRange.add();"'),
				'html' => '<div id="ranges"></div>'*/
		)
	),
	'dns_bl' => array(
		'id'     => 'dns_bl',
		'title'  => 'DNS block lists',
		'active' => $MAIN_CFG->_security->dns_bl_active,
		'items'  => array(
			array(
				'id'    => 'dns_bl_srv_1',
				'title' => '1. Hostname',
				'value' => $MAIN_CFG->_security->dns_bl_srv_1,
				'type' => 'text',
				'maxlength' => 216,
			),
			array(
				'id'    => 'dns_bl_exc_1',
				'title' => '1. Exclude list <em>(if any)</em>',
				'value' => $MAIN_CFG->_security->dns_bl_exc_1,
				'type' => 'text',
				'maxlength' => 39,
			),
			array(
				'id'    => 'dns_bl_srv_2',
				'title' => '2. Hostname',
				'value' => $MAIN_CFG->_security->dns_bl_srv_2,
				'type' => 'text',
				'maxlength' => 216,
			),
			array(
				'id'    => 'dns_bl_exc_2',
				'title' => '2. Exclude list <em>(if any)</em>',
				'value' => $MAIN_CFG->_security->dns_bl_exc_2,
				'type' => 'text',
				'maxlength' => 39,
			),
			array(
				'id'    => 'dns_bl_srv_3',
				'title' => '3. Hostname',
				'value' => $MAIN_CFG->_security->dns_bl_srv_3,
				'type' => 'text',
				'maxlength' => 216,
			),
			array(
				'id'    => 'dns_bl_exc_3',
				'title' => '3. Exclude list <em>(if any)</em>',
				'value' => $MAIN_CFG->_security->dns_bl_exc_3,
				'type' => 'text',
				'maxlength' => 39,
			),
		)
	),
/*'honeypot' => array(
		'title'  => 'Honey Pot',
		'active' => $MAIN_CFG['_security']['honeypot'],
		'items'  => array(
			'honeypot_host'    => array('title' => 'Host',                   'value' => $MAIN_CFG['_security']['honeypot_host'],    'type' => 'text', 'maxlength' => 255),
			'honeypot_key'     => array('title' => 'Key',                    'value' => $MAIN_CFG['_security']['honeypot_key'],     'type' => 'text', 'maxlength' => 12,'size' => 12),
			'honeypot_0_log'   => array('title' => 'Search engine: log',     'value' => $MAIN_CFG['_security']['honeypot_0_log'],   'type' => 'select', 'options' => array(0=>_NO, 1=>_YES)),
			'honeypot_1'       => array('title' => 'Suspicious',             'value' => $MAIN_CFG['_security']['honeypot_1'],       'type' => 'select', 'options' => array(0=>_INACTIVE, 1=>_ACTIVE)),
			'honeypot_1_days'  => array('title' => 'Suspicious: days',       'value' => $MAIN_CFG['_security']['honeypot_1_days'],  'type' => 'text', 'maxlength' => 3, 'size' => 3),
			'honeypot_1_threat'=> array('title' => 'Suspicious, threat',     'value' => $MAIN_CFG['_security']['honeypot_1_threat'],'type' => 'text', 'maxlength' => 3, 'size' => 3),
			'honeypot_1_log'   => array('title' => 'Suspicious: log',        'value' => $MAIN_CFG['_security']['honeypot_1_log'],   'type' => 'select', 'options' => array(0=>_NO, 1=>_YES)),
			'honeypot_2'       => array('title' => 'Harvester',              'value' => $MAIN_CFG['_security']['honeypot_2'],       'type' => 'select', 'options' => array(0=>_INACTIVE, 1=>_ACTIVE)),
			'honeypot_2_days'  => array('title' => 'Harvester: days',        'value' => $MAIN_CFG['_security']['honeypot_2_days'],  'type' => 'text', 'maxlength' => 3, 'size' => 3),
			'honeypot_2_threat'=> array('title' => 'Harvester: threat',      'value' => $MAIN_CFG['_security']['honeypot_2_threat'],'type' => 'text', 'maxlength' => 3, 'size' => 3),
			'honeypot_2_log'   => array('title' => 'Harvester: log',         'value' => $MAIN_CFG['_security']['honeypot_2_log'],   'type' => 'select', 'options' => array(0=>_NO, 1=>_YES)),
			'honeypot_4'       => array('title' => 'Comment Spammer',        'value' => $MAIN_CFG['_security']['honeypot_4'],       'type' => 'select', 'options' => array(0=>_INACTIVE, 1=>_ACTIVE)),
			'honeypot_4_days'  => array('title' => 'Comment Spammer: days',  'value' => $MAIN_CFG['_security']['honeypot_4_days'],  'type' => 'text', 'maxlength' => 3, 'size' => 3),
			'honeypot_4_threat'=> array('title' => 'Comment Spammer: threat','value' => $MAIN_CFG['_security']['honeypot_4_threat'],'type' => 'text', 'maxlength' => 3, 'size' => 3),
			'honeypot_4_log'   => array('title' => 'Comment Spammer: log',   'value' => $MAIN_CFG['_security']['honeypot_4_log'],   'type' => 'select', 'options' => array(0=>_NO, 1=>_YES))
		)
	),
	'sfs' => array(
		'title'  => 'Stop Forums Spam',
		'active' => $MAIN_CFG['_security']['sfs'],
		'items'  => array(
			'sfs_key' => array('title' => 'Key',  'value' => $MAIN_CFG['_security']['sfs_key'],  'type' => 'text', 'maxlength' => 12,'size' => 12),
			'dnsbl_1' => array('title' => 'Mode', 'value' => $MAIN_CFG['_security']['sfs_mode'], 'type' => 'select', 'options' => array(0=>'Log only', 1=>'Approve registration', 2 => 'Enforce')),
		)
	)
*/
);
//\Dragonfly\Output\Js::add('themes/admin/javascript/addipv4range.js');

$TPL = Dragonfly::getKernel()->OUT;
$TPL->display('submenu_inline');
$TPL->sections = $sections;
$TPL->display('admin/security/main');
