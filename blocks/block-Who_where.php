<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

$K = \Dragonfly::getKernel();
$SQL = $K->SQL;

$who_where = array(
	'members' => array(),
	'bots' => array(),
	'anonymous' => array(),
	'hidden' => 0,
);

$result = $SQL->query("SELECT u.user_id, s.uname, s.module, s.url, u.user_allow_viewonline
	FROM {$SQL->TBL->session} AS s
	LEFT JOIN {$SQL->TBL->users} AS u ON u.user_id = s.identity_id
	WHERE identity_id > 1 ORDER BY s.uname");
while ($row = $result->fetch_assoc()) {
	if ($row['user_allow_viewonline'] || is_admin()) {
		$who_where['members'][] = $row;
	} else {
		++$who_where['hidden'];
	}
}

$result = $SQL->query("SELECT host_addr, uname, module, url, guest FROM {$SQL->TBL->session}
	WHERE guest = 1 OR guest = 3
	ORDER BY host_addr");
while ($row = $result->fetch_assoc()) {
	if (is_admin()) {
		$row['host_addr'] = \Dragonfly\Net::decode_ip($row['host_addr']);
		/* or use:
			North America http://whois.arin.net/ui/query.do?queryinput=
			Asian Pacific http://apnic.net/apnic-bin/whois.pl?searchtext=
			Europe        http://ripe.net/whois?searchtext=
			Latin America http://lacnic.net/cgi-bin/lacnic/whois?query=
			Brazil        https://registro.br/cgi-bin/nicbr/whois?qr=
			Korea         http://whois.nida.or.kr/whois/webapisvc?VALUE=
		*/
		$row['host_addr_url'] = 'http://ipinfo.io/'.$row['host_addr'];
	} else {
		$row['host_addr'] = $row['host_addr_url'] = null;
	}
	if (3 == $row['guest']) {
		$who_where['bots'][] = $row;
	} else {
		$who_where['anonymous'][] = $row;
	}
}

$result->free();

$K->OUT->who_where = $who_where;
$content = $K->OUT->toString('blocks/who_where');
unset($K->OUT->who_where);
