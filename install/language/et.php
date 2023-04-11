<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2014 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Paigaldaja';
$instlang['s_progress'] = 'Paigaldamise edenemine';
$instlang['s_license'] = 'Litsents';
$instlang['s_server'] = 'Kontrolli serverit';
$instlang['s_setconfig'] = 'Seadista config.php';
$instlang['s_builddb'] = 'Ehita andmebaas';
$instlang['s_gather'] = 'Kogu kokku tähtis info';
$instlang['s_create'] = 'Loo peakasutaja (super adminni) konto';
$instlang['welcome'] = 'Teretulemast Dragonfly\'sse!';
$instlang['info'] = 'See paigaldus juhatab sind Dragonfly '.CPG_NUKE.' üles seadistamiseks mõne minutiga.<br />Paigaldaja ehitab vajalikud andmebaasid ja esimese kasutaja või uuendab su eelnevalt paigaldatud CPG-Nuke\'i PHP-Nuke\'i.';
$instlang['click'] = 'Vajuta "Olen nõus" kui sa nõustud järgneva litsentsiga:';
$instlang['no_zlib'] = 'Sinu server ei toeta Zlib kokkupakkimist. Seega ei saa sa meie litsentsi siit lehelt lugeda. Palun vaata faili GPL.txt, mille leiad oma CPG Dragonfly CMS distributsioonist ja vajuta allolevat "Olen nõus"';
$instlang['agree'] = 'Olen nõus';
$instlang['next'] = 'Edasi';

$instlang['s1_already'] = 'Sul on juba '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b> paigaldatud.';
$instlang['s1_new'] = 'Paigaldaja ei leidnud eelmist versiooni, seega paigaldab see sinu jaoks uue versiooni';
$instlang['s1_upgrade'] = 'Sinu praegune versioon on <b>%s</b> ja see uuendatakse/muudetakse versioonile Dragonfly '.CPG_NUKE.'<br /><b>Ole kindel et sul on oma andmebaasist tagavara koopia.</b>';
$instlang['s1_unknown'] = 'Paigaldaja ei suutnud aru saada millist versiooni CPG-Nuke\'ist/PHP-Nuke\'ist sa kasutad.<br />Sa ei saa paigaldusega jätkata.<br />Palun võta ühendust CPG Dragonfly CMS tootjate meeskonnaga';
$instlang['s1_database'] = 'See on kokkuvõte sellest mis sa oled seadistanud config.php failis andmebaasi ühenduseks';

$instlang['s1_dbconfig'] = 'Andmebaasi seadistus';
$instlang['s1_server2'] = '%s-i versioon, mis on hetkel sinu serveril aktiivne';
$instlang['s1_layer'] = 'SQL kiht';
$instlang['s1_layer2'] = 'SQL kiht mida sinu veebilehega kasutada';
$instlang['s1_host'] = 'Majutuse nimi';
$instlang['s1_host2'] = 'MySQL serveri  DNS-i nimi või IP';
$instlang['s1_username'] = 'Sisselogimise nimi';
$instlang['s1_username2'] = 'SQL serverisse sisse logimiseks kasutatav kasutajanimi';
$instlang['s1_password'] = 'Sisse logimise salasõna';
$instlang['s1_password2'] = 'Kasutajanime salasõna et logida SQL serverisse sisse';
$instlang['s1_dbname'] = 'Andmebaasi nimi';
$instlang['s1_dbname2'] = 'Kindla andmebaasi nimi, mis sisaldab soovitud tabeleid andmetega';
$instlang['s1_prefix'] = 'Tabeli eesliide';
$instlang['s1_prefix2'] = 'Vaikimisi eesliide tabelite nimedele';
$instlang['s1_directory_write'] = 'Kataloogi kirjutamise ligipääs';
$instlang['s1_directory_write2'] = 'Kataloogid mis vajavad kirjutamise ligipääsu teabe hoidmiseks, nagu näiteks üles laetud pildid.<br />Kui mõni ebaõnnestub, siis "CHMOD 777" see kataloog';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Ebaõnnestus aga ei ole väga tähtis';
$instlang['s1_dot_critical'] = 'Kriitiline';

