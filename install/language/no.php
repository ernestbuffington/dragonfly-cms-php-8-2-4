<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2014 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Translation by Pitcher

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Installer';
$instlang['s_progress'] = 'Installerings-progress';
$instlang['s_license'] = 'Lisens';
$instlang['s_server'] = 'Check server';
$instlang['s_setconfig'] = 'Set config.php';
$instlang['s_builddb'] = 'Bygge database';
$instlang['s_gather'] = 'Innhente viktig informasjon';
$instlang['s_create'] = 'Lage super administrator konto';
$instlang['welcome'] = 'Velkommen til Dragonfly!';
$instlang['info'] = ' Du vil med hjelp av denne installeringen, ha installert Dragonfly versjon '.CPG_NUKE.' på ditt nettsted i løpet av få minutter.<br />Installasjonen vil bygge opp den nødvendige databasen og første brukerkontoen eller oppgradere din allerede eksisterende CPG eller PHP-Nuke.';
$instlang['click'] = 'Klikk "Jeg Godkjenner" hvis du aksepterer følgende lisens:';
$instlang['no_zlib'] = 'Din server støtter ikke Zlib Kompresjon. Derfor kan du ikke lese vår lisens fra denne siden. Vennligst se GPL.txt som finnes i din CPG-Nuke distribusjon og klikk "Jeg Godkjenner" nedenfor';
$instlang['agree'] = 'Jeg Godkjenner';
$instlang['next'] = 'Neste';

$instlang['s1_already'] = 'Du har allerede '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <strong>'.CPG_NUKE.'</strong> installert.';
$instlang['s1_new'] = 'Installereren kunne ikke finne noen eldre versjoner, så den vil installere en ny versjon til deg.';
$instlang['s1_upgrade'] = 'Din nåværende versjon er <strong>%s</strong>, og den vil bli oppgradert til Dragonfly '.CPG_NUKE.'<br /><strong>Vær sikker på at du har tatt en sikkerhetskopi av din database.</strong>';
$instlang['s1_unknown'] = 'Installereren kunne ikke finne ut vilken versjon av CPG-Nuke/PHP-Nuke du bruker.<br />Du kan ikke fortsette installasjonen.<br />Vennligst kontakt CPG Dev Team';
$instlang['s1_database'] = 'Dette er en oversikt over hva du har satt i config.php for database oppkoblingen';

$instlang['s1_dbconfig'] = 'Databasekonfigurasjon';
$instlang['s1_server2'] = 'Versjonen av %s som er aktiv på din server';
$instlang['s1_layer'] = 'SQL Layer';
$instlang['s1_layer2'] = 'The SQL layer to use with your website';
$instlang['s1_host'] = 'Tjenernavn';
$instlang['s1_host2'] = 'DNS navn eller IP på serveren som kjører MySQL';
$instlang['s1_username'] = 'Innloggingsnavn';
$instlang['s1_username2'] = 'Brukernavnet som blir brukt for å logge deg inn på SQL serveren';
$instlang['s1_password'] = 'Innloggingspassord';
$instlang['s1_password2'] = 'Passordet som blir brukt for å logge deg inn på SQL serveren';
$instlang['s1_dbname'] = 'Databasenavn';
$instlang['s1_dbname2'] = 'navnet på en spesifik database som inneholder tabeller med data';
$instlang['s1_prefix'] = 'Tabell prefiks';
$instlang['s1_prefix2'] = 'En standard prefiks for tabellnavn';
$instlang['s1_directory_write'] = 'Folder skrive tilgang';
$instlang['s1_directory_write2'] = 'Foldere som trenger skrivetilgang for å lagre innformasjon som opplastetede bilder.<br />Hvis noen feiler så bruk "CHMOD 777" på mappen';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Feilet, men ikke kritisk';
$instlang['s1_dot_critical'] = 'Kritisk feil';

$instlang['s1_server_settings'] = 'Server settings';
$instlang['s1_setting'] = 'setting';
$instlang['s1_preferred'] = 'preferred';
$instlang['s1_yours'] = 'yours';
$instlang['s1_on'] = 'On';
$instlang['s1_off'] = 'Off';

