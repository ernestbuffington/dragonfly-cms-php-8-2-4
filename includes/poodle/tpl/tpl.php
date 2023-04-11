<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	Modified to support old CPGTPL system
*/

namespace Poodle;

class TPL extends \Poodle\TPL\Context
{
	const
		OPT_PUSH_DOCTYPE = 1,
		OPT_END_PARSER   = 2,
		OPT_XMLREADER    = 4;

	public static
		$USE_EVAL = true,
		$CACHE_DIR = true,
		$ERROR_MODE = 0; # 0 = html, 1 = parsed html

	public
		$L10N,
		$DTD,

		$bodylayout = '';

	protected
		$tpl_name = 'default',
		$tpl_path = 'tpl/default/',
		$tpl_type = 'html';

	private
		$_files = array(),
		$_xml_parser,
		$_total_time = 0;

	function __construct()
	{
		parent::__construct();
		$this->L10N = new \Poodle\L10N();
		if (!self::$USE_EVAL) {
			stream_wrapper_register('tpl', 'Poodle\\TPL\\StreamWrapper');
		}
		if (self::$CACHE_DIR) {
			$dir = \Poodle::getKernel()->cache_dir;
			self::$CACHE_DIR = $dir ? rtrim($dir,'/\\') . '/' : false;
		}
	}

	function __get($key)
	{
		$SQL = \Poodle::getKernel()->SQL;
		switch ($key)
		{
		// Start old CPGTPL stuff
		case 'theme':        return $this->tpl_name;
		case 'THEME_PATH':   return DF_STATIC_DOMAIN.'themes/'.$this->tpl_name;
		case 'REQUEST_URI':  return substr($_SERVER['REQUEST_URI'], strlen(DOMAIN_PATH));
		// End old CPGTPL stuff

		case 'bugs':         return \Poodle\Debugger::displayPHPErrors() ? \Poodle\Debugger::report() : null;
		case 'bugs_json':    return \Poodle\Debugger::displayPHPErrors() ? json_encode(\Poodle\Debugger::report()) : null;
		case 'memory_usage': return (is_object($this->L10N) ? $this->L10N->filesizeToHuman(memory_get_peak_usage()) : memory_get_peak_usage());
		case 'parse_time':   return microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'];
		case 'tpl_time':     return $this->_total_time;
		case 'queries':      return is_object($SQL) ? $SQL->total_queries : null;
		case 'queries_time': return is_object($SQL) ? round($SQL->total_time, 4) : null;
		case 'debug_json':
			$r = array();
			if (\Poodle\Debugger::displayPHPErrors()) {
				$bugs = array();
				foreach (\Poodle\Debugger::report() as $file => $log) {
					$bugs[] = array('file' => $file, 'log' => $log);
				}
				foreach (\Dragonfly::getKernel()->DEBUGGER->report as $file => $log) {
					$bugs[] = array('file' => $file, 'log' => array('' => $log));
				}
				if ($bugs) { $r['php'] = $bugs; }
				unset($bugs);
			}
			if (\Poodle::$DEBUG) {
				if (\Poodle::$DEBUG & \Poodle::DBG_MEMORY) {
					$r['memory'] = memory_get_peak_usage();
				}
				if (\Poodle::$DEBUG & \Poodle::DBG_TPL_TIME) {
					$r['tpl_time'] = $this->_total_time;
				}
				if (\Poodle::$DEBUG & \Poodle::DBG_TPL_INCLUDED_FILES) {
					$r['tpl_files'] = \Poodle::shortFilePath($this->_files);
					sort($r['tpl_files']);
				}
				if ((\Poodle::$DEBUG & \Poodle::DBG_SQL || \Poodle::$DEBUG & \Poodle::DBG_SQL_QUERIES) && is_object($SQL)) {
					$r['sql'] = array('count' => $SQL->total_queries, 'time' => $SQL->total_time);
					if (\Poodle::$DEBUG & \Poodle::DBG_SQL_QUERIES && $SQL->querylist) {
						$r['sql']['queries'] = array();
						foreach ($SQL->querylist as $f => $q) {
							$r['sql']['queries'][$f] = $q;
						}
					}
				}
				if (\Poodle::$DEBUG & \Poodle::DBG_INCLUDED_FILES) {
					$r['included_files'] = \Poodle::shortFilePath(array_filter(get_included_files(), function($v){return false===strpos($v,'tpl://');}));
					sort($r['included_files']);
				}
				if (\Poodle::$DEBUG & \Poodle::DBG_DECLARED_CLASSES) {
					// PHP 5.4.16: Invalid UTF-8 sequence in argument
					// Somewhere there's a PHP memory bug so we filter out any class without '\'
					$r['declared_classes'] = array_values(array_filter(get_declared_classes(), function($v){return !!strpos($v,'\\');}));
				}
				if (\Poodle::$DEBUG & \Poodle::DBG_DECLARED_INTERFACES) {
					$r['declared_interfaces'] = get_declared_interfaces();
				}
				// get_defined_functions()
				if (\Poodle::$DEBUG & \Poodle::DBG_PARSE_TIME) {
					$r['parse_time'] = microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'];
				}
			}
			return str_replace('\\/','/',json_encode($r));
		}
		return parent::__get($key);
	}

