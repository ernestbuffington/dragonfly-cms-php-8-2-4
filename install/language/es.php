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

$instlang['installer'] = 'Instalador';
$instlang['s_progress'] = 'Progreso de la instalación';
$instlang['s_license'] = 'Licencia';
$instlang['s_server'] = 'Estado servidor';
$instlang['s_setconfig'] = 'Crear config.php';
$instlang['s_builddb'] = 'Crear base de datos';
$instlang['s_gather'] = 'Recopilar información importante';
$instlang['s_create'] = 'Crear cuenta de superadministrador';
$instlang['welcome'] = 'Bienvenido a Dragonfly';
$instlang['info'] = 'Esta instalación le guiará para configurar DragonflyCMS en su sitio web en cuestión de minutos.<br />El programa de instalación creará la base de datos necesaria y primer usuario o actualizarán su CPG o PHPNuke instalado.';
$instlang['click'] = 'Haz clic en "Aceptar" si se acepta la siguiente licencia:';
$instlang['no_zlib'] = 'El servidor no soporta la compresión Zlib. Por lo tanto usted no puede leer nuestra licencia de esta página. Por favor, consulte GPL.txt se encuentran en su distribución CPG-Nuke/DragonflyCMS y haga clic en el botón "Acepto" abajo';
$instlang['agree'] = 'Acepto';
$instlang['next'] = "Siguiente";

$instlang['s1_already'] = 'Usted ya tiene '.((CPG_NUKE < 9) ? 'CPG-Nuke' : 'Dragonfly').' <b>'.CPG_NUKE.'</b> instalado.';
$instlang['s1_new'] = 'El instalador no ha podido encontrar una versión anterior, por lo que se instalará una nueva versión para usted';
$instlang['s1_upgrade'] = 'Su versión actual es <b>%s</b>, y será actualizado/convertido a DragonflyCMS. '.CPG_NUKE.'<br /><b>Asegúrese de tener una copia de seguridad de su base de datos</b>.';
$instlang['s1_unknown'] = 'El instalador no puede detectar qué versión de CPG-Nuke/PHP-Nuke está utilizando.<br />No se puede continuar la instalación.<br />Por favor, póngase en contacto con el CPG Dev Team';
$instlang['s1_database'] = 'Este es un resumen de lo que se detectó sobre su configuración en config.php para la conexión de base de datos';

$instlang['s1_dbconfig'] = 'Configuración de base de datos';
$instlang['s1_server2'] = 'La versión de %s que está actualmente activa en el servidor';
$instlang['s1_layer'] = 'Capa SQL';
$instlang['s1_layer2'] = 'La capa de SQL para su uso con el sitio web';
$instlang['s1_host'] = 'Nombre de la máquina';
$instlang['s1_host2'] = 'El nombre DNS o la IP del servidor que ejecuta el servidor SQL';
$instlang['s1_username'] = 'Nombre de Usuario';
$instlang['s1_username2'] = 'El nombre de usuario utilizado para iniciar sesión en el servidor SQL';
$instlang['s1_password'] = 'Contraseña de acceso';
$instlang['s1_password2'] = 'La contraseña del usuario para iniciar sesión en el servidor SQL';
$instlang['s1_dbname'] = 'Nombre de base de datos';
$instlang['s1_dbname2'] = 'El nombre de una base de datos específica que contiene las tablas que desee con los datos';
$instlang['s1_prefix'] = 'Prefijo de tabla';
$instlang['s1_prefix2'] = 'Un defecto prefijo para nombres de tablas';
$instlang['s1_directory_write'] = 'Directorio de acceso de escritura';
$instlang['s1_directory_write2'] = '. Directorios que necesitan acceso de escritura para almacenar información como imágenes cargadas<br />Si falla, entonces cambie los permisos de escritura con "CHMOD 777" en ese directorio';
$instlang['s1_dot_ok'] = 'OK';
$instlang['s1_dot_failed'] = 'Error pero no crítico';
$instlang['s1_dot_critical'] = 'Error crítico';

$instlang['s1_server_settings'] = 'Configuración del servidor';
$instlang['s1_setting'] = 'Configuración';
$instlang['s1_preferred'] = 'Necesario';
$instlang['s1_yours'] = 'Actual';
$instlang['s1_on'] = 'On';
$instlang['s1_off'] = 'Off';

$instlang['s1_correct'] = 'Si la información anterior es correcta, entonces vamos a comenzar a construir la base de datos';
$instlang['s1_fixerrors'] = 'Por favor, corrija los errores que se han mencionado anteriormente';
$instlang['s1_fatalerror'] = 'Por favor, pongase en contacto con el CPG-Nuke Dev Team sobre el error<br />No se puede continuar con la instalación';
$instlang['s1_build_db'] = 'Vamos a construir la base de datos';
$instlang['s1_necessary_info'] = 'Información necesaria';
$instlang['s1_donenew'] = 'La base de datos ha sido correctamente instalada, ahora vamos a continuar la instalación solicitándole alguna información necesaria';
$instlang['s1_doneup'] = 'La base de datos ha sido actualizada correctamente. Diviértete con DragonflyCMS.<br /><h2>Cambia el valor de DF_MODE_INSTALL a false y elimina la carpeta install</h2>';
$instlang['s1_trying_to_connect'] = 'Tratando de conectar con el servidor SQL';
$instlang['s1_wrong_database_name'] = 'Tienes que elegir un nombre diferente para la base de datos<br /> Siento las molestias, pero no se puede continuar con la instalación con "<b>público</ b>" como nombre de base de datos';
$instlang['s1_save_conf_succeed'] = 'Se guardó la configuración correctamente';
$instlang['s1_save_conf_failed'] = 'Error al guardar la configuración';
$instlang['s1_db_connection_succeeded'] = 'La conexión a la base de datos tuvo éxito';

