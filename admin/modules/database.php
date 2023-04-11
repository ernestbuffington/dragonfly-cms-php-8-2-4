<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('database')) { die('Access Denied'); }

$crlf = "\n";
$mode = (isset($_POST['mode']) && !Dragonfly::isDemo()) ? $_POST['mode'] : '';
if (isset($_POST['tablelist'])) {
	$tablelist = $_POST['tablelist'];
	$full = false;
} else {
	$tablelist = $db->listTables();
	$full = true;
}

\Dragonfly\Page::title(_DATABASE);

set_time_limit(0);

function show_db_result($mode, $tablelist, $query)
{
	global $db;
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->db_mode = $mode;
	$OUT->db_action = strtolower(substr($mode,0,-2));
	if ($query && count($tablelist)) {
		$OUT->query_result = $db->query($query);
	}
	$OUT->display('admin/database/result');
}

function run_sql_in_background($mode, $db, $query)
{
	$result = $db->query($query);
	$numfields = $result->field_count;
	$txt = 'Here are the results of your '.strtolower(strtolower(substr($mode,0,-2))).' request on '.$db->dbname()."\n\n";
	$txt .= str_pad($result->fetch_field_direct(0)->name, 50);
	for ($j=1; $j<$numfields; $j++) {
		$txt .= str_pad($result->fetch_field_direct($j)->name, 30);
	}
	$txt .= "\n".str_pad('', (40*$numfields), '=')."\n";
	while ($row = $result->fetch_row()) {
		$txt .= str_pad($row[0], 50);
		for ($j=1; $j<$numfields; $j++) {
			$txt .= str_pad($row[$j], 30);
		}
		$txt .= "\n";
	}
	file_put_contents(BASEDIR.'cache/sql_result.txt', $txt);
}

function clear_cache_in_background()
{
	Dragonfly::getKernel()->CACHE->clear();
}

class OutputDragonflyDB extends \Poodle\Stream\File
{
	function __construct($filename, $compress)
	{
		\Dragonfly::ob_clean();
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Transfer-Encoding: binary');
		parent::__construct('php://output','w');
		if ($compress) {
			\Poodle\HTTP\Headers::setContentDisposition('attachment', array('filename'=>"{$filename}.gz"));
			\Poodle\HTTP\Headers::setContentType('application/x-gzip', array('name'=>"{$filename}.gz"));
			$this->useGzipCompression();
		} else {
			\Poodle\HTTP\Headers::setContentDisposition('attachment', array('filename'=>$filename));
			\Poodle\HTTP\Headers::setContentType('application/xml', array('name'=>$filename));
		}
	}
}

