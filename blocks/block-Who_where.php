<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Who_where.php,v $
  $Revision: 9.7 $
  $Author: phoenix $
  $Date: 2007/01/23 10:28:16 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $prefix, $user_prefix, $db;
$content = '';

$memres = $db->sql_query('SELECT s.uname, s.module, s.url, u.user_allow_viewonline FROM '.$prefix.'_session AS s LEFT JOIN '.$user_prefix.'_users AS u ON u.username=s.uname WHERE guest=0 OR guest=2 ORDER BY s.uname');
$online_num = $db->sql_numrows($memres);
$hidden = 0;
if($online_num > 0) {
  $content .= '<img src="images/blocks/members.gif" alt="" />&nbsp;<span class="content"><b>'._BMEM.':</b></span><br />';
  for ($i = 1; $i <= $online_num; $i++) {
	$session = $db->sql_fetchrow($memres, SQL_ASSOC);
	if ($session['user_allow_viewonline'] || is_admin()) {
		$ttt = '<a href="'.getlink("Your_Account&amp;profile=$session[uname]")."\">";
		if ($session['user_allow_viewonline']) $ttt .= "$session[uname]</a> &gt;";
		else $ttt .= "<i>$session[uname]</i></a> &gt;";

		if ($i < 10 && $online_num > 99) { $content .= '00'; }
		else if (($i < 10 && $online_num > 9) || ($i < 99 && $online_num > 99)) { $content .= '0'; }
		$content .= "$i: $ttt <a href=\"$session[url]\">$session[module]</a><br />\n";
	} else {
		$hidden++;
	}
  }
}
$db->sql_freeresult($memres);

$online = array(1=>array(),3=>array());
$anonres = $db->sql_uquery('SELECT host_addr, uname, module, url, guest FROM '.$prefix.'_session WHERE guest=1 OR guest=3 ORDER BY guest, host_addr');
while($session = $db->sql_fetchrow($anonres, SQL_ASSOC)) {
	$online[$session['guest']][] = $session;
}

$online_num = count($online[3]);
if($online_num > 0) {
  $content .= '<img src="images/blocks/visitors.gif" alt="" />&nbsp;<span class="content"><b>'._BOTS.':</b></span><br />';
  for ($i = 1; $i <= $online_num; $i++) {
	$session = $online[3][$i-1];
	if ($i < 10 && $online_num > 99) { $content .= '00'; }
	else if (($i < 10 && $online_num > 9) || ($i < 99 && $online_num > 99)) { $content .= '0'; }
	$content .= "$i: ".$session['uname']." &gt; <a href=\"$session[url]\"> $session[module]</a><br />\n";
  }
  unset($online[3]);
}

$online_num = count($online[1]);
if($online_num > 0) {
  $content .= '<img src="images/blocks/visitors.gif" alt="" />&nbsp;<span class="content"><b>'._BVIS.':</b></span><br />';
  for ($i = 1; $i <= $online_num; $i++) {
	$session = $online[1][$i-1];
	if ($i < 10 && $online_num > 99) { $content .= '00'; }
	else if (($i < 10 && $online_num > 9) || ($i < 99 && $online_num > 99)) { $content .= '0'; }
	/* or use:
		Asian Pacific http://apnic.net/apnic-bin/whois.pl?searchtext=
		Europe        http://ripe.net/whois?searchtext=
		Latin America http://lacnic.net/cgi-bin/lacnic/whois?query=
		Brazil        https://registro.br/cgi-bin/nicbr/whois?qr=
		Korea         http://whois.nida.or.kr/whois/webapisvc?VALUE=
	*/
	$content .= "$i: ".(is_admin() ? '<a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput='.decode_ip($session['host_addr']).'" target="_blank" title="Query ARIN Whois">'.(strlen(decode_ip($session['host_addr'])) > 17 ? substr(decode_ip($session['host_addr']),0,17).'...' : decode_ip($session['host_addr'])).'</a> &gt;' : '')." <a href=\"$session[url]\"> $session[module]</a><br />\n";
  }
  unset($online[1]);
}

if ($hidden > 0) {
	$content .= '<span class="content"><b>'._BHID.':</b></span> '.$hidden;
}
