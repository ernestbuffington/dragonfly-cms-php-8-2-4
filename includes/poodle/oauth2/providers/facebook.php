<?php

namespace Poodle\OAuth2\Providers;

class Facebook extends \Poodle\OAuth2\Client
{
	const
		GRAPH_VERSION = 'v2.9';

	protected
		$scopes = array('email'),
		$fields = array('name','email','first_name','last_name');

	public function authenticate($code, $state)
	{
		if (isset($_POST['error_code']) || isset($_GET['error_code'])) {
			throw new \Exception(
				($_POST['error_code'] ? $_POST['error_code'] : $_GET['error_code']) . ': '
				. ($_POST['error_message'] ? $_POST['error_message'] : $_GET['error_message'])
			);
		}
		return parent::authenticate($code, $state);
	}

	public function getUserInfo()
	{
		return \Poodle\JSON::decode(
			$this->HTTPRequest('post',
				$this->getProviderConfigValue('userinfo_endpoint'),
				array(
					'method' => 'GET',
					'fields' => implode(',', $this->fields),
					'access_token' => $this->tokens->access_token,
				)
			)
		);
	}

	public function getUserPicture()
	{
		return \Poodle\JSON::decode(
			$this->HTTPRequest('post',
				$this->getProviderConfigValue('userinfo_endpoint').'/picture',
				array(
					'method' => 'GET',
					'access_token' => $this->tokens->access_token,
				)
			)
		);
	}

	protected function getProviderConfig()
	{
		return array(
			'authorization_endpoint' => 'https://www.facebook.com/'.static::GRAPH_VERSION.'/dialog/oauth',
			'token_endpoint' => 'https://graph.facebook.com/oauth/access_token',
			'userinfo_endpoint' => 'https://graph.facebook.com/'.static::GRAPH_VERSION.'/me',
			'token_endpoint_auth_methods_supported' => array('client_secret_post'),
		);
	}

	protected function HTTPRequest($method, $url, array $body = array(), array $headers = array())
	{
		if (!$body) { $body = array(); }
		if (!isset($body['appsecret_proof'])) {
			$body['appsecret_proof'] = hash_hmac('sha256',
				isset($body['access_token']) ? $body['access_token'] : "{$this->id}|{$this->secret}",
				$this->secret);
		}
		return parent::HTTPRequest($method, $url, $body, $headers);
	}

}
