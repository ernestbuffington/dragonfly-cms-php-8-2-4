<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Provider;

class OpenID extends \Poodle\Auth\Provider
{
	function __construct($config=array())
	{
		parent::__construct($config);
	}

	public function getAction($credentials=array())
	{
		return new \Poodle\Auth\Result\Form(
			array(
				array('name'=>'openid_identifier', 'type'=>'text', 'label'=>'OpenID'),
			),
			'?auth='.$this->id,
			'auth-openid'
		);
	}

	public function authenticate($credentials)
	{
		if (empty($this->discover_uri) && empty($this->identifier)) {
			if (isset($_GET['openid_mode'])) {
				try {
					return $this->finish();
				} catch (\Exception $e) {
					return new \Poodle\Auth\Result\Error(self::ERR_FAILURE, $e->getMessage());
				}
			}
			if (!isset($credentials['openid_identifier'])) {
				return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'openid_mode not set');
			}
			$this->identifier = $credentials['openid_identifier'];
			if (!strpos($this->identifier, '://')) {
				/**
				 * Could be an invalid openid_identifier, try some magic with regex patterns
				 */
				$SQL = \Poodle::getKernel()->SQL;
				$result = $SQL->query("SELECT
					auth_detect_regex,
					auth_detect_discover_uri
				FROM {$SQL->TBL->auth_providers_detect}
				WHERE auth_provider_id = {$this->id}");
				while ($provider = $result->fetch_row()) {
					if ($provider[0] && $provider[1]) {
						$re = '#^'.$provider[0].'#i';
						if (preg_match($re, $this->identifier)) {
							$this->identifier = preg_replace($re, $provider[1], $this->identifier);
							break;
						}
					}
				}
			}
		}

		$discover_uri = $this->discover_uri ? $this->discover_uri : $this->identifier;

		$_SESSION['OpenID']['return_to'] = $this->getAuthURI($credentials);
		$request = \Poodle\OpenID\RelyingParty::start($discover_uri, $_SESSION['OpenID']['return_to']);
		if (!$request) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'Unknown OpenID provider for '.$discover_uri);
		}

		if (empty($credentials['identity_id'])) {
			if ($ext = $request->addNamespace(\Poodle\OpenID\Extensions\SREG::NS_1_1, 'sreg')) {
				$ext['required'] = 'email';
				$ext['optional'] = 'fullname,nickname,language,timezone';
//				$ext['optional'] = 'fullname,nickname,dob,gender,postcode,country,language,timezone';
			}

			if ($ext = $request->addNamespace(\Poodle\OpenID\Extensions\AX::NS_1_0, 'ax')) {
				// Google required: country, email, firstname, language, lastname
				$ext['required']       = 'email,firstname,language,lastname';
				$ext['if_available']   = 'fullname,nickname,timezone';
				$ext['type.email']     = \Poodle\OpenID\Extensions\AX::AX_DOM.'/contact/email';
				$ext['type.fullname']  = \Poodle\OpenID\Extensions\AX::AX_DOM.'/namePerson';
				$ext['type.nickname']  = \Poodle\OpenID\Extensions\AX::AX_DOM.'/namePerson/friendly';
//				$ext['type.dob']       = \Poodle\OpenID\Extensions\AX::AX_DOM.'/birthDate';
//				$ext['type.gender']    = \Poodle\OpenID\Extensions\AX::AX_DOM.'/person/gender';
//				$ext['type.postcode']  = \Poodle\OpenID\Extensions\AX::AX_DOM.'/contact/postalCode/home';
//				$ext['type.country']   = \Poodle\OpenID\Extensions\AX::AX_DOM.'/contact/country/home';
				$ext['type.language']  = \Poodle\OpenID\Extensions\AX::AX_DOM.'/pref/language';
				$ext['type.timezone']  = \Poodle\OpenID\Extensions\AX::AX_DOM.'/pref/timezone';
				$ext['type.firstname'] = \Poodle\OpenID\Extensions\AX::AX_DOM.'/namePerson/first';
				$ext['type.lastname']  = \Poodle\OpenID\Extensions\AX::AX_DOM.'/namePerson/last';
			}
		}

		$server_url = $request->endpoint->server_url;
		$fields = $request->message->getFields();
		// OpenID v1
		if ($request->message->isOpenIDv1()) {
			return new \Poodle\Auth\Result\Redirect(\Poodle\URI::appendArgs($server_url, $fields));
		}
		// OpenID v2
		$formfields = array();
		foreach ($fields as $name => $value) {
			$formfields[] = array('name'=>$name, 'value'=>$value, 'type'=>'hidden');
		}
		return new \Poodle\Auth\Result\Form(
			$formfields,
			$server_url,
			'auth-openid',
			true
		);
	}

	protected function finish()
	{
		$identity_id = null;
  if (!isset($_SESSION['OpenID']['return_to'])) {
			return new \Poodle\Auth\Result\Error(self::ERR_CREDENTIAL_INVALID, 'A database record with the supplied identity_id ('.$identity_id.') could not be found.');
		}
		$response    = \Poodle\OpenID\RelyingParty::finish(null, $_SESSION['OpenID']['return_to']);
//		$this->id    = \Poodle\Auth\Provider::getIdByClass(__CLASS__);
		$claimed_id  = preg_replace('/#.*/','',$response->endpoint->claimed_id);
//		$claimed_id  = $response->getDisplayIdentifier();
		$identity_id = \Poodle\Auth\Detect::identityId($this->id, $claimed_id);
		if (!$identity_id) {
			$ext = $response->message->getNamespaceByURI(\Poodle\OpenID\Extensions\AX::NS_1_0);
			if (!$ext) $ext = $response->message->getNamespaceByURI(\Poodle\OpenID\Extensions\SREG::NS_1_1);
			if (!$ext) $ext = $response->message->getNamespaceByURI(\Poodle\OpenID\Extensions\SREG::NS_1_0);
			$nick = $claimed_id;
			if (isset($ext['nickname'])) {
				$nick = $ext['nickname'];
			} else
			if (isset($ext['fullname'])) {
				$nick = $ext['fullname'];
			} else
			if (isset($ext['firstname'])) {
				$nick = $ext['firstname'] . (isset($ext['lastname'])?' '.$ext['lastname']:'');
			} else
			if (isset($ext['email'])) {
				$nick = preg_replace('#@.*#','',$ext['email']);
			}
			$fn = \Poodle\OpenID\Extensions\AX::AX_DOM.'/namePerson/first';
			$ln = \Poodle\OpenID\Extensions\AX::AX_DOM.'/namePerson/last';

			$user = \Poodle\Identity::factory(array(
				'nickname'  => $nick,
				'email'     => isset($ext['email']) ? mb_strtolower($ext['email']) : '',
				'givenname' => $ext[$fn] ?? '',
				'surname'   => $ext[$ln] ?? '',
				'language'  => isset($ext['language']) ? strtr(strtolower($ext['language']),'_','-') : '',
				'timezone'  => $ext['timezone'] ?? date_default_timezone_get(),
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
