<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Output;

//trait \Dragonfly\Output\Tools
abstract class Tools
{

	abstract public static function minify($str);

	// child requires $langPattern $langReplace $files $mtime
	final protected static function getLangDefs($item, $lang)
	{
		$item = preg_replace(static::$langPattern, static::$langReplace, $item);
		if (false !== strpos($item, ':')) return;

		if (preg_match('#^modules/([a-z0-9_]+)/(.+)$#Di', $item, $m)) {
			$item = \Dragonfly::getModulePath($m[1]).$m[2];
		} else {
			$item = BASEDIR.$file;
		}

		$file = str_replace('!lang!', $lang, str_replace('!langcode!', $lang, $item));

		# en     is_file()  200:1  404:1
		# it     is_file()  200:2  404:2
		# en-au  is_file()  200:2  404:2
		# it-ch  is_file()  200:2  404:3

		# en-au, it-ch, it
		if ('en' !== $lang)
		{
			$pos = strpos($lang, '-');
			# en-au, it-ch
			if (3 < strlen($lang) && !is_file($file)) {
				# en-au:en, it-ch:it
				$lang = substr($lang,0,-3);
				$file = str_replace('!lang!', $lang, str_replace('!langcode!', $lang, $item));
			}
			# it, es
			if (!$lang || (!$pos && 'en' !== $lang && !is_file($file))) {
				$file = str_replace('!lang!', 'en', str_replace('!langcode!', 'en', $item));
			}
		}

		if (is_file($file)) {
			static::$files[] = $file;
			static::$mtime = max(static::$mtime, filemtime($file));
		}
	}

