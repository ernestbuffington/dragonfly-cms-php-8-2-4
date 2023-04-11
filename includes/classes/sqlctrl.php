<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * AddDefaultValueForUndefinedVariableRector (https://github.com/vimeo/psalm/blob/29b70442b11e3e66113935a2ee22e165a70c74a4/docs/fixing_code.md#possiblyundefinedvariable)
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */
 
if (!defined('DB_TYPE')) { exit; }

abstract class DBCtrl {

	protected static
		$filename,
		$stream;

	public static function output($str, $compress, $end=false)
	{
		static $buffer;

		if ($compress) {
			if (!self::$stream) {
				self::$stream = new \Poodle\Stream\File('php://output','w');
				self::$stream->useGzipCompression();
			}
			$buffer .= $str;
			unset($str);
			if ($end || strlen($buffer) > 65536) {
				self::$stream->write($buffer);
				$buffer = '';
			}
			if ($end) {
				self::$stream->close();
				self::$stream = null;
			}
		} else if (strlen($str)) {
			echo $str;
		}
		if ($end) {
//			trigger_error('SQL Backup memory: '.memory_get_peak_usage(true), E_USER_WARNING);
		}
	}

	public static function query_file($file, &$error, $replace_prefix=false)
	{
		$tmp = [];
  $filedata = null;
  $error = false;
		if (!is_array($file)) {
			$tmp['name'] = $tmp['tmp_name'] = $file;
			$tmp['type'] = preg_match("/\.gz$/is", $file) ? 'application/x-gzip' : 'text/plain';
			$file = $tmp;
		}
		if (empty($file['tmp_name']) || empty($file['name'])) cpg_error('ERROR no file specified!');
		// Most servers identify a .gz as x-tar
		if (preg_match("/^(text\/[a-zA-Z]+)|(application\/(x\-)?(gzip|tar)(\-compressed)?)|(application\/octet-stream)$/is", $file['type'])) {
			$filedata = '';
			$open = 'gzopen';
			$eof = 'gzeof';
			$read = 'gzgets';
			$close = 'gzclose';
			if (!GZIPSUPPORT) {
				if (preg_match("/\.gz$/is", $file['name'])) {
					$error = "Can't decompress file";
					return false;
				}
				$open = 'fopen';
				$eof = 'feof';
				$read = 'fread';
				$close = 'fclose';
			}
			$rc = $open($file['tmp_name'], 'rb');
			if ($rc) {
				while (!$eof($rc)) $filedata .= $read($rc, 100000);
				$close($rc);
			} else {
				$error = 'Couldn\'t open '.$file['tmp_name'].' for processing';
			}
		} else {
			$error = "Invalid filename: $file[type] $file[name]";
		}
		if ($error) { return false; }
		$filedata = DBCtrl::remove_remarks($filedata);
		$queries = DBCtrl::split_sql_file($filedata, ";\n");
		if ((is_countable($queries) ? count($queries) : 0) < 1) {
			$error = 'There are no queries in '.$file['name'];
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		set_time_limit(0);
		foreach ($queries AS $query) {
			if (!$replace_prefix) {
				$query = preg_replace('#(TABLE|INTO|EXISTS|ON) ([a-zA-Z]*?(_))#i', "\\1 {$db->TBL->prefix}".'_', $query);
			} else {
				foreach($replace_prefix AS $oldprefix => $newprefix) {
					if ($oldprefix != $newprefix) {
						$query = preg_replace("/$oldprefix/", $newprefix, $query);
					}
				}
			}
			if (SQL_LAYER == 'mysql' && preg_match('#^CREATE TABLE #', $query) && false === stripos($query, 'ENGINE=MyISAM')) {
				$query .= ' ENGINE=MyISAM';
			}
			$db->query($query);
		}
		return true;
	}

	// remove_remarks will strip the sql comment lines out of an uploaded sql file
	private static function remove_remarks($lines)
	{
		$lines = explode("\n", $lines);
		$linecount = count($lines);
		$output = '';
		for ($i = 0; $i < $linecount; $i++) {
			$line = trim($lines[$i]);
			if (strlen($line) > 0) {
				if ($line[0] != "#" && $line[0] != "-") { $output .= $line . "\n"; }
				# Trading a bit of speed for lower memory use here.
				$lines[$i] = '';
			}
		}
		return $output;
	}

	// split_sql_file will split an uploaded sql file into single sql statements.
	// Note: expects trim() to have already been run on $sql.
	private static function split_sql_file(&$sql, $delimiter)
	{
		// Split up our string into "possible" SQL statements.
		$tokens = explode($delimiter, $sql);
		unset($sql);
		$output = array();

		// we don't actually care about the matches preg gives us.
		$matches = array();

		// this is faster than calling count($oktens) every time thru the loop.
		$token_count = count($tokens);
		for ($i = 0; $i < $token_count; $i++) {
			// Don't wanna add an empty string as the last thing in the array.
			if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0))) {
				// This is the total number of single quotes in the token.
				$total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
				// Counts single quotes that are preceded by an odd number of backslashes,
				// which means they're escaped quotes.
				$escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

				$unescaped_quotes = $total_quotes - $escaped_quotes;

				// If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
				if (($unescaped_quotes % 2) == 0) {
					// It's a complete sql statement.
					$output[] = $tokens[$i];
					// save memory.
					$tokens[$i] = '';
				} else {
					// incomplete sql statement. keep adding tokens until we have a complete one.
					// $temp will hold what we have so far.
					$temp = $tokens[$i].$delimiter;
					// save memory..
					$tokens[$i] = '';

					// Do we have a complete statement yet?
					$complete_stmt = false;

					for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++) {
						// This is the total number of single quotes in the token.
						$total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
						// Counts single quotes that are preceded by an odd number of backslashes,
						// which means they're escaped quotes.
						$escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

						$unescaped_quotes = $total_quotes - $escaped_quotes;

						if (($unescaped_quotes % 2) == 1) {
							// odd number of unescaped quotes. In combination with the previous incomplete
							// statement(s), we now have a complete statement. (2 odds always make an even)
							$output[] = $temp.$tokens[$j];

							$tokens[$j] = '';
							$temp = '';

							// exit the loop.
							$complete_stmt = true;
							// make sure the outer loop continues at the right point.
							$i = $j;
						} else {
							// even number of unescaped quotes. We still don't have a complete statement.
							// (1 odd and 1 even always make an odd)
							$temp .= $tokens[$j].$delimiter;
							$tokens[$j] = '';
						}
					} // for..
				} // else
			}
		}
		return $output;
	}

	public static function installer(array $tables, $filename=false, $structure=true, $data=false, $gzip=false, array $data_options=array())
	{
		$K = \Dragonfly::getKernel();
		$SQL = $K->SQL;
		if (!$filename) $filename = microtime(true).'.xml';
		\Dragonfly\Output\Tools::attachFile($filename, 'xml', $gzip);
		\Dragonfly\Output\Tools::sendFile($SQL->XML->getDocHead());
		foreach ($tables as $table) {
			try {
				$prefix = $SQL->TBL->prefix . '_';
				if (0 === strpos($table, $prefix)) {
					$name = str_replace($prefix, '', $table);
				} else {
					$prefix = $K->db_user_prefix . '_';
					$name = (0 === strpos($table, $prefix)) ? str_replace($prefix, '', $table) : $table;
				}
				if ($structure) {
					\Dragonfly\Output\Tools::sendFile("\n\n".$SQL->XML->getTableXML($name, $table));
				}
				if ($data && $SQL->count($name)) {
					\Dragonfly\Output\Tools::sendFile($SQL->XML->getTableDataXML($name, $table, $data_options));
				}
			} catch (Exception $e) {
				\Dragonfly\Output\Tools::sendFile("\n\n".'<!-- '.$e->getMessage().'-->');
			}
		}
		\Dragonfly\Output\Tools::sendFile($SQL->XML->getDocFoot(), true);
		exit;
	}

}

require(CORE_PATH.'classes/sqlctrl/'.DB_TYPE.'.php');