	public function init() {}

	# Gecko supports background-image
	public function uaSupportsSelectOptionBgImage()
	{
		return 'gecko' === \Poodle\HTTP\Client::engine()->name;
	}

	public function toString($filename, $data = null, $mtime = 0, $options = 0)
	{
		if ($data && !($data instanceof \Poodle\TPL\Context) && !preg_match('#((tal|i18n|xsl):|(<[^>]+href|src|action|formaction)="/)#',$data)) {
			return $data;
		}
		ob_start();
		$result = self::display($filename, $data, $mtime, $options | self::OPT_XMLREADER);
		if ($result) {
			return ob_get_clean();
		}
		echo ob_get_clean();
		return false;
	}

	protected function evalCache($key, $ctx)
	{
		if (self::$USE_EVAL) {
			return $this->evalData(\Poodle::getKernel()->CACHE->get($key), $ctx);
		}
		include('tpl://cache/'.$key);
		return true;
	}

	protected function evalData($data, $ctx)
	{
		if (self::$USE_EVAL) {
			return ($data && false !== eval('?>'.$data));
		}
		include('tpl://data/'.base64_encode($data));
		return true;
	}

	public function display($filename, $data = null, $mtime = 0, $options = 0)
	{
		$ctx = $this;
		if ($data instanceof \Poodle\TPL\Context) {
			$ctx  = $data;
			$data = null;
		}
		if (!$data && !is_string($filename)) {
			trigger_error('No data to display');
			return false;
		}
		if ($data && !preg_match('#((tal|i18n|xsl):|(<[^>]+href|src|action|formaction)="/)#',$data)) {
			echo $data;
			return true;
		}

		// Start old CPGTPL stuff
		if (isset($this->cpgtpl_filenames[$filename])) { $filename = $this->cpgtpl_filenames[$filename]; }
		// End old CPGTPL stuff

		$time = microtime(true);
		$tpl_file = $this->tpl_file;
		$this->tpl_file = $filename.'.xml';

		if (!$this->_xml_parser) {
			$this->_xml_parser = new \Poodle\TPL\Parser($this);
		}

		$parsed = false;
		$CACHE = \Poodle::getKernel()->CACHE;
		$error = $file = $cache_file = $cache_key = null;
		if ($filename) {
			if ($data) {
				$cache_key = "tpl/_db/{$this->tpl_type}/".$this->tpl_file;
			} else {
				if (!$mtime && $file = $this->findFile($filename)) {
					$mtime = filemtime($file);
				}
				$cache_key = $this->tpl_path . $this->tpl_type . '/' . $this->tpl_file;
			}
			$cache_key = strtr($cache_key, '\\', '/');
			if (self::$CACHE_DIR) {
				$cache_file = self::$CACHE_DIR . $cache_key . '.php';
				if (is_file($cache_file) && (!$mtime || filemtime($cache_file) > $mtime)) {
					if ($options & self::OPT_PUSH_DOCTYPE) {
						$this->push_doctype();
					}
					include $cache_file;
					$parsed = true;
				}
			} else
			if ($CACHE && $CACHE->exists($cache_key) && (!$mtime || $CACHE->mtime($cache_key) > $mtime)) {
				if ($options & self::OPT_PUSH_DOCTYPE) {
					$this->push_doctype();
				}
				$parsed = $this->evalCache($cache_key, $ctx);
			}
		}

		if (!$parsed) {
			if (!$data && !$file) {
				$file = $this->findFile($filename);
			}
			if (!$data && !$file) {
				$parsed = false;
				$error = array(
					'type' => E_USER_WARNING,
					'message' => "File not found",
					'file' => $filename,
					'line' => 0,
				);
			} else if (!$data && $file && 'xml' !== substr($file, -3)) {
				// Start old CPGTPL stuff
				$this->_xml_parser->data = \Dragonfly\TPL\v9parser::parse($file);
				$parsed = true;
				// End old CPGTPL stuff
			} else if ($options & self::OPT_XMLREADER) {
				$parsed = $this->_xml_parser->parse_xml($file, $data);
			} else {
				$parsed = $this->_xml_parser->parse_chunk($file, $data, $options & self::OPT_END_PARSER);
			}
			$pdata = $this->_xml_parser->data;
			$this->_xml_parser->data = '';
			if ($parsed) {
				if ($options & self::OPT_PUSH_DOCTYPE) {
					$this->push_doctype();
				}
				if (strlen($pdata)) {
					$err_level = error_reporting(error_reporting() & ~E_PARSE & ~E_USER_WARNING);
					if ($this->evalData($pdata, $ctx)) {
						$data = null;
						if ($cache_file) {
							$dir = dirname($cache_file);
							if (!is_dir($dir)) {
								mkdir($dir, 0777, true);
							}
							file_put_contents($cache_file, $pdata);
						} else if ($cache_key && $CACHE) {
							$CACHE->set($cache_key, $pdata);
						}
						$pdata = null;
					} else {
						$error = error_get_last();
						$error['file'] = $filename;
					}
					error_reporting($err_level);
				} else {
					$error = array(
						'type' => E_USER_WARNING,
						'message' => 'Parsed data resulted in an empty string',
						'file' => $filename,
						'line' => 0,
					);
				}
			} else if ($this->_xml_parser->errors) {
				$error = $this->_xml_parser->errors[0];
			}
			if ($error) {
				$line = (int)$error['line'];
				if ((isset($error['type']) && 4 == $error['type']) || (1 === self::$ERROR_MODE && !isset($error['node']))) {
					$lines = preg_split("#<br[^>]*>#", highlight_string($pdata, true));
				} else {
					$count = 1;
					$lines = preg_replace('#\R#', "\n", $parsed ? $pdata : $data ?: file_get_contents($error['file']));
					if (false !== strpos($lines,'<?xml')) {
						++$line;
					}
					if (false !== strpos($lines,'<!DOCTYPE')) {
						++$line;
					}
					$lines = preg_split("#<br[^>]*>#", highlight_string($lines, true));
				}
				$l = max(0, $line-1);
				$lines[$l] = '<b style="background-color:#fcc">'.$lines[$l].'</b>';
				echo "<h1>{$error['message']} in {$filename} on line {$line}</h1>";
				if (!empty($error['node'])) {
					$node = $error['node'];
					echo "<h2>Tag '{$node['name']}' started in {$node['file']} on line {$node['line']}</h2>";
				}
				echo "\n".implode("<br/>\n", $lines);
				throw new \Exception("{$error['message']} in {$filename} on line {$line}");
				return false;
			}
		}

		if ($file && \Poodle::$DEBUG & \Poodle::DBG_TPL_INCLUDED_FILES) {
			$this->_files[] = $file . ' (' . round((microtime(true)-$time)*1000, 2) . ' ms)';
		}

		$this->tpl_file = $tpl_file;
		$this->_total_time += microtime(true)-$time;
		return true;
	}