	// child requires $files $mtime minify()
	final public static function flushUsingCache($type)
	{
		if (!isset(\Dragonfly\Net\Http::$contentType[$type])) return;

		$ETag = md5(implode(';', static::$files).static::$mtime) . (DF_MODE_DEVELOPER ? '-dev' : '');
		$gzip = GZIP_OUT ? '.gz' : '';
		$cachedFile = CACHE_PATH ."{$type}/{$ETag}";
		$life_time = (DF_MODE_DEVELOPER ? 0 : DF_HTTP_CACHE_EXPIRE);

		if (1.1 > \Dragonfly\Net\Http::$protocolVersion) {
			header('Cache-Control: public, s-maxage=' .$life_time);
		} else {
			header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $life_time));
		}
		header(\Dragonfly\Net\Http::$contentType[$type]);
		if (!DF_MODE_DEVELOPER && is_file($cachedFile .$gzip) && filemtime($cachedFile .$gzip) > static::$mtime)
		{
			if ($gzip) {
				header('Vary: Accept-Encoding');
				header('Content-Encoding: gzip');
			}
			if ($status = \Dragonfly\Net\Http::entityCache($ETag, static::$mtime)) {
				\Dragonfly\Net\Http::headersFlush($status);
			} else if ('GET' === $_SERVER['REQUEST_METHOD']) {
				header('Content-Length:' .filesize($cachedFile .$gzip));
				readfile($cachedFile .$gzip);
			}
			exit;
		}

		$buffer = '';
		foreach (static::$files as $file) {
			$buffer .= static::processContent(file_get_contents($file));
		}
		if (in_array('includes/javascript/poodle.js', static::$files)) {
			$buffer = preg_replace('/P.PostMax[\\s=][^;]+;/s', 'P.PostMax='.\Poodle\Input\POST::max_size().';', $buffer);
			$buffer = preg_replace('/P.PostMaxFiles[\\s=][^;]+;/s', 'P.PostMaxFiles='.\Poodle\Input\FILES::max_uploads().';', $buffer);
			$buffer = preg_replace('/P.PostMaxFilesize[\\s=][^;]+;/s', 'P.PostMaxFilesize='.\Poodle\Input\FILES::max_filesize().';', $buffer);
		}
		if (!DF_MODE_DEVELOPER) {
			$buffer = static::minify($buffer);
		}
		$caching = is_writable(CACHE_PATH.$type) || (is_writable(CACHE_PATH) && mkdir(CACHE_PATH.$type, 0777));
		if ($caching) {
			if (GZIPSUPPORT && $gz = gzopen($cachedFile .'.gz', 'w9'))
			{
				gzwrite($gz, $buffer);
				gzclose($gz);
			}
			file_put_contents($cachedFile, $buffer);
			chmod($cachedFile, 0666);
		}
		if (!DF_MODE_DEVELOPER && (is_file($cachedFile .$gzip) || !$gzip)) {
			if ($gzip) header('Content-Encoding: gzip');
			header('Content-Length:' .filesize($cachedFile .$gzip));
			if ('GET' === $_SERVER['REQUEST_METHOD']) exit(readfile($cachedFile .$gzip));
		} else {
			ob_start('ob_gzhandler');
			if ('GET' === $_SERVER['REQUEST_METHOD']) exit($buffer);
		}
		exit;
	}

	protected static function processContent($buffer)
	{
		return $buffer;
	}

	// child requires $toTplPattern $toClientPattern $theme
	final protected static function filter($str, $mode)
	{
		switch ($mode) {
			case 'toTpl':
				$patterns = static::$toTplPattern;
			break;
			case 'toClient':
				$patterns = static::$toClientPattern;
			break;
			default: return;
		}
		foreach ($patterns as $pattern) {
			if (1 === preg_match($pattern, $str, $match)) {
				if (!static::$theme) static::$theme = $match[1];
				return true;
			}
		}
		return false;
	}

	// child requires $toClientPattern $toClientReplace $files $mtime
	protected static function processRequest($type)
	{
		if (!isset(\Dragonfly\Net\Http::$contentType[$type])) return;
		if (!preg_match('#^[a-z0-9_\-;:\?=]+$#iD', $_GET[$type])) return false;

		$items = explode(';', $_GET[$type]);
		foreach ($items as $item)
		{
			if ($lang = strstr($item, '?'))
			{
				$item = str_replace($lang, '', $item);
				$lang = str_replace('?l=', '', $lang);
			}
			if (self::filter($item, 'toClient'))
			{
				$file = preg_replace(static::$toClientPattern, static::$toClientReplace, $item);
				if (preg_match('#^modules/([a-z0-9_]+)/(.+)$#Di', $file, $m)) {
					$file = \Dragonfly::getModulePath($m[1]).$m[2];
				} else {
					$file = BASEDIR.$file;
				}
				if (is_file($file)) {
					static::$files[] = $file;
					static::$mtime = max(static::$mtime, filemtime($file));
					if ($lang)
					{
						self::getLangDefs($item, $lang);
					}
				} else {
					trigger_error($item .' could not be found', E_USER_WARNING);
				}
			}
		}
		static::$files = array_values(array_unique(static::$files));
		return !empty(static::$files);
	}

	private static
		$sendFile = false,
		$fp,
		$fp_fltr,
		$fp_hash,
		$fp_size;

	final public static function attachFile($filename, $type, $gzip)
	{
		if (!isset(\Dragonfly\Net\Http::$contentType[$type])) {
			trigger_error('Could not attach unkown Content-Type', E_USER_WARNING);
			return;
		}
		\Poodle\PHP\INI::set('memory_limit', -1);
		\Dragonfly::ob_clean();

		if ($gzip && static::$fp = fopen('php://output', 'wb'))
		{
			header(\Dragonfly\Net\Http::$contentType['gzip']."; name=\"{$filename}.gz\"");
			header("Content-disposition: attachment; filename={$filename}.gz");

			static::$fp_size = 0;
			// write gzip header, http://www.faqs.org/rfcs/rfc1952.html
			fwrite(static::$fp, "\x1F\x8B\x08\x00".pack('V',time())."\x00\xFF", 10);
			flush();
			// add the deflate filter using default compression level
			static::$fp_fltr = stream_filter_append(static::$fp, 'zlib.deflate', STREAM_FILTER_WRITE, -1);
			// set up the CRC32 hashing context
			static::$fp_hash = hash_init('crc32b');
		}
		else
		{
			header(\Dragonfly\Net\Http::$contentType[$type]."; name=\"{$filename}\"");
			header("Content-disposition: attachment; filename={$filename}");
		}

		static::$sendFile = true;
	}

	final public static function sendFile($str, $end=false)
	{
		if (!static::$sendFile) { throw new \Exception('\Dragonfly\Output\Tools::attachFile not open'); }

		if (static::$fp)
		{
			static::$fp_size += strlen($str);
			hash_update(static::$fp_hash, $str);
			fwrite(static::$fp, $str, strlen($str));

			if ($end) {
				// remove the deflate filter
				stream_filter_remove(static::$fp_fltr);

				// write the CRC32 value
				// hash_final is a string, not an integer
				$crc = hash_final(static::$fp_hash, true);
				// need to reverse the hash_final string so it's little endian
				fwrite(static::$fp, $crc[3].$crc[2].$crc[1].$crc[0], 4);
				// write the original uncompressed file size
				fwrite(static::$fp, pack("V", static::$fp_size), 4);

				fclose(static::$fp);
				static::$fp = null;
			}
		}
		else
		{
			echo $str;
		}

		flush();

		static::$sendFile = !$end;
	}

	// Used by JS and CSS @import handling
	protected static function assoc_array_push_before(array &$array, $ref_key, $key)
	{
		$new = array();
		foreach ($array as $k => $v) {
			if ($ref_key===$k) {
				$new[$key] = isset($array[$key])?$array[$key]:null;
			}
			$new[$k] = $v;
		}
		$array = $new;
	}
}
