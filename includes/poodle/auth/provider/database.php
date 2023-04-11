<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Provider;

class Database extends \Poodle\Auth\Provider
{

	protected
		$has_form_fields = true;

	public function getAction($credentials = array())
	{
		$value = null;
		if (!empty($credentials['auth_claimed_id'])) {
			$value = $credentials['auth_claimed_id'];
		} else
		if (!empty($credentials['openid_identifier'])) {
			$value = $credentials['openid_identifier'];
		}
		return new \Poodle\Auth\Result\Form(
			array(
				array('name'=>'auth_claimed_id', 'type'=>'text',     'label'=>'Username', 'value'=>$value),
				array('name'=>'auth_password',   'type'=>'password', 'label'=>'Password'),
			),
			'?auth='.$this->id,
			'auth-database'
		);
	}

	public function authenticate($credentials)
	{
		if (!isset($credentials['auth_claimed_id']) && !isset($credentials['auth_password'])) {
			return $this->getAction($credentials);
		}

		if (!isset($credentials['auth_claimed_id'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'auth_claimed_id is missing');
		}

		if (!isset($credentials['auth_password'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, 'auth_password is missing');
		}

		if (empty($credentials['auth_claimed_id'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'auth_claimed_id is empty');
		}

		if (empty($credentials['auth_password'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'auth_password is empty');
		}

		try {
			$user_email = \Poodle\Input::validateEmail($credentials['auth_claimed_id'])
				? \Poodle\Input::lcEmail($credentials['auth_claimed_id'])
				: false;
		} catch (\Exception $e) {
			$user_email = false;
		}
		$SQL = \Poodle::getKernel()->SQL;
		$id_secure = $SQL->quote(self::secureClaimedID($credentials['auth_claimed_id']));
		if ($user_email) {
			$provider = $SQL->uFetchAssoc("SELECT
				identity_id,
				auth_password password
			FROM {$SQL->TBL->auth_identities} ua
			INNER JOIN {$SQL->TBL->users} u USING (identity_id)
			WHERE auth_provider_id = {$this->id}
			  AND (auth_claimed_id = {$id_secure} OR user_email = {$SQL->quote($user_email)})");
		} else {
			$provider = $SQL->uFetchAssoc("SELECT
				identity_id,
				auth_password password
			FROM {$SQL->TBL->auth_identities} ua
			WHERE auth_provider_id = {$this->id}
			  AND auth_claimed_id = {$id_secure}");
		}
		if (!$provider) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'A database record was not found for '.$credentials['auth_claimed_id']);
		}

		// Verify password
		if (!$this->isValidPassword($provider['identity_id'], $credentials['auth_password'], $provider['password'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'A database record for the supplied claimed_id ('.$credentials['auth_claimed_id'].') was found but, the password verification failed.');
		}

		# Lookup user in the database
		$user = \Poodle\Identity\Search::byID($provider['identity_id']);
		if (!$user) {
			return new \Poodle\Auth\Result\Error(self::ERR_IDENTITY_NOT_FOUND, 'A database record for the supplied identity_id ('.$provider['identity_id'].') could not be found.');
		}

		return new \Poodle\Auth\Result\Success($user);
	}

	/**
	 * Checks to see if the given password is valid for the given identity.
	 *
	 * @param integer $identity_id The identity to check the password for
	 * @param string $plain_password The password to check
	 * @param string $auth_password The password from the database (optional)
	 *
	 * @returns boolean true|false depending on validity
	 */
	public function isValidPassword($identity_id, $plain_password, $auth_password = null)
	{
		$identity_id = (int)$identity_id;

		// No identity_id or plain_password given
		if (1 > $identity_id || empty($plain_password)) {
			return false;
		}

		if (!$auth_password) {
			// Retrieve the current identity password
			$SQL = \Poodle::getKernel()->SQL;
			$auth = $SQL->uFetchRow("SELECT
				auth_password
			FROM {$SQL->TBL->auth_identities} ua
			WHERE identity_id = {$identity_id}
			  AND auth_provider_id = {$this->id}");
			$auth_password = $auth[0];
		}

		// Verify given password
		return self::verifyPassword($plain_password, $auth_password);
	}

	public function updateAuthentication(\Poodle\Auth\Credentials $credentials)
	{
		$identity_id = (int)$credentials->identity_id;
		if (1 > $identity_id) {
			throw new \InvalidArgumentException('Invalid $identity_id');
		}
		if (!$credentials->claimed_id) {
			$credentials->hashPassword();
			return \Poodle::getKernel()->SQL->TBL->auth_identities->update(array(
				'auth_password' => $credentials->password,
			), "identity_id={$identity_id} AND auth_provider_id={$this->id}");
		} else {
			\Poodle::getKernel()->SQL->TBL->auth_identities->delete(array(
				'identity_id' => $identity_id,
				'auth_provider_id' => $this->id
			));
			return parent::updateAuthentication($credentials);
		}
	}

}