	# Check for a valid file
	public function findFile($filename)
	{
		$lfilename = strtolower($filename);
		$files = array(
			"{$this->tpl_path}{$this->tpl_type}/{$filename}.xml",
			"{$this->tpl_path}{$this->tpl_type}/{$lfilename}.xml",
			"{$this->tpl_path}template/{$filename}.html" // old CPGTPL
		);
		if ('default' !== basename($this->tpl_path)) {
			$files[] = dirname($this->tpl_path)."/default/{$this->tpl_type}/{$filename}.xml";
			$files[] = dirname($this->tpl_path)."/default/{$this->tpl_type}/{$lfilename}.xml";
			$files[] = dirname($this->tpl_path)."/default/template/{$filename}.html"; // old CPGTPL
		}
		if (preg_match('#(dragonfly|poodle)/([^/]+)/(.+)#',$filename,$i)) {
			$files[] = "includes/{$i[1]}/{$i[2]}/tpl/{$this->tpl_type}/{$i[3]}.xml";
		}
		else if (strpos($filename,'/')) {
			$m = explode('/',$filename,2);
			$files[] = "modules/{$m[0]}/tpl/{$this->tpl_type}/{$m[1]}.xml";
			$files[] = "modules/{$m[0]}/template/{$m[1]}.html"; // old CPGTPL
		}
		foreach ($files as $file) {
			if ($file = \Poodle::getFile($file)) {
/*
				if (\Poodle::$DEBUG & \Poodle::DBG_TPL_INCLUDED_FILES) {
					$this->_files[] = $file;
				}
*/
				return $file;
			}
		}
		// Phar
		if (strpos($filename,'/')) {
			$file = \Dragonfly::getModulePath($m[0])."tpl/{$this->tpl_type}/{$m[1]}.xml";
			if (is_file($file)) {
				return $file;
			}
			$file = \Dragonfly::getModulePath($m[0])."template/{$m[1]}.html";
			if (is_file($file)) {
				return $file;
			}
		}
		trigger_error("\\Poodle\\TPL::findFile({$this->tpl_path}{$this->tpl_type}/{$filename}.xml): failed to open stream: No such file or directory", E_USER_WARNING);
	}

