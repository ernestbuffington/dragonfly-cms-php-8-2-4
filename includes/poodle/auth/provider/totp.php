<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	RFC 6238
	As used by Google Authenticator
*/

namespace Poodle\Auth\Provider;

class TOTP extends \Poodle\Auth\Provider2fa
{

	protected
		$has_form_fields = true;

	public function getAction($credentials=array())
	{
		return new \Poodle\Auth\Result\Form(
			array(
				array('name'=>'auth_totp', 'type'=>'text', 'label'=>'Enter the security code'),
			),
			'?auth='.$this->id,
			'auth-totp'
		);
	}

	public function createForIdentity(\Poodle\Identity $identity)
	{
		$secret = \Poodle\TOTP::createSecret(16);
		$this->updateAuthentication(new \Poodle\Auth\Credentials($identity, $secret));
		return $secret;
	}

	public function authenticate($credentials)
	{
		$identity_id = (int) $credentials['identity_id'];

		if (!isset($credentials['auth_totp'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'auth_totp is missing');
		}

		if (empty($credentials['auth_totp'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'auth_totp is empty');
		}

		$SQL = \Poodle::getKernel()->SQL;
		$secret = $SQL->uFetchRow("SELECT
			auth_claimed_id
		FROM {$SQL->TBL->auth_identities} ua
		WHERE auth_provider_id = {$this->id}
		  AND identity_id = {$identity_id}");
		if (!$secret) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'A database record was not found');
		}

		if (!\Poodle\TOTP::verifyCode($secret[0], $credentials['auth_totp'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'The code verification failed.');
		}

		# Code is correct so lookup user in the database
		$user = \Poodle\Identity\Search::byID($identity_id);
		if (!$user) {
			return new \Poodle\Auth\Result\Error(self::ERR_IDENTITY_NOT_FOUND, 'A database record with the supplied identity_id ('.$identity_id.') could not be found.');
		}

		return new \Poodle\Auth\Result\Success($user);
	}

	public function updateAuthentication(\Poodle\Auth\Credentials $credentials)
	{
		$credentials->hash_claimed_id = false;
		return parent::updateAuthentication($credentials);
	}

	public static function getUri($name, $secret, $issuer = '')
	{
		return \Poodle\TOTP::getUri($name, $secret, $issuer);
	}

	public static function getQRCode($name, $secret, $issuer = '')
	{
		return \Poodle\TOTP::getQRCode($name, $secret, $issuer);
	}

}
