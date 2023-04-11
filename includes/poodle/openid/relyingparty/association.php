<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-authentication-2_0.html#associations
*/

namespace Poodle\OpenID\RelyingParty;

class Association
{
	public
		$server_url,
		$handle,
		$type,
		$secret;     // mac_key
	protected
		$etime;      // time() + expires_in

	public static function remove($url, $handle=null)
	{
		$SQL = \Poodle::getKernel()->SQL;
		$handle = $handle ? " AND assoc_handle={$SQL->quote($handle)}" : '';
		$SQL->TBL->auth_providers_assoc->delete("server_url={$SQL->quote($server_url)}{$handle}");
	}

	public static function fromEndpoint($endpoint, \Poodle\HTTP\Request $fetcher)
	{
		$SQL = \Poodle::getKernel()->SQL;
		$server_url = $endpoint->server_url;

		if ($row = self::getRecord($server_url))
		{
			if ($row['apa_etime'] > time()) {
				$assoc = new Association();
				$assoc->server_url = $row['server_url'];
				$assoc->handle     = $row['assoc_handle'];
				$assoc->type       = $row['assoc_type'];
				$assoc->secret     = base64_decode($row['secret']);
				$assoc->etime      = $row['apa_etime'];
				return $assoc;
			}
			$SQL->TBL->auth_providers_assoc->delete('apa_etime<='.time());
		}

		$hash_algo = 'sha1'; // size = 20
		$request_message = new \Poodle\OpenID\Message($endpoint->preferredNamespace());

		// 8.1.1
		$request_message['mode'] = 'associate';
		$request_message['assoc_type']   = 'HMAC-SHA1'; // 8.3: HMAC-SHA1 / HMAC-SHA256
		$request_message['session_type'] = 'DH-SHA1';   // 8.4: no-encryption / DH-SHA1 / DH-SHA256
		if ($request_message->isOpenIDv2() && in_array('sha256', hash_algos()))
		{
			$hash_algo = 'sha256'; // size = 32
			$request_message['assoc_type']   = 'HMAC-SHA256';
			$request_message['session_type'] = 'DH-SHA256';
		}
		// Note: Unless using transport layer encryption, "no-encryption" MUST NOT be used.
		if (\Poodle\URI::isHTTPS($server_url) || !\Poodle\Math::ENGINE)
		{
			$hash_algo = null;
			if ($request_message->isOpenIDv2()) {
				$request_message['session_type'] = 'no-encryption';
			} else {
				// 14.2.1.  Relying Parties
				// Relying Parties MUST send a blank session_type parameter in "no-encryption" association requests.
				unset($request_message['session_type']);
			}
		}
		// 8.1.2
		else
		{
			$dh = new \Poodle\OpenID\DiffieHellman();
			$request_message['dh_consumer_public'] = \Poodle\Math::longToBase64($dh->public);
			if (!$dh->usingDefaultValues()) {
				$request_message['dh_modulus'] = \Poodle\Math::longToBase64($dh->mod);
				$request_message['dh_gen']     = \Poodle\Math::longToBase64($dh->gen);
			}
		}

		$response = $fetcher->post($server_url, $request_message->getFields());
		if (!$response || ($response->status != 200 && $response->status != 206))
		{
			return false;
		}
		$response_message = \Poodle\OpenID\Message::fromKVForm($response->body);

		// 8.2.4 on error retry once?
		if (isset($response_message['error'])) {
			trigger_error('Poodle\\OpenID\\RelyingParty\\Association provider response error: '.$response_message['error'], E_USER_WARNING);
			if ('unsupported-type' != $response_message['error_code']|| !isset($response_message['assoc_type']))
			{
				return false;
			}
			if (!preg_match('#HMAC-(.+)#',$response_message['assoc_type'],$m) || !in_array(strtolower($m[1]), hash_algos()))
			{
				trigger_error('Poodle\\OpenID\\RelyingParty\\Association provider response unsupported assoc_type: '.$response_message['assoc_type'], E_USER_WARNING);
				return false;
			}
			$request_message['assoc_type'] = $response_message['assoc_type'];
			$request_message['session_type'] = $st = (isset($response_message['session_type']) ? $response_message['session_type'] : 'no-encryption');
			if ('no-encryption' != $st && (!\Poodle\Math::ENGINE || (preg_match('#DH-(.+)#',$st,$m) && !in_array(strtolower($m[1]), hash_algos()))))
			{
				trigger_error('Poodle\\OpenID\\RelyingParty\\Association provider response unsupported session_type: '.$response_message['session_type'], E_USER_WARNING);
				return false;
			}
			// Retry
			$response = $fetcher->post($server_url, $request_message->getFields());
			if (!$response || ($response->status != 200 && $response->status != 206)) {
				return false;
			}
			$response_message = \Poodle\OpenID\Message::fromKVForm($response->body);
			if (isset($response_message['error'])) {
				trigger_error('Poodle\\OpenID\\RelyingParty\\Association provider response error: '.$response_message['error'], E_USER_WARNING);
				return false;
			}
		}

		// 8.2.1
		// Extract the common fields from the response, raising an exception if they are not found
		$assoc_handle = $response_message['assoc_handle'];
		if (!$assoc_handle) {
			trigger_error('Received invalid assoc_handle from server association response');
			return false;
		}

		$assoc_type = $response_message['assoc_type'];
		if (!$assoc_type) {
			trigger_error('Received invalid assoc_type from server association response');
			return false;
		}

		// in seconds
		if (!isset($response_message['expires_in'])) {
			trigger_error('Received no expires_in from server association response');
			return false;
		}
		$expires_in = \Poodle\OpenID::unsigned_int(trim($response_message['expires_in']));
		if (false === $expires_in) {
			trigger_error('Received invalid expires_in from server association response');
			return false;
		}

		$session_type = $response_message['session_type'];
		if (!$session_type && $response_message->isOpenIDv1()) {
			$session_type = 'no-encryption';
		}
		if (!$session_type || $request_message['session_type'] != $session_type) {
			if (!$response_message->isOpenIDv1() || $session_type != 'no-encryption')
			{
				// In OpenID 1, any association request can result in
				// a 'no-encryption' association response.
				// Any other mismatch, regardless of protocol version results
				// in the failure of the association session altogether.
				trigger_error('Received invalid session_type from server association response');
				return false;
			}
		}

		// Extract the secret from the response.
		$secret = null;
		// 8.2.3
		if ($hash_algo
		 && isset($response_message['dh_server_public'])
		 && isset($response_message['enc_mac_key']))
		{
			$secret = $dh->xorSecret(\Poodle\Math::base64ToLong(
				$response_message['dh_server_public']),
				base64_decode($response_message['enc_mac_key']),
				$hash_algo
			);
		}
		// 8.2.2
		else if (isset($response_message['mac_key']))
		{
			$secret = base64_decode($response_message['mac_key']);
		}
		if (!$secret) { return false; }

		$assoc = new Association();
		$assoc->server_url = $server_url;
		$assoc->handle     = $assoc_handle;
		$assoc->type       = $assoc_type;
		$assoc->secret     = $secret;
		$assoc->etime      = time()+$expires_in;

		$SQL->TBL->auth_providers_assoc->insert(array(
			'server_url'  => $server_url,
			'assoc_handle'=> $assoc_handle,
			'assoc_type'  => $assoc_type,
			'apa_secret'  => base64_encode($secret),
			'apa_etime'   => $assoc->etime
		));

		return $assoc;
	}

