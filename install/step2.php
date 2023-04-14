<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/install/step2.php,v $
  $Revision: 9.33 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:36 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$connected = false;
$db_layers = array(
	'mysql' => 'mysql_connect',
	'mysqli' => 'mysqli_connect',
	'postgresql' => 'pg_connect'
);

if (!isset($_POST['download'])) { inst_header(); }
if (file_exists($config_file)) {
	$go_connect = true;
	$connect = array(
		'layer' => DB_TYPE,
		'charset' => DB_CHARSET,
		'host' => $dbhost,
		'database' => $dbname,
		'username' => $dbuname,
		'password' => $dbpass,
		'prefix' => $prefix,
		'user_prefix' => $user_prefix
	);
} else if (isset($_POST['connect'])) {
	$go_connect = true;
	$connect = $_POST['connect'];
	define('DB_TYPE', $connect['layer']);
	define('DB_CHARSET', NULL);
	require(CORE_PATH.'db/'.DB_TYPE.'.php');
} else {
	$go_connect = false;
	$connect = array(
		'layer' => 'mysql',
		'charset' => NULL,
		'host' => 'localhost',
		'database' => 'dragonfly',
		'username' => '',
		'password' => '',
		'prefix' => 'cms',
		'user_prefix' => 'cms'
	);
}
if ($go_connect) {
	if (!isset($_POST['download'])) { echo '<h2>Trying to connect to SQL server</h2>'; }
	$database = (SQL_LAYER == 'postgresql') ? $connect['database'] : '';
	$db = new sql_db($connect['host'], $connect['username'], $connect['password'], $database);
	if (!defined('NO_DB')) {
		$connected = true;
		if (SQL_LAYER == 'postgresql' && $connect['database'] == 'public') {
			inst_header();
			echo '<br /><br />You need to choose a different database name.<br />Sorry for the inconvenience but you cannot continue with the installation with "<b>public</b>" as database name.';
			footer();
			exit;
		}

		$databases = $db->list_databases();
		if (SQL_LAYER != 'postgresql' && isset($databases[$connect['database']])) {
			$db->select_db($connect['database']);
		}
		$server = get_db_vars($db);
		if (!isset($databases[$connect['database']])) {
			$query = "CREATE DATABASE {$connect['database']}";
			if (SQL_LAYER == 'mysql' && $server['unicode']) {
				$query .= ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci';
			} elseif (SQL_LAYER == 'postgresql') {
				$query .= " OWNER = {$connect['username']}";
			}
			if ($db->query($query)) {
				$db->select_db($connect['database']);
			} else {
				define('NO_DB', "Failed to create the database {$connect['database']}");
				$connected = false;
			}
		} else if (SQL_LAYER == 'mysql' && $server['unicode'] && ($server['character_set_database'] != 'utf8' || $server['collation_database'] != 'utf8_general_ci')) {
			$db->query("ALTER DATABASE `{$connect['database']}` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci");
		}
		if (SQL_LAYER == 'postgresql') {
			# $db->query("SET SESSION AUTHORIZATION {$connect['username']}");
			$db->query("CREATE SCHEMA {$connect['database']} AUTHORIZATION {$connect['username']}");
			$db->query("REVOKE ALL ON SCHEMA {$connect['database']} FROM PUBLIC");
			$db->query("ALTER USER {$connect['username']} SET search_path TO {$connect['database']}");
			$db->query("ANALYZE");
			# Create some functions so that some MySQL-isms can be used in PostgreSQL.
			$db->query("CREATE OR REPLACE FUNCTION left(character varying, integer) RETURNS character varying AS 'select substr($1, 0, $2) as result' LANGUAGE sql");
			$db->query("CREATE OR REPLACE FUNCTION unix_timestamp(timestamp without time zone) RETURNS integer AS 'SELECT DATE_PART(''epoch'', $1)::INT4 as RESULT' LANGUAGE sql");
			$db->query("CREATE OR REPLACE FUNCTION rand() RETURNS double precision AS 'SELECT RANDOM() as RESULT' LANGUAGE sql");
			$db->query("CREATE OR REPLACE FUNCTION concat(text, text) RETURNS text AS 'select $1 || $2' LANGUAGE sql IMMUTABLE STRICT");
			$db->query("CREATE OR REPLACE FUNCTION concat(text, text, text) RETURNS text AS 'select $1 || $2 || $3' LANGUAGE sql IMMUTABLE STRICT");
			$db->query("CREATE OR REPLACE FUNCTION concat(text, text, text, text) RETURNS text AS 'select $1 || $2 || $3 || $4' LANGUAGE sql IMMUTABLE STRICT");
		}
	}
}

