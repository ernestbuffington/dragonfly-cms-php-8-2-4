<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\OAuth2;

use \Poodle\JSON;

abstract class Client
{
	protected
		$id = '',
		$secret = '',
		$redirect_uri  = '',
		$tokens        = null, // AccessToken
		$tokenResponse = null,
		$scopes        = array();

	public function __construct($client_id = null, $client_secret = null)
	{
		$this->id     = $client_id;
		$this->secret = $client_secret;
	}

	public function __get($k)
	{
		if (property_exists($this, $k)) {
			return $this->$k;
		}
	}

	public function __set($k, $v)
	{
		if (property_exists($this, $k)) {
			if ('tokenResponse' == $k) {
				return;
			}
			if ('scopes' == $k) {
				$this->setAuthorizationScopes($v);
				return;
			}
			if ('tokens' == $k) {
				if (!($v instanceof AccessToken)) {
					$v = new AccessToken($v);
				}
			}
			$this->$k = $v;
		}
	}

	public function addAuthorizationScope($scope)
	{
		$this->scopes = array_unique(array_merge($this->scopes, (array)$scope));
	}

	public function setAuthorizationScopes($scopes)
	{
		$this->scopes = array_unique((array)$scopes);
	}

	public function getAuthorizationHeader()
	{
		if ($this->tokens->hasExpired()) {
			$this->refreshToken();
		}
		return "{$this->tokens->type} {$this->tokens->access_token}";
	}

	/**
	 * To get a Google refresh_token use:
	 * $auth_params = array('access_type' => 'offline');
	 */
	public function getAuthorizationUrl(array $auth_params = array(), $response_method = 'GET')
	{
		$auth_endpoint = $this->getProviderConfigValue('authorization_endpoint');

		// If the client has been registered with additional scopes
		$scopes = $this->scopes ?: array();

		// State essentially acts as a session key
		$state = $_SESSION[static::class . '-state'] = md5(random_bytes(32));

		// Process additional response types
		if (empty($auth_params['response_type'])) {
			$response_types = '';
		} else {
			$response_types = $auth_params['response_type'];
			$response_types = ' ' . (is_array($response_types)
				? implode(' ', $response_types)
				: $response_types);
		}

		$auth_params = array_merge($auth_params, array(
			'response_type' => 'code' . $response_types,
			'redirect_uri' => $this->redirect_uri,
			'client_id' => $this->id,
			'state' => $state,
			'scope' => implode(' ', $scopes)
		));

		if ('POST' == $response_method) {
			$auth_params['response_mode'] = 'form_post';
		}

		return $auth_endpoint . '?' . http_build_query($auth_params, null, '&');
	}

	public function authenticate($code, $state)
	{
		// Check if we have an authorization code
		if (!$code) {
			throw new \UnexpectedValueException('Invalid code');
		}

		// Verify OAuth session
		if (empty($_SESSION[static::class . '-state']) || $state != $_SESSION[static::class . '-state']) {
			throw new \UnexpectedValueException('CSRF state token does not match one provided');
		}
		unset($_SESSION[static::class . '-state']);

		$this->requestTokens(array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->redirect_uri,
		));

		return true;
	}

	public function getUserInfo()
	{
		return JSON::decode(
			$this->HTTPRequest('get',
				$this->getProviderConfigValue('userinfo_endpoint'),
				array(),
				array(
					'Authorization' => $this->getAuthorizationHeader(),
				)
			)
		);
	}

	// https://tools.ietf.org/html/rfc7009
	public function revokeToken($token, $type = null)
	{
		if ($revocation_endpoint = $this->getProviderConfigValue('revocation_endpoint')) {
			$headers = array(
				'Authorization' => $this->getAuthorizationHeader(),
			);
			$params = array('token' => $token);
			if ($type) {
				$params['token_type_hint'] = $type;
			}
			$this->HTTPRequest('post', $revocation_endpoint, $params, $headers);
		}
	}

	abstract protected function getProviderConfig();

	protected function getProviderConfigValue($param, $default = null)
	{
		$cfg = $this->getProviderConfig();
		if (isset($cfg[$param])) {
			return $cfg[$param];
		}
		if ('token_endpoint_auth_methods_supported' === $param) {
			return array('client_secret_basic');
		}
		if ($default) {
			return $default;
		}
		throw new \DomainException("The provider {$param} has not been set.");
	}

	/**
	 * Requests Access token with refresh token
	 */
	protected function refreshToken()
	{
		if (empty($this->tokens->refresh_token)) {
			throw new \DomainException('Invalid refresh_token');
		}
		$this->requestTokens(array(
			'grant_type'    => 'refresh_token',
			'refresh_token' => $this->tokens->refresh_token,
		));
	}

	// Save the full response
	protected function setTokenResponse($json)
	{
		$this->tokenResponse = $json;
		if (!isset($json->refresh_token) && isset($this->tokens->refresh_token)) {
			$json->refresh_token = $this->tokens->refresh_token;
		}
		$this->tokens = new AccessToken($json);
	}

	protected function requestTokens(array $params)
	{
		$token_endpoint = $this->getProviderConfigValue('token_endpoint');

		$headers = array('Content-type: application/x-www-form-urlencoded');

		# client_secret_post | client_secret_basic | client_secret_jwt | private_key_jwt
		# Consider Basic authentication if provider config is set this way
		$ClientAuthentication = $this->getProviderConfigValue('token_endpoint_auth_methods_supported');
		if (in_array('client_secret_basic', $ClientAuthentication)) {
			$headers[] = 'Authorization: Basic ' . base64_encode($this->id . ':' . $this->secret);
		} else {
			$params['client_id'] = $this->id;
			$params['client_secret'] = $this->secret;
		}

		$response = $this->HTTPRequest('post', $token_endpoint, $params, $headers);
		$json = $response ? JSON::decode($response) : null;
		if (empty($json)) {
			throw new \DomainException("Failed to decode the json token ({$response})");
		}
		$this->setTokenResponse($json);
	}

	protected $HTTP;
	protected function HTTPRequest($method, $url, array $body = array(), array $headers = array())
	{
		if (!$this->HTTP) {
			$this->HTTP = \Poodle\HTTP\Request::factory();
		}
		try {
			$result = $this->HTTP->doRequest($method, $url, $body ?: null, $headers);
			if (200 == $result->status) {
				return $result->body;
			}
			$msg = json_decode($result->body, null, 512, JSON_THROW_ON_ERROR);
			if ($msg && isset($msg->error)) {
				if (isset($msg->error_description)) {
					throw new \Exception("{$msg->error} ({$msg->error_description})");
				}
				throw new \Exception(is_object($msg->error) ? $msg->error->message : $msg->error);
			}
			throw new \Exception($result->status."\n".$result->body);
		} catch (\Exception $e) {
			// most likely that user very recently revoked authorization.
			// In any event, we don't have an access token, so say so.
			\Poodle\LOG::error(static::class, $e->getMessage()."\n{$url}");
			throw $e;
		}
		return false;
	}

}
