<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Stream;

class File
{
	protected
		$filename = null,
		$mode = null,
		$gzip = array('r' => null, 'w' => null),
		$stream = null;

	function __construct($filename, $mode, $use_include_path = false, $context = null)
	{
		$context = $context ?: stream_context_create();
		$this->stream = fopen($filename, $mode+'b', $use_include_path, $context);
		$this->filename = $filename;
		$this->mode = $mode;
	}

	function __destruct()
	{
		$this->close();
	}

	function __get($k)
	{
		if (property_exists($this, $k)) {
			return $this->$k;
		}
		trigger_error('Unknown property '.get_class($this).'->'.$k);
	}

	public function useGzipCompression()
	{
		if (!$this->gzip['w'] && 'w' === $this->mode[0]) {
			// Write gzip header, see http://www.zlib.org/rfc-gzip.html#member-format
			if (fwrite($this->stream, "\x1F\x8B\x08\x08".pack('V', time())."\0\xFF", 10)) {
				// Write the original file name
				$filename = preg_replace('[^\\x20-\\x7E]', '-', basename($this->filename, '.gz'));
				if (fwrite($this->stream, $filename."\0", strlen($filename)+1)) {
					// Start compression
					$this->gzip['w'] = stream_filter_append($this->stream, 'zlib.deflate', STREAM_FILTER_WRITE, -1);
					return true;
				}
			}
			fseek($this->stream, 0);
		}
		if (!$this->gzip['r'] && 'r' === $this->mode[0]) {
			$this->gzip['r'] = stream_filter_append($this->stream, 'zlib.deflate', STREAM_FILTER_READ, -1);
			return true;
		}
		return false;
	}

	public function close()
	{
		if ($this->stream) {
			if ($this->gzip['w']) {
				stream_filter_remove($this->gzip['w']);
			}
			fclose($this->stream);
			$this->stream = null;
			return true;
		}
	}

	public function eof()
	{
		return feof($this->stream);
	}

	public function flush()
	{
		return fflush($this->stream);
	}

	public function getc()
	{
		return fgetc($this->stream);
	}

	public function getcsv($length = 0, $delimiter = ',', $enclosure = '"', $escape_char = '\\')
	{
		return fgetcsv($this->stream, $length, $delimiter, $enclosure, $escape_char);
	}

	public function gets($length = 0)
	{
		return ($length ? fgets($this->stream, $length) : fgets($this->stream));
	}

	public function lock($operation, &$wouldblock = null)
	{
		return flock($this->stream, $operation, $wouldblock);
	}

	public function passthru()
	{
		return fpassthru($this->stream);
	}

	public function putcsv(array $fields, $delimiter = ',', $enclosure = '"', $escape_char = '\\')
	{
		return fputcsv($this->stream, $fields, $delimiter, $enclosure, $escape_char);
	}

	public function read($length)
	{
		return fread($this->stream, $length);
	}

//	public function fscanf

	public function seek($offset, $whence = SEEK_SET)
	{
		return fseek($this->stream, $offset, $whence);
	}

//	public function fstat

	public function tell()
	{
		return ftell($this->stream);
	}

//	public function ftruncate

	public function write($string, $length = 0)
	{
		$length = strlen($string);
		$written = 0;
		while ($written < $length) {
			$bytes = fwrite($this->stream, $written ? substr($string, $written) : $string);
			if (!$bytes) {
				return $written ?: false;
			}
			$written += $bytes;
		}
		return $written;
	}

	public function rewind()
	{
		return rewind($this->stream);
	}
/*
	public function copy_to_stream($dest, $maxlength = -1, $offset = 0)
	{
		return stream_copy_to_stream($this->stream, $dest, $maxlength, $offset);
	}

	public function set_write_buffer($buffer)
	{
		return stream_set_write_buffer($this->stream, $buffer);
	}
*/
}
