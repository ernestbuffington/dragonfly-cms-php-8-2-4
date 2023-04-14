<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/install/language/polish.php,v $
  $Revision: 1.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 14:20:58 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$instlang['installer'] = 'Instalator';
$instlang['s_progress'] = 'Postęp w instalacji';
$instlang['s_license'] = 'Licencja';
$instlang['s_builddb'] = 'Buduj bazę danych';
$instlang['s_gather'] = 'Zbierz ważne informacje';
$instlang['s_create'] = 'Utwórz konto superadministratora';
$instlang['welcome'] = 'Witaj w Dragonfly!';
$instlang['info'] = 'Teraz w ciągu kilku minut na Twojej witrynie zostanie przeprowadzona instalacja Dragonfly '.CPG_NUKE.'.<br />Instalator utworzy bazę danych i pierwszego użytkownika lub uaktualni istniejącą instalację CPG lub PHP-Nuke.';
$instlang['click'] = 'Kliknij "Zgadzam się", jeśli przyjmujesz warunki niniejszej licencji:';
//$instlang['license'] = 'The modifications and fixes made by the CPG-Nuke Dev Team may not be used in any Nuke version/plan or website that requires payment, registration or compensation for installation, support or download of GPL licensed software without compensation agreed to by the CPG-Nuke Dev Team who undertook this considerable, consequential, and distinguished rewriting.<br /><b>That means you can\'t sell our code as part of any commercial version.</b>';
$instlang['license_edited'] = 'Twoja licencja została zmieniona. Skontaktuj się natychmiast z Development Team na dragonflycms.com. Dziękujemy.';
$instlang['no_zlib'] = 'Twój serwer nie obsługuje kompresji Zlib i dlatego nie możesz przeczytać licencji na tej stronie. Zapoznaj się więc z treścią licencji w pliku GPL.txt znajdującym się w tej dystrybucji CPG-Nuke i poniżej kliknij "Zgadzam się"';
$instlang['agree'] = 'Zgadzam się';

$instlang['s1_good'] = 'Cieszymy się, że wybrałeś Dragonfly '.CPG_NUKE;
$instlang['s1_already'] = 'Już masz zainstalowany '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b>.';
$instlang['s1_splatt'] = '<b>Uwaga</b> Baza danych starego forum Splatt zostanie usunięta! Jeśli nadal chcesz jej używać, zachowaj tabele.<br />Zachować bazę danych forum Splatt? <select name="splatt" class="formfield"><option value="0">Nie</option><option value="1">Tak</option></select>';
$instlang['s1_new'] = 'Instalator nie znalazł wcześniejszej wersji, więc zainstaluje nową wersję';
$instlang['s1_upgrade'] = 'Twoja obecnie istniejąca wersja to <b>%s</b> i zostanie zaktualizowana/przekształcona do Dragonfly '.CPG_NUKE.'<br /><b>Upewnij się, że masz kopię zapasową swojej bazy danych.</b>';
$instlang['s1_unknown'] = 'Instalator nie mógł rozpoznać używanej przez Ciebie wersji CPG-Nuke/PHP-Nuke.<br />Nie można kontynuowac instalacji.<br />Skontaktuj się z CPG Dev Team';
$instlang['s1_database'] = 'Oto podsumowanie ustawień zapisanych w pliku config.php dotyczących połączenia z bazą danych';

