<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-connect-discovery-1_0.html
*/

namespace Poodle\OpenID\Connect;

use \Poodle\JSON;

class Client extends \Poodle\OAuth2\Client
{
	protected
		$issuer = '',
		$config = array(),
		$scopes = array('openid');

	public function __construct($issuer_uri = null, $client_id = null, $client_secret = null)
	{
		parent::__construct($client_id, $client_secret);
		$this->issuer = $issuer_uri;
	}

	protected function getProviderConfig()
	{
		if (!$this->config) {
			$this->config = Discovery::getProviderConfiguration($this->issuer);
		}
		return $this->config;
	}

	/**
	 * To get a Google refresh_token use:
	 * $auth_params = array('access_type' => 'offline');
	 */
	public function getAuthorizationUrl(array $auth_params = array(), $response_method = 'GET')
	{
		// Generate and store a nonce in the session
		// The nonce is an arbitrary value
		if (!$this->scopes || in_array('openid', $this->scopes)) {
			$auth_params['nonce'] = $_SESSION['openid_connect_nonce'] = md5(random_bytes(32));
		}

		return parent::getAuthorizationUrl($auth_params, $response_method);
	}

	public function authenticate($code, $state)
	{
		$result = parent::authenticate($code, $state);

		if (!$this->scopes || in_array('openid', $this->scopes)) {
			$json = $this->tokenResponse;
			if (!property_exists($json, 'id_token')) {
				throw new \Exception('User did not authorize openid scope');
			}
			$parts = explode('.', $json->id_token);
			if (count($parts) != 3) {
				throw new \UnexpectedValueException('Wrong number of segments');
			}
			// Verify the signature
			$jwks = JSON::decode($this->HTTPRequest('get', $this->getProviderConfigValue('jwks_uri')));
			if (!$jwks) {
				throw new \DomainException('No jwks_uri\'s found');
			}
			$result = \Poodle\JWT::decode(
				$json->id_token,
				\Poodle\JWK::parseKeys($jwks->keys)
			);

			// If this is a valid openid
			$expected_at_hash = null;
			if (isset($result->at_hash) && isset($json->access_token)) {
				$header = JSON::decode(\Poodle\Base64::urlDecode($parts[0]));
				$bit = substr($header->alg, 2, 3);
				$len = ((int)$bit)/16;
				$expected_at_hash = \Poodle\Base64::urlEncode(substr(hash('sha'.$bit, $json->access_token, true), 0, $len));
			}
			if ($result->iss != $this->getProviderConfigValue('issuer')
			 || $result->nonce != $_SESSION['openid_connect_nonce']
			 || ($result->aud != $this->id && !in_array($this->id, $result->aud))
			 || (isset($result->at_hash) && $result->at_hash != $expected_at_hash)
			) {
				throw new \DomainException('Unable to verify JWT openid');
			}

			if ($result->nonce != $_SESSION['openid_connect_nonce']) {
				throw new \DomainException('Invalid JWT openid nonce');
			}
			// Clean up the session a little
			unset($_SESSION['openid_connect_nonce']);
		}

		return $result;
	}

}
