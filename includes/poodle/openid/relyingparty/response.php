<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\OpenID\RelyingParty;

class Response_Exception extends \Exception
{
	public const
		E_ERROR_MESSAGE = 1,
		E_INVALID_MODE  = 2;

	protected
		$contact,   // optional
		$reference; // optional

	function __construct($message, $code = 0, \Exception $previous = null)
	{
		if (!is_string($message)) {
			$this->contact   = $message['contact'];
			$this->reference = $message['reference'];
			$message = $message['error'];
		}
		parent::__construct($message, $code, $previous);
	}
	final public function getContact()   { return $this->contact; }
	final public function getReference() { return $this->reference; }
//	public string __toString ( void )
}


class TypeURIMismatch_Exception extends \Exception {}


class Response extends \Poodle\OpenID\RelyingParty
{
	public
		$mode,
		$identity_url,
		$setup_url;    // only when mode=setup_needed
	protected
		$fetcher;

	function __construct($endpoint, $message, $return_to)
	{
		$this->endpoint = $endpoint;
		$this->message  = $message;
		$this->identity_url = $endpoint ? $endpoint->claimed_id : null;
		$this->mode = $message['mode'];
		if ($message->isOpenIDv1() && isset($message['user_setup_url'])) {
			$this->mode = 'setup_needed';
		}
		switch ($this->mode)
		{
		// http://openid.net/specs/openid-authentication-2_0.html#rfc.section.10.2.2
		case 'cancel':
			break;

		// http://openid.net/specs/openid-authentication-2_0.html#rfc.section.5.2.3
		case 'error':
			throw new Response_Exception($message, Response_Exception::E_ERROR_MESSAGE);

		// http://openid.net/specs/openid-authentication-2_0.html#rfc.section.10.1
		case 'id_res':
			$this->process_id_res($return_to);
			break;

		// http://openid.net/specs/openid-authentication-2_0.html#rfc.section.10.2.1
		case 'setup_needed':
			if ($message->isOpenIDv1() && isset($message['user_setup_url'])) {
				// 14.2.1.  Relying Parties
				// When responding with a negative assertion to a "checkid_immediate" mode authentication request,
				// the "user_setup_url" parameter MUST be returned. This is a URL that the end user may visit to
				// complete the request. The OP MAY redirect the end user to this URL,
				// or provide the end user with a link that points to this URL.
				$this->setup_url = $message['user_setup_url'];
			}
			break;

		default:
			throw new Response_Exception("Invalid openid.mode '{$message['mode']}'", Response_Exception::E_INVALID_MODE);
		}
	}

	/**
	 * Return the display identifier for this response.
	 *
	 * The display identifier is related to the Claimed Identifier, but the
	 * two are not always identical.  The display identifier is something the
	 * user should recognize as what they entered, whereas the response's
	 * claimed identifier (in the identity_url attribute) may have extra
	 * information for better persistence.
	 *
	 * URLs will be stripped of their fragments for display.  XRIs will
	 * display the human-readable identifier (i-name) instead of the
	 * persistent identifier (i-number).
	 *
	 * Use the display identifier in your user interface.  Use
	 * identity_url for querying your database or authorization server.
	 *
	 */
	public function getDisplayIdentifier()
	{
		return $this->endpoint ? $this->endpoint->getDisplayIdentifier() : null;
	}