	private function push_doctype()
	{
		if ('xml' === $this->tpl_type || $this->_xml_parser->isXML()) {
			echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		}
		echo $this->_xml_parser->doctype();
	}

	public static function parseAttributes($name, $attribs)
	{
		$result = '';
		foreach ($attribs as $name => $value) {
			if (true === $value) $value = '';
			if (is_string($value) || is_numeric($value) || is_object($value)) {
				$result .= " {$name}=\"".htmlspecialchars($value)."\"";
			}
		}
		return $result;
	}

	protected static function echo_data($data)
	{
		echo htmlspecialchars($data, ENT_NOQUOTES);
	}

	protected static function resolveURI($uri)
	{
		return is_null($uri) ? null : \Poodle\URI::resolve($uri);
	}

	protected static function get_valid_option($options)
	{
		foreach ($options as $value) { if ($value) { return $value; } }
		return null;
	}

	/**
	 * helper method for self::path()
	 */
	private static function pathError($base, $path, $current)
	{
		$basename = '';
		$file = '';
		$line = 0;
		# self::path gets data in format ($object, "rest/of/the/path"),
		# so name of the object is not really known and something in its place
		# needs to be figured out
		if ($current !== $path) {
			$pathinfo = " (in path '.../{$path}')";
			if (preg_match('#([^/]+)/'.preg_quote($current, '#').'(?:/|$)#', $path, $m)) {
				$basename = "'{$m[1]}'";
			}
		} else $pathinfo = '';

		$bt = debug_backtrace();
		foreach ($bt as $i => $item) {
			if ('eval' === $item['function'] || 'include' === $item['function']) {
				$line = $bt[$i-1]['line'];
			}
			if (isset($item['object'])) {
				$file = $item['object']->tpl_file;
				break;
			}
		}

		if (is_array($base)) {
			$msg = "Array {$basename} doesn't have key named '{$current}'{$pathinfo}";
		} else
		if (is_object($base)) {
			$msg = get_class($base)." object {$basename} doesn't have method/property named '{$current}'{$pathinfo}";
		}
		else {
			$msg = trim("Attempt to read property '{$current}'{$pathinfo} from ".gettype($base)." value {$basename}");
		}
		\Poodle\Debugger::error(E_USER_NOTICE, $msg, $file, $line);
	}

	/**
	 * Resolve TALES path starting from the first path element.
	 * The TALES path : object/method1/10/method2
	 * will call : self::path($ctx->object, 'method1/10/method2')
	 *
	 * @param mixed  $base        first element of the path ($ctx)
	 * @param string $path        rest of the path
	 * @param bool   $check_only  when true, just return true/false if path exists
	 *
	 * @return mixed
	 */
	private static function path($base, $path, $check_only = false)
	{
		if (null === $base) {
			return null;
			self::pathError($base, $path, $path);
		}

		$segments = explode('/', $path);
		$last = count($segments) - 1;
		foreach ($segments as $i => $current) {
			if (is_object($base)) {
				# look for method. Both method_exists and is_callable are required because of __call() and protected methods
				if (method_exists($base, $current) && is_callable(array($base, $current))) {
					if ($check_only && $last === $i) { return true; }
					$base = $base->$current();
					continue;
				}

				# look for property
				if (property_exists($base, $current)) {
					$base = $base->$current;
					continue;
				}

				if ($base instanceof \ArrayAccess && $base->offsetExists($current)) {
					$base = $base->offsetGet($current);
					continue;
				}

				if ($base instanceof \Countable && ('length' === $current || 'size' === $current)) {
					$base = count($base);
					continue;
				}

				# look for isset (priority over __get)
				if (method_exists($base, '__isset')) {
					if ($base->__isset($current)) {
						$base = $base->$current;
						continue;
					}
					if ($check_only) {
						return false;
					}
				}
				# ask __get and discard if it returns null
				if (method_exists($base, '__get')) {
					$tmp = $base->$current;
					if (null !== $tmp) {
						$base = $tmp;
						continue;
					}
				}
/* Disabled, disputable if this should be allowed or not
				# magic method call
				if (method_exists($base, '__call')) {
					if ($check_only && $last === $i) { return true; }
					try
					{
						$base = $base->__call($current, array());
						continue;
					}
					catch(\Exception $e){}
				}
*/
			} else

			if (is_array($base)) {
				# key or index
				if (array_key_exists($current, $base)) {
					$base = $base[$current];
					continue;
				}
			} else

			if (is_string($base)) {
				# access char at index
				if (is_numeric($current)) {
					$base = $base[$current];
					continue;
				}
			}

			# if this point is reached, then the part cannot be resolved
			if ($check_only) {
				return false;
			}
			self::pathError($base, $path, $current);
			return null;
		}

		return $check_only ? true : $base;
	}

