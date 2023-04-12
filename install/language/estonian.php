<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2006 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/language/estonian.php,v $
  $Revision: 1.2 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 14:20:58 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Paigaldaja';
$instlang['s_progress'] = 'Paigaldamise edenemine';
$instlang['s_license'] = 'Litsents';
$instlang['s_builddb'] = 'Ehita andmebaas';
$instlang['s_gather'] = 'Kogu kokku tähtis info';
$instlang['s_create'] = 'Loo peakasutaja (super adminni) konto';
$instlang['welcome'] = 'Teretulemast Dragonfly\'sse!';
$instlang['info'] = 'See paigaldus juhatab sind Dragonfly '.CPG_NUKE.' üles seadistamiseks mõne minutiga.<br />Paigaldaja ehitab vajalikud andmebaasid ja esimese kasutaja või uuendab su eelnevalt paigaldatud CPG-Nuke\'i PHP-Nuke\'i.';
$instlang['click'] = 'Vajuta "Olen nõus" kui sa nõustud järgneva litsentsiga:';
//$instlang['license'] = 'CPG Dragonfly CMS tootjate meeskonna poolt tehtud muudatusi ja parandusi ei või kasutada ühegis Nuke\'i versioonis või veebilehel, mis nõuab paigalduse jaoks maksustamist, registratsiooni või tasu, GPL litsentsiga tarkvara tugi või allalaadimine ilma lubatud tasuta CPG Dragonfly CMS tootjate meeskonna poolt, kes võttis ette selle märkimisväärse, tähtsa, ja silmapaistva ümber kirjutamise.<br /><b>See tähendab et sa ei saa müüa meie koodi kui osa mistahes kommertslikust versioonist.</b>';
$instlang['license_edited'] = 'Sinu litsentsi on muudetud. Palun võta ühendust tootjate meeskonnaga dragonflycms.com\'ist  viivitamatult. Täname Sind.';
$instlang['no_zlib'] = 'Sinu server ei toeta Zlib kokkupakkimist. Seega ei saa sa meie litsentsi siit lehelt lugeda. Palun vaata faili GPL.txt, mille leiad oma CPG Dragonfly CMS distributsioonist ja vajuta allolevat "Olen nõus"';
$instlang['agree'] = 'Olen nõus';
$instlang['next'] = 'Edasi';

$instlang['s1_good'] = 'Oleme tänulikud, et tegid valiku kasutada Dragonfly CMS\'i';
$instlang['s1_already'] = 'Sul on juba '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b> paigaldatud.';
$instlang['s1_splatt'] = '<b>Hoiatus</b> Vana foorumi andmebaas kustutatakse! Kui sa tahad seda siiski proovida kasutada, siis jäta tabelid alles.<br />Säilita vana foorumi andmebaas? <select name="splatt" class="formfield"><option value="0">Ei</option><option value="1">Jah</option></select>';
$instlang['s1_new'] = 'Paigaldaja ei leidnud eelmist versiooni, seega paigaldab see sinu jaoks uue versiooni';
$instlang['s1_upgrade'] = 'Sinu praegune versioon on <b>%s</b> ja see uuendatakse/muudetakse versioonile Dragonfly '.CPG_NUKE.'<br /><b>Ole kindel et sul on oma andmebaasist tagavara koopia.</b>';
$instlang['s1_unknown'] = 'Paigaldaja ei suutnud aru saada millist versiooni CPG-Nuke\'ist/PHP-Nuke\'ist sa kasutad.<br />Sa ei saa paigaldusega jätkata.<br />Palun võta ühendust CPG Dragonfly CMS tootjate meeskonnaga';
$instlang['s1_database'] = 'See on kokkuvõte sellest mis sa oled seadistanud config.php failis andmebaasi ühenduseks';