	/**
	 * 10.1.  Positive Assertions
	 * mode=id_res
	 */
	protected function process_id_res($return_to)
	{
		$required_keys = array('assoc_handle', 'return_to', 'sig', 'signed');
		$required_sigs = array('return_to');
		if ($this->message->isOpenIDv1()) {
			$required_keys = array_merge($required_keys, array('identity'));
			if (isset($this->message['identity'])) {
				$required_sigs[] = 'identity';
			}
		} else {
			$required_keys = array_merge($required_keys, array('op_endpoint', 'response_nonce'));
			$required_sigs = array_merge($required_sigs, array('assoc_handle', 'op_endpoint', 'response_nonce'));
			// claimed_id and identity must both be present or both be absent
			if (isset($this->message['identity']) || isset($this->message['claimed_id'])) {
				$required_keys[] = 'claimed_id';
				$required_keys[] = 'identity';
				$required_sigs[] = 'claimed_id';
				$required_sigs[] = 'identity';
			}
		}

		// Check if all required keys exist.
		$missing_keys = array();
		foreach ($required_keys as $field) {
			if (!isset($this->message[$field])) {
				$missing_keys[] = $field;
			}
		}
		if ($missing_keys) {
			throw new Response_Exception('Missing required field(s): '.implode(', ',$missing_keys));
		}

		// Check for presence of required signed keys in value of openid.signed.
		$signed_list_str = $this->message['signed'];
		if (!$signed_list_str) {
			throw new Response_Exception('Invalid value for key openid.signed');
		}
		$signed_list = explode(',', $signed_list_str);
		$unsigned_keys = array();
		foreach ($required_sigs as $field) {
			if (isset($this->message[$field]) && !in_array($field, $signed_list)) {
				$unsigned_keys[] = $field;
			}
		}
		if ($unsigned_keys) {
			throw new Response_Exception('Value of openid.signed is missing field(s): '.implode(', ',$unsigned_keys));
		}

		/**
		 * 11.1.  Verifying the Return URL
		 */
		$ret_url_parts = parse_url(\Poodle\RFC_3986::normalize_url($return_to));
		$msg_url_parts = parse_url(\Poodle\RFC_3986::normalize_url($this->message['return_to']));
		foreach (array('scheme', 'host', 'port', 'path') as $k) {
			if (!isset($ret_url_parts[$k])) {
				$ret_url_parts[$k] = null;
			}
			if (!isset($msg_url_parts[$k])) {
				$msg_url_parts[$k] = null;
			}
			if ($ret_url_parts[$k] !== $msg_url_parts[$k]) {
				throw new Response_Exception('Invalid value for openid.return_to');
			}
		}
		if (isset($ret_url_parts['query'])) {
			$ret_url_q = \Poodle\URI::parseQuery($ret_url_parts['query']);
			$msg_url_q = isset($msg_url_parts['query']) ? \Poodle\URI::parseQuery($msg_url_parts['query']) : array();
			foreach ($ret_url_q as $k => $v) {
				if (!isset($msg_url_q[$k]) || $msg_url_q[$k] !== $v) {
					throw new Response_Exception('Invalid value for openid.return_to');
				}
			}
		}

		require_once 'poodle/yadis/yadis.php';
		$this->fetcher = \Poodle\Yadis\request();

		/**
		 * 11.2.  Verifying Discovered Information
		 */
		if ($this->message->isOpenIDv2()) {
			$this->endpoint = $this->verifyV2Discovery($this->endpoint);
		} else {
			$this->endpoint = $this->verifyV1Discovery($this->endpoint);
		}

		/**
		 * 11.3.  Checking the Nonce
		 */
		if ($this->message->isOpenIDv1()) {
			// 14.2.1.  Relying Parties
			// The Relying Party MUST accept an authentication response (Positive Assertions) that is missing
			// the "openid.response_nonce" parameter. It SHOULD implement a method for preventing replay attacks.
			$nonce = $_GET[self::V1_QUERY_NONCE_KEY] ?? '';
			$server_url = '';
		} else {
			$nonce = $this->message['response_nonce'];
			$server_url = $this->endpoint->server_url;
		}
		if (!$nonce) {
			throw new Response_Exception('Nonce missing from response');
		}
		if (!preg_match('#(\d{4}\-\d\d\-\d\dT\d\d:\d\d:\d\dZ)(.*)#', $nonce, $parts)) {
			throw new Response_Exception('Malformed nonce in response');
		}
		$etime = strtotime($parts[1]);
		if (abs($etime-time()) > self::NONCE_TIMEOUT) {
			throw new Response_Exception('Nonce out of range');
		}
		$tbl = \Poodle::getKernel()->SQL->TBL->auth_providers_nonce;
		try {
			$tbl->insert(array(
				'endpoint_id' => $this->endpoint->id,
				'nonce_etime' => $etime + self::NONCE_TIMEOUT,
				'nonce_salt'  => $parts[2]
			));
		} catch (\Exception $e) {
			throw new Response_Exception('Nonce already used');
		}
		try {
			$tbl->delete('nonce_etime <= '.time());
		} catch (\Exception $e) {}

		/**
		 * 11.4.  Verifying Signatures
		 */
		$assoc = Association::fromURLHandle($this->endpoint->server_url, $this->message['assoc_handle']);
		if ($assoc) {
			// 11.4.1.  Verifying with an Association
			if ($assoc->isExpired()) {
				throw new Response_Exception('Association with ' . $this->endpoint->server_url . ' expired');
			}
			if (!$assoc->verifySignature($this->message)) {
				throw new Response_Exception('Bad signature');
			}
		} else {
			// 11.4.2.  Verifying Directly with the OpenID Provider
			$request_message = clone $this->message;
			$request_message['mode'] = 'check_authentication';
			$response = $this->fetcher->post($this->endpoint->server_url, $request_message->getFields());
			if (!$response) {
				return null;
			}
			$response_message = \Poodle\OpenID\Message::fromKVForm($response->body);
			if (400 == $response->status) {
				throw new Response_Exception($response_message);
			} else if (200 != $response->status && 206 != $response->status) {
				throw new Response_Exception('Server responded with invalid status: '.$response->status);
			}
			if (isset($response_message['invalidate_handle'])) {
				Association::remove($this->endpoint->server_url, $response_message['invalidate_handle']);
			}
			if ('true' != $response_message['is_valid']) {
				throw new Response_Exception('Server denied check_authentication');
			}
		}

		unset($this->fetcher);
	}

