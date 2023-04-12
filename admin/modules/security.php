<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/security.php,v $
  $Revision: 1.20 $
  $Author: nanocaiordo $
  $Date: 2007/12/16 09:22:49 $
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
if (!can_admin()) { die('Access Denied'); }

$pagetitle .= ' '._BC_DELIM.' Security';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) { $page = 1; }
$per_page = 30;
$limit = "LIMIT $per_page OFFSET ".(($page-1)*$per_page);
$counter=1;

function get_ban_type($type)
{
	if ($type < 0) { return _FOREVER; }
	if ($type > 0) { return formatDateTime($type, _DATESTRING); }
	return '';
}

$cpgtpl->assign_vars(array(
	'L_DETAILS' => _TB_INFO,
	'L_SAVECHANGES' => _SAVECHANGES,
	'L_DELETE' => _DELETE,
	'L_REMOVE_SELECTED' => _REMOVE_SELECTED,
	'L_TASKS' => _TB_TASKS,
	'L_NEW' => _NEW,
	'U_SECURITY' => adminlink('security'),
	'B_LOG' => false
));

# Prepare menu & index
$ids = array(
	array(_BOTS, 'bots', 'bots'),
	array(_EMAIL_DOMAINS, 'email', 'mails'),
	array(_FLOODING, 'flooding', 'floods'),
	array('IP\'s', 'ips', 'ips'),
	array(_HTTPREFERERS, 'referers', 'referers'),
	array('Unkown User-Agents', 'uas', 'uas'),
	array('IPs Shield', 'shield', 'shields')
);

foreach ($ids as $id) {
	$cpgtpl->assign_block_vars('menu', array(
		'L_NAME' => $id[0],
		'L_ID' => $id[1],
		'U_DETAILS' => adminlink('&amp;'.$id[2]),
		'B_GET' => !isset($_GET[$id[2]]),
		'B_ACTIVE' => $MAIN_CFG['_security'][$id[1]]
	));
}

