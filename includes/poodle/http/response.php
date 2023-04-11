<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP;

class Response
{
	public
		$request_uri, # The URI that was passed to the fetcher
		$final_uri;   # The result of following redirects from the request_uri
	protected
		$status,      # The HTTP status code returned from the final_uri
		$headers,     # The headers returned from the final_uri
		$body;        # The body returned from the final_uri

	function __construct($request_uri, $final_uri = null, $status = null, $headers = null, $body = null)
	{
		$this->request_uri = $request_uri;
		$this->final_uri   = $final_uri;
		$this->status      = (int)$status;
		$this->headers     = is_array($headers) ? $headers : array();
		if (function_exists('gzinflate') && isset($this->headers['content-encoding'])
		 && (false !== stripos($this->headers['content-encoding'], 'gzip'))) {
			$this->body = gzinflate(substr($body,10,-4));
		} else {
			$this->body = $body;
		}
	}

	function __get($k)
	{
		return property_exists($this, $k) ? $this->$k : null;
	}

	public function getHeader($names)
	{
		$names = is_array($names) ? $names : array($names);
		foreach ($names as $n) {
			$n = strtolower($n);
			if (isset($this->headers[$n])) {
				return $this->headers[$n];
			}
		}
		return null;
	}
}
