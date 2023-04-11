<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	(binary) casting and b prefix forward support was added in PHP 5.2.1
*/

namespace Poodle\Stream\Wrapper;

class HTTP
{
	protected
		$fullurl,
		$p_url,
		$conn_id,
		$flushed,
		$mode = 4, # read only
		$defmode,
		$redirects = 0,
		$binary,
		$options,
		$stat = array(
			'dev' => 0,
			'ino' => 0,
			'mode' => 0,
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => -1,
			'size' => 0,
			'atime' => 0,
			'mtime' => 0,
			'ctime' => 0,
			'blksize' => -1,
			'blocks' => 0
		);

	protected function error($msg='not connected')
	{
		if ($this->options & STREAM_REPORT_ERRORS) { trigger_error($msg, E_USER_WARNING); }
		return false;
	}

	public function stream_open($path, $mode, $options, $opened_path)
	{
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		switch ($bt[1]['function'])
		{
		case 'include':
		case 'include_once':
		case 'require':
		case 'require_once':
			trigger_error($bt[1]['function'].'() URL file-access is disabled', E_USER_WARNING);
			return false;
		}
		$this->fullurl = $path;
		$this->options = $options;
		$this->defmode = $mode;

		$url = parse_url($path);
		if (empty($url['host'])) { return $this->error('missing host name'); }
		if (empty($url['port'])) { $url['port'] = \Poodle\HTTP\Request::getSchemePort($url['scheme']); }
		if ('https' === $url['scheme']) { $url['host'] = 'ssl://'.$url['host']; }
		$this->conn_id = fsockopen($url['host'], (int)$url['port'], $errno, $errstr, 2);
		if (!$this->conn_id) { return $this->error($errstr); }
		if (empty($url['path'])) { $url['path'] = '/'; }
		$this->p_url = $url;
		$this->flushed = false;
		if ('r' !== $mode[0] || (strpos($mode, '+') !== false)) { $this->mode += 2; }
		$this->binary = (strpos($mode, 'b') !== false);
		$c = $this->context();
		if (!isset($c['method']))        { stream_context_set_option($this->context, 'http', 'method', 'GET'); }
		if (!isset($c['header']))        { stream_context_set_option($this->context, 'http', 'header', ''); }
		if (!isset($c['user_agent']))    { stream_context_set_option($this->context, 'http', 'user_agent', \Poodle\PHP\INI::get('user_agent')); }
		if (!isset($c['content']))       { stream_context_set_option($this->context, 'http', 'content', ''); }
		if (!isset($c['max_redirects'])) { stream_context_set_option($this->context, 'http', 'max_redirects', 5); }
		return true;
	}
	public function stream_close()
	{
		if ($this->conn_id)
		{
			fclose($this->conn_id);
			$this->conn_id = null;
		}
	}
	public function stream_read($bytes)
	{
		if (!$this->conn_id) { return false; }
		if (!$this->flushed && !$this->stream_flush()) { return false; }
		if (feof($this->conn_id)) { return ''; }
		$bytes = max(1,$bytes);
		return $this->binary ? fread($this->conn_id, $bytes) : fgets($this->conn_id, $bytes);
	}
	public function stream_write($data)
	{
		if (!$this->conn_id)  { return false; }
		if (!$this->mode & 2) { return $this->error('Stream is in read-only mode'); }
		$c = $this->context();
		stream_context_set_option($this->context, 'http', 'method', (('x' === $this->defmode[0]) ? 'PUT' : 'POST'));
		if (stream_context_set_option($this->context, 'http', 'content', $c['content'].$data)) { return strlen($data); }
		return 0;
	}
	public function stream_eof()
	{
		if (!$this->conn_id) { return true; }
		if (!$this->flushed) { return false; }
		return feof($this->conn_id);
	}
	public function stream_seek($offset, $whence)
	{
		trigger_error("stream_seek($offset, $whence) not yet supported");
		return false;
	}
	public function stream_tell()
	{
		trigger_error("stream_tell() not yet supported");
		return 0;
	}
	public function stream_flush()
	{
		if ($this->flushed || !$this->conn_id) { return false; }
		$c = $this->context();
		# send the headers
		$this->flushed = true;
		$RequestHeaders = array(
			$c['method'].' '.$this->p_url['path'].(empty($this->p_url['query']) ? '' : '?'.$this->p_url['query']).' HTTP/1.1',
			'HOST: '.$this->p_url['host'],
			'User-Agent: '.$c['user_agent'].' StreamReader'
		);
		if (!empty($c['header'])) { $RequestHeaders[] = $c['header']; }
		if (!empty($c['content'])) {
			# http://utoronto.ca/webdocs/HTMLdocs/Book/Book-3ed/appb/mimetype.html
			if ('PUT' === $c['method']) {
				$RequestHeaders[] = 'Content-Type: '.($this->binary ? 'application/octet-stream' : 'text/plain');
			} else {
				$RequestHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
			}
			$RequestHeaders[] = 'Content-Length: '.strlen($c['content']);
		}
		$RequestHeaders[] = 'Connection: close';
		if (fwrite($this->conn_id, implode("\r\n", $RequestHeaders)."\r\n\r\n") === false) { return false; }
		# send the post data
		if (!empty($c['content']) && fwrite($this->conn_id, $c['content']) === false) { return false; }
		# Get response headers
		global $http_response_header;
		$http_response_header = array(fgets($this->conn_id, 300));
		# Check Status Code w3.org/Protocols/rfc2616/rfc2616-sec10.html
		$data = rtrim($http_response_header[0]);
		preg_match('#.* ([0-9]+) (.*)#i', $data, $head);
		# 301 Moved Permanently, 302 Found, 303 See Other, 307 Temporary Redirect
		if (($head[1] >= 301 && $head[1] <= 303) || $head[1] == 307) {
			$data = rtrim(fgets($this->conn_id, 300)); # read next line
			while (!empty($data)) {
				if (stripos($data, 'Location: ') !== false) {
					$new_location = trim(str_ireplace('Location: ', '', $data));
					break;
				}
				$data = rtrim(fgets($this->conn_id, 300)); # read next line
			}
			trigger_error($this->fullurl.' '.$head[2].': '.$new_location, E_USER_NOTICE);
			$this->stream_close();
			return ($c['max_redirects'] > $this->redirects++ && $this->stream_open($new_location, $this->defmode, $this->options, null) && $this->stream_flush());
		}
		# Read all headers
		$data = rtrim(fgets($this->conn_id, 1024)); # read line
		while (!empty($data)) {
			$http_response_header[] = $data."\r\n";
			if (stripos($data, 'Content-Length: ') !== false)     { $this->stat['size']  = trim(str_ireplace('Content-Length: ', '', $data)); }
			else if (stripos($data, 'Date: ') !== false)          { $this->stat['atime'] = strtotime(str_ireplace('Date: ', '', $data)); }
			else if (stripos($data, 'Last-Modified: ') !== false) { $this->stat['mtime'] = strtotime(str_ireplace('Last-Modified: ', '', $data)); }
			$data = rtrim(fgets($this->conn_id, 1024)); # read next line
		}
		# Client/Server error
		if ($head[1] >= 400) {
			trigger_error($this->fullurl.' '.$head[2], E_USER_WARNING);
			return false;
		}
		# file modified?
		if ($head[1] == 304) {
			trigger_error($this->fullurl.' '.$head[2], E_USER_NOTICE);
			return false;
		}
		return true;
	}
	public function stream_stat()
	{
		$this->stream_flush();
		return $this->stat;
	}
	public function dir_opendir($path, $options) { return false; }
	public function dir_readdir() { return ''; }
	public function dir_rewinddir() { return ''; }
	public function dir_closedir() { return; }
	public function url_stat($path, $flags) { return array(); }

	protected function context()
	{
		if (!$this->context) { $this->context = stream_context_create(); }
		$c = stream_context_get_options($this->context);
		return (isset($c['http']) ? $c['http'] : array());
	}
}