$instlang['s1_server_settings'] = 'Serveri sätted';
$instlang['s1_setting'] = 'säte';
$instlang['s1_preferred'] = 'eelistatud';
$instlang['s1_yours'] = 'sinul';
$instlang['s1_on'] = 'Sees';
$instlang['s1_off'] = 'Väljas';

$instlang['s1_correct'] = 'Kui üleval olev teave on õige, siis alusta andmebaasi ehitamist';
$instlang['s1_fixerrors'] = 'Palun paranda ennem üleval mainitud vead';
$instlang['s1_fatalerror'] = 'Palun võta ühendust CPG Dragonfly CMS tootjate meeskonnaga vea kohta<br />Sa ei saa paigaldamisega jätkata';
$instlang['s1_build_db'] = 'Ehitame andmebaasi';
$instlang['s1_necessary_info'] = 'Vajalik teave';
$instlang['s1_donenew'] = 'Andmebaas on korralikult paigaldatud, seadistame nüüd vajaliku informatsiooni!';
$instlang['s1_doneup'] = 'Andmebaas on korralikult uuendatud, mölla oma uskumatu Dragonfly\'ga!<br /><h2>Kustuta install.php ja install kataloog kohe!</h2>';
$instlang['s1_trying_to_connect'] = 'SQL serveriga ühendamise proovimine';
$instlang['s1_wrong_database_name'] = 'Sa pead valima mingi muu andmebaasi nime.<br />Vabandust ebameeldivuse pärast, kuid sa ei saa andmebaasi nimena "<b>public</b>" kasutada.';
$instlang['s1_save_conf_succeed'] = 'Seadete salvestamine õnnestus';
$instlang['s1_save_conf_failed'] = 'Seadete salvestamine ebaõnnestus';
$instlang['s1_db_connection_succeeded'] = 'Andmebaasiga ühendamine õnnestus';

$instlang['s2_info'] = 'Seadistame vajaliku info:';
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

$instlang['s2_error_email'] = 'Kehtetu e-mail';
$instlang['s2_error_empty'] = 'Mõned väljad jäid täitmata';
$instlang['s2_error_cookiename'] = 'Kehtetu küpsise nimi';
$instlang['s2_error_cookiesettings'] = 'Kehtetud küpsiste seadistused';
$instlang['s2_error_sessionsettings'] = 'Valed sessiooni seaded';

$instlang['s2_cookietest'] = 'Enne jätkamist kontrollime küpsiste seadeid, mis sa eelnevalt sisestasid.';
$instlang['s2_test_settings'] = 'Kontrolli seadeid';

$instlang['s3_sync_schema'] = 'Synchronizing Database Schema';
$instlang['s3_sync_data']   = 'Synchronizing Database Data';
$instlang['s3_sync_done']   = 'Synchronization done';
$instlang['s3_exec_queries'] = 'Executing additional queries';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'Nimi mida sa sellel veebilehel hakkad kasutama et administraatorina sisse logida';
$instlang['s3_email2'] = 'Sinu e-mail';
$instlang['s3_pass2'] = 'Salasõna mida sa sellel veebilehel sisselogimiseks kasutama hakkad. Sa võid kasutada ükskõik milliseid märke';
$instlang['s3_timezone'] = 'Ajavöönd';
$instlang['s3_timezone2'] = 'Ajavöönd milles sa tahad näha postitatud kirjade aega';

$instlang['s3_warning'] = 'Salasõnas peab olema vähemalt: 1 suurtäht, 1 väiketäht ja 1 number.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' on edukalt paigaldatud.<br />Kustuta install kataloog kohe!<br />Siis mölla palju süda lustib!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Sisene mu veebilehele et kõik paika panna</a>';
