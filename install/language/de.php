<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2014 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Installation';
$instlang['s_progress'] = 'Installationsablauf';
$instlang['s_license'] = 'Lizenzbedingungen zustimmen';
$instlang['s_server'] = 'Server überprüfen';
$instlang['s_setconfig'] = 'Konfigurationsdatei erstellen <br />(config.php)';
$instlang['s_builddb'] = 'Datenbank anlegen';
$instlang['s_gather'] = 'Einstellungsdaten';
$instlang['s_create'] = 'Super Admin Account erstellen';
$instlang['welcome'] = 'Wilkommen zu Dragonfly!';
$instlang['info'] = 'Installationsprogramm wird Sie durch die Einrichtung von Dragonfly '.CPG_NUKE.' führen. (wenige Minuten)<br />Es wird die Datenbank erstellen oder bereits vorhandene Datenbankdaten (CPG oder PHP-Nuke) upgraden';
$instlang['click'] = 'Klicken Sie auf "Zustimmen" wenn Sie diese Bedingungen akzeptieren:';
$instlang['no_zlib'] = 'Ihr Server Unterstützt keine Zlib Compression. Deswegen können die Lizensbestimmungen nicht angezeigt werden. Bitte manuell die GPL.txt Datei in CPG-Nuke Ordner öffnen und durchlesen und auf "Zustimmen" klicken';
$instlang['agree'] = 'Zustimmen';
$instlang['next'] = 'Weiter';

$instlang['s1_already'] = 'Eine vorherige Version '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b> ist bereits installiert.';
$instlang['s1_new'] = 'Installer konnte keine vorherige Version zum upgraden finden, es wird eine neue Version erstellt.';
$instlang['s1_upgrade'] = 'Ihre jetzige Version ist <b>%s</b>, und wird nun upgraded/konvertiert nach Dragonfly '.CPG_NUKE.'<br /><b>Erstellen Sie unbedigt vorher ein Backup Ihrer jetzigen Daten</b>';
$instlang['s1_unknown'] = 'Installer konnte die Version bereits vorhandener CPG-Nuke/PHP-Nuke nicht erkennen.<br />Installation kann nicht fortgesetzt werden.<br />Bitte kontaktieren Sie das CPG Dev Team';
$instlang['s1_database'] = 'Zusammenfassung der Setupdaten in config.php für Datenbankverbindung';

$instlang['s1_dbconfig'] = 'Datenbank Konfiguration';
$instlang['s1_server2'] = 'SQL-Version ihres Servers: %s';
$instlang['s1_layer'] = 'SQL Layer';
$instlang['s1_layer2'] = 'Auswahl welche SQL layer verwendet wird';
$instlang['s1_host'] = 'Hostname';
$instlang['s1_host2'] = 'Domain oder IP Ihres SQL-Servers';
$instlang['s1_username'] = 'Benutzername';
$instlang['s1_username2'] = 'SQL Server Benutzername';
$instlang['s1_password'] = 'Passwort';
$instlang['s1_password2'] = 'SQL Server Passwort';
$instlang['s1_dbname'] = 'Datenbankname';
$instlang['s1_dbname2'] = 'Datenbankbezeichnung wo die Tabellen erstellt werden';
$instlang['s1_prefix'] = 'Tabellen Präfix';
$instlang['s1_prefix2'] = 'Standard Präfix für Tabellennamen';
$instlang['s1_directory_write'] = 'Verzeichniss schreibrechte';
$instlang['s1_directory_write2'] = 'Verzeichnisse die Schreibrechte benötigen. Wie z.B. Ordner um Bilder hochzuladen.<br />Um dies zu erreichen Ordnerrechte ändern -> "CHMOD 777" ';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Fehler - aber nicht Kritisch';
$instlang['s1_dot_critical'] = 'Kritisch';

$instlang['s1_server_settings'] = 'Server Einstellungen';
$instlang['s1_setting'] = 'Einstellung';
$instlang['s1_preferred'] = 'bevorzugt';
$instlang['s1_yours'] = 'Ihre';
$instlang['s1_on'] = 'An';
$instlang['s1_off'] = 'Aus';

