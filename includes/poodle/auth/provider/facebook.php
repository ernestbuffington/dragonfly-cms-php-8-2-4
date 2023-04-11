<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Provider;

class Facebook extends \Poodle\Auth\Provider
{
	const
		DEFAULT_GRAPH_VERSION = 'v2.6';

	protected
		$appId     = '',
		$appSecret = '';

	function __construct($config=array())
	{
		parent::__construct($config);
		$this->appId = \Poodle::getKernel()->CFG->auth_facebook->appId;
		$this->appSecret = \Poodle::getKernel()->CFG->auth_facebook->appSecret;
	}

	public function getAction($credentials=array())
	{
		return new \Poodle\Auth\Result\Form(
			array(),
			'?auth='.$this->id,
			'auth-facebook'
		);
	}

	public static function getConfigOptions()
	{
		$CFG = \Poodle::getKernel()->CFG;
		return array(
			array(
				'name'  => 'appId',
				'type'  => 'text',
				'label' => 'App ID',
				'value' => $CFG->auth_facebook->appId
			),
			array(
				'name'  => 'appSecret',
				'type'  => 'text',
				'label' => 'App Secret',
				'value' => $CFG->auth_facebook->appSecret
			),
		);
	}

	public static function setConfigOptions($data)
	{
		$CFG = \Poodle::getKernel()->CFG;
		$CFG->set('auth_facebook', 'appId', (string)$data['appId']);
		$CFG->set('auth_facebook', 'appSecret', (string)$data['appSecret']);
	}

	public function authenticate($credentials)
	{
		$oauth2 = new \Poodle\OAuth2\Providers\Facebook($this->appId, $this->appSecret);
		$oauth2->redirect_uri = $this->getAuthURI($credentials);

		if (!isset($_GET['code'])) {
			return new \Poodle\Auth\Result\Redirect($oauth2->getAuthorizationUrl());
		}

		try {
			$oauth2->authenticate($_GET['code'], $_GET['state']);
			$user_info = $oauth2->getUserInfo();
		} catch (\Exception $e) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, $e->getMessage());
//			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Retreiving Facebook access token failed.');
//			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'CSRF state token does not match one provided.');
		}

		// https://developers.facebook.com/docs/graph-api/reference/user/
		$claimed_id  = "facebook-{$user_info->id}";
		$identity_id = \Poodle\Auth\Detect::identityId($this->id, $claimed_id);
		if (!$identity_id) {
//			if ($user_info->verified) {
			if (empty($user_info->email)) {
				$user_info->email = $user_info->id.'@facebook.com';
			}
			$user = \Poodle\Identity::factory(array(
				'nickname'  => $user_info->name,
				'email'     => $user_info->email,
				'givenname' => $user_info->first_name,
				'surname'   => $user_info->last_name,
				'language'  => strtr(strtolower($user_info->locale),'_','-'),
				'timezone'  => date_default_timezone_get(),
				//'website'   => $user_info->link,
				//'gender'    => $user_info->gender,
			));
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

}
