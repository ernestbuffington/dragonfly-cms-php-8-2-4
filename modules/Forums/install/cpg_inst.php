<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

if (!defined('ADMIN_MOD_INSTALL')) { exit; }

$name = basename(dirname(__DIR__));
if ('Forums' !== $name) {
	require_once '../../Forums/install/cpg_inst.php';
	eval("class {$name} extends Forums
	{
		protected
			\$prefix = '".strtolower($name)."_',
			\$is_instance_of = true;

		public function getXMLSchema()
		{
			\$xml = file_get_contents(__DIR__ . '/schema.xml');
			\$xml = preg_replace('#<table name=\"bb(attachments_config|config|extension_groups|extensions|forbidden_extensions)\">.*?</table>#s','',\$xml);
			return str_replace('<table name=\"bb','<table name=\"'.\$this->prefix, \$xml);
		}

		public function getXMLData()
		{
			\$xml = file_get_contents(__DIR__ . '/data.xml');
			\$xml = preg_replace('#<table name=\"bb(attachments_config|config|extension_groups|extensions|forbidden_extensions)\">.*?</table>#s','',\$xml);
			return str_replace('<table name=\"bb','<table name=\"'.\$this->prefix, \$xml);
		}
	}");
} else {
	class Forums extends \Dragonfly\ModManager\SetupBase
	{
		public
			$author      = 'CPG-Nuke Dev Team',
			$description = 'CPG Bulletin Board by CPG-Nuke Dev Team and based on phpBB 2.x which is released under the GNU GPL',
			$modname     = 'CPG-BB',
			$radmin      = true,
			$version     = '3.2.2',
			$website     = 'dragonflycms.org',
			$blocks      = true,
			$dbtables    = array();

		protected
			$prefix = 'bb',
			$is_instance_of = false;

		public function __construct()
		{
			$this->dbtables = array(
				$this->prefix.'attachments',
				$this->prefix.'attachments_desc',
				$this->prefix.'auth_access',
				$this->prefix.'banlist',
				$this->prefix.'categories',
				$this->prefix.'forums',
				$this->prefix.'forums_privileges',
				$this->prefix.'forums_watch',
				$this->prefix.'posts',
				$this->prefix.'posts_archive',
				$this->prefix.'posts_text',
				$this->prefix.'posts_text_archive',
				$this->prefix.'topic_icons',
				$this->prefix.'topics',
				$this->prefix.'topics_watch',
				$this->prefix.'vote_desc',
				$this->prefix.'vote_results',
				$this->prefix.'vote_voters',
				$this->prefix.'words',
			);
			if (!$this->is_instance_of) {
				$this->dbtables[] = $this->prefix.'attachments_config';
				$this->dbtables[] = $this->prefix.'config';
				$this->dbtables[] = $this->prefix.'extension_groups';
				$this->dbtables[] = $this->prefix.'extensions';
				$this->dbtables[] = $this->prefix.'forbidden_extensions';
			}
		}

		public function pre_install() { return true; }

		public function post_install()
		{
			\Dragonfly\Modules\Your_Account\Userinfo::hookEventListener('display', 'Dragonfly\Modules\Forums\Userinfo::displayBlock');
			return true;
		}

		public function pre_upgrade($prev_version)
		{
			global $db;
			$prefix = $db->TBL->prefix.$this->prefix;
			if (version_compare($prev_version, '3.0.4', '<')) {
				$db->query("UPDATE {$prefix}forums SET prune_next=0 WHERE prune_next IS NULL");
				$db->query("UPDATE {$prefix}topics SET icon_id=0 WHERE icon_id IS NULL");
			}

			if (version_compare($prev_version, '3.0.14', '<')) {
				$result = $db->query("SELECT forum_id, user_id, COUNT(*)
					FROM {$prefix}forums_privileges
					GROUP BY 1, 2");
				while ($row = $result->fetch_row()) {
					if (1 < $row[2]) {
						--$row[2];
						$db->query("DELETE FROM {$prefix}forums_privileges WHERE forum_id={$row[0]} AND user_id={$row[1]} LIMIT {$row[2]}");
					}
				}
				$result = $db->query("SELECT forum_id, user_id, COUNT(*)
					FROM {$prefix}forums_watch
					GROUP BY 1, 2");
				while ($row = $result->fetch_row()) {
					if (1 < $row[2]) {
						--$row[2];
						$db->query("DELETE FROM {$prefix}forums_watch WHERE forum_id={$row[0]} AND user_id={$row[1]} LIMIT {$row[2]}");
					}
				}
			}

			return true;
		}

		public function post_upgrade($prev_version)
		{
			global $db;
			$prefix = $db->TBL->prefix.$this->prefix;

			if (version_compare($prev_version, '3.0.4', '<') && isset($db->TBL->bbforum_archive)) {
				$result = $db->query("SELECT forum_id, archive_days, archive_freq FROM {$prefix}forum_archive");
				while ($row = $result->fetch_row()) {
					$db->query("UPDATE {$prefix}forums SET archive_days={$row[1]}, archive_freq={$row[2]} WHERE forum_id={$row[0]}");
				}
				$db->query("DROP TABLE {$prefix}forum_archive");
				$result = $db->query("SELECT forum_id, prune_days, prune_freq FROM {$prefix}forum_prune");
				while ($row = $result->fetch_row()) {
					$db->query("UPDATE {$prefix}forums SET prune_days={$row[1]}, prune_freq={$row[2]} WHERE forum_id={$row[0]}");
				}
				$db->query("DROP TABLE {$prefix}forum_prune");
			}

			if (version_compare($prev_version, '3.0.6', '<')) {
				$mid = $db->uFetchRow("SELECT mid FROM {$db->TBL->modules} WHERE title = '" . __CLASS__ . "'");
				$dir = $db->uFetchRow("SELECT config_value FROM {$prefix}attachments_config WHERE config_name = 'upload_dir'");
				if ($mid) {
					$db->query("INSERT INTO {$db->TBL->users_uploads} (identity_id, module_id, upload_time, upload_file, upload_size, upload_name)
					SELECT DISTINCT user_id_1, {$mid[0]}, filetime, SUBSTRING('{$dir[0]}/'||physical_filename,1,180), filesize, SUBSTRING(real_filename,1,180)
					FROM {$prefix}attachments_desc d, {$prefix}attachments a
					WHERE a.attach_id = d.attach_id");

					$db->query("UPDATE {$prefix}attachments_desc SET upload_id = (SELECT upload_id FROM {$db->TBL->users_uploads}
						WHERE module_id={$mid[0]} AND upload_file=SUBSTRING('{$dir[0]}/'||physical_filename,1,180) AND upload_time=filetime)");
				}
			}

			if (version_compare($prev_version, '3.0.7', '<')) {
				$db->query("UPDATE {$prefix}topic_icons SET icon_url='images/icons/misc/asterisk.gif', icon_name='asterisk'
					WHERE icon_url='images/icons/misc/asterix.gif'");
			}
/*
			if (version_compare($prev_version, '3.0.8', '<')) {
				$db->query("DROP TABLE bbattach_quota");
				$db->query("DROP TABLE bbquota_limits");
				$db->query("DELETE FROM bbattachments WHERE privmsgs_id > 0");
				$db->query("DROP INDEX attach_id_privmsgs_id ON bbattachments");
				$db->query("ALTER TABLE bbattachments DROP COLUMN privmsgs_id");
				$db->query("ALTER TABLE bbattachments DROP COLUMN user_id_2");
				$db->query("ALTER TABLE bbattachments_desc DROP COLUMN physical_filename");
				$db->query("ALTER TABLE bbattachments_desc DROP COLUMN real_filename");
				$db->query("ALTER TABLE bbattachments_desc DROP COLUMN filesize");
				$db->query("ALTER TABLE bbattachments_desc DROP COLUMN filetime");
				$db->query("DELETE FROM {$prefix}attachments_config WHERE config_name IN ('attachment_quota','default_upload_quota','max_filesize')");
			}
*/

			if (version_compare($prev_version, '3.0.12', '<')) {
				$db->query("UPDATE {$prefix}topics SET topic_title = REPLACE(topic_title, {$db->quote('&#039;')}, {$db->quote('\'')})");
				$db->query("UPDATE {$prefix}topics SET topic_title = REPLACE(topic_title, {$db->quote('&apos;')}, {$db->quote('\'')})");
				$db->query("UPDATE {$prefix}topics SET topic_title = REPLACE(topic_title, {$db->quote('&quot;')}, {$db->quote('"')})");
				$db->query("UPDATE {$prefix}topics SET topic_title = REPLACE(topic_title, {$db->quote('&gt;')}, {$db->quote('>')})");
				$db->query("UPDATE {$prefix}topics SET topic_title = REPLACE(topic_title, {$db->quote('&lt;')}, {$db->quote('<')})");
				$db->query("UPDATE {$prefix}topics SET topic_title = REPLACE(topic_title, {$db->quote('&amp;')}, {$db->quote('&')})");
			}

			if (version_compare($prev_version, '3.0.13', '<')) {
				$db->query("UPDATE {$prefix}vote_desc SET vote_text = REPLACE(vote_text, {$db->quote('&#039;')}, {$db->quote('\'')})");
				$db->query("UPDATE {$prefix}vote_desc SET vote_text = REPLACE(vote_text, {$db->quote('&apos;')}, {$db->quote('\'')})");
				$db->query("UPDATE {$prefix}vote_desc SET vote_text = REPLACE(vote_text, {$db->quote('&quot;')}, {$db->quote('"')})");
				$db->query("UPDATE {$prefix}vote_desc SET vote_text = REPLACE(vote_text, {$db->quote('&gt;')}, {$db->quote('>')})");
				$db->query("UPDATE {$prefix}vote_desc SET vote_text = REPLACE(vote_text, {$db->quote('&lt;')}, {$db->quote('<')})");
				$db->query("UPDATE {$prefix}vote_desc SET vote_text = REPLACE(vote_text, {$db->quote('&amp;')}, {$db->quote('&')})");

				$db->query("UPDATE {$prefix}vote_results SET vote_option_text = REPLACE(vote_option_text, {$db->quote('&#039;')}, {$db->quote('\'')})");
				$db->query("UPDATE {$prefix}vote_results SET vote_option_text = REPLACE(vote_option_text, {$db->quote('&apos;')}, {$db->quote('\'')})");
				$db->query("UPDATE {$prefix}vote_results SET vote_option_text = REPLACE(vote_option_text, {$db->quote('&quot;')}, {$db->quote('"')})");
				$db->query("UPDATE {$prefix}vote_results SET vote_option_text = REPLACE(vote_option_text, {$db->quote('&gt;')}, {$db->quote('>')})");
				$db->query("UPDATE {$prefix}vote_results SET vote_option_text = REPLACE(vote_option_text, {$db->quote('&lt;')}, {$db->quote('<')})");
				$db->query("UPDATE {$prefix}vote_results SET vote_option_text = REPLACE(vote_option_text, {$db->quote('&amp;')}, {$db->quote('&')})");
			}

			if (version_compare($prev_version, '3.0.16', '<')) {
				$db->query("DELETE FROM {$db->TBL->bbattachments_config}
				WHERE config_name IN ('ftp_pasv_mode','ftp_user','ftp_pass','allow_ftp_upload','ftp_server','ftp_path','download_path')");
			}

			if (version_compare($prev_version, '3.1.1', '<')) {
				$db->query("UPDATE {$prefix}posts_text_archive SET
					post_subject = REPLACE(
						REPLACE(
							REPLACE(
								REPLACE(
									REPLACE(
										REPLACE(post_subject, {$db->quote('&#039;')}, {$db->quote('\'')})
										, {$db->quote('&apos;')}, {$db->quote('\'')})
									, {$db->quote('&quot;')}, {$db->quote('"')})
								, {$db->quote('&gt;')}, {$db->quote('>')})
							, {$db->quote('&lt;')}, {$db->quote('<')})
						, {$db->quote('&amp;')}, {$db->quote('&')}),
					post_text = REPLACE(
						REPLACE(
							REPLACE(
								REPLACE(
									REPLACE(
										REPLACE(post_text, {$db->quote('&#039;')}, {$db->quote('\'')})
										, {$db->quote('&apos;')}, {$db->quote('\'')})
									, {$db->quote('&quot;')}, {$db->quote('"')})
								, {$db->quote('&gt;')}, {$db->quote('>')})
							, {$db->quote('&lt;')}, {$db->quote('<')})
						, {$db->quote('&amp;')}, {$db->quote('&')})");
			}

			if (version_compare($prev_version, '3.2.0', '<')) {
				$qr = $db->query("SELECT post_id, post_subject, post_text FROM {$prefix}posts_text WHERE post_search IS NULL");
				$pre_3_1 = version_compare($prev_version, '3.1.0', '<');
				$html = array(
					'&#039;' => '\'',
					'&apos;' => '\'',
					'&quot;' => '"',
					'&gt;'   => '>',
					'&lt;'   => '<',
				);
				$pgsql = ('postgresql' == strtolower($db->engine));
				while ($r = $qr->fetch_row()) {
					$r[2] = preg_replace('#(\\[/?[a-z]+)(:1)?:[0-9a-z]+#si', '$1', $r[2]);
					if ($pre_3_1) {
						$r[1] = strtr($r[1], $html);
						$r[1] = str_replace('&amp;', '&', $r[1]);
						$r[2] = strtr($r[2], $html);
						$r[2] = str_replace('&amp;', '&', $r[2]);
						$r[3] = $r[1] . ' ' . $r[2];
						if ($pgsql) {
							$r[1] = $db->quote($r[1]);
							$r[2] = $db->quote($r[2]);
							$r[3] = "to_tsvector({$db->quote($r[3])})";
//							$r[3] = "setweight(to_tsvector({$r[1]}), 'A') || setweight(to_tsvector({$r[2]}), 'D')";
						} else {
							$r[1] = $db->quote($r[1]);
							$r[2] = $db->quote($r[2]);
							$r[3] = $db->quote(static::as_search_txt($r[3]));
						}
						$db->query("UPDATE {$prefix}posts_text SET
							post_subject = {$r[1]},
							post_text = {$r[2]},
							post_search = {$r[3]}
						WHERE post_id = {$r[0]}");
					} else {
						$r[3] = $r[1] . ' ' . $r[2];
						if ($pgsql) {
							$r[2] = $db->quote($r[2]);
							$r[3] = "to_tsvector({$db->quote($r[3])})";
//							$r[3] = "setweight(to_tsvector({$db->quote($r[1])}), 'A') || setweight(to_tsvector({$db->quote($r[2])}), 'D')";
						} else {
							$r[2] = $db->quote($r[2]);
							$r[3] = $db->quote(static::as_search_txt($r[3]));
						}
						$db->query("UPDATE {$prefix}posts_text SET
							post_text = {$r[2]},
							post_search = {$r[3]}
						WHERE post_id = {$r[0]}");
					}
 				}
			}

			if (version_compare($prev_version, '3.2.1', '<')) {
				try {
					\Dragonfly\Modules\Your_Account\Userinfo::hookEventListener('display', 'Dragonfly\Modules\Forums\Userinfo::displayBlock');
				} catch (\Exception $e) {}
			}

			if (version_compare($prev_version, '3.2.2', '<')) {
				$db->query("DROP TABLE {$prefix}search_wordlist");
				$db->query("DROP TABLE {$prefix}search_wordmatch");
			}

			require_once(dirname(__DIR__) . '/classes/BoardCache.php');
			BoardCache::cacheDelete('board_config');

			return true;
		}

		public function pre_uninstall()  { return true; }

		public function post_uninstall() { return true; }

		protected static function as_search_txt($str)
		{
			$str = preg_replace('#\\[quote=.*?\\].*?\\[/quote\\]#si', '', $str);
			$str = preg_replace('#\\[/?\w.*?\\]#si', '', $str);
			$str = \Poodle\Input::fixSpaces($str);
			$str = \Poodle\Unicode::stripModifiers($str);
			$str = preg_replace('#[^\p{L}\p{N}"\-\+]+#su', ' ', $str); # strip non-Letters/non-Numbers
			return trim(preg_replace('#\s[^\s]{1,2}\s#u', ' ', " {$str} "));
		}
	}
}
