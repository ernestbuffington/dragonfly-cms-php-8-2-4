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

$instlang['installer'] = 'Installer';
$instlang['s_progress'] = 'Progresso d\'installazione';
$instlang['s_license'] = 'Licenza';
$instlang['s_server'] = 'Server';
$instlang['s_setconfig'] = 'Configurazione';
$instlang['s_builddb'] = 'Installazione';
$instlang['s_gather'] = 'Raccogli info importanti';
$instlang['s_create'] = 'Crea il super amministratore';
$instlang['welcome'] = 'Benvenuti su Dragonfly!';
$instlang['info'] = 'Questa procedura d\'installazione vi guiderà in meno di un minuto nella messa a punto di Dragonfly nel tuo sito web.<br />L\'installazione costruirà automaticamente il database necessario ed il primo utente oppure aggiornerà la tua attuale installazione.';
$instlang['click'] = 'Clicca su "Confermo" se accetti la seguente licenza:';
$instlang['no_zlib'] = 'Il tuo server non supporta la Compressione Zlib. Per questo motivo non è puoi leggere la nostra licenza in questa pagina. Controlla GPL.txt distribuito con CPG-Nuke e dopo clicca "Confermo" quì in basso';
$instlang['agree'] = 'Confermo';
$instlang['next'] = 'Prosegui';

$instlang['s1_already'] = 'Dragonfly <b>'.CPG_NUKE.'</b> è stato già precedentemente installato.';
$instlang['s1_new'] = 'Non è stata rilevata nessuna versione precedente, quindi ne verrà installata una di nuova';
$instlang['s1_upgrade'] = 'La tua versione attuale è <b>%s</b>, e sarà aggiornata a Dragonfly '.CPG_NUKE.'<br /><b>Assicurati di avere un backup del tuo database.</b>';
$instlang['s1_unknown'] = 'Non è stato possibile determinare quale versione di CPG-Nuke/PHP-Nuke stai usando.<br />Non è possibile continuare l\'installazione.<br />Cortesemente contatta CPG Dev Team';
$instlang['s1_database'] = 'Questo è il resoconto dei dati che configureremo in config.php per la connessione al database';

$instlang['s1_dbconfig'] = 'Configurazione database';
$instlang['s1_server2'] = 'La versione %s attualmente usata nel tuo server';
$instlang['s1_layer'] = 'Database SQL';
$instlang['s1_layer2'] = 'Il database SQL da usare con il tuo sito';
$instlang['s1_host'] = 'Hostname';
$instlang['s1_host2'] = 'Il nome DNS oppure IP del server dove si trova il database SQL';
$instlang['s1_username'] = 'Nome utente';
$instlang['s1_username2'] = 'Il nome utente usato pe la connessione al server SQL';
$instlang['s1_password'] = 'Password';
$instlang['s1_password2'] = 'La password del nome utente per la connessione al server SQL ';
$instlang['s1_dbname'] = 'Nome del database';
$instlang['s1_dbname2'] = 'Il nome del database che contiene le tabelle ed i dati desiderati';
$instlang['s1_prefix'] = 'Prefisso tabelle';
$instlang['s1_prefix2'] = 'Il prefisso da usare per i nomi delle tabelle';
$instlang['s1_directory_write'] = 'Permessi di scrittura cartelle';
$instlang['s1_directory_write2'] = 'Cartelle che hanno bisogno del permesso di scrittura dove salvare informazioni come le immagini che verranno caricate.<br />Se una fallisce allora "CHMOD 777" la cartella';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Fallito ma non critico';
$instlang['s1_dot_critical'] = 'Critico';

$instlang['s1_server_settings'] = 'Configurazione server';
$instlang['s1_setting'] = 'Tipo';
$instlang['s1_preferred'] = 'Preferito';
$instlang['s1_yours'] = 'Attuale';
$instlang['s1_on'] = 'On';
$instlang['s1_off'] = 'Off';