$instlang['s1_dbconfig'] = 'Konfiguracja bazy danych';
$instlang['s1_server'] = 'Wersja serwera';
$instlang['s1_server2'] = 'Wersja %s na Twoim serwerze';
$instlang['s1_host'] = 'Nazwa hosta';
$instlang['s1_host2'] = 'Nazwa DNS lub IP serwera, na którym działa MySQL';
$instlang['s1_dbname'] = 'Nazwa bazy danych';
$instlang['s1_dbname2'] = 'Nazwa bazy danych, która zawiera wymagane tabele z danymi';
$instlang['s1_prefix'] = 'Przedrostek tabel';
$instlang['s1_prefix2'] = 'Domyślny przedrostek nazw tabel';
$instlang['s1_userprefix'] = 'Przedrostek tabel użytkowników';
$instlang['s1_userprefix2'] = 'Domyślny przedrostek nazw tabel zawierających dane wszystkich użytkowników';
$instlang['s1_directory_write'] = 'Katalog z uprawnieniami zapisu';
$instlang['s1_directory_write2'] = 'Katalogi z nadanymi prawami zapisu do przechowywania danych, np. przesyłanych obrazków.<br />Może być wymagane ustawienie odpowiednich uprawnień komendą "CHMOD 777"';
$instlang['s1_dot_ok'] = 'Akceptuj';
$instlang['s1_dot_failed'] = 'Nieudane, ale niekrytyczne';
$instlang['s1_dot_critical'] = 'Krytyczne';

$instlang['s1_cache'] = 'Pamięć podręczna';
$instlang['s1_cache2'] = 'Przechowuje zapamiętane ustawienia i pliki szablonów dla szybszego wyświetlania stron';
$instlang['s1_avatars'] = 'Emblematy';
$instlang['s1_avatars2'] = 'Jeśli zezwolisz użytkownikom na używanie własnych emblematów, ten katalog będzie służył do ich przechowywania';
$instlang['s1_albums'] = 'Albumy';
$instlang['s1_albums2'] = 'Przechowuje wszystkie zdjęcia z Fotogalerii przesyłane przez FTP lub inaczej';
$instlang['s1_userpics'] = 'Zdjęcia użytkowników';
$instlang['s1_userpics2'] = 'Zawiera podfoldery każdego ID użytkownika i przechowuje w nich obrazy przesyłane przez użytkowników';
$instlang['s1_config'] = 'Includes';
$instlang['s1_config2'] = 'Stores core files needed to run the CMS';

$instlang['s1_correct'] = 'Jeśli powyższe informacje są prawidłowe, można rozpocząć budowanie bazy danych';
$instlang['s1_fixerrors'] = 'Najpierw usuń wymienione wyżej problemy';
$instlang['s1_fatalerror'] = 'Skontaktuj się z CPG-Nuke Dev Team w sprawie tych błędów<br />Instalacja nie może być kontynuowana';
$instlang['s1_build_db'] = 'Zbudujmy bazę danych';
$instlang['s1_necessary_info'] = 'Niezbędne informacje';
$instlang['s1_php'] = '<p style="color:#FF0000; font-style:bold">Nie gwarantujemy, że Dragonfly będzie prawidłowo funkcjonował na Twojej starej wersji PHP<br />Zapytaj administratora o możliwość aktualizacji do PHP w wersji 4.3.10, 5.0.3 lub nowszej</p>';
$instlang['s1_mysql'] = '<p style="color: #FF0000; font-style: bold;">Przepraszamy, obsługiwana jest tylko MySQL wersja 4 lub nowsza<br />Zapytaj administratora o możliwość aktualizacji MySQL do wersji 4 lub nowszej</p>Twoja bieżąca wersja to: %s';
$instlang['s1_donenew'] = 'Baza danych została poprawnie zbudowana, teraz konieczne będzie ustalenie niezbędnych informacji!';
$instlang['s1_optimiz'] = 'Database optimization. The execution of this step might take a while for a big database.';
$instlang['s1_doneup'] = 'Baza danych została poprawnie uaktualniona, miłej pracy lub zabawy z niesamowitym Dragonfly!<br /><h2>Usuń teraz plik install.php i katalog instalacyjny install!</h2>';

$instlang['s2_info'] = 'Ustalmy niebędne dane:';
$instlang['s2_error'] = 'Muszą być podane wszystkie dane.';
$instlang['s2_account'] = 'Niezbędne dane zostały podane. Załóżmy pierwsze konto!';
$instlang['s2_create'] = 'Utwórz konto';

