<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/developers/specs/

	7.1: The HTML form field's "name" attribute SHOULD have the value "openid_identifier".
*/

namespace Poodle\OpenID;

abstract class RelyingParty
{
	public const
		V1_QUERY_NONCE_KEY      = 'openid_nonce',
		V1_QUERY_CLAIMED_ID_KEY = 'openid_cid',
		/**
		 * Keep nonces for 3 hours. This is probably more than necessary,
		 * but there is not much overhead in storing nonces,
		 * and it prevents failure on servers with a clock-skew.
		 */
		NONCE_TIMEOUT     = 10800,
		NONCE_SALT_CHARS  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',

		SESS_ENDPOINT_KEY = '_POODLE_OPENID_LAST_PROVIDER_ENDPOINT',

		V2_0_ID_SELECT    = 'http://specs.openid.net/auth/2.0/identifier_select';

	public
		$endpoint,
		$message;

	public function isOpenIDv1()
	{
		return (!$this->endpoint || $this->endpoint->isOpenIDv1());
	}

	/**
	 * # 9.  Create indirect request
	 * http://openid.net/specs/openid-authentication-2_0.html#requesting_authentication
	 */
	public static function start($identifier, $return_to = null)
	{
		require_once 'poodle/yadis/yadis.php';
		$fetcher  = \Poodle\Yadis\request();
		$services = \Poodle\OpenID\Discover::ID($identifier, $fetcher);
		if (!$services) {
			return false;
		}
		$endpoint = reset($services);
		$_SESSION[self::SESS_ENDPOINT_KEY] = $endpoint;
		if (!$return_to) {
			$return_to = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		}

		# GMail OpenID's are based on realm, so use Google Profiles
		$endpoint->server_url = preg_replace('#(google\.com[^\?]+)$#Di','$1?source=profiles',$endpoint->server_url);

		$request = new \Poodle\OpenID\RelyingParty\Request($endpoint);
		$request->message['mode']       = 'checkid_setup';       // or checkid_immediate
		// optional:
		$request->message['claimed_id'] = $endpoint->claimed_id; // Claimed Identifier
//		$request->message['identity']   = $endpoint->claimed_id; // OP-Local Identifier
//		$request->message['realm']      = \Poodle\URI::abs(preg_replace('#[^/]*$#D','',\Poodle::$URI_INDEX));
//		$request->message['realm']      = $return_to;
		$request->message['realm']      = preg_replace('#^(.*//[^/]+/).*$#D','$1',$return_to);
		if ($request->message->isOpenIDv1()) {
			$return_to = \Poodle\URI::appendArgs($return_to, array(
				self::V1_QUERY_CLAIMED_ID_KEY => $endpoint->claimed_id,
				self::V1_QUERY_NONCE_KEY      => gmdate('Y-m-d\TH:i:s\Z').\Poodle\Random::string(6, self::NONCE_SALT_CHARS)
			));
		} else {
			if ($request->endpoint->isOPIdentifier()) {
				// This will never happen when we're in compatibility
				// mode, as long as isOPIdentifier() returns false
				// whenever preferredNamespace() returns OPENID1_NS.
				$claimed_id = $request_identity = self::V2_0_ID_SELECT;
			} else {
				$request_identity = $request->endpoint->getLocalID();
				$claimed_id = $request->endpoint->claimed_id;
			}
			$request->message['identity']   = $request_identity;
			$request->message['claimed_id'] = $claimed_id;
		}
		$request->message['return_to'] = $return_to;

		$assoc = \Poodle\OpenID\RelyingParty\Association::fromEndpoint($endpoint, $fetcher);
		if ($assoc) {
			$request->message['assoc_handle'] = $assoc->handle;
		}

		return $request;
	}

	/**
	 * # 10.  Responding to Authentication Requests
	 * http://openid.net/specs/openid-authentication-2_0.html#responding_to_authentication
	 */
	public static function finish($query = null, $return_to = null)
	{
		$endpoint = null;
		if (isset($_SESSION[self::SESS_ENDPOINT_KEY])) {
			$endpoint = $_SESSION[self::SESS_ENDPOINT_KEY];
			unset($_SESSION[self::SESS_ENDPOINT_KEY]);
		}

		if (!$return_to) {
			$return_to = \Poodle\URI::abs($_SERVER['PHP_SELF']);
		}

		return new \Poodle\OpenID\RelyingParty\Response(
			$endpoint,
			\Poodle\OpenID\Message::fromArray(\Poodle\OpenID::getQueryVars($query)),
			$return_to
		);
	}

}
