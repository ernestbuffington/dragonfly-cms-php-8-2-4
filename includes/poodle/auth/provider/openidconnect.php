<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	OpenIDConnect
*/

namespace Poodle\Auth\Provider;

abstract class OpenIDConnect extends \Poodle\Auth\Provider
{
	const
		ISSUER_URI = '';

	public function getAction($credentials=array())
	{
		return new \Poodle\Auth\Result\Form(
			array(),
			'?auth='.$this->id,
			'auth-oidc-'.static::getClassName()
		);
	}

	protected static function getClassName()
	{
		return strtolower(substr(static::class, strrpos(static::class, '\\') + 1));
	}

	public static function getConfigOptions()
	{
		$CFG = \Poodle::getKernel()->CFG['auth_oidc_'.static::getClassName()];
		return array(
			array(
				'name'  => 'client_id',
				'type'  => 'text',
				'label' => 'Client ID',
				'value' => $CFG->client_id
			),
			array(
				'name'  => 'client_secret',
				'type'  => 'text',
				'label' => 'Client Secret',
				'value' => $CFG->client_secret
			),
		);
	}

	public static function setConfigOptions($data)
	{
		$section = 'auth_oidc_'.static::getClassName();
		$CFG = \Poodle::getKernel()->CFG;
		$CFG->set($section, 'client_id', (string)$data['client_id']);
		$CFG->set($section, 'client_secret', (string)$data['client_secret']);
	}

	public function authenticate($credentials)
	{
		if (isset($_GET['error_code'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE,
				"{$_GET['error_code']}: {$_GET['error_message']}");
		}

		$CFG = \Poodle::getKernel()->CFG['auth_oidc_'.static::getClassName()];
		$OIDC = new \Poodle\OpenID\Connect\Client(
			static::ISSUER_URI,
			$CFG->client_id,
			$CFG->client_secret
		);
		$OIDC->addAuthorizationScope('openid');
		$OIDC->addAuthorizationScope('profile');
		$OIDC->addAuthorizationScope('email');
		$OIDC->redirect_uri = $this->getAuthURI($credentials);

		if (!isset($_GET['code'])) {
			return new \Poodle\Auth\Result\Redirect(
				$OIDC->getAuthorizationUrl()
			);
		}

		try {
			$openid = $OIDC->authenticate($_GET['code'], $_GET['state']);
		} catch (\Exception $e) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, $e->getMessage());
		}

		if ($openid->aud != $OIDC->id) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Invalid aud.');
		}

		if (static::ISSUER_URI != $openid->iss) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Invalid iss: '.$openid->iss);
		}

		$claimed_id  = static::getClassName()."-oidc-{$openid->sub}";
		$identity_id = \Poodle\Auth\Detect::identityId($this->id, $claimed_id);
		if (!$identity_id) {
			$user_info = $OIDC->getUserInfo();
			if (!$user_info) {
				return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'Invalid '.static::getClassName().' OIDC userinfo.');
			}
			/*
				"kind": "plus#personOpenIdConnect",
				"email_verified": "true",
			*/
			$user = \Poodle\Identity::factory(array(
				'nickname'  => $user_info->name,
				'email'     => $user_info->email,
				'givenname' => $user_info->given_name,
				'surname'   => $user_info->family_name,
				'language'  => strtr(strtolower($user_info->locale),'_','-'),
				'timezone'  => date_default_timezone_get(),
				//'website'   => $user_info->profile,
				//'avatar'    => $user_info->picture,
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
