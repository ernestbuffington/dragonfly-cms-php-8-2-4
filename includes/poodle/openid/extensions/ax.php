<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-attribute-exchange-1_0.html
	http://www.axschema.org/types/ gone?
*/

namespace Poodle\OpenID\Extensions;

class AX extends \Poodle\OpenID\Message_Fields
{
	const
		AX_DOM = 'http://axschema.org', // use http://schema.openid.net?

		NS_1_0 = 'http://openid.net/srv/ax/1.0';

	private static
		$sreg_map = array(
			'country'   => 'http://axschema.org/contact/country/home',
			'dob'       => 'http://axschema.org/birthDate',
			'email'     => 'http://axschema.org/contact/email',
			'fullname'  => 'http://axschema.org/namePerson',
			'firstname' => 'http://axschema.org/namePerson/first',
			'lastname'  => 'http://axschema.org/namePerson/last',
			'gender'    => 'http://axschema.org/person/gender',
			'language'  => 'http://axschema.org/pref/language',
			'nickname'  => 'http://axschema.org/namePerson/friendly',
			'postcode'  => 'http://axschema.org/contact/postalCode/home',
			'timezone'  => 'http://axschema.org/pref/timezone'
		);

	protected
		$valid_keys = array(
			'mode',
			'type.*',             # <alias>
			'count.*',            # <alias>
			'update_url',
			# 5.1 request
			'required',
			'if_available',
			# 5.2 mode=fetch_response
			'value.*',            # <alias>
			'value.*.*',          # <number>
			# 6 store data @ OP is not supported
			# 6.1 mode=store_request
			# 6.2.1 mode=store_response_success
			# 6.2.1 mode=store_response_failure
		);

	function __construct($uri, $alias=null)
	{
		parent::__construct(self::NS_1_0, $alias?$alias:'ax');
		# mode is required
		$this->fields['mode'] = 'fetch_request';
	}

	protected function fixKey($k)
	{
		// Attribute aliases MUST NOT contain newline, colon (:), commas (,) and periods (.)  characters
		if (preg_match('#^((type|count|value)\\.[^:,\\.]+|value\\.[^:,\\.]+\\.[0-9]+)$#D', $k)) return $k;
		// Find by type uri
		if (isset(self::$sreg_map[$k])) $uri = self::$sreg_map[$k];
		if (0===strpos($k,self::AX_DOM)) $uri = $k;
		if (isset($uri)) {
			$type = array_search($uri, $this->fields, true);
			if ($type && preg_match('#^type.([^:,\\.]+)#',$type,$m)) return 'value.'.$m[1];
			return 'value.'.array_search($uri, self::$sreg_map, true);
		}
		// Default
		return parent::fixKey($k);
	}
}

\Poodle\OpenID\Message::registerNamespaceClass(AX::NS_1_0, 'Poodle\\OpenID\\Extensions\\AX');
