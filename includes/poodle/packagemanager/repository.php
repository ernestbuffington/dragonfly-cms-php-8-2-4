<?php

namespace Poodle\PackageManager;

class Repository
{
	const
		PKB = '-----BEGIN PUBLIC KEY-----',
		PKE = '-----END PUBLIC KEY-----';

	public
		$id = 0,
		$name = '',
		$location = '',
		$enabled = false,
		$public_key = '',
		$verify_peer = true,
		$peer_fingerprint = array(
//			'sha1' => '9F2A5299A644539FF4552F1B1CF70B2CF143A57C',
//			'sha256' => 'E7AFDB79ED19C687D04B8530161C1DF7E428BBE43DBC5D885F3FBA9725627B20'
		);

	protected
		$packages = null;

	function __construct($id = 0)
	{
		$id = (int) $id;
		if ($id) {
			$SQL = \Poodle::getKernel()->SQL;
			$repo = $SQL->uFetchRow("SELECT
				repo_name,
				repo_location,
				repo_enabled,
				repo_public_key
			FROM {$SQL->TBL->packagemanager_repos}
			WHERE repo_id = {$id}");
			if (!$repo) {
				throw new \Exception('Repository not found');
			}
			$this->id = $id;
			$this->name = $repo[0];
			$this->location = $repo[1];
			$this->enabled = !!$repo[2];
			$this->public_key = $repo[3];
		}
	}

	function __get($key)
	{
		if ('packages' === $key) {
			return $this->getPackages();
		}
	}

	public function savePublicKey($filename)
	{
		if ($this->public_key) {
			return 0 < file_put_contents($filename, $this->getPublicKeyPEM());
		}
		return false;
	}

	public function getPublicKeyPEM()
	{
		return $this->public_key
			? static::PKB . "\n{$this->public_key}\n" . static::PKE
			: false;
	}

	public function getPackage($name)
	{
		foreach ($this->getPackages() as $package) {
			if ($name == $package->name) {
				$package->repository = $this;
				return $package;
			}
		}
		throw new \Exception("Repository package '{$name}' not found");
	}

	protected function getPackages($force = false)
	{
		if ($this->location && ($force || !is_array($this->packages))) {
			$this->packages = array();
			$CACHE = \Poodle::getKernel()->CACHE;
			$url = $this->location . 'packages.xml';
			$cache_key = 'Poodle/PackageManager/repo-'.md5($url);
			$data = $force ? null : $CACHE->get($cache_key);
			if ($data) {
				$this->packages = $data;
			} else {
				$context = stream_context_create(array('ssl' => array(
					'verify_peer' => $this->verify_peer,
					'verify_peer_name' => $this->verify_peer,
				)));
				if ($this->peer_fingerprint) {
					stream_context_set_option($context, 'ssl', 'peer_fingerprint', $this->peer_fingerprint);
				}
				$xml = file_get_contents($url, false, $context);
				if ($xml) {
					$data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
					$verified = true;
					if ($this->public_key) {
						$signature = $algo = false;
						if (isset($data->signature)) {
							$xml = trim(preg_replace('#<signature\s+algo[^>]+>[^>]+</signature>#', '', $xml));
							$algo = strtoupper(trim($data->signature['algo']));
							$signature = base64_decode(trim($data->signature));
						}
						$verified = $signature && $algo && openssl_verify(
							$xml,
							$signature,
							$this->getPublicKeyPEM(),
							constant("OPENSSL_ALGO_{$algo}")
						);
					} else if (isset($data->public_key)) {
						$public_key = (string) $data->public_key;
						if ($public_key) {
							$public_key = file_get_contents($public_key, false, $context);
						}
						if ($public_key) {
							$public_key = trim(str_replace(array(static::PKB,static::PKE), '', $public_key));
						}
						if ($public_key) {
							$this->public_key = null;
							\Poodle::getKernel()->SQL->TBL->packagemanager_repos->update(array(
								'repo_public_key' => $public_key,
								'repo_enabled' => 0,
							), "repo_id = {$this->id}");
							return array();
						}
					}
					if ($verified) {
						foreach ($data->package as $package) {
							$package = Package::fromSimpleXMLElement($package);
							$package->repository_id = $this->id;
							$this->packages[] = $package;
						}
						$CACHE->set($cache_key, $this->packages, 3600);
					} else {
						trigger_error("Signature failed for {$url}", E_USER_WARNING);
					}
				}
			}
		}
		return $this->packages ?: array();
	}

}