$instlang['s1_correct'] = 'Se le informazioni sono corrette possiamo cominciare a costruire il database';
$instlang['s1_fixerrors'] = 'Cortesemente ripara gli errori qui sotto elencati';
$instlang['s1_fatalerror'] = 'Per favore contatta Dragonfly CMS Dev. Team descrivendo il tuo errore<br />Non puoi continuare l\'installazione';
$instlang['s1_build_db'] = 'Cominciamo a costruire il database';
$instlang['s1_necessary_info'] = 'Informazioni Necessarie';
$instlang['s1_donenew'] = 'Il database è stato installato correttamente, cominciamo con il configurare alcune informazioni necessarie!';
$instlang['s1_doneup'] = 'Il database è stato aggiornato con successo, divertiti con l\'incredibile Dragonfly!<br /><h2>Rimuovi install.php e la cartella install in questo preciso momento!</h2>';
$instlang['s1_trying_to_connect'] = 'Connessione al server SQL ...';
$instlang['s1_wrong_database_name'] = 'You need to choose a different database name.<br />Sorry for the inconvenience but you cannot continue with the installation with "<b>public</b>" as database name.';
$instlang['s1_save_conf_succeed'] = 'riuscita e file di configurazione salvato';
$instlang['s1_save_conf_failed'] = 'fallita';
$instlang['s1_db_connection_succeeded'] = 'riuscita!';

$instlang['s2_info'] = 'Cominciamo con il configurare le informazioni necessarie:';
$instlang['s2_account'] = 'Informazioni necessarie inserite. Continuiamo con il configurare il primo utente!';
$instlang['s2_create'] = 'Crea Utente';

$instlang['s2_domain'] = 'Nome Dominio';
$instlang['s2_domain2'] = 'Il Nome Dominio è praticamente dove risiede il tuo sito animato da Dragonfly, per esempio <i>www.forzamilan.it</i>';
$instlang['s2_path'] = 'Percorso';
$instlang['s2_path2'] = 'Esatto percorso dove risiede Dragonfly, per esempio <i>/html/</i>';
$instlang['s2_email2'] = 'Email principale usata dal sito web per ricevere informazioni';
$instlang['s2_session_path'] = 'Percorso Salvataggio Sessione';
$instlang['s2_session_path2'] = 'Questo è il percorso dove verranno salvati i dati.<br />Devi cambiare questo valore per usare la funzione di Dragonfly relativa alle sessioni.<br />Il percorso deve essere accessibile da PHP come /home/forzalazio/tmp/sessiondata e sicuramente CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Dominio Cookie';
$instlang['s2_cookie_domain2'] = 'Il dominio intero, o top-level, usato dai cookie, per esempio <i>forzanapoli.it</i> o lascialo vuoto';
$instlang['s2_cookie_path'] = 'Percorso Cookie';
$instlang['s2_cookie_path2'] = 'Indirizzo web dove restringere i cookie, per esempio <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Nome Cookie Admin';
$instlang['s2_cookie_admin2'] = 'Il nome del cookie dove verranno salvate informazioni relative al login degli amministratori di questo sito';
$instlang['s2_cookie_member'] ='Nome Cookie Utente';
$instlang['s2_cookie_member2'] = 'Il nome del cookie dove verranno salvate informazioni relative al login degli utenti di questo sito';

$instlang['s2_error_email'] = 'Incorretto indirizzo email';
$instlang['s2_error_empty'] = 'Alcuni campi sono stati lasciati in bianco';
$instlang['s2_error_cookiename'] = 'Nome cookie incorretto';
$instlang['s2_error_cookiesettings'] = 'Parametri cookie incorretti';
$instlang['s2_error_sessionsettings'] = 'Parametri sessione errati';

$instlang['s2_cookietest'] = 'Prima di procedere tenteremo ad analizzare i settaggi da te specificati.';
$instlang['s2_test_settings'] = 'Analisi Settaggi';

$instlang['s3_sync_schema'] = 'Sincronizzando database schema';
$instlang['s3_sync_data']   = 'Sincronizzando database data';
$instlang['s3_sync_done']   = 'Sincronizzazione effettuata';
$instlang['s3_exec_queries'] = 'Eseguendo aggiuntive queries';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'Il nome che userai per entrare in questo sito come amministratore';
$instlang['s3_email2'] = 'Il tuo indirizzo email';
$instlang['s3_pass2'] = 'La password che userai per entrare in questo sito. Potrai usare qualsiasi carattere';
$instlang['s3_timezone'] = 'Fuso Orario';
$instlang['s3_timezone2'] = 'Il fuso orario con il quale vedrai i messaggi inseriti';

$instlang['s3_warning'] = 'Assicurati che nella tua password siano compresi almeno: 1 lettera maiuscola, 1 lettera minuscola ed 1 numero.';
$instlang['s3_finnish'] = '<h2>Installazione di Dragonfly '.CPG_NUKE.' nel tuo webserver completata.<br />Rimuovi la cartella install in questo preciso momento!<br />Solo dopo potrai essere sicuro di avere quintali di divertimento!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Entra nel sito per le rimanenti configurazioni</a>';