#
# Bots
#
if (isset($_GET['bots'])) {
	if (Security::check_post()) {
		# Delete from tables
		if (isset($_POST['mark']) && (0 < count($_POST['mark']))) {
			foreach ($_POST['mark'] as $mark) {
				$marked = $db->sql_escape_string($mark);
				$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_string='$mark' AND ban_type=1");
				$db->sql_query('DELETE FROM '.$prefix."_security_agents WHERE agent_name='$mark'");
			}
			$db->optimize_table($prefix.'_security');
			$db->optimize_table($prefix.'_security_agents');
		}
		else {
			# Insert new entry
			if (empty($_POST['ua_name'])) cpg_error(sprintf(_ERROR_NOT_SET, 'Bot name'));
			else $ua_name = $db->sql_escape_string($_POST['ua_name']);

			if (empty($_POST['ua_fullname']))	cpg_error(sprintf(_ERROR_NOT_SET, 'UA detect string'));
			else $ua_fullname = $db->sql_escape_string($_POST['ua_fullname']);

			$ua_url = empty($_POST['ua_url']) ? NULL : $db->sql_escape_string($_POST['ua_url']);
			if (preg_match('#^http://#', $ua_url))
				$ua_url = substr($ua_url, 7);
			if (empty($ua_url))
				$ua_url = NULL;
			else if (strpos('.', $ua_url) !==  false)
				cpg_error(_BAD_FORMAT, _URL);

			$ua_ban_type = intval($_POST['ua_ban_type']);
			$ua_hostname = empty($_POST['ua_hostname']) ? NULL : $db->sql_escape_string($_POST['ua_hostname']);
			$ban_details = empty($_POST['ban_details']) ? NULL : $db->sql_escape_string($_POST['ban_details']);

			$ip = $ip2 = false;
			foreach($_POST['ban_ipv4_s'] as $i => $ipv4s) {
				if (empty($ipv4s)) cpg_error(sprintf(_ERROR_NOT_SET, 'IPv4 start'));
				else if (preg_match('#^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/([0-9]{1,2})$#',$ipv4s, $match)) {
					$ip = inet_pton($match[1]);
					$match[2] = intval($match[2]);
					if ($match[2] > 32) { cpg_error(sprintf(_ERROR_BAD_FORMAT, 'CIDR')); }
					$ip1 = ip2long32($match[1], true);
					$ip2 = inet_pton(long2ip($ip1 - (0xffffffff << (32 - $match[2])) - 1));
				}
				else if (!preg_match('#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$#',$ipv4s)) {
					cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IPv4 start'));
				}
				else {
					$ip = inet_pton($ipv4s);
					if (!empty($_POST['ban_ipv4_e'][$i])) {
						if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\$/", $_POST['ban_ipv4_e'][$i])) {
							cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IPv4 end'));
						}
						$ip2 = inet_pton($_POST['ban_ipv4_e'][$i]);
					}
				}
				if ($ip === false) { cpg_error('Nothing specified'); }
				$ip_len = strlen($ip);
				if ($ip_len == 4) {
					list(,$ip) = unpack('N',$ip);
					if (empty($ip2)) {
						$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipv4_s, ban_string, ban_type) VALUES ('$ip', '$ua_name', 1)");
					} else {
						list(,$ip2) = unpack('N',$ip2);
						if ($ip2 < $ip) { $ip1 = $ip2; $ip2 = $ip; }
						else { $ip1 = $ip; }
						$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipv4_s, ban_ipv4_e, ban_string, ban_type) VALUES ($ip1, $ip2, '$ua_name', 1)");
					}
				} else if ($ip_len == 16) {
					#$ip = $db->binary_safe($ip);
					#$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipn, ban_string, ban_type) VALUES ($ip, '$ua_name', 1)");
				}
			}
			$db->sql_query('INSERT INTO '.$prefix."_security_agents	(agent_name, agent_fullname, agent_hostname, agent_url, agent_ban, agent_desc) VALUES ('$ua_name', '$ua_fullname', '$ua_hostname', '$ua_url', $ua_ban_type, '$ban_details')");
		}
		url_redirect(adminlink('&amp;bots'));
	}

	# Bots admin
	$pagetitle .= ' '._BC_DELIM.' Bots';
	if (file_exists('themes/'.$CPG_SESS['theme'].'/template/admin/security/javascript.js')) {
		$jstheme = $CPG_SESS['theme'];
	} else {
		$jstheme = 'default';
	}
	$modheader .= "\n".'<script type="text/javascript" src="themes/'.$jstheme.'/template/admin/security/javascript.js"></script>';
	require('header.php');
	$count = $db->sql_count($prefix.'_security_agents');
	pagination('&amp;bots&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT agent_name, agent_ban FROM '.$prefix."_security_agents ORDER BY agent_name $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $row['agent_name'],
				'L_BAN_LEVEL' => get_ban_type($row['agent_ban']),
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;bot='.urlencode($row['agent_name']))
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_ACTIVE' => _ACTIVE,
		'L_INACTIVE' => _INACTIVE,
		'L_URL' => _URL,
		'L_BAN_NAME' => 'Bot',
		'L_BAN_TYPE' => 'Ban type',
		'U_PAGE' => 'bots'
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
else if (isset($_GET['bot'])) {
	$pagetitle .= ' '._BC_DELIM.' Bot Details';
	require('header.php');
	$bot = $db->sql_escape_string($_GET['bot']);
	if ($result = $db->query('SELECT * FROM '.$prefix."_security_agents WHERE agent_name='$bot'")) {
		$row = $db->fetch_array($result, SQL_ASSOC);
		$db->sql_freeresult($result);
		if ($result = $db->query('SELECT ban_ipv4_s, ban_ipv4_e FROM '.$prefix."_security WHERE ban_string='$bot'")) {
			while ($ips = $db->fetch_array($result, SQL_ASSOC)) {
				$row['agent_hostname'] .= '<br />'.long2ip($ips['ban_ipv4_s']);
				if (isset($ips['ban_ipv4_e'])) {
					$row['agent_hostname'] .= ' - '.long2ip($ips['ban_ipv4_e']);
				}
			}
			$db->sql_freeresult($result);
		}
		$cpgtpl->assign_vars(array(
			'L_DESCRIPTION' => _DESCRIPTION,
			'L_TB_INFO' => _TB_INFO,
			'S_BOT_NAME' => $row['agent_name'],
			'S_BOT_UA' => $row['agent_fullname'],
			'S_BOT_DNS' => $row['agent_hostname'],
			'U_BOT_HOME' => 'http://'.$row['agent_url'],
			'S_BOT_DESC' => $row['agent_desc'],
		));
	$cpgtpl->set_handle('body', 'admin/security/bot_details.html');
	$cpgtpl->display('body');
	}
}
#
# E-mails
#
else if (isset($_GET['mails'])) {
	if (Security::check_post()) {
		if (isset($_POST['mark']) && (0 < count($_POST['mark']))) {
			foreach (($_POST['mark']) as $mark) {
				$marked = $db->sql_escape_string($mark);
				$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_string='$mark' AND ban_type=2");
			}
			$db->optimize_table($prefix.'_security');
		}
		if (!empty($_POST['new_entry'])) {
			$new_entry = $db->sql_escape_string($_POST['new_entry']);
			if (!strpos($new_entry,'.')) { cpg_error(sprintf(_ERROR_BAD_FORMAT, _EMAIL)); }
			$db->sql_query('INSERT INTO '.$prefix."_security (ban_string, ban_type) VALUES ('$new_entry', 2)");
		}
		url_redirect(adminlink('&amp;mails'));
	}
	$pagetitle .= ' '._BC_DELIM.' Email domains';
	require('header.php');
	$count = $db->sql_count($prefix.'_security', 'ban_type=2');
	pagination('&amp;mails&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT ban_string FROM '.$prefix."_security WHERE ban_type = 2 ORDER BY ban_string $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $row['ban_string'],
				'L_BAN_LEVEL' => _FOREVER,
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;mail='.urlencode($row['ban_string']))
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_BAN_NAME' => 'Email domain',
		'L_BAN_TYPE' => 'Ban type',
		'U_PAGE' => 'mails'
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
else if (isset($_GET['mail'])) {
	$pagetitle .= ' '._BC_DELIM.' E-Mail Domains Details';
	$mail = $db->sql_escape_string($_GET['mail']);
	require('header.php');
	$cpgtpl->assign_vars(array(
		'L_DESCRIPTION' => _DESCRIPTION,
		'L_TB_INFO' => _TB_INFO,
		'S_BOT_NAME' => '',
		'S_BOT_UA' => '',
		'S_BOT_DNS' => '',
		'U_BOT_HOME' => '',
		'S_BOT_DESC' => '',
	));
	$cpgtpl->set_handle('body', 'admin/security/bot_details.html');
	$cpgtpl->display('body');
}
#
# Floods
#
else if (isset($_GET['floods'])) {
	if (Security::check_post()) {
		if (isset($_POST['mark']) && (0 < count($_POST['mark']))) {
			foreach (($_POST['mark']) as $ip) {
				$ipn = $db->binary_safe(inet_pton($ip));
				$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_ipn=$ipn AND ban_type=7");
			}
			$db->optimize_table($prefix.'_security');
		}
		url_redirect(adminlink('&amp;floods'));
	}
	$pagetitle .= ' '._BC_DELIM.' IP\'s';
	require('header.php');
	$count = $db->sql_count($prefix.'_security', 'ban_type=7');
	pagination('&amp;floods&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT ban_ipn, ban_time FROM '.$prefix."_security WHERE ban_type = 7 ORDER BY ban_string $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			++$counter;
			$url = $ip = decode_ip($row['ban_ipn']);
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $ip,
				'L_BAN_LEVEL' => get_ban_type($row['ban_time']-$MAIN_CFG['_security']['bantime']),
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;ip='.$url)
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_BAN_NAME' => 'IP',
		'L_BAN_TYPE' => 'Banned on',
		'U_PAGE' => 'floods'
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
#
# IP
#
else if (isset($_GET['ips'])) {
	if (Security::check_post()) {
		if (!empty($_POST['mark'])) {
			foreach (($_POST['mark']) as $ip) {
				$ban_ipv4_s = $ban_ipv4_e = '';
				if (preg_match('#^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})( - )?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})?$#',$ip, $match)) {
					$ban_ipv4_s = ip2long32($match[1]);
					if (!empty($match[2])) {
						$ban_ipv4_e = 'AND ban_ipv4_e=\''.ip2long32($match[3]).'\'';
					}
					if (!empty($ban_ipv4_s)) $db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_ipv4_s='$ban_ipv4_s' $ban_ipv4_e AND ban_type='0'");
				}
			}
			$db->optimize_table($prefix.'_security');
			url_redirect(adminlink('&amp;ips'));
		}
		$ip = $ip2 = false;
		if (!empty($_POST['ban_ipv4_s'])) {
			# CIDR block ?
			if (preg_match('#^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/([0-9]{1,2})$#',$_POST['ban_ipv4_s'], $match)) {
				$ip = inet_pton($match[1]);
				$match[2] = intval($match[2]);
				if ($match[2] > 32) { cpg_error(sprintf(_ERROR_BAD_FORMAT, 'CIDR')); }
				$ip1 = ip2long32($match[1], true);
				$ip2 = inet_pton(long2ip($ip1 - (0xffffffff << (32 - $match[2])) - 1));
			}
			else if (!preg_match('#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$#',$_POST['ban_ipv4_s'])) {
				cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IP'));
			} else {
				$ip = inet_pton($_POST['ban_ipv4_s']);
				if (!empty($_POST['ban_ipv4_e'])) {
					if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\$/",$_POST['ban_ipv4_e'])) {
						cpg_error(sprintf(_ERROR_BAD_FORMAT, 'second ip'));
					}
					$ip2 = inet_pton($_POST['ban_ipv4_e']);
				}
			}
		}
		if (!empty($_POST['ban_mac'])) {
		}
		if ($ip === false) { cpg_error('Nothing specified'); }
		$ip_len = strlen($ip);

		$details = $db->sql_escape_string($_POST['description']);
		if ($ip_len == 4) {
			list(,$ip) = unpack('N',$ip);
			if (empty($ip2)) {
				$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipv4_s, ban_type, ban_time, ban_details) VALUES ('$ip', 0, -1, '$details')");
			} else {
				list(,$ip2) = unpack('N',$ip2);
				if ($ip2 < $ip) { $ip1 = $ip2; $ip2 = $ip; }
				else { $ip1 = $ip; }
				$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipv4_s, ban_ipv4_e, ban_type, ban_time, ban_details) VALUES ($ip1, $ip2, 0, -1, '$details')");
			}
		} else if ($ip_len == 16) {
			$ip = $db->binary_safe($ip);
			$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipn, ban_type, ban_time, ban_details) VALUES ($ip, 0, -1, '$details')");
		}
		url_redirect(adminlink('&amp;ips'));
	}
	$pagetitle .= ' '._BC_DELIM.' IP\'s';
	require('header.php');
	$count = $db->sql_count($prefix.'_security', 'ban_type=0');
	pagination('&amp;ips&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT ban_ipv4_s, ban_ipv4_e, ban_ipn, ban_time FROM '.$prefix."_security WHERE ban_type = 0 ORDER BY ban_string $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			if (!empty($row['ban_ipn'])) {
				$url = $ip = inet_ntop($row['ban_ipn']);
			} else {
				$url = $ip = long2ip($row['ban_ipv4_s']);
				if (!empty($row['ban_ipv4_e'])) { $ip .= ' - '.long2ip($row['ban_ipv4_e']); }
			}
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $ip,
				'L_BAN_LEVEL' => get_ban_type($row['ban_time']),
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;ip='.$url)
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_BAN_NAME' => 'IP (range)',
		'L_BAN_TYPE' => 'Ban type',
		'U_PAGE' => 'ips'
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
else if (isset($_GET['ip'])) {
	$pagetitle .= ' '._BC_DELIM.' IP Details';
	require('header.php');
	$ip = inet_pton($_GET['ip']);
	$ipn = $db->binary_safe($ip);
	if (strlen($ip) == 4) {
		list(,$ipv4) = unpack('N',$ip);
		$result = $db->query('SELECT * FROM '.$prefix."_security WHERE ban_type IN(0,7) AND (ban_ipn=$ipn OR ban_ipv4_s='$ipv4')");
	} else if (strlen($ip) == 16) {
		$result = $db->query('SELECT * FROM '.$prefix."_security WHERE ban_type IN(0,7) AND ban_ipn=$ipn");
	}
	if ($result) {
		$row = $db->fetch_array($result, SQL_ASSOC);
		if (empty($row['ban_ipn'])) {
			$ip = long2ip($row['ban_ipv4_s']);
			if (!empty($row['ban_ipv4_e'])) {
				$ip .= ' - '.long2ip($row['ban_ipv4_e']);
				$cidr = 32-log($row['ban_ipv4_e']-$row['ban_ipv4_s']+1, 2);
				$ip .= '<br />CIDR: '.long2ip($row['ban_ipv4_s'])."/$cidr";
			}
		} else {
			$ip = inet_ntop(substr($row['ban_ipn'],0,-1));
			if (!empty($row['log'])) {
				$log = unserialize($row['log']);
				for ($i=0; $i<5; ++$i) {
						$log[$i]['S_TIME'] = get_ban_type($log[$i]['S_TIME']);
						$cpgtpl->assign_block_vars('log', $log[$i]);
				}
			}
		}
		$cpgtpl->assign_vars(array(
			'L_DESCRIPTION' => _DESCRIPTION,
			'L_TB_INFO' => _TB_INFO,
			'S_BOT_NAME' => $row['ban_string'],
			'S_BOT_UA' => '',
			'S_BOT_DNS' => $ip,
			'U_BOT_HOME' => '',
			'S_BOT_DESC' => nl2br(get_ban_type($row['ban_time'])."\n".$row['ban_details']),
			'B_LOG' => isset($log)
		));
	$cpgtpl->set_handle('body', 'admin/security/bot_details.html');
	$cpgtpl->display('body');
	}
}
else if (isset($_GET['shields'])) {
	if (Security::check_post()) {
		if (isset($_POST['mark']) && !empty($_POST['mark'])) {
			foreach (($_POST['mark']) as $ip) {
				$ip = inet_pton($ip);
				$ip_len = strlen($ip);
				if ($ip_len == 4) {
					list(,$ip) = unpack('N',$ip);
					$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_ipv4_s=$ip AND ban_type=8");
				} else if ($ip_len == 16) {
					//mac = substr($ip,-8)
					$ip = $db->binary_safe($ip);
					$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_ipn=$ip AND ban_type=8");
				}
			}
			$db->optimize_table($prefix.'_security');
		} else {
			$ip = $ip2 = false;
			if (!empty($_POST['ban_ipv4_s'])) {
				# CIDR block ?
				if (preg_match('#^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/([0-9]{1,2})$#', $_POST['ban_ipv4_s'], $match)) {
					$ip = inet_pton($match[1]);
					$match[2] = intval($match[2]);
					if ($match[2] > 32) { cpg_error(sprintf(_ERROR_BAD_FORMAT, 'CIDR')); }
					$ip1 = ip2long32($match[1], true);
					$ip2 = inet_pton(long2ip($ip1 - (0xffffffff << (32 - $match[2])) - 1));
				}
				else if (!preg_match('#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$#', $_POST['ban_ipv4_s'])) {
					cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IPv4 start'));
				} else {
					$ip = inet_pton($_POST['ban_ipv4_s']);
					if (!empty($_POST['ban_ipv4_e'])) {
						if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\$/", $_POST['ban_ipv4_e'])) {
							cpg_error(sprintf(_ERROR_BAD_FORMAT, 'IPv4 end'));
						}
						$ip2 = inet_pton($_POST['ban_ipv4_e']);
					}
				}
			}
			if (!empty($_POST['ban_mac'])) {
			}
			if ($ip === false) { cpg_error('Nothing specified'); }
			$ip_len = strlen($ip);

			$details = $db->sql_escape_string($_POST['description']);
			if ($ip_len == 4) {
				list(,$ip) = unpack('N',$ip);
				if (empty($ip2)) {
					$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipv4_s, ban_type, ban_time, ban_details) VALUES ('$ip', 8, ".gmtime().", '$details')");
				} else {
					list(,$ip2) = unpack('N',$ip2);
					if ($ip2 < $ip) { $ip1 = $ip2; $ip2 = $ip; }
					else { $ip1 = $ip; }
					$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipv4_s, ban_ipv4_e, ban_type, ban_time, ban_details) VALUES ($ip1, $ip2, 8, ".gmtime().", '$details')");
				}
			} else {
				//mac = substr($ip,-8)
				$ip = $db->binary_safe($ip);
				$db->sql_query('INSERT INTO '.$prefix."_security (ban_ipn, ban_type, ban_time, ban_details) VALUES ($ip, 8, ".gmtime().", '$details')");
			}
		}
		url_redirect(adminlink('&amp;shields'));
	}
	$pagetitle .= ' '._BC_DELIM.' Shields';
	require('header.php');
	$count = $db->sql_count($prefix.'_security', 'ban_type=8');
	pagination('&amp;shields&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT ban_ipv4_s, ban_ipv4_e, ban_ipn, ban_time FROM '.$prefix."_security WHERE ban_type=8 $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			if (!empty($row['ban_ipn'])) {
				$url = $ip = inet_ntop($row['ban_ipn']);
			} else {
				$url = $ip = long2ip($row['ban_ipv4_s']);
				if (!empty($row['ban_ipv4_e'])) { $ip .= ' - '.long2ip($row['ban_ipv4_e']); }
			}
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $ip,
				'L_BAN_LEVEL' => get_ban_type($row['ban_time']),
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;shield='.$url)
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_BAN_NAME' => 'IP (range)',
		'L_BAN_TYPE' => _DATE,
		'U_PAGE' => 'shields'
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
else if (isset($_GET['shield'])) {
	$pagetitle .= ' '._BC_DELIM.' IP Details';
	require('header.php');
	$ip = inet_pton($_GET['shield']);
	$ipn = $db->binary_safe($ip);
	if (strlen($ip) == 4) {
		list(,$ipv4) = unpack('N',$ip);
		$result = $db->query('SELECT * FROM '.$prefix."_security WHERE ban_type=8 AND ban_ipv4_s='$ipv4'");
	} else if (strlen($ip) == 16) {
		$result = $db->query('SELECT * FROM '.$prefix."_security WHERE ban_type=8 AND ban_ipn=$ipn");
	}
	if ($result) {
		$row = $db->fetch_array($result, SQL_ASSOC);
		if (empty($row['ban_ipn'])) {
			$ip = long2ip($row['ban_ipv4_s']);
			if (!empty($row['ban_ipv4_e'])) {
				$ip .= ' - '.long2ip($row['ban_ipv4_e']);
				$cidr = 32-log($row['ban_ipv4_e']-$row['ban_ipv4_s']+1, 2);
				$ip .= '<br />CIDR: '.long2ip($row['ban_ipv4_s'])."/$cidr";
			}
		} else {
			$ip = inet_ntop(substr($row['ban_ipn'],0,-1));
		}
		if (!empty($row['log'])) {
			$log = unserialize($row['log']);
			for ($i=0; $i<5; ++$i) {
					$log[$i]['S_TIME'] = get_ban_type($log[$i]['S_TIME']);
					$cpgtpl->assign_block_vars('log', $log[$i]);
			}
		}
		$cpgtpl->assign_vars(array(
			'L_DESCRIPTION' => _DESCRIPTION,
			'L_TB_INFO' => _TB_INFO,
			'S_BOT_NAME' => $row['ban_string'],
			'S_BOT_UA' => '',
			'S_BOT_DNS' => $ip,
			'U_BOT_HOME' => '',
			'S_BOT_DESC' => nl2br(get_ban_type($row['ban_time'])."\n".$row['ban_details']),
			'B_LOG' => isset($log)
		));
	$cpgtpl->set_handle('body', 'admin/security/bot_details.html');
	$cpgtpl->display('body');
	}
}
#
# Referers
#
else if (isset($_GET['referers'])) {
	if (Security::check_post()) {
		if (isset($_POST['mark']) && (0 < count($_POST['mark']))) {
			foreach (($_POST['mark']) as $mark) {
				$marked = $db->sql_escape_string($mark);
				$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_string='$mark' AND ban_type=3");
			}
		}
		if (!empty($_POST['new_entry'])) {
			$new_entry = $db->sql_escape_string($_POST['new_entry']);
			if (!strpos($new_entry,'.')) { cpg_error(sprintf(_ERROR_BAD_FORMAT, _HTTPREFERERS)); }
			$db->sql_query('INSERT INTO '.$prefix."_security (ban_string, ban_type) VALUES ('$new_entry', 3)");
		}
		$db->optimize_table($prefix.'_security');
		url_redirect(adminlink('&amp;referers'));
	}
	$pagetitle .= ' '._BC_DELIM.' Referers';
	require('header.php');
	$count = $db->sql_count($prefix.'_security', 'ban_type=3');
	pagination('&amp;referers&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT ban_string FROM '.$prefix."_security WHERE ban_type=3 ORDER BY ban_string $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $row['ban_string'],
				'L_BAN_LEVEL' => _FOREVER,
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;referer='.urlencode($row['ban_string']))
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_BAN_NAME' => 'Referer domain',
		'L_BAN_TYPE' => 'Ban type',
		'U_PAGE' => 'referers'
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
else if (isset($_GET['referer'])) {
	$pagetitle .= ' '._BC_DELIM.' Referer Details';
	require('header.php');
	$referer = $db->sql_escape_string($_GET['referer']);
	if ($result = $db->query('SELECT ban_string, ban_details FROM '.$prefix."_security WHERE ban_type=3 AND ban_string='$referer'")) {
		$row = $db->fetch_array($result, SQL_ASSOC);
		$cpgtpl->assign_vars(array(
			'L_DESCRIPTION' => _DESCRIPTION,
			'L_TB_INFO' => _TB_INFO,
			'S_BOT_NAME' => $row['ban_string'],
			'S_BOT_UA' => '',
			'S_BOT_DNS' => '',
			'U_BOT_HOME' => '',
			'S_BOT_DESC' => $row['ban_details']
		));
	$cpgtpl->set_handle('body', 'admin/security/bot_details.html');
	$cpgtpl->display('body');
	}
}
else if (isset($_GET['uas'])) {
	if (Security::check_post()) {
		if (isset($_POST['mark']) && (0 < count($_POST['mark']))) {
			foreach (($_POST['mark']) as $mark) {
				$marked = $db->sql_escape_string($mark);
				$db->sql_query('DELETE FROM '.$prefix."_security WHERE ban_string='$mark' AND ban_type=3");
			}
		}
		if (!empty($_POST['new_entry'])) {
			$new_entry = $db->sql_escape_string($_POST['new_entry']);
			if (!strpos($new_entry,'.')) { cpg_error(sprintf(_ERROR_BAD_FORMAT, _HTTPREFERERS)); }
			$db->sql_query('INSERT INTO '.$prefix."_security (ban_string, ban_type) VALUES ('$new_entry', 3)");
		}
		$db->optimize_table($prefix.'_security');
		url_redirect(adminlink('&amp;uas'));
	}
	$pagetitle .= ' '._BC_DELIM.' User-Agents';
	require('header.php');
	$count = $db->sql_count($prefix.'_security_agents', '');
	pagination('&amp;uas&amp;page=', ceil($count/$per_page), 1, $page);
	if ($result = $db->query('SELECT agent_name, agent_ban FROM '.$prefix."_security_agents ORDER BY agent_name $limit")) {
		while ($row = $db->fetch_array($result, SQL_ASSOC)) {
			$cpgtpl->assign_block_vars('seclist', array(
				'L_NAME' => $row['agent_name'],
				'L_BAN_LEVEL' => get_ban_type($row['agent_ban']),
				'S_BACKGROUND' => (++$counter%2) ? ' class="distinct"' : '',
				'U_DETAILS' => adminlink('&amp;ua='.urlencode($row['agent_name']))
			));
		}
	}
	$cpgtpl->assign_vars(array(
		'L_ACTIVE' => _ACTIVE,
		'L_INACTIVE' => _INACTIVE,
		'L_BAN_NAME' => 'User-Agent',
		'L_BAN_TYPE' => 'Ban type',
	));
	$cpgtpl->set_handle('options', 'admin/security/options.html');
	$cpgtpl->display('options');
}
else if (isset($_GET['ua'])) {
	$pagetitle .= ' '._BC_DELIM.' User-Agents';
	require('header.php');
	$agent = $db->sql_escape_string($_GET['ua']);
	if ($result = $db->query('SELECT agent_fullname, agent_hostname, agent_url, agent_desc FROM '.$prefix."_security_agents WHERE agent_name='$agent'")) {
		$row = $db->fetch_array($result, SQL_ASSOC);
		$cpgtpl->assign_vars(array(
			'L_DESCRIPTION' => _DESCRIPTION,
			'L_TB_INFO' => _TB_INFO,
			'S_BOT_NAME' => $row['agent_name'],
			'S_BOT_UA' => $row['agent_fullname'],
			'S_BOT_DNS' => $row['agent_hostname'],
			'U_BOT_HOME' => 'http://'.$row['agent_url'],
			'S_BOT_DESC' => $row['agent_desc'],
		));
	$cpgtpl->set_handle('body', 'admin/security/bot_details.html');
	$cpgtpl->display('body');
	}
}

else {
	if (Security::check_post()) {
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['bots'])."' WHERE cfg_name='_security' AND cfg_field='bots'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['email'])."' WHERE cfg_name='_security' AND cfg_field='email'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['flooding'])."' WHERE cfg_name='_security' AND cfg_field='flooding'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['ips'])."' WHERE cfg_name='_security' AND cfg_field='ips'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['referers'])."' WHERE cfg_name='_security' AND cfg_field='referers'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['uas'])."' WHERE cfg_name='_security' AND cfg_field='uas'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['shield'])."' WHERE cfg_name='_security' AND cfg_field='shield'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['delay'])."' WHERE cfg_name='_security' AND cfg_field='delay'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['unban'])."' WHERE cfg_name='_security' AND cfg_field='unban'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['bantime'])."' WHERE cfg_name='_security' AND cfg_field='bantime'");
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".intval($_POST['_security']['debug'])."' WHERE cfg_name='_security' AND cfg_field='debug'");
		Cache::array_delete('MAIN_CFG');
		url_redirect(adminlink());
	}
	require_once('header.php');
	$cpgtpl->assign_vars(array(
		'L_TYPE' => _TYPE,
		'L_PROTECTION' => _PROTECTION,
		'L_ACTIVE' => _ACTIVE,
		'L_INACTIVE' => _INACTIVE
	));

