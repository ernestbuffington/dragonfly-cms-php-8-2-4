<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\ModManager;

if (!defined('ADMIN_MOD_INSTALL')) { exit; }

if (!class_exists('Coppermine', false)) {
	class Coppermine extends SetupBase
	{
		public
			$author      = 'CPG-Nuke Dev Team and Grégory Demar',
			$dbtables    = array(),
			$description = 'Coppermine Photo Gallery ported for Dragonfly™ by the CPG-Nuke Dev Team',
			$modname     = 'Coppermine',
			$radmin      = true,
			$version     = '1.3.5',
			$website     = 'coppermine-gallery.net';

		protected
			$prefix = 'cpg_',
			$base   = 'coppermine';

		function __construct()
		{
			$this->dbtables = array(
				$this->prefix.'albums',
				$this->prefix.'categories',
				$this->prefix.'comments',
				$this->prefix.'config',
				$this->prefix.'exif',
				$this->prefix.'favorites',
				$this->prefix.'pictures',
				$this->prefix.'votes'
			);
			if ('cpg_' === $this->prefix) {
				$this->dbtables[] = 'cpg_usergroups';
				$this->dbtables[] = 'cpg_installs';
			}
		}

		public function pre_install() { return true; }

		public function post_install()
		{
			$this->add_query('INSERT', 'cpg_installs', "DEFAULT, '{$this->base}', '{$this->prefix}', '{$this->version}'");
			return true;
		}

		public function pre_upgrade($prev_version)
		{
			global $db;
			$prefix = $db->TBL->prefix . $this->prefix;
			if (version_compare($prev_version, '1.3.6', '<')) {
				$db->query("UPDATE {$prefix}pictures SET mtime = 0 WHERE mtime IS NULL");
			}
			return true;
		}

		public function post_upgrade($prev_version)
		{
			global $db;
			$prefix = $db->TBL->prefix . $this->prefix;
			if (version_compare($prev_version, '1.3.4', '<')) {
				// change FIRST_USER_CAT to USER_GAL_CAT
				$db->query("UPDATE {$prefix}albums SET user_id = category - 10000 WHERE category > 10000");
			}
			if (version_compare($prev_version, '1.3.5', '<')) {
				$db->query("UPDATE {$prefix}albums SET uploads = 1 WHERE uploads > 1");
				$mid = $db->uFetchRow("SELECT mid FROM {$db->TBL->modules} WHERE title = '" . basename(dirname(__DIR__)) . "'");
				if ($mid) {
					$db->query("INSERT INTO {$db->TBL->users_uploads} (identity_id, module_id, upload_time, upload_file, upload_size, upload_name)
					SELECT owner_id, {$mid[0]}, ctime, SUBSTRING(filepath || filename,1,180), filesize, filename
					FROM {$prefix}pictures");
					$db->query("UPDATE {$prefix}pictures SET upload_id = (SELECT upload_id FROM {$db->TBL->users_uploads}
						WHERE module_id={$mid[0]} AND upload_file=SUBSTRING(filepath || filename,1,180) AND upload_time=ctime)");
				}
			}
			return true;
		}

		public function pre_uninstall() { return true; }

		public function post_uninstall() { return true; }
	}
}

$name = basename(dirname(__DIR__));
if ('coppermine' !== $name) {
	eval("class {$name} extends Coppermine
	{
		protected
			\$prefix = '".strtolower($name)."_',
			\$base = '{$name}_';

		public function getXMLSchema()
		{
			\$xml = file_get_contents(__DIR__ . '/schema.xml');
			\$xml = preg_replace('#<table name=\"cpg_(usergroups|installs)\">.*?</table>#s','',\$xml);
			return str_replace('<table name=\"cpg_','<table name=\"'.\$this->prefix, \$xml);
		}

		public function getXMLData()
		{
			\$xml = file_get_contents(__DIR__ . '/data.xml');
			\$xml = preg_replace('#<table name=\"cpg_(usergroups|installs)\">.*?</table>#s','',\$xml);
			return str_replace('<table name=\"cpg_','<table name=\"'.\$this->prefix, \$xml);
		}
	}");
}