$instlang['s1_dbconfig'] = 'Andmebaasi seadistus';
$instlang['s1_server'] = 'Serveri versioon';
$instlang['s1_server2'] = '%s\'i versioon, mis on hetkel sinu serveril aktiivne';
$instlang['s1_layer'] = 'SQL kiht';
$instlang['s1_layer2'] = 'SQL kiht mida sinu veebilehega kasutada';
$instlang['s1_host'] = 'Majutuse nimi';
$instlang['s1_host2'] = 'Serveri, mis jooksutab MySQL\'i  DNS\'i nimi või IP';
$instlang['s1_username'] = 'Sisselogimise nimi';
$instlang['s1_username2'] = 'Kasutajanimi millega SQL serverisse harjunud sisse logima';
$instlang['s1_password'] = 'Sisse logimise salasõna';
$instlang['s1_password2'] = 'Kasutajanime salasõna et logida SQL serverisse sisse';
$instlang['s1_dbname'] = 'Andmebaasi nimi';
$instlang['s1_dbname2'] = 'Kindla andmebaasi nimi, mis sisaldab soovitud tabeleid andmetega';
$instlang['s1_prefix'] = 'Tabeli eesliide';
$instlang['s1_prefix2'] = 'Vaikimisi eesliide tabelite nimedele';
$instlang['s1_userprefix'] = 'Kasutajate tabeli eesliide';
$instlang['s1_userprefix2'] = 'Vaikimisi eesliide tabelile mis sisaldab kõiki kasutajate andmeid';
$instlang['s1_directory_write'] = 'Kataloogi kirjutamise ligipääs';
$instlang['s1_directory_write2'] = 'Kataloogid mis vajavad kirjutamise ligipääsu teabe hoidmiseks, nagu näiteks üles laetud pildid.<br />Kui mõni ebaõnnestub, siis "CHMOD 777" see kataloog';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Ebaõnnestus aga ei ole väga tähtis';
$instlang['s1_dot_critical'] = 'Kriitiline';

$instlang['s1_cache'] = 'Cache';
$instlang['s1_cache2'] = 'Salvestab vahemälu seaded ja malli failid kiiremaks lehe tekitamiseks';
$instlang['s1_avatars'] = 'Avatars';
$instlang['s1_avatars2'] = 'Kui liikmetel on lubatud avatari üleslaadimine, sisaldab see kataloog nende üleslaetud avatare';
$instlang['s1_albums'] = 'Albums';
$instlang['s1_albums2'] = 'Hoiab kõiki pilte fotogaleriist, mis on üleslaetud läbi FTP või mõnel teisel meetodil';
$instlang['s1_userpics'] = 'Kasutajate pildid';
$instlang['s1_userpics2'] = 'Hoiab alamkatalooge iga liikme ID\'st ja säilitab seal liikmete üleslaetud pilte';
$instlang['s1_config'] = 'Includes';
$instlang['s1_config2'] = 'Hoiab süsteemi faile mida on vaja Dragonfly jooksutamiseks';

$instlang['s1_correct'] = 'Kui üleval olev teave on õige, siis alusta andmebaasi ehitamist';
$instlang['s1_fixerrors'] = 'Palun paranda ennem üleval mainitud vead';
$instlang['s1_fatalerror'] = 'Palun võta ühendust CPG Dragonfly CMS tootjate meeskonnaga vea kohta<br />Sa ei saa paigaldamisega jätkata';
$instlang['s1_build_db'] = 'Ehitame andmebaasi';
$instlang['s1_necessary_info'] = 'Vajalik teave';
$instlang['s1_php'] = '<p style="color:#FF0000; font-style:bold">Me ei saa garanteerida et Dragonfly jookseb korralikult sinu vana PHP versiooniga<br />Küsi oma serveri administraatoril uuendada PHP versioonini PHP 4.3.10 või 5.0.3 või uuemale</p>';
$instlang['s1_mysql'] = '<p style="color: #FF0000; font-style: bold;">Meil on kahju, kuid ainult MySQL 4 või uuem on toetatud<br />Küsi oma serveri administraatoril uuendada MySQL versioonini 4 või uuemale</p>Sinu praegune versioon on: %s';
$instlang['s1_donenew'] = 'Andmebaas on korralikult paigaldatud, seadistame nüüd vajaliku informatsiooni!';
$instlang['s1_optimiz'] = 'Database optimization. The execution of this step might take a while for a big database.';
$instlang['s1_doneup'] = 'Andmebaas on korralikult uuendatud, mölla oma uskumatu Dragonfly\'ga!<br /><h2>Kustuta install.php ja install kataloog kohe!</h2>';

