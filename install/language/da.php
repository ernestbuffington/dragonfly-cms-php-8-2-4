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

$instlang['installer'] = 'Instalation';
$instlang['s_progress'] = 'Installerings proses';
$instlang['s_license'] = 'Licens';
$instlang['s_server'] = 'Check server';
$instlang['s_setconfig'] = 'Set config.php';
$instlang['s_builddb'] = 'byg database';
$instlang['s_gather'] = 'Indsaml vigtige infos';
$instlang['s_create'] = 'Opret Super admin bruger';
$instlang['welcome'] = 'Velkommen til installationen af dragonflycms';
$instlang['info'] = 'Denne installation vil guide dig til opsætning Dragonfly på dit websted inden for få minutter.<br />Installationsprogrammet vil opbygge den nødvendige database og første bruger eller vil opgradere din allerede installeret CPG eller PHP-Nuke.';
$instlang['click'] = 'Klik på "Jeg accepterer", hvis du acceptere følgende licens:';
$instlang['no_zlib'] = '	Din server understøtter ikke zlib Compression. Således kan du ikke læse vores licens fra denne side. Se venligst GPL.txt findes i din CPG-Nuke distribution, og klik på "Jeg accepterer" nedenfor';
$instlang['agree'] = 'Jeg Acceptere';
$instlang['next'] = 'Næste';

$instlang['s1_already'] = 'Du har allerede '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b> Installeret.';
$instlang['s1_new'] = 'The installer couldn\'t find a previous version, so it will install a new version for you';
$instlang['s1_upgrade'] = 'Din nuværende version er <b>%s</b>, og det vil blive opgraderet / konverteres til Dragonfly '.CPG_NUKE.'<br /><b>Vær sikker på, at du har en sikkerhedskopi af din database.</b>';
$instlang['s1_unknown'] = '	Installationsprogrammet kunne ikke afsløre, hvilken version af CPG-Nuke/PHP-Nuke du bruger.<br />Du kan ikke gøre installationen færdi<br />Vær sød at kontakte cpgnuke dev team';
$instlang['s1_database'] = 'Dette er et resumé af, hvad vi setup i config.php for database forbindelse';

$instlang['s1_dbconfig'] = 'Database Konfiguration';
$instlang['s1_server2'] = '	Den version af %s, som i øjeblikket er aktiv på din server';
$instlang['s1_layer'] = 'SQL Lag';
$instlang['s1_layer2'] = 'SQL lag til brug sammen med din hjemmeside';
$instlang['s1_host'] = 'Værtsnavn / Host';
$instlang['s1_host2'] = 'DNS-navnet eller IP på den server, der kører SQL server';
$instlang['s1_username'] = 'Log ind navn / brugernavn';
$instlang['s1_username2'] = 'Brugernavnet bruges til at logge på SQL server';
$instlang['s1_password'] = 'Log ind password';
$instlang['s1_password2'] = 'Adgangskoden til brugernavn til at logge på SQL server';
$instlang['s1_dbname'] = 'Database navn';
$instlang['s1_dbname2'] = 'Navnet på en særlig database, der indeholder de ønskede tabeller med data';
$instlang['s1_prefix'] = 'Tabel præfiks / Tabel navn';
$instlang['s1_prefix2'] = 'En default prefix for tablenames';
$instlang['s1_directory_write'] = 'Directory Skriv Access';
$instlang['s1_directory_write2'] = 'Mapper som behovet skrive adgang til at gemme informationer gerne uploadet billeder.<br />Hvis man ikke derefter "chmod 777" biblioteket';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Det lykkedes ikke, men ikke kritisk';
$instlang['s1_dot_critical'] = 'Kritisk';

$instlang['s1_server_settings'] = 'Server settings';
$instlang['s1_setting'] = 'setting';
$instlang['s1_preferred'] = 'preferred';
$instlang['s1_yours'] = 'yours';
$instlang['s1_on'] = 'On';
$instlang['s1_off'] = 'Off';

