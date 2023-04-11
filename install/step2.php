<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

$connected = false;

$extensions = array_flip(get_loaded_extensions());
$ext_db = array(
	'mysqli' => 'MySQL/MariaDB/Percona',
	'pgsql'  => 'PostgreSQL',
);
foreach ($ext_db as $type => $name) {
	if (!isset($extensions[$type]) || !is_file(CORE_PATH."poodle/sql/adapter/{$type}.php")) {
		unset($ext_db[$type]);
	}
}
unset($extensions);

if (!isset($_POST['download'])) {
	inst_header();
}

if (is_file($config_file)) {
	$go_connect = false;
	$connected = true;
} else if (isset($_POST['connect'])) {
	$go_connect = true;
	$connect = $_POST['connect'];
	$connect['user_prefix'] = $connect['prefix'];
	define('DB_TYPE', $connect['layer']);
} else {
	$go_connect = false;
	$connect = array(
		'layer' => 'mysqli',
		'charset' => NULL,
		'host' => '127.0.0.1',
		'database' => 'dragonfly',
		'username' => '',
		'password' => '',
		'prefix' => 'cms'
	);
}
if ($go_connect) {
	if (!isset($_POST['download'])) {
		echo '<h2>'.$instlang['s1_trying_to_connect'].'</h2>';
	}
	try {
		$db = new \Dragonfly\SQL(
			DB_TYPE,
			array(
				'host'     => $connect['host'],
				'username' => $connect['username'],
				'password' => $connect['password'],
				'database' => $connect['database'],
				'charset'  => DB_CHARSET
			),
			$connect['prefix'].'_'
		);

		$connected = true;
		if ('PostgreSQL' == $db->engine && $connect['database'] == 'public') {
			inst_header();
			echo '<br /><br />'.$instlang['s1_wrong_database_name'];
			footer();
			exit;
		}

		$server = $db->get_details();
		if ('MySQL' == $db->engine && $server['unicode']) {
			$db->setSchemaCharset();
		}
		else if ('PostgreSQL' == $db->engine) {
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
	} catch (\Exception $e) {
		echo '<strong style="color:#F00">ERROR: '.$e->getCode().': '.$e->getMessage().'</strong>';
	}
}

if ($connected) {
	$server = $db->get_details();
	if ('MySQL' == $db->engine && $server['unicode']) {
		if ($db->server_version >= 50503) {
			$connect['charset'] = 'utf8mb4';
		} else {
			$connect['charset'] = 'utf8';
		}
	}
	$written = false;
	if (!is_file($config_file)) {
		include(BASEDIR.'install/config.php');
		if (isset($_POST['download'])) {
			header('Content-Type: text/x-delimtext; name="config.php"');
			header('Content-disposition: attachment; filename=config.php');
			echo $content;
			exit;
		}
		if ($fp = fopen($config_file, 'wb')) {
			$written = (fwrite($fp, $content) !== false);
			fclose($fp);
			chmod($config_file, 0644);
		}
		if ($written) {
			echo '<h2>'.$instlang['s1_save_conf_succeed'].'</h2>';
		} else {
			echo '<h2>'.$instlang['s1_save_conf_failed'].'</h2>';
		}
	} else {
		echo '<h2>'.$instlang['s1_db_connection_succeeded'].'</h2>';
		$written = true;
	}
	Dragonfly::getKernel()->CACHE->clear();
	if ($written) {
		echo '<p><input type="hidden" name="step" value="3" />
		<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
	} else {
		echo 'Instead download the config.php file and upload it to the server into:<br/>
		'.CORE_PATH.'
		<p><input type="hidden" name="step" value="2" />
		<input type="hidden" name="connect[layer]" value="'.$connect['layer'].'" />
		<input type="hidden" name="connect[host]" value="'.$connect['host'].'" />
		<input type="hidden" name="connect[username]" value="'.$connect['username'].'" />
		<input type="hidden" name="connect[password]" value="'.$connect['password'].'" />
		<input type="hidden" name="connect[database]" value="'.$connect['database'].'" />
		<input type="hidden" name="connect[prefix]" value="'.$connect['prefix'].'" />
		<input type="submit" name="download" value="Download config.php" class="formfield" />
		<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
	}
}
else {
	echo '<table>
	<tr>
	  <th colspan="2" nowrap="nowrap">'.$instlang['s1_dbconfig'].'</th>
	  <td><i class="infobox"><span>'.$instlang['s1_database'].'</span></i></td>
	</tr><tr>
	  <td colspan="3"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <td>'.$instlang['s1_layer'].'</td><td><select name="connect[layer]">';
	foreach ($ext_db as $layer => $info) {
		echo ($connect['layer'] == $layer) ? "<option selected=\"selected\" value=\"{$layer}\">" : "<option value=\"{$layer}\">";
		echo "{$info}</option>";
	}
	echo '</td>
	  <td><i class="infobox"><span>'.$instlang['s1_layer2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_host'].'</td><td><input type="text" name="connect[host]" value="'.$connect['host'].'"></td>
	  <td><i class="infobox"><span>'.$instlang['s1_host2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_username'].'</td><td><input type="text" name="connect[username]" value="'.$connect['username'].'"></td>
	  <td><i class="infobox"><span>'.$instlang['s1_username2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_password'].'</td><td><input type="password" name="connect[password]" value="'.$connect['password'].'"></td>
	  <td><i class="infobox"><span>'.$instlang['s1_password2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_dbname'].'</td><td><input type="text" name="connect[database]" value="'.$connect['database'].'"></td>
	  <td><i class="infobox"><span>'.$instlang['s1_dbname2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_prefix'].'</td><td><input type="text" name="connect[prefix]" value="'.$connect['prefix'].'"></td>
	  <td><i class="infobox"><span>'.$instlang['s1_prefix2'].'</span></i></td>
	</tr><tr>
	  <td colspan="3"><hr noshade="noshade" size="1" /></td>
	</tr>
</table>'
	.$instlang['s1_correct'].'<p align="center"><input type="hidden" name="step" value="2" />
	<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
}
