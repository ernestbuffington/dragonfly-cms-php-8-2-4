<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Translation by Pitcher

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/language/norsk.php,v $
  $Revision: 9.20 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 14:20:58 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Installer';
$instlang['s_progress'] = 'Installerings-progress';
$instlang['s_license'] = 'Lisens';
$instlang['s_builddb'] = 'Bygge database';
$instlang['s_gather'] = 'Innhente viktig informasjon';
$instlang['s_create'] = 'Lage super administrator konto';
$instlang['welcome'] = 'Velkommen til Dragonfly!';
$instlang['info'] = ' Du vil med hjelp av denne installeringen, ha installert Dragonfly versjon '.CPG_NUKE.' på ditt nettsted i løpet av få minutter.<br />Installasjonen vil bygge opp den nødvendige databasen og første brukerkontoen eller oppgradere din allerede eksisterende CPG eller PHP-Nuke.';
$instlang['click'] = 'Klikk "Jeg Godkjenner" hvis du aksepterer følgende lisens:';
//$instlang['license'] = 'Modifikasjoner og fikser laget av CPG Dev Team kan ikke bli brukt i annen nuke versjon/plan eller websted som krever betaling, registrering eller kompensasjon for installering, support eller nedlasting av GPL lisensiert programvare uten godkjenning av CPG Dev Team.<br /><strong>Med dette menes, at du ikke kan selge vår kode som en del av et kommersiellt produkt.</strong>';
$instlang['license_edited'] = 'Din lisens har blitt editert. Vennligst kontakt Utvikler teamet på dragonflycms.com med en gang.';
$instlang['no_zlib'] = 'Din server støtter ikke Zlib Kompresjon. Derfor kan du ikke lese vår lisens fra denne siden. Vennligst se GPL.txt som finnes i din CPG-Nuke distribusjon og klikk "Jeg Godkjenner" nedenfor';
$instlang['agree'] = 'Jeg Godkjenner';
$instlang['next'] = 'Neste';

$instlang['s1_good'] = 'Godt du tok beslutningen å bruke Dragonfly '.CPG_NUKE;
$instlang['s1_already'] = 'Du har allerede '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <strong>'.CPG_NUKE.'</strong> installert.';
$instlang['s1_splatt'] = '<strong>Advarsel</strong> Den gamle Splatt forum databasen vil bli slettet! Hvis du fremdeles vil prøve å bruke den, så behold tabellene.<br />Beholde Splatt Forum databasen? <select name="splatt" class="formfield"><option value="0">Nei</option><option value="1">Ja</option></select>';
$instlang['s1_new'] = 'Installereren kunne ikke finne noen eldre versjoner, så den vil installere en ny versjon til deg.';
$instlang['s1_upgrade'] = 'Din nåværende versjon er <strong>%s</strong>, og den vil bli oppgradert til Dragonfly '.CPG_NUKE.'<br /><strong>Vær sikker på at du har tatt en sikkerhetskopi av din database.</strong>';
$instlang['s1_unknown'] = 'Installereren kunne ikke finne ut vilken versjon av CPG-Nuke/PHP-Nuke du bruker.<br />Du kan ikke fortsette installasjonen.<br />Vennligst kontakt CPG Dev Team';
$instlang['s1_database'] = 'Dette er en oversikt over hva du har satt i config.php for database oppkoblingen';

$instlang['s1_dbconfig'] = 'Databasekonfigurasjon';
$instlang['s1_server'] = 'Serverversjon';
$instlang['s1_server2'] = 'Versjonen av %s som er aktiv på din server';
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
$instlang['s1_userprefix'] = 'prefiks for bruker tabell';
$instlang['s1_userprefix2'] = 'En standard prefiks for tabellen som inneholder all brukerdata';
$instlang['s1_directory_write'] = 'Folder skrive tilgang';
$instlang['s1_directory_write2'] = 'Foldere som trenger skrivetilgang for å lagre innformasjon som opplastetede bilder.<br />Hvis noen feiler så bruk "CHMOD 777" på mappen';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Feilet, men ikke kritisk';
$instlang['s1_dot_critical'] = 'Kritisk feil';