switch ($mode) {

	case 'BackupDB':
		if (empty($tablelist)) { cpg_error('No tables found'); }
		$filename = $db->database.'_'.date('Y-m-d_H:i:s').'.sql';
		SQLCtrl::backup($db->database, $tablelist, $filename, isset($_POST['dbstruct']), isset($_POST['dbdata']), isset($_POST['drop']), isset($_POST['gzip']), $full);
		break;

	case 'BackupSchema':
		$output = new OutputDragonflyDB('schema.xml', isset($_POST['gzip']));
		$db->XML->exportSchema($output->stream);
		$output->close();
		exit;

	case 'BackupData':
		$name = isset($_POST['filename']) ? "{$_POST['filename']}-" : '';
		$output = new OutputDragonflyDB("{$name}data.xml", isset($_POST['gzip']));
		$config = array(
			'stream' => $output->stream,
			'onduplicate' => 'IGNORE'
		);
		$re = "#^{$db->prefix}(.+)\$#D";
		fwrite($output->stream, $db->XML->getDocHead());
		foreach ($tablelist as $table) {
			if (preg_match($re, $table, $t)) {
				$data = $db->XML->getTableDataXML($t[1], $table, $config);
				if ($data) {
					fwrite($output->stream, $data);
					$data = '';
				}
			}
		}
		fwrite($output->stream, $db->XML->getDocFoot());
		$output->close();
		exit;

	case 'OptimizeDB':
		if ('PostgreSQL' == $db->engine) {
			$db->query('VACUUM ANALYZE');
			$query = 'SELECT cl.relname as tablename, st.* FROM pg_class AS cl, pg_statistic AS st WHERE st.starelid=cl.relfilenode AND cl.relkind IN(\'r\') AND cl.relname NOT LIKE \'pg_%\' AND cl.relname NOT LIKE \'sql_%\' ORDER by cl.relname';
		} else if ('MySQL' == $db->engine) {
			$query = 'OPTIMIZE TABLE '.implode(', ', $tablelist);
		}
//		register_shutdown_function('run_sql_in_background', $mode, $db, $query);
		show_db_result($mode, $tablelist, $query);
		break;

	case 'CheckDB':
		if ('PostgreSQL' == $db->engine) {
			show_db_result($mode, $tablelist, null);
		} else if ('MySQL' == $db->engine) {
			show_db_result($mode, $tablelist, 'CHECK TABLE '.implode(', ', $tablelist).' EXTENDED');
		}
		break;

	case 'AnalyzeDB':
		if ('PostgreSQL' == $db->engine) {
			$Module->sides = 0;
			$MAIN_CFG['global']['admingraphic'] =~ 1; /* \Dragonfly\Page\Menu\Admin::BLOCK */
			if ($MAIN_CFG['global']['admingraphic'] == 0)  $MAIN_CFG['global']['admingraphic'] = 4;
			if ($MAIN_CFG['global']['admingraphic'] < 1)  $MAIN_CFG['global']['admingraphic'] = 4; /* \Dragonfly\Page\Menu\Admin::CSS */
			$db->query = 'ANALYZE';
			$query = 'SELECT tablename, attname, null_frac, avg_width, n_distinct, most_common_vals, most_common_freqs, correlation FROM pg_stats WHERE schemaname=\''.$schema.'\' ORDER BY tablename';
			//$query = 'SELECT * FROM pg_statistic';
		} else if ('MySQL' == $db->engine) {
			$query = 'ANALYZE TABLE '.implode(', ', $tablelist);
		}
		show_db_result($mode, $tablelist, $query);
		break;

	case 'RepairDB':
		if ('PostgreSQL' == $db->engine) {
			$query = 'REINDEX '.$db->database;
		} else if ('MySQL' == $db->engine) {
			$query = 'REPAIR TABLE '.implode(', ', $tablelist);
		}
		show_db_result($mode, $tablelist, $query);
		break;

	case 'StatusDB':
		$Module->sides = 0;
		if ('PostgreSQL' == $db->engine) {
			$schema = $db->uFetchRow('SELECT current_schema()');
			$query = 'SELECT relname, seq_scan, seq_tup_read, idx_scan, idx_tup_fetch, n_tup_upd, n_tup_del FROM pg_stat_user_tables WHERE schemaname = \''.$schema[0].'\' ORDER BY relname';
		} else if ('MySQL' == $db->engine) {
			$query = 'SHOW TABLE STATUS';
		}
		show_db_result($mode, $tablelist, $query);
		break;

	case 'ImportSQL':
		require_once('header.php');
		if (!\Dragonfly::isDemo() && !SQLCtrl::query_file($_FILES['sqlfile'], $error)) {
			cpg_error($error);
		}
		echo '<div class="success">Import of "'.$_FILES['sqlfile']['name'].'" was successful</div>';
		break;

	case 'ImportXML':
		require_once('header.php');
		if (\Dragonfly::isDemo() || $db->XML->syncSchemaFromFile($_FILES['xmlfile']['tmp_name'])) {
			echo '<div class="success">Import of "'.$_FILES['xmlfile']['name'].'" was successful</div>';
		}
		break;

	case 'Installer':
		SQLCtrl::installer($tablelist, false, isset($_POST['inst_structure']), isset($_POST['inst_data']), isset($_POST['gzip']),
			array(
				'onduplicate' => isset($_POST['inst_onduplicate']) ? $_POST['inst_onduplicate'] : null,
				'datamode'    => isset($_POST['inst_datamode']) ? $_POST['inst_datamode'] : null
			)
		);
		break;

	case 'Synch':
		require_once('header.php');
		define('INSTALL', 1);
		require(BASEDIR."install/language/en.php");
		OpenTable();
		echo "<h3>{$instlang['s3_sync_data']}:</h3>";
		$XML = $db->XML->getImporter();
		$XML->addEventListener('afterquery', function(){echo ' .';flush();});
		if (!$XML->syncSchemaFromFile(BASEDIR."includes/dragonfly/setup/db/schemas/core.xml")) {
			print_r($XML->errors);
		} else {
			echo "<br/><strong>{$instlang['s3_sync_done']}</strong>";
		}
		CloseTable();
		register_shutdown_function('clear_cache_in_background');
		break;
/*
	case 'FixCharset':
		if ('MySQL' == $db->engine) {
			$fp  = fopen(CACHE_PATH.'db-schema.xml','w');
			$XML = $db->XML;
			$XML->exportSchema($fp);
			fclose($fp);

			$fp  = fopen(CACHE_PATH.'db-data.xml','w');
			$XML = $db->XML;
			$XML->exportData($fp);
			fclose($fp);

//			SHOW CHARACTER SET FOR DATABASE()
//			SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()
//			SELECT TABLE_NAME, CHARACTER_SET_NAME, TABLE_COLLATION FROM information_schema.TABLES T, information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA WHERE CCSA.collation_name = T.table_collation AND T.table_schema = DATABASE()
//			SHOW TABLE STATUS

			$charset = $db->get_charset();
			$collate = "{$charset}_bin"; // _general_ci or _unicode_ci
			$db->exec("ALTER DATABASE DEFAULT CHARACTER SET {$charset} DEFAULT COLLATE {$collate}");
			foreach ($db->listTables() as $table) {
//				$db->exec("ALTER TABLE {$table} DEFAULT CHARACTER SET {$charset} COLLATE {$collate}");
				$db->exec("ALTER TABLE {$table} CONVERT TO CHARACTER SET {$charset} COLLATE {$collate}");
//				ALTER TABLE cms_bbposts_text MODIFY COLUMN post_text longtext CHARACTER SET utf8 COLLATE utf8_bin NULL
			}
		}
		break;
*/
	default:
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->ZLIB = extension_loaded('zlib');
		$OUT->db_tables = $tablelist;
		$OUT->display('admin/database/index');
		break;
}
