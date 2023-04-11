<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth;

abstract class Detect
{

	/**
	 * The Claimed Identifier in a successful authentication response SHOULD be
	 * used by the Relying Party as a key for local storage of information about
	 * the user. The Claimed Identifier MAY be used as a user-visible Identifier.
	 * When displaying URL Identifiers,
	 */

	public static function provider($id)
	{
		if (!$id) {
			return false;
		}

		$SQL = \Poodle::getKernel()->SQL;
		$id_secure = $SQL->quote(\Poodle\Auth::secureClaimedID($id));

		/**
		 * Check in auth_identities table if claimed_id already exist
		 */
		$provider = $SQL->uFetchAssoc("SELECT
			identity_id,
			auth_password password,
			auth_provider_id id,
			auth_provider_class class,
			auth_provider_name name
		FROM {$SQL->TBL->auth_identities} ua
		INNER JOIN {$SQL->TBL->auth_providers} USING (auth_provider_id)
		WHERE auth_claimed_id={$id_secure}
		  AND auth_provider_is_2fa = 0
		  AND auth_provider_mode > 0");
		if ($provider) {
			$provider['id'] = (int)$provider['id'];
			$provider['identity_id'] = (int)$provider['identity_id'];
			$provider['discover_uri'] = null;
			$provider['identifier'] = $id;
			return new $provider['class']($provider);
		}

		/**
		 * Claimed_id doesn't exist, try some magic with regex patterns
		 */
		$uid = preg_replace('#https?://#','',$id);
		$result = $SQL->query("SELECT
			auth_provider_id id,
			auth_provider_class class,
			auth_provider_name name,
			auth_detect_regex regex,
			auth_detect_discover_uri discover_uri
		FROM {$SQL->TBL->auth_providers_detect}
		INNER JOIN {$SQL->TBL->auth_providers} USING (auth_provider_id)
		WHERE auth_provider_is_2fa = 0
		  AND auth_provider_mode > 0
		UNION SELECT
			auth_provider_id id,
			auth_provider_class class,
			auth_provider_name name,
			null regex,
			null discover_uri
		FROM {$SQL->TBL->auth_providers}
		WHERE auth_provider_is_2fa = 0
		  AND auth_provider_mode > 0");
		while ($provider = $result->fetch_assoc())
		{
			if ($provider['regex']) {
				$re = '#^'.$provider['regex'].'#i';
				if (preg_match($re, $uid)) {
					if ($provider['discover_uri']) {
						$provider['discover_uri'] = preg_replace($re,$provider['discover_uri'],$uid);
					}
					unset($provider['regex']);
					$provider['identifier'] = $id;
					return new $provider['class']($provider);
				}
			} else if ($provider['class']::validClaimedId($id)) {
				$provider['identifier'] = $id;
				return new $provider['class']($provider);
			}
		}

		return false;
	}

	public static function identityId($provider_id, $claimed_id)
	{
		$SQL = \Poodle::getKernel()->SQL;
		$provider_id = (int)$provider_id;
		$user = $SQL->uFetchRow("SELECT identity_id
		FROM {$SQL->TBL->auth_identities}
		WHERE auth_provider_id={$provider_id}
		  AND auth_claimed_id=".$SQL->quote(\Poodle\Auth::secureClaimedID($claimed_id)));
		return $user ? (int)$user[0] : false;
	}

	public static function providerIdByClass($class)
	{
		$SQL = \Poodle::getKernel()->SQL;
		$id = $SQL->uFetchRow("SELECT auth_provider_id FROM {$SQL->TBL->auth_providers} WHERE auth_provider_class=".$SQL->quote($class));
		return $id ? (int)$id[0] : false;
	}

}