	protected function verifyV1Discovery($endpoint)
	{
		$k = self::V1_QUERY_CLAIMED_ID_KEY;
		$claimed_id = $_GET[$k] ?? null;
		if (!$claimed_id) {
			if (!$endpoint) {
				throw new Response_Exception('Claimed Identifier is missing');
			}
			$claimed_id = $endpoint->claimed_id;
		}

		$to_match = new \Poodle\OpenID\Provider\Endpoint();
		$to_match->type_uris  = array(\Poodle\OpenID::TYPE_V1_1);
		$to_match->LocalID    = $this->message['identity'];
		// Restore delegate information from the initiation phase
		$to_match->claimed_id = $claimed_id;

		if (!$to_match->LocalID) {
			throw new Response_Exception('Missing required field openid.identity');
		}

		$to_match_1_0 = clone $to_match;
		$to_match_1_0->type_uris = array(\Poodle\OpenID::TYPE_V1_0);

		if ($endpoint) {
			try {
				$this->verifyDiscoveryResult($endpoint, $to_match);
			}
			catch (TypeURIMismatch_Exception $e) {
				$this->verifyDiscoveryResult($endpoint, $to_match_1_0);
			}
			return $endpoint;
		}

		// Endpoint is either bad (failed verification) or None
		return $this->discoverAndVerify($to_match->claimed_id, array($to_match, $to_match_1_0));
	}

