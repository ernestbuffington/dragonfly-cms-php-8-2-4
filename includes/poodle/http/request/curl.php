<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP\Request;

class CURL extends \Poodle\HTTP\Request
{
	function __construct($result_class=null)
	{
		parent::__construct($result_class);
		$this->reset();
	}

	public function supportsSSL()
	{
		$v = curl_version();
		if (is_array($v)) {
			return in_array('https', $v['protocols']);
		}
		return is_string($v) ? !!preg_match('/OpenSSL/i', $v) : false;
	}

	public function doRequest($method, $request_url, $body = null, array $extra_headers = array())
	{
		$method = strtoupper($method);
		$url    = $request_url;
		$etime  = time() + $this->timeout;
		if (is_array($body)) { $body = http_build_query($body, '', '&'); }
		if ($body && 'GET' === $method) {
			$url .= (strpos($url, '?')?'&':'?').$body;
			$body = null;
		}
		do
		{
			$this->reset();

			if (!$this->canFetchURI($url)) {
				return null;
			}

			if (!$this->allowedURI($url)) {
				trigger_error("Fetching URL not allowed: $url");
				return null;
			}

			$c = curl_init();
			if (false === $c) {
				trigger_error("Could not initialize CURL for URL '$url'");
				return null;
			}

			$cv = curl_version();
			// php.net/curl_setopt
			curl_setopt_array($c, array(
				CURLOPT_USERAGENT      => $this->user_agent.' '.(is_array($cv) ? 'curl/'.$cv['version'] : $cv),
				CURLOPT_CONNECTTIMEOUT => $this->timeout,
				CURLOPT_TIMEOUT        => $this->timeout,
				CURLOPT_URL            => $url,
				CURLOPT_HEADERFUNCTION => array($this, 'fetchHeader'),
				CURLOPT_WRITEFUNCTION  => array($this, is_resource($this->stream) ? 'streamData' : 'fetchData'),
				CURLOPT_SSL_VERIFYPEER => ($this->verify_peer || $this->ca_bundle),
			));
//			curl_setopt($c, CURLOPT_ENCODING , 'gzip');
			if (defined('CURLOPT_NOSIGNAL')) {
				curl_setopt($c, CURLOPT_NOSIGNAL, true);
			}
			if ($this->ca_bundle) {
				curl_setopt($c, CURLOPT_CAINFO, $this->ca_bundle);
			}
			if ($extra_headers) {
				curl_setopt($c, CURLOPT_HTTPHEADER, $extra_headers);
			}
			if ('HEAD' === $method) {
				curl_setopt($c, CURLOPT_NOBODY, true);
			} else if ('GET' !== $method) {
				if ('POST' === $method) {
					curl_setopt($c, CURLOPT_POST, true);
				} else {
					curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
				}
				if (!is_null($body)) {
					curl_setopt($c, CURLOPT_POSTFIELDS, $body);
				}
			}

			curl_exec($c);

			$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
			if (!$code) {
				trigger_error("Error ".curl_errno($c).": ".curl_error($c)." for {$url}");
				curl_close($c);
				return null;
			}
			curl_close($c);

			// http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3
			// In response to a request other than GET or HEAD, the user agent MUST NOT
			// automatically redirect the request unless it can be confirmed by the user
			if (is_null($body) && in_array($code, array(301, 302, 303, 307))) {
				$url = self::findRedirect($this->headers, $url);
			} else {
				$result = new $this->result_class($request_url, $url, $code, self::parseHeaders($this->headers), $this->data);
				$this->reset();
				return $result;
			}

		} while ($etime-time() > 0);

		return null;
	}

	protected function reset()
	{
		$this->headers = array();
		$this->data = '';
	}

	protected function fetchHeader($ch, $header)
	{
		array_push($this->headers, rtrim($header));
		return strlen($header);
	}

	protected function fetchData($ch, $data)
	{
		$data = substr($data, 0, min(strlen($data), ($this->max_response_kb*1024) - strlen($this->data)));
		$this->data .= $data;
		return strlen($data);
	}

	protected function streamData($ch, $data)
	{
		return fwrite($this->stream, $data);
	}

}
