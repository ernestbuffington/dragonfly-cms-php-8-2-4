<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\OpenID\Provider;

class Endpoint
{
	public
		$server_url  = null,    // 7.3.2.1
		$type_uris   = array(), // 7.3.2.1
		$LocalID     = null,    // 7.3.2.1.2
		$CanonicalID = null,    // 7.3.2.3
		$claimed_id  = null,
		$used_yadis  = false,   // whether this came from an XRDS
		$display_identifier = null;

	protected
		$id;

	function __get($k)
	{
		if ('id' === $k && !$this->id && $this->server_url) {
			$SQL = \Poodle::getKernel()->SQL;
			$row = $SQL->uFetchRow("SELECT endpoint_id FROM {$SQL->TBL->auth_providers_endpoints}
			WHERE server_url={$SQL->quote($this->server_url)}");
			if ($row) {
				$this->id = (int)$row[0];
			} else {
				$this->id = $SQL->TBL->auth_providers_endpoints->insert(array(
					'server_url' => $this->server_url,
					'used_yadis' => !!$this->used_yadis,
					'type_uris'  => implode("\n",$this->type_uris)
				), 'endpoint_id');
			}
		}
		return $this->$k;
	}

	public function getDisplayIdentifier()
	{
		if ($this->display_identifier) {
			return $this->display_identifier;
		}
		return $this->claimed_id ? preg_replace('/#.*$/Ds','',$this->claimed_id) : false;
	}

	public function usesExtension($extension_uri)
	{
		return in_array($extension_uri, $this->type_uris);
	}

	public function preferredNamespace()
	{
		if (in_array(\Poodle\OpenID::TYPE_V2_0_OP, $this->type_uris)
		 || in_array(\Poodle\OpenID::TYPE_V2_0, $this->type_uris))
		{
			return \Poodle\OpenID::NS_2_0;
		}
		return \Poodle\OpenID::NS_1_0;
	}

	/**
	 * Query this endpoint to see if it has any of the given type
	 * URIs. This is useful for implementing other endpoint classes
	 * that e.g. need to check for the presence of multiple versions
	 * of a single protocol.
	 *
	 * @param $type_uris The URIs that you wish to check
	 *
	 * @return all types that are in both in type_uris and
	 * $this->type_uris
	 */
	public function matchTypes($type_uris)
	{
		$result = array();
		foreach ($type_uris as $test_uri) {
			if ($this->supportsType($test_uri)) {
				$result[] = $test_uri;
			}
		}

		return $result;
	}

	public function supportsType($type_uri)
	{
		return (in_array($type_uri, $this->type_uris) || ((\Poodle\OpenID::TYPE_V2_0 == $type_uri) && $this->isOPIdentifier()));
	}

	public function isOpenIDv1()
	{
		return $this->preferredNamespace() != \Poodle\OpenID::NS_2_0;
	}

	public function isOPIdentifier()
	{
		return in_array(\Poodle\OpenID::TYPE_V2_0_OP, $this->type_uris);
	}

	public static function fromOPEndpointURL($op_endpoint_url)
	{
		// Construct an OP-Identifier OpenID_ServiceEndpoint object for
		// a given OP Endpoint URL
		$sep = new Endpoint();
		$sep->server_url = $op_endpoint_url;
		$sep->type_uris  = array(\Poodle\OpenID::TYPE_V2_0_OP);
		return $sep;
	}

	public function parseService($yadis_url, $uri, $type_uris, $service)
	{
		// Set the state of this object based on the contents of the service element.
		//  Return true on success, false on failure.
		$this->type_uris  = $type_uris;
		$this->server_url = $uri;
		$this->used_yadis = true;

		if (!$this->isOPIdentifier()) {
			$this->claimed_id = $yadis_url;
			// Find OP-Local Identifier using the xrd:LocalID and openid:Delegate
			// tags values from a Yadis Service element.
			// Returns false on discovery failure (when multiple
			// delegate/localID tags have different values).
			$parser = $service->parser;
			$tags = array();
			if (in_array(\Poodle\OpenID::TYPE_V2_0, $type_uris)) {
				$parser->registerNamespace('xrd', \Poodle\Yadis\XRDS::XMLNS_XRD_2_0);
				$tags[] = 'xrd:LocalID';
			}
			if (in_array(\Poodle\OpenID::TYPE_V1_1, $type_uris) || in_array(\Poodle\OpenID::TYPE_V1_0, $type_uris))
			{
				$parser->registerNamespace('openid', \Poodle\OpenID::XMLNS_1_0);
				$tags[] = 'openid:Delegate';
			}
			$local_id = null;
			foreach ($tags as $tag_name) {
				$tags = $service->getElements($tag_name);
				foreach ($tags as $tag) {
					$content = $parser->content($tag);
					if (null === $local_id) {
						$local_id = $content;
					} else if ($local_id != $content) {
						return false;
					}
				}
			}
			$this->LocalID = $local_id;
		}

		return true;
	}

	public function getLocalID()
	{
		// Return the identifier that should be sent as the openid.identity_url parameter to the server.
		if (!$this->LocalID && !$this->CanonicalID) {
			return $this->claimed_id;
		}
		return $this->LocalID ? $this->LocalID : $this->CanonicalID;
	}

}