$instlang['s1_correct'] = 'Hvis ovenstående oplysninger er korrekte, så lad begynde at opbygge databasen';
$instlang['s1_fixerrors'] = 'Ret de fejl, nævnt ovenfor først';
$instlang['s1_fatalerror'] = 'Please contact the CPG-Nuke Dev Team about the error<br />You cannot continue with the installation';
$instlang['s1_build_db'] = 'Lad os bygge databasen';
$instlang['s1_necessary_info'] = 'Nødvendige Info';
$instlang['s1_donenew'] = '	Databasen er blevet installeret korrekt, nu Lad os opsætning nogle nødvendige oplysninger!';
$instlang['s1_doneup'] = 'Databasen er blevet behørigt opdateret, har det sjovt med din utrolige Dragonfly!<br /><h2>Fjern install.php og installere bibliotek lige nu!</h2>';
$instlang['s1_trying_to_connect'] = 'Trying to connect to SQL server';
$instlang['s1_wrong_database_name'] = 'You need to choose a different database name.<br />Sorry for the inconvenience but you cannot continue with the installation with "<b>public</b>" as database name.';
$instlang['s1_save_conf_succeed'] = 'Saving configuration succeeded';
$instlang['s1_save_conf_failed'] = 'Saving configuration failed';
$instlang['s1_db_connection_succeeded'] = 'Database connection succeeded';

$instlang['s2_info'] = 'Setup de nødvendige info:';
$instlang['s2_account'] = 'De nødvendige oplysninger er blevet tilføjet. Lad os opsætte din første konto!';
$instlang['s2_create'] = 'Opret bruger';

$instlang['s2_domain'] = 'Domæne navn';
$instlang['s2_domain2'] = 'Domænenavnet hvor din Dragonfly website er vært, for eksempel<i>www.mysite.com</i>';
$instlang['s2_path'] = 'Side';
$instlang['s2_path2'] = 'Stien, hvor dine Dragonfly website er vært, for eksempel<i>/html/</i>';
$instlang['s2_email2'] = '	Den vigtigste e-mail-adresse, hvor websted oplysninger skal sendes til';
$instlang['s2_session_path'] = 'Session Gem side';
$instlang['s2_session_path2'] = 'Dette er den sti, hvor data gemmes.<br />Du skal ændre denne variabel for at bruge Dragonfly\\\'s session funktion.<br />Stien skal være tilgængelig ved PHP gerne /home/myname/tmp/sessiondata og sandsynligvis CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Cookie Domæne';
$instlang['s2_cookie_domain2'] = 'Den fulde eller topdomæne til at gemme cookies i, for eksempel <i>mysite.com</i> eller bare lade være tom';
$instlang['s2_cookie_path'] = 'Cookie side';
$instlang['s2_cookie_path2'] = 'Den web-adresse for at begrænse cookie til for eksempel <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Admin cookie navn';
$instlang['s2_cookie_admin2'] = 'Navnet på den cookie til at gemme administrator login-oplysninger på dette websted';
$instlang['s2_cookie_member'] ='Member cookie navn';
$instlang['s2_cookie_member2'] = 'Navnet på den cookie til at gemme bruger login-oplysninger på dette websted';

$instlang['s2_error_email'] = 'Ugyldig e-mail-adresse';
$instlang['s2_error_empty'] = 'Nogle områder var overladt tom';
$instlang['s2_error_cookiename'] = 'Ugyldig cookie-navn';
$instlang['s2_error_cookiesettings'] = 'Ugyldig cookie-indstillinger';
$instlang['s2_error_sessionsettings'] = 'Forkert samling indstillinger';

$instlang['s2_cookietest'] = 'Vi vil teste cookie-indstillinger, som du får specificeret før vi går videre.';
$instlang['s2_test_settings'] = 'Test indstillinger';

$instlang['s3_sync_schema'] = 'Synchronizing Database Schema';
$instlang['s3_sync_data']   = 'Synchronizing Database Data';
$instlang['s3_sync_done']   = 'Synchronization done';
$instlang['s3_exec_queries'] = 'Executing additional queries';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'Det navn, du bruger til at logge ind på denne hjemmeside som administrator';
$instlang['s3_email2'] = 'Din email adresse';
$instlang['s3_pass2'] = 'Den adgangskode, du bruger til at logge ind på denne hjemmeside. Du kan bruge enhver karakter';
$instlang['s3_timezone'] = 'Tidszone';
$instlang['s3_timezone2'] = 'Den tidszone, hvor du ønsker at se tidspunktet for indsendt indlæg';

$instlang['s3_warning'] = 'Være sikker på, at du bruger mindst: 1 stort, 1 lille og 1 tal i din adgangskode.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' er blevet installeret.<br />Fjern installere bibliotek lige nu!<br />Søger du dansk support til dragonflycms.dk kan du besøge os på www.dragonflycms.dk </h2><a href="'.$adminindex.'" style="font-size: 14px;">Gå til mit websted og sæt det op</a>';
