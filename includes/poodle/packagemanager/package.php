<?php

namespace Poodle\PackageManager;

class Package
{
	use \Poodle\Events;

	public
		// package.xml and repository packages.xml
		$type           = '',
		$name           = '',
		$version        = '',
		$summary        = '',
		$description    = '',
		$url            = '',
		$time           = 0,
		$license        = '',
		$vendor         = '',
		$group          = '',
		$requires       = array(),
		$conflicts      = array(),
		$obsoletes      = array(),
		// package.xml
		$channel        = '',
		$contents       = array(),
		$changelog      = array(),
		// repository packages.xml
		$checksum       = '',
		$signature      = '',
		$location       = '', // relative download location
		$size_package   = 0,
		$size_installed = 0,
		// other
		$repository     = null,
		$repository_id  = 0;

	protected
		$phar_file;

	function __destruct()
	{
		if ($this->phar_file) {
			if (is_file($this->phar_file)) {
				unlink($this->phar_file);
			}
			if (is_file("{$this->phar_file}.pubkey")) {
				unlink("{$this->phar_file}.pubkey");
			}
		}
	}

	public static function fromSimpleXMLElement(\SimpleXMLElement $xml)
	{
		$p = new static;
		$p->type = (string) $xml['type'];
		$p->name = (string) $xml['name'];
		$p->version = (string) $xml['version'];
		$p->summary = (string) $xml->summary;
		$p->description = (string) $xml->description;
		$p->url = (string) $xml->url;
		$p->time = (int) $xml->time;
		$p->license = (string) $xml->license;
		$p->vendor = (string) $xml->vendor;
		$p->group = (string) $xml->group;
		// repository packages.xml
		if (isset($xml->checksum)) {
			$p->checksum = "{$xml->checksum['type']}:{$xml->checksum}";
		}
		if (isset($xml->signature)) {
			$p->signature = "{$xml->signature['type']}:{$xml->signature}";
		}
		if (isset($xml->location)) {
			$p->location = (string) $xml->location;
		}
		if (isset($xml->size)) {
			$p->size_package = (int) $xml->size['package'];
			$p->size_installed = (int) $xml->size['installed'];
		}
		return $p;
	}

	public function verifyChecksum($file)
	{
		$c = explode(':', $this->checksum, 2);
		return $c && hash_file($c[0], $file) === $c[1];
	}

	public function verifySignature(PackageData $phar)
	{
		$signature = $phar->getSignature();
		$s = explode(':', $this->signature, 2);
		return $s && $signature && $signature['hash_type'] === $s[0] && $signature['hash'] === $s[1];
	}

	public function getPackageData($tmp_dir = null)
	{
		if (!$this->repository) {
			throw new \Exception('Package repository not set');
		}
		$name = $this->repository->name .'/'. $this->name;
		$location = $this->repository->location . $this->location;

		$this->phar_file = strtr(($tmp_dir ?: sys_get_temp_dir()) . '/', '\\', DIRECTORY_SEPARATOR) . md5($this->repository->location) . '-' . basename($location);
		if (!is_file($this->phar_file)) {
			$event = new \Poodle\Events\Event('download');
			$event->complete = false;
			$event->progress = array('value' => 0, 'max' => 100);
			$this->dispatchEvent($event);

			$context = stream_context_create(array('ssl' => array(
				'verify_peer' => $this->repository->verify_peer,
				'verify_peer_name' => $this->repository->verify_peer,
			)));
			if ($this->repository->peer_fingerprint) {
				stream_context_set_option($context, 'ssl', 'peer_fingerprint', $this->repository->peer_fingerprint);
			}
			if (!copy($location, $this->phar_file, $context)) {
				throw new \Exception('Failed to download package');
			}

			$event = new \Poodle\Events\Event('download');
			$event->complete = true;
			$event->progress = array('value' => 100, 'max' => 100);
			$this->dispatchEvent($event);
		}
		if (!$this->verifyChecksum($this->phar_file)) {
			throw new \Exception("Invalid checksum for package {$name}");
		}
		// attach the public key for verification
		$this->repository->savePublicKey("{$this->phar_file}.pubkey");
		$phar = new PackageData($this->phar_file);
		if (!$this->verifySignature($phar)) {
			throw new \Exception("Invalid signature for package {$name}");
		}
		return $phar;
	}

}