$instlang['s2_info'] = 'Seadistame vajaliku info:';
$instlang['s2_error'] = 'Kõik informatsiooni peab sisestama.';
$instlang['s2_account'] = 'Vajalik info on lisatud. Teeme sulle esimese konto!';
$instlang['s2_create'] = 'Loo konto';

$instlang['s2_domain'] = 'Domeeni nimi';
$instlang['s2_domain2'] = 'Domeeni nimi kus su Dragonfly mootoril jooksev veebilehekülg asub, näiteks <i>www.minuleht.ee</i>';
$instlang['s2_path'] = 'Asukoht';
$instlang['s2_path2'] = 'Asukoht kus su Dragonfly mootoril jooksev veebileht asub, näiteks <i>/html/</i>';
$instlang['s2_email2'] = 'Põhiline e-maili aadress kuhu veebiteave saadetakse';
$instlang['s2_session_path'] = 'Sessiooni salvestamise asukoht';
$instlang['s2_session_path2'] = 'See on asukoht kus säiltatakse andmefaile.<br />Sa pead muutma seda väärtust et kasutada Dragonfly sessiooni funktsioone.<br />Asukoht peav olema PHP kaudu kättesaadav, näiteks /home/minunimi/tmp/sessiondata ja arvatavasti CHMOD väärtusega 777.';
$instlang['s2_cookie_domain'] = 'Küpsise domeen';
$instlang['s2_cookie_domain2'] = 'Täis- või kõige ülemise taseme domeen kus säilitatakse küpsiseid, näiteks <i>minuleht.ee</i> või jäta lihtsalt tühjaks';
$instlang['s2_cookie_path'] = 'Küpsise asukoht';
$instlang['s2_cookie_path2'] = 'Veebiaadress milleni küpsist piirata, näiteks <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Admininistraatori küpsise nimi';
$instlang['s2_cookie_admin2'] = 'Selle veebilehe administraatori sisselogimise teavet sisaldava küpsise nimi';
$instlang['s2_cookie_member'] = 'Liikme küpsise nimi';
$instlang['s2_cookie_member2'] = 'Selle veebilehe liikme sisselogimise teavet sisaldava küpsise nimi';
$instlang['s2_cookie_cpg'] = 'Fotogalerii küpsise nimi';
$instlang['s2_cookie_cpg2'] = 'Selle veebilehe fotogaleriiga seonduvat teavet sisaldava küpsise nimi';

$instlang['s2_error_email'] = 'Kehtetu e-mail';
$instlang['s2_error_empty'] = 'Mõned väljad jäid täitmata';
$instlang['s2_error_cookiename'] = 'Kehtetu küpsise nimi';
$instlang['s2_error_cookiesettings'] = 'Kehtetud küpsiste seadistused';
$instlang['s2_error_sessionsettings'] = 'Valed sessiooni seaded';

$instlang['s2_cookietest'] = 'Enne jätkamist kontrollime küpsiste seadeid, mis sa eelnevalt sisestasid.';
$instlang['s2_test_settings'] = 'Kontrolli seadeid';

$instlang['s3_nick2'] = 'Nimi mida sa sellel veebilehel hakkad kasutama et administraatorina sisse logida';
$instlang['s3_email2'] = 'Sinu e-mail';
$instlang['s3_pass2'] = 'Salasõna mida sa sellel veebilehel sisselogimiseks kasutama hakkad. Sa võid kasutada ükskõik milliseid märke';
$instlang['s3_timezone'] = 'Ajavöönd';
$instlang['s3_timezone2'] = 'Ajavöönd milles sa tahad näha postitatud kirjade aega';

$instlang['s3_warning'] = 'Salasõnas peab olema vähemalt: 1 suurtäht, 1 väiketäht ja 1 number.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' on edukalt paigaldatud.<br />Kustuta install.php ja install kataloog kohe!<br />Siis mölla palju süda lustib!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Sisene mu veebilehele et kõik paika panna</a>';