	protected function verifyV2Discovery($endpoint)
	{
		$to_match = new \Poodle\OpenID\Provider\Endpoint();
		$to_match->type_uris  = array(\Poodle\OpenID::TYPE_V2_0);
		$to_match->claimed_id = $this->message['claimed_id'];
		$to_match->LocalID    = $this->message['identity'];
		$to_match->server_url = $this->message['op_endpoint'];

		if (!$to_match->server_url) {
			throw new Response_Exception('OP Endpoint URL missing');
		}

		if (!$to_match->claimed_id) {
			// This is a response without identifiers, so there's
			// really no checking that we can do, so return an
			// endpoint that's for the specified `openid.op_endpoint'
			return \Poodle\OpenID\Provider\Endpoint::fromOPEndpointURL($to_match->server_url);
		}

		if (!$endpoint) {
			// The claimed ID doesn't match, so we have to do
			// discovery again. This covers not using sessions, OP
			// identifier endpoints and responses that didn't match
			// the original request.
			// oidutil.log('No pre-discovered information supplied.')
			return $this->discoverAndVerify($to_match->claimed_id, array($to_match));
		}

		// The claimed ID matches, so we use the endpoint that we
		// discovered in initiation. This should be the most common case.
		try {
			$this->verifyDiscoveryResult($endpoint, $to_match);
		} // TypeURIMismatch_Exception
		catch (\Exception $e) {
			$endpoint = $this->discoverAndVerify($to_match->claimed_id, array($to_match));
		}

		// The endpoint we return should have the claimed ID from the
		// message we just verified, fragment and all.
		$endpoint->claimed_id = $to_match->claimed_id;

		return $endpoint;
	}

	protected function discoverAndVerify($claimed_id, $to_match_endpoints)
	{
		$result = null;
  $services = \Poodle\OpenID\Discover::ID($claimed_id, $this->fetcher);

		if (!$services) {
			throw new Response_Exception("No OpenID information found at {$claimed_id}");
		}
		// Search the services resulting from discovery to find one
		// that matches the information from the assertion
		foreach ($services as $endpoint) {
			foreach ($to_match_endpoints as $to_match_endpoint) {
				try {
					$this->verifyDiscoveryResult($endpoint, $to_match_endpoint);
					// It matches, so discover verification has
					// succeeded. Return this endpoint.
					return $endpoint;
				}
				catch (TypeURIMismatch_Exception $e) {}
			}
		}

		throw new Response_Exception(sprintf('No matching endpoint found after discovering %s: %s', $claimed_id, $result->message));
	}

	protected function verifyDiscoveryResult($endpoint, $to_match)
	{
		// Every type URI that's in the to_match endpoint has to be
		// present in the discovered endpoint.
		foreach ($to_match->type_uris as $type_uri) {
			if (!$endpoint->usesExtension($type_uri)) {
				throw new TypeURIMismatch_Exception("Required type {$type_uri} not present");
			}
		}

		// Fragments do not influence discovery, so we can't compare a
		// claimed identifier with a fragment to discovered information.
		$defragged_claimed_id = preg_replace('/#.*$/Ds', '', $to_match->claimed_id);

		if ($defragged_claimed_id != $endpoint->claimed_id) {
			throw new Response_Exception(sprintf('Claimed ID mismatch. Expected %s, got %s', $defragged_claimed_id, $endpoint->claimed_id));
		}

		if ($to_match->getLocalID() != $endpoint->getLocalID()) {
			throw new Response_Exception(sprintf('Local ID mismatch. Expected %s, got %s', $to_match->getLocalID(), $endpoint->getLocalID()));
		}

		// If the server URL is None, this must be an OpenID 1
		// response, because op_endpoint is a required parameter in
		// OpenID 2. In that case, we don't actually care what the
		// discovered server_url is, because signature checking or
		// check_auth should take care of that check for us.
		if (!$to_match->server_url) {
			if ($to_match->preferredNamespace() != \Poodle\OpenID::NS_1_0) {
				throw new Response_Exception('Preferred namespace mismatch (bug)');
			}
		} else if ($to_match->server_url != $endpoint->server_url) {
			throw new Response_Exception(sprintf('OP Endpoint mismatch. Expected %s, got %s', $to_match->server_url, $endpoint->server_url));
		}
	}

}
