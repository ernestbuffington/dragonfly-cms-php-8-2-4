<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /cvs/html/install/sql/data/agents.php,v $
	$Revision: 1.15 $
	$Author: nanocaiordo $
	$Date: 2007/08/04 07:06:09 $
**********************************************/
if (!defined('INSTALL')) { exit; }
global $db, $prefix;

#
# Dumping data for table 'moo_ban'
#

# bots
$stf = '(ban_ipv4_s, ban_ipv4_e, ban_string, ban_type)';
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1148998912', '1148999167', 'Above', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-772936448', '-772935937', 'Alexa', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-806230905', '-806230813', 'Alexa', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1116969472', '1116969535', 'Almaden SE', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1077384193', '1077384447', 'Ask Jeeves', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1103151872', '1103152127', 'Ask Jeeves', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1104552960', '1104553983', 'Ask Jeeves', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1104555049', '1104555263', 'Ask Jeeves', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1104555273', '1104555297', 'Ask Jeeves', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-657948160', '-657947905', 'Ask Jeeves', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-868289280', '-868289025', 'Excite', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-662492928', '-662492673', 'Excite', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1077870336', '1077870591', 'Gigablast', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1078218752', '1078220799', 'Google', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1089052672', '1089060863', 'Google', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1123631104', '1123639295', 'Google', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-655417344', '-655409153', 'Google', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-972697600', '-972696577', 'Infoseek', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-840775680', '-840774657', 'Infoseek', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1113515520', '1113515775', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1120157696', '1120174079', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1113516032', '1113516287', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1122279424', '1122287615', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1150205952', '1150222335', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-895438848', '-895434753', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-779935744', '-779927808', 'Inktomi/Yahoo!', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1089624064', '1089624319', 'Looksmart', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1089597952', '1089598207', 'Looksmart', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-775241728', '-775225345', 'Lycos', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1074003968', '1074020351', 'Hotmail', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1093926912', '1094189055', 'MSN', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-2090139648', '-2090074113', 'MSN', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-819068928', '-819003393', 'MSN', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-817594368', '-817573889', 'MSN', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-1011040233', '-1011040233', 'Netcraft', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-599226048', '-599225985', 'A Korean Bot', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('212795424', '212795439', 'NameProtect', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-898886074', '-898886074', 'Baidu', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1044750848', '1044751103', 'ilse/ingrid', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('1077848066', '1077848123', 'Giga', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-640360320', '-640360193', 'PicSearch', 1)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES('-894098783', '-894098754', 'QihooBot', 1)", TRUE);

# email domains
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'mysite.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'mail.ru', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'no-ip.com', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'mydomain.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'customscoop.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'adminshops.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'artaisle.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'ayayai.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'spamgourmet.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'abv.bg', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'gyuvetch.bg', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'gbg.bg', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'primposta.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'ak47.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'avh.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'cia.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'coder.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'cracker.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'csinibaba.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'eposta.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'fbi.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'geek.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'gyorsposta.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'hello.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'irj.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'jakuza.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'kgb.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'lamer.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'levele.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'maffia.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'me-mail.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'message.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'pobox.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'pro.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'programozo.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'reply.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'send.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'shotgun.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'skizo.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'sniper.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'soldier.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'theend.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'warrior.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'webmail.hu', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'mailinater.', 2)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'bsdmail.', 2)", TRUE);

# referer spam domains
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'adminshops.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'locators.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'southafrica2000.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'bobbemer.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'cm3.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'givemepink.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'allinternal.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'spermswap.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'asstraffic.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'perfectgonzo.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'brutalblowjobs.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'carpet2clean.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'carpetcleaners2.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'adspoll.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'zhaomu.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.lele.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'bpchina.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'played.by', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'playsite.de', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'quickly.to', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'www.sex', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'xxx', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'bookmark', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'unknown', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '95mb.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'splinder.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'blackfilmmakermag.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'bac8.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'pok2.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'ablejobs.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'profitinside.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'china.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'caiku.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.8169.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.eu.tf', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.int.tf', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.us.tf', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.de.tf', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.at.nr', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.ulv.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'seekemploymentnow.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'online-casino-reviews.ws', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'quillicreality.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'mapdes.', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.libero.it', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.siol.net', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.volny.cz', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'home.arcor.de', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'home.tiscali.de', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, '.geocities.com', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'myweb.tiscali.co.uk', 3)", TRUE);
$db->query("INSERT INTO {$prefix}_security {$stf} VALUES(NULL, NULL, 'perso.gratisweb.com', 3)", TRUE);
