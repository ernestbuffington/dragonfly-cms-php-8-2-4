<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP\Request;

class Socket extends \Poodle\HTTP\Request
{
	public function supportsSSL()
	{
		return function_exists('openssl_open');
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
			if (!$this->canFetchURI($url)) {
				return null;
			}

			if (!$this->allowedURI($url)) {
				trigger_error("Fetching URL not allowed: {$url}");
				return null;
			}

			$parts = parse_url($url);

			// Set a default port.
			$port = 0;
			if (array_key_exists('port', $parts)) {
				$port = $parts['port'];
			} else if ('http' === $parts['scheme'] || 'https' === $parts['scheme']) {
				$parts['port'] = self::getSchemePort($parts['scheme']);
			} else {
				return null;
			}

			if (!array_key_exists('path', $parts)) {
				$parts['path'] = '/';
			}

			$headers = array(
				"{$method} {$parts['path']}".(isset($parts['query']) ? "?{$parts['query']}" : '')." HTTP/1.1",
				"Host: ".$parts['host'].($port ? ":".$port : ''),
				"User-Agent: {$this->user_agent}",
				'Connection: Close',
			);
			if ($extra_headers) {
				$headers = array_merge($headers, $extra_headers);
			}
			$headers = implode("\r\n", $headers);
			if (!is_null($body)) {
				if (!stripos($headers,'Content-Type')) {
					$headers .= "\r\nContent-Type: application/x-www-form-urlencoded";
				}
				$headers .= "\r\nContent-Length: ".strlen($body);
			}

			$context = stream_context_create();
			if ('https' === $parts['scheme']) {
				$parts['host'] = 'ssl://'.$parts['host'];
				stream_context_set_option($context, 'ssl', 'verify_host', true);
				if ($this->verify_peer || $this->ca_bundle) {
					stream_context_set_option($context, 'ssl', 'verify_peer', true);
					if ($this->ca_bundle) {
						if (is_dir($this->ca_bundle) || (is_link($this->ca_bundle) && is_dir(readlink($this->ca_bundle)))) {
							$context['ssl']['capath'] = $this->ca_bundle;
						} else {
							$context['ssl']['cafile'] = $this->ca_bundle;
						}
					}
				} else {
					stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
				}
			} else {
				$parts['host'] = 'tcp://'.$parts['host'];
			}

			$errno = 0;
			$errstr = '';

			$sock = stream_socket_client("{$parts['host']}:{$parts['port']}", $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
			if (false === $sock) {
				trigger_error($errstr, E_USER_WARNING);
				return false;
			}

			stream_set_timeout($sock, $this->timeout);

			fwrite($sock, $headers . "\r\n\r\n");
			if (!is_null($body)) {
				fwrite($sock, $body);
			}

			# Read all headers
			$chunked = false;
			$response_headers = array();
			$data = rtrim(fgets($sock, 1024)); # read line
			while (strlen($data)) {
				$response_headers[] = $data;
				$chunked |= preg_match('#Transfer-Encoding:.*chunked#i',$data);
				$data = rtrim(fgets($sock, 1024)); # read next line
			}

			$code = explode(' ', $response_headers[0]);
			$code = (int)$code[1];

			# Read body
			$body = '';
			if (is_resource($this->stream)) {
				while (!feof($sock)) {
					if ($chunked) {
						$chunk = hexdec(trim(fgets($sock, 8)));
						if (!$chunk) { break; }
						while ($chunk > 0) {
							$tmp = fread($sock, $chunk);
							fwrite($this->stream, $tmp);
							$chunk -= strlen($tmp);
						}
					} else {
						fwrite($this->stream, fread($sock, 1024));
					}
				}
			} else {
				$max_bytes = $this->max_response_kb * 1024;
				while (!feof($sock) && strlen($body) < $max_bytes) {
					if ($chunked) {
						$chunk = hexdec(trim(fgets($sock, 8)));
						if (!$chunk) { break; }
						while ($chunk > 0) {
							$tmp = fread($sock, $chunk);
							$body .= $tmp;
							$chunk -= strlen($tmp);
						}
					} else {
						$body .= fread($sock, 1024);
					}
				}
			}

			fclose($sock);

			// http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3
			// In response to a request other than GET or HEAD, the user agent MUST NOT
			// automatically redirect the request unless it can be confirmed by the user
			if (is_null($body) && in_array($code, array(301, 302, 307))) {
				$url = self::findRedirect($response_headers, $url);
			} else {
				return new $this->result_class($request_url, $url, $code, self::parseHeaders($response_headers), $body);
			}

		} while ($etime-time() > 0);

		return null;
	}

}
