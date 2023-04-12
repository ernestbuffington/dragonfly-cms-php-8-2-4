<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Provider;

class SoundCloud extends \Poodle\Auth\Provider
{
	protected
		$clientId     = '',
		$clientSecret = '';

	function __construct($config=array())
	{
		parent::__construct($config);
		$this->clientId = \Poodle::getKernel()->CFG->auth_soundcloud->clientId;
		$this->clientSecret = \Poodle::getKernel()->CFG->auth_soundcloud->clientSecret;
	}

	public function getAction($credentials=array())
	{
		return new \Poodle\Auth\Result\Form(
			array(),
			'?auth='.$this->id,
			'auth-soundcloud'
		);
	}

	public static function getConfigOptions()
	{
		$CFG = \Poodle::getKernel()->CFG;
		return array(
			array(
				'name'  => 'clientId',
				'type'  => 'text',
				'label' => 'Client ID',
				'value' => $CFG->auth_soundcloud->clientId
			),
			array(
				'name'  => 'clientSecret',
				'type'  => 'text',
				'label' => 'Client Secret',
				'value' => $CFG->auth_soundcloud->clientSecret
			),
		);
	}

	public static function setConfigOptions($data)
	{
		$CFG = \Poodle::getKernel()->CFG;
		$CFG->set('auth_soundcloud', 'clientId', (string)$data['clientId']);
		$CFG->set('auth_soundcloud', 'clientSecret', (string)$data['clientSecret']);
	}

	public function authenticate($credentials)
	{
		if (isset($_GET['code']) || isset($_POST['code']) || isset($_GET['state']) || isset($_POST['state'])) {
			return $this->finish();
		}

		if (empty($credentials['redirect_uri'])) {
			$args = array('auth' => $this->id);
			$credentials['redirect_uri'] = \Poodle\URI::appendArgs($_SERVER['REQUEST_URI'], $args);
		}

		$_SESSION['SOUNDCLOUD_AUTH']['state'] = md5(uniqid(random_int(0, mt_getrandmax()), true));
		return new \Poodle\Auth\Result\Redirect('https://soundcloud.com/connect?'
			. http_build_query(array(
				'client_id' => $this->clientId,
				'redirect_uri' => \Poodle\URI::abs($credentials['redirect_uri']),
				'response_type' => 'code',
				'state' => $_SESSION['SOUNDCLOUD_AUTH']['state'],
			), null, '&'));
	}

	protected function finish()
	{
		$state = $_POST->raw('state') ?: $_GET->raw('state');
		if (empty($_SESSION['SOUNDCLOUD_AUTH']['state']) || $state != $_SESSION['SOUNDCLOUD_AUTH']['state']) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'CSRF state token does not match one provided.');
		}
		unset($_SESSION['SOUNDCLOUD_AUTH']['state']);

		if (isset($_POST['error_code']) || isset($_GET['error_code'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE,
				($_POST->raw('error_code') ?: $_GET->raw('error_code')) . ': '
				. ($_POST->raw('error_message') ?: $_GET->raw('error_message')));
		}

		$code = $_POST->raw('code') ?: $_GET->raw('code');
		$access_token = $this->getAccessTokenFromCode($code);
		if (!$access_token) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Retreiving SoundCloud access token failed.');
		}

		$user_info = json_decode($this->oauthRequest("https://api.soundcloud.com/me?oauth_token={$access_token}"), null, 512, JSON_THROW_ON_ERROR);

		// https://developers.soundcloud.com/docs/api/reference#me
		$claimed_id  = "soundcloud-{$user_info->id}";
		$identity_id = \Poodle\Auth\Detect::identityId($this->id, $claimed_id);
		if (!$identity_id) {
			if (empty($user_info->email)) {
				$user_info->email = $user_info->id.'@soundcloud.com';
			}
			$user = \Poodle\Identity::factory(array(
				'nickname'  => $user_info->username,
				'email'     => $user_info->email,
				//'givenname' => $user_info->first_name,
				//'surname'   => $user_info->last_name,
				//'language'  => strtr(strtolower($user_info->locale),'_','-'),
				'timezone'  => date_default_timezone_get(),
				//'website'   => $user_info->website,
				//'gender'    => $user_info->gender,
			));
//			$user->soundcloud_id = $user_info->id;
//			$user_info->avatar_url
		} else {
			$user = \Poodle\Identity\Search::byID($identity_id);
		}

		if (!$user) {
			return new \Poodle\Auth\Result\Error(self::ERR_IDENTITY_NOT_FOUND, 'A database record with the supplied identity_id ('.$identity_id.') could not be found.');
		}

		$result = new \Poodle\Auth\Result\Success($user);
		$result->claimed_id = $claimed_id;
		return $result;
	}

	protected $HTTP;
	protected function oauthRequest($url, $params)
	{
		if (!$this->HTTP) {
			$this->HTTP = \Poodle\HTTP\Request::factory();
		}
		try {
			if ($params) {
				$result = $this->HTTP->post($url, $params);
			} else {
				$result = $this->HTTP->get($url);
			}
		} catch (\Exception $e) {
			// most likely that user very recently revoked authorization.
			// In any event, we don't have an access token, so say so.
			return false;
		}
		if (200 != $result->status) {
			$msg = json_decode($result->body, null, 512, JSON_THROW_ON_ERROR);
			if ($msg && isset($msg->error)) {
				trigger_error('SoundCloud OAuth: '.$msg->error, E_USER_WARNING);
			} else if (isset($result->headers['www-authenticate'])) {
				trigger_error($result->headers['www-authenticate'], E_USER_WARNING);
			} else {
				trigger_error($result->status.':'.$result->body, E_USER_WARNING);
			}
			return false;
		}
		return $result->body;
	}

	protected function getAccessTokenFromCode($code)
	{
		$redirect_uri = \Poodle\URI::abs($_SERVER['REQUEST_URI']);
		$parts = parse_url($redirect_uri);
		if (!empty($parts['query'])) {
			$params = explode('&',$parts['query']);
			foreach ($params as $i => $v) {
				if (preg_match('/^(code|state)=/', $v)) {
					unset($params[$i]);
				}
			}
			$redirect_uri = rtrim(str_replace('?'.$parts['query'], '?'.implode('&',$params), $redirect_uri), '?');
		}

		$access_token_response = $this->oauthRequest('https://api.soundcloud.com/oauth2/token', array(
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'redirect_uri' => $redirect_uri,
			'grant_type' => 'authorization_code',
			'code' => $code,
//			'access_token' => $this->clientId.'|'.$this->clientSecret,
//			'appsecret_proof' => hash_hmac('sha256', $this->clientId.'|'.$this->clientSecret, $this->clientSecret)
		));

		if (empty($access_token_response)) {
			return false;
		}

		$response_params = json_decode($access_token_response, null, 512, JSON_THROW_ON_ERROR);
		return empty($response_params->access_token) ? false : $response_params->access_token;
	}

}