$instlang['s1_cache'] = 'Mellomlager';
$instlang['s1_cache2'] = 'Lagrer midlertidig innstillinger og template filer for hurtigere side generering';
$instlang['s1_avatars'] = 'Avatar';
$instlang['s1_avatars2'] = 'Når medlemer har tilgang til å laste opp egen avatar, vil denne mappen inneholde dems opplastede avatar';
$instlang['s1_albums'] = 'Albumer';
$instlang['s1_albums2'] = 'Inneholder alle bilder fra fotogalleriet som er lastet opp via FTP eller andre metoder';
$instlang['s1_userpics'] = 'Brukerbilde';
$instlang['s1_userpics2'] = 'Inneholder under-direktorier for hvert medlem og lagrer opplastede medlemsbilder der';
$instlang['s1_config'] = 'Inkluderer';
$instlang['s1_config2'] = 'Lagrer filer som er nødvendig for å kjøre DF';

$instlang['s1_correct'] = 'Hvis informasjonen er korrekt så start oppbyggingen av databasen.';
$instlang['s1_fixerrors'] = 'Vennligst fiks feilene over først';
$instlang['s1_fatalerror'] = 'Vennligst kontakt CPG-Nuke Dev Team om denne feilen<br />Du kan ikke fortsette med innstallasjonen';
$instlang['s1_build_db'] = 'La oss bygge opp databasen';
$instlang['s1_necessary_info'] = 'Nødvendig Informasjon';
$instlang['s1_php'] = '<p style="color:#FF0000; font-style:bold">Vi kan ikke garantere at Dragonfly vil kjøre 100% med din gamle PHP versjon<br />Spør din server administrator om å oppgradere til PHP 4.3.10 eller 5.0.3 eller nyere</p>';
$instlang['s1_mysql'] = '<p style="color: #FF0000; font-style: bold;">Vi beklager, men MySQL 4 eller nyere er påkrevet<br />Spør din server administrator om å oppgradere til MySQL 4 eller nyere</p>Din nåværende versjon er: %s';
$instlang['s1_donenew'] = 'Databasen er installert, la oss nå sette opp noe nødvendig informasjon!';
$instlang['s1_optimiz'] = 'Database optimization. The execution of this step might take a while for a big database.';
$instlang['s1_doneup'] = 'Databasen ble velykket oppdatert, ha det gøy med din nye Dragonfly!<br /><h2>Slett install.php og install mappen nå!</h2>';

$instlang['s2_info'] = 'La oss sette opp nødvendig informasjon:';
$instlang['s2_error'] = 'All informasjon må skrives inn.';
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
$instlang['s2_cookie_cpg'] = 'Informasjonkapselnavn for fotogalleriet';
$instlang['s2_cookie_cpg2'] = 'Navnet på informasjonskapselen til fotogalleriet for å lagre spesifik informasjon for fotogalleriet';

$instlang['s2_error_email'] = 'Ugyldig E-Post adresse';
$instlang['s2_error_empty'] = 'Noen felt er tomme';
$instlang['s2_error_cookiename'] = 'Ugyldig informasjonskapselnavn';
$instlang['s2_error_cookiesettings'] = 'Ugyldige innstillinger for informasjonskapsel ';
$instlang['s2_error_sessionsettings'] = 'Feil innstillinger for informasjonskapsel';

$instlang['s2_cookietest'] = 'Vi vil teste innstillingene du har spesifisert før vi fortsetter.';
$instlang['s2_test_settings'] = 'Test Innstillinger';

$instlang['s3_nick2'] = 'Navnet du bruker for å logge deg inn som administrator';
$instlang['s3_email2'] = 'Din E-Post adresse';
$instlang['s3_pass2'] = 'Passordet du bruker for å logge deg inn. Du kan bruke vilket som helst tegn';
$instlang['s3_timezone'] = 'Tidssone';
$instlang['s3_timezone2'] = 'Vilken tidssone du vil bruke på posterte meldinger';

$instlang['s3_warning'] = 'Sørg for at du minst har: 1 med stor bokstav, 1 med liten bokstav og ett nummer i ditt passord.';
$instlang['s3_finnish'] = '<h2>Installeringen av Dragonfly '.CPG_NUKE.' er nå ferdig.<br />Slett install.php og install mappen nå!<br />Så kan du ha det gøy!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Klikk HER for å logge inn som admin og forandre oppsettet.</a>';