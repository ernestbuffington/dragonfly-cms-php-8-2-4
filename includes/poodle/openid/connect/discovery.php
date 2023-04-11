<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-connect-discovery-1_0.html
*/

namespace Poodle\OpenID\Connect;

class Discovery
{

	protected static
		// OPTIONAL metadata elements with default values
		$def_config = array(
			'response_modes_supported' => array('query', 'fragment'),
			'grant_types_supported' => array('authorization_code', 'implicit'),
			'token_endpoint_auth_methods_supported' => array('client_secret_basic'),
			'claim_types_supported' => array('normal'),
			'claims_parameter_supported' => false,
			'request_parameter_supported' => false,
			'request_uri_parameter_supported' => true,
			'require_request_uri_registration' => false,
		),

		$issuers = array();

	public static function ID($identifier)
	{
		try {
			$at = strpos($identifier, '@');
			if (0 === $at) {
				return false; // XRI
			}
			if ($at && preg_match('#^(?:acct:)?([^/]+@.+)$#Di', $identifier, $m)) {
				$identifier = 'acct://'.$identifier;
			}
			if (!($parts = parse_url($identifier))) {
				return false;
			}

			// 2.2.1. User Input using E-Mail Address Syntax
			// 2.2.4. User Input using "acct" URI Syntax
			if (isset($parts['scheme'], $parts['host'], $parts['user']) && 'acct' === $parts['scheme']) {
				$host = $parts['host'];
				$identifier = str_replace('://', ':', $identifier);

			// 2.2.2. User Input using URL Syntax
			// 2.2.3. User Input using Hostname and Port Syntax
			} else {
				if (empty($parts['scheme']) || 'http' !== substr($parts['scheme'], 0, 4)) {
					$parts['scheme'] = 'https';
				}
				unset($parts['fragment']);
				$identifier = \Poodle\URI::unparse($parts);

				$host = $parts['host'] . (empty($parts['port']) ? '' : ":{$parts['port']}");
				if (isset($parts['path'])) {
					$host .= $parts['path'];
				}
			}

			return static::getProviderIssuer($host, $identifier);

		} catch (\Exception $e) {
			trigger_error(__CLASS__ . ' ' . $e->getMessage(), E_USER_WARNING);
			return false;
		}
		return null;
	}

	// http://openid.net/specs/openid-connect-discovery-1_0.html#IssuerDiscovery
	// Uses WebFinger http://tools.ietf.org/html/rfc7033
	public static function getProviderIssuer($host, $resource, $rel = 'http://openid.net/specs/connect/1.0/issuer')
	{
		$host = rtrim($host,'/');
		$result = $HTTP->get("https://{$host}/.well-known/webfinger?".http_build_query(array('resource' => $resource, 'rel' => $rel)));
		if (200 != $result->status) {
			throw new \Exception("Unable to get WebFinger of {$host}, status: {$result->status}");
		}

		if (!preg_match('#application/(jrd\\+)?json#', $result->getHeader('content-type'))) {
			throw new \Exception("Incorrect content type for WebFinger of {$host}");
		}

		$result = json_decode($result->body, true);
		if (!$result) {
			throw new \Exception("Invalid JSON content for WebFinger of {$host}");
		}
		if (empty($result['links'])) {
			throw new \Exception("No links in WebFinger of {$host}");
		}
		$href = null;
		foreach ($result['links'] as $link) {
			if ($link['rel'] == $rel && !empty($link['href']) && 'https:' === substr($link['href'],0,6)) {
				$href = $temp_link['href'];
			}
		}
		if (!$href) {
			throw new \Exception("No Issuer Link in WebFinger of {$host}");
		}
		return $href;
	}

	// http://openid.net/specs/openid-connect-discovery-1_0.html#ProviderConfig
	public static function getProviderConfiguration($issuer, $implicit_flow = false)
	{
		$HTTP = \Poodle\HTTP\Request::factory();
		try {
			$issuer = rtrim($issuer,'/');
			if (!isset(static::$issuers[$issuer])) {
				$result = $HTTP->get("{$issuer}/.well-known/openid-configuration");
				if (200 != $result->status) {
					throw new \Exception("Unable to get openid-configuration of {$issuer}, status: {$result->status}");
				}
				if (false === strpos($result->getHeader('content-type'), 'application/json')) {
					throw new \Exception("Incorrect content type for openid-configuration of {$issuer}");
				}

				$discovery = json_decode($result->body, true);
				if (!$discovery) {
					throw new \Exception("Invalid JSON content for openid-configuration of {$issuer}");
				}

				// id_token_signing_alg_values_supported is also required but missing at accounts.google.com
				$required = array('issuer', 'authorization_endpoint', 'jwks_uri',
					'response_types_supported', 'subject_types_supported');
				foreach ($required as $key) {
					if (!isset($discovery[$key])) {
						throw new \Exception("Missing '{$key}' in openid-configuration of {$issuer}");
					}
				}
				static::$issuers[$issuer] = $discovery;
			}

			if (!$implicit_flow && !isset(static::$issuers[$issuer]['token_endpoint'])) {
				throw new \Exception("Missing 'token_endpoint' in openid-configuration of {$issuer}");
			}

			return array_merge(static::$def_config, static::$issuers[$issuer]);

		} catch (\Exception $e) {
			trigger_error(__CLASS__ . ' ' . $e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

}