$instlang['s1_correct'] = 'Wenn Einstellungen passen - kann jetzt Datenbank erstellt werden';
$instlang['s1_fixerrors'] = 'Bitte gezeigte Fehler zuerst beseitigen';
$instlang['s1_fatalerror'] = 'Bitte Fehler an das CPG-Nuke Dev Team melden<br />Installation kann nicht fortgesetz werden';
$instlang['s1_build_db'] = 'Jetzt Datenbank erstellen';
$instlang['s1_necessary_info'] = 'Weiter Eingabe der benötigten Informationen';
$instlang['s1_donenew'] = 'Datenbank erfolgreich installiert, jetzt weiter mit Eingabe der notwendigen Daten !';
$instlang['s1_doneup'] = 'Datenbank erfolgreich upgedated, Viel Spaß mit Dragonfly!';
$instlang['s1_trying_to_connect'] = 'Verbinde mit SQL Server ...';
$instlang['s1_wrong_database_name'] = 'Bitte eingen Datenbankname verwenden.<br />Sorry, Installation kann nicht fortgesetzt werden mit "<b>public</b>" als Datenbankname.';
$instlang['s1_save_conf_succeed'] = 'Konfigurationdatei erfolgreich angelegt';
$instlang['s1_save_conf_failed'] = 'Konfigurationdatei konnte nicht angelegt werden';
$instlang['s1_db_connection_succeeded'] = 'Datenbank erfolgreich angelegt!';

$instlang['s2_info'] = 'Eingabe der notwendigen Daten:';
$instlang['s2_account'] = 'Notwendige Daten erfolgreich angelegt.Jetzt ein Account einrichten!';
$instlang['s2_create'] = 'Account erstellen';

$instlang['s2_domain'] = 'Domainname';
$instlang['s2_domain2'] = 'Domainname Ihrer Dragonfly powered Webseite, z.B. <i>www.mysite.com</i>';
$instlang['s2_path'] = 'Pfad';
$instlang['s2_path2'] = 'Pfad Ihrer Dragonfly powered Webseite, z.B. <i>/html/</i>';
$instlang['s2_email2'] = 'Email-Address an die Webseiten Meldungen gesendet werden';
$instlang['s2_session_path'] = 'Session Speicher Pfad';
$instlang['s2_session_path2'] = 'Pfad wo Sessiondaten gespeichert werden.<br />Muß angepasst werden um Dragonfly\\\'s Sessionfunktion zu nutzen.<br />Pfad muß von PHP zgreifbar sein - wie z.B. /home/myname/tmp/sessiondata und vielleicht auch CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Cookie Domain';
$instlang['s2_cookie_domain2'] = 'Kompletter Pfad Domain Cookies in, z.B. <i>mysite.com</i> oder leer lassen';
$instlang['s2_cookie_path'] = 'Cookie Pfad';
$instlang['s2_cookie_path2'] = 'Pfad für Cookie - beschränkung, z.B. <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Admin Cookie-Bezeichnung';
$instlang['s2_cookie_admin2'] = 'Cookie-Bezeichnung wo Administratorlogin Information gespeichert werden';
$instlang['s2_cookie_member'] ='Benutzer Cookie-Bezeichnung';
$instlang['s2_cookie_member2'] = 'Cookie-Bezeichnung wo Benutzerlogin Information gespeichert werden';

$instlang['s2_error_email'] = 'Ungültige Email Addresse';
$instlang['s2_error_empty'] = 'Leere Felder ausfüllen';
$instlang['s2_error_cookiename'] = 'Ungültige Cookie-Bezeichnung';
$instlang['s2_error_cookiesettings'] = 'Ungültige Cookie Einstellung';
$instlang['s2_error_sessionsettings'] = 'Ungültige  Session Einstellung';

$instlang['s2_cookietest'] = 'Zuerst werden Ihre Einstellungen getestet.';
$instlang['s2_test_settings'] = 'Teste Einstellungen';

$instlang['s3_sync_schema'] = 'Synchroniziere Datenbank Schemen';
$instlang['s3_sync_data']   = 'Synchroniziere Datenbank Daten';
$instlang['s3_sync_done']   = 'Synchronization erfolgreich abgeschlossen';
$instlang['s3_exec_queries'] = 'Führe zusätzliche Queries aus';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'Loginname des Administrators';
$instlang['s3_email2'] = 'Email-Addresse';
$instlang['s3_pass2'] = 'Passwort des Administrators. Alle Zeichen sind erlaubt';
$instlang['s3_timezone'] = 'Ihre Zeitzone';
$instlang['s3_timezone2'] = 'Verwendete Zeitzone z.B. Zeitangabe für erstellte News und Post';

$instlang['s3_warning'] = 'Passwort muß mindestens: 1 Großbuchstabe, 1 Kleinbuchstabe und 1 Zahl enthalten.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' erfolgreich installiert<br /><b>Wichtig! Ordner install jetzt löschen!</b><br />Jetzt Viel Spass!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Weiter zu Adminbereich Ihrer Webseite</a>';