$instlang['s2_domain'] = 'Nazwa domeny';
$instlang['s2_domain2'] = 'Nazwa domeny, gdzie będzie działać witryna obsługiwana przez Dragonfly, na przykład <i>www.mojawitryna.com.pl</i>';
$instlang['s2_path'] = 'Ścieżka';
$instlang['s2_path2'] = 'Ścieżka do katalogu, w którym znajduje się witryna obsługiwana przez Dragonfly, na przykład <i>/html/</i>';
$instlang['s2_email2'] = 'Główny adres email, gdzie powinny być wysyłane informacje związane z witryną';
$instlang['s2_session_path'] = 'Ścieżka do miejsca, w którym będą zapisywane sesje';
$instlang['s2_session_path2'] = 'To jest ścieżka do miejsca, w którym są przechowywane pliki danych.<br />Musisz zmienić tę zmienną, aby móc używać funkcji sesyjnych Dragonfly.<br />Ścieżka musi być dostępna dla PHP, np. /home/myname/tmp/sessiondata i prawdopodobnie z CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Domena cookie';
$instlang['s2_cookie_domain2'] = 'Nazwa pełna domeny lub domeny najwyższego stopnia do przechowywania cookie, np. <i>mojawitryna.com.pl</i> lub pozostaw puste';
$instlang['s2_cookie_path'] = 'Ścieżka cookie';
$instlang['s2_cookie_path2'] = 'Adres, do którego cookie ma być ograniczone, np. <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Nazwa cookie administratora';
$instlang['s2_cookie_admin2'] = 'Nazwa cookie do przechowywania danych logowania administratora na tej witrynie';
$instlang['s2_cookie_member'] ='Nazwa cookie użytkownika';
$instlang['s2_cookie_member2'] = 'Nazwa cookie do przechowywania danych logowania użytkownika na tej witrynie';
$instlang['s2_cookie_cpg'] = 'Nazwa cookie fotogalerii';
$instlang['s2_cookie_cpg2'] = 'Nazwa cookie do przechowywania informacji specyficznych dla fotogalerii tej witryny';

$instlang['s2_error_email'] = 'Nieprawidłowy adres email';
$instlang['s2_error_empty'] = 'Niektóre pola pozostały puste';
$instlang['s2_error_cookiename'] = 'Nieprawidłowa nazwa cookie';
$instlang['s2_error_cookiesettings'] = 'Nieprawidłowe ustawienia cookie';
$instlang['s2_error_sessionsettings'] = 'Nieprawidłowe ustawienia sesji';

$instlang['s2_cookietest'] = 'Zanim przejdziemy do następnego etapu, sprawdzimy ustawienia cookie, które podałeś.';
$instlang['s2_test_settings'] = 'Sprawdzanie ustawień';

$instlang['s3_nick2'] = 'Nazwa, której będziesz używać do logowania się na tej witrynie jako administrator';
$instlang['s3_email2'] = 'Twój adres email';
$instlang['s3_pass2'] = 'Hasło, którego będziesz używać do logowania się na tej witrynie. Możesz używać dowolnych znaków';
$instlang['s3_timezone'] = 'Strefa czasowa';
$instlang['s3_timezone2'] = 'Strefa czasowa, wg której chcesz mieć rejestrowany czas zamieszczanych wiadomości';

$instlang['s3_warning'] = 'Upewnij się, że w swoim haśle używasz przynajmniej: 1 wielkiej litery, 1 małej litery oraz 1 cyfry.';
$instlang['s3_finnish'] = '<h2>Dragonfly '.CPG_NUKE.' został pomyślnie zainstalowany.<br />Usuń teraz plik install.php i katalog instalacyjny  install!<br />A teraz miłej zabawy lub pracy!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Wejdź na witrynę, aby dokonać wszelkich ustawień</a>';