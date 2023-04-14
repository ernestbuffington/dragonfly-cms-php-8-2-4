<?php
/*********************************************
	MOO CMS, Copyright (c) 2007 The MOO Dev. Group. All rights reserved.

	This source file is free software; you can redistribute it and/or
	modify it under the terms of the MOO Public License as published
	by the MOO Development Group; either version 1 of the License, or
	(at your option) any later version.

  $Source: /public_html/includes/data/ua.inc,v $
  $Revision: 1.3 $
  $Author: nanocaiordo $
  $Date: 2007/08/20 04:28:54 $
**********************************************/
if (!defined('CPG_NUKE')) exit();

function ua_set_data($ua, $os, $engine, $extra, &$agent)
{
	if (!empty($ua)) {
		$agent = array(
			'ua' => $ua,
			'os' => $os,
			'engine' => empty($engine) ? $ua : $engine,
			'ext' => $extra
		);
	}
}

$ua_pattern = array(
	# Netscape
	'#^Mozilla/[34]\.[0-8]{1,2}( \[[a-zA-Z\-]{2,5}\])? \(([a-zA-Z0-9]+); [UI]#e',
	# Gecko family: Netscape, Firefox, Thunderbird, Camino, Galeon, Epiphany, Linspire, MultiZilla, K-Meleon, WebWasher, Mozilla
	'#^Mozilla/5\.0 \(([a-zA-Z0-9]+); U; (.*[^;])(; [a-zA-Z\-]{2,5})?; rv:[0-9\.]+.*?\) Gecko/[0-9]{8}.*(Firefox|Minefield).*#e',
	'#^Mozilla/5\.0 \(([a-zA-Z0-9]+); U; (.*[^;])(; [a-zA-Z\-]{2,5})?; rv:[0-9\.]+.*?\) Gecko/[0-9]{8}( \(No IDN\))? ([a-zA-Z6\-]+)[/|\s]+[0-9\.]+.*#e',
	'#^Mozilla/5\.0 \(([a-zA-Z0-9]+); U; (.*[^;])(; [a-zA-Z\-]{2,5})?; rv:[0-9\.]+.*?\) Gecko/[0-9]{8}( \(No IDN\))?$#e',
	# Galeon
	'#^Mozilla/5\.0 (Galeon)/[0-9\.]+ \(([a-zA-Z0-9]+); (.*[^;]); U\)#e',
	# Konqueror
	'#^Mozilla/5\.0 \(compatible; (Konqueror)/[0-9\.\-rc]+[;)] (i686 )?(Linux|FreeBSD|KHTML).*#e',
	# Lynx
	'#^(Lynx)/2\.[0-9\.]+(rel|dev)[0-9\.]+ libwww-FM/.*#e',
	# Safari family: Safari, OmniWeb, Shiira, DEVONtech
	'#^Mozilla/5\.0 \(Macintosh; U; (PPC|Intel) Mac OS X; [a-zA-Z\-]{2,5}\) AppleWebKit/.*? \(KHTML, like Gecko.*?\) ([a-zA-Z]+)/.*#e',
	'#^Mozilla/5\.0 \(Windows; U; Windows NT [0-9\.]{3}; [a-zA-Z\-]{2,5}\) AppleWebKit/.*? \(KHTML, like Gecko.*?\) Version/.* ([a-zA-Z]+)/.*#e',
	# w3m
	'#^(w3m)/[0-9\.]+#e',
	# Links
	'#^(Links) \([0-9]\.[a-z0-9]+; ([a-zA-Z]+) #e',
	# ELinks
	'#^(ELinks)/0\.[0-9\.]+ \([a-z]+; ([\w]+); #e',
	# Voyager
	'#^Mozilla/4\.0 \(compatible; (Voyager); (AmigaOS).*#e',
	# Opera
	'#^(Opera)/[67]\.[0-9]{1,2} \(([\w]+).*[^;]; U\)[\ ]{1,2}\[[a-zA-Z\-]{2,5}\]#e', # Opera 6-7
	'#^(Opera)/[89]\.[0-9]{1,2} \(([\w]+).*[^;]; U; [a-zA-Z\-]{2,5}\)#e', # Opera 8-9
	'#^Mozilla/[45]\.0 \(compatible; MSIE [56]\.0; (.*?)\) (Opera) [567]\.[0-9]{1,2} \[[a-zA-Z\-]{2,5}\]#e', # Opera 6-7 faking IE
	'#^Mozilla/5\.0 \((.*?); U\) (Opera) [67]\.[0-9]{1,2} \[[a-zA-Z\-]{2,5}\]#e', # Opera 6-7 faking Gecko
	'#^Mozilla/4\.0 \(compatible; MSIE 6\.0; (.*?); [a-zA-Z\-]{2,5}\) (Opera) [89]\.[0-9]{1,2}#e', # Opera 8-9 faking IE
	'#^Mozilla/5\.0 \((.*?); U; [a-zA-Z\-]{2,5}\) (Opera) [89]\.[0-9]{1,2}#e', # Opera 8-9 faking Gecko
	'#^Mozilla/5\.0 \(([\w]*).*[^;]; U; [a-zA-Z\-]{2,5}; rv:.*[^;]\) Gecko/.* Firefox/.* (Opera) 9\.[0-9]{1,2}#e', # Opera 9.2 faking Gecko
	# IE
	'#^Mozilla/4\.0 \([a-z]+; MSIE ([4567]\.0|5\.5)[b1]?(; .*[^;])?; (Windows) [A-Z0-9\ \.]+[;)](.*[^)])?#e',
	'#^Mozilla/2\.0 \(compatible; MSIE ([34]\.0)[1]?(; .*[^;])?; (Windows) [A-Z0-9\ \.]+[;)](.*)?#e',
	'#^Mozilla/4\.0 \(compatible; MSIE 5\.[1-2][1-7]; Mac_PowerPC\)#e', # 5.: 13, 16, 17, 21, 22, 23
	# Dillo/0.8.5-i18n-misc
	'#^Dillo/[0-9\.]+.*#e',
	# mobile phones
	'#^KWC-[a-zA-Z0-9]+/[0-9\.]+ UP\.Browser/[0-9\.]+#e',
	'#^LG-[A-Z0-9]+ (.*?)Profile/MIDP-[12]#e',
	'#^Nokia[0-9i]+/[0-9\.]+ \([0-9\.]+\) (.*?)Profile/MIDP-[12]#e',
	'#^SAMSUNG-[A-Z0-9\-]+/[A-Z0-9]+ UP\.Browser/[0-9\.]+#e',
	'#^SonyEricsson[a-zA-Z0-9]+/[A-Z0-9]+ (.*?)Profile/MIDP-[12]#e',
	'#^Mozilla/5\.0 \(iPhone; U; CPU like Mac OS X; [a-zA-Z\-]{2,5}\) AppleWebKit/[0-9\.\+]+ \(KHTML, like Gecko\) Version/.* Mobile/.* Safari/.*#e',
	# PlayStation
	'#^Mozilla/4\.0 \(PSP \(PlayStation Portable\); 2\.00\)#e',
);
$ua_replacement = array(
	# Netscape
	'ua_set_data(\'Netscape\', \'\\1\', \'Gecko\', \'\', $agent)',
	# Gecko family
	'ua_set_data(\'\\4\', \'\\2\', \'Gecko\', \'\', $agent)',
	'ua_set_data(\'\\5\', \'\\2\', \'Gecko\', \'\', $agent)',
	'ua_set_data(\'Mozilla\', \'\\1\', \'Gecko\', \'\', $agent)',
	# Galeon
	'ua_set_data(\'\\1\', \'\\3\', \'\', \'\', $agent)',
	# Konqueror
	'ua_set_data(\'\\1\', \'\\3\', \'KHTML\', \'\', $agent)',
	# Lynx
	'ua_set_data(\'\\1\', \'\', \'\', \'\', $agent)',
	# Safari family
	'ua_set_data(\'\\2\', \'Mac\', \'Safari\', \'\', $agent)',
	'ua_set_data(\'\\1\', \'Windows\', \'Safari\', \'\', $agent)',
	# w3m
	'ua_set_data(\'\\1\', \'\', \'\', \'\', $agent)',
	# Links
	'ua_set_data(\'\\1\', \'\\2\', \'\', \'\', $agent)',
	# ELinks
	'ua_set_data(\'\\1\', \'\\2\', \'\', \'\', $agent)',
	# Voyager
	'ua_set_data(\'\\1\', \'\\2\', \'\', \'\', $agent)',
	# Opera
	'ua_set_data(\'\\1\', \'\\2\', \'\', \'\', $agent)',
	'ua_set_data(\'\\1\', \'\\2\', \'\', \'\', $agent)',
	'ua_set_data(\'\\2\', \'\\1\', \'\', \'\', $agent)',
	'ua_set_data(\'\\1\', \'\\2\', \'\', \'\', $agent)',
	'ua_set_data(\'\\2\', \'\\1\', \'\', \'\', $agent)',
	'ua_set_data(\'\\2\', \'\\1\', \'\', \'\', $agent)',
	'ua_set_data(\'\\2\', \'\\1\', \'\', \'\', $agent)',
	# IE
	'ua_set_data(\'MSIE\', \'\\3\', \'\', \'\\4\', $agent)',
	'ua_set_data(\'MSIE\', \'\\3\', \'\', \'\\4\', $agent)',
	'ua_set_data(\'MSIE\', \'Mac\', \'\', \'\\4\', $agent)',
	# Dillo
	'ua_set_data(\'Dillo\', \'Linux\', \'\', \'\', $agent)',
	# mobile phones
	'ua_set_data(\'WAP\', \'\', \'\', \'KWC\', $agent)',
	'ua_set_data(\'WAP\', \'\', \'\', \'LG\', $agent)',
	'ua_set_data(\'WAP\', \'\', \'\', \'Nokia\', $agent)',
	'ua_set_data(\'WAP\', \'\', \'\', \'SAMSUNG\', $agent)',
	'ua_set_data(\'WAP\', \'\', \'\', \'SonyEricsson\', $agent)',
	'ua_set_data(\'WAP\', \'\', \'\', \'iPhone\', $agent)',
	# PlayStation
	'ua_set_data(\'PlayStation\', \'\', \'\', \'Sony\', $agent)',
);
preg_replace($ua_pattern, $ua_replacement, $_SERVER['HTTP_USER_AGENT']);
$ua_pattern = $ua_replacement = null;
# If we didn't detect a valid browser check for a bot
if (!isset($agent['ua'])) { $agent = Security::_detectBot(); return;}
# Detect IE based browsers
else if ($agent['ua'] == 'MSIE') {
	preg_match_all('#(iRider|Crazy Browser|NetCaptor|Maxthon|Avant Browser)#s', $agent['ext'], $regs);
	if (!empty($regs[0])) {
		$agent['ua'] = str_replace(' Browser','',$regs[0][count($regs[0])-1]);
		$agent['ext'] = '';
	}
}
preg_match('#(Win|Mac|Linux|FreeBSD|SunOS|IRIX|BeOS|OS/2|AIX|Amiga)#is', $agent['os'], $regs);
$agent['os'] = $regs[0];
if ($agent['os'] == 'Win') $agent['os'] = 'Windows';
return $agent;
