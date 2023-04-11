<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-authentication-2_0.html#rfc.section.7
*/

namespace Poodle\OpenID;

class Discover
{

	public static function ID($identifier, \Poodle\HTTP\Request $fetcher)
	{
		// 7.2 step 1:
		$identifier = preg_replace('#^xri:/*#i', '', $identifier);
		// 7.2 step 2:
		if ($identifier && in_array($identifier[0], array('!', '=', '@', '+', '$', '('))) {
			// 7.3 step 1
			// XRI is a dead service
			$services = array();
		} else {
			// 7.2 step 3 & 4 + 7.3 step 2 & 3
			$services = self::Yadis($identifier, $fetcher);
		}

		if ($services && !$fetcher->supportsSSL()) {
			$services = array_values(array_filter($services, function($s){return !\Poodle\URI::isHTTPS($s->server_url);}));
		}
		return $services;
	}

	// 7.2 step 3 & 4 + 7.3 step 2 & 3
	protected static function Yadis($url, \Poodle\HTTP\Request $fetcher)
	{
		// 7.2 step 3:
		$parsed = parse_url($url);
		if (!$parsed) {
			return false;
		}
		if (empty($parsed['scheme'])) {
			$url = 'http://'.$url;
		} else
		if (!preg_match('#^https?$#Di', $parsed['scheme'])) {
			return false;
		}
		$url = preg_replace('/#.*$/Ds', '', $url);

		// 7.2 step 4:
		$url = \Poodle\RFC_3986::normalize_url($url);

		// 7.3 step 2 (Yadis)
		require_once 'poodle/yadis/yadis.php';
		$response = \Poodle\Yadis\discover($url, $fetcher);
		if (!$response || ($response->failed && !$response->isXRDS())) {
			return array();
		}

		$services = self::makeServiceEndpoints($url, $response->services(array('Poodle\\OpenID\\Discover::filter_MatchesAnyType')));

		// 7.3 step 3 (HTML-Based discovery)
		if (!$services) {
			if ($response->isXRDS()) {
				// valid XRDS document but no Service Elements found
				// so we just fetch the html/xml version
				$response = $fetcher->get($url);
				if (200 != $response->status && 206 != $response->status) {
					return array();
				}
			}
			// 7.3.3
			$discovery_types = array(
				array(\Poodle\OpenID::TYPE_V2_0, 'openid2.provider', 'openid2.local_id'),
				array(\Poodle\OpenID::TYPE_V1_1, 'openid.server', 'openid.delegate')
			);
			$services = array();
			foreach ($discovery_types as $type) {
				// <link rel="openid2.provider" href="" /> | <link rel="openid.server" href="" />
				if (preg_match("#<link[^>]+rel=[\"']{$type[1]}[\"'][^>]*>#si", $response->body, $m)) {
					$service = new \Poodle\OpenID\Provider\Endpoint();
					$service->claimed_id = $response->final_uri;
					$service->server_url = self::fetch_href($m[0]);
					$service->LocalID    = (preg_match("#<link[^>]+rel=[\"']{$type[2]}[\"'][^>]*>#si", $response->body, $m) ? self::fetch_href($m[0]) : null);
					$service->type_uris  = array($type[0]);
					$services[] = $service;
				}
			}
		}

		return self::getOPOrClaimedServices($services);
	}

	public static function filter_MatchesAnyType($service)
	{
		$uris = $service->getTypes();
		foreach (\Poodle\OpenID::getTypeURIs() as $uri) {
			if (in_array($uri, $uris)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 7.3.2.2
	 * If no OP Identifier found, return the rest, sorted with
	 * most preferred first according to the type uris.
	 */
	protected static function getOPOrClaimedServices($services)
	{
		$op_services = self::sortByType($services, array(\Poodle\OpenID::TYPE_V2_0_OP));
		return $op_services ? $op_services : self::sortByType($services, \Poodle\OpenID::getTypeURIs());
	}

	protected static function sortByType($service_list, $preferred_types)
	{
		// Rearrange service_list in a new list so services are ordered by
		// types listed in preferred_types.  Return the new list.

		// Build a list with the service elements in tuples whose
		// comparison will prefer the one with the best matching service
		$services = array();
		foreach ($service_list as $index => $service) {
			// Set the index of the first matching type, or something higher
			// if no type matches.
			//
			// This provides an ordering in which service elements that
			// contain a type that comes earlier in the preferred types list
			// come before service elements that come later. If a service
			// element has more than one type, the most preferred one wins.
			$prio = count($preferred_types);
			foreach ($preferred_types as $i => $type) {
				if (in_array($type, $service->type_uris)) {
					$prio = $i;
					break;
				}
			}
			$services[$prio*1000+$index] = $service;
		}
		ksort($services);
		return array_values($services);
	}

	protected static function fetch_href($html)
	{
		return preg_match('#^.+href=["\'](.+)?["\'].+$#Dsi', $html, $m) ? \Poodle\RFC_3986::normalize_url(htmlspecialchars_decode($m[1])) : null;
	}

	protected static function makeServiceEndpoints($uri, $yadis_services)
	{
		$s = array();
		if ($yadis_services) {
			foreach ($yadis_services as $service) {
				$types = $service->getTypes();
				$uris  = $service->getURIs();
				if ($types && $uris) {
					foreach ($uris as $service_uri) {
						$sep = new \Poodle\OpenID\Provider\Endpoint();
						if ($sep->parseService($uri, $service_uri, $types, $service)) {
							$s[] = $sep;
						}
					}
				}
			}
		}
		return $s;
	}

}