$instlang['s1_correct'] = 'Hvis informasjonen er korrekt så start oppbyggingen av databasen.';
$instlang['s1_fixerrors'] = 'Vennligst fiks feilene over først';
$instlang['s1_fatalerror'] = 'Vennligst kontakt CPG-Nuke Dev Team om denne feilen<br />Du kan ikke fortsette med innstallasjonen';
$instlang['s1_build_db'] = 'La oss bygge opp databasen';
$instlang['s1_necessary_info'] = 'Nødvendig Informasjon';
$instlang['s1_donenew'] = 'Databasen er installert, la oss nå sette opp noe nødvendig informasjon!';
$instlang['s1_doneup'] = 'Databasen ble velykket oppdatert, ha det gøy med din nye Dragonfly!<br /><h2>Slett install.php og install mappen nå!</h2>';
$instlang['s1_trying_to_connect'] = 'Trying to connect to SQL server';
$instlang['s1_wrong_database_name'] = 'You need to choose a different database name.<br />Sorry for the inconvenience but you cannot continue with the installation with "<b>public</b>" as database name.';
$instlang['s1_save_conf_succeed'] = 'Saving configuration succeeded';
$instlang['s1_save_conf_failed'] = 'Saving configuration failed';
$instlang['s1_db_connection_succeeded'] = 'Database connection succeeded';

$instlang['s2_info'] = 'La oss sette opp nødvendig informasjon:';
$instlang['s2_account'] = 'Nødvendig informasjon har blitt oppdatert. La oss lage din første konto!';
$instlang['s2_create'] = 'Lag Konto';

$instlang['s2_domain'] = 'Domenenavn';
$instlang['s2_domain2'] = 'Domenenavnet hvor din Dragonfly websted ligger, for eksempel <i>www.mittdomene.no</i>';
$instlang['s2_path'] = 'Sti';
$instlang['s2_path2'] = 'Stien der din Dragonfly ligger, for eksempel <i>/html/</i>';
$instlang['s2_email2'] = 'Hoved E-Post adressen der webstedet skal sende informasjon til';
$instlang['s2_session_path'] = 'Session Lagringsti';
$instlang['s2_session_path2'] = 'Dette er stien til hvor datafiler er lagret.<br />Du må forandre denne variabelen for å bruke Dragonfly\\\'s session funksjoner.<br />Stien må være tilgjengelig for PHP som dette /home/myname/tmp/sessiondata og du antagelighvis sette CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Informasjonskapsel Domene';
$instlang['s2_cookie_domain2'] = 'Fullt domenenavn eller topp-level domene for å lagre informasjonskapslene, for eksempel <i>mittdomene.no</i> eller la det stå tomt';
$instlang['s2_cookie_path'] = 'Sti for Informasjonskapsel';
$instlang['s2_cookie_path2'] = 'Web-adresse stien til informasjonskapselen, for eksempel <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Informasjonskapselnavn for Administrator';
$instlang['s2_cookie_admin2'] = 'Navnet på informasjonskapselen for å lagre innloggings-informasjon for administrator';
$instlang['s2_cookie_member'] ='Informasjonskapselnavn for medlemmer';
$instlang['s2_cookie_member2'] = 'Navnet på informasjonskapselen for å lagre innloggings-informasjon for medlemmer';

$instlang['s2_error_email'] = 'Ugyldig E-Post adresse';
$instlang['s2_error_empty'] = 'Noen felt er tomme';
$instlang['s2_error_cookiename'] = 'Ugyldig informasjonskapselnavn';
$instlang['s2_error_cookiesettings'] = 'Ugyldige innstillinger for informasjonskapsel ';
$instlang['s2_error_sessionsettings'] = 'Feil innstillinger for informasjonskapsel';

$instlang['s2_cookietest'] = 'Vi vil teste innstillingene du har spesifisert før vi fortsetter.';
$instlang['s2_test_settings'] = 'Test Innstillinger';

$instlang['s3_sync_schema'] = 'Synchronizing Database Schema';
$instlang['s3_sync_data']   = 'Synchronizing Database Data';
$instlang['s3_sync_done']   = 'Synchronization done';
$instlang['s3_exec_queries'] = 'Executing additional queries';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'Navnet du bruker for å logge deg inn som administrator';
$instlang['s3_email2'] = 'Din E-Post adresse';
$instlang['s3_pass2'] = 'Passordet du bruker for å logge deg inn. Du kan bruke vilket som helst tegn';
$instlang['s3_timezone'] = 'Tidssone';
$instlang['s3_timezone2'] = 'Vilken tidssone du vil bruke på posterte meldinger';

$instlang['s3_warning'] = 'Sørg for at du minst har: 1 med stor bokstav, 1 med liten bokstav og ett nummer i ditt passord.';
$instlang['s3_finnish'] = '<h2>Installeringen av Dragonfly '.CPG_NUKE.' er nå ferdig.<br />Slett install mappen nå!<br />Så kan du ha det gøy!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Klikk HER for å logge inn som admin og forandre oppsettet.</a>';
