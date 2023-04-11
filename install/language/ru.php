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

$instlang['installer'] = 'Мастер Установки';
$instlang['s_progress'] = 'Процедура установки';
$instlang['s_license'] = 'Лицензия';
$instlang['s_server'] = 'Check server';
$instlang['s_setconfig'] = 'Set config.php';
$instlang['s_builddb'] = 'Создание базы данных';
$instlang['s_gather'] = 'Сбор важной информации';
$instlang['s_create'] = 'Создать администраторский аккаунт';
$instlang['welcome'] = 'Добро пожаловать в DragonFly!';
$instlang['info'] = 'Установка поможет вам установить DragonFly '.CPG_NUKE.' на ваш хостинг в течении нескольких минут.<br />Мастер установки создаст базу данных и первого пользователя или обновит уже существующую версию CPG-Nuke или PHP-Nuke.';
$instlang['click'] = 'Нажмите "Я согласен" если вы принимаете данные условия:';
$instlang['no_zlib'] = 'Ваш сервер не поддерживает Zlib компрессию. Так же вы можете прочитать нашу лицензию. Пожалуйста прочтите GPL.txt находящийся на вашем дистрибутиве DragonFLy, если вы согласны то нажмите "Я согласен"';
$instlang['agree'] = 'Я согласен';
$instlang['next'] = 'Далее';

$instlang['s1_already'] = 'Вы уже имеете установленную версию DragonFly <b>'.CPG_NUKE.'</b>.';
$instlang['s1_new'] = 'Мастер установки не смог найти предыдущую версию, в этом случае будет установленна новая версия DragonFly';
$instlang['s1_upgrade'] = 'Ваша текущая версия <b>%s</b>, и она будет обновленна или конвертированна в DragonFly '.CPG_NUKE.'<br /><b>Убедитесь что вы сделали backup вашей базы данных.</b>';
$instlang['s1_unknown'] = 'Мастер установки не смог определить какую версию CPG-Nuke/PHP-Nuke вы используете.<br />Вы не сможете продолжить установку.<br />Пожалуйста обратитесь к группе разработки DragonFly Development Team at dragonflycms.ru';
$instlang['s1_database'] = 'Здесь вы видите то что вы изменили в файле config.php для подключения к базе данных';

$instlang['s1_dbconfig'] = 'Конфигурация базы данных';
$instlang['s1_server2'] = 'Версия %s которая установленна на вашем сервере';
$instlang['s1_layer'] = 'Версия сервера';
$instlang['s1_layer2'] = 'Версия MySQL которая установленна на вашем сервере';
$instlang['s1_host'] = 'Имя хостинга';
$instlang['s1_host2'] = 'DNS имя или IP адрес вашего сервера где установлен MySQL база данных';
$instlang['s1_username'] = 'Имя пользователя';
$instlang['s1_username2'] = 'Имя пользователя используемое для подключения к базе данных';
$instlang['s1_password'] = 'Пароль';
$instlang['s1_password2'] = 'Пароль пользователя для подключения к базе данных';
$instlang['s1_dbname'] = 'Имя базы данных';
$instlang['s1_dbname2'] = 'Название базы данных которая содержит ваши данные';
$instlang['s1_prefix'] = 'Приставка таблицы';
$instlang['s1_prefix2'] = 'Приставка для названия таблицы';
$instlang['s1_directory_write'] = 'Доступ к дериктории для записей';
$instlang['s1_directory_write2'] = 'Директории которые нуждаются в доступе для записи необходимой информации, закачка аватарs.<br />Если какая-то директория выдаёт ошибку смените права доступа на "CHMOD 777" для этой директории';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Ошибка но не критическая';
$instlang['s1_dot_critical'] = 'Критическая ошибка';

$instlang['s1_server_settings'] = 'Server settings';
$instlang['s1_setting'] = 'setting';
$instlang['s1_preferred'] = 'preferred';
$instlang['s1_yours'] = 'yours';
$instlang['s1_on'] = 'On';
$instlang['s1_off'] = 'Off';