	private static function path_exists($base, $path)
	{
		return static::path($base, $path, true);
	}

	public function setTPLName($name)
	{
		if (preg_match('#^[a-z0-9_]+$#i', $name) && is_dir('themes/'.$name)) {
			$this->tpl_name = $name;
			$this->tpl_path = "themes/{$name}/";
		}
	}

	public function isTALThemeFile($filename)
	{
		return $this->themeTALFileExists($filename) || !$this->themeV9FileExists($filename);
	}

	public function themeTALFileExists($filename)
	{
		return !!\Poodle::getFile("{$this->tpl_path}{$this->tpl_type}/{$filename}.xml");
	}

	public function themeV9FileExists($filename)
	{
		return !!\Poodle::getFile("{$this->tpl_path}template/{$filename}.html");
	}

	// Start old CPGTPL stuff
	protected $cpgtpl_filenames = array();
	public function assign_block_vars($k, array $data) {
		if (strpos($k, '.')) {
			// Nested block.
			$blocks = explode('.', $k);
			$k = $blocks[0];
			if (!isset($this->$k)) { return false; }
			$top = $this->$k;
			$arr = &$top[count($this->$k)-1];
			$blockcount = count($blocks)-1;
			for ($i = 1; $i < $blockcount; ++$i)  {
				if (!isset($arr[$blocks[$i]])) { return false; }
				$arr = &$arr[$blocks[$i]];
				$arr = &$arr[count($arr) - 1];
			}
			// Now we add the block that we're actually assigning to.
			// We're adding a new iteration to this block with the given
			// variable assignments.
			$arr[$blocks[$blockcount]][] = $data;
			$this->$k = $top;
		} else {
			// Top-level block.
			if (!isset($this->$k)) { $this->$k = array(); }
			array_push($this->$k, $data);
		}
		return true;
	}
	public function assign_var($k, $v)
	{
		trigger_deprecated("Use OUT->{$k} = value");
		$this->$k = $v;
	}
	public function assign_vars(array $data) { foreach ($data as $k=>$v) { $this->$k = $v; } }
	public function assign_var_from_handle($k, $handle) {
		if (isset($this->cpgtpl_filenames[$handle])) { $handle = $this->cpgtpl_filenames[$handle]; }
		trigger_deprecated("Use OUT->{$k} = OUT->toString({$handle})");
		$this->$k = $this->toString($handle);
	}
	public function set_filenames($filename_array)
	{
		if (!is_array($filename_array)) { return false; }
		foreach ($filename_array as $handle => $filename) { $this->set_handle($handle, $filename); }
		return true;
	}
	public function set_handle($handle, $filename)
	{
		if (empty($handle)) { trigger_error('template error - No handlename specified', E_USER_ERROR); }
		if (empty($filename)) { trigger_error("template error - Empty filename specified for $handle", E_USER_ERROR); }
		$this->cpgtpl_filenames[$handle] = preg_replace('#\\.[a-z]+$#D','',$filename);
	}
	public function destroy() {}
	public function to_string($handle)
	{
		if (isset($this->cpgtpl_filenames[$handle])) { $handle = $this->cpgtpl_filenames[$handle]; }
		trigger_deprecated("Use OUT->toString({$handle})");
		return $this->toString($handle);
	}
	public function unset_block() {}
	public static function getThemes()
	{
		$themes = array();
		foreach (glob('themes/*/theme.php') as $theme) {
			$theme = basename(dirname($theme));
			if ('admin' !== $theme) {
				$themes[] = $theme;
			}
		}
		natcasesort($themes);
		return $themes;
	}
	// End old CPGTPL stuff
}

// Start old CPGTPL stuff
class_alias('Poodle\\TPL', 'cpg_template');
// End old CPGTPL stuff
