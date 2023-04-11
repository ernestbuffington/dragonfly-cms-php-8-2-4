<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP;

abstract class Request
{
	public
		$timeout = 5, // timeout in seconds.
		$max_response_kb = 1024,
		$user_agent,
		$verify_peer = false;
	protected
		$stream = null,
		$ca_bundle = null,
		$result_class = 'Poodle\\HTTP\\Response';

	public static $IGNORE_CURL;

	protected static $scheme_ports = array(
		'ftp'   => 21,
		'ftps'  => 990,
		'http'  => 80,
		'https' => 443,
		// WebSocket
		'ws'    => 80,
		'wss'   => 443,
	);

	public static function factory($result_class=null, $type='curl')
	{
		if ('curl' === $type && !self::$IGNORE_CURL && function_exists('curl_init')) {
			return new \Poodle\HTTP\Request\CURL($result_class);
		} else {
			return new \Poodle\HTTP\Request\Socket($result_class);
		}
	}

	function __construct($result_class=null)
	{
		$this->setResultClass($result_class);
		$this->user_agent = \Poodle\PHP\INI::get('user_agent').' HTTP_Request/1.0';
	}

	public function streamBodyTo($stream)
	{
		if (is_resource($stream)) {
			$this->stream = $stream;
		} else {
			throw new \Exception('Invalid body target');
		}
	}

	public function setResultClass($class=null)
	{
		if (null === $class) $class = 'Poodle\\HTTP\\Response';
		if (is_string($class)
		 && ('Poodle\\HTTP\\Response' === $class || in_array('Poodle\\HTTP\\Response', class_parents($class))))
		{
			$this->result_class = $class;
			return true;
		}
		throw new \InvalidArgumentException($class.' not a valid Poodle\\HTTP\\Response');
	}

	public function setCABundleFile($file)
	{
		$this->ca_bundle = $file;
	}

	/**
	 * Return whether a URI can be fetched.  Returns false if the URI scheme is not allowed
	 * or is not supported by this fetcher implementation; returns true otherwise.
	 *
	 * @return bool
	 */
	public function canFetchURI($uri)
	{
		if (\Poodle\URI::isHTTPS($uri) && !$this->supportsSSL()) {
			trigger_error('HTTPS URI unsupported fetching '.$uri, E_USER_WARNING);
			return false;
		}
		if (!$this->allowedURI($uri)) {
			trigger_error('URI fetching not allowed for '.$uri, E_USER_WARNING);
			return false;
		}
		return true;
	}

	/**
	 * Return whether a URI should be allowed. Override this method to conform to your local policy.
	 * By default, will attempt to fetch any http or https URI.
	 */
	public function allowedURI($uri)
	{
		return self::URIHasAllowedScheme($uri);
	}

	/**
	 * Does this fetcher implementation (and runtime) support fetching HTTPS URIs?
	 * May inspect the runtime environment.
	 *
	 * @return bool $support True if this fetcher supports HTTPS
	 * fetching; false if not.
	 */
	abstract public function supportsSSL();

	/**
	 * Fetches the specified URI using optional extra headers and returns the server's response.
	 *
	 * @param string $uri The URI to be fetched.
	 * @param array $headers An array of header strings (e.g. "Accept: text/html").
	 * @return mixed $result An array of ($code, $uri, $headers, $body) if the URI could be fetched;
	 * null if the URI does not pass the URIHasAllowedScheme check or if the server's response is malformed.
	 */
	public function head($request_url, array $extra_headers = array())
	{
		$max_response_kb = $this->max_response_kb;
		$this->max_response_kb = 0;
		$result = $this->doRequest('HEAD', $request_url, $extra_headers);
		$this->max_response_kb = $max_response_kb;
		return $result;
	}
	public function get($request_url, array $extra_headers = array())
	{
		return $this->doRequest('GET', $request_url, null, $extra_headers);
	}
	public function post($request_url, $body, array $extra_headers = array())
	{
		return $this->doRequest('POST', $request_url, $body, $extra_headers);
	}

	abstract public function doRequest($method, $request_url, $body = null, array $extra_headers = array());

	public static function URIHasAllowedScheme($uri) { return (bool)preg_match('#^https?://#i', $uri); }

	public static function getSchemePort($scheme)
	{
		return isset(self::$scheme_ports[$scheme]) ? self::$scheme_ports[$scheme] : 0;
	}

	protected static function parseHeaders($headers)
	{
		$name = null;
		$new_headers = array();
		foreach ($headers as $header) {
			if (strpos($header, ':')) {
				list($name, $value) = explode(':', $header, 2);
				$name = strtolower(trim($name));
				# TODO: handle multiple AUTH headers
				$new_headers[$name] = trim($value);
			} else if ($name) {
//				$new_headers[$name] .= trim($header);
			}
		}
		return $new_headers;
	}

	protected static function findRedirect($headers, $url)
	{
		foreach ($headers as $line) {
			if (0 === stripos($line, 'location: ')) {
				$parts = explode(' ', $line, 2);
				$uri = trim($parts[1]);
				if (!preg_match('#^[a-z][a-z0-9\\+\\.\\-]+://[^/]+#i',$uri)) {
					// no host
					preg_match('#^([a-z][a-z0-9\\+\\.\\-]+://[^/]+)(/[^\\?\\#]*)#i',$url,$url);
					if ('/' === $uri[0]) {
						// absolute path
						$uri = $url[1].$uri;
					} else {
						// relative path
						$rpos = strrpos($url[2], '/');
						$uri  = $url[1].substr($url[2], 0, $rpos+1).$uri;
					}
				}
				return $uri;
			}
		}
		return false;
	}
}