$instlang['s1_correct'] = 'Если информация в верху правильная, то вы можете приступить к созданию и внесения структуры баззы данных';
$instlang['s1_fixerrors'] = 'Пожалуйста исправьте ошибки появившиесы сверху, в первую очередь.';
$instlang['s1_fatalerror'] = 'Пожалуйста свяжитесь с DragonFly Development Team на dragonflycms.ru для обсуждения этой ошибки<br />Вы также можете продолжить установку';
$instlang['s1_build_db'] = 'Необходимо создать базу данных';
$instlang['s1_necessary_info'] = 'Необходимая информация';
$instlang['s1_donenew'] = 'База данных была установленна должным образом, сейчас внесите необходимую информацию!';
$instlang['s1_doneup'] = 'База данных была должным оброзом обновленна, надеемся вы получите удовольствие от DragonFly!<br /><h2>Удалите install.php файл и установочную директорию прямо сейчас!</h2>';
$instlang['s1_trying_to_connect'] = 'Trying to connect to SQL server';
$instlang['s1_wrong_database_name'] = 'You need to choose a different database name.<br />Sorry for the inconvenience but you cannot continue with the installation with "<b>public</b>" as database name.';
$instlang['s1_save_conf_succeed'] = 'Saving configuration succeeded';
$instlang['s1_save_conf_failed'] = 'Saving configuration failed';
$instlang['s1_db_connection_succeeded'] = 'Database connection succeeded';

$instlang['s2_info'] = 'Внесите необходимую информацию:';
$instlang['s2_account'] = 'Необходимая информация была добавленна. Создайте свой первый аккаунт!';
$instlang['s2_create'] = 'Создать аккаунт';

$instlang['s2_domain'] = 'Название домена';
$instlang['s2_domain2'] = 'Название домена где ваша версия Dragonfly будет работать, для примера<i>www.mysite.ru</i>';
$instlang['s2_path'] = 'Путь';
$instlang['s2_path2'] = 'Путь по которому ваш портал хостуется, для примера <i>/html/</i>';
$instlang['s2_email2'] = 'Основной е-маил адрес вашего портала';
$instlang['s2_session_path'] = 'Путь к файлам';
$instlang['s2_session_path2'] = 'Это путь где ваши файлы сохраненны.<br />Вы должны изменить это для использования функций Dragonfly.<br />Этот путь должен быть доступен для записи, для примера /home/myname/tmp/sessiondata и также иметь прова доступа CHMOD 777.';
$instlang['s2_cookie_domain'] = 'Cookie  домена';
$instlang['s2_cookie_domain2'] = 'Полное имя доменя для сохранения cookie <i>mysite.com</i> вы можете оставить пустым';
$instlang['s2_cookie_path'] = 'Cookie путь';
$instlang['s2_cookie_path2'] = 'ВЭБ адрес для ограничения cookie, для примера <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'Название cookie файла для администратора';
$instlang['s2_cookie_admin2'] = 'Название cookie для сохранение информации администратора для этого сайта';
$instlang['s2_cookie_member'] ='Название cookie для пользователя';
$instlang['s2_cookie_member2'] = 'Название cookie для сохранения информации пользователя для этого сайта';

$instlang['s2_error_email'] = 'Неправильный е-маил адрес';
$instlang['s2_error_empty'] = 'Некоторые поля были не заполненны';
$instlang['s2_error_cookiename'] = 'Неправильное название cookie';
$instlang['s2_error_cookiesettings'] = 'Неправильные настройки для cookie';
$instlang['s2_error_sessionsettings'] = 'Неправильные настройки подключения';

$instlang['s2_cookietest'] = 'Мы протестируем настройки cookie которые вы напечатали на предыдущей странице.';
$instlang['s2_test_settings'] = 'Тестирование настроек';

$instlang['s3_sync_schema'] = 'Synchronizing Database Schema';
$instlang['s3_sync_data']   = 'Synchronizing Database Data';
$instlang['s3_sync_done']   = 'Synchronization done';
$instlang['s3_exec_queries'] = 'Executing additional queries';
$instlang['s3_inst_modules'] = 'Installing included modules';
$instlang['s3_updt_modules'] = 'Upgrading active modules';
$instlang['s3_inst_done'] = 'Installed';
$instlang['s3_updt_done'] = 'Upgrade done';
$instlang['s3_inst_fail'] = 'Error';
$instlang['s3_nick2'] = 'Имя которое будет использованно как админ этого сайта ( вы можете изменить его позже)';
$instlang['s3_email2'] = 'Ваш е-маил адрес';
$instlang['s3_pass2'] = 'Пароль который вы будете использовать для входа на этот сайт. Вы можете использовать что угодно';
$instlang['s3_timezone'] = 'Временная зона';
$instlang['s3_timezone2'] = 'Временная зона по которой будут помещаться сообщеия';

$instlang['s3_warning'] = 'Убедитесь что вы использовали как минимум: 1 большую и 1 маленькую букву а также одну цифру в вашем пароле.';
$instlang['s3_finnish'] = '<h2>Завершение установки DragonFly '.CPG_NUKE.' на ваш вэб сайт.<br />Удалите install.php файл и установочную директорию, прямо сейчас!<br />После этого насладитесь возможностями DragonflyCMS!</h2><a href="'.$adminindex.'" style="font-size: 14px;">Войти на мой сайт для завершения установки DragonFly</a>';