if ($connected) {
	$server = get_db_vars($db);
	if (SQL_LAYER == 'mysql' && $server['unicode']) {
		if ($server['character_set_client'] != 'utf8' ||
		    $server['character_set_connection'] != 'utf8' ||
		    $server['character_set_results'] != 'utf8' ||
		    $server['collation_connection'] != 'utf8_general_ci')
		{
			$connect['charset'] = 'utf8';
		}
	}
	$written = false;
	if (!isset($CensorList)) {
		include(BASEDIR.'install/config.php');
		if (isset($_POST['download'])) {
			header('Content-Type: text/x-delimtext; name="config.php"');
			header('Content-disposition: attachment; filename=config.php');
			echo $content;
			exit;
		}
		$written = false;
		if ($fp = fopen($config_file, 'wb')) {
			$written = (fwrite($fp, $content) !== false);
			fclose($fp);
			chmod($config_file, 0644);
		}
		if ($written) {
			echo '<h1>Saving configuration succeeded</h1>';
		} else {
			echo '<h1>Saving configuration failed</h1>';
		}
	} else {
		echo '<h1>Database connection succeeded</h1>';
		$written = true;
	}
	Cache::clear();
	if ($written) {
		echo '<p><input type="hidden" name="step" value="3" />
		<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
	} else {
		echo 'Instead download the config.php file and upload it to the server into:<br/>
		'.dirname($config_file).'/
		<p><input type="hidden" name="step" value="2" />
		<input type="hidden" name="connect[layer]" value="'.$connect['layer'].'" />
		<input type="hidden" name="connect[host]" value="'.$connect['host'].'" />
		<input type="hidden" name="connect[username]" value="'.$connect['username'].'" />
		<input type="hidden" name="connect[password]" value="'.$connect['password'].'" />
		<input type="hidden" name="connect[database]" value="'.$connect['database'].'" />
		<input type="hidden" name="connect[prefix]" value="'.$connect['prefix'].'" />
		<input type="hidden" name="connect[user_prefix]" value="'.$connect['user_prefix'].'" />
		<input type="submit" name="download" value="Download config.php" class="formfield" />
		<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
	}
}
else {
	if (defined('NO_DB')) { echo '<h1>'.NO_DB.'</h1>'; }
	echo '<script language="JavaScript" type="text/javascript">
<!--'."
maketip('dbase','".$instlang['s1_dbconfig']."','".$instlang['s1_database']."');
maketip('layer','".$instlang['s1_layer']."','".$instlang['s1_layer2']."');
maketip('hostname','".$instlang['s1_host']."','".$instlang['s1_host2']."');
maketip('username','".$instlang['s1_username']."','".$instlang['s1_username2']."');
maketip('password','".$instlang['s1_password']."','".$instlang['s1_password2']."');
maketip('dbname','".$instlang['s1_dbname']."','".$instlang['s1_dbname2']."');
maketip('prefix','".$instlang['s1_prefix']."','".$instlang['s1_prefix2']."');
maketip('uprefix','".$instlang['s1_userprefix']."','".$instlang['s1_userprefix2']."');
".'// -->
</script>
<table>
	<tr>
	  <th colspan="2" nowrap="nowrap">'.$instlang['s1_dbconfig'].'</th>
	  <td>'.inst_help('dbase').'</td>
	</tr><tr>
	  <td colspan="3"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <td>'.$instlang['s1_layer'].'</td><td><select name="connect[layer]">';
	foreach ($db_layers as $layer => $func) {
		if (function_exists($func) && file_exists(CORE_PATH."db/$layer.php")) {
			echo ($connect['layer'] == $layer) ? "<option selected=\"selected\">$layer</option>" : "<option>$layer</option>";
		}
	}
	echo '</td>
	  <td>'.inst_help('layer').'</td>
	</tr><tr>
	  <td>'.$instlang['s1_host'].'</td><td><input type="text" name="connect[host]" value="'.$connect['host'].'"></td>
	  <td>'.inst_help('hostname').'</td>
	</tr><tr>
	  <td>'.$instlang['s1_username'].'</td><td><input type="text" name="connect[username]" value="'.$connect['username'].'"></td>
	  <td>'.inst_help('username').'</td>
	</tr><tr>
	  <td>'.$instlang['s1_password'].'</td><td><input type="password" name="connect[password]" value="'.$connect['password'].'"></td>
	  <td>'.inst_help('password').'</td>
	</tr><tr>
	  <td>'.$instlang['s1_dbname'].'</td><td><input type="text" name="connect[database]" value="'.$connect['database'].'"></td>
	  <td>'.inst_help('dbname').'</td>
	</tr><tr>
	  <td>'.$instlang['s1_prefix'].'</td><td><input type="text" name="connect[prefix]" value="'.$connect['prefix'].'"></td>
	  <td>'.inst_help('prefix').'</td>
	</tr><tr>
	  <td>'.$instlang['s1_userprefix'].'</td><td><input type="text" name="connect[user_prefix]" value="'.$connect['user_prefix'].'"></td>
	  <td>'.inst_help('uprefix').'</td>
	</tr><tr>
	  <td colspan="3"><hr noshade="noshade" size="1" /></td>
	</tr>
</table>'
	.$instlang['s1_correct'].'<p align="center"><input type="hidden" name="step" value="2" />
	<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
}
