<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP;

class URLInfo implements \ArrayAccess
{
	public
		$size = 0,
		$type = null,
		$date = 0,
		$data = '',
		$animation = false,
		$modified  = true;

	public static function get($url, $detectAnim=false, $getdata=false, $lastmodified=0)
	{
		$headers = array();
		if ($lastmodified > 0) {
			$headers[] = 'If-Modified-Since: '.date('D, d M Y H:i:s \G\M\T', $lastmodified);
		}
		if (extension_loaded('zlib')) {
			$headers[] = 'Accept-Encoding: gzip;q=0.9';
		}

		$file = new static();

		$request = \Poodle\HTTP\Request::factory();
		$request->user_agent = \Poodle\PHP\INI::get('user_agent').' RemoteFileInfo/1.0';
		$request->max_response_kb = ($getdata || $detectAnim) ? 1024 : 0;
		if (!($result = $request->get($url, $headers))) {
			return false;
		}

		if ($lastmodified > 0 && 304 == $result->status) {
			# file isn't modified since $lastmodified
			$file->modified = false;
			return $file;
		}

		if (200 != $result->status) {
			trigger_error("{$result->status} {$result->request_uri}:\n{$result->body}", E_USER_WARNING);
			return false;
		}

		if (isset($result->headers['content-length'])) {
			$file->size = (int)$result->headers['content-length'];
		}
		if (isset($result->headers['content-type'])) {
			$file->type = $result->headers['content-type'];
			$file->utf8 = (false !== stripos($file->type, 'charset=utf-8'));
		}
		if (isset($result->headers['last-modified'])) {
			$file->date = new \DateTime($result->headers['last-modified']);
		}

		if ($getdata) { $file->data = $result->body; }
		if ($detectAnim && false !== strpos($file->type, 'image/')) {
			// split GIF frames, 1 = header, 2 = first/main frame, 3 = second frame
			$file->animation = (count(preg_split('/\x00[\x00-\xFF]\x00\x2C/', $result->body)) > 2);
		}

		return $file;
	}

	public function offsetSet($k, $v) {}
	public function offsetExists($k)  { return property_exists($this, $k); }
	public function offsetUnset($k)   {}
	public function offsetGet($k)     { return property_exists($this, $k) ? $this->$k : null; }

}