$instlang['s2_info'] = 'Configuración de la información necesaria:';
$instlang['s2_account'] = 'La información necesaria se ha añadido. Vamos a la configuración de su primera cuenta';
$instlang['s2_create'] = 'Crear cuenta';

$instlang['s2_domain'] = 'Nombre de Dominio';
$instlang['s2_domain2'] = 'El nombre de dominio en el que se encuentra alojado su sitio web Dragonfly, por ejemplo <i>www.mysite.com</i>';
$instlang['s2_path'] = 'Ruta o Path';
$instlang['s2_path2'] = 'La ruta de acceso en el que se encuentra alojado su sitio web Dragonfly, por ejemplo <i>/html/</i>';
$instlang['s2_email2'] = 'La dirección de correo electrónico principal donde debe ser enviada la información del sitio web';
$instlang['s2_session_path'] = 'Guardar ruta de sesión';
$instlang['s2_session_path2'] = 'Esta es la ruta donde se almacenan los archivos de datos.<br />Debe cambiar esta variable a fin de poder utilizar las funciones de sesión de DragonflyCMS.<br />La ruta debe ser accesible por PHP como /home/myname/tmp/sessiondata y probablemente sea necesario cambiar los permisos con CHMOD 777.';
$instlang['s2_cookie_domain'] = 'cookie de dominio';
$instlang['s2_cookie_domain2'] = 'El dominio completo o de nivel superior donde almacenar las cookies, por ejemplo <i>mysite.com</i> o simplemente dejar en blanco';
$instlang['s2_cookie_path'] = 'Ruta de cookies';
$instlang['s2_cookie_path2'] = 'La dirección de Internet a la que limitar la cookie, por ejemplo <i>/html/</i>';
$instlang['s2_cookie_admin'] = 'nombre de la cookie de administración';
$instlang['s2_cookie_admin2'] = 'El nombre de la cookie para almacenar la información de inicio de sesión de administrador de este sitio web';
$instlang['s2_cookie_member'] = 'nombre de la cookie para miembros';
$instlang['s2_cookie_member2'] = 'El nombre de la cookie para almacenar información de entrada de los miembros de este sitio web';

$instlang['s2_error_email'] = 'Dirección de correo electrónico no válida';
$instlang['s2_error_empty'] = 'Algunos de los campos quedaron vacíos';
$instlang['s2_error_cookiename'] = 'Nombre de la cookie no válida';
$instlang['s2_error_cookiesettings'] = 'Configuración de las cookies no válida';
$instlang['s2_error_sessionsettings'] = 'Configuración de la sesión incorrecto';

$instlang['s2_cookietest'] = 'Vamos a probar la configuración de las cookies que ha especificado antes de proceder.';
$instlang['s2_test_settings'] = 'Configuración de prueba';

$instlang['s3_sync_schema'] = 'Sincronizando estructura de Base de Datos';
$instlang['s3_sync_data']   = 'Sincronizando datos de Base de Datos';
$instlang['s3_sync_done']   = 'Sincronización completada';
$instlang['s3_exec_queries'] = 'Ejecutando consultas adicionales';
$instlang['s3_inst_modules'] = 'Instalando modulos incluidos';
$instlang['s3_updt_modules'] = 'Actualizando modulos activos';
$instlang['s3_inst_done'] = 'Instalado';
$instlang['s3_updt_done'] = 'Actualizado';
$instlang['s3_inst_fail'] = 'Fallo';
$instlang['s3_nick2'] = 'El nombre que utiliza para iniciar sesión en este sitio web como administrador';
$instlang['s3_email2'] = 'Su dirección de correo electrónico';
$instlang['s3_pass2'] = 'La contraseña que utiliza para iniciar sesión en este sitio web. Usted puede utilizar cualquier carácter';
$instlang['s3_timezone'] = 'Zona horaria';
$instlang['s3_timezone2'] = 'La zona horaria en la que desea ver la hora de los mensajes enviados';

$instlang['s3_warning'] = 'Asegúrese de que utiliza por lo menos: 1 mayúscula, 1 minúscula y un número en la contraseña';
$instlang['s3_finnish'] = '<h2> DragonflyCMS '.CPG_NUKE.' se ha instalado correctamente.<br />Ahora quite el directorio de instalación!<br />Luego disfrute de DragonflyCMS!</h2><a href="'.$adminindex.'"  "style="font-size:14px">Entra en tu sitio web para configurar el resto de opciones.</a>';