#
# Flooding Settings
#
	$cpgtpl->assign_block_vars('settings', array(
		'L_NAME' => _FLOODING_TIP,
		'L_ID' => 'delay',
		'S_VALUES' => select_box('_security[delay]', $MAIN_CFG['_security']['delay'], array(1=>'Tight',2=>'Loose'))
	));
	$cpgtpl->assign_block_vars('settings', array(
		'L_NAME' => _BAN_TIP,
		'L_ID' => 'bantime',
		'S_VALUES' => select_box('_security[bantime]', $MAIN_CFG['_security']['bantime'], array(900=>'15 Minutes',3600=>'1 Hour',43200=>'12 Hours',86400=>'24 Hours'))
	));
	$cpgtpl->assign_block_vars('settings', array(
		'L_NAME' => _AUTO_UNBAN_TIP,
		'L_ID' => 'unban',
		'S_VALUES' => select_box('_security[unban]', $MAIN_CFG['_security']['unban'], array(0=>_NO, 1=>_YES))
	));
	$cpgtpl->assign_block_vars('settings', array(
		'L_NAME' => _DEBUG,
		'L_ID' => 'unban',
		'S_VALUES' => select_box('_security[debug]', $MAIN_CFG['_security']['debug'], array(0=>_NO, 1=>_YES))
	));
	$cpgtpl->set_handle('body', 'admin/security/index.html');
	$cpgtpl->display('body');

}