	public static function fromURLHandle($server_url, $handle)
	{
		if ($row = self::getRecord($server_url, $handle))
		{
			$assoc = new Association();
			$assoc->server_url = $row['server_url'];
			$assoc->handle     = $row['assoc_handle'];
			$assoc->type       = $row['assoc_type'];
			$assoc->secret     = base64_decode($row['secret']);
			$assoc->etime      = (int)$row['apa_etime'];
			return $assoc;
		}
	}

	private static function getRecord($server_url, $handle=null)
	{
		$SQL = \Poodle::getKernel()->SQL;
		$handle = $handle ? " AND assoc_handle={$SQL->quote($handle)}" : '';
		$row = $SQL->uFetchAssoc("SELECT
			server_url,
			assoc_handle,
			assoc_type,
			apa_secret AS secret,
			apa_etime
		FROM {$SQL->TBL->auth_providers_assoc}
		WHERE server_url={$SQL->quote($server_url)}{$handle}");
		return $row ? $row : null;
	}

	public function isExpired() { return time()>$this->etime; }

	public function verifySignature($message)
	{
		$sig = $message['sig'];
		if (!$sig) { return false; }
		// Auth_OpenID_KVForm::fromArray
		$kv = '';
		foreach (explode(',', $message['signed']) as $k) {
			$v = $message[$k];
			if (false !== strpbrk($k, ":\n")) { throw new \Exception("Key '{$k}' contains an invalid character"); }
			if (false !== strpos($v,   "\n")) { throw new \Exception("Value of '{$k}' contains an invalid character"); }
			$kv .= "{$k}:{$v}\n";
		}
		$algo = null;
		if ('HMAC-SHA1' === $this->type)   { $algo = 'sha1'; }
		if ('HMAC-SHA256' === $this->type) { $algo = 'sha256'; }
		$calculated_sig = $algo ? base64_encode(hash_hmac($algo, $kv, $this->secret, true)) : null;
		return $calculated_sig == $sig;
	}

}
